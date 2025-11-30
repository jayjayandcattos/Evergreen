<!---adminindex.php (with Report Generation)-->
<?php
session_start();
include 'admin_header.php';

// ✅ Direct database connection
$host = "localhost";
$user = "root";
$pass = "";
$db = "BankingDB";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
  die("DB Error: " . $conn->connect_error);
}

// ✅ FIXED: Removed deleted_at check (column doesn't exist in your DB)
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

$totalLoans = array_sum($counts);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Evergreen | Loan Dashboard</title>
  <link rel="stylesheet" href="adminstyle.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <style>
    .pending { color: #FF9800; font-weight: bold; }
    .approved { color: #4CAF50; font-weight: bold; }
    .active { color: #2e7d32; font-weight: bold; }
    .rejected { color: #f44336; font-weight: bold; }

    .analytics-section {
      margin: 30px 0;
      background: white;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .analytics-header {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 25px;
      color: #003631;
    }

    .analytics-header i { font-size: 28px; }
    .analytics-header h2 { margin: 0; font-size: 24px; }

    .chart-container {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 30px;
      align-items: center;
    }

    .chart-wrapper {
      position: relative;
      height: 350px;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .chart-stats {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 15px;
    }

    .stat-item {
      background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
      padding: 20px;
      border-radius: 10px;
      border-left: 4px solid #003631;
      transition: all 0.3s ease;
    }

    .stat-item:hover {
      transform: translateX(5px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .stat-item h4 {
      margin: 0 0 8px 0;
      color: #666;
      font-size: 14px;
      font-weight: 500;
      text-transform: uppercase;
    }

    .stat-item p {
      margin: 0;
      font-size: 28px;
      font-weight: bold;
      color: #003631;
    }

    .stat-item.active-stat { border-left-color: #2e7d32; }
    .stat-item.active-stat p { color: #2e7d32; }
    .stat-item.approved-stat { border-left-color: #4CAF50; }
    .stat-item.approved-stat p { color: #4CAF50; }
    .stat-item.pending-stat { border-left-color: #FF9800; }
    .stat-item.pending-stat p { color: #FF9800; }
    .stat-item.rejected-stat { border-left-color: #f44336; }
    .stat-item.rejected-stat p { color: #f44336; }

    /* Report Buttons */
    .report-buttons {
      margin: 30px 0;
      padding: 25px;
      background: white;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .report-buttons h3 {
      margin: 0 0 20px 0;
      color: #003631;
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 20px;
    }

    .report-buttons h3 i {
      font-size: 24px;
    }

    .button-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 15px;
    }

    .report-btn {
      padding: 16px 24px;
      border: none;
      border-radius: 10px;
      font-size: 15px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      color: white;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .report-btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 6px 16px rgba(0,0,0,0.2);
    }

    .report-btn:active {
      transform: translateY(-1px);
    }

    .report-btn i {
      font-size: 18px;
    }

    .btn-all {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .btn-all:hover {
      background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
    }

    .btn-active {
      background: linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%);
    }

    .btn-active:hover {
      background: linear-gradient(135deg, #1b5e20 0%, #2e7d32 100%);
    }

    .btn-approved {
      background: linear-gradient(135deg, #4CAF50 0%, #2e7d32 100%);
    }

    .btn-approved:hover {
      background: linear-gradient(135deg, #2e7d32 0%, #4CAF50 100%);
    }

    .btn-pending {
      background: linear-gradient(135deg, #FF9800 0%, #F57C00 100%);
    }

    .btn-pending:hover {
      background: linear-gradient(135deg, #F57C00 0%, #FF9800 100%);
    }

    .btn-rejected {
      background: linear-gradient(135deg, #f44336 0%, #c62828 100%);
    }

    .btn-rejected:hover {
      background: linear-gradient(135deg, #c62828 0%, #f44336 100%);
    }

    @media (max-width: 968px) {
      .chart-container { grid-template-columns: 1fr; }
      .button-grid { grid-template-columns: 1fr; }
    }
  </style>
</head>

<body>
  <main>
    <h1>Loan Dashboard - All Records</h1>

    <div class="cards">
      <div class="card" onclick="filterLoans('All')">
        <div class="card-header">
          <img src="images/allloans.png" alt="All Loans" class="icon-img">
          <p>All Loans</p>
        </div>
        <h3><?= $totalLoans ?></h3>
      </div>
      <div class="card" onclick="filterLoans('Active')">
        <div class="card-header">
          <img src="images/activeloanicon.png" alt="Active Loan" class="icon-img">
          <p>Active Loans</p>
        </div>
        <h3><?= $counts['Active'] ?></h3>
      </div>
      <div class="card" onclick="filterLoans('Approved')">
        <div class="card-header">
          <img src="images/activeloanicon.png" alt="Approved Loan" class="icon-img">
          <p>Approved (Awaiting Claim)</p>
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
    </div>

    <!-- Analytics Section -->
    <div class="analytics-section">
      <div class="analytics-header">
        <i class="fas fa-chart-pie"></i>
        <h2>Loan Portfolio Analytics</h2>
      </div>
      
      <div class="chart-container">
        <div class="chart-wrapper">
          <canvas id="loanPieChart"></canvas>
        </div>
        
        <div class="chart-stats">
          <div class="stat-item active-stat">
            <h4>Active Loans</h4>
            <p><?= $counts['Active'] ?></p>
          </div>
          <div class="stat-item approved-stat">
            <h4>Awaiting Claim</h4>
            <p><?= $counts['Approved'] ?></p>
          </div>
          <div class="stat-item pending-stat">
            <h4>Pending Review</h4>
            <p><?= $counts['Pending'] ?></p>
          </div>
          <div class="stat-item rejected-stat">
            <h4>Rejected</h4>
            <p><?= $counts['Rejected'] ?></p>
          </div>
        </div>
      </div>
    </div>

    <!-- Report Generation Buttons -->
    <div class="report-buttons">
      <h3>
        <i class="fas fa-file-pdf"></i>
        Generate Loan Reports
      </h3>
      <div class="button-grid">
        <button class="report-btn btn-all" onclick="generateReport('all')">
          <i class="fas fa-file-alt"></i>
          All Loans Report
        </button>
        <button class="report-btn btn-active" onclick="generateReport('active')">
          <i class="fas fa-check-circle"></i>
          Active Loans Report
        </button>
        <button class="report-btn btn-approved" onclick="generateReport('approved')">
          <i class="fas fa-thumbs-up"></i>
          Approved Loans Report
        </button>
        <button class="report-btn btn-pending" onclick="generateReport('pending')">
          <i class="fas fa-clock"></i>
          Pending Loans Report
        </button>
        <button class="report-btn btn-rejected" onclick="generateReport('rejected')">
          <i class="fas fa-times-circle"></i>
          Rejected Loans Report
        </button>
      </div>
    </div>

    <h1>All Loan Records</h1>
    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>Loan ID</th>
            <th>Client Name</th>
            <th>Loan Type</th>
            <th>Amount</th>
            <th>Loan Officer ID</th>
            <th>Date</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody id="loansTableBody">
          <?php
          $result = $conn->query("
            SELECT 
              la.id,
              la.full_name,
              la.loan_amount,
              la.created_at,
              la.status,
              COALESCE(lt.name, 'Unknown Type') AS loan_type_display
            FROM loan_applications la
            LEFT JOIN loan_types lt ON la.loan_type_id = lt.id
            ORDER BY la.id DESC
          ");
          
          if ($result && $result->num_rows > 0):
            while ($row = $result->fetch_assoc()):
              $date = date("m/d/Y", strtotime($row['created_at'] ?? 'now'));
              $time = date("h:i A", strtotime($row['created_at'] ?? 'now'));
              $statusClass = strtolower($row['status']);
          ?>
              <tr data-status="<?= htmlspecialchars($row['status']) ?>">
                <td><?= htmlspecialchars($row['id']) ?></td>
                <td><?= htmlspecialchars($row['full_name']) ?></td>
                <td><?= htmlspecialchars($row['loan_type_display']) ?></td>
                <td>₱<?= number_format($row['loan_amount'], 2) ?></td>
                <td><?= htmlspecialchars($_SESSION['loan_officer_id'] ?? 'LO-0123') ?></td>
                <td><?= $date ?> <?= $time ?></td>
                <td class="<?= $statusClass ?>"><?= htmlspecialchars($row['status']) ?></td>
                <td>
                  <button onclick="viewLoanDetails(<?= (int)$row['id'] ?>)">View Details</button>
                </td>
              </tr>
            <?php endwhile;
          else: ?>
            <tr><td colspan="8" style="text-align:center;">No records found</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </main>

  <!-- View Details Modal -->
  <div id="viewLoanModal" class="modal">
    <div class="modal-content">
      <h2>Client Loan Details (View Only)</h2>
      <p><strong>Loan Officer:</strong> <?= htmlspecialchars($_SESSION['loan_officer_id'] ?? 'LO-0123') ?></p>
      <hr>

      <div class="details">
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
          <div id="approval-info" style="display:none;">
            <h4>Approval Information</h4>
            <p><strong>Approved By:</strong> <span id="modal-approved-by"></span></p>
            <p><strong>Approved At:</strong> <span id="modal-approved-at"></span></p>
            <p><strong>Remarks:</strong> <span id="modal-remarks-display"></span></p>
          </div>

          <div id="rejection-info" style="display:none;">
            <h4>Rejection Information</h4>
            <p><strong>Rejected By:</strong> <span id="modal-rejected-by"></span></p>
            <p><strong>Rejected At:</strong> <span id="modal-rejected-at"></span></p>
            <p><strong>Rejection Remarks:</strong> <span id="modal-reject-remarks"></span></p>
          </div>

          <div style="margin-top: 20px;">
            <h3>Uploaded Documents</h3>
            <div class="document-container">
              <button type="button" id="view-valid-id-btn" class="view-doc-btn" onclick="viewDocument('valid_id')">Valid ID</button><br><br>
              <button type="button" id="view-proof-income-btn" class="view-doc-btn" onclick="viewDocument('proof_of_income')">Proof of Income</button><br><br>
              <button type="button" id="view-coe-btn" class="view-doc-btn" onclick="viewDocument('coe_document')">Certificate of Employment</button>
            </div>
          </div>
        </div>

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
            <p><strong>Next Payment Due:</strong> <span id="modal-next-payment"></span></p>
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
    const chartData = {
      active: <?= $counts['Active'] ?>,
      approved: <?= $counts['Approved'] ?>,
      pending: <?= $counts['Pending'] ?>,
      rejected: <?= $counts['Rejected'] ?>
    };

    document.addEventListener('DOMContentLoaded', function() {
      const ctx = document.getElementById('loanPieChart');
      if (ctx) {
        const loanPieChart = new Chart(ctx, {
          type: 'pie',
          data: {
            labels: ['Active Loans', 'Awaiting Claim', 'Pending Review', 'Rejected'],
            datasets: [{
              data: [chartData.active, chartData.approved, chartData.pending, chartData.rejected],
              backgroundColor: ['#2e7d32', '#4CAF50', '#FF9800', '#f44336'],
              borderWidth: 3,
              borderColor: '#ffffff'
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
              legend: {
                position: 'bottom',
                labels: {
                  padding: 20,
                  font: { size: 13, weight: 'bold' },
                  usePointStyle: true,
                  pointStyle: 'circle'
                }
              },
              tooltip: {
                callbacks: {
                  label: function(context) {
                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                    const value = context.parsed;
                    const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : '0.0';
                    return `${context.label}: ${value} (${percentage}%)`;
                  }
                },
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                padding: 12,
                titleFont: { size: 14, weight: 'bold' },
                bodyFont: { size: 13 }
              }
            }
          }
        });
        window.loanPieChart = loanPieChart;
      }
    });

    let currentValidId = '';
    let currentProofIncome = '';
    let currentCoeDocument = '';

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
          document.getElementById('modal-monthly-salary').textContent = parseFloat(data.monthly_salary || 0).toLocaleString(undefined, {minimumFractionDigits: 2});

          document.getElementById('modal-loan-type').textContent = data.loan_type || '';
          document.getElementById('modal-loan-amount').textContent = parseFloat(data.loan_amount || 0).toLocaleString(undefined, {minimumFractionDigits: 2});
          document.getElementById('modal-loan-term').textContent = data.loan_terms || '';
          document.getElementById('modal-purpose').textContent = data.purpose || '';
          document.getElementById('modal-status').textContent = data.status || '';

          const approvalInfo = document.getElementById('approval-info');
          const rejectionInfo = document.getElementById('rejection-info');

          if ((data.status === 'Active' || data.status === 'Approved') && data.approved_by) {
            document.getElementById('modal-approved-by').textContent = data.approved_by;
            document.getElementById('modal-approved-at').textContent = new Date(data.approved_at).toLocaleString();
            document.getElementById('modal-remarks-display').textContent = data.remarks || '—';
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
          document.getElementById('modal-date-applied').textContent = appliedDate ? appliedDate.toLocaleDateString() : 'N/A';

          const dueDate = data.due_date ? new Date(data.due_date) : null;
          document.getElementById('modal-due-date').textContent = dueDate ? dueDate.toLocaleDateString() : 'N/A';

          const nextPayment = data.next_payment_due ? new Date(data.next_payment_due).toLocaleDateString() : 'N/A';
          document.getElementById('modal-next-payment').textContent = nextPayment;

          const total = parseFloat(data.loan_amount || 0) * 1.20;
          document.getElementById('modal-total-payable').textContent = total.toLocaleString(undefined, {minimumFractionDigits: 2});
          document.getElementById('modal-monthly-payment').textContent = parseFloat(data.monthly_payment || 0).toLocaleString(undefined, {minimumFractionDigits: 2});

          currentValidId = data.file_url || '';
          currentProofIncome = data.proof_of_income || '';
          currentCoeDocument = data.coe_document || '';

          document.getElementById('view-valid-id-btn').disabled = !currentValidId;
          document.getElementById('view-proof-income-btn').disabled = !currentProofIncome;
          document.getElementById('view-coe-btn').disabled = !currentCoeDocument;

          document.getElementById('viewLoanModal').style.display = 'flex';
          document.getElementById('viewLoanModal').classList.add('show');
        })
        .catch(err => console.error('Error:', err));
    }

    function closeViewModal() {
      const modal = document.getElementById('viewLoanModal');
      modal.classList.remove('show');
      setTimeout(() => modal.style.display = 'none', 300);
    }

    window.onclick = function(e) {
      if (e.target === document.getElementById('viewLoanModal')) closeViewModal();
    }

    // Generate Report Function
    function generateReport(type) {
      const chartImage = window.loanPieChart ? window.loanPieChart.toBase64Image() : '';
      
      fetch(`generate_report.php?type=${type}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ chartImage: chartImage })
      })
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

    function filterLoans(status) {
      document.querySelectorAll('.card').forEach(card => card.classList.remove('active'));
      event.target.closest('.card').classList.add('active');

      const rows = document.querySelectorAll('#loansTableBody tr');
      rows.forEach(row => {
        if (status === 'All' || row.dataset.status === status) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
      });
    }

    document.addEventListener('DOMContentLoaded', function() {
      const firstCard = document.querySelector('.card');
      if (firstCard) firstCard.classList.add('active');
    });
  </script>
</body>
</html>