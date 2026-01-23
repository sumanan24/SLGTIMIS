<?php
/**
 * On-Peak/Off-Peak Request Model
 */

class OnPeakRequestModel extends Model {
    protected $table = 'onpeak_request';
    
    protected function getPrimaryKey() {
        return 'id';
    }
    
    /**
     * Add required columns if they don't exist
     */
    public function addRequiredColumnsIfNotExists() {
        // Add is_hostel_student column
        $checkColumn = "SHOW COLUMNS FROM `{$this->table}` LIKE 'is_hostel_student'";
        $result = $this->db->query($checkColumn);
        if ($result->num_rows == 0) {
            $sql = "ALTER TABLE `{$this->table}` ADD COLUMN `is_hostel_student` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=Hostel student, 0=Non-hostel student' AFTER `department_id`";
            $this->db->query($sql);
        }
        
        // Add HOD approval columns
        $checkColumn = "SHOW COLUMNS FROM `{$this->table}` LIKE 'hod_approver_id'";
        $result = $this->db->query($checkColumn);
        if ($result->num_rows == 0) {
            $sql = "ALTER TABLE `{$this->table}` ADD COLUMN `hod_approver_id` INT(11) DEFAULT NULL COMMENT 'User ID of HOD who approved/rejected' AFTER `onpeak_request_status`";
            $this->db->query($sql);
        }
        
        $checkColumn = "SHOW COLUMNS FROM `{$this->table}` LIKE 'hod_approval_date'";
        $result = $this->db->query($checkColumn);
        if ($result->num_rows == 0) {
            $sql = "ALTER TABLE `{$this->table}` ADD COLUMN `hod_approval_date` DATETIME DEFAULT NULL AFTER `hod_approver_id`";
            $this->db->query($sql);
        }
        
        $checkColumn = "SHOW COLUMNS FROM `{$this->table}` LIKE 'hod_comments'";
        $result = $this->db->query($checkColumn);
        if ($result->num_rows == 0) {
            $sql = "ALTER TABLE `{$this->table}` ADD COLUMN `hod_comments` TEXT DEFAULT NULL AFTER `hod_approval_date`";
            $this->db->query($sql);
        }
        
        // Add second approver columns
        $checkColumn = "SHOW COLUMNS FROM `{$this->table}` LIKE 'second_approver_id'";
        $result = $this->db->query($checkColumn);
        if ($result->num_rows == 0) {
            $sql = "ALTER TABLE `{$this->table}` ADD COLUMN `second_approver_id` INT(11) DEFAULT NULL COMMENT 'User ID of second approver (Director/Warden)' AFTER `hod_comments`";
            $this->db->query($sql);
        }
        
        $checkColumn = "SHOW COLUMNS FROM `{$this->table}` LIKE 'second_approval_date'";
        $result = $this->db->query($checkColumn);
        if ($result->num_rows == 0) {
            $sql = "ALTER TABLE `{$this->table}` ADD COLUMN `second_approval_date` DATETIME DEFAULT NULL AFTER `second_approver_id`";
            $this->db->query($sql);
        }
        
        $checkColumn = "SHOW COLUMNS FROM `{$this->table}` LIKE 'second_approver_role'";
        $result = $this->db->query($checkColumn);
        if ($result->num_rows == 0) {
            $sql = "ALTER TABLE `{$this->table}` ADD COLUMN `second_approver_role` VARCHAR(10) DEFAULT NULL COMMENT 'DPR, WAR, etc.' AFTER `second_approval_date`";
            $this->db->query($sql);
        }
        
        $checkColumn = "SHOW COLUMNS FROM `{$this->table}` LIKE 'second_comments'";
        $result = $this->db->query($checkColumn);
        if ($result->num_rows == 0) {
            $sql = "ALTER TABLE `{$this->table}` ADD COLUMN `second_comments` TEXT DEFAULT NULL AFTER `second_approver_role`";
            $this->db->query($sql);
        }
        
        return true;
    }
    
