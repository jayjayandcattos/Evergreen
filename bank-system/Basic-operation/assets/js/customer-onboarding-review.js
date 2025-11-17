/**
 * Customer Onboarding Review Page
 * Step 3: Review and Submit
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
let sessionData = null;
let editMode = {};
let originalValues = {};

/**
 * Initialize page on load
 */
document.addEventListener("DOMContentLoaded", function () {
  loadSessionData();
  setupEventListeners();
});

/**
 * Setup event listeners
 */
function setupEventListeners() {
  // Terms checkbox
  const termsCheckbox = document.getElementById("terms-checkbox");
  if (termsCheckbox) {
    termsCheckbox.addEventListener("change", function () {
      clearTermsError();
    });
  }

  // Edit buttons for each section
  const editButtons = document.querySelectorAll(".btn-edit-icon");
  editButtons.forEach((btn) => {
    btn.addEventListener("click", function () {
      const section = this.closest(".detail-section");
      if (section) {
        const sectionId = section
          .querySelector(".section-subtitle")
          .textContent.trim()
          .toLowerCase()
          .replace(/\s+/g, "-");
        editSection(sectionId);
      }
    });
  });

  // Back button
  const backBtn = document.getElementById("back-btn");
  if (backBtn) {
    backBtn.addEventListener("click", goBack);
  }

  // Submit button
  const submitBtn = document.getElementById("submit-btn");
  if (submitBtn) {
    submitBtn.addEventListener("click", submitApplication);
  }
}

/**
 * Load session data from backend
 */
async function loadSessionData() {
  try {
    const response = await fetch(
      `${API_BASE_URL}/customer/get-session-data.php`,
      {
        method: "GET",
        credentials: "include",
      }
    );

    const result = await response.json();

    if (result.success && result.data) {
      sessionData = result.data;
      populateReviewData(sessionData);
    } else {
      showGlobalError("Session expired. Please start from the beginning.");
      setTimeout(() => {
        window.location.href = "customer-onboarding-details.html";
      }, 2000);
    }
  } catch (error) {
    console.error("Error loading session data:", error);
    showGlobalError("Error loading your information. Please try again.");
    setTimeout(() => {
      window.location.href = "customer-onboarding-details.html";
    }, 2000);
  }
}

/**
 * Populate review fields with session data
 */
function populateReviewData(data) {
  // Personal Information - Full Name
  const fullName = `${data.first_name || ""} ${data.middle_name || ""} ${
    data.last_name || ""
  }`.trim();
  setFieldValue("review-full-name", fullName);

  // Map field names from Step 1 form to review display
  setFieldValue(
    "review-birth-date",
    formatDate(data.date_of_birth || data.birth_date)
  );
  setFieldValue("review-birth-place", data.place_of_birth || data.birth_place);
  setFieldValue("review-gender", data.gender);
  setFieldValue(
    "review-civil-status",
    data.marital_status || data.civil_status
  );
  setFieldValue("review-nationality", data.nationality);

  // Address fields
  setFieldValue("review-address", data.address_line || data.street);
  setFieldValue("review-city", data.city);
  setFieldValue("review-province", data.province || data.province_name);
  setFieldValue("review-postal-code", data.postal_code);

  // Contact Information - handle arrays from Step 1
  const email =
    Array.isArray(data.emails) && data.emails.length > 0
      ? data.emails[0]
      : data.email || "";
  const mobile =
    data.mobile_number ||
    (Array.isArray(data.phones) && data.phones.length > 0
      ? data.phones[0]
      : "");

  // Email is shown in both Contact Details and Account Security sections
  setFieldValue("review-email", email);
  setFieldValue("review-mobile", formatPhoneNumber(mobile));

  // Employment Information - handle different field names
  const occupation = data.occupation || data.employment_status || "";
  const employer = data.employer_name || "";
  const income = data.annual_income || data.source_of_funds || "";

  setFieldValue("review-occupation", occupation);
  setFieldValue("review-employer", employer);
  setFieldValue("review-annual-income", formatCurrency(income));

  // Account Type
  const accountType = data.account_type || "Savings";
  setFieldValue("review-account-type", accountType + " Account");
  
  // Note: Email is already set above and will be shown in Account Security section as username
}

/**
 * Set field value with fallback
 */
function setFieldValue(elementId, value) {
  const element = document.getElementById(elementId);
  if (element) {
    element.textContent = value || "Not provided";
  }
}

/**
 * Format date for display
 */
function formatDate(dateString) {
  if (!dateString) return "Not provided";

  const date = new Date(dateString);
  const options = { year: "numeric", month: "long", day: "numeric" };
  return date.toLocaleDateString("en-US", options);
}

