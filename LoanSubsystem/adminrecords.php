<?php 
// Set your timezone (example: Philippines)
date_default_timezone_set('Asia/Manila');
$currentDate = date("Y/m/d");
$currentTime = date("h:i:s A");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Evergreen | Loan Records</title>
  <link rel="stylesheet" href="adminstyle.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
  <nav class="navbar">
 <div class="logo">
  <img src="images/banklogo.png" alt="Bank Logo" class="logo-img">
  <span class="logo-text">EVERGREEN</span>
</div>
  <ul class="nav-links">
      <li><a href="adminindex.php">Dashboard</a></li>
      <li><a href="adminapplications.php">Loan Applications</a></li>
      <li><a href="adminrecords.php" class="active">Records</a></li>
    </ul>
     <!-- Date & Time (placed before Admin) -->
    <div class="datetime">
        <span id="currentDate"><?php echo date("Y/m/d"); ?></span>
        <span id="currentTime"><?php echo date("h:i:s A"); ?></span>
    </div>

    <div class="admin">
      <span>Admin</span>
      <div class="icon">👤</div>
    </div>
  </nav>

  <main>
    <h1>Loan Records</h1>
    <table>
      <thead>
        <tr>
          <th>Loan ID</th>
          <th>Client Name</th>
          <th>Loan Type</th>
          <th>Amount</th>
          <th>Status</th>
          <th>Loan Officer ID</th>
          <th>Time Approve</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>L-1023</td>
          <td>Juan Dela Cruz</td>
          <td>Auto Loan</td>
          <td>₱150,000</td>
          <td class="pending">PENDING</td>
          <td>LO-0123</td>
          <td>12/12/2025 11:30 AM</td>
        </tr>
      </tbody>
    </table>
  </main>
  <script>
  function updateTime() {
    const now = new Date();
    const options = { timeZone: 'Asia/Manila', hour12: true };
    document.getElementById('currentDate').textContent =
      now.toLocaleDateString('en-PH', { timeZone: 'Asia/Manila' });
    document.getElementById('currentTime').textContent =
      now.toLocaleTimeString('en-PH', options);
  }
  setInterval(updateTime, 1000);
  updateTime();
</script>

  <script src="adminscript.js"></script>
</body>
</html>
