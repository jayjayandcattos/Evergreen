# Maintaining Balance System - Implementation Guide

## Overview

The maintaining balance system enforces a minimum balance requirement on customer accounts to avoid monthly service fees and potential account closure.

## Business Rules

### 1. Maintaining Balance Requirement

- **Minimum Balance**: PHP 500.00
- **Monthly Service Fee**: PHP 100.00
- **Fee Trigger**: When account balance falls below PHP 500.00

### 2. Deposit Restrictions

- **Maximum Single Deposit**: PHP 100.00
- Deposits are processed normally regardless of account status
- Depositing when below maintaining balance can restore account to active status

### 3. Withdrawal Rules & Account Status

#### Account Status Flow:

```
ACTIVE → BELOW_MAINTAINING → FLAGGED_FOR_REMOVAL → CLOSED
  ↑            ↓                      ↓              (Final)
  └─(deposit)──┘          (zero balance or 30 days)
```

#### Status Definitions:

**ACTIVE**

- Balance >= PHP 500.00
- No service fees charged
- Full account functionality

**BELOW_MAINTAINING**

- Balance < PHP 500.00 but > 0
- Monthly service fee of PHP 100.00 charged
- Warning displayed on withdrawals
- Can restore to ACTIVE by depositing to reach PHP 500.00

**FLAGGED_FOR_REMOVAL**

- Balance = PHP 0.00
- Account locked from withdrawals
- Deposits still allowed to reactivate
- Will be CLOSED after 30 days if no deposits made

**CLOSED**

- Balance = PHP 0.00 for 30+ days
- Account permanently closed
- No transactions allowed

### 4. Service Fee Charging

**Monthly Processing** (via cron job):

- Runs daily at 00:01 AM
- Checks all accounts with balance < PHP 500.00
- Charges PHP 100.00 service fee once per month
- Creates 'Service Charge' transaction
- Updates `last_service_fee_date`
- Logs fee in `service_fee_charges` table

**Fee Calculation**:

- First fee charged after 1 day below maintaining balance
- Subsequent fees charged every 30 days
- Fee deducted from account balance via transaction

### 5. Account Closure Process

**Automatic Closure Conditions**:

1. Account flagged for removal for 30+ days
2. Zero balance with no deposits
3. Unable to maintain minimum balance

**Closure Steps**:

1. System flags account when balance reaches zero
2. Sets `closure_warning_date` to current date
3. After 30 days, cron job automatically:
   - Changes status to CLOSED
   - Locks account (`is_locked = 1`)
   - Sets `closure_date`
4. Account cannot be reopened (customer must create new account)

## Database Schema

### Modified Tables

**customer_accounts** - Added columns:

```sql
maintaining_balance_required DECIMAL(10,2) DEFAULT 500.00
monthly_service_fee DECIMAL(10,2) DEFAULT 100.00
below_maintaining_since DATE NULL
account_status ENUM('active', 'below_maintaining', 'flagged_for_removal', 'closed')
last_service_fee_date DATE NULL
closure_warning_date DATE NULL
```

### New Tables

**account_status_history**:

- Tracks all status changes
- Records balance at time of change
- Links to employee who triggered change

**service_fee_charges**:

- Records all service fee transactions
- Links to bank_transactions
- Tracks balance before/after fee

## Installation

### Step 1: Run Database Migration

```bash
# Navigate to database/sql directory
cd c:\xampp\htdocs\SIASIANOVA\Evergreen\bank-system\Basic-operation\database\sql

# Import the migration file
mysql -u root -p BankingDB < add_maintaining_balance_system.sql
```

### Step 2: Set Up Cron Job (Windows Task Scheduler)

**For Windows**:

1. Open Task Scheduler
2. Create New Task:
   - Name: "Evergreen - Maintaining Balance Processor"
   - Trigger: Daily at 00:01 AM
   - Action: Start a program
   - Program: `C:\xampp\php\php.exe`
   - Arguments: `-f "C:\xampp\htdocs\SIASIANOVA\Evergreen\bank-system\Basic-operation\api\cron\process_maintaining_balance.php"`
3. Save and enable task

