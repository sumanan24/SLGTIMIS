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
}

