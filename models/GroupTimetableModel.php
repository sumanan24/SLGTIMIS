<?php
/**
 * Group Timetable Model
 */

class GroupTimetableModel extends Model {
    protected $table = 'group_timetable';
    
    protected function getPrimaryKey() {
        return 'timetable_id';
    }
    
    /**
     * Get all timetables for a group
     */
    public function getByGroupId($groupId, $activeOnly = false) {
        $sql = "SELECT tt.*, g.name as group_name, s.staff_name,
                c.course_name, d.department_name
                FROM `{$this->table}` tt
                LEFT JOIN `groups` g ON tt.group_id = g.id
                LEFT JOIN `course` c ON g.course_id = c.course_id
                LEFT JOIN `department` d ON c.department_id = d.department_id
                LEFT JOIN `staff` s ON tt.staff_id = s.staff_id
                WHERE tt.group_id = ?";
        
        $params = [$groupId];
        $types = 'i';
        
        if ($activeOnly) {
            $sql .= " AND tt.active = 1";
        }
        
        $sql .= " ORDER BY tt.weekday, tt.period";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
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
     * Get timetable by ID with details
     */
    public function getByIdWithDetails($id) {
        $sql = "SELECT tt.*, g.name as group_name, g.course_id, c.course_name, c.department_id, d.department_name
                FROM `{$this->table}` tt
                LEFT JOIN `groups` g ON tt.group_id = g.id
                LEFT JOIN `course` c ON g.course_id = c.course_id
                LEFT JOIN `department` d ON c.department_id = d.department_id
                WHERE tt.timetable_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Get timetables by group IDs (for students viewing their groups)
     */
    public function getByGroupIds($groupIds) {
        if (empty($groupIds)) {
            return [];
        }
        
        $placeholders = implode(',', array_fill(0, count($groupIds), '?'));
        $sql = "SELECT tt.*, g.name as group_name, g.course_id, c.course_name, d.department_name,
                s.staff_name
                FROM `{$this->table}` tt
                LEFT JOIN `groups` g ON tt.group_id = g.id
                LEFT JOIN `course` c ON g.course_id = c.course_id
                LEFT JOIN `department` d ON c.department_id = d.department_id
                LEFT JOIN `staff` s ON tt.staff_id = s.staff_id
                WHERE tt.group_id IN ($placeholders) AND tt.active = 1
                ORDER BY g.name, tt.weekday, tt.period";
        
        $stmt = $this->db->prepare($sql);
        $types = str_repeat('i', count($groupIds));
        $stmt->bind_param($types, ...$groupIds);
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
     * Create a new timetable entry
     */
    public function createTimetable($data) {
        return $this->create($data);
    }
    
    /**
     * Update timetable entry
     */
    public function updateTimetable($id, $data) {
        return $this->update($id, $data);
    }
    
    /**
     * Delete timetable entry
     */
    public function deleteTimetable($id) {
        return $this->delete($id);
    }
    
    /**
     * Get weekdays list
     */
    public function getWeekdays() {
        return [
            'Monday' => 'Monday',
            'Tuesday' => 'Tuesday',
            'Wednesday' => 'Wednesday',
            'Thursday' => 'Thursday',
            'Friday' => 'Friday',
            'Saturday' => 'Saturday',
            'Sunday' => 'Sunday'
        ];
    }
    
    /**
     * Get periods list (fixed time slots)
     */
    public function getPeriods() {
        return [
            '08:30-10:00' => '08:30 - 10:00',
            '10:30-12:00' => '10:30 - 12:00',
            '13:00-14:30' => '13:00 - 14:30',
            '14:45-16:15' => '14:45 - 16:15'
        ];
    }
    
    /**
     * Get time slots array (for easy iteration)
     */
    public function getTimeSlots() {
        return [
            '08:30-10:00' => '08:30 - 10:00',
            '10:30-12:00' => '10:30 - 12:00',
            '13:00-14:30' => '13:00 - 14:30',
            '14:45-16:15' => '14:45 - 16:15'
        ];
    }
    
    /**
     * Ensure time_slot column exists and populate it from period if needed
     */
    public function ensureTimeSlotColumn() {
        try {
            // Check if time_slot column exists
            $checkColumn = "SHOW COLUMNS FROM `{$this->table}` LIKE 'time_slot'";
            $result = $this->db->query($checkColumn);
            
            if (!$result || $result->num_rows == 0) {
                // Add time_slot column after period column
                $sql = "ALTER TABLE `{$this->table}` ADD COLUMN `time_slot` VARCHAR(20) DEFAULT NULL COMMENT 'Time slot (e.g., 08:30-10:00)' AFTER `period`";
                $this->db->query($sql);
                
                // Populate time_slot from period for existing records
                $updateSql = "UPDATE `{$this->table}` SET `time_slot` = `period` WHERE `time_slot` IS NULL OR `time_slot` = ''";
                $this->db->query($updateSql);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error ensuring time_slot column: " . $e->getMessage());
            return false;
        }
    }
}

