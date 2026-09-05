<?php
/**
 * Student fingerprint attendance (device) — SAO manage; HOD/DIR/DPA/REG view dashboard.
 */
declare(strict_types=1);

class StudentDeviceAttendanceController extends Controller {
    /**
     * Full manage access (sync, holidays, users): SAO / ADM.
     */
    private function requireAccess(): bool {
        $ctx = $this->accessContext(true);
        return $ctx !== null;
    }

    /**
     * Dashboard / month / warning letter view access.
     *
     * @return array{user_id:int,role:string,can_manage:bool,department_scope:?string}|null
     */
    private function requireDashboardAccess(): ?array {
        return $this->accessContext(false);
    }

    /**
     * @return array{user_id:int,role:string,can_manage:bool,department_scope:?string}|null
     */
    private function accessContext(bool $manageOnly): ?array {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return null;
        }
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $userId = (int) $_SESSION['user_id'];
        $canManage = $userModel->canManageStudentFingerprintAttendance($userId);
        $canView = $userModel->canViewStudentAttendanceSaoDashboard($userId);

        if ($manageOnly) {
            if (!$canManage) {
                http_response_code(403);
                echo 'Access denied. You do not have permission to manage Student Attendance.';
                exit;
            }
        } elseif (!$canView && !$canManage) {
            http_response_code(403);
            echo 'Access denied. You do not have permission to view Student Attendance.';
            exit;
        }

        $role = (string) $userModel->getUserRole($userId);
        $departmentScope = null;
        if ($userModel->isHOD($userId) && !$canManage) {
            $departmentScope = $userModel->getHODDepartment($userId);
            if ($departmentScope === null || $departmentScope === '') {
                http_response_code(403);
                echo 'Access denied. HOD department is not configured.';
                exit;
            }
        }

