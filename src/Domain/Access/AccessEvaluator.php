<?php
/**
 * Central Access Evaluation Service.
 *
 * @package WPCalibrate\CommerceAccessRules
 */

declare(strict_types=1);

namespace WPCalibrate\CommerceAccessRules\Domain\Access;

use WPCalibrate\CommerceAccessRules\Capabilities;
use WPCalibrate\CommerceAccessRules\Domain\Rule\AccessRule;
use WPCalibrate\CommerceAccessRules\Domain\Rule\RuleRepository;
use WPCalibrate\CommerceAccessRules\Infrastructure\Cache\AccessCache;
use WPCalibrate\CommerceAccessRules\WooCommerce\PurchaseEvaluator;
use WPCalibrate\CommerceAccessRules\WooCommerce\ProductResolver;

/**
 * AccessEvaluator class.
 */
final class AccessEvaluator {

	private RuleRepository $rule_repo;
	private PurchaseEvaluator $purchase_evaluator;
	private AccessCache $cache;

	/**
	 * Constructor.
	 *
	 * @param RuleRepository    $rule_repo          Rule repository.
	 * @param PurchaseEvaluator $purchase_evaluator Purchase evaluator.
	 * @param AccessCache       $cache              Access cache.
	 */
	public function __construct(
		RuleRepository $rule_repo,
		PurchaseEvaluator $purchase_evaluator,
		AccessCache $cache
	) {
		$this->rule_repo          = $rule_repo;
		$this->purchase_evaluator = $purchase_evaluator;
		$this->cache              = $cache;
	}

	/**
	 * Evaluate access for a user to a specific content post/page/product.
	 *
	 * @param int|null $user_id User ID or null for current logged-in user.
	 * @param int      $post_id Content post ID.
	 * @return AccessResult
	 */
	public function evaluate_content_access( ?int $user_id, int $post_id ): AccessResult {
		$uid = null !== $user_id ? $user_id : (int) get_current_user_id();

		// 1. Check if user is an administrator with management capability (preview bypass).
		if ( $uid > 0 && Capabilities::can_manage_rules( $uid ) ) {
			return AccessResult::admin_bypass();
		}

		// 2. Check cache.
		$cache_key = "content_{$post_id}";
		$cached    = $this->cache->get( $uid, $cache_key );
		if ( null !== $cached ) {
			return $cached;
		}

		// 3. Get rules assigned to content.
		$rules = $this->rule_repo->get_rules_for_content( $post_id );

		// If no rules assigned, unrestricted!
		if ( empty( $rules ) ) {
			$result = AccessResult::allow( null, null, null );
			$this->cache->set( $uid, $cache_key, $result );
			return $result;
		}

		// 4. Evaluate multiple rules.
		// Multiple rules logic: 'OR' (satisfying any rule grants access) or 'AND' (must satisfy all). Default: 'OR'.
		$multiple_logic = get_option( 'wpcal_multiple_rules_logic', 'OR' );
		$highest_priority_rule = $rules[0];
		$allowed_grants        = array();
		$denial_reasons        = array();

		foreach ( $rules as $rule ) {
			$rule_result = $this->evaluate_rule( $uid, $rule );
			if ( $rule_result->is_allowed() ) {
				$allowed_grants[] = $rule_result;
				if ( 'OR' === $multiple_logic ) {
					// In OR mode, first matching rule grants access.
					$this->cache->set( $uid, $cache_key, $rule_result );
					return $rule_result;
				}
			} else {
				$denial_reasons[] = $rule_result;
				if ( 'AND' === $multiple_logic ) {
					// In AND mode, first failing rule denies access.
					$this->cache->set( $uid, $cache_key, $rule_result );
					return $rule_result;
				}
			}
		}

		// If we reach here in 'OR' mode and no grant was found, deny with highest priority rule's configuration.
		if ( 'OR' === $multiple_logic && empty( $allowed_grants ) ) {
			$first_denial = ! empty( $denial_reasons ) ? $denial_reasons[0] : null;
			$result = $this->build_denial_result( $highest_priority_rule, $first_denial ? $first_denial->get_reason_code() : ReasonCode::NO_QUALIFYING_PURCHASE );
			$this->cache->set( $uid, $cache_key, $result );
			return $result;
		}

		// If we reach here in 'AND' mode and all granted:
		if ( 'AND' === $multiple_logic && ! empty( $allowed_grants ) ) {
			$result = $allowed_grants[0];
			$this->cache->set( $uid, $cache_key, $result );
			return $result;
		}

		// Fail-closed default.
		$result = $this->build_denial_result( $highest_priority_rule, ReasonCode::NO_QUALIFYING_PURCHASE );
		$this->cache->set( $uid, $cache_key, $result );
		return $result;
	}

