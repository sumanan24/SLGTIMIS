<?php
require_once __DIR__ . '/AttendanceBaseController.php';

/**
 * Training programme scheduling for groups and individual students.
 * Separate from the Circuit Program (staff) and from the weekly class timetable.
 */
class TrainingProgrammeController extends AttendanceBaseController {

    private function programmes() {
        require_once BASE_PATH . '/models/TrainingProgrammeModel.php';
        return new TrainingProgrammeModel();
    }

    private function canManage(array $ctx) {
        return $ctx['canCorrect'] || $ctx['canRequest'];
    }

    /** Programme must belong to the user's department (scoped users). */
    private function guardProgramme($id, array $ctx) {
        $p = $this->programmes()->getProgramme((int) $id);
        if (!$p) {
            $_SESSION['error'] = 'Programme not found.';
            $this->redirect('training-programmes');
            return null;
        }
        if ($ctx['scoped'] && (string) $p['department_id'] !== $ctx['departmentId']) {
            $_SESSION['error'] = 'Access denied. This programme belongs to another department.';
            $this->redirect('training-programmes');
            return null;
        }
        return $p;
    }

    private function listPage($view, $title, $page, array $extra = []) {
        $ctx = $this->requireAttendanceAccess();
        if (!$ctx) {
            return;
        }
        $filters = [
            'department_id' => $this->scopeDepartment($ctx),
            'status' => trim((string) $this->get('status', '')),
            'date_from' => $this->isoDate($this->get('from', ''), ''),
            'date_to' => $this->isoDate($this->get('to', ''), ''),
        ] + $extra;
        $rows = $this->programmes()->listProgrammes($filters);
        return $this->view($view, array_merge($this->flashPull(), [
            'title' => $title, 'page' => $page, 'ctx' => $ctx, 'rows' => $rows,
            'filters' => $filters, 'canManage' => $this->canManage($ctx),
            'departments' => $ctx['scoped'] ? [] : $this->model('DepartmentModel')->getAll(),
            'pageKey' => $page,
        ]));
    }

    /** Programme Schedule */
    public function index() {
        return $this->listPage('training_programmes/index', 'Programme Schedule', 'tp-schedule');
    }

    /** Training Activities: individual sessions by date */
    public function activities() {
        $ctx = $this->requireAttendanceAccess();
        if (!$ctx) {
            return;
        }
        $from = $this->isoDate($this->get('from', ''), date('Y-m-d'));
        $to = $this->isoDate($this->get('to', ''), date('Y-m-d', strtotime($from . ' +30 days')));
        $model = $this->programmes();
        $dept = $this->scopeDepartment($ctx);
        $sessions = [];
        foreach ($model->listProgrammes(['department_id' => $dept, 'date_from' => $from, 'date_to' => $to, 'published_only' => true]) as $p) {
            foreach ($model->schedules($p['id']) as $s) {
                if ($s['session_date'] >= $from && $s['session_date'] <= $to) {
                    $sessions[] = ['programme' => $p, 'schedule' => $s];
                }
            }
        }
        usort($sessions, static function ($a, $b) {
            return strcmp($a['schedule']['session_date'] . $a['schedule']['start_time'], $b['schedule']['session_date'] . $b['schedule']['start_time']);
        });
        return $this->view('training_programmes/activities', array_merge($this->flashPull(), [
            'title' => 'Training Activities', 'page' => 'tp-activities', 'ctx' => $ctx, 'sessions' => $sessions,
            'filters' => ['from' => $from, 'to' => $to],
        ]));
    }

    /** Practical Training: practical / industrial programmes */
    public function practical() {
        $ctx = $this->requireAttendanceAccess();
        if (!$ctx) {
            return;
        }
        $dept = $this->scopeDepartment($ctx);
        $model = $this->programmes();
        $rows = array_values(array_filter($model->listProgrammes(['department_id' => $dept]), static function ($p) {
            return in_array($p['training_type'], ['Practical', 'Industrial'], true);
        }));
        return $this->view('training_programmes/index', array_merge($this->flashPull(), [
            'title' => 'Practical Training', 'page' => 'tp-practical', 'ctx' => $ctx, 'rows' => $rows,
            'filters' => ['department_id' => $dept, 'status' => '', 'date_from' => '', 'date_to' => ''],
            'canManage' => $this->canManage($ctx), 'departments' => [], 'pageKey' => 'tp-practical',
        ]));
    }

