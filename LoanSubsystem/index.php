<!--index.php--->
<?php
session_start();

// Auto-login bridge: Check if user is logged in via marketing system
// This allows seamless navigation from marketing pages to loan system
if (!isset($_SESSION['user_email'])) {
    // Check for marketing session variables (from evergreen-marketing)
    if (isset($_SESSION['user_id']) && isset($_SESSION['email'])) {
        // User is logged in from marketing system, auto-login to loan system
        $_SESSION['user_email'] = $_SESSION['email'];
        $_SESSION['user_name'] = $_SESSION['full_name'] ?? ($_SESSION['first_name'] . ' ' . ($_SESSION['last_name'] ?? ''));
        $_SESSION['user_role'] = 'client'; // Default role for customers from marketing
        
        $host = "localhost";
$user = "root";
$pass = "";
$db = "BankingDB";

// CREATE CONNECTION
$conn = new mysqli($host, $user, $pass, $db);

// CHECK CONNECTION
if ($conn->connect_error) {
    exit(json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]));
}

        
        $conn = new mysqli($host, $user, $pass, $db);
        if (!$conn->connect_error) {
            $email = $_SESSION['email'];
            $sql = "SELECT 
                        bc.customer_id,
                        bc.first_name,
                        bc.middle_name,
                        bc.last_name,
                        bc.email,
                        bc.contact_number,
                        (SELECT ca.account_number 
                         FROM customer_accounts ca 
                         WHERE ca.customer_id = bc.customer_id 
                         LIMIT 1) as account_number
                    FROM bank_customers bc
                    WHERE bc.email = ?
                    LIMIT 1";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    // Store user info in session for loan system
                    $_SESSION['user_email'] = $row['email'];
                    $_SESSION['user_name'] = trim($row['first_name'] . ' ' . ($row['middle_name'] ?? '') . ' ' . $row['last_name']);
                    $_SESSION['customer_id'] = $row['customer_id'];
                    $_SESSION['account_number'] = $row['account_number'] ?? null;
                    $_SESSION['contact_number'] = $row['contact_number'] ?? null;
                }
                $stmt->close();
            }
            $conn->close();
        }
    } else {
        // No session found, redirect to loan login
        header('Location: login.php');
        exit();
    }
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

    .notification-item.active {
      border-left-color: #2e7d32;
      background: linear-gradient(to right, #c8e6c9 0%, white 10%);
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
      color: #4CAF50;
    }

    .notification-item.active h3 {
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

    .status-badge.active {
      background: #2e7d32;
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

    .notification-timestamp {
      font-size: 13px;
      color: #888;
      font-style: italic;
      margin-top: 10px;
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
        <p class="card-title">Active Loans</p>
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
          <!--<button class="pay-btn">Pay Now</button>-->
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
// ✅ FIXED: Proper notification feed with separate PDF tracking
let allLoans = [];
let allNotifications = [];

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

      if (loans.error) {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 20px; color: red;">${loans.error}</td></tr>`;
        return;
      }

      allLoans = loans;

      // ✅ BUILD NOTIFICATION FEED (separate events for each status change)
      allNotifications = [];
      loans.forEach((loan) => {
        // First approval (Pending → Approved)
        if (loan.approved_at && (loan.status === 'Approved' || loan.status === 'Active')) {
          allNotifications.push({
            id: loan.id,
            type: 'approved',
            loan_type: loan.loan_type,
            loan_amount: loan.loan_amount,
            loan_terms: loan.loan_terms,
            monthly_payment: loan.monthly_payment,
            remarks: loan.remarks,
            timestamp: loan.approved_at,
            pdf_path: loan.pdf_approved || null // ✅ Use pdf_approved column
          });
        }

        // Second approval (Approved → Active)
        if (loan.status === 'Active' && loan.approved_at) {
          allNotifications.push({
            id: loan.id,
            type: 'active',
            loan_type: loan.loan_type,
            loan_amount: loan.loan_amount,
            loan_terms: loan.loan_terms,
            monthly_payment: loan.monthly_payment,
            next_payment_due: loan.next_payment_due,
            remarks: loan.remarks,
            timestamp: loan.approved_at,
            pdf_path: loan.pdf_active || null // ✅ Use pdf_active column
          });
        }

        // Rejection
        if (loan.status === 'Rejected' && loan.rejected_at) {
          allNotifications.push({
            id: loan.id,
            type: 'rejected',
            loan_type: loan.loan_type,
            loan_amount: loan.loan_amount,
            loan_terms: loan.loan_terms,
            rejection_remarks: loan.rejection_remarks,
            timestamp: loan.rejected_at,
            pdf_path: loan.pdf_rejected || null // ✅ Use pdf_rejected column
          });
        }
      });

      // Sort notifications by timestamp (most recent first)
      allNotifications.sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp));

      // Check if user has pending loans and disable cards
      const hasPendingLoan = loans.some(loan => loan.status === 'Pending');
      const loanCards = document.querySelectorAll('.loan-card');
      
      if (hasPendingLoan) {
        loanCards.forEach(card => {
          card.classList.add('disabled');
          card.style.position = 'relative';
          card.onclick = null;
        });
      } else {
        loanCards.forEach(card => {
          card.classList.remove('disabled');
          const loanType = card.querySelector('.loan-card-title').textContent;
          let urlLoanType;
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

      // Sort loans: latest transaction first
      loans.sort((a, b) => {
        let dateA, dateB;
        
        if (a.status === 'Active' && a.approved_at) {
          dateA = new Date(a.approved_at);
        } else if (a.status === 'Rejected' && a.rejected_at) {
          dateA = new Date(a.rejected_at);
        } else if (a.status === 'Approved' && a.approved_at) {
          dateA = new Date(a.approved_at);
        } else {
          dateA = new Date(a.created_at || 0);
        }
        
        if (b.status === 'Active' && b.approved_at) {
          dateB = new Date(b.approved_at);
        } else if (b.status === 'Rejected' && b.rejected_at) {
          dateB = new Date(b.rejected_at);
        } else if (b.status === 'Approved' && b.approved_at) {
          dateB = new Date(b.approved_at);
        } else {
          dateB = new Date(b.created_at || 0);
        }
        
        return dateB - dateA;
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
      let approvedCount = 0;
      let rejectedCount = 0;
      let earliestDueDate = null;

      loans.forEach((loan) => {
        if (loan.status === 'Active') {
          activeCount++;
          if (loan.next_payment_due) {
            const dueDate = new Date(loan.next_payment_due);
            if (!earliestDueDate || dueDate < earliestDueDate) {
              earliestDueDate = dueDate;
            }
          }
        } else if (loan.status === 'Approved') {
          approvedCount++;
        } else if (loan.status === 'Pending') {
          pendingCount++;
        } else if (loan.status === 'Rejected') {
          closedCount++;
          rejectedCount++;
        } else if (loan.status === 'Closed') {
          closedCount++;
        }

        let displayStatus = loan.status;
        let statusStyle = '';
        if (loan.status === 'Active') {
          displayStatus = 'Active';
          statusStyle = 'style="color: #2e7d32; font-weight: bold;"';
        } else if (loan.status === 'Approved') {
          displayStatus = 'Approved - Awaiting Claim';
          statusStyle = 'style="color: #4CAF50; font-weight: bold;"';
        } else if (loan.status === 'Rejected') {
          statusStyle = 'style="color: #f44336; font-weight: bold;"';
        } else if (loan.status === 'Pending') {
          statusStyle = 'style="color: #FF9800; font-weight: bold;"';
        }

        let nextPayment = '-';
        if (loan.status === 'Active' && loan.next_payment_due) {
          const date = new Date(loan.next_payment_due);
          nextPayment = date.toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric' 
          });
        } else if (loan.status === 'Approved') {
          if (loan.approved_at) {
            const claimDate = new Date(loan.approved_at);
            claimDate.setDate(claimDate.getDate() + 30);
            nextPayment = 'Claim by: ' + claimDate.toLocaleDateString('en-US', { 
              year: 'numeric', 
              month: 'short', 
              day: 'numeric' 
            });
          } else {
            nextPayment = 'Awaiting Claim';
          }
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

      activeLoansCount.textContent = activeCount;
      pendingLoansCount.textContent = pendingCount;
      closedLoansCount.textContent = closedCount;

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
      const notificationCount = allNotifications.length;
      if (notificationCount > 0) {
        notificationBadge.textContent = notificationCount;
        notificationBadge.style.display = 'flex';
      } else {
        notificationBadge.style.display = 'none';
      }

      // Update notification message
      if (approvedCount > 0 && activeCount > 0 && rejectedCount > 0) {
        notificationMessage.innerHTML = `You have <strong>${approvedCount}</strong> approved (awaiting claim), <strong>${activeCount}</strong> active, and <strong>${rejectedCount}</strong> rejected loan${rejectedCount > 1 ? 's' : ''}.`;
      } else if (approvedCount > 0 && activeCount > 0) {
        notificationMessage.innerHTML = `You have <strong>${approvedCount}</strong> loan${approvedCount > 1 ? 's' : ''} awaiting claim and <strong>${activeCount}</strong> active loan${activeCount > 1 ? 's' : ''}.`;
      } else if (approvedCount > 0) {
        notificationMessage.innerHTML = `🎉 Congratulations! You have <strong>${approvedCount}</strong> approved loan${approvedCount > 1 ? 's' : ''}. Please claim within 30 days!`;
      } else if (activeCount > 0 && rejectedCount > 0) {
        notificationMessage.innerHTML = `You have <strong>${activeCount}</strong> active and <strong>${rejectedCount}</strong> rejected loan${rejectedCount > 1 ? 's' : ''}.`;
      } else if (activeCount > 0) {
        notificationMessage.innerHTML = `You have <strong>${activeCount}</strong> active loan${activeCount > 1 ? 's' : ''}.`;
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

// ✅ FIXED: Update the notification modal to use download_pdf.php
function openNotificationModal() {
  const modal = document.getElementById('notificationModal');
  const modalBody = document.getElementById('notificationModalBody');
  
  if (allNotifications.length === 0) {
    modalBody.innerHTML = `
      <div class="notification-empty">
        <i class="fas fa-bell-slash"></i>
        <p>No notifications yet.</p>
      </div>
    `;
  } else {
    let notificationHTML = '<div class="notification-header-text"><h3>📢 You have new notifications</h3></div>';
    
    allNotifications.forEach((notif) => {
      const statusClass = notif.type;
      let statusIcon = '';
      let statusText = '';
      let statusBadge = '';
      
      if (notif.type === 'approved') {
        statusIcon = '✅';
        statusText = 'Loan Approved - Awaiting Claim';
        statusBadge = 'approved';
      } else if (notif.type === 'active') {
        statusIcon = '🎉';
        statusText = 'Loan Activated';
        statusBadge = 'active';
      } else if (notif.type === 'rejected') {
        statusIcon = '❌';
        statusText = 'Loan Rejected';
        statusBadge = 'rejected';
      }
      
      const timestamp = new Date(notif.timestamp);
      const formattedDate = timestamp.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
      
      let importantDate = '';
      let importantDateLabel = '';
      
      if (notif.type === 'approved') {
        const claimDate = new Date(notif.timestamp);
        claimDate.setDate(claimDate.getDate() + 30);
        importantDate = claimDate.toLocaleDateString('en-US', { 
          year: 'numeric', 
          month: 'long', 
          day: 'numeric' 
        });
        importantDateLabel = 'Claim Deadline';
      } else if (notif.type === 'active' && notif.next_payment_due) {
        const date = new Date(notif.next_payment_due);
        importantDate = date.toLocaleDateString('en-US', { 
          year: 'numeric', 
          month: 'long', 
          day: 'numeric' 
        });
        importantDateLabel = 'Next Payment Due';
      }
      
      let remarksText = '';
      if (notif.type === 'rejected' && notif.rejection_remarks) {
        remarksText = notif.rejection_remarks;
      } else if ((notif.type === 'approved' || notif.type === 'active') && notif.remarks) {
        remarksText = notif.remarks;
      }
      
      // ✅ CRITICAL FIX: Use download_pdf.php with proper filename
      let pdfButton = '';
      if (notif.pdf_path) {
        // Extract just the filename from the path
        const filename = notif.pdf_path.replace('uploads/', '');
        pdfButton = `<a href="download_pdf.php?file=${encodeURIComponent(filename)}" class="download-btn" download><i class="fas fa-download"></i> Download PDF</a>`;
      } else {
        pdfButton = `<button class="generate-pdf-btn" onclick="generatePDF(${notif.id}, '${notif.type}', this)"><i class="fas fa-file-pdf"></i> Generate PDF</button>`;
      }
      
      notificationHTML += `
        <div class="notification-item ${statusClass}" data-notif-id="${notif.id}-${notif.type}">
          <h3>${statusIcon} ${statusText} <span class="status-badge ${statusBadge}">${statusText}</span></h3>
          <div class="notification-divider"></div>
          <p><strong>Loan ID:</strong> ${notif.id}</p>
          <p><strong>Loan Type:</strong> ${notif.loan_type || 'N/A'}</p>
          <p><strong>Loan Amount:</strong> PHP ${parseFloat(notif.loan_amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
          <p><strong>Term:</strong> ${notif.loan_terms || 'N/A'}</p>
          <p><strong>Interest:</strong> 20% per annum</p>
          ${notif.monthly_payment ? `<p><strong>Monthly Payment:</strong> PHP ${parseFloat(notif.monthly_payment).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>` : ''}
          ${importantDate ? `<p><strong>${importantDateLabel}:</strong> ${importantDate}</p>` : ''}
          ${remarksText ? `<p><strong>Remarks:</strong> ${remarksText}</p>` : ''}
          ${notif.type === 'approved' ? '<p style="color: #f57c00; font-weight: bold;"><i class="fas fa-exclamation-triangle"></i> Please visit our bank within 30 days to claim your loan!</p>' : ''}
          <p class="notification-timestamp"><i class="fas fa-clock"></i> ${formattedDate}</p>
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

window.onclick = function(event) {
  const modal = document.getElementById('notificationModal');
  if (event.target === modal) {
    closeNotificationModal();
  }
}

document.addEventListener('keydown', function(event) {
  if (event.key === 'Escape') {
    const modal = document.getElementById('notificationModal');
    if (modal.style.display === 'block') {
      closeNotificationModal();
    }
  }
});


// ✅ FIXED: generatePDF function to update download link correctly
function generatePDF(loanId, type, buttonElement) {
  const originalText = buttonElement.innerHTML;
  buttonElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
  buttonElement.disabled = true;
  
  fetch(`generate_pdf.php?loan_id=${loanId}&type=${type}`)
    .then(response => {
      if (!response.ok) {
        throw new Error(`Server returned ${response.status}: ${response.statusText}`);
      }
      
      return response.text().then(text => {
        try {
          return JSON.parse(text);
        } catch (e) {
          console.error('Invalid JSON response:', text);
          throw new Error('Server returned invalid response. Check console for details.');
        }
      });
    })
    .then(data => {
      if (data.success) {
        // Update database with PDF filename
        return fetch('update_loan_pdf.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ 
            loan_id: loanId, 
            pdf_path: data.filename,
            type: type
          })
        }).then(res => res.json()).then(updateData => {
          if (updateData.success) {
            // ✅ Extract just the filename for the download URL
            const filename = data.filename;
            const downloadUrl = `download_pdf.php?file=${encodeURIComponent(filename)}`;
            
            // ✅ Update button to download link
            buttonElement.outerHTML = `<a href="${downloadUrl}" class="download-btn" download><i class="fas fa-download"></i> Download PDF</a>`;
            
            // ✅ Update notification feed in memory
            allNotifications.forEach(notif => {
              if (notif.id === loanId && notif.type === type) {
                notif.pdf_path = updateData.pdf_path; // Store full path from database
              }
            });
            
            alert('PDF generated successfully! Click to download.');
          } else {
            throw new Error('Update failed: ' + (updateData.error || 'Unknown'));
          }
        });
      } else {
        throw new Error(data.error || 'PDF generation failed');
      }
    })
    .catch(err => {
      console.error('PDF Generation Error:', err);
      alert('Error generating PDF:\n\n' + err.message + '\n\nPlease check:\n1. FPDF library is installed in fpdf/ folder\n2. uploads/ folder has write permissions\n3. Database connection is working');
      buttonElement.innerHTML = originalText;
      buttonElement.disabled = false;
    });
}
// Auto-scroll to dashboard after loan submission
const urlParams = new URLSearchParams(window.location.search);
const scrollTo = urlParams.get('scrollTo');

if (scrollTo === 'dashboard') {
  setTimeout(() => {
    const dashboardSection = document.getElementById('loan-dashboard');
    if (dashboardSection) {
      dashboardSection.scrollIntoView({ 
        behavior: 'smooth', 
        block: 'start' 
      });
    }
    const newUrl = window.location.pathname;
    window.history.replaceState({}, document.title, newUrl);
  }, 500);
}
</script>

</body>
</html>