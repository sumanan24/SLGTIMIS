<?php
/**
 * Activity Log Model
 * Tracks all activities in the system
 */

class ActivityLogModel extends Model {
    protected $table = 'activity_log';
    
    protected function getPrimaryKey() {
        return 'id';
    }
    
    /**
     * Add columns to existing activity_log table if they don't exist
     */
    public function addRequiredColumnsIfNotExists() {
        // Check if table exists, if not create it
        $checkTable = "SHOW TABLES LIKE '{$this->table}'";
        $result = $this->db->query($checkTable);
        
        if (!$result || $result->num_rows == 0) {
            // Create new table with all columns
            $sql = "CREATE TABLE `{$this->table}` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `user_id` INT(11) DEFAULT NULL,
                `user_type` VARCHAR(20) DEFAULT NULL COMMENT 'student, user, admin',
                `user_name` VARCHAR(100) DEFAULT NULL,
                `activity_type` VARCHAR(50) NOT NULL COMMENT 'CREATE, UPDATE, DELETE, VIEW, APPROVE, REJECT, etc.',
                `module` VARCHAR(100) NOT NULL COMMENT 'bus_season_request, on_peak_request, student, etc.',
                `record_id` VARCHAR(50) DEFAULT NULL COMMENT 'ID of the affected record',
                `description` TEXT DEFAULT NULL COMMENT 'Human-readable description',
                `old_values` TEXT DEFAULT NULL COMMENT 'JSON of old values before change',
                `new_values` TEXT DEFAULT NULL COMMENT 'JSON of new values after change',
                `ip_address` VARCHAR(45) DEFAULT NULL COMMENT 'IPv4 or IPv6',
                `mac_address` VARCHAR(17) DEFAULT NULL COMMENT 'MAC address if available',
                `user_agent` TEXT DEFAULT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_user_id` (`user_id`),
                KEY `idx_user_type` (`user_type`),
                KEY `idx_activity_type` (`activity_type`),
                KEY `idx_module` (`module`),
                KEY `idx_record_id` (`record_id`),
                KEY `idx_created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            $this->db->query($sql);
        } else {
            // Table exists, add missing columns
            $columns = [
                'user_id' => "ALTER TABLE `{$this->table}` ADD COLUMN `user_id` INT(11) DEFAULT NULL",
                'user_type' => "ALTER TABLE `{$this->table}` ADD COLUMN `user_type` VARCHAR(20) DEFAULT NULL COMMENT 'student, user, admin'",
                'user_name' => "ALTER TABLE `{$this->table}` ADD COLUMN `user_name` VARCHAR(100) DEFAULT NULL",
                'activity_type' => "ALTER TABLE `{$this->table}` ADD COLUMN `activity_type` VARCHAR(50) NOT NULL COMMENT 'CREATE, UPDATE, DELETE, etc.'",
                'module' => "ALTER TABLE `{$this->table}` ADD COLUMN `module` VARCHAR(100) DEFAULT NULL COMMENT 'bus_season_request, etc.'",
                'record_id' => "ALTER TABLE `{$this->table}` ADD COLUMN `record_id` VARCHAR(50) DEFAULT NULL COMMENT 'ID of the affected record'",
                'description' => "ALTER TABLE `{$this->table}` ADD COLUMN `description` TEXT DEFAULT NULL COMMENT 'Human-readable description'",
                'old_values' => "ALTER TABLE `{$this->table}` ADD COLUMN `old_values` TEXT DEFAULT NULL COMMENT 'JSON of old values before change'",
                'new_values' => "ALTER TABLE `{$this->table}` ADD COLUMN `new_values` TEXT DEFAULT NULL COMMENT 'JSON of new values after change'",
                'ip_address' => "ALTER TABLE `{$this->table}` ADD COLUMN `ip_address` VARCHAR(45) DEFAULT NULL COMMENT 'IPv4 or IPv6'",
                'mac_address' => "ALTER TABLE `{$this->table}` ADD COLUMN `mac_address` VARCHAR(17) DEFAULT NULL COMMENT 'MAC address if available'",
                'user_agent' => "ALTER TABLE `{$this->table}` ADD COLUMN `user_agent` TEXT DEFAULT NULL"
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
        }
        
        return true;
    }
    
    /**
     * Get client IP address
     */
    public function getClientIP() {
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_REAL_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];
        
        foreach ($ipKeys as $key) {
            if (isset($_SERVER[$key]) && !empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                
                // Handle comma-separated IPs (from proxy)
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                
                // Validate IP
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        // Fallback to REMOTE_ADDR (may be local IP behind proxy)
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Get MAC address (attempt - may not be available in web context)
     */
    public function getMACAddress() {
        // MAC address is typically not available via HTTP in web applications
        // It's a security/browser limitation
        // We can try to get it from ARP table on the server side for local network
        
        $ip = $this->getClientIP();
        
        // Only attempt on Linux/Unix systems for local IPs
        if (PHP_OS_FAMILY === 'Linux' || PHP_OS_FAMILY === 'Unix') {
            // Check if IP is local
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE) === false) {
                // Local IP - try to get MAC from ARP table
                try {
                    $mac = @exec("arp -n " . escapeshellarg($ip) . " | awk '{print $3}'");
                    if (!empty($mac) && preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $mac)) {
                        return $mac;
                    }
                } catch (Exception $e) {
                    // Silent fail
                }
            }
        }
        
        // Return null if MAC cannot be determined (most common case in web apps)
        return null;
    }
    
