<?php
/**
 * PSR-4 Autoloader for WPCalibrate Content Restriction.
 *
 * @package WPCalibrate\CommerceAccessRules
 */

declare(strict_types=1);

namespace WPCalibrate\CommerceAccessRules;

/**
 * Autoloader class.
 */
final class Autoloader {

	/**
	 * Namespace prefix.
	 *
	 * @var string
	 */
	private const PREFIX = 'WPCalibrate\\CommerceAccessRules\\';

	/**
	 * Base directory for the namespace prefix.
	 *
	 * @var string
	 */
	private string $base_dir;

	/**
	 * Constructor.
	 *
	 * @param string $base_dir Base directory path for source files.
	 */
	public function __construct( string $base_dir ) {
		$this->base_dir = rtrim( $base_dir, '/\\' ) . DIRECTORY_SEPARATOR;
	}

	/**
	 * Register the autoloader.
	 *
	 * @param string $base_dir Base directory path.
	 * @return self
	 */
	public static function register( string $base_dir ): self {
		$loader = new self( $base_dir );
		spl_autoload_register( array( $loader, 'load' ) );
		return $loader;
	}

	/**
	 * Load a class file if it matches our namespace.
	 *
	 * @param string $class Fully-qualified class name.
	 * @return bool True if loaded, false otherwise.
	 */
	public function load( string $class ): bool {
		// Does the class use the namespace prefix?
		$len = strlen( self::PREFIX );
		if ( strncmp( self::PREFIX, $class, $len ) !== 0 ) {
			return false;
		}

		// Get the relative class name.
		$relative_class = substr( $class, $len );

		// Replace namespace separators with directory separators.
		$file = $this->base_dir . str_replace( '\\', DIRECTORY_SEPARATOR, $relative_class ) . '.php';

		if ( file_exists( $file ) ) {
			require_once $file;
			return true;
		}

		return false;
	}
}
