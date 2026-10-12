<?php
/**
 * Native GitHub Release Updater for WordPress Dashboard.
 *
 * Enables seamless WordPress Dashboard update checks, notifications,
 * and one-click upgrades directly from GitHub releases.
 *
 * @package WPCalibrate\CommerceAccessRules
 */

declare(strict_types=1);

namespace WPCalibrate\CommerceAccessRules\Infrastructure\Update;

/**
 * GitHubUpdater class.
 */
final class GitHubUpdater {

	private string $plugin_file;
	private string $plugin_basename;
	private string $slug;
	private string $version;
	private string $repo; // e.g. "zeeshanraza-official/wpcalibrate-commerce-access-rules"
	private string $transient_key;

	/**
	 * Constructor.
	 *
	 * @param string $plugin_file Main plugin file path.
	 * @param string $repo        GitHub repository in "owner/repo" format.
	 * @param string $version     Current plugin version.
	 */
	public function __construct( string $plugin_file, string $repo, string $version ) {
		$this->plugin_file     = $plugin_file;
		$this->plugin_basename = plugin_basename( $plugin_file );
		$this->slug            = dirname( $this->plugin_basename );
		$this->version         = $version;
		$this->repo            = $repo;
		$this->transient_key   = 'wpcal_gh_update_' . md5( $repo );
	}

	/**
	 * Register hooks with WordPress core updater lifecycle.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_popup_info' ), 20, 3 );
		add_filter( 'upgrader_post_install', array( $this, 'post_install_rename' ), 10, 3 );
	}

	/**
	 * Check GitHub for latest release and populate update transient if a newer version exists.
	 *
	 * @param object|false $transient The update_plugins transient object.
	 * @return object|false
	 */
	public function check_for_update( $transient ) {
		if ( empty( $transient->checked ) || ! is_object( $transient ) ) {
			return $transient;
		}

		$release = $this->get_latest_release();
		if ( null === $release || empty( $release['tag_name'] ) ) {
			return $transient;
		}

		$remote_version = ltrim( (string) $release['tag_name'], 'v' );

		if ( version_compare( $remote_version, $this->version, '>' ) ) {
			$download_url = $this->get_package_url( $release );

			$item = (object) array(
				'id'            => 'github.com/' . $this->repo,
				'slug'          => $this->slug,
				'plugin'        => $this->plugin_basename,
				'new_version'   => $remote_version,
				'url'           => 'https://github.com/' . $this->repo,
				'package'       => $download_url,
				'icons'         => array(),
				'banners'       => array(),
				'banners_rtl'   => array(),
				'tested'        => '7.1.3',
				'requires_php'  => '8.2',
				'compatibility' => new \stdClass(),
			);

			$transient->response[ $this->plugin_basename ] = $item;
			unset( $transient->no_update[ $this->plugin_basename ] );
		} else {
			$item = (object) array(
				'id'            => 'github.com/' . $this->repo,
				'slug'          => $this->slug,
				'plugin'        => $this->plugin_basename,
				'new_version'   => $this->version,
				'url'           => 'https://github.com/' . $this->repo,
				'package'       => '',
				'tested'        => '7.1.3',
				'requires_php'  => '8.2',
			);
			$transient->no_update[ $this->plugin_basename ] = $item;
			unset( $transient->response[ $this->plugin_basename ] );
		}

		return $transient;
	}

