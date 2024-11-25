<?php
// Save as view_logs.php in your htdocs folder

function displayLog($logPath) {
    echo "<h3>Contents of: " . htmlspecialchars($logPath) . "</h3>";
    if(file_exists($logPath)) {
        echo "<pre>";
        echo htmlspecialchars(file_get_contents($logPath));
        echo "</pre>";
    } else {
        echo "Log file does not exist";
    }
    echo "<hr>";
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Log Viewer</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        pre { background: #f4f4f4; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h2>XAMPP Logs</h2>
    <?php
    // Display various log files
    displayLog("C:/xampp/php/logs/php_error_log");
   
    ?>
</body>
</html>