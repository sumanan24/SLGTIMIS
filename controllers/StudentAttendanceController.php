<?php
/**
 * Student Attendance Controller
 */

class StudentAttendanceController extends Controller {
    
    public function index() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Check if user is a student
        if (!isset($_SESSION['user_table']) || $_SESSION['user_table'] !== 'student') {
            $this->redirect('dashboard');
            return;
        }
        
        $studentModel = $this->model('StudentModel');
        $attendanceModel = $this->model('AttendanceModel');
        
        // Get current student
        $studentId = $_SESSION['user_name'];
        $student = $studentModel->find($studentId);
        
        if (!$student) {
            $_SESSION['error'] = 'Student record not found.';
            $this->redirect('logout');
            return;
        }
        
        // Get selected month (default to current month)
        $selectedMonth = $this->get('month', date('Y-m'));
        $startDate = $selectedMonth . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));
        
        // Get attendance records for the month
        $attendanceRecords = $attendanceModel->getAttendanceByStudentAndDateRange($studentId, $startDate, $endDate);
        
        // Generate calendar data
        $calendarData = $this->generateCalendarData($selectedMonth, $attendanceRecords);
        
        // Calculate statistics
        $stats = $this->calculateAttendanceStats($attendanceRecords, $selectedMonth);
        
        // Get previous and next months for navigation
        $prevMonth = date('Y-m', strtotime($selectedMonth . '-01 -1 month'));
        $nextMonth = date('Y-m', strtotime($selectedMonth . '-01 +1 month'));
        
        $data = [
            'title' => 'My Attendance',
            'page' => 'student-attendance',
            'student' => $student,
            'selectedMonth' => $selectedMonth,
            'calendarData' => $calendarData,
            'attendanceRecords' => $attendanceRecords,
            'stats' => $stats,
            'prevMonth' => $prevMonth,
            'nextMonth' => $nextMonth,
            'error' => $_SESSION['error'] ?? null,
            'message' => $_SESSION['message'] ?? null
        ];
        
        unset($_SESSION['error'], $_SESSION['message']);
        return $this->view('student/attendance', $data);
    }
    
    /**
     * Generate calendar data for a month
     */
    private function generateCalendarData($month, $attendanceRecords) {
        $firstDay = strtotime($month . '-01');
        $daysInMonth = date('t', $firstDay);
        $firstDayOfWeek = date('w', $firstDay);
        
        $calendar = [];
        $currentWeek = [];
        
        // Empty cells for days before month starts
        for ($i = 0; $i < $firstDayOfWeek; $i++) {
            $currentWeek[] = ['day' => null, 'date' => null, 'status' => null, 'isWeekend' => false];
        }
        
        // Calendar days
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = $month . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
            $dayOfWeek = date('w', strtotime($date));
            $isWeekend = ($dayOfWeek == 0 || $dayOfWeek == 6);
            $status = $attendanceRecords[$date] ?? null;
            
            $currentWeek[] = [
                'day' => $day,
                'date' => $date,
                'status' => $status,
                'isWeekend' => $isWeekend,
                'dayName' => date('D', strtotime($date))
            ];
            
            // Start new week when we reach Sunday
            if (count($currentWeek) == 7) {
                $calendar[] = $currentWeek;
                $currentWeek = [];
            }
        }
        
        // Fill remaining days in last week
        while (count($currentWeek) < 7) {
            $currentWeek[] = ['day' => null, 'date' => null, 'status' => null, 'isWeekend' => false];
        }
        if (count($currentWeek) > 0) {
            $calendar[] = $currentWeek;
        }
        
        return $calendar;
    }
    
    /**
     * Calculate attendance statistics
     */
    private function calculateAttendanceStats($attendanceRecords, $month) {
        $totalDays = 0;
        $presentDays = 0;
        $absentDays = 0;
        $holidayDays = 0;
        
        $firstDay = strtotime($month . '-01');
        $daysInMonth = date('t', $firstDay);
        
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = $month . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
            $dayOfWeek = date('w', strtotime($date));
            
            // Skip weekends
            if ($dayOfWeek == 0 || $dayOfWeek == 6) {
                continue;
            }
            
            $totalDays++;
            $status = $attendanceRecords[$date] ?? null;
            
            if ($status === 1) {
                $presentDays++;
            } elseif ($status === 0) {
                $absentDays++;
            } elseif ($status === -1) {
                $holidayDays++;
                $totalDays--; // Don't count holidays in total
            }
        }
        
        $attendancePercentage = $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 2) : 0;
        
        return [
            'totalDays' => $totalDays,
            'presentDays' => $presentDays,
            'absentDays' => $absentDays,
            'holidayDays' => $holidayDays,
            'attendancePercentage' => $attendancePercentage
        ];
    }
}

