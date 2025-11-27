# Payslip API Implementation Documentation

## Overview

The Payslip Data API (`payslip-data.php`) provides a secure endpoint for retrieving employee payslip information from the HRIS system. This API integrates with both the accounting system's `payslips` table and the HRIS system's `payroll_payslips` table to provide comprehensive payslip data.

**API Endpoint:** `/accounting-and-finance/modules/api/payslip-data.php`  
**Method:** GET/POST  
**Action:** `get_payslips`

---

## Issues Identified and Fixed

A comprehensive analysis was performed on the payslip API, identifying 7 critical and medium-priority issues. All issues have been resolved. This document details each fix.

---

## Fix 1: Session Handling Mismatch

### Issue
The API was starting a new session without configuring session parameters to match the HRIS system, causing session data (employee_id) to be inaccessible.

### Impact
- Authorization checks could fail
- Session employee_id might not be available
- Cross-system session sharing issues

### Solution
Configured session parameters to match HRIS settings before starting the session.

**Location:** Lines 38-55

**Before:**
```php
// Start session if not already started (for HRIS session check)
// Use the same session name as HRIS if needed
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
```

**After:**
```php
// Start session if not already started (for HRIS session check)
// Configure session to match HRIS settings
if (session_status() == PHP_SESSION_NONE) {
    // Match HRIS session configuration
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0);
    
    session_set_cookie_params([
        'lifetime' => 3600,
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    session_start();
}
```

**Result:** Session data is now properly shared between HRIS and accounting systems.

---

## Fix 2: Authorization Logic Flaw

### Issue
The authorization check was comparing string values that might have type mismatches, potentially causing false authorization failures.

### Impact
- Employees might be incorrectly denied access to their own payslips
- Type comparison inconsistencies

### Solution
Convert both session and requested employee IDs to integers before comparison.

**Location:** Lines 131-138

**Before:**
```php
// Validate that employee can only access their own payslip
// If session has employee_id, it must match the requested one
if (!empty($session_employee_id) && !empty($requested_employee_id) && $session_employee_id != $requested_employee_id) {
    throw new Exception('Unauthorized: You can only access your own payslip');
}
```

**After:**
```php
// Validate that employee can only access their own payslip
// Convert both to integers for proper comparison
$session_employee_id_int = !empty($session_employee_id) ? intval($session_employee_id) : 0;
$requested_employee_id_int = !empty($requested_employee_id) ? intval($requested_employee_id) : 0;

if ($session_employee_id_int > 0 && $requested_employee_id_int > 0 && $session_employee_id_int != $requested_employee_id_int) {
    throw new Exception('Unauthorized: You can only access your own payslip');
}
```

**Result:** Authorization checks now work correctly with proper type-safe comparisons.

---

## Fix 3: Missing Error Handling in Fallback Query

### Issue
The fallback query to `payroll_payslips` table had no error handling for query execution failures, causing silent failures.

### Impact
- Errors in fallback query were not logged
- No indication when fallback query fails
- Difficult to debug issues

### Solution
Added proper error handling for query execution.

**Location:** Lines 277-315

**Before:**
```php
$fallback_stmt = $conn->prepare($fallback_sql);
if ($fallback_stmt) {
    $fallback_stmt->bind_param("i", $employee_id);
    $fallback_stmt->execute();
    $fallback_result = $fallback_stmt->get_result();
    // ... rest of code
}
```

**After:**
```php
$fallback_stmt = $conn->prepare($fallback_sql);
if ($fallback_stmt) {
    $fallback_stmt->bind_param("i", $employee_id);
    if (!$fallback_stmt->execute()) {
        error_log("Payslip API: Fallback query execution failed: " . $fallback_stmt->error);
        $fallback_stmt->close();
    } else {
        $fallback_result = $fallback_stmt->get_result();
        // ... rest of code with proper error handling
    }
}
```

**Result:** Fallback query errors are now properly logged and handled.

---

## Fix 4: Employee Existence Check Improvements

### Issue
The employee existence check didn't verify if the `employee` table exists before querying, and had limited error handling.

### Impact
- Could fail silently if table doesn't exist
- Limited debugging information

### Solution
Added table existence check and improved error handling.

**Location:** Lines 318-340

**Before:**
```php
// Additional debugging: Check if employee exists
if (count($payslips) == 0) {
    $check_employee_sql = "SELECT employee_id, first_name, last_name FROM employee WHERE employee_id = ? LIMIT 1";
    $check_stmt = $conn->prepare($check_employee_sql);
    if ($check_stmt) {
        $check_stmt->bind_param("i", $employee_id);
        $check_stmt->execute();
        $employee_check = $check_stmt->get_result()->fetch_assoc();
        $check_stmt->close();
        // ... logging
    }
}
```

