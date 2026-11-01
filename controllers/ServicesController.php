<?php
class ServicesController extends Controller {
    private $model;

    public function __construct() {
        parent::__construct();
        require_once BASE_PATH . '/models/ServiceModel.php';
        require_once BASE_PATH . '/helpers/ServiceRegistry.php';
        require_once BASE_PATH . '/helpers/ServiceAccess.php';
        $this->model = new ServiceModel();
    }

    public function dashboard() {
        if (!$this->boot('view')) {
            return;
        }
        $actor = ServiceAccess::actor((int) $_SESSION['user_id']);
        $this->model->remind();
        return $this->view('services/dashboard', $this->pack($actor, [
            'title' => 'Service Providers',
            'section' => 'dashboard',
            'summary' => $this->model->dashboard(),
            'areas' => ServiceRegistry::areas(),
        ]));
    }

    public function area() {
        if (!$this->boot('view')) {
            return;
        }
        $actor = ServiceAccess::actor((int) $_SESSION['user_id']);
        $area = (string) $this->get('area', '');
        if (!isset(ServiceRegistry::areas()[$area]) || !ServiceAccess::canArea($actor, $area)) {
            $_SESSION['error'] = 'You cannot open that service area.';
            $this->redirect('services/dashboard');
            return;
        }
        $items = [];
        foreach (ServiceRegistry::areaResources()[$area] as $key) {
            $items[$key] = ServiceRegistry::resource($key);
        }
        return $this->view('services/area', $this->pack($actor, [
            'title' => ServiceRegistry::areas()[$area]['title'],
            'section' => $area,
            'area' => $area,
            'items' => $items,
        ]));
    }

    public function records() {
        $loaded = $this->loadResource(false);
        if (!$loaded) {
            return;
        }
        list($actor, $key, $area, $resource) = $loaded;
        $filters = $this->filters($area, $resource);
        $result = $this->model->search($resource, $filters, (int) $this->get('page', 1), 20);
        return $this->view('services/list', $this->pack($actor, [
            'title' => $resource['title'],
            'section' => $area,
            'area' => $area,
            'resource_key' => $key,
            'resource' => $resource,
            'result' => $result,
            'filters' => $filters,
            'labels' => $this->model->labelMaps(),
            'can_write' => ServiceAccess::canWrite($actor, $resource, $area),
        ]));
    }

    public function form() {
        $loaded = $this->loadResource(true);
        if (!$loaded) {
            return;
        }
        list($actor, $key, $area, $resource) = $loaded;
        $id = (int) $this->get('id', 0);
        $row = $id ? $this->model->findRow($resource['table'], $id) : null;
        if ($id && !$row) {
            $_SESSION['error'] = 'Record not found.';
            $this->redirect($this->listUrl($area, $key));
            return;
        }
        return $this->view('services/form', $this->pack($actor, [
            'title' => ($row ? 'Edit ' : 'New ') . $resource['title'],
            'section' => $area,
            'area' => $area,
            'resource_key' => $key,
            'resource' => $resource,
            'row' => $row,
            'options' => $this->options($area),
        ]));
    }

    public function save() {
        $loaded = $this->loadResource(true);
        if (!$loaded) {
            return;
        }
        list($actor, $key, $area, $resource) = $loaded;
        $id = (int) $this->post('id', 0);
        try {
            $data = $this->collect($resource, $area, $id);
            $saved = $this->model->save($resource, $data, $id, $actor);
            if ($key === 'cleaning_issues') {
                $this->model->adjustStock('issue', $saved);
            }
            if ($key === 'cleaning_purchases') {
                $this->model->adjustStock('purchase', $saved);
            }
            $_SESSION['message'] = 'Record saved.';
            $this->redirect('services/view?area=' . urlencode($area) . '&resource=' . urlencode($key) . '&id=' . $saved);
        } catch (Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('services/form?area=' . urlencode($area) . '&resource=' . urlencode($key) . ($id ? '&id=' . $id : ''));
        }
    }

