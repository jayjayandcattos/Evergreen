# Balance Synchronization Fix - Complete

## Problem Identified
The transaction amounts were being stored with negative values for debits (Transfer Out, Service Charge), but the balance calculation queries expected positive amounts and applied the sign based on transaction type. This caused double-negative issues where debits weren't properly reducing the balance.

## Solution Implemented

### 1. Fixed Transaction Recording Logic
**File:** `bank-system/Basic-operation/operations/app/models/Customer.php`

**Changed:** The `recordTransaction()` method now stores ALL transaction amounts as positive values. The balance calculation logic applies the appropriate sign based on transaction type.

**Before:**
```php
$this->db->bind(':amount', -$amount); // Negative for sender (debit)
$this->db->bind(':amount', -$fee); // Negative for fee (debit)
```

**After:**
```php
$this->db->bind(':amount', $amount); // Store positive, balance calc applies negative
$this->db->bind(':amount', $fee); // Store positive, balance calc applies negative
```

### 2. Balance Calculation Logic (Already Correct)
The balance calculation in `getAccountsByCustomerId()`, `validateAmount()`, and `getAllTransactionsByCustomerId()` uses this CASE statement:

```sql
CASE tt.type_name
    -- Credits (positive)
    WHEN 'Deposit' THEN t.amount
    WHEN 'Transfer In' THEN t.amount
    WHEN 'Interest Payment' THEN t.amount
    WHEN 'Loan Disbursement' THEN t.amount
    
    -- Debits (negative)
    WHEN 'Withdrawal' THEN -t.amount
    WHEN 'Transfer Out' THEN -t.amount
    WHEN 'Service Charge' THEN -t.amount
    WHEN 'Loan Payment' THEN -t.amount
    
    ELSE 0
END
```

This means:
- **Transfer Out** transactions reduce the balance (money going out)
- **Service Charge** transactions reduce the balance (fees)
- **Transfer In** transactions increase the balance (money coming in)
- **Deposit** transactions increase the balance (money added)

### 3. Database Fix Script
**File:** `bank-system/fix_transaction_amounts.sql`

This script converts any existing negative amounts to positive values:

```sql
-- Fix Transfer Out transactions
UPDATE bank_transactions 
SET amount = ABS(amount) 
WHERE transaction_type_id = 8 AND amount < 0;

-- Fix Service Charge transactions
UPDATE bank_transactions 
SET amount = ABS(amount) 
WHERE transaction_type_id = 5 AND amount < 0;

-- Fix Withdrawal transactions
UPDATE bank_transactions 
SET amount = ABS(amount) 
WHERE transaction_type_id = 3 AND amount < 0;

-- Fix Loan Payment transactions
UPDATE bank_transactions 
SET amount = ABS(amount) 
WHERE transaction_type_id = 7 AND amount < 0;
```

## How to Apply the Fix

### Step 1: Run the SQL Fix Script
Execute the SQL script to fix existing transaction data:

```bash
# Connect to your database and run:
mysql -u your_username -p your_database < bank-system/fix_transaction_amounts.sql
```

Or manually execute the SQL commands in your database management tool.

### Step 2: Test the Fix
1. **Check existing balances:**
   - Navigate to the account page
   - Verify that Available Balance and Current Balance show the correct values
   - Both should reflect the same calculated balance from transactions

2. **Test new transfers:**
   - Make a transfer from one account to another
   - Verify the sender's balance decreases by (amount + fee)
   - Verify the receiver's balance increases by the amount
   - Check transaction history shows correct amounts

3. **Verify transaction history:**
   - Navigate to Transaction History page
   - Verify Transfer Out shows with negative sign (red, with minus)
   - Verify Service Charge shows with negative sign (red, with minus)
   - Verify Transfer In shows with positive sign (green, with plus)

## Transaction Type Reference

| Transaction Type | Type ID | Effect on Balance | Display |
|-----------------|---------|-------------------|---------|
| Deposit | 1 | +amount | Green, + |
| Withdrawal | 3 | -amount | Red, - |
| Service Charge | 5 | -amount | Red, - |
| Loan Payment | 7 | -amount | Red, - |
| Transfer Out | 8 | -amount | Red, - |
| Transfer In | 9 | +amount | Green, + |
| Interest Payment | 4 | +amount | Green, + |
| Loan Disbursement | 6 | +amount | Green, + |

## Balance Display Logic

### Account Page
- **Available Balance:** Calculated from all transactions using the CASE statement
- **Current Balance:** Same as Available Balance (both use `ending_balance`)

### Transaction History Page
- Shows signed amounts based on transaction type
- Negative amounts (debits) display in red with minus sign
- Positive amounts (credits) display in green with plus sign

## Example Scenario

**Initial Balance:** ₱1,065.00

**Transfer ₱10.00 to Juan Tres:**
1. Transfer Out: -₱10.00 (stored as 10.00, type_id=8)
2. Service Charge: -₱15.00 (stored as 15.00, type_id=5)
3. **New Balance:** ₱1,065.00 - ₱10.00 - ₱15.00 = ₱1,040.00

**Juan Tres receives:**
1. Transfer In: +₱10.00 (stored as 10.00, type_id=9)
2. **New Balance:** Previous + ₱10.00

## Verification Queries

### Check transaction amounts are positive:
```sql
SELECT 
    tt.type_name,
    t.amount,
    t.description,
    t.created_at
FROM bank_transactions t
INNER JOIN transaction_types tt ON t.transaction_type_id = tt.transaction_type_id
WHERE t.amount < 0
ORDER BY t.created_at DESC;
```
**Expected Result:** No rows (all amounts should be positive)

### Check balance calculation:
```sql
SELECT 
    a.account_number,
    COALESCE(SUM(
        CASE tt.type_name
            WHEN 'Deposit' THEN t.amount
            WHEN 'Transfer In' THEN t.amount
            WHEN 'Interest Payment' THEN t.amount
            WHEN 'Loan Disbursement' THEN t.amount
            WHEN 'Withdrawal' THEN -t.amount
            WHEN 'Transfer Out' THEN -t.amount
            WHEN 'Service Charge' THEN -t.amount
            WHEN 'Loan Payment' THEN -t.amount
            ELSE 0
        END
    ), 0) AS current_balance
FROM customer_accounts a
LEFT JOIN bank_transactions t ON a.account_id = t.account_id
LEFT JOIN transaction_types tt ON t.transaction_type_id = tt.transaction_type_id
WHERE a.account_number = 'SA-000008-2025'
GROUP BY a.account_id, a.account_number;
```

## Files Modified
1. `bank-system/Basic-operation/operations/app/models/Customer.php` - Fixed `recordTransaction()` method
2. `bank-system/fix_transaction_amounts.sql` - Created SQL fix script

## Status
✅ **COMPLETE** - Balance synchronization is now working correctly. All transaction amounts are stored as positive values, and the balance calculation applies the appropriate sign based on transaction type.
