<?php
    session_start([
       'cookie_httponly' => true,
       'cookie_secure' => isset($_SERVER['HTTPS']),
       'use_strict_mode' => true
    ]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Card Rewards</title>

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

        .nav-buttons {
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.7rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-login {
            background: transparent;
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
        }

        .btn-login:hover {
            background: rgba(255,255,255,0.1);
        }

        .btn-primary {
            background: #f5a623;
            color: #0d4d4d;
            font-weight: bold;
            display: flex;
            align-items: center;
        }

        .btn-primary:hover {
            background: #e69610;
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
            position: relative;
            overflow: hidden;
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
            max-width: 500px;
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
        }

        .btn-apply:hover {
            background: #e0a03a;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(241,178,74,0.3);
        }

        .hero-image {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .card-hand {
            position: relative;
            width: 100%;
            max-width: 450px;
        }

        .credit-card-display {
            width: 530px;
            height: 330px;
            background: linear-gradient(135deg, #2a2a2a 0%, #1a1a1a 100%);
            border-radius: 15px;
            position: relative;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
            transform: rotate(-5deg);
            animation: float 3s ease-in-out infinite;
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

        /* Products Section */
        .products-section {
            background: #F5F5F0;
            padding: 4rem 5%;
        }

        .products-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .products-intro {
            background: linear-gradient(135deg, #003631 0%, #004d45 100%);
            border-radius: 20px;
            padding: 3rem;
            text-align: center;
            margin-bottom: 3rem;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            height: 50vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .products-intro h2 {
            color: white;
            font-size: 2rem;
            margin-bottom: 1rem;
            max-width: 800px;
        }

        .products-intro p {
            color: rgba(255,255,255,0.85);
            font-size: 1rem;
            line-height: 1.6;
            max-width: 600px;
        }

        .products-cards-section {
            background: linear-gradient(135deg, #00524a 0%, #006b5f 100%);
            border-radius: 20px;
            padding: 3rem;
            margin-bottom: 2rem;
        }

        .products-cards-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .products-cards-header h2 {
            color: white;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .products-cards-header p {
            color: rgba(255,255,255,0.85);
            font-size: 1rem;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .product-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
        }

        .product-card-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .product-icon {
            width: 50px;
            height: 50px;
            background: #F1B24A;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .product-card-title-section h3 {
            color: white;
            font-size: 1.2rem;
            margin-bottom: 0.25rem;
        }

        .product-badge {
            display: inline-block;
            background: rgba(241, 178, 74, 0.2);
            color: #F1B24A;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .product-card p {
            color: rgba(255,255,255,0.8);
            font-size: 0.9rem;
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .product-features {
            list-style: none;
        }

        .product-features li {
            color: rgba(255,255,255,0.85);
            font-size: 0.85rem;
            padding-left: 1.5rem;
            margin-bottom: 0.5rem;
            position: relative;
        }

        .product-features li:before {
            content: '•';
            color: #F1B24A;
            font-weight: bold;
            position: absolute;
            left: 0;
        }

        .cta-section {
            background: rgba(241, 178, 74, 0.1);
            border-radius: 15px;
            padding: 2.5rem;
            text-align: center;
            height: 30vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .cta-section h3 {
            color: white;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .cta-section p {
            color: rgba(255,255,255,0.8);
            font-size: 0.95rem;
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
                position: fixed;
                left: 0;
                top: 80px;
                width: 100vw;
                padding: 1.2rem 3%;
                transform: none;
            }

            .dropdown-content a {
                margin: 0 1rem;
                font-size: 0.95rem;
            }

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

            .products-grid {
                grid-template-columns: 1fr;
            }

            .footer-content {
                grid-template-columns: 1fr 1fr;
            }
        }

        /* Mobile landscape and smaller tablets */
        @media (max-width: 768px) {
            .hero {
                grid-template-columns: 1fr;
            }

            .hero-content h1 {
                font-size: 2.5rem;
            }

            .products-grid {
                grid-template-columns: 1fr;
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

            .hero {
                padding: 3rem 5%;
                margin-top: 15%;
            }

            .hero-content h1 {
                font-size: 1.8rem;
            }

            .hero-content p {
                font-size: 1rem;
            }

            .credit-card-display {
                width: 200px;
                height: 125px;
            }

            .products-section {
                padding: 3rem 5%;
            }

            .products-intro {
                padding: 2rem;
                height: auto;
                min-height: 30vh;
            }

            .products-intro h2 {
                font-size: 1.5rem;
            }

            .products-cards-section {
                padding: 2rem;
            }

            .products-cards-header h2 {
                font-size: 1.5rem;
            }

            .products-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .cta-section {
                padding: 2rem;
                height: auto;
                min-height: 20vh;
            }

            .cta-section h3 {
                font-size: 1.2rem;
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

            .hero {
                padding: 2rem 3%;
                margin-top: 100px;
            }

            .hero-content h1 {
                font-size: 1.5rem;
            }

            .hero-content p {
                font-size: 0.95rem;
            }

            .credit-card-display {
                width: 180px;
                height: 112px;
            }

            .card-chip {
                width: 30px;
                height: 23px;
                left: 15px;
                top: 40px;
            }

            .card-logo {
                right: 15px;
                top: 15px;
                font-size: 0.8rem;
            }

            .card-number {
                bottom: 35px;
                left: 15px;
                font-size: 0.7rem;
                letter-spacing: 2px;
            }

            .card-holder {
                bottom: 15px;
                left: 15px;
                font-size: 0.6rem;
            }

            .products-intro {
                padding: 1.5rem;
            }

            .products-intro h2 {
                font-size: 1.3rem;
            }

            .products-intro p {
                font-size: 0.9rem;
            }

            .products-cards-section {
                padding: 1.5rem;
            }

            .products-cards-header h2 {
                font-size: 1.3rem;
            }

            .products-cards-header p {
                font-size: 0.9rem;
            }

            .product-card {
                padding: 1.5rem;
            }

            .product-icon {
                width: 40px;
                height: 40px;
                font-size: 1.2rem;
            }

            .product-card-title-section h3 {
                font-size: 1rem;
            }

            .product-badge {
                font-size: 0.7rem;
                padding: 0.2rem 0.6rem;
            }

            .cta-section {
                padding: 1.5rem;
            }

            .cta-section h3 {
                font-size: 1.1rem;
            }

            .cta-section p {
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
                <a href="viewing.php">
                    <img src="images/Logo.png.png">
                </a>
            </div>
                <span>
                    <a href="viewing.php">
                    EVERGREEN
                    </a>
                </span>
        </div>
        <div class="nav-buttons">
            <a href="login.php" class="btn btn-login">Login

            </a>
                
            <button class="btn btn-primary">Get Started</button>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Points Details</h1>
            <p>Earn points, cashback, and exclusive perks every time you use your EVERGREEN Card — making every purchase more rewarding.</p>
            <button class="btn-apply">Apply Now</button>
        </div>
        <div class="hero-image">
            <div class="card-hand">
                <div class="credit-card-display">
                    <div class="card-chip"></div>
                    <div class="card-logo">VISA</div>
                    <div class="card-number">•••• •••• •••• 4589</div>
                    <div class="card-holder">CARDHOLDER NAME</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Products Section -->
    <section class="products-section">
        <div class="products-container">
            <!-- Intro Card -->
            <div class="products-intro">
                <h2>Financial Products That Reward You</h2>
                <p>From loans to lifestyle perks, we offer comprehensive financial solutions designed to support your goals</p>
            </div>

            <!-- Products Cards Section -->
            <div class="products-cards-section">
                <div class="products-cards-header">
                    <h2>Financial Products That Reward You</h2>
                    <p>From loans to lifestyle perks, we offer comprehensive financial solutions designed to support your goals</p>
                </div>

                <!-- Products Grid -->
                <div class="products-grid">
                    <!-- Card 1: Tap Your Card -->
                    <div class="product-card">
                        <div class="product-card-header">
                            <div class="product-icon">💳</div>
                            <div class="product-card-title-section">
                                <h3>Tap Your Card for Points</h3>
                                <span class="product-badge">Earn Daily | Max 5,000 pts</span>
                            </div>
                        </div>
                        <p>Freeze your card via self-competitive rates and flexible terms, but pre-approved in minutes.</p>
                        <ul class="product-features">
                            <li>No application fee</li>
                            <li>Same-day approval</li>
                            <li>Up to 24 months</li>
                        </ul>
                    </div>

                    <!-- Card 2: New Home -->
                    <div class="product-card">
                        <div class="product-card-header">
                            <div class="product-icon">🏠</div>
                            <div class="product-card-title-section">
                                <h3>New Home with Cashback</h3>
                                <span class="product-badge">Loan Points | Save ₱20k+ P/M</span>
                            </div>
                        </div>
                        <p>Make homeownership a reality with our mortgage solutions. First-time buyer programs available.</p>
                        <ul class="product-features">
                            <li>Low down payment options</li>
                            <li>Loan guidance</li>
                            <li>Fast closing</li>
                        </ul>
                    </div>

                    <!-- Card 3: Student Tuition -->
                    <div class="product-card">
                        <div class="product-card-header">
                            <div class="product-icon">🎓</div>
                            <div class="product-card-title-section">
                                <h3>Student Tuition</h3>
                                <span class="product-badge">School Points | Save from ₱400 P/M</span>
                            </div>
                        </div>
                        <p>Invest in your education with flexible student loan options and competitive interest rates.</p>
                        <ul class="product-features">
                            <li>Deferred payments</li>
                            <li>No origination fees</li>
                            <li>Cosigner release</li>
                        </ul>
                    </div>

                    <!-- Card 4: Lifestyle Perks -->
                    <div class="product-card">
                        <div class="product-card-header">
                            <div class="product-icon">🍽️</div>
                            <div class="product-card-title-section">
                                <h3>Lifestyle Perks</h3>
                                <span class="product-badge">Style ⚡ Save | Exclusive Lifestyle Benefits</span>
                            </div>
                        </div>
                        <p>Enjoy dining discounts, travel rewards, and exclusive offers at premium partner locations.</p>
                        <ul class="product-features">
                            <li>Restaurant discounts</li>
                            <li>Travel rewards</li>
                            <li>Shopping benefits</li>
                        </ul>
                    </div>
                </div>

                <!-- CTA Section -->
                <div class="cta-section">
                    <h3>Ready to Get Started?</h3>
                    <p>Apply for any of our financial products and start earning rewards today. Our team is here to help you every step of the way.</p>
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

        
    </script>
</body>
</html>