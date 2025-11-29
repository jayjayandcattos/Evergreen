// Employee Login Script
document.addEventListener("DOMContentLoaded", function () {
  const loginForm = document.getElementById("loginForm");
  const togglePassword = document.getElementById("togglePassword");
  const passwordInput = document.getElementById("password");
  const loginBtn = document.getElementById("loginBtn");
  const btnText = document.getElementById("btnText");
  const btnSpinner = document.getElementById("btnSpinner");
  const alertContainer = document.getElementById("alert-container");

  // Toggle Password Visibility
  togglePassword.addEventListener("click", function () {
    const type =
      passwordInput.getAttribute("type") === "password" ? "text" : "password";
    passwordInput.setAttribute("type", type);

    // Toggle eye icon
    const eyeIcon = document.getElementById("eyeIcon");
    if (type === "text") {
      eyeIcon.innerHTML = `
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                <line x1="1" y1="1" x2="23" y2="23"></line>
            `;
    } else {
      eyeIcon.innerHTML = `
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
            `;
    }
  });

  // Show Alert Function
  function showAlert(message, type = "danger") {
    alertContainer.innerHTML = `
            <div class="alert alert-${type}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    ${
                      type === "danger"
                        ? '<circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line>'
                        : '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline>'
                    }
                </svg>
                <span>${message}</span>
            </div>
        `;

    // Auto-hide after 5 seconds
    setTimeout(() => {
      alertContainer.innerHTML = "";
    }, 5000);
  }

  // Set Loading State
  function setLoading(loading) {
    if (loading) {
      loginBtn.disabled = true;
      btnText.classList.add("d-none");
      btnSpinner.classList.remove("d-none");
    } else {
      loginBtn.disabled = false;
      btnText.classList.remove("d-none");
      btnSpinner.classList.add("d-none");
    }
  }

  // Handle Form Submission
  loginForm.addEventListener("submit", async function (e) {
    e.preventDefault();

    // Clear previous alerts
    alertContainer.innerHTML = "";

    // Get form data
    const username = document.getElementById("username").value.trim();
    const password = document.getElementById("password").value;
    const rememberMe = document.getElementById("rememberMe").checked;

    // Validate inputs
    if (!username || !password) {
      showAlert("Please enter both username and password", "danger");
      return;
    }

    // Set loading state
    setLoading(true);

    try {
      // Send login request
      const response = await fetch("../api/auth/employee-login.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          username: username,
          password: password,
          rememberMe: rememberMe,
        }),
      });

      const result = await response.json();

      if (result.success) {
        // Show success message
        showAlert("Login successful! Redirecting...", "success");

        // Store user info in session storage
        if (result.employee) {
          sessionStorage.setItem(
            "employee_name",
            result.employee.first_name + " " + result.employee.last_name
          );
          sessionStorage.setItem("employee_role", result.employee.role);
        }

        // Redirect to dashboard after short delay
        setTimeout(() => {
          window.location.href = "employee-dashboard.html";
        }, 1000);
      } else {
        // Show error message
        showAlert(result.message || "Invalid username or password", "danger");
        setLoading(false);
      }
    } catch (error) {
      console.error("Login error:", error);
      showAlert("An error occurred. Please try again later.", "danger");
      setLoading(false);
    }
  });

  // Check if already logged in
  checkLoginStatus();
});

// Check Login Status
async function checkLoginStatus() {
  try {
    const response = await fetch("../api/auth/check-session.php");
    const result = await response.json();

    if (result.logged_in) {
      // Already logged in, redirect to dashboard
      window.location.href = "employee-dashboard.html";
    }
  } catch (error) {
    console.error("Session check error:", error);
  }
}
