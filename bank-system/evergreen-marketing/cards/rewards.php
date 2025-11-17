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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cash Rewards - Evergreen</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
            background-color: #f5f5f0;
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

        /* DROPDOWN STYLES */
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

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #003631 0%, #002a26 100%);
            padding: 8rem 5% 4rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            align-items: center;
            min-height: 50vh;
        }

        .hero-content h1 {
            color: #F1B24A;
            font-size: 5rem;
            margin-bottom: 1.5rem;
        }

        .hero-content p {
            color: rgba(255,255,255,0.9);
            font-size: 1.05rem;
            line-height: 1.7;
            margin-bottom: 2rem;
            margin-top: 1.5rem;
            max-width: 500px;
        }

        .hero-apply {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn-apply {
            background: #F1B24A;
            color: #003631;
            padding: 0.9rem 2rem;
            border: none;
            border-radius: 25px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
        }

        .btn-apply:hover {
            background: #e0a03a;
            transform: translateY(-2px);
        }

        .hero-image {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .credit-card-display {
            width: 580px;
            height: 330px;
            background: linear-gradient(135deg, #2a2a2a 0%, #1a1a1a 100%);
            border-radius: 15px;
            position: relative;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
            transform: rotate(-5deg);
            animation: float 3s ease-in-out infinite;
            margin-left: 14%;
        }

        @keyframes float {
            0%, 100% { transform: rotate(-5deg) translateY(0); }
            50% { transform: rotate(-5deg) translateY(-10px); }
        }

        .card-chip {
            width: 45px;
            height: 35px;
            background: linear-gradient(135deg, #ffd700 0%, #F1B24A 100%);
            border-radius: 5px;
            position: absolute;
            left: 25px;
            top: 60px;
        }

        .card-logo {
            position: absolute;
            right: 25px;
            top: 25px;
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
        }

        .card-number {
            position: absolute;
            bottom: 50px;
            left: 25px;
            color: white;
            font-size: 1rem;
            letter-spacing: 3px;
        }

        .card-holder {
            position: absolute;
            bottom: 20px;
            left: 25px;
            color: rgba(255,255,255,0.8);
            font-size: 0.8rem;
        }

        /* Main Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 auto;
        }

        .points-section {
            background: linear-gradient(135deg, #003631 0%, #1a5f5f 100%);
            border-radius: 16px;
            padding: 40px;
            display: flex;
            gap: 40px;
            margin-bottom: 60px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-top: 40px;
        }

        .points-display {
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 10%;
        }

        .points-label {
            color: #d4af37;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 1px;
            margin-bottom: 20px;
        }

        .points-number {
            font-size: 72px;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 20px;
        }

        .points-description {
            color: #8fb3a3;
            font-size: 14px;
        }

        .missions-panel {
            flex: 2;
            background: #e8e4d9;
            border-radius: 12px;
            padding: 30px;
        }

        .tabs {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
            border-bottom: 2px solid #d4cbb8;
            padding-bottom: 10px;
            justify-content: space-between;
        }

        .tab {
            background: none;
            border: none;
            font-size: 15px;
            font-weight: 600;
            color: #666;
            cursor: pointer;
            padding: 8px 0;
            transition: color 0.3s;
        }

        .tab.active {
            color: #003631;
            border-bottom: 3px solid #003631;
            margin-bottom: -12px;
        }

        .view-all a{
            color: #d4af37;
            font-size: 18px;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            justify-content: end;
            align-items: end;
            margin-bottom: 10px;
        }

        /* Tab preview styles */
        .tab-preview {
            display: none;
        }

        .tab-preview.active {
            display: block;
        }

        /* Mission card without collect button */
        .mission-card-preview {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .mission-card-preview .mission-points {
            font-size: 28px;
            font-weight: 700;
            color: #003631;
            min-width: 60px;
        }

        .mission-card-preview .mission-points-label {
            font-size: 11px;
            color: #666;
            font-weight: 500;
        }

        .mission-card-preview .mission-details {
            flex: 1;
        }

        .mission-card-preview .mission-text {
            font-size: 14px;
            color: #333;
            line-height: 1.5;
        }

        .history-card, .completed-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            position: relative;
        }

        .history-timestamp, .completed-timestamp {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 11px;
            color: #999;
        }

        .view-all:hover {
            transform: translateY(-2px);
        }

        .mission-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .mission-points {
            font-size: 28px;
            font-weight: 700;
            color: #003631;
            min-width: 60px;
        }

        .mission-points-label {
            font-size: 11px;
            color: #666;
            font-weight: 500;
            text-align: center;
        }

        .mission-details {
            flex: 1;
        }

        .mission-text {
            font-size: 14px;
            color: #333;
            line-height: 1.5;
        }

        .redeem-section {
            margin-top: 40px;
            margin-bottom: 60px;
        }

        .section-title {
            font-size: 24px;
            font-weight: 700;
            color: #003631;
            margin-bottom: 30px;
        }

        .carousel-container {
            position: relative;
        }

        .carousel {
            display: flex;
            gap: 20px;
            overflow: hidden;
            padding: 20px 0;
        }

        .carousel-track {
            display: flex;
            gap: 20px;
            transition: transform 0.5s ease;
        }

        .reward-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            min-width: 300px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .reward-icon {
            width: 80px;
            height: 80px;
            background: #f0f0f0;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
        }

        .reward-title {
            font-size: 18px;
            font-weight: 700;
            color: #003631;
            margin-bottom: 15px;
        }

        .reward-description {
            font-size: 13px;
            color: #666;
            line-height: 1.6;
            margin-bottom: 25px;
        }

        .redeem-button {
            background: #003631;
            color: #F1B24A;
            border: none;
            padding: 12px 32px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }

        .redeem-button:hover {
            background: #1a5f5f;
        }

        .carousel-button {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: #003631;
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            z-index: 10;
            transition: background 0.3s;
        }

        .carousel-button:hover {
            background: #1a5f5f;
        }

        .carousel-button.prev {
            left: -20px;
        }

        .carousel-button.next {
            right: -20px;
        }

        .dots {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 20px;
        }

        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #d4cbb8;
            cursor: pointer;
            transition: background 0.3s;
        }

        .dot.active {
            background: #003631;
            width: 24px;
            border-radius: 4px;
        }

        /* Discount Section */
        .discounts-section {
            background: linear-gradient(135deg, #003631 0%, #1a5f5f 100%);
            border-radius: 16px;
            padding: 60px 40px;
            margin-top: 60px;
            margin-bottom: 60px;
        }

        .discounts-title {
            color: white;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 50px;
            text-align: left;
            border-bottom: 3px solid #d4af37;
            display: inline-block;
            padding-bottom: 10px;
        }

        .discounts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 40px;
            max-width: 900px;
            margin: 0 auto 40px;
        }

        .discount-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            transition: transform 0.3s;
        }

        .discount-card:hover {
            transform: translateY(-5px);
        }

        .discount-header {
            background: #f5a623;
            padding: 30px 20px 20px;
            position: relative;
        }

        .zigzag {
            position: absolute;
            bottom: -10px;
            left: 0;
            right: 0;
            height: 20px;
            background: linear-gradient(135deg, #f5a623 25%, transparent 25%) -10px 0,
                        linear-gradient(225deg, #f5a623 25%, transparent 25%) -10px 0,
                        linear-gradient(315deg, #f5a623 25%, transparent 25%),
                        linear-gradient(45deg, #f5a623 25%, transparent 25%);
            background-size: 40px 40px;
            background-color: white;
        }

        .discount-percentage {
            font-size: 56px;
            font-weight: 700;
            color: #003631;
            text-align: center;
            line-height: 1;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .discount-body {
            padding: 40px 30px 30px;
            text-align: center;
        }

        .discount-label {
            font-size: 14px;
            font-weight: 700;
            color: #003631;
            letter-spacing: 1px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e0e0e0;
        }

        .discount-description {
            font-size: 14px;
            color: #666;
            line-height: 1.6;
            margin-bottom: 30px;
            min-height: 60px;
        }

        .discount-redeem-button {
            background: #003631;
            color: #F1B24A;
            border: none;
            padding: 12px 40px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .discount-redeem-button:hover {
            background: #1a5f5f;
            transform: scale(1.05);
        }

        .discounts-footer {
            text-align: center;
            color: white;
            font-size: 16px;
            margin-top: 20px;
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
        }

        .contact-icon {
            width: 15px;
        }

        .social-icon:hover {
            background: #F1B24A;
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

        /* Responsive - UPDATED */
        @media (max-width: 968px) {
        .hero {
        grid-template-columns: 1fr;
        padding: 6rem 5% 3rem;
        }

        .hero-content h1 {
            font-size: 2.2rem;
        }

        .credit-card-display {
            width: 240px;
            height: 150px;
            margin-left: 25%;
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

        .points-section {
            flex-direction: column;
            padding: 30px;
        }

        .carousel-button {
            display: none;
        }

        .carousel {
            overflow-x: auto;
            scroll-snap-type: x mandatory;
        }

        .reward-card {
            scroll-snap-align: start;
        }

        .reward-card:hover {
            transform: translateY(-10px);
            transition: 0.2s ease-in;
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

        .hero-content {
            margin-top: 50px;
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

    .hero-content h1 {
        font-size: 1.8rem;
    }

    .hero-content p {
        font-size: 0.95rem;
    }

    .credit-card-display {
        width: 100%;
        max-width: 350px;
        height: 220px;
        margin-left: 0;
    }

    .points-number {
        font-size: 56px;
    }

    .tabs {
        flex-wrap: wrap;
        gap: 10px;
        justify-content: center;
    }

    .tab {
        font-size: 0.9rem;
    }

    .view-all {
        float: none;
        display: block;
        margin-top: 10px;
        text-align: center;
    }

    .view-all a {
        justify-content: center;
    }

    .mission-card,
    .mission-card-preview,
    .history-card,
    .completed-card {
        flex-direction: column;
        text-align: center;
    }

    .discounts-section {
        padding: 40px 20px;
    }

    .discounts-title {
        font-size: 24px;
    }

    .discounts-grid {
        grid-template-columns: 1fr;
        gap: 30px;
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

    .hero-content h1 {
        font-size: 1.75rem;
    }

    .btn-apply {
        padding: 0.7rem 1.5rem;
        font-size: 0.9rem;
    }

    .hero-apply {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }

    .points-section {
        padding: 20px;
    }

    .points-number {
        font-size: 48px;
    }

    .missions-panel {
        padding: 20px;
    }

    .section-title {
        font-size: 20px;
    }

    .reward-card {
        min-width: 250px;
        padding: 20px;
    }

    .discount-percentage {
        font-size: 48px;
    }

    .discount-body {
        padding: 30px 20px 20px;
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
                    <img src="../images/Logo.png.png">
                </a>
            </div>
            <span>
                <a href="../viewingpage.php">EVERGREEN</a>
            </span>
        </div>

        <div class="nav-links">
            <a href="../viewingpage.php">Home</a>

            <div class="dropdown">
                <button class="dropbtn" onclick="toggleDropdown()">Cards ⏷</button>
                <div class="dropdown-content" id="cardsDropdown">
                    <a href="../cards/credit.php">Credit Cards</a>
                    <a href="../cards/debit.php">Debit Cards</a>
                    <a href="../cards/prepaid.php">Prepaid Cards</a>
                    <a href="../cards/rewards.php">Card Rewards</a>
                </div>
            </div>

            <a href="../../../LoanSubsystem/index.php">Loans</a>
            <a href="../about.php">About Us</a>
        </div>

        <div class="nav-buttons">
            <a href="#" class="username-profile"><?php echo htmlspecialchars($fullName); ?></a>

            <div class="profile-actions">
                <div class="logo-icon" style="width:40px;height:40px;">
                    <button id="profileBtn" class="profile-btn" aria-haspopup="true" aria-expanded="false" onclick="toggleProfileDropdown(event)" title="Open profile menu">
                        <img src="../images/pfp.png" alt="Profile Icon">
                    </button>
                </div>

                <div id="profileDropdown" class="profile-dropdown" role="menu" aria-labelledby="profileBtn">
                    <a href="../../Basic-operation/operations/public/customer/account" role="menuitem">Profile</a>
                    <a href="../../Basic-operation/operations/public/customer/referral" role="menuitem">Refer to a friend</a>
                    <a href="../cards/points.php" role="menuitem">Missions</a>
                    <a href="viewing.php" role="menuitem" onclick="showSignOutModal(event)">Sign Out</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Cash Rewards</h1>
            <p>Earn points, cashback, and exclusive perks every time you
             use your EVERGREEN Card — making every purchase more
              rewarding.</p>
            
            <div class="hero-apply">
                <p>Need another card?</p>
                <a href="../evergreen_form.php" class="btn-apply">Apply Now</a>
            </div>
        </div>
        <div class="hero-image">
            <div class="credit-card-display">
                <div class="card-chip"></div>
                <div class="card-logo">VISA</div>
                <div class="card-number">•••• •••• •••• 4589</div>
                <div class="card-holder">CARDHOLDER NAME</div>
            </div>
        </div>
    </section>

    <div class="container">
        <div class="points-section">
            <div class="points-display">
                <div class="points-label">EVERGREEN POINTS</div>
                <div class="points-number" id="totalPoints">0.00</div>
                <div class="points-description">Collect more points to<br>enjoy exciting rewards!</div>
            </div>
            <div class="missions-panel">
    <div class="tabs">
        <button class="tab active" onclick="switchPreviewTab('mission-preview')">Mission</button>
        <button class="tab" onclick="switchPreviewTab('history-preview')">Point History</button>
        <button class="tab" onclick="switchPreviewTab('completed-preview')">Completed</button>
    </div>
    <div class="view-all">
        <a href="../cards/points.php">View All →</a>
    </div>


    <div id="mission-preview" class="tab-preview active">
        <p style="text-align:center;padding:20px;color:#666;">Loading missions...</p>
    </div>
    <div id="history-preview" class="tab-preview">
        <p style="text-align:center;padding:20px;color:#666;">Loading history...</p>
    </div>
    <div id="completed-preview" class="tab-preview">
        <p style="text-align:center;padding:20px;color:#666;">Loading completed...</p>
    </div>
</div>
        </div>

        <div class="redeem-section">
            <h2 class="section-title">Redeem Rewards</h2>
            <div class="carousel-container">
                <button class="carousel-button prev" onclick="moveCarousel(-1)">‹</button>
                <div class="carousel">
                    <div class="carousel-track" id="carouselTrack">
                        <!-- Display the rewards here dynamically -->
                    </div>
                </div>
                <button class="carousel-button next" onclick="moveCarousel(1)">›</button>
            </div>
            <div class="dots" id="dots"></div>
        </div>

        <!-- Discount Section -->
        <div class="discounts-section">
            <h2 class="discounts-title">Redeem Discounts</h2>
            <div class="discounts-grid">
                <div class="discount-card">
                    <div class="discount-header">
                        <div class="discount-percentage">
                            <h4>50%</h4>
                        </div>
                        <div class="zigzag"></div>
                    </div>
                    <div class="discount-body">
                        <div class="discount-label">DISCOUNT</div>
                        <div class="discount-description">Enjoy 50% savings on your next getaway when you book with your EVERGREEN Card.</div>
                        <button class="discount-redeem-button">Redeem</button>
                    </div>
                </div>

                <div class="discount-card">
                    <div class="discount-header">
                        <div class="discount-percentage">
                            <h4>50%</h4>
                        </div>
                        <div class="zigzag"></div>
                    </div>
                    <div class="discount-body">
                        <div class="discount-label">DISCOUNT</div>
                        <div class="discount-description">Enjoy 20% off on your favorite meals when you dine with your EVERGREEN Card.</div>
                        <button class="discount-redeem-button">Redeem</button>
                    </div>
                </div>
            </div>
            <div class="discounts-footer">
                Discover more options designed to give you flexibility and rewards.
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-brand">
                <div class="logo">
                    <div class="logo-icon">
                        <img src="../images/icon.png" alt="Evergreen Logo">
                    </div>
                </div>
                <p>Secure. Invest. Achieve. Your trusted financial partner for a prosperous future.</p>
                <div class="social-icons">
                    <div class="social-icon">
                        <a href="https://www.facebook.com/profile.php?id=61582812214198">
                            <img src="../images/fb-trans.png" alt="facebook" class="contact-icon">
                        </a>
                    </div>
                    <div class="social-icon">
                        <a href="https://www.instagram.com/evergreenbanking/">
                            <img src="../images/trans-ig.png" alt="instagram" class="contact-icon">
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="footer-section">
                <h4>Products</h4>
                <ul>
                    <li><a href="../cards/credit.php">Credit Cards</a></li>
                    <li><a href="../cards/debit.php">Debit Cards</a></li>
                    <li><a href="../cards/prepaid.php">Prepaid Cards</a></li>
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
                <div class="contact-item">✉️ hello@evergreenbank.com</div>
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

<!-- Modal redeem notif -->
 <div class="modal-container" style="display: none">
    <div class="success-notif">
        <h1 class="check-symbol">✔</h1>
        <h2 class="success-txt">Claimed</h2>
        <h2 class="txtrewards">Rewards</h2>
        <button class="ok">Okay</button>
    </div>
 </div>

 <style>
    /* Modal Container */
    .modal-container {
        background-color: rgba(0, 0, 0, 0.4);
        position: fixed;
        left: 0;
        top: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        width: 100%;
        height: 100vh;
    }


    .success-notif {
        background-color: white;
        padding: 20px;
        border-radius: 15px;
        display: flex;
        flex-direction: column;
        text-align: center;
        justify-content: center;
        align-items: center;
        gap: 10px;
        width: 30%;
        animation: slideUp 0.3s ease-out forwards;
    }

    /* Animation */
    @keyframes slideUp {
        from {
            transform: translateY(10px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
        }

    .check-symbol {
        font-size: 50px;
        background-color: #003631;
        color:white;
        border-radius: 60px;
        padding: 15px;
        width: 100px;
    }

    .ok {
        background-color: #003631;
        color: #d4af37;
        border: none;
        padding: 10px;
        border-radius: 15px;
        width: 30%;
        font-weight: 600;
    }
 </style>

    <script src="../js/points_system.js"></script>
<script>
    // Set correct API path
    pointsSystem.apiUrl = '../points_api.php';

    // Dropdown Toggle
    function toggleDropdown() {
        const dropdown = document.getElementById('cardsDropdown');
        dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    }

    // Close dropdown when clicking outside
    window.onclick = function(event) {
        if (!event.target.matches('.dropbtn')) {
            const dropdown = document.getElementById('cardsDropdown');
            if (dropdown.style.display === 'block') {
                dropdown.style.display = 'none';
            }
        }
    }

    // Carousel functionality
    let currentSlide = 0;
    let totalSlides = 0;
    let track; // defined later
    let dotsContainer;

    // Wait until the DOM is ready before building the carousel
    document.addEventListener('DOMContentLoaded', async function() {
        track = document.getElementById('carouselTrack');
        dotsContainer = document.getElementById('dots');

        redeemDisplay();
        updateCarousel(); // make sure initial state shows correctly

        await pointsSystem.loadUserPoints();
        await loadPreviewMissions();
    });

    function moveCarousel(direction) {
        currentSlide += direction;
        if (currentSlide < 0) currentSlide = 0;
        if (currentSlide > totalSlides) currentSlide = totalSlides;
        updateCarousel();
    }

    function goToSlide(index) {
        currentSlide = index;
        updateCarousel();
    }

    function updateCarousel() {
        const offset = currentSlide * -320;
        track.style.transform = `translateX(${offset}px)`;
        
        const dots = document.querySelectorAll('.dot');
        dots.forEach((dot, index) => {
            dot.classList.toggle('active', index === currentSlide);
        });
    }

    // Tab switching for preview
    function switchPreviewTab(tabName) {
        const tabs = document.querySelectorAll('.missions-panel .tab');
        tabs.forEach(t => t.classList.remove('active'));
        event.target.classList.add('active');

        const previews = document.querySelectorAll('.tab-preview');
        previews.forEach(p => p.classList.remove('active'));
        document.getElementById(tabName).classList.add('active');

        // Load data based on tab
        if (tabName === 'mission-preview') {
            loadPreviewMissions();
        } else if (tabName === 'history-preview') {
            loadPreviewHistory();
        } else if (tabName === 'completed-preview') {
            loadPreviewCompleted();
        }
    }

    // Load preview missions (read-only, no collect button)
    async function loadPreviewMissions() {
        const missions = await pointsSystem.loadMissions();
        const container = document.getElementById('mission-preview');
        
        container.innerHTML = '';
        
        if (missions.length === 0) {
            container.innerHTML = '<p style="text-align:center;padding:20px;color:#666;">All missions completed! 🎉</p>';
        } else {
            missions.slice(0, 2).forEach(mission => {
                const card = document.createElement('div');
                card.className = 'mission-card-preview';
                card.innerHTML = `
                    <div>
                        <div class="mission-points">${parseFloat(mission.points_value).toFixed(2)}</div>
                        <div class="mission-points-label">points</div>
                    </div>
                    <div class="mission-details">
                        <div class="mission-text">${mission.mission_text}</div>
                    </div>
                `;
                container.appendChild(card);
            });
        }
    }

    // Load preview history
    async function loadPreviewHistory() {
        const history = await pointsSystem.loadPointHistory();
        const container = document.getElementById('history-preview');
        
        container.innerHTML = '';
        
        if (history.length === 0) {
            container.innerHTML = '<p style="text-align:center;padding:20px;color:#666;">No history yet</p>';
        } else {
            history.slice(0, 2).forEach(item => {
                const card = document.createElement('div');
                card.className = 'history-card';
                card.innerHTML = `
                    <div class="history-timestamp">${item.timestamp}</div>
                    <div>
                        <div class="mission-points">${item.points}</div>
                        <div class="mission-points-label">points</div>
                    </div>
                    <div class="mission-details">
                        <div class="mission-text">${item.description}</div>
                    </div>
                `;
                container.appendChild(card);
            });
        }
    }

    // Load preview completed
    async function loadPreviewCompleted() {
        const completed = await pointsSystem.loadCompletedMissions();
        const container = document.getElementById('completed-preview');
        
        container.innerHTML = '';
        
        if (completed.length === 0) {
            container.innerHTML = '<p style="text-align:center;padding:20px;color:#666;">No completed missions yet</p>';
        } else {
            completed.slice(0, 2).forEach(item => {
                const card = document.createElement('div');
                card.className = 'completed-card';
                card.innerHTML = `
                    <div class="completed-timestamp">${item.timestamp}</div>
                    <div>
                        <div class="mission-points">${item.points}</div>
                        <div class="mission-points-label">points</div>
                    </div>
                    <div class="mission-details">
                        <div class="mission-text">${item.description}</div>
                    </div>
                `;
                container.appendChild(card);
            });
        }
    }

    // Load data on page load
    document.addEventListener('DOMContentLoaded', async function() {
        await pointsSystem.loadUserPoints();
        await loadPreviewMissions();
    });

    // Profile dropdown toggle
    function toggleProfileDropdown(e) {
        e.stopPropagation();
        const dd = document.getElementById('profileDropdown');
        const btn = document.getElementById('profileBtn');
        const isOpen = dd.classList.toggle('show');
        btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    }

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

    // redeem reward function
    function redeemDisplay() {

        const track = document.getElementById('carouselTrack');
        const dotsContainer = document.getElementById('dots');
        track.innerHTML = ''; // clear old content
        dotsContainer.innerHTML = '';

        // Mock Rewards
        let redeemRewards = [
            {icon: "🏠", title: "Home-credit discount", desc: "Get 50% off, through home-credit"},
            {icon: "🎁", title: "Gift Voucher", desc: "Redeem ₱500 worth of gift vouchers at selected stores"},
            {icon: "🍔", title: "Food Treat", desc: "Enjoy a free meal at Jollibee or McDonald's"},
            {icon: "🚗", title: "Fuel Discount", desc: "Save ₱5 per liter at participating gas stations"},
            {icon: "📱", title: "Mobile Load Bonus", desc: "Receive ₱100 free load for any network"},
            {icon: "🎬", title: "Movie Pass", desc: "Get two free cinema tickets for any movie"},
            {icon: "🛍️", title: "Shopping Cashback", desc: "Earn 10% cashback on your next online purchase"},
            {icon: "💳", title: "Card Upgrade", desc: "Upgrade your membership card for premium perks"},
            {icon: "🎡", title: "Theme Park Access", desc: "Free entry to Enchanted Kingdom for one day"},
            {icon: "🧃", title: "Drink Reward", desc: "Free Starbucks or Coffee Bean drink of your choice"}
        ];

    // redeem rewards display 
    for(let i = 0; i < redeemRewards.length; i++) {
        // main container
        let rewardCard = document.createElement("div");
        rewardCard.className = "reward-card";

        let rewardIcon = document.createElement("div");
        rewardIcon.className = "reward-icon";
        rewardIcon.textContent = redeemRewards[i].icon;

        let rewardTitle = document.createElement("div");
        rewardTitle.className = "reward-title";
        rewardTitle.textContent = redeemRewards[i].title;

        let rewardDesc = document.createElement("div");
        rewardDesc.className = "reward-description";
        rewardDesc.textContent = redeemRewards[i].desc;

        let redeemBtn = document.createElement("button");
        redeemBtn.className = "redeem-button";
        redeemBtn.textContent = "Redeem";

        // appending
        rewardCard.appendChild(rewardIcon);
        rewardCard.appendChild(rewardTitle);
        rewardCard.appendChild(rewardDesc);
        rewardCard.appendChild(redeemBtn);

        track.appendChild(rewardCard);

        // modal Claimed
        redeemBtn.addEventListener("click", function() {
            let modalCont = document.querySelector(".modal-container");
            let successTxt = document.querySelector(".success-txt");
            let txt = document.querySelector(".txtrewards");
            modalCont.style.display = "flex";

            successTxt.textContent = `Redeemed`;
            txt.textContent = redeemRewards[i].title;

            document.querySelector(".ok").addEventListener("click", function() {
                modalCont.style.display = "none";
                rewardCard.remove();
            }, {once : true});

        })
    }

    const cards = document.querySelectorAll('.reward-card');
    totalSlides = cards.length - 1;

    // Create dots
    for (let i = 0; i <= totalSlides; i++) {
        const dot = document.createElement('div');
        dot.className = 'dot';
        if (i === 0) dot.classList.add('active');
        dot.onclick = () => goToSlide(i);
        dotsContainer.appendChild(dot);
    }

    }

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
            "><img src="../images/warning.png"></div>
            
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
    confirmBtn.onclick = () => window.location.href = '../logout.php';
    
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

// Add this to your rewards.php script section
function redeemDisplay() {
    const track = document.getElementById('carouselTrack');
    const dotsContainer = document.getElementById('dots');
    track.innerHTML = '';
    dotsContainer.innerHTML = '';

    // Rewards with point costs
    let redeemRewards = [
        {icon: "🏠", title: "Home-credit discount", desc: "Get 50% off through home-credit", points: 500},
        {icon: "🎁", title: "Gift Voucher", desc: "Redeem ₱500 worth of gift vouchers", points: 300},
        {icon: "🍔", title: "Food Treat", desc: "Free meal at Jollibee or McDonald's", points: 200},
        {icon: "🚗", title: "Fuel Discount", desc: "Save ₱5 per liter at gas stations", points: 150},
        {icon: "📱", title: "Mobile Load Bonus", desc: "₱100 free load for any network", points: 100},
        {icon: "🎬", title: "Movie Pass", desc: "Two free cinema tickets", points: 250},
        {icon: "🛍️", title: "Shopping Cashback", desc: "10% cashback on next purchase", points: 180},
        {icon: "💳", title: "Card Upgrade", desc: "Premium membership upgrade", points: 400},
        {icon: "🎡", title: "Theme Park Access", desc: "Free Enchanted Kingdom entry", points: 350},
        {icon: "🧃", title: "Drink Reward", desc: "Free Starbucks or Coffee Bean drink", points: 120}
    ];

    // Create reward cards
    for(let i = 0; i < redeemRewards.length; i++) {
        let rewardCard = document.createElement("div");
        rewardCard.className = "reward-card";

        let rewardIcon = document.createElement("div");
        rewardIcon.className = "reward-icon";
        rewardIcon.textContent = redeemRewards[i].icon;

        let rewardTitle = document.createElement("div");
        rewardTitle.className = "reward-title";
        rewardTitle.textContent = redeemRewards[i].title;

        let rewardDesc = document.createElement("div");
        rewardDesc.className = "reward-description";
        rewardDesc.textContent = redeemRewards[i].desc;

        // Add points cost display
        let pointsCost = document.createElement("div");
        pointsCost.style.cssText = "font-size: 18px; font-weight: 700; color: #003631; margin: 10px 0;";
        pointsCost.textContent = `${redeemRewards[i].points} Points`;

        let redeemBtn = document.createElement("button");
        redeemBtn.className = "redeem-button";
        redeemBtn.textContent = "Redeem";

        // Add click handler with points system
        redeemBtn.addEventListener("click", async function() {
            const success = await pointsSystem.redeemReward(
                redeemRewards[i].title,
                redeemRewards[i].points
            );
            
            if (success) {
                // Remove the card after successful redemption
                setTimeout(() => {
                    rewardCard.style.transition = 'all 0.5s ease';
                    rewardCard.style.opacity = '0';
                    rewardCard.style.transform = 'scale(0.8)';
                    setTimeout(() => rewardCard.remove(), 500);
                }, 2000);
            }
        });

        rewardCard.appendChild(rewardIcon);
        rewardCard.appendChild(rewardTitle);
        rewardCard.appendChild(rewardDesc);
        rewardCard.appendChild(pointsCost);
        rewardCard.appendChild(redeemBtn);

        track.appendChild(rewardCard);
    }

    const cards = document.querySelectorAll('.reward-card');
    totalSlides = cards.length - 1;

    // Create dots
    for (let i = 0; i <= totalSlides; i++) {
        const dot = document.createElement('div');
        dot.className = 'dot';
        if (i === 0) dot.classList.add('active');
        dot.onclick = () => goToSlide(i);
        dotsContainer.appendChild(dot);
    }
}
</script>
</body>
</html>