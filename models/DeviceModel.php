<?php
/**
 * Device / Laptop asset management
 */
class DeviceModel extends Model {
    protected $table = 'devices';
    private static $tablesEnsured = false;

    public const STATUS_AVAILABLE = 'available';
    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_UNDER_MAINTENANCE = 'under_maintenance';
    public const STATUS_DAMAGED = 'damaged';
    public const STATUS_LOST = 'lost';
    public const STATUS_RETURNED = 'returned';
    public const STATUS_RETIRED = 'retired';
    public const STATUS_DISPOSED = 'disposed';

    public const STATUSES = [
        self::STATUS_AVAILABLE,
        self::STATUS_ASSIGNED,
        self::STATUS_UNDER_MAINTENANCE,
        self::STATUS_DAMAGED,
        self::STATUS_LOST,
        self::STATUS_RETURNED,
        self::STATUS_RETIRED,
        self::STATUS_DISPOSED,
    ];

    public const DEVICE_TYPES = ['Laptop', 'Desktop', 'Tablet', 'Other'];

    public const ASSET_CATEGORIES = [
        'Laptop', 'Desktop', 'Tablet', 'Printer', 'Projector',
        'Furniture', 'Lab Equipment', 'Tools', 'Vehicle', 'Other',
    ];

    public const SIMPLE_CONDITIONS = ['Good', 'Needs Repair', 'Damaged', 'Missing', 'Unserviceable'];

    public const DISPOSE_REASONS = ['Beyond Repair', 'Old / Obsolete', 'Damaged', 'Unserviceable', 'Other'];

    public const CONDITION_VALUES = ['Good', 'Fair', 'Damaged'];

    public const ACCESSORY_TYPES = [
        'Laptop', 'Charger', 'Carry Bag', 'Wireless Mouse', 'Keyboard',
        'Docking Station', 'HDMI Cable', 'Adapter', 'Other',
    ];

    protected function getPrimaryKey() {
        return 'id';
    }

    public function __construct() {
        parent::__construct();
        $this->ensureTables();
    }

    public function ensureTables(): void {
        if (self::$tablesEnsured) {
            return;
        }
        $sqlFile = BASE_PATH . '/database/device_assets.sql';
        if (!is_readable($sqlFile)) {
            self::$tablesEnsured = true;
            return;
        }
        $sql = file_get_contents($sqlFile);
        if ($sql === false) {
            self::$tablesEnsured = true;
            return;
        }
        try {
            $conn = $this->db->getConnection();
            $conn->multi_query($sql);
            while ($conn->more_results() && $conn->next_result()) {
                /* flush */
            }
        } catch (Throwable $e) {
            error_log('DeviceModel::ensureTables: ' . $e->getMessage());
        }
        $this->ensureAssetColumns();
        self::$tablesEnsured = true;
    }

