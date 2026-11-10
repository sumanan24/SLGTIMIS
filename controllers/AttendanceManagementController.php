<?php
require_once __DIR__ . '/AttendanceBaseController.php';

/**
 * Attendance Management: daily attendance, class attendance, biometric records, corrections,
 * reports and rule settings. Biometric punches are read only; corrections are audited.
 */
class AttendanceManagementController extends AttendanceBaseController {

    private function dailyModel() {
        require_once BASE_PATH . '/models/DailyAttendanceModel.php';
        return new DailyAttendanceModel();
    }

    /** Date filter: valid Y-m-d, never in the future. */
    private function pickDate($key = 'date') {
        $today = AttendanceRules::today();
        $d = $this->isoDate($this->get($key, ''), $today);
        return $d > $today ? $today : $d;
    }

    private function departmentsForPicker(array $ctx) {
        if ($ctx['scoped']) {
            return [];
        }
        return $this->model('DepartmentModel')->getAll();
    }

    // ------------------------------------------------------------------ daily
    public function daily() {
        $ctx = $this->requireAttendanceAccess();
        if (!$ctx) {
            return;
        }
        require_once BASE_PATH . '/helpers/AttendanceRules.php';
        $date = $this->pickDate();
        $dept = $this->scopeDepartment($ctx);
        $groupId = trim((string) $this->get('group_id', ''));
        $indicator = trim((string) $this->get('indicator', ''));
        $search = trim((string) $this->get('search', ''));
        $needScope = ($dept === '' && $groupId === '');
        $rows = [];
        if (!$needScope) {
            $rows = $this->dailyModel()->listDay($date, [
                'department_id' => $dept, 'group_id' => $groupId, 'indicator' => $indicator, 'search' => $search,
            ]);
        }
        if ($this->get('export') === 'xlsx' && !$needScope) {
            require_once BASE_PATH . '/helpers/ReportExport.php';
            list($h, $r) = $this->dailyReportTable($rows);
            ReportExport::xlsx('daily_attendance_' . $date, $h, $r, 'Daily ' . $date);
        }
        return $this->view('attendance_management/daily', array_merge($this->flashPull(), [
            'title' => 'Daily Attendance',
            'page' => 'att-daily',
            'ctx' => $ctx,
            'date' => $date,
            'rows' => $rows,
            'summary' => DailyAttendanceModel::summarise($rows),
            'editState' => AttendanceRules::editState($date),
            'filters' => ['department_id' => $dept, 'group_id' => $groupId, 'indicator' => $indicator, 'search' => $search],
            'departments' => $this->departmentsForPicker($ctx),
            'groups' => $this->visibleGroups($dept),
            'needScope' => $needScope,
            'csrf' => $this->csrfToken(),
            'rules' => AttendanceRules::config(),
        ]));
    }

    private function dailyReportTable(array $rows) {
        $h = ['Reg No', 'Name', 'Group', 'Course', 'IN', 'OUT', 'Punches', 'Biometric status', 'Final attendance', 'Source', 'Indicator', 'Reason'];
        $out = [];
        foreach ($rows as $r) {
            $out[] = [$r['student_id'], $r['student_name'], $r['group_name'], $r['course_name'], $r['first_in'] ? substr($r['first_in'], 0, 5) : '', $r['last_out'] ? substr($r['last_out'], 0, 5) : '',
                $r['punch_count'], $this->statusText($r['calc_status']), $this->statusText($r['final_status']), ucfirst($r['source']), $r['indicator'], $r['calc_reason']];
        }
        return [$h, $out];
    }

    private function statusText($s) {
        return $s === 'full' ? 'Full Day' : ($s === 'half' ? 'Half Day' : 'Absent');
    }

