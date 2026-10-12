<?php
/**
 * Admin Assets Enqueue Handler.
 *
 * @package WPCalibrate\CommerceAccessRules
 */

declare(strict_types=1);

namespace WPCalibrate\CommerceAccessRules\Admin;

/**
 * Assets class.
 */
final class Assets {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
	}

	/**
	 * Enqueue admin assets only on relevant screens.
	 *
	 * @param string $hook_suffix Current admin screen hook suffix.
	 * @return void
	 */
	public function enqueue_admin_assets( string $hook_suffix ): void {
		$is_plugin_page = isset( $_GET['page'] ) && in_array( sanitize_text_field( wp_unslash( $_GET['page'] ) ), array( 'wpcalibrate', 'wpcalibrate-access-rules' ), true );
		$is_post_editor = in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true );

		if ( ! $is_plugin_page && ! $is_post_editor ) {
			return; // Do not enqueue across the entire dashboard!
		}

		// Admin CSS.
		wp_enqueue_style(
			'wpcalibrate-access-rules-admin',
			WPCALIBRATE_COMMERCE_ACCESS_RULES_URL . 'assets/admin/css/admin.css',
			array(),
			WPCALIBRATE_COMMERCE_ACCESS_RULES_VERSION
		);

		// Admin JS.
		wp_enqueue_script(
			'wpcalibrate-access-rules-admin',
			WPCALIBRATE_COMMERCE_ACCESS_RULES_URL . 'assets/admin/js/admin.js',
			array( 'jquery' ),
			WPCALIBRATE_COMMERCE_ACCESS_RULES_VERSION,
			true
		);

		wp_localize_script(
			'wpcalibrate-access-rules-admin',
			'wpcalibrateAccessRules',
			array(
				'restUrl' => esc_url_raw( rest_url( 'wpcalibrate-commerce-access-rules/v1/' ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'strings' => array(
					'confirmDelete'    => __( 'Are you sure you want to delete this restriction rule? This cannot be undone.', 'wpcalibrate-commerce-access-rules' ),
					'confirmDuplicate' => __( 'Duplicate this restriction rule?', 'wpcalibrate-commerce-access-rules' ),
					'loading'          => __( 'Loading...', 'wpcalibrate-commerce-access-rules' ),
					'selectProduct'    => __( 'Search products...', 'wpcalibrate-commerce-access-rules' ),
					'selectCategory'   => __( 'Search categories...', 'wpcalibrate-commerce-access-rules' ),
					'selectUser'       => __( 'Search customers...', 'wpcalibrate-commerce-access-rules' ),
					'noResults'        => __( 'No results found', 'wpcalibrate-commerce-access-rules' ),
				),
			)
		);
	}

	/**
	 * Enqueue Gutenberg block assets in block editor.
	 *
	 * @return void
	 */
	public function enqueue_block_editor_assets(): void {
		wp_enqueue_script(
			'wpcalibrate-restriction-block',
			WPCALIBRATE_COMMERCE_ACCESS_RULES_URL . 'assets/admin/js/block.js',
			array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n', 'wp-block-editor' ),
			WPCALIBRATE_COMMERCE_ACCESS_RULES_VERSION,
			true
		);

		wp_enqueue_style(
			'wpcalibrate-restriction-block-editor',
			WPCALIBRATE_COMMERCE_ACCESS_RULES_URL . 'assets/admin/css/admin.css',
			array(),
			WPCALIBRATE_COMMERCE_ACCESS_RULES_VERSION
		);
	}

	/**
	 * Enqueue public styling only when necessary.
	 *
	 * @return void
	 */
	public function enqueue_frontend_assets(): void {
		wp_enqueue_style(
			'wpcalibrate-access-rules-public',
			WPCALIBRATE_COMMERCE_ACCESS_RULES_URL . 'assets/public/css/public.css',
			array(),
			WPCALIBRATE_COMMERCE_ACCESS_RULES_VERSION
		);
	}
}
