<?php
/**
 * Demo Restriction Rules Seeder.
 *
 * @package WPCalibrate\CommerceAccessRules
 */

declare(strict_types=1);

namespace WPCalibrate\CommerceAccessRules\Domain\Rule;

/**
 * DemoDataSeeder class.
 */
final class DemoDataSeeder {

	private RuleRepository $rule_repo;

	/**
	 * Constructor.
	 *
	 * @param RuleRepository $rule_repo
	 */
	public function __construct( RuleRepository $rule_repo ) {
		$this->rule_repo = $rule_repo;
	}

	/**
	 * Seed standard demo restriction rules into the database.
	 *
	 * @param bool $force Force seeding even if rules already exist.
	 * @return int Number of rules created.
	 */
	public function seed_demo_rules( bool $force = false ): int {
		// Do not duplicate if rules already exist, unless forced.
		if ( ! $force && $this->rule_repo->count() > 0 ) {
			return 0;
		}

		$created_count = 0;

		// 1. Detect any published WooCommerce product to link.
		$sample_product_id = $this->find_sample_product_id();

		// Rule 1: VIP Club Members Access (Roles + Login).
		$rule1 = new AccessRule();
		$rule1->set_title( __( 'VIP Club Members - Exclusive Content', 'wpcalibrate-commerce-access-rules' ) );
		$rule1->set_status( AccessRule::STATUS_ACTIVE );
		$rule1->set_priority( 20 );
		$rule1->set_groups_relation( 'AND' );
		$rule1->set_condition_groups(
			array(
				array(
					'relation'   => 'AND',
					'conditions' => array(
						array( 'type' => 'logged_in', 'value' => true ),
						array(
							'type'  => 'roles',
							'roles' => array( 'administrator', 'customer', 'subscriber' ),
						),
					),
				),
			)
		);
		$rule1->set_duration_type( AccessRule::DURATION_PERMANENT );
		$rule1->set_denied_action( AccessRule::ACTION_MESSAGE );
		$rule1->set_denied_message(
			__( '<h3>VIP Access Required</h3><p>This premium content is reserved exclusively for registered VIP Club Members. Please log in or subscribe to gain access.</p>', 'wpcalibrate-commerce-access-rules' )
		);
		$rule1->set_purchase_button_text( __( 'Join VIP Club', 'wpcalibrate-commerce-access-rules' ) );
		$this->rule_repo->save( $rule1 );
		$created_count++;

		// Rule 2: Premium Product Buyer Access (Commerce Purchase + 30 Days Expiry).
		$rule2 = new AccessRule();
		$rule2->set_title( __( 'Purchased Product Access (Digital Course / Ebook)', 'wpcalibrate-commerce-access-rules' ) );
		$rule2->set_status( AccessRule::STATUS_ACTIVE );
		$rule2->set_priority( 15 );
		$rule2->set_groups_relation( 'AND' );
		$rule2->set_condition_groups(
			array(
				array(
					'relation'   => 'AND',
					'conditions' => array(
						array( 'type' => 'logged_in', 'value' => true ),
						array(
							'type'     => 'wc_product',
							'products' => array( $sample_product_id ),
						),
					),
				),
			)
		);
		$rule2->set_duration_type( AccessRule::DURATION_DAYS );
		$rule2->set_duration_value( 30 );
		$rule2->set_qualifying_order_statuses( array( 'wc-completed', 'wc-processing' ) );
		$rule2->set_denied_action( AccessRule::ACTION_PURCHASE_BUTTON );
		$rule2->set_redirect_product_id( $sample_product_id );
		$rule2->set_denied_message(
			__( '<p>Access to this material is reserved for customers who purchased the digital course or ebook.</p>', 'wpcalibrate-commerce-access-rules' )
		);
		$rule2->set_purchase_button_text( __( 'Buy Product to Unlock (30-Day Access)', 'wpcalibrate-commerce-access-rules' ) );
		$this->rule_repo->save( $rule2 );
		$created_count++;

		// Rule 3: Registered Members Only (Login Redirect).
		$rule3 = new AccessRule();
		$rule3->set_title( __( 'Registered Members Only (Free Content)', 'wpcalibrate-commerce-access-rules' ) );
		$rule3->set_status( AccessRule::STATUS_ACTIVE );
		$rule3->set_priority( 10 );
		$rule3->set_groups_relation( 'AND' );
		$rule3->set_condition_groups(
			array(
				array(
					'relation'   => 'AND',
					'conditions' => array(
						array( 'type' => 'logged_in', 'value' => true ),
					),
				),
			)
		);
		$rule3->set_duration_type( AccessRule::DURATION_PERMANENT );
		$rule3->set_denied_action( AccessRule::ACTION_LOGIN_REDIRECT );
		$rule3->set_denied_message(
			__( '<p>Please log in to your account to read this member guide.</p>', 'wpcalibrate-commerce-access-rules' )
		);
		$this->rule_repo->save( $rule3 );
		$created_count++;

		// Rule 4: Seasonal Early Access Window (Fixed Dates, Inactive draft for scheduling).
		$now_year = (int) gmdate( 'Y' );
		$rule4    = new AccessRule();
		$rule4->set_title( __( 'Early Access Promotion Window (Seasonal)', 'wpcalibrate-commerce-access-rules' ) );
		$rule4->set_status( AccessRule::STATUS_INACTIVE );
		$rule4->set_priority( 5 );
		$rule4->set_groups_relation( 'AND' );
		$rule4->set_condition_groups(
			array(
				array(
					'relation'   => 'AND',
					'conditions' => array(
						array( 'type' => 'logged_in', 'value' => true ),
						array(
							'type'  => 'fixed_date',
							'start' => "{$now_year}-10-01T00:00",
							'end'   => "{$now_year}-12-31T23:59",
						),
					),
				),
			)
		);
		$rule4->set_duration_type( AccessRule::DURATION_FIXED );
		$rule4->set_fixed_start( "{$now_year}-10-01T00:00" );
		$rule4->set_fixed_end( "{$now_year}-12-31T23:59" );
		$rule4->set_denied_action( AccessRule::ACTION_MESSAGE );
		$rule4->set_denied_message(
			__( '<p>This promotional material is currently unavailable outside the designated campaign window.</p>', 'wpcalibrate-commerce-access-rules' )
		);
		$this->rule_repo->save( $rule4 );
		$created_count++;

		return $created_count;
	}

	/**
	 * Find a sample WooCommerce product ID to link, or fallback to default.
	 *
	 * @return int
	 */
	private function find_sample_product_id(): int {
		if ( function_exists( 'wc_get_products' ) ) {
			$products = wc_get_products(
				array(
					'limit'  => 1,
					'status' => 'publish',
					'return' => 'ids',
				)
			);
			if ( ! empty( $products ) && is_array( $products ) ) {
				return (int) reset( $products );
			}
		}

		// Fallback to WP query if wc_get_products is not available.
		if ( function_exists( 'get_posts' ) ) {
			$posts = get_posts(
				array(
					'post_type'      => 'product',
					'post_status'    => 'publish',
					'posts_per_page' => 1,
					'fields'         => 'ids',
				)
			);
			if ( ! empty( $posts ) && is_array( $posts ) ) {
				return (int) reset( $posts );
			}
		}

		return 18; // Default sample product ID.
	}
}
