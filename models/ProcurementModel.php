<?php
/**
 * Procurement module data access (tables prefixed proc_).
 * Every query uses prepared statements; failures throw RuntimeException.
 */
class ProcurementModel extends Model {
    private static $schemaReady = false;

    // Workflow statuses
    const ST_DRAFT = 'draft';
    const ST_PENDING_DIRECTOR = 'pending_director';
    const ST_RETURNED = 'returned';
    const ST_REJECTED = 'rejected';
    const ST_PENDING_ACCOUNTANT = 'pending_accountant';
    const ST_PENDING_PURCHASE = 'pending_purchase';
    const ST_IN_PROCUREMENT = 'in_procurement';
    const ST_PENDING_VERIFICATION = 'pending_verification';
    const ST_VERIFIED = 'verified';
    const ST_CLOSED = 'closed';
    const ST_CANCELLED = 'cancelled';

    const STATUS_LABELS = [
        'draft' => 'Draft',
        'pending_director' => 'Pending Director',
        'returned' => 'Returned',
        'rejected' => 'Rejected',
        'pending_accountant' => 'Pending Accountant',
        'pending_purchase' => 'Pending Purchase',
        'in_procurement' => 'In Procurement',
        'pending_verification' => 'Pending Verification',
        'verified' => 'Verified',
        'closed' => 'Closed',
        'cancelled' => 'Cancelled',
    ];

    /** Statuses after both approvals, where purchasing may happen. */
    const PURCHASE_STATUSES = ['pending_purchase', 'in_procurement', 'pending_verification', 'verified', 'closed'];

    public function __construct() {
        parent::__construct();
        $this->table = 'proc_requests';
        $this->ensureSchema();
    }

    protected function getPrimaryKey() {
        return 'id';
    }

    public function ensureSchema(): void {
        if (self::$schemaReady) {
            return;
        }
        $file = BASE_PATH . '/database/procurement.sql';
        if (is_file($file)) {
            $sql = file_get_contents($file);
            if ($sql !== false) {
                foreach (array_filter(array_map('trim', preg_split('/;\s*$/m', $sql))) as $chunk) {
                    $chunk = trim(preg_replace('/^--.*$/m', '', $chunk));
                    if ($chunk === '') {
                        continue;
                    }
                    try {
                        $this->db->query($chunk);
                    } catch (Throwable $e) {
                        // The position type seed is optional; never block the module on it
                        error_log('ProcurementModel::ensureSchema ' . $e->getMessage());
                    }
                }
            }
        }
        self::$schemaReady = true;
    }

    /* ------------------------------------------------------------------ low level */

    private function conn(): mysqli {
        return $this->db->getConnection();
    }

