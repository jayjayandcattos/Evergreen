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
    <title>Credit Cards - Evergreen Bank</title>
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
            text-decoration: none;
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

        /* Why Choose Section */
        .why-choose {
            background: #f5f5f5;
            padding: 4rem 5%;
            text-align: center;
        }

        .why-choose h2 {
            color: #003631;
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
        }

        .why-choose-subtitle {
            color: #666;
            margin-bottom: 3rem;
        }

        .benefits-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .benefit-card {
            background: white;
            padding: 2.5rem 2rem;
            border-radius: 15px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .benefit-card:hover {
            transform: scale(1.1);
            transition: 0.2s ease-in;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .benefit-icon {
            width: 60px;
            height: 60px;
            background: #f0f0f0;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 1.8rem;
        }

        .benefit-card h3 {
            color: #003631;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }

        .benefit-card p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .learn-more-link {
            color: #003631;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .learn-more-link:hover {
            color: #F1B24A;
        }

        /* Choose Your Card Section */
        .choose-card {
            padding: 4rem 5%;
            background: white;
        }

        .choose-card h2 {
            color: #003631;
            font-size: 2rem;
            margin-bottom: 3rem;
            padding-bottom: 0.5rem;
            border-bottom: 3px solid #F1B24A;
            display: inline-block;
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .card-item {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .card-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .card-image {
            width: 100%;
            height: 180px;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            border-radius: 12px;
            margin-bottom: 1.5rem;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card-image1 {
            width: 100%;
            height: 180px;
            background: linear-gradient(135deg, #f7d774 0%, #b88900 100%);
            border-radius: 12px;
            margin-bottom: 1.5rem;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card-image2 {
            width: 100%;
            height: 180px;
            background: linear-gradient(135deg, #d9d9d9 0%, #9e9e9e 100%);
            border-radius: 12px;
            margin-bottom: 1.5rem;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card-image3 {
            width: 100%;
            height: 180px;
            background: linear-gradient(135deg, #1a1a1a 0%, #0f0f0f 100%);
            border-radius: 12px;
            margin-bottom: 1.5rem;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .mini-card {
            width: 200px;
            height: 110px;
            background: linear-gradient(135deg, #1e3a8a 0%, #0f172a 100%);
            border-radius: 8px;
            position: relative;
        }

        .mini-card1 {
            width: 200px;
            height: 110px;
            background: linear-gradient(135deg, #b8860b 0%, #7a5c00 100%);
            border-radius: 8px;
            position: relative;
        }

        .mini-card2 {
            width: 200px;
            height: 110px;
            background: linear-gradient(135deg, #e5e4e2 0%, #b8b8b8 100%);
            border-radius: 8px;
            position: relative;
        }

        .mini-card3 {
            width: 200px;
            height: 110px;
            background: linear-gradient(135deg, #2a2a2a 0%, #1a1a1a 100%);
            border-radius: 8px;
            position: relative;
        }

        .mini-chip {
            width: 25px;
            height: 20px;
            background: linear-gradient(135deg, #ffd700 0%, #F1B24A 100%);
            border-radius: 3px;
            position: absolute;
            left: 12px;
            top: 25px;
        }

        .mini-logo {
            position: absolute;
            right: 12px;
            top: 12px;
            color: white;
            font-weight: bold;
            font-size: 0.6rem;
        }

        .card-item h3 {
            color: #003631;
            margin-bottom: 1rem;
            font-size: 1.3rem;
        }

        .card-item p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
        }

        .card-buttons {
            display: flex;
            gap: 0.8rem;
            justify-content: center;
            align-items: center;
        }

        .card-buttons a {
            text-decoration: none;
        }

        .btn-small {
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-yellow {
            background: #F1B24A;
            color: #003631;
        }

        .btn-yellow:hover {
            background: #e0a03a;
            transform: translateY(-2px);
        }

        .btn-dark {
            background: #003631;
            color: white;
        }

        .btn-dark:hover {
            background: #002a26;
            transform: translateY(-2px);
        }

        .btn-small a {
            color: #e0a03a;
            text-decoration: none;
        }

        /* Discover More Section */
        .discover-more {
            background: linear-gradient(135deg, #003631 0%, #002a26 100%);
            padding: 4rem 5%;
        }

        .discover-more h2 {
            color: white;
            font-size: 2rem;
            margin-bottom: 3rem;
            padding-bottom: 0.5rem;
            border-bottom: 3px solid #F1B24A;
            display: inline-block;
        }

        .discover-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 3rem;
            max-width: 900px;
            margin: 0 auto 3rem;
        }

        .discover-card {
            background: white;
            border-radius: 15px;
            padding: 2.5rem;
            text-align: center;
            transition: transform 0.3s;
        }

        .discover-card:hover {
            transform: translateY(-8px);
        }

        .discover-card-image {
            width: 200px;
            height: 100px;
            margin: 0 auto 1.5rem;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            border-radius: 10px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .discover-card h3 {
            color: #003631;
            margin-bottom: 1rem;
            font-size: 1.3rem;
        }

        .discover-card p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .discover-footer {
            text-align: center;
            color: rgba(255,255,255,0.9);
            font-size: 1.05rem;
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

            .benefits-grid,
            .cards-grid,
            .discover-grid {
                grid-template-columns: 1fr;
            }

            .card-buttons {
                flex-direction: column;
            }

            .footer-content {
                grid-template-columns: 1fr;
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
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav>
        <div class="logo">
            <div class="logo-icon">
                <a href="../viewing.php">
                    <img src="../images/Logo.png.png">
                </a>
            </div>
                <span>
                    <a href="../viewing.php">
                    EVERGREEN
                    </a>
                </span>
        </div>
        <div class="nav-buttons">
            <a href="../login.php" class="btn btn-login">Login

            </a>
                
            <a href="../login.php" class="btn btn-primary">Get Started</a>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Credit Cards</h1>
            <p>A credit card lets you borrow funds up to a certain limit to make purchases or pay bills. It offers convenience, security, and the flexibility to pay later, often with rewards or cashback benefits.</p>
            <a href="../login.php" class="btn-apply">Apply Now</a>
        </div>
        <div class="hero-image">
            <div class="card-hand">
                <div class="credit-card-display">
                    <div class="card-chip"></div>
                    <div class="card-logo">CREDIT CARD</div>
                    <div class="card-number">•••• •••• •••• 4589</div>
                    <div class="card-holder">CARDHOLDER NAME</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Why Choose Our Cards Section -->
    <section class="why-choose">
        <h2>Why Choose Our Cards?</h2>
        <p class="why-choose-subtitle">Our cards give you the freedom to spend smart and earn more.</p>
        
        <div class="benefits-grid">
            <div class="benefit-card">
                <div class="benefit-icon">💰</div>
                <h3>Savings & Deposits</h3>
                <p>High-yield savings accounts and CDs to help your money grow faster.</p>
            </div>
            
            <div class="benefit-card">
                <div class="benefit-icon">📈</div>
                <h3>Investments</h3>
                <p>Personalized investment strategies aligned with your financial goals.</p>
            </div>
            
            <div class="benefit-card">
                <div class="benefit-icon">🏠</div>
                <h3>Home Loans</h3>
                <p>Competitive mortgage rates and flexible repayment options for your dream home.</p>
            </div>
        </div>
    </section>

    <!-- Choose Your Card Section -->
    <section class="choose-card">
        <h2>Choose your Card</h2>
        
        <div class="cards-grid">
            <div class="card-item">
                <div class="card-image">
                    <div class="mini-card">
                        <div class="mini-chip"></div>
                        <div class="mini-logo">CREDIT CARD</div>
                    </div>
                </div>
                <h3>Blue Card</h3>
                <p>The Blue Card helps you shop smarter with flexible installment plans. Pay for your groceries and essentials over time while enjoying exclusive perks and rewards.</p>
                <div class="card-buttons">
                    <a href="../login.php" class="btn-small btn-yellow">Apply</a>
                </div>
            </div>

            <div class="card-item">
                <div class="card-image1">
                    <div class="mini-card1">
                        <div class="mini-chip"></div>
                        <div class="mini-logo">CREDIT CARD</div>
                    </div>
                </div>
                <h3>Gold Card</h3>
                <p>The Gold Card gives you exclusive shopping privileges. Enjoy higher rewards, priority offers, and premium benefits on every purchase.</p><br>
                <div class="card-buttons">
                    <a href="../login.php" class="btn-small btn-yellow">Apply</a>
                </div>
            </div>

            <div class="card-item">
                <div class="card-image2">
                    <div class="mini-card2">
                        <div class="mini-chip"></div>
                        <div class="mini-logo">CREDIT CARD</div>
                    </div>
                </div>
                <h3>Platinum Card</h3>
                <p>The Platinum Card offers ultimate privileges for your shopping and lifestyle needs. Enjoy the highest rewards, priority access, and exclusive benefits reserved for our top-tier members.</p>
                <div class="card-buttons">
                    <a href="../login.php" class="btn-small btn-yellow">Apply</a>
                </div>
            </div>

            <div class="card-item">
                <div class="card-image3">
                    <div class="mini-card3">
                        <div class="mini-chip"></div>
                        <div class="mini-logo">CREDIT CARD</div>
                    </div>
                </div>
                <h3>Black Elite Card</h3>
                <p>The Black Elite Card delivers unparalleled privileges for discerning members. Enjoy maximum rewards and personalized benefits designed for a truly elite shopping experience.</p>
                <div class="card-buttons">
                    <a href="../login.php" class="btn-small btn-yellow">Apply</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Discover More Cards Section -->
    <section class="discover-more">
        <h2>Discover More Cards</h2>
        
        <div class="discover-grid">
            <div class="discover-card">
                <div class="discover-card-image">
                    <div class="mini-card">
                        <div class="mini-chip"></div>
                        <div class="mini-logo">DEBIT CARD</div>
                    </div>
                </div>
                <h3>Debit Cards</h3>
                <p>Competitive mortgage rates and flexible repayment options for your dream home.</p>
                <button class="btn-small btn-dark">
                    <a href="../cards/debitno.php">View</a>
                </button>
            </div>

            <div class="discover-card">
                <div class="discover-card-image">
                    <div class="mini-card">
                        <div class="mini-chip"></div>
                        <div class="mini-logo">PREPAID</div>
                    </div>
                </div>
                <h3>Prepaid Cards</h3>
                <p>Competitive mortgage rates and flexible repayment options for your dream home.</p>
                <button class="btn-small btn-dark">
                    <a href="../cards/prepaidno.php">View</a>
                </button>
            </div>
        </div>

        <p class="discover-footer">Discover more options designed to give you flexibility and rewards.</p>
    </section>

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
                    <li><a href="../cards/creditno.php">Credit Cards</a></li>
                    <li><a href="../cards/debitno.php">Debit Cards</a></li>
                    <li><a href="../cards/prepaidno.php">Prepaid Cards</a></li>
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
        // Dropdown functionality
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

        // Smooth scroll behavior
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });

        // Card hover animations
        const cards = document.querySelectorAll('.benefit-card, .card-item, .discover-card');
        cards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transition = 'transform 0.3s ease, box-shadow 0.3s ease';
            });
        });

        // Button click effects
        const buttons = document.querySelectorAll('button');
        buttons.forEach(btn => {
            btn.addEventListener('click', function(e) {
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
            });
        });

        // Intersection Observer for scroll animations
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

        // Observe cards for animation
        document.querySelectorAll('.card-item, .discover-card').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(el);
        });

        // Parallax effect for hero card
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const heroCard = document.querySelector('.credit-card-display');
            if (heroCard && scrolled < 600) {
                heroCard.style.transform = `rotate(-5deg) translateY(${scrolled * 0.1}px)`;
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
    </script>
</body>
</html>