<?php
require_once 'config/database.php';

$db = getDBConnection();

echo "<h2>bank_customers with customer_id = 6:</h2>";
$stmt = $db->query("SELECT * FROM bank_customers WHERE customer_id = 6");
$customer = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($customer);
echo "</pre>";

echo "<h2>All bank_customers:</h2>";
$stmt = $db->query("SELECT customer_id, email, application_id, is_verified, is_active FROM bank_customers ORDER BY customer_id");
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($customers);
echo "</pre>";

echo "<h2>All customer_accounts:</h2>";
$stmt = $db->query("SELECT account_id, customer_id, account_number FROM customer_accounts ORDER BY account_id");
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($accounts);
echo "</pre>";

echo "<h2>Fixing the data - linking customer_id 6 to an application:</h2>";

// Check if there's an application we can link to
$stmt = $db->query("SELECT application_id, first_name, last_name, email FROM account_applications WHERE application_id <= 10 ORDER BY application_id");
$apps = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<h3>Available applications:</h3><pre>";
print_r($apps);
echo "</pre>";

// Let's create a proper link - assuming customer_id 6 should be linked to application_id 1 (John Doe)
// or we need to create a new application for this customer
echo "<h3>Solution:</h3>";
echo "<p>We need to either:</p>";
echo "<ol>";
echo "<li>Link customer_id 6 to an existing application_id</li>";
echo "<li>Create a new application for this customer</li>";
echo "</ol>";

echo "<form method='POST'>";
echo "<h3>Option 1: Link to existing application</h3>";
echo "Application ID: <input type='number' name='app_id' value='1'> ";
echo "<button type='submit' name='link'>Link customer_id 6 to this application</button>";
echo "</form>";

echo "<form method='POST'>";
echo "<h3>Option 2: Create new application</h3>";
echo "First Name: <input name='first_name' value='Customer'><br>";
echo "Last Name: <input name='last_name' value='Six'><br>";
echo "Email: <input name='email' value='customer6@example.com'><br>";
echo "Phone: <input name='phone' value='09123456789'><br>";
echo "<button type='submit' name='create'>Create application and link</button>";
echo "</form>";

if (isset($_POST['link'])) {
    $appId = (int)$_POST['app_id'];
    $stmt = $db->prepare("UPDATE bank_customers SET application_id = :app_id WHERE customer_id = 6");
    $stmt->execute(['app_id' => $appId]);
    echo "<p style='color: green;'>✓ Linked customer_id 6 to application_id $appId</p>";
    echo "<p><a href='check-tables.php'>Test the account lookup again</a></p>";
}

if (isset($_POST['create'])) {
    // Create new application
    $stmt = $db->prepare("
        INSERT INTO account_applications (first_name, last_name, email, phone_number, application_status, created_at)
        VALUES (:first_name, :last_name, :email, :phone, 'Approved', NOW())
    ");
    $stmt->execute([
        'first_name' => $_POST['first_name'],
        'last_name' => $_POST['last_name'],
        'email' => $_POST['email'],
        'phone' => $_POST['phone']
    ]);
    
    $newAppId = $db->lastInsertId();
    
    // Link to customer
    $stmt = $db->prepare("UPDATE bank_customers SET application_id = :app_id WHERE customer_id = 6");
    $stmt->execute(['app_id' => $newAppId]);
    
    echo "<p style='color: green;'>✓ Created application_id $newAppId and linked to customer_id 6</p>";
    echo "<p><a href='check-tables.php'>Test the account lookup again</a></p>";
}
?>
