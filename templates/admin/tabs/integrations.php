<?php
/**
 * Integrations Admin Tab.
 *
 * @package WPCalibrate\CommerceAccessRules
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wc_active        = class_exists( 'WooCommerce' );
$hpos_active      = class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' )
	&& method_exists( '\Automattic\WooCommerce\Utilities\OrderUtil', 'custom_orders_table_usage_is_enabled' )
	&& \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
$elementor_active = class_exists( '\Elementor\Plugin' );
$wcs_active       = function_exists( 'wcs_user_has_subscription' );
?>

<div class="wpcal-integrations-container">
	<div class="wpcal-card">
		<h2><?php esc_html_e( 'Supported Ecosystem Integrations', 'wpcalibrate-commerce-access-rules' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'WPCalibrate Content Restriction features modular, optional integrations that operate seamlessly without hard dependencies.', 'wpcalibrate-commerce-access-rules' ); ?>
		</p>
	</div>

	<div class="wpcal-grid-2" style="margin-top: 20px;">
		<!-- WooCommerce Card -->
		<div class="wpcal-card">
			<div class="wpcal-integration-header">
				<h3><?php esc_html_e( 'WooCommerce Core & HPOS', 'wpcalibrate-commerce-access-rules' ); ?></h3>
				<?php if ( $wc_active ) : ?>
					<span class="wpcal-badge wpcal-badge-success"><?php esc_html_e( 'Connected', 'wpcalibrate-commerce-access-rules' ); ?></span>
				<?php else : ?>
					<span class="wpcal-badge wpcal-badge-danger"><?php esc_html_e( 'Missing / Inactive', 'wpcalibrate-commerce-access-rules' ); ?></span>
				<?php endif; ?>
			</div>
			<p>
				<?php esc_html_e( 'WooCommerce provides the commercial backbone for order lookups, customer purchases, order status events, and category relations.', 'wpcalibrate-commerce-access-rules' ); ?>
			</p>
			<ul class="wpcal-feature-list">
				<li>✓ <?php esc_html_e( 'HPOS (High-Performance Order Storage) fully supported via CRUD APIs.', 'wpcalibrate-commerce-access-rules' ); ?></li>
				<li>✓ <?php esc_html_e( 'Cart and Checkout Blocks declared compatible.', 'wpcalibrate-commerce-access-rules' ); ?></li>
				<li>✓ <?php esc_html_e( 'Item-level partial and full refund tracking.', 'wpcalibrate-commerce-access-rules' ); ?></li>
			</ul>
		</div>

		<!-- Elementor Card -->
		<div class="wpcal-card">
			<div class="wpcal-integration-header">
				<h3><?php esc_html_e( 'Elementor Page Builder', 'wpcalibrate-commerce-access-rules' ); ?></h3>
				<?php if ( $elementor_active ) : ?>
					<span class="wpcal-badge wpcal-badge-success"><?php esc_html_e( 'Active & Loaded', 'wpcalibrate-commerce-access-rules' ); ?></span>
				<?php else : ?>
					<span class="wpcal-badge wpcal-badge-neutral"><?php esc_html_e( 'Optional (Not Active)', 'wpcalibrate-commerce-access-rules' ); ?></span>
				<?php endif; ?>
			</div>
			<p>
				<?php esc_html_e( 'Adds server-side restriction controls directly into Elementor sections, containers, and widgets.', 'wpcalibrate-commerce-access-rules' ); ?>
			</p>
			<ul class="wpcal-feature-list">
				<li>✓ <?php esc_html_e( 'Controls appear under Elementor Advanced tab.', 'wpcalibrate-commerce-access-rules' ); ?></li>
				<li>✓ <?php esc_html_e( 'Server-side enforcement prevents HTML leakage in browser DOM.', 'wpcalibrate-commerce-access-rules' ); ?></li>
				<li>✓ <?php esc_html_e( 'Fails gracefully if Elementor is deactivated.', 'wpcalibrate-commerce-access-rules' ); ?></li>
			</ul>
		</div>

		<!-- WooCommerce Subscriptions Card -->
		<div class="wpcal-card">
			<div class="wpcal-integration-header">
				<h3><?php esc_html_e( 'WooCommerce Subscriptions', 'wpcalibrate-commerce-access-rules' ); ?></h3>
				<?php if ( $wcs_active ) : ?>
					<span class="wpcal-badge wpcal-badge-success"><?php esc_html_e( 'Active & Loaded', 'wpcalibrate-commerce-access-rules' ); ?></span>
				<?php else : ?>
					<span class="wpcal-badge wpcal-badge-neutral"><?php esc_html_e( 'Optional (Not Active)', 'wpcalibrate-commerce-access-rules' ); ?></span>
				<?php endif; ?>
			</div>
			<p>
				<?php esc_html_e( 'Enables recurring subscription access conditions based on active subscription products.', 'wpcalibrate-commerce-access-rules' ); ?>
			</p>
			<ul class="wpcal-feature-list">
				<li>✓ <?php esc_html_e( 'Validates active and pending-cancel subscription states.', 'wpcalibrate-commerce-access-rules' ); ?></li>
				<li>✓ <?php esc_html_e( 'Automatically invalidates access cache upon subscription status change.', 'wpcalibrate-commerce-access-rules' ); ?></li>
				<li>✓ <?php esc_html_e( 'Strictly optional: core features operate without subscriptions.', 'wpcalibrate-commerce-access-rules' ); ?></li>
			</ul>
		</div>

		<!-- Gutenberg Blocks Card -->
		<div class="wpcal-card">
			<div class="wpcal-integration-header">
				<h3><?php esc_html_e( 'WordPress Block Editor (Gutenberg)', 'wpcalibrate-commerce-access-rules' ); ?></h3>
				<span class="wpcal-badge wpcal-badge-success"><?php esc_html_e( 'Core Native', 'wpcalibrate-commerce-access-rules' ); ?></span>
			</div>
			<p>
				<?php esc_html_e( 'Native container block allows restricting any nested blocks in the WordPress post editor.', 'wpcalibrate-commerce-access-rules' ); ?>
			</p>
			<ul class="wpcal-feature-list">
				<li>✓ <code>wpcalibrate/content-restriction</code> <?php esc_html_e( 'wrapper block.', 'wpcalibrate-commerce-access-rules' ); ?></li>
				<li>✓ <?php esc_html_e( 'Inner content rendered strictly server-side.', 'wpcalibrate-commerce-access-rules' ); ?></li>
			</ul>
		</div>
	</div>
</div>
