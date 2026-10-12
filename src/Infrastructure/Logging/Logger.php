<?php
/**
 * Diagnostic Logger.
 *
 * @package WPCalibrate\CommerceAccessRules
 */

declare(strict_types=1);

namespace WPCalibrate\CommerceAccessRules\Infrastructure\Logging;

/**
 * Logger class.
 */
final class Logger {

	public const SOURCE = 'wpcalibrate-commerce-access-rules';

	/**
	 * Log a message.
	 *
	 * @param string               $level   Log level ('debug', 'info', 'notice', 'warning', 'error', 'critical').
	 * @param string               $message Log message.
	 * @param array<string, mixed> $context Additional context data.
	 * @return void
	 */
	public static function log( string $level, string $message, array $context = array() ): void {
		$enabled = (bool) get_option( 'wpcal_enable_logging', false );
		if ( ! $enabled && 'error' !== $level && 'critical' !== $level ) {
			return;
		}

		if ( ! function_exists( 'wc_get_logger' ) ) {
			return;
		}

		$logger  = wc_get_logger();
		$masked  = self::mask_sensitive_data( $context );
		$context_clean = array_merge( array( 'source' => self::SOURCE ), $masked );

		$logger->log( $level, self::mask_string( $message ), $context_clean );
	}

	/**
	 * Log debug message.
	 *
	 * @param string               $message
	 * @param array<string, mixed> $context
	 * @return void
	 */
	public static function debug( string $message, array $context = array() ): void {
		self::log( 'debug', $message, $context );
	}

	/**
	 * Log error message.
	 *
	 * @param string               $message
	 * @param array<string, mixed> $context
	 * @return void
	 */
	public static function error( string $message, array $context = array() ): void {
		self::log( 'error', $message, $context );
	}

	/**
	 * Mask sensitive keys in an array.
	 *
	 * @param array<string, mixed> $data
	 * @return array<string, mixed>
	 */
	public static function mask_sensitive_data( array $data ): array {
		$sensitive_keys = array( 'license_key', 'key', 'password', 'secret', 'token', 'auth' );
		$clean = array();

		foreach ( $data as $k => $v ) {
			if ( is_array( $v ) ) {
				$clean[ $k ] = self::mask_sensitive_data( $v );
			} elseif ( is_string( $k ) && in_array( strtolower( $k ), $sensitive_keys, true ) ) {
				$clean[ $k ] = '••••••••';
			} elseif ( is_string( $v ) ) {
				$clean[ $k ] = self::mask_string( $v );
			} else {
				$clean[ $k ] = $v;
			}
		}

		return $clean;
	}

	/**
	 * Mask sensitive strings.
	 *
	 * @param string $str
	 * @return string
	 */
	public static function mask_string( string $str ): string {
		// Pattern for potential 32/40-char API or license keys:
		return (string) preg_replace( '/([a-zA-Z0-9]{4})[a-zA-Z0-9]{12,}([a-zA-Z0-9]{4})/', '$1••••••••$2', $str );
	}
}
