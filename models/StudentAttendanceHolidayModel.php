<?php
/**
 * Institute / department / course holidays and special leave for student device attendance.
 * Used by SAO and ADM to exclude days from present/absent calculations.
 */
declare(strict_types=1);

class StudentAttendanceHolidayModel extends Model {
    protected $table = 'student_attendance_holiday';

    public const TYPE_HOLIDAY = 'holiday';
    public const TYPE_SPECIAL_LEAVE = 'special_leave';

    protected function getPrimaryKey() {
        return 'id';
    }

    public function ensureTable(): void {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->table}` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `leave_date` DATE NOT NULL,
            `leave_type` ENUM('holiday','special_leave') NOT NULL DEFAULT 'holiday',
            `title` VARCHAR(150) NOT NULL DEFAULT '',
            `department_id` VARCHAR(20) NULL DEFAULT NULL,
            `course_id` VARCHAR(50) NULL DEFAULT NULL,
            `notes` VARCHAR(255) NOT NULL DEFAULT '',
            `created_by` INT NULL DEFAULT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_leave_date` (`leave_date`),
            KEY `idx_leave_dept` (`department_id`),
            KEY `idx_leave_course` (`course_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->db->query($sql);
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function listForMonth(string $reportMonth, string $departmentId = '', string $courseId = ''): array {
        $this->ensureTable();
        if (!preg_match('/^\d{4}-\d{2}$/', $reportMonth)) {
            return [];
        }
        $from = $reportMonth . '-01';
        $to = date('Y-m-t', strtotime($from . ' 12:00:00'));

        $sql = "SELECT h.*, d.`department_name`, c.`course_name`
                FROM `{$this->table}` h
                LEFT JOIN `department` d ON d.`department_id` = h.`department_id`
                LEFT JOIN `course` c ON c.`course_id` = h.`course_id`
                WHERE h.`leave_date` BETWEEN ? AND ?";
        $types = 'ss';
        $params = [$from, $to];

        // Include institute-wide rows + matching scope
        $scope = [];
        $scope[] = '(h.`department_id` IS NULL OR h.`department_id` = \'\') AND (h.`course_id` IS NULL OR h.`course_id` = \'\')';
        if ($departmentId !== '') {
            $scope[] = 'h.`department_id` = ? AND (h.`course_id` IS NULL OR h.`course_id` = \'\')';
            $types .= 's';
            $params[] = $departmentId;
        }
        if ($courseId !== '') {
            $scope[] = 'h.`course_id` = ?';
            $types .= 's';
            $params[] = $courseId;
        } elseif ($departmentId !== '') {
            // All courses under department when course not selected
            $scope[] = 'h.`department_id` = ?';
            $types .= 's';
            $params[] = $departmentId;
        }
        $sql .= ' AND (' . implode(' OR ', $scope) . ')';
        $sql .= ' ORDER BY h.`leave_date` ASC, h.`id` ASC';

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();
        return $rows;
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function listRecent(int $limit = 200): array {
        $this->ensureTable();
        $limit = max(1, min(500, $limit));
        $sql = "SELECT h.*, d.`department_name`, c.`course_name`
                FROM `{$this->table}` h
                LEFT JOIN `department` d ON d.`department_id` = h.`department_id`
                LEFT JOIN `course` c ON c.`course_id` = h.`course_id`
                ORDER BY h.`leave_date` DESC, h.`id` DESC
                LIMIT {$limit}";
        $res = $this->db->query($sql);
        $rows = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    /**
     * Map Y-m-d => leave row for a given student scope.
     *
     * @return array<string, array<string,mixed>>
     */
    public function mapForScope(string $dateFrom, string $dateTo, string $departmentId = '', string $courseId = ''): array {
        $this->ensureTable();
        $sql = "SELECT * FROM `{$this->table}` WHERE `leave_date` BETWEEN ? AND ?";
        $types = 'ss';
        $params = [$dateFrom, $dateTo];
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        $map = [];
        while ($row = $res->fetch_assoc()) {
            $d = (string) ($row['leave_date'] ?? '');
            $dept = trim((string) ($row['department_id'] ?? ''));
            $course = trim((string) ($row['course_id'] ?? ''));
            $applies = false;
            if ($dept === '' && $course === '') {
                $applies = true; // all students
            } elseif ($course !== '' && $courseId !== '' && $course === $courseId) {
                $applies = true;
            } elseif ($course === '' && $dept !== '' && $departmentId !== '' && $dept === $departmentId) {
                $applies = true;
            }
            if ($applies && $d !== '') {
                $map[$d] = $row;
            }
        }
        $stmt->close();
        return $map;
    }

    public function createLeave(array $data, &$sqlError = null) {
        $this->ensureTable();
        return $this->create($data, $sqlError);
    }

    public function deleteLeave(int $id): bool {
        $this->ensureTable();
        return (bool) $this->delete($id);
    }
}
