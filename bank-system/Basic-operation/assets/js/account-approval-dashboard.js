/**
 * Account Approval Dashboard
 * Handles fetching, displaying, and managing pending account applications
 */

// Detect API path dynamically
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
  return window.location.origin + "/bank-system/Basic-operation/api";
}

const API_BASE_URL = getApiBaseUrl();
console.log("API Base URL:", API_BASE_URL);

let allApplications = [];
let currentApplicationId = null;
let applicationModal = null;
let rejectionModal = null;
let successModal = null;
let rejectionSuccessModal = null;
let approvalConfirmModal = null;

// Initialize on page load
document.addEventListener("DOMContentLoaded", function () {
  // Initialize modals
  applicationModal = new bootstrap.Modal(
    document.getElementById("applicationModal")
  );
  rejectionModal = new bootstrap.Modal(
    document.getElementById("rejectionModal")
  );
  successModal = new bootstrap.Modal(document.getElementById("successModal"));
  rejectionSuccessModal = new bootstrap.Modal(
    document.getElementById("rejectionSuccessModal")
  );
  approvalConfirmModal = new bootstrap.Modal(
    document.getElementById("approvalConfirmModal")
  );

  // Load applications
  loadApplications();

  // Setup event listeners
  setupEventListeners();

  // Load employee name
  loadEmployeeName();
});

/**
 * Load employee name from session
 */
function loadEmployeeName() {
  const employeeName = sessionStorage.getItem("employee_name") || "Employee";
  document.getElementById("employeeName").textContent = employeeName;
}

/**
 * Setup event listeners
 */
function setupEventListeners() {
  // Search input
  document
    .getElementById("searchInput")
    .addEventListener("input", filterApplications);

  // Filter dropdowns
  document
    .getElementById("filterStatus")
    .addEventListener("change", filterApplications);
  document
    .getElementById("filterAccountType")
    .addEventListener("change", filterApplications);

  // Clear filters button
  document
    .getElementById("clearFilters")
    .addEventListener("click", clearFilters);

  // Approve button - show confirmation modal
  document
    .getElementById("approveBtn")
    .addEventListener("click", showApprovalConfirmation);

  // Confirm approve button - actually approve
  document
    .getElementById("confirmApproveBtn")
    .addEventListener("click", handleApprove);

  // Reject button
  document.getElementById("rejectBtn").addEventListener("click", () => {
    applicationModal.hide();
    rejectionModal.show();
  });

  // Confirm reject button
  document
    .getElementById("confirmRejectBtn")
    .addEventListener("click", handleReject);

  // Logout button
  document.getElementById("logoutBtn").addEventListener("click", handleLogout);
}

/**
 * Load all applications from API
 */
async function loadApplications() {
  try {
    const response = await fetch(
      `${API_BASE_URL}/customer/get-applications.php`,
      {
        method: "GET",
        credentials: "include",
      }
    );

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const result = await response.json();

    if (result.success) {
      allApplications = result.applications || [];
      updateStats();
      displayApplications(allApplications);
    } else {
      showError(result.message || "Failed to load applications");
      displayEmptyState("Failed to load applications");
    }
  } catch (error) {
    console.error("Error loading applications:", error);
    showError("Error loading applications. Please try again.");
    displayEmptyState("Error loading applications");
  }
}

/**
 * Update statistics cards
 */
function updateStats() {
  const today = new Date().toISOString().split("T")[0];

  const stats = {
    pending: allApplications.filter(
      (app) => app.application_status === "pending"
    ).length,
    approved: allApplications.filter(
      (app) =>
        app.application_status === "approved" &&
        app.submitted_at?.startsWith(today)
    ).length,
    rejected: allApplications.filter(
      (app) =>
        app.application_status === "rejected" &&
        app.submitted_at?.startsWith(today)
    ).length,
    total: allApplications.length,
  };

  document.getElementById("stat-pending").textContent = stats.pending;
  document.getElementById("stat-approved").textContent = stats.approved;
  document.getElementById("stat-rejected").textContent = stats.rejected;
  document.getElementById("stat-total").textContent = stats.total;
}

/**
 * Display applications in table
 */
