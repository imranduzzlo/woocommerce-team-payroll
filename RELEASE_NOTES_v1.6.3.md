# Release v1.6.3 - Attributed Total with Unified Earnings Logic

## 🎯 Overview
This release refactors the Attributed Total column to use the same unified calculation logic as the "Your Earnings" column, ensuring consistency and accuracy across all order displays.

## ✨ What's New

### Attributed Total Column - Unified Logic
The Attributed Total column now uses the **exact same calculation logic** as "Your Earnings":

- **Owner (Agent + Processor)**: Shows full order total
- **Agent Only**: Shows agent's attributed portion (agent_order_value)
- **Processor Only**: Shows processor's attributed portion (processor_order_value)

### Benefits
- ✅ **Consistent Logic**: Attributed Total and Earnings use identical calculation methods
- ✅ **Accurate Attribution**: Proper sales attribution per employee role
- ✅ **Performance Metrics**: Enables accurate AOV and conversion rate calculations
- ✅ **Fair Comparisons**: Compare agents and processors based on their actual order responsibility
- ✅ **Owner Recognition**: Owners correctly see full order value when they handle both roles

## 📊 Example

| Scenario | Order Total | Attributed Total | Your Earnings |
|----------|-------------|------------------|---------------|
| **Owner** (Agent + Processor) | $1,000 | **$1,000** | $100 |
| **Agent Only** (70% split) | $1,000 | **$700** | $70 |
| **Processor Only** (30% split) | $1,000 | **$300** | $30 |

## 🔧 Technical Details

### Calculation Logic
```php
// Same logic as earnings calculation
if ( $is_agent && $is_processor ) {
    $attributed_value = $order->get_total(); // Owner gets full order total
} elseif ( $user_role === 'agent' ) {
    $attributed_value = $commission_data['agent_order_value']; // Agent's portion
} elseif ( $user_role === 'processor' ) {
    $attributed_value = $commission_data['processor_order_value']; // Processor's portion
}
```

### Files Modified
- `includes/class-ajax-handlers.php` - Unified attributed total calculation with earnings logic
- `includes/class-employee-detail.php` - Restored Attributed Total column in order table
- `woocommerce-team-payroll.php` - Version bump to 1.6.3
- `CHANGELOG.md` - Added release notes

## 📦 Installation

### Automatic Update (Recommended)
If you have the plugin installed, you'll receive an automatic update notification in your WordPress admin panel.

### Manual Installation
1. Download the latest release ZIP file
2. Go to WordPress Admin → Plugins → Add New → Upload Plugin
3. Upload the ZIP file and click "Install Now"
4. Activate the plugin

### GitHub Installation
```bash
cd wp-content/plugins/
git clone https://github.com/imranduzzlo/woocommerce-team-payroll.git
cd woocommerce-team-payroll
git checkout v1.6.3
```

## 🔄 Upgrade Notes
- No database changes required
- No breaking changes
- Safe to upgrade from any 1.6.x version

## 📝 Full Changelog
See [CHANGELOG.md](CHANGELOG.md) for complete version history.

## 🐛 Bug Reports
Found a bug? Please report it on our [GitHub Issues](https://github.com/imranduzzlo/woocommerce-team-payroll/issues) page.

## 💬 Support
For support and questions, please visit our [GitHub repository](https://github.com/imranduzzlo/woocommerce-team-payroll).

---

**Released on:** April 20, 2026  
**Commit:** e54ade4  
**Tag:** v1.6.3
