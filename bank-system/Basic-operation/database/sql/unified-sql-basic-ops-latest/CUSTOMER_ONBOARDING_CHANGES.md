# Customer Onboarding System - Changes & Enhancements

**Date:** November 29, 2025  
**Module:** Customer Account Creation & Onboarding  
**Database:** BankingDB

---

## Overview

This document details all changes made to the customer onboarding system to improve user experience, data validation, and system flexibility. The primary focus was on implementing flexible contact verification (email OR phone) and robust duplicate detection.

---

## 1. Duplicate Name Prevention

### Problem

Customers with identical first name and last name combinations could create multiple accounts, even with different email addresses.

### Solution Implemented

#### Backend API Enhancement

**File:** `api/customer/check-duplicate-name.php`

Created dedicated endpoint to check for duplicate customer names:

```php
// Checks for existing customers with same first + last name (case-insensitive)
SELECT customer_id, first_name, last_name
FROM bank_customers
WHERE LOWER(TRIM(first_name)) = LOWER(:first_name)
AND LOWER(TRIM(last_name)) = LOWER(:last_name)
```

**Features:**

- Case-insensitive comparison
- Whitespace trimming
- Real-time validation via AJAX
- Returns customer ID and name if duplicate found

#### Frontend Validation Enhancement

**File:** `public/customer-onboarding-details.html`

**Visual Feedback:**

1. **Prominent Error Banner**

   - Fixed position at top of page
   - Red gradient background with shadow
   - Bounce-in animation
   - Auto-displays customer's full name in error
   - Shows: "Cannot proceed: Customer with name '[Name]' already exists"

2. **Field Highlighting**

   - Both first name and last name fields get red borders
   - Error message appears below last name field
   - Visual feedback clears when user starts typing

3. **Real-time Checking**
   - Validates on blur (when user leaves field)
   - Also checks on input with 600ms debounce
   - Prevents typing lag while providing quick feedback

**Validation Logic:**

```javascript
// Primary check: API call on form submit
if (result.exists) {
  // Show errors and BLOCK submission
  return; // STOP SUBMISSION
}

// Secondary check: Flag-based validation
if (window.duplicateNameExists === true) {
  // Show error and BLOCK submission
  return; // STOP SUBMISSION
}
```

**Bypass Prevention:**

- Double-check validation on form submit
- Re-verifies with API before allowing submission
- Both checks must pass to proceed
- Cannot bypass by changing other fields

---

## 2. Flexible Contact Verification (Email OR Phone)

### Problem

Original system required BOTH email and phone number, but verification process only needed ONE method. This created unnecessary friction for users who only had one contact method.

### Solution Implemented

#### Validation Logic Changes

**File:** `api/customer/create-final.php`

**Before:**

```php
$requiredFields = [
    'first_name', 'last_name', ..., 'email', 'mobile_number', ...
];
```

**After:**

```php
$requiredFields = [
    'first_name', 'last_name', ..., // email & mobile_number REMOVED
];

// At least one contact method required
$hasEmail = !empty($mappedData['email']);
$hasPhone = !empty($mappedData['mobile_number']);

if (!$hasEmail && !$hasPhone) {
    $errors['contact'] = "At least one contact method (email or phone number) is required";
}
```

#### Database Schema Modification

**Table:** `bank_customers`

**Change:**

```sql
-- Before: email NOT NULL
ALTER TABLE bank_customers MODIFY COLUMN email VARCHAR(255) NULL;

-- Maintained unique constraint (allows NULL)
ADD UNIQUE KEY idx_email (email) USING BTREE;
```

**Result:**

- Email can now be NULL
- Unique constraint still prevents duplicate emails
- Multiple NULL values allowed (for phone-only customers)

#### Conditional Data Insertion

**Email Table Insert:**

```php
// Only insert if email was provided and verified
if ($hasEmail) {
    INSERT INTO emails (customer_id, email, is_primary, created_at)
    VALUES (:customer_id, :email, 1, NOW());
}
```

**Phone Table Insert:**

```php
// Only insert if phone was provided and verified
if ($hasPhone) {
    INSERT INTO phones (customer_id, phone_number, phone_type, is_primary, created_at)
    VALUES (:customer_id, :phone_number, 'mobile', 1, NOW());
}
```

**Bank Customers Table Insert:**

```php
$customerEmail = $hasEmail ? $mappedData['email'] : null;

INSERT INTO bank_customers (first_name, middle_name, last_name, email, password_hash, created_at)
VALUES (:first_name, :middle_name, :last_name, :email, :password_hash, NOW());
```

#### Duplicate Detection Updates

**Email Duplicate Check:**

```php
// Only check if email is provided
if ($hasEmail) {
    SELECT customer_id FROM emails WHERE email = :email LIMIT 1;
}
```

**Phone Duplicate Check:**

```php
// Only check if phone is provided
if ($hasPhone) {
    SELECT customer_id FROM phones WHERE phone_number = :phone LIMIT 1;
}
```

---

## 3. Error Handling Improvements

### Problem

PHP errors were returning HTML instead of JSON, causing "Unexpected token '<'" errors in frontend JavaScript.

### Solutions Implemented

#### Custom Error Handler

**File:** `api/customer/create-final.php`

```php
// Set error handler to ensure JSON responses
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("PHP Error [$errno]: $errstr in $errfile on line $errline");
    if (!headers_sent()) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Server error occurred',
            'debug' => [
                'error' => $errstr,
                'file' => $errfile,
                'line' => $errline
            ]
        ]);
        exit;
    }
});
```

**Benefits:**

- Catches all PHP errors/warnings
- Always returns JSON (never HTML)
- Includes debug information for troubleshooting
- Proper HTTP status codes

#### Enhanced Exception Handling

```php
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'error_code' => $e->getCode(),
        'debug' => [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}
```

#### Frontend Error Detection

**File:** `assets/js/customer-onboarding-review.js`

```javascript
// Check if response is JSON before parsing
const contentType = response.headers.get("content-type");
if (!contentType || !contentType.includes("application/json")) {
  const text = await response.text();
  console.error("Non-JSON response from server:", text);
  throw new Error(
    "Server returned an error. Please check if XAMPP is running..."
  );
}
```

---

## 4. Data Type Handling

### Problem

Session data stored `emails` and `phones` as arrays or objects, causing "Array to string conversion" errors when binding to SQL parameters.

### Solution Implemented

#### Helper Function

**File:** `api/customer/create-final.php`

```php
// Extract string from potential array/object
function extractString($value) {
    if (is_array($value) && !empty($value)) {
        $first = $value[0];
        if (is_array($first)) {
            return $first['value'] ?? $first['email'] ?? $first['phone'] ?? $first[0] ?? '';
        } elseif (is_object($first)) {
            return $first->value ?? $first->email ?? $first->phone ?? '';
        }
        return (string)$first;
    } elseif (is_object($value)) {
        return $value->value ?? $value->email ?? $value->phone ?? '';
    }
    return $value;
}
```

**Usage:**

```php
'email' => extractString($data['emails'] ?? $data['email'] ?? null),
'mobile_number' => extractString($data['phones'] ?? $data['mobile_number'] ?? null),
```

**Handles:**

- Arrays with string values
- Arrays with object/array values
- Objects with various property names
- Nested data structures
- NULL/empty values

#### Phone Number Formatting

**File:** `assets/js/customer-onboarding-review.js`

