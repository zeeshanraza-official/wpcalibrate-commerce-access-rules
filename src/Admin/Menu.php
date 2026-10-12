<?php
/**
 * Admin Menu and Routing Controller.
 *
 * @package WPCalibrate\CommerceAccessRules
 */

declare(strict_types=1);

namespace WPCalibrate\CommerceAccessRules\Admin;

use WPCalibrate\CommerceAccessRules\Capabilities;
use WPCalibrate\CommerceAccessRules\Domain\Rule\AccessRule;
use WPCalibrate\CommerceAccessRules\Domain\Rule\DemoDataSeeder;
use WPCalibrate\CommerceAccessRules\Domain\Rule\RuleRepository;
use WPCalibrate\CommerceAccessRules\Licensing\LicenseServiceInterface;

/**
 * Menu class.
 */
final class Menu {

	public const PARENT_SLUG = 'wpcalibrate';
	public const PAGE_SLUG   = 'wpcalibrate-access-rules';

	private RuleRepository $rule_repo;
	private LicenseServiceInterface $license_service;

	/**
	 * Constructor.
	 *
	 * @param RuleRepository          $rule_repo
	 * @param LicenseServiceInterface $license_service
	 */
	public function __construct( RuleRepository $rule_repo, LicenseServiceInterface $license_service ) {
		$this->rule_repo       = $rule_repo;
		$this->license_service = $license_service;
	}

	/**
	 * Register admin menu hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'register_admin_menus' ), 9 );
		add_action( 'admin_init', array( $this, 'handle_form_actions' ) );
		add_action( 'admin_head', array( $this, 'render_admin_menu_styles' ) );
	}

	/**
	 * Register shared top-level menu and plugin submenu.
	 *
	 * @return void
	 */
	public function register_admin_menus(): void {
		global $menu;

		// 1. Check if shared WPCalibrate parent menu already exists in $menu.
		$parent_exists = false;
		if ( is_array( $menu ) ) {
			foreach ( $menu as $item ) {
				if ( isset( $item[2] ) && self::PARENT_SLUG === $item[2] ) {
					$parent_exists = true;
					break;
				}
			}
		}

		// 2. Register parent menu only if another WPCalibrate plugin has not registered it.
		if ( ! $parent_exists ) {
			// Select brand icon based on admin contrast.
			$icon_path = WPCALIBRATE_COMMERCE_ACCESS_RULES_DIR . 'branding/icon-white.png';
			$icon_url  = file_exists( $icon_path )
				? WPCALIBRATE_COMMERCE_ACCESS_RULES_URL . 'branding/icon-white.png'
				: 'dashicons-lock';

			add_menu_page(
				'WPCalibrate',
				'WPCalibrate',
				Capabilities::MANAGE_RULES,
				self::PARENT_SLUG,
				array( $this, 'render_parent_landing' ),
				$icon_url,
				58
			);
		}

		// 3. Register this plugin's isolated submenu.
		add_submenu_page(
			self::PARENT_SLUG,
			__( 'Content Restriction', 'wpcalibrate-commerce-access-rules' ),
			__( 'Content Restriction', 'wpcalibrate-commerce-access-rules' ),
			Capabilities::MANAGE_RULES,
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Fallback parent landing: Redirects to this plugin's main page if clicked directly.
	 *
	 * @return void
	 */
	public function render_parent_landing(): void {
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) );
		exit;
	}

