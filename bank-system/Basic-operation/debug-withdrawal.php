<?php
// Capture all output from the withdrawal API
ob_start();

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/SIABASICOPS/bank-system/Basic-operation/api/employee/process-withdrawal.php');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'account_number' => 'SA-6837-2025',
    'amount' => 100
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

ob_end_clean();

echo "<h2>Withdrawal API Test</h2>";
echo "<h3>HTTP Status Code: $httpCode</h3>";
echo "<h3>Raw Response (first 1000 chars):</h3>";
echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ccc; white-space: pre-wrap;'>";
echo htmlspecialchars(substr($response, 0, 1000));
echo "</pre>";

echo "<h3>Full Response Length: " . strlen($response) . " characters</h3>";

echo "<h3>Character Analysis (first 300 chars):</h3>";
echo "<pre style='background: #fff3cd; padding: 10px; border: 1px solid #856404;'>";
for ($i = 0; $i < min(300, strlen($response)); $i++) {
    $char = $response[$i];
    $ord = ord($char);
    if ($ord < 32 || $ord > 126) {
        echo "[$i]: \\x" . dechex($ord) . " ";
    } else {
        echo "[$i]: '$char' ";
    }
    if (($i + 1) % 10 == 0) echo "\n";
}
echo "</pre>";

echo "<h3>JSON Decode Attempt:</h3>";
$decoded = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "<p style='color: red;'>JSON Error: " . json_last_error_msg() . "</p>";
    echo "<p>Error at position: " . json_last_error() . "</p>";
} else {
    echo "<pre>";
    print_r($decoded);
    echo "</pre>";
}
?>
