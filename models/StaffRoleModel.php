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
     * Get role by ID
     */
    public function getById($id) {
        return $this->find($id);
    }
    
    /**
     * Create new role
     */
    public function createRole($data) {
        return $this->create($data);
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

