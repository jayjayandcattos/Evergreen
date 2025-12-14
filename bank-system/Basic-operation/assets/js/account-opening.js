// Detect API path dynamically based on current page location
function getApiBaseUrl() {
  // Get the current page path
  const currentPath = window.location.pathname;

  // If we're in /public/, go up one level to find /api/
  if (currentPath.includes("/public/")) {
    const basePath = currentPath.substring(0, currentPath.indexOf("/public/"));
    return window.location.origin + basePath + "/api";
  }

  // Fallback: construct from known structure
  const pathParts = currentPath.split("/");
  const basicOpIndex = pathParts.indexOf("Basic-operation");
  if (basicOpIndex !== -1) {
    const basePath = pathParts.slice(0, basicOpIndex + 1).join("/");
    return window.location.origin + basePath + "/api";
  }

  // Final fallback
  return window.location.origin + "/Evergreen/bank-system/Basic-operation/api";
}

// API Base URL
const API_BASE_URL = getApiBaseUrl();
console.log("API Base URL:", API_BASE_URL);

// Initialize on page load
document.addEventListener("DOMContentLoaded", function () {
  setupAccountTypeSelection();
  setupFormHandlers();
  loadCustomerAccounts();
  updateSubmitButtonState(); // Initialize button state (should be disabled)
});

// Setup account type card selection
function setupAccountTypeSelection() {
  const accountCards = document.querySelectorAll(".account-type-card");
  const hiddenInput = document.getElementById("selected_account_type");

  accountCards.forEach((card) => {
    card.addEventListener("click", function () {
      // Remove selected class from all cards
      accountCards.forEach((c) => c.classList.remove("selected"));

      // Add selected class to clicked card
      this.classList.add("selected");

      // Set hidden input value
      const shortType = this.getAttribute("data-type");
      const typeMap = {
        Savings: "Savings Account",
        Checking: "Checking Account",
      };
      hiddenInput.value = typeMap[shortType] || shortType;

      // Clear error
      clearError("account_type");

      // Update submit button state
      updateSubmitButtonState();
    });
  });
}

// Customer accounts cache
let customerAccounts = [];

// Account verification state
let isAccountVerified = false;
let verifiedAccountInfo = null;

// Setup form handlers
function setupFormHandlers() {
  const form = document.getElementById("accountOpeningForm");

  form.addEventListener("submit", handleFormSubmit);

  // Validate existing account number
  const existingAccountInput = document.getElementById(
    "existing_account_number"
  );
  if (existingAccountInput) {
    // Add debounce for account verification
    let verifyTimeout;
    existingAccountInput.addEventListener("input", function (e) {
      clearTimeout(verifyTimeout);
      const accountNumber = e.target.value.trim();

      if (accountNumber.length > 0) {
        verifyTimeout = setTimeout(() => {
          verifyExistingAccount(accountNumber);
        }, 500); // Wait 500ms after user stops typing
      } else {
        isAccountVerified = false;
        verifiedAccountInfo = null;
        updateAccountVerificationStatus("", false);
        clearError("existing_account_number");
      }
    });
  }

  // Validate initial deposit on input
  const initialDepositInput = document.getElementById("initial_deposit");
  if (initialDepositInput) {
    initialDepositInput.addEventListener("input", validateInitialDeposit);
    initialDepositInput.addEventListener("input", handleDepositAmountChange);
  }

  // Handle deposit source selection
  const depositSourceSelect = document.getElementById("deposit_source");
  if (depositSourceSelect) {
    depositSourceSelect.addEventListener("change", handleDepositSourceChange);
  }

  // Handle source account selection
  const sourceAccountSelect = document.getElementById("source_account_number");
  if (sourceAccountSelect) {
    sourceAccountSelect.addEventListener("change", handleSourceAccountChange);
  }

  // Handle ID image uploads with preview
  const idFrontInput = document.getElementById("id_front_image");
  if (idFrontInput) {
    idFrontInput.addEventListener("change", function (e) {
      handleImagePreview(e, "id_front_preview");
    });
  }

  const idBackInput = document.getElementById("id_back_image");
  if (idBackInput) {
    idBackInput.addEventListener("change", function (e) {
      handleImagePreview(e, "id_back_preview");
    });
  }
}

// Validate initial deposit
function validateInitialDeposit(e) {
  const value = parseFloat(e.target.value);

  if (value < 0) {
    showError("initial_deposit", "Initial deposit cannot be negative");
    return false;
  }

  clearError("initial_deposit");
  return true;
}

