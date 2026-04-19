# WooCommerce Team Payroll - Installation Guide

## 📦 Manual Installation (First Time)

### Step 1: Download the Plugin

1. Go to the GitHub release page: https://github.com/imranduzzlo/woocommerce-team-payroll/releases/tag/v1.6.4
2. Click on **"Source code (zip)"** to download the plugin ZIP file
3. Save the file to your computer (it will be named `woocommerce-team-payroll-1.6.4.zip`)

### Step 2: Extract the ZIP File

1. **Extract the ZIP file** using your preferred extraction tool (WinRAR, 7-Zip, Windows built-in, etc.)
2. After extraction, you'll see a folder named: `woocommerce-team-payroll-1.6.4`
3. **IMPORTANT:** Rename this folder to: `woocommerce-team-payroll` (remove the `-1.6.4` part)

**Why rename?** WordPress expects the plugin folder name to match the plugin slug. The folder must be named `woocommerce-team-payroll` for the plugin to work correctly.

### Step 3: Upload to WordPress

**Option A: Via FTP/File Manager (Recommended)**

1. Connect to your server via FTP (FileZilla, WinSCP) or use cPanel File Manager
2. Navigate to: `/wp-content/plugins/`
3. Upload the `woocommerce-team-payroll` folder to this directory
4. The final path should be: `/wp-content/plugins/woocommerce-team-payroll/`

**Option B: Via WordPress Admin (Alternative)**

1. Go to WordPress Admin → Plugins → Add New
2. Click "Upload Plugin" button at the top
3. **IMPORTANT:** You need to ZIP the renamed folder first:
   - Take the `woocommerce-team-payroll` folder (after renaming)
   - Create a new ZIP file from this folder
   - Upload this new ZIP file
4. Click "Install Now"

### Step 4: Activate the Plugin

1. Go to WordPress Admin → Plugins
2. Find "WooCommerce Team Payroll & Commission System" in the list
3. Click **"Activate"**
4. Done! The plugin is now active and ready to use

---

## 🔄 Future Updates (Automatic)

After the first manual installation, the plugin will automatically check for updates!

### How Automatic Updates Work

1. **Automatic Check:** The plugin checks GitHub for new releases every 12 hours
2. **Update Notification:** When a new version is available, you'll see it in:
   - WordPress Admin → Dashboard → Updates
   - WordPress Admin → Plugins (update badge next to plugin name)
3. **One-Click Update:** Simply click "Update Now" and WordPress will:
   - Download the latest version from GitHub
   - Automatically fix the folder name
   - Install the update
   - Preserve all your settings and data

### Manual Update Check

To manually check for updates:
1. Go to WordPress Admin → Dashboard → Updates
2. Click "Check Again" button
3. If a new version is available, it will appear in the list

---

## ✅ Verify Installation

After activation, verify the plugin is working:

1. **Check Plugin Version:**
   - Go to WordPress Admin → Plugins
   - Find "WooCommerce Team Payroll & Commission System"
   - Version should show: **1.6.4**

2. **Check Menu Items:**
   - You should see new menu items in WordPress admin:
     - Team Payroll (main menu)
     - Dashboard
     - Employees
     - Payroll
     - Reports
     - Settings

3. **Check WooCommerce Integration:**
   - Go to WooCommerce → Orders
   - Edit any order
   - You should see "Team Assignment" section with agent/processor dropdowns

---

## 🔧 Requirements

Before installation, ensure your server meets these requirements:

- ✅ WordPress 5.0 or higher
- ✅ WooCommerce 5.0 or higher
- ✅ PHP 7.4 or higher
- ✅ MySQL 5.6 or higher

---

## 🐛 Troubleshooting

### Plugin Not Showing After Upload

**Problem:** Plugin doesn't appear in WordPress Plugins list

**Solution:**
1. Check folder name is exactly: `woocommerce-team-payroll` (no version number)
2. Check folder location is: `/wp-content/plugins/woocommerce-team-payroll/`
3. Check the main plugin file exists: `/wp-content/plugins/woocommerce-team-payroll/woocommerce-team-payroll.php`

### Critical Error After Activation

**Problem:** "There has been a critical error on this website"

**Solution:**
1. Check WooCommerce is installed and activated first
2. Check PHP version is 7.4 or higher
3. Check file permissions (folders: 755, files: 644)
4. Enable WordPress debug mode to see the actual error

### Updates Not Showing

**Problem:** New version available but WordPress doesn't show update

**Solution:**
1. Go to Dashboard → Updates → Click "Check Again"
2. Clear WordPress transients cache
3. Wait 12 hours for automatic check (or force check as above)
4. Verify GitHub repository is accessible: https://github.com/imranduzzlo/woocommerce-team-payroll

---

## 📞 Support

- **GitHub Issues:** https://github.com/imranduzzlo/woocommerce-team-payroll/issues
- **Documentation:** https://github.com/imranduzzlo/woocommerce-team-payroll
- **Changelog:** https://github.com/imranduzzlo/woocommerce-team-payroll/blob/main/CHANGELOG.md

---

## 📝 Next Steps After Installation

1. **Configure Settings:**
   - Go to Team Payroll → Settings
   - Set commission calculation statuses
   - Configure checkout fields
   - Set up contact information

2. **Add Employees:**
   - Go to Team Payroll → Employees
   - Add your team members
   - Set their roles (agent/processor)
   - Configure salary types

3. **Test the System:**
   - Create a test order
   - Assign agent/processor
   - Check commission calculation
   - Verify in employee details

---

**Version:** 1.6.4  
**Release Date:** April 19, 2026  
**Repository:** https://github.com/imranduzzlo/woocommerce-team-payroll
