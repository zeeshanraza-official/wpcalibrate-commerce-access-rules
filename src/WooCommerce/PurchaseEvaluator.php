<?php
/**
 * WooCommerce Purchase Evaluator.
 *
 * @package WPCalibrate\CommerceAccessRules
 */

declare(strict_types=1);

namespace WPCalibrate\CommerceAccessRules\WooCommerce;

use WC_Order;
use WC_Order_Item_Product;
use WPCalibrate\CommerceAccessRules\Domain\Rule\AccessRule;

/**
 * PurchaseEvaluator class.
 */
final class PurchaseEvaluator {

	/**
	 * Default qualifying order statuses.
	 *
	 * @var string[]
	 */
	public const DEFAULT_QUALIFYING_STATUSES = array( 'wc-completed', 'wc-processing' );

	/**
	 * Check if a customer has a qualifying purchase for the given rule.
	 *
	 * Returns an array with:
	 * [
	 *   'qualifies' => bool,
	 *   'order_id' => int|null,
	 *   'purchase_date' => int|null, // Unix timestamp
	 *   'expiry' => int|null,        // Unix timestamp or null
	 *   'reason_code' => string,
	 * ]
	 *
	 * @param int        $user_id User ID (or 0 for guest).
	 * @param AccessRule $rule    The restriction rule.
	 * @param int[]      $target_product_ids List of product IDs to check.
	 * @param int[]      $target_category_ids List of category IDs to check.
	 * @return array<string, mixed>
	 */
	public function evaluate_customer_purchase(
		int $user_id,
		AccessRule $rule,
		array $target_product_ids = array(),
		array $target_category_ids = array()
	): array {
		if ( ! function_exists( 'wc_get_orders' ) ) {
			return array(
				'qualifies'     => false,
				'order_id'      => null,
				'purchase_date' => null,
				'expiry'        => null,
				'reason_code'   => 'no_qualifying_purchase',
			);
		}

		if ( $user_id <= 0 ) {
			return array(
				'qualifies'     => false,
				'order_id'      => null,
				'purchase_date' => null,
				'expiry'        => null,
				'reason_code'   => 'guest',
			);
		}

		// If no products or categories are targeted, purchase evaluation isn't required by rule conditions.
		if ( empty( $target_product_ids ) && empty( $target_category_ids ) ) {
			return array(
				'qualifies'     => false,
				'order_id'      => null,
				'purchase_date' => null,
				'expiry'        => null,
				'reason_code'   => 'no_qualifying_purchase',
			);
		}

		// Determine allowed statuses.
		$qualifying_statuses = $rule->get_qualifying_order_statuses();
		if ( empty( $qualifying_statuses ) ) {
			$global_statuses = get_option( 'wpcal_qualifying_order_statuses', self::DEFAULT_QUALIFYING_STATUSES );
			$qualifying_statuses = is_array( $global_statuses ) && ! empty( $global_statuses )
				? $global_statuses
				: self::DEFAULT_QUALIFYING_STATUSES;
		}

		// Normalize statuses for wc_get_orders (strip 'wc-' prefix if present, as wc_get_orders accepts both or bare).
		$clean_statuses = array_map(
			static function ( string $s ): string {
				return str_starts_with( $s, 'wc-' ) ? substr( $s, 3 ) : $s;
			},
			$qualifying_statuses
		);

		// Query customer's orders using WooCommerce CRUD API (HPOS-compatible).
		$customer_orders = wc_get_orders(
			array(
				'customer_id' => $user_id,
				'status'      => $clean_statuses,
				'limit'       => 50,
				'orderby'     => 'date',
				'order'       => 'DESC',
				'return'      => 'objects',
			)
		);

		if ( empty( $customer_orders ) ) {
			return array(
				'qualifies'     => false,
				'order_id'      => null,
				'purchase_date' => null,
				'expiry'        => null,
				'reason_code'   => 'no_qualifying_purchase',
			);
		}

		$current_time = time();

		// Iterate through orders to find a qualifying purchase.
		// If multiple orders qualify, we pick the one that gives the latest valid expiry or permanent access.
		$best_grant = null;

		foreach ( $customer_orders as $order ) {
			if ( ! ( $order instanceof WC_Order ) ) {
				continue;
			}

			// Validate order status is indeed qualifying.
			$order_status = $order->get_status();
			if ( ! in_array( $order_status, $clean_statuses, true ) ) {
				continue;
			}

			// Check line items.
			$order_qualifies = false;
			$items = $order->get_items( 'line_item' );

			foreach ( $items as $item_id => $item ) {
				if ( ! ( $item instanceof WC_Order_Item_Product ) ) {
					continue;
				}

				$product_id   = $item->get_product_id();
				$variation_id = $item->get_variation_id();

				// Item matches target products?
				$matches_product = ! empty( $target_product_ids ) && (
					in_array( $product_id, $target_product_ids, true ) ||
					( $variation_id && in_array( $variation_id, $target_product_ids, true ) )
				);

				// Item matches target categories?
				$matches_category = false;
				if ( ! empty( $target_category_ids ) && ! $matches_product ) {
					$product_cat_ids = wc_get_product_term_ids( $product_id, 'product_cat' );
					if ( ! empty( array_intersect( $product_cat_ids, $target_category_ids ) ) ) {
						$matches_category = true;
					}
				}

				if ( ! $matches_product && ! $matches_category ) {
					continue;
				}

				// Check partial refunds for this specific line item:
				// If total qty ordered minus qty refunded is > 0, the item remains qualifying.
				$ordered_qty  = $item->get_quantity();
				$refunded_qty = abs( $order->get_qty_refunded_for_item( $item_id ) );
				$net_qty      = $ordered_qty - $refunded_qty;

				if ( $net_qty > 0 ) {
					$order_qualifies = true;
					break; // Found a qualifying item in this order.
				}
			}

			if ( ! $order_qualifies ) {
				continue;
			}

			// Order has valid qualifying item! Calculate access duration.
			$purchase_date_obj = $order->get_date_paid() ?? $order->get_date_completed() ?? $order->get_date_created();
			$purchase_timestamp = $purchase_date_obj ? $purchase_date_obj->getTimestamp() : $current_time;

			$expiry = $this->calculate_expiry( $purchase_timestamp, $rule );

			// Check if already expired at request time.
			if ( null !== $expiry && $expiry < $current_time ) {
				// This order's access has expired. We continue checking older/newer orders if available.
				continue;
			}

			// Permanent access is the best grant possible.
			if ( null === $expiry ) {
				return array(
					'qualifies'     => true,
					'order_id'      => $order->get_id(),
					'purchase_date' => $purchase_timestamp,
					'expiry'        => null,
					'reason_code'   => 'allowed',
				);
			}

			// If we have an expiring grant, keep the one with the farthest future expiry.
			if ( null === $best_grant || ( $expiry > $best_grant['expiry'] ) ) {
				$best_grant = array(
					'qualifies'     => true,
					'order_id'      => $order->get_id(),
					'purchase_date' => $purchase_timestamp,
					'expiry'        => $expiry,
					'reason_code'   => 'allowed',
				);
			}
		}

		if ( null !== $best_grant ) {
			return $best_grant;
		}

		return array(
			'qualifies'     => false,
			'order_id'      => null,
			'purchase_date' => null,
			'expiry'        => null,
			'reason_code'   => 'expired',
		);
	}

