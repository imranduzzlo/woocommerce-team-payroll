# Changelog

All notable changes to WooCommerce Team Payroll & Commission System will be documented in this file.

## [1.0.1] - 2026-04-19

### 🐛 Debug - Orders Tab Empty Results

**CHANGES:**
- Added debug logging to AJAX handler to identify why orders aren't showing
- Logs total orders, matched orders, and final count
- Returns debug info in AJAX response
- Helps identify if user has no assigned orders or if there's a filtering issue

**DEBUG INFO:**
- Check browser console for debug object in AJAX response
- Check WordPress debug.log for backend logging
- Shows: user_id, total_orders, matched_orders, final_count

**LIKELY ISSUE:**
- User ID 1 (admin) may not have any orders assigned as agent or processor
- Orders need `_primary_agent_id` or `_processor_user_id` meta to match user

## [1.0.0] - 2026-04-19

### 🎉 Initial Release - Professional GitHub Updater

**MAJOR CHANGES:**
- ✅ Complete rewrite of GitHub updater from scratch
- ✅ Professional update checker that works like WordPress.org plugins
- ✅ Shows updates in WordPress admin whether plugin is active or inactive
- ✅ Automatic update detection from GitHub releases
- ✅ Repository changed to: https://github.com/imranduzzlo/pv-team-payroll
- ✅ Version reset to 1.0.0 for clean start

**FEATURES:**
- Admin Employee Details page with Orders tab
- Complete My Account Orders table implementation
- Advanced filter controls (role, status, date range with presets, search, per page)
- Sortable table headers with proper column structure
- AJAX-powered data loading
- Proper attributed total display logic
- Employee Role column shows "Agent" or "Processor"
- Uses My Account styling (pv-table, pv-table-controls, Phosphor icons)
- Cache busting for CSS files

**GITHUB UPDATER:**
- Checks GitHub releases every 12 hours automatically
- Shows update notification in WordPress Plugins page
- Provides "View details" link with changelog
- Works with GitHub releases (not just tags)
- Proper version comparison and normalization
- Caches API responses to avoid rate limiting
- Debug logging when WP_DEBUG is enabled

**REQUIREMENTS:**
- WordPress 5.0 or higher
- WooCommerce 5.0 or higher
- PHP 7.2 or higher

**INSTALLATION:**
1. Upload plugin to `/wp-content/plugins/woocommerce-team-payroll/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure settings under WooCommerce > Team Payroll

**UPDATES:**
- Plugin will automatically check for updates from GitHub
- Updates appear in WordPress admin like any other plugin
- Click "Update Now" to install latest version from GitHub releases

---

## Previous Versions (Pre-1.0.0)

All previous development versions have been consolidated into this 1.0.0 release.
