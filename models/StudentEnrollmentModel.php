<?php
/**
 * Student Enrollment Model
 */

class StudentEnrollmentModel extends Model {
    protected $table = 'student_enroll';
    
    /** Last SQL error when an update fails (for display to user) */
    protected $lastSqlError = '';
    
    protected function getPrimaryKey() {
        return 'student_id';
    }
    
    /**
     * Get last SQL error from update operations.
     */
    public function getLastSqlError() {
        return $this->lastSqlError;
    }
    
    /**
     * Get enrollments for a student
     */
    public function getByStudentId($studentId) {
        $sql = "SELECT se.*, c.course_name, c.department_id, d.department_name, a.academic_year as academic_year_name
                FROM `{$this->table}` se
                LEFT JOIN `course` c ON se.course_id = c.course_id
                LEFT JOIN `department` d ON c.department_id = d.department_id
                LEFT JOIN `academic` a ON se.academic_year = a.academic_year
                WHERE se.student_id = ?
                ORDER BY se.academic_year DESC, se.student_enroll_date DESC";
        
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
     * Get current enrollment (status = Following only)
     */
    public function getCurrentEnrollment($studentId) {
        $sql = "SELECT se.*, c.course_name, c.department_id, d.department_name
                FROM `{$this->table}` se
                LEFT JOIN `course` c ON se.course_id = c.course_id
                LEFT JOIN `department` d ON c.department_id = d.department_id
                WHERE se.student_id = ? AND se.student_enroll_status = 'Following'
                ORDER BY se.academic_year DESC
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Get latest enrollment (any status - for editing Dropout/Long Absent to re-register)
     */
    public function getLatestEnrollment($studentId) {
        $sql = "SELECT se.*, c.course_name, c.department_id, d.department_name
                FROM `{$this->table}` se
                LEFT JOIN `course` c ON se.course_id = c.course_id
                LEFT JOIN `department` d ON c.department_id = d.department_id
                WHERE se.student_id = ?
                ORDER BY se.academic_year DESC, se.student_enroll_date DESC
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Create new enrollment
     */
    public function createEnrollment($data) {
        $sql = "INSERT INTO `{$this->table}` 
                (`student_id`, `course_id`, `academic_year`, `course_mode`, `student_enroll_status`, `student_enroll_date`, `student_enroll_exit_date`) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("sssssss",
            $data['student_id'],
            $data['course_id'],
            $data['academic_year'],
            $data['course_mode'],
            $data['student_enroll_status'],
            $data['student_enroll_date'],
            $data['student_enroll_exit_date']
        );
        
        return $stmt->execute();
    }
    
    /**
     * Update enrollment for a student (active Following only). On failure use getLastSqlError().
     */
    public function updateEnrollment($studentId, $data) {
        $this->lastSqlError = '';
        $sql = "UPDATE `{$this->table}` SET 
                `course_id` = ?, 
                `academic_year` = ?, 
                `course_mode` = ?, 
                `student_enroll_status` = ?
                WHERE `student_id` = ? AND `student_enroll_status` = 'Following'
                ORDER BY `academic_year` DESC
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            $this->lastSqlError = $this->db->getConnection()->error ?? 'Prepare failed (student_enroll)';
            return false;
        }
        $stmt->bind_param("sssss", 
            $data['course_id'],
            $data['academic_year'],
            $data['course_mode'],
            $data['student_enroll_status'],
            $studentId
        );
        
        if (!$stmt->execute()) {
            $this->lastSqlError = $stmt->error ?? 'Execute failed (student_enroll)';
            return false;
        }
        return true;
    }
    
    /**
     * Update enrollment by record (student_id, course_id, academic_year) - for Dropout re-registration. On failure use getLastSqlError().
     */
    public function updateEnrollmentByRecord($studentId, $courseId, $academicYear, $data) {
        $this->lastSqlError = '';
        $sql = "UPDATE `{$this->table}` SET 
                `course_id` = ?, 
                `academic_year` = ?, 
                `course_mode` = ?, 
                `student_enroll_status` = ?
                WHERE `student_id` = ? AND `course_id` = ? AND `academic_year` = ?";
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            $this->lastSqlError = $this->db->getConnection()->error ?? 'Prepare failed (student_enroll)';
            return false;
        }
        $stmt->bind_param("sssssss", 
            $data['course_id'],
            $data['academic_year'],
            $data['course_mode'],
            $data['student_enroll_status'],
            $studentId,
            $courseId,
            $academicYear
        );
        
        if (!$stmt->execute()) {
            $this->lastSqlError = $stmt->error ?? 'Execute failed (student_enroll)';
            return false;
        }
        return true;
    }
}