```javascript
function formatPhoneNumber(phoneNumber) {
  if (!phoneNumber) return "Not provided";

  // Convert to string if it's not already (handle objects/arrays)
  let phoneStr = phoneNumber;
  if (typeof phoneNumber === "object") {
    if (phoneNumber.phone) {
      phoneStr = phoneNumber.phone;
    } else if (phoneNumber.number) {
      phoneStr = phoneNumber.number;
    } else {
      phoneStr = JSON.stringify(phoneNumber);
    }
  } else if (typeof phoneNumber !== "string") {
    phoneStr = String(phoneNumber);
  }

  // Format if it starts with +
  if (phoneStr.startsWith("+")) {
    const cleaned = phoneStr.replace(/\D/g, "");
    if (cleaned.length >= 10) {
      return `+${cleaned.slice(0, 2)} ${cleaned.slice(2, 5)} ${cleaned.slice(
        5,
        8
      )} ${cleaned.slice(8)}`;
    }
  }

  return phoneStr;
}
```

---

## 5. System Behavior Matrix

### Account Creation Scenarios

| Scenario       | Email Provided | Phone Provided | Result                                           |
| -------------- | -------------- | -------------- | ------------------------------------------------ |
| **Scenario 1** | ✅ Yes         | ❌ No          | ✅ Account created with email login              |
| **Scenario 2** | ❌ No          | ✅ Yes         | ✅ Account created with phone login              |
| **Scenario 3** | ✅ Yes         | ✅ Yes         | ✅ Account created with both methods             |
| **Scenario 4** | ❌ No          | ❌ No          | ❌ Error: "At least one contact method required" |

### Duplicate Detection Scenarios

| Check Type              | When Performed                       | Action if Duplicate           |
| ----------------------- | ------------------------------------ | ----------------------------- |
| **Name (First + Last)** | Real-time (blur/input) + Form submit | Block submission, show banner |
| **Email**               | Form submit (if email provided)      | Block submission, show error  |
| **Phone**               | Form submit (if phone provided)      | Block submission, show error  |

### Data Storage Scenarios

| Contact Method | bank_customers.email | emails table | phones table |
| -------------- | -------------------- | ------------ | ------------ |
| **Email only** | Stored               | 1 record     | 0 records    |
| **Phone only** | NULL                 | 0 records    | 1 record     |
| **Both**       | Stored               | 1 record     | 1 record     |

---

## 6. Files Modified

### Backend Files

1. **`api/customer/create-final.php`**

   - Added error handler for JSON responses
   - Added extractString() helper function
   - Modified validation logic (email/phone optional)
   - Added conditional duplicate checking
   - Added conditional data insertion
   - Enhanced exception handling

2. **`api/customer/check-duplicate-name.php`**
   - Existing file (no changes, already implemented)

### Frontend Files

1. **`public/customer-onboarding-details.html`**

   - Enhanced checkDuplicateName() function
   - Added showDuplicateNameBanner() function
   - Added hideDuplicateNameBanner() function
   - Enhanced form submission validation
   - Added real-time duplicate checking on input
   - Enhanced visual error feedback

2. **`assets/js/customer-onboarding-review.js`**
   - Enhanced formatPhoneNumber() function
   - Added content-type check before JSON parsing
   - Better error handling for non-JSON responses

### Database Changes

1. **`bank_customers` table**
   - Modified `email` column from NOT NULL to NULL
   - Maintained unique constraint on email

---

## 7. Testing Checklist

### Duplicate Name Detection

- [x] Same name (different email) → Blocked
- [x] Same name (case variations) → Blocked
- [x] Same name (extra spaces) → Blocked
- [x] Banner appears with correct name
- [x] Fields highlighted in red
- [x] Error clears when typing
- [x] Cannot bypass by changing email

### Contact Method Flexibility

- [x] Email only verification → Account created
- [x] Phone only verification → Account created
- [x] Both verified → Account created
- [x] Neither verified → Error message
- [x] Duplicate email → Blocked
- [x] Duplicate phone → Blocked

### Error Handling

- [x] PHP errors return JSON (not HTML)
- [x] Array to string errors resolved
- [x] Phone number formatting works
- [x] Proper error messages displayed
- [x] Debug information in logs

---

## 8. Database Queries Reference

### Check Duplicate Name

```sql
SELECT customer_id, first_name, last_name
FROM bank_customers
WHERE LOWER(TRIM(first_name)) = LOWER(?)
AND LOWER(TRIM(last_name)) = LOWER(?);
```

### Check Duplicate Email

```sql
SELECT customer_id
FROM emails
WHERE email = ?
LIMIT 1;
```

### Check Duplicate Phone

```sql
SELECT customer_id
FROM phones
WHERE phone_number = ?
LIMIT 1;
```

### Insert Customer (Email + Phone)

```sql
INSERT INTO bank_customers (first_name, middle_name, last_name, email, password_hash, created_at)
VALUES (?, ?, ?, ?, ?, NOW());

INSERT INTO emails (customer_id, email, is_primary, created_at)
VALUES (?, ?, 1, NOW());

INSERT INTO phones (customer_id, phone_number, phone_type, is_primary, created_at)
VALUES (?, ?, 'mobile', 1, NOW());
```

### Insert Customer (Email Only)

```sql
INSERT INTO bank_customers (first_name, middle_name, last_name, email, password_hash, created_at)
VALUES (?, ?, ?, ?, ?, NOW());

INSERT INTO emails (customer_id, email, is_primary, created_at)
VALUES (?, ?, 1, NOW());
-- phones table: NO INSERT
```

### Insert Customer (Phone Only)

```sql
INSERT INTO bank_customers (first_name, middle_name, last_name, email, password_hash, created_at)
VALUES (?, ?, ?, NULL, ?, NOW());

-- emails table: NO INSERT
INSERT INTO phones (customer_id, phone_number, phone_type, is_primary, created_at)
VALUES (?, ?, 'mobile', 1, NOW());
```

---

## 9. Migration Notes

### For Production Deployment

1. **Database Migration:**

   ```sql
   -- Make email nullable
   ALTER TABLE bank_customers
   MODIFY COLUMN email VARCHAR(255) NULL;
   ```

2. **Existing Data:**

   - No impact on existing customers (all have emails)
   - New customers can use phone-only registration

3. **Rollback Plan:**
   ```sql
   -- Revert email to NOT NULL (only if no phone-only customers exist)
   UPDATE bank_customers SET email = CONCAT('customer', customer_id, '@placeholder.com') WHERE email IS NULL;
   ALTER TABLE bank_customers MODIFY COLUMN email VARCHAR(255) NOT NULL;
   ```

---

## 10. Future Enhancements

### Potential Improvements

1. **Multi-factor Authentication:**

   - Require both email AND phone for high-value accounts
   - Optional SMS 2FA for transactions

2. **Contact Preference:**

   - Let customers choose preferred contact method
   - Send notifications to preferred channel

3. **Duplicate Detection:**

   - Add fuzzy matching for similar names (e.g., "John Smith" vs "Jon Smith")
   - Check for common typos or spelling variations

4. **Verification Status:**

   - Track which contact method was verified
   - Allow customers to add/verify additional contacts later

5. **Login Flexibility:**
   - Allow login with either email or phone number
   - Auto-detect input type (email vs phone format)

---

## 11. Navigation & User Flow

### Success Modal Redirect

**Files Modified:**

- `public/customer-onboarding-review.html`
- `assets/js/customer-onboarding-review.js`

**Change:** Updated "Back to Home" button to redirect to employee dashboard after successful account creation.

**Before:**

```javascript
// Redirect to login page
window.location.href = "/Evergreen/bank-system/evergreen-marketing/login.php";
// OR
window.location.href = "../index.html";
```

**After:**

```javascript
// Redirect to employee dashboard
window.location.href = "employee-dashboard.html";
```

**Implementation:**

1. **HTML File** (`customer-onboarding-review.html`):

   ```javascript
   successModalEl.addEventListener("shown.bs.modal", function () {
     const backHomeBtn = document.querySelector(".btn-back-home");
     if (backHomeBtn) {
       backHomeBtn.addEventListener("click", function () {
         window.location.href = "employee-dashboard.html";
       });
     }
   });
   ```

