// Transaction History JavaScript
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
  // For bank-system/Basic-operation/public/transaction-history.html
  // API should be at bank-system/Basic-operation/api
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

// Log the API base URL for debugging
console.log('API Base URL:', API_BASE_URL);
console.log('Current path:', window.location.pathname);

let allTransactions = [];
let filteredTransactions = [];

document.addEventListener("DOMContentLoaded", function () {
  // Load transactions on page load
  loadTransactions();

  // Filter elements
  const transactionTypeSelect = document.getElementById("transactionType");
  const filterButtons = document.querySelectorAll(".filter-btn");
  const fromDateInput = document.getElementById("fromDate");
  const toDateInput = document.getElementById("toDate");
  const exportLink = document.querySelector(".export-link");

  // Transaction type filter
  if (transactionTypeSelect) {
    transactionTypeSelect.addEventListener("change", function () {
      applyFilters();
    });
  }

  // Quick date filter buttons
  filterButtons.forEach((button, index) => {
    button.addEventListener("click", function () {
      // Remove active class from all buttons
      filterButtons.forEach((btn) => btn.classList.remove("active"));
      // Add active class to clicked button
      this.classList.add("active");

      // Clear custom date inputs
      fromDateInput.value = "";
      toDateInput.value = "";

      // Apply filter based on button
      let dateFilter = "";
      if (index === 0) dateFilter = "last30days";
      else if (index === 1) dateFilter = "lastmonth";
      else if (index === 2) dateFilter = "thisyear";

      applyFilters(dateFilter);
    });
  });

  // Date range inputs
  if (fromDateInput) {
    fromDateInput.addEventListener("change", function () {
      // Remove active class from filter buttons
      filterButtons.forEach((btn) => btn.classList.remove("active"));
      applyFilters();
    });
  }

  if (toDateInput) {
    toDateInput.addEventListener("change", function () {
      // Remove active class from filter buttons
      filterButtons.forEach((btn) => btn.classList.remove("active"));
      applyFilters();
    });
  }

  // Export functionality
  if (exportLink) {
    exportLink.addEventListener("click", function (e) {
      e.preventDefault();
      showExportModal();
    });
  }

  // Export modal buttons
  const exportCSVBtn = document.getElementById("exportCSV");
  const exportPDFBtn = document.getElementById("exportPDF");

  if (exportCSVBtn) {
    exportCSVBtn.addEventListener("click", function () {
      exportAsCSV();
      const modal = bootstrap.Modal.getInstance(
        document.getElementById("exportModal")
      );
      modal.hide();
    });
  }

  if (exportPDFBtn) {
    exportPDFBtn.addEventListener("click", function () {
      exportAsPDF();
      const modal = bootstrap.Modal.getInstance(
        document.getElementById("exportModal")
      );
      modal.hide();
    });
  }

  // Set date input types to date
  fromDateInput.type = "date";
  toDateInput.type = "date";
});

// Load all transactions from database
async function loadTransactions() {
  try {
    const apiUrl = `${API_BASE_URL}/employee/get-transactions.php`;
    console.log("Fetching transactions from:", apiUrl);
    
    const response = await fetch(apiUrl);

    console.log("Response status:", response.status);
    
    // Check if response is OK before parsing JSON
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const contentType = response.headers.get("content-type");
    if (!contentType || !contentType.includes("application/json")) {
      const text = await response.text();
      console.error("Expected JSON but got:", text.substring(0, 200));
      throw new Error("Server returned non-JSON response. Check API path.");
    }
    
    const result = await response.json();
    console.log("Result:", result);

    if (result.success) {
      allTransactions = result.data;
      filteredTransactions = allTransactions;
      console.log("Loaded transactions:", allTransactions.length);
      updateTable(filteredTransactions);
    } else {
      console.error("Failed to load transactions:", result.message);
      showError(result.message || "Failed to load transactions");
    }
  } catch (error) {
    console.error("Error loading transactions:", error);
    showError("Error connecting to server. Check console for details.");
  }
}

// Apply all active filters
async function applyFilters(dateFilter = "") {
  const typeSelect = document.getElementById("transactionType");
  const fromDateInput = document.getElementById("fromDate");
  const toDateInput = document.getElementById("toDate");

  const type = typeSelect.value;
  const fromDate = fromDateInput.value;
  const toDate = toDateInput.value;

  // Build query parameters
  const params = new URLSearchParams();

  if (type && type !== "All Transaction Types") {
    params.append("type", type.toLowerCase());
  }

  if (dateFilter) {
    params.append("dateFilter", dateFilter);
  } else if (fromDate || toDate) {
    if (fromDate) params.append("from", fromDate);
    if (toDate) params.append("to", toDate);
  }

  try {
    const url = `${API_BASE_URL}/employee/get-transactions.php?${params.toString()}`;
    console.log("Filtering transactions from:", url);
    
    const response = await fetch(url);
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const contentType = response.headers.get("content-type");
    if (!contentType || !contentType.includes("application/json")) {
      const text = await response.text();
      console.error("Expected JSON but got:", text.substring(0, 200));
      throw new Error("Server returned non-JSON response. Check API path.");
    }
    
    const result = await response.json();

    if (result.success) {
      filteredTransactions = result.data;
      updateTable(filteredTransactions);
    } else {
      console.error("Failed to filter transactions:", result.message);
      showError(result.message || "Failed to filter transactions");
    }
  } catch (error) {
    console.error("Error filtering transactions:", error);
    showError("Error filtering transactions: " + error.message);
  }
}

