<?php
/**
 * Student Model
 */

class StudentModel extends Model {
    protected $table = 'student';
    
    protected function getPrimaryKey() {
        return 'student_id';
    }
    
    /**
     * Get students with pagination and filters
     */
    public function getStudents($page = 1, $perPage = 20, $filters = []) {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT DISTINCT s.* FROM `{$this->table}` s";
        $joins = [];
        $conditions = [];
        $params = [];
        $types = '';
        
        // Join with student_enroll if filtering by course or academic_year
        if (!empty($filters['course_id']) || !empty($filters['academic_year'])) {
            $joins[] = "LEFT JOIN `student_enroll` se ON s.student_id = se.student_id";
        }
        
        // Join with course if filtering by course or department
        if (!empty($filters['course_id']) || !empty($filters['department_id'])) {
            if (!in_array("LEFT JOIN `student_enroll` se ON s.student_id = se.student_id", $joins)) {
                $joins[] = "LEFT JOIN `student_enroll` se ON s.student_id = se.student_id";
            }
            $joins[] = "LEFT JOIN `course` c ON se.course_id = c.course_id";
        }
        
        // Join with department if filtering by department
        if (!empty($filters['department_id'])) {
            if (!in_array("LEFT JOIN `student_enroll` se ON s.student_id = se.student_id", $joins)) {
                $joins[] = "LEFT JOIN `student_enroll` se ON s.student_id = se.student_id";
            }
            if (!in_array("LEFT JOIN `course` c ON se.course_id = c.course_id", $joins)) {
                $joins[] = "LEFT JOIN `course` c ON se.course_id = c.course_id";
            }
            $joins[] = "LEFT JOIN `department` d ON c.department_id = d.department_id";
        }
        
        if (!empty($joins)) {
            $sql .= " " . implode(" ", $joins);
        }
        
        // Search filter
        if (!empty($filters['search'])) {
            $search = '%' . $this->db->escape($filters['search']) . '%';
            $conditions[] = "(s.student_fullname LIKE ? OR s.student_id LIKE ? OR s.student_email LIKE ? OR s.student_nic LIKE ?)";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $types .= 'ssss';
        }
        
        // Status filter
        if (!empty($filters['status'])) {
            $conditions[] = "s.student_status = ?";
            $params[] = $filters['status'];
            $types .= 's';
        }
        
        // District filter
        if (!empty($filters['district'])) {
            $conditions[] = "s.student_district = ?";
            $params[] = $filters['district'];
            $types .= 's';
        }
        
        // Gender filter
        if (!empty($filters['gender'])) {
            $conditions[] = "s.student_gender = ?";
            $params[] = $filters['gender'];
            $types .= 's';
        }
        
        // Department filter
        if (!empty($filters['department_id'])) {
            $conditions[] = "c.department_id = ?";
            $params[] = $filters['department_id'];
            $types .= 's';
        }
        
        // Course filter
        if (!empty($filters['course_id'])) {
            $conditions[] = "se.course_id = ?";
            $params[] = $filters['course_id'];
            $types .= 's';
        }
        
        // Academic year filter
        if (!empty($filters['academic_year'])) {
            $conditions[] = "se.academic_year = ?";
            $params[] = $filters['academic_year'];
            $types .= 's';
        }
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        
        $sql .= " ORDER BY s.student_id DESC LIMIT $perPage OFFSET $offset";
        
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
     * Get total count of students with filters (only active students with status 'Following')
     */
    public function getTotalStudents($filters = []) {
        $sql = "SELECT COUNT(DISTINCT s.student_id) as total FROM `{$this->table}` s";
        $joins = [];
        $conditions = [];
        $params = [];
        $types = '';
        
        // Always join with student_enroll to filter by active status (Following)
        $joins[] = "INNER JOIN `student_enroll` se ON s.student_id = se.student_id";
        // Always filter by active enrollment status
        $conditions[] = "se.student_enroll_status = 'Following'";
        
        // Join with course if filtering by course or department
        $needsCourseJoin = !empty($filters['course_id']) || !empty($filters['department_id']);
        $needsDeptJoin = !empty($filters['department_id']);
        
        if ($needsCourseJoin) {
            $joins[] = "LEFT JOIN `course` c ON se.course_id = c.course_id";
        }
        
        if ($needsDeptJoin) {
            $joins[] = "LEFT JOIN `department` d ON c.department_id = d.department_id";
        }
        
        // Add joins to SQL
        if (!empty($joins)) {
            $sql .= " " . implode(" ", $joins);
        }
        
        // Search filter
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $conditions[] = "(s.student_fullname LIKE ? OR s.student_id LIKE ? OR s.student_email LIKE ? OR s.student_nic LIKE ?)";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $types .= 'ssss';
        }
        
        // Status filter (for student_status, not enrollment status)
        if (!empty($filters['status'])) {
            $conditions[] = "s.student_status = ?";
            $params[] = $filters['status'];
            $types .= 's';
        }
        
        // District filter
        if (!empty($filters['district'])) {
            $conditions[] = "s.student_district = ?";
            $params[] = $filters['district'];
            $types .= 's';
        }
        
        // Gender filter
        if (!empty($filters['gender'])) {
            $conditions[] = "s.student_gender = ?";
            $params[] = $filters['gender'];
            $types .= 's';
        }
        
        // Department filter
        if (!empty($filters['department_id'])) {
            $conditions[] = "c.department_id = ?";
            $params[] = $filters['department_id'];
            $types .= 's';
        }
        
        // Course filter
        if (!empty($filters['course_id'])) {
            $conditions[] = "se.course_id = ?";
            $params[] = $filters['course_id'];
            $types .= 's';
        }
        
        // Academic year filter
        if (!empty($filters['academic_year'])) {
            $conditions[] = "se.academic_year = ?";
            $params[] = $filters['academic_year'];
            $types .= 's';
        }
        
        // Add WHERE clause with all conditions
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
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
        return $row['total'];
    }
    
