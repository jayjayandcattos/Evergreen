# Referral System Migration

## Overview
This migration adds referral code functionality to the `bank_customers` table.

## Files
- `add_referral_fields.sql` - SQL migration script

## How to Run

### Option 1: Using phpMyAdmin
1. Open phpMyAdmin
2. Select the `bankingdb` database
3. Click on the "SQL" tab
4. Copy and paste the contents of `add_referral_fields.sql`
5. Click "Go" to execute

### Option 2: Using MySQL Command Line
```bash
mysql -u root -p bankingdb < add_referral_fields.sql
```

### Option 3: Using MySQL Workbench
1. Open MySQL Workbench
2. Connect to your database
3. Open the SQL script file
4. Execute the script

## What This Migration Does

Adds the following fields to `bank_customers` table:
- `referral_code` (VARCHAR(20), UNIQUE) - Unique referral code for each customer
- `total_points` (DECIMAL(10,2), DEFAULT 0.00) - Points balance for the customer
- `referred_by_customer_id` (INT, NULL) - ID of the customer who referred this user

Also adds:
- Index on `referral_code` for fast lookups
- Index on `referred_by_customer_id`
- Foreign key constraint linking to `bank_customers(customer_id)`

## Notes
- The foreign key constraint may fail if it already exists. This is safe to ignore.
- Existing customers will have `NULL` referral codes until they are generated on next login or account update.