2. **JS File** (`customer-onboarding-review.js`):
   ```javascript
   function goToLogin() {
     sessionStorage.clear();
     window.location.href = "employee-dashboard.html";
   }
   ```

**Rationale:**

- Employees creating customer accounts should return to their dashboard
- Maintains workflow continuity for bank staff
- Reduces navigation steps for tellers/admins

**Implementation Details:**

- Triggered when success modal is shown
- Executes on "Back to Home" button click
- Relative path keeps navigation within Basic-operation/public directory

---

## 12. Account Maintenance & Service Fees

### Problem

Accounts with zero balance need to be tracked and charged maintenance fees if they remain inactive for extended periods.

### Solution Implemented

**Files Modified:**

- `api/reports/get-account-statistics.php`
- `api/accounts/process-account-maintenance.php` (new)

### Automatic Status Updates

**Trigger:** Every time the reports page is loaded

**Logic:**

1. **Account reaches 0 balance:**

   - Status automatically changes from `active` to `below_maintaining`
   - `below_maintaining_since` date is recorded

2. **Account balance becomes positive:**

   - Status automatically changes from `below_maintaining` back to `active`
   - `below_maintaining_since` date is cleared

3. **Account below maintaining for 6+ months:**
   - Automatic monthly charge of PHP 100.00
   - Charge occurs every 30 days after 6-month threshold
   - Transaction type: "Monthly Maintenance Fee"

### Implementation Details

**Status Update Query:**

```php
// Update to below_maintaining when balance <= 0
UPDATE customer_accounts
SET account_status = 'below_maintaining',
    below_maintaining_since = CURDATE()
WHERE account_id = :account_id AND account_status = 'active'
```

**Service Fee Charge Query:**

```php
// Charge 100 pesos monthly after 6 months below maintaining
INSERT INTO bank_transactions
(account_id, transaction_type_id, amount, transaction_date, description, created_at)
VALUES
(:account_id, :type_id, 100.00, NOW(),
 'Monthly maintenance fee for account below maintaining balance', NOW())
```

**Fee Calculation Logic:**

```php
$belowSince = new DateTime($account['below_maintaining_since']);
$now = new DateTime();
$interval = $belowSince->diff($now);
$monthsDiff = $interval->m + ($interval->y * 12);

if ($monthsDiff >= 6) {
    // Check if 30 days passed since last charge
    $lastFeeDate = new DateTime($account['last_service_fee_date']);
    $daysSinceLastCharge = $lastFeeDate->diff($now)->days;

    if ($daysSinceLastCharge >= 30) {
        // Charge the fee
    }
}
```

### Database Fields Used

**customer_accounts table:**

- `account_status` - Tracks current account state
- `below_maintaining_since` - Records date when balance dropped to 0
- `last_service_fee_date` - Tracks last monthly fee charge date
- `monthly_service_fee` - Default 100.00

### New Transaction Type

**transaction_types table:**

- `type_name`: "Monthly Maintenance Fee"
- `description`: "Monthly fee for accounts below maintaining balance for 6+ months"

### Service Fee and Account Lifecycle Timeline

| Months Below Maintaining | Status                | Action                                      |
| ------------------------ | --------------------- | ------------------------------------------- |
| 0 months                 | `below_maintaining`   | Status changed when balance = 0             |
| 1-4 months               | `below_maintaining`   | No fees charged, monitoring period          |
| 5 months                 | `flagged_for_removal` | **WARNING: Account flagged for closure**    |
| 5+ months                | `flagged_for_removal` | PHP 100 monthly fee starts                  |
| 6 months                 | `closed`              | **Account archived and closed permanently** |

**Note:** After 6 months, account is moved to `archived_customer_accounts` table and marked as `closed` with `is_locked = 1`.

### Account Archival Process (6 Months)

When an account reaches 6 months below maintaining balance:

1. **Archive Creation:**

   - Full account details copied to `archived_customer_accounts` table
   - Final balance recorded
   - Archive timestamp and reason logged

2. **Account Closure:**

   - `account_status` set to `closed`
   - `is_locked` set to `1`
   - Account remains in `customer_accounts` but inaccessible

3. **Transaction Blocking:**
   - Deposits: Rejected with "Account is closed"
   - Withdrawals: Rejected with "Account is closed"
   - Transfers: Rejected with "Account is closed"

**Archive Table Structure:**

```sql
CREATE TABLE archived_customer_accounts (
    archive_id INT AUTO_INCREMENT PRIMARY KEY,
    account_id INT NOT NULL,
    customer_id INT NOT NULL,
    account_number VARCHAR(30) NOT NULL,
    final_balance DECIMAL(18,2) DEFAULT 0.00,
    below_maintaining_since DATE,
    flagged_for_removal_date DATE,
    original_status ENUM('active','below_maintaining','flagged_for_removal','closed'),
    archive_reason VARCHAR(255),
    archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    -- ... all other account fields ...
);
```

### Balance Calculation Update

Updated to include Monthly Maintenance Fee:

```php
CASE tt.type_name
    WHEN 'Deposit' THEN t.amount
    WHEN 'Transfer In' THEN t.amount
    WHEN 'Interest Payment' THEN t.amount
    WHEN 'Loan Disbursement' THEN t.amount
    WHEN 'Withdrawal' THEN -t.amount
    WHEN 'Transfer Out' THEN -t.amount
    WHEN 'Service Charge' THEN -t.amount
    WHEN 'Loan Payment' THEN -t.amount
    WHEN 'Monthly Maintenance Fee' THEN -t.amount  // NEW
    ELSE 0
END
```

### Process Flow

```
Account Balance Check (on reports page load)
    ↓
Is balance <= 0?
    ↓ YES
Status = active? → Update to "below_maintaining" + Record date
    ↓
How long below maintaining?
    ↓
├─ 0-4 months: Keep status = "below_maintaining", no fees
│
├─ 5 months:
│   └─ Update to "flagged_for_removal"
│   └─ Set closure_warning_date
│   └─ Start charging PHP 100/month
│
└─ 6+ months:
    └─ Archive account to archived_customer_accounts
    └─ Set status = "closed", is_locked = 1
    └─ Block all transactions (deposits, withdrawals, transfers)

    ↓ NO (balance > 0)

Status = below_maintaining OR flagged_for_removal?
    → Update to "active" + Clear dates
```

### Code Implementation

**File:** `api/reports/get-account-statistics.php`

```php
// 5 months: Flag for removal
if ($monthsDiff >= 5 && $monthsDiff < 6 && $account['account_status'] === 'below_maintaining') {
    UPDATE customer_accounts
    SET account_status = 'flagged_for_removal',
        closure_warning_date = CURDATE()
    WHERE account_id = :account_id;
}

// 6 months: Archive and close
if ($monthsDiff >= 6 && $account['account_status'] !== 'closed') {
    archiveAccount($db, $accountId, $currentBalance);
}
```

**File:** `api/employee/process-deposit.php` & `process-withdrawal.php`

```php
// Block transactions on closed accounts
if ($account['account_status'] === 'closed') {
    throw new Exception('Account is closed and cannot accept transactions');
}

if ($account['account_status'] === 'flagged_for_removal') {
    throw new Exception('Account is flagged for removal. Please contact customer service.');
}
```

### Testing Scenarios

1. **New account with 0 balance:**

   - Status should immediately change to "below_maintaining"
   - No fees or flags for first 4 months

2. **Account at 0 for 5 months:**

   - Status changes to "flagged_for_removal"
   - `closure_warning_date` is set
   - First PHP 100 fee charged
   - Customer can still deposit to recover account

3. **Account at 0 for 6 months:**

   - Account archived to `archived_customer_accounts`
   - Status set to "closed"
   - `is_locked` set to 1
   - All transactions blocked permanently

4. **Account recovers (deposit made before 6 months):**

   - Status changes back to "active"
   - `below_maintaining_since` cleared
   - `closure_warning_date` cleared
   - No more charges

