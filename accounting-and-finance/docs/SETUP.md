# Quick Setup Guide - Accounting & Finance System

## 🚀 Quick Start (5 Minutes)

### Step 1: Start XAMPP
1. Open XAMPP Control Panel
2. Start **Apache** and **MySQL** services
3. Ensure both services show "Running" status

### Step 2: Initialize Database
1. Open your web browser
2. Navigate to: `http://localhost/Accounting and finance/database/init.php`
3. You should see "Database initialized successfully!" message
4. Click "Go to Login Page" link

### Step 3: Login to System
Use the following demo account:

**Administrator Account:**
- Email: `admin@system.com`
- Password: `admin123`

## 🎯 What You Get

### Admin Dashboard Features
- ✅ User management
- ✅ Employee management
- ✅ System overview and statistics
- ✅ Full access to all modules
- ✅ Payroll processing
- ✅ Financial reports
- ✅ General ledger management
- ✅ Journal entries
- ✅ Chart of accounts
- ✅ Transaction recording
- ✅ Expense claims processing
- ✅ Trial balance

## 📊 Demo Data Included

The system comes pre-loaded with:
- **1 Admin Account** (Full system access)
- **4 Sample Employees** from different departments
- **Complete Chart of Accounts** (Assets, Liabilities, Equity, Revenue, Expenses)
- **Sample Bank Accounts**
- **Salary Components** (Basic, Overtime, Deductions)
- **Fiscal Period** for current month

## 🔧 System Requirements

- **XAMPP** 3.3+ (Apache + MySQL + PHP)
- **PHP** 7.4 or higher
- **MySQL** 5.7 or higher
- **Web Browser** (Chrome, Firefox, Safari, Edge)

## 📁 File Structure

```
Accounting and finance/
├── index.php              # Login page
├── admin/                 # Admin dashboard
├── accounting/            # Accounting officer dashboard
├── modules/               # Core system modules
├── assets/                # CSS, JS, Images
├── config/                # Database configuration
├── database/              # Database schema and init
└── includes/              # Authentication system
```

## 🎨 Modern UI Features

- **Responsive Design** - Works on desktop, tablet, mobile
- **Bootstrap 5** - Modern, clean interface
- **Font Awesome Icons** - Professional iconography
- **Custom Animations** - Smooth transitions and effects
- **Dark/Light Theme** - Professional color scheme

## 🔐 Security Features

- **Password Hashing** - Secure password storage
- **SQL Injection Protection** - Prepared statements
- **XSS Protection** - Input sanitization
- **Role-based Access** - Admin access control
- **Session Management** - Secure login sessions
- **Audit Logging** - Track all user actions

## 📈 Key Modules

### 1. Payroll Management
- Process employee payroll
- Calculate deductions (Tax, SSS, PhilHealth, Pag-IBIG)
- Generate digital payslips
- Create payroll journal entries

### 2. General Ledger
- Double-entry bookkeeping
- Chart of accounts management
- Journal entries
- Trial balance generation

### 3. Financial Reporting
- Income Statement
- Balance Sheet
- Cash Flow Statement
- Payroll Summary
- Expense Analysis

### 4. Expense Tracking
- Employee expense claims
- Approval workflow
- Category-based tracking
- Automatic journal entries

### 5. Loan Management
- Loan processing
- Payment tracking
- Interest calculations
- Journal entry automation

## 🚨 Troubleshooting

### Database Connection Issues
1. Ensure MySQL is running in XAMPP
2. Check `config/database.php` settings
3. Verify database `BankingDB` exists

### Login Issues
1. Use exact demo credentials provided
2. Clear browser cache and cookies
3. Check if session is enabled in PHP

### **Common Issue: "Admin User Not Found"**
This is the most common setup problem! If you get "Invalid username or password" errors:

**Quick Fix:**
1. Run the automated setup: `http://localhost/Accounting and finance/database/init.php`
2. Or manually run `database/insert_admin.sql` in phpMyAdmin
3. Or use the utility script: `utils/fix_admin_password.php`

**Manual Fix Steps:**
1. Open phpMyAdmin (`http://localhost/phpmyadmin`)
2. Select the `BankingDB` database
3. Go to **SQL** tab
4. Copy and paste contents of `database/Sampled_data.sql`
5. Click **Go** to execute
6. Try logging in again with `admin` / `admin123`

### Permission Issues
1. Ensure XAMPP has proper file permissions
2. Check if PHP can write to session directory
3. Verify web server has access to project files

## 📞 Support

If you encounter any issues:
1. Check the main README.md for detailed documentation
2. Review the code comments for technical details
3. Ensure all XAMPP services are running
4. Try refreshing the database initialization

## 🎉 You're Ready!

Once you see the login page, you have successfully set up the Accounting & Finance System. The system is ready for:

- **Demo and Testing** - Explore all features with sample data
- **Development** - Extend and customize for your needs
- **Production** - Configure for real business use

**Happy Accounting!** 📊✨