        return [
            'user_id' => $userId,
            'role' => $role,
            'can_manage' => $canManage,
            'department_scope' => $departmentScope !== null ? (string) $departmentScope : null,
        ];
    }

    private function syncService(): StudentDeviceAttendanceSyncService {
        require_once BASE_PATH . '/core/StudentDeviceAttendanceSyncService.php';
        return new StudentDeviceAttendanceSyncService();
    }

    private function urls(): array {
        $root = rtrim(APP_URL, '/') . '/attendance/student-device';
        return [
            'index' => $root,
            'sao' => $root . '/sao-dashboard',
            'events' => $root . '/events',
            'month' => $root . '/month',
            'holidays' => $root . '/holidays',
            'users' => $root . '/users',
            'sync' => $root . '/sync',
            'search' => $root . '/events',
            'export_excel' => $root . '/export/excel',
            'export_csv' => $root . '/export/csv',
            'export_month_excel' => $root . '/export/month-excel',
            'test' => $root . '/machine/test',
            'logs' => $root . '/logs',
            'warning' => $root . '/warning-letter',
        ];
    }

    private function filtersFromRequest(): array {
        $personId = trim((string) $this->get('person_id', ''));
        $name = trim((string) $this->get('student_name', ''));
        $date = trim((string) $this->get('date', ''));
        $dateFrom = trim((string) $this->get('date_from', ''));
        $dateTo = trim((string) $this->get('date_to', ''));
        $filters = [];
        if ($personId !== '') {
            $filters['person_id'] = $personId;
        }
        if ($name !== '') {
            $filters['student_name'] = $name;
        }
        if ($date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $filters['date'] = $date;
        }
        if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $filters['date_from'] = $dateFrom;
        }
        if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $filters['date_to'] = $dateTo;
        }
        return $filters;
    }

    private function machineHost(): string {
        $cfg = require BASE_PATH . '/config/student_attendance_machine.php';
        return (string) ($cfg['host'] ?? '');
    }

    /** Dashboard */
    public function index() {
        $ctx = $this->requireDashboardAccess();
        if ($ctx === null) {
            return;
        }
        if (!$ctx['can_manage']) {
            $this->redirect('attendance/student-device/sao-dashboard');
            return;
        }
        $svc = $this->syncService();
        $att = $svc->attendanceModel();
        $recent = $att->searchDailyGrouped([], 1, 8);

        return $this->view('attendance/student_device/index', [
            'title' => 'Student Fingerprint Attendance',
            'page' => 'student-device-attendance',
            'urls' => $this->urls(),
            'canManageDevice' => true,
            'rows' => $recent['rows'],
            'todayCount' => $att->countToday(),
            'uniqueToday' => $att->countUniqueStudentsToday(),
            'totalRecords' => $att->countAll(),
            'lastSync' => $svc->logModel()->getLastSuccessful(),
            'machineHost' => $this->machineHost(),
            'syncSummary' => $_SESSION['student_att_sync_summary'] ?? null,
            'connectionStatus' => $_SESSION['student_att_connection'] ?? null,
        ]);
    }

    /** Monthly SAO attendance dashboard (RBAC: SAO/HOD/DIR/DPA/REG) */
    public function saoDashboard() {
        $ctx = $this->requireDashboardAccess();
        if ($ctx === null) {
            return;
        }

        $svc = $this->syncService();
        $att = $svc->attendanceModel();

        $reportMonth = trim((string) $this->get('report_month', ''));
        if ($reportMonth === '' || !preg_match('/^\d{4}-\d{2}$/', $reportMonth)) {
            $reportMonth = (new DateTimeImmutable('now', new DateTimeZone('Asia/Colombo')))->format('Y-m');
        }

        $departmentId = trim((string) $this->get('department_id', ''));
        if ($ctx['department_scope'] !== null) {
            $departmentId = $ctx['department_scope'];
        }
        $groupId = trim((string) $this->get('group_id', ''));
        $studentId = trim((string) $this->get('student_id', ''));
        $statusFilter = trim((string) $this->get('status', 'flagged'));
        if (!in_array($statusFilter, ['flagged', 'low', 'ok', 'all'], true)) {
            $statusFilter = 'flagged';
        }
        $run = (string) $this->get('run', '') === '1'
            || $departmentId !== ''
            || $groupId !== ''
            || $studentId !== '';

        $departmentModel = $this->model('DepartmentModel');
        $groupModel = $this->model('GroupModel');
        $allDepartments = $departmentModel->getAll();
        if ($ctx['department_scope'] !== null) {
            $departments = array_values(array_filter(
                $allDepartments,
                static function (array $d) use ($ctx): bool {
                    return (string) ($d['department_id'] ?? '') === $ctx['department_scope'];
                }
            ));
        } else {
            $departments = $allDepartments;
        }

        $groups = $departmentId !== ''
            ? $groupModel->getAllWithDetails($departmentId)
            : ($ctx['department_scope'] === null ? $groupModel->getAllWithDetails() : $groupModel->getAllWithDetails($ctx['department_scope']));

        if ($run) {
            $dashboard = $att->buildSaoDashboard($reportMonth, $departmentId, $groupId, $studentId, $statusFilter, 3);
        } else {
            $dashboard = [
                'flagged' => [],
                'summary' => [
                    'students' => 0,
                    'working_days' => 0,
                    'present' => 0,
                    'absent' => 0,
                    'late' => 0,
                    'incomplete' => 0,
                    'leave' => 0,
                    'flagged' => 0,
                    'avg_attendance_pct' => 0.0,
                ],
                'working_days' => [],
                'in_cutoff' => '08:40',
                'out_cutoff' => '16:00',
                'consecutive_threshold' => 3,
            ];
        }

        $monthDisplay = '';
        $ts = strtotime($reportMonth . '-01 12:00:00');
        if ($ts) {
            $monthDisplay = date('F Y', $ts);
        }

        $studentsForFilter = [];
        if ($run && ($departmentId !== '' || $groupId !== '')) {
            $studentsForFilter = $att->listActiveStudentsForReport($departmentId, '', '', $groupId, '');
        }

        return $this->view('attendance/student_device/sao_dashboard', [
            'title' => 'Monthly SAO Attendance Dashboard',
            'page' => 'student-device-attendance-sao',
            'urls' => $this->urls(),
            'canManageDevice' => $ctx['can_manage'],
            'isHodScoped' => $ctx['department_scope'] !== null,
            'userRole' => $ctx['role'],
            'reportMonth' => $reportMonth,
            'departmentId' => $departmentId,
            'groupId' => $groupId,
            'studentId' => $studentId,
            'statusFilter' => $statusFilter,
            'departments' => $departments,
            'groups' => $groups,
            'studentsForFilter' => $studentsForFilter,
            'dashboard' => $dashboard,
            'monthDisplay' => $monthDisplay,
            'reportRun' => $run,
        ]);
    }

    /** Warning letter preview / print / PDF */
    public function warningLetter() {
        $ctx = $this->requireDashboardAccess();
        if ($ctx === null) {
            return;
        }

        $studentId = trim((string) $this->get('student_id', ''));
        $reportMonth = trim((string) $this->get('report_month', ''));
        $format = strtolower(trim((string) $this->get('format', 'preview')));
        if ($studentId === '' || !preg_match('/^\d{4}-\d{2}$/', $reportMonth)) {
            $_SESSION['flash_error'] = 'Select a student and month for the warning letter.';
            $this->redirect('attendance/student-device/sao-dashboard');
            return;
        }

        $departmentId = $ctx['department_scope'] ?? '';
        $svc = $this->syncService();
        $row = $svc->attendanceModel()->getSaoDashboardStudentRow($reportMonth, $studentId, (string) $departmentId);
        if ($row === null) {
            $_SESSION['flash_error'] = 'Student attendance record not found for this month.';
            $this->redirect('attendance/student-device/sao-dashboard?run=1&report_month=' . rawurlencode($reportMonth));
            return;
        }

        if ($ctx['department_scope'] !== null
            && (string) ($row['department_id'] ?? '') !== $ctx['department_scope']) {
            http_response_code(403);
            echo 'Access denied for this student.';
            exit;
        }

        $monthLabel = date('F Y', strtotime($reportMonth . '-01 12:00:00')) ?: $reportMonth;
        $meta = [
            'report_month' => $reportMonth,
            'month_label' => $monthLabel,
            'letter_date' => (new DateTimeImmutable('now', new DateTimeZone('Asia/Colombo')))->format('d F Y'),
            'reference' => 'SAO/ATT/' . date('Y') . '/' . $studentId,
            'in_cutoff' => StudentDeviceAttendanceModel::PRESENT_IN_CUTOFF,
            'out_cutoff' => StudentDeviceAttendanceModel::PRESENT_OUT_CUTOFF,
            'consecutive_threshold' => 3,
        ];
        $meta['in_cutoff'] = substr($meta['in_cutoff'], 0, 5);
        $meta['out_cutoff'] = substr($meta['out_cutoff'], 0, 5);

        require_once BASE_PATH . '/helpers/AttendanceWarningLetterHelper.php';

        if ($format === 'pdf') {
            AttendanceWarningLetterHelper::streamPdf($row, $meta, true);
            return;
        }

        $body = AttendanceWarningLetterHelper::renderHtml($row, $meta);
        $urls = $this->urls();
        $pdfUrl = $urls['warning'] . '?' . http_build_query([
            'student_id' => $studentId,
            'report_month' => $reportMonth,
            'format' => 'pdf',
        ]);
        $printUrl = $urls['warning'] . '?' . http_build_query([
            'student_id' => $studentId,
            'report_month' => $reportMonth,
            'format' => 'print',
        ]);

        if ($format === 'print') {
            require_once BASE_PATH . '/helpers/ComplaintLetterPdfHelper.php';
            return $this->view('attendance/student_device/warning_letter_print', [
                'title' => 'Attendance Warning Letter',
                'letterHtml' => $body,
                'letterCss' => ComplaintLetterPdfHelper::complaintLetterStylesheet(),
                'pdfUrl' => $pdfUrl,
                'backUrl' => $urls['sao'] . '?run=1&report_month=' . rawurlencode($reportMonth),
                'use_print_layout' => true,
            ]);
        }

        require_once BASE_PATH . '/helpers/ComplaintLetterPdfHelper.php';
        return $this->view('attendance/student_device/warning_letter', [
            'title' => 'Attendance Warning Letter',
            'page' => 'student-device-attendance-sao',
            'urls' => $urls,
            'canManageDevice' => $ctx['can_manage'],
            'student' => $row,
            'meta' => $meta,
            'letterHtml' => $body,
            'letterCss' => ComplaintLetterPdfHelper::complaintLetterStylesheet(),
            'pdfUrl' => $pdfUrl,
            'printUrl' => $printUrl,
            'reportMonth' => $reportMonth,
        ]);
    }

    /** Attendance events (In / Out / Others) */
    public function events() {
        if (!$this->requireAccess()) {
            return;
        }
        $svc = $this->syncService();
        $att = $svc->attendanceModel();
        $filters = $this->filtersFromRequest();
        $page = max(1, (int) $this->get('page', 1));
        $result = $att->searchDailyGrouped($filters, $page, 50);

        return $this->view('attendance/student_device/events', [
            'title' => 'Attendance events',
            'page' => 'student-device-attendance-events',
            'urls' => $this->urls(),
            'filters' => $filters,
            'rows' => $result['rows'],
            'total' => $result['total'],
            'pageNum' => $page,
            'perPage' => 50,
        ]);
    }

    /** Student month matrix report (device punches → 1 / 0 / H + allowance) */
    public function month() {
        $ctx = $this->requireDashboardAccess();
        if ($ctx === null) {
            return;
        }
        $svc = $this->syncService();
        $att = $svc->attendanceModel();

        $reportMonth = trim((string) $this->get('report_month', ''));
        if ($reportMonth === '' || !preg_match('/^\d{4}-\d{2}$/', $reportMonth)) {
            $reportMonth = (new DateTimeImmutable('now', new DateTimeZone('Asia/Colombo')))->format('Y-m');
        }
        $departmentId = trim((string) $this->get('department_id', ''));
        if ($ctx['department_scope'] !== null) {
            $departmentId = $ctx['department_scope'];
        }
        $courseId = trim((string) $this->get('course_id', ''));
        $academicYear = trim((string) $this->get('academic_year', ''));
        $studentId = trim((string) $this->get('student_id', ''));
        $eligibleOnly = (string) $this->get('eligible_only', '0') === '1';
        $courseMode = trim((string) $this->get('course_mode', ''));
        if (!in_array(strtolower($courseMode), ['full', 'part', 'full time', 'part time'], true)
            && !in_array($courseMode, ['Full', 'Part'], true)) {
            $courseMode = '';
        }
        $page = max(1, (int) $this->get('page', 1));
        $run = (string) $this->get('run', '') === '1'
            || $departmentId !== ''
            || $courseId !== ''
            || $academicYear !== ''
            || $studentId !== ''
            || $courseMode !== '';

        $departmentModel = $this->model('DepartmentModel');
        $courseModel = $this->model('CourseModel');
        $studentModel = $this->model('StudentModel');
        $allDepartments = $departmentModel->getAll();
        if ($ctx['department_scope'] !== null) {
            $departments = array_values(array_filter(
                $allDepartments,
                static function (array $d) use ($ctx): bool {
                    return (string) ($d['department_id'] ?? '') === $ctx['department_scope'];
                }
            ));
        } else {
            $departments = $allDepartments;
        }
        $academicYears = $studentModel->getAcademicYears();
        $courses = [];
        if ($departmentId !== '') {
            $courses = $courseModel->getCoursesWithDepartment(['department_id' => $departmentId]);
        }

        if ($run) {
            $report = $att->buildMatrixMonthReport(
                $reportMonth,
                $departmentId,
                $courseId,
                $academicYear,
                $studentId,
                $eligibleOnly,
                $page,
                40,
                $courseMode
            );
            // Don't pass full all_students to the view (memory)
            unset($report['all_students']);
        } else {
            $report = [
                'students' => [],
                'columns' => [],
                'summary' => [
                    'students' => 0,
                    'working_days' => 0,
                    'effective_working_days' => 0,
                    'present' => 0,
                    'absent' => 0,
                    'holiday' => 0,
                    'total_allowance' => 0,
                    'above_90' => 0,
                    'above_75' => 0,
                    'below_75' => 0,
                ],
                'page' => 1,
                'per_page' => 40,
                'total_students' => 0,
                'total_pages' => 0,
                'allowance_high' => 7500,
                'allowance_mid' => 6000,
                'in_cutoff' => '08:40',
                'out_cutoff' => '16:00',
            ];
        }

        $monthDisplay = '';
        $ts = strtotime($reportMonth . '-01 12:00:00');
        if ($ts) {
            $monthDisplay = date('F Y', $ts);
        }

        $scopeLabel = 'All students';
        if ($departmentId !== '') {
            foreach ($departments as $d) {
                if (($d['department_id'] ?? '') === $departmentId) {
                    $scopeLabel = (string) ($d['department_name'] ?? $departmentId);
                    break;
                }
            }
        }
        if ($courseId !== '') {
            foreach ($courses as $c) {
                if (($c['course_id'] ?? '') === $courseId) {
                    $scopeLabel .= ' · ' . (string) ($c['course_name'] ?? $courseId);
                    break;
                }
            }
        }
        if ($courseMode !== '') {
            $scopeLabel .= ' · ' . StudentDeviceAttendanceModel::courseModeLabel($courseMode);
        }

        $exportQs = http_build_query(array_filter([
            'report_month' => $reportMonth,
            'department_id' => $departmentId,
            'course_id' => $courseId,
            'academic_year' => $academicYear,
            'student_id' => $studentId,
            'course_mode' => $courseMode,
            'eligible_only' => $eligibleOnly ? '1' : '',
        ], static fn ($v) => $v !== '' && $v !== null));

        return $this->view('attendance/student_device/month', [
            'title' => 'Student month report',
            'page' => 'student-device-attendance-month',
            'urls' => $this->urls(),
            'canManageDevice' => $ctx['can_manage'],
            'reportMonth' => $reportMonth,
            'departmentId' => $departmentId,
            'courseId' => $courseId,
            'academicYear' => $academicYear,
            'studentId' => $studentId,
            'courseMode' => $courseMode,
            'eligibleOnly' => $eligibleOnly,
            'departments' => $departments,
            'courses' => $courses,
            'academicYears' => $academicYears,
            'report' => $report,
            'monthDisplay' => $monthDisplay,
            'scopeLabel' => $scopeLabel,
            'reportRun' => $run,
            'reportPage' => (int) ($report['page'] ?? 1),
            'reportTotalPages' => (int) ($report['total_pages'] ?? 0),
            'reportTotalStudents' => (int) ($report['total_students'] ?? 0),
            'exportMonthUrl' => $this->urls()['export_month_excel'] . ($exportQs !== '' ? ('?' . $exportQs) : ''),
            'isHodScoped' => $ctx['department_scope'] !== null,
        ]);
    }

    /** Excel/CSV export of matrix month report with final total allowance */
    public function exportMonthExcel() {
        $ctx = $this->requireDashboardAccess();
        if ($ctx === null) {
            return;
        }
        $svc = $this->syncService();
        $att = $svc->attendanceModel();

        $reportMonth = trim((string) $this->get('report_month', ''));
        if ($reportMonth === '' || !preg_match('/^\d{4}-\d{2}$/', $reportMonth)) {
            $_SESSION['flash_error'] = 'Select a valid month to export.';
            $this->redirect('attendance/student-device/month');
            return;
        }
        $departmentId = trim((string) $this->get('department_id', ''));
        if ($ctx['department_scope'] !== null) {
            $departmentId = $ctx['department_scope'];
        }
        $courseId = trim((string) $this->get('course_id', ''));
        $academicYear = trim((string) $this->get('academic_year', ''));
        $studentId = trim((string) $this->get('student_id', ''));
        $eligibleOnly = (string) $this->get('eligible_only', '0') === '1';
        $courseMode = trim((string) $this->get('course_mode', ''));

        $report = $att->buildMatrixMonthReport(
            $reportMonth,
            $departmentId,
            $courseId,
            $academicYear,
            $studentId,
            $eligibleOnly,
            1,
            99999,
            $courseMode
        );
        $students = $report['all_students'] ?? $report['students'] ?? [];
        $columns = $report['columns'] ?? [];
        $summary = $report['summary'] ?? [];

        $filename = 'student_device_month_' . $reportMonth . '_' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        echo "\xEF\xBB\xBF";

        $out = fopen('php://output', 'w');
        $headers = [
            'Student ID',
            'Full Name',
            'NIC',
            'Department',
            'Course Mode',
            'Bank Name',
            'Account No',
            'Branch',
        ];
        foreach ($columns as $col) {
            $headers[] = (string) ($col['day'] ?? '');
        }
        $headers[] = 'Total Days';
        $headers[] = 'P';
        $headers[] = '%';
        $headers[] = 'Allowance';
        fputcsv($out, $headers);

        foreach ($students as $st) {
            $row = [
                $st['student_id'] ?? '',
                $st['student_fullname'] ?: ($st['student_name'] ?? ''),
                $st['student_nic'] ?? '',
                $st['department_name'] ?? '',
                $st['course_mode_label'] ?? ($st['course_mode'] ?? ''),
                $st['bank_name'] !== '' ? $st['bank_name'] : '-',
                $st['bank_account_no'] !== '' ? $st['bank_account_no'] : '-',
                $st['bank_branch'] !== '' ? $st['bank_branch'] : '-',
            ];
            foreach ($columns as $col) {
                $ymd = $col['date'] ?? '';
                $cell = $st['day_by_day'][$ymd] ?? '';
                if ($cell === 'H') {
                    $row[] = '-1';
                } elseif ($cell === '1') {
                    $row[] = '1';
                } elseif ($cell === '0') {
                    $row[] = '0';
                } else {
                    $row[] = '';
                }
            }
            $row[] = (int) ($st['effective_working_days'] ?? 0);
            $row[] = (int) ($st['present_days'] ?? 0);
            $row[] = number_format((float) ($st['attendance_percentage'] ?? 0), 1) . '%';
            $row[] = number_format((float) ($st['allowance'] ?? 0), 0, '.', '');
            fputcsv($out, $row);
        }

        $totalRow = ['FINAL TOTAL'];
        for ($i = 1; $i < 8; $i++) {
            $totalRow[] = '';
        }
        foreach ($columns as $_) {
            $totalRow[] = '';
        }
        $totalRow[] = (int) ($summary['effective_working_days'] ?? 0);
        $totalRow[] = (int) ($summary['present'] ?? 0);
        $totalRow[] = '';
        $totalRow[] = number_format((float) ($summary['total_allowance'] ?? 0), 0, '.', '');
        fputcsv($out, $totalRow);

        fclose($out);
        exit;
    }

    /** Holidays and special leave (SAO / ADM) */
    public function holidays() {
        if (!$this->requireAccess()) {
            return;
        }
        require_once BASE_PATH . '/models/StudentAttendanceHolidayModel.php';
        $holidayModel = new StudentAttendanceHolidayModel();
        $holidayModel->ensureTable();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = trim((string) ($_POST['action'] ?? 'create'));
            if ($action === 'delete') {
                $id = (int) ($_POST['id'] ?? 0);
                if ($id > 0 && $holidayModel->deleteLeave($id)) {
                    $_SESSION['flash_success'] = 'Leave day removed.';
                } else {
                    $_SESSION['flash_error'] = 'Could not remove leave day.';
                }
                $this->redirect('attendance/student-device/holidays');
                return;
            }

            $leaveDate = trim((string) ($_POST['leave_date'] ?? ''));
            $leaveType = trim((string) ($_POST['leave_type'] ?? 'holiday'));
            $title = trim((string) ($_POST['title'] ?? ''));
            $departmentId = trim((string) ($_POST['department_id'] ?? ''));
            $courseId = trim((string) ($_POST['course_id'] ?? ''));
            $notes = trim((string) ($_POST['notes'] ?? ''));

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $leaveDate)) {
                $_SESSION['flash_error'] = 'Select a valid leave date.';
                $this->redirect('attendance/student-device/holidays');
                return;
            }
            if (!in_array($leaveType, ['holiday', 'special_leave'], true)) {
                $leaveType = 'holiday';
            }
            if ($title === '') {
                $title = $leaveType === 'special_leave' ? 'Special leave' : 'Holiday';
            }

            $sqlError = null;
            $ok = $holidayModel->createLeave([
                'leave_date' => $leaveDate,
                'leave_type' => $leaveType,
                'title' => mb_substr($title, 0, 150),
                'department_id' => $departmentId !== '' ? $departmentId : null,
                'course_id' => $courseId !== '' ? $courseId : null,
                'notes' => mb_substr($notes, 0, 255),
                'created_by' => (int) ($_SESSION['user_id'] ?? 0) ?: null,
            ], $sqlError);

            if ($ok !== false) {
                $_SESSION['flash_success'] = 'Leave day saved. It will be skipped in present/absent reports.';
            } else {
                $_SESSION['flash_error'] = $sqlError ?: 'Failed to save leave day.';
            }
            $this->redirect('attendance/student-device/holidays');
            return;
        }

        $departmentModel = $this->model('DepartmentModel');
        $courseModel = $this->model('CourseModel');
        $departmentId = trim((string) $this->get('department_id', ''));
        $courses = $departmentId !== ''
            ? $courseModel->getCoursesWithDepartment(['department_id' => $departmentId])
            : [];

        return $this->view('attendance/student_device/holidays', [
            'title' => 'Holidays & special leave',
            'page' => 'student-device-attendance-holidays',
            'urls' => $this->urls(),
            'rows' => $holidayModel->listRecent(250),
            'departments' => $departmentModel->getAll(),
            'courses' => $courses,
            'departmentId' => $departmentId,
        ]);
    }

    public function users() {
        if (!$this->requireAccess()) {
            return;
        }
        $svc = $this->syncService();
        return $this->view('attendance/student_device/users', [
            'title' => 'Machine users',
            'page' => 'student-device-attendance-users',
            'urls' => $this->urls(),
            'machineUsers' => $svc->attendanceModel()->listMachineUsers(500),
        ]);
    }

    public function search() {
        return $this->events();
    }

    public function filter() {
        return $this->events();
    }

    public function sync() {
        if (!$this->requireAccess()) {
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('attendance/student-device');
            return;
        }

        $mode = trim((string) ($_POST['sync_mode'] ?? 'today'));
        $tz = new DateTimeZone('Asia/Colombo');
        $today = new DateTimeImmutable('now', $tz);

        if ($mode === 'range') {
            $from = trim((string) ($_POST['date_from'] ?? ''));
            $to = trim((string) ($_POST['date_to'] ?? ''));
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
                $_SESSION['flash_error'] = 'Invalid date range.';
                $this->redirect('attendance/student-device');
                return;
            }
            $start = new DateTimeImmutable($from . ' 00:00:00', $tz);
            $end = new DateTimeImmutable($to . ' 23:59:59', $tz);
        } elseif ($mode === 'full') {
            $start = new DateTimeImmutable('2026-01-01 00:00:00', $tz);
            $end = $today->setTime(23, 59, 59);
        } else {
            $start = $today->setTime(0, 0, 0);
            $end = $today->setTime(23, 59, 59);
        }

        $svc = $this->syncService();
        $summary = $svc->syncRange(
            $start,
            $end,
            (int) $_SESSION['user_id'],
            (string) ($_SESSION['user_name'] ?? '')
        );
        $_SESSION['student_att_sync_summary'] = $summary;
        if ($summary['ok']) {
            $_SESSION['flash_success'] = 'Synchronization Completed';
        } else {
            $_SESSION['flash_error'] = $summary['message'] ?: 'Synchronization failed.';
        }
        $this->redirect('attendance/student-device');
    }

    public function connectionTest() {
        if (!$this->requireAccess()) {
            return;
        }
        $svc = $this->syncService();
        $result = $svc->machine()->testConnection();
        $_SESSION['student_att_connection'] = [
            'host' => $svc->machine()->getHost(),
            'ok' => !empty($result['ok']),
            'message' => (string) ($result['message'] ?? ''),
            'tested_at' => date('Y-m-d H:i:s'),
            'device_info' => $result['device_info'] ?? null,
        ];
        if (!empty($result['ok'])) {
            $_SESSION['flash_success'] = 'Machine ' . $svc->machine()->getHost() . ': CONNECTED';
        } else {
            $_SESSION['flash_error'] = $result['message'] ?? 'DISCONNECTED';
        }
        $this->redirect('attendance/student-device');
    }

    public function logs() {
        if (!$this->requireAccess()) {
            return;
        }
        $svc = $this->syncService();
        return $this->view('attendance/student_device/logs', [
            'title' => 'Student Attendance Sync Logs',
            'page' => 'student-device-attendance-logs',
            'urls' => $this->urls(),
            'logs' => $svc->logModel()->recent(100),
        ]);
    }

    public function exportExcel() {
        if (!$this->requireAccess()) {
            return;
        }
        require_once BASE_PATH . '/vendor/autoload.php';
        $svc = $this->syncService();
        $filters = $this->filtersFromRequest();
        $rows = $svc->attendanceModel()->exportDailyGrouped($filters);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(
            ['Student ID', 'Employee No', 'Student Name', 'Date', 'In', 'Out', 'Others', 'Machine ID'],
            null,
            'A1'
        );
        $r = 2;
        foreach ($rows as $row) {
            $sheet->fromArray([
                $row['student_id'] ?? '',
                $row['employee_no'] ?? '',
                $row['student_name'] ?? '',
                $row['attendance_date'] ?? '',
                $row['time_in'] ?? '',
                $row['time_out'] ?? '',
                $row['time_others'] ?? '',
                $row['machine_id'] ?? '',
            ], null, 'A' . $r);
            $r++;
        }
        $filename = 'student_attendance_' . date('Y-m-d') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function exportCsv() {
        if (!$this->requireAccess()) {
            return;
        }
        $svc = $this->syncService();
        $filters = $this->filtersFromRequest();
        $rows = $svc->attendanceModel()->exportDailyGrouped($filters);
        $filename = 'student_attendance_' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($out, ['Student ID', 'Employee No', 'Student Name', 'Date', 'In', 'Out', 'Others', 'Machine ID']);
        foreach ($rows as $row) {
            fputcsv($out, [
                $row['student_id'] ?? '',
                $row['employee_no'] ?? '',
                $row['student_name'] ?? '',
                $row['attendance_date'] ?? '',
                $row['time_in'] ?? '',
                $row['time_out'] ?? '',
                $row['time_others'] ?? '',
                $row['machine_id'] ?? '',
            ]);
        }
        fclose($out);
        exit;
    }
}
