# Barangay-Based Zip Code System

## Overview
The form now includes a complete address hierarchy with automatic zip code population based on the selected barangay.

## Address Selection Flow

```
Province → City/Municipality → Barangay → Zip Code (Auto-filled)
```

### Step-by-Step Process:
1. **Select Province** → Cities load
2. **Select City** → Barangays load
3. **Select Barangay** → Zip code automatically populates (read-only)
4. **Enter Street Address** → Complete address

## Features

### 1. Cascading Dropdowns
- **Province Dropdown**: All 81 Philippine provinces
- **City Dropdown**: Loads cities based on selected province
- **Barangay Dropdown**: Loads barangays based on selected city
- Each dropdown is disabled until the previous selection is made

### 2. Automatic Zip Code Population
- Zip code field is **read-only** (cannot be manually edited)
- Automatically fills when barangay is selected
- Each barangay has its own unique zip code
- Cursor shows "not-allowed" to indicate field is locked

### 3. Sample Data Included

**Cities with Barangay Data:**
- Quezon City (20 barangays)
- Makati (25 barangays)
- Manila (15 barangays)
- Pasig (30 barangays)
- Taguig (25 barangays)
- Parañaque (16 barangays)
- Cebu City (15 barangays)
- Davao City (12 barangays)

**Total**: 158 barangays with unique zip codes

## Database Schema

### account_applications table
```sql
street_address VARCHAR(255) NOT NULL,
barangay VARCHAR(150) NOT NULL,
city VARCHAR(100) NOT NULL,
state VARCHAR(100) NOT NULL,  -- Province
zip_code VARCHAR(20) NOT NULL  -- Auto-populated, read-only
```

## API Endpoints

### get_locations.php

**1. Get Provinces**
```
GET get_locations.php?action=get_provinces
Returns: ["Metro Manila", "Cebu", ...]
```

**2. Get Cities**
```
GET get_locations.php?action=get_cities&province=Metro%20Manila
Returns: ["Quezon City", "Makati", ...]
```

**3. Get Barangays** (NEW)
```
GET get_locations.php?action=get_barangays&city=Quezon%20City
Returns: ["Project 6", "Fairview", "Commonwealth", ...]
```

**4. Get Zip Code** (UPDATED)
```
GET get_locations.php?action=get_zipcode&barangay=Project%206
Returns: {"zipcode": "1100"}
```

## Example Zip Codes

### Quezon City
- Project 6 → 1100
- Fairview → 1118
- Commonwealth → 1121
- Batasan Hills → 1126

### Makati
- Poblacion → 1210
- Bel-Air → 1209
- Forbes Park → 1220
- Rockwell → 1200

### Manila
- Ermita → 1000
- Malate → 1004
- Binondo → 1006
- Sampaloc → 1008

### Pasig
- Kapitolyo → 1603
- Ugong → 1604
- Manggahan → 1611

### Taguig
- Fort Bonifacio → 1634
- Upper Bicutan → 1630
- Western Bicutan → 1630

## Adding More Barangays

To add more barangays and zip codes, edit `get_locations.php`:

### 1. Add Barangays to City
```php
$barangays = [
    "Your City" => [
        "Barangay 1",
        "Barangay 2",
        "Barangay 3"
    ],
];
```

### 2. Add Zip Codes for Barangays
```php
$barangayZipCodes = [
    "Barangay 1" => "1234",
    "Barangay 2" => "1235",
    "Barangay 3" => "1236",
];
```

## Validation

### Required Fields
- Province *
- City/Municipality *
- Barangay *
- Street Address *
- Zip Code * (auto-filled)

### Field States
- **Province**: Always enabled
- **City**: Enabled after province selection
- **Barangay**: Enabled after city selection
- **Zip Code**: Always read-only, auto-populated

## Migration

If you already have the `account_applications` table, run:

```sql
USE BankingDB;

ALTER TABLE account_applications 
ADD COLUMN barangay VARCHAR(150) NOT NULL DEFAULT '' 
AFTER street_address;
```

Or use the migration file: `sql/add_barangay_column.sql`

## Testing

### Test Complete Address Flow
1. Open `evergreen_form.php`
2. Select "Metro Manila" as province
3. Select "Quezon City" as city
4. Select "Project 6" as barangay
5. Zip code should automatically show "1100"
6. Try to edit zip code → should not be editable

### Test Different Cities
1. Select "Makati" → Choose "Poblacion" → Zip: 1210
2. Select "Manila" → Choose "Ermita" → Zip: 1000
3. Select "Pasig" → Choose "Kapitolyo" → Zip: 1603

### Test Cities Without Barangay Data
1. Select a city not in the barangay list
2. Barangay dropdown should show "No barangays available"
3. Barangay dropdown should be disabled

## Benefits

✅ **Accurate Addresses**: Ensures correct zip codes for each barangay
✅ **User-Friendly**: No need to remember or look up zip codes
✅ **Data Consistency**: Prevents incorrect zip code entries
✅ **Complete Hierarchy**: Province → City → Barangay → Zip Code
✅ **Validation**: All address fields are required and validated

## Future Enhancements

Potential improvements:
1. Add all Philippine barangays (42,000+)
2. Integrate with Philippine Postal Corporation API
3. Add street name suggestions
4. Add landmark/building selection
5. Validate address completeness
6. Add map integration for address verification

## Troubleshooting

### Barangays not loading
- Check if city is in `$barangays` array in `get_locations.php`
- Verify API endpoint is accessible
- Check browser console for errors

### Zip code not auto-filling
- Verify barangay is in `$barangayZipCodes` array
- Check API response in browser network tab
- Ensure barangay name matches exactly (case-sensitive)

### Dropdown not enabling
- Check JavaScript console for errors
- Verify previous dropdown has a value selected
- Check that API returns valid data

## Data Sources

The barangay and zip code data is based on:
- Philippine Postal Corporation (PHLPost) zip code directory
- Philippine Statistics Authority (PSA) barangay listings
- Local government unit (LGU) official records

**Note**: This is sample data for major cities. For production use, you should obtain complete and official data from PHLPost or PSA.
