<?php

/**
 * Staff Module Enrollment Controller
 * HOD (and department-restricted roles) can enroll staff to modules in their department.
 */
class StaffModuleEnrollmentController extends Controller
{
    /**
     * Show form and handle enrollment submission
     */
    public function create()
    {
        // Require login
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }

        // Prevent students
        if (isset($_SESSION['user_table']) && $_SESSION['user_table'] === 'student') {
            $_SESSION['error'] = 'Access denied.';
            $this->redirect('dashboard');
            return;
        }

        // Role check: allow HOD, IN1, IN2, IN3 for now (department-restricted roles)
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        $allowedRoles = ['HOD', 'IN1', 'IN2', 'IN3'];

        if (!in_array($userRole, $allowedRoles)) {
            $_SESSION['error'] = 'Access denied. Only HOD and department instructors can enroll staff to modules.';
            $this->redirect('dashboard');
            return;
        }

        // Determine department for current user
        $departmentId = $this->getUserDepartment();
        if (empty($departmentId)) {
            $_SESSION['error'] = 'Unable to determine your department for staff module enrollment.';
            $this->redirect('dashboard');
            return;
        }

        // Load required models
        $staffModel = $this->model('StaffModel');
        $courseModel = $this->model('CourseModel');
        $groupTimetableModel = $this->model('GroupTimetableModel');
        $staffModuleEnrollmentModel = $this->model('StaffModuleEnrollmentModel');
        // Ensure enrollment table exists before use
        $staffModuleEnrollmentModel->ensureTableStructure();
        $studentModel = $this->model('StudentModel');

        // Dropdown data
        // Staff limited to department
        $staffList = $staffModel->getStaffWithDepartment(1, 1000, '', $departmentId);

        // Courses limited to department
        $courses = $courseModel->getCoursesWithDepartment(['department_id' => $departmentId]);

        // Academic years using existing helper from StudentModel
        $academicYears = $studentModel->getAcademicYears();

        // Modules will normally be filtered by selected course on frontend via AJAX.
        // For initial load, we can leave modules empty and let JS populate when course is selected.
        $modules = [];

        // Handle POST: create / edit / delete enrollment
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $this->post('action', 'create');
            
            // Delete enrollment
            if ($action === 'delete') {
                $enrollmentId = trim($this->post('enrollment_id', ''));
                if ($enrollmentId === '') {
                    $_SESSION['error'] = 'Enrollment ID is required.';
                    $this->redirect('hod/staff-module-enroll');
                    return;
                }
                
                if ($staffModuleEnrollmentModel->delete($enrollmentId)) {
                    $_SESSION['message'] = 'Enrollment deleted successfully.';
                } else {
                    $_SESSION['error'] = 'Failed to delete enrollment.';
                }
                
                $this->redirect('hod/staff-module-enroll');
                return;
            }
            
            // Edit enrollment date
            if ($action === 'edit_date') {
                $enrollmentId = trim($this->post('enrollment_id', ''));
                $enrollmentDate = trim($this->post('enrollment_date', ''));
                
                if ($enrollmentId === '' || $enrollmentDate === '') {
                    $_SESSION['error'] = 'Enrollment ID and date are required.';
                    $this->redirect('hod/staff-module-enroll');
                    return;
                }
                
                $enrollDateTime = $enrollmentDate . ' 00:00:00';
                $ok = $staffModuleEnrollmentModel->update($enrollmentId, [
                    'staff_module_enrollment_date' => $enrollDateTime,
                ]);
                
                if ($ok) {
                    $_SESSION['message'] = 'Enrollment date updated successfully.';
                } else {
                    $_SESSION['error'] = 'Failed to update enrollment date.';
                }
                
                $this->redirect('hod/staff-module-enroll');
                return;
            }
            
            // Create new enrollment
            $staffId = trim($this->post('staff_id', ''));
            $courseId = trim($this->post('course_id', ''));
            $moduleId = trim($this->post('module_id', ''));
            $academicYear = trim($this->post('academic_year', ''));
            $enrollmentDate = trim($this->post('enrollment_date', ''));
            
            if ($staffId === '' || $courseId === '' || $moduleId === '' || $academicYear === '') {
                $_SESSION['error'] = 'All fields are required.';
                $this->redirect('hod/staff-module-enroll');
                return;
            }
            