	/**
	 * Output admin menu styles to ensure the WPCalibrate menu icon matches standard WordPress menu icons.
	 *
	 * Standard WordPress dashboard menu icons are 20x20px with color #a7aaad (inactive) and #ffffff (active/hover).
	 *
	 * @return void
	 */
	public function render_admin_menu_styles(): void {
		?>
		<style id="wpcalibrate-menu-icon-css">
			#adminmenu .toplevel_page_wpcalibrate div.wp-menu-image img,
			#adminmenu .toplevel_page_wpcalibrate .wp-menu-image img {
				width: 20px !important;
				height: 20px !important;
				max-width: 20px !important;
				max-height: 20px !important;
				padding: 7px 0 0 0 !important;
				box-sizing: content-box !important;
				object-fit: contain !important;
				opacity: 0.85 !important;
				filter: brightness(0) invert(0.67) !important;
				transition: filter 0.15s ease, opacity 0.15s ease;
			}
			#adminmenu .toplevel_page_wpcalibrate:hover div.wp-menu-image img,
			#adminmenu .toplevel_page_wpcalibrate.opensub div.wp-menu-image img,
			#adminmenu .toplevel_page_wpcalibrate.wp-has-current-submenu div.wp-menu-image img,
			#adminmenu .toplevel_page_wpcalibrate.current div.wp-menu-image img {
				opacity: 1 !important;
				filter: brightness(0) invert(1) !important;
			}
		</style>
		<?php
	}

	/**
	 * Handle form actions (save rule, toggle status, delete, duplicate, settings, license).
	 *
	 * @return void
	 */
	public function handle_form_actions(): void {
		$page = isset( $_REQUEST['page'] ) ? sanitize_key( wp_unslash( $_REQUEST['page'] ) ) : '';
		if ( self::PAGE_SLUG !== $page && self::PARENT_SLUG !== $page ) {
			return;
		}

		$action = isset( $_REQUEST['wpcal_action'] ) ? sanitize_key( wp_unslash( $_REQUEST['wpcal_action'] ) ) : '';
		if ( empty( $action ) ) {
			return;
		}

		switch ( $action ) {
			case 'save_rule':
				$this->process_save_rule();
				break;

			case 'delete_rule':
				$this->process_delete_rule();
				break;

			case 'duplicate_rule':
				$this->process_duplicate_rule();
				break;

			case 'toggle_status':
				$this->process_toggle_status();
				break;

			case 'save_settings':
				$this->process_save_settings();
				break;

			case 'activate_license':
				$this->process_activate_license();
				break;

			case 'deactivate_license':
				$this->process_deactivate_license();
				break;

			case 'load_demo_rules':
				$this->process_load_demo_rules();
				break;
		}
	}

	/**
	 * Process saving a restriction rule.
	 *
	 * @return void
	 */
	private function process_save_rule(): void {
		check_admin_referer( 'wpcal_save_rule', 'wpcal_nonce' );

		if ( ! Capabilities::can_manage_rules() ) {
			wp_die( esc_html__( 'Unauthorized', 'wpcalibrate-commerce-access-rules' ), 403 );
		}

		$rule_id = isset( $_POST['rule_id'] ) ? absint( $_POST['rule_id'] ) : 0;
		$title   = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		if ( empty( $title ) ) {
			$title = __( 'Untitled Rule', 'wpcalibrate-commerce-access-rules' );
		}

		$status   = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : AccessRule::STATUS_ACTIVE;
		$priority = isset( $_POST['priority'] ) ? (int) $_POST['priority'] : 10;

		$groups_relation = isset( $_POST['groups_relation'] ) && strtoupper( sanitize_text_field( wp_unslash( $_POST['groups_relation'] ) ) ) === 'OR' ? 'OR' : 'AND';

		// Parse raw condition groups from form.
		$condition_groups = array();
		if ( isset( $_POST['condition_groups'] ) && is_array( $_POST['condition_groups'] ) ) {
			foreach ( $_POST['condition_groups'] as $group_data ) {
				if ( ! is_array( $group_data ) ) {
					continue;
				}
				$group_rel = isset( $group_data['relation'] ) && strtoupper( sanitize_text_field( wp_unslash( $group_data['relation'] ) ) ) === 'OR' ? 'OR' : 'AND';
				$conditions = array();

				if ( isset( $group_data['conditions'] ) && is_array( $group_data['conditions'] ) ) {
					foreach ( $group_data['conditions'] as $cond_data ) {
						if ( ! is_array( $cond_data ) || empty( $cond_data['type'] ) ) {
							continue;
						}
						$type = sanitize_key( wp_unslash( $cond_data['type'] ) );
						$cond = array( 'type' => $type );

						if ( in_array( $type, array( 'logged_in', 'logged_out' ), true ) ) {
							$cond['value'] = true;
						} elseif ( 'roles' === $type && isset( $cond_data['roles'] ) && is_array( $cond_data['roles'] ) ) {
							$cond['roles'] = array_map( 'sanitize_key', $cond_data['roles'] );
						} elseif ( 'users' === $type && ! empty( $cond_data['users'] ) ) {
							$users_arr = is_array( $cond_data['users'] ) ? $cond_data['users'] : explode( ',', (string) $cond_data['users'] );
							$cond['users'] = array_filter( array_map( 'absint', $users_arr ) );
						} elseif ( ( 'wc_product' === $type || 'wc_subscription' === $type ) && ! empty( $cond_data['products'] ) ) {
							$prods_arr = is_array( $cond_data['products'] ) ? $cond_data['products'] : explode( ',', (string) $cond_data['products'] );
							$cond['products'] = array_filter( array_map( 'absint', $prods_arr ) );
						} elseif ( 'wc_category' === $type && ! empty( $cond_data['categories'] ) ) {
							$cats_arr = is_array( $cond_data['categories'] ) ? $cond_data['categories'] : explode( ',', (string) $cond_data['categories'] );
							$cond['categories'] = array_filter( array_map( 'absint', $cats_arr ) );
						} elseif ( 'fixed_date' === $type ) {
							$cond['start'] = sanitize_text_field( wp_unslash( $cond_data['start'] ?? '' ) );
							$cond['end']   = sanitize_text_field( wp_unslash( $cond_data['end'] ?? '' ) );
						}

						$conditions[] = $cond;
					}
				}

				if ( ! empty( $conditions ) ) {
					$condition_groups[] = array(
						'relation'   => $group_rel,
						'conditions' => $conditions,
					);
				}
			}
		}

		// Duration.
		$duration_type  = isset( $_POST['duration_type'] ) ? sanitize_key( wp_unslash( $_POST['duration_type'] ) ) : AccessRule::DURATION_PERMANENT;
		$duration_value = isset( $_POST['duration_value'] ) ? max( 0, (int) $_POST['duration_value'] ) : 0;
		$fixed_start    = isset( $_POST['fixed_start'] ) ? sanitize_text_field( wp_unslash( $_POST['fixed_start'] ) ) : null;
		$fixed_end      = isset( $_POST['fixed_end'] ) ? sanitize_text_field( wp_unslash( $_POST['fixed_end'] ) ) : null;

		// Denied action.
		$denied_action         = isset( $_POST['denied_action'] ) ? sanitize_key( wp_unslash( $_POST['denied_action'] ) ) : AccessRule::ACTION_MESSAGE;
		$denied_message        = isset( $_POST['denied_message'] ) ? wp_kses_post( wp_unslash( $_POST['denied_message'] ) ) : '';
		$redirect_page_id      = isset( $_POST['redirect_page_id'] ) ? absint( $_POST['redirect_page_id'] ) : null;
		$redirect_product_id   = isset( $_POST['redirect_product_id'] ) ? absint( $_POST['redirect_product_id'] ) : null;
		$redirect_external_url = isset( $_POST['redirect_external_url'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_external_url'] ) ) : null;
		$purchase_button_text  = isset( $_POST['purchase_button_text'] ) ? sanitize_text_field( wp_unslash( $_POST['purchase_button_text'] ) ) : '';

		// Qualifying order statuses for this rule.
		$order_statuses = isset( $_POST['qualifying_order_statuses'] ) && is_array( $_POST['qualifying_order_statuses'] )
			? array_map( 'sanitize_key', $_POST['qualifying_order_statuses'] )
			: array();

		$rule = $rule_id > 0 ? $this->rule_repo->get( $rule_id ) : new AccessRule();
		if ( ! $rule ) {
			$rule = new AccessRule();
		}

		$rule->set_title( $title );
		$rule->set_status( $status );
		$rule->set_priority( $priority );
		$rule->set_groups_relation( $groups_relation );
		$rule->set_condition_groups( $condition_groups );
		$rule->set_duration_type( $duration_type );
		$rule->set_duration_value( $duration_value );
		$rule->set_fixed_start( $fixed_start );
		$rule->set_fixed_end( $fixed_end );
		$rule->set_denied_action( $denied_action );
		$rule->set_denied_message( $denied_message );
		$rule->set_redirect_page_id( $redirect_page_id );
		$rule->set_redirect_product_id( $redirect_product_id );
		$rule->set_redirect_external_url( $redirect_external_url );
		$rule->set_purchase_button_text( $purchase_button_text );
		$rule->set_qualifying_order_statuses( $order_statuses );

		try {
			$saved_id = $this->rule_repo->save( $rule );
			Notices::add_flash_notice( __( 'Restriction rule saved successfully.', 'wpcalibrate-commerce-access-rules' ), 'success' );
			wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=rules&action=edit&rule_id=' . $saved_id ) );
			exit;
		} catch ( \Exception $e ) {
			Notices::add_flash_notice( sprintf( __( 'Error saving rule: %s', 'wpcalibrate-commerce-access-rules' ), $e->getMessage() ), 'error' );
		}
	}

	/**
	 * Process rule deletion.
	 *
	 * @return void
	 */
	private function process_delete_rule(): void {
		$rule_id = isset( $_GET['rule_id'] ) ? absint( $_GET['rule_id'] ) : 0;
		check_admin_referer( 'wpcal_delete_rule_' . $rule_id );

		if ( ! Capabilities::can_manage_rules() ) {
			wp_die( esc_html__( 'Unauthorized', 'wpcalibrate-commerce-access-rules' ), 403 );
		}

		if ( $this->rule_repo->delete( $rule_id ) ) {
			Notices::add_flash_notice( __( 'Restriction rule deleted successfully.', 'wpcalibrate-commerce-access-rules' ), 'success' );
		} else {
			Notices::add_flash_notice( __( 'Could not delete restriction rule.', 'wpcalibrate-commerce-access-rules' ), 'error' );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=rules' ) );
		exit;
	}

	/**
	 * Process rule duplication.
	 *
	 * @return void
	 */
	private function process_duplicate_rule(): void {
		$rule_id = isset( $_GET['rule_id'] ) ? absint( $_GET['rule_id'] ) : 0;
		check_admin_referer( 'wpcal_duplicate_rule_' . $rule_id );

		if ( ! Capabilities::can_manage_rules() ) {
			wp_die( esc_html__( 'Unauthorized', 'wpcalibrate-commerce-access-rules' ), 403 );
		}

		$new_id = $this->rule_repo->duplicate( $rule_id );
		if ( $new_id ) {
			Notices::add_flash_notice( __( 'Restriction rule duplicated successfully.', 'wpcalibrate-commerce-access-rules' ), 'success' );
			wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=rules&action=edit&rule_id=' . $new_id ) );
			exit;
		}

		Notices::add_flash_notice( __( 'Failed to duplicate rule.', 'wpcalibrate-commerce-access-rules' ), 'error' );
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=rules' ) );
		exit;
	}

	/**
	 * Process status toggle (active / inactive).
	 *
	 * @return void
	 */
	private function process_toggle_status(): void {
		$rule_id    = isset( $_GET['rule_id'] ) ? absint( $_GET['rule_id'] ) : 0;
		$new_status = isset( $_GET['status'] ) && 'active' === $_GET['status'] ? 'active' : 'inactive';
		check_admin_referer( 'wpcal_toggle_status_' . $rule_id );

		if ( ! Capabilities::can_manage_rules() ) {
			wp_die( esc_html__( 'Unauthorized', 'wpcalibrate-commerce-access-rules' ), 403 );
		}

		if ( $this->rule_repo->set_status( $rule_id, $new_status ) ) {
			Notices::add_flash_notice( __( 'Rule status updated.', 'wpcalibrate-commerce-access-rules' ), 'success' );
		} else {
			Notices::add_flash_notice( __( 'Failed to update rule status.', 'wpcalibrate-commerce-access-rules' ), 'error' );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=rules' ) );
		exit;
	}

	/**
	 * Process saving plugin general settings.
	 *
	 * @return void
	 */
	private function process_save_settings(): void {
		check_admin_referer( 'wpcal_save_settings', 'wpcal_settings_nonce' );

		if ( ! Capabilities::can_manage_settings() ) {
			wp_die( esc_html__( 'Unauthorized', 'wpcalibrate-commerce-access-rules' ), 403 );
		}

		$order_statuses = isset( $_POST['wpcal_qualifying_order_statuses'] ) && is_array( $_POST['wpcal_qualifying_order_statuses'] )
			? array_map( 'sanitize_key', $_POST['wpcal_qualifying_order_statuses'] )
			: array( 'wc-completed', 'wc-processing' );

		$message = isset( $_POST['wpcal_default_restriction_message'] )
			? wp_kses_post( wp_unslash( $_POST['wpcal_default_restriction_message'] ) )
			: '';

		$multi_logic = isset( $_POST['wpcal_multiple_rules_logic'] ) && 'AND' === strtoupper( sanitize_text_field( wp_unslash( $_POST['wpcal_multiple_rules_logic'] ) ) )
			? 'AND'
			: 'OR';

		$hide_search    = ! empty( $_POST['wpcal_hide_from_search'] );
		$hide_archives  = ! empty( $_POST['wpcal_hide_from_archives'] );
		$enable_cache   = ! empty( $_POST['wpcal_enable_cache'] );
		$enable_logging = ! empty( $_POST['wpcal_enable_logging'] );
		$preserve_data  = ! empty( $_POST['wpcal_preserve_data_on_uninstall'] );

		update_option( 'wpcal_qualifying_order_statuses', $order_statuses );
		update_option( 'wpcal_default_restriction_message', $message );
		update_option( 'wpcal_multiple_rules_logic', $multi_logic );
		update_option( 'wpcal_hide_from_search', $hide_search );
		update_option( 'wpcal_hide_from_archives', $hide_archives );
		update_option( 'wpcal_enable_cache', $enable_cache );
		update_option( 'wpcal_enable_logging', $enable_logging );
		update_option( 'wpcal_preserve_data_on_uninstall', $preserve_data );

		Notices::add_flash_notice( __( 'Settings saved successfully.', 'wpcalibrate-commerce-access-rules' ), 'success' );
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=settings' ) );
		exit;
	}

	/**
	 * Process license activation.
	 *
	 * @return void
	 */
	private function process_activate_license(): void {
		check_admin_referer( 'wpcal_license_action', 'wpcal_license_nonce' );

		if ( ! Capabilities::can_manage_license() ) {
			wp_die( esc_html__( 'Unauthorized', 'wpcalibrate-commerce-access-rules' ), 403 );
		}

		$key    = isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) : '';
		$res    = $this->license_service->activate( $key );
		$status = $res['success'] ? 'success' : 'warning';

		Notices::add_flash_notice( $res['message'], $status );
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=license' ) );
		exit;
	}

	/**
	 * Process license deactivation.
	 *
	 * @return void
	 */
	private function process_deactivate_license(): void {
		check_admin_referer( 'wpcal_license_action', 'wpcal_license_nonce' );

		if ( ! Capabilities::can_manage_license() ) {
			wp_die( esc_html__( 'Unauthorized', 'wpcalibrate-commerce-access-rules' ), 403 );
		}

		$res = $this->license_service->deactivate();
		Notices::add_flash_notice( $res['message'], 'success' );
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=license' ) );
		exit;
	}

	/**
	 * Process manual demo rules loading.
	 *
	 * @return void
	 */
	private function process_load_demo_rules(): void {
		check_admin_referer( 'wpcal_load_demo_rules', 'wpcal_nonce' );

		if ( ! Capabilities::can_manage_rules() ) {
			wp_die( esc_html__( 'Unauthorized', 'wpcalibrate-commerce-access-rules' ), 403 );
		}

		$seeder = new DemoDataSeeder( $this->rule_repo );
		$count  = $seeder->seed_demo_rules( true );

		if ( $count > 0 ) {
			Notices::add_flash_notice(
				sprintf(
					/* translators: %d: number of rules */
					__( 'Successfully loaded %d demo restriction rules.', 'wpcalibrate-commerce-access-rules' ),
					$count
				),
				'success'
			);
		} else {
			Notices::add_flash_notice( __( 'Demo restriction rules already exist.', 'wpcalibrate-commerce-access-rules' ), 'info' );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=rules' ) );
		exit;
	}

	/**
	 * Render the main plugin admin interface.
	 *
	 * @return void
	 */
	public function render_page(): void {
		// Automatically populate demo rules if no rules currently exist.
		if ( 0 === $this->rule_repo->count() ) {
			$seeder = new DemoDataSeeder( $this->rule_repo );
			$seeder->seed_demo_rules( false );
		}

		$current_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'overview';
		$valid_tabs  = array( 'overview', 'rules', 'inspector', 'settings', 'integrations', 'license', 'support' );
		if ( ! in_array( $current_tab, $valid_tabs, true ) ) {
			$current_tab = 'overview';
		}

		$rule_repo       = $this->rule_repo;
		$license_service = $this->license_service;

		// Include admin layout template.
		require WPCALIBRATE_COMMERCE_ACCESS_RULES_DIR . 'templates/admin/layout.php';
	}
}
