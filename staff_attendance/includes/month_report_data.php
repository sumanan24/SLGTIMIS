<?php
declare(strict_types=1);

/**
 * Month report detail: same rows as the device dashboard (per employee per day with times),
 * for the calendar month range. Uses staff_attendance_dashboard_fetch_from_db().
 *
 * @return array{employees: array, grouped: array, rangePunches: int, todayCount: int, distinctEmployees: int, dbError: string|null}
 */
function staff_attendance_month_report_fetch(string $reportMonth, string $employeeNo): array
{
    require_once __DIR__ . '/../config.php';
    require_once __DIR__ . '/dashboard_data.php';

    if ($reportMonth === '' || !preg_match('/^\d{4}-\d{2}$/', $reportMonth)) {
        return [
            'employees' => [],
            'grouped' => [],
            'rangePunches' => 0,
            'todayCount' => 0,
            'distinctEmployees' => 0,
            'dbError' => null,
        ];
    }

    $tz = new DateTimeZone(STAFF_TIMEZONE);
    $todayYmd = (new DateTimeImmutable('now', $tz))->format('Y-m-d');

    $dateFrom = $reportMonth . '-01';
    $dateTo = date('Y-m-t', strtotime($dateFrom));
    $employeeNo = trim($employeeNo);

    return staff_attendance_dashboard_fetch_from_db($dateFrom, $dateTo, $employeeNo, $todayYmd);
}

/**
 * Flat rows for PDF export (same columns as on-screen table).
 *
 * @param array<int, array<string, mixed>> $grouped
 * @return array<int, array<string, string>>
 */
function staff_attendance_month_report_pdf_rows(array $grouped): array
{
    require_once __DIR__ . '/../config.php';

    $out = [];
    foreach ($grouped as $r) {
        $split = attendance_split_day_times((string) ($r['times_csv'] ?? ''));
        $d = (string) ($r['d'] ?? '');
        $dayLabel = $d !== '' ? date('l', strtotime($d . ' 12:00:00')) : '';
        $otherStr = $split['other'] !== [] ? implode(', ', $split['other']) : '—';

        $out[] = [
            'employee_no' => (string) ($r['employee_no'] ?? ''),
            'name' => (string) ($r['staff_name'] ?? ''),
            'department' => (string) ($r['department'] ?? ''),
            'date' => $d,
            'day' => $dayLabel,
            'check_in' => $split['in'],
            'check_out' => $split['out'],
            'other' => $otherStr === '—' ? '' : $otherStr,
        ];
    }

    return $out;
}
