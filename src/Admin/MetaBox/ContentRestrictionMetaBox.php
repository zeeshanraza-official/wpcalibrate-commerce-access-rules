<?php
/**
 * Content Edit-Screen Restriction Meta Box.
 *
 * @package WPCalibrate\CommerceAccessRules
 */

declare(strict_types=1);

namespace WPCalibrate\CommerceAccessRules\Admin\MetaBox;

use WPCalibrate\CommerceAccessRules\Capabilities;
use WPCalibrate\CommerceAccessRules\Content\AssignmentRepository;
use WPCalibrate\CommerceAccessRules\Domain\Rule\RuleRepository;

/**
 * ContentRestrictionMetaBox class.
 */
final class ContentRestrictionMetaBox {

	private RuleRepository $rule_repo;

	public const NONCE_ACTION = 'wpcal_save_content_restrictions';
	public const NONCE_NAME   = 'wpcal_restriction_nonce';

	/**
	 * Constructor.
	 *
	 * @param RuleRepository $rule_repo
	 */
	public function __construct( RuleRepository $rule_repo ) {
		$this->rule_repo = $rule_repo;
	}

	/**
	 * Register meta box hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_post' ), 10, 2 );
	}

	/**
	 * Add meta box to supported post types.
	 *
	 * @return void
	 */
	public function add_meta_boxes(): void {
		$post_types = get_post_types( array( 'public' => true ), 'names' );

		// Always ensure standard types are supported.
		$supported = array_unique( array_merge( array( 'post', 'page', 'product' ), array_values( $post_types ) ) );

		// Exclude our private rule CPT itself.
		$supported = array_diff( $supported, array( RuleRepository::POST_TYPE, 'attachment', 'revision', 'nav_menu_item' ) );

		foreach ( $supported as $post_type ) {
			add_meta_box(
				'wpcal_content_restriction_box',
				__( 'WPCalibrate Content Restriction', 'wpcalibrate-commerce-access-rules' ),
				array( $this, 'render_meta_box' ),
				$post_type,
				'side',
				'default'
			);
		}
	}

	/**
	 * Render the meta box markup.
	 *
	 * @param \WP_Post $post Current post object.
	 * @return void
	 */
	public function render_meta_box( \WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		$assigned_rules = AssignmentRepository::get_assigned_rule_ids( $post->ID );
		$all_rules      = $this->rule_repo->find( array( 'per_page' => 100, 'orderby' => 'priority', 'order' => 'DESC' ) );
		$manage_url     = admin_url( 'admin.php?page=wpcalibrate-access-rules&tab=rules' );

		?>
		<div class="wpcal-metabox-wrapper">
			<p class="description">
				<?php esc_html_e( 'Select restriction rules to apply to this content. If multiple rules are selected, access is evaluated according to plugin settings.', 'wpcalibrate-commerce-access-rules' ); ?>
			</p>

			<?php if ( empty( $all_rules ) ) : ?>
				<p><em><?php esc_html_e( 'No restriction rules created yet.', 'wpcalibrate-commerce-access-rules' ); ?></em></p>
			<?php else : ?>
				<div class="wpcal-rule-checkboxes" style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 8px; margin: 8px 0; background: #fafafa; border-radius: 4px;">
					<?php foreach ( $all_rules as $rule ) : ?>
						<?php
						$rid        = (int) $rule->get_id();
						$is_checked = in_array( $rid, $assigned_rules, true );
						$is_active  = $rule->is_active();
						?>
						<label style="display: block; margin-bottom: 6px; font-size: 13px;">
							<input type="checkbox" name="wpcal_assigned_rules[]" value="<?php echo esc_attr( (string) $rid ); ?>" <?php checked( $is_checked ); ?> />
							<strong><?php echo esc_html( $rule->get_title() ); ?></strong>
							<?php if ( ! $is_active ) : ?>
								<span style="color: #999; font-size: 11px;">(<?php esc_html_e( 'Inactive', 'wpcalibrate-commerce-access-rules' ); ?>)</span>
							<?php endif; ?>
							<br>
							<span style="color: #666; font-size: 11px; margin-left: 20px;">
								<?php echo esc_html( $rule->get_access_summary() ); ?>
							</span>
						</label>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<p style="margin-top: 10px;">
				<a href="<?php echo esc_url( $manage_url ); ?>" target="_blank" class="button button-secondary" style="font-size: 12px;">
					<?php esc_html_e( 'Manage Restriction Rules ↗', 'wpcalibrate-commerce-access-rules' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Save meta box data.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @return void
	 */
	public function save_post( int $post_id, \WP_Post $post ): void {
		// Nonce check.
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
			return;
		}

		// Autosave / revision check.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Capability check.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$submitted_rules = isset( $_POST['wpcal_assigned_rules'] ) && is_array( $_POST['wpcal_assigned_rules'] )
			? array_map( 'absint', $_POST['wpcal_assigned_rules'] )
			: array();

		AssignmentRepository::set_assigned_rule_ids( $post_id, $submitted_rules );
	}
}
