# Payslip Data API Analysis

## File: `payslip-data.php`

### Overview
This API handles payslip data retrieval for employees from the HRIS system. It queries two potential data sources:
1. `payslips` table (accounting system) - uses `employee_external_no` (e.g., "EMP025")
2. `payroll_payslips` table (HRIS system) - uses `employee_id` (e.g., 25)

---

## 🔍 **Issues Identified**

### 1. **Session Handling Mismatch** ⚠️
**Location:** Lines 38-42

**Issue:**
- API starts a new session if none exists, but doesn't configure session parameters to match HRIS
- HRIS may use different session name, cookie path, or domain
- This could cause session data (employee_id) to not be accessible

**Impact:** 
- Authorization check might fail
- Session employee_id might not be available

**Recommendation:**
```php
// Before session_start(), configure to match HRIS
if (session_status() == PHP_SESSION_NONE) {
    // Match HRIS session configuration
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}
```

---

### 2. **Authorization Logic Flaw** ⚠️
**Location:** Lines 117-121

**Issue:**
```php
if (!empty($session_employee_id) && !empty($requested_employee_id) && $session_employee_id != $requested_employee_id) {
    throw new Exception('Unauthorized: You can only access your own payslip');
}
```

**Problem:**
- Compares `$session_employee_id` (string from session) with `$requested_employee_id` (string from GET/POST)
- But `$employee_id` was already converted to `intval()` on line 112
- Type mismatch: comparing string "25" with string "25" works, but if one is int and one is string, comparison might fail

**Fix:**
```php
// Convert both to integers before comparison
$session_employee_id_int = !empty($session_employee_id) ? intval($session_employee_id) : 0;
$requested_employee_id_int = !empty($requested_employee_id) ? intval($requested_employee_id) : 0;

if ($session_employee_id_int > 0 && $requested_employee_id_int > 0 && $session_employee_id_int != $requested_employee_id_int) {
    throw new Exception('Unauthorized: You can only access your own payslip');
}
```

---

### 3. **Missing Error Handling in Fallback Query** ⚠️
**Location:** Lines 277-315

**Issue:**
- If `$fallback_stmt->prepare()` fails, it logs but continues
- No check if `$fallback_stmt->execute()` succeeds
- No error handling for query execution failures

**Recommendation:**
```php
$fallback_stmt = $conn->prepare($fallback_sql);
if ($fallback_stmt) {
    $fallback_stmt->bind_param("i", $employee_id);
    if (!$fallback_stmt->execute()) {
        error_log("Payslip API: Fallback query execution failed: " . $fallback_stmt->error);
        $fallback_stmt->close();
        // Continue - don't throw, just log
    } else {
        $fallback_result = $fallback_stmt->get_result();
        // ... rest of code
    }
}
```

---

### 4. **Employee Existence Check Uses Wrong Connection** ⚠️
**Location:** Lines 318-334

**Issue:**
- Uses `$conn` (mysqli from accounting system)
- Queries `employee` table which exists in BankingDB
- This should work, but if the table doesn't exist or has different structure, it will fail silently

**Current Code:**
```php
$check_employee_sql = "SELECT employee_id, first_name, last_name FROM employee WHERE employee_id = ? LIMIT 1";
$check_stmt = $conn->prepare($check_employee_sql);
```

**Recommendation:**
- Add error handling
- Check if table exists first
- Provide more detailed logging

---

### 5. **No Input Sanitization for Employee ID** ⚠️
**Location:** Lines 92-115

**Issue:**
- Employee ID is converted to int, which is good
- But no validation for SQL injection (though intval() helps)
- No check for maximum reasonable value (e.g., employee_id < 10000)

**Recommendation:**
```php
$employee_id = intval($employee_id);
if ($employee_id <= 0 || $employee_id > 99999) {
    throw new Exception('Invalid employee ID: Must be between 1 and 99999');
}
```

---

### 6. **Potential NULL Handling Issues** ⚠️
**Location:** Multiple locations

**Issues:**
- `$row['gross_pay'] ?? 0` - Good
- But `$row['period_start']` and `$row['period_end']` might be NULL
- No validation that dates are valid before using them

**Recommendation:**
```php
'period_start' => $row['period_start'] ? date('Y-m-d', strtotime($row['period_start'])) : null,
'period_end' => $row['period_end'] ? date('Y-m-d', strtotime($row['period_end'])) : null,
```

---

### 7. **JSON Response Structure Inconsistency** ⚠️
**Location:** Lines 336-342

**Issue:**
- Returns `success: true` even when no payslips found
- Frontend expects `data` array, which is correct
- But no indication in response if employee exists or not

**Recommendation:**
Add metadata to response:
```php
echo json_encode([
    'success' => true,
    'data' => $payslips,
    'count' => count($payslips),
    'employee_id' => $employee_id,
    'employee_external_no' => $employee_external_no,
    'employee_exists' => isset($employee_check) ? !empty($employee_check) : null,
    'searched_tables' => ['payslips', 'payroll_payslips']
]);
```

---

## ✅ **What's Working Well**

1. **Dual Query Strategy**: Good fallback mechanism
2. **Error Logging**: Comprehensive logging for debugging
3. **Input Validation**: Converts to integer, validates > 0
4. **JSON Structure**: Well-structured response with breakdown
5. **Security**: Uses prepared statements
6. **CORS Headers**: Properly configured for cross-origin requests

---

## 🔧 **Recommended Fixes Priority**

### High Priority:
1. Fix authorization logic (type comparison)
2. Add error handling for fallback query execution
3. Improve session handling to match HRIS

### Medium Priority:
4. Add employee ID range validation
5. Improve NULL handling for dates
6. Add table existence checks

### Low Priority:
7. Add response metadata
8. Improve error messages

---

## 🧪 **Testing Recommendations**

1. **Test with valid employee_id that has payslips**
2. **Test with valid employee_id that has NO payslips**
3. **Test with invalid employee_id**
4. **Test with session employee_id mismatch**
5. **Test with no session (direct API call)**
6. **Test with employee_id = 0 or negative**
7. **Test with very large employee_id**

---

## 📝 **Code Quality Notes**

- **Good**: Uses prepared statements (SQL injection protection)
- **Good**: Comprehensive error logging
- **Good**: Fallback query strategy
- **Needs Improvement**: Session handling
- **Needs Improvement**: Error handling in some areas
- **Needs Improvement**: Type consistency in comparisons

---

## 🔐 **Security Considerations**

1. ✅ Uses prepared statements
2. ✅ Validates employee_id is positive integer
3. ⚠️ Authorization check has type mismatch issue
4. ⚠️ Session handling might not match HRIS
5. ✅ CORS headers configured (but `Access-Control-Allow-Origin: *` is permissive)

---

## 📊 **Performance Considerations**

1. Two queries executed (payslips, then fallback)
2. Employee existence check only runs if no payslips found (good)
3. LIMIT 10 on both queries (good)
4. Indexes should exist on:
   - `payslips.employee_external_no`
   - `payroll_payslips.employee_id`
   - `employee.employee_id`

---

## 🎯 **Summary**

The API is **mostly well-written** but has a few critical issues:
1. **Session handling** needs to match HRIS configuration
2. **Authorization logic** has type comparison issue
3. **Error handling** in fallback query needs improvement

The main issue causing "No payslips found" is likely:
- **No data exists** in either table for the employee
- **Session mismatch** preventing employee_id from being retrieved
- **Authorization check failing** due to type mismatch

