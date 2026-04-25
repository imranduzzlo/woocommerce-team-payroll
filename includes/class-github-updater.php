<?php
/**
 * GitHub Plugin Updater
 * Enables automatic updates from GitHub repository
 * Based on WordPress best practices and proper update_plugins_{$hostname} filter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Team_Payroll_GitHub_Updater {

	private $file;
	private $plugin_slug;
	private $plugin_basename;
	private $github_user;
	private $github_repo;
	private $github_url;
	private $plugin_data;

	public function __construct( $file ) {
		$this->file = $file;
		$this->plugin_basename = plugin_basename( $file );
		
		// Load plugin data
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		
		$this->plugin_data = get_plugin_data( $file );
		
		// Parse GitHub URL from Plugin URI
		$plugin_uri = $this->plugin_data['PluginURI'] ?? '';
		if ( $plugin_uri && strpos( $plugin_uri, 'github.com' ) !== false ) {
			$path = parse_url( $plugin_uri, PHP_URL_PATH );
			$parts = explode( '/', trim( $path, '/' ) );
			if ( count( $parts ) >= 2 ) {
				$this->github_user = $parts[0];
				$this->github_repo = $parts[1];
				$this->github_url = "https://github.com/{$this->github_user}/{$this->github_repo}";
				$this->plugin_slug = dirname( $this->plugin_basename );
			}
		}
		
		$this->init();
	}

	private function init() {
		if ( ! $this->github_user || ! $this->github_repo ) {
			return;
		}

		// Hook into WordPress update system using the proper filter
		add_filter( 'update_plugins_github.com', array( $this, 'check_for_update' ), 10, 3 );
		
		// Plugin information for the modal
		add_filter( 'plugins_api', array( $this, 'plugin_information' ), 10, 3 );
		
		// Fix directory name after update
		add_filter( 'upgrader_install_package_result', array( $this, 'fix_plugin_directory' ), 10, 2 );
		
		// Update plugin details URL
		add_filter( 'admin_url', array( $this, 'update_plugin_details_url' ), 10, 2 );
	}

	/**
	 * Check for updates using the proper WordPress filter
	 */
	public function check_for_update( $update, $plugin_data, $plugin_file ) {
		// Only process our plugin
		if ( $plugin_file !== $this->plugin_basename ) {
			return $update;
		}

		// Get latest release from GitHub
		$release = $this->get_latest_release();
		
		if ( ! $release ) {
			return false;
		}

		$current_version = $this->plugin_data['Version'] ?? '0';
		$latest_version = ltrim( $release['tag_name'], 'v' );

		// Only return update if newer version available
		if ( version_compare( $latest_version, $current_version, '<=' ) ) {
			return false;
		}

		// Return update information
		return array(
			'id'            => $this->github_url,
			'slug'          => $this->plugin_slug,
			'plugin'        => $this->plugin_basename,
			'version'       => $latest_version,
			'new_version'   => $latest_version,
			'url'           => $release['html_url'],
			'package'       => $release['zipball_url'],
			'tested'        => '6.7',
			'requires'      => '5.0',
			'requires_php'  => '7.2',
			'icons'         => array(),
			'banners'       => array(),
		);
	}

	/**
	 * Get latest release from GitHub API
	 */
	private function get_latest_release() {
		$transient_key = 'wc_tp_github_release_v2';
		$cached = get_transient( $transient_key );

		if ( $cached !== false ) {
			return $cached;
		}

		$api_url = "https://api.github.com/repos/{$this->github_user}/{$this->github_repo}/releases/latest";
		
		$response = wp_remote_get( $api_url, array(
			'headers' => array(
				'Accept' => 'application/vnd.github.v3+json',
			),
		) );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$release = json_decode( $body, true );

		if ( ! isset( $release['tag_name'] ) ) {
			return false;
		}

		// Cache for 6 hours
		set_transient( $transient_key, $release, 6 * HOUR_IN_SECONDS );

		return $release;
	}

	/**
	 * Plugin information for the details modal
	 */
	public function plugin_information( $result, $action, $args ) {
		if ( $action !== 'plugin_information' ) {
			return $result;
		}

		if ( ! isset( $args->slug ) || $args->slug !== $this->plugin_slug ) {
			return $result;
		}

		$release = $this->get_latest_release();
		
		if ( ! $release ) {
			return $result;
		}

		$latest_version = ltrim( $release['tag_name'], 'v' );

		return (object) array(
			'name'          => $this->plugin_data['Name'],
			'slug'          => $this->plugin_slug,
			'version'       => $latest_version,
			'author'        => $this->plugin_data['Author'],
			'homepage'      => $this->github_url,
			'download_link' => $release['zipball_url'],
			'requires'      => '5.0',
			'requires_php'  => '7.2',
			'tested'        => '6.7',
			'last_updated'  => $release['published_at'],
			'sections'      => array(
				'description' => $this->plugin_data['Description'],
				'changelog'   => $this->format_changelog( $release['body'] ?? '' ),
			),
		);
	}

	/**
	 * Format changelog from GitHub release notes
	 */
	private function format_changelog( $body ) {
		if ( empty( $body ) ) {
			return '<p>No changelog available.</p>';
		}

		// Convert markdown to HTML (basic)
		$body = wp_kses_post( wpautop( $body ) );
		
		return $body;
	}

	/**
	 * Fix plugin directory name after update
	 * GitHub zipballs extract to {user}-{repo}-{commit} format
	 */
	public function fix_plugin_directory( $result, $hook_extra ) {
		global $wp_filesystem;

		// Only process our plugin
		if ( ! isset( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->plugin_basename ) {
			return $result;
		}

		$plugin_dir = WP_PLUGIN_DIR . '/' . $this->plugin_slug;
		$new_plugin_dir = $result['destination'];

		// If already correct, return
		if ( $new_plugin_dir === $plugin_dir ) {
			return $result;
		}

		// Move to correct directory
		if ( $wp_filesystem->move( $new_plugin_dir, $plugin_dir, true ) ) {
			$result['destination'] = $plugin_dir;
			$result['destination_name'] = $this->plugin_slug;
		}

		return $result;
	}

	/**
	 * Update plugin details URL to point to GitHub
	 */
	public function update_plugin_details_url( $url, $path ) {
		if ( strpos( $path, 'plugin-install.php' ) === false ) {
			return $url;
		}

		if ( strpos( $url, 'plugin=' . $this->plugin_slug ) === false ) {
			return $url;
		}

		// Redirect to GitHub releases page
		return $this->github_url . '/releases';
	}
}

// Initialize updater - runs even when plugin is inactive
if ( file_exists( WP_PLUGIN_DIR . '/woocommerce-team-payroll/woocommerce-team-payroll.php' ) ) {
	new WC_Team_Payroll_GitHub_Updater( WP_PLUGIN_DIR . '/woocommerce-team-payroll/woocommerce-team-payroll.php' );
}
