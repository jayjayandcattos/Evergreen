<?php
/**
 * PHP Error Log Viewer
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>PHP Error Log Viewer</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #333; }
        .error-box { background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success-box { background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; margin: 10px 0; border-radius: 4px; }
        .info-box { background: #d1ecf1; border: 1px solid #bee5eb; padding: 10px; margin: 10px 0; border-radius: 4px; }
        pre { white-space: pre-wrap; word-wrap: break-word; }
        .refresh { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>PHP Error Log Viewer</h1>
    <button class="refresh" onclick="location.reload()">Refresh</button>
    
    <?php
    $logFiles = [
        'C:/xampp/php/logs/php_error_log',
        'C:/xampp/apache/logs/error.log',
        ini_get('error_log')
    ];
    
    $foundLog = false;
    
    foreach ($logFiles as $logFile) {
        if ($logFile && file_exists($logFile)) {
            $foundLog = true;
            echo "<div class='success-box'><strong>Log file found:</strong> $logFile</div>";
            
            // Get last 50 lines
            $lines = file($logFile);
            $lastLines = array_slice($lines, -50);
            
            echo "<h2>Last 50 Log Entries:</h2>";
            echo "<div class='info-box'><pre>";
            foreach ($lastLines as $line) {
                // Highlight account opening related logs
                if (stripos($line, 'account') !== false || stripos($line, 'open') !== false) {
                    echo "<span style='background: yellow;'>$line</span>";
                } else {
                    echo htmlspecialchars($line);
                }
            }
            echo "</pre></div>";
            break;
        }
    }
    
    if (!$foundLog) {
        echo "<div class='error-box'><strong>No PHP error log files found in these locations:</strong><br>";
        foreach ($logFiles as $logFile) {
            echo "- " . htmlspecialchars($logFile) . "<br>";
        }
        echo "</div>";
        
        echo "<div class='info-box'>";
        echo "<strong>PHP Error Log Configuration:</strong><br>";
        echo "error_log setting: " . ini_get('error_log') . "<br>";
        echo "log_errors: " . (ini_get('log_errors') ? 'Enabled' : 'Disabled') . "<br>";
        echo "display_errors: " . (ini_get('display_errors') ? 'Enabled' : 'Disabled') . "<br>";
        echo "</div>";
    }
    ?>
    
    <hr>
    <p><em>Last refreshed: <?php echo date('Y-m-d H:i:s'); ?></em></p>
</body>
</html>
