<?php
/**
 * User Model
 */

class UserModel extends Model {
    protected $table = 'user';
    
    protected function getPrimaryKey() {
        return 'user_id';
    }
    
    /**
     * Add lock fields to user table if they don't exist
     */
    public function addLockFieldsIfNotExists() {
        try {
            // Check if account_locked field exists
            $checkSql = "SHOW COLUMNS FROM `{$this->table}` LIKE 'account_locked'";
            $result = $this->db->query($checkSql);
            
            if ($result->num_rows == 0) {
                // Add account_locked field
                $sql = "ALTER TABLE `{$this->table}` ADD COLUMN `account_locked` TINYINT(1) DEFAULT 0 COMMENT '1=Locked, 0=Unlocked' AFTER `user_active`";
                $this->db->query($sql);
            }
            
            // Check if lock_reason field exists
            $checkSql2 = "SHOW COLUMNS FROM `{$this->table}` LIKE 'lock_reason'";
            $result2 = $this->db->query($checkSql2);
            
            if ($result2->num_rows == 0) {
                // Add lock_reason field
                $sql2 = "ALTER TABLE `{$this->table}` ADD COLUMN `lock_reason` TEXT DEFAULT NULL COMMENT 'Reason for account lock' AFTER `account_locked`";
                $this->db->query($sql2);
            }
            
            // Check if locked_at field exists
            $checkSql3 = "SHOW COLUMNS FROM `{$this->table}` LIKE 'locked_at'";
            $result3 = $this->db->query($checkSql3);
            
            if ($result3->num_rows == 0) {
                // Add locked_at field
                $sql3 = "ALTER TABLE `{$this->table}` ADD COLUMN `locked_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Timestamp when account was locked' AFTER `lock_reason`";
                $this->db->query($sql3);
            }
            
            // Check if locked_by field exists
            $checkSql4 = "SHOW COLUMNS FROM `{$this->table}` LIKE 'locked_by'";
            $result4 = $this->db->query($checkSql4);
            
            if ($result4->num_rows == 0) {
                // Add locked_by field
                $sql4 = "ALTER TABLE `{$this->table}` ADD COLUMN `locked_by` INT(11) DEFAULT NULL COMMENT 'User ID who locked/unlocked the account' AFTER `locked_at`";
                $this->db->query($sql4);
            }
            
            $this->ensureMustChangePasswordColumn();
            return true;
        } catch (Exception $e) {
            error_log("Error adding lock fields to user table: " . $e->getMessage());
            return false;
        }
    }

    public function ensureMustChangePasswordColumn(): void {
        $check = $this->db->query("SHOW COLUMNS FROM `{$this->table}` LIKE 'must_change_password'");
        if ($check && $check->num_rows === 0) {
            $this->db->query("ALTER TABLE `{$this->table}` ADD COLUMN `must_change_password` TINYINT(1) NOT NULL DEFAULT 0 AFTER `user_active`");
        }
    }

    public function findStudentLogin(string $nic, string $studentId): ?array {
        $nic = strtoupper(trim($nic));
        $sql = "SELECT * FROM `{$this->table}` WHERE `user_table` = 'student' AND (`user_name` = ? OR `user_name` = ?) LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException('Could not look up the student login: ' . ($this->db->getConnection()->error ?: 'prepare failed'));
        }
        $stmt->bind_param('ss', $nic, $studentId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: null;
    }

    public function createStudentLogin(string $nic, string $email, string $passwordPlain, bool $mustChange = true) {
        $this->ensureMustChangePasswordColumn();
        $nic = strtoupper(trim($nic));
        $hash = hash('sha256', $passwordPlain);
        $must = $mustChange ? 1 : 0;
        $active = 1;
        $table = 'student';
        $position = 'STU';
        $sql = "INSERT INTO `{$this->table}` (`user_table`, `staff_position_type_id`, `user_name`, `user_password_hash`, `user_email`, `user_active`, `must_change_password`, `user_creation_timestamp`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $now = time();
        $stmt->bind_param('sssssiii', $table, $position, $nic, $hash, $email, $active, $must, $now);
        if (!$stmt->execute()) {
            throw new RuntimeException('Could not create the student login: ' . ($stmt->error ?: 'unknown error'));
        }
        return $this->db->lastInsertId();
    }