// Current step tracking
let currentStep = 1;

// Handle form submission
async function handleFormSubmit(e) {
  e.preventDefault();

  // Clear previous errors and success message
  clearAllErrors();
  hideSuccessMessage();

  if (currentStep === 1) {
    // Step 1: Validate and show review
    if (!validateForm()) {
      return;
    }
    showReviewStep();
  } else if (currentStep === 2) {
    // Step 2: Submit to API
    await submitAccountOpening();
  }
}

// Show review step
function showReviewStep() {
  // Hide step 1 content
  document.querySelector(".form-section form").style.display = "none";

  // Show review section
  let reviewSection = document.getElementById("review-section");
  if (!reviewSection) {
    reviewSection = createReviewSection();
    document
      .querySelector(".form-section")
      .insertBefore(
        reviewSection,
        document.querySelector(".form-section form")
      );
  }
  reviewSection.style.display = "block";

  // Populate review data
  populateReviewData();

  // Update sidebar
  document.querySelectorAll(".sidebar .nav-item").forEach((item, index) => {
    if (index === 0) {
      item.classList.remove("active");
    } else if (index === 1) {
      item.classList.add("active");
    }
  });

  currentStep = 2;
}

// Submit account opening to API
async function submitAccountOpening() {
  // Disable submit button
  const submitBtn = document.getElementById("submit-btn");
  submitBtn.disabled = true;
  submitBtn.innerHTML =
    '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

  try {
    // Collect form data (returns FormData object with files)
    const formData = collectFormData();

    // Send to API with FormData (no Content-Type header needed for multipart/form-data)
    const response = await fetch(`${API_BASE_URL}/customer/open-account.php`, {
      method: "POST",
      credentials: "include", // Include cookies for session
      body: formData, // Send FormData directly (browsers set correct headers automatically)
    });

    if (!response.ok) {
      const errorText = await response.text();
      console.error("Server error:", errorText);
      console.error("Response status:", response.status);
      throw new Error(
        `Server error (${response.status}). Please check console for details.`
      );
    }

    const result = await response.json();
    console.log("Server response:", result);

    if (result.success) {
      // Show success modal with application number
      document.getElementById("modalApplicationNumber").textContent =
        result.application_number;
      const successModal = new bootstrap.Modal(
        document.getElementById("successModal")
      );
      successModal.show();

      // Reset form
      document.getElementById("accountOpeningForm").reset();
      document.querySelectorAll(".account-type-card").forEach((card) => {
        card.classList.remove("selected");
      });
      document.getElementById("selected_account_type").value = "";

      // Clear image previews
      document.getElementById("id_front_preview").innerHTML = "";
      document.getElementById("id_front_preview").classList.remove("show");
      document.getElementById("id_back_preview").innerHTML = "";
      document.getElementById("id_back_preview").classList.remove("show");

      // Reset verification state
      isAccountVerified = false;
      verifiedAccountInfo = null;
      updateAccountVerificationStatus("", false);
      updateSubmitButtonState();

      // Reset source account row visibility
      document.getElementById("source_account_row").style.display = "none";

      // Reset to step 1
      currentStep = 1;
      backToFormStep();
    } else {
      // Show validation errors
      if (result.errors) {
        displayErrors(result.errors);
      }
      // Always show the message if available
      if (result.message) {
        alert(result.message);
      } else {
        alert(
          "An error occurred while opening your account. Please try again."
        );
      }
      submitBtn.disabled = false;
      submitBtn.innerHTML = "Open Account";
    }
  } catch (error) {
    console.error("Error:", error);
    console.error("Error details:", error.message);
    alert(
      "Could not connect to server: " +
        error.message +
        ". Please check the console for more details."
    );
    submitBtn.disabled = false;
    submitBtn.innerHTML = "Open Account";
  }
}

