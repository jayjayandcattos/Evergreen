// API Configuration
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

// Store all accounts data
let allAccounts = [];

// Load data on page load
document.addEventListener("DOMContentLoaded", async function () {
  // Check authentication and update employee display
  const employee = await checkAuthentication();
  if (employee) {
    updateEmployeeDisplay(employee);
  }

  loadReportsData();

  // Setup search and filter
  document
    .getElementById("search-account")
    .addEventListener("input", filterAccounts);
  document
    .getElementById("filter-status")
    .addEventListener("change", filterAccounts);
});

// Load all reports data
async function loadReportsData() {
  try {
    const response = await fetch(
      `${API_BASE_URL}/reports/get-account-statistics.php`
    );
    const result = await response.json();

    if (result.success) {
      updateStatistics(result.statistics);
      allAccounts = result.accounts;
      displayAccounts(allAccounts);
    } else {
      console.error("Failed to load reports:", result.message);
      showError("Failed to load reports data");
    }
  } catch (error) {
    console.error("Error loading reports:", error);
    showError("Error connecting to server");
  }
}

// Update statistics cards
function updateStatistics(stats) {
  document.getElementById("total-accounts").textContent =
    stats.total_accounts || "0";
  document.getElementById("active-accounts").textContent =
    stats.active_accounts || "0";
  document.getElementById("below-maintaining-accounts").textContent =
    stats.below_maintaining || "0";
  document.getElementById("closed-accounts").textContent =
    stats.closed_accounts || "0";
  document.getElementById("flagged-accounts").textContent =
    stats.flagged_for_removal || "0";
  document.getElementById("loan-approvals").textContent =
    stats.loan_approvals || "0";
}

// Display accounts in table
function displayAccounts(accounts) {
  const tbody = document.getElementById("accounts-table-body");

  if (!accounts || accounts.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="7" class="text-center text-muted">No accounts found</td>
      </tr>
    `;
    return;
  }

  tbody.innerHTML = accounts
    .map((account) => {
      const statusClass = getStatusClass(account.account_status);
      const statusText = getStatusText(account.account_status);
      const balanceClass =
        parseFloat(account.current_balance) < 500 ? "balance-low" : "";

      return `
      <tr>
        <td><strong>${account.account_number}</strong></td>
        <td>${account.customer_name}</td>
        <td>${account.account_type}</td>
        <td class="balance-amount ${balanceClass}">PHP ${formatNumber(
        account.current_balance
      )}</td>
        <td><span class="status-badge ${statusClass}">${statusText}</span></td>
        <td>${account.below_maintaining_since || "-"}</td>
        <td>${formatDateTime(account.last_updated)}</td>
      </tr>
    `;
    })
    .join("");
}

// Filter accounts based on search and status
function filterAccounts() {
  const searchTerm = document
    .getElementById("search-account")
    .value.toLowerCase();
  const statusFilter = document.getElementById("filter-status").value;

  let filtered = allAccounts;

  // Apply search filter
  if (searchTerm) {
    filtered = filtered.filter(
      (account) =>
        account.account_number.toLowerCase().includes(searchTerm) ||
        account.customer_name.toLowerCase().includes(searchTerm)
    );
  }

  // Apply status filter
  if (statusFilter) {
    filtered = filtered.filter(
      (account) => account.account_status === statusFilter
    );
  }

  displayAccounts(filtered);
}

// Get status badge class
function getStatusClass(status) {
  const statusMap = {
    active: "status-active",
    below_maintaining: "status-below-maintaining",
    flagged_for_removal: "status-flagged",
    closed: "status-closed",
  };
  return statusMap[status] || "status-active";
}

// Get status text
function getStatusText(status) {
  const textMap = {
    active: "Active",
    below_maintaining: "Below Maintaining",
    flagged_for_removal: "Flagged",
    closed: "Closed",
  };
  return textMap[status] || status;
}

// Format number with thousand separators
function formatNumber(num) {
  if (!num) return "0.00";
  return parseFloat(num).toLocaleString("en-PH", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
}

// Format date and time
function formatDateTime(dateStr) {
  if (!dateStr) return "-";
  const date = new Date(dateStr);
  return date.toLocaleDateString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

// Show error message
function showError(message) {
  const tbody = document.getElementById("accounts-table-body");
  tbody.innerHTML = `
    <tr>
      <td colspan="7" class="text-center text-danger">${message}</td>
    </tr>
  `;
}
