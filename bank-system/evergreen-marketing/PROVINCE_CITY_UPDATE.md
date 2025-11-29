# Province and City Dropdown Update

## What Was Changed

The signup form now has **separate dropdowns** for Province and City/Municipality instead of a single text input field.

## Files Created/Modified

### 1. **get_locations.php** (NEW)
- API endpoint that provides Philippine provinces and cities
- Contains complete data for all 81 provinces and their cities/municipalities
- Two actions:
  - `?action=get_provinces` - Returns list of all provinces
  - `?action=get_cities&province=PROVINCE_NAME` - Returns cities for a specific province

### 2. **signup.php** (MODIFIED)
- Replaced single "City/Province" text input with two dropdowns:
  - **Province dropdown**: Lists all Philippine provinces
  - **City/Municipality dropdown**: Dynamically loads cities based on selected province
- Added CSS styling for select elements
- Added JavaScript to:
  - Load provinces on page load
  - Load cities when a province is selected
  - Enable/disable city dropdown based on province selection
- Updated form validation to include province field
- Backend now combines city and province as "City, Province" format for storage

## How It Works

1. **Page loads** → Provinces are fetched from API and populated in dropdown
2. **User selects province** → Cities for that province are fetched and populated
3. **User selects city** → Form can be submitted
4. **Form submits** → Data is stored as "City, Province" (e.g., "Quezon City, Metro Manila")

## Features

✅ Dynamic city loading based on province selection
✅ City dropdown is disabled until province is selected
✅ Complete Philippine location data (81 provinces, 1000+ cities)
✅ Form validation for both fields
✅ Error handling and visual feedback
✅ Maintains existing form styling and behavior

## Testing

To test the feature:
1. Open signup.php in browser
2. Select a province from the dropdown
3. City dropdown should enable and populate with cities
4. Select a city and complete the form
5. Submit and verify data is stored correctly
