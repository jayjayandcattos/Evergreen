<!--adminapplications.php--> 
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

// Count statuses
$counts = ['Active' => 0, 'Pending' => 0, 'Approved' => 0, 'Rejected' => 0, 'Closed' => 0];
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
  <title>Evergreen | Loan Applications</title>
  <link rel="stylesheet" href="adminstyle.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    .section-title {
      margin: 30px 0 15px 0;
      font-size: 22px;
      font-weight: bold;
      color: #003631;
      border-bottom: 3px solid #003631;
      padding-bottom: 10px;
    }
    .table-container {
      margin-bottom: 40px;
    }
    .pending { color: #FF9800; font-weight: bold; }
    .approved { color: #4CAF50; font-weight: bold; }
    .active { color: #2e7d32; font-weight: bold; }
    .rejected { color: #f44336; font-weight: bold; }
  </style>
</head>

<body>
  <main>
    <h1>Loan Applications Management</h1>

    <!-- PENDING LOANS TABLE -->
    <h2 class="section-title">📋 Pending Applications (<?= $counts['Pending'] ?>)</h2>
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
        <tbody id="pendingTableBody">
          <?php
          // ✅ FIXED: Corrected JOIN condition
          $result = $conn->query("
            SELECT 
              la.*, 
              lt.name AS loan_type_name,
              lvi.valid_id_type
            FROM loan_applications la 
            LEFT JOIN loan_types lt ON la.loan_type_id = lt.id 
            LEFT JOIN loan_valid_id lvi ON la.loan_valid_id_type = lvi.loan_valid_id_type
            WHERE la.status = 'Pending' 
            ORDER BY la.id DESC
          ");
          if ($result && $result->num_rows > 0):
            while ($row = $result->fetch_assoc()):
              $applied_date = date("m/d/Y", strtotime($row['created_at'] ?? 'now'));
              $applied_time = date("h:i A", strtotime($row['created_at'] ?? 'now'));
          ?>
              <tr data-loan-id="<?= (int)$row['id'] ?>">
                <td><?= htmlspecialchars($row['id']) ?></td>
                <td><?= htmlspecialchars($row['full_name']) ?></td>
                <td><?= htmlspecialchars($row['loan_type_name'] ?? 'N/A') ?></td>
                <td>₱<?= number_format($row['loan_amount'], 2) ?></td>
                <td><?= htmlspecialchars($_SESSION['loan_officer_id'] ?? 'LO-0123') ?></td>
                <td><?= $applied_date ?> <?= $applied_time ?></td>
                <td class="pending">Pending</td>
                <td>
                  <button onclick="viewLoanApplication(<?= (int)$row['id'] ?>, 'pending')">View Details</button>
                </td>
              </tr>
            <?php endwhile;
          else: ?>
            <tr><td colspan="8" style="text-align:center;">No pending loans</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- APPROVED LOANS TABLE (Awaiting 2nd Approval) -->
    <h2 class="section-title">✅ Approved Applications - Awaiting Claim (<?= $counts['Approved'] ?>)</h2>
    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>Loan ID</th>
            <th>Client Name</th>
            <th>Loan Type</th>
            <th>Amount</th>
            <th>Loan Officer ID</th>
            <th>Approved Date</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody id="approvedTableBody">
          <?php
          // ✅ FIXED: Corrected JOIN condition
          $result = $conn->query("
            SELECT 
              la.*, 
              lt.name AS loan_type_name,
              lvi.valid_id_type
            FROM loan_applications la 
            LEFT JOIN loan_types lt ON la.loan_type_id = lt.id 
            LEFT JOIN loan_valid_id lvi ON la.loan_valid_id_type = lvi.loan_valid_id_type
            WHERE la.status = 'Approved' 
            ORDER BY la.approved_at DESC
          ");
          if ($result && $result->num_rows > 0):
            while ($row = $result->fetch_assoc()):
              $approved_date = date("m/d/Y", strtotime($row['approved_at'] ?? 'now'));
              $approved_time = date("h:i A", strtotime($row['approved_at'] ?? 'now'));
          ?>
              <tr data-loan-id="<?= (int)$row['id'] ?>">
                <td><?= htmlspecialchars($row['id']) ?></td>
                <td><?= htmlspecialchars($row['full_name']) ?></td>
                <td><?= htmlspecialchars($row['loan_type_name'] ?? 'N/A') ?></td>
                <td>₱<?= number_format($row['loan_amount'], 2) ?></td>
                <td><?= htmlspecialchars($_SESSION['loan_officer_id'] ?? 'LO-0123') ?></td>
                <td><?= $approved_date ?> <?= $approved_time ?></td>
                <td class="approved">Approved</td>
                <td>
                  <button onclick="viewLoanApplication(<?= (int)$row['id'] ?>, 'approved')">View Details</button>
                </td>
              </tr>
            <?php endwhile;
          else: ?>
            <tr><td colspan="8" style="text-align:center;">No approved loans awaiting claim</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </main>

  <!-- Application Details Modal -->
  <div id="statusModal" class="modal">
    <div class="status-modal" style="max-height: 90vh; overflow-y: auto; position: relative;">
      <span class="close-status" onclick="closeApplicationModal()">&times;</span>
      <div style="padding: 1.5rem;">
        <h2 style="margin-top: 0;">Loan Application Details</h2>
        <hr>
        
        <!-- Account Information -->
        <h3>Account Information</h3>
        <div class="info-grid">
          <div class="field"><label>Full Name</label><input type="text" id="modal-full-name" readonly></div>
          <div class="field"><label>Account Number</label><input type="text" id="modal-account-number" readonly></div>
          <div class="field"><label>Loan ID</label><input type="text" id="modal-loan-id" readonly></div>
          <div class="field"><label>Contact Number</label><input type="text" id="modal-contact-number" readonly></div>
          <div class="field"><label>Email Address</label><input type="text" id="modal-email" readonly></div>
          <div class="field"><label>Job Title</label><input type="text" id="modal-job" readonly></div>
          <div class="field"><label>Monthly Salary</label><input type="text" id="modal-monthly-salary" readonly></div>
          <div class="field"><label>Date Applied</label><input type="text" id="modal-date-applied" readonly></div>
        </div>
          </br>
        
        <!-- Loan Details -->
        <h3>Loan Details</h3>
        <div class="info-grid">
          <div class="field"><label>Loan Type</label><input type="text" id="modal-loan-type" readonly></div>
          <div class="field"><label>Loan Term</label><input type="text" id="modal-loan-term" readonly></div>
          <div class="field"><label>Loan Amount</label><input type="text" id="modal-loan-amount" readonly></div>
          <div class="field"><label>Purpose</label><input type="text" id="modal-purpose" readonly></div>
          <div class="field"><label>Valid ID Type</label><input type="text" id="modal-valid-id-type" readonly></div>
          <div class="field"><label>Valid ID Number</label><input type="text" id="modal-valid-id-number" readonly></div>
        </div>
          </br>
        
        <!-- Payment Summary -->
        <h3>Payment Summary (20% Annual Interest)</h3>
        <div class="info-grid">
          <div class="field"><label>Monthly Payment</label><input type="text" id="modal-monthly-payment" readonly></div>
          <div class="field"><label>Total Payable</label><input type="text" id="modal-total-payable" readonly></div>
          <div class="field"><label>Due Date</label><input type="text" id="modal-due-date" readonly></div>
          <div class="field"><label>Status</label><input type="text" id="modal-status" readonly></div>
        </div>

        <!-- Uploaded Documents -->
        <h3>Uploaded Documents</h3>
        <div class="info-grid">
          <div class="field"><label>Valid ID</label><button type="button" id="view-valid-id-btn" class="view-doc-btn" onclick="viewDocument('valid_id')">View Document</button></div>
          <div class="field"><label>Proof of Income</label><button type="button" id="view-proof-income-btn" class="view-doc-btn" onclick="viewDocument('proof_of_income')">View Document</button></div>
          <div class="field"><label>COE</label><button type="button" id="view-coe-btn" class="view-doc-btn" onclick="viewDocument('coe_document')">View Document</button></div>
        </div>

        <!-- Action Buttons -->
        <div class="button-group">
          <button class="back-status" onclick="closeApplicationModal()">Back</button>
          <button id="approve-btn" class="approve-btn" onclick="confirmAndApproveLoan()">Approve</button>
          <button class="reject-btn" onclick="confirmAndRejectLoan()">Reject</button>
        </div>
      </div>
    </div>
  </div>

<script>
    let currentLoanId = null;
    let currentLoanStage = 'pending';
    let currentValidId = '';
    let currentProofIncome = '';
    let currentCoeDocument = '';
    let currentClientName = '';

    function viewDocument(docType) {
      let filePath = '';
      let docName = '';
      switch (docType) {
        case 'valid_id': filePath = currentValidId; docName = 'Valid ID'; break;
        case 'proof_of_income': filePath = currentProofIncome; docName = 'Proof of Income'; break;
        case 'coe_document': filePath = currentCoeDocument; docName = 'COE'; break;
        default: return;
      }
      if (!filePath) { alert(`No ${docName} uploaded`); return; }
      window.open(filePath, '_blank');
    }

    function viewLoanApplication(loanId, stage) {
      currentLoanId = loanId;
      currentLoanStage = stage;
      
      fetch('view_loan.php?id=' + loanId)
        .then(res => res.json())
        .then(data => {
          if (data.error) { alert(data.error); return; }

          currentClientName = data.full_name || '';
          document.getElementById('modal-full-name').value = data.full_name || '';
          document.getElementById('modal-account-number').value = data.account_number || '';
          document.getElementById('modal-loan-id').value = data.id || '';
          document.getElementById('modal-contact-number').value = data.contact_number || '';
          document.getElementById('modal-email').value = data.email || '';
          document.getElementById('modal-job').value = data.job || '';
          
          const monthlySalary = parseFloat(data.monthly_salary) || 0;
          document.getElementById('modal-monthly-salary').value = '₱' + monthlySalary.toLocaleString(undefined, {minimumFractionDigits: 2});
          
          const appliedDate = data.created_at ? new Date(data.created_at) : null;
          document.getElementById('modal-date-applied').value = appliedDate ? appliedDate.toLocaleDateString('en-US', {year: 'numeric', month: 'long', day: 'numeric'}) : 'N/A';
          
          document.getElementById('modal-loan-type').value = data.loan_type || '';
          document.getElementById('modal-loan-term').value = data.loan_terms || '';
          document.getElementById('modal-loan-amount').value = '₱' + parseFloat(data.loan_amount || 0).toLocaleString(undefined, {minimumFractionDigits: 2});
          document.getElementById('modal-purpose').value = data.purpose || '';
          
          // ✅ FIXED: Now correctly receives valid_id_type from API
          document.getElementById('modal-valid-id-type').value = data.valid_id_type || 'N/A';
          document.getElementById('modal-valid-id-number').value = data.valid_id_number || 'N/A';
          
          const monthlyPayment = parseFloat(data.monthly_payment) || 0;
          document.getElementById('modal-monthly-payment').value = '₱' + monthlyPayment.toLocaleString(undefined, {minimumFractionDigits: 2});
          document.getElementById('modal-total-payable').value = '₱' + (parseFloat(data.loan_amount || 0) * 1.20).toLocaleString(undefined, {minimumFractionDigits: 2});
          
          const dueDate = data.due_date ? new Date(data.due_date) : null;
          document.getElementById('modal-due-date').value = dueDate ? dueDate.toLocaleDateString('en-US', {year: 'numeric', month: 'long', day: 'numeric'}) : 'N/A';
          document.getElementById('modal-status').value = data.status || '';

          currentValidId = data.file_url || '';
          currentProofIncome = data.proof_of_income || '';
          currentCoeDocument = data.coe_document || '';

          document.getElementById('view-valid-id-btn').disabled = !currentValidId;
          document.getElementById('view-proof-income-btn').disabled = !currentProofIncome;
          document.getElementById('view-coe-btn').disabled = !currentCoeDocument;

          document.getElementById('statusModal').style.display = 'flex';
          document.getElementById('statusModal').classList.add('show');
        })
        .catch(err => { console.error(err); alert('Failed to load loan details.'); });
    }

    function confirmAndApproveLoan() {
      if (!currentLoanId) return;
      
      if (currentLoanStage === 'pending') {
        if (confirm('Approve this loan for ' + currentClientName + '? Client must claim within 30 days.')) {
          updateLoanStatus(currentLoanId, 'Approved', 'first_approve');
        }
      } else if (currentLoanStage === 'approved') {
        if (confirm('Confirm that ' + currentClientName + ' has claimed the loan? This will activate the loan.')) {
          updateLoanStatus(currentLoanId, 'Active', 'second_approve');
        }
      }
    }

    function confirmAndRejectLoan() {
      if (!currentLoanId) return;
      
      if (currentLoanStage === 'pending') {
        const remarks = prompt('Enter rejection reason (required):');
        if (!remarks || !remarks.trim()) {
          alert('Remarks are required for rejection.');
          return;
        }
        if (confirm('Reject this loan for ' + currentClientName + '?')) {
          updateLoanStatus(currentLoanId, 'Rejected', 'first_reject', remarks);
        }
      } else if (currentLoanStage === 'approved') {
        const remarks = prompt('Enter reason why client did not claim (optional):');
        if (confirm('Reject this approved loan for ' + currentClientName + '? They chose not to claim.')) {
          updateLoanStatus(currentLoanId, 'Rejected', 'second_reject', remarks || '');
        }
      }
    }

    function updateLoanStatus(loanId, status, action, remarks) {
      remarks = remarks || '';
      
      console.log('Sending update:', {loanId, status, action, remarks});
      
      fetch('./upload_loan_status.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          loan_id: loanId,
          status: status,
          action: action,
          remarks: remarks
        })
      })
      .then(function(res) {
        console.log('Response status:', res.status);
        console.log('Response URL:', res.url);
        
        if (res.status === 404) {
          throw new Error('update_loan_status.php not found! Make sure it is in the correct directory.');
        }
        
        if (!res.ok) {
          throw new Error('HTTP error! status: ' + res.status);
        }
        
        const contentType = res.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
          return res.text().then(text => {
            console.error('Non-JSON response:', text);
            throw new Error('Server returned non-JSON response. This usually means a PHP error.');
          });
        }
        
        return res.json();
      })
      .then(function(data) {
        console.log('Parsed data:', data);
        
        if (data.success) {
          alert(data.message);
          var row = document.querySelector('tr[data-loan-id="' + loanId + '"]');
          if (row) row.remove();
          closeApplicationModal();
          location.reload();
        } else {
          alert('Update failed: ' + (data.error || 'Unknown error'));
        }
      })
      .catch(function(err) {
        console.error('Full error:', err);
        alert('Error: ' + err.message + '\n\nCheck console (F12) for details.');
      });
    }

    function closeApplicationModal() {
      const modal = document.getElementById('statusModal');
      modal.classList.remove('show');
      setTimeout(function() { modal.style.display = 'none'; }, 300);
    }

    window.onclick = function(e) {
      if (e.target.id === 'statusModal') closeApplicationModal();
    }
  </script>
</body>
</html>