5. **Flagged account trying to withdraw:**

   - Error: "Account is flagged for removal. Please contact customer service."

6. **Closed account trying to deposit:**

   - Error: "Account is closed and cannot accept transactions"
   - Two charges total (month 6 and month 7)

7. **Account recovers (deposit made):**
   - Status changes back to "active"
   - below_maintaining_since cleared
   - No more charges

---

## 13. Critical Fix: Customer Account Linking (Online vs Walk-In Systems)

### Problem Identified

**Date:** November 29, 2025

Accounts created through the **online customer onboarding system** were not appearing in the **walk-in (employee) system** queries. This was causing data inconsistency between the two systems.

**Root Cause:**

- Online system (`create-final.php`) was creating accounts in `customer_accounts` table
- Walk-in system (`open-account.php`) was also creating accounts in `customer_accounts` table
- **BUT** the walk-in system uses `customer_linked_accounts` table to query accounts
- The online system was **NOT** creating entries in `customer_linked_accounts`
- Result: Online accounts were "invisible" to walk-in queries

### Database Schema Analysis

**Redundant/Confusing Tables:**

```
accounts              (81 rows)  - Accounting system (General Ledger Chart of Accounts)
account_types         (80 rows)  - Accounting account types (Assets, Liabilities, etc.)
bank_accounts         (0 rows)   - DEPRECATED/UNUSED
customer_accounts     (3 rows)   - Banking system customer accounts ✓ ACTIVE
bank_account_types    (6 rows)   - Banking account types (Savings, Checking, etc.) ✓ ACTIVE
customer_linked_accounts (0→3)   - Links customers to their accounts ✓ REQUIRED
```

**Correct Usage:**

- **Accounting Module:** Uses `accounts` + `account_types`
- **Banking Module:** Uses `customer_accounts` + `bank_account_types` + `customer_linked_accounts`

### Solution Implemented

**File:** `api/customer/create-final.php`

Added `customer_linked_accounts` entry creation after account creation:

```php
// Create customer account
$stmt = $db->prepare("
    INSERT INTO customer_accounts (customer_id, account_number, account_type_id, interest_rate, created_at)
    VALUES (:customer_id, :account_number, :account_type_id, :interest_rate, NOW())
");
$stmt->execute();

// Get the new account ID
$accountId = $db->lastInsertId();

// Link the account to the customer (CRITICAL: Required for account queries)
$stmt = $db->prepare("
    INSERT INTO customer_linked_accounts (customer_id, account_id, is_active, linked_at)
    VALUES (:customer_id, :account_id, 1, NOW())
");
$stmt->bindParam(':customer_id', $customerId);
$stmt->bindParam(':account_id', $accountId);
$stmt->execute();
```

### Data Migration (Backfill)

Fixed existing accounts missing links:

```sql
-- Find accounts without links
SELECT ca.account_id, ca.account_number, ca.customer_id
FROM customer_accounts ca
LEFT JOIN customer_linked_accounts cla ON ca.account_id = cla.account_id
WHERE cla.link_id IS NULL;

-- Result: 3 accounts found (SA-6837-2025, SA-9526-2025, SA-4460-2025)

-- Backfill missing links
INSERT INTO customer_linked_accounts (customer_id, account_id, is_active, linked_at)
SELECT customer_id, account_id, 1, created_at
FROM customer_accounts
WHERE account_id NOT IN (SELECT account_id FROM customer_linked_accounts);

-- Result: 3 rows inserted
```

### Impact & Testing

**Before Fix:**

- Online accounts: Created in `customer_accounts` ✓
- Walk-in queries: Use `customer_linked_accounts` JOIN
- Result: Online accounts NOT visible in walk-in system ✗

**After Fix:**

- Online accounts: Created in both `customer_accounts` + `customer_linked_accounts` ✓
- Walk-in queries: Find all accounts ✓
- Result: Complete data consistency ✓

**Query Pattern Used by Walk-In System:**

```php
SELECT ca.account_id, ca.account_number, ca.customer_id
FROM customer_accounts ca
INNER JOIN customer_linked_accounts cla ON ca.account_id = cla.account_id
WHERE cla.customer_id = :customer_id
AND cla.is_active = 1
```

### Verification Steps

1. **Check for orphaned accounts:**

   ```sql
   SELECT COUNT(*) as orphaned_accounts
   FROM customer_accounts ca
   LEFT JOIN customer_linked_accounts cla ON ca.account_id = cla.account_id
   WHERE cla.link_id IS NULL;
   ```

   Expected result: `0`

2. **Verify all accounts have links:**

   ```sql
   SELECT
       (SELECT COUNT(*) FROM customer_accounts) as total_accounts,
       (SELECT COUNT(*) FROM customer_linked_accounts WHERE is_active = 1) as linked_accounts;
   ```

   Expected result: Both columns should match

3. **Test account visibility:**
   - Create account via online system
   - Check if it appears in walk-in system queries
   - Verify account can be used for deposits/withdrawals

### Tables Relationship Diagram

```
bank_customers (customer data)
    ↓ customer_id
customer_accounts (account data)
    ↓ account_id
customer_linked_accounts (CRITICAL LINK)
    ↓ Enables queries like:
       - "Show all accounts for this customer"
       - "Verify customer owns this account"
       - "List active accounts for transfers"
```

### Prevention

**Future Development Guidelines:**

1. Always create `customer_linked_accounts` entry when creating `customer_accounts`
2. Use transactions to ensure both inserts succeed or both fail
3. Check both systems (online + walk-in) when testing account creation
4. Add database constraint to enforce link requirement (optional)

**Recommended Constraint:**

```sql
-- Ensure every account has at least one active link
-- (Note: May need trigger or application-level enforcement)
```

---

## 14. Address Data Population in bank_customers Table

### Problem Identified

**Date:** November 29, 2025

The `bank_customers` table has redundant address-related columns (`address`, `city_province`, `contact_number`, `birthday`) that were showing NULL values for customers created through the online onboarding system.

**Root Cause:**

- Online system was only populating the normalized `addresses` table
- The `bank_customers` table has denormalized address columns that weren't being filled
- This caused inconsistency for queries that read from `bank_customers` directly

### Database Schema Analysis

**Redundant Address Storage:**

```
bank_customers table (denormalized):
- address          (street address)
- city_province    (city + province combined)
- contact_number   (phone number)
- birthday         (date of birth)

addresses table (normalized):
- address_line
- barangay_id → barangays table
- city_id → cities table
- province_id → provinces table

customer_profiles table:
- date_of_birth

phones table:
- phone_number
```

### Solution Implemented

**File:** `api/customer/create-final.php`

Updated the `bank_customers` INSERT to populate all address-related fields:

**Before:**

```php
INSERT INTO bank_customers (first_name, middle_name, last_name, email, password_hash, created_at)
VALUES (:first_name, :middle_name, :last_name, :email, :password_hash, NOW())
```

**After:**

```php
// Get city and province names
$stmt = $db->prepare("SELECT city_name FROM cities WHERE city_id = :city_id");
$stmt->execute();
$cityName = $stmt->fetch()['city_name'] ?? '';

$stmt = $db->prepare("SELECT province_name FROM provinces WHERE province_id = :province_id");
$stmt->execute();
$provinceName = $stmt->fetch()['province_name'] ?? '';

// Build full address and city_province
$fullAddress = $mappedData['address_line'];
$cityProvince = trim($cityName . ', ' . $provinceName, ', ');

INSERT INTO bank_customers (
    first_name, middle_name, last_name, email, password_hash,
    address, city_province, contact_number, birthday, created_at
) VALUES (
    :first_name, :middle_name, :last_name, :email, :password_hash,
    :address, :city_province, :contact_number, :birthday, NOW()
)
```

