/**
 * Customer Onboarding - Security & Credentials (Step 2)
 * Handles form validation, mobile verification, and navigation
 */

// Detect API path dynamically based on current page location
function getApiBaseUrl() {
  // Get the current page path
  const currentPath = window.location.pathname;
  
  // If we're in /public/, go up one level to find /api/
  if (currentPath.includes('/public/')) {
    const basePath = currentPath.substring(0, currentPath.indexOf('/public/'));
    return window.location.origin + basePath + '/api';
  }
  
  // Fallback: construct from known structure
  const pathParts = currentPath.split('/');
  const basicOpIndex = pathParts.indexOf('Basic-operation');
  if (basicOpIndex !== -1) {
    const basePath = pathParts.slice(0, basicOpIndex + 1).join('/');
    return window.location.origin + basePath + '/api';
  }
  
  // Final fallback
  return window.location.origin + '/bank-system/Basic-operation/api';
}

const API_BASE_URL = getApiBaseUrl();
console.log('API Base URL:', API_BASE_URL);

// State management
let isCodeSent = false;
let isCodeVerified = false;
let canResend = true;
let resendTimer = null;
let verificationTimer = null;
let countryCodes = [];
let verificationSessionId = null; // Store session ID from send-code response

// Initialize on page load
document.addEventListener("DOMContentLoaded", function () {
  loadCountryCodes();
  setupFormHandlers();
  checkSessionData();
});

/**
 * Check if step 1 is completed
 */
function checkSessionData() {
  // In a real application, you would validate session on backend
  // For now, we'll just check if we came from step 1
  const urlParams = new URLSearchParams(window.location.search);
  if (!urlParams.has("from") && !sessionStorage.getItem("step1_completed")) {
    // Optionally redirect to step 1
    // window.location.href = 'customer-onboarding-details.html';
  }
}

/**
 * Load country codes from API
 */
async function loadCountryCodes() {
  try {
    const apiUrl = `${API_BASE_URL}/common/get-country-codes.php`;
    console.log("Loading country codes from:", apiUrl);
    
    const response = await fetch(apiUrl);

    console.log("Country codes response status:", response.status);

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    // Check content type
    const contentType = response.headers.get("content-type");
    if (!contentType || !contentType.includes("application/json")) {
      const text = await response.text();
      console.error("Expected JSON but got:", text.substring(0, 200));
      throw new Error("Server returned non-JSON response");
    }

    const result = await response.json();
    console.log("Country codes result:", result);

    if (result.success && result.data && result.data.length > 0) {
      countryCodes = result.data;
      console.log("Loaded", countryCodes.length, "country codes");
      populateCountryCodeDropdown();
    } else {
      console.warn("No country codes in response, using fallback");
      throw new Error(result.message || "No country codes returned");
    }
  } catch (error) {
    console.error("Error loading country codes:", error);
    // Fallback to default country codes
    countryCodes = [
      {
        country_code_id: 1,
        country_name: "Philippines",
        phone_code: "+63",
        iso_code: "PH",
      },
      {
        country_code_id: 2,
        country_name: "United States",
        phone_code: "+1",
        iso_code: "US",
      },
      {
        country_code_id: 3,
        country_name: "United Kingdom",
        phone_code: "+44",
        iso_code: "GB",
      },
      {
        country_code_id: 4,
        country_name: "Singapore",
        phone_code: "+65",
        iso_code: "SG",
      },
      {
        country_code_id: 5,
        country_name: "Malaysia",
        phone_code: "+60",
        iso_code: "MY",
      },
    ];
    console.log("Using fallback country codes:", countryCodes.length);
    populateCountryCodeDropdown();
  }
}

/**
 * Populate country code dropdown
 */
function populateCountryCodeDropdown() {
  const select = document.querySelector(".phone-country-code");
  console.log("Populating country code dropdown, found select:", !!select);
  
  if (!select) {
    console.error("Country code select element not found!");
    return;
  }
  
  if (countryCodes.length === 0) {
    console.error("No country codes available to populate!");
    return;
  }
  
  const options = countryCodes
    .map(
      (cc) =>
        `<option value="${cc.country_code_id}" ${
          cc.iso_code === "PH" ? "selected" : ""
        }>
      ${cc.phone_code} ${cc.country_name}
    </option>`
    )
    .join("");
  
  select.innerHTML = options;
  console.log("Populated dropdown with", countryCodes.length, "options");
  
  // Verify dropdown is populated
  if (select.options.length === 0) {
    console.error("Dropdown is still empty after population!");
  } else {
    console.log("Dropdown has", select.options.length, "options");
  }
}

