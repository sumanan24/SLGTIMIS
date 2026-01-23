<?php
/**
 * Staff Model
 */

class StaffModel extends Model {
    protected $table = 'staff';
    
    protected function getPrimaryKey() {
        return 'staff_id';
    }
    
    /**
     * Get staff with department info
     */
    public function getStaffWithDepartment($page = 1, $perPage = 20, $search = '', $departmentId = '') {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT s.*, d.department_name 
                FROM `{$this->table}` s 
                LEFT JOIN `department` d ON s.department_id = d.department_id";
        
        $conditions = [];
        $params = [];
        $types = '';
        
        if (!empty($departmentId)) {
            $conditions[] = "s.department_id = ?";
            $params[] = $departmentId;
            $types .= 's';
        }
        
        if (!empty($search)) {
            $searchTerm = '%' . $search . '%';
            $searchConditions = [];
            $searchConditions[] = "s.staff_name LIKE ?";
            $searchConditions[] = "s.staff_id LIKE ?";
            $searchConditions[] = "s.staff_email LIKE ?";
            $searchConditions[] = "s.staff_nic LIKE ?";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            $types .= 'ssss';
            $conditions[] = "(" . implode(" OR ", $searchConditions) . ")";
        }
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $sql .= " ORDER BY s.staff_id DESC LIMIT $perPage OFFSET $offset";
        
        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
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
     * Get total count of staff
     */
    public function getTotalStaff($search = '', $departmentId = '') {
        $sql = "SELECT COUNT(*) as total FROM `{$this->table}`";
        
        $conditions = [];
        $params = [];
        $types = '';
        
        if (!empty($departmentId)) {
            $conditions[] = "department_id = ?";
            $params[] = $departmentId;
            $types .= 's';
        }
        
        if (!empty($search)) {
            $searchTerm = '%' . $search . '%';
            $searchConditions = [];
            $searchConditions[] = "staff_name LIKE ?";
            $searchConditions[] = "staff_id LIKE ?";
            $searchConditions[] = "staff_email LIKE ?";
            $searchConditions[] = "staff_nic LIKE ?";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            $types .= 'ssss';
            $conditions[] = "(" . implode(" OR ", $searchConditions) . ")";
        }
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        
        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->db->query($sql);
        }
        
        $row = $result->fetch_assoc();
        return $row['total'];
    }
    
    /**
     * Get staff by ID with department
     */
    public function getById($id) {
        $sql = "SELECT s.*, d.department_name 
                FROM `{$this->table}` s 
                LEFT JOIN `department` d ON s.department_id = d.department_id
                WHERE s.staff_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Create new staff
     */
    public function createStaff($data) {
        return $this->create($data);
    }
    
    /**
     * Update staff
     */
    public function updateStaff($id, $data) {
        return $this->update($id, $data);
    }
    
    /**
     * Delete staff
     */
    public function deleteStaff($id) {
        return $this->delete($id);
    }
    
    /**
     * Check if staff exists
     */
    public function exists($id) {
        $staff = $this->find($id);
        return $staff !== null;
    }
}

