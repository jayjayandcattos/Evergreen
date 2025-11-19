<?php
    session_start([
       'cookie_httponly' => true,
       'cookie_secure' => isset($_SERVER['HTTPS']),
       'use_strict_mode' => true
    ]);
    // Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['email'])) {
    header("Location: viewing.php");
    exit;
}

// Get user info from session
$fullName = $_SESSION['full_name'] ?? ($_SESSION['first_name'] . ' ' . $_SESSION['last_name']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learnmore</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }

        /* Navigation */
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
            background: transparent; /* was #F1B24A */
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
            object-fit: contain; /* change from cover -> contain */
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
            position: relative; /* needed for dropdown positioning */
        }

        /* profile dropdown */
        .profile-btn {
            width: 40px;
            height: 40px;
            background: transparent;
            border: none;              /* now a button */
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

        .profile-btn {
            width: 40px;
            height: 40px;
            background: transparent;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .profile-btn img {
            width: 200%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            background-color: #003631;
        }

        /* DROPDOWN STYLES - UPDATED TO MATCH CREDIT.PHP */
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

        /* Dropdown menu box - UPDATED FOR FULL WIDTH */
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


        .btn-primary:hover {
            background: #e69610;
            transform: translateY(-2px);
        }
        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, #003631 0%, #004d45 100%);
            padding: 80px 5% 80px;
            margin-top: 70px;
        }

        .hero-container {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
        }

        .trust-badge {
            display: inline-block;
            background: rgba(241, 178, 74, 0.2);
            color: #F1B24A;
            padding: 0.5rem 1.5rem;
            border-radius: 25px;
            margin-bottom: 2rem;
            font-size: 0.9rem;
        }

        .hero-title {
            color: white;
            font-size: 4rem;
            line-height: 1.1;
            margin-bottom: 2rem;
        }

        .hero-title span {
            color: #F1B24A;
        }

        .hero-features {
            display: flex;
            gap: 3rem;
            margin-top: 3rem;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: white;
        }

        .feature-icon {
            color: #F1B24A;
            font-size: 1.2rem;
        }

        /* Dashboard Card */
        .dashboard-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .balance-box {
            background: rgba(255,255,255,0.15);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 1.5rem;
        }

        .balance-label {
            color: rgba(255,255,255,0.8);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .balance-amount {
            color: white;
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .balance-change {
            color: #4ade80;
            font-size: 0.85rem;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .dashboard-item {
            background: rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 1.25rem;
        }

        .item-label {
            color: rgba(255,255,255,0.7);
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
        }

        .item-value {
            color: white;
            font-size: 1.3rem;
            font-weight: bold;
        }

        .item-value.gold {
            color: #F1B24A;
        }

        .activity-box {
            background: rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 1.25rem;
        }

        .activity-title {
            color: rgba(255,255,255,0.9);
            font-size: 0.9rem;
            margin-bottom: 0.75rem;
            font-weight: 600;
        }

        .activity-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .activity-text {
            color: rgba(255,255,255,0.8);
            font-size: 0.9rem;
        }

        .activity-amount {
            color: #4ade80;
            font-weight: bold;
        }

        /* Stats Section */
        .stats-section {
            background: #F5F5F0;
            padding: 4rem 5%;
        }

        .stats-container {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 3rem;
            text-align: center;
        }

        .stat-item {
            padding: 1rem;
        }

        .stat-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .stat-icon.purple {
            color: #6366f1;
        }

        .stat-icon.green {
            color: #10b981;
        }

        .stat-icon.orange {
            color: #f59e0b;
        }

        .stat-icon.violet {
            color: #8b5cf6;
        }

        .stat-number {
            color: #003631;
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
            font-size: 1rem;
        }

        /* Features Section */
        .features-section {
            background: url('images/bg-rewards.png') center center / cover no-repeat, linear-gradient(135deg, #003631 100%, #004d45 100%);
            background-blend-mode: overlay;
            padding: 6rem 5%;
            position: relative;
            overflow: hidden;
        }

        .features-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 54, 49, 0.7);
            z-index: 0;
        }

        .features-section::after {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(241,178,74,0.1) 0%, transparent 70%);
            border-radius: 50%;
            bottom: 20%;
            left: 5%;
            animation: glow 3s ease-in-out infinite;
            z-index: 0;
        }

        @keyframes glow {
            0%, 100% { opacity: 0.5; transform: scale(1); }
            50% { opacity: 0.8; transform: scale(1.1); }
        }

        .features-container {
            max-width: 1400px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .features-header {
            text-align: center;
            margin-bottom: 4rem;
        }

        .features-title {
            color: #F1B24A;
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .features-subtitle {
            color: rgba(255,255,255,0.8);
            font-size: 1.1rem;
            max-width: 700px;
            margin: 0 auto;
            line-height: 1.6;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .features-grid-bottom {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
        }

        .feature-card {
            background: rgba(255,255,255,0.95);
            border-radius: 20px;
            padding: 2.5rem;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .feature-icon-box {
            width: 60px;
            height: 60px;
            background: #F1B24A;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
        }

        .feature-card-title {
            color: #003631;
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 1rem;
        }

        .feature-card-text {
            color: #666;
            line-height: 1.6;
            font-size: 0.95rem;
        }

        /* Decorative elements */
        .features-section .sparkle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(241,178,74,0.6);
            border-radius: 50%;
            animation: sparkle 2s ease-in-out infinite;
        }

        .sparkle:nth-child(1) { top: 15%; left: 10%; animation-delay: 0s; }
        .sparkle:nth-child(2) { top: 25%; left: 85%; animation-delay: 0.5s; }
        .sparkle:nth-child(3) { top: 60%; left: 15%; animation-delay: 1s; }
        .sparkle:nth-child(4) { top: 70%; left: 90%; animation-delay: 1.5s; }

        @keyframes sparkle {
            0%, 100% { opacity: 0; transform: scale(0); }
            50% { opacity: 1; transform: scale(1); }
        }

        /* Engagement Section */
        .engagement-section {
            background: #F5F5F0;
            padding: 5rem 5%;
        }

        .engagement-container {
            max-width: 900px;
            margin: 0 auto;
            background: linear-gradient(135deg, #f9f3e8 0%, #f5ebda 100%);
            border-radius: 25px;
            padding: 4rem 3rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }

        .engagement-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .engagement-icon {
            color: #F1B24A;
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .engagement-title {
            color: #003631;
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 1rem;
        }

        .engagement-description {
            color: #666;
            font-size: 1rem;
            line-height: 1.6;
        }

        .key-features-label {
            color: #003631;
            font-weight: bold;
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
        }

        .features-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-bottom: 2.5rem;
        }

        .feature-list-item {
            background: white;
            padding: 1.25rem 1.5rem;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .feature-list-item:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .feature-number {
            width: 32px;
            height: 32px;
            background: #F1B24A;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        .feature-list-text {
            color: #333;
            font-size: 0.95rem;
        }

        .engagement-note {
            background: rgba(241, 178, 74, 0.1);
            border-left: 4px solid #F1B24A;
            padding: 1.5rem;
            border-radius: 8px;
            color: #666;
            font-size: 0.9rem;
            line-height: 1.6;
        }

        /* Footer */
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
            background-color: #e0a03a;
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

            @media (max-width: 968px) {
        .hero-container {
            grid-template-columns: 1fr;
            padding: 4rem 0;
        }

        .hero-title {
            font-size: 2.5rem;
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
        }

        .dropdown-content a {
            margin: 0 1rem;
            font-size: 0.95rem;
        }

        .stats-container {
            grid-template-columns: repeat(2, 1fr);
            gap: 2rem;
        }

        .features-grid {
            grid-template-columns: 1fr;
        }

        .features-grid-bottom {
            grid-template-columns: 1fr;
        }

        .footer-content {
            grid-template-columns: 1fr 1fr;
        }
    }

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

        .hero-section {
            padding: 60px 5% 60px;
        }

        .hero-title {
            font-size: 2rem;
        }

        .content-hero {
            margin-top: 50px;
        }

        .hero-features {
            flex-direction: column;
            gap: 1rem;
        }

        .dashboard-card {
            padding: 2rem;
        }

        .balance-amount {
            font-size: 2rem;
        }

        .dashboard-grid {
            grid-template-columns: 1fr;
        }

        .stats-container {
            grid-template-columns: 1fr;
            gap: 2rem;
        }

        .features-title {
            font-size: 2rem;
        }

        .features-subtitle {
            font-size: 1rem;
        }

        .engagement-container {
            padding: 3rem 2rem;
        }

        .engagement-title {
            font-size: 1.5rem;
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

    @media (max-width: 480px) {
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

        .hero-title {
            font-size: 1.75rem;
        }

        .trust-badge {
            font-size: 0.8rem;
            padding: 0.4rem 1rem;
        }

        .balance-amount {
            font-size: 1.75rem;
        }

        .item-value {
            font-size: 1.1rem;
        }

        .stat-number {
            font-size: 2rem;
        }

        .stat-label {
            font-size: 0.9rem;
        }

        .features-title {
            font-size: 1.75rem;
        }

        .feature-card {
            padding: 2rem;
        }

        .feature-card-title {
            font-size: 1.1rem;
        }

        .engagement-container {
            padding: 2.5rem 1.5rem;
        }

        .engagement-title {
            font-size: 1.3rem;
        }

        .feature-list-item {
            padding: 1rem;
        }

        .feature-list-text {
            font-size: 0.85rem;
        }
    }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav>
        <div class="logo">
            <div class="logo-icon">
                <a href="viewingpage.php">
                    <img src="images/Logo.svg">
                </a>
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
                    <a href="cards/credit.php">Credit Cards</a>
                    <a href="cards/debit.php">Debit Cards</a>
                    <a href="cards/prepaid.php">Prepaid Cards</a>
                    <a href="cards/rewards.php">Card Rewards</a>
                </div>
            </div>

            <a href="#loans">Loans</a>
            <a href="about.php">About Us</a>
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
                    <a href="#" role="menuitem">Profile</a>
                    <a href="refer.php" role="menuitem">Refer to a friend</a>
                    <a href="cards/points.php" role="menuitem">Missions</a>
                    <a href="viewing.php" role="menuitem" onclick="showSignOutModal(event)">Sign Out</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-container">
            <!-- Left Content -->
            <div class="content-hero">
                <div class="trust-badge">
                    ⭐ Trusted by 100,000+ clients
                </div>
                
                <h1 class="hero-title">
                    WELCOME TO<br>
                    <span>EVERGREEN</span>
                </h1>
                
                <div class="hero-features">
                    <div class="feature-item">
                        <span class="feature-icon">✓</span>
                        <span>No Hidden Fees</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon">✓</span>
                        <span>24/7 Support</span>
                    </div>
                </div>
            </div>

            <!-- Right Dashboard Card -->
            <div class="dashboard-card">
                <!-- Total Balance -->
                <div class="balance-box">
                    <div class="balance-label">Total Balance</div>
                    <div class="balance-amount">₱127,459.00</div>
                    <div class="balance-change">+2.5% this month</div>
                </div>

                <!-- Savings and Rewards -->
                <div class="dashboard-grid">
                    <div class="dashboard-item">
                        <div class="item-label">Savings</div>
                        <div class="item-value">₱45,231</div>
                    </div>
                    <div class="dashboard-item">
                        <div class="item-label">Rewards</div>
                        <div class="item-value gold">2,450 pts</div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="activity-box">
                    <div class="activity-title">Recent Activity</div>
                    <div class="activity-row">
                        <span class="activity-text">Direct Deposit</span>
                        <span class="activity-amount">+₱2,250</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="stats-container">
            <!-- Stat 1 -->
            <div class="stat-item">
                <div class="stat-icon purple">👥</div>
                <div class="stat-number">100K+</div>
                <div class="stat-label">Active Clients</div>
            </div>
            
            <!-- Stat 2 -->
            <div class="stat-item">
                <div class="stat-icon green">💰</div>
                <div class="stat-number">$2.5B+</div>
                <div class="stat-label">Assets Under Management</div>
            </div>
            
            <!-- Stat 3 -->
            <div class="stat-item">
                <div class="stat-icon orange">🏆</div>
                <div class="stat-number">4.9/5</div>
                <div class="stat-label">Customer Rating</div>
            </div>
            
            <!-- Stat 4 -->
            <div class="stat-item">
                <div class="stat-icon violet">🛡️</div>
                <div class="stat-number">99.9%</div>
                <div class="stat-label">Uptime Guarantee</div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <span class="sparkle"></span>
        <span class="sparkle"></span>
        <span class="sparkle"></span>
        <span class="sparkle"></span>
        
        <div class="features-container">
            <div class="features-header">
                <h2 class="features-title">Everything You Need in One Place</h2>
                <p class="features-subtitle">Modern banking features designed to simplify your financial life and help you reach your goals faster</p>
            </div>

            <!-- Top Row - 2 Cards -->
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon-box">🔒</div>
                    <h3 class="feature-card-title">Bank-Level Security</h3>
                    <p class="feature-card-text">Your data is protected with 256-bit encryption and multi-factor authentication.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon-box">📈</div>
                    <h3 class="feature-card-title">High-Yield Savings</h3>
                    <p class="feature-card-text">Earn competitive interest rates on your savings with no minimum balance requirements or monthly fees.</p>
                </div>
            </div>

            <!-- Bottom Row - 3 Cards -->
            <div class="features-grid-bottom">
                <div class="feature-card">
                    <div class="feature-icon-box">💳</div>
                    <h3 class="feature-card-title">Rewards Program</h3>
                    <p class="feature-card-text">Earn points on every purchase and redeem for cash back, travel, or exclusive partner offers.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon-box">🔔</div>
                    <h3 class="feature-card-title">Smart Alerts</h3>
                    <p class="feature-card-text">Stay informed with personalized notifications about your account activity, bills, and special offers.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon-box">💬</div>
                    <h3 class="feature-card-title">24/7 Support</h3>
                    <p class="feature-card-text">Get help whenever you need it with our round-the-clock customer support via chat, phone, or email.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Client Engagement Section -->
    <section class="engagement-section">
        <div class="engagement-container">
            <div class="engagement-header">
                <div class="engagement-icon">🔔</div>
                <h2 class="engagement-title">Real-Time Client Engagement</h2>
                <p class="engagement-description">Connect with clients through personalized notifications and in-system messaging.</p>
            </div>

            <div class="key-features-label">Key Features:</div>

            <div class="features-list">
                <div class="feature-list-item">
                    <div class="feature-number">1</div>
                    <div class="feature-list-text">Personalized push notifications</div>
                </div>

                <div class="feature-list-item">
                    <div class="feature-number">2</div>
                    <div class="feature-list-text">In-app messaging system</div>
                </div>

                <div class="feature-list-item">
                    <div class="feature-number">3</div>
                    <div class="feature-list-text">Automated engagement workflows</div>
                </div>

                <div class="feature-list-item">
                    <div class="feature-number">4</div>
                    <div class="feature-list-text">Client preference management</div>
                </div>

                <div class="feature-list-item">
                    <div class="feature-number">5</div>
                    <div class="feature-list-text">Engagement analytics dashboard</div>
                </div>

                <div class="feature-list-item">
                    <div class="feature-number">6</div>
                    <div class="feature-list-text">Two-way communication channels</div>
                </div>
            </div>

            <div class="engagement-note">
                This subsystem is designed to enhance client engagement within a pilot environment, ensuring optimal performance and usability for your banking operations.
            </div>
        </div>
    </section>

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
                        </div>
                    <div class="social-icon">
                        <a href="https://www.instagram.com/evergreenbanking/">
                            <img src="images/trans-ig.png" alt="instagram" class="contact-icon">
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="footer-section">
                <h4>Products</h4>
                <ul>
                    <li><a href="#">Credit Cards</a></li>
                    <li><a href="#">Debit Cards</a></li>
                    <li><a href="#">Prepaid Cards</a></li>
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
                <a href="#">Privacy Policy</a>
                <a href="#">Terms and Agreements</a>
                <a href="#">FAQS</a>
                <a href="#">About Us</a>
            </div>
        </div>
    </footer>

    <script>
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

        // Profile dropdown toggle
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
                width: 60px;
                height: 60px;
                background: linear-gradient(135deg, #003631 0%, #1a6b62 100%);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 1.5rem;
                font-size: 2rem;
            ">⚠️</div>
            
            <h3 style="
                color: #003631;
                margin-bottom: 0.75rem;
                font-size: 1.75rem;
                font-weight: 600;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            ">Sign Out</h3>
            
            <p style="
                color: #666;
                margin-bottom: 2rem;
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