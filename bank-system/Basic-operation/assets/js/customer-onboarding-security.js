/**
 * Customer Onboarding - Document Verification (Step 2)
 * Handles file uploads, document validation, and navigation
 */

// Detect API path dynamically based on current page location
function getApiBaseUrl() {
  const currentPath = window.location.pathname;

  if (currentPath.includes("/public/")) {
    const basePath = currentPath.substring(0, currentPath.indexOf("/public/"));
    return window.location.origin + basePath + "/api";
  }

  const pathParts = currentPath.split("/");
  const basicOpIndex = pathParts.indexOf("Basic-operation");
  if (basicOpIndex !== -1) {
    const basePath = pathParts.slice(0, basicOpIndex + 1).join("/");
    return window.location.origin + basePath + "/api";
  }

  return window.location.origin + "/Evergreen/bank-system/Basic-operation/api";
}

const API_BASE_URL = getApiBaseUrl();
console.log("API Base URL:", API_BASE_URL);

// State management
let uploadedFiles = {
  id_front: null,
  id_back: null,
};

// Initialize on page load
document.addEventListener("DOMContentLoaded", function () {
  setupFormHandlers();
  setupFileUploadHandlers();
  checkSessionData();
});

/**
 * Check if step 1 is completed
 */
function checkSessionData() {
  const urlParams = new URLSearchParams(window.location.search);
  if (!urlParams.has("from") && !sessionStorage.getItem("step1_completed")) {
    // Optionally redirect to step 1
    // window.location.href = 'customer-onboarding-details.html';
  }
}

/**
 * Setup file upload handlers
 */
function setupFileUploadHandlers() {
  const fileInputs = document.querySelectorAll(".file-input");

  fileInputs.forEach((input) => {
    // Handle file selection
    input.addEventListener("change", function (e) {
      handleFileSelect(e.target);
    });

    // Handle drag and drop
    const label = input.nextElementSibling;

    label.addEventListener("dragover", function (e) {
      e.preventDefault();
      e.stopPropagation();
      this.style.borderColor = "#1a6b62";
      this.style.backgroundColor = "#f0f7f5";
    });

    label.addEventListener("dragleave", function (e) {
      e.preventDefault();
      e.stopPropagation();
      this.style.borderColor = "#ccc";
      this.style.backgroundColor = "#fafafa";
    });

    label.addEventListener("drop", function (e) {
      e.preventDefault();
      e.stopPropagation();
      this.style.borderColor = "#ccc";
      this.style.backgroundColor = "#fafafa";

      const files = e.dataTransfer.files;
      if (files.length > 0) {
        input.files = files;
        handleFileSelect(input);
      }
    });
  });
}

/**
 * Handle file selection
 */
function handleFileSelect(input) {
  const file = input.files[0];
  const fieldName = input.name;
  const wrapper = input.closest(".file-upload-wrapper");
  const label = input.nextElementSibling;
  const fileNameSpan = label.querySelector(".file-name");
  const previewDiv = document.getElementById(`preview_${fieldName}`);

  if (!file) {
    wrapper.classList.remove("has-file");
    fileNameSpan.textContent = "";
    if (previewDiv) {
      previewDiv.classList.remove("show");
      previewDiv.innerHTML = "";
    }
    uploadedFiles[fieldName] = null;
    return;
  }

  // Validate file
  const validation = validateFile(file);
  if (!validation.valid) {
    showError(validation.message);
    input.value = "";
    return;
  }

  // Update UI
  wrapper.classList.add("has-file");
  fileNameSpan.textContent = file.name;
  uploadedFiles[fieldName] = file;

  // Show preview for images
  if (file.type.startsWith("image/")) {
    const reader = new FileReader();
    reader.onload = function (e) {
      previewDiv.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
      previewDiv.classList.add("show");
    };
    reader.readAsDataURL(file);
  } else if (file.type === "application/pdf") {
    previewDiv.innerHTML = `
      <div style="padding: 15px; background: #f0f7f5; border-radius: 8px; text-align: center;">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#1a6b62" stroke-width="2">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
          <polyline points="14 2 14 8 20 8"></polyline>
        </svg>
        <p style="margin: 10px 0 0 0; font-size: 14px; color: #1a6b62; font-weight: 600;">PDF Document</p>
        <p style="margin: 5px 0 0 0; font-size: 12px; color: #666;">${file.name}</p>
      </div>
    `;
    previewDiv.classList.add("show");
  }

  console.log(`File selected for ${fieldName}:`, file.name);
}

