<?php
/**
 * Student Dashboard Controller
 */

class StudentDashboardController extends Controller {
    
    public function index() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Check if user is a student - prevent non-students from accessing
        if (!isset($_SESSION['user_table']) || $_SESSION['user_table'] !== 'student') {
            $_SESSION['error'] = 'Access denied. This dashboard is only available for students.';
            // Redirect to appropriate dashboard based on user type
            require_once BASE_PATH . '/models/UserModel.php';
            $userModel = new UserModel();
            if ($userModel->isHOD($_SESSION['user_id'])) {
                $this->redirect('hod/dashboard');
            } else {
                $this->redirect('dashboard');
            }
            return;
        }
        
        require_once BASE_PATH . '/models/UserModel.php';
        $dashUserModel = new UserModel();
        if ($dashUserModel->mustChangePassword((int) $_SESSION['user_id'])) {
            $this->redirect('student/change-password');
            return;
        }

        $studentModel = $this->model('StudentModel');
        $attendanceModel = $this->model('AttendanceModel');
        $enrollmentModel = $this->model('StudentEnrollmentModel');
        $roomAllocationModel = $this->model('RoomAllocationModel');
        
        // Get current student
        require_once BASE_PATH . '/helpers/AdmissionRegistrationService.php';
        $studentId = (new AdmissionRegistrationService())->resolveStudentPortalId((string) ($_SESSION['user_name'] ?? ''));
        if ($studentId) {
            $_SESSION['user_name'] = $studentId;
        }
        $student = $studentId ? $studentModel->find($studentId) : null;
        
        if (!$student) {
            $_SESSION['error'] = 'Student record not found.';
            $this->redirect('logout');
            return;
        }
        
        // Get current enrollment
        $currentEnrollment = $enrollmentModel->getCurrentEnrollment($studentId);

