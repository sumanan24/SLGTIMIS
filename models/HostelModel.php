<?php
/**
 * Hostel Model
 */

class HostelModel extends Model {
    protected $table = 'hostels';
    
    protected function getPrimaryKey() {
        return 'id';
    }
    
    /**
     * Get all hostels with pagination and search
     */
    public function getHostels($page = 1, $perPage = 20, $search = '') {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT * FROM `{$this->table}` WHERE 1=1";
        $params = [];
        $types = '';
        
        if (!empty($search)) {
            $searchTerm = '%' . $search . '%';
            $sql .= " AND (name LIKE ? OR location LIKE ? OR gender LIKE ?)";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= 'sss';
        }
        
        $sql .= " ORDER BY name ASC LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;
        $types .= 'ii';
        
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
     * Get total count of hostels
     */
    public function getTotalHostels($search = '') {
        $sql = "SELECT COUNT(*) as total FROM `{$this->table}` WHERE 1=1";
        $params = [];
        $types = '';
        
        if (!empty($search)) {
            $searchTerm = '%' . $search . '%';
            $sql .= " AND (name LIKE ? OR location LIKE ? OR gender LIKE ?)";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= 'sss';
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
     * Get all hostels (for dropdowns)
     */
    public function getAll() {
        return $this->all('name ASC');
    }
    
    /**
     * Check if hostel exists
     */
    public function exists($id) {
        $hostel = $this->find($id);
        return $hostel !== null;
    }
    
    /**
     * Get hostel by ID
     */
    public function getById($id) {
        return $this->find($id);
    }
    
    /**
     * Create hostel
     */
    public function createHostel($data) {
        return $this->create($data);
    }
    
    /**
     * Update hostel
     */
    public function updateHostel($id, $data) {
        return $this->update($id, $data);
    }
    
    /**
     * Delete hostel
     */
    public function deleteHostel($id) {
        // Check if hostel has rooms
        $roomModel = new RoomModel();
        $rooms = $roomModel->where('hostel_id', $id);
        if (!empty($rooms)) {
            return false; // Cannot delete hostel with rooms
        }
        return $this->delete($id);
    }
}
