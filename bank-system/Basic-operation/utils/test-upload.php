<?php
/**
 * Test Upload Functionality
 * This page tests if the uploads directory is writable
 */

$uploadDir = '../uploads/id_images/';

echo "<h1>Upload Directory Test</h1>";

// Check if directory exists
if (is_dir($uploadDir)) {
    echo "<p style='color: green;'>✅ Directory exists: $uploadDir</p>";
} else {
    echo "<p style='color: red;'>❌ Directory does NOT exist: $uploadDir</p>";
    echo "<p>Attempting to create...</p>";
    if (mkdir($uploadDir, 0777, true)) {
        echo "<p style='color: green;'>✅ Directory created successfully</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to create directory</p>";
    }
}

// Check if directory is writable
if (is_writable($uploadDir)) {
    echo "<p style='color: green;'>✅ Directory is writable</p>";
} else {
    echo "<p style='color: red;'>❌ Directory is NOT writable</p>";
    echo "<p>Try running: chmod 777 $uploadDir</p>";
}

// Test file creation
$testFile = $uploadDir . 'test_' . time() . '.txt';
if (file_put_contents($testFile, 'Test content')) {
    echo "<p style='color: green;'>✅ Test file created successfully: $testFile</p>";
    
    // Clean up test file
    if (unlink($testFile)) {
        echo "<p style='color: green;'>✅ Test file deleted successfully</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Failed to create test file</p>";
}

echo "<hr>";
echo "<h2>Current Directory Permissions</h2>";
echo "<pre>";
echo "Upload directory: $uploadDir\n";
echo "Absolute path: " . realpath($uploadDir) . "\n";
echo "Permissions: " . substr(sprintf('%o', fileperms($uploadDir)), -4) . "\n";
echo "</pre>";

echo "<hr>";
echo "<h2>PHP Upload Settings</h2>";
echo "<pre>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "\n";
echo "file_uploads: " . (ini_get('file_uploads') ? 'Enabled' : 'Disabled') . "\n";
echo "</pre>";
?>
