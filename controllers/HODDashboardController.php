<?php
/**
 * HOD Dashboard Controller
 * Shows dashboard with only the user's department details
 * Supports HOD, IN1, IN2, and IN3 roles (department-restricted access)
 */

class HODDashboardController extends Controller {
    
    public function index() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Check if user is HOD, IN1, IN2, or IN3 (department-restricted roles)
        if (!$this->isDepartmentRestricted()) {
            $_SESSION['error'] = 'Access denied. This dashboard is only available for Head of Department or Instructors.';
            $this->redirect('dashboard');
            return;
        }
        
        try {
            // Get user's department (for HOD, IN1, IN2, or IN3)
            $userDepartmentId = $this->getUserDepartment();
            if (!$userDepartmentId) {
                $_SESSION['error'] = 'Department information not found. Please contact administrator.';
                $this->redirect('dashboard');
                return;
            }
            
            // Load models
            $studentModel = $this->model('StudentModel');
            $staffModel = $this->model('StaffModel');
            $courseModel = $this->model('CourseModel');
            $departmentModel = $this->model('DepartmentModel');
            
            // Get department information
            $department = $departmentModel->getById($userDepartmentId);
            if (!$department) {
                $_SESSION['error'] = 'Department not found.';
                $this->redirect('dashboard');
                return;
            }
            
            // Get academic years and set default to last one
            $academicYears = $studentModel->getAcademicYears();
            $selectedAcademicYear = $this->get('academic_year', '');
            
            // If no academic year selected, use the last one
            if (empty($selectedAcademicYear) && !empty($academicYears)) {
                $selectedAcademicYear = $academicYears[0]; // First one is the latest (DESC order)
            }
            
            // Fetch data filtered by user's department and academic year
            $filters = [
                'department_id' => $userDepartmentId,
                'academic_year' => $selectedAcademicYear
            ];
            
            // Get department statistics
            $totalStudents = $studentModel->getTotalStudents($filters);
            $totalStudentsByYear = $totalStudents;
            
            // Get department courses
            $departmentCourses = $courseModel->getCoursesWithDepartment(['department_id' => $userDepartmentId]);
            $totalCourses = count($departmentCourses);
            
            // Get department staff count
            $totalStaff = $staffModel->getTotalStaff('', $userDepartmentId);
            
            // Get recent students in department
            $recentStudents = $this->getDepartmentRecentStudents($studentModel, $userDepartmentId, $selectedAcademicYear, 5);
            
            // Get course enrollment for department only
            $courseEnrollment = $studentModel->getCourseEnrollmentByDepartment($selectedAcademicYear);
            // Filter to show only user's department
            $departmentEnrollment = [];
            if (isset($courseEnrollment[$userDepartmentId])) {
                $departmentEnrollment[$userDepartmentId] = $courseEnrollment[$userDepartmentId];
            }
            
            // Get NVQ stats for department only
            $nvqStatsByDepartment = $studentModel->getStudentsByNVQLevelAndDepartment($selectedAcademicYear);
            $departmentNVQStats = [];
            if (isset($nvqStatsByDepartment[$userDepartmentId])) {
                $departmentNVQStats[$userDepartmentId] = $nvqStatsByDepartment[$userDepartmentId];
            }
            
            // Get gender stats for department
            $genderStats = $studentModel->getStudentsByGender($selectedAcademicYear);
            // Note: Gender stats don't filter by department in the model, so we'll calculate separately
            $departmentGenderStats = $this->getDepartmentGenderStats($studentModel, $userDepartmentId, $selectedAcademicYear);
            
            // Get religion stats for department
            $departmentReligionStats = $this->getDepartmentReligionStats($studentModel, $userDepartmentId, $selectedAcademicYear);
            
            // Get district stats for department
            $departmentDistrictStats = $this->getDepartmentDistrictStats($studentModel, $userDepartmentId, $selectedAcademicYear);
            
            // Final deduplication check - ensure each student appears only once
            $uniqueStudents = [];
            $seenIds = [];
            foreach ($recentStudents as $student) {
                $id = $student['student_id'] ?? null;
                if ($id && !in_array($id, $seenIds)) {
                    $uniqueStudents[] = $student;
                    $seenIds[] = $id;
                }
            }
            
            $data = [
                'title' => 'Department Dashboard',
                'page' => 'dashboard',
                'user_name' => $_SESSION['user_name'] ?? 'User',
                'department' => $department,
                'department_id' => $userDepartmentId,
                'totalStudents' => $totalStudents,
                'totalStudentsByYear' => $totalStudentsByYear,
                'totalStaff' => $totalStaff,
                'totalCourses' => $totalCourses,
                'courses' => $departmentCourses,
                'recentStudents' => array_values($uniqueStudents),
                'courseEnrollmentByDepartment' => $departmentEnrollment,
                'nvqStatsByDepartment' => $departmentNVQStats,
                'genderStats' => $departmentGenderStats,
                'religionStats' => $departmentReligionStats,
                'districtStats' => $departmentDistrictStats,
                'academicYears' => $academicYears,
                'selectedAcademicYear' => $selectedAcademicYear
            ];
            
            return $this->view('hod/dashboard', $data);
        } catch (Exception $e) {
            $data = [
                'title' => 'Dashboard Error',
                'error' => 'Error loading dashboard: ' . $e->getMessage()
            ];
            return $this->view('errors/404', $data);
        }
    }
    
    /**
     * Get gender statistics for HOD's department
     */
    private function getDepartmentGenderStats($studentModel, $departmentId, $academicYear = null) {
        $db = Database::getInstance();
        $sql = "SELECT s.student_gender, COUNT(DISTINCT s.student_id) as count 
                FROM `student` s
                INNER JOIN `student_enroll` se ON s.student_id = se.student_id
                INNER JOIN `course` c ON se.course_id = c.course_id
                WHERE se.student_enroll_status = 'Following' 
                AND c.department_id = ?
                AND s.student_gender IS NOT NULL AND s.student_gender != ''";
        
        $params = [$departmentId];
        $types = 's';
        
        if (!empty($academicYear)) {
            $sql .= " AND se.academic_year = ?";
            $params[] = $academicYear;
            $types .= 's';
        }
        
        $sql .= " GROUP BY s.student_gender ORDER BY s.student_gender";
        
        $stmt = $db->prepare($sql);
        if (!$stmt) {
            return [];
        }
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[$row['student_gender']] = (int)$row['count'];
            }
        }
        
        return $data;
    }
    
    /**
     * Get religion statistics for HOD's department
     */
    private function getDepartmentReligionStats($studentModel, $departmentId, $academicYear = null) {
        $db = Database::getInstance();
        $sql = "SELECT s.student_religion, COUNT(DISTINCT s.student_id) as count 
                FROM `student` s
                INNER JOIN `student_enroll` se ON s.student_id = se.student_id
                INNER JOIN `course` c ON se.course_id = c.course_id
                WHERE se.student_enroll_status = 'Following' 
                AND c.department_id = ?
                AND s.student_religion IS NOT NULL AND s.student_religion != ''";
        
        $params = [$departmentId];
        $types = 's';
        
        if (!empty($academicYear)) {
            $sql .= " AND se.academic_year = ?";
            $params[] = $academicYear;
            $types .= 's';
        }
        
        $sql .= " GROUP BY s.student_religion ORDER BY count DESC";
        
        $stmt = $db->prepare($sql);
        if (!$stmt) {
            return [];
        }
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = [
                    'name' => $row['student_religion'],
                    'count' => (int)$row['count']
                ];
            }
        }
        
        return $data;
    }
    
    /**
     * Get district statistics for HOD's department
     */
    private function getDepartmentDistrictStats($studentModel, $departmentId, $academicYear = null) {
        $db = Database::getInstance();
        $sql = "SELECT s.student_district, COUNT(DISTINCT s.student_id) as count 
                FROM `student` s
                INNER JOIN `student_enroll` se ON s.student_id = se.student_id
                INNER JOIN `course` c ON se.course_id = c.course_id
                WHERE se.student_enroll_status = 'Following' 
                AND c.department_id = ?
                AND s.student_district IS NOT NULL AND s.student_district != ''";
        
        $params = [$departmentId];
        $types = 's';
        
        if (!empty($academicYear)) {
            $sql .= " AND se.academic_year = ?";
            $params[] = $academicYear;
            $types .= 's';
        }
        
        $sql .= " GROUP BY s.student_district ORDER BY count DESC LIMIT 10";
        
        $stmt = $db->prepare($sql);
        if (!$stmt) {
            return [];
        }
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[$row['student_district']] = (int)$row['count'];
            }
        }
        
        return $data;
    }
    
    /**
     * Get recent students for HOD's department
     */
    private function getDepartmentRecentStudents($studentModel, $departmentId, $academicYear = null, $limit = 5) {
        $db = Database::getInstance();
        $sql = "SELECT DISTINCT s.student_id, s.student_fullname, s.student_email, s.student_status 
                FROM `student` s
                INNER JOIN `student_enroll` se ON s.student_id = se.student_id
                INNER JOIN `course` c ON se.course_id = c.course_id
                WHERE se.student_enroll_status = 'Following' 
                AND c.department_id = ?";
        
        $params = [$departmentId];
        $types = 's';
        
        if (!empty($academicYear)) {
            $sql .= " AND se.academic_year = ?";
            $params[] = $academicYear;
            $types .= 's';
        }
        
        $sql .= " GROUP BY s.student_id ORDER BY s.student_id DESC LIMIT ?";
        $params[] = (int)$limit;
        $types .= 'i';
        
        $stmt = $db->prepare($sql);
        if (!$stmt) {
            return [];
        }
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        $seenIds = [];
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $studentId = $row['student_id'] ?? null;
                if ($studentId && !in_array($studentId, $seenIds)) {
                    $data[] = $row;
                    $seenIds[] = $studentId;
                }
            }
        }
        
        return $data;
    }
}

