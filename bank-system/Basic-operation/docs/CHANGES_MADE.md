# Changes Made - Customer Onboarding System

## Overview

This document tracks all changes made to the customer onboarding process, database schema, and related files.

---

## Change Log

### 2025-12-07 - Account Approval Dashboard & Customer Account Creation

#### Enhancement #7 - Improved Approval/Rejection Success Modals

**Time:** Latest Session

**Issue:**

- Both approval and rejection used the same success modal
- When approving an application, account number was shown (correct)
- When rejecting another application without page refresh, the previous account number was still displayed (bug)
- No visual distinction between approval and rejection success

**Root Cause:**

- Single `successModal` reused for both actions
- Account number details (`#successDetails`) not being hidden after showing
- No separate feedback mechanism for rejection success

**Fixes Applied:**

1. **Created Dedicated Rejection Success Modal** (`rejectionSuccessModal`):

   ```html
   <!-- New modal with distinct red/crimson styling -->
   <div class="modal fade" id="rejectionSuccessModal">
     <div class="rejection-circle">
       <i class="bi bi-x-lg text-white"></i>
       <!-- X icon instead of checkmark -->
     </div>
     <h4>Application Rejected</h4>
     <p>
       The application has been rejected successfully.<br />
       <small>The applicant will be notified of the decision.</small>
     </p>
   </div>
   ```

2. **Distinct Visual Styling**:

   - **Approval Modal**: Green gradient circle with checkmark, shows account number
   - **Rejection Modal**: Red gradient circle with X icon, no account number

3. **CSS Animations** for rejection modal:

   ```css
   .rejection-circle {
     background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
     animation: scaleIn 0.5s ease-out;
     box-shadow: 0 10px 30px rgba(239, 68, 68, 0.4);
   }
   ```

4. **Updated JavaScript Logic**:

   ```javascript
   // Initialize new modal
   rejectionSuccessModal = new bootstrap.Modal(
     document.getElementById("rejectionSuccessModal")
   );

   // In handleReject():
   if (result.success) {
     rejectionModal.hide();
     applicationModal.hide(); // Close application details too
     rejectionSuccessModal.show(); // Show dedicated rejection modal
   }

   // In showSuccessMessage():
   // Always hide account details by default
   document.getElementById("successDetails").style.display = "none";
   ```

**Result:**

- ✅ Approval shows green modal with account number
- ✅ Rejection shows red modal without previous account number
- ✅ Clear visual distinction between success types
- ✅ No data leakage between different actions
- ✅ Both modals close the application details modal properly

#### Enhancement #6 - Custom Approval Confirmation Modal

**Time:** Latest Session

**Issue:**

- Approval used native browser `confirm()` dialog
- Plain, unstyled, doesn't match application design
- No animations or visual appeal
- Inconsistent with other modals in the application

**Fix Applied:**

Created custom approval confirmation modal with:

1. **Modal Structure** (`approvalConfirmModal`):

   ```html
   <div class="modal fade" id="approvalConfirmModal">
     <div class="confirm-icon-wrapper">
       <div class="confirm-icon">
         <i class="bi bi-question-circle"></i>
       </div>
     </div>
     <h4>Approve Application?</h4>
     <p>
       Are you sure you want to approve this application?<br />
       <small>This will create a new bank account for the customer.</small>
     </p>
     <button class="btn btn-outline-secondary" data-bs-dismiss="modal">
       Cancel
     </button>
     <button class="btn btn-success" id="confirmApproveBtn">
       Yes, Approve
     </button>
   </div>
   ```

2. **Animated Icon**:

   - Orange/amber gradient circle with question mark
   - Pulsing glow animation
   - Ripple effect expanding outward
   - Bouncing icon animation

3. **CSS Animations**:

   ```css
   @keyframes pulseGlow {
     0%,
     100% {
       box-shadow: 0 10px 30px rgba(245, 158, 11, 0.4);
     }
     50% {
       box-shadow: 0 10px 40px rgba(245, 158, 11, 0.6);
     }
   }

   @keyframes ripple {
     0% {
       transform: scale(1);
       opacity: 1;
     }
     100% {
       transform: scale(1.4);
       opacity: 0;
     }
   }

   @keyframes bounceIcon {
     0%,
     100% {
       transform: translateY(0);
     }
     50% {
       transform: translateY(-5px);
     }
   }
   ```

4. **Button Hover Effects**:

   - Buttons lift up slightly on hover
   - Smooth color transitions
   - Drop shadow on hover

5. **Updated Event Flow**:

   ```javascript
   // OLD: Direct confirm dialog
   if (!confirm("Are you sure you want to approve this application?")) {
     return;
   }

   // NEW: Two-step process
   // Step 1: Show confirmation modal
   function showApprovalConfirmation() {
     applicationModal.hide();
     approvalConfirmModal.show();
   }

   // Step 2: Actual approval
   async function handleApprove() {
     approvalConfirmModal.hide();
     // ... proceed with approval API call
   }
   ```

**Result:**

- ✅ Professional, branded confirmation dialog
- ✅ Smooth animations matching application style
- ✅ Clear explanation of what will happen
- ✅ Better UX with visual feedback

#### Enhancement #5 - Bank Customers Schema Integration

**Time:** Earlier Session

**Background:**
User provided full `bank_customers` table schema with comprehensive fields including personal information, contact details, referral system, and audit tracking.

**Existing Table Structure:**
The database had a minimal `bank_customers` table with only:

- customer_id
- email
- password_hash
- application_id
- is_verified
- is_active
- created_at
- created_by_employee_id
- last_login

**Required Schema:**

```sql
CREATE TABLE bank_customers (
  customer_id INT AUTO_INCREMENT PRIMARY KEY,
  application_id INT UNIQUE,
  last_name VARCHAR(100) NOT NULL,
  first_name VARCHAR(100) NOT NULL,
  middle_name VARCHAR(100),
  address VARCHAR(255),
  city_province VARCHAR(100),
  email VARCHAR(100) UNIQUE NOT NULL,
  contact_number VARCHAR(20),
  birthday DATE,
  verification_code VARCHAR(6),
  password_hash VARCHAR(255),
  is_verified TINYINT(1) DEFAULT 0,
  is_active TINYINT(1) DEFAULT 1,
  bank_id VARCHAR(50) UNIQUE,
  referral_code VARCHAR(10) UNIQUE,
  referred_by_customer_id INT,
  total_points INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (application_id) REFERENCES account_applications(application_id),
  FOREIGN KEY (referred_by_customer_id) REFERENCES bank_customers(customer_id),
  INDEX idx_email (email),
  INDEX idx_bank_id (bank_id),
  INDEX idx_referral_code (referral_code)
);
```

**Migration Created and Executed:**

Created `database/migrations/add_bank_customers_fields.php`:

```php
// Added missing columns with proper data types and constraints
ALTER TABLE bank_customers
ADD COLUMN last_name VARCHAR(100) AFTER application_id,
ADD COLUMN first_name VARCHAR(100) AFTER last_name,
ADD COLUMN middle_name VARCHAR(100) AFTER first_name,
ADD COLUMN address VARCHAR(255) AFTER middle_name,
ADD COLUMN city_province VARCHAR(100) AFTER address,
ADD COLUMN contact_number VARCHAR(20) AFTER email,
ADD COLUMN birthday DATE AFTER contact_number,
ADD COLUMN verification_code VARCHAR(6) AFTER birthday,
ADD COLUMN bank_id VARCHAR(50) UNIQUE AFTER is_active,
ADD COLUMN referral_code VARCHAR(10) UNIQUE AFTER bank_id,
ADD COLUMN referred_by_customer_id INT AFTER referral_code,
ADD COLUMN total_points INT DEFAULT 0 AFTER referred_by_customer_id,
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

// Added indexes for performance
CREATE INDEX idx_email ON bank_customers(email);
CREATE INDEX idx_bank_id ON bank_customers(bank_id);
CREATE INDEX idx_referral_code ON bank_customers(referral_code);

// Added foreign key for referral system
ALTER TABLE bank_customers
ADD CONSTRAINT fk_referred_by
FOREIGN KEY (referred_by_customer_id) REFERENCES bank_customers(customer_id);
```

**Migration Execution:**

- Executed via `database/run_migration.php`
- All columns added successfully
- Indexes created
- Foreign key constraint established
- Zero errors

**Result:**

- ✅ All 21 schema fields now exist in bank_customers table
- ✅ Proper data types and constraints
- ✅ Self-referencing foreign key for referral system
- ✅ Indexes for optimized queries
- ✅ Compatible with account creation workflow

