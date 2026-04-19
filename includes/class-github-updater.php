<?php
/**
 * GitHub Plugin Updater
 * Enables automatic updates from GitHub repository
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Team_Payroll_GitHub_Updater {

	private $plugin_slug;
	private $plugin_file;
	private $github_repo;
	private $github_user;
	private $github_branch;
	private $github_api_url;

	public function __construct() {
		$this->plugin_slug = 'woocommerce-team-payroll';
		$this->plugin_file = 'woocommerce-team-payroll/woocommerce-team-payroll.php';
		$this->github_user = 'imranduzzlo';
		$this->github_repo = 'pv-team-management';
		$this->github_branch = 'main';
		$this->github_api_url = "https://api.github.com/repos/{$this->github_user}/{$this->github_repo}";

		// Hook into WordPress update checks
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_info' ), 10, 3 );
		
		// Clear cache on plugin activation
		add_action( 'activated_plugin', array( $this, 'clear_cache' ) );
		
		// Force check on admin page load
		add_action( 'admin_init', array( $this, 'force_check' ) );
	}

	/**
	 * Clear update cache
	 */
	public function clear_cache() {
		delete_transient( 'wc_tp_github_release' );
		delete_transient( 'wc_tp_last_update_check' );
		delete_transient( 'update_plugins' );
	}

	/**
	 * Force update check on admin init
	 */
	public function force_check() {
		// Clear cache to force fresh check
		delete_transient( 'wc_tp_github_release' );
		delete_transient( 'wc_tp_last_update_check' );
		
		// Trigger WordPress update check
		wp_update_plugins();
	}

	/**
	 * Check for plugin updates from GitHub
	 */
	public function check_for_update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		// Initialize response if not set
		if ( ! isset( $transient->response ) ) {
			$transient->response = array();
		}

		$current_version = $this->get_current_version();

		// Get latest release from GitHub
		$latest_release = $this->get_latest_release();

		if ( ! $latest_release ) {
			// Remove from response if no release found
			unset( $transient->response[ $this->plugin_file ] );
			return $transient;
		}

		// Normalize versions for comparison
		$latest_version = $this->normalize_version( $latest_release['version'] );
		$current_version_normalized = $this->normalize_version( $current_version );

		// Debug logging
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( "WC Team Payroll Update Check: Current={$current_version_normalized}, Latest={$latest_version}, Compare=" . version_compare( $latest_version, $current_version_normalized, '>' ) );
		}

		// Only add to response if there's a newer version
		if ( version_compare( $latest_version, $current_version_normalized, '>' ) ) {
			$transient->response[ $this->plugin_file ] = (object) array(
				'id'            => $this->github_repo,
				'slug'          => $this->plugin_slug,
				'plugin'        => $this->plugin_file,
				'new_version'   => $latest_version,
				'url'           => $latest_release['url'],
				'package'       => $latest_release['download_url'],
				'tested'        => '6.4',
				'requires'      => '5.0',
				'requires_php'  => '7.2',
				'icons'         => array(),
				'banners'       => array(),
				'upgrade_notice' => 'New version available from GitHub',
			);
		} else {
			// Explicitly remove from response if versions are equal or current is newer
			if ( isset( $transient->response[ $this->plugin_file ] ) ) {
				unset( $transient->response[ $this->plugin_file ] );
			}
		}

		return $transient;
	}

	/**
	 * Get current plugin version
	 */
	private function get_current_version() {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// Get the plugin file path
		$plugin_file_path = WP_PLUGIN_DIR . '/' . $this->plugin_file;
		
		// Make sure the file exists
		if ( ! file_exists( $plugin_file_path ) ) {
			return '0';
		}

		$plugin_data = get_plugin_data( $plugin_file_path );
		$version = isset( $plugin_data['Version'] ) ? $plugin_data['Version'] : '0';
		
		// Debug log
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( "WC Team Payroll: Reading version from {$plugin_file_path}: {$version}" );
		}
		
		return $version;
	}

	/**
	 * Normalize version string
	 */
	private function normalize_version( $version ) {
		// Remove 'v' prefix if present
		$version = ltrim( $version, 'v' );
		// Remove any whitespace
		$version = trim( $version );
		// Ensure it's a valid version format (add .0 if needed)
		if ( ! preg_match( '/^\d+\.\d+\.\d+/', $version ) ) {
			// If it's just X.Y, add .0
			if ( preg_match( '/^\d+\.\d+$/', $version ) ) {
				$version = $version . '.0';
			} else {
				$version = '0.0.0';
			}
		}
		return $version;
	}

	/**
	 * Get plugin info for the update modal
	 */
	public function plugin_info( $result, $action, $args ) {
		if ( $action !== 'plugin_information' ) {
			return $result;
		}

		if ( $args->slug !== $this->plugin_slug ) {
			return $result;
		}

		$latest_release = $this->get_latest_release();

		if ( ! $latest_release ) {
			return $result;
		}

		$result = (object) array(
			'name'            => 'WooCommerce Team Payroll & Commission System',
			'slug'            => $this->plugin_slug,
			'version'         => $latest_release['version'],
			'author'          => 'Imran',
			'author_profile'  => 'https://imranhossain.me/',
			'download_link'   => $latest_release['download_url'],
			'trunk'           => $latest_release['download_url'],
			'requires'        => '5.0',
			'requires_php'    => '7.2',
			'tested'          => '6.4',
			'last_updated'    => $latest_release['updated'],
			'sections'        => array(
				'description' => 'Manage team-based commission and payroll system with agents and processors',
				'changelog'   => $this->get_changelog(),
			),
			'banners'         => array(),
			'icons'           => array(),
		);

		return $result;
	}

	/**
	 * Get latest release from GitHub
	 */
	private function get_latest_release() {
		$transient_key = 'wc_tp_github_release';
		$cached = get_transient( $transient_key );

		if ( $cached !== false ) {
			return $cached;
		}

		$response = wp_remote_get(
			"{$this->github_api_url}/releases/latest",
			array(
				'timeout'   => 10,
				'sslverify' => true,
				'headers'   => array(
					'Accept' => 'application/vnd.github.v3+json',
					'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ),
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'WC Team Payroll GitHub API Error: ' . $response->get_error_message() );
			}
			return false;
		}

		$http_code = wp_remote_retrieve_response_code( $response );
		if ( $http_code !== 200 ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( "WC Team Payroll GitHub API HTTP {$http_code}: " . wp_remote_retrieve_body( $response ) );
			}
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$release = json_decode( $body, true );

		if ( ! isset( $release['tag_name'] ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'WC Team Payroll GitHub API: No tag_name in response' );
			}
			return false;
		}

		$version = ltrim( $release['tag_name'], 'v' );
		$download_url = "{$this->github_api_url}/zipball/{$release['tag_name']}";

		$result = array(
			'version'      => $version,
			'url'          => $release['html_url'],
			'download_url' => $download_url,
			'updated'      => $release['published_at'],
		);

		// Cache for 12 hours (longer cache to avoid rate limiting)
		set_transient( $transient_key, $result, 12 * HOUR_IN_SECONDS );

		return $result;
	}

	/**
	 * Get changelog from GitHub releases
	 */
	private function get_changelog() {
		$response = wp_remote_get(
			"{$this->github_api_url}/releases",
			array(
				'timeout'   => 10,
				'sslverify' => true,
				'headers'   => array(
					'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ),
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return 'Unable to fetch changelog from GitHub.';
		}

		$body = wp_remote_retrieve_body( $response );
		$releases = json_decode( $body, true );

		if ( ! is_array( $releases ) || empty( $releases ) ) {
			return 'No releases found.';
		}

		$changelog = '<ul>';
		foreach ( array_slice( $releases, 0, 5 ) as $release ) {
			$changelog .= '<li><strong>' . esc_html( $release['tag_name'] ) . '</strong> - ' . esc_html( $release['published_at'] ) . '<br/>';
			$changelog .= wp_kses_post( wpautop( $release['body'] ) ) . '</li>';
		}
		$changelog .= '</ul>';

		return $changelog;
	}
}

// Initialize updater on plugins_loaded to ensure plugin version is defined
add_action( 'plugins_loaded', function() {
	new WC_Team_Payroll_GitHub_Updater();
}, 25 );
