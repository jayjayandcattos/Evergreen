<?php
require_once 'config/database.php';

// Get user initials from session (only if logged in)
$userInitials = 'U';
$displayName = 'Guest';

if (isset($_SESSION['user_email'])) {
    $user = getUserByEmail($_SESSION['user_email']);
    
    if ($user) {
        $displayName = $user['display_name'] ?? $user['full_name'] ?? 'Guest';
        $nameParts = explode(' ', $displayName);
        $firstInitial = $nameParts[0][0] ?? '';
        $lastInitial = end($nameParts)[0] ?? '';
        $userInitials = strtoupper($firstInitial . $lastInitial);
    }
}
?>

<style>
/* Header & Navigation */
:root {
  --primary-color: #003631;
  --secondary-color: #FAF7EF;
  --accent-color: #f4b244;
  --text-dark: #1a3a30;
  --text-light: #ffffff;
}
header {
  background-color: var(--primary-color);
  font-family: 'Inter', sans-serif;
  font-size: 12px;
  padding: 1rem 2rem;
  position: fixed;
  top: 0;
  width: 100%;
  z-index: 1000;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.logo-container {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.logo {
  height: 40px;
  width: auto;
}

.logo-text {
  color: white;
  font-weight: bold;
  font-size: 1.2rem;
  letter-spacing: 1px;
}

nav ul {
  display: flex;
  list-style: none;
  gap: 2rem;
}

nav a {
  color: white;
  text-decoration: none;
  font-weight: 500;
  transition: color 0.3s ease;
  position: relative;
}

nav a:hover {
  color: var(--accent-color);
}

nav a::after {
  content: '';
  position: absolute;
  bottom: -5px;
  left: 0;
  width: 0;
  height: 2px;
  background-color: var(--accent-color);
  transition: width 0.3s ease;
}

nav a:hover::after {
  width: 100%;
}

/* === Dropdown Base Style (used by both Cards and User) === */
.dropdown-content {
  display: none;
  position: absolute;
  background-color: var(--primary-color);
  min-width: 120px;
  box-shadow: 0 8px 16px rgba(0,0,0,0.2);
  z-index: 1;
  top: 100%;
  left: 0;
}

.dropdown-content a {
  color: white;
  padding: 12px 16px;
  display: block;
  text-decoration: none;
}

.dropdown-content a:hover {
  background-color: rgba(255,255,255,0.1);
}

/* Keep existing .dropdown for Cards */
.dropdown {
  position: relative;
}

.dropdown:hover .dropdown-content {
  display: block;
}

/* === User Section === */
.user-section {
  display: flex;
  align-items: center;
  gap: 1rem;
  position: relative; /* so dropdown is positioned relative to this */
}

.username {
  color: white;
  font-weight: 500;
}

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
  cursor: pointer; /* 👈 makes it clear it's clickable */
  transition: opacity 0.2s ease;
}

.avatar:hover {
  opacity: 0.85; /* 👈 subtle hover effect */
}
</style>

<!-- Dynamic Header -->
<header id="main-header">
  <div class="logo-container">
    <img src="logo.png" alt="Evergreen Logo" class="logo">
    <span class="logo-text">EVERGREEN</span>
  </div>

  <nav>
    <ul>
      <li><a href="#home">Home</a></li>
      <li class="dropdown">
        <a href="#cards">Cards ▼</a>
        <div class="dropdown-content">
          <a href="#credit-cards">Credit Cards</a>
          <a href="#debit-cards">Debit Cards</a>
          <a href="#prepaid-cards">Prepaid Cards</a>
        </div>
      </li>
      <li><a href="index.php">Loans</a></li>
      <li><a href="/bank-system/evergreen-marketing/about.php">About Us</a></li>
    </ul>
  </nav>

  <!-- User section: full name + clickable avatar -->
  <div class="user-section" id="userAvatarContainer">
    <span class="username"><?= htmlspecialchars($displayName) ?></span>
    <div class="avatar" id="userAvatarCircle"><?= htmlspecialchars($userInitials) ?></div>

    <?php if ($displayName !== 'Guest'): ?>
      <!-- Reuse .dropdown-content — identical to Cards dropdown -->
      <div class="dropdown-content" id="userDropdown" style="left: auto; right: 0;">
        <a href="logout.php">Logout</a>
      </div>
    <?php endif; ?>
  </div>
</header>

<!-- JavaScript for click-to-toggle -->
<script>
document.addEventListener('DOMContentLoaded', function () {
  const avatar = document.getElementById('userAvatarCircle');
  const dropdown = document.getElementById('userDropdown');
  const container = document.getElementById('userAvatarContainer');

  if (avatar && dropdown) {
    avatar.addEventListener('click', function (e) {
      e.stopPropagation();
      // Toggle display
      if (dropdown.style.display === 'block') {
        dropdown.style.display = 'none';
      } else {
        dropdown.style.display = 'block';
      }
    });

    // Close when clicking outside
    document.addEventListener('click', function (e) {
      if (container && !container.contains(e.target)) {
        dropdown.style.display = 'none';
      }
    });
  }
});
</script>