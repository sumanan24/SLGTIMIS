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

/**
 * Events per ISAPI request (maxResults). Larger = fewer HTTP round-trips.
 * Set 2000 for bigger pages; if the device returns HTTP 400, lower (e.g. 500 or 100) per firmware limits.
 */
define('HIKVISION_MAX_RESULTS_PER_CHUNK', 2000);

/** Access control events only (Hikvision major type 5). */
define('HIKVISION_ACS_MAJOR', 5);

/**
 * Access control sub-type (required with major=5 — omitting causes HTTP 400 errorMsg "minor").
 * Used when HIKVISION_ACS_MINORS is empty.
 */
define('HIKVISION_ACS_MINOR', 0);

/**
 * Comma-separated minor codes: runs one full paginated sync per value (chunked HTTP + INSERT IGNORE).
 * Captures different event subtypes under major=5 (face, card, door, etc.). Same punch may appear in multiple passes — duplicates are ignored.
 * Leave empty ("") to use only HIKVISION_ACS_MINOR once. Example: 0,1,2,3,75,76
 */
define('HIKVISION_ACS_MINORS', '0,75,1,2,3');

/** INSERT IGNORE batch size (multi-row). */
define('HIKVISION_INSERT_BATCH_SIZE', 500);

/** Safety cap: max HTTP pages per sync. */
define('HIKVISION_MAX_SYNC_PAGES', 20000);

/** Pagination default */
define('ATT_PAGE_SIZE', 25);

/**
 * Dashboard: auto-sync after loading data from DB (see dashboard_data.php). Set FALSE if PHP cannot reach the device.
 */
define('STAFF_ATT_DASHBOARD_AUTO_SYNC', true);

/**
 * Seconds between automatic pulls. 0 = every time you open the dashboard.
 */
define('STAFF_ATT_DASHBOARD_SYNC_COOLDOWN', 0);

/**
 * Hikvision sync window for dashboard auto-sync only — keep short so sync does not run too long.
 * P0D = today only (00:00:00–23:59:59 Asia/Colombo). Use Device sync page for a full week backfill.
 */
define('STAFF_DASHBOARD_AUTO_SYNC_INTERVAL', 'P0D');

/** Max raw rows (legacy list pages) */
define('STAFF_ATT_DASHBOARD_ROW_LIMIT', 500);

/**
 * Default date range on Device sync page: end = today 23:59:59, start = (end − interval) at 00:00:00.
 * P6D = seven calendar days inclusive (today + previous six days).
 */
define('STAFF_ATTENDANCE_SYNC_DEFAULT_INTERVAL', 'P6D');

/**
 * After sync, UPDATE rows with empty name/dept from `staff` (only when REQUIRE_STAFF_DIRECTORY is false).
 */
define('STAFF_ATTENDANCE_ENRICH_FROM_STAFF_TABLE', true);

/**
 * If true: only INSERT when employee_no matches staff.staff_id or staff.staff_nic (names from staff).
 * If false: INSERT every valid punch from the device (full data); empty names can be filled later via STAFF_ATTENDANCE_ENRICH_FROM_STAFF_TABLE.
 */
define('STAFF_ATTENDANCE_REQUIRE_STAFF_DIRECTORY', false);

/** Default date range on dashboard (days inclusive back from date_to). 1 = today only on first load. */
define('STAFF_DASHBOARD_SUMMARY_DAYS', 1);

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