    // ------------------------------------------------------------------ class
    public function classAttendance() {
        $ctx = $this->requireAttendanceAccess();
        if (!$ctx) {
            return;
        }
        require_once BASE_PATH . '/helpers/AttendanceRules.php';
        require_once BASE_PATH . '/models/ClassAttendanceModel.php';
        $date = $this->pickDate();
        $dept = $this->scopeDepartment($ctx);
        $groupId = (int) $this->get('group_id', 0);
        $groups = $this->visibleGroups($dept);
        $result = ['sessions' => [], 'members' => [], 'blocks' => []];
        $group = null;
        if ($groupId > 0) {
            foreach ($groups as $g) {
                if ((int) $g['id'] === $groupId) {
                    $group = $g;
                }
            }
            if (!$group) {
                $_SESSION['error'] = 'Group not found in your scope.';
                $this->redirect('attendance-management/class');
                return;
            }
            $result = (new ClassAttendanceModel())->forGroupDate($groupId, $date);
        }
        $timetableId = (int) $this->get('timetable_id', 0);
        if ($this->get('export') === 'xlsx' && $group) {
            require_once BASE_PATH . '/helpers/ReportExport.php';
            $h = ['Session', 'Time', 'Type', 'Reg No', 'Name', 'Group', 'IN', 'OUT', 'Biometric status', 'Class attendance', 'Source', 'Correction', 'Indicator'];
            $out = [];
            foreach ($result['blocks'] as $b) {
                $s = $b['session'];
                $label = $s['module_name'] ?: ($s['subject'] ?: 'Class');
                foreach ($b['rows'] as $r) {
                    $out[] = [$label, substr($s['start_clock'], 0, 5) . '-' . substr($s['end_clock'], 0, 5), $s['session_type'], $r['student_id'], $r['student_name'], $group['name'],
                        $r['first_in'] ? substr($r['first_in'], 0, 5) : '', $r['last_out'] ? substr($r['last_out'], 0, 5) : '', $this->statusText($r['calc_status']),
                        $this->statusText($r['final_status']), ucfirst($r['source']), $r['correction_status'], $r['indicator']];
                }
            }
            ReportExport::xlsx('class_attendance_' . $date, $h, $out, 'Class ' . $date);
        }
        return $this->view('attendance_management/class', array_merge($this->flashPull(), [
            'title' => 'Class Attendance',
            'page' => 'att-class',
            'ctx' => $ctx,
            'date' => $date,
            'group' => $group,
            'groups' => $groups,
            'departments' => $this->departmentsForPicker($ctx),
            'result' => $result,
            'timetableId' => $timetableId,
            'editState' => AttendanceRules::editState($date),
            'filters' => ['department_id' => $dept, 'group_id' => $groupId],
            'csrf' => $this->csrfToken(),
        ]));
    }

    // ------------------------------------------------------------------ biometric
    public function biometric() {
        $ctx = $this->requireAttendanceAccess();
        if (!$ctx) {
            return;
        }
        require_once BASE_PATH . '/helpers/AttendanceRules.php';
        $today = AttendanceRules::today();
        $to = $this->pickDate('to');
        $from = $this->isoDate($this->get('from', ''), $to);
        if ($from > $to) {
            $from = $to;
        }
        if ((strtotime($to) - strtotime($from)) / 86400 > 31) {
            $from = date('Y-m-d', strtotime($to . ' -31 days'));
        }
        $dept = $this->scopeDepartment($ctx);
        $search = trim((string) $this->get('search', ''));
        $rows = $this->dailyModel()->biometricRecords($from, $to, $dept, $search, 800);
        if ($this->get('export') === 'xlsx') {
            require_once BASE_PATH . '/helpers/ReportExport.php';
            list($h, $r) = $this->biometricTable($rows);
            ReportExport::xlsx('biometric_records_' . $from . '_' . $to, $h, $r, 'Biometric');
        }
        $unmatched = [];
        if (!$ctx['scoped']) {
            require_once BASE_PATH . '/models/StudentDeviceAttendanceModel.php';
            $unmatched = (new StudentDeviceAttendanceModel())->listUnmatched(50);
        }
        return $this->view('attendance_management/biometric', array_merge($this->flashPull(), [
            'title' => 'Biometric Records',
            'page' => 'att-biometric',
            'ctx' => $ctx,
            'rows' => $rows,
            'unmatched' => $unmatched,
            'filters' => ['from' => $from, 'to' => $to, 'search' => $search, 'department_id' => $dept],
            'departments' => $this->departmentsForPicker($ctx),
            'rules' => AttendanceRules::config(),
        ]));
    }

