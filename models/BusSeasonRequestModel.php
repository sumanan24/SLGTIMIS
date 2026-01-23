<?php
/**
 * Bus Season Request Model
 */

class BusSeasonRequestModel extends Model {
    protected $table = 'season_requests';
    protected $paymentTable = 'season_payments';
    
    protected function getPrimaryKey() {
        return 'id';
    }
    
    /**
     * Add required columns to existing season_requests table if they don't exist
     */
    public function addRequiredColumnsIfNotExists() {
        // Add HOD approval columns
        $columns = [
            'hod_approver_id' => "ALTER TABLE `{$this->table}` ADD COLUMN `hod_approver_id` INT(11) DEFAULT NULL COMMENT 'User ID of HOD who approved' AFTER `approved_by`",
            'hod_approval_date' => "ALTER TABLE `{$this->table}` ADD COLUMN `hod_approval_date` DATETIME DEFAULT NULL AFTER `hod_approver_id`",
            'hod_comments' => "ALTER TABLE `{$this->table}` ADD COLUMN `hod_comments` TEXT DEFAULT NULL AFTER `hod_approval_date`",
            'second_approver_id' => "ALTER TABLE `{$this->table}` ADD COLUMN `second_approver_id` INT(11) DEFAULT NULL COMMENT 'User ID of second approver (DIR, DPA, DPI, REG)' AFTER `hod_comments`",
            'second_approver_role' => "ALTER TABLE `{$this->table}` ADD COLUMN `second_approver_role` VARCHAR(10) DEFAULT NULL COMMENT 'DIR, DPA, DPI, REG' AFTER `second_approver_id`",
            'second_approval_date' => "ALTER TABLE `{$this->table}` ADD COLUMN `second_approval_date` DATETIME DEFAULT NULL AFTER `second_approver_role`",
            'second_comments' => "ALTER TABLE `{$this->table}` ADD COLUMN `second_comments` TEXT DEFAULT NULL AFTER `second_approval_date`",
            'department_id' => "ALTER TABLE `{$this->table}` ADD COLUMN `department_id` VARCHAR(6) DEFAULT NULL AFTER `student_id`"
        ];
        
        foreach ($columns as $columnName => $sql) {
            try {
                $checkSql = "SHOW COLUMNS FROM `{$this->table}` LIKE '{$columnName}'";
                $result = $this->db->query($checkSql);
                if (!$result || $result->num_rows == 0) {
                    $this->db->query($sql);
                }
            } catch (Exception $e) {
                error_log("Error adding column {$columnName}: " . $e->getMessage());
            }
        }
        
        return true;
    }
    
    /**
     * Create new request (approval only, no payment details)
     */
    public function createRequest($data) {
        $this->addRequiredColumnsIfNotExists();
        
        $sql = "INSERT INTO `{$this->table}` 
                (`student_id`, `department_id`, `season_year`, `season_name`, `depot_name`, 
                 `route_from`, `route_to`, `change_point`, `distance_km`, `status`, `notes`) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)";
        
        $stmt = $this->db->prepare($sql);
        
        // Extract values to variables (bind_param requires variables, not direct array access)
        $studentId = $data['student_id'];
        $departmentId = $data['department_id'] ?? null;
        $seasonYear = $data['season_year'];
        $seasonName = $data['season_name'] ?? '';
        $depotName = $data['depot_name'] ?? '';
        $routeFrom = $data['route_from'] ?? '';
        $routeTo = $data['route_to'] ?? '';
        $changePoint = $data['change_point'] ?? '';
        $distanceKm = floatval($data['distance_km'] ?? 0);
        $notes = $data['notes'] ?? null;
        
