<?php
   session_start([
       'cookie_httponly' => true,
       'cookie_secure' => isset($_SERVER['HTTPS']), // Set to true if using HTTPS
       'use_strict_mode' => true
    ]);

    // Check if user is logged in using the correct session variable from login.php
    if (!isset($_SESSION['customer_id']) || !isset($_SESSION['email'])) {
        // If not logged in, redirect to login page
        header("Location: login.php");
        exit;
    }

    // Get user info from session
    $fullName = $_SESSION['full_name'] ?? ($_SESSION['customer_first_name'] . ' ' . $_SESSION['customer_last_name']);
    $firstName = $_SESSION['first_name'] ?? 'Customer';


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
        $referrer_points = 50.00;
        $referred_points = 25.00;
        
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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
            background-image: url("images/referbg.png");
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        nav {
            position: fixed;
            top: 0;
            width: 100%;
            background: #003631;
            padding: 1rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        nav span {
            font-size: 24px;
        }

        nav span a {
            color: white;
            text-decoration: none;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: white;
            font-size: 1.2rem;
            font-weight: bold;
        }

        .logo-icon {
            width: 50px;
            height: 50px;
            background: transparent;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            overflow: hidden;
        }

        .logo-icon img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            object-position: center;
            display: block;
            border-radius: 50%;
            background: transparent;
        }

        .nav-links {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            margin: 0 1.1rem;
            font-size: 1rem;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: #F1B24A;
        }

        .nav-buttons {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .username-profile {
            background: transparent;
            color: #FFFFFF;
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: 5px;
        }

        .username-profile:hover {
            color: #F1B24A;
        }

        .profile-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
            position: relative;
        }

        .profile-btn {
            width: 40px;
            height: 40px;
            background: transparent;
            border: none;
            padding: 0;
            cursor: pointer;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .profile-btn img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            background-color: #003631;
            display:block;
        }

        .profile-dropdown {
            display: none;
            position: absolute;
            right: 0;
            top: calc(100% + 8px);
            background: #D9D9D9;
            color: #003631;
            border-radius: 8px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.12);
            min-width: 160px;
            z-index: 200;
        }

        .profile-dropdown a {
            display: block;
            padding: 0.65rem 1rem;
            color: #003631;
            text-decoration: none;
            font-weight: 600;
        }

        .profile-dropdown a:hover {
            background: rgba(0,0,0,0.04);
        }

        .profile-dropdown.show {
            display: block;
        }

        /* DROPDOWN STYLES  */
.dropdown {
    position: relative;
}   

.dropbtn {
    background: none;
    border: none;
    color: white;
    font-size: 1rem;
    cursor: pointer;
    padding: 0.5rem 1rem;
    transition: color 0.3s;
}

.dropbtn:hover {
    color: #F1B24A;
}

/* Dropdown menu box - FULL WIDTH */
.dropdown-content {
    display: none;
    position: fixed;
    left: 0;
    top: 80px;
    width: 100vw;
    background-color: #D9D9D9;
    padding: 1.5rem 5%;
    box-shadow: 0 8px 16px rgba(0,0,0,0.15);
    z-index: 99;
    text-align: center;
}

/* Links inside dropdown */
.dropdown-content a {
    color: #003631;
    margin: 0 2rem;
    font-size: 1rem;
    text-decoration: none;
    display: inline-block;
    padding: 0.5rem 1rem;
    transition: all 0.3s ease;
    font-weight: 500;
}

