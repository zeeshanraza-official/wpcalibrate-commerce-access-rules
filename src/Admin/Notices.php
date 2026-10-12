<?php
/**
 * Admin Notices Handler.
 *
 * @package WPCalibrate\CommerceAccessRules
 */

declare(strict_types=1);

namespace WPCalibrate\CommerceAccessRules\Admin;

/**
 * Notices class.
 */
final class Notices {

	/**
	 * Flash notice transient key prefix.
	 */
	private const FLASH_KEY_PREFIX = 'wpcal_flash_notice_';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_notices', array( $this, 'render_admin_notices' ) );
	}

	/**
	 * Render admin notices.
	 *
	 * @return void
	 */
	public function render_admin_notices(): void {
		// 1. Dependency notice: Check if WooCommerce is active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			?>
			<div class="notice notice-error wpcal-notice">
				<p>
					<strong><?php esc_html_e( 'WPCalibrate Content Restriction requires WooCommerce to be installed and active.', 'wpcalibrate-commerce-access-rules' ); ?></strong>
					<?php esc_html_e( 'Please install and activate WooCommerce to enable commerce-based content access rules.', 'wpcalibrate-commerce-access-rules' ); ?>
				</p>
			</div>
			<?php
		}

		// 2. Render flash notices for current user.
		$user_id = (int) get_current_user_id();
		if ( $user_id <= 0 ) {
			return;
		}

		$flash_key = self::FLASH_KEY_PREFIX . $user_id;
		$notices   = get_transient( $flash_key );

		if ( is_array( $notices ) && ! empty( $notices ) ) {
			delete_transient( $flash_key );
			foreach ( $notices as $notice ) {
				$type    = sanitize_html_class( $notice['type'] ?? 'info' );
				$message = wp_kses_post( $notice['message'] ?? '' );
				?>
				<div class="notice notice-<?php echo esc_attr( $type ); ?> is-dismissible wpcal-notice">
					<p><?php echo $message; ?></p>
				</div>
				<?php
			}
		}
	}

	/**
	 * Add a flash notice to show on next page load.
	 *
	 * @param string $message Notice message.
	 * @param string $type    Notice type ('success', 'error', 'warning', 'info').
	 * @return void
	 */
	public static function add_flash_notice( string $message, string $type = 'success' ): void {
		$user_id = (int) get_current_user_id();
		if ( $user_id <= 0 ) {
			return;
		}

		$flash_key = self::FLASH_KEY_PREFIX . $user_id;
		$notices   = get_transient( $flash_key );
		if ( ! is_array( $notices ) ) {
			$notices = array();
		}

		$notices[] = array(
			'message' => $message,
			'type'    => $type,
		);

		set_transient( $flash_key, $notices, 60 );
	}
}
