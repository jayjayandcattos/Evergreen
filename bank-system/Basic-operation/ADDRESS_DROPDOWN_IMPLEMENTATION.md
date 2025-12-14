# Address Dropdown Implementation - Complete Guide

## Overview

This document outlines the complete implementation of cascading location dropdowns for the customer onboarding form, replacing text input fields with database-driven dropdown selects for provinces, cities, and barangays.

## Changes Summary

### 1. Frontend Changes (`public/customer-onboarding-details.html`)

#### Replaced HTML Elements

- **Province**: Changed from text input to `<select>` dropdown with `name="province_id"`
- **City**: Changed from text input to `<select>` dropdown with `name="city_id"` (disabled until province selected)
- **Barangay**: Added new `<select>` dropdown with `name="barangay_id"` (disabled until city selected)
- **Postal Code**: Changed to `readonly` (auto-filled from city/barangay selection)

#### Added JavaScript Functions

```javascript
// Load provinces from database on page load
async function loadProvinces()

// Load cities when province is selected
async function loadCities(provinceId)

// Load barangays when city is selected
async function loadBarangays(cityId)

// Setup event handlers for cascading dropdowns
function setupLocationHandlers()
```

#### Initialization

```javascript
document.addEventListener("DOMContentLoaded", async function () {
  await loadCountryCodes();
  await loadProvinces(); // NEW: Load provinces on page load
  setupFormHandlers();
  setupLocationHandlers(); // NEW: Setup cascading dropdowns
  restoreFormData();
});
```

### 2. Backend API Endpoints (`api/location/`)

Created three new API endpoints:

#### `get-provinces.php`

- **Method**: GET
- **Returns**: All provinces ordered by name
- **Response Format**:

```json
{
  "success": true,
  "data": [
    {
      "province_id": 1,
      "province_name": "Metro Manila",
      "region": "NCR"
    }
  ]
}
```

#### `get-cities.php`

- **Method**: GET
- **Parameter**: `province_id` (required)
- **Returns**: Cities/municipalities for selected province
- **Response Format**:

```json
{
  "success": true,
  "data": [
    {
      "city_id": 1,
      "city_name": "Quezon City",
      "city_type": "city",
      "zip_code": "1100"
    }
  ]
}
```

#### `get-barangays.php`

- **Method**: GET
- **Parameter**: `city_id` (required)
- **Returns**: Barangays for selected city
- **Response Format**:

```json
{
  "success": true,
  "data": [
    {
      "barangay_id": 1,
      "barangay_name": "Barangay Commonwealth",
      "zip_code": "1121"
    }
  ]
}
```

### 3. Validation Updates (`includes/validation.php`)

Updated `validateStep1Data()` function to validate location foreign keys:

```php
// Validate province_id
if (empty($data['province_id']) || !is_numeric($data['province_id'])) {
    $errors['province_id'] = 'Province is required';
} else {
    // Check if province exists in database
    $stmt = $pdo->prepare("SELECT province_id FROM provinces WHERE province_id = ?");
    $stmt->execute([$data['province_id']]);
    if (!$stmt->fetch()) {
        $errors['province_id'] = 'Invalid province selected';
    }
}

// Validate city_id (must belong to selected province)
if (empty($data['city_id']) || !is_numeric($data['city_id'])) {
    $errors['city_id'] = 'City is required';
} else {
    $stmt = $pdo->prepare("SELECT city_id FROM cities WHERE city_id = ? AND province_id = ?");
    $stmt->execute([$data['city_id'], $data['province_id']]);
    if (!$stmt->fetch()) {
        $errors['city_id'] = 'Invalid city selected or city does not belong to selected province';
    }
}

// Validate barangay_id (must belong to selected city)
if (empty($data['barangay_id']) || !is_numeric($data['barangay_id'])) {
    $errors['barangay_id'] = 'Barangay is required';
} else {
    $stmt = $pdo->prepare("SELECT barangay_id FROM barangays WHERE barangay_id = ? AND city_id = ?");
    $stmt->execute([$data['barangay_id'], $data['city_id']]);
    if (!$stmt->fetch()) {
        $errors['barangay_id'] = 'Invalid barangay selected or barangay does not belong to selected city';
    }
}
```

