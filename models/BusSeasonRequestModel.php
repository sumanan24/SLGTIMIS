<?php
/**
 * Bus Season Request Model
 * MVC Architecture - Handles all database operations for bus season requests
 */

class BusSeasonRequestModel extends Model {
    protected $table = 'season_requests';
    protected $paymentTable = 'season_payments';
    
    protected function getPrimaryKey() {
        return 'id';
    }
    
    /**
     * Ensure table structure exists (renamed from addRequiredColumnsIfNotExists)
     */
    public function ensureTableStructure() {
        $this->addRequiredColumnsIfNotExists();
    }
    
    /**
     * Add required columns to existing season_requests table if they don't exist
     */
    private function addRequiredColumnsIfNotExists() {
        $columns = [
            'department_id' => "ALTER TABLE `{$this->table}` ADD COLUMN `department_id` VARCHAR(6) DEFAULT NULL AFTER `student_id`",
            'hod_approver_id' => "ALTER TABLE `{$this->table}` ADD COLUMN `hod_approver_id` INT(11) DEFAULT NULL COMMENT 'User ID of HOD who approved' AFTER `approved_by`",
            'hod_approval_date' => "ALTER TABLE `{$this->table}` ADD COLUMN `hod_approval_date` DATETIME DEFAULT NULL AFTER `hod_approver_id`",
            'hod_comments' => "ALTER TABLE `{$this->table}` ADD COLUMN `hod_comments` TEXT DEFAULT NULL AFTER `hod_approval_date`",
            'second_approver_id' => "ALTER TABLE `{$this->table}` ADD COLUMN `second_approver_id` INT(11) DEFAULT NULL COMMENT 'User ID of second approver (DIR, DPA, DPI, REG)' AFTER `hod_comments`",
            'second_approver_role' => "ALTER TABLE `{$this->table}` ADD COLUMN `second_approver_role` VARCHAR(10) DEFAULT NULL COMMENT 'DIR, DPA, DPI, REG' AFTER `second_approver_id`",
            'second_approval_date' => "ALTER TABLE `{$this->table}` ADD COLUMN `second_approval_date` DATETIME DEFAULT NULL AFTER `second_approver_role`",
            'second_comments' => "ALTER TABLE `{$this->table}` ADD COLUMN `second_comments` TEXT DEFAULT NULL AFTER `second_approval_date`"
        ];
        
        foreach ($columns as $columnName => $sql) {
            try {
                $checkSql = "SHOW COLUMNS FROM `{$this->table}` LIKE '{$columnName}'";
                $result = $this->db->query($checkSql);
                if (!$result || $result->num_rows == 0) {
                    $alterResult = $this->db->query($sql);
                    if (!$alterResult) {
                        $conn = $this->db->getConnection();
                        $error = $conn ? $conn->error : 'Unknown error';
                        error_log("Error adding column {$columnName} to {$this->table}: " . $error);
                        if (strpos($sql, 'AFTER') !== false) {
                            $simpleSql = preg_replace('/\s+AFTER\s+`?[\w]+`?/i', '', $sql);
                            $simpleResult = $this->db->query($simpleSql);
                            if (!$simpleResult) {
                                error_log("Error adding column {$columnName} without AFTER clause: " . ($conn ? $conn->error : 'Unknown error'));
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("Exception adding column {$columnName}: " . $e->getMessage());
            }
        }
        
        // Add columns for payments table
        $paymentColumns = [
            'issued_at' => "ALTER TABLE `{$this->paymentTable}` ADD COLUMN `issued_at` DATETIME DEFAULT NULL AFTER `payment_date`"
        ];
        
        foreach ($paymentColumns as $columnName => $sql) {
            try {
                $checkSql = "SHOW COLUMNS FROM `{$this->paymentTable}` LIKE '{$columnName}'";
                $result = $this->db->query($checkSql);
                if (!$result || $result->num_rows == 0) {
                    $this->db->query($sql);
                }
            } catch (Exception $e) {
                error_log("Error adding payment column {$columnName}: " . $e->getMessage());
            }
        }
        
        return true;
    }
    
    /**
     * Create new request
     */
    public function create($data) {
        $logPrefix = "BusSeasonRequestModel::create";
        
        try {
            $this->ensureTableStructure();
            
            $sql = "INSERT INTO `{$this->table}` 
                    (`student_id`, `department_id`, `season_year`, `season_name`, `depot_name`, 
                     `route_from`, `route_to`, `change_point`, `distance_km`, `status`, `notes`) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)";
            
            error_log("{$logPrefix} - SQL: " . $sql);
            error_log("{$logPrefix} - Data: " . json_encode($data));
            
            $stmt = $this->db->prepare($sql);
            
            if (!$stmt) {
                $conn = $this->db->getConnection();
                $errorMsg = $conn ? $conn->error : 'Unknown error';
                $errorCode = $conn ? $conn->errno : 0;
                error_log("{$logPrefix} - Prepare failed [Error: {$errorMsg}, Code: {$errorCode}]");
                return false;
            }
            
            $studentId = $data['student_id'] ?? '';
            $departmentId = $data['department_id'] ?? null;
            $seasonYear = $data['season_year'] ?? '';
            $seasonName = $data['season_name'] ?? '';
            $depotName = $data['depot_name'] ?? '';
            $routeFrom = $data['route_from'] ?? '';
            $routeTo = $data['route_to'] ?? '';
            $changePoint = $data['change_point'] ?? '';
            $distanceKm = floatval($data['distance_km'] ?? 0);
            $notes = $data['notes'] ?? null;
            
            // Validate critical fields - only route_from and route_to are required
            if (empty($studentId) || empty($seasonYear) || empty($routeFrom) || empty($routeTo)) {
                error_log("{$logPrefix} - Validation failed. Missing required fields.");
                error_log("{$logPrefix} - studentId: {$studentId}, seasonYear: {$seasonYear}, routeFrom: {$routeFrom}, routeTo: {$routeTo}");
                $stmt->close();
                return false;
            }
            
            // Set defaults for optional fields
            if (empty($changePoint)) {
                $changePoint = '';
            }
            if ($distanceKm <= 0) {
                $distanceKm = 0;
            }
            
            $bindResult = $stmt->bind_param("ssssssssds",
                $studentId, $departmentId, $seasonYear, $seasonName, $depotName,
                $routeFrom, $routeTo, $changePoint, $distanceKm, $notes
            );
            
            if (!$bindResult) {
                error_log("{$logPrefix} - Bind param failed: " . $stmt->error);
                $stmt->close();
                return false;
            }
            
            if ($stmt->execute()) {
                $insertId = $this->db->lastInsertId();
                $stmt->close();
                
                if ($insertId) {
                    error_log("{$logPrefix} - Success. Insert ID: {$insertId}");
                    return $insertId;
                } else {
                    error_log("{$logPrefix} - Execute succeeded but no insert ID returned");
                    return false;
                }
            } else {
                $error = $stmt->error;
                $errorCode = $stmt->errno;
                error_log("{$logPrefix} - Execute failed [Error: {$error}, Code: {$errorCode}]");
                $stmt->close();
                return false;
            }
        } catch (Exception $e) {
            error_log("{$logPrefix} - Exception: " . $e->getMessage());
            error_log("{$logPrefix} - Stack trace: " . $e->getTraceAsString());
            return false;
        } catch (Error $e) {
            error_log("{$logPrefix} - Fatal Error: " . $e->getMessage());
            error_log("{$logPrefix} - Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * Find request with details (joins)
     */
    public function findWithDetails($requestId) {
        $this->ensureTableStructure();
        
        $sql = "SELECT r.*, 
                s.student_fullname, s.student_email, s.student_id, s.student_gender, s.student_nic,
                d.department_name, d.department_id,
                hod.user_name as hod_approver_name,
                second.user_name as second_approver_name
                FROM `{$this->table}` r
                LEFT JOIN `student` s ON r.student_id = s.student_id
                LEFT JOIN `department` d ON r.department_id = d.department_id
                LEFT JOIN `user` hod ON r.hod_approver_id = hod.user_id
                LEFT JOIN `user` second ON r.second_approver_id = second.user_id
                WHERE r.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $requestId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Get all requests by student ID
     */
    public function getByStudentId($studentId) {
        $this->ensureTableStructure();
        
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
     * Get request by student ID and season year
     */
    public function getByStudentIdAndYear($studentId, $seasonYear) {
        $this->ensureTableStructure();
        
        $sql = "SELECT * FROM `{$this->table}` 
                WHERE `student_id` = ? AND `season_year` = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ss", $studentId, $seasonYear);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Check if student has existing request for season year
     */
    public function hasExistingRequest($studentId, $seasonYear) {
        $request = $this->getByStudentIdAndYear($studentId, $seasonYear);
        return $request !== null;
    }
    
    /**
     * Get pending requests for HOD approval
     */
    public function getPendingHODRequests($departmentId) {
        $this->ensureTableStructure();
        
        $sql = "SELECT r.*, 
                s.student_fullname, s.student_email, s.student_id, s.student_gender, s.student_nic,
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
     * Get pending requests for second approval
     */
    public function getPendingSecondRequests($approverRole) {
        $this->ensureTableStructure();
        
        $sql = "SELECT r.*, 
                s.student_fullname, s.student_email, s.student_id, s.student_gender, s.student_nic,
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
     * Get requests for SAO processing
     */
    public function getRequestsForSAO() {
        $this->ensureTableStructure();
        
        $sql = "SELECT r.*, 
                s.student_fullname, s.student_email, s.student_id, s.student_gender, s.student_nic,
                d.department_name, d.department_id,
                hod.user_name as hod_approver_name,
                second.user_name as second_approver_name,
                (SELECT COUNT(*) FROM `{$this->paymentTable}` p WHERE p.request_id = r.id) as has_payment
                FROM `{$this->table}` r
                INNER JOIN `student` s ON r.student_id = s.student_id
                LEFT JOIN `department` d ON r.department_id = d.department_id
                LEFT JOIN `user` hod ON r.hod_approver_id = hod.user_id
                LEFT JOIN `user` second ON r.second_approver_id = second.user_id
                ORDER BY r.created_at DESC";
        
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
        $this->ensureTableStructure();
        
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
     * Update second approval
     */
    public function updateSecondApproval($requestId, $approverId, $approverRole, $approved, $comments = '') {
        $this->ensureTableStructure();
        
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
     * Update request status
     */
    public function updateStatus($requestId, $status) {
        $sql = "UPDATE `{$this->table}` SET `status` = ? WHERE `id` = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("si", $status, $requestId);
        return $stmt->execute();
    }
    
    /**
     * Create payment collection
     */
    public function createPaymentCollection($requestId, $studentId, $paidAmount, $seasonRate, $collectedBy, $paymentMethod = 'cash', $paymentReference = null, $notes = null) {
        $this->ensureTableStructure();
        
        $sql = "INSERT INTO `{$this->paymentTable}` 
                (`request_id`, `student_id`, `paid_amount`, `season_rate`, `status`, 
                 `payment_method`, `payment_reference`, `collected_by`, `notes`, `payment_date`) 
                VALUES (?, ?, ?, ?, 'paid', ?, ?, ?, ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        
        if (!$stmt) {
            $conn = $this->db->getConnection();
            error_log("BusSeasonRequestModel::createPaymentCollection - Prepare failed: " . ($conn ? $conn->error : 'Unknown error'));
            return false;
        }
        
        $stmt->bind_param("iiddsisi",
            $requestId, $studentId, $paidAmount, $seasonRate,
            $paymentMethod, $paymentReference, $collectedBy, $notes
        );
        
        if ($stmt->execute()) {
            $insertId = $this->db->lastInsertId();
            $stmt->close();
            return $insertId;
        } else {
            error_log("BusSeasonRequestModel::createPaymentCollection - Execute failed: " . $stmt->error);
            $stmt->close();
            return false;
        }
    }
    
    /**
     * Get payment collection by request ID
     */
    public function getPaymentCollectionByRequestId($requestId) {
        $sql = "SELECT p.*, 
                u.user_name as collected_by_name
                FROM `{$this->paymentTable}` p
                LEFT JOIN `user` u ON p.collected_by = u.user_id
                WHERE p.request_id = ?
                ORDER BY p.created_at DESC
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $requestId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Get payment collection by ID
     */
    public function getPaymentCollectionById($paymentId) {
        $sql = "SELECT p.*, 
                u.user_name as collected_by_name
                FROM `{$this->paymentTable}` p
                LEFT JOIN `user` u ON p.collected_by = u.user_id
                WHERE p.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $paymentId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Check if payment collection exists for request
     */
    public function hasPaymentCollection($requestId) {
        $payment = $this->getPaymentCollectionByRequestId($requestId);
        return $payment !== null;
    }
    
    /**
     * Update payment status
     */
    public function updatePaymentStatus($paymentId, $status, $data = []) {
        $this->ensureTableStructure();
        
        $updates = ['status' => $status];
        
        if (isset($data['total_amount'])) $updates['total_amount'] = $data['total_amount'];
        if (isset($data['student_paid'])) $updates['student_paid'] = $data['student_paid'];
        if (isset($data['slgti_paid'])) $updates['slgti_paid'] = $data['slgti_paid'];
        if (isset($data['ctb_paid'])) $updates['ctb_paid'] = $data['ctb_paid'];
        if (isset($data['season_rate'])) $updates['season_rate'] = $data['season_rate'];
        if (isset($data['remaining_balance'])) $updates['remaining_balance'] = $data['remaining_balance'];
        if (isset($data['payment_reference'])) $updates['payment_reference'] = $data['payment_reference'];
        
        if ($status === 'issued') {
            $updates['issued_at'] = date('Y-m-d H:i:s');
        }
        
        $setParts = [];
        $types = '';
        $values = [];
        
        foreach ($updates as $key => $value) {
            $setParts[] = "`{$key}` = ?";
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
            $values[] = $value;
        }
        
        $types .= 'i'; // for paymentId
        $values[] = $paymentId;
        
        $sql = "UPDATE `{$this->paymentTable}` SET " . implode(', ', $setParts) . " WHERE `id` = ?";
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("BusSeasonRequestModel::updatePaymentStatus - Prepare failed: " . $this->db->getConnection()->error);
            return false;
        }
        
        $stmt->bind_param($types, ...$values);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Get all payment collections with filters
     */
    public function getAllPaymentCollections($filters = []) {
        $this->ensureTableStructure();
        
        $sql = "SELECT p.id as payment_id, p.paid_amount, p.season_rate, p.total_amount, p.student_paid, p.slgti_paid, p.ctb_paid, 
                p.remaining_balance, p.status as payment_status, p.payment_date, p.payment_method, 
                p.payment_reference, p.collected_by, p.notes as payment_notes, p.issued_at,
                p.student_id as payment_student_id,
                r.id as request_id, r.student_id as request_student_id, r.season_year, r.season_name, r.depot_name, r.route_from, r.route_to, r.change_point, r.distance_km,
                r.status as request_status,
                s.student_fullname, s.student_email, s.student_nic,
                d.department_name, d.department_id,
                hod.user_name as hod_approver_name,
                second.user_name as second_approver_name,
                u.user_name as collected_by_name
                FROM `{$this->paymentTable}` p
                INNER JOIN `{$this->table}` r ON p.request_id = r.id
                LEFT JOIN `student` s ON p.student_id = s.student_id
                LEFT JOIN `department` d ON r.department_id = d.department_id
                LEFT JOIN `user` hod ON r.hod_approver_id = hod.user_id
                LEFT JOIN `user` second ON r.second_approver_id = second.user_id
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
            $sql .= " AND (p.student_id = ? OR r.student_id = ?)";
            $params[] = $filters['student_id'];
            $params[] = $filters['student_id'];
            $types .= 'ss';
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND LOWER(p.status) = LOWER(?)";
            $params[] = $filters['status'];
            $types .= 's';
        }
        
        if (!empty($filters['month'])) {
            $sql .= " AND DATE_FORMAT(p.payment_date, '%Y-%m') = ?";
            $params[] = $filters['month'];
            $types .= 's';
        }
        
        $sql .= " ORDER BY p.payment_date DESC, r.created_at DESC";
        
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