### Data Migration (Backfill)

Fixed existing customers with NULL address data:

```sql
-- Backfill address data from normalized tables
UPDATE bank_customers bc
INNER JOIN addresses a ON bc.customer_id = a.customer_id
INNER JOIN cities c ON a.city_id = c.city_id
INNER JOIN provinces p ON a.province_id = p.province_id
LEFT JOIN phones ph ON bc.customer_id = ph.customer_id AND ph.is_primary = 1
LEFT JOIN customer_profiles cp ON bc.customer_id = cp.customer_id
SET
    bc.address = a.address_line,
    bc.city_province = CONCAT(c.city_name, ', ', p.province_name),
    bc.contact_number = CASE WHEN ph.phone_number = 'Array' THEN NULL ELSE ph.phone_number END,
    bc.birthday = cp.date_of_birth
WHERE bc.address IS NULL
AND a.address_line IS NOT NULL;

-- Result: 5 customers updated (customer_id: 6, 7, 11, 12, 13)
```

### Data Flow

**Online Customer Onboarding:**

```
Step 1: Customer Details
  ↓ Collects: name, address, birthday, contact

Step 2: Security & Verification
  ↓ Collects: email/phone, password

Step 3: Review & Submit
  ↓
create-final.php
  ↓
INSERT INTO bank_customers (name, address, city_province, contact_number, birthday) ✓
INSERT INTO addresses (address_line, barangay_id, city_id, province_id) ✓
INSERT INTO customer_profiles (date_of_birth, gender, etc.) ✓
INSERT INTO emails (email) ✓ (if provided)
INSERT INTO phones (phone_number) ✓ (if provided)
INSERT INTO customer_accounts (account_number, account_type) ✓
INSERT INTO customer_linked_accounts (customer_id, account_id) ✓
```

### Fields Populated

| Field            | Source                             | Example                      |
| ---------------- | ---------------------------------- | ---------------------------- |
| `address`        | `address_line` from form           | "14 Titus st., Interville 3" |
| `city_province`  | Lookup from `cities` + `provinces` | "Quezon City, NCR"           |
| `contact_number` | `mobile_number` from verification  | "+639155904899"              |
| `birthday`       | `date_of_birth` from form          | "2004-09-20"                 |

### Verification

**Check if all customers have address data:**

```sql
SELECT
    customer_id,
    first_name,
    last_name,
    address,
    city_province,
    contact_number,
    birthday
FROM bank_customers
WHERE customer_id IN (6, 7, 11, 12, 13);
```

**Expected Result:** All fields populated (except contact_number may be NULL if not provided)

### Impact

**Before Fix:**

- `bank_customers.address` = NULL
- `bank_customers.city_province` = NULL
- `bank_customers.contact_number` = NULL
- `bank_customers.birthday` = NULL
- Data only in normalized tables (`addresses`, `phones`, `customer_profiles`)

**After Fix:**

- All fields populated in `bank_customers` ✓
- Data also maintained in normalized tables ✓
- Redundant storage ensures compatibility with both query patterns ✓

---

## 15. Account Archive System & Transaction Blocking

### Problem

**Date:** November 29, 2025

Accounts below maintaining balance for extended periods needed automatic closure and archival to maintain data integrity while preserving historical records.

### Solution Implemented

**Files Modified/Created:**

- `database/sql/create_archived_accounts_table.sql` (new)
- `api/reports/get-account-statistics.php` (updated)
- `api/employee/process-deposit.php` (updated)
- `api/employee/process-withdrawal.php` (already had checks)

### Archive Lifecycle

**Stage 1: Below Maintaining (0-4 months)**

- Account status: `below_maintaining`
- Transactions: Allowed
- Fees: None
- Customer action: Can deposit to restore account

**Stage 2: Flagged for Removal (5 months)**

- Account status: `flagged_for_removal`
- Transactions: Allowed but warned
- Fees: PHP 100/month starts
- Customer warning: "Account flagged for removal, please contact customer service"
- `closure_warning_date` recorded

**Stage 3: Closed & Archived (6+ months)**

- Account status: `closed`
- Account flag: `is_locked = 1`
- Transactions: **BLOCKED** (deposits, withdrawals, transfers all rejected)
- Data: Copied to `archived_customer_accounts` table
- Customer action: Account permanently inaccessible

### Archive Table Schema

```sql
CREATE TABLE archived_customer_accounts (
    archive_id INT AUTO_INCREMENT PRIMARY KEY,

    -- Original account data
    account_id INT NOT NULL,
    customer_id INT NOT NULL,
    account_number VARCHAR(30) NOT NULL,
    account_type_id INT NOT NULL,

    -- Financial data
    final_balance DECIMAL(18,2) DEFAULT 0.00,
    maintaining_balance_required DECIMAL(10,2) DEFAULT 500.00,
    monthly_service_fee DECIMAL(10,2) DEFAULT 100.00,

    -- Timeline tracking
    below_maintaining_since DATE,
    flagged_for_removal_date DATE,
    closure_warning_date DATE,

    -- Archive metadata
    archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    archive_reason VARCHAR(255),
    original_status ENUM('active','below_maintaining','flagged_for_removal','closed'),

    INDEX idx_account_number (account_number),
    INDEX idx_customer_id (customer_id)
);
```

### Transaction Blocking

**Deposits Blocked:**

```php
if ($account['account_status'] === 'closed') {
    throw new Exception('Account is closed and cannot accept deposits');
}
if ($account['account_status'] === 'flagged_for_removal') {
    throw new Exception('Account is flagged for removal. Please contact customer service.');
}
```

**Withdrawals Blocked:**

```php
if ($account['account_status'] === 'closed') {
    throw new Exception('Account is closed');
}
if ($account['account_status'] === 'flagged_for_removal') {
    throw new Exception('Account is flagged for removal. Withdrawals restricted.');
}
```

### Archive Query Examples

**View all archived accounts:**

```sql
SELECT
    account_number,
    final_balance,
    DATEDIFF(archived_at, below_maintaining_since) as days_below_maintaining,
    archive_reason,
    archived_at
FROM archived_customer_accounts
ORDER BY archived_at DESC;
```

**Find customer's archived accounts:**

```sql
SELECT
    a.account_number,
    a.final_balance,
    a.archived_at,
    CONCAT(bc.first_name, ' ', bc.last_name) as customer_name
FROM archived_customer_accounts a
INNER JOIN bank_customers bc ON a.customer_id = bc.customer_id
WHERE a.customer_id = :customer_id;
```

**Count accounts by closure reason:**

```sql
SELECT
    archive_reason,
    COUNT(*) as total_archived
FROM archived_customer_accounts
GROUP BY archive_reason;
```

### Impact on Reports

**Statistics Updated:**

- `closed_accounts` now includes archived accounts
- `flagged_for_removal` shown separately
- Archive count available for auditing

**Report Query:**

```sql
SELECT
    COUNT(*) as total_accounts,
    SUM(CASE WHEN account_status = 'active' THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN account_status = 'below_maintaining' THEN 1 ELSE 0 END) as below_maintaining,
    SUM(CASE WHEN account_status = 'flagged_for_removal' THEN 1 ELSE 0 END) as flagged,
    SUM(CASE WHEN account_status = 'closed' THEN 1 ELSE 0 END) as closed,
    (SELECT COUNT(*) FROM archived_customer_accounts) as archived
FROM customer_accounts;
```

### Data Preservation

**What's Archived:**
✓ Complete account details  
✓ Final balance at closure  
✓ All timeline dates  
✓ Account type and settings  
✓ Closure reason and timestamp

**What Remains in customer_accounts:**
✓ Account record (for foreign key integrity)  
✓ Status = 'closed', is_locked = 1  
✓ Transaction history remains in bank_transactions

**What's Deleted:**
✗ Nothing is deleted - full audit trail maintained

