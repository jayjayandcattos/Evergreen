<?php
session_start();

// Simple authentication check (you should implement proper authentication)
// For now, just checking if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['email'])) {
    // Redirect to login or show error
    // header("Location: login.php");
    // exit;
}

// Database connection
$host = "localhost";
$user = "root"; 
$pass = ""; 
$db = "BankingDB";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch applications
$sql = "SELECT * FROM account_applications ORDER BY submitted_at DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Applications - Evergreen Bank</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        h1 {
            color: #003631;
            margin-bottom: 30px;
            font-size: 28px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #003631 0%, #1a6b62 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-card h3 {
            font-size: 32px;
            margin-bottom: 5px;
        }

        .stat-card p {
            font-size: 14px;
            opacity: 0.9;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        th {
            background: #f8f9fa;
            color: #003631;
            font-weight: 600;
            position: sticky;
            top: 0;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background: #d4edda;
            color: #155724;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .status-under_review {
            background: #d1ecf1;
            color: #0c5460;
        }

        .account-type {
            font-size: 12px;
            color: #666;
        }

        .cards-list {
            font-size: 12px;
            color: #1a6b62;
            font-weight: 500;
        }

        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            margin-right: 5px;
        }

        .btn-view {
            background: #003631;
            color: white;
        }

        .btn-approve {
            background: #28a745;
            color: white;
        }

        .btn-reject {
            background: #dc3545;
            color: white;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #003631;
            text-decoration: none;
            font-weight: 600;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="viewingpage.php" class="back-link">← Back to Dashboard</a>
        
        <h1>Account Applications</h1>

        <?php
        // Calculate statistics
        $stats = [
            'total' => 0,
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0
        ];

        $result->data_seek(0);
        while ($row = $result->fetch_assoc()) {
            $stats['total']++;
            $stats[$row['application_status']]++;
        }
        ?>

        <div class="stats">
            <div class="stat-card">
                <h3><?php echo $stats['total']; ?></h3>
                <p>Total Applications</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['pending']; ?></h3>
                <p>Pending Review</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['approved']; ?></h3>
                <p>Approved</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['rejected']; ?></h3>
                <p>Rejected</p>
            </div>
        </div>

        <?php if ($result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>App #</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Account Type</th>
                        <th>Cards</th>
                        <th>Services</th>
                        <th>Income</th>
                        <th>Status</th>
                        <th>Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $result->data_seek(0);
                    while ($row = $result->fetch_assoc()): 
                        $accountTypeLabel = [
                            'acct-checking' => 'Checking',
                            'acct-savings' => 'Savings',
                            'acct-both' => 'Checking & Savings'
                        ][$row['account_type']] ?? $row['account_type'];
                    ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['application_number']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['phone_number']); ?></td>
                            <td class="account-type"><?php echo $accountTypeLabel; ?></td>
                            <td class="cards-list">
                                <?php 
                                $cards = $row['selected_cards'] ? explode(',', $row['selected_cards']) : [];
                                echo !empty($cards) ? htmlspecialchars(ucwords(implode(', ', $cards))) : '<em>None</em>'; 
                                ?>
                            </td>
                            <td class="cards-list">
                                <?php 
                                $services = $row['additional_services'] ? explode(',', $row['additional_services']) : [];
                                echo !empty($services) ? htmlspecialchars(implode(', ', $services)) : '<em>None</em>'; 
                                ?>
                            </td>
                            <td>$<?php echo number_format($row['annual_income'], 0); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $row['application_status']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $row['application_status'])); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($row['submitted_at'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-data">
                <p>No applications found.</p>
            </div>
        <?php endif; ?>
    </div>


</body>
</html>

<?php
$conn->close();
?>
