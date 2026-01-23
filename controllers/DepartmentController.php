<?php
/**
 * Department Controller
 */

class DepartmentController extends Controller {
    
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
        
        $departmentModel = $this->model('DepartmentModel');
        
        // Get user's department if user is HOD, IN1, IN2, or IN3 - show only their department
        $userDepartmentId = $this->getUserDepartment();
        if ($userDepartmentId) {
            $dept = $departmentModel->getById($userDepartmentId);
            $departments = $dept ? [$dept] : [];
        } else {
            $departments = $departmentModel->getAll();
        }
        
        // Check if user is ADM for edit permissions
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        $isADM = ($userRole === 'ADM') || $userModel->isAdmin($_SESSION['user_id']);
        
        $data = [
            'title' => 'Departments',
            'page' => 'departments',
            'departments' => $departments,
            'isHOD' => $userDepartmentId ? true : false,
            'hodDepartmentId' => $userDepartmentId,
            'isADM' => $isADM,
            'message' => $_SESSION['message'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ];
        
        unset($_SESSION['message'], $_SESSION['error']);
        
        return $this->view('departments/index', $data);
    }
    
    public function create() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Only ADM can create departments
        if (!$this->checkAdminOrADM()) {
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $departmentModel = $this->model('DepartmentModel');
            
            $department_id = trim($this->post('department_id', ''));
            $department_name = trim($this->post('department_name', ''));
            
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
                'department_name' => $department_name
            ];
            
            $result = $departmentModel->createDepartment($deptData);
            
            if ($result) {
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
                $_SESSION['error'] = 'Failed to create department.';
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
        if (!$this->checkAdminOrADM()) {
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
            
            $deptData = ['department_name' => $department_name];
            
            // Get old values before update
            $oldValues = ['department_name' => $department['department_name']];
            
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
        if (!$this->checkAdminOrADM()) {
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