// Verify existing account number
async function verifyExistingAccount(accountNumber) {
  if (!accountNumber || accountNumber.length === 0) {
    isAccountVerified = false;
    verifiedAccountInfo = null;
    updateSubmitButtonState();
    return false;
  }

  const statusDiv = document.getElementById("account_verification_status");
  const input = document.getElementById("existing_account_number");

  // Show loading state
  statusDiv.innerHTML =
    '<span style="color: #666;"><i class="bi bi-hourglass-split"></i> Verifying account...</span>';
  input.classList.remove("error");
  clearError("existing_account_number");
  updateSubmitButtonState();

  try {
    // Use the employee get-customer-account API to just verify account exists
    const response = await fetch(
      `${API_BASE_URL}/employee/get-customer-account.php`,
      {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          account_number: accountNumber,
        }),
      }
    );

    const result = await response.json();

    if (result.success && result.data) {
      // Account exists and is valid
      isAccountVerified = true;
      verifiedAccountInfo = result.data;
      statusDiv.innerHTML = `<span style="color: #28a745;"><i class="bi bi-check-circle"></i> Account verified: ${result.data.customer_name} - ${result.data.account_type}</span>`;
      input.classList.remove("error");
      clearError("existing_account_number");
      updateSubmitButtonState();
      return true;
    } else {
      isAccountVerified = false;
      verifiedAccountInfo = null;
      statusDiv.innerHTML =
        '<span style="color: #dc3545;"><i class="bi bi-x-circle"></i> Account not found</span>';
      input.classList.add("error");
      showError(
        "existing_account_number",
        result.message || "Account number not found in the system."
      );
      updateSubmitButtonState();
      return false;
    }
  } catch (error) {
    console.error("Error verifying account:", error);
    isAccountVerified = false;
    verifiedAccountInfo = null;
    statusDiv.innerHTML =
      '<span style="color: #dc3545;"><i class="bi bi-x-circle"></i> Error verifying account</span>';
    input.classList.add("error");
    showError(
      "existing_account_number",
      "Error verifying account. Please try again."
    );
    updateSubmitButtonState();
    return false;
  }
}

// Update submit button state based on account verification
function updateSubmitButtonState() {
  const submitBtn = document.getElementById("submit-btn");
  const accountType = document.getElementById("selected_account_type").value;

  if (submitBtn) {
    // Disable button if account is not verified or account type not selected
    if (!isAccountVerified || !accountType) {
      submitBtn.disabled = true;
      submitBtn.style.opacity = "0.6";
      submitBtn.style.cursor = "not-allowed";
    } else {
      submitBtn.disabled = false;
      submitBtn.style.opacity = "1";
      submitBtn.style.cursor = "pointer";
    }
  }
}

// Update account verification status display
function updateAccountVerificationStatus(message, isValid) {
  const statusDiv = document.getElementById("account_verification_status");
  if (!statusDiv) return;

  if (message) {
    if (isValid) {
      statusDiv.innerHTML = `<span style="color: #28a745;"><i class="bi bi-check-circle"></i> ${message}</span>`;
    } else {
      statusDiv.innerHTML = `<span style="color: #dc3545;"><i class="bi bi-x-circle"></i> ${message}</span>`;
    }
  } else {
    statusDiv.innerHTML = "";
  }
}

// Validate form
function validateForm() {
  let isValid = true;

  // Check if existing account number is provided and verified
  const existingAccountNumber = document
    .getElementById("existing_account_number")
    .value.trim();
  if (!existingAccountNumber) {
    showError(
      "existing_account_number",
      "Please enter your existing account number"
    );
    isValid = false;
  } else if (!isAccountVerified) {
    showError(
      "existing_account_number",
      "Please verify your account number. Make sure it exists and belongs to you."
    );
    isValid = false;
  }

  // Check if account type is selected
  const accountType = document.getElementById("selected_account_type").value;
  if (!accountType) {
    showError("account_type", "Please select an account type");
    isValid = false;
  }

  // Validate ID fields
  const idType = document.getElementById("id_type").value;
  if (!idType) {
    showError("id_type", "Please select an ID type");
    isValid = false;
  }

  const idNumber = document.getElementById("id_number").value.trim();
  if (!idNumber) {
    showError("id_number", "Please enter your ID number");
    isValid = false;
  }

  // Validate ID images
  const idFrontFile = document.getElementById("id_front_image").files[0];
  if (!idFrontFile) {
    showError("id_front_image", "Please upload the front image of your ID");
    isValid = false;
  }

  const idBackFile = document.getElementById("id_back_image").files[0];
  if (!idBackFile) {
    showError("id_back_image", "Please upload the back image of your ID");
    isValid = false;
  }

  // Validate initial deposit if provided
  const initialDeposit =
    parseFloat(document.getElementById("initial_deposit").value) || 0;
  const depositSource = document.getElementById("deposit_source").value;
  const sourceAccount = document.getElementById("source_account_number").value;

  if (initialDeposit > 0) {
    if (!depositSource) {
      showError(
        "deposit_source",
        "Please select a deposit source (Cash or Transfer)"
      );
      isValid = false;
    }

    if (depositSource === "transfer") {
      if (!sourceAccount) {
        showError("source_account_number", "Please select a source account");
        isValid = false;
      } else {
        // Check balance
        const selectedOption = document.getElementById("source_account_number")
          .options[
          document.getElementById("source_account_number").selectedIndex
        ];
        const balance =
          parseFloat(selectedOption.getAttribute("data-balance")) || 0;

        if (initialDeposit > balance) {
          showError(
            "initial_deposit",
            `Insufficient balance. Available: ₱${balance.toFixed(2)}`
          );
          isValid = false;
        }
      }
    }

    if (
      !validateInitialDeposit({
        target: document.getElementById("initial_deposit"),
      })
    ) {
      isValid = false;
    }
  }

  return isValid;
}

