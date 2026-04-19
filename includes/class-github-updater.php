<?php
/**
 * GitHub Plugin Updater
 * 
 * Professional GitHub updater for WordPress plugins
 * Handles automatic updates from GitHub releases
 * 
 * @package WC_Team_Payroll
 * @version 1.6.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Team_Payroll_GitHub_Updater {

	/**
	 * GitHub repository owner
	 * @var string
	 */
	private $github_user = 'imranduzzlo';

	/**
	 * GitHub repository name
	 * @var string
	 */
	private $github_repo = 'woocommerce-team-payroll';

	/**
	 * Plugin slug (must match folder name)
	 * @var string
	 */
	private $plugin_slug = 'woocommerce-team-payroll';

	/**
	 * Plugin basename (folder/file.php)
	 * @var string
	 */
	private $plugin_basename = 'woocommerce-team-payroll/woocommerce-team-payroll.php';

	/**
	 * GitHub API URL
	 * @var string
	 */
	private $github_api_url;

	/**
	 * Plugin data
	 * @var array
	 */
	private $plugin_data = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		// Build GitHub API URL
		$this->github_api_url = sprintf(
			'https://api.github.com/repos/%s/%s',
			$this->github_user,
			$this->github_repo
		);

		// Get plugin data
		$this->plugin_data = $this->get_plugin_data();

		// Initialize hooks
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks
	 */
	private function init_hooks() {
		// Check for updates
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );
		
		// Provide plugin information
		add_filter( 'plugins_api', array( $this, 'get_plugin_info' ), 20, 3 );
		
		// Fix folder name during installation (CRITICAL FIX)
		add_filter( 'upgrader_source_selection', array( $this, 'fix_folder_name' ), 10, 4 );
		
		// Clear cache after update
		add_action( 'upgrader_process_complete', array( $this, 'clear_update_cache' ), 10, 2 );
	}

	/**
	 * Get plugin data from main file
	 * 
	 * @return array Plugin data
	 */
	private function get_plugin_data() {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugin_file = WP_PLUGIN_DIR . '/' . $this->plugin_basename;
		
		if ( ! file_exists( $plugin_file ) ) {
			return array();
		}

		return get_plugin_data( $plugin_file, false, false );
	}

	/**
	 * Check for plugin updates
	 * 
	 * @param object $transient Update transient
	 * @return object Modified transient
	 */
	public function check_for_update( $transient ) {
		// If no checked plugins, return early
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		// Get current version
		$current_version = isset( $this->plugin_data['Version'] ) ? $this->plugin_data['Version'] : '0.0.0';

		// Get latest release from GitHub
		$latest_release = $this->get_latest_release();

		// If no release found, return transient unchanged
		if ( ! $latest_release ) {
			return $transient;
		}

		// Compare versions
		$latest_version = $this->normalize_version( $latest_release->version );
		$current_version = $this->normalize_version( $current_version );

		// Debug log
		$this->log( sprintf(
			'Update Check: Current=%s, Latest=%s, Update Available=%s',
			$current_version,
			$latest_version,
			version_compare( $latest_version, $current_version, '>' ) ? 'YES' : 'NO'
		) );

		// If newer version available, add to update transient
		if ( version_compare( $latest_version, $current_version, '>' ) ) {
			$transient->response[ $this->plugin_basename ] = (object) array(
				'slug'          => $this->plugin_slug,
				'plugin'        => $this->plugin_basename,
				'new_version'   => $latest_version,
				'url'           => $latest_release->html_url,
				'package'       => $latest_release->download_url,
				'icons'         => array(),
				'banners'       => array(),
				'banners_rtl'   => array(),
				'tested'        => '6.7',
				'requires_php'  => '7.2',
				'compatibility' => new stdClass(),
			);
		} else {
			// Remove from response if no update available
			if ( isset( $transient->response[ $this->plugin_basename ] ) ) {
				unset( $transient->response[ $this->plugin_basename ] );
			}
		}

		return $transient;
	}

	/**
	 * Get plugin information for the update modal
	 * 
	 * @param false|object|array $result The result object or array
	 * @param string $action The type of information being requested
	 * @param object $args Plugin API arguments
	 * @return false|object Modified result
	 */
	public function get_plugin_info( $result, $action, $args ) {
		// Only handle plugin_information requests
		if ( $action !== 'plugin_information' ) {
			return $result;
		}

		// Only handle our plugin
		if ( ! isset( $args->slug ) || $args->slug !== $this->plugin_slug ) {
			return $result;
		}

		// Get latest release
		$latest_release = $this->get_latest_release();

		if ( ! $latest_release ) {
			return $result;
		}

		// Build plugin info object
		$plugin_info = new stdClass();
		$plugin_info->name = $this->plugin_data['Name'];
		$plugin_info->slug = $this->plugin_slug;
		$plugin_info->version = $latest_release->version;
		$plugin_info->author = $this->plugin_data['Author'];
		$plugin_info->author_profile = $this->plugin_data['AuthorURI'];
		$plugin_info->requires = '5.0';
		$plugin_info->tested = '6.7';
		$plugin_info->requires_php = '7.2';
		$plugin_info->download_link = $latest_release->download_url;
		$plugin_info->trunk = $latest_release->download_url;
		$plugin_info->last_updated = $latest_release->published_at;
		$plugin_info->homepage = $this->plugin_data['PluginURI'];
		
		// Sections
		$plugin_info->sections = array(
			'description' => $this->plugin_data['Description'],
			'changelog'   => $this->get_changelog_html( $latest_release ),
		);

		// Additional info
		$plugin_info->banners = array();
		$plugin_info->icons = array();

		return $plugin_info;
	}

	/**
	 * Fix folder name during installation
	 * 
	 * GitHub creates folders like: woocommerce-team-payroll-1.6.3
	 * WordPress needs: woocommerce-team-payroll
	 * 
	 * @param string $source File source location
	 * @param string $remote_source Remote file source location
	 * @param WP_Upgrader $upgrader WP_Upgrader instance
	 * @param array $hook_extra Extra arguments passed to hooked filters
	 * @return string|WP_Error Modified source location or WP_Error
	 */
	public function fix_folder_name( $source, $remote_source, $upgrader, $hook_extra ) {
		global $wp_filesystem;

		// Check if this is our plugin
		if ( ! isset( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->plugin_basename ) {
			return $source;
		}

		// Get the correct folder name
		$correct_folder = $this->plugin_slug;
		
		// Get current folder name from source
		$path_parts = explode( '/', trim( $source, '/' ) );
		$current_folder = array_pop( $path_parts );

		// If folder name is already correct, return as is
		if ( $current_folder === $correct_folder ) {
			return $source;
		}

		// Build new source path with correct folder name
		$new_source = trailingslashit( $remote_source ) . $correct_folder . '/';

		// Rename the folder
		if ( $wp_filesystem->move( $source, $new_source ) ) {
			$this->log( "Renamed folder from '{$current_folder}' to '{$correct_folder}'" );
			return $new_source;
		}

		// If rename failed, return error
		return new WP_Error( 'rename_failed', 'Could not rename plugin folder during installation.' );
	}

	/**
	 * Get latest release from GitHub
	 * 
	 * @return object|false Release object or false on failure
	 */
	private function get_latest_release() {
		// Check cache first
		$cache_key = 'wc_tp_github_release_' . md5( $this->github_repo );
		$cached = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		// Fetch from GitHub API
		$api_url = $this->github_api_url . '/releases/latest';
		
		$response = wp_remote_get( $api_url, array(
			'timeout' => 10,
			'headers' => array(
				'Accept'     => 'application/vnd.github.v3+json',
				'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' ),
			),
		) );

		// Handle errors
		if ( is_wp_error( $response ) ) {
			$this->log( 'GitHub API Error: ' . $response->get_error_message() );
			return false;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		
		if ( $response_code !== 200 ) {
			$this->log( sprintf( 'GitHub API returned HTTP %d', $response_code ) );
			return false;
		}

		// Parse response
		$body = wp_remote_retrieve_body( $response );
		$release_data = json_decode( $body );

		if ( ! $release_data || ! isset( $release_data->tag_name ) ) {
			$this->log( 'Invalid GitHub API response - no tag_name found' );
			return false;
		}

		// Build release object
		$release = new stdClass();
		$release->version = $this->normalize_version( $release_data->tag_name );
		$release->html_url = $release_data->html_url;
		$release->download_url = $release_data->zipball_url;
		$release->published_at = $release_data->published_at;
		$release->body = isset( $release_data->body ) ? $release_data->body : '';
		$release->name = isset( $release_data->name ) ? $release_data->name : $release_data->tag_name;

		// Cache for 6 hours
		set_transient( $cache_key, $release, 6 * HOUR_IN_SECONDS );

		return $release;
	}

	/**
	 * Get changelog HTML from release notes
	 * 
	 * @param object $release Release object
	 * @return string Changelog HTML
	 */
	private function get_changelog_html( $release ) {
		if ( empty( $release->body ) ) {
			return '<p>No changelog available.</p>';
		}

		// Convert markdown to HTML (basic conversion)
		$changelog = $release->body;
		
		// Convert headers
		$changelog = preg_replace( '/^### (.+)$/m', '<h3>$1</h3>', $changelog );
		$changelog = preg_replace( '/^## (.+)$/m', '<h2>$1</h2>', $changelog );
		$changelog = preg_replace( '/^# (.+)$/m', '<h1>$1</h1>', $changelog );
		
		// Convert lists
		$changelog = preg_replace( '/^\* (.+)$/m', '<li>$1</li>', $changelog );
		$changelog = preg_replace( '/^- (.+)$/m', '<li>$1</li>', $changelog );
		
		// Wrap lists in ul tags
		$changelog = preg_replace( '/(<li>.*<\/li>)/s', '<ul>$1</ul>', $changelog );
		
		// Convert line breaks to paragraphs
		$changelog = wpautop( $changelog );

		return $changelog;
	}

	/**
	 * Normalize version string
	 * Removes 'v' prefix and ensures proper format
	 * 
	 * @param string $version Version string
	 * @return string Normalized version
	 */
	private function normalize_version( $version ) {
		// Remove 'v' prefix
		$version = ltrim( trim( $version ), 'v' );
		
		// Ensure valid version format
		if ( ! preg_match( '/^\d+\.\d+\.\d+/', $version ) ) {
			// If only X.Y, add .0
			if ( preg_match( '/^\d+\.\d+$/', $version ) ) {
				$version .= '.0';
			}
		}

		return $version;
	}

	/**
	 * Clear update cache after plugin update
	 * 
	 * @param WP_Upgrader $upgrader Upgrader instance
	 * @param array $options Update options
	 */
	public function clear_update_cache( $upgrader, $options ) {
		if ( $options['action'] === 'update' && $options['type'] === 'plugin' ) {
			$cache_key = 'wc_tp_github_release_' . md5( $this->github_repo );
			delete_transient( $cache_key );
		}
	}

	/**
	 * Log debug messages (only if WP_DEBUG is enabled)
	 * 
	 * @param string $message Log message
	 */
	private function log( $message ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[WC Team Payroll Updater] ' . $message );
		}
	}
}

// Initialize the updater
function wc_team_payroll_init_github_updater() {
	new WC_Team_Payroll_GitHub_Updater();
}

// Hook into plugins_loaded with priority 5 (early)
add_action( 'plugins_loaded', 'wc_team_payroll_init_github_updater', 5 );
