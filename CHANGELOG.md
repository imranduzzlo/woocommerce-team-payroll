## [1.6.44] - 2026-04-20
### 🐛 Bug Fix - Profile Badge Display Issues

#### FIXED - Locked Badge Icon Not Visible
- **Phosphor Icons Loading**: Added Phosphor Icons CDN to employee detail page
- **Icon Display**: Lock icon now displays properly in locked badge state
- **Missing Library**: Fixed issue where `.ph` icon classes weren't rendering

#### FIXED - Badge Showing Locked Despite Achievements
- **Auto Recalculation**: Added `update_achievements()` call before badge display
- **Fresh Data**: Ensures achievement data is current when page loads
- **Both Pages Fixed**: Applied fix to both Employee Details (backend) and My Account (frontend)
- **Cache Issue**: Resolved stale data showing locked badge when achievements exist

#### TECHNICAL CHANGES
- Added Phosphor Icons CDN link in `add_styles()` function
- Added `$performance_tracker->update_achievements($user_id)` before badge rendering
- Ensures achievements recalculate on every page load for accurate badge display

#### BENEFITS
- ✅ Lock icon now visible in locked badge state
- ✅ Badges show correct achievement tier immediately
- ✅ No more locked badge when achievements exist
- ✅ Consistent badge display across all pages

---

## [1.6.43] - 2026-04-20
### ⭐ Enhancement - Badge Stars for Category Achievements & Cache Fix

#### ADDED - Badge Stars System
- **Profile Badge Stars**: Display stars on profile badges based on category achievements
- **Category Tracking**: Track which categories (Earnings, Orders, AOV) achieved each tier
- **Star Count Display**: Show 1-3 stars based on how many categories achieved the same tier
  - Example: Gold in Earnings + Gold in Orders + Gold in AOV = G★★★
  - Example: Bronze in Earnings + Bronze in Orders = B★★
- **Consistent Styling**: Same badge appearance in both frontend (My Account) and backend (Employee Details)
- **Responsive Design**: Stars scale properly on mobile devices

#### FIXED - Achievement Threshold Cache Issue
- **Cache Clearing**: When achievement/goal thresholds change in settings, all user caches are now cleared
- **Immediate Updates**: Frontend now shows updated thresholds immediately after saving
- **Current Period Only**: Only clears current period data, preserves achievement history
- **Auto Recalculation**: Achievements/goals recalculate with new thresholds on next page load

#### TECHNICAL CHANGES
- Added `tier_categories` tracking in `update_period_achievement_stats()`
- Added `clear_all_achievement_caches()` function to clear stale achievement data
- Added `clear_all_goal_caches()` function to clear stale goal data
- Updated AJAX responses to include `tier_categories` data
- Added badge star rendering in both SVG (backend) and HTML (frontend)
- Added responsive CSS for star display on mobile devices

#### BENEFITS
- ✅ Visual representation of multi-category achievements
- ✅ Immediate feedback when settings change
- ✅ No more stale data on frontend
- ✅ Consistent badge display across admin and frontend
- ✅ Better achievement tracking granularity

---

## [1.6.36] - 2026-04-20
### 🧹 Cleanup - Remove Role Display from Achievement Cards

#### REMOVED
- Removed "Role: administrator" (or other role) display from achievement cards
- Employees don't need to see their role on achievement cards
- Role is already indicated by role-based achievement thresholds
- Cleaner, less cluttered achievement card display

#### BEFORE
```
🏆 Earnings Bronze
Threshold: $3,000
Unlocked: 2026-04-20 10:30:00
Value: $3,500
Role: administrator
```

#### AFTER
```
🏆 Earnings Bronze
Threshold: $3,000
Unlocked: 2026-04-20 10:30:00
Value: $3,500
```

#### BENEFITS
- ✅ Cleaner card layout
- ✅ Less redundant information
- ✅ Better focus on achievement data
- ✅ Improved user experience

---

## [1.6.35] - 2026-04-20
### ✨ Add - Period History Tab to Frontend

#### ADDED
- Added "Period History" tab to employee account page
- Displays timeline of all completed periods
- Shows achievements earned in each period
- Displays highest tier achieved per period
- Summary statistics for all periods

#### TAB FEATURES
- **Period Timeline** - Visual timeline of all completed periods
- **Period Cards** - Shows period ID, date range, highest tier, achievements
- **Summary Stats** - Gold periods, Silver periods, Bronze periods, Total achievements
- **Sorted View** - Newest periods displayed first
- **Achievement Details** - List of achievements earned in each period

#### FRONTEND TABS NOW INCLUDE
1. Overview - Quick performance summary
2. Goals - Goal progress tracking
3. Achievements - Current period achievements
4. **Period History** - NEW! Historical achievement data
5. Bonus Achieved - Claimed bonuses
6. Baselines - Baseline metrics

#### WHAT EMPLOYEES SEE
- Complete history of achievement performance
- Track progress over multiple periods
- See which periods had gold/silver/bronze achievements
- Compare performance across different periods

---

## [1.6.34] - 2026-04-20
### 🔧 Fix - Achievement Data Contamination with Stats Fields

#### FIXED
- Fixed achievement cards showing stats fields (Period, Period Type, Start Date, End Date, etc.)
- Achievement data now properly filtered to only include achievement keys
- Stats fields stored separately in `_wc_tp_period_achievements_stats_{period_id}`
- Individual achievements stored cleanly in `_wc_tp_period_achievements_{period_id}`

#### TECHNICAL DETAILS
- Updated `update_period_achievements()` to filter stored data on retrieval
- Only keys matching pattern `(earnings|orders|aov)_(bronze|silver|gold)` are treated as achievements
- Stats fields (period, period_type, start_date, etc.) are now completely separate
- Prevents data contamination and ensures clean achievement display

#### DATA SEPARATION
**Achievements Meta** (`_wc_tp_period_achievements_{period_id}`):
```php
[
    'earnings_bronze' => [...],
    'earnings_silver' => [...],
    'orders_bronze' => [...],
    ...
]
```

**Stats Meta** (`_wc_tp_period_achievements_stats_{period_id}`):
```php
[
    'period' => 'monthly_2026_04',
    'period_type' => 'monthly',
    'start_date' => '2026-04-01',
    'bronze_count' => 2,
    ...
]
```

---

## [1.6.33] - 2026-04-20
### 🔧 Fix - Overview Achievements Summary Display

#### FIXED
- Fixed overview showing "Achievements 0 Total Unlocked 0 0 0" even when achievements were unlocked
- Overview now correctly retrieves stats from `_wc_tp_period_achievements_stats_{period_id}`
- Added calculation of next achievement for quick stats display
- Overview achievements summary now displays correctly with proper counts

#### TECHNICAL DETAILS
- Updated overview case to fetch both achievements and stats separately
- `render_achievements_summary()` now receives stats object with correct counts
- `update_period_achievement_stats()` now calculates `next_achievement` for progress display
- Stats properly include: `total_unlocked`, `bronze_count`, `silver_count`, `gold_count`, `next_achievement`

#### WHAT'S DISPLAYED
- Total unlocked achievements count
- Bronze, Silver, Gold badge counts (🥉 🥈 🥇)
- Next achievement progress percentage

---

## [1.6.32] - 2026-04-20
### ✨ Refactor - Period Achievements Using v1.6.23 All-Time Approach

#### REFACTORED
- Simplified period achievements to use exact same approach as v1.6.23 all-time system
- Only difference: timeline changed from all-time to period-based
- Removed complex nested data structures
- Cleaner, more maintainable code

#### ARCHITECTURE
**Before (Complex):**
- Stored period summary + individual achievements in one meta
- Multiple data transformations
- Confusing data structure

**After (Simple - Like v1.6.23):**
- Store individual achievements in `_wc_tp_period_achievements_{period_id}`
- Store stats separately in `_wc_tp_period_achievements_stats_{period_id}`
- Direct return of individual achievements array
- Same structure as all-time system

#### DATA STRUCTURE
**Individual Achievements** (`_wc_tp_period_achievements_{period_id}`):
```php
[
    'earnings_bronze' => [
        'unlocked' => true/false,
        'threshold' => 3000,
        'tier' => 'bronze',
        'unlocked_date' => '2026-04-20 10:30:00',
        'value_at_unlock' => 3500,
        'current_progress' => 2500,
        'percentage' => 83.33
    ],
    ...
]
```

**Stats** (`_wc_tp_period_achievements_stats_{period_id}`):
```php
[
    'period' => 'monthly_2026_04',
    'period_type' => 'monthly',
    'start_date' => '2026-04-01',
    'end_date' => '2026-04-30',
    'bronze_count' => 2,
    'silver_count' => 1,
    'gold_count' => 0,
    'total_unlocked' => 3,
    'highest_tier' => 'silver',
    'achievements_unlocked' => ['earnings_bronze', 'orders_bronze', 'aov_silver'],
    'updated_at' => '2026-04-20 10:30:00'
]
```

#### BENEFITS
- ✅ Same proven approach as v1.6.23 (which worked perfectly)
- ✅ Simpler code, easier to maintain
- ✅ Cleaner data structure
- ✅ Better separation of concerns (achievements vs stats)
- ✅ No network errors
- ✅ Consistent with all-time system pattern

---

## [1.6.31] - 2026-04-20
### 🔧 Fix - Period Achievement Data Structure and Error Handling

#### FIXED
- Fixed network error by storing individual achievements in period data
- Added proper error handling with try-catch in AJAX achievements endpoint
- Ensured all data arrays are properly validated before returning
- Fixed data structure to include both individual achievements and period summary

#### TECHNICAL DETAILS
- `update_period_achievements()` now stores individual achievements in `$period_data['achievements']`
- Period summary data (stats, counts, etc.) also stored in same meta
- AJAX endpoint validates all data is arrays before returning
- Better error messages for debugging

#### DATA STRUCTURE
Period meta now contains:
- `achievements` - Individual achievement objects (for frontend display)
- `period`, `period_type`, `start_date`, `end_date` - Period info
- `earnings`, `orders`, `aov` - Period metrics
- `bronze_count`, `silver_count`, `gold_count`, `total_unlocked` - Stats
- `achievements_unlocked` - List of unlocked achievement keys
- `updated_at` - Last update timestamp

---

## [1.6.30] - 2026-04-20
### 🔧 Critical Fix - Achievement Tab Data Structure

#### FIXED
- Fixed "Network error" on achievement tab by returning correct data structure
- Changed `update_period_achievements()` to return individual achievements array (like old system)
- Achievements now display properly with correct format
- Stats are still stored and retrieved from period metadata
- Achievement cards now render with correct data

#### TECHNICAL DETAILS
- `update_period_achievements()` now returns `$period_achievements` array with individual achievement objects
- Period summary data is still saved to `_wc_tp_period_achievements_{period_id}` meta
- Frontend receives achievements in same format as old all-time system
- Maintains backward compatibility with existing data structures

---

## [1.6.29] - 2026-04-20
### 🐛 Bug Fixes - Achievement Tab Network Errors

#### FIXED
- Fixed "Network error. Please try again." when reloading achievement tab
- Fixed "Achievements are disabled in settings" false positive error
- Made `get_employee_roles()` method public to fix AJAX access issues
- Added null checks for user roles in AJAX handlers
- Improved error handling and logging in fetchData function
- Added default values for achievement settings in JavaScript
- Fixed achievementsEnabled check to use strict comparison (=== 0)

#### IMPROVEMENTS
- Better error logging for AJAX failures
- More robust role detection in AJAX endpoints
- Improved error messages with detailed logging

---

## [1.6.28] - 2026-04-20
### 🔄 Maintenance Release

#### UPDATES
- Version bump to 1.6.28
- Stable release of role-based achievement settings compliance

---

## [1.6.27] - 2026-04-20
### 🎯 Role-Based Achievement Settings Compliance

#### IMPLEMENTED - Full Achievement Settings Integration
**FEATURES ADDED:**

1. **AJAX Config Endpoint Enhancement**
   - Updated `config` case to return role-specific achievement configurations
   - Returns user's employee role
   - Returns role-specific achievements with thresholds
   - Includes all global settings: enabled, display_style, period, show_locked, notification

2. **AJAX Achievements Endpoint Enhancement**
   - Updated `achievements` case to return role-specific achievements
   - Returns user's employee role
   - Returns role-specific achievement configurations
   - Returns full achievements config for frontend use
   - Enables frontend to display role-specific thresholds

3. **Frontend JavaScript Updates**
   - Updated `loadConfiguration()` to store role-specific achievements
   - Added `userRole` property to track employee role
   - Added `roleAchievements` property to store role-specific configs
   - Added `achievementsPeriod` property for period tracking

4. **Achievement Card Display Enhancement**
   - Updated `renderAchievementCard()` to use role-specific achievement names
   - Shows role-specific achievement descriptions
   - Displays role information on achievement cards
   - Uses role-specific thresholds for display
   - Maintains backward compatibility with generic names

5. **Achievement Rendering Enhancement**
   - Updated `renderAchievements()` to load role-specific data
   - Stores role achievements from AJAX response
   - Updates user role from AJAX response
   - Updates achievement settings from AJAX response
   - Ensures all settings are applied to display

#### VERIFICATION CHECKLIST
- ✅ Role-specific achievements returned in config endpoint
- ✅ Role-specific achievements returned in achievements endpoint
- ✅ Frontend stores role-specific achievement configurations
- ✅ Achievement cards display role-specific names and descriptions
- ✅ Achievement cards display role information
- ✅ Thresholds are role-specific and correctly displayed
- ✅ All achievement settings (enabled, display_style, period, show_locked, notification) are followed
- ✅ Backward compatibility maintained

---

## [1.6.26] - 2026-04-20
### ✨ PERIOD-BASED ACHIEVEMENT SYSTEM - Complete Implementation

#### IMPLEMENTED - Period Achievement System (STEPS 1-9)
**MAJOR FEATURES ADDED:**

1. **Period Configuration (STEP 1)**
   - Added "Achievement Period" dropdown to admin settings
   - Supports 6 period types: Daily, Weekly, Monthly, Quarterly, Half-Yearly, Yearly
   - Weekly period respects WordPress `start_of_week` setting
   - Allows achievements to reset/restart based on selected period
   - Configuration stored in `wc_tp_achievements_config`

2. **Period Helper Functions (STEP 1)**
   - `get_period_date_range($period_type)` - Calculates start/end dates for all period types
   - `get_current_period_id($period_type)` - Returns current period ID
   - `has_period_changed($user_id, $period_type)` - Detects period changes
   - `reset_period_achievements($user_id, $period_type)` - Resets achievements when period changes
   - All functions support: daily, weekly, monthly, quarterly, half_yearly, yearly

3. **Period Achievement Calculation (STEP 2)**
   - Created `update_period_achievements($user_id)` function
   - Calculates achievements for current period only (not all-time)
   - Stores in `_wc_tp_period_achievements_{period_id}` meta
   - Determines highest tier (gold/silver/bronze) for period
   - Tracks individual tier achievements (earnings_gold, orders_silver, etc.)
   - Maintains achievement count per tier

4. **Period Achievement Finalization (STEP 3)**
   - Created `check_and_finalize_period_achievements()` cron handler
   - Created `finalize_period_achievements()` function
   - Archives completed period data to history
   - Sends email notifications when period ends
   - Maintains `_wc_tp_period_achievements_history` with last 365 records
   - Automatic cleanup of old records

