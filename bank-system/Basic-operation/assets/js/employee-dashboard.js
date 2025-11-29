/**
 * Employee Dashboard JavaScript
 * Handles dashboard functionality and navigation
 */

document.addEventListener("DOMContentLoaded", async function () {
  // Check authentication first
  await initAuthentication();

  // Set current date/time
  updateDateTime();

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
 * Handle card clicks for analytics
 */
document.querySelectorAll(".dashboard-card").forEach((card) => {
  card.addEventListener("click", function () {
    const cardTitle = this.querySelector(".card-title").textContent;
    console.log(`Navigating to: ${cardTitle}`);
  });
});