.dropdown-content a:hover {
    color: #F1B24A;
    transform: translateY(-2px);
}

        .main-content {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 120px 5% 60px;
        }

        .refer-container {
            max-width: 700px;
            width: 100%;
            text-align: center;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #003631 0%, #1a6b62 100%);
            border-radius: 50%;
            margin: 0 auto 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 30px rgba(0,54,49,0.3);
        }

        .profile-avatar img {
            width: 60px;
            height: 60px;
            opacity: 0.9;
        }

        .refer-title {
            color: #003631;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 40px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .refer-card {
            background: white;
            border-radius: 20px;
            padding: 50px 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            position: relative;
            overflow: visible;
            width: 250%;
            margin-left: -75%;
        }

        .gifts-decoration {
            position: absolute;
            right: 30%;
            top: 40%;
            transform: translateY(-50%);
            width: 250px;
            height: 250px;
            pointer-events: none;
        }

        .gifts-decoration img {
            width: 300%;
            height: 150%;
        }

        .form-group {
            padding-top: 3%;
            margin-bottom: 30px;
            display: flex;
            text-align: left;
            flex-direction: column;
        }

        .form-label {
            display: block;
            color: #003631;
            font-weight: 600;
            margin-bottom: 15px;
            font-size: 30px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: left;
            flex-direction: column;
            margin-bottom: 30px;
        }

        .form-input {
            width: 50%;
            padding: 15px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            background: #f8f8f8;
            color: #003631;
            font-weight: 600;
            letter-spacing: 3px;
            height: 60px;
            text-transform: uppercase;
        }

        .form-input:focus {
            outline: none;
            border-color: #F1B24A;
            background: white;
        }

        .form-input[readonly] {
            color: #003631;
            cursor: default;
            background: #f8f8f8;
        }

        .eye-icon {
            position: absolute;
            right: 52%;
            top: 14px;
            cursor: pointer;
            color: #999;
            font-size: 1.2rem;
            transition: color 0.3s;
            user-select: none;
        }

        .eye-icon:hover {
            color: #003631;
        }

        .friend-code-input {
            padding: 15px 20px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #003631;
            font-weight: 600;
            background: white;
        }

        .confirm-btn {
            width: 100%;
            max-width: 200px;
            padding: 15px 30px;
            background: #F1B24A;
            color: #003631;
            border: none;
            border-radius: 30px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 20px;
            margin-right: 12%;
        }

        .confirm-btn:hover {
            background: #e0a03a;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(241, 178, 74, 0.3);
        }

        .confirm-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .stats-container {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .stat-box {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 20px 30px;
            border-radius: 15px;
            border: 2px solid #dee2e6;
            min-width: 150px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #003631;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            color: #666;
            font-weight: 500;
        }

        footer {
            background: #003631;
            color: white;
            padding: 3rem 5% 1rem;
        }

        .footer-content {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 3rem;
            margin-bottom: 2rem;
        }

        .footer-brand p {
            color: rgba(255,255,255,0.7);
            margin: 1rem 0;
            line-height: 1.6;
        }

        .social-icons {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .social-icon {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.3s;
            gap: 15px;
        }
        
        .contact-icon {
            width: 15px;
        }

        .social-icon:hover {
            background-color: #F1B24A;
        }

        .footer-section h4 {
            margin-bottom: 1rem;
        }

        .footer-section ul {
            list-style: none;
        }

        .footer-section ul li {
            margin-bottom: 0.5rem;
        }

        .footer-section ul li a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-section ul li a:hover {
            color: #F1B24A;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            color: rgba(255,255,255,0.7);
        }

        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .footer-links {
            display: flex;
            gap: 2rem;
        }

        .footer-links a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            font-size: 0.9rem;
        }

        /* Tablet and smaller desktop */
@media (max-width: 968px) {
    nav {
        padding: 1rem 3%;
    }

    .nav-links {
        gap: 1rem;
    }

    .nav-links a {
        font-size: 0.95rem;
        margin: 0 0.5rem;
    }

    .dropdown-content {
        padding: 1.2rem 3%;
        top: 80px;
    }

    .dropdown-content a {
        margin: 0 1rem;
        font-size: 0.95rem;
    }

    .refer-title {
        font-size: 1.8rem;
    }

    .refer-card {
        width: 200%;
        margin-left: -50%;
        padding: 40px 30px;
    }

    .gifts-decoration {
        width: 200px;
        height: 200px;
        right: 25%;
    }

    .form-input {
        width: 60%;
    }

    .eye-icon {
        right: 42%;
    }

    .footer-content {
        grid-template-columns: 1fr 1fr;
    }
}

/* Mobile landscape and smaller tablets */
@media (max-width: 768px) {
    .main-content {
        padding: 100px 3% 40px;
    }

    .refer-title {
        font-size: 1.6rem;
        margin-bottom: 30px;
    }

    .refer-card {
        width: 100%;
        margin-left: 0;
        padding: 35px 25px;
    }

    .gifts-decoration {
        display: none;
    }

    .form-label {
        font-size: 24px;
    }

    .form-input {
        width: 100%;
        height: 55px;
        font-size: 0.95rem;
    }

    .eye-icon {
        right: 15px;
    }

    .confirm-btn {
        max-width: 180px;
        margin-right: 0;
    }

    .footer-content {
        grid-template-columns: 1fr;
    }
}

/* Mobile devices */
@media (max-width: 640px) {
    nav {
        padding: 1rem 3%;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .logo {
        font-size: 1rem;
    }

    .logo-icon {
        width: 40px;
        height: 40px;
    }

    .nav-links {
        order: 3;
        width: 100%;
        justify-content: center;
        gap: 0.8rem;
        flex-wrap: wrap;
    }

    .nav-links a {
        font-size: 0.9rem;
        margin: 0 0.3rem;
    }

    .dropdown-content {
        top: 120px;
        padding: 1rem 2%;
    }

    .dropdown-content a {
        margin: 0.3rem 0.5rem;
        font-size: 0.85rem;
        padding: 0.4rem 0.8rem;
    }

    .main-content {
        padding: 140px 5% 40px;
    }

    .profile-avatar {
        width: 100px;
        height: 100px;
        margin-bottom: 25px;
    }

    .profile-avatar img {
        width: 50px;
        height: 50px;
    }

    .refer-title {
        font-size: 1.4rem;
        margin-bottom: 25px;
    }

    .refer-card {
        padding: 30px 20px;
    }

    .form-label {
        font-size: 20px;
        margin-bottom: 12px;
    }

    .form-input {
        height: 50px;
        font-size: 0.9rem;
        padding: 12px 15px;
        letter-spacing: 2px;
    }

    .eye-icon {
        right: 15px;
        top: 12px;
        font-size: 1.1rem;
    }

    .confirm-btn {
        width: 100%;
        max-width: 100%;
        padding: 12px 25px;
        font-size: 0.95rem;
    }

    .stat-box {
        min-width: 120px;
        padding: 15px 20px;
    }

    .stat-value {
        font-size: 28px;
    }

    .stat-label {
        font-size: 13px;
    }

    .footer-content {
        grid-template-columns: 1fr;
        gap: 2rem;
    }

    .footer-bottom {
        flex-direction: column;
        text-align: center;
    }

    .footer-links {
        flex-direction: column;
        gap: 1rem;
    }
}

/* Extra small mobile devices */
@media (max-width: 480px) {
    nav {
        padding: 0.8rem 3%;
    }

    .dropdown-content a {
        display: inline-block;
        margin: 0.2rem 0.3rem;
        font-size: 0.8rem;
    }

    .username-profile {
        font-size: 0.85rem;
        padding: 0.4rem 0.8rem;
    }

    .nav-buttons {
        gap: 0.5rem;
    }

    .profile-btn {
        width: 35px;
        height: 35px;
    }

    .main-content {
        padding: 150px 3% 30px;
    }

    .profile-avatar {
        width: 80px;
        height: 80px;
        margin-bottom: 20px;
    }

    .profile-avatar img {
        width: 40px;
        height: 40px;
    }

    .refer-title {
        font-size: 1.2rem;
        margin-bottom: 20px;
    }

    .refer-card {
        padding: 25px 15px;
    }

    .form-group {
        padding-top: 2%;
        margin-bottom: 20px;
    }

    .form-label {
        font-size: 18px;
        margin-bottom: 10px;
    }

    .input-wrapper {
        margin-bottom: 20px;
    }

    .form-input {
        height: 45px;
        font-size: 0.85rem;
        padding: 10px 12px;
        letter-spacing: 1.5px;
    }

    .eye-icon {
        right: 12px;
        top: 10px;
        font-size: 1rem;
    }

    .confirm-btn {
        padding: 10px 20px;
        font-size: 0.9rem;
        margin-top: 15px;
    }

    .stats-container {
        flex-direction: column;
        gap: 15px;
    }

    .stat-box {
        width: 100%;
        min-width: auto;
        padding: 12px 20px;
    }

    .stat-value {
        font-size: 24px;
    }

    .stat-label {
        font-size: 12px;
    }
}
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav>
        <div class="logo">
            <div class="logo-icon">
                <img src="images/Logo.png.png">
            </div>
            <span>
                <a href="viewingpage.php">EVERGREEN</a>
            </span>
        </div>

        <div class="nav-links">
            <a href="viewingpage.php">Home</a>

        <div class="dropdown">
            <button class="dropbtn" onclick="toggleDropdown()">Cards ⏷</button>
                <div class="dropdown-content" id="cardsDropdown">
                    <a href="./cards/credit.php">Credit Cards</a>
                    <a href="./cards/debit.php">Debit Cards</a>
                    <a href="./cards/prepaid.php">Prepaid Cards</a>
                    <a href="./cards/rewards.php">Card Rewards</a>
                </div>
        </div>

                     <a href="../../LoanSubsystem/index.php">Loans</a>
                     <a href="./about.php">About Us</a>
        </div>

        <div class="nav-buttons">
            <a href="#" class="username-profile"><?php echo htmlspecialchars($fullName); ?></a>

            <div class="profile-actions">
                <div class="logo-icon" style="width:40px;height:40px;">
                    <button id="profileBtn" class="profile-btn" aria-haspopup="true" aria-expanded="false" onclick="toggleProfileDropdown(event)" title="Open profile menu">
                        <img src="images/pfp.png" alt="Profile Icon">
                    </button>
                </div>

                <div id="profileDropdown" class="profile-dropdown" role="menu" aria-labelledby="profileBtn">
                    <a href="../Basic-operation/operations/public/customer/account" role="menuitem">Profile</a>
                    <a href="../Basic-operation/operations/public/customer/referral" role="menuitem">Refer to a friend</a>
                    <a href="./cards/points.php" role="menuitem">Missions</a>
                    <a href="viewing.php" role="menuitem" onclick="showSignOutModal(event)">Sign Out</a>
                </div>
            </div>
        </div>
    </nav>


    <div class="main-content">
        <div class="refer-container">
            <div class="profile-avatar">
                <img src="images/pfp.png" alt="Profile">
            </div>

            <h1 class="refer-title">Refer Friends. Earn Points. Win Together.</h1>

            <div class="refer-card">
                <form id="referForm">
                    <div class="form-group">
                        <label class="form-label">Your code:</label>
                        <div class="input-wrapper">
                            <input type="text" class="form-input" id="userCode" maxlength="6" readonly>
                            <span class="eye-icon" onclick="toggleCodeVisibility()" title="Show code">👁</span>
                        </div>

                        <label class="form-label">Enter your friend's code:</label>
                        <div class="input-wrapper">
                            <input type="text" class="form-input friend-code-input" id="friendCode" maxlength="6" placeholder="Enter code">
                        </div>

                    </div>

                    <button type="submit" class="confirm-btn" id="confirmBtn">Confirm</button>
                </form>

                <div class="gifts-decoration">
                    <img src="images/gift.png" alt="Gifts">
                </div>
            </div>
        </div>
    </div>



    
   <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-brand">
                <div class="logo">
                    <div class="logo-icon">
                        <img src="images/icon.png">
                    </div>
                </div>
                <p>Secure. Invest. Achieve. Your trusted financial partner for a prosperous future.</p>
                <div class="social-icons">
                    <div class="social-icon">
                        <a href="https://www.facebook.com/profile.php?id=61582812214198">
                            <img src="images/fb-trans.png" alt="facebook" class="contact-icon">
                        </a>
                        <a href="https://www.instagram.com/evergreenbanking/">
                            <img src="images/trans-ig.png" alt="instagram" class="contact-icon">
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="footer-section">
                <h4>Products</h4>
                <ul>
                    <li><a href="./cards/credit.php">Credit Cards</a></li>
                    <li><a href="./cards/debit.php">Debit Cards</a></li>
                    <li><a href="./cards/prepaid.php">Prepaid Cards</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h4>Services</h4>
                <ul>
                    <li><a href="#">Home Loans</a></li>
                    <li><a href="#">Personal Loans</a></li>
                    <li><a href="#">Auto Loans</a></li>
                    <li><a href="#">Multipurpose Loans</a></li>
                    <li><a href="#">Website Banking</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h4>Contact Us</h4>
                <div class="contact-item">📞 1-800-EVERGREEN</div>
                <div class="contact-item">✉️ evrgrn.64@gmail.com</div>
                <div class="contact-item">📍 123 Financial District, Suite 500<br>&nbsp;&nbsp;&nbsp;&nbsp;New York, NY 10004</div>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>© 2023 Evergreen Bank. All rights reserved.<br>Member FDIC. Equal Housing Lender. Evergreen Bank, N.A.</p>
            <div class="footer-links">
                <a href="policy.php">Privacy Policy</a>
                <a href="terms.php">Terms and Agreements</a>
                <a href="faq.php">FAQS</a>
                <a href="about.php">About Us</a>
            </div>
        </div>
    </footer>

    <script>

        let codeVisible = false;
        let userReferralCode = '';

        // Load user's referral code and stats on page load
        document.addEventListener('DOMContentLoaded', async function() {
            await loadReferralCode();
            await loadReferralStats();
        });

        async function loadReferralCode() {
            try {
                const response = await fetch('referral_api.php?action=get_referral_code');
                const data = await response.json();
                
                if (data.success) {
                    userReferralCode = data.referral_code;
                    // Display masked code initially
                    document.getElementById('userCode').value = '••••••';
                } else {
                    console.error('Failed to load referral code');
                    document.getElementById('userCode').value = 'ERROR';
                }
            } catch (error) {
                console.error('Error loading referral code:', error);
                document.getElementById('userCode').value = 'ERROR';
            }
        }

        async function loadReferralStats() {
            try {
                const response = await fetch('referral_api.php?action=get_referral_stats');
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('totalReferrals').textContent = data.total_referrals;
                    document.getElementById('referralPoints').textContent = data.referral_points;
                }
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        }

        function toggleCodeVisibility() {
            const codeInput = document.getElementById('userCode');
            const eyeIcon = document.querySelector('.eye-icon');
            
            codeVisible = !codeVisible;
            
            if (codeVisible) {
                codeInput.value = userReferralCode;
                eyeIcon.textContent = '👁';
                eyeIcon.title = 'Hide code';
            } else {
                codeInput.value = '••••••';
                eyeIcon.textContent = '👁';
                eyeIcon.title = 'Show code';
            }
        }

        document.getElementById('referForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const friendCode = document.getElementById('friendCode').value.trim().toUpperCase();
    const confirmBtn = document.getElementById('confirmBtn');
    
    if (!friendCode) {
        showModal('Error', 'Please enter a referral code', 'error');
        return;
    }
    
    confirmBtn.disabled = true;
    confirmBtn.textContent = 'Processing...';
    
    try {
        const formData = new FormData();
        formData.append('action', 'apply_referral');
        formData.append('friend_code', friendCode);
        
        const response = await fetch('referral_api.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showModal(
                'Success!',
                `You earned ${data.points_earned} points! Your friend ${data.referrer_name} also earned points. Your total points: ${data.total_points}`,
                'success',
                true  // Add reload flag
            );
            document.getElementById('friendCode').value = '';
        } else {
            showModal('Error', data.message, 'error');
        }
    } catch (error) {
        showModal('Error', 'An error occurred. Please try again.', 'error');
    } finally {
        confirmBtn.disabled = false;
        confirmBtn.textContent = 'Confirm';
    }
});

        function showModal(title, message, type, shouldReload = false) {
    const modal = document.createElement('div');
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 54, 49, 0.8);
        backdrop-filter: blur(4px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
        animation: fadeIn 0.3s ease;
    `;
    
    const iconBg = type === 'success' ? '#28a745' : '#dc3545';
    const icon = type === 'success' ? '✓' : '✕';
    
    modal.innerHTML = `
        <style>
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            @keyframes slideUp {
                from { opacity: 0; transform: translateY(20px); }
                to { opacity: 1; transform: translateY(0); }
            }
        </style>
        <div style="
            background: white;
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 420px;
            width: 90%;
            text-align: center;
            animation: slideUp 0.4s ease;
        ">
            <div style="
                width: 80px;
                height: 80px;
                background: ${iconBg};
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 1.5rem;
                font-size: 3rem;
                color: white;
            ">${icon}</div>
            
            <h3 style="
                color: #003631;
                margin-bottom: 0.75rem;
                font-size: 1.75rem;
                font-weight: 600;
            ">${title}</h3>
            
            <p style="
                color: #666;
                margin-bottom: 2rem;
                font-size: 1rem;
                line-height: 1.6;
            ">${message}</p>
            
            <button id="modalOkBtn" style="
                background: #003631;
                color: white;
                border: none;
                padding: 12px 32px;
                border-radius: 8px;
                font-size: 14px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.2s;
            " onmouseover="this.style.background='#F1B24A'; this.style.color='#003631'" onmouseout="this.style.background='#003631'; this.style.color='white'">
                OK
            </button>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    const okBtn = modal.querySelector('#modalOkBtn');
    okBtn.addEventListener('click', function() {
        modal.remove();
        if (shouldReload) {
            location.reload(); // Reload the page to show updated stats
        }
    });
    
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.remove();
            if (shouldReload) {
                location.reload();
            }
        }
    });
}

        // Smooth scrolling for navigation
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add scroll effect to navbar
        let lastScroll = 0;
        const nav = document.querySelector('nav');

        window.addEventListener('scroll', () => {
            const currentScroll = window.pageYOffset;
            
            if (currentScroll > 100) {
                nav.style.padding = '0.7rem 5%';
            } else {
                nav.style.padding = '1rem 5%';
            }
            
            lastScroll = currentScroll;
        });

        // Button click animations


        // Intersection Observer for fade-in animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        function toggleDropdown() {
            const dropdown = document.getElementById("cardsDropdown");
            dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
        }

        // Close dropdown when clicking outside
        window.addEventListener("click", function(e) {
            if (!e.target.matches('.dropbtn')) {
                const dropdown = document.getElementById("cardsDropdown");
                if (dropdown && dropdown.style.display === "block") {
                    dropdown.style.display = "none";
                }
            }
        }); 

        // Profile Toggle Dropdown
        function toggleProfileDropdown(e) {
            e.stopPropagation();
            const dd = document.getElementById('profileDropdown');
            const btn = document.getElementById('profileBtn');
            const isOpen = dd.classList.toggle('show');
            btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        }

        // close profile dropdown when clicking outside or pressing Esc
        window.addEventListener('click', function (e) {
            const dd = document.getElementById('profileDropdown');
            const btn = document.getElementById('profileBtn');
            if (!dd) return;
            if (dd.classList.contains('show') && !e.composedPath().includes(dd) && e.target !== btn) {
                dd.classList.remove('show');
                btn.setAttribute('aria-expanded', 'false');
            }
        });

        window.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const dd = document.getElementById('profileDropdown');
                const btn = document.getElementById('profileBtn');
                if (dd && dd.classList.contains('show')) {
                    dd.classList.remove('show');
                    btn.setAttribute('aria-expanded', 'false');
                }
            }
        });

        // Custom styled confirmation modal that matches Evergreen Bank design
