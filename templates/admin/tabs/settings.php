<?php
/**
 * General Settings Admin Tab.
 *
 * @package WPCalibrate\CommerceAccessRules
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$all_order_statuses = function_exists( 'wc_get_order_statuses' )
	? wc_get_order_statuses()
	: array(
		'wc-completed'  => __( 'Completed', 'wpcalibrate-commerce-access-rules' ),
		'wc-processing' => __( 'Processing', 'wpcalibrate-commerce-access-rules' ),
		'wc-on-hold'    => __( 'On hold', 'wpcalibrate-commerce-access-rules' ),
		'wc-pending'    => __( 'Pending payment', 'wpcalibrate-commerce-access-rules' ),
	);

$qualifying_statuses = get_option( 'wpcal_qualifying_order_statuses', array( 'wc-completed', 'wc-processing' ) );
$default_message     = get_option( 'wpcal_default_restriction_message', '' );
$multiple_logic      = get_option( 'wpcal_multiple_rules_logic', 'OR' );
$hide_search         = (bool) get_option( 'wpcal_hide_from_search', false );
$hide_archives       = (bool) get_option( 'wpcal_hide_from_archives', false );
$enable_cache        = (bool) get_option( 'wpcal_enable_cache', true );
$enable_logging      = (bool) get_option( 'wpcal_enable_logging', false );
$preserve_data       = (bool) get_option( 'wpcal_preserve_data_on_uninstall', true );
?>

<div class="wpcal-settings-container">
	<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=' . \WPCalibrate\CommerceAccessRules\Admin\Menu::PAGE_SLUG . '&tab=settings' ) ); ?>">
		<?php wp_nonce_field( 'wpcal_save_settings', 'wpcal_settings_nonce' ); ?>
		<input type="hidden" name="wpcal_action" value="save_settings" />

		<div class="wpcal-card wpcal-form-section">
			<h2><?php esc_html_e( 'WooCommerce Commerce Access Defaults', 'wpcalibrate-commerce-access-rules' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Configure default order statuses and evaluation rules.', 'wpcalibrate-commerce-access-rules' ); ?>
			</p>

			<div class="wpcal-form-row">
				<label><strong><?php esc_html_e( 'Default Qualifying Order Statuses:', 'wpcalibrate-commerce-access-rules' ); ?></strong></label>
				<p class="description"><?php esc_html_e( 'Orders must possess one of these statuses to grant purchase-derived content access:', 'wpcalibrate-commerce-access-rules' ); ?></p>
				<div class="wpcal-order-status-list" style="margin-top: 8px;">
					<?php foreach ( $all_order_statuses as $st_key => $st_label ) : ?>
						<label style="display: block; margin-bottom: 6px;">
							<input type="checkbox" name="wpcal_qualifying_order_statuses[]" value="<?php echo esc_attr( $st_key ); ?>" <?php checked( in_array( $st_key, (array) $qualifying_statuses, true ) ); ?> />
							<?php echo esc_html( $st_label ); ?> <code>(<?php echo esc_html( $st_key ); ?>)</code>
						</label>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="wpcal-form-row" style="margin-top: 16px;">
				<label for="wpcal-multiple-rules-logic"><strong><?php esc_html_e( 'Multiple Rules Conflict Handling:', 'wpcalibrate-commerce-access-rules' ); ?></strong></label>
				<select id="wpcal-multiple-rules-logic" name="wpcal_multiple_rules_logic" class="wpcal-select">
					<option value="OR" <?php selected( $multiple_logic, 'OR' ); ?>>
						<?php esc_html_e( 'Allow access if customer satisfies ANY assigned rule (OR logic - Recommended)', 'wpcalibrate-commerce-access-rules' ); ?>
					</option>
					<option value="AND" <?php selected( $multiple_logic, 'AND' ); ?>>
						<?php esc_html_e( 'Require customer to satisfy ALL assigned rules (AND logic)', 'wpcalibrate-commerce-access-rules' ); ?>
					</option>
				</select>
				<p class="description">
					<?php esc_html_e( 'When multiple rules apply to the same post, OR logic allows access if at least one rule is satisfied. Highest priority rule governs denial if all fail.', 'wpcalibrate-commerce-access-rules' ); ?>
				</p>
			</div>
		</div>

		<div class="wpcal-card wpcal-form-section" style="margin-top: 20px;">
			<h2><?php esc_html_e( 'Content Visibility & Leakage Prevention', 'wpcalibrate-commerce-access-rules' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Control whether restricted posts appear in WordPress listings.', 'wpcalibrate-commerce-access-rules' ); ?>
			</p>

			<div class="wpcal-form-row">
				<label style="display: block; margin-bottom: 8px;">
					<input type="checkbox" name="wpcal_hide_from_search" value="1" <?php checked( $hide_search ); ?> />
					<strong><?php esc_html_e( 'Hide restricted content from WordPress search results', 'wpcalibrate-commerce-access-rules' ); ?></strong>
				</label>
				<p class="description" style="margin-left: 24px;">
					<?php esc_html_e( 'When checked, unauthorized visitors will not see restricted post titles in WordPress search queries.', 'wpcalibrate-commerce-access-rules' ); ?>
				</p>
			</div>

			<div class="wpcal-form-row" style="margin-top: 12px;">
				<label style="display: block; margin-bottom: 8px;">
					<input type="checkbox" name="wpcal_hide_from_archives" value="1" <?php checked( $hide_archives ); ?> />
					<strong><?php esc_html_e( 'Hide restricted content from category and archive loops', 'wpcalibrate-commerce-access-rules' ); ?></strong>
				</label>
				<p class="description" style="margin-left: 24px;">
					<?php esc_html_e( 'When checked, unauthorized visitors will not see restricted posts in general archive pages.', 'wpcalibrate-commerce-access-rules' ); ?>
				</p>
			</div>

			<div class="wpcal-form-row" style="margin-top: 16px;">
				<label for="wpcal-default-message"><strong><?php esc_html_e( 'Default Restriction Message Markup:', 'wpcalibrate-commerce-access-rules' ); ?></strong></label>
				<?php
				wp_editor(
					$default_message,
					'wpcal_default_restriction_message',
					array(
						'textarea_name' => 'wpcal_default_restriction_message',
						'textarea_rows' => 5,
						'media_buttons' => false,
						'teeny'         => true,
					)
				);
				?>
			</div>
		</div>

		<div class="wpcal-card wpcal-form-section" style="margin-top: 20px;">
			<h2><?php esc_html_e( 'Performance & Maintenance', 'wpcalibrate-commerce-access-rules' ); ?></h2>

			<div class="wpcal-form-row">
				<label style="display: block; margin-bottom: 8px;">
					<input type="checkbox" name="wpcal_enable_cache" value="1" <?php checked( $enable_cache ); ?> />
					<strong><?php esc_html_e( 'Enable user-isolated persistent access caching', 'wpcalibrate-commerce-access-rules' ); ?></strong>
				</label>
				<p class="description" style="margin-left: 24px;">
					<?php esc_html_e( 'Improves page load performance by caching access decisions with strict per-user versioning and automatic invalidation on order status changes.', 'wpcalibrate-commerce-access-rules' ); ?>
				</p>
			</div>

			<div class="wpcal-form-row" style="margin-top: 12px;">
				<label style="display: block; margin-bottom: 8px;">
					<input type="checkbox" name="wpcal_enable_logging" value="1" <?php checked( $enable_logging ); ?> />
					<strong><?php esc_html_e( 'Enable diagnostic logging to WooCommerce Logs', 'wpcalibrate-commerce-access-rules' ); ?></strong>
				</label>
				<p class="description" style="margin-left: 24px;">
					<?php esc_html_e( 'Logs access evaluation events and cache invalidations for debugging. Sensitive keys and passwords are automatically masked.', 'wpcalibrate-commerce-access-rules' ); ?>
				</p>
			</div>

			<div class="wpcal-form-row" style="margin-top: 16px;">
				<label style="display: block; margin-bottom: 8px;">
					<input type="checkbox" name="wpcal_preserve_data_on_uninstall" value="1" <?php checked( $preserve_data ); ?> />
					<strong><?php esc_html_e( 'Preserve plugin data on uninstall', 'wpcalibrate-commerce-access-rules' ); ?></strong>
				</label>
				<p class="description" style="margin-left: 24px;">
					<?php esc_html_e( 'When unchecked, completely deleting the plugin from WordPress will purge all restriction rules, assignments, and settings. WooCommerce orders, products, and customer records are NEVER deleted.', 'wpcalibrate-commerce-access-rules' ); ?>
				</p>
			</div>
		</div>

		<div style="margin-top: 20px;">
			<button type="submit" class="button button-primary button-large">
				<?php esc_html_e( 'Save Settings', 'wpcalibrate-commerce-access-rules' ); ?>
			</button>
		</div>
	</form>
</div>
