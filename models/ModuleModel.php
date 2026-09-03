<?php
/**
 * Module Model - course modules (table `module`: course_id, course_version, module_id, module_name, credit, semester)
 * Uniqueness: (course_id, course_version, module_id). course_version = 0 when no versions.
 */

class ModuleModel extends Model {
    protected $table = 'module';

    protected function getPrimaryKey() {
        return 'module_id';
    }

    /**
     * Ensure module table has course_version column (INT, default 0).
     */
    public function ensureModuleVersionColumn() {
        $conn = $this->db->getConnection();
        $res = $conn->query("SHOW COLUMNS FROM `{$this->table}` LIKE 'course_version'");
        if ($res && $res->num_rows > 0) {
            return;
        }
        $conn->query("ALTER TABLE `{$this->table}` ADD COLUMN `course_version` INT(11) NOT NULL DEFAULT 0 AFTER `course_id`");
    }

    /**
     * Ensure module table has credit column (DECIMAL for credit hours, e.g. 1.5, 2).
     */
    public function ensureModuleCreditColumn() {
        $this->ensureModuleVersionColumn();
        $conn = $this->db->getConnection();
        $res = $conn->query("SHOW COLUMNS FROM `{$this->table}` LIKE 'credit'");
        if ($res && $res->num_rows > 0) {
            return;
        }
        $conn->query("ALTER TABLE `{$this->table}` ADD COLUMN `credit` DECIMAL(4,2) NULL DEFAULT NULL AFTER `module_name`");
    }

    /**
     * Semester number (e.g. 1, 2) for organising modules — optional.
     */
    public function ensureModuleSemesterColumn() {
        $this->ensureModuleCreditColumn();
        $conn = $this->db->getConnection();
        $res = $conn->query("SHOW COLUMNS FROM `{$this->table}` LIKE 'semester'");
        if (!$res || $res->num_rows === 0) {
            $conn->query("ALTER TABLE `{$this->table}` ADD COLUMN `semester` TINYINT UNSIGNED NULL DEFAULT NULL COMMENT 'Academic semester (e.g. 1, 2)' AFTER `credit`");
        }
        $this->ensureUnusedModuleColumnsOptional();
    }

    /**
     * Columns the SIS create/import forms actually write.
     *
     * @return list<string>
     */
    private function managedModuleColumns(): array {
        return ['course_id', 'course_version', 'module_id', 'module_name', 'credit', 'semester'];
    }

    /**
     * Leftover schema fields (e.g. module_aim, module_learning_hours) are unused by
     * SIS imports/forms — allow NULL so inserts succeed without them.
     */
    public function ensureUnusedModuleColumnsOptional() {
        $conn = $this->db->getConnection();
        $res = $conn->query("SHOW COLUMNS FROM `{$this->table}`");
        if (!$res) {
            return;
        }
        $managed = array_flip($this->managedModuleColumns());
        while ($row = $res->fetch_assoc()) {
            $field = (string) ($row['Field'] ?? '');
            if ($field === '' || isset($managed[$field])) {
                continue;
            }
            $extra = strtolower((string) ($row['Extra'] ?? ''));
            if (strpos($extra, 'auto_increment') !== false) {
                continue;
            }
            $allowsNull = strtoupper((string) ($row['Null'] ?? '')) === 'YES';
            $hasDefault = array_key_exists('Default', $row) && $row['Default'] !== null;
            if ($allowsNull || $hasDefault) {
                continue;
            }
            $type = trim((string) ($row['Type'] ?? ''));
            if ($type === '') {
                continue;
            }
            $safeField = str_replace('`', '', $field);
            $conn->query("ALTER TABLE `{$this->table}` MODIFY COLUMN `{$safeField}` {$type} NULL DEFAULT NULL");
        }
    }

    /**
     * @deprecated Use ensureUnusedModuleColumnsOptional()
     */
    public function ensureModuleAimOptional() {
        $this->ensureUnusedModuleColumnsOptional();
    }

    /**
     * Fallback values when leftover NOT NULL columns cannot be altered.
     *
     * @param array<string, mixed> $col
     * @return mixed
     */
    private function defaultForUnusedModuleColumn(array $col) {
        $type = strtolower((string) ($col['Type'] ?? ''));
        if (preg_match('/^(tinyint|smallint|mediumint|int|bigint|decimal|float|double|bit)/', $type)) {
            return 0;
        }
        if (preg_match("/^enum\\s*\\((.+)\\)/", $type, $m) && preg_match("/'((?:\\\\'|[^'])*)'/", $m[1], $ev)) {
            return stripcslashes($ev[1]);
        }
        return '';
    }

