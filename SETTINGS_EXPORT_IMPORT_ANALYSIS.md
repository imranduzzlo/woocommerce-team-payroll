# Performance Settings Export/Import Analysis

## All Settings to Export/Import

### 1. **Performance Scoring Configuration**
- **Option Key:** `wc_tp_performance_config`
- **Structure:** 
  ```
  {
    "roles": {
      "shop_employee": {
        "earnings_weight": 30,
        "orders_weight": 40,
        "aov_weight": 30,
        "ranges": {
          "earnings": [...],
          "orders": [...],
          "aov": [...]
        }
      }
    }
  }
  ```
- **Contains:** Role-based performance scoring weights and ranges

### 2. **Goals Configuration**
- **Option Key:** `wc_tp_goals_config`
- **Structure:**
  ```
  {
    "roles": {
      "shop_employee": {
        "enabled": true,
        "goals": {
          "order_value": {
            "threshold": 3000,
            "currency": "USD"
          },
          "orders": {
            "threshold": 50
          },
          "aov": {
            "threshold": 60
          }
        }
      }
    }
  }
  ```
- **Contains:** Role-based performance goals and targets

### 3. **Achievements Configuration**
- **Option Key:** `wc_tp_achievements_config`
- **Structure:**
  ```
  {
    "enabled": true,
    "period": "monthly",
    "display_style": "badges",
    "show_locked": true,
    "notification": true,
    "roles": {
      "shop_employee": {
        "enabled": true,
        "display_style": "badges",
        "show_locked": true,
        "notification": true,
        "period": "monthly",
        "achievements": {
          "earnings": {
            "bronze": { threshold: 3000 },
            "silver": { threshold: 5000 },
            "gold": { threshold: 8000 }
          },
          "orders": {
            "bronze": { threshold: 40 },
            "silver": { threshold: 60 },
            "gold": { threshold: 100 }
          },
          "aov": {
            "bronze": { threshold: 50 },
            "silver": { threshold: 75 },
            "gold": { threshold: 100 }
          }
        }
      }
    }
  }
  ```
- **Contains:** Achievement thresholds, display settings, notifications

### 4. **Bonus Configuration**
- **Option Key:** `wc_tp_achievement_bonuses`
- **Structure:**
  ```
  {
    "enabled": true,
    "notification": true,
    "shop_employee": [
      {
        "rule_id": 1,
        "tier": "gold",
        "months": 2,
        "bonus_type": "money",
        "bonus_amount": 300,
        "bonus_description": "Gold Streak Bonus",
        "repeatable": true
      }
    ]
  }
  ```
- **Contains:** Bonus rules, streak requirements, reward amounts

### 5. **Baselines Configuration**
- **Option Key:** `wc_tp_baselines_config`
- **Structure:**
  ```
  {
    "roles": {
      "shop_employee": {
        "method": "average",
        "periods": 3,
        "percentile": 50,
        "baseline_earnings": 5000,
        "baseline_orders": 50,
        "baseline_aov": 100
      }
    }
  }
  ```
- **Contains:** Baseline calculation methods and values

### 6. **Calculation Configuration**
- **Option Key:** `wc_tp_calculation_config`
- **Structure:**
  ```
  {
    "roles": {
      "shop_employee": {
        "formula": "earnings * 0.1 + orders * 5",
        "attribution_method": "full_value",
        "split_percentage": 50
      }
    }
  }
  ```
- **Contains:** Custom calculation formulas, attribution methods

### 7. **System Configuration**
- **Option Key:** `wc_tp_system_config`
- **Structure:**
  ```
  {
    "auto_calculate": true,
    "calculation_frequency": "daily",
    "enable_notifications": true,
    "enable_reports": true,
    "data_retention_days": 365
  }
  ```
- **Contains:** System-wide settings and preferences

## Export Format

**File Format:** JSON
**Filename:** `wc-team-payroll-settings-{date}-{time}.json`
**Structure:**
```json
{
  "export_version": "1.0",
  "export_date": "2026-04-20 10:30:00",
  "plugin_version": "1.6.42",
  "settings": {
    "wc_tp_performance_config": {...},
    "wc_tp_goals_config": {...},
    "wc_tp_achievements_config": {...},
    "wc_tp_achievement_bonuses": {...},
    "wc_tp_baselines_config": {...},
    "wc_tp_calculation_config": {...},
    "wc_tp_system_config": {...}
  }
}
```

## Import Process

1. **Validation**
   - Check export version compatibility
   - Validate JSON structure
   - Verify all required settings present

2. **Backup**
   - Create backup of current settings before import
   - Store backup with timestamp

3. **Import**
   - Update each option key with imported data
   - Sanitize and validate data
   - Clear any caches

4. **Verification**
   - Confirm all settings imported successfully
   - Display summary of imported settings
   - Show any warnings or errors

## Security Considerations

1. **Nonce Verification** - All AJAX requests must include nonce
2. **Capability Check** - Only admins can export/import
3. **Data Sanitization** - All imported data must be sanitized
4. **Backup Creation** - Always backup before import
5. **Validation** - Validate structure and data types

## Implementation Plan

### Phase 1: Export Settings
1. Create AJAX handler for export
2. Collect all settings from database
3. Format as JSON
4. Trigger download

### Phase 2: Import Settings
1. Create AJAX handler for import
2. Validate uploaded file
3. Create backup of current settings
4. Import and sanitize data
5. Display results

### Phase 3: UI Integration
1. Add export button to performance tab
2. Add import button to performance tab
3. Add file input for import
4. Show progress/status messages
5. Display import summary

## Testing Checklist

- [ ] Export creates valid JSON file
- [ ] Export includes all settings
- [ ] Import accepts valid JSON
- [ ] Import validates data structure
- [ ] Import creates backup
- [ ] Import sanitizes data
- [ ] Import updates all options
- [ ] Import shows success message
- [ ] Export/Import works for all roles
- [ ] Export/Import preserves data integrity
- [ ] Error handling works properly
- [ ] Nonce verification works
- [ ] Capability checks work
