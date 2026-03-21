<?php
/**
 * Group Timetable Controller - Full CRUD
 * group_id from URL; time_slot = 08:30-10:00, 10:30-12:00, 13:00-14:30, 14:45-16:15
 * Modules from module table; Lecturers = department staff
 */

class GroupTimetableController extends Controller {

    /**
     * Whether current user may manage timetables (no redirect).
     */
    private function userCanManageTimetable() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        $isAdmin = $userModel->isAdmin($_SESSION['user_id']);
        $allowedRoles = ['HOD', 'ADM'];
        return in_array($userRole, $allowedRoles) || $isAdmin;
    }

    private function checkTimetableAccess() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return false;
        }
        if (!$this->userCanManageTimetable()) {
            $_SESSION['error'] = 'Access denied. Only HOD and ADM can manage timetables.';
            $this->redirect('dashboard');
            return false;
        }
        return true;
    }

    /**
     * List timetable entries for group_id (from URL)
     */
    public function index() {
        if (!$this->checkTimetableAccess()) return;
        $groupId = $this->get('group_id', '');
        $groupModel = $this->model('GroupModel');
        $timetableModel = $this->model('GroupTimetableModel');
        $group = null;
        $entries = [];
        $modules = [];
        $staff = [];
        $groupsList = [];
        $weekdaysToShow = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        $grid = [];
        // Load groups user can access (for group selector)
        $departmentId = $this->getUserDepartment();
        $groupsList = $groupModel->getAllWithDetails($departmentId);
        
        if ($groupId !== '') {
            $group = $groupModel->getByIdWithDetails($groupId);
            if ($group) {
                $entries = $timetableModel->getByGroupId($groupId);
                $modules = $timetableModel->getModulesByCourseId($group['course_id'] ?? '');
                $staffModel = $this->model('StaffModel');
                $staff = $staffModel->getStaffWithDepartment(1, 1000, '', $group['department_id'] ?? '');
                $timeSlotsAssoc = $timetableModel::getTimeSlots();
                $slotKeys = array_keys($timeSlotsAssoc);
                foreach ($weekdaysToShow as $d) {
                    foreach ($slotKeys as $slotKey) {
                        $grid[$d][$slotKey] = null;
                    }
                }
                foreach ($entries as $e) {
                    // Day: full name (day column) or legacy weekday 1–7
                    $day = '';
                    if (!empty($e['day'])) {
                        $day = $timetableModel::normalizeDay((string) $e['day']);
                    } elseif (isset($e['weekday']) && $e['weekday'] !== '') {
                        $day = $timetableModel::mapWeekdayNumberToDayName($e['weekday']);
                    }
                    // Slot: time_slot string or legacy period P1–P4
                    $slot = '';
                    if (!empty($e['time_slot'])) {
                        $slot = $timetableModel::resolveGridSlotKey((string) $e['time_slot']);
                    } elseif (!empty($e['period'])) {
                        $slot = $timetableModel::mapPeriodToSlotKey((string) $e['period']);
                    }
                    if ($day !== '' && $slot !== '' && isset($grid[$day][$slot])) {
                        if (!isset($e['id']) || $e['id'] === null || $e['id'] === '') {
                            if (isset($e['timetable_id'])) {
                                $e['id'] = $e['timetable_id'];
                            } elseif (isset($e['entry_id'])) {
                                $e['id'] = $e['entry_id'];
                            }
                        }
                        $grid[$day][$slot] = $e;
                    }
                }
            }
        }
        $timeSlots = $timetableModel::getTimeSlots();
        $data = [
            'title' => $group ? ('Timetable: ' . $group['name']) : 'Group Timetable',
            'page' => 'group-timetable',
            'group_id' => $groupId,
            'groupsList' => $groupsList,
            'group' => $group,
            'entries' => $entries,
            'grid' => $grid,
            'weekdaysToShow' => $weekdaysToShow,
            'timeSlots' => $timeSlots,
            'modules' => $modules,
            'staff' => $staff,
            'message' => $_SESSION['message'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ];
        unset($_SESSION['message'], $_SESSION['error']);
        return $this->view('group-timetable/index', $data);
    }

    /**
     * AJAX: save a new slot (module + staff required). Returns JSON; includes MySQL error text on failure.
     */
    public function saveSlot() {
        header('Content-Type: application/json; charset=utf-8');
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            return;
        }
        if (!$this->userCanManageTimetable()) {
            echo json_encode(['success' => false, 'error' => 'Access denied. Only HOD and ADM can manage timetables.']);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid method']);
            return;
        }
        $groupId = trim($this->post('group_id', ''));
        $day = trim($this->post('day', ''));
        $timeSlot = trim($this->post('time_slot', ''));
        $moduleId = trim($this->post('module_id', ''));
        $staffId = trim($this->post('staff_id', ''));
        if ($groupId === '' || $day === '' || $timeSlot === '') {
            echo json_encode(['success' => false, 'error' => 'Group, day, and time slot are required.']);
            return;
        }
        if ($moduleId === '' || $staffId === '') {
            echo json_encode(['success' => false, 'error' => 'Module and Staff are required.']);
            return;
        }
        $groupModel = $this->model('GroupModel');
        $departmentId = $this->getUserDepartment();
        if (!$groupModel->canAccessGroup((int) $groupId, $departmentId)) {
            echo json_encode(['success' => false, 'error' => 'Access denied for this group.']);
            return;
        }
        $timetableModel = $this->model('GroupTimetableModel');
        $dayNorm = $timetableModel::normalizeDay($day);
        $timeSlotNorm = $timetableModel::normalizeTimeSlot($timeSlot);
        $slotKey = $timetableModel::resolveGridSlotKey($timeSlotNorm);
        if ($slotKey === '') {
            $slotKey = $timeSlotNorm;
        }
        if ($timetableModel->existsSlot($groupId, $dayNorm, $slotKey, null)) {
            echo json_encode(['success' => false, 'error' => 'This group already has an entry for the same day and time slot.']);
            return;
        }
        $sqlErr = '';
        $newId = $timetableModel->createTimetable([
            'group_id' => (int) $groupId,
            'day' => $dayNorm ?: null,
            'time_slot' => $slotKey ?: null,
            'subject' => null,
            'session_type' => 'Theory',
            'module_id' => $moduleId,
            'staff_id' => $staffId,
            'room' => null
        ], $sqlErr);
        if ($newId) {
            echo json_encode(['success' => true, 'message' => 'Timetable entry saved.', 'id' => (int) $newId]);
            return;
        }
        echo json_encode([
            'success' => false,
            'error' => 'Failed to save timetable entry.',
            'sql_error' => $sqlErr !== '' ? $sqlErr : null
        ]);
    }

    /**
     * Create: GET form (group_id from URL) or POST store
     */
    public function create() {
        if (!$this->checkTimetableAccess()) return;
        $groupId = $this->get('group_id', '');
        $groupModel = $this->model('GroupModel');
        $timetableModel = $this->model('GroupTimetableModel');
        $group = $groupId !== '' ? $groupModel->getByIdWithDetails($groupId) : null;
        $modules = [];
        $staff = [];
        if ($group) {
            $modules = $timetableModel->getModulesByCourseId($group['course_id'] ?? '');
            $staffModel = $this->model('StaffModel');
            $staff = $staffModel->getStaffWithDepartment(1, 1000, '', $group['department_id'] ?? '');
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $groupIdPost = trim($this->post('group_id', ''));
            $day = trim($this->post('day', ''));
            $timeSlot = trim($this->post('time_slot', ''));
            $subject = trim($this->post('subject', ''));
            $sessionType = trim($this->post('session_type', 'Theory'));
            $moduleId = trim($this->post('module_id', ''));
            $staffId = trim($this->post('staff_id', ''));
            $room = trim($this->post('room', ''));
            if ($sessionType !== 'Practical') $sessionType = 'Theory';
            $dayNorm = $timetableModel::normalizeDay($day);
            $timeSlotNorm = $timetableModel::normalizeTimeSlot($timeSlot);
            if ($groupIdPost !== '' && $dayNorm !== '' && $timeSlotNorm !== '' && $timetableModel->existsSlot($groupIdPost, $dayNorm, $timeSlotNorm, null)) {
                $_SESSION['error'] = 'This group already has an entry for the same day and time slot. Please choose a different day or time.';
                $this->redirect('group-timetable/create?group_id=' . urlencode($groupIdPost ?: $groupId) . '&day=' . urlencode($day) . '&time_slot=' . urlencode($timeSlot));
                return;
            }
            $sqlErr = '';
            $id = $timetableModel->createTimetable([
                'group_id' => $groupIdPost ?: null,
                'day' => $dayNorm ?: null,
                'time_slot' => $timeSlotNorm ?: null,
                'subject' => $subject ?: null,
                'session_type' => $sessionType,
                'module_id' => $moduleId ?: null,
                'staff_id' => $staffId ?: null,
                'room' => $room ?: null
            ], $sqlErr);
            if ($id) {
                $_SESSION['message'] = 'Timetable entry created successfully.';
                $this->redirect('group-timetable/index?group_id=' . urlencode($groupIdPost ?: $groupId));
            } else {
                $_SESSION['error'] = 'Failed to create timetable entry.' . ($sqlErr !== '' ? ' SQL: ' . $sqlErr : '');
                $this->redirect('group-timetable/create?group_id=' . urlencode($groupIdPost ?: $groupId));
            }
            return;
        }
        $defaultDay = $this->get('day', '');
        $defaultTimeSlot = $this->get('time_slot', '');
        $data = [
            'title' => 'Create Timetable Entry',
            'page' => 'group-timetable',
            'group_id' => $groupId,
            'group' => $group,
            'days' => $timetableModel::getDays(),
            'sessionTypes' => $timetableModel::getSessionTypes(),
            'timeSlots' => $timetableModel::getTimeSlots(),
            'modules' => $modules,
            'staff' => $staff,
            'defaultDay' => $defaultDay,
            'defaultTimeSlot' => $defaultTimeSlot,
            'entry' => null,
            'error' => $_SESSION['error'] ?? null
        ];
        unset($_SESSION['error']);
        return $this->view('group-timetable/create', $data);
    }

    /**
     * Edit: GET form or POST update
     */
    public function edit() {
        if (!$this->checkTimetableAccess()) return;
        $id = $this->get('id', '');
        if ($id === '') {
            $_SESSION['error'] = 'Entry ID required.';
            $this->redirect('group-timetable/index');
            return;
        }
        $timetableModel = $this->model('GroupTimetableModel');
        $groupModel = $this->model('GroupModel');
        $entry = $timetableModel->getById($id);
        if (!$entry) {
            $_SESSION['error'] = 'Timetable entry not found.';
            $this->redirect('group-timetable/index');
            return;
        }
        $groupId = $entry['group_id'] ?? $this->get('group_id', '');
        $group = $groupId ? $groupModel->getByIdWithDetails($groupId) : null;
        $modules = [];
        $staff = [];
        if ($group) {
            $modules = $timetableModel->getModulesByCourseId($group['course_id'] ?? '');
            $staffModel = $this->model('StaffModel');
            $staff = $staffModel->getStaffWithDepartment(1, 1000, '', $group['department_id'] ?? '');
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $day = trim($this->post('day', ''));
            $timeSlot = trim($this->post('time_slot', ''));
            $subject = trim($this->post('subject', ''));
            $sessionType = trim($this->post('session_type', 'Theory'));
            $moduleId = trim($this->post('module_id', ''));
            $staffId = trim($this->post('staff_id', ''));
            $room = trim($this->post('room', ''));
            if ($sessionType !== 'Practical') $sessionType = 'Theory';
            $dayNorm = $timetableModel::normalizeDay($day);
            $timeSlotNorm = $timetableModel::normalizeTimeSlot($timeSlot);
            $entryGroupId = $entry['group_id'] ?? '';
            if ($entryGroupId !== '' && $dayNorm !== '' && $timeSlotNorm !== '' && $timetableModel->existsSlot($entryGroupId, $dayNorm, $timeSlotNorm, $id)) {
                $_SESSION['error'] = 'This group already has an entry for the same day and time slot. Please choose a different day or time.';
                $this->redirect('group-timetable/edit?id=' . urlencode($id));
                return;
            }
            $sqlErr = '';
            $ok = $timetableModel->updateTimetable($id, [
                'day' => $dayNorm ?: null,
                'time_slot' => $timeSlotNorm ?: null,
                'subject' => $subject ?: null,
                'session_type' => $sessionType,
                'module_id' => $moduleId ?: null,
                'staff_id' => $staffId ?: null,
                'room' => $room ?: null
            ], $sqlErr);
            if ($ok) {
                $_SESSION['message'] = 'Timetable entry updated successfully.';
                $this->redirect('group-timetable/index?group_id=' . urlencode($entry['group_id'] ?? ''));
            } else {
                $_SESSION['error'] = 'Failed to update timetable entry.' . ($sqlErr !== '' ? ' SQL: ' . $sqlErr : '');
                $this->redirect('group-timetable/edit?id=' . urlencode($id));
            }
            return;
        }
        $data = [
            'title' => 'Edit Timetable Entry',
            'page' => 'group-timetable',
            'group_id' => $groupId,
            'group' => $group,
            'entry' => $entry,
            'days' => $timetableModel::getDays(),
            'sessionTypes' => $timetableModel::getSessionTypes(),
            'timeSlots' => $timetableModel::getTimeSlots(),
            'modules' => $modules,
            'staff' => $staff,
            'error' => $_SESSION['error'] ?? null
        ];
        unset($_SESSION['error']);
        return $this->view('group-timetable/edit', $data);
    }

    /**
     * Delete: GET confirm or POST delete
     */
    public function delete() {
        if (!$this->checkTimetableAccess()) return;
        $id = $this->get('id', '');
        if ($id === '') {
            $_SESSION['error'] = 'Entry ID required.';
            $this->redirect('group-timetable/index');
            return;
        }
        $timetableModel = $this->model('GroupTimetableModel');
        $entry = $timetableModel->getById($id);
        if (!$entry) {
            $_SESSION['error'] = 'Timetable entry not found.';
            $this->redirect('group-timetable/index');
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($timetableModel->deleteTimetable($id)) {
                $_SESSION['message'] = 'Timetable entry deleted successfully.';
            } else {
                $_SESSION['error'] = 'Failed to delete timetable entry.';
            }
            $this->redirect('group-timetable/index?group_id=' . urlencode($entry['group_id'] ?? ''));
            return;
        }
        $data = [
            'title' => 'Delete Timetable Entry',
            'page' => 'group-timetable',
            'entry' => $entry
        ];
        return $this->view('group-timetable/delete', $data);
    }
}
