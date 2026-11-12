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

        $dl = rtrim(APP_URL, '/') . '/student/forms/download?form=';
        $forms = [
            [
                'key' => 'student-confirmation',
                'title' => 'Student Confirmation Letter',
                'description' => 'Official confirmation letter for your NVQ level and province (Level 04/05 × Northern / Other Provinces), filled from your student profile.',
                'icon' => 'fa-envelope-open-text',
                'pages' => '4 pages',
                'submit_to' => 'Keep for your records / admissions',
                'action_label' => 'Download PDF',
                'url' => $dl . 'student-confirmation',
            ],
            [
                'key' => 'hostel-request',
                'title' => 'Hostel Request Form',
                'description' => 'Request hostel admission, renewal or a change of room.',
                'icon' => 'fa-bed',
                'pages' => '1 page',
                'submit_to' => 'Hostel Office',
                'action_label' => 'Download PDF',
                'url' => $dl . 'hostel-request',
            ],
            [
                'key' => 'bus-season-request',
                'title' => 'Bus Season Request Form',
                'description' => 'Apply for a monthly bus season ticket for your route.',
                'icon' => 'fa-bus',
                'pages' => '1 page',
                'submit_to' => 'Student Affairs Office',
                'action_label' => 'Download PDF',
                'url' => $dl . 'bus-season-request',
            ],
            [
                'key' => 'family-income',
                'title' => 'Family Income Confirmation',
                'description' => 'Declare family annual income below Rs. 500,000 for the Rs. 7,500 monthly student allowance. Needs Grama Niladhari certification.',
                'icon' => 'fa-file-signature',
                'pages' => '1 page',
                'submit_to' => 'Branch Principal',
                'action_label' => 'Download PDF',
                'url' => $dl . 'family-income',
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
        $allowed = [
            'student-confirmation',
            'confirmation',
            'confirmation-letter',
            'calling-letter',
            'student-application',
            'application',
            'hostel-request',
            'bus-season-request',
            'family-income',
        ];
        if (!in_array($form, $allowed, true)) {
            $_SESSION['error'] = 'Requested form was not found.';
            $this->redirect('student/forms');
            return;
        }

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
        if ($enrollment === false) {
            $enrollment = null;
        }

        $confirmationForms = ['student-confirmation', 'confirmation', 'confirmation-letter', 'calling-letter', 'student-application', 'application'];
        if (in_array($form, $confirmationForms, true)) {
            $this->streamStudentConfirmationLetter($student, is_array($enrollment) ? $enrollment : null);
            return;
        }

        require_once BASE_PATH . '/helpers/StudentSampleFormsPdfHelper.php';
        $application = StudentSampleFormsPdfHelper::findApplicationForStudent($student);

        if ($form === 'family-income') {
            StudentSampleFormsPdfHelper::streamFamilyIncomeForm(true, $student, $enrollment, $application);
            return;
        }

        if ($form === 'hostel-request') {
            $allocation = null;
            try {
                $roomAllocationModel = $this->model('RoomAllocationModel');
                $row = $roomAllocationModel->getActiveByStudentId($studentId);
                $allocation = is_array($row) ? $row : null;
            } catch (Throwable $e) {
                $allocation = null;
            }
            StudentSampleFormsPdfHelper::streamHostelRequestForm(true, $student, $enrollment, $application, $allocation);
            return;
        }

        if ($form === 'bus-season-request') {
            $latest = null;
            try {
                $busSeasonModel = $this->model('BusSeasonRequestModel');
                $rows = $busSeasonModel->getByStudentId($studentId);
                if (is_array($rows) && isset($rows[0]) && is_array($rows[0])) {
                    $latest = $rows[0];
                }
            } catch (Throwable $e) {
                $latest = null;
            }
            StudentSampleFormsPdfHelper::streamBusSeasonRequestForm(true, $student, $enrollment, $application, $latest);
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
     * Stream the official confirmation letter for the logged-in student.
     * Template is chosen from NVQ level (04/05) and province (Northern / Other).
     *
     * @param array<string,mixed> $student
     * @param array<string,mixed>|null $enrollment
     */
    private function streamStudentConfirmationLetter(array $student, ?array $enrollment): void {
        require_once BASE_PATH . '/helpers/CallingLetterPdfHelper.php';

        $clean = static function ($v): string {
            return trim(preg_replace('/\s+/u', ' ', (string) ($v ?? '')) ?? '');
        };

        $nvqRaw = (string) ($enrollment['course_nvq_level'] ?? '');
        $province = (string) ($student['student_provice'] ?? '');
        $templateKey = CallingLetterPdfHelper::resolveTemplateKeyForStudent($nvqRaw, $province);
        if ($templateKey === null) {
            $_SESSION['error'] = 'Confirmation letter is available for NVQ Level 04 and 05 students only. Your course level could not be matched.';
            $this->redirect('student/forms');
            return;
        }

        if (!is_file(CallingLetterPdfHelper::templatePath($templateKey))) {
            $_SESSION['error'] = 'Confirmation letter template is missing for ' . CallingLetterPdfHelper::labelForTemplate($templateKey) . '.';
            $this->redirect('student/forms');
            return;
        }

        $name = $clean($student['student_fullname'] ?? '');
        $name = preg_replace('/^(Mr|Ms|Mrs|Miss|Dr)\.?\s+/iu', '', $name) ?? $name;
        $name = $clean($name);

        $addressLines = [];
        foreach (preg_split('/\r\n|\r|\n/', (string) ($student['student_address'] ?? '')) as $part) {
            $part = $clean($part);
            if ($part !== '') {
                $addressLines[] = $part;
            }
        }
        $district = $clean($student['student_district'] ?? '');
        $zip = $clean($student['student_zip'] ?? '');
        $tail = trim($district . ($zip !== '' ? ' ' . $zip : ''));
        if ($tail !== '') {
            $addressLines[] = $tail;
        }
        if (count($addressLines) > 2) {
            $addressLines = [
                $addressLines[0],
                implode(', ', array_slice($addressLines, 1)),
            ];
        }

        $nvqLevel = CallingLetterPdfHelper::nvqLevelForTemplate($templateKey);
        $row = [
            'name' => $name,
            'address_lines' => $addressLines,
            'department' => $clean($enrollment['department_name'] ?? ''),
            'course' => $clean($enrollment['course_name'] ?? ''),
            'nvq_level' => $nvqLevel,
        ];

        $studentIdSafe = preg_replace('/[^A-Za-z0-9_-]+/', '_', (string) ($student['student_id'] ?? 'student'));
        $filename = 'confirmation_letter_' . strtoupper($templateKey) . '_' . $studentIdSafe . '.pdf';

        session_write_close();
        try {
            $bytes = CallingLetterPdfHelper::buildSingleStudentPdfBytes($row, $templateKey);
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($bytes));
            echo $bytes;
            exit;
        } catch (Throwable $ex) {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                @session_start();
            }
            $_SESSION['error'] = 'Could not build confirmation letter: ' . $ex->getMessage();
            $this->redirect('student/forms');
        }
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
