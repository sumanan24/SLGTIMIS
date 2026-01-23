<?php
/**
 * Login Attempt Model
 * Tracks failed login attempts for account locking
 */

class LoginAttemptModel extends Model {
    protected $table = 'login_attempts';
    
    protected function getPrimaryKey() {
        return 'attempt_id';
    }
    
    /**
     * Create login_attempts table if it doesn't exist
     */
    public function createTableIfNotExists() {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->table}` (
            `attempt_id` INT(11) NOT NULL AUTO_INCREMENT,
            `username` VARCHAR(100) NOT NULL,
            `ip_address` VARCHAR(45) DEFAULT NULL,
            `user_agent` TEXT DEFAULT NULL,
            `status` VARCHAR(20) DEFAULT 'failed' COMMENT 'success, failed',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`attempt_id`),
            KEY `idx_username` (`username`),
            KEY `idx_ip_address` (`ip_address`),
            KEY `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        return $this->db->query($sql);
    }
    
    /**
     * Record a login attempt
     */
    public function recordAttempt($username, $status = 'failed') {
        $this->createTableIfNotExists();
        
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $sql = "INSERT INTO `{$this->table}` (`username`, `ip_address`, `user_agent`, `status`) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ssss", $username, $ipAddress, $userAgent, $status);
        
        return $stmt->execute();
    }
    
    /**
     * Get failed login attempts count for a username in the last hour
     */
    public function getFailedAttemptsCount($username, $timeWindowMinutes = 60) {
        $this->createTableIfNotExists();
        
        $sql = "SELECT COUNT(*) as count 
                FROM `{$this->table}` 
                WHERE `username` = ? 
                AND `status` = 'failed' 
                AND `created_at` >= DATE_SUB(NOW(), INTERVAL ? MINUTE)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("si", $username, $timeWindowMinutes);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return (int)$row['count'];
    }
    
    /**
     * Clear failed attempts for a username (after successful login or unlock)
     */
    public function clearFailedAttempts($username) {
        $this->createTableIfNotExists();
        
        $sql = "DELETE FROM `{$this->table}` WHERE `username` = ? AND `status` = 'failed'";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $username);
        
        return $stmt->execute();
    }
    
    /**
     * Get all failed attempts for admin review
     */
    public function getFailedAttempts($limit = 100) {
        $this->createTableIfNotExists();
        
        $sql = "SELECT * FROM `{$this->table}` 
                WHERE `status` = 'failed' 
                ORDER BY `created_at` DESC 
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $attempts = [];
        while ($row = $result->fetch_assoc()) {
            $attempts[] = $row;
        }
        
        return $attempts;
    }
}

