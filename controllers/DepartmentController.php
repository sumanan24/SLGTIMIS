<?php
/**
 * Department Controller
 */

require_once BASE_PATH . '/models/DepartmentModel.php';

class DepartmentController extends Controller {

    /**
     * Assign (or replace) the one person responsible for a department.
     * Types: permanent, acting, cover_up. Admin only.
     */
    public function assignHead() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        if (!$this->requireModule('departments', 'edit')) {
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->validCsrf()) {
            $_SESSION['error'] = 'Invalid request. Please try again.';
            $this->redirect('departments');
            return;
        }

        $departmentModel = $this->model('DepartmentModel');
        $departmentId = trim((string) $this->post('department_id', ''));
        $staffId = trim((string) $this->post('staff_id', ''));
        $type = strtolower(trim((string) $this->post('head_type', '')));
        $startDate = trim((string) $this->post('head_start_date', ''));
        $remarks = trim((string) $this->post('head_remarks', ''));

        $department = $departmentId !== '' ? $departmentModel->getById($departmentId) : null;
        if (!$department) {
            $_SESSION['error'] = 'Department not found.';
            $this->redirect('departments');
            return;
        }
        if ($staffId === '') {
            $_SESSION['error'] = 'Select the staff member to assign.';
            $this->redirect('departments');
            return;
        }
        if (!isset(DepartmentModel::headTypes()[$type])) {
            $_SESSION['error'] = 'Select Permanent, Acting or Cover-up.';
            $this->redirect('departments');
            return;
        }

        $staff = $this->model('StaffModel')->find($staffId);
        if (!$staff || ($staff['staff_status'] ?? '') !== 'Working') {
            $_SESSION['error'] = 'Only working staff members can be assigned.';
            $this->redirect('departments');
            return;
        }

        if ($startDate !== '') {
            $d = DateTime::createFromFormat('Y-m-d', $startDate);
            if (!$d || $d->format('Y-m-d') !== $startDate) {
                $_SESSION['error'] = 'Invalid start date.';
                $this->redirect('departments');
                return;
            }
        }
        $remarks = function_exists('mb_substr') ? mb_substr($remarks, 0, 255) : substr($remarks, 0, 255);

        if ($type === DepartmentModel::HEAD_PERMANENT) {
            $other = $departmentModel->findPermanentHeadPost($staffId, $departmentId);
            if ($other) {
                $_SESSION['error'] = ($staff['staff_name'] ?? $staffId) . ' is already the permanent head of '
                    . $other['department_name'] . '. A person can hold only one permanent head post.';
                $this->redirect('departments');
                return;
            }
        }

        $old = [
            'head_staff_id' => $department['head_staff_id'] ?? null,
            'head_type' => $department['head_type'] ?? null,
        ];
        $new = ['head_staff_id' => $staffId, 'head_type' => $type, 'head_start_date' => $startDate ?: null];

        $sqlError = null;
        $ok = $departmentModel->assignHead(
            $departmentId,
            $staffId,
            $type,
            $startDate !== '' ? $startDate : null,
            $remarks !== '' ? $remarks : null,
            (string) ($_SESSION['user_name'] ?? $_SESSION['user_id']),
            $sqlError
        );