/**
 * Validate file
 */
function validateFile(file) {
  const maxSize = 5 * 1024 * 1024; // 5MB
  const allowedTypes = [
    "image/jpeg",
    "image/jpg",
    "image/png",
    "application/pdf",
  ];

  if (!allowedTypes.includes(file.type)) {
    return {
      valid: false,
      message: "Invalid file type. Please upload JPG, PNG, or PDF files only.",
    };
  }

  if (file.size > maxSize) {
    return {
      valid: false,
      message: "File size exceeds 5MB. Please upload a smaller file.",
    };
  }

  return { valid: true };
}

/**
 * Setup form handlers
 */
function setupFormHandlers() {
  const form = document.getElementById("documentForm");
  const backButton = document.querySelector(".btn-back");

  // Form submission
  if (form) {
    form.addEventListener("submit", handleFormSubmit);
  }

  // Back button
  if (backButton) {
    backButton.addEventListener("click", function () {
      window.location.href = "customer-onboarding-details.html";
    });
  }

  // SSN formatting
  const ssnInput = document.getElementById("ssn");
  if (ssnInput) {
    ssnInput.addEventListener("input", function (e) {
      formatSSN(e.target);
    });
  }
}

/**
 * Format SSN input
 */
function formatSSN(input) {
  let value = input.value.replace(/\D/g, ""); // Remove non-digits

  // Format as XXX-XX-XXXX or XXX-XXX-XXX-XXX (Philippine TIN)
  if (value.length <= 9) {
    // US SSN format: XXX-XX-XXXX
    if (value.length > 5) {
      value =
        value.slice(0, 3) + "-" + value.slice(3, 5) + "-" + value.slice(5);
    } else if (value.length > 3) {
      value = value.slice(0, 3) + "-" + value.slice(3);
    }
  } else {
    // Philippine TIN format: XXX-XXX-XXX-XXX
    if (value.length > 9) {
      value =
        value.slice(0, 3) +
        "-" +
        value.slice(3, 6) +
        "-" +
        value.slice(6, 9) +
        "-" +
        value.slice(9, 12);
    }
  }

  input.value = value;
}

/**
 * Handle form submission
 */
async function handleFormSubmit(e) {
  e.preventDefault();

  // Validate form
  if (!validateForm()) {
    return;
  }

  // Show loading state
  const submitButton = document.querySelector(".btn-continue");
  const originalText = submitButton.textContent;
  submitButton.disabled = true;
  submitButton.textContent = "Uploading...";

  try {
    // Prepare form data
    const formData = new FormData();

    // Add files
    if (uploadedFiles.id_front) {
      formData.append("id_front", uploadedFiles.id_front);
    }
    if (uploadedFiles.id_back) {
      formData.append("id_back", uploadedFiles.id_back);
    }

    // Add form fields
    const idType = document.getElementById("id_type").value;
    const idNumber = document.getElementById("id_number").value;

    console.log("🔍 Step 2 - idType value:", idType);
    console.log("🔍 Step 2 - idNumber value:", idNumber);

    formData.append("id_type", idType);
    formData.append("id_number", idNumber);

    // Get step 1 data from session storage
    const step1Data = sessionStorage.getItem("onboarding_step1");
    if (step1Data) {
      formData.append("step1_data", step1Data);
    }

    // Convert files to base64 for storage in sessionStorage
    const filePromises = [];
    const step2Data = {
      id_type: idType,
      id_number: idNumber,
      documents_uploaded: true,
    };

    if (uploadedFiles.id_front) {
      filePromises.push(
        new Promise((resolve) => {
          const reader = new FileReader();
          reader.onload = (e) => {
            step2Data.id_front_data = e.target.result;
            step2Data.id_front_name = uploadedFiles.id_front.name;
            step2Data.id_front_type = uploadedFiles.id_front.type;
            resolve();
          };
          reader.readAsDataURL(uploadedFiles.id_front);
        })
      );
    }

    if (uploadedFiles.id_back) {
      filePromises.push(
        new Promise((resolve) => {
          const reader = new FileReader();
          reader.onload = (e) => {
            step2Data.id_back_data = e.target.result;
            step2Data.id_back_name = uploadedFiles.id_back.name;
            step2Data.id_back_type = uploadedFiles.id_back.type;
            resolve();
          };
          reader.readAsDataURL(uploadedFiles.id_back);
        })
      );
    }

    // Wait for all files to be converted
    await Promise.all(filePromises);

    console.log("🔍 Step 2 - Saving to sessionStorage:", step2Data);
    sessionStorage.setItem("onboarding_step2", JSON.stringify(step2Data));
    console.log("Document verification data prepared with files");

    sessionStorage.setItem("step2_completed", "true");
    window.location.href = "customer-onboarding-review.html?from=security";
  } catch (error) {
    console.error("Error submitting form:", error);
    showError("An error occurred while uploading documents. Please try again.");
    submitButton.disabled = false;
    submitButton.textContent = originalText;
  }
}