        $currentGroup = null;
        try {
            $db = Database::getInstance();
            $gStmt = $db->prepare("SELECT g.`name`, g.`academic_year`, g.`course_id`
                FROM `group_students` gs
                INNER JOIN `groups` g ON g.`id` = gs.`group_id`
                WHERE gs.`student_id` = ? AND gs.`status` = 'active'
                ORDER BY gs.`enrolled_at` DESC LIMIT 1");
            if ($gStmt) {
                $gStmt->bind_param('s', $studentId);
                $gStmt->execute();
                $currentGroup = $gStmt->get_result()->fetch_assoc() ?: null;
            }
        } catch (Throwable $e) {
            $currentGroup = null;
        }
        
        // Get hostel allocation
        $hostelAllocation = $roomAllocationModel->getActiveByStudentId($studentId);
        
        // Get active roommates (other active allocations in same room)
        $roommates = [];
        if (!empty($hostelAllocation) && !empty($hostelAllocation['room_id'])) {
            $roomAllocations = $roomAllocationModel->getByRoomId($hostelAllocation['room_id'], ['status' => 'active']);
            if (!empty($roomAllocations)) {
                foreach ($roomAllocations as $a) {
                    if (!empty($a['student_id']) && $a['student_id'] !== $studentId) {
                        $roommates[] = $a;
                    }
                }
            }
        }
        
        // Get attendance summary for current month
        $currentMonth = date('Y-m');
        $startDate = $currentMonth . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));
        
        $attendanceRecords = $attendanceModel->getAttendanceByStudentAndDateRange($studentId, $startDate, $endDate);
        
        // Calculate attendance statistics
        $totalDays = 0;
        $presentDays = 0;
        $absentDays = 0;
        $holidayDays = 0;
        $attendancePercentage = 0;
        
        foreach ($attendanceRecords as $date => $status) {
            $dayOfWeek = date('w', strtotime($date));
            // Skip weekends
            if ($dayOfWeek == 0 || $dayOfWeek == 6) {
                continue;
            }
            
            $totalDays++;
            if ($status == 1) {
                $presentDays++;
            } elseif ($status == 0) {
                $absentDays++;
            } elseif ($status == -1) {
                $holidayDays++;
                $totalDays--; // Don't count holidays in total
            }
        }
        
        if ($totalDays > 0) {
            $attendancePercentage = round(($presentDays / $totalDays) * 100, 2);
        }
        
        // Get recent attendance (last 10 days)
        $recentAttendance = array_slice($attendanceRecords, -10, 10, true);
        
        // Get payment info
        $recentPayments = [];
        $busSeasonPayments = [];
        try {
            // Generic/hostel/other payments from finance system
            $paymentModel = $this->model('PaymentModel');
            $recentPayments = $paymentModel->getPaymentsByStudent($studentId, 1, 5);
        } catch (Exception $e) {
            // Ignore and leave empty
        }
        
        try {
            // Bus season payments (season ticket collections)
            $busSeasonModel = $this->model('BusSeasonRequestModel');
            $busSeasonModel->ensureTableStructure();
            $busSeasonPayments = $busSeasonModel->getAllPaymentsByStudentId($studentId);
        } catch (Exception $e) {
            // Ignore and leave empty
        }
        
        // Check if student has accepted code of conduct
        $hasAcceptedConduct = !empty($student['student_conduct_accepted_at']);
        
        $data = [
            'title' => 'Student Dashboard',
            'page' => 'student-dashboard',
            'student' => $student,
            'currentEnrollment' => $currentEnrollment,
            'currentGroup' => $currentGroup,
            'hostelAllocation' => $hostelAllocation,
            'roommates' => $roommates,
            'attendanceRecords' => $attendanceRecords,
            'totalDays' => $totalDays,
            'presentDays' => $presentDays,
            'absentDays' => $absentDays,
            'holidayDays' => $holidayDays,
            'attendancePercentage' => $attendancePercentage,
            'currentMonth' => $currentMonth,
            'recentAttendance' => $recentAttendance,
            'recentPayments' => $recentPayments,
            'busSeasonPayments' => $busSeasonPayments,
            'hasAcceptedConduct' => $hasAcceptedConduct,
            'studentNotices' => array_slice($this->studentHandbook()['notices'] ?? [], 0, 3),
        ];
        
        return $this->view('student/dashboard', $data);
    }

    /**
     * Forms available to logged-in students (PDF filled from student profile).
     */
    public function forms() {
        if (!$this->requireStudentAccess()) {
            return;
        }

        $forms = [
            [
                'key' => 'student-application',
                'title' => 'Student Application Form',
                'description' => 'A4 application PDF filled with your login student details (2 pages).',
                'icon' => 'fa-file-pdf',
                'action_label' => 'Download PDF',
                'url' => rtrim(APP_URL, '/') . '/student/forms/download?form=student-application',
            ],
        ];

        return $this->view('student/forms', [
            'title' => 'Forms',
            'page' => 'student-forms',
            'forms' => $forms,
        ]);
    }

    /**
     * Download application form PDF filled from the logged-in student record.
     */
    public function downloadForm() {
        if (!$this->requireStudentAccess()) {
            return;
        }

        $form = strtolower(trim((string) $this->get('form', '')));
        if ($form === 'student-application' || $form === 'application') {
            $studentId = (string) ($_SESSION['user_name'] ?? '');
            $studentModel = $this->model('StudentModel');
            $student = $studentModel->find($studentId);
            if (!$student) {
                $_SESSION['error'] = 'Student record not found.';
                $this->redirect('student/forms');
                return;
            }

            $enrollment = null;
            try {
                $enrollmentModel = $this->model('StudentEnrollmentModel');
                $enrollment = $enrollmentModel->getCurrentEnrollment($studentId)
                    ?: $enrollmentModel->getLatestEnrollment($studentId);
            } catch (Throwable $e) {
                $enrollment = null;
            }

            require_once BASE_PATH . '/helpers/StudentSampleFormsPdfHelper.php';
            $application = StudentSampleFormsPdfHelper::findApplicationForStudent($student);
            StudentSampleFormsPdfHelper::streamStudentApplicationBlank(true, $student, $enrollment, $application);
            return;
        }

        $_SESSION['error'] = 'Requested form was not found.';
        $this->redirect('student/forms');
    }

    public function notices() {
        if (!$this->requireStudentAccess()) {
            return;
        }
        $handbook = $this->studentHandbook();
        return $this->view('student/notices', [
            'title' => 'SLGTI notices and rules',
            'page' => 'student-notices',
            'notices' => $handbook['notices'] ?? [],
            'codeOfConduct' => $handbook['code_of_conduct'] ?? [],
            'commonRules' => $handbook['common_rules'] ?? [],
        ]);
    }

    private function studentHandbook(): array {
        $file = BASE_PATH . '/config/slgti_student_notices.php';
        if (!is_file($file)) {
            return ['notices' => [], 'code_of_conduct' => [], 'common_rules' => []];
        }
        $data = require $file;
        return is_array($data) ? $data : ['notices' => [], 'code_of_conduct' => [], 'common_rules' => []];
    }

    /**
     * @return bool
     */
    private function requireStudentAccess(): bool {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return false;
        }
        if (!isset($_SESSION['user_table']) || $_SESSION['user_table'] !== 'student') {
            $_SESSION['error'] = 'Access denied. This section is only available for students.';
            require_once BASE_PATH . '/models/UserModel.php';
            $userModel = new UserModel();
            if ($userModel->isHOD($_SESSION['user_id'])) {
                $this->redirect('hod/dashboard');
            } else {
                $this->redirect('dashboard');
            }
            return false;
        }
        return true;
    }

    /**
     * Detailed payments page for students
     */
    public function payments() {
        // Same auth + role checks as index
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        if (!isset($_SESSION['user_table']) || $_SESSION['user_table'] !== 'student') {
            $_SESSION['error'] = 'Access denied. This section is only available for students.';
            require_once BASE_PATH . '/models/UserModel.php';
            $userModel = new UserModel();
            if ($userModel->isHOD($_SESSION['user_id'])) {
                $this->redirect('hod/dashboard');
            } else {
                $this->redirect('dashboard');
            }
            return;
        }
        
        $studentId = $_SESSION['user_name'];
        $studentModel = $this->model('StudentModel');
        $student = $studentModel->find($studentId);
        if (!$student) {
            $_SESSION['error'] = 'Student record not found.';
            $this->redirect('logout');
            return;
        }
        
        // Load all payments
        $hostelAndOther = [];
        $busSeasonPayments = [];
        try {
            $paymentModel = $this->model('PaymentModel');
            $hostelAndOther = $paymentModel->getByStudentId($studentId);
        } catch (Exception $e) {
        }
        
        try {
            $busSeasonModel = $this->model('BusSeasonRequestModel');
            $busSeasonModel->ensureTableStructure();
            $busSeasonPayments = $busSeasonModel->getAllPaymentsByStudentId($studentId);
        } catch (Exception $e) {
        }
        
        $data = [
            'title' => 'My Payments',
            'page' => 'student-dashboard',
            'student' => $student,
            'hostelPayments' => $hostelAndOther,
            'busSeasonPayments' => $busSeasonPayments,
        ];
        
        return $this->view('student/payments', $data);
    }
}

