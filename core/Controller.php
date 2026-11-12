<?php
/**
 * Base Controller Class
 */

class Controller {
    protected $view;
    
    public function __construct() {
        $this->view = new View();
    }
    
    /**
     * Load a model
     */
    protected function model($model) {
        $modelFile = BASE_PATH . '/models/' . $model . '.php';
        
        if (file_exists($modelFile)) {
            require_once $modelFile;
            return new $model();
        }
        
        throw new Exception("Model $model not found");
    }
    
    /**
     * Load a view
     */
    protected function view($view, $data = []) {
        return $this->view->render($view, $data);
    }
    
    /**
     * Redirect to a URL
     */
    protected function redirect($url) {
        // Remove leading slash from URL if present to avoid double slashes
        $url = ltrim($url, '/');
        header("Location: " . APP_URL . "/" . $url);
        exit();
    }
    
    /**
     * Return JSON response
     */
    protected function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }
    
    /**
     * Get POST data
     */
    protected function post($key = null, $default = null) {
        if ($key === null) {
            return $_POST;
        }
        return $_POST[$key] ?? $default;
    }
    
    /**
     * Get GET data
     */
    protected function get($key = null, $default = null) {
        if ($key === null) {
            return $_GET;
        }
        return $_GET[$key] ?? $default;
    }
    
    /**
     * Check if user is SAO (Students Affair Office) - restrict access
     */
    protected function checkNotSAO() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return false;
        }
        
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        
        if ($userModel->isSAO($_SESSION['user_id'])) {
            $_SESSION['error'] = 'Access denied. This section is not available for your role.';
            $this->redirect('dashboard');
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if user is SAO - allow access
     */
    protected function checkSAO() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return false;
        }
        
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        
        if (!$userModel->isSAO($_SESSION['user_id'])) {
            $_SESSION['error'] = 'Access denied. This section is only available for Student Affairs Office.';
            $this->redirect('dashboard');
            return false;
        }
        
        return true;
    }
    
    /**
     * Get HOD's department ID if user is HOD, otherwise return null
     */
    protected function getHODDepartment() {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        
        if ($userModel->isHOD($_SESSION['user_id'])) {
            return $userModel->getHODDepartment($_SESSION['user_id']);
        }
        
        return null;
    }
    
    /**
     * Get user's department ID for HOD, IN1, IN2, or IN3 roles
     * Returns null if user doesn't have department-restricted access or can access all departments
     */
    protected function getUserDepartment() {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        $isAdmin = $userModel->isAdmin($_SESSION['user_id']);
        
        // ADM and Admin can access all departments
        if ($userRole === 'ADM' || $isAdmin) {
            return null;
        }
        
        $uid = (int) $_SESSION['user_id'];

        // HOD: their own post's department (fall back to the department they are assigned to head)
        if ($userRole === 'HOD') {
            $own = $userModel->getHODDepartment($uid)
                ?: ($userModel->getHeadedDepartmentId($uid) ?: $userModel->getStaffDepartmentId($uid));
            return $this->pickActiveDepartment($userModel, $uid, $own);
        }

        // Lecturers and IN1-IN3: a Permanent / Acting / Cover-up head assignment takes priority over
        // the department on their staff record. They can switch back from the dashboard.
        if (in_array($userRole, self::LECTURER_ROLES, true) || in_array($userRole, ['IN1', 'IN2', 'IN3'], true)) {
            $own = $userModel->getStaffDepartmentId($uid);
            $headed = $userModel->getHeadedDepartmentIds($uid);
            $default = $headed[0] ?? $own;
            return $this->pickActiveDepartment($userModel, $uid, $default ?: null, $own);
        }

        // Anyone else who is the assigned head of a department sees that department only
        // (institute-wide roles such as DIR / REG / FIN keep their full view).
        if (!in_array($userRole, self::INSTITUTE_WIDE_ROLES, true)) {
            $headed = $userModel->getHeadedDepartmentIds($uid);
            return $this->pickActiveDepartment($userModel, $uid, $headed[0] ?? null);
        }
        
        return null;
    }

    /**
     * Departments the current user may work in: every department they are assigned to head
     * (Permanent, Acting or Cover-up) plus the one on their own staff record.
     *
     * @return list<string>
     */
    protected function getSelectableDepartmentIds($userModel, int $uid, ?string ...$extra): array {
        $ids = $userModel->getHeadedDepartmentIds($uid);
        foreach ($extra as $e) {
            if ($e !== null && trim($e) !== '') {
                $ids[] = trim($e);
            }
        }
        $own = $userModel->getStaffDepartmentId($uid);
        if ($own) {
            $ids[] = $own;
        }
        if ($userModel->isHOD($uid)) {
            $hodDept = $userModel->getHODDepartment($uid);
            if ($hodDept) {
                $ids[] = (string) $hodDept;
            }
        }
        return array_values(array_unique($ids));
    }

    /**
     * Use the department chosen on the dashboard (session) when it is one the user may work in,
     * otherwise the default.
     */
    private function pickActiveDepartment($userModel, int $uid, ?string $default, ?string $own = null): ?string {
        $chosen = trim((string) ($_SESSION['active_department_id'] ?? ''));
        if ($chosen !== '' && in_array($chosen, $this->getSelectableDepartmentIds($userModel, $uid, $default, $own), true)) {
            return $chosen;
        }
        return $default;
    }

    /**
     * Switch the department the user is working in (dashboard selector). Returns false when the
     * user is not allowed to work in that department.
     */
    protected function setActiveDepartment(string $departmentId): bool {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $uid = (int) $_SESSION['user_id'];
        $departmentId = trim($departmentId);
        if ($departmentId === '' || !in_array($departmentId, $this->getSelectableDepartmentIds($userModel, $uid), true)) {
            return false;
        }
        $_SESSION['active_department_id'] = $departmentId;
        return true;
    }

    /** Lecturer positions: their data views are limited to their own department. */
    const LECTURER_ROLES = ['LE1', 'LE2', 'SLE'];
    /** Positions that always see every department. */
    const INSTITUTE_WIDE_ROLES = ['ADM', 'DIR', 'DPI', 'DPA', 'REG', 'SAO', 'FIN', 'ACC', 'HRO', 'MHF', 'EXAM'];

    /**
     * HOD, or the staff member assigned as head of a department: may manage (CRUD) the
     * courses and modules of their OWN department (see getUserDepartment()).
     */
    protected function isDepartmentManager(): bool {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $role = $userModel->getUserRole($_SESSION['user_id']);
        if ($role === 'HOD') {
            return true;
        }
        if (in_array($role, self::INSTITUTE_WIDE_ROLES, true)) {
            return false;
        }
        // Only for the department they actually head (not their own department when it is a different one).
        $active = $this->getUserDepartment();
        return $active !== null
            && in_array($active, $userModel->getHeadedDepartmentIds((int) $_SESSION['user_id']), true);
    }

    /**
     * True when the current user's data views must be limited to one department:
     * HOD / IN1-3, lecturers, and assigned department heads. (Write rights are not implied.)
     */
    protected function isDepartmentScoped(): bool {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        if ($this->isDepartmentRestricted()) {
            return true;
        }
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        if ($userModel->isAdmin($_SESSION['user_id'])) {
            return false;
        }
        $role = $userModel->getUserRole($_SESSION['user_id']);
        if (in_array($role, self::LECTURER_ROLES, true)) {
            return true;
        }
        return !in_array($role, self::INSTITUTE_WIDE_ROLES, true)
            && $userModel->getHeadedDepartmentId($_SESSION['user_id']) !== null;
    }
    
    /**
     * Check if user is HOD
     */
    protected function isHOD() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        return $userModel->isHOD($_SESSION['user_id']);
    }
    
    /**
     * HOD / IN1-3 without a department on their account must never fall back to "all departments".
     * Returns false (after redirecting with a message) in that case.
     */
    protected function requireDepartmentAssigned(string $redirect = 'dashboard'): bool {
        if ($this->isDepartmentScoped() && !$this->getUserDepartment()) {
            $_SESSION['error'] = 'Your account has no department assigned. Please contact the administrator.';
            $this->redirect($redirect);
            return false;
        }
        return true;
    }

    /**
     * Whether the current user may open a student's record.
     * Department-restricted users (HOD, IN1-3) only see students enrolled in a course of their own department.
     * Everyone else is not restricted here (module permissions still apply).
     */
    protected function canAccessStudentRecord($studentId): bool {
        if (!$this->isDepartmentScoped()) {
            return true;
        }
        $deptId = $this->getUserDepartment();
        $studentId = trim((string) $studentId);
        if (!$deptId || $studentId === '') {
            return false;
        }
        $stmt = Database::getInstance()->prepare(
            "SELECT 1 FROM `student_enroll` se
             INNER JOIN `course` c ON c.course_id = se.course_id
             WHERE se.student_id = ? AND c.department_id = ? LIMIT 1"
        );
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('ss', $studentId, $deptId);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res && $res->num_rows > 0;
    }

    /**
     * Check if user is HOD, IN1, IN2, or IN3 (department-restricted roles)
     */
    protected function isDepartmentRestricted() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        
        return in_array($userRole, ['HOD', 'IN1', 'IN2', 'IN3']);
    }
    
    /**
     * Check if current logged-in user has finance access (FIN, ACC, or ADM)
     */
    protected function hasFinanceAccess() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        return $userModel->hasFinanceAccess($_SESSION['user_id']);
    }
    
    /**
     * Redirect if user doesn't have finance access
     */
    protected function checkFinanceAccess() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return false;
        }
        
        if (!$this->hasFinanceAccess()) {
            $_SESSION['error'] = 'Access denied. Payments section is only available for Finance Officer, Accountant, or Administrator.';
            $this->redirect('dashboard');
            return false;
        }
        
        return true;
    }

    /**
     * View payments list / print receipt (FIN, ACC, ADM, or read-only DIR).
     */
    protected function checkPaymentsViewAccess() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return false;
        }

        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();

        if (!$userModel->canViewPaymentsList($_SESSION['user_id'])) {
            $_SESSION['error'] = 'Access denied. Payments are available to Finance staff (including Finance MA/MA2), Administrator, or Director (view only).';
            $this->redirect('dashboard');
            return false;
        }

        return true;
    }
    
    /**
     * Check if current logged-in user is Admin or ADM
     */
    protected function isAdminOrADM() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        return $userModel->isAdminOrADM($_SESSION['user_id']);
    }
    
    /**
     * Redirect if user is not Admin or ADM
     */
    protected function checkAdminOrADM() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return false;
        }
        
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        
        if (!$userModel->isAdminOrADM($_SESSION['user_id'])) {
            $_SESSION['error'] = 'Access denied. This section is only available for Administrator.';
            $this->redirect('dashboard');
            return false;
        }
        
        return true;
    }

    /**
     * Exam module: EXAM, ADM, or system admin.
     */
    protected function checkExamsAccess() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return false;
        }
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        if (!$userModel->canAccessExamsModule($_SESSION['user_id'])) {
            $_SESSION['error'] = 'Access denied. The Exam module is only available for Examination Office (EXAM) or Administrator (ADM).';
            $this->redirect('dashboard');
            return false;
        }
        return true;
    }
    
    /**
     * Check if current logged-in user can manage room allocations (SAO or ADM)
     */
    protected function canManageRoomAllocations() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        return $userModel->canManageRoomAllocations($_SESSION['user_id']);
    }
    
    /**
     * Redirect if user cannot manage room allocations (not SAO or ADM)
     */
    protected function checkRoomAllocationAccess() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return false;
        }
        
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        
        if (!$userModel->canManageRoomAllocations($_SESSION['user_id'])) {
            $_SESSION['error'] = 'Access denied. Room Allocations section is only available for Student Affairs Office or Administrator.';
            $this->redirect('dashboard');
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if user can view room allocations (SAO, ADM, or FIN)
     * FIN users have read-only access
     */
    protected function checkRoomAllocationViewAccess() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return false;
        }
        
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();

        if (!$userModel->canViewRoomAllocations($_SESSION['user_id'])) {
            $_SESSION['error'] = 'Access denied. Room Allocations section is only available for authorized roles.';
            $this->redirect('dashboard');
            return false;
        }

        return true;
    }
    
    /**
     * Log activity - Helper method for all controllers
     * 
     * @param string $activityType CREATE, UPDATE, DELETE, APPROVE, REJECT, VIEW, etc.
     * @param string $module Module name (e.g., 'student', 'staff', 'on_peak_request', 'bus_season_request')
     * @param string|null $recordId ID of the affected record
     * @param string $description Human-readable description
     * @param array|null $oldValues Values before change (for UPDATE/DELETE)
     * @param array|null $newValues Values after change (for CREATE/UPDATE)
     * @return bool Success status
     */
    protected function logActivity($activityType, $module, $recordId = null, $description = '', $oldValues = null, $newValues = null) {
        try {
            $activityModel = $this->model('ActivityLogModel');
            return $activityModel->logActivity([
                'activity_type' => $activityType,
                'module' => $module,
                'record_id' => $recordId,
                'description' => $description,
                'old_values' => $oldValues,
                'new_values' => $newValues
            ]);
        } catch (Exception $e) {
            // Silently fail - don't break the application if logging fails
            error_log("Activity logging failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Staff Member → Module → Action. Role RBAC is the default; staff overrides win.
     */
    protected function requireModule($moduleKey, $action = 'view') {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return false;
        }
        require_once BASE_PATH . '/core/AccessControl.php';
        if (!AccessControl::can((int) $_SESSION['user_id'], $moduleKey, $action)) {
            $_SESSION['error'] = 'You do not have permission to access this module.';
            $this->redirect('dashboard');
            return false;
        }
        return true;
    }
}

