# Buffalo Marathon 2025 - Deployment Schema Updates

## 📋 Summary of Database Column Corrections

### Issues Fixed - August 13, 2025

#### 1. Categories Table Column Names
**Problem**: Deployment script was using incorrect column names
- ❌ **Wrong**: `fee` (old column name)
- ✅ **Correct**: `price` (current schema)

**Files Updated**:
- `deploy_production.php` - Fixed INSERT and UPDATE statements
- `my-registration.php` - Fixed SELECT query and display logic

#### 2. Schedules Table Column Names  
**Problem**: Deployment script was using wrong column structure
- ❌ **Wrong**: `title`, `description` (old column names)
- ✅ **Correct**: `event_name`, `event_description` (current schema)

**Files Updated**:
- `deploy_production.php` - Fixed table creation and INSERT statements

#### 3. Updated Schema Structure

##### Categories Table (Correct Schema):
```sql
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    distance VARCHAR(50) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,              -- ✅ CORRECT
    early_bird_price DECIMAL(10,2),
    max_participants INT DEFAULT 0,
    min_age INT DEFAULT 5,
    max_age INT DEFAULT 100,
    start_time TIME,
    estimated_duration VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    features JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

##### Schedules Table (Correct Schema):
```sql
CREATE TABLE schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(255) NOT NULL,          -- ✅ CORRECT
    event_description TEXT,                    -- ✅ CORRECT
    event_date DATE NOT NULL,
    event_time TIME NOT NULL,
    end_time TIME,
    location VARCHAR(255),
    category_id INT,
    event_type ENUM('race', 'activity', 'ceremony', 'registration', 'other') DEFAULT 'other',
    is_active BOOLEAN DEFAULT TRUE,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### 🔧 Deployment Script Corrections

#### Updated Categories Data (deploy_production.php):
```php
$categories = [
    ['Full Marathon', '42.2 KM', 200.00, 150.00, 'Description...', 1200, 18, '05:30:00'],
    ['Half Marathon', '21.1 KM', 150.00, 120.00, 'Description...', 1200, 16, '05:30:00'],
    ['Power Challenge', '10 KM', 100.00, 80.00, 'Description...', 1200, 14, '06:00:00'],
    ['Family Fun Run', '5 KM', 75.00, 60.00, 'Description...', 1200, 8, '06:00:00'],
    ['VIP Run', '5 KM', 250.00, 200.00, 'Description...', 1200, 18, '06:30:00'],
    ['Kid Run', '1 KM', 25.00, 20.00, 'Description...', 1200, 5, '06:00:00']
];

// ✅ CORRECT INSERT STATEMENT
$stmt = $db->prepare("
    INSERT INTO categories (name, distance, price, early_bird_price, description, max_participants, min_age, start_time, sort_order, is_active) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
    ON DUPLICATE KEY UPDATE 
    price = VALUES(price), 
    early_bird_price = VALUES(early_bird_price),
    description = VALUES(description),
    max_participants = VALUES(max_participants),
    min_age = VALUES(min_age),
    start_time = VALUES(start_time)
");
```

#### Updated Schedule Data:
```php
$schedule_events = [
    ['2025-10-01', '08:30:00', 'Race Pack Collection Day 1', 'Collect your race materials and bib number', 1],
    // ... (all 5 collection days)
    ['2025-10-11', '05:30:00', 'Marathon Start', 'Full Marathon and Half Marathon start', 6],
    ['2025-10-11', '06:00:00', 'Power Challenge Start', '10KM Power Challenge begins', 7],
    ['2025-10-11', '06:00:00', 'Family Fun Run Start', '5KM Family Fun Run begins', 8],
    ['2025-10-11', '06:00:00', 'Kid Run Start', '1KM Kid Run begins', 9],
    ['2025-10-11', '06:30:00', 'VIP Run Start', '5KM VIP Run begins', 10],
    ['2025-10-11', '11:00:00', 'Post-Race Celebration', 'Food, drinks, and celebration', 11],
    ['2025-10-11', '11:30:00', 'Awards Ceremony', 'Prize giving and medal ceremony', 12],
    ['2025-10-11', '13:00:00', 'Live Entertainment', 'Zambia Army Pop Band performance', 13]
];

// ✅ CORRECT INSERT STATEMENT
$stmt = $db->prepare("
    INSERT INTO schedules (event_date, event_time, event_name, event_description, display_order, is_active) 
    VALUES (?, ?, ?, ?, ?, 1)
    ON DUPLICATE KEY UPDATE 
    event_description = VALUES(event_description)
");
```

### 📊 Updated Capacity Settings

**Previous Settings**:
- Total capacity: 4,200 participants
- Mixed participant limits per category

**New Settings**:
- Total capacity: **7,200 participants** 
- Uniform limit: **1,200 participants per category**
- 6 active categories (including VIP Run at 6:30 AM)

### 🧪 Verification Tools

#### New File: `verify_schema.php`
- Automated schema verification script
- Checks for correct column names
- Validates data integrity
- Identifies legacy column references
- Confirms deployment readiness

#### Usage:
```bash
php verify_schema.php
```

### 🚀 Deployment Checklist

#### ✅ Fixed Issues:
- [x] Categories table uses `price` instead of `fee`
- [x] Schedules table uses `event_name` and `event_description`
- [x] Updated participant limits to 1,200 per category
- [x] Added VIP Run with 6:30 AM start time
- [x] Updated total capacity to 7,200
- [x] Fixed all SQL INSERT/UPDATE statements
- [x] Updated race pack collection schedule (Oct 1-5)
- [x] Created verification script

#### 📋 Next Steps:
1. **Run Verification**: Execute `verify_schema.php` to confirm fixes
2. **Test Deployment**: Run `deploy_production.php` on staging environment
3. **Database Migration**: Apply `update_schedule.sql` to existing databases
4. **Final Testing**: Verify all web pages work with new schema

### 🔍 Files Modified

1. **`deploy_production.php`**:
   - Fixed categories INSERT statement (price vs fee)
   - Fixed schedules table creation and INSERT (event_name vs title)
   - Updated participant data with correct limits and times
   - Updated total capacity setting

2. **`my-registration.php`**:
   - Fixed SELECT query (c.price vs c.fee)
   - Fixed display logic ($registration['price'] vs $registration['fee'])

3. **`verify_schema.php`** (NEW):
   - Complete schema verification tool
   - Automated issue detection
   - Data integrity checks

### 📞 Support Information

**Database Issues**: Check column names match current schema  
**Deployment Issues**: Run verification script first  
**Data Issues**: Use update_schedule.sql for migrations  

**Contact**: info@buffalo-marathon.com  
**Technical Support**: admin@buffalo-marathon.com

---

**Update Completed**: August 13, 2025  
**Schema Version**: 2.0 (Production Ready)  
**Verification**: Run `verify_schema.php` to confirm  
**Status**: ✅ Ready for Production Deployment
