<?php
/**
 * Course Controller
 */

class CourseController extends Controller {
    
    public function getByDepartment() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }
        
        // Restrict SAO users
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        if ($userModel->isSAO($_SESSION['user_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Access denied. This section is not available for your role.']);
            exit;
        }
        
        $departmentId = $this->get('department_id', '');
        
        if (empty($departmentId)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'courses' => []]);
            exit;
        }
        
        $courseModel = $this->model('CourseModel');
        $courses = $courseModel->all("course_name ASC");
        
        // Filter courses by department
        $filteredCourses = array_filter($courses, function($course) use ($departmentId) {
            return isset($course['department_id']) && $course['department_id'] === $departmentId;
        });
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'courses' => array_values($filteredCourses)]);
        exit;
    }
    
    public function index() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Restrict SAO users
        if (!$this->checkNotSAO()) {
            return;
        }
        
        $courseModel = $this->model('CourseModel');
        $departmentModel = $this->model('DepartmentModel');
        
        // Get user's department if user is HOD, IN1, IN2, or IN3
        $userDepartmentId = $this->getUserDepartment();
        
        // Get filter parameters
        $filters = [
            'department_id' => $userDepartmentId ? $userDepartmentId : $this->get('department_id', ''),
            'nvq_level' => $this->get('nvq_level', ''),
            'search' => $this->get('search', ''),
            'course_status' => $this->get('course_status', ''),
        ];
        
        // Remove empty filters
        $filters = array_filter($filters, function($value) {
            return $value !== '';
        });

        $perPage = 20;
        $page = max(1, (int) $this->get('page', 1));
        $total = $courseModel->getTotalCoursesWithDepartment($filters);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $currentPage = min($page, $totalPages);

        // Get filtered courses (paginated)
        $courses = $courseModel->getCoursesWithDepartmentPage($filters, $currentPage, $perPage);
        
        // Get departments for filter dropdown - only show user's department if department-restricted
        if ($userDepartmentId) {
            $dept = $departmentModel->getById($userDepartmentId);
            $departments = $dept ? [$dept] : [];
        } else {
            $departments = $departmentModel->getAll();
        }
        
        // Check if user is department-restricted or ADM for edit permissions
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $isDepartmentRestricted = $this->isDepartmentRestricted();
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        $isADM = ($userRole === 'ADM') || $userModel->isAdmin($_SESSION['user_id']);
        $isHOD = $this->isHOD();
        $canEdit = $isDepartmentRestricted || $isADM;
        $canCreate = $isHOD || $isADM; // Only HOD and ADM can create courses
        
        $data = [
            'title' => 'Courses',
            'page' => 'courses',
            'courses' => $courses,
            'departments' => $departments,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'total' => $total,
            'perPage' => $perPage,
            'filters' => [
                'department_id' => $this->get('department_id', ''),
                'nvq_level' => $this->get('nvq_level', ''),
                'search' => $this->get('search', ''),
                'course_status' => $this->get('course_status', ''),
            ],
            'isHOD' => $isHOD,
            'canEdit' => $canEdit,
            'canCreate' => $canCreate,
            'message' => $_SESSION['message'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ];
        
        unset($_SESSION['message'], $_SESSION['error']);
        return $this->view('courses/index', $data);
    }
    
    public function create() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Only HOD and ADM can create courses
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $isHOD = $this->isHOD();
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        $isADM = ($userRole === 'ADM') || $userModel->isAdmin($_SESSION['user_id']);
        
        if (!$isHOD && !$isADM) {
            $_SESSION['error'] = 'Access denied. Only Head of Department (HOD) and Administrators (ADM) can create courses.';
            $this->redirect('courses');
            return;
        }
        
        // Get HOD's department if user is HOD
        $hodDepartmentId = $this->getHODDepartment();
        
        $departmentModel = $this->model('DepartmentModel');
        
        // For HOD users, only show their department
        if ($hodDepartmentId) {
            $dept = $departmentModel->getById($hodDepartmentId);
            $departments = $dept ? [$dept] : [];
        } else {
            $departments = $departmentModel->getAll();
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $courseModel = $this->model('CourseModel');
            
            $course_id = trim($this->post('course_id', ''));
            $course_name = trim($this->post('course_name', ''));
            $course_nvq_level = $this->post('course_nvq_level', '');
            $course_ojt_duration = (int)$this->post('course_ojt_duration', 0);
            $course_institute_training = (int)$this->post('course_institute_training', 0);
            $department_id = trim($this->post('department_id', ''));
            $course_status = trim($this->post('course_status', CourseModel::STATUS_DRAFT));

            // For HOD users, force their department
            if ($hodDepartmentId) {
                $department_id = $hodDepartmentId;
            }

            // Validation
            if (empty($course_id) || empty($course_name) || empty($course_nvq_level) ||
                empty($department_id) || $course_ojt_duration <= 0 || $course_institute_training <= 0) {
                $_SESSION['error'] = 'All fields are required and must be valid.';
                $this->redirect('courses/create');
                return;
            }
            
            // Check if course ID already exists
            if ($courseModel->exists($course_id)) {
                $_SESSION['error'] = 'Course ID already exists.';
                $this->redirect('courses/create');
                return;
            }
            
            // Check if department exists
            if (!$departmentModel->exists($department_id)) {
                $_SESSION['error'] = 'Selected department does not exist.';
                $this->redirect('courses/create');
                return;
            }
            
            // Create course
            $courseData = [
                'course_id' => $course_id,
                'course_name' => $course_name,
                'course_nvq_level' => $course_nvq_level,
                'course_ojt_duration' => $course_ojt_duration,
                'course_institute_training' => $course_institute_training,
                'department_id' => $department_id,
                'course_status' => $courseModel->normalizeStatus($course_status),
            ];
            
            $result = $courseModel->createCourse($courseData);
            
            if ($result) {
                // Log activity
                $this->logActivity(
                    'CREATE',
                    'course',
                    $course_id,
                    "Course created: {$course_name} ({$course_id})",
                    null,
                    $courseData
                );
                
                $_SESSION['message'] = 'Course created successfully.';
                $this->redirect('courses');
            } else {
                $_SESSION['error'] = 'Failed to create course.';
                $this->redirect('courses/create');
            }
        } else {
            $departmentModel = $this->model('DepartmentModel');
            
            // For HOD users, only show their department
            if ($hodDepartmentId) {
                $dept = $departmentModel->getById($hodDepartmentId);
                $departments = $dept ? [$dept] : [];
            } else {
                $departments = $departmentModel->getAll();
            }
            
            $data = [
                'title' => 'Create Course',
                'page' => 'courses',
                'departments' => $departments,
                'hodDepartmentId' => $hodDepartmentId,
                'error' => $_SESSION['error'] ?? null
            ];
            unset($_SESSION['error']);
            return $this->view('courses/create', $data);
        }
    }
    
    public function edit() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Only HOD and ADM can edit courses
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $isHOD = $this->isHOD();
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        $isADM = ($userRole === 'ADM') || $userModel->isAdmin($_SESSION['user_id']);
        
        if (!$isHOD && !$isADM) {
            $_SESSION['error'] = 'Access denied. Only Head of Department (HOD) and Administrators (ADM) can edit courses.';
            $this->redirect('courses');
            return;
        }
        
        $id = $this->get('id', '');
        if (empty($id)) {
            $_SESSION['error'] = 'Course ID is required.';
            $this->redirect('courses');
            return;
        }
        
        // Get HOD's department if user is HOD
        $hodDepartmentId = $this->getHODDepartment();
        
        $courseModel = $this->model('CourseModel');
        $course = $courseModel->getById($id);
        
        if (!$course) {
            $_SESSION['error'] = 'Course not found.';
            $this->redirect('courses');
            return;
        }
        
        // Check if HOD is trying to edit a course from another department
        if ($hodDepartmentId && isset($course['department_id']) && $course['department_id'] !== $hodDepartmentId) {
            $_SESSION['error'] = 'Access denied. You can only edit courses from your own department.';
            $this->redirect('courses');
            return;
        }
        
        $departmentModel = $this->model('DepartmentModel');
        
        // For HOD users, only show their department
        if ($hodDepartmentId) {
            $dept = $departmentModel->getById($hodDepartmentId);
            $departments = $dept ? [$dept] : [];
        } else {
            $departments = $departmentModel->getAll();
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $course_name = trim($this->post('course_name', ''));
            $course_nvq_level = $this->post('course_nvq_level', '');
            $course_ojt_duration = (int)$this->post('course_ojt_duration', 0);
            $course_institute_training = (int)$this->post('course_institute_training', 0);
            $department_id = trim($this->post('department_id', ''));
            $course_status = trim($this->post('course_status', CourseModel::STATUS_DRAFT));
            
            // For HOD users, force their department
            if ($hodDepartmentId) {
                $department_id = $hodDepartmentId;
            }
            
            // Validation
            if (empty($course_name) || empty($course_nvq_level) || 
                empty($department_id) || $course_ojt_duration <= 0 || $course_institute_training <= 0) {
                $_SESSION['error'] = 'All fields are required and must be valid.';
                $this->redirect('courses/edit?id=' . urlencode($id));
                return;
            }
            
            // Check if department exists
            if (!$departmentModel->exists($department_id)) {
                $_SESSION['error'] = 'Selected department does not exist.';
                $this->redirect('courses/edit?id=' . urlencode($id));
                return;
            }
            
            // Update course
            $result = $courseModel->updateCourse($id, [
                'course_name' => $course_name,
                'course_nvq_level' => $course_nvq_level,
                'course_ojt_duration' => $course_ojt_duration,
                'course_institute_training' => $course_institute_training,
                'department_id' => $department_id,
                'course_status' => $courseModel->normalizeStatus($course_status),
            ]);
            
            if ($result) {
                $_SESSION['message'] = 'Course updated successfully.';
                $this->redirect('courses');
            } else {
                $_SESSION['error'] = 'Failed to update course.';
                $this->redirect('courses/edit?id=' . urlencode($id));
            }
        } else {
            $courseModel = $this->model('CourseModel');
            $versions = $courseModel->getVersionsForCourse($id);
            $latestVersion = $courseModel->getLatestVersionForCourse($id);
            $data = [
                'title' => 'Edit Course',
                'page' => 'courses',
                'course' => $course,
                'departments' => $departments,
                'versions' => $versions,
                'latestVersion' => $latestVersion,
                'message' => $_SESSION['message'] ?? null,
                'error' => $_SESSION['error'] ?? null
            ];
            unset($_SESSION['message'], $_SESSION['error']);
            return $this->view('courses/edit', $data);
        }
    }

    /**
     * Create a new version for a course (same course code, incremented version number).
     * New student enrollments will use this latest version.
     */
    public function newVersion() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $isHOD = $this->isHOD();
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        $isADM = ($userRole === 'ADM') || $userModel->isAdmin($_SESSION['user_id']);
        if (!$isHOD && !$isADM) {
            $_SESSION['error'] = 'Access denied. Only HOD and ADM can create course versions.';
            $this->redirect('courses');
            return;
        }
        $id = trim($this->get('id', ''));
        if (empty($id)) {
            $_SESSION['error'] = 'Course ID is required.';
            $this->redirect('courses');
            return;
        }
        $courseModel = $this->model('CourseModel');
        $course = $courseModel->getById($id);
        if (!$course) {
            $_SESSION['error'] = 'Course not found.';
            $this->redirect('courses');
            return;
        }
        $hodDepartmentId = $this->getHODDepartment();
        if ($hodDepartmentId && isset($course['department_id']) && $course['department_id'] !== $hodDepartmentId) {
            $_SESSION['error'] = 'Access denied. You can only manage versions for courses in your department.';
            $this->redirect('courses');
            return;
        }
        $newVersionNo = $courseModel->createNewVersion($id);
        if ($newVersionNo > 0) {
            $_SESSION['message'] = "Course version added: {$newVersionNo}. New enrollments will use this version.";
            $this->redirect('courses/edit?id=' . urlencode($id));
        } else {
            $_SESSION['error'] = 'Failed to add course version.';
            $this->redirect('courses/edit?id=' . urlencode($id));
        }
    }

    /**
     * Remove a course version (same access as newVersion).
     */
    public function removeVersion() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $isHOD = $this->isHOD();
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        $isADM = ($userRole === 'ADM') || $userModel->isAdmin($_SESSION['user_id']);
        if (!$isHOD && !$isADM) {
            $_SESSION['error'] = 'Access denied. Only HOD and ADM can remove course versions.';
            $this->redirect('courses');
            return;
        }
        $id = trim($this->get('id', ''));
        $versionNo = (int)$this->get('version_no', 0);
        if (empty($id) || $versionNo < 1) {
            $_SESSION['error'] = 'Course ID and version number are required.';
            $this->redirect('courses');
            return;
        }
        $courseModel = $this->model('CourseModel');
        $course = $courseModel->getById($id);
        if (!$course) {
            $_SESSION['error'] = 'Course not found.';
            $this->redirect('courses');
            return;
        }
        $hodDepartmentId = $this->getHODDepartment();
        if ($hodDepartmentId && isset($course['department_id']) && $course['department_id'] !== $hodDepartmentId) {
            $_SESSION['error'] = 'Access denied. You can only manage versions for courses in your department.';
            $this->redirect('courses');
            return;
        }
        if ($courseModel->deleteVersion($id, $versionNo)) {
            $_SESSION['message'] = "Course version {$versionNo} removed.";
            $this->redirect('courses/edit?id=' . urlencode($id));
        } else {
            $_SESSION['error'] = 'Failed to remove version or version not found.';
            $this->redirect('courses/edit?id=' . urlencode($id));
        }
    }
    
    public function delete() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Only HOD and ADM can delete courses
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $isHOD = $this->isHOD();
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        $isADM = ($userRole === 'ADM') || $userModel->isAdmin($_SESSION['user_id']);
        
        if (!$isHOD && !$isADM) {
            $_SESSION['error'] = 'Access denied. Only Head of Department (HOD) and Administrators (ADM) can delete courses.';
            $this->redirect('courses');
            return;
        }
        
        $id = $this->get('id', '');
        if (empty($id)) {
            $_SESSION['error'] = 'Course ID is required.';
            $this->redirect('courses');
            return;
        }
        
        // Get HOD's department if user is HOD
        $hodDepartmentId = $this->getHODDepartment();
        
        $courseModel = $this->model('CourseModel');
        $course = $courseModel->getById($id);
        
        // Check if HOD is trying to delete a course from another department
        if ($hodDepartmentId && isset($course['department_id']) && $course['department_id'] !== $hodDepartmentId) {
            $_SESSION['error'] = 'Access denied. You can only delete courses from your own department.';
            $this->redirect('courses');
            return;
        }
        
        if (!$course) {
            $_SESSION['error'] = 'Course not found.';
            $this->redirect('courses');
            return;
        }
        
        // Store old values for logging
        $oldValues = $course;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Delete course
            $result = $courseModel->deleteCourse($id);
            
            if ($result) {
                // Log activity
                $this->logActivity(
                    'DELETE',
                    'course',
                    $id,
                    "Course deleted: {$course['course_name']} ({$id})",
                    $oldValues,
                    null
                );
                
                $_SESSION['message'] = 'Course deleted successfully.';
            } else {
                $_SESSION['error'] = 'Failed to delete course.';
            }
            
            $this->redirect('courses');
        } else {
            $data = [
                'title' => 'Delete Course',
                'page' => 'courses',
                'course' => $course
            ];
            return $this->view('courses/delete', $data);
        }
    }
}