    /**
     * Get requests for a student
     */
    public function getByStudentId($studentId) {
        $sql = "SELECT r.*, 
                s.student_fullname, s.student_email, s.student_gender,
                hod.user_name as hod_approver_name, 
                second.user_name as second_approver_name,
                d.department_name
                FROM `{$this->table}` r
                INNER JOIN `student` s ON r.student_id = s.student_id
                LEFT JOIN `user` hod ON r.hod_approver_id = hod.user_id
                LEFT JOIN `user` second ON r.second_approver_id = second.user_id
                LEFT JOIN `department` d ON r.department_id = d.department_id
                WHERE r.student_id = ?
                ORDER BY r.request_date_time DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $studentId);
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
     * Get pending requests for HOD approval
     */
    public function getPendingHODRequests($hodDepartmentId) {
        $sql = "SELECT r.*, 
                s.student_fullname, s.student_email, s.student_id, s.student_gender,
                d.department_name
                FROM `{$this->table}` r
                INNER JOIN `student` s ON r.student_id = s.student_id
                LEFT JOIN `department` d ON r.department_id = d.department_id
                WHERE (r.onpeak_request_status IS NULL OR r.onpeak_request_status = 'pending' OR r.onpeak_request_status = '')
                AND r.department_id = ?
                ORDER BY r.request_date_time ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $hodDepartmentId);
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
     * Get pending requests for second approval
     * For hostel students, needs Director (DPR) or Warden (WAR) approval
     */
    public function getPendingFinalRequests($approverRole, $userId, $approverGender = null) {
        return $this->getPendingSecondRequests($approverRole, $userId, $approverGender);
    }
    