    public function findByEmail(string $email): ?array {
        $email = trim($email);
        if ($email === '') {
            return null;
        }
        $stmt = $this->db->prepare("SELECT * FROM `{$this->table}` WHERE `user_email` = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: null;
    }

    /**
     * Link the existing student row to a login. Prefer NIC as username.
     * A DB trigger already inserts user_name = student_id when a student is created.
     *
     * @return array{user_id:int, username:string, created:bool, password_reset:bool}
     */
    public function linkOrCreateStudentAccount(string $nic, string $studentId, string $email, bool $newStudent): array {
        $this->ensureMustChangePasswordColumn();
        $nic = strtoupper(trim($nic));
        $studentId = trim($studentId);
        $email = trim($email);
        $existing = $this->findStudentLogin($nic, $studentId);
        if ($existing) {
            $userId = (int) $existing['user_id'];
            $currentName = (string) $existing['user_name'];
            $username = $currentName;
            if (strcasecmp($currentName, $nic) !== 0) {
                $taken = $this->findByUsername($nic);
                if (!$taken || (int) $taken['user_id'] === $userId) {
                    $stmt = $this->db->prepare("UPDATE `{$this->table}` SET `user_name` = ? WHERE `user_id` = ?");
                    $stmt->bind_param('si', $nic, $userId);
                    if ($stmt->execute()) {
                        $username = $nic;
                    }
                }
            }
            $must = $newStudent ? 1 : (int) ($existing['must_change_password'] ?? 0);
            $stmt = $this->db->prepare("UPDATE `{$this->table}` SET `user_active` = 1, `must_change_password` = ? WHERE `user_id` = ?");
            $stmt->bind_param('ii', $must, $userId);
            $stmt->execute();
            return [
                'user_id' => $userId,
                'username' => $username,
                'created' => false,
                'password_reset' => false,
            ];
        }

        $uniqueEmail = $this->uniqueStudentEmail($email, $studentId);
        $id = (int) $this->createStudentLogin($nic, $uniqueEmail, $nic, true);
        return [
            'user_id' => $id,
            'username' => $nic,
            'created' => true,
            'password_reset' => true,
        ];
    }

    public function findByUsername(string $username): ?array {
        $username = trim($username);
        $stmt = $this->db->prepare("SELECT * FROM `{$this->table}` WHERE `user_name` = ? LIMIT 1");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: null;
    }

    private function uniqueStudentEmail(string $email, string $studentId): string {
        $email = trim($email);
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) && !$this->findByEmail($email)) {
            return $email;
        }
        $base = preg_replace('/[^A-Za-z0-9]+/', '', $studentId) ?: 'student';
        $candidate = strtolower($base) . '@slgtimis.local';
        $n = 1;
        while ($this->findByEmail($candidate)) {
            $n++;
            $candidate = strtolower($base) . $n . '@slgtimis.local';
        }
        return $candidate;
    }

    public function activateStudentLogin(int $userId, bool $mustChange = false): void {
        $this->ensureMustChangePasswordColumn();
        $must = $mustChange ? 1 : 0;
        $stmt = $this->db->prepare("UPDATE `{$this->table}` SET `user_active` = 1, `must_change_password` = ? WHERE `user_id` = ?");
        $stmt->bind_param('ii', $must, $userId);
        $stmt->execute();
    }

    public function clearMustChangePassword(int $userId): void {
        $this->ensureMustChangePasswordColumn();
        $stmt = $this->db->prepare("UPDATE `{$this->table}` SET `must_change_password` = 0 WHERE `user_id` = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
    }

    public function mustChangePassword(int $userId): bool {
        $this->ensureMustChangePasswordColumn();
        $stmt = $this->db->prepare("SELECT `must_change_password` FROM `{$this->table}` WHERE `user_id` = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return !empty($row['must_change_password']);
    }
    
    /**
     * Check if account is locked
     */
    public function isAccountLocked($userId) {
        $this->addLockFieldsIfNotExists();
        
        $sql = "SELECT `account_locked` FROM `{$this->table}` WHERE `user_id` = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return !empty($row) && (int)$row['account_locked'] === 1;
    }
    
    /**
     * Check if account is locked by username
     */
    public function isAccountLockedByUsername($username) {
        $this->addLockFieldsIfNotExists();
        
        $sql = "SELECT `account_locked` FROM `{$this->table}` WHERE `user_name` = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return !empty($row) && (int)$row['account_locked'] === 1;
    }
    
    /**
     * Lock account
     */
    public function lockAccount($userId, $reason = 'Too many failed login attempts', $lockedBy = null) {
        $this->addLockFieldsIfNotExists();
        
        $sql = "UPDATE `{$this->table}` 
                SET `account_locked` = 1, 
                    `lock_reason` = ?, 
                    `locked_at` = NOW(), 
                    `locked_by` = ? 
                WHERE `user_id` = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("sii", $reason, $lockedBy, $userId);
        
        return $stmt->execute();
    }
    
    /**
     * Lock account by username
     */
    public function lockAccountByUsername($username, $reason = 'Too many failed login attempts', $lockedBy = null) {
        $this->addLockFieldsIfNotExists();
        
        $sql = "UPDATE `{$this->table}` 
                SET `account_locked` = 1, 
                    `lock_reason` = ?, 
                    `locked_at` = NOW(), 
                    `locked_by` = ? 
                WHERE `user_name` = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("sis", $reason, $lockedBy, $username);
        
        return $stmt->execute();
    }
    
    /**
     * Unlock account
     */
    public function unlockAccount($userId, $unlockedBy = null) {
        $this->addLockFieldsIfNotExists();
        
        $sql = "UPDATE `{$this->table}` 
                SET `account_locked` = 0, 
                    `lock_reason` = NULL, 
                    `locked_at` = NULL,
                    `locked_by` = ? 
                WHERE `user_id` = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $unlockedBy, $userId);
        
        return $stmt->execute();
    }
    
    /**
     * Reset password
     */
    public function resetPassword($userId, $newPasswordHash, $resetBy = null) {
        $this->addLockFieldsIfNotExists();
        
        $sql = "UPDATE `{$this->table}` 
                SET `user_password_hash` = ? 
                WHERE `user_id` = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("si", $newPasswordHash, $userId);
        
        return $stmt->execute();
    }
    
    /**
     * Get user by username
     */
    public function getUserByUsername($username) {
        $sql = "SELECT * FROM `{$this->table}` WHERE `user_name` = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Get user role (staff_position_type_id)
     * String codes are trimmed and uppercased so comparisons match (e.g. "sao", " SAO " → SAO).
     */
    public function getUserRole($userId) {
        try {
            $sql = "SELECT `staff_position_type_id` FROM `{$this->table}` WHERE `user_id` = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            $val = $row ? $row['staff_position_type_id'] : null;
            if ($val === null || $val === '') {
                return null;
            }
            if (is_string($val)) {
                return strtoupper(trim($val));
            }
            return (string) $val;
        } catch (Exception $e) {
            error_log("Error getting user role: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Check if user has SAO (Students Affair Office) role
     */
    public function isSAO($userId) {
        $role = $this->getUserRole($userId);
        if ($role === 'SAO' || $role === 'RSA') { // SAO or Registrar (Student Affairs)
            return true;
        }
        // Management Assistants of the Student Affairs Office department do the SAO's work
        return $this->isStudentAffairsAssistant($userId);
    }
    
    /**
     * Check if user is HOD (Head of Department)
     */
    public function isHOD($userId) {
        $role = $this->getUserRole($userId);
        return $role === 'HOD';
    }

    /**
     * Check if user is HRO (HR Officer)
     */
    public function isHRO($userId) {
        return $this->getUserRole($userId) === 'HRO';
    }

    /**
     * Staff CRUD: ADM, MHF, REG, HRO, or system admin.
     */
    public function canManageStaff($userId) {
        if ($this->isAdmin($userId)) {
            return true;
        }
        return in_array($this->getUserRole($userId), ['ADM', 'MHF', 'REG', 'HRO'], true);
    }

    /**
     * Staff device attendance — daily report + device sync (full module tier).
     */
    public function canManageStaffDeviceSyncDaily($userId) {
        if ($this->isAdminOrADM($userId)) {
            return true;
        }
        return $this->isHRO($userId) || $this->getUserRole($userId) === 'MA2';
    }

    /**
     * Side nav: dashboard, daily, month, sync (not limited to dashboard + month only).
     */
    public function hasFullStaffDeviceAttendanceNav($userId) {
        return $this->isAdminOrADM($userId) || $this->isHRO($userId) || $this->getUserRole($userId) === 'MA2';
    }

    /**
     * Check if user has finance-related position (FIN, ACC, or ADM)
     * FIN = Finance Officer
     * ACC = Accountant
     * ADM = Administrator
     */
    /**
     * Department-bound academic staff see only their own department on the dashboard:
     * HOD, instructors (IN1-IN3) and lecturers (LE1, LE2, SLE).
     */
    public function usesDepartmentDashboard($userId): bool {
        if ($this->isAdminOrADM($userId)) {
            return false;
        }
        return in_array($this->getUserRole($userId), ['HOD', 'IN1', 'IN2', 'IN3', 'LE1', 'LE2', 'SLE'], true);
    }

    /**
     * Department id of a staff login (user_name = staff_id), or null.
     */
    public function getStaffDepartmentId($userId): ?string {
        $user = $this->find($userId);
        if (!$user || ($user['user_table'] ?? '') !== 'staff' || empty($user['user_name'])) {
            return null;
        }
        $stmt = $this->db->prepare("SELECT `department_id` FROM `staff` WHERE `staff_id` = ? LIMIT 1");
        $stmt->bind_param('s', $user['user_name']);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $dept = $row ? trim((string) ($row['department_id'] ?? '')) : '';
        return $dept !== '' ? $dept : null;
    }

    /**
     * Department this staff login is the assigned head of (department.head_staff_id), or null.
     */
    public function getHeadedDepartmentId($userId): ?string {
        try {
            $user = $this->find($userId);
            if (!$user || ($user['user_table'] ?? '') !== 'staff' || empty($user['user_name'])) {
                return null;
            }
            $stmt = $this->db->prepare("SELECT `department_id` FROM `department` WHERE `head_staff_id` = ? ORDER BY `department_id` LIMIT 1");
            if (!$stmt) {
                return null;
            }
            $stmt->bind_param('s', $user['user_name']);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            return $row && trim((string) $row['department_id']) !== '' ? (string) $row['department_id'] : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Ids of every department this staff login is assigned to head (Permanent, Acting or Cover-up).
     * Real assignments only (department.head_staff_id); no fallback to the staff member's own department.
     *
     * @return list<string>
     */
    public function getHeadedDepartmentIds($userId): array {
        $ids = [];
        try {
            $user = $this->find($userId);
            if (!$user || ($user['user_table'] ?? '') !== 'staff' || empty($user['user_name'])) {
                return [];
            }
            $stmt = $this->db->prepare("SELECT `department_id` FROM `department` WHERE `head_staff_id` = ? ORDER BY `department_name`");
            if (!$stmt) {
                return [];
            }
            $stmt->bind_param('s', $user['user_name']);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($res && ($row = $res->fetch_assoc())) {
                $id = trim((string) $row['department_id']);
                if ($id !== '') {
                    $ids[] = $id;
                }
            }
        } catch (Throwable $e) {
            return [];
        }
        return $ids;
    }

    /**
     * Departments this staff login heads, for display ("Head of Department - ICT").
     * Based on department.head_staff_id; a HOD position without an assignment falls back to
     * the staff member's own department.
     *
     * @return list<array{department_id:string, department_name:string, head_type:string, head_type_label:string}>
     */
    public function getHeadedDepartments($userId): array {
        $out = [];
        try {
            $user = $this->find($userId);
            if (!$user || ($user['user_table'] ?? '') !== 'staff' || empty($user['user_name'])) {
                return [];
            }
            $stmt = $this->db->prepare(
                "SELECT `department_id`, `department_name`, `head_type` FROM `department` WHERE `head_staff_id` = ? ORDER BY `department_name`"
            );
            if ($stmt) {
                $stmt->bind_param('s', $user['user_name']);
                $stmt->execute();
                $res = $stmt->get_result();
                $labels = ['permanent' => 'Permanent', 'acting' => 'Acting', 'cover_up' => 'Cover-up'];
                while ($res && ($row = $res->fetch_assoc())) {
                    $type = strtolower((string) ($row['head_type'] ?? ''));
                    $out[] = [
                        'department_id' => (string) $row['department_id'],
                        'department_name' => (string) $row['department_name'],
                        'head_type' => $type,
                        'head_type_label' => $labels[$type] ?? '',
                    ];
                }
            }
            if (empty($out) && $this->getUserRole($userId) === 'HOD') {
                $deptId = $this->getHODDepartment($userId) ?: $this->getStaffDepartmentId($userId);
                if ($deptId) {
                    $s2 = $this->db->prepare("SELECT `department_name` FROM `department` WHERE `department_id` = ?");
                    $s2->bind_param('s', $deptId);
                    $s2->execute();
                    $r2 = $s2->get_result()->fetch_assoc();
                    $out[] = [
                        'department_id' => (string) $deptId,
                        'department_name' => (string) ($r2['department_name'] ?? $deptId),
                        'head_type' => '',
                        'head_type_label' => '',
                    ];
                }
            }
        } catch (Throwable $e) {
            return [];
        }
        return $out;
    }

    /**
     * Finance staff (FIN / ACC) and Finance Office MA/MA2 get a finance-only dashboard.
     * System admin and ADM keep the full dashboard.
     */
    public function usesFinanceDashboard($userId): bool {
        if ($this->isAdminOrADM($userId)) {
            return false;
        }
        if (in_array($this->getUserRole($userId), ['FIN', 'ACC'], true)) {
            return true;
        }
        return $this->isFinanceDepartmentAssistant($userId);
    }

    public function hasFinanceAccess($userId) {
        $role = $this->getUserRole($userId);
        if (in_array($role, ['FIN', 'ACC', 'ADM'], true)) {
            return true;
        }
        // Management Assistants posted to Finance Office (e.g. MA2 in FIN)
        return $this->isFinanceDepartmentAssistant($userId);
    }
    
    /**
     * Management Assistant (MA, MA1, MA2 ...) who works in the Student Affairs Office department (STU).
     * isSAO() treats them as SAO, so they have every function the SAO has.
     */
    public function isStudentAffairsAssistant($userId): bool {
        return $this->isManagementAssistantInDepartment($userId, 'STU');
    }

    /**
     * Management Assistant (MA, MA1, MA2 ...) posted to the Finance Office (FIN).
     */
    public function isFinanceDepartmentAssistant($userId): bool {
        return $this->isManagementAssistantInDepartment($userId, 'FIN');
    }

    /**
     * FIN officer, or MA/MA2 posted in the Finance department.
     * Used for student attendance report: eligible students, locked months only.
     */
    public function isFinanceAttendanceViewer($userId): bool {
        if ($this->getUserRole($userId) === 'FIN') {
            return true;
        }
        return $this->isFinanceDepartmentAssistant($userId);
    }

    /**
     * Management Assistant (role code starts with MA) in a given staff department.
     */
    private function isManagementAssistantInDepartment($userId, string $departmentId): bool {
        $role = (string) $this->getUserRole($userId);
        if ($role === '' || strpos($role, 'MA') !== 0) {
            return false;
        }
        $departmentId = strtoupper(trim($departmentId));
        if ($departmentId === '') {
            return false;
        }
        try {
            $stmt = $this->db->prepare("SELECT s.`department_id` FROM `{$this->table}` u
                JOIN `staff` s ON s.`staff_id` = u.`user_name`
                WHERE u.`user_id` = ? AND u.`user_table` = 'staff' LIMIT 1");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            return $row && strtoupper(trim((string) $row['department_id'])) === $departmentId;
        } catch (Throwable $e) {
            error_log('isManagementAssistantInDepartment: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Student fingerprint device attendance: SAO, Student Affairs Office assistants (MA in STU),
     * and ADM (or system Admin).
     */
    public function canManageStudentFingerprintAttendance($userId): bool {
        return $this->isAdminOrADM($userId) || $this->isSAO($userId);
    }

    /**
     * Student Information Excel export (fingerprint-import): SAO and ADM / system admin.
     */
    public function canAccessStudentFingerprintImport($userId): bool {
        return $this->canManageStudentFingerprintAttendance($userId);
    }

    /**
     * Monthly SAO attendance dashboard (view): SAO, ADM, HOD, DIR, DPA, REG.
     */
    public function canViewStudentAttendanceSaoDashboard($userId): bool {
        if ($this->isAdminOrADM($userId) || $this->isSAO($userId)) {
            return true;
        }
        $role = $this->getUserRole($userId);
        return in_array($role, ['HOD', 'DIR', 'DPA', 'REG'], true);
    }

    /**
     * Manage leave / sync / device ops for student attendance (SAO + ADM only).
     */
    public function canManageStudentAttendanceSaoDashboard($userId): bool {
        return $this->canManageStudentFingerprintAttendance($userId);
    }

    /**
     * Check if user is ADM (Administrator position) or Admin user
     */
    public function isAdminOrADM($userId) {
        // Check if user is admin (by user_name or user_table)
        if ($this->isAdmin($userId)) {
            return true;
        }
        
        // Check if user has ADM position
        $role = $this->getUserRole($userId);
        return $role === 'ADM';
    }

    /**
     * Staff device attendance: dashboard + month report (embedded app).
     * Daily / sync: Admin/ADM, HRO and MA2 via canManageStaffDeviceSyncDaily().
     * DIR, REG, FIN, ACC, HOD: dashboard + month only (limited side nav).
     */
    public function canViewStaffDeviceDashboardMonth($userId) {
        if ($this->isAdminOrADM($userId) || $this->isHRO($userId)) {
            return true;
        }
        $role = $this->getUserRole($userId);
        return in_array($role, ['MA2', 'DIR', 'REG', 'FIN', 'ACC', 'HOD'], true);
    }
    
    /**
     * Check if user can access room/hostel management (ADM only)
     */
    public function canManageHostelsRooms($userId) {
        return $this->isAdminOrADM($userId);
    }

    /**
     * Online applications list and detail: SAO/RSA, DIR (NIC-complete rows only), ADM, system admin.
     */
    public function canViewOnlineStudentApplications($userId): bool {
        if ($this->isSAO($userId) || $this->isAdminOrADM($userId)) {
            return true;
        }
        return $this->getUserRole($userId) === 'DIR';
    }

    /**
     * SAO/RSA and DIR: hide NIC-only drafts and rows missing NIC copy + birth certificate (ADM / system admin see all).
     */
    public function usesLimitedStudentApplicationList($userId): bool {
        if ($this->isAdminOrADM($userId)) {
            return false;
        }
        return $this->isSAO($userId) || $this->getUserRole($userId) === 'DIR';
    }

    /**
     * Approve / reject online applications: SAO/RSA and ADM / system admin (DIR is view-only).
     */
    public function canDecideOnlineStudentApplications($userId): bool {
        return $this->isSAO($userId) || $this->isAdminOrADM($userId);
    }

    /**
     * Entrance exam / interview schedules: SAO/RSA, REG, DIR (view), ADM / system admin.
     */
    public function canViewApplicationAdmissionSchedules($userId): bool {
        if ($this->isSAO($userId) || $this->isAdminOrADM($userId)) {
            return true;
        }
        $role = $this->getUserRole($userId);
        return in_array($role, ['REG', 'DIR'], true);
    }

    /**
     * Create / edit schedules, entries: SAO/RSA, REG, ADM / system admin (DIR view-only).
     */
    public function canManageApplicationAdmissionSchedules($userId): bool {
        if ($this->isSAO($userId) || $this->isAdminOrADM($userId)) {
            return true;
        }
        return $this->getUserRole($userId) === 'REG';
    }

    /**
     * Interview selection list (selected / not selected / waitlist): SAO/RSA and ADM / system admin only.
     */
    public function canUpdateApplicationAdmissionSelection($userId): bool {
        return $this->isSAO($userId) || $this->isAdminOrADM($userId);
    }

    /**
     * Edit stored application fields and replace uploads: SAO/RSA and ADM / system admin (DIR is view-only).
     */
    public function canEditOnlineStudentApplications($userId): bool {
        return $this->isSAO($userId) || $this->isAdminOrADM($userId);
    }

    /**
     * Exam module: examinations office (EXAM) or Administrator (ADM), plus system admin.
     */
    public function canAccessExamsModule($userId): bool {
        if ($this->isAdmin($userId)) {
            return true;
        }
        $role = $this->getUserRole($userId);
        return in_array($role, ['EXAM', 'ADM'], true);
    }
    
    /**
     * View payments list / receipts: FIN, ACC, ADM, Finance MA/MA2, or DIR (DIR is read-only).
     */
    public function canViewPaymentsList($userId): bool {
        if ($this->hasFinanceAccess($userId)) {
            return true;
        }
        return $this->getUserRole($userId) === 'DIR';
    }

    /**
     * Bus season process & payment collections: SAO, DIR (view), ADM, system admin.
     */
    public function canViewBusSeasonOperations($userId): bool {
        if ($this->isSAO($userId) || $this->isAdminOrADM($userId)) {
            return true;
        }
        return $this->getUserRole($userId) === 'DIR';
    }

    /**
     * Modify bus season requests / payments: SAO and ADM / system admin only.
     */
    public function canManageBusSeasonOperations($userId): bool {
        return $this->isSAO($userId) || $this->isAdminOrADM($userId);
    }

    /**
     * View hostel room allocations: SAO, ADM, FIN, DIR, system admin.
     */
    public function canViewRoomAllocations($userId): bool {
        if ($this->canManageRoomAllocations($userId)) {
            return true;
        }
        return in_array($this->getUserRole($userId), ['FIN', 'DIR'], true);
    }

    /**
     * Check if user can access room allocations (SAO or ADM)
     */
    public function canManageRoomAllocations($userId) {
        // Check if SAO
        if ($this->isSAO($userId)) {
            return true;
        }
        
        // Check if ADM or Admin
        if ($this->isAdminOrADM($userId)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get HOD's department ID from staff table
     * Returns null if user is not HOD or staff record not found
     */
    public function getHODDepartment($userId) {
        try {
            // Get user's username (which corresponds to staff_id)
            $user = $this->find($userId);
            if (!$user || !$this->isHOD($userId)) {
                return null;
            }
            
            // Get department from staff table using user_name (which is staff_id)
            $sql = "SELECT s.department_id 
                    FROM `staff` s 
                    WHERE s.staff_id = ? AND s.staff_position = 'HOD'";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("s", $user['user_name']);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            return $row ? $row['department_id'] : null;
        } catch (Exception $e) {
            error_log("Error getting HOD department: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get user gender from staff table
     * Returns gender (Male/Female/Transgender) or null if not found
     */
    public function getUserGender($userId) {
        try {
            // Get user's username (which corresponds to staff_id)
            $user = $this->find($userId);
            if (!$user || !isset($user['user_name'])) {
                return null;
            }
            
            // Get gender from staff table using user_name (which is staff_id)
            $sql = "SELECT s.staff_gender 
                    FROM `staff` s 
                    WHERE s.staff_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("s", $user['user_name']);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            return $row ? $row['staff_gender'] : null;
        } catch (Exception $e) {
            error_log("Error getting user gender: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Check if user is admin
     * Admin is determined by user_table = 'admin' or user_name = 'admin'
     */
    public function isAdmin($userId) {
        try {
            // First, check if user_table column exists
            $checkColumn = "SHOW COLUMNS FROM `{$this->table}` LIKE 'user_table'";
            $resultCheck = $this->db->query($checkColumn);
            $hasUserTableColumn = ($resultCheck && $resultCheck->num_rows > 0);
            
            if ($hasUserTableColumn) {
                // If user_table column exists, check both user_table and user_name
                $sql = "SELECT `user_table`, `user_name` FROM `{$this->table}` WHERE `user_id` = ?";
            } else {
                // If user_table column doesn't exist, only check user_name
                $sql = "SELECT `user_name` FROM `{$this->table}` WHERE `user_id` = ?";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            if (!$row) {
                return false;
            }
            
            // Check if user_table is 'admin' or user_name is 'admin'
            $isAdminByTable = false;
            if ($hasUserTableColumn && isset($row['user_table'])) {
                $isAdminByTable = (strtolower($row['user_table']) === 'admin');
            }
            
            $isAdminByName = (isset($row['user_name']) && strtolower($row['user_name']) === 'admin');
            
            return $isAdminByTable || $isAdminByName;
        } catch (Exception $e) {
            error_log("Error checking if user is admin: " . $e->getMessage());
            // Fallback: just check username
            try {
                $sql = "SELECT `user_name` FROM `{$this->table}` WHERE `user_id` = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                
                return (isset($row['user_name']) && strtolower($row['user_name']) === 'admin');
            } catch (Exception $e2) {
                error_log("Error in fallback admin check: " . $e2->getMessage());
                return false;
            }
        }
    }
    
    /**
     * Get all locked accounts
     */
    public function getLockedAccounts() {
        $this->addLockFieldsIfNotExists();
        
        $sql = "SELECT * FROM `{$this->table}` WHERE `account_locked` = 1 ORDER BY `locked_at` DESC";
        $result = $this->db->query($sql);
        
        $accounts = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $accounts[] = $row;
            }
        }
        
        return $accounts;
    }
    
    /**
     * Get all users with lock status and optional filters
     */
    public function getAllUsersWithLockStatus($filters = []) {
        $this->addLockFieldsIfNotExists();
        
        $sql = "SELECT `user_id`, `user_name`, `user_email`, `user_active`, `account_locked`, 
                       `lock_reason`, `locked_at`, `locked_by`, `user_last_login_timestamp`
                FROM `{$this->table}` 
                WHERE 1=1";
        
        $conditions = [];
        $params = [];
        $types = '';
        
        // Search by username
        if (!empty($filters['search'])) {
            $conditions[] = "(`user_name` LIKE ? OR `user_email` LIKE ? OR `user_id` LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $types .= 'sss';
        }
        
        // Filter by active status
        if (isset($filters['status']) && $filters['status'] !== '') {
            $conditions[] = "`user_active` = ?";
            $params[] = (int)$filters['status'];
            $types .= 'i';
        }
        
        // Filter by lock status
        if (isset($filters['lock_status']) && $filters['lock_status'] !== '') {
            if ($filters['lock_status'] == 'locked') {
                $conditions[] = "`account_locked` = 1";
            } elseif ($filters['lock_status'] == 'unlocked') {
                $conditions[] = "(`account_locked` = 0 OR `account_locked` IS NULL)";
            }
        }
        
        // Filter by has login (never logged in vs has logged in)
        if (isset($filters['has_login']) && $filters['has_login'] !== '') {
            if ($filters['has_login'] == 'yes') {
                $conditions[] = "`user_last_login_timestamp` IS NOT NULL";
            } elseif ($filters['has_login'] == 'no') {
                $conditions[] = "(`user_last_login_timestamp` IS NULL OR `user_last_login_timestamp` = 0)";
            }
        }
        
        if (!empty($conditions)) {
            $sql .= " AND " . implode(" AND ", $conditions);
        }
        
        // Order by
        $orderBy = $filters['order_by'] ?? 'user_name';
        $allowedOrderBy = [
            'user_id',
            'user_name',
            'user_email',
            'user_active',
            'account_locked',
            'lock_reason',
            'locked_at',
            'user_last_login_timestamp'
        ];
        if (!in_array($orderBy, $allowedOrderBy, true)) {
            $orderBy = 'user_name';
        }
        $orderDir = isset($filters['order_dir']) && strtoupper($filters['order_dir']) === 'DESC' ? 'DESC' : 'ASC';
        $sql .= " ORDER BY `{$orderBy}` {$orderDir}";
        
        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->db->query($sql);
        }
        
        $users = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        }
        
        return $users;
    }

    /**
     * HOD of ICT department — full device asset management (same as ADM for this module).
     */
    public function isHodIct($userId): bool {
        if (!$this->isHOD($userId)) {
            return false;
        }
        $dept = strtoupper(trim((string) $this->getHODDepartment($userId)));

        return $dept === 'ICT';
    }

    /**
     * Register and update assets: admin, ADM, inventory officer (ACC), and every HOD.
     * HODs are limited to their own department in the controller.
     */
    public function canManageDevices($userId): bool {
        if ($this->isAdminOrADM($userId)) {
            return true;
        }
        $role = $this->getUserRole($userId);
        if ($role === 'ACC') {
            return true;
        }

        return $this->isHOD($userId);
    }

    /**
     * Approve a transfer or a disposal request.
     */
    public function canApproveDeviceRequests($userId): bool {
        if ($this->isAdminOrADM($userId)) {
            return true;
        }

        return $this->getUserRole($userId) === 'ACC';
    }

    /**
     * View assets. Directors monitor every department. Other staff and students
     * only see assets assigned to them (scoped in the controller).
     */
    public function canViewDevices($userId): bool {
        if ($this->canManageDevices($userId)) {
            return true;
        }
        $role = $this->getUserRole($userId);
        if (in_array($role, ['DIR', 'DPI', 'DPA', 'ACC'], true)) {
            return true;
        }
        $user = $this->find($userId);
        if (!$user) {
            return false;
        }
        $table = (string) ($user['user_table'] ?? '');

        return $table === 'student' || $table === 'staff' || $table === '';
    }

    public function canScanDeviceQr($userId): bool {
        return $this->canViewDevices($userId);
    }

    public function canExportDevices($userId): bool {
        return $this->canViewDevices($userId);
    }

    public function canPrintDeviceQr($userId): bool {
        return $this->canViewDevices($userId);
    }

    public function canViewDeviceHistory($userId): bool {
        return $this->canViewDevices($userId);
    }

    /**
     * Student complaint letters: SAO, ADM, HOD, REG, DIR (+ system admin).
     */
    public function canViewComplaintLetters($userId): bool {
        if ($this->isAdminOrADM($userId) || $this->isSAO($userId)) {
            return true;
        }
        $role = $this->getUserRole($userId);

        return in_array($role, ['HOD', 'REG', 'DIR'], true);
    }

    /**
     * Create/edit/delete/generate complaint letters: SAO, ADM, HOD (dept-scoped in controller).
     */
    public function canManageComplaintLetters($userId): bool {
        if ($this->isAdminOrADM($userId) || $this->isSAO($userId)) {
            return true;
        }

        return $this->isHOD($userId);
    }

    /**
     * REG and DIR — read-only complaint letter access.
     */
    public function isComplaintLetterReadOnly($userId): bool {
        if ($this->canManageComplaintLetters($userId)) {
            return false;
        }
        if (!$this->canViewComplaintLetters($userId)) {
            return false;
        }
        $role = $this->getUserRole($userId);

        return in_array($role, ['REG', 'DIR'], true);
    }

    /**
     * Facilities Officer: ADM, FAC, FO, MHF, or system admin.
     */
    public function isFacilitiesOfficer($userId): bool {
        if ($this->isAdminOrADM($userId)) {
            return true;
        }
        return in_array($this->getUserRole($userId), ['FAC', 'FO', 'MHF'], true);
    }

    /**
     * Principal / management monitoring: DIR, DPI, DPA, REG.
     */
    public function isFacilitiesMonitor($userId): bool {
        if ($this->isAdminOrADM($userId)) {
            return true;
        }
        return in_array($this->getUserRole($userId), ['DIR', 'DPI', 'DPA', 'REG'], true);
    }
}

