<?php
/**
 * Debug: Force Check for Plugin Updates
 * 
 * Upload this file to your plugin root folder and access it via:
 * https://yoursite.com/wp-content/plugins/woocommerce-team-payroll/check-update.php
 */

// Load WordPress
require_once('../../../wp-load.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('Unauthorized');
}

echo '<h1>WooCommerce Team Payroll - Update Checker</h1>';
echo '<style>body { font-family: Arial, sans-serif; padding: 20px; } pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; }</style>';

// Get current plugin version
if (!function_exists('get_plugin_data')) {
    require_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

$plugin_file = WP_PLUGIN_DIR . '/woocommerce-team-payroll/woocommerce-team-payroll.php';
$plugin_data = get_plugin_data($plugin_file);
$current_version = $plugin_data['Version'];

echo '<h2>Current Installation</h2>';
echo '<pre>';
echo 'Plugin Version: ' . $current_version . "\n";
echo 'Plugin File: ' . $plugin_file . "\n";
echo 'Plugin Exists: ' . (file_exists($plugin_file) ? 'Yes' : 'No') . "\n";
echo '</pre>';

// Clear all update caches
echo '<h2>Clearing Caches...</h2>';
delete_transient('wc_tp_github_release');
delete_site_transient('update_plugins');
delete_transient('wc_tp_last_update_check');
echo '<p>✅ Cleared all update-related transients</p>';

// Check GitHub API
echo '<h2>Checking GitHub API...</h2>';
$github_url = 'https://api.github.com/repos/imranduzzlo/woocommerce-team-payroll/releases/latest';

$response = wp_remote_get($github_url, array(
    'timeout' => 10,
    'headers' => array(
        'Accept' => 'application/vnd.github.v3+json',
        'User-Agent' => 'WordPress/' . get_bloginfo('version'),
    ),
));

if (is_wp_error($response)) {
    echo '<p style="color: red;">❌ Error: ' . $response->get_error_message() . '</p>';
} else {
    $http_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $release = json_decode($body, true);
    
    echo '<pre>';
    echo 'HTTP Status: ' . $http_code . "\n";
    
    if ($http_code === 200 && isset($release['tag_name'])) {
        $latest_version = ltrim($release['tag_name'], 'v');
        
        echo 'Latest Version: ' . $latest_version . "\n";
        echo 'Release Name: ' . $release['name'] . "\n";
        echo 'Published: ' . $release['published_at'] . "\n";
        echo 'Download URL: ' . $release['zipball_url'] . "\n\n";
        
        // Version comparison
        echo '--- Version Comparison ---' . "\n";
        echo 'Current: ' . $current_version . "\n";
        echo 'Latest: ' . $latest_version . "\n";
        
        if (version_compare($latest_version, $current_version, '>')) {
            echo 'Status: ✅ UPDATE AVAILABLE!' . "\n";
        } else {
            echo 'Status: ℹ️ You have the latest version' . "\n";
        }
    } else {
        echo 'Error: Could not parse release data' . "\n";
        echo 'Response: ' . print_r($release, true);
    }
    echo '</pre>';
}

// Force WordPress to check for updates
echo '<h2>Forcing WordPress Update Check...</h2>';
wp_update_plugins();
echo '<p>✅ Triggered WordPress update check</p>';

// Check if update is available in WordPress
$update_plugins = get_site_transient('update_plugins');
$plugin_basename = 'woocommerce-team-payroll/woocommerce-team-payroll.php';

echo '<h2>WordPress Update Status</h2>';
echo '<pre>';
if (isset($update_plugins->response[$plugin_basename])) {
    echo '✅ UPDATE DETECTED BY WORDPRESS!' . "\n\n";
    $update = $update_plugins->response[$plugin_basename];
    echo 'New Version: ' . $update->new_version . "\n";
    echo 'Package URL: ' . $update->package . "\n";
    echo 'Tested Up To: ' . (isset($update->tested) ? $update->tested : 'N/A') . "\n";
} else {
    echo 'ℹ️ No update detected by WordPress yet' . "\n";
    echo 'This might be due to:' . "\n";
    echo '  - Cache not cleared yet (wait a few minutes)' . "\n";
    echo '  - Updater class not loaded' . "\n";
    echo '  - Version comparison issue' . "\n";
}
echo '</pre>';

echo '<h2>Next Steps</h2>';
echo '<ol>';
echo '<li>Go to <a href="' . admin_url('plugins.php') . '">Plugins page</a></li>';
echo '<li>Or go to <a href="' . admin_url('update-core.php') . '">Updates page</a></li>';
echo '<li>Look for "WooCommerce Team Payroll & Commission System"</li>';
echo '<li>If update shows, click "Update Now"</li>';
echo '<li>If still not showing, wait 5 minutes and refresh</li>';
echo '</ol>';

echo '<p><a href="' . admin_url('plugins.php') . '" class="button button-primary">Go to Plugins Page</a></p>';
