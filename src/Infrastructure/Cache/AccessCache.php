<?php
/**
 * Safe User-Scoped Access Cache.
 *
 * @package WPCalibrate\CommerceAccessRules
 */

declare(strict_types=1);

namespace WPCalibrate\CommerceAccessRules\Infrastructure\Cache;

use WPCalibrate\CommerceAccessRules\Domain\Access\AccessResult;

/**
 * AccessCache class.
 */
final class AccessCache {

	/**
	 * In-memory request cache: [ user_id => [ context_key => AccessResult ] ].
	 *
	 * @var array<int, array<string, AccessResult>>
	 */
	private array $runtime_cache = array();

	/**
	 * Transient cache prefix.
	 */
	private const TRANSIENT_PREFIX = 'wpcal_acc_';

	/**
	 * Get cached result for a user and context key.
	 *
	 * @param int    $user_id     User ID (0 for guest).
	 * @param string $context_key Unique context key (e.g. "post_123", "rule_45").
	 * @return AccessResult|null Cached result or null.
	 */
	public function get( int $user_id, string $context_key ): ?AccessResult {
		// 1. Check runtime memory cache first.
		if ( isset( $this->runtime_cache[ $user_id ][ $context_key ] ) ) {
			$result = $this->runtime_cache[ $user_id ][ $context_key ];
			// Ensure request-time expiry has not passed.
			if ( $this->is_expired( $result ) ) {
				unset( $this->runtime_cache[ $user_id ][ $context_key ] );
				return null;
			}
			return $result;
		}

		// 2. Check transient if persistent cache is enabled in settings.
		if ( ! $this->is_persistent_cache_enabled() || $user_id <= 0 ) {
			return null;
		}

		$transient_key = $this->build_transient_key( $user_id, $context_key );
		$cached_data   = get_transient( $transient_key );

		if ( is_array( $cached_data ) && isset( $cached_data['allowed'], $cached_data['reason_code'] ) ) {
			$result = new AccessResult(
				(bool) $cached_data['allowed'],
				(string) $cached_data['reason_code'],
				isset( $cached_data['rule_id'] ) ? (int) $cached_data['rule_id'] : null,
				$cached_data['grant_source'] ?? null,
				isset( $cached_data['expiry'] ) ? (int) $cached_data['expiry'] : null,
				$cached_data['denied_action'] ?? null,
				$cached_data['redirect_url'] ?? null,
				$cached_data['custom_message'] ?? null,
				isset( $cached_data['qualifying_product_id'] ) ? (int) $cached_data['qualifying_product_id'] : null,
				$cached_data['purchase_button_text'] ?? null
			);

			// Validate expiry at request time!
			if ( $this->is_expired( $result ) ) {
				$this->delete_transient( $transient_key );
				return null;
			}

			// Store in runtime cache for the rest of this request.
			$this->runtime_cache[ $user_id ][ $context_key ] = $result;
			return $result;
		}

		return null;
	}

	/**
	 * Store result in cache.
	 *
	 * @param int          $user_id     User ID (0 for guest).
	 * @param string       $context_key Context key.
	 * @param AccessResult $result      Evaluation result.
	 * @return void
	 */
	public function set( int $user_id, string $context_key, AccessResult $result ): void {
		// Store in runtime memory cache.
		if ( ! isset( $this->runtime_cache[ $user_id ] ) ) {
			$this->runtime_cache[ $user_id ] = array();
		}
		$this->runtime_cache[ $user_id ][ $context_key ] = $result;

		// If persistent caching is enabled and user is logged in, cache transient for short TTL (max 10 minutes).
		if ( $this->is_persistent_cache_enabled() && $user_id > 0 ) {
			$transient_key = $this->build_transient_key( $user_id, $context_key );
			$ttl = 600; // 10 minutes max.

			// If expiry is sooner than TTL, bound by expiry.
			if ( null !== $result->get_expiry() ) {
				$seconds_until_expiry = $result->get_expiry() - time();
				if ( $seconds_until_expiry <= 0 ) {
					return; // Don't cache already expired grant.
				}
				$ttl = min( $ttl, $seconds_until_expiry );
			}

			set_transient( $transient_key, $result->to_array(), $ttl );
		}
	}

	/**
	 * Invalidate all cached data for a specific user.
	 *
	 * @param int $user_id User ID.
	 * @return void
	 */
	public function invalidate_user( int $user_id ): void {
		unset( $this->runtime_cache[ $user_id ] );

		// Increment user's cache version bump.
		$current_ver = (int) get_user_meta( $user_id, 'wpcal_cache_ver', true );
		update_user_meta( $user_id, 'wpcal_cache_ver', $current_ver + 1 );
	}

	/**
	 * Invalidate all caches globally (on rule save/delete, settings update, etc.).
	 *
	 * @return void
	 */
	public function flush(): void {
		$this->runtime_cache = array();

		// Bump global cache version.
		$ver = (int) get_option( 'wpcal_global_cache_ver', 1 );
		update_option( 'wpcal_global_cache_ver', $ver + 1, false );
	}

	/**
	 * Check if result has expired at request time.
	 *
	 * @param AccessResult $result
	 * @return bool
	 */
	private function is_expired( AccessResult $result ): bool {
		$expiry = $result->get_expiry();
		return null !== $expiry && $expiry < time();
	}

	/**
	 * Build transient key including user-specific version and global version.
	 *
	 * @param int    $user_id
	 * @param string $context_key
	 * @return string
	 */
	private function build_transient_key( int $user_id, string $context_key ): string {
		$global_ver = (int) get_option( 'wpcal_global_cache_ver', 1 );
		$user_ver   = (int) get_user_meta( $user_id, 'wpcal_cache_ver', true );
		$raw        = "{$user_id}_{$user_ver}_{$global_ver}_{$context_key}";
		return self::TRANSIENT_PREFIX . md5( $raw );
	}

	/**
	 * Check if persistent caching is enabled in settings.
	 *
	 * @return bool
	 */
	private function is_persistent_cache_enabled(): bool {
		return (bool) get_option( 'wpcal_enable_cache', true );
	}

	/**
	 * Delete transient safely.
	 *
	 * @param string $key
	 * @return void
	 */
	private function delete_transient( string $key ): void {
		delete_transient( $key );
	}
}
