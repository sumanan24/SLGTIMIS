<?php
/**
 * Inventory items (per department stock line)
 */
class InventoryModel extends Model {
    protected $table = 'inventory';

    protected function getPrimaryKey() {
        return 'item_id';
    }

    /**
     * @param string|null $departmentId
     * @param string $search
     * @param string|null $statusFilter active|inactive|''
     * @return array<int, array<string, mixed>>
     */
    public function getItems($departmentId = null, $search = '', $statusFilter = '') {
        $sql = "SELECT i.*, d.`department_name` 
                FROM `{$this->table}` i
                LEFT JOIN `department` d ON i.`department_id` = d.`department_id`";
        $params = [];
        $types = '';
        $where = [];

        if ($departmentId !== null && $departmentId !== '') {
            $where[] = "`i`.`department_id` = ?";
            $params[] = $departmentId;
            $types .= 's';
        }
        if ($search !== '') {
            $where[] = "(`i`.`item_name` LIKE ? OR `i`.`item_code` LIKE ? OR `i`.`category` LIKE ?)";
            $q = '%' . $search . '%';
            $params[] = $q;
            $params[] = $q;
            $params[] = $q;
            $types .= 'sss';
        }
        if ($statusFilter === 'active' || $statusFilter === 'inactive') {
            $where[] = "`i`.`status` = ?";
            $params[] = $statusFilter;
            $types .= 's';
        }

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= " ORDER BY `i`.`item_name` ASC";

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

    public function countItems($departmentId = null) {
        $sql = "SELECT COUNT(*) AS c FROM `{$this->table}`";
        $params = [];
        $types = '';
        if ($departmentId !== null && $departmentId !== '') {
            $sql .= " WHERE `department_id` = ?";
            $params[] = $departmentId;
            $types .= 's';
        }
        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                return 0;
            }
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->db->query($sql);
        }
        if ($result && $row = $result->fetch_assoc()) {
            return (int)($row['c'] ?? 0);
        }
        return 0;
    }

    /**
     * Older SLGTIMIS databases use inventory_quantity/inventory_status and
     * have no reorder level. Only calculate low stock for the current schema.
     */
    public function supportsLowStockCalculation() {
        foreach (['status', 'quantity', 'reorder_level'] as $column) {
            $result = $this->db->query(
                "SHOW COLUMNS FROM `{$this->table}` LIKE '" . $this->db->escape($column) . "'"
            );
            if (!$result || $result->num_rows === 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getLowStockItems($departmentId = null, $limit = 20) {
        if (!$this->supportsLowStockCalculation()) {
            return [];
        }

        $limit = max(1, min(100, (int)$limit));
        $sql = "SELECT i.*, d.`department_name` FROM `{$this->table}` i
                LEFT JOIN `department` d ON i.`department_id` = d.`department_id`
                WHERE `i`.`status` = 'active' AND `i`.`quantity` < `i`.`reorder_level`";
        $params = [];
        $types = '';
        if ($departmentId !== null && $departmentId !== '') {
            $sql .= " AND `i`.`department_id` = ?";
            $params[] = $departmentId;
            $types .= 's';
        }
        $sql .= " ORDER BY `i`.`quantity` ASC LIMIT {$limit}";

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

    public function createItem($data, &$sqlError = null) {
        return $this->create($data, $sqlError);
    }

    public function updateItem($id, $data, &$sqlError = null) {
        return $this->update($id, $data, $sqlError);
    }

    public function deleteItem($id) {
        return $this->delete($id);
    }

    /**
     * @return int|false new quantity or false
     */
    public function adjustQuantity($itemId, $delta, &$sqlError = null) {
        $delta = (int)$delta;
        $delta = $delta === 0 ? 0 : $delta;
        $sql = "UPDATE `{$this->table}` SET `quantity` = `quantity` + ? WHERE `item_id` = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            $sqlError = $this->db->getConnection()->error ?? 'Prepare failed';
            return false;
        }
        $stmt->bind_param('ii', $delta, $itemId);
        if (!$stmt->execute()) {
            $sqlError = $stmt->error ?? 'Execute failed';
            return false;
        }
        $row = $this->find($itemId);
        return $row ? (int)($row['quantity'] ?? 0) : false;
    }

    public function getQuantity($itemId) {
        $row = $this->find($itemId);
        return $row ? (int)($row['quantity'] ?? 0) : null;
    }

    /**
     * Find another row with same item_code in to_department (for transfer).
     */
    public function findByCodeAndDepartment($itemCode, $departmentId) {
        if ($itemCode === '' || $itemCode === null) {
            return null;
        }
        $sql = "SELECT * FROM `{$this->table}` WHERE `item_code` = ? AND `department_id` <=> ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('ss', $itemCode, $departmentId);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? $res->fetch_assoc() : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getItemsForSelect($departmentId = null) {
        $sql = "SELECT `item_id`, `item_name`, `item_code`, `department_id`, `quantity` FROM `{$this->table}` WHERE `status` = 'active'";
        $params = [];
        $types = '';
        if ($departmentId !== null && $departmentId !== '') {
            $sql .= " AND `department_id` = ?";
            $params[] = $departmentId;
            $types .= 's';
        }
        $sql .= " ORDER BY `item_name` ASC";

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
        $r = $this->db->query("SHOW TABLES LIKE 'inventory'");
        return $r && $r->num_rows > 0;
    }
}
