<?php
/**
 * Content-to-Rule Assignment Repository.
 *
 * @package WPCalibrate\CommerceAccessRules
 */

declare(strict_types=1);

namespace WPCalibrate\CommerceAccessRules\Content;

use WPCalibrate\CommerceAccessRules\Domain\Rule\RuleRepository;

/**
 * AssignmentRepository class.
 */
final class AssignmentRepository {

	/**
	 * Get assigned rule IDs for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return int[] List of rule IDs.
	 */
	public static function get_assigned_rule_ids( int $post_id ): array {
		$raw = get_post_meta( $post_id, RuleRepository::META_ASSIGNMENT, true );
		if ( ! is_array( $raw ) ) {
			return array();
		}

		return array_values( array_unique( array_filter( array_map( 'absint', $raw ) ) ) );
	}

	/**
	 * Save assigned rule IDs for a post.
	 *
	 * @param int   $post_id  Post ID.
	 * @param int[] $rule_ids List of rule IDs.
	 * @return void
	 */
	public static function set_assigned_rule_ids( int $post_id, array $rule_ids ): void {
		$clean_ids = array_values( array_unique( array_filter( array_map( 'absint', $rule_ids ) ) ) );

		if ( empty( $clean_ids ) ) {
			delete_post_meta( $post_id, RuleRepository::META_ASSIGNMENT );
		} else {
			update_post_meta( $post_id, RuleRepository::META_ASSIGNMENT, $clean_ids );
		}

		do_action( 'wpcalibrate_content_rules_updated', $post_id, $clean_ids );
	}

	/**
	 * Check if a post has any restriction rules assigned.
	 *
	 * @param int $post_id
	 * @return bool
	 */
	public static function has_restrictions( int $post_id ): bool {
		$rules = self::get_assigned_rule_ids( $post_id );
		return ! empty( $rules );
	}
}
