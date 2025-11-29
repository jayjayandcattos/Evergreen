// Populate deposit confirmation with transaction data
document.addEventListener("DOMContentLoaded", function () {
  // Get transaction data from sessionStorage
  const transactionDataStr = sessionStorage.getItem("transaction_data");

  if (!transactionDataStr) {
    alert("No transaction data found. Redirecting to transaction page.");
    window.location.href = "employee-transaction.html";
    return;
  }

  const data = JSON.parse(transactionDataStr);

  // Populate transaction information
  populateTransactionInfo(data);

  // Setup button handlers
  setupButtonHandlers();

  // Setup Complete button
  const completeBtn = document.querySelector(".receipt-complete-btn");
  if (completeBtn) {
    completeBtn.addEventListener("click", showSuccessModal);
  }

  // Setup Back button
  const backBtn = document.querySelector(".receipt-back-btn");
  if (backBtn) {
    backBtn.addEventListener("click", function () {
      sessionStorage.removeItem("transaction_data");
      window.location.href = "employee-transaction.html";
    });
  }
});

function populateTransactionInfo(data) {
  // Transaction Information
  document.getElementById("receipt-date").textContent =
    data.transaction_date || "N/A";
  document.getElementById("receipt-reference").textContent =
    data.transaction_reference || "N/A";

  // Account Details
  document.getElementById("receipt-account-number").textContent =
    data.account_number ? "****" + data.account_number.slice(-4) : "N/A";
  document.getElementById("receipt-account-name").textContent =
    data.customer_name || "N/A";
  document.getElementById("receipt-account-type").textContent =
    data.account_type || "Savings";

  // Transaction Details
  document.getElementById("receipt-amount").textContent = data.amount
    ? "PHP " + data.amount
    : "N/A";
  document.getElementById("receipt-previous-balance").textContent =
    data.previous_balance ? "PHP " + data.previous_balance : "N/A";
  document.getElementById("receipt-balance").textContent = data.new_balance
    ? "PHP " + data.new_balance
    : "N/A";

  // Processing Information
  document.getElementById("receipt-branch").textContent =
    data.branch || "Main Branch";
  document.getElementById("receipt-terminal").textContent =
    data.terminal || "Teller-01";
  document.getElementById("receipt-employee").textContent =
    data.employee_name || "System Admin";
}

function printReceipt() {
  window.print();
}

function showSuccessModal() {
  const modal = document.getElementById("successModal");
  const modalOverlay = document.getElementById("modalOverlay");

  modal.style.display = "flex";
  modalOverlay.style.display = "block";

  // Add animation class after a brief delay for smooth transition
  setTimeout(() => {
    modal.classList.add("show");
    modalOverlay.classList.add("show");
  }, 10);
}

function closeSuccessModal() {
  const modal = document.getElementById("successModal");
  const modalOverlay = document.getElementById("modalOverlay");

  modal.classList.remove("show");
  modalOverlay.classList.remove("show");

  // Wait for animation to complete before hiding
  setTimeout(() => {
    modal.style.display = "none";
    modalOverlay.style.display = "none";
  }, 300);
}

function redirectToTransactions() {
  // Clear transaction data and redirect to employee transaction page
  sessionStorage.removeItem("transaction_data");
  window.location.href = "employee-transaction.html";
}

function setupButtonHandlers() {
  // Navbar Transactions link
  const navbarTransactionLink = document.getElementById(
    "navbar-transactions-link"
  );
  if (navbarTransactionLink) {
    navbarTransactionLink.addEventListener("click", function (e) {
      e.preventDefault();
      sessionStorage.removeItem("transaction_data");
      window.location.href = "employee-transaction.html";
    });
  }

  // Make "Transactions" nav link work (backup selector)
  const transactionLinks = document.querySelectorAll(
    'a[href="employee-transaction.html"], a[href="/public/employee-transaction.html"]'
  );
  transactionLinks.forEach((link) => {
    link.addEventListener("click", function (e) {
      e.preventDefault();
      // Clear transaction data
      sessionStorage.removeItem("transaction_data");
      window.location.href = "employee-transaction.html";
    });
  });

  // Sidebar buttons
  const withdrawBtn = document.querySelector(".transaction-btn:first-child");
  const depositBtn = document.querySelector(".transaction-btn:last-child");

  if (withdrawBtn) {
    withdrawBtn.addEventListener("click", function () {
      sessionStorage.removeItem("transaction_data");
      window.location.href = "employee-transaction.html";
    });
  }

  if (depositBtn) {
    depositBtn.addEventListener("click", function () {
      sessionStorage.removeItem("transaction_data");
      window.location.href = "employee-transaction.html";
    });
  }

  // Close modal when clicking overlay
  const modalOverlay = document.getElementById("modalOverlay");
  if (modalOverlay) {
    modalOverlay.addEventListener("click", closeSuccessModal);
  }

  // OK button in modal
  const okBtn = document.querySelector(".modal-ok-btn");
  if (okBtn) {
    okBtn.addEventListener("click", redirectToTransactions);
  }
}
