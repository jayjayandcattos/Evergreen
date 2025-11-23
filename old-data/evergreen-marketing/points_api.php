<?php
// Turn off error display and capture errors instead
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set JSON header immediately
header('Content-Type: application/json');

// Start output buffering to catch any errors
ob_start();

try {
    session_start();
    include("db_connect.php");
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false, 
        'message' => 'Initialization error: ' . $e->getMessage()
    ]);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['customer_id'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Missions array - all missions defined here
$MISSIONS = [
    1 => [
        'id' => 1,
        'mission_text' => 'Refer your first friend to EVERGREEN',
        'points_value' => 50.00,
        'required_referrals' => 1
    ],
    2 => [
        'id' => 2,
        'mission_text' => 'Successfully refer 3 friends',
        'points_value' => 150.00,
        'required_referrals' => 3
    ],
    3 => [
        'id' => 3,
        'mission_text' => 'Reach 5 successful referrals',
        'points_value' => 250.00,
        'required_referrals' => 5
    ],
    4 => [
        'id' => 4,
        'mission_text' => 'Refer 10 friends and unlock premium rewards',
        'points_value' => 500.00,
        'required_referrals' => 10
    ],
    5 => [
        'id' => 5,
        'mission_text' => 'Achieve 15 referrals milestone',
        'points_value' => 750.00,
        'required_referrals' => 15
    ],
    6 => [
        'id' => 6,
        'mission_text' => 'Become a referral champion with 20 friends',
        'points_value' => 1000.00,
        'required_referrals' => 20
    ],
    7 => [
        'id' => 7,
        'mission_text' => 'Share your referral code on social media',
        'points_value' => 30.00,
        'required_referrals' => 0  // Always available
    ],
    8 => [
        'id' => 8,
        'mission_text' => 'Have 3 friends use your referral code in one week',
        'points_value' => 200.00,
        'required_referrals' => 0  // Manual check - always pending
    ],
    9 => [
        'id' => 9,
        'mission_text' => 'Reach 25 total referrals - Elite status',
        'points_value' => 1500.00,
        'required_referrals' => 25
    ],
    10 => [
        'id' => 10,
        'mission_text' => 'Ultimate referrer - 50 successful referrals',
        'points_value' => 3000.00,
        'required_referrals' => 50
    ],
    11 => [
        'id' => 11,
        'mission_text' => 'Refer a friend and earn bonus points',
        'points_value' => 20.00,
        'required_referrals' => 1
    ],
    12 => [
        'id' => 12,
        'mission_text' => 'Use a referral code to get started',
        'points_value' => 10.00,
        'required_referrals' => 0  // Always available
    ]
];

$customer_id = $_SESSION['customer_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    // Clean any output before processing
    ob_clean();
    
    switch ($action) {
        case 'get_user_points':
            getUserPoints($conn, $customer_id);
            break;
        
        case 'get_missions':
            getMissions($conn, $customer_id);
            break;
        
        case 'collect_mission':
            collectMission($conn, $customer_id);
            break;
        
        case 'get_point_history':
            getPointHistory($conn, $customer_id);
            break;
        
        case 'get_completed_missions':
            getCompletedMissions($conn, $customer_id);
            break;
        
        // Add this case in your switch statement
        case 'redeem_reward':
            if (!isset($_SESSION['customer_id'])) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Not logged in']);
                exit;
            }
            
            $reward_name = $_POST['reward_name'] ?? '';
            $points_cost = floatval($_POST['points_cost'] ?? 0);
            
            if (empty($reward_name) || $points_cost <= 0) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Invalid reward data']);
                exit;
            }
            
            // Get customer's current points from bank_customers table
            $customer_id = $_SESSION['customer_id'];
            $stmt = $conn->prepare("SELECT total_points FROM bank_customers WHERE customer_id = ?");
            $stmt->bind_param("i", $customer_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $customer = $result->fetch_assoc();
            
            if ($customer['total_points'] < $points_cost) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Insufficient points']);
                exit;
            }
            
            // Deduct points
            $new_total = $customer['total_points'] - $points_cost;
            $stmt = $conn->prepare("UPDATE bank_customers SET total_points = ? WHERE customer_id = ?");
            $stmt->bind_param("di", $new_total, $customer_id);
            $stmt->execute();
            
            // Log the redemption in history (negative points)
            $description = "Redeemed: " . $reward_name;
            $negative_points = -$points_cost;
            $stmt = $conn->prepare("INSERT INTO points_history (user_id, points, description, transaction_type) VALUES (?, ?, ?, 'redemption')");
            $stmt->bind_param("ids", $customer_id, $negative_points, $description);
            $stmt->execute();
            
            ob_clean();
            echo json_encode([
                'success' => true,
                'new_total' => $new_total,
                'points_deducted' => $points_cost,
                'message' => 'Reward redeemed successfully'
            ]);
        break;
        
        default:
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
        'error_type' => get_class($e),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
} catch (Error $e) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Fatal error: ' . $e->getMessage(),
        'error_type' => get_class($e),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

