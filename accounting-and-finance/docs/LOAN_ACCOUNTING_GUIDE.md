# Loan Accounting Module - Setup & Usage Guide

## Overview
The Loan Accounting Module provides comprehensive loan management features including loan tracking, payment schedules, filtering, reporting, and audit trails.

## Setup Instructions

### 1. Database Setup
The loan tables are already included in your main `schema.sql`. If you haven't run it yet:

```bash
# In MySQL/phpMyAdmin, run:
source database/schema.sql
```

### 2. Add Sample Data (Optional)
To test the module with sample data:

```bash
# In MySQL/phpMyAdmin, run:
source database/sample_loan_data.sql
```

This will create:
- 5 loan types (Personal, Housing, Vehicle, Emergency, Salary)
- 5 sample employees
- 10 sample loans with various statuses
- Sample payment history

### 3. Access the Module
Navigate to: `http://localhost/Accounting and finance/modules/loan-accounting.php`

## Features

### Dashboard Statistics
- **Total Loans**: Count of all loans in the system
- **Active Loans**: Number of currently active loans
- **Total Amount**: Sum of all loan principal amounts
- **Outstanding Balance**: Total amount still owed

### Loan History Table
Displays all loans with the following information:
- Loan Number
- Borrower Name (Employee External Number)
- Loan Type
- Start Date & Maturity Date (auto-calculated)
- Loan Amount
- Interest Rate
- Outstanding Balance
- Status (Pending, Active, Paid, Defaulted, Cancelled)
- Actions (View Details, Delete)

### Filtering Options
Apply filters to find specific loans:
- **Date Range**: Filter by loan start date (from/to)
- **Status**: Filter by loan status
- **Loan Number**: Search by loan number

### Export & Print
- **Export to Excel**: Export filtered loans to Excel format
- **Print**: Print-friendly version of the loan table

### Audit Trail
View complete audit history of all loan operations:
- Loan creation
- Status changes
- Payments
- Modifications
- Deletions/Restorations

### Loan Details
Click "View Details" on any loan to see:
- Complete loan information
- Borrower details
- Loan terms and conditions
- Payment schedule (if available)
- Transaction history
- Current status and balances

### Soft Delete (Bin Station)
- Deleted loans are marked as "cancelled" status
- Can be restored from the Bin Station module
- Permanent deletion available through Bin Station

## Database Schema

### Loans Table
```sql
- id: Primary key
- loan_no: Unique loan number
- loan_type_id: Foreign key to loan_types
- borrower_external_no: Employee reference number
- principal_amount: Original loan amount
- interest_rate: Annual interest rate (decimal)
- start_date: Loan start date
- term_months: Loan duration in months
- monthly_payment: Calculated monthly payment
- current_balance: Remaining balance
- status: pending, active, paid, defaulted, cancelled
- created_by: User who created the loan
- created_at: Timestamp
```

### Loan Types Table
```sql
- id: Primary key
- code: Unique type code (PL, HL, VL, etc.)
- name: Type name
- max_amount: Maximum loan amount allowed
- max_term_months: Maximum term in months
- interest_rate: Default interest rate
- description: Type description
- is_active: Active status
```

### Loan Payments Table
```sql
- id: Primary key
- loan_id: Foreign key to loans
- payment_date: Date of payment
- amount: Total payment amount
- principal_amount: Principal portion
- interest_amount: Interest portion
- payment_reference: Payment reference number
- journal_entry_id: Link to accounting entry
- created_at: Timestamp
```

## API Endpoints

### `/modules/api/loan-data.php`

Available actions:
- `get_loans`: Retrieve loans with optional filters
- `get_loan_details`: Get detailed loan information
- `get_audit_trail`: View audit history
- `get_statistics`: Get dashboard statistics
- `soft_delete_loan`: Mark loan as cancelled
- `restore_loan`: Restore cancelled loan
- `get_bin_items`: Get cancelled loans
- `permanent_delete_loan`: Hard delete loan

## Usage Examples

### Creating Manual Loans
Use phpMyAdmin or MySQL to insert new loans:

```sql
INSERT INTO loans (
    loan_no, 
    loan_type_id, 
    borrower_external_no, 
    principal_amount, 
    interest_rate, 
    start_date, 
    term_months, 
    monthly_payment, 
    current_balance, 
    status, 
    created_by
) VALUES (
    'LOAN-2024-009',
    1,  -- Personal Loan type
    'EMP001',
    200000.00,
    12.5000,
    '2024-10-27',
    36,
    6700.00,
    200000.00,
    'active',
    1  -- User ID
);
```

### Recording Payments

```sql
INSERT INTO loan_payments (
    loan_id,
    payment_date,
    amount,
    principal_amount,
    interest_amount,
    payment_reference
) VALUES (
    1,  -- Loan ID
    '2024-10-27',
    6700.00,
    4616.67,
    2083.33,
    'PAY-2024-500'
);

-- Update loan balance
UPDATE loans 
SET current_balance = current_balance - 4616.67 
WHERE id = 1;
```

## Status Definitions

- **Pending**: Loan application pending approval
- **Active**: Loan is active and payments are being made
- **Paid**: Loan has been fully paid
- **Defaulted**: Loan is in default (missed payments)
- **Cancelled**: Loan was cancelled/deleted (soft delete)

## Best Practices

1. **Always use loan types**: Define loan types before creating loans
2. **Record all payments**: Keep payment history up to date
3. **Regular reconciliation**: Match loan balances with payment records
4. **Use audit trail**: Review changes and maintain compliance
5. **Backup regularly**: Keep database backups before bulk operations

## Integration Points

The Loan Accounting module integrates with:
- **Journal Entries**: Loan disbursements and payments can be linked to journal entries
- **Employee References**: Borrowers linked to employee records
- **Audit Logs**: All operations logged for compliance
- **Bin Station**: Deleted loans available for restoration

## Troubleshooting

### Issue: "No Loan Data Available"
**Solution**: 
- Ensure database schema is created
- Run `sample_loan_data.sql` for test data
- Check database connection in `config/database.php`

### Issue: Maturity date not showing correctly
**Solution**: Maturity date is calculated as `start_date + term_months`. Ensure both fields are populated.

### Issue: Cannot delete loan
**Solution**: 
- Only non-paid loans should be deleted
- Check user permissions
- Verify loan exists and is not already cancelled

## Future Enhancements

Potential additions for the module:
- Loan application workflow
- Automated payment processing
- Interest calculation automation
- Payment reminders and notifications
- Amortization schedule generation
- Loan restructuring features
- Integration with payroll for salary deduction
- Mobile responsive improvements
- Bulk loan import/export

## Support

For issues or questions:
1. Check this guide first
2. Review the audit trail for operation history
3. Check database logs for errors
4. Refer to main system documentation

---
**Last Updated**: October 2024  
**Module Version**: 1.0  
**Compatible With**: Accounting & Finance System v1.0

