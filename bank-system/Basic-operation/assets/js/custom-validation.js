/**
 * Custom Validation Messages for Evergreen Banking System
 * This script replaces default browser validation messages with custom ones
 *
 * Usage: Include this in your HTML after the form
 */

// Custom validation message templates
const VALIDATION_MESSAGES = {
  // Field-specific messages
  first_name: {
    valueMissing: "Please enter the first name",
    patternMismatch: "First name should only contain letters",
    tooShort: "First name is too short",
  },
  middle_name: {
    patternMismatch: "Middle name should only contain letters",
  },
  last_name: {
    valueMissing: "Please enter the last name",
    patternMismatch: "Last name should only contain letters",
    tooShort: "Last name is too short",
  },
  address_line: {
    valueMissing: "Please enter the complete home address",
    tooShort: "Please provide a more detailed address",
  },
  city: {
    valueMissing: "Please enter the city",
  },
  province: {
    valueMissing: "Please enter the province",
  },
  gender: {
    valueMissing: "Please select the gender",
  },
  date_of_birth: {
    valueMissing: "Please enter the date of birth",
    rangeOverflow: "The client must be at least 18 years old",
  },
  marital_status: {
    valueMissing: "Please select the civil status",
  },
  nationality: {
    valueMissing: "Please enter the citizenship",
  },
  "emails[]": {
    valueMissing: "Please enter the email address",
    typeMismatch: "Please enter a valid email address (e.g., name@example.com)",
  },
  "phone_numbers[]": {
    valueMissing: "Please enter the phone number",
    patternMismatch: "Please enter a valid phone number (numbers only)",
  },
  "phone_country_codes[]": {
    valueMissing: "Please select a country code",
  },

  // Default messages for any field
  default: {
    valueMissing: "This field is required",
    typeMismatch: "Please enter a valid value",
    patternMismatch: "Please match the requested format",
    tooShort: "This value is too short",
    tooLong: "This value is too long",
    rangeUnderflow: "This value is too low",
    rangeOverflow: "This value is too high",
  },
};

// Get custom message for a field
function getCustomMessage(input) {
  const fieldName = input.name;
  const fieldMessages = VALIDATION_MESSAGES[fieldName] || {};
  const defaultMessages = VALIDATION_MESSAGES.default;

  // Check all validation states
  if (input.validity.valueMissing) {
    return fieldMessages.valueMissing || defaultMessages.valueMissing;
  } else if (input.validity.typeMismatch) {
    return fieldMessages.typeMismatch || defaultMessages.typeMismatch;
  } else if (input.validity.patternMismatch) {
    return fieldMessages.patternMismatch || defaultMessages.patternMismatch;
  } else if (input.validity.tooShort) {
    return fieldMessages.tooShort || defaultMessages.tooShort;
  } else if (input.validity.tooLong) {
    return fieldMessages.tooLong || defaultMessages.tooLong;
  } else if (input.validity.rangeUnderflow) {
    return fieldMessages.rangeUnderflow || defaultMessages.rangeUnderflow;
  } else if (input.validity.rangeOverflow) {
    return fieldMessages.rangeOverflow || defaultMessages.rangeOverflow;
  }

  return "Please check this field";
}

// Apply custom validation to an input
function applyCustomValidation(input) {
  // Set custom message
  input.addEventListener("invalid", function (e) {
    e.preventDefault();
    const message = getCustomMessage(this);
    this.setCustomValidity(message);

    // Show error visually
    this.classList.add("error");
    showFieldError(this, message);
  });

  // Clear message on input
  input.addEventListener("input", function () {
    this.setCustomValidity("");
    this.classList.remove("error");
    hideFieldError(this);
  });

  // Clear message on change (for selects)
  input.addEventListener("change", function () {
    this.setCustomValidity("");
    this.classList.remove("error");
    hideFieldError(this);
  });

  // Clear message on focus
  input.addEventListener("focus", function () {
    this.setCustomValidity("");
  });
}

// Show error message below field
function showFieldError(input, message) {
  const wrapper = input.closest(".field-wrapper") || input.parentElement;

  // Remove existing error
  let errorDiv = wrapper.querySelector(".validation-error");
  if (errorDiv) {
    errorDiv.remove();
  }

  // Create new error message
  errorDiv = document.createElement("div");
  errorDiv.className = "validation-error";
  errorDiv.textContent = message;
  errorDiv.style.cssText = `
    color: #dc3545;
    font-size: 12px;
    margin-top: 4px;
    display: block;
  `;

  wrapper.appendChild(errorDiv);
}

// Hide error message
function hideFieldError(input) {
  const wrapper = input.closest(".field-wrapper") || input.parentElement;
  const errorDiv = wrapper.querySelector(".validation-error");
  if (errorDiv) {
    errorDiv.remove();
  }
}

// Initialize custom validation for all form fields
function initializeCustomValidation(formId = "accountCreationForm") {
  const form = document.getElementById(formId);
  if (!form) {
    console.error(`Form with id "${formId}" not found`);
    return;
  }

  // Get all input, select, and textarea elements
  const fields = form.querySelectorAll("input, select, textarea");

  fields.forEach((field) => {
    applyCustomValidation(field);
  });

  // Handle form submission
  form.addEventListener("submit", function (e) {
    // Check if form is valid
    if (!this.checkValidity()) {
      e.preventDefault();
      e.stopPropagation();

      // Find first invalid field
      const firstInvalid = this.querySelector(":invalid");
      if (firstInvalid) {
        const message = getCustomMessage(firstInvalid);
        firstInvalid.setCustomValidity(message);
        showFieldError(firstInvalid, message);

        // Scroll to first error
        firstInvalid.scrollIntoView({
          behavior: "smooth",
          block: "center",
        });

        // Focus on the field
        setTimeout(() => firstInvalid.focus(), 300);
      }
    }
  });

  console.log(`✅ Custom validation initialized for ${fields.length} fields`);
}

// Auto-initialize when DOM is ready
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", () => {
    initializeCustomValidation();
  });
} else {
  // DOM already loaded
  initializeCustomValidation();
}

// Export for manual initialization if needed
if (typeof module !== "undefined" && module.exports) {
  module.exports = { initializeCustomValidation, VALIDATION_MESSAGES };
}
