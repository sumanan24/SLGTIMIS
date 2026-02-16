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
        
        // Prevent students from accessing HOD dashboard
        if (isset($_SESSION['user_table']) && $_SESSION['user_table'] === 'student') {
            $_SESSION['error'] = 'Access denied. Students cannot access this dashboard.';
            $this->redirect('student/dashboard');
            return;
        }
        
        // Check if user has access: HOD, IN1, IN2, IN3, FIN, ACC, DIR, REG
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        $allowedRoles = ['HOD', 'IN1', 'IN2', 'IN3', 'FIN', 'ACC', 'DIR', 'REG'];
        
        if (!in_array($userRole, $allowedRoles)) {
            $_SESSION['error'] = 'Access denied. This dashboard is only available for authorized roles.';
            $this->redirect('dashboard');
            return;
        }
        
        try {
            // Get user's department (for HOD, IN1, IN2, or IN3)
            // FIN, ACC, DIR, REG may not have a department
            $userDepartmentId = $this->getUserDepartment();
            $department = null;
            
            // Load models
            $studentModel = $this->model('StudentModel');
            $staffModel = $this->model('StaffModel');
            $courseModel = $this->model('CourseModel');
            $departmentModel = $this->model('DepartmentModel');
            $hostelModel = $this->model('HostelModel');
            $roomAllocationModel = $this->model('RoomAllocationModel');
            
            // Get department information if user has a department
            if ($userDepartmentId) {
                $department = $departmentModel->getById($userDepartmentId);
                if (!$department) {
                    $userDepartmentId = null; // Reset if department not found
                }
            }
            
            // Get academic years and set default to last one
            $academicYears = $studentModel->getAcademicYears();
            $selectedAcademicYear = $this->get('academic_year', '');
            
            // If no academic year selected, use the last one
            if (empty($selectedAcademicYear) && !empty($academicYears)) {
                $selectedAcademicYear = $academicYears[0]; // First one is the latest (DESC order)
            }
            
            // Initialize variables
            $totalStudents = 0;
            $totalStudentsByYear = 0;
            $totalCourses = 0;
            $totalStaff = 0;
            $recentStudents = [];
            $departmentEnrollment = [];
            $departmentNVQStats = [];
            $departmentGenderStats = [];
            $departmentReligionStats = [];
            $departmentDistrictStats = [];
            
            // Get department-specific data only if user has a department
            if ($userDepartmentId) {
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
                if (isset($courseEnrollment[$userDepartmentId])) {
                    $departmentEnrollment[$userDepartmentId] = $courseEnrollment[$userDepartmentId];
                }
                
                // Get NVQ stats for department only
                $nvqStatsByDepartment = $studentModel->getStudentsByNVQLevelAndDepartment($selectedAcademicYear);
                if (isset($nvqStatsByDepartment[$userDepartmentId])) {
                    $departmentNVQStats[$userDepartmentId] = $nvqStatsByDepartment[$userDepartmentId];
                }
                
                // Get gender stats for department
                $departmentGenderStats = $this->getDepartmentGenderStats($studentModel, $userDepartmentId, $selectedAcademicYear);
                
                // Get religion stats for department
                $departmentReligionStats = $this->getDepartmentReligionStats($studentModel, $userDepartmentId, $selectedAcademicYear);
                
                // Get district stats for department
                $departmentDistrictStats = $this->getDepartmentDistrictStats($studentModel, $userDepartmentId, $selectedAcademicYear);
            }
            
            // Get hostel information (for HOD, IN1, IN2, IN3, FIN, ACC, DIR, REG)
            $hostelStats = $this->getHostelStatistics($hostelModel, $roomAllocationModel, $userDepartmentId, $selectedAcademicYear);
            
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
                'hostelStats' => $hostelStats,
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
                WHERE s.student_status = 'Active'
                AND se.student_enroll_status = 'Following' 
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
    
    /**
     * Get hostel statistics
     */
    private function getHostelStatistics($hostelModel, $roomAllocationModel, $departmentId = null, $academicYear = null) {
        $db = Database::getInstance();
        
        // Get all hostels
        $allHostels = $hostelModel->getAll();
        
        // Get hostel statistics
        $hostelStats = [];
        $totalHostels = count($allHostels);
        $totalRooms = 0;
        $totalCapacity = 0;
        $totalOccupied = 0;
        $departmentHostelStudents = 0;
        
        $roomModel = $this->model('RoomModel');
        
        foreach ($allHostels as $hostel) {
            // Get rooms for this hostel using RoomModel method
            $rooms = $roomModel->getByHostelId($hostel['id']);
            
            $hostelRoomCount = count($rooms);
            $hostelCapacity = 0;
            $hostelOccupied = 0;
            
            foreach ($rooms as $room) {
                $roomCapacity = (int)($room['capacity'] ?? 0);
                $hostelCapacity += $roomCapacity;
                
                // Get occupied beds
                $sql = "SELECT COUNT(*) as count FROM `hostel_allocations` 
                        WHERE room_id = ? AND status = 'active'";
                $stmt = $db->prepare($sql);
                if ($stmt) {
                    $roomId = $room['id'];
                    $stmt->bind_param('s', $roomId);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($row = $result->fetch_assoc()) {
                        $hostelOccupied += (int)$row['count'];
                    }
                }
            }
            
            $totalRooms += $hostelRoomCount;
            $totalCapacity += $hostelCapacity;
            $totalOccupied += $hostelOccupied;
            
            // Get department students in this hostel
            if ($departmentId) {
                $sql = "SELECT COUNT(DISTINCT ha.student_id) as count 
                        FROM `hostel_allocations` ha
                        INNER JOIN `student` s ON ha.student_id = s.student_id
                        INNER JOIN `student_enroll` se ON s.student_id = se.student_id
                        INNER JOIN `course` c ON se.course_id = c.course_id
                        INNER JOIN `hostel_rooms` r ON ha.room_id = r.id
                        INNER JOIN `hostel_blocks` b ON r.block_id = b.id
                        WHERE ha.status = 'active'
                        AND se.student_enroll_status = 'Following'
                        AND c.department_id = ?
                        AND b.hostel_id = ?";
                
                if (!empty($academicYear)) {
                    $sql .= " AND se.academic_year = ?";
                }
                
                $stmt = $db->prepare($sql);
                if ($stmt) {
                    if (!empty($academicYear)) {
                        $stmt->bind_param('sss', $departmentId, $hostel['id'], $academicYear);
                    } else {
                        $stmt->bind_param('ss', $departmentId, $hostel['id']);
                    }
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($row = $result->fetch_assoc()) {
                        $deptCount = (int)$row['count'];
                        $departmentHostelStudents += $deptCount;
                    }
                }
            }
            
            $hostelStats[] = [
                'id' => $hostel['id'],
                'name' => $hostel['name'],
                'location' => $hostel['location'] ?? '',
                'gender' => $hostel['gender'] ?? '',
                'room_count' => $hostelRoomCount,
                'capacity' => $hostelCapacity,
                'occupied' => $hostelOccupied,
                'available' => $hostelCapacity - $hostelOccupied,
                'occupancy_rate' => $hostelCapacity > 0 ? round(($hostelOccupied / $hostelCapacity) * 100, 1) : 0
            ];
        }
        
        return [
            'total_hostels' => $totalHostels,
            'total_rooms' => $totalRooms,
            'total_capacity' => $totalCapacity,
            'total_occupied' => $totalOccupied,
            'total_available' => $totalCapacity - $totalOccupied,
            'department_students' => $departmentHostelStudents,
            'hostels' => $hostelStats
        ];
    }
}

