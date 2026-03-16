<?php
/**
 * Module Model - course modules (table `module`: course_id, course_version, module_id, module_name)
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
     * Get module by course_id and module_id (and optional course_version for when column exists)
     */
    public function getByCourseAndModule($courseId, $moduleId, $courseVersion = null) {
        $this->ensureModuleVersionColumn();
        $this->ensureModuleCreditColumn();
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
        $this->ensureModuleVersionColumn();
        $this->ensureModuleCreditColumn();
        $sql = "SELECT m.*, c.course_name 
                FROM `{$this->table}` m 
                LEFT JOIN `course` c ON c.course_id = m.course_id 
                WHERE 1=1";
        $params = [];
        $types = '';
        if (!empty($courseId)) {
            $sql .= " AND m.course_id = ?";
            $params[] = $courseId;
            $types .= 's';
        }
        if ($courseVersion !== null && $courseVersion !== '') {
            $sql .= " AND m.course_version = ?";
            $params[] = (int)$courseVersion;
            $types .= 'i';
        }
        $sql .= " ORDER BY c.course_name, m.course_version, m.module_name, m.module_id";
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
     * Create a module (course_id, course_version, module_id, module_name, credit optional)
     */
    public function createModule($data, &$sqlError = null) {
        $this->ensureModuleVersionColumn();
        $this->ensureModuleCreditColumn();
        $columns = ['course_id', 'course_version', 'module_id', 'module_name', 'credit'];
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
        return $this->create($filtered, $sqlError);
    }

    /**
     * Update module by course_id, course_version and module_id. Allowed fields: module_name, credit.
     */
    public function updateModule($courseId, $moduleId, $courseVersion, $data, &$sqlError = null) {
        $this->ensureModuleVersionColumn();
        $this->ensureModuleCreditColumn();
        $allowed = ['module_name', 'credit'];
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
        $v = $courseVersion === null ? 0 : (int)$courseVersion;
        return $this->getByCourseAndModule($courseId, $moduleId, $v) !== null;
    }
}
