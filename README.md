# WooCommerce Team Payroll & Commission System

> A comprehensive WordPress plugin for managing team-based commission and payroll systems with intelligent agent/processor workflows, automated salary management, and performance tracking.

[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-5.0%2B-purple.svg)](https://woocommerce.com/)
[![PHP](https://img.shields.io/badge/PHP-7.2%2B-777BB4.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2%2B-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Version](https://img.shields.io/badge/Version-1.0.0-green.svg)](https://github.com/imranduzzlo/woocommerce-team-payroll/releases)

---

## 🚀 Quick Start for Releases

### Creating a New Release

1. **Update version** in `woocommerce-team-payroll.php` (lines 6 and 24)
2. **Update** `CHANGELOG.md` with changes
3. **Commit and push** to GitHub
4. **Create release** on GitHub with tag `v1.0.1` (with 'v' prefix)
5. **Users get automatic updates** in WordPress admin!

---

## 📋 Table of Contents

- [Overview](#-overview)
- [Key Features](#-key-features)
- [Requirements](#-requirements)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Usage Guide](#-usage-guide)
- [Architecture](#-architecture)
- [Automatic Updates](#-automatic-updates)
- [Support](#-support)
- [License](#-license)

---

## 🎯 Overview

WooCommerce Team Payroll & Commission System is a powerful solution for businesses that need to manage team-based sales commissions, employee salaries, and performance tracking. The plugin seamlessly integrates with WooCommerce to automatically calculate and track earnings based on order data.

### **Perfect For:**
- E-commerce teams with sales agents and order processors
- Businesses with commission-based compensation models
- Companies needing flexible salary structures (fixed, commission, or hybrid)
- Organizations requiring detailed payroll tracking and reporting

---

## ✨ Key Features

### 💰 **Commission Management**
- **Flexible Split System**: Customizable agent/processor commission percentages
- **Product-Level Rates**: Set individual commission rates per product
- **Salary-Aware Calculation**: Commission eligibility based on employee salary type
- **Refund Handling**: Configurable commission handling for refunded orders
- **Real-time Calculation**: Automatic commission calculation on order status changes

### 👥 **Employee Management**
- **Three Salary Types**:
  - **Commission-Based**: Earnings from order commissions only
  - **Fixed Salary**: Regular salary with automated transfers
  - **Combined**: Base salary + commission earnings
- **Employee Status**: Active/inactive status with login restrictions
- **Auto-Generated IDs**: Customizable employee ID prefix system
- **Profile Management**: Profile pictures, contact info, payment methods
- **Salary History**: Complete audit trail of all salary changes

### 📊 **Performance Tracking**
- **Goal System**: Set and track performance goals (orders, revenue, commission)
- **Achievements**: Unlock achievements with money or badge rewards
- **Leaderboard**: Real-time ranking of top performers
- **Analytics**: Detailed performance metrics and trends
- **Reports**: Comprehensive earnings and performance reports

### 🛒 **Order Integration**
- **Checkout Fields**: Auto-populate agent dropdown at checkout
- **Role-Based Filtering**: Show only eligible agents based on user roles
- **Order Editor**: Modify order items, quantities, prices, and meta data
- **Custom Statuses**: Support for custom order statuses
- **Change Logging**: Automatic audit trail of all order modifications

### 🎨 **Frontend Features**
- **My Account Tabs**:
  - **Salary Details**: View salary info, payment methods, history
  - **My Earnings**: Monthly earnings breakdown with filters
  - **Orders Commission**: Detailed order list with commission data
  - **Reports**: Personal performance analytics and charts
- **Customizable Styling**: Full control over colors, fonts, and layouts
- **Responsive Design**: Mobile-friendly interface
- **Real-time Updates**: AJAX-powered for seamless user experience

### 🔧 **Admin Features**
- **Dashboard**: Overview with stats, payroll summary, top earners
- **Employee Management**: Complete CRUD operations with filtering
- **Payroll Page**: Manage payments, view due amounts, export data
- **Settings Panel**: Comprehensive configuration with multiple tabs
- **Order Editor**: Advanced order editing capabilities
- **Performance Settings**: Configure goals, achievements, and rules

### 🔄 **Automation**
- **Automatic Salary Transfers**: Daily, weekly, or monthly salary automation
- **Commission Calculation**: Triggered on configurable order statuses
- **Pending Salary Tracking**: Automatic tracking of unpaid salary amounts
- **GitHub Auto-Updates**: Seamless plugin updates from GitHub releases

---

## 📦 Requirements

| Requirement | Version |
|------------|---------|
| **WordPress** | 5.0 or higher |
| **WooCommerce** | 5.0 or higher (tested up to 10.7.0) |
| **PHP** | 7.2 or higher |
| **Custom Fields** | ACF or Smart Custom Fields (optional) |

### **Compatibility**
- ✅ WooCommerce HPOS (High-Performance Order Storage)
- ✅ WordPress Multisite
- ✅ Modern PHP versions (7.2 - 8.x)
- ✅ All major WordPress themes

---

## 🚀 Installation

### **Method 1: Automatic Updates (Recommended)**

Once installed, the plugin will automatically check for updates from GitHub:
1. Updates appear in `Dashboard > Updates` like any WordPress plugin
2. Click `Update Now` to install the latest version
3. No manual download needed!

### **Method 2: Manual Installation**

1. **Download** the latest release from [GitHub Releases](https://github.com/imranduzzlo/woocommerce-team-payroll/releases)
2. **Extract** the ZIP file on your computer
3. **IMPORTANT**: Rename the extracted folder from `woocommerce-team-payroll-x.x.x` to `woocommerce-team-payroll` (remove the version number)
4. **Upload** the renamed folder to `/wp-content/plugins/`
5. **Activate** the plugin through the 'Plugins' menu in WordPress
6. **Configure** settings at `Team Payroll > Settings`

> **⚠️ Important Note for Manual Installation:**  
> GitHub automatically adds the version number to the folder name (e.g., `woocommerce-team-payroll-1.0.6`).  
> You **must rename** it to `woocommerce-team-payroll` (without version number) for the plugin to work correctly.

### **Method 3: WordPress Admin Upload**

1. Go to `Plugins > Add New`
2. Click `Upload Plugin`
3. Choose the downloaded ZIP file
4. Click `Install Now`
5. **After installation**, go to your file manager and rename the folder from `woocommerce-team-payroll-x.x.x` to `woocommerce-team-payroll`
6. Return to WordPress and click `Activate`

### **Post-Installation**

After activation, you'll see a notice to flush rewrite rules:
1. Go to `Settings > Permalinks`
2. Click `Save Changes` (no need to modify anything)
3. This ensures My Account endpoints work correctly

---

## ⚙️ Configuration

### **1. General Settings** (`Team Payroll > Settings > General`)

#### **Employee ID Configuration**
```
Employee ID Prefix: PVVB-EMID
```
- Auto-generates unique employee IDs (e.g., PVVB-EMID001)
- Stored in user meta as `vb_user_id`

#### **Contact Information**
Configure contact details shown to inactive employees:
- WhatsApp Number
- Contact Email
- Telegram Username

#### **Order Editor Settings**
- Enable/disable order editing functionality
- Select which order statuses allow editing
- Automatic conflict detection with other plugins

### **2. Commission Settings** (`Settings > Commission`)

```
Agent Commission: 70%
Processor Commission: 30%
```

**How It Works:**
- **Single User (Agent = Processor)**: Gets 100% of commission
- **Two Users**: Commission split by configured percentages
- **Salary-Aware**: Only commission-eligible employees receive commission

**Commission Calculation Statuses:**
- Configure which order statuses trigger commission calculation
- Default: `completed`, `processing`

### **3. Field Mapping** (`Settings > WooCommerce`)

#### **Checkout Fields**
```
Agent Field Name: order_agent_name
Processor Field Name: _processor_user_id
```

#### **Agent User Roles**
Select which user roles can be agents:
- ☑️ Shop Employee
- ☑️ Shop Manager
- ☑️ Administrator

#### **Product Commission Field**
```
Commission Field Name: team_commission
```
Set commission percentage per product (e.g., 10 = 10%)

### **4. Frontend Styling** (`Settings > Styling`)

Customize the appearance of My Account pages:
- **Colors**: Primary, secondary, text, background
- **Typography**: Font family, sizes, weights
- **Buttons**: Background, text, hover states, border radius
- **Tables**: Header, row hover, borders
- **Custom CSS**: Add your own CSS rules

### **5. Performance Settings** (`Settings > Performance`)

Configure goals and achievements:
- **Goal Types**: Orders, revenue, commission
- **Achievement Rewards**: Money or badges
- **Leaderboard**: Enable/disable public rankings

---

## 📖 Usage Guide

### **For Administrators**

#### **Adding Employees**
1. Go to `Users > Add New`
2. Create user with role: Shop Employee, Shop Manager, or Administrator
3. Go to `Team Payroll > Team Members`
4. Click `Manage` next to the employee
5. Configure salary type and amount

#### **Managing Salaries**
```
Salary Types:
├── Commission-Based: Earnings from orders only
├── Fixed Salary: Regular salary (daily/weekly/monthly)
└── Combined: Base salary + commission
```

#### **Adding Payments**
1. Go to employee detail page
2. Click `Add Payment`
3. Enter amount, date, payment method, and notes
4. Payment is tracked in employee's payment history

#### **Viewing Payroll**
1. Go to `Team Payroll > Payroll`
2. Filter by date range
3. View earnings, paid amounts, and due amounts
4. Export data for accounting

### **For Employees**

#### **Viewing Earnings**
1. Go to `My Account > My Earnings`
2. View current month and total earnings
3. See breakdown of salary vs commission
4. Check payment history

#### **Checking Orders**
1. Go to `My Account > My Orders (Commission)`
2. View all orders with commission details
3. Filter by date, status, or role (agent/processor)
4. Click order to see detailed breakdown

#### **Tracking Performance**
1. Go to `My Account > Reports`
2. View performance metrics and charts
3. Track progress toward goals
4. See achievement unlocks

At checkout, current employee can:
- Select an agent from the dropdown (if configured)
- Agent selection determines commission allocation
- If logged-in user is an agent, they're auto-assigned

---

## 🏗️ Architecture

### **Core Classes**

```
includes/
├── class-core-engine.php          # Commission calculation logic
├── class-payroll-engine.php       # Payroll summaries and reports
├── class-settings.php             # Settings management
├── class-dashboard.php            # Admin dashboard
├── class-employee-management.php  # Employee CRUD operations
├── class-checkout-integration.php # Checkout field handling
├── class-myaccount.php           # Frontend My Account tabs
├── class-performance-tracker.php  # Performance goals/achievements
├── class-order-editor.php        # Order editing functionality
├── class-salary-automation.php   # Automatic salary transfers
└── class-github-updater.php      # Auto-update from GitHub
```

### **Database Schema**

#### **User Meta**
```
_wc_tp_fixed_salary          # Boolean: Is fixed salary
_wc_tp_combined_salary       # Boolean: Is combined salary
_wc_tp_salary_amount         # Float: Salary amount
_wc_tp_salary_frequency      # String: daily/weekly/monthly
_wc_tp_salary_history        # Array: Salary change history
_wc_tp_payments              # Array: Payment records
_wc_tp_payment_methods       # Array: Payment method details
_wc_tp_employee_status       # String: active/inactive
_wc_tp_total_earnings        # Float: Total salary earnings
_wc_tp_salary_transactions   # Array: Salary transfer history
vb_user_id                   # String: Employee ID
```

#### **Order Meta**
```
_primary_agent_id            # Int: Agent user ID
_processor_user_id           # Int: Processor user ID
_commission_data             # Array: Commission breakdown
```

### **Hooks & Filters**

#### **Actions**
```php
// Commission calculation
do_action('wc_team_payroll_commission_calculated', $order_id, $commission_data);

// Salary changes
do_action('wc_tp_salary_changed', $user_id, $salary_data);

// Order editing
do_action('wc_team_payroll_order_edited', $order_id);
```

#### **Filters**
```php
// Modify commission data
apply_filters('wc_tp_commission_data', $commission_data, $order);

// Customize menu items
apply_filters('woocommerce_account_menu_items', $items);
```

---

## 🔄 Automatic Updates

This plugin supports **automatic updates from GitHub releases**, just like WordPress.org plugins!

### **How It Works**

1. **Automatic Checks**: Plugin checks GitHub for new releases every 12 hours
2. **Update Notifications**: Updates appear in WordPress admin (Plugins page and Dashboard)
3. **One-Click Update**: Click "Update Now" to install the latest version
4. **Changelog Display**: View release notes before updating
5. **Safe Updates**: Automatic backup recommended before updating

### **Update Features**

✅ **Seamless Integration**: Works with WordPress native update system  
✅ **Version Detection**: Automatically detects when new releases are available  
✅ **Direct Download**: Downloads from GitHub releases  
✅ **Update Details**: View changelog and version info before updating  
✅ **Manual Check**: Force update check anytime with "Check Updates" link

### **Manual Update Check**

**Method 1: Plugin Row**
- Go to `Plugins` page
- Find "WooCommerce Team Payroll"
- Click "Check Updates" link

**Method 2: Updates Page**
- Go to `Dashboard > Updates`
- Click "Check Again" button
- Available updates will appear

**Method 3: Debug Tools**
- Go to `Team Payroll > Settings > Debug`
- Click "Force Update Check"

### **For Plugin Developers**

If you're forking this plugin or want to create releases:

1. **Update Version**: Change version in `woocommerce-team-payroll.php`
2. **Update Changelog**: Add entry to `CHANGELOG.md`
3. **Create Release**: Go to GitHub > Releases > "Draft a new release"
4. **Tag Format**: Use `v1.0.0` format (with 'v' prefix)
5. **Publish**: Click "Publish release"

See `RELEASE-GUIDE.md` for detailed instructions on creating releases.

### **Troubleshooting Updates**

**Update not showing?**
- Wait 12 hours for automatic check
- Click "Check Updates" to force check
- Verify you're on the latest version
- Check GitHub for new releases

**Update failed?**
- Check file permissions
- Ensure WordPress can write to plugins directory
- Try manual installation
- Check error logs for details

---

## 🆘 Support

### **Getting Help**

- **Issues**: [GitHub Issues](https://github.com/imranduzzlo/woocommerce-team-payroll/issues)
- **Documentation**: Check this README and inline code comments
- **Feature Requests**: Submit via GitHub Issues with `enhancement` label

### **Common Issues**

#### **My Account tabs not showing**
1. Go to `Settings > Permalinks`
2. Click `Save Changes`
3. Clear browser cache

#### **Commission not calculating**
1. Check `Settings > Commission`
2. Verify commission calculation statuses
3. Ensure products have commission rates set
4. Check employee salary type (commission-eligible)

#### **Order editor not appearing**
1. Check `Settings > General > Order Editor Settings`
2. Ensure order status is in editable statuses list
3. Verify no conflicting plugins

---

## 📄 License

This plugin is licensed under the **GNU General Public License v2 or later**.

```
Copyright (C) 2024 Imran

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

---

## 👨‍💻 Author

**Imran**
- Website: [imranhossain.me](https://imranhossain.me/)
- GitHub: [@imranduzzlo](https://github.com/imranduzzlo)

---

## 🙏 Acknowledgments

- Built for WooCommerce ecosystem
- Compatible with Advanced Custom Fields (ACF)
- Inspired by real-world team payroll needs

---

**Made with ❤️ by Imran**