	/**
	 * Evaluate access for a user against a single specific rule (e.g. for shortcode or block).
	 *
	 * @param int        $user_id User ID.
	 * @param AccessRule $rule    Rule entity.
	 * @return AccessResult
	 */
	public function evaluate_rule( int $user_id, AccessRule $rule ): AccessResult {
		// If rule is disabled/inactive, it does not restrict.
		if ( ! $rule->is_active() ) {
			return AccessResult::allow( $rule->get_id(), 'rule_disabled', null );
		}

		// Check fixed date window on rule level if specified.
		$date_result = $this->check_fixed_dates( $rule );
		if ( null !== $date_result ) {
			return $this->build_denial_result( $rule, $date_result );
		}

		$condition_groups = $rule->get_condition_groups();
		if ( empty( $condition_groups ) ) {
			// No conditions configured -> allow.
			return AccessResult::allow( $rule->get_id(), 'empty_conditions', null );
		}

		$groups_relation = $rule->get_groups_relation(); // 'AND' or 'OR'
		$group_results   = array();
		$best_grant      = null;

		foreach ( $condition_groups as $group ) {
			$group_eval = $this->evaluate_condition_group( $user_id, $rule, $group );
			$group_results[] = $group_eval;

			if ( $group_eval['passed'] ) {
				if ( null === $best_grant || ( isset( $group_eval['grant'] ) && $group_eval['grant'] instanceof AccessResult ) ) {
					$best_grant = $group_eval['grant'] ?? AccessResult::allow( $rule->get_id(), 'condition_met' );
				}
				if ( 'OR' === $groups_relation ) {
					break; // In OR mode, one passing group is enough.
				}
			} elseif ( 'AND' === $groups_relation ) {
				// In AND mode, any failing group fails the rule.
				return $this->build_denial_result( $rule, $group_eval['reason_code'] );
			}
		}

		if ( 'OR' === $groups_relation ) {
			$passed_any = false;
			foreach ( $group_results as $gr ) {
				if ( $gr['passed'] ) {
					$passed_any = true;
					break;
				}
			}
			if ( $passed_any && null !== $best_grant ) {
				return $best_grant;
			}
			$fail_reason = ! empty( $group_results[0]['reason_code'] ) ? $group_results[0]['reason_code'] : ReasonCode::NO_QUALIFYING_PURCHASE;
			return $this->build_denial_result( $rule, $fail_reason );
		}

		// 'AND' mode passed all groups!
		return $best_grant ?? AccessResult::allow( $rule->get_id(), 'all_groups_passed' );
	}

	/**
	 * Evaluate a single condition group.
	 *
	 * @param int                  $user_id User ID.
	 * @param AccessRule           $rule    Rule entity.
	 * @param array<string, mixed> $group   Group configuration.
	 * @return array{passed: bool, reason_code: string, grant?: AccessResult}
	 */
	private function evaluate_condition_group( int $user_id, AccessRule $rule, array $group ): array {
		$relation   = isset( $group['relation'] ) && strtoupper( (string) $group['relation'] ) === 'OR' ? 'OR' : 'AND';
		$conditions = isset( $group['conditions'] ) && is_array( $group['conditions'] ) ? $group['conditions'] : array();

		if ( empty( $conditions ) ) {
			return array(
				'passed'      => true,
				'reason_code' => ReasonCode::ALLOWED,
			);
		}

		$condition_results = array();
		$grant_result      = null;

		foreach ( $conditions as $cond ) {
			$eval = $this->evaluate_single_condition( $user_id, $rule, $cond );
			$condition_results[] = $eval;

			if ( $eval['passed'] ) {
				if ( isset( $eval['grant'] ) ) {
					$grant_result = $eval['grant'];
				}
				if ( 'OR' === $relation ) {
					return array(
						'passed'      => true,
						'reason_code' => ReasonCode::ALLOWED,
						'grant'       => $grant_result ?? AccessResult::allow( $rule->get_id(), 'condition_passed' ),
					);
				}
			} elseif ( 'AND' === $relation ) {
				return array(
					'passed'      => false,
					'reason_code' => $eval['reason_code'],
				);
			}
		}

		if ( 'OR' === $relation ) {
			$first_fail = $condition_results[0]['reason_code'] ?? ReasonCode::NO_QUALIFYING_PURCHASE;
			return array(
				'passed'      => false,
				'reason_code' => $first_fail,
			);
		}

		// All conditions passed in AND relation.
		return array(
			'passed'      => true,
			'reason_code' => ReasonCode::ALLOWED,
			'grant'       => $grant_result ?? AccessResult::allow( $rule->get_id(), 'group_passed' ),
		);
	}

