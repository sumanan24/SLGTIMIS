<?php
/**
 * SLGTI SIS - Bootstrap File
 * Entry point for the application
 */

// Error reporting FIRST (before anything else)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Register error handlers
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        echo "<!DOCTYPE html><html><head><title>Fatal Error</title>";
        echo "<style>body{font-family:Arial,sans-serif;padding:40px;background:#ffffff;color:#000000;}";
        echo ".error-box{background:#ffffff;padding:30px;border-radius:8px;border:2px solid #000000;box-shadow:0 4px 8px rgba(0,0,0,0.2);max-width:800px;margin:0 auto;}";
        echo "h1{color:#000000;margin-top:0;border-bottom:2px solid #000000;padding-bottom:10px;}pre{background:#000000;color:#ffffff;padding:15px;border-radius:4px;overflow:auto;border:1px solid #000000;}</style></head><body>";
        echo "<div class='error-box'>";
        echo "<h1>Fatal Error</h1>";
        echo "<p><strong>Error:</strong> " . htmlspecialchars($error['message']) . "</p>";
        echo "<p><strong>File:</strong> " . htmlspecialchars($error['file']) . "</p>";
        echo "<p><strong>Line:</strong> " . $error['line'] . "</p>";
        echo "</div></body></html>";
    }
});

// Set error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

// Start output buffering to catch any errors
ob_start();

// Define base path FIRST (before any other includes)
define('BASE_PATH', __DIR__);

// Start session with security settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
// Detect HTTPS and set secure cookie flag accordingly
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
           (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ||
           (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
ini_set('session.cookie_secure', $isHttps ? 1 : 0);
@session_start();

// Load configuration
try {
    require_once BASE_PATH . '/config/database.php';
} catch (Exception $e) {
    die("Error loading config: " . $e->getMessage());
}

// Load core classes
try {
    require_once BASE_PATH . '/core/Database.php';
    require_once BASE_PATH . '/core/Model.php';
    require_once BASE_PATH . '/core/View.php';
    require_once BASE_PATH . '/core/Controller.php';
    require_once BASE_PATH . '/core/Router.php';
    
    // Initialize security tables (creates tables if they don't exist)
    require_once BASE_PATH . '/core/init_security_tables.php';
    initSecurityTables();
} catch (Exception $e) {
    die("Error loading core classes: " . $e->getMessage());
}

// Get the URI
$uri = $_SERVER['REQUEST_URI'];
$basePath = dirname($_SERVER['SCRIPT_NAME']);

// Remove base path from URI
if ($basePath !== '/' && $basePath !== '\\') {
    $uri = str_replace($basePath, '', $uri);
}

// Clean up URI
$uri = parse_url($uri, PHP_URL_PATH);
$uri = trim($uri, '/');

// If empty, set to empty string for home route
if (empty($uri)) {
    $uri = '';
}

// Check session timeout for logged-in users (after URI is determined and all classes are loaded)
// Only check timeout if NOT already on login or home page to avoid redirect loops
if ($uri !== 'login' && $uri !== 'home' && $uri !== '') {
    if (isset($_SESSION['user_id'])) {
        require_once BASE_PATH . '/controllers/AuthController.php';
        $sessionValid = AuthController::checkSessionTimeout();
        // If session is invalid (timed out), redirect to login with timeout parameter
        if (!$sessionValid) {
            // Clear session data first
            $_SESSION = [];
            // Destroy the session file on server to prevent restoration on next request
            if (session_id() !== '') {
                session_destroy();
            }
            if (defined('APP_URL')) {
                header("Location: " . APP_URL . "/login?timeout=1");
            } else {
                header("Location: /login?timeout=1");
            }
            exit();
        }
    }
}

// Create router and dispatch
try {
    // Check if core files exist
    $coreFiles = [
        'Database.php',
        'Model.php',
        'View.php',
        'Controller.php',
        'Router.php'
    ];
    
    foreach ($coreFiles as $file) {
        $filePath = BASE_PATH . '/core/' . $file;
        if (!file_exists($filePath)) {
            throw new Exception("Core file missing: $file");
        }
    }
    
    // Check if routes file exists
    if (!file_exists(BASE_PATH . '/config/routes.php')) {
        throw new Exception("Routes configuration file not found. Please run install.php first.");
    }
    
    $router = new Router();
    $output = $router->route($uri);
    
    // Output the result if it's a string
    if (is_string($output)) {
        echo $output;
    }
    
    // Flush output buffer
    ob_end_flush();
    
} catch (ErrorException $e) {
    ob_end_clean();
    http_response_code(500);
    echo "<!DOCTYPE html><html><head><title>Error</title>";
    echo "<style>body{font-family:Arial,sans-serif;padding:40px;background:#ffffff;color:#000000;}";
    echo ".error-box{background:#ffffff;padding:30px;border-radius:8px;border:2px solid #000000;box-shadow:0 4px 8px rgba(0,0,0,0.2);max-width:800px;margin:0 auto;}";
    echo "h1{color:#000000;margin-top:0;border-bottom:2px solid #000000;padding-bottom:10px;}pre{background:#000000;color:#ffffff;padding:15px;border-radius:4px;overflow:auto;border:1px solid #000000;}</style></head><body>";
    echo "<div class='error-box'>";
    echo "<h1>Application Error</h1>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    if (ini_get('display_errors')) {
        echo "<h3>Stack Trace:</h3>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
    echo "</div></body></html>";
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo "<!DOCTYPE html><html><head><title>Error</title>";
    echo "<style>body{font-family:Arial,sans-serif;padding:40px;background:#ffffff;color:#000000;}";
    echo ".error-box{background:#ffffff;padding:30px;border-radius:8px;border:2px solid #000000;box-shadow:0 4px 8px rgba(0,0,0,0.2);max-width:800px;margin:0 auto;}";
    echo "h1{color:#000000;margin-top:0;border-bottom:2px solid #000000;padding-bottom:10px;}h3{color:#000000;border-bottom:1px solid #000000;padding-bottom:5px;}pre{background:#000000;color:#ffffff;padding:15px;border-radius:4px;overflow:auto;border:1px solid #000000;}</style></head><body>";
    echo "<div class='error-box'>";
    echo "<h1>Application Error</h1>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    if (ini_get('display_errors')) {
        echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . " (Line: " . $e->getLine() . ")</p>";
        echo "<h3>Stack Trace:</h3>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    } else {
        echo "<p>Check the error log for more details.</p>";
    }
    echo "</div></body></html>";
} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo "<!DOCTYPE html><html><head><title>Fatal Error</title>";
    echo "<style>body{font-family:Arial,sans-serif;padding:40px;background:#ffffff;color:#000000;}";
    echo ".error-box{background:#ffffff;padding:30px;border-radius:8px;border:2px solid #000000;box-shadow:0 4px 8px rgba(0,0,0,0.2);max-width:800px;margin:0 auto;}";
    echo "h1{color:#000000;margin-top:0;border-bottom:2px solid #000000;padding-bottom:10px;}h3{color:#000000;border-bottom:1px solid #000000;padding-bottom:5px;}pre{background:#000000;color:#ffffff;padding:15px;border-radius:4px;overflow:auto;border:1px solid #000000;}</style></head><body>";
    echo "<div class='error-box'>";
    echo "<h1>Fatal Error</h1>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    if (ini_get('display_errors')) {
        echo "<h3>Stack Trace:</h3>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
    echo "</div></body></html>";
}