    /**
     * Get user agent
     */
    public function getUserAgent() {
        return $_SERVER['HTTP_USER_AGENT'] ?? null;
    }
    
    /**
     * Log an activity
     */
    public function logActivity($data) {
        $this->addRequiredColumnsIfNotExists();
        
        // Get user information
        $userId = null;
        $userType = null;
        $userName = null;
        
        if (isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
            $userType = $_SESSION['user_table'] ?? 'user';
            $userName = $_SESSION['user_name'] ?? $_SESSION['user_fullname'] ?? null;
        }
        
        // Get system information
        $ipAddress = $this->getClientIP();
        $macAddress = $this->getMACAddress();
        $userAgent = $this->getUserAgent();
        
        // Prepare values for JSON storage
        $oldValues = null;
        $newValues = null;
        
        if (isset($data['old_values']) && !empty($data['old_values'])) {
            $oldValues = is_string($data['old_values']) ? $data['old_values'] : json_encode($data['old_values']);
        }
        
        if (isset($data['new_values']) && !empty($data['new_values'])) {
            $newValues = is_string($data['new_values']) ? $data['new_values'] : json_encode($data['new_values']);
        }
        
        // Extract required values to variables (bind_param requires references)
        $activityType = $data['activity_type'] ?? null;
        $module = $data['module'] ?? null;
        $recordId = $data['record_id'] ?? null;
        $description = $data['description'] ?? null;
        
        $sql = "INSERT INTO `{$this->table}` 
                (`user_id`, `user_type`, `user_name`, `activity_type`, `module`, `record_id`, 
                 `description`, `old_values`, `new_values`, `ip_address`, `mac_address`, `user_agent`) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("isssssssssss",
            $userId,
            $userType,
            $userName,
            $activityType,
            $module,
            $recordId,
            $description,
            $oldValues,
            $newValues,
            $ipAddress,
            $macAddress,
            $userAgent
        );
        
        return $stmt->execute();
    }
    
    /**
     * Get activity logs with filters
     */
    public function getActivityLogs($filters = [], $limit = 100, $offset = 0) {
        $this->createTableIfNotExists();
        
        $sql = "SELECT * FROM `{$this->table}` WHERE 1=1";
        $params = [];
        $types = '';
        
        if (!empty($filters['module'])) {
            $sql .= " AND `module` = ?";
            $params[] = $filters['module'];
            $types .= 's';
        }
        
        if (!empty($filters['activity_type'])) {
            $sql .= " AND `activity_type` = ?";
            $params[] = $filters['activity_type'];
            $types .= 's';
        }
        
        if (!empty($filters['user_id'])) {
            $sql .= " AND `user_id` = ?";
            $params[] = $filters['user_id'];
            $types .= 'i';
        }
        
        if (!empty($filters['record_id'])) {
            $sql .= " AND `record_id` = ?";
            $params[] = $filters['record_id'];
            $types .= 's';
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(`created_at`) >= ?";
            $params[] = $filters['date_from'];
            $types .= 's';
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(`created_at`) <= ?";
            $params[] = $filters['date_to'];
            $types .= 's';
        }
        
        $sql .= " ORDER BY `created_at` DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        
        $stmt = $this->db->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $logs = [];
        while ($row = $result->fetch_assoc()) {
            // Decode JSON values if they exist
            if (!empty($row['old_values'])) {
                $row['old_values'] = json_decode($row['old_values'], true);
            }
            if (!empty($row['new_values'])) {
                $row['new_values'] = json_decode($row['new_values'], true);
            }
            $logs[] = $row;
        }
        
        return $logs;
    }
    
    /**
     * Get count of activity logs with filters
     */
    public function getActivityLogCount($filters = []) {
        $this->createTableIfNotExists();
        
        $sql = "SELECT COUNT(*) as total FROM `{$this->table}` WHERE 1=1";
        $params = [];
        $types = '';
        
        if (!empty($filters['module'])) {
            $sql .= " AND `module` = ?";
            $params[] = $filters['module'];
            $types .= 's';
        }
        
        if (!empty($filters['activity_type'])) {
            $sql .= " AND `activity_type` = ?";
            $params[] = $filters['activity_type'];
            $types .= 's';
        }
        
        if (!empty($filters['user_id'])) {
            $sql .= " AND `user_id` = ?";
            $params[] = $filters['user_id'];
            $types .= 'i';
        }
        
        if (!empty($filters['record_id'])) {
            $sql .= " AND `record_id` = ?";
            $params[] = $filters['record_id'];
            $types .= 's';
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(`created_at`) >= ?";
            $params[] = $filters['date_from'];
            $types .= 's';
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(`created_at`) <= ?";
            $params[] = $filters['date_to'];
            $types .= 's';
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
        return $row['total'] ?? 0;
    }
}

