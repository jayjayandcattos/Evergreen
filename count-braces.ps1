$content = Get-Content 'c:\xampp\htdocs\SIABASICOPS\bank-system\Basic-operation\assets\js\employee-transaction.js' -Raw
$open = ([regex]::Matches($content, '\{')).Count
$close = ([regex]::Matches($content, '\}')).Count
Write-Host "Open braces: $open"
Write-Host "Close braces: $close"
Write-Host "Difference: $($open - $close)"
