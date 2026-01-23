<?php
/**
 * Activity Logger
 * Logs all user activities for security and audit purposes
 */

class ActivityLogger {
    protected $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->createTableIfNotExists();
    }
    
    /**
     * Create activity_log table if it doesn't exist
     */
    private function createTableIfNotExists() {
        $sql = "CREATE TABLE IF NOT EXISTS `activity_log` (
            `log_id` INT(11) NOT NULL AUTO_INCREMENT,
            `user_id` INT(11) DEFAULT NULL,
            `username` VARCHAR(100) DEFAULT NULL,
            `activity_type` VARCHAR(50) NOT NULL,
            `activity_description` TEXT,
            `ip_address` VARCHAR(45) DEFAULT NULL,
            `user_agent` TEXT DEFAULT NULL,
            `request_url` VARCHAR(255) DEFAULT NULL,
            `request_method` VARCHAR(10) DEFAULT NULL,
            `status` VARCHAR(20) DEFAULT 'success' COMMENT 'success, failed, error',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`log_id`),
            KEY `idx_user_id` (`user_id`),
            KEY `idx_username` (`username`),
            KEY `idx_activity_type` (`activity_type`),
            KEY `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->db->query($sql);
    }
    
    /**
     * Log an activity
     */
    public function log($activityType, $description, $status = 'success', $userId = null, $username = null) {
        try {
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $requestUrl = $_SERVER['REQUEST_URI'] ?? null;
            $requestMethod = $_SERVER['REQUEST_METHOD'] ?? null;
            
            // Get user info from session if not provided
            if ($userId === null && isset($_SESSION['user_id'])) {
                $userId = $_SESSION['user_id'];
            }
            if ($username === null && isset($_SESSION['user_name'])) {
                $username = $_SESSION['user_name'];
            }
            
            $sql = "INSERT INTO `activity_log` 
                    (`user_id`, `username`, `activity_type`, `activity_description`, `ip_address`, `user_agent`, `request_url`, `request_method`, `status`) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param(
                "issssssss",
                $userId,
                $username,
                $activityType,
                $description,
                $ipAddress,
                $userAgent,
                $requestUrl,
                $requestMethod,
                $status
            );
            
            return $stmt->execute();
        } catch (Exception $e) {
            // Log error to file if database logging fails
            error_log("ActivityLogger Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get activity logs with filters
     */
    public function getLogs($filters = [], $page = 1, $perPage = 50) {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT * FROM `activity_log` WHERE 1=1";
        $conditions = [];
        $params = [];
        $types = '';
        
        if (!empty($filters['user_id'])) {
            $conditions[] = "`user_id` = ?";
            $params[] = $filters['user_id'];
            $types .= 'i';
        }
        
        if (!empty($filters['username'])) {
            $conditions[] = "`username` LIKE ?";
            $params[] = '%' . $filters['username'] . '%';
            $types .= 's';
        }
        
        if (!empty($filters['activity_type'])) {
            $conditions[] = "`activity_type` = ?";
            $params[] = $filters['activity_type'];
            $types .= 's';
        }
        
        if (!empty($filters['status'])) {
            $conditions[] = "`status` = ?";
            $params[] = $filters['status'];
            $types .= 's';
        }
        
        if (!empty($filters['date_from'])) {
            $conditions[] = "DATE(`created_at`) >= ?";
            $params[] = $filters['date_from'];
            $types .= 's';
        }
        
        if (!empty($filters['date_to'])) {
            $conditions[] = "DATE(`created_at`) <= ?";
            $params[] = $filters['date_to'];
            $types .= 's';
        }
        
        if (!empty($conditions)) {
            $sql .= " AND " . implode(" AND ", $conditions);
        }
        
        // Build SQL with limit/offset (can't use prepared statements for these)
        $sql .= " ORDER BY `created_at` DESC LIMIT " . (int)$perPage . " OFFSET " . (int)$offset;
        
        $stmt = $this->db->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $logs = [];
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
        
        return $logs;
    }
    
    /**
     * Get total count of logs with filters
     */
    public function getLogsCount($filters = []) {
        $sql = "SELECT COUNT(*) as total FROM `activity_log` WHERE 1=1";
        $conditions = [];
        $params = [];
        $types = '';
        
        if (!empty($filters['user_id'])) {
            $conditions[] = "`user_id` = ?";
            $params[] = $filters['user_id'];
            $types .= 'i';
        }
        
        if (!empty($filters['username'])) {
            $conditions[] = "`username` LIKE ?";
            $params[] = '%' . $filters['username'] . '%';
            $types .= 's';
        }
        
        if (!empty($filters['activity_type'])) {
            $conditions[] = "`activity_type` = ?";
            $params[] = $filters['activity_type'];
            $types .= 's';
        }
        
        if (!empty($filters['status'])) {
            $conditions[] = "`status` = ?";
            $params[] = $filters['status'];
            $types .= 's';
        }
        
        if (!empty($filters['date_from'])) {
            $conditions[] = "DATE(`created_at`) >= ?";
            $params[] = $filters['date_from'];
            $types .= 's';
        }
        
        if (!empty($filters['date_to'])) {
            $conditions[] = "DATE(`created_at`) <= ?";
            $params[] = $filters['date_to'];
            $types .= 's';
        }
        
        if (!empty($conditions)) {
            $sql .= " AND " . implode(" AND ", $conditions);
        }
        
        $stmt = $this->db->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return (int)$row['total'];
    }
    
    /**
     * Get activity types for filter dropdown
     */
    public function getActivityTypes() {
        $sql = "SELECT DISTINCT `activity_type` FROM `activity_log` ORDER BY `activity_type`";
        $result = $this->db->query($sql);
        
        $types = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $types[] = $row['activity_type'];
            }
        }
        
        return $types;
    }
}

