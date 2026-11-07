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
            
            // Get academic years and set default to last one
            $academicYears = $studentModel->getAcademicYears();
            $selectedAcademicYear = $this->get('academic_year', '');
            
            // If no academic year selected, use the last one
            if (empty($selectedAcademicYear) && !empty($academicYears)) {
                $selectedAcademicYear = $academicYears[0]; // First one is the latest (DESC order)
            }
            
            // Same count as the Students page: Following + Internship for the selected year.
            if (!empty($selectedAcademicYear)) {
                $totalStudents = $studentModel->getTotalStudents(['academic_year' => $selectedAcademicYear]);
            } else {
                $totalStudents = $studentModel->getTotalStudents([]);
            }
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
            'error' => 'Unable to load some dashboard statistics. ' . $e->getMessage(),
        ];
    }
}

