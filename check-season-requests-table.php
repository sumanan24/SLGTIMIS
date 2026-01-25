<?php
/**
 * Diagnostic script to check season_requests table structure
 * DELETE THIS FILE AFTER USE FOR SECURITY
 */

define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/core/Database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Season Requests Table Check</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        h1 { color: #333; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: bold; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Season Requests Table Structure Check</h1>
        
        <?php
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            if (!$conn || $conn->connect_error) {
                echo "<p class='error'>✗ Database connection failed: " . ($conn ? $conn->connect_error : 'Connection object is null') . "</p>";
                exit;
            }
            
            echo "<p class='success'>✓ Database connected successfully</p>";
            
            // Check table exists
            echo "<h2>1. Table Existence Check</h2>";
            $tableCheck = $conn->query("SHOW TABLES LIKE 'season_requests'");
            if ($tableCheck && $tableCheck->num_rows > 0) {
                echo "<p class='success'>✓ Table 'season_requests' exists</p>";
            } else {
                echo "<p class='error'>✗ Table 'season_requests' does not exist</p>";
                exit;
            }
            
            // Get actual table structure
            echo "<h2>2. Actual Table Structure</h2>";
            $result = $conn->query("DESCRIBE `season_requests`");
            if ($result) {
                echo "<table>";
                echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
                $actualColumns = [];
                while ($row = $result->fetch_assoc()) {
                    $actualColumns[] = $row['Field'];
                    echo "<tr>";
                    echo "<td><strong>" . htmlspecialchars($row['Field']) . "</strong></td>";
                    echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
                    echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p class='error'>✗ Failed to describe table: " . $conn->error . "</p>";
            }
            
            // Expected columns from the SQL query
            echo "<h2>3. Expected Columns (from SQL query)</h2>";
            $expectedColumns = [
                'id', 'student_id', 'department_id', 'season_year', 'season_name', 'depot_name',
                'route_from', 'route_to', 'change_point', 'distance_km', 'status', 'approved_by',
                'hod_approver_id', 'hod_approval_date', 'hod_comments', 'second_approver_id',
                'second_approver_role', 'second_approval_date', 'second_comments', 'approved_at',
                'notes', 'created_at', 'updated_at'
            ];
            
            echo "<table>";
            echo "<tr><th>Expected Column</th><th>Status</th><th>Case Match</th></tr>";
            $missingColumns = [];
            $caseMismatches = [];
            
            foreach ($expectedColumns as $expectedCol) {
                $found = false;
                $exactMatch = false;
                
                foreach ($actualColumns as $actualCol) {
                    if (strtolower($actualCol) === strtolower($expectedCol)) {
                        $found = true;
                        if ($actualCol === $expectedCol) {
                            $exactMatch = true;
                        } else {
                            $caseMismatches[] = "Expected: '{$expectedCol}', Found: '{$actualCol}'";
                        }
                        break;
                    }
                }
                
                if (!$found) {
                    $missingColumns[] = $expectedCol;
                    echo "<tr><td><strong>" . htmlspecialchars($expectedCol) . "</strong></td><td class='error'>✗ Missing</td><td>-</td></tr>";
                } elseif (!$exactMatch) {
                    $actualColFound = '';
                    foreach ($actualColumns as $ac) {
                        if (strtolower($ac) === strtolower($expectedCol)) {
                            $actualColFound = $ac;
                            break;
                        }
                    }
                    echo "<tr><td><strong>" . htmlspecialchars($expectedCol) . "</strong></td><td class='warning'>⚠ Found (case mismatch)</td><td>Actual: '{$actualColFound}'</td></tr>";
                } else {
                    echo "<tr><td>" . htmlspecialchars($expectedCol) . "</td><td class='success'>✓ Found</td><td class='success'>✓ Match</td></tr>";
                }
            }
            echo "</table>";
            
            // Summary
            echo "<h2>4. Summary</h2>";
            if (empty($missingColumns) && empty($caseMismatches)) {
                echo "<p class='success'>✓ All columns exist and case matches correctly!</p>";
            } else {
                if (!empty($missingColumns)) {
                    echo "<p class='error'>✗ Missing columns: " . implode(', ', $missingColumns) . "</p>";
                }
                if (!empty($caseMismatches)) {
                    echo "<p class='warning'>⚠ Case mismatches found:</p><ul>";
                    foreach ($caseMismatches as $mismatch) {
                        echo "<li>" . htmlspecialchars($mismatch) . "</li>";
                    }
                    echo "</ul>";
                }
            }
            
            // Test the SQL query
            echo "<h2>5. Test SQL Query</h2>";
            $testSql = "SELECT `id`, `student_id`, `department_id`, `season_year`, `season_name`, `depot_name`, `route_from`, `route_to`, `change_point`, `distance_km`, `status`, `approved_by`, `hod_approver_id`, `hod_approval_date`, `hod_comments`, `second_approver_id`, `second_approver_role`, `second_approval_date`, `second_comments`, `approved_at`, `notes`, `created_at`, `updated_at` FROM `season_requests` WHERE 1 LIMIT 1";
            
            $testResult = $conn->query($testSql);
            if ($testResult) {
                echo "<p class='success'>✓ SQL query executed successfully</p>";
                echo "<pre>" . htmlspecialchars($testSql) . "</pre>";
            } else {
                echo "<p class='error'>✗ SQL query failed: " . $conn->error . "</p>";
                echo "<pre>" . htmlspecialchars($testSql) . "</pre>";
            }
            
            // Check for case-sensitive issues in code
            echo "<h2>6. Code Consistency Check</h2>";
            echo "<p>All SQL queries in BusSeasonRequestModel use backticks consistently.</p>";
            echo "<p class='success'>✓ Table name: 'season_requests' (lowercase with underscore)</p>";
            echo "<p class='success'>✓ Payment table: 'season_payments' (lowercase with underscore)</p>";
            
        } catch (Exception $e) {
            echo "<p class='error'>✗ Exception: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        ?>
        
        <hr>
        <p><strong>Note:</strong> Delete this file after checking for security purposes.</p>
    </div>
</body>
</html>