#### Enhancement #4 - Account Number Format Change

**Time:** Earlier Session

**Issue:**

- Old account number format: `ACC20251207000001999`
- Long, hard to read, no account type indication
- User requested format: `SA-4893-2025` for Savings, `CHA-4893-2025` for Checking

**New Format Specification:**

- **Prefix**: Account type identifier
  - `SA` = Savings Account
  - `CHA` = Checking Account
- **Random Digits**: 4-digit random number (1000-9999)
- **Year**: Current year (2025)
- **Format**: `[PREFIX]-[XXXX]-[YYYY]`

**Implementation in approve-application.php:**

```php
function generateAccountNumber($accountType, $db) {
    $prefix = ($accountType === 'Savings') ? 'SA' : 'CHA';
    $year = date('Y');
    $maxAttempts = 10;

    for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
        // Generate random 4-digit number
        $randomNumber = str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
        $accountNumber = "{$prefix}-{$randomNumber}-{$year}";

        // Check if account number already exists
        $checkStmt = $db->prepare("
            SELECT COUNT(*) FROM customer_accounts
            WHERE account_number = ?
        ");
        $checkStmt->bind_param("s", $accountNumber);
        $checkStmt->execute();
        $checkStmt->bind_result($count);
        $checkStmt->fetch();
        $checkStmt->close();

        if ($count == 0) {
            return $accountNumber; // Unique account number found
        }
    }

    // Fallback: add timestamp if all attempts fail
    $timestamp = substr(time(), -4);
    return "{$prefix}-{$timestamp}-{$year}";
}
```

**Features:**

- Duplicate checking with retry logic (up to 10 attempts)
- Timestamp fallback if all random attempts fail (extremely rare)
- Clear account type identification
- Human-readable format
- Year included for easy identification

**Result:**

- ✅ Savings accounts: `SA-4893-2025`
- ✅ Checking accounts: `CHA-5721-2025`
- ✅ No duplicates
- ✅ Easy to read and communicate

#### Enhancement #3 - Existing Customer Account Opening Support

**Time:** Earlier Session

**Issue:**
When approving applications from existing customers, error occurred:

```
"Customer record not found for this application"
```

**Root Cause:**
The approval logic only looked for customers via `application_id` link in `bank_customers` table. This works for NEW customers but fails for EXISTING customers who already have a `customer_id` directly in `account_applications`.

**Data Structure:**

- **New Customer**:
  - `account_applications.customer_id` = NULL
  - `account_applications.application_id` = 123
  - `bank_customers.application_id` = 123 (link)
- **Existing Customer**:
  - `account_applications.customer_id` = 456 (direct reference)
  - `account_applications.application_id` = 789
  - `bank_customers.customer_id` = 456 (no application_id link)

**Fix in approve-application.php:**

```php
// OLD: Only looked via application_id
$stmt = $db->prepare("
    SELECT customer_id FROM bank_customers
    WHERE application_id = ?
");

// NEW: Dual lookup strategy
// Step 1: Check if customer_id exists directly in application
$stmt = $db->prepare("
    SELECT customer_id FROM account_applications
    WHERE application_id = ?
");
$stmt->bind_param("i", $applicationId);
$stmt->execute();
$result = $stmt->get_result();
$application = $result->fetch_assoc();
$stmt->close();

// Step 2a: Existing customer - use customer_id directly
if ($application['customer_id']) {
    $customerId = $application['customer_id'];
}
// Step 2b: New customer - lookup via application_id link
else {
    $stmt = $db->prepare("
        SELECT customer_id FROM bank_customers
        WHERE application_id = ?
    ");
    $stmt->bind_param("i", $applicationId);
    $stmt->execute();
    $result = $stmt->get_result();
    $customer = $result->fetch_assoc();
    $customerId = $customer['customer_id'];
}
```

**Result:**

- ✅ New customers: Works via application_id link
- ✅ Existing customers: Works via direct customer_id
- ✅ Both scenarios handled gracefully
- ✅ No error messages

#### Bug Fix #5 - Data Insertion Issues (Brackets in Database)

**Time:** Earlier Session

**Issue:**
Database fields showing JSON arrays/objects instead of clean values:

- `email`: `["pohojop422@bialode.com"]` instead of `pohojop422@bialode.com`
- `contact_number`: `[{"country_code_id":...` instead of `+63 9123456789`
- `city_province`: Empty or no value

**Root Cause Analysis:**

1. **Frontend**: `customer-onboarding-review.js` was using `JSON.stringify()` on arrays before adding to FormData

   ```javascript
   // This created: ["email@example.com"]
   formData.append("email", JSON.stringify(emailArray));
   ```

2. **Backend**: `create-final.php` expected direct values, not JSON strings

3. **City Province**: No database lookup implemented

**Fixes Applied:**

1. **Enhanced extractString() in create-final.php** (Lines 83-104):

   ```php
   function extractString($value) {
       // Handle null/undefined
       if ($value === null || $value === 'undefined' || $value === '') {
           return '';
       }

       // Handle arrays
       if (is_array($value)) {
           // Get first non-empty value
           foreach ($value as $item) {
               if (is_array($item) || is_object($item)) {
                   continue;
               }
               $str = trim((string)$item);
               if ($str !== '') {
                   return $str;
               }
           }
           return '';
       }

       // Handle objects - check common keys
       if (is_object($value)) {
           if (isset($value->full_number)) return trim($value->full_number);
           if (isset($value->number)) return trim($value->number);
           if (isset($value->email)) return trim($value->email);
           return '';
       }

       // Handle strings
       return trim((string)$value);
   }
   ```

2. **JSON Decoding for multipart/form-data** (Lines 47-61):

   ```php
   // Try to decode JSON strings in POST data
   foreach ($_POST as $key => $value) {
       if (is_string($value) &&
           (strpos($value, '[') === 0 || strpos($value, '{') === 0)) {
           $decoded = json_decode($value, true);
           if (json_last_error() === JSON_ERROR_NONE) {
               $_POST[$key] = $decoded;
           }
       }
   }
   ```

3. **Email/Phone Mapping Priority** (Lines 112-114):

   ```php
   // Check single values first, then arrays
   'email' => $data['email'] ??
              extractString($data['emails'] ?? []),
   'mobile_number' => $data['phone_number'] ??
                      extractString($data['phones'] ?? []),
   ```

4. **City Province Database Lookup** (Lines 318-340):

   ```php
   // Look up city and province names
   $cityId = $data['city_id'] ?? $data['city'];
   $provinceId = $data['province_id'] ?? $data['province'];

   if ($cityId && $provinceId) {
       $stmt = $db->prepare("
           SELECT c.city_name, p.province_name
           FROM cities c
           JOIN provinces p ON c.province_id = p.province_id
           WHERE c.city_id = ? AND p.province_id = ?
       ");
       $stmt->bind_param("ii", $cityId, $provinceId);
       $stmt->execute();
       $result = $stmt->get_result();

       if ($row = $result->fetch_assoc()) {
           $cityProvince = $row['city_name'] . ', ' . $row['province_name'];
       }
   }
   ```

**Result:**

- ✅ Email stored as clean string: `user@example.com`
- ✅ Contact number stored properly: `+63 9123456789`
- ✅ City province stored as: `Manila, Metro Manila`
- ✅ No brackets or JSON artifacts
- ✅ Proper data types in database

### 2025-12-07 - Database Architecture Restructure for Walk-in Registration

#### Bug Fix #4 - Fixed Dropdown Values (employment_status, source_of_funds)

**Time:** Latest Session

**Issue:** Employment status and source of funds were storing numeric IDs instead of text values

- Example: `employment_status` = "1" instead of "Employed"
- Example: `source_of_funds` = "7" instead of "Employment"

**Root Cause:**

- Dropdown `<option>` elements used `value="${status.employment_status_id}"`
- This stored the database ID (integer) instead of the readable name (string)

**Fix Applied in customer-onboarding-details.html:**

```javascript
// BEFORE (employment_status):
`<option value="${status.employment_status_id}">...</option>` ❌ Stores ID (1, 2, 3)

// AFTER:
`<option value="${status.status_name}">...</option>` ✓ Stores name ("Employed", "Self-Employed")

// BEFORE (source_of_funds):
`<option value="${source.source_id}">...</option>` ❌ Stores ID (7, 8, 9)

// AFTER:
`<option value="${source.source_name}">...</option>` ✓ Stores name ("Employment", "Business")
```

**Result:**

