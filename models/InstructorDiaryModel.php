<?php
/**
 * Instructor Diary Model
 */
class InstructorDiaryModel extends Model {
    protected $table = 'instructor_diary';
    
    protected function getPrimaryKey() {
        return 'instructor_diary_id';
    }
    
    /**
     * Ensure instructor_diary table exists with correct structure.
     * Uses VARCHAR staff_id/module_id to match existing schema.
     */
    public function ensureTableStructure() {
        $check = $this->db->query("SHOW TABLES LIKE '{$this->table}'");
        if ($check && $check->num_rows === 0) {
            $sql = "CREATE TABLE `{$this->table}` (
                        `instructor_diary_id` INT(11) NOT NULL AUTO_INCREMENT,
                        `staff_module_enrollment_id` INT(11) NOT NULL,
                        `staff_id` VARCHAR(50) NOT NULL,
                        `module_id` VARCHAR(50) NOT NULL,
                        `diary_date` DATE NOT NULL,
                        `start_time` TIME NOT NULL,
                        `end_time` TIME NOT NULL,
                        `topic_covered` VARCHAR(255) NOT NULL,
                        PRIMARY KEY (`instructor_diary_id`),
                        KEY `idx_staff` (`staff_id`),
                        KEY `idx_module` (`module_id`),
                        KEY `idx_enrollment` (`staff_module_enrollment_id`),
                        KEY `idx_diary_date` (`diary_date`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            
            if (!$this->db->query($sql)) {
                error_log('InstructorDiaryModel::ensureTableStructure create failed: ' . $this->db->error);
            }
        }
        
        return true;
    }
    
    /**
     * Create a new diary entry.
     */
    public function createEntry($data) {
        $this->ensureTableStructure();
        return $this->create($data);
    }
    
    /**
     * Check if an entry already exists for the same staff, module, date and time range.
     */
    public function existsEntry($staffId, $moduleId, $diaryDate, $startTime, $endTime) {
        $this->ensureTableStructure();
        
        $sql = "SELECT 1 FROM `{$this->table}` 
                WHERE staff_id = ? 
                  AND module_id = ? 
                  AND diary_date = ? 
                  AND start_time = ? 
                  AND end_time = ?
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("sssss", $staffId, $moduleId, $diaryDate, $startTime, $endTime);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result && $result->num_rows > 0;
    }
    
    /**
     * Get diary entries for a staff member (optional date range, module, course, group).
     */
    public function getByStaff($staffId, $filters = []) {
        $this->ensureTableStructure();
        
        $sql = "SELECT d.*, 
                       sme.course_id,
                       sme.academic_year,
                       m.module_name,
                       s.staff_name
                FROM `{$this->table}` d
                LEFT JOIN `staff_module_enrollment` sme 
                    ON d.staff_module_enrollment_id = sme.staff_module_enrollment_id
                LEFT JOIN `module` m ON d.module_id = m.module_id
                LEFT JOIN `staff` s ON d.staff_id = s.staff_id
                WHERE d.staff_id = ?";
        
        $params = [$staffId];
        $types = 's';
        
        if (!empty($filters['from_date'])) {
            $sql .= " AND d.diary_date >= ?";
            $params[] = $filters['from_date'];
            $types .= 's';
        }
        
        if (!empty($filters['to_date'])) {
            $sql .= " AND d.diary_date <= ?";
            $params[] = $filters['to_date'];
            $types .= 's';
        }
        
        if (!empty($filters['module_id'])) {
            $sql .= " AND d.module_id = ?";
            $params[] = $filters['module_id'];
            $types .= 's';
        }
        
        if (!empty($filters['course_id'])) {
            $sql .= " AND sme.course_id = ?";
            $params[] = $filters['course_id'];
            $types .= 's';
        }
        
        if (!empty($filters['academic_year'])) {
            $sql .= " AND sme.academic_year = ?";
            $params[] = $filters['academic_year'];
            $types .= 's';
        }
        
        $sql .= " GROUP BY d.instructor_diary_id
                  ORDER BY d.diary_date DESC, d.start_time ASC";
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log('InstructorDiaryModel::getByStaff prepare failed: ' . $this->db->error);
            return [];
        }
        
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
     * Get diary entries for a course/group via course_id + academic_year filters.
     * HOD can use this to view class/group-wise reports.
     */
    public function getByCourseAndYear($courseId, $academicYear, $filters = []) {
        $this->ensureTableStructure();
        
        $sql = "SELECT d.*, 
                       sme.course_id,
                       sme.academic_year,
                       m.module_name,
                       s.staff_name
                FROM `{$this->table}` d
                LEFT JOIN `staff_module_enrollment` sme 
                    ON d.staff_module_enrollment_id = sme.staff_module_enrollment_id
                LEFT JOIN `module` m ON d.module_id = m.module_id
                LEFT JOIN `staff` s ON d.staff_id = s.staff_id
                WHERE sme.course_id = ? AND sme.academic_year = ?";
        
        $params = [$courseId, $academicYear];
        $types = 'ss';
        
        if (!empty($filters['from_date'])) {
            $sql .= " AND d.diary_date >= ?";
            $params[] = $filters['from_date'];
            $types .= 's';
        }
        
        if (!empty($filters['to_date'])) {
            $sql .= " AND d.diary_date <= ?";
            $params[] = $filters['to_date'];
            $types .= 's';
        }
        
        if (!empty($filters['module_id'])) {
            $sql .= " AND d.module_id = ?";
            $params[] = $filters['module_id'];
            $types .= 's';
        }
        
        $sql .= " GROUP BY d.instructor_diary_id
                  ORDER BY d.diary_date DESC, d.start_time ASC";
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log('InstructorDiaryModel::getByCourseAndYear prepare failed: ' . $this->db->error);
            return [];
        }
        
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
     * Generic report query for HOD/management.
     * - If $departmentId is provided, restrict to that department via staff.department_id.
     * - Supports optional filters: staff_id, course_id, academic_year, from_date, to_date.
     * - Used for initial "all department" load and for filtered report + Excel export.
     */
    public function getForReport($departmentId = null, $filters = []) {
        $this->ensureTableStructure();
        
        $sql = "SELECT d.*, 
                       sme.course_id,
                       sme.academic_year,
                       m.module_name,
                       s.staff_name,
                       c.course_name
                FROM `{$this->table}` d
                LEFT JOIN `staff_module_enrollment` sme 
                    ON d.staff_module_enrollment_id = sme.staff_module_enrollment_id
                LEFT JOIN `module` m ON d.module_id = m.module_id
                LEFT JOIN `staff` s ON d.staff_id = s.staff_id
                LEFT JOIN `course` c ON sme.course_id = c.course_id
                WHERE 1=1";
        
        $params = [];
        $types = '';
        
        // Department restriction (for HOD)
        if (!empty($departmentId)) {
            $sql .= " AND s.department_id = ?";
            $params[] = $departmentId;
            $types .= 's';
        }
        
        if (!empty($filters['staff_id'])) {
            $sql .= " AND d.staff_id = ?";
            $params[] = $filters['staff_id'];
            $types .= 's';
        }
        
        if (!empty($filters['course_id'])) {
            $sql .= " AND sme.course_id = ?";
            $params[] = $filters['course_id'];
            $types .= 's';
        }
        
        if (!empty($filters['academic_year'])) {
            $sql .= " AND sme.academic_year = ?";
            $params[] = $filters['academic_year'];
            $types .= 's';
        }
        
        if (!empty($filters['from_date'])) {
            $sql .= " AND d.diary_date >= ?";
            $params[] = $filters['from_date'];
            $types .= 's';
        }
        
        if (!empty($filters['to_date'])) {
            $sql .= " AND d.diary_date <= ?";
            $params[] = $filters['to_date'];
            $types .= 's';
        }
        
        $sql .= " GROUP BY d.instructor_diary_id
                  ORDER BY d.diary_date DESC, d.start_time ASC";
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log('InstructorDiaryModel::getForReport prepare failed: ' . $this->db->error);
            return [];
        }
        
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        
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
}