    public function remove() {
        if (!$this->boot('delete')) {
            return;
        }
        $loaded = $this->loadResource(true);
        if (!$loaded) {
            return;
        }
        list($actor, $key, $area, $resource) = $loaded;
        $id = (int) $this->post('id', 0);
        $row = $id ? $this->model->findRow($resource['table'], $id) : null;
        if ($row) {
            $this->model->softDelete($resource['table'], $id, $actor, $resource);
            $_SESSION['message'] = 'Record archived.';
        }
        $this->redirect($this->listUrl($area, $key));
    }

    public function show() {
        $loaded = $this->loadResource(false);
        if (!$loaded) {
            return;
        }
        list($actor, $key, $area, $resource) = $loaded;
        $id = (int) $this->get('id', 0);
        $row = $id ? $this->model->findRow($resource['table'], $id) : null;
        if (!$row) {
            $_SESSION['error'] = 'Record not found.';
            $this->redirect($this->listUrl($area, $key));
            return;
        }
        $history = [];
        if ($key === 'contracts' && !empty($row['provider_id'])) {
            $history = $this->model->relatedContracts($row['provider_id'], $id);
        }
        return $this->view('services/show', $this->pack($actor, [
            'title' => $resource['title'],
            'section' => $area,
            'area' => $area,
            'resource_key' => $key,
            'resource' => $resource,
            'row' => $row,
            'documents' => $this->model->documents($key, $id),
            'history' => $history,
            'labels' => $this->model->labelMaps(),
            'can_write' => ServiceAccess::canWrite($actor, $resource, $area),
        ]));
    }

    public function upload() {
        $loaded = $this->loadResource(true);
        if (!$loaded) {
            return;
        }
        list($actor, $key, $area) = $loaded;
        $id = (int) $this->post('id', 0);
        try {
            $path = $this->storeFile('document', true);
            $doc = ServiceRegistry::resource('documents');
            $this->model->save($doc, [
                'category_id' => $this->model->categoryId($area) ?: null,
                'resource_key' => $key,
                'record_id' => $id,
                'title' => trim((string) $this->post('title', 'Attachment')) ?: 'Attachment',
                'file_path' => $path,
            ], 0, $actor);
            $_SESSION['message'] = 'Document uploaded.';
        } catch (Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
        }
        $this->redirect('services/view?area=' . urlencode($area) . '&resource=' . urlencode($key) . '&id=' . $id);
    }

