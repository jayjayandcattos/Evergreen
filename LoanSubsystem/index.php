<!---index.php--->

<?php
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user_email'])) {
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Evergreen Trust and Savings</title>
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <style>
    /* Notification Button Styles */
    .notification-btn {
      position: relative;
      background: #003631;
      color: white;
      border: none;
      padding: 12px 24px;
      border-radius: 8px;
      cursor: pointer;
      font-size: 16px;
      margin: 15px 0;
      transition: all 0.3s ease;
      font-weight: 500;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .notification-btn:hover {
      background: #005a4d;
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    
    .notification-badge {
      position: absolute;
      top: -8px;
      right: -8px;
      background: #ff4444;
      color: white;
      border-radius: 50%;
      min-width: 24px;
      height: 24px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 12px;
      font-weight: bold;
      border: 2px solid white;
      animation: pulse 2s infinite;
    }

    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.1); }
    }

    /* Notification Modal */
    .notification-modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.6);
      animation: fadeIn 0.3s;
    }

    .notification-modal-content {
      background-color: #fefefe;
      margin: 3% auto;
      padding: 0;
      border-radius: 12px;
      width: 90%;
      max-width: 700px;
      max-height: 85vh;
      overflow: hidden;
      box-shadow: 0 8px 32px rgba(0,0,0,0.2);
      animation: slideDown 0.4s ease-out;
    }

    .notification-modal-header {
      background: linear-gradient(135deg, #003631 0%, #005a4d 100%);
      color: white;
      padding: 24px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 3px solid #00796b;
    }

    .notification-modal-header h2 {
      margin: 0;
      font-size: 24px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .notification-close {
      color: white;
      font-size: 32px;
      font-weight: bold;
      cursor: pointer;
      background: none;
      border: none;
      transition: transform 0.2s;
      line-height: 1;
      padding: 0;
      width: 32px;
      height: 32px;
    }

    .notification-close:hover {
      transform: rotate(90deg);
    }

    .notification-modal-body {
      padding: 24px;
      max-height: 65vh;
      overflow-y: auto;
      background: #f8f9fa;
    }

    .notification-header-text {
      background: white;
      padding: 16px;
      border-radius: 8px;
      margin-bottom: 20px;
      border-left: 4px solid #003631;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .notification-header-text h3 {
      margin: 0;
      color: #003631;
      font-size: 18px;
    }

    .notification-item {
      background: white;
      border-left: 5px solid #003631;
      padding: 20px;
      margin-bottom: 16px;
      border-radius: 8px;
      transition: all 0.3s ease;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .notification-item:hover {
      transform: translateX(8px);
      box-shadow: 0 4px 16px rgba(0,0,0,0.12);
    }

    .notification-item.approved {
      border-left-color: #4CAF50;
      background: linear-gradient(to right, #e8f5e9 0%, white 10%);
    }

    .notification-item.rejected {
      border-left-color: #f44336;
      background: linear-gradient(to right, #ffebee 0%, white 10%);
    }

    .notification-item h3 {
      margin: 0 0 12px 0;
      color: #003631;
      font-size: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .notification-item.approved h3 {
      color: #2e7d32;
    }

    .notification-item.rejected h3 {
      color: #c62828;
    }

    .status-badge {
      display: inline-block;
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 14px;
      font-weight: bold;
    }

    .status-badge.approved {
      background: #4CAF50;
      color: white;
    }

    .status-badge.rejected {
      background: #f44336;
      color: white;
    }

    .notification-item p {
      margin: 8px 0;
      color: #555;
      line-height: 1.8;
      font-size: 15px;
    }

    .notification-item p strong {
      color: #003631;
      font-weight: 600;
      min-width: 180px;
      display: inline-block;
    }

    .notification-divider {
      height: 1px;
      background: linear-gradient(to right, transparent, #ddd, transparent);
      margin: 12px 0;
    }

    .notification-empty {
      text-align: center;
      padding: 60px 20px;
      color: #999;
    }

    .notification-empty i {
      font-size: 64px;
      color: #ddd;
      margin-bottom: 20px;
    }

    .notification-empty p {
      font-size: 18px;
      color: #666;
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    @keyframes slideDown {
      from {
        transform: translateY(-100px);
        opacity: 0;
      }
      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

    /* Scrollbar styling */
    .notification-modal-body::-webkit-scrollbar {
      width: 10px;
    }

    .notification-modal-body::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 10px;
    }

    .notification-modal-body::-webkit-scrollbar-thumb {
      background: #003631;
      border-radius: 10px;
    }

    .notification-modal-body::-webkit-scrollbar-thumb:hover {
      background: #005a4d;
    }

    .loan-card.disabled {
  opacity: 0.5;
  cursor: not-allowed !important;
  pointer-events: none;
  filter: grayscale(50%);
}

.loan-card.disabled::after {
  content: 'Application Pending';
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  background: rgba(0, 0, 0, 0.8);
  color: white;
  padding: 10px 20px;
  border-radius: 8px;
  font-weight: bold;
  font-size: 14px;
  z-index: 10;
}

/* PDF Buttons */
.download-btn {
  display: inline-block;
  background: #007bff;
  color: white;
  padding: 8px 12px;
  border-radius: 4px;
  text-decoration: none;
  margin-top: 10px;
  font-size: 14px;
  transition: all 0.2s ease;
}

.download-btn:hover {
  background: #0056b3;
  transform: translateY(-1px);
}

/* Ensure PDF buttons are visible */
.pdf-actions {
    margin-top: 15px;
    padding-top: 10px;
    border-top: 1px solid #eee;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.download-btn, .generate-pdf-btn {
    padding: 8px 12px;
    border-radius: 4px;
    font-size: 14px;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.download-btn {
    background: #007bff;
    color: white;
    text-decoration: none;
}

.download-btn:hover {
    background: #0056b3;
    transform: translateY(-1px);
}

.generate-pdf-btn {
    background: #6c757d;
    color: white;
    border: none;
    cursor: pointer;
}

.generate-pdf-btn:hover {
    background: #545b62;
    transform: translateY(-1px);
}

/* Improve notification item spacing */
.notification-item {
    background: white;
    border-left: 5px solid #003631;
    padding: 20px;
    margin-bottom: 16px;
    border-radius: 8px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    line-height: 1.5;
}

.notification-item p {
    margin: 8px 0;
    color: #555;
    font-size: 15px;
}

.notification-item p strong {
    color: #003631;
    font-weight: 600;
    min-width: 180px;
    display: inline-block;
}
  </style>
</head>
<body>

<?php include 'header.php'; ?>

<section id="home" class="hero">
  <div class="hero-content">
    <h1 class="hero-title">EVERGREEN <span style="color:#ffffff;">TRUST AND SAVINGS</span></h1>
    <h1 class="hero-subtitle" style="color:#003631;">LOAN SERVICES</h1>
    <p class="hero-description" style="color: #3A3A3AAA;">
      Bring your plans to life. Enjoy low interest rates and choose the financing option that suits your needs.
    </p>
    <div class="btn-container">
      <a href="#loan-services" class="btn btn-primary">Apply for Loan</a>
      <a href="#loan-dashboard" class="btn btn-secondary">Go to Dashboard</a>
    </div>
  </div>

  <div class="hero-image">
    <img src="landing_page.png" alt="Apply for a Loan Easily">
  </div>
</section>

<section id="loan-services" class="loan-services-wrapper">
  <h2 class="loan-services-title">LOAN SERVICES WE OFFER</h2>
  <div class="loan-cards">
    <div class="loan-card" onclick="window.location.href='Loan_AppForm.php?loanType=Personal%20Loan'">
      <img src="personalloan.png" alt="Personal Loan">
      <div class="loan-card-content">
        <h3 class="loan-card-title">Personal Loan</h3>
        <p class="loan-card-desc">Stop worrying and bring your plans to life.</p>
      </div>
    </div>
    
    <div class="loan-card" onclick="window.location.href='Loan_AppForm.php?loanType=Car%20Loan'">
      <img src="carloan.png" alt="Auto Loan">
      <div class="loan-card-content">
        <h3 class="loan-card-title">Car Loan</h3>
        <p class="loan-card-desc">Drive your new car with low rates and fast approval.</p>
      </div>
    </div>
  
    <div class="loan-card" onclick="window.location.href='Loan_AppForm.php?loanType=Home%20Loan'">
      <img src="housingloan.png" alt="Home Loan">
      <div class="loan-card-content">
        <h3 class="loan-card-title">Home Loan</h3>
        <p class="loan-card-desc">Take the first step to your new home.</p>
      </div>
    </div>

    <div class="loan-card" onclick="window.location.href='Loan_AppForm.php?loanType=Multi-Purpose%20Loan'">
      <img src="mpl.png" alt="Multipurpose Loan">
      <div class="loan-card-content">
        <h3 class="loan-card-title">Multi-Purpose Loan</h3>
        <p class="loan-card-desc">Carry on with your plans, use your property to fund your various needs.</p>
      </div>
    </div>
  </div>
</section>

<section id="loan-dashboard">
  <div class="Loan_Dashboard">
    <h1 class="loan-title">Loan Dashboard</h1>

    <section class="stats">
      <div class="card">
        <p class="card-title">Approved Loans</p>
        <p class="card-value" id="activeLoansCount">0</p>
      </div>

      <div class="card">
        <p class="card-title">Pending Applications</p>
        <p class="card-value" id="pendingLoansCount">0</p>
      </div>

      <div class="card">
        <p class="card-title">Closed Loans</p>
        <p class="card-value" id="closedLoansCount">0</p>
      </div>
    </section>

    <section class="loans">
      <div class="loan-table" style="max-height: 450px; overflow-y: auto;">
        <table>
          <thead>
            <tr>
              <th>LOAN ID</th>
              <th>TYPE</th>
              <th>AMOUNT</th>
              <th>MONTHLY PAYMENT</th>
              <th>STATUS</th>
              <th>NEXT PAYMENT DUE</th>
            </tr>
          </thead>
          <tbody id="loanTableBody">
            <tr>
              <td colspan="6" style="text-align: center; padding: 20px;">Loading...</td>
            </tr>
          </tbody>
        </table>

        <div class="loan-footer">
          <p>Your next payment is due on <b id="nextPaymentDate">-</b></p>
          <button class="pay-btn">Pay Now</button>
        </div>
      </div>

      <div class="notifications">
        <h2>Notifications</h2>
        <p id="notificationMessage">No new notifications.</p>
        <button class="notification-btn" id="viewNotificationsBtn" onclick="openNotificationModal()">
          <i class="fas fa-bell"></i> View All Notifications
          <span class="notification-badge" id="notificationBadge" style="display:none;">0</span>
        </button>
      </div>
    </section>
  </div>
</section>

<!-- Notification Modal -->
<div id="notificationModal" class="notification-modal">
  <div class="notification-modal-content">
    <div class="notification-modal-header">
      <h2><i class="fas fa-bell"></i> Your Notifications</h2>
      <button class="notification-close" onclick="closeNotificationModal()">&times;</button>
    </div>
    <div class="notification-modal-body" id="notificationModalBody">
      <div class="notification-empty">
        <i class="fas fa-bell-slash"></i>
        <p>No notifications yet.</p>
      </div>
    </div>
  </div>
</div>

<footer>
  <div class="footer-container">
    <div class="footer-top-columns">
      <div class="footer-col branding-col">
        <img src="logo.png" alt="Evergreen Bank" class="footer-logo">
        <p class="tagline">Secure. Invest. Achieve. Your trusted financial partner for a prosperous future.</p>
        <div class="social-links">
          <a href="#"><i class="fab fa-facebook-f"></i></a>
          <a href="#"><i class="fab fa-twitter"></i></a>
          <a href="#"><i class="fab fa-instagram"></i></a>
          <a href="#"><i class="fab fa-linkedin-in"></i></a>
        </div>
      </div>

      <div class="footer-col">
        <h3>Products</h3>
        <ul>
          <li><a href="#">Credit Cards</a></li>
          <li><a href="#">Debit Cards</a></li>
          <li><a href="#">Prepaid Cards</a></li>
        </ul>
      </div>

      <div class="footer-col">
        <h3>Services</h3>
        <ul>
          <li><a href="#">Home Loans</a></li>
          <li><a href="#">Personal Loans</a></li>
          <li><a href="#">Auto Loans</a></li>
          <li><a href="#">Multipurpose Loans</a></li>
          <li><a href="#">Website Banking</a></li>
        </ul>
      </div>

      <div class="footer-col">
        <h3>Contact Us</h3>
        <p class="contact-item">
          <i class="fas fa-phone-alt"></i> 1-800-EVERGREEN
        </p>
        <p class="contact-item">
          <i class="fas fa-envelope"></i> support@evergreenbank.com
        </p>
        <p class="contact-item address">
          <i class="fas fa-map-marker-alt"></i> 123 Financial District, Suite 500<br>New York, NY 10004
        </p>
      </div>
    </div>

    <div class="footer-bottom-bar">
      <p class="copyright-text">&copy; 2025 Evergreen Bank. All rights reserved.</p>
      <div class="legal-links">
        <a href="#">Privacy Policy</a>
        <span class="separator">|</span>
        <a href="#">Terms and Agreements</a>
        <span class="separator">|</span>
        <a href="#">FAQs</a>
        <span class="separator">|</span>
        <a href="#">About Us</a>
      </div>
      <p class="disclaimer">Member FDIC. Equal Housing Lender. Evergreen Bank, N.A.</p>
    </div>
  </div>
</footer>

<script>
let allLoans = [];

document.addEventListener("DOMContentLoaded", async function () {
  const tbody = document.getElementById('loanTableBody');
  const activeLoansCount = document.getElementById('activeLoansCount');
  const pendingLoansCount = document.getElementById('pendingLoansCount');
  const closedLoansCount = document.getElementById('closedLoansCount');
  const nextPaymentDate = document.getElementById('nextPaymentDate');
  const notificationMessage = document.getElementById('notificationMessage');
  const notificationBadge = document.getElementById('notificationBadge');

  async function loadLoans() {
    try {
      const response = await fetch('fetch_loan.php', {
        method: 'GET',
        credentials: 'include'
      });

      if (!response.ok) {
        throw new Error('Network response was not ok');
      }

      const loans = await response.json();

      // Handle error response from PHP
      if (loans.error) {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 20px; color: red;">${loans.error}</td></tr>`;
        return;
      }

      // Store loans globally for notifications
      allLoans = loans;

      // ✅ Check if user has pending loans and disable cards
      const hasPendingLoan = loans.some(loan => loan.status === 'Pending');
      const loanCards = document.querySelectorAll('.loan-card');
      
      if (hasPendingLoan) {
        loanCards.forEach(card => {
          card.classList.add('disabled');
          card.style.position = 'relative';
          // Remove click handler
          card.onclick = null;
        });
      } else {
        loanCards.forEach(card => {
          card.classList.remove('disabled');
          // Restore click handlers
          const loanType = card.querySelector('.loan-card-title').textContent;
          let urlLoanType;
          // Map card titles to dropdown values
          if (loanType === 'Housing Loan') {
            urlLoanType = 'Home Loan';
          } else if (loanType === 'Multipurpose Loan') {
            urlLoanType = 'Multi-Purpose Loan';
          } else {
            urlLoanType = loanType;
          }
          card.onclick = function() {
            window.location.href = `Loan_AppForm.php?loanType=${encodeURIComponent(urlLoanType)}`;
          };
        });
      }

      // Sort loans: latest transaction first (most recent approved or rejected)
      loans.sort((a, b) => {
        let dateA, dateB;
        
        // Get the most relevant date for each loan
        if (a.status === 'Approved' && a.approved_at) {
          dateA = new Date(a.approved_at);
        } else if (a.status === 'Rejected' && a.rejected_at) {
          dateA = new Date(a.rejected_at);
        } else {
          dateA = new Date(a.created_at || 0);
        }
        
        if (b.status === 'Approved' && b.approved_at) {
          dateB = new Date(b.approved_at);
        } else if (b.status === 'Rejected' && b.rejected_at) {
          dateB = new Date(b.rejected_at);
        } else {
          dateB = new Date(b.created_at || 0);
        }
        
        return dateB - dateA; // Most recent first
      });

      tbody.innerHTML = '';

      if (loans.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 20px;">No loans found. Apply for a loan to get started!</td></tr>';
        activeLoansCount.textContent = '0';
        pendingLoansCount.textContent = '0';
        closedLoansCount.textContent = '0';
        nextPaymentDate.textContent = '-';
        notificationMessage.textContent = 'No loans yet. Apply for a loan above!';
        notificationBadge.style.display = 'none';
        return;
      }

      // Count loans by status
      let activeCount = 0;
      let pendingCount = 0;
      let closedCount = 0;
      let rejectedCount = 0;
      let earliestDueDate = null;
      let notificationCount = 0;

      loans.forEach((loan) => {
        // Count by status
        if (loan.status === 'Approved') {
          activeCount++;
          notificationCount++;
          // Track earliest due date from next_payment_due
          if (loan.next_payment_due) {
            const dueDate = new Date(loan.next_payment_due);
            if (!earliestDueDate || dueDate < earliestDueDate) {
              earliestDueDate = dueDate;
            }
          }
        } else if (loan.status === 'Pending') {
          pendingCount++;
        } else if (loan.status === 'Rejected') {
          closedCount++;
          rejectedCount++;
          notificationCount++;
        } else if (loan.status === 'Closed') {
          closedCount++;
        }

        // Format status for display
        let displayStatus = loan.status;
        let statusStyle = '';
        if (loan.status === 'Approved') {
          displayStatus = 'Approved';
          statusStyle = 'style="color: #4CAF50; font-weight: bold;"';
        } else if (loan.status === 'Rejected') {
          statusStyle = 'style="color: #f44336; font-weight: bold;"';
        } else if (loan.status === 'Pending') {
          statusStyle = 'style="color: #FF9800; font-weight: bold;"';
        }

        // Format next payment due
        let nextPayment = '-';
        if (loan.status === 'Approved' && loan.next_payment_due) {
          const date = new Date(loan.next_payment_due);
          nextPayment = date.toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric' 
          });
        } else if (loan.status === 'Pending') {
          nextPayment = 'Pending Approval';
        } else if (loan.status === 'Rejected') {
          nextPayment = 'N/A';
        }

        const row = `
          <tr>
            <td>${loan.id}</td>
            <td>${loan.loan_type || 'N/A'}</td>
            <td>₱${parseFloat(loan.loan_amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
            <td>₱${parseFloat(loan.monthly_payment || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
            <td ${statusStyle}>${displayStatus}</td>
            <td>${nextPayment}</td>
          </tr>
        `;
        tbody.insertAdjacentHTML('beforeend', row);
      });

      // Update stats cards
      activeLoansCount.textContent = activeCount;
      pendingLoansCount.textContent = pendingCount;
      closedLoansCount.textContent = closedCount;

      // Update next payment date
      if (earliestDueDate) {
        nextPaymentDate.textContent = earliestDueDate.toLocaleDateString('en-US', { 
          year: 'numeric', 
          month: 'long', 
          day: 'numeric' 
        });
      } else {
        nextPaymentDate.textContent = '-';
      }

      // Update notification badge
      if (notificationCount > 0) {
        notificationBadge.textContent = notificationCount;
        notificationBadge.style.display = 'flex';
      } else {
        notificationBadge.style.display = 'none';
      }

      // Update notification message
      if (activeCount > 0 && rejectedCount > 0) {
        notificationMessage.innerHTML = `You have <strong>${activeCount}</strong> approved and <strong>${rejectedCount}</strong> rejected loan${rejectedCount > 1 ? 's' : ''}.`;
      } else if (activeCount > 0) {
        notificationMessage.innerHTML = `You have <strong>${activeCount}</strong> approved loan${activeCount > 1 ? 's' : ''}.`;
      } else if (rejectedCount > 0) {
        notificationMessage.innerHTML = `You have <strong>${rejectedCount}</strong> rejected loan${rejectedCount > 1 ? 's' : ''}.`;
      } else if (pendingCount > 0) {
        notificationMessage.innerHTML = `You have <strong>${pendingCount}</strong> pending application${pendingCount > 1 ? 's' : ''}.`;
      } else {
        notificationMessage.textContent = 'All your loans are settled. Great job!';
      }

    } catch (error) {
      console.error('Error loading loans:', error);
      tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 20px; color: red;">Error loading loan data. Please refresh the page.</td></tr>';
    }
  }

  loadLoans();
});

function openNotificationModal() {
  const modal = document.getElementById('notificationModal');
  const modalBody = document.getElementById('notificationModalBody');
  
  // Filter loans that are approved or rejected
  const notifications = allLoans.filter(loan => loan.status === 'Approved' || loan.status === 'Rejected');
  
  if (notifications.length === 0) {
    modalBody.innerHTML = `
      <div class="notification-empty">
        <i class="fas fa-bell-slash"></i>
        <p>No notifications yet.</p>
      </div>
    `;
  } else {
    let notificationHTML = '<div class="notification-header-text"><h3>📢 You have new notifications</h3></div>';
    
    notifications.forEach((loan) => {
      const isApproved = loan.status === 'Approved';
      const statusClass = isApproved ? 'approved' : 'rejected';
      const statusIcon = isApproved ? '✅' : '❌';
      const statusText = isApproved ? 'Approved' : 'Rejected';
      
      // Format next payment due
      let nextPaymentDue = 'N/A';
      if (isApproved && loan.next_payment_due) {
        const date = new Date(loan.next_payment_due);
        nextPaymentDue = date.toLocaleDateString('en-US', { 
          year: 'numeric', 
          month: 'short', 
          day: 'numeric' 
        });
      }
      
      // Get the correct remarks based on status
      let remarksText = '';
      if (isApproved && loan.remarks) {
        remarksText = loan.remarks;
      } else if (!isApproved && loan.rejection_remarks) {
        remarksText = loan.rejection_remarks;
      }
      
      // Generate PDF button HTML
      let pdfButton = '';
      if (loan.pdf_path) {
        pdfButton = `<a href="${loan.pdf_path}" class="download-btn" download><i class="fas fa-download"></i> Download PDF</a>`;
      } else {
        pdfButton = `<button class="generate-pdf-btn" onclick="generatePDF(${loan.id}, '${isApproved ? 'approved' : 'rejected'}')"><i class="fas fa-file-pdf"></i> Generate PDF</button>`;
      }
      
      notificationHTML += `
        <div class="notification-item ${statusClass}">
          <h3>${statusIcon} Loan Application ${statusText} <span class="status-badge ${statusClass}">${statusText}</span></h3>
          <div class="notification-divider"></div>
          <p><strong>Loan Amount:</strong> ₱${parseFloat(loan.loan_amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
          <p><strong>Term:</strong> ${loan.loan_terms || 'N/A'}</p>
          <p><strong>Interest:</strong> 20% per annum</p>
          <p><strong>Monthly Payment:</strong> ₱${parseFloat(loan.monthly_payment || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
          <p><strong>Status:</strong> ${statusText}</p>
          ${isApproved ? `<p><strong>Next Payment Due:</strong> ${nextPaymentDue}</p>` : ''}
          ${remarksText ? `<p><strong>${isApproved ? 'Approval' : 'Rejection'} Remarks:</strong> ${remarksText}</p>` : ''}
          <div class="pdf-actions">
            ${pdfButton}
          </div>
        </div>
      `;
    });
    
    modalBody.innerHTML = notificationHTML;
  }
  
  modal.style.display = 'block';
}

function closeNotificationModal() {
  const modal = document.getElementById('notificationModal');
  modal.style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
  const modal = document.getElementById('notificationModal');
  if (event.target === modal) {
    closeNotificationModal();
  }
}

function generatePDF(loanId, type) {
  const button = event.target;
  const originalText = button.innerHTML;
  button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
  button.disabled = true;
  
  fetch(`generate_pdf.php?loan_id=${loanId}&type=${type}`)
    .then(response => {
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      return response.json();
    })
    .then(data => {
      if (data.success) {
        return fetch('update_loan_pdf.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ loan_id: loanId, pdf_path: data.filename })
        }).then(res => res.json()).then(updateData => {
          if (updateData.success) {
            button.outerHTML = `<a href="${data.filename}" class="download-btn" download><i class="fas fa-download"></i> Download PDF</a>`;
            const loanIndex = allLoans.findIndex(loan => loan.id === loanId);
            if (loanIndex !== -1) allLoans[loanIndex].pdf_path = data.filename;
          } else {
            throw new Error('Update failed: ' + (updateData.error || 'Unknown'));
          }
        });
      } else {
        throw new Error(data.error || 'Unknown error');
      }
    })
    .catch(err => {
      console.error('PDF Error:', err);
      alert('Error generating PDF: ' + err.message);
      button.innerHTML = originalText;
      button.disabled = false;
    });
}

// Auto-scroll to dashboard after loan submission
const urlParams = new URLSearchParams(window.location.search);
const scrollTo = urlParams.get('scrollTo');

if (scrollTo === 'dashboard') {
  // Wait a bit for the page to fully load
  setTimeout(() => {
    const dashboardSection = document.getElementById('loan-dashboard');
    if (dashboardSection) {
      dashboardSection.scrollIntoView({ 
        behavior: 'smooth', 
        block: 'start' 
      });
    }
    
    // Clean up URL by removing the parameter
    const newUrl = window.location.pathname;
    window.history.replaceState({}, document.title, newUrl);
  }, 500); // 500ms delay to ensure content is loaded
}
</script>

</body>
</html>