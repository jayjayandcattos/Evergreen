<?php
// Start output buffering to catch any unexpected output
ob_start();

// Suppress error display and log errors instead
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();

// Check database connection first
include("db_connect.php");

// If connection failed, handle it gracefully with JSON
if (isset($db_connection_error) || !isset($conn) || $conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . (isset($db_connection_error) ? $db_connection_error : (isset($conn) ? $conn->connect_error : 'Connection not established'))
    ]);
    exit;
}

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
    case 'get_user_points':
        getUserPoints($conn, $user_id);
        break;
    
    case 'get_missions':
        getMissions($conn, $user_id);
        break;
    
    case 'collect_mission':
        collectMission($conn, $user_id);
        break;
    
    case 'get_point_history':
        getPointHistory($conn, $user_id);
        break;
    
    case 'get_completed_missions':
        getCompletedMissions($conn, $user_id);
        break;
    
    // Add this case in your switch statement
    case 'redeem_reward':
        ob_clean(); // Clear any unexpected output
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Not logged in']);
            ob_end_flush();
            exit;
        }
        
        $reward_name = $_POST['reward_name'] ?? '';
        $points_cost = floatval($_POST['points_cost'] ?? 0);
        
        if (empty($reward_name) || $points_cost <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid reward data']);
            ob_end_flush();
            exit;
        }
        
        // Get user's current points
        $user_id = $_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT total_points FROM bank_customers WHERE customer_id = ?");
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
            ob_end_flush();
            exit;
        }
        
        $stmt->bind_param("i", $user_id);
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Query error: ' . $stmt->error]);
            $stmt->close();
            ob_end_flush();
            exit;
        }
        
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        if (!$user || $user['total_points'] < $points_cost) {
            echo json_encode(['success' => false, 'message' => 'Insufficient points']);
            ob_end_flush();
            exit;
        }
        
        // Deduct points
        $new_total = $user['total_points'] - $points_cost;
        $stmt = $conn->prepare("UPDATE bank_customers SET total_points = ? WHERE customer_id = ?");
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
            ob_end_flush();
            exit;
        }
        
        $stmt->bind_param("di", $new_total, $user_id);
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Query error: ' . $stmt->error]);
            $stmt->close();
            ob_end_flush();
            exit;
        }
        $stmt->close();
        
        // Log the redemption in history (negative points)
        $description = "Redeemed: " . $reward_name;
        $negative_points = -$points_cost;
        $stmt = $conn->prepare("INSERT INTO points_history (user_id, points, description, transaction_type) VALUES (?, ?, ?, 'redemption')");
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
            ob_end_flush();
            exit;
        }
        
        $stmt->bind_param("ids", $user_id, $negative_points, $description);
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Query error: ' . $stmt->error]);
            $stmt->close();
            ob_end_flush();
            exit;
        }
        $stmt->close();
        
        echo json_encode([
            'success' => true,
            'new_total' => $new_total,
            'points_deducted' => $points_cost,
            'message' => 'Reward redeemed successfully'
        ]);
        ob_end_flush();
    break;
    
    default:
        ob_clean(); // Clear any unexpected output
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        ob_end_flush();
        exit;
    }
} catch (Throwable $e) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
    ob_end_flush();
    exit;
}

function getUserPoints($conn, $user_id) {
    ob_clean(); // Clear any unexpected output
    header('Content-Type: application/json');
    
    $sql = "SELECT total_points FROM bank_customers WHERE customer_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        ob_end_flush();
        return;
    }
    
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Query error: ' . $stmt->error]);
        $stmt->close();
        ob_end_flush();
        return;
    }
    
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'total_points' => number_format($row['total_points'], 2, '.', '')
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
    $stmt->close();
    ob_end_flush();
}

