<?php
/**
 * Restriction Rule Domain Entity.
 *
 * @package WPCalibrate\CommerceAccessRules
 */

declare(strict_types=1);

namespace WPCalibrate\CommerceAccessRules\Domain\Rule;

/**
 * AccessRule class.
 */
final class AccessRule {

	public const STATUS_ACTIVE   = 'active';
	public const STATUS_INACTIVE = 'inactive';

	public const ACTION_MESSAGE          = 'message';
	public const ACTION_LOGIN_REDIRECT   = 'login_redirect';
	public const ACTION_PAGE_REDIRECT    = 'page_redirect';
	public const ACTION_PRODUCT_REDIRECT = 'product_redirect';
	public const ACTION_PURCHASE_BUTTON  = 'purchase_button';
	public const ACTION_HIDE             = 'hide';

	public const DURATION_PERMANENT = 'permanent';
	public const DURATION_DAYS      = 'days';
	public const DURATION_WEEKS     = 'weeks';
	public const DURATION_MONTHS    = 'months';
	public const DURATION_FIXED     = 'fixed';

	private ?int $id = null;
	private string $title = '';
	private string $status = self::STATUS_ACTIVE;
	private int $priority = 10;

	/**
	 * Condition groups.
	 * Format:
	 * [
	 *   [
	 *     'relation' => 'AND'|'OR',
	 *     'conditions' => [
	 *       ['type' => 'logged_in', 'value' => true],
	 *       ['type' => 'roles', 'roles' => ['subscriber']],
	 *       ['type' => 'users', 'users' => [1, 2]],
	 *       ['type' => 'wc_product', 'products' => [123, 456]],
	 *       ['type' => 'wc_category', 'categories' => [10]],
	 *       ['type' => 'wc_subscription', 'products' => [789]],
	 *       ['type' => 'fixed_date', 'start' => '2026-10-01 00:00:00', 'end' => '2026-10-31 23:59:59'],
	 *     ]
	 *   ]
	 * ]
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $condition_groups = array();

	/**
	 * Relation between condition groups: 'AND' or 'OR'.
	 */
	private string $groups_relation = 'AND';

	/**
	 * Qualifying order statuses (e.g. ['wc-completed', 'wc-processing']). Empty = use global default.
	 *
	 * @var string[]
	 */
	private array $qualifying_order_statuses = array();

	private string $duration_type = self::DURATION_PERMANENT;
	private int $duration_value = 0;
	private ?string $fixed_start = null;
	private ?string $fixed_end = null;

	private string $denied_action = self::ACTION_MESSAGE;
	private string $denied_message = '';
	private ?int $redirect_page_id = null;
	private ?int $redirect_product_id = null;
	private ?string $redirect_external_url = null;
	private string $purchase_button_text = '';

	private ?string $date_created = null;
	private ?string $date_modified = null;

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $data Initial data.
	 */
	public function __construct( array $data = array() ) {
		if ( ! empty( $data ) ) {
			$this->populate( $data );
		}
	}

