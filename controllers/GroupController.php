<?php
/**
 * Group Controller
 */

class GroupController extends Controller {
    
    /**
     * Check if user has group access (HOD, IN1, IN2, IN3, ADM)
     * HOD/IN1/IN2/IN3 can only access their own department
     * ADM can access all departments
     */
    private function checkGroupAccess() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return false;
        }
        
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        $isAdmin = $userModel->isAdmin($_SESSION['user_id']);
        
        // Allow HOD, IN1, IN2, IN3, ADM, and Admin
        $allowedRoles = ['HOD', 'IN1', 'IN2', 'IN3', 'ADM'];
        $hasAccess = in_array($userRole, $allowedRoles) || $isAdmin;
        
        if (!$hasAccess) {
            $_SESSION['error'] = 'Access denied. Only HOD, IN1, IN2, IN3, and ADM can access groups.';
            $this->redirect('dashboard');
            return false;
        }
        
        return true;
    }
    
    /**
     * Get user's department ID
     * For HOD: use getHODDepartment()
     * For IN1/IN2/IN3: get from staff table
     * For ADM/Admin: return null (can access all)
     */
    private function getUserDepartment() {
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
        
        // HOD: use existing method
        if ($userRole === 'HOD') {
            return $userModel->getHODDepartment($_SESSION['user_id']);
        }
        
        // IN1, IN2, IN3: get department from staff table
        if (in_array($userRole, ['IN1', 'IN2', 'IN3'])) {
            $user = $userModel->find($_SESSION['user_id']);
            if (!$user || !isset($user['user_name'])) {
                return null;
            }
            
            $staffModel = $this->model('StaffModel');
            $staff = $staffModel->find($user['user_name']);
            return $staff ? ($staff['department_id'] ?? null) : null;
        }
        
        return null;
    }
    
    /**
     * List all groups
     */
    public function index() {
        // Check authentication and access
        if (!$this->checkGroupAccess()) {
            return;
        }
        
        $groupModel = $this->model('GroupModel');
        $departmentId = $this->getUserDepartment();
        
        // Get groups (filtered by department for HOD/IN1/IN2/IN3, all for ADM)
        $groups = $groupModel->getAllWithDetails($departmentId);
        
        $data = [
            'title' => 'Student Groups',
            'page' => 'groups',
            'groups' => $groups,
            'message' => $_SESSION['message'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ];
        
        unset($_SESSION['message'], $_SESSION['error']);
        return $this->view('groups/index', $data);
    }
    
    /**
     * Create new group
     */
    public function create() {
        // Check authentication and access
        if (!$this->checkGroupAccess()) {
            return;
        }
        
        $departmentModel = $this->model('DepartmentModel');
        $courseModel = $this->model('CourseModel');
        $studentModel = $this->model('StudentModel');
        $groupModel = $this->model('GroupModel');
        
        $departmentId = $this->getUserDepartment();
        
        // Get departments (only user's department for HOD/IN1/IN2/IN3, all for ADM)
        if ($departmentId) {
            $dept = $departmentModel->getById($departmentId);
            $departments = $dept ? [$dept] : [];
        } else {
            $departments = $departmentModel->getAll();
        }
        
        // Get academic years
        $academicYears = $studentModel->getAcademicYears();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($this->post('name', ''));
            $courseId = trim($this->post('course_id', ''));
            $academicYear = trim($this->post('academic_year', ''));
            $status = trim($this->post('status', 'active'));
            
            // Validation
            if (empty($name)) {
                $_SESSION['error'] = 'Group name is required.';
                $this->redirect('groups/create');
                return;
            }
            
            if (empty($courseId)) {
                $_SESSION['error'] = 'Course is required.';
                $this->redirect('groups/create');
                return;
            }
            
            if (empty($academicYear)) {
                $_SESSION['error'] = 'Academic year is required.';
                $this->redirect('groups/create');
                return;
            }
            
            // Verify course belongs to user's department (if not ADM)
            if ($departmentId) {
                $course = $courseModel->getById($courseId);
                if (!$course || $course['department_id'] !== $departmentId) {
                    $_SESSION['error'] = 'Access denied. You can only create groups for courses in your department.';
                    $this->redirect('groups');
                    return;
                }
            }
            
            // Create group
            $groupData = [
                'name' => $name,
                'course_id' => $courseId,
                'academic_year' => $academicYear,
                'status' => $status,
                'created_by' => $_SESSION['user_name'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $groupId = $groupModel->createGroup($groupData);
            
            if ($groupId) {
                $_SESSION['message'] = 'Group created successfully.';
                $this->redirect('groups/show?id=' . $groupId);
            } else {
                $_SESSION['error'] = 'Failed to create group.';
                $this->redirect('groups/create');
            }
        } else {
            $data = [
                'title' => 'Create Group',
                'page' => 'groups',
                'departments' => $departments,
                'academicYears' => $academicYears,
                'courses' => [],
                'error' => $_SESSION['error'] ?? null
            ];
            unset($_SESSION['error']);
            return $this->view('groups/create', $data);
        }
    }
    
    /**
     * View group details and students
     */
    public function show() {
        // Check authentication and access
        if (!$this->checkGroupAccess()) {
            return;
        }
        
        $id = $this->get('id', '');
        if (empty($id)) {
            $_SESSION['error'] = 'Group ID is required.';
            $this->redirect('groups');
            return;
        }
        
        $groupModel = $this->model('GroupModel');
        $group = $groupModel->getByIdWithDetails($id);
        
        if (!$group) {
            $_SESSION['error'] = 'Group not found.';
            $this->redirect('groups');
            return;
        }
        
        // Check if user can access this group (department check)
        $departmentId = $this->getUserDepartment();
        if (!$groupModel->canAccessGroup($id, $departmentId)) {
            $_SESSION['error'] = 'Access denied. You can only view groups from your department.';
            $this->redirect('groups');
            return;
        }
        
        // Get students in the group
        $students = $groupModel->getGroupStudents($id);
        
        // Get available students for adding
        $availableStudents = [];
        if (!empty($group['course_id']) && !empty($group['academic_year'])) {
            $availableStudents = $groupModel->getAvailableStudents($group['course_id'], $group['academic_year'], $id);
        }
        
        $data = [
            'title' => 'Group: ' . $group['name'],
            'page' => 'groups',
            'group' => $group,
            'students' => $students,
            'availableStudents' => $availableStudents,
            'message' => $_SESSION['message'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ];
        
        unset($_SESSION['message'], $_SESSION['error']);
        return $this->view('groups/view', $data);
    }
    
    /**
     * Edit group
     */
    public function edit() {
        // Check authentication and access
        if (!$this->checkGroupAccess()) {
            return;
        }
        
        $id = $this->get('id', '');
        if (empty($id)) {
            $_SESSION['error'] = 'Group ID is required.';
            $this->redirect('groups');
            return;
        }
        
        $groupModel = $this->model('GroupModel');
        $departmentModel = $this->model('DepartmentModel');
        $courseModel = $this->model('CourseModel');
        $studentModel = $this->model('StudentModel');
        
        $group = $groupModel->getByIdWithDetails($id);
        
        if (!$group) {
            $_SESSION['error'] = 'Group not found.';
            $this->redirect('groups');
            return;
        }
        
        // Check if user can access this group
        $departmentId = $this->getUserDepartment();
        if (!$groupModel->canAccessGroup($id, $departmentId)) {
            $_SESSION['error'] = 'Access denied. You can only edit groups from your department.';
            $this->redirect('groups');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($this->post('name', ''));
            $status = trim($this->post('status', 'active'));
            
            // Validation
            if (empty($name)) {
                $_SESSION['error'] = 'Group name is required.';
                $this->redirect('groups/edit?id=' . urlencode($id));
                return;
            }
            
            // Update group
            $groupData = [
                'name' => $name,
                'status' => $status
            ];
            
            $result = $groupModel->updateGroup($id, $groupData);
            
            if ($result) {
                $_SESSION['message'] = 'Group updated successfully.';
                $this->redirect('groups/show?id=' . $id);
            } else {
                $_SESSION['error'] = 'Failed to update group.';
                $this->redirect('groups/edit?id=' . urlencode($id));
            }
        } else {
            // Get departments
            $departmentId = $this->getUserDepartment();
            if ($departmentId) {
                $dept = $departmentModel->getById($departmentId);
                $departments = $dept ? [$dept] : [];
            } else {
                $departments = $departmentModel->getAll();
            }
            
            $academicYears = $studentModel->getAcademicYears();
            
            // Get courses for the group's department
            $courses = [];
            if (!empty($group['department_id'])) {
                $courses = $courseModel->getCoursesWithDepartment(['department_id' => $group['department_id']]);
            }
            
            $data = [
                'title' => 'Edit Group',
                'page' => 'groups',
                'group' => $group,
                'departments' => $departments,
                'courses' => $courses,
                'academicYears' => $academicYears,
                'error' => $_SESSION['error'] ?? null
            ];
            unset($_SESSION['error']);
            return $this->view('groups/edit', $data);
        }
    }
    
    /**
     * Delete group
     */
    public function delete() {
        // Check authentication and access
        if (!$this->checkGroupAccess()) {
            return;
        }
        
        $id = $this->get('id', '');
        if (empty($id)) {
            $_SESSION['error'] = 'Group ID is required.';
            $this->redirect('groups');
            return;
        }
        
        $groupModel = $this->model('GroupModel');
        $group = $groupModel->getByIdWithDetails($id);
        
        if (!$group) {
            $_SESSION['error'] = 'Group not found.';
            $this->redirect('groups');
            return;
        }
        
        // Check if user can access this group
        $departmentId = $this->getUserDepartment();
        if (!$groupModel->canAccessGroup($id, $departmentId)) {
            $_SESSION['error'] = 'Access denied. You can only delete groups from your department.';
            $this->redirect('groups');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $groupModel->deleteGroup($id);
            
            if ($result) {
                $_SESSION['message'] = 'Group deleted successfully.';
            } else {
                $_SESSION['error'] = 'Failed to delete group.';
            }
            
            $this->redirect('groups');
        } else {
            $data = [
                'title' => 'Delete Group',
                'page' => 'groups',
                'group' => $group
            ];
            return $this->view('groups/delete', $data);
        }
    }
    
    /**
     * Add students to group (AJAX)
     */
    public function addStudents() {
        // Check authentication and access
        if (!$this->checkGroupAccess()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Invalid request method']);
            return;
        }
        
        $groupId = $this->post('group_id', '');
        $studentIds = $this->post('student_ids', []);
        
        if (empty($groupId)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Group ID is required']);
            return;
        }
        
        if (empty($studentIds) || !is_array($studentIds)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Please select at least one student']);
            return;
        }
        
        $groupModel = $this->model('GroupModel');
        
        // Check if user can access this group
        $departmentId = $this->getUserDepartment();
        if (!$groupModel->canAccessGroup($groupId, $departmentId)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            return;
        }
        
        $result = $groupModel->addStudentsToGroup($groupId, $studentIds);
        
        if ($result) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Students added successfully']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Failed to add students']);
        }
    }
    
    /**
     * Remove student from group (AJAX)
     */
    public function removeStudent() {
        // Check authentication and access
        if (!$this->checkGroupAccess()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Invalid request method']);
            return;
        }
        
        $groupId = $this->post('group_id', '');
        $studentId = $this->post('student_id', '');
        
        if (empty($groupId) || empty($studentId)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Group ID and Student ID are required']);
            return;
        }
        
        $groupModel = $this->model('GroupModel');
        
        // Check if user can access this group
        $departmentId = $this->getUserDepartment();
        if (!$groupModel->canAccessGroup($groupId, $departmentId)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            return;
        }
        
        $result = $groupModel->removeStudentFromGroup($groupId, $studentId);
        
        if ($result) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Student removed successfully']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Failed to remove student']);
        }
    }
    
    /**
     * Get courses by department (AJAX)
     */
    public function getCoursesByDepartment() {
        if (!isset($_SESSION['user_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
        
        $departmentId = $this->get('department_id', '');
        
        if (empty($departmentId)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'courses' => []]);
            return;
        }
        
        $courseModel = $this->model('CourseModel');
        $courses = $courseModel->getCoursesWithDepartment(['department_id' => $departmentId]);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'courses' => $courses]);
    }
    
    /**
     * Get available students by course and academic year (AJAX)
     */
    public function getAvailableStudents() {
        if (!isset($_SESSION['user_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
        
        $courseId = $this->get('course_id', '');
        $academicYear = $this->get('academic_year', '');
        $groupId = $this->get('group_id', '');
        
        if (empty($courseId) || empty($academicYear)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'students' => []]);
            return;
        }
        
        $groupModel = $this->model('GroupModel');
        $students = $groupModel->getAvailableStudents($courseId, $academicYear, $groupId ?: null);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'students' => $students]);
    }
}