function getMissions($conn, $user_id) {
    // Get user's current referral count (check if referrals table exists)
    $referral_count = 0;
    try {
        $referral_sql = "SELECT COUNT(*) as referral_count FROM referrals WHERE referrer_id = ?";
        $stmt = $conn->prepare($referral_sql);
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result) {
                $referral_data = $result->fetch_assoc();
                $referral_count = $referral_data ? (int)$referral_data['referral_count'] : 0;
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        // If referrals table doesn't exist, just use 0
        $referral_count = 0;
    }
    
    // Get all missions with their completion status
    $sql = "SELECT 
                m.id, 
                m.mission_text, 
                m.points_value,
                um.status,
                CASE 
                    WHEN m.id = 1 AND ? >= 1 THEN 'available'
                    WHEN m.id = 2 AND ? >= 3 THEN 'available'
                    WHEN m.id = 3 AND ? >= 5 THEN 'available'
                    WHEN m.id = 4 AND ? >= 10 THEN 'available'
                    WHEN m.id = 5 AND ? >= 15 THEN 'available'
                    WHEN m.id = 6 AND ? >= 20 THEN 'available'
                    WHEN m.id = 7 THEN 'available'
                    WHEN m.id = 8 THEN 'pending'
                    WHEN m.id = 9 AND ? >= 25 THEN 'available'
                    WHEN m.id = 10 AND ? >= 50 THEN 'available'
                    ELSE 'pending'
                END as mission_status
            FROM missions m
            LEFT JOIN user_missions um ON m.id = um.mission_id AND um.user_id = ?
            WHERE um.id IS NULL OR um.status != 'collected'
            ORDER BY m.id";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $conn->error
        ]);
        return;
    }
    
    $stmt->bind_param("iiiiiiiii", 
        $referral_count, $referral_count, $referral_count, 
        $referral_count, $referral_count, $referral_count,
        $referral_count, $referral_count, $user_id
    );
    
    if (!$stmt->execute()) {
        echo json_encode([
            'success' => false,
            'message' => 'Query error: ' . $stmt->error
        ]);
        $stmt->close();
        return;
    }
    
    $result = $stmt->get_result();
    
    $missions = [];
    while ($row = $result->fetch_assoc()) {
        $missions[] = [
            'id' => $row['id'],
            'mission_text' => $row['mission_text'],
            'points_value' => $row['points_value'],
            'status' => $row['mission_status'],
            'current_referrals' => $referral_count
        ];
    }
    
    ob_clean(); // Clear any unexpected output
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'missions' => $missions,
        'total_referrals' => $referral_count
    ]);
    $stmt->close();
    ob_end_flush();
}

