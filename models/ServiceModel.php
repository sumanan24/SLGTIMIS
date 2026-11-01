<?php
class ServiceModel extends Model {
    protected $table = 'service_categories';
    private static $schemaReady = false;

    public function __construct() {
        parent::__construct();
        require_once BASE_PATH . '/helpers/ServiceRegistry.php';
        $this->ensureSchema();
    }

    protected function getPrimaryKey() {
        return 'id';
    }

    public function ensureSchema() {
        if (self::$schemaReady) {
            return;
        }
        $sqlFile = BASE_PATH . '/database/services.sql';
        if (is_file($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            if ($sql !== false) {
                $chunks = array_filter(array_map('trim', preg_split('/;\s*$/m', $sql)));
                foreach ($chunks as $chunk) {
                    $chunk = trim(preg_replace('/^--.*$/m', '', $chunk));
                    if ($chunk === '') {
                        continue;
                    }
                    if (!$this->db->query($chunk)) {
                        throw new RuntimeException('Service schema could not be prepared.');
                    }
                }
            }
        }
        $this->ensureFingerColumn();
        self::$schemaReady = true;
    }

    public function fingerNumberTaken($number, $exceptId = 0) {
        $number = trim((string) $number);
        if ($number === '') {
            return false;
        }
        $exceptId = (int) $exceptId;
        $stmt = $this->db->prepare('SELECT `id` FROM `service_staff` WHERE `finger_machine_no` = ? AND `id` <> ? AND `deleted_at` IS NULL LIMIT 1');
        $stmt->bind_param('si', $number, $exceptId);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            return true;
        }
        $staff = $this->db->prepare('SELECT `staff_id` FROM `staff` WHERE `finger_machine_no` = ? LIMIT 1');
        if ($staff) {
            $staff->bind_param('s', $number);
            $staff->execute();
            if ($staff->get_result()->fetch_assoc()) {
                return true;
            }
        }
        return false;
    }

    private function ensureFingerColumn() {
        $check = $this->db->query("SHOW COLUMNS FROM `service_staff` LIKE 'finger_machine_no'");
        if ($check && $check->num_rows === 0) {
            $this->db->query('ALTER TABLE `service_staff` ADD COLUMN `finger_machine_no` VARCHAR(50) NULL DEFAULT NULL AFTER `phone`');
        }
        $idx = $this->db->query("SHOW INDEX FROM `service_staff` WHERE Key_name = 'uq_service_staff_finger'");
        if ($idx && $idx->num_rows === 0) {
            $this->db->query('ALTER TABLE `service_staff` ADD UNIQUE KEY `uq_service_staff_finger` (`finger_machine_no`)');
        }
    }