	/**
	 * Populate rule from array.
	 *
	 * @param array<string, mixed> $data
	 */
	public function populate( array $data ): void {
		if ( isset( $data['id'] ) ) {
			$this->id = (int) $data['id'];
		}
		if ( isset( $data['title'] ) ) {
			$this->title = sanitize_text_field( (string) $data['title'] );
		}
		if ( isset( $data['status'] ) ) {
			$this->status = in_array( $data['status'], array( self::STATUS_ACTIVE, self::STATUS_INACTIVE ), true )
				? (string) $data['status']
				: self::STATUS_ACTIVE;
		}
		if ( isset( $data['priority'] ) ) {
			$this->priority = (int) $data['priority'];
		}
		if ( isset( $data['condition_groups'] ) && is_array( $data['condition_groups'] ) ) {
			$this->condition_groups = $this->sanitize_condition_groups( $data['condition_groups'] );
		}
		if ( isset( $data['groups_relation'] ) ) {
			$this->groups_relation = strtoupper( (string) $data['groups_relation'] ) === 'OR' ? 'OR' : 'AND';
		}
		if ( isset( $data['qualifying_order_statuses'] ) && is_array( $data['qualifying_order_statuses'] ) ) {
			$this->qualifying_order_statuses = array_map( 'sanitize_key', $data['qualifying_order_statuses'] );
		}
		if ( isset( $data['duration_type'] ) ) {
			$valid_durations = array( self::DURATION_PERMANENT, self::DURATION_DAYS, self::DURATION_WEEKS, self::DURATION_MONTHS, self::DURATION_FIXED );
			$this->duration_type = in_array( $data['duration_type'], $valid_durations, true )
				? (string) $data['duration_type']
				: self::DURATION_PERMANENT;
		}
		if ( isset( $data['duration_value'] ) ) {
			$this->duration_value = max( 0, (int) $data['duration_value'] );
		}
		if ( isset( $data['fixed_start'] ) && ! empty( $data['fixed_start'] ) ) {
			$this->fixed_start = sanitize_text_field( (string) $data['fixed_start'] );
		}
		if ( isset( $data['fixed_end'] ) && ! empty( $data['fixed_end'] ) ) {
			$this->fixed_end = sanitize_text_field( (string) $data['fixed_end'] );
		}
		if ( isset( $data['denied_action'] ) ) {
			$valid_actions = array(
				self::ACTION_MESSAGE,
				self::ACTION_LOGIN_REDIRECT,
				self::ACTION_PAGE_REDIRECT,
				self::ACTION_PRODUCT_REDIRECT,
				self::ACTION_PURCHASE_BUTTON,
				self::ACTION_HIDE,
			);
			$this->denied_action = in_array( $data['denied_action'], $valid_actions, true )
				? (string) $data['denied_action']
				: self::ACTION_MESSAGE;
		}
		if ( isset( $data['denied_message'] ) ) {
			$this->denied_message = wp_kses_post( (string) $data['denied_message'] );
		}
		if ( isset( $data['redirect_page_id'] ) ) {
			$this->redirect_page_id = (int) $data['redirect_page_id'] ?: null;
		}
		if ( isset( $data['redirect_product_id'] ) ) {
			$this->redirect_product_id = (int) $data['redirect_product_id'] ?: null;
		}
		if ( isset( $data['redirect_external_url'] ) && ! empty( $data['redirect_external_url'] ) ) {
			$url = esc_url_raw( (string) $data['redirect_external_url'] );
			$this->redirect_external_url = ! empty( $url ) ? $url : null;
		}
		if ( isset( $data['purchase_button_text'] ) ) {
			$this->purchase_button_text = sanitize_text_field( (string) $data['purchase_button_text'] );
		}
		if ( isset( $data['date_created'] ) ) {
			$this->date_created = (string) $data['date_created'];
		}
		if ( isset( $data['date_modified'] ) ) {
			$this->date_modified = (string) $data['date_modified'];
		}
	}

	/**
	 * Sanitize condition groups structure.
	 *
	 * @param array<int, mixed> $groups
	 * @return array<int, array<string, mixed>>
	 */
	private function sanitize_condition_groups( array $groups ): array {
		$sanitized = array();
		foreach ( $groups as $group ) {
			if ( ! is_array( $group ) ) {
				continue;
			}
			$relation = isset( $group['relation'] ) && strtoupper( (string) $group['relation'] ) === 'OR' ? 'OR' : 'AND';
			$conditions = array();

			if ( isset( $group['conditions'] ) && is_array( $group['conditions'] ) ) {
				foreach ( $group['conditions'] as $cond ) {
					if ( ! is_array( $cond ) || empty( $cond['type'] ) ) {
						continue;
					}
					$type = sanitize_key( (string) $cond['type'] );
					$clean_cond = array( 'type' => $type );

					switch ( $type ) {
						case 'logged_in':
						case 'logged_out':
							$clean_cond['value'] = true;
							break;

						case 'roles':
							$roles = isset( $cond['roles'] ) && is_array( $cond['roles'] ) ? $cond['roles'] : array();
							$clean_cond['roles'] = array_values( array_map( 'sanitize_key', $roles ) );
							break;

						case 'users':
							$users = isset( $cond['users'] ) && is_array( $cond['users'] ) ? $cond['users'] : array();
							$clean_cond['users'] = array_values( array_map( 'absint', $users ) );
							break;

						case 'wc_product':
							$products = isset( $cond['products'] ) && is_array( $cond['products'] ) ? $cond['products'] : array();
							$clean_cond['products'] = array_values( array_map( 'absint', $products ) );
							break;

						case 'wc_category':
							$cats = isset( $cond['categories'] ) && is_array( $cond['categories'] ) ? $cond['categories'] : array();
							$clean_cond['categories'] = array_values( array_map( 'absint', $cats ) );
							break;

						case 'wc_subscription':
							$subs = isset( $cond['products'] ) && is_array( $cond['products'] ) ? $cond['products'] : array();
							$clean_cond['products'] = array_values( array_map( 'absint', $subs ) );
							break;

						case 'fixed_date':
							$clean_cond['start'] = ! empty( $cond['start'] ) ? sanitize_text_field( (string) $cond['start'] ) : '';
							$clean_cond['end']   = ! empty( $cond['end'] ) ? sanitize_text_field( (string) $cond['end'] ) : '';
							break;

						default:
							// Pass-through for extension filters, properly sanitized.
							foreach ( $cond as $k => $v ) {
								if ( $k !== 'type' ) {
									$clean_cond[ sanitize_key( (string) $k ) ] = is_scalar( $v ) ? sanitize_text_field( (string) $v ) : $v;
								}
							}
							break;
					}

					$conditions[] = $clean_cond;
				}
			}

			if ( ! empty( $conditions ) ) {
				$sanitized[] = array(
					'relation'   => $relation,
					'conditions' => $conditions,
				);
			}
		}

		return $sanitized;
	}

