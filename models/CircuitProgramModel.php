<?php
/**
 * Circuit Program Model
 */

class CircuitProgramModel extends Model {
    protected $table = 'circuit_program';
    protected $detailsTable = 'circuit_program_details';
    
    protected function getPrimaryKey() {
        return 'id';
    }
    
    /**
     * Create table if it doesn't exist
     */
    public function createTableIfNotExists() {
        // Create main circuit_program table
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->table}` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `staff_id` VARCHAR(50) NOT NULL COMMENT 'Staff ID of the employee',
            `employee_name` VARCHAR(255) NOT NULL,
            `designation` VARCHAR(255) DEFAULT NULL,
            `department_id` VARCHAR(6) DEFAULT NULL,
            `mode_of_travel` VARCHAR(100) DEFAULT NULL,
            `status` VARCHAR(50) DEFAULT 'pending' COMMENT 'pending, approved, rejected',
            `approver_id` INT(11) DEFAULT NULL COMMENT 'User ID of approver (DIR, DPA, DPI, REG)',
            `approver_role` VARCHAR(10) DEFAULT NULL COMMENT 'DIR, DPA, DPI, REG',
            `approval_date` DATETIME DEFAULT NULL,
            `approval_comments` TEXT DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_staff_id` (`staff_id`),
            KEY `idx_status` (`status`),
            KEY `idx_department_id` (`department_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->db->query($sql);
        
        // Create circuit_program_details table for program dates/destinations/purposes
        $detailsSql = "CREATE TABLE IF NOT EXISTS `{$this->detailsTable}` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `circuit_program_id` INT(11) NOT NULL,
            `date` DATE NOT NULL,
            `destination` VARCHAR(255) NOT NULL,
            `purpose` TEXT DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_circuit_program_id` (`circuit_program_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        try {
            $this->db->query($detailsSql);
            
            // Add foreign key constraint separately if it doesn't exist
            $checkFkSql = "SELECT COUNT(*) as fk_exists 
                          FROM information_schema.KEY_COLUMN_USAGE 
                          WHERE TABLE_SCHEMA = DATABASE() 
                          AND TABLE_NAME = '{$this->detailsTable}' 
                          AND CONSTRAINT_NAME = 'fk_circuit_program_details_program'";
            $fkResult = $this->db->query($checkFkSql);
            $fkRow = $fkResult->fetch_assoc();
            
            if (!$fkRow || $fkRow['fk_exists'] == 0) {
                try {
                    $addFkSql = "ALTER TABLE `{$this->detailsTable}` 
                                ADD CONSTRAINT `fk_circuit_program_details_program` 
                                FOREIGN KEY (`circuit_program_id`) 
                                REFERENCES `{$this->table}`(`id`) 
                                ON DELETE CASCADE";
                    $this->db->query($addFkSql);
                } catch (Exception $e) {
                    // Foreign key might not be supported or table structure doesn't allow it
                    error_log("Note: Could not add foreign key constraint: " . $e->getMessage());
                }
            }
        } catch (Exception $e) {
            error_log("Error creating circuit_program_details table: " . $e->getMessage());
        }
        
        return true;
    }
    
    /**
     * Create new circuit program
     */
    public function createProgram($data) {
        $this->createTableIfNotExists();
        
        // Get the mysqli connection for transactions
        $conn = $this->db->getConnection();
        $conn->autocommit(false);
        
        try {
            // Insert main program record
            $sql = "INSERT INTO `{$this->table}` 
                    (`staff_id`, `employee_name`, `designation`, `department_id`, `mode_of_travel`, `status`) 
                    VALUES (?, ?, ?, ?, ?, 'pending')";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssss",
                $data['staff_id'],
                $data['employee_name'],
                $data['designation'],
                $data['department_id'],
                $data['mode_of_travel']
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to create circuit program: " . $stmt->error);
            }
            
            $programId = $conn->insert_id;
            $stmt->close();
            
            // Insert program details (dates, destinations, purposes)
            if (!empty($data['program_details']) && is_array($data['program_details'])) {
                $detailsSql = "INSERT INTO `{$this->detailsTable}` 
                              (`circuit_program_id`, `date`, `destination`, `purpose`) 
                              VALUES (?, ?, ?, ?)";
                $detailsStmt = $conn->prepare($detailsSql);
                
                foreach ($data['program_details'] as $detail) {
                    if (!empty($detail['date']) && !empty($detail['destination'])) {
                        // Extract values to variables (bind_param requires variables, not direct array access)
                        $detailDate = $detail['date'];
                        $detailDestination = $detail['destination'];
                        $detailPurpose = $detail['purpose'] ?? '';
                        
                        $detailsStmt->bind_param("isss",
                            $programId,
                            $detailDate,
                            $detailDestination,
                            $detailPurpose
                        );
                        if (!$detailsStmt->execute()) {
                            throw new Exception("Failed to insert program detail: " . $detailsStmt->error);
                        }
                    }
                }
                $detailsStmt->close();
            }
            
            $conn->commit();
            $conn->autocommit(true);
            return $programId;
            
        } catch (Exception $e) {
            $conn->rollback();
            $conn->autocommit(true);
            error_log("Error creating circuit program: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all programs by staff ID
     */
    public function getByStaffId($staffId) {
        $this->createTableIfNotExists();
        
        $sql = "SELECT cp.*, 
                d.department_name,
                approver.user_name as approver_name
                FROM `{$this->table}` cp
                LEFT JOIN `department` d ON cp.department_id = d.department_id
                LEFT JOIN `user` approver ON cp.approver_id = approver.user_id
                WHERE cp.staff_id = ?
                ORDER BY cp.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $staffId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $programs = [];
        while ($row = $result->fetch_assoc()) {
            $programs[] = $row;
        }
        
        return $programs;
    }
    
    /**
     * Get program by ID with details
     */
    public function getById($programId) {
        $this->createTableIfNotExists();
        
        $sql = "SELECT cp.*, 
                d.department_name,
                approver.user_name as approver_name
                FROM `{$this->table}` cp
                LEFT JOIN `department` d ON cp.department_id = d.department_id
                LEFT JOIN `user` approver ON cp.approver_id = approver.user_id
                WHERE cp.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $programId);
        $stmt->execute();
        $result = $stmt->get_result();
        $program = $result->fetch_assoc();
        
        if ($program) {
            // Get program details
            $detailsSql = "SELECT * FROM `{$this->detailsTable}` 
                          WHERE `circuit_program_id` = ? 
                          ORDER BY `date` ASC";
            $detailsStmt = $this->db->prepare($detailsSql);
            $detailsStmt->bind_param("i", $programId);
            $detailsStmt->execute();
            $detailsResult = $detailsStmt->get_result();
            
            $program['details'] = [];
            while ($detail = $detailsResult->fetch_assoc()) {
                $program['details'][] = $detail;
            }
        }
        
        return $program;
    }
    
    /**
     * Get all programs (for listing)
     */
    public function getAll($filters = []) {
        $this->createTableIfNotExists();
        
        $sql = "SELECT cp.*, 
                d.department_name,
                s.staff_name,
                approver.user_name as approver_name
                FROM `{$this->table}` cp
                LEFT JOIN `department` d ON cp.department_id = d.department_id
                LEFT JOIN `staff` s ON cp.staff_id = s.staff_id
                LEFT JOIN `user` approver ON cp.approver_id = approver.user_id
                WHERE 1=1";
        
        $params = [];
        $types = '';
        
        if (!empty($filters['status'])) {
            $sql .= " AND cp.status = ?";
            $params[] = $filters['status'];
            $types .= 's';
        }
        
        if (!empty($filters['staff_id'])) {
            $sql .= " AND cp.staff_id = ?";
            $params[] = $filters['staff_id'];
            $types .= 's';
        }
        
        if (!empty($filters['department_id'])) {
            $sql .= " AND cp.department_id = ?";
            $params[] = $filters['department_id'];
            $types .= 's';
        }
        
        $sql .= " ORDER BY cp.created_at DESC";
        
        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->db->query($sql);
        }
        
        $programs = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $programs[] = $row;
            }
        }
        
        return $programs;
    }
    
    /**
     * Get pending programs for approval (DIR, DPA, DPI, REG)
     */
    public function getPendingApprovals() {
        $this->createTableIfNotExists();
        
        $sql = "SELECT cp.*, 
                d.department_name,
                s.staff_name
                FROM `{$this->table}` cp
                LEFT JOIN `department` d ON cp.department_id = d.department_id
                LEFT JOIN `staff` s ON cp.staff_id = s.staff_id
                WHERE cp.status = 'pending'
                ORDER BY cp.created_at ASC";
        
        $result = $this->db->query($sql);
        
        $programs = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $programs[] = $row;
            }
        }
        
        return $programs;
    }
    
    /**
     * Update approval status
     */
    public function updateApproval($programId, $approverId, $approverRole, $approved, $comments = '') {
        $this->createTableIfNotExists();
        
        $status = $approved ? 'approved' : 'rejected';
        $sql = "UPDATE `{$this->table}` 
                SET `status` = ?, 
                    `approver_id` = ?, 
                    `approver_role` = ?,
                    `approval_date` = NOW(), 
                    `approval_comments` = ?
                WHERE `id` = ? AND `status` = 'pending'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("sissi", $status, $approverId, $approverRole, $comments, $programId);
        
        return $stmt->execute();
    }
    
    /**
     * Delete program
     */
    public function deleteProgram($programId) {
        $this->createTableIfNotExists();
        
        $sql = "DELETE FROM `{$this->table}` WHERE `id` = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $programId);
        
        return $stmt->execute();
    }
}

