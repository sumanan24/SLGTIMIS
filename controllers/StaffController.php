<?php
/**
 * Staff Controller
 */

class StaffController extends Controller {
    
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
        
        $staffModel = $this->model('StaffModel');
        
        // Get user's department if user is HOD, IN1, IN2, or IN3
        $userDepartmentId = $this->getUserDepartment();
        
        $page = $this->get('page', 1);
        $search = $this->get('search', '');
        
        $staff = $staffModel->getStaffWithDepartment($page, 20, $search, $userDepartmentId ? $userDepartmentId : '');
        $total = $staffModel->getTotalStaff($search, $userDepartmentId ? $userDepartmentId : '');
        $totalPages = ceil($total / 20);
        
        // Check if user can manage staff (ADM, MHF, REG only)
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        $isAdmin = $userModel->isAdmin($_SESSION['user_id']);
        $canManageStaff = in_array($userRole, ['ADM', 'MHF', 'REG']) || $isAdmin;
        
        $data = [
            'title' => 'Staff',
            'page' => 'staff',
            'staff' => $staff,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'search' => $search,
            'canManageStaff' => $canManageStaff,
            'message' => $_SESSION['message'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ];
        
        unset($_SESSION['message'], $_SESSION['error']);
        return $this->view('staff/index', $data);
    }
    
    public function create() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Restrict SAO users
        if (!$this->checkNotSAO()) {
            return;
        }
        
        // Only ADM, MHF, REG can create staff
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        $isAdmin = $userModel->isAdmin($_SESSION['user_id']);
        $canManageStaff = in_array($userRole, ['ADM', 'MHF', 'REG']) || $isAdmin;
        
        if (!$canManageStaff) {
            $_SESSION['error'] = 'Access denied. Only ADM, MHF, and REG can create staff.';
            $this->redirect('staff');
            return;
        }
        
        $departmentModel = $this->model('DepartmentModel');
        $departments = $departmentModel->getAll();
        
        $roleModel = $this->model('StaffRoleModel');
        $roles = $roleModel->getAll();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $staffModel = $this->model('StaffModel');
            
            $data = [
                'staff_id' => trim($this->post('staff_id', '')),
                'department_id' => trim($this->post('department_id', '')),
                'staff_name' => trim($this->post('staff_name', '')),
                'staff_address' => trim($this->post('staff_address', '')),
                'staff_dob' => $this->post('staff_dob', ''),
                'staff_nic' => trim($this->post('staff_nic', '')),
                'staff_email' => trim($this->post('staff_email', '')),
                'staff_pno' => (int)$this->post('staff_pno', 0),
                'staff_date_of_join' => $this->post('staff_date_of_join', ''),
                'staff_gender' => $this->post('staff_gender', ''),
                'staff_epf' => trim($this->post('staff_epf', '')),
                'staff_position' => trim($this->post('staff_position', '')),
                'staff_type' => $this->post('staff_type', ''),
                'staff_status' => $this->post('staff_status', 'Working')
            ];
            
            // Validation
            if (empty($data['staff_id']) || empty($data['staff_name']) || empty($data['staff_email']) || 
                empty($data['staff_nic']) || empty($data['department_id'])) {
                $_SESSION['error'] = 'Staff ID, Name, Email, NIC, and Department are required.';
                $this->redirect('staff/create');
                return;
            }
            
            // Check if staff ID already exists
            if ($staffModel->exists($data['staff_id'])) {
                $_SESSION['error'] = 'Staff ID already exists.';
                $this->redirect('staff/create');
                return;
            }
            
            // Check if department exists
            if (!$departmentModel->exists($data['department_id'])) {
                $_SESSION['error'] = 'Selected department does not exist.';
                $this->redirect('staff/create');
                return;
            }
            
            // Create staff
            $result = $staffModel->createStaff($data);
            
