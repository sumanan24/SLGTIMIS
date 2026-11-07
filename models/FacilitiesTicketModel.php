<?php
/**
 * Facilities tickets, categories, history, evidence, and notifications.
 */
class FacilitiesTicketModel extends Model {
    protected $table = 'facilities_tickets';
    private static $schemaReady = false;

    /** Status groups used by the Pending / In Progress / Resolved / Closed ticket views. */
    public const STATUS_GROUPS = [
        'pending' => ['new', 'under_review', 'assigned', 'accepted', 'reopened', 'on_hold'],
        'in_progress' => ['in_progress'],
        'resolved' => ['completed_pending_verification', 'verified'],
        'closed' => ['closed', 'cancelled', 'rejected'],
    ];

    public function __construct() {
        parent::__construct();
        $this->ensureSchema();
    }

    protected function getPrimaryKey() {
        return 'id';
    }

    public function ensureSchema(): void {
        if (self::$schemaReady) {
            return;
        }
        $sqlFile = BASE_PATH . '/database/facilities.sql';
        if (is_file($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            if ($sql !== false) {
                $chunks = array_filter(array_map('trim', preg_split('/;\s*$/m', $sql)));
                foreach ($chunks as $chunk) {
                    if ($chunk === '' || strpos($chunk, '--') === 0 && strpos($chunk, 'CREATE') === false) {
                        $chunk = trim(preg_replace('/^--.*$/m', '', $chunk));
                    }
                    if ($chunk === '') {
                        continue;
                    }
                    $this->db->query($chunk);
                }
            }
        }
        $this->seedCategories();
        $this->ensureEnhancements();
        self::$schemaReady = true;
    }

    private function ensureEnhancements(): void {
        $columns = [
            'request_type' => "VARCHAR(40) NOT NULL DEFAULT 'maintenance'",
            'routed_role' => 'VARCHAR(40) NULL DEFAULT NULL',
            'routed_staff_id' => 'VARCHAR(64) NULL DEFAULT NULL',
            'acknowledged_at' => 'DATETIME NULL DEFAULT NULL',
            'completed_at' => 'DATETIME NULL DEFAULT NULL',
            'escalation_level' => 'TINYINT UNSIGNED NOT NULL DEFAULT 0',
        ];
        foreach ($columns as $name => $definition) {
            $check = $this->db->query("SHOW COLUMNS FROM `facilities_tickets` LIKE '" . $name . "'");
            if ($check && $check->num_rows > 0) {
                continue;
            }
            $this->db->query('ALTER TABLE `facilities_tickets` ADD COLUMN `' . $name . '` ' . $definition);
        }
        $this->seedRouting();
    }

    private function seedRouting(): void {
        $map = [
            'ict' => 'ict_hod',
            'electrical' => 'electrical_hod',
            'building' => 'construction_hod',
            'plumbing' => 'construction_hod',
            'furniture' => 'facility_officer',
            'transport' => 'facility_officer',
            'cleaning' => 'facility_officer',
            'other' => 'facility_officer',
        ];
        foreach ($map as $slug => $role) {
            $stmt = $this->db->prepare('SELECT `id` FROM `facilities_categories` WHERE `slug` = ? LIMIT 1');
            $stmt->bind_param('s', $slug);
            $stmt->execute();
            $cat = $stmt->get_result()->fetch_assoc();
            if (!$cat) {
                continue;
            }
            $cid = (int) $cat['id'];
            $exists = $this->db->prepare('SELECT `id` FROM `facilities_routes` WHERE `category_id` = ? LIMIT 1');
            $exists->bind_param('i', $cid);
            $exists->execute();
            if ($exists->get_result()->fetch_assoc()) {
                continue;
            }
            $ins = $this->db->prepare('INSERT INTO `facilities_routes` (`category_id`, `route_key`) VALUES (?, ?)');
            $ins->bind_param('is', $cid, $role);
            $ins->execute();
        }
        $sla = ['low' => 14, 'medium' => 7, 'high' => 3, 'urgent' => 1, 'critical' => 1];
        foreach ($sla as $priority => $days) {
            $stmt = $this->db->prepare('INSERT IGNORE INTO `facilities_sla` (`priority`, `target_days`) VALUES (?, ?)');
            $stmt->bind_param('si', $priority, $days);
            $stmt->execute();
        }
        $settings = [
            'unassigned_hours' => '24',
            'facility_escalate_days' => '3',
            'director_escalate_days' => '7',
        ];
        foreach ($settings as $key => $value) {
            $stmt = $this->db->prepare('INSERT IGNORE INTO `facilities_settings` (`setting_key`, `setting_value`) VALUES (?, ?)');
            $stmt->bind_param('ss', $key, $value);
            $stmt->execute();
        }
    }

    private function seedCategories(): void {
        $count = $this->db->query('SELECT COUNT(*) AS c FROM `facilities_categories`');
        $row = $count ? $count->fetch_assoc() : null;
        if ((int) ($row['c'] ?? 0) > 0) {
            return;
        }
        $seed = [
            ['Electrical', 'electrical', ['Light failure', 'Fan failure', 'Power socket', 'Wiring', 'Electrical equipment', 'Other']],
            ['Plumbing', 'plumbing', ['Water leakage', 'Tap', 'Toilet', 'Drainage', 'Water supply', 'Other']],
            ['Building / Civil', 'building', ['Door', 'Window', 'Ceiling', 'Wall', 'Floor', 'Roofing', 'Painting', 'Other']],
            ['ICT / Network', 'ict', ['Computer', 'Printer', 'Network', 'Wi-Fi', 'CCTV', 'Telephone', 'Projector', 'Other']],
            ['Furniture', 'furniture', ['Chair', 'Table', 'Cupboard', 'Desk', 'Repair', 'Replacement']],
            ['Cleaning / Environment', 'cleaning', ['Cleaning', 'Waste disposal', 'Garden', 'Pest control', 'Other']],
            ['Transport', 'transport', ['Vehicle issue', 'Vehicle maintenance', 'Other']],
            ['Other Facilities', 'other', ['General', 'Other']],
        ];
        $sort = 10;
        foreach ($seed as $item) {
            $stmt = $this->db->prepare('INSERT INTO `facilities_categories` (`name`, `slug`, `sort_order`) VALUES (?, ?, ?)');
            $stmt->bind_param('ssi', $item[0], $item[1], $sort);
            $stmt->execute();
            $cid = (int) $this->db->lastInsertId();
            $ss = 10;
            foreach ($item[2] as $sub) {
                $s2 = $this->db->prepare('INSERT INTO `facilities_subcategories` (`category_id`, `name`, `sort_order`) VALUES (?, ?, ?)');
                $s2->bind_param('isi', $cid, $sub, $ss);
                $s2->execute();
                $ss += 10;
            }
            $sort += 10;
        }
    }

    public function lockTicketSequence(): void {
        $this->db->query("SELECT GET_LOCK('slgti_fac_ticket', 8)");
    }

    public function unlockTicketSequence(): void {
        $this->db->query("SELECT RELEASE_LOCK('slgti_fac_ticket')");
    }

    public function nextTicketNumber(): array {
        $year = (int) date('Y');
        $stmt = $this->db->prepare('SELECT COALESCE(MAX(`ticket_seq`), 0) + 1 AS n FROM `facilities_tickets` WHERE `ticket_year` = ?');
        $stmt->bind_param('i', $year);
        $stmt->execute();
        $seq = (int) ($stmt->get_result()->fetch_assoc()['n'] ?? 1);
        return [
            'ticket_number' => sprintf('SLGTI/MNT/%d/%04d', $year, $seq),
            'ticket_year' => $year,
            'ticket_seq' => $seq,
        ];
    }

    public function rawQuery(string $sql) {
        return $this->db->query($sql);
    }

    public function categories(bool $activeOnly = true): array {
        $sql = 'SELECT * FROM `facilities_categories`';
        if ($activeOnly) {
            $sql .= ' WHERE `is_active` = 1';
        }
        $sql .= ' ORDER BY `sort_order`, `name`';
        $res = $this->db->query($sql);
        $rows = [];
        while ($res && ($row = $res->fetch_assoc())) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function subcategories($categoryId = null, bool $activeOnly = true): array {
        $sql = 'SELECT * FROM `facilities_subcategories` WHERE 1=1';
        $params = [];
        $types = '';
        if ($categoryId) {
            $sql .= ' AND `category_id` = ?';
            $params[] = (int) $categoryId;
            $types .= 'i';
        }
        if ($activeOnly) {
            $sql .= ' AND `is_active` = 1';
        }
        $sql .= ' ORDER BY `sort_order`, `name`';
        $stmt = $this->db->prepare($sql);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function saveCategory(array $data, $id = null) {
        if ($id) {
            return $this->db->prepare('UPDATE `facilities_categories` SET `name`=?, `slug`=?, `sort_order`=?, `is_active`=? WHERE `id`=?')
                && $this->runUpdateCategory($data, (int) $id);
        }
        $stmt = $this->db->prepare('INSERT INTO `facilities_categories` (`name`, `slug`, `sort_order`, `is_active`) VALUES (?, ?, ?, ?)');
        $name = $data['name'];
        $slug = $data['slug'];
        $sort = (int) ($data['sort_order'] ?? 100);
        $active = (int) ($data['is_active'] ?? 1);
        $stmt->bind_param('ssii', $name, $slug, $sort, $active);
        $stmt->execute();
        return $this->db->lastInsertId();
    }

    private function runUpdateCategory(array $data, int $id): bool {
        $stmt = $this->db->prepare('UPDATE `facilities_categories` SET `name`=?, `slug`=?, `sort_order`=?, `is_active`=? WHERE `id`=?');
        $name = $data['name'];
        $slug = $data['slug'];
        $sort = (int) ($data['sort_order'] ?? 100);
        $active = (int) ($data['is_active'] ?? 1);
        $stmt->bind_param('ssiii', $name, $slug, $sort, $active, $id);
        return $stmt->execute();
    }

    public function saveSubcategory(array $data, $id = null) {
        $cid = (int) $data['category_id'];
        $name = $data['name'];
        $sort = (int) ($data['sort_order'] ?? 100);
        $active = (int) ($data['is_active'] ?? 1);
        if ($id) {
            $stmt = $this->db->prepare('UPDATE `facilities_subcategories` SET `category_id`=?, `name`=?, `sort_order`=?, `is_active`=? WHERE `id`=?');
            $id = (int) $id;
            $stmt->bind_param('isiii', $cid, $name, $sort, $active, $id);
            return $stmt->execute();
        }
        $stmt = $this->db->prepare('INSERT INTO `facilities_subcategories` (`category_id`, `name`, `sort_order`, `is_active`) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('isii', $cid, $name, $sort, $active);
        $stmt->execute();
        return $this->db->lastInsertId();
    }

    public function getDetailed($id): ?array {
        $sql = "SELECT t.*,
                    c.name AS category_name,
                    sc.name AS subcategory_name,
                    rd.department_name AS requester_department_name,
                    ad.department_name AS responsible_department_name,
                    rs.staff_name AS requester_name,
                    asf.staff_name AS responsible_name,
                    asf.staff_position AS responsible_position
                FROM `facilities_tickets` t
                LEFT JOIN `facilities_categories` c ON c.id = t.category_id
                LEFT JOIN `facilities_subcategories` sc ON sc.id = t.subcategory_id
                LEFT JOIN `department` rd ON rd.department_id = t.requester_department_id
                LEFT JOIN `department` ad ON ad.department_id = t.responsible_department_id
                LEFT JOIN `staff` rs ON rs.staff_id = t.requester_staff_id
                LEFT JOIN `staff` asf ON asf.staff_id = t.responsible_staff_id
                WHERE t.id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $id = (int) $id;
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: null;
    }

    public function search(array $filters, int $page = 1, int $perPage = 20): array {
        $where = ['1=1'];
        $params = [];
        $types = '';
        $this->applyFilters($filters, $where, $params, $types);
        $whereSql = implode(' AND ', $where);
        $countSql = "SELECT COUNT(*) AS c FROM `facilities_tickets` t WHERE {$whereSql}";
        $stmt = $this->db->prepare($countSql);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $total = (int) ($stmt->get_result()->fetch_assoc()['c'] ?? 0);

        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT t.*, c.name AS category_name, sc.name AS subcategory_name,
                    rd.department_name AS requester_department_name,
                    ad.department_name AS responsible_department_name,
                    rs.staff_name AS requester_name,
                    asf.staff_name AS responsible_name
                FROM `facilities_tickets` t
                LEFT JOIN `facilities_categories` c ON c.id = t.category_id
                LEFT JOIN `facilities_subcategories` sc ON sc.id = t.subcategory_id
                LEFT JOIN `department` rd ON rd.department_id = t.requester_department_id
                LEFT JOIN `department` ad ON ad.department_id = t.responsible_department_id
                LEFT JOIN `staff` rs ON rs.staff_id = t.requester_staff_id
                LEFT JOIN `staff` asf ON asf.staff_id = t.responsible_staff_id
                WHERE {$whereSql}
                ORDER BY FIELD(t.current_priority,'critical','urgent','high','medium','low'), t.created_at DESC
                LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $this->db->prepare($sql);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $rows[] = $this->withDeadlineState($row);
        }
        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
    }

    public function listForExport(array $filters): array {
        $where = ['1=1'];
        $params = [];
        $types = '';
        $this->applyFilters($filters, $where, $params, $types);
        $sql = "SELECT t.*, c.name AS category_name, sc.name AS subcategory_name,
                    rd.department_name AS requester_department_name,
                    ad.department_name AS responsible_department_name,
                    rs.staff_name AS requester_name,
                    asf.staff_name AS responsible_name
                FROM `facilities_tickets` t
                LEFT JOIN `facilities_categories` c ON c.id = t.category_id
                LEFT JOIN `facilities_subcategories` sc ON sc.id = t.subcategory_id
                LEFT JOIN `department` rd ON rd.department_id = t.requester_department_id
                LEFT JOIN `department` ad ON ad.department_id = t.responsible_department_id
                LEFT JOIN `staff` rs ON rs.staff_id = t.requester_staff_id
                LEFT JOIN `staff` asf ON asf.staff_id = t.responsible_staff_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY t.created_at DESC";
        $stmt = $this->db->prepare($sql);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $rows[] = $this->withDeadlineState($row);
        }
        return $rows;
    }

    private function applyFilters(array $filters, array &$where, array &$params, string &$types): void {
        $map = [
            'status' => ['t.status = ?', 's'],
            'priority' => ['t.current_priority = ?', 's'],
            'category_id' => ['t.category_id = ?', 'i'],
            'request_type' => ['t.request_type = ?', 's'],
            'department_id' => ['(t.requester_department_id = ? OR t.responsible_department_id = ?)', 'ss'],
            'staff_id' => ['(t.requester_staff_id = ? OR t.responsible_staff_id = ?)', 'ss'],
            'requester_staff_id' => ['t.requester_staff_id = ?', 's'],
            'responsible_staff_id' => ['t.responsible_staff_id = ?', 's'],
            'responsible_department_id' => ['t.responsible_department_id = ?', 's'],
            'location' => ['t.location LIKE ?', 's'],
        ];
        foreach ($map as $key => $spec) {
            if (!isset($filters[$key]) || $filters[$key] === '' || $filters[$key] === null) {
                continue;
            }
            $where[] = $spec[0];
            $val = $filters[$key];
            if ($key === 'location') {
                $val = '%' . $val . '%';
            }
            if ($spec[1] === 'ss') {
                $params[] = $val;
                $params[] = $val;
                $types .= 'ss';
            } elseif ($spec[1] === 'i') {
                $params[] = (int) $val;
                $types .= 'i';
            } else {
                $params[] = $val;
                $types .= 's';
            }
        }
        if (!empty($filters['q'])) {
            $q = '%' . $filters['q'] . '%';
            $where[] = '(t.ticket_number LIKE ? OR t.title LIKE ? OR t.location LIKE ? OR t.requester_staff_id LIKE ? OR t.responsible_staff_id LIKE ?)';
            array_push($params, $q, $q, $q, $q, $q);
            $types .= 'sssss';
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'DATE(t.created_at) >= ?';
            $params[] = $filters['date_from'];
            $types .= 's';
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'DATE(t.created_at) <= ?';
            $params[] = $filters['date_to'];
            $types .= 's';
        }
        if (!empty($filters['group']) && isset(self::STATUS_GROUPS[$filters['group']])) {
            $quoted = array_map(static function ($s) { return "'" . $s . "'"; }, self::STATUS_GROUPS[$filters['group']]);
            $where[] = 't.status IN (' . implode(',', $quoted) . ')';
        }
        if (!empty($filters['overdue'])) {
            $where[] = "t.deadline IS NOT NULL AND t.deadline < CURDATE() AND t.status NOT IN ('closed','cancelled','rejected')";
        }
        if (!empty($filters['due_soon'])) {
            $where[] = "t.deadline IS NOT NULL AND t.deadline BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 2 DAY) AND t.status NOT IN ('closed','cancelled','rejected')";
        }
        if (!empty($filters['unassigned'])) {
            $where[] = "(t.responsible_staff_id IS NULL OR t.responsible_staff_id = '') AND t.status IN ('new','under_review')";
        }
        if (!empty($filters['escalated'])) {
            $where[] = 't.escalation_level > 0';
        }
        if (!empty($filters['assigned_to_me']) && !empty($filters['scope_staff_id'])) {
            $where[] = 't.responsible_staff_id = ?';
            $params[] = $filters['scope_staff_id'];
            $types .= 's';
        }
        if (!empty($filters['scope_staff_id']) && empty($filters['officer_all'])) {
            $sid = $filters['scope_staff_id'];
            $dept = $filters['scope_department_id'] ?? '';
            if (!empty($filters['hod_scope']) && $dept !== '') {
                $where[] = '(t.requester_staff_id = ? OR t.responsible_staff_id = ? OR t.requester_department_id = ? OR t.responsible_department_id = ?)';
                array_push($params, $sid, $sid, $dept, $dept);
                $types .= 'ssss';
            } else {
                $where[] = '(t.requester_staff_id = ? OR t.responsible_staff_id = ?)';
                array_push($params, $sid, $sid);
                $types .= 'ss';
            }
        }
    }

    public function withDeadlineState(array $row): array {
        $closed = in_array($row['status'] ?? '', ['closed', 'cancelled', 'rejected'], true);
        $deadline = $row['deadline'] ?? null;
        $row['is_overdue'] = 0;
        $row['overdue_days'] = 0;
        $row['due_state'] = 'none';
        if ($deadline && !$closed) {
            $days = (int) floor((strtotime(date('Y-m-d')) - strtotime($deadline)) / 86400);
            if ($days > 0) {
                $row['is_overdue'] = 1;
                $row['overdue_days'] = $days;
                $row['due_state'] = 'overdue';
            } elseif ($days >= -2) {
                $row['due_state'] = 'due_soon';
            } else {
                $row['due_state'] = 'on_time';
            }
        }
        return $row;
    }

    public function dashboardCounts(array $filters = []): array {
        $where = ['1=1'];
        $params = [];
        $types = '';
        $this->applyFilters($filters, $where, $params, $types);
        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(status = 'new') AS new_count,
                    SUM(status = 'under_review') AS under_review,
                    SUM(status IN ('assigned','accepted','reopened')) AS assigned_count,
                    SUM(status = 'in_progress') AS in_progress,
                    SUM(status = 'completed_pending_verification') AS pending_verification,
                    SUM(status IN ('completed_pending_verification','verified')) AS completed_count,
                    SUM(status = 'closed') AS closed_count,
                    SUM(status = 'reopened') AS reopened_count,
                    SUM(current_priority = 'critical') AS critical_count,
                    SUM(deadline IS NOT NULL AND deadline < CURDATE() AND status NOT IN ('closed','cancelled','rejected')) AS overdue_count,
                    SUM(status = 'on_hold') AS on_hold,
                    SUM(escalation_level > 0) AS escalated_count
                FROM `facilities_tickets` t
                WHERE " . implode(' AND ', $where);
        $stmt = $this->db->prepare($sql);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: [];
    }

    public function statusBreakdown(array $filters = []): array {
        return $this->groupCount('status', $filters);
    }

    public function priorityBreakdown(array $filters = []): array {
        return $this->groupCount('current_priority', $filters);
    }

    private function groupCount(string $column, array $filters): array {
        $where = ['1=1'];
        $params = [];
        $types = '';
        $this->applyFilters($filters, $where, $params, $types);
        $sql = "SELECT t.`{$column}` AS k, COUNT(*) AS c FROM `facilities_tickets` t WHERE " . implode(' AND ', $where) . " GROUP BY t.`{$column}`";
        $stmt = $this->db->prepare($sql);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $out = [];
        while ($row = $res->fetch_assoc()) {
            $out[$row['k']] = (int) $row['c'];
        }
        return $out;
    }

    public function departmentPerformance(): array {
        $sql = "SELECT COALESCE(t.responsible_department_id, t.requester_department_id) AS department_id,
                    d.department_name,
                    COUNT(*) AS total,
                    SUM(t.status = 'closed') AS completed,
                    SUM(t.status = 'in_progress') AS in_progress,
                    SUM(t.status NOT IN ('closed','cancelled','rejected')) AS pending,
                    SUM(t.deadline IS NOT NULL AND t.deadline < CURDATE() AND t.status NOT IN ('closed','cancelled','rejected')) AS overdue,
                    AVG(CASE WHEN t.closed_at IS NOT NULL THEN TIMESTAMPDIFF(HOUR, t.created_at, t.closed_at) END) AS avg_hours
                FROM `facilities_tickets` t
                LEFT JOIN `department` d ON d.department_id = COALESCE(t.responsible_department_id, t.requester_department_id)
                GROUP BY department_id, d.department_name
                ORDER BY total DESC";
        $res = $this->db->query($sql);
        $rows = [];
        while ($res && ($row = $res->fetch_assoc())) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function staffWorkload(?string $departmentId = null): array {
        $sql = "SELECT s.staff_id, s.staff_name, s.staff_position, s.department_id, d.department_name,
                    SUM(t.status NOT IN ('closed','cancelled','rejected')) AS open_tickets,
                    SUM(t.status = 'in_progress') AS in_progress,
                    SUM(t.status = 'closed') AS completed,
                    SUM(t.deadline IS NOT NULL AND t.deadline < CURDATE() AND t.status NOT IN ('closed','cancelled','rejected')) AS overdue
                FROM `staff` s
                LEFT JOIN `department` d ON d.department_id = s.department_id
                LEFT JOIN `facilities_tickets` t ON t.responsible_staff_id = s.staff_id
                WHERE 1=1";
        if ($departmentId) {
            $sql .= ' AND s.department_id = ?';
        }
        $sql .= ' GROUP BY s.staff_id, s.staff_name, s.staff_position, s.department_id, d.department_name
                  HAVING open_tickets > 0 OR completed > 0
                  ORDER BY open_tickets DESC, s.staff_name';
        if ($departmentId) {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('s', $departmentId);
            $stmt->execute();
            $res = $stmt->get_result();
        } else {
            $res = $this->db->query($sql);
        }
        $rows = [];
        while ($res && ($row = $res->fetch_assoc())) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function staffPickerRows(?string $departmentId = null): array {
        $sql = "SELECT s.staff_id, s.staff_name, s.staff_position, s.department_id, d.department_name,
                    SUM(CASE WHEN t.id IS NOT NULL AND t.status NOT IN ('closed','cancelled','rejected') THEN 1 ELSE 0 END) AS open_tickets,
                    SUM(CASE WHEN t.id IS NOT NULL AND t.deadline IS NOT NULL AND t.deadline < CURDATE() AND t.status NOT IN ('closed','cancelled','rejected') THEN 1 ELSE 0 END) AS overdue
                FROM `staff` s
                LEFT JOIN `department` d ON d.department_id = s.department_id
                LEFT JOIN `facilities_tickets` t ON t.responsible_staff_id = s.staff_id
                WHERE (s.staff_status IS NULL OR s.staff_status = '' OR s.staff_status IN ('Active','Permanent','On Contract','1'))";
        if ($departmentId) {
            $sql .= ' AND s.department_id = ?';
        }
        $sql .= ' GROUP BY s.staff_id, s.staff_name, s.staff_position, s.department_id, d.department_name
                  ORDER BY s.staff_name';
        if ($departmentId) {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('s', $departmentId);
            $stmt->execute();
            $res = $stmt->get_result();
        } else {
            $res = $this->db->query($sql);
        }
        $rows = [];
        while ($res && ($row = $res->fetch_assoc())) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function monthlyTrend(int $months = 6): array {
        $sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym,
                    COUNT(*) AS created_count,
                    SUM(status IN ('completed_pending_verification','verified','closed')) AS completed_count,
                    SUM(status = 'closed') AS closed_count,
                    SUM(deadline IS NOT NULL AND deadline < CURDATE() AND status NOT IN ('closed','cancelled','rejected')) AS overdue_count
                FROM `facilities_tickets`
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
                GROUP BY ym
                ORDER BY ym";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $months);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function averageResolutionHours(): ?float {
        $res = $this->db->query("SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, closed_at)) AS h FROM `facilities_tickets` WHERE closed_at IS NOT NULL");
        $row = $res ? $res->fetch_assoc() : null;
        return $row && $row['h'] !== null ? (float) $row['h'] : null;
    }

    public function children(string $table, int $ticketId, string $order = 'id ASC'): array {
        $allowed = [
            'facilities_assignments', 'facilities_progress_updates', 'facilities_evidence',
            'facilities_comments', 'facilities_status_history', 'facilities_priority_history',
            'facilities_deadline_history', 'facilities_verifications', 'facilities_activity',
        ];
        if (!in_array($table, $allowed, true)) {
            return [];
        }
        $stmt = $this->db->prepare("SELECT * FROM `{$table}` WHERE `ticket_id` = ? ORDER BY {$order}");
        $stmt->bind_param('i', $ticketId);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function addChild(string $table, array $data) {
        $allowed = [
            'facilities_assignments', 'facilities_progress_updates', 'facilities_evidence',
            'facilities_comments', 'facilities_status_history', 'facilities_priority_history',
            'facilities_deadline_history', 'facilities_verifications', 'facilities_activity',
            'facilities_notifications', 'facilities_escalations', 'facilities_feedback', 'facilities_materials',
        ];
        if (!in_array($table, $allowed, true)) {
            return false;
        }
        $old = $this->table;
        $this->table = $table;
        $id = $this->create($data);
        $this->table = $old;
        return $id;
    }

    public function updateTicket(int $id, array $data): bool {
        return $this->update($id, $data);
    }

    public function clearCurrentAssignments(int $ticketId): void {
        $stmt = $this->db->prepare('UPDATE `facilities_assignments` SET `is_current` = 0 WHERE `ticket_id` = ?');
        $stmt->bind_param('i', $ticketId);
        $stmt->execute();
    }

    /**
     * SQL condition (alias t = ticket) for tickets that concern one person:
     * they raised it, are responsible for it, were routed it, or are/were assigned it (or assigned it).
     * Uses six "?" placeholders, all bound to the same staff id.
     */
    private const RELATED_TICKET_SQL = "(t.requester_staff_id = ? OR t.responsible_staff_id = ? OR t.routed_staff_id = ?
        OR EXISTS (SELECT 1 FROM `facilities_assignments` a WHERE a.ticket_id = t.id AND (a.staff_id = ? OR a.assigned_by = ?)))";

    /** Notices for the logged-in person, only for tickets that are related to them. */
    public function notificationsFor(string $staffId, int $limit = 30): array {
        $sql = 'SELECT n.*, t.ticket_number, t.title AS ticket_title, t.status AS ticket_status
                FROM `facilities_notifications` n
                JOIN `facilities_tickets` t ON t.id = n.ticket_id
                WHERE n.staff_id = ? AND ' . self::RELATED_TICKET_SQL . '
                ORDER BY n.created_at DESC, n.id DESC LIMIT ?';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ssssssi', $staffId, $staffId, $staffId, $staffId, $staffId, $staffId, $limit);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function unreadCount(string $staffId): int {
        $sql = 'SELECT COUNT(*) AS c
                FROM `facilities_notifications` n
                JOIN `facilities_tickets` t ON t.id = n.ticket_id
                WHERE n.staff_id = ? AND n.is_read = 0 AND ' . self::RELATED_TICKET_SQL;
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ssssss', $staffId, $staffId, $staffId, $staffId, $staffId, $staffId);
        $stmt->execute();
        return (int) ($stmt->get_result()->fetch_assoc()['c'] ?? 0);
    }

    public function markNotificationsRead(string $staffId): void {
        $stmt = $this->db->prepare('UPDATE `facilities_notifications` SET `is_read` = 1 WHERE `staff_id` = ?');
        $stmt->bind_param('s', $staffId);
        $stmt->execute();
    }

    public function hasNotificationToday(int $ticketId, string $event): bool {
        $stmt = $this->db->prepare('SELECT id FROM `facilities_notifications` WHERE `ticket_id` = ? AND `event_type` = ? AND DATE(`created_at`) = CURDATE() LIMIT 1');
        $stmt->bind_param('is', $ticketId, $event);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res && $res->num_rows > 0;
    }

    public function evidenceByType(int $ticketId): array {
        $rows = $this->children('facilities_evidence', $ticketId, 'uploaded_at ASC');
        $grouped = ['initial' => [], 'before' => [], 'during' => [], 'after' => [], 'completion' => [], 'invoice' => [], 'service_report' => [], 'other' => []];
        foreach ($rows as $row) {
            $type = $row['evidence_type'] ?? 'other';
            if (!isset($grouped[$type])) {
                $grouped[$type] = [];
            }
            $grouped[$type][] = $row;
        }
        return $grouped;
    }

    public function hasCompletionEvidence(int $ticketId): bool {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS c FROM `facilities_evidence` WHERE `ticket_id` = ? AND `evidence_type` IN ('after','completion','during','invoice','service_report')");
        $stmt->bind_param('i', $ticketId);
        $stmt->execute();
        return (int) ($stmt->get_result()->fetch_assoc()['c'] ?? 0) > 0;
    }

    public function approachingDeadlines(int $days = 2): array {
        $sql = "SELECT t.* FROM `facilities_tickets` t
                WHERE t.deadline IS NOT NULL
                  AND t.deadline BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                  AND t.status NOT IN ('closed','cancelled','rejected')";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $days);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $rows[] = $this->withDeadlineState($row);
        }
        return $rows;
    }

    public function setting(string $key, string $default): string {
        $stmt = $this->db->prepare('SELECT `setting_value` FROM `facilities_settings` WHERE `setting_key` = ? LIMIT 1');
        $stmt->bind_param('s', $key);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ? (string) $row['setting_value'] : $default;
    }

    public function saveSetting(string $key, string $value): void {
        $stmt = $this->db->prepare('INSERT INTO `facilities_settings` (`setting_key`, `setting_value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`)');
        $stmt->bind_param('ss', $key, $value);
        $stmt->execute();
    }

    public function slaDays(string $priority): int {
        $stmt = $this->db->prepare('SELECT `target_days` FROM `facilities_sla` WHERE `priority` = ? LIMIT 1');
        $stmt->bind_param('s', $priority);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ? (int) $row['target_days'] : 7;
    }

    public function saveSla(string $priority, int $days): void {
        $stmt = $this->db->prepare('INSERT INTO `facilities_sla` (`priority`, `target_days`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `target_days` = VALUES(`target_days`)');
        $stmt->bind_param('si', $priority, $days);
        $stmt->execute();
    }

    public function routes(): array {
        $sql = 'SELECT r.*, c.name AS category_name, c.slug, s.staff_name
                FROM `facilities_routes` r
                JOIN `facilities_categories` c ON c.id = r.category_id
                LEFT JOIN `staff` s ON s.staff_id = r.staff_id
                ORDER BY c.sort_order, c.name';
        $res = $this->db->query($sql);
        $rows = [];
        while ($res && ($row = $res->fetch_assoc())) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function routeForCategory(int $categoryId): ?array {
        $stmt = $this->db->prepare('SELECT * FROM `facilities_routes` WHERE `category_id` = ? LIMIT 1');
        $stmt->bind_param('i', $categoryId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: null;
    }

    public function saveRouteStaff(int $categoryId, string $staffId): void {
        $this->ensureRoute($categoryId);
        if ($staffId === '') {
            $stmt = $this->db->prepare('UPDATE `facilities_routes` SET `staff_id` = NULL WHERE `category_id` = ?');
            $stmt->bind_param('i', $categoryId);
            $stmt->execute();
            return;
        }
        $stmt = $this->db->prepare('UPDATE `facilities_routes` SET `staff_id` = ? WHERE `category_id` = ?');
        $stmt->bind_param('si', $staffId, $categoryId);
        $stmt->execute();
    }

    /** Make sure a category has a routing row (default: Facility Officer). */
    public function ensureRoute(int $categoryId, string $routeKey = 'facility_officer'): void {
        $stmt = $this->db->prepare('INSERT IGNORE INTO `facilities_routes` (`category_id`, `route_key`) VALUES (?, ?)');
        $stmt->bind_param('is', $categoryId, $routeKey);
        $stmt->execute();
    }

    /** Every category (active or not) with its responsible person and usage counts. */
    public function categoriesWithRoutes(): array {
        $sql = 'SELECT c.*, r.route_key, r.staff_id, s.staff_name,
                    (SELECT COUNT(*) FROM `facilities_tickets` t WHERE t.category_id = c.id) AS ticket_count,
                    (SELECT COUNT(*) FROM `facilities_subcategories` sc WHERE sc.category_id = c.id) AS sub_count
                FROM `facilities_categories` c
                LEFT JOIN `facilities_routes` r ON r.category_id = c.id
                LEFT JOIN `staff` s ON s.staff_id = r.staff_id
                ORDER BY c.sort_order, c.name';
        $res = $this->db->query($sql);
        $rows = [];
        while ($res && ($row = $res->fetch_assoc())) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function categoryByName(string $name, int $exceptId = 0): ?array {
        $stmt = $this->db->prepare('SELECT * FROM `facilities_categories` WHERE LOWER(`name`) = LOWER(?) AND `id` <> ? LIMIT 1');
        $stmt->bind_param('si', $name, $exceptId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: null;
    }

    /** A slug that is not used by another category. */
    public function uniqueCategorySlug(string $name, int $exceptId = 0): string {
        $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $name), '-'));
        if ($slug === '') {
            $slug = 'category-' . substr(md5($name), 0, 8);
        }
        $base = $slug;
        $i = 2;
        while (true) {
            $stmt = $this->db->prepare('SELECT `id` FROM `facilities_categories` WHERE `slug` = ? AND `id` <> ? LIMIT 1');
            $stmt->bind_param('si', $slug, $exceptId);
            $stmt->execute();
            if (!$stmt->get_result()->fetch_assoc()) {
                return $slug;
            }
            $slug = $base . '-' . $i++;
        }
    }

    public function categoryTicketCount(int $categoryId): int {
        $stmt = $this->db->prepare('SELECT COUNT(*) AS c FROM `facilities_tickets` WHERE `category_id` = ?');
        $stmt->bind_param('i', $categoryId);
        $stmt->execute();
        return (int) ($stmt->get_result()->fetch_assoc()['c'] ?? 0);
    }

    /** Delete a category that has no tickets, with its subcategories and routing row. */
    public function deleteCategory(int $categoryId): bool {
        if ($this->categoryTicketCount($categoryId) > 0) {
            return false;
        }
        foreach (['DELETE FROM `facilities_subcategories` WHERE `category_id` = ?', 'DELETE FROM `facilities_routes` WHERE `category_id` = ?'] as $sql) {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $categoryId);
            $stmt->execute();
        }
        $stmt = $this->db->prepare('DELETE FROM `facilities_categories` WHERE `id` = ?');
        $stmt->bind_param('i', $categoryId);
        return $stmt->execute();
    }

    /** Staff mobile number (staff_pno) or an empty string. */
    public function staffPhone(string $staffId): string {
        $stmt = $this->db->prepare('SELECT `staff_pno` FROM `staff` WHERE `staff_id` = ? LIMIT 1');
        $stmt->bind_param('s', $staffId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return trim((string) ($row['staff_pno'] ?? ''));
    }

    public function staffDepartmentId(string $staffId): string {
        $stmt = $this->db->prepare('SELECT `department_id` FROM `staff` WHERE `staff_id` = ? LIMIT 1');
        $stmt->bind_param('s', $staffId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return (string) ($row['department_id'] ?? '');
    }

    public function findHodByDepartmentLike(array $likes): ?array {
        if (!$likes) {
            return null;
        }
        $where = [];
        $params = [];
        $types = '';
        foreach ($likes as $like) {
            $where[] = 'd.department_name LIKE ?';
            $params[] = $like;
            $types .= 's';
        }
        $sql = "SELECT s.staff_id, s.department_id
                FROM `staff` s
                JOIN `department` d ON d.department_id = s.department_id
                WHERE s.staff_position = 'HOD' AND (" . implode(' OR ', $where) . ")
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: null;
    }

    public function findStaffByRoles(array $roles): array {
        if (!$roles) {
            return [];
        }
        $marks = implode(',', array_fill(0, count($roles), '?'));
        $sql = "SELECT `user_name` AS staff_id FROM `user` WHERE `user_active` = 1 AND UPPER(`staff_position_type_id`) IN ({$marks})";
        $stmt = $this->db->prepare($sql);
        $types = str_repeat('s', count($roles));
        $upper = array_map('strtoupper', $roles);
        $stmt->bind_param($types, ...$upper);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row['staff_id'];
        }
        return $rows;
    }

    public function staffOptions(): array {
        $res = $this->db->query('SELECT `staff_id`, `staff_name`, `department_id` FROM `staff` ORDER BY `staff_name` LIMIT 500');
        $rows = [];
        while ($res && ($row = $res->fetch_assoc())) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function feedbackFor(int $ticketId): ?array {
        $stmt = $this->db->prepare('SELECT * FROM `facilities_feedback` WHERE `ticket_id` = ? LIMIT 1');
        $stmt->bind_param('i', $ticketId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: null;
    }

    public function satisfactionAverage(): ?float {
        $res = $this->db->query('SELECT AVG(`overall_rating`) AS avg_rating, COUNT(*) AS c FROM `facilities_feedback`');
        $row = $res ? $res->fetch_assoc() : null;
        if (!$row || (int) $row['c'] < 1) {
            return null;
        }
        return round((float) $row['avg_rating'], 2);
    }

    public function categoryBreakdown(array $filters = []): array {
        $where = ['1=1'];
        $params = [];
        $types = '';
        $this->applyFilters($filters, $where, $params, $types);
        $sql = 'SELECT c.name AS k, COUNT(*) AS c
                FROM `facilities_tickets` t
                LEFT JOIN `facilities_categories` c ON c.id = t.category_id
                WHERE ' . implode(' AND ', $where) . '
                GROUP BY c.name
                ORDER BY c DESC';
        $stmt = $this->db->prepare($sql);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $out = [];
        while ($row = $res->fetch_assoc()) {
            $out[$row['k'] ?: 'Uncategorised'] = (int) $row['c'];
        }
        return $out;
    }

    public function requestTypeBreakdown(array $filters = []): array {
        $where = ['1=1'];
        $params = [];
        $types = '';
        $this->applyFilters($filters, $where, $params, $types);
        $sql = 'SELECT t.request_type AS k, COUNT(*) AS c FROM `facilities_tickets` t WHERE ' . implode(' AND ', $where) . ' GROUP BY t.request_type';
        $stmt = $this->db->prepare($sql);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $out = [];
        while ($row = $res->fetch_assoc()) {
            $out[$row['k'] ?: 'maintenance'] = (int) $row['c'];
        }
        return $out;
    }

    public function escalationCandidates(string $sql): array {
        $res = $this->db->query($sql);
        $rows = [];
        while ($res && ($row = $res->fetch_assoc())) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function hasEscalation(int $ticketId, int $level): bool {
        $stmt = $this->db->prepare('SELECT `id` FROM `facilities_escalations` WHERE `ticket_id` = ? AND `level` = ? LIMIT 1');
        $stmt->bind_param('ii', $ticketId, $level);
        $stmt->execute();
        return (bool) $stmt->get_result()->fetch_assoc();
    }
}