    /**
     * @param array<string, mixed> $filtered
     */
    private function fillUnusedModuleColumnDefaults(array &$filtered): void {
        $conn = $this->db->getConnection();
        $res = $conn->query("SHOW COLUMNS FROM `{$this->table}`");
        if (!$res) {
            return;
        }
        $managed = array_flip($this->managedModuleColumns());
        while ($row = $res->fetch_assoc()) {
            $field = (string) ($row['Field'] ?? '');
            if ($field === '' || isset($managed[$field]) || array_key_exists($field, $filtered)) {
                continue;
            }
            $extra = strtolower((string) ($row['Extra'] ?? ''));
            if (strpos($extra, 'auto_increment') !== false) {
                continue;
            }
            $allowsNull = strtoupper((string) ($row['Null'] ?? '')) === 'YES';
            $hasDefault = array_key_exists('Default', $row) && $row['Default'] !== null;
            if ($allowsNull || $hasDefault) {
                continue;
            }
            $filtered[$field] = $this->defaultForUnusedModuleColumn($row);
        }
    }

    /**
     * Get module by course_id and module_id (and optional course_version for when column exists)
     */
    public function getByCourseAndModule($courseId, $moduleId, $courseVersion = null) {
        $this->ensureModuleVersionColumn();
        $this->ensureModuleCreditColumn();
        $this->ensureModuleSemesterColumn();
        $sql = "SELECT m.*, c.course_name 
                FROM `{$this->table}` m 
                LEFT JOIN `course` c ON c.course_id = m.course_id 
                WHERE m.course_id = ? AND m.module_id = ?";
        $params = [$courseId, $moduleId];
        $types = 'ss';
        if ($courseVersion !== null) {
            $sql .= " AND m.course_version = ?";
            $params[] = (int)$courseVersion;
            $types .= 'i';
        } else {
            $sql .= " AND (m.course_version = 0 OR m.course_version IS NULL)";
        }
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_assoc() : null;
    }

    /**
     * Get all modules with course name, optional filter by course_id and course_version
     */
    public function getAllWithCourse($courseId = null, $courseVersion = null) {
        $filters = [];
        if (!empty($courseId)) {
            $filters['course_id'] = $courseId;
        }
        if ($courseVersion !== null && $courseVersion !== '') {
            $filters['course_version'] = (int) $courseVersion;
        }

        return $this->getAllWithCourseFiltered($filters);
    }

