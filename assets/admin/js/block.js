/**
 * Gutenberg Content Restriction Block
 *
 * @package WPCalibrate\CommerceAccessRules
 */

(function (wp) {
	'use strict';

	var registerBlockType = wp.blocks.registerBlockType;
	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var InspectorControls = wp.blockEditor.InspectorControls || wp.editor.InspectorControls;
	var InnerBlocks = wp.blockEditor.InnerBlocks || wp.editor.InnerBlocks;
	var PanelBody = wp.components.PanelBody;
	var TextControl = wp.components.TextControl;
	var SelectControl = wp.components.SelectControl;

	registerBlockType('wpcalibrate/content-restriction', {
		title: __('WPCalibrate Restriction', 'wpcalibrate-commerce-access-rules'),
		description: __('Restricts enclosed blocks based on WooCommerce purchases or customer access rules.', 'wpcalibrate-commerce-access-rules'),
		icon: 'lock',
		category: 'layout',
		attributes: {
			ruleId: {
				type: 'number',
				default: 0
			},
			action: {
				type: 'string',
				default: ''
			},
			customMessage: {
				type: 'string',
				default: ''
			}
		},

		edit: function (props) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;

			return el(
				wp.element.Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __('Restriction Settings', 'wpcalibrate-commerce-access-rules'), initialOpen: true },
						el(TextControl, {
							label: __('Restriction Rule ID', 'wpcalibrate-commerce-access-rules'),
							help: __('Enter the numerical ID of the central restriction rule.', 'wpcalibrate-commerce-access-rules'),
							type: 'number',
							value: attributes.ruleId || '',
							onChange: function (val) {
								setAttributes({ ruleId: parseInt(val, 10) || 0 });
							}
						}),
						el(SelectControl, {
							label: __('Denied Action Override', 'wpcalibrate-commerce-access-rules'),
							value: attributes.action,
							options: [
								{ label: __('-- Use Rule Default --', 'wpcalibrate-commerce-access-rules'), value: '' },
								{ label: __('Show Custom Message', 'wpcalibrate-commerce-access-rules'), value: 'message' },
								{ label: __('Show Purchase Button', 'wpcalibrate-commerce-access-rules'), value: 'purchase_button' },
								{ label: __('Hide Content Completely', 'wpcalibrate-commerce-access-rules'), value: 'hide' }
							],
							onChange: function (val) {
								setAttributes({ action: val });
							}
						}),
						el(TextControl, {
							label: __('Custom Denial Message Override', 'wpcalibrate-commerce-access-rules'),
							value: attributes.customMessage,
							onChange: function (val) {
								setAttributes({ customMessage: val });
							}
						})
					)
				),
				el(
					'div',
					{
						className: 'wpcal-editor-restriction-wrapper',
						style: {
							border: '2px dashed #2563eb',
							borderRadius: '6px',
							padding: '16px',
							background: 'rgba(37, 99, 235, 0.02)',
							margin: '12px 0'
						}
					},
					el(
						'div',
						{
							className: 'wpcal-editor-badge',
							style: {
								display: 'inline-flex',
								alignItems: 'center',
								gap: '6px',
								background: '#2563eb',
								color: '#ffffff',
								fontSize: '11px',
								fontWeight: '600',
								padding: '2px 8px',
								borderRadius: '4px',
								marginBottom: '12px',
								textTransform: 'uppercase'
							}
						},
						'🔒 ' + __('WPCalibrate Restricted Wrapper', 'wpcalibrate-commerce-access-rules') +
						(attributes.ruleId ? ' (Rule #' + attributes.ruleId + ')' : ' (' + __('No Rule Selected', 'wpcalibrate-commerce-access-rules') + ')')
					),
					el(InnerBlocks, null)
				)
			);
		},

		save: function () {
			// Content is rendered server-side in PHP via render_callback!
			return el(InnerBlocks.Content, null);
		}
	});
})(window.wp);