	/**
	 * Provide plugin information modal data when clicking "View version X details" in dashboard.
	 *
	 * @param false|object|array $result Result from previous filters.
	 * @param string             $action Action name.
	 * @param object             $args   Query args.
	 * @return false|object
	 */
	public function plugin_popup_info( $result, string $action, object $args ) {
		if ( 'plugin_information' !== $action || ! isset( $args->slug ) || $args->slug !== $this->slug ) {
			return $result;
		}

		$release = $this->get_latest_release();
		if ( null === $release ) {
			return $result;
		}

		$remote_version = ltrim( (string) $release['tag_name'], 'v' );
		$body           = ! empty( $release['body'] ) ? wp_kses_post( wpautop( $release['body'] ) ) : 'Please see GitHub repository for release notes.';

		return (object) array(
			'name'          => 'WPCalibrate Content Restriction',
			'slug'          => $this->slug,
			'version'       => $remote_version,
			'author'        => '<a href="https://wpcalibrate.com">WPCalibrate</a>',
			'homepage'      => 'https://github.com/' . $this->repo,
			'requires'      => '6.5',
			'tested'        => '7.1.3',
			'requires_php'  => '8.2',
			'download_link' => $this->get_package_url( $release ),
			'sections'      => array(
				'description' => 'Restricts access to WordPress content according to WooCommerce purchases, customers, roles, dates, and configurable access rules.',
				'changelog'   => $body,
			),
		);
	}

	/**
	 * Normalize plugin folder directory name after upgrader extracts release archive.
	 *
	 * When downloading from GitHub, root folders may have repository/branch prefixes
	 * (e.g. zeeshanraza-official-wpcalibrate-commerce-access-rules-xxx).
	 * This hook ensures it restores to the canonical plugin slug directory.
	 *
	 * @param bool  $response   Install response.
	 * @param array $hook_extra Extra hook data.
	 * @param array $result     Installation result.
	 * @return array
	 */
	public function post_install_rename( $response, array $hook_extra, array $result ): array {
		global $wp_filesystem;

		if ( ! isset( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->plugin_basename ) {
			return $result;
		}

		if ( empty( $result['destination'] ) || empty( $wp_filesystem ) ) {
			return $result;
		}

		$proper_destination = WP_PLUGIN_DIR . '/' . $this->slug;

		if ( trailingslashit( $result['destination'] ) !== trailingslashit( $proper_destination ) ) {
			$wp_filesystem->move( $result['destination'], $proper_destination );
			$result['destination'] = $proper_destination;
		}

		return $result;
	}

	/**
	 * Fetch latest release from GitHub API with transient caching.
	 *
	 * @return array<string, mixed>|null
	 */
	private function get_latest_release(): ?array {
		$force = isset( $_GET['force-check'] ) && is_admin(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! $force ) {
			$cached = get_site_transient( $this->transient_key );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}

		$url      = 'https://api.github.com/repos/' . $this->repo . '/releases/latest';
		$response = wp_remote_get(
			$url,
			array(
				'timeout'    => 10,
				'user-agent' => 'WPCalibrate-Content-Restriction-Updater/' . $this->version . '; ' . home_url(),
				'headers'    => array(
					'Accept' => 'application/vnd.github.v3+json',
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			// Cache failure for 1 hour to prevent flooding GitHub API rate limits.
			set_site_transient( $this->transient_key, array(), HOUR_IN_SECONDS );
			return null;
		}

		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) || empty( $body['tag_name'] ) ) {
			set_site_transient( $this->transient_key, array(), HOUR_IN_SECONDS );
			return null;
		}

		// Cache valid release for 12 hours.
		set_site_transient( $this->transient_key, $body, 12 * HOUR_IN_SECONDS );

		return $body;
	}

	/**
	 * Determine best package download URL from release assets or zipball fallback.
	 *
	 * @param array<string, mixed> $release
	 * @return string
	 */
	private function get_package_url( array $release ): string {
		// Prefer attached zip asset if available.
		if ( ! empty( $release['assets'] ) && is_array( $release['assets'] ) ) {
			foreach ( $release['assets'] as $asset ) {
				if ( ! empty( $asset['browser_download_url'] ) && str_ends_with( (string) $asset['name'], '.zip' ) ) {
					return (string) $asset['browser_download_url'];
				}
			}
		}

		// Fallback to repository zipball.
		return ! empty( $release['zipball_url'] ) ? (string) $release['zipball_url'] : '';
	}
}
