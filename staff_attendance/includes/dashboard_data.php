<?php
declare(strict_types=1);

/**
 * Load dashboard rows and KPIs from DB (same filters used before/after device sync).
 *
 * @return array{employees: array<int, array<string, mixed>>, grouped: array<int, array<string, mixed>>, rangePunches: int, todayCount: int, distinctEmployees: int, dbError: string|null}
 */
function staff_attendance_dashboard_fetch_from_db(string $dateFrom, string $dateTo, string $employeeNo, string $todayYmd): array
{
    $employees = [];
    $grouped = [];
    $rangePunches = 0;
    $todayCount = 0;
    $distinctEmployees = 0;
    $dbError = null;

    try {
        $db = attendance_db();
        $db->query('SET SESSION group_concat_max_len = 16384');

        $nameOk = 'staff_name IS NOT NULL AND TRIM(staff_name) <> \'\'';

        $disR = $db->prepare('SELECT COUNT(DISTINCT employee_no) AS c FROM staff_attendance WHERE DATE(attendance_time) BETWEEN ? AND ? AND ' . $nameOk);
        $disR->bind_param('ss', $dateFrom, $dateTo);
        $disR->execute();
        $distinctEmployees = (int) ($disR->get_result()->fetch_assoc()['c'] ?? 0);
        $disR->close();

        $stmt = $db->prepare('SELECT COUNT(*) AS c FROM staff_attendance WHERE DATE(attendance_time) = ? AND ' . $nameOk);
        $stmt->bind_param('s', $todayYmd);
        $stmt->execute();
        $todayCount = (int) ($stmt->get_result()->fetch_assoc()['c'] ?? 0);
        $stmt->close();

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

        $rangeStmt = $db->prepare(
            'SELECT COUNT(*) AS c FROM staff_attendance WHERE DATE(attendance_time) BETWEEN ? AND ? AND ' . $nameOk
        );
        $rangeStmt->bind_param('ss', $dateFrom, $dateTo);
        $rangeStmt->execute();
        $rangePunches = (int) ($rangeStmt->get_result()->fetch_assoc()['c'] ?? 0);
        $rangeStmt->close();

        if ($employeeNo === '') {
            $sql = 'SELECT employee_no,
                           MAX(staff_name) AS staff_name,
                           MAX(department) AS department,
                           DATE(attendance_time) AS d,
                           GROUP_CONCAT(DATE_FORMAT(attendance_time, \'%H:%i:%s\') ORDER BY attendance_time SEPARATOR \',\') AS times_csv
                    FROM staff_attendance
                    WHERE DATE(attendance_time) BETWEEN ? AND ?
                      AND ' . $nameOk . '
                    GROUP BY employee_no, DATE(attendance_time)
                    ORDER BY d DESC, staff_name ASC, employee_no ASC';
            $gq = $db->prepare($sql);
            $gq->bind_param('ss', $dateFrom, $dateTo);
        } else {
            $sql = 'SELECT employee_no,
                           MAX(staff_name) AS staff_name,
                           MAX(department) AS department,
                           DATE(attendance_time) AS d,
                           GROUP_CONCAT(DATE_FORMAT(attendance_time, \'%H:%i:%s\') ORDER BY attendance_time SEPARATOR \',\') AS times_csv
                    FROM staff_attendance
                    WHERE DATE(attendance_time) BETWEEN ? AND ?
                      AND employee_no = ?
                      AND ' . $nameOk . '
                    GROUP BY employee_no, DATE(attendance_time)
                    ORDER BY d DESC, staff_name ASC, employee_no ASC';
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
        'rangePunches' => $rangePunches,
        'todayCount' => $todayCount,
        'distinctEmployees' => $distinctEmployees,
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

    $dateToIn = trim((string) ($_GET['date_to'] ?? ''));
    $dateFromIn = trim((string) ($_GET['date_from'] ?? ''));
    $employeeNo = trim((string) ($_GET['employee_no'] ?? ''));

    if ($dateToIn === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateToIn)) {
        $dateTo = $todayYmd;
    } else {
        $dateTo = $dateToIn;
    }

    $dateToDt = DateTimeImmutable::createFromFormat('Y-m-d', $dateTo, $tzDash);
    if ($dateToDt === false) {
        $dateToDt = $nowInTz;
        $dateTo = $todayYmd;
    }

    if ($dateFromIn === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFromIn)) {
        $dateFrom = $dateToDt->modify('-' . ($summaryDays - 1) . ' days')->format('Y-m-d');
    } else {
        $dateFrom = $dateFromIn;
    }

    if ($dateFrom > $dateTo) {
        $tmp = $dateFrom;
        $dateFrom = $dateTo;
        $dateTo = $tmp;
    }

    $spanDays = (int) floor((strtotime($dateTo) - strtotime($dateFrom)) / 86400) + 1;
    if ($spanDays > 31) {
        $dateFrom = (DateTimeImmutable::createFromFormat('Y-m-d', $dateTo, $tzDash) ?: $nowInTz)
            ->modify('-30 days')
            ->format('Y-m-d');
    }

    $data = staff_attendance_dashboard_fetch_from_db($dateFrom, $dateTo, $employeeNo, $todayYmd);

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
            $data = staff_attendance_dashboard_fetch_from_db($dateFrom, $dateTo, $employeeNo, $todayYmd);
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
        'rangePunches' => $data['rangePunches'],
        'todayCount' => $data['todayCount'],
        'distinctEmployees' => $data['distinctEmployees'],
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
