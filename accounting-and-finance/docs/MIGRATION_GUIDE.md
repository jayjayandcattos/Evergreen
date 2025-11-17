# Migration & Deployment Guide
## Accounting & Finance System

This guide covers migrating your Accounting & Finance System between devices, servers, or environments.

## 🚀 Quick Migration (5 Minutes)

### Automated Migration (Recommended)
1. **Copy Files:** Transfer entire project folder to new device
2. **Start XAMPP:** Ensure Apache and MySQL are running
3. **Run Setup:** Navigate to `http://localhost/Accounting and finance/database/init.php`
4. **Login:** Use `admin` / `admin123` credentials
5. **Done!** System is ready to use

## 📋 Detailed Migration Process

### Phase 1: Pre-Migration Preparation

#### **Current System Backup**
1. **Database Export:**
   ```sql
   -- Export entire database
   mysqldump -u root -p BankingDB > backup.sql
   ```

2. **File System Backup:**
   - Copy entire project folder
   - Include all subdirectories and files
   - Preserve file permissions if possible

3. **Configuration Notes:**
   - Document current database settings
   - Note any custom configurations
   - List installed modules and customizations

### Phase 2: New Environment Setup

#### **Option A: Automated Setup (Easiest)**
1. **Install XAMPP** on target device
2. **Start Services:** Apache and MySQL
3. **Copy Project Files** to `htdocs` directory
4. **Run Initialization:**
   - Navigate to: `http://localhost/Accounting and finance/database/init.php`
   - Follow on-screen instructions
   - System will create database, schema, and admin user automatically

#### **Option B: Manual Setup**
1. **Create Database:**
   ```sql
   CREATE DATABASE BankingDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. **Import Schema:**
   - Open phpMyAdmin
   - Import `database/unified_schema.sql`

3. **Create Admin User:**
   - **CRITICAL STEP:** Import `database/Sampled_data.sql` into `BankingDB` database
   - Without this, you cannot log in!

4. **Verify Data:**
   - Check admin user exists: `SELECT * FROM users WHERE username = 'admin';`
   - Verify employee data: `SELECT COUNT(*) FROM employee_refs;`

### Phase 3: Data Migration (If Moving Existing Data)

#### **Database Import**
1. **Method 1: phpMyAdmin**
   - Select target database
   - Go to **Import** tab
   - Choose your backup.sql file
   - Click **Go**

2. **Method 2: Command Line**
   ```bash
   mysql -u root -p BankingDB < backup.sql
   ```

#### **File System Migration**
1. **Copy Project Files:**
   ```bash
   # Windows
   xcopy "C:\xampp\htdocs\Accounting and finance" "D:\new_location" /E /I
   
   # Linux/Mac
   cp -r /path/to/source /path/to/destination
   ```

2. **Update Configuration:**
   - Edit `config/database.php` if needed
   - Update any hardcoded paths
   - Verify file permissions

### Phase 4: Verification & Testing

#### **System Verification Checklist**
- [ ] **Database Connection:** `http://localhost/Accounting and finance/test_db_connection.php`
- [ ] **Admin Login:** Test with `admin` / `admin123`
- [ ] **Module Access:** Verify all modules load correctly
- [ ] **Data Integrity:** Check that data migrated correctly
- [ ] **Functionality:** Test key features (transactions, reports, etc.)

#### **Common Verification Queries**
```sql
-- Check admin user exists
SELECT id, username, email, is_active FROM users WHERE username = 'admin';

-- Check table count
SELECT COUNT(*) as table_count FROM information_schema.tables 
WHERE table_schema = 'BankingDB';

-- Check sample data
SELECT COUNT(*) as transaction_count FROM journal_entries;
```

## 🚨 Troubleshooting Migration Issues

### **Issue 1: "Admin User Not Found"**
**Symptoms:** Cannot log in with admin credentials
**Root Cause:** Admin user not created during migration

**Solutions:**
1. **Quick Fix:** Run `http://localhost/Accounting and finance/database/init.php`
2. **Manual Fix:** Import `database/insert_admin.sql` in phpMyAdmin
3. **Utility Fix:** Run `utils/fix_admin_password.php`

### **Issue 2: Database Connection Failed**
**Symptoms:** "Connection failed" errors
**Root Cause:** Database configuration mismatch

**Solutions:**
1. Check `config/database.php` settings
2. Verify MySQL service is running
3. Confirm database `BankingDB` exists
4. Test connection with phpMyAdmin

### **Issue 3: Tables Missing**
**Symptoms:** "Table doesn't exist" errors
**Root Cause:** Schema not imported properly

**Solutions:**
1. Re-import `database/unified_schema.sql`
2. Check for SQL errors during import
3. Verify database permissions

### **Issue 4: Permission Errors**
**Symptoms:** "Access denied" or file permission errors
**Root Cause:** Incorrect file permissions

**Solutions:**
1. **Windows:** Run XAMPP as Administrator

## 📊 Migration Validation

### **Data Integrity Checks**
```sql
-- Verify user accounts
SELECT COUNT(*) FROM users;

-- Check financial data
SELECT COUNT(*) FROM journal_entries;
SELECT COUNT(*) FROM accounts;

-- Verify relationships
SELECT COUNT(*) FROM journal_lines WHERE journal_id IN (SELECT id FROM journal_entries);
```

### **Functional Testing**
1. **User Authentication:** Login/logout functionality
2. **Transaction Recording:** Create test transactions
3. **Financial Reports:** Generate sample reports
4. **Payroll Processing:** Test payroll calculations
5. **Data Export:** Verify export functionality

## 🔄 Rollback Procedures

### **If Migration Fails**
1. **Stop Services:** Stop Apache and MySQL
2. **Restore Backup:** Copy original files back
3. **Database Restore:** Import original database backup
4. **Verify System:** Test that original system works
5. **Investigate Issues:** Identify and fix migration problems
6. **Retry Migration:** Attempt migration again with fixes

## 📈 Best Practices

### **Before Migration**
- ✅ Test migration on a development environment first
- ✅ Create complete backups of both database and files
- ✅ Document current system configuration
- ✅ Plan for downtime if migrating production system

### **During Migration**
- ✅ Verify each step before proceeding to next
- ✅ Test functionality at each phase
- ✅ Keep original system running until migration is verified
- ✅ Have rollback plan ready

### **After Migration**
- ✅ Change default passwords immediately
- ✅ Update security configurations
- ✅ Set up monitoring and logging
- ✅ Create new backup procedures
- ✅ Document new system configuration

## 📞 Support & Resources

### **Documentation References**
- `docs/README.md` - Complete system overview
- `docs/SETUP.md` - Quick setup guide
- `docs/INSTALLATION_GUIDE.md` - Detailed installation

### **Utility Scripts**
- `database/init.php` - Automated setup
- `test_db_connection.php` - Database testing
- `utils/fix_admin_password.php` - Password reset
