<?php
/**
 * Full Content Restriction Handler.
 *
 * @package WPCalibrate\CommerceAccessRules
 */

declare(strict_types=1);

namespace WPCalibrate\CommerceAccessRules\Content;

use WPCalibrate\CommerceAccessRules\Domain\Access\AccessEvaluator;
use WPCalibrate\CommerceAccessRules\Domain\Access\AccessResult;
use WPCalibrate\CommerceAccessRules\Domain\Rule\AccessRule;
use WPCalibrate\CommerceAccessRules\WooCommerce\ProductResolver;

/**
 * FullContentRestriction class.
 */
final class FullContentRestriction {

	private AccessEvaluator $evaluator;

	/**
	 * Recursion guard for the_content filter.
	 *
	 * @var array<int, bool>
	 */
	private array $processing_posts = array();

	/**
	 * Constructor.
	 *
	 * @param AccessEvaluator $evaluator
	 */
	public function __construct( AccessEvaluator $evaluator ) {
		$this->evaluator = $evaluator;
	}

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'template_redirect', array( $this, 'handle_template_redirect' ), 10 );
		add_filter( 'the_content', array( $this, 'filter_the_content' ), 999 );
	}

	/**
	 * Handle full-page redirects on template_redirect.
	 *
	 * @return void
	 */
	public function handle_template_redirect(): void {
		if ( ! is_singular() ) {
			return;
		}

		$post_id = get_queried_object_id();
		if ( ! $post_id || ! AssignmentRepository::has_restrictions( $post_id ) ) {
			return;
		}

		$result = $this->evaluator->evaluate_content_access( null, $post_id );
		if ( $result->is_allowed() ) {
			return;
		}

		// Handle redirects.
		$redirect_url = $result->get_redirect_url();
		if ( empty( $redirect_url ) ) {
			return;
		}

		// Prevent redirect loop!
		if ( $this->is_current_url( $redirect_url ) ) {
			return;
		}

		// Validate redirect URL safely.
		$safe_url = wp_sanitize_redirect( $redirect_url );
		if ( empty( $safe_url ) ) {
			return;
		}

		// If redirecting to WordPress login page, ensure we aren't already on login.
		if ( is_admin() || ( isset( $GLOBALS['pagenow'] ) && 'wp-login.php' === $GLOBALS['pagenow'] ) ) {
			return;
		}

		wp_safe_redirect( $safe_url );
		exit;
	}

	/**
	 * Filter post content when denied.
	 *
	 * @param string $content Post content markup.
	 * @return string Modified content or original if allowed.
	 */
	public function filter_the_content( string $content ): string {
		$post_id = get_the_ID();
		if ( ! $post_id || ! AssignmentRepository::has_restrictions( $post_id ) ) {
			return $content;
		}

		// Recursion guard.
		if ( isset( $this->processing_posts[ $post_id ] ) ) {
			return $content;
		}
		$this->processing_posts[ $post_id ] = true;

		$result = $this->evaluator->evaluate_content_access( null, $post_id );

		unset( $this->processing_posts[ $post_id ] );

		if ( $result->is_allowed() ) {
			return $content;
		}

		return $this->render_denied_content( $result );
	}

	/**
	 * Render denied content markup.
	 *
	 * @param AccessResult $result
	 * @return string
	 */
	public function render_denied_content( AccessResult $result ): string {
		$action  = $result->get_denied_action();
		$message = $result->get_custom_message() ?? '';

		if ( AccessRule::ACTION_HIDE === $action ) {
			return '';
		}

		$output = '<div class="wpcal-restricted-notice" role="region" aria-label="' . esc_attr__( 'Restricted Content Notice', 'wpcalibrate-commerce-access-rules' ) . '">';
		$output .= wp_kses_post( $message );

		if ( AccessRule::ACTION_PURCHASE_BUTTON === $action || $result->get_qualifying_product_id() ) {
			$prod_id = $result->get_qualifying_product_id();
			if ( $prod_id ) {
				$output .= $this->render_purchase_button( $prod_id, $result->get_purchase_button_text() );
			}
		}

		$output .= '</div>';
		return $output;
	}

	/**
	 * Render a purchase button for the qualifying product.
	 *
	 * @param int         $product_id
	 * @param string|null $button_text
	 * @return string
	 */
	public function render_purchase_button( int $product_id, ?string $button_text = null ): string {
		$cart_url  = ProductResolver::get_add_to_cart_url( $product_id );
		$prod_name = ProductResolver::get_product_name( $product_id );
		$price_html = ProductResolver::get_product_price_html( $product_id );
		$label     = ! empty( $button_text ) ? $button_text : __( 'Buy Now to Unlock Access', 'wpcalibrate-commerce-access-rules' );

		ob_start();
		?>
		<div class="wpcal-purchase-wrapper">
			<?php if ( ! empty( $price_html ) ) : ?>
				<div class="wpcal-purchase-price"><?php echo wp_kses_post( $price_html ); ?></div>
			<?php endif; ?>
			<a href="<?php echo esc_url( $cart_url ); ?>" class="wpcal-purchase-button button alt" role="button">
				<?php echo esc_html( $label ); ?>
			</a>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Check if a URL matches the currently requested URL.
	 *
	 * @param string $url
	 * @return bool
	 */
	private function is_current_url( string $url ): bool {
		$target_path  = wp_parse_url( $url, PHP_URL_PATH );
		$current_path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';

		if ( empty( $target_path ) || empty( $current_path ) ) {
			return false;
		}

		return rtrim( (string) $target_path, '/' ) === rtrim( (string) $current_path, '/' );
	}
}
