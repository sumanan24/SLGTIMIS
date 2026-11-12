<?php
/**
 * Student Attendance Controller
 * Calendar shows fingerprint attendance (In–Out > 7 hours = Present)
 * and staff manual marks (manual overrides finger when saved).
 */

class StudentAttendanceController extends Controller {

    public function index() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }

        if (!isset($_SESSION['user_table']) || $_SESSION['user_table'] !== 'student') {
            $this->redirect('dashboard');
            return;
        }

        $studentModel = $this->model('StudentModel');
        $attendanceModel = $this->model('AttendanceModel');

        $studentId = (string) ($_SESSION['user_name'] ?? '');
        $student = $studentModel->find($studentId);

        if (!$student) {
            $_SESSION['error'] = 'Student record not found.';
            $this->redirect('logout');
            return;
        }

        $selectedMonth = $this->get('month', date('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', (string) $selectedMonth)) {
            $selectedMonth = date('Y-m');
        }
        $startDate = $selectedMonth . '-01';
        $endDate = date('Y-m-t', strtotime($startDate . ' 12:00:00'));

        require_once BASE_PATH . '/helpers/StudentAttendanceCalendarHelper.php';
        $pack = StudentAttendanceCalendarHelper::buildMonth((string) $studentId, (string) $selectedMonth, $attendanceModel);
        $calendarData = $pack['calendar'];
        $stats = $pack['stats'];

        $prevMonth = date('Y-m', strtotime($selectedMonth . '-01 -1 month'));
        $nextMonth = date('Y-m', strtotime($selectedMonth . '-01 +1 month'));

        $data = [
            'title' => 'My Attendance',
            'page' => 'student-attendance',
            'student' => $student,
            'selectedMonth' => $selectedMonth,
            'calendarData' => $calendarData,
            'stats' => $stats,
            'prevMonth' => $prevMonth,
            'nextMonth' => $nextMonth,
            'error' => $_SESSION['error'] ?? null,
            'message' => $_SESSION['message'] ?? null,
        ];

        unset($_SESSION['error'], $_SESSION['message']);
        return $this->view('student/attendance', $data);
    }
}