function displayApplications(applications) {
  const tbody = document.getElementById("applicationsTableBody");

  if (!applications || applications.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="6">
          <div class="empty-state">
            <i class="bi bi-inbox"></i>
            <h5>No Applications Found</h5>
            <p>There are no applications matching your criteria.</p>
          </div>
        </td>
      </tr>
    `;
    return;
  }

  tbody.innerHTML = applications
    .map((app) => {
      const statusBadge = getStatusBadge(app.application_status);
      const fullName = `${app.first_name} ${app.middle_name || ""} ${
        app.last_name
      }`.trim();
      const submittedDate = formatDate(app.submitted_at);

      return `
        <tr onclick="viewApplicationDetails(${app.application_id})">
          <td><strong>${app.application_number}</strong></td>
          <td>${fullName}</td>
          <td>${app.account_type || "N/A"}</td>
          <td>${submittedDate}</td>
          <td>${statusBadge}</td>
          <td>
            <button class="btn btn-sm btn-primary" onclick="event.stopPropagation(); viewApplicationDetails(${
              app.application_id
            })">
              <i class="bi bi-eye"></i> View
            </button>
          </td>
        </tr>
      `;
    })
    .join("");
}

/**
 * Display empty state
 */
function displayEmptyState(message) {
  const tbody = document.getElementById("applicationsTableBody");
  tbody.innerHTML = `
    <tr>
      <td colspan="6">
        <div class="empty-state">
          <i class="bi bi-exclamation-circle"></i>
          <h5>${message}</h5>
        </div>
      </td>
    </tr>
  `;
}

/**
 * Filter applications based on search and filters
 */
function filterApplications() {
  const searchTerm = document.getElementById("searchInput").value.toLowerCase();
  const statusFilter = document.getElementById("filterStatus").value;
  const accountTypeFilter = document.getElementById("filterAccountType").value;

  const filtered = allApplications.filter((app) => {
    const fullName = `${app.first_name} ${app.middle_name || ""} ${
      app.last_name
    }`.toLowerCase();
    const matchesSearch =
      fullName.includes(searchTerm) ||
      app.application_number.toLowerCase().includes(searchTerm) ||
      app.email?.toLowerCase().includes(searchTerm);

    const matchesStatus =
      !statusFilter || app.application_status === statusFilter;
    const matchesAccountType =
      !accountTypeFilter || app.account_type === accountTypeFilter;

    return matchesSearch && matchesStatus && matchesAccountType;
  });

  displayApplications(filtered);
}

/**
 * Clear all filters
 */
function clearFilters() {
  document.getElementById("searchInput").value = "";
  document.getElementById("filterStatus").value = "pending";
  document.getElementById("filterAccountType").value = "";
  filterApplications();
}

/**
 * View application details
 */
async function viewApplicationDetails(applicationId) {
  currentApplicationId = applicationId;

  try {
    const response = await fetch(
      `${API_BASE_URL}/customer/get-application-details.php?id=${applicationId}`,
      {
        method: "GET",
        credentials: "include",
      }
    );

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const result = await response.json();

    if (result.success) {
      displayApplicationDetails(result.application);
      applicationModal.show();
    } else {
      showError(result.message || "Failed to load application details");
    }
  } catch (error) {
    console.error("Error loading application details:", error);
    showError("Error loading application details. Please try again.");
  }
}

/**
 * Display application details in modal
 */
function displayApplicationDetails(app) {
  const detailsContainer = document.getElementById("applicationDetails");
  // Explicit base paths (avoid /public in URLs)
  const rootBase = `${window.location.origin}/Evergreen/bank-system/Basic-operation`;
  const uploadsBase = `${rootBase}/uploads`;

  // Resolve ID images: prefer application_documents if provided
  let idFrontPath = null;
  let idBackPath = null;
  if (Array.isArray(app.documents)) {
    const frontDoc = app.documents.find(
      (d) => d.document_type === "id_front" && d.file_path
    );
    const backDoc = app.documents.find(
      (d) => d.document_type === "id_back" && d.file_path
    );
    idFrontPath = frontDoc ? frontDoc.file_path : null;
    idBackPath = backDoc ? backDoc.file_path : null;
  }

  // Helper to build a proper URL for file paths
  const buildFileUrl = (p) => {
    if (!p) return null;
    // If already absolute (http/https), return as-is
    if (/^https?:\/\//i.test(p)) return p;
    // Normalize leading slash
    const path = p.startsWith("/") ? p.slice(1) : p;
    // Prefer uploadsBase when path starts with uploads/
    if (path.startsWith("uploads/")) {
      return `${uploadsBase}/${path.replace(/^uploads\//, "")}`;
    }
    // Fallback to baseUrl
    return `${baseUrl}/${path}`;
  };

  const idFrontUrl = buildFileUrl(idFrontPath) || buildFileUrl(app.id_front_image);
  const idBackUrl = buildFileUrl(idBackPath) || buildFileUrl(app.id_back_image);

  detailsContainer.innerHTML = `
    <!-- Personal Information -->
    <div class="detail-section">
      <h5 class="section-title">Personal Information</h5>
      <div class="detail-row">
        <div class="detail-item">
          <span class="detail-label">First Name</span>
          <span class="detail-value">${app.first_name || "N/A"}</span>
        </div>
        <div class="detail-item">
          <span class="detail-label">Middle Name</span>
          <span class="detail-value">${app.middle_name || "N/A"}</span>
        </div>
      </div>
      <div class="detail-row">
        <div class="detail-item">
          <span class="detail-label">Last Name</span>
          <span class="detail-value">${app.last_name || "N/A"}</span>
        </div>
        <div class="detail-item">
          <span class="detail-label">Date of Birth</span>
          <span class="detail-value">${formatDate(app.date_of_birth)}</span>
        </div>
      </div>
      <div class="detail-row">
        <div class="detail-item">
          <span class="detail-label">Place of Birth</span>
          <span class="detail-value">${app.place_of_birth || "N/A"}</span>
        </div>
        <div class="detail-item">
          <span class="detail-label">Gender</span>
          <span class="detail-value">${app.gender || "N/A"}</span>
        </div>
      </div>
      <div class="detail-row">
        <div class="detail-item">
          <span class="detail-label">Civil Status</span>
          <span class="detail-value">${app.civil_status || "N/A"}</span>
        </div>
        <div class="detail-item">
          <span class="detail-label">Nationality</span>
          <span class="detail-value">${app.nationality || "N/A"}</span>
        </div>
      </div>
    </div>

    <!-- Contact Information -->
    <div class="detail-section">
      <h5 class="section-title">Contact Information</h5>
      <div class="detail-row">
        <div class="detail-item">
          <span class="detail-label">Email</span>
          <span class="detail-value">${app.email || "N/A"}</span>
        </div>
        <div class="detail-item">
          <span class="detail-label">Phone Number</span>
          <span class="detail-value">${app.phone_number || "N/A"}</span>
        </div>
      </div>
    </div>

    <!-- Address Information -->
    <div class="detail-section">
      <h5 class="section-title">Address Information</h5>
      <div class="detail-row">
        <div class="detail-item">
          <span class="detail-label">Street Address</span>
          <span class="detail-value">${app.street_address || "N/A"}</span>
        </div>
        <div class="detail-item">
          <span class="detail-label">Barangay</span>
          <span class="detail-value">${app.barangay_name || "N/A"}</span>
        </div>
      </div>
      <div class="detail-row">
        <div class="detail-item">
          <span class="detail-label">City</span>
          <span class="detail-value">${app.city_name || "N/A"}</span>
        </div>
        <div class="detail-item">
          <span class="detail-label">Province</span>
          <span class="detail-value">${app.province_name || "N/A"}</span>
        </div>
      </div>
      <div class="detail-row">
        <div class="detail-item">
          <span class="detail-label">Postal Code</span>
          <span class="detail-value">${app.postal_code || "N/A"}</span>
        </div>
      </div>
    </div>

    <!-- Employment Information -->
    <div class="detail-section">
      <h5 class="section-title">Employment Information</h5>
      <div class="detail-row">
        <div class="detail-item">
          <span class="detail-label">Employment Status</span>
          <span class="detail-value">${
            app.employment_status_name || app.employment_status || "N/A"
          }</span>
        </div>
        <div class="detail-item">
          <span class="detail-label">Occupation</span>
          <span class="detail-value">${app.occupation || "N/A"}</span>
        </div>
      </div>
      <div class="detail-row">
        <div class="detail-item">
          <span class="detail-label">Employer Name</span>
          <span class="detail-value">${app.employer_name || "N/A"}</span>
        </div>
        <div class="detail-item">
          <span class="detail-label">Source of Funds</span>
          <span class="detail-value">${
            app.source_of_funds_name || app.source_of_funds || "N/A"
          }</span>
        </div>
      </div>
    </div>

    <!-- Account Information -->
    <div class="detail-section">
      <h5 class="section-title">Account Information</h5>
      <div class="detail-row">
        <div class="detail-item">
          <span class="detail-label">Application Number</span>
          <span class="detail-value"><strong>${
            app.application_number
          }</strong></span>
        </div>
        <div class="detail-item">
          <span class="detail-label">Account Type</span>
          <span class="detail-value">${app.account_type || "N/A"}</span>
        </div>
      </div>
      <div class="detail-row">
        <div class="detail-item">
          <span class="detail-label">Application Status</span>
          <span class="detail-value">${getStatusBadge(
            app.application_status
          )}</span>
        </div>
        <div class="detail-item">
          <span class="detail-label">Submitted Date</span>
          <span class="detail-value">${formatDateTime(app.submitted_at)}</span>
        </div>
      </div>
    </div>

    <!-- ID Verification -->
    <div class="detail-section">
      <h5 class="section-title">ID Verification</h5>
      <div class="detail-row">
        <div class="detail-item">
          <span class="detail-label">ID Type</span>
          <span class="detail-value">${app.id_type || "N/A"}</span>
        </div>
        <div class="detail-item">
          <span class="detail-label">ID Number</span>
          <span class="detail-value">${app.id_number || "N/A"}</span>
        </div>
      </div>
      
      <div class="id-images-container mt-3">
        <div class="id-image-wrapper">
          <span class="id-image-label">Front of ID</span>
          ${
            idFrontUrl
              ? `<img src="${idFrontUrl}" alt="ID Front" class="id-image" onclick="openImageInNewTab('${idFrontUrl}')">`
              : '<div class="no-image"><i class="bi bi-image"></i> No image uploaded</div>'
          }
        </div>
        <div class="id-image-wrapper">
          <span class="id-image-label">Back of ID</span>
          ${
            idBackUrl
              ? `<img src="${idBackUrl}" alt="ID Back" class="id-image" onclick="openImageInNewTab('${idBackUrl}')">`
              : '<div class="no-image"><i class="bi bi-image"></i> No image uploaded</div>'
          }
        </div>
      </div>
    </div>

    ${
      app.rejection_reason
        ? `
    <div class="detail-section">
      <div class="alert alert-warning">
        <strong>Rejection Reason:</strong> ${app.rejection_reason}
      </div>
    </div>
    `
        : ""
    }
  `;

  // Show/hide action buttons based on status
  const modalFooter = document.getElementById("modalFooter");
  const approveBtn = document.getElementById("approveBtn");
  const rejectBtn = document.getElementById("rejectBtn");

  if (app.application_status === "pending") {
    approveBtn.style.display = "inline-block";
    rejectBtn.style.display = "inline-block";
  } else {
    approveBtn.style.display = "none";
    rejectBtn.style.display = "none";
  }
}

