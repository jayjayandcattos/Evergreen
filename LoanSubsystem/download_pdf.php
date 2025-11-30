<?php
// download_pdf.php - Handles PDF downloads with proper headers
session_start();

// Check authentication
if (!isset($_SESSION['user_email'])) {
    http_response_code(403);
    die('Access denied');
}

// Get filename from query parameter
$filename = $_GET['file'] ?? '';

if (empty($filename)) {
    http_response_code(400);
    die('No file specified');
}

// ✅ Security: Prevent directory traversal attacks
$filename = basename($filename);

// ✅ Ensure file has .pdf extension
if (!preg_match('/\.pdf$/i', $filename)) {
    http_response_code(400);
    die('Invalid file type');
}

// ✅ Build full file path
$filepath = 'uploads/' . $filename;

// ✅ Check if file exists
if (!file_exists($filepath)) {
    http_response_code(404);
    die('File not found: ' . htmlspecialchars($filename));
}

// ✅ Verify user owns this loan (security check)
$loan_id = null;
if (preg_match('/loan_\w+_(\d+)_\d+\.pdf/', $filename, $matches)) {
    $loan_id = intval($matches[1]);
}

if ($loan_id) {
    $host = "localhost";
    $user = "root";
    $pass = "";
    $db = "BankingDB";
    
    $conn = new mysqli($host, $user, $pass, $db);
    if (!$conn->connect_error) {
        $stmt = $conn->prepare("SELECT email FROM loan_applications WHERE id = ?");
        $stmt->bind_param("i", $loan_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            $conn->close();
            http_response_code(404);
            die('Loan not found');
        }
        
        $row = $result->fetch_assoc();
        if ($row['email'] !== $_SESSION['user_email']) {
            $stmt->close();
            $conn->close();
            http_response_code(403);
            die('Access denied');
        }
        
        $stmt->close();
        $conn->close();
    }
}

// ✅ Clear any output buffers
if (ob_get_level()) {
    ob_end_clean();
}

// ✅ Set proper headers for PDF download
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// ✅ Output the file
readfile($filepath);
exit;
?>