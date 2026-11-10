<?php
require_once __DIR__ . '/AttendanceBaseController.php';

/**
 * Instructor diary generated from published timetable sessions.
 * Instructors record what was actually taught; the HOD reviews submitted entries.
 * Sessions are never completed automatically.
 */
class TeachingDiaryController extends AttendanceBaseController {

    public function daily() {
        return $this->records('daily');
    }

    public function lessons() {
        return $this->records('lessons');
    }

    public function practical() {
        return $this->records('practical');
    }

    private function records($mode) {
        $ctx = $this->requireAttendanceAccess(self::TEACHING_ROLES);
        if (!$ctx) {
            return;
        }
        $staffId = $ctx['staffId'];
        $model = $this->model('InstructorDiaryModel');

        $today = date('Y-m-d');
        $to = $this->isoDate($this->get('to', ''), $today);
        $from = $this->isoDate($this->get('from', ''), date('Y-m-d', strtotime($to . ' -6 days')));
        if ($from > $to) {
            $from = $to;
        }
        if ((strtotime($to) - strtotime($from)) / 86400 > 61) {
            $from = date('Y-m-d', strtotime($to . ' -61 days'));
        }
        $model->generateForRange($staffId, $from, $to);

        $filters = ['staff_id' => $staffId, 'from' => $from, 'to' => $to, 'status' => trim((string) $this->get('status', ''))];
        if ($mode === 'lessons') {
            $filters['session_type'] = 'Theory';
        } elseif ($mode === 'practical') {
            $filters['session_type'] = 'Practical';
        }
        $titles = ['daily' => 'Daily Teaching Records', 'lessons' => 'Lesson Records', 'practical' => 'Practical Training Records'];
        $pages = ['daily' => 'diary-daily', 'lessons' => 'diary-lessons', 'practical' => 'diary-practical'];
        return $this->view('teaching_diary/records', array_merge($this->flashPull(), [
            'title' => $titles[$mode],
            'page' => $pages[$mode],
            'mode' => $mode,
            'entries' => $model->sessionEntries($filters),
            'filters' => ['from' => $from, 'to' => $to, 'status' => $filters['status']],
            'csrf' => $this->csrfToken(),
        ]));
    }

    public function action() {
        $ctx = $this->requireAttendanceAccess(self::TEACHING_ROLES);
        if (!$ctx) {
            return;
        }
        $back = trim((string) $this->post('back', 'teaching-diary/daily'));
        if (strpos($back, 'teaching-diary/') !== 0) {
            $back = 'teaching-diary/daily';
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf()) {
            $_SESSION['error'] = 'Your session expired. Please try again.';
            $this->redirect($back);
            return;
        }
        $model = $this->model('InstructorDiaryModel');
        $result = $model->instructorAction(
            (int) $this->post('diary_id', 0),
            $ctx['staffId'],
            (string) $this->post('do', ''),
            [
                'topic_covered' => $this->post('topic_covered', ''),
                'teaching_methods' => $this->post('teaching_methods', ''),
                'learning_outcomes' => $this->post('learning_outcomes', ''),
                'activities_completed' => $this->post('activities_completed', ''),
                'actual_hours' => $this->post('actual_hours', 0),
                'reason' => $this->post('reason', ''),
            ]
        );
        $_SESSION[$result['ok'] ? 'message' : 'error'] = $result['message'];
        $this->redirect($back);
    }

    /**
     * HOD: submitted diary entries of the department awaiting review.
     */
    public function review() {
        $ctx = $this->requireAttendanceAccess(['HOD', 'ADM', 'DIR', 'DPI', 'DPA']);
        if (!$ctx) {
            return;
        }
        if (!$ctx['canCorrect'] && !$ctx['isHigher']) {
            $_SESSION['error'] = 'Only the Head of Department can review diary entries.';
            $this->redirect('dashboard');
            return;
        }
        $dept = $this->scopeDepartment($ctx);
        $model = $this->model('InstructorDiaryModel');
        $to = $this->isoDate($this->get('to', ''), date('Y-m-d'));
        $from = $this->isoDate($this->get('from', ''), date('Y-m-d', strtotime($to . ' -30 days')));
        $status = trim((string) $this->get('status', 'Submitted'));
        $filters = ['department_id' => $dept, 'from' => $from, 'to' => $to, 'status' => $status === 'all' ? '' : $status];
        $staffFilter = trim((string) $this->get('staff_id', ''));
        if ($staffFilter !== '') {
            $filters['staff_id'] = $staffFilter;
        }
        $model->generateForRange(['department_id' => (string) $dept], $from, $to);
        require_once BASE_PATH . '/models/GroupTimetableModel.php';
        return $this->view('teaching_diary/review', array_merge($this->flashPull(), [
            'title' => 'Diary Review',
            'page' => 'diary-review',
            'entries' => $model->sessionEntries($filters),
            'staffList' => (new GroupTimetableModel())->listStaff($dept),
            'filters' => ['from' => $from, 'to' => $to, 'status' => $status, 'staff_id' => $staffFilter, 'department_id' => $dept],
            'ctx' => $ctx,
            'csrf' => $this->csrfToken(),
        ]));
    }

