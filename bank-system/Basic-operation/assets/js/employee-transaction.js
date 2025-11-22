// API Configuration - dynamically detect based on current host
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

// Track current transaction type
let currentTransactionType = "withdraw";
let accountData = null;

// Set current date and time
function setCurrentDateTime() {
  const now = new Date();
  const options = {
    year: "numeric",
    month: "long",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  };
  const formattedDate = now.toLocaleDateString("en-US", options);
  document.getElementById("transactionDate").value = formattedDate;
}

// Format currency input
function formatCurrency(input) {
  let value = input.value.replace(/[^\d]/g, "");

  if (value) {
    value = parseInt(value).toLocaleString("en-PH");
    input.value = "PHP " + value;
  }
}

// Show inline error message
function showError(message, fieldId = null) {
  const existingErrors = document.querySelectorAll(".inline-error-message");
  existingErrors.forEach((err) => err.remove());

  const errorDiv = document.createElement("div");
  errorDiv.className = "inline-error-message alert alert-danger mt-3";
  errorDiv.style.padding = "12px 16px";
  errorDiv.style.borderRadius = "6px";
  errorDiv.style.fontSize = "14px";
  errorDiv.style.marginBottom = "0";
  errorDiv.textContent = message;

  if (fieldId) {
    const field = document.getElementById(fieldId);
    if (field && field.parentElement) {
      field.parentElement.appendChild(errorDiv);
      field.classList.add("is-invalid");
      field.style.borderColor = "#dc3545";
    }
  } else {
    const form = document.getElementById("transactionForm");
    form.insertBefore(errorDiv, form.firstChild);
  }

  setTimeout(() => {
    errorDiv.remove();
    if (fieldId) {
      const field = document.getElementById(fieldId);
      if (field) {
        field.classList.remove("is-invalid");
        field.style.borderColor = "";
      }
    }
  }, 5000);
}

// Clear error messages
function clearErrors() {
  const existingErrors = document.querySelectorAll(".inline-error-message");
  existingErrors.forEach((err) => err.remove());

  const invalidFields = document.querySelectorAll(".is-invalid");
  invalidFields.forEach((field) => {
    field.classList.remove("is-invalid");
    field.style.borderColor = "";
  });
}

// Account number lookup from database
async function lookupAccount() {
  const accountNumber = document.getElementById("accountNumber").value;

  document.getElementById("name").value = "";
  document.getElementById("currentBalance").value = "";
  accountData = null;
  clearErrors();

  const allowedPrefixes = ["CHA", "SA"];
  const isValidFormat = (accNum) => {
      const parts = accNum.split('-');
      if (parts.length !== 3) return false;
      if (!allowedPrefixes.includes(parts[0])) return false;
      if (!/^\d{4}$/.test(parts[1])) return false;
      if (!/^\d{4}$/.test(parts[2])) return false;
      return true;
  };

  if (!isValidFormat(accountNumber)) {
    showError(
        "Invalid account number format. Use CHA-1234-5678 or SA-1234-5678.",
        "accountNumber"
    );
    return;
}

  try {
    const response = await fetch(
      `${API_BASE_URL}/employee/get-customer-account.php`,
      {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ account_number: accountNumber }),
      }
    );

    const result = await response.json();

    if (result.success) {
      accountData = result.data;
      document.getElementById("name").value = accountData.customer_name;
      document.getElementById("currentBalance").value =
        "PHP " + accountData.balance;
    } else {
      showError(result.message, "accountNumber");
    }
  } catch (error) {
    console.error("Error looking up account:", error);
    showError(
      "Error retrieving account information. Please try again.",
      "accountNumber"
    );
  }
}

// Form validation and submission
async function validateForm(e) {
  e.preventDefault();
  clearErrors();

  const accountNumber = document.getElementById("accountNumber").value;
  const withdrawAmount = document.getElementById("withdrawAmount").value;
  const name = document.getElementById("name").value;

  const allowedPrefixes = ["CHA", "SA"];
  const isValidFormat = (accNum) => {
      const parts = accNum.split('-');
      if (parts.length !== 3) return false;
      if (!allowedPrefixes.includes(parts[0])) return false;
      if (!/^\d{4}$/.test(parts[1])) return false;
      if (!/^\d{4}$/.test(parts[2])) return false;
      return true;
  };

  if (!isValidFormat(accountNumber)) {
      showError(
          "Please enter a valid account number (e.g., CHA-XXXX-XXXX or SA-XXXX-XXXX).",
          "accountNumber"
      );
      return false;
  }

  if (!name || !accountData) {
    showError(
      "Account not found. Please check the account number.",
      "accountNumber"
    );
    return false;
  }

  if (
    !withdrawAmount ||
    withdrawAmount.trim() === "" ||
    withdrawAmount === "PHP 0" ||
    withdrawAmount === "PHP "
  ) {
    showError(
      `Please enter a ${currentTransactionType} amount.`,
      "withdrawAmount"
    );
    return false;
  }

  const amount = parseFloat(withdrawAmount.replace(/[^\d.]/g, ""));
  if (isNaN(amount) || amount <= 0) {
    showError(
      `Please enter a valid ${currentTransactionType} amount.`,
      "withdrawAmount"
    );
    return false;
  }

  if (currentTransactionType === "withdraw") {
    const currentBalance = parseFloat(accountData.balance.replace(/,/g, ""));
    if (amount > currentBalance) {
      showError(
        `Insufficient balance. Current balance: PHP ${accountData.balance}`,
        "withdrawAmount"
      );
      return false;
    }
  }

  const submitBtn = document.querySelector(".submit-btn");
  submitBtn.disabled = true;
  submitBtn.innerHTML =
    '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

  try {
    const endpoint =
      currentTransactionType === "withdraw"
        ? `${API_BASE_URL}/employee/process-withdrawal.php`
        : `${API_BASE_URL}/employee/process-deposit.php`;

    const response = await fetch(endpoint, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        account_number: accountNumber,
        amount: amount,
      }),
    });

    const result = await response.json();

    if (result.success) {
      sessionStorage.setItem("transaction_data", JSON.stringify(result.data));

      if (currentTransactionType === "withdraw") {
        window.location.href = "withdrawal-receipt.html";
      } else if (currentTransactionType === "deposit") {
        window.location.href = "deposit-confirmation.html";
      }
    } else {
      showError(result.message);
      submitBtn.disabled = false;
      submitBtn.textContent = "Submit";
    }
  } catch (error) {
    console.error("Transaction error:", error);
    showError(
      "An error occurred while processing the transaction. Please try again."
    );
    submitBtn.disabled = false;
    submitBtn.textContent = "Submit";
  }

  return false;
}

