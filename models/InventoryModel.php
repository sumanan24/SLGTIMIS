<?php
/**
 * Inventory Model
 */

class InventoryModel extends Model {
    protected $table = 'inventory';

    protected function getPrimaryKey() {
        return 'inventory_id';
    }

    /**
     * Get inventory records, optionally filtered by department
     */
    public function getInventories($departmentId = null) {
        $sql = "SELECT i.*,
                       d.department_name,
                       ii.inventory_item_description,
                       ii.item_code
                FROM `{$this->table}` i
                LEFT JOIN `department` d ON i.inventory_department_id = d.department_id
                LEFT JOIN `inventory_item` ii ON i.item_id = ii.item_id";

        $params = [];
        $types  = '';

        if (!empty($departmentId)) {
            $sql .= " WHERE i.inventory_department_id = ?";
            $params[] = $departmentId;
            $types   .= 's';
        }

        $sql .= " ORDER BY i.inventory_department_id, i.item_id";

        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                error_log("InventoryModel::getInventories SQL error: " . $this->db->getConnection()->error);
                return [];
            }
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->db->query($sql);
        }

        $data = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }

        return $data;
    }

    /**
     * Get single inventory by ID
     */
    public function getById($id) {
        return $this->find($id);
    }

    /**
     * Create inventory record
     */
    public function createInventory($data, &$sqlError = null) {
        return $this->create($data, $sqlError);
    }

    /**
     * Update inventory
     */
    public function updateInventory($id, $data, &$sqlError = null) {
        return $this->update($id, $data, $sqlError);
    }

    /**
     * Delete inventory
     */
    public function deleteInventory($id) {
        return $this->delete($id);
    }
}

