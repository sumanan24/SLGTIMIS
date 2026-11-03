<?php
/**
 * Device / Laptop Asset Management
 */

require_once BASE_PATH . '/models/DeviceModel.php';
require_once BASE_PATH . '/helpers/DeviceAssetHelper.php';

class DeviceController extends Controller {

    private function requireLogin(): int {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
        }
        return (int) $_SESSION['user_id'];
    }

    private function userModel(): UserModel {
        require_once BASE_PATH . '/models/UserModel.php';
        return new UserModel();
    }

    private function deviceModel(): DeviceModel {
        return $this->model('DeviceModel');
    }

    private function resolveDeviceFromRequest(DeviceModel $model): ?array {
        $serialNumber = trim((string) $this->get('serial', ''));
        if ($serialNumber !== '') {
            return $model->findDeviceBySerialNumber($serialNumber);
        }
        $assetId = trim((string) $this->get('asset_id', ''));
        if ($assetId !== '') {
            return $model->findDeviceByAssetId($assetId);
        }
        $id = (int) $this->get('id', 0);
        if ($id > 0) {
            return $model->findDevice($id);
        }

        return null;
    }

    private function requireView(int $uid): UserModel {
        $um = $this->userModel();
        if (!$um->canViewDevices($uid)) {
            $_SESSION['error'] = 'You do not have permission to view device assets.';
            $this->redirect('dashboard');
        }
        return $um;
    }

    private function requireManage(int $uid): UserModel {
        $um = $this->requireView($uid);
        if (!$um->canManageDevices($uid)) {
            $_SESSION['error'] = 'You do not have permission to manage device assets.';
            $this->redirect('devices');
        }
        return $um;
    }

    /**
     * @return array<string, mixed>
     */
    private function listFilters(): array {
        return [
            'search' => trim((string) $this->get('q', '')),
            'serial' => trim((string) $this->get('serial', '')),
            'device_type' => trim((string) $this->get('type', '')),
            'status' => trim((string) $this->get('status', '')),
            'department_id' => trim((string) $this->get('dept', '')),
            'condition' => trim((string) $this->get('condition', '')),
            'report' => trim((string) $this->get('report', '')),
            'assigned' => trim((string) $this->get('assigned', '')),
            'warranty' => trim((string) $this->get('warranty', '')),
            'sort' => trim((string) $this->get('sort', 'asset_id')),
            'dir' => trim((string) $this->get('dir', 'ASC')),
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    private function scopedFilters(array $filters, UserModel $um, int $uid): array {
        $scope = $this->assetScope($um, $uid);
        if (($scope['mode'] ?? '') === 'department') {
            $filters['department_id'] = (string) ($scope['department_id'] ?? '');
        } elseif (($scope['mode'] ?? '') === 'staff') {
            $filters['staff_id'] = (string) ($scope['staff_id'] ?? '');
            $filters['department_id'] = '';
        } elseif (($scope['mode'] ?? '') === 'student') {
            $filters['student_id'] = (string) ($scope['student_id'] ?? '');
            $filters['department_id'] = '';
        }

        return $filters;
    }

    /**
     * @param array<string, mixed> $device
     */
    private function denyIfHidden(array $device, UserModel $um, int $uid): void {
        if ($this->canSeeDevice($device, $this->assetScope($um, $uid))) {
            return;
        }
        $_SESSION['error'] = 'You can only open assets you are allowed to see.';
        $this->redirect('devices/list');
    }

    private function parseLabelSets(?int $raw = null): int
    {
        $sets = $raw ?? (int) $this->get('sets', DeviceAssetHelper::defaultLabelSets());

        return DeviceAssetHelper::clampLabelSets($sets);
    }

    /**
     * @return array<string, mixed>
     */
    private function baseViewData(int $uid, UserModel $um): array {
        $scope = $this->assetScope($um, $uid);

        return [
            'canManage' => $um->canManageDevices($uid),
            'canApprove' => $um->canApproveDeviceRequests($uid),
            'canExport' => $um->canExportDevices($uid),
            'canPrintQr' => $um->canPrintDeviceQr($uid),
            'canViewHistory' => $um->canViewDeviceHistory($uid),
            'canScan' => $um->canScanDeviceQr($uid),
            'isReadOnlyRole' => !$um->canManageDevices($uid),
            'assetScope' => $scope,
            'lockedDepartmentId' => ($scope['mode'] ?? '') === 'department' ? (string) ($scope['department_id'] ?? '') : '',
        ];
    }

    /**
     * @return array{mode: string, department_id?: string, staff_id?: string, student_id?: string}
     */
    private function assetScope(UserModel $um, int $uid): array {
        $role = (string) $um->getUserRole($uid);
        $user = $um->find($uid);
        $table = (string) ($user['user_table'] ?? '');
        if ($table === 'student') {
            return ['mode' => 'student', 'student_id' => (string) ($user['user_name'] ?? '')];
        }
        if ($um->isAdminOrADM($uid) || in_array($role, ['DIR', 'DPI', 'DPA', 'ACC'], true)) {
            return ['mode' => 'all'];
        }
        if ($um->isHOD($uid)) {
            return ['mode' => 'department', 'department_id' => (string) $um->getHODDepartment($uid)];
        }

        return ['mode' => 'staff', 'staff_id' => (string) ($user['user_name'] ?? '')];
    }

    /**
     * @param array<string, mixed> $device
     * @param array<string, mixed> $scope
     */
    private function canSeeDevice(array $device, array $scope): bool {
        $mode = (string) ($scope['mode'] ?? 'staff');
        if ($mode === 'all') {
            return true;
        }
        if ($mode === 'department') {
            $dept = (string) ($scope['department_id'] ?? '');

            return $dept !== '' && (
                (string) ($device['department_id'] ?? '') === $dept
                || (string) ($device['staff_department_id'] ?? '') === $dept
            );
        }
        if ($mode === 'student') {
            $id = (string) ($scope['student_id'] ?? '');

            return $id !== '' && (string) ($device['assigned_student_id'] ?? '') === $id;
        }
        $id = (string) ($scope['staff_id'] ?? '');

        return $id !== '' && (string) ($device['assigned_employee_id'] ?? '') === $id;
    }

    public function dashboard() {
        $uid = $this->requireLogin();
        $um = $this->requireView($uid);
        $model = $this->deviceModel();
        $scope = $this->assetScope($um, $uid);

        return $this->view('devices/dashboard', array_merge($this->baseViewData($uid, $um), [
            'page' => 'devices',
            'deviceSection' => 'dashboard',
            'stats' => $model->simpleStats($scope),
            'activity' => $model->getAuditLogs(null, 8),
        ]));
    }

    public function index() {
        $uid = $this->requireLogin();
        $um = $this->requireView($uid);
        $model = $this->deviceModel();
        $serialFilter = trim((string) $this->get('serial', ''));
        if ($serialFilter !== '') {
            $device = $model->findDeviceBySerialNumber($serialFilter);
            if ($device) {
                $this->redirect('devices/view?id=' . (int) ($device['id'] ?? 0));
            }
        }
        $filters = $this->scopedFilters($this->listFilters(), $um, $uid);
        $page = max(1, (int) $this->get('page', 1));
        $result = $model->listDevices($filters, $page, 25);
        require_once BASE_PATH . '/models/DepartmentModel.php';
        $departments = (new DepartmentModel())->getAll();

        return $this->view('devices/index', array_merge($this->baseViewData($uid, $um), [
            'page' => 'devices',
            'deviceSection' => 'devices',
            'devices' => $result['rows'],
            'total' => $result['total'],
            'currentPage' => $page,
            'perPage' => 25,
            'filters' => $filters,
            'departments' => $departments,
            'statuses' => DeviceModel::STATUSES,
            'deviceTypes' => DeviceModel::DEVICE_TYPES,
        ]));
    }

    public function create() {
        $uid = $this->requireLogin();
        $this->requireManage($uid);

        return $this->view('devices/form', $this->formViewData(null));
    }

    public function store() {
        $uid = $this->requireLogin();
        $this->requireManage($uid);
        $model = $this->deviceModel();
        $data = $this->validatedDevicePost();
        if ($data === null) {
            $this->redirect('devices/create');
        }
        if ($model->assetIdExists($data['asset_id'])) {
            $_SESSION['error'] = 'Asset ID already exists.';
            $this->redirect('devices/create');
        }
        if (trim((string) ($data['asset_id'] ?? '')) === '') {
            $data['asset_id'] = $model->nextAssetCode();
        }
        $error = null;
        $id = $model->createDevice($data, $uid, $error);
        if ($id === null) {
            $_SESSION['error'] = $error ?: 'Could not create device.';
            $this->redirect('devices/create');
        }
        $accessories = $this->post('accessories', []);
        if (is_array($accessories)) {
            $model->syncAccessories($id, $accessories);
        }
        $photo = $this->saveAssetPhoto($id);
        if ($photo !== null) {
            $model->updateDevice($id, ['photo_path' => $photo], $uid);
        }
        $model->logAudit($id, $uid, 'device_created', null, $data);
        $this->logActivity('CREATE', 'devices', (string) $id, 'Asset registered: ' . $data['asset_id']);
        $_SESSION['success'] = 'Asset saved. Asset code ' . $data['asset_id'] . ' and its QR code are ready.';
        $this->redirect('devices/view?id=' . $id);
    }

    public function importTemplate() {
        $uid = $this->requireLogin();
        $this->requireManage($uid);
        $autoload = BASE_PATH . '/vendor/autoload.php';
        if (!is_file($autoload)) {
            $_SESSION['error'] = 'Excel is not available. Ask the office to install the spreadsheet library.';
            $this->redirect('devices/create');
        }
        require_once $autoload;

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Assets');
        $headers = [
            'Asset Name', 'Category', 'Brand', 'Model', 'Serial Number', 'Purchase Date',
            'Purchase Price', 'Room / Location', 'Quantity', 'Asset Condition', 'Remarks',
        ];
        foreach ($headers as $i => $header) {
            $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue($column . '1', $header);
        }
        $sheet->getStyle('A1:K1')->getFont()->setBold(true);
        $example = ['Office laptop (example)', 'Laptop', 'Dell', 'Latitude', '', date('Y-m-d'), '150000', 'Lab 2', 1, 'Good', 'Delete this example row before you upload'];
        foreach ($example as $i => $value) {
            $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue($column . '2', $value);
        }
        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $choices = $spreadsheet->createSheet();
        $choices->setTitle('Choices');
        $choices->setCellValue('A1', 'Category');
        $choices->setCellValue('B1', 'Condition');
        $row = 2;
        foreach (DeviceModel::ASSET_CATEGORIES as $cat) {
            $choices->setCellValue('A' . $row, $cat);
            $row++;
        }
        $row = 2;
        foreach (DeviceModel::SIMPLE_CONDITIONS as $cond) {
            $choices->setCellValue('B' . $row, $cond);
            $row++;
        }
        $catLast = 1 + count(DeviceModel::ASSET_CATEGORIES);
        $condLast = 1 + count(DeviceModel::SIMPLE_CONDITIONS);
        for ($r = 2; $r <= 200; $r++) {
            $this->excelList($sheet, 'B' . $r, 'Choices!$A$2:$A$' . $catLast);
            $this->excelList($sheet, 'J' . $r, 'Choices!$B$2:$B$' . $condLast);
        }
        $spreadsheet->setActiveSheetIndex(0);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="slgti-assets-template.xlsx"');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function importSave() {
        $uid = $this->requireLogin();
        $um = $this->requireManage($uid);
        $rows = $this->readAssetSpreadsheet();
        if ($rows === null) {
            $this->redirect('devices/create');
        }
        $scope = $this->assetScope($um, $uid);
        $departmentId = ($scope['mode'] ?? '') === 'department'
            ? (string) ($scope['department_id'] ?? '')
            : trim((string) $this->post('department_id', ''));
        if ($departmentId === '') {
            $_SESSION['error'] = 'Please choose a department before you upload the Excel file.';
            $this->redirect('devices/create');
        }
        $model = $this->deviceModel();
        $saved = 0;
        $errors = [];
        foreach ($rows as $row) {
            $line = (int) ($row['_row'] ?? 0);
            $name = trim((string) ($row['asset_name'] ?? ''));
            if ($name === '' || stripos($name, '(example)') !== false) {
                continue;
            }
            $built = $this->assetFromImportRow($row, $departmentId);
            if (!empty($built['error'])) {
                $errors[] = 'Row ' . $line . ': ' . $built['error'];
                continue;
            }
            $data = $built['data'];
            $data['asset_id'] = $model->nextAssetCode();
            $error = null;
            $id = $model->createDevice($data, $uid, $error);
            if ($id === null) {
                $errors[] = 'Row ' . $line . ': ' . ($error ?: 'Could not save this asset.');
                continue;
            }
            $model->logAudit($id, $uid, 'device_created', null, $data);
            $saved++;
        }
        if ($saved > 0) {
            $_SESSION['success'] = $saved . ' asset' . ($saved === 1 ? '' : 's') . ' saved. Asset codes and QR codes were created.';
        }
        if ($errors) {
            $_SESSION['import_errors'] = array_slice($errors, 0, 20);
            if (!$saved) {
                $_SESSION['error'] = 'No assets were saved. Check the rows below.';
            }
        } elseif ($saved === 0) {
            $_SESSION['error'] = 'The file has no assets. Type one asset on each row, then upload it again.';
        }
        $this->redirect('devices/create');
    }

    public function edit() {
        $uid = $this->requireLogin();
        $this->requireManage($uid);
        $id = (int) $this->get('id', 0);
        $device = $this->deviceModel()->findDevice($id);
        if (!$device) {
            $_SESSION['error'] = 'Asset not found.';
            $this->redirect('devices/list');
        }
        $this->denyIfHidden($device, $this->userModel(), $uid);

        return $this->view('devices/form', $this->formViewData($device));
    }

    public function update() {
        $uid = $this->requireLogin();
        $this->requireManage($uid);
        $id = (int) $this->get('id', 0);
        $model = $this->deviceModel();
        $old = $model->findDevice($id);
        if (!$old) {
            $_SESSION['error'] = 'Asset not found.';
            $this->redirect('devices/list');
        }
        $this->denyIfHidden($old, $this->userModel(), $uid);
        $data = $this->validatedDevicePost($id);
        if ($data === null) {
            $this->redirect('devices/edit?id=' . $id);
        }
        if ($model->assetIdExists($data['asset_id'], $id)) {
            $_SESSION['error'] = 'Asset ID already exists.';
            $this->redirect('devices/edit?id=' . $id);
        }
        $photo = $this->saveAssetPhoto($id);
        if ($photo !== null) {
            $data['photo_path'] = $photo;
        }
        $model->updateDevice($id, $data, $uid);
        $accessories = $this->post('accessories', []);
        if (is_array($accessories)) {
            $model->syncAccessories($id, $accessories);
        }
        if ($this->hasConditionPost()) {
            $model->recordCondition($id, $this->conditionFromPost(), $uid);
        }
        $model->logAudit($id, $uid, 'device_updated', $old, $data);
        $this->logActivity('UPDATE', 'devices', (string) $id, 'Device updated: ' . $data['asset_id']);
        $_SESSION['success'] = 'Asset updated.';
        $this->redirect('devices/view?id=' . $id);
    }

    public function delete() {
        $uid = $this->requireLogin();
        $this->requireManage($uid);
        $id = (int) $this->post('id', 0);
        $model = $this->deviceModel();
        $old = $model->findDevice($id);
        if (!$old) {
            $_SESSION['error'] = 'Device not found.';
            $this->redirect('devices');
        }
        if ($model->isDeviceAssigned($old)) {
            $_SESSION['error'] = 'Cannot delete an assigned device. Return the device first.';
            $this->redirect('devices/view?id=' . $id);
        }
        $assetLabel = (string) ($old['asset_id'] ?? $id);
        if (!$model->hardDeleteDevice($id)) {
            $_SESSION['error'] = 'Could not delete device. Please try again.';
            $this->redirect('devices/view?id=' . $id);
        }
        $this->logActivity('DELETE', 'devices', (string) $id, 'Device permanently deleted: ' . $assetLabel);
        $_SESSION['success'] = 'Device "' . $assetLabel . '" and all related records were permanently deleted from the database.';
        $this->redirect('devices/list');
    }

    public function show() {
        $uid = $this->requireLogin();
        $um = $this->requireView($uid);
        $model = $this->deviceModel();
        $device = $this->resolveDeviceFromRequest($model);
        if (!$device) {
            $_SESSION['error'] = 'Asset not found.';
            $this->redirect('devices/list');
        }
        $this->denyIfHidden($device, $um, $uid);
        $id = (int) ($device['id'] ?? 0);
        $qrUri = DeviceAssetHelper::qrPngDataUriForDevice($device, 260);
        $canDeleteDevice = $um->canManageDevices($uid) && !$model->isDeviceAssigned($device);

        return $this->view('devices/view', array_merge($this->baseViewData($uid, $um), [
            'page' => 'devices',
            'deviceSection' => 'devices',
            'device' => $device,
            'canDeleteDevice' => $canDeleteDevice,
            'accessories' => $model->getAccessories($id),
            'assignments' => $model->getAssignmentHistory($id),
            'activeAssignment' => $model->getActiveAssignment($id),
            'conditionHistory' => $model->getConditionHistory($id),
            'qrDataUri' => $qrUri,
            'qrUrl' => DeviceAssetHelper::qrContentUrl($device),
            'fullDetail' => $um->canManageDevices($uid),
            'labelPrinterConfig' => DeviceAssetHelper::labelConfig(),
            'defaultLabelSets' => DeviceAssetHelper::defaultLabelSets(),
            'maxLabelSets' => DeviceAssetHelper::maxLabelSets(),
            'labelsPerSet' => DeviceAssetHelper::labelsPerSet(),
        ]));
    }

    public function assign() {
        $uid = $this->requireLogin();
        $um = $this->requireManage($uid);
        $id = (int) $this->get('id', 0);
        $model = $this->deviceModel();
        $device = $id > 0 ? $model->findDevice($id) : null;
        if ($device) {
            $this->denyIfHidden($device, $um, $uid);
        }
        $scope = $this->assetScope($um, $uid);
        $dept = ($scope['mode'] ?? '') === 'department' ? (string) $scope['department_id'] : trim((string) $this->get('dept', ''));
        require_once BASE_PATH . '/models/DepartmentModel.php';
        require_once BASE_PATH . '/models/StaffModel.php';
        require_once BASE_PATH . '/models/CourseModel.php';
        require_once BASE_PATH . '/models/GroupModel.php';
        $staff = (new StaffModel())->getStaffWithDepartment(1, 500, '', $dept !== '' ? $dept : null);
        $courses = (new CourseModel())->getCoursesWithDepartment($dept !== '' ? ['department_id' => $dept] : []);
        $groups = (new GroupModel())->getAllWithDetails($dept !== '' ? $dept : null, null);

        return $this->view('devices/assign', array_merge($this->baseViewData($uid, $um), [
            'page' => 'devices',
            'deviceSection' => 'assign',
            'device' => $device,
            'devices' => $model->listDevices($this->scopedFilters(['status' => ''], $um, $uid), 1, 300)['rows'],
            'staffList' => $staff,
            'departments' => (new DepartmentModel())->getAll(),
            'courses' => $courses,
            'groups' => $groups,
            'history' => $device ? $model->getAssignmentHistory((int) $device['id']) : [],
            'activeAssignments' => $device ? $model->getActiveAssignments((int) $device['id']) : [],
            'selectedDepartment' => $dept,
            'slots' => $device ? $model->availableSlots($device) : 0,
        ]));
    }

    public function assignSave() {
        $uid = $this->requireLogin();
        $um = $this->requireManage($uid);
        $id = (int) $this->post('device_id', 0);
        $who = trim((string) $this->post('who', 'staff'));
        $personId = $who === 'student' ? trim((string) $this->post('student_id', '')) : trim((string) $this->post('employee_id', ''));
        $issueDate = trim((string) $this->post('issue_date', ''));
        $expected = trim((string) $this->post('expected_return_date', ''));
        $remarks = trim((string) $this->post('remarks', ''));
        $change = (int) $this->post('change_person', 0) === 1;
        $model = $this->deviceModel();
        $device = $model->findDevice($id);
        if (!$device) {
            $_SESSION['error'] = 'Please choose an asset.';
            $this->redirect('devices/assign');
        }
        $this->denyIfHidden($device, $um, $uid);
        if ($personId === '' || $issueDate === '') {
            $_SESSION['error'] = 'Choose a person and the assignment date.';
            $this->redirect('devices/assign?id=' . $id);
        }
        if ($who === 'staff') {
            require_once BASE_PATH . '/models/StaffModel.php';
            if (!(new StaffModel())->exists($personId)) {
                $_SESSION['error'] = 'That staff member was not found.';
                $this->redirect('devices/assign?id=' . $id);
            }
        } else {
            require_once BASE_PATH . '/models/StudentModel.php';
            $student = (new StudentModel())->find($personId);
            if (!$student) {
                $_SESSION['error'] = 'That student was not found.';
                $this->redirect('devices/assign?id=' . $id);
            }
        }
        if ($change && $model->availableSlots($device) < 1) {
            $active = $model->getActiveAssignment($id);
            if ($active) {
                $model->returnDevice($id, $issueDate, $uid, 'Changed to another person', DeviceModel::STATUS_AVAILABLE, (int) $active['id']);
                $device = $model->findDevice($id) ?: $device;
            }
        }
        if ($model->availableSlots($device) < 1) {
            $_SESSION['error'] = 'This asset is already with someone. Return it first, or raise the quantity if you have more than one.';
            $this->redirect('devices/assign?id=' . $id);
        }
        $saved = $model->assignToPerson($id, $who === 'student' ? 'student' : 'staff', $personId, $issueDate, $uid, $expected !== '' ? $expected : null, $remarks !== '' ? $remarks : null);
        if (!$saved) {
            $_SESSION['error'] = 'Could not assign this asset.';
            $this->redirect('devices/assign?id=' . $id);
        }
        $model->logAudit($id, $uid, 'device_assigned', $device, ['who' => $who, 'person_id' => $personId, 'issue_date' => $issueDate]);
        $_SESSION['success'] = 'Asset assigned.';
        $this->redirect('devices/view?id=' . $id);
    }

    public function lookupPeople() {
        $uid = $this->requireLogin();
        $um = $this->requireView($uid);
        $model = $this->deviceModel();
        $scope = $this->assetScope($um, $uid);
        $dept = ($scope['mode'] ?? '') === 'department' ? (string) $scope['department_id'] : trim((string) $this->get('department_id', ''));
        $who = trim((string) $this->get('who', 'staff'));
        if ($who === 'student') {
            $rows = $model->searchStudents(
                trim((string) $this->get('q', '')),
                $dept,
                trim((string) $this->get('course_id', '')),
                trim((string) $this->get('group_id', ''))
            );
            $this->json(['ok' => true, 'people' => $rows]);
            return;
        }
        require_once BASE_PATH . '/models/StaffModel.php';
        $staff = (new StaffModel())->getStaffWithDepartment(1, 30, trim((string) $this->get('q', '')), $dept !== '' ? $dept : null);
        $people = [];
        foreach ($staff as $row) {
            $people[] = [
                'staff_id' => $row['staff_id'] ?? '',
                'staff_name' => $row['staff_name'] ?? '',
                'department_name' => $row['department_name'] ?? '',
            ];
        }
        $this->json(['ok' => true, 'people' => $people]);
    }

    public function returnDevice() {
        $uid = $this->requireLogin();
        $this->requireManage($uid);
        $id = (int) $this->post('device_id', 0);
        $returnDate = trim((string) $this->post('return_date', date('Y-m-d')));
        $newStatus = trim((string) $this->post('new_status', DeviceModel::STATUS_AVAILABLE));
        $remarks = trim((string) $this->post('remarks', ''));
        $model = $this->deviceModel();
        $device = $model->findDevice($id);
        if (!$device) {
            $_SESSION['error'] = 'Device not found.';
            $this->redirect('devices');
        }
        $this->denyIfHidden($device, $this->userModel(), $uid);
        $old = $device;
        $assignmentId = (int) $this->post('assignment_id', 0);
        if (!$model->returnDevice($id, $returnDate, $uid, $remarks, $newStatus, $assignmentId)) {
            $_SESSION['error'] = 'Could not return this asset.';
            $this->redirect('devices/assign?id=' . $id);
        }
        $model->logAudit($id, $uid, 'device_returned', $old, ['return_date' => $returnDate, 'status' => $newStatus]);
        $_SESSION['success'] = 'Asset returned.';
        $this->redirect($assignmentId > 0 ? 'devices/assign?id=' . $id : 'devices/view?id=' . $id);
    }

    public function qrPrint() {
        $uid = $this->requireLogin();
        $um = $this->requireView($uid);
        if (!$um->canPrintDeviceQr($uid)) {
            $_SESSION['error'] = 'Not authorized.';
            $this->redirect('devices');
        }
        $id = (int) $this->get('id', 0);
        $device = $this->deviceModel()->findDevice($id);
        if (!$device) {
            $_SESSION['error'] = 'Device not found.';
            $this->redirect('devices');
        }
        $token = (string) $device['qr_token'];
        $sets = $this->parseLabelSets();
        return $this->view('devices/qr_print', [
            'use_label_print_layout' => true,
            'title' => 'Device QR — ' . ($device['asset_id'] ?? ''),
            'device' => $device,
            'qrDataUri' => DeviceAssetHelper::qrPngDataUriForDevice($device),
            'qrUrl' => DeviceAssetHelper::qrContentUrl($device),
            'labelCopies' => DeviceAssetHelper::labelsPerSet(),
            'labelSets' => $sets,
            'labelConfig' => DeviceAssetHelper::labelConfig(),
        ]);
    }

    public function qrPdf() {
        $uid = $this->requireLogin();
        $um = $this->requireView($uid);
        if (!$um->canPrintDeviceQr($uid)) {
            $_SESSION['error'] = 'Not authorized.';
            $this->redirect('devices');
        }
        $id = (int) $this->get('id', 0);
        $model = $this->deviceModel();
        $device = $model->findDevice($id);
        if (!$device) {
            $_SESSION['error'] = 'Device not found.';
            $this->redirect('devices');
        }
        require_once BASE_PATH . '/helpers/ExamPdfHelper.php';
        if (!ExamPdfHelper::dompdfAvailable()) {
            $_SESSION['error'] = 'PDF library not installed. Run composer install on the server.';
            $this->redirect('devices/view?id=' . $id);
        }
        $token = (string) $device['qr_token'];
        $sets = $this->parseLabelSets();
        $qrDataUri = DeviceAssetHelper::qrPngDataUriForDevice($device, 220);
        $html = DeviceAssetHelper::renderQrLabelPdfHtml($device, $qrDataUri, $sets);
        $model->logAudit($id, $uid, 'qr_pdf_download', null, [
            'asset_id' => $device['asset_id'] ?? '',
            'sets' => $sets,
            'labels_per_set' => DeviceAssetHelper::labelsPerSet(),
        ]);
        $filename = 'device-qr-' . preg_replace('/[^a-zA-Z0-9._-]+/', '_', (string) ($device['asset_id'] ?? $id)) . '-2up-x' . $sets . '.pdf';
        ExamPdfHelper::streamHtml($html, $filename, DeviceAssetHelper::labelPaper4x1Pdf(), 'landscape');
    }

    public function qrZpl() {
        $uid = $this->requireLogin();
        $um = $this->requireView($uid);
        if (!$um->canPrintDeviceQr($uid)) {
            $_SESSION['error'] = 'Not authorized.';
            $this->redirect('devices');
        }
        $id = (int) $this->get('id', 0);
        $model = $this->deviceModel();
        $device = $model->findDevice($id);
        if (!$device) {
            $_SESSION['error'] = 'Device not found.';
            $this->redirect('devices');
        }
        $token = (string) $device['qr_token'];
        $sets = $this->parseLabelSets((int) $this->get('sets', (int) $this->post('sets', DeviceAssetHelper::defaultLabelSets())));
        $zpl = DeviceAssetHelper::renderQrLabelZpl($device, $token, $sets);
        $model->logAudit($id, $uid, 'qr_zpl_download', null, [
            'asset_id' => $device['asset_id'] ?? '',
            'sets' => $sets,
            'labels_per_set' => DeviceAssetHelper::labelsPerSet(),
        ]);
        $filename = 'device-qr-' . preg_replace('/[^a-zA-Z0-9._-]+/', '_', (string) ($device['asset_id'] ?? $id)) . '-2up-x' . $sets . '.zpl';
        header('Content-Type: text/plain; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($zpl));
        echo $zpl;
        exit;
    }

    /** JSON — printers installed on the PC running WAMP. */
    public function listPrinters() {
        $uid = $this->requireLogin();
        $um = $this->requireView($uid);
        if (!$um->canPrintDeviceQr($uid)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['ok' => false, 'error' => 'Not authorized.', 'printers' => []]);
            exit;
        }

        $printers = DeviceAssetHelper::listSystemPrinters();
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'ok' => true,
            'printers' => $printers,
            'platform' => PHP_OS_FAMILY,
        ]);
        exit;
    }

    /** POST — send ZPL to a Windows printer on the server PC. */
    public function qrPrintServer() {
        $uid = $this->requireLogin();
        $um = $this->requireView($uid);
        if (!$um->canPrintDeviceQr($uid)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['ok' => false, 'error' => 'Not authorized.']);
            exit;
        }

        $printer = trim((string) $this->post('printer', ''));
        $zpl = (string) $this->post('zpl', '');
        $id = (int) $this->post('device_id', 0);
        $sets = $this->parseLabelSets((int) $this->post('sets', DeviceAssetHelper::defaultLabelSets()));

        if ($zpl === '' && $id > 0) {
            $device = $this->deviceModel()->findDevice($id);
            if ($device) {
                $zpl = DeviceAssetHelper::renderQrLabelZpl($device, (string) $device['qr_token'], $sets);
            }
        }

        $result = DeviceAssetHelper::sendZplToWindowsPrinter($printer, $zpl);
        if (!empty($result['ok']) && $id > 0) {
            $this->deviceModel()->logAudit($id, $uid, 'qr_server_print', null, [
                'printer' => $printer,
                'sets' => $sets,
            ]);
        }

        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($result);
        exit;
    }

    public function regenerateQr() {
        $uid = $this->requireLogin();
        $this->requireManage($uid);
        $id = (int) $this->post('id', 0);
        $model = $this->deviceModel();
        $device = $model->findDevice($id);
        if (!$device) {
            $_SESSION['error'] = 'Device not found.';
            $this->redirect('devices');
        }
        $oldToken = $device['qr_token'] ?? '';
        $newToken = $model->regenerateQrToken($id, $uid);
        if ($newToken === null) {
            $_SESSION['error'] = 'Could not regenerate QR.';
            $this->redirect('devices/view?id=' . $id);
        }
        $model->logAudit($id, $uid, 'qr_regenerated', ['qr_token' => $oldToken], ['qr_token' => $newToken]);
        $_SESSION['success'] = 'QR code regenerated.';
        $this->redirect('devices/view?id=' . $id);
    }

    public function scan() {
        $uid = $this->requireLogin();
        $um = $this->requireView($uid);
        if (!$um->canScanDeviceQr($uid)) {
            $_SESSION['error'] = 'You are not authorized to scan device QR codes.';
            $this->redirect('dashboard');
        }

        return $this->view('devices/scan', array_merge($this->baseViewData($uid, $um), [
            'page' => 'devices',
            'deviceSection' => 'scan',
        ]));
    }

    public function qrView($token = '') {
        $uid = $this->requireLogin();
        $um = $this->requireView($uid);
        if (!$this->checkQrRateLimit()) {
            return $this->view('devices/qr_unauthorized', [
                'page' => 'devices',
                'message' => 'Too many requests. Please wait and try again.',
            ]);
        }
        $token = trim((string) $token);
        if ($token === '') {
            return $this->view('devices/qr_unauthorized', [
                'page' => 'devices',
                'message' => 'Invalid QR code.',
            ]);
        }
        if (!$um->canScanDeviceQr($uid)) {
            return $this->view('devices/qr_unauthorized', [
                'page' => 'devices',
                'message' => 'You are not authorized to view this device information.',
            ]);
        }
        $model = $this->deviceModel();
        $device = $model->findByQrToken($token);
        if (!$device) {
            return $this->view('devices/qr_unauthorized', [
                'page' => 'devices',
                'message' => 'Device not found or invalid QR code.',
            ]);
        }
        $model->logAudit((int) $device['id'], $uid, 'qr_view', null, ['asset_id' => $device['asset_id'] ?? '']);
        $full = $um->canManageDevices($uid);

        return $this->view('devices/qr_view', array_merge($this->baseViewData($uid, $um), [
            'page' => 'devices',
            'device' => $device,
            'accessories' => $model->getAccessories((int) $device['id']),
            'activeAssignment' => $model->getActiveAssignment((int) $device['id']),
            'qrDataUri' => DeviceAssetHelper::qrPngDataUri($token, 260),
            'fullDetail' => $full,
        ]));
    }

    public function history() {
        $uid = $this->requireLogin();
        $um = $this->requireView($uid);
        if (!$um->canViewDeviceHistory($uid)) {
            $_SESSION['error'] = 'Not authorized.';
            $this->redirect('devices');
        }
        $id = (int) $this->get('id', 0);
        $model = $this->deviceModel();
        $device = $id > 0 ? $model->findDevice($id) : null;

        return $this->view('devices/history', array_merge($this->baseViewData($uid, $um), [
            'page' => 'devices',
            'deviceSection' => $id > 0 ? 'devices' : 'audit',
            'device' => $device,
            'auditLogs' => $model->getAuditLogs($id > 0 ? $id : null, 200),
            'assignments' => $id > 0 ? $model->getAssignmentHistory($id) : [],
            'conditionHistory' => $id > 0 ? $model->getConditionHistory($id) : [],
        ]));
    }

    public function audit() {
        return $this->history();
    }

    public function maintenance() {
        $_GET['status'] = DeviceModel::STATUS_UNDER_MAINTENANCE;
        return $this->index();
    }

    public function warranty() {
        $uid = $this->requireLogin();
        $this->requireView($uid);
        if (!isset($_GET['warranty']) || $_GET['warranty'] === '') {
            $_GET['warranty'] = 'expiring_90';
        }
        return $this->index();
    }

    public function assignments() {
        $uid = $this->requireLogin();
        $this->requireView($uid);
        $this->redirect('devices/list?assigned=yes&status=assigned');
    }

    public function export() {
        $uid = $this->requireLogin();
        $um = $this->requireView($uid);
        if (!$um->canExportDevices($uid)) {
            $_SESSION['error'] = 'Not authorized.';
            $this->redirect('devices');
        }
        $rows = $this->deviceModel()->exportRows($this->scopedFilters($this->listFilters(), $um, $uid));
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="slgti-devices-' . date('Y-m-d') . '.csv"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, [
            'Asset ID', 'Asset Tag', 'Type', 'Brand', 'Model', 'Serial', 'Assigned To', 'Department',
            'Status', 'Purchase Date', 'Supplier', 'Warranty Expiry', 'Computer Name',
        ]);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['asset_id'] ?? '',
                $r['asset_tag_no'] ?? '',
                $r['device_type'] ?? '',
                $r['brand'] ?? '',
                $r['model'] ?? '',
                $r['serial_number'] ?? '',
                $r['assigned_staff_name'] ?? '',
                $r['assigned_department_name'] ?? '',
                $r['status'] ?? '',
                $r['purchase_date'] ?? '',
                $r['supplier'] ?? '',
                $r['warranty_expiry'] ?? '',
                $r['computer_name'] ?? '',
            ]);
        }
        fclose($out);
        exit;
    }

    public function printRecord() {
        $uid = $this->requireLogin();
        $um = $this->requireView($uid);
        $id = (int) $this->get('id', 0);
        $model = $this->deviceModel();
        $device = $model->findDevice($id);
        if (!$device) {
            $_SESSION['error'] = 'Device not found.';
            $this->redirect('devices');
        }

        return $this->view('devices/print_record', [
            'use_print_layout' => true,
            'title' => 'Property Receipt — ' . ($device['asset_id'] ?? ''),
            'device' => $device,
            'accessories' => $model->getAccessories($id),
            'activeAssignment' => $model->getActiveAssignment($id),
            'assignments' => $model->getAssignmentHistory($id),
            'qrDataUri' => DeviceAssetHelper::qrPngDataUri((string) $device['qr_token'], 200),
            'fullDetail' => $um->canManageDevices($uid),
        ]);
    }

    private function checkQrRateLimit(): bool {
        $key = 'device_qr_rate';
        $now = time();
        if (!isset($_SESSION[$key]) || !is_array($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'start' => $now];
        }
        if ($now - (int) $_SESSION[$key]['start'] > 60) {
            $_SESSION[$key] = ['count' => 0, 'start' => $now];
        }
        $_SESSION[$key]['count'] = (int) $_SESSION[$key]['count'] + 1;

        return $_SESSION[$key]['count'] <= 30;
    }

    /**
     * @param array<string, mixed>|null $device
     * @return array<string, mixed>
     */
    private function formViewData(?array $device): array {
        $uid = (int) $_SESSION['user_id'];
        $um = $this->userModel();
        $id = $device ? (int) ($device['id'] ?? 0) : 0;
        $model = $this->deviceModel();

        require_once BASE_PATH . '/models/DepartmentModel.php';

        return array_merge($this->baseViewData($uid, $um), [
            'page' => 'devices',
            'deviceSection' => $id > 0 ? 'edit' : 'create',
            'device' => $device,
            'departments' => (new DepartmentModel())->getAll(),
            'categories' => DeviceModel::ASSET_CATEGORIES,
            'conditions' => DeviceModel::SIMPLE_CONDITIONS,
            'accessories' => $id > 0 ? $model->getAccessories($id) : [],
            'statuses' => DeviceModel::STATUSES,
            'deviceTypes' => DeviceModel::DEVICE_TYPES,
            'conditionValues' => DeviceModel::CONDITION_VALUES,
            'accessoryTypes' => DeviceModel::ACCESSORY_TYPES,
            'isEdit' => $device !== null,
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function validatedDevicePost(?int $exceptId = null): ?array {
        $assetName = trim((string) $this->post('asset_name', ''));
        $assetId = trim((string) $this->post('asset_id', ''));
        if ($assetName === '' && $assetId === '') {
            $_SESSION['error'] = 'Please enter the asset name.';
            return null;
        }
        if ($assetName === '') {
            $assetName = $assetId;
        }
        $category = trim((string) $this->post('category', ''));
        if ($category !== '' && !in_array($category, DeviceModel::ASSET_CATEGORIES, true)) {
            $category = 'Other';
        }
        $deviceType = $category !== ''
            ? DeviceModel::deviceTypeForCategory($category)
            : trim((string) $this->post('device_type', 'Other'));
        if (!in_array($deviceType, DeviceModel::DEVICE_TYPES, true)) {
            $deviceType = 'Other';
        }
        $um = $this->userModel();
        $uid = (int) ($_SESSION['user_id'] ?? 0);
        $scope = $this->assetScope($um, $uid);
        $departmentId = ($scope['mode'] ?? '') === 'department'
            ? (string) ($scope['department_id'] ?? '')
            : trim((string) $this->post('department_id', ''));
        if ($departmentId === '') {
            $_SESSION['error'] = 'Please choose a department.';
            return null;
        }
        $condition = trim((string) $this->post('asset_condition', 'Good'));
        if (!in_array($condition, DeviceModel::SIMPLE_CONDITIONS, true)) {
            $condition = 'Good';
        }
        $quantity = max(1, (int) $this->post('quantity', 1));
        $assigned = false;
        $status = DeviceModel::statusForCondition($condition, false);
        if ($exceptId !== null && $exceptId > 0) {
            $existing = $this->deviceModel()->findDevice($exceptId);
            if ($existing && (string) ($existing['status'] ?? '') === DeviceModel::STATUS_DISPOSED) {
                $status = DeviceModel::STATUS_DISPOSED;
            } else {
                $assigned = $existing ? $this->deviceModel()->activeAssignmentCount($exceptId) > 0 : false;
                $status = DeviceModel::statusForCondition($condition, $assigned);
            }
            if ($existing && $quantity < $this->deviceModel()->activeAssignmentCount($exceptId)) {
                $_SESSION['error'] = 'Quantity cannot be less than the number of people who already have this asset.';
                return null;
            }
        }
        $cost = trim((string) $this->post('purchase_cost', ''));
        $purchaseCost = $cost === '' ? null : (float) $cost;

        return [
            'asset_id' => $assetId,
            'asset_name' => $assetName,
            'category' => $category !== '' ? $category : $deviceType,
            'department_id' => $departmentId,
            'room_location' => trim((string) $this->post('room_location', '')) ?: null,
            'quantity' => $quantity,
            'asset_condition' => $condition,
            'asset_tag_no' => trim((string) $this->post('asset_tag_no', '')) ?: null,
            'device_type' => $deviceType,
            'brand' => trim((string) $this->post('brand', '')) ?: null,
            'model' => trim((string) $this->post('model', '')) ?: null,
            'serial_number' => trim((string) $this->post('serial_number', '')) ?: null,
            'product_number' => trim((string) $this->post('product_number', '')) ?: null,
            'service_tag' => trim((string) $this->post('service_tag', '')) ?: null,
            'computer_name' => trim((string) $this->post('computer_name', '')) ?: null,
            'processor' => trim((string) $this->post('processor', '')) ?: null,
            'ram' => trim((string) $this->post('ram', '')) ?: null,
            'storage_type' => trim((string) $this->post('storage_type', '')) ?: null,
            'storage_capacity' => trim((string) $this->post('storage_capacity', '')) ?: null,
            'display_size' => trim((string) $this->post('display_size', '')) ?: null,
            'operating_system' => trim((string) $this->post('operating_system', '')) ?: null,
            'purchase_date' => trim((string) $this->post('purchase_date', '')) ?: null,
            'supplier' => trim((string) $this->post('supplier', '')) ?: null,
            'purchase_cost' => $purchaseCost,
            'warranty_expiry' => trim((string) $this->post('warranty_expiry', '')) ?: null,
            'lan_mac_address' => trim((string) $this->post('lan_mac_address', '')) ?: null,
            'wifi_mac_address' => trim((string) $this->post('wifi_mac_address', '')) ?: null,
            'charger_serial_no' => trim((string) $this->post('charger_serial_no', '')) ?: null,
            'battery_serial_no' => trim((string) $this->post('battery_serial_no', '')) ?: null,
            'status' => $status,
            'windows_activated' => (int) $this->post('windows_activated', 0) === 1 ? 1 : 0,
            'ms_office_activated' => (int) $this->post('ms_office_activated', 0) === 1 ? 1 : 0,
            'bitlocker_enabled' => (int) $this->post('bitlocker_enabled', 0) === 1 ? 1 : 0,
            'antivirus_installed' => (int) $this->post('antivirus_installed', 0) === 1 ? 1 : 0,
            'remarks' => trim((string) $this->post('remarks', '')) ?: null,
        ];
    }

    private function saveAssetPhoto(int $deviceId): ?string {
        if (empty($_FILES['photo']['tmp_name']) || !is_uploaded_file((string) $_FILES['photo']['tmp_name'])) {
            return null;
        }
        $ext = strtolower(pathinfo((string) ($_FILES['photo']['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $_SESSION['error'] = 'Photo must be a JPG, PNG, or WEBP file.';
            return null;
        }
        if ((int) ($_FILES['photo']['size'] ?? 0) > 5 * 1024 * 1024) {
            $_SESSION['error'] = 'Photo must be smaller than 5 MB.';
            return null;
        }
        $dir = BASE_PATH . '/uploads/device-assets';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return null;
        }
        $name = 'asset-' . $deviceId . '-' . date('YmdHis') . '.' . $ext;
        if (!move_uploaded_file((string) $_FILES['photo']['tmp_name'], $dir . '/' . $name)) {
            return null;
        }

        return 'uploads/device-assets/' . $name;
    }

    public function conditionForm() {
        $uid = $this->requireLogin();
        $um = $this->requireManage($uid);
        $model = $this->deviceModel();
        $id = (int) $this->get('id', 0);
        $device = $id > 0 ? $model->findDevice($id) : null;
        if ($device) {
            $this->denyIfHidden($device, $um, $uid);
        }

        return $this->view('devices/condition', array_merge($this->baseViewData($uid, $um), [
            'page' => 'devices',
            'deviceSection' => 'condition',
            'device' => $device,
            'devices' => $model->listDevices($this->scopedFilters([], $um, $uid), 1, 300)['rows'],
            'conditions' => DeviceModel::SIMPLE_CONDITIONS,
            'history' => $device ? $model->getConditionHistory((int) $device['id']) : [],
        ]));
    }

    public function conditionSave() {
        $uid = $this->requireLogin();
        $um = $this->requireManage($uid);
        $id = (int) $this->post('device_id', 0);
        $model = $this->deviceModel();
        $device = $model->findDevice($id);
        if (!$device) {
            $_SESSION['error'] = 'Please choose an asset.';
            $this->redirect('devices/condition');
        }
        $this->denyIfHidden($device, $um, $uid);
        $condition = trim((string) $this->post('asset_condition', ''));
        $remarks = trim((string) $this->post('remarks', ''));
        if (!in_array($condition, DeviceModel::SIMPLE_CONDITIONS, true)) {
            $_SESSION['error'] = 'Please choose the condition.';
            $this->redirect('devices/condition?id=' . $id);
        }
        $photo = $this->saveAssetPhoto($id);
        if (!$model->recordSimpleCondition($id, $condition, $remarks, $uid, $photo)) {
            $_SESSION['error'] = 'Could not save the condition.';
            $this->redirect('devices/condition?id=' . $id);
        }
        $_SESSION['success'] = 'Condition updated.';
        $this->redirect('devices/view?id=' . $id);
    }

    public function transferForm() {
        $uid = $this->requireLogin();
        $um = $this->requireView($uid);
        if (!$um->canManageDevices($uid) && !$um->canApproveDeviceRequests($uid)) {
            $_SESSION['error'] = 'You cannot open asset transfers.';
            $this->redirect('devices');
        }
        $model = $this->deviceModel();
        require_once BASE_PATH . '/models/DepartmentModel.php';
        $scope = $this->assetScope($um, $uid);

        return $this->view('devices/transfer', array_merge($this->baseViewData($uid, $um), [
            'page' => 'devices',
            'deviceSection' => 'transfer',
            'devices' => $um->canManageDevices($uid) ? $model->listDevices($this->scopedFilters([], $um, $uid), 1, 300)['rows'] : [],
            'departments' => (new DepartmentModel())->getAll(),
            'transfers' => $model->listTransfers($scope),
        ]));
    }

    public function transferSave() {
        $uid = $this->requireLogin();
        $um = $this->requireManage($uid);
        $id = (int) $this->post('device_id', 0);
        $model = $this->deviceModel();
        $device = $model->findDevice($id);
        if (!$device) {
            $_SESSION['error'] = 'Please choose an asset.';
            $this->redirect('devices/transfer');
        }
        $this->denyIfHidden($device, $um, $uid);
        $to = trim((string) $this->post('to_department_id', ''));
        $location = trim((string) $this->post('to_location', ''));
        $reason = trim((string) $this->post('reason', ''));
        if ($to === '' || $reason === '') {
            $_SESSION['error'] = 'Choose the department and write the reason.';
            $this->redirect('devices/transfer');
        }
        if ((string) ($device['department_id'] ?? '') === $to) {
            $_SESSION['error'] = 'Choose a different department.';
            $this->redirect('devices/transfer');
        }
        if (!$model->requestTransfer($id, $to, $location, $reason, $uid)) {
            $_SESSION['error'] = 'Could not send the transfer request.';
            $this->redirect('devices/transfer');
        }
        $_SESSION['success'] = 'Transfer request sent. It is waiting for approval.';
        $this->redirect('devices/transfer');
    }

    public function transferDecide() {
        $uid = $this->requireLogin();
        $um = $this->requireView($uid);
        if (!$um->canApproveDeviceRequests($uid)) {
            $_SESSION['error'] = 'Only an authorized officer can approve this.';
            $this->redirect('devices/transfer');
        }
        $id = (int) $this->post('transfer_id', 0);
        $decision = trim((string) $this->post('decision', ''));
        $ok = $this->deviceModel()->decideTransfer($id, $decision === 'approved' ? 'approved' : 'rejected', $uid, trim((string) $this->post('note', '')));
        $_SESSION[$ok ? 'success' : 'error'] = $ok ? 'Transfer updated.' : 'Could not update that transfer.';
        $this->redirect('devices/transfer');
    }

    public function transferConfirm() {
        $uid = $this->requireLogin();
        $um = $this->requireManage($uid);
        $id = (int) $this->post('transfer_id', 0);
        $model = $this->deviceModel();
        $row = $model->findTransfer($id);
        if (!$row || ($row['status'] ?? '') !== 'approved') {
            $_SESSION['error'] = 'This transfer is not ready to receive.';
            $this->redirect('devices/transfer');
        }
        $scope = $this->assetScope($um, $uid);
        if (($scope['mode'] ?? '') === 'department' && (string) ($row['to_department_id'] ?? '') !== (string) ($scope['department_id'] ?? '')) {
            $_SESSION['error'] = 'Only the receiving department can confirm this.';
            $this->redirect('devices/transfer');
        }
        if ($model->confirmTransfer($id, $uid)) {
            $_SESSION['success'] = 'Transfer completed. The asset is now with the new department.';
        } else {
            $_SESSION['error'] = 'Could not complete the transfer.';
        }
        $this->redirect('devices/transfer');
    }

    public function disposeForm() {
        $uid = $this->requireLogin();
        $um = $this->requireView($uid);
        if (!$um->canManageDevices($uid) && !$um->canApproveDeviceRequests($uid)) {
            $_SESSION['error'] = 'You cannot open disposal requests.';
            $this->redirect('devices');
        }
        $model = $this->deviceModel();

        return $this->view('devices/dispose', array_merge($this->baseViewData($uid, $um), [
            'page' => 'devices',
            'deviceSection' => 'dispose',
            'devices' => $um->canManageDevices($uid) ? $model->listDevices($this->scopedFilters([], $um, $uid), 1, 300)['rows'] : [],
            'reasons' => DeviceModel::DISPOSE_REASONS,
            'disposals' => $model->listDisposals($this->assetScope($um, $uid)),
        ]));
    }

    public function disposeSave() {
        $uid = $this->requireLogin();
        $um = $this->requireManage($uid);
        $id = (int) $this->post('device_id', 0);
        $model = $this->deviceModel();
        $device = $model->findDevice($id);
        if (!$device) {
            $_SESSION['error'] = 'Please choose an asset.';
            $this->redirect('devices/dispose');
        }
        $this->denyIfHidden($device, $um, $uid);
        $reason = trim((string) $this->post('reason', ''));
        if (!$model->requestDisposal($id, $reason, trim((string) $this->post('remarks', '')), $uid)) {
            $_SESSION['error'] = 'Please choose a reason.';
            $this->redirect('devices/dispose');
        }
        $_SESSION['success'] = 'Disposal request sent.';
        $this->redirect('devices/dispose');
    }

    public function disposeDecide() {
        $uid = $this->requireLogin();
        $um = $this->requireView($uid);
        if (!$um->canApproveDeviceRequests($uid)) {
            $_SESSION['error'] = 'Only an authorized officer can approve this.';
            $this->redirect('devices/dispose');
        }
        $ok = $this->deviceModel()->decideDisposal(
            (int) $this->post('disposal_id', 0),
            trim((string) $this->post('decision', '')) === 'approved' ? 'approved' : 'rejected',
            $uid,
            trim((string) $this->post('note', ''))
        );
        $_SESSION[$ok ? 'success' : 'error'] = $ok ? 'Disposal request updated. Approved assets stay in the history.' : 'Could not update that request.';
        $this->redirect('devices/dispose');
    }

    public function reports() {
        $uid = $this->requireLogin();
        $um = $this->requireView($uid);
        $report = trim((string) $this->get('report', 'department'));
        $allowed = ['department', 'staff_assigned', 'student_assigned', 'available', 'damaged', 'transfers', 'disposals'];
        if (!in_array($report, $allowed, true)) {
            $report = 'department';
        }
        $model = $this->deviceModel();
        $scope = $this->assetScope($um, $uid);
        $filters = $this->scopedFilters(['report' => $report], $um, $uid);
        $rows = in_array($report, ['transfers', 'disposals'], true) ? [] : $model->listDevices($filters, 1, 1000)['rows'];

        return $this->view('devices/reports', array_merge($this->baseViewData($uid, $um), [
            'page' => 'devices',
            'deviceSection' => 'reports',
            'report' => $report,
            'rows' => $rows,
            'transfers' => $report === 'transfers' ? $model->listTransfers($scope) : [],
            'disposals' => $report === 'disposals' ? $model->listDisposals($scope) : [],
        ]));
    }

    public function reportFile() {
        $uid = $this->requireLogin();
        $um = $this->requireView($uid);
        $report = trim((string) $this->get('report', 'department'));
        $format = trim((string) $this->get('format', 'excel'));
        $model = $this->deviceModel();
        $scope = $this->assetScope($um, $uid);
        $rows = $model->listDevices($this->scopedFilters(['report' => $report], $um, $uid), 1, 2000)['rows'];
        $title = 'SLGTI assets';
        if ($format === 'pdf') {
            require_once BASE_PATH . '/helpers/ExamPdfHelper.php';
            if (!ExamPdfHelper::dompdfAvailable()) {
                $_SESSION['error'] = 'PDF is not available on this computer. Use Excel, or print the report from the browser.';
                $this->redirect('devices/reports?report=' . rawurlencode($report));
            }
            $html = $this->reportHtml($title, $rows);
            ExamPdfHelper::streamHtml($html, 'slgti-assets-' . date('Y-m-d') . '.pdf');
            return;
        }
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="slgti-assets-' . date('Y-m-d') . '.csv"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Asset code', 'Asset name', 'Department', 'Location', 'Condition', 'Status', 'Assigned to']);
        foreach ($rows as $row) {
            fputcsv($out, [
                $row['asset_id'] ?? '',
                $row['asset_name'] ?? '',
                $row['owner_department_name'] ?? '',
                $row['room_location'] ?? '',
                $row['asset_condition'] ?? '',
                DeviceModel::statusLabel((string) ($row['status'] ?? '')),
                $row['assigned_student_name'] ?? $row['assigned_staff_name'] ?? '',
            ]);
        }
        fclose($out);
        exit;
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function reportHtml(string $title, array $rows): string {
        $body = '';
        foreach ($rows as $row) {
            $who = htmlspecialchars((string) (($row['assigned_student_name'] ?? '') ?: ($row['assigned_staff_name'] ?? '')), ENT_QUOTES, 'UTF-8');
            $body .= '<tr><td>' . htmlspecialchars((string) ($row['asset_id'] ?? ''), ENT_QUOTES, 'UTF-8')
                . '</td><td>' . htmlspecialchars((string) ($row['asset_name'] ?? ''), ENT_QUOTES, 'UTF-8')
                . '</td><td>' . htmlspecialchars((string) ($row['owner_department_name'] ?? ''), ENT_QUOTES, 'UTF-8')
                . '</td><td>' . htmlspecialchars((string) ($row['room_location'] ?? ''), ENT_QUOTES, 'UTF-8')
                . '</td><td>' . htmlspecialchars((string) ($row['asset_condition'] ?? ''), ENT_QUOTES, 'UTF-8')
                . '</td><td>' . $who . '</td></tr>';
        }
        if ($body === '') {
            $body = '<tr><td colspan="6">No assets found.</td></tr>';
        }

        return '<html><body><h1>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1><table border="1" cellpadding="4" cellspacing="0"><tr><th>Code</th><th>Name</th><th>Department</th><th>Location</th><th>Condition</th><th>Assigned to</th></tr>' . $body . '</table></body></html>';
    }

    public function stickers() {
        $uid = $this->requireLogin();
        $um = $this->requireView($uid);
        $model = $this->deviceModel();
        $ids = $this->get('ids', []);
        if (!is_array($ids)) {
            $ids = $ids !== '' ? explode(',', (string) $ids) : [];
        }
        $picked = [];
        foreach ($ids as $raw) {
            $device = $model->findDevice((int) $raw);
            if ($device && $this->canSeeDevice($device, $this->assetScope($um, $uid))) {
                $picked[] = $device;
            }
        }

        return $this->view('devices/stickers', array_merge($this->baseViewData($uid, $um), [
            'page' => 'devices',
            'deviceSection' => 'stickers',
            'devices' => $model->listDevices($this->scopedFilters([], $um, $uid), 1, 300)['rows'],
            'picked' => $picked,
        ]));
    }

    private function hasConditionPost(): bool {
        return trim((string) $this->post('cond_lcd_screen', '')) !== ''
            || trim((string) $this->post('cond_keyboard', '')) !== '';
    }

    /**
     * @return array<string, mixed>
     */
    private function conditionFromPost(): array {
        return [
            'lcd_screen' => trim((string) $this->post('cond_lcd_screen', '')) ?: null,
            'keyboard' => trim((string) $this->post('cond_keyboard', '')) ?: null,
            'touchpad' => trim((string) $this->post('cond_touchpad', '')) ?: null,
            'battery' => trim((string) $this->post('cond_battery', '')) ?: null,
            'ports' => trim((string) $this->post('cond_ports', '')) ?: null,
            'charger' => trim((string) $this->post('cond_charger', '')) ?: null,
            'outer_body' => trim((string) $this->post('cond_outer_body', '')) ?: null,
            'remarks' => trim((string) $this->post('condition_remarks', '')) ?: null,
        ];
    }

    private function excelList($sheet, string $cell, string $formula): void {
        $validation = new \PhpOffice\PhpSpreadsheet\Cell\DataValidation();
        $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
        $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
        $validation->setAllowBlank(true);
        $validation->setShowDropDown(false);
        $validation->setShowErrorMessage(true);
        $validation->setErrorTitle('Choose from the list');
        $validation->setError('Please choose a value from the list.');
        $validation->setFormula1($formula);
        $sheet->setDataValidation($cell, $validation);
    }

    /**
     * @return list<array<string, string>>|null
     */
    private function readAssetSpreadsheet(): ?array {
        if (empty($_FILES['import_file']) || (int) $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = 'Choose an Excel file.';
            return null;
        }
        $file = $_FILES['import_file'];
        if ((int) $file['size'] > 5 * 1024 * 1024) {
            $_SESSION['error'] = 'The file is larger than 5 MB.';
            return null;
        }
        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        $table = [];
        if (in_array($ext, ['xlsx', 'xls'], true)) {
            $autoload = BASE_PATH . '/vendor/autoload.php';
            if (!is_file($autoload)) {
                $_SESSION['error'] = 'Excel is not available. Save the file as CSV and upload that.';
                return null;
            }
            require_once $autoload;
            try {
                $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file['tmp_name'])->getActiveSheet();
                $table = $sheet->toArray('', true, true, false);
            } catch (Throwable $e) {
                $_SESSION['error'] = 'The Excel file could not be read. Download a new template and try again.';
                return null;
            }
        } elseif ($ext === 'csv') {
            $handle = fopen($file['tmp_name'], 'r');
            if (!$handle) {
                $_SESSION['error'] = 'The file could not be read.';
                return null;
            }
            while (($line = fgetcsv($handle)) !== false) {
                $table[] = $line;
            }
            fclose($handle);
        } else {
            $_SESSION['error'] = 'Upload the Excel file from Download Excel file.';
            return null;
        }
        if (!$table || !isset($table[0]) || !is_array($table[0])) {
            $_SESSION['error'] = 'The file is empty.';
            return null;
        }
        $keys = [];
        foreach ($table[0] as $header) {
            $keys[] = $this->assetImportHeaderKey((string) $header);
        }
        if (!in_array('asset_name', $keys, true)) {
            $_SESSION['error'] = 'The first row must include Asset Name. Download the Excel file and use that.';
            return null;
        }
        $rows = [];
        $body = array_slice($table, 1);
        foreach ($body as $index => $line) {
            if (count($rows) >= 500) {
                break;
            }
            if (!is_array($line)) {
                continue;
            }
            $assoc = [];
            foreach ($keys as $i => $key) {
                if ($key === '') {
                    continue;
                }
                $assoc[$key] = isset($line[$i]) ? trim((string) $line[$i]) : '';
            }
            if (implode('', $assoc) === '') {
                continue;
            }
            $assoc['_row'] = $index + 2;
            $rows[] = $assoc;
        }

        return $rows;
    }

    private function assetImportHeaderKey(string $header): string {
        $key = strtolower(trim($header));
        $key = preg_replace('/^\xEF\xBB\xBF/', '', $key);
        $key = str_replace(['/', '-', ' '], '_', $key);
        $key = preg_replace('/_+/', '_', (string) $key);
        $map = [
            'asset_name' => 'asset_name',
            'name' => 'asset_name',
            'category' => 'category',
            'brand' => 'brand',
            'model' => 'model',
            'serial_number' => 'serial_number',
            'serial' => 'serial_number',
            'purchase_date' => 'purchase_date',
            'purchase_price' => 'purchase_price',
            'price' => 'purchase_price',
            'room_location' => 'room_location',
            'room' => 'room_location',
            'location' => 'room_location',
            'quantity' => 'quantity',
            'qty' => 'quantity',
            'asset_condition' => 'asset_condition',
            'condition' => 'asset_condition',
            'remarks' => 'remarks',
            'remark' => 'remarks',
        ];

        return $map[$key] ?? '';
    }

    /**
     * @param array<string, string> $row
     * @return array{error?: string, data?: array<string, mixed>}
     */
    private function assetFromImportRow(array $row, string $departmentId): array {
        $name = trim((string) ($row['asset_name'] ?? ''));
        if ($name === '') {
            return ['error' => 'Enter the asset name.'];
        }
        $category = $this->matchChoice((string) ($row['category'] ?? ''), DeviceModel::ASSET_CATEGORIES);
        if ($category === null) {
            return ['error' => 'Choose a category from the list.'];
        }
        $condition = trim((string) ($row['asset_condition'] ?? ''));
        if ($condition === '') {
            $condition = 'Good';
        } else {
            $matched = $this->matchChoice($condition, DeviceModel::SIMPLE_CONDITIONS);
            if ($matched === null) {
                return ['error' => 'Choose a condition from the list.'];
            }
            $condition = $matched;
        }
        if ($departmentId === '') {
            return ['error' => 'Choose a department on the page before you upload.'];
        }
        $quantity = trim((string) ($row['quantity'] ?? ''));
        $quantity = $quantity === '' ? 1 : (int) $quantity;
        if ($quantity < 1) {
            return ['error' => 'Quantity must be 1 or more.'];
        }
        $price = str_replace([',', 'Rs', 'rs', 'LKR', ' '], '', (string) ($row['purchase_price'] ?? ''));
        $purchaseDate = $this->parseAssetDate((string) ($row['purchase_date'] ?? ''));
        if (trim((string) ($row['purchase_date'] ?? '')) !== '' && $purchaseDate === null) {
            return ['error' => 'Purchase date must look like 2026-03-15.'];
        }

        return ['data' => [
            'asset_name' => $name,
            'category' => $category,
            'device_type' => DeviceModel::deviceTypeForCategory($category),
            'brand' => trim((string) ($row['brand'] ?? '')) ?: null,
            'model' => trim((string) ($row['model'] ?? '')) ?: null,
            'serial_number' => trim((string) ($row['serial_number'] ?? '')) ?: null,
            'purchase_date' => $purchaseDate,
            'purchase_cost' => $price === '' ? null : (float) $price,
            'department_id' => $departmentId,
            'room_location' => trim((string) ($row['room_location'] ?? '')) ?: null,
            'quantity' => $quantity,
            'asset_condition' => $condition,
            'status' => DeviceModel::statusForCondition($condition, false),
            'remarks' => trim((string) ($row['remarks'] ?? '')) ?: null,
        ]];
    }

    /**
     * @param list<string> $choices
     */
    private function matchChoice(string $value, array $choices): ?string {
        $value = strtolower(trim($value));
        if ($value === '') {
            return null;
        }
        foreach ($choices as $choice) {
            if (strtolower($choice) === $value) {
                return $choice;
            }
        }

        return null;
    }

    private function parseAssetDate(string $value): ?string {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
            return substr($value, 0, 10);
        }
        if (preg_match('/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})$/', $value, $match)) {
            return sprintf('%04d-%02d-%02d', (int) $match[3], (int) $match[2], (int) $match[1]);
        }
        if (is_numeric($value)) {
            $unix = ((float) $value - 25569) * 86400;
            if ($unix > 0) {
                return gmdate('Y-m-d', (int) $unix);
            }
        }
        $time = strtotime($value);

        return $time ? date('Y-m-d', $time) : null;
    }
}
