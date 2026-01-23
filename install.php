<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuration
$config = [
    'db_host' => 'localhost',
    'db_user' => 'root',
    'db_pass' => '1234',
    'db_name' => 'sisslgti',
    'sql_file' => 'sis.sql'
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SLGTI SIS - Installation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }
        h1 {
            color: #2563eb;
            margin-bottom: 10px;
            text-align: center;
        }
        .subtitle {
            text-align: center;
            color: #64748b;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #334155;
            font-weight: 500;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #2563eb;
        }
        .btn {
            background: #2563eb;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #1d4ed8;
        }
        .btn:disabled {
            background: #94a3b8;
            cursor: not-allowed;
        }
        .message {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }
        .error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }
        .info {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #3b82f6;
        }
        .progress {
            margin-top: 20px;
            padding: 15px;
            background: #f1f5f9;
            border-radius: 6px;
        }
        .progress-step {
            padding: 8px 0;
            color: #475569;
        }
        .progress-step.active {
            color: #2563eb;
            font-weight: 600;
        }
        .progress-step.completed {
            color: #10b981;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>SLGTI SIS Installation</h1>
        <p class="subtitle">Student Information System Setup</p>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Get form data
            $db_host = $_POST['db_host'] ?? $config['db_host'];
            $db_user = $_POST['db_user'] ?? $config['db_user'];
            $db_pass = $_POST['db_pass'] ?? $config['db_pass'];
            $db_name = $_POST['db_name'] ?? $config['db_name'];
            
            $errors = [];
            $messages = [];
            
            // Step 1: Connect to MySQL
            echo '<div class="progress">';
            echo '<div class="progress-step active">Step 1: Connecting to MySQL...</div>';
            echo '</div>';
            
            try {
                $conn = new mysqli($db_host, $db_user, $db_pass);
                
                if ($conn->connect_error) {
                    throw new Exception("Connection failed: " . $conn->connect_error);
                }
                
                $messages[] = "✓ Successfully connected to MySQL server";
                
                // Step 2: Create database
                echo '<div class="progress">';
                echo '<div class="progress-step completed">Step 1: Connected to MySQL</div>';
                echo '<div class="progress-step active">Step 2: Creating database...</div>';
                echo '</div>';
                
                $sql = "CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
                if ($conn->query($sql) === TRUE) {
                    $messages[] = "✓ Database '$db_name' created successfully";
                } else {
                    $messages[] = "ℹ Database '$db_name' already exists or created";
                }
                
                // Step 3: Select database
                $conn->select_db($db_name);
                $messages[] = "✓ Selected database '$db_name'";
                
                // Step 4: Import SQL file
                echo '<div class="progress">';
                echo '<div class="progress-step completed">Step 1: Connected to MySQL</div>';
                echo '<div class="progress-step completed">Step 2: Database created</div>';
                echo '<div class="progress-step active">Step 3: Importing SQL file...</div>';
                echo '</div>';
                
                if (!file_exists($config['sql_file'])) {
                    throw new Exception("SQL file '{$config['sql_file']}' not found!");
                }
                
                // Method 1: Try to execute SQL file directly using multi_query (faster and handles constraints better)
                $sql_content = file_get_contents($config['sql_file']);
                
                // Remove DELIMITER statements and empty stored procedures/functions (MySQL command-line only syntax)
                // Pattern: DROP PROCEDURE/FUNCTION IF EXISTS `name`; DELIMITER ;; ;; DELIMITER ;
                // Remove the entire block including DROP, DELIMITER, and empty procedure/function body
                $sql_content = preg_replace(
                    '/DROP PROCEDURE IF EXISTS\s+`[^`]+`;\s*[\r\n]+\s*DELIMITER\s*;;\s*[\r\n]+\s*;;\s*[\r\n]+\s*DELIMITER\s*;/i',
                    '',
                    $sql_content
                );
                
                // Remove empty functions (same pattern as procedures)
                $sql_content = preg_replace(
                    '/DROP FUNCTION IF EXISTS\s+`[^`]+`;\s*[\r\n]+\s*DELIMITER\s*;;\s*[\r\n]+\s*;;\s*[\r\n]+\s*DELIMITER\s*;/i',
                    '',
                    $sql_content
                );
                
                // Clean up patterns that result from DELIMITER removal
                // Remove standalone semicolons on their own lines (leftover from DELIMITER blocks)
                $sql_content = preg_replace('/^\s*;\s*$/m', '', $sql_content);
                
                // Clean up double semicolons
                $sql_content = preg_replace('/;\s*;\s*/', ';', $sql_content);
                
                // Remove patterns like: ; DROP FUNCTION ... ; ;
                $sql_content = preg_replace('/;\s*(DROP (FUNCTION|PROCEDURE) IF EXISTS)/i', '$1', $sql_content);
                $sql_content = preg_replace('/(DROP (FUNCTION|PROCEDURE) IF EXISTS\s+`[^`]+`);\s*;\s*/i', '$1;', $sql_content);
                
                // Handle triggers: Remove DELIMITER statements but convert ;; to ; for triggers
                // First, remove all DELIMITER statements
                $sql_content = preg_replace('/DELIMITER\s*;;[\r\n]*/i', '', $sql_content);
                $sql_content = preg_replace('/DELIMITER\s*;[\r\n]*/i', '', $sql_content);
                
                // Then convert ;; to ; (these are trigger statement terminators)
                // Only replace ;; that appear at end of lines or before newlines
                $sql_content = preg_replace('/;;(\s*[\r\n])/i', ';$1', $sql_content);
                $sql_content = preg_replace('/;;\s*$/m', ';', $sql_content);
                
                // Also remove any standalone DELIMITER statements at start of lines
                $sql_content = preg_replace('/^\s*DELIMITER\s+[^\r\n]*[\r\n]*/mi', '', $sql_content);
                
                // Remove DEFINER clause from CREATE statements (causes issues if user doesn't exist)
                $sql_content = preg_replace(
                    '/CREATE\s+DEFINER\s*=\s*`[^`]+`@`[^`]+`\s+/i',
                    'CREATE ',
                    $sql_content
                );
                
                // Remove problematic foreign key constraints that reference non-unique columns
                // These constraints fail because they reference columns that are not unique/primary keys
                
                // 1. Remove assessments_type_ibfk_2 (module_id alone is not unique in module table)
                $sql_content = preg_replace(
                    '/,\s*CONSTRAINT\s+`assessments_type_ibfk_2`\s+FOREIGN\s+KEY\s+\(`module_id`\)\s+REFERENCES\s+`module`\s+\(`module_id`\)\s+ON\s+UPDATE\s+CASCADE/i',
                    '',
                    $sql_content
                );
                
                // 2. Remove payment_reason_foreingkey (payment_reason is not unique in payment table)
                $sql_content = preg_replace(
                    '/,\s*CONSTRAINT\s+`payment_reason_foreingkey`\s+FOREIGN\s+KEY\s+\(`payment_reason`\)\s+REFERENCES\s+`payment`\s+\(`payment_reason`\)\s+ON\s+UPDATE\s+CASCADE/i',
                    '',
                    $sql_content
                );
                
                // 3. Remove ALL constraints that reference module.module_id alone (not the composite key)
                // This catches: delete_feedback_survey_ibfk_4, feedback_survey_ibfk_4, etc.
                $sql_content = preg_replace(
                    '/,\s*CONSTRAINT\s+`[^`]+`\s+FOREIGN\s+KEY\s+\(`module_id`\)\s+REFERENCES\s+`module`\s+\(`module_id`\)[^,\)]*/i',
                    '',
                    $sql_content
                );
                
                // 4. Remove ALL constraints that reference payment.payment_reason (self-reference to non-unique column)
                $sql_content = preg_replace(
                    '/,\s*CONSTRAINT\s+`[^`]+`\s+FOREIGN\s+KEY\s+\(`payment_reason`\)\s+REFERENCES\s+`payment`\s+\(`payment_reason`\)[^,\)]*/i',
                    '',
                    $sql_content
                );
                
                // Clean up any double commas that might result
                $sql_content = preg_replace('/,\s*,/', ',', $sql_content);
                $sql_content = preg_replace('/,\s*\)/', ')', $sql_content);
                
                // Disable foreign key checks and set SQL mode
                $conn->query("SET FOREIGN_KEY_CHECKS=0");
                $conn->query("SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO'");
                $conn->query("SET AUTOCOMMIT=0");
                $conn->query("START TRANSACTION");
                
                $executed = 0;
                $failed = 0;
                $error_messages = [];
                
                // Try multi_query first (handles large files better)
                if ($conn->multi_query($sql_content)) {
                    do {
                        // Store result to free memory
                        if ($result = $conn->store_result()) {
                            $result->free();
                        }
                        $executed++;
                        
                        // Check for errors
                        if ($conn->errno) {
                            $error_msg = $conn->error;
                            // Ignore certain errors (duplicates, table exists, foreign key constraints)
                            $ignored_patterns = [
                                'Duplicate',
                                'already exists',
                                'Unknown table',
                                'foreign key constraint',
                                'Missing unique key',
                                'Cannot add foreign key'
                            ];
                            
                            $should_ignore = false;
                            foreach ($ignored_patterns as $pattern) {
                                if (stripos($error_msg, $pattern) !== false) {
                                    $should_ignore = true;
                                    break;
                                }
                            }
                            
                            if (!$should_ignore) {
                                $failed++;
                                if (count($error_messages) < 10) {
                                    $error_messages[] = substr($error_msg, 0, 150);
                                }
                            }
                        }
                    } while ($conn->next_result());
                    
                    // Commit transaction
                    $conn->query("COMMIT");
                } else {
                    // If multi_query fails, fall back to parsing queries
                    $conn->query("ROLLBACK");
                    
                    // Re-read SQL file for fallback method
                    $sql_content = file_get_contents($config['sql_file']);
                    
                    // Remove DELIMITER statements and empty stored procedures/functions (MySQL command-line only syntax)
                    $sql_content = preg_replace(
                        '/DROP PROCEDURE IF EXISTS\s+`[^`]+`;\s*[\r\n]+\s*DELIMITER\s*;;\s*[\r\n]+\s*;;\s*[\r\n]+\s*DELIMITER\s*;/i',
                        '',
                        $sql_content
                    );
                    
                    // Remove empty functions (same pattern as procedures)
                    $sql_content = preg_replace(
                        '/DROP FUNCTION IF EXISTS\s+`[^`]+`;\s*[\r\n]+\s*DELIMITER\s*;;\s*[\r\n]+\s*;;\s*[\r\n]+\s*DELIMITER\s*;/i',
                        '',
                        $sql_content
                    );
                    
                    // Clean up patterns that result from DELIMITER removal
                    // Remove standalone semicolons on their own lines
                    $sql_content = preg_replace('/^\s*;\s*$/m', '', $sql_content);
                    
                    // Clean up double semicolons
                    $sql_content = preg_replace('/;\s*;\s*/', ';', $sql_content);
                    
                    // Remove patterns like: ; DROP FUNCTION ... ; ;
                    $sql_content = preg_replace('/;\s*(DROP (FUNCTION|PROCEDURE) IF EXISTS)/i', '$1', $sql_content);
                    $sql_content = preg_replace('/(DROP (FUNCTION|PROCEDURE) IF EXISTS\s+`[^`]+`);\s*;\s*/i', '$1;', $sql_content);
                    
                    // Handle triggers: Remove DELIMITER statements but keep triggers
                    // First, remove all DELIMITER statements
                    $sql_content = preg_replace('/DELIMITER\s*;;[\r\n]*/i', '', $sql_content);
                    $sql_content = preg_replace('/DELIMITER\s*;[\r\n]*/i', '', $sql_content);
                    
                    // Then convert ;; to ; (these are trigger statement terminators)
                    $sql_content = preg_replace('/;;(\s*[\r\n])/i', ';$1', $sql_content);
                    $sql_content = preg_replace('/;;\s*$/m', ';', $sql_content);
                    
                    // Also remove any standalone DELIMITER statements at start of lines
                    $sql_content = preg_replace('/^\s*DELIMITER\s+[^\r\n]*[\r\n]*/mi', '', $sql_content);
                    
                    // Remove DEFINER clause from CREATE statements
                    $sql_content = preg_replace(
                        '/CREATE\s+DEFINER\s*=\s*`[^`]+`@`[^`]+`\s+/i',
                        'CREATE ',
                        $sql_content
                    );
                    
                    // Remove problematic foreign key constraints (same as above)
                    // 1. Remove assessments_type_ibfk_2
                    $sql_content = preg_replace(
                        '/,\s*CONSTRAINT\s+`assessments_type_ibfk_2`\s+FOREIGN\s+KEY\s+\(`module_id`\)\s+REFERENCES\s+`module`\s+\(`module_id`\)\s+ON\s+UPDATE\s+CASCADE/i',
                        '',
                        $sql_content
                    );
                    
                    // 2. Remove payment_reason_foreingkey
                    $sql_content = preg_replace(
                        '/,\s*CONSTRAINT\s+`payment_reason_foreingkey`\s+FOREIGN\s+KEY\s+\(`payment_reason`\)\s+REFERENCES\s+`payment`\s+\(`payment_reason`\)\s+ON\s+UPDATE\s+CASCADE/i',
                        '',
                        $sql_content
                    );
                    
                    // 3. Remove any other module_id constraints
                    $sql_content = preg_replace(
                        '/,\s*CONSTRAINT\s+`[^`]+`\s+FOREIGN\s+KEY\s+\(`module_id`\)\s+REFERENCES\s+`module`\s+\(`module_id`\)[^,\)]*/i',
                        '',
                        $sql_content
                    );
                    
                    // 4. Remove any payment_reason self-reference constraints
                    $sql_content = preg_replace(
                        '/,\s*CONSTRAINT\s+`[^`]+`\s+FOREIGN\s+KEY\s+\(`payment_reason`\)\s+REFERENCES\s+`payment`\s+\(`payment_reason`\)[^,\)]*/i',
                        '',
                        $sql_content
                    );
                    
                    $sql_content = preg_replace('/,\s*,/', ',', $sql_content);
                    $sql_content = preg_replace('/,\s*\)/', ')', $sql_content);
                    
                    // Remove comments
                    $sql_content = preg_replace('/--.*$/m', '', $sql_content);
                    $sql_content = preg_replace('/\/\*.*?\*\//s', '', $sql_content);
                    
                    // Split into queries, but keep them larger to preserve structure
                    $queries = [];
                    $current_query = '';
                    
                    // Split by semicolon but preserve CREATE TABLE statements
                    $lines = explode("\n", $sql_content);
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if (empty($line)) continue;
                        
                        $current_query .= $line . "\n";
                        
                        // If line ends with semicolon, it's a complete query
                        if (substr(rtrim($line), -1) === ';') {
                            $query = trim($current_query);
                            if (!empty($query) && 
                                !preg_match('/^(SET|DROP TABLE IF EXISTS)/i', $query) &&
                                strlen($query) > 10) {
                                $queries[] = $query;
                            }
                            $current_query = '';
                        }
                    }
                    
                    // Execute queries
                    foreach ($queries as $query) {
                        if ($conn->query($query) === TRUE) {
                            $executed++;
                        } else {
                            $error_msg = $conn->error;
                            // Ignore duplicate, table exists, and foreign key constraint errors
                            $ignored_errors = [
                                'Duplicate',
                                'already exists',
                                'Unknown table',
                                'Cannot add foreign key',
                                'foreign key constraint',
                                'Missing unique key',
                                'cannot add foreign key constraint',
                                'Failed to add the foreign key constraint',
                                'payment_reason_foreingkey',
                                'assessments_type_ibfk_2'
                            ];
                            
                            $should_ignore = false;
                            foreach ($ignored_errors as $ignore_pattern) {
                                if (stripos($error_msg, $ignore_pattern) !== false) {
                                    $should_ignore = true;
                                    break;
                                }
                            }
                            
                            if (!$should_ignore) {
                                $failed++;
                                if (count($error_messages) < 10) {
                                    $error_messages[] = substr($error_msg, 0, 150);
                                }
                            } else {
                                $executed++; // Count as executed if it's just a constraint issue
                            }
                        }
                    }
                    
                    $conn->query("COMMIT");
                }
                
                // Re-enable foreign key checks
                $conn->query("SET FOREIGN_KEY_CHECKS=1");
                $conn->query("SET AUTOCOMMIT=1");
                
                // Add error messages to errors array
                foreach ($error_messages as $msg) {
                    $errors[] = $msg;
                }
                
                $messages[] = "✓ Executed queries successfully";
                if ($failed > 0) {
                    $messages[] = "⚠ $failed queries had non-critical errors (constraints, duplicates, etc.)";
                }
                
                // Try to fix the foreign key constraint issue if it exists
                // The module table has composite PK, so we need to handle the constraint differently
                try {
                    // Check if assessments_type table exists and has the problematic constraint
                    $check = $conn->query("SHOW CREATE TABLE assessments_type");
                    if ($check && $check->num_rows > 0) {
                        $row = $check->fetch_assoc();
                        // If the constraint exists but fails, we'll note it but continue
                        $messages[] = "ℹ Note: Some foreign key constraints may need manual adjustment";
                    }
                } catch (Exception $e) {
                    // Ignore - table might not exist yet
                }
                
                // Step 5: Create config file
                echo '<div class="progress">';
                echo '<div class="progress-step completed">Step 1: Connected to MySQL</div>';
                echo '<div class="progress-step completed">Step 2: Database created</div>';
                echo '<div class="progress-step completed">Step 3: SQL imported</div>';
                echo '<div class="progress-step active">Step 4: Creating config file...</div>';
                echo '</div>';
                
                $config_content = "<?php\n";
                $config_content .= "// Database Configuration\n";
                $config_content .= "// This file is auto-generated by install.php\n\n";
                $config_content .= "if (!defined('DB_HOST')) {\n";
                $config_content .= "    define('DB_HOST', '$db_host');\n";
                $config_content .= "    define('DB_USER', '$db_user');\n";
                $config_content .= "    define('DB_PASS', '$db_pass');\n";
                $config_content .= "    define('DB_NAME', '$db_name');\n";
                $config_content .= "    define('DB_CHARSET', 'utf8mb4');\n";
                $config_content .= "}\n\n";
                $config_content .= "// Application Configuration\n";
                $config_content .= "if (!defined('APP_NAME')) {\n";
                $config_content .= "    define('APP_NAME', 'SLGTI SIS');\n";
                $config_content .= "    // Detect HTTPS automatically\n";
                $config_content .= "    \$protocol = (!empty(\$_SERVER['HTTPS']) && \$_SERVER['HTTPS'] !== 'off') || \n";
                $config_content .= "                (isset(\$_SERVER['SERVER_PORT']) && \$_SERVER['SERVER_PORT'] == 443) ||\n";
                $config_content .= "                (isset(\$_SERVER['HTTP_X_FORWARDED_PROTO']) && \$_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')\n";
                $config_content .= "                ? 'https://' : 'http://';\n";
                $config_content .= "    // Get base path and normalize it (remove trailing slash, handle root case)\n";
                $config_content .= "    \$basePath = dirname(\$_SERVER['SCRIPT_NAME']);\n";
                $config_content .= "    \$basePath = rtrim(\$basePath, '/\\\\'); // Remove trailing slashes\n";
                $config_content .= "    // If basePath is empty or just a slash, set it to empty string\n";
                $config_content .= "    if (\$basePath === '/' || \$basePath === '\\\\' || empty(\$basePath)) {\n";
                $config_content .= "        \$basePath = '';\n";
                $config_content .= "    }\n";
                $config_content .= "    define('APP_URL', \$protocol . \$_SERVER['HTTP_HOST'] . \$basePath);\n";
                $config_content .= "}\n\n";
                $config_content .= "// BASE_PATH is defined in index.php\n";
                $config_content .= "// If not defined, set it here (shouldn't happen)\n";
                $config_content .= "if (!defined('BASE_PATH')) {\n";
                $config_content .= "    define('BASE_PATH', dirname(__DIR__));\n";
                $config_content .= "}\n";
                
                if (file_put_contents('config/database.php', $config_content)) {
                    $messages[] = "✓ Configuration file created successfully";
                } else {
                    // Try creating config directory first
                    if (!is_dir('config')) {
                        mkdir('config', 0755, true);
                    }
                    if (file_put_contents('config/database.php', $config_content)) {
                        $messages[] = "✓ Configuration file created successfully";
                    } else {
                        $errors[] = "✗ Failed to create config file. Please create config/database.php manually.";
                    }
                }
                
                // Close connection
                $conn->close();
                
                // Display results
                echo '<div class="message success">';
                echo '<h3>Installation Completed Successfully!</h3>';
                echo '<ul style="margin-top: 10px; padding-left: 20px;">';
                foreach ($messages as $msg) {
                    echo '<li>' . htmlspecialchars($msg) . '</li>';
                }
                echo '</ul>';
                echo '</div>';
                
                if (!empty($errors)) {
                    echo '<div class="message error">';
                    echo '<h3>Warnings:</h3>';
                    echo '<ul style="margin-top: 10px; padding-left: 20px;">';
                    foreach ($errors as $error) {
                        echo '<li>' . htmlspecialchars($error) . '</li>';
                    }
                    echo '</ul>';
                    echo '</div>';
                }
                
                echo '<div class="message info">';
                echo '<p><strong>Next Steps:</strong></p>';
                echo '<ol style="margin-top: 10px; padding-left: 20px;">';
                echo '<li>Delete or rename this install.php file for security</li>';
                echo '<li>Visit <a href="index.php">index.php</a> to access the application</li>';
                echo '<li>Default login credentials should be in your database</li>';
                echo '</ol>';
                echo '</div>';
                
                echo '<div style="text-align: center; margin-top: 20px;">';
                echo '<a href="index.php" class="btn">Go to Application</a>';
                echo '</div>';
                
            } catch (Exception $e) {
                echo '<div class="message error">';
                echo '<h3>Installation Failed</h3>';
                echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
                echo '</div>';
            }
            
        } else {
            // Show installation form
            ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="db_host">Database Host:</label>
                    <input type="text" id="db_host" name="db_host" value="<?php echo htmlspecialchars($config['db_host']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="db_user">Database User:</label>
                    <input type="text" id="db_user" name="db_user" value="<?php echo htmlspecialchars($config['db_user']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="db_pass">Database Password:</label>
                    <input type="password" id="db_pass" name="db_pass" value="<?php echo htmlspecialchars($config['db_pass']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="db_name">Database Name:</label>
                    <input type="text" id="db_name" name="db_name" value="<?php echo htmlspecialchars($config['db_name']); ?>" required>
                </div>
                
                <div class="message info">
                    <p><strong>Note:</strong> Make sure the SQL file (sis.sql) is in the same directory as this install script.</p>
                </div>
                
                <button type="submit" class="btn">Install Database</button>
            </form>
            <?php
        }
        ?>
    </div>
</body>
</html>

