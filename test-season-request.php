<?php
/**
 * Test Script for Bus Season Request (Nginx Compatibility)
 * Access this file to test form submission on nginx server
 * DELETE THIS FILE AFTER TESTING FOR SECURITY
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
    <title>Bus Season Request Test - Nginx</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        h1 { color: #333; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #17a2b8; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: bold; }
        .test-form { margin-top: 20px; padding: 20px; background: #f8f9fa; border-radius: 4px; }
        .test-form input, .test-form button { padding: 8px; margin: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Bus Season Request - Nginx Test</h1>
        
        <?php
        define('BASE_PATH', __DIR__);
        require_once __DIR__ . '/config/database.php';
        require_once __DIR__ . '/core/Database.php';
        require_once __DIR__ . '/core/SeasonRequestHelper.php';
        
        // Start session for testing
        session_start();
        
        // Test 1: Server Information
        echo "<h2>1. Server Information</h2>";
        echo "<table>";
        echo "<tr><th>Setting</th><th>Value</th></tr>";
        echo "<tr><td>Server Software</td><td>" . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</td></tr>";
        echo "<tr><td>PHP SAPI</td><td>" . php_sapi_name() . "</td></tr>";
        echo "<tr><td>PHP Version</td><td>" . phpversion() . "</td></tr>";
        echo "<tr><td>Request Method</td><td>" . ($_SERVER['REQUEST_METHOD'] ?? 'Unknown') . "</td></tr>";
        echo "<tr><td>Content-Type</td><td>" . ($_SERVER['CONTENT_TYPE'] ?? 'not set') . "</td></tr>";
        echo "<tr><td>Content-Length</td><td>" . ($_SERVER['CONTENT_LENGTH'] ?? 'not set') . "</td></tr>";
        echo "</table>";
        
        // Test 2: POST Data Handling
        echo "<h2>2. POST Data Test</h2>";
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            echo "<p class='success'>✓ POST request received</p>";
            echo "<p><strong>POST Data:</strong></p>";
            echo "<pre>" . print_r($_POST, true) . "</pre>";
            
            if (empty($_POST)) {
                echo "<p class='error'>✗ POST array is empty!</p>";
                echo "<p class='info'>This indicates an nginx configuration issue. Check:</p>";
                echo "<ul>";
                echo "<li>client_max_body_size in nginx.conf</li>";
                echo "<li>fastcgi_pass configuration</li>";
                echo "<li>PHP-FPM settings</li>";
                echo "</ul>";
                
                // Try to read raw input
                $rawInput = file_get_contents('php://input');
                if (!empty($rawInput)) {
                    echo "<p class='info'>Raw input received: " . htmlspecialchars(substr($rawInput, 0, 200)) . "</p>";
                } else {
                    echo "<p class='error'>Raw input is also empty!</p>";
                }
            } else {
                echo "<p class='success'>✓ POST data received successfully</p>";
            }
        } else {
            echo "<p class='info'>No POST request. Use the test form below.</p>";
        }
        
        // Test 3: Database Connection
        echo "<h2>3. Database Connection</h2>";
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            if ($conn && !$conn->connect_error) {
                echo "<p class='success'>✓ Database connection successful</p>";
                
                // Test table access
                $tableCheck = $conn->query("SHOW TABLES LIKE 'season_requests'");
                if ($tableCheck && $tableCheck->num_rows > 0) {
                    echo "<p class='success'>✓ Table 'season_requests' exists</p>";
                } else {
                    echo "<p class='error'>✗ Table 'season_requests' does not exist</p>";
                }
            } else {
                echo "<p class='error'>✗ Database connection failed</p>";
                if ($conn) {
                    echo "<p class='error'>Error: " . $conn->connect_error . "</p>";
                }
            }
        } catch (Exception $e) {
            echo "<p class='error'>✗ Database test failed: " . $e->getMessage() . "</p>";
        }
        
        // Test 4: CSRF Token
        echo "<h2>4. CSRF Token Test</h2>";
        $csrfToken = SeasonRequestHelper::generateCSRFToken();
        echo "<p class='success'>✓ CSRF token generated: " . substr($csrfToken, 0, 20) . "...</p>";
        
        if (isset($_POST['csrf_token'])) {
            $isValid = SeasonRequestHelper::verifyCSRFToken($_POST['csrf_token']);
            if ($isValid) {
                echo "<p class='success'>✓ CSRF token verification passed</p>";
            } else {
                echo "<p class='error'>✗ CSRF token verification failed</p>";
            }
        }
        
        // Test 5: PHP Configuration
        echo "<h2>5. PHP Configuration</h2>";
        echo "<table>";
        echo "<tr><th>Setting</th><th>Value</th></tr>";
        echo "<tr><td>post_max_size</td><td>" . ini_get('post_max_size') . "</td></tr>";
        echo "<tr><td>upload_max_filesize</td><td>" . ini_get('upload_max_filesize') . "</td></tr>";
        echo "<tr><td>max_execution_time</td><td>" . ini_get('max_execution_time') . "s</td></tr>";
        echo "<tr><td>memory_limit</td><td>" . ini_get('memory_limit') . "</td></tr>";
        echo "<tr><td>max_input_vars</td><td>" . ini_get('max_input_vars') . "</td></tr>";
        echo "</table>";
        
        // Test 6: Session
        echo "<h2>6. Session Test</h2>";
        if (session_status() === PHP_SESSION_ACTIVE) {
            echo "<p class='success'>✓ Session is active</p>";
            echo "<p>Session ID: " . session_id() . "</p>";
        } else {
            echo "<p class='error'>✗ Session is not active</p>";
        }
        ?>
        
        <!-- Test Form -->
        <div class="test-form">
            <h3>Test Form Submission</h3>
            <form method="POST" action="">
                <?php $csrfToken = SeasonRequestHelper::generateCSRFToken(); ?>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="form_submitted" value="1">
                
                <div>
                    <label>Route From:</label><br>
                    <input type="text" name="route_from" value="Test From" required>
                </div>
                
                <div>
                    <label>Route To:</label><br>
                    <input type="text" name="route_to" value="Test To" required>
                </div>
                
                <div>
                    <label>Distance (KM):</label><br>
                    <input type="number" name="distance_km" value="10.5" step="0.1" required>
                </div>
                
                <div>
                    <label>Change Point:</label><br>
                    <input type="text" name="change_point" value="Test Point">
                </div>
                
                <button type="submit">Test Submit</button>
            </form>
        </div>
        
        <hr>
        <p><strong>Note:</strong> Delete this file after testing for security purposes.</p>
    </div>
</body>
</html>

