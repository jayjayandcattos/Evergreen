# Path Reference Guide

## Updated File Structure

After reorganization, all file paths have been updated. Here's a reference for how to access files from different locations:

### Root Level (/)
- `index.php` → Redirects to `core/login.php`

### Core Files (`core/`)
Files: `dashboard.php`, `login.php`, `logout.php`, `index.php`

**Paths used in core files:**
- Config: `../config/database.php`
- Session: `../includes/session.php`
- Assets: `../assets/css/style.css`
- Modules: `../modules/[module-name].php`

### Module Files (`modules/`)
Files: All 6 module PHP files

**Paths used in module files:**
- Config: `../config/database.php`
- Session: `../includes/session.php`
- Assets: `../assets/css/style.css`
- Dashboard: `../core/dashboard.php`
- Logout: `../core/logout.php`

### Utility Files (`utils/`)
Files: `fix_admin_password.php`, `test_login.php`

**Paths used in utils files:**
- Config: `../config/database.php`
- Login page: `../core/login.php`

## Entry Points

1. **Main Application**: `http://localhost/Accounting and finance/` or `http://localhost/Accounting and finance/index.php`
   - Auto-redirects to: `core/login.php`

2. **Direct Login**: `http://localhost/Accounting and finance/core/login.php`

3. **Dashboard**: `http://localhost/Accounting and finance/core/dashboard.php`

4. **Admin Tools**:
   - Password Reset: `http://localhost/Accounting and finance/utils/fix_admin_password.php`
   - Login Test: `http://localhost/Accounting and finance/utils/test_login.php`

## Folder Structure

```
/
├── assets/           # CSS and static files
├── config/           # Database configuration
├── core/             # Main application files
├── database/         # Database scripts
├── docs/             # Documentation
├── includes/         # Shared PHP includes
├── modules/          # Feature modules
└── utils/            # Admin & utility tools
```

## Navigation Flow

```
Root (index.php)
    ↓
Core (login.php) → Authentication
    ↓
Core (dashboard.php) → Main menu
    ↓
Modules (various) → Features
    ↓
Core (logout.php) → End session
```

