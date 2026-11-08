<?php
/**
 * Dashboard Controller
 */

class DashboardController extends Controller {
    
    public function index() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Redirect students to student dashboard
        if (isset($_SESSION['user_table']) && $_SESSION['user_table'] === 'student') {
            $_SESSION['error'] = 'Access denied. Students must use the student portal.';
            $this->redirect('student/dashboard');
            return;
        }
        
        // HOD, instructors and lecturers only see their own department
        require_once BASE_PATH . '/models/UserModel.php';
        if ($this->isDepartmentRestricted() || (new UserModel())->usesDepartmentDashboard((int) $_SESSION['user_id'])) {
            $this->redirect('hod/dashboard');
            return;
        }

        // Finance staff (FIN / ACC) only see finance-related items
        if ((new UserModel())->usesFinanceDashboard((int) $_SESSION['user_id'])) {
            return $this->financeDashboard();
        }
        
        try {
            // Load models
            $studentModel = $this->model('StudentModel');
            $staffModel = $this->model('StaffModel');
            $courseModel = $this->model('CourseModel');
            
            // The dashboard totals cover every academic year (no year filter). Year-wise figures are
            // in the "Students by academic year" table.
            $academicYears = [];
            $selectedAcademicYear = '';
            
            // Same count as the Students page: Following + Internship across all years.
            $totalStudents = $studentModel->getTotalStudents([]);
            $totalStaff = $staffModel->count();
            $totalCourses = $courseModel->count();
            $recentStudents = $studentModel->getRecentStudents(5);
            
            // Fetch statistics filtered by academic year if selected
            $nvqStats = $studentModel->getStudentsByNVQLevel($selectedAcademicYear);
            $nvqStatsByDepartment = $studentModel->getStudentsByNVQLevelAndDepartment($selectedAcademicYear);
            $courseEnrollmentByDepartment = $studentModel->getCourseEnrollmentByDepartment($selectedAcademicYear);
            $religionStats = $studentModel->getStudentsByReligion($selectedAcademicYear);
            $genderStats = $studentModel->getStudentsByGender($selectedAcademicYear);
            $departmentStats = $studentModel->getStudentsByDepartment($selectedAcademicYear);
            $districtStats = $studentModel->getStudentsByDistrict($selectedAcademicYear);
            $provinceStats = $studentModel->getStudentsByProvince($selectedAcademicYear);
            $enrollStatusCounts = $studentModel->getStudentsByEnrollStatus($selectedAcademicYear);
            $totalStudentsByYear = $totalStudents;

            // Overview panels: year-wise students, hostel availability, staff by category.
            // Each loads on its own so one failing query never blanks the whole dashboard.
            require_once BASE_PATH . '/core/AccessControl.php';
            $uid = (int) $_SESSION['user_id'];
            $overviewErrors = [];

            $yearWiseStudents = [];
            $studyModeTotals = ['full_time' => 0, 'part_time' => 0];
            if (AccessControl::can($uid, 'students', 'view')) {
                try {
                    $overviewModel = $this->model('DashboardOverviewModel');
                    $studyModeTotals = $overviewModel->getStudyModeTotals();
                    $yearWiseStudents = $overviewModel->getStudentsByAcademicYear();
                } catch (Throwable $e) {
                    error_log('DashboardController year-wise students: ' . $e->getMessage());
                    $overviewErrors['students'] = 'Unable to load the academic year breakdown.';
                }
            }

            $hostelOverview = ['hostels' => [], 'totals' => []];
            try {
                $hostelOverview = $this->buildHostelOverview($this->model('RoomModel')->getOccupancyReport());
            } catch (Throwable $e) {
                error_log('DashboardController hostel overview: ' . $e->getMessage());
                $overviewErrors['hostel'] = 'Unable to load hostel availability.';
            }

            $canViewStaff = AccessControl::can($uid, 'staff', 'view');
            $staffBreakdown = null;
            if ($canViewStaff) {
                try {
                    $staffBreakdown = $this->model('DashboardOverviewModel')->getStaffBreakdown();
                } catch (Throwable $e) {
                    error_log('DashboardController staff breakdown: ' . $e->getMessage());
                    $overviewErrors['staff'] = 'Unable to load the staff breakdown.';
                }
            }
            
            // Final deduplication check - ensure each student appears only once
            $uniqueStudents = [];
            $seenIds = [];
            foreach ($recentStudents as $student) {
                $id = $student['student_id'] ?? null;
                if ($id && !in_array($id, $seenIds)) {
                    $uniqueStudents[] = $student;
                    $seenIds[] = $id;
                }
            }

            $data = [
                'title' => 'Dashboard',
                'page' => 'dashboard',
                'user_name' => $_SESSION['user_name'] ?? 'User',
                'totalStudents' => $totalStudents,
                'totalStudentsByYear' => $totalStudentsByYear,
                'totalStaff' => $totalStaff,
                'totalCourses' => $totalCourses,
                'recentStudents' => array_values($uniqueStudents),
                'nvqStats' => $nvqStats,
                'nvqStatsByDepartment' => $nvqStatsByDepartment,
                'courseEnrollmentByDepartment' => $courseEnrollmentByDepartment,
                'religionStats' => $religionStats,
                'genderStats' => $genderStats,
                'departmentStats' => $departmentStats,
                'districtStats' => $districtStats,
                'provinceStats' => $provinceStats,
                'enrollStatusCounts' => $enrollStatusCounts,
                'academicYears' => $academicYears,
                'selectedAcademicYear' => $selectedAcademicYear,
                'yearWiseStudents' => $yearWiseStudents,
                'studyModeTotals' => $studyModeTotals,
                'canViewStudents' => AccessControl::can($uid, 'students', 'view'),
                'hostelOverview' => $hostelOverview,
                'canViewStaff' => $canViewStaff,
                'canViewCourses' => AccessControl::can($uid, 'courses', 'view'),
                'canViewDepartments' => AccessControl::can($uid, 'departments', 'view'),
                'staffBreakdown' => $staffBreakdown,
                'overviewErrors' => $overviewErrors,
            ];
            
            return $this->view('dashboard/index', $data);
        } catch (Throwable $e) {
            error_log('DashboardController::index: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            $data = $this->fallbackDashboardData($e);
            try {
                return $this->view('dashboard/index', $data);
            } catch (Throwable $e2) {
                error_log('DashboardController::index view: ' . $e2->getMessage());
                return $this->view('errors/app', [
                    'title' => 'Dashboard Error',
                    'page' => 'dashboard',
                    'error' => 'Error loading dashboard: ' . $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Group the per-room occupancy rows into hostels with totals.
     *
     * @param list<array<string, mixed>> $rooms Rows from RoomModel::getOccupancyReport()
     * @return array{hostels: list<array<string, mixed>>, totals: array<string, int|float>}
     */
    private function buildHostelOverview(array $rooms): array {
        $hostels = [];
        $totals = ['hostels' => 0, 'rooms' => 0, 'capacity' => 0, 'allocated' => 0, 'available' => 0];

        foreach ($rooms as $room) {
            $hostelId = (string) ($room['hostel_id'] ?? '');
            if ($hostelId === '') {
                continue; // room not linked to a hostel
            }
            if (!isset($hostels[$hostelId])) {
                $hostels[$hostelId] = [
                    'id' => $hostelId,
                    'name' => (string) ($room['hostel_name'] ?? ''),
                    'gender' => (string) ($room['hostel_gender'] ?? ''),
                    'location' => (string) ($room['location'] ?? ''),
                    'blocks' => [],
                    'rooms' => 0, 'capacity' => 0, 'allocated' => 0, 'available' => 0,
                    'full_rooms' => 0, 'room_list' => [],
                ];
            }
            $capacity = (int) $room['capacity'];
            $allocated = (int) $room['allocated'];
            $available = max(0, $capacity - $allocated);
            $block = (string) ($room['block_name'] ?? '');

            $h = &$hostels[$hostelId];
            $h['blocks'][$block] = true;
            $h['rooms']++;
            $h['capacity'] += $capacity;
            $h['allocated'] += $allocated;
            $h['available'] += $available;
            if ($available === 0) {
                $h['full_rooms']++;
            }
            $h['room_list'][] = [
                'block' => $block,
                'room' => (string) ($room['room_no'] ?? ''),
                'capacity' => $capacity,
                'allocated' => $allocated,
                'available' => $available,
            ];
            unset($h);

            $totals['rooms']++;
            $totals['capacity'] += $capacity;
            $totals['allocated'] += $allocated;
            $totals['available'] += $available;
        }

        foreach ($hostels as &$h) {
            $h['blocks'] = count($h['blocks']);
            $h['occupancy'] = $h['capacity'] > 0 ? round($h['allocated'] / $h['capacity'] * 100) : 0;
        }
        unset($h);

        $totals['hostels'] = count($hostels);
        $totals['occupancy'] = $totals['capacity'] > 0 ? round($totals['allocated'] / $totals['capacity'] * 100) : 0;

        return ['hostels' => array_values($hostels), 'totals' => $totals];
    }

    private function financeDashboard() {
        require_once BASE_PATH . '/core/AccessControl.php';
        $uid = (int) $_SESSION['user_id'];
        $data = [
            'title' => 'Finance Dashboard',
            'page' => 'dashboard',
            'user_name' => $_SESSION['user_name'] ?? 'User',
            'canManagePayments' => AccessControl::can($uid, 'payments', 'add'),
            'canViewPayments' => AccessControl::can($uid, 'payments', 'view'),
            'error' => null,
        ];
        try {
            $data += $this->model('PaymentModel')->getFinanceDashboard();
        } catch (Throwable $e) {
            error_log('DashboardController::financeDashboard: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            $data['error'] = 'Unable to load the finance figures right now.';
        }
        return $this->view('dashboard/finance', $data);
    }

    /**
     * @return array<string, mixed>
     */
    private function fallbackDashboardData(Throwable $e): array {
        return [
            'title' => 'Dashboard',
            'page' => 'dashboard',
            'user_name' => $_SESSION['user_name'] ?? 'User',
            'totalStudents' => 0,
            'totalStudentsByYear' => 0,
            'totalStaff' => 0,
            'totalCourses' => 0,
            'recentStudents' => [],
            'nvqStats' => [],
            'nvqStatsByDepartment' => [],
            'courseEnrollmentByDepartment' => [],
            'religionStats' => [],
            'genderStats' => [],
            'departmentStats' => [],
            'districtStats' => [],
            'provinceStats' => [],
            'enrollStatusCounts' => [],
            'academicYears' => [],
            'selectedAcademicYear' => '',
            'yearWiseStudents' => [],
            'studyModeTotals' => ['full_time' => 0, 'part_time' => 0],
            'canViewStudents' => false,
            'hostelOverview' => ['hostels' => [], 'totals' => []],
            'canViewStaff' => false,
            'canViewCourses' => false,
            'canViewDepartments' => false,
            'staffBreakdown' => null,
            'overviewErrors' => [],
            'error' => 'Unable to load some dashboard statistics. ' . $e->getMessage(),
        ];
    }
}

