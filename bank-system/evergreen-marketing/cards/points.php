<?php
    session_start([
       'cookie_httponly' => true,
       'cookie_secure' => isset($_SERVER['HTTPS']),
       'use_strict_mode' => true
    ]);
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['email'])) {
        header("Location: ../login.php");
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
    <title>Points Details - Evergreen</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
            background-color: #e8e8e8;
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
            gap: 3rem;
            align-items: center;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
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

        /* DROPDOWN STYLES - UPDATED FOR FULL WIDTH */
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

        /* Points Card */
        .points-card {
            background: linear-gradient(135deg, #0d4d3d 0%, #1a6b56 100%);
            border-radius: 20px;
            padding: 40px;
            margin: 20px auto;
            max-width: 80%;
            text-align: center;
            color: white;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .points-label {
            font-size: 11px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #ffd700;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .points-value {
            font-size: 72px;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .points-subtitle {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.6);
        }

        /* Tabs */
        .tabs {
            display: flex;
            max-width: 80%;
            margin: 0 auto;
            background-color: #d0d0d0;
        }

        .tab {
            flex: 1;
            padding: 15px;
            text-align: center;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            color: #666;
            transition: all 0.3s ease;
        }

        .tab.active {
            color: #0d4d3d;
            border-bottom-color: #0d4d3d;
            background-color: #e8e8e8;
        }

        .tab-content {
            display: none;
            max-width: 80%;
            margin: 0 auto;
            padding: 20px;
            max-height: 600px;
            overflow-y: auto;
        }

        .tab-content.active {
            display: block;
        }

        /* Mission Cards */
        .mission-card {
            background: linear-gradient(135deg, #fef8e8 0%, #fdf5dc 100%);
            border-radius: 15px;
            padding: 25px 30px;
            margin-bottom: 15px;
            display: grid;
            grid-template-columns: 120px 1px 1fr;
            gap: 25px;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease;
            position: relative;
            min-height: 140px;
        }

        .mission-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .mission-timestamp {
            position: absolute;
            top: 15px;
            right: 25px;
            font-size: 11px;
            color: #999;
            font-weight: 500;
        }

        .mission-points {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .mission-points-value {
            font-size: 35px;
            font-weight: 700;
            color: #0d4d3d;
            line-height: 1;
        }

        .mission-points-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
            font-weight: 600;
        }

        .mission-divider {
            width: 1px;
            height: 80px;
            background: linear-gradient(to bottom, transparent, #d0d0d0, transparent);
            align-self: center;
        }

        .mission-details {
            display: flex;
            flex-direction: row;
            gap: 20px;
            justify-content: space-between;
        }

        .mission-description {
            font-size: 15px;
            color: #333;
            line-height: 1.6;
            font-weight: 500;
            display: flex;
            text-align: center;
            align-items: center;
            justify-content: center;
        }

        .mission-actions {
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        .collect-btn {
            background: linear-gradient(135deg, #0d4d3d 0%, #1a6b56 100%);
            color: white;
            border: none;
            padding: 8px 32px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(13, 77, 61, 0.3);
            white-space: nowrap;
            margin-top: 5%;
        }

        .collect-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background: #ccc;
        }

        .collect-btn:disabled:hover {
            transform: none;
            box-shadow: none;
        }

        .collect-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(13, 77, 61, 0.4);
        }

        .collect-btn:active {
            transform: scale(0.98);
        }

        .collect-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .completed-badge {
            background: linear-gradient(135deg, #0d4d3d 0%, #1a6b56 100%);
            color: white;
            border: none;
            padding: 8px 24px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
            cursor: default;
            display: inline-block;
            width: auto;
            margin-top: 5%;
        }

        /* Scrollbar */
        .tab-content::-webkit-scrollbar {
            width: 8px;
        }

        .tab-content::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .tab-content::-webkit-scrollbar-thumb {
            background: #0d4d3d;
            border-radius: 10px;
        }

        .tab-content::-webkit-scrollbar-thumb:hover {
            background: #1a6b56;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .empty-state-text {
            font-size: 16px;
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

/* DROPDOWN STYLES - UPDATED FOR FULL WIDTH */
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

/* ------------------------------ */
/* 📱 RESPONSIVE MEDIA QUERIES */
/* ------------------------------ */

/* Large Desktops (1200px - 1399px) */
@media (max-width: 1399px) {
    .hero-content h1 {
        font-size: 4.5rem;
    }
}

/* Small Desktops & Large Tablets (992px - 1199px) */
@media (max-width: 1199px) {
    nav {
        padding: 1rem 3%;
    }

    .nav-links {
        gap: 2rem;
    }

    .nav-links a {
        margin: 0 0.5rem;
    }

    .dropdown-content {
        padding: 1.2rem 3%;
    }

    .dropdown-content a {
        margin: 0 1rem;
        font-size: 0.95rem;
    }

    .hero {
        grid-template-columns: 1fr;
        text-align: center;
        padding: 6rem 5% 4rem;
        gap: 2rem;
    }

    .hero-content h1 {
        font-size: 4rem;
    }

    .hero-content p {
        margin-left: auto;
        margin-right: auto;
        max-width: 600px;
    }

    .hero-apply {
        justify-content: center;
    }

    .hero-image {
        margin-top: 1rem;
    }

    .credit-card-display {
        width: 480px;
        height: 300px;
    }

    .points-card {
        max-width: 85%;
    }

    .tabs, .tab-content {
        max-width: 85%;
    }

    .footer-content {
        grid-template-columns: 1.5fr 1fr 1fr;
        gap: 2.5rem;
    }
}

/* Tablets (768px - 991px) */
@media (max-width: 991px) {
    nav {
        padding: 1rem 2rem;
    }

    .nav-links {
        gap: 1.5rem;
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

    .nav-buttons {
        gap: 0.5rem;
    }

    .username-profile {
        font-size: 0.9rem;
        padding: 0.5rem 0.75rem;
    }

    .hero {
        padding: 6rem 3% 3rem;
    }

    .hero-content h1 {
        font-size: 3.5rem;
    }

    .hero-content p {
        font-size: 1rem;
        margin-top: 1rem;
    }

    .btn-apply {
        padding: 0.8rem 1.75rem;
        font-size: 0.95rem;
    }

    .credit-card-display {
        width: 420px;
        height: 260px;
    }

    .points-card {
        max-width: 90%;
        padding: 35px;
    }

    .points-value {
        font-size: 60px;
    }

    .tabs, .tab-content {
        max-width: 90%;
    }

    .tab {
        font-size: 14px;
        padding: 13px;
    }

    .mission-card {
        grid-template-columns: 100px 1px 1fr;
        gap: 20px;
        padding: 20px 25px;
    }

    .mission-points-value {
        font-size: 42px;
    }

    .mission-timestamp {
        font-size: 10px;
        top: 12px;
        right: 20px;
    }

    .mission-description {
        font-size: 14px;
    }

    .collect-btn, .completed-badge {
        padding: 7px 28px;
        font-size: 12px;
    }

    .footer-content {
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
    }

    .footer-section:first-child {
        grid-column: 1 / -1;
    }
}

/* Small Tablets & Large Phones (640px - 767px) */
@media (max-width: 767px) {
    nav {
        padding: 1rem 3%;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .logo {
        font-size: 1.1rem;
    }

    .logo-icon {
        width: 45px;
        height: 45px;
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

    .username-profile {
        font-size: 0.85rem;
        padding: 0.4rem 0.8rem;
    }

    .nav-buttons {
        gap: 0.5rem;
    }

    .hero {
        padding: 5.5rem 4% 3rem;
    }

    .hero-content {
        margin-top: 50px;
    }

    .hero-content h1 {
        font-size: 3rem;
        margin-bottom: 1rem;
    }

    .hero-content p {
        font-size: 0.95rem;
        line-height: 1.6;
    }

    .hero-apply {
        flex-direction: column;
        gap: 0.75rem;
    }

    .credit-card-display {
        width: 350px;
        height: 220px;
    }

    .card-chip {
        width: 40px;
        height: 30px;
        left: 20px;
        top: 50px;
    }

    .card-number {
        font-size: 0.9rem;
        bottom: 40px;
        left: 20px;
    }

    .card-holder {
        font-size: 0.75rem;
        bottom: 18px;
        left: 20px;
    }

    .points-card {
        max-width: 92%;
        padding: 30px 25px;
    }

    .points-label {
        font-size: 10px;
    }

    .points-value {
        font-size: 56px;
    }

    .points-subtitle {
        font-size: 12px;
    }

    .tabs, .tab-content {
        max-width: 92%;
    }

    .mission-card {
        grid-template-columns: 1fr;
        text-align: center;
        gap: 15px;
        padding: 20px;
        min-height: auto;
    }

    .mission-divider {
        display: none;
    }

    .mission-timestamp {
        position: static;
        margin-bottom: 10px;
    }

    .mission-points {
        padding-bottom: 10px;
        border-bottom: 1px solid #e0e0e0;
    }

    .mission-details {
        flex-direction: column;
        gap: 15px;
        align-items: center;
    }

    .mission-description {
        text-align: center;
    }

    .mission-actions {
        justify-content: center;
    }

    .collect-btn, .completed-badge {
        margin-top: 0;
    }

    footer {
        padding: 2.5rem 1.5rem 1rem;
    }

    .footer-content {
        grid-template-columns: 1fr;
        gap: 2.5rem;
        text-align: left;
    }

    .footer-brand {
        text-align: left;
    }

    .social-icons {
        justify-content: flex-start;
    }
}

/* Mobile Phones (480px - 639px) */
@media (max-width: 639px) {
    nav {
        padding: 0.8rem 1rem;
    }

    .logo {
        font-size: 1rem;
    }

    .logo-icon {
        width: 40px;
        height: 40px;
    }

    .dropdown-content a {
        display: inline-block;
        margin: 0.2rem 0.3rem;
        font-size: 0.8rem;
    }

    .hero {
        padding: 5rem 3% 2.5rem;
    }

    .hero-content h1 {
        font-size: 2.5rem;
        line-height: 1.2;
    }

    .hero-content p {
        font-size: 0.9rem;
    }

    .btn-apply {
        padding: 0.75rem 1.5rem;
        font-size: 0.9rem;
    }

    .credit-card-display {
        width: 90%;
        max-width: 320px;
        height: 200px;
        transform: rotate(0deg);
    }

    .points-card {
        max-width: 95%;
        padding: 25px 20px;
    }

    .points-value {
        font-size: 48px;
    }

    .tabs {
        max-width: 95%;
    }

    .tab {
        padding: 12px 8px;
        font-size: 13px;
    }

    .tab-content {
        max-width: 95%;
        padding: 15px 10px;
        max-height: 500px;
    }

    .mission-card {
        padding: 18px 15px;
    }

    .mission-points-value {
        font-size: 36px;
    }

    .mission-points-label {
        font-size: 11px;
    }

    .mission-description {
        font-size: 13px;
    }

    .collect-btn, .completed-badge {
        padding: 6px 24px;
        font-size: 12px;
    }

    .empty-state {
        padding: 50px 15px;
    }

    .empty-state-icon {
        font-size: 42px;
    }

    .empty-state-text {
        font-size: 15px;
    }

    footer {
        padding: 2rem 1rem 1rem;
    }

    .footer-content {
        gap: 2rem;
    }

    .footer-brand p {
        font-size: 0.9rem;
    }

    .footer-section h4 {
        font-size: 1rem;
        margin-bottom: 0.75rem;
    }

    .footer-section ul li {
        font-size: 0.9rem;
    }

    .contact-item {
        font-size: 0.9rem;
    }

    .footer-bottom {
        flex-direction: column;
        gap: 1.5rem;
        font-size: 0.85rem;
    }

    .footer-links {
        flex-wrap: wrap;
        gap: 1rem;
    }

    .footer-links a {
        font-size: 0.85rem;
    }
}

/* Extra Small Phones (320px - 479px) */
@media (max-width: 479px) {
    nav span {
        font-size: 20px;
    }

    .logo {
        font-size: 0.9rem;
    }

    .logo-icon {
        width: 35px;
        height: 35px;
    }

    .profile-btn {
        width: 35px;
        height: 35px;
    }

    .hero {
        padding: 4.5rem 2% 2rem;
    }

    .hero-content h1 {
        font-size: 2rem;
    }

    .hero-content p {
        font-size: 0.85rem;
        line-height: 1.5;
    }

    .hero-apply p {
        font-size: 0.9rem;
    }

    .btn-apply {
        padding: 0.7rem 1.3rem;
        font-size: 0.85rem;
    }

    .credit-card-display {
        width: 95%;
        max-width: 280px;
        height: 175px;
    }

    .card-chip {
        width: 35px;
        height: 28px;
        left: 15px;
        top: 40px;
    }

    .card-logo {
        font-size: 1rem;
        right: 15px;
        top: 15px;
    }

    .card-number {
        font-size: 0.8rem;
        bottom: 35px;
        left: 15px;
        letter-spacing: 2px;
    }

    .card-holder {
        font-size: 0.65rem;
        bottom: 15px;
        left: 15px;
    }

    .points-card {
        padding: 20px 15px;
    }

    .points-label {
        font-size: 9px;
        margin-bottom: 12px;
    }

    .points-value {
        font-size: 42px;
        margin-bottom: 12px;
    }

    .points-subtitle {
        font-size: 11px;
    }

    .tab {
        padding: 10px 6px;
        font-size: 12px;
    }

    .tab-content {
        padding: 12px 8px;
    }

    .mission-card {
        padding: 15px 12px;
    }

    .mission-points-value {
        font-size: 32px;
    }

    .mission-description {
        font-size: 12px;
    }

    .collect-btn, .completed-badge {
        padding: 6px 20px;
        font-size: 11px;
    }

    .empty-state {
        padding: 40px 10px;
    }

    .empty-state-icon {
        font-size: 36px;
    }

    .empty-state-text {
        font-size: 14px;
    }

    footer {
        padding: 1.5rem 0.75rem 0.75rem;
    }

    .footer-brand p {
        font-size: 0.85rem;
    }

    .social-icon {
        width: 30px;
        height: 30px;
        font-size: 0.9rem;
    }

    .footer-section h4 {
        font-size: 0.95rem;
    }

    .footer-section ul li,
    .contact-item {
        font-size: 0.85rem;
    }

    .footer-bottom p {
        font-size: 0.8rem;
    }

    .footer-links a {
        font-size: 0.8rem;
    }
}

/* Landscape Orientation Adjustments */
@media (max-height: 600px) and (orientation: landscape) {
    .hero {
        padding: 4rem 5% 2rem;
        min-height: auto;
    }

    .hero-content h1 {
        font-size: 2.5rem;
        margin-bottom: 1rem;
    }

    .hero-content p {
        margin-top: 0.5rem;
        margin-bottom: 1rem;
    }

    .credit-card-display {
        width: 300px;
        height: 190px;
    }

    .points-card {
        padding: 25px;
    }

    .points-value {
        font-size: 48px;
    }

    .tab-content {
        max-height: 400px;
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
                    <img src="../images/Logo.svg">
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

            <a href="/Evergreen/LoanSubsystem/">Loans</a>
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
                    <a href="#" role="menuitem">Profile</a>
                    <a href="../refer.php" role="menuitem">Refer to a friend</a>
                    <a href="../cards/points.php" role="menuitem">Missions</a>
                    <a href="viewing.php" role="menuitem" onclick="showSignOutModal(event)">Sign Out</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Rewards</h1>
            <p>Earn points, cashback, and exclusive perks every time you<br>
               use your EVERGREEN Card — making every purchase more<br>
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

    <!-- Points Card -->
    <div class="points-card">
        <div class="points-label">EVERGREEN POINTS</div>
        <div class="points-value" id="totalPoints">0.00</div>
        <div class="points-subtitle">Collect more points to enjoy exciting rewards!</div>
    </div>

    <!-- Tabs -->
    <div class="tabs">
        <button class="tab active" onclick="switchTab('mission')">Mission</button>
        <button class="tab" onclick="switchTab('history')">Point History</button>
        <button class="tab" onclick="switchTab('completed')">Completed</button>
    </div>

    <!-- Mission Tab -->
    <div id="mission" class="tab-content active">
        <div class="empty-state">
            <div class="empty-state-icon">⏳</div>
            <div class="empty-state-text">Loading missions...</div>
        </div>
    </div>

    <!-- Point History Tab -->
    <div id="history" class="tab-content">
        <div class="empty-state">
            <div class="empty-state-icon">📊</div>
            <div class="empty-state-text">No point history yet</div>
        </div>
    </div>

    <!-- Completed Tab -->
    <div id="completed" class="tab-content">
        <div class="empty-state">
            <div class="empty-state-icon">✓</div>
            <div class="empty-state-text">No completed missions yet</div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-brand">
                <div class="logo">
                    <div class="logo-icon">
                        <img src="../images/icon.png">
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
                <div class="contact-item">✉️ evrgrn.64@gmail.com</div>
                <div class="contact-item">📍 123 Financial District, Suite 500<br>&nbsp;&nbsp;&nbsp;&nbsp;New York, NY 10004</div>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>© 2023 Evergreen Bank. All rights reserved.<br>Member FDIC. Equal Housing Lender. Evergreen Bank, N.A.</p>
            <div class="footer-links">
                <a href="../policy.php">Privacy Policy</a>
                <a href="../terms.php">Terms and Agreements</a>
                <a href="../faq.php">FAQS</a>
                <a href="../about.php">About Us</a>
            </div>
        </div>
    </footer>

    <script src="../js/points_system.js"></script>
    <script>

        pointsSystem.apiUrl = '../points_api.php';
        
        // Dropdown functionality
        function toggleDropdown() {
            const dropdown = document.getElementById("cardsDropdown");
            dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
        }

        window.addEventListener("click", function(e) {
            if (!e.target.matches('.dropbtn')) {
                const dropdown = document.getElementById("cardsDropdown");
                if (dropdown && dropdown.style.display === "block") {
                    dropdown.style.display = "none";
                }
            }
        });

        // Tab switching with data loading
        function switchTab(tabName) {
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => tab.classList.remove('active'));

            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => content.classList.remove('active'));

            event.target.classList.add('active');
            document.getElementById(tabName).classList.add('active');

            // Load data based on tab
            if (tabName === 'mission') {
                pointsSystem.renderMissions('mission');
            } else if (tabName === 'history') {
                pointsSystem.renderPointHistory('history');
            } else if (tabName === 'completed') {
                pointsSystem.renderCompletedMissions('completed');
            }
        }

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
                margin: 0 auto 1.5rem;
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