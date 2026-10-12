<?php
/**
 * Plugin Uninstaller.
 *
 * @package WPCalibrate\CommerceAccessRules
 */

declare(strict_types=1);

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// 1. Check data retention preference.
$preserve_data = (bool) get_option( 'wpcal_preserve_data_on_uninstall', true );
if ( $preserve_data ) {
	// Administrator chose to preserve plugin data.
	return;
}

// Load autoloader to access Capabilities.
require_once dirname( __FILE__ ) . '/src/Autoloader.php';
\WPCalibrate\CommerceAccessRules\Autoloader::register( dirname( __FILE__ ) . '/src/' );

/**
 * Perform single-site uninstall routine.
 */
function wpcal_uninstall_single_site(): void {
	global $wpdb;

	// 1. Delete all restriction rules (CPT wpcal_access_rule).
	$rule_ids = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s",
			'wpcal_access_rule'
		)
	);

	if ( ! empty( $rule_ids ) ) {
		foreach ( $rule_ids as $rid ) {
			wp_delete_post( (int) $rid, true );
		}
	}

	// 2. Remove content assignment meta from posts/pages/products.
	delete_post_meta_by_key( '_wpcalibrate_access_rules' );
	delete_post_meta_by_key( '_wpcal_rule_config' );
	delete_post_meta_by_key( '_wpcal_rule_status' );
	delete_post_meta_by_key( '_wpcal_rule_priority' );

	// 3. Remove user meta cache versions.
	delete_metadata( 'user', 0, 'wpcal_cache_ver', '', true );

	// 4. Delete plugin options.
	$options_to_delete = array(
		'wpcal_qualifying_order_statuses',
		'wpcal_default_restriction_message',
		'wpcal_multiple_rules_logic',
		'wpcal_hide_from_search',
		'wpcal_hide_from_archives',
		'wpcal_enable_cache',
		'wpcal_enable_logging',
		'wpcal_preserve_data_on_uninstall',
		'wpcal_schema_version',
		'wpcal_plugin_version',
		'wpcal_license_key',
		'wpcal_license_data',
		'wpcal_global_cache_ver',
	);

	foreach ( $options_to_delete as $opt ) {
		delete_option( $opt );
	}

	// 5. Delete plugin transients.
	$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wpcal_%' OR option_name LIKE '_transient_timeout_wpcal_%'"
	);

	// 6. Remove plugin capabilities from all roles.
	\WPCalibrate\CommerceAccessRules\Capabilities::remove_capabilities();
}

// Handle multisite network uninstall if applicable.
if ( is_multisite() ) {
	$site_ids = get_sites( array( 'fields' => 'ids', 'number' => 0 ) );
	foreach ( $site_ids as $site_id ) {
		switch_to_blog( (int) $site_id );
		wpcal_uninstall_single_site();
		restore_current_blog();
	}
} else {
	wpcal_uninstall_single_site();
}
