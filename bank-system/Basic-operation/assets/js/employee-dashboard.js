/**
 * Employee Dashboard JavaScript
 * Handles dashboard functionality and navigation
 */

document.addEventListener("DOMContentLoaded", async function () {
  // Initialize authentication (includes logout button setup)
  const employee = await initAuthentication();

  if (employee) {
    // Update dashboard welcome message
    const dashboardNameElement = document.getElementById(
      "dashboardEmployeeName"
    );
    if (dashboardNameElement) {
      dashboardNameElement.textContent = employee.name || "Employee";
    }
  }

  // Set current date/time
  updateDateTime();
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
 * Handle card clicks for analytics
 */
document.querySelectorAll(".dashboard-card").forEach((card) => {
  card.addEventListener("click", function () {
    const cardTitle = this.querySelector(".card-title").textContent;
    console.log(`Navigating to: ${cardTitle}`);
  });
});
