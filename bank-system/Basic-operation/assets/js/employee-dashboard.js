/**
 * Employee Dashboard JavaScript
 * Handles dashboard functionality and navigation
 */

document.addEventListener("DOMContentLoaded", async function () {
  // Check authentication first
  await initAuthentication();

  // Set current date/time
  updateDateTime();

  // Load dashboard stats (placeholder for future implementation)
  loadDashboardStats();

  // Set username from session/localStorage
  loadUserInfo();
});

/**
 * Update date and time display
 */
function updateDateTime() {
  const now = new Date();
  const options = {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  };
  const dateStr = now.toLocaleDateString("en-US", options);
  const timeStr = now.toLocaleTimeString("en-US");

  console.log(`Dashboard loaded at: ${dateStr} ${timeStr}`);
}

/**
 * Load dashboard statistics
 * TODO: Connect to actual API endpoints
 */
function loadDashboardStats() {
  // Placeholder - will be replaced with actual API calls
  const stats = {
    activeCustomers: "--",
    todayTransactions: "--",
    pendingApprovals: "--",
  };

  // Update stat displays
  const statValues = document.querySelectorAll(".stat-value");
  if (statValues.length >= 3) {
    statValues[0].textContent = stats.activeCustomers;
    statValues[1].textContent = stats.todayTransactions;
    statValues[2].textContent = stats.pendingApprovals;
  }
}

/**
 * Load user information
 */
function loadUserInfo() {
  // Get username from session storage or localStorage
  const username =
    sessionStorage.getItem("employee_username") ||
    localStorage.getItem("employee_username") ||
    "Employee";

  const usernameElements = document.querySelectorAll(".username-text");
  usernameElements.forEach((el) => {
    el.textContent = username;
  });
}

/**
 * Navigate to transaction page with specific type
 */
function navigateToTransaction(type) {
  window.location.href = `employee-transaction.html?type=${type}`;
}

/**
 * Handle card clicks for analytics
 */
document.querySelectorAll(".dashboard-card").forEach((card) => {
  card.addEventListener("click", function () {
    const cardTitle = this.querySelector(".card-title").textContent;
    console.log(`Navigating to: ${cardTitle}`);
  });
});
