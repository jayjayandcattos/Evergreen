<?php
// Test the actual withdrawal API endpoint
$url = 'http://localhost/SIABASICOPS/bank-system/Basic-operation/api/employee/process-withdrawal.php';

$data = [
    'account_number' => 'SA-6837-2025',
    'amount' => 100
];

$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($data)
    ]
];

$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);

echo "<h2>Raw Response from process-withdrawal.php:</h2>";
echo "<pre>";
echo htmlspecialchars($result);
echo "</pre>";

echo "<h2>HTTP Response Headers:</h2>";
echo "<pre>";
print_r($http_response_header);
echo "</pre>";

echo "<h2>Decoded JSON:</h2>";
$decoded = json_decode($result, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "<p style='color: red;'>JSON Error: " . json_last_error_msg() . "</p>";
    echo "<p>First 500 characters of response:</p>";
    echo "<pre>" . htmlspecialchars(substr($result, 0, 500)) . "</pre>";
} else {
    echo "<pre>";
    print_r($decoded);
    echo "</pre>";
}
?>
