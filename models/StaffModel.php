<?php
/**
 * Staff Model
 */

class StaffModel extends Model {
    protected $table = 'staff';
    private $lastError = '';

    public function __construct() {
        parent::__construct();
        $this->ensureFingerMachineColumn();
        $this->ensureProfileAppointmentColumns();
    }
    
    protected function getPrimaryKey() {
        return 'staff_id';
    }

    private function ensureFingerMachineColumn() {
        try {
            $result = $this->db->query("SHOW COLUMNS FROM `staff` LIKE 'finger_machine_no'");
            if ($result && $result->num_rows === 0) {
                $this->db->query("ALTER TABLE `staff`
                    ADD COLUMN `finger_machine_no` VARCHAR(50) NULL DEFAULT NULL
                    COMMENT 'Fingerprint machine employee number'
                    AFTER `staff_id`");
            }
            $idx = $this->db->query("SHOW INDEX FROM `staff` WHERE Key_name = 'uq_staff_finger_machine_no'");
            if ($idx && $idx->num_rows === 0) {
                $this->db->query("ALTER TABLE `staff` ADD UNIQUE KEY `uq_staff_finger_machine_no` (`finger_machine_no`)");
            }
        } catch (Throwable $e) {
            error_log('StaffModel::ensureFingerMachineColumn: ' . $e->getMessage());
        }
    }

    private function ensureProfileAppointmentColumns() {
        $columns = [
            'staff_ininame' => "ALTER TABLE `staff`
                ADD COLUMN `staff_ininame` VARCHAR(100) NULL DEFAULT NULL
                COMMENT 'Name with initials'
                AFTER `staff_name`",
            'appoint_type' => "ALTER TABLE `staff`
                ADD COLUMN `appoint_type` VARCHAR(20) NOT NULL DEFAULT 'none'
                COMMENT 'none, cover_up, acting'
                AFTER `staff_type`",
            'appoint_position' => "ALTER TABLE `staff`
                ADD COLUMN `appoint_position` VARCHAR(100) NULL DEFAULT NULL
                COMMENT 'Cover-up or Acting position'
                AFTER `appoint_type`",
            'appoint_department_id' => "ALTER TABLE `staff`
                ADD COLUMN `appoint_department_id` VARCHAR(6) NULL DEFAULT NULL
                COMMENT 'Department for Cover-up or Acting'
                AFTER `appoint_position`",
        ];
        try {
            foreach ($columns as $name => $sql) {
                $result = $this->db->query("SHOW COLUMNS FROM `staff` LIKE '{$name}'");
                if ($result && $result->num_rows === 0) {
                    $this->db->query($sql);
                }
            }
        } catch (Throwable $e) {
            error_log('StaffModel::ensureProfileAppointmentColumns: ' . $e->getMessage());
        }
    }

    /**
     * One permanent post per person. Cover-up / Acting only for Permanent staff.
     */
    public static function normalizeAppointment($staffType, $primaryPosition, $appointType, $appointPosition, $appointDepartmentId) {
        $staffType = trim((string) $staffType);
        $primaryPosition = trim((string) $primaryPosition);
        $appointType = strtolower(trim((string) $appointType));
        $appointPosition = trim((string) $appointPosition);
        $appointDepartmentId = trim((string) $appointDepartmentId);
        if (!in_array($appointType, ['none', 'cover_up', 'acting'], true)) {
            $appointType = 'none';
        }

        if ($appointType === 'none') {
            return [
                'ok' => true,
                'error' => null,
                'appoint_type' => 'none',
                'appoint_position' => null,
                'appoint_department_id' => null,
            ];
        }

        if ($staffType !== 'Permanent') {
            return [
                'ok' => false,
                'error' => 'Only Permanent staff can be assigned Cover-up or Acting duties.',
                'appoint_type' => 'none',
                'appoint_position' => null,
                'appoint_department_id' => null,
            ];
        }

        if ($appointPosition === '') {
            return [
                'ok' => false,
                'error' => 'Select the Cover-up or Acting position.',
                'appoint_type' => $appointType,
                'appoint_position' => null,
                'appoint_department_id' => $appointDepartmentId !== '' ? $appointDepartmentId : null,
            ];
        }

        if ($primaryPosition !== '' && strcasecmp($appointPosition, $primaryPosition) === 0) {
            return [
                'ok' => false,
                'error' => 'A person cannot hold two permanent positions. Cover-up / Acting must be a different post.',
                'appoint_type' => $appointType,
                'appoint_position' => $appointPosition,
                'appoint_department_id' => $appointDepartmentId !== '' ? $appointDepartmentId : null,
            ];
        }

        return [
            'ok' => true,
            'error' => null,
            'appoint_type' => $appointType,
            'appoint_position' => $appointPosition,
            'appoint_department_id' => $appointDepartmentId !== '' ? $appointDepartmentId : null,
        ];
    }

    public function fingerMachineExists($number, $exceptStaffId = null) {
        $number = trim((string) $number);
        if ($number === '') {
            return false;
        }
        if ($exceptStaffId) {
            $stmt = $this->db->prepare("SELECT 1 FROM `staff` WHERE `finger_machine_no` = ? AND `staff_id` <> ? LIMIT 1");
            $stmt->bind_param('ss', $number, $exceptStaffId);
        } else {
            $stmt = $this->db->prepare("SELECT 1 FROM `staff` WHERE `finger_machine_no` = ? LIMIT 1");
            $stmt->bind_param('s', $number);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        return $result && $result->num_rows > 0;
    }
    
    /**
     * Get staff with department info
     */
    public function getStaffWithDepartment($page = 1, $perPage = 20, $search = '', $departmentId = '') {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT s.*, d.department_name 
                FROM `{$this->table}` s 
                LEFT JOIN `department` d ON s.department_id = d.department_id";
        
        $conditions = [];
        $params = [];
        $types = '';
        
        if (!empty($departmentId)) {
            $conditions[] = "s.department_id = ?";
            $params[] = $departmentId;
            $types .= 's';
        }
        
        if (!empty($search)) {
            $searchTerm = '%' . $search . '%';
            $searchConditions = [];
            $searchConditions[] = "s.staff_name LIKE ?";
            $searchConditions[] = "s.staff_ininame LIKE ?";
            $searchConditions[] = "s.staff_id LIKE ?";
            $searchConditions[] = "s.staff_email LIKE ?";
            $searchConditions[] = "s.staff_nic LIKE ?";
            $searchConditions[] = "s.finger_machine_no LIKE ?";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            $types .= 'ssssss';
            $conditions[] = "(" . implode(" OR ", $searchConditions) . ")";
        }
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $sql .= " ORDER BY s.staff_name, s.staff_id LIMIT $perPage OFFSET $offset";
        
        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
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
     * Get total count of staff
     */
    public function getTotalStaff($search = '', $departmentId = '') {
        $sql = "SELECT COUNT(*) as total FROM `{$this->table}`";
        
        $conditions = [];
        $params = [];
        $types = '';
        
        if (!empty($departmentId)) {
            $conditions[] = "department_id = ?";
            $params[] = $departmentId;
            $types .= 's';
        }
        
        if (!empty($search)) {
            $searchTerm = '%' . $search . '%';
            $searchConditions = [];
            $searchConditions[] = "staff_name LIKE ?";
            $searchConditions[] = "staff_ininame LIKE ?";
            $searchConditions[] = "staff_id LIKE ?";
            $searchConditions[] = "staff_email LIKE ?";
            $searchConditions[] = "staff_nic LIKE ?";
            $searchConditions[] = "finger_machine_no LIKE ?";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            $types .= 'ssssss';
            $conditions[] = "(" . implode(" OR ", $searchConditions) . ")";
        }
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        
        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->db->query($sql);
        }
        
        $row = $result->fetch_assoc();
        return $row['total'];
    }
    
    /**
     * Get staff by ID with department
     */
    public function getById($id) {
        $sql = "SELECT s.*, d.department_name 
                FROM `{$this->table}` s 
                LEFT JOIN `department` d ON s.department_id = d.department_id
                WHERE s.staff_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Create new staff
     * @param array $data
     * @param string|null $sqlError Set to MySQL error on failure
     * @return int|false
     */
    public function createStaff($data, &$sqlError = null) {
        return $this->create($data, $sqlError);
    }
    
    /**
     * Update staff
     */
    public function updateStaff($id, $data) {
        return $this->update($id, $data);
    }
    
    /**
     * Delete staff
     */
    public function deleteStaff($id) {
        return $this->delete($id);
    }
    
    /**
     * All staff for navbar assignment (ordered by name)
     */
    public function getAllForNavAssign(): array {
        $sql = "SELECT s.`staff_id`, s.`staff_name`, s.`department_id`, s.`staff_position`, d.`department_name`
                FROM `{$this->table}` s
                LEFT JOIN `department` d ON d.`department_id` = s.`department_id`
                ORDER BY s.`staff_name` ASC, s.`staff_id` ASC";
        $result = $this->db->query($sql);
        if (!$result) {
            return [];
        }
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Check if staff exists
     */
    public function exists($id) {
        $staff = $this->find($id);
        return $staff !== null;
    }

    public function getLastError() {
        return $this->lastError;
    }

    /**
     * Change staff_id and keep login in sync (user.user_name = staff.staff_id).
     */
    public function renameStaffId($oldId, $newId) {
        $this->lastError = '';
        $oldId = trim((string) $oldId);
        $newId = trim((string) $newId);
        if ($oldId === '' || $newId === '') {
            $this->lastError = 'Staff ID is required.';
            return false;
        }
        if ($oldId === $newId) {
            return true;
        }
        if (strlen($newId) > 64) {
            $this->lastError = 'Staff ID must be 64 characters or fewer.';
            return false;
        }
        if ($this->exists($newId)) {
            $this->lastError = 'That Staff ID is already in use.';
            return false;
        }

        $userCheck = $this->db->prepare("SELECT `user_id` FROM `user` WHERE `user_name` = ? LIMIT 1");
        if ($userCheck) {
            $userCheck->bind_param('s', $newId);
            $userCheck->execute();
            if ($userCheck->get_result()->fetch_assoc()) {
                $this->lastError = 'That username is already used by a login account.';
                return false;
            }
        }

        $this->db->begin_transaction();
        try {
            $this->db->query('SET FOREIGN_KEY_CHECKS=0');

            $staffStmt = $this->db->prepare("UPDATE `{$this->table}` SET `staff_id` = ? WHERE `staff_id` = ?");
            if (!$staffStmt) {
                throw new Exception($this->db->getConnection()->error ?: 'Failed to prepare staff ID update.');
            }
            $staffStmt->bind_param('ss', $newId, $oldId);
            if (!$staffStmt->execute()) {
                throw new Exception($staffStmt->error ?: 'Failed to update staff ID.');
            }

            $schema = DB_NAME;
            $tablesStmt = $this->db->prepare(
                "SELECT c.`TABLE_NAME`
                 FROM information_schema.`COLUMNS` c
                 INNER JOIN information_schema.`TABLES` t
                    ON t.`TABLE_SCHEMA` = c.`TABLE_SCHEMA` AND t.`TABLE_NAME` = c.`TABLE_NAME`
                 WHERE c.`TABLE_SCHEMA` = ?
                   AND c.`COLUMN_NAME` = 'staff_id'
                   AND c.`TABLE_NAME` <> 'staff'
                   AND t.`TABLE_TYPE` = 'BASE TABLE'"
            );
            if ($tablesStmt) {
                $tablesStmt->bind_param('s', $schema);
                $tablesStmt->execute();
                $tables = $tablesStmt->get_result();
                while ($row = $tables->fetch_assoc()) {
                    $table = (string) ($row['TABLE_NAME'] ?? '');
                    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
                        continue;
                    }
                    $rel = $this->db->prepare("UPDATE `{$table}` SET `staff_id` = ? WHERE `staff_id` = ?");
                    if ($rel) {
                        $rel->bind_param('ss', $newId, $oldId);
                        $rel->execute();
                    }
                }
            }

            $userStmt = $this->db->prepare("UPDATE `user` SET `user_name` = ? WHERE `user_name` = ?");
            if ($userStmt) {
                $userStmt->bind_param('ss', $newId, $oldId);
                $userStmt->execute();
            }

            $loginAttempts = $this->db->query("SHOW TABLES LIKE 'login_attempts'");
            if ($loginAttempts && $loginAttempts->num_rows > 0) {
                $loginStmt = $this->db->prepare("UPDATE `login_attempts` SET `username` = ? WHERE `username` = ?");
                if ($loginStmt) {
                    $loginStmt->bind_param('ss', $newId, $oldId);
                    $loginStmt->execute();
                }
            }

            $this->replaceStaffUploadPaths($oldId, $newId);
            $this->db->query('SET FOREIGN_KEY_CHECKS=1');
            $this->db->commit();
            $this->renameStaffUploadDir($oldId, $newId);
            return true;
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();
            try {
                $this->db->query('SET FOREIGN_KEY_CHECKS=1');
                $this->db->rollback();
            } catch (Throwable $ignored) {
            }
            error_log('StaffModel::renameStaffId: ' . $e->getMessage());
            return false;
        }
    }

    private function replaceStaffUploadPaths($oldId, $newId) {
        $safeOld = preg_replace('/[^A-Za-z0-9._-]/', '_', (string) $oldId);
        $safeNew = preg_replace('/[^A-Za-z0-9._-]/', '_', (string) $newId);
        $oldFrag = 'uploads/staff/' . $safeOld;
        $newFrag = 'uploads/staff/' . $safeNew;
        $photo = $this->db->prepare("UPDATE `staff` SET `staff_photo_path` = REPLACE(`staff_photo_path`, ?, ?) WHERE `staff_id` = ?");
        if ($photo) {
            $photo->bind_param('sss', $oldFrag, $newFrag, $newId);
            $photo->execute();
        }
        $docs = $this->db->query("SHOW TABLES LIKE 'staff_document'");
        if ($docs && $docs->num_rows > 0) {
            $docStmt = $this->db->prepare("UPDATE `staff_document` SET `stored_path` = REPLACE(`stored_path`, ?, ?) WHERE `staff_id` = ?");
            if ($docStmt) {
                $docStmt->bind_param('sss', $oldFrag, $newFrag, $newId);
                $docStmt->execute();
            }
        }
    }

    private function renameStaffUploadDir($oldId, $newId) {
        $safeOld = preg_replace('/[^A-Za-z0-9._-]/', '_', (string) $oldId);
        $safeNew = preg_replace('/[^A-Za-z0-9._-]/', '_', (string) $newId);
        $oldDir = BASE_PATH . '/uploads/staff/' . $safeOld;
        $newDir = BASE_PATH . '/uploads/staff/' . $safeNew;
        if (is_dir($oldDir) && $oldDir !== $newDir && !is_dir($newDir)) {
            @rename($oldDir, $newDir);
        }
    }
}

