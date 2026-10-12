<?php
/**
 * Restriction Rule Repository.
 *
 * @package WPCalibrate\CommerceAccessRules
 */

declare(strict_types=1);

namespace WPCalibrate\CommerceAccessRules\Domain\Rule;

/**
 * RuleRepository class.
 */
final class RuleRepository {

	public const POST_TYPE    = 'wpcal_access_rule';
	public const META_CONFIG  = '_wpcal_rule_config';
	public const META_STATUS  = '_wpcal_rule_status';
	public const META_PRIORITY = '_wpcal_rule_priority';

	/**
	 * Content assignment meta key on restricted posts/pages/products.
	 */
	public const META_ASSIGNMENT = '_wpcalibrate_access_rules';

	/**
	 * Register the private custom post type.
	 */
	public static function register_post_type(): void {
		$labels = array(
			'name'               => _x( 'Restriction Rules', 'post type general name', 'wpcalibrate-commerce-access-rules' ),
			'singular_name'      => _x( 'Restriction Rule', 'post type singular name', 'wpcalibrate-commerce-access-rules' ),
			'menu_name'          => _x( 'Restriction Rules', 'admin menu', 'wpcalibrate-commerce-access-rules' ),
			'name_admin_bar'     => _x( 'Restriction Rule', 'add new on admin bar', 'wpcalibrate-commerce-access-rules' ),
			'add_new'            => _x( 'Add New', 'restriction rule', 'wpcalibrate-commerce-access-rules' ),
			'add_new_item'       => __( 'Add New Restriction Rule', 'wpcalibrate-commerce-access-rules' ),
			'new_item'           => __( 'New Restriction Rule', 'wpcalibrate-commerce-access-rules' ),
			'edit_item'          => __( 'Edit Restriction Rule', 'wpcalibrate-commerce-access-rules' ),
			'view_item'          => __( 'View Restriction Rule', 'wpcalibrate-commerce-access-rules' ),
			'all_items'          => __( 'All Restriction Rules', 'wpcalibrate-commerce-access-rules' ),
			'search_items'       => __( 'Search Restriction Rules', 'wpcalibrate-commerce-access-rules' ),
			'not_found'          => __( 'No restriction rules found.', 'wpcalibrate-commerce-access-rules' ),
			'not_found_in_trash' => __( 'No restriction rules found in Trash.', 'wpcalibrate-commerce-access-rules' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => false,
			'show_in_menu'       => false,
			'query_var'          => false,
			'rewrite'            => false,
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title' ),
			'can_export'         => true,
		);

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Get a rule by ID.
	 *
	 * @param int $id Rule post ID.
	 * @return AccessRule|null
	 */
	public function get( int $id ): ?AccessRule {
		$post = get_post( $id );
		if ( ! $post || $post->post_type !== self::POST_TYPE || $post->post_status === 'trash' ) {
			return null;
		}

		$config = get_post_meta( $id, self::META_CONFIG, true );
		if ( ! is_array( $config ) ) {
			$config = array();
		}

		$config['id']            = $post->ID;
		$config['title']         = $post->post_title;
		$config['date_created']  = $post->post_date;
		$config['date_modified'] = $post->post_modified;

		$status = get_post_meta( $id, self::META_STATUS, true );
		if ( ! empty( $status ) ) {
			$config['status'] = $status;
		}

		$priority = get_post_meta( $id, self::META_PRIORITY, true );
		if ( '' !== $priority && false !== $priority ) {
			$config['priority'] = (int) $priority;
		}

		return new AccessRule( $config );
	}

	/**
	 * Save a rule (insert or update).
	 *
	 * @param AccessRule $rule Rule entity.
	 * @return int Saved rule ID.
	 * @throws \RuntimeException On save failure.
	 */
	public function save( AccessRule $rule ): int {
		$post_data = array(
			'post_type'   => self::POST_TYPE,
			'post_title'  => $rule->get_title(),
			'post_status' => 'publish',
		);

		if ( $rule->get_id() ) {
			$post_data['ID'] = $rule->get_id();
			$post_id         = wp_update_post( $post_data, true );
		} else {
			$post_id = wp_insert_post( $post_data, true );
		}

		if ( is_wp_error( $post_id ) || empty( $post_id ) ) {
			$msg = is_wp_error( $post_id ) ? $post_id->get_error_message() : __( 'Could not insert restriction rule post into database.', 'wpcalibrate-commerce-access-rules' );
			throw new \RuntimeException( 'Failed to save restriction rule: ' . $msg );
		}

		$rule->set_id( (int) $post_id );
		$config = $rule->to_array();

		// Don't duplicate post properties inside meta array.
		unset( $config['id'], $config['title'], $config['date_created'], $config['date_modified'] );

		update_post_meta( $post_id, self::META_CONFIG, $config );
		update_post_meta( $post_id, self::META_STATUS, $rule->get_status() );
		update_post_meta( $post_id, self::META_PRIORITY, $rule->get_priority() );

		// Clean related access cache.
		do_action( 'wpcalibrate_access_rule_saved', $post_id, $rule );

		return (int) $post_id;
	}

	/**
	 * Delete a rule.
	 *
	 * @param int  $id    Rule ID.
	 * @param bool $force Force permanent deletion.
	 * @return bool
	 */
	public function delete( int $id, bool $force = true ): bool {
		$post = get_post( $id );
		if ( ! $post || $post->post_type !== self::POST_TYPE ) {
			return false;
		}

		do_action( 'wpcalibrate_access_rule_before_delete', $id );

		$result = wp_delete_post( $id, $force );
		if ( $result ) {
			delete_post_meta( $id, self::META_CONFIG );
			delete_post_meta( $id, self::META_STATUS );
			delete_post_meta( $id, self::META_PRIORITY );
			do_action( 'wpcalibrate_access_rule_deleted', $id );
			return true;
		}

		return false;
	}

	/**
	 * Duplicate a rule.
	 *
	 * @param int $id Rule ID to duplicate.
	 * @return int|null New duplicated rule ID or null on failure.
	 */
	public function duplicate( int $id ): ?int {
		$original = $this->get( $id );
		if ( ! $original ) {
			return null;
		}

		$copy_data = $original->to_array();
		unset( $copy_data['id'] );

		/* translators: %s: original rule title */
		$copy_data['title']  = sprintf( __( '%s (Copy)', 'wpcalibrate-commerce-access-rules' ), $original->get_title() );
		$copy_data['status'] = AccessRule::STATUS_INACTIVE; // New copy starts inactive for safety.

		$new_rule = new AccessRule( $copy_data );
		return $this->save( $new_rule );
	}

	/**
	 * Set rule status (active/inactive).
	 *
	 * @param int    $id     Rule ID.
	 * @param string $status Target status.
	 * @return bool
	 */
	public function set_status( int $id, string $status ): bool {
		$rule = $this->get( $id );
		if ( ! $rule ) {
			return false;
		}

		$rule->set_status( $status );
		update_post_meta( $id, self::META_STATUS, $rule->get_status() );

		// Update inside meta config too.
		$config = get_post_meta( $id, self::META_CONFIG, true );
		if ( is_array( $config ) ) {
			$config['status'] = $rule->get_status();
			update_post_meta( $id, self::META_CONFIG, $config );
		}

		do_action( 'wpcalibrate_access_rule_status_changed', $id, $status );
		return true;
	}

	/**
	 * Find rules matching query parameters.
	 *
	 * @param array<string, mixed> $args Query arguments (status, search, paged, per_page, orderby, order).
	 * @return AccessRule[]
	 */
	public function find( array $args = array() ): array {
		$defaults = array(
			'status'   => '',
			'search'   => '',
			'paged'    => 1,
			'per_page' => 20,
			'orderby'  => 'priority',
			'order'    => 'DESC',
		);
		$parsed = wp_parse_args( $args, $defaults );

		$query_args = array(
			'post_type'              => self::POST_TYPE,
			'post_status'            => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page'         => (int) $parsed['per_page'],
			'paged'                  => (int) $parsed['paged'],
			'no_found_rows'          => false,
			'update_post_term_cache' => false,
		);

		if ( ! empty( $parsed['search'] ) ) {
			$query_args['s'] = sanitize_text_field( (string) $parsed['search'] );
		}

		$meta_query = array();
		if ( ! empty( $parsed['status'] ) ) {
			$meta_query[] = array(
				'key'     => self::META_STATUS,
				'value'   => sanitize_key( (string) $parsed['status'] ),
				'compare' => '=',
			);
		}

		if ( ! empty( $meta_query ) ) {
			$query_args['meta_query'] = $meta_query;
		}

		// Sorting.
		if ( 'priority' === $parsed['orderby'] ) {
			$query_args['meta_key'] = self::META_PRIORITY;
			$query_args['orderby']  = 'meta_value_num';
			$query_args['order']    = strtoupper( (string) $parsed['order'] ) === 'ASC' ? 'ASC' : 'DESC';
		} elseif ( 'title' === $parsed['orderby'] ) {
			$query_args['orderby'] = 'title';
			$query_args['order']   = strtoupper( (string) $parsed['order'] ) === 'DESC' ? 'DESC' : 'ASC';
		} elseif ( 'date' === $parsed['orderby'] ) {
			$query_args['orderby'] = 'date';
			$query_args['order']   = strtoupper( (string) $parsed['order'] ) === 'ASC' ? 'ASC' : 'DESC';
		} else {
			$query_args['orderby'] = 'ID';
			$query_args['order']   = 'DESC';
		}

		$query = new \WP_Query( $query_args );
		$rules = array();

		foreach ( $query->posts as $post ) {
			$rule = $this->get( (int) $post->ID );
			if ( $rule ) {
				$rules[] = $rule;
			}
		}

		return $rules;
	}

	/**
	 * Count total rules matching query arguments.
	 *
	 * @param array<string, mixed> $args
	 * @return int
	 */
	public function count( array $args = array() ): int {
		$query_args = array(
			'post_type'      => self::POST_TYPE,
			'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page' => 1,
			'fields'         => 'ids',
		);

		if ( ! empty( $args['search'] ) ) {
			$query_args['s'] = sanitize_text_field( (string) $args['search'] );
		}

		if ( ! empty( $args['status'] ) ) {
			$query_args['meta_query'] = array(
				array(
					'key'     => self::META_STATUS,
					'value'   => sanitize_key( (string) $args['status'] ),
					'compare' => '=',
				),
			);
		}

		$query = new \WP_Query( $query_args );
		return (int) $query->found_posts;
	}

	/**
	 * Get all active rules assigned to a specific content item (post/page/product), sorted by priority DESC.
	 *
	 * @param int $post_id Content post ID.
	 * @return AccessRule[]
	 */
	public function get_rules_for_content( int $post_id ): array {
		$rule_ids = get_post_meta( $post_id, self::META_ASSIGNMENT, true );
		if ( ! is_array( $rule_ids ) || empty( $rule_ids ) ) {
			return array();
		}

		$rules = array();
		foreach ( $rule_ids as $rid ) {
			$rule = $this->get( (int) $rid );
			if ( $rule && $rule->is_active() ) {
				$rules[] = $rule;
			}
		}

		// Sort by priority descending (higher priority evaluated first).
		usort(
			$rules,
			static function ( AccessRule $a, AccessRule $b ): int {
				return $b->get_priority() <=> $a->get_priority();
			}
		);

		return $rules;
	}

	/**
	 * Count how many content items are assigned to this rule.
	 * Uses an efficient query limited to IDs.
	 *
	 * @param int $rule_id
	 * @return int
	 */
	public function count_assignments( int $rule_id ): int {
		global $wpdb;

		// Assignments are stored as serialized arrays in postmeta: e.g. a:1:{i:0;i:123;}
		// Or individual entries. We can do a safe LIKE query with regex or serialized pattern.
		$like = '%' . $wpdb->esc_like( (string) $rule_id ) . '%';
		$sql  = $wpdb->prepare(
			"SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value LIKE %s",
			self::META_ASSIGNMENT,
			$like
		);

		return (int) $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	}
}