/**
 * Open image in new tab
 */
function openImageInNewTab(url) {
  window.open(url, "_blank");
}

/**
 * Show approval confirmation modal
 */
function showApprovalConfirmation() {
  if (!currentApplicationId) return;

  // Hide application details modal and show confirmation
  applicationModal.hide();
  approvalConfirmModal.show();
}

/**
 * Handle approve action
 */
async function handleApprove() {
  if (!currentApplicationId) return;

  // Close confirmation modal
  approvalConfirmModal.hide();

  const approveBtn = document.getElementById("confirmApproveBtn");
  const originalText = approveBtn.innerHTML;
  approveBtn.disabled = true;
  approveBtn.innerHTML =
    '<span class="spinner-border spinner-border-sm me-1"></span>Approving...';

  try {
    const response = await fetch(
      `${API_BASE_URL}/customer/approve-application.php`,
      {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        credentials: "include",
        body: JSON.stringify({ application_id: currentApplicationId }),
      }
    );

    const result = await response.json();

    if (result.success) {
      applicationModal.hide();

      console.log("Approval result:", result); // Debug log
      console.log("Account number:", result.account_number); // Debug log

      // Set success message
      document.getElementById("successMessage").textContent =
        "Application approved successfully!";

      // Show account number if available
      const successDetailsDiv = document.getElementById("successDetails");
      const successAccountNumber = document.getElementById(
        "successAccountNumber"
      );

      if (result.account_number) {
        successAccountNumber.textContent = result.account_number;
        successDetailsDiv.style.display = "block";
        console.log("Showing account number:", result.account_number); // Debug log
      } else {
        console.log("No account number in response"); // Debug log
        successDetailsDiv.style.display = "none";
      }

      // Show the modal
      successModal.show();

      loadApplications(); // Reload applications
    } else {
      showError(result.message || "Failed to approve application");
      approveBtn.disabled = false;
      approveBtn.innerHTML = originalText;
      // Re-show application modal
      applicationModal.show();
    }
  } catch (error) {
    console.error("Error approving application:", error);
    showError("Error approving application. Please try again.");
    approveBtn.disabled = false;
    approveBtn.innerHTML = originalText;
    // Re-show application modal
    applicationModal.show();
  }
}