- ✅ `employment_status` now stores: "Employed", "Self-Employed", "Unemployed", "Retired"
- ✅ `source_of_funds` now stores: "Employment", "Business", "Pension", "Investment", "Savings"

#### Bug Fix #3 - Fixed Field Mapping (phone_number, employment_status, occupation)

**Time:** Latest Session

**Issues Found:**

1. Phone number not being recorded - extractString() was looking for wrong key
2. Employment status not being recorded - mapped incorrectly to occupation
3. Occupation confused with employment status - needs to map to job_title

**Root Cause:**

- Form has `job_title` field (actual occupation like "Manager", "Engineer")
- Form has `employment_status` field (employment type like "Employed", "Self-Employed")
- Phones array structure: `[{ country_code_id, number, full_number }]`
- extractString() was looking for `phone` key, but actual key is `full_number`

**Fixes Applied:**

1. **Updated extractString() function in create-final.php:**

   ```php
   // OLD: looked for $first['phone']
   // NEW: looks for $first['full_number'] ?? $first['number']
   ```

2. **Fixed field mapping in create-final.php:**

   ```php
   // OLD MAPPING:
   'occupation' => $data['employment_status'] ❌ (WRONG!)
   'annual_income' => $data['annual_income'] ?? $data['source_of_funds'] ❌ (WRONG!)

   // NEW MAPPING:
   'employment_status' => $data['employment_status'] ✓ (e.g., "Employed")
   'occupation' => $data['job_title'] ?? $data['occupation'] ✓ (e.g., "Manager")
   'source_of_funds' => $data['source_of_funds'] ✓ (e.g., "Employment")
   'annual_income' => $data['annual_income'] ✓ (numeric value)
   ```

3. **Added debug logging:**
   - Logs email, mobile_number, employment_status, occupation, source_of_funds
   - Helps diagnose mapping issues

**Field Clarification:**

- **employment_status**: Type of employment ("Employed", "Self-Employed", "Unemployed", "Retired")
- **occupation**: Job title/position ("Manager", "Engineer", "Teacher", "Doctor")
- **employer_name**: Company/organization name
- **source_of_funds**: Where money comes from ("Employment", "Business", "Pension", "Investment")
- **annual_income**: Numeric annual income in PHP

#### Bug Fix #2 - Fixed Data Flow from JavaScript to PHP API

**Time:** Latest Session

**Issue:** Step 2 data (id_type, id_number) was stored in sessionStorage but not sent to PHP API

- Error: "Column 'id_type' cannot be null"
- Root cause: Review page wasn't sending sessionStorage data in POST body
- API was reading from PHP session which didn't have Step 2 data

**Fixes Applied:**

1. **Modified customer-onboarding-review.js:**

   - Now reads data from sessionStorage (both step1 and step2)
   - Combines data and sends in POST body to create-final.php
   - Added logging for debugging

2. **Modified create-final.php:**

   - Now reads from POST body first (sent from JavaScript)
   - Merges POST data into PHP session for consistency
   - Fallback to PHP session if POST body is empty
   - Added logging to track data source

3. **Database schema updates:**

   ```sql
   ALTER TABLE account_applications
   MODIFY COLUMN id_type VARCHAR(50) NULL,
   MODIFY COLUMN id_number VARCHAR(100) NULL,
   MODIFY COLUMN employment_status VARCHAR(50) NULL,
   MODIFY COLUMN street_address VARCHAR(255) NULL,
   MODIFY COLUMN postal_code VARCHAR(20) NULL,
   MODIFY COLUMN account_type VARCHAR(50) NULL;
   ```

   - Made additional columns nullable as they may come from different steps or be optional

4. **Updated schema file** to reflect nullable columns

**Data Flow Now:**

1. Step 1 form → calls create-step1.php API → saves to PHP session
2. Step 2 form → saves to sessionStorage only (no API call)
3. Step 3 review → reads sessionStorage (step1 + step2) → sends combined data to create-final.php
4. create-final.php → reads POST body → merges into PHP session → creates records

#### Bug Fix #1 - Allow NULL for email/phone_number in account_applications

**Time:** Latest Session (after API update)

**Issue:** Database error "Column 'phone_number' cannot be null"

- User can provide either email OR phone number (not both required)
- But database columns were set to NOT NULL

**Fix Applied:**

```sql
ALTER TABLE account_applications
MODIFY COLUMN email VARCHAR(255) NULL COMMENT 'At least one of email or phone_number must be provided';

ALTER TABLE account_applications
MODIFY COLUMN phone_number VARCHAR(20) NULL COMMENT 'At least one of email or phone_number must be provided';
```

**Result:** Both columns now allow NULL, enforcing "at least one" at application level

#### Major Architecture Change - Separation of Authentication and Application Data

**Time:** Latest Session

##### Database Schema Changes

###### bank_customers Table - Restructured to Minimal Login Table

- **Operation:** Dropped and recreated entire table (with FOREIGN_KEY_CHECKS=0)
- **Previous:** 17+ columns including personal data, address, phone, birthday, etc.
- **Current:** 9 columns - authentication only
- **New Structure:**
  ```sql
  CREATE TABLE bank_customers (
      customer_id INT AUTO_INCREMENT PRIMARY KEY,
      email VARCHAR(255) UNIQUE,
      password_hash VARCHAR(255),
      application_id INT,
      is_verified BOOLEAN DEFAULT 0,
      is_active BOOLEAN DEFAULT 0,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      created_by_employee_id INT,
      last_login DATETIME,
      FOREIGN KEY (application_id) REFERENCES account_applications(application_id)
  )
  ```
- **Key Changes:**
  - ❌ Removed: first_name, middle_name, last_name, address, city_province, contact_number, birthday, bank_id, referral_code, verification_code, etc.
  - ✅ Added: application_id (FK link), created_by_employee_id, last_login
  - ✅ password_hash can be NULL (for walk-in registrations)

###### account_applications Table - Expanded to Comprehensive Application Data

- **Operation:** Multiple ALTER TABLE commands to add/remove/modify columns
- **Added Columns:**

  - `middle_name` VARCHAR(100) - Middle name
  - `place_of_birth` VARCHAR(255) - Birth place
  - `gender` VARCHAR(20) - Gender (Male/Female/Other)
  - `civil_status` VARCHAR(20) - Marital status
  - `nationality` VARCHAR(100) - Nationality
  - `barangay_id` INT - FK to barangays table (replaced text barangay)
  - `city_id` INT - FK to cities table (replaced text city)
  - `province_id` INT - FK to provinces table (replaced text state)
  - `occupation` VARCHAR(255) - Job occupation
  - `source_of_funds` VARCHAR(255) - Where money comes from
  - `reviewed_by_employee_id` INT - Employee who approved/rejected
  - `rejection_reason` TEXT - Reason if rejected
  - `created_by_employee_id` INT - Employee who created application

- **Modified Columns:**

  - `zip_code` → `postal_code` (renamed)

- **Removed Obsolete Columns:**
  - `barangay` (replaced by barangay_id FK)
  - `city` (replaced by city_id FK)
  - `state` (replaced by province_id FK)
  - `ssn` (already have id_number)
  - `job_title` (replaced by occupation)
  - `selected_cards` (not needed for basic application)
  - `additional_services` (not needed)
  - `marketing_consent` (not needed)

###### unified_schemalatestnow.sql - Schema File Updated

- Updated bank_customers table definition to minimal structure
- Updated account_applications table definition with all new columns
- Schema file now accurately reflects database structure

##### API Changes

###### create-final.php - Complete Rewrite

**Old Flow:**

1. Create bank_customers with all personal data
2. Create customer_profiles
3. Create account_applications (minimal)
4. Create emails, phones, addresses
5. Create customer_accounts immediately

**New Flow:**

1. Create minimal bank_customers (email, password_hash=NULL, created_by_employee_id)
2. Create comprehensive account_applications (ALL customer data, status='pending')
3. Link records (bank_customers.application_id → account_applications.application_id)
4. **NO** emails, phones, addresses, customer_profiles, or customer_accounts created
5. Return application_number (not account_number)

**Specific Changes:**

- ✅ Step 1: Insert only email, password_hash (NULL), created_by_employee_id into bank_customers
- ✅ Step 2: Insert ALL data into account_applications (30+ fields)
- ✅ Step 3: Link via UPDATE bank_customers SET application_id
- ❌ Removed: emails table insert
- ❌ Removed: phones table insert
- ❌ Removed: addresses table insert
- ❌ Removed: customer_profiles table insert
- ❌ Removed: customer_accounts creation
- ❌ Removed: bank_id, referral_code generation
- ❌ Removed: accountNumber, genderId variables
- ✅ Changed return: application_number instead of account_number

