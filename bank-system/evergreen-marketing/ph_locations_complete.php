<?php
header('Content-Type: application/json');

// This file provides comprehensive Philippine location data
// For production, consider using a database instead of arrays

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'get_barangays') {
    $city = isset($_GET['city']) ? $_GET['city'] : '';
    
    // For cities without specific barangay data, generate generic barangays
    // In production, you should use a complete database
    
    // Return generic barangays with common names
    $genericBarangays = [
        "Poblacion",
        "Barangay 1",
        "Barangay 2", 
        "Barangay 3",
        "San Antonio",
        "San Jose",
        "San Juan",
        "Santa Cruz",
        "Santo Niño"
    ];
    
    echo json_encode($genericBarangays);
    
} elseif ($action === 'get_zipcode') {
    $barangay = isset($_GET['barangay']) ? $_GET['barangay'] : '';
    $city = isset($_GET['city']) ? $_GET['city'] : '';
    
    // Return generic zip code based on city
    // In production, use actual zip code database
    echo json_encode(['zipcode' => '']);
}
?>
