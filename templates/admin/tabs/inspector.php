<?php
/**
 * Customer Access Inspector Admin Tab.
 *
 * @package WPCalibrate\CommerceAccessRules
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wpcal-inspector-container">
	<div class="wpcal-card">
		<h2><?php esc_html_e( 'Customer Access Inspector', 'wpcalibrate-commerce-access-rules' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Search and inspect any WordPress user or WooCommerce customer to diagnose their real-time content access permissions, qualifying orders, expiry dates, and reason codes.', 'wpcalibrate-commerce-access-rules' ); ?>
		</p>

		<div class="wpcal-inspector-search-bar" style="margin-top: 16px; display: flex; gap: 8px; max-width: 600px;">
			<input type="number" id="wpcal-inspect-user-id" placeholder="<?php esc_attr_e( 'Enter User ID (e.g. 1, 25)', 'wpcalibrate-commerce-access-rules' ); ?>" style="flex: 1;" />
			<button type="button" id="wpcal-btn-inspect-user" class="button button-primary">
				<?php esc_html_e( 'Inspect Customer Access', 'wpcalibrate-commerce-access-rules' ); ?>
			</button>
		</div>
	</div>

	<div id="wpcal-inspector-results" style="margin-top: 24px; display: none;">
		<div class="wpcal-card" id="wpcal-user-summary-card">
			<!-- User info dynamically rendered via JS -->
		</div>

		<div class="wpcal-card" style="margin-top: 16px;">
			<h3><?php esc_html_e( 'Access Evaluation Report Across All Rules', 'wpcalibrate-commerce-access-rules' ); ?></h3>
			<table class="wp-list-table widefat striped" id="wpcal-inspector-rules-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Rule', 'wpcalibrate-commerce-access-rules' ); ?></th>
						<th><?php esc_html_e( 'Access Status', 'wpcalibrate-commerce-access-rules' ); ?></th>
						<th><?php esc_html_e( 'Reason / Evaluation', 'wpcalibrate-commerce-access-rules' ); ?></th>
						<th><?php esc_html_e( 'Grant Source / Order', 'wpcalibrate-commerce-access-rules' ); ?></th>
						<th><?php esc_html_e( 'Expiry Date', 'wpcalibrate-commerce-access-rules' ); ?></th>
					</tr>
				</thead>
				<tbody id="wpcal-inspector-tbody">
					<!-- Populated by JS -->
				</tbody>
			</table>
		</div>
	</div>

	<div id="wpcal-inspector-loading" style="display: none; padding: 20px; text-align: center;">
		<span class="spinner is-active" style="float: none; margin: 0 auto;"></span>
		<p><?php esc_html_e( 'Evaluating real-time customer access...', 'wpcalibrate-commerce-access-rules' ); ?></p>
	</div>
</div>
