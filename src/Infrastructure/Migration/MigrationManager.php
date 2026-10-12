<?php
/**
 * Versioned Migration and Upgrade Manager.
 *
 * @package WPCalibrate\CommerceAccessRules
 */

declare(strict_types=1);

namespace WPCalibrate\CommerceAccessRules\Infrastructure\Migration;

use WPCalibrate\CommerceAccessRules\Capabilities;

/**
 * MigrationManager class.
 */
final class MigrationManager {

	public const CURRENT_SCHEMA_VERSION = '1.0.0';
	public const OPTION_SCHEMA_VERSION  = 'wpcal_schema_version';
	public const OPTION_PLUGIN_VERSION  = 'wpcal_plugin_version';

	/**
	 * Run migrations if needed.
	 *
	 * @return void
	 */
	public static function run(): void {
		$installed_schema = (string) get_option( self::OPTION_SCHEMA_VERSION, '0.0.0' );

		if ( version_compare( $installed_schema, self::CURRENT_SCHEMA_VERSION, '<' ) ) {
			self::migrate( $installed_schema );
			update_option( self::OPTION_SCHEMA_VERSION, self::CURRENT_SCHEMA_VERSION );
		}

		update_option( self::OPTION_PLUGIN_VERSION, WPCALIBRATE_COMMERCE_ACCESS_RULES_VERSION );
	}

	/**
	 * Perform version-specific migration routines.
	 *
	 * @param string $from_version
	 * @return void
	 */
	private static function migrate( string $from_version ): void {
		// Migration for 1.0.0: ensure capabilities and initial options exist.
		if ( version_compare( $from_version, '1.0.0', '<' ) ) {
			Capabilities::register_capabilities();

			// Set default options if not already set.
			add_option( 'wpcal_qualifying_order_statuses', array( 'wc-completed', 'wc-processing' ) );
			add_option( 'wpcal_multiple_rules_logic', 'OR' );
			add_option( 'wpcal_enable_cache', true );
			add_option( 'wpcal_preserve_data_on_uninstall', true );
			add_option( 'wpcal_hide_from_search', false );
			add_option( 'wpcal_hide_from_archives', false );
			add_option( 'wpcal_enable_logging', false );
		}
	}
}