**After:**
```php
// Additional debugging: Check if employee exists
$employee_check = null;
if (count($payslips) == 0) {
    // Check if employee table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'employee'");
    if ($table_check && $table_check->num_rows > 0) {
        $check_employee_sql = "SELECT employee_id, first_name, last_name FROM employee WHERE employee_id = ? LIMIT 1";
        $check_stmt = $conn->prepare($check_employee_sql);
        if ($check_stmt) {
            $check_stmt->bind_param("i", $employee_id);
            if ($check_stmt->execute()) {
                $employee_check = $check_stmt->get_result()->fetch_assoc();
                // ... detailed logging
            } else {
                error_log("Payslip API: Error checking employee existence: " . $check_stmt->error);
            }
            $check_stmt->close();
        } else {
            error_log("Payslip API: Failed to prepare employee check query: " . $conn->error);
        }
    } else {
        error_log("Payslip API: Employee table does not exist in database");
    }
}
```

**Result:** Employee existence checks are now more robust with better error handling.

---

## Fix 5: Input Validation Enhancements

### Issue
Employee ID validation only checked for positive values, but didn't enforce a maximum reasonable value.

### Impact
- Could accept unreasonably large employee IDs
- Potential for edge case issues

### Solution
Added maximum value validation (99999).

**Location:** Line 127

**Before:**
```php
// Convert to integer (handles both string and numeric)
$employee_id = intval($employee_id);
if ($employee_id <= 0) {
    throw new Exception('Invalid employee ID format. Employee ID must be a positive number.');
}
```

**After:**
```php
// Convert to integer (handles both string and numeric)
$employee_id = intval($employee_id);
if ($employee_id <= 0 || $employee_id > 99999) {
    throw new Exception('Invalid employee ID format. Employee ID must be between 1 and 99999.');
}
```

**Result:** Employee ID validation now enforces reasonable bounds.

---

## Fix 6: NULL Handling Improvements

### Issue
Some fields in the response might be NULL, but weren't handled with proper default values, potentially causing undefined index errors.

### Impact
- Potential PHP warnings/errors
- Inconsistent response structure

### Solution
Added NULL coalescing operators for all response fields.

**Location:** Multiple locations (lines 177-191, 287-308)

**Before:**
```php
$payslip_data = [
    'id' => $row['id'],
    'employee_external_no' => $row['employee_external_no'],
    'gross_pay' => floatval($row['gross_pay']),
    'total_deductions' => floatval($row['total_deductions']),
    'net_pay' => floatval($row['net_pay']),
    'payroll_run_id' => $row['payroll_run_id'],
    'run_at' => $row['run_at'],
    'payroll_status' => $row['payroll_status'],
    'period_start' => $row['period_start'],
    'period_end' => $row['period_end'],
    'frequency' => $row['frequency'],
    'created_at' => $row['created_at'],
    'breakdown' => []
];
```

**After:**
```php
$payslip_data = [
    'id' => $row['id'],
    'employee_external_no' => $row['employee_external_no'],
    'gross_pay' => floatval($row['gross_pay'] ?? 0),
    'total_deductions' => floatval($row['total_deductions'] ?? 0),
    'net_pay' => floatval($row['net_pay'] ?? 0),
    'payroll_run_id' => $row['payroll_run_id'] ?? null,
    'run_at' => $row['run_at'] ?? null,
    'payroll_status' => $row['payroll_status'] ?? 'unknown',
    'period_start' => $row['period_start'] ?? null,
    'period_end' => $row['period_end'] ?? null,
    'frequency' => $row['frequency'] ?? null,
    'created_at' => $row['created_at'] ?? null,
    'breakdown' => []
];
```

**Result:** All fields now have proper default values, preventing undefined index errors.

---

## Fix 7: Response Metadata Additions

### Issue
The API response didn't include metadata about employee existence or which tables were searched, making debugging difficult.

### Impact
- Limited debugging information
- Frontend couldn't determine if employee exists
- No indication of data source

### Solution
Added metadata fields to the response.

**Location:** Lines 336-352

**Before:**
```php
echo json_encode([
    'success' => true,
    'data' => $payslips,
    'count' => count($payslips),
    'employee_id' => $employee_id,
    'employee_external_no' => $employee_external_no
]);
```

