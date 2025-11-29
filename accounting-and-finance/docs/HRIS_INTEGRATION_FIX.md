# HRIS-Accounting Integration Fix

## Problem
Employees 1-25 from `Sampled_data.sql` were not showing HRIS attendance and leave records in the accounting payroll system, while newly added employees (26+) worked correctly.

## Root Cause
The employee_id extraction from `external_employee_no` was not robust enough and lacked fallback mechanisms for edge cases.

## Solution Implemented

### 1. Enhanced Employee ID Extraction
**Files Modified:**
- `accounting-and-finance/modules/payroll-management.php`
- `accounting-and-finance/modules/api/payroll-calculation.php`

**Changes:**
- Added multiple fallback methods to extract `employee_id` from `external_employee_no`
- Added direct database lookup if regex extraction fails
- Added comprehensive error logging for debugging
- Validates employee_id before executing queries

### 2. Improved Error Handling
- Added error logging for attendance query execution
- Added error logging for leave query execution
- Added validation checks before running queries
- Better error messages for troubleshooting

### 3. Employee Refs Sync Utility
**New File:** `accounting-and-finance/utils/sync_employee_refs.php`

This utility script:
- Checks all HRIS employees for corresponding `employee_refs` records
- Auto-creates missing `employee_refs` records with proper format (EMP001, EMP002, etc.)
- Updates existing records if HRIS data has changed
- Verifies sync completion

**Usage:**
Access via browser: `http://localhost/Evergreen/accounting-and-finance/utils/sync_employee_refs.php`

Or run via command line:
```bash
php accounting-and-finance/utils/sync_employee_refs.php
```

## Testing Steps

1. **Run the sync utility:**
   - Navigate to `accounting-and-finance/utils/sync_employee_refs.php`
   - Verify all employees 1-25 have employee_refs records created/updated

2. **Test Employee 1 (EMP001):**
   - Go to Payroll Management
   - Select employee "EMP001"
   - Check if HRIS attendance records appear
   - Check if HRIS leave records appear

3. **Test Employee 25 (EMP025):**
   - Select employee "EMP025"
   - Verify attendance and leave records show correctly

4. **Compare with Employee 26+:**
   - Select a newly added employee
   - Verify behavior is consistent with employees 1-25

## Expected Behavior

- All employees (1-25 and 26+) should show HRIS attendance records
- All employees should show HRIS leave records
- No difference in behavior between pre-existing and newly added employees
- Error logs should show successful employee_id extraction

## Troubleshooting

If attendance/leave records still don't show:

1. **Check Error Logs:**
   - Look for error messages in PHP error log
   - Check for "Could not extract employee_id" messages
   - Verify employee_id extraction is successful

2. **Verify Database:**
   - Ensure `employee` table has records for employees 1-25
   - Ensure `employee_refs` table has records with format EMP001-EMP025
   - Check that `attendance` table has records with correct `employee_id`
   - Check that `leave_request` table has records with correct `employee_id`

3. **Run Sync Utility:**
   - Run `sync_employee_refs.php` to ensure all records are properly linked
   - Verify no missing employee_refs records

4. **Check Employee Selection:**
   - Verify the dropdown shows employees in format "Name (EMP001)"
   - Ensure `$selected_employee` variable contains "EMP001" format

## Technical Details

### Employee ID Extraction Logic
1. First attempts regex match: `/EMP(\d+)/i` on `external_employee_no`
2. Falls back to direct numeric conversion if input is numeric
3. Final fallback: Direct database lookup using JOIN query
4. Validates extracted employee_id is > 0 before use

### Query Structure
- Attendance query uses UNION ALL to combine:
  - HRIS `attendance` table (uses `employee_id`)
  - Accounting `employee_attendance` table (uses `external_employee_no`)
- Leave query uses `employee_id` directly from HRIS `leave_request` table

## Files Modified

1. `accounting-and-finance/modules/payroll-management.php`
   - Enhanced employee_id extraction (lines ~370-400)
   - Improved attendance query error handling
   - Improved leave query error handling

2. `accounting-and-finance/modules/api/payroll-calculation.php`
   - Enhanced employee_id extraction (lines ~25-50)
   - Added fallback lookup mechanism

3. `accounting-and-finance/utils/sync_employee_refs.php` (NEW)
   - Complete sync utility for employee_refs records

## Notes

- The fix maintains backward compatibility with existing functionality
- All changes include comprehensive error logging for debugging
- The sync utility can be run multiple times safely (idempotent)

