<?php
/**
 * Gutenberg Block Restriction Handler.
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
 * BlockRestriction class.
 */
final class BlockRestriction {

	public const BLOCK_NAME = 'wpcalibrate/content-restriction';

	private AccessEvaluator $evaluator;
	private RuleRepository $rule_repo;

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
	 * Register Gutenberg block.
	 *
	 * @return void
	 */
	public function register(): void {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type(
			self::BLOCK_NAME,
			array(
				'attributes'      => array(
					'ruleId'        => array(
						'type'    => 'number',
						'default' => 0,
					),
					'action'        => array(
						'type'    => 'string',
						'default' => '',
					),
					'customMessage' => array(
						'type'    => 'string',
						'default' => '',
					),
				),
				'render_callback' => array( $this, 'render_block' ),
			)
		);
	}

	/**
	 * Server-side render callback for Gutenberg restriction block.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @param string               $content    Inner blocks rendered content.
	 * @return string Filtered content.
	 */
	public function render_block( array $attributes, string $content ): string {
		$rule_id = isset( $attributes['ruleId'] ) ? absint( $attributes['ruleId'] ) : 0;
		if ( $rule_id <= 0 ) {
			return $content;
		}

		$rule = $this->rule_repo->get( $rule_id );
		if ( ! $rule || ! $rule->is_active() ) {
			return $content;
		}

		// Admin preview bypass.
		$user_id = (int) get_current_user_id();
		if ( $user_id > 0 && Capabilities::can_manage_rules( $user_id ) ) {
			return $content;
		}

		$result = $this->evaluator->evaluate_rule( $user_id, $rule );
		if ( $result->is_allowed() ) {
			return $content;
		}

		$action = ! empty( $attributes['action'] ) ? sanitize_key( $attributes['action'] ) : $result->get_denied_action();

		if ( AccessRule::ACTION_HIDE === $action ) {
			return '';
		}

		$message = ! empty( $attributes['customMessage'] ) ? wp_kses_post( $attributes['customMessage'] ) : $result->get_custom_message();
		if ( empty( $message ) ) {
			$message = '<div class="wpcal-block-restricted"><p>' . esc_html__( 'This content is restricted.', 'wpcalibrate-commerce-access-rules' ) . '</p></div>';
		}

		$output = '<div class="wpcal-block-restriction-wrapper">';
		$output .= wp_kses_post( $message );

		if ( AccessRule::ACTION_PURCHASE_BUTTON === $action && $result->get_qualifying_product_id() ) {
			$prod_id  = $result->get_qualifying_product_id();
			$cart_url = get_permalink( $prod_id );
			$label    = $result->get_purchase_button_text() ?: __( 'Buy Now to Unlock Access', 'wpcalibrate-commerce-access-rules' );
			$output  .= '<div class="wpcal-block-purchase"><a href="' . esc_url( $cart_url ) . '" class="button">' . esc_html( $label ) . '</a></div>';
		}

		$output .= '</div>';
		return $output;
	}
}