/**
 * Format phone number for display
 */
function formatPhoneNumber(phoneNumber) {
  if (!phoneNumber) return "Not provided";

  // If it starts with +, format it nicely
  if (phoneNumber.startsWith("+")) {
    // Format as +XX XXX XXX XXXX
    const cleaned = phoneNumber.replace(/\D/g, "");
    if (cleaned.length >= 10) {
      return `+${cleaned.slice(0, 2)} ${cleaned.slice(2, 5)} ${cleaned.slice(
        5,
        8
      )} ${cleaned.slice(8)}`;
    }
  }

  return phoneNumber;
}

/**
 * Format currency for display
 */
function formatCurrency(amount) {
  if (!amount) return "Not provided";

  // If it's already a string description (like "Employment"), return as is
  if (isNaN(amount)) {
    return amount;
  }

  // Convert to number if string
  const numAmount = typeof amount === "string" ? parseFloat(amount) : amount;

  if (isNaN(numAmount)) return amount;

  return new Intl.NumberFormat("en-PH", {
    style: "currency",
    currency: "PHP",
  }).format(numAmount);
}

/**
 * Edit a specific section
 */
function editSection(sectionId) {
  const section = document.querySelector(`#section-${sectionId}`);
  if (!section) return;

  if (editMode[sectionId]) {
    // Save mode - save the changes
    saveSection(sectionId);
  } else {
    // Edit mode - make fields editable
    enableEditMode(sectionId, section);
  }
}

/**
 * Enable edit mode for a section
 */