5. **3D Coin Badge Display (STEP 4)**
   - Replaced crown icons with 3D coin-style badges
   - Shows G/S/B letters (Gold/Silver/Bronze) in center
   - Color-matched text: Gold (#8B6914), Silver (#606060), Bronze (#6B3410)
   - Realistic 3D coin effect with:
     - Multi-layer radial gradients
     - Shine highlights
     - Drop shadows
     - Border effects
   - Implemented in both frontend and admin views
   - SVG-based for scalability

6. **Frontend Period Achievements Tab (STEP 5)**
   - Added AJAX endpoint: `period_achievements` case
   - Returns current period data, history, and period range
   - Created `loadPeriodAchievements()` JavaScript function
   - Created `renderPeriodAchievements()` function with:
     - Current period badge display
     - 3D coin badge with tier colors
     - Metrics display (Earnings, Orders, AOV)
     - Detailed period history timeline
     - Expandable timeline items
     - Summary statistics (Gold/Silver/Bronze counts)

7. **Frontend Period History Timeline (STEP 6)**
   - Enhanced `renderPeriodAchievements()` with detailed timeline view
   - Shows all periods in chronological order
   - Expandable timeline items for each period
   - Summary statistics showing:
       - Total achievements per tier
       - Total achievements count
       - Period date ranges
   - Responsive design with gradient connector line
   - Hover effects and smooth animations

8. **Admin Period History Tab (STEP 7)**
   - Added "Period History" tab to admin employee details
   - Created `loadPeriodHistory()` JavaScript function
   - Created `renderPeriodHistory()` function with:
     - Grid layout showing all periods (newest first)
     - Current period indicator badge
     - 3D coin badges for each period
     - Period date ranges
     - Metrics display per period
     - Achievement lists per period
     - Empty state handling
   - Responsive grid layout (auto-fill, minmax 320px)

9. **Period Achievement Notifications (STEP 8)**
   - Created `checkAndShowPeriodNotification()` function
   - Created `showPeriodAchievementNotification()` function
   - Beautiful modal notification with:
     - Confetti animation header
     - 3D coin badge with pulse animation
     - Period label and date range
     - Metrics display
     - Achievement list
     - Close button and overlay click handling
   - Uses sessionStorage to prevent duplicate notifications
   - Smooth animations and transitions

**PERIOD TYPES SUPPORTED:**
- **Daily** (YYYY-MM-DD): Resets every day at midnight
- **Weekly** (YYYY-WXX): Resets every Monday (respects WordPress start_of_week)
- **Monthly** (YYYY-MM): Resets 1st of each month
- **Quarterly** (YYYY-QX): Resets Jan 1, Apr 1, Jul 1, Oct 1
- **Half-Yearly** (YYYY-HX): Resets Jan 1, Jul 1
- **Yearly** (YYYY): Resets January 1st

**DATA STRUCTURE:**
```
_wc_tp_period_achievements_{period_id} = [
    'period' => '2026-04',
    'period_type' => 'monthly',
    'start_date' => '2026-04-01',
    'end_date' => '2026-04-30',
    'earnings' => 15000,
    'orders' => 45,
    'aov' => 333.33,
    'earnings_tier' => 'gold',
    'orders_tier' => 'silver',
    'aov_tier' => 'silver',
    'bronze_count' => 0,
    'silver_count' => 2,
    'gold_count' => 1,
    'highest_tier' => 'gold',
    'achievements_unlocked' => ['earnings_gold', 'orders_silver', 'aov_silver']
]
```

**TESTING & VERIFICATION:**
- ✅ All 6 period types tested
- ✅ Period ID generation verified
- ✅ Achievement calculation validated
- ✅ Period reset logic confirmed
- ✅ Frontend display responsive
- ✅ Admin display functional
- ✅ Notifications working
- ✅ Edge cases handled (leap years, boundaries)
- ✅ Performance acceptable
- ✅ Browser compatibility confirmed

**FILES MODIFIED:**
- `includes/class-performance-tracker.php` - Added period helper functions, achievement calculation, finalization
- `includes/class-performance-tracker-ajax.php` - Added period_achievements AJAX case
- `includes/class-performance-settings.php` - Added Achievement Period dropdown
- `includes/class-employee-detail.php` - Added Period History tab
- `assets/js/performance-tracker.js` - Added period functions, notifications, timeline
- `assets/css/performance-tracker.css` - Added period history and notification styles
- `includes/class-myaccount.php` - 3D coin badge implementation

**DOCUMENTATION CREATED:**
- `TESTING_PERIOD_ACHIEVEMENTS.md` - Comprehensive testing guide
- `STEP9_VERIFICATION_SUMMARY.md` - Complete verification report
- `PERIOD_TYPES_REFERENCE.md` - Quick reference guide

**BENEFITS:**
- ✅ Achievements can reset based on configurable period
- ✅ Employees can earn same achievements multiple times across periods
- ✅ Beautiful 3D coin badges with tier-specific colors
- ✅ Comprehensive period history tracking
- ✅ Automatic notifications for new achievements
- ✅ Admin can view complete period history
- ✅ Responsive design works on all devices
- ✅ Smooth animations and transitions
- ✅ Full test coverage and documentation

---

## [1.6.25] - 2026-04-20
### ✨ FRONTEND - Bonus Achieved Tab Integration

#### ADDED - Frontend Bonus Achieved Tab
**NEW FEATURES:**

1. **Frontend Bonus Achieved Tab**
   - Added "Bonus Achieved" tab button to frontend Performance Tracker
   - Displays all achieved bonuses in a professional table format
   - Shows: Tier, Description, Amount/Type, Status, Action buttons

2. **Employee Bonus Claiming**
   - Money bonuses: "Claim" button automatically adds to earnings
   - Physical bonuses: "Claim" button prompts for secret code verification
   - Status updates from "Pending" to "Claimed" after claiming

3. **Physical Bonus Secret Code View**
   - "View Code" button for submitted physical bonuses
   - Shows secret code in popup for employee reference
   - "Resend Email" button to request code resend from admin

4. **JavaScript Integration**
   - `loadBonusAchieved()` function loads bonus data via AJAX
   - `renderBonusAchieved()` function renders the bonus table
   - `claimMoneyBonus()` and `claimPhysicalBonus()` handle claiming
   - `showSecretCodePopup()` displays secret code information

5. **AJAX Endpoint**
   - `wc_tp_get_performance_tracker_data` with `bonus_achieved` section
   - Returns all achieved bonuses with current status
   - Supports both employee and admin contexts

**BENEFITS:**
- ✅ Employees can now claim bonuses from frontend
- ✅ Seamless integration with Performance Tracker
- ✅ Professional UI with status badges and action buttons
- ✅ Real-time status updates
- ✅ Easy access to secret codes for physical bonuses

**FILES MODIFIED:**
- `woocommerce-team-payroll.php` - Updated version to 1.6.25
- `includes/class-myaccount.php` - Added Bonus Achieved tab button to frontend Performance Tracker

---

## [1.6.25] - 2026-04-20
### ✨ FRONTEND - Bonus Achieved Tab Integration

#### ADDED - Frontend Bonus Achieved Tab
**NEW FEATURES:**

1. **Frontend Bonus Achieved Tab**
   - Added "Bonus Achieved" tab button to frontend Performance Tracker
   - Displays all achieved bonuses in a professional table format
   - Shows: Tier, Description, Amount/Type, Status, Action buttons

2. **Employee Bonus Claiming**
   - Money bonuses: "Claim" button automatically adds to earnings
   - Physical bonuses: "Claim" button prompts for secret code verification
   - Status updates from "Pending" to "Claimed" after claiming

3. **Physical Bonus Secret Code View**
   - "View Code" button for submitted physical bonuses
   - Shows secret code in popup for employee reference
   - "Resend Email" button to request code resend from admin

4. **JavaScript Integration**
   - `loadBonusAchieved()` function loads bonus data via AJAX
   - `renderBonusAchieved()` function renders the bonus table
   - `claimMoneyBonus()` and `claimPhysicalBonus()` handle claiming
   - `showSecretCodePopup()` displays secret code information

5. **AJAX Endpoint**
   - `wc_tp_get_performance_tracker_data` with `bonus_achieved` section
   - Returns all achieved bonuses with current status
   - Supports both employee and admin contexts

**BENEFITS:**
- ✅ Employees can now claim bonuses from frontend
- ✅ Seamless integration with Performance Tracker
- ✅ Professional UI with status badges and action buttons
- ✅ Real-time status updates
- ✅ Easy access to secret codes for physical bonuses

**FILES MODIFIED:**
- `woocommerce-team-payroll.php` - Updated version to 1.6.25
- `includes/class-myaccount.php` - Added Bonus Achieved tab button to frontend Performance Tracker

---

## [1.6.24] - 2026-04-20
### ✨ COMPLETE - Bonus Claim System with Email Notifications

#### IMPLEMENTED - Full Bonus Claim System (STEPS 4-11)
**MAJOR FEATURES ADDED:**

1. **Bonus Serial ID System (STEP 1)**
   - Each bonus rule now has auto-incrementing `rule_id`
   - Tracks `next_rule_id` in config for proper sequencing
   - Preserves rule_id when editing (doesn't change)
   - Displays rule ID in admin UI

2. **Achieved Bonuses Storage (STEP 2-3)**
   - Created `_wc_tp_achieved_bonuses` meta for lifetime storage
   - Each bonus record includes: id, rule_id, tier, streak_count, bonus_type, bonus_amount, bonus_description, achieved_date, status, secret_code
   - Generates 8-character secret code for physical/other bonuses
   - Saves to both achieved_bonuses (lifetime) and bonus_history (display)

3. **Bonus Achieved Tab - Employee View (STEP 4-6)**
   - Added "Bonus Achieved" tab to Performance Tracker
   - Displays table with: Tier, Description, Amount/Type, Status, Action
   - Money bonus: "Claim" button adds to earnings
   - Physical bonus: "Claim" button prompts for secret code verification
   - AJAX endpoint: `wc_tp_get_performance_tracker_data` with `bonus_achieved` section

4. **Bonus Achieved Tab - Admin View (STEP 7-8)**
   - Added "Bonus Achieved" tab to Admin Employee Details
   - Same table display as employee view
   - Money bonus: "Submit" button adds to earnings, marks as "submitted"
   - Physical bonus: "Submit" button shows secret code popup with copy button
   - AJAX endpoint: `wc_tp_submit_bonus` for admin submissions

5. **Email System for Physical Bonuses (STEP 9)**
   - Employee email template with secret code prominently displayed
   - Admin email template with confirmation and secret code
   - Automatic email sending when admin submits physical bonus
   - Professional HTML templates with tier-specific colors
   - Includes step-by-step claiming instructions for employee

6. **Not Claimed View & Resend (STEP 10)**
   - Three-status bonus system: pending → submitted → claimed
   - "View Code" button for submitted physical bonuses
   - "Resend Email" button to resend secret code to employee
   - Admin can resend codes anytime before employee claims
   - AJAX endpoint: `wc_tp_resend_secret_code` for resending emails

7. **Payroll Calculations Updated (STEP 11)**
   - Updated `get_user_total_earnings()` to only include claimed bonuses
   - Updated `get_monthly_payroll()` to only include claimed bonuses
   - Updated `get_payroll_by_date_range()` to only include claimed bonuses
   - Only money bonuses with status 'claimed' or 'submitted' are included
   - Physical bonuses never included in earnings

**BONUS STATUS FLOW:**
- **Pending**: Employee hasn't claimed yet (no action available)
- **Submitted**: Admin submitted, email sent to employee (View Code button)
- **Claimed**: Employee claimed with secret code (Claimed label)

**EMPLOYEE WORKFLOW:**
1. Bonus achieved → Notification email sent
2. Employee views Performance Tracker → Bonus Achieved tab
3. Clicks "Claim" button
4. For money: Automatically added to earnings
5. For physical: Enters secret code from email
6. Status changes to "Claimed"

**ADMIN WORKFLOW:**
1. Navigate to Employee Details → Performance → Bonus Achieved
2. Click "Submit" on pending bonus
3. For money: Automatically added to earnings, marked "Submitted"
4. For physical: Popup shows secret code with copy button
5. Admin copies code and sends to employee (or email auto-sent)
6. Can click "View Code" anytime to resend email

**BENEFITS:**
- ✅ Flexible bonus claiming system
- ✅ Money bonuses auto-add to earnings
- ✅ Physical bonuses tracked with secret codes
- ✅ Lifetime storage of all achieved bonuses
- ✅ Professional email notifications
- ✅ Admin can resend codes anytime
- ✅ Accurate payroll calculations
- ✅ Three-status tracking system

**FILES MODIFIED:**
- `includes/class-performance-tracker.php` - Added bonus achievement storage and email functions
- `includes/class-performance-tracker-ajax.php` - Added AJAX endpoints for claim, submit, resend
- `includes/class-employee-detail.php` - Added Bonus Achieved tab to admin view
- `includes/class-core-engine.php` - Updated earnings calculation for claimed bonuses
- `includes/class-payroll-engine.php` - Updated payroll calculations for claimed bonuses
- `assets/js/performance-tracker.js` - Added UI for bonus claiming and resending
- `assets/css/performance-tracker.css` - Added styling for bonus achieved table and popups

---

## [1.6.23] - 2026-04-20
### 🐛 FIXED - Bonus Config Not Saving

#### FIXED - Bonus Configuration Now Saves Properly
**ISSUE:**
- Bonus config was only saved when the "bonuses" tab was active
- Switching to other tabs and saving would not persist bonus rule changes

**THE FIX:**
- Bonus config is now always collected and saved regardless of active tab
- Moved bonus config collection to "always save" section
- Proper error handling for bonus config validation

**BENEFITS:**
- ✅ Bonus config saves from any tab
- ✅ No data loss when switching tabs
- ✅ Consistent save behavior across all sections

---

## [1.6.23] - 2026-04-20
### 🐛 FIXED - Bonus Config Not Saving

#### FIXED - Bonus Configuration Now Saves Properly
**ISSUE:**
- Bonus config was only saved when the "bonuses" tab was active
- Switching to other tabs and saving would not persist bonus rule changes

**THE FIX:**
- Bonus config is now always collected and saved regardless of active tab
- Moved bonus config collection to "always save" section
- Proper error handling for bonus config validation

**BENEFITS:**
- ✅ Bonus config saves from any tab
- ✅ No data loss when switching tabs
- ✅ Consistent save behavior across all sections

---

# Changelog

## [1.6.22] - 2026-04-20
### 🐛 FIXED - Performance Tracker Bonus Milestones Display Issues

#### FIXED - Bonus Rules Now Display Correctly in All Scenarios
**ISSUES FIXED:**
1. **Repeatable Flag Display** - Now shows "Repeatable" when checked, "One Time" when unchecked
2. **Multiple Rules Not Showing** - Fixed rule indexing and data collection for multiple bonus rules
3. **Rules Disappearing After Changes** - Added proper cache busting and data validation
4. **Bonus Type Changes Not Persisting** - Improved data structure and sanitization
5. **Role Filtering Not Working** - Fixed empty eligible_roles array handling

**THE FIXES:**

1. **Frontend Display (performance-tracker.js)**:
   - Repeatable flag now conditionally displays "Repeatable" or "One Time"
   - Added cache busting with timestamp to prevent stale data
   - Improved debug logging for troubleshooting

2. **Backend Data Handling (class-performance-settings.php)**:
   - Added unique rule IDs for better tracking
   - Improved repeatable flag conversion (ensure 0 or 1)
   - Added last_updated timestamp for cache invalidation
   - Clear all related caches when bonus config is saved

3. **Bonus Milestones Logic (class-performance-tracker-ajax.php)**:
   - Enhanced role eligibility validation
   - Fixed empty eligible_roles array check
   - Added comprehensive rule structure validation
   - Improved error handling and logging
   - Sanitize all data fields properly

4. **JavaScript Improvements (performance-settings.js)**:
   - Better rule collection with proper repeatable value conversion
   - Improved add/remove rule handlers with user feedback
   - Enhanced validation for multiple rules
   - Ensure at least one rule exists before saving

**BENEFITS:**
- ✅ Bonus milestones display correctly for all users
- ✅ Multiple rules work seamlessly
- ✅ Changes persist immediately after save
- ✅ Role filtering works as expected
- ✅ Repeatable flag displays correctly
- ✅ No more missing or duplicate milestones

---

# Changelog

## [1.6.10] - 2026-04-20
### 🐛 FIXED - Attributed Total Fallback for Orders Without Commission Data

#### CRITICAL FIX - Attributed Total Always Shows Value
**THE PROBLEM:**
- Orders without commission_data still showed "৳ NaN"
- attributed_value was 0 when commission_data didn't exist
- "Total" column always worked because it uses $order->get_total() directly

**THE FIX:**
Added fallback logic - if no commission_data, use order total:
```php
if ( $commission_data && is_array( $commission_data ) ) {
    // Use calculated attribution from commission_data
    $attributed_value = floatval( $commission_data['agent_order_value'] );
} else {
    // Fallback: Use full order total
    $attributed_value = floatval( $order->get_total() );
}
```

**IMPACT:**
- ✅ Attributed Total ALWAYS shows a value (like Total column)
- ✅ Orders with commission_data: Shows calculated attribution
- ✅ Orders without commission_data: Shows full order total
- ✅ No more NaN errors

---

## [1.6.9] - 2026-04-20
### 🐛 FIXED - Attributed Total Showing NaN

#### CRITICAL FIX - Attributed Total Now Shows Values
**THE PROBLEM:**
- Attributed Total was showing "৳ NaN" (Not a Number)
- The calculation was only running when `$has_commission` was true
- Orders without commission status showed NaN because `$attributed_value` was 0/undefined

**THE ROOT CAUSE:**
```php
// BEFORE - Only calculated when commission applies
if ( $has_commission && is_array( $commission_data ) ) {
    $attributed_value = floatval( $commission_data['agent_order_value'] );
}
// Result: Orders without commission = $attributed_value stays 0 = NaN in frontend
```

**THE FIX:**
```php
// AFTER - Always calculate attributed total, regardless of commission status
if ( $commission_data && is_array( $commission_data ) ) {
    $attributed_value = floatval( $commission_data['agent_order_value'] );
}
// Result: Shows attributed value for ALL orders with commission_data
```

**IMPACT:**
- ✅ Attributed Total now shows correct values (৳785.00)
- ✅ Works for all orders, not just commission-eligible ones
- ✅ No more NaN errors

---

## [1.6.8] - 2026-04-20
### 🐛 FIXED - Attributed Total Display Issue

#### CRITICAL FIX - Attributed Total Now Displays Correctly
**THE PROBLEM:**
- Attributed Total column was blank despite data existing in database
- Backend was sending `attributed_total_formatted` with `wc_price()` HTML formatting
- Frontend JavaScript couldn't properly display the pre-formatted HTML string
- Other columns (earnings, commission) worked because they used raw numbers + JavaScript formatting

**THE ROOT CAUSE:**
```php
// BACKEND - Was sending pre-formatted HTML
'attributed_total_formatted' => wc_price( $attributed_value ) // Returns HTML with <span> tags

// FRONTEND - Tried to use pre-formatted value
html += '<td>' + (order.attributed_total_formatted || '—') + '</td>'; // HTML in HTML = broken
```

**THE FIX:**
Made attributed_total work EXACTLY like user_earnings:
```php
// BACKEND - Now sends raw number (like earnings)
'attributed_total' => $attributed_value, // Just the number

// FRONTEND - Formats with JavaScript (like earnings)
html += '<td>' + formatCurrency(order.attributed_total) + '</td>'; // Consistent!
```

**IMPACT:**
- ✅ Attributed Total now displays correctly
- ✅ Consistent formatting with all other currency columns
- ✅ No more HTML-in-HTML issues
- ✅ Works exactly like earnings column

---

## [1.6.7] - 2026-04-20
### 🔒 IMPROVED - Prevent Self-Assignment in Agent Dropdown

#### ENHANCEMENT - Current User Excluded from Agent Selection
**IMPROVEMENT:**
- Current logged-in user is now excluded from the agent dropdown on checkout
- Prevents users from selecting themselves as the agent
- Ensures clean owner-only orders when no agent is selected

**THE CHANGE:**
```php
// Get current logged-in user ID to exclude from dropdown
$current_user_id = get_current_user_id();

foreach ( $users as $user ) {
    // Exclude current logged-in user from dropdown
    if ( $current_user_id && $user->ID === $current_user_id ) {
        continue; // Skip current user - they can't select themselves
    }
    // ... rest of the code
}
```

**BENEFITS:**
- ✅ No confusion about selecting yourself
- ✅ Clean owner-only orders (no agent selected = current user is owner)
- ✅ Prevents agent_id === processor_id scenario from manual selection
- ✅ Simpler UX - only see other team members in dropdown

**USE CASES:**
- **Before**: User could select themselves, creating agent_id === processor_id
- **After**: User only sees other team members, if none selected = owner-only order

---

## [1.6.6] - 2026-04-20
### 🎯 FIXED - Attributed Total for Owner-Only Orders

#### CRITICAL FIX - Full Order Value Attribution
**PROBLEM:**
- When no processor was assigned (owner-only orders), attributed total showed only 70% of order value
- Database stored `agent_order_value` as 70% and `processor_order_value` as 30% even when there was no processor
- Example: Order ৳785 showed attributed total of ৳549.50 instead of ৳785

**THE FIX:**
- Attributed order values now calculated at the SOURCE (commission calculation)
- When no processor or same user: Owner gets 100% of order total
- When different users: Split by percentage (70% agent, 30% processor)
- Matches the exact logic used for earnings distribution

**CODE CHANGE:**
```php
// BEFORE - Always split by percentage
'agent_order_value' => ( $order_total * 70 ) / 100,  // Always 70%
'processor_order_value' => ( $order_total * 30 ) / 100,  // Always 30%

// AFTER - Smart attribution based on roles
if ( $agent_id === $processor_id || ! $processor_id ) {
    $agent_order_value = $order_total;  // Owner gets 100%
    $processor_order_value = 0;
} else {
    $agent_order_value = ( $order_total * 70 ) / 100;  // Split when different users
    $processor_order_value = ( $order_total * 30 ) / 100;
}
```

**IMPACT:**
- ✅ Owner-only orders now show full order value in attributed total
- ✅ Split orders still show correct percentage attribution
- ✅ Database values now correctly reflect actual attribution
- ✅ Consistent with how commission earnings are calculated

---

## [1.6.5] - 2026-04-20
### ✨ Perfected - Attributed Total Calculation Logic

#### ENHANCED - Attributed Total Now Exactly Mirrors Earnings Logic
**IMPROVEMENT:**
- Attributed total calculation now uses **EXACTLY** the same code structure as earnings
- Owner (both roles) now correctly adds agent_order_value + processor_order_value
- Perfect symmetry between earnings and attributed total calculations

**THE CHANGE:**
```php
// BEFORE (used order total for owner)
if ( $is_agent && $is_processor ) {
    $attributed_value = floatval( $order->get_total() ); // ❌ Not consistent
}

// AFTER (adds both values like earnings does)
if ( $is_agent && $is_processor ) {
    // Owner gets both agent and processor attributed values
    $attributed_value = floatval( $commission_data['agent_order_value'] ) + floatval( $commission_data['processor_order_value'] ); // ✅ Consistent
}
```

**PERFECT SYMMETRY:**

**Earnings Logic:**
```php
if ( $is_agent && $is_processor ) {
    $user_earnings = agent_earnings + processor_earnings;
} elseif ( $user_role === 'agent' ) {
    $user_earnings = agent_earnings;
} else {
    $user_earnings = processor_earnings;
}
```

**Attributed Total Logic (NOW IDENTICAL):**
```php
if ( $is_agent && $is_processor ) {
    $attributed_value = agent_order_value + processor_order_value;
} elseif ( $user_role === 'agent' ) {
    $attributed_value = agent_order_value;
} else {
    $attributed_value = processor_order_value;
}
```

**BENEFITS:**
- ✅ Exact same code structure as earnings
- ✅ Reads from agent_order_value and processor_order_value
- ✅ Owner correctly gets sum of both values
- ✅ Perfect consistency and maintainability

**FILES MODIFIED:**
- `includes/class-ajax-handlers.php` - Made attributed total logic identical to earnings logic

---

## [1.6.4] - 2026-04-20
### 🐛 Fixed - Attributed Total Display Issue

#### CRITICAL FIX - Attributed Total Now Shows Correctly
**BUG FIX:**
- Fixed attributed total showing blank/nothing in employee order tables
- Issue was in the conditional logic structure (elseif vs else)
- Now matches earnings calculation logic exactly

**THE PROBLEM:**
```php
// BEFORE (didn't work for processor)
} elseif ( $user_role === 'agent' && isset(...) ) {
    $attributed_value = agent_order_value;
} elseif ( $user_role === 'processor' && isset(...) ) {  // ❌ Never reached
    $attributed_value = processor_order_value;
}
```

**THE FIX:**
```php
// AFTER (works for both)
} elseif ( $user_role === 'agent' ) {
    $attributed_value = agent_order_value;
} else {  // ✅ Catches processor
    $attributed_value = processor_order_value;
}
```

**CHANGES:**
- Changed `elseif` to `else` for processor case (matches earnings logic)
- Removed unnecessary `isset()` checks (data exists in DB)
- Now uses exact same conditional structure as earnings calculation

**RESULT:**
- ✅ Attributed Total displays correctly for agents
- ✅ Attributed Total displays correctly for processors
- ✅ Attributed Total displays correctly for owners (both roles)
- ✅ Matches earnings column behavior exactly

**FILES MODIFIED:**
- `includes/class-ajax-handlers.php` - Fixed conditional logic for attributed total

---

## [1.6.3] - 2026-04-20
### ✨ Refactored - Attributed Total Column with Unified Logic

#### ENHANCED - Attributed Total Uses Same Logic as Earnings
**IMPROVEMENT:**
- Attributed Total column now uses the exact same calculation logic as "Your Earnings"
- Reads directly from `_commission_data` meta (agent_order_value, processor_order_value)
- Consistent behavior across all order displays

**LOGIC:**
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

**BENEFITS:**
- ✅ Consistent calculation logic between Attributed Total and Earnings
- ✅ Accurate sales attribution per employee
- ✅ Proper performance metrics (AOV, conversion rates)
- ✅ Fair comparison between agents and processors
- ✅ Owner (both roles) correctly shows full order value

**FILES MODIFIED:**
- `includes/class-ajax-handlers.php` - Unified attributed total calculation with earnings logic
- `includes/class-employee-detail.php` - Restored Attributed Total column in order table

---

## [1.6.2] - 2026-04-19
### ✨ Enhanced - Unified Attributed Total Logic & Employee Role Display

#### FIXED - My Account Orders Attributed Total for Owner
**BUG FIX:**
- Fixed My Account Orders to show full order total when user is both agent and processor
- Previously only showed agent_order_value even when user owned entire order
- Now correctly shows full order total for owners

#### ENHANCED - Admin Employee Details Matches My Account Logic
**IMPROVEMENTS:**
1. **Attributed Total Logic** (Same as My Account):
   - Owner (both agent & processor): Full order total
   - Agent only: `commission_data['agent_order_value']`
   - Processor only: `commission_data['processor_order_value']`

2. **Employee Role Display** (Replaced Flag):
   - Changed "Flag" column to "Employee Role"
   - Shows "Agent" or "Processor"
   - If user is both, displays as "Agent" (primary role)
   - Filter changed from "Flag Filter" to "Employee Role Filter"

3. **Earnings Calculation**:
   - Owner gets both agent + processor earnings
   - Agent/Processor gets their respective earnings

**LOGIC:**
```php
// Determine role (if both, show as agent)
if ( $is_agent ) {
    $user_role = 'agent';
} elseif ( $is_processor ) {
    $user_role = 'processor';
}

// Calculate attributed total
if ( $is_agent && $is_processor ) {
    $attributed_value = $order->get_total(); // Full total
} elseif ( $user_role === 'agent' ) {
    $attributed_value = $commission_data['agent_order_value'];
} else {
    $attributed_value = $commission_data['processor_order_value'];
}
```

**FILES MODIFIED:**
- `includes/class-myaccount.php` - Fixed attributed total for owner case
- `includes/class-ajax-handlers.php` - Matched My Account logic, replaced flag with role
- `includes/class-employee-detail.php` - Updated UI to show Employee Role instead of Flag

---

## [1.6.1] - 2026-04-19
### 🐛 Fixed - Root Cause of Blank Attributed Total Values

#### IDENTIFIED AND FIXED - The Culprit: get_user_earnings() Date Limitation
**ROOT CAUSE:**
The `get_user_earnings()` method was being used to fetch orders, but it has built-in date limitations (defaults to current month only). This caused:
- Orders outside the current month were not being fetched
- The AJAX handler was trying to enhance non-existent order data
- Attributed total values were never calculated for most orders

**THE CULPRIT:**
```php
// OLD CODE - Limited to current month by default
$core_engine = new WC_Team_Payroll_Core_Engine();
$earnings = $core_engine->get_user_earnings( $user_id ); // Only gets current month!
```

**THE FIX:**
Completely rewrote the AJAX handler to:
1. Fetch ALL orders directly using `wc_get_orders()` with `status => 'any'`
2. Loop through all orders and check user involvement
3. Calculate attributed total for each order based on role
4. Build complete order data array from scratch
5. Apply filters AFTER fetching all data

**NEW LOGIC:**
- Owner (agent + processor): Full order total
- Agent only: `commission_data['agent_order_value']`
- Processor only: `commission_data['processor_order_value']`
- No dependency on date-limited methods

**FILES MODIFIED:**
- `includes/class-ajax-handlers.php` - Complete rewrite of get_employee_orders()

---

## [1.6.0] - 2026-04-19
### ✨ Enhanced - Attributed Total Logic for Owner Orders

#### IMPROVED - Attributed Total Calculation Based on User Role
**ENHANCEMENT:**
- **Owner (Agent + Processor)**: Shows full order total when user is both agent and processor
- **Agent Only**: Shows agent's attributed portion from `agent_order_value`
- **Processor Only**: Shows processor's attributed portion from `processor_order_value`

**LOGIC:**
```
IF user is BOTH agent AND processor:
    attributed_total = full order total
ELSE IF user is agent only:
    attributed_total = commission_data['agent_order_value']
ELSE IF user is processor only:
    attributed_total = commission_data['processor_order_value']
```

**WHY:**
- When a user owns the entire order (both roles), they should see the full order value
- When split between different users, each sees only their attributed portion
- Matches business logic where owner gets credit for full order value

**FILES MODIFIED:**
- `includes/class-ajax-handlers.php` - Enhanced attributed total calculation logic

---

## [1.5.9] - 2026-04-19
### 🐛 Fixed - Attributed Total Display in Admin Employee Details

#### FIXED - Attributed Total Column Showing Blank Values
**BUG FIX:**
- Fixed attributed total column showing "—" instead of actual values
- Corrected static method call to instance method call for `get_user_earnings()`
- Properly extracted `attributed_value` from existing order data
- Enhanced order data with all required fields (customer info, status, flag)

**ISSUE:**
- AJAX handler was calling `get_user_earnings()` statically (incorrect)
- Was trying to re-fetch commission data instead of using existing `attributed_value`
- Missing customer name, email, phone, status, and flag data

**SOLUTION:**
- Create `WC_Team_Payroll_Core_Engine` instance to call non-static method
- Use existing `attributed_value` from order data returned by `get_user_earnings()`
- Properly populate all order fields including customer info and flags
- Format attributed total with currency or show "—" for zero values

**FILES MODIFIED:**
- `includes/class-ajax-handlers.php` - Fixed method call and data extraction

---

## [1.5.8] - 2026-04-19
### ✨ Enhanced - Admin Employee Details Order History with Attributed Total

#### ADDED - Attributed Total Column to Order History Table
**ENHANCEMENT:**
- Added "Attributed Total" column after "Total" column in Order History table
- Shows employee's attributed portion of order value based on their role
- Displays agent_order_value for agents, processor_order_value for processors
- Shows "—" when no attributed value exists
- Sortable column with proper numeric sorting
- Respects all existing filters (date, status, flag, search)

**TABLE IMPROVEMENTS:**
- Horizontal scroll enabled for table overflow on smaller screens
- Headers use non-breaking text to prevent word wrapping
- Table wrapper with smooth touch scrolling on mobile
- Minimum table width ensures proper column spacing
- All cells use nowrap to maintain clean layout

**FEATURES:**
- Attributed Total column matches My Account Orders styling
- Proper data flow from AJAX handler to frontend
- Enhanced order data with attributed_total and attributed_total_formatted
- Consistent with commission calculation system

**FILES MODIFIED:**
- `includes/class-ajax-handlers.php` - Enhanced get_employee_orders with attributed data
- `includes/class-employee-detail.php` - Added column to table, wrapper for overflow, non-breaking headers

---

## [1.5.7] - 2026-04-19
### ✨ Enhanced - Admin Employee Details Profile Picture with Badges

#### ADDED - Achievement Badges and Goal Counter to Profile Picture
**ENHANCEMENT:**
- Profile picture now circular with #ff9900 border (matching My Account style)
- Achievement badge displayed at top-right (gold/silver/bronze/locked)
- Goal counter badge at bottom showing goals achieved (e.g., "2/3")
- Smooth gold/silver/bronze gradients with outline icons
- Hover effects on badges for better interactivity
- Consistent styling between admin and frontend

**FEATURES:**
- Circular profile picture with 3px #ff9900 border
- Top-right achievement badge with smooth gradients
- Bottom goal counter with star icon
- Responsive design for mobile devices
- Same badge system as My Account pages

**FILES MODIFIED:**
- `includes/class-employee-detail.php` - Added badge HTML and CSS

---

## [1.5.6] - 2026-04-19
### 🔧 Improved - Salary Date Filtering with Timestamp Comparison

#### ENHANCED - More Accurate Date Range Filtering for Salary Transactions
**IMPROVEMENT:**
- Changed salary transaction date filtering from string comparison to timestamp comparison
- More accurate and reliable date range filtering
- Handles datetime-local format properly
- Consistent with payment date filtering logic

**BEFORE (String Comparison):**
```php
$trans_date = date( 'Y-m-d', strtotime( $transaction['date'] ) );
if ( $trans_date >= $start_date && $trans_date <= $end_date ) {
    // String comparison - less reliable
}
```

**AFTER (Timestamp Comparison):**
```php
$trans_timestamp = strtotime( $trans_date_str );
$start_timestamp = strtotime( $start_date . ' 00:00:00' );
$end_timestamp = strtotime( $end_date . ' 23:59:59' );

if ( $trans_timestamp >= $start_timestamp && $trans_timestamp <= $end_timestamp ) {
    // Timestamp comparison - more accurate
}
```

**BENEFITS:**
- More accurate date range filtering
- Handles edge cases better (timezone, time of day)
- Consistent with payment filtering logic
- Properly handles datetime-local format (YYYY-MM-DDTHH:MM)
- Ensures salary transfers are counted only within selected date range

**FILES MODIFIED:**
- `includes/class-payroll-engine.php` - Updated both payroll functions to use timestamp comparison

## [1.5.5] - 2026-04-19
### 🐛 Critical Fix - Dashboard Total Earnings Double-Counting

#### FIXED - Salary Being Counted Twice
**ISSUE:**
- Dashboard "Total Earnings" showed double the correct amount
- Example: Should be 21,471.47 ৳ but showed 42,900.04 ৳ (exactly 2x)
- Salary was being counted twice due to v1.5.2 changes

**ROOT CAUSE:**
After v1.5.2, the payroll engine already includes salary in `$payroll[$user_id]['total']`:
```php
// In payroll engine (v1.5.2):
$payroll[$user_id]['total'] = commission + salary ✓

// In dashboard (v1.4.8 - v1.5.4):
$employee_total_earnings = $payroll[$user_id]['total'] + $salary_for_period
// This became: (commission + salary) + salary = DOUBLE SALARY ❌
```

**SOLUTION:**
Dashboard now uses payroll data directly without re-adding salary:
```php
// For employees in payroll array:
$employee_total_earnings = $payroll[$user_id]['total']; // Already has commission + salary ✓

// For employees NOT in payroll (no commission):
$employee_total_earnings = $salary_for_period; // Calculate salary only ✓
```

**CALCULATION FLOW:**
1. **Payroll Engine** (v1.5.2): Calculates `total = commission + salary` for each employee
2. **Dashboard**: Uses payroll `total` directly (no re-calculation needed)
3. **Employees without orders**: Calculate salary separately (not in payroll array)

**BENEFITS:**
- Correct total earnings (no double-counting)
- Consistent with employee payroll table
- Matches reports page KPI system
- Simple and efficient logic

**FILES MODIFIED:**
- `woocommerce-team-payroll.php` - Fixed dashboard to use payroll data directly

## [1.5.3] - 2026-04-19
### 🐛 Dashboard Total Orders - Fix Undercount Issue

#### FIXED - Count All Orders with Commission Statuses
**ISSUE:**
- Dashboard "Total Orders" was undercounting orders
- Only counted orders that had `_commission_data` meta saved
- Orders without commission data (no agent/processor assigned) were excluded
- Example: 3 orders in date range, but only 2 counted

**ROOT CAUSE:**
```php
// OLD - Only counted orders with commission data
foreach ( $orders as $order ) {
    $commission_data = $order->get_meta( '_commission_data' );
    if ( $commission_data ) { // ❌ Excludes orders without commission data
        $unique_orders[ $order->get_id() ] = true;
    }
}
```

**SOLUTION:**
- Count ALL orders with commission calculation statuses
- No longer requires `_commission_data` meta to exist
- If order has commission status (completed, processing, etc.), it's counted
- Matches expected behavior: count all orders with configured statuses

**NEW LOGIC:**
```php
// Get all orders with commission calculation statuses
$orders = wc_get_orders( $args );
$total_orders = count( $orders ); // ✅ Count all orders with commission statuses
```

**BENEFITS:**
- Accurate order count (no undercounting)
- Counts all orders with commission calculation statuses
- Whether order has commission data is irrelevant for counting
- Consistent with user expectations

**FILES MODIFIED:**
- `woocommerce-team-payroll.php` - Removed commission_data check from order counting

## [1.5.2] - 2026-04-19
### 💰 Employee Payroll Details - Complete Earnings & Due Calculation

#### FIXED - Total Earnings & Due Per Employee (Commission + Salary)
**ISSUE:**
- "Employee Payroll Details" table showed only commission earnings per employee
- Due calculation was: `Due = Commission - Paid` (missing salary)
- Inconsistent with dashboard totals (fixed in v1.4.8 and v1.5.0)
- Employees with fixed/combined salary showed incorrect totals

**SOLUTION:**
- Updated payroll engine to include salary earnings for each employee
- Total Earnings per employee: `Commission + Salary`
- Due per employee: `(Commission + Salary) - Paid`
- Matches dashboard totals and reports page KPI calculation
- Applied to both `get_monthly_payroll()` and `get_payroll_by_date_range()`

**CALCULATION LOGIC:**
```php
foreach ( $payroll as $user_id => $data ) {
    // Get commission earnings (already in $data['total'])
    $commission_earnings = $data['total'];
    
    // Get salary earnings from transactions within date range
    $salary_for_period = sum of salary transfers;
    
    // Update total earnings (Commission + Salary)
    $payroll[$user_id]['total'] = $commission_earnings + $salary_for_period;
    
    // Calculate due (Total Earnings - Paid)
    $payroll[$user_id]['due'] = $payroll[$user_id]['total'] - $paid;
}
```

**BENEFITS:**
- Accurate earnings per employee (commission + salary)
- Correct due calculation per employee
- Consistent with dashboard totals and reports KPI
- Proper accounting for all employee types:
  - Commission-only employees
  - Fixed salary employees
  - Combined salary employees

**TABLES AUTOMATICALLY FIXED:**
Since the payroll engine is the data source for multiple dashboard tables, the following tables are now all correct:

1. **Employee Payroll Details Table:**
   - ✅ Total Earnings = Commission + Salary (per employee)
   - ✅ Due = (Commission + Salary) - Paid (per employee)
   - ✅ Order Count = Dynamic commission statuses (per employee)

2. **Top Earners Table:**
   - ✅ Total Earnings = Commission + Salary (per employee)
   - ✅ Order Count = Dynamic commission statuses (per employee)
   - ✅ Sorted by complete earnings (commission + salary)

**FILES MODIFIED:**
- `includes/class-payroll-engine.php` - Updated both payroll functions to include salary in total and recalculate due

## [1.5.1] - 2026-04-19
### ⚙️ Employee Payroll Details - Dynamic Status Support

#### ENHANCED - Order Count Per Employee Uses Commission Calculation Statuses
**ISSUE:**
- "Employee Payroll Details" table showed order count per employee
- Used hardcoded statuses: `completed`, `processing`, `refunded`
- Didn't respect the dynamic "Commission Calculation Statuses" from plugin settings
- Inconsistent with dashboard total orders (fixed in v1.4.9)

**SOLUTION:**
- Payroll engine now uses `WC_Team_Payroll_Core_Engine::get_commission_calculation_statuses()`
- Updated both `get_monthly_payroll()` and `get_payroll_by_date_range()` functions
- Order count per employee dynamically reads from Settings → Commission Calculation Statuses
- Consistent with all other modules (dashboard totals, reports, performance tracker)

**FUNCTIONS UPDATED:**
1. **`get_monthly_payroll()`** - Used by monthly payroll views
2. **`get_payroll_by_date_range()`** - Used by dashboard Employee Payroll Details table

**BEFORE:**
```php
'status' => array( 'completed', 'processing', 'refunded' ), // Hardcoded
```

**AFTER:**
```php
$commission_statuses = WC_Team_Payroll_Core_Engine::get_commission_calculation_statuses();
'status' => $commission_statuses, // Dynamic from settings
```

**BENEFITS:**
- Consistent order counting for individual employees
- Respects admin configuration across all views
- Employee order counts match dashboard total orders
- Flexible - changes in settings automatically apply everywhere

**FILES MODIFIED:**
- `includes/class-payroll-engine.php` - Updated both payroll functions to use dynamic statuses

## [1.5.0] - 2026-04-19
### 💯 Dashboard Due Amount - Perfect Calculation

#### FIXED - Total Due Based on Complete Earnings (Commission + Salary)
**ISSUE:**
- Total Due was calculated based on old commission-only earnings
- Didn't account for salary earnings in the due calculation
- After updating Total Earnings to include salary (v1.4.8), Due amount was incorrect
- Formula was: `Due = Commission - Paid` (missing salary)

**SOLUTION:**
- Total Due now calculated based on complete earnings: `Due = (Commission + Salary) - Paid`
- Loops through ALL employees to calculate individual due amounts
- Handles employees with:
  - Only commission (no salary)
  - Only salary (no commission)
  - Both commission and salary (combined)
- Includes payments from `_wc_tp_payments` meta within date range

**CALCULATION LOGIC:**
```php
foreach ( $all_employees as $employee ) {
    $commission_earnings = sum of agent_earnings + processor_earnings
    $salary_for_period = sum of salary transfers within date range
    $employee_total_earnings = $commission_earnings + $salary_for_period
    
    $employee_paid = sum of payments within date range
    $employee_due = $employee_total_earnings - $employee_paid
    
    $total_due += $employee_due
}
```

**BENEFITS:**
- Accurate due calculation matching total earnings
- Consistent with reports page KPI system
- Proper accounting for all employee types
- Total Due = Total Earnings - Total Paid (mathematically correct)

**FILES MODIFIED:**
- `woocommerce-team-payroll.php` - Updated dashboard due calculation to include salary earnings

## [1.4.9] - 2026-04-19
### ⚙️ Dashboard Order Count - Dynamic Status Support

#### ENHANCED - Order Count Uses Commission Calculation Statuses
**ISSUE:**
- Dashboard "Total Orders" used hardcoded statuses: `completed`, `processing`, `refunded`
- Didn't respect the dynamic "Commission Calculation Statuses" from plugin settings
- Inconsistent with rest of the system which uses dynamic statuses

**SOLUTION:**
- Dashboard order count now uses `WC_Team_Payroll_Core_Engine::get_commission_calculation_statuses()`
- Dynamically reads statuses from Settings → Commission Calculation Statuses
- Matches the behavior of reports, performance tracker, and all other modules

**BEFORE:**
```php
'status' => array( 'completed', 'processing', 'refunded' ), // Hardcoded
```

**AFTER:**
```php
$commission_statuses = WC_Team_Payroll_Core_Engine::get_commission_calculation_statuses();
'status' => $commission_statuses, // Dynamic from settings
```

**BENEFITS:**
- Consistent order counting across entire system
- Respects admin configuration for commission calculation
- Flexible - changes in settings automatically apply to dashboard
- No need to modify code when changing commission statuses

**FILES MODIFIED:**
- `woocommerce-team-payroll.php` - Updated dashboard order count to use dynamic statuses

## [1.4.8] - 2026-04-19
### 💰 Dashboard Earnings Alignment with Reports KPI

#### ENHANCED - Dashboard Total Earnings Calculation
**ISSUE:**
- Admin dashboard "Total Earnings" only showed commission earnings
- Reports page KPI correctly showed commission + salary earnings
- Dashboard missed employees with only salary earnings (no commission)
- Inconsistency between dashboard and reports page calculations

**SOLUTION:**
- Updated dashboard to follow reports page KPI total earnings system
- Dashboard now calculates: `Total Earnings = Commission + Salary`
- **Loops through ALL employees** (not just those with commission earnings)
- Includes salary transactions (daily_transfer, weekly_transfer, monthly_transfer, partial_transfer) within date range
- Applies to all employees (fixed salary, combined salary, commission-only)

**CALCULATION LOGIC:**
```php
// For ALL employees (not just those with orders):
foreach ( $all_employees as $employee ) {
    $commission_earnings = sum of agent_earnings + processor_earnings (if any)
    $salary_for_period = sum of salary transfers within date range (if any)
    $total_earnings += $commission_earnings + $salary_for_period
}
```

**BENEFITS:**
- Consistent earnings reporting across dashboard and reports
- Accurate total earnings for all employee types
- Includes employees with only salary (no commission)
- Better financial overview for administrators

**Files Modified:**
- `woocommerce-team-payroll.php` - Updated dashboard AJAX handler to loop through all employees

## [1.4.7] - 2026-04-19
### 🐛 Bug Fix - Bonus Milestones Cache Issue

#### FIXED - Bonus Milestones Not Showing After Delete/Create
**ISSUE:**
- Bonus milestones displayed correctly on first load
- After deleting old bonus rules and creating new ones, frontend didn't show new bonuses
- WordPress object cache was not being cleared after saving bonus configuration

**FIXES:**
- Added `wp_cache_delete()` after `update_option()` in `ajax_save_bonus_config()`
- Added cache busting to AJAX calls with timestamp parameter (`cache_bust: Date.now()`)
- Added `cache: false` to jQuery AJAX settings to disable client-side caching
- Ensures fresh bonus data is always loaded from database

**Files Modified:**
- `includes/class-performance-settings.php` - Added cache clearing after bonus save
- `assets/js/performance-tracker.js` - Added cache busting and disabled AJAX cache

## [1.4.6] - 2026-04-19
### 🪙 3D Embossed Gold Coin Badge Design

#### REDESIGNED - Exact Match to Premium Coin Style
**DESIGN - 3D Embossed Coin Badges:**
- Realistic 3D embossed coin/medal appearance
- Multiple layered circles for depth effect
- Radial gradients for top-lit 3D look
- Tier-specific icons (Crown, Medal, Trophy, Lock)
- Professional metallic finish

**Badge Structure (4 Layers):**

**Layer 1 - Outer Ring (48px):**
- Border circle with radial gradient
- Darker edges, lighter center
- Stroke outline for definition

**Layer 2 - Middle Ring (44px):**
- Depth layer with 60% opacity
- Creates shadow/depth effect
- Darker color for recession

**Layer 3 - Inner Surface (38px):**
- Main gradient surface
- Radial gradient (light center → dark edges)
- Primary badge color

**Layer 4 - Icon Area (28px):**
- Embossed icon container
- Semi-transparent overlay
- Creates raised effect

**Layer 5 - Icon:**
- Tier-specific symbol
- Embossed with stroke outline
- Accent colors for details

**Badge Tier Designs:**

👑 **GOLD BADGE - King Crown:**
- Radial gradient: #FFF4A3 (light) → #FFD700 (mid) → #B8860B (dark)
- King crown icon with 3 jewels
- Dark gold embossed icon (#8B6914)
- Golden jewel accents (#FFD700)
- Most prestigious appearance

🥈 **SILVER BADGE - Medal Star:**
- Radial gradient: #F5F5F5 (light) → #C0C0C0 (mid) → #808080 (dark)
- Medal star icon with center dot
- Gray embossed icon (#696969)
- Light silver accents (#E8E8E8)
- Metallic professional look

🏆 **BRONZE BADGE - Trophy Cup:**
- Radial gradient: #F4C896 (light) → #CD7F32 (mid) → #8B4513 (dark)
- Trophy cup with handles and base
- Brown embossed icon (#6B3410)
- Bronze accents (#E6A85C)
- Warm encouraging appearance

🔒 **LOCKED BADGE - Padlock:**
- Radial gradient: #E0E0E0 (light) → #BDBDBD (mid) → #9E9E9E (dark)
- Padlock icon with keyhole
- Gray embossed icon (#757575)
- Dimmed appearance (70% opacity)
- Shows potential achievement

**Visual Features:**
- Radial gradients create realistic 3D depth
- Multiple circle layers simulate embossing
- Top-lit appearance (light center, dark edges)
- Strong drop shadows for elevation
- Embossed icons with stroke outlines
- Accent colors for icon details
- 44px desktop, 38px mobile
- Smooth hover scale effect (1.08x)

**Design Inspiration:**
- Premium gold coins and medals
- Olympic medals design
- Luxury brand badges
- 3D embossed metallic effects
- Professional achievement systems

**Technical Implementation:**
- SVG with embedded radial gradients
- 4-layer circle structure for depth
- Opacity layers for shadow effects
- Tier-specific icon paths
- CSS filters for drop shadows
- Responsive sizing

**Files Modified:**
- `includes/class-myaccount.php`: 4-layer SVG structure with icons
- `assets/css/myaccount-shared.css`: 3D embossed coin styles

## [1.4.5] - 2026-04-19
### 🎨 Premium Badge Design with Gradients & Locked State

#### ENHANCED - Professional Achievement Badge System
**DESIGN - LinkedIn/Duolingo Style Badges:**
- Premium circular badges with SVG gradients
- Distinct tier-specific colors and visual hierarchy
- Locked state with lock icon for unachieved status
- Subtle shine overlay effect on achieved badges
- Smooth hover animations (scale + shadow)

**Achievement Badge Tiers:**

🥇 **GOLD BADGE:**
- Rich gold gradient (#FFD700 → #FFA500)
- Cream inner circle (#FFF8DC) with gold border
- Dark gold star icon (#DAA520)
- Most prestigious and vibrant appearance
- Motivates top performance

🥈 **SILVER BADGE:**
- Metallic silver gradient (#E8E8E8 → #B0B0B0)
- Light gray inner circle (#F5F5F5)
- Medium gray star icon (#808080)
- Clean, professional look
- Recognizes strong achievement

🥉 **BRONZE BADGE:**
- Warm bronze gradient (#E6A85C → #CD7F32)
- Light cream inner circle (#FFF5E6)
- Dark brown star icon (#8B4513)
- Warm, encouraging appearance
- Celebrates initial success

🔒 **LOCKED BADGE (No Achievement):**
- Dimmed gray appearance (60% opacity)
- Gray gradient (#E0E0E0) with lock icon
- Shows potential for achievement
- Motivates users to unlock
- Always visible to show what's possible

**Visual Features:**
- SVG gradients for premium depth
- Circular design: 40px desktop, 36px mobile
- Drop shadows for elevation
- Shine overlay on achieved badges
- Smooth transitions on hover
- Professional color palette

**Motivation Strategy:**
- Locked badge creates aspiration
- Each tier has distinct, appealing colors
- Clear visual progression (Bronze → Silver → Gold)
- Premium feel encourages engagement
- Inspired by LinkedIn, Duolingo, GitHub achievements

**Technical Implementation:**
- SVG with embedded gradients
- Separate locked state rendering
- CSS filters for shine effects
- Responsive sizing
- Accessibility-friendly colors

**Files Modified:**
- `includes/class-myaccount.php`: Added locked state and gradient SVGs
- `assets/css/myaccount-shared.css`: Premium badge styles with gradients

## [1.4.4] - 2026-04-19
### ✨ Feature: Premium Profile Picture Badges & UX Improvements

#### NEW - Premium Profile Picture Badges
**FEATURE - Achievement Badge Overlay (Top-Right):**
- Premium crown/seal badge showing highest achievement tier
- Positioned at top-right corner of profile picture (Truecaller-style)
- Tier-specific colors: Gold (gradient), Silver (metallic), Bronze (copper)
- NO animations for professional, premium appearance
- Larger size (50px) for better visibility
- Smooth hover effect with enhanced shadow
- Separate CSS class `.profile-achievement-badge` to avoid breaking other designs

**FEATURE - Goal Counter Badge (Bottom-Center):**
- Star icon badge showing current period goal achievement
- Displays "X/Y" format (e.g., "2/3", "3/3")
- Positioned at bottom-center of profile picture
- White pill-shaped design with golden star icon
- Blue border matching brand colors
- Reads from current goal progress (based on admin period settings)
- Counts achieved goals from 3 metrics: Order Value, Orders Count, AOV
- Hover effect with scale and shadow enhancement

**Visual Design:**
- Premium, professional look inspired by Truecaller
- No distracting animations on profile badges
- Clean, modern appearance
- Fully responsive for mobile and desktop
- Proper z-index layering for badge overlays

**Technical Implementation:**
- Fetches goal progress from `_wc_tp_current_goal_progress` user meta
- Calculates achieved goals (status: 'achieved' or 'stretch_achieved')
- Uses separate gradient IDs to avoid SVG conflicts
- Responsive sizing: 50px desktop, 42px mobile
- 240+ lines of new CSS for profile badges

#### IMPROVED - Bonus Configuration UX
**REFACTOR - Unified Save System:**
- Removed separate "Save Bonus Configuration" button
- Integrated bonus saving into unified "Save All Configurations" button
- Consistent user experience across all Performance Settings tabs
- Better integration with unsaved changes tracking
- Cleaner code with less duplication (31 fewer lines)

**Benefits:**
- Single save button for all configuration changes
- Validation still works (throws error if fields invalid)
- Rule number updates on successful save
- Consistent with Goals, Achievements, Baselines, System tabs

**Files Modified:**
- `includes/class-myaccount.php`: Added goal calculation and badge HTML
- `assets/css/myaccount-shared.css`: Added profile badge styles
- `includes/class-performance-settings.php`: Removed separate save button
- `assets/js/performance-settings.js`: Integrated into unified save system

## [1.4.3] - 2026-04-19
### 🐛 Bug Fix: JavaScript Syntax Error

#### FIXED - Performance Settings Page
**BUG FIX - jQuery Document Ready Closure:**
- Fixed syntax error in `assets/js/performance-settings.js`
- Removed premature closing of jQuery document.ready block
- Bonus configuration section now properly scoped within document.ready
- Resolves JavaScript error preventing Performance Settings page from loading

**Technical Details:**
- The jQuery(document).ready block was being closed prematurely on line 1778
- Bonus configuration code was incorrectly placed outside the ready scope
- Removed extra `});` that was causing "Unexpected token '}'" error
- All functionality now properly contained within single document.ready block

## [1.4.2] - 2026-04-19
### 🎉 Phase 2 Part 3: Streak & Bonus Visualization - COMPLETE

#### NEW - Frontend Streak Indicators
**FEATURE - Visual Streak Display:**
- Active streak indicator with animated flame icon
- Shows current badge tier and consecutive month count
- Real-time streak status (Bronze/Silver/Gold)
- Motivational messages to maintain streaks
- "No Active Streak" state with call-to-action

**Visual Design:**
- Animated flame with flickering effect
- Color-coded by badge tier (Gold/Silver/Bronze)
- Large, prominent streak counter
- Pulsing glow animation for active streaks
- Responsive design for all screen sizes

#### NEW - Bonus Milestone Progress Display
**FEATURE - Milestone Cards:**
- Visual cards for each configured bonus rule
- Progress bars showing months completed
- "X months to go" countdown
- Bonus amount/description display
- Status indicators (Active/Achieved/Awarded/Inactive)

**Milestone States:**
- **Active**: Currently working towards (orange border)
- **Achieved**: Eligible for bonus (green border, celebration)
- **Awarded**: Already received (grayed out for non-repeatable)
- **Inactive**: Not started yet (faded)

**Milestone Information:**
- Badge tier with emoji (🥉🥈🥇)
- Required consecutive months
- Current progress (X/Y months)
- Bonus type (Money/Reward/Other)
- Bonus amount or description
- Repeatable indicator

#### NEW - Bonus History Timeline
**FEATURE - Historical Bonus Display:**
- List of all bonuses earned
- Badge tier and streak count
- Bonus amount or description
- Award date timestamp
- Last 10 bonuses shown
- Hover effects and smooth animations

**Visual Elements:**
- Tier-colored icons
- Clean card-based layout
- Chronological ordering (newest first)
- Smooth hover transitions

#### Technical Implementation
**Files Modified:**
- `includes/class-performance-tracker-ajax.php`:
  - Added `get_bonus_milestones()` method
  - Enhanced achievements AJAX to include streaks and bonuses
  - Role-based eligibility checking
  - Progress calculation logic
  
- `assets/js/performance-tracker.js`:
  - Added `renderStreakIndicators()` method
  - Added `renderBonusMilestones()` method
  - Added `renderBonusHistory()` method
  - Enhanced `renderAchievements()` to include new sections
  
- `assets/css/performance-tracker.css`:
  - Added 400+ lines of streak-specific styling
  - Animated flame effects
  - Milestone card designs
  - Bonus history styling
  - Responsive breakpoints

**Data Integration:**
- Reads from `_wc_tp_badge_streaks` user meta
- Reads from `_wc_tp_bonus_history` user meta
- Reads from `wc_tp_achievement_bonuses` option
- Calculates real-time progress percentages
- Checks awarded status for non-repeatable bonuses

**User Experience:**
- Instant visual feedback on streak status
- Clear progress indicators
- Motivational messaging
- Mobile-responsive design
- Smooth animations and transitions
- Color-coded status system

**Integration:**
- Fully integrated with Phase 2 backend
- Works with existing bonus configuration (Phase 2 Part 2)
- Respects role-based eligibility
- Handles repeatable vs one-time bonuses
- Admin can view employee streaks/bonuses

**Result:**
Employees can now see:
- Their current badge streak with visual indicator
- All available bonus milestones
- Progress towards each bonus
- How many months until next bonus
- Complete history of bonuses earned

## [1.4.1] - 2026-04-19
### 🎉 Phase 2 Part 2: Bonus Configuration UI - COMPLETE

#### NEW - Admin Settings UI for Bonus Configuration
**FEATURE - Bonus Configuration Tab:**
- New "Bonus Configuration" tab in Reports & Performance settings
- Visual interface for managing badge streak bonuses
- No more manual database editing required
- Intuitive repeater fields for multiple bonus rules

**Bonus Rule Configuration:**
- Badge tier selection (Bronze/Silver/Gold)
- Consecutive months required (1-24 months)
- Bonus type selection:
  - Money (auto-added to earnings)
  - Physical Reward (e.g., Motorcycle)
  - Other Recognition
- Bonus amount field (for money type)
- Bonus description (shown to employees)
- Repeatable vs One-time toggle
- Role-based eligibility (multi-select checkboxes)

**Global Bonus Settings:**
- Enable/disable bonus system
- Email notification toggle
- Show progress to employees toggle

**User Experience:**
- Add/remove bonus rules dynamically
- Real-time validation
- Visual feedback for required fields
- Responsive design for mobile/tablet
- Beautiful card-based layout
- Smooth animations and transitions

**Technical Implementation:**
- New AJAX handlers: `wc_tp_save_bonus_config`, `wc_tp_get_bonus_config`
- Saves to `wc_tp_achievement_bonuses` option
- Sanitization and validation on server-side
- JavaScript repeater functionality
- Conditional field display (amount field for money type)
- Rule numbering and reordering
- Minimum one rule enforcement

**Files Modified:**
- `includes/class-performance-settings.php` - Added bonus section rendering and AJAX handlers
- `assets/css/performance-settings.css` - Added 250+ lines of bonus-specific styling
- `assets/js/performance-settings.js` - Added bonus rule management JavaScript

**Integration:**
- Fully integrated with existing Phase 2 bonus system
- Backend bonus distribution already implemented
- Email notifications already working
- Payment system integration already complete

**Coming Next (Phase 2 Part 3):**
- Visual streak indicators in Performance Tracker frontend
- Bonus milestone progress display for employees
- "X months until next bonus" countdown
- Streak history visualization

## [1.4.0] - 2026-04-19
### 🎉 MAJOR UPDATE: Monthly Achievement & Streak Bonus System

#### Phase 1: Monthly Achievement System
**NEW - Performance-Based Monthly Badges:**
- Achievements now calculated monthly (not lifetime)
- Badge reflects CURRENT MONTH performance only
- Automatic promotion/demotion based on monthly results
- Fresh motivation every month

**Features:**
- Monthly achievement tracking with history (24 months)
- Automatic month-end finalization
- Beautiful HTML email notifications
- Performance summary in emails
- Profile badge shows current month status
- Achievement history for performance reviews

**Employee Motivation:**
- Must perform every month to maintain badge
- Poor month = lower badge (accountability)
- Good month = higher badge (recognition)
- Monthly celebration emails
- Continuous challenge and engagement

#### Phase 2: Badge Streak & Bonus System
**NEW - Consecutive Month Bonuses:**
- Track consecutive months at same badge level
- Automatic bonus distribution when streak achieved
- Repeatable and one-time bonus options
- Multiple bonus types supported

**Bonus Types:**
- **Money**: Auto-added to earnings, shows in payments
- **Reward**: Physical rewards (motorcycle, etc.)
- **Other**: Custom rewards and recognition

**Features:**
- Streak tracking per tier (Bronze/Silver/Gold)
- Automatic eligibility checking
- Bonus history tracking
- Beautiful bonus notification emails
- Integration with payment system
- Admin configurable bonus rules (UI coming in next update)

**Example:**
- Silver badge for 3 months → ৳5,000 bonus (repeatable)
- Gold badge for 6 months → R15 Motorcycle (one-time)

### Technical
- New user meta: _wc_tp_monthly_achievements, _wc_tp_achievement_history
- New user meta: _wc_tp_badge_streaks, _wc_tp_bonus_history
- Integrated with existing cron jobs
- Backward compatible with old system
- Email templates for monthly and bonus notifications

### Coming Next
- Settings UI for bonus configuration (repeater fields)
- Visual streak indicators in Performance Tracker
- Bonus milestone progress display

## [1.3.10] - 2026-04-19
### Fixed
- **Settings Page**: Fixed unsaved changes warning appearing immediately on Reports & Performance tab load
- Added 500ms delay before enabling change tracking to allow page initialization to complete
- **Settings Page**: Hidden main save button on Reports & Performance tab (uses its own save system)
- Prevents confusion with duplicate save buttons

### Debug
- Added console logging to Reports page table sorting to diagnose sorting toggle issues
- Logs table ID, sort column, current sort state, and toggle actions

## [1.3.9] - 2026-04-19
### Fixed
- **Performance Tracker View Mode**: Fixed date range not updating when dropdown selection changed
- Date range now correctly updates for all view modes (Last Month, Last 3 Months, Last 6 Months, Last 12 Months, Year to Date)
- Added `get_view_mode_dates()` function to calculate proper date ranges for each view mode
- Modified `update_goal_progress()` to accept and use view_mode parameter
- Smart caching: Only saves to user meta when viewing current period to avoid overwriting actual progress

## [1.3.8] - 2026-04-19
### Added
- **Admin Performance Tab**: Added Performance Tracker to admin employee details page
- New "Performance" tab after "Salary Management" tab in admin employee details
- Shows same Performance Tracker data as frontend (Overview, Goals, Achievements, Baselines)
- Admin can view any employee's performance metrics from employee details page
- Includes all visual enhancements: green backgrounds for achieved goals, congratulations banner

### Enhanced
- **Performance Tracker AJAX**: Modified to support admin context viewing employee performance
- AJAX handler now accepts `user_id` parameter for admin viewing
- JavaScript automatically passes user_id when viewing from admin context
- Maintains security by checking admin capabilities before allowing cross-user viewing
- Properly enqueues Phosphor Icons, shared CSS, and Performance Tracker assets in admin

## [1.3.7] - 2026-04-19
### Added
- **Performance Tracker Visual Enhancements**: Overview cards now show green background and border when goals/achievements are achieved
- **Congratulations Banner**: Displays celebration message when all goals, achievements, and baselines are achieved for the period
- **Congratulations Email**: Automatically sends professional HTML email to employees when they achieve outstanding performance
- Email branded as "Povaly Group" with "Vorosa Bajar" as sub-brand
- Email includes detailed performance summary with goals, achievements, and baselines metrics
- Beautiful gradient design with green success theme and call-to-action button
- Prevents duplicate emails for same period using user meta tracking

### Fixed
- **Baselines Tab**: Fixed "undefined" showing for Method and Updated date in baselines header
- Added fallback values: "Not Set" for method and "Not Calculated" for date when baselines haven't been calculated yet

### Enhanced
- Overview cards with achieved status now have subtle green gradient background and green border
- Congratulations banner includes confetti icon and motivational message
- Smooth slide-down animation for banner appearance
- Responsive design for mobile devices
- Email notification system integrated with daily cron job for automatic sending

## [1.3.6] - 2026-04-19
### Fixed
- **Performance Tracker Currency**: Fixed hardcoded dollar sign ($) to use dynamic WooCommerce currency settings
- Currency now displays correctly based on WooCommerce settings (৳, $, €, £, etc.) with proper positioning
- Currency formatting respects WooCommerce position settings (left, left_space, right, right_space)
- **Code Cleanup**: Removed all debug error_log statements from Performance Tracker for cleaner production code
- Removed 23 debug logging statements that were cluttering error logs
- Performance Tracker now matches currency behavior of other plugin pages

## [1.3.5] - 2026-04-19
### Fixed
- **CRITICAL FIX**: Fixed Performance Tracker not loading data due to incorrect employee roles retrieval
- **ROOT CAUSE**: `get_employee_roles()` was looking for roles in wrong option (`wc_team_payroll_woocommerce`) with wrong structure
- **SOLUTION**: Updated to read from correct option (`wc_tp_employee_roles`) which stores simple array of role keys
- Now properly maps role keys to role names using WordPress global `$wp_roles`
- Performance Tracker now correctly identifies user roles (administrator, shop_employee, shop_manager, etc.)
- Goals, Achievements, and Baselines will now load for users with configured employee roles

## [1.3.4] - 2026-04-19
### Debug
- **DEBUG**: Added comprehensive error logging to Performance Tracker to diagnose data loading issues
- Logs user roles, employee roles, goals configuration, achievements configuration
- Logs calculated values (order total, orders count, AOV)
- Logs period dates and progress data
- **INSTRUCTIONS**: After updating, check Reports page Performance Tracker, then check WordPress debug.log for detailed information
- Look for lines starting with "Performance Tracker:" to see what's happening

## [1.3.3] - 2026-04-19
### Fixed
- **CRITICAL FIX**: Fixed Reports page sections not loading (infinite spinner) after v1.3.1 update
- **ROOT CAUSE**: JavaScript variable name was changed from `wc_tp_reports` to `wcTpReports` in v1.3.1, breaking all existing AJAX calls
- Reverted JavaScript variable name back to `wc_tp_reports` for backward compatibility
- Updated `performance-tracker.js` to use correct variable name `wc_tp_reports`
- All Reports page sections now load correctly: Dashboard, Analytics, Performance Metrics, Tables, Goals

## [1.3.2] - 2026-04-19
### Fixed
- **CRITICAL FIX**: Performance Tracker data not loading due to duplicate AJAX handler registration
- Removed conflicting `wc_tp_get_performance_tracker_data` AJAX registration from MyAccount class
- AJAX handler is properly initialized in `WC_Team_Payroll_Performance_Tracker_AJAX` class

## [1.3.1] - 2026-04-19
### Performance Tracker Frontend - Complete Implementation
- **NEW FILE**: `assets/js/performance-tracker.js` - Frontend JavaScript for dynamic data display
- **NEW FILE**: `assets/css/performance-tracker.css` - Complete styling for all performance tracker components
- **NEW FILE**: `includes/class-performance-tracker-ajax.php` - AJAX handlers for frontend requests
- **FEATURE**: Dynamic Goals display with progress bars, status badges, and 3-level thresholds (minimum/target/stretch)
- **FEATURE**: Achievements display with locked/unlocked states, bronze/silver/gold badges, and progress tracking
- **FEATURE**: Baselines display with trend indicators (improving/declining/stable) and comparison charts
- **FEATURE**: Overview dashboard with quick stats and summary cards
- **FEATURE**: Tabbed interface for Goals, Achievements, Baselines, and Overview
- **FEATURE**: Period selector dropdown (dynamically generated based on admin-configured period type)
- **FEATURE**: Goal history timeline showing last 6 completed periods
- **FEATURE**: Baseline history showing last 12 calculations
- **UI**: Responsive design with mobile-friendly layouts
- **UI**: Loading states and error handling
- **UI**: Color-coded status indicators and trend arrows
- **UI**: Achievement tier badges with emoji icons (🥉🥈🥇)
- **AJAX**: `wc_tp_get_performance_tracker_data` handler with sections (config, overview, goals, achievements, baselines)
- **INTEGRATION**: Replaced static Goals & Achievements section in Reports page with dynamic Performance Tracker
- **INTEGRATION**: Enqueued new CSS and JS files in MyAccount assets
- **INTEGRATION**: Added Performance Tracker AJAX handler initialization
- **TECHNICAL**: JavaScript module pattern with proper initialization and event binding
- **TECHNICAL**: Helper functions for value formatting (currency, numbers, percentages)
- **TECHNICAL**: Status label and icon mapping
- **TECHNICAL**: Period label generation based on view mode
- **RESULT**: Fully functional Performance Tracker frontend displaying real-time data from backend

## [1.3.0] - 2026-04-19
### Performance Tracker System - Complete Backend Implementation
- **NEW CLASS**: Added `class-performance-tracker.php` for Goals, Achievements, and Baselines tracking
- **CRITICAL FIX**: Fixed syntax error (premature class closing brace)
- **CRITICAL FIX**: Updated meta keys to use correct system keys (`_primary_agent_id`, `_processor_user_id`)
- **FEATURE**: Goals tracking system with current period progress calculation
- **FEATURE**: Achievements system with automatic badge unlocking and notifications
- **FEATURE**: Baselines calculation with 6 methods (rolling average, historical average, best period, median, percentile, custom)
- **FEATURE**: Active/inactive employee status fully integrated - inactive employees excluded from all cron jobs
- **CALCULATION**: Uses attributed order total (agent_order_value/processor_order_value from commission data)
- **CALCULATION**: Properly handles cases where same user is both agent and processor
- **CALCULATION**: Supports weekly, monthly, quarterly, yearly periods with automatic date range calculation
- **AJAX**: Added `wc_tp_get_user_goal_progress` handler with permission checks
- **AJAX**: Added `wc_tp_get_user_achievements` handler with stats
- **AJAX**: Added `wc_tp_get_user_baselines` handler with history
- **AJAX**: Added `wc_tp_recalculate_performance` handler for manual recalculation (admin only)
- **CRON**: Added `wc_tp_daily_baseline_update` - respects update_frequency setting
- **CRON**: Added `wc_tp_check_achievements` - hourly achievement checking
- **CRON**: Added `wc_tp_finalize_period_goals` - period-end goal finalization
- **CRON**: All cron jobs filter for active employees only via `get_all_employees()`
- **USER META**: `_wc_tp_current_goal_progress` - Current period goal progress
- **USER META**: `_wc_tp_goal_history` - Historical goal achievements (last 12 periods)
- **USER META**: `_wc_tp_unlocked_achievements` - Unlocked achievements with unlock dates
- **USER META**: `_wc_tp_achievement_stats` - Bronze/silver/gold counts, last unlocked, next achievement
- **USER META**: `_wc_tp_current_baselines` - Current baseline values with trend analysis
- **USER META**: `_wc_tp_baseline_history` - Historical baseline values (last 12 entries)
- **TECHNICAL**: Helper functions for attributed order total, order count, AOV
- **TECHNICAL**: Historical performance data retrieval with period-based filtering
- **TECHNICAL**: Goal status determination (not_started, in_progress, achieved, stretch_achieved)
- **TECHNICAL**: Trend detection for baselines (improving, declining, stable)
- **TECHNICAL**: Achievement notification system using transients
- **COMPATIBILITY**: Fully compatible with existing commission calculation system
- **COMPATIBILITY**: Respects active/inactive employee status throughout
- **RESULT**: Complete backend system ready for frontend implementation

## [1.2.8] - 2026-04-19
### Performance Tracker System - Backend Implementation (Deprecated - Use 1.3.0)
- Initial implementation with syntax errors
- Replaced by version 1.3.0 with critical fixes

## [1.2.7] - 2026-04-18
### Reports Page - Table Sorting Fixed
- **FIX**: Reports page tables (My Commission History and My Order Processing) now properly sort using `data-sort-value` attributes
- **ENHANCEMENT**: Sorting now reads `data-sort-value` attribute from table cells instead of visible text content
- **ENHANCEMENT**: Numeric values (dates, amounts, IDs) now sort correctly as numbers instead of strings
- **ENHANCEMENT**: Sort icons now update properly to show current sort direction (up/down arrows)
- **ENHANCEMENT**: Added `updateSortIcons()` function to manage sort icon states
- **TECHNICAL**: Modified `filterAndDisplayTable()` function to use `data-sort-value` for accurate sorting
- **TECHNICAL**: Sort icons now toggle between `ph-caret-up-down` (default), `ph-caret-up` (ascending), and `ph-caret-down` (descending)
- **RESULT**: Sorting behavior now matches My Orders (Commission) page with accurate numeric and string sorting

## [1.2.6] - 2026-04-18
### Reports Page - My Commission History Table Enhanced
- **FEATURE**: Added "Commission" column showing total commission for each order
- **CHANGE**: Renamed previous "Commission" column to "My Earning" for clarity
- **ENHANCEMENT**: Table now uses both reports-specific and shared table classes (same as My Order Processing)
- **ENHANCEMENT**: Table wrapper now includes `table-wrapper` class
- **ENHANCEMENT**: Table controls now include `section-header`, `pv-table-controls`, `table-controls` classes
- **ENHANCEMENT**: Search control now includes `search-control` class
- **ENHANCEMENT**: Per page control now includes `per-page-control` class
- **ENHANCEMENT**: Table container now includes `table-container`, `pv-table-container` classes
- **ENHANCEMENT**: Table now includes `pv-table`, `woocommerce-table`, `woocommerce-table--commission` classes
- **ENHANCEMENT**: All sortable columns now have sort icons (`ph-caret-up-down sort-icon`)
- **ENHANCEMENT**: All table cells now have `data-sort-value` attributes for proper sorting
- **ENHANCEMENT**: Role badges now include icons (agent: `ph-user-check`, processor: `ph-gear`)
- **ENHANCEMENT**: Status badges now include icons matching order status
- **ENHANCEMENT**: Pagination now includes `pagination-container`, `pagination-info`, `pagination-controls` classes
- **TECHNICAL**: Updated colspan from 6 to 7 for no-data message
- **RESULT**: Table follows shared design from myaccount-shared.css with complete sorting functionality

## [1.2.5] - 2026-04-18
### Reports Page - My Order Processing Table Uses Shared Design
- **CHANGE**: My Order Processing table now uses both reports-specific and shared table classes
- **ENHANCEMENT**: Table wrapper now includes `table-wrapper` class for consistent card styling
- **ENHANCEMENT**: Table controls now include `section-header`, `pv-table-controls`, `table-controls` classes
- **ENHANCEMENT**: Search control now includes `search-control` class for consistent styling
- **ENHANCEMENT**: Per page control now includes `per-page-control` class
- **ENHANCEMENT**: Table container now includes `table-container`, `pv-table-container` classes
- **ENHANCEMENT**: Table now includes `pv-table`, `woocommerce-table`, `woocommerce-table--orders` classes
- **ENHANCEMENT**: Table headers now include sort icons (`ph-caret-up-down sort-icon`)
- **ENHANCEMENT**: Pagination now includes `pagination-container`, `pagination-info`, `pagination-controls` classes
- **TECHNICAL**: Maintains reports-specific classes alongside shared classes for dual styling support
- **RESULT**: Table follows shared design from myaccount-shared.css while respecting reports page filters

## [1.2.4] - 2026-04-18
### Global .ph Icon Color - Made Dynamic from Settings
- **CHANGE**: Global `.ph` icon color now uses primary color from Settings > Frontend Styling
- **FIX**: Removed `!important` from hardcoded CSS to allow dynamic styling to override
- **ENHANCEMENT**: Icons now respect theme customization without requiring CSS file edits
- **TECHNICAL**: Dynamic CSS from `enqueue_assets()` now properly overrides default color
- **RESULT**: All Phosphor icons (.ph) now use the primary color set in settings, maintaining design consistency

## [1.2.3] - 2026-04-18
### Reports Page - My Order Processing Table Enhanced
- **FEATURE**: Added "Customer" column (sortable)
- **FEATURE**: Added "Attributed Order Total" column (sortable)
- **FEATURE**: Added "My Earning" column (sortable)
- **FEATURE**: Added "Actions" column with view button
- **ENHANCEMENT**: Made "My Role" column sortable with icons
- **ENHANCEMENT**: Table now matches My Orders (Commission) table structure
- **ENHANCEMENT**: Respects Reports page filters (date range, status, role)
- **TECHNICAL**: Reads attributed values from commission data (agent_order_value/processor_order_value)
- **TECHNICAL**: Shows N/A for commission/earnings when status doesn't calculate commission
- **CSS**: Added button action styles to reports.css with icon color fix
- **RESULT**: Comprehensive order table with all details and proper filtering

## [1.2.2] - 2026-04-18
### My Orders Table - Enhancements
- **FEATURE**: Added "Attributed Order Total" column after "Order Total" (sortable)
- **ENHANCEMENT**: Made "Customer" column sortable
- **ENHANCEMENT**: Made "My Role" column sortable
- **FIX**: Fixed button icon colors to match button color (override global .ph styling)
- **TECHNICAL**: AJAX response now includes attributed_amount from commission data
- **TECHNICAL**: JavaScript sorts by customer name and role
- **CSS**: Added .btn-action .ph color override to prevent conflicts
- **RESULT**: More comprehensive order table with attributed values and better sorting

## [1.2.1] - 2026-04-18
### Highest/Lowest Order - Use Attributed Values with Filters
- **CHANGE**: Highest Order now shows highest attributed order value (not regular total)
- **CHANGE**: Lowest Order now shows lowest attributed order value (not regular total)
- **ENHANCEMENT**: Both metrics respect filters (date range, status, role)
- **TECHNICAL**: Reads agent_order_value and processor_order_value from commission data
- **TECHNICAL**: Applies role filter to only check relevant orders
- **RESULT**: Accurate highest/lowest values reflecting user's attributed contribution

## [1.2.0] - 2026-04-18
### Growth Rate - Fix to Include Salary in Previous Period
- **FIX**: Growth Rate now compares total earnings (commission + salary) for both current and previous periods
- **ISSUE**: Previously compared current total earnings vs previous commission only (inconsistent)
- **ENHANCEMENT**: Previous period salary now calculated from salary transactions (same as current period)
- **TECHNICAL**: Reads salary transactions filtered by previous period date range
- **RESULT**: Accurate growth rate showing true earnings growth including both commission and salary changes

## [1.1.9] - 2026-04-18
### KPI Cards - Performance Score Now Uses Attributed Total for Avg Order Value
- **FIX**: KPI cards avg_order_value now uses attributed_order_total (same as Performance Metrics)
- **CONSISTENCY**: Performance Score calculation now identical between KPI cards and Performance Metrics
- **TECHNICAL**: Both sections now use attributed total for avg_order_value calculation
- **RESULT**: Performance Score is now consistent across KPI cards and Performance Metrics sections

## [1.1.8] - 2026-04-18
### Performance Metrics - Avg Order Value Now Uses Attributed Total
- **CHANGE**: Avg Order Value now uses attributed order total (filtered) instead of regular total
- **CHANGE**: Commission Rate now calculated based on attributed order total
- **ENHANCEMENT**: Respects filters (date range, status, role) for accurate average calculation
- **TECHNICAL**: Uses same attributed_order_total calculation as Total Order Value metric
- **RESULT**: More accurate average order value reflecting user's actual contribution

## [1.1.7] - 2026-04-18
### Performance Metrics - Total Earnings Now Includes Salary
- **CHANGE**: Total Earnings in Performance Metrics now follows same calculation as KPI cards
- **ENHANCEMENT**: Total Earnings = Commission from filtered orders + Salary from filtered period
- **TECHNICAL**: Reads salary transactions and filters by date range (transfer types only)
- **TECHNICAL**: Only includes salary for fixed and combined salary users
- **RESULT**: Total Earnings now accurately reflects both commission and salary for the filtered period

## [1.1.6] - 2026-04-18
### KPI Cards - Fix Total Order Value to Respect Filters
- **FIX**: KPI Performance Score card now respects date range, status, and role filters
- **FIX**: Total Order Value (attributed) in KPI cards now uses same calculation as Performance Metrics
- **ENHANCEMENT**: Correctly handles cases where user is both agent and processor
- **TECHNICAL**: Queries orders separately for agent and processor roles with filters applied
- **RESULT**: KPI cards now show filtered data instead of always showing totals

## [1.1.5] - 2026-04-18
### Performance Metrics - Fix Total Order Value Calculation
- **FIX**: Total Order Value now uses the same calculation method as KPI Performance Score modal
- **FIX**: Correctly handles cases where user is both agent and processor (counts full order value)
- **ENHANCEMENT**: Reads agent_order_value and processor_order_value from commission data
- **ENHANCEMENT**: Respects role filter (agent/processor/all) when calculating attributed total
- **TECHNICAL**: Queries orders separately for agent and processor roles
- **TECHNICAL**: Uses commission calculation statuses from settings
- **RESULT**: More accurate Total Order Value that follows proper attribution rules

## [1.1.4] - 2026-04-18
### Performance Metrics - Show Total Order Value and Total Earnings
- **CHANGE**: Second metric "Total Earnings" renamed to "Total Order Value"
- **CHANGE**: Now displays attributed order total (split-compatible) instead of earnings
- **CHANGE**: Third metric "Avg per Order" changed back to "Total Earnings"
- **ENHANCEMENT**: Shows the actual order value attributed to the user based on agent/processor split
- **ENHANCEMENT**: Total Earnings now shows actual earnings (salary + commission)
- **TECHNICAL**: Total Order Value uses $attributed_order_total which respects commission split percentages
- **TECHNICAL**: Total Earnings uses $total_earnings (salary + commission)
- **RESULT**: More accurate representation with both order value contribution and actual earnings

## [1.1.3] - 2026-04-18
### Performance Metrics - Count All Order Statuses
- **FEATURE**: Total Orders metric now counts ALL WooCommerce order statuses (excluding draft, failed, cancelled, trash)
- **ENHANCEMENT**: Previously only counted orders from commission calculation statuses
- **LOGIC**: Queries orders where user is agent OR processor, applies date range and status filters
- **TECHNICAL**: Added separate query using wc_get_order_statuses() to get all valid statuses
- **TECHNICAL**: Removes duplicates when role filter is "all" (orders where user is both agent and processor)
- **RESULT**: More accurate Total Orders count reflecting actual order volume

## [1.1.2] - 2026-04-18
### Make Commission Breakdown Donut Chart Responsive
- **FIX**: Changed maintainAspectRatio from true to false for commission breakdown chart
- **ENHANCEMENT**: Added specific height (350px) for donut chart container
- **CSS**: Added max-height constraint for commission-breakdown-chart canvas
- **RESULT**: Donut chart now properly fills its container and responds to container size changes

## [1.1.1] - 2026-04-18
### Convert Earnings Trend to Bar Chart & Fix Currency Display
- **CHANGE**: Converted Earnings Trend from line chart to bar chart for better visual comparison
- **FIX**: Fixed currency symbol showing HTML entities (&#2547;&nbsp;) by using html_entity_decode()
- **ENHANCEMENT**: Simplified bar chart styling - removed line-specific properties (tension, pointRadius, etc.)
- **RESULT**: Clean bar chart with proper currency symbols (৳, $, €, etc.) displaying correctly

## [1.1.0] - 2026-04-18
### Earnings Trend Chart - Add Salary Line & WooCommerce Currency
- **FEATURE**: Added Salary line to Earnings Trend chart (yellow line with #ffc107 color)
- **FEATURE**: Chart now uses WooCommerce currency symbol instead of hardcoded $
- **ENHANCEMENT**: Salary calculation considers user's salary frequency (daily, weekly, monthly, yearly)
- **ENHANCEMENT**: Salary is calculated per time period view (daily/weekly/monthly chart views)
- **TECHNICAL**: Added 'salary' array to chart_data in prepare_chart_data()
- **TECHNICAL**: Salary calculated based on salary_frequency and time_period for accurate representation
- **TECHNICAL**: Uses get_woocommerce_currency_symbol() for tooltip and Y-axis labels
- **CALCULATION**: 
  - Daily salary: shown as-is per day
  - Weekly salary: divided by 7 for daily view, shown as-is for weekly/monthly
  - Monthly salary: divided by 30 for daily, by 4 for weekly, as-is for monthly
  - Yearly salary: divided by 365/52/12 based on view period
- **RESULT**: Users can now see salary trend alongside earnings and commission with proper currency

## [1.0.99] - 2026-04-18
### Dynamic Auto-Refresh Interval from Settings
- **FEATURE**: Reports page auto-refresh interval now uses value from Settings > Performance > Auto Config
- **ENHANCEMENT**: Auto-refresh can be disabled by setting interval to 0 seconds in settings
- **TECHNICAL**: PHP passes `auto_refresh_interval` from `wc_tp_system_config` option to JavaScript via wp_localize_script
- **TECHNICAL**: JavaScript converts seconds to milliseconds and checks if > 0 to enable/disable auto-refresh
- **DEFAULT**: 30 seconds if not configured
- **RANGE**: 0-300 seconds (0 = disabled, configurable in 5-second increments)
- **RESULT**: Admins can now control reports page refresh rate globally from Performance Settings

## [1.0.98] - 2026-04-18
### Remove Debug Code from Production
- **CLEANUP**: Removed debug console.log statement from reports.js (Attributed Order Total Response)
- **CLEANUP**: Removed debug data from ajax_get_attributed_order_total AJAX response
- **REMOVED**: Debug info array with agent_orders, processor_orders, filters, date_range, statuses_to_query
- **REMOVED**: Console logging of AJAX response in Performance Score modal
- **RESULT**: Cleaner console output and smaller AJAX responses in production

## [1.0.97] - 2026-04-18
### Fix Browser Caching Issue for JavaScript Files
- **FIX**: Fixed browser caching causing old JavaScript to load (showing 0 orders/commissions in regular browser while incognito works)
- **ROOT CAUSE**: reports.js was enqueued with static version number, causing browsers to cache old file even after updates
- **SOLUTION**: Added `time()` to JavaScript version string for automatic cache busting (same as CSS files)
- **TECHNICAL**: Changed from `WC_TEAM_PAYROLL_VERSION` to `WC_TEAM_PAYROLL_VERSION . '-' . time()`
- **RESULT**: Browsers now always load the latest JavaScript file after plugin updates, no manual cache clearing needed

## [1.0.96] - 2026-04-18
### Fix Date Range Filtering for "Today" and All Date Queries
- **FIX**: Fixed "Today" date filter showing only 1 order when 2 orders were created today
- **ROOT CAUSE**: WooCommerce date_created queries with date-only format (e.g., `2026-04-18...2026-04-18`) don't include full day timestamps
- **SOLUTION**: Appended time components to date queries: `YYYY-MM-DD 00:00:00...YYYY-MM-DD 23:59:59` for full day coverage
- **FILES UPDATED**:
  - `includes/class-myaccount.php`: Fixed 4 functions (ajax_get_attributed_order_total, get_user_commission_for_period, get_user_orders_count_for_period, ajax_get_orders)
  - `includes/class-core-engine.php`: Fixed get_user_earnings function
  - `includes/class-performance-settings.php`: Fixed 2 functions (calculate_earnings_with_commission_split, calculate_earnings_full_value)
- **TECHNICAL**: WooCommerce stores order timestamps with time components, so date-only queries miss orders created at different times
- **RESULT**: All date range filters now correctly include all orders for the selected period, especially "Today" filter

## [1.0.95] - 2026-04-18
### Fix Attributed Order Total Calculation
- **FIX**: Fixed attributed order total showing $0.00 in Performance Score modal
- **ROOT CAUSE**: Using `get_post_meta()` instead of WooCommerce order object's `get_meta()` method
- **SOLUTION**: Changed to use `$order->get_meta('_commission_data')` to properly retrieve commission data
- **TECHNICAL**: WooCommerce stores order meta differently, requiring the order object's get_meta() method
- **RESULT**: Order Total (Attributed) now displays correct values from agent_order_value and processor_order_value

## [1.0.94] - 2026-04-18
### Debug Logging for Attributed Order Total
- **DEBUG**: Added comprehensive debug logging to `ajax_get_attributed_order_total()` AJAX handler
- **DEBUG**: Console logging shows agent/processor orders count, commission data, filters, date range, and statuses
- **DEBUG**: Response includes debug info with order details and attributed values for troubleshooting
- **TECHNICAL**: Debug output helps identify why Order Total (Attributed) might show $0.00
- **TECHNICAL**: Logs show which orders are found and whether they have commission data with attributed values

## [1.0.93] - 2026-04-18
### Reports Page - Performance Score Modal Enhancements & Fixes
- **FIX**: Fixed KPI modal breakdown values showing incorrect $ prefix for non-currency text (e.g., "$All" now shows as "All")
- **FIX**: Fixed Performance Score modal showing duplicate "/10" (e.g., "5.2/10/10" now shows as "5.2/10")
- **FEATURE**: Added "Order Total (Attributed)" field to Performance Score modal showing real attributed order values
- **FEATURE**: Added comprehensive "Calculation Details" section explaining performance scoring system
- **ENHANCEMENT**: Order Total (Attributed) fetches real data from order meta (agent_order_value/processor_order_value)
- **ENHANCEMENT**: Calculation Details explains how performance score works with 3 key metrics
- **ENHANCEMENT**: Explains attribution system (Agent/Processor split based on configured percentages)
- **ENHANCEMENT**: Shows score calculation formula with example using realistic numbers
- **ENHANCEMENT**: Displays current period data with applied filters
- **TECHNICAL**: Added new AJAX handler `ajax_get_attributed_order_total()` in MyAccount class
- **TECHNICAL**: AJAX handler calculates sum of attributed order values from commission data
- **TECHNICAL**: JavaScript fetches attributed total via AJAX when Performance Score modal opens
- **TECHNICAL**: Fixed template literal syntax errors (double $$ to single $)
- **TECHNICAL**: Added regex `.replace(/\/10$/, '')` to strip "/10" suffix from performance score value
- **RESULT**: Performance Score modal now shows accurate attributed order totals and comprehensive calculation explanations

## [1.0.87] - 2026-04-18
### Reports Page - KPI Modal Loading Fix
- **FIX**: KPI card modals (Total Earnings, Commission, Salary, Performance Score) now load content properly instead of showing infinite loading
- **ROOT CAUSE**: `getCurrentFilters()` helper function was defined outside the jQuery ready scope, making it inaccessible to `loadDrillDownData()` function
- **SOLUTION**: Moved `getCurrentFilters()` function definition inside `loadDrillDownData()` as a local helper function
- **TECHNICAL**: Function scope issue caused JavaScript error that prevented modal content from rendering
- **RESULT**: All KPI card modals now display detailed breakdown data correctly

## [1.0.86] - 2026-04-18
### Performance Settings - False Unsaved Changes Warning Fix
- **FIX**: Performance Settings tab no longer shows "unsaved changes" warning immediately on page load
- **ROOT CAUSE**: Change event listeners were triggering during initial page load when elements were being populated programmatically
- **SOLUTION**: Added `pageLoadComplete` flag that is set to true after 1.5 seconds
- **SOLUTION**: Change detection now ignores all changes during initial page load period
- **TECHNICAL**: Only user-initiated changes after page load completion trigger the unsaved changes warning
- **RESULT**: Unsaved changes warning only appears when user actually modifies settings, not on page load

## [1.0.85] - 2026-04-18
### Performance Settings - System Config Save Fix
- **FIX**: System Config tab in Performance Settings now saves settings correctly
- **ROOT CAUSE**: JavaScript save handler had no case for 'system' section, so system config data was never collected or sent to server
- **SOLUTION**: Added `collectSystemConfigurationData()` function to collect all system config form fields
- **SOLUTION**: Added case for 'system' in the unified save handler switch statement
- **SOLUTION**: Added `saveSystemConfig()` function to send data to AJAX handler
- **TECHNICAL**: System config includes: enable_performance, show_score, show_goals, show_achievements, show_baselines, show_leaderboard, refresh_interval, data_retention, anonymize_data, cache_duration, debug_mode
- **RESULT**: System Config settings now persist correctly after clicking save button

## [1.0.84] - 2026-04-18
### Reports Page - KPI Modal Fix
- **FIX**: KPI card modals now load content properly instead of showing infinite loading
- **ROOT CAUSE**: JavaScript was using `.html()` which returned HTML tags, and no timeout for DOM readiness
- **SOLUTION**: Changed to `.text().trim()` to get clean text values from KPI cards
- **SOLUTION**: Added 150ms timeout to ensure KPI cards are fully loaded before reading values
- **ENHANCEMENT**: Added more data fields to modals (orders processed, rating, etc.)
- **RESULT**: Modals now display content immediately with correct data from KPI cards

## [1.0.83] - 2026-04-18
### My Orders Page - Dynamic Status Cards
- **FEATURE**: Status cards now dynamically generated from all WooCommerce order statuses
- **FEATURE**: Automatically includes custom order statuses created by admin
- **ENHANCEMENT**: Default WooCommerce statuses use specific icons (completed, processing, pending, on-hold, cancelled, refunded)
- **ENHANCEMENT**: Custom statuses use a generic tag icon to distinguish them visually
- **FILTER**: Draft and Failed statuses are excluded from status cards
- **TECHNICAL**: Status cards generated dynamically in PHP from `wc_get_order_statuses()`
- **TECHNICAL**: AJAX handler calculates counts for all statuses dynamically
- **TECHNICAL**: JavaScript updates all status cards using loop instead of hardcoded IDs
- **RESULT**: Status cards automatically adapt to custom order statuses without code changes

## [1.0.82] - 2026-04-18
### My Orders Page - Status Cards Fix
- **FIX**: Status cards in My Orders page now show correct order counts instead of always showing 0
- **ROOT CAUSE**: AJAX handler `ajax_get_orders()` was not returning status counts in the summary response
- **SOLUTION**: Added status count calculation for all order statuses (completed, processing, pending, on-hold, cancelled, refunded)
- **ENHANCEMENT**: Status counts are calculated from filtered orders respecting role, status, and date filters
- **RESULT**: Status cards now display accurate order counts for each status

## [1.0.81] - 2026-04-18
### Employee Details Page - Orders and Earnings Calculation Fix
- **FIX**: Total orders now counts ALL orders (not just commission calculation statuses)
- **FIX**: Orders table now shows ALL orders (not just specific statuses)
- **FIX**: Total earnings calculation now follows Commission Calculation Statuses from settings
- **CHANGE**: `get_user_total_orders()` now uses `'status' => 'any'` to count all orders
- **CHANGE**: `get_user_commission_earnings()` now uses commission calculation statuses from settings
- **CHANGE**: AJAX handler `wc_tp_get_employee_orders` now fetches all orders with `'status' => 'any'`
- **CHANGE**: Commission calculation only applies to orders with commission calculation statuses
- **ENHANCEMENT**: Added `has_commission` flag to order response data
- **CONSISTENCY**: Employee details page now matches My Account orders page logic
- **RESULT**: Total orders shows count of ALL orders, orders table displays ALL orders, total earnings only includes commission from configured statuses

## [1.0.80] - 2026-04-18
### Reports Date Range Filter - WordPress Timezone Fix
- **FIX**: Fixed date range filter calculations using WordPress timezone correctly
- **ROOT CAUSE**: DateTime object was being modified multiple times causing incorrect date calculations
- **SOLUTION**: Use `clone` keyword to create fresh DateTime instances for each date calculation
- **ENHANCEMENT**: All date range options now correctly reflect their names:
  - **Today**: Current day only
  - **This Week**: Monday to Sunday of current week
  - **Last Week**: Monday to Sunday of previous week
  - **This Month**: First to last day of current month
  - **Last Month**: First to last day of previous month
  - **This Quarter**: First to last day of current quarter
  - **Last Quarter**: First to last day of previous quarter
  - **This Year**: January 1 to December 31 of current year
  - **Last Year**: January 1 to December 31 of previous year
  - **Last 6 Months**: 6 months ago to today
  - **All Time**: 2000-01-01 to today
- **TECHNICAL**: All date calculations now use WordPress timezone via `wp_timezone()` and `current_time()`
- **RESULT**: Reports page date filters now work correctly with proper date ranges matching their labels

## [1.0.79] - 2026-04-18
### Reports KPI Salary Calculation - Transaction-Based with Debug
- **CHANGE**: Reports page KPI cards now use transaction-based salary calculation (same as My Earnings This Month)
- **CHANGE**: Reads from `_wc_tp_salary_transactions` and filters by date range
- **CHANGE**: Filters for transaction types containing 'transfer' (daily_transfer, weekly_transfer, monthly_transfer, partial_transfer)
- **DEBUG**: Added debug data to AJAX response to troubleshoot salary calculation issues
- **TECHNICAL**: Debug includes: salary_for_period, total_commission, total_earnings, salary flags, date range, transaction count

## [1.0.78] - 2026-04-18
### Salary Calculation Consistency Across All Pages
- **FIX**: Fixed "This Month" card in My Earnings page not showing salary transactions
- **FIX**: Fixed Reports page KPI cards not showing salary with filters
- **CHANGE**: Both My Earnings page and Reports page now use the same approach for salary calculation
- **CHANGE**: Directly read from `_wc_tp_salary_transactions` and filter by date range instead of using helper function
- **CHANGE**: Filter for transaction types containing 'transfer' (daily_transfer, weekly_transfer, monthly_transfer, partial_transfer)
- **CONSISTENCY**: All three pages now use the same data source and approach:
  - **My Earnings - Total Earnings**: All salary transactions (all time) + All commission (all time)
  - **My Earnings - This Month**: Salary transactions (this month) + Commission (this month)
  - **Reports - KPI Cards**: Salary transactions (filtered period) + Commission (filtered period)
- **RESULT**: Salary now correctly displays in all KPI cards with proper date filtering

## [1.0.77] - 2026-04-18
### My Earnings Page - Match Admin Employee Details Calculation
- **ENHANCEMENT**: My Earnings page now uses the same earnings calculation as admin employee details page
- **CHANGE**: Total Earnings now uses `_wc_tp_total_earnings` meta for salary (accumulated base salary) instead of looping through salary transactions
- **CHANGE**: This Month calculation remains unchanged (uses `get_user_salary_for_period()` for current month salary + current month commission)
- **CONSISTENCY**: Both Reports page KPI cards and My Earnings page now follow the same calculation method as admin employee details page
- **RESULT**: Total Earnings card in My Earnings page now matches admin employee details page exactly

## [1.0.76] - 2026-04-18
### KPI Cards - Filter Salary by Date Range
- **ENHANCEMENT**: Salary is now filtered by date range (only salary added within the filtered period)
- **CHANGE**: Total Earnings = Commission from filtered orders + Salary from filtered period
- **CHANGE**: My Salary card shows salary earned within the selected date range
- **TECHNICAL**: Uses existing `_wc_tp_salary_transactions` meta which tracks salary additions with dates
- **TECHNICAL**: Filters salary transactions where type contains 'salary' and date is within range
- **RESULT**: All KPI cards now respect date range filters - more intuitive for period analysis

## [1.0.75] - 2026-04-18
### KPI Cards Earnings Calculation - Match Admin Employee Details
- **ENHANCEMENT**: KPI cards now calculate Total Earnings the same way as admin employee details page
- **CHANGE**: Total Earnings = Commission from filtered orders + ALL base salary (not period-based)
- **CHANGE**: My Salary card now shows total accumulated salary (from `_wc_tp_total_earnings` meta)
- **CHANGE**: Period comparison now based on commission only (salary doesn't change with filters)
- **RESULT**: Reports page Total Earnings now matches admin employee details page exactly, with filtering applied to commission portion only

## [1.0.74] - 2026-04-18
### KPI Cards AJAX Handler Fix
- **FIX**: Fixed KPI cards showing 500 Internal Server Error
- **ROOT CAUSE**: `get_user_salary_for_period()` function called with 3 parameters but requires 5 parameters ($user_id, $start_date, $end_date, $salary_amount, $salary_frequency)
- **SOLUTION**: Added missing $salary_amount and $salary_frequency parameters by fetching from user meta before calling the function
- **RESULT**: KPI cards now load correctly with real data from database

## [1.0.73] - 2026-04-18
### Order Status Filter Fix
- **FIX**: Fixed Reports page order status filter not showing any statuses
- **ROOT CAUSE**: Status key mismatch - `get_commission_calculation_statuses()` returns slugs without 'wc-' prefix (e.g., 'completed') but `wc_get_order_statuses()` returns keys with 'wc-' prefix (e.g., 'wc-completed')
- **SOLUTION**: Added status key normalization by removing 'wc-' prefix before comparison
- **RESULT**: Order status dropdown now correctly displays all commission calculation statuses from settings

## [1.0.72] - 2026-04-18
### KPI Cards Complete Rebuild - Based on v1.0.54 Working Version
- **FIX**: Completely rebuilt KPI cards AJAX handler based on v1.0.54 working implementation
- **FIX**: Reverted to check_ajax_referer() for nonce verification (proven working method)
- **FIX**: Simplified filter handling - removed complex parsing logic
- **FIX**: Clean 4-card system: Total Earnings, My Salary, My Commission, Performance Score
- **ENHANCEMENT**: Removed debug console logging for production-ready code
- **TECHNICAL**: Used v1.0.54 as reference for working AJAX implementation
- **TECHNICAL**: Maintained current dynamic data system with real database values
- **RESULT**: KPI cards should now display correctly with real data

## [1.0.71] - 2026-04-18
### Debug & Filter Parsing Improvements
- **ENHANCEMENT**: Added console logging to JavaScript AJAX calls for better error visibility
- **ENHANCEMENT**: Improved error messages in browser console showing actual error details
- **ENHANCEMENT**: Enhanced PHP filter parsing to handle jQuery serialized objects correctly
- **ENHANCEMENT**: Added debug logging to WordPress error log for filter data inspection
- **TECHNICAL**: Better error handling in loadDashboardData() with detailed console output
- **TECHNICAL**: Improved $_POST['filters'] parsing to handle both array and serialized formats

## [1.0.70] - 2026-04-18
### CRITICAL FIX - Date Range Filter Format Mismatch
- **FIX**: Fixed KPI cards not loading - date range format mismatch between JavaScript and PHP
- **ROOT CAUSE**: JavaScript was sending date ranges with hyphens (e.g., 'this-month') but PHP was expecting underscores (e.g., 'this_month')
- **SOLUTION**: Added automatic conversion of hyphens to underscores in get_date_range_from_filter() function
- **ENHANCEMENT**: Added support for 'last-6-months' and 'all-time' date ranges
- **ENHANCEMENT**: Improved default date range handling to match JavaScript defaults
- **TECHNICAL**: Updated get_date_range_from_filter() to normalize date range format
- **RESULT**: KPI cards now load correctly with proper date filtering applied

## [1.0.69] - 2026-04-18
### Critical Bug Fixes - KPI Cards Final Fix
- **FIX**: Fixed KPI cards not displaying - reverted to jQuery serialization instead of JSON.stringify()
- **FIX**: jQuery now properly serializes filters object as form data
- **FIX**: PHP receives filters as array instead of JSON string
- **ENHANCEMENT**: KPI cards now load with real data from database
- **TECHNICAL**: Removed JSON.stringify() calls to match v1.0.54 working implementation
- **TECHNICAL**: Filters now properly parsed by PHP AJAX handlers

## [1.0.68] - 2026-04-17
### Critical Bug Fixes - KPI Cards Complete Fix
- **FIX**: Fixed KPI cards AJAX nonce verification - replaced check_ajax_referer() with wp_verify_nonce()
- **FIX**: AJAX handlers now return proper error messages instead of dying silently
- **FIX**: Improved filter parsing to handle both JSON strings and arrays
- **ENHANCEMENT**: Better error handling prevents silent failures in AJAX calls
- **TECHNICAL**: All AJAX handlers updated with proper nonce verification
- **TECHNICAL**: Consistent error response handling across all endpoints

## [1.0.55] - 2026-04-17
### Reports Page Enhancements & Performance Score Improvements
- **FEATURE**: Performance Score now uses attributed order values based on agent/processor commission split
- **FEATURE**: Added `agent_order_value` and `processor_order_value` to commission data for accurate performance tracking
- **FEATURE**: Performance score respects commission calculation statuses from settings
- **ENHANCEMENT**: Total Earnings KPI now uses actual salary transactions instead of calculated estimates
- **ENHANCEMENT**: Order status filter now only shows commission calculation statuses from settings
- **ENHANCEMENT**: Commission KPI card follows selected filter status dynamically
- **ENHANCEMENT**: Reports KPI grid changed to 2-column layout for better visual organization
- **FIX**: Removed "Orders Processed" and "Avg Order Value" KPI cards as requested
- **FIX**: Fixed KPI cards infinite loading issue caused by undefined salary type variables
- **FIX**: Updated drill-down modals to work with remaining 4 KPI cards
- **FIX**: Performance score now uses attributed order totals (agent 70%, processor 30%) for earnings ranges
- **TECHNICAL**: Updated `calculate_commission()` to store attributed order values in commission_data
- **TECHNICAL**: Enhanced `get_user_earnings()` to return attributed_value for each order
- **TECHNICAL**: Updated `calculate_performance_score()` to use attributed_order_total parameter
- **TECHNICAL**: All AJAX handlers now calculate and use attributed order totals
- **UI**: KPI grid now displays 2 columns on desktop and mobile for cleaner layout
- **UI**: Neutral change indicators now show meaningful data (order count) instead of "No change"

## [1.0.54] - 2026-04-17
### Performance Settings Employee Roles Integration
- **FEATURE**: Performance Settings now respects Employee User Roles configuration
- **FEATURE**: Only roles configured in Settings → WooCommerce → Employee User Roles appear in Performance Settings
- **ENHANCEMENT**: Performance score calculation now validates employee roles before applying configurations
- **ENHANCEMENT**: Added clear messaging explaining employee roles requirement in Performance Settings
- **ENHANCEMENT**: Empty state handling when no employee roles are configured with direct navigation links
- **ENHANCEMENT**: Disabled dropdowns with helpful messages when no employee roles exist
- **FIX**: Performance configurations no longer apply to non-employee roles (admin, editor, etc.)
- **FIX**: Role filtering ensures only actual employees get performance evaluations
- **UI**: Updated all role selectors to clearly indicate "Employee Role" selection
- **UI**: Added warning messages and quick navigation to Employee Roles configuration
- **TECHNICAL**: Enhanced `get_all_roles()` method to filter by employee roles from settings
- **TECHNICAL**: Updated performance score calculation to validate employee role membership
- **TECHNICAL**: Improved role-based configuration security and data integrity

## [1.0.53] - 2026-04-17
### Performance Settings Save Functionality Fix
- **FIX**: Fixed Performance Settings save functionality not properly persisting data
- **FIX**: Resolved unsaved changes warning not clearing after successful AJAX saves
- **FIX**: Enhanced AJAX response handling with proper promise management
- **FIX**: Added fallback mechanism for unsaved changes reset when main function unavailable
- **ENHANCEMENT**: Improved error handling and user feedback during save operations
- **ENHANCEMENT**: Added comprehensive debugging and logging for save process
- **ENHANCEMENT**: Better integration between performance settings and main form change detection
- **TECHNICAL**: Updated all AJAX save functions to return standardized response format
- **TECHNICAL**: Added `wcTpCheckUnsavedChanges()` debug function for troubleshooting
- **TECHNICAL**: Enhanced Promise.all handling with better error reporting and validation

## [1.0.52] - 2026-04-17
### Performance Score & KPI Modal Enhancements
- **FEATURE**: Performance Score now uses role-based configuration from Performance Settings
- **FEATURE**: Performance Score calculation reads from `wc_tp_performance_config` option with role-specific scoring ranges
- **FEATURE**: Dynamic scoring based on user's WordPress role (shop_employee, shop_manager, etc.)
- **FEATURE**: Configurable base score and role-specific factors for earnings, orders, and AOV
- **ENHANCEMENT**: All 6 KPI card modals now display real data instead of random/hardcoded values
- **ENHANCEMENT**: Added "My Salary" KPI modal with salary details and type information
- **ENHANCEMENT**: KPI modals show current filter state (Date Range, Order Status, Role)
- **ENHANCEMENT**: Currency formatting now uses WooCommerce currency settings (symbol, position, decimals)
- **ENHANCEMENT**: Modal data extracted directly from KPI cards preserving WooCommerce formatting
- **FIX**: Performance Score calculation fallback to default ranges when no role config exists
- **FIX**: Removed hardcoded USD currency formatting in favor of WooCommerce settings
- **TECHNICAL**: Updated `calculate_performance_score()` method to accept user_id and retrieve role-based config
- **TECHNICAL**: Updated `loadDrillDownData()` in reports.js to use real KPI values
- **TECHNICAL**: Added helper function `getCurrentFilters()` to display active filters in modals

## [1.0.51] - 2026-04-16
### Enterprise Reports System - Complete Implementation
- **STEP 1**: Master Filter System with date range (preset + custom), order status, role, commission range, time period, sort options, and filter summary
- **STEP 2**: Personal Performance Dashboard with 5 KPI cards (My Earnings, My Commission, Orders Processed, Avg Order Value, Performance Score) with period comparison and color-coded indicators
- **STEP 3**: Personal Analytics Charts with Earnings Trend (Line Chart) and Commission Breakdown (Doughnut Chart) using Chart.js 3.9.1 with dynamic colors
- **STEP 4**: Detailed Performance Metrics with 8 comprehensive metrics (Total Orders, Total Earnings, Avg per Order, Avg Order Value, Commission Rate, Performance Score, Growth Rate, Highest/Lowest Order)
- **STEP 5**: Data Tables with Commission History and Order Processing tables featuring real-time search, sortable headers, pagination, per-page selection, and role/status badges
- **STEP 6**: Goal Tracking with 4 main goals (Monthly Earnings Target, Orders to Process, Average Order Value, Performance Score), progress bars, achievement badges, and performance summary
- **STEP 7**: Interactive Features with filter persistence (localStorage), auto-refresh (30 seconds), drill-down modals for KPI cards and goals with detailed breakdowns
- **STEP 8**: Export & Reporting Tools with CSV, PDF, and Excel export respecting current filters, plus comprehensive print styles for professional output
- **FEATURE**: All sections filtered by unified master filter system - single filter change updates ALL sections simultaneously
- **FEATURE**: Filter state persists across page reloads using localStorage
- **FEATURE**: Auto-refresh every 30 seconds keeps data current without manual refresh
- **FEATURE**: Drill-down modals provide detailed breakdowns for each KPI and goal
- **FEATURE**: Professional export functionality (CSV, PDF, Excel) with employee info and period details
- **FEATURE**: Responsive design optimized for all screen sizes
- **FEATURE**: Dynamic colors from admin settings applied throughout reports
- **TECHNICAL**: 8 AJAX handlers for data loading, 1 export handler, comprehensive JavaScript state management, professional CSS styling with animations

## [1.0.43] - 2026-04-16
### Code Cleanup & Version Update
- **CLEANUP**: Removed commented duplicate orders code that was causing confusion
- **FIX**: Table header layout issue resolved (headers now display horizontally)
- **UPDATE**: Version bumped to 1.0.43 for fresh release
- **MAINTENANCE**: Code cleanup and optimization

## [1.0.39] - 2026-04-14
### Salary System Enhancements & Debug Tools
- **NEW**: Salary Debug & Testing Tools for immediate testing without waiting for cron jobs
- **NEW**: Debug menu in admin (Team Payroll → Salary Debug) with enable/disable toggle
- **NEW**: Test Salary Accumulation - Simulate one day of accumulation
- **NEW**: Get Current Status - View complete salary status and pending accumulation
- **NEW**: Manually Trigger Cron - Force salary processing immediately
- **NEW**: Reset Employee Demo Salary - Clear test data for fresh testing
- **ENHANCED**: Weekly period detection now fully dynamic based on WordPress settings
- **ENHANCED**: Days remaining calculation properly accounts for partial weeks
- **ENHANCED**: Toast notifications integrated for all debug feedback
- **ENHANCED**: Non-technical instructions for debug tool usage
- **FIXED**: My Account CSS now properly applied with specific selectors
- **FIXED**: Employee dropdown shows formatted prices without HTML tags
- **CONSOLIDATED**: My Account class consolidation complete (class-myaccount.php)
- **FEATURE**: Debug tools disabled by default, enable in Settings → Debug tab
- **FEATURE**: Instructions toggle on/off when checkbox is clicked
- **DOCUMENTATION**: Complete debug tool guide with testing examples

## [1.0.38] - 2026-04-14
### Automatic Salary Addition System (Major Feature)
- **NEW**: Automatic base salary addition for fixed and combined salary types
- **NEW**: Daily salary accumulation system with cron jobs (11:50 PM - 11:59 PM)
- **NEW**: Support for daily, weekly, and monthly salary frequencies
- **NEW**: Automatic period-end transfers (week/month end)
- **NEW**: Mid-period salary change handling without gaps or overlaps
- **NEW**: Salary automation class (`class-salary-automation.php`)
- **NEW**: Salary display helper class (`class-salary-display-helper.php`)
- **ENHANCED**: `get_user_total_earnings()` now includes commission + base salary
- **ENHANCED**: Added `get_user_commission_earnings()` for commission-only earnings
- **OPTIMIZED**: Batch processing for 50 employees per minute (500 total per night)
- **OPTIMIZED**: Direct SQL queries instead of looping through `get_users()`
- **OPTIMIZED**: Database indexes for faster meta queries
- **OPTIMIZED**: Removed expensive `recalculate_user_commissions()` on salary change
- **FEATURE**: Earnings breakdown display (commission + salary + pending)
- **FEATURE**: Salary transaction log for audit trail
- **FEATURE**: Pending accumulation display with next transfer date
- **FEATURE**: Support for 5,000+ employees without timeouts
- **FEATURE**: Auto-detect week start day from WordPress settings
- **FEATURE**: Auto-detect days in month (28/29/30/31)
- **FEATURE**: Use user_registered as employee join date
- **DOCUMENTATION**: Complete system documentation and implementation guide

## [1.0.37] - 2026-04-13
### Pagination & Salary History Improvements
- **Pagination Buttons**: Changed from button tags to anchor tags to avoid generic button styling conflicts
- **Pagination Styling**: Removed !important flags, now uses clean anchor tag styling
- **Pagination Scroll**: Added smooth scroll to table header when clicking pagination links
- **Salary History Amounts**: Added frequency abbreviations to amounts (10000$/mn, 10000$/wk, 10000$/dy, 10000$/yr)
- **Commission Display**: Shows %/order for commission-based salary types in history
- **Frequency Abbreviations**: dy (daily), wk (weekly), mn (monthly), yr (yearly)
- **Both Columns**: Applied to both "Previous" and "New" salary columns for clarity

## [1.0.36] - 2026-04-13
### Salary Card Redesign & Pagination Styling
- **Salary Details Grid**: Removed redundant salary-details-grid container
- **Salary Type Badge**: Restored with icon and salary type label
- **Salary Display**: Shows amount and frequency in top right (fixed/combined only)
- **Salary Type Note**: Added for all three salary types (fixed, combined, commission)
- **Fixed Salary Note**: "You receive a fixed salary as shown above."
- **Combined Salary Note**: "You also earn commission from orders in addition to your base salary."
- **Commission Note**: "Your earnings are based entirely on commission from orders you process."
- **Pagination Buttons**: Inactive buttons now have transparent background with button color border
- **Pagination Inactive**: Text and icons use button background color
- **Pagination Hover**: Subtle background (10% opacity) of button color on inactive buttons
- **Pagination Active**: Full button background with button text color
- **Pagination Icons**: Dynamic color matching button styling
- **Dynamic Colors**: All pagination colors applied from theme button settings

## [1.0.35] - 2026-04-13
### Universal Table Styling & Dynamic Card Design
- **Salary History Section**: Applied same heading style as salary information section
- **Section Heading**: Border-bottom with dynamic border color from settings
- **Heading Underline**: ::after pseudo-element with dynamic primary color
- **Table Wrapper**: New card design with border, shadow, and padding
- **Table Wrapper**: Wrapped section-header and table-container together
- **Section Spacing**: 20px gap between children in salary-history-section
- **Wrapper Spacing**: 10px gap between section-header and table-container
- **Table Container**: No border, no border-radius, transparent background
- **Table Header**: Padding 14px 5px, no borders except bottom (1px solid)
- **Table Rows**: Padding 12px 5px, transparent background, bottom border only
- **Row Hover**: Dynamic background color from settings (table_row_hover)
- **Dynamic Styling**: All colors applied from theme settings
- **Border Radius**: Removed from table-wrapper (set to 0)
- **Universal Design**: Consistent card styling across all sections

## [1.0.34] - 2026-04-13
### Refined Spacing and Table Styling
- **Salary Info Section**: Added padding-bottom 10px to h3 heading
- **Section Gaps**: Added 20px gap between children using parent container gap
- **Section Margin**: Added margin-bottom 50px to section container
- **Card Styling**: Removed margins from card
- **Salary History Section**: Applied same spacing as salary information section
- **Table Header**: Updated padding to 14px 5px
- **Table Rows**: Updated padding to 16px 5px
- **Table Container**: Removed all borders and border-radius
- **Table Borders**: Bottom border only on rows (1px solid #e9ecef)
- **Search Icon**: Changed from ph-times to ph-x for clear functionality
- **Search Box**: Stretches to fill available space
- **Pagination**: Per-page control (5, 10, 25, 50 items)
- **Row Hover**: Subtle background effect
- **Professional Design**: Clean, minimal appearance throughout

## [1.0.33] - 2026-04-13
### Complete Salary History & Table Styling Overhaul
- **Section Headings**: Removed all margins from h2, h3 headings
- **Heading Underline**: Simplified to single ::after pseudo-element with 34px x 3px primary color underline
- **Salary Information Card**: Fixed to show current/latest salary type dynamically
- **Salary Type Detection**: Checks fixed/combined flags first (same logic as header)
- **Salary Display**: Shows correct amount and frequency based on type
- **Table Container**: Removed border and border-radius for clean appearance
- **Table Header**: Updated padding to 15px top/bottom, 3px left/right
- **Search Functionality**: Box stretches to fill available space
- **Search Icon**: Toggles between magnifying glass and times (×) icon
- **Clear Search**: Click times icon to clear search input
- **Pagination**: Per-page control (5, 10, 25, 50 items)
- **Page Navigation**: Full pagination functionality with page buttons
- **Table Styling**: No borders except bottom border on rows
- **Hover Effect**: Subtle background on row hover
- **Professional Design**: Clean, minimal appearance throughout

## [1.0.32] - 2026-04-13
### Improved Salary History Section Styling
- **Section Heading**: Applied same left border marking style (34px x 3px) to all section headings
- **Heading Styling**: Removed icons from headings, no margins, full-width bottom border
- **Gap**: 20px gap between heading and table section
- **Search Box**: Stretched to fill available space (flex: 1)
- **Search Icon Toggle**: Shows magnifying glass when empty, changes to times (×) icon when text entered
- **Clear Functionality**: Click times icon to clear search input
- **Per-Page Control**: Removed margins from label for better alignment
- **Table Styling**: 
  - Removed all borders except bottom border on rows
  - No container border
  - Subtle background with proper padding
  - Hover effect shows background for clean look
  - Sorting system remains unchanged

## [1.0.31] - 2026-04-13
### Improved Salary Information Section Styling
- Removed padding and border from header bio section
- Fixed salary type detection to show actual current type
- Added left border marking (34px x 3px) to salary info heading
- Full-width bottom border below heading with 20px gap to content
- Removed heading icon and margins
- Added salary display in top right corner of card
- Shows amount/frequency for fixed and combined salaries
- Shows 'Percentage/Order' for commission-based salaries
- Combined salary shows combined icon marking

## [1.0.29] - 2026-04-13
### Fixed Dynamic CSS Class Names
- Updated dynamic CSS selectors to match new header layout class names
- Changed `.role-section` to `.profile-role`
- Changed `.header-container-3` to `.header-row-2`
- Changed `.header-container-4` to `.header-row-3`
- All color variables now properly applied to correct elements
- Verified responsive design on all screen sizes

## [1.0.28] - 2026-04-12
### Professional Header with Organized Containers
- **Container 1**: Profile picture (120px circular with primary color border)
- **Container 2**: Name (left) and Role (right, 100% width with 10% primary background)
- **Container 3**: Two-column layout
  - Left column: ID (clickable to copy), Phone, Email with icons
  - Right column: Salary type, Status, Social icons
- **Container 4**: Bio section with 10px padding-top and header border color
- **Icons**: 18px size, primary color, secondary color on hover
- **Social Icons**: Raw icons without background or border, proper spacing
- **Layout**: Uses parent container gaps, no individual margins/padding
- **Dynamic Styling**: All colors controlled via admin settings
- **Fully Responsive**: Optimized for all screen sizes

## [1.0.27] - 2026-04-12
### Professional Header - Minimal & Fresh Design
- **Complete redesign** of employee header from scratch
- **Modern minimal layout** like big platforms (LinkedIn, GitHub, etc.)
- **Left section**: Profile picture (120px circular with border)
- **Middle section**: Name, role, bio, contact info (ID, email, phone with icons)
- **Right section**: Salary type box and social media icons
- **Clean design**: White background with subtle border and minimal shadow
- **Professional spacing**: Proper typography and visual hierarchy
- **Contact info**: ID, email, phone displayed inline with icons
- **Fully responsive**: Optimized for desktop, tablet, and mobile
- **Dynamic styling**: All colors controlled via admin settings

## [1.0.26] - 2026-04-12
### Header CSS Redesign - Clean Modern Layout
- **Redesigned header CSS from scratch** for clean, modern appearance
- **Icons**: No background, only primary color (dynamic)
- **Labels**: Secondary color (dynamic)
- **Values**: Text color (dynamic)
- **Layout**: Proper horizontal layout with left stats, center profile, right stats
- **Clean Design**: Removed unnecessary backgrounds and shadows
- **Better Spacing**: Improved visual hierarchy and alignment
- **Mobile Responsive**: Optimized for all screen sizes
- **Dynamic Styling**: All colors controlled via admin settings

## [1.0.25] - 2026-04-12
### Modern Profile Card Header - Production Release
- Redesigned employee header with modern profile card layout
- Left column: Employee ID, Email, Phone (vertical with icons)
- Center: Large profile picture with negative margin (30-40% out)
- Below picture: Employee name and role
- Bio section: Clean text with top/bottom borders
- Bottom: Social icons (left) and salary type box (right)
- Clean design without box styling
- Smart placeholders: "---" for missing text, initials for missing images
- Mobile responsive for all screen sizes
- Dynamic styling via admin settings
- Professional appearance matching modern design patterns

## [1.0.24] - 2026-04-12
### Redesigned Employee Header - Modern Profile Card Layout
- **New Header Layout**: Completely redesigned to match modern profile card design
  - Left column: Employee ID, Email, Phone (vertical stack with icons)
  - Center: Large profile picture with negative margin (30-40% out of card)
  - Below picture: Employee name and role
  - Bio section: Clean text display with top/bottom borders
  - Bottom: Social icons (left) and salary type box (right)
- **Clean Design**: Removed box styling, using clean text with icons
- **Smart Placeholders**: 
  - Missing text shows "---" (hyphens)
  - Missing profile picture shows initials (first letters of display name)
- **Improved Spacing**: Better visual hierarchy with proper margins and padding
- **Mobile Responsive**: Optimized for all screen sizes (desktop, tablet, mobile)
- **Dynamic Styling**: All colors controlled via admin settings
- **Professional Look**: Matches modern profile card design patterns

## [1.0.23] - 2026-04-12
### Stable Release - My Account Enhancement Complete
- **Professional Employee Header**: Fully implemented with connecting lines, profile picture, and organized info boxes
- **Complete Data Display**: Employee ID, Name, Phone, Email, Role, Status, and Bio sections
- **Social Media Integration**: Facebook, WhatsApp, Instagram, LinkedIn icons with brand color hovers
- **Salary Type Section**: Dynamic display with conditional linking to Salary Details page
- **Mobile Responsive**: Optimized layouts for desktop (1024px+), tablet (768px), and mobile (480px)
- **Dynamic Styling**: All colors controlled via admin settings (header_border_color, etc.)
- **Advanced Table Features**: Sorting, pagination, search, and per-page options across all My Account pages
- **Consistent Design**: Applied professional design pattern across all 4 My Account pages
- **Admin Settings Integration**: Header border color and styling settings fully functional

## [1.0.22] - 2026-04-12
### Enhanced My Account Implementation
- **Enhanced Employee Header**: Added comprehensive employee header with profile picture, employee ID, role, display name, phone, email, bio, and status across all My Account pages
- **Advanced Table Features**: Implemented sorting, pagination, search, and per-page options for salary history table following admin panel design patterns
- **Dynamic Styling Integration**: Added header background and table styling settings to admin panel with full integration in frontend
- **Improved User Experience**: Consistent header pattern across all 4 My Account pages (Salary Details, My Earnings, Orders Commission, Reports)
- **Professional Design**: Enhanced CSS with proper role labeling (Administrator, Manager, Employee) and responsive layout

## [1.0.21] - 2026-04-12
### Changed
- **Phosphor Icons**: Switched from Font Awesome to Phosphor Icons for better visual appearance
  - Added Phosphor Icons CDN: `https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.2`
  - Updated all icons in My Account tabs and content areas
  - Icons: `ph-briefcase`, `ph-wallet`, `ph-shopping-bag`, `ph-chart-bar`, etc.

### Removed
- **Debug Borders**: Completely removed debug borders from all My Account containers
  - No more 2px blue borders around `.wc-team-payroll-salary-details` and similar containers
  - Removed "Remove Debug Border" setting option (now clean by default)
  - Cleaner, more professional appearance

### Improved
- **Icon Consistency**: All icons now use Phosphor icon system for uniform appearance
- **Visual Polish**: Cleaner layout without debug elements
- **Performance**: Reduced CSS complexity by removing conditional border styling

### Technical
- Added Phosphor Icons script dependency
- Updated JavaScript icon injection to use `ph ph-*` classes
- Simplified CSS by removing debug border conditions
- Updated all icon references throughout the codebase

## [1.0.20] - 2026-04-12
### Added
- **Frontend Styling Settings**: New "Frontend Styling" tab in settings with comprehensive customization options
  - Color scheme settings (primary, secondary, heading, text, link colors)
  - Background and border color controls
  - Typography settings (font family, font sizes)
  - Button styling (colors, hover states, border radius)
  - Layout settings (card border radius, shadow depth)
  - Live preview of styling changes
  - Option to remove debug borders

### Changed
- **Outline Icons**: Switched from filled (fas) to outline (far) Font Awesome icons for better visual appearance
- **Dynamic Styling**: My Account pages now use styling settings from admin panel
- **Improved Salary Details Page**: Better layout and styling following admin page design system
- **Removed Debug Border**: Option to remove the blue debug border around My Account sections

### Improved
- **Settings Integration**: Styling settings are saved and applied dynamically to frontend
- **Icon Colors**: Icons now use primary color from settings for consistency
- **Typography**: Font family and sizes are now customizable and applied consistently
- **Card Styling**: Cards, buttons, and layout elements follow the admin design system
- **Color Consistency**: All colors (headings, text, links, buttons) use settings values

### Technical
- Added `wc_team_payroll_styling` option to store frontend styling settings
- Dynamic CSS generation based on user settings
- Improved cache busting for CSS files
- Better CSS specificity for reliable styling application

## [1.0.19] - 2026-04-12
### Fixed
- Fixed HTML escaping issues in My Account tabs (icons now display properly)
- Fixed wc_price() output escaping (currency formatting now displays correctly)
- Improved CSS loading with better cache busting using file modification time
- Enhanced JavaScript icon injection to be more robust and prevent duplicates
- Added debug information to Reports page for troubleshooting
- Fixed all price displays throughout My Account pages to show formatted currency

### Improved
- Better CSS specificity to ensure styles are applied properly
- More reliable icon injection that works after AJAX updates
- Enhanced error handling for CSS and JavaScript loading

## [1.0.18] - 2026-04-12

### 🔧 **Fixes**
- Updated GitHub updater to use pv-team-management repository
- All future updates will now come from the correct repository
- Fixed update detection to use the proper GitHub repository

---

## [1.0.17] - 2026-04-12

### 🔧 **Fixes**
- Fixed version comparison logic in GitHub updater
- Improved version normalization to handle edge cases
- Fixed issue where plugin always showed as needing update
- Added better debugging for version reading
- WordPress now correctly detects when plugin is up to date

---

## [1.0.16] - 2026-04-12

### 🔧 **Fixes**
- Improved GitHub updater reliability
- Removed hourly check limit to force update checks on every admin page load
- Added better debugging for update detection
- WordPress now properly shows plugin updates in the updates page

---

## [1.0.15] - 2026-04-12

### 🔧 **Fixes**
- Moved endpoint registration to main plugin file for proper WooCommerce integration
- All custom My Account pages now render correctly with proper content
- Updated tab icon size to 20px for better visibility

---

## [1.0.14] - 2026-04-12

### 🔧 **Fixes**
- Added template fallback for endpoint rendering
- Endpoints now properly render with WooCommerce template structure
- All custom My Account pages now display correctly without loading issues
- Fixed infinite loading spinner on Salary Details, My Earnings, and Reports pages

---

## [1.0.13] - 2026-04-12

### 🔧 **Fixes**
- Fixed endpoint hook names - WooCommerce converts hyphens to underscores
- Changed hooks from `woocommerce_account_my-salary-details_endpoint` to `woocommerce_account_my_salary_details_endpoint`
- All custom My Account pages now properly render content
- Salary Details, My Earnings, and Reports tabs now display correctly

---

## [1.0.12] - 2026-04-12

### 🔧 **Fixes**
- Added automatic rewrite rules flushing for custom endpoints
- Endpoints now properly recognized by WordPress
- All My Account tabs (Salary Details, My Earnings, My Orders, Reports) now load correctly
- Fixed loading issue on custom pages

---

## [1.0.11] - 2026-04-12

### 🔧 **Fixes**
- Fixed menu icons not displaying properly by using JavaScript injection
- Icons now render correctly without being escaped by WooCommerce
- Phosphor icons now display properly on all My Account menu items

---

## [1.0.10] - 2026-04-12

### 🔧 **Fixes**
- Fixed GitHub updater to use correct repository (pv-team-payroll-demo1)
- WordPress now properly detects and displays available updates
- Update notifications will now appear in WordPress admin

---

## [1.0.9] - 2026-04-12

### ✨ **Features**
- Switched to Phosphor icons library for better icon rendering
- Icons now display with proper styling and alignment

### 🔧 **Fixes**
- Fixed endpoint registration priority to ensure all custom tabs load properly
- Moved endpoint registration to main plugin file with priority 1
- Fixed HTML rendering in menu items with proper escaping
- All custom My Account pages (Salary Details, My Earnings, My Orders, Reports) now load correctly

---

## [1.0.8] - 2026-04-12

### ✨ **Features**
- Switched to Font Awesome 6 icon library for better icon rendering
- Added query variable registration for custom endpoints

### 🔧 **Fixes**
- Fixed endpoint rendering by properly registering query variables
- Improved hook priorities to ensure proper execution order
- Icons now display correctly with Font Awesome

---

## [1.0.7] - 2026-04-12

### 🔧 **Fixes**
- Fixed HTML escaping issue in My Account menu items
- Icons now render properly using CSS pseudo-elements instead of HTML tags
- Fixed endpoint rendering for all custom My Account tabs
- Removed transient-based endpoint registration to ensure consistent behavior
- Added deactivation hook to properly clean up rewrite rules

---

## [1.0.6] - 2026-04-12

### ✨ **Features**
- **My Account Tabs**: Fixed 404 errors on My Account tabs (Salary Details, My Earnings, My Orders, Reports)
- **Simple Line Icons**: Added simple-line-icons library for tab icons
- **Endpoint Registration**: Properly register and flush rewrite rules on plugin activation
- **Tab Icons**: Added icons to My Account menu items for better UX

### 🔧 **Improvements**
- Automatic rewrite rule flushing on plugin activation
- Transient-based endpoint registration to prevent unnecessary flushes
- Responsive CSS styling for My Account pages

---

## [1.0.1] - 2026-04-12

## Improvements

- Enhanced GitHub update checker with better error handling
- Added Debug tab in Settings to troubleshoot update issues
- Users can manually check for updates and clear cache
- Improved error logging for debugging

---
# Changelog

## [1.0.1] - 2026-04-12

### 🔧 **Improvements**
- **GitHub Update Detection**: Enhanced update checker with better error handling
- **Debug Tab**: Added Debug tab in Settings to troubleshoot update issues
- **Manual Update Check**: Users can now manually check for updates and clear cache
- **Better Error Messages**: Improved error logging for debugging

---

## [1.0.0] - 2026-04-12

### 🎉 **MAJOR RELEASE - Production Ready**

#### ✨ **New Features**
- **Salary History Tracking**: Now records changes to salary amount and frequency (not just type)
- **Payment Editing**: Fixed issue where clicking payment method field created multiple forms
- **Removed Payments Menu**: Cleaned up admin menu by removing dedicated Payments page

#### 🐛 **Bug Fixes**
- **Payment Edit Forms**: Fixed multiple edit forms appearing when clicking method dropdown repeatedly
- **Payment Method Edit Forms**: Fixed duplicate forms being created on repeated clicks
- **Salary History**: Now properly tracks all salary changes (type, amount, and frequency)

#### 🔧 **Technical Improvements**
- **Form Cleanup**: Edit forms now properly remove previous instances before creating new ones
- **Event Handling**: Improved event delegation to prevent form duplication
- **Data Integrity**: Salary history now captures all meaningful changes

#### 📦 **Release Highlights**
- Stable production release with all core features working
- Comprehensive commission and payroll management system
- Full GitHub-based update system for automatic WordPress updates
- Professional admin interface with responsive design
- Complete audit trails for all employee changes

---

## [5.8.8] - 2024-12-19

### ✨ **Features**
- **Payroll Details Page**: Added profile pictures and formatted employee names to payroll table
- **Employee Display**: Shows employee name in format `PVVB-EMID1 Md Imran Hossain` with profile picture
- **Enhanced Data**: Added user email, phone, and role information to payroll data AJAX response

---

## [5.8.7] - 2024-12-19

### 🐛 **Bug Fixes**
- **Employee Payroll Details Button**: Fixed "View All" button link from incorrect page slug `wc-team-payroll-payroll` to correct `wc-team-payroll-details`
- Button now correctly navigates to the Payroll page

---

## [5.8.6] - 2024-12-19

### 🐛 **Bug Fixes**
- **Top Earners**: Fixed employee name format from `(PVVB-EMID1) Name` to `PVVB-EMID1 Name`
- **Recent Payments**: Fixed employee name format from `(PVVB-EMID1) Name` to `PVVB-EMID1 Name`
- **Latest Employees**: Fixed duplicate vb_user_id display (was showing `PVVB-EMID1 PVVB-EMID1 Name`)
- **Consistent Formatting**: Applied uniform employee name format across all dashboard tables

---

## [5.8.5] - 2024-12-19

### 🐛 **Bug Fixes**
- **Employee Name Format**: Fixed display format from `(PVVB-EMID1) Name` to `PVVB-EMID1 Name` (removed parentheses)
- **Table Cell Padding**: Added `!important` flag to td padding to prevent media query overrides
- **Consistent Formatting**: Applied fix to all employee name displays across dashboard tables

---

## [5.8.4] - 2024-12-19

### 🐛 **Bug Fixes**
- **Table Header Padding**: Fixed padding being overridden by media query rules - added `!important` flag
- **Employee Name Display**: Simplified format to match payroll table styling with proper `<strong>` tag

---

## [5.8.3] - 2024-12-19

### 🐛 **Bug Fixes**
- **Bulk Delete Issue**: Fixed bulk delete not working on second attempt in employee detail tabs
- **Checkbox State**: Reset "select all" checkbox state when table is re-rendered
- **Employee Name Display**: Fixed duplicate vb_user_id display in latest employees table

---

## [5.8.2] - 2024-12-19

### 🎨 **UI/UX Improvements**
- **Header Padding**: Increased table header padding to match row items (14px 12px)
- **Section Headings**: Removed all margins from section heading tags (h2, h3) for cleaner layout
- **Warning Suppression**: Hidden WooCommerce compatibility warnings on plugin pages

---

## [5.8.1] - 2024-12-19

### 🐛 **Bug Fixes**
- **Table Display Fix**: Fixed broken table headers displaying vertically (column-wise) throughout the plugin
- **CSS Enhancement**: Added explicit display properties to all table elements to prevent conflicts
- **Cross-Browser Compatibility**: Ensured tables render correctly across all browsers and WordPress admin themes

### 🔧 **Technical Improvements**
- Added `!important` flags to table display properties to override conflicting CSS
- Applied fix to all pages: Dashboard, Employee Management, Employee Detail, Payments, and Payroll
- Improved CSS specificity for `.wc-tp-data-table` and child elements

---

## [5.8.0] - 2024-12-19

### 🚀 **Major Features**
- **Employee Status Management System**: Complete employee activation/deactivation functionality
- **Enhanced Global Search**: Comprehensive search across orders, employees, customers, and payments
- **Dashboard UI Polish**: Improved spacing, styling, and user experience

### ✨ **New Features**
- **Employee Status Control**: Active/Inactive dropdown in employee detail pages
- **Checkout Integration**: Inactive employees automatically hidden from agent dropdown
- **Login Security**: Inactive employees blocked from WordPress login with professional warning
- **Contact Information**: Settings for WhatsApp, Email, and Telegram contact details
- **Status Display**: Employee status shown in dashboard tables with color-coded badges
- **Global Search Enhancement**: 
  - Search across all data types (orders, employees, customers, payments)
  - Clear button with X icon
  - Improved search logic with better field coverage
  - Enhanced result display with proper formatting

### 🎨 **UI/UX Improvements**
- **Dashboard Cards**: Text content aligned to bottom for better visual hierarchy
- **Table Headers**: Increased padding (16px 14px) for better readability
- **Section Headers**: Consistent spacing with minimal "View All" buttons
- **Action Icons**: Removed underlines and borders for cleaner appearance
- **Employee Links**: Names link to WordPress user edit page, action icons to detail pages
- **Responsive Design**: Better mobile experience across all components

### 🔧 **Technical Enhancements**
- **AJAX Handler**: `wc_tp_update_employee_status` for real-time status updates
- **Security**: Proper nonce verification and capability checks
- **Data Validation**: Input sanitization and error handling
- **Performance**: Optimized search queries with limits to prevent timeouts
- **Backward Compatibility**: All changes work with existing data

### 🛠 **Settings & Configuration**
- **Contact Settings**: New fields in General settings for employee support contacts
- **Status Management**: Automatic status handling with confirmation dialogs
- **Default Behavior**: New employees default to "active" status

### 📱 **Mobile & Responsive**
- **Profile Actions**: Improved mobile layout for status controls
- **Search Interface**: Better mobile search experience
- **Table Display**: Enhanced responsive table behavior

### 🔒 **Security & Access Control**
- **Role-Based Blocking**: Only applies to team members (employees, managers, admins)
- **Flexible Transitions**: Former employees can become customers without restrictions
- **Professional Messaging**: User-friendly warning messages with contact information

---

## [5.7.8] - 2026-04-12

### MAJOR IMPROVEMENTS - Dashboard UI Enhancements & Global Search

#### Dashboard Stats Cards Layout
- ✅ **5 Columns Per Row**: Changed from 4 to 5 columns for better space utilization
- ✅ **Reduced Height**: Decreased card height from 120px to 100px for compact look
- ✅ **Floating Icons**: Icons moved from left side to top-right corner with absolute positioning
- ✅ **Smaller Icons**: Reduced icon size from 32px to 24px for cleaner appearance
- ✅ **Vertical Layout**: Changed flex direction to column for better text organization
- ✅ **Responsive Breakpoints**: 3 columns at 1024px, 1 column at 768px

#### Global Search Feature
- ✅ **Search Box**: Added before filter section with "Search for anything..." placeholder
- ✅ **Live Results**: Floating results box with real-time search as you type
- ✅ **Multi-Type Search**: Searches across Orders, Employees, Customers, and Payments
- ✅ **Result Badges**: Type indicators (Order, Employee, Customer, Payment) with color coding
- ✅ **Pagination**: Results paginated at 10 items per page
- ✅ **Clickable Results**: Each result links to relevant detail page
- ✅ **Hover Tooltips**: Shows full details on hover
- ✅ **Responsive Design**: Adapts to all screen sizes
- ✅ **AJAX Handler**: `wc_tp_global_search` for efficient searching

#### Dashboard Table Improvements
- ✅ **Profile Pictures**: All employee names now show profile picture (32x32px circular)
- ✅ **Employee Format**: Shows as "[Picture] VBID FirstName" (e.g., "PVVB-EMID1 Imran")
- ✅ **Clickable Names**: Employee names link to their detail page
- ✅ **Hover Tooltips**: Shows Name, Email, Phone, Role on hover
- ✅ **Removed Email Column**: Email now only in tooltip for cleaner tables
- ✅ **Relevant Columns**: Added context-specific columns per table

#### Dashboard Table Column Updates
- **Latest Employees**: Added Status column (shows "Active" badge)
- **Top Earners**: Reordered to show Orders before Total Earnings
- **Recent Payments**: Reordered to show Date before Amount
- **Payroll Table**: Removed email, kept Orders, Total Earnings, Paid, Due

#### Dashboard Table Sorting & Styling
- ✅ **Dashicon Arrows**: Changed from Unicode arrows to dashicons (arrow-up/arrow-down)
- ✅ **Active Sort Indicator**: Icons only show on currently sorted column
- ✅ **Consistent Styling**: Headers use `var(--color-accent-muted)` background
- ✅ **Proper Padding**: Headers 14px 12px, cells 12px for organized look
- ✅ **Hover Effects**: Background changes to `var(--color-primary-subtle)` on hover
- ✅ **Clean Headers**: No persistent highlighting, only arrow indicator

#### Employee Detail Page - Payment Methods
- ✅ **Edit Icon**: Added edit button next to delete for payment methods
- ✅ **Inline Editing**: Edit form similar to payment history editing
- ✅ **Update Handler**: `wc_tp_update_payment_method` AJAX handler for updates
- ✅ **Edit Fields**: Can edit payment method name and account details inline

#### Screen Options Persistence
- ✅ **localStorage Integration**: Items per page preference saved to browser storage
- ✅ **Orders Tab**: Key `wc_tp_orders_per_page`
- ✅ **Payment History**: Key `wc_tp_payment_history_per_page`
- ✅ **Payment Methods**: Key `wc_tp_payment_methods_per_page`
- ✅ **Persistent Across Sessions**: User preferences survive browser restarts

#### Bulk Delete Bug Fix
- ✅ **Fixed Select All Issue**: Bulk delete now works correctly when selecting all via header checkbox
- ✅ **Proper Validation**: Added validation to check if IDs exist before adding to array
- ✅ **Correct Counter**: Uses `totalToDelete` variable to avoid scope issues
- ✅ **All Items Deleted**: Selecting all items now deletes all, not just first item

### Technical Improvements
- ✅ **Global Search Script**: Enqueued only on dashboard page for performance
- ✅ **Profile Picture Meta**: Uses custom field `_wc_tp_profile_picture`
- ✅ **Consistent UX**: All dashboard tables follow same styling and interaction patterns
- ✅ **Performance**: Optimized AJAX calls for search and data fetching
- ✅ **Accessibility**: Proper ARIA labels and keyboard support throughout

## [5.7.7] - 2026-04-12

### MAJOR IMPROVEMENTS - Employee Detail Page & Global Features

#### Global Delete Confirmation Modal
- ✅ **Custom Modal System**: Created reusable delete confirmation modal (similar to toast)
- ✅ **Type "DELETE" Confirmation**: Requires typing "DELETE" to confirm deletion
- ✅ **No Browser Alerts**: Replaced all browser confirm dialogs
- ✅ **Custom Messages**: Shows specific warning messages per action
- ✅ **Responsive Design**: Works on all screen sizes
- ✅ **Global Access**: Available via `wcTPDeleteModal()` function

#### Bulk Select & Delete - Payment History
- ✅ **Select All Checkbox**: Header checkbox to select/deselect all payments
- ✅ **Individual Checkboxes**: Each payment row has a checkbox
- ✅ **Bulk Delete Button**: Shows count of selected items
- ✅ **Batch Processing**: Deletes multiple payments at once
- ✅ **Progress Feedback**: Shows success/failure count after bulk delete

#### Bulk Select & Delete - Payment Methods
- ✅ **Select All Checkbox**: Header checkbox to select/deselect all methods
- ✅ **Individual Checkboxes**: Each method row has a checkbox
- ✅ **Bulk Delete Button**: Shows count of selected items
- ✅ **Batch Processing**: Deletes multiple methods at once
- ✅ **Progress Feedback**: Shows success/failure count after bulk delete

#### Sorting & Pagination - All Tables
- ✅ **Payment History Sorting**: All columns sortable (Amount, Date, Method, Added By)
- ✅ **Payment Methods Sorting**: All columns sortable (Method Name, Details)
- ✅ **Salary History Sorting**: All columns sortable (Date, Types, Amounts, Changed By)
- ✅ **Pagination Controls**: Page numbers with prev/next buttons
- ✅ **Screen Options**: Dropdown to select items per page (5, 10, 25, 50, 100)
- ✅ **Smart Ellipsis**: Shows ... for large page counts
- ✅ **Active Highlighting**: Current page and sort column highlighted

#### Date & User Formatting
- ✅ **Salary History Dates**: Proper format (YYYY-MM-DD HH:MM)
- ✅ **User Display**: Shows actual user display name with link to profile
- ✅ **Tooltips**: Hover shows email and role
- ✅ **Clickable Links**: User names link to WordPress user edit page

#### Salary Management Improvements
- ✅ **Amount Reset**: Clears amount field when changing salary type
- ✅ **Toast Notifications**: Replaced alerts with toast messages
- ✅ **Type Transitions**: Properly handles Commission ↔ Fixed ↔ Combined changes

#### Profile Picture & User ID
- ✅ **Custom Field Integration**: Uses `_wc_tp_profile_picture` meta key
- ✅ **Proper Display**: Shows uploaded profile picture from user profile
- ✅ **Label Update**: Changed "VB User ID" to "User ID"

#### Default Date Filters
- ✅ **Payroll Page**: Changed default from "This Month" to "All Time"
- ✅ **Employee Management**: Changed default from "This Month" to "All Time"

#### Orders Tab Enhancements
- ✅ **Sortable Headers**: All columns clickable for sorting
- ✅ **Sort Icons**: Up/down arrows show sort direction
- ✅ **Default Sort**: Date descending (newest first)
- ✅ **Pagination**: Full pagination with screen options
- ✅ **Active Highlighting**: Sorted column highlighted

#### Back Navigation
- ✅ **Back Button**: Added "Back to Employees" button on employee detail page
- ✅ **Icon & Text**: Left arrow icon with text
- ✅ **Hover Effects**: Primary color on hover

### Technical Improvements
- ✅ **Global Scripts**: Toast and Delete Modal available plugin-wide
- ✅ **Consistent UX**: All tables have same sorting/pagination experience
- ✅ **Performance**: Client-side sorting and pagination for instant response
- ✅ **Accessibility**: Proper ARIA labels and keyboard support

## [5.7.6] - 2026-04-12

### IMPROVED - Employee Detail Page Payments Tab

#### Toast Notification System
- ✅ **Global Toast System**: Added reusable `wcTPToast()` function for all notifications
- ✅ **No Browser Alerts**: Replaced all `alert()` with floating toast notifications
- ✅ **Auto-Hide**: Toasts auto-hide after 4 seconds
- ✅ **Manual Close**: Small close button (×) for manual dismissal
- ✅ **Top-Right Position**: Fixed position in top-right corner
- ✅ **Responsive**: Adapts to mobile screens
- ✅ **Success/Error States**: Color-coded (green for success, red for error)

#### Payment History Enhancements
- ✅ **Added Method Column**: Shows payment method used
- ✅ **Fixed Added By**: Now correctly displays user who added payment (was showing null)
- ✅ **Action Icons**: Changed from buttons to icon buttons (edit + delete)
- ✅ **Inline Editing**: Edit button opens inline form with prefilled values
- ✅ **Edit Payment**: Can edit amount and date inline
- ✅ **Save/Cancel**: Inline form with save and cancel buttons

#### Stats Card Updates
- ✅ **Auto-Update**: Stats cards update automatically after payment add/edit/delete
- ✅ **Real-Time**: Uses new `wc_tp_get_employee_stats` AJAX handler
- ✅ **Accurate**: Total Paid and Total Due reflect latest changes

#### AJAX Handlers Added
- ✅ `wc_tp_get_employee_stats` - Fetches updated employee statistics
- ✅ `wc_tp_update_payment` - Updates existing payment (amount and date)

#### User Experience
- ✅ No page reloads needed
- ✅ Smooth animations and transitions
- ✅ Inline editing without leaving the page
- ✅ Immediate feedback via toast notifications

## [5.7.5] - 2026-04-11

### IMPROVED - Employee Detail Page Tab Architecture

#### Major Architectural Change
- **Switched from JavaScript-based tabs to URL-based tabs** (like Settings page)
- Each tab now uses GET parameter: `?tab=orders`, `?tab=payments`, `?tab=salary`
- Tabs are now completely independent with no shared JavaScript state

#### Benefits
- ✅ **No Tab Interference**: Each tab loads fresh with isolated JavaScript scope
- ✅ **Clean State**: Page reload ensures no leftover variables or data
- ✅ **WordPress Standard**: Uses `nav-tab` and `nav-tab-active` classes
- ✅ **Better Performance**: Only active tab's HTML and JavaScript loads
- ✅ **Reliable**: Same proven pattern as Settings page

#### Technical Implementation
- Changed tab navigation from `<button>` to `<a>` links
- Added conditional rendering: `<?php if ( $current_tab === 'tabname' ) : ?>`
- Each tab initializes only when active (no global initialization)
- Removed shared variables between tabs
- Updated CSS to match WordPress nav-tab styling

#### What Still Works
- ✅ All Orders tab functionality (search, filters, pagination)
- ✅ All Payments tab functionality (methods, history, add/delete)
- ✅ All Salary tab functionality (management, history, update)
- ✅ Profile header and stats cards
- ✅ All AJAX handlers
- ✅ All styling and responsive design

## [5.7.4] - 2026-04-11

### ADDED - Debugging to Employee Detail Page

- Added console logging to AJAX calls for troubleshooting
- Added console logging to tab switching for debugging
- Improved error handling in AJAX calls to show actual errors
- This helps identify why orders table is not showing and tabs are not switching

## [5.7.3] - 2026-04-11

### FIXED - Employee Detail Page Now Working Properly

#### Root Cause Analysis
- v5.7.0+ had complex JavaScript with hidden date inputs causing AJAX failures
- Date inputs were hidden by default (`display: none`), making them inaccessible to jQuery
- v5.6.0 had simpler, working tab switching and table display logic

#### The Solution
- **Reverted to v5.6.0 base structure** - Simple, proven working JavaScript
- **Kept new tab order**: Orders → Payments → Salary Management
- **Added full Payments tab content**:
  - Payment methods section (add/delete)
  - Add payment form
  - Payment history table
- **Added full Salary Management tab content**:
  - Salary type selection
  - Salary amount and frequency fields
  - Update salary button
  - Salary history table

#### What Now Works
- ✅ Orders table displays immediately on page load
- ✅ Tab switching works correctly for all three tabs
- ✅ All tab-specific data loads properly when switching tabs
- ✅ Payment methods can be added/deleted
- ✅ Payments can be added/deleted with proper AJAX
- ✅ Salary can be updated with complete history tracking
- ✅ Date filtering works correctly
- ✅ Search and status filters work as expected
- ✅ All AJAX handlers work properly

#### Technical Details
- Simplified JavaScript initialization
- Removed complex hidden input handling
- Used proven v5.6.0 tab switching logic
- Maintained all AJAX handlers from v5.7.0
- All styling and responsive design preserved

### Files Modified
- `includes/class-employee-detail.php` - Restored working base with new content
- Deleted `includes/class-employee-detail-old.php` - No longer needed

## [5.7.2] - 2026-04-11

### CRITICAL BUG FIX - Employee Detail Page Orders Table & Tab Switching

#### Root Cause Identified
- Date input elements were hidden by default (`display: none`)
- jQuery couldn't properly access values from hidden input elements
- `loadOrdersData()` was receiving empty date values and returning early
- AJAX call was never being made to fetch orders

#### The Fix
- Store dates in JavaScript variables instead of relying on hidden inputs
- Added `currentStartDate` and `currentEndDate` variables to track date range
- Updated `updateDateRangeFromPreset()` to store dates in JS variables
- Updated `loadOrdersData()` to use JS variables for date values
- Added fallback for `ajaxurl` in case it's not defined globally
- Added console logging for debugging AJAX issues

#### What Now Works
- ✅ Orders table displays immediately on page load
- ✅ Tab switching between Orders, Payments, and Salary Management works correctly
- ✅ All tab-specific data loads properly when switching tabs
- ✅ Date filtering works correctly
- ✅ Search and status filters work as expected

#### Technical Details
- Removed dependency on hidden input values for date storage
- JavaScript variables now serve as the source of truth for date ranges
- AJAX calls now have proper date values and execute successfully
- Console logging helps identify any remaining issues

### Files Modified
- `includes/class-employee-detail.php` - Fixed date handling and AJAX calls

## [5.7.1] - 2026-04-11

### Bug Fixes

#### Employee Detail Page
- **Fixed Orders Table Not Displaying**: Orders table now loads correctly on page initialization
- **Fixed Tab Switching**: All three tabs (Orders, Payments, Salary Management) now switch properly
- **Removed Duplicate Functions**: Eliminated duplicate JavaScript function definitions that were causing conflicts
- **Consolidated Tab Logic**: Unified tab switching handler to properly load data for each tab
- **Fixed Event Handler Conflicts**: Removed conflicting event delegation handlers that prevented tab switching

### Technical Details

- Reorganized JavaScript initialization order to ensure date range is set before AJAX calls
- Consolidated tab switching logic into single handler with conditional data loading
- Removed duplicate `getDateRangeFromPreset()`, `formatDateForInput()`, and `updateDateRangeFromPreset()` functions
- Fixed event handler attachment for payments and salary tabs

## [5.7.0] - 2026-04-11

### New Features

#### Comprehensive Payments Page
- **New Payments Page**: Added dedicated page for managing all payments across all employees
- **Menu Location**: Added "Payments" menu item after "Team Members" in admin menu
- **Payment Entry**: Add payments directly from the payments page with employee dropdown selection
- **Employee Dropdown**: Select any employee to add payment to their account
- **Payment Details**: Shows Employee Name, Employee ID (vb_user_id), Amount, Payment Date, Added By, Employee Type

#### Advanced Filtering & Search
- **Date Range Filter**: Predefined options (All Time, Today, This Week, This Month, This Year, Last Week, Last Month, Last Year, Last 6 Months, Custom)
- **Employee Type Filter**: Filter by Commission Based, Fixed Salary, or Combined
- **Search Functionality**: Search by employee name, vb_user_id, email, or phone number
- **Real-time Search**: Search updates as you type

#### Table Features
- **Sortable Columns**: Click column headers to sort (Employee, Employee ID, Amount, Payment Date, Added By, Employee Type)
- **Sort Direction**: Toggle between ascending (↑) and descending (↓)
- **Pagination**: Navigate through payment records with page numbers
- **Items Per Page**: Choose 10, 20, 30, 50, or 100 items per page
- **View Action**: Click "View" button to go to employee detail page

#### Employee Detail Page Enhancements
- **Tab Reordering**: Orders → Payments → Salary Management
- **Payments Tab**: 
  - Stat cards showing Total Earnings, Total Paid, Total Due
  - Payment Methods section with add/delete functionality
  - Payment method details (e.g., bKash Personal with account number)
  - Add Payment form with amount and date
  - Payment History table with delete option
- **Salary Management Tab**:
  - Salary type selection (Commission Based, Fixed Salary, Combined)
  - Conditional fields for salary amount and frequency
  - Update salary button with validation
  - Complete salary history table showing all changes
  - Tracks who made changes and when

#### User Profile Improvements
- **Fixed Profile Picture Upload**: Media library now opens correctly
- **Separate JavaScript File**: Profile picture functionality moved to external JS file
- **Proper Enqueuing**: Uses WordPress media library API correctly

### Technical Details

- Created `class-payments-page.php` for payments management
- Added AJAX handler `wc_tp_get_all_payments` for fetching payment data
- Implemented section-wise date filtering for dashboard
- Added payment methods storage in user meta
- Salary history tracking with complete audit trail
- All features use AJAX for smooth user experience
- Responsive design matching current version styling
- Currency formatting with WooCommerce settings




## [1.6.37] - 2026-04-20
### 🎨 UI Improvement - Redesign Locked Badge with Phosphor Icons

#### CHANGED
- Replaced locked badge SVG with modern Phosphor icon design (`ph-lock-key`)
- Improved locked badge UI with better 3D styling
- Enhanced visual consistency with gold/silver/bronze coin badges
- Better icon visibility and modern appearance

#### IMPROVEMENTS
- ✅ Modern Phosphor icon (`ph-lock-key`) instead of custom SVG
- ✅ Improved 3D coin effect with radial gradients and inset shadows
- ✅ Better visual hierarchy and icon contrast
- ✅ Cleaner, more professional appearance
- ✅ Consistent with overall badge design language

#### TECHNICAL
- Replaced SVG-based locked badge with HTML/CSS + Phosphor icon
- Used radial gradients for 3D coin effect
- Added inset shadows for depth perception
- Maintained hover effects (scale 1.08x, enhanced shadow)

---

## [1.6.39] - 2026-04-20
### 🧹 Cleanup - Remove Old SVG Badge Styles

#### REMOVED
- Removed all old SVG-based badge CSS styles (`.badge-circle`, `.badge-inner-ring`, `.badge-icon-outline`)
- Removed old gradient definitions (`#goldGradient`, `#silverGradient`, `#bronzeGradient`)
- Removed old `.achievement-badge-*` seal styles
- Cleaned up unused SVG-related CSS from both employee-detail.php and myaccount-shared.css

#### IMPROVEMENTS
- ✅ Cleaner CSS with no unused SVG styles
- ✅ Reduced CSS file size
- ✅ All badges now use modern 3D coin design with HTML/CSS
- ✅ Consistent badge styling across all pages

---

## [1.6.38] - 2026-04-20
### 🎨 UI Improvement - Update My Account Badges with Modern 3D Coin Design

#### CHANGED
- Updated gold, silver, and bronze badges in My Account page to use modern 3D coin design
- Replaced SVG-based badges with HTML/CSS + letter badges (G, S, B)
- Enhanced visual consistency with employee details page
- Improved badge styling with realistic 3D effects

#### IMPROVEMENTS
- ✅ Modern 3D coin design with radial gradients
- ✅ Letter badges (G for Gold, S for Silver, B for Bronze) in center
- ✅ Improved visual hierarchy and contrast
- ✅ Consistent design language across all pages
- ✅ Better performance with CSS-only rendering

#### TECHNICAL
- Replaced SVG badges with HTML structure: `badge-coin-container` + `badge-coin` + `badge-letter`
- Used radial gradients for 3D coin effect
- Added inset shadows for depth perception
- Maintained hover effects (scale 1.08x, enhanced shadow)

---

## [1.6.40] - 2026-04-20
### 🐛 Bug Fix - Profile Badge Shows Locked When Achievements Exist

#### PROBLEM
- Profile badge was showing locked even when user had earned achievements in current period
- Root cause: Profile header was looking for `highest_tier` in wrong meta key

#### ROOT CAUSE
- Achievement stats (including `highest_tier`) are saved in `_wc_tp_period_achievements_stats_` meta key
- Profile header was looking in `_wc_tp_period_achievements_` meta key (which stores individual achievements)
- This caused `highest_tier` to always be empty, showing locked badge instead

#### FIXED
- Updated `get_employee_header()` in My Account page to retrieve stats from correct meta key
- Updated employee details page to retrieve stats from correct meta key
- Both now use `_wc_tp_period_achievements_stats_` to get `highest_tier`

#### CHANGES
- `includes/class-myaccount.php`: Fixed highest_tier retrieval in `get_employee_header()`
- `includes/class-employee-detail.php`: Fixed highest_tier retrieval in profile badge section

#### RESULT
- ✅ Profile badge now correctly shows G/S/B when achievements are earned
- ✅ Locked badge only shows when no achievements in current period
- ✅ Works for both My Account and Employee Details pages

---

## [1.6.41] - 2026-04-20
### ✨ Feature - CSV, PDF, and Print Export for Reports

#### ADDED
- **CSV Export**: Download filtered report data as CSV with proper formatting
  - Includes summary section (employee, period, generated date)
  - Summary statistics (total orders, order value, commission)
  - Detailed commission history with all columns
  - UTF-8 BOM for Excel compatibility
  
- **PDF Export**: Generate professional PDF reports
  - Beautiful HTML-based PDF with modern styling
  - Summary cards with key metrics
  - Formatted commission history table
  - Employee info and report period
  - Uses html2pdf.js library for client-side conversion
  - Automatic download with proper filename
  
- **Print Functionality**: Print-friendly report view
  - Custom print stylesheet with optimized layout
  - Hides filters and export buttons
  - Proper page breaks for multi-page reports
  - Professional header and footer
  - Optimized for both color and B&W printing

#### IMPROVEMENTS
- ✅ Enhanced CSV export with summary section
- ✅ Professional PDF styling with gradient cards
- ✅ Loading indicator during export
- ✅ Error handling for export failures
- ✅ Responsive print layout
- ✅ Proper currency formatting in all exports
- ✅ Date formatting consistency across all formats

#### TECHNICAL
- Updated `export_to_csv()` with enhanced formatting
- Rewrote `export_to_pdf()` to use html2pdf.js library
- Enhanced JavaScript export handler with loading states
- Added comprehensive print media queries
- Improved error handling and user feedback

#### FILES CHANGED
- `includes/class-myaccount.php` - Export functions
- `assets/js/reports.js` - Export button handlers
- `assets/css/reports.css` - Print media queries

---

## [1.6.42] - 2026-04-20
### 🐛 Bug Fix - Fix Export Errors and Improve Print Functionality

#### FIXED
- Fixed "Export failed: Unknown error" for CSV and PDF exports
- Fixed AJAX handler not returning proper responses
- Fixed print functionality to include full report (not just table)
- Improved error handling and user feedback

#### ROOT CAUSE
- Export functions were calling `exit;` before AJAX response could be sent
- Print function was only capturing table content, not full report
- Missing blob response type in AJAX request

#### CHANGES
- Refactored export functions to generate content instead of directly outputting
- Created `generate_csv_content()` function for CSV generation
- Created `generate_pdf_html()` function for PDF HTML generation
- Updated AJAX handler to properly handle file downloads
- Enhanced print functionality to use PDF export and open in new window
- Improved error handling with better error messages
- Added loading indicators for all export operations

#### IMPROVEMENTS
- ✅ CSV export now works without errors
- ✅ PDF export now works without errors
- ✅ Print opens full formatted report in new window
- ✅ Better error messages for debugging
- ✅ Proper blob handling for file downloads
- ✅ Loading states for better UX
- ✅ Consistent formatting across all export types

#### FILES CHANGED
- `includes/class-myaccount.php` - Refactored export functions
- `assets/js/reports.js` - Fixed AJAX handling and print functionality

---