    /**
     * Get modules with course/department info and optional filters.
     *
     * @param array{department_id?: string, course_id?: string, course_version?: int} $filters
     * @return list<array<string, mixed>>
     */
    public function getAllWithCourseFiltered(array $filters = []): array {
        $this->ensureModuleVersionColumn();
        $this->ensureModuleCreditColumn();
        $this->ensureModuleSemesterColumn();

        $sql = "SELECT m.*, c.course_name, c.department_id, d.department_name
                FROM `{$this->table}` m
                INNER JOIN `course` c ON c.course_id = m.course_id
                LEFT JOIN `department` d ON d.department_id = c.department_id
                WHERE 1=1";

        $params = [];
        $types = '';

        if (!empty($filters['department_id'])) {
            $sql .= " AND c.department_id = ?";
            $params[] = trim((string) $filters['department_id']);
            $types .= 's';
        }

        if (!empty($filters['course_id'])) {
            $sql .= " AND m.course_id = ?";
            $params[] = trim((string) $filters['course_id']);
            $types .= 's';
        }

        if (array_key_exists('course_version', $filters) && $filters['course_version'] !== null && $filters['course_version'] !== '') {
            $sql .= " AND m.course_version = ?";
            $params[] = (int) $filters['course_version'];
            $types .= 'i';
        }

        $sql .= " ORDER BY d.department_name, c.course_name, m.course_version, m.semester, m.module_name, m.module_id";

        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
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
     * Get modules for a department (via course.department_id), optional filter by course_id.
     *
     * @return list<array<string, mixed>>
     */
    public function getAllWithCourseByDepartment(string $departmentId, ?string $courseId = null, $courseVersion = null): array {
        $filters = ['department_id' => $departmentId];
        if ($courseId !== null && trim($courseId) !== '') {
            $filters['course_id'] = trim($courseId);
        }
        if ($courseVersion !== null && $courseVersion !== '') {
            $filters['course_version'] = (int) $courseVersion;
        }

        return $this->getAllWithCourseFiltered($filters);
    }

    /**
     * Modules for a course in a given semester (uses `module.semester`).
     *
     * @return list<array<string, mixed>>
     */
    public function getByCourseAndSemester(string $courseId, int $semester, ?int $courseVersion = null): array {
        $this->ensureModuleVersionColumn();
        $this->ensureModuleCreditColumn();
        $this->ensureModuleSemesterColumn();
        $sql = "SELECT m.*, c.course_name
                FROM `{$this->table}` m
                LEFT JOIN `course` c ON c.course_id = m.course_id
                WHERE m.course_id = ? AND m.semester = ?";
        if ($courseVersion !== null) {
            $sql .= " AND m.course_version = ?";
        }
        $sql .= " ORDER BY m.module_name ASC, m.module_id ASC";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }
        if ($courseVersion !== null) {
            $stmt->bind_param('sii', $courseId, $semester, $courseVersion);
        } else {
            $stmt->bind_param('si', $courseId, $semester);
        }
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
     * Create a module (course_id, course_version, module_id, module_name, credit optional)
     */
    public function createModule($data, &$sqlError = null) {
        $this->ensureModuleVersionColumn();
        $this->ensureModuleCreditColumn();
        $this->ensureModuleSemesterColumn();
        $this->ensureUnusedModuleColumnsOptional();
        $columns = ['course_id', 'course_version', 'module_id', 'module_name', 'credit', 'semester'];
        $filtered = [];
        foreach ($columns as $col) {
            if (!array_key_exists($col, $data)) {
                if ($col === 'course_version') {
                    $filtered['course_version'] = 0;
                }
                continue;
            }
            if ($col === 'course_version') {
                $filtered[$col] = (int)$data[$col];
            } elseif ($col === 'credit') {
                $filtered[$col] = $data[$col] === '' || $data[$col] === null ? null : (float)$data[$col];
            } elseif ($col === 'semester') {
                $filtered[$col] = $data[$col] === '' || $data[$col] === null ? null : (int)$data[$col];
            } else {
                $filtered[$col] = $data[$col];
            }
        }
        if (!isset($filtered['course_version'])) {
            $filtered['course_version'] = 0;
        }
        if (empty($filtered['course_id']) || $filtered['module_id'] === '' || $filtered['module_name'] === '') {
            $sqlError = 'Missing course_id, course_version, module_id or module_name';
            return false;
        }
        $this->fillUnusedModuleColumnDefaults($filtered);
        return $this->create($filtered, $sqlError);
    }

    /**
     * Update module by course_id, course_version and module_id. Allowed fields: module_name, credit, semester.
     */
    public function updateModule($courseId, $moduleId, $courseVersion, $data, &$sqlError = null) {
        $this->ensureModuleVersionColumn();
        $this->ensureModuleCreditColumn();
        $this->ensureModuleSemesterColumn();
        $allowed = ['module_name', 'credit', 'semester'];
        $set = [];
        $values = [];
        $types = '';
        foreach ($data as $col => $val) {
            if (!in_array($col, $allowed, true)) {
                continue;
            }
            $set[] = "`$col` = ?";
            if ($col === 'credit') {
                $types .= 's';
                $values[] = $val === '' || $val === null ? null : (float)$val;
            } elseif ($col === 'semester') {
                $types .= 's';
                $values[] = $val === '' || $val === null ? null : (string)(int)$val;
            } else {
                $types .= 's';
                $values[] = $val;
            }
        }
        if (empty($set)) {
            return false;
        }
        $courseVersion = $courseVersion === null ? 0 : (int)$courseVersion;
        $sql = "UPDATE `{$this->table}` SET " . implode(', ', $set) . " WHERE course_id = ? AND module_id = ? AND course_version = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            $sqlError = $this->db->getConnection()->error ?? 'Prepare failed';
            return false;
        }
        $types .= 'ssi';
        $values[] = $courseId;
        $values[] = $moduleId;
        $values[] = $courseVersion;
        $refs = [];
        foreach ($values as $k => $v) {
            $refs[$k] = &$values[$k];
        }
        call_user_func_array([$stmt, 'bind_param'], array_merge([$types], $refs));
        if (!$stmt->execute()) {
            $sqlError = $stmt->error ?? 'Execute failed';
            return false;
        }
        return true;
    }

    /**
     * Delete module by course_id, course_version and module_id
     */
    public function deleteByCourseAndModule($courseId, $moduleId, $courseVersion = null) {
        $this->ensureModuleVersionColumn();
        $this->ensureModuleSemesterColumn();
        $sql = "DELETE FROM `{$this->table}` WHERE course_id = ? AND module_id = ?";
        $params = [$courseId, $moduleId];
        $types = 'ss';
        if ($courseVersion !== null) {
            $sql .= " AND course_version = ?";
            $params[] = (int)$courseVersion;
            $types .= 'i';
        } else {
            $sql .= " AND (course_version = 0 OR course_version IS NULL)";
        }
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    /**
     * Check if (course_id, course_version, module_id) exists
     */
    public function exists($courseId, $moduleId, $courseVersion = 0) {
        $this->ensureModuleVersionColumn();
        $this->ensureModuleSemesterColumn();
        $v = $courseVersion === null ? 0 : (int)$courseVersion;
        return $this->getByCourseAndModule($courseId, $moduleId, $v) !== null;
    }
}