---

## Support & Troubleshooting

### Common Issues

**Issue:** "Array to string conversion" error

- **Cause:** Session data contains nested arrays/objects
- **Solution:** extractString() function handles this automatically

**Issue:** "Unexpected token '<'" in JSON parsing

- **Cause:** PHP error returning HTML
- **Solution:** Custom error handler ensures JSON responses

**Issue:** Duplicate name check not working

- **Cause:** Database connection issue or API endpoint not accessible
- **Solution:** Check XAMPP running, verify API path, check browser console

**Issue:** Customer can't proceed even with valid data

- **Cause:** Duplicate name exists, validation preventing submission
- **Solution:** This is intentional - customer needs different name

**Issue:** Account not being charged maintenance fee

- **Cause:** Account hasn't been below maintaining for 6 full months yet
- **Solution:** Check below_maintaining_since date - must be 6+ months old

**Issue:** Account charged multiple times in same month

- **Cause:** last_service_fee_date not updating properly
- **Solution:** Verify last_service_fee_date field is being updated after each charge

**Issue:** Accounts created online don't show in walk-in system (FIXED)

- **Cause:** Missing customer_linked_accounts entry
- **Solution:** Already fixed in create-final.php. For old data, run backfill query above

**Issue:** Customer has account but can't see it / "Account not found"

- **Cause:** Account exists in customer_accounts but not linked in customer_linked_accounts
- **Solution:** Run backfill query or manually insert link

---

## 16. Employee Table Integration (HRIS ↔ Banking System)

### Problem

Two separate employee tables existed with no relationship:

1. **`employee` table** - HRIS system with 25 employees (hire_date, department, position, etc.)
2. **`bank_employees` table** - Banking login system with 5 employees (username, password, role)

**Issues:**

- HRIS employees couldn't access banking system
- Banking employees had no connection to HR records
- Data duplication and inconsistency
- Name/email mismatches between systems

### Solution Implemented

#### Database Schema Update

**Added Foreign Key Relationship:**

```sql
ALTER TABLE bank_employees
ADD COLUMN hris_employee_id INT NULL AFTER employee_id,
ADD CONSTRAINT fk_bank_emp_hris
    FOREIGN KEY (hris_employee_id)
    REFERENCES employee(employee_id)
    ON DELETE SET NULL
    ON UPDATE CASCADE;
```

**Relationship Details:**

- `bank_employees.hris_employee_id` → `employee.employee_id`
- **NULL allowed** - Banking-only accounts without HRIS records supported
- **ON DELETE SET NULL** - Bank access preserved if HRIS record deleted
- **ON UPDATE CASCADE** - Employee ID changes propagate automatically

#### Unified Data View

**Created View:** `v_bank_employee_details`

```sql
CREATE OR REPLACE VIEW v_bank_employee_details AS
SELECT
    be.employee_id as bank_employee_id,
    be.username,
    be.role,
    be.is_active as bank_account_active,
    be.hris_employee_id,

    -- Use HRIS data if available, fallback to bank_employees data
    COALESCE(e.first_name, be.first_name) as first_name,
    COALESCE(e.last_name, be.last_name) as last_name,
    COALESCE(e.middle_name, '') as middle_name,
    COALESCE(e.email, be.email) as email,
    COALESCE(e.contact_number, '') as contact_number,
    COALESCE(e.address, '') as address,
    COALESCE(e.city, '') as city,
    COALESCE(e.province, '') as province,

    -- HRIS-specific fields
    e.gender,
    e.birth_date,
    e.hire_date,
    e.department_id,
    e.position_id,
    e.employment_status,

    be.created_at as bank_account_created,
    be.updated_at as bank_account_updated
FROM bank_employees be
LEFT JOIN employee e ON be.hris_employee_id = e.employee_id;
```

**Priority Logic:** HRIS data takes precedence over bank_employees data

#### Login API Updates

**File:** `api/auth/employee-login.php`

**Query Enhancement:**

```php
// Join with HRIS employee table
SELECT
    be.employee_id,
    be.username,
    be.password_hash,
    be.email,
    be.hris_employee_id,
    COALESCE(e.first_name, be.first_name) as first_name,
    COALESCE(e.last_name, be.last_name) as last_name,
    COALESCE(e.email, be.email) as employee_email,
    COALESCE(e.contact_number, '') as contact_number,
    be.role,
    be.is_active
FROM bank_employees be
LEFT JOIN employee e ON be.hris_employee_id = e.employee_id
WHERE be.username = :username
```

**Session Data Updated:**

```php
$_SESSION['employee_name'] = $employee['first_name'] . ' ' . $employee['last_name'];
$_SESSION['employee_email'] = $employee['employee_email']; // HRIS email prioritized
$_SESSION['hris_employee_id'] = $employee['hris_employee_id']; // Store HRIS reference
```

### Usage Examples

#### 1. Link Existing Bank Employee to HRIS Record

```sql
-- Example: Link admin user to Juan Santos (HRIS employee #1)
UPDATE bank_employees
SET hris_employee_id = 1
WHERE username = 'admin';
```

#### 2. Grant Bank Access to HRIS Employee

```sql
-- Insert new bank_employees record linked to HRIS
INSERT INTO bank_employees (
    hris_employee_id,
    username,
    password_hash,
    email,
    first_name,
    last_name,
    role,
    is_active
) VALUES (
    5,                                  -- Roberto Garcia from HRIS
    'roberto.garcia',                   -- New username
    '$2y$10$hashedpassword...',         -- Hashed password
    'roberto.garcia@company.com',       -- From HRIS
    'Roberto Antonio',                  -- From HRIS
    'Garcia',                           -- From HRIS
    'teller',                           -- Banking role
    1                                   -- Active
);
```

#### 3. Query All Employees with Bank Access

```sql
SELECT
    bank_employee_id,
    username,
    CONCAT(first_name, ' ', last_name) as full_name,
    email,
    role,
    employment_status,
    CASE
        WHEN hris_employee_id IS NOT NULL THEN 'Linked to HRIS'
        ELSE 'Banking Only'
    END as account_type
FROM v_bank_employee_details
WHERE bank_account_active = 1
ORDER BY last_name, first_name;
```

#### 4. Find HRIS Employees Without Bank Access

```sql
SELECT
    employee_id,
    first_name,
    last_name,
    email,
    employment_status,
    hire_date
FROM employee
WHERE employee_id NOT IN (
    SELECT hris_employee_id
    FROM bank_employees
    WHERE hris_employee_id IS NOT NULL
)
AND employment_status = 'Active'
ORDER BY hire_date DESC;
```

### Benefits

1. **Data Consistency** - Employee info synced between HRIS and Banking
2. **Single Source of Truth** - HRIS `employee` table is authoritative
3. **System Independence** - Both tables remain functional independently
4. **Flexibility** - Banking-only accounts still supported (NULL hris_employee_id)
5. **Audit Trail** - Both systems maintain timestamps
6. **Subsystem Safety** - Other systems using `employee` table unaffected

### Migration Path

**For Existing Systems:**

1. **Run Schema Update:**

   ```bash
   mysql -u root BankingDB < link_bank_employees_to_hris.sql
   ```

2. **Review Existing Employees:**

   ```sql
   SELECT * FROM v_bank_employee_details;
   ```

3. **Link Existing Accounts (Optional):**

   ```sql
   -- Match by email or name
   UPDATE bank_employees be
   INNER JOIN employee e ON be.email = e.email
   SET be.hris_employee_id = e.employee_id;
   ```

4. **Test Login:**
   - Login with existing credentials
   - Verify name displays correctly from HRIS data
   - Check session contains hris_employee_id

### Files Modified

- `database/sql/link_bank_employees_to_hris.sql` - Complete migration script
- `api/auth/employee-login.php` - Updated query with LEFT JOIN to employee table
- Database: Added `hris_employee_id` column to `bank_employees`
- Database: Created `v_bank_employee_details` view

