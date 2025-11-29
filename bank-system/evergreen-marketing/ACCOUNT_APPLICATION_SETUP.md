# Account Application System Setup

## Overview
This system handles account applications submitted through `evergreen_form.php` and stores them in the BankingDB database.

## Database Setup

### Step 1: Run the SQL Script
Execute the SQL script to create the account applications table:

```bash
# In phpMyAdmin or MySQL command line:
```

Run this file: `sql/account_application_simple.sql`

Or manually execute:

```sql
USE BankingDB;

CREATE TABLE IF NOT EXISTS account_applications (
    application_id INT AUTO_INCREMENT PRIMARY KEY,
    application_number VARCHAR(50) UNIQUE NOT NULL,
    application_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    date_of_birth DATE NOT NULL,
    street_address VARCHAR(255) NOT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    zip_code VARCHAR(20) NOT NULL,
    ssn VARCHAR(50) NOT NULL,
    id_type VARCHAR(50) NOT NULL,
    id_number VARCHAR(100) NOT NULL,
    employment_status VARCHAR(50) NOT NULL,
    employer_name VARCHAR(150),
    job_title VARCHAR(100),
    annual_income DECIMAL(15,2),
    account_type VARCHAR(50) NOT NULL,
    additional_services TEXT,
    terms_accepted BOOLEAN DEFAULT FALSE,
    privacy_acknowledged BOOLEAN DEFAULT FALSE,
    marketing_consent BOOLEAN DEFAULT FALSE,
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    reviewed_at DATETIME,
    INDEX idx_application_number (application_number),
    INDEX idx_email (email),
    INDEX idx_status (application_status),
    INDEX idx_submitted_at (submitted_at)
);
```

## Files Involved

### 1. **evergreen_form.php**
- The front-end form where users submit account applications
- Collects personal info, employment details, and account preferences
- Submits data via AJAX to `submit_account_application.php`

### 2. **submit_account_application.php**
- Backend PHP script that processes form submissions
- Validates and sanitizes input data
- Generates unique application number (format: APP-YYYY-#####)
- Inserts application into database
- Returns success/error response

### 3. **view_applications.php**
- Admin page to view all submitted applications
- Shows statistics (total, pending, approved, rejected)
- Displays applications in a table with key information
- Access via: `http://localhost/Evergreen-M/bank-system/evergreen-marketing/view_applications.php`

## Application Data Captured

### Personal Information
- First Name, Last Name
- Email, Phone Number
- Date of Birth

### Address
- Street Address
- City, State, Zip Code

### Identity Verification
- SSN (Social Security Number)
- ID Type (Driver's License, Passport, State ID)
- ID Number

### Employment
- Employment Status
- Employer Name
- Job Title
- Annual Income

### Account Preferences
- Account Type: Checking, Savings, or Both
- Additional Services: Debit card, Online banking, Mobile banking, Overdraft protection

### Terms & Agreements
- Terms and Conditions accepted
- Privacy Policy acknowledged
- Marketing consent

## Application Number Format

Applications are assigned unique numbers in this format:
```
APP-2025-00001
APP-2025-00002
...
```

Format: `APP-[YEAR]-[5-digit-sequence]`

## Application Status

Applications can have three statuses:
- **pending**: Newly submitted, awaiting review
- **approved**: Application has been approved
- **rejected**: Application has been rejected

## Testing

### Test the Form Submission
1. Open `evergreen_form.php` in your browser
2. Fill out all required fields
3. Select account type (Checking, Savings, or Both)
4. Choose additional services
5. Accept terms and conditions
6. Click "Submit"
7. You should see a success modal with the application reference number

### View Submitted Applications
1. Open `view_applications.php` in your browser
2. You should see all submitted applications in a table
3. Statistics cards show counts by status

## Database Connection

The system uses these database credentials (configured in `submit_account_application.php`):
```php
$host = "localhost";
$user = "root"; 
$pass = ""; 
$db = "BankingDB";
```

Make sure your database credentials match your local setup.

## Troubleshooting

### Issue: "Database connection failed"
- Check that XAMPP MySQL is running
- Verify database credentials in `submit_account_application.php`
- Ensure BankingDB database exists

### Issue: "Table doesn't exist"
- Run the SQL script: `sql/account_application_simple.sql`
- Check that you're using the correct database (BankingDB)

### Issue: Form submission not working
- Check browser console for JavaScript errors
- Verify `submit_account_application.php` path is correct
- Check PHP error logs in XAMPP

## Next Steps

To extend this system, you can:
1. Add email notifications when applications are submitted
2. Create an approval workflow for admin users
3. Link approved applications to actual bank accounts
4. Add document upload functionality
5. Implement application search and filtering