### 4. Step 1 API Update (`api/customer/create-step1.php`)

Updated validation data structure:

```php
$validationData = [
    // ... other fields ...
    'province_id' => $data['province_id'] ?? '',  // Changed from 'province'
    'city_id' => $data['city_id'] ?? '',          // Changed from 'city'
    'barangay_id' => $data['barangay_id'] ?? '',  // NEW field
    // ... other fields ...
];
```

### 5. Final Customer Creation (`api/customer/create-final.php`)

Updated to use location foreign keys:

#### Data Mapping

```php
$mappedData = [
    // ... other fields ...
    'province_id' => $data['province_id'] ?? null,   // Changed from 'province'
    'city_id' => $data['city_id'] ?? null,           // Changed from 'city'
    'barangay_id' => $data['barangay_id'] ?? null,   // NEW field
    // ... other fields ...
];
```

#### Required Fields Validation

```php
$requiredFields = [
    // ... other fields ...
    'province_id', 'city_id', 'barangay_id',  // Changed from 'city', 'province'
    // ... other fields ...
];
```

#### Database Insertion

```php
$stmt = $db->prepare("
    INSERT INTO addresses (
        customer_id, address_line, barangay_id, city_id, province_id,
        postal_code, address_type, is_primary, created_at
    ) VALUES (
        :customer_id, :address_line, :barangay_id, :city_id, :province_id,
        :postal_code, 'home', 1, NOW()
    )
");

$stmt->bindParam(':barangay_id', $mappedData['barangay_id']);
$stmt->bindParam(':city_id', $mappedData['city_id']);
$stmt->bindParam(':province_id', $mappedData['province_id']);
```

## Database Structure

### Tables Involved

#### provinces

```sql
CREATE TABLE provinces (
    province_id INT AUTO_INCREMENT PRIMARY KEY,
    province_name VARCHAR(100) NOT NULL,
    country VARCHAR(100) NOT NULL DEFAULT 'Philippines',
    region VARCHAR(100) DEFAULT NULL,
    CHECK (country = 'Philippines')
);
```

#### cities

```sql
CREATE TABLE cities (
    city_id INT AUTO_INCREMENT PRIMARY KEY,
    city_name VARCHAR(100) NOT NULL,
    province_id INT NOT NULL,
    city_type ENUM('city','municipality') DEFAULT 'city',
    zip_code VARCHAR(10) DEFAULT NULL,
    FOREIGN KEY (province_id) REFERENCES provinces(province_id) ON DELETE CASCADE
);
```

#### barangays

```sql
CREATE TABLE barangays (
    barangay_id INT AUTO_INCREMENT PRIMARY KEY,
    barangay_name VARCHAR(100) NOT NULL,
    city_id INT NOT NULL,
    zip_code VARCHAR(10) DEFAULT NULL,
    FOREIGN KEY (city_id) REFERENCES cities(city_id) ON DELETE CASCADE
);
```

#### addresses

```sql
CREATE TABLE addresses (
    address_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    address_line VARCHAR(255) DEFAULT NULL,
    barangay_id INT DEFAULT NULL,
    city_id INT DEFAULT NULL,
    province_id INT DEFAULT NULL,
    postal_code VARCHAR(10) DEFAULT NULL,
    address_type ENUM('home','work','billing','shipping','other') DEFAULT 'home',
    is_primary TINYINT(1) DEFAULT 0,
    FOREIGN KEY (customer_id) REFERENCES bank_customers(customer_id),
    FOREIGN KEY (barangay_id) REFERENCES barangays(barangay_id),
    FOREIGN KEY (city_id) REFERENCES cities(city_id),
    FOREIGN KEY (province_id) REFERENCES provinces(province_id)
);
```

## User Flow

1. **Page Load**: Provinces dropdown is populated automatically
2. **Select Province**: User selects a province
   - Cities dropdown becomes enabled
   - Cities for that province are loaded
   - Barangays dropdown is reset and disabled
3. **Select City**: User selects a city
   - Barangays dropdown becomes enabled
   - Barangays for that city are loaded
   - Postal code is auto-filled if city has a zip_code
4. **Select Barangay**: User selects a barangay
   - If barangay has a specific zip_code, it overrides the city's zip_code
