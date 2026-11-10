<?php
/**
 * Student portal (view only): published timetable, biometric attendance and training programmes.
 */
class StudentAcademicController extends Controller {

    /** @return array<string,mixed>|null */
    private function currentStudent() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return null;
        }
        if (($_SESSION['user_table'] ?? '') !== 'student') {
            $this->redirect('dashboard');
            return null;
        }
        $student = $this->model('StudentModel')->find($_SESSION['user_name']);
        if (!$student) {
            $_SESSION['error'] = 'Student record not found.';
            $this->redirect('logout');
            return null;
        }
        return $student;
    }

    private function activeGroup($studentId) {
        require_once BASE_PATH . '/models/GroupModel.php';
        return (new GroupModel())->getActiveGroupOfStudent($studentId);
    }

    /** Weekly timetable of the student's group (published sessions only). */
    public function timetable() {
        $student = $this->currentStudent();
        if (!$student) {
            return;
        }
        require_once BASE_PATH . '/models/GroupTimetableModel.php';
        $anchor = DateTime::createFromFormat('Y-m-d', (string) $this->get('week', date('Y-m-d')));
        if (!$anchor) {
            $anchor = new DateTime();
        }
        $monday = (clone $anchor)->modify('monday this week');
        $group = $this->activeGroup($student['student_id']);
        $days = [];
        if ($group) {
            $model = new GroupTimetableModel();
            for ($i = 0; $i < 6; $i++) {
                $d = (clone $monday)->modify('+' . $i . ' day')->format('Y-m-d');
                $days[$d] = $model->sessionsForDate($d, ['group_id' => (int) $group['id']]);
            }
        }
        $data = [
            'title' => 'My Timetable', 'page' => 'student-timetable', 'student' => $student, 'group' => $group, 'days' => $days,
            'prevWeek' => (clone $monday)->modify('-7 day')->format('Y-m-d'),
            'nextWeek' => (clone $monday)->modify('+7 day')->format('Y-m-d'),
            'weekStart' => $monday->format('Y-m-d'),
            'error' => $_SESSION['error'] ?? null, 'message' => $_SESSION['message'] ?? null,
        ];
        unset($_SESSION['error'], $_SESSION['message']);
        return $this->view('student/timetable', $data);
    }

    /** Biometric daily attendance and class attendance of the month (read only). */
    public function biometricAttendance() {
        $student = $this->currentStudent();
        if (!$student) {
            return;
        }
        require_once BASE_PATH . '/helpers/AttendanceRules.php';
        require_once BASE_PATH . '/models/DailyAttendanceModel.php';
        require_once BASE_PATH . '/models/GroupTimetableModel.php';
        $month = (string) $this->get('month', date('Y-m'));
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            $month = date('Y-m');
        }
        $sid = (string) $student['student_id'];
        $group = $this->activeGroup($sid);

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT c.department_id FROM `student_enroll` se INNER JOIN `course` c ON c.course_id = se.course_id
            WHERE se.student_id = ? ORDER BY se.student_enroll_date DESC LIMIT 1");
        $dept = '';
        if ($stmt) {
            $stmt->bind_param('s', $sid);
            $stmt->execute();
            $r = $stmt->get_result()->fetch_assoc();
            $dept = $r ? (string) $r['department_id'] : '';
        }

        $daily = new DailyAttendanceModel();
        $summary = $daily->monthSummary($month, [[
            'student_id' => $sid, 'student_name' => (string) ($student['student_fullname'] ?? ''),
            'group_name' => $group['name'] ?? '', 'course_name' => '',
        ]], $dept);
        $row = $summary['rows'][0] ?? null;
        $dayMap = $row['days'] ?? [];

        // Class attendance follows the effective daily result for every published session of the group.
        $sessions = [];
        if ($group && $dayMap) {
            $first = $month . '-01';
            $to = min(date('Y-m-t', strtotime($first)), AttendanceRules::today());
            foreach ((new GroupTimetableModel())->sessionsForGroupRange((int) $group['id'], $first, $to) as $date => $list) {
                if (!isset($dayMap[$date])) {
                    continue;
                }
                foreach ($list as $s) {
                    $sessions[] = ['date' => $date, 'session' => $s, 'status' => $dayMap[$date]];
                }
            }
            usort($sessions, static function ($a, $b) {
                return strcmp($b['date'] . $b['session']['start_clock'], $a['date'] . $a['session']['start_clock']);
            });
        }
        $data = [
            'title' => 'Biometric Attendance', 'page' => 'student-attendance', 'student' => $student, 'group' => $group,
            'month' => $month, 'summary' => $row, 'dayMap' => $dayMap, 'sessions' => $sessions,
            'prevMonth' => date('Y-m', strtotime($month . '-01 -1 month')),
            'nextMonth' => date('Y-m', strtotime($month . '-01 +1 month')),
            'error' => $_SESSION['error'] ?? null, 'message' => $_SESSION['message'] ?? null,
        ];
        unset($_SESSION['error'], $_SESSION['message']);
        return $this->view('student/biometric_attendance', $data);
    }

    /** Published training programmes the student takes part in. */
    public function programmes() {
        $student = $this->currentStudent();
        if (!$student) {
            return;
        }
        require_once BASE_PATH . '/models/TrainingProgrammeModel.php';
        $model = new TrainingProgrammeModel();
        $sid = (string) $student['student_id'];
        $items = [];
        foreach ($model->listProgrammes(['student_id' => $sid, 'published_only' => true]) as $p) {
            $items[] = ['programme' => $p, 'schedules' => $model->schedules($p['id'])];
        }
        $data = [
            'title' => 'Training Programmes', 'page' => 'student-programmes', 'student' => $student, 'items' => $items,
            'today' => date('Y-m-d'),
            'error' => $_SESSION['error'] ?? null, 'message' => $_SESSION['message'] ?? null,
        ];
        unset($_SESSION['error'], $_SESSION['message']);
        return $this->view('student/training_programmes', $data);
    }
}