### Testing Checklist

- [x] Schema update applied successfully
- [x] View `v_bank_employee_details` returns data
- [x] Login still works for existing bank employees
- [x] Employee name displays correctly from HRIS (if linked)
- [x] Session contains `hris_employee_id` field
- [x] NULL hris_employee_id employees can still login
- [x] New bank access can be granted to HRIS employees

---

## 17. Employee Name Display & Authentication Fix

### Problem

**Date:** November 29, 2025

Employee name was showing "Username" instead of the actual employee name across multiple pages (reports, transaction-history, employee-transaction, dashboard).

**Root Causes:**

1. **Missing ID attributes** - HTML pages had `<span class="username-text">Username</span>` without `id="employeeName"`
2. **Missing auth-helper.js** - Pages weren't including the authentication helper script
3. **Old session logic** - Dashboard was using deprecated `loadUserInfo()` from sessionStorage
4. **Path issue** - employee-login.php used relative path `../../config/database.php` instead of `__DIR__`

### Solutions Implemented

#### 1. Fixed Database Path in Login API

**File:** `api/auth/employee-login.php`

**Before:**

```php
require_once '../../config/database.php';
```

**After:**

```php
require_once __DIR__ . '/../../config/database.php';
```

**Issue:** Relative paths don't work reliably in all contexts (CLI vs web server). Using `__DIR__` provides absolute path resolution.

#### 2. Updated check-session.php Response

**File:** `api/auth/check-session.php`

**Added hris_employee_id to session response:**

```php
echo json_encode([
    'logged_in' => true,
    'employee' => [
        'id' => $_SESSION['employee_id'] ?? null,
        'username' => $_SESSION['employee_username'] ?? null,
        'name' => $_SESSION['employee_name'] ?? null,
        'role' => $_SESSION['employee_role'] ?? null,
        'email' => $_SESSION['employee_email'] ?? null,
        'hris_employee_id' => $_SESSION['hris_employee_id'] ?? null  // ADDED
    ]
]);
```

#### 3. Added ID Attributes to HTML Pages

**Files Modified:**

- `public/reports.html`
- `public/transaction-history.html`
- `public/employee-transaction.html`

**Before:**

```html
<span class="username-text me-2">Username</span>
```

**After:**

```html
<span class="username-text me-2" id="employeeName">Username</span>
```

#### 4. Added auth-helper.js to All Pages

**Files Modified:**

- `public/reports.html`
- `public/transaction-history.html`
- `public/employee-transaction.html`

**Added script tag:**

```html
<script src="../assets/js/auth-helper.js"></script>
<script src="../assets/js/[page-specific].js"></script>
```

**Load Order:** auth-helper.js must load BEFORE page-specific scripts.

#### 5. Updated JavaScript Files

**Files Modified:**

- `assets/js/employee-dashboard.js`
- `assets/js/reports.js`
- `assets/js/transaction-history.js`
- `assets/js/employee-transaction.js`

**Before (dashboard):**

```javascript
document.addEventListener("DOMContentLoaded", async function () {
  await initAuthentication(); // Function doesn't exist
  updateDateTime();
  loadUserInfo(); // Deprecated sessionStorage method
});
```

**After (all pages):**

```javascript
document.addEventListener("DOMContentLoaded", async function () {
  // Check authentication and update employee display
  const employee = await checkAuthentication();
  if (employee) {
    updateEmployeeDisplay(employee);
  }

  // Page-specific initialization
  // ...
});
```

**Key Functions from auth-helper.js:**

```javascript
// Checks session and redirects if not logged in
async function checkAuthentication() {
  const response = await fetch("../api/auth/check-session.php");
  const result = await response.json();

  if (!result.logged_in) {
    window.location.href = "employee-login.html";
    return null;
  }

  return result.employee;
}

// Updates employee name display
function updateEmployeeDisplay(employee) {
  const employeeNameElement = document.getElementById("employeeName");
  if (employeeNameElement && employee) {
    employeeNameElement.textContent = employee.name || "Employee";
  }
}
```

#### 6. Updated Dashboard Welcome Message

**File:** `public/employee-dashboard.html`

**Before:**

```html
<h1 class="welcome-title">Welcome Back!</h1>
```

**After:**

```html
<h1 class="welcome-title">
  Welcome Back, <span id="dashboardEmployeeName">Employee</span>!
</h1>
```

**File:** `assets/js/employee-dashboard.js`

**Added:**

```javascript
const employee = await checkAuthentication();
if (employee) {
  updateEmployeeDisplay(employee);

  // Update dashboard welcome message
  const dashboardNameElement = document.getElementById("dashboardEmployeeName");
  if (dashboardNameElement) {
    dashboardNameElement.textContent = employee.name || "Employee";
  }
}
```

### Test Employees Created

**Employee 1:**

- **Username:** maria.santos
- **Password:** Test123!
- **Name:** Maria Santos
- **HRIS ID:** 26
- **Bank ID:** 8
- **Role:** Teller

**Employee 2:**

- **Username:** john.delacruz
- **Password:** Test123!
- **Name:** John Dela Cruz
- **HRIS ID:** 27
- **Bank ID:** 9
- **Role:** Teller

**Creation Script:** `utils/create_test_employee.php`

```php
$password_hash = password_hash('Test123!', PASSWORD_BCRYPT);

INSERT INTO bank_employees (
    hris_employee_id, username, password_hash,
    email, first_name, last_name, role, is_active, employee_name
) VALUES (
    :hris_employee_id, :username, :password_hash,
    :email, :first_name, :last_name, :role, 1, :employee_name
);
```

### Data Flow

**Login Process:**

```
1. User enters username/password
   ↓
2. employee-login.php validates credentials
   ↓
3. JOIN bank_employees + employee (HRIS)
   ↓
4. COALESCE(hris.first_name, bank.first_name) as first_name
   ↓
5. Session created with employee_name = "First Last"
   ↓
6. Response includes employee.name from HRIS
```

**Page Load:**

```
1. Page loads → DOMContentLoaded fires
   ↓
2. checkAuthentication() calls check-session.php
   ↓
3. Session returns employee object with name
   ↓
4. updateEmployeeDisplay(employee) called
   ↓
5. document.getElementById("employeeName").textContent = employee.name
   ↓
6. "Maria Santos" displays instead of "Username"
```

### Files Modified Summary

**Backend:**

- ✅ `api/auth/employee-login.php` - Fixed database path
- ✅ `api/auth/check-session.php` - Added hris_employee_id to response
- ✅ `utils/create_test_employee.php` - Script to create test employees

**Frontend HTML:**

- ✅ `public/employee-dashboard.html` - Added dashboardEmployeeName span
- ✅ `public/reports.html` - Added id="employeeName", included auth-helper.js
- ✅ `public/transaction-history.html` - Added id="employeeName", included auth-helper.js
- ✅ `public/employee-transaction.html` - Added id="employeeName", included auth-helper.js

**Frontend JavaScript:**

- ✅ `assets/js/employee-dashboard.js` - Uses checkAuthentication(), updates welcome message
- ✅ `assets/js/reports.js` - Added checkAuthentication() call
- ✅ `assets/js/transaction-history.js` - Added checkAuthentication() call
- ✅ `assets/js/employee-transaction.js` - Added checkAuthentication() call
- ✅ `assets/js/auth-helper.js` - Already exists with checkAuthentication() and updateEmployeeDisplay()

### Testing

**Verification Steps:**

1. ✅ Login as maria.santos or john.delacruz
2. ✅ Dashboard shows "Welcome Back, Maria Santos!" (or John Dela Cruz)
3. ✅ Top-right corner shows employee name on all pages
4. ✅ Navigate to Reports → Shows employee name
5. ✅ Navigate to Transaction History → Shows employee name
6. ✅ Navigate to Transactions → Shows employee name

