# Location Formatter Usage Guide

## Overview

The Location Formatter automatically converts Bangladesh state codes (like `BD-58`) and thana codes (like `BD-58-03`) to readable labels throughout your plugin.

---

## Features

✅ **Automatic Conversion** - Works everywhere in WooCommerce
✅ **BD Thana Add Plugin Support** - Uses thana.json if plugin exists
✅ **WooCommerce States Support** - Uses WooCommerce states.php
✅ **Fallback to Codes** - Shows codes if plugin not installed
✅ **No Configuration Needed** - Works automatically

---

## Where It Works

### 1. Order Pages (Admin)
- Billing address
- Shipping address
- Order details

### 2. Order Emails
- Customer emails
- Admin emails

### 3. My Account Pages
- Address display
- Order history

### 4. Anywhere in Your Code
- Custom displays
- Reports
- Dashboards

---

## Helper Functions

### Get State Label
```php
// Convert state code to label
$state_label = wc_tp_get_state_label( 'BD-58' );
// Returns: "Dhaka" (if data available) or "BD-58" (if not)
```

### Get Thana Label
```php
// Convert thana code to label
$thana_label = wc_tp_get_thana_label( 'BD-58-03' );
// Returns: "Dhanmondi" (if data available) or "BD-58-03" (if not)
```

### Format Complete Location
```php
// Combine state and thana
$location = wc_tp_format_location( 'BD-58', 'BD-58-03' );
// Returns: "Dhanmondi, Dhaka"
```

### Format Text with Codes
```php
// Replace all codes in text
$text = "Order from BD-58-03, BD-58";
$formatted = wc_tp_format_text( $text );
// Returns: "Order from Dhanmondi, Dhaka"
```

---

## Usage Examples

### Example 1: In Order Display
```php
// Get order
$order = wc_get_order( $order_id );

// Get billing state
$state_code = $order->get_billing_state();
$state_label = wc_tp_get_state_label( $state_code );

echo "State: " . $state_label;
// Output: "State: Dhaka" instead of "State: BD-58"
```

### Example 2: In Reports
```php
// Format agent location
$agent_state = get_user_meta( $agent_id, 'billing_state', true );
$agent_city = get_user_meta( $agent_id, 'billing_city', true );

$location = wc_tp_format_location( $agent_state, $agent_city );

echo "Agent Location: " . $location;
// Output: "Agent Location: Dhanmondi, Dhaka"
```

### Example 3: In Custom Table
```php
// Display orders with formatted locations
foreach ( $orders as $order ) {
    $state = $order->get_billing_state();
    $city = $order->get_billing_city();
    
    echo '<tr>';
    echo '<td>' . $order->get_order_number() . '</td>';
    echo '<td>' . wc_tp_format_location( $state, $city ) . '</td>';
    echo '</tr>';
}
```

### Example 4: In Dashboard Widget
```php
// Show top performing locations
$locations = array(
    array( 'state' => 'BD-58', 'thana' => 'BD-58-03', 'sales' => 50000 ),
    array( 'state' => 'BD-13', 'thana' => 'BD-13-01', 'sales' => 45000 ),
);

foreach ( $locations as $loc ) {
    $location_name = wc_tp_format_location( $loc['state'], $loc['thana'] );
    echo $location_name . ': ' . wc_price( $loc['sales'] ) . '<br>';
}
// Output:
// Dhanmondi, Dhaka: ৳50,000
// Chittagong City, Chittagong: ৳45,000
```

---

## Advanced Usage

### Check if Data is Available
```php
$formatter = wc_tp_location_formatter();

// Check if BD Thana Add plugin is active
if ( $formatter->is_thana_plugin_active() ) {
    echo "Thana data available";
}

// Check if state data is loaded
if ( $formatter->is_state_data_loaded() ) {
    echo "State data available";
}
```

