# Balance Fix - Quick Test Guide

## Step 1: Apply the Database Fix

Run this SQL script in your database:

```sql
-- Fix existing negative amounts in transactions
UPDATE bank_transactions SET amount = ABS(amount) WHERE transaction_type_id = 8 AND amount < 0;
UPDATE bank_transactions SET amount = ABS(amount) WHERE transaction_type_id = 5 AND amount < 0;
UPDATE bank_transactions SET amount = ABS(amount) WHERE transaction_type_id = 3 AND amount < 0;
UPDATE bank_transactions SET amount = ABS(amount) WHERE transaction_type_id = 7 AND amount < 0;
```

## Step 2: Verify Current Balances

1. **Login to the system**
2. **Navigate to Accounts page**
3. **Check Juan Tres account (SA-000008-2025):**
   - Available Balance should match Current Balance
   - Both should show the correct balance after all transactions

## Step 3: Test New Transfer

1. **Go to Fund Transfer page**
2. **Make a test transfer:**
   - From: Your account (e.g., SA-000008-2025)
   - To: Another account
   - Amount: ₱10.00
   - Note the current balance before transfer

3. **After transfer, verify:**
   - Your balance decreased by ₱25.00 (₱10.00 + ₱15.00 fee)
   - Recipient balance increased by ₱10.00

## Step 4: Check Transaction History

1. **Navigate to Transaction History**
2. **Verify the display:**
   - ✅ Transfer Out shows: **-₱10.00** (red, negative)
   - ✅ Service Charge shows: **-₱15.00** (red, negative)
   - ✅ Transfer In shows: **+₱10.00** (green, positive)

## Step 5: Verify Balance Sync

1. **Check Account Details page**
2. **Verify:**
   - Available Balance = Current Balance
   - Balance reflects all transactions correctly
   - No discrepancies between displayed balance and transaction history

## Expected Results

### Before Fix:
- ❌ Balances not syncing properly
- ❌ Transfer Out might show positive instead of negative
- ❌ Service charges not reducing balance correctly

### After Fix:
- ✅ Available Balance = Current Balance
- ✅ Transfer Out reduces balance (shows negative)
- ✅ Service Charge reduces balance (shows negative)
- ✅ Transfer In increases balance (shows positive)
- ✅ All transactions properly affect account balance

## Quick Verification Query

Run this in your database to check if the fix worked:

```sql
-- Check Juan Tres account balance
SELECT 
    a.account_number,
    a.account_id,
    COALESCE(SUM(
        CASE tt.type_name
            WHEN 'Deposit' THEN t.amount
            WHEN 'Transfer In' THEN t.amount
            WHEN 'Interest Payment' THEN t.amount
            WHEN 'Withdrawal' THEN -t.amount
            WHEN 'Transfer Out' THEN -t.amount
            WHEN 'Service Charge' THEN -t.amount
            WHEN 'Loan Payment' THEN -t.amount
            ELSE 0
        END
    ), 0) AS calculated_balance
FROM customer_accounts a
LEFT JOIN bank_transactions t ON a.account_id = t.account_id
LEFT JOIN transaction_types tt ON t.transaction_type_id = tt.transaction_type_id
WHERE a.account_number = 'SA-000008-2025'
GROUP BY a.account_id, a.account_number;
```

This should match the balance shown in the UI.

## Troubleshooting

### If balances still don't match:
1. Clear browser cache
2. Re-run the SQL fix script
3. Check for any transactions with negative amounts:
   ```sql
   SELECT * FROM bank_transactions WHERE amount < 0;
   ```
4. Verify transaction type IDs are correct in your database

### If new transfers don't work:
1. Check PHP error logs
2. Verify the Customer.php file was saved correctly
3. Test with a small amount first (₱1.00)

## Success Criteria
- ✅ All transaction amounts in database are positive
- ✅ Balance calculations show correct values
- ✅ New transfers work correctly
- ✅ Transaction history displays with correct signs
- ✅ Available Balance = Current Balance
