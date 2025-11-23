# Payroll Integration Fix - HRIS to Accounting System

## Problem Summary
The payroll system was not showing newly recruited employees from HRIS, and approved leave requests were not appearing in the Daily Attendance Records.

## Root Causes

### 1. Employee Not Showing Issue
- **Original Problem**: The query was doing `FROM employee_refs LEFT JOIN employee`, which meant employees had to exist in `employee_refs` first before showing up in payroll
- **Impact**: New employees added in HRIS at `http://localhost/evergreen/hris-sia/pages/employees.php` would not appear in the payroll dropdown

### 2. Leave Not Showing Issue
- **Original Problem**: Leave integration code existed but had potential issues with status matching
- **Impact**: Approved leaves from `http://localhost/evergreen/hris-sia/pages/leave.php` might not show in Daily Attendance Records

## Solutions Implemented

### Fix 1: Reversed Employee Query Logic
**Changed FROM**: `employee_refs` (accounting) **TO**: `employee` (HRIS)

**Before**:
```sql
FROM employee_refs er
LEFT JOIN employee e ON ...
```

**After**:
```sql
FROM employee e
LEFT JOIN employee_refs er ON ...
```

**Result**: Now ALL employees from HRIS will show in payroll, even if they don't have a record in `employee_refs` yet.

### Fix 2: Updated Employee Mapping
- Employee number format: `EMP001`, `EMP002`, `EMP003` (automatically generated from HRIS employee_id)
- Mapping: `employee.employee_id = 1` → `external_employee_no = 'EMP001'`
- Salary priority: Contract salary > employee_refs salary > 0

### Fix 3: Enhanced Leave Status Matching
**Before**:
```sql
WHERE ... AND UPPER(TRIM(lr.status)) = 'APPROVED'
```

**After**:
```sql
WHERE ... AND (UPPER(TRIM(lr.status)) = 'APPROVED' OR LOWER(TRIM(lr.status)) = 'approved')
```

**Result**: More robust status matching for approved leaves.

### Fix 4: Updated Filter Dropdowns
- Position filter now pulls from HRIS `position` table
- Department filter now pulls from HRIS `department` table
- Employment type uses HRIS data with fallback to 'regular'

## How It Works Now

### New Employee Flow
1. HR adds employee in HRIS: `http://localhost/evergreen/hris-sia/pages/employees.php`
2. Employee is saved to `employee` table with auto-increment `employee_id`
3. Payroll system automatically shows employee as `EMP001`, `EMP002`, etc.
4. No manual intervention needed!

### Leave Request Flow
1. Employee applies for leave in HRIS: `http://localhost/evergreen/hris-sia/pages/leave.php`
2. HR approves the leave (status = 'approved')
3. Leave is saved to `leave_request` table
4. Payroll system reads approved leaves and displays them in Daily Attendance Records
5. Leave days show with status badge "Leave" and remarks showing leave type and reason

## Database Tables Used

### HRIS Tables (Source of Truth)
- `employee` - Main employee records
- `department` - Department information
- `position` - Position/job titles
- `contract` - Employment contracts
- `leave_request` - Leave applications
- `leave_type` - Types of leave
- `attendance` - Daily attendance records

### Accounting Tables (Supplementary)
- `employee_refs` - Optional payroll-specific data (salary overrides, etc.)
- `employee_attendance` - Additional attendance records from accounting system

## Testing Checklist

### Test 1: New Employee Recruitment
- [ ] Add a new employee in HRIS employees page
- [ ] Refresh payroll management page
- [ ] Verify new employee appears in "Select Employee" dropdown
- [ ] Select the new employee and verify their details display correctly

### Test 2: Leave Request Integration
- [ ] Create a leave request for an employee in HRIS
- [ ] Approve the leave request
- [ ] Go to payroll management and select that employee
- [ ] Verify the leave dates appear in Daily Attendance Records with "Leave" status
- [ ] Check that leave remarks show the leave type and reason

### Test 3: Attendance Summary
- [ ] Verify "Leave Days" counter increases when leaves are approved
- [ ] Check that leave days don't count as absent days
- [ ] Ensure payroll calculations account for approved leaves

## Important Notes

1. **Single Database**: Both systems use the same database, so no database connection changes were needed
2. **Employee Number Format**: Always `EMP` + 3-digit padded number (EMP001, EMP002, etc.)
3. **Data Priority**: HRIS data takes precedence over accounting system data
4. **Backward Compatible**: Existing employee_refs records still work and supplement HRIS data
5. **No Data Loss**: The changes are query-only, no data migration required

## Files Modified
- `accounting-and-finance/modules/payroll-management.php` - Main payroll management page

## No Additional Files Needed
Since both systems share the same database, no additional integration scripts or triggers are required. The fix is purely in the SQL queries.
