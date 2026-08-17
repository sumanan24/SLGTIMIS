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
            'device_type' => trim((string) $this->get('type', '')),
            'status' => trim((string) $this->get('status', '')),
            'department_id' => trim((string) $this->get('dept', '')),
            'assigned' => trim((string) $this->get('assigned', '')),
            'warranty' => trim((string) $this->get('warranty', '')),
            'sort' => trim((string) $this->get('sort', 'asset_id')),
            'dir' => trim((string) $this->get('dir', 'ASC')),
        ];
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
        return [
            'canManage' => $um->canManageDevices($uid),
            'canExport' => $um->canExportDevices($uid),
            'canPrintQr' => $um->canPrintDeviceQr($uid),
            'canViewHistory' => $um->canViewDeviceHistory($uid),
            'canScan' => $um->canScanDeviceQr($uid),
            'isReadOnlyRole' => !$um->canManageDevices($uid),
        ];
    }

    public function dashboard() {
        $uid = $this->requireLogin();
        $um = $this->requireView($uid);
        $model = $this->deviceModel();

        return $this->view('devices/dashboard', array_merge($this->baseViewData($uid, $um), [
            'page' => 'devices',
            'deviceSection' => 'dashboard',
            'stats' => $model->dashboardStats(),
            'chartDept' => $model->chartByDepartment(),
            'chartType' => $model->chartByType(),
            'chartStatus' => $model->chartByStatus(),
        ]));
    }

    public function index() {
        $uid = $this->requireLogin();
        $um = $this->requireView($uid);
        $model = $this->deviceModel();
        $filters = $this->listFilters();
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
        $model->logAudit($id, $uid, 'device_created', null, $data);
        $this->logActivity('CREATE', 'devices', (string) $id, 'Device created: ' . $data['asset_id']);
        $_SESSION['success'] = 'Device registered successfully.';
        $this->redirect('devices/view?id=' . $id);
    }

    public function edit() {
        $uid = $this->requireLogin();
        $this->requireManage($uid);
        $id = (int) $this->get('id', 0);
        $device = $this->deviceModel()->findDevice($id);
        if (!$device) {
            $_SESSION['error'] = 'Device not found.';
            $this->redirect('devices');
        }

        return $this->view('devices/form', $this->formViewData($device));
    }

    public function update() {
        $uid = $this->requireLogin();
        $this->requireManage($uid);
        $id = (int) $this->get('id', 0);
        $model = $this->deviceModel();
        $old = $model->findDevice($id);
        if (!$old) {
            $_SESSION['error'] = 'Device not found.';
            $this->redirect('devices');
        }
        $data = $this->validatedDevicePost($id);
        if ($data === null) {
            $this->redirect('devices/edit?id=' . $id);
        }
        if ($model->assetIdExists($data['asset_id'], $id)) {
            $_SESSION['error'] = 'Asset ID already exists.';
            $this->redirect('devices/edit?id=' . $id);
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
        $_SESSION['success'] = 'Device updated.';
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
        $model->softDeleteDevice($id, $uid);
        $model->logAudit($id, $uid, 'device_deleted', $old, null);
        $this->logActivity('DELETE', 'devices', (string) $id, 'Device deleted: ' . ($old['asset_id'] ?? ''));
        $_SESSION['success'] = 'Device removed.';
        $this->redirect('devices');
    }

    public function show() {
        $uid = $this->requireLogin();
        $um = $this->requireView($uid);
        $id = (int) $this->get('id', 0);
        $model = $this->deviceModel();
        $device = $model->findDevice($id);
        if (!$device) {
            $_SESSION['error'] = 'Device not found.';
            $this->redirect('devices');
        }
        $qrUri = DeviceAssetHelper::qrPngDataUri((string) $device['qr_token'], 260);

        return $this->view('devices/view', array_merge($this->baseViewData($uid, $um), [
            'page' => 'devices',
            'deviceSection' => 'devices',
            'device' => $device,
            'accessories' => $model->getAccessories($id),
            'assignments' => $model->getAssignmentHistory($id),
            'activeAssignment' => $model->getActiveAssignment($id),
            'conditionHistory' => $model->getConditionHistory($id),
            'qrDataUri' => $qrUri,
            'qrUrl' => DeviceAssetHelper::qrScanUrl((string) $device['qr_token']),
            'fullDetail' => $um->canManageDevices($uid),
            'labelPrinterConfig' => DeviceAssetHelper::labelConfig(),
            'defaultLabelSets' => DeviceAssetHelper::defaultLabelSets(),
            'maxLabelSets' => DeviceAssetHelper::maxLabelSets(),
            'labelsPerSet' => DeviceAssetHelper::labelsPerSet(),
        ]));
    }

    public function assign() {
        $uid = $this->requireLogin();
        $this->requireManage($uid);
        $id = (int) $this->get('id', 0);
        $model = $this->deviceModel();
        $device = $model->findDevice($id);
        if (!$device) {
            $_SESSION['error'] = 'Device not found.';
            $this->redirect('devices');
        }
        require_once BASE_PATH . '/models/StaffModel.php';
        $staff = (new StaffModel())->getStaffWithDepartment(1, 500, '', null);

        return $this->view('devices/assign', array_merge($this->baseViewData($uid, $this->userModel()), [
            'page' => 'devices',
            'deviceSection' => 'assignments',
            'device' => $device,
            'staffList' => $staff,
            'activeAssignment' => $model->getActiveAssignment($id),
        ]));
    }

    public function assignSave() {
        $uid = $this->requireLogin();
        $this->requireManage($uid);
        $id = (int) $this->post('device_id', 0);
        $employeeId = trim((string) $this->post('employee_id', ''));
        $issueDate = trim((string) $this->post('issue_date', ''));
        $remarks = trim((string) $this->post('remarks', ''));
        $model = $this->deviceModel();
        $device = $model->findDevice($id);
        if (!$device) {
            $_SESSION['error'] = 'Device not found.';
            $this->redirect('devices');
        }
        if ($employeeId === '' || $issueDate === '') {
            $_SESSION['error'] = 'Employee and issue date are required.';
            $this->redirect('devices/assign?id=' . $id);
        }
        require_once BASE_PATH . '/models/StaffModel.php';
        if (!(new StaffModel())->exists($employeeId)) {
            $_SESSION['error'] = 'Invalid employee.';
            $this->redirect('devices/assign?id=' . $id);
        }
        $old = $device;
        if (!$model->assignDevice($id, $employeeId, $issueDate, $uid, $remarks)) {
            $_SESSION['error'] = 'Assignment failed.';
            $this->redirect('devices/assign?id=' . $id);
        }
        $model->logAudit($id, $uid, 'device_assigned', $old, ['employee_id' => $employeeId, 'issue_date' => $issueDate]);
        $this->logActivity('UPDATE', 'devices', (string) $id, 'Device assigned to ' . $employeeId);
        $_SESSION['success'] = 'Device assigned.';
        $this->redirect('devices/view?id=' . $id);
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
        $old = $device;
        $model->returnDevice($id, $returnDate, $uid, $remarks, $newStatus);
        $model->logAudit($id, $uid, 'device_returned', $old, ['return_date' => $returnDate, 'status' => $newStatus]);
        $_SESSION['success'] = 'Device returned.';
        $this->redirect('devices/view?id=' . $id);
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
            'qrDataUri' => DeviceAssetHelper::qrPngDataUri($token),
            'qrUrl' => DeviceAssetHelper::qrScanUrl($token),
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
        $qrDataUri = DeviceAssetHelper::qrPngDataUri($token, 220);
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
        $rows = $this->deviceModel()->exportRows($this->listFilters());
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
            'title' => 'Asset Record — ' . ($device['asset_id'] ?? ''),
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

        return array_merge($this->baseViewData($uid, $um), [
            'page' => 'devices',
            'deviceSection' => $id > 0 ? 'devices' : 'create',
            'device' => $device,
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
        $assetId = trim((string) $this->post('asset_id', ''));
        if ($assetId === '') {
            $_SESSION['error'] = 'Asset ID is required.';
            return null;
        }
        $deviceType = trim((string) $this->post('device_type', 'Laptop'));
        if (!in_array($deviceType, DeviceModel::DEVICE_TYPES, true)) {
            $deviceType = 'Laptop';
        }
        $status = trim((string) $this->post('status', DeviceModel::STATUS_AVAILABLE));
        if (!in_array($status, DeviceModel::STATUSES, true)) {
            $status = DeviceModel::STATUS_AVAILABLE;
        }
        $cost = trim((string) $this->post('purchase_cost', ''));
        $purchaseCost = $cost === '' ? null : (float) $cost;

        return [
            'asset_id' => $assetId,
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
}
