# Account Opening - Valid ID Upload Feature

## Overview

Added functionality to capture and store valid ID information when customers open additional accounts. The system now collects ID type, ID number, and front/back images of the ID, while automatically pulling customer information from their existing account.

## Changes Made

### 1. Database Schema Updates

**File:** `database/sql/add_id_images_to_applications.sql`

Added three new columns to `account_applications` table:

- `id_front_image` - VARCHAR(255) - Path to front image of valid ID
- `id_back_image` - VARCHAR(255) - Path to back image of valid ID
- `id_uploaded_at` - DATETIME - Timestamp when ID images were uploaded

**Status:** ✅ Migration executed successfully

### 2. Frontend Changes

**File:** `public/account-opening.html`

Added new section: **Valid ID Verification**

- ID Type dropdown with 10 Philippine ID options:
  - Driver's License
  - Passport
  - SSS ID
  - UMID
  - PhilHealth ID
  - Postal ID
  - Voter's ID
  - PRC ID
  - National ID (PhilSys)
  - TIN ID
- ID Number text input
- ID Front Image file upload with preview
- ID Back Image file upload with preview
- Image preview CSS styling

All ID fields are required (marked with red asterisk).

### 3. JavaScript Updates

**File:** `assets/js/account-opening.js`

**New Functions:**

- `handleImagePreview(event, previewDivId)` - Displays preview of uploaded image
  - Validates file type (JPG, PNG, GIF only)
  - Validates file size (max 5MB)
  - Shows thumbnail preview below upload button
  - Clears invalid uploads with error message

**Modified Functions:**

- `setupFormHandlers()` - Added event listeners for ID image uploads
- `validateForm()` - Added validation for:
  - ID type selection
  - ID number entry
  - Front image upload
  - Back image upload
- `collectFormData()` - Changed from JSON to FormData object to support file uploads
  - Now includes `id_type`, `id_number`, `id_front_image`, `id_back_image`
- `handleFormSubmit()` - Removed `Content-Type: application/json` header (browser sets multipart/form-data automatically)

### 4. Backend API Updates

**File:** `api/customer/open-account.php`

**Input Handling:**

- Changed from `json_decode(file_get_contents('php://input'))` to `$_POST` for form data
- Added `$_FILES` handling for image uploads

**New Validation:**

```php
if (empty($input['id_type'])) {
    $errors['id_type'] = 'ID type is required';
}

if (empty($input['id_number'])) {
    $errors['id_number'] = 'ID number is required';
}

if (!isset($_FILES['id_front_image']) || $_FILES['id_front_image']['error'] !== UPLOAD_ERR_OK) {
    $errors['id_front_image'] = 'Front image of ID is required';
}

if (!isset($_FILES['id_back_image']) || $_FILES['id_back_image']['error'] !== UPLOAD_ERR_OK) {
    $errors['id_back_image'] = 'Back image of ID is required';
}
```

**New Feature: Auto-fill from Existing Account**

```php
// Fetch customer information from existing account's application
$stmt = $db->prepare("
    SELECT aa.*
    FROM customer_accounts ca
    INNER JOIN bank_customers bc ON ca.customer_id = bc.customer_id
    INNER JOIN account_applications aa ON bc.application_id = aa.application_id
    WHERE ca.account_number = :account_number
    LIMIT 1
");
```

This retrieves all customer data from the existing account's application, so the user doesn't need to re-enter:

- Name (first, middle, last)
- Date of birth
- Place of birth
- Gender
- Civil status
- Nationality
- Email
- Phone number
- Address (street, barangay, city, province, postal code)
- Employment status
- Employer name
- Occupation
- Annual income
- Source of funds

**File Upload Handling:**

```php
$uploadDir = '../../uploads/id_images/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Upload front image
if (isset($_FILES['id_front_image']) && $_FILES['id_front_image']['error'] === UPLOAD_ERR_OK) {
    $ext = pathinfo($_FILES['id_front_image']['name'], PATHINFO_EXTENSION);
    $fileName = 'id_front_' . $customerId . '_' . time() . '.' . $ext;
    $targetPath = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES['id_front_image']['tmp_name'], $targetPath)) {
        $idFrontPath = 'uploads/id_images/' . $fileName;
    }
}
```

