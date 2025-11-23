# Login Fix Guide - Evergreen Bank

## Problem Solved ✅
The login system now works for accounts created through **both** systems:
1. **Marketing System** (bank_users table) - requires Bank ID
2. **Basic-operation System** (bank_customers table) - no Bank ID needed

## What Was Fixed

### 1. Updated Login Logic (`login.php`)
- Made Bank ID field **optional**
- Login now checks **both** `bank_users` and `bank_customers` tables
- If Bank ID is provided → checks `bank_users` first
- If Bank ID is empty or not found → checks `bank_customers`
- Sets all required session variables for both systems

### 2. Session Variables Set
After successful login, these session variables are available:
```php
$_SESSION['customer_id']        // For Basic-operation system
$_SESSION['user_id']            // For marketing system
$_SESSION['email']              // User's email
$_SESSION['first_name']         // First name
$_SESSION['last_name']          // Last name
$_SESSION['full_name']          // Full name
$_SESSION['bank_id']            // Bank ID (empty for Basic-operation users)
```

## How to Test

### Step 1: Verify Your Account Exists
1. Go to: http://localhost/Evergreen/bank-system/evergreen-marketing/test_login.php
2. Enter the email you used during registration
3. Click "Check Account"
4. You should see:
   - ✅ Account Found in bank_customers!
   - Your customer details
   - Your bank account number(s)

### Step 2: Login
1. Go to: http://localhost/Evergreen/bank-system/evergreen-marketing/login.php
2. **Leave Bank ID field empty** (or enter it if you have one)
3. Enter your **email**
4. Enter your **password**
5. Click "SIGN IN"
6. You should be redirected to the dashboard

## Login Scenarios

### Scenario 1: Account Created via Basic-operation
- **Bank ID**: Leave empty
- **Email**: Your registration email
- **Password**: Your registration password
- **Result**: ✅ Login successful

### Scenario 2: Account Created via Marketing System
- **Bank ID**: Your 4-digit bank ID
- **Email**: Your registration email
- **Password**: Your registration password
- **Result**: ✅ Login successful

### Scenario 3: Wrong Password
- **Result**: ❌ "Invalid email or password" error

### Scenario 4: Account Not Found
- **Result**: ❌ "Invalid email or password" error

## Troubleshooting

### Issue: "Invalid email or password"
**Possible causes:**
1. Email is misspelled
2. Password is incorrect
3. Account was not created successfully

**Solution:**
1. Use the test page to verify account exists: `test_login.php?email=your@email.com`
2. If account doesn't exist, create a new one
3. If account exists, try resetting password (if feature available)

### Issue: Login successful but redirected to blank page
**Possible causes:**
1. `viewingpage.php` has errors
2. Session variables not set correctly

**Solution:**
1. Check browser console (F12) for JavaScript errors
2. Check PHP error log: `C:\xampp\php\logs\php_error_log`
3. Verify session variables are set by adding this to `viewingpage.php`:
```php
<?php
session_start();
echo '<pre>';
print_r($_SESSION);
echo '</pre>';
?>
```

### Issue: Can't access customer dashboard
**Possible causes:**
1. `customer_id` session variable not set
2. Database connection issue

**Solution:**
1. Verify `customer_id` is in session (use test above)
2. Check database connection in `db_connect.php`

## Database Structure

### bank_customers (Basic-operation)
```sql
CREATE TABLE bank_customers (
    customer_id INT PRIMARY KEY,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    middle_name VARCHAR(50),
    email VARCHAR(255) UNIQUE,
    password_hash VARCHAR(255),
    created_at TIMESTAMP
);
```

### bank_users (Marketing)
```sql
CREATE TABLE bank_users (
    id INT PRIMARY KEY,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    middle_name VARCHAR(100),
    email VARCHAR(150) UNIQUE,
    password VARCHAR(255),
    bank_id VARCHAR(50),
    is_verified BOOLEAN,
    created_at TIMESTAMP
);
```

## Testing Credentials

### Sample Account (from Sampled_data.sql)
- **Email**: juan.reyes@email.com
- **Password**: password
- **Bank ID**: (not needed for Basic-operation accounts)

### Your New Account
- **Email**: The email you used during registration
- **Password**: The password you created
- **Bank ID**: Leave empty

## Next Steps

1. ✅ Test login with your newly created account
2. ✅ Verify you can access the dashboard
3. ✅ Check that all features work (view accounts, transactions, etc.)
4. ✅ Test creating additional accounts

## Files Modified

1. `bank-system/evergreen-marketing/login.php`
   - Updated login logic to check both tables
   - Made Bank ID optional
   - Added proper session variable handling

2. `bank-system/evergreen-marketing/test_login.php` (NEW)
   - Test page to verify account exists
   - Shows account details and bank accounts

## Support

If you still can't login:
1. Run the test page: `test_login.php?email=your@email.com`
2. Check browser console (F12 → Console)
3. Check PHP error log
4. Verify database has your account:
```sql
SELECT * FROM bank_customers WHERE email = 'your@email.com';
```

## Success! 🎉

You should now be able to:
- ✅ Create accounts via Basic-operation system
- ✅ Login with email and password (no Bank ID needed)
- ✅ Access the customer dashboard
- ✅ View your bank accounts and transactions
