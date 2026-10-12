<?php
/**
 * License Management Admin Tab.
 *
 * @package WPCalibrate\CommerceAccessRules
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @var \WPCalibrate\CommerceAccessRules\Licensing\LicenseServiceInterface $license_service
 */

$masked_key   = $license_service->get_masked_key();
$status       = $license_service->get_status();
$license_data = $license_service->get_license_data();
?>

<div class="wpcal-license-container">
	<div class="wpcal-card">
		<h2><?php esc_html_e( 'License Management', 'wpcalibrate-commerce-access-rules' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Manage your WPCalibrate license to receive automatic updates and premium product support.', 'wpcalibrate-commerce-access-rules' ); ?>
		</p>

		<div class="notice notice-info inline" style="margin: 16px 0; padding: 12px;">
			<p>
				<strong><?php esc_html_e( 'Developer Notice:', 'wpcalibrate-commerce-access-rules' ); ?></strong>
				<?php esc_html_e( 'External licensing server integration is currently disabled pending specification of official WPCalibrate licensing API endpoints, request signatures, and renewal protocols. License keys are safely masked and stored locally.', 'wpcalibrate-commerce-access-rules' ); ?>
			</p>
		</div>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=' . \WPCalibrate\CommerceAccessRules\Admin\Menu::PAGE_SLUG . '&tab=license' ) ); ?>" style="max-width: 600px;">
			<?php wp_nonce_field( 'wpcal_license_action', 'wpcal_license_nonce' ); ?>

			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<label for="wpcal-license-key"><?php esc_html_e( 'License Key', 'wpcalibrate-commerce-access-rules' ); ?></label>
						</th>
						<td>
							<input type="password" id="wpcal-license-key" name="license_key" value="<?php echo esc_attr( $masked_key ); ?>" class="regular-text" placeholder="••••-••••-••••-••••" autocomplete="off" />
							<p class="description">
								<?php
								if ( ! empty( $masked_key ) ) {
									printf(
										/* translators: %s: masked key */
										esc_html__( 'Active stored key: %s', 'wpcalibrate-commerce-access-rules' ),
										'<code>' . esc_html( $masked_key ) . '</code>'
									);
								} else {
									esc_html_e( 'Enter your license key provided in your WPCalibrate purchase receipt.', 'wpcalibrate-commerce-access-rules' );
								}
								?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php esc_html_e( 'License Status', 'wpcalibrate-commerce-access-rules' ); ?></th>
						<td>
							<?php if ( 'active' === $status ) : ?>
								<span class="wpcal-badge wpcal-badge-success"><?php esc_html_e( 'Active', 'wpcalibrate-commerce-access-rules' ); ?></span>
							<?php elseif ( 'inactive' === $status ) : ?>
								<span class="wpcal-badge wpcal-badge-neutral"><?php esc_html_e( 'Pending Verification', 'wpcalibrate-commerce-access-rules' ); ?></span>
							<?php else : ?>
								<span class="wpcal-badge wpcal-badge-danger"><?php esc_html_e( 'Unregistered', 'wpcalibrate-commerce-access-rules' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>

					<?php if ( ! empty( $license_data['expiry'] ) ) : ?>
						<tr>
							<th scope="row"><?php esc_html_e( 'Expiration Date', 'wpcalibrate-commerce-access-rules' ); ?></th>
							<td>
								<?php echo esc_html( wp_date( get_option( 'date_format' ), (int) $license_data['expiry'] ) ); ?>
							</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>

			<div class="wpcal-license-actions" style="margin-top: 16px;">
				<button type="submit" name="wpcal_action" value="activate_license" class="button button-primary">
					<?php esc_html_e( 'Save & Verify License', 'wpcalibrate-commerce-access-rules' ); ?>
				</button>

				<?php if ( ! empty( $masked_key ) ) : ?>
					<button type="submit" name="wpcal_action" value="deactivate_license" class="button button-secondary" style="margin-left: 8px;">
						<?php esc_html_e( 'Deactivate License', 'wpcalibrate-commerce-access-rules' ); ?>
					</button>
				<?php endif; ?>

				<a href="https://marketplace.wpcalibrate.com/" target="_blank" rel="noopener noreferrer" class="button button-link" style="margin-left: 12px;">
					<?php esc_html_e( 'Renew or Purchase License ↗', 'wpcalibrate-commerce-access-rules' ); ?>
				</a>
			</div>
		</form>
	</div>
</div>