    public function categories() {
        $rows = [];
        $result = $this->db->query('SELECT * FROM `service_categories` ORDER BY `sort_order`, `id`');
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    public function categoryId($slug) {
        $stmt = $this->db->prepare('SELECT `id` FROM `service_categories` WHERE `slug` = ? LIMIT 1');
        $stmt->bind_param('s', $slug);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ? (int) $row['id'] : 0;
    }

    public function findRow($table, $id) {
        $this->assertTable($table);
        $stmt = $this->db->prepare('SELECT * FROM `' . $table . '` WHERE `id` = ? AND `deleted_at` IS NULL LIMIT 1');
        $id = (int) $id;
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: null;
    }

    public function search(array $resource, array $filters, $page, $perPage) {
        $table = $resource['table'];
        $this->assertTable($table);
        $where = ['`deleted_at` IS NULL'];
        $types = '';
        $params = [];
        if (!empty($filters['category_id']) && $this->tableHas($table, 'category_id')) {
            $where[] = '`category_id` = ?';
            $types .= 'i';
            $params[] = (int) $filters['category_id'];
        }
        if (!empty($filters['q'])) {
            $parts = [];
            foreach ($resource['search'] as $col) {
                $parts[] = '`' . $col . '` LIKE ?';
                $types .= 's';
                $params[] = '%' . $filters['q'] . '%';
            }
            if ($parts) {
                $where[] = '(' . implode(' OR ', $parts) . ')';
            }
        }
        $dateCol = $resource['date'];
        if ($dateCol) {
            if (!empty($filters['date_from'])) {
                $where[] = '`' . $dateCol . '` >= ?';
                $types .= 's';
                $params[] = $filters['date_from'];
            }
            if (!empty($filters['date_to'])) {
                $where[] = '`' . $dateCol . '` <= ?';
                $types .= 's';
                $params[] = $filters['date_to'] . (strpos($dateCol, '_at') !== false ? ' 23:59:59' : '');
            }
        }
        if (!empty($filters['status']) && $this->tableHas($table, 'status')) {
            $where[] = '`status` = ?';
            $types .= 's';
            $params[] = $filters['status'];
        }
        $sqlWhere = implode(' AND ', $where);
        $count = $this->scalar('SELECT COUNT(*) AS c FROM `' . $table . '` WHERE ' . $sqlWhere, $types, $params);
        $page = max(1, (int) $page);
        $perPage = max(1, (int) $perPage);
        $offset = ($page - 1) * $perPage;
        $sql = 'SELECT * FROM `' . $table . '` WHERE ' . $sqlWhere . ' ORDER BY `id` DESC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset;
        $rows = $this->rows($sql, $types, $params);
        return ['rows' => $rows, 'total' => (int) $count, 'page' => $page, 'per_page' => $perPage];
    }

    public function save(array $resource, array $data, $id, $actor) {
        $table = $resource['table'];
        $this->assertTable($table);
        $allowed = array_keys($resource['fields']);
        $clean = [];
        foreach ($allowed as $col) {
            if (!array_key_exists($col, $data)) {
                continue;
            }
            $clean[$col] = $data[$col];
        }
        if (array_key_exists('category_id', $data) && $this->tableHas($table, 'category_id')) {
            $clean['category_id'] = (int) $data['category_id'];
        }
        $now = date('Y-m-d H:i:s');
        if ($id) {
            $clean['updated_at'] = $now;
            $this->updateRow($table, $clean, (int) $id);
            $this->audit($actor, 'update', $resource, (int) $id, 'Record updated');
            return (int) $id;
        }
        if (!empty($resource['code']) && empty($clean[$resource['code']])) {
            $clean[$resource['code']] = $this->nextCode($resource['prefix']);
        }
        if ($this->tableHas($table, 'created_by')) {
            $clean['created_by'] = $actor['staff_id'];
        }
        if ($table === 'service_documents') {
            $clean['uploaded_by'] = $actor['staff_id'];
        }
        $newId = $this->insertRow($table, $clean);
        $this->audit($actor, 'create', $resource, (int) $newId, 'Record created');
        return (int) $newId;
    }

    public function softDelete($table, $id, array $actor, array $resource) {
        $this->assertTable($table);
        $now = date('Y-m-d H:i:s');
        $stmt = $this->db->prepare('UPDATE `' . $table . '` SET `deleted_at` = ? WHERE `id` = ? AND `deleted_at` IS NULL');
        $id = (int) $id;
        $stmt->bind_param('si', $now, $id);
        $stmt->execute();
        $this->audit($actor, 'delete', $resource, $id, 'Record archived');
    }

    public function options($kind, $categoryId) {
        if ($kind === 'staff') {
            $sql = 'SELECT `id`, `full_name`, `staff_code` FROM `service_staff` WHERE `deleted_at` IS NULL';
            $types = '';
            $params = [];
            if ($categoryId) {
                $sql .= ' AND `category_id` = ?';
                $types = 'i';
                $params[] = (int) $categoryId;
            }
            $sql .= ' ORDER BY `full_name` LIMIT 500';
            return $this->rows($sql, $types, $params);
        }
        if ($kind === 'provider') {
            $sql = 'SELECT `id`, `provider_name`, `provider_code` FROM `service_providers` WHERE `deleted_at` IS NULL';
            $types = '';
            $params = [];
            if ($categoryId) {
                $sql .= ' AND `category_id` = ?';
                $types = 'i';
                $params[] = (int) $categoryId;
            }
            $sql .= ' ORDER BY `provider_name` LIMIT 500';
            return $this->rows($sql, $types, $params);
        }
        if ($kind === 'unit') {
            return $this->rows('SELECT `id`, `unit_code`, `identification_no`, `room` FROM `ac_units` WHERE `deleted_at` IS NULL ORDER BY `unit_code` LIMIT 500', '', []);
        }
        if ($kind === 'inventory') {
            return $this->rows('SELECT `id`, `item_code`, `item_name` FROM `cleaning_inventory` WHERE `deleted_at` IS NULL ORDER BY `item_name` LIMIT 500', '', []);
        }
        if ($kind === 'contract') {
            $sql = 'SELECT `id`, `contract_code`, `reference_no` FROM `service_contracts` WHERE `deleted_at` IS NULL';
            $types = '';
            $params = [];
            if ($categoryId) {
                $sql .= ' AND `category_id` = ?';
                $types = 'i';
                $params[] = (int) $categoryId;
            }
            $sql .= ' ORDER BY `id` DESC LIMIT 300';
            return $this->rows($sql, $types, $params);
        }
        return [];
    }

    public function documents($resourceKey, $recordId) {
        return $this->rows(
            'SELECT * FROM `service_documents` WHERE `deleted_at` IS NULL AND `resource_key` = ? AND `record_id` = ? ORDER BY `id` DESC',
            'si',
            [$resourceKey, (int) $recordId]
        );
    }

    public function relatedContracts($providerId, $exceptId) {
        return $this->rows(
            'SELECT `id`, `contract_code`, `reference_no`, `start_date`, `expiry_date`, `status` FROM `service_contracts` WHERE `deleted_at` IS NULL AND `provider_id` = ? AND `id` <> ? ORDER BY `expiry_date` DESC',
            'ii',
            [(int) $providerId, (int) $exceptId]
        );
    }

    public function adjustStock($kind, $id) {
        if ($kind === 'issue') {
            $row = $this->findRow('cleaning_item_issues', $id);
            if (!$row) {
                return;
            }
            $delta = (float) $row['quantity'] - (float) $row['applied_qty'];
            if ($delta != 0) {
                $this->changeStock((int) $row['inventory_id'], 0 - $delta);
                $applied = (float) $row['quantity'];
                $stmt = $this->db->prepare('UPDATE `cleaning_item_issues` SET `applied_qty` = ? WHERE `id` = ?');
                $stmt->bind_param('di', $applied, $id);
                $stmt->execute();
            }
            if (!empty($row['returned_on']) && (int) $row['restored'] === 0 && $row['condition_note'] !== 'Missing' && $row['condition_note'] !== 'Damaged') {
                $this->changeStock((int) $row['inventory_id'], (float) $row['quantity']);
                $stmt = $this->db->prepare('UPDATE `cleaning_item_issues` SET `restored` = 1 WHERE `id` = ?');
                $stmt->bind_param('i', $id);
                $stmt->execute();
            }
        }
        if ($kind === 'purchase') {
            $row = $this->findRow('cleaning_material_purchases', $id);
            if (!$row || empty($row['inventory_id'])) {
                return;
            }
            $delta = (float) $row['quantity'] - (float) $row['applied_qty'];
            if ($delta != 0) {
                $this->changeStock((int) $row['inventory_id'], $delta);
                $applied = (float) $row['quantity'];
                $stmt = $this->db->prepare('UPDATE `cleaning_material_purchases` SET `applied_qty` = ? WHERE `id` = ?');
                $stmt->bind_param('di', $applied, $id);
                $stmt->execute();
            }
        }
    }

    public function dashboard() {
        return [
            'categories' => (int) $this->scalar('SELECT COUNT(*) AS c FROM `service_categories`', '', []),
            'staff' => (int) $this->scalar('SELECT COUNT(*) AS c FROM `service_staff` WHERE `deleted_at` IS NULL', '', []),
            'active_contracts' => (int) $this->scalar("SELECT COUNT(*) AS c FROM `service_contracts` WHERE `deleted_at` IS NULL AND `status` = 'Active'", '', []),
            'expiring_90' => (int) $this->scalar("SELECT COUNT(*) AS c FROM `service_contracts` WHERE `deleted_at` IS NULL AND `status` IN ('Active','Pending Renewal') AND `expiry_date` IS NOT NULL AND `expiry_date` BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)", '', []),
            'expired' => (int) $this->scalar("SELECT COUNT(*) AS c FROM `service_contracts` WHERE `deleted_at` IS NULL AND (`status` = 'Expired' OR (`expiry_date` IS NOT NULL AND `expiry_date` < CURDATE() AND `status` <> 'Terminated'))", '', []),
            'pending_invoices' => (int) $this->scalar("SELECT COUNT(*) AS c FROM `service_invoices` WHERE `deleted_at` IS NULL AND `status` = 'Pending'", '', []),
            'month_spend' => (float) $this->scalar('SELECT COALESCE(SUM(`amount`),0) AS c FROM `service_payments` WHERE `deleted_at` IS NULL AND `paid_on` >= DATE_FORMAT(CURDATE(), \'%Y-%m-01\')', '', []),
            'pending_complaints' => (int) $this->scalar("SELECT (SELECT COUNT(*) FROM `security_incidents` WHERE `deleted_at` IS NULL AND `investigation_status` <> 'Closed') + (SELECT COUNT(*) FROM `ac_breakdown_complaints` WHERE `deleted_at` IS NULL AND `progress` <> 'Completed') + (SELECT COUNT(*) FROM `canteen_complaints` WHERE `deleted_at` IS NULL AND `status` NOT IN ('Resolved','Closed')) AS c", '', []),
            'ongoing_maintenance' => (int) $this->scalar("SELECT COUNT(*) AS c FROM `ac_breakdown_complaints` WHERE `deleted_at` IS NULL AND `progress` IN ('Open','Assigned','In progress')", '', []),
            'stock_alerts' => (int) $this->scalar('SELECT COUNT(*) AS c FROM `cleaning_inventory` WHERE `deleted_at` IS NULL AND `quantity_on_hand` <= `min_level`', '', []),
            'upcoming_maintenance' => (int) $this->scalar('SELECT COUNT(*) AS c FROM `ac_maintenance_records` WHERE `deleted_at` IS NULL AND `next_date` BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)', '', []),
        ];
    }

    public function remind() {
        $contracts = $this->rows("SELECT * FROM `service_contracts` WHERE `deleted_at` IS NULL AND `expiry_date` IS NOT NULL AND `status` IN ('Active','Pending Renewal') AND `expiry_date` <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)", '', []);
        foreach ($contracts as $contract) {
            $days = (int) floor((strtotime($contract['expiry_date']) - strtotime(date('Y-m-d'))) / 86400);
            $milestone = $days < 0 ? 'expired' : ($days <= 0 ? '0' : ($days <= 30 ? '30' : ($days <= 60 ? '60' : '90')));
            $title = $milestone === 'expired' || $milestone === '0' ? 'Agreement expired' : ('Agreement expires in ' . $milestone . ' days');
            $message = ($contract['contract_code'] ?: 'Contract') . ' expires on ' . $contract['expiry_date'];
            $stmt = $this->db->prepare('INSERT IGNORE INTO `service_notifications` (`category_id`, `contract_id`, `inventory_id`, `milestone`, `title`, `message`) VALUES (?, ?, 0, ?, ?, ?)');
            $cat = (int) $contract['category_id'];
            $cid = (int) $contract['id'];
            $stmt->bind_param('iisss', $cat, $cid, $milestone, $title, $message);
            $stmt->execute();
            if ($days < 0) {
                $expired = 'Expired';
                $upd = $this->db->prepare("UPDATE `service_contracts` SET `status` = ? WHERE `id` = ? AND `status` = 'Active'");
                $upd->bind_param('si', $expired, $cid);
                $upd->execute();
            }
        }
        $low = $this->rows('SELECT * FROM `cleaning_inventory` WHERE `deleted_at` IS NULL AND `quantity_on_hand` <= `min_level`', '', []);
        $month = date('Y-m');
        foreach ($low as $item) {
            $title = 'Low cleaning stock';
            $message = $item['item_name'] . ' is at ' . $item['quantity_on_hand'] . ' (minimum ' . $item['min_level'] . ')';
            $stmt = $this->db->prepare('INSERT IGNORE INTO `service_notifications` (`category_id`, `contract_id`, `inventory_id`, `milestone`, `title`, `message`) VALUES (NULL, 0, ?, ?, ?, ?)');
            $iid = (int) $item['id'];
            $stmt->bind_param('isss', $iid, $month, $title, $message);
            $stmt->execute();
        }
    }

    public function notifications($limit) {
        return $this->rows('SELECT * FROM `service_notifications` ORDER BY `id` DESC LIMIT ' . (int) $limit, '', []);
    }

    public function unreadCount() {
        return (int) $this->scalar('SELECT COUNT(*) AS c FROM `service_notifications` WHERE `is_read` = 0', '', []);
    }

    public function markRead($id) {
        $stmt = $this->db->prepare('UPDATE `service_notifications` SET `is_read` = 1 WHERE `id` = ?');
        $id = (int) $id;
        $stmt->bind_param('i', $id);
        $stmt->execute();
    }

    public function reportRows($key, array $filters) {
        $from = !empty($filters['date_from']) ? $filters['date_from'] : date('Y-m-01');
        $to = !empty($filters['date_to']) ? $filters['date_to'] : date('Y-m-d');
        if ($key === 'security_attendance_summary') {
            return $this->rows(
                "SELECT s.staff_code, s.full_name, SUM(a.status = 'Present') AS present_days, SUM(a.status = 'Absent') AS absent_days, SUM(a.status = 'Leave') AS leave_days, SUM(a.status = 'Off') AS off_days FROM `security_attendance` a JOIN `service_staff` s ON s.id = a.staff_id WHERE a.deleted_at IS NULL AND a.attendance_date BETWEEN ? AND ? GROUP BY s.id, s.staff_code, s.full_name ORDER BY s.full_name",
                'ss',
                [$from, $to]
            );
        }
        if ($key === 'cleaning_attendance_summary') {
            return $this->rows(
                "SELECT s.staff_code, s.full_name, SUM(a.status = 'Present') AS present_days, SUM(a.status = 'Absent') AS absent_days, SUM(a.status = 'Leave') AS leave_days FROM `cleaning_attendance` a JOIN `service_staff` s ON s.id = a.staff_id WHERE a.deleted_at IS NULL AND a.attendance_date BETWEEN ? AND ? GROUP BY s.id, s.staff_code, s.full_name ORDER BY s.full_name",
                'ss',
                [$from, $to]
            );
        }
        if ($key === 'cleaning_expenditure_summary') {
            return $this->rows(
                'SELECT `category`, SUM(`quantity`) AS quantity, SUM(`total_amount`) AS total_amount FROM `cleaning_material_purchases` WHERE `deleted_at` IS NULL AND `purchased_on` BETWEEN ? AND ? GROUP BY `category` ORDER BY `category`',
                'ss',
                [$from, $to]
            );
        }
        if ($key === 'cleaning_stock') {
            return $this->rows('SELECT `item_code`, `item_name`, `category`, `quantity_on_hand`, `min_level`, CASE WHEN `quantity_on_hand` <= `min_level` THEN \'Low\' ELSE \'OK\' END AS stock_status FROM `cleaning_inventory` WHERE `deleted_at` IS NULL ORDER BY `item_name`', '', []);
        }
        if ($key === 'evaluations') {
            return $this->rows(
                'SELECT e.eval_code, c.name AS category, s.full_name, e.period_month, e.rating, e.remarks FROM `service_performance_evaluations` e LEFT JOIN `service_categories` c ON c.id = e.category_id LEFT JOIN `service_staff` s ON s.id = e.staff_id WHERE e.deleted_at IS NULL AND e.created_at BETWEEN ? AND ? ORDER BY e.id DESC',
                'ss',
                [$from . ' 00:00:00', $to . ' 23:59:59']
            );
        }
        return null;
    }

    public function labelMaps() {
        $cats = [];
        foreach ($this->categories() as $cat) {
            $cats[$cat['id']] = $cat['name'];
        }
        $staff = [];
        foreach ($this->rows('SELECT `id`, `full_name`, `staff_code` FROM `service_staff` WHERE `deleted_at` IS NULL', '', []) as $row) {
            $staff[$row['id']] = $row['full_name'];
        }
        $providers = [];
        foreach ($this->rows('SELECT `id`, `provider_name` FROM `service_providers` WHERE `deleted_at` IS NULL', '', []) as $row) {
            $providers[$row['id']] = $row['provider_name'];
        }
        $units = [];
        foreach ($this->rows('SELECT `id`, `unit_code` FROM `ac_units` WHERE `deleted_at` IS NULL', '', []) as $row) {
            $units[$row['id']] = $row['unit_code'];
        }
        $items = [];
        foreach ($this->rows('SELECT `id`, `item_name` FROM `cleaning_inventory` WHERE `deleted_at` IS NULL', '', []) as $row) {
            $items[$row['id']] = $row['item_name'];
        }
        return ['category_id' => $cats, 'staff_id' => $staff, 'provider_id' => $providers, 'unit_id' => $units, 'inventory_id' => $items, 'contract_id' => []];
    }

    private function changeStock($inventoryId, $delta) {
        $stmt = $this->db->prepare('UPDATE `cleaning_inventory` SET `quantity_on_hand` = `quantity_on_hand` + ? WHERE `id` = ?');
        $stmt->bind_param('di', $delta, $inventoryId);
        $stmt->execute();
    }

    private function nextCode($prefix) {
        $year = date('Y');
        $lock = 'slgti_svc_' . $prefix;
        $this->db->query("SELECT GET_LOCK('" . $this->db->escape($lock) . "', 5)");
        try {
            $like = $prefix . '/' . $year . '/%';
            $max = 0;
            $tables = [
                'service_providers' => 'provider_code',
                'service_staff' => 'staff_code',
                'service_contracts' => 'contract_code',
                'service_payments' => 'payment_code',
                'service_performance_evaluations' => 'eval_code',
                'security_incidents' => 'incident_code',
                'security_gate_passes' => 'pass_code',
                'ac_units' => 'unit_code',
                'ac_breakdown_complaints' => 'complaint_code',
                'cleaning_inventory' => 'item_code',
                'canteen_complaints' => 'complaint_code',
            ];
            foreach ($tables as $table => $col) {
                $stmt = $this->db->prepare('SELECT `' . $col . '` AS code FROM `' . $table . '` WHERE `' . $col . '` LIKE ?');
                $stmt->bind_param('s', $like);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $parts = explode('/', (string) $row['code']);
                    $seq = (int) end($parts);
                    if ($seq > $max) {
                        $max = $seq;
                    }
                }
            }
            $invoiceLike = $like;
            $stmt = $this->db->prepare('SELECT `invoice_no` AS code FROM `service_invoices` WHERE `invoice_no` LIKE ?');
            $stmt->bind_param('s', $invoiceLike);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $parts = explode('/', (string) $row['code']);
                $seq = (int) end($parts);
                if ($seq > $max) {
                    $max = $seq;
                }
            }
            return sprintf('%s/%s/%04d', $prefix, $year, $max + 1);
        } finally {
            $this->db->query("SELECT RELEASE_LOCK('" . $this->db->escape($lock) . "')");
        }
    }

    private function insertRow($table, array $data) {
        $cols = array_keys($data);
        $sql = 'INSERT INTO `' . $table . '` (`' . implode('`,`', $cols) . '`) VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')';
        $stmt = $this->db->prepare($sql);
        $this->bind($stmt, $data);
        if (!$stmt->execute()) {
            throw new RuntimeException('Could not save the record.');
        }
        return (int) $this->db->lastInsertId();
    }

    private function updateRow($table, array $data, $id) {
        $sets = [];
        foreach (array_keys($data) as $col) {
            $sets[] = '`' . $col . '` = ?';
        }
        $sql = 'UPDATE `' . $table . '` SET ' . implode(', ', $sets) . ' WHERE `id` = ? AND `deleted_at` IS NULL';
        $data['id'] = $id;
        $stmt = $this->db->prepare($sql);
        $this->bind($stmt, $data);
        if (!$stmt->execute()) {
            throw new RuntimeException('Could not update the record.');
        }
    }

    private function bind($stmt, array $data) {
        $types = '';
        $values = [];
        foreach ($data as $value) {
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
            $values[] = $value;
        }
        $stmt->bind_param($types, ...$values);
    }

    private function rows($sql, $types, array $params) {
        $stmt = $this->db->prepare($sql);
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }

    private function scalar($sql, $types, array $params) {
        $rows = $this->rows($sql, $types, $params);
        if (!$rows) {
            return 0;
        }
        $row = reset($rows);
        return reset($row);
    }

    private function audit(array $actor, $action, array $resource, $id, $summary) {
        $key = '';
        foreach (ServiceRegistry::resources() as $name => $item) {
            if ($item['table'] === $resource['table'] && $item['title'] === $resource['title']) {
                $key = $name;
                break;
            }
        }
        $stmt = $this->db->prepare('INSERT INTO `service_audit` (`user_id`, `staff_id`, `action`, `resource_key`, `record_id`, `summary`) VALUES (?, ?, ?, ?, ?, ?)');
        $uid = (int) $actor['user_id'];
        $staff = (string) $actor['staff_id'];
        $stmt->bind_param('isssis', $uid, $staff, $action, $key, $id, $summary);
        $stmt->execute();
    }

    private function tableHas($table, $column) {
        $map = [
            'category_id' => ['service_providers', 'service_staff', 'service_contracts', 'service_invoices', 'service_payments', 'service_documents', 'service_performance_evaluations'],
            'created_by' => ['service_providers', 'service_staff', 'service_contracts', 'service_invoices', 'service_payments', 'service_performance_evaluations', 'security_attendance', 'security_duty_rosters', 'security_incidents', 'security_gate_passes', 'security_irb_records', 'security_item_issues', 'ac_units', 'ac_maintenance_records', 'ac_breakdown_complaints', 'cleaning_attendance', 'cleaning_duty_allocations', 'cleaning_inventory', 'cleaning_item_issues', 'cleaning_material_purchases', 'cleaning_inspections', 'canteen_inspections', 'canteen_complaints'],
            'status' => ['service_providers', 'service_contracts', 'service_invoices', 'security_attendance', 'security_gate_passes', 'security_irb_records', 'ac_units', 'cleaning_attendance', 'canteen_complaints'],
        ];
        return isset($map[$column]) && in_array($table, $map[$column], true);
    }

    private function assertTable($table) {
        foreach (ServiceRegistry::resources() as $resource) {
            if ($resource['table'] === $table) {
                return;
            }
        }
        throw new InvalidArgumentException('Unknown record type.');
    }
}