// Update table with transaction data
function updateTable(transactions) {
  const tbody = document.querySelector(".transaction-table tbody");
  tbody.innerHTML = "";

  if (transactions.length === 0) {
    tbody.innerHTML =
      '<tr><td colspan="8" style="text-align: center; padding: 40px;">No transactions found</td></tr>';
    return;
  }

  transactions.forEach((transaction) => {
    const row = createTableRow(transaction);
    tbody.appendChild(row);
  });
}

// Create table row element
function createTableRow(transaction) {
  const row = document.createElement("tr");
  row.style.cursor = "pointer";

  // Determine status icon
  let statusIcon = "☑"; // Completed
  let statusClass = "status-success";

  if (transaction.status === "Pending") {
    statusIcon = "⊡";
    statusClass = "status-pending";
  } else if (transaction.status === "Failed") {
    statusIcon = "☒";
    statusClass = "status-failed";
  }

  row.innerHTML = `
    <td>${transaction.date}</td>
    <td>${transaction.reference}</td>
    <td>${transaction.account_number}</td>
    <td>${transaction.title || transaction.customer_name}</td>
    <td>${transaction.type}</td>
    <td>${transaction.method}</td>
    <td>PHP ${transaction.amount}</td>
    <td><span class="status-icon ${statusClass}">${statusIcon}</span></td>
  `;

  // Add click event
  row.addEventListener("click", function () {
    viewTransactionDetails(transaction);
  });

  return row;
}

// View transaction details
function viewTransactionDetails(transaction) {
  console.log("View transaction details:", transaction);
  // TODO: Show modal or navigate to detail page
  alert(
    `Transaction ID: ${transaction.reference}\nAmount: PHP ${transaction.amount}\nType: ${transaction.type}\nDate: ${transaction.date}`
  );
}

// Show export modal
function showExportModal() {
  const exportModal = new bootstrap.Modal(
    document.getElementById("exportModal")
  );
  exportModal.show();
}

// Export as CSV
function exportAsCSV() {
  if (filteredTransactions.length === 0) {
    alert("No transactions to export");
    return;
  }

  // Create CSV content
  let csv =
    "Date,Transaction ID,Account Number,Title,Customer Name,Type,Method,Amount,Status\n";

  filteredTransactions.forEach((transaction) => {
    csv += `"${transaction.date}","${transaction.reference}","${transaction.account_number}","${transaction.title || transaction.customer_name}","${transaction.customer_name}","${transaction.type}","${transaction.method}","PHP ${transaction.amount}","${transaction.status}"\n`;
  });

  // Download CSV
  const blob = new Blob([csv], { type: "text/csv" });
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = `transactions_${new Date().toISOString().split("T")[0]}.csv`;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  window.URL.revokeObjectURL(url);
}

// Export as PDF
function exportAsPDF() {
  if (filteredTransactions.length === 0) {
    alert("No transactions to export");
    return;
  }

  // Create HTML content for PDF
  let htmlContent = `
    <!DOCTYPE html>
    <html>
    <head>
      <title>Transaction History Report</title>
      <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        h1 { color: #003631; text-align: center; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background-color: #003631; color: white; padding: 12px; text-align: left; }
        td { padding: 10px; border-bottom: 1px solid #ddd; }
        tr:hover { background-color: #f5f5f5; }
        .header { text-align: center; margin-bottom: 20px; }
        .date { color: #666; font-size: 14px; }
      </style>
    </head>
    <body>
      <div class="header">
        <h1>Evergreen Bank - Transaction History</h1>
        <p class="date">Generated on: ${new Date().toLocaleDateString()}</p>
      </div>
      <table>
        <thead>
          <tr>
            <th>Date</th>
            <th>Transaction ID</th>
            <th>Account No.</th>
            <th>Title</th>
            <th>Customer Name</th>
            <th>Type</th>
            <th>Method</th>
            <th>Amount</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
  `;

  filteredTransactions.forEach((transaction) => {
    htmlContent += `
      <tr>
        <td>${transaction.date}</td>
        <td>${transaction.reference}</td>
        <td>${transaction.account_number}</td>
        <td>${transaction.title || transaction.customer_name}</td>
        <td>${transaction.customer_name}</td>
        <td>${transaction.type}</td>
        <td>${transaction.method}</td>
        <td>PHP ${transaction.amount}</td>
        <td>${transaction.status}</td>
      </tr>
    `;
  });

  htmlContent += `
        </tbody>
      </table>
    </body>
    </html>
  `;

  // Open in new window for printing/saving as PDF
  const printWindow = window.open("", "_blank");
  printWindow.document.write(htmlContent);
  printWindow.document.close();
  printWindow.focus();

  // Trigger print dialog after a short delay
  setTimeout(() => {
    printWindow.print();
  }, 250);
}

// Show error message
function showError(message) {
  const tbody = document.querySelector(".transaction-table tbody");
  tbody.innerHTML = `<tr><td colspan="8" style="text-align: center; padding: 40px; color: red;">${message}</td></tr>`;
}
