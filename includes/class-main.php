<?php
/**
 * Main plugin class - Singleton pattern
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Team_Payroll_Main {

	private static $instance = null;

	/**
	 * Get singleton instance
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->init();
	}

	/**
	 * Initialize plugin
	 */
	private function init() {
		// Load text domain
		load_plugin_textdomain( 'wc-team-payroll', false, dirname( plugin_basename( WC_TEAM_PAYROLL_PATH . 'woocommerce-team-payroll.php' ) ) . '/languages' );

		// Include GitHub updater
		require_once WC_TEAM_PAYROLL_PATH . 'includes/class-github-updater.php';

		// Include all required files
		require_once WC_TEAM_PAYROLL_PATH . 'includes/class-core-engine.php';
		require_once WC_TEAM_PAYROLL_PATH . 'includes/class-payroll-engine.php';
		require_once WC_TEAM_PAYROLL_PATH . 'includes/class-settings.php';
		require_once WC_TEAM_PAYROLL_PATH . 'includes/class-dashboard.php';
		require_once WC_TEAM_PAYROLL_PATH . 'includes/class-checkout-integration.php';
		require_once WC_TEAM_PAYROLL_PATH . 'includes/class-employee-management.php';
		require_once WC_TEAM_PAYROLL_PATH . 'includes/class-employee-detail.php';

		// Initialize all classes - they hook into admin_menu in their constructors
		new WC_Team_Payroll_Core_Engine();
		new WC_Team_Payroll_Settings();
		new WC_Team_Payroll_Dashboard();
		new WC_Team_Payroll_Checkout_Integration();
		new WC_Team_Payroll_Employee_Management();
		new WC_Team_Payroll_Employee_Detail();

		// Add plugin action links
		add_filter( 'plugin_action_links_' . plugin_basename( WC_TEAM_PAYROLL_PATH . 'woocommerce-team-payroll.php' ), array( $this, 'add_action_links' ) );
		
		// Handle manual update check
		add_action( 'admin_init', array( $this, 'handle_manual_update_check' ) );
	}

	/**
	 * Add plugin action links
	 */
	public function add_action_links( $links ) {
		$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-team-payroll-settings' ) ) . '">' . esc_html__( 'Settings', 'wc-team-payroll' ) . '</a>';
		$update_link = '<a href="' . esc_url( admin_url( 'plugins.php?wc_tp_force_update_check=1' ) ) . '" style="color: #2271b1; font-weight: 600;">' . esc_html__( 'Check Updates', 'wc-team-payroll' ) . '</a>';
		array_unshift( $links, $settings_link, $update_link );
		return $links;
	}

	/**
	 * Handle manual update check
	 */
	public function handle_manual_update_check() {
		if ( ! isset( $_GET['wc_tp_force_update_check'] ) ) {
			return;
		}

		if ( ! current_user_can( 'update_plugins' ) ) {
			return;
		}

		// Clear all update caches
		delete_transient( 'wc_tp_github_release' );
		delete_transient( 'wc_tp_last_update_check' );
		delete_site_transient( 'update_plugins' );

		// Force WordPress to check for updates
		wp_update_plugins();

		// Add admin notice
		add_action( 'admin_notices', function() {
			echo '<div class="notice notice-success is-dismissible">';
			echo '<p><strong>' . esc_html__( 'WooCommerce Team Payroll:', 'wc-team-payroll' ) . '</strong> ';
			echo esc_html__( 'Update check completed! If an update is available, it will appear below.', 'wc-team-payroll' );
			echo '</p></div>';
		} );

		// Redirect to remove query parameter
		wp_safe_redirect( admin_url( 'plugins.php' ) );
		exit;
	}
}
