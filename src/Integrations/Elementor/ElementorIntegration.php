<?php
/**
 * Optional Elementor Integration.
 *
 * @package WPCalibrate\CommerceAccessRules
 */

declare(strict_types=1);

namespace WPCalibrate\CommerceAccessRules\Integrations\Elementor;

use WPCalibrate\CommerceAccessRules\Capabilities;
use WPCalibrate\CommerceAccessRules\Domain\Access\AccessEvaluator;
use WPCalibrate\CommerceAccessRules\Domain\Rule\RuleRepository;

/**
 * ElementorIntegration class.
 */
final class ElementorIntegration {

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
	 * Register integration if Elementor is active.
	 *
	 * @return void
	 */
	public function register(): void {
		if ( ! did_action( 'elementor/loaded' ) && ! class_exists( '\Elementor\Plugin' ) ) {
			return;
		}

		// Register controls on sections, containers, and widgets.
		add_action( 'elementor/element/common/_section_style/after_section_end', array( $this, 'add_elementor_controls' ), 10, 2 );
		add_action( 'elementor/element/section/section_advanced/after_section_end', array( $this, 'add_elementor_controls' ), 10, 2 );
		add_action( 'elementor/element/container/section_layout/after_section_end', array( $this, 'add_elementor_controls' ), 10, 2 );

		// Server-side rendering enforcement.
		add_filter( 'elementor/frontend/element/should_render', array( $this, 'filter_element_rendering' ), 10, 2 );
		add_filter( 'elementor/frontend/section/should_render', array( $this, 'filter_element_rendering' ), 10, 2 );
		add_filter( 'elementor/frontend/widget/should_render', array( $this, 'filter_element_rendering' ), 10, 2 );
	}

	/**
	 * Add WPCalibrate restriction controls to Elementor elements.
	 *
	 * @param \Elementor\Element_Base $element Element instance.
	 * @param array<string, mixed>    $args    Section arguments.
	 * @return void
	 */
	public function add_elementor_controls( $element, $args ): void {
		if ( ! class_exists( '\Elementor\Controls_Manager' ) ) {
			return;
		}

		$element->start_controls_section(
			'wpcal_restriction_section',
			array(
				'label' => __( 'WPCalibrate Access Restriction', 'wpcalibrate-commerce-access-rules' ),
				'tab'   => \Elementor\Controls_Manager::TAB_ADVANCED,
			)
		);

		$element->add_control(
			'wpcal_enable_restriction',
			array(
				'label'        => __( 'Restrict this element', 'wpcalibrate-commerce-access-rules' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'wpcalibrate-commerce-access-rules' ),
				'label_off'    => __( 'No', 'wpcalibrate-commerce-access-rules' ),
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		// Get active rules for dropdown.
		$rules = $this->rule_repo->find( array( 'status' => 'active', 'per_page' => 100 ) );
		$options = array( '' => __( '-- Select a Rule --', 'wpcalibrate-commerce-access-rules' ) );
		foreach ( $rules as $rule ) {
			if ( $rule->get_id() ) {
				$options[ (string) $rule->get_id() ] = sprintf( '%s (#%d)', $rule->get_title(), $rule->get_id() );
			}
		}

		$element->add_control(
			'wpcal_selected_rule',
			array(
				'label'     => __( 'Restriction Rule', 'wpcalibrate-commerce-access-rules' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'options'   => $options,
				'default'   => '',
				'condition' => array(
					'wpcal_enable_restriction' => 'yes',
				),
			)
		);

		$element->end_controls_section();
	}

	/**
	 * Server-side filtering before Elementor element renders.
	 *
	 * @param bool                    $should_render Whether Elementor intends to render this element.
	 * @param \Elementor\Element_Base $element       Element instance.
	 * @return bool
	 */
	public function filter_element_rendering( bool $should_render, $element ): bool {
		if ( ! $should_render || ! is_object( $element ) || ! method_exists( $element, 'get_settings_for_display' ) ) {
			return $should_render;
		}

		$settings = $element->get_settings_for_display();
		if ( empty( $settings['wpcal_enable_restriction'] ) || 'yes' !== $settings['wpcal_enable_restriction'] ) {
			return $should_render;
		}

		$rule_id = isset( $settings['wpcal_selected_rule'] ) ? absint( $settings['wpcal_selected_rule'] ) : 0;
		if ( $rule_id <= 0 ) {
			return $should_render;
		}

		$rule = $this->rule_repo->get( $rule_id );
		if ( ! $rule || ! $rule->is_active() ) {
			return $should_render;
		}

		// Admin preview bypass.
		$user_id = (int) get_current_user_id();
		if ( $user_id > 0 && Capabilities::can_manage_rules( $user_id ) ) {
			return true;
		}

		$result = $this->evaluator->evaluate_rule( $user_id, $rule );
		if ( $result->is_allowed() ) {
			return true;
		}

		// Denied server-side! Do not render this element to unauthorized visitor.
		return false;
	}
}
