# WooCommerce Team Payroll & Commission System
## Complete Documentation & User Guide

> **Version:** 1.0.0  
> **Author:** Imran  
> **Last Updated:** April 2026

---

## 📖 About This Documentation

This comprehensive guide will help you understand, install, configure, and use the WooCommerce Team Payroll & Commission System plugin. Whether you're an administrator setting up the system or an employee tracking your earnings, this documentation has everything you need.

---

## 🎯 What is WooCommerce Team Payroll?

WooCommerce Team Payroll & Commission System is a powerful WordPress plugin designed to manage team-based sales commissions, employee salaries, and performance tracking for WooCommerce stores. It's perfect for businesses with sales teams, agents, and order processors who need flexible compensation models.

### **Key Highlights**

- ✅ **Flexible Commission System** - Split commissions between agents and processors
- ✅ **Multiple Salary Types** - Commission-based, fixed salary, or combined
- ✅ **Automatic Salary Transfers** - Daily, weekly, or monthly automation
- ✅ **Performance Tracking** - Goals, achievements, and leaderboards
- ✅ **Employee Dashboard** - Beautiful My Account pages for employees
- ✅ **Order Editor** - Modify orders with automatic commission recalculation
- ✅ **Payment Management** - Track payments with complete history
- ✅ **Real-time Reports** - Comprehensive analytics and insights

---

## 📋 Table of Contents