// Load customer accounts
async function loadCustomerAccounts() {
  try {
    const response = await fetch(
      `${API_BASE_URL}/customer/get-my-accounts.php`,
      {
        method: "GET",
        credentials: "include",
      }
    );

    const result = await response.json();

    if (result.success && result.data) {
      customerAccounts = result.data;
      populateSourceAccountDropdown();
    }
  } catch (error) {
    console.error("Error loading customer accounts:", error);
  }
}

// Populate source account dropdown
function populateSourceAccountDropdown() {
  const select = document.getElementById("source_account_number");
  if (!select) return;

  if (customerAccounts.length === 0) {
    select.innerHTML = '<option value="">No accounts available</option>';
    return;
  }

  const options = customerAccounts
    .map((account) => {
      const balance = parseFloat(account.balance || 0);
      return `<option value="${
        account.account_number
      }" data-balance="${balance}" data-account-id="${account.account_id}">
      ${account.account_number} - ${account.account_type} (₱${
        account.balance_formatted || "0.00"
      })
    </option>`;
    })
    .join("");

  select.innerHTML =
    '<option value="">Select source account</option>' + options;
}

// Handle deposit source change
function handleDepositSourceChange(e) {
  const source = e.target.value;
  const sourceAccountRow = document.getElementById("source_account_row");
  const sourceAccountSelect = document.getElementById("source_account_number");

  if (source === "transfer") {
    sourceAccountRow.style.display = "flex";
    sourceAccountSelect.required = true;
    if (customerAccounts.length > 0) {
      populateSourceAccountDropdown();
    }
  } else {
    sourceAccountRow.style.display = "none";
    sourceAccountSelect.required = false;
    sourceAccountSelect.value = "";
    clearError("source_account_number");
  }

  // Validate if deposit amount is provided
  const depositAmount = document.getElementById("initial_deposit").value;
  if (depositAmount && parseFloat(depositAmount) > 0) {
    if (source === "transfer" && !sourceAccountSelect.value) {
      showError(
        "deposit_source",
        "Please select a source account when transferring funds"
      );
    } else {
      clearError("deposit_source");
    }
  }
}

// Handle deposit amount change
function handleDepositAmountChange(e) {
  const amount = parseFloat(e.target.value) || 0;
  const depositSource = document.getElementById("deposit_source").value;

  if (amount > 0 && !depositSource) {
    // Require deposit source if amount is provided
    document.getElementById("deposit_source").required = true;
  } else if (amount === 0) {
    document.getElementById("deposit_source").required = false;
    document.getElementById("deposit_source").value = "";
    document.getElementById("source_account_row").style.display = "none";
    document.getElementById("source_account_number").value = "";
  }
}

// Handle source account selection change
function handleSourceAccountChange(e) {
  const accountNumber = e.target.value;
  const selectedOption = e.target.options[e.target.selectedIndex];
  const balance = parseFloat(selectedOption.getAttribute("data-balance")) || 0;

  const balanceText = document.getElementById("source_account_balance");
  if (balanceText) {
    balanceText.textContent = `Available balance: ₱${balance.toFixed(2)}`;
  }

  // Validate if deposit amount exceeds balance
  const depositAmount =
    parseFloat(document.getElementById("initial_deposit").value) || 0;
  if (depositAmount > balance) {
    showError(
      "source_account_number",
      `Insufficient balance. Available: ₱${balance.toFixed(2)}`
    );
  } else {
    clearError("source_account_number");
  }
}

