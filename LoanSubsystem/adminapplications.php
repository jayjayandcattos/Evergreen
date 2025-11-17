<?php
session_start();
include 'admin_header.php';

$host = "localhost";
$user = "root";
$pass = "";
$db = "bankingdb";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
  die("DB Error: " . $conn->connect_error);
}

// Count statuses (for future use if needed)
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
  <title>Evergreen | Loan Applications</title>
  <link rel="stylesheet" href="adminstyle.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
  <main>
    <h1>Loan Applications</h1>

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
        <tbody id="loanTableBody">
          <?php
          // ONLY PENDING LOANS
          $result = $conn->query("SELECT * FROM loan_applications WHERE status = 'Pending' ORDER BY id DESC");
          if ($result && $result->num_rows > 0):
            while ($row = $result->fetch_assoc()):
              $applied_date = date("m/d/Y", strtotime($row['created_at'] ?? 'now'));
              $applied_time = date("h:i A", strtotime($row['created_at'] ?? 'now'));
              $statusClass = strtolower($row['status']);
          ?>
              <tr data-loan-id="<?= (int)$row['id'] ?>">
                <td><?= htmlspecialchars($row['id']) ?></td>
                <td><?= htmlspecialchars($row['full_name']) ?></td>
                <td><?= htmlspecialchars($row['loan_type']) ?></td>
                <td>₱<?= number_format($row['loan_amount'], 2) ?></td>
                <td><?= htmlspecialchars($_SESSION['loan_officer_id'] ?? 'LO-0123') ?></td>
                <td><?= $applied_date ?> <?= $applied_time ?></td>
                <td class="<?= $statusClass ?>"><?= htmlspecialchars($row['status']) ?></td>
                <td>
                  <button onclick="viewLoanApplication(<?= (int)$row['id'] ?>)">View Details</button>
                </td>
              </tr>
            <?php endwhile;
          else: ?>
            <tr>
              <td colspan="8" style="text-align:center;">No pending loans found</td>
            </tr>
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
        <h2 style="margin-top: 0;">Loan Application Status</h2>
        <hr>
        <!-- Account Information -->
        <h3>Account Information</h3>
        <div class="info-grid">
          <div class="field">
            <label>Full Name</label>
            <input type="text" id="modal-full-name" readonly>
          </div>
          <div class="field">
            <label>Account Number</label>
            <input type="text" id="modal-account-number" readonly>
          </div>
          <div class="field">
            <label>Loan ID</label>
            <input type="text" id="modal-loan-id" readonly>
          </div>
          <div class="field">
            <label>Contact Number</label>
            <input type="text" id="modal-contact-number" readonly>
          </div>
          <div class="field">
            <label>Email Address</label>
            <input type="text" id="modal-email" readonly>
          </div>
          <div class="field">
            <label>Job Title</label>
            <input type="text" id="modal-job" readonly>
          </div>
          <div class="field">
            <label>Monthly Salary</label>
            <input type="text" id="modal-monthly-salary" readonly>
          </div>
          <div class="field">
            <label>Date Applied</label>
            <input type="text" id="modal-date-applied" readonly>
          </div>
        </div>
        <br>
        <!-- Loan Details -->
        <h3>Loan Details</h3>
        <div class="info-grid">
          <div class="field">
            <label>Loan Type</label>
            <input type="text" id="modal-loan-type" readonly>
          </div>
          <div class="field">
            <label>Loan Term</label>
            <input type="text" id="modal-loan-term" readonly>
          </div>
          <div class="field">
            <label>Loan Amount</label>
            <input type="text" id="modal-loan-amount" readonly>
          </div>
          <div class="field">
            <label>Purpose</label>
            <input type="text" id="modal-purpose" readonly>
          </div>
        </div>
        <br>
        <!-- Payment Summary -->
        <h3>Payment Summary (20% Annual Interest)</h3>
        <div class="info-grid">
          <div class="field">
            <label>Monthly Payment</label>
            <input type="text" id="modal-monthly-payment" readonly>
          </div>
          <div class="field">
            <label>Total Payable</label>
            <input type="text" id="modal-total-payable" readonly>
          </div>
          <div class="field">
            <label>Due Date</label>
            <input type="text" id="modal-due-date" readonly>
          </div>
          <div class="field">
            <label>Status</label>
            <input type="text" id="modal-status" readonly>
          </div>
        </div>

        <!-- Remarks 
        <h3>Remarks</h3>
        <div class="field" style="grid-column: span 2;">
          <label>Remarks</label>
          <textarea id="modal-remarks" readonly style="height: 80px; resize: vertical;"></textarea>
        </div>-->
        <br>
        <!-- Uploaded Documents -->
        <h3>Uploaded Documents</h3>
        <div class="info-grid">
          <div class="field">
            <label>Valid ID</label>
            <button type="button" id="view-valid-id-btn" class="view-doc-btn" onclick="viewDocument('valid_id')">View Document</button>
          </div>
          <div class="field">
            <label>Proof of Income / Payslip</label>
            <button type="button" id="view-proof-income-btn" class="view-doc-btn" onclick="viewDocument('proof_of_income')">View Document</button>
          </div>
          <div class="field">
            <label>Certificate of Employment (COE)</label>
            <button type="button" id="view-coe-btn" class="view-doc-btn" onclick="viewDocument('coe_document')">View Document</button>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="button-group">
          <button class="back-status" onclick="closeApplicationModal()">Back</button>
          <button class="approve-btn" onclick="confirmAndApproveLoan()">Approve</button>
          <button class="reject-btn" onclick="confirmAndRejectLoan()">Reject</button>
          <!-- <button class="remarks-btn" onclick="openRemarksModal()">Add Remarks</button>-->
        </div>
      </div>
    </div>
  </div>

  <!-- Remarks Modal -->
  <div id="remarksModal" class="modal">
    <div class="remarks-modal">
      <button class="close-btn" onclick="closeRemarksModal()">&times;</button>
      <div class="remarks-content">
        <h3>Add Remarks</h3>
        <textarea id="remarksText" placeholder="Input Remarks....."></textarea>
      </div>
      <div class="remarks-buttons">
        <button class="back-remarks" onclick="closeRemarksModal()">Back</button>
        <button class="submit-btn" onclick="submitRemarks()">Submit</button>
      </div>
    </div>
  </div>

  <script>
    let currentLoanId = null;
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

    function viewLoanApplication(loanId) {
      currentLoanId = loanId;
      fetch(`view_loan.php?id=${loanId}`)
        .then(res => res.json())
        .then(data => {
          if (data.error) {
            alert(data.error);
            return;
          }

          document.getElementById('modal-full-name').value = data.full_name || '';
          document.getElementById('modal-account-number').value = data.account_number || '';
          document.getElementById('modal-loan-id').value = data.id || '';
          document.getElementById('modal-contact-number').value = data.contact_number || '';
          document.getElementById('modal-email').value = data.email || '';
          document.getElementById('modal-job').value = data.job || '';
          document.getElementById('modal-monthly-salary').value = '₱' + parseFloat(data.monthly_salary || 0).toLocaleString(undefined, {
            minimumFractionDigits: 2
          });

          const appliedDate = data.created_at ? new Date(data.created_at) : null;
          document.getElementById('modal-date-applied').value =
            appliedDate ? appliedDate.toLocaleDateString('en-US', {
              year: 'numeric',
              month: 'long',
              day: 'numeric'
            }) : 'N/A';

          document.getElementById('modal-loan-type').value = data.loan_type || '';
          document.getElementById('modal-loan-term').value = data.loan_terms || '';
          document.getElementById('modal-loan-amount').value = '₱' + parseFloat(data.loan_amount || 0).toLocaleString(undefined, {
            minimumFractionDigits: 2
          });
          document.getElementById('modal-purpose').value = data.purpose || '';

          document.getElementById('modal-monthly-payment').value = '₱' + parseFloat(data.monthly_payment || 0).toLocaleString(undefined, {
            minimumFractionDigits: 2
          });
          document.getElementById('modal-total-payable').value = '₱' + (parseFloat(data.loan_amount || 0) * 1.20).toLocaleString(undefined, {
            minimumFractionDigits: 2
          });

          const dueDate = data.due_date ? new Date(data.due_date) : null;
          document.getElementById('modal-due-date').value =
            dueDate ? dueDate.toLocaleDateString('en-US', {
              year: 'numeric',
              month: 'long',
              day: 'numeric'
            }) : 'N/A';

          document.getElementById('modal-status').value = data.status || '';
          //document.getElementById('modal-remarks').value = data.remarks || '';

          // Store document paths for viewing
          currentValidId = data.file_url || '';
          currentProofIncome = data.proof_of_income || '';
          currentCoeDocument = data.coe_document || '';

          // Enable/disable view buttons based on document availability
          const validIdBtn = document.getElementById('view-valid-id-btn');
          const proofIncomeBtn = document.getElementById('view-proof-income-btn');
          const coeBtn = document.getElementById('view-coe-btn');

          validIdBtn.disabled = !currentValidId;
          proofIncomeBtn.disabled = !currentProofIncome;
          coeBtn.disabled = !currentCoeDocument;

          document.getElementById('statusModal').style.display = 'flex';
          document.getElementById('statusModal').classList.add('show');
        })
        .catch(err => {
          console.error(err);
          alert('Failed to load loan details.');
        });
    }

    function confirmAndApproveLoan() {
      if (!currentLoanId) return;
      const remarks = prompt('Enter approval remarks (optional):', '');
      if (remarks === null) return;
      if (confirm('Approve this loan?')) {
        updateLoanStatus(currentLoanId, 'Approved', remarks);
      }
    }

    function confirmAndRejectLoan() {
      if (!currentLoanId) return;
      const remarks = prompt('Enter rejection remarks (required):', '');
      if (remarks === null || !remarks.trim()) {
        alert('Remarks are required for rejection.');
        return;
      }
      if (confirm('Reject this loan?')) {
        updateLoanStatus(currentLoanId, 'Rejected', remarks);
      }
    }

    function closeApplicationModal() {
      const modal = document.getElementById('statusModal');
      modal.classList.remove('show');
      setTimeout(() => modal.style.display = 'none', 300);
    }

    function openRemarksModal() {
      document.getElementById('statusModal').classList.remove('show');
      setTimeout(() => {
        document.getElementById('statusModal').style.display = 'none';
        document.getElementById('remarksModal').style.display = 'flex';
        document.getElementById('remarksModal').classList.add('show');
      }, 300);
    }

    function closeRemarksModal() {
      document.getElementById('remarksModal').classList.remove('show');
      setTimeout(() => {
        document.getElementById('remarksModal').style.display = 'none';
        document.getElementById('statusModal').style.display = 'flex';
        document.getElementById('statusModal').classList.add('show');
      }, 300);
    }

    function submitRemarks() {
      const remarks = document.getElementById('remarksText').value.trim();
      if (!remarks) {
        alert('Please enter remarks');
        return;
      }
      if (!currentLoanId) return;

      fetch('update_loan_remarks.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            loan_id: currentLoanId,
            remarks: remarks
          })
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            alert('Remarks added!');
            document.getElementById('modal-remarks').value = remarks;
            document.getElementById('remarksText').value = '';
            closeRemarksModal();
          } else {
            alert('Failed: ' + (data.error || 'Unknown error'));
          }
        });
    }

    function updateLoanStatus(loanId, status, remarks = '') {
      fetch('upload_loan_status.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            loan_id: loanId,
            status: status,
            remarks: remarks
          })
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            alert(`Loan ${status.toLowerCase()} successfully!`);
            const row = document.querySelector(`tr[data-loan-id="${loanId}"]`);
            if (row) row.remove(); // Remove from list
            document.getElementById('modal-status').value = status;
            if (remarks) document.getElementById('modal-remarks').value = remarks;
            // Do NOT close modal — but row is gone
          } else {
            alert('Update failed: ' + (data.error || 'Unknown error'));
          }
        });
    }

    window.onclick = function(e) {
      if (e.target.id === 'statusModal') closeApplicationModal();
      if (e.target.id === 'remarksModal') closeRemarksModal();
    }
  </script>
</body>

</html>