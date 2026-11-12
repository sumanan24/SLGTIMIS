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
        $allowedRoles = ['HOD', 'IN1', 'IN2', 'IN3', 'LE1', 'LE2', 'SLE', 'FIN', 'ACC', 'DIR', 'REG'];
        
        // Anyone assigned as Permanent / Acting / Cover-up head of a department may open it too.
        $headedIds = $userModel->getHeadedDepartmentIds((int) $_SESSION['user_id']);
        if (!in_array($userRole, $allowedRoles) && empty($headedIds)) {
            $_SESSION['error'] = 'Access denied. This dashboard is only available for authorized roles.';
            $this->redirect('dashboard');
            return;
        }

        // Department switcher (a head can hold more than one department, e.g. own post + Acting).
        $requestedDepartment = trim((string) $this->get('department_id', ''));
        if ($requestedDepartment !== '') {
            if (!$this->setActiveDepartment($requestedDepartment)) {
                $_SESSION['error'] = 'You are not assigned to that department.';
            }
            $this->redirect('hod/dashboard' . (($ay = trim((string) $this->get('academic_year', ''))) !== '' ? '?academic_year=' . urlencode($ay) : ''));
            return;
        }
        
        try {
            // Get user's department (for HOD, IN1, IN2, or IN3)
            // FIN, ACC, DIR, REG may not have a department
            $userDepartmentId = $this->getUserDepartment();
            if (!$userDepartmentId && in_array($userRole, ['LE1', 'LE2', 'SLE'], true)) {
                // Lecturers: use the department on their own staff record
                $userDepartmentId = $userModel->getStaffDepartmentId((int) $_SESSION['user_id']);
            }
            $department = null;
            
            // Load models
            $studentModel = $this->model('StudentModel');
            $staffModel = $this->model('StaffModel');
            $courseModel = $this->model('CourseModel');
            $departmentModel = $this->model('DepartmentModel');
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
            $departmentEnrollment = [];
            $departmentCourses = [];
            $courseModuleRows = [];
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

                // Courses with their module counts (for the Courses & Modules section)
                $workspaceCounts = $courseModel->getWorkspaceCounts(array_column($departmentCourses, 'course_id'));
                foreach ($departmentCourses as $c) {
                    $cid = (string) ($c['course_id'] ?? '');
                    $courseModuleRows[] = [
                        'course_id' => $cid,
                        'course_name' => $c['course_name'] ?? $cid,
                        'course_nvq_level' => $c['course_nvq_level'] ?? '',
                        'course_status' => $c['course_status'] ?? 'active',
                        'modules' => $workspaceCounts[$cid]['modules'] ?? 0,
                        'groups' => $workspaceCounts[$cid]['groups'] ?? 0,
                    ];
                }
                
                // Get department staff count
                $totalStaff = $staffModel->getTotalStaff('', $userDepartmentId);
                
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
            
            // Department assignments (Permanent / Acting / Cover-up) of this login
            $assignments = [];
            $labels = DepartmentModel::headTypes();
            foreach ($headedIds as $hid) {
                $row = $departmentModel->getById($hid);
                if (!$row) {
                    continue;
                }
                $type = strtolower((string) ($row['head_type'] ?? ''));
                $assignments[] = [
                    'department_id' => (string) $row['department_id'],
                    'department_name' => (string) ($row['department_name'] ?? $hid),
                    'head_type' => $type,
                    'head_type_label' => $labels[$type] ?? '',
                    'start_date' => $row['head_start_date'] ?? null,
                    'remarks' => $row['head_remarks'] ?? null,
                    'active' => ((string) $row['department_id'] === (string) $userDepartmentId),
                ];
            }
            // Own department (staff record) as a switch target when it differs from the assigned ones
            $ownDepartment = null;
            $ownId = $userModel->getStaffDepartmentId((int) $_SESSION['user_id']);
            if ($ownId && !in_array($ownId, $headedIds, true) && !empty($assignments)) {
                $ownRow = $departmentModel->getById($ownId);
                if ($ownRow) {
                    $ownDepartment = [
                        'department_id' => (string) $ownRow['department_id'],
                        'department_name' => (string) ($ownRow['department_name'] ?? $ownId),
                        'active' => ((string) $ownRow['department_id'] === (string) $userDepartmentId),
                    ];
                }
            }
            $activeAssignment = null;
            foreach ($assignments as $a) {
                if ($a['active']) {
                    $activeAssignment = $a;
                    break;
                }
            }

            $flashError = $_SESSION['error'] ?? null;
            unset($_SESSION['error']);

            // Department student counts by enrolment status
            $studentStatusCounts = [];
            if ($userDepartmentId) {
                try {
                    $studentStatusCounts = $this->getDepartmentEnrollmentBreakdown($userDepartmentId, $selectedAcademicYear);
                } catch (Throwable $ex) {
                    error_log('HODDashboard status counts: ' . $ex->getMessage());
                }
            }

            // "My work": modules, timetable, upcoming programmes and tickets of the logged-in staff member
            $my = ['staff_id' => '', 'modules' => [], 'timetable' => [], 'programs' => [], 'tickets' => [], 'ticket_total' => 0];
            $loginRow = $userModel->find((int) $_SESSION['user_id']);
            if ($loginRow && ($loginRow['user_table'] ?? '') === 'staff' && !empty($loginRow['user_name'])) {
                $my['staff_id'] = (string) $loginRow['user_name'];
                $sid = $my['staff_id'];
                try {
                    $my['modules'] = $this->model('StaffModuleEnrollmentModel')->getByStaff($sid, (string) $selectedAcademicYear);
                } catch (Throwable $ex) {
                    error_log('HODDashboard modules: ' . $ex->getMessage());
                }
                try {
                    $my['timetable'] = $this->model('GroupTimetableModel')->listByStaff($sid);
                } catch (Throwable $ex) {
                    error_log('HODDashboard timetable: ' . $ex->getMessage());
                }
                try {
                    $my['programs'] = $this->model('CircuitProgramModel')->upcomingForStaff($sid, 5);
                } catch (Throwable $ex) {
                    error_log('HODDashboard programs: ' . $ex->getMessage());
                }
                try {
                    $res = $this->model('FacilitiesTicketModel')->search(['assigned_to_me' => 1, 'scope_staff_id' => $sid], 1, 100);
                    $closed = ['closed', 'cancelled', 'rejected'];
                    $open = array_values(array_filter($res['rows'] ?? [], static function ($r) use ($closed) {
                        return !in_array($r['status'] ?? '', $closed, true);
                    }));
                    $my['ticket_total'] = count($open);
                    $my['tickets'] = array_slice($open, 0, 5);
                } catch (Throwable $ex) {
                    error_log('HODDashboard tickets: ' . $ex->getMessage());
                }
            }

            // Student attendance shortcuts (mark + locked report) for HOD / IN / assigned heads
            $isAssignedHead = $userModel->isAssignedDepartmentHead((int) $_SESSION['user_id']);
            $canMarkAttendance = in_array($userRole, ['HOD', 'IN1', 'IN2', 'IN3'], true)
                || $isAssignedHead
                || $userModel->isAdmin((int) $_SESSION['user_id']);
            $canViewAttendanceReport = $canMarkAttendance
                || in_array($userRole, ['DIR', 'DPI', 'DPA', 'REG', 'FIN', 'ACC', 'SAO', 'ADM'], true)
                || $userModel->isFinanceDepartmentAssistant((int) $_SESSION['user_id']);
            $attendanceMonth = date('Y-m');
            $attendanceLockStatus = null;
            if ($userDepartmentId && $canViewAttendanceReport) {
                try {
                    $attendanceLockStatus = $this->model('AttendanceMonthLockModel')
                        ->getLockStatus($userDepartmentId, $attendanceMonth);
                } catch (Throwable $ex) {
                    $attendanceLockStatus = null;
                }
            }
            $attendanceMonthLocked = is_array($attendanceLockStatus)
                && (($attendanceLockStatus['status'] ?? '') === 'locked');

            $data = [
                'studentStatusCounts' => $studentStatusCounts,
                'my' => $my,
                'error' => $flashError,
                'assignments' => $assignments,
                'ownDepartment' => $ownDepartment,
                'activeAssignment' => $activeAssignment,
                'courseModuleRows' => $courseModuleRows,
                'canManageCourses' => $this->isDepartmentManager(),
                'canMarkAttendance' => $canMarkAttendance,
                'canViewAttendanceReport' => $canViewAttendanceReport,
                'attendanceMonth' => $attendanceMonth,
                'attendanceMonthLocked' => $attendanceMonthLocked,
                'attendanceLockStatus' => $attendanceLockStatus,
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
                'courseEnrollmentByDepartment' => $departmentEnrollment,
                'nvqStatsByDepartment' => $departmentNVQStats,
                'genderStats' => $departmentGenderStats,
                'religionStats' => $departmentReligionStats,
                'districtStats' => $departmentDistrictStats,
                'academicYears' => $academicYears,
                'selectedAcademicYear' => $selectedAcademicYear
            ];
            
            return $this->view('hod/dashboard', $data);
        } catch (Throwable $e) {
            error_log('HODDashboardController::index: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            return $this->view('errors/app', [
                'title' => 'Dashboard Error',
                'page' => 'dashboard',
                'error' => 'Error loading dashboard: ' . $e->getMessage(),
            ]);
        }
    }
    
    /**
     * Distinct students of a department for an academic year, each split into full time and part time.
     * "total" = current students (Following + Internship, same rule as StudentModel::getTotalStudents);
     * Completed students are counted separately and are not part of the total.
     *
     * @return array<string, array{total:int, full:int, part:int}>
     */
    private function getDepartmentEnrollmentBreakdown($departmentId, $academicYear = null) {
        $out = [];
        foreach (['total', 'following', 'internship', 'completed'] as $k) {
            $out[$k] = ['total' => 0, 'full' => 0, 'part' => 0];
        }
        $full = "LOWER(TRIM(se.`course_mode`)) LIKE 'full%'";
        $part = "LOWER(TRIM(se.`course_mode`)) LIKE 'part%'";
        $cols = [];
        foreach (['total' => "se.`student_enroll_status` IN ('Following', 'Internship')", 'following' => "se.`student_enroll_status` = 'Following'", 'internship' => "se.`student_enroll_status` = 'Internship'", 'completed' => "se.`student_enroll_status` = 'Completed'"] as $k => $cond) {
            $cols[] = "COUNT(DISTINCT CASE WHEN {$cond} THEN se.`student_id` END) AS {$k}_total";
            $cols[] = "COUNT(DISTINCT CASE WHEN {$cond} AND {$full} THEN se.`student_id` END) AS {$k}_full";
            $cols[] = "COUNT(DISTINCT CASE WHEN {$cond} AND {$part} THEN se.`student_id` END) AS {$k}_part";
        }
        $sql = "SELECT " . implode(', ', $cols) . "
                FROM `student_enroll` se
                INNER JOIN `course` c ON c.`course_id` = se.`course_id`
                WHERE c.`department_id` = ?";
        $params = [$departmentId];
        $types = 's';
        if (!empty($academicYear)) {
            $sql .= " AND se.`academic_year` = ?";
            $params[] = $academicYear;
            $types .= 's';
        }
        $stmt = Database::getInstance()->prepare($sql);
        if (!$stmt) {
            return $out;
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) {
            foreach (array_keys($out) as $k) {
                $out[$k] = [
                    'total' => (int) $row[$k . '_total'],
                    'full' => (int) $row[$k . '_full'],
                    'part' => (int) $row[$k . '_part'],
                ];
            }
        }
        return $out;
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

