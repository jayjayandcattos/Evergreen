# Evergreen System - Project Documentation

## Project Updates

- **First commit** - 10:26pm
- **Created Finance branch**
- **Fixed directory connections** - Updated all hardcoded paths from `/bank-system/` to `/Evergreen/bank-system/` throughout the project

---

## Directory Structure

This project contains multiple modules under the `Evergreen` directory:

```
Evergreen/
├── accounting-and-finance/     # Accounting & Finance Core Module
├── bank-system/                 # Bank System Modules
│   ├── Basic-operation/        # Bank Operations & Customer Portal
│   ├── evergreen-marketing/    # Marketing & Public Pages
│   └── SIATEST-main/           # Test Environment
├── hris-sia/                   # HRIS-SIA Module
└── LoanSubsystem/              # Loan Management Subsystem
```

---

## Module Details

### 1. Accounting & Finance - Core Module
**Location:** `accounting-and-finance/`  
**Base URL:** `http://localhost/Evergreen/accounting-and-finance/`  
**Description:** Core accounting and financial management system

### 2. Bank System - Basic Operation Module
**Location:** `bank-system/Basic-operation/`  
**Base URL:** `http://localhost/Evergreen/bank-system/Basic-operation/`  
**Customer Portal:** `http://localhost/Evergreen/bank-system/Basic-operation/operations/public/`  
**API Endpoints:** `http://localhost/Evergreen/bank-system/Basic-operation/api/`  
**Public Pages:** `http://localhost/Evergreen/bank-system/Basic-operation/public/`  
**Description:** Customer onboarding, account management, transactions, and employee operations


### 3. Bank System - Marketing Module
**Location:** `bank-system/evergreen-marketing/`  
**Base URL:** `http://localhost/Evergreen/bank-system/evergreen-marketing/`  
**Login:** `http://localhost/Evergreen/bank-system/evergreen-marketing/login.php`  
**Description:** Marketing website, user authentication, and public-facing pages

### 4. HRIS-SIA Module
**Location:** `hris-sia/`  
**Base URL:** `http://localhost/Evergreen/hris-sia/`  
**Description:** Human Resources Information System

### 5. Loan Subsystem
**Location:** `LoanSubsystem/`  
**Description:** Loan application and management system

### Basic Operation API
Base URL: `http://localhost/Evergreen/bank-system/Basic-operation/api/`


## Development Notes

### Path Structure
All paths are configured to work with the base directory structure:
- Web root: `http://localhost/Evergreen/`
- File system: `C:\xampp\htdocs\Evergreen\`

---

## Contributing

When adding new features or modules:
1. Follow the existing directory structure
2. Use dynamic path detection in JavaScript (see existing files)
3. Update this README with new modules/endpoints
4. Test all paths work correctly with `/Evergreen/` base directory

---

## Contact & Support

SBIT-3C devs
