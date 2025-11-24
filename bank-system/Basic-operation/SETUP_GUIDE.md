# Basic-Operation System Setup Guide

## Problem Fixed
The account creation was failing because the `bank_customers` table was missing the `email` column needed for login.

## Solution Applied
1. Added `email` column to `bank_customers` table in unified schema
2. Updated account creation API to store email in `bank_customers`
3. Updated login query to use email directly from `bank_customers`

## Setup Instructions

### Step 1: Database Setup
1. Open phpMyAdmin: http://localhost/phpmyadmin
2. Drop existing database (if any):
   ```sql
   DROP DATABASE IF EXISTS BankingDB;
   ```

3. Run the unified schema:
   - Click "SQL" tab
   - Open file: `accounting-and-finance/database/sql/unified_schema.sql`
   - Copy entire content and paste
   - Click "Go"

4. Run the sample data:
   - Click "BankingDB" database
   - Click "SQL" tab
   - Open file: `accounting-and-finance/database/sql/Sampled_data.sql`
   - Copy entire content and paste
   - Click "Go"

### Step 2: Fix Customer.php (IMPORTANT!)
The `Customer.php` file got corrupted during editing. You need to manually fix it:

1. Open: `bank-system/Basic-operation/operations/app/models/Customer.php`
2. Find the `getCustomerByEmailOrAccountNumber` method (around line 12)
3. Replace the corrupted method with this code:

```php
  public function getCustomerByEmailOrAccountNumber($identifier) {
    $this->db->query("
            SELECT
                c.customer_id,
                c.first_name,
                c.last_name,
                c.email,
                c.password_hash,
                a.account_number
            FROM
                bank_customers c
            LEFT JOIN
                customer_accounts a ON c.customer_id = a.customer_id
            WHERE
                c.email = :emailIdentifier OR a.account_number = :accountIdentifier
            LIMIT 1;
        ");

    if(filter_var($identifier, FILTER_VALIDATE_EMAIL)){
        $email = $identifier;
        $account_number = null;
    } else {
        $email = null;
        $account_number = $identifier;
    }

    $this->db->bind(':emailIdentifier', $email);
    $this->db->bind(':accountIdentifier', $account_number);
    return $this->db->single();

    }
```

**The fixed code is also available in:** `bank-system/Basic-operation/operations/app/models/Customer_FIXED.php`

### Step 3: Test Account Creation
1. Go to: http://localhost/Evergreen/bank-system/Basic-operation/public/customer-onboarding-details.html
2. Fill in all required fields
3. Complete all 3 steps
4. Click "Confirm" on the review page
5. You should see "Account Created Successfully!" with an account number

### Step 4: Test Login
1. Go to: http://localhost/Evergreen/bank-system/evergreen-marketing/login.php
2. Enter the email you used during registration
3. Enter the password you created
4. Click "Login"
5. You should be redirected to the customer dashboard

## What Was Changed

### Files Modified:
1. `accounting-and-finance/database/sql/unified_schema.sql`
   - Added `email` column to `bank_customers` table with UNIQUE constraint

2. `accounting-and-finance/database/sql/Sampled_data.sql`
   - Updated sample bank_customers data to include email addresses

3. `bank-system/Basic-operation/api/customer/create-final.php`
   - Modified INSERT query to include email in `bank_customers` table

4. `bank-system/Basic-operation/operations/app/models/Customer.php`
   - Updated `getCustomerByEmailOrAccountNumber()` to query email from `bank_customers` directly
   - Removed JOIN with `emails` table (email is now in main table)

## Database Schema Changes

### Before:
```sql
CREATE TABLE bank_customers (
    customer_id INT AUTO_INCREMENT PRIMARY KEY,
    last_name VARCHAR(50) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50) DEFAULT NULL,
    password_hash VARCHAR(255) NOT NULL,
    ...
);
```

### After:
```sql
CREATE TABLE bank_customers (
    customer_id INT AUTO_INCREMENT PRIMARY KEY,
    last_name VARCHAR(50) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50) DEFAULT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    ...
);
```

## Troubleshooting

### Error: "An error occurred while creating your account"
- Check browser console (F12) for detailed error messages
- Check PHP error log: `C:\xampp\php\logs\php_error_log`
- Verify database connection in `bank-system/Basic-operation/config/database.php`

### Error: "Email already exists"
- The email is already registered
- Use a different email or delete the existing customer from database

### Login fails after account creation
- Verify the `Customer.php` file was fixed correctly
- Check that email column exists in `bank_customers` table:
  ```sql
  DESCRIBE bank_customers;
  ```

### Account number not showing in success modal
- Check browser console for JavaScript errors
- Verify the API response includes `account_number` field

## Testing Credentials

After running sample data, you can test with these accounts:

| Email | Password | Account Number |
|-------|----------|----------------|
| juan.reyes@email.com | password | SA-XXXX-2024 |
| maria.santos@email.com | password | CHA-XXXX-2024 |

(Password is hashed as: `password`)

## Next Steps

1. Test the complete flow: Registration → Login → Dashboard
2. Verify account creation creates records in all required tables
3. Test login with both email and account number
4. Verify customer can view their accounts after login

## Support

If you encounter issues:
1. Check the browser console (F12 → Console tab)
2. Check PHP error logs
3. Verify database tables exist and have correct structure
4. Ensure XAMPP Apache and MySQL are running