            if ($result) {
                // Log activity
                $this->logActivity(
                    'CREATE',
                    'staff',
                    $data['staff_id'],
                    "Staff created: {$data['staff_name']} ({$data['staff_id']})",
                    null,
                    $data
                );
                
                $_SESSION['message'] = 'Staff created successfully.';
                $this->redirect('staff');
            } else {
                $_SESSION['error'] = 'Failed to create staff.';
                $this->redirect('staff/create');
            }
        } else {
            $data = [
                'title' => 'Create Staff',
                'page' => 'staff',
                'departments' => $departments,
                'roles' => $roles,
                'error' => $_SESSION['error'] ?? null
            ];
            unset($_SESSION['error']);
            return $this->view('staff/create', $data);
        }
    }
    
    public function edit() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Restrict SAO users
        if (!$this->checkNotSAO()) {
            return;
        }
        
        // Only ADM, MHF, REG can edit staff
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        $isAdmin = $userModel->isAdmin($_SESSION['user_id']);
        $canManageStaff = in_array($userRole, ['ADM', 'MHF', 'REG']) || $isAdmin;
        
        if (!$canManageStaff) {
            $_SESSION['error'] = 'Access denied. Only ADM, MHF, and REG can edit staff.';
            $this->redirect('staff');
            return;
        }
        
        $id = $this->get('id', '');
        if (empty($id)) {
            $_SESSION['error'] = 'Staff ID is required.';
            $this->redirect('staff');
            return;
        }
        
        $staffModel = $this->model('StaffModel');
        $staff = $staffModel->getById($id);
        
        if (!$staff) {
            $_SESSION['error'] = 'Staff not found.';
            $this->redirect('staff');
            return;
        }
        
        $departmentModel = $this->model('DepartmentModel');
        $departments = $departmentModel->getAll();
        
        $roleModel = $this->model('StaffRoleModel');
        $roles = $roleModel->getAll();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'department_id' => trim($this->post('department_id', '')),
                'staff_name' => trim($this->post('staff_name', '')),
                'staff_address' => trim($this->post('staff_address', '')),
                'staff_dob' => $this->post('staff_dob', ''),
                'staff_nic' => trim($this->post('staff_nic', '')),
                'staff_email' => trim($this->post('staff_email', '')),
                'staff_pno' => (int)$this->post('staff_pno', 0),
                'staff_date_of_join' => $this->post('staff_date_of_join', ''),
                'staff_gender' => $this->post('staff_gender', ''),
                'staff_epf' => trim($this->post('staff_epf', '')),
                'staff_position' => trim($this->post('staff_position', '')),
                'staff_type' => $this->post('staff_type', ''),
                'staff_status' => $this->post('staff_status', 'Working')
            ];
            
            // Validation
            if (empty($data['staff_name']) || empty($data['staff_email']) || 
                empty($data['staff_nic']) || empty($data['department_id'])) {
                $_SESSION['error'] = 'Name, Email, NIC, and Department are required.';
                $this->redirect('staff/edit?id=' . urlencode($id));
                return;
            }
            
            // Check if department exists
            if (!$departmentModel->exists($data['department_id'])) {
                $_SESSION['error'] = 'Selected department does not exist.';
                $this->redirect('staff/edit?id=' . urlencode($id));
                return;
            }
            
            // Update staff
            // Get old values before update
            $oldStaff = $staffModel->find($id);
            $oldValues = $oldStaff ? array_intersect_key($oldStaff, $data) : null;
            
            $result = $staffModel->updateStaff($id, $data);
            
            if ($result) {
                // Log activity
                $staffName = isset($data['staff_name']) ? $data['staff_name'] : ($oldStaff['staff_name'] ?? 'Unknown');
                $this->logActivity(
                    'UPDATE',
                    'staff',
                    $id,
                    "Staff updated: {$staffName} ({$id})",
                    $oldValues,
                    $data
                );
                
                $_SESSION['message'] = 'Staff updated successfully.';
                $this->redirect('staff');
            } else {
                $_SESSION['error'] = 'Failed to update staff.';
                $this->redirect('staff/edit?id=' . urlencode($id));
            }
        } else {
            $data = [
                'title' => 'Edit Staff',
                'page' => 'staff',
                'staff' => $staff,
                'departments' => $departments,
                'roles' => $roles,
                'error' => $_SESSION['error'] ?? null
            ];
            unset($_SESSION['error']);
            return $this->view('staff/edit', $data);
        }
    }
    
    public function delete() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Restrict SAO users
        if (!$this->checkNotSAO()) {
            return;
        }
        
        // Only ADM, MHF, REG can delete staff
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        $isAdmin = $userModel->isAdmin($_SESSION['user_id']);
        $canManageStaff = in_array($userRole, ['ADM', 'MHF', 'REG']) || $isAdmin;
        
        if (!$canManageStaff) {
            $_SESSION['error'] = 'Access denied. Only ADM, MHF, and REG can delete staff.';
            $this->redirect('staff');
            return;
        }
        
        $id = $this->get('id', '');
        if (empty($id)) {
            $_SESSION['error'] = 'Staff ID is required.';
            $this->redirect('staff');
            return;
        }
        
        $staffModel = $this->model('StaffModel');
        $staff = $staffModel->getById($id);
        
        if (!$staff) {
            $_SESSION['error'] = 'Staff not found.';
            $this->redirect('staff');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Delete staff
            $result = $staffModel->deleteStaff($id);
            
            if ($result) {
                $_SESSION['message'] = 'Staff deleted successfully.';
            } else {
                $_SESSION['error'] = 'Failed to delete staff.';
            }
            
            $this->redirect('staff');
        } else {
            $data = [
                'title' => 'Delete Staff',
                'page' => 'staff',
                'staff' => $staff
            ];
            return $this->view('staff/delete', $data);
        }
    }
}