    /** Training Progress: attendance progress per programme */
    public function progress() {
        $ctx = $this->requireAttendanceAccess();
        if (!$ctx) {
            return;
        }
        $dept = $this->scopeDepartment($ctx);
        $model = $this->programmes();
        $summary = [];
        foreach ($model->listProgrammes(['department_id' => $dept, 'status' => 'published']) as $p) {
            $rows = $model->progress($p['id']);
            $n = count($rows);
            $avg = 0.0;
            foreach ($rows as $r) {
                $avg += $r['percent'];
            }
            $summary[] = ['programme' => $p, 'participants' => $n, 'average' => $n > 0 ? round($avg / $n, 1) : 0.0, 'rows' => $rows];
        }
        return $this->view('training_programmes/progress', array_merge($this->flashPull(), [
            'title' => 'Training Progress', 'page' => 'tp-progress', 'ctx' => $ctx, 'summary' => $summary,
        ]));
    }

    public function create() {
        $ctx = $this->requireAttendanceAccess();
        if (!$ctx) {
            return;
        }
        if (!$this->canManage($ctx)) {
            $_SESSION['error'] = 'You do not have permission to schedule training programmes.';
            $this->redirect('training-programmes');
            return;
        }
        require_once BASE_PATH . '/models/GroupTimetableModel.php';
        $dept = $ctx['scoped'] ? $ctx['departmentId'] : trim((string) $this->get('department_id', $this->post('department_id', '')));
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->store($ctx, $dept);
        }
        $groups = $dept !== '' ? $this->visibleGroups($dept) : [];
        return $this->view('training_programmes/create', array_merge($this->flashPull(), [
            'title' => 'Schedule Training Programme', 'page' => 'tp-schedule', 'ctx' => $ctx,
            'department_id' => $dept, 'departments' => $ctx['scoped'] ? [] : $this->model('DepartmentModel')->getAll(),
            'groups' => $groups, 'staff' => (new GroupTimetableModel())->listStaff($dept),
            'courses' => $dept !== '' ? $this->model('CourseModel')->getCoursesWithDepartment(['department_id' => $dept]) : [],
            'csrf' => $this->csrfToken(),
            'old' => $_SESSION['tp_old'] ?? [],
        ]));
    }

    private function store(array $ctx, $dept) {
        if (!$this->verifyCsrf()) {
            $_SESSION['error'] = 'Your session expired. Please try again.';
            $this->redirect('training-programmes/create');
            return;
        }
        $_SESSION['tp_old'] = $_POST;
        $fail = function ($msg) {
            $_SESSION['error'] = $msg;
            $this->redirect('training-programmes/create' . (!empty($_POST['department_id']) ? '?department_id=' . urlencode((string) $_POST['department_id']) : ''));
        };
        $title = trim((string) $this->post('title', ''));
        $type = (string) $this->post('training_type', 'Practical');
        if ($dept === '') {
            return $fail('Select a department.');
        }
        if ($title === '' || mb_strlen($title) > 200) {
            return $fail('Enter a programme title (up to 200 characters).');
        }
        if (!isset(TrainingProgrammeModel::types()[$type])) {
            return $fail('Select a valid training type.');
        }
        $instructor = trim((string) $this->post('instructor_staff_id', ''));
        require_once BASE_PATH . '/models/GroupTimetableModel.php';
        if ($instructor !== '') {
            $ok = false;
            foreach ((new GroupTimetableModel())->listStaff($dept) as $s) {
                if ((string) $s['staff_id'] === $instructor) {
                    $ok = true;
                }
            }
            if (!$ok) {
                return $fail('Select an instructor from the department.');
            }
        }
        // schedule rows
        $dates = (array) $this->post('session_date', []);
        $starts = (array) $this->post('start_time', []);
        $ends = (array) $this->post('end_time', []);
        $locs = (array) $this->post('location', []);
        $schedule = [];
        require_once BASE_PATH . '/helpers/AttendanceRules.php';
        foreach ($dates as $i => $d) {
            $d = $this->isoDate($d, '');
            $st = AttendanceRules::hms($starts[$i] ?? '');
            $en = AttendanceRules::hms($ends[$i] ?? '');
            if ($d === '' && ($starts[$i] ?? '') === '' && ($ends[$i] ?? '') === '') {
                continue;
            }
            if ($d === '' || $st === '' || $en === '' || $st >= $en) {
                return $fail('Each session needs a date, a start time and a later end time.');
            }
            $schedule[] = ['date' => $d, 'start' => $st, 'end' => $en, 'location' => mb_substr(trim((string) ($locs[$i] ?? '')), 0, 120)];
        }
        if (!$schedule) {
            return $fail('Add at least one session.');
        }
        $model = $this->programmes();
        $conflicts = $model->instructorConflicts($instructor, $schedule);
        if ($conflicts) {
            return $fail(implode(' ', array_slice($conflicts, 0, 2)));
        }
        // participants: only groups of this department, students of this department
        $allowedGroups = [];
        foreach ($this->visibleGroups($dept) as $g) {
            $allowedGroups[(int) $g['id']] = true;
        }
        $groupIds = [];
        foreach ((array) $this->post('group_ids', []) as $g) {
            if (isset($allowedGroups[(int) $g])) {
                $groupIds[] = (int) $g;
            }
        }
        $studentIds = [];
        require_once BASE_PATH . '/models/AttendanceCorrectionModel.php';
        $corr = new AttendanceCorrectionModel();
        foreach (preg_split('/[\s,;]+/', (string) $this->post('student_ids', ''), -1, PREG_SPLIT_NO_EMPTY) as $sid) {
            if (!$corr->studentInDepartment($sid, $dept)) {
                return $fail('Student ' . $sid . ' was not found in this department.');
            }
            $studentIds[] = $sid;
        }
        $res = $model->createProgramme([
            'title' => $title,
            'description' => trim((string) $this->post('description', '')),
            'department_id' => $dept,
            'course_id' => trim((string) $this->post('course_id', '')) ?: null,
            'batch' => trim((string) $this->post('batch', '')) ?: null,
            'academic_year' => trim((string) $this->post('academic_year', '')) ?: null,
            'training_type' => $type,
            'instructor_staff_id' => $instructor !== '' ? $instructor : null,
            'learning_outcomes' => trim((string) $this->post('learning_outcomes', '')),
            'created_by' => $ctx['userName'],
        ], $schedule, $studentIds, $groupIds);
        if (!$res['ok']) {
            return $fail($res['message']);
        }
        unset($_SESSION['tp_old']);
        $_SESSION['message'] = $res['message'] . ' Publish it when the schedule is final.';
        $this->redirect('training-programmes/show?id=' . $res['id']);
    }

    public function show() {
        $ctx = $this->requireAttendanceAccess();
        if (!$ctx) {
            return;
        }
        $p = $this->guardProgramme((int) $this->get('id', 0), $ctx);
        if (!$p) {
            return;
        }
        $model = $this->programmes();
        $att = $p['status'] === 'draft' ? ['schedules' => $model->schedules($p['id']), 'participants' => $model->participants($p['id']), 'matrix' => [], 'totals' => []] : $model->attendance($p['id']);
        if ($this->get('export') === 'xlsx' && $p['status'] !== 'draft') {
            require_once BASE_PATH . '/helpers/ReportExport.php';
            $labels = ['present' => 'Present', 'partial' => 'Partial', 'absent' => 'Absent', 'upcoming' => 'Upcoming'];
            $rows = [];
            foreach ($att['participants'] as $pt) {
                foreach ($att['schedules'] as $sch) {
                    $rows[] = [
                        $p['title'], $p['training_type'], $sch['session_date'], substr($sch['start_time'], 0, 5) . '-' . substr($sch['end_time'], 0, 5),
                        $pt['student_id'], $pt['student_name'], $pt['group_name'] ?: '-',
                        $p['status'] === 'cancelled' ? 'Cancelled' : ($labels[$att['matrix'][$pt['student_id']][$sch['id']] ?? 'absent'] ?? ''),
                    ];
                }
            }
            ReportExport::xlsx('programme_' . (int) $p['id'], ['Programme', 'Type', 'Date', 'Time', 'Reg No', 'Name', 'Group', 'Attendance'], $rows, 'Programme');
        }
        $groups = $this->visibleGroups((string) $p['department_id']);
        return $this->view('training_programmes/show', array_merge($this->flashPull(), [
            'title' => $p['title'], 'page' => 'tp-schedule', 'ctx' => $ctx, 'p' => $p, 'att' => $att,
            'canManage' => $this->canManage($ctx), 'groups' => $groups, 'csrf' => $this->csrfToken(),
        ]));
    }

    public function status() {
        $ctx = $this->requireAttendanceAccess();
        if (!$ctx) {
            return;
        }
        $id = (int) $this->post('id', 0);
        $back = 'training-programmes/show?id=' . $id;
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf() || !$this->canManage($ctx)) {
            $_SESSION['error'] = 'You do not have permission to change this programme.';
            $this->redirect($back);
            return;
        }
        $p = $this->guardProgramme($id, $ctx);
        if (!$p) {
            return;
        }
        $action = $this->post('action', '');
        $model = $this->programmes();
        if ($action === 'publish') {
            $conf = $model->instructorConflicts((string) $p['instructor_staff_id'], array_map(static function ($s) {
                return ['date' => $s['session_date'], 'start' => $s['start_time'], 'end' => $s['end_time']];
            }, $model->schedules($id)), $id);
            if ($conf) {
                $_SESSION['error'] = 'Cannot publish: ' . implode(' ', array_slice($conf, 0, 2));
            } elseif (!$model->participants($id)) {
                $_SESSION['error'] = 'Add participants before publishing.';
            } else {
                if ($model->setStatus($id, 'published')) {
                    $_SESSION['message'] = 'Programme published.';
                } else {
                    $_SESSION['error'] = 'Only a draft programme can be published.';
                }
            }
        } elseif ($action === 'cancel') {
            $reason = trim((string) $this->post('reason', ''));
            if (mb_strlen($reason) < 5) {
                $_SESSION['error'] = 'Enter a reason for cancelling the programme.';
            } else {
                if ($model->setStatus($id, 'cancelled', $reason)) {
                    $_SESSION['message'] = 'Programme cancelled.';
                } else {
                    $_SESSION['error'] = 'The programme is already cancelled.';
                }
            }
        }
        $this->redirect($back);
    }

    public function participants() {
        $ctx = $this->requireAttendanceAccess();
        if (!$ctx) {
            return;
        }
        $id = (int) $this->post('id', 0);
        $back = 'training-programmes/show?id=' . $id;
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf() || !$this->canManage($ctx)) {
            $_SESSION['error'] = 'You do not have permission to change this programme.';
            $this->redirect($back);
            return;
        }
        $p = $this->guardProgramme($id, $ctx);
        if (!$p) {
            return;
        }
        if ($p['status'] === 'cancelled') {
            $_SESSION['error'] = 'A cancelled programme cannot be changed.';
            $this->redirect($back);
            return;
        }
        $model = $this->programmes();
        if ($this->post('action', '') === 'remove') {
            $model->removeParticipant($id, (string) $this->post('student_id', ''));
            $_SESSION['message'] = 'Participant removed.';
            $this->redirect($back);
            return;
        }
        require_once BASE_PATH . '/models/AttendanceCorrectionModel.php';
        $corr = new AttendanceCorrectionModel();
        $studentIds = [];
        foreach (preg_split('/[\s,;]+/', (string) $this->post('student_ids', ''), -1, PREG_SPLIT_NO_EMPTY) as $sid) {
            if ($corr->studentInDepartment($sid, (string) $p['department_id'])) {
                $studentIds[] = $sid;
            }
        }
        $allowed = [];
        foreach ($this->visibleGroups((string) $p['department_id']) as $g) {
            $allowed[(int) $g['id']] = true;
        }
        $groupIds = array_values(array_filter(array_map('intval', (array) $this->post('group_ids', [])), static function ($g) use ($allowed) { return isset($allowed[$g]); }));
        $added = $model->addParticipants($id, $studentIds, $groupIds);
        $_SESSION['message'] = $added . ' participant(s) added.';
        $this->redirect($back);
    }
}
