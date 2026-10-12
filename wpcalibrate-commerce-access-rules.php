<?php
/**
 * Plugin Name:       WPCalibrate Content Restriction
 * Plugin URI:        https://wpcalibrate.com/plugins/wpcalibrate-commerce-access-rules/
 * Description:       Restricts access to WordPress content according to WooCommerce purchases, customers, roles, dates, and configurable access rules.
 * Version:           1.0.2
 * Requires at least: 6.5
 * Requires PHP:      8.2
 * Author:            WPCalibrate
 * Author URI:        https://wpcalibrate.com/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wpcalibrate-commerce-access-rules
 * Domain Path:       /languages
 * Update URI:        https://github.com/zeeshanraza-official/wpcalibrate-commerce-access-rules
 * GitHub Plugin URI: zeeshanraza-official/wpcalibrate-commerce-access-rules
 * Primary Branch:    main
 * Release Asset:     true
 *
 * WC requires at least: 8.0
 * WC tested up to:      11.2.1
 * Tested up to:         7.1.3
 *
 * @package WPCalibrate\CommerceAccessRules
 */

declare(strict_types=1);

namespace WPCalibrate\CommerceAccessRules;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin version constant.
 */
define( 'WPCALIBRATE_COMMERCE_ACCESS_RULES_VERSION', '1.0.2' );

/**
 * Plugin file path constant.
 */
define( 'WPCALIBRATE_COMMERCE_ACCESS_RULES_FILE', __FILE__ );

/**
 * Plugin directory path constant.
 */
define( 'WPCALIBRATE_COMMERCE_ACCESS_RULES_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL constant.
 */
define( 'WPCALIBRATE_COMMERCE_ACCESS_RULES_URL', plugin_dir_url( __FILE__ ) );

/**
 * Minimum PHP version required.
 */
define( 'WPCALIBRATE_COMMERCE_ACCESS_RULES_MIN_PHP', '8.2.0' );

/**
 * Minimum WordPress version required.
 */
define( 'WPCALIBRATE_COMMERCE_ACCESS_RULES_MIN_WP', '6.5' );

/**
 * Minimum WooCommerce version required.
 */
define( 'WPCALIBRATE_COMMERCE_ACCESS_RULES_MIN_WC', '8.0.0' );

// Load the autoloader.
require_once WPCALIBRATE_COMMERCE_ACCESS_RULES_DIR . 'src/Autoloader.php';
Autoloader::register( WPCALIBRATE_COMMERCE_ACCESS_RULES_DIR . 'src/' );

/**
 * Declare WooCommerce HPOS and Cart/Checkout Blocks compatibility.
 */
add_action(
	'before_woocommerce_init',
	static function (): void {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				__FILE__,
				true
			);
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'cart_checkout_blocks',
				__FILE__,
				true
			);
		}
	}
);

/**
 * Activation hook callback.
 */
register_activation_hook(
	__FILE__,
	static function (): void {
		Plugin::activate();
	}
);

/**
 * Deactivation hook callback.
 */
register_deactivation_hook(
	__FILE__,
	static function (): void {
		Plugin::deactivate();
	}
);

/**
 * Initialize the plugin after all active plugins are loaded.
 */
add_action(
	'plugins_loaded',
	static function (): void {
		Plugin::get_instance()->init();
	},
	10
);
