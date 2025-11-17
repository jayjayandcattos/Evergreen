# Accounting & Finance System

A comprehensive Enterprise Resource Planning (ERP) and financial management system designed for banking, HRIS, and financial organizations. This unified system integrates banking operations, HRIS (Human Resources Information System), and accounting to process payroll, manage loans, track expenses, and handle all financial operations.

## 🚀 Features

### Core Modules
- **User Authentication & Role Management** - Admin role with full system access
- **Banking Operations** - Customer accounts, transactions, and management
- **HRIS Integration** - Employee management, attendance, recruitment, and onboarding
- **Transaction Reading & Management** - Complete financial transaction viewing and filtering
- **General Ledger** - Double-entry bookkeeping system with journal entries
- **Payroll Management** - Automated payroll processing with HRIS integration
- **Expense Tracking** - Business expense claims and reimbursements
- **Financial Reporting** - Income Statement, Balance Sheet, Cash Flow Statement
- **Loan Accounting** - Loan management and tracking system
- **Chart of Accounts** - Comprehensive account management

### Key Capabilities
- ✅ Modern, responsive UI with Bootstrap 5
- ✅ Role-based access control with session management
- ✅ Real-time financial calculations and reporting
- ✅ Automated journal entry generation
- ✅ Comprehensive transaction filtering and search
- ✅ Advanced audit trails and logging
- ✅ Export functionality for reports and data
- ✅ Mobile-friendly responsive design
- ✅ API endpoints for data integration
- ✅ Database connection testing utilities

## 📋 Requirements

- **PHP** 7.4 or higher
- **MySQL** 5.7 or higher
- **XAMPP** (recommended for local development)
- **Web Server** (Apache/Nginx)

## 🛠️ Installation

### 1. Clone/Download the Project
```bash
# Place the project in your XAMPP htdocs directory
C:\xampp\htdocs\Accounting and finance\
```