    /**
     * Get pending requests for second approval
     * For temporary exit, hostel students need second approval from DIR, DPA, DPI, REG, ADM, HOD, or WAR
     * WAR can only approve students of the same gender (female WAR approves female students, male WAR approves male students)
     */
    public function getPendingSecondRequests($approverRole, $userId, $approverGender = null) {
        $sql = "SELECT r.*, 
                s.student_fullname, s.student_email, s.student_id, s.student_gender,
                d.department_name, d.department_id,
                hod.user_name as hod_approver_name
                FROM `{$this->table}` r
                INNER JOIN `student` s ON r.student_id = s.student_id
                LEFT JOIN `department` d ON r.department_id = d.department_id
                LEFT JOIN `user` hod ON r.hod_approver_id = hod.user_id
                WHERE (r.onpeak_request_status = 'hod_approved' OR r.onpeak_request_status = 'HOD Approved')
                AND r.second_approver_id IS NULL";
        
        // Filter by approver role - DIR, DPA, DPI, REG, ADM, HOD, or WAR can approve
        $allowedRoles = ['DIR', 'DPA', 'DPI', 'REG', 'ADM', 'HOD', 'WAR'];
        if (in_array($approverRole, $allowedRoles)) {
            // All these roles can approve hostel students after HOD approval
            $sql .= " AND r.is_hostel_student = 1";
            
            // For WAR, filter by gender - female WAR approves female students, male WAR approves male students
            if ($approverRole === 'WAR' && $approverGender) {
                // Normalize gender values (handle Male/Female)
                $normalizedGender = ucfirst(strtolower($approverGender));
                $sql .= " AND s.student_gender = ?";
                $params = [$normalizedGender];
                $types = 's';
            }
        } else {
            // Unknown role - return empty
            return [];
        }
        
        $sql .= " ORDER BY r.hod_approval_date ASC, r.request_date_time ASC";
        
        // Execute query with or without gender filter
        if (isset($params)) {
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
     * Create new request
     */
    public function createRequest($data) {
        // Add required columns if they don't exist
        $this->addRequiredColumnsIfNotExists();
        
        $sql = "INSERT INTO `{$this->table}` 
                (`student_id`, `department_id`, `contact_no`, `reason`, `exit_date`, `exit_time`, `return_date`, `return_time`, `comment`, `is_hostel_student`, `onpeak_request_status`, `request_date_time`) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ssissssssi",
            $data['student_id'],
            $data['department_id'],
            $data['contact_no'],
            $data['reason'],
            $data['exit_date'],
            $data['exit_time'],
            $data['return_date'],
            $data['return_time'],
            $data['comment'],
            $data['is_hostel_student']
        );
        
        return $stmt->execute();
    }
    
    /**
     * Approve/reject by HOD
     * For non-hostel students, approval goes directly to approved
     * For hostel students, approval goes to hod_approved (needs second approver)
     */
    public function updateHODApproval($requestId, $approverId, $approved, $comments = '') {
        // Get request to check if hostel student
        $request = $this->getRequestWithDetails($requestId);
        $isHostelStudent = $request && isset($request['is_hostel_student']) && $request['is_hostel_student'] == 1;
        
        if (!$approved) {
            $status = 'HOD Rejected';
        } else {
            if ($isHostelStudent) {
                // Hostel student needs second approval
                $status = 'hod_approved';
            } else {
                // Non-hostel student only needs HOD approval, directly approved
                $status = 'Approved';
                // Set second approver as HOD for non-hostel students
                $sql = "UPDATE `{$this->table}` 
                        SET `onpeak_request_status` = ?, 
                            `hod_approver_id` = ?, 
                            `hod_approval_date` = NOW(), 
                            `hod_comments` = ?,
                            `second_approver_id` = ?,
                            `second_approval_date` = NOW(),
                            `second_approver_role` = 'HOD'
                        WHERE `id` = ?";
                
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param("sisis", $status, $approverId, $comments, $approverId, $requestId);
                
                return $stmt->execute();
            }
        }
        
        $sql = "UPDATE `{$this->table}` 
                SET `onpeak_request_status` = ?, 
                    `hod_approver_id` = ?, 
                    `hod_approval_date` = NOW(), 
                    `hod_comments` = ?
                WHERE `id` = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("siss", $status, $approverId, $comments, $requestId);
        
        return $stmt->execute();
    }
    
    /**
     * Approve/reject by second approver
     */
    public function updateSecondApproval($requestId, $approverId, $approverRole, $approved, $comments = '') {
        $status = $approved ? 'Approved' : 'Rejected';
        $sql = "UPDATE `{$this->table}` 
                SET `onpeak_request_status` = ?, 
                    `second_approver_id` = ?, 
                    `second_approval_date` = NOW(), 
                    `second_comments` = ?,
                    `second_approver_role` = ?
                WHERE `id` = ? AND (`onpeak_request_status` = 'hod_approved' OR `onpeak_request_status` = 'HOD Approved')";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("sisss", $status, $approverId, $comments, $approverRole, $requestId);
        
        return $stmt->execute();
    }
    
    /**
     * Get request by ID with full details
     */
    public function getRequestWithDetails($requestId) {
        $sql = "SELECT r.*, 
                s.student_fullname, s.student_email, s.student_id, s.student_gender, s.student_phone,
                hod.user_name as hod_approver_name,
                second.user_name as second_approver_name,
                d.department_name, d.department_id
                FROM `{$this->table}` r
                INNER JOIN `student` s ON r.student_id = s.student_id
                LEFT JOIN `user` hod ON r.hod_approver_id = hod.user_id
                LEFT JOIN `user` second ON r.second_approver_id = second.user_id
                LEFT JOIN `department` d ON r.department_id = d.department_id
                WHERE r.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $requestId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Cancel request (by student)
     */
    public function cancelRequest($requestId, $studentId) {
        $sql = "UPDATE `{$this->table}` 
                SET `onpeak_request_status` = 'Cancelled' 
                WHERE `id` = ? AND `student_id` = ? AND (`onpeak_request_status` IS NULL OR `onpeak_request_status` = '' OR `onpeak_request_status` = 'pending')";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("is", $requestId, $studentId);
        
        return $stmt->execute();
    }
    
    /**
     * Check if student has pending request
     */
    public function hasPendingRequest($studentId) {
        $sql = "SELECT COUNT(*) as count FROM `{$this->table}` 
                WHERE `student_id` = ? 
                AND (`onpeak_request_status` IS NULL OR `onpeak_request_status` = '' OR `onpeak_request_status` = 'pending' OR `onpeak_request_status` = 'hod_approved' OR `onpeak_request_status` = 'HOD Approved')";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['count'] > 0;
    }
}

