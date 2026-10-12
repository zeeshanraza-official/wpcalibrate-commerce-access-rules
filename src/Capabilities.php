<?php
/**
 * Plugin Capabilities and Authorization Management.
 *
 * @package WPCalibrate\CommerceAccessRules
 */

declare(strict_types=1);

namespace WPCalibrate\CommerceAccessRules;

/**
 * Capabilities class.
 */
final class Capabilities {

	/**
	 * Capability to manage restriction rules (create, edit, delete, preview).
	 */
	public const MANAGE_RULES = 'wpcalibrate_manage_access_rules';

	/**
	 * Capability to manage plugin settings.
	 */
	public const MANAGE_SETTINGS = 'wpcalibrate_manage_access_settings';

	/**
	 * Capability to view customer access inspector.
	 */
	public const VIEW_INSPECTOR = 'wpcalibrate_view_access_inspector';

	/**
	 * Capability to manage plugin license.
	 */
	public const MANAGE_LICENSE = 'wpcalibrate_manage_access_license';

	/**
	 * Get all plugin-specific capabilities.
	 *
	 * @return string[] List of capability names.
	 */
	public static function get_all_capabilities(): array {
		return array(
			self::MANAGE_RULES,
			self::MANAGE_SETTINGS,
			self::VIEW_INSPECTOR,
			self::MANAGE_LICENSE,
		);
	}

	/**
	 * Register plugin capabilities to the administrator role idempotently.
	 *
	 * @return void
	 */
	public static function register_capabilities(): void {
		$admin_role = get_role( 'administrator' );
		if ( ! $admin_role ) {
			return;
		}

		foreach ( self::get_all_capabilities() as $cap ) {
			if ( ! $admin_role->has_cap( $cap ) ) {
				$admin_role->add_cap( $cap );
			}
		}
	}

	/**
	 * Remove plugin capabilities from all roles during uninstall.
	 *
	 * @return void
	 */
	public static function remove_capabilities(): void {
		global $wp_roles;

		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new \WP_Roles(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		$roles = $wp_roles->get_names();
		foreach ( array_keys( $roles ) as $role_slug ) {
			$role = get_role( $role_slug );
			if ( $role ) {
				foreach ( self::get_all_capabilities() as $cap ) {
					$role->remove_cap( $cap );
				}
			}
		}
	}

	/**
	 * Check if current user can manage restriction rules.
	 *
	 * @param int|null $user_id Optional user ID. Defaults to current user.
	 * @return bool
	 */
	public static function can_manage_rules( ?int $user_id = null ): bool {
		if ( null !== $user_id ) {
			return user_can( $user_id, self::MANAGE_RULES ) || user_can( $user_id, 'manage_options' );
		}
		return current_user_can( self::MANAGE_RULES ) || current_user_can( 'manage_options' );
	}

	/**
	 * Check if current user can manage settings.
	 *
	 * @return bool
	 */
	public static function can_manage_settings(): bool {
		return current_user_can( self::MANAGE_SETTINGS ) || current_user_can( 'manage_options' );
	}

	/**
	 * Check if current user can view access inspector.
	 *
	 * @return bool
	 */
	public static function can_view_inspector(): bool {
		return current_user_can( self::VIEW_INSPECTOR ) || current_user_can( 'manage_options' );
	}

	/**
	 * Check if current user can manage license.
	 *
	 * @return bool
	 */
	public static function can_manage_license(): bool {
		return current_user_can( self::MANAGE_LICENSE ) || current_user_can( 'manage_options' );
	}
}