// Cancel button handler
function handleCancel() {
  if (
    confirm("Are you sure you want to cancel? All entered data will be lost.")
  ) {
    document.getElementById("transactionForm").reset();
    setCurrentDateTime();
  }
}

// Transaction type switching
function switchTransactionType(type) {
  currentTransactionType = type;
  const buttons = document.querySelectorAll(".transaction-btn");
  buttons.forEach((btn) => btn.classList.remove("active"));

  if (type === "withdraw") {
    buttons[0].classList.add("active");
    document.querySelector(".transaction-title").textContent = "Withdraw";
    document.querySelector('label[for="withdrawAmount"]').textContent =
      "Withdraw Amount";
    document.getElementById("withdrawAmount").placeholder = "PHP 000,000";
  } else if (type === "deposit") {
    buttons[1].classList.add("active");
    document.querySelector(".transaction-title").textContent = "Deposit";
    document.querySelector('label[for="withdrawAmount"]').textContent =
      "Deposit Amount";
    document.getElementById("withdrawAmount").placeholder = "PHP 000,000";
  }

  document.getElementById("transactionForm").reset();
  setCurrentDateTime();
}

// Event listeners
document.addEventListener("DOMContentLoaded", function () {
  // Set initial date/time
  setCurrentDateTime();

  // Account number input
  const accountInput = document.getElementById("accountNumber");
  accountInput.addEventListener("input", function () {
    let rawValue = this.value.toUpperCase().replace(/[^A-Z0-9]/g, ''); // Only allow alphanumeric
    const allowedPrefixes = ["CHA", "SA"];
    const prefixLength = 3;
    let formattedValue = "";
    let matchedPrefix = "";

    // Find if the rawValue starts with any of the allowed prefixes
    for (const prefix of allowedPrefixes) {
      if (rawValue.startsWith(prefix)) {
        matchedPrefix = prefix;
        break;
      }
    }

    if (matchedPrefix) {
      formattedValue = matchedPrefix;
      let digits = rawValue.substring(matchedPrefix.length);

      if (digits.length > 0) {
        // Ensure the first hyphen is added
        formattedValue += "-" + digits.slice(0, 4);
      }
      if (digits.length > 4) {
        // Ensure the second hyphen is added
        formattedValue += "-" + digits.slice(4, 8);
      }
    } else {
      // If no full prefix matches, allow typing for potential prefixes
      if (rawValue.length <= prefixLength) {
        formattedValue = rawValue;
      } else {
        // If they typed more than prefixLength without matching a prefix,
        // clear or restrict to the maximum prefix length (3 chars).
        formattedValue = rawValue.slice(0, prefixLength);
      }
    }

    this.value = formattedValue.slice(0, 13); // Enforce total length including hyphens

    // Trigger lookup when the full formatted length is reached and a prefix is present
    if (this.value.length === 13 && allowedPrefixes.includes(this.value.substring(0, prefixLength))) {
      lookupAccount();
    } else {
      document.getElementById("name").value = "";
      document.getElementById("currentBalance").value = "";
      accountData = null;
      clearErrors();
    }
  });


  accountInput.addEventListener("blur", lookupAccount);
  accountInput.addEventListener("focus", clearErrors);

  // Amount input
  const amountInput = document.getElementById("withdrawAmount");
  amountInput.addEventListener("blur", function () {
    formatCurrency(this);
  });

  amountInput.addEventListener("focus", function () {
    if (this.value.startsWith("PHP ")) {
      this.value = this.value.replace(/[^\d]/g, "");
    }
  });

  // Form submission
  document
    .getElementById("transactionForm")
    .addEventListener("submit", validateForm);

  // Cancel button
  document.querySelector(".cancel-btn").addEventListener("click", handleCancel);

  // Transaction type buttons
  const transactionButtons = document.querySelectorAll(".transaction-btn");
  transactionButtons[0].addEventListener("click", () =>
    switchTransactionType("withdraw")
  );
  transactionButtons[1].addEventListener("click", () =>
    switchTransactionType("deposit")
  );

  // Navbar Transactions link - refresh page
  const navbarTransactionsLink = document.getElementById(
    "navbar-transactions-link"
  );
  if (navbarTransactionsLink) {
    navbarTransactionsLink.addEventListener("click", function (e) {
      e.preventDefault();
      window.location.href = "employee-transaction.html";
    });
  }
});