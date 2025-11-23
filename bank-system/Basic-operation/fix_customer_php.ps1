# PowerShell Script to Fix Customer.php
# Run this in PowerShell: .\fix_customer_php.ps1

$filePath = "operations/app/models/Customer.php"

Write-Host "Reading Customer.php file..." -ForegroundColor Yellow

# Read the entire file
$content = Get-Content $filePath -Raw

# Define the corrupted method pattern (approximate)
$corruptedPattern = '(?s)public function getCustomerByEmailOrAccountNumber\(\$identifier\).*?return \$this->db->single\(\);.*?\}'

# Define the fixed method
$fixedMethod = @'
public function getCustomerByEmailOrAccountNumber($identifier) {
    $this->db->query("
            SELECT
                c.customer_id,
                c.first_name,
                c.last_name,
                c.email,
                c.password_hash,
                a.account_number
            FROM
                bank_customers c
            LEFT JOIN
                customer_accounts a ON c.customer_id = a.customer_id
            WHERE
                c.email = :emailIdentifier OR a.account_number = :accountIdentifier
            LIMIT 1;
        ");

    if(filter_var($identifier, FILTER_VALIDATE_EMAIL)){
        $email = $identifier;
        $account_number = null;
    } else {
        $email = null;
        $account_number = $identifier;
    }

    $this->db->bind(':emailIdentifier', $email);
    $this->db->bind(':accountIdentifier', $account_number);
    return $this->db->single();
  }
'@

# Replace the corrupted method
if ($content -match $corruptedPattern) {
    Write-Host "Found corrupted method. Replacing..." -ForegroundColor Green
    $content = $content -replace $corruptedPattern, $fixedMethod
    
    # Save the fixed content
    Set-Content -Path $filePath -Value $content -NoNewline
    
    Write-Host "✅ Customer.php has been fixed!" -ForegroundColor Green
    Write-Host "You can now try logging in again." -ForegroundColor Cyan
} else {
    Write-Host "❌ Could not find the corrupted method pattern." -ForegroundColor Red
    Write-Host "Please fix manually using URGENT_FIX_CUSTOMER_PHP.md" -ForegroundColor Yellow
}