// Collect form data
function collectFormData() {
  const form = document.getElementById("accountOpeningForm");
  const formData = new FormData(form);

  const depositAmount = formData.get("initial_deposit");
  const depositSource = formData.get("deposit_source");
  const sourceAccount = formData.get("source_account_number");

  // Create a new FormData with all necessary fields including files
  const data = new FormData();

  // Add text fields
  data.append(
    "existing_account_number",
    formData.get("existing_account_number")
  );
  data.append("account_type", formData.get("account_type"));
  data.append("id_type", formData.get("id_type"));
  data.append("id_number", formData.get("id_number"));

  // Add customer_id from verified account
  if (verifiedAccountInfo && verifiedAccountInfo.customer_id) {
    data.append("customer_id", verifiedAccountInfo.customer_id);
  }

  // Add file uploads
  const idFrontFile = document.getElementById("id_front_image").files[0];
  const idBackFile = document.getElementById("id_back_image").files[0];

  if (idFrontFile) {
    data.append("id_front_image", idFrontFile);
  }
  if (idBackFile) {
    data.append("id_back_image", idBackFile);
  }

  // Add optional fields
  if (depositAmount && parseFloat(depositAmount) > 0) {
    data.append("initial_deposit", parseFloat(depositAmount));
  }
  if (depositSource) {
    data.append("deposit_source", depositSource);
  }
  if (depositSource === "transfer" && sourceAccount) {
    data.append("source_account_number", sourceAccount);
  }
  if (formData.get("account_purpose")) {
    data.append("account_purpose", formData.get("account_purpose"));
  }

  return data;
}

// Display validation errors
function displayErrors(errors) {
  for (let [field, message] of Object.entries(errors)) {
    showError(field, message);
  }
}

// Show error message
function showError(field, message) {
  const errorElement = document.getElementById(`error-${field}`);
  if (errorElement) {
    errorElement.textContent = message;
    errorElement.classList.add("show");
  }

  // Also add error class to input
  const input =
    document.querySelector(`[name="${field}"]`) ||
    document.getElementById(field);
  if (input) {
    input.classList.add("error");
  }
}

// Clear error message
function clearError(field) {
  const errorElement = document.getElementById(`error-${field}`);
  if (errorElement) {
    errorElement.textContent = "";
    errorElement.classList.remove("show");
  }

  // Remove error class from input
  const input =
    document.querySelector(`[name="${field}"]`) ||
    document.getElementById(field);
  if (input) {
    input.classList.remove("error");
  }
}

// Clear all errors
function clearAllErrors() {
  document.querySelectorAll(".error-message").forEach((el) => {
    el.textContent = "";
    el.classList.remove("show");
  });
  document.querySelectorAll(".form-control").forEach((el) => {
    el.classList.remove("error");
  });
}

// Create review section HTML
function createReviewSection() {
  const section = document.createElement("div");
  section.id = "review-section";
  section.style.display = "none";
  section.innerHTML = `
    <div class="section-title">Review Your Information</div>
    <div class="info-box">
      <p><strong>Please review all information before submitting.</strong> Make sure all details are correct.</p>
    </div>
    
    <div class="review-grid" style="display: grid; gap: 20px;">
      <div class="review-card" style="background: #f8f9fa; padding: 15px; border-radius: 6px;">
        <h6 style="color: var(--primary-dark); margin-bottom: 10px;">Account Verification</h6>
        <p><strong>Existing Account:</strong> <span id="review-existing-account"></span></p>
        <p><strong>Account Holder:</strong> <span id="review-account-holder"></span></p>
      </div>
      
      <div class="review-card" style="background: #f8f9fa; padding: 15px; border-radius: 6px;">
        <h6 style="color: var(--primary-dark); margin-bottom: 10px;">Valid ID Information</h6>
        <p><strong>ID Type:</strong> <span id="review-id-type"></span></p>
        <p><strong>ID Number:</strong> <span id="review-id-number"></span></p>
        <p><strong>ID Images:</strong> <span id="review-id-images"></span></p>
      </div>
      
      <div class="review-card" style="background: #f8f9fa; padding: 15px; border-radius: 6px;">
        <h6 style="color: var(--primary-dark); margin-bottom: 10px;">New Account Details</h6>
        <p><strong>Account Type:</strong> <span id="review-account-type"></span></p>
        <p><strong>Initial Deposit:</strong> <span id="review-initial-deposit"></span></p>
        <p><strong>Account Purpose:</strong> <span id="review-account-purpose"></span></p>
      </div>
    </div>
    
    <div class="button-group" style="margin-top: 30px;">
      <button type="button" class="btn btn-continue" id="confirm-submit-btn">Confirm & Create Account</button>
      <button type="button" class="btn btn-cancel" id="back-to-form-btn">Back to Edit</button>
    </div>
  `;

  // Add event listeners
  setTimeout(() => {
    document
      .getElementById("confirm-submit-btn")
      .addEventListener("click", () => {
        handleFormSubmit(new Event("submit"));
      });

    document
      .getElementById("back-to-form-btn")
      .addEventListener("click", () => {
        backToFormStep();
      });
  }, 100);

  return section;
}