	/**
	 * Evaluate a single condition.
	 *
	 * @param int                  $user_id User ID.
	 * @param AccessRule           $rule    Rule entity.
	 * @param array<string, mixed> $cond    Condition data.
	 * @return array{passed: bool, reason_code: string, grant?: AccessResult}
	 */
	private function evaluate_single_condition( int $user_id, AccessRule $rule, array $cond ): array {
		$type = $cond['type'] ?? '';

		switch ( $type ) {
			case 'logged_in':
				if ( $user_id > 0 ) {
					return array(
						'passed'      => true,
						'reason_code' => ReasonCode::ALLOWED,
						'grant'       => AccessResult::allow( $rule->get_id(), 'logged_in' ),
					);
				}
				return array(
					'passed'      => false,
					'reason_code' => ReasonCode::GUEST,
				);

			case 'logged_out':
				if ( $user_id <= 0 ) {
					return array(
						'passed'      => true,
						'reason_code' => ReasonCode::ALLOWED,
						'grant'       => AccessResult::allow( $rule->get_id(), 'logged_out' ),
					);
				}
				return array(
					'passed'      => false,
					'reason_code' => ReasonCode::ROLE_MISMATCH,
				);

			case 'roles':
				if ( $user_id <= 0 ) {
					return array(
						'passed'      => false,
						'reason_code' => ReasonCode::GUEST,
					);
				}
				$user = get_userdata( $user_id );
				if ( ! $user ) {
					return array(
						'passed'      => false,
						'reason_code' => ReasonCode::USER_MISMATCH,
					);
				}
				$target_roles = (array) ( $cond['roles'] ?? array() );
				$matches      = array_intersect( (array) $user->roles, $target_roles );
				if ( ! empty( $matches ) ) {
					return array(
						'passed'      => true,
						'reason_code' => ReasonCode::ALLOWED,
						'grant'       => AccessResult::allow( $rule->get_id(), 'role_' . reset( $matches ) ),
					);
				}
				return array(
					'passed'      => false,
					'reason_code' => ReasonCode::ROLE_MISMATCH,
				);

			case 'users':
				if ( $user_id <= 0 ) {
					return array(
						'passed'      => false,
						'reason_code' => ReasonCode::GUEST,
					);
				}
				$allowed_users = array_map( 'intval', (array) ( $cond['users'] ?? array() ) );
				if ( in_array( $user_id, $allowed_users, true ) ) {
					return array(
						'passed'      => true,
						'reason_code' => ReasonCode::ALLOWED,
						'grant'       => AccessResult::allow( $rule->get_id(), 'user_' . $user_id ),
					);
				}
				return array(
					'passed'      => false,
					'reason_code' => ReasonCode::USER_MISMATCH,
				);

			case 'wc_product':
				$target_products = array_map( 'intval', (array) ( $cond['products'] ?? array() ) );
				$res = $this->purchase_evaluator->evaluate_customer_purchase( $user_id, $rule, $target_products, array() );
				if ( $res['qualifies'] ) {
					return array(
						'passed'      => true,
						'reason_code' => ReasonCode::ALLOWED,
						'grant'       => AccessResult::allow(
							$rule->get_id(),
							'order_' . $res['order_id'],
							$res['expiry']
						),
					);
				}
				return array(
					'passed'      => false,
					'reason_code' => $res['reason_code'],
				);

			case 'wc_category':
				$target_categories = array_map( 'intval', (array) ( $cond['categories'] ?? array() ) );
				$res = $this->purchase_evaluator->evaluate_customer_purchase( $user_id, $rule, array(), $target_categories );
				if ( $res['qualifies'] ) {
					return array(
						'passed'      => true,
						'reason_code' => ReasonCode::ALLOWED,
						'grant'       => AccessResult::allow(
							$rule->get_id(),
							'order_' . $res['order_id'],
							$res['expiry']
						),
					);
				}
				return array(
					'passed'      => false,
					'reason_code' => $res['reason_code'],
				);

			case 'wc_subscription':
				$filter_res = apply_filters( 'wpcalibrate_access_evaluate_subscription_condition', null, $user_id, $cond, $rule );
				if ( is_array( $filter_res ) && isset( $filter_res['passed'] ) ) {
					return $filter_res;
				}
				return array(
					'passed'      => false,
					'reason_code' => ReasonCode::SUBSCRIPTION_INACTIVE,
				);

			case 'fixed_date':
				$start = ! empty( $cond['start'] ) ? (string) $cond['start'] : '';
				$end   = ! empty( $cond['end'] ) ? (string) $cond['end'] : '';
				$date_res = $this->validate_date_window( $start, $end );
				if ( null === $date_res ) {
					return array(
						'passed'      => true,
						'reason_code' => ReasonCode::ALLOWED,
						'grant'       => AccessResult::allow( $rule->get_id(), 'date_window' ),
					);
				}
				return array(
					'passed'      => false,
					'reason_code' => $date_res,
				);

			default:
				// Extensibility hook for custom conditions.
				$custom = apply_filters( 'wpcalibrate_access_evaluate_custom_condition', null, $user_id, $cond, $rule );
				if ( is_array( $custom ) && isset( $custom['passed'] ) ) {
					return $custom;
				}
				return array(
					'passed'      => false,
					'reason_code' => ReasonCode::NO_QUALIFYING_PURCHASE,
				);
		}
	}

