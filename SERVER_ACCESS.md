# Evergreen System - Server Access Guide

## 🌐 Access URLs

### Option 1: XAMPP Apache (Recommended for PHP)
Make sure XAMPP Apache is running, then access:

1. **Accounting & Finance**
   - URL: `http://localhost/Evergreen/accounting-and-finance/`
   - Login: `http://localhost/Evergreen/accounting-and-finance/core/login.php`

2. **Bank System - Basic Operation**
   - URL: `http://localhost/Evergreen/bank-system/Basic-operation/operations/public/`

3. **Bank System - Evergreen Marketing**
   - URL: `http://localhost/Evergreen/bank-system/evergreen-marketing/`

4. **HRIS-SIA**
   - URL: `http://localhost/Evergreen/hris-sia/`

5. **Loan Subsystem**
   - URL: `http://localhost/Evergreen/LoanSubsystem/`

---

### Option 2: npx serve (Static Assets Only)
Started servers on different ports (for static assets testing):

1. **Accounting & Finance** → Port `3001`
   - URL: `http://localhost:3001/`

2. **Bank System - Basic Operation** → Port `3002`
   - URL: `http://localhost:3002/`

3. **Bank System - Evergreen Marketing** → Port `3003`
   - URL: `http://localhost:3003/`

4. **HRIS-SIA** → Port `3004`
   - URL: `http://localhost:3004/`

5. **Loan Subsystem** → Port `3005`
   - URL: `http://localhost:3005/`

⚠️ **Note:** `npx serve` only serves static files. PHP features won't work. For full functionality, use XAMPP Apache (Option 1).

---

### Option 3: PHP Built-in Server (Development)
For development testing with PHP support:

```powershell
# Accounting & Finance
cd accounting-and-finance
php -S localhost:8001

# Bank System - Basic Operation
cd bank-system/Basic-operation/operations/public
php -S localhost:8002

# Bank System - Evergreen Marketing
cd bank-system/evergreen-marketing
php -S localhost:8003

# HRIS-SIA
cd hris-sia
php -S localhost:8004

# Loan Subsystem
cd LoanSubsystem
php -S localhost:8005
```

---

## 🚀 Quick Start Checklist

- [x] XAMPP Apache is running
- [x] XAMPP MySQL is running
- [x] All subsystems are accessible via localhost
- [x] Expense tracking filters are working
- [x] Audit trail functionality is complete
- [x] Financial reporting uses real data from all subsystems

---

## 📝 Admin Credentials

### Accounting & Finance
- Email: `admin@system.com`
- Password: `admin123`

---

## 🔧 Troubleshooting

### If npx serve shows errors:
- Make sure Node.js is installed
- Run `npm install -g serve` if needed
- PHP features require XAMPP Apache, not npx serve

### If PHP pages don't load:
- Check XAMPP Apache is running
- Verify MySQL is running
- Check database connections in config files

---

## 📌 Current Status

✅ **All subsystems integrated**
✅ **Real data from HRIS-SIA, Bank System, and Loan Subsystem**
✅ **Expense tracking with filters working**
✅ **Audit trail functionality complete**
✅ **Financial reporting using real operational data**
✅ **All mock data removed**