        if ($ok) {
            $label = DepartmentModel::headTypes()[$type];
            $this->logActivity(
                'UPDATE',
                'department',
                $departmentId,
                "Head of department assigned ({$label}): " . ($staff['staff_name'] ?? $staffId) . " for {$department['department_name']}",
                $old,
                $new
            );
            $_SESSION['message'] = ($staff['staff_name'] ?? $staffId) . " assigned as {$label} head of {$department['department_name']}.";
        } else {
            error_log('DepartmentController::assignHead: ' . $sqlError);
            $_SESSION['error'] = 'Failed to assign head of department.';
        }
        $this->redirect('departments');
    }

    /**
     * Remove the current head assignment. Admin only.
     */
    public function removeHead() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        if (!$this->requireModule('departments', 'edit')) {
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->validCsrf()) {
            $_SESSION['error'] = 'Invalid request. Please try again.';
            $this->redirect('departments');
            return;
        }

        $departmentModel = $this->model('DepartmentModel');
        $departmentId = trim((string) $this->post('department_id', ''));
        $department = $departmentId !== '' ? $departmentModel->getById($departmentId) : null;
        if (!$department) {
            $_SESSION['error'] = 'Department not found.';
            $this->redirect('departments');
            return;
        }

        $sqlError = null;
        if ($departmentModel->clearHead($departmentId, $sqlError)) {
            $this->logActivity(
                'UPDATE',
                'department',
                $departmentId,
                "Head of department removed for {$department['department_name']}",
                ['head_staff_id' => $department['head_staff_id'] ?? null, 'head_type' => $department['head_type'] ?? null],
                ['head_staff_id' => null, 'head_type' => null]
            );
            $_SESSION['message'] = 'Head of department removed.';
        } else {
            error_log('DepartmentController::removeHead: ' . $sqlError);
            $_SESSION['error'] = 'Failed to remove head of department.';
        }
        $this->redirect('departments');
    }

    private function validCsrf(): bool {
        $sent = (string) $this->post('csrf', '');
        $known = (string) ($_SESSION['dept_csrf'] ?? '');
        return $sent !== '' && $known !== '' && hash_equals($known, $sent);
    }
    
    public function index() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        if (!$this->requireModule('departments', 'view')) {
            return;
        }
        
        $departmentModel = $this->model('DepartmentModel');
        $perPage = 12;
        $page = max(1, (int) $this->get('page', 1));
        $search = trim((string) $this->get('search', ''));
        $categoryRaw = strtolower(trim((string) $this->get('category', '')));
        $category = isset(DepartmentModel::categories()[$categoryRaw]) ? $categoryRaw : '';
        $categoryCounts = [];
        
        // Get user's department if user is HOD, IN1, IN2, or IN3 - show only their department
        $userDepartmentId = $this->getUserDepartment();
        if ($userDepartmentId) {
            $dept = $departmentModel->getByIdWithCounts($userDepartmentId);
            $departments = $dept ? [$dept] : [];
            if ($search !== '' && $dept) {
                $haystack = strtolower(($dept['department_id'] ?? '') . ' ' . ($dept['department_name'] ?? ''));
                if (strpos($haystack, strtolower($search)) === false) {
                    $departments = [];
                }
            }
            if ($category !== '' && $dept && DepartmentModel::normalizeCategory($dept['department_category'] ?? '') !== $category) {
                $departments = [];
            }
            $total = count($departments);
            $totalPages = 1;
            $currentPage = 1;
        } else {
            $total = $departmentModel->getTotalDepartments($search, $category);
            $totalPages = max(1, (int) ceil($total / $perPage));
            $currentPage = min($page, $totalPages);
            $departments = $departmentModel->getDepartmentsPage($currentPage, $perPage, $search, $category);
            $categoryCounts = $departmentModel->getCategoryCounts($search);
        }
        
        // Check if user is ADM for edit permissions
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        $isADM = ($userRole === 'ADM') || $userModel->isAdmin($_SESSION['user_id']);

        $staffOptions = $isADM ? $departmentModel->getAssignableStaff() : [];
        if (empty($_SESSION['dept_csrf'])) {
            $_SESSION['dept_csrf'] = bin2hex(random_bytes(16));
        }
        
        $data = [
            'category' => $category,
            'categoryCounts' => $categoryCounts,
            'staffOptions' => $staffOptions,
            'csrf' => $_SESSION['dept_csrf'],
            'title' => 'Departments',
            'page' => 'departments',
            'departments' => $departments,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'total' => $total,
            'perPage' => $perPage,
            'search' => $search,
            'isHOD' => $userDepartmentId ? true : false,
            'hodDepartmentId' => $userDepartmentId,
            'isADM' => $isADM,
            'message' => $_SESSION['message'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ];
        
        unset($_SESSION['message'], $_SESSION['error']);
        
        return $this->view('departments/index', $data);
    }
    
    /**
     * Department profile: HOD (current + history), staff, students and activity.
     */
    public function show() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        if (!$this->requireModule('departments', 'view')) {
            return;
        }

        $id = trim((string) $this->get('id', ''));
        $departmentModel = $this->model('DepartmentModel');
        $department = $id !== '' ? $departmentModel->getByIdWithCounts($id) : null;
        if (!$department) {
            $_SESSION['error'] = 'Department not found.';
            $this->redirect('departments');
            return;
        }

        // HOD / instructors may only open their own department.
        $userDepartmentId = $this->getUserDepartment();
        if ($userDepartmentId && $userDepartmentId !== $department['department_id']) {
            $_SESSION['error'] = 'You can only view your own department.';
            $this->redirect('departments');
            return;
        }

        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        $isADM = ($userRole === 'ADM') || $userModel->isAdmin($_SESSION['user_id']);

        $deptId = (string) $department['department_id'];
        $headId = (string) ($department['head_staff_id'] ?? '');

        $activity = [];
        try {
            $activity = $this->model('ActivityLogModel')->getActivityLogs(
                ['module' => 'department', 'record_id' => $deptId],
                8,
                0
            );
        } catch (Throwable $e) {
            error_log('DepartmentController::show activity: ' . $e->getMessage());
        }

        return $this->view('departments/show', [
            'title' => 'Department - ' . $department['department_name'],
            'page' => 'departments',
            'department' => $department,
            'isADM' => $isADM,
            'headContact' => $headId !== '' ? $departmentModel->getStaffContact($headId) : null,
            'headHistory' => $departmentModel->getHeadHistory($deptId, 6),
            'roleHeads' => $departmentModel->getRoleBasedHeads($deptId),
            'staffOverview' => $departmentModel->getStaffOverview($deptId, 12),
            'studentOverview' => $departmentModel->getStudentOverview($deptId),
            'yearHistory' => $departmentModel->getStudentYearHistory($deptId, 6),
            'recentStudents' => $departmentModel->getRecentStudents($deptId, 8),
            'activity' => $activity,
        ]);
    }

    public function create() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        if (!$this->requireModule('departments', 'add')) {
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $departmentModel = $this->model('DepartmentModel');
            
            $department_id = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', trim($this->post('department_id', ''))));
            $department_name = trim($this->post('department_name', ''));
            $department_category = DepartmentModel::normalizeCategory($this->post('department_category', ''));
            
            // Validation
            if (empty($department_id) || empty($department_name)) {
                $_SESSION['error'] = 'All fields are required.';
                $this->redirect('departments/create');
                return;
            }
            
            // Check if department ID already exists
            if ($departmentModel->exists($department_id)) {
                $_SESSION['error'] = 'Department ID already exists.';
                $this->redirect('departments/create');
                return;
            }
            
            // Create department
            $deptData = [
                'department_id' => $department_id,
                'department_name' => $department_name,
                'department_category' => $department_category
            ];
            
            $sqlError = null;
            $result = $departmentModel->createDepartment($deptData, $sqlError);
            
            // For tables with manual primary keys (like department_id),
            // insert_id can be 0 even on success, so we only treat FALSE as failure.
            if ($result !== false) {
                // Log activity
                $this->logActivity(
                    'CREATE',
                    'department',
                    $department_id,
                    "Department created: {$department_name} ({$department_id})",
                    null,
                    $deptData
                );
                
                $_SESSION['message'] = 'Department created successfully.';
                $this->redirect('departments');
            } else {
                if ($sqlError) {
                    error_log("DepartmentController::create - SQL error while creating department {$department_id}: " . $sqlError);
                    $_SESSION['error'] = 'Failed to create department. Database error: ' . $sqlError;
                } else {
                    $_SESSION['error'] = 'Failed to create department.';
                }
                $this->redirect('departments/create');
            }
        } else {
            $data = [
                'title' => 'Create Department',
                'page' => 'departments',
                'error' => $_SESSION['error'] ?? null
            ];
            unset($_SESSION['error']);
            return $this->view('departments/create', $data);
        }
    }
    
    public function edit() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Only ADM can edit departments
        if (!$this->requireModule('departments', 'edit')) {
            return;
        }
        
        $id = $this->get('id', '');
        
        if (empty($id)) {
            $_SESSION['error'] = 'Department ID is required.';
            $this->redirect('departments');
            return;
        }
        
        $departmentModel = $this->model('DepartmentModel');
        $department = $departmentModel->getById($id);
        
        if (!$department) {
            $_SESSION['error'] = 'Department not found.';
            $this->redirect('departments');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $department_name = trim($this->post('department_name', ''));
            
            // Validation
            if (empty($department_name)) {
                $_SESSION['error'] = 'Department name is required.';
                $this->redirect('departments/edit?id=' . urlencode($id));
                return;
            }
            
            $department_category = DepartmentModel::normalizeCategory($this->post('department_category', ''));
            $deptData = [
                'department_name' => $department_name,
                'department_category' => $department_category,
            ];
            
            // Get old values before update
            $oldValues = [
                'department_name' => $department['department_name'],
                'department_category' => $department['department_category'] ?? DepartmentModel::CATEGORY_ACADEMIC,
            ];
            
            // Update department
            $result = $departmentModel->updateDepartment($id, $deptData);
            
            if ($result) {
                // Log activity
                $this->logActivity(
                    'UPDATE',
                    'department',
                    $id,
                    "Department updated: {$department_name} ({$id})",
                    $oldValues,
                    $deptData
                );
                
                $_SESSION['message'] = 'Department updated successfully.';
                $this->redirect('departments');
            } else {
                $_SESSION['error'] = 'Failed to update department.';
                $this->redirect('departments/edit?id=' . urlencode($id));
            }
        } else {
            $data = [
                'title' => 'Edit Department',
                'page' => 'departments',
                'department' => $department,
                'error' => $_SESSION['error'] ?? null
            ];
            unset($_SESSION['error']);
            return $this->view('departments/edit', $data);
        }
    }
    
    public function delete() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Only ADM can delete departments
        if (!$this->requireModule('departments', 'delete')) {
            return;
        }
        
        $id = $this->get('id', '');
        if (empty($id)) {
            $_SESSION['error'] = 'Department ID is required.';
            $this->redirect('departments');
            return;
        }
        
        $departmentModel = $this->model('DepartmentModel');
        $department = $departmentModel->getById($id);
        
        if (!$department) {
            $_SESSION['error'] = 'Department not found.';
            $this->redirect('departments');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Check if department is used in courses
            $courseModel = $this->model('CourseModel');
            $courses = $courseModel->where('department_id', $id);
            
            if (!empty($courses)) {
                $_SESSION['error'] = 'Cannot delete department. It is being used by courses.';
                $this->redirect('departments');
                return;
            }
            
            // Store old values for logging
            $oldValues = $department;
            
            // Delete department
            $result = $departmentModel->deleteDepartment($id);
            
            if ($result) {
                // Log activity
                $this->logActivity(
                    'DELETE',
                    'department',
                    $id,
                    "Department deleted: {$department['department_name']} ({$id})",
                    $oldValues,
                    null
                );
                
                $_SESSION['message'] = 'Department deleted successfully.';
            } else {
                $_SESSION['error'] = 'Failed to delete department.';
            }
            
            $this->redirect('departments');
        } else {
            $data = [
                'title' => 'Delete Department',
                'page' => 'departments',
                'department' => $department
            ];
            return $this->view('departments/delete', $data);
        }
    }
}

