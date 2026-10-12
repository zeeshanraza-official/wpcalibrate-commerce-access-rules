<?php
/**
 * Rules List & Router Admin Tab.
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

$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';

if ( 'add' === $action || 'edit' === $action ) {
	include WPCALIBRATE_COMMERCE_ACCESS_RULES_DIR . 'templates/admin/tabs/rule-edit.php';
	return;
}

// Listing table parameters.
$search   = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
$status   = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
$paged    = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
$per_page = 20;

$query_args = array(
	'search'   => $search,
	'status'   => $status,
	'paged'    => $paged,
	'per_page' => $per_page,
	'orderby'  => 'priority',
	'order'    => 'DESC',
);

$rules       = $rule_repo->find( $query_args );
$total_rules = $rule_repo->count( $query_args );
$total_pages = ceil( $total_rules / $per_page );
?>

<div class="wpcal-rules-list-container">
	<div class="wpcal-table-toolbar">
		<form method="get" class="wpcal-search-form">
			<input type="hidden" name="page" value="<?php echo esc_attr( \WPCalibrate\CommerceAccessRules\Admin\Menu::PAGE_SLUG ); ?>" />
			<input type="hidden" name="tab" value="rules" />

			<div class="wpcal-filter-group">
				<select name="status" onchange="this.form.submit()">
					<option value=""><?php esc_html_e( 'All Statuses', 'wpcalibrate-commerce-access-rules' ); ?></option>
					<option value="active" <?php selected( $status, 'active' ); ?>><?php esc_html_e( 'Active', 'wpcalibrate-commerce-access-rules' ); ?></option>
					<option value="inactive" <?php selected( $status, 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'wpcalibrate-commerce-access-rules' ); ?></option>
				</select>

				<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search rules...', 'wpcalibrate-commerce-access-rules' ); ?>" />
				<button type="submit" class="button"><?php esc_html_e( 'Filter', 'wpcalibrate-commerce-access-rules' ); ?></button>
				<?php if ( ! empty( $search ) || ! empty( $status ) ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-access-rules&tab=rules' ) ); ?>" class="button">
						<?php esc_html_e( 'Reset', 'wpcalibrate-commerce-access-rules' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</form>

		<div class="wpcal-table-actions" style="display: flex; gap: 8px;">
			<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=' . \WPCalibrate\CommerceAccessRules\Admin\Menu::PAGE_SLUG . '&tab=rules&wpcal_action=load_demo_rules' ), 'wpcal_load_demo_rules', 'wpcal_nonce' ) ); ?>" class="button button-secondary">
				<?php esc_html_e( 'Load Demo Rules', 'wpcalibrate-commerce-access-rules' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . \WPCalibrate\CommerceAccessRules\Admin\Menu::PAGE_SLUG . '&tab=rules&action=add' ) ); ?>" class="button button-primary">
				+ <?php esc_html_e( 'Add New Rule', 'wpcalibrate-commerce-access-rules' ); ?>
			</a>
		</div>
	</div>

	<?php if ( empty( $rules ) ) : ?>
		<div class="wpcal-empty-state">
			<p><?php esc_html_e( 'No restriction rules found matching your criteria.', 'wpcalibrate-commerce-access-rules' ); ?></p>
			<div style="display: flex; gap: 10px; justify-content: center; margin-top: 12px;">
				<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=' . \WPCalibrate\CommerceAccessRules\Admin\Menu::PAGE_SLUG . '&tab=rules&wpcal_action=load_demo_rules' ), 'wpcal_load_demo_rules', 'wpcal_nonce' ) ); ?>" class="button button-primary">
					<?php esc_html_e( 'Load Sample Demo Rules', 'wpcalibrate-commerce-access-rules' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . \WPCalibrate\CommerceAccessRules\Admin\Menu::PAGE_SLUG . '&tab=rules&action=add' ) ); ?>" class="button button-secondary">
					<?php esc_html_e( 'Create your first restriction rule', 'wpcalibrate-commerce-access-rules' ); ?>
				</a>
			</div>
		</div>
	<?php else : ?>
		<table class="wp-list-table widefat fixed striped table-view-list wpcal-rules-table">
			<thead>
				<tr>
					<th scope="col" style="width: 22%;"><?php esc_html_e( 'Rule Name', 'wpcalibrate-commerce-access-rules' ); ?></th>
					<th scope="col" style="width: 10%;"><?php esc_html_e( 'Status', 'wpcalibrate-commerce-access-rules' ); ?></th>
					<th scope="col" style="width: 8%;"><?php esc_html_e( 'Priority', 'wpcalibrate-commerce-access-rules' ); ?></th>
					<th scope="col" style="width: 30%;"><?php esc_html_e( 'Access Conditions', 'wpcalibrate-commerce-access-rules' ); ?></th>
					<th scope="col" style="width: 10%;"><?php esc_html_e( 'Assignments', 'wpcalibrate-commerce-access-rules' ); ?></th>
					<th scope="col" style="width: 20%;"><?php esc_html_e( 'Actions', 'wpcalibrate-commerce-access-rules' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $rules as $rule ) : ?>
					<?php
					$rid            = (int) $rule->get_id();
					$edit_url       = admin_url( 'admin.php?page=wpcalibrate-access-rules&tab=rules&action=edit&rule_id=' . $rid );
					$duplicate_url  = wp_nonce_url( admin_url( 'admin.php?page=wpcalibrate-access-rules&wpcal_action=duplicate_rule&rule_id=' . $rid ), 'wpcal_duplicate_rule_' . $rid );
					$delete_url     = wp_nonce_url( admin_url( 'admin.php?page=wpcalibrate-access-rules&wpcal_action=delete_rule&rule_id=' . $rid ), 'wpcal_delete_rule_' . $rid );
					$toggle_url     = wp_nonce_url( admin_url( 'admin.php?page=wpcalibrate-access-rules&wpcal_action=toggle_status&rule_id=' . $rid . '&status=' . ( $rule->is_active() ? 'inactive' : 'active' ) ), 'wpcal_toggle_status_' . $rid );
					$assigned_count = $rule_repo->count_assignments( $rid );
					?>
					<tr>
						<td>
							<strong>
								<a href="<?php echo esc_url( $edit_url ); ?>" class="row-title">
									<?php echo esc_html( $rule->get_title() ); ?>
								</a>
							</strong>
							<span style="color: #888; font-size: 11px; display: block;">ID: #<?php echo esc_html( (string) $rid ); ?></span>
						</td>
						<td>
							<?php if ( $rule->is_active() ) : ?>
								<span class="wpcal-badge wpcal-badge-success"><?php esc_html_e( 'Active', 'wpcalibrate-commerce-access-rules' ); ?></span>
							<?php else : ?>
								<span class="wpcal-badge wpcal-badge-neutral"><?php esc_html_e( 'Inactive', 'wpcalibrate-commerce-access-rules' ); ?></span>
							<?php endif; ?>
						</td>
						<td>
							<code><?php echo esc_html( (string) $rule->get_priority() ); ?></code>
						</td>
						<td>
							<span class="wpcal-rule-summary"><?php echo esc_html( $rule->get_access_summary() ); ?></span>
						</td>
						<td>
							<span class="wpcal-count-badge"><?php echo esc_html( (string) $assigned_count ); ?></span>
						</td>
						<td>
							<div class="wpcal-row-actions">
								<a href="<?php echo esc_url( $edit_url ); ?>" class="button button-small">
									<?php esc_html_e( 'Edit', 'wpcalibrate-commerce-access-rules' ); ?>
								</a>
								<a href="<?php echo esc_url( $toggle_url ); ?>" class="button button-small">
									<?php echo $rule->is_active() ? esc_html__( 'Disable', 'wpcalibrate-commerce-access-rules' ) : esc_html__( 'Enable', 'wpcalibrate-commerce-access-rules' ); ?>
								</a>
								<a href="<?php echo esc_url( $duplicate_url ); ?>" class="button button-small" onclick="return confirm(wpcalibrateAccessRules.strings.confirmDuplicate);">
									<?php esc_html_e( 'Duplicate', 'wpcalibrate-commerce-access-rules' ); ?>
								</a>
								<a href="<?php echo esc_url( $delete_url ); ?>" class="button button-small wpcal-btn-delete" onclick="return confirm(wpcalibrateAccessRules.strings.confirmDelete);">
									<?php esc_html_e( 'Delete', 'wpcalibrate-commerce-access-rules' ); ?>
								</a>
							</div>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<?php if ( $total_pages > 1 ) : ?>
			<div class="tablenav bottom">
				<div class="tablenav-pages">
					<span class="displaying-num"><?php printf( /* translators: %d: count */ esc_html__( '%d rules', 'wpcalibrate-commerce-access-rules' ), $total_rules ); ?></span>
					<?php
					echo paginate_links( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						array(
							'base'    => add_query_arg( 'paged', '%#%' ),
							'format'  => '',
							'current' => $paged,
							'total'   => $total_pages,
						)
					);
					?>
				</div>
			</div>
		<?php endif; ?>
	<?php endif; ?>
</div>
