<?php
/**
 * Staff Role Model (Staff Position Type)
 */

class StaffRoleModel extends Model {
    protected $table = 'staff_position_type';
    
    protected function getPrimaryKey() {
        return 'staff_position_type_id';
    }
    
    /**
     * Get all roles ordered by position
     */
    public function getAll() {
        return $this->all('staff_position ASC, staff_position_type_name ASC');
    }
    
    /**
     * All roles with how many staff / login accounts use each one.
     *
     * @return list<array<string, mixed>>
     */
    public function getAllWithUsage() {
        $sql = "SELECT t.*,
                    COALESCE(s.total, 0)   AS staff_total,
                    COALESCE(s.working, 0) AS staff_working,
                    COALESCE(u.total, 0)   AS user_total
                FROM `{$this->table}` t
                LEFT JOIN (
                    SELECT `staff_position` AS role_id,
                           COUNT(*) AS total,
                           SUM(`staff_status` = 'Working') AS working
                    FROM `staff`
                    GROUP BY `staff_position`
                ) s ON s.role_id = t.`staff_position_type_id`
                LEFT JOIN (
                    SELECT `staff_position_type_id` AS role_id, COUNT(*) AS total
                    FROM `user`
                    GROUP BY `staff_position_type_id`
                ) u ON u.role_id = t.`staff_position_type_id`
                ORDER BY t.`staff_position_type_name` ASC, t.`staff_position_type_id` ASC";

        try {
            $result = $this->db->query($sql);
        } catch (Throwable $e) {
            error_log('StaffRoleModel::getAllWithUsage: ' . $e->getMessage());
            $result = false;
        }
        if (!$result) {
            // Fall back to the plain list so the page still works.
            $rows = $this->getAll();
            foreach ($rows as &$row) {
                $row['staff_total'] = 0;
                $row['staff_working'] = 0;
                $row['user_total'] = 0;
            }
            unset($row);
            return $rows;
        }

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Get role by ID
     */
    public function getById($id) {
        return $this->find($id);
    }
    
    /**
     * Create new role
     */
    public function createRole($data, &$sqlError = null) {
        // The staff_position column (old "level") is still NOT NULL in the database but is no
        // longer entered by users; keep it filled so existing ordering and inserts stay valid.
        if (!isset($data['staff_position'])) {
            $result = $this->db->query("SELECT COALESCE(MAX(`staff_position`), 0) + 1 AS next_pos FROM `{$this->table}`");
            $row = $result ? $result->fetch_assoc() : null;
            $data['staff_position'] = min(127, max(1, (int) ($row['next_pos'] ?? 1)));
        }
        return $this->create($data, $sqlError);
    }

    /**
     * Update role
     */
    public function updateRole($id, $data) {
        return $this->update($id, $data);
    }
    
    /**
     * Delete role
     */
    public function deleteRole($id) {
        // Check if role is used
        if ($this->isUsed($id)) {
            return false; // Cannot delete role that is in use
        }
        
        return $this->delete($id);
    }
    
    /**
     * Check if role exists
     */
    public function exists($id) {
        $role = $this->find($id);
        return $role !== null;
    }
    
    /**
     * Check if role is used
     */
    public function isUsed($id) {
        $db = Database::getInstance();
        
        // Check user table
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM `user` WHERE `staff_position_type_id` = ?");
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['total'] > 0) {
            return true;
        }
        
        // Check staff table (staff_position column references staff_position_type_id)
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM `staff` WHERE `staff_position` = ?");
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['total'] > 0) {
            return true;
        }
        
        // Check staff_position table
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM `staff_position` WHERE `staff_position_type_id` = ?");
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['total'] > 0;
    }
}

