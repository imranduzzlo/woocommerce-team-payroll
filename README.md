# WooCommerce Team Payroll & Commission System

A comprehensive WordPress plugin for managing team-based commission and payroll systems with agents and processors.

## Features

- **Commission Management**: Flexible commission calculation with agent/processor split (customizable percentages)
- **Salary Types**: Fixed salary, commission-based, or combined (base + commission)
- **Salary History**: Track all salary changes with timestamps and change logs
- **Manual Payments**: Add payments with date/time picker via AJAX
- **Per-Order Bonuses**: Add employee-specific bonuses to orders
- **Extra Earnings Rules**: Define conditional earnings rules with multiple condition types
- **Refunded Order Commission**: Handle commission for refunded orders (None/Percentage/Flat)
- **Order Change Logging**: Automatic logging of all order updates and changes
- **Frontend Integration**: My Account tabs for employees to view earnings and orders
- **Checkout Integration**: Auto-populate agent dropdown with role-based filtering
- **Admin Dashboards**: Team Dashboard, Payroll Management, Employee Management
- **AJAX Operations**: All operations work without page reloads
- **Shortcode System**: Display earnings data anywhere on your site

## Requirements

- WordPress 5.0+
- WooCommerce 5.0+
- Advanced Custom Fields (ACF) or Smart Custom Fields (SCF)
- PHP 7.2+

## Installation

1. Download the plugin from GitHub
2. Upload to `/wp-content/plugins/`
3. Activate the plugin in WordPress admin
4. Configure settings in Team Payroll > Settings

## Automatic Updates

This plugin supports automatic updates from GitHub. Updates will appear in your WordPress admin panel like any other plugin update.

## Configuration

### Commission Settings
- Set agent and processor commission percentages
- Configure refunded order commission handling
- Enable/disable breakdown tables and features

### Field Mapping
- Map your custom checkout fields (ThemeHigh)
- Map your ACF/SCF product fields
- Select which user roles can be agents

### Extra Earnings Rules
- Create conditional earnings rules
- Set conditions: order total, specific products, categories, or agents
- Set rule end dates for automatic deactivation

## Support

For issues and feature requests, visit: https://github.com/imranduzzlo/pv-team-payroll

## License

GPL v2 or later
