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
    case 'get_referral_code':
        getReferralCode($conn, $user_id);
        break;
    
    case 'get_referral_stats':
        getReferralStats($conn, $user_id);
        break;
    
    case 'apply_referral':
        applyReferral($conn, $user_id);
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function getReferralCode($conn, $user_id) {
    $sql = "SELECT referral_code FROM bank_users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'referral_code' => $row['referral_code']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
    $stmt->close();
}

function getReferralStats($conn, $user_id) {
    // Get total referrals count
    $sql = "SELECT COUNT(*) as total FROM referrals WHERE referrer_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $total_referrals = $row['total'];
    $stmt->close();
    
    // Get total points from referrals
    $sql = "SELECT SUM(points_earned) as total_points FROM referrals WHERE referrer_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $referral_points = $row['total_points'] ?? 0;
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'total_referrals' => $total_referrals,
        'referral_points' => number_format($referral_points, 2, '.', '')
    ]);
}

function applyReferral($conn, $user_id) {
    $friend_code = strtoupper(trim($_POST['friend_code'] ?? ''));
    
    if (empty($friend_code)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a referral code']);
        return;
    }
    
    $conn->begin_transaction();
    
    try {
        // Check if user is trying to use their own code
        $sql = "SELECT referral_code FROM bank_users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        if ($user['referral_code'] === $friend_code) {
            throw new Exception("You cannot use your own referral code");
        }
        
        // Find the referrer
        $sql = "SELECT id, first_name, last_name FROM bank_users WHERE referral_code = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $friend_code);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('Invalid referral code');
        }
        
        $referrer = $result->fetch_assoc();
        $referrer_id = $referrer['id'];
        $referrer_name = $referrer['first_name'] . ' ' . $referrer['last_name'];
        $stmt->close();
        
        // Check if user already used a referral code
        $sql = "SELECT id FROM referrals WHERE referred_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            throw new Exception('You have already used a referral code');
        }
        $stmt->close();
        
        // Points to award
        $referrer_points = 20.00;
        $referred_points = 10.00;
        
        // Create referral record
        $sql = "INSERT INTO referrals (referrer_id, referred_id, points_earned) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iid", $referrer_id, $user_id, $referrer_points);
        $stmt->execute();
        $stmt->close();
        
        // Award points to referrer
        $sql = "UPDATE bank_users SET total_points = total_points + ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("di", $referrer_points, $referrer_id);
        $stmt->execute();
        $stmt->close();
        
        // Award points to referred user
        $sql = "UPDATE bank_users SET total_points = total_points + ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("di", $referred_points, $user_id);
        $stmt->execute();
        $stmt->close();
        
        // Add to point history for referrer - FIXED VERSION
        $sql = "INSERT IGNORE INTO user_missions (user_id, mission_id, points_earned, completed_at) 
                SELECT ?, id, ?, NOW() FROM missions WHERE mission_text LIKE '%Refer a friend%' LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("id", $referrer_id, $referrer_points);
        $stmt->execute();
        $stmt->close();
        
        // Add to point history for referred user - FIXED VERSION
        $sql = "INSERT IGNORE INTO user_missions (user_id, mission_id, points_earned, completed_at) 
                SELECT ?, id, ?, NOW() FROM missions WHERE mission_text LIKE '%Use a referral code%' LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("id", $user_id, $referred_points);
        $stmt->execute();
        $stmt->close();
        
        $conn->commit();
        
        // Get updated total points for the user - THIS IS IMPORTANT
        $sql = "SELECT total_points FROM bank_users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_data = $result->fetch_assoc();
        $total_points = $user_data['total_points'];
        $stmt->close();
        
        echo json_encode([
            'success' => true,
            'message' => 'Referral applied successfully!',
            'points_earned' => number_format($referred_points, 2, '.', ''),
            'referrer_name' => $referrer_name,
            'total_points' => number_format($total_points, 2, '.', '')  // THIS WAS MISSING IN YOUR ORIGINAL
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}
?>