    /**
     * Get unique academic years for filter dropdown
     */
    public function getAcademicYears() {
        $sql = "SELECT DISTINCT `academic_year` FROM `academic` WHERE `academic_year` IS NOT NULL AND `academic_year` != '' ORDER BY `academic_year` DESC";
        $result = $this->db->query($sql);
        $data = [];
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row['academic_year'];
            }
        }
        
        return $data;
    }
    
    /**
     * Get unique districts for filter dropdown
     */
    public function getDistricts() {
        $sql = "SELECT DISTINCT `student_district` FROM `{$this->table}` WHERE `student_district` IS NOT NULL AND `student_district` != '' ORDER BY `student_district` ASC";
        $result = $this->db->query($sql);
        $data = [];
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row['student_district'];
            }
        }
        
        return $data;
    }
    
    /**
     * Get recent students without duplicates (only active students with status 'Following')
     * Ensures each student_id appears only once
     */
    public function getRecentStudents($limit = 5) {
        // Get only active students (status 'Following')
        $sql = "SELECT DISTINCT s.`student_id`, s.`student_fullname`, s.`student_email`, s.`student_status` 
                FROM `{$this->table}` s
                INNER JOIN `student_enroll` se ON s.student_id = se.student_id
                WHERE se.student_enroll_status = 'Following'
                GROUP BY s.`student_id`
                ORDER BY s.`student_id` DESC 
                LIMIT " . (int)$limit;
        
        $result = $this->db->query($sql);
        $data = [];
        $seenIds = []; // Additional safeguard
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $studentId = $row['student_id'] ?? null;
                // Double check to prevent duplicates
                if ($studentId && !in_array($studentId, $seenIds)) {
                    $data[] = $row;
                    $seenIds[] = $studentId;
                }
            }
        }
        
        return $data;
    }
    
    /**
     * Create new student
     */
    public function createStudent($data) {
        return $this->create($data);
    }
    
    /**
     * Update student
     */
    public function updateStudent($id, $data) {
        return $this->update($id, $data);
    }
    
    /**
     * Update student ID (registration number)
     * This requires updating the student_id in multiple tables
     */
    public function updateStudentId($oldId, $newId) {
        // Start transaction
        $this->db->begin_transaction();
        
        try {
            // Update student table
            $sql1 = "UPDATE `{$this->table}` SET `student_id` = ? WHERE `student_id` = ?";
            $stmt1 = $this->db->prepare($sql1);
            $stmt1->bind_param("ss", $newId, $oldId);
            $result1 = $stmt1->execute();
            
            if (!$result1) {
                throw new Exception("Failed to update student_id in student table");
            }
            
            // Update student_enroll table
            $sql2 = "UPDATE `student_enroll` SET `student_id` = ? WHERE `student_id` = ?";
            $stmt2 = $this->db->prepare($sql2);
            $stmt2->bind_param("ss", $newId, $oldId);
            $result2 = $stmt2->execute();
            
            if (!$result2) {
                throw new Exception("Failed to update student_id in student_enroll table");
            }
            
            // Commit transaction
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            // Rollback on error
            $this->db->rollback();
            return false;
        }
    }
    
    /**
     * Delete student
     */
    public function deleteStudent($id) {
        return $this->delete($id);
    }
    
    /**
     * Check if student exists
     */
    public function exists($id) {
        $student = $this->find($id);
        return $student !== null;
    }
    
    /**
     * Get last registration number for a course and academic year
     */
    public function getLastRegistrationNumber($courseId, $academicYear) {
        $sql = "SELECT s.student_id 
                FROM `{$this->table}` s
                INNER JOIN `student_enroll` se ON s.student_id = se.student_id
                WHERE se.course_id = ? AND se.academic_year = ?
                ORDER BY s.student_id DESC
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ss", $courseId, $academicYear);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['student_id'];
        }
        
        return null;
    }
    
    /**
     * Generate next registration number
     */
    public function generateNextRegistrationNumber($courseId, $academicYear) {
        $lastRegNumber = $this->getLastRegistrationNumber($courseId, $academicYear);
        
        if (!$lastRegNumber) {
            // No previous registration number, generate first one
            // Get course code from course table
            $sql = "SELECT `course_code`, `course_id` FROM `course` WHERE `course_id` = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("s", $courseId);
            $stmt->execute();
            $result = $stmt->get_result();
            $course = $result->fetch_assoc();
            
            $courseCode = !empty($course['course_code']) ? $course['course_code'] : strtoupper(substr($courseId, 0, 3));
            $year = explode('/', $academicYear)[0] ?: date('Y');
            return $year . '_' . strtoupper($courseCode) . '_001';
        }
        
        // Parse the last registration number and increment
        // Format: YYYY_COURSECODE_NNN or similar
        $parts = explode('_', $lastRegNumber);
        if (count($parts) >= 3) {
            $year = $parts[0];
            $courseCode = $parts[1];
            $number = intval($parts[2]);
            $nextNumber = str_pad($number + 1, 3, '0', STR_PAD_LEFT);
            return $year . '_' . $courseCode . '_' . $nextNumber;
        }
        
        // If format doesn't match, try to extract number from end
        preg_match('/(\d+)$/', $lastRegNumber, $matches);
        if (!empty($matches[1])) {
            $number = intval($matches[1]);
            $nextNumber = str_pad($number + 1, 3, '0', STR_PAD_LEFT);
            return substr($lastRegNumber, 0, -strlen($matches[1])) . $nextNumber;
        }
        
        // Fallback: append _001
        return $lastRegNumber . '_001';
    }
    
    /**
     * Get students count by NVQ Level (only active students with status 'Following')
     */
    public function getStudentsByNVQLevel($academicYear = null) {
        $sql = "SELECT c.course_nvq_level, COUNT(DISTINCT s.student_id) as count 
                FROM `{$this->table}` s
                INNER JOIN `student_enroll` se ON s.student_id = se.student_id
                INNER JOIN `course` c ON se.course_id = c.course_id
                WHERE se.student_enroll_status = 'Following' 
                AND c.course_nvq_level IN ('04', '05', '06') 
                AND c.course_nvq_level IS NOT NULL";
        
        $params = [];
        $types = '';
        
        if (!empty($academicYear)) {
            $sql .= " AND se.academic_year = ?";
            $params[] = $academicYear;
            $types .= 's';
        }
        
        $sql .= " GROUP BY c.course_nvq_level ORDER BY c.course_nvq_level";
        
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
                $data[$row['course_nvq_level']] = (int)$row['count'];
            }
        }
        
        return $data;
    }
    
    /**
     * Get course enrollment counts by department (only active students with status 'Following')
     */
    public function getCourseEnrollmentByDepartment($academicYear = null) {
        $sql = "SELECT 
                    d.department_id,
                    d.department_name,
                    c.course_id,
                    c.course_name,
                    c.course_nvq_level,
                    COUNT(DISTINCT s.student_id) as enrollment_count 
                FROM `{$this->table}` s
                INNER JOIN `student_enroll` se ON s.student_id = se.student_id
                INNER JOIN `course` c ON se.course_id = c.course_id
                INNER JOIN `department` d ON c.department_id = d.department_id
                WHERE se.student_enroll_status = 'Following' 
                AND d.department_id IS NOT NULL
                AND c.course_id IS NOT NULL";
        
        $params = [];
        $types = '';
        
        if (!empty($academicYear)) {
            $sql .= " AND se.academic_year = ?";
            $params[] = $academicYear;
            $types .= 's';
        }
        
        $sql .= " GROUP BY d.department_id, d.department_name, c.course_id, c.course_name, c.course_nvq_level 
                  ORDER BY d.department_name, c.course_name";
        
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
                $deptId = $row['department_id'];
                $deptName = $row['department_name'];
                
                if (!isset($data[$deptId])) {
                    $data[$deptId] = [
                        'department_id' => $deptId,
                        'department_name' => $deptName,
                        'total_enrollment' => 0,
                        'courses' => []
                    ];
                }
                
                $enrollmentCount = (int)$row['enrollment_count'];
                $data[$deptId]['total_enrollment'] += $enrollmentCount;
                
                $data[$deptId]['courses'][] = [
                    'course_id' => $row['course_id'],
                    'course_name' => $row['course_name'],
                    'course_nvq_level' => $row['course_nvq_level'],
                    'enrollment_count' => $enrollmentCount
                ];
            }
        }
        
        return $data;
    }
    
    /**
     * Get students count by NVQ Level and Department (only active students with status 'Following')
     */
    public function getStudentsByNVQLevelAndDepartment($academicYear = null) {
        $sql = "SELECT 
                    d.department_id,
                    d.department_name,
                    c.course_nvq_level,
                    COUNT(DISTINCT s.student_id) as count 
                FROM `{$this->table}` s
                INNER JOIN `student_enroll` se ON s.student_id = se.student_id
                INNER JOIN `course` c ON se.course_id = c.course_id
                INNER JOIN `department` d ON c.department_id = d.department_id
                WHERE se.student_enroll_status = 'Following' 
                AND c.course_nvq_level IN ('04', '05', '06') 
                AND c.course_nvq_level IS NOT NULL
                AND d.department_id IS NOT NULL";
        
        $params = [];
        $types = '';
        
        if (!empty($academicYear)) {
            $sql .= " AND se.academic_year = ?";
            $params[] = $academicYear;
            $types .= 's';
        }
        
        $sql .= " GROUP BY d.department_id, d.department_name, c.course_nvq_level 
                  ORDER BY d.department_name, c.course_nvq_level";
        
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
                $deptId = $row['department_id'];
                $deptName = $row['department_name'];
                $nvqLevel = $row['course_nvq_level'];
                
                if (!isset($data[$deptId])) {
                    $data[$deptId] = [
                        'department_id' => $deptId,
                        'department_name' => $deptName,
                        'levels' => []
                    ];
                }
                
                $data[$deptId]['levels'][$nvqLevel] = (int)$row['count'];
            }
        }
        
        return $data;
    }
    
    /**
     * Get students count by Religion (only active students with status 'Following')
     */
    public function getStudentsByReligion($academicYear = null) {
        $sql = "SELECT s.student_religion, COUNT(DISTINCT s.student_id) as count 
                FROM `{$this->table}` s
                INNER JOIN `student_enroll` se ON s.student_id = se.student_id";
        
        $conditions = [
            "se.student_enroll_status = 'Following'",
            "s.student_religion IS NOT NULL AND s.student_religion != ''"
        ];
        $params = [];
        $types = '';
        
        if (!empty($academicYear)) {
            $conditions[] = "se.academic_year = ?";
            $params[] = $academicYear;
            $types .= 's';
        }
        
        $sql .= " WHERE " . implode(' AND ', $conditions);
        $sql .= " GROUP BY s.student_religion ORDER BY count DESC";
        
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
                $data[$row['student_religion']] = (int)$row['count'];
            }
        }
        
        return $data;
    }
    
    /**
     * Get students count by Gender (only active students with status 'Following')
     */
    public function getStudentsByGender($academicYear = null) {
        $sql = "SELECT s.student_gender, COUNT(DISTINCT s.student_id) as count 
                FROM `{$this->table}` s
                INNER JOIN `student_enroll` se ON s.student_id = se.student_id";
        
        $conditions = [
            "se.student_enroll_status = 'Following'",
            "s.student_gender IS NOT NULL AND s.student_gender != ''"
        ];
        $params = [];
        $types = '';
        
        if (!empty($academicYear)) {
            $conditions[] = "se.academic_year = ?";
            $params[] = $academicYear;
            $types .= 's';
        }
        
        $sql .= " WHERE " . implode(' AND ', $conditions);
        $sql .= " GROUP BY s.student_gender ORDER BY s.student_gender";
        
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
                $data[$row['student_gender']] = (int)$row['count'];
            }
        }
        
        return $data;
    }
    
    /**
     * Get students count by Department (only active students with status 'Following')
     */
    public function getStudentsByDepartment($academicYear = null) {
        $sql = "SELECT d.department_id, d.department_name, COUNT(DISTINCT s.student_id) as count 
                FROM `{$this->table}` s
                INNER JOIN `student_enroll` se ON s.student_id = se.student_id
                INNER JOIN `course` c ON se.course_id = c.course_id
                INNER JOIN `department` d ON c.department_id = d.department_id
                WHERE se.student_enroll_status = 'Following' 
                AND d.department_id IS NOT NULL";
        
        $params = [];
        $types = '';
        
        if (!empty($academicYear)) {
            $sql .= " AND se.academic_year = ?";
            $params[] = $academicYear;
            $types .= 's';
        }
        
        $sql .= " GROUP BY d.department_id, d.department_name ORDER BY count DESC";
        
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
                $data[] = [
                    'id' => $row['department_id'],
                    'name' => $row['department_name'],
                    'count' => (int)$row['count']
                ];
            }
        }
        
        return $data;
    }
    
    /**
     * Get students count by District (only active students with status 'Following')
     */
    public function getStudentsByDistrict($academicYear = null) {
        $sql = "SELECT s.student_district, COUNT(DISTINCT s.student_id) as count 
                FROM `{$this->table}` s
                INNER JOIN `student_enroll` se ON s.student_id = se.student_id";
        
        $conditions = [
            "se.student_enroll_status = 'Following'",
            "s.student_district IS NOT NULL AND s.student_district != ''"
        ];
        $params = [];
        $types = '';
        
        if (!empty($academicYear)) {
            $conditions[] = "se.academic_year = ?";
            $params[] = $academicYear;
            $types .= 's';
        }
        
        $sql .= " WHERE " . implode(' AND ', $conditions);
        $sql .= " GROUP BY s.student_district ORDER BY count DESC LIMIT 10";
        
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
                $data[$row['student_district']] = (int)$row['count'];
            }
        }
        
        return $data;
    }
    
    /**
     * Get students count by Province (only active students with status 'Following')
     */
    public function getStudentsByProvince($academicYear = null) {
        $sql = "SELECT s.student_provice as province, COUNT(DISTINCT s.student_id) as count 
                FROM `{$this->table}` s
                INNER JOIN `student_enroll` se ON s.student_id = se.student_id";
        
        $conditions = [
            "se.student_enroll_status = 'Following'",
            "s.student_provice IS NOT NULL AND s.student_provice != ''"
        ];
        $params = [];
        $types = '';
        
        if (!empty($academicYear)) {
            $conditions[] = "se.academic_year = ?";
            $params[] = $academicYear;
            $types .= 's';
        }
        
        $sql .= " WHERE " . implode(' AND ', $conditions);
        $sql .= " GROUP BY s.student_provice ORDER BY count DESC";
        
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
                $data[$row['province']] = (int)$row['count'];
            }
        }
        
        return $data;
    }
    
    /**
     * Update student profile image path
     */
    public function updateStudentImage($studentId, $imagePath) {
        // First check if file_path column exists, if not, add it
        $this->addFilePathColumnIfNotExists();
        
        $sql = "UPDATE `{$this->table}` SET `file_path` = ? WHERE `student_id` = ?";
        $stmt = $this->db->prepare($sql);
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("ss", $imagePath, $studentId);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Add file_path column to student table if it doesn't exist
     */
    private function addFilePathColumnIfNotExists() {
        try {
            // Check if file_path field exists
            $checkSql = "SHOW COLUMNS FROM `{$this->table}` LIKE 'file_path'";
            $result = $this->db->query($checkSql);
            
            if ($result->num_rows == 0) {
                // Add file_path field
                $sql = "ALTER TABLE `{$this->table}` ADD COLUMN `file_path` VARCHAR(255) DEFAULT NULL COMMENT 'Profile image file path' AFTER `student_status`";
                $this->db->query($sql);
            }
        } catch (Exception $e) {
            // Column might already exist or other error
            // Silently continue
        }
    }
    
    /**
     * Get profile image path for a student
     * Handles both path formats (student_profile, Student_profile, and Studnet_profile typo)
     */
    public function getProfileImagePath($student) {
        if (empty($student['file_path'])) {
            return null;
        }
        
        $imagePath = $student['file_path'];
        $filename = basename($imagePath);
        
        // Define possible directory paths
        $possiblePaths = [
            BASE_PATH . '/assets/img/student_profile/' . $filename,
            BASE_PATH . '/assets/img/Student_profile/' . $filename,
            BASE_PATH . '/assets/img/Studnet_profile/' . $filename,
            BASE_PATH . '/assets/' . $imagePath, // Try exact path from database
        ];
        
        // Check each possible path
        foreach ($possiblePaths as $fullPath) {
            if (file_exists($fullPath)) {
                // Return the corresponding URL
                $relativePath = str_replace(BASE_PATH . '/assets/', '', $fullPath);
                return APP_URL . '/assets/' . $relativePath;
            }
        }
        
        return null;
    }
}

