# Points & Rewards System Migration Guide
## From evergreen_bank to BankingDB (unified_schema)

### Overview
This guide explains how to migrate the points and rewards system from the old `evergreen_bank` database to the new unified `BankingDB` schema.

### Database Changes Required

#### 1. Database Connection
The `db_connect.php` file needs to be updated to connect to `BankingDB` instead of `evergreen_bank`.

**Current (evergreen_bank):**
```php
$conn = mysqli_connect("localhost", "root", "", "evergreen_bank");
```

**New (BankingDB):**
```php
$conn = mysqli_connect("localhost", "root", "", "BankingDB");
```

#### 2. Table Structure Comparison

The unified schema already includes all necessary tables. Here's the mapping:

| Old Table (evergreen_bank) | New Table (BankingDB) | Status |
|----------------------------|----------------------|--------|
| `bank_customers` | `bank_customers` | ✅ Exists |
| `missions` | `missions` | ✅ Exists |
| `user_missions` | `user_missions` | ✅ Exists |
| `points_history` | `points_history` | ✅ Exists |
| `referrals` | `referrals` | ❌ **MISSING** |

#### 3. Missing Table: referrals

The `referrals` table is NOT in the unified schema but is required for the points system. You need to add it.

**SQL to add referrals table:**
```sql
CREATE TABLE referrals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    referrer_id INT NOT NULL,
    referred_id INT NOT NULL,
    points_earned DECIMAL(10,2) DEFAULT 20.00,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_referral (referred_id),
    INDEX idx_referrer_id (referrer_id),
    INDEX idx_referred_id (referred_id),
    FOREIGN KEY (referrer_id) REFERENCES bank_customers(customer_id) ON DELETE CASCADE,
    FOREIGN KEY (referred_id) REFERENCES bank_customers(customer_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Files That Need Updates

#### 1. db_connect.php
**Location:** `bank-system/evergreen-marketing/db_connect.php`

**Change:**
```php
// OLD
$conn = mysqli_connect("localhost", "root", "", "evergreen_bank");

// NEW
$conn = mysqli_connect("localhost", "root", "", "BankingDB");
```

#### 2. points_api.php
**Location:** `bank-system/evergreen-marketing/points_api.php`

**No changes needed** - This file uses the connection from `db_connect.php`, so it will automatically use the new database once `db_connect.php` is updated.

#### 3. cards/points.php
**Location:** `bank-system/evergreen-marketing/cards/points.php`

**No changes needed** - This file only contains frontend code and uses the API.

#### 4. cards/rewards.php
**Location:** `bank-system/evergreen-marketing/cards/rewards.php`

**No changes needed** - This file only contains frontend code and uses the API.

### Migration Steps

1. **Backup your current data:**
   ```sql
   -- Export data from evergreen_bank
   mysqldump -u root evergreen_bank bank_customers missions user_missions points_history referrals > evergreen_backup.sql
   ```

2. **Add the missing referrals table to BankingDB:**
   ```sql
   USE BankingDB;
   
   CREATE TABLE referrals (
       id INT AUTO_INCREMENT PRIMARY KEY,
       referrer_id INT NOT NULL,
       referred_id INT NOT NULL,
       points_earned DECIMAL(10,2) DEFAULT 20.00,
       created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
       UNIQUE KEY unique_referral (referred_id),
       INDEX idx_referrer_id (referrer_id),
       INDEX idx_referred_id (referred_id),
       FOREIGN KEY (referrer_id) REFERENCES bank_customers(customer_id) ON DELETE CASCADE,
       FOREIGN KEY (referred_id) REFERENCES bank_customers(customer_id) ON DELETE CASCADE
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
   ```

3. **Migrate existing data from evergreen_bank to BankingDB:**
   ```sql
   -- Copy referrals data
   INSERT INTO BankingDB.referrals (id, referrer_id, referred_id, points_earned, created_at)
   SELECT id, referrer_id, referred_id, points_earned, created_at
   FROM evergreen_bank.referrals;
   
   -- Copy missions data (if not already present)
   INSERT IGNORE INTO BankingDB.missions (id, mission_text, points_value, created_at)
   SELECT customer_id, mission_text, points_value, created_at
   FROM evergreen_bank.missions;
   
   -- Copy user_missions data
   INSERT IGNORE INTO BankingDB.user_missions (id, user_id, mission_id, points_earned, completed_at)
   SELECT customer_id, user_id, mission_id, points_earned, completed_at
   FROM evergreen_bank.user_missions;
   
   -- Copy points_history data
   INSERT IGNORE INTO BankingDB.points_history (id, user_id, points, description, transaction_type, created_at)
   SELECT customer_id, user_id, points, description, transaction_type, created_at
   FROM evergreen_bank.points_history;
   ```

4. **Update db_connect.php:**
   Change the database name from `evergreen_bank` to `BankingDB`.

5. **Test the system:**
   - Login to the system
   - Navigate to points.php
   - Check if points are displayed correctly
   - Try collecting a mission
   - Check rewards.php
   - Try redeeming a reward

### Verification Queries

After migration, run these queries to verify data integrity:

```sql
USE BankingDB;

-- Check total points match
SELECT customer_id, total_points FROM bank_customers WHERE total_points > 0;

-- Check missions exist
SELECT COUNT(*) as mission_count FROM missions;

-- Check user missions
SELECT COUNT(*) as completed_missions FROM user_missions;

-- Check points history
SELECT COUNT(*) as history_count FROM points_history;

-- Check referrals
SELECT COUNT(*) as referral_count FROM referrals;
```

### Important Notes

1. **Session Variables:** The system uses `$_SESSION['user_id']` which should map to `bank_customers.customer_id` in BankingDB.

2. **Foreign Keys:** Ensure that all `user_id` references in `user_missions`, `points_history`, and `referrals` tables correspond to valid `customer_id` values in `bank_customers`.

3. **Auto-increment IDs:** The unified schema uses different primary key names:
   - `missions.id` (was `missions.customer_id` in old schema)
   - `points_history.id` (was `points_history.customer_id` in old schema)
   - `user_missions.id` (was `user_missions.customer_id` in old schema)

4. **Testing:** After migration, thoroughly test:
   - Points display
   - Mission collection
   - Reward redemption
   - Points history
   - Referral tracking

### Rollback Plan

If something goes wrong:

1. Restore db_connect.php to use `evergreen_bank`
2. Restore data from backup:
   ```bash
   mysql -u root evergreen_bank < evergreen_backup.sql
   ```

### Support

If you encounter issues:
1. Check error logs in browser console (F12)
2. Check PHP error logs
3. Verify database connection in db_connect.php
4. Ensure all tables exist in BankingDB
5. Verify foreign key constraints are satisfied