**Test File Created:** `public/test-auth.html`

Displays complete session info and employee data for debugging.

---

## 18. Withdrawal Maintaining Balance Enforcement

### Problem

**Date:** November 29, 2025

Withdrawals were allowing customers to bring their balance below the required maintaining balance (PHP 500), which should be prevented to protect both the customer and the bank.

**Issues:**

- Customers could withdraw down to PHP 0
- No clear error messages about maintaining balance requirements
- System only warned but didn't prevent problematic withdrawals
- Customers already below maintaining could withdraw more funds

### Solution Implemented

**File:** `api/employee/process-withdrawal.php`

#### Enhanced Validation Logic

**Rule 1: Prevent withdrawals below maintaining balance**

```php
// Check if withdrawal would bring balance below maintaining requirement
if ($newBalance < $maintainingBalance && $previousBalance >= $maintainingBalance) {
    $deficit = $maintainingBalance - $newBalance;
    throw new Exception(
        'Withdrawal denied. This would bring your balance to PHP ' . number_format($newBalance, 2) . ', ' .
        'which is PHP ' . number_format($deficit, 2) . ' below the required maintaining balance of PHP ' .
        number_format($maintainingBalance, 2) . '. ' .
        'Maximum withdrawal allowed: PHP ' . number_format($previousBalance - $maintainingBalance, 2)
    );
}
```

**Error Example:**

> Withdrawal denied. This would bring your balance to PHP 300.00, which is PHP 200.00 below the required maintaining balance of PHP 500.00. Maximum withdrawal allowed: PHP 1,500.00

**Rule 2: Block withdrawals when already below maintaining**

```php
// Check if balance is already below maintaining and withdrawal would make it worse
if ($previousBalance < $maintainingBalance && $newBalance < $previousBalance) {
    throw new Exception(
        'Withdrawal denied. Your current balance (PHP ' . number_format($previousBalance, 2) . ') ' .
        'is already below the maintaining balance of PHP ' . number_format($maintainingBalance, 2) . '. ' .
        'Please deposit funds to meet the maintaining balance requirement before making withdrawals.'
    );
}
```

**Error Example:**

> Withdrawal denied. Your current balance (PHP 400.00) is already below the maintaining balance of PHP 500.00. Please deposit funds to meet the maintaining balance requirement before making withdrawals.

**Rule 3: Prevent zero balance withdrawals**

```php
// Check if balance will reach zero
if ($newBalance == 0) {
    throw new Exception(
        'Withdrawal denied. This would result in a zero balance. ' .
        'Minimum balance of PHP ' . number_format($maintainingBalance, 2) . ' must be maintained. ' .
        'Maximum withdrawal allowed: PHP ' . number_format($previousBalance - $maintainingBalance, 2)
    );
}
```

**Error Example:**

> Withdrawal denied. This would result in a zero balance. Minimum balance of PHP 500.00 must be maintained. Maximum withdrawal allowed: PHP 1,500.00

**Rule 4: Prevent negative balance (safety check)**

```php
// Prevent withdrawal that would result in negative balance
if ($newBalance < 0) {
    throw new Exception('Withdrawal would result in negative balance');
}
```

### Withdrawal Scenarios

| Current Balance | Withdrawal Amount | Maintaining Balance | Result                                |
| --------------- | ----------------- | ------------------- | ------------------------------------- |
| PHP 2,000       | PHP 1,600         | PHP 500             | ❌ DENIED - Would leave PHP 400       |
| PHP 2,000       | PHP 1,500         | PHP 500             | ✅ ALLOWED - Leaves PHP 500           |
| PHP 2,000       | PHP 1,400         | PHP 500             | ✅ ALLOWED - Leaves PHP 600           |
| PHP 400         | PHP 100           | PHP 500             | ❌ DENIED - Already below maintaining |
| PHP 400         | PHP 400           | PHP 500             | ❌ DENIED - Would result in zero      |
| PHP 600         | PHP 600           | PHP 500             | ❌ DENIED - Would result in zero      |

### Maximum Withdrawal Calculation

**Formula:**

```php
$maxWithdrawal = $currentBalance - $maintainingBalance;
```

**Examples:**

- Balance: PHP 2,000, Maintaining: PHP 500 → Max: PHP 1,500
- Balance: PHP 1,000, Maintaining: PHP 500 → Max: PHP 500
- Balance: PHP 500, Maintaining: PHP 500 → Max: PHP 0 (no withdrawal allowed)
- Balance: PHP 400, Maintaining: PHP 500 → Max: PHP 0 (already below)

### Error Message Structure

All error messages include:

1. ✅ Clear reason for denial
2. ✅ Current balance amount
3. ✅ Maintaining balance requirement
4. ✅ Calculated maximum withdrawal allowed
5. ✅ Specific deficit amount (if applicable)

### Code Changes

**Before:**

```php
// Check if withdrawal would bring balance below maintaining requirement
if ($newBalance < $maintainingBalance && $previousBalance >= $maintainingBalance) {
    $warnings[] = "This withdrawal will bring your balance below the maintaining balance...";
    $statusUpdate = 'below_maintaining';  // WARNING ONLY, NOT BLOCKING
}

if ($newBalance == 0) {
    $warnings[] = "CRITICAL: Account balance will be ZERO...";  // WARNING ONLY
    $statusUpdate = 'flagged_for_removal';
}
```

**After:**

```php
// BLOCK withdrawals that violate maintaining balance
if ($newBalance < $maintainingBalance && $previousBalance >= $maintainingBalance) {
    throw new Exception('Withdrawal denied...');  // BLOCKING
}

if ($previousBalance < $maintainingBalance && $newBalance < $previousBalance) {
    throw new Exception('Withdrawal denied...');  // BLOCKING
}

if ($newBalance == 0) {
    throw new Exception('Withdrawal denied...');  // BLOCKING
}
```

### Impact on User Experience

**Before Fix:**

- ❌ Customers could withdraw to PHP 0
- ❌ Vague warnings that were easy to ignore
- ❌ Accounts frequently falling below maintaining
- ❌ Excessive service fees accumulating

**After Fix:**

- ✅ Withdrawals blocked with clear explanations
- ✅ Customers know exactly how much they can withdraw
- ✅ Maintains account health automatically
- ✅ Reduces risk of account closure
- ✅ Better customer protection

### Testing Scenarios

**Test 1: Normal withdrawal (above maintaining)**

```
Current: PHP 2,000
Withdraw: PHP 1,000
Result: ✅ Success (leaves PHP 1,000)
```

**Test 2: Withdrawal to exactly maintaining**

```
Current: PHP 2,000
Withdraw: PHP 1,500
Result: ✅ Success (leaves PHP 500)
```

**Test 3: Withdrawal below maintaining**

```
Current: PHP 2,000
Withdraw: PHP 1,600
Result: ❌ Error - "Would bring balance to PHP 400, which is PHP 100 below required PHP 500. Max: PHP 1,500"
```

**Test 4: Withdrawal when already below**

```
Current: PHP 400
Withdraw: PHP 100
Result: ❌ Error - "Balance already below PHP 500. Please deposit first."
```

**Test 5: Withdrawal to zero**

```
Current: PHP 600
Withdraw: PHP 600
Result: ❌ Error - "Would result in zero balance. Max: PHP 100"
```

### Files Modified

- ✅ `api/employee/process-withdrawal.php` - Lines 174-217 (validation logic)

### Related Systems

This works in conjunction with:

- Account status tracking (below_maintaining, flagged_for_removal)
- Monthly service fees (PHP 100 after 5 months)
- Account archival (after 6 months)
- Deposit processing (allows recovery from below maintaining)

---

**Last Updated:** November 29, 2025  
**Version:** 1.5  
**Author:** System Integration Team
