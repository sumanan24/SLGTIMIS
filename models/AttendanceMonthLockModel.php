<?php
/**
 * Attendance Month Lock Model
 * Tracks locked months for attendance - prevents changes after locking
 */

class AttendanceMonthLockModel extends Model {
    protected $table = 'attendance_month_lock';
    
    protected function getPrimaryKey() {
        return 'lock_id';
    }
    
    /**
     * Create attendance_month_lock table if it doesn't exist
     */
    public function createTableIfNotExists() {
        try {
            // Check if table exists
            $checkSql = "SHOW TABLES LIKE '{$this->table}'";
            $result = $this->db->query($checkSql);
            
            if ($result && $result->num_rows == 0) {
                // Create table
                $sql = "CREATE TABLE `{$this->table}` (
                    `lock_id` INT(11) NOT NULL AUTO_INCREMENT,
                    `department_id` VARCHAR(6) NOT NULL,
                    `month` VARCHAR(7) NOT NULL COMMENT 'Format: YYYY-MM',
                    `locked_by` INT(11) NOT NULL COMMENT 'User ID who locked the month',
                    `locked_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `locked_by_name` VARCHAR(100) DEFAULT NULL,
                    `status` ENUM('locked', 'unlocked') DEFAULT 'locked',
                    PRIMARY KEY (`lock_id`),
                    UNIQUE KEY `unique_dept_month` (`department_id`, `month`),
                    KEY `idx_department_id` (`department_id`),
                    KEY `idx_month` (`month`),
                    KEY `idx_status` (`status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                
                return $this->db->query($sql);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error creating attendance_month_lock table: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if a month is locked for a department
     */
    public function isMonthLocked($departmentId, $month) {
        $this->createTableIfNotExists();
        
        $sql = "SELECT `lock_id`, `status`, `locked_by`, `locked_at`, `locked_by_name`
                FROM `{$this->table}` 
                WHERE `department_id` = ? AND `month` = ? AND `status` = 'locked'
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ss", $departmentId, $month);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Lock a month for a department
     */
    public function lockMonth($departmentId, $month, $lockedBy, $lockedByName = null) {
        $this->createTableIfNotExists();
        
        // Check if already locked (status = 'locked')
        $existing = $this->isMonthLocked($departmentId, $month);
        if ($existing) {
            return false; // Already locked
        }
        
        // Insert or update lock record
        // Use INSERT ... ON DUPLICATE KEY UPDATE to handle existing unlocked records
        $sql = "INSERT INTO `{$this->table}` (`department_id`, `month`, `locked_by`, `locked_by_name`, `status`)
                VALUES (?, ?, ?, ?, 'locked')
                ON DUPLICATE KEY UPDATE 
                    `status` = 'locked',
                    `locked_by` = VALUES(`locked_by`),
                    `locked_by_name` = VALUES(`locked_by_name`),
                    `locked_at` = CURRENT_TIMESTAMP";
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("Error preparing lockMonth statement: " . $this->db->error);
            return false;
        }
        
        $stmt->bind_param("ssis", $departmentId, $month, $lockedBy, $lockedByName);
        $result = $stmt->execute();
        
        if (!$result) {
            error_log("Error executing lockMonth: " . $stmt->error);
        }
        
        $stmt->close();
        return $result;
    }
    
    /**
     * Unlock a month for a department (admin only)
     */
    public function unlockMonth($departmentId, $month) {
        $this->createTableIfNotExists();
        
        $sql = "UPDATE `{$this->table}` 
                SET `status` = 'unlocked'
                WHERE `department_id` = ? AND `month` = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ss", $departmentId, $month);
        
        return $stmt->execute();
    }
    
    /**
     * Get lock status for a month and department
     */
    public function getLockStatus($departmentId, $month) {
        $this->createTableIfNotExists();
        
        $sql = "SELECT `lock_id`, `status`, `locked_by`, `locked_at`, `locked_by_name`
                FROM `{$this->table}` 
                WHERE `department_id` = ? AND `month` = ?
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ss", $departmentId, $month);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Get all locked months for a department
     */
    public function getLockedMonths($departmentId = '') {
        $this->createTableIfNotExists();
        
        $sql = "SELECT l.*, d.department_name, u.user_name
                FROM `{$this->table}` l
                LEFT JOIN `department` d ON l.department_id = d.department_id
                LEFT JOIN `user` u ON l.locked_by = u.user_id
                WHERE l.`status` = 'locked'";
        
        $params = [];
        $types = '';
        
        if (!empty($departmentId)) {
            $sql .= " AND l.department_id = ?";
            $params[] = $departmentId;
            $types .= 's';
        }
        
        $sql .= " ORDER BY l.`month` DESC, l.`locked_at` DESC";
        
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
}

