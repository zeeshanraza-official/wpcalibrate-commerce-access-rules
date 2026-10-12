<?php
/**
 * Admin Layout Template.
 *
 * @package WPCalibrate\CommerceAccessRules
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Variables passed from Menu controller:
 *
 * @var string                                                      $current_tab
 * @var \WPCalibrate\CommerceAccessRules\Domain\Rule\RuleRepository $rule_repo
 * @var \WPCalibrate\CommerceAccessRules\Licensing\LicenseServiceInterface $license_service
 */

$brand_icon_url = WPCALIBRATE_COMMERCE_ACCESS_RULES_URL . 'branding/icon-dark.png';
$tabs = array(
	'overview'     => __( 'Overview', 'wpcalibrate-commerce-access-rules' ),
	'rules'        => __( 'Restriction Rules', 'wpcalibrate-commerce-access-rules' ),
	'inspector'    => __( 'Access Inspector', 'wpcalibrate-commerce-access-rules' ),
	'settings'     => __( 'General Settings', 'wpcalibrate-commerce-access-rules' ),
	'integrations' => __( 'Integrations', 'wpcalibrate-commerce-access-rules' ),
	'license'      => __( 'License', 'wpcalibrate-commerce-access-rules' ),
	'support'      => __( 'Support & Contact', 'wpcalibrate-commerce-access-rules' ),
);
?>

<div class="wrap wpcal-admin-wrap">
	<header class="wpcal-header">
		<div class="wpcal-brand-area">
			<img src="<?php echo esc_url( $brand_icon_url ); ?>" alt="WPCalibrate" class="wpcal-brand-logo" width="36" height="36" />
			<div class="wpcal-title-meta">
				<h1 class="wpcal-main-title"><?php esc_html_e( 'WPCalibrate Content Restriction', 'wpcalibrate-commerce-access-rules' ); ?></h1>
				<span class="wpcal-version-badge"><?php echo esc_html( 'v' . WPCALIBRATE_COMMERCE_ACCESS_RULES_VERSION ); ?></span>
			</div>
		</div>
		<div class="wpcal-header-links">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-access-rules&tab=rules&action=add' ) ); ?>" class="button button-primary">
				+ <?php esc_html_e( 'Add New Rule', 'wpcalibrate-commerce-access-rules' ); ?>
			</a>
		</div>
	</header>

	<nav class="nav-tab-wrapper wpcal-nav-tabs" aria-label="<?php esc_attr_e( 'Plugin navigation tabs', 'wpcalibrate-commerce-access-rules' ); ?>">
		<?php foreach ( $tabs as $tab_key => $tab_label ) : ?>
			<?php
			$tab_url = admin_url( 'admin.php?page=' . \WPCalibrate\CommerceAccessRules\Admin\Menu::PAGE_SLUG . '&tab=' . $tab_key );
			$active_class = ( $current_tab === $tab_key ) ? 'nav-tab-active' : '';
			?>
			<a href="<?php echo esc_url( $tab_url ); ?>" class="nav-tab <?php echo esc_attr( $active_class ); ?>">
				<?php echo esc_html( $tab_label ); ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<main class="wpcal-tab-content">
		<?php
		$tab_file = WPCALIBRATE_COMMERCE_ACCESS_RULES_DIR . 'templates/admin/tabs/' . $current_tab . '.php';
		if ( file_exists( $tab_file ) ) {
			include $tab_file;
		} else {
			include WPCALIBRATE_COMMERCE_ACCESS_RULES_DIR . 'templates/admin/tabs/overview.php';
		}
		?>
	</main>
</div>