### Get All States
```php
$formatter = wc_tp_location_formatter();
$states = $formatter->get_all_states();

// Display dropdown
echo '<select name="state">';
foreach ( $states as $code => $label ) {
    echo '<option value="' . esc_attr( $code ) . '">' . esc_html( $label ) . '</option>';
}
echo '</select>';
```

### Get All Thanas
```php
$formatter = wc_tp_location_formatter();
$thanas = $formatter->get_all_thanas();

// Display dropdown
echo '<select name="thana">';
foreach ( $thanas as $thana ) {
    $code = $thana['value'] ?? '';
    $label = $thana['label'] ?? $code;
    echo '<option value="' . esc_attr( $code ) . '">' . esc_html( $label ) . '</option>';
}
echo '</select>';
```

---

## Integration with Existing Code

### In Order Details Modal
```php
// In class-order-details-modal.php
$billing_state = $order->get_billing_state();
$billing_city = $order->get_billing_city();

// Instead of showing codes
echo $billing_state; // BD-58
echo $billing_city;  // BD-58-03

// Show labels
echo wc_tp_get_state_label( $billing_state ); // Dhaka
echo wc_tp_get_thana_label( $billing_city );  // Dhanmondi
```

### In Reports
```php
// In class-reports.php
$order_location = $order->get_billing_state() . ', ' . $order->get_billing_city();

// Instead of: "BD-58, BD-58-03"
// Use:
$order_location = wc_tp_format_location(
    $order->get_billing_state(),
    $order->get_billing_city()
);
// Output: "Dhanmondi, Dhaka"
```

### In Dashboard
```php
// In class-dashboard.php
foreach ( $top_agents as $agent ) {
    $location = get_user_meta( $agent->ID, 'billing_state', true );
    
    // Format location
    $location_label = wc_tp_get_state_label( $location );
    
    echo $agent->display_name . ' - ' . $location_label;
}
```

---

## Data Sources

### BD Thana Add Plugin
- **File**: `wp-content/plugins/bd-thana-add/data/thana.json`
- **Format**: JSON array with `value` and `label` keys
- **Example**:
```json
[
    {
        "value": "BD-58-03",
        "label": "Dhanmondi"
    }
]
```

### WooCommerce States
- **File**: `wp-content/plugins/woocommerce/i18n/states.php`
- **Format**: PHP array
- **Example**:
```php
'BD' => array(
    'BD-58' => 'Dhaka',
    'BD-13' => 'Chittagong',
)
```

---

## Fallback Behavior

If data files don't exist:
- ✅ Shows original codes (BD-58, BD-58-03)
- ✅ No errors or warnings
- ✅ Plugin continues to work normally

---

## Performance

- ✅ Data loaded once per request
- ✅ Cached in memory
- ✅ No database queries
- ✅ Minimal overhead

---

## Compatibility

- ✅ Works with or without BD Thana Add plugin
- ✅ Compatible with all WooCommerce versions
- ✅ No conflicts with other plugins
- ✅ Safe to use everywhere

---

## Tips

1. **Always use helper functions** - They're easier and cleaner
2. **Format in display, not storage** - Store codes, display labels
3. **Check for empty values** - Functions handle empty strings gracefully
4. **Use in templates** - Works great in email templates
5. **Combine with other data** - Mix with order data, user data, etc.

---

## Example: Complete Order Display

```php
// Get order
$order = wc_get_order( $order_id );

// Get address data
$billing_address = array(
    'first_name' => $order->get_billing_first_name(),
    'last_name'  => $order->get_billing_last_name(),
    'address_1'  => $order->get_billing_address_1(),
    'city'       => wc_tp_get_thana_label( $order->get_billing_city() ),
    'state'      => wc_tp_get_state_label( $order->get_billing_state() ),
    'postcode'   => $order->get_billing_postcode(),
    'country'    => $order->get_billing_country(),
);

// Display formatted address
echo WC()->countries->get_formatted_address( $billing_address );
```

---

**Made with ❤️ by Imran**
