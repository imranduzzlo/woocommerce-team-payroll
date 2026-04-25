<?php
/**
 * Update Debug Page
 * Shows detailed information about the update system
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Team_Payroll_Update_Debug {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_debug_page' ), 999 );
	}

	/**
	 * Add debug page to admin menu
	 */
	public function add_debug_page() {
		// Add as a top-level menu if parent doesn't exist
		$parent_slug = 'wc-team-payroll-dashboard';
		
		// Check if parent menu exists
		global $menu;
		$parent_exists = false;
		if ( is_array( $menu ) ) {
			foreach ( $menu as $item ) {
				if ( isset( $item[2] ) && $item[2] === $parent_slug ) {
					$parent_exists = true;
					break;
				}
			}
		}
		
		if ( $parent_exists ) {
			add_submenu_page(
				$parent_slug,
				__( 'Update Debug', 'wc-team-payroll' ),
				__( 'Update Debug', 'wc-team-payroll' ),
				'manage_options',
				'wc-team-payroll-update-debug',
				array( $this, 'render_debug_page' )
			);
		} else {
			// Add under Tools menu as fallback
			add_management_page(
				__( 'WC Team Payroll Update Debug', 'wc-team-payroll' ),
				__( 'WC Payroll Updates', 'wc-team-payroll' ),
				'manage_options',
				'wc-team-payroll-update-debug',
				array( $this, 'render_debug_page' )
			);
		}
	}

	/**
	 * Render debug page
	 */
	public function render_debug_page() {
		// Handle clear cache action
		if ( isset( $_POST['clear_cache'] ) && check_admin_referer( 'wc_tp_clear_cache' ) ) {
			delete_transient( 'wc_tp_github_release' );
			delete_transient( 'wc_tp_github_release_v2' ); // New transient key
			delete_transient( 'wc_tp_last_update_check' );
			delete_site_transient( 'update_plugins' );
			delete_site_transient( 'update_plugins_last_checked' );
			wp_clean_plugins_cache();
			wp_update_plugins();
			
			echo '<div class="notice notice-success"><p><strong>Cache cleared and update check triggered!</strong></p></div>';
		}

		// Get plugin data
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		
		// Use the constant that's already defined in the main plugin file
		$plugin_file = WC_TEAM_PAYROLL_PATH . 'woocommerce-team-payroll.php';
		
		if ( ! file_exists( $plugin_file ) ) {
			// Fallback: try to find it
			$possible_paths = array(
				WP_PLUGIN_DIR . '/woocommerce-team-payroll/woocommerce-team-payroll.php',
				dirname( dirname( __FILE__ ) ) . '/woocommerce-team-payroll.php',
			);
			
			foreach ( $possible_paths as $path ) {
				if ( file_exists( $path ) ) {
					$plugin_file = $path;
					break;
				}
			}
		}
		
		if ( ! file_exists( $plugin_file ) ) {
			echo '<div class="notice notice-error"><p><strong>Error:</strong> Could not locate plugin file at: ' . esc_html( $plugin_file ) . '</p></div>';
			echo '<div class="notice notice-info"><p>WC_TEAM_PAYROLL_PATH: ' . esc_html( WC_TEAM_PAYROLL_PATH ) . '</p></div>';
			echo '<div class="notice notice-info"><p>__FILE__: ' . esc_html( __FILE__ ) . '</p></div>';
			return;
		}
		
		$plugin_data = get_plugin_data( $plugin_file );
		$current_version = isset( $plugin_data['Version'] ) ? $plugin_data['Version'] : 'Unknown';

		// Get GitHub release info
		$github_api_url = 'https://api.github.com/repos/imranduzzlo/woocommerce-team-payroll/releases/latest';
		$response = wp_remote_get( $github_api_url, array(
			'timeout'   => 10,
			'sslverify' => true,
			'headers'   => array(
				'Accept' => 'application/vnd.github.v3+json',
				'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ),
			),
		) );

		$github_status = 'Unknown';
		$latest_version = 'Unknown';
		$github_tag = 'Unknown';
		$published_at = 'Unknown';

		if ( ! is_wp_error( $response ) ) {
			$http_code = wp_remote_retrieve_response_code( $response );
			if ( $http_code === 200 ) {
				$body = wp_remote_retrieve_body( $response );
				$release = json_decode( $body, true );
				if ( isset( $release['tag_name'] ) ) {
					$github_status = 'Connected';
					$github_tag = $release['tag_name'];
					$latest_version = ltrim( $release['tag_name'], 'v' );
					$published_at = date( 'Y-m-d H:i:s', strtotime( $release['published_at'] ) );
				}
			} else {
				$github_status = 'HTTP ' . $http_code;
			}
		} else {
			$github_status = 'Error: ' . $response->get_error_message();
		}

		// Get WordPress update transient
		$update_plugins = get_site_transient( 'update_plugins' );
		$plugin_basename = 'woocommerce-team-payroll/woocommerce-team-payroll.php';
		
		$in_response = isset( $update_plugins->response[ $plugin_basename ] );
		$in_no_update = isset( $update_plugins->no_update[ $plugin_basename ] );
		$in_checked = isset( $update_plugins->checked[ $plugin_basename ] );

		// Get cached release
		$cached_release = get_transient( 'wc_tp_github_release_v2' );
		if ( ! $cached_release ) {
			$cached_release = get_transient( 'wc_tp_github_release' ); // Fallback to old key
		}

		?>
		<div class="wrap">
			<h1><?php _e( 'Update System Debug', 'wc-team-payroll' ); ?></h1>
			
			<div class="card" style="max-width: 100%; margin-top: 20px;">
				<h2>Current Status</h2>
				<table class="widefat striped">
					<tbody>
						<tr>
							<td><strong>Current Version</strong></td>
							<td><code><?php echo esc_html( $current_version ); ?></code></td>
						</tr>
						<tr>
							<td><strong>Latest GitHub Version</strong></td>
							<td><code><?php echo esc_html( $latest_version ); ?></code></td>
						</tr>
						<tr>
							<td><strong>GitHub Tag</strong></td>
							<td><code><?php echo esc_html( $github_tag ); ?></code></td>
						</tr>
						<tr>
							<td><strong>Published At</strong></td>
							<td><?php echo esc_html( $published_at ); ?></td>
						</tr>
						<tr>
							<td><strong>GitHub API Status</strong></td>
							<td>
								<?php if ( $github_status === 'Connected' ): ?>
									<span style="color: green;">✓ <?php echo esc_html( $github_status ); ?></span>
								<?php else: ?>
									<span style="color: red;">✗ <?php echo esc_html( $github_status ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<td><strong>Update Available?</strong></td>
							<td>
								<?php 
								$update_available = version_compare( $latest_version, $current_version, '>' );
								if ( $update_available ) {
									echo '<span style="color: green; font-weight: bold;">YES - Update to ' . esc_html( $latest_version ) . '</span>';
								} else {
									echo '<span style="color: blue;">NO - Up to date</span>';
								}
								?>
							</td>
						</tr>
					</tbody>
				</table>
			</div>

			<div class="card" style="max-width: 100%; margin-top: 20px;">
				<h2>WordPress Update Transient</h2>
				<table class="widefat striped">
					<tbody>
						<tr>
							<td><strong>In update_plugins->response</strong></td>
							<td>
								<?php if ( $in_response ): ?>
									<span style="color: green;">✓ YES</span>
									<?php if ( isset( $update_plugins->response[ $plugin_basename ]->new_version ) ): ?>
										<br><small>New version: <?php echo esc_html( $update_plugins->response[ $plugin_basename ]->new_version ); ?></small>
									<?php endif; ?>
								<?php else: ?>
									<span style="color: red;">✗ NO</span>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<td><strong>In update_plugins->no_update</strong></td>
							<td>
								<?php if ( $in_no_update ): ?>
									<span style="color: blue;">✓ YES (Plugin is up to date)</span>
								<?php else: ?>
									<span style="color: gray;">✗ NO</span>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<td><strong>In update_plugins->checked</strong></td>
							<td>
								<?php if ( $in_checked ): ?>
									<span style="color: green;">✓ YES</span>
									<br><small>Checked version: <?php echo esc_html( $update_plugins->checked[ $plugin_basename ] ); ?></small>
								<?php else: ?>
									<span style="color: red;">✗ NO</span>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<td><strong>Last Checked</strong></td>
							<td>
								<?php 
								if ( isset( $update_plugins->last_checked ) ) {
									echo date( 'Y-m-d H:i:s', $update_plugins->last_checked );
								} else {
									echo 'Never';
								}
								?>
							</td>
						</tr>
					</tbody>
				</table>
			</div>

			<div class="card" style="max-width: 100%; margin-top: 20px;">
				<h2>Cache Status</h2>
				<table class="widefat striped">
					<tbody>
						<tr>
							<td><strong>GitHub Release Cached</strong></td>
							<td>
								<?php if ( $cached_release ): ?>
									<span style="color: blue;">✓ YES</span>
									<br><small>Cached version: <?php echo esc_html( $cached_release['version'] ); ?></small>
								<?php else: ?>
									<span style="color: gray;">✗ NO (Will fetch fresh)</span>
								<?php endif; ?>
							</td>
						</tr>
					</tbody>
				</table>
			</div>

			<div class="card" style="max-width: 100%; margin-top: 20px;">
				<h2>Actions</h2>
				<form method="post">
					<?php wp_nonce_field( 'wc_tp_clear_cache' ); ?>
					<p>
						<button type="submit" name="clear_cache" class="button button-primary">
							Clear All Caches & Force Update Check
						</button>
					</p>
					<p class="description">
						This will clear all update caches and force WordPress to check for updates immediately.
					</p>
				</form>
			</div>

			<div class="card" style="max-width: 100%; margin-top: 20px;">
				<h2>Troubleshooting</h2>
				<div style="background: #f0f0f1; padding: 15px; border-left: 4px solid #2271b1;">
					<h3>If update count shows but no update appears:</h3>
					<ol>
						<li><strong>Click "Clear All Caches" button above</strong> - This forces a fresh check</li>
						<li><strong>Wait 5 minutes</strong> - WordPress needs time to process</li>
						<li><strong>Refresh the Plugins page</strong> - Press F5 or Ctrl+R</li>
						<li><strong>Check Dashboard > Updates</strong> - Update might appear there</li>
					</ol>

					<h3>If still not working:</h3>
					<ol>
						<li>Make sure current version (<?php echo esc_html( $current_version ); ?>) is LOWER than GitHub version (<?php echo esc_html( $latest_version ); ?>)</li>
						<li>Check that GitHub release is marked as "latest" (not pre-release)</li>
						<li>Verify tag format is correct: <code>v1.0.0</code> (with 'v' prefix)</li>
						<li>Enable WP_DEBUG in wp-config.php and check debug.log for errors</li>
					</ol>
				</div>
			</div>
		</div>
		<?php
	}
}

// Initialize
new WC_Team_Payroll_Update_Debug();