##### Architecture Benefits

**Separation of Concerns:**

- **bank_customers:** Authentication layer (login credentials only)
- **account_applications:** Application workflow (pending/approved/rejected with full data)
- **customer_profiles:** Customer master data (created after approval)
- **addresses:** Address master data (created after approval)
- **customer_accounts:** Banking accounts (created after approval)

**Walk-in Registration Flow:**

1. **Employee creates application** (current implementation):

   - Steps 1-3 submitted
   - Minimal bank_customers record (email, password=NULL, is_active=0)
   - Complete account_applications record (status='pending')
   - Link established via application_id

2. **Approval workflow** (to be implemented):

   - Employee reviews application
   - If approved:
     - Migrate data to customer_profiles
     - Migrate data to addresses
     - Create emails record
     - Create phones record
     - Create customer_accounts
     - Set bank_customers.is_active = 1
     - Notify customer

3. **Customer password setup** (to be implemented):
   - Customer receives notification with secure link
   - Customer sets password
   - bank_customers.password_hash updated
   - Customer can login to online banking

**Advantages:**

1. ✅ Clean separation between login and application data
2. ✅ Walk-in registrations don't create accounts prematurely
3. ✅ Employee approval required before account activation
4. ✅ Document verification before customer access
5. ✅ Customer sets own password (security best practice)
6. ✅ Application data preserved even if rejected
7. ✅ No data duplication between tables

### 2025-12-07 - Previous Changes

#### 1. Changed SSN Field to ID Number (Step 2)

- **File:** `customer-onboarding-security.html`
- **Change:** Renamed "Social Security Number (SSN/TIN)" field to "ID Number"
- **Details:**
  - Field name changed from `ssn` to `id_number`
  - Placeholder changed to "Enter your ID number"
  - Help text updated to "Enter the ID number shown on your selected document"
  - Max length increased from 20 to 100 characters
- **Reason:** To match the ID number field in the account_applications table and avoid confusion with SSN field

#### 2. Created application_documents Table

- **File:** Database - BankingDB
- **Change:** Added new table to store uploaded ID images and documents
- **Date Created:** 2025-12-07
- **Structure:**
  ```sql
  CREATE TABLE application_documents (
      document_id INT AUTO_INCREMENT PRIMARY KEY,
      application_id INT NOT NULL,
      document_type VARCHAR(50) NOT NULL,
      file_name VARCHAR(255) NOT NULL,
      file_path VARCHAR(500) NOT NULL,
      file_size INT,
      mime_type VARCHAR(100),
      uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_application_id (application_id),
      INDEX idx_document_type (document_type),
      FOREIGN KEY (application_id) REFERENCES account_applications(application_id) ON DELETE CASCADE
  )
  ```
- **Purpose:** Store ID front/back images uploaded during account application
- **Document Types Supported:** id_front, id_back, proof_of_income, proof_of_address

#### 3. Updated Database Schema File

- **File:** `bank-system/Basic-operation/database/sql/unified-sql-basic-ops-latest/unified_schemalatestnow.sql`
- **Change:** Added application_documents table definition
- **Date:** 2025-12-07

#### 4. Made Source of Funds & Employment Status Required

- **File:** `customer-onboarding-details.html`
- **Change:** Added `required` attribute to both fields
- **Details:**
  - Added red asterisk (\*) to field labels
  - Added error message containers
- **Date:** 2025-12-07

#### 5. Removed Identification Details Section from Step 1

- **File:** `customer-onboarding-details.html`
- **Change:** Removed SSN, ID Type, and ID Number fields from Step 1
- **Reason:** These fields are already present in Step 2 (Document Verification page)
- **Date:** 2025-12-07

---

## Original Documentation

This document outlines all data collected during the customer onboarding process and how it maps to the database schema.

## Database Tables

The customer onboarding process primarily populates the `account_applications` table, which has the following required fields:

### Required Fields in `account_applications`

#### Application Reference

- `application_number` - Auto-generated unique reference
- `application_status` - Default: 'pending'

#### Personal Information (Step 1 - Details Page)

- ✅ `first_name` - First name
- ✅ `middle_name` - Middle name
- ✅ `last_name` - Last name
- ✅ `email` - Email address (multiple emails supported)
- ✅ `phone_number` - Phone number with country code
- ✅ `date_of_birth` - Date of birth
- ✅ `gender` - Gender selection
- ✅ `marital_status` - Civil status
- ✅ `nationality` - Citizenship

#### Address Information (Step 1 - Details Page)

- ✅ `street_address` - Street address/Address line
- ✅ `city` - City (from city_id dropdown)
- ✅ `state` - Province (from province_id dropdown)
- ✅ `zip_code` - Postal code (4 digits)
- ✅ `barangay` - Barangay (optional)
- ✅ `place_of_birth` - Place of birth (city dropdown)

#### Identity Verification (Step 1 - Details Page & Step 2 - Document Verification)

