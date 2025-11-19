<?php
session_start();
include("db_connect.php");

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

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
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Not logged in']);
            exit;
        }
        
        $reward_name = $_POST['reward_name'] ?? '';
        $points_cost = floatval($_POST['points_cost'] ?? 0);
        
        if (empty($reward_name) || $points_cost <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid reward data']);
            exit;
        }
        
        // Get user's current points
        $user_id = $_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT total_points FROM bank_customers WHERE customer_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if ($user['total_points'] < $points_cost) {
            echo json_encode(['success' => false, 'message' => 'Insufficient points']);
            exit;
        }
        
        // Deduct points
        $new_total = $user['total_points'] - $points_cost;
        $stmt = $conn->prepare("UPDATE bank_customers SET total_points = ? WHERE customer_id = ?");
        $stmt->bind_param("di", $new_total, $user_id);
        $stmt->execute();
        
        // Log the redemption in history (negative points)
        $description = "Redeemed: " . $reward_name;
        $negative_points = -$points_cost;
        $stmt = $conn->prepare("INSERT INTO points_history (user_id, points, description, transaction_type) VALUES (?, ?, ?, 'redemption')");
        $stmt->bind_param("ids", $user_id, $negative_points, $description);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'new_total' => $new_total,
            'points_deducted' => $points_cost,
            'message' => 'Reward redeemed successfully'
        ]);
    break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function getUserPoints($conn, $user_id) {
    $sql = "SELECT total_points FROM bank_customers WHERE customer_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
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
}

function getMissions($conn, $user_id) {
    // Get user's current referral count
    $referral_sql = "SELECT COUNT(*) as referral_count FROM referrals WHERE referrer_id = ?";
    $stmt = $conn->prepare($referral_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $referral_data = $result->fetch_assoc();
    $referral_count = $referral_data['referral_count'];
    $stmt->close();
    
    // Get all missions with their completion status
    $sql = "SELECT 
                m.customer_id, 
                m.mission_text, 
                m.points_value,
                um.status,
                CASE 
                    WHEN m.customer_id = 1 AND ? >= 1 THEN 'available'
                    WHEN m.customer_id = 2 AND ? >= 3 THEN 'available'
                    WHEN m.customer_id= 3 AND ? >= 5 THEN 'available'
                    WHEN m.customer_id = 4 AND ? >= 10 THEN 'available'
                    WHEN m.customer_id = 5 AND ? >= 15 THEN 'available'
                    WHEN m.customer_id = 6 AND ? >= 20 THEN 'available'
                    WHEN m.customer_id = 7 THEN 'available'
                    WHEN m.customer_id = 8 THEN 'pending'
                    WHEN m.customer_id = 9 AND ? >= 25 THEN 'available'
                    WHEN m.customer_id = 10 AND ? >= 50 THEN 'available'
                    ELSE 'pending'
                END as mission_status
            FROM missions m
            LEFT JOIN user_missions um ON m.customer_id = um.mission_id AND um.user_id = ?
            WHERE um.customer_id IS NULL OR um.status != 'collected'
            ORDER BY m.customer_id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiiiiiiii", 
        $referral_count, $referral_count, $referral_count, 
        $referral_count, $referral_count, $referral_count,
        $referral_count, $referral_count, $user_id
    );
    $stmt->execute();
    $result = $stmt->get_result();
    
    $missions = [];
    while ($row = $result->fetch_assoc()) {
        $missions[] = [
            'customer_id' => $row['customer_id'],
            'mission_text' => $row['mission_text'],
            'points_value' => $row['points_value'],
            'status' => $row['mission_status'],
            'current_referrals' => $referral_count
        ];
    }
    
    echo json_encode([
        'success' => true,
        'missions' => $missions,
        'total_referrals' => $referral_count
    ]);
    $stmt->close();
}

function collectMission($conn, $user_id) {
    $mission_id = $_POST['mission_id'] ?? 0;
    
    if (!$mission_id) {
        echo json_encode(['success' => false, 'message' => 'Mission ID required']);
        return;
    }
    
    $conn->begin_transaction();
    
    try {
        // Get user's referral count
        $sql = "SELECT COUNT(*) as referral_count FROM referrals WHERE referrer_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $referral_data = $result->fetch_assoc();
        $referral_count = $referral_data['referral_count'];
        $stmt->close();
        
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
        $sql = "SELECT points_value, mission_text FROM missions WHERE customer_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $mission_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('Mission not found');
        }
        
        $mission = $result->fetch_assoc();
        $points = $mission['points_value'];
        $mission_text = $mission['mission_text'];
        $stmt->close();
        
        // Check if already collected
        $sql = "SELECT customer_id FROM user_missions WHERE user_id = ? AND mission_id = ? AND status = 'collected'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $mission_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            throw new Exception('Mission already collected');
        }
        $stmt->close();
        
        // Add or update mission record
        $sql = "INSERT INTO user_missions (user_id, mission_id, points_earned, status) 
                VALUES (?, ?, ?, 'collected')
                ON DUPLICATE KEY UPDATE status = 'collected', completed_at = NOW()";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iid", $user_id, $mission_id, $points);
        $stmt->execute();
        $stmt->close();
        
        // Update user's total points
        $sql = "UPDATE bank_customers SET total_points = total_points + ? WHERE customer_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("di", $points, $user_id);
        $stmt->execute();
        $stmt->close();

        // ✅ ADD THIS: Log the mission collection in points_history
        $sql = "INSERT INTO points_history (user_id, points, description, transaction_type) 
                VALUES (?, ?, ?, 'mission')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ids", $user_id, $points, $mission_text);
        $stmt->execute();
        $stmt->close();
        
        // Get updated total
        $sql = "SELECT total_points FROM bank_customers WHERE customer_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $total_points = $user['total_points'];
        $stmt->close();
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Mission completed! 🎉',
            'points_earned' => number_format($points, 2, '.', ''),
            'total_points' => number_format($total_points, 2, '.', ''),
            'mission_text' => $mission_text
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
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
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
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
    
    echo json_encode([
        'success' => true,
        'history' => $history
    ]);
    $stmt->close();
}

function getCompletedMissions($conn, $user_id) {
    $sql = "SELECT um.points_earned, m.mission_text, um.completed_at 
            FROM user_missions um
            JOIN missions m ON um.mission_id = m.customer_id
            WHERE um.user_id = ?
            ORDER BY um.completed_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $completed = [];
    while ($row = $result->fetch_assoc()) {
        $completed[] = [
            'points' => number_format($row['points_earned'], 2, '.', ''),
            'description' => $row['mission_text'],
            'timestamp' => date('F j, Y g:i A', strtotime($row['completed_at']))
        ];
    }
    
    echo json_encode([
        'success' => true,
        'completed' => $completed
    ]);
    $stmt->close();
}

?>