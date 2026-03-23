<?php
declare(strict_types=1);

/**
 * Month report detail: same rows as the device dashboard (per employee per day with times),
 * for the calendar month range. Uses staff_attendance_dashboard_fetch_from_db().
 *
 * @return array{employees: array, grouped: array, dbError: string|null}
 */
function staff_attendance_month_report_fetch(string $reportMonth, string $employeeNo): array
{
    require_once __DIR__ . '/../config.php';
    require_once __DIR__ . '/dashboard_data.php';

    if ($reportMonth === '' || !preg_match('/^\d{4}-\d{2}$/', $reportMonth)) {
        return [
            'employees' => [],
            'grouped' => [],
            'dbError' => null,
        ];
    }

    $dateFrom = $reportMonth . '-01';
    $dateTo = date('Y-m-t', strtotime($dateFrom));
    $employeeNo = trim($employeeNo);

    return staff_attendance_dashboard_fetch_from_db($dateFrom, $dateTo, $employeeNo, true);
}

/**
 * Sort grouped rows by staff name, employee no., then date (for “all employees” view and PDF sections).
 *
 * @param array<int, array<string, mixed>> $grouped
 * @return array<int, array<string, mixed>>
 */
function staff_attendance_month_report_sort_grouped(array $grouped): array
{
    $rows = $grouped;
    usort($rows, static function (array $a, array $b): int {
        $cmp = strcmp((string) ($a['staff_name'] ?? ''), (string) ($b['staff_name'] ?? ''));
        if ($cmp !== 0) {
            return $cmp;
        }
        $cmp = strcmp((string) ($a['employee_no'] ?? ''), (string) ($b['employee_no'] ?? ''));
        if ($cmp !== 0) {
            return $cmp;
        }

        return strcmp((string) ($a['d'] ?? ''), (string) ($b['d'] ?? ''));
    });

    return $rows;
}

/**
 * One PDF table row (no employee columns — name appears in section header).
 *
 * @param array<string, mixed> $r
 * @return array<string, string>
 */
function staff_attendance_month_report_row_for_pdf(array $r): array
{
    require_once __DIR__ . '/../config.php';

    $split = attendance_split_day_times((string) ($r['times_csv'] ?? ''));
    $d = (string) ($r['d'] ?? '');
    $dayLabel = $d !== '' ? date('l', strtotime($d . ' 12:00:00')) : '';
    $otherStr = $split['other'] !== [] ? implode(', ', $split['other']) : '—';

    return [
        'date' => $d,
        'day' => $dayLabel,
        'check_in' => $split['in'],
        'check_out' => $split['out'],
        'other' => $otherStr === '—' ? '' : $otherStr,
    ];
}

/**
 * Group rows into PDF sections: one section per employee (A4 portrait, one page per employee).
 *
 * @param array<int, array<string, mixed>> $grouped
 * @return array<int, array{employeeLabel: string, rows: array<int, array<string, string>>}>
 */
function staff_attendance_month_report_sections_from_grouped(array $grouped): array
{
    if ($grouped === []) {
        return [];
    }

    $sorted = staff_attendance_month_report_sort_grouped($grouped);
    $sections = [];
    $lastEno = null;

    foreach ($sorted as $r) {
        $eno = (string) ($r['employee_no'] ?? '');
        $sn = trim((string) ($r['staff_name'] ?? ''));
        $label = $sn !== '' ? $sn . ' (' . $eno . ')' : $eno;

        if ($lastEno !== $eno) {
            $sections[] = [
                'employeeLabel' => $label,
                'rows' => [],
            ];
            $lastEno = $eno;
        }

        $sections[count($sections) - 1]['rows'][] = staff_attendance_month_report_row_for_pdf($r);
    }

    return $sections;
}

/**
 * Display month label and employee filter line for report header / PDF.
 *
 * @param array<int, array<string, mixed>> $employees
 * @return array{monthDisplay: string, employeeFilterLabel: string}
 */
function staff_attendance_month_report_header_meta(string $reportMonth, string $employeeNo, array $employees): array
{
    $monthDisplay = '';
    if ($reportMonth !== '' && preg_match('/^\d{4}-\d{2}$/', $reportMonth)) {
        $monthDisplay = date('F Y', strtotime($reportMonth . '-01'));
    }

    $employeeFilterLabel = 'All employees';
    $employeeNo = trim($employeeNo);
    if ($employeeNo !== '') {
        $found = false;
        foreach ($employees as $em) {
            if ((string) ($em['employee_no'] ?? '') === $employeeNo) {
                $sn = trim((string) ($em['staff_name'] ?? ''));
                $employeeFilterLabel = $sn !== '' ? $sn . ' (' . $employeeNo . ')' : $employeeNo;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $employeeFilterLabel = $employeeNo;
        }
    }

    return [
        'monthDisplay' => $monthDisplay,
        'employeeFilterLabel' => $employeeFilterLabel,
    ];
}