/**
 * Handle reject action
 */
async function handleReject() {
  const reason = document.getElementById("rejectionReason").value.trim();

  if (!reason) {
    document.getElementById("rejectionReason").classList.add("is-invalid");
    return;
  }

  document.getElementById("rejectionReason").classList.remove("is-invalid");

  const confirmBtn = document.getElementById("confirmRejectBtn");
  const originalText = confirmBtn.innerHTML;
  confirmBtn.disabled = true;
  confirmBtn.innerHTML =
    '<span class="spinner-border spinner-border-sm me-1"></span>Rejecting...';

  try {
    const response = await fetch(
      `${API_BASE_URL}/customer/reject-application.php`,
      {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        credentials: "include",
        body: JSON.stringify({
          application_id: currentApplicationId,
          rejection_reason: reason,
        }),
      }
    );

    const result = await response.json();

    if (result.success) {
      rejectionModal.hide();
      applicationModal.hide();
      document.getElementById("rejectionReason").value = "";

      // Show rejection success modal
      rejectionSuccessModal.show();

      loadApplications(); // Reload applications
    } else {
      showError(result.message || "Failed to reject application");
      confirmBtn.disabled = false;
      confirmBtn.innerHTML = originalText;
    }
  } catch (error) {
    console.error("Error rejecting application:", error);
    showError("Error rejecting application. Please try again.");
    confirmBtn.disabled = false;
    confirmBtn.innerHTML = originalText;
  }
}