	// Getters and Setters.

	public function get_id(): ?int {
		return $this->id;
	}

	public function set_id( ?int $id ): void {
		$this->id = $id;
	}

	public function get_title(): string {
		return $this->title;
	}

	public function set_title( string $title ): void {
		$this->title = sanitize_text_field( $title );
	}

	public function get_status(): string {
		return $this->status;
	}

	public function set_status( string $status ): void {
		$this->status = in_array( $status, array( self::STATUS_ACTIVE, self::STATUS_INACTIVE ), true )
			? $status
			: self::STATUS_ACTIVE;
	}

	public function is_active(): bool {
		return $this->status === self::STATUS_ACTIVE;
	}

	public function get_priority(): int {
		return $this->priority;
	}

	public function set_priority( int $priority ): void {
		$this->priority = $priority;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function get_condition_groups(): array {
		return $this->condition_groups;
	}

	/**
	 * @param array<int, array<string, mixed>> $groups
	 */
	public function set_condition_groups( array $groups ): void {
		$this->condition_groups = $this->sanitize_condition_groups( $groups );
	}

	public function get_groups_relation(): string {
		return $this->groups_relation;
	}

	public function set_groups_relation( string $relation ): void {
		$this->groups_relation = strtoupper( $relation ) === 'OR' ? 'OR' : 'AND';
	}

	/**
	 * @return string[]
	 */
	public function get_qualifying_order_statuses(): array {
		return $this->qualifying_order_statuses;
	}

	/**
	 * @param string[] $statuses
	 */
	public function set_qualifying_order_statuses( array $statuses ): void {
		$this->qualifying_order_statuses = array_values( array_map( 'sanitize_key', $statuses ) );
	}

	public function get_duration_type(): string {
		return $this->duration_type;
	}

	public function set_duration_type( string $type ): void {
		$this->duration_type = $type;
	}

	public function get_duration_value(): int {
		return $this->duration_value;
	}

	public function set_duration_value( int $value ): void {
		$this->duration_value = max( 0, $value );
	}

	public function get_fixed_start(): ?string {
		return $this->fixed_start;
	}

	public function set_fixed_start( ?string $start ): void {
		$this->fixed_start = $start;
	}

	public function get_fixed_end(): ?string {
		return $this->fixed_end;
	}

	public function set_fixed_end( ?string $end ): void {
		$this->fixed_end = $end;
	}

	public function get_denied_action(): string {
		return $this->denied_action;
	}

	public function set_denied_action( string $action ): void {
		$this->denied_action = $action;
	}

	public function get_denied_message(): string {
		return $this->denied_message;
	}

	public function set_denied_message( string $message ): void {
		$this->denied_message = wp_kses_post( $message );
	}

	public function get_redirect_page_id(): ?int {
		return $this->redirect_page_id;
	}

	public function set_redirect_page_id( ?int $id ): void {
		$this->redirect_page_id = $id;
	}

	public function get_redirect_product_id(): ?int {
		return $this->redirect_product_id;
	}

	public function set_redirect_product_id( ?int $id ): void {
		$this->redirect_product_id = $id;
	}

	public function get_redirect_external_url(): ?string {
		return $this->redirect_external_url;
	}

	public function set_redirect_external_url( ?string $url ): void {
		$this->redirect_external_url = $url;
	}

	public function get_purchase_button_text(): string {
		return ! empty( $this->purchase_button_text )
			? $this->purchase_button_text
			: __( 'Buy Now to Unlock Access', 'wpcalibrate-commerce-access-rules' );
	}

	public function set_purchase_button_text( string $text ): void {
		$this->purchase_button_text = sanitize_text_field( $text );
	}

	public function get_date_created(): ?string {
		return $this->date_created;
	}

	public function get_date_modified(): ?string {
		return $this->date_modified;
	}

	/**
	 * Extract all qualifying product IDs across all conditions in this rule.
	 *
	 * @return int[]
	 */
	public function get_all_target_product_ids(): array {
		$product_ids = array();
		foreach ( $this->condition_groups as $group ) {
			if ( empty( $group['conditions'] ) || ! is_array( $group['conditions'] ) ) {
				continue;
			}
			foreach ( $group['conditions'] as $cond ) {
				if ( ( $cond['type'] === 'wc_product' || $cond['type'] === 'wc_subscription' ) && ! empty( $cond['products'] ) ) {
					foreach ( $cond['products'] as $pid ) {
						$product_ids[] = (int) $pid;
					}
				}
			}
		}
		return array_values( array_unique( $product_ids ) );
	}

	/**
	 * Extract all qualifying category IDs across all conditions in this rule.
	 *
	 * @return int[]
	 */
	public function get_all_target_category_ids(): array {
		$cat_ids = array();
		foreach ( $this->condition_groups as $group ) {
			if ( empty( $group['conditions'] ) || ! is_array( $group['conditions'] ) ) {
				continue;
			}
			foreach ( $group['conditions'] as $cond ) {
				if ( $cond['type'] === 'wc_category' && ! empty( $cond['categories'] ) ) {
					foreach ( $cond['categories'] as $cid ) {
						$cat_ids[] = (int) $cid;
					}
				}
			}
		}
		return array_values( array_unique( $cat_ids ) );
	}

	/**
	 * Summary of main access conditions for display in admin table.
	 *
	 * @return string
	 */
	public function get_access_summary(): string {
		$summaries = array();
		foreach ( $this->condition_groups as $group ) {
			if ( empty( $group['conditions'] ) ) {
				continue;
			}
			foreach ( $group['conditions'] as $c ) {
				switch ( $c['type'] ) {
					case 'logged_in':
						$summaries[] = __( 'Logged-in Users', 'wpcalibrate-commerce-access-rules' );
						break;
					case 'logged_out':
						$summaries[] = __( 'Logged-out Users', 'wpcalibrate-commerce-access-rules' );
						break;
					case 'roles':
						$summaries[] = sprintf(
							/* translators: %s: list of roles */
							__( 'Roles: %s', 'wpcalibrate-commerce-access-rules' ),
							implode( ', ', (array) ( $c['roles'] ?? array() ) )
						);
						break;
					case 'users':
						$summaries[] = sprintf(
							/* translators: %d: count of users */
							__( '%d Specific User(s)', 'wpcalibrate-commerce-access-rules' ),
							count( (array) ( $c['users'] ?? array() ) )
						);
						break;
					case 'wc_product':
						$summaries[] = sprintf(
							/* translators: %d: count of products */
							__( 'Purchased %d Product(s)', 'wpcalibrate-commerce-access-rules' ),
							count( (array) ( $c['products'] ?? array() ) )
						);
						break;
					case 'wc_category':
						$summaries[] = sprintf(
							/* translators: %d: count of categories */
							__( 'Purchased in %d Category(ies)', 'wpcalibrate-commerce-access-rules' ),
							count( (array) ( $c['categories'] ?? array() ) )
						);
						break;
					case 'wc_subscription':
						$summaries[] = sprintf(
							/* translators: %d: count of subscriptions */
							__( 'Active Subscription (%d products)', 'wpcalibrate-commerce-access-rules' ),
							count( (array) ( $c['products'] ?? array() ) )
						);
						break;
					case 'fixed_date':
						$summaries[] = __( 'Fixed Date Window', 'wpcalibrate-commerce-access-rules' );
						break;
				}
			}
		}

		if ( empty( $summaries ) ) {
			return __( 'No conditions configured', 'wpcalibrate-commerce-access-rules' );
		}

		return implode( ' | ', array_slice( array_unique( $summaries ), 0, 3 ) );
	}

	/**
	 * Convert rule to storage / array format.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'id'                        => $this->id,
			'title'                     => $this->title,
			'status'                    => $this->status,
			'priority'                  => $this->priority,
			'condition_groups'          => $this->condition_groups,
			'groups_relation'           => $this->groups_relation,
			'qualifying_order_statuses' => $this->qualifying_order_statuses,
			'duration_type'             => $this->duration_type,
			'duration_value'            => $this->duration_value,
			'fixed_start'               => $this->fixed_start,
			'fixed_end'                 => $this->fixed_end,
			'denied_action'             => $this->denied_action,
			'denied_message'            => $this->denied_message,
			'redirect_page_id'          => $this->redirect_page_id,
			'redirect_product_id'       => $this->redirect_product_id,
			'redirect_external_url'     => $this->redirect_external_url,
			'purchase_button_text'      => $this->purchase_button_text,
			'date_created'              => $this->date_created,
			'date_modified'             => $this->date_modified,
		);
	}
}
