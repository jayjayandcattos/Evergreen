# Referral System Implementation

## Overview
A complete referral code system has been implemented for the Evergreen Banking signup process. Users can now generate referral codes after signup and earn points when their codes are used by new users.

## Features Implemented

### 1. Database Schema
- **Location**: `migrations/add_referral_fields.sql`
- Added `referral_code` field (VARCHAR(20), UNIQUE) to `bank_customers`
- Added `total_points` field (DECIMAL(10,2), DEFAULT 0.00) to track points
- Added `referred_by_customer_id` field (INT, NULL) to track who referred whom
- Added indexes and foreign key constraints

### 2. Signup Form Enhancement
- **File**: `signup.php`
- Added optional referral code input field in Step 2 (Contact & Address)
- Field automatically converts input to uppercase
- Includes helpful hint text about earning bonus points
- Referral code is stored in session during registration process

### 3. Referral Code Generation
- **File**: `verify.php`
- Automatically generates unique referral code after successful account creation
- Format: `EVG` + customer_id (padded to 6 digits) + random 3-4 digits
- Example: `EVG000001123`
- Ensures uniqueness by checking database before assignment

### 4. Referral Processing
- **File**: `verify.php`
- Processes referral codes entered during signup
- Validates referral code exists and is not the user's own code
- Prevents duplicate referrals
- Awards points to both referrer and new user

### 5. Points System
- **Referrer Points**: 50.00 points when someone uses their code
- **New User Points**: 25.00 points when they use a referral code
- Points are added to `total_points` field in `bank_customers` table
- All point transactions are wrapped in database transactions for data integrity

### 6. Success Modal Enhancement
- **File**: `verify.php`
- Displays generated referral code prominently in success modal
- Shows confirmation message if referral code was successfully applied
- Includes instructions to share referral code with friends

### 7. Session Management
- **File**: `login.php`
- Cleans up referral session data on login page load
- Prevents stale session data from persisting

## How It Works

### For New Users (Signing Up)
1. User fills out signup form
2. Optionally enters a friend's referral code in Step 2
3. Completes email verification
4. System creates account and generates unique referral code
5. If referral code was provided:
   - System validates the code
   - Awards 50 points to referrer
   - Awards 25 points to new user
   - Links new user to referrer
6. Success modal displays:
   - Generated referral code
   - Confirmation if referral was processed

### For Existing Users (Referring Friends)
1. User shares their referral code with friends
2. When friend signs up and uses the code:
   - Referrer automatically receives 50 points
   - Friend receives 25 points
   - Referral relationship is recorded

## Database Migration

**IMPORTANT**: Before using the referral system, you must run the database migration:

```sql
-- Run this SQL in your bankingdb database
ALTER TABLE bank_customers 
ADD COLUMN referral_code VARCHAR(20) UNIQUE NULL,
ADD COLUMN total_points DECIMAL(10,2) DEFAULT 0.00,
ADD COLUMN referred_by_customer_id INT NULL,
ADD INDEX idx_referral_code (referral_code),
ADD INDEX idx_referred_by (referred_by_customer_id);

ALTER TABLE bank_customers 
ADD CONSTRAINT fk_referred_by 
FOREIGN KEY (referred_by_customer_id) REFERENCES bank_customers(customer_id) ON DELETE SET NULL;
```

Or use the migration file: `migrations/add_referral_fields.sql`

## Points Configuration

Current point values can be adjusted in `verify.php` in the `processReferral()` function:

```php
$referrer_points = 50.00;  // Points for referrer
$referred_points = 25.00;  // Points for new user
```

## Security Features

- Referral codes are validated before processing
- Users cannot use their own referral code
- Duplicate referrals are prevented
- All database operations use prepared statements
- Transactions ensure data integrity
- Invalid referral codes don't break the signup process

## Testing Checklist

- [ ] Run database migration
- [ ] Test signup without referral code
- [ ] Verify referral code is generated
- [ ] Test signup with valid referral code
- [ ] Verify points are awarded correctly
- [ ] Test signup with invalid referral code (should not break signup)
- [ ] Test signup with own referral code (should be rejected)
- [ ] Verify success modal displays referral code
- [ ] Check that referral relationship is recorded in database

## Files Modified

1. `signup.php` - Added referral code input field
2. `verify.php` - Added referral code generation and processing
3. `login.php` - Added session cleanup
4. `migrations/add_referral_fields.sql` - Database migration script
5. `migrations/README.md` - Migration instructions

## Future Enhancements

Potential improvements:
- Referral code validation on frontend (AJAX check)
- Referral statistics dashboard
- Referral history tracking
- Email notifications when referral code is used
- Tiered referral rewards (more points for multiple referrals)
- Referral code expiration dates
- Admin panel to manage referral codes and points

