<?php
// Include database configuration
require_once __DIR__ . '/config/database.php';

// Get user initials from session (only if logged in)
$userInitials = 'U';
$displayName = 'Guest';

if (isset($_SESSION['user_email'])) {
    // Get user name from BankingDB
    if (isset($_SESSION['user_name']) && $_SESSION['user_name'] !== 'Guest') {
        $displayName = $_SESSION['user_name'];
        $nameParts = explode(' ', $displayName);
        $firstInitial = $nameParts[0][0] ?? '';
        $lastInitial = end($nameParts)[0] ?? '';
        $userInitials = strtoupper($firstInitial . $lastInitial);
    } else {
        // Fallback: Query database for user info
        $conn = getDBConnection();
        if ($conn) {
            $email = $_SESSION['user_email'];
            $sql = "SELECT 
                        bc.first_name,
                        bc.middle_name,
                        bc.last_name
                    FROM bank_customers bc
                    WHERE bc.email = ?
                    LIMIT 1";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    $displayName = trim($row['first_name'] . ' ' . ($row['middle_name'] ?? '') . ' ' . $row['last_name']);
                    $nameParts = explode(' ', $displayName);
                    $firstInitial = $nameParts[0][0] ?? '';
                    $lastInitial = end($nameParts)[0] ?? '';
                    $userInitials = strtoupper($firstInitial . $lastInitial);
                    
                    // Update session
                    $_SESSION['user_name'] = $displayName;
                }
                $stmt->close();
            }
        }
    }
}
?>

<style>
/* ✅ REDESIGNED HEADER - Modern Clean Style */
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

/* Logo Section */
.logo {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: white;
    font-size: 1.2rem;
    font-weight: bold;
}

.logo a {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: white;
    text-decoration: none;
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

/* Navigation Links */
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
    position: relative;
}

.nav-links a:hover {
    color: #F1B24A;
}

.nav-links a::after {
    content: '';
    position: absolute;
    bottom: -5px;
    left: 0;
    width: 0;
    height: 2px;
    background-color: #F1B24A;
    transition: width 0.3s ease;
}

.nav-links a:hover::after {
    width: 100%;
}

/* Dropdown Styles */
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

/* User Profile Section */
.profile-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
    position: relative;
}

.username-profile {
    background: transparent;
    color: #FFFFFF;
    text-decoration: none;
    display: flex;
    align-items: center;
    padding: 0.5rem 1rem;
    border-radius: 5px;
    font-weight: 500;
}

.username-profile:hover {
    color: #F1B24A;
}

/* Profile Button/Avatar */
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
    transition: opacity 0.2s ease;
}

.profile-btn:hover {
    opacity: 0.85;
}

.profile-btn img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
    background-color: #003631;
    display: block;
}

/* Profile Avatar with Initials */
.avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    cursor: pointer;
    transition: opacity 0.2s ease;
}

.avatar:hover {
    opacity: 0.85;
}

/* Profile Dropdown */
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
</style>

<!-- Dynamic Header -->
<nav>
    <div class="logo">
        <a href="#home" style="display: flex; align-items: center; gap: 0.5rem; color: white; text-decoration: none;">
            <div class="logo-icon">
                <img src="logo.png" alt="Evergreen Logo">
            </div>
            <span>EVERGREEN</span>
        </a>
    </div>

    <div class="nav-links">
        <a href="/Evergreen/bank-system/evergreen-marketing/viewing.php">Home</a>
        <a href="#loan-services">Loan Services</a>
        <a href="#loan-dashboard">Dashboard</a>
        <a href="/Evergreen/bank-system/Basic-operation/operations/public/customer/account">Profile</a>
        <!--<a href="../../bank-system/evergreen-marketing/viewingpage.php">Banking</a>-->
    </div>

    <div class="profile-actions">
        <span class="username-profile"><?= htmlspecialchars($displayName) ?></span>
        
        <?php if ($displayName !== 'Guest'): ?>
            <button class="profile-btn" id="profileBtn" onclick="toggleProfileDropdown(event)" aria-expanded="false">
                <div class="avatar"><?= htmlspecialchars($userInitials) ?></div>
            </button>
            
            <div class="profile-dropdown" id="profileDropdown">
                <a href="logout.php">Logout</a>
            </div>
        <?php endif; ?>
    </div>
</nav>

<script>
// Profile dropdown toggle
function toggleProfileDropdown(e) {
    e.stopPropagation();
    const dd = document.getElementById('profileDropdown');
    const btn = document.getElementById('profileBtn');
    const isOpen = dd.classList.toggle('show');
    btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
}

// Close profile dropdown when clicking outside or pressing Esc
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
</script>