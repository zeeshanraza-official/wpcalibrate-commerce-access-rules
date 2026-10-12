<?php
/**
 * REST API Controller for Admin Ajax and Modals.
 *
 * @package WPCalibrate\CommerceAccessRules
 */

declare(strict_types=1);

namespace WPCalibrate\CommerceAccessRules\REST;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WPCalibrate\CommerceAccessRules\Capabilities;
use WPCalibrate\CommerceAccessRules\Domain\Access\AccessEvaluator;
use WPCalibrate\CommerceAccessRules\Domain\Rule\RuleRepository;
use WPCalibrate\CommerceAccessRules\WooCommerce\ProductResolver;

/**
 * RestController class.
 */
final class RestController {

	public const NAMESPACE = 'wpcalibrate-commerce-access-rules/v1';

	private RuleRepository $rule_repo;
	private AccessEvaluator $evaluator;

	/**
	 * Constructor.
	 *
	 * @param RuleRepository  $rule_repo
	 * @param AccessEvaluator $evaluator
	 */
	public function __construct( RuleRepository $rule_repo, AccessEvaluator $evaluator ) {
		$this->rule_repo = $rule_repo;
		$this->evaluator = $evaluator;
	}

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/products',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_products' ),
				'permission_callback' => array( $this, 'check_manage_permission' ),
				'args'                => array(
					'search' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'default'           => '',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/categories',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_categories' ),
				'permission_callback' => array( $this, 'check_manage_permission' ),
				'args'                => array(
					'search' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'default'           => '',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/users',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_users' ),
				'permission_callback' => array( $this, 'check_inspector_permission' ),
				'args'                => array(
					'search' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'default'           => '',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/inspect',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'inspect_customer' ),
				'permission_callback' => array( $this, 'check_inspector_permission' ),
				'args'                => array(
					'user_id' => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	/**
	 * Permission callback for rule management endpoints.
	 *
	 * @return bool
	 */
	public function check_manage_permission(): bool {
		return Capabilities::can_manage_rules();
	}

	/**
	 * Permission callback for inspector endpoints.
	 *
	 * @return bool
	 */
	public function check_inspector_permission(): bool {
		return Capabilities::can_view_inspector() || Capabilities::can_manage_rules();
	}

	/**
	 * Search WooCommerce products.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function get_products( WP_REST_Request $request ): WP_REST_Response {
		$search = (string) $request->get_param( 'search' );
		$items  = ProductResolver::search_products( $search, 30 );
		return new WP_REST_Response( array( 'items' => $items ), 200 );
	}

	/**
	 * Search product categories.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function get_categories( WP_REST_Request $request ): WP_REST_Response {
		$search = (string) $request->get_param( 'search' );
		$items  = ProductResolver::search_categories( $search );
		return new WP_REST_Response( array( 'items' => $items ), 200 );
	}

	/**
	 * Search users for rule condition or access inspector.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function get_users( WP_REST_Request $request ): WP_REST_Response {
		$search = (string) $request->get_param( 'search' );
		$args   = array(
			'number' => 20,
			'fields' => array( 'ID', 'display_name', 'user_email' ),
		);

		if ( ! empty( $search ) ) {
			$args['search']         = '*' . $search . '*';
			$args['search_columns'] = array( 'user_login', 'user_email', 'display_name' );
		}

		$user_query = new \WP_User_Query( $args );
		$results    = array();

		foreach ( $user_query->get_results() as $u ) {
			$results[] = array(
				'id'    => (int) $u->ID,
				'text'  => sprintf( '%s (%s) [ID: %d]', $u->display_name, $u->user_email, $u->ID ),
				'email' => $u->user_email,
			);
		}

		return new WP_REST_Response( array( 'items' => $results ), 200 );
	}

	/**
	 * Inspect customer access across all active rules.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function inspect_customer( WP_REST_Request $request ): WP_REST_Response {
		$user_id = (int) $request->get_param( 'user_id' );
		$user    = get_userdata( $user_id );

		if ( ! $user ) {
			return new WP_REST_Response( array( 'message' => __( 'Customer not found.', 'wpcalibrate-commerce-access-rules' ) ), 404 );
		}

		$all_rules = $this->rule_repo->find( array( 'per_page' => 100 ) );
		$reports   = array();

		foreach ( $all_rules as $rule ) {
			$result = $this->evaluator->evaluate_rule( $user_id, $rule );
			$order_edit_url = null;

			// If granted by order, provide link to edit order for authorized admin.
			if ( $result->is_allowed() && $result->get_grant_source() && str_starts_with( $result->get_grant_source(), 'order_' ) ) {
				$order_id = absint( substr( $result->get_grant_source(), 6 ) );
				if ( $order_id > 0 && current_user_can( 'edit_shop_orders' ) ) {
					$order_edit_url = admin_url( 'post.php?post=' . $order_id . '&action=edit' );
					// Check HPOS URL if applicable.
					if ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' )
						&& method_exists( '\Automattic\WooCommerce\Utilities\OrderUtil', 'custom_orders_table_usage_is_enabled' )
						&& \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ) {
						$order_edit_url = admin_url( 'admin.php?page=wc-orders&action=edit&id=' . $order_id );
					}
				}
			}

			$reports[] = array(
				'rule_id'          => $rule->get_id(),
				'rule_title'       => $rule->get_title(),
				'status'           => $rule->get_status(),
				'summary'          => $rule->get_access_summary(),
				'result'           => $result->to_array(),
				'order_edit_url'   => $order_edit_url,
				'assignment_count' => $this->rule_repo->count_assignments( (int) $rule->get_id() ),
			);
		}

		return new WP_REST_Response(
			array(
				'user' => array(
					'id'           => $user->ID,
					'name'         => $user->display_name,
					'email'        => $user->user_email,
					'roles'        => $user->roles,
					'registered'   => $user->user_registered,
				),
				'rules' => $reports,
			),
			200
		);
	}
}
