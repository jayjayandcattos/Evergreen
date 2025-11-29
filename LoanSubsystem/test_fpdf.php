<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

try {
    ob_clean();
    
    if (!file_exists('fpdf/fpdf.php')) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'FPDF not found']);
        exit;
    }
    
    require_once('fpdf/fpdf.php');
    
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(40, 10, 'Test PDF');
    
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $filename = 'test_' . time() . '.pdf';
    $fullPath = $uploadDir . $filename;
    
    ob_clean();
    $pdf->Output('F', $fullPath);
    
    if (file_exists($fullPath)) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'PDF created: ' . $fullPath]);
    } else {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'File not created']);
    }
    
} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

ob_end_flush();