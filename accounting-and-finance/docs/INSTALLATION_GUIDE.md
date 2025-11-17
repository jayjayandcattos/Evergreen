# Installation Guide - Accounting and Finance System

## Prerequisites
- XAMPP (Apache + MySQL + PHP)
- Web Browser (Chrome, Firefox, Edge, etc.)

## Installation Steps

### Step 1: Start XAMPP
1. Open XAMPP Control Panel
2. Start **Apache** server
3. Start **MySQL** server

### Step 2: Create Database
1. Open your web browser and go to: `http://localhost/phpmyadmin`
2. Click on **SQL** tab
3. Copy and paste the contents of `database/schema.sql`
4. Click **Go** to execute

### Step 3: Insert Admin User and Default Data
> **⚠️ CRITICAL STEP:** This step is REQUIRED! Without it, you cannot log in to the system.

1. In phpMyAdmin, click on **SQL** tab again
2. Copy and paste the contents of `database/insert_admin.sql`
3. Click **Go** to execute

**Alternative:** Use the automated setup script:
1. Navigate to: `http://localhost/Accounting and finance/database/init.php`
2. Follow the on-screen instructions
3. This will create both the database schema AND the admin user automatically

### Step 4: Access the System
1. Open your web browser
2. Navigate to: `http://localhost/Accounting%20and%20finance/core/index.php`

### Step 5: Login with Admin Credentials
Use the following credentials to login:
- **Username:** admin
- **Password:** admin123

## Default Admin Account
```
ID: 1
Username: admin
Password: admin123
Email: admin@system.com
Full Name: System Administrator
```

## Troubleshooting

### **"Admin User Not Found" Error (Most Common Issue)**
If you get "Invalid username or password" when trying to log in:

**Root Cause:** The admin user was not created in the database.

**Quick Fix:**
1. Run the automated setup: `http://localhost/Accounting and finance/database/init.php`
2. Or use the utility script: `utils/fix_admin_password.php`

**Manual Fix:**
1. Open phpMyAdmin (`http://localhost/phpmyadmin`)
2. Select the `BankingDB` database
3. Go to **SQL** tab
4. Copy and paste the contents of `database/Sampled_data.sql`
5. Click **Go** to execute
6. Verify the admin user was created by running: `SELECT * FROM users WHERE username = 'admin';`

**Verify Admin User Exists:**
```sql
SELECT id, username, email, full_name, is_active FROM users WHERE username = 'admin';
```
You should see one row with the admin user details.

### Cannot connect to database
- Make sure MySQL is running in XAMPP
- Check if database name is `BankingDB`
- Verify credentials in `config/database.php`

### Login page not loading
- Ensure Apache is running in XAMPP
- Check if files are in the correct htdocs folder
- Clear browser cache

### Password not working
- Make sure you executed `database/insert_admin.sql`
- The password is case-sensitive: `admin123`

## File Structure
```
Accounting and finance/
├── assets/
│   └── css/
│       └── style.css
├── config/
│   └── database.php
├── database/
│   ├── schema.sql
│   └── insert_admin.sql
├── includes/
│   └── session.php
├── modules/
│   ├── general-ledger.php
│   ├── financial-reporting.php
│   ├── loan-accounting.php
│   ├── transaction-reading.php
│   ├── expense-tracking.php
│   └── payroll-management.php
├── index.php
├── login.php
├── dashboard.php
└── logout.php
```

## Security Notes
- **Change the default admin password** after first login
- Update database credentials in `config/database.php` if needed
- Keep your XAMPP installation updated

## Next Steps
After successful installation:
1. Login to the system
2. Explore the dashboard
3. Access different modules
4. Create additional user accounts
5. Start configuring the system for your organization

## Support
For issues or questions, please refer to the documentation or contact your system administrator.

