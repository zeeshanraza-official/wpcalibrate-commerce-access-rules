<?php
/**
 * Main Plugin Composition Root.
 *
 * @package WPCalibrate\CommerceAccessRules
 */

declare(strict_types=1);

namespace WPCalibrate\CommerceAccessRules;

use WPCalibrate\CommerceAccessRules\Admin\Assets;
use WPCalibrate\CommerceAccessRules\Admin\Menu;
use WPCalibrate\CommerceAccessRules\Admin\MetaBox\ContentRestrictionMetaBox;
use WPCalibrate\CommerceAccessRules\Admin\Notices;
use WPCalibrate\CommerceAccessRules\Content\BlockRestriction;
use WPCalibrate\CommerceAccessRules\Content\FullContentRestriction;
use WPCalibrate\CommerceAccessRules\Content\Shortcode;
use WPCalibrate\CommerceAccessRules\Content\VisibilityProtection;
use WPCalibrate\CommerceAccessRules\Domain\Access\AccessEvaluator;
use WPCalibrate\CommerceAccessRules\Domain\Rule\RuleRepository;
use WPCalibrate\CommerceAccessRules\Infrastructure\Cache\AccessCache;
use WPCalibrate\CommerceAccessRules\Infrastructure\Migration\MigrationManager;
use WPCalibrate\CommerceAccessRules\Infrastructure\Update\GitHubUpdater;
use WPCalibrate\CommerceAccessRules\Integrations\Elementor\ElementorIntegration;
use WPCalibrate\CommerceAccessRules\Integrations\Subscriptions\SubscriptionsIntegration;
use WPCalibrate\CommerceAccessRules\Licensing\LicenseServiceInterface;
use WPCalibrate\CommerceAccessRules\Licensing\NullLicenseService;
use WPCalibrate\CommerceAccessRules\REST\RestController;
use WPCalibrate\CommerceAccessRules\WooCommerce\OrderHooks;
use WPCalibrate\CommerceAccessRules\WooCommerce\PurchaseEvaluator;

/**
 * Plugin class.
 */
final class Plugin {

	private static ?Plugin $instance = null;

	private RuleRepository $rule_repo;
	private PurchaseEvaluator $purchase_evaluator;
	private AccessCache $cache;
	private AccessEvaluator $evaluator;
	private LicenseServiceInterface $license_service;
	private Menu $menu;
	private Assets $assets;
	private Notices $notices;
	private ContentRestrictionMetaBox $metabox;
	private FullContentRestriction $full_content;
	private Shortcode $shortcode;
	private BlockRestriction $block_restriction;
	private VisibilityProtection $visibility;
	private OrderHooks $order_hooks;
	private RestController $rest_controller;
	private ElementorIntegration $elementor_integration;
	private SubscriptionsIntegration $subscriptions_integration;

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		// Private constructor for singleton.
	}

	/**
	 * Initialize plugin components and hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		// Run migrations and capability checks.
		MigrationManager::run();

		// Instantiate core services.
		$this->rule_repo                 = new RuleRepository();
		$this->purchase_evaluator        = new PurchaseEvaluator();
		$this->cache                     = new AccessCache();
		$this->evaluator                 = new AccessEvaluator( $this->rule_repo, $this->purchase_evaluator, $this->cache );
		$this->license_service           = new NullLicenseService();
		$this->menu                      = new Menu( $this->rule_repo, $this->license_service );
		$this->assets                    = new Assets();
		$this->notices                   = new Notices();
		$this->metabox                   = new ContentRestrictionMetaBox( $this->rule_repo );
		$this->full_content              = new FullContentRestriction( $this->evaluator );
		$this->shortcode                 = new Shortcode( $this->evaluator, $this->rule_repo );
		$this->block_restriction         = new BlockRestriction( $this->evaluator, $this->rule_repo );
		$this->visibility                = new VisibilityProtection( $this->evaluator );
		$this->order_hooks               = new OrderHooks( $this->cache );
		$this->rest_controller           = new RestController( $this->rule_repo, $this->evaluator );
		$this->elementor_integration     = new ElementorIntegration( $this->evaluator, $this->rule_repo );
		$this->subscriptions_integration = new SubscriptionsIntegration( $this->cache );

		// Register hooks.
		add_action( 'init', array( RuleRepository::class, 'register_post_type' ) );
		add_action( 'init', array( $this->block_restriction, 'register' ) );
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'admin_init', array( \WPCalibrate\CommerceAccessRules\Infrastructure\Migration\MigrationManager::class, 'run' ), 5 );
		add_action( 'rest_api_init', array( $this->rest_controller, 'register_routes' ) );

		$this->menu->register();
		$this->assets->register();
		$this->notices->register();
		$this->metabox->register();
		$this->full_content->register();
		$this->shortcode->register();
		$this->visibility->register();

		// Register WooCommerce and optional integrations if dependencies are active.
		if ( class_exists( 'WooCommerce' ) ) {
			$this->order_hooks->register();
		}

		$this->elementor_integration->register();
		$this->subscriptions_integration->register();

		// Register GitHub updater for WordPress dashboard updates from GitHub releases.
		if ( is_admin() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			$github_updater = new GitHubUpdater(
				WPCALIBRATE_COMMERCE_ACCESS_RULES_FILE,
				'zeeshanraza-official/wpcalibrate-commerce-access-rules',
				WPCALIBRATE_COMMERCE_ACCESS_RULES_VERSION
			);
			$github_updater->register();
		}
	}

	/**
	 * Load translation textdomain.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'wpcalibrate-commerce-access-rules',
			false,
			dirname( plugin_basename( WPCALIBRATE_COMMERCE_ACCESS_RULES_FILE ) ) . '/languages'
		);
	}

	/**
	 * Plugin activation routine.
	 *
	 * @return void
	 */
	public static function activate(): void {
		// Verify minimum PHP version.
		if ( version_compare( PHP_VERSION, WPCALIBRATE_COMMERCE_ACCESS_RULES_MIN_PHP, '<' ) ) {
			deactivate_plugins( plugin_basename( WPCALIBRATE_COMMERCE_ACCESS_RULES_FILE ) );
			wp_die(
				sprintf(
					/* translators: 1: Required PHP version, 2: Current PHP version */
					esc_html__( 'WPCalibrate Content Restriction requires PHP version %1$s or higher. Your server is running PHP %2$s.', 'wpcalibrate-commerce-access-rules' ),
					esc_html( WPCALIBRATE_COMMERCE_ACCESS_RULES_MIN_PHP ),
					esc_html( PHP_VERSION )
				)
			);
		}

		// Register capabilities.
		Capabilities::register_capabilities();

		// Register post type before any rewrite rule handling.
		RuleRepository::register_post_type();

		// Run migrations.
		MigrationManager::run();
	}

	/**
	 * Plugin deactivation routine.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		// Clear transients.
		delete_transient( 'wpcal_global_cache_ver' );
	}

	// Service getters.

	public function get_rule_repository(): RuleRepository {
		return $this->rule_repo;
	}

	public function get_purchase_evaluator(): PurchaseEvaluator {
		return $this->purchase_evaluator;
	}

	public function get_access_evaluator(): AccessEvaluator {
		return $this->evaluator;
	}

	public function get_cache(): AccessCache {
		return $this->cache;
	}

	public function get_license_service(): LicenseServiceInterface {
		return $this->license_service;
	}
}
