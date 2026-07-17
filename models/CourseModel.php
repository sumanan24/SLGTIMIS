<?php
/**
 * Course Model
 */

class CourseModel extends Model {
    protected $table = 'course';
    protected $courseVersionTable = 'course_version';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_DRAFT = 'draft';
    public const STATUS_DEACTIVATED = 'deactivated';
    
    protected function getPrimaryKey() {
        return 'course_id';
    }

    /**
     * @return list<string>
     */
    public static function validStatuses(): array {
        return [
            self::STATUS_ACTIVE,
            self::STATUS_DRAFT,
            self::STATUS_DEACTIVATED,
        ];
    }

    public static function statusLabel(string $status): string {
        $labels = [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_DEACTIVATED => 'Deactivated',
        ];

        return $labels[$status] ?? ucfirst($status);
    }

    public function normalizeStatus(?string $status, string $default = self::STATUS_DRAFT): string {
        $status = strtolower(trim((string) $status));
        if ($status === '') {
            return $default;
        }

        return in_array($status, self::validStatuses(), true) ? $status : $default;
    }

    /**
     * Ensure course_status column exists (active, draft, deactivated).
     */
    public function ensureCourseStatusColumn(): void {
        $conn = $this->db->getConnection();
        $result = $conn->query("SHOW COLUMNS FROM `{$this->table}` LIKE 'course_status'");
        if ($result && $result->num_rows > 0) {
            return;
        }

        $sql = "ALTER TABLE `{$this->table}`
                ADD COLUMN `course_status` ENUM('active','draft','deactivated') NOT NULL DEFAULT 'active'
                COMMENT 'Only active courses are visible for student enrollment'
                AFTER `course_institute_training`";
        if (!$conn->query($sql)) {
            error_log('CourseModel::ensureCourseStatusColumn failed: ' . $conn->error);
        }
    }

    public function isActiveForStudents(string $courseId): bool {
        $this->ensureCourseStatusColumn();
        $course = $this->find($courseId);
        if (!$course) {
            return false;
        }

        return ($course['course_status'] ?? self::STATUS_ACTIVE) === self::STATUS_ACTIVE;
    }
    
    /**
     * Build WHERE clause and bind params for course list queries.
     *
     * @return array{sql: string, params: array<int, mixed>, types: string}
     */
    private function buildCourseFilterClause(array $filters = [], string $alias = 'c') {
        $this->ensureCourseStatusColumn();

        $sql = '';
        $params = [];
        $types = '';

        if (!empty($filters['department_id'])) {
            $sql .= " AND {$alias}.`department_id` = ?";
            $params[] = $filters['department_id'];
            $types .= 's';
        }

        if (!empty($filters['nvq_level'])) {
            $sql .= " AND {$alias}.`course_nvq_level` = ?";
            $params[] = $filters['nvq_level'];
            $types .= 's';
        }

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $sql .= " AND ({$alias}.`course_name` LIKE ? OR {$alias}.`course_id` LIKE ?)";
            $params[] = $search;
            $params[] = $search;
            $types .= 'ss';
        }

        if (!empty($filters['course_status'])) {
            $status = $this->normalizeStatus((string) $filters['course_status'], '');
            if ($status !== '') {
                $sql .= " AND {$alias}.`course_status` = ?";
                $params[] = $status;
                $types .= 's';
            }
        }

        if (!empty($filters['active_only'])) {
            $sql .= " AND {$alias}.`course_status` = ?";
            $params[] = self::STATUS_ACTIVE;
            $types .= 's';
        }

