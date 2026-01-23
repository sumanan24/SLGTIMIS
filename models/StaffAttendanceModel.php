<?php
/**
 * Staff Attendance Model
 */

class StaffAttendanceModel extends Model {
    protected $table = 'staff_attendance';
    
    protected function getPrimaryKey() {
        return 'attendance_id';
    }
    
    /**
     * Create staff attendance table if it doesn't exist
     */
    public function createTableIfNotExists() {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->table}` (
            `attendance_id` INT(11) NOT NULL AUTO_INCREMENT,
            `staff_id` VARCHAR(50) NOT NULL,
            `employee_no` VARCHAR(50) DEFAULT NULL,
            `card_no` VARCHAR(50) DEFAULT NULL,
            `date` DATE NOT NULL,
            `check_in_time` TIME DEFAULT NULL,
            `check_out_time` TIME DEFAULT NULL,
            `status` TINYINT(1) DEFAULT 1 COMMENT '1=Present, 0=Absent',
            `device_id` VARCHAR(100) DEFAULT NULL,
            `source` VARCHAR(50) DEFAULT 'hikvision' COMMENT 'Source of attendance data',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`attendance_id`),
            UNIQUE KEY `unique_staff_date` (`staff_id`, `date`),
            KEY `idx_staff_id` (`staff_id`),
            KEY `idx_date` (`date`),
            KEY `idx_employee_no` (`employee_no`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        return $this->db->query($sql);
    }
    
    /**
     * Sync attendance records from Hikvision
     * 
     * @param array $records Array of attendance records from Hikvision
     * @param array $staffMapping Mapping of employee_no to staff_id
     * @return array Result with success/error counts
     */
    public function syncFromHikvision($records, $staffMapping = []) {
        $conn = $this->db->getConnection();
        $conn->begin_transaction();
        
        $successCount = 0;
        $errorCount = 0;
        $errors = [];
        
        try {
            foreach ($records as $record) {
                $employeeNo = $record['employee_id'] ?? $record['employee_no'] ?? '';
                $cardNo = $record['card_no'] ?? '';
                $date = $record['date'] ?? date('Y-m-d');
                $time = $record['time'] ?? '';
                $type = $record['type'] ?? '1'; // 1 = Check-in, 2 = Check-out
                
                // Map employee_no to staff_id
                $staffId = null;
                if (!empty($staffMapping[$employeeNo])) {
                    $staffId = $staffMapping[$employeeNo];
                } elseif (!empty($cardNo) && !empty($staffMapping[$cardNo])) {
                    $staffId = $staffMapping[$cardNo];
                } else {
                    // Try to find staff by employee_no or card_no
                    $staffId = $this->findStaffByEmployeeNo($employeeNo, $cardNo);
                }
                
                if (!$staffId) {
                    $errorCount++;
                    $errors[] = "Staff not found for employee_no: {$employeeNo}";
                    continue;
                }
                
                // Extract time from datetime
                $timeOnly = $this->extractTime($time);
                
                // Check if record exists
                $checkSql = "SELECT `attendance_id`, `check_in_time`, `check_out_time` 
                            FROM `{$this->table}` 
                            WHERE `staff_id` = ? AND `date` = ?";
                $checkStmt = $conn->prepare($checkSql);
                $checkStmt->bind_param("ss", $staffId, $date);
                $checkStmt->execute();
                $existing = $checkStmt->get_result()->fetch_assoc();
                $checkStmt->close();
                
                if ($existing) {
                    // Update existing record
                    if ($type == '1' || $type == 1) {
                        // Check-in
                        $updateSql = "UPDATE `{$this->table}` 
                                     SET `check_in_time` = ?, `employee_no` = ?, `card_no` = ?, 
                                         `device_id` = ?, `status` = 1, `source` = 'hikvision'
                                     WHERE `attendance_id` = ?";
                        $updateStmt = $conn->prepare($updateSql);
                        $updateStmt->bind_param("ssssi", $timeOnly, $employeeNo, $cardNo, 
                                               $record['device_id'] ?? '', $existing['attendance_id']);
                    } else {
                        // Check-out
                        $updateSql = "UPDATE `{$this->table}` 
                                     SET `check_out_time` = ?, `employee_no` = ?, `card_no` = ?, 
                                         `device_id` = ?, `status` = 1, `source` = 'hikvision'
                                     WHERE `attendance_id` = ?";
                        $updateStmt = $conn->prepare($updateSql);
                        $updateStmt->bind_param("ssssi", $timeOnly, $employeeNo, $cardNo, 
                                               $record['device_id'] ?? '', $existing['attendance_id']);
                    }
                    
                    if (!$updateStmt->execute()) {
                        throw new Exception('Failed to update: ' . $updateStmt->error);
                    }
                    $updateStmt->close();
                } else {
                    // Insert new record
                    $checkInTime = ($type == '1' || $type == 1) ? $timeOnly : null;
                    $checkOutTime = ($type == '2' || $type == 2) ? $timeOnly : null;
                    
                    $insertSql = "INSERT INTO `{$this->table}` 
                                 (`staff_id`, `employee_no`, `card_no`, `date`, `check_in_time`, 
                                  `check_out_time`, `status`, `device_id`, `source`) 
                                 VALUES (?, ?, ?, ?, ?, ?, 1, ?, 'hikvision')";
                    $insertStmt = $conn->prepare($insertSql);
                    $insertStmt->bind_param("sssssss", $staffId, $employeeNo, $cardNo, $date, 
                                           $checkInTime, $checkOutTime, $record['device_id'] ?? '');
                    
                    if (!$insertStmt->execute()) {
                        throw new Exception('Failed to insert: ' . $insertStmt->error);
                    }
                    $insertStmt->close();
                }
                
                $successCount++;
            }
            
            $conn->commit();
            
            return [
                'success' => $successCount,
                'errors' => $errorCount,
                'total' => count($records),
                'error_messages' => $errors
            ];
        } catch (Exception $e) {
            $conn->rollback();
            error_log('Staff attendance sync error: ' . $e->getMessage());
            return [
                'success' => $successCount,
                'errors' => $errorCount + (count($records) - $successCount),
                'total' => count($records),
                'error_messages' => array_merge($errors, [$e->getMessage()])
            ];
        }
    }
    
    /**
     * Find staff by employee number or card number
     * 
     * @param string $employeeNo Employee number
     * @param string $cardNo Card number
     * @return string|null Staff ID or null
     */
    private function findStaffByEmployeeNo($employeeNo, $cardNo = '') {
        // Check if staff table has employee_no or card_no fields
        $sql = "SELECT `staff_id` FROM `staff` 
                WHERE (`staff_id` = ? OR `staff_nic` = ?)";
        
        $params = [$employeeNo, $employeeNo];
        $types = 'ss';
        
        // If card_no is provided, also search by it
        if (!empty($cardNo)) {
            $sql .= " OR `staff_id` = ? OR `staff_nic` = ?";
            $params[] = $cardNo;
            $params[] = $cardNo;
            $types .= 'ss';
        }
        
        $sql .= " LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        if ($stmt) {
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            
            return $row ? $row['staff_id'] : null;
        }
        
        return null;
    }
    
    /**
     * Extract time from datetime string
     * 
     * @param string $datetime Datetime string
     * @return string Time in HH:MM:SS format
     */
    private function extractTime($datetime) {
        if (preg_match('/(\d{2}:\d{2}:\d{2})/', $datetime, $matches)) {
            return $matches[1];
        }
        return date('H:i:s');
    }
    
    /**
     * Get staff attendance by date range
     * 
     * @param string $staffId Staff ID
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Attendance records
     */
    public function getAttendanceByDateRange($staffId, $startDate, $endDate) {
        $sql = "SELECT * FROM `{$this->table}` 
                WHERE `staff_id` = ? AND `date` >= ? AND `date` <= ?
                ORDER BY `date` ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("sss", $staffId, $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        $stmt->close();
        return $data;
    }
    
    /**
     * Get all staff attendance for a date
     * 
     * @param string $date Date in YYYY-MM-DD format
     * @return array Attendance records
     */
    public function getAttendanceByDate($date) {
        $sql = "SELECT sa.*, s.staff_name, s.staff_email, d.department_name
                FROM `{$this->table}` sa
                LEFT JOIN `staff` s ON sa.staff_id = s.staff_id
                LEFT JOIN `department` d ON s.department_id = d.department_id
                WHERE sa.`date` = ?
                ORDER BY s.staff_name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        $stmt->close();
        return $data;
    }
}