**For Linux**:

```bash
# Edit crontab
crontab -e

# Add line:
1 0 * * * /usr/bin/php /path/to/Evergreen/bank-system/Basic-operation/api/cron/process_maintaining_balance.php
```

### Step 3: Test the System

**Manual Test Run**:

```bash
# Run the cron script manually
php c:\xampp\htdocs\SIASIANOVA\Evergreen\bank-system\Basic-operation\api\cron\process_maintaining_balance.php
```

## API Changes

### Deposit API (process-deposit.php)

**New Features**:

- Maximum deposit validation (PHP 100.00)
- Cleaner JSON output (no PHP warnings)

**Response**:

```json
{
  "success": true,
  "message": "Deposit processed successfully",
  "data": {
    "transaction_reference": "DP20251128000001",
    "new_balance": "450.00"
  }
}
```

### Withdrawal API (process-withdrawal.php)

**New Features**:

- Maintaining balance checks
- Account status validation
- Warning messages for low balance
- Automatic status updates

**Response with Warnings**:

```json
{
  "success": true,
  "message": "Withdrawal processed successfully",
  "data": {
    "new_balance": "100.00",
    "account_status": "below_maintaining",
    "maintaining_balance": "500.00"
  },
  "warnings": [
    "This withdrawal will bring your balance below the maintaining balance of PHP 500.00",
    "A monthly service fee of PHP 100.00 will be charged"
  ]
}
```

## Monitoring & Reports

### Check Account Statuses

```sql
SELECT
    account_number,
    account_status,
    below_maintaining_since,
    last_service_fee_date,
    closure_warning_date
FROM customer_accounts
WHERE account_status != 'active'
ORDER BY account_status, below_maintaining_since;
```

### View Service Fee History

```sql
SELECT
    ca.account_number,
    sfc.fee_amount,
    sfc.balance_before,
    sfc.balance_after,
    sfc.charge_date,
    sfc.fee_type
FROM service_fee_charges sfc
JOIN customer_accounts ca ON sfc.account_id = ca.account_id
ORDER BY sfc.charge_date DESC
LIMIT 50;
```

### Accounts Flagged for Removal

```sql
SELECT
    account_number,
    closure_warning_date,
    DATEDIFF(CURDATE(), closure_warning_date) as days_flagged
FROM customer_accounts
WHERE account_status = 'flagged_for_removal'
ORDER BY closure_warning_date;
```

## Testing Scenarios

### Scenario 1: Account Falls Below Maintaining Balance

1. Create account with initial deposit of PHP 600.00
2. Withdraw PHP 200.00 (balance = PHP 400.00)
3. Verify warnings displayed
4. Check account status changed to 'below_maintaining'

### Scenario 2: Monthly Service Fee

1. Wait 30 days (or run cron manually)
2. Verify PHP 100.00 fee charged
3. Check transaction created with type 'Service Charge'
4. Verify balance reduced accordingly

### Scenario 3: Account Reaches Zero

1. Account with balance PHP 150.00
2. Withdraw PHP 150.00
3. Verify status changed to 'flagged_for_removal'
4. Verify closure warning displayed

### Scenario 4: Account Closure

1. Account flagged for 30+ days
2. Run cron job
3. Verify account status changed to 'closed'
4. Verify account is locked
5. Attempt withdrawal (should fail)

### Scenario 5: Account Restoration

1. Account with status 'below_maintaining'
2. Deposit to bring balance >= PHP 500.00
3. Verify status changed back to 'active'
4. Verify service fees stop

## Troubleshooting

### Issue: Service fees not charging

**Solution**:

- Check cron job is running
- Verify transaction_types has 'Service Charge' entry
- Check logs in `logs/maintaining_balance.log`

### Issue: Accounts not closing after 30 days

**Solution**:

- Verify cron job runs daily
- Check `closure_warning_date` is set correctly
- Manually run cron script to test

### Issue: Deposit API returning 500 error

**Solution**:

- Check PHP error logs
- Verify database connection
- Ensure all required columns exist in customer_accounts table

## Contact & Support

For questions or issues, contact the development team or refer to the main project documentation.
