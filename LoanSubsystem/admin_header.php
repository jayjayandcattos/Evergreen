<?php
if (!isset($_SESSION['user_email']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit();
}
?>

<style>
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
  left: 0;
  right: 0;
  width: 100%;
  z-index: 1000;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  height: 60px;
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

/* ✅ CENTERED NAVIGATION */
.nav-center {
  display: flex;
  justify-content: center;
  flex-grow: 1;
}

nav ul {
  display: flex;
  list-style: none;
  gap: 2rem;
  margin: 0;
  padding: 0;
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

/* ✅ SINGLE UNDERLINE */
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

nav a:hover::after,
nav a.active::after {
  width: 100%;
}

nav a.active {
  color: var(--accent-color);
}

/* Admin Section - Single Line */
.admin-section {
  display: flex;
  align-items: center;
  gap: 12px;
  position: relative;
}

.datetime {
  color: white;
  font-size: 14px;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  text-align: right;
  margin-right: 5px;
}

.datetime span {
  line-height: 1.4;
}

.username {
  color: white;
  font-weight: 500;
  font-size: 14px;
}

.admin-icon {
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

.admin-icon:hover {
  opacity: 0.85;
}

.dropdown-content {
  display: none;
  position: absolute;
  background-color: var(--primary-color);
  min-width: 120px;
  box-shadow: 0 8px 16px rgba(0,0,0,0.2);
  z-index: 1001;
  top: 100%;
  right: 0;
  border-radius: 4px;
  margin-top: 5px;
}

.dropdown-content a {
  color: white;
  padding: 12px 16px;
  display: block;
  text-decoration: none;
  transition: background 0.2s ease;
}

.dropdown-content a:hover {
  background-color: rgba(255,255,255,0.1);
}
</style>

<header id="main-header">
  <div class="logo-container">
    <img src="images/banklogo.png" alt="Evergreen Logo" class="logo">
    <span class="logo-text">EVERGREEN</span>
  </div>

  <!-- ✅ CENTERED NAV -->
  <div class="nav-center">
    <nav>
      <ul>
        <li><a href="adminindex.php" class="<?= basename($_SERVER['PHP_SELF']) === 'adminindex.php' ? 'active' : '' ?>">Dashboard</a></li>
        <li><a href="adminapplications.php" class="<?= basename($_SERVER['PHP_SELF']) === 'adminapplications.php' ? 'active' : '' ?>">Loan Applications</a></li>
      </ul>
    </nav>
  </div>

  <!-- ✅ ADMIN SECTION (right-aligned) -->
  <div class="admin-section" id="adminUserContainer">
    <div class="datetime">
      <span id="currentDate"><?= date("Y/m/d") ?></span>
      <span id="currentTime"><?= date("h:i:s A") ?></span>
    </div>
    <span class="username"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
    <div class="admin-icon" id="adminIcon">👤</div>
    
    <div class="dropdown-content" id="adminDropdown">
      <a href="logout.php">Logout</a>
    </div>
  </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const icon = document.getElementById('adminIcon');
  const dropdown = document.getElementById('adminDropdown');
  const container = document.getElementById('adminUserContainer');

  if (icon && dropdown) {
    icon.addEventListener('click', function (e) {
      e.stopPropagation();
      dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    });

    document.addEventListener('click', function (e) {
      if (container && !container.contains(e.target)) {
        dropdown.style.display = 'none';
      }
    });
  }

  // Update time every second
  function updateTime() {
    const now = new Date();
    const options = { timeZone: 'Asia/Manila', hour12: true };
    const timeEl = document.getElementById('currentTime');
    const dateEl = document.getElementById('currentDate');
    if (timeEl) timeEl.textContent = now.toLocaleTimeString('en-PH', options);
    if (dateEl) dateEl.textContent = now.toLocaleDateString('en-PH', { timeZone: 'Asia/Manila' });
  }
  setInterval(updateTime, 1000);
  updateTime();
});
</script>