/**
 * Setup all form handlers
 */
function setupFormHandlers() {
  const form = document.getElementById("securityForm");
  const sendCodeBtn = document.querySelector(".btn-send-code");
  const resendLink = document.querySelector(".resend-link");
  const backBtn = document.querySelector(".btn-back");
  const codeInputs = document.querySelectorAll(".code-box");

  // Send code button
  if (sendCodeBtn) {
    sendCodeBtn.addEventListener("click", handleSendCode);
  }

  // Resend code link
  if (resendLink) {
    resendLink.addEventListener("click", function (e) {
      e.preventDefault();
      if (canResend) {
        handleSendCode();
      }
    });
  }

  // Back button - navigate to step 1
  if (backBtn) {
    backBtn.addEventListener("click", function () {
      window.location.href = "customer-onboarding-details.html";
    });
  }

  // Form submission
  if (form) {
    form.addEventListener("submit", handleFormSubmit);
  }

  // Auto-verify when all code boxes are filled
  if (codeInputs.length === 4) {
    codeInputs.forEach((input, index) => {
      input.addEventListener("input", function () {
        if (index === 3 && getAllCodeDigits().length === 4) {
          // Auto-verify when 4th digit is entered
          handleVerifyCode();
        }
      });
    });
  }

  // Real-time password validation
  const passwordInput = document.querySelector('input[name="password"]');
  const confirmPasswordInput = document.querySelector(
    'input[name="confirm_password"]'
  );

  if (passwordInput) {
    passwordInput.addEventListener("input", function () {
      validatePasswordStrength(this.value);
    });
  }

  if (confirmPasswordInput) {
    confirmPasswordInput.addEventListener("blur", function () {
      validatePasswordMatch();
    });
  }
}

/**
 * Handle send verification code
 */
async function handleSendCode() {
  const mobileInput = document.querySelector('input[name="mobile_number"]');
  const countryCodeSelect = document.querySelector(".phone-country-code");
  const sendCodeBtn = document.querySelector(".btn-send-code");

  if (!mobileInput || !mobileInput.value) {
    showError(mobileInput, "Please enter your mobile number");
    return;
  }

  if (!countryCodeSelect || !countryCodeSelect.value) {
    showError(mobileInput, "Please select country code");
    return;
  }

  const mobileNumber = mobileInput.value.trim();
  const countryCode = countryCodes.find(
    (cc) => cc.country_code_id == countryCodeSelect.value
  );

  if (!countryCode) {
    showError(mobileInput, "Invalid country code");
    return;
  }

  const fullPhoneNumber = countryCode.phone_code + mobileNumber;

  // Disable button
  sendCodeBtn.disabled = true;
  sendCodeBtn.textContent = "Sending...";

  try {
    console.log("Sending verification code to:", fullPhoneNumber);

    const response = await fetch(
      `${API_BASE_URL}/customer/send-verification-code.php`,
      {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ phone_number: fullPhoneNumber }),
      }
    );

    console.log("Response status:", response.status);

    if (!response.ok) {
      const errorText = await response.text();
      console.error("Server error response:", errorText);
      throw new Error(`Server returned ${response.status}: ${errorText}`);
    }

    const result = await response.json();
    console.log("Server response:", result);

    if (result.success) {
      isCodeSent = true;
      sendCodeBtn.textContent = "Code Sent";
      sendCodeBtn.classList.add("btn-success");

      // Store session ID for verification
      if (result.session_id) {
        verificationSessionId = result.session_id;
        console.log("Stored session ID:", verificationSessionId);
      }

      // Show success message
      showSuccess(
        mobileInput,
        "Verification code sent! Check your mobile phone."
      );

      // Enable code input boxes
      enableCodeInputs();

      // Start resend timer
      startResendTimer(30);

      // For development/testing: show code in mock display
      if (result.dev_code) {
        console.log("DEV MODE - Verification code:", result.dev_code);
        showMockCode(result.dev_code);
      }
    } else {
      showError(mobileInput, result.message || "Failed to send code");
      sendCodeBtn.disabled = false;
      sendCodeBtn.textContent = "Send Code";
    }
  } catch (error) {
    console.error("Error sending code:", error);
    showError(mobileInput, "Error: " + error.message);
    sendCodeBtn.disabled = false;
    sendCodeBtn.textContent = "Send Code";
  }
}

