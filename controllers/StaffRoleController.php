<?php
/**
 * Staff Role Controller
 */

class StaffRoleController extends Controller {
    
    public function index() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Only ADM and Admin can access staff roles
        if (!$this->checkAdminOrADM()) {
            return;
        }
        
        $roleModel = $this->model('StaffRoleModel');
        $roles = $roleModel->getAll();
        
        $data = [
            'title' => 'Staff Roles',
            'page' => 'staff-roles',
            'roles' => $roles,
            'message' => $_SESSION['message'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ];
        
        unset($_SESSION['message'], $_SESSION['error']);
        return $this->view('staff-roles/index', $data);
    }
    
    public function create() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Only ADM and Admin can access staff roles
        if (!$this->checkAdminOrADM()) {
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $roleModel = $this->model('StaffRoleModel');
            
            $data = [
                'staff_position_type_id' => strtoupper(trim($this->post('staff_position_type_id', ''))),
                'staff_position_type_name' => trim($this->post('staff_position_type_name', '')),
                'staff_position' => (int)$this->post('staff_position', 0)
            ];
            
            // Validation
            if (empty($data['staff_position_type_id']) || empty($data['staff_position_type_name'])) {
                $_SESSION['error'] = 'Role ID and Name are required.';
                $this->redirect('staff-roles/create');
                return;
            }
            
            // Check if role ID already exists
            if ($roleModel->exists($data['staff_position_type_id'])) {
                $_SESSION['error'] = 'Role ID already exists.';
                $this->redirect('staff-roles/create');
                return;
            }
            
            // Create role
            $result = $roleModel->createRole($data);
            
            if ($result) {
                $_SESSION['message'] = 'Staff role created successfully.';
                $this->redirect('staff-roles');
            } else {
                $_SESSION['error'] = 'Failed to create staff role.';
                $this->redirect('staff-roles/create');
            }
        } else {
            $data = [
                'title' => 'Create Staff Role',
                'page' => 'staff-roles',
                'error' => $_SESSION['error'] ?? null
            ];
            unset($_SESSION['error']);
            return $this->view('staff-roles/create', $data);
        }
    }
    
    public function edit() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Only ADM and Admin can access staff roles
        if (!$this->checkAdminOrADM()) {
            return;
        }
        
        $id = $this->get('id', '');
        if (empty($id)) {
            $_SESSION['error'] = 'Role ID is required.';
            $this->redirect('staff-roles');
            return;
        }
        
        $roleModel = $this->model('StaffRoleModel');
        $role = $roleModel->find($id);
        
        if (!$role) {
            $_SESSION['error'] = 'Role not found.';
            $this->redirect('staff-roles');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'staff_position_type_name' => trim($this->post('staff_position_type_name', '')),
                'staff_position' => (int)$this->post('staff_position', 0)
            ];
            
            // Validation
            if (empty($data['staff_position_type_name'])) {
                $_SESSION['error'] = 'Role Name is required.';
                $this->redirect('staff-roles/edit?id=' . urlencode($id));
                return;
            }
            
            // Update role
            $result = $roleModel->updateRole($id, $data);
            
            if ($result) {
                $_SESSION['message'] = 'Staff role updated successfully.';
                $this->redirect('staff-roles');
            } else {
                $_SESSION['error'] = 'Failed to update staff role.';
                $this->redirect('staff-roles/edit?id=' . urlencode($id));
            }
        } else {
            $data = [
                'title' => 'Edit Staff Role',
                'page' => 'staff-roles',
                'role' => $role,
                'error' => $_SESSION['error'] ?? null
            ];
            unset($_SESSION['error']);
            return $this->view('staff-roles/edit', $data);
        }
    }
    
    public function delete() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Only ADM and Admin can access staff roles
        if (!$this->checkAdminOrADM()) {
            return;
        }
        
        $id = $this->get('id', '');
        if (empty($id)) {
            $_SESSION['error'] = 'Role ID is required.';
            $this->redirect('staff-roles');
            return;
        }
        
        $roleModel = $this->model('StaffRoleModel');
        $role = $roleModel->find($id);
        
        if (!$role) {
            $_SESSION['error'] = 'Role not found.';
            $this->redirect('staff-roles');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Check if role is used
            if ($roleModel->isUsed($id)) {
                $_SESSION['error'] = 'Cannot delete role. It is currently assigned to staff members or users.';
                $this->redirect('staff-roles');
                return;
            }
            
            // Delete role
            $result = $roleModel->deleteRole($id);
            
            if ($result) {
                $_SESSION['message'] = 'Staff role deleted successfully.';
            } else {
                $_SESSION['error'] = 'Failed to delete staff role. It may be in use.';
            }
            
            $this->redirect('staff-roles');
        } else {
            $isUsed = $roleModel->isUsed($id);
            $data = [
                'title' => 'Delete Staff Role',
                'page' => 'staff-roles',
                'role' => $role,
                'isUsed' => $isUsed
            ];
            return $this->view('staff-roles/delete', $data);
        }
    }
}