function showSignOutModal(event) {
    event.preventDefault();
    
    // Create modal overlay
    const modal = document.createElement('div');
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 54, 49, 0.8);
        backdrop-filter: blur(4px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
        animation: fadeIn 0.2s ease;
    `;
    
    // Create modal content
    modal.innerHTML = `
        <style>
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            @keyframes slideUp {
                from { 
                    opacity: 0;
                    transform: translateY(20px);
                }
                to { 
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            /* Image Icon */
            img {
            width: 55px;
            height: 50px;
            margin-bottom:5px;
            }
        </style>
        <div style="
            background: white;
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 420px;
            width: 90%;
            text-align: center;
            animation: slideUp 0.3s ease;
        ">
            <div style="
                width: 90px;
                height: 90px;
                background: linear-gradient(135deg, #003631 0%, #1a6b62 100%);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-direction: start;
                margin: 0 auto 2.5rem;
                font-size: 2rem;
            "><img src="images/warning.png"></div>
            
            <h3 style="
                color: #003631;
                margin-bottom: 0.75rem;
                font-size: 1.75rem;
                font-weight: 600;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            ">Sign Out</h3>
            
            <p style="
                color: #666;
                margin-bottom: 20px;
                font-size: 1rem;
                line-height: 1.6;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            ">Are you sure you want to sign out of your account?</p>
            
            <div style="
                display: flex;
                gap: 1rem;
                justify-content: center;
            ">
                <button id="cancelBtn" style="
                    padding: 0.85rem 2rem;
                    background: transparent;
                    color: #003631;
                    border: 2px solid #003631;
                    border-radius: 8px;
                    cursor: pointer;
                    font-weight: 600;
                    font-size: 0.95rem;
                    transition: all 0.3s ease;
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                ">Cancel</button>
                
                <button id="confirmBtn" style="
                    padding: 0.85rem 2rem;
                    background: #003631;
                    color: white;
                    border: 2px solid #003631;
                    border-radius: 8px;
                    cursor: pointer;
                    font-weight: 600;
                    font-size: 0.95rem;
                    transition: all 0.3s ease;
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                ">Sign Out</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Get buttons
    const cancelBtn = modal.querySelector('#cancelBtn');
    const confirmBtn = modal.querySelector('#confirmBtn');
    
    // Add hover effects for Cancel button
    cancelBtn.onmouseover = () => {
        cancelBtn.style.background = '#f5f5f5';
        cancelBtn.style.borderColor = '#003631';
        cancelBtn.style.transform = 'translateY(-2px)';
    };
    cancelBtn.onmouseout = () => {
        cancelBtn.style.background = 'transparent';
        cancelBtn.style.transform = 'translateY(0)';
    };
    
    // Add hover effects for Confirm button
    confirmBtn.onmouseover = () => {
        confirmBtn.style.background = '#F1B24A';
        confirmBtn.style.borderColor = '#F1B24A';
        confirmBtn.style.color = '#003631';
        confirmBtn.style.transform = 'translateY(-2px)';
        confirmBtn.style.boxShadow = '0 4px 12px rgba(241, 178, 74, 0.3)';
    };
    confirmBtn.onmouseout = () => {
        confirmBtn.style.background = '#003631';
        confirmBtn.style.borderColor = '#003631';
        confirmBtn.style.color = 'white';
        confirmBtn.style.transform = 'translateY(0)';
        confirmBtn.style.boxShadow = 'none';
    };
    
    // Handle button clicks
    cancelBtn.onclick = () => document.body.removeChild(modal);
    confirmBtn.onclick = () => window.location.href = 'logout.php';
    
    // Close on outside click
    modal.onclick = (e) => {
        if (e.target === modal) {
            document.body.removeChild(modal);
        }
    };
    
    // Close on Escape key
    const handleEscape = (e) => {
        if (e.key === 'Escape' && document.body.contains(modal)) {
            document.body.removeChild(modal);
            document.removeEventListener('keydown', handleEscape);
        }
    };
    document.addEventListener('keydown', handleEscape);
}
    </script>
</body>
</html>