	/**
	 * Check fixed start/end dates on the rule level.
	 *
	 * @param AccessRule $rule
	 * @return string|null Reason code if date restriction fails, or null if allowed.
	 */
	private function check_fixed_dates( AccessRule $rule ): ?string {
		if ( AccessRule::DURATION_FIXED !== $rule->get_duration_type() ) {
			return null;
		}

		return $this->validate_date_window( (string) $rule->get_fixed_start(), (string) $rule->get_fixed_end() );
	}

	/**
	 * Validate a date window against current site time.
	 *
	 * @param string $start Start date string.
	 * @param string $end   End date string.
	 * @return string|null ReasonCode or null if valid.
	 */
	private function validate_date_window( string $start, string $end ): ?string {
		$tz           = wp_timezone();
		$now          = new \DateTimeImmutable( 'now', $tz );
		$current_time = $now->getTimestamp();

		if ( ! empty( $start ) ) {
			try {
				$start_dt = new \DateTimeImmutable( $start, $tz );
				if ( $current_time < $start_dt->getTimestamp() ) {
					return ReasonCode::BEFORE_START;
				}
			} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
				// Invalid date string fails closed.
			}
		}

		if ( ! empty( $end ) ) {
			try {
				$end_dt = new \DateTimeImmutable( $end, $tz );
				if ( $current_time > $end_dt->getTimestamp() ) {
					return ReasonCode::EXPIRED;
				}
			} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
				// Invalid date string fails closed.
			}
		}

		return null;
	}

	/**
	 * Build a structured AccessResult for denied access.
	 *
	 * @param AccessRule $rule        The rule controlling the denial.
	 * @param string     $reason_code Underlying failure reason.
	 * @return AccessResult
	 */
	public function build_denial_result( AccessRule $rule, string $reason_code ): AccessResult {
		$action         = $rule->get_denied_action();
		$message        = $rule->get_denied_message();
		$redirect_url   = null;
		$qual_prod_id   = null;
		$btn_text       = $rule->get_purchase_button_text();

		// Default fallback message if not configured.
		if ( empty( $message ) ) {
			$message = get_option(
				'wpcal_default_restriction_message',
				'<div class="wpcal-restriction-box"><p>' . __( 'This content is restricted. Please purchase access or log in to view.', 'wpcalibrate-commerce-access-rules' ) . '</p></div>'
			);
		}

		// Single qualifying product resolution for purchase button or product redirect.
		$target_pids = $rule->get_all_target_product_ids();
		if ( count( $target_pids ) === 1 ) {
			$qual_prod_id = $target_pids[0];
		} elseif ( $rule->get_redirect_product_id() ) {
			$qual_prod_id = $rule->get_redirect_product_id();
		}

		switch ( $action ) {
			case AccessRule::ACTION_LOGIN_REDIRECT:
				// Build safe login redirect preserving current page.
				$current_url  = home_url( add_query_arg( array(), null ) );
				$redirect_url = wp_login_url( $current_url );
				break;

			case AccessRule::ACTION_PAGE_REDIRECT:
				if ( $rule->get_redirect_page_id() ) {
					$redirect_url = get_permalink( $rule->get_redirect_page_id() ) ?: home_url( '/' );
				} elseif ( $rule->get_redirect_external_url() ) {
					$redirect_url = $rule->get_redirect_external_url();
				}
				break;

			case AccessRule::ACTION_PRODUCT_REDIRECT:
				if ( $qual_prod_id ) {
					$redirect_url = ProductResolver::get_product_permalink( $qual_prod_id );
				}
				break;

			case AccessRule::ACTION_PURCHASE_BUTTON:
				// Custom purchase button displayed alongside or within message.
				break;

			case AccessRule::ACTION_HIDE:
				// Completely hide content.
				$message = '';
				break;

			case AccessRule::ACTION_MESSAGE:
			default:
				// Show custom message.
				break;
		}

		return AccessResult::deny(
			$reason_code,
			$rule->get_id(),
			$action,
			$redirect_url,
			$message,
			$qual_prod_id,
			$btn_text
		);
	}
}
