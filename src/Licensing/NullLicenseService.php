<?php
/**
 * Null License Service Implementation.
 *
 * Provides safe state handling, key masking, and developer notice until external provider endpoints are specified.
 *
 * @package WPCalibrate\CommerceAccessRules
 */

declare(strict_types=1);

namespace WPCalibrate\CommerceAccessRules\Licensing;

/**
 * NullLicenseService class.
 */
final class NullLicenseService implements LicenseServiceInterface {

	public const OPTION_KEY  = 'wpcal_license_key';
	public const OPTION_DATA = 'wpcal_license_data';

	/**
	 * Attempt to activate a license key.
	 *
	 * @param string $license_key
	 * @return array<string, mixed>
	 */
	public function activate( string $license_key ): array {
		$clean_key = trim( sanitize_text_field( $license_key ) );
		if ( empty( $clean_key ) ) {
			return array(
				'success' => false,
				'message' => __( 'Please enter a valid license key.', 'wpcalibrate-commerce-access-rules' ),
			);
		}

		// Store sanitized key.
		update_option( self::OPTION_KEY, $clean_key );

		// Set status to pending verification since no external provider endpoints are configured.
		$data = array(
			'status'       => 'inactive',
			'expiry'       => null,
			'renewal_url'  => 'https://marketplace.wpcalibrate.com/',
			'last_checked' => time(),
			'provider'     => 'pending_provider_specification',
		);
		update_option( self::OPTION_DATA, $data );

		return array(
			'success' => false,
			'message' => __( 'License server integration is currently pending provider specification. Remote activation is disabled.', 'wpcalibrate-commerce-access-rules' ),
		);
	}

	/**
	 * Deactivate current license.
	 *
	 * @return array<string, mixed>
	 */
	public function deactivate(): array {
		delete_option( self::OPTION_KEY );
		delete_option( self::OPTION_DATA );

		return array(
			'success' => true,
			'message' => __( 'License deactivated successfully.', 'wpcalibrate-commerce-access-rules' ),
		);
	}

	/**
	 * Get current license status.
	 *
	 * @return string
	 */
	public function get_status(): string {
		$data = get_option( self::OPTION_DATA, array() );
		if ( ! is_array( $data ) || empty( $data['status'] ) ) {
			$stored_key = (string) get_option( self::OPTION_KEY, '' );
			return ! empty( $stored_key ) ? 'inactive' : 'unregistered';
		}

		return (string) $data['status'];
	}

	/**
	 * Get masked license key for display.
	 *
	 * @return string
	 */
	public function get_masked_key(): string {
		$key = (string) get_option( self::OPTION_KEY, '' );
		if ( empty( $key ) ) {
			return '';
		}

		$len = strlen( $key );
		if ( $len <= 4 ) {
			return str_repeat( '•', $len );
		}

		$last4 = substr( $key, -4 );
		return str_repeat( '•', max( 8, $len - 4 ) ) . $last4;
	}

	/**
	 * Get stored license details.
	 *
	 * @return array<string, mixed>
	 */
	public function get_license_data(): array {
		$data = get_option( self::OPTION_DATA, array() );
		if ( ! is_array( $data ) ) {
			$data = array();
		}

		return wp_parse_args(
			$data,
			array(
				'status'       => $this->get_status(),
				'expiry'       => null,
				'renewal_url'  => 'https://marketplace.wpcalibrate.com/',
				'last_checked' => null,
				'provider'     => 'pending_provider_specification',
			)
		);
	}
}
