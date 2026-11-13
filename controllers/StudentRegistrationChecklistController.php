<?php
/**
 * Student Registration Checklist Module
 * Seven stations, fee calculation, save status, A4 print.
 */
class StudentRegistrationChecklistController extends Controller {

    private function requireAccess(): bool {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return false;
        }
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $uid = (int) $_SESSION['user_id'];
        if ($userModel->getUserRole($uid) !== 'ADM' && !$userModel->isSAO($uid) && !$this->isHOD()) {
            $_SESSION['error'] = 'Access denied. Only ADM, SAO, and HOD users can use the registration checklist.';
            $this->redirect('students');
            return false;
        }
        return true;
    }

    /**
     * @return array{academic_year:string,department_id:string,course_id:string,status:string,current_enrollment:bool}
     */
    private function filtersFrom(array $src): array {
        $userDepartmentId = $this->getUserDepartment();
        $restricted = $this->isDepartmentRestricted();

        $year = trim((string) ($src['academic_year'] ?? ''));
        $dept = trim((string) ($src['department_id'] ?? ''));
        $course = trim((string) ($src['course_id'] ?? ''));

        if ($restricted) {
            $dept = $userDepartmentId ?: '__none__';
        } elseif ($userDepartmentId && $dept === '') {
            // leave open for ADM/SAO with dept assignment — use posted/get value
        }

        return [
            'academic_year' => $year,
            'department_id' => $dept,
            'course_id' => $course,
            'status' => '',
            'current_enrollment' => true,
        ];
    }

    /**
     * List students for registration checklist.
     * URL: /students/registration-checklist
     */
    public function index() {
        if (!$this->requireAccess()) {
            return;
        }

        if (empty($_SESSION['reg_checklist_csrf'])) {
            $_SESSION['reg_checklist_csrf'] = bin2hex(random_bytes(32));
        }

        $studentModel = $this->model('StudentModel');
        $departmentModel = $this->model('DepartmentModel');
        $courseModel = $this->model('CourseModel');
        $checklistModel = $this->model('StudentRegistrationChecklistModel');
        $checklistModel->ensureTable();

        $userDepartmentId = $this->getUserDepartment();
        $restricted = $this->isDepartmentRestricted();
        $filters = $this->filtersFrom($_GET);

        $rows = $filters['academic_year'] !== ''
            ? $this->listRows($filters)
            : [];

        if ($userDepartmentId) {
            $departments = array_filter([$departmentModel->getById($userDepartmentId)]);
            $courses = $courseModel->getCoursesWithDepartment(['department_id' => $userDepartmentId]);
        } elseif ($restricted) {
            $departments = [];
            $courses = [];
        } else {
            $departments = $departmentModel->getAll();
            $courses = $courseModel->all('course_name ASC');
        }

        $data = [
            'title' => 'Student Registration Checklist',
            'page' => 'students',
            'filters' => $filters,
            'rows' => $rows,
            'departments' => $departments,
            'courses' => $courses,
            'academicYears' => $studentModel->getAcademicYears(),
            'isHOD' => $restricted,
            'csrfToken' => (string) $_SESSION['reg_checklist_csrf'],
            'error' => $_SESSION['error'] ?? null,
            'message' => $_SESSION['message'] ?? null,
        ];
        unset($_SESSION['error'], $_SESSION['message']);
        return $this->view('students/registration_checklist', $data);
    }

    /**
     * @param array{academic_year?:string,department_id?:string,course_id?:string} $filters
     * @return list<array<string,mixed>>
     */
    private function listRows(array $filters): array {
        $year = trim((string) ($filters['academic_year'] ?? ''));
        if ($year === '') {
            return [];
        }

        $db = Database::getInstance();
        $sql = "SELECT DISTINCT s.`student_id`, s.`student_title`, s.`student_fullname`, s.`student_nic`,
                       s.`student_phone`, s.`student_whatsapp`, s.`student_address`, s.`student_provice`,
                       se.`academic_year`, c.`course_name`, c.`course_nvq_level`, d.`department_name`
                FROM `student` s
                INNER JOIN `student_enroll` se ON se.`student_id` = s.`student_id`
                LEFT JOIN `course` c ON c.`course_id` = se.`course_id`
                LEFT JOIN `department` d ON d.`department_id` = c.`department_id`
                WHERE se.`academic_year` = ?
                  AND se.`student_enroll_status` IN ('Following', 'Internship')";
        $types = 's';
        $params = [$year];

        $dept = trim((string) ($filters['department_id'] ?? ''));
        if ($dept !== '' && $dept !== '__none__') {
            $sql .= ' AND c.`department_id` = ?';
            $types .= 's';
            $params[] = $dept;
        } elseif ($dept === '__none__') {
            $sql .= ' AND 1 = 0';
        }

        $course = trim((string) ($filters['course_id'] ?? ''));
        if ($course !== '') {
            $sql .= ' AND se.`course_id` = ?';
            $types .= 's';
            $params[] = $course;
        }

        $sql .= ' ORDER BY s.`student_id` ASC';
        $stmt = $db->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        /** @var StudentRegistrationChecklistModel $checklistModel */
        $checklistModel = $this->model('StudentRegistrationChecklistModel');
        $rows = [];
        $seen = [];
        while ($result && ($r = $result->fetch_assoc())) {
            $sid = trim((string) ($r['student_id'] ?? ''));
            if ($sid === '' || isset($seen[$sid])) {
                continue;
            }
            $seen[$sid] = true;

            $saved = $checklistModel->findByStudentYear($sid, $year);
            $stationData = $saved
                ? StudentRegistrationChecklistModel::normalizeStationData($saved['station_data'] ?? [])
                : StudentRegistrationChecklistModel::emptyStationData();
            $progress = StudentRegistrationChecklistModel::progressSummary($stationData);
            $feeInfo = StudentRegistrationChecklistModel::calculateFee(
                (string) ($r['student_provice'] ?? ''),
                (string) ($r['course_nvq_level'] ?? '')
            );

            $title = trim((string) ($r['student_title'] ?? ''));
            $fullname = trim((string) ($r['student_fullname'] ?? ''));
            $rows[] = [
                'student_id' => $sid,
                'name' => trim($title . ' ' . $fullname),
                'nic' => trim((string) ($r['student_nic'] ?? '')),
                'department' => trim((string) ($r['department_name'] ?? '')),
                'course' => trim((string) ($r['course_name'] ?? '')),
                'nvq' => $feeInfo['nvq'],
                'province' => trim((string) ($r['student_provice'] ?? '')),
                'academic_year' => $year,
                'applicable_fee' => $saved ? (float) $saved['applicable_fee'] : $feeInfo['applicable_fee'],
                'payment_status' => $saved ? (string) $saved['payment_status'] : 'Unpaid',
                'final_confirmed' => $saved ? (int) $saved['final_confirmed'] : 0,
                'progress' => $progress,
                'has_saved' => $saved !== null,
            ];
        }
        $stmt->close();
        return $rows;
    }

    /**
     * Open / save one student checklist.
     * URL: /students/registration-checklist/form
     */
    public function form() {
        if (!$this->requireAccess()) {
            return;
        }

        if (empty($_SESSION['reg_checklist_csrf'])) {
            $_SESSION['reg_checklist_csrf'] = bin2hex(random_bytes(32));
        }

        $studentId = trim((string) $this->get('student_id', $this->post('student_id', '')));
        $year = trim((string) $this->get('academic_year', $this->post('academic_year', '')));

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->saveForm();
            return;
        }

        if ($studentId === '' || $year === '') {
            $_SESSION['error'] = 'Select a student and academic year.';
            $this->redirect('students/registration-checklist');
            return;
        }

        $context = $this->loadStudentContext($studentId, $year);
        if ($context === null) {
            $_SESSION['error'] = 'Student enrolment not found for that academic year.';
            $this->redirect('students/registration-checklist?academic_year=' . urlencode($year));
            return;
        }

        /** @var StudentRegistrationChecklistModel $checklistModel */
        $checklistModel = $this->model('StudentRegistrationChecklistModel');
        $checklistModel->ensureTable();
        $saved = $checklistModel->findByStudentYear($studentId, $year);

        $feeInfo = StudentRegistrationChecklistModel::calculateFee(
            (string) ($context['province'] ?? ''),
            (string) ($context['nvq_level'] ?? '')
        );

        $stationData = $saved
            ? StudentRegistrationChecklistModel::normalizeStationData($saved['station_data'] ?? [])
            : StudentRegistrationChecklistModel::emptyStationData();

        $applicableFee = $saved ? (float) $saved['applicable_fee'] : $feeInfo['applicable_fee'];
        $amountPaid = $saved ? (float) $saved['amount_paid'] : 0.0;
        $receipt = $saved ? (string) $saved['receipt_number'] : '';
        $paymentStatus = $saved
            ? (string) $saved['payment_status']
            : StudentRegistrationChecklistModel::paymentStatus($applicableFee, $amountPaid);
        $outstanding = $saved
            ? (float) $saved['outstanding_balance']
            : max(0, round($applicableFee - $amountPaid, 2));

        require_once BASE_PATH . '/helpers/ComplaintLetterPdfHelper.php';
        $logoSrc = ComplaintLetterPdfHelper::logoDataUri();
        if ($logoSrc === '' && is_file(BASE_PATH . '/assets/img/logo.png')) {
            $logoSrc = APP_URL . '/assets/img/logo.png';
        }
        $institute = ComplaintLetterPdfHelper::institutePostFrom();

        $staffName = trim((string) ($_SESSION['user_name'] ?? $_SESSION['username'] ?? ''));
        if ($staffName === '' && !empty($_SESSION['user_id'])) {
            require_once BASE_PATH . '/models/UserModel.php';
            $userModel = new UserModel();
            $u = $userModel->find((int) $_SESSION['user_id']);
            if (is_array($u)) {
                $staffName = trim((string) ($u['user_name'] ?? $u['user_fullname'] ?? ''));
            }
        }

        $data = [
            'title' => 'Registration Checklist — ' . $studentId,
            'page' => 'students',
            'student' => $context,
            'stations' => StudentRegistrationChecklistModel::stations(),
            'stationData' => $stationData,
            'feeInfo' => $feeInfo,
            'applicableFee' => $applicableFee,
            'amountPaid' => $amountPaid,
            'outstanding' => $outstanding,
            'receiptNumber' => $receipt,
            'paymentStatus' => $paymentStatus,
            'remarks' => $saved ? (string) ($saved['remarks'] ?? '') : '',
            'finalConfirmed' => $saved ? (int) $saved['final_confirmed'] : 0,
            'officerName' => $saved ? (string) ($saved['officer_name'] ?? '') : $staffName,
            'coordinatorName' => $saved ? (string) ($saved['coordinator_name'] ?? '') : '',
            'csrfToken' => (string) $_SESSION['reg_checklist_csrf'],
            'logoSrc' => $logoSrc,
            'institute' => $institute,
            'error' => $_SESSION['error'] ?? null,
            'message' => $_SESSION['message'] ?? null,
            'printMode' => ((string) $this->get('print', '')) === '1',
        ];
        unset($_SESSION['error'], $_SESSION['message']);
        return $this->view('students/registration_checklist_form', $data);
    }

    private function saveForm(): void {
        $studentId = trim((string) $this->post('student_id', ''));
        $year = trim((string) $this->post('academic_year', ''));
        $back = 'students/registration-checklist/form?student_id=' . urlencode($studentId)
            . '&academic_year=' . urlencode($year);

        $sent = (string) $this->post('csrf_token', '');
        if ($sent === '' || !hash_equals((string) ($_SESSION['reg_checklist_csrf'] ?? ''), $sent)) {
            $_SESSION['error'] = 'Your session expired. Please try again.';
            $this->redirect($back);
            return;
        }

        $context = $this->loadStudentContext($studentId, $year);
        if ($context === null) {
            $_SESSION['error'] = 'Student enrolment not found.';
            $this->redirect('students/registration-checklist');
            return;
        }

        $feeInfo = StudentRegistrationChecklistModel::calculateFee(
            (string) ($context['province'] ?? ''),
            (string) ($context['nvq_level'] ?? '')
        );
        $applicableFee = (float) $feeInfo['applicable_fee'];
        // Allow override only if posted fee matches schedule (security: keep calculated)
        $amountPaid = (float) str_replace([',', ' '], '', (string) $this->post('amount_paid', '0'));
        if ($amountPaid < 0) {
            $amountPaid = 0;
        }
        $receipt = trim((string) $this->post('receipt_number', ''));
        $paymentStatus = StudentRegistrationChecklistModel::paymentStatus($applicableFee, $amountPaid);

        $postedStations = $this->post('stations', []);
        if (!is_array($postedStations)) {
            $postedStations = [];
        }
        $stationData = StudentRegistrationChecklistModel::emptyStationData();
        $now = date('Y-m-d H:i');
        foreach (StudentRegistrationChecklistModel::stations() as $num => $station) {
            $key = (string) $num;
            $block = is_array($postedStations[$key] ?? null) ? $postedStations[$key] : [];
            foreach (array_keys($station['items']) as $itemKey) {
                $stationData[$key]['items'][$itemKey] = !empty($block['items'][$itemKey]);
            }
            $staff = trim((string) ($block['staff'] ?? ''));
            $stationData[$key]['staff'] = $staff;
            $prevAt = trim((string) ($block['completed_at'] ?? ''));
            $anyChecked = in_array(true, $stationData[$key]['items'], true);
            if ($anyChecked && $staff !== '' && $prevAt === '') {
                $stationData[$key]['completed_at'] = $now;
            } else {
                $stationData[$key]['completed_at'] = $prevAt;
            }
        }

        // Station 3 payment validation: if payment_received ticked, require amount/receipt consistency
        if (!empty($stationData['3']['items']['payment_received']) && $amountPaid <= 0) {
            $_SESSION['error'] = 'Financial Clearance: enter Amount Paid when “Payment received” is checked.';
            $this->redirect($back);
            return;
        }
        if (!empty($stationData['3']['items']['receipt_issued']) && $receipt === '') {
            $_SESSION['error'] = 'Financial Clearance: enter Receipt Number when “Receipt issued” is checked.';
            $this->redirect($back);
            return;
        }

        /** @var StudentRegistrationChecklistModel $checklistModel */
        $checklistModel = $this->model('StudentRegistrationChecklistModel');
        $ok = $checklistModel->saveChecklist([
            'student_id' => $studentId,
            'academic_year' => $year,
            'station_data' => $stationData,
            'applicable_fee' => $applicableFee,
            'amount_paid' => $amountPaid,
            'receipt_number' => $receipt,
            'payment_status' => $paymentStatus,
            'remarks' => trim((string) $this->post('remarks', '')),
            'final_confirmed' => !empty($this->post('final_confirmed', '')),
            'officer_name' => trim((string) $this->post('officer_name', '')),
            'coordinator_name' => trim((string) $this->post('coordinator_name', '')),
            'updated_by' => (int) ($_SESSION['user_id'] ?? 0),
        ]);

        if (!$ok) {
            $_SESSION['error'] = 'Could not save the registration checklist.';
            $this->redirect($back);
            return;
        }

        $_SESSION['reg_checklist_csrf'] = bin2hex(random_bytes(32));
        $_SESSION['message'] = 'Registration checklist saved.';

        if ((string) $this->post('action', '') === 'save_print') {
            $this->redirect($back . '&print=1');
            return;
        }
        $this->redirect($back);
    }

    /**
     * @return array<string,mixed>|null
     */
    private function loadStudentContext(string $studentId, string $year): ?array {
        if ($studentId === '' || $year === '') {
            return null;
        }
        $db = Database::getInstance();
        $sql = "SELECT s.`student_id`, s.`student_title`, s.`student_fullname`, s.`student_nic`,
                       s.`student_phone`, s.`student_whatsapp`, s.`student_address`, s.`student_provice`,
                       se.`academic_year`, se.`course_id`, c.`course_name`, c.`course_nvq_level`,
                       d.`department_name`, d.`department_id`
                FROM `student` s
                INNER JOIN `student_enroll` se ON se.`student_id` = s.`student_id`
                LEFT JOIN `course` c ON c.`course_id` = se.`course_id`
                LEFT JOIN `department` d ON d.`department_id` = c.`department_id`
                WHERE s.`student_id` = ?
                  AND se.`academic_year` = ?
                  AND se.`student_enroll_status` IN ('Following', 'Internship')
                ORDER BY se.`student_enroll_id` DESC
                LIMIT 1";
        $stmt = $db->prepare($sql);
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('ss', $studentId, $year);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$r) {
            return null;
        }

        // Department restriction
        if ($this->isDepartmentRestricted()) {
            $userDept = $this->getUserDepartment();
            if (!$userDept || (string) ($r['department_id'] ?? '') !== (string) $userDept) {
                return null;
            }
        }

        $phone = trim((string) ($r['student_whatsapp'] ?? ''));
        if ($phone === '') {
            $phone = trim((string) ($r['student_phone'] ?? ''));
        }
        $title = trim((string) ($r['student_title'] ?? ''));
        $fullname = trim((string) ($r['student_fullname'] ?? ''));
        $nvq = StudentRegistrationChecklistModel::normalizeNvq((string) ($r['course_nvq_level'] ?? ''));

        return [
            'student_id' => trim((string) $r['student_id']),
            'name' => trim($title . ' ' . $fullname),
            'nic' => trim((string) ($r['student_nic'] ?? '')),
            'phone' => $phone,
            'address' => trim((string) ($r['student_address'] ?? '')),
            'province' => trim((string) ($r['student_provice'] ?? '')),
            'course_name' => trim((string) ($r['course_name'] ?? '')),
            'nvq_level' => $nvq,
            'department' => trim((string) ($r['department_name'] ?? '')),
            'academic_year' => $year,
        ];
    }
}