/**
 * Handle verify code
 */
async function handleVerifyCode() {
  const mobileInput = document.querySelector('input[name="mobile_number"]');
  const countryCodeSelect = document.querySelector(".phone-country-code");
  const code = getAllCodeDigits();

  if (code.length !== 4) {
    showCodeError("Please enter the complete 4-digit code");
    return;
  }

  const mobileNumber = mobileInput.value.trim();
  const countryCode = countryCodes.find(
    (cc) => cc.country_code_id == countryCodeSelect.value
  );

  if (!countryCode) {
    showCodeError("Invalid country code");
    return;
  }

  const fullPhoneNumber = countryCode.phone_code + mobileNumber;

  try {
    const response = await fetch(`${API_BASE_URL}/customer/verify-code.php`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        phone_number: fullPhoneNumber,
        code: code,
        session_id: verificationSessionId, // Include session ID from send-code
      }),
    });

    const result = await response.json();

    if (result.success && result.verified) {
      isCodeVerified = true;
      showCodeSuccess("Code verified successfully! ✓");
      disableCodeInputs();

      // Add visual feedback
      document.querySelectorAll(".code-box").forEach((box) => {
        box.classList.add("verified");
        box.style.borderColor = "#28a745";
        box.style.backgroundColor = "#d4edda";
      });
    } else {
      isCodeVerified = false;
      showCodeError(result.message || "Invalid verification code");
      clearCodeInputs();
    }
  } catch (error) {
    console.error("Error verifying code:", error);
    showCodeError("An error occurred. Please try again.");
  }
}

/**
 * Handle form submission
 */
async function handleFormSubmit(e) {
  e.preventDefault();

  // Clear previous errors
  clearAllErrors();

  // Get form data
  const formData = collectFormData();

  // Validate before submission
  if (!validateForm(formData)) {
    return;
  }

  // Check if mobile is verified
  if (!isCodeVerified) {
    showCodeError("Please verify your mobile number first");
    return;
  }

  try {
    const response = await fetch(`${API_BASE_URL}/customer/create-step2.php`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(formData),
    });

    const result = await response.json();

    if (result.success) {
      // Store step completion in session storage
      sessionStorage.setItem("step2_completed", "true");

      // Redirect to step 3 (review)
      window.location.href =
        result.redirect || "customer-onboarding-review.html";
    } else {
      // Show validation errors
      if (result.errors) {
        displayErrors(result.errors);
      } else {
        alert(result.message || "An error occurred");
      }
    }
  } catch (error) {
    console.error("Error submitting form:", error);
    alert("An error occurred while submitting the form");
  }
}

/**
 * Collect form data
 */
function collectFormData() {
  const passwordInput = document.querySelector('input[name="password"]');
  const confirmPasswordInput = document.querySelector(
    'input[name="confirm_password"]'
  );
  const mobileInput = document.querySelector('input[name="mobile_number"]');
  const countryCodeSelect = document.querySelector(".phone-country-code");

  // Get full phone number with country code
  let fullPhoneNumber = "";
  if (mobileInput && countryCodeSelect) {
    const countryCode = countryCodes.find(
      (cc) => cc.country_code_id == countryCodeSelect.value
    );
    if (countryCode) {
      fullPhoneNumber = countryCode.phone_code + mobileInput.value.trim();
    }
  }

  return {
    password: passwordInput?.value || "",
    confirm_password: confirmPasswordInput?.value || "",
    mobile_number: fullPhoneNumber,
  };
}

/**
 * Validate form before submission
 */
