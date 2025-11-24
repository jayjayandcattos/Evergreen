# Evergreen Form Updates

## Recent Enhancements

### 1. Auto-Populate Zip Code
**Feature**: Zip code automatically fills when user selects a city

**How it works**:
- User selects Province → Cities load
- User selects City → Zip code automatically populates
- If zip code not found in database, field becomes editable for manual entry

**Supported Cities** (50+ major cities):
- All Metro Manila cities (Quezon City: 1100, Makati: 1200, Manila: 1000, etc.)
- Major provincial cities (Cebu: 6000, Davao: 8000, Baguio: 2600, etc.)

**To add more zip codes**:
Edit `get_locations.php` and add to the `$zipCodes` array:
```php
$zipCodes = [
    "City Name" => "ZipCode",
    // Example:
    "Quezon City" => "1100",
];
```

### 2. Age Validation (18+ Only)
**Feature**: Applicants must be at least 18 years old

**Validations**:
1. **Date Picker Restriction**: Cannot select dates less than 18 years ago
2. **Form Validation**: Shows error if user tries to submit with age < 18
3. **Helper Text**: Displays "You must be at least 18 years old" below date field

**Error Messages**:
- "You must be at least 18 years old to apply"

### 3. Province and City Dropdowns
**Feature**: Dynamic location selection using Philippine locations API

**How it works**:
- Province dropdown loads all 81 Philippine provinces
- City dropdown dynamically loads based on selected province
- City dropdown is disabled until province is selected
- Uses the same API as signup.php (`get_locations.php`)

### 4. Card Selection
**Feature**: Users can select which cards they want to apply for

**Available Cards**:
- **Debit Card** - Access your funds instantly
- **Credit Card** - Build credit & earn rewards
- **Prepaid Card** - Control your spending

**Features**:
- Multiple card selection allowed
- Visual feedback when cards are selected
- Stored in database as comma-separated values

## Form Fields

### Personal Information (Step 1)
- First Name *
- Last Name *
- Email Address *
- Phone Number *
- Date of Birth * (must be 18+)
- Street Address *
- Province * (dropdown)
- City/Municipality * (dropdown)
- Zip Code * (auto-populated)

### Identity Verification (Step 2)
- Social Security Number *
- ID Type * (Driver's License, Passport, State ID)
- ID Number *

### Employment Information (Step 2)
- Employment Status *
- Employer Name *
- Job Title *
- Annual Income (USD) *

### Account Preferences (Step 3)
- Account Type * (Checking, Savings, or Both)
- Card Selection (Debit, Credit, Prepaid)
- Additional Services (Online Banking, Mobile Banking, Overdraft Protection, SMS Alerts)
- Terms and Conditions *
- Privacy Policy *
- Marketing Consent

## Validation Rules

### Age Validation
```javascript
// User must be 18 years or older
const birthDate = new Date(dateString);
const today = new Date();
let age = today.getFullYear() - birthDate.getFullYear();
// Account for month/day differences
return age >= 18;
```

### Required Fields
All fields marked with * are required and validated before proceeding to next step.

### Email Validation
Must be valid email format: `example@domain.com`

### SSN Validation
Must match format: `123-45-6789` or `123456789`

### Income Validation
Must be numeric value

## Database Schema

### account_applications table
```sql
- selected_cards TEXT (comma-separated: debit, credit, prepaid)
- additional_services TEXT (comma-separated: online, mobile, overdraft, alerts)
- date_of_birth DATE (must be 18+ years ago)
- city VARCHAR(100) (from dropdown)
- state VARCHAR(100) (province from dropdown)
- zip_code VARCHAR(20) (auto-populated)
```

## API Endpoints

### get_locations.php

**Get Provinces**:
```
GET get_locations.php?action=get_provinces
Returns: ["Metro Manila", "Cebu", "Davao", ...]
```

**Get Cities**:
```
GET get_locations.php?action=get_cities&province=Metro%20Manila
Returns: ["Quezon City", "Makati", "Manila", ...]
```

**Get Zip Code**:
```
GET get_locations.php?action=get_zipcode&city=Quezon%20City
Returns: {"zipcode": "1100"}
```

## Testing

### Test Age Validation
1. Try to select a date less than 18 years ago
2. Date picker should not allow it
3. If you manually enter it, form validation will catch it

### Test Zip Code Auto-Population
1. Select "Metro Manila" as province
2. Select "Quezon City" as city
3. Zip code should automatically fill with "1100"
4. Try a city not in the database - field becomes editable

### Test Card Selection
1. Click on card options or checkboxes
2. Selected cards should highlight
3. Multiple cards can be selected
4. Submit form and check database

## Troubleshooting

### Zip code not auto-populating
- Check if city is in the `$zipCodes` array in `get_locations.php`
- Check browser console for API errors
- Verify `get_locations.php` is accessible

### Age validation not working
- Check browser console for JavaScript errors
- Verify date format is correct (YYYY-MM-DD)
- Check that `setMaxBirthDate()` function is called on page load

### Cities not loading
- Verify province name matches exactly in `get_locations.php`
- Check browser console for API errors
- Ensure `get_locations.php` returns valid JSON

## Future Enhancements

Potential improvements:
1. Add more zip codes for all Philippine cities
2. Add barangay selection
3. Integrate with postal code API
4. Add real-time age calculation display
5. Add card eligibility checker based on income
