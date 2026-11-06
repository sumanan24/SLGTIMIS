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
            $courseVersion = (int) $this->post('course_version', 0);
            
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

            $allowedVersions = $this->versionNumbersForCourse($courseModel, $courseId);
            if (!in_array($courseVersion, $allowedVersions, true)) {
                $_SESSION['error'] = 'Select a valid course version.';
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
                'course_version' => $courseVersion,
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
            $availableStudents = $groupModel->getAvailableStudents(
                $group['course_id'],
                $group['academic_year'],
                $id,
                isset($group['course_version']) ? (int) $group['course_version'] : 0
            );
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
     * Set every active student in one group to Internship or back to Following.
     */
    public function setEnrollmentStatus() {
        if (!$this->checkGroupAccess()) {
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('groups');
            return;
        }
        $id = (int) $this->post('id', 0);
        $status = $this->post('status', '') === 'Internship' ? 'Internship' : 'Following';
        $back = $this->post('return', '') === 'show' ? 'groups/show?id=' . $id : 'groups';
        if ($id <= 0) {
            $_SESSION['error'] = 'Choose a group first.';
            $this->redirect('groups');
            return;
        }
        $groupModel = $this->model('GroupModel');
        if (!$groupModel->canAccessGroup($id, $this->getUserDepartment())) {
            $_SESSION['error'] = 'Access denied. You can only update groups from your department.';
            $this->redirect('groups');
            return;
        }
        $members = count($groupModel->getGroupStudents($id));
        if ($members === 0) {
            $_SESSION['error'] = 'This group has no students to update.';
            $this->redirect($back);
            return;
        }
        $updated = $groupModel->setGroupEnrollmentStatus($id, $status);
        if ($updated > 0) {
            $_SESSION['message'] = $updated . ' student' . ($updated === 1 ? '' : 's') . ' set to ' . $status . '.';
        } else {
            $_SESSION['message'] = 'Students in this group are already ' . $status . '.';
        }
        $this->redirect($back);
    }

    /**
     * Add or update one class on this group's weekly timetable.
     */
    public function saveTimetable() {
        if (!$this->checkGroupAccess()) {
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('groups');
            return;
        }
        $groupId = (int) $this->post('group_id', 0);
        $back = 'groups/timetable?id=' . $groupId;
        $groupModel = $this->model('GroupModel');
        $group = $groupId > 0 ? $groupModel->getByIdWithDetails($groupId) : null;
        if (!$group || !$groupModel->canAccessGroup($groupId, $this->getUserDepartment())) {
            $_SESSION['error'] = 'Access denied. You can only update groups from your department.';
            $this->redirect('groups');
            return;
        }

        $timetable = $this->timetableModel();
        $days = GroupTimetableModel::dayNames();
        $periods = GroupTimetableModel::periods();
        $day = (int) $this->post('weekday', 0);
        $period = trim((string) $this->post('period', ''));
        $moduleId = trim((string) $this->post('module_id', ''));
        $subject = trim((string) $this->post('subject', ''));
        $staffId = trim((string) $this->post('staff_id', ''));
        $room = trim((string) $this->post('classroom', ''));
        $startDate = $this->isoDate($this->post('start_date', ''));
        $endDate = $this->isoDate($this->post('end_date', ''));
        $slotId = (int) $this->post('slot_id', 0);

        if (!isset($days[$day]) || !isset($periods[$period]) || $startDate === '' || $endDate === '' || $startDate > $endDate) {
            $_SESSION['error'] = 'Choose the day, period, and a finish date that is not before the start date.';
            $this->redirect($back);
            return;
        }
        if (strlen($subject) > 120) {
            $subject = substr($subject, 0, 120);
        }
        if (strlen($room) > 64) {
            $room = substr($room, 0, 64);
        }

        $context = $this->timetableContext($group);
        $allowedModules = [];
        foreach ($context['modules'] as $module) {
            $allowedModules[(string) $module['module_id']] = (string) ($module['module_name'] ?? $module['module_id']);
        }
        if ($moduleId !== '' && !isset($allowedModules[$moduleId])) {
            $_SESSION['error'] = 'Choose a module from this course.';
            $this->redirect($back);
            return;
        }
        if ($moduleId === '' && $subject === '') {
            $_SESSION['error'] = 'Type the class name, or choose a module.';
            $this->redirect($back);
            return;
        }
        $allowedStaff = [];
        foreach ($context['staff'] as $staff) {
            $allowedStaff[(string) $staff['staff_id']] = (string) ($staff['staff_name'] ?? $staff['staff_id']);
        }
        if ($staffId === '' || !isset($allowedStaff[$staffId])) {
            $_SESSION['error'] = 'Choose a lecturer from this department.';
            $this->redirect($back);
            return;
        }

        if ($slotId > 0 && !$timetable->findSlot($slotId, $groupId)) {
            $_SESSION['error'] = 'That class is no longer on this timetable.';
            $this->redirect($back);
            return;
        }
        if ($timetable->groupOverlaps($groupId, $day, $period, $startDate, $endDate, $slotId)) {
            $_SESSION['error'] = 'This group already has ' . $periods[$period] . ' on ' . $days[$day] . ' for those dates.';
            $this->redirect($back);
            return;
        }
        $busy = $timetable->staffOverlap($staffId, $day, $period, $startDate, $endDate, $slotId);
        if ($busy) {
            $_SESSION['error'] = $allowedStaff[$staffId] . ' already has ' . $periods[$period] . ' on ' . $days[$day] . ' in ' . $busy['group_name'] . '.';
            $this->redirect($back);
            return;
        }

        $saved = $timetable->saveSlot([
            'group_id' => $groupId,
            'weekday' => $day,
            'period' => $period,
            'module_id' => $moduleId !== '' ? $moduleId : null,
            'subject' => $subject !== '' ? $subject : null,
            'staff_id' => $staffId,
            'classroom' => $room !== '' ? $room : null,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ], $slotId);
        if (!$saved) {
            $_SESSION['error'] = 'The class could not be saved.';
            $this->redirect($back);
            return;
        }
        $_SESSION['message'] = $slotId > 0 ? 'Class updated.' : 'Class added to the timetable.';
        $this->redirect($back);
    }

    /**
     * Remove one class from this group's timetable.
     */
    public function deleteTimetable() {
        if (!$this->checkGroupAccess()) {
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('groups');
            return;
        }
        $groupId = (int) $this->post('group_id', 0);
        $slotId = (int) $this->post('slot_id', 0);
        $groupModel = $this->model('GroupModel');
        if ($groupId <= 0 || !$groupModel->canAccessGroup($groupId, $this->getUserDepartment())) {
            $_SESSION['error'] = 'Access denied. You can only update groups from your department.';
            $this->redirect('groups');
            return;
        }
        if ($this->timetableModel()->deleteSlot($slotId, $groupId)) {
            $_SESSION['message'] = 'Class removed from the timetable.';
        } else {
            $_SESSION['error'] = 'That class is no longer on this timetable.';
        }
        $this->redirect('groups/timetable?id=' . $groupId);
    }

    /**
     * Weekly class timetable for one group.
     */
    public function timetable() {
        if (!$this->checkGroupAccess()) {
            return;
        }
        $id = (int) $this->get('id', 0);
        if ($id <= 0) {
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
        if (!$groupModel->canAccessGroup($id, $this->getUserDepartment())) {
            $_SESSION['error'] = 'Access denied. You can only view groups from your department.';
            $this->redirect('groups');
            return;
        }
        $timetable = $this->timetableContext($group);
        $data = [
            'title' => 'Timetable: ' . $group['name'],
            'page' => 'groups',
            'group' => $group,
            'timetableSlots' => $timetable['slots'],
            'timetableModules' => $timetable['modules'],
            'timetableStaff' => $timetable['staff'],
            'message' => $_SESSION['message'] ?? null,
            'error' => $_SESSION['error'] ?? null,
        ];
        unset($_SESSION['message'], $_SESSION['error']);
        return $this->view('groups/timetable_page', $data);
    }

    /**
     * @return array{slots: array, modules: array, staff: array}
     */
    private function timetableContext(array $group) {
        $moduleModel = $this->model('ModuleModel');
        $modules = $moduleModel->getAllWithCourseFiltered([
            'course_id' => (string) ($group['course_id'] ?? ''),
            'course_version' => (int) ($group['course_version'] ?? 0),
        ]);
        $staff = $this->timetableModel()->listStaff((string) ($group['department_id'] ?? ''));
        return [
            'slots' => $this->timetableModel()->listByGroup((int) ($group['id'] ?? 0)),
            'modules' => $modules,
            'staff' => $staff,
        ];
    }

    private function timetableModel() {
        require_once BASE_PATH . '/models/GroupTimetableModel.php';
        return new GroupTimetableModel();
    }

    private function isoDate($value) {
        $value = trim((string) $value);
        $dt = DateTime::createFromFormat('Y-m-d', $value);
        if (!$dt || $dt->format('Y-m-d') !== $value) {
            return '';
        }
        return $value;
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
            $courseVersion = (int) $this->post('course_version', 0);
            
            // Validation
            if (empty($name)) {
                $_SESSION['error'] = 'Group name is required.';
                $this->redirect('groups/edit?id=' . urlencode($id));
                return;
            }

            $allowedVersions = $this->versionNumbersForCourse($courseModel, (string) ($group['course_id'] ?? ''), $courseVersion);
            if (!in_array($courseVersion, $allowedVersions, true)) {
                $_SESSION['error'] = 'Select a valid course version.';
                $this->redirect('groups/edit?id=' . urlencode($id));
                return;
            }
            
            // Update group
            $groupData = [
                'name' => $name,
                'status' => $status,
                'course_version' => $courseVersion
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
            
            $versions = $this->versionNumbersForCourse(
                $courseModel,
                (string) ($group['course_id'] ?? ''),
                isset($group['course_version']) ? (int) $group['course_version'] : 0
            );

            $data = [
                'title' => 'Edit Group',
                'page' => 'groups',
                'group' => $group,
                'departments' => $departments,
                'courses' => $courses,
                'academicYears' => $academicYears,
                'versions' => $versions,
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
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'error' => 'Invalid request method'], 405);
        }
        
        $groupId = $this->post('group_id', '');
        $studentIds = $this->post('student_ids', []);
        
        if ($groupId === '' || $groupId === null) {
            $this->json(['success' => false, 'error' => 'Group ID is required'], 400);
        }
        
        if (empty($studentIds) || !is_array($studentIds)) {
            $this->json(['success' => false, 'error' => 'Please select at least one student'], 400);
        }
        
        $groupModel = $this->model('GroupModel');
        
        // Check if user can access this group
        $departmentId = $this->getUserDepartment();
        if (!$groupModel->canAccessGroup($groupId, $departmentId)) {
            $this->json(['success' => false, 'error' => 'Access denied'], 403);
        }
        
        $result = $groupModel->addStudentsToGroup($groupId, $studentIds);
        
        if ($result) {
            $this->json(['success' => true, 'message' => 'Students added successfully']);
        }
        $this->json(['success' => false, 'error' => 'Failed to add students'], 500);
    }
    
    /**
     * Remove student from group (AJAX)
     */
    public function removeStudent() {
        // Check authentication and access
        if (!$this->checkGroupAccess()) {
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'error' => 'Invalid request method'], 405);
        }
        
        $groupId = $this->post('group_id', '');
        $studentId = $this->post('student_id', '');
        
        if ($groupId === '' || $groupId === null || $studentId === '' || $studentId === null) {
            $this->json(['success' => false, 'error' => 'Group ID and Student ID are required'], 400);
        }
        
        $groupModel = $this->model('GroupModel');
        
        // Check if user can access this group
        $departmentId = $this->getUserDepartment();
        if (!$groupModel->canAccessGroup($groupId, $departmentId)) {
            $this->json(['success' => false, 'error' => 'Access denied'], 403);
        }
        
        $result = $groupModel->removeStudentFromGroup($groupId, $studentId);
        
        if ($result) {
            $this->json(['success' => true, 'message' => 'Student removed successfully']);
        }
        $this->json(['success' => false, 'error' => 'Failed to remove student'], 500);
    }
    
    /**
     * Get courses by department (AJAX)
     */
    public function getCoursesByDepartment() {
        header('Content-Type: application/json');
        if (!$this->checkGroupAccess()) {
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            exit;
        }

        $departmentId = trim((string) $this->get('department_id', ''));
        if ($departmentId === '') {
            echo json_encode(['success' => true, 'courses' => []]);
            exit;
        }

        // Department-restricted roles can only query their own department
        $userDept = $this->getUserDepartment();
        if ($userDept && $departmentId !== $userDept) {
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            exit;
        }

        $courseModel = $this->model('CourseModel');
        $courses = $courseModel->getCoursesWithDepartment(['department_id' => $departmentId]);

        // Return lean payload (id + name) for dropdown
        $out = [];
        foreach ($courses as $c) {
            $cid = (string) ($c['course_id'] ?? '');
            $out[] = [
                'course_id' => $cid,
                'course_name' => (string) ($c['course_name'] ?? $c['course_id'] ?? ''),
                'versions' => $this->versionNumbersForCourse($courseModel, $cid),
            ];
        }
        echo json_encode(['success' => true, 'courses' => $out]);
        exit;
    }
    
    /**
     * Get available students by course and academic year (AJAX)
     */
    public function getAvailableStudents() {
        if (!isset($_SESSION['user_id'])) {
            $this->json(['success' => false, 'error' => 'Unauthorized'], 401);
        }
        
        $courseId = $this->get('course_id', '');
        $academicYear = $this->get('academic_year', '');
        $groupId = $this->get('group_id', '');
        
        if (empty($courseId) || empty($academicYear)) {
            $this->json(['success' => true, 'students' => []]);
        }
        
        $groupModel = $this->model('GroupModel');
        $courseVersion = null;
        if ($groupId !== '' && $groupId !== null) {
            $group = $groupModel->getByIdWithDetails($groupId);
            if ($group) {
                $courseVersion = isset($group['course_version']) ? (int) $group['course_version'] : 0;
            }
        }
        $students = $groupModel->getAvailableStudents($courseId, $academicYear, $groupId ?: null, $courseVersion);
        
        $this->json(['success' => true, 'students' => $students]);
    }

    /**
     * Version numbers for a course dropdown (always includes 0 = default).
     *
     * @return list<int>
     */
    private function versionNumbersForCourse($courseModel, $courseId, $includeVersion = null) {
        $versions = [0];
        if ($courseId !== '') {
            foreach ($courseModel->getVersionsForCourse($courseId) as $row) {
                $versions[] = (int) ($row['version_no'] ?? 0);
            }
        }
        if ($includeVersion !== null) {
            $versions[] = (int) $includeVersion;
        }
        $versions = array_values(array_unique($versions));
        sort($versions, SORT_NUMERIC);
        return $versions;
    }
}