function enableEditMode(sectionId, section) {
  editMode[sectionId] = true;
  originalValues[sectionId] = {};

  const valueElements = section.querySelectorAll(".value");
  valueElements.forEach((el) => {
    const id = el.id;

    // For security section, always require password reentry
    if (sectionId === "account-security" && id === "review-password") {
      originalValues[sectionId][id] = "••••••••";
      el.innerHTML = `
        <div class="password-edit-container">
          <input type="password" class="form-control form-control-sm mb-2" id="edit-new-password" placeholder="Enter Password" required>
          <input type="password" class="form-control form-control-sm" id="edit-confirm-password" placeholder="Confirm Password" required>
        </div>
      `;
      return;
    }

    // For username in security section, create regular input
    if (sectionId === "account-security" && id === "review-username") {
      originalValues[sectionId][id] = el.textContent;
      const currentValue =
        el.textContent === "Not provided" ? "" : el.textContent;

      const inputElement = document.createElement("input");
      inputElement.type = "text";
      inputElement.value = currentValue;
      inputElement.className = "form-control form-control-sm";
      inputElement.id = `edit-${id}`;
      inputElement.style.width = "100%";

      el.innerHTML = "";
      el.appendChild(inputElement);
      return;
    }

    // Skip password display field in non-security sections
    if (el.textContent.includes("••••")) {
      return;
    }

    // For all other fields
    originalValues[sectionId][id] = el.textContent;

    const currentValue =
      el.textContent === "Not provided" ? "" : el.textContent;

    // Create appropriate input based on field type
    let inputElement;

    // Check if this field should be a dropdown
    if (id === "review-gender") {
      // Gender dropdown
      inputElement = document.createElement("select");
      inputElement.className = "form-select form-select-sm";
      inputElement.innerHTML = `
          <option value="">Select Gender</option>
          <option value="Male" ${
            currentValue === "Male" ? "selected" : ""
          }>Male</option>
          <option value="Female" ${
            currentValue === "Female" ? "selected" : ""
          }>Female</option>
          <option value="Other" ${
            currentValue === "Other" ? "selected" : ""
          }>Other</option>
        `;
    } else if (id === "review-civil-status") {
      // Civil Status dropdown
      inputElement = document.createElement("select");
      inputElement.className = "form-select form-select-sm";
      inputElement.innerHTML = `
          <option value="">Select Civil Status</option>
          <option value="Single" ${
            currentValue === "Single" ? "selected" : ""
          }>Single</option>
          <option value="Married" ${
            currentValue === "Married" ? "selected" : ""
          }>Married</option>
          <option value="Widowed" ${
            currentValue === "Widowed" ? "selected" : ""
          }>Widowed</option>
          <option value="Separated" ${
            currentValue === "Separated" ? "selected" : ""
          }>Separated</option>
          <option value="Divorced" ${
            currentValue === "Divorced" ? "selected" : ""
          }>Divorced</option>
        `;
    } else if (id === "review-nationality") {
      // Nationality dropdown
      inputElement = document.createElement("select");
      inputElement.className = "form-select form-select-sm";
      inputElement.innerHTML = `
          <option value="">Select Nationality</option>
          <option value="Filipino" ${
            currentValue === "Filipino" ? "selected" : ""
          }>Filipino</option>
          <option value="American" ${
            currentValue === "American" ? "selected" : ""
          }>American</option>
          <option value="Chinese" ${
            currentValue === "Chinese" ? "selected" : ""
          }>Chinese</option>
          <option value="Japanese" ${
            currentValue === "Japanese" ? "selected" : ""
          }>Japanese</option>
          <option value="Korean" ${
            currentValue === "Korean" ? "selected" : ""
          }>Korean</option>
          <option value="Other" ${
            currentValue.includes("Other") ||
            (![
              "Filipino",
              "American",
              "Chinese",
              "Japanese",
              "Korean",
            ].includes(currentValue) &&
              currentValue)
              ? "selected"
              : ""
          }>Other</option>
        `;
    } else if (id === "review-occupation") {
      // Occupation/Employment Status dropdown
      inputElement = document.createElement("select");
      inputElement.className = "form-select form-select-sm";
      inputElement.innerHTML = `
          <option value="">Select Employment Status</option>
          <option value="Employed" ${
            currentValue === "Employed" ? "selected" : ""
          }>Employed</option>
          <option value="Self-Employed" ${
            currentValue === "Self-Employed" ? "selected" : ""
          }>Self-Employed</option>
          <option value="Unemployed" ${
            currentValue === "Unemployed" ? "selected" : ""
          }>Unemployed</option>
          <option value="Student" ${
            currentValue === "Student" ? "selected" : ""
          }>Student</option>
          <option value="Retired" ${
            currentValue === "Retired" ? "selected" : ""
          }>Retired</option>
        `;
    } else if (id === "review-birth-date") {
      // Date input
      inputElement = document.createElement("input");
      inputElement.type = "date";
      inputElement.className = "form-control form-control-sm";
      // Try to parse the date
      if (currentValue) {
        const date = new Date(currentValue);
        if (!isNaN(date)) {
          inputElement.value = date.toISOString().split("T")[0];
        }
      }
    } else {
      // Regular text input
      inputElement = document.createElement("input");
      inputElement.type = "text";
      inputElement.value = currentValue;
      inputElement.className = "form-control form-control-sm";
    }

    inputElement.id = `edit-${id}`;
    inputElement.style.width = "100%";

    // Replace text with input/select
    el.innerHTML = "";
    el.appendChild(inputElement);
  });

  // Update buttons - both Save and Cancel in header
  const btnContainer = section.querySelector(".section-header-inline");
  const editBtn = btnContainer.querySelector(".btn-edit-icon");

  if (editBtn) {
    // Hide the edit button and create new Save/Cancel buttons
    editBtn.style.display = "none";
  }

  // Create button group container
  const buttonGroup = document.createElement("div");
  buttonGroup.className = "edit-button-group";
  buttonGroup.style.display = "flex";
  buttonGroup.style.gap = "0.5rem";

  // Create Save button
  const saveBtn = document.createElement("button");
  saveBtn.type = "button";
  saveBtn.className = "btn-edit-icon btn-save-edit";
  saveBtn.innerHTML = "<span>Save</span>";
  saveBtn.style.backgroundColor = "#003631";
  saveBtn.style.color = "white";
  saveBtn.style.border = "none";
  saveBtn.onclick = () => saveSection(sectionId);

  // Create Cancel button
  const cancelBtn = document.createElement("button");
  cancelBtn.type = "button";
  cancelBtn.className = "btn-edit-icon btn-cancel-edit";
  cancelBtn.innerHTML = "<span>Cancel</span>";
  cancelBtn.style.backgroundColor = "#6c757d";
  cancelBtn.style.color = "white";
  cancelBtn.style.border = "none";
  cancelBtn.onclick = () => cancelEdit(sectionId);

  buttonGroup.appendChild(saveBtn);
  buttonGroup.appendChild(cancelBtn);
  btnContainer.appendChild(buttonGroup);
}

/**
 * Save section changes
 */
