/**
 * Authentication Helper Functions
 * Shared across all authenticated pages
 */

// Check if employee is logged in
async function checkAuthentication() {
  try {
    const response = await fetch("../api/auth/check-session.php");
    const result = await response.json();

    if (!result.logged_in) {
      // Not logged in, redirect to login page
      window.location.href = "employee-login.html";
      return null;
    }

    return result.employee;
  } catch (error) {
    console.error("Authentication check error:", error);
    window.location.href = "employee-login.html";
    return null;
  }
}

// Update username display in navbar
function updateEmployeeDisplay(employee) {
  const employeeNameElement = document.getElementById("employeeName");
  if (employeeNameElement && employee) {
    employeeNameElement.textContent = employee.name || "Employee";
  }
}

// Handle logout
async function handleLogout() {
  if (!confirm("Are you sure you want to logout?")) {
    return;
  }

  try {
    const response = await fetch("../api/auth/employee-logout.php");
    const result = await response.json();

    if (result.success) {
      // Clear any stored data
      sessionStorage.clear();

      // Redirect to login page
      window.location.href = "employee-login.html";
    }
  } catch (error) {
    console.error("Logout error:", error);
    // Force redirect anyway
    window.location.href = "employee-login.html";
  }
}

// Initialize authentication on page load
async function initAuthentication() {
  const employee = await checkAuthentication();

  if (employee) {
    updateEmployeeDisplay(employee);

    // Setup logout button if it exists
    const logoutBtn = document.getElementById("logoutBtn");
    if (logoutBtn) {
      logoutBtn.addEventListener("click", handleLogout);
    }
  }

  return employee;
}

// Auto-check session every 5 minutes
setInterval(async () => {
  const employee = await checkAuthentication();
  if (!employee) {
    alert("Your session has expired. Please login again.");
  }
}, 5 * 60 * 1000);