function getUserPoints($conn, $customer_id) {
    ob_clean();
    
    // Try bank_customers first, then bank_users
    $sql = "SELECT total_points FROM bank_customers WHERE customer_id = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        // Try alternative table name
        $sql = "SELECT total_points FROM bank_users WHERE id = ?";
        $stmt = $conn->prepare($sql);
    }
    
    if (!$stmt) {
        echo json_encode([
            'success' => false, 
            'message' => 'Database error: ' . $conn->error
        ]);
        return;
    }
    
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'total_points' => number_format($row['total_points'], 2, '.', '')
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Customer not found']);
    }
    $stmt->close();
}

function getMissions($conn, $customer_id) {
    global $MISSIONS;
    
    // Debug: Check if $MISSIONS array exists
    if (!isset($MISSIONS) || empty($MISSIONS)) {
        ob_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Missions array not found or empty',
            'missions' => [],
            'debug' => 'MISSIONS array is not set'
        ]);
        return;
    }
    
    try {
        // Initialize referral count to 0 in case of errors
        $referral_count = 0;
        $collected_mission_ids = [];
        
        // Get customer's current referral count - try both table names
        $referral_sql = "SELECT COUNT(*) as referral_count FROM bank_customers WHERE referred_by_customer_id = ?";
        $stmt = $conn->prepare($referral_sql);
        
        if (!$stmt) {
            // Try alternative table name
            $referral_sql = "SELECT COUNT(*) as referral_count FROM bank_users WHERE referred_by = ?";
            $stmt = $conn->prepare($referral_sql);
        }
        
        if ($stmt) {
            $stmt->bind_param("i", $customer_id);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                $referral_data = $result->fetch_assoc();
                $referral_count = $referral_data ? (int)$referral_data['referral_count'] : 0;
            }
            $stmt->close();
        }
        
        // Get collected missions from user_missions table
        // Try with status column first, fallback to just checking if record exists
        $collected_sql = "SELECT mission_id FROM user_missions WHERE user_id = ? AND status = 'collected'";
        $stmt = $conn->prepare($collected_sql);
        
        if (!$stmt) {
            // If status column doesn't exist, just get all missions for this user
            $collected_sql = "SELECT mission_id FROM user_missions WHERE user_id = ?";
            $stmt = $conn->prepare($collected_sql);
        }
        
        if ($stmt) {
            $stmt->bind_param("i", $customer_id);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $collected_mission_ids[] = (int)$row['mission_id'];
                }
            }
            $stmt->close();
        }
        
        // Build missions array from $MISSIONS array
        $missions = [];
        foreach ($MISSIONS as $mission) {
            // Skip if already collected
            if (in_array($mission['id'], $collected_mission_ids)) {
                continue;
            }
            
            // Determine mission status based on requirements
            $mission_status = 'pending';
            
            // Always available missions (7 = social media, 12 = use referral code)
            if ($mission['id'] == 7 || $mission['id'] == 12) {
                $mission_status = 'available';
            } elseif ($mission['id'] == 8) {
                // Weekly challenge - always pending (manual check)
                $mission_status = 'pending';
            } elseif (isset($mission['required_referrals'])) {
                if ($mission['required_referrals'] == 0) {
                    // Missions with 0 required referrals are always available
                    $mission_status = 'available';
                } elseif ($referral_count >= $mission['required_referrals']) {
                    // Requirements met
                    $mission_status = 'available';
                }
            }
            
            $missions[] = [
                'id' => $mission['id'],
                'mission_text' => $mission['mission_text'],
                'points_value' => $mission['points_value'],
                'status' => $mission_status,
                'current_referrals' => $referral_count
            ];
        }
        
        // Ensure at least mission 7 and 12 are always shown (if not collected)
        // This is a safety check to ensure missions are displayed
        if (count($missions) == 0 && isset($MISSIONS[7]) && !in_array(7, $collected_mission_ids)) {
            $missions[] = [
                'id' => $MISSIONS[7]['id'],
                'mission_text' => $MISSIONS[7]['mission_text'],
                'points_value' => $MISSIONS[7]['points_value'],
                'status' => 'available',
                'current_referrals' => $referral_count
            ];
        }
        
        ob_clean();
        echo json_encode([
            'success' => true,
            'missions' => $missions,
            'total_referrals' => $referral_count,
            'debug' => [
                'missions_count' => count($MISSIONS),
                'collected_count' => count($collected_mission_ids),
                'returned_count' => count($missions)
            ]
        ]);
    } catch (Exception $e) {
        ob_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Error loading missions: ' . $e->getMessage(),
            'missions' => [],
            'debug' => [
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]
        ]);
    }
}

