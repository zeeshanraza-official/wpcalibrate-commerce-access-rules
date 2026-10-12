<?php
/**
 * Optional WooCommerce Subscriptions Integration.
 *
 * @package WPCalibrate\CommerceAccessRules
 */

declare(strict_types=1);

namespace WPCalibrate\CommerceAccessRules\Integrations\Subscriptions;

use WPCalibrate\CommerceAccessRules\Domain\Access\AccessResult;
use WPCalibrate\CommerceAccessRules\Domain\Access\ReasonCode;
use WPCalibrate\CommerceAccessRules\Domain\Rule\AccessRule;
use WPCalibrate\CommerceAccessRules\Infrastructure\Cache\AccessCache;

/**
 * SubscriptionsIntegration class.
 */
final class SubscriptionsIntegration {

	private AccessCache $cache;

	/**
	 * Constructor.
	 *
	 * @param AccessCache $cache
	 */
	public function __construct( AccessCache $cache ) {
		$this->cache = $cache;
	}

	/**
	 * Register integration if WooCommerce Subscriptions is active.
	 *
	 * @return void
	 */
	public function register(): void {
		if ( ! function_exists( 'wcs_user_has_subscription' ) && ! class_exists( '\WC_Subscriptions' ) ) {
			return;
		}

		add_filter(
			'wpcalibrate_access_evaluate_subscription_condition',
			array( $this, 'evaluate_subscription_condition' ),
			10,
			4
		);

		add_action(
			'woocommerce_subscription_status_updated',
			array( $this, 'on_subscription_status_updated' ),
			10,
			3
		);
	}

	/**
	 * Evaluate subscription condition.
	 *
	 * @param mixed                $current_result Current filter result.
	 * @param int                  $user_id        User ID.
	 * @param array<string, mixed> $condition      Condition parameters.
	 * @param AccessRule           $rule           The rule.
	 * @return array{passed: bool, reason_code: string, grant?: AccessResult}
	 */
	public function evaluate_subscription_condition( $current_result, int $user_id, array $condition, AccessRule $rule ): array {
		if ( $user_id <= 0 ) {
			return array(
				'passed'      => false,
				'reason_code' => ReasonCode::GUEST,
			);
		}

		if ( ! function_exists( 'wcs_user_has_subscription' ) ) {
			return array(
				'passed'      => false,
				'reason_code' => ReasonCode::SUBSCRIPTION_INACTIVE,
			);
		}

		$target_products = array_map( 'intval', (array) ( $condition['products'] ?? array() ) );
		if ( empty( $target_products ) ) {
			return array(
				'passed'      => false,
				'reason_code' => ReasonCode::SUBSCRIPTION_INACTIVE,
			);
		}

		$allowed_statuses = array( 'active', 'pending-cancel' );

		foreach ( $target_products as $product_id ) {
			if ( wcs_user_has_subscription( $user_id, $product_id, $allowed_statuses ) ) {
				return array(
					'passed'      => true,
					'reason_code' => ReasonCode::ALLOWED,
					'grant'       => AccessResult::allow(
						$rule->get_id(),
						'subscription_product_' . $product_id,
						null
					),
				);
			}
		}

		return array(
			'passed'      => false,
			'reason_code' => ReasonCode::SUBSCRIPTION_INACTIVE,
		);
	}

	/**
	 * Invalidate customer cache when subscription status updates.
	 *
	 * @param \WC_Subscription $subscription Subscription object.
	 * @param string           $new_status   New status.
	 * @param string           $old_status   Previous status.
	 * @return void
	 */
	public function on_subscription_status_updated( $subscription, string $new_status, string $old_status ): void {
		if ( ! is_object( $subscription ) || ! method_exists( $subscription, 'get_customer_id' ) ) {
			return;
		}

		$customer_id = (int) $subscription->get_customer_id();
		if ( $customer_id > 0 ) {
			$this->cache->invalidate_user( $customer_id );
		}
	}
}
