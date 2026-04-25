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
		$this->github_repo = 'woocommerce-team-payroll';
		$this->github_branch = 'main';
		$this->github_api_url = "https://api.github.com/repos/{$this->github_user}/{$this->github_repo}";

		// Hook into WordPress update checks
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_info' ), 10, 3 );
		add_filter( 'upgrader_source_selection', array( $this, 'fix_source_directory' ), 10, 4 );
		
		// Add after plugin update hook
		add_action( 'upgrader_process_complete', array( $this, 'after_update' ), 10, 2 );
		
		// Clear cache on plugin activation
		add_action( 'activated_plugin', array( $this, 'clear_cache' ) );
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
	 * Fix the source directory name after download
	 * GitHub zipballs extract to username-repo-commit format
	 */
	public function fix_source_directory( $source, $remote_source, $upgrader, $hook_extra = null ) {
		global $wp_filesystem;

		// Only process our plugin
		if ( ! isset( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->plugin_file ) {
			return $source;
		}

		// Get the correct directory name
		$corrected_source = trailingslashit( $remote_source ) . $this->plugin_slug . '/';

		// Check if source is already correct
		if ( $source === $corrected_source ) {
			return $source;
		}

		// Rename the directory
		if ( $wp_filesystem->move( $source, $corrected_source, true ) ) {
			return $corrected_source;
		}

		return new WP_Error( 'rename_failed', __( 'Unable to rename plugin directory.', 'wc-team-payroll' ) );
	}

	/**
	 * Clear cache after plugin update
	 */
	public function after_update( $upgrader_object, $options ) {
		// Check if this is a plugin update
		if ( $options['action'] !== 'update' || $options['type'] !== 'plugin' ) {
			return;
		}

		// Check if our plugin was updated
		if ( isset( $options['plugins'] ) ) {
			foreach ( $options['plugins'] as $plugin ) {
				if ( $plugin === $this->plugin_file ) {
					$this->clear_cache();
					break;
				}
			}
		}
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

		// Make sure our plugin is in the checked array
		if ( ! isset( $transient->checked[ $this->plugin_file ] ) ) {
			$transient->checked[ $this->plugin_file ] = $this->get_current_version();
		}

		$current_version = $this->get_current_version();

		// Get latest release from GitHub
		$latest_release = $this->get_latest_release();

		if ( ! $latest_release ) {
			// Remove from response if no release found
			if ( isset( $transient->response[ $this->plugin_file ] ) ) {
				unset( $transient->response[ $this->plugin_file ] );
			}
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
			$update_object = (object) array(
				'id'            => "github.com/{$this->github_user}/{$this->github_repo}",
				'slug'          => $this->plugin_slug,
				'plugin'        => $this->plugin_file,
				'new_version'   => $latest_version,
				'url'           => $latest_release['url'],
				'package'       => $latest_release['download_url'],
				'tested'        => '6.7',
				'requires'      => '5.0',
				'requires_php'  => '7.2',
				'icons'         => array(),
				'banners'       => array(),
				'compatibility' => new stdClass(),
			);

			$transient->response[ $this->plugin_file ] = $update_object;
			
			// Debug logging
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'WC Team Payroll: Update available - ' . $latest_version );
			}
		} else {
			// Explicitly remove from response if versions are equal or current is newer
			if ( isset( $transient->response[ $this->plugin_file ] ) ) {
				unset( $transient->response[ $this->plugin_file ] );
			}
			
			// Add to no_update to show plugin is up to date
			if ( ! isset( $transient->no_update ) ) {
				$transient->no_update = array();
			}
			
			$transient->no_update[ $this->plugin_file ] = (object) array(
				'id'            => "github.com/{$this->github_user}/{$this->github_repo}",
				'slug'          => $this->plugin_slug,
				'plugin'        => $this->plugin_file,
				'new_version'   => $current_version_normalized,
				'url'           => "https://github.com/{$this->github_user}/{$this->github_repo}",
				'package'       => '',
				'tested'        => '6.7',
				'requires'      => '5.0',
				'requires_php'  => '7.2',
				'icons'         => array(),
				'banners'       => array(),
				'compatibility' => new stdClass(),
			);
			
			// Debug logging
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'WC Team Payroll: Plugin is up to date - ' . $current_version_normalized );
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
			'author'          => '<a href="https://imranhossain.me/">Imran</a>',
			'author_profile'  => 'https://imranhossain.me/',
			'homepage'        => "https://github.com/{$this->github_user}/{$this->github_repo}",
			'download_link'   => $latest_release['download_url'],
			'trunk'           => $latest_release['download_url'],
			'requires'        => '5.0',
			'requires_php'    => '7.2',
			'tested'          => '6.7',
			'last_updated'    => $latest_release['updated'],
			'sections'        => array(
				'description' => $this->get_description(),
				'changelog'   => $this->get_changelog(),
				'installation' => $this->get_installation_instructions(),
			),
			'banners'         => array(),
			'icons'           => array(),
		);

		return $result;
	}

	/**
	 * Get plugin description
	 */
	private function get_description() {
		return '<p><strong>WooCommerce Team Payroll & Commission System</strong> is a powerful plugin designed to manage team-based sales commissions, employee salaries, and performance tracking for WooCommerce stores.</p>
		<h3>Key Features</h3>
		<ul>
			<li>✅ Flexible commission system with agent/processor split</li>
			<li>✅ Multiple salary types: Commission-based, Fixed, Combined</li>
			<li>✅ Automatic salary transfers (daily, weekly, monthly)</li>
			<li>✅ Performance tracking with goals and achievements</li>
			<li>✅ Beautiful employee dashboards</li>
			<li>✅ Advanced order editor with automatic recalculation</li>
			<li>✅ Comprehensive reporting and analytics</li>
			<li>✅ WooCommerce HPOS compatible</li>
		</ul>
		<p><a href="https://github.com/' . $this->github_user . '/' . $this->github_repo . '" target="_blank">View on GitHub</a> | <a href="https://github.com/' . $this->github_user . '/' . $this->github_repo . '/issues" target="_blank">Report Issues</a></p>';
	}

	/**
	 * Get installation instructions
	 */
	private function get_installation_instructions() {
		return '<ol>
			<li>Click "Install Now" to download and install the plugin</li>
			<li>Activate the plugin through the Plugins menu</li>
			<li>Go to WooCommerce → Team Payroll to configure settings</li>
			<li>Add employees and configure their salary types</li>
			<li>Start tracking commissions and managing payroll!</li>
		</ol>
		<p><strong>Requirements:</strong> WordPress 5.0+, WooCommerce 5.0+, PHP 7.2+</p>';
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
			'body'         => isset( $release['body'] ) ? $release['body'] : '',
		);

		// Cache for 6 hours (balanced between freshness and rate limiting)
		set_transient( $transient_key, $result, 6 * HOUR_IN_SECONDS );

		// Debug logging
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( "WC Team Payroll: Fetched release {$version} from GitHub" );
		}

		return $result;
	}

	/**
	 * Get changelog from GitHub releases
	 */
	private function get_changelog() {
		$latest_release = $this->get_latest_release();
		
		if ( ! $latest_release || empty( $latest_release['body'] ) ) {
			return '<p>View full changelog on <a href="https://github.com/' . $this->github_user . '/' . $this->github_repo . '/releases" target="_blank">GitHub</a>.</p>';
		}

		// Convert markdown to HTML (basic conversion)
		$changelog = $latest_release['body'];
		$changelog = wp_kses_post( wpautop( $changelog ) );
		
		return $changelog;
	}

	/**
	 * Add settings link to force update check
	 */
	public function add_action_links( $links ) {
		$check_link = '<a href="' . admin_url( 'plugins.php?wc_tp_check_update=1' ) . '">' . __( 'Check for Updates', 'wc-team-payroll' ) . '</a>';
		array_unshift( $links, $check_link );
		return $links;
	}
}

// Initialize updater ALWAYS (even when plugin is inactive)
// This allows WordPress to check for updates even if plugin is not active
if ( ! function_exists( 'wc_tp_init_github_updater' ) ) {
	function wc_tp_init_github_updater() {
		// Only initialize once
		static $initialized = false;
		if ( $initialized ) {
			return;
		}
		$initialized = true;
		
		new WC_Team_Payroll_GitHub_Updater();
	}
}

// Hook early to ensure updates work even when plugin is inactive
add_action( 'init', 'wc_tp_init_github_updater', 1 );
add_action( 'admin_init', 'wc_tp_init_github_updater', 1 );
