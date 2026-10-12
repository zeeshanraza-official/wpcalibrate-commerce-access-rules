<?php
/**
 * WooCommerce Order Hooks and Cache Invalidation.
 *
 * @package WPCalibrate\CommerceAccessRules
 */

declare(strict_types=1);

namespace WPCalibrate\CommerceAccessRules\WooCommerce;

use WC_Order;
use WPCalibrate\CommerceAccessRules\Infrastructure\Cache\AccessCache;

/**
 * OrderHooks class.
 */
final class OrderHooks {

	/**
	 * Cache service instance.
	 */
	private AccessCache $cache;

	/**
	 * Track processed orders in current PHP request to ensure idempotency.
	 *
	 * @var array<string, bool>
	 */
	private static array $processed_events = array();

	/**
	 * Constructor.
	 *
	 * @param AccessCache $cache Access cache instance.
	 */
	public function __construct( AccessCache $cache ) {
		$this->cache = $cache;
	}

	/**
	 * Register WooCommerce order hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'woocommerce_order_status_changed', array( $this, 'on_order_status_changed' ), 10, 4 );
		add_action( 'woocommerce_order_partially_refunded', array( $this, 'on_order_refunded' ), 10, 2 );
		add_action( 'woocommerce_order_fully_refunded', array( $this, 'on_order_refunded' ), 10, 2 );
	}

	/**
	 * Handle order status changes.
	 *
	 * @param int      $order_id   Order ID.
	 * @param string   $old_status Previous status.
	 * @param string   $new_status New status.
	 * @param WC_Order $order      Order object.
	 * @return void
	 */
	public function on_order_status_changed( int $order_id, string $old_status, string $new_status, $order ): void {
		$event_key = "status_{$order_id}_{$old_status}_{$new_status}";
		if ( isset( self::$processed_events[ $event_key ] ) ) {
			return; // Idempotent guard.
		}
		self::$processed_events[ $event_key ] = true;

		if ( ! ( $order instanceof WC_Order ) ) {
			$order = wc_get_order( $order_id );
		}

		if ( ! $order ) {
			return;
		}

		$customer_id = $order->get_customer_id();
		if ( $customer_id > 0 ) {
			$this->cache->invalidate_user( $customer_id );
		}

		do_action( 'wpcalibrate_access_order_status_changed', $order_id, $customer_id, $old_status, $new_status );
	}

	/**
	 * Handle order refunds.
	 *
	 * @param int $order_id  Order ID.
	 * @param int $refund_id Refund ID.
	 * @return void
	 */
	public function on_order_refunded( int $order_id, int $refund_id ): void {
		$event_key = "refund_{$order_id}_{$refund_id}";
		if ( isset( self::$processed_events[ $event_key ] ) ) {
			return;
		}
		self::$processed_events[ $event_key ] = true;

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$customer_id = $order->get_customer_id();
		if ( $customer_id > 0 ) {
			$this->cache->invalidate_user( $customer_id );
		}

		do_action( 'wpcalibrate_access_order_refunded', $order_id, $customer_id, $refund_id );
	}
}