	/**
	 * Calculate access expiry timestamp based on purchase timestamp and rule duration settings.
	 *
	 * @param int        $purchase_timestamp Purchase Unix timestamp.
	 * @param AccessRule $rule               Restriction rule.
	 * @return int|null Unix timestamp when access expires, or null if permanent.
	 */
	public function calculate_expiry( int $purchase_timestamp, AccessRule $rule ): ?int {
		$type  = $rule->get_duration_type();
		$value = $rule->get_duration_value();

		if ( AccessRule::DURATION_PERMANENT === $type || $value <= 0 ) {
			return null;
		}

		$dt = new \DateTimeImmutable( '@' . $purchase_timestamp );
		$dt = $dt->setTimezone( wp_timezone() );

		switch ( $type ) {
			case AccessRule::DURATION_DAYS:
				$dt = $dt->modify( "+{$value} days" );
				break;

			case AccessRule::DURATION_WEEKS:
				$days = $value * 7;
				$dt   = $dt->modify( "+{$days} days" );
				break;

			case AccessRule::DURATION_MONTHS:
				// Calendar-month calculation using PHP DateTimeImmutable modify.
				$dt = $dt->modify( "+{$value} months" );
				break;

			default:
				return null;
		}

		return $dt->getTimestamp();
	}
}
