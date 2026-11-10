<?php
/**
 * Academic year master (table `academic`).
 */

class AcademicYearModel extends Model {
    protected $table = 'academic';

    protected function getPrimaryKey() {
        return 'academic_year';
    }

    public static function formatDate($value) {
        $value = trim((string) $value);
        if ($value === '' || $value === '0000-00-00') {
            return '—';
        }
        $ts = strtotime($value);
        return $ts ? date('d M Y', $ts) : $value;
    }

    public function getAll() {
        return $this->all('academic_year DESC');
    }

    public function getById($id) {
        return $this->find($id);
    }

    public function exists($id) {
        return $this->find($id) !== null;
    }

    public function createYear(array $data, &$sqlError = null) {
        return $this->create($data, $sqlError);
    }

    public function updateYear($id, array $data, &$sqlError = null) {
        return $this->update($id, $data, $sqlError);
    }

    public function deleteYear($id) {
        return $this->delete($id);
    }

    /**
     * Distinct students per academic year for the courses of one department.
     * total = current students (Following + Internship, same rule as the dashboard).
     *
     * @return array<string, array{total:int, following:int, internship:int, completed:int}>
     */
    public function studentCountsByDepartment($departmentId) {
        $out = [];
        $departmentId = trim((string) $departmentId);
        if ($departmentId === '') {
            return $out;
        }
        $stmt = $this->db->prepare(
            "SELECT se.`academic_year` AS academic_year,
                    COUNT(DISTINCT CASE WHEN se.`student_enroll_status` IN ('Following', 'Internship') THEN se.`student_id` END) AS total,
                    COUNT(DISTINCT CASE WHEN se.`student_enroll_status` = 'Following' THEN se.`student_id` END) AS following,
                    COUNT(DISTINCT CASE WHEN se.`student_enroll_status` = 'Internship' THEN se.`student_id` END) AS internship,
                    COUNT(DISTINCT CASE WHEN se.`student_enroll_status` = 'Completed' THEN se.`student_id` END) AS completed
             FROM `student_enroll` se
             INNER JOIN `course` c ON c.`course_id` = se.`course_id`
             WHERE c.`department_id` = ? AND se.`academic_year` IS NOT NULL AND se.`academic_year` <> ''
             GROUP BY se.`academic_year`"
        );
        if (!$stmt) {
            return $out;
        }
        $stmt->bind_param('s', $departmentId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($res && ($row = $res->fetch_assoc())) {
            $out[(string) $row['academic_year']] = [
                'total' => (int) $row['total'],
                'following' => (int) $row['following'],
                'internship' => (int) $row['internship'],
                'completed' => (int) $row['completed'],
            ];
        }
        $stmt->close();
        return $out;
    }

    /**
     * How many related records use this academic year.
     *
     * @return array{total:int, details:list<string>}
     */
    public function usageSummary($year) {
        $checks = [
            ['student_enroll', 'academic_year', 'student enrollment(s)'],
            ['groups', 'academic_year', 'group(s)'],
            ['staff_module_enrollment', 'academic_year', 'staff module enrollment(s)'],
        ];
        $total = 0;
        $details = [];
        $conn = $this->db->getConnection();

        foreach ($checks as [$table, $column, $label]) {
            $tbl = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($table) . "'");
            if (!$tbl || $tbl->num_rows === 0) {
                continue;
            }
            $col = $conn->query("SHOW COLUMNS FROM `{$table}` LIKE '" . $conn->real_escape_string($column) . "'");
            if (!$col || $col->num_rows === 0) {
                continue;
            }
            $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM `{$table}` WHERE `{$column}` = ?");
            if (!$stmt) {
                continue;
            }
            $stmt->bind_param('s', $year);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $count = (int) ($row['c'] ?? 0);
            if ($count > 0) {
                $total += $count;
                $details[] = $count . ' ' . $label;
            }
        }

        return ['total' => $total, 'details' => $details];
    }
}