function validateForm(data) {
  let isValid = true;

  // Validate password
  if (!data.password) {
    showFieldError("password", "Password is required");
    isValid = false;
  }

  // Validate confirm password
  if (data.password !== data.confirm_password) {
    showFieldError("confirm_password", "Passwords do not match");
    isValid = false;
  }

  // Validate mobile
  if (!data.mobile_number) {
    showFieldError("mobile_number", "Mobile number is required");
    isValid = false;
  }

  return isValid;
}

/**
 * Validate password strength
 */
function validatePasswordStrength(password) {
  const input = document.querySelector('input[name="password"]');
  const messages = [];

  if (password.length < 8) {
    messages.push("at least 8 characters");
  }
  if (!/[A-Z]/.test(password)) {
    messages.push("one uppercase letter");
  }
  if (!/[a-z]/.test(password)) {
    messages.push("one lowercase letter");
  }
  if (!/[0-9]/.test(password)) {
    messages.push("one number");
  }
  if (!/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
    messages.push("one special character");
  }

  if (messages.length > 0) {
    showError(input, "Password must contain " + messages.join(", "));
    return false;
  }

  hideError(input);
  return true;
}

/**
 * Validate password match
 */
function validatePasswordMatch() {
  const passwordInput = document.querySelector('input[name="password"]');
  const confirmInput = document.querySelector('input[name="confirm_password"]');

  if (passwordInput.value !== confirmInput.value) {
    showError(confirmInput, "Passwords do not match");
    return false;
  }

  hideError(confirmInput);
  return true;
}

/**
 * Get all code digits
 */
function getAllCodeDigits() {
  const codeInputs = document.querySelectorAll(".code-box");
  return Array.from(codeInputs)
    .map((input) => input.value)
    .join("");
}

/**
 * Clear code inputs
 */
function clearCodeInputs() {
  const codeInputs = document.querySelectorAll(".code-box");
  codeInputs.forEach((input) => {
    input.value = "";
  });
  codeInputs[0]?.focus();
}

/**
 * Enable code inputs
 */
function enableCodeInputs() {
  const codeInputs = document.querySelectorAll(".code-box");
  codeInputs.forEach((input) => {
    input.disabled = false;
  });
  codeInputs[0]?.focus();
}

/**
 * Disable code inputs
 */
function disableCodeInputs() {
  const codeInputs = document.querySelectorAll(".code-box");
  codeInputs.forEach((input) => {
    input.disabled = true;
  });
}

/**
 * Start resend timer
 */
function startResendTimer(seconds) {
  canResend = false;
  let remaining = seconds;

  const timerText = document.querySelector(".timer-text");
  const resendLink = document.querySelector(".resend-link");

  if (resendLink) {
    resendLink.style.pointerEvents = "none";
    resendLink.style.opacity = "0.5";
  }

  resendTimer = setInterval(() => {
    remaining--;
    if (timerText) {
      timerText.textContent = `(You can request a new code in ${remaining} seconds)`;
    }

    if (remaining <= 0) {
      clearInterval(resendTimer);
      canResend = true;
      if (timerText) {
        timerText.textContent = "";
      }
      if (resendLink) {
        resendLink.style.pointerEvents = "auto";
        resendLink.style.opacity = "1";
      }
    }
  }, 1000);
}

/**
 * Show error message for a field
 */
function showError(inputElement, message) {
  inputElement.classList.add("error");
  const parent = inputElement.closest(".form-group");
  if (parent) {
    let errorDiv = parent.querySelector(".error-message");
    if (!errorDiv) {
      errorDiv = document.createElement("div");
      errorDiv.className = "error-message";
      errorDiv.style.color = "#dc3545";
      errorDiv.style.fontSize = "12px";
      errorDiv.style.marginTop = "4px";
      parent.appendChild(errorDiv);
    }
    errorDiv.textContent = message;
    errorDiv.style.display = "block";
  }
}

/**
 * Hide error message
 */
function hideError(inputElement) {
  inputElement.classList.remove("error");
  const parent = inputElement.closest(".form-group");
  if (parent) {
    const errorDiv = parent.querySelector(".error-message");
    if (errorDiv) {
      errorDiv.style.display = "none";
    }
  }
}

