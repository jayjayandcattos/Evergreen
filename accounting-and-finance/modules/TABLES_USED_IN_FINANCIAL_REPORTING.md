# Tables Used in Financial Reporting Module

This document lists all database tables used in `financial-reporting.php` and identifies which subsystem each table comes from.

## Database: `BankingDB`

All tables are accessed from the same `BankingDB` database used by all subsystems.

---

## 1. BANK SYSTEM (Basic-operation & Evergreen Marketing)

### `customer_accounts`
- **Subsystem**: Bank System - Basic-operation
- **Purpose**: Stores customer bank account balances
- **Used For**:
  - Balance Sheet: Assets (sum of all customer account balances)
  - Cash Flow Statement: Cash balance calculation
- **Key Fields Used**:
  - `balance` - Account balance
  - `is_locked` - Account status filter

### `bank_accounts`
- **Subsystem**: Bank System - Accounting
- **Purpose**: Stores bank's internal account balances
- **Used For**:
  - Balance Sheet: Assets (sum of all bank account balances)
  - Cash Flow Statement: Cash balance calculation
- **Key Fields Used**:
  - `current_balance` - Current account balance
  - `is_active` - Account status filter

### `bank_transactions`
- **Subsystem**: Bank System - Basic-operation
- **Purpose**: Records all financial transactions (deposits, withdrawals, transfers, interest, loan disbursements)
- **Used For**:
  - Balance Sheet: Loan payments (to calculate outstanding loan balances)
  - Income Statement: Interest income from bank transactions
  - Cash Flow Statement: Deposits, withdrawals, loan disbursements
  - Trial Balance: Deposits (debits), withdrawals (credits), loan disbursements
- **Key Fields Used**:
  - `amount` - Transaction amount
  - `transaction_type_id` - Links to transaction_types
  - `description` - Transaction description for filtering
- **JOIN With**: `transaction_types` table

### `transaction_types`
- **Subsystem**: Bank System - Basic-operation
- **Purpose**: Defines types of transactions (Deposit, Withdrawal, Interest, Loan Payment, etc.)
- **Used For**:
  - Filtering transactions by type (deposit, withdrawal, interest, loan payment)
  - Income Statement: Interest income identification
  - Cash Flow Statement: Deposits vs withdrawals identification
  - Trial Balance: Transaction categorization
- **Key Fields Used**:
  - `transaction_type_id` - Primary key
  - `type_name` - Transaction type name for filtering
- **JOIN With**: `bank_transactions` table

---

## 2. LOAN SUBSYSTEM

### `loan_applications`
- **Subsystem**: Loan Subsystem
- **Purpose**: Stores loan applications and approved loans
- **Used For**:
  - Balance Sheet: Outstanding loan balances (liabilities)
  - Income Statement: Loan interest income estimation (20% annual rate)
- **Key Fields Used**:
  - `loan_amount` - Total loan amount
  - `status` - Loan status filter ('Approved', 'Active', 'Disbursed')
- **Calculation Notes**:
  - Outstanding loans = Total loan amounts - Loan payments made
  - Interest estimated as: `loan_amount * 0.20 / 12` (monthly interest on 20% annual rate)

---

## 3. HRIS-SIA / PAYROLL MODULE

### `payroll_runs`
- **Subsystem**: HRIS-SIA (via Accounting & Finance - Payroll Management)
- **Purpose**: Records payroll processing runs with total payroll expenses
- **Used For**:
  - Income Statement: Payroll expenses
  - Trial Balance: Payroll expenses (debits and credits)
- **Key Fields Used**:
  - `total_net` - Total net pay for all employees in the payroll run
  - `status` - Payroll run status filter ('completed', 'finalized')
- **Calculation Notes**:
  - Expenses = Sum of `total_net` from completed/finalized payroll runs
  - In Trial Balance: Payroll expenses create both debit (expense) and credit (cash/liability) entries

---

## SUMMARY BY REPORT

### Balance Sheet
- **Assets**:
  - `customer_accounts` (balance)
  - `bank_accounts` (current_balance)
- **Liabilities**:
  - `loan_applications` (loan_amount)
  - `bank_transactions` + `transaction_types` (loan_payments)
- **Equity**:
  - Calculated as: Assets - Liabilities (no table used)

### Income Statement
- **Revenue**:
  - `bank_transactions` + `transaction_types` (interest income)
  - `loan_applications` (estimated loan interest)
- **Expenses**:
  - `payroll_runs` (total_net)

### Cash Flow Statement
- **Cash Balance**:
  - `customer_accounts` (balance)
  - `bank_accounts` (current_balance)
- **Operating Cash Flow**:
  - `bank_transactions` + `transaction_types` (deposits - withdrawals)
- **Financing Cash Flow**:
  - `bank_transactions` (loan disbursements)

### Trial Balance
- **Debits**:
  - `bank_transactions` + `transaction_types` (deposits)
  - `bank_transactions` (loan disbursements)
  - `payroll_runs` (total_net)
- **Credits**:
  - `bank_transactions` + `transaction_types` (withdrawals)
  - `bank_transactions` (loan disbursements)
  - `payroll_runs` (total_net)

---

## NOTES

1. **No Accounting & Finance Database Tables Used**:
   - All queries to `accounts`, `account_types`, `journal_lines`, `journal_entries` have been removed
   - Financial reporting now relies entirely on data from other subsystems

2. **All Tables Share Same Database**:
   - All subsystems use `BankingDB` database
   - No cross-database queries needed
   - Single database connection (`$conn`) is used for all queries

3. **Table Existence Checks**:
   - All queries check for table existence before executing: `SHOW TABLES LIKE 'table_name'`
   - This ensures the page doesn't crash if a table doesn't exist

4. **Error Handling**:
   - All queries validate result before calling `fetch_assoc()`
   - Default values (0) are used if queries fail or return no results

