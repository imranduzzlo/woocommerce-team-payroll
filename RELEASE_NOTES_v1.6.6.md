# Release Notes - v1.6.6

## 🎯 CRITICAL FIX - Attributed Total for Owner-Only Orders

### The Problem
When viewing employee order details, the "Attributed Total" column was showing incorrect values for owner-only orders (orders with no processor assigned):

- **Expected**: Order ৳785 → Attributed Total ৳785 (100%)
- **Actual**: Order ৳785 → Attributed Total ৳549.50 (70%)

The issue was in the commission calculation engine - it was ALWAYS splitting the attributed order value by percentage (70% agent, 30% processor), even when there was no processor involved.

### The Fix
Modified `includes/class-core-engine.php` to calculate attributed order values intelligently:

**Before:**
```php
'agent_order_value' => ( $order_total * 70 ) / 100,  // Always 70%
'processor_order_value' => ( $order_total * 30 ) / 100,  // Always 30%
```

**After:**
```php
if ( $agent_id === $processor_id || ! $processor_id ) {
    // Owner-only order: Full attribution
    $agent_order_value = $order_total;  // 100%
    $processor_order_value = 0;
} else {
    // Split order: Percentage attribution
    $agent_order_value = ( $order_total * 70 ) / 100;
    $processor_order_value = ( $order_total * 30 ) / 100;
}
```

### What Changed
1. **Attributed order values now calculated at the source** - Fixed in the commission calculation engine where data is stored
2. **Smart attribution logic** - Matches exactly how earnings are distributed
3. **Database values corrected** - New orders will store correct attributed values

### Impact
- ✅ **Owner-only orders**: Show full order value (100%) in attributed total
- ✅ **Split orders**: Show correct percentage attribution (70%/30%)
- ✅ **Consistency**: Attributed total logic now matches earnings logic perfectly
- ✅ **Database integrity**: Values stored correctly from the start

### Example Scenarios

#### Scenario 1: Owner-Only Order
- Order Total: ৳785
- Agent: User #1
- Processor: None
- **Attributed Total for User #1**: ৳785 (100%) ✅

#### Scenario 2: Split Order
- Order Total: ৳1000
- Agent: User #1
- Processor: User #2
- **Attributed Total for User #1**: ৳700 (70%) ✅
- **Attributed Total for User #2**: ৳300 (30%) ✅

#### Scenario 3: Owner as Both Roles
- Order Total: ৳500
- Agent: User #1
- Processor: User #1
- **Attributed Total for User #1**: ৳500 (100%) ✅

### Upgrade Notes
- **Existing orders**: Will need commission recalculation to update attributed values
- **New orders**: Will automatically have correct attributed values
- **No breaking changes**: Frontend display logic unchanged

---

**Full Changelog**: [View CHANGELOG.md](CHANGELOG.md)
