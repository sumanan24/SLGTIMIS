<?php
/**
 * Department Model
 */

class DepartmentModel extends Model {
    protected $table = 'department';

    public const HEAD_PERMANENT = 'permanent';
    public const HEAD_ACTING = 'acting';
    public const HEAD_COVER_UP = 'cover_up';

    public const CATEGORY_ACADEMIC = 'academic';
    public const CATEGORY_ADMIN = 'admin';

    public function __construct() {
        parent::__construct();
        $this->ensureCategoryColumn();
        $this->ensureHeadColumns();
        $this->ensureHeadHistoryTable();
    }

    /**
     * @return array<string, string> category key => label
     */
    public static function categories(): array {
        return [
            self::CATEGORY_ACADEMIC => 'Academic',
            self::CATEGORY_ADMIN => 'Admin',
        ];
    }

    public static function normalizeCategory($value, string $default = self::CATEGORY_ACADEMIC): string {
        $value = strtolower(trim((string) $value));
        return isset(self::categories()[$value]) ? $value : $default;
    }

    /**
     * Adds department_category (existing departments default to academic).
     */
    private function ensureCategoryColumn(): void {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        try {
            $conn = $this->db->getConnection();
            $have = $conn->query("SHOW COLUMNS FROM `{$this->table}` LIKE 'department_category'");
            if ($have && $have->num_rows > 0) {
                return;
            }
            $sql = "ALTER TABLE `{$this->table}`
                ADD COLUMN `department_category` VARCHAR(20) NOT NULL DEFAULT 'academic'
                COMMENT 'academic or admin'";
            if (!$conn->query($sql)) {
                error_log('DepartmentModel::ensureCategoryColumn failed: ' . $conn->error);
            }
        } catch (Throwable $e) {
            error_log('DepartmentModel::ensureCategoryColumn: ' . $e->getMessage());
        }
    }

    /**
     * Counts per category (for the filter tabs).
     *
     * @return array<string, int>
     */
    public function getCategoryCounts(string $search = ''): array {
        $counts = [self::CATEGORY_ACADEMIC => 0, self::CATEGORY_ADMIN => 0];
        $sql = "SELECT `department_category` AS cat, COUNT(*) AS total FROM `{$this->table}`";
        $search = trim($search);
        if ($search !== '') {
            $sql .= " WHERE `department_id` LIKE ? OR `department_name` LIKE ?";
        }
        $sql .= " GROUP BY `department_category`";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return $counts;
        }
        if ($search !== '') {
            $like = '%' . $search . '%';
            $stmt->bind_param('ss', $like, $like);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        while ($res && ($row = $res->fetch_assoc())) {
            $key = self::normalizeCategory($row['cat'] ?? '');
            $counts[$key] += (int) $row['total'];
        }
        $stmt->close();
        return $counts;
    }
    
    protected function getPrimaryKey() {
        return 'department_id';
    }

    /**
     * @return array<string, string> type key => label
     */
    public static function headTypes(): array {
        return [
            self::HEAD_PERMANENT => 'Permanent',
            self::HEAD_ACTING => 'Acting',
            self::HEAD_COVER_UP => 'Cover-up',
        ];
    }

    /**
     * Adds head-of-department columns (one responsible person per department).
     */
    private function ensureHeadColumns(): void {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        try {
            $conn = $this->db->getConnection();
            $have = $conn->query("SHOW COLUMNS FROM `{$this->table}` LIKE 'head_staff_id'");
            if ($have && $have->num_rows > 0) {
                return;
            }

            // Match staff.staff_id type/collation so the join never hits a collation mismatch.
            $type = 'VARCHAR(64)';
            $collation = '';
            $col = $conn->query("SHOW FULL COLUMNS FROM `staff` LIKE 'staff_id'");
            if ($col && ($row = $col->fetch_assoc())) {
                $type = $row['Type'] ?: $type;
                if (!empty($row['Collation']) && preg_match('/^[A-Za-z0-9_]+$/', $row['Collation'])) {
                    // A collation is only valid together with its own character set (prefix before "_").
                    $charset = explode('_', $row['Collation'])[0];
                    $collation = " CHARACTER SET {$charset} COLLATE {$row['Collation']}";
                }
            }

            $sql = "ALTER TABLE `{$this->table}`
                ADD COLUMN `head_staff_id` {$type}{$collation} NULL DEFAULT NULL COMMENT 'Staff responsible for this department',
                ADD COLUMN `head_type` VARCHAR(20) NULL DEFAULT NULL COMMENT 'permanent, acting, cover_up',
                ADD COLUMN `head_start_date` DATE NULL DEFAULT NULL,
                ADD COLUMN `head_remarks` VARCHAR(255) NULL DEFAULT NULL,
                ADD COLUMN `head_assigned_by` VARCHAR(100) NULL DEFAULT NULL,
                ADD COLUMN `head_assigned_at` DATETIME NULL DEFAULT NULL";
            if (!$conn->query($sql)) {
                error_log('DepartmentModel::ensureHeadColumns failed: ' . $conn->error);
            }
        } catch (Throwable $e) {
            error_log('DepartmentModel::ensureHeadColumns: ' . $e->getMessage());
        }
    }