/**
 * Get status badge HTML
 */
function getStatusBadge(status) {
  const statusMap = {
    pending: '<span class="badge badge-pending">Pending</span>',
    approved: '<span class="badge badge-approved">Approved</span>',
    rejected: '<span class="badge badge-rejected">Rejected</span>',
  };
  return (
    statusMap[status] || '<span class="badge badge-secondary">Unknown</span>'
  );
}

/**
 * Format date
 */
function formatDate(dateString) {
  if (!dateString) return "N/A";
  const date = new Date(dateString);
  return date.toLocaleDateString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
}

/**
 * Format date and time
 */
function formatDateTime(dateString) {
  if (!dateString) return "N/A";
  const date = new Date(dateString);
  return date.toLocaleString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

/**
 * Show success message
 */
function showSuccessMessage(message) {
  document.getElementById("successMessage").textContent = message;
  // Always hide account details by default
  document.getElementById("successDetails").style.display = "none";
  successModal.show();
}

/**
 * Show error message
 */
function showError(message) {
  alert(message);
}

/**
 * Handle logout
 */
function handleLogout() {
  if (confirm("Are you sure you want to logout?")) {
    sessionStorage.clear();
    window.location.href = "../index.html";
  }
}

// Make viewApplicationDetails available globally
window.viewApplicationDetails = viewApplicationDetails;
window.openImageInNewTab = openImageInNewTab;
