# URGENT: Fix Customer.php File

## Problem
The file `bank-system/Basic-operation/operations/app/models/Customer.php` is corrupted and causing a syntax error.

## Solution - Manual Fix Required

### Step 1: Open the File
Open: `bank-system/Basic-operation/operations/app/models/Customer.php`

### Step 2: Find the Corrupted Method
Look for the `getCustomerByEmailOrAccountNumber` method around line 12-44.

It currently looks corrupted like this:
```php
public function getCustomerByEmailOrAccountNumber($identifier) {
    $this->db->query("
            SELECT
                c.customer_id,
                c.first_name,
                c.last_name,
                c.email,
                c.password_hash,
                mber           // <-- CORRUPTED
            FROM
                bank_mers c    // <-- CORRUPTED
            LEFT JOIN
                customer_id    // <-- CORRUPTED
            WHERE
                cier           // <-- CORRUPTED
            LIMIT 1;
        ");

        $email = $identifier;
        $account_number = nul;  // <-- CORRUPTED
    } else {
        $ema                    // <-- CORRUPTED
        $account_numbe          // <-- CORRUPTED
    }
```

### Step 3: Replace with This Fixed Code

**DELETE** the entire corrupted `getCustomerByEmailOrAccountNumber` method (lines 12-44 approximately)

**PASTE** this fixed version:

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

### Step 4: Save the File
Save the file (Ctrl+S or Cmd+S)

### Step 5: Verify the Fix
1. Refresh your browser
2. The syntax error should be gone
3. Try logging in again

## Alternative: Restore from Git

If you have Git version control:
```bash
cd C:\xampp\htdocs\Evergreen
git checkout bank-system/Basic-operation/operations/app/models/Customer.php
```

Then manually apply the fix above.

## What This Method Does

This method is used by the login system to find a customer by either:
1. **Email address** - for Basic-operation accounts
2. **Account number** - for existing bank accounts

The fixed version:
- Queries the `bank_customers` table
- Joins with `customer_accounts` to get account number
- Uses the `email` column directly from `bank_customers` (not from `emails` table)
- Returns customer data including password hash for verification

## After Fixing

Once fixed, you should be able to:
1. ✅ Login with email and password
2. ✅ Access the customer dashboard
3. ✅ View your bank accounts

## Need Help?

If you're still having issues:
1. Check the file has no syntax errors
2. Make sure the closing brace `}` is present
3. Verify the method is inside the `Customer` class
4. Check there are no extra characters or missing semicolons