    /**
     * Staff who can be assigned as head (currently working).
     *
     * @return list<array<string, mixed>>
     */
    public function getAssignableStaff(): array {
        $sql = "SELECT s.`staff_id`, s.`staff_name`, s.`staff_position`, s.`staff_type`,
                       s.`department_id`, d.`department_name`
                FROM `staff` s
                LEFT JOIN `{$this->table}` d ON d.`department_id` = s.`department_id`
                WHERE s.`staff_status` = 'Working'
                ORDER BY s.`staff_name` ASC, s.`staff_id` ASC";
        $result = $this->db->query($sql);
        $rows = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    /**
     * Department where this staff member is the permanent head (other than $exceptDepartmentId).
     *
     * @return array<string, mixed>|null
     */
    public function findPermanentHeadPost(string $staffId, string $exceptDepartmentId = ''): ?array {
        $sql = "SELECT `department_id`, `department_name` FROM `{$this->table}`
                WHERE `head_staff_id` = ? AND `head_type` = ? AND `department_id` <> ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return null;
        }
        $type = self::HEAD_PERMANENT;
        $stmt->bind_param('sss', $staffId, $type, $exceptDepartmentId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = ($res && $res->num_rows > 0) ? $res->fetch_assoc() : null;
        $stmt->close();
        return $row ?: null;
    }

    public function assignHead(string $departmentId, string $staffId, string $type, ?string $startDate, ?string $remarks, string $assignedBy, &$sqlError = null): bool {
        $ok = $this->update($departmentId, [
            'head_staff_id' => $staffId,
            'head_type' => $type,
            'head_start_date' => $startDate,
            'head_remarks' => $remarks,
            'head_assigned_by' => $assignedBy,
            'head_assigned_at' => date('Y-m-d H:i:s'),
        ], $sqlError);

        if ($ok) {
            $this->closeOpenHeadHistory($departmentId);
            $name = '';
            $stmt = $this->db->prepare("SELECT `staff_name` FROM `staff` WHERE `staff_id` = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param('s', $staffId);
                $stmt->execute();
                $res = $stmt->get_result();
                $name = ($res && ($r = $res->fetch_assoc())) ? (string) $r['staff_name'] : '';
                $stmt->close();
            }
            $ins = $this->db->prepare(
                "INSERT INTO `department_head_history`
                 (`department_id`, `staff_id`, `staff_name`, `head_type`, `start_date`, `remarks`, `assigned_by`)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            if ($ins) {
                $start = $startDate ?: date('Y-m-d');
                $ins->bind_param('sssssss', $departmentId, $staffId, $name, $type, $start, $remarks, $assignedBy);
                $ins->execute();
                $ins->close();
            }
        }
        return $ok;
    }

    public function clearHead(string $departmentId, &$sqlError = null): bool {
        $ok = $this->update($departmentId, [
            'head_staff_id' => null,
            'head_type' => null,
            'head_start_date' => null,
            'head_remarks' => null,
            'head_assigned_by' => null,
            'head_assigned_at' => null,
        ], $sqlError);
        if ($ok) {
            $this->closeOpenHeadHistory($departmentId);
        }
        return $ok;
    }

    private function closeOpenHeadHistory(string $departmentId): void {
        $stmt = $this->db->prepare(
            "UPDATE `department_head_history` SET `end_date` = CURDATE()
             WHERE `department_id` = ? AND `end_date` IS NULL"
        );
        if ($stmt) {
            $stmt->bind_param('s', $departmentId);
            $stmt->execute();
            $stmt->close();
        }
    }

    private function ensureHeadHistoryTable(): void {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        try {
            $sql = "CREATE TABLE IF NOT EXISTS `department_head_history` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `department_id` VARCHAR(6) NOT NULL,
                `staff_id` VARCHAR(64) NOT NULL,
                `staff_name` VARCHAR(100) NULL DEFAULT NULL,
                `head_type` VARCHAR(20) NOT NULL,
                `start_date` DATE NULL DEFAULT NULL,
                `end_date` DATE NULL DEFAULT NULL,
                `remarks` VARCHAR(255) NULL DEFAULT NULL,
                `assigned_by` VARCHAR(100) NULL DEFAULT NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_dept_head_hist_dept` (`department_id`, `start_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            if (!$this->db->getConnection()->query($sql)) {
                error_log('DepartmentModel::ensureHeadHistoryTable failed: ' . $this->db->getConnection()->error);
            }
        } catch (Throwable $e) {
            error_log('DepartmentModel::ensureHeadHistoryTable: ' . $e->getMessage());
        }
    }

    /**
     * Run a prepared SELECT and return all rows.
     *
     * @param list<string|int> $params
     * @return list<array<string, mixed>>
     */
    private function fetchRows(string $sql, string $types = '', array $params = []): array {
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log('DepartmentModel::fetchRows prepare failed: ' . $this->db->getConnection()->error);
            return [];
        }
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($res && ($row = $res->fetch_assoc())) {
            $rows[] = $row;
        }
        $stmt->close();
        return $rows;
    }

    /** @return list<array<string, mixed>> newest first */
    public function getHeadHistory(string $departmentId, int $limit = 6): array {
        return $this->fetchRows(
            "SELECT `staff_id`, `staff_name`, `head_type`, `start_date`, `end_date`, `remarks`, `assigned_by`, `created_at`
             FROM `department_head_history` WHERE `department_id` = ?
             ORDER BY `start_date` DESC, `id` DESC LIMIT " . max(1, $limit),
            's',
            [$departmentId]
        );
    }

    /** Working staff whose staff record role is HOD in this department. */
    public function getRoleBasedHeads(string $departmentId): array {
        return $this->fetchRows(
            "SELECT `staff_id`, `staff_name`, `staff_email`, `staff_date_of_join`
             FROM `staff` WHERE `department_id` = ? AND `staff_position` = 'HOD' AND `staff_status` = 'Working'
             ORDER BY `staff_name`",
            's',
            [$departmentId]
        );
    }

    /** @return array<string, mixed>|null */
    public function getStaffContact(string $staffId): ?array {
        $rows = $this->fetchRows(
            "SELECT `staff_id`, `staff_name`, `staff_email`, `staff_pno`, `staff_position`, `staff_type`, `staff_date_of_join`
             FROM `staff` WHERE `staff_id` = ? LIMIT 1",
            's',
            [$staffId]
        );
        return $rows[0] ?? null;
    }

    /**
     * @return array{members: list<array<string,mixed>>, by_status: array<string,int>, by_type: array<string,int>, total: int}
     */
    public function getStaffOverview(string $departmentId, int $limit = 12): array {
        $members = $this->fetchRows(
            "SELECT `staff_id`, `staff_name`, `staff_position`, `staff_type`, `staff_status`, `staff_date_of_join`
             FROM `staff` WHERE `department_id` = ?
             ORDER BY (`staff_status` = 'Working') DESC, (`staff_position` = 'HOD') DESC, `staff_name` ASC
             LIMIT " . max(1, $limit),
            's',
            [$departmentId]
        );
        $byStatus = [];
        foreach ($this->fetchRows(
            "SELECT COALESCE(`staff_status`, 'Unknown') AS k, COUNT(*) AS n FROM `staff` WHERE `department_id` = ? GROUP BY k",
            's',
            [$departmentId]
        ) as $r) {
            $byStatus[(string) $r['k']] = (int) $r['n'];
        }
        $byType = [];
        foreach ($this->fetchRows(
            "SELECT `staff_type` AS k, COUNT(*) AS n FROM `staff` WHERE `department_id` = ? AND `staff_status` = 'Working' GROUP BY k",
            's',
            [$departmentId]
        ) as $r) {
            $byType[(string) $r['k']] = (int) $r['n'];
        }
        return [
            'members' => $members,
            'by_status' => $byStatus,
            'by_type' => $byType,
            'total' => array_sum($byStatus),
        ];
    }

    /**
     * Distinct students by enrolment status, plus per-course breakdown.
     *
     * @return array{totals: array<string,int>, courses: list<array<string,mixed>>}
     */
    public function getStudentOverview(string $departmentId): array {
        $totals = ['following' => 0, 'completed' => 0, 'dropout' => 0, 'long_absent' => 0, 'ever' => 0];
        $row = $this->fetchRows(
            "SELECT COUNT(DISTINCT se.`student_id`) AS ever,
                    COUNT(DISTINCT CASE WHEN se.`student_enroll_status` = 'Following' THEN se.`student_id` END) AS following,
                    COUNT(DISTINCT CASE WHEN se.`student_enroll_status` = 'Completed' THEN se.`student_id` END) AS completed,
                    COUNT(DISTINCT CASE WHEN se.`student_enroll_status` = 'Dropout' THEN se.`student_id` END) AS dropout,
                    COUNT(DISTINCT CASE WHEN se.`student_enroll_status` = 'Long Absent' THEN se.`student_id` END) AS long_absent
             FROM `student_enroll` se
             INNER JOIN `course` c ON c.`course_id` = se.`course_id`
             WHERE c.`department_id` = ?",
            's',
            [$departmentId]
        );
        if ($row) {
            foreach ($totals as $k => $_) {
                $totals[$k] = (int) ($row[0][$k] ?? 0);
            }
        }

        $courses = $this->fetchRows(
            "SELECT c.`course_id`, c.`course_name`, c.`course_nvq_level`,
                    SUM(se.`student_enroll_status` = 'Following') AS following,
                    SUM(se.`student_enroll_status` = 'Completed') AS completed,
                    SUM(se.`student_enroll_status` IN ('Dropout', 'Long Absent')) AS left_count,
                    COUNT(se.`student_id`) AS total
             FROM `course` c
             LEFT JOIN `student_enroll` se ON se.`course_id` = c.`course_id`
             WHERE c.`department_id` = ?
             GROUP BY c.`course_id`, c.`course_name`, c.`course_nvq_level`
             ORDER BY c.`course_nvq_level` DESC, c.`course_name` ASC",
            's',
            [$departmentId]
        );
        return ['totals' => $totals, 'courses' => $courses];
    }

    /** Enrolment history by academic year (newest first). */
    public function getStudentYearHistory(string $departmentId, int $limit = 6): array {
        return $this->fetchRows(
            "SELECT se.`academic_year`,
                    COUNT(*) AS total,
                    SUM(se.`student_enroll_status` = 'Following') AS following,
                    SUM(se.`student_enroll_status` = 'Completed') AS completed,
                    SUM(se.`student_enroll_status` IN ('Dropout', 'Long Absent')) AS left_count
             FROM `student_enroll` se
             INNER JOIN `course` c ON c.`course_id` = se.`course_id`
             WHERE c.`department_id` = ?
             GROUP BY se.`academic_year`
             ORDER BY se.`academic_year` DESC LIMIT " . max(1, $limit),
            's',
            [$departmentId]
        );
    }

    /** Most recent enrolments (currently following). */
    public function getRecentStudents(string $departmentId, int $limit = 10): array {
        return $this->fetchRows(
            "SELECT s.`student_id`, s.`student_fullname`, c.`course_name`, se.`academic_year`, se.`student_enroll_date`
             FROM `student_enroll` se
             INNER JOIN `student` s ON s.`student_id` = se.`student_id`
             INNER JOIN `course` c ON c.`course_id` = se.`course_id`
             WHERE c.`department_id` = ? AND se.`student_enroll_status` = 'Following'
             ORDER BY se.`student_enroll_date` DESC, s.`student_fullname` ASC LIMIT " . max(1, $limit),
            's',
            [$departmentId]
        );
    }
    
    /**
     * Get all departments
     */
    public function getAll() {
        return $this->all('department_name ASC');
    }

    /**
     * Base select with course and staff counts.
     */
    private function selectWithCountsSql() {
        return "SELECT d.*,
                    hs.`staff_name` AS head_name,
                    hs.`staff_position` AS head_position,
                    COALESCE(c.cnt, 0) AS course_count,
                    COALESCE(s.cnt, 0) AS staff_count
                FROM `{$this->table}` d
                LEFT JOIN `staff` hs ON hs.`staff_id` = d.`head_staff_id`
                LEFT JOIN (
                    SELECT `department_id`, COUNT(*) AS cnt
                    FROM `course`
                    GROUP BY `department_id`
                ) c ON c.`department_id` = d.`department_id`
                LEFT JOIN (
                    SELECT `department_id`, COUNT(*) AS cnt
                    FROM `staff`
                    GROUP BY `department_id`
                ) s ON s.`department_id` = d.`department_id`";
    }

    /**
     * @return array{0: string, 1: string, 2: list<string>} WHERE sql, bind types, bind params
     */
    private function buildFilter(string $alias, string $search, $category): array {
        $conds = [];
        $types = '';
        $params = [];
        if ($search !== '') {
            $conds[] = "({$alias}`department_id` LIKE ? OR {$alias}`department_name` LIKE ?)";
            $like = '%' . $search . '%';
            $types .= 'ss';
            $params[] = $like;
            $params[] = $like;
        }
        $category = strtolower(trim((string) $category));
        if (isset(self::categories()[$category])) {
            $conds[] = "{$alias}`department_category` = ?";
            $types .= 's';
            $params[] = $category;
        }
        $where = $conds ? ' WHERE ' . implode(' AND ', $conds) : '';
        return [$where, $types, $params];
    }

    /**
     * Get departments with pagination, optional search and category.
     */
    public function getDepartmentsPage($page = 1, $perPage = 20, $search = '', $category = '') {
        $page = max(1, (int) $page);
        $perPage = max(1, min(100, (int) $perPage));
        $offset = ($page - 1) * $perPage;
        $search = trim((string) $search);

        [$where, $types, $params] = $this->buildFilter('d.', $search, $category);
        $sql = $this->selectWithCountsSql() . $where
            . " ORDER BY d.`department_category` ASC, d.`department_name` ASC LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $types .= 'ii';
        $params[] = $perPage;
        $params[] = $offset;
        $stmt->bind_param($types, ...$params);

        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        $stmt->close();

        return $data;
    }

    /**
     * Get total department count, optionally filtered by search.
     */
    public function getTotalDepartments($search = '', $category = '') {
        $search = trim((string) $search);
        [$where, $types, $params] = $this->buildFilter('', $search, $category);
        if ($where === '') {
            return (int) $this->count();
        }

        $sql = "SELECT COUNT(*) AS total FROM `{$this->table}`" . $where;
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $total = 0;
        if ($result && ($row = $result->fetch_assoc())) {
            $total = (int) ($row['total'] ?? 0);
        }
        $stmt->close();
        return $total;
    }

    /**
     * Get one department with course and staff counts.
     */
    public function getByIdWithCounts($id) {
        $sql = $this->selectWithCountsSql() . " WHERE d.`department_id` = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return $this->getById($id);
        }
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
        $stmt->close();
        return $row;
    }

