# Employee Login System

## Overview

The employee login system provides secure authentication for bank employees to access the internal banking system.

## Access the Login Page

Navigate to: `http://localhost/SIASIANOVA/Evergreen/bank-system/Basic-operation/public/employee-login.html`

## Default Login Credentials

### Administrator Account

- **Username:** `admin`
- **Password:** `password`
- **Role:** Admin
- **Email:** admin@evergreenbank.com

### Teller Account

- **Username:** `teller1`
- **Password:** `password`
- **Role:** Teller
- **Email:** teller1@evergreenbank.com

## Features

### Security

- Password hashing using PHP's `password_hash()` (bcrypt)
- Session management with timeout (8 hours)
- "Remember Me" option for extended sessions (30 days)
- Automatic session validation
- Session timeout warning
- Secure logout functionality

### User Interface

- Modern, responsive design
- Password visibility toggle
- Real-time form validation
- Loading states and error messages
- Auto-redirect if already logged in
- Professional Evergreen Bank branding

### Session Management

- Sessions expire after 8 hours of inactivity
- Auto-check session validity every 5 minutes
- Logout button available on all authenticated pages
- Session data includes: employee ID, name, role, email

## File Structure

### Frontend Files

- `/public/employee-login.html` - Login page
- `/assets/css/employee-login.css` - Login page styling
- `/assets/js/employee-login.js` - Login form handling
- `/assets/js/auth-helper.js` - Shared authentication functions

### Backend API Files

- `/api/auth/employee-login.php` - Authentication endpoint
- `/api/auth/check-session.php` - Session validation endpoint
- `/api/auth/employee-logout.php` - Logout endpoint

### Database

- Table: `bank_employees`
- Columns: employee_id, username, password_hash, email, first_name, last_name, role, is_active, created_at, updated_at

## Adding New Employees

### Via Database

```sql
INSERT INTO bank_employees (username, password_hash, email, first_name, last_name, role, is_active)
VALUES (
    'username_here',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- This is "password"
    'email@evergreenbank.com',
    'FirstName',
    'LastName',
    'teller',  -- or 'admin' or 'manager'
    1
);
```

### Generating Password Hash

Use PHP to generate a secure password hash:

```php
<?php
echo password_hash('your_password_here', PASSWORD_DEFAULT);
?>
```

## User Roles

- **admin** - Full system access
- **teller** - Standard banking operations
- **manager** - Supervisory access

## Security Notes

1. **Change default passwords immediately** in production
2. The default password hash corresponds to "password"
3. Always use HTTPS in production
4. Session cookies are HTTP-only for security
5. Failed login attempts are logged (future enhancement)

## Troubleshooting

### Can't Login

1. Verify XAMPP Apache and MySQL are running
2. Check database connection in `/config/database.php`
3. Verify `bank_employees` table exists and has data
4. Check browser console for JavaScript errors
5. Verify PHP session is working

### Session Expires Too Quickly

- Session timeout is set to 8 hours
- Check PHP `session.gc_maxlifetime` in php.ini
- Enable "Remember Me" for 30-day sessions

### Database Errors

Run the migration script:

```bash
mysql -u root BankingDB < database/sql/create_bank_employees.sql
```

## API Endpoints

### POST /api/auth/employee-login.php

**Request:**

```json
{
  "username": "admin",
  "password": "password",
  "rememberMe": false
}
```

**Response (Success):**

```json
{
  "success": true,
  "message": "Login successful",
  "employee": {
    "id": 1,
    "username": "admin",
    "first_name": "System",
    "last_name": "Administrator",
    "role": "admin",
    "email": "admin@evergreenbank.com"
  }
}
```

**Response (Error):**

```json
{
  "success": false,
  "message": "Invalid username or password"
}
```

### GET /api/auth/check-session.php

**Response (Logged In):**

```json
{
  "logged_in": true,
  "employee": {
    "id": 1,
    "username": "admin",
    "name": "System Administrator",
    "role": "admin",
    "email": "admin@evergreenbank.com"
  }
}
```

**Response (Not Logged In):**

```json
{
  "logged_in": false
}
```

### GET /api/auth/employee-logout.php

**Response:**

```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

## Protected Pages

All pages that require authentication should:

1. Include `auth-helper.js` before other scripts
2. Call `initAuthentication()` on page load
3. Include logout button with ID `logoutBtn`
4. Have username display element with ID `employeeName`

Example:

```html
<script src="../assets/js/auth-helper.js"></script>
<script src="../assets/js/your-page.js"></script>
```

```javascript
document.addEventListener("DOMContentLoaded", async function () {
  await initAuthentication();
  // Your page logic here
});
```

## Next Steps

1. Change default passwords
2. Implement password reset functionality
3. Add failed login attempt tracking
4. Implement role-based access control
5. Add audit logging for security events
6. Create employee management interface (admin only)