// Populate review data
function populateReviewData() {
  // Account verification
  document.getElementById("review-existing-account").textContent =
    document.getElementById("existing_account_number").value;
  document.getElementById("review-account-holder").textContent =
    verifiedAccountInfo ? verifiedAccountInfo.customer_name : "N/A";

  // ID information
  document.getElementById("review-id-type").textContent =
    document.getElementById("id_type").value || "Not selected";
  document.getElementById("review-id-number").textContent =
    document.getElementById("id_number").value || "Not provided";

  const frontFile = document.getElementById("id_front_image").files[0];
  const backFile = document.getElementById("id_back_image").files[0];
  document.getElementById("review-id-images").textContent =
    frontFile && backFile ? "Front and Back uploaded" : "Missing images";

  // Account details
  document.getElementById("review-account-type").textContent =
    document.getElementById("selected_account_type").value || "Not selected";

  const depositAmount = document.getElementById("initial_deposit").value;
  const depositSource = document.getElementById("deposit_source").value;
  let depositText = "None";
  if (depositAmount && parseFloat(depositAmount) > 0) {
    depositText = `₱${parseFloat(depositAmount).toFixed(2)}`;
    if (depositSource === "cash") {
      depositText += " (Cash)";
    } else if (depositSource === "transfer") {
      const sourceAccount = document.getElementById(
        "source_account_number"
      ).value;
      depositText += ` (Transfer from ${sourceAccount})`;
    }
  }
  document.getElementById("review-initial-deposit").textContent = depositText;

  const purpose = document.querySelector('[name="account_purpose"]').value;
  document.getElementById("review-account-purpose").textContent =
    purpose || "Not specified";
}

// Go back to form step
function backToFormStep() {
  // Hide review section
  document.getElementById("review-section").style.display = "none";

  // Show form
  document.querySelector(".form-section form").style.display = "block";

  // Update sidebar
  document.querySelectorAll(".sidebar .nav-item").forEach((item, index) => {
    if (index === 0) {
      item.classList.add("active");
    } else if (index === 1) {
      item.classList.remove("active");
    }
  });

  currentStep = 1;
}

// Handle image preview
function handleImagePreview(event, previewDivId) {
  const file = event.target.files[0];
  const previewDiv = document.getElementById(previewDivId);
  const inputId = event.target.id;

  // Clear previous preview
  previewDiv.innerHTML = "";
  previewDiv.classList.remove("show");
  clearError(inputId);

  if (!file) {
    return;
  }

  // Validate file type
  const validTypes = ["image/jpeg", "image/jpg", "image/png", "image/gif"];
  if (!validTypes.includes(file.type)) {
    showError(inputId, "Please upload a valid image file (JPG, PNG, or GIF)");
    event.target.value = ""; // Clear the input
    return;
  }

  // Validate file size (max 5MB)
  const maxSize = 5 * 1024 * 1024; // 5MB in bytes
  if (file.size > maxSize) {
    showError(inputId, "File size must be less than 5MB");
    event.target.value = ""; // Clear the input
    return;
  }

  // Create image preview
  const reader = new FileReader();
  reader.onload = function (e) {
    const img = document.createElement("img");
    img.src = e.target.result;
    img.alt = "ID Preview";
    previewDiv.appendChild(img);
    previewDiv.classList.add("show");
  };
  reader.readAsDataURL(file);
}

// Show success message
function showSuccessMessage(message) {
  const successElement = document.getElementById("success-message");
  if (successElement) {
    successElement.innerHTML = message;
    successElement.classList.add("show");
  }
}

// Hide success message
function hideSuccessMessage() {
  const successElement = document.getElementById("success-message");
  if (successElement) {
    successElement.classList.remove("show");
    successElement.innerHTML = "";
  }
}