    private function biometricTable(array $rows) {
        $h = ['Date', 'Time', 'Reg No', 'Name', 'Device', 'Event ID', 'Rule classification', 'Counted'];
        $out = [];
        foreach ($rows as $r) {
            $out[] = [$r['attendance_date'], substr($r['attendance_time'], 0, 8), $r['student_id'], $r['name'], $r['machine_id'], $r['event_id'], $r['classification'], $r['counted'] ? 'Yes' : 'No'];
        }
        return [$h, $out];
    }

    // ------------------------------------------------------------------ corrections
    public function corrections() {
        $ctx = $this->requireAttendanceAccess();
        if (!$ctx) {
            return;
        }
        require_once BASE_PATH . '/models/AttendanceCorrectionModel.php';
        $model = new AttendanceCorrectionModel();
        $dept = $this->scopeDepartment($ctx);
        $tab = $this->get('tab', 'pending');
        if (!in_array($tab, ['pending', 'history', 'audit'], true)) {
            $tab = 'pending';
        }
        $from = $this->isoDate($this->get('from', ''), '');
        $to = $this->isoDate($this->get('to', ''), '');
        $data = ['pending' => [], 'history' => [], 'audit' => []];
        if ($tab === 'pending') {
            $data['pending'] = $model->listCorrections(['status' => 'pending', 'department_id' => $dept]);
        } elseif ($tab === 'history') {
            $data['history'] = $model->listCorrections(['department_id' => $dept, 'date_from' => $from, 'date_to' => $to]);
        } else {
            $studentId = trim((string) $this->get('student_id', ''));
            $adate = $this->isoDate($this->get('date', ''), '');
            if ($studentId !== '' && $adate !== '') {
                if (!$this->canTouchStudent($ctx, $studentId)) {
                    $_SESSION['error'] = 'Access denied for that student.';
                    $this->redirect('attendance-management/corrections?tab=audit');
                    return;
                }
                $data['audit'] = $model->auditTrail($studentId, $adate);
            } else {
                $data['audit'] = $model->listAudit($dept, $from, $to, 200);
            }
        }
        return $this->view('attendance_management/corrections', array_merge($this->flashPull(), $data, [
            'title' => 'Attendance Corrections',
            'page' => 'att-corrections',
            'ctx' => $ctx,
            'tab' => $tab,
            'filters' => ['from' => $from, 'to' => $to, 'student_id' => trim((string) $this->get('student_id', '')), 'date' => $this->isoDate($this->get('date', ''), '')],
            'csrf' => $this->csrfToken(),
        ]));
    }