    /** Prepare + bind + execute. Types are detected from the PHP value when not given. */
    private function run(string $sql, array $params = []): mysqli_stmt {
        $stmt = $this->conn()->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException('Database error: ' . $this->conn()->error);
        }
        if ($params) {
            $types = '';
            foreach ($params as $p) {
                $types .= is_int($p) ? 'i' : (is_float($p) ? 'd' : 's');
            }
            $stmt->bind_param($types, ...$params);
        }
        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            throw new RuntimeException('Database error: ' . $err);
        }
        return $stmt;
    }

    public function all_(string $sql, array $params = []): array {
        $stmt = $this->run($sql, $params);
        $res = $stmt->get_result();
        $rows = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        $stmt->close();
        return $rows;
    }

    public function one_(string $sql, array $params = []): ?array {
        $rows = $this->all_($sql, $params);
        return $rows ? $rows[0] : null;
    }

    public function exec_(string $sql, array $params = []): int {
        $stmt = $this->run($sql, $params);
        $n = $stmt->affected_rows;
        $stmt->close();
        return (int) $n;
    }

    public function insertId(): int {
        return (int) $this->conn()->insert_id;
    }

    public function begin(): void {
        $this->conn()->begin_transaction();
    }

    public function commit(): void {
        $this->conn()->commit();
    }

    public function rollback(): void {
        try {
            $this->conn()->rollback();
        } catch (Throwable $e) {
            // nothing to roll back
        }
    }

    private function now(): string {
        return date('Y-m-d H:i:s');
    }

    /* ------------------------------------------------------------------ requests */

    /** Next request number (PR/2026/00001). Must run inside the caller's transaction. */
    public function nextNumber(): array {
        $year = (int) date('Y');
        $this->exec_('INSERT INTO proc_counters (`year`, last_seq) VALUES (?, LAST_INSERT_ID(1)) ON DUPLICATE KEY UPDATE last_seq = LAST_INSERT_ID(last_seq + 1)', [$year]);
        $seq = $this->insertId();
        return [$year, $seq, sprintf('PR/%d/%05d', $year, $seq)];
    }

    public function insertRequest(array $d): int {
        list($year, $seq, $no) = $this->nextNumber();
        $now = $this->now();
        $this->exec_(
            'INSERT INTO proc_requests (request_no, request_year, request_seq, title, category, priority, department_id, justification, required_date, estimated_total, status, requested_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$no, $year, $seq, $d['title'], $d['category'], $d['priority'], $d['department_id'], $d['justification'], $d['required_date'], (float) $d['estimated_total'], self::ST_DRAFT, $d['requested_by'], $now, $now]
        );
        return $this->insertId();
    }

    public function updateFields(int $id, array $fields): void {
        if (!$fields) {
            return;
        }
        $sets = [];
        $params = [];
        foreach ($fields as $col => $val) {
            if (!preg_match('/^[a-z_]+$/', $col)) {
                throw new InvalidArgumentException('Bad column');
            }
            $sets[] = "`$col` = ?";
            $params[] = $val;
        }
        $sets[] = '`updated_at` = ?';
        $params[] = $this->now();
        $params[] = $id;
        $this->exec_('UPDATE proc_requests SET ' . implode(', ', $sets) . ' WHERE id = ?', $params);
    }

    /** SQL cannot bind NULL through the typed helper above, so NULL goes through this form. */
    public function updateNullable(int $id, array $fields): void {
        $sets = [];
        $params = [];
        foreach ($fields as $col => $val) {
            if (!preg_match('/^[a-z_]+$/', $col)) {
                throw new InvalidArgumentException('Bad column');
            }
            if ($val === null) {
                $sets[] = "`$col` = NULL";
            } else {
                $sets[] = "`$col` = ?";
                $params[] = $val;
            }
        }
        $sets[] = '`updated_at` = ?';
        $params[] = $this->now();
        $params[] = $id;
        $this->exec_('UPDATE proc_requests SET ' . implode(', ', $sets) . ' WHERE id = ?', $params);
    }

    public function get(int $id, bool $lock = false): ?array {
        return $this->one_(
            'SELECT r.*, d.department_name, rs.staff_name AS requester_name, rs.staff_pno AS requester_phone,
                    dn.staff_name AS director_name, an.staff_name AS accountant_name,
                    ps.staff_name AS purchase_started_name, pb.staff_name AS purchase_name,
                    vn.staff_name AS verified_name, cn.staff_name AS closed_name
             FROM proc_requests r
             LEFT JOIN department d ON d.department_id = r.department_id
             LEFT JOIN staff rs ON rs.staff_id = r.requested_by
             LEFT JOIN staff dn ON dn.staff_id = r.director_by
             LEFT JOIN staff an ON an.staff_id = r.accountant_by
             LEFT JOIN staff ps ON ps.staff_id = r.purchase_started_by
             LEFT JOIN staff pb ON pb.staff_id = r.purchase_by
             LEFT JOIN staff vn ON vn.staff_id = r.verified_by
             LEFT JOIN staff cn ON cn.staff_id = r.closed_by
             WHERE r.id = ? LIMIT 1' . ($lock ? ' FOR UPDATE' : ''),
            [$id]
        );
    }

    /** Row lock used by every workflow step so two clicks cannot move a request twice. */
    public function lockRow(int $id): ?array {
        return $this->one_('SELECT * FROM proc_requests WHERE id = ? FOR UPDATE', [$id]);
    }

    /* ------------------------------------------------------------------ items */

    public function items(int $requestId): array {
        return $this->all_('SELECT * FROM proc_items WHERE request_id = ? ORDER BY line_no, id', [$requestId]);
    }

    public function replaceItems(int $requestId, array $items): void {
        $this->exec_('DELETE FROM proc_items WHERE request_id = ?', [$requestId]);
        $line = 1;
        foreach ($items as $it) {
            $this->exec_(
                'INSERT INTO proc_items (request_id, line_no, item_name, specification, quantity, unit, unit_price, line_total) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [$requestId, $line++, $it['item_name'], $it['specification'], (float) $it['quantity'], $it['unit'], (float) $it['unit_price'], (float) $it['line_total']]
            );
        }
    }

    /* ------------------------------------------------------------------ history / audit */

    public function addHistory(?int $requestId, string $action, ?string $from, ?string $to, string $actorId, ?string $actorRole, ?string $remarks = null, $details = null): void {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        $this->exec_(
            'INSERT INTO proc_history (request_id, action, from_status, to_status, actor_staff_id, actor_role, remarks, details, ip_address, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $requestId === null ? 0 : $requestId,
                $action,
                (string) $from,
                (string) $to,
                $actorId,
                (string) $actorRole,
                (string) $remarks,
                $details === null ? '' : (is_string($details) ? $details : json_encode($details, JSON_UNESCAPED_UNICODE)),
                $ip,
                $this->now(),
            ]
        );
    }

    public function history(int $requestId): array {
        return $this->all_(
            'SELECT h.*, s.staff_name AS actor_name FROM proc_history h LEFT JOIN staff s ON s.staff_id = h.actor_staff_id
             WHERE h.request_id = ? ORDER BY h.id ASC',
            [$requestId]
        );
    }

    /** System wide audit rows (including role changes that belong to no request). */
    public function auditLog(array $f, int $limit = 200): array {
        $where = ['1=1'];
        $p = [];
        if (!empty($f['request_id'])) {
            $where[] = 'h.request_id = ?';
            $p[] = (int) $f['request_id'];
        }
        if (!empty($f['actor'])) {
            $where[] = 'h.actor_staff_id = ?';
            $p[] = (string) $f['actor'];
        }
        if (!empty($f['action'])) {
            $where[] = 'h.action = ?';
            $p[] = (string) $f['action'];
        }
        $p[] = $limit;
        return $this->all_(
            'SELECT h.*, s.staff_name AS actor_name, r.request_no FROM proc_history h
             LEFT JOIN staff s ON s.staff_id = h.actor_staff_id LEFT JOIN proc_requests r ON r.id = h.request_id
             WHERE ' . implode(' AND ', $where) . ' ORDER BY h.id DESC LIMIT ?',
            $p
        );
    }

    /* ------------------------------------------------------------------ documents */

    public function addDocument(array $d): int {
        $this->exec_(
            'INSERT INTO proc_documents (request_id, doc_type, original_name, stored_name, mime_type, file_size, uploaded_by, uploaded_role, uploaded_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$d['request_id'], $d['doc_type'], $d['original_name'], $d['stored_name'], $d['mime_type'], (int) $d['file_size'], $d['uploaded_by'], $d['uploaded_role'], $this->now()]
        );
        return $this->insertId();
    }

    public function documents(int $requestId, bool $includeDeleted = false): array {
        return $this->all_(
            'SELECT d.*, s.staff_name AS uploader_name FROM proc_documents d LEFT JOIN staff s ON s.staff_id = d.uploaded_by
             WHERE d.request_id = ?' . ($includeDeleted ? '' : ' AND d.is_deleted = 0') . ' ORDER BY d.id ASC',
            [$requestId]
        );
    }

    public function document(int $id): ?array {
        return $this->one_('SELECT * FROM proc_documents WHERE id = ? LIMIT 1', [$id]);
    }

    public function countDocuments(int $requestId, string $type): int {
        $row = $this->one_('SELECT COUNT(*) AS c FROM proc_documents WHERE request_id = ? AND doc_type = ? AND is_deleted = 0', [$requestId, $type]);
        return (int) ($row['c'] ?? 0);
    }

    public function softDeleteDocument(int $id, string $staffId): void {
        $this->exec_('UPDATE proc_documents SET is_deleted = 1, deleted_by = ?, deleted_at = ? WHERE id = ?', [$staffId, $this->now(), $id]);
    }

    /* ------------------------------------------------------------------ notifications */

    public function notify(string $staffId, ?int $requestId, string $title, string $message): void {
        if ($staffId === '') {
            return;
        }
        $this->exec_(
            'INSERT INTO proc_notifications (request_id, staff_id, title, message, created_at) VALUES (?, ?, ?, ?, ?)',
            [$requestId === null ? 0 : $requestId, $staffId, mb_substr($title, 0, 160), mb_substr($message, 0, 500), $this->now()]
        );
    }

    public function notifications(string $staffId, int $limit = 50, bool $unreadOnly = false): array {
        return $this->all_(
            'SELECT n.*, r.request_no FROM proc_notifications n LEFT JOIN proc_requests r ON r.id = n.request_id
             WHERE n.staff_id = ?' . ($unreadOnly ? ' AND n.is_read = 0' : '') . ' ORDER BY n.id DESC LIMIT ?',
            [$staffId, $limit]
        );
    }

    public function unreadCount(string $staffId): int {
        if ($staffId === '') {
            return 0;
        }
        $row = $this->one_('SELECT COUNT(*) AS c FROM proc_notifications WHERE staff_id = ? AND is_read = 0', [$staffId]);
        return (int) ($row['c'] ?? 0);
    }

    public function markRead(string $staffId, int $id = 0): void {
        if ($id > 0) {
            $this->exec_('UPDATE proc_notifications SET is_read = 1 WHERE staff_id = ? AND id = ?', [$staffId, $id]);
        } else {
            $this->exec_('UPDATE proc_notifications SET is_read = 1 WHERE staff_id = ? AND is_read = 0', [$staffId]);
        }
    }

    /* ------------------------------------------------------------------ roles */

    /** Staff ids that hold a workflow role (position type or admin assignment), active accounts only. */
    public function staffIdsForRole(string $role, array $positionCodes): array {
        $ids = [];
        if ($positionCodes) {
            $in = implode(',', array_fill(0, count($positionCodes), '?'));
            $rows = $this->all_(
                "SELECT user_name FROM `user` WHERE UPPER(staff_position_type_id) IN ($in) AND user_active = 1 AND user_deleted = 0 AND user_table = 'staff'",
                $positionCodes
            );
            foreach ($rows as $r) {
                $ids[] = (string) $r['user_name'];
            }
        }
        $rows = $this->all_(
            "SELECT pr.staff_id FROM proc_roles pr JOIN `user` u ON u.user_name = pr.staff_id AND u.user_active = 1 AND u.user_deleted = 0 WHERE pr.role = ?",
            [$role]
        );
        foreach ($rows as $r) {
            $ids[] = (string) $r['staff_id'];
        }
        return array_values(array_unique($ids));
    }

    public function assignedRoles(string $staffId): array {
        $out = [];
        foreach ($this->all_('SELECT role FROM proc_roles WHERE staff_id = ?', [$staffId]) as $r) {
            $out[] = (string) $r['role'];
        }
        return $out;
    }

    public function roleAssignments(): array {
        return $this->all_(
            'SELECT pr.*, s.staff_name, s.department_id, d.department_name, a.staff_name AS assigned_name
             FROM proc_roles pr LEFT JOIN staff s ON s.staff_id = pr.staff_id
             LEFT JOIN department d ON d.department_id = s.department_id
             LEFT JOIN staff a ON a.staff_id = pr.assigned_by ORDER BY pr.role, s.staff_name'
        );
    }

    public function assignRole(string $staffId, string $role, string $byId): bool {
        $n = $this->exec_('INSERT IGNORE INTO proc_roles (staff_id, role, assigned_by, assigned_at) VALUES (?, ?, ?, ?)', [$staffId, $role, $byId, $this->now()]);
        return $n > 0;
    }

    public function revokeRole(int $id): ?array {
        $row = $this->one_('SELECT * FROM proc_roles WHERE id = ?', [$id]);
        if ($row) {
            $this->exec_('DELETE FROM proc_roles WHERE id = ?', [$id]);
        }
        return $row;
    }

    public function staffName(string $staffId): string {
        $row = $this->one_('SELECT staff_name FROM staff WHERE staff_id = ?', [$staffId]);
        return (string) ($row['staff_name'] ?? $staffId);
    }

    public function staffOptions(): array {
        return $this->all_(
            "SELECT s.staff_id, s.staff_name, d.department_name FROM staff s LEFT JOIN department d ON d.department_id = s.department_id
             WHERE s.staff_status = 'Active' ORDER BY s.staff_name"
        );
    }

    public function departments(): array {
        return $this->all_('SELECT department_id, department_name FROM department ORDER BY department_name');
    }

    public function departmentName(?string $id): string {
        if ($id === null || $id === '') {
            return '';
        }
        $row = $this->one_('SELECT department_name FROM department WHERE department_id = ?', [$id]);
        return (string) ($row['department_name'] ?? '');
    }

    public function staffDepartment(string $staffId): ?string {
        $row = $this->one_('SELECT department_id FROM staff WHERE staff_id = ?', [$staffId]);
        $d = $row['department_id'] ?? null;
        return ($d === null || $d === '') ? null : (string) $d;
    }

    /* ------------------------------------------------------------------ search / visibility */

    /**
     * Visibility rule: which requests a person may see. Returns [sqlFragment, params].
     * $actor needs: staff_id, roles (list), is_admin, hod_department_id.
     */
    public function visibility(array $actor): array {
        if (!empty($actor['is_admin'])) {
            return ['1=1', []];
        }
        $roles = $actor['roles'] ?? [];
        $sid = (string) $actor['staff_id'];
        $or = ['r.requested_by = ?'];
        $p = [$sid];
        if (!empty($actor['hod_department_id'])) {
            $or[] = "(r.status <> 'draft' AND r.department_id = ?)";
            $p[] = (string) $actor['hod_department_id'];
        }
        if (in_array('director', $roles, true) || in_array('procurement_officer', $roles, true)) {
            $or[] = "r.status <> 'draft'";
        }
        if (in_array('accountant', $roles, true)) {
            $or[] = "(r.director_decision = 'approved' AND r.status <> 'draft')";
            $or[] = 'r.accountant_by = ?';
            $p[] = $sid;
        }
        if (in_array('purchase_officer', $roles, true)) {
            $or[] = "(r.accountant_decision = 'approved' AND r.status IN ('pending_purchase','in_procurement','pending_verification','verified','closed'))";
        }
        return ['(' . implode(' OR ', $or) . ')', $p];
    }

    private function filterSql(array $f, array $actor): array {
        list($vis, $p) = $this->visibility($actor);
        $where = [$vis];
        if (!empty($f['scope']) && $f['scope'] === 'mine') {
            $where[] = 'r.requested_by = ?';
            $p[] = (string) $actor['staff_id'];
        }
        if (!empty($f['statuses']) && is_array($f['statuses'])) {
            $in = implode(',', array_fill(0, count($f['statuses']), '?'));
            $where[] = "r.status IN ($in)";
            foreach ($f['statuses'] as $s) {
                $p[] = (string) $s;
            }
        }
        if (!empty($f['status']) && isset(self::STATUS_LABELS[$f['status']])) {
            $where[] = 'r.status = ?';
            $p[] = (string) $f['status'];
        }
        if (!empty($f['q'])) {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], (string) $f['q']) . '%';
            $where[] = '(r.request_no LIKE ? OR r.title LIKE ? OR rs.staff_name LIKE ? OR r.supplier_name LIKE ? OR r.po_number LIKE ? OR r.invoice_number LIKE ?)';
            for ($i = 0; $i < 6; $i++) {
                $p[] = $like;
            }
        }
        foreach (['department_id', 'requested_by', 'priority', 'category'] as $k) {
            if (isset($f[$k]) && $f[$k] !== '') {
                $where[] = "r.$k = ?";
                $p[] = (string) $f[$k];
            }
        }
        if (!empty($f['date_from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $f['date_from'])) {
            $where[] = 'r.created_at >= ?';
            $p[] = $f['date_from'] . ' 00:00:00';
        }
        if (!empty($f['date_to']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $f['date_to'])) {
            $where[] = 'r.created_at <= ?';
            $p[] = $f['date_to'] . ' 23:59:59';
        }
        if (isset($f['min_amount']) && $f['min_amount'] !== '' && is_numeric($f['min_amount'])) {
            $where[] = 'r.estimated_total >= ?';
            $p[] = (float) $f['min_amount'];
        }
        if (isset($f['max_amount']) && $f['max_amount'] !== '' && is_numeric($f['max_amount'])) {
            $where[] = 'r.estimated_total <= ?';
            $p[] = (float) $f['max_amount'];
        }
        return [implode(' AND ', $where), $p];
    }

    public function search(array $f, array $actor, int $page = 1, int $perPage = 15): array {
        list($where, $p) = $this->filterSql($f, $actor);
        $from = ' FROM proc_requests r LEFT JOIN department d ON d.department_id = r.department_id LEFT JOIN staff rs ON rs.staff_id = r.requested_by WHERE ' . $where;
        $row = $this->one_('SELECT COUNT(*) AS c, COALESCE(SUM(r.estimated_total),0) AS amount' . $from, $p);
        $total = (int) ($row['c'] ?? 0);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $pages));
        $p2 = $p;
        $p2[] = $perPage;
        $p2[] = ($page - 1) * $perPage;
        $rows = $this->all_(
            'SELECT r.*, d.department_name, rs.staff_name AS requester_name' . $from . ' ORDER BY r.updated_at DESC, r.id DESC LIMIT ? OFFSET ?',
            $p2
        );
        return ['rows' => $rows, 'total' => $total, 'amount' => (float) ($row['amount'] ?? 0), 'page' => $page, 'pages' => $pages, 'per_page' => $perPage];
    }

    /** Full list without paging (reports / CSV). Hard capped. */
    public function searchAll(array $f, array $actor, int $cap = 5000): array {
        list($where, $p) = $this->filterSql($f, $actor);
        $p[] = $cap;
        return $this->all_(
            'SELECT r.*, d.department_name, rs.staff_name AS requester_name FROM proc_requests r
             LEFT JOIN department d ON d.department_id = r.department_id LEFT JOIN staff rs ON rs.staff_id = r.requested_by
             WHERE ' . $where . ' ORDER BY r.created_at DESC, r.id DESC LIMIT ?',
            $p
        );
    }

    /** status => [count, amount] inside the visibility scope (+ optional filters). */
    public function statusCounts(array $actor, array $f = []): array {
        list($where, $p) = $this->filterSql($f, $actor);
        $rows = $this->all_(
            'SELECT r.status, COUNT(*) AS c, COALESCE(SUM(r.estimated_total),0) AS amount FROM proc_requests r
             LEFT JOIN staff rs ON rs.staff_id = r.requested_by WHERE ' . $where . ' GROUP BY r.status',
            $p
        );
        $out = [];
        foreach (self::STATUS_LABELS as $k => $_) {
            $out[$k] = ['count' => 0, 'amount' => 0.0];
        }
        foreach ($rows as $r) {
            $out[$r['status']] = ['count' => (int) $r['c'], 'amount' => (float) $r['amount']];
        }
        return $out;
    }

    public function groupBy(string $dim, array $actor, array $f = []): array {
        $map = [
            'department' => ["COALESCE(d.department_name, 'Unassigned')", 'LEFT JOIN department d ON d.department_id = r.department_id'],
            'month' => ["DATE_FORMAT(r.created_at, '%Y-%m')", ''],
            'category' => ['r.category', ''],
            'priority' => ['r.priority', ''],
        ];
        if (!isset($map[$dim])) {
            return [];
        }
        list($where, $p) = $this->filterSql($f, $actor);
        list($expr, $join) = $map[$dim];
        $joinDept = $dim === 'department' ? $join : '';
        // filterSql refers to alias rs only; department filters use r.department_id so d is optional
        return $this->all_(
            "SELECT $expr AS label, COUNT(*) AS c, COALESCE(SUM(r.estimated_total),0) AS estimated, COALESCE(SUM(r.actual_total),0) AS actual
             FROM proc_requests r $joinDept LEFT JOIN staff rs ON rs.staff_id = r.requested_by WHERE $where GROUP BY label ORDER BY " . ($dim === 'month' ? 'label DESC' : 'c DESC'),
            $p
        );
    }

    public function recentHistory(array $actor, int $limit = 10): array {
        list($vis, $p) = $this->visibility($actor);
        $p[] = $limit;
        return $this->all_(
            "SELECT h.*, r.request_no, r.title, s.staff_name AS actor_name FROM proc_history h
             JOIN proc_requests r ON r.id = h.request_id LEFT JOIN staff s ON s.staff_id = h.actor_staff_id
             WHERE $vis ORDER BY h.id DESC LIMIT ?",
            $p
        );
    }
}