    /**
     * Department asset fields and request tables. Existing device rows are kept.
     */
    public function ensureAssetColumns(): void {
        $columns = [
            'devices' => [
                'asset_name' => "`asset_name` VARCHAR(200) NULL",
                'category' => "`category` VARCHAR(80) NULL",
                'department_id' => "`department_id` VARCHAR(20) NULL",
                'room_location' => "`room_location` VARCHAR(150) NULL",
                'quantity' => "`quantity` INT NOT NULL DEFAULT 1",
                'asset_condition' => "`asset_condition` VARCHAR(40) NULL DEFAULT 'Good'",
                'photo_path' => "`photo_path` VARCHAR(255) NULL",
                'assigned_student_id' => "`assigned_student_id` VARCHAR(20) NULL",
            ],
            'device_assignments' => [
                'assignee_type' => "`assignee_type` VARCHAR(20) NOT NULL DEFAULT 'staff'",
                'student_id' => "`student_id` VARCHAR(20) NULL",
                'expected_return_date' => "`expected_return_date` DATE NULL",
            ],
            'device_condition_history' => [
                'asset_condition' => "`asset_condition` VARCHAR(40) NULL",
                'photo_path' => "`photo_path` VARCHAR(255) NULL",
            ],
        ];
        foreach ($columns as $table => $defs) {
            foreach ($defs as $column => $definition) {
                $safeTable = preg_replace('/[^a-z_]/', '', $table);
                $safeColumn = preg_replace('/[^a-z_]/', '', $column);
                $exists = $this->db->query("SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'");
                if ($exists && $exists->num_rows > 0) {
                    continue;
                }
                $this->db->query("ALTER TABLE `{$safeTable}` ADD COLUMN {$definition}");
            }
        }
        $this->db->query("ALTER TABLE `device_assignments` MODIFY `employee_id` VARCHAR(20) NULL");
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS `device_transfers` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `device_id` INT UNSIGNED NOT NULL,
                `from_department_id` VARCHAR(20) DEFAULT NULL,
                `to_department_id` VARCHAR(20) NOT NULL,
                `to_location` VARCHAR(150) DEFAULT NULL,
                `reason` VARCHAR(255) DEFAULT NULL,
                `status` VARCHAR(30) NOT NULL DEFAULT 'waiting',
                `requested_by` INT DEFAULT NULL,
                `approved_by` INT DEFAULT NULL,
                `confirmed_by` INT DEFAULT NULL,
                `decision_note` VARCHAR(255) DEFAULT NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_device_transfers_device` (`device_id`),
                KEY `idx_device_transfers_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS `device_disposals` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `device_id` INT UNSIGNED NOT NULL,
                `reason` VARCHAR(80) NOT NULL,
                `remarks` TEXT DEFAULT NULL,
                `status` VARCHAR(30) NOT NULL DEFAULT 'waiting',
                `requested_by` INT DEFAULT NULL,
                `approved_by` INT DEFAULT NULL,
                `decision_note` VARCHAR(255) DEFAULT NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_device_disposals_device` (`device_id`),
                KEY `idx_device_disposals_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public static function generateUuid(): string {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public static function generateQrToken(): string {
        return bin2hex(random_bytes(32));
    }

    public static function deviceTypeForCategory(string $category): string {
        if (in_array($category, ['Laptop', 'Desktop', 'Tablet'], true)) {
            return $category;
        }

        return 'Other';
    }

    public static function statusForCondition(string $condition, bool $assigned): string {
        if ($condition === 'Needs Repair') {
            return self::STATUS_UNDER_MAINTENANCE;
        }
        if ($condition === 'Damaged') {
            return self::STATUS_DAMAGED;
        }
        if ($condition === 'Missing') {
            return self::STATUS_LOST;
        }
        if ($condition === 'Unserviceable') {
            return self::STATUS_RETIRED;
        }

        return $assigned ? self::STATUS_ASSIGNED : self::STATUS_AVAILABLE;
    }

    public static function statusLabel(string $status): string {
        $labels = [
            self::STATUS_AVAILABLE => 'Available',
            self::STATUS_ASSIGNED => 'Assigned',
            self::STATUS_UNDER_MAINTENANCE => 'Under Maintenance',
            self::STATUS_DAMAGED => 'Damaged',
            self::STATUS_LOST => 'Lost',
            self::STATUS_RETURNED => 'Returned',
            self::STATUS_RETIRED => 'Retired',
            self::STATUS_DISPOSED => 'Disposed',
        ];

        return $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }

    public static function warrantyCategory(?string $expiry): string {
        if ($expiry === null || trim($expiry) === '') {
            return 'unknown';
        }
        $ts = strtotime($expiry);
        if ($ts === false) {
            return 'unknown';
        }
        $today = strtotime(date('Y-m-d'));
        $days = (int) floor(($ts - $today) / 86400);
        if ($days < 0) {
            return 'expired';
        }
        if ($days <= 30) {
            return 'expiring_30';
        }
        if ($days <= 90) {
            return 'expiring_90';
        }

        return 'valid';
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public function listDevices(array $filters = [], int $page = 1, int $perPage = 25): array {
        $this->ensureTables();
        $page = max(1, $page);
        $perPage = max(5, min(5000, $perPage));
        $offset = ($page - 1) * $perPage;

        $where = ['d.`deleted_at` IS NULL'];
        $types = '';
        $params = [];

        $this->applyListFilters($filters, $where, $types, $params);

        $whereSql = implode(' AND ', $where);
        $countSql = 'SELECT COUNT(*) AS c FROM `devices` d LEFT JOIN `staff` s ON s.`staff_id` = d.`assigned_employee_id` '
            . 'LEFT JOIN `department` dep ON dep.`department_id` = s.`department_id` '
            . 'LEFT JOIN `student` st ON st.`student_id` = d.`assigned_student_id` WHERE ' . $whereSql;
        $total = $this->scalarCount($countSql, $types, $params);

        $orderBy = $this->sanitizeOrderBy($filters['sort'] ?? 'asset_id');
        $orderDir = (!empty($filters['dir']) && strtoupper((string) $filters['dir']) === 'DESC') ? 'DESC' : 'ASC';

        $sql = 'SELECT d.*, s.`staff_name` AS assigned_staff_name, s.`staff_pno` AS assigned_staff_phone, '
            . 's.`staff_email` AS assigned_staff_email, s.`department_id` AS staff_department_id, '
            . 'dep.`department_name` AS assigned_department_name, '
            . 'own.`department_name` AS owner_department_name, '
            . 'st.`student_fullname` AS assigned_student_name, '
            . '(SELECT COUNT(*) FROM `device_assignments` da WHERE da.`device_id` = d.`id` AND da.`is_active` = 1) AS active_assignment_count '
            . 'FROM `devices` d '
            . 'LEFT JOIN `staff` s ON s.`staff_id` = d.`assigned_employee_id` '
            . 'LEFT JOIN `department` dep ON dep.`department_id` = s.`department_id` '
            . 'LEFT JOIN `department` own ON own.`department_id` = d.`department_id` '
            . 'LEFT JOIN `student` st ON st.`student_id` = d.`assigned_student_id` '
            . 'WHERE ' . $whereSql . " ORDER BY {$orderBy} {$orderDir} LIMIT ? OFFSET ?";
        $types .= 'ii';
        $params[] = $perPage;
        $params[] = $offset;
        $rows = $this->fetchAllPrepared($sql, $types, $params);

        return ['rows' => $rows, 'total' => $total];
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function applyListFilters(array $filters, array &$where, string &$types, array &$params): void {
        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $where[] = '(d.`asset_id` LIKE ? OR d.`asset_name` LIKE ? OR d.`asset_tag_no` LIKE ? OR d.`brand` LIKE ? OR d.`model` LIKE ? '
                . 'OR d.`serial_number` LIKE ? OR d.`computer_name` LIKE ? OR s.`staff_name` LIKE ? OR st.`student_fullname` LIKE ?)';
            $q = '%' . $search . '%';
            $types .= str_repeat('s', 9);
            for ($i = 0; $i < 9; $i++) {
                $params[] = $q;
            }
        }
        $serial = trim((string) ($filters['serial'] ?? ''));
        if ($serial !== '') {
            $where[] = 'd.`serial_number` = ?';
            $types .= 's';
            $params[] = $serial;
        }
        if (!empty($filters['device_type']) && in_array($filters['device_type'], self::DEVICE_TYPES, true)) {
            $where[] = 'd.`device_type` = ?';
            $types .= 's';
            $params[] = $filters['device_type'];
        }
        if (!empty($filters['status']) && in_array($filters['status'], self::STATUSES, true)) {
            $where[] = 'd.`status` = ?';
            $types .= 's';
            $params[] = $filters['status'];
        }
        if (!empty($filters['department_id'])) {
            $dept = trim((string) $filters['department_id']);
            $where[] = '(d.`department_id` = ? OR s.`department_id` = ?)';
            $types .= 'ss';
            $params[] = $dept;
            $params[] = $dept;
        }
        if (!empty($filters['staff_id'])) {
            $staffId = trim((string) $filters['staff_id']);
            $where[] = '(d.`assigned_employee_id` = ? OR EXISTS (SELECT 1 FROM `device_assignments` da WHERE da.`device_id` = d.`id` AND da.`is_active` = 1 AND da.`employee_id` = ?))';
            $types .= 'ss';
            $params[] = $staffId;
            $params[] = $staffId;
        }
        if (!empty($filters['student_id'])) {
            $studentId = trim((string) $filters['student_id']);
            $where[] = '(d.`assigned_student_id` = ? OR EXISTS (SELECT 1 FROM `device_assignments` da WHERE da.`device_id` = d.`id` AND da.`is_active` = 1 AND da.`student_id` = ?))';
            $types .= 'ss';
            $params[] = $studentId;
            $params[] = $studentId;
        }
        if (!empty($filters['condition']) && in_array($filters['condition'], self::SIMPLE_CONDITIONS, true)) {
            $where[] = 'd.`asset_condition` = ?';
            $types .= 's';
            $params[] = $filters['condition'];
        }
        $report = trim((string) ($filters['report'] ?? ''));
        if ($report === 'staff_assigned') {
            $where[] = 'EXISTS (SELECT 1 FROM `device_assignments` da WHERE da.`device_id` = d.`id` AND da.`is_active` = 1 AND da.`assignee_type` = \'staff\')';
        } elseif ($report === 'student_assigned') {
            $where[] = 'EXISTS (SELECT 1 FROM `device_assignments` da WHERE da.`device_id` = d.`id` AND da.`is_active` = 1 AND da.`assignee_type` = \'student\')';
        } elseif ($report === 'available') {
            $where[] = 'd.`status` = \'available\'';
        } elseif ($report === 'damaged') {
            $where[] = '(d.`asset_condition` = \'Damaged\' OR d.`status` = \'damaged\')';
        }
        if (($filters['assigned'] ?? '') === 'yes') {
            $where[] = 'd.`assigned_employee_id` IS NOT NULL AND TRIM(d.`assigned_employee_id`) <> \'\'';
        } elseif (($filters['assigned'] ?? '') === 'no') {
            $where[] = '(d.`assigned_employee_id` IS NULL OR TRIM(d.`assigned_employee_id`) = \'\')';
        }
        $warranty = trim((string) ($filters['warranty'] ?? ''));
        if ($warranty === 'expired') {
            $where[] = 'd.`warranty_expiry` IS NOT NULL AND d.`warranty_expiry` < CURDATE()';
        } elseif ($warranty === 'expiring_30') {
            $where[] = 'd.`warranty_expiry` IS NOT NULL AND d.`warranty_expiry` >= CURDATE() AND d.`warranty_expiry` <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)';
        } elseif ($warranty === 'expiring_90') {
            $where[] = 'd.`warranty_expiry` IS NOT NULL AND d.`warranty_expiry` >= CURDATE() AND d.`warranty_expiry` <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)';
        } elseif ($warranty === 'valid') {
            $where[] = 'd.`warranty_expiry` IS NOT NULL AND d.`warranty_expiry` > DATE_ADD(CURDATE(), INTERVAL 90 DAY)';
        }
    }

    private function sanitizeOrderBy(string $col): string {
        $allowed = [
            'asset_id' => 'd.`asset_id`',
            'asset_tag_no' => 'd.`asset_tag_no`',
            'device_type' => 'd.`device_type`',
            'brand' => 'd.`brand`',
            'status' => 'd.`status`',
            'warranty_expiry' => 'd.`warranty_expiry`',
            'created_at' => 'd.`created_at`',
        ];

        return $allowed[$col] ?? 'd.`asset_id`';
    }

    public function findDevice(int $id, bool $includeDeleted = false): ?array {
        $this->ensureTables();
        $sql = 'SELECT d.*, s.`staff_name` AS assigned_staff_name, s.`staff_pno` AS assigned_staff_phone, '
            . 's.`staff_email` AS assigned_staff_email, s.`staff_position` AS assigned_staff_position, '
            . 's.`staff_epf` AS assigned_staff_epf, s.`department_id` AS staff_department_id, '
            . 'dep.`department_name` AS assigned_department_name, '
            . 'own.`department_name` AS owner_department_name, '
            . 'st.`student_fullname` AS assigned_student_name '
            . 'FROM `devices` d '
            . 'LEFT JOIN `staff` s ON s.`staff_id` = d.`assigned_employee_id` '
            . 'LEFT JOIN `department` dep ON dep.`department_id` = s.`department_id` '
            . 'LEFT JOIN `department` own ON own.`department_id` = d.`department_id` '
            . 'LEFT JOIN `student` st ON st.`student_id` = d.`assigned_student_id` '
            . 'WHERE d.`id` = ?';
        if (!$includeDeleted) {
            $sql .= ' AND d.`deleted_at` IS NULL';
        }
        $sql .= ' LIMIT 1';
        $rows = $this->fetchAllPrepared($sql, 'i', [$id]);

        return $rows[0] ?? null;
    }

    public function findDeviceByAssetId(string $assetId, bool $includeDeleted = false): ?array {
        $assetId = trim($assetId);
        if ($assetId === '') {
            return null;
        }
        $this->ensureTables();
        $sql = 'SELECT d.*, s.`staff_name` AS assigned_staff_name, s.`staff_pno` AS assigned_staff_phone, '
            . 's.`staff_email` AS assigned_staff_email, s.`staff_position` AS assigned_staff_position, '
            . 'dep.`department_name` AS assigned_department_name '
            . 'FROM `devices` d '
            . 'LEFT JOIN `staff` s ON s.`staff_id` = d.`assigned_employee_id` '
            . 'LEFT JOIN `department` dep ON dep.`department_id` = s.`department_id` '
            . 'WHERE d.`asset_id` = ?';
        if (!$includeDeleted) {
            $sql .= ' AND d.`deleted_at` IS NULL';
        }
        $sql .= ' LIMIT 1';
        $rows = $this->fetchAllPrepared($sql, 's', [$assetId]);

        return $rows[0] ?? null;
    }

    public function findDeviceBySerialNumber(string $serialNumber, bool $includeDeleted = false): ?array {
        $serialNumber = trim($serialNumber);
        if ($serialNumber === '') {
            return null;
        }
        $this->ensureTables();
        $sql = 'SELECT d.*, s.`staff_name` AS assigned_staff_name, s.`staff_pno` AS assigned_staff_phone, '
            . 's.`staff_email` AS assigned_staff_email, s.`staff_position` AS assigned_staff_position, '
            . 'dep.`department_name` AS assigned_department_name '
            . 'FROM `devices` d '
            . 'LEFT JOIN `staff` s ON s.`staff_id` = d.`assigned_employee_id` '
            . 'LEFT JOIN `department` dep ON dep.`department_id` = s.`department_id` '
            . 'WHERE d.`serial_number` = ?';
        if (!$includeDeleted) {
            $sql .= ' AND d.`deleted_at` IS NULL';
        }
        $sql .= ' LIMIT 1';
        $rows = $this->fetchAllPrepared($sql, 's', [$serialNumber]);

        return $rows[0] ?? null;
    }

    public function findByQrToken(string $token): ?array {
        $token = trim($token);
        if ($token === '' || strlen($token) > 64) {
            return null;
        }
        $this->ensureTables();
        $sql = 'SELECT d.*, s.`staff_name` AS assigned_staff_name, s.`staff_pno` AS assigned_staff_phone, '
            . 's.`staff_email` AS assigned_staff_email, s.`staff_position` AS assigned_staff_position, '
            . 'dep.`department_name` AS assigned_department_name, '
            . 'own.`department_name` AS owner_department_name, '
            . 'st.`student_fullname` AS assigned_student_name '
            . 'FROM `devices` d '
            . 'LEFT JOIN `staff` s ON s.`staff_id` = d.`assigned_employee_id` '
            . 'LEFT JOIN `department` dep ON dep.`department_id` = s.`department_id` '
            . 'LEFT JOIN `department` own ON own.`department_id` = d.`department_id` '
            . 'LEFT JOIN `student` st ON st.`student_id` = d.`assigned_student_id` '
            . 'WHERE d.`qr_token` = ? AND d.`deleted_at` IS NULL LIMIT 1';
        $rows = $this->fetchAllPrepared($sql, 's', [$token]);

        return $rows[0] ?? null;
    }

    public function assetIdExists(string $assetId, ?int $exceptId = null): bool {
        $assetId = trim($assetId);
        if ($assetId === '') {
            return false;
        }
        $sql = 'SELECT `id` FROM `devices` WHERE `asset_id` = ? AND `deleted_at` IS NULL';
        $types = 's';
        $params = [$assetId];
        if ($exceptId !== null && $exceptId > 0) {
            $sql .= ' AND `id` <> ?';
            $types .= 'i';
            $params[] = $exceptId;
        }
        $sql .= ' LIMIT 1';
        $rows = $this->fetchAllPrepared($sql, $types, $params);

        return !empty($rows);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createDevice(array $data, int $userId, ?string &$error = null): ?int {
        $this->ensureTables();
        $data['uuid'] = self::generateUuid();
        $data['qr_token'] = self::generateQrToken();
        $data['qr_generated_at'] = date('Y-m-d H:i:s');
        $data['created_by'] = $userId;
        $data['updated_by'] = $userId;
        if (empty($data['status'])) {
            $data['status'] = self::STATUS_AVAILABLE;
        }
        $id = $this->create($data, $error);
        if ($id === false) {
            return null;
        }

        return (int) $id;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateDevice(int $id, array $data, int $userId): bool {
        unset($data['id'], $data['uuid'], $data['qr_token'], $data['created_at'], $data['created_by']);
        $data['updated_by'] = $userId;
        return (bool) $this->update($id, $data);
    }

    public function softDeleteDevice(int $id, int $userId): bool {
        return (bool) $this->update($id, [
            'deleted_at' => date('Y-m-d H:i:s'),
            'updated_by' => $userId,
        ]);
    }

    /**
     * @param array<string, mixed> $device
     */
    public function isDeviceAssigned(array $device): bool {
        if (($device['status'] ?? '') === self::STATUS_ASSIGNED) {
            return true;
        }
        if (trim((string) ($device['assigned_employee_id'] ?? '')) !== '') {
            return true;
        }
        $id = (int) ($device['id'] ?? 0);
        if ($id > 0 && $this->getActiveAssignment($id) !== null) {
            return true;
        }

        return false;
    }

    public function hardDeleteDevice(int $id): bool {
        $conn = $this->db->getConnection();
        $conn->begin_transaction();
        try {
            $childTables = [
                'device_accessories',
                'device_assignments',
                'device_condition_history',
                'device_audit_logs',
                'device_transfers',
                'device_disposals',
            ];
            foreach ($childTables as $table) {
                $stmt = $this->db->prepare("DELETE FROM `{$table}` WHERE `device_id` = ?");
                if (!$stmt) {
                    throw new RuntimeException('Prepare failed for ' . $table);
                }
                $stmt->bind_param('i', $id);
                if (!$stmt->execute()) {
                    $stmt->close();
                    throw new RuntimeException('Delete failed for ' . $table);
                }
                $stmt->close();
            }
            if (!$this->delete($id)) {
                throw new RuntimeException('Delete failed for devices');
            }
            $conn->commit();

            return true;
        } catch (Throwable $e) {
            $conn->rollback();
            error_log('DeviceModel::hardDeleteDevice: ' . $e->getMessage());

            return false;
        }
    }

    public function regenerateQrToken(int $id, int $userId): ?string {
        $token = self::generateQrToken();
        $ok = $this->update($id, [
            'qr_token' => $token,
            'qr_generated_at' => date('Y-m-d H:i:s'),
            'updated_by' => $userId,
        ]);
        return $ok ? $token : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getAccessories(int $deviceId): array {
        $sql = 'SELECT * FROM `device_accessories` WHERE `device_id` = ? ORDER BY `accessory_type` ASC, `id` ASC';
        return $this->fetchAllPrepared($sql, 'i', [$deviceId]);
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    public function syncAccessories(int $deviceId, array $rows): void {
        $this->db->query('DELETE FROM `device_accessories` WHERE `device_id` = ' . (int) $deviceId);
        foreach ($rows as $row) {
            $type = trim((string) ($row['accessory_type'] ?? ''));
            if ($type === '' || !in_array($type, self::ACCESSORY_TYPES, true)) {
                continue;
            }
            $status = trim((string) ($row['status'] ?? 'Good'));
            if (!in_array($status, ['Good', 'Fair', 'Damaged', 'Missing'], true)) {
                $status = 'Good';
            }
            $stmt = $this->db->prepare(
                'INSERT INTO `device_accessories` (`device_id`, `accessory_type`, `serial_number`, `status`, `remarks`) VALUES (?, ?, ?, ?, ?)'
            );
            if (!$stmt) {
                continue;
            }
            $serial = trim((string) ($row['serial_number'] ?? ''));
            $remarks = trim((string) ($row['remarks'] ?? ''));
            $stmt->bind_param('issss', $deviceId, $type, $serial, $status, $remarks);
            $stmt->execute();
            $stmt->close();
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getAssignmentHistory(int $deviceId): array {
        $sql = 'SELECT a.*, s.`staff_name`, dep.`department_name`, s.`staff_email`, s.`staff_pno`, s.`staff_position`, '
            . 'st.`student_fullname` '
            . 'FROM `device_assignments` a '
            . 'LEFT JOIN `staff` s ON s.`staff_id` = a.`employee_id` '
            . 'LEFT JOIN `department` dep ON dep.`department_id` = s.`department_id` '
            . 'LEFT JOIN `student` st ON st.`student_id` = a.`student_id` '
            . 'WHERE a.`device_id` = ? ORDER BY a.`issue_date` DESC, a.`id` DESC';
        return $this->fetchAllPrepared($sql, 'i', [$deviceId]);
    }

    public function getActiveAssignment(int $deviceId): ?array {
        $rows = $this->getActiveAssignments($deviceId);

        return $rows[0] ?? null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getActiveAssignments(int $deviceId): array {
        $sql = 'SELECT a.*, s.`staff_name`, s.`staff_epf`, dep.`department_name`, st.`student_fullname` '
            . 'FROM `device_assignments` a '
            . 'LEFT JOIN `staff` s ON s.`staff_id` = a.`employee_id` '
            . 'LEFT JOIN `department` dep ON dep.`department_id` = s.`department_id` '
            . 'LEFT JOIN `student` st ON st.`student_id` = a.`student_id` '
            . 'WHERE a.`device_id` = ? AND a.`is_active` = 1 ORDER BY a.`id` DESC';

        return $this->fetchAllPrepared($sql, 'i', [$deviceId]);
    }

    public function activeAssignmentCount(int $deviceId): int {
        $rows = $this->fetchAllPrepared(
            'SELECT COUNT(*) AS c FROM `device_assignments` WHERE `device_id` = ? AND `is_active` = 1',
            'i',
            [$deviceId]
        );

        return (int) ($rows[0]['c'] ?? 0);
    }

    /**
     * @param array<string, mixed> $device
     */
    public function availableSlots(array $device): int {
        $qty = max(1, (int) ($device['quantity'] ?? 1));
        $id = (int) ($device['id'] ?? 0);

        return max(0, $qty - ($id > 0 ? $this->activeAssignmentCount($id) : 0));
    }

    public function assignDevice(int $deviceId, string $employeeId, string $issueDate, int $userId, ?string $remarks = null): bool {
        return $this->assignToPerson($deviceId, 'staff', $employeeId, $issueDate, $userId, null, $remarks);
    }

    public function assignToPerson(int $deviceId, string $assigneeType, string $personId, string $issueDate, int $userId, ?string $expectedReturn = null, ?string $remarks = null): bool {
        $device = $this->findDevice($deviceId);
        if (!$device || $personId === '') {
            return false;
        }
        $blocked = ['disposed', 'lost', 'retired'];
        if (in_array(strtolower((string) ($device['status'] ?? '')), $blocked, true)) {
            return false;
        }
        if ($this->availableSlots($device) < 1) {
            return false;
        }
        $assigneeType = $assigneeType === 'student' ? 'student' : 'staff';
        $employeeId = $assigneeType === 'staff' ? $personId : null;
        $studentId = $assigneeType === 'student' ? $personId : null;
        $expectedReturn = $expectedReturn !== null && $expectedReturn !== '' ? $expectedReturn : null;

        $this->db->getConnection()->begin_transaction();
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO `device_assignments` (`device_id`, `employee_id`, `student_id`, `assignee_type`, `issue_date`, `expected_return_date`, `issued_by`, `remarks`, `is_active`) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)'
            );
            if (!$stmt) {
                throw new RuntimeException('Prepare failed');
            }
            $stmt->bind_param('isssssis', $deviceId, $employeeId, $studentId, $assigneeType, $issueDate, $expectedReturn, $userId, $remarks);
            if (!$stmt->execute()) {
                throw new RuntimeException($stmt->error);
            }
            $stmt->close();
            $this->syncAssignee($deviceId, $userId);
            $this->db->getConnection()->commit();

            return true;
        } catch (Throwable $e) {
            $this->db->getConnection()->rollback();
            error_log('DeviceModel::assignToPerson: ' . $e->getMessage());

            return false;
        }
    }

    public function returnDevice(int $deviceId, string $returnDate, int $userId, ?string $remarks = null, string $newStatus = self::STATUS_AVAILABLE, int $assignmentId = 0): bool {
        $note = $remarks !== null && $remarks !== '' ? "\nReturn: " . $remarks : '';
        if ($assignmentId > 0) {
            $stmt = $this->db->prepare(
                'UPDATE `device_assignments` SET `is_active` = 0, `return_date` = ?, `returned_by` = ?, `remarks` = CONCAT(IFNULL(`remarks`, \'\'), ?) WHERE `id` = ? AND `device_id` = ? AND `is_active` = 1'
            );
            if (!$stmt) {
                return false;
            }
            $stmt->bind_param('sisii', $returnDate, $userId, $note, $assignmentId, $deviceId);
        } else {
            $stmt = $this->db->prepare(
                'UPDATE `device_assignments` SET `is_active` = 0, `return_date` = ?, `returned_by` = ?, `remarks` = CONCAT(IFNULL(`remarks`, \'\'), ?) WHERE `device_id` = ? AND `is_active` = 1'
            );
            if (!$stmt) {
                return false;
            }
            $stmt->bind_param('sisi', $returnDate, $userId, $note, $deviceId);
        }
        $stmt->execute();
        $changed = $stmt->affected_rows > 0;
        $stmt->close();
        if (!$changed) {
            return false;
        }
        if ($this->activeAssignmentCount($deviceId) > 0 && $newStatus !== self::STATUS_DISPOSED) {
            $this->syncAssignee($deviceId, $userId);

            return true;
        }
        $device = $this->findDevice($deviceId);
        $condition = (string) ($device['asset_condition'] ?? 'Good');
        $status = $newStatus;
        if ($newStatus === self::STATUS_DISPOSED) {
            $status = self::STATUS_DISPOSED;
        } elseif ($condition !== '' && $condition !== 'Good') {
            $status = self::statusForCondition($condition, false);
        } elseif (!in_array($newStatus, self::STATUSES, true)) {
            $status = self::STATUS_AVAILABLE;
        }

        return (bool) $this->update($deviceId, [
            'assigned_employee_id' => null,
            'assigned_student_id' => null,
            'status' => $status,
            'updated_by' => $userId,
        ]);
    }

    private function syncAssignee(int $deviceId, int $userId): void {
        $active = $this->getActiveAssignments($deviceId);
        $device = $this->findDevice($deviceId);
        if (!$device) {
            return;
        }
        $latest = $active[0] ?? null;
        $assigned = $latest !== null;
        $condition = (string) ($device['asset_condition'] ?? 'Good');
        if ($condition === '') {
            $condition = 'Good';
        }
        $this->update($deviceId, [
            'assigned_employee_id' => $latest && ($latest['assignee_type'] ?? 'staff') !== 'student' ? ($latest['employee_id'] ?? null) : null,
            'assigned_student_id' => $latest && ($latest['assignee_type'] ?? '') === 'student' ? ($latest['student_id'] ?? null) : null,
            'status' => self::statusForCondition($condition, $assigned),
            'updated_by' => $userId,
        ]);
    }

    /**
     * @param array<string, mixed> $condition
     */
    public function recordCondition(int $deviceId, array $condition, int $userId): bool {
        $fields = [
            'cond_lcd_screen' => $condition['lcd_screen'] ?? null,
            'cond_keyboard' => $condition['keyboard'] ?? null,
            'cond_touchpad' => $condition['touchpad'] ?? null,
            'cond_battery' => $condition['battery'] ?? null,
            'cond_ports' => $condition['ports'] ?? null,
            'cond_charger' => $condition['charger'] ?? null,
            'cond_outer_body' => $condition['outer_body'] ?? null,
            'condition_remarks' => $condition['remarks'] ?? null,
            'updated_by' => $userId,
        ];
        foreach (['cond_lcd_screen', 'cond_keyboard', 'cond_touchpad', 'cond_battery', 'cond_ports', 'cond_charger', 'cond_outer_body'] as $k) {
            if ($fields[$k] !== null && !in_array($fields[$k], self::CONDITION_VALUES, true)) {
                $fields[$k] = null;
            }
        }
        $ok = (bool) $this->update($deviceId, $fields);
        if (!$ok) {
            return false;
        }
        $stmt = $this->db->prepare(
            'INSERT INTO `device_condition_history` (`device_id`, `lcd_screen`, `keyboard`, `touchpad`, `battery`, `ports`, `charger`, `outer_body`, `remarks`, `recorded_by`) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        if ($stmt) {
            $stmt->bind_param(
                'issssssssi',
                $deviceId,
                $fields['cond_lcd_screen'],
                $fields['cond_keyboard'],
                $fields['cond_touchpad'],
                $fields['cond_battery'],
                $fields['cond_ports'],
                $fields['cond_charger'],
                $fields['cond_outer_body'],
                $fields['condition_remarks'],
                $userId
            );
            $stmt->execute();
            $stmt->close();
        }

        return true;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getConditionHistory(int $deviceId): array {
        $sql = 'SELECT c.*, u.`user_name` AS recorded_by_name '
            . 'FROM `device_condition_history` c '
            . 'LEFT JOIN `user` u ON u.`user_id` = c.`recorded_by` '
            . 'WHERE c.`device_id` = ? ORDER BY c.`recorded_at` DESC, c.`id` DESC';
        return $this->fetchAllPrepared($sql, 'i', [$deviceId]);
    }

    /**
     * @param array<string, mixed>|null $old
     * @param array<string, mixed>|null $new
     */
    public function logAudit(?int $deviceId, ?int $userId, string $action, $old = null, $new = null): void {
        $this->ensureTables();
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? substr((string) $_SERVER['HTTP_USER_AGENT'], 0, 255) : null;
        $stmt = $this->db->prepare(
            'INSERT INTO `device_audit_logs` (`device_id`, `user_id`, `action`, `old_values`, `new_values`, `ip_address`, `user_agent`) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        if (!$stmt) {
            return;
        }
        $oldJson = $old !== null ? json_encode($old, JSON_UNESCAPED_UNICODE) : null;
        $newJson = $new !== null ? json_encode($new, JSON_UNESCAPED_UNICODE) : null;
        $stmt->bind_param('iisssss', $deviceId, $userId, $action, $oldJson, $newJson, $ip, $ua);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getAuditLogs(?int $deviceId = null, int $limit = 100): array {
        $limit = max(1, min(500, $limit));
        $sql = 'SELECT l.*, u.`user_name`, d.`asset_id` '
            . 'FROM `device_audit_logs` l '
            . 'LEFT JOIN `user` u ON u.`user_id` = l.`user_id` '
            . 'LEFT JOIN `devices` d ON d.`id` = l.`device_id` ';
        $params = [];
        $types = '';
        if ($deviceId !== null && $deviceId > 0) {
            $sql .= 'WHERE l.`device_id` = ? ';
            $types = 'i';
            $params[] = $deviceId;
        }
        $sql .= 'ORDER BY l.`created_at` DESC, l.`id` DESC LIMIT ' . $limit;
        if ($types !== '') {
            return $this->fetchAllPrepared($sql, $types, $params);
        }
        $result = $this->db->query($sql);
        $rows = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboardStats(): array {
        $this->ensureTables();
        $stats = [
            'total' => 0,
            'available' => 0,
            'assigned' => 0,
            'under_maintenance' => 0,
            'damaged' => 0,
            'retired' => 0,
            'warranty_expiring_30' => 0,
            'warranty_expiring_90' => 0,
            'warranty_expired' => 0,
        ];
        $sql = 'SELECT `status`, COUNT(*) AS c FROM `devices` WHERE `deleted_at` IS NULL GROUP BY `status`';
        $result = $this->db->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $stats['total'] += (int) ($row['c'] ?? 0);
                $key = (string) ($row['status'] ?? '');
                if (isset($stats[$key])) {
                    $stats[$key] = (int) ($row['c'] ?? 0);
                }
            }
        }
        $wSql = 'SELECT '
            . 'SUM(CASE WHEN `warranty_expiry` IS NOT NULL AND `warranty_expiry` < CURDATE() THEN 1 ELSE 0 END) AS expired, '
            . 'SUM(CASE WHEN `warranty_expiry` IS NOT NULL AND `warranty_expiry` >= CURDATE() AND `warranty_expiry` <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS exp30, '
            . 'SUM(CASE WHEN `warranty_expiry` IS NOT NULL AND `warranty_expiry` > DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND `warranty_expiry` <= DATE_ADD(CURDATE(), INTERVAL 90 DAY) THEN 1 ELSE 0 END) AS exp90 '
            . 'FROM `devices` WHERE `deleted_at` IS NULL';
        $wRes = $this->db->query($wSql);
        if ($wRes && ($wRow = $wRes->fetch_assoc())) {
            $stats['warranty_expired'] = (int) ($wRow['expired'] ?? 0);
            $stats['warranty_expiring_30'] = (int) ($wRow['exp30'] ?? 0);
            $stats['warranty_expiring_90'] = (int) ($wRow['exp90'] ?? 0);
        }

        return $stats;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function chartByDepartment(): array {
        $sql = 'SELECT dep.`department_name`, COUNT(*) AS c '
            . 'FROM `devices` d '
            . 'LEFT JOIN `staff` s ON s.`staff_id` = d.`assigned_employee_id` '
            . 'LEFT JOIN `department` dep ON dep.`department_id` = s.`department_id` '
            . 'WHERE d.`deleted_at` IS NULL AND d.`assigned_employee_id` IS NOT NULL '
            . 'GROUP BY dep.`department_name` ORDER BY c DESC LIMIT 12';
        return $this->queryRows($sql);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function chartByType(): array {
        $sql = 'SELECT `device_type`, COUNT(*) AS c FROM `devices` WHERE `deleted_at` IS NULL GROUP BY `device_type` ORDER BY c DESC';
        return $this->queryRows($sql);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function chartByStatus(): array {
        $sql = 'SELECT `status`, COUNT(*) AS c FROM `devices` WHERE `deleted_at` IS NULL GROUP BY `status` ORDER BY c DESC';
        return $this->queryRows($sql);
    }

    /**
     * @param array<string, mixed> $filters
     * @return list<array<string, mixed>>
     */
    public function exportRows(array $filters = []): array {
        $result = $this->listDevices($filters, 1, 10000);

        return $result['rows'];
    }

    public function nextAssetCode(): string {
        $prefix = 'AST-' . date('Y') . '-';
        $rows = $this->fetchAllPrepared(
            'SELECT `asset_id` FROM `devices` WHERE `asset_id` LIKE ? ORDER BY `asset_id` DESC LIMIT 1',
            's',
            [$prefix . '%']
        );
        $next = 1;
        if (!empty($rows[0]['asset_id']) && preg_match('/(\d+)$/', (string) $rows[0]['asset_id'], $match)) {
            $next = ((int) $match[1]) + 1;
        }

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @param array<string, mixed> $scope
     * @return array<string, int>
     */
    public function simpleStats(array $scope = []): array {
        $filters = $this->scopeFilters($scope);
        $stats = [
            'total' => 0,
            'available' => 0,
            'assigned' => 0,
            'damaged' => 0,
            'under_repair' => 0,
            'pending_transfers' => 0,
        ];
        $rows = $this->listDevices($filters, 1, 10000)['rows'];
        foreach ($rows as $row) {
            $stats['total']++;
            $status = (string) ($row['status'] ?? '');
            $condition = (string) ($row['asset_condition'] ?? '');
            if ($status === self::STATUS_AVAILABLE) {
                $stats['available']++;
            }
            if ($status === self::STATUS_ASSIGNED || (int) ($row['active_assignment_count'] ?? 0) > 0) {
                $stats['assigned']++;
            }
            if ($condition === 'Damaged' || $status === self::STATUS_DAMAGED) {
                $stats['damaged']++;
            }
            if ($condition === 'Needs Repair' || $status === self::STATUS_UNDER_MAINTENANCE) {
                $stats['under_repair']++;
            }
        }
        $stats['pending_transfers'] = $this->countTransfers('waiting', $scope);

        return $stats;
    }

    /**
     * @param array<string, mixed> $scope
     * @return array<string, mixed>
     */
    public function scopeFilters(array $scope): array {
        $filters = [];
        if (($scope['mode'] ?? '') === 'department' && !empty($scope['department_id'])) {
            $filters['department_id'] = $scope['department_id'];
        } elseif (($scope['mode'] ?? '') === 'staff' && !empty($scope['staff_id'])) {
            $filters['staff_id'] = $scope['staff_id'];
        } elseif (($scope['mode'] ?? '') === 'student' && !empty($scope['student_id'])) {
            $filters['student_id'] = $scope['student_id'];
        }

        return $filters;
    }

    public function recordSimpleCondition(int $deviceId, string $condition, string $remarks, int $userId, ?string $photoPath = null): bool {
        if (!in_array($condition, self::SIMPLE_CONDITIONS, true)) {
            return false;
        }
        $device = $this->findDevice($deviceId);
        if (!$device) {
            return false;
        }
        $assigned = $this->activeAssignmentCount($deviceId) > 0;
        $status = self::statusForCondition($condition, $assigned);
        if ((string) ($device['status'] ?? '') === self::STATUS_DISPOSED) {
            $status = self::STATUS_DISPOSED;
        }
        $ok = (bool) $this->update($deviceId, [
            'asset_condition' => $condition,
            'condition_remarks' => $remarks !== '' ? $remarks : null,
            'status' => $status,
            'photo_path' => $photoPath ?: ($device['photo_path'] ?? null),
            'updated_by' => $userId,
        ]);
        if (!$ok) {
            return false;
        }
        $stmt = $this->db->prepare(
            'INSERT INTO `device_condition_history` (`device_id`, `asset_condition`, `remarks`, `photo_path`, `recorded_by`) VALUES (?, ?, ?, ?, ?)'
        );
        if ($stmt) {
            $stmt->bind_param('isssi', $deviceId, $condition, $remarks, $photoPath, $userId);
            $stmt->execute();
            $stmt->close();
        }

        return true;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function requestTransfer(int $deviceId, string $toDepartmentId, string $toLocation, string $reason, int $userId): ?array {
        $device = $this->findDevice($deviceId);
        if (!$device || $toDepartmentId === '') {
            return null;
        }
        $from = (string) ($device['department_id'] ?? '');
        $status = 'waiting';
        $stmt = $this->db->prepare(
            'INSERT INTO `device_transfers` (`device_id`, `from_department_id`, `to_department_id`, `to_location`, `reason`, `status`, `requested_by`) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('isssssi', $deviceId, $from, $toDepartmentId, $toLocation, $reason, $status, $userId);
        $ok = $stmt->execute();
        $id = (int) $stmt->insert_id;
        $stmt->close();
        if (!$ok) {
            return null;
        }
        $this->logAudit($deviceId, $userId, 'transfer_requested', null, ['transfer_id' => $id, 'to' => $toDepartmentId]);

        return $this->findTransfer($id);
    }

    public function decideTransfer(int $transferId, string $decision, int $userId, string $note = ''): bool {
        $row = $this->findTransfer($transferId);
        if (!$row || ($row['status'] ?? '') !== 'waiting' || !in_array($decision, ['approved', 'rejected'], true)) {
            return false;
        }
        $stmt = $this->db->prepare(
            'UPDATE `device_transfers` SET `status` = ?, `approved_by` = ?, `decision_note` = ? WHERE `id` = ? AND `status` = \'waiting\''
        );
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('sisi', $decision, $userId, $note, $transferId);
        $stmt->execute();
        $ok = $stmt->affected_rows > 0;
        $stmt->close();
        if ($ok) {
            $this->logAudit((int) $row['device_id'], $userId, 'transfer_' . $decision, null, ['transfer_id' => $transferId]);
        }

        return $ok;
    }

    public function confirmTransfer(int $transferId, int $userId): bool {
        $row = $this->findTransfer($transferId);
        if (!$row || ($row['status'] ?? '') !== 'approved') {
            return false;
        }
        $this->db->getConnection()->begin_transaction();
        try {
            $stmt = $this->db->prepare(
                'UPDATE `device_transfers` SET `status` = \'completed\', `confirmed_by` = ? WHERE `id` = ? AND `status` = \'approved\''
            );
            if (!$stmt) {
                throw new RuntimeException('Prepare failed');
            }
            $stmt->bind_param('ii', $userId, $transferId);
            $stmt->execute();
            $stmt->close();
            $location = trim((string) ($row['to_location'] ?? ''));
            $data = [
                'department_id' => $row['to_department_id'],
                'updated_by' => $userId,
            ];
            if ($location !== '') {
                $data['room_location'] = $location;
            }
            $this->update((int) $row['device_id'], $data);
            $this->db->getConnection()->commit();
            $this->logAudit((int) $row['device_id'], $userId, 'transfer_completed', null, ['transfer_id' => $transferId]);

            return true;
        } catch (Throwable $e) {
            $this->db->getConnection()->rollback();

            return false;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findTransfer(int $id): ?array {
        $rows = $this->fetchAllPrepared(
            'SELECT t.*, d.`asset_id`, d.`asset_name`, d.`photo_path`, fd.`department_name` AS from_department_name, td.`department_name` AS to_department_name '
            . 'FROM `device_transfers` t '
            . 'INNER JOIN `devices` d ON d.`id` = t.`device_id` '
            . 'LEFT JOIN `department` fd ON fd.`department_id` = t.`from_department_id` '
            . 'LEFT JOIN `department` td ON td.`department_id` = t.`to_department_id` '
            . 'WHERE t.`id` = ? LIMIT 1',
            'i',
            [$id]
        );

        return $rows[0] ?? null;
    }

    /**
     * @param array<string, mixed> $scope
     * @return list<array<string, mixed>>
     */
    public function listTransfers(array $scope = [], string $status = ''): array {
        $sql = 'SELECT t.*, d.`asset_id`, d.`asset_name`, d.`department_id` AS current_department_id, '
            . 'fd.`department_name` AS from_department_name, td.`department_name` AS to_department_name '
            . 'FROM `device_transfers` t '
            . 'INNER JOIN `devices` d ON d.`id` = t.`device_id` '
            . 'LEFT JOIN `department` fd ON fd.`department_id` = t.`from_department_id` '
            . 'LEFT JOIN `department` td ON td.`department_id` = t.`to_department_id` WHERE 1=1';
        $types = '';
        $params = [];
        if ($status !== '') {
            $sql .= ' AND t.`status` = ?';
            $types .= 's';
            $params[] = $status;
        }
        if (($scope['mode'] ?? '') === 'department' && !empty($scope['department_id'])) {
            $sql .= ' AND (t.`from_department_id` = ? OR t.`to_department_id` = ? OR d.`department_id` = ?)';
            $types .= 'sss';
            $params[] = $scope['department_id'];
            $params[] = $scope['department_id'];
            $params[] = $scope['department_id'];
        }
        $sql .= ' ORDER BY t.`id` DESC LIMIT 200';

        return $types === '' ? $this->queryRows($sql) : $this->fetchAllPrepared($sql, $types, $params);
    }

    /**
     * @param array<string, mixed> $scope
     */
    public function countTransfers(string $status, array $scope = []): int {
        return count($this->listTransfers($scope, $status));
    }

    public function requestDisposal(int $deviceId, string $reason, string $remarks, int $userId): bool {
        if (!in_array($reason, self::DISPOSE_REASONS, true) || !$this->findDevice($deviceId)) {
            return false;
        }
        $status = 'waiting';
        $stmt = $this->db->prepare(
            'INSERT INTO `device_disposals` (`device_id`, `reason`, `remarks`, `status`, `requested_by`) VALUES (?, ?, ?, ?, ?)'
        );
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('isssi', $deviceId, $reason, $remarks, $status, $userId);
        $ok = $stmt->execute();
        $stmt->close();
        if ($ok) {
            $this->logAudit($deviceId, $userId, 'disposal_requested', null, ['reason' => $reason]);
        }

        return $ok;
    }

    public function decideDisposal(int $disposalId, string $decision, int $userId, string $note = ''): bool {
        $rows = $this->fetchAllPrepared('SELECT * FROM `device_disposals` WHERE `id` = ? LIMIT 1', 'i', [$disposalId]);
        $row = $rows[0] ?? null;
        if (!$row || ($row['status'] ?? '') !== 'waiting' || !in_array($decision, ['approved', 'rejected'], true)) {
            return false;
        }
        $stmt = $this->db->prepare(
            'UPDATE `device_disposals` SET `status` = ?, `approved_by` = ?, `decision_note` = ? WHERE `id` = ?'
        );
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('sisi', $decision, $userId, $note, $disposalId);
        $stmt->execute();
        $stmt->close();
        if ($decision === 'approved') {
            $deviceId = (int) $row['device_id'];
            $this->returnDevice($deviceId, date('Y-m-d'), $userId, 'Disposed', self::STATUS_DISPOSED);
            $this->update($deviceId, [
                'status' => self::STATUS_DISPOSED,
                'updated_by' => $userId,
            ]);
        }
        $this->logAudit((int) $row['device_id'], $userId, 'disposal_' . $decision, null, ['disposal_id' => $disposalId]);

        return true;
    }

    /**
     * @param array<string, mixed> $scope
     * @return list<array<string, mixed>>
     */
    public function listDisposals(array $scope = []): array {
        $sql = 'SELECT p.*, d.`asset_id`, d.`asset_name`, d.`department_id`, dep.`department_name` '
            . 'FROM `device_disposals` p '
            . 'INNER JOIN `devices` d ON d.`id` = p.`device_id` '
            . 'LEFT JOIN `department` dep ON dep.`department_id` = d.`department_id` WHERE 1=1';
        $types = '';
        $params = [];
        if (($scope['mode'] ?? '') === 'department' && !empty($scope['department_id'])) {
            $sql .= ' AND d.`department_id` = ?';
            $types .= 's';
            $params[] = $scope['department_id'];
        }
        $sql .= ' ORDER BY p.`id` DESC LIMIT 200';

        return $types === '' ? $this->queryRows($sql) : $this->fetchAllPrepared($sql, $types, $params);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function searchStudents(string $term, string $departmentId = '', string $courseId = '', string $groupId = ''): array {
        $term = trim($term);
        if ($term === '') {
            return [];
        }
        $like = '%' . $term . '%';
        $sql = 'SELECT DISTINCT s.`student_id`, s.`student_fullname`, c.`course_name`, se.`academic_year` '
            . 'FROM `student` s '
            . 'INNER JOIN `student_enroll` se ON se.`student_id` = s.`student_id` AND se.`student_enroll_status` IN (\'Following\', \'Internship\') '
            . 'LEFT JOIN `course` c ON c.`course_id` = se.`course_id` '
            . 'WHERE (s.`student_id` LIKE ? OR s.`student_fullname` LIKE ?)';
        $types = 'ss';
        $params = [$like, $like];
        if ($departmentId !== '') {
            $sql .= ' AND c.`department_id` = ?';
            $types .= 's';
            $params[] = $departmentId;
        }
        if ($courseId !== '') {
            $sql .= ' AND se.`course_id` = ?';
            $types .= 's';
            $params[] = $courseId;
        }
        if ($groupId !== '') {
            $sql .= ' AND EXISTS (SELECT 1 FROM `group_students` gs INNER JOIN `groups` g ON g.`id` = gs.`group_id` '
                . 'WHERE gs.`student_id` = s.`student_id` AND gs.`status` = \'active\' AND g.`id` = ?)';
            $types .= 'i';
            $params[] = (int) $groupId;
        }
        $sql .= ' ORDER BY s.`student_fullname` ASC LIMIT 20';

        return $this->fetchAllPrepared($sql, $types, $params);
    }

    private function scalarCount(string $sql, string $types, array $params): int {
        $rows = $this->fetchAllPrepared($sql, $types, $params);

        return (int) ($rows[0]['c'] ?? 0);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function queryRows(string $sql): array {
        $result = $this->db->query($sql);
        $rows = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function fetchAllPrepared(string $sql, string $types, array $params): array {
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }
        if ($types !== '' && $params !== []) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        $stmt->close();

        return $rows;
    }
}