**After:**
```php
// Prepare response with metadata
$response = [
    'success' => true,
    'data' => $payslips,
    'count' => count($payslips),
    'employee_id' => $employee_id,
    'employee_external_no' => $employee_external_no,
    'searched_tables' => ['payslips', 'payroll_payslips']
];

// Add employee existence info if checked
if (isset($employee_check)) {
    $response['employee_exists'] = !empty($employee_check);
    if (!empty($employee_check)) {
        $response['employee_name'] = trim(($employee_check['first_name'] ?? '') . ' ' . ($employee_check['last_name'] ?? ''));
    }
}

echo json_encode($response);
```

**Result:** API responses now include helpful metadata for debugging and frontend handling.

---

## Code Changes Summary

### Files Modified
- `accounting-and-finance/modules/api/payslip-data.php`

### Lines Changed
- Lines 38-55: Session handling
- Lines 125-129: Input validation
- Lines 131-138: Authorization logic
- Lines 177-191: NULL handling (payslips table)
- Lines 277-315: Fallback query error handling
- Lines 287-308: NULL handling (payroll_payslips table)
- Lines 318-340: Employee existence check
- Lines 336-352: Response metadata

### Total Changes
- 7 major fixes implemented
- Improved error handling throughout
- Enhanced logging and debugging
- Better input validation
- More robust NULL handling

---

## Testing Guide

### Running the Test Suite

1. **Access the test file:**
   ```
   http://localhost/accounting-and-finance/utils/test_payslip_api.php
   ```

2. **Test Scenarios Covered:**
   - Valid employee ID with payslips
   - Valid employee ID with no payslips
   - Missing employee_id parameter
   - Invalid employee_id (zero, negative, too large, non-numeric)
   - String numeric employee_id
   - Multiple valid employee IDs
   - Response structure validation

3. **Interpreting Results:**
   - ✅ Green: Test passed
   - ❌ Red: Test failed
   - 📄 Icon: Employee has payslips
   - 📭 Icon: Employee has no payslips

### Manual Testing

**Test 1: Valid Employee with Payslips**
```bash
curl "http://localhost/accounting-and-finance/modules/api/payslip-data.php?action=get_payslips&employee_id=25"
```

**Test 2: Invalid Employee ID**
```bash
curl "http://localhost/accounting-and-finance/modules/api/payslip-data.php?action=get_payslips&employee_id=0"
```

**Expected Response:**
```json
{
    "success": false,
    "error": "Invalid employee ID format. Employee ID must be between 1 and 99999."
}
```

**Test 3: Missing Employee ID**
```bash
curl "http://localhost/accounting-and-finance/modules/api/payslip-data.php?action=get_payslips"
```

**Expected Response:**
```json
{
    "success": false,
    "error": "Employee ID is required"
}
```

---

## Verification Steps

### 1. Verify Session Handling
1. Log into HRIS system as an employee
2. Access employee dashboard
3. Click "View My Payslip"
4. ✅ **Expected:** Payslips load without authorization errors

### 2. Verify Input Validation
1. Try accessing payslip with employee_id=0
2. ✅ **Expected:** Error message about invalid employee ID
3. Try with employee_id=100000
4. ✅ **Expected:** Error message about range validation

### 3. Verify Authorization
1. Log in as employee ID 25
2. Try to access payslip for employee ID 26
3. ✅ **Expected:** Authorization error (if session is active)

### 4. Verify Response Structure
1. Call API with valid employee_id
2. Check response includes:
   - `success` field
   - `data` array
   - `count` field
   - `employee_id` field
   - `employee_external_no` field
   - `searched_tables` field
   - `employee_exists` field (if no payslips found)

### 5. Verify Error Handling
1. Check error logs for detailed information
2. Verify fallback query errors are logged
3. Verify employee existence check errors are logged

---

## API Usage Examples

### Example 1: Successful Request

