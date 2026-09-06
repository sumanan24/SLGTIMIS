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
            'face_photo' => $root . '/users/face-photo',
            'sync' => $root . '/sync',
            'search' => $root . '/events',
            'export_excel' => $root . '/export/excel',
            'export_csv' => $root . '/export/csv',
            'export_month_excel' => $root . '/export/month-excel',
            'fingerprint_import' => $root . '/fingerprint-import',
            'export_fingerprint_import' => $root . '/export/fingerprint-import',
            'test' => $root . '/machine/test',
            'refresh_users' => $root . '/machine/refresh-users',
            'save_credentials' => $root . '/machine/save-credentials',
            'logs' => $root . '/logs',
            'devices' => $root . '/devices',
            'warning' => $root . '/warning-letter',
        ];
    }

    /**
     * Write gitignored config/student_attendance_machine.local.php (production secrets).
     *
     * @return array{ok:bool,message:string,path:string}
     */
    private function writeMachineLocalConfig(string $password, string $username = 'admin'): array {
        $path = BASE_PATH . '/config/student_attendance_machine.local.php';
        $dir = dirname($path);
        $existing = [];
        if (is_file($path)) {
            $tmp = require $path;
            if (is_array($tmp)) {
                $existing = $tmp;
            }
        }
        $data = array_merge([
            'host' => '172.16.0.26',
            'username' => 'admin',
            'password' => '',
            'ssl' => false,
            'port' => 0,
            'timeout' => 60,
            'reader_hosts' => ['172.16.0.29', '172.16.0.28', '172.16.0.27'],
            'timezone' => 'Asia/Colombo',
        ], $existing, [
            'username' => $username !== '' ? $username : 'admin',
            'password' => $password,
        ]);

        if (!is_dir($dir)) {
            return ['ok' => false, 'message' => 'config/ directory missing on server.', 'path' => $path];
        }
        if ((is_file($path) && !is_writable($path)) || (!is_file($path) && !is_writable($dir))) {
            return [
                'ok' => false,
                'message' => 'Cannot write config/student_attendance_machine.local.php — fix folder permissions or upload the file via FTP.',
                'path' => $path,
            ];
        }

        $export = var_export($data, true);
        $php = "<?php\n/** Auto-saved machine credentials (gitignored). Do not commit. */\ndeclare(strict_types=1);\n\nreturn {$export};\n";
        $written = @file_put_contents($path, $php, LOCK_EX);
        if ($written === false) {
            return [
                'ok' => false,
                'message' => 'Failed to write local machine config. Upload via FTP instead.',
                'path' => $path,
            ];
        }

        return [
            'ok' => true,
            'message' => 'Machine password saved on this server.',
            'path' => $path,
        ];
    }

    /** Clear cached probe / connection state after credentials change. */
    private function clearMachineSessionCache(): void {
        unset(
            $_SESSION['student_att_device_status'],
            $_SESSION['student_att_connection'],
            $_SESSION['student_att_lockout_until'],
            $_SESSION['student_att_refresh_users'],
            $_SESSION['student_att_sync_summary']
        );
    }

    /**
     * Auto-remove face JPEG cache files under assets/img/machine_faces.
     * Pass null to delete all cache files; otherwise delete files older than $maxAgeSeconds.
     */
    private function purgeMachineFaceCacheFiles(?int $maxAgeSeconds = 300): int {
        $dir = BASE_PATH . '/assets/img/machine_faces';
        if (!is_dir($dir)) {
            return 0;
        }
        $deleted = 0;
        $now = time();
        $entries = @scandir($dir);
        if (!is_array($entries)) {
            return 0;
        }
        foreach ($entries as $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }
            if (!preg_match('/\.(jpe?g)$/i', $name)) {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $name;
            if (!is_file($path)) {
                continue;
            }
            $age = $now - (int) @filemtime($path);
            if ($maxAgeSeconds === null || $age >= $maxAgeSeconds) {
                if (@unlink($path)) {
                    $deleted++;
                }
            }
        }
        return $deleted;
    }

    /** Throttled auto-purge of stale face cache (once per 10 minutes per session). */
    private function autoPurgeStaleCaches(): void {
        $last = (int) ($_SESSION['student_att_cache_purged_at'] ?? 0);
        if ($last > 0 && (time() - $last) < 600) {
            return;
        }
        $_SESSION['student_att_cache_purged_at'] = time();
        // Face JPEGs are short-lived; drop anything older than 5 minutes
        $this->purgeMachineFaceCacheFiles(300);
    }

    /** Clear session + face files + OPcache for machine config (after password save). */
    private function clearAllMachineCaches(): void {
        $this->clearMachineSessionCache();
        $this->purgeMachineFaceCacheFiles(null);
        unset($_SESSION['student_att_cache_purged_at']);

        $paths = [
            BASE_PATH . '/config/student_attendance_machine.php',
            BASE_PATH . '/config/student_attendance_machine.local.php',
            BASE_PATH . '/controllers/StudentDeviceAttendanceController.php',
        ];
        if (function_exists('opcache_invalidate')) {
            foreach ($paths as $p) {
                if (is_file($p)) {
                    @opcache_invalidate($p, true);
                }
            }
        }
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }
        clearstatcache(true);
    }

    private function credentialSyncService(): StudentDeviceCredentialSyncService {
        require_once BASE_PATH . '/core/StudentDeviceCredentialSyncService.php';
        return new StudentDeviceCredentialSyncService($this->syncService()->attendanceModel());
    }

    /**
     * After MAIN enroll: queue + push credentials to reader terminals.
     *
     * @param list<int> $fingerSlots
     */
    private function fanOutCredentialsToReaders(
        string $employeeNo,
        string $name,
        string $studentId,
        array $fingerSlots = [],
        bool $includeFace = false
    ): string {
        try {
            require_once BASE_PATH . '/core/HikvisionIntegration.php';
            if (!HikvisionIntegration::isCurlAvailable()) {
                return ' · Readers: skipped (PHP cURL missing)';
            }
            $r = $this->credentialSyncService()->queueAndSyncEmployee(
                $employeeNo,
                $name,
                $studentId,
                $fingerSlots,
                $includeFace,
                true
            );
            return ' · Readers: ' . ($r['message'] ?? 'queued');
        } catch (Throwable $e) {
            error_log('[StudentDevice fanOut] ' . $e->getMessage());
            return ' · Readers: sync error — ' . $e->getMessage();
        }
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

        $deviceCards = [];
        $userStats = $att->machineUserStatsByHost();
        $passwordOk = false;
        $cfg = [];
        $this->autoPurgeStaleCaches();
        try {
            require_once BASE_PATH . '/core/HikvisionIntegration.php';
            $cfg = require BASE_PATH . '/config/student_attendance_machine.php';
            $passwordOk = !empty($cfg['configured']) && trim((string) ($cfg['password'] ?? '')) !== '';

            // Drop stale probe cache that still says "Password empty" after secrets were fixed
            $cached = $_SESSION['student_att_device_status'] ?? null;
            if ($passwordOk && is_array($cached) && !empty($cached['devices'])) {
                $staleEmpty = false;
                foreach ($cached['devices'] as $p) {
                    $m = strtolower((string) ($p['message'] ?? ''));
                    if (str_contains($m, 'password empty') || str_contains($m, 'set password first')) {
                        $staleEmpty = true;
                        break;
                    }
                }
                if ($staleEmpty) {
                    unset($_SESSION['student_att_device_status'], $_SESSION['student_att_lockout_until']);
                    $cached = null;
                }
            }

            // Don't show stale DISCONNECTED banner when password was never set
            if (!$passwordOk) {
                unset($_SESSION['student_att_connection']);
            }

            $probes = (is_array($cached) && !empty($cached['devices'])) ? $cached['devices'] : [];
            $byHost = [];
            foreach ($probes as $p) {
                $h = (string) ($p['host'] ?? '');
                if ($h !== '') {
                    $byHost[$h] = $p;
                }
            }
            foreach (($cfg['devices'] ?? []) as $d) {
                $host = (string) ($d['host'] ?? '');
                if ($host === '') {
                    continue;
                }
                $p = $byHost[$host] ?? null;
                $st = $userStats[$host] ?? ['users' => 0, 'last_synced' => null];
                $online = null;
                $status = '';
                $reason = '';
                if (!$passwordOk) {
                    $message = 'Password empty on this server — set HIKVISION_PASS / STUDENT_HIKVISION_PASS in .env or Save password on Device page';
                    $status = 'invalid_config';
                    $reason = 'Invalid configuration';
                } elseif (is_array($p)) {
                    $online = !empty($p['online']);
                    $status = (string) ($p['status'] ?? ($online ? 'online' : 'offline'));
                    $reason = (string) ($p['reason'] ?? '');
                    $message = (string) ($p['message'] ?? ($online ? 'OK' : 'Offline'));
                } else {
                    $message = 'Password OK — click Test all (LAN check, no Internet)';
                    $status = '';
                }
                $deviceCards[] = [
                    'host' => $host,
                    'role' => (string) ($d['role'] ?? ''),
                    'label' => (string) ($d['label'] ?? $host),
                    'online' => $online,
                    'status' => $status,
                    'reason' => $reason,
                    'message' => $message,
                    'tcp_ok' => !empty($p['tcp_ok']),
                    'http_ok' => !empty($p['http_ok']),
                    'auth_ok' => !empty($p['auth_ok']),
                    'model' => (string) ($p['model'] ?? ''),
                    'users' => (int) ($st['users'] ?? 0),
                    'last_synced' => $st['last_synced'] ?? null,
                ];
            }
        } catch (Throwable $e) {
            error_log('[StudentDevice index devices] ' . $e->getMessage());
        }

        $machineCfg = is_array($cfg) ? $cfg : [];
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
            'deviceCards' => $deviceCards,
            'refreshUsersSummary' => $_SESSION['student_att_refresh_users'] ?? null,
            'passwordConfigured' => $passwordOk,
            'machineUsername' => (string) ($cfg['username'] ?? 'admin'),
            'sisLanIp' => (string) ($machineCfg['sis_lan_ip'] ?? (defined('SIS_LAN_IP') ? SIS_LAN_IP : '172.16.1.245')),
            'sisPublicHost' => (string) ($machineCfg['sis_public_host'] ?? (defined('SIS_PUBLIC_HOST') ? SIS_PUBLIC_HOST : 'sis.slgti.ac.lk')),
            'sisLanUrl' => (string) ($machineCfg['sis_lan_url'] ?? (defined('SIS_LAN_URL') ? SIS_LAN_URL : 'http://172.16.1.245')),
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
        $courseId = trim((string) $this->get('course_id', ''));
        $academicYear = trim((string) $this->get('academic_year', ''));
        $groupId = trim((string) $this->get('group_id', ''));
        $studentId = trim((string) $this->get('student_id', ''));
        $statusFilter = trim((string) $this->get('status', 'flagged'));
        if (!in_array($statusFilter, ['flagged', 'low', 'ok', 'all'], true)) {
            $statusFilter = 'flagged';
        }
        $run = (string) $this->get('run', '') === '1'
            || $departmentId !== ''
            || $courseId !== ''
            || $academicYear !== ''
            || $groupId !== ''
            || $studentId !== '';

        $departmentModel = $this->model('DepartmentModel');
        $courseModel = $this->model('CourseModel');
        $studentModel = $this->model('StudentModel');
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

        $academicYears = $studentModel->getAcademicYears();
        $courses = [];
        if ($departmentId !== '') {
            $courses = $courseModel->getCoursesWithDepartment(['department_id' => $departmentId]);
        }

        $groupDept = $departmentId !== '' ? $departmentId : $ctx['department_scope'];
        $groups = $groupModel->getAllWithDetails(
            $groupDept !== null && $groupDept !== '' ? $groupDept : null,
            $courseId !== '' ? $courseId : null
        );

        if ($run) {
            $dashboard = $att->buildSaoDashboard(
                $reportMonth,
                $departmentId,
                $courseId,
                $academicYear,
                $groupId,
                $studentId,
                $statusFilter,
                3
            );
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
        if ($run && ($departmentId !== '' || $courseId !== '' || $academicYear !== '' || $groupId !== '')) {
            $studentsForFilter = $att->listActiveStudentsForReport(
                $departmentId,
                $courseId,
                $academicYear,
                $groupId,
                ''
            );
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
            'courseId' => $courseId,
            'academicYear' => $academicYear,
            'groupId' => $groupId,
            'studentId' => $studentId,
            'statusFilter' => $statusFilter,
            'departments' => $departments,
            'courses' => $courses,
            'academicYears' => $academicYears,
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
        $model = $svc->attendanceModel();
        $search = trim((string) $this->get('q', $this->post('q', '')));
        $departmentId = trim((string) $this->get('department_id', $this->post('department_id', '')));
        $courseId = trim((string) $this->get('course_id', $this->post('course_id', '')));
        $academicYear = trim((string) $this->get('academic_year', $this->post('academic_year', '')));
        $courseMode = trim((string) $this->get('course_mode', $this->post('course_mode', '')));
        if (!in_array(strtolower($courseMode), ['full', 'part', 'full time', 'part time'], true)
            && !in_array($courseMode, ['Full', 'Part'], true)) {
            $courseMode = '';
        }
        $filters = [
            'department_id' => $departmentId,
            'course_id' => $courseId,
            'academic_year' => $academicYear,
            'course_mode' => $courseMode,
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleUsersPost($model, $svc, $search, $filters);
            return;
        }

        $cards = $model->listMachineUsersForEnroll(
            $search,
            300,
            $departmentId,
            $courseId,
            $academicYear,
            $courseMode
        );
        // Live finger details from machine (fixes stale "0 fingers" after successful enroll).
        $curlMissing = false;
        $machineConfigured = true;
        $machineConfigHint = '';
        require_once BASE_PATH . '/core/HikvisionIntegration.php';
        if (!HikvisionIntegration::isCurlAvailable()) {
            $curlMissing = true;
        } else {
            $this->enrichCardsWithLiveFingerDetails($cards, $svc, $model, $search);
        }
        $machineCfg = require BASE_PATH . '/config/student_attendance_machine.php';
        $machineConfigured = !empty($machineCfg['configured']);
        if (!$machineConfigured) {
            $envOk = !empty($machineCfg['env_file_present']);
            $localOk = !empty($machineCfg['local_file_present']);
            $machineConfigHint = 'Hikvision password is empty on this server. '
                . ($envOk
                    ? 'Edit .env and set STUDENT_HIKVISION_PASS to the MAIN terminal web password. '
                    : 'Create .env (copy from .env.example) with STUDENT_HIKVISION_PASS. ')
                . ($localOk
                    ? 'Or set password in config/student_attendance_machine.local.php. '
                    : 'Or copy config/student_attendance_machine.local.php.example to config/student_attendance_machine.local.php and set password. ');
        }

        require_once BASE_PATH . '/models/StudentModel.php';
        $studentModel = new StudentModel();
        foreach ($cards as &$card) {
            $card['profile_photo_url'] = $studentModel->getProfileImagePath($card) ?: '';
        }
        unset($card);

        $departmentModel = $this->model('DepartmentModel');
        $courseModel = $this->model('CourseModel');
        $departments = $departmentModel->getAll();
        $courses = [];
        if ($departmentId !== '') {
            $courses = $courseModel->getCoursesWithDepartment(['department_id' => $departmentId]);
        }
        $academicYears = $studentModel->getAcademicYears();

        return $this->view('attendance/student_device/users', [
            'title' => 'Student machine users',
            'page' => 'student-device-attendance-users',
            'urls' => $this->urls(),
            'canManageDevice' => true,
            'search' => $search,
            'cards' => $cards,
            'machineHost' => $svc->machine()->getHost(),
            'departments' => $departments,
            'courses' => $courses,
            'academicYears' => $academicYears,
            'departmentId' => $departmentId,
            'courseId' => $courseId,
            'academicYear' => $academicYear,
            'courseMode' => $courseMode,
            'curlMissing' => $curlMissing,
            'machineConfigured' => $machineConfigured,
            'machineConfigHint' => $machineConfigHint,
            'adminLockoutUntil' => (int) ($_SESSION['student_att_lockout_until'] ?? 0),
        ]);
    }

    /**
     * Hikvision multi-device credential synchronization dashboard.
     */
    public function devices() {
        if (!$this->requireAccess()) {
            return;
        }

        $this->autoPurgeStaleCaches();
        require_once BASE_PATH . '/core/HikvisionIntegration.php';
        $cred = $this->credentialSyncService();
        $model = $cred->attendanceModel();
        $tabDefault = 'overview';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = trim((string) $this->post('action', ''));
            $employeeNo = trim((string) $this->post('employee_no', ''));
            $deviceHost = trim((string) $this->post('device_host', ''));
            $returnTab = trim((string) $this->post('return_tab', $tabDefault));
            if (!in_array($returnTab, ['overview', 'presence', 'tools', 'queue', 'logs'], true)) {
                $returnTab = $tabDefault;
            }

            $go = static function (string $tab, array $q = []) use ($returnTab): void {
                $tab = $tab !== '' ? $tab : $returnTab;
                $q = array_merge(['tab' => $tab], $q);
                $qs = http_build_query(array_filter($q, static fn ($v) => $v !== null && $v !== ''));
                header('Location: ' . rtrim(APP_URL, '/') . '/attendance/student-device/devices' . ($qs !== '' ? '?' . $qs : ''));
                exit;
            };

            try {
                if (!HikvisionIntegration::isCurlAvailable()) {
                    $_SESSION['flash_error'] = 'PHP cURL is not installed on this server. Install php-curl before device sync.';
                    $go($returnTab);
                    return;
                }

                if ($action === 'test_all' || $action === 'test_one') {
                    $lockoutUntil = (int) ($_SESSION['student_att_lockout_until'] ?? 0);
                    if ($lockoutUntil > time() && (string) $this->post('force', '') !== '1') {
                        $_SESSION['flash_error'] = 'Admin lock cooldown until ' . date('H:i', $lockoutUntil)
                            . '. Reboot terminals and confirm browser login first. Do not keep testing.';
                        $go('overview');
                        return;
                    }

                    if ($action === 'test_one' && $deviceHost !== '') {
                        $statuses = $cred->probeDeviceStatuses($deviceHost);
                        // Merge into existing cache so other cards keep last known status
                        $cached = $_SESSION['student_att_device_status']['devices'] ?? [];
                        $byHost = [];
                        if (is_array($cached)) {
                            foreach ($cached as $c) {
                                $h = (string) ($c['host'] ?? '');
                                if ($h !== '') {
                                    $byHost[$h] = $c;
                                }
                            }
                        }
                        foreach ($statuses as $s) {
                            $h = (string) ($s['host'] ?? '');
                            if ($h !== '') {
                                $byHost[$h] = $s;
                            }
                        }
                        $statuses = array_values($byHost);
                    } else {
                        $statuses = $cred->probeDeviceStatuses();
                    }

                    $_SESSION['student_att_device_status'] = [
                        'devices' => $statuses,
                        'tested_at' => date('Y-m-d H:i:s'),
                    ];
                    $this->rememberDeviceLockout($statuses);

                    $lines = [];
                    $online = 0;
                    foreach ($statuses as $s) {
                        if ($action === 'test_one' && $deviceHost !== '' && ($s['host'] ?? '') !== $deviceHost) {
                            continue;
                        }
                        $st = strtoupper((string) ($s['status'] ?? (!empty($s['online']) ? 'online' : 'offline')));
                        if (!empty($s['online'])) {
                            $online++;
                        }
                        $lines[] = ($s['host'] ?? '') . ': ' . $st
                            . (!empty($s['reason']) && $st !== 'ONLINE' ? ' (' . $s['reason'] . ')' : '');
                    }
                    $msg = ($action === 'test_all'
                        ? "Online {$online}/" . count($cred->devices()) . ' — '
                        : '') . implode(' · ', $lines);
                    $anyAuth = false;
                    foreach ($statuses as $s) {
                        if (($s['status'] ?? '') === 'auth_error') {
                            $anyAuth = true;
                            break;
                        }
                    }
                    if ($online > 0 && !$anyAuth) {
                        $_SESSION['flash_success'] = $msg;
                    } elseif ($online > 0) {
                        $_SESSION['flash_success'] = $msg;
                    } else {
                        $_SESSION['flash_error'] = $msg;
                    }
                    $go('overview');
                    return;
                }

                if ($action === 'sync_user') {
                    if ($employeeNo === '') {
                        $_SESSION['flash_error'] = 'Employee No is required.';
                        $go('tools');
                        return;
                    }
                    $st = $model->findActiveStudentByEmployeeNo($employeeNo);
                    $name = $employeeNo;
                    $sid = '';
                    if ($st) {
                        $ini = trim((string) ($st['student_ininame'] ?? ''));
                        $full = trim((string) ($st['student_fullname'] ?? ''));
                        $name = $ini !== '' ? $ini : ($full !== '' ? $full : $employeeNo);
                        $sid = (string) ($st['student_id'] ?? '');
                    }
                    $r = $cred->queueAndSyncEmployee($employeeNo, $name, $sid, [], true, true);
                    $_SESSION['flash_success'] = $r['message'] ?? 'Sync queued.';
                    $go($returnTab === 'presence' ? 'presence' : 'queue', [
                        'pf' => (string) $this->post('pf', ''),
                        'q' => (string) $this->post('q', ''),
                    ]);
                    return;
                }

                if ($action === 'sync_all') {
                    @set_time_limit(90);
                    $r = $cred->queueAndSyncAllUsers(false, 500);
                    $proc = $cred->processPendingJobs(10, '', 40);
                    $_SESSION['flash_success'] = ($r['message'] ?? 'Queued.')
                        . ' ' . ($proc['message'] ?? '');
                    $go('queue');
                    return;
                }

                if ($action === 'process_pending') {
                    @set_time_limit(90);
                    $r = $cred->processPendingJobs(15, '', 50);
                    $_SESSION['flash_success'] = $r['message'] ?? 'Processed pending jobs.';
                    $go('queue');
                    return;
                }

                if ($action === 'retry_failed') {
                    @set_time_limit(90);
                    $n = $model->requeueFailedCredentialJobs();
                    $r = $cred->processPendingJobs(15, '', 50);
                    $_SESSION['flash_success'] = "Requeued {$n} failed job(s). " . ($r['message'] ?? '');
                    $go('queue');
                    return;
                }

                if ($action === 'refresh_presence') {
                    @set_time_limit(100);
                    $result = $cred->refreshUserDirectoriesFromAllDevices(75);
                    if (!empty($result['ok'])) {
                        $_SESSION['flash_success'] = $result['message'] ?? 'Person list refreshed.';
                    } else {
                        $_SESSION['flash_error'] = $result['message'] ?? 'Could not refresh person list.';
                    }
                    if ($returnTab === 'tools') {
                        $go('tools');
                    } else {
                        $go('presence', ['pf' => 'missing']);
                    }
                    return;
                }

                if ($action === 'delete_user_readers' || $action === 'delete_user_one') {
                    @set_time_limit(90);
                    if ($employeeNo === '' || !preg_match('/^[A-Za-z0-9_\\-\\/]{2,40}$/', $employeeNo)) {
                        $_SESSION['flash_error'] = 'Valid Employee No is required.';
                        $go('tools');
                        return;
                    }
                    $includeMain = (string) $this->post('include_main', '') === '1';
                    $onlyHost = $action === 'delete_user_one' ? $deviceHost : '';
                    if ($action === 'delete_user_one' && $onlyHost === '') {
                        $_SESSION['flash_error'] = 'Select a reader to delete from.';
                        $go('tools');
                        return;
                    }
                    $mainHost = (string) ($cred->mainDevice()['host'] ?? '');
                    if ($onlyHost !== '' && $onlyHost === $mainHost && !$includeMain) {
                        $_SESSION['flash_error'] = 'To delete from MAIN, check “Also delete from MAIN”.';
                        $go('tools');
                        return;
                    }
                    $r = $cred->deletePersonFromDevices($employeeNo, $includeMain, $onlyHost);
                    if (!empty($r['ok'])) {
                        $_SESSION['flash_success'] = $r['message'] ?? 'Deleted.';
                    } else {
                        $_SESSION['flash_error'] = $r['message'] ?? 'Delete failed.';
                    }
                    $go('tools', [
                        'tq' => (string) $this->post('tq', ''),
                        'tpage' => (string) $this->post('tpage', ''),
                    ]);
                    return;
                }

                if ($action === 'delete_users_selected') {
                    @set_time_limit(180);
                    $rawList = $_POST['employee_nos'] ?? [];
                    if (!is_array($rawList)) {
                        $rawList = [];
                    }
                    $selected = [];
                    foreach ($rawList as $eno) {
                        $eno = trim((string) $eno);
                        if ($eno !== '' && preg_match('/^[A-Za-z0-9_\\-\\/]{2,40}$/', $eno)) {
                            $selected[$eno] = $eno;
                        }
                    }
                    $selected = array_values($selected);
                    if ($selected === []) {
                        $_SESSION['flash_error'] = 'Select at least one person to delete.';
                        $go('tools');
                        return;
                    }
                    $includeMain = (string) $this->post('include_main', '') === '1';
                    $onlyHost = trim((string) $this->post('device_host', ''));
                    $okN = 0;
                    $failN = 0;
                    $msgs = [];
                    foreach ($selected as $eno) {
                        $r = $cred->deletePersonFromDevices($eno, $includeMain, $onlyHost);
                        if (!empty($r['ok'])) {
                            $okN++;
                        } else {
                            $failN++;
                            $msgs[] = $eno . ': ' . ($r['message'] ?? 'failed');
                        }
                    }
                    $summary = "Deleted {$okN}/" . count($selected) . ' selected person(s) from '
                        . ($onlyHost !== '' ? $onlyHost : ($includeMain ? 'MAIN + readers' : 'all readers')) . '.';
                    if ($failN > 0) {
                        $_SESSION['flash_error'] = $summary . ' ' . implode(' · ', array_slice($msgs, 0, 5));
                    } else {
                        $_SESSION['flash_success'] = $summary;
                    }
                    $go('tools', [
                        'tq' => (string) $this->post('tq', ''),
                        'tpage' => (string) $this->post('tpage', ''),
                    ]);
                    return;
                }

                $_SESSION['flash_error'] = 'Unknown action.';
            } catch (Throwable $e) {
                error_log('[StudentDevice devices] ' . $e->getMessage());
                $_SESSION['flash_error'] = $e->getMessage();
            }
            $go($returnTab);
            return;
        }

        $tab = strtolower(trim((string) $this->get('tab', $tabDefault)));
        if (!in_array($tab, ['overview', 'presence', 'tools', 'queue', 'logs'], true)) {
            $tab = $tabDefault;
        }

        $presenceFilter = strtolower(trim((string) $this->get('pf', 'missing')));
        if (!in_array($presenceFilter, ['all', 'missing', 'complete'], true)) {
            $presenceFilter = 'missing';
        }
        $presenceQ = trim((string) $this->get('q', ''));
        $presencePage = max(1, (int) $this->get('page', 1));
        $presencePerPage = 20;

        $queueStatus = strtolower(trim((string) $this->get('qs', 'all')));
        if (!in_array($queueStatus, ['all', 'pending', 'syncing', 'failed', 'success'], true)) {
            $queueStatus = 'all';
        }

        $logsPage = max(1, (int) $this->get('lpage', 1));
        $logsPerPage = 25;

        $cfg = require BASE_PATH . '/config/student_attendance_machine.php';
        $cfgDevices = is_array($cfg['devices'] ?? null) ? $cfg['devices'] : [];
        $hostsOrdered = [];
        $deviceMeta = [];
        foreach ($cfgDevices as $d) {
            if (!is_array($d)) {
                continue;
            }
            $host = trim((string) ($d['host'] ?? ''));
            if ($host === '') {
                continue;
            }
            $hostsOrdered[] = $host;
            $deviceMeta[$host] = [
                'label' => (string) ($d['label'] ?? $host),
                'role' => (string) ($d['role'] ?? ''),
            ];
        }

        $forceProbe = (string) $this->get('probe', '') === '1';
        $cached = $_SESSION['student_att_device_status'] ?? null;
        // Never auto-probe on page load — only when user clicks Test (probe=1) or connectionTest.
        // Auto-probe during admin lockout makes lockouts worse.
        $lockoutUntil = (int) ($_SESSION['student_att_lockout_until'] ?? 0);
        $inLockout = $lockoutUntil > time();
        $shouldProbe = HikvisionIntegration::isCurlAvailable()
            && $forceProbe
            && !$inLockout;

        if ($shouldProbe) {
            $deviceStatuses = $cred->probeDeviceStatuses();
            $_SESSION['student_att_device_status'] = [
                'devices' => $deviceStatuses,
                'tested_at' => date('Y-m-d H:i:s'),
            ];
            $cached = $_SESSION['student_att_device_status'];
            $this->rememberDeviceLockout($deviceStatuses);
        } elseif (is_array($cached) && !empty($cached['devices'])) {
            $deviceStatuses = $cached['devices'];
        } else {
            $deviceStatuses = [];
            foreach ($cfgDevices as $d) {
                if (!is_array($d)) {
                    continue;
                }
                $deviceStatuses[] = [
                    'host' => (string) ($d['host'] ?? ''),
                    'role' => (string) ($d['role'] ?? ''),
                    'label' => (string) ($d['label'] ?? ''),
                    'online' => null,
                    'message' => $inLockout
                        ? ('Admin lock cooldown — wait until ' . date('H:i:s', $lockoutUntil) . ', or reboot terminals')
                        : 'Not tested — click Test',
                ];
            }
        }

        $stats = $model->credentialSyncStatsByDevice();
        $userStats = $model->machineUserStatsByHost();
        $rows = [];
        $onlineCount = 0;
        $pendingTotal = 0;
        $failedTotal = 0;
        foreach ($deviceStatuses as $ds) {
            $host = (string) ($ds['host'] ?? '');
            $st = $stats[$host] ?? [
                'pending' => 0,
                'syncing' => 0,
                'success' => 0,
                'failed' => 0,
                'last_sync' => null,
            ];
            $us = $userStats[$host] ?? ['users' => 0, 'last_synced' => null];
            $rows[] = array_merge($ds, $st, [
                'users_on_device' => (int) ($us['users'] ?? 0),
                'users_last_synced' => $us['last_synced'] ?? null,
            ]);
            if (!empty($ds['online'])) {
                $onlineCount++;
            }
            $pendingTotal += (int) ($st['pending'] ?? 0) + (int) ($st['syncing'] ?? 0);
            $failedTotal += (int) ($st['failed'] ?? 0);
            if ($host !== '' && !isset($deviceMeta[$host])) {
                $hostsOrdered[] = $host;
                $deviceMeta[$host] = [
                    'label' => (string) ($ds['label'] ?? $host),
                    'role' => (string) ($ds['role'] ?? ''),
                ];
            }
        }
        $hostsOrdered = array_values(array_unique($hostsOrdered));

        $allPresence = $hostsOrdered !== []
            ? $model->personPresenceByHosts($hostsOrdered, 3000)
            : [];
        $presenceSummary = ['total' => count($allPresence), 'missing' => 0, 'complete' => 0];
        foreach ($allPresence as $pr) {
            if ((int) ($pr['missing_count'] ?? 0) > 0) {
                $presenceSummary['missing']++;
            } else {
                $presenceSummary['complete']++;
            }
        }

        $filteredPresence = $allPresence;
        if ($presenceFilter === 'missing') {
            $filteredPresence = array_values(array_filter(
                $allPresence,
                static fn (array $r): bool => (int) ($r['missing_count'] ?? 0) > 0
            ));
        } elseif ($presenceFilter === 'complete') {
            $filteredPresence = array_values(array_filter(
                $allPresence,
                static fn (array $r): bool => (int) ($r['missing_count'] ?? 0) === 0
            ));
        }
        if ($presenceQ !== '') {
            $qLower = mb_strtolower($presenceQ);
            $filteredPresence = array_values(array_filter(
                $filteredPresence,
                static function (array $r) use ($qLower): bool {
                    return str_contains(mb_strtolower((string) ($r['employee_no'] ?? '')), $qLower)
                        || str_contains(mb_strtolower((string) ($r['name'] ?? '')), $qLower);
                }
            ));
        }
        $presenceTotal = count($filteredPresence);
        $presencePages = max(1, (int) ceil($presenceTotal / $presencePerPage));
        if ($presencePage > $presencePages) {
            $presencePage = $presencePages;
        }
        $presenceRows = array_slice(
            $filteredPresence,
            ($presencePage - 1) * $presencePerPage,
            $presencePerPage
        );

        // Tools tab: synced persons across 4 machines (select → delete)
        $toolsQ = trim((string) $this->get('tq', ''));
        $toolsPage = max(1, (int) $this->get('tpage', 1));
        $toolsPerPage = 25;
        $toolsScope = strtolower(trim((string) $this->get('ts', 'synced')));
        if (!in_array($toolsScope, ['synced', 'readers', 'all'], true)) {
            $toolsScope = 'synced';
        }
        $toolsFiltered = $allPresence;
        if ($toolsScope === 'synced') {
            // Present on at least one machine
            $toolsFiltered = array_values(array_filter(
                $allPresence,
                static fn (array $r): bool => (int) ($r['present_count'] ?? 0) > 0
            ));
        } elseif ($toolsScope === 'readers') {
            // Present on at least one reader (not only MAIN)
            $readerHosts = [];
            foreach ($deviceMeta as $h => $meta) {
                if (($meta['role'] ?? '') === 'reader') {
                    $readerHosts[] = $h;
                }
            }
            $toolsFiltered = array_values(array_filter(
                $allPresence,
                static function (array $r) use ($readerHosts): bool {
                    foreach ($readerHosts as $rh) {
                        if (!empty($r['devices'][$rh]['present'])) {
                            return true;
                        }
                    }
                    return false;
                }
            ));
        }
        if ($toolsQ !== '') {
            $tqLower = mb_strtolower($toolsQ);
            $toolsFiltered = array_values(array_filter(
                $toolsFiltered,
                static function (array $r) use ($tqLower): bool {
                    return str_contains(mb_strtolower((string) ($r['employee_no'] ?? '')), $tqLower)
                        || str_contains(mb_strtolower((string) ($r['name'] ?? '')), $tqLower);
                }
            ));
        }
        // Prefer people on more machines first (already synced)
        usort($toolsFiltered, static function (array $a, array $b): int {
            $c = ((int) ($b['present_count'] ?? 0)) <=> ((int) ($a['present_count'] ?? 0));
            if ($c !== 0) {
                return $c;
            }
            return strcmp((string) ($a['employee_no'] ?? ''), (string) ($b['employee_no'] ?? ''));
        });
        $toolsTotal = count($toolsFiltered);
        $toolsPages = max(1, (int) ceil($toolsTotal / $toolsPerPage));
        if ($toolsPage > $toolsPages) {
            $toolsPage = $toolsPages;
        }
        $toolsRows = array_slice(
            $toolsFiltered,
            ($toolsPage - 1) * $toolsPerPage,
            $toolsPerPage
        );

        $statusList = $queueStatus === 'all' ? [] : [$queueStatus];
        $jobs = $model->listCredentialSyncJobs($statusList, 1000);
        $queueTotal = count($jobs);

        $allLogs = $model->listCredentialSyncLogs(500);
        $logsTotal = count($allLogs);
        $logsPages = max(1, (int) ceil($logsTotal / $logsPerPage));
        if ($logsPage > $logsPages) {
            $logsPage = $logsPages;
        }
        $logs = array_slice($allLogs, ($logsPage - 1) * $logsPerPage, $logsPerPage);

        return $this->view('attendance/student_device/devices', [
            'title' => 'Hikvision device sync',
            'page' => 'student-device-attendance-devices',
            'urls' => $this->urls(),
            'canManageDevice' => true,
            'deviceTab' => $tab,
            'deviceRows' => $rows,
            'jobs' => $jobs,
            'logs' => $logs,
            'curlMissing' => !HikvisionIntegration::isCurlAvailable(),
            'mainHost' => (string) ($cred->mainDevice()['host'] ?? ''),
            'presenceRows' => $presenceRows,
            'presenceHosts' => $hostsOrdered,
            'presenceDeviceMeta' => $deviceMeta,
            'presenceFilter' => $presenceFilter,
            'presenceSummary' => $presenceSummary,
            'presenceQ' => $presenceQ,
            'presencePage' => $presencePage,
            'presencePages' => $presencePages,
            'presenceTotal' => $presenceTotal,
            'presencePerPage' => $presencePerPage,
            'toolsRows' => $toolsRows,
            'toolsQ' => $toolsQ,
            'toolsPage' => $toolsPage,
            'toolsPages' => $toolsPages,
            'toolsTotal' => $toolsTotal,
            'toolsScope' => $toolsScope,
            'queueStatus' => $queueStatus,
            'queueTotal' => $queueTotal,
            'logsPage' => $logsPage,
            'logsPages' => $logsPages,
            'logsTotal' => $logsTotal,
            'kpi' => [
                'devices' => count($rows),
                'online' => $onlineCount,
                'missing' => (int) $presenceSummary['missing'],
                'pending' => $pendingTotal,
                'failed' => $failedTotal,
            ],
            'testedAt' => is_array($cached) ? (string) ($cached['tested_at'] ?? '') : '',
        ]);
    }

    /**
     * Stream enrolled face JPEG from the fingerprint terminal (read machine photo).
     */
    public function facePhoto() {
        if (!$this->requireAccess()) {
            return;
        }
        $employeeNo = trim((string) $this->get('employee_no', ''));
        if ($employeeNo === '' || !preg_match('/^[A-Za-z0-9_\\-\\/]{2,40}$/', $employeeNo)) {
            http_response_code(400);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'Invalid employee number.';
            exit;
        }

        $cacheFile = $this->machineFaceCachePath($employeeNo);
        $refresh = (string) $this->get('refresh', '') === '1';
        $this->autoPurgeStaleCaches();

        if (!$refresh && is_file($cacheFile) && (time() - (int) filemtime($cacheFile)) < 300) {
            header('Content-Type: image/jpeg');
            header('Cache-Control: private, max-age=120');
            readfile($cacheFile);
            exit;
        }

        try {
            $hik = $this->studentHikvision();
            $photo = $hik->getFacePhoto($employeeNo);
            if (empty($photo['ok']) || empty($photo['jpeg'])) {
                http_response_code(404);
                header('Content-Type: text/plain; charset=UTF-8');
                echo $photo['message'] ?? 'Face photo not found on machine.';
                exit;
            }
            $this->cacheMachineFaceJpeg($employeeNo, $photo['jpeg']);
            header('Content-Type: image/jpeg');
            header('Cache-Control: private, max-age=120');
            echo $photo['jpeg'];
            exit;
        } catch (Throwable $e) {
            error_log('[StudentDevice facePhoto] ' . $e->getMessage());
            http_response_code(502);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'Could not read face photo from machine.';
            exit;
        }
    }

    private function machineFaceCachePath(string $employeeNo): string {
        $cacheDir = BASE_PATH . '/assets/img/machine_faces';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }
        $safe = preg_replace('/[^A-Za-z0-9_-]+/', '_', trim($employeeNo)) ?: 'face';
        return $cacheDir . '/' . $safe . '.jpg';
    }

    private function cacheMachineFaceJpeg(string $employeeNo, string $jpeg): void {
        if ($jpeg === '' || strncmp($jpeg, "\xFF\xD8\xFF", 3) !== 0) {
            return;
        }
        @file_put_contents($this->machineFaceCachePath($employeeNo), $jpeg);
    }

    private function clearMachineFaceCache(string $employeeNo): void {
        $path = $this->machineFaceCachePath($employeeNo);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    /**
     * Immediately pull Finger 01/02 + Face status from the machine and persist to DB.
     * Retries briefly so device lag after enroll does not leave the UI at 0.
     *
     * @param list<int> $expectSlots Optional finger slots that should appear after a successful enroll
     * @return array{ok: bool, count: int, slots: list<int>, face_count: int, has_face: bool, message: string}
     */
    private function syncEmployeeFingerInfoFromMachine(
        HikvisionIntegration $hik,
        StudentDeviceAttendanceModel $model,
        string $machineId,
        string $employeeNo,
        string $name = '',
        array $expectSlots = []
    ): array {
        $employeeNo = trim($employeeNo);
        if ($employeeNo === '') {
            return [
                'ok' => false,
                'count' => 0,
                'slots' => [],
                'face_count' => 0,
                'has_face' => false,
                'message' => 'Missing employee no',
            ];
        }

        $detail = ['ok' => false, 'count' => 0, 'slots' => []];
        $attempts = 4;
        for ($i = 0; $i < $attempts; $i++) {
            if ($i > 0) {
                usleep(600000);
            }
            $detail = $hik->getFingerPrintDetails($employeeNo);
            if (empty($detail['ok'])) {
                continue;
            }
            $slots = array_values(array_map('intval', $detail['slots'] ?? []));
            $missing = false;
            foreach ($expectSlots as $want) {
                $want = (int) $want;
                if ($want > 0 && !in_array($want, $slots, true)) {
                    $missing = true;
                    break;
                }
            }
            if (!$missing || $expectSlots === []) {
                break;
            }
        }

        $slots = array_values(array_map('intval', $detail['slots'] ?? []));
        foreach ($expectSlots as $want) {
            $want = (int) $want;
            if ($want > 0 && !in_array($want, $slots, true)) {
                $slots[] = $want;
            }
        }
        $slots = array_values(array_unique(array_filter($slots, static fn ($n) => $n > 0)));
        sort($slots);
        $count = max((int) ($detail['count'] ?? 0), count($slots));

        $faceCount = 0;
        $liveUser = $hik->searchUsers(5, $employeeNo);
        if (!empty($liveUser['users'][0])) {
            $faceCount = (int) ($liveUser['users'][0]['face_count'] ?? 0);
            if ($name === '' || $name === $employeeNo) {
                $liveName = trim((string) ($liveUser['users'][0]['name'] ?? ''));
                if ($liveName !== '') {
                    $name = $liveName;
                }
            }
        }

        $model->upsertMachineUsers([[
            'employee_no' => $employeeNo,
            'name' => $name !== '' ? $name : $employeeNo,
            'user_type' => 'normal',
            'finger_count' => $count,
            'face_count' => $faceCount,
            'finger_slots' => $slots,
        ]], $machineId !== '' ? $machineId : 'device');

        $slotLabel = $slots === []
            ? 'no fingers'
            : ('Finger ' . implode(', Finger ', array_map(
                static fn ($n) => str_pad((string) $n, 2, '0', STR_PAD_LEFT),
                $slots
            )));
        $faceLabel = $faceCount > 0 ? 'Face enrolled' : 'Face empty';

        return [
            'ok' => !empty($detail['ok']) || !empty($liveUser['ok']),
            'count' => $count,
            'slots' => $slots,
            'face_count' => $faceCount,
            'has_face' => $faceCount > 0,
            'message' => 'Synced · ' . $count . ' finger(s) (' . $slotLabel . ') · ' . $faceLabel,
        ];
    }

    /**
     * Load student profile image as JPEG bytes for FaceDataRecord upload.
     */
    private function loadStudentProfileJpegBytes(string $studentId): ?string {
        $studentId = trim($studentId);
        if ($studentId === '') {
            return null;
        }
        require_once BASE_PATH . '/models/StudentModel.php';
        $studentModel = new StudentModel();
        $student = $studentModel->find($studentId);
        if (!$student || !is_array($student)) {
            return null;
        }
        $rel = trim((string) ($student['student_profile_img'] ?? $student['file_path'] ?? ''));
        if ($rel === '') {
            return null;
        }
        $rel = ltrim($rel, '/');
        if (strpos($rel, 'assets/') === 0) {
            $rel = substr($rel, 7);
        }
        if (strpos($rel, 'img/student_profile/') === 0) {
            $rel = str_replace('img/student_profile/', 'img/Studnet_profile/', $rel);
        }
        if (strpos($rel, 'img/Student_profile/') === 0) {
            $rel = str_replace('img/Student_profile/', 'img/Studnet_profile/', $rel);
        }
        if (strpos($rel, 'img/') !== 0) {
            $rel = 'img/Studnet_profile/' . basename($rel);
        }
        $full = BASE_PATH . '/assets/' . $rel;
        if (!is_file($full)) {
            return null;
        }
        $raw = (string) file_get_contents($full);
        if ($raw === '') {
            return null;
        }
        if (strncmp($raw, "\xFF\xD8\xFF", 3) === 0) {
            return strlen($raw) > 250000 ? $this->compressJpegBytes($raw, 180000) : $raw;
        }
        if (!function_exists('imagecreatefromstring') || !function_exists('imagejpeg')) {
            return null;
        }
        $img = @imagecreatefromstring($raw);
        if ($img === false) {
            return null;
        }
        ob_start();
        imagejpeg($img, null, 82);
        imagedestroy($img);
        $jpeg = (string) ob_get_clean();
        if ($jpeg === '' || strncmp($jpeg, "\xFF\xD8\xFF", 3) !== 0) {
            return null;
        }
        return strlen($jpeg) > 250000 ? $this->compressJpegBytes($jpeg, 180000) : $jpeg;
    }

    private function compressJpegBytes(string $jpeg, int $maxBytes): string {
        if (!function_exists('imagecreatefromstring') || !function_exists('imagejpeg')) {
            return $jpeg;
        }
        $img = @imagecreatefromstring($jpeg);
        if ($img === false) {
            return $jpeg;
        }
        $quality = 75;
        $out = $jpeg;
        while ($quality >= 40) {
            ob_start();
            imagejpeg($img, null, $quality);
            $out = (string) ob_get_clean();
            if (strlen($out) <= $maxBytes) {
                break;
            }
            $quality -= 10;
        }
        imagedestroy($img);
        return $out !== '' ? $out : $jpeg;
    }

    /**
     * Refresh finger_count / Finger 01–02 slots from device for visible cards.
     *
     * @param list<array<string,mixed>> $cards
     */
    private function enrichCardsWithLiveFingerDetails(
        array &$cards,
        StudentDeviceAttendanceSyncService $svc,
        StudentDeviceAttendanceModel $model,
        string $search
    ): void {
        if ($cards === []) {
            return;
        }
        require_once BASE_PATH . '/core/HikvisionIntegration.php';
        if (!HikvisionIntegration::isCurlAvailable()) {
            return;
        }
        $limit = ($search !== '' || count($cards) <= 24) ? count($cards) : min(12, count($cards));
        try {
            $hik = $this->studentHikvision();
        } catch (Throwable $e) {
            return;
        }
        $machineId = $svc->machine()->getHost();
        for ($i = 0; $i < $limit; $i++) {
            $eno = trim((string) ($cards[$i]['employee_no'] ?? ''));
            if ($eno === '') {
                continue;
            }
            $name = (string) ($cards[$i]['student_name'] ?? $cards[$i]['machine_name'] ?? $eno);
            try {
                $synced = $this->syncEmployeeFingerInfoFromMachine($hik, $model, $machineId, $eno, $name, []);
            } catch (Throwable $e) {
                error_log('[StudentDevice enrich] ' . $e->getMessage());
                continue;
            }
            $slots = $synced['slots'];
            $cards[$i]['finger_count'] = $synced['count'];
            $cards[$i]['finger_slots'] = $slots;
            $cards[$i]['has_finger_01'] = in_array(1, $slots, true);
            $cards[$i]['has_finger_02'] = in_array(2, $slots, true);
            $cards[$i]['face_count'] = (int) ($synced['face_count'] ?? 0);
            $cards[$i]['has_face'] = !empty($synced['has_face']);
        }
    }

    private function studentHikvision(): HikvisionIntegration {
        require_once BASE_PATH . '/core/HikvisionIntegration.php';
        return HikvisionIntegration::fromStudentAttendanceConfig();
    }

    /**
     * POST actions: refresh users, add user on device, enroll finger 1 / 2 / both.
     */
    private function handleUsersPost(
        StudentDeviceAttendanceModel $model,
        StudentDeviceAttendanceSyncService $svc,
        string $search,
        array $filters = []
    ): void {
        $action = trim((string) $this->post('action', ''));
        $employeeNo = trim((string) $this->post('employee_no', ''));
        $name = trim((string) $this->post('name', ''));
        $studentId = trim((string) $this->post('student_id', ''));
        $fingerNo = (int) $this->post('finger_no', 1);
        $usersUrl = $this->usersRedirectUrl($search, '', $filters);

        require_once BASE_PATH . '/core/HikvisionIntegration.php';
        if (!HikvisionIntegration::isCurlAvailable()) {
            $_SESSION['flash_error'] = 'PHP cURL is not installed on this server. Install php-curl and restart PHP-FPM/Apache before using machine actions.';
            $this->redirect($usersUrl);
            return;
        }

        if ($action === 'clear_admin_lock') {
            unset($_SESSION['student_att_lockout_until'], $_SESSION['student_att_device_status']);
            $hik = $this->studentHikvision();
            $login = $hik->assertDigestLogin();
            if (!empty($login['ok'])) {
                unset($_SESSION['student_att_lockout_until']);
                $_SESSION['flash_success'] = 'MAIN login OK after reboot. You can enroll fingerprints now — place finger on scanner, then click Finger 01 once.';
            } else {
                $_SESSION['student_att_lockout_until'] = time() + 20 * 60;
                $_SESSION['flash_error'] = 'Still cannot login to MAIN. '
                    . ($login['message'] ?? '')
                    . ' Open http://172.16.0.26 in a browser and confirm admin password, then use Device → Save password.';
            }
            $this->redirect($usersUrl);
            return;
        }

        $lockoutUntil = (int) ($_SESSION['student_att_lockout_until'] ?? 0);
        if (
            $lockoutUntil > time()
            && in_array($action, ['add_finger', 'add_user_and_fingers', 'add_face', 'add_user'], true)
        ) {
            $_SESSION['flash_error'] = 'Admin lock cooldown until ' . date('H:i', $lockoutUntil)
                . '. Reboot MAIN, login at http://172.16.0.26 in browser, then click “Terminal rebooted — clear lock & test” below. Do not click Finger again yet.';
            $this->redirect($usersUrl);
            return;
        }

        try {
            if ($action === 'refresh') {
                $hik = $this->studentHikvision();
                $res = $hik->searchUsers(200);
                if (!$res['ok']) {
                    $_SESSION['flash_error'] = $res['message'] ?? 'Could not refresh users from machine.';
                    $this->redirect($usersUrl);
                    return;
                }
                $machineId = $svc->machine()->getHost();
                $users = $res['users'] ?? [];
                // Enrich finger slots for users already in search results (or focused search).
                foreach ($users as &$u) {
                    $eno = (string) ($u['employee_no'] ?? '');
                    if ($eno === '') {
                        continue;
                    }
                    if ($search !== '' && stripos($eno, $search) === false) {
                        continue;
                    }
                    $detail = $hik->getFingerPrintDetails($eno);
                    if (!empty($detail['ok'])) {
                        $u['finger_count'] = (int) ($detail['count'] ?? $u['finger_count'] ?? 0);
                        $u['finger_slots'] = $detail['slots'] ?? [];
                    }
                }
                unset($u);
                $saved = $model->upsertMachineUsers($users, $machineId);
                $model->linkFingerIdsFromMachineUsers($machineId);
                $_SESSION['flash_success'] = 'Refreshed ' . $saved . ' user(s) from machine ' . $machineId . '.';
                $this->redirect($usersUrl);
                return;
            }

            if ($employeeNo === '') {
                $_SESSION['flash_error'] = 'Employee number (Person ID code) is required.';
                $this->redirect($usersUrl);
                return;
            }

            // Resolve name / student if missing.
            if ($studentId === '' || $name === '') {
                $st = $model->findActiveStudentByEmployeeNo($employeeNo);
                if ($st) {
                    if ($studentId === '') {
                        $studentId = (string) ($st['student_id'] ?? '');
                    }
                    if ($name === '') {
                        $ini = trim((string) ($st['student_ininame'] ?? ''));
                        $full = trim((string) ($st['student_fullname'] ?? ''));
                        $name = $ini !== '' ? $ini : $full;
                    }
                }
            }
            if ($name === '') {
                $name = $employeeNo;
            }

            $hik = $this->studentHikvision();
            $machineId = $svc->machine()->getHost();

            if ($action === 'add_user' || $action === 'add_user_and_fingers') {
                $created = $hik->createUser($employeeNo, $name, 'normal');
                if (!$created['ok']) {
                    $_SESSION['flash_error'] = $created['message'] ?? 'Failed to add user on machine.';
                    $this->redirect($this->usersRedirectUrl($search, $employeeNo, $filters));
                    return;
                }
                if ($studentId !== '') {
                    $model->setStudentFingerId($studentId, $employeeNo);
                }
                $synced = $this->syncEmployeeFingerInfoFromMachine(
                    $hik,
                    $model,
                    $machineId,
                    $employeeNo,
                    $name,
                    []
                );
                if ($action === 'add_user') {
                    $_SESSION['flash_success'] = ($created['message'] ?? 'User added.')
                        . ' · ' . $synced['message']
                        . $this->fanOutCredentialsToReaders($employeeNo, $name, $studentId, [], false);
                    $this->redirect($this->usersRedirectUrl($search, $employeeNo, $filters));
                    return;
                }
            }

            if ($action === 'add_finger' || $action === 'add_user_and_fingers') {
                $slots = $action === 'add_user_and_fingers'
                    ? [1, 2]
                    : [max(1, min(2, $fingerNo > 0 ? $fingerNo : 1))];
                $messages = [];
                $okAll = true;
                foreach ($slots as $slot) {
                    $enroll = $hik->enrollFingerPrint($employeeNo, $slot);
                    $messages[] = $enroll['message'] ?? ('Finger 0' . $slot . ($enroll['ok'] ? ' OK' : ' failed'));
                    if (empty($enroll['ok'])) {
                        $okAll = false;
                        break;
                    }
                }
                if ($studentId !== '') {
                    $model->setStudentFingerId($studentId, $employeeNo);
                }
                // Auto-sync machine immediately after enroll
                $synced = $this->syncEmployeeFingerInfoFromMachine(
                    $hik,
                    $model,
                    $machineId,
                    $employeeNo,
                    $name,
                    $okAll ? $slots : []
                );
                $text = implode(' ', $messages) . ' · ' . $synced['message'];
                if ($okAll) {
                    $text .= $this->fanOutCredentialsToReaders(
                        $employeeNo,
                        $name,
                        $studentId,
                        $synced['slots'] ?? $slots,
                        !empty($synced['has_face'])
                    );
                    unset($_SESSION['student_att_lockout_until']);
                    $_SESSION['flash_success'] = $text;
                } else {
                    $joined = strtolower($text);
                    if (str_contains($joined, 'locked') || str_contains($joined, '401')) {
                        $_SESSION['student_att_lockout_until'] = time() + 20 * 60;
                    }
                    $_SESSION['flash_error'] = $text;
                }
                $this->redirect($this->usersRedirectUrl($search, $employeeNo, $filters));
                return;
            }

            if ($action === 'remove_finger') {
                $slot = max(0, min(2, $fingerNo));
                $del = $hik->deleteFingerPrint($employeeNo, $slot);
                usleep(400000);
                $synced = $this->syncEmployeeFingerInfoFromMachine(
                    $hik,
                    $model,
                    $machineId,
                    $employeeNo,
                    $name,
                    []
                );
                if (!empty($del['ok']) && $slot > 0) {
                    $fpSlots = array_values(array_filter(
                        $synced['slots'],
                        static fn ($n) => (int) $n !== $slot
                    ));
                    $model->upsertMachineUsers([[
                        'employee_no' => $employeeNo,
                        'name' => $name,
                        'user_type' => 'normal',
                        'finger_count' => count($fpSlots),
                        'face_count' => (int) ($synced['face_count'] ?? 0),
                        'finger_slots' => $fpSlots,
                    ]], $machineId);
                    $synced['count'] = count($fpSlots);
                    $synced['slots'] = $fpSlots;
                    $synced['message'] = 'Synced from machine · ' . $synced['count'] . ' finger(s)';
                } elseif (!empty($del['ok']) && $slot === 0) {
                    $model->upsertMachineUsers([[
                        'employee_no' => $employeeNo,
                        'name' => $name,
                        'user_type' => 'normal',
                        'finger_count' => 0,
                        'face_count' => (int) ($synced['face_count'] ?? 0),
                        'finger_slots' => [],
                    ]], $machineId);
                    $synced['count'] = 0;
                    $synced['slots'] = [];
                    $synced['message'] = 'Synced from machine · 0 fingers';
                }
                if (!empty($del['ok'])) {
                    $_SESSION['flash_success'] = ($del['message'] ?? 'Fingerprint removed.')
                        . ' · ' . $synced['message'];
                } else {
                    $_SESSION['flash_error'] = ($del['message'] ?? 'Failed to remove fingerprint.')
                        . ' · ' . $synced['message'];
                }
                $this->redirect($this->usersRedirectUrl($search, $employeeNo, $filters));
                return;
            }

            if ($action === 'add_face') {
                // Prefer MIS profile photo; fallback to on-device terminal enrollment.
                // If face already exists and this is not a Replace, treat as success (read photo).
                $enroll = ['ok' => false, 'message' => 'Face enroll failed.'];
                $forceReplace = (string) $this->post('replace_face', '') === '1';
                $existing = $hik->getFacePhoto($employeeNo);
                if (!$forceReplace && !empty($existing['ok']) && !empty($existing['jpeg'])) {
                    $this->cacheMachineFaceJpeg($employeeNo, $existing['jpeg']);
                    $enroll = [
                        'ok' => true,
                        'message' => 'Face already enrolled on machine (photo read OK).',
                    ];
                } else {
                    if ($forceReplace && !empty($existing['ok'])) {
                        $hik->deleteFace($employeeNo);
                        $this->clearMachineFaceCache($employeeNo);
                        usleep(300000);
                    }
                    $jpeg = $this->loadStudentProfileJpegBytes($studentId);
                    if ($jpeg !== null) {
                        $enroll = $hik->enrollFaceFromJpeg($employeeNo, $jpeg);
                        if (!empty($enroll['ok'])) {
                            $enroll['message'] = ($enroll['message'] ?? 'Face enrolled.') . ' (from student profile photo)';
                        }
                    }
                    if (empty($enroll['ok'])) {
                        $onDevice = $hik->enrollFaceOnDevice($employeeNo);
                        if (!empty($onDevice['ok'])) {
                            $enroll = $onDevice;
                        } elseif ($jpeg === null) {
                            $enroll = [
                                'ok' => false,
                                'message' => 'No student profile photo found, and on-device face enroll failed: '
                                    . ($onDevice['message'] ?? 'unknown'),
                            ];
                        } else {
                            $enroll = [
                                'ok' => false,
                                'message' => 'Profile photo upload failed ('
                                    . ($enroll['message'] ?? 'error')
                                    . '). On-device: ' . ($onDevice['message'] ?? 'failed'),
                            ];
                        }
                    }
                    if (!empty($enroll['ok'])) {
                        $fresh = $hik->getFacePhoto($employeeNo);
                        if (!empty($fresh['ok']) && !empty($fresh['jpeg'])) {
                            $this->cacheMachineFaceJpeg($employeeNo, $fresh['jpeg']);
                        }
                    }
                }
                usleep(400000);
                $synced = $this->syncEmployeeFingerInfoFromMachine(
                    $hik,
                    $model,
                    $machineId,
                    $employeeNo,
                    $name,
                    []
                );
                if (empty($enroll['ok']) && !empty($synced['has_face'])) {
                    $enroll = [
                        'ok' => true,
                        'message' => 'Face already on machine (sync confirmed).',
                    ];
                    $photo = $hik->getFacePhoto($employeeNo);
                    if (!empty($photo['ok']) && !empty($photo['jpeg'])) {
                        $this->cacheMachineFaceJpeg($employeeNo, $photo['jpeg']);
                    }
                }
                if (!empty($enroll['ok']) && empty($synced['has_face'])) {
                    // Force face flag if enroll succeeded but UserInfo lag
                    $model->upsertMachineUsers([[
                        'employee_no' => $employeeNo,
                        'name' => $name,
                        'user_type' => 'normal',
                        'finger_count' => (int) ($synced['count'] ?? 0),
                        'face_count' => 1,
                        'finger_slots' => $synced['slots'] ?? [],
                    ]], $machineId);
                    $synced['has_face'] = true;
                    $synced['face_count'] = 1;
                    $synced['message'] = 'Synced · Face enrolled';
                }
                if ($studentId !== '') {
                    $model->setStudentFingerId($studentId, $employeeNo);
                }
                $text = ($enroll['message'] ?? '') . ' · ' . $synced['message'];
                if (!empty($enroll['ok'])) {
                    $text .= $this->fanOutCredentialsToReaders(
                        $employeeNo,
                        $name,
                        $studentId,
                        $synced['slots'] ?? [],
                        true
                    );
                    $_SESSION['flash_success'] = $text;
                } else {
                    $_SESSION['flash_error'] = $text;
                }
                $this->redirect($this->usersRedirectUrl($search, $employeeNo, $filters));
                return;
            }

            if ($action === 'remove_face') {
                $del = $hik->deleteFace($employeeNo);
                $this->clearMachineFaceCache($employeeNo);
                usleep(400000);
                $synced = $this->syncEmployeeFingerInfoFromMachine(
                    $hik,
                    $model,
                    $machineId,
                    $employeeNo,
                    $name,
                    []
                );
                if (!empty($del['ok'])) {
                    $model->upsertMachineUsers([[
                        'employee_no' => $employeeNo,
                        'name' => $name,
                        'user_type' => 'normal',
                        'finger_count' => (int) ($synced['count'] ?? 0),
                        'face_count' => 0,
                        'finger_slots' => $synced['slots'] ?? [],
                    ]], $machineId);
                    $_SESSION['flash_success'] = ($del['message'] ?? 'Face removed.') . ' · Synced · Face empty';
                } else {
                    $_SESSION['flash_error'] = ($del['message'] ?? 'Failed to remove face.') . ' · ' . $synced['message'];
                }
                $this->redirect($this->usersRedirectUrl($search, $employeeNo, $filters));
                return;
            }

            $_SESSION['flash_error'] = 'Unknown action.';
        } catch (Throwable $e) {
            error_log('[StudentDevice users] ' . $e->getMessage());
            $_SESSION['flash_error'] = 'Machine action failed: ' . $e->getMessage();
        }
        $this->redirect($this->usersRedirectUrl($search, $employeeNo, $filters));
    }

    /** Keep focus on the same employee after enroll/remove so live finger info is visible. */
    private function usersRedirectUrl(string $search, string $employeeNo = '', array $filters = []): string {
        $q = trim($search);
        if ($q === '' && trim($employeeNo) !== '') {
            $q = trim($employeeNo);
        }
        $params = [];
        if ($q !== '') {
            $params['q'] = $q;
        }
        foreach (['department_id', 'course_id', 'academic_year', 'course_mode'] as $key) {
            $val = trim((string) ($filters[$key] ?? ''));
            if ($val !== '') {
                $params[$key] = $val;
            }
        }
        $qs = http_build_query($params);
        return 'attendance/student-device/users' . ($qs !== '' ? ('?' . $qs) : '');
    }

    /**
     * Student Information Excel export — filter UI (RBAC: HOD scoped; SAO/ADM/DIR/DPA/REG all depts).
     */
    public function fingerprintImport() {
        $ctx = $this->requireDashboardAccess();
        if ($ctx === null) {
            return;
        }

        $departmentId = trim((string) $this->get('department_id', ''));
        if ($ctx['department_scope'] !== null) {
            $departmentId = $ctx['department_scope'];
        }
        $courseId = trim((string) $this->get('course_id', ''));
        $academicYear = trim((string) $this->get('academic_year', ''));
        $groupId = trim((string) $this->get('group_id', ''));
        $courseMode = trim((string) $this->get('course_mode', ''));
        if (!in_array(strtolower($courseMode), ['full', 'part', 'full time', 'part time'], true)
            && !in_array($courseMode, ['Full', 'Part'], true)) {
            $courseMode = '';
        }
        $run = (string) $this->get('run', '') === '1'
            || $departmentId !== ''
            || $courseId !== ''
            || $academicYear !== ''
            || $groupId !== ''
            || $courseMode !== '';

        $departmentModel = $this->model('DepartmentModel');
        $courseModel = $this->model('CourseModel');
        $studentModel = $this->model('StudentModel');
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

        $academicYears = $studentModel->getAcademicYears();
        $courses = [];
        if ($departmentId !== '') {
            $courses = $courseModel->getCoursesWithDepartment(['department_id' => $departmentId]);
        }
        $groupDept = $departmentId !== '' ? $departmentId : $ctx['department_scope'];
        $groups = $groupModel->getAllWithDetails(
            $groupDept !== null && $groupDept !== '' ? $groupDept : null,
            $courseId !== '' ? $courseId : null
        );

        $students = [];
        if ($run) {
            $students = $this->syncService()->attendanceModel()->listStudentsForFingerprintImport(
                $departmentId,
                $courseId,
                $academicYear,
                $groupId,
                $courseMode
            );
            // Extra server-side HOD guard (never leak other depts).
            if ($ctx['department_scope'] !== null) {
                $scope = $ctx['department_scope'];
                $students = array_values(array_filter(
                    $students,
                    static function (array $row) use ($scope): bool {
                        return (string) ($row['department_id'] ?? '') === $scope;
                    }
                ));
            }
        }

        $exportQuery = http_build_query(array_filter([
            'academic_year' => $academicYear,
            'department_id' => $departmentId,
            'course_id' => $courseId,
            'group_id' => $groupId,
            'course_mode' => $courseMode,
        ], static function ($v): bool {
            return $v !== '' && $v !== null;
        }));

        return $this->view('attendance/student_device/fingerprint_import', [
            'title' => 'Student Information Excel Export',
            'page' => 'student-device-attendance-fingerprint-import',
            'urls' => $this->urls(),
            'canManageDevice' => $ctx['can_manage'],
            'isHodScoped' => $ctx['department_scope'] !== null,
            'userRole' => $ctx['role'],
            'departments' => $departments,
            'courses' => $courses,
            'academicYears' => $academicYears,
            'groups' => $groups,
            'departmentId' => $departmentId,
            'courseId' => $courseId,
            'academicYear' => $academicYear,
            'groupId' => $groupId,
            'courseMode' => $courseMode,
            'students' => $students,
            'run' => $run,
            'filterError' => '',
            'exportUrl' => $this->urls()['export_fingerprint_import'] . ($exportQuery !== '' ? '?' . $exportQuery : ''),
        ]);
    }

    /**
     * Download Student Information .xlsx (RBAC enforced; read-only; does not modify student data).
     */
    public function exportFingerprintImport() {
        $ctx = $this->requireDashboardAccess();
        if ($ctx === null) {
            return;
        }

        $departmentId = trim((string) $this->get('department_id', ''));
        if ($ctx['department_scope'] !== null) {
            $departmentId = $ctx['department_scope'];
        }
        $courseId = trim((string) $this->get('course_id', ''));
        $academicYear = trim((string) $this->get('academic_year', ''));
        $groupId = trim((string) $this->get('group_id', ''));
        $courseMode = trim((string) $this->get('course_mode', ''));
        if (!in_array(strtolower($courseMode), ['full', 'part', 'full time', 'part time'], true)
            && !in_array($courseMode, ['Full', 'Part'], true)) {
            $courseMode = '';
        }

        $students = $this->syncService()->attendanceModel()->listStudentsForFingerprintImport(
            $departmentId,
            $courseId,
            $academicYear,
            $groupId,
            $courseMode
        );
        if ($ctx['department_scope'] !== null) {
            $scope = $ctx['department_scope'];
            $students = array_values(array_filter(
                $students,
                static function (array $row) use ($scope): bool {
                    return (string) ($row['department_id'] ?? '') === $scope;
                }
            ));
        }

        if ($students === []) {
            $_SESSION['flash_error'] = 'No authorized students found for the selected filters.';
            $q = http_build_query(array_filter([
                'run' => '1',
                'academic_year' => $academicYear,
                'department_id' => $departmentId,
                'course_id' => $courseId,
                'group_id' => $groupId,
                'course_mode' => $courseMode,
            ]));
            $this->redirect('attendance/student-device/fingerprint-import?' . $q);
            return;
        }

        $headers = [
            'Person ID',
            'Organization',
            'Person Name',
            'Gender',
            'Contact',
            'Email',
            'Effective Time',
            'Expiry Time',
            'Card No.',
            'Room No.',
            'Floor No.',
        ];
        $rows = [];
        foreach ($students as $s) {
            $rows[] = [
                (string) ($s['person_id'] ?? ''),
                'SLGTI',
                (string) ($s['person_name'] ?? ''),
                (string) ($s['gender_code'] ?? ''),
                (string) ($s['contact'] ?? ''),
                (string) ($s['email'] ?? ''),
                '',
                '',
                '',
                '',
                '',
            ];
        }

        $baseName = 'student_information_export_' . date('Y-m-d_His');
        require_once BASE_PATH . '/vendor/autoload.php';

        if (!class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
            $_SESSION['flash_error'] = 'Excel engine not available. Run composer install.';
            $this->redirect('attendance/student-device/fingerprint-import');
            return;
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Students');
        $sheet->fromArray($headers, null, 'A1');
        $sheet->fromArray($rows, null, 'A2');

        $lastRow = count($rows) + 1;
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1F4E79'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ];
        $sheet->getStyle('A1:K1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(22);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:K' . $lastRow);

        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $sheet->getStyle($col . '1:' . $col . $lastRow)
                ->getNumberFormat()
                ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
        }
        $sheet->getStyle('A2:K' . $lastRow)->getAlignment()->setVertical(
            \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
        );

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $baseName . '.xlsx"');
        header('Cache-Control: max-age=0, no-cache, must-revalidate');
        header('Pragma: public');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
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

        @set_time_limit(200);
        $svc = $this->syncService();
        // Prefer Sync today / short ranges — full history across 4 machines can hit PHP limits
        $budget = ($mode === 'today') ? 120 : (($mode === 'range') ? 160 : 180);
        $summary = $svc->syncRange(
            $start,
            $end,
            (int) $_SESSION['user_id'],
            (string) ($_SESSION['user_name'] ?? ''),
            $budget
        );
        $_SESSION['student_att_sync_summary'] = $summary;

        // Cache per-device online from sync result for dashboard cards
        if (!empty($summary['devices']) && is_array($summary['devices'])) {
            $probes = [];
            foreach ($summary['devices'] as $d) {
                $probes[] = [
                    'host' => $d['host'] ?? '',
                    'role' => $d['role'] ?? '',
                    'label' => $d['label'] ?? '',
                    'online' => !empty($d['ok']),
                    'message' => $d['message'] ?? '',
                ];
            }
            $_SESSION['student_att_device_status'] = [
                'devices' => $probes,
                'tested_at' => date('Y-m-d H:i:s'),
            ];
        }

        if ($summary['ok']) {
            $_SESSION['flash_success'] = $summary['message'] ?: 'Synchronization Completed';
        } else {
            $_SESSION['flash_error'] = $summary['message'] ?: 'Synchronization failed.';
        }
        $this->redirect('attendance/student-device');
    }

    public function connectionTest() {
        if (!$this->requireAccess()) {
            return;
        }
        $cfg = require BASE_PATH . '/config/student_attendance_machine.php';
        if (trim((string) ($cfg['password'] ?? '')) === '') {
            $_SESSION['flash_error'] = 'Cannot test: machine password is empty on this server. '
                . 'Use “Save machine password” below (or set STUDENT_HIKVISION_PASS / local.php), then Test once.';
            $this->redirect('attendance/student-device');
            return;
        }
        $lockoutUntil = (int) ($_SESSION['student_att_lockout_until'] ?? 0);
        if ($lockoutUntil > time() && (string) $this->get('force', '') !== '1') {
            $_SESSION['flash_error'] = 'Admin lock cooldown active until ' . date('H:i', $lockoutUntil)
                . '. Reboot the terminals to unlock sooner, or wait. '
                . 'To force a test anyway use Test after reboot only.';
            $this->redirect('attendance/student-device');
            return;
        }
        @set_time_limit(90);
        require_once BASE_PATH . '/core/HikvisionIntegration.php';
        $cred = $this->credentialSyncService();
        $probes = $cred->probeDeviceStatuses();
        $_SESSION['student_att_device_status'] = [
            'devices' => $probes,
            'tested_at' => date('Y-m-d H:i:s'),
        ];
        $this->rememberDeviceLockout($probes);

        $online = 0;
        $locked = 0;
        $authErr = 0;
        $lines = [];
        foreach ($probes as $p) {
            $st = strtoupper((string) ($p['status'] ?? (!empty($p['online']) ? 'online' : 'offline')));
            if (!empty($p['online'])) {
                $online++;
            }
            if (($p['status'] ?? '') === 'auth_error') {
                $authErr++;
            }
            if (!empty($p['locked']) || stripos((string) ($p['message'] ?? ''), 'locked') !== false) {
                $locked++;
            }
            $extra = (!empty($p['reason']) && $st !== 'ONLINE') ? ' (' . $p['reason'] . ')' : '';
            $lines[] = ($p['host'] ?? '') . ': ' . $st . $extra;
        }

        $main = $cred->mainDevice();
        $mainHost = (string) ($main['host'] ?? '');
        $mainOk = false;
        foreach ($probes as $p) {
            if ((string) ($p['host'] ?? '') === $mainHost) {
                $mainOk = !empty($p['online']);
                $_SESSION['student_att_connection'] = [
                    'host' => $mainHost,
                    'ok' => $mainOk,
                    'message' => (string) ($p['message'] ?? ''),
                    'tested_at' => date('Y-m-d H:i:s'),
                    'device_info' => null,
                    'status' => (string) ($p['status'] ?? ''),
                    'reason' => (string) ($p['reason'] ?? ''),
                ];
                break;
            }
        }

        if ($online > 0) {
            unset($_SESSION['student_att_lockout_until']);
            $_SESSION['flash_success'] = "Devices online: {$online}/" . count($probes) . ' — ' . implode(' · ', $lines);
        } elseif ($locked > 0 || $authErr > 0) {
            $until = (int) ($_SESSION['student_att_lockout_until'] ?? (time() + 1200));
            $_SESSION['flash_error'] = 'Auth/lock on terminals. '
                . 'Confirm browser login at http://172.16.0.26 first, then Test once. '
                . implode(' · ', $lines);
        } else {
            $_SESSION['flash_error'] = 'Devices not reachable on LAN — ' . implode(' · ', $lines);
        }
        $this->redirect('attendance/student-device');
    }

    /**
     * Save Hikvision admin password onto this server (writes gitignored local.php).
     * Required on production because .env / local.php are not in git.
     */
    public function saveMachineCredentials() {
        if (!$this->requireAccess()) {
            return;
        }
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
            $this->redirect('attendance/student-device');
            return;
        }

        $username = trim((string) $this->post('machine_username', 'admin'));
        $password = (string) $this->post('machine_password', '');
        $passwordConfirm = (string) $this->post('machine_password_confirm', '');

        if ($username === '') {
            $username = 'admin';
        }
        if (trim($password) === '') {
            $_SESSION['flash_error'] = 'Enter the Hikvision admin password (same as http://172.16.0.26 web login).';
            $this->redirect('attendance/student-device');
            return;
        }
        if ($password !== $passwordConfirm) {
            $_SESSION['flash_error'] = 'Password confirmation does not match.';
            $this->redirect('attendance/student-device');
            return;
        }

        $written = $this->writeMachineLocalConfig($password, $username);
        if (empty($written['ok'])) {
            $_SESSION['flash_error'] = $written['message'] ?? 'Could not save password on server.';
            $this->redirect('attendance/student-device');
            return;
        }

        $this->clearAllMachineCaches();
        clearstatcache(true, $written['path']);

        $autoTest = (string) $this->post('auto_test', '1') === '1';
        if ($autoTest) {
            @set_time_limit(90);
            require_once BASE_PATH . '/core/HikvisionIntegration.php';
            try {
                $probes = $this->probeWithPassword($username, $password);
                $_SESSION['student_att_device_status'] = [
                    'devices' => $probes,
                    'tested_at' => date('Y-m-d H:i:s'),
                ];
                $this->rememberDeviceLockout($probes);
                $online = 0;
                foreach ($probes as $p) {
                    if (!empty($p['online'])) {
                        $online++;
                    }
                }
                foreach ($probes as $p) {
                    if (($p['role'] ?? '') === 'main') {
                        $_SESSION['student_att_connection'] = [
                            'host' => (string) ($p['host'] ?? '172.16.0.26'),
                            'ok' => !empty($p['online']),
                            'message' => (string) ($p['message'] ?? ''),
                            'tested_at' => date('Y-m-d H:i:s'),
                            'device_info' => null,
                        ];
                        break;
                    }
                }
                if ($online > 0) {
                    $_SESSION['flash_success'] = $written['message']
                        . " Devices online: {$online}/" . count($probes) . '.';
                } else {
                    $_SESSION['flash_error'] = $written['message']
                        . ' Password saved, but devices still offline — check LAN from this server to 172.16.0.26, admin lock, or wrong password.';
                }
            } catch (Throwable $e) {
                $_SESSION['flash_success'] = $written['message'] . ' Saved. Test failed: ' . $e->getMessage();
            }
        } else {
            $_SESSION['flash_success'] = $written['message'] . ' Click Test all once.';
        }

        $this->redirect('attendance/student-device');
    }

    /**
     * Probe MAIN + readers using explicit credentials (avoids PHP require cache after write).
     *
     * @return list<array<string,mixed>>
     */
    private function probeWithPassword(string $username, string $password): array {
        require_once BASE_PATH . '/core/HikvisionIntegration.php';
        $hosts = [
            ['host' => '172.16.0.26', 'role' => 'main', 'label' => 'Main (enrollment)'],
            ['host' => '172.16.0.29', 'role' => 'reader', 'label' => 'Reader 1'],
            ['host' => '172.16.0.28', 'role' => 'reader', 'label' => 'Reader 2'],
            ['host' => '172.16.0.27', 'role' => 'reader', 'label' => 'Reader 3'],
        ];
        // Prefer hosts from written local / config when available
        $localPath = BASE_PATH . '/config/student_attendance_machine.local.php';
        if (is_file($localPath)) {
            $local = include $localPath;
            if (is_array($local)) {
                $main = trim((string) ($local['host'] ?? '172.16.0.26'));
                $readers = $local['reader_hosts'] ?? [];
                if (!is_array($readers)) {
                    $readers = [];
                }
                $hosts = [['host' => $main, 'role' => 'main', 'label' => 'Main (enrollment)']];
                $i = 1;
                foreach ($readers as $rip) {
                    $rip = trim((string) $rip);
                    if ($rip === '' || $rip === $main) {
                        continue;
                    }
                    $hosts[] = ['host' => $rip, 'role' => 'reader', 'label' => 'Reader ' . $i];
                    $i++;
                }
            }
        }

        $out = [];
        $skipRestReason = '';
        foreach ($hosts as $device) {
            $host = (string) ($device['host'] ?? '');
            $row = [
                'host' => $host,
                'role' => (string) ($device['role'] ?? ''),
                'label' => (string) ($device['label'] ?? $host),
                'online' => false,
                'message' => '',
                'locked' => false,
            ];
            if ($skipRestReason !== '') {
                $row['message'] = $skipRestReason;
                $out[] = $row;
                continue;
            }
            try {
                $hik = new HikvisionIntegration([
                    'host' => $host,
                    'username' => $username,
                    'password' => $password,
                    'ssl' => false,
                    'port' => 0,
                    'timeout' => 12,
                ]);
                $test = $hik->testConnection();
                $row['online'] = !empty($test['success']);
                $row['message'] = (string) ($test['message'] ?? ($row['online'] ? 'OK' : 'Failed'));
                $msgLower = strtolower($row['message']);
                if (!$row['online'] && (str_contains($msgLower, 'temporarily locked') || str_contains($msgLower, 'lockstatus'))) {
                    $row['locked'] = true;
                    $skipRestReason = 'Skipped — admin locked on another device (same password). Wait ~15–20 min or reboot each terminal, then Test once.';
                } elseif (!$row['online'] && str_contains($msgLower, '401')) {
                    $skipRestReason = 'Skipped — login failed (check password). Do not keep clicking Test.';
                }
            } catch (Throwable $e) {
                $row['message'] = $e->getMessage();
            }
            $out[] = $row;
        }
        return $out;
    }

    /**
     * Remember device admin lockout so we do not keep probing and extending the lock.
     *
     * @param list<array<string,mixed>> $probes
     */
    private function rememberDeviceLockout(array $probes): void {
        $locked = false;
        foreach ($probes as $p) {
            if (!empty($p['locked']) || stripos((string) ($p['message'] ?? ''), 'locked') !== false) {
                $locked = true;
                break;
            }
        }
        if ($locked) {
            $until = time() + 20 * 60;
            $prev = (int) ($_SESSION['student_att_lockout_until'] ?? 0);
            $_SESSION['student_att_lockout_until'] = max($prev, $until);
            return;
        }
        foreach ($probes as $p) {
            if (!empty($p['online'])) {
                unset($_SESSION['student_att_lockout_until']);
                return;
            }
        }
    }

    /**
     * Pull student/user directories from MAIN + 3 readers into the central DB cache.
     */
    public function refreshUsersFromMachines() {
        if (!$this->requireAccess()) {
            return;
        }
        $cfg = require BASE_PATH . '/config/student_attendance_machine.php';
        if (trim((string) ($cfg['password'] ?? '')) === '') {
            $_SESSION['flash_error'] = 'Cannot refresh users: machine password is empty on this server. Save the password first.';
            $this->redirect('attendance/student-device');
            return;
        }
        @set_time_limit(100);
        require_once BASE_PATH . '/core/HikvisionIntegration.php';
        $cred = $this->credentialSyncService();
        $result = $cred->refreshUserDirectoriesFromAllDevices(75);
        $_SESSION['student_att_refresh_users'] = $result;
        // Also refresh probe cache from result online flags
        $probes = [];
        foreach ($result['devices'] ?? [] as $d) {
            $probes[] = [
                'host' => $d['host'] ?? '',
                'role' => $d['role'] ?? '',
                'label' => $d['label'] ?? '',
                'online' => !empty($d['online']),
                'message' => $d['message'] ?? '',
            ];
        }
        if ($probes !== []) {
            $_SESSION['student_att_device_status'] = [
                'devices' => $probes,
                'tested_at' => date('Y-m-d H:i:s'),
            ];
        }
        if (!empty($result['ok'])) {
            $_SESSION['flash_success'] = $result['message'] ?? 'User directories refreshed.';
        } else {
            $_SESSION['flash_error'] = $result['message'] ?? 'Could not refresh user directories.';
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
