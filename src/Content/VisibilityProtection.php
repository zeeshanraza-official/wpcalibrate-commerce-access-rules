<?php
/**
 * Visibility and Leakage Protection for Excerpts, Search, Archives, Feeds, and REST API.
 *
 * @package WPCalibrate\CommerceAccessRules
 */

declare(strict_types=1);

namespace WPCalibrate\CommerceAccessRules\Content;

use WPCalibrate\CommerceAccessRules\Capabilities;
use WPCalibrate\CommerceAccessRules\Domain\Access\AccessEvaluator;

/**
 * VisibilityProtection class.
 */
final class VisibilityProtection {

	private AccessEvaluator $evaluator;

	/**
	 * Constructor.
	 *
	 * @param AccessEvaluator $evaluator
	 */
	public function __construct( AccessEvaluator $evaluator ) {
		$this->evaluator = $evaluator;
	}

	/**
	 * Register protection hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		// Protect excerpts.
		add_filter( 'get_the_excerpt', array( $this, 'filter_excerpt' ), 999, 2 );
		add_filter( 'the_excerpt', array( $this, 'filter_excerpt' ), 999, 2 );

		// Protect RSS feeds.
		add_filter( 'the_content_feed', array( $this, 'filter_feed_content' ), 999 );
		add_filter( 'the_excerpt_rss', array( $this, 'filter_feed_content' ), 999 );

		// Protect search and archive loops via pre_get_posts.
		add_action( 'pre_get_posts', array( $this, 'filter_query' ), 99 );

		// Protect WP REST API responses for posts, pages, and products.
		add_filter( 'rest_prepare_post', array( $this, 'filter_rest_response' ), 10, 3 );
		add_filter( 'rest_prepare_page', array( $this, 'filter_rest_response' ), 10, 3 );
		add_filter( 'rest_prepare_product', array( $this, 'filter_rest_response' ), 10, 3 );
	}

	/**
	 * Filter excerpt to prevent leaking protected body content.
	 *
	 * @param string        $excerpt Post excerpt.
	 * @param \WP_Post|null $post    Post object.
	 * @return string Filtered excerpt.
	 */
	public function filter_excerpt( string $excerpt, $post = null ): string {
		$post_obj = $post instanceof \WP_Post ? $post : get_post();
		if ( ! $post_obj || ! AssignmentRepository::has_restrictions( $post_obj->ID ) ) {
			return $excerpt;
		}

		$user_id = (int) get_current_user_id();
		if ( $user_id > 0 && Capabilities::can_manage_rules( $user_id ) ) {
			return $excerpt;
		}

		$result = $this->evaluator->evaluate_content_access( $user_id, $post_obj->ID );
		if ( $result->is_allowed() ) {
			return $excerpt;
		}

		// When restricted, do not expose body text in excerpt.
		return wp_strip_all_tags( $result->get_custom_message() ?: __( 'Content restricted.', 'wpcalibrate-commerce-access-rules' ) );
	}

	/**
	 * Filter RSS feed content to prevent leaking protected body.
	 *
	 * @param string $content Feed content.
	 * @return string Filtered content.
	 */
	public function filter_feed_content( string $content ): string {
		$post_id = get_the_ID();
		if ( ! $post_id || ! AssignmentRepository::has_restrictions( $post_id ) ) {
			return $content;
		}

		$result = $this->evaluator->evaluate_content_access( 0, $post_id );
		if ( $result->is_allowed() ) {
			return $content;
		}

		return esc_html__( 'This content is restricted and not available via feed.', 'wpcalibrate-commerce-access-rules' );
	}

	/**
	 * Filter main query to hide restricted content from search or archives if configured.
	 *
	 * @param \WP_Query $query
	 * @return void
	 */
	public function filter_query( \WP_Query $query ): void {
		// Never modify admin queries or non-main queries unless explicitly targeted.
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$hide_from_search   = (bool) get_option( 'wpcal_hide_from_search', false );
		$hide_from_archives = (bool) get_option( 'wpcal_hide_from_archives', false );

		if ( ! $hide_from_search && ! $hide_from_archives ) {
			return;
		}

		// Check if query is search or archive.
		$is_search  = $query->is_search() && $hide_from_search;
		$is_archive = ( $query->is_archive() || $query->is_home() ) && $hide_from_archives;

		if ( ! $is_search && ! $is_archive ) {
			return;
		}

		// Authorized managers should still see everything.
		$user_id = (int) get_current_user_id();
		if ( $user_id > 0 && Capabilities::can_manage_rules( $user_id ) ) {
			return;
		}

		// Exclude restricted posts that the visitor cannot access.
		$restricted_ids = $this->get_denied_post_ids_for_user( $user_id );
		if ( ! empty( $restricted_ids ) ) {
			$post__not_in = (array) $query->get( 'post__not_in', array() );
			$query->set( 'post__not_in', array_unique( array_merge( $post__not_in, $restricted_ids ) ) );
		}
	}

	/**
	 * Get IDs of posts that have restrictions which current user cannot access.
	 *
	 * @param int $user_id
	 * @return int[]
	 */
	private function get_denied_post_ids_for_user( int $user_id ): array {
		global $wpdb;

		// Find posts having the assignment meta key.
		$post_ids = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prepare(
				"SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s",
				\WPCalibrate\CommerceAccessRules\Domain\Rule\RuleRepository::META_ASSIGNMENT
			)
		);

		if ( empty( $post_ids ) ) {
			return array();
		}

		$denied = array();
		foreach ( $post_ids as $pid ) {
			$id = (int) $pid;
			$res = $this->evaluator->evaluate_content_access( $user_id, $id );
			if ( ! $res->is_allowed() ) {
				$denied[] = $id;
			}
		}

		return $denied;
	}

	/**
	 * Filter REST API response for protected post types.
	 *
	 * @param \WP_REST_Response $response
	 * @param \WP_Post          $post
	 * @param \WP_REST_Request  $request
	 * @return \WP_REST_Response
	 */
	public function filter_rest_response( \WP_REST_Response $response, \WP_Post $post, \WP_REST_Request $request ): \WP_REST_Response {
		if ( ! AssignmentRepository::has_restrictions( $post->ID ) ) {
			return $response;
		}

		$user_id = (int) get_current_user_id();
		if ( $user_id > 0 && Capabilities::can_manage_rules( $user_id ) ) {
			return $response;
		}

		$result = $this->evaluator->evaluate_content_access( $user_id, $post->ID );
		if ( $result->is_allowed() ) {
			return $response;
		}

		$data = $response->get_data();

		// Censor content and excerpt.
		if ( isset( $data['content'] ) ) {
			$data['content']['rendered']  = wp_kses_post( $result->get_custom_message() ?: __( 'Content restricted.', 'wpcalibrate-commerce-access-rules' ) );
			$data['content']['protected'] = true;
		}

		if ( isset( $data['excerpt'] ) ) {
			$data['excerpt']['rendered']  = wp_strip_all_tags( $result->get_custom_message() ?: __( 'Content restricted.', 'wpcalibrate-commerce-access-rules' ) );
			$data['excerpt']['protected'] = true;
		}

		$data['wpcalibrate_restricted'] = true;
		$data['wpcalibrate_reason']     = $result->get_reason_code();

		$response->set_data( $data );
		return $response;
	}
}