**New Application Record Creation:**
When a new account is opened, creates a new `account_applications` record that:

- Copies all customer data from existing application
- Adds NEW ID information (type, number, images)
- Marks as 'approved' status (since customer already verified)
- Links to the account opening transaction

## File Upload Specifications

**Allowed Formats:** JPG, JPEG, PNG, GIF  
**Maximum File Size:** 5MB per image  
**Naming Convention:** `id_front_{customer_id}_{timestamp}.{ext}` and `id_back_{customer_id}_{timestamp}.{ext}`  
**Storage Location:** `uploads/id_images/`  
**Database Storage:** Relative path stored in `account_applications` table

## Data Flow

1. **User enters existing account number** → Account verified via `get-customer-account.php`
2. **User selects ID type** → Dropdown selection
3. **User enters ID number** → Text input
4. **User uploads front image** → File upload with instant preview and validation
5. **User uploads back image** → File upload with instant preview and validation
6. **User selects new account type** → Savings or Checking
7. **User submits form** → JavaScript validates all fields
8. **API receives FormData** → Validates file uploads
9. **API fetches customer data** → From existing account's application
10. **API uploads files** → Saves to `uploads/id_images/` folder
11. **API creates account** → New account in `customer_accounts`
12. **API creates application record** → Combines existing data + new ID info
13. **Success response** → Shows new account number

## Security Considerations

✅ **File Type Validation:** Client-side (JS) and server-side (PHP)  
✅ **File Size Validation:** Max 5MB enforced  
✅ **Customer Authentication:** Must be logged in (`$_SESSION['customer_id']`)  
✅ **Account Ownership:** Verifies existing account belongs to customer  
✅ **Upload Directory Permissions:** 0777 (adjust in production)  
⚠️ **TODO:** Add file virus scanning in production  
⚠️ **TODO:** Sanitize uploaded file names further  
⚠️ **TODO:** Consider encrypting stored images

## Testing Checklist

- [ ] Load account opening page without errors
- [ ] Select ID type from dropdown
- [ ] Enter ID number
- [ ] Upload front image (valid format) - should show preview
- [ ] Upload back image (valid format) - should show preview
- [ ] Try uploading invalid file type - should show error
- [ ] Try uploading file > 5MB - should show error
- [ ] Verify account number - should show customer name
- [ ] Select account type (Savings or Checking)
- [ ] Submit form - should create account successfully
- [ ] Check `account_applications` table - should have new record with ID data
- [ ] Check `uploads/id_images/` folder - should contain uploaded files
- [ ] Verify account number matches pattern (SA-XXXX-2025 or CA-XXXX-2025)

## Database Query Example

To view all account applications with ID images:

```sql
SELECT
    application_id,
    application_number,
    CONCAT(first_name, ' ', last_name) as customer_name,
    id_type,
    id_number,
    id_front_image,
    id_back_image,
    id_uploaded_at,
    account_type,
    application_status,
    submitted_at
FROM account_applications
WHERE id_front_image IS NOT NULL
ORDER BY submitted_at DESC;
```

## Next Steps

1. **Test the complete flow** - Open a new account with ID upload
2. **Verify files are saved** - Check `uploads/id_images/` directory
3. **Review data integrity** - Check `account_applications` records
4. **Optional Enhancements:**
   - Add ability to view uploaded IDs in admin panel
   - Add image compression to reduce file sizes
   - Add OCR to auto-extract ID number from images
   - Add face matching between ID and customer photo
   - Add expiry date field for IDs

## Files Modified/Created

### Created:

- `database/sql/add_id_images_to_applications.sql` - Migration script
- `docs/ACCOUNT_OPENING_ID_UPLOAD.md` - This documentation

### Modified:

- `public/account-opening.html` - Added ID upload fields
- `assets/js/account-opening.js` - Added file upload handling
- `api/customer/open-account.php` - Added file processing and data copying

### Directory Created:

- `uploads/id_images/` - Storage for uploaded ID images