/**
 * Show success message
 */
function showSuccess(inputElement, message) {
  const parent = inputElement.closest(".form-group");
  if (parent) {
    let successDiv = parent.querySelector(".success-message");
    if (!successDiv) {
      successDiv = document.createElement("div");
      successDiv.className = "success-message";
      successDiv.style.color = "#28a745";
      successDiv.style.fontSize = "12px";
      successDiv.style.marginTop = "4px";
      parent.appendChild(successDiv);
    }
    successDiv.textContent = message;
    successDiv.style.display = "block";

    // Hide after 5 seconds
    setTimeout(() => {
      successDiv.style.display = "none";
    }, 5000);
  }
}

/**
 * Show code error
 */
function showCodeError(message) {
  const codeGroup = document.querySelector(".code-inputs").parentElement;
  let errorDiv = codeGroup.querySelector(".code-error");
  if (!errorDiv) {
    errorDiv = document.createElement("div");
    errorDiv.className = "code-error";
    errorDiv.style.color = "#dc3545";
    errorDiv.style.fontSize = "12px";
    errorDiv.style.marginTop = "8px";
    codeGroup.appendChild(errorDiv);
  }
  errorDiv.textContent = message;
  errorDiv.style.display = "block";
}

/**
 * Show code success
 */
function showCodeSuccess(message) {
  const codeGroup = document.querySelector(".code-inputs").parentElement;
  let successDiv = codeGroup.querySelector(".code-success");
  if (!successDiv) {
    successDiv = document.createElement("div");
    successDiv.className = "code-success";
    successDiv.style.color = "#28a745";
    successDiv.style.fontSize = "12px";
    successDiv.style.marginTop = "8px";
    successDiv.style.fontWeight = "bold";
    codeGroup.appendChild(successDiv);
  }
  successDiv.textContent = message;
  successDiv.style.display = "block";

  // Hide code error if exists
  const errorDiv = codeGroup.querySelector(".code-error");
  if (errorDiv) {
    errorDiv.style.display = "none";
  }
}

/**
 * Show field error by field name
 */
function showFieldError(fieldName, message) {
  const input = document.querySelector(`input[name="${fieldName}"]`);
  if (input) {
    showError(input, message);
  }
}

/**
 * Display validation errors from server
 */
function displayErrors(errors) {
  for (let [field, message] of Object.entries(errors)) {
    showFieldError(field, message);
  }
}

/**
 * Show mock code display for testing
 */
function showMockCode(code) {
  const mockDisplay = document.getElementById("mockCodeDisplay");
  const mockValue = document.getElementById("mockCodeValue");

  if (mockDisplay && mockValue) {
    mockValue.textContent = code;
    mockDisplay.style.display = "block";

    // Add click to copy functionality
    mockValue.addEventListener("click", function () {
      // Copy to clipboard
      navigator.clipboard
        .writeText(code)
        .then(() => {
          const originalText = mockValue.textContent;
          mockValue.textContent = "✓ Copied!";
          setTimeout(() => {
            mockValue.textContent = originalText;
          }, 1000);
        })
        .catch((err) => {
          console.error("Failed to copy:", err);
        });
    });

    // Auto-fill code after 2 seconds
    setTimeout(() => {
      if (confirm("Auto-fill verification code? (Test Mode Only)")) {
        autoFillCode(code);
      }
    }, 2000);
  }
}

/**
 * Auto-fill verification code (testing only)
 */
function autoFillCode(code) {
  const codeInputs = document.querySelectorAll(".code-box");
  const digits = code.split("");

  codeInputs.forEach((input, index) => {
    if (digits[index]) {
      input.value = digits[index];
    }
  });

  // Trigger verification
  setTimeout(() => {
    handleVerifyCode();
  }, 500);
}

/**
 * Clear all errors
 */
function clearAllErrors() {
  document
    .querySelectorAll(".form-control")
    .forEach((el) => el.classList.remove("error"));
  document.querySelectorAll(".error-message").forEach((el) => {
    el.style.display = "none";
  });
  document.querySelectorAll(".success-message").forEach((el) => {
    el.style.display = "none";
  });
}