async function saveSection(sectionId) {
  const section = document.querySelector(`#section-${sectionId}`);
  const updatedData = {};
  let hasError = false;

  // For security section, validate password fields
  if (sectionId === "account-security") {
    const newPassword = document.getElementById("edit-new-password");
    const confirmPassword = document.getElementById("edit-confirm-password");

    if (
      !newPassword ||
      !confirmPassword ||
      !newPassword.value ||
      !confirmPassword.value
    ) {
      showSuccessMessage(
        "Please enter both password fields!",
        "error",
        section
      );
      return;
    }

    if (newPassword.value !== confirmPassword.value) {
      showSuccessMessage("Passwords do not match!", "error", section);
      return;
    }

    // Validate password requirements
    const password = newPassword.value;
    const errors = [];

    if (password.length < 8) {
      errors.push("at least 8 characters");
    }
    if (!/[A-Z]/.test(password)) {
      errors.push("one uppercase letter");
    }
    if (!/[a-z]/.test(password)) {
      errors.push("one lowercase letter");
    }
    if (!/[0-9]/.test(password)) {
      errors.push("one number");
    }
    if (!/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
      errors.push("one special character");
    }

    if (errors.length > 0) {
      showSuccessMessage(
        "Password must contain " + errors.join(", ") + "!",
        "error",
        section
      );
      return;
    }

    // Hash password and update
    updatedData.password = newPassword.value;
    updatedData.confirm_password = confirmPassword.value;
  }

  // Collect new values from inputs and selects
  const inputs = section.querySelectorAll(
    "input.form-control, input.form-control-sm, select.form-select, select.form-select-sm"
  );
  inputs.forEach((input) => {
    // Skip password fields as they're handled separately
    if (
      input.id === "edit-new-password" ||
      input.id === "edit-confirm-password"
    ) {
      return;
    }

    const fieldId = input.id.replace("edit-", "");
    let newValue = input.value.trim();

    // Validate that non-password fields are not empty
    if (!newValue || (input.tagName === "SELECT" && newValue === "")) {
      hasError = true;
      // Add visual feedback
      input.classList.add("is-invalid");
      // Remove invalid class on change
      input.addEventListener(
        "input",
        function () {
          this.classList.remove("is-invalid");
        },
        { once: true }
      );
    } else {
      input.classList.remove("is-invalid");
    }

    // For date inputs, format nicely
    if (input.type === "date" && newValue) {
      const date = new Date(newValue);
      newValue = date.toISOString().split("T")[0]; // Keep YYYY-MM-DD format for storage
    }

    // Map field IDs to session data keys
    const fieldMap = {
      "review-full-name": "full_name",
      "review-birth-date": "date_of_birth",
      "review-birth-place": "place_of_birth",
      "review-gender": "gender",
      "review-civil-status": "marital_status",
      "review-nationality": "nationality",
      "review-address": "address_line",
      "review-city": "city",
      "review-province": "province",
      "review-postal-code": "postal_code",
      "review-email": "email",
      "review-mobile": "mobile_number",
      "review-occupation": "employment_status",
      "review-employer": "employer_name",
      "review-annual-income": "annual_income",
      "review-username": "username",
    };

    const dataKey = fieldMap[fieldId];
    if (dataKey && newValue) {
      updatedData[dataKey] = newValue;
    }
  });

  // If there are validation errors, show message and stop
  if (hasError) {
    showSuccessMessage("Please fill in all required fields!", "error", section);
    return;
  }

  try {
    // Update session data
    const response = await fetch(
      `${API_BASE_URL}/customer/update-session.php`,
      {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        credentials: "include",
        body: JSON.stringify(updatedData),
      }
    );

    const result = await response.json();

    if (result.success) {
      // Reload session data
      await loadSessionData();

      // Exit edit mode
      disableEditMode(sectionId, section);

      // Show success message instead of alert
      showSuccessMessage("Changes saved successfully!", "success", section);
    } else {
      showSuccessMessage(
        "Error: " + (result.message || "Unknown error"),
        "error",
        section
      );
    }
  } catch (error) {
    console.error("Error saving changes:", error);
    showSuccessMessage(
      "Error saving changes. Please try again.",
      "error",
      section
    );
  }
}

/**
 * Show success/error message inline
 */
function showSuccessMessage(message, type = "success", section) {
  // Remove any existing message
  const existingMsg = section.querySelector(".edit-message");
  if (existingMsg) {
    existingMsg.remove();
  }

  // Create message element
  const msgEl = document.createElement("div");
  msgEl.className = `edit-message alert alert-${
    type === "success" ? "success" : "danger"
  } mt-2`;
  msgEl.style.padding = "0.5rem 1rem";
  msgEl.style.fontSize = "0.9rem";
  msgEl.textContent = message;

  // Insert after section header
  const header = section.querySelector(".section-header-inline");
  header.parentNode.insertBefore(msgEl, header.nextSibling);

  // Auto-remove after 3 seconds for success messages
  if (type === "success") {
    setTimeout(() => {
      msgEl.remove();
    }, 3000);
  }
}

/**
 * Cancel edit mode
 */
