<?php
/**
 * Staff Attendance module — uses project DB from config/database.php
 */

declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/config/database.php';

date_default_timezone_set('Asia/Colombo');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Hikvision terminal (Digest auth) */
define('HIKVISION_IP', '172.16.0.230');
define('HIKVISION_USER', 'admin');
define('HIKVISION_PASS', 'TCI@itgls2025#@');

/** Use false if device serves HTTP only */
define('HIKVISION_USE_HTTPS', false);

/** Pagination default */
define('ATT_PAGE_SIZE', 25);

/**
 * Dashboard auto-sync throttle (seconds). 0 = sync on every dashboard load.
 * Use ?force_sync=1 on dashboard URL to sync immediately regardless of cooldown.
 */
define('STAFF_ATT_DASHBOARD_SYNC_COOLDOWN', 0);

/**
 * @return mysqli
 */
function attendance_db(): mysqli
{
    static $conn = null;
    if ($conn instanceof mysqli) {
        return $conn;
    }
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset(DB_CHARSET);
    return $conn;
}

function attendance_escape(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function attendance_base_url(): string
{
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $dir = str_replace('\\', '/', dirname($script));
    if ($dir === '/' || $dir === '.') {
        return '';
    }
    return rtrim($dir, '/');
}
