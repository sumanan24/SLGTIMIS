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
        self::$tablesEnsured = true;
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
        $perPage = max(5, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $where = ['d.`deleted_at` IS NULL'];
        $types = '';
        $params = [];

        $this->applyListFilters($filters, $where, $types, $params);

        $whereSql = implode(' AND ', $where);
        $countSql = 'SELECT COUNT(*) AS c FROM `devices` d LEFT JOIN `staff` s ON s.`staff_id` = d.`assigned_employee_id` '
            . 'LEFT JOIN `department` dep ON dep.`department_id` = s.`department_id` WHERE ' . $whereSql;
        $total = $this->scalarCount($countSql, $types, $params);

        $orderBy = $this->sanitizeOrderBy($filters['sort'] ?? 'asset_id');
        $orderDir = (!empty($filters['dir']) && strtoupper((string) $filters['dir']) === 'DESC') ? 'DESC' : 'ASC';

        $sql = 'SELECT d.*, s.`staff_name` AS assigned_staff_name, s.`staff_pno` AS assigned_staff_phone, '
            . 's.`staff_email` AS assigned_staff_email, dep.`department_name` AS assigned_department_name '
            . 'FROM `devices` d '
            . 'LEFT JOIN `staff` s ON s.`staff_id` = d.`assigned_employee_id` '
            . 'LEFT JOIN `department` dep ON dep.`department_id` = s.`department_id` '
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
            $where[] = '(d.`asset_id` LIKE ? OR d.`asset_tag_no` LIKE ? OR d.`brand` LIKE ? OR d.`model` LIKE ? '
                . 'OR d.`serial_number` LIKE ? OR d.`computer_name` LIKE ? OR s.`staff_name` LIKE ?)';
            $q = '%' . $search . '%';
            $types .= str_repeat('s', 7);
            for ($i = 0; $i < 7; $i++) {
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
            $where[] = 's.`department_id` = ?';
            $types .= 's';
            $params[] = trim((string) $filters['department_id']);
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
            . 'dep.`department_name` AS assigned_department_name '
            . 'FROM `devices` d '
            . 'LEFT JOIN `staff` s ON s.`staff_id` = d.`assigned_employee_id` '
            . 'LEFT JOIN `department` dep ON dep.`department_id` = s.`department_id` '
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
            . 'dep.`department_name` AS assigned_department_name '
            . 'FROM `devices` d '
            . 'LEFT JOIN `staff` s ON s.`staff_id` = d.`assigned_employee_id` '
            . 'LEFT JOIN `department` dep ON dep.`department_id` = s.`department_id` '
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
        $sql = 'SELECT a.*, s.`staff_name`, dep.`department_name`, s.`staff_email`, s.`staff_pno`, s.`staff_position` '
            . 'FROM `device_assignments` a '
            . 'INNER JOIN `staff` s ON s.`staff_id` = a.`employee_id` '
            . 'LEFT JOIN `department` dep ON dep.`department_id` = s.`department_id` '
            . 'WHERE a.`device_id` = ? ORDER BY a.`issue_date` DESC, a.`id` DESC';
        return $this->fetchAllPrepared($sql, 'i', [$deviceId]);
    }

    public function getActiveAssignment(int $deviceId): ?array {
        $sql = 'SELECT a.*, s.`staff_name`, dep.`department_name` '
            . 'FROM `device_assignments` a '
            . 'INNER JOIN `staff` s ON s.`staff_id` = a.`employee_id` '
            . 'LEFT JOIN `department` dep ON dep.`department_id` = s.`department_id` '
            . 'WHERE a.`device_id` = ? AND a.`is_active` = 1 ORDER BY a.`id` DESC LIMIT 1';
        $rows = $this->fetchAllPrepared($sql, 'i', [$deviceId]);

        return $rows[0] ?? null;
    }

    public function assignDevice(int $deviceId, string $employeeId, string $issueDate, int $userId, ?string $remarks = null): bool {
        $this->db->getConnection()->begin_transaction();
        try {
            $stmt = $this->db->prepare(
                'UPDATE `device_assignments` SET `is_active` = 0, `return_date` = COALESCE(`return_date`, CURDATE()), `returned_by` = ? WHERE `device_id` = ? AND `is_active` = 1'
            );
            if ($stmt) {
                $stmt->bind_param('ii', $userId, $deviceId);
                $stmt->execute();
                $stmt->close();
            }
            $stmt2 = $this->db->prepare(
                'INSERT INTO `device_assignments` (`device_id`, `employee_id`, `issue_date`, `issued_by`, `remarks`, `is_active`) VALUES (?, ?, ?, ?, ?, 1)'
            );
            if (!$stmt2) {
                throw new RuntimeException('Prepare failed');
            }
            $stmt2->bind_param('issis', $deviceId, $employeeId, $issueDate, $userId, $remarks);
            $stmt2->execute();
            $stmt2->close();
            $this->update($deviceId, [
                'assigned_employee_id' => $employeeId,
                'status' => self::STATUS_ASSIGNED,
                'updated_by' => $userId,
            ]);
            $this->db->getConnection()->commit();

            return true;
        } catch (Throwable $e) {
            $this->db->getConnection()->rollback();
            error_log('DeviceModel::assignDevice: ' . $e->getMessage());

            return false;
        }
    }

    public function returnDevice(int $deviceId, string $returnDate, int $userId, ?string $remarks = null, string $newStatus = self::STATUS_AVAILABLE): bool {
        $stmt = $this->db->prepare(
            'UPDATE `device_assignments` SET `is_active` = 0, `return_date` = ?, `returned_by` = ?, `remarks` = CONCAT(IFNULL(`remarks`, \'\'), ?) WHERE `device_id` = ? AND `is_active` = 1'
        );
        if (!$stmt) {
            return false;
        }
        $note = $remarks !== null && $remarks !== '' ? "\nReturn: " . $remarks : '';
        $stmt->bind_param('sisi', $returnDate, $userId, $note, $deviceId);
        $stmt->execute();
        $stmt->close();

        return (bool) $this->update($deviceId, [
            'assigned_employee_id' => null,
            'status' => in_array($newStatus, self::STATUSES, true) ? $newStatus : self::STATUS_AVAILABLE,
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