    public function download() {
        if (!$this->boot('view')) {
            return;
        }
        $relative = str_replace('\\', '/', (string) $this->get('file', ''));
        $root = realpath(BASE_PATH . '/uploads/services');
        $full = realpath(BASE_PATH . '/' . $relative);
        if ($root === false || $full === false || strpos($full, $root) !== 0 || !is_file($full)) {
            $_SESSION['error'] = 'File not found.';
            $this->redirect('services/dashboard');
            return;
        }
        $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
        $types = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
        header('Content-Type: ' . (isset($types[$ext]) ? $types[$ext] : 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . basename($full) . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($full);
        exit;
    }

    public function export() {
        $loaded = $this->loadResource(false);
        if (!$loaded) {
            return;
        }
        list(, $key, $area, $resource) = $loaded;
        $filters = $this->filters($area, $resource);
        $special = $this->model->reportRows($key, $filters);
        if ($special !== null) {
            $rows = $special;
        } else {
            $rows = $this->model->search($resource, $filters, 1, 5000)['rows'];
        }
        $this->sendCsv($resource['title'], $rows);
    }

    public function reports() {
        if (!$this->boot('view')) {
            return;
        }
        $actor = ServiceAccess::actor((int) $_SESSION['user_id']);
        $key = (string) $this->get('report', 'contracts');
        $reports = ServiceRegistry::reports();
        if (!isset($reports[$key])) {
            $key = 'contracts';
        }
        $filters = [
            'date_from' => (string) $this->get('date_from', date('Y-m-01')),
            'date_to' => (string) $this->get('date_to', date('Y-m-d')),
            'q' => trim((string) $this->get('q', '')),
        ];
        $special = $this->model->reportRows($key, $filters);
        $resource = ServiceRegistry::resource($key);
        if ($special !== null) {
            $rows = $special;
            $title = $reports[$key];
        } elseif ($resource) {
            $rows = $this->model->search($resource, $filters, 1, 500)['rows'];
            $title = $reports[$key];
        } else {
            $rows = [];
            $title = $reports[$key];
        }
        if ($this->get('export') === 'csv') {
            $this->sendCsv($title, $rows);
            return;
        }
        $data = $this->pack($actor, [
            'title' => $title,
            'section' => 'reports',
            'report' => $key,
            'reports' => $reports,
            'rows' => $rows,
            'filters' => $filters,
            'labels' => $this->model->labelMaps(),
        ]);
        if ($this->get('print')) {
            $data['use_print_layout'] = true;
            return $this->view('services/print', $data);
        }
        return $this->view('services/reports', $data);
    }

    public function notifications() {
        if (!$this->boot('view')) {
            return;
        }
        $actor = ServiceAccess::actor((int) $_SESSION['user_id']);
        $this->model->remind();
        return $this->view('services/notifications', $this->pack($actor, [
            'title' => 'Service reminders',
            'section' => 'notifications',
            'notices' => $this->model->notifications(100),
        ]));
    }

    public function readNotice() {
        if (!$this->boot('view')) {
            return;
        }
        $this->model->markRead((int) $this->post('id', 0));
        $this->redirect('services/notifications');
    }

    private function boot($action) {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return false;
        }
        if (!empty($_SESSION['user_table']) && $_SESSION['user_table'] === 'student') {
            $_SESSION['error'] = 'Service management is available to staff only.';
            $this->redirect('student/dashboard');
            return false;
        }
        return $this->requireModule('services', $action);
    }

    private function loadResource($write) {
        if (!$this->boot($write ? 'edit' : 'view')) {
            return null;
        }
        $actor = ServiceAccess::actor((int) $_SESSION['user_id']);
        $key = (string) ($this->get('resource') ?: $this->post('resource'));
        $area = (string) ($this->get('area') ?: $this->post('area'));
        $resource = ServiceRegistry::resource($key);
        $areas = ServiceRegistry::areaResources();
        if (!$resource || !isset($areas[$area]) || !in_array($key, $areas[$area], true) || !ServiceAccess::canArea($actor, $area)) {
            $_SESSION['error'] = 'That record is not available.';
            $this->redirect('services/dashboard');
            return null;
        }
        if ($write && !ServiceAccess::canWrite($actor, $resource, $area)) {
            $_SESSION['error'] = 'You cannot change this record.';
            $this->redirect($this->listUrl($area, $key));
            return null;
        }
        return [$actor, $key, $area, $resource];
    }

    private function filters($area, array $resource) {
        $filters = [
            'q' => trim((string) $this->get('q', '')),
            'date_from' => (string) $this->get('date_from', ''),
            'date_to' => (string) $this->get('date_to', ''),
            'status' => trim((string) $this->get('status', '')),
        ];
        if ($area !== 'contracts' && $this->model->categoryId($area)) {
            $filters['category_id'] = $this->model->categoryId($area);
        }
        return $filters;
    }

    private function collect(array $resource, $area, $id) {
        $data = [];
        $existing = $id ? $this->model->findRow($resource['table'], $id) : null;
        foreach ($resource['fields'] as $name => $field) {
            if ($field['type'] === 'file') {
                if (!empty($_FILES[$name]['name'])) {
                    $data[$name] = $this->storeFile($name, !empty($field['required']) && !$existing);
                } elseif (!empty($field['required']) && !$existing) {
                    throw new InvalidArgumentException($field['label'] . ' is required.');
                }
                continue;
            }
            $value = trim((string) $this->post($name, ''));
            if ($value === '') {
                if (!empty($field['required'])) {
                    throw new InvalidArgumentException($field['label'] . ' is required.');
                }
                $data[$name] = null;
                continue;
            }
            if ($field['type'] === 'number') {
                if (!is_numeric($value)) {
                    throw new InvalidArgumentException($field['label'] . ' must be a number.');
                }
                $data[$name] = $value;
            } elseif ($field['type'] === 'date') {
                $data[$name] = $this->dateValue($value, $field['label']);
            } elseif ($field['type'] === 'datetime') {
                $data[$name] = str_replace('T', ' ', $value);
            } elseif ($field['type'] === 'month') {
                if (!preg_match('/^\d{4}-\d{2}$/', $value)) {
                    throw new InvalidArgumentException('Choose a valid month.');
                }
                $data[$name] = $value;
            } elseif ($field['type'] === 'select' && $field['options'] && !isset($field['options'][$value])) {
                throw new InvalidArgumentException('Choose a valid ' . $field['label'] . '.');
            } else {
                $data[$name] = $value;
            }
        }
        if ($area !== 'contracts') {
            $categoryId = $this->model->categoryId($area);
            if ($categoryId) {
                $data['category_id'] = $categoryId;
            }
        }
        if (isset($data['finger_machine_no']) && $data['finger_machine_no'] !== null && $data['finger_machine_no'] !== '') {
            $data['finger_machine_no'] = substr(trim((string) $data['finger_machine_no']), 0, 50);
            if ($this->model->fingerNumberTaken($data['finger_machine_no'], $id)) {
                throw new InvalidArgumentException('That finger number is already used.');
            }
        }
        if ($resource['table'] === 'cleaning_material_purchases') {
            $qty = (float) ($data['quantity'] ?? 0);
            $price = (float) ($data['unit_price'] ?? 0);
            if (empty($data['total_amount'])) {
                $data['total_amount'] = number_format($qty * $price, 2, '.', '');
            }
        }
        return $data;
    }

    private function dateValue($value, $label) {
        $dt = DateTime::createFromFormat('Y-m-d', $value);
        if (!$dt || $dt->format('Y-m-d') !== $value) {
            throw new InvalidArgumentException($label . ' must be a valid date.');
        }
        return $value;
    }

    private function storeFile($field, $required) {
        if (empty($_FILES[$field]['name'])) {
            if ($required) {
                throw new InvalidArgumentException('A file is required.');
            }
            return null;
        }
        $file = $_FILES[$field];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('The file could not be uploaded.');
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new InvalidArgumentException('Files must be 5 MB or smaller.');
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'webp'], true)) {
            throw new InvalidArgumentException('Upload a PDF or image file.');
        }
        $dir = BASE_PATH . '/uploads/services/' . date('Y/m');
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            throw new RuntimeException('The upload folder is not available.');
        }
        $name = bin2hex(random_bytes(16)) . '.' . $ext;
        $dest = $dir . '/' . $name;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new RuntimeException('The file could not be stored.');
        }
        return 'uploads/services/' . date('Y/m') . '/' . $name;
    }

    private function options($area) {
        $categoryId = $area === 'contracts' ? 0 : $this->model->categoryId($area);
        return [
            'categories' => $this->model->categories(),
            'staff' => $this->model->options('staff', $categoryId),
            'providers' => $this->model->options('provider', $categoryId),
            'units' => $this->model->options('unit', 0),
            'inventory' => $this->model->options('inventory', 0),
            'contracts' => $this->model->options('contract', $categoryId),
        ];
    }

    private function pack(array $actor, array $data) {
        $data['page'] = 'services';
        $data['actor'] = $actor;
        $data['areas'] = isset($data['areas']) ? $data['areas'] : ServiceRegistry::areas();
        $data['message'] = $_SESSION['message'] ?? null;
        $data['error'] = $_SESSION['error'] ?? null;
        unset($_SESSION['message'], $_SESSION['error']);
        return $data;
    }

    private function listUrl($area, $key) {
        return 'services/records?area=' . urlencode($area) . '&resource=' . urlencode($key);
    }

    private function sendCsv($title, array $rows) {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9_-]+/', '-', $title) . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        if (!$rows) {
            fputcsv($out, ['No records']);
            fclose($out);
            exit;
        }
        fputcsv($out, array_keys($rows[0]));
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }
}