- ✅ `ssn` - Social Security Number or TIN (added to Step 1)
- ✅ `id_type` - Type of ID (Passport, Driver's License, National ID, etc.)
- ✅ `id_number` - ID number (added to Step 1)

#### Document Uploads (Step 2 - Document Verification Page)

- ✅ `id_front` - Front of ID image (stored in `application_documents` table)
- ✅ `id_back` - Back of ID image (stored in `application_documents` table)

#### Employment Information (Step 1 - Financial Details)

- ✅ `employment_status` - Employment status dropdown
- ✅ `employer_name` - Name of employer
- ✅ `job_title` - Job title/position (newly added)
- ✅ `annual_income` - Annual income in PHP (newly added)
- ✅ `employer_address` - Employer address

#### Account Preferences (Step 1 - Financial Details)

- ✅ `account_type` - Savings or Checking account
- ✅ `source_of_funds` - Source of funds dropdown

#### Additional Services

- `additional_services` - JSON array of selected services (debit, online, mobile, overdraft)
- Note: This field may need to be added to the form if card/service selection is required

#### Terms Agreement (Step 3 - Review Page)

- `terms_accepted` - Boolean (accepted via review page)
- `privacy_acknowledged` - Boolean (accepted via review page)
- `marketing_consent` - Boolean (optional marketing consent)

#### Application Tracking (Auto-populated)

- `submitted_at` - Timestamp when application is submitted
- `ip_address` - User's IP address
- `user_agent` - Browser user agent

## Changes Made

### 1. customer-onboarding-details.html (Step 1)

**Added Identification Details Section:**

- SSN/TIN field with formatting
- ID Type dropdown (9 common Philippine IDs)
- ID Number field

**Added Financial Details:**

- Job Title field
- Annual Income field (numeric input with PHP currency)

### 2. customer-onboarding-security.html (Step 2)

**Completely transformed from password/MFA to Document Verification:**

- Removed password creation fields
- Removed confirm password fields
- Removed MFA method selection (phone/email)
- Removed verification code inputs
- Added ID Type selection
- Added SSN/TIN input with auto-formatting
- Added file upload for ID front
- Added file upload for ID back
- Added drag-and-drop support
- Added file preview (images and PDFs)
- Added document requirement guidelines
- Added security notice

### 3. customer-onboarding-security.js

**Complete rewrite:**

- Removed all password validation logic
- Removed MFA/verification code logic
- Removed country code API calls
- Added file upload handlers
- Added drag-and-drop functionality
- Added file validation (type, size)
- Added image preview generation
- Added SSN formatting (US: XXX-XX-XXXX, PH: XXX-XXX-XXX-XXX)
- Added document validation before submission
- Stores uploaded files and form data in sessionStorage

## Data Flow

### Step 1: Personal Details (customer-onboarding-details.html)

**Collects:**

- Personal info (name, DOB, gender, marital status, nationality)
- Address (street, barangay, city, province, postal code)
- Identification (SSN, ID type, ID number)
- Contact (emails, phone numbers with country codes)
- Financial (account type, employment status, employer info, job title, annual income, source of funds)

**Storage:** Data saved to `sessionStorage` as `onboarding_step1`

### Step 2: Document Verification (customer-onboarding-security.html)

**Collects:**

- ID type (dropdown selection)
- SSN/TIN (with formatting)
- ID front image (JPG, PNG, PDF up to 5MB)
- ID back image (JPG, PNG, PDF up to 5MB)

**Storage:**

- Form data saved to `sessionStorage` as `onboarding_step2`
- Files stored temporarily in memory until final submission

### Step 3: Review & Submit (customer-onboarding-review.html)

**Actions:**

- Displays all collected data for review
- Allows editing (returns to previous steps)
- Collects terms acceptance
- Submits complete application to backend API

**Submission:**

- Combines Step 1 + Step 2 data
- Uploads files to server
- Creates `account_applications` record
- Creates `application_documents` records for ID images
- Returns application number and status

## Missing Fields (Optional/To Be Implemented)

The following fields exist in the database but are not currently in the form:

- `selected_cards` - Card selection (may be implemented in Step 1 or separate step)
- `additional_services` - JSON array of additional services
- `reviewed_by`, `decision_by` - Admin user IDs (backend only)
- `reviewed_at`, `decision_at` - Admin action timestamps (backend only)
- `rejection_reason` - Only used if application rejected (backend only)
- `customer_id`, `account_id` - Linked when application approved (backend only)

## Validation Requirements

### Step 1 (Details Page)

- All fields marked with red asterisk (\*) are required
- Email validation (format check)
- Phone number validation (country code + number)
- Postal code: exactly 4 digits
- Date of birth: must be 18+ years old (should add validation)
- SSN/TIN: minimum 9 digits
- Dependent dropdowns: province → city → barangay

### Step 2 (Document Verification)

- ID type: required selection
- SSN/TIN: required, minimum 9 digits, auto-formatted
- ID front: required, JPG/PNG/PDF, max 5MB
- ID back: required, JPG/PNG/PDF, max 5MB
- File validation: type, size, readability

### Step 3 (Review)

- Terms acceptance: required checkbox
- Privacy acknowledgment: required checkbox
- Marketing consent: optional checkbox

## Session Storage Keys

- `onboarding_step1` - JSON string of Step 1 form data
- `onboarding_step2` - JSON string of Step 2 form data
- `step1_completed` - Boolean flag
- `step2_completed` - Boolean flag

## API Endpoints (To Be Implemented)

- `POST /api/onboarding/upload-documents.php` - Upload ID documents
- `POST /api/onboarding/submit-application.php` - Submit complete application
- `GET /api/common/get-country-codes.php` - Get country codes for phone (already exists)
- `GET /api/common/get-provinces.php` - Get provinces (already exists)
- `GET /api/common/get-cities.php` - Get cities by province (already exists)
- `GET /api/common/get-barangays.php` - Get barangays by city (already exists)

## Compliance Notes

### Philippine Banking Requirements

All essential information for opening a bank account in the Philippines is now collected:

1. ✅ Full name (first, middle, last)
2. ✅ Date of birth and place of birth
3. ✅ Address (complete with barangay, city, province, postal code)
4. ✅ Contact information (email, phone)
5. ✅ Valid government-issued ID (type, number, and scanned copies)
6. ✅ TIN/SSN for tax purposes
7. ✅ Source of funds
8. ✅ Employment information (status, employer, position, income)
9. ✅ Account type preference (Savings/Checking)

### Data Protection

- Document uploads are validated for security
- Sensitive information (SSN, ID numbers) are handled securely
- User is informed about data usage via privacy notice

## Recommendations

1. **Add age validation** - Ensure applicant is 18+ years old based on date_of_birth
2. **Add card selection** - If the bank offers different cards, add selection UI
3. **Add additional services** - Checkbox selection for online banking, mobile banking, etc.
4. **Backend validation** - Verify all data server-side before database insertion
5. **File scanning** - Implement OCR to extract and verify ID information
6. **Duplicate checking** - Check for existing applications with same SSN/email/phone
7. **Application status tracking** - Allow users to check application status
8. **Email/SMS notifications** - Send confirmation when application is submitted

## Testing Checklist

- [ ] Step 1: Fill all required fields and verify data is saved
- [ ] Step 1: Test province → city → barangay cascade
- [ ] Step 1: Test email and phone number addition/removal
- [ ] Step 1: Test SSN formatting
- [ ] Step 2: Upload valid ID images (front and back)
- [ ] Step 2: Test file drag-and-drop
- [ ] Step 2: Test file size validation (>5MB should fail)
- [ ] Step 2: Test file type validation (only JPG, PNG, PDF allowed)
- [ ] Step 2: Verify SSN carries over from Step 1
- [ ] Navigation: Test back button from Step 2 to Step 1
- [ ] Navigation: Test data persistence across steps
- [ ] Step 3: Verify all data displays correctly in review page
- [ ] Step 3: Test final submission
- [ ] Database: Verify data is inserted into account_applications table
- [ ] Database: Verify documents are stored in application_documents table

---

## Summary of Current State (as of 2025-12-07)

### Database Tables

- ✅ `account_applications` - Main application data
- ✅ `application_documents` - Stores uploaded ID images (NEWLY CREATED)

### Form Pages

1. **Step 1: Personal Details** (customer-onboarding-details.html)
   - Removed: SSN, ID Type, ID Number fields (moved to Step 2)
   - Made Required: Source of Funds, Employment Status
2. **Step 2: Document Verification** (customer-onboarding-security.html)
   - Changed: SSN field → ID Number field
   - Uploads: ID front/back images
3. **Step 3: Review** (customer-onboarding-review.html)
   - Status: Pending implementation

### Next Steps

- Implement backend API to handle document uploads
- Create Step 3 review page
- Add form validation for ID number format
- Implement application submission logic

---

**Last Updated:** 2025-12-07 20:30 (Philippine Time)
**Status:** Complete - Approval/Rejection workflows fully implemented with proper modals and data flow

---

## 📊 COMPLETE DATA FLOW DOCUMENTATION

### Overview

This section provides a comprehensive view of the entire customer account creation workflow, from initial application through approval and account creation.

---

### 🗄️ DATABASE SCHEMA HIGHLIGHTS

#### Core Tables Involved

1. **bank_customers** - Customer Authentication & Profile

   ```sql
   PRIMARY COLUMNS (after schema integration):
   - customer_id (PK) - Unique customer identifier
   - application_id (FK) - Links to account_applications
   - last_name, first_name, middle_name - Full name
   - address, city_province - Location
   - email (UNIQUE) - Login credential
   - contact_number - Phone number
   - birthday - Date of birth
   - password_hash - Encrypted password (NULL for walk-ins)
   - verification_code - Email/SMS verification
   - is_verified, is_active - Account status flags
   - bank_id (UNIQUE) - Internal bank customer ID
   - referral_code (UNIQUE) - Customer's referral code
   - referred_by_customer_id (FK) - Who referred this customer
   - total_points - Referral/loyalty points
   - created_by_employee_id - Employee who created record
   - created_at, updated_at - Audit timestamps
   ```

2. **account_applications** - Pending Application Data

   ```sql
   PRIMARY COLUMNS:
   - application_id (PK) - Unique application identifier
   - application_number (UNIQUE) - Human-readable ref (APP-YYYYMMDD-XXXX)
   - customer_id (FK, nullable) - For existing customers

   PERSONAL INFO:
   - first_name, middle_name, last_name
   - email, phone_number
   - date_of_birth, place_of_birth
   - gender, civil_status, nationality

   ADDRESS:
   - street_address
   - barangay_id (FK → barangays)
   - city_id (FK → cities)
   - province_id (FK → provinces)
   - postal_code

   IDENTIFICATION:
   - id_type (Passport, Driver's License, National ID, etc.)
   - id_number

   EMPLOYMENT:
   - employment_status (Employed, Self-Employed, etc.)
   - occupation (job title)
   - employer_name, employer_address
   - annual_income
   - source_of_funds

   ACCOUNT:
   - account_type (Savings, Checking)

   WORKFLOW:
   - application_status (pending, approved, rejected)
   - submitted_at
   - reviewed_by_employee_id (FK)
   - reviewed_at
   - rejection_reason (if rejected)
   - created_by_employee_id (FK)
   ```

3. **customer_accounts** - Active Bank Accounts
   ```sql
   PRIMARY COLUMNS:
   - account_id (PK)
   - customer_id (FK → bank_customers)
   - account_number (UNIQUE) - Format: SA-XXXX-YYYY or CHA-XXXX-YYYY
   - account_type (Savings, Checking)
   - balance (DECIMAL)
   - status (active, inactive, frozen, closed)
   - opened_date
   - opened_by_employee_id (FK)
   - created_at, updated_at
   ```

---

### 🔄 COMPLETE WORKFLOW: Walk-In Customer Registration

#### Phase 1: Application Creation (Employee Portal)

**Entry Point:** `customer-onboarding-details.html`

**Step 1 - Personal Details Collection:**

```
┌─────────────────────────────────────────────────────────────┐
│ FORM: customer-onboarding-details.html                      │
├─────────────────────────────────────────────────────────────┤
│ Collects:                                                    │
│ ✓ Personal: name, DOB, gender, civil status, nationality   │
│ ✓ Contact: emails[], phones[{country_code, number}]        │
│ ✓ Address: street, barangay, city, province, postal        │
│ ✓ Employment: status, employer, job title, income          │
│ ✓ Financial: account type, source of funds                 │
│                                                              │
│ Storage: sessionStorage['onboarding_step1']                │
│ Next: customer-onboarding-security.html                    │
└─────────────────────────────────────────────────────────────┘
```

**Step 2 - Document Verification:**

```
┌─────────────────────────────────────────────────────────────┐
│ FORM: customer-onboarding-security.html                     │
├─────────────────────────────────────────────────────────────┤
│ Collects:                                                    │
│ ✓ ID Type: Passport, Driver's License, National ID, etc.   │
│ ✓ ID Number: Unique identifier from ID                     │
│ ✓ Document Images:                                          │
│   - id_front (JPG/PNG/PDF, max 5MB)                        │
│   - id_back (JPG/PNG/PDF, max 5MB)                         │
│                                                              │
│ Validation:                                                  │
│ • File type: JPG, PNG, PDF only                            │
│ • File size: Maximum 5MB per file                          │
│ • Both front and back required                             │
│                                                              │
│ Storage: sessionStorage['onboarding_step2']                │
│ Next: customer-onboarding-review.html                      │
└─────────────────────────────────────────────────────────────┘
```

**Step 3 - Review & Submit:**

```
┌─────────────────────────────────────────────────────────────┐
│ PAGE: customer-onboarding-review.html                       │
├─────────────────────────────────────────────────────────────┤
│ Actions:                                                     │
│ 1. Reads sessionStorage (step1 + step2)                    │
│ 2. Displays all data for review                            │
│ 3. Allows editing (returns to steps)                       │
│ 4. Collects terms acceptance                               │
│ 5. Combines data into FormData                             │
│ 6. Sends to API: create-final.php                          │
│                                                              │
│ POST Data Format:                                            │
│ • Content-Type: multipart/form-data                        │
│ • Fields: All form data from step1 + step2                 │
│ • Files: id_front, id_back                                 │
│ • Arrays: JSON stringified (emails, phones)                │
└─────────────────────────────────────────────────────────────┘
```

**API Processing: create-final.php**

```php
┌─────────────────────────────────────────────────────────────┐
│ API: customer/create-final.php                              │
├─────────────────────────────────────────────────────────────┤
│ STEP 1: Data Preparation                                    │
│ ──────────────────────                                      │
│ • Decode JSON strings in $_POST                            │
│ • Extract clean values using extractString()               │
│ • Lookup city_province from database                       │
│ • Get employee_id from session                             │
│                                                              │
│ STEP 2: Generate Application Number                         │
│ ───────────────────────────────────                         │
│ • Format: APP-YYYYMMDD-XXXX                                │
│ • Example: APP-20251207-3085                               │
│ • Duplicate check with retry (max 10 attempts)             │
│                                                              │
│ STEP 3: Create bank_customers Record                        │
│ ─────────────────────────────────────                       │
│ INSERT INTO bank_customers:                                 │
│ • email, contact_number                                    │
│ • last_name, first_name, middle_name                       │
│ • address, city_province, birthday                         │
│ • password_hash = NULL (walk-in)                           │
│ • is_verified = 0, is_active = 0                           │
│ • created_by_employee_id                                   │
│                                                              │
│ → Returns: customer_id                                      │
│                                                              │
│ STEP 4: Create account_applications Record                  │
│ ──────────────────────────────────────────                  │
│ INSERT INTO account_applications:                           │
│ • application_number (generated)                           │
│ • All personal data (30+ fields)                           │
│ • application_status = 'pending'                           │
│ • created_by_employee_id                                   │
│ • submitted_at = NOW()                                     │
│                                                              │
│ → Returns: application_id                                   │
│                                                              │
│ STEP 5: Link Records                                        │
│ ────────────────                                            │
│ UPDATE bank_customers                                       │
│ SET application_id = ?                                      │
│ WHERE customer_id = ?                                       │
│                                                              │
│ STEP 6: Store Documents                                     │
│ ───────────────────────                                     │
│ INSERT INTO application_documents:                          │
│ • application_id                                           │
│ • document_type: 'id_front', 'id_back'                    │
│ • file_path, file_name, file_size, mime_type              │
│                                                              │
│ RESPONSE:                                                    │
│ {                                                            │
│   success: true,                                            │
│   application_number: "APP-20251207-3085",                 │
│   message: "Application submitted successfully"            │
│ }                                                            │
└─────────────────────────────────────────────────────────────┘
```

**Data State After Submission:**

```
DATABASE STATE:
┌──────────────────┐     ┌─────────────────────────┐
│ bank_customers   │◄────│ account_applications    │
├──────────────────┤     ├─────────────────────────┤
│ customer_id: 123 │     │ application_id: 456     │
│ application_id:  │     │ customer_id: NULL       │
│   456 ─────────────────┤ application_number:     │
│ email: user@...  │     │   APP-20251207-3085     │
│ password: NULL   │     │ application_status:     │
│ is_active: 0     │     │   'pending' ←───────────┼─ WAITING
│ is_verified: 0   │     │ first_name: John        │   FOR
└──────────────────┘     │ last_name: Doe          │   APPROVAL
                         │ account_type: Savings   │
                         │ ... (30+ fields)        │
                         └─────────────────────────┘
                                    │
                                    │ application_id (FK)
                                    ↓
                         ┌─────────────────────────┐
                         │ application_documents   │
                         ├─────────────────────────┤
                         │ document_id: 1          │
                         │ application_id: 456     │
                         │ document_type:          │
                         │   'id_front'            │
                         │ file_path: uploads/...  │
                         ├─────────────────────────┤
                         │ document_id: 2          │
                         │ application_id: 456     │
                         │ document_type:          │
                         │   'id_back'             │
                         │ file_path: uploads/...  │
                         └─────────────────────────┘
```

---

#### Phase 2: Application Review (Manager Portal)

**Entry Point:** `account-approval-dashboard.html`

**Dashboard Features:**

```
┌─────────────────────────────────────────────────────────────┐
│ DASHBOARD: account-approval-dashboard.html                  │
├─────────────────────────────────────────────────────────────┤
│ Statistics Cards:                                            │
│ ┌────────┐ ┌────────┐ ┌────────┐ ┌────────┐              │
│ │Pending │ │Approved│ │Rejected│ │  Total │              │
│ │   5    │ │   12   │ │    3   │ │   20   │              │
│ └────────┘ └────────┘ └────────┘ └────────┘              │
│                                                              │
│ Filters:                                                     │
│ • Search: by name, application number                       │
│ • Status: All / Pending / Approved / Rejected              │
│ • Account Type: All / Savings / Checking                   │
│                                                              │
│ Applications Table:                                          │
│ ┌──────────────┬────────────┬──────┬────────┬────────┐    │
│ │ App Number   │ Name       │ Type │ Date   │ Action │    │
│ ├──────────────┼────────────┼──────┼────────┼────────┤    │
│ │ APP-20251207 │ John Doe   │ Sav. │ Dec 7  │ [View] │    │
│ │ APP-20251206 │ Jane Smith │ Check│ Dec 6  │ [View] │    │
│ └──────────────┴────────────┴──────┴────────┴────────┘    │
│                                                              │
│ API: customer/get-applications.php                          │
│ • Fetches applications with filters                        │
│ • Joins with employee data for created_by info             │
│ • Returns paginated results                                │
└─────────────────────────────────────────────────────────────┘
```

**View Application Details:**

```
┌─────────────────────────────────────────────────────────────┐
│ MODAL: Application Details                                  │
├─────────────────────────────────────────────────────────────┤
│ Displays:                                                    │
│ ✓ All personal information                                 │
│ ✓ Address details (with proper city_province format)       │
│ ✓ Employment information                                   │
│ ✓ ID type and number                                       │
│ ✓ Uploaded ID images (preview)                             │
│ ✓ Application metadata (submitted date, created by)        │
│                                                              │
│ Actions:                                                     │
│ ┌──────────┐  ┌───────────┐  ┌────────┐                  │
│ │  Close   │  │  Reject   │  │ Approve│                  │
│ └──────────┘  └───────────┘  └────────┘                  │
│                     ↓              ↓                         │
│          [Rejection Modal]  [Confirmation Modal]            │
└─────────────────────────────────────────────────────────────┘
```

---

#### Phase 3: Application Approval

**User Action:** Click "Approve" → Confirmation Modal → "Yes, Approve"

```
┌─────────────────────────────────────────────────────────────┐
│ CONFIRMATION MODAL: approvalConfirmModal                    │
├─────────────────────────────────────────────────────────────┤
│                  ┌────────────┐                             │
│                  │     ?      │  ← Animated question icon   │
│                  └────────────┘     (pulsing orange)        │
│                                                              │
│         Approve Application?                                │
│                                                              │
│  Are you sure you want to approve this application?         │
│  This will create a new bank account for the customer.      │
│                                                              │
│         ┌──────────┐   ┌─────────────┐                     │
│         │  Cancel  │   │ Yes, Approve│                     │
│         └──────────┘   └─────────────┘                     │
│                              ↓                               │
│                     handleApprove()                         │
└─────────────────────────────────────────────────────────────┘
```

**API Processing: approve-application.php**

```php
┌─────────────────────────────────────────────────────────────┐
│ API: customer/approve-application.php                       │
├─────────────────────────────────────────────────────────────┤
│ INPUT: { application_id: 456 }                              │
│                                                              │
│ STEP 1: Validate Application                                │
│ ──────────────────────────                                  │
│ SELECT * FROM account_applications                          │
│ WHERE application_id = 456                                  │
│   AND application_status = 'pending'                        │
│                                                              │
│ → Ensures application exists and is pending                 │
│                                                              │
│ STEP 2: Find Customer ID                                    │
│ ─────────────────────────                                   │
│ Strategy A (Existing Customer):                             │
│   If application.customer_id IS NOT NULL                   │
│   → Use customer_id directly                                │
│                                                              │
│ Strategy B (New Customer):                                  │
│   SELECT customer_id FROM bank_customers                    │
│   WHERE application_id = 456                                │
│   → Use linked customer_id                                  │
│                                                              │
│ STEP 3: Generate Account Number                             │
│ ───────────────────────────────                             │
│ function generateAccountNumber($accountType, $db):          │
│   • Prefix: 'SA' for Savings, 'CHA' for Checking           │
│   • Random: 4-digit number (1000-9999)                     │
│   • Year: Current year (2025)                              │
│   • Format: SA-4893-2025                                   │
│   • Duplicate check with retry (max 10 attempts)           │
│   • Fallback: Use timestamp if all attempts fail           │
│                                                              │
│ STEP 4: Create Bank Account                                 │
│ ───────────────────────                                     │
│ INSERT INTO customer_accounts:                              │
│ • customer_id: 123                                         │
│ • account_number: SA-4893-2025                             │
│ • account_type: Savings                                    │
│ • balance: 0.00                                            │
│ • status: 'active'                                         │
│ • opened_date: NOW()                                       │
│ • opened_by_employee_id: (from session)                    │
│                                                              │
│ STEP 5: Update Application Status                           │
│ ─────────────────────────────                               │
│ UPDATE account_applications SET                             │
│   application_status = 'approved',                         │
│   reviewed_by_employee_id = ?,                             │
│   reviewed_at = NOW()                                      │
│ WHERE application_id = 456                                  │
│                                                              │
│ STEP 6: Activate Customer                                   │
│ ─────────────────────────                                   │
│ UPDATE bank_customers SET                                   │
│   is_active = 1                                            │
│ WHERE customer_id = 123                                     │
│                                                              │
│ RESPONSE:                                                    │
│ {                                                            │
│   success: true,                                            │
│   account_number: "SA-4893-2025",                          │
│   message: "Application approved successfully"            │
│ }                                                            │
└─────────────────────────────────────────────────────────────┘
```

**Success Modal Display:**

```
┌─────────────────────────────────────────────────────────────┐
│ SUCCESS MODAL: successModal                                 │
├─────────────────────────────────────────────────────────────┤
│                  ┌────────────┐                             │
│                  │     ✓      │  ← Animated checkmark       │
│                  └────────────┘     (green, scaling)        │
│                                                              │
│                  Success!                                    │
│                                                              │
│       Application approved successfully!                    │
│                                                              │
│         ┌──────────────────────────┐                       │
│         │ Account Number           │                       │
│         │ SA-4893-2025             │  ← Highlighted        │
│         └──────────────────────────┘                       │
│                                                              │
│                  ┌────────┐                                 │
│                  │   OK   │                                 │
│                  └────────┘                                 │
└─────────────────────────────────────────────────────────────┘
```

**Final Database State:**

```
DATABASE STATE AFTER APPROVAL:
┌──────────────────┐     ┌─────────────────────────┐
│ bank_customers   │◄────│ account_applications    │
├──────────────────┤     ├─────────────────────────┤
│ customer_id: 123 │     │ application_id: 456     │
│ application_id:  │     │ customer_id: NULL       │
│   456 ─────────────────┤ application_number:     │
│ email: user@...  │     │   APP-20251207-3085     │
│ password: NULL   │     │ application_status:     │
│ is_active: 1 ←─  │     │   'approved' ←──────────┼─ APPROVED
│ is_verified: 0   │     │ reviewed_by_employee_id:│
└──────────────────┘     │   789                   │
        │ customer_id    │ reviewed_at: NOW()      │
        │ (FK)           └─────────────────────────┘
        ↓
┌──────────────────────────────┐
│ customer_accounts             │
├──────────────────────────────┤
│ account_id: 999 (NEW!)       │
│ customer_id: 123             │
│ account_number: SA-4893-2025 │  ← ACCOUNT CREATED
│ account_type: Savings        │
│ balance: 0.00                │
│ status: active               │
│ opened_date: NOW()           │
│ opened_by_employee_id: 789   │
└──────────────────────────────┘
```

---

#### Phase 4: Application Rejection (Alternative Path)

**User Action:** Click "Reject" → Rejection Reason Modal → Submit

```
┌─────────────────────────────────────────────────────────────┐
│ REJECTION MODAL: rejectionModal                             │
├─────────────────────────────────────────────────────────────┤
│         Rejection Reason                                    │
│                                                              │
│  Please provide a reason for rejection:                     │
│                                                              │
│  ┌───────────────────────────────────────────────┐         │
│  │                                                │         │
│  │ Enter rejection reason...                     │         │
│  │                                                │         │
│  │                                                │         │
│  └───────────────────────────────────────────────┘         │
│                                                              │
│      ┌──────────┐   ┌──────────────────────┐              │
│      │  Cancel  │   │ Confirm Rejection    │              │
│      └──────────┘   └──────────────────────┘              │
│                              ↓                               │
│                     handleReject()                          │
└─────────────────────────────────────────────────────────────┘
```

**API Processing: reject-application.php**

```php
┌─────────────────────────────────────────────────────────────┐
│ API: customer/reject-application.php                        │
├─────────────────────────────────────────────────────────────┤
│ INPUT:                                                       │
│ {                                                            │
│   application_id: 456,                                      │
│   rejection_reason: "Incomplete documentation"             │
│ }                                                            │
│                                                              │
│ PROCESSING:                                                  │
│ UPDATE account_applications SET                             │
│   application_status = 'rejected',                         │
│   rejection_reason = ?,                                    │
│   reviewed_by_employee_id = ?,                             │
│   reviewed_at = NOW()                                      │
│ WHERE application_id = ?                                    │
│   AND application_status = 'pending'                        │
│                                                              │
│ RESPONSE:                                                    │
│ {                                                            │
│   success: true,                                            │
│   message: "Application rejected successfully"            │
│ }                                                            │
└─────────────────────────────────────────────────────────────┘
```

**Rejection Success Modal:**

```
┌─────────────────────────────────────────────────────────────┐
│ SUCCESS MODAL: rejectionSuccessModal                        │
├─────────────────────────────────────────────────────────────┤
│                  ┌────────────┐                             │
│                  │     ✕      │  ← Animated X icon          │
│                  └────────────┘     (red, pulsing)          │
│                                                              │
│          Application Rejected                               │
│                                                              │
│  The application has been rejected successfully.            │
│  The applicant will be notified of the decision.            │
│                                                              │
│                  ┌────────┐                                 │
│                  │   OK   │                                 │
│                  └────────┘                                 │
└─────────────────────────────────────────────────────────────┘
```

**Database State After Rejection:**

```
┌──────────────────┐     ┌─────────────────────────┐
│ bank_customers   │◄────│ account_applications    │
├──────────────────┤     ├─────────────────────────┤
│ customer_id: 123 │     │ application_id: 456     │
│ application_id:  │     │ application_status:     │
│   456 ─────────────────┤   'rejected' ←──────────┼─ REJECTED
│ email: user@...  │     │ rejection_reason:       │
│ password: NULL   │     │  "Incomplete docs"      │
│ is_active: 0     │     │ reviewed_by_employee_id:│   NO
│ is_verified: 0   │     │   789                   │   ACCOUNT
└──────────────────┘     │ reviewed_at: NOW()      │   CREATED
                         └─────────────────────────┘
```

---

### 🎯 KEY DATA FLOW POINTS

#### 1. **Email & Contact Number Extraction**

**Problem:** Frontend sends arrays, backend needs single values

**Solution:**

```php
// extractString() function handles multiple formats
extractString(['user@example.com']) → 'user@example.com'
extractString('user@example.com') → 'user@example.com'
extractString([{email: 'user@example.com'}]) → 'user@example.com'
extractString([{full_number: '+63 9123456789'}]) → '+63 9123456789'
```

#### 2. **City Province Lookup**

**Problem:** Form has city_id and province_id, but need readable "City, Province"

**Solution:**

```php
// Database JOIN to get names
SELECT c.city_name, p.province_name
FROM cities c
JOIN provinces p ON c.province_id = p.province_id
WHERE c.city_id = ? AND p.province_id = ?

Result: "Manila, Metro Manila"
```

#### 3. **Application Number Generation**

**Format:** `APP-YYYYMMDD-XXXX`

```php
$date = date('Ymd'); // 20251207
$random = str_pad(rand(1000, 9999), 4, '0'); // 3085
$appNumber = "APP-{$date}-{$random}"; // APP-20251207-3085

// With duplicate check and retry
```

#### 4. **Account Number Generation**

**Format:** `PREFIX-XXXX-YYYY`

```php
$prefix = ($accountType === 'Savings') ? 'SA' : 'CHA';
$random = str_pad(rand(1000, 9999), 4, '0'); // 4893
$year = date('Y'); // 2025
$accountNumber = "{$prefix}-{$random}-{$year}"; // SA-4893-2025

// With duplicate check and retry (max 10 attempts)
// Timestamp fallback if all attempts fail
```

#### 5. **Customer ID Resolution (Dual Strategy)**

**For New Customers:**

```sql
-- Application links to customer via application_id
SELECT customer_id FROM bank_customers
WHERE application_id = ?
```

**For Existing Customers:**

```sql
-- Application has direct customer_id reference
SELECT customer_id FROM account_applications
WHERE application_id = ?
-- Use customer_id directly if NOT NULL
```

#### 6. **Modal State Management**

**Problem:** Success modal showing old data after different actions

**Solution:**

```javascript
// Always hide account details by default
function showSuccessMessage(message) {
  document.getElementById("successMessage").textContent = message;
  document.getElementById("successDetails").style.display = "none";
  successModal.show();
}

// Only show account details when explicitly set
if (result.account_number) {
  document.getElementById("successAccountNumber").textContent =
    result.account_number;
  document.getElementById("successDetails").style.display = "block";
}

// Use separate modals for different actions
// - successModal: For approval with account number
// - rejectionSuccessModal: For rejection without account data
```

---

### 📋 COMPLETE FIELD MAPPING

#### From Form → account_applications Table

```
PERSONAL INFORMATION:
┌─────────────────────┬──────────────────────────┐
│ Form Field          │ Database Column          │
├─────────────────────┼──────────────────────────┤
│ first_name          │ first_name               │
│ middle_name         │ middle_name              │
│ last_name           │ last_name                │
│ date_of_birth       │ date_of_birth            │
│ place_of_birth      │ place_of_birth           │
│ gender              │ gender                   │
│ civil_status        │ civil_status             │
│ nationality         │ nationality              │
└─────────────────────┴──────────────────────────┘

CONTACT INFORMATION:
┌─────────────────────┬──────────────────────────┐
│ Form Field          │ Database Column          │
├─────────────────────┼──────────────────────────┤
│ emails[0]           │ email (extracted)        │
│ phones[0].full_num  │ phone_number (extracted) │
└─────────────────────┴──────────────────────────┘

ADDRESS:
┌─────────────────────┬──────────────────────────┐
│ Form Field          │ Database Column          │
├─────────────────────┼──────────────────────────┤
│ street_address      │ street_address           │
│ barangay_id         │ barangay_id (FK)         │
│ city_id             │ city_id (FK)             │
│ province_id         │ province_id (FK)         │
│ postal_code         │ postal_code              │
└─────────────────────┴──────────────────────────┘

IDENTIFICATION:
┌─────────────────────┬──────────────────────────┐
│ Form Field          │ Database Column          │
├─────────────────────┼──────────────────────────┤
│ id_type             │ id_type                  │
│ id_number           │ id_number                │
│ id_front (file)     │ application_documents    │
│ id_back (file)      │ application_documents    │
└─────────────────────┴──────────────────────────┘

EMPLOYMENT & FINANCIAL:
┌─────────────────────┬──────────────────────────┐
│ Form Field          │ Database Column          │
├─────────────────────┼──────────────────────────┤
│ employment_status   │ employment_status        │
│ job_title           │ occupation               │
│ employer_name       │ employer_name            │
│ employer_address    │ employer_address         │
│ annual_income       │ annual_income            │
│ source_of_funds     │ source_of_funds          │
└─────────────────────┴──────────────────────────┘

ACCOUNT:
┌─────────────────────┬──────────────────────────┐
│ Form Field          │ Database Column          │
├─────────────────────┼──────────────────────────┤
│ account_type        │ account_type             │
└─────────────────────┴──────────────────────────┘
```

#### From account_applications → bank_customers (On Approval)

```
┌────────────────────────┬──────────────────────┐
│ account_applications   │ bank_customers       │
├────────────────────────┼──────────────────────┤
│ first_name             │ first_name           │
│ middle_name            │ middle_name          │
│ last_name              │ last_name            │
│ email                  │ email                │
│ phone_number           │ contact_number       │
│ date_of_birth          │ birthday             │
│ street_address +       │ address              │
│ city + province        │ city_province        │
│ application_id         │ application_id (FK)  │
└────────────────────────┴──────────────────────┘
```

---

### 🔐 SECURITY & VALIDATION

#### Input Validation

1. **File Uploads:**

   - Allowed types: JPG, PNG, PDF
   - Max size: 5MB per file
   - Scanned for malicious content
   - Stored outside web root

2. **Data Sanitization:**

   - All inputs sanitized using `mysqli_real_escape_string()`
   - Prepared statements used for SQL queries
   - XSS prevention in output display

3. **Session Management:**
   - Employee authentication required
   - Session timeout after inactivity
   - CSRF protection on forms

#### Database Constraints

1. **Foreign Keys:**

   - Maintain referential integrity
   - Cascade deletes where appropriate
   - Prevent orphaned records

2. **Unique Constraints:**

   - email (bank_customers)
   - account_number (customer_accounts)
   - application_number (account_applications)
   - bank_id (bank_customers)
   - referral_code (bank_customers)

3. **Check Constraints:**
   - Date validations (DOB, opened_date)
   - Status enums (pending/approved/rejected)
   - Balance >= 0

---

### 📊 STATISTICS & REPORTING

Dashboard provides real-time statistics:

```sql
-- Pending applications
SELECT COUNT(*) FROM account_applications
WHERE application_status = 'pending'

-- Approved today
SELECT COUNT(*) FROM account_applications
WHERE application_status = 'approved'
  AND DATE(reviewed_at) = CURDATE()

-- Rejected today
SELECT COUNT(*) FROM account_applications
WHERE application_status = 'rejected'
  AND DATE(reviewed_at) = CURDATE()

-- Total applications
SELECT COUNT(*) FROM account_applications
```

---

### 🎨 UI/UX ENHANCEMENTS

#### Modal Animations

1. **Approval Confirmation Modal:**

   - Pulsing orange question mark icon
   - Ripple effect on icon
   - Bouncing animation
   - Smooth hover transitions on buttons

2. **Success Modal (Approval):**

   - Scaling green checkmark
   - Account number highlighted
   - Fade-in animation

3. **Rejection Success Modal:**
   - Red X icon with pulse effect
   - Distinct color scheme
   - Separate from approval success

#### Responsive Design

- Mobile-friendly modals
- Responsive tables with horizontal scroll
- Touch-friendly buttons
- Adaptive layouts for different screen sizes

---

**End of Data Flow Documentation**
