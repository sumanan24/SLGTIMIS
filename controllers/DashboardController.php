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
        
        // Redirect HOD, IN1, IN2, IN3 users to their dedicated department dashboard
        if ($this->isDepartmentRestricted()) {
            $this->redirect('hod/dashboard');
            return;
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
            
            // Fetch all data once - filter by academic year if selected
            // Always filter for active students only (status 'Following')
            if (!empty($selectedAcademicYear)) {
                $totalStudents = $studentModel->getTotalStudents(['academic_year' => $selectedAcademicYear]);
            } else {
                // Count only active students (those with enrollment status 'Following')
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
            
            // Get total students for selected academic year (same as totalStudents when filtered)
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

            $inventorySummary = null;
            if (file_exists(BASE_PATH . '/models/InventoryModel.php')) {
                try {
                    require_once BASE_PATH . '/models/InventoryModel.php';
                    $inventoryModel = new InventoryModel();
                    if ($inventoryModel->tableExists()) {
                        $supportsLowStock = $inventoryModel->supportsLowStockCalculation();
                        $inventorySummary = [
                            'totalItems' => $inventoryModel->countItems(),
                            'lowStockCount' => $supportsLowStock
                                ? count($inventoryModel->getLowStockItems(null, 999))
                                : null,
                        ];
                    }
                } catch (Throwable $inventoryError) {
                    // Inventory is an optional widget and must not blank all dashboard statistics.
                    error_log('DashboardController::inventorySummary: ' . $inventoryError->getMessage());
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
                'academicYears' => $academicYears,
                'selectedAcademicYear' => $selectedAcademicYear,
                'inventorySummary' => $inventorySummary
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
            'academicYears' => [],
            'selectedAcademicYear' => '',
            'inventorySummary' => null,
            'error' => 'Unable to load some dashboard statistics. ' . $e->getMessage(),
        ];
    }
}

