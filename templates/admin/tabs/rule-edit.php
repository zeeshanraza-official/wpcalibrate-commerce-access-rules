<?php
/**
 * Rule Add / Edit Form Template.
 *
 * @package WPCalibrate\CommerceAccessRules
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @var \WPCalibrate\CommerceAccessRules\Domain\Rule\RuleRepository $rule_repo
 */

$rule_id = isset( $_GET['rule_id'] ) ? absint( $_GET['rule_id'] ) : 0;
$rule    = $rule_id > 0 ? $rule_repo->get( $rule_id ) : new \WPCalibrate\CommerceAccessRules\Domain\Rule\AccessRule();

if ( ! $rule ) {
	$rule = new \WPCalibrate\CommerceAccessRules\Domain\Rule\AccessRule();
}

$is_new           = ! $rule->get_id();
$condition_groups = $rule->get_condition_groups();
if ( empty( $condition_groups ) ) {
	// Provide default initial empty group.
	$condition_groups = array(
		array(
			'relation'   => 'AND',
			'conditions' => array(
				array( 'type' => 'logged_in', 'value' => true ),
			),
		),
	);
}

// Available WordPress user roles.
global $wp_roles;
$available_roles = isset( $wp_roles ) ? $wp_roles->get_names() : array();

// Available WC Order statuses.
$all_order_statuses = function_exists( 'wc_get_order_statuses' )
	? wc_get_order_statuses()
	: array(
		'wc-completed'  => __( 'Completed', 'wpcalibrate-commerce-access-rules' ),
		'wc-processing' => __( 'Processing', 'wpcalibrate-commerce-access-rules' ),
		'wc-on-hold'    => __( 'On hold', 'wpcalibrate-commerce-access-rules' ),
	);

$rule_order_statuses = $rule->get_qualifying_order_statuses();
?>