1. [System Requirements](#-system-requirements)
2. [Installation Guide](#-installation-guide)
3. [Initial Setup](#-initial-setup)
4. [Understanding Commission System](#-understanding-commission-system)
5. [Salary Management](#-salary-management)
6. [Employee Management](#-employee-management)
7. [Checkout Integration](#-checkout-integration)
8. [Frontend Features](#-frontend-features)
9. [Performance Tracking](#-performance-tracking)
10. [Order Editor](#-order-editor)
11. [Payment Management](#-payment-management)
12. [Reports & Analytics](#-reports--analytics)
13. [Customization](#-customization)
14. [Troubleshooting](#-troubleshooting)
15. [Developer Guide](#-developer-guide)
16. [FAQ](#-faq)
17. [Support & Resources](#-support--resources)

---

## 🔧 System Requirements

Before installing the plugin, ensure your system meets these requirements:

### **Minimum Requirements**

| Component | Requirement |
|-----------|-------------|
| **WordPress** | Version 5.0 or higher |
| **WooCommerce** | Version 5.0 or higher |
| **PHP** | Version 7.2 or higher |
| **MySQL** | Version 5.6 or higher |
| **WordPress Memory** | 128 MB minimum (256 MB recommended) |

### **Recommended Environment**

- PHP 7.4 or 8.0+
- MySQL 5.7+ or MariaDB 10.3+
- HTTPS enabled
- WordPress Cron enabled

### **Optional Dependencies**

- **Advanced Custom Fields (ACF)** or **Smart Custom Fields (SCF)** - For custom product fields
- **WP Crontrol** - For debugging scheduled tasks

### **Compatibility**

✅ **WooCommerce HPOS** (High-Performance Order Storage)  
✅ **WordPress Multisite**  
✅ **Modern PHP versions** (7.2 - 8.x)  
✅ **All major WordPress themes**  
✅ **WooCommerce 10.7.0** tested and compatible

---

## 🚀 Installation Guide

### **Method 1: Automatic Updates (Recommended for Existing Users)**

Once installed, the plugin automatically checks for updates from GitHub:

1. Updates appear in `Dashboard > Updates` like any WordPress plugin
2. Click `Update Now` to install the latest version
3. The plugin automatically handles folder naming and updates
4. No manual download or configuration needed!

### **Method 2: Manual Installation (First-Time Installation)**

1. **Download the Plugin**
   - Visit [GitHub Releases](https://github.com/imranduzzlo/woocommerce-team-payroll/releases)
   - Download the latest version ZIP file (e.g., `woocommerce-team-payroll-1.0.6.zip`)

2. **Extract and Rename** ⚠️ **IMPORTANT STEP**
   - Extract the ZIP file on your computer
   - You'll see a folder named `woocommerce-team-payroll-1.0.6` (with version number)
   - **Rename** the folder to `woocommerce-team-payroll` (remove the version number)
   - This step is crucial for the plugin to work correctly!

3. **Upload to WordPress**
   - Login to your WordPress admin panel
   - Go to `Plugins > Add New`
   - Click `Upload Plugin` button
   - **Option A**: Re-zip the renamed folder and upload it
   - **Option B**: Use FTP to upload the renamed folder to `/wp-content/plugins/`

4. **Activate the Plugin**
   - After installation completes, click `Activate Plugin`
   - You'll see "WooCommerce Team Payroll" in your admin menu

5. **Flush Permalinks** (Important!)
   - Go to `Settings > Permalinks`
   - Click `Save Changes` (no need to modify anything)
   - This ensures My Account endpoints work correctly

> **⚠️ Why Rename the Folder?**  
> GitHub automatically adds the version number to downloaded releases (e.g., `woocommerce-team-payroll-1.0.6`).  
> WordPress expects the folder to be named `woocommerce-team-payroll` (without version).  
> If you don't rename it, the plugin won't activate correctly.  
> **Note**: Automatic updates handle this renaming automatically!

### **Method 3: FTP Installation**

1. **Download and extract** the ZIP file on your computer
2. **Rename** the folder from `woocommerce-team-payroll-x.x.x` to `woocommerce-team-payroll`
3. **Upload via FTP** to `/wp-content/plugins/woocommerce-team-payroll/`
4. **Activate** the plugin from `Plugins` menu in WordPress admin
5. **Flush permalinks** as described above

### **Post-Installation Checklist**

After activation, you should see:

- ✅ New admin menu: **Team Payroll**
- ✅ Settings page: **Team Payroll > Settings**
- ✅ Dashboard: **Team Payroll > Dashboard**
- ✅ Employee management: **Team Payroll > Team Members**
- ✅ My Account tabs for employees (if logged in as employee)

### **Automatic Updates**

The plugin supports automatic updates from GitHub:

- Updates appear in WordPress admin like native plugins
- Click "Update Now" to install the latest version
- Changelog is displayed before updating
- Folder renaming is handled automatically during updates
- No need to manually download and upload

---

## ⚙️ Initial Setup

Follow these steps to configure the plugin for first-time use:

### **Step 1: General Settings**

Navigate to `Team Payroll > Settings > General`

#### **Employee ID Configuration**

```
Employee ID Prefix: PVVB-EMID
```

- This prefix is used for auto-generated employee IDs
- Example: PVVB-EMID001, PVVB-EMID002, etc.
- Stored in user meta as `vb_user_id`
- Can be customized to match your company naming convention

#### **Enable/Disable Features**

- ☑️ **Enable Breakdown Table** - Show commission breakdown on order details
- ☑️ **Enable My Account Integration** - Add earnings tabs to My Account page
- ☑️ **Enable Shortcodes** - Enable shortcode system for displaying earnings

#### **Contact Information for Inactive Employees**

Configure contact details shown to inactive employees when they try to login:

- **WhatsApp Number** - Without + or country code (e.g., 1234567890)
- **Contact Email** - Support email address
- **Telegram Username** - Without @ symbol

### **Step 2: Commission Settings**

Navigate to `Team Payroll > Settings > Commission`

#### **Set Commission Split**

```
Agent Commission: 70%
Processor Commission: 30%
```

**How it works:**
- When agent and processor are the same person → 100% commission
- When agent and processor are different → Split by configured percentages
- Only commission-eligible employees receive commission

**Example:**
```
Order Total: $1000
Product Commission Rate: 10%
Total Commission: $100

If Agent ≠ Processor:
- Agent gets: $70 (70%)
- Processor gets: $30 (30%)

If Agent = Processor:
- Agent gets: $100 (100%)
```

### **Step 3: WooCommerce Integration**

Navigate to `Team Payroll > Settings > WooCommerce`

#### **Checkout Field Mapping**

```
Agent Field Name: order_agent_name
Processor Field Name: _processor_user_id
```

These fields are used to capture agent/processor information at checkout.

#### **Agent User Roles**

Select which user roles can be agents:

- ☑️ Shop Employee
- ☑️ Shop Manager
- ☑️ Administrator

Only users with these roles will appear in the agent dropdown at checkout.

#### **Product Commission Field**

```
Commission Field Name: team_commission
```

This is the custom field name used to store commission percentage on products.

**Setting Product Commission:**
1. Edit a product in WooCommerce
2. Find the "Team Commission" field
3. Enter percentage (e.g., 10 for 10%)
4. Save the product

#### **Commission Calculation Statuses**

Select which order statuses trigger commission calculation:

- ☑️ Completed
- ☑️ Processing

Commission is automatically calculated when orders reach these statuses.

### **Step 4: User Roles Configuration**

Navigate to `Team Payroll > Settings > User Roles`

#### **Employee Roles**

Select which WordPress roles are considered employees:

- ☑️ Shop Employee (recommended)
- ☑️ Shop Manager
- ☑️ Administrator

Users with these roles will:
- Appear in employee lists
- Have access to My Account earnings tabs
- Be eligible for commission (if salary type allows)
- Appear in agent dropdown at checkout

### **Step 5: Verify Setup**

After completing the initial setup:

1. **Test Permalinks**
   - Go to `Settings > Permalinks`
   - Click "Save Changes"
   - Visit My Account page as an employee
   - Verify new tabs appear (Salary Details, My Earnings, etc.)

2. **Create Test Employee**
   - Go to `Users > Add New`
   - Create user with "Shop Employee" role
   - Go to `Team Payroll > Team Members`
   - Verify employee appears in the list

3. **Test Checkout**
   - Visit your store's checkout page
   - Verify agent dropdown appears
   - Verify it shows active employees

4. **Test Commission**
   - Set commission rate on a product
   - Place a test order
   - Change order status to "Completed"
   - Check order meta for commission data

---

## 💰 Understanding Commission System

The commission system is the core feature of this plugin. Understanding how it works is crucial for proper setup.

### **Commission Calculation Flow**

```
1. Customer places order
   ↓
2. Order reaches configured status (e.g., Completed)
   ↓
3. Plugin checks for agent and processor
   ↓
4. Calculates commission based on product rates
   ↓
5. Applies agent/processor split
   ↓
6. Checks salary type eligibility
   ↓
7. Stores commission data in order meta
```

### **Commission Components**

#### **1. Product Commission Rate**

Each product can have its own commission rate:

- Set as percentage (e.g., 10 = 10%)
- Stored in product meta field (default: `team_commission`)
- Applied to product line total
- Can be different for each product

**Example:**
```
Product A: $100 × 10% = $10 commission
Product B: $200 × 15% = $30 commission
Total Order Commission: $40
```

#### **2. Agent & Processor Roles**

**Agent (Primary):**
- Person who brought the customer
- Selected at checkout or auto-assigned
- Gets larger commission share (default 70%)

**Processor:**
- Person who processes/fulfills the order
- Usually the logged-in user at checkout
- Gets smaller commission share (default 30%)

#### **3. Commission Split Logic**

**Scenario 1: Single User (Agent = Processor)**
```
Agent: John
Processor: John
Commission: $100

Result:
- John gets: $100 (100%)
- No split applied
```

**Scenario 2: Two Different Users**
```
Agent: John
Processor: Sarah
Commission: $100
Split: 70/30

Result:
- John gets: $70 (70%)
- Sarah gets: $30 (30%)
```

**Scenario 3: No Agent Selected**
```
Agent: None
Processor: John (logged in)
Commission: $100

Result:
- John becomes agent
- John gets: $100 (100%)
```

### **Salary-Aware Commission**

Commission eligibility depends on employee salary type:

| Salary Type | Commission Eligible? | Explanation |
|-------------|---------------------|-------------|
| **Commission-Based** | ✅ Yes | Earnings from commissions only |
| **Fixed Salary** | ❌ No | Regular salary, no commission |
| **Combined** | ✅ Yes | Base salary + commission |

**Example with Mixed Salary Types:**
```
Order Commission: $100
Agent (John): Commission-based ✅
Processor (Sarah): Fixed salary ❌

Result:
- John gets: $70 (his 70% share)
- Sarah gets: $0 (fixed salary, no commission)
- $30 vanishes (not redistributed)
```

### **Commission Calculation Triggers**

Commission is calculated when:

1. **Order Status Changes**
   - Order reaches configured status (e.g., Completed, Processing)
   - Automatic calculation via WooCommerce hooks

2. **Order is Edited**
   - Items added/removed
   - Quantities changed
   - Prices modified
   - Automatic recalculation

3. **Manual Recalculation**
   - Admin can trigger via order editor
   - Useful for fixing issues

### **Refunded Orders**

When an order is refunded:

- Commission is set to $0
- Previous commission data is preserved
- Employees see refunded status in their orders list
- Affects earnings calculations

### **Commission Data Storage**

Commission data is stored in order meta as `_commission_data`:

```php
array(
    'order_id' => 123,
    'agent_id' => 5,
    'processor_id' => 8,
    'total_commission' => 100.00,
    'agent_earnings' => 70.00,
    'processor_earnings' => 30.00,
    'agent_order_value' => 700.00,
    'processor_order_value' => 300.00,
    'order_total' => 1000.00,
    'calculated_at' => '2026-04-25 10:30:00',
    'items' => array(
        array(
            'product_id' => 456,
            'product_name' => 'Product A',
            'line_total' => 500.00,
            'commission_rate' => 10,
            'commission' => 50.00
        )
    )
)
```

### **Viewing Commission Data**

**For Administrators:**
- Order edit page → Commission breakdown table
- Dashboard → Payroll summary
- Reports → Detailed analytics

**For Employees:**
- My Account → My Orders (Commission)
- My Account → My Earnings
- My Account → Reports

---

## 💵 Salary Management

The plugin supports three different salary types to accommodate various compensation models.

### **Three Salary Types**

#### **1. Commission-Based**

**Best for:** Sales agents, freelancers

**How it works:**
- Earnings come entirely from order commissions
- No fixed salary
- Income varies based on performance
- Highly motivating for sales-driven roles

**Example:**
```
Month 1: 10 orders × $50 commission = $500
Month 2: 20 orders × $50 commission = $1000
Month 3: 5 orders × $50 commission = $250
```

#### **2. Fixed Salary**

**Best for:** Support staff, managers, administrative roles

**How it works:**
- Regular salary amount (daily/weekly/monthly)
- No commission from orders
- Automatic salary transfers
- Predictable income

**Frequencies:**
- **Daily:** Salary transferred every day
- **Weekly:** Accumulated daily, transferred on week end (Saturday)
- **Monthly:** Accumulated daily, transferred on month end

**Example:**
```
Salary: $3000/month
Frequency: Monthly

Day 1-30: Accumulates $100/day
Day 31: Transfers $3000 to earnings
```

#### **3. Combined (Base + Commission)**

**Best for:** Senior sales staff, team leads

**How it works:**
- Fixed base salary + order commissions
- Best of both worlds
- Guaranteed income + performance incentives
- Automatic salary transfers + commission

**Example:**
```
Base Salary: $1000/month
Commission: $50/order

Month earnings:
- Base: $1000
- Commission (15 orders): $750
- Total: $1750
```

### **Setting Up Employee Salary**

#### **Step-by-Step Guide**

1. **Navigate to Employee Management**
   - Go to `Team Payroll > Team Members`
   - Find the employee
   - Click "Manage" button

2. **Configure Salary**
   - Scroll to "Salary Information" section
   - Select salary type:
     - Commission-Based
     - Fixed Salary
     - Combined (Base + Commission)

3. **Enter Salary Details** (for Fixed or Combined)
   - **Amount:** Enter salary amount
   - **Frequency:** Select daily/weekly/monthly

4. **Save Changes**
   - Click "Update Salary" button
   - Change is logged in salary history

### **Automatic Salary Transfers**

For Fixed and Combined salary types, the system automatically transfers salary to earnings.

#### **How It Works**

**Daily Frequency:**
```
Every day at midnight:
- Transfer full salary amount
- Add to employee earnings
- Log transaction
```

**Weekly Frequency:**
```
Monday-Friday:
- Accumulate daily amount (salary ÷ 7)
- Store in pending salary

Saturday (week end):
- Transfer accumulated amount
- Reset pending salary
- Log transaction
```

**Monthly Frequency:**
```
Day 1-30:
- Accumulate daily amount (salary ÷ days_in_month)
- Store in pending salary

Last day of month:
- Transfer accumulated amount
- Reset pending salary
- Log transaction
```

#### **Partial Periods**

When salary is set mid-period:

```
Example: Monthly salary set on 15th

Calculation:
- Days remaining: 16 (15th to 30th)
- Daily rate: $3000 ÷ 30 = $100
- Partial salary: $100 × 16 = $1600

Result:
- Accumulates $100/day for remaining days
- Transfers $1600 on month end
```

### **Salary History**

All salary changes are automatically tracked:

**What's Logged:**
- Date and time of change
- Previous salary type and amount
- New salary type and amount
- Frequency changes
- Who made the change

**Where to View:**
- **Admin:** Employee detail page → Salary History tab
- **Employee:** My Account → Salary Details → Salary Change History

**Example History Entry:**
```
Date: April 25, 2026 10:30 AM
Changed by: Admin (admin@example.com)

Previous: Commission-Based
New: Combined (Base + Commission)
Amount: $2000/month
```

### **Salary Transactions**

All salary transfers are logged as transactions:

**Transaction Types:**
- `daily_transfer` - Daily salary transfer
- `weekly_transfer` - Weekly salary transfer
- `monthly_transfer` - Monthly salary transfer
- `partial_transfer` - Partial period transfer

**Transaction Data:**
```php
array(
    'type' => 'monthly_transfer',
    'amount' => 3000.00,
    'date' => '2026-04-30 23:59:59',
    'period_start' => '2026-04-01',
    'period_end' => '2026-04-30',
    'days' => 30,
    'daily_rate' => 100.00
)
```

### **Pending Salary**

For weekly and monthly frequencies, salary accumulates in "pending":

**Viewing Pending Salary:**
- **Admin:** Employee detail page → Salary Information
- **Employee:** My Account → My Earnings → Amount Due

**Example:**
```
Monthly Salary: $3000
Today: April 15
Days elapsed: 15
Pending: $1500 (15 × $100)
```

### **Salary Debug Tool**

For testing and troubleshooting salary automation:

1. **Enable Debug Mode**
   - Go to `Settings > Debug`
   - Check "Enable Salary Debug Tools"
   - Save settings

2. **Access Debug Tool**
   - Go to `Team Payroll > Salary Debug`
   - Select employee
   - Use available tools:
     - Test Accumulation
     - Get Status
     - Manual Transfer
     - Reset Data

3. **Testing Scenarios**
   - **Daily:** 1 click = immediate transfer
   - **Weekly:** Accumulates until Saturday
   - **Monthly:** Accumulates until month end

---

## 👥 Employee Management

Complete guide to managing your team members.

### **Adding New Employees**

#### **Method 1: WordPress Users**

1. **Create WordPress User**
   - Go to `Users > Add New`
   - Fill in user details:
     - Username
     - Email
     - Password
   - Select role: Shop Employee, Shop Manager, or Administrator
   - Click "Add New User"

2. **Employee ID Auto-Generation**
   - Plugin automatically generates employee ID
   - Format: PREFIX + sequential number
   - Example: PVVB-EMID001, PVVB-EMID002
   - Stored in user meta as `vb_user_id`

3. **Configure Employee**
   - Go to `Team Payroll > Team Members`
   - Find the new employee
   - Click "Manage"
   - Set up salary, payment methods, etc.

#### **Method 2: WooCommerce Customers**

Existing customers can be converted to employees:

1. Edit customer user
2. Change role to Shop Employee
3. Employee ID is auto-generated
4. Configure in Team Members page

### **Employee Information**

Each employee profile includes:

**Basic Information:**
- Display Name
- Email Address
- Phone Number
- Employee ID (vb_user_id)
- User Role
- Registration Date

**Salary Information:**
- Salary Type (Commission/Fixed/Combined)
- Salary Amount
- Frequency (Daily/Weekly/Monthly)
- Pending Salary
- Total Earnings

**Payment Information:**
- Payment Methods
- Payment History
- Total Paid
- Amount Due

**Performance Data:**
- Total Orders
- Total Commission
- Current Month Earnings
- Performance Goals
- Achievements

### **Employee Status**

Employees can be Active or Inactive:

#### **Active Status**

- ✅ Can login to WordPress
- ✅ Can process orders
- ✅ Earns commission (if eligible)
- ✅ Appears in agent dropdown
- ✅ Receives salary transfers
- ✅ Access to My Account tabs

#### **Inactive Status**

- ❌ Cannot login to WordPress
- ❌ Cannot process orders
- ❌ Does not earn commission
- ❌ Hidden from agent dropdown
- ❌ Salary transfers paused
- ❌ Shows contact info on login attempt

**Setting Employee Status:**

1. Go to employee detail page
2. Find "Employee Status" section
3. Select Active or Inactive
4. Save changes

**Inactive Employee Login:**

When inactive employees try to login, they see:

```
⚠️ Account Deactivated

Your employee account has been deactivated.
If this is a mistake, please contact us:

Contact us:
WhatsApp | Email | Telegram
```

### **Payment Methods**

Configure how employees receive payments:

#### **Adding Payment Method**

1. Go to employee detail page
2. Scroll to "Payment Methods" section
3. Click "Add Payment Method"
4. Enter:
   - Method Name (e.g., Bank Transfer, bKash)
   - Method Details (e.g., Account number)
5. Save

**Example Payment Methods:**
```
Method: Bank Transfer
Details: Account #123456789, Bank of America

Method: bKash
Details: +880 1234567890

Method: PayPal
Details: employee@example.com
```

#### **Viewing Payment Methods**

- **Admin:** Employee detail page
- **Employee:** My Account → Salary Details → Payment Methods

### **Profile Pictures**

Employees can upload profile pictures:

1. **Admin Upload:**
   - Edit user in WordPress
   - Use profile picture field
   - Or use Gravatar

2. **Employee Upload:**
   - My Account → Account Details
   - Upload profile picture
   - Appears in dashboards and reports

### **Employee Filtering**

Filter employees by various criteria:

**Available Filters:**
- **Date Range:** Employee created date
- **Salary Type:** Commission/Fixed/Combined
- **Status:** Active/Inactive
- **Search:** Name, email, employee ID

**Example Use Cases:**
```
Find all commission-based employees:
- Filter: Salary Type = Commission-Based

Find employees created this month:
- Filter: Date Range = This Month

Find inactive employees:
- Filter: Status = Inactive
```

### **Bulk Operations**

Perform actions on multiple employees:

1. Select employees using checkboxes
2. Choose bulk action:
   - Export to CSV
   - Change status
   - Send notifications
3. Apply action

---

## 🛒 Checkout Integration

Configure how agents are assigned at checkout.

### **Agent Dropdown**

The plugin automatically adds an agent selection dropdown at checkout.

#### **How It Works**

1. **Customer visits checkout**
2. **Agent dropdown appears** (if configured)
3. **Customer selects agent** (optional)
4. **Order is placed** with agent/processor data

#### **Dropdown Behavior**

**Shows:**
- ✅ Active employees only
- ✅ Users with configured roles
- ✅ Employee ID + Name format
- ✅ Alphabetically sorted

**Hides:**
- ❌ Inactive employees
- ❌ Current logged-in user (can't select self)
- ❌ Users without employee roles

**Example Dropdown:**
```
Select Agent
- PVVB-EMID001 John Doe
- PVVB-EMID002 Jane Smith
- PVVB-EMID003 Bob Johnson
```

### **Agent Assignment Logic**

**Scenario 1: Customer selects agent**
```
Selected Agent: John
Logged-in User: Sarah

Result:
- Agent: John
- Processor: Sarah
- Commission split applies
```

**Scenario 2: No agent selected, user logged in**
```
Selected Agent: None
Logged-in User: John (has employee role)

Result:
- Agent: John
- Processor: None
- John gets 100% commission
```

**Scenario 3: No agent, no logged-in user**
```
Selected Agent: None
Logged-in User: None

Result:
- No commission assignment
- Order processed normally
```

### **Configuration**

Navigate to `Settings > WooCommerce`

#### **Field Names**

```
Agent Field Name: order_agent_name
Processor Field Name: _processor_user_id
```

These are the meta keys used to store agent/processor IDs.

#### **Agent User Roles**

Select which roles can be agents:

- ☑️ Shop Employee
- ☑️ Shop Manager  
- ☑️ Administrator

#### **Customization**

**Change dropdown label:**
```php
add_filter( 'wc_tp_agent_field_label', function( $label ) {
    return 'Select Sales Representative';
} );
```

**Change dropdown placeholder:**
```php
add_filter( 'wc_tp_agent_field_placeholder', function( $placeholder ) {
    return 'Choose your agent...';
} );
```

### **Testing Checkout Integration**

1. **Create test employees** with different roles
2. **Visit checkout page** as guest
3. **Verify dropdown appears** with active employees
4. **Select an agent** and place order
5. **Check order meta** for agent/processor IDs
6. **Verify commission** is calculated correctly

---

## 🎨 Frontend Features

Beautiful, responsive dashboards for employees to track their earnings and performance.

### **My Account Tabs**

Employees get four new tabs in WooCommerce My Account:

#### **1. Salary Details**

**What employees see:**
- Current salary type and amount
- Salary frequency
- Payment methods
- Salary change history

**Features:**
- Visual salary type badges
- Formatted salary display
- Payment method cards
- Searchable history table
- Sortable columns

**Example View:**
```
┌─────────────────────────────────┐
│ Salary Information              │
├─────────────────────────────────┤
│ Type: Combined                  │
│ Amount: $2000/month             │
│ + Commission from orders        │
└─────────────────────────────────┘

┌─────────────────────────────────┐
│ Payment Methods                 │
├─────────────────────────────────┤
│ 🏦 Bank Transfer                │
│    Account #123456789           │
│                                 │
│ 📱 bKash                        │
│    +880 1234567890              │
└─────────────────────────────────┘
```

#### **2. My Earnings**

**What employees see:**
- Current month earnings
- Total earnings (all time)
- Total paid
- Amount due
- Monthly earnings history

**Features:**
- Summary cards with breakdowns
- Salary vs Commission split
- Searchable earnings table
- Date range filters
- Export to CSV

**Example View:**
```
┌──────────────┬──────────────┬──────────────┬──────────────┐
│ This Month   │ Total        │ Total Paid   │ Amount Due   │
├──────────────┼──────────────┼──────────────┼──────────────┤
│ $1,500       │ $15,000      │ $12,000      │ $3,000       │
│ Salary: $800 │ Salary: $8k  │              │              │
│ Comm: $700   │ Comm: $7k    │              │              │
└──────────────┴──────────────┴──────────────┴──────────────┘
```

#### **3. My Orders (Commission)**

**What employees see:**
- All orders they're involved in
- Commission earned per order
- Role (Agent or Processor)
- Order status and details

**Features:**
- Filterable by date, status, role
- Searchable by order number
- Click to view order details
- Commission breakdown modal
- Export to CSV

**Example View:**
```
┌────────┬──────────┬─────────┬────────────┬──────────┐
│ Order  │ Date     │ Total   │ Commission │ Role     │
├────────┼──────────┼─────────┼────────────┼──────────┤
│ #1234  │ Apr 25   │ $500    │ $35        │ Agent    │
│ #1235  │ Apr 24   │ $300    │ $9         │ Processor│
│ #1236  │ Apr 23   │ $800    │ $56        │ Agent    │
└────────┴──────────┴─────────┴────────────┴──────────┘
```

#### **4. Reports**

**What employees see:**
- Performance dashboard
- Goals progress
- Achievements
- Analytics charts
- Leaderboard position

**Features:**
- Interactive charts
- Goal tracking with progress bars
- Achievement badges
- Performance trends
- Comparison with team average

### **Customizing Appearance**

Navigate to `Settings > Frontend Styling`

#### **Color Scheme**

Customize all colors:

- **Primary Color** - Buttons, links, accents
- **Secondary Color** - Success states, positive amounts
- **Heading Color** - All headings (h1, h2, h3)
- **Text Color** - Body text
- **Link Color** - Links in normal state
- **Link Hover Color** - Links when hovered
- **Background Color** - Main content areas
- **Card Background** - Cards and info boxes
- **Border Color** - Borders and dividers

#### **Typography**

Customize fonts and sizes:

- **Font Family** - Choose from presets or custom
- **Base Font Size** - Body text size (px or CSS variable)
- **Heading Font Size** - Main heading size (px or CSS variable)

**Preset Fonts:**
- Inherit from theme
- Arial
- Helvetica
- Segoe UI
- Roboto
- Open Sans
- Lato
- Poppins
- Georgia
- Times New Roman

**Custom Font Example:**
```css
Font Family: Custom
Custom Font: 'Inter', -apple-system, sans-serif
```

#### **Button Styling**

Customize button appearance:

- **Button Background** - Primary button color
- **Button Text Color** - Button text
- **Button Hover Background** - Hover state
- **Button Border Radius** - Roundness (0-20px)

#### **Table Styling**

Customize table appearance:

- **Table Header Background** - Header row color
- **Table Row Hover** - Hover state color
- **Table Border Color** - Border and divider color

#### **Layout Settings**

- **Card Border Radius** - Card roundness (0-20px)
- **Card Shadow** - None, Light, Medium, Heavy

#### **Custom CSS**

Add your own CSS rules:

```css
/* Example: Custom card styling */
.pv-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

/* Example: Custom button */
.button-primary {
    text-transform: uppercase;
    letter-spacing: 1px;
}
```

#### **Live Preview**

Test your styling changes in real-time:

1. Make styling changes
2. Click "Live Preview" button (bottom right)
3. Preview panel opens on the right
4. See changes instantly
5. Close preview when done

#### **Reset to Defaults**

Reset all styling to default values:

1. Scroll to bottom of Styling tab
2. Click "Reset to Default Styling"
3. Confirm action
4. All styling resets to plugin defaults

### **Responsive Design**

All frontend features are fully responsive:

- **Desktop** - Full layout with all features
- **Tablet** - Optimized layout, touch-friendly
- **Mobile** - Stacked layout, mobile-optimized tables

**Mobile Features:**
- Swipeable tables
- Collapsible sections
- Touch-friendly buttons
- Optimized font sizes

---

## 📊 Performance Tracking

Motivate your team with goals, achievements, and leaderboards.

### **Goals System**

Set performance targets for employees:

#### **Goal Types**

**1. Order Goals**
```
Target: Complete 50 orders this month
Progress: 35/50 (70%)
Reward: $100 bonus
```

**2. Revenue Goals**
```
Target: Generate $10,000 in sales
Progress: $7,500/$10,000 (75%)
Reward: $200 bonus
```

**3. Commission Goals**
```
Target: Earn $1,000 in commission
Progress: $850/$1,000 (85%)
Reward: $150 bonus
```

#### **Setting Up Goals**

Navigate to `Settings > Reports & Performance`

1. **Create Goal**
   - Click "Add New Goal"
   - Select goal type
   - Set target value
   - Set reward amount
   - Set end date (optional)

2. **Assign to Employees**
   - All employees (default)
   - Specific employees
   - By role
   - By salary type

3. **Configure Rewards**
   - Money reward (added to earnings)
   - Badge reward (virtual recognition)
   - Both

#### **Goal Progress Tracking**

Employees see their progress in:

- My Account → Reports → Goals
- Dashboard widgets
- Email notifications (optional)

**Progress Display:**
```
┌─────────────────────────────────────┐
│ Monthly Order Goal                  │
├─────────────────────────────────────┤
│ Target: 50 orders                   │
│ Progress: 35/50 (70%)               │
│ ████████████████░░░░░░░░            │
│ Reward: $100 bonus                  │
│ Ends: April 30, 2026                │
└─────────────────────────────────────┘
```

### **Achievements System**

Reward employees for reaching milestones:

#### **Achievement Types**

**Milestone Achievements:**
- First Order
- 10 Orders
- 50 Orders
- 100 Orders
- 500 Orders

**Revenue Achievements:**
- $1,000 in Sales
- $10,000 in Sales
- $50,000 in Sales
- $100,000 in Sales

**Commission Achievements:**
- $100 in Commission
- $1,000 in Commission
- $5,000 in Commission
- $10,000 in Commission

**Special Achievements:**
- Perfect Month (no refunds)
- Top Performer
- Team Player
- Customer Favorite

#### **Creating Achievements**

1. **Define Achievement**
   - Name and description
   - Icon/badge image
   - Unlock criteria

2. **Set Rewards**
   - Money reward
   - Badge reward
   - Both

3. **Configure Visibility**
   - Show to all employees
   - Show only when unlocked
   - Show on leaderboard

#### **Claiming Achievements**

Employees can claim achievements:

1. Go to My Account → Reports → Achievements
2. View unlocked achievements
3. Click "Claim Reward"
4. Reward added to earnings

**Achievement Display:**
```
┌─────────────────────────────────────┐
│ 🏆 100 Orders Milestone             │
├─────────────────────────────────────┤
│ Congratulations! You've completed   │
│ 100 orders. Keep up the great work!│
│                                     │
│ Reward: $500 bonus                  │
│ Status: ✅ Claimed                  │
│ Date: April 25, 2026                │
└─────────────────────────────────────┘
```

### **Leaderboard**

Real-time ranking of top performers:

#### **Leaderboard Metrics**

- Total Orders
- Total Revenue
- Total Commission
- Current Month Performance
- Achievement Count

#### **Leaderboard Display**

**Admin Dashboard:**
```
┌────┬──────────────┬────────┬──────────┬────────────┐
│ #  │ Employee     │ Orders │ Revenue  │ Commission │
├────┼──────────────┼────────┼──────────┼────────────┤
│ 🥇 │ John Doe     │ 45     │ $15,000  │ $1,050     │
│ 🥈 │ Jane Smith   │ 38     │ $12,500  │ $875       │
│ 🥉 │ Bob Johnson  │ 32     │ $10,000  │ $700       │
│ 4  │ Sarah Wilson │ 28     │ $8,500   │ $595       │
│ 5  │ Mike Brown   │ 25     │ $7,000   │ $490       │
└────┴──────────────┴────────┴──────────┴────────────┘
```

**Employee View:**
```
Your Position: #3 🥉

You're in the top 10%!
Keep pushing to reach #2.

Gap to #2: 6 orders
```

#### **Leaderboard Configuration**

Navigate to `Settings > Reports & Performance`

- **Enable/Disable** leaderboard
- **Select metrics** to display
- **Set update frequency** (real-time, hourly, daily)
- **Show/hide** to employees
- **Configure rewards** for top positions

---

## ✏️ Order Editor

Advanced order editing with automatic commission recalculation.

### **Enabling Order Editor**

Navigate to `Settings > General > Order Editor Settings`

1. **Enable Feature**
   - Check "Enable Order Editor"
   - Save settings

2. **Select Editable Statuses**
   - Choose which order statuses allow editing
   - Recommended: Processing, On Hold, Pending
   - Avoid: Completed, Refunded (unless necessary)

3. **Verify Compatibility**
   - Plugin checks for conflicts
   - Disables automatically if conflicts detected
   - Shows warning message

### **Order Editor Features**

#### **1. Add Products**

Add new products to existing orders:

1. Open order in admin
2. Find "Order Items" section
3. Click "Add Product" button
4. Search and select product
5. Set quantity and price
6. Click "Add"
7. Commission recalculates automatically

#### **2. Edit Items**

Modify existing order items:

1. Click edit icon next to item
2. Change:
   - Quantity
   - Price
   - Line total
3. Save changes
4. Commission recalculates

#### **3. Remove Items**

Delete items from orders:

1. Click delete icon next to item
2. Confirm deletion
3. Item removed
4. Commission recalculates

#### **4. Edit Order Meta**

Modify custom order meta fields:

1. Find "Custom Fields" section
2. Click "Edit Custom Fields"
3. Modify field values
4. Save changes

**Supported Field Types:**
- Text
- Email
- URL
- Date
- Number
- Textarea
- Checkbox

#### **5. Recalculate Totals**

Manually trigger recalculation:

1. Click "Recalculate" button
2. System recalculates:
   - Order subtotal
   - Tax
   - Shipping
   - Total
   - Commission

### **Audit Trail**

All changes are logged:

**What's Logged:**
- Who made the change
- When it was made
- What was changed
- Old and new values

**Where to View:**
- Order notes section
- Shows complete change history

**Example Log Entry:**
```
April 25, 2026 @ 10:30 AM by Admin

Order edited:
- Added: Product A (Qty: 2, Price: $50)
- Removed: Product B
- Changed quantity: Product C (2 → 3)
- Commission recalculated: $100 → $150
```

### **Conflict Detection**

Plugin automatically detects conflicts with:

- Other order editing plugins
- Theme customizations
- Custom order management systems

**If conflict detected:**
- Order editor disables automatically
- Warning message shown in settings
- Manual override available (not recommended)

### **Best Practices**

**DO:**
- ✅ Edit orders in Processing/Pending status
- ✅ Verify commission after editing
- ✅ Add notes explaining changes
- ✅ Test on staging site first

**DON'T:**
- ❌ Edit completed orders unnecessarily
- ❌ Remove items without customer approval
- ❌ Change prices without documentation
- ❌ Ignore audit trail

---

## 💳 Payment Management

Track and manage employee payments.

### **Adding Payments**

#### **Manual Payment Entry**

1. **Navigate to Employee**
   - Go to `Team Payroll > Team Members`
   - Click "Manage" next to employee

2. **Add Payment**
   - Scroll to "Payments" section
   - Click "Add Payment" button

3. **Enter Payment Details**
   - **Amount:** Payment amount
   - **Date:** Payment date and time
   - **Payment Method:** How payment was made
   - **Note:** Optional note/reference

4. **Save Payment**
   - Click "Add Payment"
   - Payment added to history
   - Total paid updated

**Example Payment:**
```
Amount: $1,500
Date: April 25, 2026 10:30 AM
Method: Bank Transfer
Note: Monthly salary for April 2026
Added by: Admin
```

### **Payment History**

View complete payment history:

**Admin View:**
- Employee detail page → Payments tab
- Shows all payments with details
- Sortable and searchable
- Export to CSV

**Employee View:**
- My Account → Salary Details
- Shows their payment history
- Cannot edit or delete

**Payment History Table:**
```
┌────────────┬─────────┬────────────────┬──────────────┬────────────┐
│ Date       │ Amount  │ Method         │ Note         │ Added By   │
├────────────┼─────────┼────────────────┼──────────────┼────────────┤
│ Apr 25     │ $1,500  │ Bank Transfer  │ Monthly      │ Admin      │
│ Apr 15     │ $500    │ bKash          │ Advance      │ Manager    │
│ Mar 31     │ $2,000  │ Bank Transfer  │ Monthly      │ Admin      │
└────────────┴─────────┴────────────────┴──────────────┴────────────┘
```

### **Payment Methods**

Configure employee payment methods:

#### **Adding Payment Method**

1. Go to employee detail page
2. Find "Payment Methods" section
3. Click "Add Payment Method"
4. Enter:
   - **Method Name:** e.g., Bank Transfer
   - **Method Details:** e.g., Account number
5. Save

#### **Common Payment Methods**

**Bank Transfer:**
```
Method: Bank Transfer
Details: 
- Bank: Bank of America
- Account: 123456789
- Routing: 987654321
- Account Name: John Doe
```

**Mobile Banking:**
```
Method: bKash
Details: +880 1234567890

Method: Nagad
Details: +880 9876543210
```

**Digital Wallets:**
```
Method: PayPal
Details: john.doe@example.com

Method: Stripe
Details: Connected account ID: acct_123456
```

**Cash:**
```
Method: Cash
Details: Pickup from office
```

### **Payment Tracking**

Track payment status and amounts:

**Total Paid:**
- Sum of all payments
- Displayed in employee detail
- Visible to employee

**Amount Due:**
- Total Earnings - Total Paid
- Automatically calculated
- Updated in real-time

**Example:**
```
Total Earnings: $15,000
Total Paid: $12,000
Amount Due: $3,000
```

### **Payment Reports**

Generate payment reports:

1. **Navigate to Reports**
   - Go to `Team Payroll > Payroll`

2. **Filter by Date Range**
   - Select start and end date
   - Click "Filter"

3. **View Payment Summary**
   - Total paid in period
   - Payment breakdown by employee
   - Payment method distribution

4. **Export Report**
   - Click "Export to CSV"
   - Download for accounting

---

## 📈 Reports & Analytics

Comprehensive reporting and analytics.

### **Dashboard Reports**

Navigate to `Team Payroll > Dashboard`

#### **Key Metrics**

**Summary Cards:**
- Total Employees
- Total Orders
- Total Earnings
- Total Paid
- Total Due

**Charts:**
- Earnings trend (line chart)
- Commission distribution (pie chart)
- Top performers (bar chart)
- Order status breakdown (donut chart)

#### **Date Range Filters**

Filter data by:
- Today
- This Week
- This Month
- This Year
- Last Week
- Last Month
- Last Year
- Last 6 Months
- Custom Range

### **Payroll Reports**

Navigate to `Team Payroll > Payroll`

#### **Payroll Summary**

View payroll for any period:

```
┌──────────────┬────────┬──────────┬─────────┬─────────┐
│ Employee     │ Orders │ Earnings │ Paid    │ Due     │
├──────────────┼────────┼──────────┼─────────┼─────────┤
│ John Doe     │ 45     │ $3,150   │ $3,000  │ $150    │
│ Jane Smith   │ 38     │ $2,660   │ $2,500  │ $160    │
│ Bob Johnson  │ 32     │ $2,240   │ $2,000  │ $240    │
└──────────────┴────────┴──────────┴─────────┴─────────┘

Total: $8,050 | Paid: $7,500 | Due: $550
```

#### **Export Options**

- **CSV Export** - For Excel/Google Sheets
- **PDF Export** - For printing/archiving
- **Email Report** - Send to stakeholders

### **Employee Reports**

Navigate to employee detail page

#### **Performance Metrics**

- Total orders (all time)
- Total commission earned
- Average order value
- Conversion rate
- Customer satisfaction

#### **Earnings Breakdown**

```
Total Earnings: $15,000
├─ Commission: $9,000 (60%)
├─ Base Salary: $5,000 (33%)
└─ Bonuses: $1,000 (7%)
```

#### **Monthly Trends**

Line chart showing:
- Monthly earnings
- Order count
- Average commission
- Growth rate

### **Custom Reports**

Create custom reports:

1. **Select Metrics**
   - Choose data points to include
   - Orders, earnings, payments, etc.

2. **Apply Filters**
   - Date range
   - Employees
   - Order status
   - Salary type

3. **Generate Report**
   - View in browser
   - Export to CSV/PDF
   - Schedule email delivery

---

## 🎨 Customization

Extend and customize the plugin.

### **Styling Customization**

Already covered in [Frontend Features](#-frontend-features) section.

### **Shortcodes**

Display earnings data anywhere on your site:

#### **Available Shortcodes**

**1. Employee Earnings**
```
[wc_tp_earnings user_id="5"]
```
Shows total earnings for user ID 5.

**2. Monthly Earnings**
```
[wc_tp_monthly_earnings user_id="5" month="4" year="2026"]
```
Shows earnings for specific month.

**3. Leaderboard**
```
[wc_tp_leaderboard limit="10"]
```
Shows top 10 performers.

**4. Employee Stats**
```
[wc_tp_stats user_id="5"]
```
Shows employee statistics.

#### **Shortcode Parameters**

**Common Parameters:**
- `user_id` - User ID (default: current user)
- `format` - Display format (table, card, inline)
- `show_label` - Show/hide labels (yes/no)
- `currency` - Currency symbol

**Example with Parameters:**
```
[wc_tp_earnings user_id="5" format="card" show_label="yes"]
```

### **Hooks & Filters**

Customize plugin behavior with WordPress hooks.

#### **Action Hooks**

**Commission Calculated:**
```php
do_action( 'wc_team_payroll_commission_calculated', $order_id, $commission_data );

// Example usage:
add_action( 'wc_team_payroll_commission_calculated', function( $order_id, $commission_data ) {
    // Send notification
    // Log to external system
    // Trigger automation
}, 10, 2 );
```

**Salary Changed:**
```php
do_action( 'wc_tp_salary_changed', $user_id, $salary_data );

// Example usage:
add_action( 'wc_tp_salary_changed', function( $user_id, $salary_data ) {
    // Notify employee
    // Update external HR system
    // Log change
}, 10, 2 );
```

**Order Edited:**
```php
do_action( 'wc_team_payroll_order_edited', $order_id );

// Example usage:
add_action( 'wc_team_payroll_order_edited', function( $order_id ) {
    // Recalculate commission
    // Send notification
    // Update inventory
}, 10, 1 );
```

**Salary Transferred:**
```php
do_action( 'wc_tp_salary_transferred', $user_id, $amount, $type );

// Example usage:
add_action( 'wc_tp_salary_transferred', function( $user_id, $amount, $type ) {
    // Send payment notification
    // Update accounting system
    // Generate invoice
}, 10, 3 );
```

#### **Filter Hooks**

**Modify Commission Data:**
```php
$commission_data = apply_filters( 'wc_tp_commission_data', $commission_data, $order );

// Example: Add bonus for high-value orders
add_filter( 'wc_tp_commission_data', function( $commission_data, $order ) {
    if ( $order->get_total() > 1000 ) {
        $commission_data['agent_earnings'] *= 1.1; // 10% bonus
    }
    return $commission_data;
}, 10, 2 );
```

**Modify Agent Percentage:**
```php
$agent_percentage = apply_filters( 'wc_tp_agent_percentage', $agent_percentage, $order_id );

// Example: Higher percentage for premium products
add_filter( 'wc_tp_agent_percentage', function( $percentage, $order_id ) {
    $order = wc_get_order( $order_id );
    // Check if order has premium products
    if ( has_premium_products( $order ) ) {
        return 80; // 80% instead of default 70%
    }
    return $percentage;
}, 10, 2 );
```

**Customize My Account Menu:**
```php
$items = apply_filters( 'woocommerce_account_menu_items', $items );

// Example: Reorder menu items
add_filter( 'woocommerce_account_menu_items', function( $items ) {
    // Move earnings to top
    $earnings = $items['my-earnings'];
    unset( $items['my-earnings'] );
    $items = array( 'my-earnings' => $earnings ) + $items;
    return $items;
}, 20 );
```

### **Custom Templates**

Override plugin templates:

1. **Copy Template**
   - From: `plugins/woocommerce-team-payroll/templates/`
   - To: `themes/your-theme/woocommerce-team-payroll/`

2. **Modify Template**
   - Edit the copied file
   - Changes won't be overwritten on update

3. **Available Templates**
   - `myaccount/salary-details.php`
   - `myaccount/my-earnings.php`
   - `myaccount/orders-commission.php`
   - `myaccount/reports.php`

---

## 🔧 Troubleshooting

Common issues and their solutions.

### **My Account Tabs Not Showing**

**Problem:** Employee doesn't see Salary Details, My Earnings, etc. tabs

**Solutions:**

1. **Flush Permalinks**
   ```
   Go to: Settings > Permalinks
   Action: Click "Save Changes"
   Result: Endpoints registered
   ```

2. **Check User Role**
   ```
   Verify user has: Shop Employee, Shop Manager, or Administrator role
   Not working with: Customer, Subscriber roles
   ```

3. **Clear Browser Cache**
   ```
   Press: Ctrl+Shift+Delete (Windows) or Cmd+Shift+Delete (Mac)
   Clear: Cached images and files
   Reload: Page
   ```

4. **Check Plugin Activation**
   ```
   Go to: Plugins
   Verify: WooCommerce Team Payroll is active
   Check: No error messages
   ```

### **Commission Not Calculating**

**Problem:** Orders don't have commission data

**Solutions:**

1. **Check Commission Settings**
   ```
   Go to: Settings > Commission
   Verify: Agent and processor percentages are set
   Check: Values are between 0-100
   ```

2. **Check Product Commission Rate**
   ```
   Edit product
   Find: Team Commission field
   Verify: Has value (e.g., 10 for 10%)
   Save: Product
   ```

3. **Check Order Status**
   ```
   Go to: Settings > WooCommerce
   Verify: Order status is in "Commission Calculation Statuses"
   Default: Completed, Processing
   ```

4. **Check Employee Salary Type**
   ```
   Go to: Team Members > Manage employee
   Verify: Salary type is Commission-Based or Combined
   Note: Fixed salary employees don't get commission
   ```

5. **Check Agent/Processor Assignment**
   ```
   Edit order
   Check: _primary_agent_id meta exists
   Check: _processor_user_id meta exists
   Verify: IDs are valid user IDs
   ```

### **Automatic Salary Not Transferring**

**Problem:** Fixed/Combined salary not transferring automatically

**Solutions:**

1. **Check WordPress Cron**
   ```
   Install: WP Crontrol plugin
   Go to: Tools > Cron Events
   Find: wc_tp_daily_salary_cron
   Verify: Scheduled and running
   ```

2. **Check Salary Configuration**
   ```
   Go to: Employee detail page
   Verify: Salary type is Fixed or Combined
   Check: Amount and frequency are set
   Verify: Employee status is Active
   ```

3. **Use Salary Debug Tool**
   ```
   Go to: Settings > Debug
   Enable: Salary Debug Tools
   Go to: Team Payroll > Salary Debug
   Select: Employee
   Click: Test Accumulation
   ```

4. **Check Server Cron**
   ```
   Some hosts disable WP Cron
   Solution: Set up real cron job
   Add to crontab: */15 * * * * wget -q -O - https://yoursite.com/wp-cron.php
   ```

### **Order Editor Not Appearing**

**Problem:** Can't see edit icons or order editor features

**Solutions:**

1. **Check Order Editor Settings**
   ```
   Go to: Settings > General > Order Editor Settings
   Verify: "Enable Order Editor" is checked
   Check: Order status is in editable statuses list
   Save: Settings
   ```

2. **Check Order Status**
   ```
   Edit order
   Check: Current order status
   Verify: Status is in editable statuses list
   Example: Processing, Pending, On Hold
   ```

3. **Check for Conflicts**
   ```
   Disable: Other order editing plugins temporarily
   Clear: Browser cache
   Check: JavaScript console for errors (F12)
   Test: Order editor appears
   ```

4. **Check Browser Console**
   ```
   Press: F12 to open developer tools
   Go to: Console tab
   Look for: JavaScript errors
   Fix: Any reported errors
   ```

### **Agent Dropdown Not Populating**

**Problem:** Checkout page doesn't show agent dropdown or it's empty

**Solutions:**

1. **Check Agent User Roles**
   ```
   Go to: Settings > WooCommerce
   Verify: Agent user roles are selected
   Default: Shop Employee, Shop Manager, Administrator
   ```

2. **Check Employee Status**
   ```
   Go to: Team Members
   Verify: Employees are Active (not Inactive)
   Note: Inactive employees don't appear in dropdown
   ```

3. **Check User Roles**
   ```
   Go to: Users
   Verify: Users have correct roles assigned
   Check: At least one user has Shop Employee role
   ```

4. **Clear Browser Cache**
   ```
   Clear: Browser cache and cookies
   Reload: Checkout page
   Test: Dropdown appears
   ```

5. **Check JavaScript**
   ```
   Press: F12 to open developer tools
   Go to: Console tab
   Look for: window.__AGENT_USERS__ variable
   Verify: Contains user data
   ```

### **Performance Issues**

**Problem:** Plugin slowing down site

**Solutions:**

1. **Optimize Database**
   ```
   Install: WP-Optimize plugin
   Run: Database optimization
   Clean: Post revisions, spam comments
   ```

2. **Enable Caching**
   ```
   Install: WP Super Cache or W3 Total Cache
   Enable: Page caching
   Exclude: My Account pages from cache
   ```

3. **Limit Query Results**
   ```
   Go to: Settings > Performance
   Set: Maximum orders per page
   Reduce: History display limit
   ```

4. **Check Server Resources**
   ```
   Verify: PHP memory limit (256MB recommended)
   Check: MySQL query performance
   Monitor: Server CPU and RAM usage
   ```

### **Payment Not Showing**

**Problem:** Added payment doesn't appear in history

**Solutions:**

1. **Check Payment Data**
   ```
   Verify: All required fields filled
   Check: Amount is greater than 0
   Verify: Date is valid
   ```

2. **Clear Cache**
   ```
   Clear: Browser cache
   Clear: WordPress object cache
   Reload: Page
   ```

3. **Check User Meta**
   ```
   Install: User Meta Manager plugin
   Find: _wc_tp_payments meta key
   Verify: Payment data exists
   ```

---

## 👨‍💻 Developer Guide

Technical documentation for developers.

### **Database Schema**

#### **User Meta Keys**

```php
// Salary Configuration
_wc_tp_fixed_salary          // bool: Is fixed salary
_wc_tp_combined_salary       // bool: Is combined salary
_wc_tp_salary_amount         // float: Salary amount
_wc_tp_salary_frequency      // string: daily|weekly|monthly

// Salary Data
_wc_tp_total_earnings        // float: Total salary earnings
_wc_tp_pending_salary        // float: Pending salary amount
_wc_tp_salary_transactions   // array: Salary transfer history
_wc_tp_salary_history        // array: Salary change history

// Payment Data
_wc_tp_payments              // array: Payment records
_wc_tp_payment_methods       // array: Payment method details

// Employee Data
_wc_tp_employee_status       // string: active|inactive
vb_user_id                   // string: Employee ID

// Performance Data
_wc_tp_achieved_bonuses      // array: Achievement data
_wc_tp_performance_goals     // array: Goal progress
```

#### **Order Meta Keys**

```php
// Commission Data
_primary_agent_id            // int: Agent user ID
_processor_user_id           // int: Processor user ID
_commission_data             // array: Commission breakdown

// Legacy Keys (backward compatibility)
_wc_tp_agent_id             // int: Old agent ID key
_wc_tp_processor_id         // int: Old processor ID key
```

#### **Commission Data Structure**

```php
array(
    'order_id' => 123,
    'agent_id' => 5,
    'processor_id' => 8,
    'total_commission' => 100.00,
    'agent_earnings' => 70.00,
    'processor_earnings' => 30.00,
    'agent_order_value' => 700.00,
    'processor_order_value' => 300.00,
    'order_total' => 1000.00,
    'calculated_at' => '2026-04-25 10:30:00',
    'items' => array(
        array(
            'product_id' => 456,
            'product_name' => 'Product A',
            'line_total' => 500.00,
            'commission_rate' => 10,
            'commission' => 50.00
        )
    )
)
```

### **Core Classes**

#### **WC_Team_Payroll_Core_Engine**

Handles commission calculations.

**Key Methods:**
```php
// Calculate commission for order
calculate_order_commission( $order_id )

// Calculate commission breakdown
calculate_commission( $order, $agent_id, $processor_id )

// Get user earnings for date range
get_user_earnings( $user_id, $start_date, $end_date, $order_statuses )

// Get total earnings (all time)
get_user_total_earnings( $user_id )

// Get commission earnings only
get_user_commission_earnings( $user_id )
```

#### **WC_Team_Payroll_Payroll_Engine**

Handles payroll calculations.

**Key Methods:**
```php
// Get monthly payroll summary
get_monthly_payroll( $year, $month )

// Get payroll by date range
get_payroll_by_date_range( $start_date, $end_date )

// Mark payroll as paid
mark_payroll_paid( $user_id, $year, $month, $amount )
```

#### **WC_Team_Payroll_Salary_Automation**

Handles automatic salary transfers.

**Key Methods:**
```php
// Initialize salary system
init()

// Process daily salary accumulation
process_daily_salary()

// Transfer salary to earnings
transfer_salary( $user_id, $amount, $type )

// Get pending salary
get_pending_salary( $user_id )
```

### **Helper Functions**

#### **Get Employee Data**

```php
// Get employee ID
$employee_id = get_user_meta( $user_id, 'vb_user_id', true );

// Get salary type
$is_fixed = get_user_meta( $user_id, '_wc_tp_fixed_salary', true );
$is_combined = get_user_meta( $user_id, '_wc_tp_combined_salary', true );

// Get salary amount
$salary_amount = get_user_meta( $user_id, '_wc_tp_salary_amount', true );

// Get employee status
$status = get_user_meta( $user_id, '_wc_tp_employee_status', true );
```

#### **Get Commission Data**

```php
// Get order commission
$order = wc_get_order( $order_id );
$commission_data = $order->get_meta( '_commission_data' );

// Get agent ID
$agent_id = $order->get_meta( '_primary_agent_id' );

// Get processor ID
$processor_id = $order->get_meta( '_processor_user_id' );
```

### **Custom Queries**

#### **Get Top Earners**

```php
global $wpdb;

$top_earners = $wpdb->get_results( "
    SELECT 
        u.ID,
        u.display_name,
        um.meta_value as total_earnings
    FROM {$wpdb->users} u
    INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
    WHERE um.meta_key = '_wc_tp_total_earnings'
    ORDER BY CAST(um.meta_value AS DECIMAL(10,2)) DESC
    LIMIT 10
" );
```

#### **Get Orders by Agent**

```php
$orders = wc_get_orders( array(
    'limit' => -1,
    'meta_query' => array(
        array(
            'key' => '_primary_agent_id',
            'value' => $user_id,
            'compare' => '='
        )
    )
) );
```

### **REST API Endpoints**

Plugin doesn't include REST API by default, but you can add custom endpoints:

```php
// Register custom endpoint
add_action( 'rest_api_init', function() {
    register_rest_route( 'wc-team-payroll/v1', '/earnings/(?P<user_id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'get_user_earnings_api',
        'permission_callback' => function() {
            return current_user_can( 'manage_woocommerce' );
        }
    ) );
} );

// Endpoint callback
function get_user_earnings_api( $request ) {
    $user_id = $request['user_id'];
    $core_engine = new WC_Team_Payroll_Core_Engine();
    $earnings = $core_engine->get_user_total_earnings( $user_id );
    
    return rest_ensure_response( array(
        'user_id' => $user_id,
        'total_earnings' => $earnings,
        'currency' => get_woocommerce_currency()
    ) );
}
```

### **Testing**

#### **Unit Testing**

```php
// Example PHPUnit test
class CommissionTest extends WP_UnitTestCase {
    
    public function test_commission_calculation() {
        $core_engine = new WC_Team_Payroll_Core_Engine();
        
        // Create test order
        $order = wc_create_order();
        $order->add_product( $product, 1 );
        $order->calculate_totals();
        
        // Calculate commission
        $commission_data = $core_engine->calculate_commission( 
            $order, 
            $agent_id, 
            $processor_id 
        );
        
        // Assert
        $this->assertEquals( 100, $commission_data['total_commission'] );
        $this->assertEquals( 70, $commission_data['agent_earnings'] );
        $this->assertEquals( 30, $commission_data['processor_earnings'] );
    }
}
```

---

## ❓ FAQ

### **General Questions**

**Q: Is this plugin free?**  
A: Yes, the plugin is free and open-source under GPL v2 license.

**Q: Does it work with the latest WooCommerce?**  
A: Yes, tested and compatible with WooCommerce 10.7.0 and HPOS.

**Q: Can I use it on multiple sites?**  
A: Yes, you can use it on unlimited sites.

**Q: Does it support multisite?**  
A: Yes, WordPress Multisite is supported.

### **Commission Questions**

**Q: Can I set different commission rates per product?**  
A: Yes, each product can have its own commission rate.

**Q: What happens if I change commission rates?**  
A: New rates apply to new orders only. Existing orders keep their original commission.

**Q: Can I have more than 2 people per order?**  
A: Currently supports agent and processor only (2 people max).

**Q: How are refunds handled?**  
A: Refunded orders have commission set to $0.

### **Salary Questions**

**Q: Can employees have both salary and commission?**  
A: Yes, use the "Combined" salary type.

**Q: How often is salary transferred?**  
A: Depends on frequency: daily, weekly, or monthly.

**Q: What if I change salary mid-month?**  
A: System calculates partial period automatically.

**Q: Can I manually trigger salary transfers?**  
A: Yes, use the Salary Debug tool.

### **Technical Questions**

**Q: Does it work with custom themes?**  
A: Yes, compatible with all WordPress themes.

**Q: Can I customize the appearance?**  
A: Yes, extensive styling options in Settings > Frontend Styling.

**Q: Does it slow down my site?**  
A: No, optimized for performance with minimal database queries.

**Q: Can I export data?**  
A: Yes, export to CSV from various pages.

### **Support Questions**

**Q: Where can I get help?**  
A: GitHub Issues: https://github.com/imranduzzlo/woocommerce-team-payroll/issues

**Q: Can I request features?**  
A: Yes, submit feature requests on GitHub with "enhancement" label.

**Q: Is there documentation?**  
A: Yes, this document and in-plugin documentation tab.

**Q: Can I hire you for customization?**  
A: Contact via GitHub or website: https://imranhossain.me/

---

## 💬 Support & Resources

### **Getting Help**

**GitHub Issues**  
Report bugs or request features:  
https://github.com/imranduzzlo/woocommerce-team-payroll/issues

**Documentation**  
Complete guide (this document):  
https://github.com/imranduzzlo/woocommerce-team-payroll/blob/main/DOCUMENTATION.md

**README**  
Quick start guide:  
https://github.com/imranduzzlo/woocommerce-team-payroll/blob/main/README.md

**In-Plugin Documentation**  
Navigate to: `Team Payroll > Settings > Documentation`

### **Community**

**GitHub Repository**  
https://github.com/imranduzzlo/woocommerce-team-payroll

**Changelog**  
View all changes:  
https://github.com/imranduzzlo/woocommerce-team-payroll/blob/main/CHANGELOG.md

### **Author**

**Imran**  
Website: https://imranhossain.me/  
GitHub: https://github.com/imranduzzlo

### **Contributing**

Contributions are welcome!

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

### **License**

GNU General Public License v2 or later  
https://www.gnu.org/licenses/gpl-2.0.html

---

## 🎉 Thank You!

Thank you for using WooCommerce Team Payroll & Commission System!

If you find this plugin helpful, please:
- ⭐ Star the repository on GitHub
- 📢 Share with others who might benefit
- 🐛 Report bugs to help improve the plugin
- 💡 Suggest features for future versions

**Made with ❤️ by Imran**

---

*Last Updated: April 2026*  
*Version: 1.0.0*  
*Documentation Version: 1.0*
