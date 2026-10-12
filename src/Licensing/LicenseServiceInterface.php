<?php
/**
 * License Service Interface.
 *
 * @package WPCalibrate\CommerceAccessRules
 */

declare(strict_types=1);

namespace WPCalibrate\CommerceAccessRules\Licensing;

/**
 * LicenseServiceInterface.
 */
interface LicenseServiceInterface {

	/**
	 * Attempt to activate a license key.
	 *
	 * @param string $license_key
	 * @return array<string, mixed> Result array with 'success' (bool) and 'message' (string).
	 */
	public function activate( string $license_key ): array;

	/**
	 * Attempt to deactivate the current license.
	 *
	 * @return array<string, mixed> Result array.
	 */
	public function deactivate(): array;

	/**
	 * Get current license status ('unregistered', 'inactive', 'active', 'expired', 'invalid').
	 *
	 * @return string
	 */
	public function get_status(): string;

	/**
	 * Get masked license key for display (e.g. ••••••••1234).
	 *
	 * @return string
	 */
	public function get_masked_key(): string;

	/**
	 * Get stored license details (status, expiry, renewal_url, etc.).
	 *
	 * @return array<string, mixed>
	 */
	public function get_license_data(): array;
}