    public function reviewAction() {
        $ctx = $this->requireAttendanceAccess(['HOD', 'ADM', 'DIR', 'DPI', 'DPA']);
        if (!$ctx) {
            return;
        }
        $back = 'teaching-diary/review';
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf()) {
            $_SESSION['error'] = 'Your session expired. Please try again.';
            $this->redirect($back);
            return;
        }
        if (!$ctx['canCorrect'] && !$ctx['isHigher']) {
            $_SESSION['error'] = 'Only the Head of Department can review diary entries.';
            $this->redirect($back);
            return;
        }
        $model = $this->model('InstructorDiaryModel');
        $entry = $model->findSessionEntry((int) $this->post('diary_id', 0));
        if (!$entry) {
            $_SESSION['error'] = 'Diary entry not found.';
            $this->redirect($back);
            return;
        }
        if ($ctx['scoped']) {
            $staff = $this->model('StaffModel')->find($entry['staff_id']);
            if (!$staff || (string) ($staff['department_id'] ?? '') !== $ctx['departmentId']) {
                $_SESSION['error'] = 'Access denied. This entry belongs to another department.';
                $this->redirect($back);
                return;
            }
        }
        if ((string) $entry['staff_id'] === $ctx['staffId']) {
            $_SESSION['error'] = 'You cannot review your own diary entry.';
            $this->redirect($back);
            return;
        }
        $result = $model->review($entry['instructor_diary_id'], $ctx['staffId'], $this->post('decision', '') === 'approve', $this->post('remarks', ''));
        $_SESSION[$result['ok'] ? 'message' : 'error'] = $result['message'];
        $this->redirect($back);
    }

    /**
     * Teaching progress: scheduled vs actual hours (own records, or the department for the HOD).
     */
    public function progress() {
        $ctx = $this->requireAttendanceAccess();
        if (!$ctx) {
            return;
        }
        $model = $this->model('InstructorDiaryModel');
        $to = $this->isoDate($this->get('to', ''), date('Y-m-d'));
        $from = $this->isoDate($this->get('from', ''), date('Y-m-01'));
        $filters = ['from' => $from, 'to' => $to];
        $viewAll = $ctx['canCorrect'] || $ctx['isHigher'];
        if ($viewAll) {
            $filters['department_id'] = $this->scopeDepartment($ctx);
            $sf = trim((string) $this->get('staff_id', ''));
            if ($sf !== '') {
                $filters['staff_id'] = $sf;
            }
        } else {
            $filters['staff_id'] = $ctx['staffId'];
        }
        $model->generateForRange($viewAll ? ['department_id' => (string) ($filters['department_id'] ?? '')] : $ctx['staffId'], $from, $to);
        $rows = $model->progressByModule($filters);
        if ($this->get('export') === 'xlsx' || $this->get('export') === 'pdf') {
            return $this->exportProgress($rows, $filters);
        }
        require_once BASE_PATH . '/models/GroupTimetableModel.php';
        return $this->view('teaching_diary/progress', array_merge($this->flashPull(), [
            'title' => 'Teaching Progress',
            'page' => 'diary-progress',
            'rows' => $rows,
            'filters' => ['from' => $from, 'to' => $to, 'staff_id' => $filters['staff_id'] ?? '', 'department_id' => $filters['department_id'] ?? ''],
            'viewAll' => $viewAll,
            'staffList' => $viewAll ? (new GroupTimetableModel())->listStaff($filters['department_id'] ?? '') : [],
            'ctx' => $ctx,
        ]));
    }

    private function exportProgress(array $rows, array $filters) {
        require_once BASE_PATH . '/helpers/ReportExport.php';
        $headers = ['Instructor', 'Module', 'Group', 'Sessions', 'Completed', 'Not conducted', 'Outstanding', 'Scheduled hours', 'Actual hours', 'Variance (h)'];
        $out = [];
        foreach ($rows as $r) {
            $out[] = [$r['staff_name'] ?: $r['staff_id'], $r['module_name'], $r['group_name'], (int) $r['sessions'], (int) $r['completed'], (int) $r['not_conducted'],
                (int) $r['outstanding'], round((float) $r['scheduled_hours'], 2), round((float) $r['actual_hours'], 2), round((float) $r['actual_hours'] - (float) $r['scheduled_hours'], 2)];
        }
        $name = 'teaching_progress_' . date('Ymd');
        if ($this->get('export') === 'pdf') {
            ReportExport::pdf($name, 'Teaching Progress', ['Period: ' . $filters['from'] . ' to ' . $filters['to']], $headers, $out);
        }
        ReportExport::xlsx($name, $headers, $out, 'Teaching Progress');
        exit;
    }
}