<div class="wpcal-rule-editor">
	<div class="wpcal-editor-header">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . \WPCalibrate\CommerceAccessRules\Admin\Menu::PAGE_SLUG . '&tab=rules' ) ); ?>" class="wpcal-back-link">
			← <?php esc_html_e( 'Back to All Rules', 'wpcalibrate-commerce-access-rules' ); ?>
		</a>
		<h2><?php echo $is_new ? esc_html__( 'Create New Restriction Rule', 'wpcalibrate-commerce-access-rules' ) : esc_html__( 'Edit Restriction Rule', 'wpcalibrate-commerce-access-rules' ); ?></h2>
	</div>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=' . \WPCalibrate\CommerceAccessRules\Admin\Menu::PAGE_SLUG . '&tab=rules' ) ); ?>" id="wpcal-rule-form" novalidate>
		<?php wp_nonce_field( 'wpcal_save_rule', 'wpcal_nonce' ); ?>
		<input type="hidden" name="page" value="<?php echo esc_attr( \WPCalibrate\CommerceAccessRules\Admin\Menu::PAGE_SLUG ); ?>" />
		<input type="hidden" name="tab" value="rules" />
		<input type="hidden" name="wpcal_action" value="save_rule" />
		<input type="hidden" name="rule_id" value="<?php echo esc_attr( (string) $rule->get_id() ); ?>" />

		<div class="wpcal-grid-editor">
			<!-- Main Column -->
			<div class="wpcal-editor-main">
				<div class="wpcal-card wpcal-form-section">
					<label for="wpcal-rule-title" class="wpcal-label">
						<strong><?php esc_html_e( 'Rule Title', 'wpcalibrate-commerce-access-rules' ); ?></strong>
						<span class="required">*</span>
					</label>
					<input type="text" id="wpcal-rule-title" name="title" value="<?php echo esc_attr( $rule->get_title() ); ?>" placeholder="<?php esc_attr_e( 'e.g. VIP Club Members or Premium Product Buyers', 'wpcalibrate-commerce-access-rules' ); ?>" class="widefat wpcal-input-lg" required />
				</div>

				<!-- Condition Groups Section -->
				<div class="wpcal-card wpcal-form-section">
					<div class="wpcal-section-header">
						<h3><?php esc_html_e( 'Access Conditions', 'wpcalibrate-commerce-access-rules' ); ?></h3>
						<p class="description">
							<?php esc_html_e( 'Configure the conditions required for a visitor to gain access. Group conditions with AND/OR logic.', 'wpcalibrate-commerce-access-rules' ); ?>
						</p>
					</div>

					<div class="wpcal-group-relation-selector" style="margin-bottom: 16px;">
						<label><strong><?php esc_html_e( 'Between Condition Groups:', 'wpcalibrate-commerce-access-rules' ); ?></strong></label>
						<label style="margin-left: 12px;">
							<input type="radio" name="groups_relation" value="AND" <?php checked( $rule->get_groups_relation(), 'AND' ); ?> />
							<?php esc_html_e( 'Require ALL Groups (AND)', 'wpcalibrate-commerce-access-rules' ); ?>
						</label>
						<label style="margin-left: 12px;">
							<input type="radio" name="groups_relation" value="OR" <?php checked( $rule->get_groups_relation(), 'OR' ); ?> />
							<?php esc_html_e( 'Require ANY Group (OR)', 'wpcalibrate-commerce-access-rules' ); ?>
						</label>
					</div>

					<div id="wpcal-condition-groups-container">
						<?php foreach ( $condition_groups as $g_idx => $group ) : ?>
							<?php
							$g_rel = isset( $group['relation'] ) && strtoupper( (string) $group['relation'] ) === 'OR' ? 'OR' : 'AND';
							$conditions = isset( $group['conditions'] ) && is_array( $group['conditions'] ) ? $group['conditions'] : array();
							?>
							<div class="wpcal-condition-group-card" data-group-index="<?php echo esc_attr( (string) $g_idx ); ?>">
								<div class="wpcal-group-header">
									<span class="wpcal-group-title"><?php printf( /* translators: %d: group index */ esc_html__( 'Condition Group #%d', 'wpcalibrate-commerce-access-rules' ), $g_idx + 1 ); ?></span>
									<div class="wpcal-group-relation">
										<label>
											<?php esc_html_e( 'Conditions logic:', 'wpcalibrate-commerce-access-rules' ); ?>
											<select name="condition_groups[<?php echo esc_attr( (string) $g_idx ); ?>][relation]">
												<option value="AND" <?php selected( $g_rel, 'AND' ); ?>><?php esc_html_e( 'Match ALL Conditions (AND)', 'wpcalibrate-commerce-access-rules' ); ?></option>
												<option value="OR" <?php selected( $g_rel, 'OR' ); ?>><?php esc_html_e( 'Match ANY Condition (OR)', 'wpcalibrate-commerce-access-rules' ); ?></option>
											</select>
										</label>
									</div>
									<button type="button" class="button-link wpcal-btn-remove-group" title="<?php esc_attr_e( 'Remove Group', 'wpcalibrate-commerce-access-rules' ); ?>">✕</button>
								</div>

								<div class="wpcal-conditions-list">
									<?php foreach ( $conditions as $c_idx => $cond ) : ?>
										<?php $c_type = $cond['type'] ?? 'logged_in'; ?>
										<div class="wpcal-condition-row" data-cond-index="<?php echo esc_attr( (string) $c_idx ); ?>">
											<div class="wpcal-cond-field-type">
												<select name="condition_groups[<?php echo esc_attr( (string) $g_idx ); ?>][conditions][<?php echo esc_attr( (string) $c_idx ); ?>][type]" class="wpcal-cond-type-select">
													<option value="logged_in" <?php selected( $c_type, 'logged_in' ); ?>><?php esc_html_e( 'User is Logged In', 'wpcalibrate-commerce-access-rules' ); ?></option>
													<option value="logged_out" <?php selected( $c_type, 'logged_out' ); ?>><?php esc_html_e( 'User is Logged Out (Inverse)', 'wpcalibrate-commerce-access-rules' ); ?></option>
													<option value="roles" <?php selected( $c_type, 'roles' ); ?>><?php esc_html_e( 'User has Specific Role(s)', 'wpcalibrate-commerce-access-rules' ); ?></option>
													<option value="users" <?php selected( $c_type, 'users' ); ?>><?php esc_html_e( 'Specific WordPress User(s)', 'wpcalibrate-commerce-access-rules' ); ?></option>
													<option value="wc_product" <?php selected( $c_type, 'wc_product' ); ?>><?php esc_html_e( 'Purchased WooCommerce Product(s)', 'wpcalibrate-commerce-access-rules' ); ?></option>
													<option value="wc_category" <?php selected( $c_type, 'wc_category' ); ?>><?php esc_html_e( 'Purchased in WooCommerce Category(ies)', 'wpcalibrate-commerce-access-rules' ); ?></option>
													<option value="wc_subscription" <?php selected( $c_type, 'wc_subscription' ); ?>><?php esc_html_e( 'Active WooCommerce Subscription', 'wpcalibrate-commerce-access-rules' ); ?></option>
													<option value="fixed_date" <?php selected( $c_type, 'fixed_date' ); ?>><?php esc_html_e( 'Fixed Date Window', 'wpcalibrate-commerce-access-rules' ); ?></option>
												</select>
											</div>

											<div class="wpcal-cond-params">
												<!-- Roles Selector -->
												<div class="wpcal-param-panel wpcal-param-roles" style="<?php echo 'roles' === $c_type ? '' : 'display:none;'; ?>">
													<select name="condition_groups[<?php echo esc_attr( (string) $g_idx ); ?>][conditions][<?php echo esc_attr( (string) $c_idx ); ?>][roles][]" multiple style="width: 100%; min-height: 80px;">
														<?php
														$sel_roles = (array) ( $cond['roles'] ?? array() );
														foreach ( $available_roles as $r_slug => $r_name ) :
														?>
															<option value="<?php echo esc_attr( $r_slug ); ?>" <?php echo in_array( $r_slug, $sel_roles, true ) ? 'selected' : ''; ?>>
																<?php echo esc_html( $r_name ); ?> (<?php echo esc_html( $r_slug ); ?>)
															</option>
														<?php endforeach; ?>
													</select>
													<span class="description" style="font-size: 11px;"><?php esc_html_e( 'Hold Ctrl/Cmd to select multiple roles.', 'wpcalibrate-commerce-access-rules' ); ?></span>
												</div>

												<!-- Specific Users Selector -->
												<div class="wpcal-param-panel wpcal-param-users" style="<?php echo 'users' === $c_type ? '' : 'display:none;'; ?>">
													<?php
													$user_ids = implode( ', ', array_map( 'absint', (array) ( $cond['users'] ?? array() ) ) );
													?>
													<input type="text" name="condition_groups[<?php echo esc_attr( (string) $g_idx ); ?>][conditions][<?php echo esc_attr( (string) $c_idx ); ?>][users]" value="<?php echo esc_attr( $user_ids ); ?>" placeholder="<?php esc_attr_e( 'Comma-separated User IDs, e.g. 1, 14, 25', 'wpcalibrate-commerce-access-rules' ); ?>" style="width: 100%;" />
												</div>

												<!-- Products Selector -->
												<div class="wpcal-param-panel wpcal-param-wc_product" style="<?php echo ( 'wc_product' === $c_type || 'wc_subscription' === $c_type ) ? '' : 'display:none;'; ?>">
													<?php
													$prod_ids = implode( ', ', array_map( 'absint', (array) ( $cond['products'] ?? array() ) ) );
													?>
													<input type="text" name="condition_groups[<?php echo esc_attr( (string) $g_idx ); ?>][conditions][<?php echo esc_attr( (string) $c_idx ); ?>][products]" value="<?php echo esc_attr( $prod_ids ); ?>" placeholder="<?php esc_attr_e( 'Comma-separated Product IDs, e.g. 101, 102', 'wpcalibrate-commerce-access-rules' ); ?>" style="width: 100%;" />
													<span class="description" style="font-size: 11px;"><?php esc_html_e( 'Enter WooCommerce product IDs that grant access upon purchase.', 'wpcalibrate-commerce-access-rules' ); ?></span>
												</div>

												<!-- Categories Selector -->
												<div class="wpcal-param-panel wpcal-param-wc_category" style="<?php echo 'wc_category' === $c_type ? '' : 'display:none;'; ?>">
													<?php
													$cat_ids = implode( ', ', array_map( 'absint', (array) ( $cond['categories'] ?? array() ) ) );
													?>
													<input type="text" name="condition_groups[<?php echo esc_attr( (string) $g_idx ); ?>][conditions][<?php echo esc_attr( (string) $c_idx ); ?>][categories]" value="<?php echo esc_attr( $cat_ids ); ?>" placeholder="<?php esc_attr_e( 'Comma-separated Category Term IDs, e.g. 15, 20', 'wpcalibrate-commerce-access-rules' ); ?>" style="width: 100%;" />
												</div>

												<!-- Fixed Date Selector -->
												<div class="wpcal-param-panel wpcal-param-fixed_date" style="<?php echo 'fixed_date' === $c_type ? '' : 'display:none;'; ?>">
													<div style="display: flex; gap: 8px;">
														<div>
															<label style="font-size: 11px;"><?php esc_html_e( 'Start (Site Time):', 'wpcalibrate-commerce-access-rules' ); ?></label>
															<input type="datetime-local" name="condition_groups[<?php echo esc_attr( (string) $g_idx ); ?>][conditions][<?php echo esc_attr( (string) $c_idx ); ?>][start]" value="<?php echo esc_attr( (string) ( $cond['start'] ?? '' ) ); ?>" />
														</div>
														<div>
															<label style="font-size: 11px;"><?php esc_html_e( 'End (Site Time):', 'wpcalibrate-commerce-access-rules' ); ?></label>
															<input type="datetime-local" name="condition_groups[<?php echo esc_attr( (string) $g_idx ); ?>][conditions][<?php echo esc_attr( (string) $c_idx ); ?>][end]" value="<?php echo esc_attr( (string) ( $cond['end'] ?? '' ) ); ?>" />
														</div>
													</div>
												</div>
											</div>

											<div class="wpcal-cond-actions">
												<button type="button" class="button-link wpcal-btn-remove-cond" title="<?php esc_attr_e( 'Remove condition', 'wpcalibrate-commerce-access-rules' ); ?>">✕</button>
											</div>
										</div>
									<?php endforeach; ?>
								</div>

								<div class="wpcal-group-footer">
									<button type="button" class="button button-small wpcal-btn-add-cond">
										+ <?php esc_html_e( 'Add Condition', 'wpcalibrate-commerce-access-rules' ); ?>
									</button>
								</div>
							</div>
						<?php endforeach; ?>
					</div>

					<button type="button" id="wpcal-btn-add-group" class="button button-secondary" style="margin-top: 12px;">
						+ <?php esc_html_e( 'Add Condition Group', 'wpcalibrate-commerce-access-rules' ); ?>
					</button>
				</div>

				<!-- Purchase Duration & Expiry -->
				<div class="wpcal-card wpcal-form-section">
					<div class="wpcal-section-header">
						<h3><?php esc_html_e( 'Access Duration & Expiry', 'wpcalibrate-commerce-access-rules' ); ?></h3>
						<p class="description">
							<?php esc_html_e( 'Determine how long access lasts after a qualifying purchase.', 'wpcalibrate-commerce-access-rules' ); ?>
						</p>
					</div>

					<div class="wpcal-form-row">
						<label for="wpcal-duration-type"><strong><?php esc_html_e( 'Duration Model:', 'wpcalibrate-commerce-access-rules' ); ?></strong></label>
						<select id="wpcal-duration-type" name="duration_type" class="wpcal-select">
							<option value="permanent" <?php selected( $rule->get_duration_type(), 'permanent' ); ?>><?php esc_html_e( 'Permanent Access (Lifetime)', 'wpcalibrate-commerce-access-rules' ); ?></option>
							<option value="days" <?php selected( $rule->get_duration_type(), 'days' ); ?>><?php esc_html_e( 'Configurable Days after purchase', 'wpcalibrate-commerce-access-rules' ); ?></option>
							<option value="weeks" <?php selected( $rule->get_duration_type(), 'weeks' ); ?>><?php esc_html_e( 'Configurable Weeks after purchase', 'wpcalibrate-commerce-access-rules' ); ?></option>
							<option value="months" <?php selected( $rule->get_duration_type(), 'months' ); ?>><?php esc_html_e( 'Configurable Calendar Months after purchase', 'wpcalibrate-commerce-access-rules' ); ?></option>
							<option value="fixed" <?php selected( $rule->get_duration_type(), 'fixed' ); ?>><?php esc_html_e( 'Fixed Calendar Window (All Users)', 'wpcalibrate-commerce-access-rules' ); ?></option>
						</select>
					</div>

					<div id="wpcal-duration-value-wrap" class="wpcal-form-row" style="<?php echo in_array( $rule->get_duration_type(), array( 'days', 'weeks', 'months' ), true ) ? '' : 'display:none;'; ?>">
						<label for="wpcal-duration-val"><strong><?php esc_html_e( 'Duration Length (Units):', 'wpcalibrate-commerce-access-rules' ); ?></strong></label>
						<input type="number" id="wpcal-duration-val" name="duration_value" value="<?php echo esc_attr( (string) ( $rule->get_duration_value() > 0 ? $rule->get_duration_value() : 30 ) ); ?>" min="0" step="1" style="width: 120px;" />
						<p class="description"><?php esc_html_e( 'e.g. 30 for 30 days, or 12 for 12 months.', 'wpcalibrate-commerce-access-rules' ); ?></p>
					</div>

					<div id="wpcal-fixed-dates-wrap" class="wpcal-form-row" style="<?php echo 'fixed' === $rule->get_duration_type() ? '' : 'display:none;'; ?>">
						<div style="display: flex; gap: 16px;">
							<div>
								<label><strong><?php esc_html_e( 'Start Date/Time:', 'wpcalibrate-commerce-access-rules' ); ?></strong></label>
								<input type="datetime-local" name="fixed_start" value="<?php echo esc_attr( (string) $rule->get_fixed_start() ); ?>" />
							</div>
							<div>
								<label><strong><?php esc_html_e( 'End Date/Time:', 'wpcalibrate-commerce-access-rules' ); ?></strong></label>
								<input type="datetime-local" name="fixed_end" value="<?php echo esc_attr( (string) $rule->get_fixed_end() ); ?>" />
							</div>
						</div>
						<p class="description"><?php esc_html_e( 'Timestamps evaluated against the WordPress site timezone.', 'wpcalibrate-commerce-access-rules' ); ?></p>
					</div>
				</div>

				<!-- Denied Access Behavior -->
				<div class="wpcal-card wpcal-form-section">
					<div class="wpcal-section-header">
						<h3><?php esc_html_e( 'Denied Access Action', 'wpcalibrate-commerce-access-rules' ); ?></h3>
						<p class="description">
							<?php esc_html_e( 'What should happen when an unauthorized visitor encounters content protected by this rule?', 'wpcalibrate-commerce-access-rules' ); ?>
						</p>
					</div>

					<div class="wpcal-form-row">
						<label for="wpcal-denied-action"><strong><?php esc_html_e( 'Action on Denial:', 'wpcalibrate-commerce-access-rules' ); ?></strong></label>
						<select id="wpcal-denied-action" name="denied_action" class="wpcal-select">
							<option value="message" <?php selected( $rule->get_denied_action(), 'message' ); ?>><?php esc_html_e( 'Display Custom Restriction Message', 'wpcalibrate-commerce-access-rules' ); ?></option>
							<option value="login_redirect" <?php selected( $rule->get_denied_action(), 'login_redirect' ); ?>><?php esc_html_e( 'Redirect to WordPress Login Page', 'wpcalibrate-commerce-access-rules' ); ?></option>
							<option value="page_redirect" <?php selected( $rule->get_denied_action(), 'page_redirect' ); ?>><?php esc_html_e( 'Redirect to Internal Page', 'wpcalibrate-commerce-access-rules' ); ?></option>
							<option value="product_redirect" <?php selected( $rule->get_denied_action(), 'product_redirect' ); ?>><?php esc_html_e( 'Redirect to Required WooCommerce Product', 'wpcalibrate-commerce-access-rules' ); ?></option>
							<option value="purchase_button" <?php selected( $rule->get_denied_action(), 'purchase_button' ); ?>><?php esc_html_e( 'Show Purchase Button', 'wpcalibrate-commerce-access-rules' ); ?></option>
							<option value="hide" <?php selected( $rule->get_denied_action(), 'hide' ); ?>><?php esc_html_e( 'Hide Content Completely (Blank/Removed)', 'wpcalibrate-commerce-access-rules' ); ?></option>
						</select>
					</div>

					<!-- Page Redirect Selector -->
					<div id="wpcal-page-redirect-wrap" class="wpcal-form-row" style="<?php echo 'page_redirect' === $rule->get_denied_action() ? '' : 'display:none;'; ?>">
						<label for="wpcal-redirect-page"><strong><?php esc_html_e( 'Internal Redirect Page:', 'wpcalibrate-commerce-access-rules' ); ?></strong></label>
						<?php
						wp_dropdown_pages(
							array(
								'name'              => 'redirect_page_id',
								'id'                => 'wpcal-redirect-page',
								'selected'          => $rule->get_redirect_page_id(),
								'show_option_none'  => __( '-- Select Target Page --', 'wpcalibrate-commerce-access-rules' ),
								'option_none_value' => '0',
							)
						);
						?>
					</div>

					<!-- Product Redirect / Button Target Selector -->
					<div id="wpcal-product-redirect-wrap" class="wpcal-form-row" style="<?php echo in_array( $rule->get_denied_action(), array( 'product_redirect', 'purchase_button' ), true ) ? '' : 'display:none;'; ?>">
						<label for="wpcal-redirect-product"><strong><?php esc_html_e( 'Qualifying WooCommerce Product ID:', 'wpcalibrate-commerce-access-rules' ); ?></strong></label>
						<input type="number" id="wpcal-redirect-product" name="redirect_product_id" value="<?php echo esc_attr( (string) $rule->get_redirect_product_id() ); ?>" placeholder="<?php esc_attr_e( 'Product ID', 'wpcalibrate-commerce-access-rules' ); ?>" style="width: 140px;" />
						<p class="description"><?php esc_html_e( 'Optional if a single product is already targeted under Conditions.', 'wpcalibrate-commerce-access-rules' ); ?></p>
					</div>

					<!-- Purchase Button Label -->
					<div id="wpcal-btn-text-wrap" class="wpcal-form-row" style="<?php echo 'purchase_button' === $rule->get_denied_action() ? '' : 'display:none;'; ?>">
						<label for="wpcal-btn-text"><strong><?php esc_html_e( 'Button Text:', 'wpcalibrate-commerce-access-rules' ); ?></strong></label>
						<input type="text" id="wpcal-btn-text" name="purchase_button_text" value="<?php echo esc_attr( $rule->get_purchase_button_text() ); ?>" class="widefat" />
					</div>

					<!-- Custom Message WYSIWYG -->
					<div id="wpcal-message-wrap" class="wpcal-form-row" style="<?php echo 'hide' === $rule->get_denied_action() ? 'display:none;' : ''; ?>">
						<label for="wpcal-denied-message"><strong><?php esc_html_e( 'Custom Restriction Message Markup:', 'wpcalibrate-commerce-access-rules' ); ?></strong></label>
						<?php
						wp_editor(
							$rule->get_denied_message(),
							'wpcal_denied_message',
							array(
								'textarea_name' => 'denied_message',
								'textarea_rows' => 6,
								'media_buttons' => false,
								'teeny'         => true,
							)
						);
						?>
					</div>
				</div>
			</div>

			<!-- Sidebar Column -->
			<div class="wpcal-editor-side">
				<!-- Publish / Save Card -->
				<div class="wpcal-card">
					<h3><?php esc_html_e( 'Status & Priority', 'wpcalibrate-commerce-access-rules' ); ?></h3>

					<div class="wpcal-side-row">
						<label><strong><?php esc_html_e( 'Rule Status:', 'wpcalibrate-commerce-access-rules' ); ?></strong></label>
						<select name="status" class="widefat">
							<option value="active" <?php selected( $rule->get_status(), 'active' ); ?>><?php esc_html_e( 'Active (Enforcing)', 'wpcalibrate-commerce-access-rules' ); ?></option>
							<option value="inactive" <?php selected( $rule->get_status(), 'inactive' ); ?>><?php esc_html_e( 'Inactive (Disabled)', 'wpcalibrate-commerce-access-rules' ); ?></option>
						</select>
					</div>

					<div class="wpcal-side-row" style="margin-top: 12px;">
						<label for="wpcal-priority"><strong><?php esc_html_e( 'Rule Priority:', 'wpcalibrate-commerce-access-rules' ); ?></strong></label>
						<input type="number" id="wpcal-priority" name="priority" value="<?php echo esc_attr( (string) $rule->get_priority() ); ?>" class="widefat" />
						<p class="description" style="font-size: 11px;">
							<?php esc_html_e( 'Higher numbers are evaluated first when multiple rules are assigned.', 'wpcalibrate-commerce-access-rules' ); ?>
						</p>
					</div>

					<hr style="margin: 16px 0; border: 0; border-top: 1px solid #eee;">

					<div class="wpcal-side-actions">
						<button type="submit" class="button button-primary button-large" style="width: 100%;">
							<?php echo $is_new ? esc_html__( 'Publish Restriction Rule', 'wpcalibrate-commerce-access-rules' ) : esc_html__( 'Update Restriction Rule', 'wpcalibrate-commerce-access-rules' ); ?>
						</button>
					</div>
				</div>

				<!-- Qualifying Order Statuses -->
				<div class="wpcal-card" style="margin-top: 16px;">
					<h3><?php esc_html_e( 'Order Status Overrides', 'wpcalibrate-commerce-access-rules' ); ?></h3>
					<p class="description" style="font-size: 11px;">
						<?php esc_html_e( 'Leave unchecked to use global settings, or select specific qualifying statuses for this rule:', 'wpcalibrate-commerce-access-rules' ); ?>
					</p>
					<div class="wpcal-order-status-list">
						<?php foreach ( $all_order_statuses as $st_key => $st_label ) : ?>
							<label style="display: block; margin-bottom: 4px; font-size: 12px;">
								<input type="checkbox" name="qualifying_order_statuses[]" value="<?php echo esc_attr( $st_key ); ?>" <?php checked( in_array( $st_key, $rule_order_statuses, true ) ); ?> />
								<?php echo esc_html( $st_label ); ?>
							</label>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</div>
	</form>
</div>
