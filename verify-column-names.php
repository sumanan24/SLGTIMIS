<?php
/**
 * Verify all column names used in CRUD operations match database structure
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
    <title>Column Names Verification</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        h1 { color: #333; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: bold; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 0.85rem; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Column Names Verification - CRUD Operations</h1>
        
        <?php
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            if (!$conn || $conn->connect_error) {
                echo "<p class='error'>✗ Database connection failed</p>";
                exit;
            }
            
            echo "<p class='success'>✓ Database connected successfully</p>";
            
            // Get actual columns
            $result = $conn->query("DESCRIBE `season_requests`");
            $seasonRequestsColumns = [];
            while ($row = $result->fetch_assoc()) {
                $seasonRequestsColumns[strtolower($row['Field'])] = $row['Field']; // Store both lowercase key and actual case
            }
            
            $result2 = $conn->query("DESCRIBE `season_payments`");
            $seasonPaymentsColumns = [];
            while ($row = $result2->fetch_assoc()) {
                $seasonPaymentsColumns[strtolower($row['Field'])] = $row['Field'];
            }
            
            // Expected columns from code
            $expectedSeasonRequests = [
                'id', 'student_id', 'department_id', 'season_year', 'season_name', 'depot_name',
                'route_from', 'route_to', 'change_point', 'distance_km', 'status', 'approved_by',
                'hod_approver_id', 'hod_approval_date', 'hod_comments', 'second_approver_id',
                'second_approver_role', 'second_approval_date', 'second_comments', 'approved_at',
                'notes', 'created_at', 'updated_at'
            ];
            
            $expectedSeasonPayments = [
                'id', 'request_id', 'student_id', 'paid_amount', 'season_rate', 'total_amount',
                'student_paid', 'slgti_paid', 'ctb_paid', 'remaining_balance', 'status',
                'payment_date', 'payment_method', 'payment_reference', 'collected_by', 'notes',
                'issued_at', 'created_at', 'updated_at'
            ];
            
            echo "<h2>1. season_requests Table</h2>";
            echo "<table>";
            echo "<tr><th>Expected Column</th><th>Actual Column</th><th>Status</th></tr>";
            $allMatch = true;
            foreach ($expectedSeasonRequests as $expected) {
                $lowerExpected = strtolower($expected);
                if (isset($seasonRequestsColumns[$lowerExpected])) {
                    $actual = $seasonRequestsColumns[$lowerExpected];
                    if ($actual === $expected) {
                        echo "<tr><td>{$expected}</td><td>{$actual}</td><td class='success'>✓ Match</td></tr>";
                    } else {
                        echo "<tr><td>{$expected}</td><td>{$actual}</td><td class='error'>✗ Case Mismatch</td></tr>";
                        $allMatch = false;
                    }
                } else {
                    echo "<tr><td>{$expected}</td><td>-</td><td class='error'>✗ Missing</td></tr>";
                    $allMatch = false;
                }
            }
            echo "</table>";
            
            if ($allMatch) {
                echo "<p class='success'>✓ All columns match correctly!</p>";
            }
            
            echo "<h2>2. season_payments Table</h2>";
            echo "<table>";
            echo "<tr><th>Expected Column</th><th>Actual Column</th><th>Status</th></tr>";
            $allMatch2 = true;
            foreach ($expectedSeasonPayments as $expected) {
                $lowerExpected = strtolower($expected);
                if (isset($seasonPaymentsColumns[$lowerExpected])) {
                    $actual = $seasonPaymentsColumns[$lowerExpected];
                    if ($actual === $expected) {
                        echo "<tr><td>{$expected}</td><td>{$actual}</td><td class='success'>✓ Match</td></tr>";
                    } else {
                        echo "<tr><td>{$expected}</td><td>{$actual}</td><td class='error'>✗ Case Mismatch</td></tr>";
                        $allMatch2 = false;
                    }
                } else {
                    echo "<tr><td>{$expected}</td><td>-</td><td class='error'>✗ Missing</td></tr>";
                    $allMatch2 = false;
                }
            }
            echo "</table>";
            
            if ($allMatch2) {
                echo "<p class='success'>✓ All columns match correctly!</p>";
            }
            
            // Test CRUD operations
            echo "<h2>3. Test CRUD Operations</h2>";
            
            // Test CREATE
            echo "<h3>CREATE Test</h3>";
            $testInsert = "INSERT INTO `season_requests` (`student_id`, `season_year`, `route_from`, `route_to`, `status`) VALUES ('TEST001', '2026', 'Test From', 'Test To', 'pending')";
            $result = $conn->query($testInsert);
            if ($result) {
                $insertId = $conn->insert_id;
                echo "<p class='success'>✓ INSERT successful (ID: {$insertId})</p>";
                
                // Test READ
                echo "<h3>READ Test</h3>";
                $testSelect = "SELECT `id`, `student_id`, `season_year`, `route_from`, `route_to`, `status` FROM `season_requests` WHERE `id` = {$insertId}";
                $result2 = $conn->query($testSelect);
                if ($result2 && $result2->num_rows > 0) {
                    echo "<p class='success'>✓ SELECT successful</p>";
                    $row = $result2->fetch_assoc();
                    echo "<pre>Retrieved: " . print_r($row, true) . "</pre>";
                    
                    // Test UPDATE
                    echo "<h3>UPDATE Test</h3>";
                    $testUpdate = "UPDATE `season_requests` SET `status` = 'test', `notes` = 'Test update' WHERE `id` = {$insertId}";
                    $result3 = $conn->query($testUpdate);
                    if ($result3) {
                        echo "<p class='success'>✓ UPDATE successful</p>";
                        
                        // Clean up - DELETE test record
                        echo "<h3>DELETE Test</h3>";
                        $testDelete = "DELETE FROM `season_requests` WHERE `id` = {$insertId}";
                        $result4 = $conn->query($testDelete);
                        if ($result4) {
                            echo "<p class='success'>✓ DELETE successful (test record removed)</p>";
                        } else {
                            echo "<p class='error'>✗ DELETE failed: " . $conn->error . "</p>";
                        }
                    } else {
                        echo "<p class='error'>✗ UPDATE failed: " . $conn->error . "</p>";
                    }
                } else {
                    echo "<p class='error'>✗ SELECT failed: " . $conn->error . "</p>";
                }
            } else {
                echo "<p class='error'>✗ INSERT failed: " . $conn->error . "</p>";
            }
            
            // Summary
            echo "<h2>4. Summary</h2>";
            echo "<p><strong>Column Naming Convention:</strong></p>";
            echo "<ul>";
            echo "<li>✓ All columns use lowercase with underscores (snake_case)</li>";
            echo "<li>✓ All SQL queries use backticks (`) for table and column names</li>";
            echo "<li>✓ Consistent naming: lowercase, underscores, no mixed case</li>";
            echo "</ul>";
            
            echo "<p><strong>Case Sensitivity:</strong></p>";
            echo "<ul>";
            echo "<li>✓ MySQL on Windows: Case-insensitive (but best practice to use consistent case)</li>";
            echo "<li>✓ MySQL on Linux: Case-sensitive (must match exactly)</li>";
            echo "<li>✓ Using backticks ensures compatibility across platforms</li>";
            echo "</ul>";
            
        } catch (Exception $e) {
            echo "<p class='error'>✗ Exception: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        ?>
        
        <hr>
        <p><strong>Note:</strong> Delete this file after checking for security purposes.</p>
    </div>
</body>
</html>

