<?php
/**
 * Shortcode Content Restriction.
 *
 * @package WPCalibrate\CommerceAccessRules
 */

declare(strict_types=1);

namespace WPCalibrate\CommerceAccessRules\Content;

use WPCalibrate\CommerceAccessRules\Capabilities;
use WPCalibrate\CommerceAccessRules\Domain\Access\AccessEvaluator;
use WPCalibrate\CommerceAccessRules\Domain\Rule\AccessRule;
use WPCalibrate\CommerceAccessRules\Domain\Rule\RuleRepository;

/**
 * Shortcode class.
 */
final class Shortcode {

	public const TAG = 'wpcalibrate_restrict';

	private AccessEvaluator $evaluator;
	private RuleRepository $rule_repo;

	/**
	 * Recursion depth guard.
	 */
	private static int $depth = 0;
	private const MAX_DEPTH   = 5;

	/**
	 * Constructor.
	 *
	 * @param AccessEvaluator $evaluator
	 * @param RuleRepository  $rule_repo
	 */
	public function __construct( AccessEvaluator $evaluator, RuleRepository $rule_repo ) {
		$this->evaluator = $evaluator;
		$this->rule_repo = $rule_repo;
	}

	/**
	 * Register the shortcode.
	 *
	 * @return void
	 */
	public function register(): void {
		add_shortcode( self::TAG, array( $this, 'render' ) );
	}

	/**
	 * Render shortcode callback.
	 *
	 * @param array<string, mixed>|string $atts    Shortcode attributes.
	 * @param string|null                  $content Enclosed content.
	 * @return string Rendered output.
	 */
	public function render( $atts, ?string $content = null ): string {
		if ( null === $content || '' === trim( $content ) ) {
			return '';
		}

		// Prevent infinite recursion.
		if ( self::$depth >= self::MAX_DEPTH ) {
			return '';
		}

		$parsed = shortcode_atts(
			array(
				'rule'    => 0,
				'action'  => '',
				'message' => '',
			),
			$atts,
			self::TAG
		);

		$rule_id = absint( $parsed['rule'] );
		if ( $rule_id <= 0 ) {
			// If no rule specified, render enclosed content.
			return do_shortcode( $content );
		}

		$rule = $this->rule_repo->get( $rule_id );
		if ( ! $rule || ! $rule->is_active() ) {
			// Inactive rule fails open or fails according to config. Disabled rule does not restrict.
			return do_shortcode( $content );
		}

		// Check admin preview bypass.
		$current_user_id = (int) get_current_user_id();
		if ( $current_user_id > 0 && Capabilities::can_manage_rules( $current_user_id ) ) {
			self::$depth++;
			$out = do_shortcode( $content );
			self::$depth--;
			return $out;
		}

		$result = $this->evaluator->evaluate_rule( $current_user_id, $rule );
		if ( $result->is_allowed() ) {
			self::$depth++;
			$out = do_shortcode( $content );
			self::$depth--;
			return $out;
		}

		// Denied. Determine denial output.
		$action = ! empty( $parsed['action'] ) ? sanitize_key( $parsed['action'] ) : $result->get_denied_action();

		if ( AccessRule::ACTION_HIDE === $action ) {
			return '';
		}

		$message = ! empty( $parsed['message'] ) ? wp_kses_post( $parsed['message'] ) : $result->get_custom_message();
		if ( empty( $message ) ) {
			$message = '<div class="wpcal-inline-restricted-notice"><p>' . esc_html__( 'This content is restricted.', 'wpcalibrate-commerce-access-rules' ) . '</p></div>';
		}

		$output = '<div class="wpcal-inline-restriction">';
		$output .= wp_kses_post( $message );

		if ( AccessRule::ACTION_PURCHASE_BUTTON === $action && $result->get_qualifying_product_id() ) {
			$prod_id  = $result->get_qualifying_product_id();
			$cart_url = get_permalink( $prod_id );
			$label    = $result->get_purchase_button_text() ?: __( 'Buy Now to Unlock Access', 'wpcalibrate-commerce-access-rules' );
			$output  .= '<div class="wpcal-inline-purchase"><a href="' . esc_url( $cart_url ) . '" class="button">' . esc_html( $label ) . '</a></div>';
		}

		$output .= '</div>';
		return $output;
	}
}