        return [
            'sql' => $sql,
            'params' => $params,
            'types' => $types,
        ];
    }

    /**
     * Get courses with department info
     */
    public function getCoursesWithDepartment($filters = []) {
        $filterClause = $this->buildCourseFilterClause($filters);

        $sql = "SELECT c.*, d.`department_name`
                FROM `{$this->table}` c
                LEFT JOIN `department` d ON c.`department_id` = d.`department_id`
                WHERE 1=1{$filterClause['sql']}
                ORDER BY c.`course_name`";

        $params = $filterClause['params'];
        $types = $filterClause['types'];

        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                error_log("SQL Error in getCoursesWithDepartment: " . $this->db->error);
                return [];
            }
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->db->query($sql);
        }

        $data = [];

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }

        return $data;
    }

    /**
     * Get paginated courses with department info
     */
    public function getCoursesWithDepartmentPage(array $filters = [], $page = 1, $perPage = 20) {
        $page = max(1, (int) $page);
        $perPage = max(1, min(100, (int) $perPage));
        $offset = ($page - 1) * $perPage;
        $filterClause = $this->buildCourseFilterClause($filters);

        $sql = "SELECT c.*, d.`department_name`
                FROM `{$this->table}` c
                LEFT JOIN `department` d ON c.`department_id` = d.`department_id`
                WHERE 1=1{$filterClause['sql']}
                ORDER BY c.`course_name`
                LIMIT {$perPage} OFFSET {$offset}";

        $params = $filterClause['params'];
        $types = $filterClause['types'];

        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                error_log("SQL Error in getCoursesWithDepartmentPage: " . $this->db->error);
                return [];
            }
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->db->query($sql);
        }

        $data = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }

        return $data;
    }

    /**
     * Count courses matching filters
     */
    public function getTotalCoursesWithDepartment(array $filters = []) {
        $filterClause = $this->buildCourseFilterClause($filters);

        $sql = "SELECT COUNT(*) AS total
                FROM `{$this->table}` c
                WHERE 1=1{$filterClause['sql']}";

        $params = $filterClause['params'];
        $types = $filterClause['types'];

        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                return 0;
            }
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->db->query($sql);
        }

        if ($result && ($row = $result->fetch_assoc())) {
            return (int) ($row['total'] ?? 0);
        }

        return 0;
    }
    
    /**
     * Get all courses
     */
    public function getAll() {
        return $this->getCoursesWithDepartment();
    }
    
    /**
     * Get course by ID
     */
    public function getById($id) {
        $course = $this->find($id);
        if ($course) {
            // Get department name
            $sql = "SELECT d.`department_name` 
                    FROM `department` d 
                    WHERE d.`department_id` = ?";
            $stmt = $this->db->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("s", $course['department_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result && $result->num_rows > 0) {
                    $dept = $result->fetch_assoc();
                    $course['department_name'] = $dept['department_name'];
                }
            }
        }
        return $course;
    }
    
    /**
     * Create new course and ensure version 1 exists in course_version.
     */
    public function createCourse($data) {
        $this->ensureCourseStatusColumn();
        if (!isset($data['course_status'])) {
            $data['course_status'] = self::STATUS_DRAFT;
        } else {
            $data['course_status'] = $this->normalizeStatus($data['course_status']);
        }

        $ok = $this->create($data);
        if ($ok && !empty($data['course_id'])) {
            $this->ensureCourseVersionTable();
            $this->getLatestVersionForCourse($data['course_id']); // creates version 1 if missing
        }
        return $ok;
    }
    
    /**
     * Update course
     */
    public function updateCourse($id, $data) {
        $this->ensureCourseStatusColumn();
        if (isset($data['course_status'])) {
            $data['course_status'] = $this->normalizeStatus($data['course_status']);
        }

        return $this->update($id, $data);
    }
    
    /**
     * Delete course
     */
    public function deleteCourse($id) {
        return $this->delete($id);
    }
    
    /**
     * Check if course exists
     */
    public function exists($id) {
        $course = $this->find($id);
        return $course !== null;
    }

    /**
     * Ensure course_version table exists for tracking course versions.
     * Columns: id (PK), course_id, version_no, is_active, created_at.
     */
    public function ensureCourseVersionTable() {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->courseVersionTable}` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `course_id` VARCHAR(11) NOT NULL,
                    `version_no` INT(11) NOT NULL,
                    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `idx_course` (`course_id`),
                    UNIQUE KEY `uniq_course_version` (`course_id`, `version_no`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $this->db->query($sql);
    }

    /**
     * Get latest version number for a course.
     * If no version exists, return 0 (default).
     */
    public function getLatestVersionForCourse($courseId) {
        $this->ensureCourseVersionTable();

        $sql = "SELECT MAX(version_no) AS max_version
                FROM `{$this->courseVersionTable}`
                WHERE course_id = ?";
        $stmt = $this->db->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("s", $courseId);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && ($row = $res->fetch_assoc()) && $row['max_version'] !== null) {
                return (int)$row['max_version'];
            }
        }

        return 0;
    }

    /**
     * Get all versions for a course (version_no, is_active, created_at).
     */
    public function getVersionsForCourse($courseId) {
        $this->ensureCourseVersionTable();
        $sql = "SELECT id, course_id, version_no, is_active, created_at
                FROM `{$this->courseVersionTable}`
                WHERE course_id = ?
                ORDER BY version_no DESC";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param("s", $courseId);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        return $data;
    }

    /**
     * Create a new version for a course (increments version number).
     * Returns the new version number or 0 on failure.
     */
    public function createNewVersion($courseId) {
        $this->ensureCourseVersionTable();
        $next = $this->getLatestVersionForCourse($courseId) + 1;
        $sql = "INSERT INTO `{$this->courseVersionTable}` (course_id, version_no, is_active)
                VALUES (?, ?, 1)";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param("si", $courseId, $next);
        return $stmt->execute() ? $next : 0;
    }

    /**
     * Remove a course version by course_id and version_no.
     * Returns true if deleted.
     */
    public function deleteVersion($courseId, $versionNo) {
        $this->ensureCourseVersionTable();
        $sql = "DELETE FROM `{$this->courseVersionTable}` WHERE course_id = ? AND version_no = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("si", $courseId, $versionNo);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }
}

