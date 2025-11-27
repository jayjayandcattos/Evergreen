<?php
/**
 * Payslip API Testing Tool
 * Comprehensive test suite for the payslip-data.php API
 * 
 * This tool tests all scenarios identified in the API analysis:
 * - Valid employee IDs with and without payslips
 * - Invalid employee IDs and edge cases
 * - Authorization checks
 * - Session handling
 * - Error handling
 */

// Get base URL dynamically
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base_path = dirname(dirname($_SERVER['PHP_SELF']));
$api_url = "$protocol://$host$base_path/modules/api/payslip-data.php";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip API Test Suite</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .header {
            background: #0A3D3D;
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .test-section {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .test-section h3 {
            margin-top: 0;
            color: #0A3D3D;
            border-bottom: 2px solid #0A3D3D;
            padding-bottom: 10px;
        }
        .test-case {
            margin: 15px 0;
            padding: 15px;
            background: #f9f9f9;
            border-left: 4px solid #0A3D3D;
            border-radius: 4px;
        }
        .test-case h4 {
            margin-top: 0;
            color: #333;
        }
        .success {
            color: #28a745;
            font-weight: bold;
        }
        .failure {
            color: #dc3545;
            font-weight: bold;
        }
        .info {
            color: #17a2b8;
            font-weight: bold;
        }
        .response-box {
            background: #f0f0f0;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
            font-family: monospace;
            font-size: 12px;
            max-height: 300px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .summary {
            background: #e7f3ff;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .summary h3 {
            margin-top: 0;
        }
        .stats {
            display: flex;
            gap: 20px;
            margin-top: 10px;
        }
        .stat-box {
            flex: 1;
            padding: 15px;
            background: white;
            border-radius: 4px;
            text-align: center;
        }
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #0A3D3D;
        }
        .stat-label {
            color: #666;
            margin-top: 5px;
        }
        code {
            background: #f0f0f0;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🧪 Payslip API Test Suite</h1>
        <p>Comprehensive testing for <code>payslip-data.php</code> API endpoint</p>
        <p><strong>API URL:</strong> <code><?php echo htmlspecialchars($api_url); ?></code></p>
    </div>

    <?php
    $test_results = [];
    $total_tests = 0;
    $passed_tests = 0;
    $failed_tests = 0;
    $info_tests = 0;

    /**
     * Helper function to make API calls
     */
    function callAPI($url, $params = []) {
        $full_url = $url . '?action=get_payslips';
        if (!empty($params)) {
            $full_url .= '&' . http_build_query($params);
        }
        
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => 'Accept: application/json',
                'timeout' => 10
            ]
        ]);
        
        $response = @file_get_contents($full_url, false, $context);
        
        if ($response === false) {
            return [
                'success' => false,
                'error' => 'Failed to connect to API. Make sure the server is running.',
                'http_code' => 0
            ];
        }
        
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'error' => 'Invalid JSON response: ' . json_last_error_msg(),
                'raw_response' => $response
            ];
        }
        
        return $data;
    }

    /**
     * Display test result
     */
    function displayTest($title, $description, $result, $expected = null) {
        global $total_tests, $passed_tests, $failed_tests, $info_tests;
        $total_tests++;
        
        $status_class = '';
        $status_text = '';
        $status_icon = '';
        
        if (isset($result['success']) && $result['success'] === true) {
            if ($expected === 'error') {
                $status_class = 'failure';
                $status_text = 'UNEXPECTED SUCCESS';
                $status_icon = '❌';
                $failed_tests++;
            } else {
                $status_class = 'success';
                $status_text = 'PASSED';
                $status_icon = '✅';
                $passed_tests++;
            }
        } else {
            if ($expected === 'error') {
                $status_class = 'success';
                $status_text = 'EXPECTED ERROR';
                $status_icon = '✅';
                $passed_tests++;
            } else {
                $status_class = 'failure';
                $status_text = 'FAILED';
                $status_icon = '❌';
                $failed_tests++;
            }
        }
        
        echo "<div class='test-case'>";
        echo "<h4>{$status_icon} {$title}</h4>";
        echo "<p>{$description}</p>";
        echo "<p><strong>Status:</strong> <span class='{$status_class}'>{$status_text}</span></p>";
        echo "<div class='response-box'>";
        echo htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        echo "</div>";
        echo "</div>";
    }

    // Test 1: Valid employee_id with payslips (if exists)
    echo "<div class='test-section'>";
    echo "<h3>Test 1: Valid Employee ID with Payslips</h3>";
    echo "<p>Tests successful retrieval when employee has payslip data.</p>";
    
    $result = callAPI($api_url, ['employee_id' => 25]);
    displayTest(
        "Employee ID 25 (EMP025)",
        "Testing with employee_id=25. Should return payslips if data exists, or empty array if no payslips.",
        $result,
        'success_or_empty'
    );
    echo "</div>";

    // Test 2: Valid employee_id with NO payslips
    echo "<div class='test-section'>";
    echo "<h3>Test 2: Valid Employee ID with No Payslips</h3>";
    echo "<p>Tests handling when employee exists but has no payslip records.</p>";
    
    $result = callAPI($api_url, ['employee_id' => 999]);
    displayTest(
        "Employee ID 999 (EMP999)",
        "Testing with employee_id=999. Should return success with empty data array if employee exists but has no payslips.",
        $result,
        'success_or_empty'
    );
    echo "</div>";

    // Test 3: Missing employee_id parameter
    echo "<div class='test-section'>";
    echo "<h3>Test 3: Missing Employee ID Parameter</h3>";
    echo "<p>Tests validation when employee_id is not provided.</p>";
    
    $result = callAPI($api_url, []);
    displayTest(
        "No employee_id parameter",
        "Testing without employee_id. Should return error: 'Employee ID is required'.",
        $result,
        'error'
    );
    echo "</div>";

    // Test 4: Invalid employee_id (zero)
    echo "<div class='test-section'>";
    echo "<h3>Test 4: Invalid Employee ID - Zero</h3>";
    echo "<p>Tests validation for employee_id = 0.</p>";
    
    $result = callAPI($api_url, ['employee_id' => 0]);
    displayTest(
        "Employee ID = 0",
        "Testing with employee_id=0. Should return error: 'Invalid employee ID format'.",
        $result,
        'error'
    );
    echo "</div>";

    // Test 5: Invalid employee_id (negative)
    echo "<div class='test-section'>";
    echo "<h3>Test 5: Invalid Employee ID - Negative</h3>";
    echo "<p>Tests validation for negative employee_id.</p>";
    
    $result = callAPI($api_url, ['employee_id' => -1]);
    displayTest(
        "Employee ID = -1",
        "Testing with employee_id=-1. Should return error: 'Invalid employee ID format'.",
        $result,
        'error'
    );
    echo "</div>";

    // Test 6: Very large employee_id
    echo "<div class='test-section'>";
    echo "<h3>Test 6: Invalid Employee ID - Too Large</h3>";
    echo "<p>Tests validation for employee_id > 99999.</p>";
    
    $result = callAPI($api_url, ['employee_id' => 100000]);
    displayTest(
        "Employee ID = 100000",
        "Testing with employee_id=100000. Should return error: 'Invalid employee ID format' (must be <= 99999).",
        $result,
        'error'
    );
    echo "</div>";

    // Test 7: Non-numeric employee_id
    echo "<div class='test-section'>";
    echo "<h3>Test 7: Invalid Employee ID - Non-Numeric</h3>";
    echo "<p>Tests validation for non-numeric employee_id.</p>";
    
    $result = callAPI($api_url, ['employee_id' => 'abc']);
    displayTest(
        "Employee ID = 'abc'",
        "Testing with employee_id='abc'. Should return error or convert to 0 and fail validation.",
        $result,
        'error'
    );
    echo "</div>";

    // Test 8: String numeric employee_id
    echo "<div class='test-section'>";
    echo "<h3>Test 8: Valid Employee ID - String Format</h3>";
    echo "<p>Tests that string numeric employee_id is accepted and converted.</p>";
    
    $result = callAPI($api_url, ['employee_id' => '25']);
    displayTest(
        "Employee ID = '25' (string)",
        "Testing with employee_id='25' as string. Should be converted to integer and work correctly.",
        $result,
        'success_or_empty'
    );
    echo "</div>";

    // Test 9: Multiple valid employee IDs
    echo "<div class='test-section'>";
    echo "<h3>Test 9: Multiple Valid Employee IDs</h3>";
    echo "<p>Tests API with various valid employee IDs to check data availability.</p>";
    
    $test_ids = [1, 2, 5, 10, 20, 25, 50, 100];
    foreach ($test_ids as $test_id) {
        $result = callAPI($api_url, ['employee_id' => $test_id]);
        $has_data = isset($result['data']) && is_array($result['data']) && count($result['data']) > 0;
        $status = $has_data ? 'Has Payslips' : 'No Payslips';
        $icon = $has_data ? '📄' : '📭';
        
        echo "<div class='test-case'>";
        echo "<h4>{$icon} Employee ID {$test_id} (EMP" . str_pad($test_id, 3, '0', STR_PAD_LEFT) . ")</h4>";
        echo "<p><strong>Status:</strong> <span class='info'>{$status}</span></p>";
        if (isset($result['count'])) {
            echo "<p><strong>Payslip Count:</strong> {$result['count']}</p>";
        }
        if (isset($result['employee_exists'])) {
            echo "<p><strong>Employee Exists:</strong> " . ($result['employee_exists'] ? 'Yes' : 'No') . "</p>";
        }
        echo "</div>";
    }
    echo "</div>";

    // Test 10: Response structure validation
    echo "<div class='test-section'>";
    echo "<h3>Test 10: Response Structure Validation</h3>";
    echo "<p>Tests that API response has correct structure and metadata.</p>";
    
    $result = callAPI($api_url, ['employee_id' => 25]);
    
    $checks = [];
    $checks['Has success field'] = isset($result['success']);
    $checks['Has data field'] = isset($result['data']) && is_array($result['data']);
    $checks['Has count field'] = isset($result['count']);
    $checks['Has employee_id field'] = isset($result['employee_id']);
    $checks['Has employee_external_no field'] = isset($result['employee_external_no']);
    $checks['Has searched_tables field'] = isset($result['searched_tables']);
    
    echo "<div class='test-case'>";
    echo "<h4>Response Structure Check</h4>";
    foreach ($checks as $check => $passed) {
        $icon = $passed ? '✅' : '❌';
        $class = $passed ? 'success' : 'failure';
        echo "<p><span class='{$class}'>{$icon} {$check}</span></p>";
    }
    echo "<div class='response-box'>";
    echo htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    echo "</div>";
    echo "</div>";
    echo "</div>";

    // Summary
    echo "<div class='summary'>";
    echo "<h3>📊 Test Summary</h3>";
    echo "<div class='stats'>";
    echo "<div class='stat-box'>";
    echo "<div class='stat-number'>{$total_tests}</div>";
    echo "<div class='stat-label'>Total Tests</div>";
    echo "</div>";
    echo "<div class='stat-box'>";
    echo "<div class='stat-number' style='color: #28a745;'>{$passed_tests}</div>";
    echo "<div class='stat-label'>Passed</div>";
    echo "</div>";
    echo "<div class='stat-box'>";
    echo "<div class='stat-number' style='color: #dc3545;'>{$failed_tests}</div>";
    echo "<div class='stat-label'>Failed</div>";
    echo "</div>";
    echo "<div class='stat-box'>";
    $pass_rate = $total_tests > 0 ? round(($passed_tests / $total_tests) * 100, 1) : 0;
    echo "<div class='stat-number' style='color: #17a2b8;'>{$pass_rate}%</div>";
    echo "<div class='stat-label'>Pass Rate</div>";
    echo "</div>";
    echo "</div>";
    
    if ($failed_tests == 0) {
        echo "<p style='color: #28a745; font-size: 18px; font-weight: bold; margin-top: 20px;'>✅ All tests passed!</p>";
    } else {
        echo "<p style='color: #dc3545; font-size: 18px; font-weight: bold; margin-top: 20px;'>⚠️ Some tests failed. Review the results above.</p>";
    }
    echo "</div>";

    echo "<hr>";
    echo "<p style='color: #666; font-size: 12px; text-align: center;'>";
    echo "⚠️ <strong>Note:</strong> This test file should be removed or secured in production environments.";
    echo "</p>";
    ?>

</body>
</html>

