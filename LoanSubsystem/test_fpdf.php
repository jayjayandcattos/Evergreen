<?php
// test_fpdf.php

// Disable any error output to browser
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/test_fpdf_errors.log');

// CRITICAL: Start output buffering BEFORE anything else
ob_start();

// Immediately clean any accidental output (e.g., BOM, spaces, newlines)
if (ob_get_level()) {
    ob_clean();
}

try {
    // Ensure no extra output leaks
    header('Content-Type: application/json; charset=utf-8');

    // Check FPDF exists
    if (!file_exists('fpdf/fpdf.php')) {
        throw new Exception('FPDF library not found in fpdf/fpdf.php');
    }

    require_once('fpdf/fpdf.php');

    // Create uploads dir if missing
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            throw new Exception('Failed to create uploads/ directory');
        }
    }

    if (!is_writable($uploadDir)) {
        throw new Exception('uploads/ directory is not writable');
    }

    // Create simple PDF
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(40, 10, 'Hello, this is a test PDF!');

    // Generate filename
    $filename = 'test_' . time() . '.pdf';
    $fullPath = $uploadDir . $filename;

    // Save to file
    $pdf->Output('F', $fullPath);

    // Verify file exists and is not empty
    if (!file_exists($fullPath) || filesize($fullPath) === 0) {
        throw new Exception('PDF file was not created or is empty');
    }

    // Discard any remaining output buffer
    if (ob_get_level()) {
        ob_end_clean();
    }

    // Send clean JSON response
    echo json_encode([
        'success' => true,
        'filename' => $filename,
        'full_path' => $fullPath,
        'message' => 'Test PDF generated successfully'
    ]);

} catch (Exception $e) {
    // Discard all output
    if (ob_get_level()) {
        ob_clean();
    }

    // Send error as JSON
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

exit;
?>