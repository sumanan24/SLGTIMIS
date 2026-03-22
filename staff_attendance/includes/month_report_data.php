<?php
declare(strict_types=1);

/**
 * Month summary rows: one row per employee_no for the given YYYY-MM.
 *
 * @return array<int, array<string, mixed>>
 */
function staff_attendance_month_report_rows(string $reportMonth, string $staffNameFilter): array
{
    require_once __DIR__ . '/../config.php';

    $nameOk = 'staff_name IS NOT NULL AND TRIM(staff_name) <> \'\'';
    $db = attendance_db();

    $staffNameFilter = trim($staffNameFilter);

    $baseSql = "SELECT employee_no,
                       MAX(staff_name) AS staff_name,
                       MAX(department) AS department,
                       COUNT(DISTINCT DATE(attendance_time)) AS days_present,
                       COUNT(*) AS punch_count
                FROM staff_attendance
                WHERE $nameOk AND DATE_FORMAT(attendance_time, '%Y-%m') = ?";

    if ($staffNameFilter !== '') {
        $sql = $baseSql . ' AND staff_name LIKE ? GROUP BY employee_no ORDER BY staff_name ASC, employee_no ASC';
        $like = '%' . $staffNameFilter . '%';
        $stmt = $db->prepare($sql);
        if ($stmt === false) {
            return [];
        }
        $stmt->bind_param('ss', $reportMonth, $like);
    } else {
        $sql = $baseSql . ' GROUP BY employee_no ORDER BY staff_name ASC, employee_no ASC';
        $stmt = $db->prepare($sql);
        if ($stmt === false) {
            return [];
        }
        $stmt->bind_param('s', $reportMonth);
    }

    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows;
}
