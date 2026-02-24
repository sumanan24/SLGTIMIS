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
     * Ensure table structure exists
     */
    public function ensureTableStructure() {
        // Check if season_requests table exists
        $checkTable = $this->db->query("SHOW TABLES LIKE '{$this->table}'");
        if ($checkTable->num_rows == 0) {
            // Create season_requests table
            $sql = "CREATE TABLE IF NOT EXISTS `{$this->table}` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `student_id` VARCHAR(50) NOT NULL,
                `department_id` VARCHAR(50) DEFAULT NULL,
                `season_year` VARCHAR(10) NOT NULL,
                `season_name` VARCHAR(100) DEFAULT NULL,
                `depot_name` VARCHAR(100) DEFAULT NULL,
                `route_from` VARCHAR(255) NOT NULL,
                `route_to` VARCHAR(255) NOT NULL,
                `change_point` VARCHAR(255) DEFAULT NULL,
                `distance_km` DECIMAL(10,2) DEFAULT 0,
                `status` VARCHAR(20) DEFAULT 'pending',
                `notes` TEXT DEFAULT NULL,
                `hod_approver_id` INT(11) DEFAULT NULL,
                `hod_approval_date` DATETIME DEFAULT NULL,
                `hod_comments` TEXT DEFAULT NULL,
                `second_approver_id` INT(11) DEFAULT NULL,
                `second_approver_role` VARCHAR(10) DEFAULT NULL,
                `second_approval_date` DATETIME DEFAULT NULL,
                `second_comments` TEXT DEFAULT NULL,
                `approved_by` INT(11) DEFAULT NULL,
                `approved_at` DATETIME DEFAULT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `student_id` (`student_id`),
                KEY `status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $this->db->query($sql);
        }
        
        // Check if season_payments table exists
        $checkPaymentTable = $this->db->query("SHOW TABLES LIKE '{$this->paymentTable}'");
        if ($checkPaymentTable->num_rows == 0) {
            // Create season_payments table
            $sql = "CREATE TABLE IF NOT EXISTS `{$this->paymentTable}` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `request_id` INT(11) NOT NULL,
                `student_id` VARCHAR(50) NOT NULL,
                `paid_amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
                `season_rate` DECIMAL(10,2) DEFAULT 0,
                `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
                `student_paid` DECIMAL(10,2) DEFAULT 0,
                `slgti_paid` DECIMAL(10,2) DEFAULT 0,
                `ctb_paid` DECIMAL(10,2) DEFAULT 0,
                `remaining_balance` DECIMAL(10,2) NOT NULL DEFAULT 0,
                `status` VARCHAR(20) DEFAULT 'Paid',
                `payment_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `payment_method` VARCHAR(20) DEFAULT 'Cash',
                `payment_reference` VARCHAR(255) DEFAULT NULL,
                `collected_by` VARCHAR(50) DEFAULT NULL,
                `notes` TEXT DEFAULT NULL,
                `issued_at` DATETIME DEFAULT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `request_id` (`request_id`),
                KEY `student_id` (`student_id`),
                KEY `status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $this->db->query($sql);
        }
        
        // Add missing columns if they don't exist
        $this->addMissingColumns();
        
        return true;
    }
    
    /**
     * Add missing columns to tables
     */
    private function addMissingColumns() {
        $columns = [
            'season_requests' => [
                'hod_approver_id' => "ALTER TABLE `{$this->table}` ADD COLUMN `hod_approver_id` INT(11) DEFAULT NULL AFTER `status`",
                'hod_approval_date' => "ALTER TABLE `{$this->table}` ADD COLUMN `hod_approval_date` DATETIME DEFAULT NULL AFTER `hod_approver_id`",
                'hod_comments' => "ALTER TABLE `{$this->table}` ADD COLUMN `hod_comments` TEXT DEFAULT NULL AFTER `hod_approval_date`",
                'second_approver_id' => "ALTER TABLE `{$this->table}` ADD COLUMN `second_approver_id` INT(11) DEFAULT NULL AFTER `hod_comments`",
                'second_approver_role' => "ALTER TABLE `{$this->table}` ADD COLUMN `second_approver_role` VARCHAR(10) DEFAULT NULL AFTER `second_approver_id`",
                'second_approval_date' => "ALTER TABLE `{$this->table}` ADD COLUMN `second_approval_date` DATETIME DEFAULT NULL AFTER `second_approver_role`",
                'second_comments' => "ALTER TABLE `{$this->table}` ADD COLUMN `second_comments` TEXT DEFAULT NULL AFTER `second_approval_date`",
                'approved_by' => "ALTER TABLE `{$this->table}` ADD COLUMN `approved_by` INT(11) DEFAULT NULL AFTER `second_comments`",
                'approved_at' => "ALTER TABLE `{$this->table}` ADD COLUMN `approved_at` DATETIME DEFAULT NULL AFTER `approved_by`"
            ],
            'season_payments' => [
                'total_amount' => "ALTER TABLE `{$this->paymentTable}` ADD COLUMN `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `season_rate`",
                'student_paid' => "ALTER TABLE `{$this->paymentTable}` ADD COLUMN `student_paid` DECIMAL(10,2) DEFAULT 0 AFTER `total_amount`",
                'slgti_paid' => "ALTER TABLE `{$this->paymentTable}` ADD COLUMN `slgti_paid` DECIMAL(10,2) DEFAULT 0 AFTER `student_paid`",
                'ctb_paid' => "ALTER TABLE `{$this->paymentTable}` ADD COLUMN `ctb_paid` DECIMAL(10,2) DEFAULT 0 AFTER `slgti_paid`",
                'remaining_balance' => "ALTER TABLE `{$this->paymentTable}` ADD COLUMN `remaining_balance` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `ctb_paid`",
                'issued_at' => "ALTER TABLE `{$this->paymentTable}` ADD COLUMN `issued_at` DATETIME DEFAULT NULL AFTER `payment_date`"
            ]
        ];
        
        foreach ($columns['season_requests'] as $column => $sql) {
            $check = $this->db->query("SHOW COLUMNS FROM `{$this->table}` LIKE '{$column}'");
            if ($check->num_rows == 0) {
                $this->db->query($sql);
            }
        }
        
        foreach ($columns['season_payments'] as $column => $sql) {
            $check = $this->db->query("SHOW COLUMNS FROM `{$this->paymentTable}` LIKE '{$column}'");
            if ($check->num_rows == 0) {
                $this->db->query($sql);
            }
        }
    }
    
    /**
     * Create new request
     */
    public function create($data) {
        $this->ensureTableStructure();
        
        try {
            // Prepare values with defaults
            $studentId = $data['student_id'] ?? '';
            $departmentId = $data['department_id'] ?? null;
            $seasonYear = $data['season_year'] ?? '';
            $seasonName = $data['season_name'] ?? '';
            $depotName = $data['depot_name'] ?? '';
            $routeFrom = $data['route_from'] ?? '';
            $routeTo = $data['route_to'] ?? '';
            $changePoint = $data['change_point'] ?? '';
            $distanceKm = isset($data['distance_km']) ? (float)$data['distance_km'] : 0;
            $notes = $data['notes'] ?? '';
            
            // Handle NULL department_id in SQL
            if ($departmentId === null || $departmentId === '') {
                $sql = "INSERT INTO `{$this->table}` 
                        (`student_id`, `department_id`, `season_year`, `season_name`, `depot_name`, 
                         `route_from`, `route_to`, `change_point`, `distance_km`, `status`, `notes`) 
                        VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)";
                
                $stmt = $this->db->prepare($sql);
                if (!$stmt) {
                    try {
                        $conn = $this->db->getConnection();
                        $error = $conn ? $conn->error : 'Unknown error';
                    } catch (Exception $e) {
                        $error = 'Database connection error: ' . $e->getMessage();
                    }
                    error_log("BusSeasonRequestModel::create - Prepare failed: " . $error . " | SQL: " . $sql);
                    return false;
                }
                
                $stmt->bind_param("sssssssds",
                    $studentId,
                    $seasonYear,
                    $seasonName,
                    $depotName,
                    $routeFrom,
                    $routeTo,
                    $changePoint,
                    $distanceKm,
                    $notes
                );
            } else {
                $sql = "INSERT INTO `{$this->table}` 
                        (`student_id`, `department_id`, `season_year`, `season_name`, `depot_name`, 
                         `route_from`, `route_to`, `change_point`, `distance_km`, `status`, `notes`) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)";
                
                $stmt = $this->db->prepare($sql);
                if (!$stmt) {
                    try {
                        $conn = $this->db->getConnection();
                        $error = $conn ? $conn->error : 'Unknown error';
                    } catch (Exception $e) {
                        $error = 'Database connection error: ' . $e->getMessage();
                    }
                    error_log("BusSeasonRequestModel::create - Prepare failed: " . $error . " | SQL: " . $sql);
                    return false;
                }
                
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
            }
            
            if ($stmt->execute()) {
                $insertId = $this->db->lastInsertId();
                error_log("BusSeasonRequestModel::create - Request created successfully. ID: " . $insertId);
                $stmt->close();
                return $insertId;
            } else {
                $error = $stmt->error ?? 'Unknown error';
                error_log("BusSeasonRequestModel::create - Execute failed: " . $error . " | SQL: " . $sql);
                $stmt->close();
                return false;
            }
        } catch (Exception $e) {
            error_log("BusSeasonRequestModel::create - Exception: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
            return false;
        } catch (Error $e) {
            error_log("BusSeasonRequestModel::create - Fatal Error: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * Find request with details
     */
    public function findWithDetails($id) {
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
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Get requests by student ID
     */
    public function getByStudentId($studentId) {
        $sql = "SELECT r.*, 
                d.department_name,
                hod.user_name as hod_approver_name,
                second.user_name as second_approver_name
                FROM `{$this->table}` r
                LEFT JOIN `department` d ON r.department_id = d.department_id
                LEFT JOIN `user` hod ON r.hod_approver_id = hod.user_id
                LEFT JOIN `user` second ON r.second_approver_id = second.user_id
                WHERE r.student_id = ?
                ORDER BY r.created_at DESC";
        
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
     * Check if student has existing request for season year
     * Now allows multiple requests per season year (one per month)
     */
    public function hasExistingRequest($studentId, $seasonYear) {
        $this->ensureTableStructure();
        
        try {
            // Allow multiple requests per season year - students can request each month
            // Only check if there's a request in the current month
            $currentMonth = date('Y-m');
            $sql = "SELECT COUNT(*) as count FROM `{$this->table}` 
                    WHERE `student_id` = ? 
                    AND `season_year` = ?
                    AND DATE_FORMAT(`created_at`, '%Y-%m') = ?";
            
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                error_log("BusSeasonRequestModel::hasExistingRequest - Prepare failed: " . ($this->db->error ?? 'Unknown error'));
                return false; // Don't block if there's an error
            }
            
            $stmt->bind_param("sss", $studentId, $seasonYear, $currentMonth);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result) {
                $row = $result->fetch_assoc();
                return (int)($row['count'] ?? 0) > 0;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("BusSeasonRequestModel::hasExistingRequest - Error: " . $e->getMessage());
            return false; // Don't block if there's an error
        }
    }
    
    /**
     * Get student IDs that already have a request for the current month in the given season year.
     * Used to exclude them from the "Create Request" dropdown so they don't appear.
     */
    public function getStudentIdsWithRequestForCurrentMonth($seasonYear) {
        $this->ensureTableStructure();
        $currentMonth = date('Y-m');
        $sql = "SELECT DISTINCT `student_id` FROM `{$this->table}` 
                WHERE `season_year` = ? 
                AND DATE_FORMAT(`created_at`, '%Y-%m') = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param("ss", $seasonYear, $currentMonth);
        $stmt->execute();
        $result = $stmt->get_result();
        $ids = [];
        while ($result && $row = $result->fetch_assoc()) {
            $ids[] = $row['student_id'];
        }
        return $ids;
    }
    
    /**
     * Check if student has existing request for a specific month
     */
    public function hasExistingRequestForMonth($studentId, $seasonYear, $month = null) {
        if ($month === null) {
            $month = date('Y-m');
        }
        
        $sql = "SELECT COUNT(*) as count FROM `{$this->table}` 
                WHERE `student_id` = ? 
                AND `season_year` = ?
                AND DATE_FORMAT(`created_at`, '%Y-%m') = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("sss", $studentId, $seasonYear, $month);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['count'] > 0;
    }
    
    /**
     * Get total number of requests for a student in a season year
     */
    public function getTotalRequestsForYear($studentId, $seasonYear) {
        $this->ensureTableStructure();
        
        try {
            $sql = "SELECT COUNT(*) as count FROM `{$this->table}` 
                    WHERE `student_id` = ? 
                    AND `season_year` = ?";
            
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                error_log("BusSeasonRequestModel::getTotalRequestsForYear - Prepare failed: " . ($this->db->error ?? 'Unknown error'));
                return 0;
            }
            
            $stmt->bind_param("ss", $studentId, $seasonYear);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result) {
                $row = $result->fetch_assoc();
                return (int)($row['count'] ?? 0);
            }
            
            return 0;
        } catch (Exception $e) {
            error_log("BusSeasonRequestModel::getTotalRequestsForYear - Error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Check if student has reached maximum requests per year
     * Maximum: 12 requests per year (one per month)
     */
    public function hasReachedMaxRequests($studentId, $seasonYear, $maxRequests = 12) {
        try {
            $totalRequests = $this->getTotalRequestsForYear($studentId, $seasonYear);
            return $totalRequests >= $maxRequests;
        } catch (Exception $e) {
            error_log("BusSeasonRequestModel::hasReachedMaxRequests - Error: " . $e->getMessage());
            return false; // Don't block if there's an error
        }
    }
    
    /**
     * Get pending requests for HOD approval
     */
    public function getPendingHODRequests($departmentId) {
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
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        
        return $data;
    }
    
    /**
     * Get pending requests for second approval
     */
    public function getPendingSecondRequests($approverRole) {
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
     * @param array $filters Optional: payment_filter ('needs_payment'|'issued'|'monthly_payment'|'all'), student_id, request_id
     *   - needs_payment: only requests that need initial payment (NOT issued yet)
     *   - issued: only requests with payment status = issued (or issued_at set)
     *   - monthly_payment: only issued requests (for collecting monthly payments)
     *   - all: no filter (shows all requests)
     */
    public function getRequestsForSAO($filters = []) {
        $this->ensureTableStructure();
        
        $paymentFilter = $filters['payment_filter'] ?? 'needs_payment';
        
        // Use request_id to connect season_requests and season_payments.
        // All payment counts/checks are per request (not just per student).
        $sql = "SELECT r.*, 
                s.student_fullname, s.student_email, s.student_id, s.student_gender, s.student_nic,
                d.department_name, d.department_id,
                (SELECT COUNT(*) FROM `{$this->paymentTable}` p WHERE p.request_id = r.id) as has_payment,
                (SELECT COUNT(*) FROM `{$this->paymentTable}` p WHERE p.request_id = r.id AND (LOWER(TRIM(p.status)) = 'issued' OR p.issued_at IS NOT NULL)) as has_issued_payment,
                (SELECT COUNT(*) FROM `{$this->table}` r2 WHERE r2.student_id = r.student_id AND r2.season_year = r.season_year) as total_requests_for_student
                FROM `{$this->table}` r
                INNER JOIN `student` s ON r.student_id = s.student_id
                LEFT JOIN `department` d ON r.department_id = d.department_id";
        
        $conditions = [];
        $params = [];
        $types = '';
        
        // Only apply approval filter if NOT showing 'all' requests
        if ($paymentFilter !== 'all') {
            // Only show processable requests for SAO - must be approved at some level
            // Only show: hod_approved, approved, paid, issued
            $conditions[] = "(LOWER(TRIM(r.status)) = 'hod_approved' 
                             OR LOWER(TRIM(r.status)) = 'approved' 
                             OR LOWER(TRIM(r.status)) = 'paid' 
                             OR LOWER(TRIM(r.status)) = 'issued')";
        }
        
        if ($paymentFilter === 'needs_payment') {
            // Show requests that need initial payment: exclude requests that already have an issued payment
            // This is for collecting the FIRST payment only, not monthly payments
            $conditions[] = "NOT EXISTS (
                SELECT 1 FROM `{$this->paymentTable}` p 
                WHERE p.request_id = r.id 
                AND (LOWER(TRIM(p.status)) = 'issued' OR p.issued_at IS NOT NULL)
            )";
        } elseif ($paymentFilter === 'issued') {
            // Show only requests that have at least one issued payment
            $conditions[] = "EXISTS (
                SELECT 1 FROM `{$this->paymentTable}` p 
                WHERE p.request_id = r.id 
                AND (LOWER(TRIM(p.status)) = 'issued' OR p.issued_at IS NOT NULL)
            )";
        } elseif ($paymentFilter === 'monthly_payment') {
            // Show only issued requests (for collecting monthly payments)
            $conditions[] = "EXISTS (
                SELECT 1 FROM `{$this->paymentTable}` p 
                WHERE p.request_id = r.id 
                AND (LOWER(TRIM(p.status)) = 'issued' OR p.issued_at IS NOT NULL)
            )";
        }
        /* 'all' = no payment filter - shows all approved requests */
        
        // Search by name, NIC, or student ID
        if (!empty($filters['search'])) {
            $searchTerm = trim($filters['search']);
            $conditions[] = "(r.student_id LIKE ? 
                             OR s.student_fullname LIKE ? 
                             OR s.student_nic LIKE ?)";
            $searchParam = "%{$searchTerm}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $types .= 'sss';
        }
        
        if (!empty($filters['student_id'])) {
            $conditions[] = "r.student_id = ?";
            $params[] = $filters['student_id'];
            $types .= 's';
        }
        if (!empty($filters['request_id'])) {
            $conditions[] = "r.id = ?";
            $params[] = $filters['request_id'];
            $types .= 'i';
        }
        
        // Apply WHERE clause only if conditions exist
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $sql .= " ORDER BY r.created_at DESC";
        
        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
            if ($stmt) {
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $result = $stmt->get_result();
            } else {
                error_log("BusSeasonRequestModel::getRequestsForSAO - Prepare failed: " . ($this->db->getConnection()->error ?? 'Unknown error'));
                return [];
            }
        } else {
            $result = $this->db->query($sql);
            if (!$result) {
                error_log("BusSeasonRequestModel::getRequestsForSAO - Query failed: " . ($this->db->getConnection()->error ?? 'Unknown error'));
                return [];
            }
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
        
        // Map payment method to DB enum values (case-sensitive: 'Cash','Bank Transfer')
        $methodMap = ['cash' => 'Cash', 'bank_transfer' => 'Bank Transfer', 'cheque' => 'Cash'];
        $paymentMethodDb = $methodMap[strtolower(trim($paymentMethod))] ?? 'Cash';
        
        // Required columns: total_amount, remaining_balance (NOT NULL in schema)
        $totalAmount = ($seasonRate > 0) ? (float)$seasonRate : (float)$paidAmount;
        $remainingBalance = max(0, $totalAmount - (float)$paidAmount);
        
        // Status enum is case-sensitive: 'Paid' or 'Completed'
        $sql = "INSERT INTO `{$this->paymentTable}` 
                (`request_id`, `student_id`, `paid_amount`, `season_rate`, `total_amount`, 
                 `student_paid`, `slgti_paid`, `ctb_paid`, `remaining_balance`, `status`, 
                 `payment_method`, `payment_reference`, `collected_by`, `notes`, `payment_date`) 
                VALUES (?, ?, ?, ?, ?, ?, 0, 0, ?, 'Paid', ?, ?, ?, ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        
        if (!$stmt) {
            $conn = $this->db->getConnection();
            error_log("BusSeasonRequestModel::createPaymentCollection - Prepare failed: " . ($conn ? $conn->error : 'Unknown error'));
            return false;
        }
        
        // student_id VARCHAR, collected_by VARCHAR (stores user_id as string)
        $collectedByStr = (string)$collectedBy;
        $studentPaid = (float)$paidAmount;
        $paymentRef = $paymentReference !== null ? (string)$paymentReference : '';
        $notesStr = $notes !== null ? (string)$notes : '';
        $stmt->bind_param("isdddddssss",
            $requestId, $studentId, $paidAmount, $seasonRate, $totalAmount,
            $studentPaid, $remainingBalance,
            $paymentMethodDb, $paymentRef, $collectedByStr, $notesStr
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
     * Get all payment collections for a request
     */
    public function getAllPaymentsByRequestId($requestId) {
        $sql = "SELECT p.*, 
                u.user_name as collected_by_name
                FROM `{$this->paymentTable}` p
                LEFT JOIN `user` u ON p.collected_by = u.user_id
                WHERE p.request_id = ?
                ORDER BY p.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $requestId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $payments = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $payments[] = $row;
            }
        }
        
        return $payments;
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
        
        if (isset($data['paid_amount'])) $updates['paid_amount'] = $data['paid_amount'];
        if (isset($data['payment_date'])) $updates['payment_date'] = $data['payment_date'];
        if (isset($data['payment_method'])) $updates['payment_method'] = $data['payment_method'];
        if (isset($data['notes'])) $updates['notes'] = $data['notes'];
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
            try {
                $conn = $this->db->getConnection();
                $error = $conn ? $conn->error : 'Unknown error';
            } catch (Exception $e) {
                $error = 'Database connection error: ' . $e->getMessage();
            }
            error_log("BusSeasonRequestModel::updatePaymentStatus - Prepare failed: " . $error . " | SQL: " . $sql);
            return false;
        }
        
        $stmt->bind_param($types, ...$values);
        $result = $stmt->execute();
        
        if (!$result) {
            $error = $stmt->error ?? 'Unknown error';
            error_log("BusSeasonRequestModel::updatePaymentStatus - Execute failed: " . $error);
            $stmt->close();
            return false;
        }
        
        $stmt->close();
        return true;
    }
    
    /**
     * Delete payment collection by ID
     */
    public function deletePaymentCollection($paymentId) {
        $this->ensureTableStructure();
        
        try {
            $sql = "DELETE FROM `{$this->paymentTable}` WHERE `id` = ?";
            $stmt = $this->db->prepare($sql);
            
            if (!$stmt) {
                try {
                    $conn = $this->db->getConnection();
                    $error = $conn ? $conn->error : 'Unknown error';
                } catch (Exception $e) {
                    $error = 'Database connection error: ' . $e->getMessage();
                }
                error_log("BusSeasonRequestModel::deletePaymentCollection - Prepare failed: " . $error);
                return false;
            }
            
            $stmt->bind_param("i", $paymentId);
            
            if ($stmt->execute()) {
                $stmt->close();
                return true;
            } else {
                $error = $stmt->error ?? 'Unknown error';
                error_log("BusSeasonRequestModel::deletePaymentCollection - Execute failed: " . $error);
                $stmt->close();
                return false;
            }
        } catch (Exception $e) {
            error_log("BusSeasonRequestModel::deletePaymentCollection - Exception: " . $e->getMessage());
            return false;
        } catch (Error $e) {
            error_log("BusSeasonRequestModel::deletePaymentCollection - Fatal Error: " . $e->getMessage());
            return false;
        }
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
            $sql .= " AND p.student_id = ?";
            $params[] = $filters['student_id'];
            $types .= 's';
        }
        
        if (!empty($filters['month'])) {
            $sql .= " AND MONTH(p.payment_date) = ?";
            $params[] = $filters['month'];
            $types .= 'i';
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND LOWER(TRIM(p.status)) = LOWER(TRIM(?))";
            $params[] = $filters['status'];
            $types .= 's';
        }
        
        $sql .= " ORDER BY p.payment_date DESC, p.created_at DESC";
        
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