            // Basic security: ensure selected staff belongs to this department
            $staff = $staffModel->getById($staffId);
            if (!$staff || ($staff['department_id'] ?? null) !== $departmentId) {
                $_SESSION['error'] = 'Invalid staff selected for your department.';
                $this->redirect('hod/staff-module-enroll');
                return;
            }
            
            // Optional: ensure course belongs to this department
            $course = $courseModel->getById($courseId);
            if (!$course || ($course['department_id'] ?? null) !== $departmentId) {
                $_SESSION['error'] = 'Invalid course selected for your department.';
                $this->redirect('hod/staff-module-enroll');
                return;
            }
            
            // Optional: ensure module belongs to this course
            $availableModules = $groupTimetableModel->getModulesByCourseId($courseId);
            $validModuleIds = array_column($availableModules, 'module_id');
            if (!in_array($moduleId, $validModuleIds, true)) {
                $_SESSION['error'] = 'Invalid module selected for the chosen course.';
                $this->redirect('hod/staff-module-enroll');
                return;
            }
            
            $enrollDateTime = $enrollmentDate !== '' ? ($enrollmentDate . ' 00:00:00') : date('Y-m-d H:i:s');
            
            $data = [
                'staff_id' => $staffId,
                'course_id' => $courseId,
                'module_id' => $moduleId,
                'academic_year' => $academicYear,
                'staff_module_enrollment_date' => $enrollDateTime,
            ];
            
            if ($staffModuleEnrollmentModel->enrollStaffToModule($data)) {
                $_SESSION['message'] = 'Staff enrolled to module successfully.';
            } else {
                $_SESSION['error'] = 'Failed to enroll staff to module.';
            }
            
            $this->redirect('hod/staff-module-enroll');
            return;
        }

        // Optional: load enrollment being edited
        $editId = (int)$this->get('edit_id', 0);
        $editingEnrollment = null;
        $selectedAcademicYear = $this->get('academic_year', '');
        if ($editId > 0) {
            $candidate = $staffModuleEnrollmentModel->find($editId);
            if ($candidate) {
                // Ensure this enrollment belongs to current department
                $staffForEnroll = $staffModel->getById($candidate['staff_id']);
                if ($staffForEnroll && ($staffForEnroll['department_id'] ?? null) === $departmentId) {
                    $editingEnrollment = $candidate;
                    if ($selectedAcademicYear === '' && !empty($candidate['academic_year'])) {
                        $selectedAcademicYear = $candidate['academic_year'];
                    }
                }
            }
        }

        // Existing enrollments list for this department (optional display)
        $enrollments = $staffModuleEnrollmentModel->getEnrollmentsByDepartment($departmentId, [
            'academic_year' => $selectedAcademicYear,
        ]);

        $data = [
            'title' => 'Staff Module Enrollment',
            'page' => 'hod-staff-module-enroll',
            'department_id' => $departmentId,
            'staffList' => $staffList,
            'courses' => $courses,
            'modules' => $modules,
            'academicYears' => $academicYears,
            'selectedAcademicYear' => $selectedAcademicYear,
            'enrollments' => $enrollments,
            'editingEnrollment' => $editingEnrollment,
        ];

        return $this->view('hod/staff_module_enroll', $data);
    }

    /**
     * AJAX: Get modules for a given course (for HOD/department roles)
     */
    public function getModulesByCourse()
    {
        if (!isset($_SESSION['user_id'])) {
            $this->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Only allow HOD and department-restricted roles to use this endpoint
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        $allowedRoles = ['HOD', 'IN1', 'IN2', 'IN3'];

        if (!in_array($userRole, $allowedRoles)) {
            $this->json(['success' => false, 'message' => 'Access denied'], 403);
        }

        $courseId = trim($this->get('course_id', ''));
        if ($courseId === '') {
            $this->json(['success' => false, 'message' => 'Course ID is required'], 400);
        }

        try {
            $timetableModel = $this->model('GroupTimetableModel');
            $modules = $timetableModel->getModulesByCourseId($courseId);
            $this->json(['success' => true, 'modules' => $modules]);
        } catch (Exception $e) {
            error_log('StaffModuleEnrollmentController::getModulesByCourse - ' . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Failed to load modules'], 500);
        }
    }
}

