<?php
/**
 * Group Timetable Model (CRUD)
 * group_id from URL; time_slot = 08:30-10:00 | 10:30-12:00 | 13:00-14:30 | 14:45-16:15
 * module_id from module table; staff_id from staff (department)
 */

class GroupTimetableModel extends Model {
    protected $table = 'group_timetable';

    protected function getPrimaryKey() {
        return 'id';
    }

    /** Fixed time slots for timetable */
    public static function getTimeSlots() {
        return [
            '08:30-10:00' => '08:30 - 10:00',
            '10:30-12:00' => '10:30 - 12:00',
            '13:00-14:30' => '13:00 - 14:30',
            '14:45-16:15' => '14:45 - 16:15'
        ];
    }

    /**
     * Get all timetable entries by group_id (from URL)
     */
    public function getByGroupId($groupId) {
        if ($groupId === null || $groupId === '') return [];
        $sql = "SELECT tt.*, m.module_name, s.staff_name 
                FROM `{$this->table}` tt 
                LEFT JOIN `module` m ON m.module_id = tt.module_id 
                LEFT JOIN `staff` s ON s.staff_id = tt.staff_id 
                WHERE tt.group_id = ? 
                ORDER BY FIELD(tt.day, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), tt.time_slot";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $groupId);
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
     * Get all entries (optional filter by group_id)
     */
    public function getAll($groupId = null) {
        $sql = "SELECT tt.*, m.module_name, s.staff_name 
                FROM `{$this->table}` tt 
                LEFT JOIN `module` m ON m.module_id = tt.module_id 
                LEFT JOIN `staff` s ON s.staff_id = tt.staff_id 
                WHERE 1=1";
        $params = [];
        $types = '';
        if ($groupId !== null && $groupId !== '') {
            $sql .= " AND tt.group_id = ?";
            $params[] = $groupId;
            $types .= 'i';
        }
        $sql .= " ORDER BY tt.group_id, FIELD(tt.day, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), tt.time_slot";
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
     * Normalize day (e.g. "monday" -> "Monday") and time_slot (e.g. "08:30 - 10:00" -> "08:30-10:00")
     * so unique check and DB constraint match.
     */
    public static function normalizeDay($day) {
        $day = trim((string) $day);
        return $day !== '' ? ucfirst(strtolower($day)) : '';
    }

    public static function normalizeTimeSlot($timeSlot) {
        $slot = trim((string) $timeSlot);
        return $slot !== '' ? preg_replace('/\s*-\s*/', '-', $slot) : '';
    }

    /**
     * Check if (group_id, day, time_slot) already exists. For edit, pass $excludeId to allow same row.
     */
    public function existsSlot($groupId, $day, $timeSlot, $excludeId = null) {
        $day = self::normalizeDay($day);
        $timeSlot = self::normalizeTimeSlot($timeSlot);
        if ($groupId === null || $groupId === '' || $day === '' || $timeSlot === '') {
            return false;
        }
        $sql = "SELECT 1 FROM `{$this->table}` WHERE group_id = ? AND day = ? AND time_slot = ?";
        $params = [$groupId, $day, $timeSlot];
        $types = 'iss';
        if ($excludeId !== null && $excludeId !== '') {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
            $types .= 'i';
        }
        $sql .= " LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result && $result->num_rows > 0;
    }

    /**
     * Get single record by id (with module name)
     */
    public function getById($id) {
        $sql = "SELECT tt.*, m.module_name, s.staff_name 
                FROM `{$this->table}` tt 
                LEFT JOIN `module` m ON m.module_id = tt.module_id 
                LEFT JOIN `staff` s ON s.staff_id = tt.staff_id 
                WHERE tt.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_assoc() : null;
    }

    /**
     * Modules for dropdown - from module table by course_id
     */
    public function getModulesByCourseId($courseId) {
        if ($courseId === null || $courseId === '') return [];
        $sql = "SELECT module_id, module_name FROM `module` WHERE course_id = ? ORDER BY module_name, module_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $courseId);
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

    public function createTimetable($data) {
        return $this->create($data);
    }

    public function updateTimetable($id, $data) {
        return $this->update($id, $data);
    }

    public function deleteTimetable($id) {
        return $this->delete($id);
    }

    public static function getDays() {
        return ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    }

    public static function getSessionTypes() {
        return ['Theory' => 'Theory', 'Practical' => 'Practical'];
    }
}