function cancelEdit(sectionId) {
  const section = document.querySelector(`#section-${sectionId}`);

  // Restore original values
  Object.keys(originalValues[sectionId] || {}).forEach((id) => {
    const el = document.getElementById(id);
    if (el) {
      el.textContent = originalValues[sectionId][id];
    }
  });

  disableEditMode(sectionId, section);
}

/**
 * Disable edit mode
 */
function disableEditMode(sectionId, section) {
  editMode[sectionId] = false;

  // Show the original edit button again
  const editBtn = section.querySelector(
    ".btn-edit-icon:not(.btn-save-edit):not(.btn-cancel-edit)"
  );
  if (editBtn) {
    editBtn.style.display = "";
  }

  // Remove button group
  const buttonGroup = section.querySelector(".edit-button-group");
  if (buttonGroup) {
    buttonGroup.remove();
  }

  // Remove any success/error messages
  const msgEl = section.querySelector(".edit-message");
  if (msgEl) {
    msgEl.remove();
  }
}

/**
 * Go back to previous step
 */
function goBack() {
  window.location.href = "customer-onboarding-security.html";
}

/**
 * Validate terms acceptance
 */
function validateTerms() {
  const termsCheckbox = document.getElementById("terms-checkbox");
  const termsError = document.getElementById("terms-error");

  if (!termsCheckbox.checked) {
    termsError.textContent = "You must accept the terms and conditions";
    termsCheckbox.parentElement.classList.add("is-invalid");
    return false;
  }

  return true;
}

/**
 * Clear terms error
 */
function clearTermsError() {
  const termsError = document.getElementById("terms-error");
  const termsCheckbox = document.getElementById("terms-checkbox");

  if (termsError) {
    termsError.textContent = "";
  }

  if (termsCheckbox) {
    termsCheckbox.parentElement.classList.remove("is-invalid");
  }
}

/**
 * Submit final application
 */
async function submitApplication() {
  // Validate terms
  if (!validateTerms()) {
    return;
  }

  const submitBtn = document.getElementById("submit-btn");
  const btnText = submitBtn.querySelector(".btn-text");
  const spinner = submitBtn.querySelector(".spinner-border");

  try {
    // Disable button and show spinner
    submitBtn.disabled = true;
    btnText.classList.add("d-none");
    spinner.classList.remove("d-none");

    const response = await fetch(`${API_BASE_URL}/customer/create-final.php`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      credentials: "include",
    });

    const result = await response.json();

    if (result.success) {
      // Show success modal with account number
      showSuccessModal(result.account_number);
    } else {
      // Handle errors with inline message
      let errorMsg = "";
      if (result.errors) {
        errorMsg = "Please fix the following errors: ";
        const errorList = [];
        for (const field in result.errors) {
          errorList.push(result.errors[field]);
        }
        errorMsg += errorList.join(", ");
      } else {
        errorMsg = result.message || "An error occurred during submission";
      }

      showGlobalError(errorMsg);

      // Re-enable button
      submitBtn.disabled = false;
      btnText.classList.remove("d-none");
      spinner.classList.add("d-none");
    }
  } catch (error) {
    console.error("Error submitting application:", error);
    showGlobalError("An error occurred while submitting your application");

    // Re-enable button
    submitBtn.disabled = false;
    btnText.classList.remove("d-none");
    spinner.classList.add("d-none");
  }
}

/**
 * Show success modal
 */
function showSuccessModal(accountNumber) {
  const accountNumberEl = document.getElementById("account-number");
  if (accountNumberEl) {
    accountNumberEl.textContent = accountNumber;
  }

  const successModal = new bootstrap.Modal(
    document.getElementById("successModal")
  );
  successModal.show();
}

/**
 * Go to login page
 */
function goToLogin() {
  // Clear session storage
  sessionStorage.clear();

  // Redirect to login (update this URL as needed)
  window.location.href = "../index.html";
}

/**
 * Show global error message at top of form
 */
function showGlobalError(message) {
  // Remove existing global error
  const existingError = document.querySelector(".global-error-message");
  if (existingError) {
    existingError.remove();
  }

  // Create error element
  const errorEl = document.createElement("div");
  errorEl.className = "global-error-message alert alert-danger";
  errorEl.style.marginBottom = "20px";
  errorEl.textContent = message;

  // Insert at top of review card
  const reviewCard = document.querySelector(".review-card");
  if (reviewCard) {
    reviewCard.insertBefore(errorEl, reviewCard.firstChild);

    // Auto-remove after 5 seconds
    setTimeout(() => {
      errorEl.remove();
    }, 5000);
  }
}
