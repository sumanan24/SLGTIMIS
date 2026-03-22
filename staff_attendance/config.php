<?php
/**
 * Staff Attendance module — uses project DB from config/database.php
 */

declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/config/database.php';

/** Application & attendance storage timezone (Sri Lanka) */
define('STAFF_TIMEZONE', 'Asia/Colombo');

date_default_timezone_set(STAFF_TIMEZONE);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Hikvision terminal (Digest auth) — 172.16.x.x is private LAN only.
 * The machine running PHP must have a route to this IP (same network, VPN, or run sync on the LAN server).
 * If you see "Failed to connect / Timeout", the server cannot reach the device (not a password issue).
 */
define('HIKVISION_IP', '172.16.0.230');
define('HIKVISION_USER', 'admin');
define('HIKVISION_PASS', 'TCI@itgls2025#@');

/** Use false if device serves HTTP only */
define('HIKVISION_USE_HTTPS', false);

/**
 * Non-standard port only (e.g. 8080). Use 0 for HTTP default 80 or HTTPS default 443.
 */
define('HIKVISION_HTTP_PORT', 0);

/** Seconds to wait for TCP connect per ISAPI request (raise on slow LAN; won't fix wrong network). */
define('HIKVISION_CURL_CONNECT_TIMEOUT', 12);

/** Whole-request timeout for single HTTP call (fallback / digest streams). */
define('HIKVISION_CURL_TIMEOUT', 60);

/**
 * Multi-page sync: longer per-request timeout (each chunk may be large).
 * Full history uses many chunks — see HIKVISION_MAX_RESULTS_PER_CHUNK.
 */
define('HIKVISION_SYNC_CURL_TIMEOUT', 300);

/** DS-K1T320MFWX / ISAPI: events per request (pagination step). */
define('HIKVISION_MAX_RESULTS_PER_CHUNK', 100);

/** Access control events only (Hikvision major type 5). */
define('HIKVISION_ACS_MAJOR', 5);

/**
 * Access control sub-type (required with major=5 — omitting causes HTTP 400 errorMsg "minor").
 * Use 0 for all sub-types on many DS-K* terminals; use 75 if you need face-recognition events only or if 0 returns HTTP 400.
 */
define('HIKVISION_ACS_MINOR', 0);

/** INSERT IGNORE batch size (multi-row). */
define('HIKVISION_INSERT_BATCH_SIZE', 500);

/** Safety cap: max HTTP pages per sync. */
define('HIKVISION_MAX_SYNC_PAGES', 20000);

/** Pagination default */
define('ATT_PAGE_SIZE', 25);

/**
 * Dashboard: auto-sync when opening dashboard.
 * Keep FALSE if PHP runs on a public server — 172.16.x.x is unreachable from the internet.
 * Use sync_attendance.php from a PC on the same LAN as the Hikvision, or enable this only when
 * the web server is on the LAN / VPN to the device.
 */
define('STAFF_ATT_DASHBOARD_AUTO_SYNC', false);

/**
 * Seconds between automatic pulls. 0 = every time you open the dashboard.
 */
define('STAFF_ATT_DASHBOARD_SYNC_COOLDOWN', 0);

/** Max raw rows (legacy list pages) */
define('STAFF_ATT_DASHBOARD_ROW_LIMIT', 500);

/**
 * Hikvision sync window when dashboard opens.
 * Use at least P1M so staff who did not punch every week still appear after sync (200+ employees).
 * P1W only loads people active in that week.
 */
define('STAFF_SYNC_LOOKBACK_INTERVAL', 'P1M');

/** Default date range on dashboard summary (days inclusive from date_to backwards) */
define('STAFF_DASHBOARD_SUMMARY_DAYS', 7);

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
    @$conn->query("SET time_zone = '+05:30'");
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

/**
 * From GROUP_CONCAT of times (HH:MM:SS) ordered: first = check-in, last = check-out, between = other punches.
 *
 * @return array{in: string, out: string, other: list<string>}
 */
function attendance_split_day_times(string $timesCsv): array
{
    $timesCsv = trim($timesCsv);
    if ($timesCsv === '') {
        return ['in' => '', 'out' => '', 'other' => []];
    }
    $parts = array_map('trim', explode(',', $timesCsv));
    $parts = array_values(array_filter($parts, static function ($t) {
        return $t !== '';
    }));
    $n = count($parts);
    if ($n === 0) {
        return ['in' => '', 'out' => '', 'other' => []];
    }
    if ($n === 1) {
        return ['in' => $parts[0], 'out' => $parts[0], 'other' => []];
    }
    return [
        'in' => $parts[0],
        'out' => $parts[$n - 1],
        'other' => array_slice($parts, 1, $n - 2),
    ];
}
