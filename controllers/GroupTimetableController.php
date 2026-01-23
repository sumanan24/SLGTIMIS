<?php
/**
 * Group Timetable Controller
 */

class GroupTimetableController extends Controller {
    
    /**
     * Check if user has timetable management access (HOD, ADM only)
     */
    private function checkTimetableAccess() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return false;
        }
        
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        $isAdmin = $userModel->isAdmin($_SESSION['user_id']);
        
        // Allow HOD, ADM, and Admin
        $allowedRoles = ['HOD', 'ADM'];
        $hasAccess = in_array($userRole, $allowedRoles) || $isAdmin;
        
        if (!$hasAccess) {
            $_SESSION['error'] = 'Access denied. Only HOD and ADM can manage timetables.';
            $this->redirect('dashboard');
            return false;
        }
        
        return true;
    }
    
    /**
     * Get user's department ID (for HOD)
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
        
        // HOD: use parent method
        if ($userRole === 'HOD') {
            return $this->getHODDepartment();
        }
        
        return null;
    }
    
    /**
     * List timetables for a group
     */
    public function index() {
        // Check authentication and access
        if (!$this->checkTimetableAccess()) {
            return;
        }
        
        $groupId = $this->get('group_id', '');
        if (empty($groupId)) {
            $_SESSION['error'] = 'Group ID is required.';
            $this->redirect('groups');
            return;
        }
        
        $timetableModel = $this->model('GroupTimetableModel');
        $groupModel = $this->model('GroupModel');
        
        // Ensure time_slot column exists in database
        $timetableModel->ensureTimeSlotColumn();
        
        $group = $groupModel->getByIdWithDetails($groupId);
        if (!$group) {
            $_SESSION['error'] = 'Group not found.';
            $this->redirect('groups');
            return;
        }
        
        // Check if user can access this group (department check)
        $departmentId = $this->getUserDepartment();
        if ($departmentId && $group['department_id'] !== $departmentId) {
            $_SESSION['error'] = 'Access denied. You can only manage timetables for groups in your department.';
            $this->redirect('groups');
            return;
        }
        
        $timetables = $timetableModel->getByGroupId($groupId);
        
        $data = [
            'title' => 'Timetable: ' . $group['name'],
            'page' => 'group-timetable',
            'group' => $group,
            'timetables' => $timetables,
            'message' => $_SESSION['message'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ];
        
        unset($_SESSION['message'], $_SESSION['error']);
        return $this->view('group-timetable/index', $data);
    }
    
    /**
     * Create new timetable entry
     */
    public function create() {
        // Check authentication and access
        if (!$this->checkTimetableAccess()) {
            return;
        }
        
        $groupId = $this->get('group_id', '');
        if (empty($groupId)) {
            $_SESSION['error'] = 'Group ID is required.';
            $this->redirect('groups');
            return;
        }
        
        $timetableModel = $this->model('GroupTimetableModel');
        $groupModel = $this->model('GroupModel');
        $staffModel = $this->model('StaffModel');
        
        // Ensure time_slot column exists in database
        $timetableModel->ensureTimeSlotColumn();
        
        $group = $groupModel->getByIdWithDetails($groupId);
        if (!$group) {
            $_SESSION['error'] = 'Group not found.';
            $this->redirect('groups');
            return;
        }
        
        // Check if user can access this group
        $departmentId = $this->getUserDepartment();
        if ($departmentId && $group['department_id'] !== $departmentId) {
            $_SESSION['error'] = 'Access denied. You can only manage timetables for groups in your department.';
            $this->redirect('groups');
            return;
        }
        
        // Get staff for the department
        $staff = [];
        if (!empty($group['department_id'])) {
            $staff = $staffModel->getStaffWithDepartment(1, 1000, '', $group['department_id']);
        }
        
        // Get modules (filter by course if available, otherwise get all)
        $modules = [];
        require_once BASE_PATH . '/core/Database.php';
        $db = Database::getInstance()->getConnection();
        if (!empty($group['course_id'])) {
            $sql = "SELECT DISTINCT module_id FROM `module` WHERE course_id = ? ORDER BY module_id";
            $stmt = $db->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("s", $group['course_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $modules[] = $row['module_id'];
                }
            }
        } else {
            // If no course_id, get all distinct module_ids
            $sql = "SELECT DISTINCT module_id FROM `module` ORDER BY module_id";
            $result = $db->query($sql);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $modules[] = $row['module_id'];
                }
            }
        }
        
        // Get existing timetable entries for this group
        $existingTimetables = $timetableModel->getByGroupId($groupId);
        
        // Organize existing entries by weekday and period for easy lookup
        // If multiple entries exist for same weekday+period, use the most recent one
        $existingDataMap = [];
        foreach ($existingTimetables as $entry) {
            $key = $entry['weekday'] . '_' . $entry['period'];
            // Only keep the first entry found (or most recent if sorted by updated_at)
            if (!isset($existingDataMap[$key])) {
                $existingDataMap[$key] = $entry;
            }
        }
        
        $weekdays = $timetableModel->getWeekdays();
        $periods = $timetableModel->getPeriods();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $startDate = trim($this->post('start_date', ''));
            $endDate = trim($this->post('end_date', ''));
            $timetableData = $this->post('timetable', []);
            
            $createdCount = 0;
            $errorCount = 0;
            
            // Process timetable grid data
            if (!empty($timetableData) && is_array($timetableData)) {
                foreach ($timetableData as $weekday => $timeSlots) {
                    if (!is_array($timeSlots)) continue;
                    
                    foreach ($timeSlots as $period => $entry) {
                        $moduleId = trim($entry['module_id'] ?? '');
                        $staffId = trim($entry['staff_id'] ?? '');
                        $classroom = trim($entry['classroom'] ?? '');
                        
                        // Check if entry already exists for this weekday and period
                        $dataKey = $weekday . '_' . $period;
                        $existingEntry = $existingDataMap[$dataKey] ?? null;
                        
                        // Ensure only one entry per weekday+period: delete any duplicates first
                        if (!$existingEntry) {
                            // Check for duplicates even if not in our map (might be duplicates in DB)
                            $allEntries = $timetableModel->getByGroupId($groupId);
                            foreach ($allEntries as $dup) {
                                if ($dup['weekday'] === $weekday && $dup['period'] === $period) {
                                    if (!$existingEntry) {
                                        $existingEntry = $dup; // Use first found as existing
                                    } else {
                                        // Delete duplicate
                                        $timetableModel->deleteTimetable($dup['timetable_id']);
                                    }
                                }
                            }
                        } else {
                            // Delete any other duplicates for this slot
                            $allEntries = $timetableModel->getByGroupId($groupId);
                            foreach ($allEntries as $dup) {
                                if ($dup['weekday'] === $weekday && 
                                    $dup['period'] === $period && 
                                    $dup['timetable_id'] != $existingEntry['timetable_id']) {
                                    $timetableModel->deleteTimetable($dup['timetable_id']);
                                }
                            }
                        }
                        
                        // Only process if at least module or staff is provided
                        if (!empty($moduleId) || !empty($staffId)) {
                            $entryData = [
                                'module_id' => $moduleId ?: null,
                                'staff_id' => $staffId ?: null,
                                'classroom' => $classroom ?: null,
                                'start_date' => $startDate ?: null,
                                'end_date' => $endDate ?: null,
                                'active' => 1,
                                'updated_at' => date('Y-m-d H:i:s')
                            ];
                            
                            if ($existingEntry) {
                                // Update existing entry
                                $result = $timetableModel->updateTimetable($existingEntry['timetable_id'], $entryData);
                                if ($result) {
                                    $createdCount++; // Count as successful operation
                                } else {
                                    $errorCount++;
                                }
                            } else {
                                // Create new entry (only if no existing entry for this slot)
                                $entryData['group_id'] = $groupId;
                                $entryData['weekday'] = $weekday;
                                $entryData['period'] = $period; // Time slot stored in period field
                                $entryData['time_slot'] = $period; // Also store in time_slot column
                                $entryData['created_at'] = date('Y-m-d H:i:s');
                                
                                $timetableId = $timetableModel->createTimetable($entryData);
                                if ($timetableId) {
                                    $createdCount++;
                                } else {
                                    $errorCount++;
                                }
                            }
                        } elseif ($existingEntry && empty($moduleId) && empty($staffId)) {
                            // If existing entry exists but form fields are cleared, delete it
                            $timetableModel->deleteTimetable($existingEntry['timetable_id']);
                            $createdCount++; // Count as successful operation
                        }
                    }
                }
            }
            
            if ($createdCount > 0) {
                $_SESSION['message'] = "Successfully created {$createdCount} timetable " . ($createdCount == 1 ? 'entry' : 'entries') . ".";
                if ($errorCount > 0) {
                    $_SESSION['message'] .= " {$errorCount} " . ($errorCount == 1 ? 'entry' : 'entries') . " failed to create.";
                }
                $this->redirect('group-timetable/index?group_id=' . urlencode($groupId));
            } else {
                $_SESSION['error'] = 'No timetable entries were created. Please fill in at least Module ID or Staff for the entries you want to create.';
                $this->redirect('group-timetable/create?group_id=' . urlencode($groupId));
            }
        } else {
            $timeSlots = $timetableModel->getTimeSlots();
            $data = [
                'title' => 'Create Timetable - ' . $group['name'],
                'page' => 'group-timetable',
                'group' => $group,
                'staff' => $staff,
                'modules' => $modules,
                'weekdays' => $weekdays,
                'periods' => $periods,
                'timeSlots' => $timeSlots,
                'existingDataMap' => $existingDataMap,
                'error' => $_SESSION['error'] ?? null
            ];
            unset($_SESSION['error']);
            return $this->view('group-timetable/create', $data);
        }
    }
    
    /**
     * Edit timetable entry
     */
    public function edit() {
        // Check authentication and access
        if (!$this->checkTimetableAccess()) {
            return;
        }
        
        $id = $this->get('id', '');
        if (empty($id)) {
            $_SESSION['error'] = 'Timetable ID is required.';
            $this->redirect('groups');
            return;
        }
        
        $timetableModel = $this->model('GroupTimetableModel');
        $staffModel = $this->model('StaffModel');
        
        // Ensure time_slot column exists in database
        $timetableModel->ensureTimeSlotColumn();
        
        $timetable = $timetableModel->getByIdWithDetails($id);
        if (!$timetable) {
            $_SESSION['error'] = 'Timetable entry not found.';
            $this->redirect('groups');
            return;
        }
        
        // Check if user can access this group
        $departmentId = $this->getUserDepartment();
        if ($departmentId && $timetable['department_id'] !== $departmentId) {
            $_SESSION['error'] = 'Access denied. You can only manage timetables for groups in your department.';
            $this->redirect('groups');
            return;
        }
        
        // Get staff for the department  
        $staff = [];
        if (!empty($timetable['department_id'])) {
            $staff = $staffModel->getStaffWithDepartment(1, 1000, '', $timetable['department_id']);
        }
        
        // Get modules (filter by course if available, otherwise get all)
        $modules = [];
        require_once BASE_PATH . '/core/Database.php';
        $db = Database::getInstance()->getConnection();
        if (!empty($timetable['course_id'])) {
            $sql = "SELECT DISTINCT module_id FROM `module` WHERE course_id = ? ORDER BY module_id";
            $stmt = $db->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("s", $timetable['course_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $modules[] = $row['module_id'];
                }
            }
        } else {
            // If no course_id, get all distinct module_ids
            $sql = "SELECT DISTINCT module_id FROM `module` ORDER BY module_id";
            $result = $db->query($sql);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $modules[] = $row['module_id'];
                }
            }
        }
        
        $weekdays = $timetableModel->getWeekdays();
        $periods = $timetableModel->getPeriods();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $moduleId = trim($this->post('module_id', ''));
            $staffId = trim($this->post('staff_id', ''));
            $weekday = trim($this->post('weekday', ''));
            $period = trim($this->post('period', ''));
            $classroom = trim($this->post('classroom', ''));
            $startDate = trim($this->post('start_date', ''));
            $endDate = trim($this->post('end_date', ''));
            $active = $this->post('active', 1) ? 1 : 0;
            
            // Validation
            if (empty($weekday)) {
                $_SESSION['error'] = 'Weekday is required.';
                $this->redirect('group-timetable/edit?id=' . urlencode($id));
                return;
            }
            
            if (empty($period)) {
                $_SESSION['error'] = 'Period is required.';
                $this->redirect('group-timetable/edit?id=' . urlencode($id));
                return;
            }
            
            // Update timetable entry
            $timetableData = [
                'module_id' => $moduleId ?: null,
                'staff_id' => $staffId ?: null,
                'weekday' => $weekday,
                'period' => $period, // Time slot stored in period field
                'time_slot' => $period, // Also store in time_slot column if exists
                'classroom' => $classroom ?: null,
                'start_date' => $startDate ?: null,
                'end_date' => $endDate ?: null,
                'active' => $active,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $result = $timetableModel->updateTimetable($id, $timetableData);
            
            if ($result) {
                $_SESSION['message'] = 'Timetable entry updated successfully.';
                $this->redirect('group-timetable/index?group_id=' . urlencode($timetable['group_id']));
            } else {
                $_SESSION['error'] = 'Failed to update timetable entry.';
                $this->redirect('group-timetable/edit?id=' . urlencode($id));
            }
        } else {
            $data = [
                'title' => 'Edit Timetable Entry',
                'page' => 'group-timetable',
                'timetable' => $timetable,
                'staff' => $staff,
                'modules' => $modules,
                'weekdays' => $weekdays,
                'periods' => $periods,
                'error' => $_SESSION['error'] ?? null
            ];
            unset($_SESSION['error']);
            return $this->view('group-timetable/edit', $data);
        }
    }
    
    /**
     * Delete timetable entry
     */
    public function delete() {
        // Check authentication and access
        if (!$this->checkTimetableAccess()) {
            return;
        }
        
        $id = $this->get('id', '');
        if (empty($id)) {
            $_SESSION['error'] = 'Timetable ID is required.';
            $this->redirect('groups');
            return;
        }
        
        $timetableModel = $this->model('GroupTimetableModel');
        $timetable = $timetableModel->getByIdWithDetails($id);
        
        if (!$timetable) {
            $_SESSION['error'] = 'Timetable entry not found.';
            $this->redirect('groups');
            return;
        }
        
        // Check if user can access this group
        $departmentId = $this->getUserDepartment();
        if ($departmentId && $timetable['department_id'] !== $departmentId) {
            $_SESSION['error'] = 'Access denied. You can only manage timetables for groups in your department.';
            $this->redirect('groups');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $timetableModel->deleteTimetable($id);
            
            if ($result) {
                $_SESSION['message'] = 'Timetable entry deleted successfully.';
            } else {
                $_SESSION['error'] = 'Failed to delete timetable entry.';
            }
            
            $this->redirect('group-timetable/index?group_id=' . urlencode($timetable['group_id']));
        } else {
            $data = [
                'title' => 'Delete Timetable Entry',
                'page' => 'group-timetable',
                'timetable' => $timetable
            ];
            return $this->view('group-timetable/delete', $data);
        }
    }
    
    /**
     * View timetable (for students)
     */
    public function studentView() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Check if user is a student
        if (!isset($_SESSION['user_table']) || $_SESSION['user_table'] !== 'student') {
            $_SESSION['error'] = 'Access denied. This page is only available for students.';
            $this->redirect('dashboard');
            return;
        }
        
        $studentId = $_SESSION['user_name'] ?? null;
        if (!$studentId) {
            $_SESSION['error'] = 'Student ID not found.';
            $this->redirect('dashboard');
            return;
        }
        
        $timetableModel = $this->model('GroupTimetableModel');
        $groupModel = $this->model('GroupModel');
        
        // Get groups that the student belongs to
        $groups = $groupModel->getAllWithDetails();
        $studentGroups = [];
        foreach ($groups as $group) {
            $groupStudents = $groupModel->getGroupStudents($group['id']);
            foreach ($groupStudents as $student) {
                if ($student['student_id'] === $studentId) {
                    $studentGroups[] = $group['id'];
                    break;
                }
            }
        }
        
        // Get timetables for student's groups
        $timetables = [];
        if (!empty($studentGroups)) {
            $timetables = $timetableModel->getByGroupIds($studentGroups);
        }
        
        $data = [
            'title' => 'My Timetable',
            'page' => 'timetable',
            'timetables' => $timetables,
            'studentGroups' => $studentGroups,
            'message' => $_SESSION['message'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ];
        
        unset($_SESSION['message'], $_SESSION['error']);
        return $this->view('group-timetable/student-view', $data);
    }
}

