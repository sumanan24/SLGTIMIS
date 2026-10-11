<?php
/**
 * Facilities tickets, categories, history, evidence, and notifications.
 */
class FacilitiesTicketModel extends Model {
    protected $table = 'facilities_tickets';
    private static $schemaReady = false;

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
        self::$schemaReady = true;
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

    public function nextTicketNumber(): array {
        $year = (int) date('Y');
        $stmt = $this->db->prepare('SELECT COALESCE(MAX(`ticket_seq`), 0) + 1 AS n FROM `facilities_tickets` WHERE `ticket_year` = ?');
        $stmt->bind_param('i', $year);
        $stmt->execute();
        $seq = (int) ($stmt->get_result()->fetch_assoc()['n'] ?? 1);
        return [
            'ticket_number' => sprintf('SLGTI/FAC/%d/%05d', $year, $seq),
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
        if (!empty($filters['overdue'])) {
            $where[] = "t.deadline IS NOT NULL AND t.deadline < CURDATE() AND t.status NOT IN ('closed','cancelled','rejected')";
        }
        if (!empty($filters['due_soon'])) {
            $where[] = "t.deadline IS NOT NULL AND t.deadline BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 2 DAY) AND t.status NOT IN ('closed','cancelled','rejected')";
        }
        if (!empty($filters['unassigned'])) {
            $where[] = "(t.responsible_staff_id IS NULL OR t.responsible_staff_id = '') AND t.status IN ('new','under_review')";
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
                    SUM(deadline IS NOT NULL AND deadline < CURDATE() AND status NOT IN ('closed','cancelled','rejected')) AS overdue_count
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
            'facilities_notifications',
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

    public function notificationsFor(string $staffId, int $limit = 30): array {
        $stmt = $this->db->prepare('SELECT * FROM `facilities_notifications` WHERE `staff_id` = ? ORDER BY `created_at` DESC LIMIT ?');
        $stmt->bind_param('si', $staffId, $limit);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function unreadCount(string $staffId): int {
        $stmt = $this->db->prepare('SELECT COUNT(*) AS c FROM `facilities_notifications` WHERE `staff_id` = ? AND `is_read` = 0');
        $stmt->bind_param('s', $staffId);
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
}
