<?php
declare(strict_types=1);

/**
 * Load dashboard rows from DB (same filters used before/after device sync).
 *
 * @return array{employees: array<int, array<string, mixed>>, grouped: array<int, array<string, mixed>>, dbError: string|null}
 */
function staff_attendance_dashboard_fetch_from_db(string $dateFrom, string $dateTo, string $employeeNo, bool $groupedSortAsc = false): array
{
    $employees = [];
    $grouped = [];
    $dbError = null;

    try {
        $db = attendance_db();
        $db->query('SET SESSION group_concat_max_len = 16384');

        $nameOk = 'staff_name IS NOT NULL AND TRIM(staff_name) <> \'\'';

        $empStmt = $db->prepare(
            'SELECT DISTINCT employee_no, staff_name FROM staff_attendance WHERE DATE(attendance_time) BETWEEN ? AND ? AND ' . $nameOk . ' ORDER BY staff_name ASC, employee_no ASC'
        );
        $empStmt->bind_param('ss', $dateFrom, $dateTo);
        $empStmt->execute();
        $empRes = $empStmt->get_result();
        if ($empRes) {
            while ($row = $empRes->fetch_assoc()) {
                $employees[] = $row;
            }
        }
        $empStmt->close();

        $orderGrouped = $groupedSortAsc
            ? 'ORDER BY d ASC, MIN(attendance_time) ASC, staff_name ASC, employee_no ASC'
            : 'ORDER BY d DESC, MAX(attendance_time) DESC, staff_name ASC, employee_no ASC';

        if ($employeeNo === '') {
            $sql = 'SELECT employee_no,
                           MAX(staff_name) AS staff_name,
                           DATE(attendance_time) AS d,
                           GROUP_CONCAT(DATE_FORMAT(attendance_time, \'%H:%i:%s\') ORDER BY attendance_time SEPARATOR \',\') AS times_csv
                    FROM staff_attendance
                    WHERE DATE(attendance_time) BETWEEN ? AND ?
                      AND ' . $nameOk . '
                    GROUP BY employee_no, DATE(attendance_time)
                    ' . $orderGrouped;
            $gq = $db->prepare($sql);
            $gq->bind_param('ss', $dateFrom, $dateTo);
        } else {
            $sql = 'SELECT employee_no,
                           MAX(staff_name) AS staff_name,
                           DATE(attendance_time) AS d,
                           GROUP_CONCAT(DATE_FORMAT(attendance_time, \'%H:%i:%s\') ORDER BY attendance_time SEPARATOR \',\') AS times_csv
                    FROM staff_attendance
                    WHERE DATE(attendance_time) BETWEEN ? AND ?
                      AND employee_no = ?
                      AND ' . $nameOk . '
                    GROUP BY employee_no, DATE(attendance_time)
                    ' . $orderGrouped;
            $gq = $db->prepare($sql);
            $gq->bind_param('sss', $dateFrom, $dateTo, $employeeNo);
        }
        $gq->execute();
        $grouped = $gq->get_result()->fetch_all(MYSQLI_ASSOC);
        $gq->close();
    } catch (Throwable $e) {
        $dbError = $e->getMessage();
    }

    return [
        'employees' => $employees,
        'grouped' => $grouped,
        'dbError' => $dbError,
    ];
}

/**
 * Shared state for staff device attendance dashboard (standalone + main app layout).
 * Loads from DB first; then pulls today-only from device (config) for a fast sync, then re-queries DB on success.
 *
 * @return array<string, mixed>
 */
function staff_attendance_load_dashboard_state(): array
{
    require_once __DIR__ . '/../config.php';
    require_once __DIR__ . '/hikvision_sync_lib.php';

    $tzDash = new DateTimeZone(STAFF_TIMEZONE);
    $nowInTz = new DateTimeImmutable('now', $tzDash);
    $todayYmd = $nowInTz->format('Y-m-d');

    $summaryDays = defined('STAFF_DASHBOARD_SUMMARY_DAYS') ? max(1, min(31, (int) STAFF_DASHBOARD_SUMMARY_DAYS)) : 1;

    $employeeNo = trim((string) ($_GET['employee_no'] ?? ''));

    $dateTo = $todayYmd;
    $dateToDt = DateTimeImmutable::createFromFormat('Y-m-d', $dateTo, $tzDash);
    if ($dateToDt === false) {
        $dateToDt = $nowInTz;
        $dateTo = $todayYmd;
    }

    $dateFrom = $dateToDt->modify('-' . ($summaryDays - 1) . ' days')->format('Y-m-d');

    $data = staff_attendance_dashboard_fetch_from_db($dateFrom, $dateTo, $employeeNo);

    $nowTs = time();
    $lastAuto = (int) ($_SESSION['staff_attendance_last_auto_sync'] ?? 0);

    $shouldSync = false;
    if (STAFF_ATT_DASHBOARD_AUTO_SYNC) {
        if (STAFF_ATT_DASHBOARD_SYNC_COOLDOWN === 0) {
            $shouldSync = true;
        } else {
            $shouldSync = ($nowTs - $lastAuto >= STAFF_ATT_DASHBOARD_SYNC_COOLDOWN);
        }
    }

    if ($shouldSync) {
        $end = $nowInTz->setTime(23, 59, 59);
        $ivStr = defined('STAFF_DASHBOARD_AUTO_SYNC_INTERVAL')
            ? (string) STAFF_DASHBOARD_AUTO_SYNC_INTERVAL
            : 'P0D';
        try {
            $start = $end->sub(new DateInterval($ivStr))->setTime(0, 0, 0);
        } catch (Exception $e) {
            $start = $end->sub(new DateInterval('P0D'))->setTime(0, 0, 0);
        }
        $result = attendance_run_hikvision_sync($start, $end);
        $_SESSION['staff_attendance_last_auto_sync'] = $nowTs;

        if (!$result['ok']) {
            $_SESSION['flash_error'] = $result['error'] ?? 'Automatic sync failed.';
        } else {
            $data = staff_attendance_dashboard_fetch_from_db($dateFrom, $dateTo, $employeeNo);
        }
    }

    return [
        'summaryDays' => $summaryDays,
        'dateFrom' => $dateFrom,
        'dateTo' => $dateTo,
        'employeeNo' => $employeeNo,
        'todayYmd' => $todayYmd,
        'employees' => $data['employees'],
        'grouped' => $data['grouped'],
        'dbError' => $data['dbError'],
    ];
}

/**
 * Nav links for staff_attendance/*.php (relative to module folder).
 *
 * @return array{device: string, list: string, daily: string, month: string, sync: string}
 */
function staff_attendance_dashboard_urls_for_module(string $deviceDashboardHref): array
{
    $base = function_exists('attendance_base_url') ? attendance_base_url() : '';
    $prefix = ($base === '' || $base === '/') ? '' : rtrim($base, '/') . '/';

    return [
        'device' => $deviceDashboardHref,
        'list' => $prefix . 'list_attendance.php',
        'daily' => $prefix . 'daily_report.php',
        'month' => $prefix . 'month_report.php',
        'sync' => $prefix . 'sync_attendance.php',
    ];
}

/**
 * Nav URLs inside main SLGTIMIS layout (AttendanceController routes).
 *
 * @return array{device: string, list: string, daily: string, month: string, sync: string}
 */
function staff_attendance_embed_nav_urls(): array
{
    $root = rtrim(APP_URL, '/') . '/attendance/staff-device';

    return [
        'device' => $root,
        'list' => $root . '/list',
        'daily' => $root . '/daily',
        'month' => $root . '/month',
        'sync' => $root . '/sync',
    ];
}