        $stmt->bind_param("ssssssssds",
            $studentId,
            $departmentId,
            $seasonYear,
            $seasonName,
            $depotName,
            $routeFrom,
            $routeTo,
            $changePoint,
            $distanceKm,
            $notes
        );
        
        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }
    
    /**
     * Get request by student ID and season year
     */
    public function getByStudentIdAndYear($studentId, $seasonYear) {
        $this->addRequiredColumnsIfNotExists();
        
        $sql = "SELECT * FROM `{$this->table}` 
                WHERE `student_id` = ? AND `season_year` = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ss", $studentId, $seasonYear);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Get all requests by student ID
     */
    public function getByStudentId($studentId) {
        $this->addRequiredColumnsIfNotExists();
        
        $sql = "SELECT r.*, 
                d.department_name,
                hod.user_name as hod_approver_name,
                second.user_name as second_approver_name
                FROM `{$this->table}` r
                LEFT JOIN `department` d ON r.department_id = d.department_id
                LEFT JOIN `user` hod ON r.hod_approver_id = hod.user_id
                LEFT JOIN `user` second ON r.second_approver_id = second.user_id
                WHERE r.student_id = ?
                ORDER BY r.season_year DESC, r.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        return $data;
    }
    
    /**
     * Get pending requests for HOD approval
     */
    public function getPendingHODRequests($departmentId) {
        $this->addRequiredColumnsIfNotExists();
        
        $sql = "SELECT r.*, 
                s.student_fullname, s.student_email, s.student_id, s.student_gender,
                d.department_name, d.department_id
                FROM `{$this->table}` r
                INNER JOIN `student` s ON r.student_id = s.student_id
                LEFT JOIN `department` d ON r.department_id = d.department_id
                WHERE r.status = 'pending'
                AND r.department_id = ?
                ORDER BY r.created_at ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $departmentId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        return $data;
    }
    
    /**
     * Get pending requests for second approval (DIR, DPA, DPI, REG)
     */
    public function getPendingSecondRequests($approverRole) {
        $this->addRequiredColumnsIfNotExists();
        
        $sql = "SELECT r.*, 
                s.student_fullname, s.student_email, s.student_id, s.student_gender,
                d.department_name, d.department_id,
                hod.user_name as hod_approver_name
                FROM `{$this->table}` r
                INNER JOIN `student` s ON r.student_id = s.student_id
                LEFT JOIN `department` d ON r.department_id = d.department_id
                LEFT JOIN `user` hod ON r.hod_approver_id = hod.user_id
                WHERE r.status = 'hod_approved'
                AND r.second_approver_id IS NULL
                ORDER BY r.hod_approval_date ASC, r.created_at ASC";
        
        $result = $this->db->query($sql);
        
        $data = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        
        return $data;
    }
    
    /**
     * Get requests for SAO (after HOD approval) - shows all requests (HOD approved or not, need second approval or not)
     */
    public function getRequestsForSAO() {
        $this->addRequiredColumnsIfNotExists();
        
        $sql = "SELECT r.*, 
                s.student_fullname, s.student_email, s.student_id, s.student_gender,
                d.department_name, d.department_id,
                hod.user_name as hod_approver_name,
                second.user_name as second_approver_name,
                (SELECT COUNT(*) FROM `{$this->paymentTable}` p WHERE p.request_id = r.id) as has_payment
                FROM `{$this->table}` r
                INNER JOIN `student` s ON r.student_id = s.student_id
                LEFT JOIN `department` d ON r.department_id = d.department_id
                LEFT JOIN `user` hod ON r.hod_approver_id = hod.user_id
                LEFT JOIN `user` second ON r.second_approver_id = second.user_id
                WHERE r.status IN ('hod_approved', 'approved')
                ORDER BY 
                    CASE 
                        WHEN r.status = 'approved' THEN 1
                        WHEN r.status = 'hod_approved' THEN 2
                        ELSE 3
                    END,
                    r.second_approval_date DESC, 
                    r.hod_approval_date DESC, 
                    r.created_at DESC";
        
        $result = $this->db->query($sql);
        
        $data = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        
        return $data;
    }
    
    /**
     * Update HOD approval
     */
    public function updateHODApproval($requestId, $approverId, $approved, $comments = '') {
        $this->addRequiredColumnsIfNotExists();
        
        $status = $approved ? 'hod_approved' : 'rejected';
        $sql = "UPDATE `{$this->table}` 
                SET `status` = ?, 
                    `hod_approver_id` = ?, 
                    `hod_approval_date` = NOW(), 
                    `hod_comments` = ?,
                    `approved_by` = ?,
                    `approved_at` = NOW()
                WHERE `id` = ? AND `status` = 'pending'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("sisii", $status, $approverId, $comments, $approverId, $requestId);
        
        return $stmt->execute();
    }
    
    /**
     * Update second approval (DIR, DPA, DPI, REG)
     */
    public function updateSecondApproval($requestId, $approverId, $approverRole, $approved, $comments = '') {
        $this->addRequiredColumnsIfNotExists();
        
        $status = $approved ? 'approved' : 'rejected';
        $sql = "UPDATE `{$this->table}` 
                SET `status` = ?, 
                    `second_approver_id` = ?, 
                    `second_approver_role` = ?,
                    `second_approval_date` = NOW(), 
                    `second_comments` = ?,
                    `approved_by` = ?,
                    `approved_at` = NOW()
                WHERE `id` = ? AND `status` = 'hod_approved'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("sissii", $status, $approverId, $approverRole, $comments, $approverId, $requestId);
        
        return $stmt->execute();
    }
    
    /**
     * Calculate season total from student payment (30%)
     * Student pays 30%, so total = student_payment / 0.30
     */
    public function calculateSeasonTotal($studentPayment) {
        // Student pays 30%, so total = student_payment / 0.30
        $total = $studentPayment / 0.30;
        return round($total, 2);
    }
    
    /**
     * Calculate SLGTI payment (35% of total)
     */
    public function calculateSLGTIPayment($total) {
        return round($total * 0.35, 2);
    }
    
    /**
     * Calculate CTB payment (35% of total)
     */
    public function calculateCTBPayment($total) {
        return round($total * 0.35, 2);
    }
    
    /**
     * Create payment collection record (separate table)
     */
    public function createPaymentCollection($requestId, $studentId, $studentPayment, $seasonRate, $paymentMethod = 'cash', $paymentReference = null, $notes = null, $collectedBy) {
        $this->addRequiredColumnsIfNotExists();
        
        // Calculate totals
        $totalAmount = $this->calculateSeasonTotal($studentPayment); // Total = student_payment / 0.30
        $slgtiPaid = $this->calculateSLGTIPayment($totalAmount);
        $ctbPaid = $this->calculateCTBPayment($totalAmount);
        $remainingBalance = 0; // All payments calculated, no remaining balance
        
        $sql = "INSERT INTO `{$this->paymentTable}` 
                (`request_id`, `student_id`, `paid_amount`, `season_rate`, `total_amount`, 
                 `student_paid`, `slgti_paid`, `ctb_paid`, `remaining_balance`, 
                 `status`, `payment_date`, `payment_method`, `payment_reference`, 
                 `collected_by`, `notes`) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed', NOW(), ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("isddddddssis", 
            $requestId,
            $studentId,
            $studentPayment, // paid_amount (what student actually paid)
            $seasonRate, // season_rate
            $totalAmount, // total_amount (100%)
            $studentPayment, // student_paid (30%)
            $slgtiPaid, // slgti_paid (35%)
            $ctbPaid, // ctb_paid (35%)
            $remainingBalance, // remaining_balance
            $paymentMethod,
            $paymentReference,
            $collectedBy,
            $notes
        );
        
        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }
    
    /**
     * Get payment collection by request ID
     */
    public function getPaymentCollectionByRequestId($requestId) {
        $sql = "SELECT p.*, 
                u.user_name as collected_by_name
                FROM `{$this->paymentTable}` p
                LEFT JOIN `user` u ON p.collected_by = u.user_id
                WHERE p.request_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $requestId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Get all payment collections for SAO/ADM - shows all requests (with or without payments)
     */
    public function getAllPaymentCollections($filters = []) {
        $this->addRequiredColumnsIfNotExists();
        
        $sql = "SELECT r.id as request_id,
                r.student_id, r.season_year, r.season_name, r.depot_name, r.route_from, r.route_to, r.change_point, r.distance_km,
                r.status as request_status,
                r.hod_approver_id, r.hod_approval_date, r.hod_comments,
                r.second_approver_id, r.second_approver_role, r.second_approval_date, r.second_comments,
                s.student_fullname, s.student_email,
                d.department_name, d.department_id,
                hod.user_name as hod_approver_name,
                second.user_name as second_approver_name,
                p.id as payment_id, p.paid_amount, p.season_rate, p.total_amount, p.student_paid, p.slgti_paid, p.ctb_paid, 
                p.remaining_balance, p.status as payment_status, p.payment_date, p.payment_method, 
                p.payment_reference, p.collected_by, p.notes as payment_notes,
                u.user_name as collected_by_name
                FROM `{$this->table}` r
                INNER JOIN `student` s ON r.student_id = s.student_id
                LEFT JOIN `department` d ON r.department_id = d.department_id
                LEFT JOIN `user` hod ON r.hod_approver_id = hod.user_id
                LEFT JOIN `user` second ON r.second_approver_id = second.user_id
                LEFT JOIN `{$this->paymentTable}` p ON r.id = p.request_id
                LEFT JOIN `user` u ON p.collected_by = u.user_id
                WHERE 1=1";
        
        $params = [];
        $types = '';
        
        if (!empty($filters['season_year'])) {
            $sql .= " AND r.season_year = ?";
            $params[] = $filters['season_year'];
            $types .= 's';
        }
        
        if (!empty($filters['student_id'])) {
            $sql .= " AND p.student_id = ?";
            $params[] = $filters['student_id'];
            $types .= 's';
        }
        
        $sql .= " ORDER BY p.payment_date DESC";
        
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
     * Check if payment already collected for request
     */
    public function hasPaymentCollection($requestId) {
        $sql = "SELECT COUNT(*) as count FROM `{$this->paymentTable}` WHERE `request_id` = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $requestId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row && $row['count'] > 0;
    }
    
    /**
     * Get request by ID with full details (including payment if exists)
     */
    public function getRequestWithDetails($requestId) {
        $this->addRequiredColumnsIfNotExists();
        
        $sql = "SELECT r.*, 
                s.student_fullname, s.student_email, s.student_id, s.student_gender,
                d.department_name, d.department_id,
                hod.user_name as hod_approver_name,
                second.user_name as second_approver_name
                FROM `{$this->table}` r
                INNER JOIN `student` s ON r.student_id = s.student_id
                LEFT JOIN `department` d ON r.department_id = d.department_id
                LEFT JOIN `user` hod ON r.hod_approver_id = hod.user_id
                LEFT JOIN `user` second ON r.second_approver_id = second.user_id
                WHERE r.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $requestId);
        $stmt->execute();
        $result = $stmt->get_result();
        $request = $result->fetch_assoc();
        
        // Get payment collection if exists
        if ($request) {
            $payment = $this->getPaymentCollectionByRequestId($requestId);
            if ($payment) {
                $request['payment'] = $payment;
            }
        }
        
        return $request;
    }
    
    /**
     * Check if student has existing request for season year
     */
    public function hasExistingRequest($studentId, $seasonYear) {
        $request = $this->getByStudentIdAndYear($studentId, $seasonYear);
        return $request !== null;
    }
}