### 2. Database Setup
1. Start XAMPP and ensure MySQL is running
2. Open phpMyAdmin (http://localhost/phpmyadmin)
3. Import the unified database schema:
   - Go to the **Import** tab
   - Choose the file `database/unified_schema.sql`
   - Click **Go** to execute
4. **⚠️ CRITICAL:** Insert the admin user and sample data:
   - Go to the **SQL** tab and select the `BankingDB` database
   - Copy and paste the contents of `database/Sampled_data.sql`
   - Click **Go** to execute
   - **Without this step, you cannot log in to the system!**

**Alternative:** Use the automated setup script:
- Navigate to: `http://localhost/Accounting and finance/database/init.php`
- This will create database, schema, and admin user automatically

### 3. Configuration
The database configuration is already set up in `config/database.php` for XAMPP default settings:
- Host: localhost
- Database: BankingDB
- Username: root
- Password: (empty)

### 4. Access the System
Open your browser and navigate to:
```
http://localhost/Accounting and finance/core/login.php
```
Or simply:
```
http://localhost/Accounting and finance/
```
(The root index.php will redirect you to the login page)

## 👥 Demo Accounts

### Administrator
- **Email:** admin@system.com
- **Password:** admin123
- **Access:** Full system access, user management, all modules

## 📁 Project Structure

```
Accounting and finance/
├── assets/
│   ├── css/
│   │   ├── dashboard.css         # Dashboard-specific styles
│   │   ├── style.css             # Main application styles
│   │   └── transaction-reading.css # Transaction module styles
│   └── js/
│       ├── dashboard.js          # Dashboard functionality
│       └── transaction-reading.js # Transaction module JavaScript
├── config/
│   └── database.php              # Database configuration
├── core/
│   ├── dashboard.php             # Main dashboard
│   ├── index.php                 # Core index redirect
│   ├── login.php                 # Login page
│   └── logout.php                # Logout handler
├── database/
│   ├── generate_hash.php         # Password hash generator utility
│   ├── insert_admin.sql          # Admin user creation script
│   ├── schema.sql                # Complete database schema
│   └── Transaction_Data.sql      # Sample transaction data
├── docs/
│   ├── ACCOUNTING_FINANCE_SYSTEM_OVERVIEW.md # System overview
│   ├── INSTALLATION_GUIDE.md     # Detailed installation guide
│   ├── PATH_REFERENCE.md         # File path reference
│   ├── README.md                 # This file
│   └── SETUP.md                  # Quick setup guide
├── includes/
│   └── session.php               # Session management
├── modules/
│   ├── api/
│   │   └── transaction-data.php  # Transaction data API
│   ├── expense-tracking.php      # Expense tracking module
│   ├── financial-reporting.php   # Financial reporting module
│   ├── general-ledger.php        # General ledger module
│   ├── loan-accounting.php        # Loan accounting module
│   ├── payroll-management.php    # Payroll management module
│   └── transaction-reading.php   # Transaction reading module
├── utils/
│   ├── fix_admin_password.php    # Admin password utility
│   └── test_login.php            # Login testing utility
├── index.php                     # Main entry point (redirects to core)
├── test_db_connection.php        # Database connection test
└── README.md                     # This file
```

## 🔧 System Architecture

### Database Schema
The system uses a comprehensive MySQL database with the following key tables:

#### User Management
- `users` - User accounts and authentication
- `roles` - User roles and permissions
- `user_roles` - User-role assignments

#### Employee Reference (HRIS Integration)
- `employee_refs` - External employee reference data
- `employee_benefits` - Employee benefit configurations
- `employee_deductions` - Employee deduction settings

#### Chart of Accounts
- `account_types` - Account type classifications
- `accounts` - Chart of accounts structure
- `account_balances` - Real-time account balances

#### Journal Entries
- `journal_types` - Journal entry type definitions
- `journal_entries` - Journal entry headers
- `journal_lines` - Individual debit/credit lines
- `fiscal_periods` - Accounting periods

#### Payroll Management
- `payroll_runs` - Payroll processing runs
- `payslips` - Individual employee payslips
- `payroll_items` - Payroll line items
- `payroll_deductions` - Payroll deduction calculations

#### Expense Management
- `expense_categories` - Expense category definitions
- `expense_claims` - Business expense claims
- `expense_line_items` - Individual expense items

#### Loan Management
- `loans` - Loan account information
- `loan_payments` - Loan payment records
- `loan_schedules` - Payment schedules

#### System Management
- `audit_logs` - System audit trail
- `system_settings` - Application configuration

### Key Features

#### 1. Transaction Reading & Management
- Comprehensive transaction viewing interface
- Advanced filtering by date, type, status, and account
- Real-time transaction search and display
- Export capabilities for transaction data
- Detailed transaction line item viewing

#### 2. Payroll Management
- Automated payroll calculation
- Integration with HRIS employee data
- Government-mandated deductions (SSS, PhilHealth, Pag-IBIG)
- Digital payslip generation
- Journal entry automation

#### 3. General Ledger
- Double-entry bookkeeping system
- Real-time account balances
- Trial balance generation
- Chart of accounts management
- Journal entry processing

#### 4. Financial Reporting
- Income Statement (Profit & Loss)
- Balance Sheet
- Cash Flow Statement
- Payroll Summary Reports
- Expense Analysis Reports

#### 5. Expense Tracking
- Employee expense claims
- Approval workflow
- Category-based tracking
- Automatic journal entry creation

#### 6. Loan Accounting
- Loan account management
- Payment tracking and scheduling
- Interest calculations
- Loan status monitoring

## 🎨 UI/UX Features

### Modern Design
- Clean, professional interface
- Responsive design for all devices
- Bootstrap 5 framework
- Custom CSS with modern animations
- Font Awesome icons

### User Experience
- Intuitive navigation
- Real-time form validation
- Loading states and feedback
- Search and filter functionality
- Export capabilities

## 🔐 Security Features

- Password hashing with PHP password_hash()
- SQL injection prevention with prepared statements
- XSS protection with htmlspecialchars()
- Session management
- Role-based access control
- Audit logging

## 📊 Reporting Capabilities

### Financial Reports
1. **Income Statement** - Revenue and expense analysis
2. **Balance Sheet** - Assets, liabilities, and equity
3. **Cash Flow Statement** - Cash flow analysis
4. **Trial Balance** - Account balance verification

### Management Reports
1. **Payroll Summary** - Payroll cost analysis
2. **Expense Analysis** - Expense category breakdown
3. **Employee Reports** - Individual employee financial data

## 🚀 Getting Started

### For Administrators
1. Log in with admin credentials
2. Configure chart of accounts
3. Set up payroll periods
4. Review system settings
5. Manage all financial operations

## 🔄 HRIS Integration

The system is designed to integrate with HRIS systems for:
- Employee master data synchronization
- Attendance and time tracking
- Leave management
- Benefits administration
- Salary and position updates

## 📱 Mobile Support

The system is fully responsive and works on:
- Desktop computers
- Tablets
- Mobile phones
- All modern browsers

## 🧪 Testing & Utilities

### Database Testing
- `test_db_connection.php` - Test database connectivity
- `utils/test_login.php` - Test login functionality

### System Utilities
- `utils/fix_admin_password.php` - Reset admin password
- `database/generate_hash.php` - Generate password hashes

### API Endpoints
- `modules/api/transaction-data.php` - Transaction data API for external integration

## 🛠️ Development

### Adding New Features
1. Create new modules in the `modules/` directory
2. Follow the existing code structure and patterns
3. Update the database schema if needed
4. Add appropriate role-based access controls
5. Include proper session management with `includes/session.php`
6. Use the established database connection from `config/database.php`

### Customization
- Modify CSS in `assets/css/` for styling changes
- Update JavaScript in `assets/js/` for functionality
- Extend modules for additional features
- Use utility files in `utils/` for system maintenance

## 🔄 Migration & Deployment Guide

### Moving System Between Devices
When migrating your Accounting & Finance System to a new device or server:

#### **Pre-Migration Checklist:**
1. ✅ Export database from current system
2. ✅ Copy all PHP files and folders
3. ✅ Note current database configuration
4. ✅ Backup any custom configurations

#### **New Device Setup:**
1. **Install XAMPP** on the new device
2. **Start Apache and MySQL** services
3. **Run Automated Setup:**
   - Navigate to: `http://localhost/Accounting and finance/database/init.php`
   - This will create database, schema, and admin user automatically
4. **Or Manual Setup:**
   - Create database `accounting_finance` in phpMyAdmin
   - Import `database/schema.sql`
   - **CRITICAL:** Import `database/insert_admin.sql` (admin user creation)
5. **Test Login** with `admin` / `admin123`

#### **Common Migration Issues:**
- **"Admin user not found"** → Run `database/insert_admin.sql` or use `utils/fix_admin_password.php`
- **Database connection failed** → Check `config/database.php` settings
- **Tables missing** → Re-import `database/schema.sql`

#### **Verification Steps:**
1. Test database connection: `http://localhost/Accounting and finance/test_db_connection.php`
2. Verify admin user exists in database
3. Test login functionality
4. Check all modules are accessible

### Production Deployment
For production environments:
1. Change default admin password immediately
2. Update database credentials in `config/database.php`
3. Configure proper file permissions
4. Set up regular database backups
5. Enable SSL/HTTPS for security

## 📚 Documentation

The system includes comprehensive documentation in the `docs/` directory:

- **README.md** - This comprehensive overview
- **SETUP.md** - Quick 5-minute setup guide
- **INSTALLATION_GUIDE.md** - Detailed installation instructions
- **MIGRATION_GUIDE.md** - Complete migration and deployment guide
- **PATH_REFERENCE.md** - File path reference guide
- **ACCOUNTING_FINANCE_SYSTEM_OVERVIEW.md** - System architecture overview

## 📞 Support

For technical support or questions:
1. Check the system documentation
2. Review the code comments
3. Check the audit logs for system activity
4. Contact your system administrator

## 🔄 Updates and Maintenance

### Regular Maintenance
- Monitor system logs
- Update user passwords regularly
- Backup database regularly
- Review and update chart of accounts
- Process payroll on schedule

### System Updates
- Keep PHP and MySQL updated
- Monitor security updates
- Test new features before deployment
- Maintain backup procedures

## 📄 License

This project is developed for educational and business purposes. Please ensure compliance with your organization's policies and local regulations.

---

**System Version:** 1.1.0  
**Last Updated:** January 2025  
**Compatible with:** PHP 7.4+, MySQL 5.7+, XAMPP 3.3+
