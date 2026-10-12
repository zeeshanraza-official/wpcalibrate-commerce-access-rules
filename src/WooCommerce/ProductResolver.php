<?php
/**
 * WooCommerce Product and Category Resolver.
 *
 * @package WPCalibrate\CommerceAccessRules
 */

declare(strict_types=1);

namespace WPCalibrate\CommerceAccessRules\WooCommerce;

/**
 * ProductResolver class.
 */
final class ProductResolver {

	/**
	 * Get product title by ID safely.
	 *
	 * @param int $product_id Product ID.
	 * @return string Product name.
	 */
	public static function get_product_name( int $product_id ): string {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return sprintf( '#%d', $product_id );
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return sprintf( '#%d (%s)', $product_id, __( 'Deleted/Missing', 'wpcalibrate-commerce-access-rules' ) );
		}

		return $product->get_name();
	}

	/**
	 * Get product price formatted.
	 *
	 * @param int $product_id Product ID.
	 * @return string
	 */
	public static function get_product_price_html( int $product_id ): string {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return '';
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return '';
		}

		return $product->get_price_html();
	}

	/**
	 * Get product purchase/add-to-cart URL.
	 *
	 * @param int $product_id Product ID.
	 * @return string
	 */
	public static function get_add_to_cart_url( int $product_id ): string {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return get_permalink( $product_id ) ?: home_url( '/' );
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return home_url( '/' );
		}

		return $product->add_to_cart_url();
	}

	/**
	 * Get product permalink.
	 *
	 * @param int $product_id
	 * @return string
	 */
	public static function get_product_permalink( int $product_id ): string {
		return get_permalink( $product_id ) ?: home_url( '/' );
	}

	/**
	 * Get category name by ID.
	 *
	 * @param int $term_id Product category term ID.
	 * @return string
	 */
	public static function get_category_name( int $term_id ): string {
		$term = get_term( $term_id, 'product_cat' );
		if ( ! $term || is_wp_error( $term ) ) {
			return sprintf( '#%d', $term_id );
		}

		return $term->name;
	}

	/**
	 * Search products for admin select dropdowns.
	 *
	 * @param string $search Search query.
	 * @param int    $limit  Maximum products to return.
	 * @return array<int, array<string, mixed>>
	 */
	public static function search_products( string $search = '', int $limit = 20 ): array {
		if ( ! function_exists( 'wc_get_products' ) ) {
			return array();
		}

		$args = array(
			'status'  => 'publish',
			'limit'   => $limit,
			'orderby' => 'title',
			'order'   => 'ASC',
		);

		if ( ! empty( $search ) ) {
			$args['s'] = $search;
		}

		$products = wc_get_products( $args );
		$results  = array();

		foreach ( $products as $product ) {
			$results[] = array(
				'id'    => $product->get_id(),
				'text'  => sprintf( '%s (ID: %d)', $product->get_name(), $product->get_id() ),
				'price' => wp_strip_all_tags( $product->get_price_html() ),
			);
		}

		return $results;
	}

	/**
	 * Search product categories for admin select dropdowns.
	 *
	 * @param string $search Search query.
	 * @return array<int, array<string, mixed>>
	 */
	public static function search_categories( string $search = '' ): array {
		$args = array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'number'     => 50,
		);

		if ( ! empty( $search ) ) {
			$args['search'] = $search;
		}

		$terms   = get_terms( $args );
		$results = array();

		if ( is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				if ( $term instanceof \WP_Term ) {
					$results[] = array(
						'id'   => $term->term_id,
						'text' => sprintf( '%s (ID: %d)', $term->name, $term->term_id ),
					);
				}
			}
		}

		return $results;
	}
}
