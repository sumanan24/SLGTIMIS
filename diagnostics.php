<?php
/**
 * Diagnostic Script for Bus Season Request Issues
 * Access this file directly to check system configuration
 * DELETE THIS FILE AFTER DIAGNOSIS FOR SECURITY
 */

// Security: Only allow access from localhost or specific IPs
$allowedIPs = ['127.0.0.1', '::1']; // Add your server IP if needed
$clientIP = $_SERVER['REMOTE_ADDR'] ?? '';

// Uncomment to restrict access
// if (!in_array($clientIP, $allowedIPs) && !in_array($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '', $allowedIPs)) {
//     die('Access denied');
// }

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>System Diagnostics</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        h1 { color: #333; border-bottom: 2px solid #333; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .info { color: #17a2b8; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>System Diagnostics - Bus Season Request</h1>
        
        <?php
        $issues = [];
        $warnings = [];
        
        // 1. PHP Version
        echo "<h2>1. PHP Configuration</h2>";
        echo "<table>";
        echo "<tr><th>Setting</th><th>Value</th><th>Status</th></tr>";
        
        $phpVersion = phpversion();
        echo "<tr><td>PHP Version</td><td>{$phpVersion}</td><td>";
        if (version_compare($phpVersion, '7.4.0', '>=')) {
            echo "<span class='success'>✓ OK</span>";
        } else {
            echo "<span class='error'>✗ PHP 7.4+ required</span>";
            $issues[] = "PHP version {$phpVersion} is too old";
        }
        echo "</td></tr>";
        
        // Check required extensions
        $requiredExtensions = ['mysqli', 'pdo', 'json', 'mbstring', 'session'];
        foreach ($requiredExtensions as $ext) {
            $loaded = extension_loaded($ext);
            echo "<tr><td>Extension: {$ext}</td><td>" . ($loaded ? 'Loaded' : 'Not loaded') . "</td><td>";
            if ($loaded) {
                echo "<span class='success'>✓ OK</span>";
            } else {
                echo "<span class='error'>✗ Missing</span>";
                $issues[] = "PHP extension '{$ext}' is not loaded";
            }
            echo "</td></tr>";
        }
        
        // Memory and limits
        $memoryLimit = ini_get('memory_limit');
        $maxExecutionTime = ini_get('max_execution_time');
        $postMaxSize = ini_get('post_max_size');
        $uploadMaxFilesize = ini_get('upload_max_filesize');
        
        echo "<tr><td>Memory Limit</td><td>{$memoryLimit}</td><td><span class='info'>Info</span></td></tr>";
        echo "<tr><td>Max Execution Time</td><td>{$maxExecutionTime}s</td><td><span class='info'>Info</span></td></tr>";
        echo "<tr><td>POST Max Size</td><td>{$postMaxSize}</td><td><span class='info'>Info</span></td></tr>";
        echo "<tr><td>Upload Max Filesize</td><td>{$uploadMaxFilesize}</td><td><span class='info'>Info</span></td></tr>";
        
        echo "</table>";
        
        // 2. Database Connection
        echo "<h2>2. Database Connection</h2>";
        try {
            define('BASE_PATH', __DIR__);
            require_once __DIR__ . '/config/database.php';
            require_once __DIR__ . '/core/Database.php';
            
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            if ($conn && !$conn->connect_error) {
                echo "<p class='success'>✓ Database connection successful</p>";
                
                // Check if table exists
                $tableCheck = $conn->query("SHOW TABLES LIKE 'season_requests'");
                if ($tableCheck && $tableCheck->num_rows > 0) {
                    echo "<p class='success'>✓ Table 'season_requests' exists</p>";
                    
                    // Check table structure
                    $columns = $conn->query("SHOW COLUMNS FROM `season_requests`");
                    if ($columns) {
                        echo "<h3>Table Structure:</h3>";
                        echo "<table>";
                        echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
                        while ($col = $columns->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>{$col['Field']}</td>";
                            echo "<td>{$col['Type']}</td>";
                            echo "<td>{$col['Null']}</td>";
                            echo "<td>{$col['Key']}</td>";
                            echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
                            echo "</tr>";
                        }
                        echo "</table>";
                    }
                } else {
                    echo "<p class='error'>✗ Table 'season_requests' does not exist</p>";
                    $issues[] = "Table 'season_requests' is missing";
                }
                
                // Test INSERT (without actually inserting)
                $testSql = "INSERT INTO `season_requests` 
                            (`student_id`, `department_id`, `season_year`, `season_name`, `depot_name`, 
                             `route_from`, `route_to`, `change_point`, `distance_km`, `status`, `notes`) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)";
                $testStmt = $conn->prepare($testSql);
                if ($testStmt) {
                    echo "<p class='success'>✓ SQL prepare test successful</p>";
                    $testStmt->close();
                } else {
                    echo "<p class='error'>✗ SQL prepare test failed: " . $conn->error . "</p>";
                    $issues[] = "SQL prepare failed: " . $conn->error;
                }
                
            } else {
                echo "<p class='error'>✗ Database connection failed</p>";
                if ($conn) {
                    echo "<p class='error'>Error: " . $conn->connect_error . "</p>";
                    $issues[] = "Database connection error: " . $conn->connect_error;
                } else {
                    echo "<p class='error'>Connection object is null</p>";
                    $issues[] = "Database connection object is null";
                }
            }
        } catch (Exception $e) {
            echo "<p class='error'>✗ Database test failed: " . $e->getMessage() . "</p>";
            $issues[] = "Database exception: " . $e->getMessage();
        }
        
        // 3. File Permissions
        echo "<h2>3. File Permissions</h2>";
        $errorLogPath = __DIR__ . '/error.log';
        $errorLogWritable = is_writable($errorLogPath) || is_writable(__DIR__);
        echo "<p>Error log path: {$errorLogPath}</p>";
        echo "<p>Error log writable: " . ($errorLogWritable ? "<span class='success'>✓ Yes</span>" : "<span class='error'>✗ No</span>") . "</p>";
        if (!$errorLogWritable) {
            $warnings[] = "Error log file may not be writable";
        }
        
        // 4. Recent Error Log Entries
        echo "<h2>4. Recent Error Log Entries (Last 20 lines)</h2>";
        if (file_exists($errorLogPath) && is_readable($errorLogPath)) {
            $lines = file($errorLogPath);
            $recentLines = array_slice($lines, -20);
            echo "<pre>" . htmlspecialchars(implode('', $recentLines)) . "</pre>";
        } else {
            echo "<p class='warning'>⚠ Error log file not readable or doesn't exist</p>";
        }
        
        // 5. Server Information
        echo "<h2>5. Server Information</h2>";
        echo "<table>";
        echo "<tr><th>Setting</th><th>Value</th></tr>";
        echo "<tr><td>Server Software</td><td>" . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</td></tr>";
        echo "<tr><td>PHP SAPI</td><td>" . php_sapi_name() . "</td></tr>";
        echo "<tr><td>Document Root</td><td>" . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "</td></tr>";
        echo "<tr><td>Script Path</td><td>" . __FILE__ . "</td></tr>";
        echo "<tr><td>Base Path</td><td>" . __DIR__ . "</td></tr>";
        echo "</table>";
        
        // Summary
        echo "<h2>Summary</h2>";
        if (empty($issues) && empty($warnings)) {
            echo "<p class='success'>✓ All checks passed! No issues found.</p>";
        } else {
            if (!empty($issues)) {
                echo "<p class='error'>Issues Found:</p>";
                echo "<ul>";
                foreach ($issues as $issue) {
                    echo "<li class='error'>{$issue}</li>";
                }
                echo "</ul>";
            }
            if (!empty($warnings)) {
                echo "<p class='warning'>Warnings:</p>";
                echo "<ul>";
                foreach ($warnings as $warning) {
                    echo "<li class='warning'>{$warning}</li>";
                }
                echo "</ul>";
            }
        }
        ?>
        
        <hr>
        <p><strong>Note:</strong> Delete this file after diagnosis for security purposes.</p>
    </div>
</body>
</html>