    /** POST: correct / request / exceptional request – the mode follows the date and the user's rights. */
    public function correct() {
        $ctx = $this->requireAttendanceAccess();
        if (!$ctx) {
            return;
        }
        require_once BASE_PATH . '/helpers/AttendanceRules.php';
        require_once BASE_PATH . '/models/AttendanceCorrectionModel.php';
        $back = $this->safeBack((string) $this->post('back', 'attendance-management/daily'));
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf()) {
            $_SESSION['error'] = 'Your session expired. Please try again.';
            $this->redirect($back);
            return;
        }
        $studentId = trim((string) $this->post('student_id', ''));
        $date = $this->isoDate($this->post('date', ''), '');
        if ($studentId === '' || $date === '') {
            $_SESSION['error'] = 'Student and date are required.';
            $this->redirect($back);
            return;
        }
        if (!$this->canTouchStudent($ctx, $studentId)) {
            $_SESSION['error'] = 'Access denied. This student belongs to another department.';
            $this->redirect($back);
            return;
        }
        $state = AttendanceRules::editState($date);
        if ($date > AttendanceRules::today()) {
            $_SESSION['error'] = 'Attendance for a future date cannot be corrected.';
            $this->redirect($back);
            return;
        }
        if ($state['editable']) {
            $mode = $ctx['canCorrect'] ? 'direct' : ($ctx['canRequest'] ? 'request' : '');
        } else {
            $mode = $ctx['canCorrect'] ? 'exceptional' : '';
        }
        if ($mode === '') {
            $_SESSION['error'] = $state['editable'] ? 'You do not have permission to change attendance.' : $state['reason'] . ' Only the Head of Department can submit an exceptional correction.';
            $this->redirect($back);
            return;
        }
        $res = (new AttendanceCorrectionModel())->submit(
            $studentId, $date, (string) $this->post('new_status', ''), (string) $this->post('reason', ''), $mode,
            ['user_id' => $ctx['userId'], 'user_name' => $ctx['userName']]
        );
        $_SESSION[$res['ok'] ? 'message' : 'error'] = $res['message'];
        $this->redirect($back);
    }

    public function decide() {
        $ctx = $this->requireAttendanceAccess();
        if (!$ctx) {
            return;
        }
        require_once BASE_PATH . '/helpers/AttendanceRules.php';
        require_once BASE_PATH . '/models/AttendanceCorrectionModel.php';
        $back = 'attendance-management/corrections?tab=pending';
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf()) {
            $_SESSION['error'] = 'Your session expired. Please try again.';
            $this->redirect($back);
            return;
        }
        $model = new AttendanceCorrectionModel();
        $row = $model->find((int) $this->post('id', 0));
        if (!$row) {
            $_SESSION['error'] = 'Request not found.';
            $this->redirect($back);
            return;
        }
        if ((int) $row['exceptional'] === 1) {
            if (!$ctx['canApproveExceptional']) {
                $_SESSION['error'] = 'Exceptional corrections are approved by the higher authority only.';
                $this->redirect($back);
                return;
            }
        } else {
            if (!$ctx['canCorrect']) {
                $_SESSION['error'] = 'Only the Head of Department can approve correction requests.';
                $this->redirect($back);
                return;
            }
            if (!$this->canTouchStudent($ctx, $row['student_id'])) {
                $_SESSION['error'] = 'Access denied. This student belongs to another department.';
                $this->redirect($back);
                return;
            }
        }
        $res = $model->decide((int) $row['id'], $this->post('decision', '') === 'approve', $this->post('remarks', ''),
            ['user_id' => $ctx['userId'], 'user_name' => $ctx['userName']], $ctx['canApproveExceptional']);
        $_SESSION[$res['ok'] ? 'message' : 'error'] = $res['message'];
        $this->redirect($back);
    }

    public function verify() {
        $ctx = $this->requireAttendanceAccess();
        if (!$ctx) {
            return;
        }
        require_once BASE_PATH . '/helpers/AttendanceRules.php';
        require_once BASE_PATH . '/models/AttendanceCorrectionModel.php';
        $back = $this->safeBack((string) $this->post('back', 'attendance-management/daily'));
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf() || !$ctx['canCorrect']) {
            $_SESSION['error'] = 'You do not have permission to verify attendance.';
            $this->redirect($back);
            return;
        }
        $studentId = trim((string) $this->post('student_id', ''));
        if (!$this->canTouchStudent($ctx, $studentId)) {
            $_SESSION['error'] = 'Access denied. This student belongs to another department.';
            $this->redirect($back);
            return;
        }
        $res = (new AttendanceCorrectionModel())->verify($studentId, $this->isoDate($this->post('date', ''), ''), $this->post('remarks', ''),
            ['user_id' => $ctx['userId'], 'user_name' => $ctx['userName']]);
        $_SESSION[$res['ok'] ? 'message' : 'error'] = $res['message'];
        $this->redirect($back);
    }

    /** Only allow redirects back into this module. */
    private function safeBack($back) {
        $back = ltrim($back, '/');
        return (strpos($back, 'attendance-management/') === 0) ? $back : 'attendance-management/daily';
    }

    // ------------------------------------------------------------------ reports
    public function reports() {
        $ctx = $this->requireAttendanceAccess();
        if (!$ctx) {
            return;
        }
        require_once BASE_PATH . '/helpers/AttendanceRules.php';
        $type = $this->get('type', 'daily');
        $types = ['daily' => 'Daily attendance', 'monthly' => 'Monthly attendance', 'biometric' => 'Biometric records', 'programme' => 'Training programme attendance', 'instructor' => 'Instructor diary / teaching'];
        if (!isset($types[$type])) {
            $type = 'daily';
        }
        $dept = $this->scopeDepartment($ctx);
        $groupId = trim((string) $this->get('group_id', ''));
        $date = $this->pickDate('date');
        $month = preg_match('/^\d{4}-\d{2}$/', (string) $this->get('month', '')) ? $this->get('month') : date('Y-m');
        if ($month > date('Y-m')) {
            $month = date('Y-m');
        }
        $to = $this->pickDate('to');
        $from = $this->isoDate($this->get('from', ''), date('Y-m-01', strtotime($to)));
        if ($from > $to) {
            $from = $to;
        }
        $needScope = in_array($type, ['daily', 'monthly'], true) && $dept === '' && $groupId === '';

        $report = ['title' => $types[$type], 'headers' => [], 'rows' => [], 'summary' => []];
        if (!$needScope) {
            $report = $this->buildReport($type, $types[$type], $ctx, $dept, $groupId, $date, $month, $from, $to);
        }
        $export = $this->get('export');
        if (($export === 'xlsx' || $export === 'pdf') && !$needScope) {
            require_once BASE_PATH . '/helpers/ReportExport.php';
            $name = 'report_' . $type . '_' . date('Ymd_His');
            if ($export === 'pdf') {
                ReportExport::pdf($name, $report['title'], $report['summary'], $report['headers'], $report['rows']);
            }
            ReportExport::xlsx($name, $report['headers'], $report['rows'], $report['title']);
        }
        return $this->view('attendance_management/reports', array_merge($this->flashPull(), [
            'title' => 'Attendance Reports',
            'page' => 'att-reports',
            'ctx' => $ctx,
            'type' => $type,
            'types' => $types,
            'report' => $report,
            'needScope' => $needScope,
            'filters' => ['department_id' => $dept, 'group_id' => $groupId, 'date' => $date, 'month' => $month, 'from' => $from, 'to' => $to],
            'departments' => $this->departmentsForPicker($ctx),
            'groups' => $this->visibleGroups($dept),
        ]));
    }

    private function buildReport($type, $title, array $ctx, $dept, $groupId, $date, $month, $from, $to) {
        $daily = $this->dailyModel();
        $deptName = $dept !== '' ? $dept : 'All departments';
        switch ($type) {
            case 'daily':
                $rows = $daily->listDay($date, ['department_id' => $dept, 'group_id' => $groupId]);
                list($h, $r) = $this->dailyReportTable($rows);
                $s = DailyAttendanceModel::summarise($rows);
                return ['title' => 'Daily Attendance - ' . $date, 'headers' => $h, 'rows' => $r, 'summary' => [
                    'Department: ' . $deptName, 'Students: ' . $s['total'] . '   Full day: ' . $s['full'] . '   Half day: ' . $s['half'] . '   Absent: ' . $s['absent'] . '   Pending verification: ' . $s['pending']]];
            case 'monthly':
                require_once BASE_PATH . '/models/StudentDeviceAttendanceModel.php';
                $students = (new StudentDeviceAttendanceModel())->listActiveStudentsForReport($dept, '', '', $groupId, '');
                $m = $daily->monthSummary($month, $students, $dept);
                $r = [];
                foreach ($m['rows'] as $row) {
                    $r[] = [$row['student_id'], $row['student_name'], $row['group_name'], $row['working_days'], $row['full_days'], $row['half_days'], $row['absent_days'], $row['attendance_days'], $row['percentage'] . '%'];
                }
                return ['title' => 'Monthly Attendance - ' . $month, 'headers' => ['Reg No', 'Name', 'Group', 'Working days', 'Full days', 'Half days', 'Absent days', 'Attendance days', 'Attendance %'], 'rows' => $r,
                    'summary' => ['Department: ' . $deptName, 'Working days exclude weekends, public holidays and institute leave. Half day counts 0.5.']];
            case 'biometric':
                $rows = $daily->biometricRecords($from, $to, $dept, '', 2000);
                list($h, $r) = $this->biometricTable($rows);
                return ['title' => 'Biometric Records ' . $from . ' to ' . $to, 'headers' => $h, 'rows' => $r, 'summary' => ['Department: ' . $deptName, 'Original punches are shown unchanged; classification follows the configured rules.']];
            case 'programme':
                require_once BASE_PATH . '/models/TrainingProgrammeModel.php';
                $r = (new TrainingProgrammeModel())->reportRows(['department_id' => $dept, 'date_from' => $from, 'date_to' => $to]);
                return ['title' => 'Training Programme Attendance ' . $from . ' to ' . $to, 'headers' => ['Programme', 'Type', 'Date', 'Time', 'Reg No', 'Name', 'Group', 'Attendance'], 'rows' => $r, 'summary' => ['Department: ' . $deptName]];
            case 'instructor':
                $rows = $this->model('InstructorDiaryModel')->progressByModule(['department_id' => $dept, 'from' => $from, 'to' => $to]);
                $r = [];
                foreach ($rows as $x) {
                    $r[] = [$x['staff_name'] ?: $x['staff_id'], $x['module_name'], $x['group_name'], (int) $x['sessions'], (int) $x['completed'], (int) $x['not_conducted'], (int) $x['outstanding'],
                        round((float) $x['scheduled_hours'], 2), round((float) $x['actual_hours'], 2)];
                }
                return ['title' => 'Instructor Teaching Report ' . $from . ' to ' . $to, 'headers' => ['Instructor', 'Module', 'Group', 'Sessions', 'Recorded', 'Not conducted', 'Outstanding', 'Scheduled hours', 'Actual hours'], 'rows' => $r, 'summary' => ['Department: ' . $deptName]];
        }
        return ['title' => $title, 'headers' => [], 'rows' => [], 'summary' => []];
    }

    // ------------------------------------------------------------------ settings
    public function settings() {
        $ctx = $this->requireAttendanceAccess();
        if (!$ctx) {
            return;
        }
        if (!$ctx['canConfigure']) {
            $_SESSION['error'] = 'Only an authorised administrator can change the attendance rules.';
            $this->redirect('attendance-management/daily');
            return;
        }
        require_once BASE_PATH . '/helpers/AttendanceRules.php';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->verifyCsrf()) {
                $_SESSION['error'] = 'Your session expired. Please try again.';
                $this->redirect('attendance-management/settings');
                return;
            }
            $before = AttendanceRules::config();
            $errors = AttendanceRules::saveConfig($_POST);
            if ($errors) {
                $_SESSION['error'] = implode(' ', $errors);
            } else {
                $this->logActivity('update', 'attendance_rules', null, 'Attendance rules changed', json_encode($before), json_encode(AttendanceRules::config()));
                $_SESSION['message'] = 'Attendance rules saved. Closed months keep the results calculated at the time.';
            }
            $this->redirect('attendance-management/settings');
            return;
        }
        return $this->view('attendance_management/settings', array_merge($this->flashPull(), [
            'title' => 'Attendance Rules',
            'page' => 'att-settings',
            'cfg' => AttendanceRules::config(),
            'csrf' => $this->csrfToken(),
        ]));
    }
}