/**
 * Validate form
 */
function validateForm() {
  let isValid = true;

  // Validate ID type
  const idType = document.getElementById("id_type");
  if (!idType.value) {
    showFieldError(idType, "Please select an ID type");
    isValid = false;
  }

  // Validate ID Number
  const idNumber = document.getElementById("id_number");
  if (!idNumber || !idNumber.value.trim()) {
    if (idNumber) showFieldError(idNumber, "ID Number is required");
    isValid = false;
  }

  // Validate files
  const idFront = document.getElementById("id_front");
  if (!idFront.files || idFront.files.length === 0) {
    showError("Please upload the front of your ID");
    isValid = false;
  }

  const idBack = document.getElementById("id_back");
  if (!idBack.files || idBack.files.length === 0) {
    showError("Please upload the back of your ID");
    isValid = false;
  }

  return isValid;
}

/**
 * Show field error
 */
function showFieldError(field, message) {
  field.style.borderColor = "#dc3545";

  // Create or update error message
  let errorDiv = field.parentElement.querySelector(".error-message");
  if (!errorDiv) {
    errorDiv = document.createElement("div");
    errorDiv.className = "error-message";
    errorDiv.style.color = "#dc3545";
    errorDiv.style.fontSize = "12px";
    errorDiv.style.marginTop = "5px";
    field.parentElement.appendChild(errorDiv);
  }
  errorDiv.textContent = message;
  errorDiv.style.display = "block";

  // Remove error on input
  field.addEventListener(
    "input",
    function () {
      field.style.borderColor = "";
      if (errorDiv) {
        errorDiv.style.display = "none";
      }
    },
    { once: true }
  );
}

/**
 * Show error message
 */
function showError(message) {
  // Create toast notification
  const toast = document.createElement("div");
  toast.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    background-color: #dc3545;
    color: white;
    padding: 15px 20px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 10000;
    max-width: 400px;
    animation: slideIn 0.3s ease;
  `;
  toast.textContent = message;

  document.body.appendChild(toast);

  setTimeout(() => {
    toast.style.animation = "slideOut 0.3s ease";
    setTimeout(() => {
      document.body.removeChild(toast);
    }, 300);
  }, 5000);
}

/**
 * Show success message
 */
function showSuccess(message) {
  const toast = document.createElement("div");
  toast.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    background-color: #28a745;
    color: white;
    padding: 15px 20px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 10000;
    max-width: 400px;
    animation: slideIn 0.3s ease;
  `;
  toast.textContent = message;

  document.body.appendChild(toast);

  setTimeout(() => {
    toast.style.animation = "slideOut 0.3s ease";
    setTimeout(() => {
      document.body.removeChild(toast);
    }, 300);
  }, 3000);
}

// Add animations
const style = document.createElement("style");
style.textContent = `
  @keyframes slideIn {
    from {
      transform: translateX(400px);
      opacity: 0;
    }
    to {
      transform: translateX(0);
      opacity: 1;
    }
  }
  
  @keyframes slideOut {
    from {
      transform: translateX(0);
      opacity: 1;
    }
    to {
      transform: translateX(400px);
      opacity: 0;
    }
  }
`;
document.head.appendChild(style);