function collectMission($conn, $customer_id) {
    global $MISSIONS;
    
    $mission_id = $_POST['mission_id'] ?? 0;
    
    if (!$mission_id) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Mission ID required']);
        return;
    }
    
    // Check if mission exists in array
    if (!isset($MISSIONS[$mission_id])) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Mission not found']);
        return;
    }
    
    $conn->begin_transaction();
    
    try {
        // Get customer's referral count - try both table names
        $referral_count = 0;
        $sql = "SELECT COUNT(*) as referral_count FROM bank_customers WHERE referred_by_customer_id = ?";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            // Try alternative table name
            $sql = "SELECT COUNT(*) as referral_count FROM bank_users WHERE referred_by = ?";
            $stmt = $conn->prepare($sql);
        }
        
        if (!$stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }
        
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $referral_data = $result->fetch_assoc();
        $referral_count = $referral_data ? (int)$referral_data['referral_count'] : 0;
        $stmt->close();
        
        // Get mission details from array
        $mission = $MISSIONS[$mission_id];
        $points = $mission['points_value'];
        $mission_text = $mission['mission_text'];
        $required_referrals = $mission['required_referrals'];
        
        // Check if mission requirements are met (except for special missions)
        if ($mission_id != 7 && $mission_id != 8 && $required_referrals > 0 && $referral_count < $required_referrals) {
            throw new Exception('Mission requirements not met. You need ' . $required_referrals . ' referrals.');
        }
        
        // Mission 8 (weekly challenge) should remain pending - manual check required
        if ($mission_id == 8) {
            throw new Exception('This mission requires manual verification. Please contact support.');
        }
        
        // Check if already collected - try with status, fallback without
        $sql = "SELECT id FROM user_missions WHERE user_id = ? AND mission_id = ? AND status = 'collected'";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            // Try without status column
            $sql = "SELECT id FROM user_missions WHERE user_id = ? AND mission_id = ?";
            $stmt = $conn->prepare($sql);
        }
        
        if (!$stmt) {
            throw new Exception('Database error checking collected missions: ' . $conn->error);
        }
        
        $stmt->bind_param("ii", $customer_id, $mission_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $stmt->close();
            throw new Exception('Mission already collected');
        }
        $stmt->close();
        
        // Add or update mission record - try with status, fallback without
        $sql = "INSERT INTO user_missions (user_id, mission_id, points_earned, status) 
                VALUES (?, ?, ?, 'collected')
                ON DUPLICATE KEY UPDATE status = 'collected', completed_at = NOW()";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            // Try without status column
            $sql = "INSERT INTO user_missions (user_id, mission_id, points_earned, completed_at) 
                    VALUES (?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE completed_at = NOW()";
            $stmt = $conn->prepare($sql);
        }
        
        if (!$stmt) {
            throw new Exception('Database error inserting mission: ' . $conn->error);
        }
        
        $stmt->bind_param("iid", $customer_id, $mission_id, $points);
        $stmt->execute();
        $stmt->close();
        
        // Update customer's total points - try both table names
        $sql = "UPDATE bank_customers SET total_points = total_points + ? WHERE customer_id = ?";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            // Try alternative table name
            $sql = "UPDATE bank_users SET total_points = total_points + ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
        }
        
        if (!$stmt) {
            throw new Exception('Database error updating points: ' . $conn->error);
        }
        
        $stmt->bind_param("di", $points, $customer_id);
        $stmt->execute();
        $stmt->close();

        // ✅ ADD THIS: Log the mission collection in points_history
        $sql = "INSERT INTO points_history (user_id, points, description, transaction_type) 
                VALUES (?, ?, ?, 'mission')";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            // If points_history table doesn't exist, continue without logging
            // This is not critical, so we'll just log a warning
            error_log('Warning: points_history table not found, skipping history log');
        } else {
            $stmt->bind_param("ids", $customer_id, $points, $mission_text);
            $stmt->execute();
            $stmt->close();
        }
        
        // Get updated total - try both table names
        $sql = "SELECT total_points FROM bank_customers WHERE customer_id = ?";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            // Try alternative table name
            $sql = "SELECT total_points FROM bank_users WHERE id = ?";
            $stmt = $conn->prepare($sql);
        }
        
        if (!$stmt) {
            throw new Exception('Database error getting total points: ' . $conn->error);
        }
        
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $customer = $result->fetch_assoc();
        
        if (!$customer) {
            throw new Exception('Customer not found after points update');
        }
        
        $total_points = $customer['total_points'];
        $stmt->close();
        
        $conn->commit();
        
        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Mission completed! 🎉',
            'points_earned' => number_format($points, 2, '.', ''),
            'total_points' => number_format($total_points, 2, '.', ''),
            'mission_text' => $mission_text
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        ob_clean();
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function getPointHistory($conn, $customer_id) {
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
    $stmt->bind_param("i", $customer_id);
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
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'history' => $history
    ]);
    $stmt->close();
}

