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
     * Override find to ensure student_profile_img column exists
     */
    public function find($id) {
        // Ensure student_profile_img column exists
        $this->addStudentProfileImgColumnIfNotExists();
        
        return parent::find($id);
    }
    
    /**
     * Get students with pagination and filters
     */
    public function getStudents($page = 1, $perPage = 20, $filters = []) {
        // Ensure student_profile_img column exists
        $this->addStudentProfileImgColumnIfNotExists();
        
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT DISTINCT s.* FROM `{$this->table}` s";
        $joins = [];
        $conditions = [];
        $params = [];
        $types = '';
        
        // Join with student_enroll if filtering by course, academic_year, course_mode, or group
        if (!empty($filters['course_id']) || !empty($filters['academic_year']) || !empty($filters['course_mode']) || !empty($filters['group_id'])) {
            $joins[] = "LEFT JOIN `student_enroll` se ON s.student_id = se.student_id";
        }
        
        // Join with group_students when filtering by group
        if (!empty($filters['group_id'])) {
            $joins[] = "INNER JOIN `group_students` gs ON s.student_id = gs.student_id AND gs.status = 'active'";
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
        
        // Course mode filter
        if (!empty($filters['course_mode'])) {
            // Normalize course_mode: 'Full Time' -> 'Full', 'Part Time' -> 'Part'
            $courseMode = $filters['course_mode'];
            $courseModeUpper = strtoupper(trim($courseMode));
            if ($courseModeUpper === 'FULL TIME' || $courseModeUpper === 'FULL') {
                $courseMode = 'Full';
            } elseif ($courseModeUpper === 'PART TIME' || $courseModeUpper === 'PART') {
                $courseMode = 'Part';
            }
            // Use case-insensitive comparison for course_mode
            $conditions[] = "LOWER(TRIM(se.`course_mode`)) = LOWER(TRIM(?))";
            $params[] = $courseMode;
            $types .= 's';
        }
        
        // Group filter
        if (!empty($filters['group_id'])) {
            $conditions[] = "gs.group_id = ?";
            $params[] = $filters['group_id'];
            $types .= 'i';
        }
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        
        $sql .= " ORDER BY s.student_id ASC LIMIT $perPage OFFSET $offset";
        
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
     * Get total count of students with filters
     * Counts students where student_status = 'Active' AND student_enroll_status = 'Following'
     */
    public function getTotalStudents($filters = []) {
        $sql = "SELECT COUNT(DISTINCT s.student_id) as total FROM `{$this->table}` s";
        $joins = [];
        $conditions = [];
        $params = [];
        $types = '';
        
        // Always join with student_enroll to filter by enrollment status
        $joins[] = "INNER JOIN `student_enroll` se ON s.student_id = se.student_id";
        // Filter by: student_status = 'Active' AND student_enroll_status = 'Following'
        $conditions[] = "s.student_status = 'Active'";
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
        
        if (!empty($filters['group_id'])) {
            $joins[] = "INNER JOIN `group_students` gs ON s.student_id = gs.student_id AND gs.status = 'active'";
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
        
        // Course mode filter
        if (!empty($filters['course_mode'])) {
            // Normalize course_mode: 'Full Time' -> 'Full', 'Part Time' -> 'Part'
            // Also handle if already 'Full' or 'Part'
            $courseMode = trim($filters['course_mode']);
            $courseModeUpper = strtoupper($courseMode);
            if ($courseModeUpper === 'FULL TIME' || $courseModeUpper === 'FULL') {
                $courseMode = 'Full';
            } elseif ($courseModeUpper === 'PART TIME' || $courseModeUpper === 'PART') {
                $courseMode = 'Part';
            }
            // Ensure we have a valid course_mode value
            if (in_array($courseMode, ['Full', 'Part'])) {
                // Use case-insensitive comparison for course_mode with backticks
                $conditions[] = "LOWER(TRIM(se.`course_mode`)) = LOWER(TRIM(?))";
                $params[] = $courseMode;
                $types .= 's';
            }
        }
        
        // Group filter
        if (!empty($filters['group_id'])) {
            $conditions[] = "gs.group_id = ?";
            $params[] = $filters['group_id'];
            $types .= 'i';
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
     * Get recent students without duplicates (only active students with status 'Active' AND enrollment 'Following')
     * Ensures each student_id appears only once
     */
    public function getRecentStudents($limit = 5) {
        // Get only active students (status 'Active' AND enrollment 'Following')
        $sql = "SELECT DISTINCT s.`student_id`, s.`student_fullname`, s.`student_email`, s.`student_status` 
                FROM `{$this->table}` s
                INNER JOIN `student_enroll` se ON s.student_id = se.student_id
                WHERE s.student_status = 'Active'
                AND se.student_enroll_status = 'Following'
                GROUP BY s.`student_id`
                ORDER BY s.`student_id` ASC 
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
     * Override to handle NULL values properly (especially student_email)
     * Generate a default email if student_email is NULL to avoid trigger errors
     */
    public function createStudent($data) {
        // Generate a default email if student_email is NULL or empty
        // This is needed because database triggers may create user accounts that require email
        if (empty($data['student_email']) && !empty($data['student_id'])) {
            // Generate email: student_id@slgtimis.local (or use student_id as email)
            $data['student_email'] = $data['student_id'] . '@slgtimis.local';
        }
        
        // Separate NULL values from regular values
        $columns = [];
        $placeholders = [];
        $types = '';
        $values = [];
        
        foreach ($data as $column => $value) {
            $columns[] = "`$column`";
            if ($value === null) {
                $placeholders[] = 'NULL';
                // Don't add to values array for NULL
            } else {
                $placeholders[] = '?';
                $types .= 's';
                $values[] = $value;
            }
        }
        
        $columnsStr = implode(', ', $columns);
        $placeholdersStr = implode(', ', $placeholders);
        
        $sql = "INSERT INTO `{$this->table}` ($columnsStr) VALUES ($placeholdersStr)";
        $stmt = $this->db->prepare($sql);
        
        if (!$stmt) {
            error_log("SQL Error in createStudent: " . $this->db->error . " | SQL: " . $sql);
            return false;
        }
        
        if (!empty($values)) {
            $stmt->bind_param($types, ...$values);
        }
        
        if ($stmt->execute()) {
            // For string primary keys (student_id), return the student_id instead of lastInsertId()
            // lastInsertId() works for auto-increment integer keys, not string keys
            if (isset($data['student_id'])) {
                return $data['student_id'];
            }
            // Fallback to lastInsertId() for integer keys
            return $this->db->lastInsertId();
        } else {
            error_log("SQL Execute Error in createStudent: " . $stmt->error . " | SQL: " . $sql);
            return false;
        }
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
            
            // Update attendance and group_students tables (no FK, must update explicitly)
            $tablesWithStudentId = ['attendance', 'group_students'];
            foreach ($tablesWithStudentId as $tbl) {
                $check = $this->db->query("SHOW TABLES LIKE '{$tbl}'");
                if ($check && $check->num_rows > 0) {
                    $sqlT = "UPDATE `{$tbl}` SET `student_id` = ? WHERE `student_id` = ?";
                    $stmtT = $this->db->prepare($sqlT);
                    if ($stmtT) {
                        $stmtT->bind_param("ss", $newId, $oldId);
                        $stmtT->execute();
                        $stmtT->close();
                    }
                }
            }
            
            // Update user table (student login username = student_id)
            $checkUser = $this->db->query("SHOW TABLES LIKE 'user'");
            if ($checkUser && $checkUser->num_rows > 0) {
                $sqlUser = "UPDATE `user` SET `user_name` = ? WHERE `user_table` = 'student' AND `user_name` = ?";
                $stmtUser = $this->db->prepare($sqlUser);
                if ($stmtUser) {
                    $stmtUser->bind_param("ss", $newId, $oldId);
                    $stmtUser->execute();
                    $stmtUser->close();
                }
            }
            
            // Update login_attempts so failed-attempt history follows the account
            $checkLoginAttempts = $this->db->query("SHOW TABLES LIKE 'login_attempts'");
            if ($checkLoginAttempts && $checkLoginAttempts->num_rows > 0) {
                $sqlLa = "UPDATE `login_attempts` SET `username` = ? WHERE `username` = ?";
                $stmtLa = $this->db->prepare($sqlLa);
                if ($stmtLa) {
                    $stmtLa->bind_param("ss", $newId, $oldId);
                    $stmtLa->execute();
                    $stmtLa->close();
                }
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
     * Sync user_active with student_status for student login accounts.
     * When student_status is Active, set user_active=1; otherwise user_active=0.
     */
    public function syncUserActiveWithStudentStatus($studentId) {
        $student = $this->find($studentId);
        if (!$student || !isset($student['student_status'])) {
            return false;
        }
        $isActive = (strtoupper(trim($student['student_status'])) === 'ACTIVE');
        $userActive = $isActive ? 1 : 0;
        
        $check = $this->db->query("SHOW TABLES LIKE 'user'");
        if (!$check || $check->num_rows === 0) {
            return false;
        }
        $sql = "UPDATE `user` SET `user_active` = ? WHERE `user_table` = 'student' AND `user_name` = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("is", $userActive, $studentId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
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
     * Get last registration number for a course, academic year, and course mode
     * Note: Database stores course_mode as enum('Part','Full'), so we normalize the input
     */
    public function getLastRegistrationNumber($courseId, $academicYear, $courseMode = null) {
        $sql = "SELECT s.`student_id` 
                FROM `{$this->table}` s
                INNER JOIN `student_enroll` se ON s.`student_id` = se.`student_id`
                WHERE se.`course_id` = ? AND se.`academic_year` = ?";
        
        $params = [$courseId, $academicYear];
        $types = "ss";
        
        // Filter by course mode if provided
        // Normalize course_mode to match database enum: 'Full Time' -> 'Full', 'Part Time' -> 'Part'
        if (!empty($courseMode)) {
            $normalizedCourseMode = $courseMode;
            // Handle various input formats (case-insensitive)
            $courseModeUpper = strtoupper(trim($courseMode));
            if ($courseModeUpper === 'FULL TIME' || $courseModeUpper === 'FULL') {
                $normalizedCourseMode = 'Full';
            } elseif ($courseModeUpper === 'PART TIME' || $courseModeUpper === 'PART') {
                $normalizedCourseMode = 'Part';
            }
            // Use case-insensitive comparison for course_mode with proper backticks
            $sql .= " AND LOWER(TRIM(se.`course_mode`)) = LOWER(TRIM(?))";
            $params[] = $normalizedCourseMode;
            $types .= "s";
        }
        
        // Order by student_id DESC to get the latest/highest student ID, limit 1
        $sql .= " ORDER BY s.`student_id` DESC LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("SQL Error in getLastRegistrationNumber: " . $this->db->error);
            return null;
        }
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $lastStudentId = $row['student_id'];
            error_log("Last Student ID found: " . $lastStudentId . " for course: " . $courseId . ", year: " . $academicYear . ", mode: " . $courseMode);
            return $lastStudentId;
        }
        
        error_log("No last Student ID found for course: " . $courseId . ", year: " . $academicYear . ", mode: " . $courseMode);
        return null;
    }
    
    /**
     * Get next available registration number that doesn't exist yet
     * This method finds the last ID, increments it, and keeps checking until it finds an available ID
     */
    public function getNextAvailableRegistrationNumber($courseId, $academicYear, $courseMode = null) {
        $lastRegNumber = $this->getLastRegistrationNumber($courseId, $academicYear, $courseMode);
        
        // If no last registration number found, return null (will need to generate from scratch)
        if (!$lastRegNumber) {
            return null;
        }
        
        // Extract last 3 digits from the student ID
        $match = preg_match('/(\d{3})$/', $lastRegNumber, $matches);
        if (!$match) {
            // If no 3 digits found, return the last ID as is (let frontend handle it)
            return $lastRegNumber;
        }
        
        $lastNumber = intval($matches[1]);
        $baseId = preg_replace('/\d{3}$/', '', $lastRegNumber);
        
        // Try up to 1000 iterations to find an available ID
        $maxAttempts = 1000;
        $attempt = 0;
        
        while ($attempt < $maxAttempts) {
            $nextNumber = $lastNumber + 1 + $attempt;
            $nextNumberStr = str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            $nextId = $baseId . $nextNumberStr;
            
            // Check if this ID already exists
            if (!$this->exists($nextId)) {
                error_log("Next available Student ID: " . $nextId . " for course: " . $courseId . ", year: " . $academicYear . ", mode: " . $courseMode);
                return $nextId;
            }
            
            $attempt++;
        }
        
        // If we couldn't find an available ID after many attempts, return the incremented last ID
        // (This shouldn't happen in normal circumstances)
        $nextNumber = $lastNumber + 1;
        $nextNumberStr = str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        $nextId = $baseId . $nextNumberStr;
        error_log("Warning: Could not find available ID after " . $maxAttempts . " attempts. Returning: " . $nextId);
        return $nextId;
    }
    
    /**
     * Generate next registration number based on course, academic year, and course mode
     * Format: 
     * - Full Time: YYYY/DEPARTMENT_CODE/COURSE_CODE_NNN (e.g., 2025/COT/5CT001)
     * - Part Time: YYYY/DEPARTMENT_CODE/COURSE_CODE/PT_NNN (e.g., 2025/COT/5CT/PT001)
     */
    public function generateNextRegistrationNumber($courseId, $academicYear, $courseMode = 'Full Time') {
        $lastRegNumber = $this->getLastRegistrationNumber($courseId, $academicYear, $courseMode);
        
        // Check if Part Time (only PT students have mode in ID)
        $isPartTime = ($courseMode === 'Part Time');
        
        // Get course code and department code from course table
        $sql = "SELECT c.`course_code`, c.`course_id`, c.`department_id`, d.`department_id` as dept_code
                FROM `course` c
                LEFT JOIN `department` d ON c.department_id = d.department_id
                WHERE c.`course_id` = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $courseId);
        $stmt->execute();
        $result = $stmt->get_result();
        $course = $result->fetch_assoc();
        
        if (!$course) {
            // Fallback if course not found
            $courseCode = strtoupper(substr($courseId, 0, 3));
            $departmentCode = 'GEN';
        } else {
            $courseCode = !empty($course['course_code']) ? $course['course_code'] : strtoupper(substr($courseId, 0, 3));
            $departmentCode = !empty($course['department_id']) ? $course['department_id'] : 'GEN';
        }
        
        $year = explode('/', $academicYear)[0] ?: date('Y');
        
        if (!$lastRegNumber) {
            // No previous registration number, generate first one
            if ($isPartTime) {
                // Part Time: YYYY/DEPARTMENT_CODE/COURSE_CODE/PT001
                return $year . '/' . strtoupper($departmentCode) . '/' . strtoupper($courseCode) . '/PT001';
            } else {
                // Full Time: YYYY/DEPARTMENT_CODE/COURSE_CODE001
                return $year . '/' . strtoupper($departmentCode) . '/' . strtoupper($courseCode) . '001';
            }
        }
        
        // Parse the last registration number and increment
        // Handle multiple formats for backward compatibility:
        // - New format Part Time: YYYY/DEPARTMENT_CODE/COURSE_CODE/PT_NNN (e.g., 2025/COT/5CT/PT001)
        // - New format Full Time: YYYY/DEPARTMENT_CODE/COURSE_CODE_NNN (e.g., 2025/COT/5CT001)
        // - Old format: YYYY/COURSECODE/NNN (e.g., 2024/ABC/001)
        // - Old format: YYYY_COURSECODE_NNN (e.g., 2024_ABC_001)
        // - Old format with mode: YYYY_COURSECODE_MODE_NNN (e.g., 2024_ABC_FT_001)
        
        $parts = [];
        if (strpos($lastRegNumber, '/') !== false) {
            $parts = explode('/', $lastRegNumber);
        } else {
            $parts = explode('_', $lastRegNumber);
        }
        
        // First, detect the format of the last registration number
        // Check if format is new format Part Time (4 parts: YYYY/DEPARTMENT_CODE/COURSE_CODE/PT_NNN)
        if (count($parts) >= 4) {
            $year = $parts[0];
            $deptCode = $parts[1];
            $lastCourseCode = $parts[2];
            
            // Check if last part contains PT and number (e.g., PT001)
            $lastPart = $parts[3];
            preg_match('/^PT(\d+)$/i', $lastPart, $ptMatches);
            
            if (!empty($ptMatches)) {
                // Format: YYYY/DEPARTMENT_CODE/COURSE_CODE/PT_NNN
                $number = intval($ptMatches[1]);
                $nextNumber = str_pad($number + 1, 3, '0', STR_PAD_LEFT);
                
                if ($isPartTime) {
                    // Generating Part Time, increment PT number
                    return $year . '/' . $deptCode . '/' . strtoupper($courseCode) . '/PT' . $nextNumber;
                } else {
                    // Generating Full Time, start new sequence
                    return $year . '/' . $deptCode . '/' . strtoupper($courseCode) . '001';
                }
            }
        }
        
        // Check if format is new format Full Time (3 parts: YYYY/DEPARTMENT_CODE/COURSE_CODE_NNN)
        // Last part should contain course code and number together (e.g., 5CT001)
        if (count($parts) >= 3) {
            $year = $parts[0];
            $deptCode = $parts[1];
            $lastPart = $parts[2];
            
            // Check if last part contains both course code and number (e.g., 5CT001)
            preg_match('/^([A-Za-z]+)(\d+)$/', $lastPart, $matches);
            
            if (!empty($matches)) {
                // Format: YYYY/DEPARTMENT_CODE/COURSE_CODE_NNN
                $lastCourseCode = $matches[1];
                $number = intval($matches[2]);
                $nextNumber = str_pad($number + 1, 3, '0', STR_PAD_LEFT);
                
                if (!$isPartTime) {
                    // Generating Full Time, increment number
                    return $year . '/' . $deptCode . '/' . strtoupper($courseCode) . $nextNumber;
                } else {
                    // Generating Part Time, start new PT sequence
                    return $year . '/' . $deptCode . '/' . strtoupper($courseCode) . '/PT001';
                }
            }
        }
        
        // Check if format is old format with mode in underscores (4 parts: YYYY_COURSECODE_MODE_NNN)
        if (count($parts) >= 4) {
            $year = $parts[0];
            $oldCourseCode = $parts[1];
            $oldMode = strtoupper($parts[2]);
            $number = intval($parts[3]);
            
            // Convert old format to new format
            if ($isPartTime && ($oldMode === 'PT' || $oldMode === 'PART')) {
                // Part Time: convert to YYYY/DEPARTMENT_CODE/COURSE_CODE/PT_NNN
                $nextNumber = str_pad($number + 1, 3, '0', STR_PAD_LEFT);
                return $year . '/' . strtoupper($departmentCode) . '/' . strtoupper($courseCode) . '/PT' . $nextNumber;
            } elseif (!$isPartTime && ($oldMode === 'FT' || $oldMode === 'FULL' || empty($oldMode))) {
                // Full Time: convert to YYYY/DEPARTMENT_CODE/COURSE_CODE_NNN
                $nextNumber = str_pad($number + 1, 3, '0', STR_PAD_LEFT);
                return $year . '/' . strtoupper($departmentCode) . '/' . strtoupper($courseCode) . $nextNumber;
            } else {
                // Different mode, start from 001
                if ($isPartTime) {
                    return $year . '/' . strtoupper($departmentCode) . '/' . strtoupper($courseCode) . '/PT001';
                } else {
                    return $year . '/' . strtoupper($departmentCode) . '/' . strtoupper($courseCode) . '001';
                }
            }
        }
        
        // Check if format is old format without mode (3 parts: YYYY/COURSECODE/NNN or YYYY_COURSECODE_NNN)
        if (count($parts) >= 3) {
            $year = $parts[0];
            $deptOrCourseCode = $parts[1];
            $lastPart = $parts[2];
            
            // Check if last part is just a number
            if (is_numeric($lastPart)) {
                $number = intval($lastPart);
                $nextNumber = str_pad($number + 1, 3, '0', STR_PAD_LEFT);
                
                if ($isPartTime) {
                    return $year . '/' . strtoupper($departmentCode) . '/' . strtoupper($courseCode) . '/PT' . $nextNumber;
                } else {
                    return $year . '/' . strtoupper($departmentCode) . '/' . strtoupper($courseCode) . $nextNumber;
                }
            }
            
            // Check if last part contains course code and number (e.g., 5CT006)
            preg_match('/^([A-Za-z]+)(\d+)$/', $lastPart, $matches);
            if (!empty($matches)) {
                $number = intval($matches[2]);
                $nextNumber = str_pad($number + 1, 3, '0', STR_PAD_LEFT);
                
                if ($isPartTime) {
                    return $year . '/' . strtoupper($departmentCode) . '/' . strtoupper($courseCode) . '/PT' . $nextNumber;
                } else {
                    return $year . '/' . strtoupper($departmentCode) . '/' . strtoupper($courseCode) . $nextNumber;
                }
            }
        }
        
        // If format doesn't match, try to extract number from end
        preg_match('/(\d+)$/', $lastRegNumber, $matches);
        if (!empty($matches[1])) {
            $number = intval($matches[1]);
            $nextNumber = str_pad($number + 1, 3, '0', STR_PAD_LEFT);
            // Extract prefix and normalize
            $prefix = substr($lastRegNumber, 0, -strlen($matches[1]));
            // Normalize separator to slash (handle both _ and /)
            $prefix = str_replace('_', '/', $prefix);
            // Remove mode abbreviations if present
            $prefix = preg_replace('/\/(FT|PT)\/?$/', '', $prefix);
            // Remove trailing separator if present
            $prefix = rtrim($prefix, '/');
            // Extract year if present, otherwise use current year
            $prefixParts = explode('/', $prefix);
            $year = !empty($prefixParts[0]) && is_numeric($prefixParts[0]) ? $prefixParts[0] : date('Y');
            
            if ($isPartTime) {
                return $year . '/' . strtoupper($departmentCode) . '/' . strtoupper($courseCode) . '/PT' . $nextNumber;
            } else {
                return $year . '/' . strtoupper($departmentCode) . '/' . strtoupper($courseCode) . $nextNumber;
            }
        }
        
        // Fallback: use current format
        $normalized = str_replace('_', '/', $lastRegNumber);
        if ($isPartTime) {
            return $year . '/' . strtoupper($departmentCode) . '/' . strtoupper($courseCode) . '/PT001';
        } else {
            return $year . '/' . strtoupper($departmentCode) . '/' . strtoupper($courseCode) . '001';
        }
    }
    
    /**
     * Get students count by NVQ Level (only active students with status 'Following')
     */
    public function getStudentsByNVQLevel($academicYear = null) {
        $sql = "SELECT c.course_nvq_level, COUNT(DISTINCT s.student_id) as count 
                FROM `{$this->table}` s
                INNER JOIN `student_enroll` se ON s.student_id = se.student_id
                INNER JOIN `course` c ON se.course_id = c.course_id
                WHERE s.student_status = 'Active'
                AND se.student_enroll_status = 'Following' 
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
     * Get course enrollment counts by department with gender breakdown and course mode
     * Only active students with status 'Active' AND enrollment status 'Following'
     */
    public function getCourseEnrollmentByDepartment($academicYear = null) {
        $sql = "SELECT 
                    d.department_id,
                    d.department_name,
                    c.course_id,
                    c.course_name,
                    c.course_nvq_level,
                    se.course_mode,
                    s.student_gender,
                    COUNT(DISTINCT s.student_id) as enrollment_count 
                FROM `{$this->table}` s
                INNER JOIN `student_enroll` se ON s.student_id = se.student_id
                INNER JOIN `course` c ON se.course_id = c.course_id
                INNER JOIN `department` d ON c.department_id = d.department_id
                WHERE s.student_status = 'Active'
                AND se.student_enroll_status = 'Following' 
                AND d.department_id IS NOT NULL
                AND c.course_id IS NOT NULL";
        
        $params = [];
        $types = '';
        
        if (!empty($academicYear)) {
            $sql .= " AND se.academic_year = ?";
            $params[] = $academicYear;
            $types .= 's';
        }
        
        $sql .= " GROUP BY d.department_id, d.department_name, c.course_id, c.course_name, c.course_nvq_level, se.course_mode, s.student_gender 
                  ORDER BY d.department_name, c.course_nvq_level, c.course_name, se.course_mode";
        
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
                $courseId = $row['course_id'];
                $courseMode = $row['course_mode'] ?? 'Full';
                $nvqLevel = $row['course_nvq_level'];
                $gender = $row['student_gender'] ?? 'Unknown';
                $count = (int)$row['enrollment_count'];
                
                if (!isset($data[$deptId])) {
                    $data[$deptId] = [
                        'department_id' => $deptId,
                        'department_name' => $deptName,
                        'total_enrollment' => 0,
                        'nvq_levels' => []
                    ];
                }
                
                // Group by NVQ Level
                if (!isset($data[$deptId]['nvq_levels'][$nvqLevel])) {
                    $data[$deptId]['nvq_levels'][$nvqLevel] = [];
                }
                
                // Find or create course entry (group by course_id and course_mode)
                $courseFound = false;
                foreach ($data[$deptId]['nvq_levels'][$nvqLevel] as &$course) {
                    if ($course['course_id'] === $courseId && $course['course_mode'] === $courseMode) {
                        // Update gender counts
                        if ($gender === 'Female') {
                            $course['female_count'] = ($course['female_count'] ?? 0) + $count;
                        } elseif ($gender === 'Male') {
                            $course['male_count'] = ($course['male_count'] ?? 0) + $count;
                        }
                        $course['total_count'] = ($course['total_count'] ?? 0) + $count;
                        $courseFound = true;
                        break;
                    }
                }
                
                if (!$courseFound) {
                    $courseData = [
                        'course_id' => $courseId,
                        'course_name' => $row['course_name'],
                        'course_nvq_level' => $nvqLevel,
                        'course_mode' => $courseMode,
                        'female_count' => ($gender === 'Female') ? $count : 0,
                        'male_count' => ($gender === 'Male') ? $count : 0,
                        'total_count' => $count
                    ];
                    $data[$deptId]['nvq_levels'][$nvqLevel][] = $courseData;
                }
                
                $data[$deptId]['total_enrollment'] += $count;
            }
        }
        
        // Sort NVQ levels and courses within each level
        foreach ($data as &$dept) {
            ksort($dept['nvq_levels']);
            foreach ($dept['nvq_levels'] as &$courses) {
                usort($courses, function($a, $b) {
                    return strcmp($a['course_name'], $b['course_name']);
                });
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
                WHERE s.student_status = 'Active'
                AND se.student_enroll_status = 'Following' 
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
     * Only returns the 4 main religions: Buddhism, Hinduism, Islam, Christianity
     */
    public function getStudentsByReligion($academicYear = null) {
        // Define the 4 main religions (case-insensitive matching)
        $mainReligions = ['Buddhism', 'Hinduism', 'Islam', 'Christianity'];
        
        $sql = "SELECT s.student_religion, COUNT(DISTINCT s.student_id) as count 
                FROM `{$this->table}` s
                INNER JOIN `student_enroll` se ON s.student_id = se.student_id";
        
        $conditions = [
            "s.student_status = 'Active'",
            "se.student_enroll_status = 'Following'",
            "s.student_religion IS NOT NULL AND s.student_religion != ''"
        ];
        $params = [];
        $types = '';
        
        // Filter to only include the 4 main religions (case-insensitive)
        $religionConditions = [];
        foreach ($mainReligions as $religion) {
            $religionConditions[] = "LOWER(TRIM(s.student_religion)) = LOWER(TRIM(?))";
            $params[] = $religion;
            $types .= 's';
        }
        
        if (!empty($religionConditions)) {
            $conditions[] = "(" . implode(' OR ', $religionConditions) . ")";
        }
        
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
                $religion = trim($row['student_religion']);
                // Normalize religion name to match one of the 4 main religions
                $normalizedReligion = $this->normalizeReligionName($religion);
                if ($normalizedReligion) {
                    // If this religion already exists in data, add to it (in case of case variations)
                    if (isset($data[$normalizedReligion])) {
                        $data[$normalizedReligion] += (int)$row['count'];
                    } else {
                        $data[$normalizedReligion] = (int)$row['count'];
                    }
                }
            }
        }
        
        // Ensure all 4 main religions are in the result (with 0 count if no students)
        $finalData = [];
        foreach ($mainReligions as $religion) {
            $finalData[$religion] = $data[$religion] ?? 0;
        }
        
        // Sort by count descending
        arsort($finalData);
        
        return $finalData;
    }
    
    /**
     * Normalize religion name to one of the 4 main religions
     */
    private function normalizeReligionName($religion) {
        $religion = trim($religion);
        $religionLower = strtolower($religion);
        
        // Map variations to main religions
        $religionMap = [
            'buddhism' => 'Buddhism',
            'buddhist' => 'Buddhism',
            'hinduism' => 'Hinduism',
            'hindu' => 'Hinduism',
            'islam' => 'Islam',
            'muslim' => 'Islam',
            'islamic' => 'Islam',
            'christianity' => 'Christianity',
            'christian' => 'Christianity',
            'catholic' => 'Christianity',
            'protestant' => 'Christianity'
        ];
        
        if (isset($religionMap[$religionLower])) {
            return $religionMap[$religionLower];
        }
        
        // If exact match with main religions
        $mainReligions = ['Buddhism', 'Hinduism', 'Islam', 'Christianity'];
        foreach ($mainReligions as $mainReligion) {
            if (strtolower($mainReligion) === $religionLower) {
                return $mainReligion;
            }
        }
        
        return null;
    }
    
    /**
     * Get students count by Gender (only active students with status 'Following')
     */
    public function getStudentsByGender($academicYear = null) {
        $sql = "SELECT s.student_gender, COUNT(DISTINCT s.student_id) as count 
                FROM `{$this->table}` s
                INNER JOIN `student_enroll` se ON s.student_id = se.student_id";
        
        $conditions = [
            "s.student_status = 'Active'",
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
                WHERE s.student_status = 'Active'
                AND se.student_enroll_status = 'Following' 
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
            "s.student_status = 'Active'",
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
            "s.student_status = 'Active'",
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
     * Database stores path relative to assets folder (e.g., "img/Student_profile/filename.jpg")
     */
    public function updateStudentImage($studentId, $imagePath) {
        // First check if student_profile_img column exists, if not, add it
        $this->addStudentProfileImgColumnIfNotExists();
        
        // Normalize the path - ensure it's relative to assets folder
        $imagePath = ltrim($imagePath, '/');
        if (strpos($imagePath, 'assets/') === 0) {
            $imagePath = substr($imagePath, 7); // Remove 'assets/' prefix
        }
        
        $sql = "UPDATE `{$this->table}` SET `student_profile_img` = ? WHERE `student_id` = ?";
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
     * Add student_documents_pdf column to student table if it doesn't exist
     */
    public function addStudentDocumentsPdfColumnIfNotExists() {
        try {
            // Check if student_documents_pdf field exists
            $checkSql = "SHOW COLUMNS FROM `{$this->table}` LIKE 'student_documents_pdf'";
            $result = $this->db->query($checkSql);
            
            if ($result->num_rows == 0) {
                // Add student_documents_pdf field
                $sql = "ALTER TABLE `{$this->table}` ADD COLUMN `student_documents_pdf` VARCHAR(255) DEFAULT NULL COMMENT 'Compressed PDF documents filename (student_id.pdf)' AFTER `student_profile_img`";
                $this->db->query($sql);
            }
        } catch (Exception $e) {
            // Column might already exist or other error
            // Silently continue
        }
    }
    
    /**
     * Add allowance_eligible_date column to student table if it doesn't exist
     */
    public function addAllowanceEligibleDateColumnIfNotExists() {
        try {
            // Check if allowance_eligible_date field exists
            $checkSql = "SHOW COLUMNS FROM `{$this->table}` LIKE 'allowance_eligible_date'";
            $result = $this->db->query($checkSql);
            
            if ($result->num_rows == 0) {
                // Add allowance_eligible_date field
                $sql = "ALTER TABLE `{$this->table}` ADD COLUMN `allowance_eligible_date` DATE DEFAULT NULL COMMENT 'Date from which student becomes eligible for allowance' AFTER `allowance_eligible`";
                $this->db->query($sql);
            }
        } catch (Exception $e) {
            // Column might already exist or other error
            // Silently continue
        }
    }
    
    /**
     * Add student_profile_img column to student table if it doesn't exist
     * Also migrates data from file_path column if it exists
     */
    public function addStudentProfileImgColumnIfNotExists() {
        try {
            // Check if student_profile_img field exists
            $checkSql = "SHOW COLUMNS FROM `{$this->table}` LIKE 'student_profile_img'";
            $result = $this->db->query($checkSql);
            
            if ($result->num_rows == 0) {
                // Add student_profile_img field
                $sql = "ALTER TABLE `{$this->table}` ADD COLUMN `student_profile_img` VARCHAR(255) DEFAULT NULL COMMENT 'Profile image path relative to assets folder' AFTER `student_status`";
                $this->db->query($sql);
                
                // Migrate data from file_path if it exists
                $checkFilePathSql = "SHOW COLUMNS FROM `{$this->table}` LIKE 'file_path'";
                $filePathResult = $this->db->query($checkFilePathSql);
                if ($filePathResult->num_rows > 0) {
                    // Migrate existing data
                    $migrateSql = "UPDATE `{$this->table}` SET `student_profile_img` = `file_path` WHERE `file_path` IS NOT NULL AND `file_path` != ''";
                    $this->db->query($migrateSql);
                }
            }
        } catch (Exception $e) {
            // Column might already exist or other error
            // Silently continue
        }
    }
    
    /**
     * Get profile image path for a student
     * Database stores path relative to assets (e.g., "img/Studnet_profile/filename.jpg")
     * Returns: assets/img/Studnet_profile/filename.jpg format
     */
    public function getProfileImagePath($student) {
        // Ensure student_profile_img column exists
        $this->addStudentProfileImgColumnIfNotExists();
        
        // Check both student_profile_img and file_path (for backward compatibility)
        $imagePath = $student['student_profile_img'] ?? $student['file_path'] ?? null;
        
        if (empty($imagePath)) {
            return null;
        }
        
        // Remove leading slash if present
        $imagePath = ltrim($imagePath, '/');
        
        // Remove 'assets/' prefix if present (shouldn't be, but handle it)
        if (strpos($imagePath, 'assets/') === 0) {
            $imagePath = substr($imagePath, 7);
        }
        
        // Convert old paths to new Studnet_profile path
        if (strpos($imagePath, 'img/student_profile/') === 0) {
            $imagePath = str_replace('img/student_profile/', 'img/Studnet_profile/', $imagePath);
        }
        if (strpos($imagePath, 'img/Student_profile/') === 0) {
            $imagePath = str_replace('img/Student_profile/', 'img/Studnet_profile/', $imagePath);
        }
        
        // If path doesn't start with 'img/Studnet_profile/', assume it's just a filename
        if (strpos($imagePath, 'img/Studnet_profile/') !== 0) {
            // If it's just a filename, prepend the standard path
            $imagePath = 'img/Studnet_profile/' . basename($imagePath);
        }
        
        // Check if file exists in assets directory
        $fullPath = BASE_PATH . '/assets/' . $imagePath;
        if (file_exists($fullPath)) {
            // Return in format: assets/img/Studnet_profile/filename.jpg
            return APP_URL . '/assets/' . $imagePath;
        }
        
        return null;
    }
}

