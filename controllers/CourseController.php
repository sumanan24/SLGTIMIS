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
        
        require_once BASE_PATH . '/core/AccessControl.php';
        if (!AccessControl::can((int) $_SESSION['user_id'], 'courses', 'view')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Access denied. This section is not available for your role.']);
            exit;
        }
        
        $departmentId = $this->get('department_id', '');

        // Department-restricted roles (HOD / IN1-3) only get their own department
        if ($this->isDepartmentScoped()) {
            $departmentId = (string) $this->getUserDepartment();
        }
        
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
        if (!$this->requireModule('courses', 'view')) {
            return;
        }
        
        $courseModel = $this->model('CourseModel');
        $departmentModel = $this->model('DepartmentModel');
        
        // Get user's department if user is HOD, IN1, IN2, or IN3
        $userDepartmentId = $this->getUserDepartment();
        // Scoped = HOD / IN1-3, lecturers and department heads: own department only
        $deptRestricted = $this->isDepartmentScoped();
        
        // Get filter parameters
        $filters = [
            // A restricted user without a department must never fall back to "all departments"
            'department_id' => $userDepartmentId ? $userDepartmentId : ($deptRestricted ? '__none__' : $this->get('department_id', '')),
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
        } elseif ($deptRestricted) {
            $departments = [];
        } else {
            $departments = $departmentModel->getAll();
        }

        $courseCounts = $courseModel->getWorkspaceCounts(array_column($courses, 'course_id'));
        $myDepartment = ($userDepartmentId && !empty($departments)) ? $departments[0] : null;
        $departmentTotals = null;
        if ($myDepartment) {
            $allDeptCourses = $courseModel->getCoursesWithDepartment(['department_id' => $userDepartmentId]);
            $deptCounts = $courseModel->getWorkspaceCounts(array_column($allDeptCourses, 'course_id'));
            $departmentTotals = ['courses' => count($allDeptCourses), 'modules' => 0, 'groups' => 0, 'active' => 0];
            foreach ($allDeptCourses as $dc) {
                if (($dc['course_status'] ?? 'active') === 'active') {
                    $departmentTotals['active']++;
                }
            }
            foreach ($deptCounts as $dcCount) {
                $departmentTotals['modules'] += $dcCount['modules'];
                $departmentTotals['groups'] += $dcCount['groups'];
            }
        }
        
        // Check if user is department-restricted or ADM for edit permissions
        require_once BASE_PATH . '/models/UserModel.php';
        require_once BASE_PATH . '/core/AccessControl.php';
        $userModel = new UserModel();
        $isDepartmentRestricted = $this->isDepartmentRestricted();
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        $isADM = ($userRole === 'ADM') || $userModel->isAdmin($_SESSION['user_id']);
        $isHOD = $this->isDepartmentManager();
        $uid = (int) $_SESSION['user_id'];
        $canEdit = AccessControl::can($uid, 'courses', 'edit');
        $canCreate = AccessControl::can($uid, 'courses', 'add');
        
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
            'courseCounts' => $courseCounts,
            'myDepartment' => $myDepartment,
            'departmentTotals' => $departmentTotals,
            'deptRestricted' => $deptRestricted,
            'canEdit' => $canEdit,
            'canCreate' => $canCreate,
            'message' => $_SESSION['message'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ];
        
        unset($_SESSION['message'], $_SESSION['error']);
        return $this->view('courses/index', $data);
    }
    
    /**
     * Course workspace: one page to manage a course's versions, modules and groups.
     * Department-restricted users (HOD / IN1-3) can only open courses of their own department.
     */
    public function manage() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        if (!$this->requireDepartmentAssigned('dashboard')) {
            return;
        }
        if (!$this->requireModule('courses', 'view')) {
            return;
        }

        $id = trim((string) $this->get('id', ''));
        if ($id === '') {
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

        require_once BASE_PATH . '/models/UserModel.php';
        require_once BASE_PATH . '/core/AccessControl.php';
        $userModel = new UserModel();
        $uid = (int) $_SESSION['user_id'];
        $userRole = $userModel->getUserRole($uid);
        $isADM = ($userRole === 'ADM') || $userModel->isAdmin($uid);
        $isHOD = $this->isDepartmentManager();
        $deptRestricted = $this->isDepartmentRestricted();
        $userDepartmentId = $this->getUserDepartment();

        if ($this->isDepartmentScoped() && (!$userDepartmentId || ($course['department_id'] ?? '') !== $userDepartmentId)) {
            $_SESSION['error'] = 'Access denied. You can only manage courses from your own department.';
            $this->redirect('courses');
            return;
        }

        // Versions (0 = default / unversioned)
        $versionRows = $courseModel->getVersionsForCourse($id);
        $versionNumbers = [];
        foreach ($versionRows as $v) {
            $versionNumbers[] = (int) ($v['version_no'] ?? 0);
        }
        $versionNumbers[] = 0;
        $versionNumbers = array_values(array_unique($versionNumbers));
        rsort($versionNumbers, SORT_NUMERIC);

        $versionParam = $this->get('version', '');
        $versionFilter = ($versionParam === '' || $versionParam === null || !in_array((int) $versionParam, $versionNumbers, true))
            ? null
            : (int) $versionParam;

        $moduleModel = $this->model('ModuleModel');
        $allModules = $moduleModel->getAllWithCourseFiltered(['course_id' => $id]);
        $modules = $allModules;
        if ($versionFilter !== null) {
            $modules = array_values(array_filter($allModules, static fn($m) => (int) ($m['course_version'] ?? 0) === $versionFilter));
        }

        // Order by semester (unset last), then newest version first, then module id
        $sortModules = static function (array $a, array $b): int {
            $sa = ($a['semester'] ?? '') === '' || $a['semester'] === null ? PHP_INT_MAX : (int) $a['semester'];
            $sb = ($b['semester'] ?? '') === '' || $b['semester'] === null ? PHP_INT_MAX : (int) $b['semester'];
            return [$sa, -(int) ($a['course_version'] ?? 0), (string) $a['module_id']]
                <=> [$sb, -(int) ($b['course_version'] ?? 0), (string) $b['module_id']];
        };
        usort($modules, $sortModules);

        $canViewGroups = AccessControl::can($uid, 'groups', 'view');
        $allGroups = [];
        $groups = [];
        if ($canViewGroups) {
            $groupModel = $this->model('GroupModel');
            $allGroups = $groupModel->getAllWithDetails(null, $id);
            $groups = $allGroups;
            if ($versionFilter !== null) {
                $groups = array_values(array_filter($allGroups, static fn($g) => (int) ($g['course_version'] ?? 0) === $versionFilter));
            }
        }

        $studentTotal = 0;
        $creditTotal = 0.0;
        foreach ($groups as $g) {
            $studentTotal += (int) ($g['student_count'] ?? 0);
        }
        foreach ($modules as $m) {
            $creditTotal += (float) ($m['credit'] ?? 0);
        }

        $moduleCountByVersion = [];
        foreach ($allModules as $m) {
            $ver = (int) ($m['course_version'] ?? 0);
            $moduleCountByVersion[$ver] = ($moduleCountByVersion[$ver] ?? 0) + 1;
        }
        $groupCountByVersion = [];
        foreach ($allGroups as $g) {
            $ver = (int) ($g['course_version'] ?? 0);
            $groupCountByVersion[$ver] = ($groupCountByVersion[$ver] ?? 0) + 1;
        }

        $data = [
            'title' => 'Manage Course',
            'page' => 'courses',
            'course' => $course,
            'versionNumbers' => $versionNumbers,
            'versionFilter' => $versionFilter,
            'moduleCountByVersion' => $moduleCountByVersion,
            'groupCountByVersion' => $groupCountByVersion,
            'modules' => $modules,
            'groups' => $groups,
            'studentTotal' => $studentTotal,
            'creditTotal' => $creditTotal,
            'canViewGroups' => $canViewGroups,
            'canEditCourse' => AccessControl::can($uid, 'courses', 'edit'),
            'canManageVersions' => $isHOD || $isADM,
            'canEditModules' => $isADM || $deptRestricted || $isHOD,
            'canDeleteModules' => $isADM || $isHOD,
            'canManageGroups' => AccessControl::can($uid, 'groups', 'add'),
            'message' => $_SESSION['message'] ?? null,
            'error' => $_SESSION['error'] ?? null,
        ];
        unset($_SESSION['message'], $_SESSION['error']);
        return $this->view('courses/manage', $data);
    }

    public function create() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        if (!$this->requireDepartmentAssigned('dashboard')) {
            return;
        }
        
        if (!$this->requireModule('courses', 'add')) {
            return;
        }
        
        // Get HOD's department if user is HOD
        $hodDepartmentId = $this->getUserDepartment();
        
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
        if (!$this->requireDepartmentAssigned('dashboard')) {
            return;
        }
        
        if (!$this->requireModule('courses', 'edit')) {
            return;
        }
        
        $id = $this->get('id', '');
        if (empty($id)) {
            $_SESSION['error'] = 'Course ID is required.';
            $this->redirect('courses');
            return;
        }
        
        // Get HOD's department if user is HOD
        $hodDepartmentId = $this->getUserDepartment();
        
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
        if (!$this->requireDepartmentAssigned('dashboard')) {
            return;
        }
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $isHOD = $this->isDepartmentManager();
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
        $hodDepartmentId = $this->getUserDepartment();
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
        if (!$this->requireDepartmentAssigned('dashboard')) {
            return;
        }
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $isHOD = $this->isDepartmentManager();
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
        $hodDepartmentId = $this->getUserDepartment();
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
        if (!$this->requireDepartmentAssigned('dashboard')) {
            return;
        }
        
        if (!$this->requireModule('courses', 'delete')) {
            return;
        }
        
        $id = $this->get('id', '');
        if (empty($id)) {
            $_SESSION['error'] = 'Course ID is required.';
            $this->redirect('courses');
            return;
        }
        
        // Get HOD's department if user is HOD
        $hodDepartmentId = $this->getUserDepartment();
        
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
        
        // A course in use cannot be deleted: its students / groups would lose their course.
        $deps = $courseModel->getDependencyCounts($id);
        if ($deps['enrollments'] > 0 || $deps['groups'] > 0) {
            $_SESSION['error'] = 'This course cannot be deleted: it has ' . $deps['enrollments'] . ' student enrollment(s) and '
                . $deps['groups'] . ' group(s). Set its status to Inactive instead.';
            $this->redirect('courses');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Delete course (its unused modules and versions go with it)
            $result = $courseModel->deleteCourseWithContent($id);
            
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