    /**
     * Departments that have at least one course at the given NVQ level (e.g. '4' or '5').
     *
     * @return list<array<string, mixed>>
     */
    public function getDepartmentsWithNvqCourses(string $nvqLevel): array {
        if (!in_array($nvqLevel, ['4', '5'], true)) {
            return [];
        }

        require_once BASE_PATH . '/models/CourseModel.php';
        (new CourseModel())->ensureCourseStatusColumn();

        $sql = "SELECT DISTINCT d.* FROM `{$this->table}` d
                INNER JOIN `course` c ON c.`department_id` = d.`department_id`
                    AND c.`course_nvq_level` = ?
                    AND c.`course_status` = 'active'
                ORDER BY d.`department_name` ASC";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log('DepartmentModel::getDepartmentsWithNvqCourses prepare failed: ' . $this->db->getConnection()->error);
            return [];
        }
        $stmt->bind_param('s', $nvqLevel);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        $stmt->close();
        return $rows;
    }
    
    /**
     * Get department by ID
     */
    public function getById($id) {
        return $this->find($id);
    }
    
    /**
     * Create new department
     */
    public function createDepartment($data, &$sqlError = null) {
        return $this->create($data, $sqlError);
    }
    
    /**
     * Update department
     */
    public function updateDepartment($id, $data) {
        return $this->update($id, $data);
    }
    
    /**
     * Delete department
     */
    public function deleteDepartment($id) {
        return $this->delete($id);
    }
    
    /**
     * Check if department exists
     */
    public function exists($id) {
        $dept = $this->find($id);
        return $dept !== null;
    }
}

