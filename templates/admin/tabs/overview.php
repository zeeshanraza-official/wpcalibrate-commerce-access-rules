<?php
/**
 * Overview Admin Tab.
 *
 * @package WPCalibrate\CommerceAccessRules
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @var \WPCalibrate\CommerceAccessRules\Domain\Rule\RuleRepository $rule_repo
 */

$total_rules  = $rule_repo->count();
$active_rules = $rule_repo->count( array( 'status' => 'active' ) );
$wc_active        = class_exists( 'WooCommerce' );
$hpos_active      = class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' )
	&& method_exists( '\Automattic\WooCommerce\Utilities\OrderUtil', 'custom_orders_table_usage_is_enabled' )
	&& \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
$elementor_active = class_exists( '\Elementor\Plugin' );
$wcs_active       = function_exists( 'wcs_user_has_subscription' );
?>

<div class="wpcal-overview-dashboard">
	<div class="wpcal-grid-3">
		<div class="wpcal-card wpcal-stat-card">
			<div class="wpcal-stat-number"><?php echo esc_html( (string) $total_rules ); ?></div>
			<div class="wpcal-stat-label"><?php esc_html_e( 'Total Restriction Rules', 'wpcalibrate-commerce-access-rules' ); ?></div>
		</div>

		<div class="wpcal-card wpcal-stat-card">
			<div class="wpcal-stat-number wpcal-text-success"><?php echo esc_html( (string) $active_rules ); ?></div>
			<div class="wpcal-stat-label"><?php esc_html_e( 'Active Rules Enforcing Access', 'wpcalibrate-commerce-access-rules' ); ?></div>
		</div>

		<div class="wpcal-card wpcal-stat-card">
			<div class="wpcal-stat-number">
				<?php echo $wc_active ? '<span class="dashicons dashicons-yes-alt wpcal-icon-ok"></span>' : '<span class="dashicons dashicons-warning wpcal-icon-warn"></span>'; ?>
			</div>
			<div class="wpcal-stat-label">
				<?php
				if ( $wc_active ) {
					echo esc_html__( 'WooCommerce Connected', 'wpcalibrate-commerce-access-rules' );
					if ( $hpos_active ) {
						echo ' <small>(' . esc_html__( 'HPOS Active', 'wpcalibrate-commerce-access-rules' ) . ')</small>';
					}
				} else {
					esc_html_e( 'WooCommerce Inactive', 'wpcalibrate-commerce-access-rules' );
				}
				?>
			</div>
		</div>
	</div>

	<div class="wpcal-grid-2" style="margin-top: 24px;">
		<div class="wpcal-card">
			<h2><?php esc_html_e( 'Quick Actions', 'wpcalibrate-commerce-access-rules' ); ?></h2>
			<ul class="wpcal-action-list">
				<li>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-access-rules&tab=rules&action=add' ) ); ?>" class="button button-primary">
						+ <?php esc_html_e( 'Create Restriction Rule', 'wpcalibrate-commerce-access-rules' ); ?>
					</a>
					<p class="description"><?php esc_html_e( 'Define purchase, role, user, or date conditions for access.', 'wpcalibrate-commerce-access-rules' ); ?></p>
				</li>
				<li>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-access-rules&tab=inspector' ) ); ?>" class="button button-secondary">
						🔍 <?php esc_html_e( 'Inspect Customer Access', 'wpcalibrate-commerce-access-rules' ); ?>
					</a>
					<p class="description"><?php esc_html_e( 'Search any customer to view their granted access and qualifying orders.', 'wpcalibrate-commerce-access-rules' ); ?></p>
				</li>
				<li>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-access-rules&tab=settings' ) ); ?>" class="button button-secondary">
						⚙ <?php esc_html_e( 'Configure General Settings', 'wpcalibrate-commerce-access-rules' ); ?>
					</a>
					<p class="description"><?php esc_html_e( 'Customize default denial actions, qualifying order statuses, and caching.', 'wpcalibrate-commerce-access-rules' ); ?></p>
				</li>
			</ul>
		</div>

		<div class="wpcal-card">
			<h2><?php esc_html_e( 'Integration Health', 'wpcalibrate-commerce-access-rules' ); ?></h2>
			<table class="widefat striped" style="border: 0;">
				<tbody>
					<tr>
						<td><strong><?php esc_html_e( 'WooCommerce Core', 'wpcalibrate-commerce-access-rules' ); ?></strong></td>
						<td>
							<?php if ( $wc_active ) : ?>
								<span class="wpcal-badge wpcal-badge-success"><?php esc_html_e( 'Active', 'wpcalibrate-commerce-access-rules' ); ?></span>
							<?php else : ?>
								<span class="wpcal-badge wpcal-badge-danger"><?php esc_html_e( 'Inactive', 'wpcalibrate-commerce-access-rules' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'WooCommerce HPOS (High-Performance Order Storage)', 'wpcalibrate-commerce-access-rules' ); ?></strong></td>
						<td>
							<?php if ( $hpos_active ) : ?>
								<span class="wpcal-badge wpcal-badge-success"><?php esc_html_e( 'Enabled', 'wpcalibrate-commerce-access-rules' ); ?></span>
							<?php else : ?>
								<span class="wpcal-badge wpcal-badge-neutral"><?php esc_html_e( 'Standard / Legacy', 'wpcalibrate-commerce-access-rules' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Elementor Page Builder', 'wpcalibrate-commerce-access-rules' ); ?></strong></td>
						<td>
							<?php if ( $elementor_active ) : ?>
								<span class="wpcal-badge wpcal-badge-success"><?php esc_html_e( 'Available (Server-side Enforcement Active)', 'wpcalibrate-commerce-access-rules' ); ?></span>
							<?php else : ?>
								<span class="wpcal-badge wpcal-badge-neutral"><?php esc_html_e( 'Optional (Not installed)', 'wpcalibrate-commerce-access-rules' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'WooCommerce Subscriptions', 'wpcalibrate-commerce-access-rules' ); ?></strong></td>
						<td>
							<?php if ( $wcs_active ) : ?>
								<span class="wpcal-badge wpcal-badge-success"><?php esc_html_e( 'Available (Subscription Rules Enabled)', 'wpcalibrate-commerce-access-rules' ); ?></span>
							<?php else : ?>
								<span class="wpcal-badge wpcal-badge-neutral"><?php esc_html_e( 'Optional (Not installed)', 'wpcalibrate-commerce-access-rules' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</div>
