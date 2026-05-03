<?php
/**
 * Attendance Controller
 */

class AttendanceController extends Controller {
    
    /**
     * Check if user has attendance access (HOD, IN1, IN2, IN3 only)
     */
    private function checkAttendanceAccess() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return false;
        }
        
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        $isAdmin = $userModel->isAdmin($_SESSION['user_id']);
        
        // Allow HOD, IN1, IN2, IN3, and Admin
        $allowedRoles = ['HOD', 'IN1', 'IN2', 'IN3'];
        $hasAccess = in_array($userRole, $allowedRoles) || $isAdmin;
        
        if (!$hasAccess) {
            $_SESSION['error'] = 'Access denied. Only HOD, IN1, IN2, and IN3 can access attendance.';
            $this->redirect('dashboard');
            return false;
        }
        
        return true;
    }
    
    public function index() {
        // Check authentication and access
        if (!$this->checkAttendanceAccess()) {
            return;
        }
        
        $departmentModel = $this->model('DepartmentModel');
        $courseModel = $this->model('CourseModel');
        $studentModel = $this->model('StudentModel');
        $attendanceModel = $this->model('AttendanceModel');
        
        // HOD / IN1–IN3: own department only; ADM / admin: any department
        $userDepartmentId = $this->getUserDepartment();
        if ($this->isDepartmentRestricted()) {
            if ($userDepartmentId === null || $userDepartmentId === '') {
                $_SESSION['error'] = 'Your account has no department assigned. Please contact the administrator.';
                $this->redirect('dashboard');
                return;
            }
            $departmentId = trim((string) $userDepartmentId);
        } else {
            $departmentId = $this->get('department_id', '');
        }
        $courseId = $this->get('course_id', '');
        $academicYear = $this->get('academic_year', '');
        $month = $this->get('month', date('Y-m'));
        $group = $this->get('group', '');
        
        // Get filter options - only show user's department if department-restricted
        if ($userDepartmentId) {
            $dept = $departmentModel->getById($userDepartmentId);
            $departments = $dept ? [$dept] : [];
        } else {
            $departments = $departmentModel->getAll();
        }
        $academicYears = $studentModel->getAcademicYears();
        
        // Get courses based on department
        $courses = [];
        if (!empty($departmentId)) {
            $courses = $courseModel->getCoursesWithDepartment(['department_id' => $departmentId]);
        }
        
        // Get groups based on course and academic year
        $groups = [];
        if (!empty($courseId) && !empty($academicYear)) {
            $groupModel = $this->model('GroupModel');
            $groups = $groupModel->getGroupsByCourseAndYear($courseId, $academicYear);
        }
        
        // Get students based on filters
        $students = [];
        $attendanceData = [];
        
        if (!empty($departmentId) && !empty($courseId) && !empty($academicYear)) {
            $filters = [
                'department_id' => $departmentId,
                'course_id' => $courseId,
                'academic_year' => $academicYear
            ];
            
            // Add group filter if provided
            if (!empty($group)) {
                $filters['group_id'] = $group;
            }
            
            $students = $attendanceModel->getStudentsForAttendance($filters);
            
            // Get date range for the month
            $startDate = $month . '-01';
            $endDate = date('Y-m-t', strtotime($startDate));
            
            // Get existing attendance records
            foreach ($students as &$student) {
                $studentAttendance = $attendanceModel->getAttendanceByStudentAndDateRange(
                    $student['student_id'], 
                    $startDate, 
                    $endDate
                );
                $attendanceData[$student['student_id']] = $studentAttendance;
            }
        }
        
        // Generate calendar days for the month (excluding weekends)
        $calendarDays = $this->generateCalendarDays($month);
        
        // Get holiday dates from attendance data
        $holidayDates = [];
        if (!empty($attendanceData)) {
            foreach ($attendanceData as $studentAttendance) {
                foreach ($studentAttendance as $date => $status) {
                    if ($status == -1 && !in_array($date, $holidayDates)) {
                        $holidayDates[] = $date;
                    }
                }
            }
        }
        
        // Check if month is locked for this department
        $lockModel = $this->model('AttendanceMonthLockModel');
        $isMonthLocked = false;
        $lockStatus = null;
        $isAdmin = false;
        
        if (!empty($departmentId) && !empty($month)) {
            $lockStatus = $lockModel->getLockStatus($departmentId, $month);
            $isMonthLocked = ($lockStatus && $lockStatus['status'] === 'locked');
        }
        
        // Check if user is admin
        if (isset($_SESSION['user_id'])) {
            require_once BASE_PATH . '/models/UserModel.php';
            $userModel = new UserModel();
            $isAdmin = $userModel->isAdmin($_SESSION['user_id']);
        }
        
        $data = [
            'title' => 'Student Attendance',
            'page' => 'attendance',
            'departments' => $departments,
            'courses' => $courses,
            'academicYears' => $academicYears,
            'groups' => $groups,
            'students' => $students,
            'attendanceData' => $attendanceData,
            'calendarDays' => $calendarDays,
            'holidayDates' => $holidayDates,
            'selectedDepartment' => $departmentId,
            'selectedCourse' => $courseId,
            'selectedAcademicYear' => $academicYear,
            'selectedMonth' => $month,
            'selectedGroup' => $group,
            'isMonthLocked' => $isMonthLocked,
            'isAdmin' => $isAdmin,
            'lockStatus' => $lockStatus,
            'error' => $_SESSION['error'] ?? null,
            'message' => $_SESSION['message'] ?? null,
            'lockDepartmentSelection' => $this->isDepartmentRestricted(),
            'forcedDepartmentId' => $this->isDepartmentRestricted() ? (string) $userDepartmentId : '',
        ];
        
        unset($_SESSION['error'], $_SESSION['message']);
        return $this->view('attendance/index', $data);
    }
    
    /**
     * Bulk update attendance
     */
    public function bulkUpdate() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->json(['success' => false, 'error' => 'Unauthorized'], 401);
        }
        
        // Check attendance access
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        $isAdmin = $userModel->isAdmin($_SESSION['user_id']);
        $allowedRoles = ['HOD', 'IN1', 'IN2', 'IN3'];
        $hasAccess = in_array($userRole, $allowedRoles) || $isAdmin;
        
        if (!$hasAccess) {
            $this->json(
                ['success' => false, 'error' => 'Access denied. Only HOD, IN1, IN2, and IN3 can update attendance.'],
                403
            );
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'error' => 'Invalid request method'], 405);
        }
        
        // Get JSON input
        $jsonInput = file_get_contents('php://input');
        $data = json_decode($jsonInput, true);
        
        // Fallback to POST if JSON is not available
        if (empty($data)) {
            $attendanceData = $this->post('attendance', []);
            $moduleName = trim($this->post('module_name', 'General'));
            $departmentId = trim($this->post('department_id', ''));
            $month = trim($this->post('month', ''));
        } else {
            $attendanceData = $data['attendance'] ?? [];
            $moduleName = trim($data['module_name'] ?? 'General');
            $departmentId = trim($data['department_id'] ?? '');
            $month = trim($data['month'] ?? '');
        }
        
        // Check if month is locked (only allow admin to modify locked months)
        if (!empty($departmentId) && !empty($month)) {
            $lockModel = $this->model('AttendanceMonthLockModel');
            $lockStatus = $lockModel->getLockStatus($departmentId, $month);
            
            if ($lockStatus && $lockStatus['status'] === 'locked' && !$isAdmin) {
                $this->json([
                    'success' => false,
                    'error' => 'Attendance for this month has been locked and cannot be modified. Please contact administrator to unlock.'
                ], 423);
            }
        }
        
        if (empty($attendanceData)) {
            $this->json(['success' => false, 'error' => 'No attendance data provided'], 400);
        }
        
        // Process attendance data
        $processedData = [];
        foreach ($attendanceData as $record) {
            $studentId = trim($record['student_id'] ?? '');
            $date = trim($record['date'] ?? '');
            
            // Handle status: can be -1 (holiday), 0 (absent), 1 (present), or null/empty (not marked)
            $status = null;
            if (isset($record['status'])) {
                $status = $record['status'] === '' || $record['status'] === null ? null : (int)$record['status'];
            }
            
            // Skip if status is null/empty (not marked)
            if ($status === null) {
                continue;
            }
            
            // Validate status value (-1, 0, or 1)
            if (!in_array($status, [-1, 0, 1])) {
                continue;
            }
            
            if (!empty($studentId) && !empty($date)) {
                $processedData[] = [
                    'student_id' => $studentId,
                    'date' => $date,
                    'attendance_status' => $status,
                    'module_name' => $moduleName,
                    'staff_name' => $_SESSION['user_name'] ?? 'System'
                ];
            }
        }
        
        if (empty($processedData)) {
            $this->json(['success' => false, 'error' => 'No valid attendance records to process'], 400);
        }
        
        // Bulk update using chunk method
        try {
            $attendanceModel = $this->model('AttendanceModel');
            
            if (!$attendanceModel) {
                throw new Exception('Failed to initialize AttendanceModel');
            }
            
            $result = $attendanceModel->bulkUpdateAttendance($processedData, 100);
            
            if ($result['errors'] > 0 && $result['success'] === 0) {
                // All records failed
                $this->json([
                    'success' => false,
                    'error' => 'Failed to save attendance. Please check your database connection and try again.'
                ], 500);
            }
            
            $this->json([
                'success' => true,
                'message' => "Attendance updated successfully. {$result['success']} records processed." . ($result['errors'] > 0 ? " {$result['errors']} records failed." : ''),
                'stats' => $result
            ]);
        } catch (Exception $e) {
            error_log('Attendance bulk update error: ' . $e->getMessage());
            $this->json([
                'success' => false,
                'error' => 'Database error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Check if user has attendance report access (DIR, DPI, DPA, REG, FIN, ACC, SAO, HOD, IN1, IN2, IN3, ADM)
     */
    private function checkAttendanceReportAccess() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return false;
        }
        
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        $isAdmin = $userModel->isAdmin($_SESSION['user_id']);
        
        // Allow DIR, DPI, DPA, REG, FIN, ACC, SAO, HOD, IN1, IN2, IN3, ADM, and Admin
        $allowedRoles = ['DIR', 'DPI', 'DPA', 'REG', 'FIN', 'ACC', 'SAO', 'HOD', 'IN1', 'IN2', 'IN3', 'ADM'];
        $hasAccess = in_array($userRole, $allowedRoles) || $isAdmin;
        
        if (!$hasAccess) {
            $_SESSION['error'] = 'Access denied. Only authorized roles can view attendance reports.';
            $this->redirect('dashboard');
            return false;
        }
        
        return true;
    }
    
    /**
     * ADM (all departments) and system admin; HOD / IN1–IN3 own department only. Weekday month grid, 4 slots per day.
     */
    private function checkMonthAttendanceSheetAccess() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return false;
        }
        
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        $isAdmin = $userModel->isAdmin($_SESSION['user_id']);
        $allowedRoles = ['ADM', 'HOD', 'IN1', 'IN2', 'IN3'];
        $hasAccess = in_array($userRole, $allowedRoles, true) || $isAdmin;
        
        if (!$hasAccess) {
            $_SESSION['error'] = 'Access denied. Only ADM, HOD, IN1, IN2, IN3, or administrator can download the month attendance sheet.';
            $this->redirect('dashboard');
            return false;
        }
        
        return true;
    }
    
    /**
     * Attendance Report
     */
    public function report() {
        // Check authentication and access (different from regular attendance access)
        if (!$this->checkAttendanceReportAccess()) {
            return;
        }
        
        $departmentModel = $this->model('DepartmentModel');
        $courseModel = $this->model('CourseModel');
        $studentModel = $this->model('StudentModel');
        $attendanceModel = $this->model('AttendanceModel');
        $lockModel = $this->model('AttendanceMonthLockModel');
        
        // Get user's department if user is HOD, IN1, IN2, or IN3
        $userDepartmentId = $this->getUserDepartment();
        $isDepartmentRestricted = $this->isDepartmentRestricted();
        $isHOD = $this->isHOD();
        $isAdmin = false;
        $isFIN = false;
        $userRole = null;
        
        // Check if user is admin or FIN
        if (isset($_SESSION['user_id'])) {
            require_once BASE_PATH . '/models/UserModel.php';
            $userModel = new UserModel();
            $isAdmin = $userModel->isAdmin($_SESSION['user_id']);
            $userRole = $userModel->getUserRole($_SESSION['user_id']);
            $isFIN = ($userRole === 'FIN');
        }
        
        // Get filter parameters - use user's department if department-restricted user
        $departmentId = $userDepartmentId ? $userDepartmentId : $this->get('department_id', '');
        $courseId = $this->get('course_id', '');
        $academicYear = $this->get('academic_year', '');
        $month = $this->get('month', date('Y-m'));
        // FIN users see only eligible (allowance-eligible) students; others can toggle via filter
        $eligibleOnly = $isFIN ? true : ($this->get('eligible_only', '0') === '1');
        
        // Get filter options - only show user's department if department-restricted
        if ($userDepartmentId) {
            $dept = $departmentModel->getById($userDepartmentId);
            $departments = $dept ? [$dept] : [];
        } else {
            $departments = $departmentModel->getAll();
        }
        $academicYears = $studentModel->getAcademicYears();
        
        // Get courses based on department
        $courses = [];
        if (!empty($departmentId)) {
            $courses = $courseModel->getCoursesWithDepartment(['department_id' => $departmentId]);
        }
        
        // Check if month is locked for this department
        $lockStatus = null;
        $isMonthLocked = false;
        if (!empty($departmentId) && !empty($month)) {
            $lockStatus = $lockModel->getLockStatus($departmentId, $month);
            $isMonthLocked = ($lockStatus && $lockStatus['status'] === 'locked');
        }
        
        // Get report data
        $reportData = [];
        $allDays = [];
        $summary = [
            'total_students' => 0,
            'total_present' => 0,
            'total_absent' => 0,
            'total_holidays' => 0,
            'total_working_days' => 0,
            'total_effective_working_days' => 0,
            'total_allowance' => 0,
            'above_90' => 0,
            'above_80' => 0,
            'below_80' => 0
        ];
        
        // FIN users can only view locked (HOD approved) months
        // Only get report data if not FIN or if month is locked (for FIN users)
        if (!empty($month) && (!$isFIN || $isMonthLocked)) {
            $filters = [
                'department_id' => $departmentId,
                'course_id' => $courseId,
                'academic_year' => $academicYear,
                'eligible_only' => $eligibleOnly
            ];
            
            $reportData = $attendanceModel->getAttendanceReport($month, $filters);
            
            // Get all days from first student (all students have same days)
            if (!empty($reportData)) {
                $allDays = $reportData[0]['all_days'] ?? [];
                $summary['total_working_days'] = count($allDays);
                $summary['total_students'] = count($reportData);
                
                // Get effective working days (same for all students - working days minus holidays)
                $firstStudent = $reportData[0];
                $summary['total_effective_working_days'] = $firstStudent['effective_working_days'] ?? 0;
                
                // Calculate summary statistics
                foreach ($reportData as $student) {
                    $summary['total_present'] += $student['present_days'];
                    $summary['total_absent'] += ($student['effective_working_days'] - $student['present_days']);
                    $summary['total_holidays'] += $student['holiday_days'];
                    $summary['total_allowance'] += $student['allowance'];
                    
                    if ($student['attendance_percentage'] >= 90) {
                        $summary['above_90']++;
                    } elseif ($student['attendance_percentage'] >= 80) {
                        $summary['above_80']++;
                    } else {
                        $summary['below_80']++;
                    }
                }
            }
        }
        
        $data = [
            'title' => 'Attendance Report',
            'page' => 'attendance-report',
            'departments' => $departments,
            'courses' => $courses,
            'academicYears' => $academicYears,
            'reportData' => $reportData,
            'allDays' => $allDays,
            'summary' => $summary,
            'selectedDepartment' => $departmentId,
            'selectedCourse' => $courseId,
            'selectedAcademicYear' => $academicYear,
            'selectedMonth' => $month,
            'eligibleOnly' => $eligibleOnly,
            'isHOD' => $isHOD,
            'isAdmin' => $isAdmin,
            'isFIN' => $isFIN,
            'isMonthLocked' => $isMonthLocked,
            'lockStatus' => $lockStatus,
            'error' => $_SESSION['error'] ?? null,
            'message' => $_SESSION['message'] ?? null
        ];
        
        unset($_SESSION['error'], $_SESSION['message']);
        return $this->view('attendance/report', $data);
    }
    
    /**
     * Lock attendance month for a department (HOD only)
     */
    public function lockMonth() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->json(['success' => false, 'message' => 'Unauthorized'], 401);
            return;
        }
        
        // Only HOD, IN1, IN2, IN3 can lock months
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        $isAdmin = $userModel->isAdmin($_SESSION['user_id']);
        $allowedRoles = ['HOD', 'IN1', 'IN2', 'IN3'];
        $hasAccess = in_array($userRole, $allowedRoles) || $isAdmin;
        
        if (!$hasAccess) {
            $this->json(['success' => false, 'message' => 'Access denied. Only HOD, IN1, IN2, and IN3 can lock months.'], 403);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Invalid request method'], 405);
            return;
        }
        
        $departmentId = $this->post('department_id');
        $month = $this->post('month');
        
        if (empty($departmentId) || empty($month)) {
            $this->json(['success' => false, 'message' => 'Department ID and Month are required'], 400);
            return;
        }
        
        // Verify HOD's department matches
        $hodDepartmentId = $this->getHODDepartment();
        if ($hodDepartmentId !== $departmentId) {
            $this->json(['success' => false, 'message' => 'Access denied. You can only lock your own department\'s attendance.'], 403);
            return;
        }
        
        // Get user name
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $user = $userModel->find($_SESSION['user_id']);
        $lockedByName = $user['user_name'] ?? null;
        
        $lockModel = $this->model('AttendanceMonthLockModel');
        
        try {
            $result = $lockModel->lockMonth($departmentId, $month, $_SESSION['user_id'], $lockedByName);
            
            if ($result) {
                require_once BASE_PATH . '/core/ActivityLogger.php';
                $activityLogger = new ActivityLogger();
                $activityLogger->log(
                    'attendance_month_locked',
                    "Locked attendance for {$departmentId} - {$month}",
                    'success',
                    $_SESSION['user_id'],
                    $lockedByName
                );
                
                $this->json([
                    'success' => true,
                    'message' => 'Month locked successfully. Attendance cannot be modified until unlocked by admin.'
                ]);
            } else {
                $this->json(['success' => false, 'message' => 'Month is already locked or failed to lock'], 400);
            }
        } catch (Exception $e) {
            error_log("Error locking attendance month: " . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Unlock attendance month for a department (Admin only)
     */
    public function unlockMonth() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->json(['success' => false, 'message' => 'Unauthorized'], 401);
            return;
        }
        
        // Only admin can unlock months
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        if (!$userModel->isAdmin($_SESSION['user_id'])) {
            $this->json(['success' => false, 'message' => 'Access denied. Only administrators can unlock months.'], 403);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Invalid request method'], 405);
            return;
        }
        
        $departmentId = $this->post('department_id');
        $month = $this->post('month');
        
        if (empty($departmentId) || empty($month)) {
            $this->json(['success' => false, 'message' => 'Department ID and Month are required'], 400);
            return;
        }
        
        $lockModel = $this->model('AttendanceMonthLockModel');
        
        try {
            $result = $lockModel->unlockMonth($departmentId, $month);
            
            if ($result) {
                require_once BASE_PATH . '/core/ActivityLogger.php';
                $activityLogger = new ActivityLogger();
                $user = $userModel->find($_SESSION['user_id']);
                $activityLogger->log(
                    'attendance_month_unlocked',
                    "Unlocked attendance for {$departmentId} - {$month}",
                    'success',
                    $_SESSION['user_id'],
                    $user['user_name'] ?? null
                );
                
                $this->json([
                    'success' => true,
                    'message' => 'Month unlocked successfully. Attendance can now be modified.'
                ]);
            } else {
                $this->json(['success' => false, 'message' => 'Failed to unlock month'], 400);
            }
        } catch (Exception $e) {
            error_log("Error unlocking attendance month: " . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Export attendance report to Excel
     */
    public function exportReport() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['error'] = 'Unauthorized. Please login to continue.';
            $this->redirect('login');
            return;
        }
        
        // Check if user has attendance report access
        if (!$this->checkAttendanceReportAccess()) {
            return;
        }
        
        $attendanceModel = $this->model('AttendanceModel');
        $lockModel = $this->model('AttendanceMonthLockModel');
        
        // Get user role to check if FIN
        $isFIN = false;
        if (isset($_SESSION['user_id'])) {
            require_once BASE_PATH . '/models/UserModel.php';
            $userModel = new UserModel();
            $userRole = $userModel->getUserRole($_SESSION['user_id']);
            $isFIN = ($userRole === 'FIN');
        }
        
        // Get filter parameters
        $departmentId = $this->get('department_id', '');
        $courseId = $this->get('course_id', '');
        $academicYear = $this->get('academic_year', '');
        $month = $this->get('month', date('Y-m'));
        
        if (empty($departmentId) || empty($month)) {
            $_SESSION['error'] = 'Department and Month are required for Excel export.';
            $this->redirect('attendance/report');
            return;
        }
        
        // Check if month is locked (HOD approved) - only allow download if month is locked
        // FIN users can ONLY download locked months. Other users with attendance report access
        // can also download locked months.
        $lockStatus = $lockModel->getLockStatus($departmentId, $month);
        $isMonthLocked = ($lockStatus && $lockStatus['status'] === 'locked');
        
        if (!$isMonthLocked) {
            if ($isFIN) {
                $_SESSION['error'] = 'Access Restricted: Attendance reports can only be viewed after HOD approval and monthly lock. Please wait until the month is locked.';
            } else {
                $_SESSION['error'] = 'Access denied. Excel download is only available for locked (HOD approved) months.';
            }
            $this->redirect('attendance/report?' . http_build_query([
                'department_id' => $departmentId,
                'course_id' => $courseId,
                'academic_year' => $academicYear,
                'month' => $month
            ]));
            return;
        }
        
        $filters = [
            'department_id' => $departmentId,
            'course_id' => $courseId,
            'academic_year' => $academicYear
        ];
        // FIN users export only eligible (allowance-eligible) students
        if ($isFIN) {
            $filters['eligible_only'] = true;
        }
        
        // Get report data (same method as report view)
        $reportData = $attendanceModel->getAttendanceReport($month, $filters);
        
        // Get all days from first student (all students have same days)
        $allDays = [];
        $summary = [
            'total_present' => 0,
            'total_effective_working_days' => 0,
            'total_allowance' => 0
        ];
        
        if (!empty($reportData)) {
            $allDays = $reportData[0]['all_days'] ?? [];
            // Get effective working days (same for all students)
            $firstStudent = $reportData[0];
            $summary['total_effective_working_days'] = $firstStudent['effective_working_days'] ?? 0;
            
            // Calculate summary statistics
            foreach ($reportData as $student) {
                $summary['total_present'] += $student['present_days'];
                $summary['total_allowance'] += $student['allowance'];
            }
        }
        
        // Set headers for Excel download
        $filename = 'attendance_report_' . $month . '_' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Add BOM for UTF-8 Excel compatibility
        echo "\xEF\xBB\xBF";
        
        // Open output stream
        $output = fopen('php://output', 'w');
        
        // Build header row - Fixed columns + Day columns + Summary columns
        $headers = [
            'Student ID',
            'Full Name',
            'NIC',
            'Bank Name',
            'Account No',
            'Branch'
        ];
        
        // Add day columns
        foreach ($allDays as $day) {
            $headers[] = $day['day']; // Just the day number (e.g., "01", "02")
        }
        
        // Add summary columns
        $headers[] = 'Total Days'; // Total Days (effective working days)
        $headers[] = 'P'; // Present Days
        $headers[] = '%'; // Attendance Percentage
        $headers[] = 'Allowance'; // Allowance
        
        // Write header row
        fputcsv($output, $headers);
        
        // Add data rows
        foreach ($reportData as $student) {
            $row = [
                $student['student_id'],
                $student['student_fullname'],
                $student['student_nic'],
                $student['bank_name'] ?? '-',
                $student['bank_account_no'] ?? '-',
                $student['bank_branch'] ?? '-'
            ];
            
            // Add day-by-day attendance status
            foreach ($allDays as $day) {
                $status = $student['day_by_day'][$day['date']] ?? '';
                $statusValue = '';
                
                if ($status == 'P') {
                    $statusValue = '1'; // Present = 1
                } elseif ($status == 'A') {
                    $statusValue = '0'; // Absent = 0
                } elseif ($status == 'H') {
                    $statusValue = '-1'; // Holiday = -1 (as per requirement)
                } else {
                    $statusValue = ''; // Not marked = empty
                }
                
                $row[] = $statusValue;
            }
            
            // Add summary columns
            $row[] = $student['effective_working_days']; // Total Days
            $row[] = $student['present_days']; // Present Days
            $row[] = number_format($student['attendance_percentage'], 1) . '%'; // Percentage
            $row[] = number_format($student['allowance'], 0); // Allowance
            
            fputcsv($output, $row);
        }
        
        // Add totals row
        if (!empty($reportData)) {
            $totalRow = [];
            // "Total:" in first column, empty for rest of student info columns
            $totalRow[] = 'Total:';
            for ($i = 1; $i < 6; $i++) {
                $totalRow[] = '';
            }
            // Empty cells for day columns
            foreach ($allDays as $day) {
                $totalRow[] = '';
            }
            // Total Days, Total Present, empty for percentage, Total Allowance
            $totalRow[] = $summary['total_effective_working_days'];
            $totalRow[] = $summary['total_present'];
            $totalRow[] = ''; // Empty for percentage
            $totalRow[] = number_format($summary['total_allowance'], 0);
            
            fputcsv($output, $totalRow);
        }
        
        fclose($output);
        exit();
    }
    
    /**
     * Form to download blank weekday month sheet (Excel, 4 slots per day).
     */
    public function monthSheet() {
        if (!$this->checkMonthAttendanceSheetAccess()) {
            return;
        }
        
        $departmentModel = $this->model('DepartmentModel');
        $courseModel = $this->model('CourseModel');
        $studentModel = $this->model('StudentModel');
        
        // HOD / IN1–IN3: own department only; ADM / admin: all departments
        $userDepartmentId = $this->getUserDepartment();
        if ($this->isDepartmentRestricted()) {
            if ($userDepartmentId === null || $userDepartmentId === '') {
                $_SESSION['error'] = 'Your account has no department assigned. Please contact the administrator.';
                $this->redirect('dashboard');
                return;
            }
            $departmentId = trim((string) $userDepartmentId);
        } else {
            $departmentId = $this->get('department_id', '');
        }
        $courseId = $this->get('course_id', '');
        $academicYear = $this->get('academic_year', '');
        $month = $this->get('month', date('Y-m'));
        $group = $this->get('group', '');
        
        if ($userDepartmentId) {
            $dept = $departmentModel->getById($userDepartmentId);
            $departments = $dept ? [$dept] : [];
        } else {
            $departments = $departmentModel->getAll();
        }
        $academicYears = $studentModel->getAcademicYears();
        
        $courses = [];
        if (!empty($departmentId)) {
            $courses = $courseModel->getCoursesWithDepartment(['department_id' => $departmentId]);
        }
        
        $groups = [];
        if (!empty($courseId) && !empty($academicYear)) {
            $groupModel = $this->model('GroupModel');
            $groups = $groupModel->getGroupsByCourseAndYear($courseId, $academicYear);
        }
        
        $data = [
            'title' => 'Month attendance register (Excel)',
            'page' => 'attendance-month-sheet',
            'departments' => $departments,
            'courses' => $courses,
            'academicYears' => $academicYears,
            'groups' => $groups,
            'selectedDepartment' => $departmentId,
            'selectedCourse' => $courseId,
            'selectedAcademicYear' => $academicYear,
            'selectedMonth' => $month,
            'selectedGroup' => $group,
            'error' => $_SESSION['error'] ?? null,
            'message' => $_SESSION['message'] ?? null,
            'lockDepartmentSelection' => $this->isDepartmentRestricted(),
            'forcedDepartmentId' => $this->isDepartmentRestricted() ? (string) $userDepartmentId : '',
        ];
        
        unset($_SESSION['error'], $_SESSION['message']);
        return $this->view('attendance/month_sheet', $data);
    }
    
    /**
     * Excel (.xlsx): register with #, Reg. No., Name, NIC and one column per weekday
     * (Saturdays, Sundays, and Sri Lanka public holidays excluded). Native OOXML, PHP 7.4–safe.
     */
    public function exportMonthSheet() {
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['error'] = 'Unauthorized. Please login to continue.';
            $this->redirect('login');
            return;
        }
        if (!$this->checkMonthAttendanceSheetAccess()) {
            return;
        }
        
        $userDepartmentId = $this->getUserDepartment();
        if ($this->isDepartmentRestricted()) {
            if ($userDepartmentId === null || $userDepartmentId === '') {
                $_SESSION['error'] = 'Your account has no department assigned. Please contact the administrator.';
                $this->redirect('dashboard');
                return;
            }
            $departmentId = trim((string) $userDepartmentId);
        } else {
            $departmentId = trim((string) $this->get('department_id', ''));
        }
        $courseId = trim((string) $this->get('course_id', ''));
        $academicYear = trim((string) $this->get('academic_year', ''));
        $month = trim((string) $this->get('month', date('Y-m')));
        $group = trim((string) $this->get('group', ''));
        
        if ($departmentId === '' || $courseId === '' || $academicYear === '' || $month === '') {
            $_SESSION['error'] = 'Department, Course, Academic Year, and Month are required.';
            $this->redirect('attendance/month-sheet?' . http_build_query([
                'department_id' => $departmentId,
                'course_id' => $courseId,
                'academic_year' => $academicYear,
                'month' => $month,
                'group' => $group,
            ]));
            return;
        }
        
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $_SESSION['error'] = 'Invalid month format.';
            $this->redirect('attendance/month-sheet');
            return;
        }
        
        $attendanceModel = $this->model('AttendanceModel');
        $filters = [
            'department_id' => $departmentId,
            'course_id' => $courseId,
            'academic_year' => $academicYear,
            'all_student_statuses' => true,
        ];
        if ($group !== '') {
            $filters['group_id'] = $group;
        }
        
        $students = $attendanceModel->getStudentsForAttendance($filters);
        if (empty($students)) {
            $_SESSION['error'] = 'No students found for the selected filters.';
            $this->redirect('attendance/month-sheet?' . http_build_query([
                'department_id' => $departmentId,
                'course_id' => $courseId,
                'academic_year' => $academicYear,
                'month' => $month,
                'group' => $group,
            ]));
            return;
        }
        
        require_once BASE_PATH . '/helpers/SriLankaPublicHolidays.php';
        $workingDays = SriLankaPublicHolidays::weekdayTeachingDaysInMonth($month);
        if (empty($workingDays)) {
            $_SESSION['error'] = 'No teaching weekdays in the selected month (all weekdays may be public holidays), or invalid month.';
            $this->redirect('attendance/month-sheet?' . http_build_query([
                'department_id' => $departmentId,
                'course_id' => $courseId,
                'academic_year' => $academicYear,
                'month' => $month,
                'group' => $group,
            ]));
            return;
        }
        
        require_once BASE_PATH . '/helpers/AttendanceRegisterExportXlsx.php';
        try {
            AttendanceRegisterExportXlsx::stream($students, $workingDays, $month, $courseId, $group);
        } catch (Throwable $e) {
            error_log('exportMonthSheet native xlsx: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            $_SESSION['error'] = 'Could not build the Excel file. Check the server error log for exportMonthSheet. '
                . 'Ensure helpers/StoredZipWriter.php is deployed; for PhpSpreadsheet mode also run composer install and enable the PHP zip extension.';
            $this->redirect('attendance/month-sheet?' . http_build_query([
                'department_id' => $departmentId,
                'course_id' => $courseId,
                'academic_year' => $academicYear,
                'month' => $month,
                'group' => $group,
            ]));
            return;
        }
        exit();
    }
    
    /**
     * Generate calendar days for a month (excluding weekends)
     */
    private function generateCalendarDays($month) {
        $days = [];
        $startDate = new DateTime($month . '-01');
        $endDate = new DateTime($startDate->format('Y-m-t'));
        
        $currentDate = clone $startDate;
        
        while ($currentDate <= $endDate) {
            $dayOfWeek = (int)$currentDate->format('w'); // 0 = Sunday, 6 = Saturday
            
            // Skip weekends (Saturday = 6, Sunday = 0)
            if ($dayOfWeek != 0 && $dayOfWeek != 6) {
                $days[] = [
                    'date' => $currentDate->format('Y-m-d'),
                    'day' => $currentDate->format('d'),
                    'day_name' => $currentDate->format('D'),
                    'day_of_week' => $dayOfWeek
                ];
            }
            
            $currentDate->modify('+1 day');
        }
        
        return $days;
    }
    
    /**
     * Sync staff attendance from Hikvision device
     */
    public function syncHikvision() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->json(['success' => false, 'error' => 'Unauthorized'], 401);
        }
        
        // Restrict SAO users
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        if ($userModel->isSAO($_SESSION['user_id'])) {
            $this->json(['success' => false, 'error' => 'Access denied. This section is not available for your role.'], 403);
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'error' => 'Invalid request method'], 405);
        }
        
        try {
            // Get configuration
            $configFile = BASE_PATH . '/config/hikvision.php';
            if (!file_exists($configFile)) {
                // Create default config
                $defaultConfig = [
                    'host' => '192.168.1.64',
                    'port' => 80,
                    'username' => 'admin',
                    'password' => 'admin12345',
                    'timeout' => 10
                ];
                file_put_contents($configFile, "<?php\nreturn " . var_export($defaultConfig, true) . ";\n");
            }
            
            $hikvisionConfig = require $configFile;
            
            // Initialize Hikvision integration
            require_once BASE_PATH . '/core/HikvisionIntegration.php';
            $hikvision = new HikvisionIntegration($hikvisionConfig);
            
            // Get date range from POST or use today
            $startDate = $this->post('start_date', date('Y-m-d'));
            $endDate = $this->post('end_date', date('Y-m-d'));
            
            // Format dates for Hikvision API
            $startTime = $startDate . 'T00:00:00';
            $endTime = $endDate . 'T23:59:59';
            
            // Get attendance records from Hikvision
            $records = $hikvision->getAttendanceRecords($startTime, $endTime);
            
            if (empty($records)) {
                $this->json([
                    'success' => true,
                    'message' => 'No attendance records found for the specified date range',
                    'records' => 0
                ]);
            }
            
            // Get staff mapping (employee_no to staff_id)
            // You may need to create a mapping table or use staff_id as employee_no
            $staffModel = $this->model('StaffModel');
            $allStaff = $staffModel->all();
            $staffMapping = [];
            foreach ($allStaff as $staff) {
                // Map by staff_id (assuming staff_id matches employee_no)
                $staffMapping[$staff['staff_id']] = $staff['staff_id'];
                // Also map by NIC if available
                if (!empty($staff['staff_nic'])) {
                    $staffMapping[$staff['staff_nic']] = $staff['staff_id'];
                }
            }
            
            // Sync to database
            $staffAttendanceModel = $this->model('StaffAttendanceModel');
            
            // Create table if not exists
            $staffAttendanceModel->createTableIfNotExists();
            
            // Sync records
            $result = $staffAttendanceModel->syncFromHikvision($records, $staffMapping);
            
            $this->json([
                'success' => true,
                'message' => "Synced {$result['success']} records successfully" . 
                            ($result['errors'] > 0 ? ". {$result['errors']} errors occurred." : ''),
                'stats' => $result
            ]);
            
        } catch (Exception $e) {
            error_log('Hikvision sync error: ' . $e->getMessage());
            $this->json([
                'success' => false,
                'error' => 'Sync failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get attendance report from Hikvision device
     */
    public function getHikvisionReport() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->json(['success' => false, 'error' => 'Unauthorized'], 401);
        }
        
        // Restrict SAO users
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        if ($userModel->isSAO($_SESSION['user_id'])) {
            $this->json(['success' => false, 'error' => 'Access denied. This section is not available for your role.'], 403);
        }
        
        try {
            // Get configuration
            $configFile = BASE_PATH . '/config/hikvision.php';
            if (!file_exists($configFile)) {
                $this->json([
                    'success' => false,
                    'error' => 'Hikvision configuration file not found. Please configure the device first.'
                ], 500);
            }
            
            $hikvisionConfig = require $configFile;
            
            // Initialize Hikvision integration
            require_once BASE_PATH . '/core/HikvisionIntegration.php';
            $hikvision = new HikvisionIntegration($hikvisionConfig);
            
            // Get date range from POST/GET or use default (last 30 days)
            $startDate = $this->post('start_date', $this->get('start_date', date('Y-m-d', strtotime('-30 days'))));
            $endDate = $this->post('end_date', $this->get('end_date', date('Y-m-d')));
            $employeeId = $this->post('employee_id', $this->get('employee_id', null));
            
            // Get attendance report
            $records = $hikvision->getAttendanceReport($startDate, $endDate, $employeeId);
            
            $response = [
                'success' => true,
                'records' => $records,
                'count' => count($records),
                'date_range' => [
                    'start' => $startDate,
                    'end' => $endDate
                ]
            ];
            
            // Add debug info if no records found
            if (empty($records)) {
                $debugInfo = $hikvision->getLastDebugInfo();
                $response['debug_info'] = $debugInfo;
                $response['message'] = 'No attendance records found. Please check: 1) Error logs for API responses, 2) Date range matches device data, 3) Device connection status.';
            }
            
            $this->json($response);
            
        } catch (Exception $e) {
            error_log('Hikvision report error: ' . $e->getMessage());
            $this->json([
                'success' => false,
                'error' => 'Failed to get attendance report: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Test Hikvision connection
     */
    public function testHikvision() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->json(['success' => false, 'error' => 'Unauthorized'], 401);
        }
        
        // Restrict SAO users
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        if ($userModel->isSAO($_SESSION['user_id'])) {
            $this->json(['success' => false, 'error' => 'Access denied. This section is not available for your role.'], 403);
        }
        
        try {
            // Get configuration
            $configFile = BASE_PATH . '/config/hikvision.php';
            if (!file_exists($configFile)) {
                $this->json([
                    'success' => false,
                    'error' => 'Hikvision configuration file not found. Please configure the device first.'
                ], 500);
            }
            
            $hikvisionConfig = require $configFile;
            
            // Initialize Hikvision integration
            require_once BASE_PATH . '/core/HikvisionIntegration.php';
            $hikvision = new HikvisionIntegration($hikvisionConfig);
            
            // Test connection
            $result = $hikvision->testConnection();
            
            $this->json($result);
            
        } catch (Exception $e) {
            error_log('Hikvision test error: ' . $e->getMessage());
            $this->json([
                'success' => false,
                'error' => 'Connection test failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Staff attendance view - Load data from machine
     */
    public function staffAttendance() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Restrict SAO users
        if (!$this->checkNotSAO()) {
            return;
        }
        
        $configFile = BASE_PATH . '/config/hikvision.php';
        if (!file_exists($configFile)) {
            $_SESSION['error'] = 'Hikvision configuration not found.';
            $this->redirect('dashboard');
            return;
        }
        
        $hikvisionConfig = require $configFile;
        require_once BASE_PATH . '/core/HikvisionIntegration.php';
        $hikvision = new HikvisionIntegration($hikvisionConfig);
        
        // Get date filter
        $date = $this->get('date', date('Y-m-d'));
        
        // Validate date - ensure it's not in the future
        $today = date('Y-m-d');
        if ($date > $today) {
            $date = $today;
            $_SESSION['error'] = 'Date cannot be in the future. Showing today\'s records.';
        }
        
        // Get device information and users
        $deviceInfo = null;
        $deviceStatus = 'disconnected';
        $deviceUsers = [];
        $machineAttendanceRecords = [];
        
        try {
            $connectionResult = $hikvision->testConnection();
            if ($connectionResult['success']) {
                $deviceStatus = 'connected';
                $deviceInfo = $connectionResult['device_info'] ?? $connectionResult['raw_response'] ?? null;
                
                // Get users from device
                try {
                    $deviceUsers = $hikvision->getUsers();
                } catch (Exception $e) {
                    error_log('Error getting device users: ' . $e->getMessage());
                    $deviceUsers = [];
                }
                
                // Get attendance records from machine for the selected date
                if (!empty($deviceUsers)) {
                    try {
                        $machineAttendanceRecords = $hikvision->getAttendanceReport($date, $date, null);
                    } catch (Exception $e) {
                        error_log('Error getting machine attendance: ' . $e->getMessage());
                        $machineAttendanceRecords = [];
                    }
                }
            }
        } catch (Exception $e) {
            error_log('Error connecting to device: ' . $e->getMessage());
        }
        
        // Map attendance records by employee ID
        $attendanceByEmployee = [];
        foreach ($machineAttendanceRecords as $record) {
            $empId = $record['employee_id'] ?? $record['employee_no'] ?? '';
            if (!empty($empId)) {
                if (!isset($attendanceByEmployee[$empId])) {
                    $attendanceByEmployee[$empId] = [];
                }
                $attendanceByEmployee[$empId][] = $record;
            }
        }
        
        // Combine device users with their attendance records
        $usersWithAttendance = [];
        foreach ($deviceUsers as $user) {
            $empId = $user['employee_no'] ?? '';
            $userRecords = $attendanceByEmployee[$empId] ?? [];
            
            // Get check-in and check-out times
            $checkInTime = null;
            $checkOutTime = null;
            $recordDate = null;
            $recordType = null;
            
            if (!empty($userRecords)) {
                // Sort by time to get first check-in and last check-out
                usort($userRecords, function($a, $b) {
                    $timeA = $a['time'] ?? '';
                    $timeB = $b['time'] ?? '';
                    return strcmp($timeA, $timeB);
                });
                
                foreach ($userRecords as $rec) {
                    $type = $rec['type'] ?? '';
                    $recTime = $rec['time'] ?? '';
                    $recDate = $rec['date'] ?? '';
                    
                    if (empty($recordDate)) {
                        $recordDate = $recDate;
                    }
                    
                    if (($type == '1' || $type == 1 || stripos($type, 'check-in') !== false || stripos($type, 'in') !== false) && empty($checkInTime)) {
                        $checkInTime = $recTime;
                        if (preg_match('/(\d{2}:\d{2}:\d{2})/', $checkInTime, $matches)) {
                            $checkInTime = $matches[1];
                        }
                    } elseif (($type == '2' || $type == 2 || stripos($type, 'check-out') !== false || stripos($type, 'out') !== false)) {
                        $checkOutTime = $recTime;
                        if (preg_match('/(\d{2}:\d{2}:\d{2})/', $checkOutTime, $matches)) {
                            $checkOutTime = $matches[1];
                        }
                    }
                }
            }
            
            $usersWithAttendance[] = [
                'employee_no' => $empId,
                'name' => $user['name'] ?? 'N/A',
                'card_no' => $user['card_no'] ?? '',
                'user_type' => $user['user_type'] ?? 'normal',
                'valid' => $user['valid'] ?? true,
                'check_in_time' => $checkInTime,
                'check_out_time' => $checkOutTime,
                'has_attendance' => !empty($userRecords),
                'attendance_records' => $userRecords
            ];
        }
        
        $data = [
            'title' => 'Staff Attendance',
            'page' => 'staff-attendance',
            'selectedDate' => $date,
            'deviceInfo' => $deviceInfo,
            'deviceStatus' => $deviceStatus,
            'hikvisionConfig' => $hikvisionConfig,
            'deviceUsers' => $deviceUsers,
            'usersWithAttendance' => $usersWithAttendance,
            'message' => $_SESSION['message'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ];
        
        unset($_SESSION['message'], $_SESSION['error']);
        return $this->view('attendance/staff', $data);
    }

    /**
     * Staff device attendance dashboard (Hikvision ISAPI → staff_attendance table) — same logic as staff_attendance/dashboard.php, main app layout.
     */
    public function staffDeviceDashboard() {
        if (!$this->checkStaffDeviceDashboardMonthAccess()) {
            return;
        }

        require_once BASE_PATH . '/staff_attendance/includes/dashboard_data.php';
        require_once BASE_PATH . '/models/UserModel.php';
        $state = staff_attendance_load_dashboard_state();
        $state['title'] = 'Staff attendance (device)';
        $state['page'] = 'staff-attendance-device';
        $state['dash_form_action'] = rtrim(APP_URL, '/') . '/attendance/staff-device';
        $state['urls'] = staff_attendance_embed_nav_urls();
        $state['embed_main_layout'] = true;
        $userModel = new UserModel();
        $state['staffDeviceNavLimited'] = !$userModel->isAdminOrADM((int) $_SESSION['user_id']);

        return $this->view('attendance/staff_device_dashboard', $state);
    }

    /**
     * Full staff device module (all punches, daily, sync): Admin / ADM only.
     */
    private function checkStaffDeviceAccess(): bool {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return false;
        }
        if (!$this->checkNotSAO()) {
            return false;
        }
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        if (!$userModel->isAdminOrADM($_SESSION['user_id'])) {
            $_SESSION['error'] = 'Access denied. This staff device section is only available to Administrators (ADM).';
            $this->redirect('dashboard');
            return false;
        }
        return true;
    }

    /**
     * Dashboard + month report: Admin/ADM, or DIR, REG, FIN, ACC, HOD.
     */
    private function checkStaffDeviceDashboardMonthAccess(): bool {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return false;
        }
        if (!$this->checkNotSAO()) {
            return false;
        }
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        if ($userModel->canViewStaffDeviceDashboardMonth($_SESSION['user_id'])) {
            return true;
        }
        $_SESSION['error'] = 'Access denied. Staff device attendance is available to DIR, REG, FIN, ACC, HOD, ADM, or Administrators.';
        $this->redirect('dashboard');
        return false;
    }

    /**
     * @return array{device: string, list: string, daily: string, month: string, sync: string}|null
     */
    private function staffDeviceNavUrls(): array {
        require_once BASE_PATH . '/staff_attendance/includes/dashboard_data.php';
        return staff_attendance_embed_nav_urls();
    }

    private function staffDeviceAccessOk(): bool {
        return $this->checkStaffDeviceAccess();
    }

    /**
     * Raw punch list (device table) — main app layout + staff device side nav.
     */
    public function staffDeviceList() {
        if (!$this->staffDeviceAccessOk()) {
            return;
        }
        require_once BASE_PATH . '/staff_attendance/config.php';

        $urls = $this->staffDeviceNavUrls();
        $listBase = $urls['list'];

        $perPage = isset($_GET['per_page']) ? max(5, min(100, (int) $_GET['per_page'])) : (defined('ATT_PAGE_SIZE') ? ATT_PAGE_SIZE : 25);
        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $offset = ($page - 1) * $perPage;

        $q = trim((string) ($_GET['q'] ?? ''));
        $employeeNo = trim((string) ($_GET['employee_no'] ?? ''));
        $staffName = trim((string) ($_GET['staff_name'] ?? ''));
        $department = trim((string) ($_GET['department'] ?? ''));
        $startDate = trim((string) ($_GET['start_date'] ?? ''));
        $endDate = trim((string) ($_GET['end_date'] ?? ''));

        $where = [];
        $params = [];
        $types = '';

        if ($q !== '') {
            $where[] = '(employee_no LIKE ? OR staff_name LIKE ?)';
            $like = '%' . $q . '%';
            $params[] = $like;
            $params[] = $like;
            $types .= 'ss';
        }
        if ($employeeNo !== '') {
            $where[] = 'employee_no LIKE ?';
            $params[] = '%' . $employeeNo . '%';
            $types .= 's';
        }
        if ($staffName !== '') {
            $where[] = 'staff_name LIKE ?';
            $params[] = '%' . $staffName . '%';
            $types .= 's';
        }
        if ($department !== '') {
            $where[] = 'department LIKE ?';
            $params[] = '%' . $department . '%';
            $types .= 's';
        }
        if ($startDate !== '' && $endDate !== '') {
            $where[] = 'DATE(attendance_time) BETWEEN ? AND ?';
            $params[] = $startDate;
            $params[] = $endDate;
            $types .= 'ss';
        } elseif ($startDate !== '') {
            $where[] = 'DATE(attendance_time) >= ?';
            $params[] = $startDate;
            $types .= 's';
        } elseif ($endDate !== '') {
            $where[] = 'DATE(attendance_time) <= ?';
            $params[] = $endDate;
            $types .= 's';
        }

        $sqlWhere = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $db = attendance_db();

        $countSql = "SELECT COUNT(*) AS c FROM staff_attendance $sqlWhere";
        $countStmt = $db->prepare($countSql);
        if ($types !== '') {
            $countStmt->bind_param($types, ...$params);
        }
        $countStmt->execute();
        $totalRows = (int) ($countStmt->get_result()->fetch_assoc()['c'] ?? 0);
        $countStmt->close();

        $totalPages = max(1, (int) ceil($totalRows / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
            $offset = ($page - 1) * $perPage;
        }

        $listSql = "SELECT attendance_id, employee_no, staff_name, department, attendance_time, device_ip, event_type, created_at
            FROM staff_attendance $sqlWhere
            ORDER BY attendance_time DESC
            LIMIT ? OFFSET ?";
        $listStmt = $db->prepare($listSql);
        $typesList = $types . 'ii';
        $paramsList = array_merge($params, [$perPage, $offset]);
        if ($typesList !== 'ii') {
            $listStmt->bind_param($typesList, ...$paramsList);
        } else {
            $listStmt->bind_param('ii', $perPage, $offset);
        }
        $listStmt->execute();
        $rows = $listStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $listStmt->close();

        $qs = $_GET;
        unset($qs['page']);
        $baseQuery = http_build_query($qs);

        return $this->view('attendance/staff_device_list', [
            'title' => 'Staff device — All punches',
            'page' => 'staff-attendance-device-list',
            'staffDeviceSection' => 'list',
            'urls' => $urls,
            'listBase' => $listBase,
            'baseQuery' => $baseQuery,
            'rows' => $rows,
            'totalRows' => $totalRows,
            'totalPages' => $totalPages,
            'pageNum' => $page,
            'perPage' => $perPage,
            'q' => $q,
            'employeeNo' => $employeeNo,
            'staffName' => $staffName,
            'department' => $department,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    /**
     * Daily check-in / check-out summary — main app layout.
     */
    public function staffDeviceDaily() {
        if (!$this->staffDeviceAccessOk()) {
            return;
        }
        require_once BASE_PATH . '/staff_attendance/config.php';

        $urls = $this->staffDeviceNavUrls();
        $tz = new DateTimeZone(STAFF_TIMEZONE);
        $todayYmd = (new DateTimeImmutable('now', $tz))->format('Y-m-d');

        $reportDate = trim((string) ($_GET['report_date'] ?? ''));
        if ($reportDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $reportDate)) {
            $reportDate = $todayYmd;
        }

        $nameOk = 'staff_name IS NOT NULL AND TRIM(staff_name) <> \'\'';
        $db = attendance_db();
        $sql = "SELECT employee_no, MAX(staff_name) AS staff_name,
                MIN(attendance_time) AS check_in,
                MAX(attendance_time) AS check_out
                FROM staff_attendance
                WHERE DATE(attendance_time) = ? AND $nameOk
                GROUP BY employee_no
                ORDER BY employee_no";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('s', $reportDate);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $this->view('attendance/staff_device_daily', [
            'title' => 'Staff device — Daily report',
            'page' => 'staff-attendance-device-daily',
            'staffDeviceSection' => 'daily',
            'urls' => $urls,
            'reportDate' => $reportDate,
            'todayYmd' => $todayYmd,
            'rows' => $rows,
        ]);
    }

    /**
     * Monthly summary (days present per employee) — main app layout.
     */
    public function staffDeviceMonth() {
        if (!$this->checkStaffDeviceDashboardMonthAccess()) {
            return;
        }
        require_once BASE_PATH . '/staff_attendance/config.php';
        require_once BASE_PATH . '/models/UserModel.php';

        $urls = $this->staffDeviceNavUrls();
        $userModel = new UserModel();
        $staffDeviceNavLimited = !$userModel->isAdminOrADM((int) $_SESSION['user_id']);
        $tz = new DateTimeZone(STAFF_TIMEZONE);
        $defaultMonth = (new DateTimeImmutable('now', $tz))->format('Y-m');

        $reportMonth = trim((string) ($_GET['report_month'] ?? ''));
        if ($reportMonth === '' || !preg_match('/^\d{4}-\d{2}$/', $reportMonth)) {
            $reportMonth = $defaultMonth;
        }

        $employeeNo = trim((string) ($_GET['employee_no'] ?? ''));

        require_once BASE_PATH . '/staff_attendance/includes/month_report_data.php';
        $state = staff_attendance_month_report_fetch($reportMonth, $employeeNo);
        $stateAll = staff_attendance_month_report_fetch($reportMonth, '');
        $groupedSorted = staff_attendance_month_report_sort_grouped($state['grouped']);
        $pdfSectionsCurrent = staff_attendance_month_report_sections_from_grouped($state['grouped']);
        $pdfSectionsAll = staff_attendance_month_report_sections_from_grouped($stateAll['grouped']);
        $headerMeta = staff_attendance_month_report_header_meta($reportMonth, $employeeNo, $state['employees']);
        $monthDisplay = $headerMeta['monthDisplay'];
        $pdfPayloadCurrent = json_encode(
            [
                'reportMonth' => $reportMonth,
                'monthDisplay' => $monthDisplay,
                'sections' => $pdfSectionsCurrent,
            ],
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE
        );
        $pdfPayloadAll = json_encode(
            [
                'reportMonth' => $reportMonth,
                'monthDisplay' => $monthDisplay,
                'sections' => $pdfSectionsAll,
            ],
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE
        );

        return $this->view('attendance/staff_device_month', [
            'title' => 'Staff Attendance Summary',
            'page' => 'staff-attendance-device-month',
            'staffDeviceSection' => 'month',
            'staffDeviceNavLimited' => $staffDeviceNavLimited,
            'urls' => $urls,
            'reportMonth' => $reportMonth,
            'employeeNo' => $employeeNo,
            'employees' => $state['employees'],
            'grouped' => $state['grouped'],
            'groupedSorted' => $groupedSorted,
            'dbError' => $state['dbError'],
            'monthDisplay' => $monthDisplay,
            'employeeFilterLabel' => $headerMeta['employeeFilterLabel'],
            'pdfPayloadCurrent' => $pdfPayloadCurrent,
            'pdfPayloadAll' => $pdfPayloadAll,
            'hasRowsAll' => $stateAll['grouped'] !== [],
        ]);
    }

    /**
     * Manual Hikvision sync — main app layout (POST redirects back here).
     */
    public function staffDeviceSync() {
        if (!$this->staffDeviceAccessOk()) {
            return;
        }
        require_once BASE_PATH . '/staff_attendance/config.php';
        require_once BASE_PATH . '/staff_attendance/includes/hikvision_sync_lib.php';

        $urls = $this->staffDeviceNavUrls();

        $tz = new DateTimeZone(STAFF_TIMEZONE);
        $now = new DateTimeImmutable('now', $tz);
        $defaultEnd = $now->setTime(23, 59, 59);
        $syncIv = defined('STAFF_ATTENDANCE_SYNC_DEFAULT_INTERVAL') ? (string) STAFF_ATTENDANCE_SYNC_DEFAULT_INTERVAL : 'P6D';
        try {
            $defaultStart = $defaultEnd->sub(new DateInterval($syncIv))->setTime(0, 0, 0);
        } catch (Exception $e) {
            $defaultStart = $defaultEnd->sub(new DateInterval('P6D'))->setTime(0, 0, 0);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $startRaw = trim((string) ($_POST['sync_start'] ?? ''));
            $endRaw = trim((string) ($_POST['sync_end'] ?? ''));

            if ($startRaw === '') {
                $startRaw = $defaultStart->format('Y-m-d H:i:s');
            }
            if ($endRaw === '') {
                $endRaw = $defaultEnd->format('Y-m-d H:i:s');
            }

            $startDt = DateTime::createFromFormat('Y-m-d H:i:s', $startRaw);
            if ($startDt === false) {
                $startDt = new DateTime($startRaw);
            }
            $endDt = DateTime::createFromFormat('Y-m-d H:i:s', $endRaw);
            if ($endDt === false) {
                $endDt = new DateTime($endRaw);
            }

            $result = attendance_run_hikvision_sync($startDt, $endDt);
            $_SESSION['staff_attendance_sync_result'] = $result;

            if ($result['ok']) {
                $tr = (int) ($result['total_received'] ?? 0);
                $ins = (int) ($result['inserted'] ?? 0);
                $_SESSION['flash_success'] = sprintf(
                    'Sync finished. Total received: %d — Total inserted: %d (duplicates skipped: %d, invalid: %d, not in staff list: %d).',
                    $tr,
                    $ins,
                    (int) ($result['skipped_dup'] ?? 0),
                    (int) ($result['skipped_bad'] ?? 0),
                    (int) ($result['skipped_not_in_directory'] ?? 0)
                );
            } else {
                $_SESSION['flash_error'] = $result['error'] ?? 'Sync failed.';
            }

            $this->redirect('attendance/staff-device/sync');
        }

        $reachTest = null;
        if (isset($_GET['test']) && (string) $_GET['test'] === '1') {
            $reachTest = attendance_hikvision_test_reachability();
        }

        $showResult = null;
        if (!empty($_SESSION['staff_attendance_sync_result'])) {
            $showResult = $_SESSION['staff_attendance_sync_result'];
            unset($_SESSION['staff_attendance_sync_result']);
        }

        $defaultStartStr = $defaultStart->format('Y-m-d H:i:s');
        $defaultEndStr = $defaultEnd->format('Y-m-d H:i:s');

        return $this->view('attendance/staff_device_sync', [
            'title' => 'Staff device — Hikvision sync',
            'page' => 'staff-attendance-device-sync',
            'staffDeviceSection' => 'sync',
            'urls' => $urls,
            'defaultStartStr' => $defaultStartStr,
            'defaultEndStr' => $defaultEndStr,
            'reachTest' => $reachTest,
            'showResult' => $showResult,
        ]);
    }
    
    /**
     * View machine attendance records directly from device
     */
    public function machineAttendance() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Restrict SAO users
        if (!$this->checkNotSAO()) {
            return;
        }
        
        $configFile = BASE_PATH . '/config/hikvision.php';
        if (!file_exists($configFile)) {
            $_SESSION['error'] = 'Hikvision configuration not found.';
            $this->redirect('attendance/staff');
            return;
        }
        
        $hikvisionConfig = require $configFile;
        require_once BASE_PATH . '/core/HikvisionIntegration.php';
        $hikvision = new HikvisionIntegration($hikvisionConfig);
        
        // Get date range from GET parameters
        $startDate = $this->get('start_date', date('Y-m-d', strtotime('-7 days')));
        $endDate = $this->get('end_date', date('Y-m-d'));
        $employeeId = $this->get('employee_id', '');
        
        // Validate dates - ensure they are not in the future
        $today = date('Y-m-d');
        if ($startDate > $today) {
            $_SESSION['error'] = 'Start date cannot be in the future. Please select a past date.';
            $startDate = date('Y-m-d', strtotime('-7 days'));
        }
        if ($endDate > $today) {
            $_SESSION['error'] = 'End date cannot be in the future. Please select a past date or today.';
            $endDate = $today;
        }
        if ($startDate > $endDate) {
            $_SESSION['error'] = 'Start date must be before or equal to end date.';
            $startDate = date('Y-m-d', strtotime('-7 days'));
            $endDate = $today;
        }
        
        // Get device info and users
        $deviceInfo = null;
        $deviceStatus = 'disconnected';
        $deviceUsers = [];
        $connectionResult = $hikvision->testConnection();
        if ($connectionResult['success']) {
            $deviceStatus = 'connected';
            $deviceInfo = $connectionResult['device_info'] ?? $connectionResult['raw_response'] ?? null;
            
            // Get users from device
            try {
                $deviceUsers = $hikvision->getUsers();
            } catch (Exception $e) {
                error_log('Error getting device users: ' . $e->getMessage());
                $deviceUsers = [];
            }
        }
        
        // Get attendance records from machine
        $machineRecords = [];
        $error = null;
        $debugInfo = null;
        if ($deviceStatus === 'connected') {
            try {
                // Get attendance report (getAttendanceReport expects YYYY-MM-DD format, not datetime)
                $machineRecords = $hikvision->getAttendanceReport($startDate, $endDate, $employeeId ?: null);
                if (empty($machineRecords)) {
                    // Get debug info to help troubleshoot
                    $debugInfo = $hikvision->getLastDebugInfo();
                    $error = 'No attendance records found for the specified date range.';
                    if ($debugInfo) {
                        error_log('Machine attendance debug info: ' . json_encode($debugInfo, JSON_PRETTY_PRINT));
                    }
                }
            } catch (Exception $e) {
                $error = 'Error fetching attendance records: ' . $e->getMessage();
                error_log('Machine attendance error: ' . $e->getMessage());
            }
        } else {
            $error = 'Device is not connected. Please check the device connection.';
        }
        
        // Get all staff for employee ID mapping
        $staffModel = $this->model('StaffModel');
        $allStaff = $staffModel->all();
        $staffMap = [];
        foreach ($allStaff as $staff) {
            $staffMap[$staff['staff_id']] = $staff;
            if (!empty($staff['staff_nic'])) {
                $staffMap[$staff['staff_nic']] = $staff;
            }
        }
        
        $data = [
            'title' => 'Machine Attendance Records',
            'page' => 'machine-attendance',
            'deviceInfo' => $deviceInfo,
            'deviceStatus' => $deviceStatus,
            'deviceUsers' => $deviceUsers,
            'hikvisionConfig' => $hikvisionConfig,
            'machineRecords' => $machineRecords,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'employeeId' => $employeeId,
            'staffMap' => $staffMap,
            'error' => $error,
            'debugInfo' => $debugInfo,
            'message' => $_SESSION['message'] ?? null
        ];
        
        unset($_SESSION['message'], $_SESSION['error']);
        return $this->view('attendance/machine', $data);
    }
    
    /**
     * Export machine attendance records to CSV
     */
    public function exportMachineAttendance() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Restrict SAO users
        if (!$this->checkNotSAO()) {
            return;
        }
        
        $configFile = BASE_PATH . '/config/hikvision.php';
        if (!file_exists($configFile)) {
            $_SESSION['error'] = 'Hikvision configuration not found.';
            $this->redirect('attendance/machine');
            return;
        }
        
        $hikvisionConfig = require $configFile;
        require_once BASE_PATH . '/core/HikvisionIntegration.php';
        $hikvision = new HikvisionIntegration($hikvisionConfig);
        
        // Get date range from GET parameters
        $startDate = $this->get('start_date', date('Y-m-d', strtotime('-7 days')));
        $endDate = $this->get('end_date', date('Y-m-d'));
        $employeeId = $this->get('employee_id', '');
        
        // Validate dates
        $today = date('Y-m-d');
        if ($startDate > $today) {
            $startDate = date('Y-m-d', strtotime('-7 days'));
        }
        if ($endDate > $today) {
            $endDate = $today;
        }
        
        // Test connection
        $connectionResult = $hikvision->testConnection();
        if (!$connectionResult['success']) {
            $_SESSION['error'] = 'Device is not connected. Cannot export data.';
            $this->redirect('attendance/machine?start_date=' . $startDate . '&end_date=' . $endDate);
            return;
        }
        
        // Get attendance records
        try {
            $machineRecords = $hikvision->getAttendanceReport($startDate, $endDate, $employeeId ?: null);
            
            if (empty($machineRecords)) {
                $_SESSION['error'] = 'No attendance records found for the specified date range.';
                $this->redirect('attendance/machine?start_date=' . $startDate . '&end_date=' . $endDate);
                return;
            }
            
            // Get staff mapping
            $staffModel = $this->model('StaffModel');
            $allStaff = $staffModel->all();
            $staffMap = [];
            foreach ($allStaff as $staff) {
                $staffMap[$staff['staff_id']] = $staff;
                if (!empty($staff['staff_nic'])) {
                    $staffMap[$staff['staff_nic']] = $staff;
                }
            }
            
            // Set headers for CSV download
            $filename = 'machine_attendance_' . $startDate . '_to_' . $endDate . '.csv';
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            // Open output stream
            $output = fopen('php://output', 'w');
            
            // Add BOM for UTF-8 (helps Excel recognize UTF-8)
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Write CSV headers
            fputcsv($output, [
                'Date',
                'Time',
                'Employee ID',
                'Card No.',
                'Staff Name',
                'Department',
                'Type',
                'Device Name'
            ]);
            
            // Write data rows
            foreach ($machineRecords as $record) {
                $employeeId = $record['employee_id'] ?? $record['employee_no'] ?? '';
                $staffInfo = $staffMap[$employeeId] ?? null;
                if (!$staffInfo && !empty($employeeId)) {
                    foreach ($staffMap as $key => $staff) {
                        if (is_array($staff) && isset($staff['staff_nic']) && $staff['staff_nic'] == $employeeId) {
                            $staffInfo = $staff;
                            break;
                        }
                    }
                }
                
                $recordDate = $record['date'] ?? '';
                $recordTime = $record['time'] ?? '';
                // Extract just time part if it's a datetime
                if (preg_match('/(\d{2}:\d{2}:\d{2})/', $recordTime, $matches)) {
                    $recordTime = $matches[1];
                }
                
                $recordType = $record['type'] ?? $record['event_description'] ?? '';
                $typeLabel = '';
                if ($recordType == '1' || $recordType == 1 || strtolower($recordType) == 'check-in' || stripos($recordType, 'check-in') !== false) {
                    $typeLabel = 'Check-In';
                } elseif ($recordType == '2' || $recordType == 2 || strtolower($recordType) == 'check-out' || stripos($recordType, 'check-out') !== false) {
                    $typeLabel = 'Check-Out';
                } else {
                    $typeLabel = $recordType ?: 'Unknown';
                }
                
                fputcsv($output, [
                    $recordDate,
                    $recordTime,
                    $employeeId,
                    $record['card_no'] ?? '',
                    $staffInfo && is_array($staffInfo) ? ($staffInfo['staff_name'] ?? '') : '',
                    $staffInfo && is_array($staffInfo) ? ($staffInfo['department_name'] ?? '') : '',
                    $typeLabel,
                    $record['device_name'] ?? $record['device_id'] ?? ''
                ]);
            }
            
            fclose($output);
            exit;
            
        } catch (Exception $e) {
            error_log('Export machine attendance error: ' . $e->getMessage());
            $_SESSION['error'] = 'Error exporting attendance records: ' . $e->getMessage();
            $this->redirect('attendance/machine?start_date=' . $startDate . '&end_date=' . $endDate);
            return;
        }
    }
    
    /**
     * Get groups by course and academic year (AJAX endpoint)
     */
    public function getGroupsByCourseAndYear() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->json(['success' => false, 'error' => 'Unauthorized'], 401);
        }
        
        $courseId = $this->get('course_id', '');
        $academicYear = $this->get('academic_year', '');
        
        if (empty($courseId) || empty($academicYear)) {
            $this->json(['success' => true, 'groups' => []]);
        }
        
        $groupModel = $this->model('GroupModel');
        $groups = $groupModel->getGroupsByCourseAndYear($courseId, $academicYear);
        
        $this->json(['success' => true, 'groups' => $groups]);
    }
}

