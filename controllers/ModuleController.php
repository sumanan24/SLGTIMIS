<?php
/**
 * Module Controller - list, view, add course modules
 */

class ModuleController extends Controller {

    public function index() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        if (!$this->checkNotSAO()) {
            return;
        }
        $moduleModel = $this->model('ModuleModel');
        $courseModel = $this->model('CourseModel');
        $courseId = trim($this->get('course_id', ''));
        $modules = $moduleModel->getAllWithCourse($courseId !== '' ? $courseId : null);
        $courses = $courseModel->getCoursesWithDepartment([]);
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        $isADM = ($userRole === 'ADM') || $userModel->isAdmin($_SESSION['user_id']);
        $isHOD = $this->isHOD();
        $canCreate = $isHOD || $isADM;
        $canEdit = $canCreate;
        $data = [
            'title' => 'Modules',
            'page' => 'modules',
            'modules' => $modules,
            'courses' => $courses,
            'filter_course_id' => $courseId,
            'canCreate' => $canCreate,
            'canEdit' => $canEdit,
            'message' => $_SESSION['message'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ];
        unset($_SESSION['message'], $_SESSION['error']);
        return $this->view('modules/index', $data);
    }

    public function show() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        if (!$this->checkNotSAO()) {
            return;
        }
        $courseId = trim($this->get('course_id', ''));
        $moduleId = trim($this->get('module_id', ''));
        $courseVersion = $this->get('course_version') !== null && $this->get('course_version') !== '' ? (int)$this->get('course_version') : null;
        if (empty($courseId) || empty($moduleId)) {
            $_SESSION['error'] = 'Course and module are required.';
            $this->redirect('modules');
            return;
        }
        $moduleModel = $this->model('ModuleModel');
        $module = $moduleModel->getByCourseAndModule($courseId, $moduleId, $courseVersion);
        if (!$module) {
            $_SESSION['error'] = 'Module not found.';
            $this->redirect('modules');
            return;
        }
        $data = [
            'title' => 'View Module',
            'page' => 'modules',
            'module' => $module
        ];
        return $this->view('modules/view', $data);
    }

    public function create() {
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
            $_SESSION['error'] = 'Only HOD and ADM can add modules.';
            $this->redirect('modules');
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $courseId = trim($this->post('course_id', ''));
            $courseVersion = (int) $this->post('course_version', 0);
            $moduleId = trim($this->post('module_id', ''));
            $moduleName = trim($this->post('module_name', ''));
            $credit = $this->post('credit');
            $credit = $credit === '' || $credit === null ? null : (float) $credit;
            if (empty($courseId) || empty($moduleId) || empty($moduleName)) {
                $_SESSION['error'] = 'Course, version, module ID and module name are required.';
                $this->redirect('modules/create');
                return;
            }
            $moduleModel = $this->model('ModuleModel');
            if ($moduleModel->exists($courseId, $moduleId, $courseVersion)) {
                $_SESSION['error'] = 'This module ID already exists for the selected course and version.';
                $this->redirect('modules/create');
                return;
            }
            $sqlError = null;
            $result = $moduleModel->createModule([
                'course_id' => $courseId,
                'course_version' => $courseVersion,
                'module_id' => $moduleId,
                'module_name' => $moduleName,
                'credit' => $credit
            ], $sqlError);
            if ($result !== false) {
                $_SESSION['message'] = 'Module added successfully.';
                $this->redirect('modules');
            } else {
                $_SESSION['error'] = $sqlError ?: 'Failed to add module.';
                $this->redirect('modules/create');
            }
        } else {
            $courseModel = $this->model('CourseModel');
            $courses = $courseModel->getCoursesWithDepartment([]);
            $versionsByCourse = [];
            foreach ($courses as $c) {
                $cid = $c['course_id'] ?? '';
                if ($cid === '') continue;
                $versions = [0];
                $rows = $courseModel->getVersionsForCourse($cid);
                foreach ($rows as $v) {
                    $versions[] = (int)($v['version_no'] ?? 0);
                }
                $versions = array_unique($versions);
                sort($versions, SORT_NUMERIC);
                $versionsByCourse[$cid] = $versions;
            }
            $data = [
                'title' => 'Add Module',
                'page' => 'modules',
                'courses' => $courses,
                'versionsByCourse' => $versionsByCourse,
                'error' => $_SESSION['error'] ?? null
            ];
            unset($_SESSION['error']);
            return $this->view('modules/create', $data);
        }
    }

    /**
     * Edit module (ADM and HOD). HOD restricted to own department's courses.
     */
    public function edit() {
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
            $_SESSION['error'] = 'Only HOD and ADM can edit modules.';
            $this->redirect('modules');
            return;
        }
        $courseId = trim($this->get('course_id', ''));
        $moduleId = trim($this->get('module_id', ''));
        $courseVersion = $this->get('course_version') !== null && $this->get('course_version') !== '' ? (int)$this->get('course_version') : 0;
        if (empty($courseId) || empty($moduleId)) {
            $_SESSION['error'] = 'Course and module are required.';
            $this->redirect('modules');
            return;
        }
        $moduleModel = $this->model('ModuleModel');
        $courseModel = $this->model('CourseModel');
        $module = $moduleModel->getByCourseAndModule($courseId, $moduleId, $courseVersion);
        if (!$module) {
            $_SESSION['error'] = 'Module not found.';
            $this->redirect('modules');
            return;
        }
        $course = $courseModel->getById($courseId);
        $hodDepartmentId = $this->getHODDepartment();
        if ($hodDepartmentId && $course && isset($course['department_id']) && $course['department_id'] !== $hodDepartmentId) {
            $_SESSION['error'] = 'Access denied. You can only edit modules for courses in your department.';
            $this->redirect('modules');
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $moduleName = trim($this->post('module_name', ''));
            $credit = $this->post('credit');
            $credit = $credit === '' || $credit === null ? null : (float)$credit;
            if (empty($moduleName)) {
                $_SESSION['error'] = 'Module name is required.';
                $this->redirect('modules/edit?course_id=' . urlencode($courseId) . '&module_id=' . urlencode($moduleId) . '&course_version=' . $courseVersion);
                return;
            }
            $sqlError = null;
            $ok = $moduleModel->updateModule($courseId, $moduleId, $courseVersion, [
                'module_name' => $moduleName,
                'credit' => $credit
            ], $sqlError);
            if ($ok) {
                $_SESSION['message'] = 'Module updated successfully.';
                $this->redirect('modules');
            } else {
                $_SESSION['error'] = $sqlError ?: 'Failed to update module.';
                $this->redirect('modules/edit?course_id=' . urlencode($courseId) . '&module_id=' . urlencode($moduleId) . '&course_version=' . $courseVersion);
            }
        } else {
            $data = [
                'title' => 'Edit Module',
                'page' => 'modules',
                'module' => $module,
                'error' => $_SESSION['error'] ?? null
            ];
            unset($_SESSION['error']);
            return $this->view('modules/edit', $data);
        }
    }

    /**
     * Delete module (ADM and HOD). HOD restricted to own department's courses.
     */
    public function delete() {
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
            $_SESSION['error'] = 'Only HOD and ADM can delete modules.';
            $this->redirect('modules');
            return;
        }
        $courseId = trim($this->get('course_id', ''));
        $moduleId = trim($this->get('module_id', ''));
        $courseVersion = $this->get('course_version') !== null && $this->get('course_version') !== '' ? (int)$this->get('course_version') : 0;
        if (empty($courseId) || empty($moduleId)) {
            $_SESSION['error'] = 'Course and module are required.';
            $this->redirect('modules');
            return;
        }
        $courseModel = $this->model('CourseModel');
        $course = $courseModel->getById($courseId);
        $hodDepartmentId = $this->getHODDepartment();
        if ($hodDepartmentId && $course && isset($course['department_id']) && $course['department_id'] !== $hodDepartmentId) {
            $_SESSION['error'] = 'Access denied. You can only delete modules for courses in your department.';
            $this->redirect('modules');
            return;
        }
        $moduleModel = $this->model('ModuleModel');
        if ($moduleModel->deleteByCourseAndModule($courseId, $moduleId, $courseVersion)) {
            $_SESSION['message'] = 'Module deleted successfully.';
        } else {
            $_SESSION['error'] = 'Failed to delete module or module not found.';
        }
        $this->redirect('modules');
    }
}
