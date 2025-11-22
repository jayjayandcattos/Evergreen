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

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evergreen Bank - Where Your Future Stays Green</title>

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

        /* DROPDOWN STYLES - Replace the existing dropdown styles */
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


        /* Hero Section */
        .hero-section {
            margin-top: 80px;
            padding: 4rem 5%;
            background: #f5f5f5;
        }

        .hero-container {
            display: grid;
            grid-template-columns: 45% 55%;
            gap: 2rem;
            align-items: start;
            max-width: 1400px;
            margin: 0 auto;
        }

        .hero-left {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .hero-text h1 {
            font-size: 3.5rem;
            color: #003631;
            line-height: 1.2;
            border-left: 6px solid #F1B24A;
            padding-left: 1.5rem;
        }

        .hero-text h1 .green-text {
            color: #007A6C;
        }

        .hero-main-image {
            width: 100%;
            height: 600px;
            max-height: 150%;
            object-fit: cover;
            border-radius: 8px;
            border: 4px solid white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .hero-right {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            margin-top: 5%;
        }

        .hero-small-images {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .hero-small-image {
            width: 100%;
            max-height: 400px;
            object-fit: cover;
            border-radius: 8px;
            border: 3px solid white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .hero-right p {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #000000ff;
        }

        /* About Section */
        .about-section {
            padding: 4rem 5%;
            background: white;
        }

        .about-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .about-text p {
            font-size: 1rem;
            line-height: 1.8;
            color: #333;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 3rem;
            margin-top: 3rem;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 3.5rem;
            font-weight: bold;
            color: #003631;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 1rem;
            color: #666;
            font-weight: 600;
        }

        .badge-item {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .badge-icon {
            width: 80px;
            height: 80px;
            margin-bottom: 1rem;
        }

        .badge-icon img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .social-links {
            display: flex;
            justify-content: flex-end;
            gap: 1.5rem;
            margin-top: 2rem;
            padding-right: 2rem;
        }

        .social-links a {
            color: #003631;
            font-size: 1.3rem;
            text-decoration: none;
            transition: color 0.3s;
        }

        .social-links a:hover {
            color: #F1B24A;
        }

        .social-links img {
            width: 20px;
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

        /* Tablet and smaller desktop - UPDATED */
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

            .hero-container {
                grid-template-columns: 1fr;
            }

            .hero-text h1 {
                font-size: 2.5rem;
            }

            .hero-main-image {
                height: 400px;
            }

            .hero-right {
                margin-top: 2rem;
            }

            .stats-container {
                grid-template-columns: repeat(3, 1fr);
                gap: 2rem;
            }

            .footer-content {
                grid-template-columns: 1fr 1fr;
            }
        }

        /* Mobile landscape and smaller tablets */
        @media (max-width: 768px) {
            .hero-container {
                grid-template-columns: 1fr;
            }

            .hero-text h1 {
                font-size: 2.5rem;
            }

            .hero-small-images {
                grid-template-columns: 1fr;
            }

            .stats-container {
                grid-template-columns: 1fr;
                gap: 2rem;
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

            .hero-section {
                padding: 3rem 5%;
            }

            .hero-text h1 {
                font-size: 1.8rem;
                padding-left: 1rem;
                border-left-width: 4px;
            }

            .hero-main-image {
                height: 300px;
            }

            .hero-small-images {
                gap: 1rem;
            }

            .hero-small-image {
                max-height: 200px;
            }

            .hero-right p {
                font-size: 1rem;
            }

            .stats-container {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .stat-number {
                font-size: 2.5rem;
            }

            .badge-icon {
                width: 60px;
                height: 60px;
            }

            .about-section {
                padding: 3rem 5%;
            }

            .footer-content {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .footer-bottom {
                flex-direction: row;
                text-align: center;
            }

            .footer-links {
                flex-direction: row;
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

            .hero-section {
                padding: 2rem 3%;
                margin-top: 100px;
            }

            .hero-text h1 {
                font-size: 1.5rem;
            }

            .hero-main-image {
                height: 250px;
            }

            .hero-small-image {
                max-height: 150px;
            }

            .hero-right p {
                font-size: 0.95rem;
            }

            .stat-number {
                font-size: 2rem;
            }

            .stat-label {
                font-size: 0.9rem;
            }

            .social-links {
                gap: 1rem;
                padding-right: 0;
                justify-content: center;
            }

            .social-links a {
                font-size: 1.1rem;
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
                    <img src="images/Logo.png.png">
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

            <a href="../../LoanSubsystem/index.php">Loans</a>
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
                    <a href="../Basic-operation/operations/public/customer/account" role="menuitem">Profile</a>
                    <a href="cards/points.php" role="menuitem">Missions</a>
                    <a href="viewing.php" role="menuitem" onclick="showSignOutModal(event)">Sign Out</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-container">
            <div class="hero-left">
                <div class="hero-text">
                    <h1>Where Your Future <span class="green-text">Stays Green.</span></h1>
                </div>
                <img src="images/hero-main.png" alt="Business professionals meeting" class="hero-main-image">
            </div>
            <div class="hero-right">
                <div class="hero-small-images">
                    <img src="images/financial.png" alt="Financial growth chart" class="hero-small-image">
                    <img src="images/team.png" alt="Professional team" class="hero-small-image">
                </div>
                <p>Evergreen was established to provide secure and innovative financial solutions tailored to modern banking needs. Built with trust and technology at its core, Evergreen continues to help individuals and businesses grow through reliable, user-friendly digital banking services.</p>
                
                <!-- Stats in Right Column -->
                <div class="stats-container">
                    <div class="stat-item">
                        <div class="stat-number">10K+</div>
                        <div class="stat-label">Users all around<br>the world</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">8K+</div>
                        <div class="stat-label">Satisfied<br>Customer</div>
                    </div>
                    <div class="badge-item">
                        <div class="badge-icon">
                            <img src="images/bank-award.png" alt="Bank Award">
                        </div>
                        <div class="stat-label">Bank Award<br>2025</div>
                    </div>
                </div>

                <!-- Social Links in Right Column -->
                <div class="social-links">
                    <a href="https://www.facebook.com/profile.php?id=61582812214198">
                        <img src="images/fbicon.png" alt="facebook" class>
                    </a>
                    <a href="https://www.instagram.com/evergreenbanking/">
                        <img src="images/igicon.png" alt="instagram">
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-brand">
                <div class="logo">
                    <div class="logo-icon">
                        <img src="images/icon.png" alt="Evergreen Logo">
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
                    <li><a href="cards/credit.php">Credit Cards</a></li>
                    <li><a href="cards/debit.php">Debit Cards</a></li>
                    <li><a href="cards/prepaid.php">Prepaid Cards</a></li>
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
            "><img src="./images/warning.png"></div>
            
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