5. **Submit Form**: Form submits with foreign key IDs (province_id, city_id, barangay_id)

## Testing Instructions

### 1. Verify Database Has Location Data

```sql
-- Check provinces
SELECT COUNT(*) FROM provinces;

-- Check cities
SELECT COUNT(*) FROM cities;

-- Check barangays
SELECT COUNT(*) FROM barangays;
```

If counts are 0, you need to import location data first.

### 2. Test Frontend Dropdowns

1. Open: `http://localhost/Evergreen/bank-system/Basic-operation/public/customer-onboarding-details.html`
2. Open browser console (F12)
3. Verify you see: "Loading provinces from: ..." message
4. Verify you see: "Loaded X provinces" message
5. Check province dropdown has options
6. Select a province → verify cities load
7. Select a city → verify barangays load and postal code fills
8. Select a barangay → verify postal code updates (if barangay has specific ZIP)

### 3. Test API Endpoints

```bash
# Test get-provinces
curl http://localhost/Evergreen/bank-system/Basic-operation/api/location/get-provinces.php

# Test get-cities (replace 1 with actual province_id)
curl http://localhost/Evergreen/bank-system/Basic-operation/api/location/get-cities.php?province_id=1

# Test get-barangays (replace 1 with actual city_id)
curl http://localhost/Evergreen/bank-system/Basic-operation/api/location/get-barangays.php?city_id=1
```

### 4. Test Form Submission

1. Fill all required fields in the form
2. Select province → city → barangay
3. Submit form
4. Check browser console for any errors
5. Verify in database:

```sql
SELECT a.*,
       p.province_name,
       c.city_name,
       b.barangay_name
FROM addresses a
LEFT JOIN provinces p ON a.province_id = p.province_id
LEFT JOIN cities c ON a.city_id = c.city_id
LEFT JOIN barangays b ON a.barangay_id = b.barangay_id
ORDER BY a.address_id DESC
LIMIT 1;
```

## Benefits of This Implementation

1. **Data Consistency**: No more typos or variations in location names
2. **Data Integrity**: Foreign key relationships ensure valid location references
3. **Better UX**: Users can't select invalid combinations (e.g., wrong city for province)
4. **Auto-fill**: Postal codes automatically filled based on selection
5. **Scalable**: Easy to add/update location data without code changes
6. **Reporting**: Easy to query customers by location using JOINs
7. **Standardization**: All addresses follow Philippine Standard Geographic Code (PSGC)

## Troubleshooting

### Dropdowns Not Loading

- Check browser console for API errors
- Verify API endpoints return valid JSON
- Check database connection in API files
- Ensure location tables have data

### "Invalid province selected" Error

- Verify province_id exists in database
- Check if province_id is being sent as integer, not string
- Clear session data and try again

### Postal Code Not Auto-filling

- Check if cities/barangays table has zip_code values
- Verify JavaScript event handlers are attached
- Check console for JavaScript errors

### Database Errors on Form Submit

- Verify all three foreign keys (province_id, city_id, barangay_id) are being sent
- Check if addresses table has correct foreign key constraints
- Ensure IDs are valid integers

## Next Steps

1. **Import Location Data**: Use the Python script to import PSGC Excel data
2. **Test Complete Flow**: Test customer registration from start to finish
3. **Update Reports**: Modify any reports that query address data to use JOINs
4. **Migration**: If you have existing customer addresses, create migration script to convert text to IDs

## Files Modified

1. ✅ `public/customer-onboarding-details.html` - Added cascading dropdowns
2. ✅ `api/location/get-provinces.php` - NEW endpoint
3. ✅ `api/location/get-cities.php` - NEW endpoint
4. ✅ `api/location/get-barangays.php` - NEW endpoint
5. ✅ `includes/validation.php` - Updated to validate foreign keys
6. ✅ `api/customer/create-step1.php` - Updated data structure
7. ✅ `api/customer/create-final.php` - Updated to use foreign keys

## Support

If you encounter any issues:

1. Check browser console for JavaScript errors
2. Check PHP error logs for backend errors
3. Verify database structure matches expected schema
4. Test API endpoints directly with curl/Postman
5. Check session data structure
