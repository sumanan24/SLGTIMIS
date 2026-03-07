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
        
        $studentModel = $this->model('StudentModel');
        $attendanceModel = $this->model('AttendanceModel');
        $enrollmentModel = $this->model('StudentEnrollmentModel');
        $roomAllocationModel = $this->model('RoomAllocationModel');
        
        // Get current student
        $studentId = $_SESSION['user_name']; // Student ID is stored in user_name
        $student = $studentModel->find($studentId);
        
        if (!$student) {
            $_SESSION['error'] = 'Student record not found.';
            $this->redirect('logout');
            return;
        }
        
        // Get current enrollment
        $currentEnrollment = $enrollmentModel->getCurrentEnrollment($studentId);
        
        // Get hostel allocation
        $hostelAllocation = $roomAllocationModel->getActiveByStudentId($studentId);
        
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
            'hostelAllocation' => $hostelAllocation,
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
            'hasAcceptedConduct' => $hasAcceptedConduct
        ];
        
        return $this->view('student/dashboard', $data);
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

