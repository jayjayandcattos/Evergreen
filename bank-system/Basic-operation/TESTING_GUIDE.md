# Quick Testing Guide - Customer Onboarding Security

## Prerequisites

✅ XAMPP running (Apache + MySQL)
✅ Database `evergreendb` exists
✅ Step 1 completed (or session mocked)

## Quick Test Steps

### 1. Access the Security Page

Open in browser:

```
http://localhost/sianova/public/customer-onboarding-security.html
```

### 2. Fill Out Credentials

**Username:**

- Enter: `JDelaCruz` or any valid username
- Must be 5-20 characters, start with letter

**Password:**

- Enter: `SecureP@ss123` (or any password meeting requirements)
- Must have: 8+ chars, uppercase, lowercase, number, special char

**Confirm Password:**

- Re-enter the same password

### 3. Mobile Verification

**Mobile Number:**

- Enter: `+639123456789` (or any number with country code)

**Send Code:**

- Click "Send Code" button
- In MOCK mode, an alert will show the code
- Check browser console for the code
- Example: `DEV MODE - Verification code is: 1234`

**Enter Code:**

- Type the 4-digit code shown
- It will auto-verify when you enter the 4th digit
- Green boxes = verified ✓

### 4. Submit Form

- Click "Continue" button
- If successful, redirects to review page
- If error, shows error messages

## Testing Different Scenarios

### ✅ Valid Submission

```
Username: TestUser123
Password: ValidP@ss123
Confirm: ValidP@ss123
Mobile: +639123456789
Code: (from console/alert)
```

**Expected:** Success, redirect to review

### ❌ Password Mismatch

```
Password: ValidP@ss123
Confirm: DifferentP@ss123
```

**Expected:** Error "Passwords do not match"

### ❌ Weak Password

```
Password: weak
```

**Expected:** Error showing requirements

### ❌ Invalid Username

```
Username: 123  (too short)
Username: _test  (must start with letter)
```

**Expected:** Username validation errors

### ❌ Not Verified

```
1. Enter all fields
2. DON'T verify mobile code
3. Click Continue
```

**Expected:** Error "Please verify your mobile number first"

### ❌ Wrong Code

```
1. Send code
2. Enter wrong 4-digit code (e.g., 9999)
```

**Expected:** Error "Invalid verification code"

### ❌ Expired Code

```
1. Send code
2. Wait 6+ minutes
3. Enter code
```

**Expected:** Error "Verification code has expired"

## Back Button Test

1. Fill out any data
2. Click "Back" button
   **Expected:** Returns to `customer-onboarding-details.html`

## Resend Code Test

1. Send code
2. Wait for timer (30 seconds)
3. Click "Resend Code"
   **Expected:** New code sent, timer resets

## Console Debugging

### Check for Code (Mock Mode)

Open browser console (F12), look for:

```
DEV MODE - Verification code: 1234
```

### Check for Errors

Look for any red errors in console indicating:

- API connection issues
- JavaScript errors
- Network problems

## Common Issues & Fixes

### Issue: "Failed to send code"

**Fix:**

- Check XAMPP Apache is running
- Verify API path: `http://localhost/sianova/api/...`
- Check `includes/sms-service.php` exists

### Issue: "Please complete step 1 first"

**Fix:**

- Go to `customer-onboarding-details.html` first
- Complete Step 1
- Then access Step 2

### Issue: Code boxes don't enable

**Fix:**

- Check if "Send Code" was clicked
- Look for "Code Sent" button text
- Check console for errors

### Issue: Form doesn't submit

**Fix:**

- Check all fields are filled
- Verify mobile code is verified (green boxes)
- Check console for validation errors
- Check network tab for API response

## API Testing (Postman/Insomnia)

### Send Verification Code

```
POST http://localhost/sianova/api/customer/send-verification-code.php
Content-Type: application/json

{
  "phone_number": "+639123456789"
}
```

### Verify Code

```
POST http://localhost/sianova/api/customer/verify-code.php
Content-Type: application/json

{
  "phone_number": "+639123456789",
  "code": "1234"
}
```

### Submit Step 2

```
POST http://localhost/sianova/api/customer/create-step2.php
Content-Type: application/json

{
  "username": "TestUser123",
  "password": "ValidP@ss123",
  "confirm_password": "ValidP@ss123",
  "mobile_number": "+639123456789"
}
```

## Expected Browser Alerts (Mock Mode Only)

When you click "Send Code", you should see:

```
DEV MODE - Your verification code is: [4-digit-number]
```

This is ONLY for testing. In production with real SMS:

- No alert will show
- Code will be sent to actual phone
- User enters code from SMS

## Success Indicators

✅ "Send Code" button changes to "Code Sent" (green)
✅ Code input boxes turn green when verified
✅ Success message: "Code verified successfully! ✓"
✅ No errors on form submission
✅ Redirect to review page

## Need Help?

Check these files for configuration:

- API Base URL: `assets/js/customer-onboarding-security.js` (line 6)
- SMS Provider: `includes/sms-service.php` (line 9)
- Database: `config/database.php`

Current Settings:

- API URL: `http://localhost/sianova/api`
- SMS Mode: `mock` (for testing)
- Session: PHP sessions (auto-started)
