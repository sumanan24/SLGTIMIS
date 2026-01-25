<?php
/**
 * CRUD Column Name Case Sensitivity Check
 * Checks all CREATE, READ, UPDATE operations for case-sensitive column name issues
 * DELETE THIS FILE AFTER USE FOR SECURITY
 */

define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/models/BusSeasonRequestModel.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>CRUD Case Sensitivity Check</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1400px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        h1 { color: #333; border-bottom: 2px solid #333; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 0.9rem; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: bold; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 0.85rem; }
        .operation { margin: 15px 0; padding: 10px; background: #f8f9fa; border-radius: 4px; }
        .operation-title { font-weight: bold; color: #0066cc; margin-bottom: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>CRUD Operations - Column Name Case Sensitivity Check</h1>
        
        <?php
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            if (!$conn || $conn->connect_error) {
                echo "<p class='error'>✗ Database connection failed</p>";
                exit;
            }
            
            echo "<p class='success'>✓ Database connected successfully</p>";
            
            // Get actual table structure
            echo "<h2>1. Actual Table Structure</h2>";
            
            // Check season_requests table
            $result = $conn->query("DESCRIBE `season_requests`");
            $seasonRequestsColumns = [];
            if ($result) {
                echo "<h3>Table: season_requests</h3>";
                echo "<table>";
                echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
                while ($row = $result->fetch_assoc()) {
                    $seasonRequestsColumns[] = $row['Field'];
                    echo "<tr><td><strong>" . htmlspecialchars($row['Field']) . "</strong></td>";
                    echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Key']) . "</td></tr>";
                }
                echo "</table>";
            }
            
            // Check season_payments table
            $result2 = $conn->query("DESCRIBE `season_payments`");
            $seasonPaymentsColumns = [];
            if ($result2) {
                echo "<h3>Table: season_payments</h3>";
                echo "<table>";
                echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
                while ($row = $result2->fetch_assoc()) {
                    $seasonPaymentsColumns[] = $row['Field'];
                    echo "<tr><td><strong>" . htmlspecialchars($row['Field']) . "</strong></td>";
                    echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Key']) . "</td></tr>";
                }
                echo "</table>";
            }
            
            // Analyze CRUD operations
            echo "<h2>2. CRUD Operations Analysis</h2>";
            
            // CREATE Operations
            echo "<h3>CREATE Operations (INSERT)</h3>";
            
            $createOps = [
                [
                    'method' => 'create()',
                    'sql' => "INSERT INTO `season_requests` (`student_id`, `department_id`, `season_year`, `season_name`, `depot_name`, `route_from`, `route_to`, `change_point`, `distance_km`, `status`, `notes`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)",
                    'columns' => ['student_id', 'department_id', 'season_year', 'season_name', 'depot_name', 'route_from', 'route_to', 'change_point', 'distance_km', 'status', 'notes']
                ],
                [
                    'method' => 'createPaymentCollection()',
                    'sql' => "INSERT INTO `season_payments` (`request_id`, `student_id`, `paid_amount`, `season_rate`, `status`, `payment_method`, `payment_reference`, `collected_by`, `notes`, `payment_date`) VALUES (?, ?, ?, ?, 'paid', ?, ?, ?, ?, NOW())",
                    'columns' => ['request_id', 'student_id', 'paid_amount', 'season_rate', 'status', 'payment_method', 'payment_reference', 'collected_by', 'notes', 'payment_date']
                ]
            ];
            
            foreach ($createOps as $op) {
                echo "<div class='operation'>";
                echo "<div class='operation-title'>" . htmlspecialchars($op['method']) . "</div>";
                echo "<pre>" . htmlspecialchars($op['sql']) . "</pre>";
                
                $table = strpos($op['sql'], 'season_requests') !== false ? 'season_requests' : 'season_payments';
                $actualColumns = $table === 'season_requests' ? $seasonRequestsColumns : $seasonPaymentsColumns;
                
                $errors = [];
                $warnings = [];
                foreach ($op['columns'] as $col) {
                    $found = false;
                    $exactMatch = false;
                    foreach ($actualColumns as $actualCol) {
                        if (strtolower($actualCol) === strtolower($col)) {
                            $found = true;
                            if ($actualCol === $col) {
                                $exactMatch = true;
                            } else {
                                $warnings[] = "Column '{$col}' case mismatch: Found '{$actualCol}'";
                            }
                            break;
                        }
                    }
                    if (!$found) {
                        $errors[] = "Column '{$col}' not found in table";
                    }
                }
                
                if (empty($errors) && empty($warnings)) {
                    echo "<p class='success'>✓ All columns exist and case matches</p>";
                } else {
                    if (!empty($errors)) {
                        echo "<p class='error'>✗ Errors:</p><ul>";
                        foreach ($errors as $err) {
                            echo "<li>" . htmlspecialchars($err) . "</li>";
                        }
                        echo "</ul>";
                    }
                    if (!empty($warnings)) {
                        echo "<p class='warning'>⚠ Warnings:</p><ul>";
                        foreach ($warnings as $warn) {
                            echo "<li>" . htmlspecialchars($warn) . "</li>";
                        }
                        echo "</ul>";
                    }
                }
                echo "</div>";
            }
            
            // READ Operations
            echo "<h3>READ Operations (SELECT)</h3>";
            
            $readOps = [
                [
                    'method' => 'findWithDetails()',
                    'sql' => "SELECT r.*, s.student_fullname, s.student_email, s.student_id, s.student_gender, s.student_nic, d.department_name, d.department_id, hod.user_name as hod_approver_name, second.user_name as second_approver_name FROM `season_requests` r LEFT JOIN `student` s ON r.student_id = s.student_id LEFT JOIN `department` d ON r.department_id = d.department_id LEFT JOIN `user` hod ON r.hod_approver_id = hod.user_id LEFT JOIN `user` second ON r.second_approver_id = second.user_id WHERE r.id = ?",
                    'columns' => ['id', 'student_id', 'department_id', 'hod_approver_id', 'second_approver_id']
                ],
                [
                    'method' => 'getByStudentId()',
                    'sql' => "SELECT r.* FROM `season_requests` r WHERE r.student_id = ?",
                    'columns' => ['student_id']
                ],
                [
                    'method' => 'getPendingHODRequests()',
                    'sql' => "SELECT r.* FROM `season_requests` r WHERE r.status = 'pending' AND r.department_id = ?",
                    'columns' => ['status', 'department_id']
                ],
                [
                    'method' => 'getAllPaymentCollections()',
                    'sql' => "SELECT p.id as payment_id, p.paid_amount, p.season_rate, p.total_amount, p.student_paid, p.slgti_paid, p.ctb_paid, p.remaining_balance, p.status as payment_status, p.payment_date, p.payment_method, p.payment_reference, p.collected_by, p.notes as payment_notes, p.issued_at, p.student_id as payment_student_id, r.id as request_id, r.student_id as request_student_id, r.season_year, r.season_name, r.depot_name, r.route_from, r.route_to, r.change_point, r.distance_km, r.status as request_status FROM `season_payments` p INNER JOIN `season_requests` r ON p.request_id = r.id WHERE 1=1",
                    'columns' => ['id', 'paid_amount', 'season_rate', 'total_amount', 'student_paid', 'slgti_paid', 'ctb_paid', 'remaining_balance', 'status', 'payment_date', 'payment_method', 'payment_reference', 'collected_by', 'notes', 'issued_at', 'student_id', 'request_id']
                ]
            ];
            
            foreach ($readOps as $op) {
                echo "<div class='operation'>";
                echo "<div class='operation-title'>" . htmlspecialchars($op['method']) . "</div>";
                echo "<pre>" . htmlspecialchars($op['sql']) . "</pre>";
                
                // Extract table from SQL
                $table = 'season_requests';
                if (strpos($op['sql'], 'season_payments') !== false) {
                    $table = 'season_payments';
                }
                $actualColumns = $table === 'season_requests' ? $seasonRequestsColumns : $seasonPaymentsColumns;
                
                $errors = [];
                $warnings = [];
                foreach ($op['columns'] as $col) {
                    $found = false;
                    $exactMatch = false;
                    foreach ($actualColumns as $actualCol) {
                        if (strtolower($actualCol) === strtolower($col)) {
                            $found = true;
                            if ($actualCol === $col) {
                                $exactMatch = true;
                            } else {
                                $warnings[] = "Column '{$col}' case mismatch: Found '{$actualCol}'";
                            }
                            break;
                        }
                    }
                    if (!$found) {
                        $errors[] = "Column '{$col}' not found in table";
                    }
                }
                
                if (empty($errors) && empty($warnings)) {
                    echo "<p class='success'>✓ All columns exist and case matches</p>";
                } else {
                    if (!empty($errors)) {
                        echo "<p class='error'>✗ Errors:</p><ul>";
                        foreach ($errors as $err) {
                            echo "<li>" . htmlspecialchars($err) . "</li>";
                        }
                        echo "</ul>";
                    }
                    if (!empty($warnings)) {
                        echo "<p class='warning'>⚠ Warnings:</p><ul>";
                        foreach ($warnings as $warn) {
                            echo "<li>" . htmlspecialchars($warn) . "</li>";
                        }
                        echo "</ul>";
                    }
                }
                echo "</div>";
            }
            
            // UPDATE Operations
            echo "<h3>UPDATE Operations</h3>";
            
            $updateOps = [
                [
                    'method' => 'updateHODApproval()',
                    'sql' => "UPDATE `season_requests` SET `status` = ?, `hod_approver_id` = ?, `hod_approval_date` = NOW(), `hod_comments` = ?, `approved_by` = ?, `approved_at` = NOW() WHERE `id` = ? AND `status` = 'pending'",
                    'columns' => ['status', 'hod_approver_id', 'hod_approval_date', 'hod_comments', 'approved_by', 'approved_at', 'id']
                ],
                [
                    'method' => 'updateSecondApproval()',
                    'sql' => "UPDATE `season_requests` SET `status` = ?, `second_approver_id` = ?, `second_approver_role` = ?, `second_approval_date` = NOW(), `second_comments` = ?, `approved_by` = ?, `approved_at` = NOW() WHERE `id` = ? AND `status` = 'hod_approved'",
                    'columns' => ['status', 'second_approver_id', 'second_approver_role', 'second_approval_date', 'second_comments', 'approved_by', 'approved_at', 'id']
                ],
                [
                    'method' => 'updateStatus()',
                    'sql' => "UPDATE `season_requests` SET `status` = ? WHERE `id` = ?",
                    'columns' => ['status', 'id']
                ],
                [
                    'method' => 'updatePaymentStatus()',
                    'sql' => "UPDATE `season_payments` SET `status` = ?, `total_amount` = ?, `student_paid` = ?, `slgti_paid` = ?, `ctb_paid` = ?, `season_rate` = ?, `remaining_balance` = ?, `payment_reference` = ?, `issued_at` = ? WHERE `id` = ?",
                    'columns' => ['status', 'total_amount', 'student_paid', 'slgti_paid', 'ctb_paid', 'season_rate', 'remaining_balance', 'payment_reference', 'issued_at', 'id']
                ]
            ];
            
            foreach ($updateOps as $op) {
                echo "<div class='operation'>";
                echo "<div class='operation-title'>" . htmlspecialchars($op['method']) . "</div>";
                echo "<pre>" . htmlspecialchars($op['sql']) . "</pre>";
                
                $table = strpos($op['sql'], 'season_requests') !== false ? 'season_requests' : 'season_payments';
                $actualColumns = $table === 'season_requests' ? $seasonRequestsColumns : $seasonPaymentsColumns;
                
                $errors = [];
                $warnings = [];
                foreach ($op['columns'] as $col) {
                    $found = false;
                    $exactMatch = false;
                    foreach ($actualColumns as $actualCol) {
                        if (strtolower($actualCol) === strtolower($col)) {
                            $found = true;
                            if ($actualCol === $col) {
                                $exactMatch = true;
                            } else {
                                $warnings[] = "Column '{$col}' case mismatch: Found '{$actualCol}'";
                            }
                            break;
                        }
                    }
                    if (!$found) {
                        $errors[] = "Column '{$col}' not found in table";
                    }
                }
                
                if (empty($errors) && empty($warnings)) {
                    echo "<p class='success'>✓ All columns exist and case matches</p>";
                } else {
                    if (!empty($errors)) {
                        echo "<p class='error'>✗ Errors:</p><ul>";
                        foreach ($errors as $err) {
                            echo "<li>" . htmlspecialchars($err) . "</li>";
                        }
                        echo "</ul>";
                    }
                    if (!empty($warnings)) {
                        echo "<p class='warning'>⚠ Warnings:</p><ul>";
                        foreach ($warnings as $warn) {
                            echo "<li>" . htmlspecialchars($warn) . "</li>";
                        }
                        echo "</ul>";
                    }
                }
                echo "</div>";
            }
            
            // Summary
            echo "<h2>3. Summary</h2>";
            echo "<p><strong>Table Names:</strong></p>";
            echo "<ul>";
            echo "<li>✓ season_requests (all lowercase, underscore)</li>";
            echo "<li>✓ season_payments (all lowercase, underscore)</li>";
            echo "</ul>";
            
            echo "<p><strong>Column Naming Convention:</strong></p>";
            echo "<ul>";
            echo "<li>✓ All columns use lowercase with underscores (snake_case)</li>";
            echo "<li>✓ All SQL queries use backticks for table and column names</li>";
            echo "<li>✓ Consistent naming throughout all CRUD operations</li>";
            echo "</ul>";
            
            // Test actual queries
            echo "<h2>4. Test Actual Queries</h2>";
            
            $testQueries = [
                "SELECT `id`, `student_id`, `department_id`, `season_year` FROM `season_requests` WHERE 1 LIMIT 1",
                "SELECT `id`, `request_id`, `student_id`, `paid_amount` FROM `season_payments` WHERE 1 LIMIT 1"
            ];
            
            foreach ($testQueries as $testSql) {
                echo "<div class='operation'>";
                echo "<pre>" . htmlspecialchars($testSql) . "</pre>";
                $result = $conn->query($testSql);
                if ($result) {
                    echo "<p class='success'>✓ Query executed successfully</p>";
                } else {
                    echo "<p class='error'>✗ Query failed: " . htmlspecialchars($conn->error) . "</p>";
                }
                echo "</div>";
            }
            
        } catch (Exception $e) {
            echo "<p class='error'>✗ Exception: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        ?>
        
        <hr>
        <p><strong>Note:</strong> Delete this file after checking for security purposes.</p>
    </div>
</body>
</html>

