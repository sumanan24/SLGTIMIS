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
        
        if (!$this->requireModule('staff', 'view')) {
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
        
        require_once BASE_PATH . '/core/AccessControl.php';
        $uid = (int) $_SESSION['user_id'];
        $canManageStaff = AccessControl::can($uid, 'staff', 'add')
            || AccessControl::can($uid, 'staff', 'edit')
            || AccessControl::can($uid, 'staff', 'delete');
        $canOpenPersonalFile = AccessControl::can($uid, 'personal_files', 'view');
        
        $data = [
            'title' => 'Staff',
            'page' => 'staff',
            'staff' => $staff,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'search' => $search,
            'canManageStaff' => $canManageStaff,
            'canOpenPersonalFile' => $canOpenPersonalFile,
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
        
        if (!$this->requireModule('staff', 'add')) {
            return;
        }
        
        $departmentModel = $this->model('DepartmentModel');
        $departments = $departmentModel->getAll();
        
        $roleModel = $this->model('StaffRoleModel');
        $roles = $roleModel->getAll();
        $this->model('StaffRoleSalaryModel');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $staffModel = $this->model('StaffModel');
            
            // Read only the fields user should fill
            $staffId       = trim($this->post('staff_id', ''));
            $departmentId  = trim($this->post('department_id', ''));
            $staffName     = trim($this->post('staff_name', ''));
            $staffIniname  = trim($this->post('staff_ininame', ''));
            $staffNic      = trim($this->post('staff_nic', ''));
            $staffEmail    = trim($this->post('staff_email', ''));
            $staffPno      = trim($this->post('staff_pno', ''));
            $staffGender   = $this->post('staff_gender', '');
            $staffEpf      = trim($this->post('staff_epf', ''));
            $staffPosition = trim($this->post('staff_position', ''));
            $staffType     = $this->post('staff_type', '');
            $staffStatus   = $this->post('staff_status', 'Working');
            $joinDate      = $this->post('staff_date_of_join', '');
            $fingerMachineNo = $this->normalizeFingerMachineNo($this->post('finger_machine_no', ''));
            $appointment = StaffModel::normalizeAppointment(
                $staffType,
                $staffPosition,
                'none',
                '',
                ''
            );

            if (empty($staffId) || empty($departmentId) || empty($staffName) ||
                empty($staffNic) || empty($staffPosition) || empty($staffType) || empty($staffStatus)) {
                $_SESSION['error'] = 'Username, Department, Full Name, NIC, Position, Staff Type, and Status are required.';
                $this->redirect('staff/create');
                return;
            }

            if (!$appointment['ok']) {
                $_SESSION['error'] = $appointment['error'];
                $this->redirect('staff/create');
                return;
            }
            
            if ($staffModel->exists($staffId)) {
                $_SESSION['error'] = 'Username already exists.';
                $this->redirect('staff/create');
                return;
            }

            if ($fingerMachineNo !== null && $staffModel->fingerMachineExists($fingerMachineNo)) {
                $_SESSION['error'] = 'Finger machine number already assigned to another staff member.';
                $this->redirect('staff/create');
                return;
            }
            
            // Check if department exists
            if (!$departmentModel->exists($departmentId)) {
                $_SESSION['error'] = 'Selected department does not exist.';
                $this->redirect('staff/create');
                return;
            }
            
            // Build full data record, auto-filling DB-required fields that are not on the form
            $today = date('Y-m-d');
            $autoEmail = $staffEmail !== '' ? $staffEmail : (strtolower($staffId) . '@slgti.local');
            $autoEpf   = $staffEpf !== '' ? $staffEpf : ('AUTO-' . $staffId);

            $data = [
                'staff_id'              => $staffId,
                'finger_machine_no'     => $fingerMachineNo,
                'department_id'         => $departmentId,
                'staff_name'            => $staffName,
                'staff_ininame'         => $staffIniname !== '' ? $staffIniname : null,
                'staff_address'         => '-',
                'staff_dob'             => $today,
                'staff_nic'             => $staffNic,
                'staff_email'           => $autoEmail,
                'staff_pno'             => $staffPno !== '' ? $staffPno : '0',
                'staff_date_of_join'    => $joinDate !== '' ? $joinDate : $today,
                'staff_gender'          => $staffGender !== '' ? $staffGender : 'Male',
                'staff_epf'             => $autoEpf,
                'staff_position'        => $staffPosition,
                'staff_type'            => $staffType,
                'appoint_type'          => $appointment['appoint_type'],
                'appoint_position'      => $appointment['appoint_position'],
                'appoint_department_id' => $appointment['appoint_department_id'],
                'staff_status'          => $staffStatus,
                'salary_grade'          => $this->normalizeSalaryGrade($this->post('salary_grade', ''))
            ];
            
            // Create staff
            $sqlError = null;
            $result = $staffModel->createStaff($data, $sqlError);
            
            // For manual primary keys, treat only FALSE as failure
            if ($result !== false) {
                // Build department label for message
                $dept = $departmentModel->getById($data['department_id']);
                $deptLabel = $dept ? "{$dept['department_name']} ({$data['department_id']})" : $data['department_id'];
                // Log activity
                $this->logActivity(
                    'CREATE',
                    'staff',
                    $data['staff_id'],
                    "Staff created: {$data['staff_name']} ({$data['staff_id']})",
                    null,
                    $data
                );
                
                $_SESSION['message'] = 'Staff created successfully. Username: ' . $data['staff_id'] . ', Department: ' . $deptLabel . '.';
                $this->redirect('staff');
            } else {
                if ($sqlError) {
                    error_log("StaffController::create - SQL error while creating staff {$data['staff_id']}: " . $sqlError);
                    $_SESSION['error'] = 'Failed to create staff. Database error: ' . $sqlError;
                } else {
                    $_SESSION['error'] = 'Failed to create staff.';
                }
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
        
        if (!$this->requireModule('personal_files', $_SERVER['REQUEST_METHOD'] === 'POST' ? 'edit' : 'view')) {
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
        $salaryModel = $this->model('StaffRoleSalaryModel');
        $fileModel = $this->model('StaffPersonalFileModel');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $section = strtolower(trim((string) $this->post('update_section', 'personal')));
            $savedId = $this->savePersonalFileSection($id, $section, $staff, $staffModel, $departmentModel);
            $redirectId = is_string($savedId) && $savedId !== '' ? $savedId : $id;
            $this->redirect('staff/edit?id=' . urlencode($redirectId) . '&tab=' . urlencode($section));
            return;
        }

        require_once BASE_PATH . '/core/AccessControl.php';
        require_once BASE_PATH . '/models/UserModel.php';
        $uid = (int) $_SESSION['user_id'];
        $targetUserId = AccessControl::userIdForStaff($id);
        $permissionTargetIsAdmin = $targetUserId > 0 && (new UserModel())->isAdmin($targetUserId);
        $tabs = ['personal', 'contact', 'employment', 'qualifications', 'training', 'service', 'attendance', 'documents', 'payroll', 'family', 'administrative', 'access'];
        $activeTab = strtolower(trim((string) $this->get('tab', 'personal')));
        if (!in_array($activeTab, $tabs, true)) {
            $activeTab = 'personal';
        }

        $attendanceMonth = trim((string) $this->get('month', ''));
        if (!preg_match('/^\d{4}-\d{2}$/', $attendanceMonth)) {
            $attendanceMonth = date('Y-m');
        }
        $fingerNo = trim((string) ($staff['finger_machine_no'] ?? ''));
        $leaveRecords = $fileModel->listKind('leave', $id);

        $data = [
            'title' => 'Staff Personal File',
            'page' => 'staff',
            'staff' => $staff,
            'departments' => $departments,
            'roles' => $roles,
            'activeTab' => $activeTab,
            'fileLabels' => StaffPersonalFileModel::labels(),
            'qualifications' => $fileModel->listKind('qualification', $id),
            'trainings' => $fileModel->listKind('training', $id),
            'serviceHistory' => $fileModel->listKind('service', $id),
            'leaveRecords' => $leaveRecords,
            'leaveTotals' => $fileModel->leaveTotals($id),
            'leaveMonthDays' => $fileModel->leaveDaysInMonth($id, $attendanceMonth),
            'leaveBalances' => $fileModel->leaveBalances($id, (int) substr($attendanceMonth, 0, 4), $attendanceMonth),
            'attendanceMonth' => $attendanceMonth,
            'deviceAttendance' => $fileModel->deviceMonthAttendance($fingerNo, $attendanceMonth, $leaveRecords),
            'documents' => $fileModel->listDocuments($id),
            'familyMembers' => $fileModel->listKind('family', $id),
            'adminRecords' => $fileModel->listKind('admin', $id),
            'salaryHistory' => $salaryModel->getStaffHistory($id),
            'salaryCalc' => $salaryModel->calculateStaffSalary($staff),
            'canEditFile' => AccessControl::can($uid, 'personal_files', 'edit'),
            'canAddFile' => AccessControl::can($uid, 'personal_files', 'add'),
            'canDeleteFile' => AccessControl::can($uid, 'personal_files', 'delete'),
            'canUploadFile' => AccessControl::can($uid, 'personal_files', 'upload'),
            'canDownloadFile' => AccessControl::can($uid, 'personal_files', 'download'),
            'canApproveFile' => AccessControl::can($uid, 'personal_files', 'approve'),
            'canEditStaffAccess' => AccessControl::can($uid, 'staff', 'edit'),
            'permissionMatrix' => AccessControl::can($uid, 'staff', 'edit') ? AccessControl::matrixForStaff($id) : [],
            'permissionTargetIsAdmin' => $permissionTargetIsAdmin,
            'error' => $_SESSION['error'] ?? null,
            'message' => $_SESSION['message'] ?? null
        ];
        unset($_SESSION['error'], $_SESSION['message']);
        return $this->view('staff/edit', $data);
    }

    public function savePermissions() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('staff');
            return;
        }
        if (!$this->requireModule('staff', 'edit')) {
            return;
        }
        $id = trim((string) $this->get('id', $this->post('staff_id', '')));
        if ($id === '') {
            $_SESSION['error'] = 'Staff member is required.';
            $this->redirect('staff');
            return;
        }
        $staffModel = $this->model('StaffModel');
        if (!$staffModel->getById($id)) {
            $_SESSION['error'] = 'Staff not found.';
            $this->redirect('staff');
            return;
        }

        require_once BASE_PATH . '/core/AccessControl.php';
        $permModel = $this->model('StaffModulePermissionModel');
        $action = strtolower(trim((string) $this->post('perm_action', 'save')));

        if ($action === 'reset') {
            $permModel->resetStaff($id);
            $_SESSION['message'] = 'Permissions reset to role defaults for this staff member.';
            $this->redirect('staff/edit?id=' . urlencode($id) . '&tab=access');
            return;
        }

        $posted = $this->post('modules', []);
        if (!is_array($posted)) {
            $posted = [];
        }
        $modules = [];
        foreach (array_keys(AccessControl::catalog()) as $key) {
            $row = $posted[$key] ?? [];
            $modules[$key] = [
                'access_mode' => $row['access_mode'] ?? 'inherit',
                'can_view' => !empty($row['can_view']),
                'can_add' => !empty($row['can_add']),
                'can_edit' => !empty($row['can_edit']),
                'can_delete' => !empty($row['can_delete']),
                'can_upload' => !empty($row['can_upload']),
                'can_download' => !empty($row['can_download']),
                'can_approve' => !empty($row['can_approve']),
            ];
        }
        $permModel->saveStaff($id, $modules, $_SESSION['user_id'] ?? null);
        $this->logActivity(
            'UPDATE',
            'staff-permissions',
            $id,
            'Staff module permissions updated for ' . $id,
            null,
            $modules
        );
        $_SESSION['message'] = 'Module permissions saved for this staff member.';
        $this->redirect('staff/edit?id=' . urlencode($id) . '&tab=access');
    }

    public function saveFileRecord() {
        if (!$this->requireModule('personal_files', 'add')) {
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('staff');
            return;
        }
        $id = trim((string) $this->get('id', $this->post('staff_id', '')));
        require_once BASE_PATH . '/models/StaffPersonalFileModel.php';
        $kind = preg_replace('/[^a-z_]/', '', strtolower((string) $this->post('kind', '')));
        $tab = StaffPersonalFileModel::kinds()[$kind]['tab'] ?? 'personal';
        $fileModel = $this->model('StaffPersonalFileModel');
        [$ok, $error] = $fileModel->saveKind($kind, $id, $_POST, $_SESSION['user_id'] ?? null);
        $_SESSION[$ok ? 'message' : 'error'] = $ok ? 'Leave sheet submitted.' : ($error ?: 'Could not save the record.');
        if ($ok && $kind === 'leave') {
            $_SESSION['message'] = 'Leave sheet submitted for recommendation.';
        } elseif ($ok) {
            $_SESSION['message'] = 'Record saved.';
        }
        $extra = '';
        $month = trim((string) $this->post('return_month', ''));
        if ($tab === 'attendance' && preg_match('/^\d{4}-\d{2}$/', $month)) {
            $extra = '&month=' . urlencode($month);
        }
        $this->redirect('staff/edit?id=' . urlencode($id) . '&tab=' . urlencode($tab) . $extra);
    }

    public function deleteFileRecord() {
        if (!$this->requireModule('personal_files', 'delete')) {
            return;
        }
        $id = trim((string) $this->get('id', ''));
        require_once BASE_PATH . '/models/StaffPersonalFileModel.php';
        $kind = preg_replace('/[^a-z_]/', '', strtolower((string) $this->get('kind', $this->post('kind', ''))));
        $recordId = (int) $this->get('record', $this->post('record_id', 0));
        $tab = StaffPersonalFileModel::kinds()[$kind]['tab'] ?? 'personal';
        $ok = $this->model('StaffPersonalFileModel')->deleteKind($kind, $id, $recordId);
        $_SESSION[$ok ? 'message' : 'error'] = $ok ? 'Record deleted.' : 'Could not delete the record.';
        $this->redirect('staff/edit?id=' . urlencode($id) . '&tab=' . urlencode($tab));
    }

    public function uploadDocument() {
        if (!$this->requireModule('personal_files', 'upload')) {
            return;
        }
        $id = trim((string) $this->get('id', $this->post('staff_id', '')));
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('staff/edit?id=' . urlencode($id) . '&tab=documents');
            return;
        }
        $title = trim((string) $this->post('title', ''));
        $docType = preg_replace('/[^a-z_]/', '', strtolower((string) $this->post('doc_type', 'other')));
        $file = $_FILES['document'] ?? null;
        if ($title === '' || !$file || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = 'Document title and file are required.';
            $this->redirect('staff/edit?id=' . urlencode($id) . '&tab=documents');
            return;
        }
        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
        if (!in_array($ext, $allowed, true) || (int) $file['size'] > 8 * 1024 * 1024) {
            $_SESSION['error'] = 'Upload a PDF, Word, or image file up to 8 MB.';
            $this->redirect('staff/edit?id=' . urlencode($id) . '&tab=documents');
            return;
        }
        $fileModel = $this->model('StaffPersonalFileModel');
        $dir = $fileModel->storageDir($id);
        $storedName = 'doc_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $full = $dir . '/' . $storedName;
        if (!move_uploaded_file($file['tmp_name'], $full)) {
            $_SESSION['error'] = 'Failed to store the document.';
            $this->redirect('staff/edit?id=' . urlencode($id) . '&tab=documents');
            return;
        }
        $rel = 'uploads/staff/' . basename($dir) . '/' . $storedName;
        $ok = $fileModel->addDocument($id, [
            'doc_type' => $docType !== '' ? $docType : 'other',
            'title' => $title,
            'original_name' => $file['name'],
            'stored_path' => $rel,
            'mime_type' => $file['type'] ?? '',
            'file_size' => (int) $file['size'],
            'uploaded_by' => (int) ($_SESSION['user_id'] ?? 0),
        ]);
        $_SESSION[$ok ? 'message' : 'error'] = $ok ? 'Document uploaded.' : 'Failed to save document details.';
        $this->redirect('staff/edit?id=' . urlencode($id) . '&tab=documents');
    }

    public function downloadDocument() {
        if (!$this->requireModule('personal_files', 'download')) {
            return;
        }
        $id = trim((string) $this->get('id', ''));
        $docId = (int) $this->get('doc', 0);
        $doc = $this->model('StaffPersonalFileModel')->getDocument($id, $docId);
        if (!$doc) {
            $_SESSION['error'] = 'Document not found.';
            $this->redirect('staff/edit?id=' . urlencode($id) . '&tab=documents');
            return;
        }
        $full = BASE_PATH . '/' . ltrim((string) $doc['stored_path'], '/');
        if (!is_file($full)) {
            $_SESSION['error'] = 'The file is missing on the server.';
            $this->redirect('staff/edit?id=' . urlencode($id) . '&tab=documents');
            return;
        }
        $name = $doc['original_name'] ?: basename($full);
        header('Content-Type: ' . ($doc['mime_type'] ?: 'application/octet-stream'));
        header('Content-Disposition: inline; filename="' . str_replace('"', '', $name) . '"');
        header('Content-Length: ' . filesize($full));
        readfile($full);
        exit();
    }

    public function deleteDocument() {
        if (!$this->requireModule('personal_files', 'delete')) {
            return;
        }
        $id = trim((string) $this->get('id', ''));
        $docId = (int) $this->get('doc', $this->post('doc', 0));
        $ok = $this->model('StaffPersonalFileModel')->deleteDocument($id, $docId);
        $_SESSION[$ok ? 'message' : 'error'] = $ok ? 'Document deleted.' : 'Could not delete the document.';
        $this->redirect('staff/edit?id=' . urlencode($id) . '&tab=documents');
    }

    public function approveDocument() {
        if (!$this->requireModule('personal_files', 'approve')) {
            return;
        }
        $id = trim((string) $this->get('id', ''));
        $docId = (int) $this->get('doc', $this->post('doc', 0));
        $ok = $this->model('StaffPersonalFileModel')->approveDocument($id, $docId, (int) ($_SESSION['user_id'] ?? 0));
        $_SESSION[$ok ? 'message' : 'error'] = $ok ? 'Document approved.' : 'Could not approve the document.';
        $this->redirect('staff/edit?id=' . urlencode($id) . '&tab=documents');
    }

    public function uploadPhoto() {
        $id = trim((string) $this->get('id', $this->post('staff_id', '')));
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if (!$this->requireModule('personal_files', 'view')) {
                return;
            }
            $staff = $this->model('StaffModel')->getById($id);
            $rel = (string) ($staff['staff_photo_path'] ?? '');
            $full = $rel !== '' ? BASE_PATH . '/' . ltrim($rel, '/') : '';
            if ($rel === '' || !is_file($full)) {
                http_response_code(404);
                exit();
            }
            $mime = mime_content_type($full) ?: 'image/jpeg';
            header('Content-Type: ' . $mime);
            header('Content-Length: ' . filesize($full));
            readfile($full);
            exit();
        }
        if (!$this->requireModule('personal_files', 'upload')) {
            return;
        }
        $file = $_FILES['photo'] ?? null;
        if (!$file || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = 'Select a profile photo to upload.';
            $this->redirect('staff/edit?id=' . urlencode($id) . '&tab=personal');
            return;
        }
        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'], true) || (int) $file['size'] > 4 * 1024 * 1024) {
            $_SESSION['error'] = 'Upload a JPG or PNG photo up to 4 MB.';
            $this->redirect('staff/edit?id=' . urlencode($id) . '&tab=personal');
            return;
        }
        $fileModel = $this->model('StaffPersonalFileModel');
        $dir = $fileModel->storageDir($id);
        $storedName = 'photo_' . time() . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
        $full = $dir . '/' . $storedName;
        if (!move_uploaded_file($file['tmp_name'], $full)) {
            $_SESSION['error'] = 'Failed to store the photo.';
            $this->redirect('staff/edit?id=' . urlencode($id) . '&tab=personal');
            return;
        }
        $rel = 'uploads/staff/' . basename($dir) . '/' . $storedName;
        $this->model('StaffModel')->updateStaff($id, ['staff_photo_path' => $rel]);
        $_SESSION['message'] = 'Profile photo updated.';
        $this->redirect('staff/edit?id=' . urlencode($id) . '&tab=personal');
    }
    
    public function delete() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        if (!$this->requireModule('staff', 'delete')) {
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

    private function normalizeSalaryGrade($value) {
        $this->model('StaffRoleSalaryModel');
        if ($value === '' || $value === null) {
            return null;
        }
        $grade = (int) $value;
        return in_array($grade, [1, 2, 3], true) ? $grade : null;
    }

    private function normalizeFingerMachineNo($value) {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        return substr($value, 0, 50);
    }

    private function emptyToNull($value) {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    private function savePersonalFileSection($id, $section, array $staff, $staffModel, $departmentModel) {
        $data = [];
        if ($section === 'personal') {
            $newStaffId = trim((string) $this->post('staff_id', $id));
            $data = [
                'staff_name' => trim($this->post('staff_name', '')),
                'staff_ininame' => $this->emptyToNull($this->post('staff_ininame', '')),
                'staff_dob' => $this->emptyToNull($this->post('staff_dob', '')),
                'staff_nic' => trim($this->post('staff_nic', '')),
                'staff_gender' => $this->emptyToNull($this->post('staff_gender', '')),
                'staff_civil_status' => $this->emptyToNull($this->post('staff_civil_status', '')),
                'staff_nationality' => $this->emptyToNull($this->post('staff_nationality', '')),
                'finger_machine_no' => $this->normalizeFingerMachineNo($this->post('finger_machine_no', '')),
            ];
            if ($newStaffId === '' || $data['staff_name'] === '' || $data['staff_nic'] === '') {
                $_SESSION['error'] = 'Staff ID, full name, and NIC are required.';
                return false;
            }
            if (strlen($newStaffId) > 64) {
                $_SESSION['error'] = 'Staff ID must be 64 characters or fewer.';
                return false;
            }
            if ($data['finger_machine_no'] !== null && $staffModel->fingerMachineExists($data['finger_machine_no'], $id)) {
                $_SESSION['error'] = 'Finger machine number already assigned to another staff member.';
                return false;
            }
            if ($newStaffId !== $id) {
                if (!$staffModel->renameStaffId($id, $newStaffId)) {
                    $_SESSION['error'] = $staffModel->getLastError() ?: 'Failed to change Staff ID.';
                    return false;
                }
                if (isset($_SESSION['user_name']) && (string) $_SESSION['user_name'] === (string) $id) {
                    $_SESSION['user_name'] = $newStaffId;
                }
                $id = $newStaffId;
            }
        } elseif ($section === 'contact') {
            $data = [
                'staff_address' => $this->emptyToNull($this->post('staff_address', '')),
                'staff_current_address' => $this->emptyToNull($this->post('staff_current_address', '')),
                'staff_pno' => $this->emptyToNull($this->post('staff_pno', '')),
                'staff_email' => trim($this->post('staff_email', '')),
                'staff_emergency_name' => $this->emptyToNull($this->post('staff_emergency_name', '')),
                'staff_emergency_phone' => $this->emptyToNull($this->post('staff_emergency_phone', '')),
                'staff_emergency_relation' => $this->emptyToNull($this->post('staff_emergency_relation', '')),
            ];
            if ($data['staff_email'] === '') {
                $_SESSION['error'] = 'Email is required.';
                return false;
            }
        } elseif ($section === 'employment') {
            $appointment = StaffModel::normalizeAppointment(
                $this->post('staff_type', ''),
                $this->post('staff_position', ''),
                $this->post('appoint_type', 'none'),
                $this->post('appoint_position', ''),
                $this->post('appoint_department_id', '')
            );
            if (!$appointment['ok']) {
                $_SESSION['error'] = $appointment['error'];
                return false;
            }
            $data = [
                'staff_date_of_join' => $this->emptyToNull($this->post('staff_date_of_join', '')),
                'finger_machine_no' => $this->normalizeFingerMachineNo($this->post('finger_machine_no', '')),
                'staff_designation' => $this->emptyToNull($this->post('staff_designation', '')),
                'staff_position' => trim($this->post('staff_position', '')),
                'salary_grade' => $this->normalizeSalaryGrade($this->post('salary_grade', '')),
                'department_id' => trim($this->post('department_id', '')),
                'staff_type' => trim($this->post('staff_type', '')),
                'staff_confirmation_date' => $this->emptyToNull($this->post('staff_confirmation_date', '')),
                'staff_status' => $this->post('staff_status', 'Working'),
                'staff_retirement_date' => $this->emptyToNull($this->post('staff_retirement_date', '')),
                'appoint_type' => $appointment['appoint_type'],
                'appoint_position' => $appointment['appoint_position'],
                'appoint_department_id' => $appointment['appoint_department_id'],
            ];
            if ($data['department_id'] === '' || $data['staff_position'] === '') {
                $_SESSION['error'] = 'Department and role are required.';
                return false;
            }
            if (!$departmentModel->exists($data['department_id'])) {
                $_SESSION['error'] = 'Selected department does not exist.';
                return false;
            }
            if ($data['finger_machine_no'] !== null && $staffModel->fingerMachineExists($data['finger_machine_no'], $id)) {
                $_SESSION['error'] = 'Finger machine number already assigned to another staff member.';
                return false;
            }
        } elseif ($section === 'payroll') {
            $data = [
                'staff_bank_name' => $this->emptyToNull($this->post('staff_bank_name', '')),
                'staff_bank_branch' => $this->emptyToNull($this->post('staff_bank_branch', '')),
                'staff_bank_account' => $this->emptyToNull($this->post('staff_bank_account', '')),
                'staff_epf' => trim($this->post('staff_epf', '')),
                'staff_etf' => $this->emptyToNull($this->post('staff_etf', '')),
            ];
            if ($data['staff_epf'] === '') {
                $_SESSION['error'] = 'EPF number is required.';
                return false;
            }
        } else {
            $_SESSION['error'] = 'Unknown section.';
            return false;
        }

        $oldValues = array_intersect_key($staff, $data);
        $result = $staffModel->updateStaff($id, $data);
        if (!$result) {
            $_SESSION['error'] = 'Failed to update the personal file.';
            return false;
        }
        $this->logActivity('UPDATE', 'staff-file', $id, 'Staff personal file updated (' . $section . ')', $oldValues, $data);
        $_SESSION['message'] = 'Personal file updated.';
        return $id;
    }
}

