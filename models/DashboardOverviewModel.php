<?php
/**
 * Read-only aggregates for the main dashboard:
 *  - students per academic year (department -> course drill-down)
 *  - staff by category (position) and employment type
 *
 * Hostel availability is served by RoomModel::getOccupancyReport() so the dashboard and the
 * Hostel Report page always show the same figures.
 */
class DashboardOverviewModel extends Model {

    /** Enrolment statuses counted as "current" students (same rule as the dashboard total). */
    private const CURRENT_STATUSES = "('Following', 'Internship')";

    /** Employment types always shown first, in this order. */
    private const CORE_STAFF_TYPES = ['Permanent', 'On Contract', 'Visiting Lecturer'];

    /**
     * @return list<array<string, mixed>>
     */
    private function rows(string $sql): array {
        $result = $this->db->query($sql);
        $rows = [];
        while ($result && ($row = $result->fetch_assoc())) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * SQL select-list shared by the year / department / course queries.
     * "total" is Following + Internship (the dashboard rule); Completed is reported on its own and is
     * never part of the total. Gender counts cover the same current (Following + Internship) students.
     * Counts are distinct students.
     */
    private function statusColumns(): string {
        $cur = self::CURRENT_STATUSES;
        return "COUNT(DISTINCT CASE WHEN se.`student_enroll_status` IN {$cur} THEN s.`student_id` END) AS total,
                COUNT(DISTINCT CASE WHEN se.`student_enroll_status` = 'Following' THEN s.`student_id` END) AS following,
                COUNT(DISTINCT CASE WHEN se.`student_enroll_status` = 'Internship' THEN s.`student_id` END) AS internship,
                COUNT(DISTINCT CASE WHEN se.`student_enroll_status` = 'Completed' THEN s.`student_id` END) AS completed,
                COUNT(DISTINCT CASE WHEN se.`student_enroll_status` IN {$cur} AND s.`student_gender` = 'Male' THEN s.`student_id` END) AS male,
                COUNT(DISTINCT CASE WHEN se.`student_enroll_status` IN {$cur} AND s.`student_gender` = 'Female' THEN s.`student_id` END) AS female,
                COUNT(DISTINCT CASE WHEN se.`student_enroll_status` IN {$cur} AND se.`course_mode` = 'Full' THEN s.`student_id` END) AS full_time,
                COUNT(DISTINCT CASE WHEN se.`student_enroll_status` IN {$cur} AND se.`course_mode` = 'Part' THEN s.`student_id` END) AS part_time";
    }

    /**
     * Current (Following + Internship) students split by study mode, across all academic years.
     * Counts are distinct students, so a student enrolled in both modes appears in both figures.
     *
     * @return array{full_time: int, part_time: int}
     */
    public function getStudyModeTotals(): array {
        $cur = self::CURRENT_STATUSES;
        $rows = $this->rows("SELECT
                COUNT(DISTINCT CASE WHEN se.`course_mode` = 'Full' THEN se.`student_id` END) AS full_time,
                COUNT(DISTINCT CASE WHEN se.`course_mode` = 'Part' THEN se.`student_id` END) AS part_time
            FROM `student_enroll` se
            INNER JOIN `student` s ON s.`student_id` = se.`student_id`
            WHERE se.`student_enroll_status` IN {$cur}");
        return [
            'full_time' => (int) ($rows[0]['full_time'] ?? 0),
            'part_time' => (int) ($rows[0]['part_time'] ?? 0),
        ];
    }

    /**
     * @param array<string, mixed> $r
     * @return array<string, int>
     */
    private function statusCounts(array $r): array {
        return [
            'total' => (int) $r['total'],
            'following' => (int) $r['following'],
            'internship' => (int) $r['internship'],
            'completed' => (int) $r['completed'],
            'male' => (int) $r['male'],
            'female' => (int) $r['female'],
            'full_time' => (int) $r['full_time'],
            'part_time' => (int) $r['part_time'],
        ];
    }

    /**
     * Students per academic year, newest year first, with Following / Internship / Completed counts,
     * plus departments, each with courses.
     *
     * A student enrolled on two courses in one year is counted once in the year and department
     * figures, and once per course in the course rows.
     *
     * @return list<array<string, mixed>>
     */
    public function getStudentsByAcademicYear(): array {
        $years = [];

        // Every academic year, even when it has no students.
        foreach ($this->rows("SELECT DISTINCT `academic_year` FROM `academic`
                              WHERE `academic_year` IS NOT NULL AND `academic_year` <> ''") as $r) {
            $years[(string) $r['academic_year']] = $this->emptyYear((string) $r['academic_year']);
        }

        $cols = $this->statusColumns();
        $statuses = "('Following', 'Internship', 'Completed')";

        foreach ($this->rows("SELECT se.`academic_year` AS academic_year, {$cols}
                FROM `student_enroll` se
                INNER JOIN `student` s ON s.`student_id` = se.`student_id`
                WHERE se.`student_enroll_status` IN {$statuses}
                GROUP BY se.`academic_year`") as $r) {
            $key = (string) $r['academic_year'];
            // Left side wins on duplicate keys, so the fresh counts replace the zeros.
            $years[$key] = $this->statusCounts($r) + ($years[$key] ?? $this->emptyYear($key));
        }

        foreach ($this->rows("SELECT se.`academic_year` AS academic_year,
                    d.`department_id` AS department_id, d.`department_name` AS department_name, {$cols}
                FROM `student_enroll` se
                INNER JOIN `student` s ON s.`student_id` = se.`student_id`
                INNER JOIN `course` c ON c.`course_id` = se.`course_id`
                INNER JOIN `department` d ON d.`department_id` = c.`department_id`
                WHERE se.`student_enroll_status` IN {$statuses}
                GROUP BY se.`academic_year`, d.`department_id`, d.`department_name`
                ORDER BY d.`department_name`") as $r) {
            $key = (string) $r['academic_year'];
            $years[$key] = $years[$key] ?? $this->emptyYear($key);
            $years[$key]['departments'][(string) $r['department_id']] = $this->statusCounts($r) + [
                'department_id' => (string) $r['department_id'],
                'department_name' => (string) $r['department_name'],
                'courses' => [],
            ];
        }

        foreach ($this->rows("SELECT se.`academic_year` AS academic_year,
                    d.`department_id` AS department_id,
                    c.`course_id` AS course_id, c.`course_name` AS course_name,
                    c.`course_nvq_level` AS nvq_level, {$cols}
                FROM `student_enroll` se
                INNER JOIN `student` s ON s.`student_id` = se.`student_id`
                INNER JOIN `course` c ON c.`course_id` = se.`course_id`
                INNER JOIN `department` d ON d.`department_id` = c.`department_id`
                WHERE se.`student_enroll_status` IN {$statuses}
                GROUP BY se.`academic_year`, d.`department_id`, c.`course_id`, c.`course_name`, c.`course_nvq_level`
                ORDER BY c.`course_nvq_level`, c.`course_name`") as $r) {
            $key = (string) $r['academic_year'];
            $dept = (string) $r['department_id'];
            if (!isset($years[$key]['departments'][$dept])) {
                continue;
            }
            $years[$key]['departments'][$dept]['courses'][] = $this->statusCounts($r) + [
                'course_id' => (string) $r['course_id'],
                'course_name' => trim((string) $r['course_name']),
                'nvq_level' => (string) $r['nvq_level'],
            ];
        }

        // Newest academic year first ("2025/2026" sorts correctly as text).
        krsort($years, SORT_STRING);
        foreach ($years as &$year) {
            $year['departments'] = array_values($year['departments']);
        }
        unset($year);

        return array_values($years);
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyYear(string $year): array {
        return [
            'academic_year' => $year,
            'total' => 0, 'following' => 0, 'internship' => 0, 'completed' => 0,
            'male' => 0, 'female' => 0, 'full_time' => 0, 'part_time' => 0,
            'departments' => [],
        ];
    }

    /**
     * Working staff grouped by category (position) and by department, split by employment type.
     *
     * @return array{types: list<string>, by_category: list<array<string, mixed>>, by_department: list<array<string, mixed>>, totals: array<string, int>}
     */
    public function getStaffBreakdown(): array {
        // Look-ups are fetched separately (no cross-table joins) so differing table collations cannot break the query.
        $positionNames = [];
        foreach ($this->rows("SELECT `staff_position_type_id` AS id, `staff_position_type_name` AS name,
                                     `staff_position` AS sort_order
                              FROM `staff_position_type`") as $r) {
            $positionNames[(string) $r['id']] = ['name' => trim((string) $r['name']), 'sort' => (int) $r['sort_order']];
        }
        $departmentNames = [];
        foreach ($this->rows("SELECT `department_id`, `department_name` FROM `department`") as $r) {
            $departmentNames[(string) $r['department_id']] = (string) $r['department_name'];
        }

        $rows = $this->rows("SELECT `staff_position` AS position_id, `department_id` AS department_id,
                                    `staff_type` AS staff_type, COUNT(*) AS total
                             FROM `staff`
                             WHERE `staff_status` = 'Working'
                             GROUP BY `staff_position`, `department_id`, `staff_type`");

        $types = self::CORE_STAFF_TYPES;
        $byCategory = [];
        $byDepartment = [];
        $totals = ['all' => 0];

        foreach ($rows as $r) {
            $type = trim((string) $r['staff_type']);
            if ($type === '') {
                $type = 'Unassigned';
            }
            if (!in_array($type, $types, true)) {
                $types[] = $type;
            }
            $count = (int) $r['total'];

            $posId = (string) $r['position_id'];
            $deptId = (string) $r['department_id'];

            if (!isset($byCategory[$posId])) {
                $byCategory[$posId] = [
                    'label' => $positionNames[$posId]['name'] ?? ($posId !== '' ? $posId : 'Not set'),
                    'sort' => $positionNames[$posId]['sort'] ?? 999,
                    'counts' => [], 'total' => 0,
                ];
            }
            if (!isset($byDepartment[$deptId])) {
                $byDepartment[$deptId] = [
                    'label' => $departmentNames[$deptId] ?? ($deptId !== '' ? $deptId : 'Not set'),
                    'sort' => 0,
                    'counts' => [], 'total' => 0,
                ];
            }

            $byCategory[$posId]['counts'][$type] = ($byCategory[$posId]['counts'][$type] ?? 0) + $count;
            $byCategory[$posId]['total'] += $count;
            $byDepartment[$deptId]['counts'][$type] = ($byDepartment[$deptId]['counts'][$type] ?? 0) + $count;
            $byDepartment[$deptId]['total'] += $count;

            $totals[$type] = ($totals[$type] ?? 0) + $count;
            $totals['all'] += $count;
        }

        uasort($byCategory, function ($a, $b) {
            return [$a['sort'], $a['label']] <=> [$b['sort'], $b['label']];
        });
        uasort($byDepartment, function ($a, $b) {
            return strcasecmp($a['label'], $b['label']);
        });

        return [
            'types' => $types,
            'by_category' => array_values($byCategory),
            'by_department' => array_values($byDepartment),
            'totals' => $totals,
        ];
    }
}