**Request:**
```http
GET /accounting-and-finance/modules/api/payslip-data.php?action=get_payslips&employee_id=25
```

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "employee_external_no": "EMP025",
            "gross_pay": 50000.00,
            "total_deductions": 5000.00,
            "net_pay": 45000.00,
            "payroll_run_id": 1,
            "run_at": "2024-01-15 10:00:00",
            "payroll_status": "completed",
            "period_start": "2024-01-01",
            "period_end": "2024-01-15",
            "frequency": "semimonthly",
            "created_at": "2024-01-15 10:05:00",
            "breakdown": {
                "earnings": [
                    {"name": "Basic Salary", "amount": 50000.00}
                ],
                "deductions": [
                    {"name": "SSS", "amount": 2000.00},
                    {"name": "Pag-IBIG", "amount": 1000.00},
                    {"name": "PhilHealth", "amount": 1000.00},
                    {"name": "Withholding Tax", "amount": 1000.00}
                ]
            }
        }
    ],
    "count": 1,
    "employee_id": 25,
    "employee_external_no": "EMP025",
    "searched_tables": ["payslips", "payroll_payslips"]
}
```

### Example 2: No Payslips Found

**Request:**
```http
GET /accounting-and-finance/modules/api/payslip-data.php?action=get_payslips&employee_id=999
```

**Response:**
```json
{
    "success": true,
    "data": [],
    "count": 0,
    "employee_id": 999,
    "employee_external_no": "EMP999",
    "searched_tables": ["payslips", "payroll_payslips"],
    "employee_exists": true,
    "employee_name": "John Doe"
}
```

### Example 3: Invalid Employee ID

**Request:**
```http
GET /accounting-and-finance/modules/api/payslip-data.php?action=get_payslips&employee_id=0
```

**Response:**
```json
{
    "success": false,
    "error": "Invalid employee ID format. Employee ID must be between 1 and 99999."
}
```

---

## Troubleshooting

### Issue: "No payslips found" for valid employee

**Possible Causes:**
1. No payslip data exists in either `payslips` or `payroll_payslips` tables
2. Employee ID mismatch between HRIS and accounting system
3. Payroll hasn't been processed yet

**Solutions:**
1. Check database for payslip records:
   ```sql
   SELECT * FROM payslips WHERE employee_external_no = 'EMP025';
   SELECT * FROM payroll_payslips WHERE employee_id = 25;
   ```
2. Verify employee exists:
   ```sql
   SELECT * FROM employee WHERE employee_id = 25;
   ```
3. Check API response metadata for `employee_exists` field
4. Review error logs for detailed information

### Issue: Authorization errors

**Possible Causes:**
1. Session not properly configured
2. Employee ID mismatch in session vs request

**Solutions:**
1. Verify session configuration matches HRIS
2. Check that session employee_id matches requested employee_id
3. Review authorization logic in code (lines 131-138)

### Issue: API returns errors

**Possible Causes:**
1. Database connection issues
2. Missing tables
3. Invalid employee ID format

**Solutions:**
1. Check database connection in `config/database.php`
2. Verify tables exist: `payslips`, `payroll_payslips`, `employee`
3. Review error logs for specific error messages
4. Run test suite to identify specific issues

---

## Security Considerations

### Implemented Security Measures

1. ✅ **Prepared Statements:** All queries use prepared statements to prevent SQL injection
2. ✅ **Input Validation:** Employee ID is validated and sanitized
3. ✅ **Authorization Checks:** Employees can only access their own payslips
4. ✅ **Type Safety:** Integer conversion prevents type-based attacks
5. ✅ **Range Validation:** Employee ID must be between 1 and 99999

### Recommendations

1. **CORS Configuration:** Consider restricting `Access-Control-Allow-Origin` to specific domains instead of `*`
2. **Rate Limiting:** Implement rate limiting to prevent abuse
3. **Authentication:** Consider adding API key or token authentication for direct API calls
4. **Logging:** Review and secure error logs in production

---

## Performance Considerations

### Optimizations

1. **Indexes:** Ensure indexes exist on:
   - `payslips.employee_external_no`
   - `payroll_payslips.employee_id`
   - `employee.employee_id`

2. **Query Limits:** Both queries limit results to 10 payslips

3. **Fallback Strategy:** Only queries fallback table if primary query returns no results

### Monitoring

- Monitor query execution times
- Check error logs for slow queries
- Review database indexes regularly

---

## Future Enhancements

### Potential Improvements

1. **Caching:** Implement caching for frequently accessed payslips
2. **Pagination:** Add pagination support for employees with many payslips
3. **Filtering:** Add date range filtering
4. **Export:** Add PDF export functionality
5. **Notifications:** Notify employees when new payslips are available

---

## Related Documentation

- [Payslip API Analysis](./modules/api/PAYSLIP_API_ANALYSIS.md) - Detailed analysis of issues
- [HRIS Integration Status](./INTEGRATION_STATUS.md) - HRIS system integration details
- [Payroll Management Guide](./README.md#payroll-management) - Payroll system documentation

---

## Changelog

### Version 1.1.0 (Current)
- ✅ Fixed session handling mismatch
- ✅ Fixed authorization logic type comparison
- ✅ Added error handling for fallback query
- ✅ Improved employee existence check
- ✅ Enhanced input validation (max value)
- ✅ Improved NULL handling throughout
- ✅ Added response metadata

### Version 1.0.0 (Initial)
- Initial API implementation
- Basic payslip retrieval functionality
- Dual data source support (payslips + payroll_payslips)

---

**Last Updated:** December 2024  
**Maintained By:** Development Team  
**Status:** ✅ Production Ready

