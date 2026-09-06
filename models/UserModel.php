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
            
            return true;
        } catch (Exception $e) {
            error_log("Error adding lock fields to user table: " . $e->getMessage());
            return false;
        }
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
        return $role === 'SAO' || $role === 'RSA'; // SAO or Registrar (Student Affairs)
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
        return $this->isHRO($userId);
    }

    /**
     * Side nav: dashboard, daily, month, sync (not limited to dashboard + month only).
     */
    public function hasFullStaffDeviceAttendanceNav($userId) {
        return $this->isAdminOrADM($userId) || $this->isHRO($userId);
    }

    /**
     * Check if user has finance-related position (FIN, ACC, or ADM)
     * FIN = Finance Officer
     * ACC = Accountant
     * ADM = Administrator
     */
    public function hasFinanceAccess($userId) {
        $role = $this->getUserRole($userId);
        return in_array($role, ['FIN', 'ACC', 'ADM']);
    }
    
    /**
     * Student fingerprint device attendance: SAO and ADM (or system Admin) only.
     */
    public function canManageStudentFingerprintAttendance($userId): bool {
        if ($this->isAdminOrADM($userId)) {
            return true;
        }
        return $this->getUserRole($userId) === 'SAO';
    }

    /**
     * Student Information Excel export (fingerprint-import): ADM / system admin only (not SAO).
     */
    public function canAccessStudentFingerprintImport($userId): bool {
        return $this->isAdminOrADM($userId);
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
     * Daily / sync: Admin/ADM and HRO via canManageStaffDeviceSyncDaily().
     * DIR, REG, FIN, ACC, HOD: dashboard + month only (limited side nav).
     */
    public function canViewStaffDeviceDashboardMonth($userId) {
        if ($this->isAdminOrADM($userId) || $this->isHRO($userId)) {
            return true;
        }
        $role = $this->getUserRole($userId);
        return in_array($role, ['DIR', 'REG', 'FIN', 'ACC', 'HOD'], true);
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
     * View payments list / receipts: FIN, ACC, ADM, or DIR (DIR is read-only).
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
     * Full CRUD on device assets: system admin, ADM, HOD ICT.
     */
    public function canManageDevices($userId): bool {
        if ($this->isAdminOrADM($userId)) {
            return true;
        }

        return $this->isHodIct($userId);
    }

    /**
     * View/search/scan/export device assets: ADM, HOD ICT, ACC, DIR (+ system admin).
     */
    public function canViewDevices($userId): bool {
        if ($this->canManageDevices($userId)) {
            return true;
        }
        $role = $this->getUserRole($userId);

        return in_array($role, ['ACC', 'DIR'], true);
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
}

