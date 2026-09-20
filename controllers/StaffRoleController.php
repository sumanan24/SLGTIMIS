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
        
        if (!$this->requireModule('staff_roles', 'view')) {
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
        
        if (!$this->requireModule('staff_roles', 'add')) {
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
        
        if (!$this->requireModule('staff_roles', 'edit')) {
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
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $this->post('form_section', 'role') === 'role') {
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
            
            $oldValues = [
                'staff_position_type_name' => $role['staff_position_type_name'],
                'staff_position' => $role['staff_position']
            ];
            $result = $roleModel->updateRole($id, $data);
            
            if ($result) {
                $this->logActivity(
                    'UPDATE',
                    'staff-roles',
                    $id,
                    'Staff role updated: ' . $data['staff_position_type_name'] . ' (' . $id . ')',
                    $oldValues,
                    $data
                );
                $_SESSION['message'] = 'Staff role updated successfully.';
                $this->redirect('staff-roles');
            } else {
                $_SESSION['error'] = 'Failed to update staff role.';
                $this->redirect('staff-roles/edit?id=' . urlencode($id));
            }
            return;
        }

        $salaryModel = $this->model('StaffRoleSalaryModel');
        $activeGrade = (int) $this->get('grade', 3);
        if (!StaffRoleSalaryModel::isValidGrade($activeGrade)) {
            $activeGrade = StaffRoleSalaryModel::GRADE_3;
        }

        $data = [
            'title' => 'Edit Staff Role',
            'page' => 'staff-roles',
            'role' => $role,
            'salaryGrades' => $salaryModel->getRoleSalaryBundle($id),
            'roleStaff' => $salaryModel->getRoleStaffWithCalculation($id),
            'salaryGradesList' => StaffRoleSalaryModel::$grades,
            'salaryGradeLabels' => StaffRoleSalaryModel::$gradeLabels,
            'activeGrade' => $activeGrade,
            'message' => $_SESSION['message'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ];
        unset($_SESSION['message'], $_SESSION['error']);
        return $this->view('staff-roles/edit', $data);
    }

    /**
     * Save grade salary structure and 18-year increments (creates a revision when pay values change).
     */
    public function saveSalary() {
        if (!$this->guardStaffRolesAccess()) {
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('staff-roles');
            return;
        }

        $id = trim((string) $this->post('staff_position_type_id', $this->get('id', '')));
        $grade = (int) $this->post('grade', 3);
        if ($id === '') {
            $_SESSION['error'] = 'Role ID is required.';
            $this->redirect('staff-roles');
            return;
        }

        $roleModel = $this->model('StaffRoleModel');
        if (!$roleModel->find($id)) {
            $_SESSION['error'] = 'Role not found.';
            $this->redirect('staff-roles');
            return;
        }

        $salaryModel = $this->model('StaffRoleSalaryModel');
        $postedNames = $this->post('allowance_name', []);
        $postedAmounts = $this->post('allowance_amount', []);
        $slabYears = $this->post('slab_years', []);
        $slabAmounts = $this->post('slab_amount', []);
        if (!is_array($postedNames)) {
            $postedNames = [];
        }
        if (!is_array($postedAmounts)) {
            $postedAmounts = [];
        }
        if (!is_array($slabYears)) {
            $slabYears = [];
        }
        if (!is_array($slabAmounts)) {
            $slabAmounts = [];
        }

        $input = [
            'basic_salary' => $this->post('basic_salary', 0),
            'max_basic_salary' => $this->post('max_basic_salary', 0),
            'effective_date' => $this->post('effective_date', ''),
            'remarks' => $this->post('remarks', ''),
            'allowance_name' => $postedNames,
            'allowance_amount' => $postedAmounts,
            'slab_years' => $slabYears,
            'slab_amount' => $slabAmounts,
        ];

        $result = $salaryModel->saveGradeSalary($id, $grade, $input, $_SESSION['user_id'] ?? null);
        if ($result['ok']) {
            $this->logActivity(
                !empty($result['revision']) ? 'REVISION' : 'UPDATE',
                'staff-roles-salary',
                $id,
                'Grade ' . $grade . ' salary ' . (!empty($result['revision']) ? 'revised' : 'saved') . ' for role ' . $id,
                null,
                $input
            );
            $_SESSION['message'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }

        $this->redirect('staff-roles/edit?id=' . urlencode($id) . '&grade=' . (int) $grade);
    }

    /**
     * Assign a salary grade to a staff member of this role.
     */
    public function assignSalaryGrade() {
        if (!$this->guardStaffRolesAccess()) {
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('staff-roles');
            return;
        }

        $id = trim((string) $this->post('staff_position_type_id', $this->get('id', '')));
        $staffId = trim((string) $this->post('staff_id', ''));
        $grade = $this->post('salary_grade', '');
        $activeGrade = (int) $this->post('grade', 3);

        $salaryModel = $this->model('StaffRoleSalaryModel');
        $result = $salaryModel->assignStaffGrade($staffId, $grade, $_SESSION['user_id'] ?? null);
        if ($result['ok']) {
            $this->logActivity(
                'UPDATE',
                'staff-roles-salary',
                $staffId,
                'Assigned salary grade ' . ($grade === '' ? 'none' : $grade) . ' for staff ' . $staffId,
                null,
                ['staff_id' => $staffId, 'salary_grade' => $grade]
            );
            $_SESSION['message'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }

        $this->redirect('staff-roles/edit?id=' . urlencode($id) . '&grade=' . $activeGrade);
    }

    /**
     * Persist calculated salaries (increments / revisions) for staff on this role.
     */
    public function applyStaffSalaries() {
        if (!$this->guardStaffRolesAccess()) {
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('staff-roles');
            return;
        }

        $id = trim((string) $this->post('staff_position_type_id', $this->get('id', '')));
        $activeGrade = (int) $this->post('redirect_grade', $this->post('grade', 3));
        if ($activeGrade < 1 || $activeGrade > 3) {
            $activeGrade = 3;
        }

        $salaryModel = $this->model('StaffRoleSalaryModel');
        $result = $salaryModel->applyToRoleStaff($id, null, $_SESSION['user_id'] ?? null);
        if ($result['ok']) {
            $this->logActivity(
                'UPDATE',
                'staff-roles-salary',
                $id,
                $result['message'],
                null,
                ['role' => $id, 'updated' => $result['updated']]
            );
            $_SESSION['message'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }

        $this->redirect('staff-roles/edit?id=' . urlencode($id) . '&grade=' . (int) $activeGrade);
    }

    private function guardStaffRolesAccess() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return false;
        }
        if (!$this->requireModule('staff_roles', 'edit')) {
            return false;
        }
        return true;
    }
    
    public function delete() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        if (!$this->requireModule('staff_roles', 'delete')) {
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