function collectMission($conn, $user_id) {
    $mission_id = $_POST['mission_id'] ?? 0;
    
    if (!$mission_id) {
        echo json_encode(['success' => false, 'message' => 'Mission ID required']);
        return;
    }
    
    $conn->begin_transaction();
    
    try {
        // Get user's referral count (check if referrals table exists)
        $referral_count = 0;
        try {
            $sql = "SELECT COUNT(*) as referral_count FROM referrals WHERE referrer_id = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result) {
                    $referral_data = $result->fetch_assoc();
                    $referral_count = $referral_data ? (int)$referral_data['referral_count'] : 0;
                }
                $stmt->close();
            }
        } catch (Exception $e) {
            // If referrals table doesn't exist, just use 0
            $referral_count = 0;
        }
        
        // Check if mission requirements are met
        $requirements = [
            1 => 1,   // 1 referral
            2 => 3,   // 3 referrals
            3 => 5,   // 5 referrals
            4 => 10,  // 10 referrals
            5 => 15,  // 15 referrals
            6 => 20,  // 20 referrals
            7 => 0,   // Social media (always available)
            8 => 0,   // Weekly challenge (manual check)
            9 => 25,  // 25 referrals
            10 => 50  // 50 referrals
        ];
        
        if (isset($requirements[$mission_id]) && $referral_count < $requirements[$mission_id]) {
            throw new Exception('Mission requirements not met. You need ' . $requirements[$mission_id] . ' referrals.');
        }
        
        // Get mission details
        $sql = "SELECT points_value, mission_text FROM missions WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }
        
        $stmt->bind_param("i", $mission_id);
        if (!$stmt->execute()) {
            throw new Exception('Query error: ' . $stmt->error);
        }
        
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            throw new Exception('Mission not found');
        }
        
        $mission = $result->fetch_assoc();
        $points = $mission['points_value'];
        $mission_text = $mission['mission_text'];
        $stmt->close();
        
        // Check if already collected
        $sql = "SELECT id FROM user_missions WHERE user_id = ? AND mission_id = ? AND status = 'collected'";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }
        
        $stmt->bind_param("ii", $user_id, $mission_id);
        if (!$stmt->execute()) {
            throw new Exception('Query error: ' . $stmt->error);
        }
        
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $stmt->close();
            throw new Exception('Mission already collected');
        }
        $stmt->close();
        
        // Add or update mission record
        $sql = "INSERT INTO user_missions (user_id, mission_id, points_earned, status) 
                VALUES (?, ?, ?, 'collected')
                ON DUPLICATE KEY UPDATE status = 'collected', completed_at = NOW()";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }
        
        $stmt->bind_param("iid", $user_id, $mission_id, $points);
        if (!$stmt->execute()) {
            throw new Exception('Query error: ' . $stmt->error);
        }
        $stmt->close();
        
        // Update user's total points
        $sql = "UPDATE bank_customers SET total_points = total_points + ? WHERE customer_id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }
        
        $stmt->bind_param("di", $points, $user_id);
        if (!$stmt->execute()) {
            throw new Exception('Query error: ' . $stmt->error);
        }
        $stmt->close();

        // ✅ ADD THIS: Log the mission collection in points_history
        $sql = "INSERT INTO points_history (user_id, points, description, transaction_type) 
                VALUES (?, ?, ?, 'mission')";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }
        
        $stmt->bind_param("ids", $user_id, $points, $mission_text);
        if (!$stmt->execute()) {
            throw new Exception('Query error: ' . $stmt->error);
        }
        $stmt->close();
        
        // Get updated total
        $sql = "SELECT total_points FROM bank_customers WHERE customer_id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }
        
        $stmt->bind_param("i", $user_id);
        if (!$stmt->execute()) {
            throw new Exception('Query error: ' . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $total_points = $user ? $user['total_points'] : 0;
        $stmt->close();
        
        $conn->commit();
        
        ob_clean(); // Clear any unexpected output
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Mission completed! 🎉',
            'points_earned' => number_format($points, 2, '.', ''),
            'total_points' => number_format($total_points, 2, '.', ''),
            'mission_text' => $mission_text
        ]);
        ob_end_flush();
        
    } catch (Exception $e) {
        $conn->rollback();
        ob_clean(); // Clear any unexpected output
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        ob_end_flush();
    }
}

function getPointHistory($conn, $user_id) {
    // Query the points_history table to get ALL transactions (missions + redemptions)
    $sql = "SELECT 
                points, 
                description, 
                transaction_type,
                created_at 
            FROM points_history 
            WHERE user_id = ?
            ORDER BY created_at DESC";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $conn->error
        ]);
        return;
    }
    
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        echo json_encode([
            'success' => false,
            'message' => 'Query error: ' . $stmt->error
        ]);
        $stmt->close();
        return;
    }
    
    $result = $stmt->get_result();
    
    $history = [];
    while ($row = $result->fetch_assoc()) {
        $history[] = [
            'points' => number_format($row['points'], 2, '.', ''),
            'description' => $row['description'],
            'transaction_type' => $row['transaction_type'],
            'timestamp' => date('F j, Y g:i A', strtotime($row['created_at']))
        ];
    }
    
    ob_clean(); // Clear any unexpected output
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'history' => $history
    ]);
    $stmt->close();
    ob_end_flush();
}

function getCompletedMissions($conn, $user_id) {
    $sql = "SELECT um.points_earned, m.mission_text, um.completed_at 
            FROM user_missions um
            JOIN missions m ON um.mission_id = m.id
            WHERE um.user_id = ? AND um.status = 'collected'
            ORDER BY um.completed_at DESC";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $conn->error
        ]);
        return;
    }
    
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        echo json_encode([
            'success' => false,
            'message' => 'Query error: ' . $stmt->error
        ]);
        $stmt->close();
        return;
    }
    
    $result = $stmt->get_result();
    
    $completed = [];
    while ($row = $result->fetch_assoc()) {
        $completed[] = [
            'points' => number_format($row['points_earned'], 2, '.', ''),
            'description' => $row['mission_text'],
            'timestamp' => date('F j, Y g:i A', strtotime($row['completed_at']))
        ];
    }
    
    ob_clean(); // Clear any unexpected output
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'completed' => $completed
    ]);
    $stmt->close();
    ob_end_flush();
}

?>