<?php
/**
 * inventory_log — unified audit trail
 */
class InventoryLogModel extends Model {
    protected $table = 'inventory_log';

    protected function getPrimaryKey() {
        return 'log_id';
    }

    public function insertLog($data, &$sqlError = null) {
        return $this->create($data, $sqlError);
    }

    /**
     * @param array<string, mixed> $filters date_from, date_to, department_id, item_id, action_type
     * @return array<int, array<string, mixed>>
     */
    public function getLogs($filters = []) {
        $sql = "SELECT l.*, i.`item_name`, i.`item_code`, d.`department_name`
                FROM `{$this->table}` l
                LEFT JOIN `inventory` i ON l.`item_id` = i.`item_id`
                LEFT JOIN `department` d ON l.`department_id` = d.`department_id`";
        $params = [];
        $types = '';
        $where = [];

        if (!empty($filters['date_from'])) {
            $where[] = "DATE(l.`action_date`) >= ?";
            $params[] = $filters['date_from'];
            $types .= 's';
        }
        if (!empty($filters['date_to'])) {
            $where[] = "DATE(l.`action_date`) <= ?";
            $params[] = $filters['date_to'];
            $types .= 's';
        }
        if (isset($filters['department_id']) && $filters['department_id'] !== '') {
            $where[] = "l.`department_id` = ?";
            $params[] = $filters['department_id'];
            $types .= 's';
        }
        if (!empty($filters['item_id'])) {
            $where[] = "l.`item_id` = ?";
            $params[] = (string)(int)$filters['item_id'];
            $types .= 's';
        }
        if (!empty($filters['action_type']) && in_array($filters['action_type'], ['IN', 'OUT', 'TRANSFER'], true)) {
            $where[] = "l.`action_type` = ?";
            $params[] = $filters['action_type'];
            $types .= 's';
        }

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= " ORDER BY l.`action_date` DESC, l.`log_id` DESC LIMIT 500";

        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                return [];
            }
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->db->query($sql);
        }

        $rows = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getRecent($limit = 15, $departmentId = null) {
        $limit = max(1, min(50, (int)$limit));
        $sql = "SELECT l.*, i.`item_name`, i.`item_code`, d.`department_name`
                FROM `{$this->table}` l
                LEFT JOIN `inventory` i ON l.`item_id` = i.`item_id`
                LEFT JOIN `department` d ON l.`department_id` = d.`department_id`";
        $params = [];
        $types = '';
        if ($departmentId !== null && $departmentId !== '') {
            $sql .= " WHERE l.`department_id` = ?";
            $params[] = $departmentId;
            $types .= 's';
        }
        $sql .= " ORDER BY l.`action_date` DESC LIMIT {$limit}";

        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                return [];
            }
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->db->query($sql);
        }

        $rows = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    public function tableExists() {
        $r = $this->db->query("SHOW TABLES LIKE 'inventory_log'");
        return $r && $r->num_rows > 0;
    }
}