function getCompletedMissions($conn, $customer_id) {
    global $MISSIONS;
    
    // Try with status column first, fallback without
    $sql = "SELECT um.mission_id, um.points_earned, um.completed_at 
            FROM user_missions um
            WHERE um.user_id = ? AND um.status = 'collected'
            ORDER BY um.completed_at DESC";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        // Try without status column - just get all missions for this user
        $sql = "SELECT um.mission_id, um.points_earned, um.completed_at 
                FROM user_missions um
                WHERE um.user_id = ?
                ORDER BY um.completed_at DESC";
        $stmt = $conn->prepare($sql);
    }
    
    if (!$stmt) {
        ob_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $conn->error,
            'completed' => []
        ]);
        return;
    }
    
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $completed = [];
    while ($row = $result->fetch_assoc()) {
        $mission_id = $row['mission_id'];
        // Get mission text from array, fallback to generic if not found
        $mission_text = isset($MISSIONS[$mission_id]) 
            ? $MISSIONS[$mission_id]['mission_text'] 
            : 'Mission #' . $mission_id;
        
        $completed[] = [
            'points' => number_format($row['points_earned'], 2, '.', ''),
            'description' => $mission_text,
            'timestamp' => date('F j, Y g:i A', strtotime($row['completed_at']))
        ];
    }
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'completed' => $completed
    ]);
    $stmt->close();
}

?>