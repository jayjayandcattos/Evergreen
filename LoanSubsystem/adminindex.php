<?php
session_start();
include 'admin_header.php';

$host = "localhost";
$user = "root";
$pass = "";
$db = "BankingDB";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
  die("DB Error: " . $conn->connect_error);
}

// Count loan statuses
$counts = ['Approved' => 0, 'Pending' => 0, 'Rejected' => 0, 'Closed' => 0];
$statusResult = $conn->query("SELECT status, COUNT(*) as total FROM loan_applications GROUP BY status");
if ($statusResult) {
  while ($row = $statusResult->fetch_assoc()) {
    $status = ucfirst(strtolower(trim($row['status'])));
    if (array_key_exists($status, $counts)) {
      $counts[$status] = (int)$row['total'];
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Evergreen | Loan Dashboard</title>
  <link rel="stylesheet" href="adminstyle.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
  <main>
    <h1>Loan Dashboard</h1>

    <div class="cards">
      <div class="card" onclick="filterLoans('Approved')">
        <div class="card-header">
          <img src="images/activeloanicon.png" alt="Approved Loan" class="icon-img">
          <p>Approved Loans</p>
        </div>
        <h3><?= $counts['Approved'] ?></h3>
      </div>
      <div class="card" onclick="filterLoans('Pending')">
        <div class="card-header">
          <img src="images/pendinloanicon.png" alt="Pending Loan" class="icon-img">
          <p>Pending Loans</p>
        </div>
        <h3><?= $counts['Pending'] ?></h3>
      </div>
      <div class="card" onclick="filterLoans('Rejected')">
        <div class="card-header">
          <img src="images/rejectedloanicon.png" alt="Rejected Loan" class="icon-img">
          <p>Rejected Loans</p>
        </div>
        <h3><?= $counts['Rejected'] ?></h3>
      </div>
      <div class="card" onclick="filterLoans('Closed')">
        <div class="card-header">
          <img src="images/closedloanicon.png" alt="Closed Loan" class="icon-img">
          <p>Closed Loans</p>
        </div>
        <h3><?= $counts['Closed'] ?></h3>
      </div>
    </div>

    <div style="margin-bottom: 20px; text-align: center;">
      <button onclick="generateReport('all')" class="btn btn-primary">Generate All Loans Report</button>
      <button onclick="generateReport('approved')" class="btn btn-success">Generate Approved Loans Report</button>
      <button onclick="generateReport('pending')" class="btn btn-warning">Generate Pending Loans Report</button>
      <button onclick="generateReport('rejected')" class="btn btn-danger">Generate Rejected Loans Report</button>
    </div>

    <h1>Records</h1>
    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>Loan ID</th>
            <th>Client Name</th>
            <th>Loan Type</th>
            <th>Amount</th>
            <th>Loan Officer ID</th>
            <th>Time</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody id="loansTableBody">
          <?php
          // Latest first
          $result = $conn->query("SELECT * FROM loan_applications ORDER BY id DESC");
          if ($result && $result->num_rows > 0):
            while ($row = $result->fetch_assoc()):
              $applied_date = date("m/d/Y", strtotime($row['created_at'] ?? 'now'));
              $applied_time = date("h:i A", strtotime($row['created_at'] ?? 'now'));
              $statusClass = strtolower($row['status']);
              $statusStyle = ($row['status'] === 'Approved') ? 'font-weight:bold;' : '';
          ?>
              <tr data-status="<?= htmlspecialchars($row['status']) ?>">
                <td><?= htmlspecialchars($row['id']) ?></td>
                <td><?= htmlspecialchars($row['full_name']) ?></td>
                <td><?= htmlspecialchars($row['loan_type']) ?></td>
                <td>₱<?= number_format($row['loan_amount'], 2) ?></td>
                <td><?= htmlspecialchars($_SESSION['loan_officer_id'] ?? 'LO-0123') ?></td>
                <td><?= $applied_date ?> <?= $applied_time ?></td>
                <td class="<?= $statusClass ?>" style="<?= $statusStyle ?>"><?= htmlspecialchars($row['status']) ?></td>
                <td>
                  <button onclick="viewLoanDetails(<?= (int)$row['id'] ?>)">View Details</button>
                </td>
              </tr>
            <?php endwhile;
          else: ?>
            <tr>
              <td colspan="8" style="text-align:center;">No records found</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </main>

  <!-- View Details Modal -->
  <div id="viewLoanModal" class="modal">
    <div class="modal-content">
      <h2>Client Account Details</h2>
      <p><strong>Loan Officer:</strong> <?= htmlspecialchars($_SESSION['loan_officer_id'] ?? 'LO-0123') ?></p>
      <hr>

      <div class="details">
        <!-- LEFT SIDE -->
        <div class="column">
          <h3>Account Details</h3>
          <p><strong>Full Name:</strong> <span id="modal-full-name"></span></p>
          <p><strong>Account Number:</strong> <span id="modal-account-number"></span></p>
          <p><strong>Loan ID:</strong> <span id="modal-loan-id"></span></p>
          <p><strong>Contact Number:</strong> <span id="modal-contact-number"></span></p>
          <p><strong>Email:</strong> <span id="modal-email"></span></p>
          <p><strong>Job Title:</strong> <span id="modal-job"></span></p>
          <p><strong>Monthly Salary:</strong> ₱<span id="modal-monthly-salary"></span></p>

          <hr>
          <p><strong>Remarks:</strong> <span id="modal-remarks-display"></span></p>

          <!-- ✅ Approval Info -->
          <div id="approval-info" style="display:none;">
            <p><strong>Approved By:</strong> <span id="modal-approved-by"></span></p>
            <p><strong>Approved At:</strong> <span id="modal-approved-at"></span></p>
          </div>

          <!-- ✅ Rejection Info -->
          <div id="rejection-info" style="display:none;">
            <p><strong>Rejected By:</strong> <span id="modal-rejected-by"></span></p>
            <p><strong>Rejected At:</strong> <span id="modal-rejected-at"></span></p>
            <p><strong>Rejection Remarks:</strong> <span id="modal-reject-remarks"></span></p>
          </div>

          <!-- Uploaded Documents Section -->
          <div style="margin-top: 20px;">
            <h3>Uploaded Documents</h3>
            <div class="document-container">
              <div class="document-item">
                <button type="button" id="view-valid-id-btn" class="view-doc-btn" onclick="viewDocument('valid_id')">Valid ID</button>
              </div>
              <br>
              <div class="document-item">
                <button type="button" id="view-proof-income-btn" class="view-doc-btn" onclick="viewDocument('proof_of_income')">Proof of Income</button>
              </div>
              <br>
              <div class="document-item">
                <button type="button" id="view-coe-btn" class="view-doc-btn" onclick="viewDocument('coe_document')">Certificate of Employment</button>
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT SIDE -->
        <div class="column">
          <h3>Loan Details</h3>
          <p><strong>Loan Type:</strong> <span id="modal-loan-type"></span></p>
          <p><strong>Loan Amount:</strong> ₱<span id="modal-loan-amount"></span></p>
          <p><strong>Loan Term:</strong> <span id="modal-loan-term"></span></p>
          <p><strong>Purpose:</strong> <span id="modal-purpose"></span></p>
          <p><strong>Date Applied:</strong> <span id="modal-date-applied"></span></p>

          <div class="payment-summary">
            <h4>Payment Summary (20% Annual Interest)</h4>
            <p><strong>Monthly Payment:</strong> ₱<span id="modal-monthly-payment"></span></p>
            <p><strong>Total Payable:</strong> ₱<span id="modal-total-payable"></span></p>
            <p><strong>Due Date:</strong> <span id="modal-due-date"></span></p>
          </div>

          <p><strong>Status:</strong> <span id="modal-status"></span></p>
        </div>
      </div>

      <div class="return-btn-container">
        <button id="returnBtn" onclick="closeViewModal()">Return</button>
      </div>
    </div>
  </div>

  <script>
    let currentValidId = '';
    let currentProofIncome = '';
    let currentCoeDocument = '';

    function viewDocument(docType) {
      let filePath = '';
      let docName = '';

      switch (docType) {
        case 'valid_id':
          filePath = currentValidId;
          docName = 'Valid ID';
          break;
        case 'proof_of_income':
          filePath = currentProofIncome;
          docName = 'Proof of Income';
          break;
        case 'coe_document':
          filePath = currentCoeDocument;
          docName = 'Certificate of Employment';
          break;
        default:
          return;
      }

      if (!filePath) {
        alert(`No ${docName} uploaded`);
        return;
      }

      window.open(filePath, '_blank');
    }

    function viewLoanDetails(loanId) {
      fetch(`view_loan.php?id=${loanId}`)
        .then(res => res.json())
        .then(data => {
          if (data.error) return alert(data.error);

          document.getElementById('modal-full-name').textContent = data.full_name || '';
          document.getElementById('modal-account-number').textContent = data.account_number || '';
          document.getElementById('modal-loan-id').textContent = data.id || '';
          document.getElementById('modal-contact-number').textContent = data.contact_number || '';
          document.getElementById('modal-email').textContent = data.email || '';
          document.getElementById('modal-job').textContent = data.job || '';
          document.getElementById('modal-monthly-salary').textContent =
            parseFloat(data.monthly_salary || 0).toLocaleString(undefined, {
              minimumFractionDigits: 2
            });

          document.getElementById('modal-loan-type').textContent = data.loan_type || '';
          document.getElementById('modal-loan-amount').textContent =
            parseFloat(data.loan_amount || 0).toLocaleString(undefined, {
              minimumFractionDigits: 2
            });
          document.getElementById('modal-loan-term').textContent = data.loan_terms || '';
          document.getElementById('modal-purpose').textContent = data.purpose || '';
          document.getElementById('modal-status').textContent = data.status || '';
          document.getElementById('modal-remarks-display').textContent = data.remarks || '—';

          const approvalInfo = document.getElementById('approval-info');
          const rejectionInfo = document.getElementById('rejection-info');

          if (data.status === 'Approved' && data.approved_by) {
            document.getElementById('modal-approved-by').textContent = data.approved_by;
            document.getElementById('modal-approved-at').textContent = new Date(data.approved_at).toLocaleString();
            approvalInfo.style.display = 'block';
            rejectionInfo.style.display = 'none';
          } else if (data.status === 'Rejected' && data.rejected_by) {
            document.getElementById('modal-rejected-by').textContent = data.rejected_by;
            document.getElementById('modal-rejected-at').textContent = new Date(data.rejected_at).toLocaleString();
            document.getElementById('modal-reject-remarks').textContent = data.rejection_remarks || '—';
            rejectionInfo.style.display = 'block';
            approvalInfo.style.display = 'none';
          } else {
            approvalInfo.style.display = 'none';
            rejectionInfo.style.display = 'none';
          }

          const appliedDate = data.created_at ? new Date(data.created_at) : null;
          document.getElementById('modal-date-applied').textContent =
            appliedDate ? appliedDate.toLocaleDateString() : 'N/A';

          const dueDate = data.due_date ? new Date(data.due_date) : null;
          document.getElementById('modal-due-date').textContent =
            dueDate ? dueDate.toLocaleDateString() : 'N/A';

          const total = parseFloat(data.loan_amount || 0) * 1.20;
          document.getElementById('modal-total-payable').textContent =
            total.toLocaleString(undefined, {
              minimumFractionDigits: 2
            });
          document.getElementById('modal-monthly-payment').textContent =
            parseFloat(data.monthly_payment || 0).toLocaleString(undefined, {
              minimumFractionDigits: 2
            });

          // Store document paths for viewing
          currentValidId = data.file_url || '';
          currentProofIncome = data.proof_of_income || '';
          currentCoeDocument = data.coe_document || '';

          // Enable/disable view buttons based on document availability
          const validIdBtn = document.getElementById('view-valid-id-btn');
          const proofIncomeBtn = document.getElementById('view-proof-income-btn');
          const coeBtn = document.getElementById('view-coe-btn');

          if (validIdBtn) validIdBtn.disabled = !currentValidId;
          if (proofIncomeBtn) proofIncomeBtn.disabled = !currentProofIncome;
          if (coeBtn) coeBtn.disabled = !currentCoeDocument;

          document.getElementById('viewLoanModal').style.display = 'flex';
          document.getElementById('viewLoanModal').classList.add('show');
        })
        .catch(err => console.error(err));
    }

    function closeViewModal() {
      const modal = document.getElementById('viewLoanModal');
      modal.classList.remove('show');
      setTimeout(() => modal.style.display = 'none', 300);
    }

    window.onclick = function(e) {
      if (e.target === document.getElementById('viewLoanModal')) closeViewModal();
    }

    function generateReport(type) {
      fetch(`generate_report.php?type=${type}`)
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            window.open(data.filename, '_blank');
          } else {
            alert('Error: ' + (data.error || 'Unknown error'));
          }
        })
        .catch(err => {
          console.error(err);
          alert('Failed to generate report');
        });
    }

    // ✅ Card filtering with persistent active state
    function filterLoans(status) {
      // Remove active class from all cards
      document.querySelectorAll('.card').forEach(card => {
        card.classList.remove('active');
      });

      // Add active class to clicked card
      event.target.closest('.card').classList.add('active');

      // Filter table rows
      const rows = document.querySelectorAll('#loansTableBody tr');
      rows.forEach(row => {
        if (status === 'All' || row.dataset.status === status) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
      });
    }

    // Initialize: Set first card as active by default
    document.addEventListener('DOMContentLoaded', function() {
      const firstCard = document.querySelector('.card');
      if (firstCard) {
        firstCard.classList.add('active');
      }
    });
  </script>
</body>

</html>