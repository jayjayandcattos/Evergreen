<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'db_connect.php';

if (!isset($conn) || $conn->connect_error) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'get_provinces':
        $result = $conn->query("SELECT province_id as id, province_name as name FROM provinces ORDER BY province_name ASC");
        $provinces = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $provinces[] = [
                    'id' => (int)$row['id'],
                    'name' => $row['name']
                ];
            }
        }
        echo json_encode($provinces);
        break;

    case 'get_cities':
        $province_id = isset($_GET['province_id']) ? (int)$_GET['province_id'] : 0;
        
        if ($province_id > 0) {
            $stmt = $conn->prepare("SELECT city_id as id, city_name as name FROM cities WHERE province_id = ? ORDER BY city_name ASC");
            $stmt->bind_param("i", $province_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $cities = [];
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $cities[] = [
                        'id' => (int)$row['id'],
                        'name' => $row['name']
                    ];
                }
            }
            $stmt->close();
            echo json_encode($cities);
        } else {
            echo json_encode([]);
        }
        break;

    case 'get_barangays':
        $city_id = isset($_GET['city_id']) ? (int)$_GET['city_id'] : 0;
        
        if ($city_id > 0) {
            $stmt = $conn->prepare("SELECT barangay_id as id, barangay_name as name, zip_code FROM barangays WHERE city_id = ? ORDER BY barangay_name ASC");
            $stmt->bind_param("i", $city_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $barangays = [];
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $barangays[] = [
                        'id' => (int)$row['id'],
                        'name' => $row['name'],
                        'zip_code' => $row['zip_code'] ?? ''
                    ];
                }
            }
            $stmt->close();
            echo json_encode($barangays);
        } else {
            echo json_encode([]);
        }
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}

$conn->close();
?>

