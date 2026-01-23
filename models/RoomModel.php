<?php
/**
 * Room Model
 */

class RoomModel extends Model {
    protected $table = 'hostel_rooms';
    
    protected function getPrimaryKey() {
        return 'id';
    }
    
    /**
     * Get all rooms with hostel and block info
     */
    public function getRooms($page = 1, $perPage = 20, $filters = []) {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT r.*, h.name as hostel_name, b.name as block_name,
                (SELECT COUNT(*) FROM hostel_allocations WHERE room_id = r.id AND status = 'active') as occupied_beds
                FROM `{$this->table}` r
                LEFT JOIN `hostel_blocks` b ON r.block_id = b.id
                LEFT JOIN `hostels` h ON b.hostel_id = h.id
                WHERE 1=1";
        
        $params = [];
        $types = '';
        
        if (!empty($filters['hostel_id'])) {
            $sql .= " AND b.hostel_id = ?";
            $params[] = $filters['hostel_id'];
            $types .= 's';
        }
        
        if (!empty($filters['block_id'])) {
            $sql .= " AND r.block_id = ?";
            $params[] = $filters['block_id'];
            $types .= 's';
        }
        
        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $sql .= " AND r.room_no LIKE ?";
            $params[] = $searchTerm;
            $types .= 's';
        }
        
        $sql .= " ORDER BY h.name, b.name, r.room_no ASC LIMIT ? OFFSET ?";
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
                $row['available_beds'] = $row['capacity'] - $row['occupied_beds'];
                $data[] = $row;
            }
        }
        
        return $data;
    }
    
    /**
     * Get total count of rooms
     */
    public function getTotalRooms($filters = []) {
        $sql = "SELECT COUNT(*) as total FROM `{$this->table}` r
                LEFT JOIN `hostel_blocks` b ON r.block_id = b.id
                WHERE 1=1";
        
        $params = [];
        $types = '';
        
        if (!empty($filters['hostel_id'])) {
            $sql .= " AND b.hostel_id = ?";
            $params[] = $filters['hostel_id'];
            $types .= 's';
        }
        
        if (!empty($filters['block_id'])) {
            $sql .= " AND r.block_id = ?";
            $params[] = $filters['block_id'];
            $types .= 's';
        }
        
        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $sql .= " AND r.room_no LIKE ?";
            $params[] = $searchTerm;
            $types .= 's';
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
     * Get rooms by hostel ID
     */
    public function getByHostelId($hostelId) {
        $sql = "SELECT r.*, b.name as block_name 
                FROM `{$this->table}` r
                LEFT JOIN `hostel_blocks` b ON r.block_id = b.id
                WHERE b.hostel_id = ?
                ORDER BY b.name, r.room_no";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $hostelId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        
        return $data;
    }
    
    /**
     * Get rooms by block ID
     */
    public function getByBlockId($blockId) {
        return $this->where('block_id', $blockId);
    }
    
    /**
     * Get available rooms (with available beds)
     */
    public function getAvailableRooms($hostelId = null, $blockId = null) {
        $sql = "SELECT r.*, b.name as block_name, h.name as hostel_name,
                (SELECT COUNT(*) FROM hostel_allocations WHERE room_id = r.id AND status = 'active') as occupied_beds,
                (r.capacity - (SELECT COUNT(*) FROM hostel_allocations WHERE room_id = r.id AND status = 'active')) as available_beds
                FROM `{$this->table}` r
                LEFT JOIN `hostel_blocks` b ON r.block_id = b.id
                LEFT JOIN `hostels` h ON b.hostel_id = h.id
                WHERE (r.capacity - (SELECT COUNT(*) FROM hostel_allocations WHERE room_id = r.id AND status = 'active')) > 0";
        
        $params = [];
        $types = '';
        
        if (!empty($hostelId)) {
            $sql .= " AND b.hostel_id = ?";
            $params[] = $hostelId;
            $types .= 's';
        }
        
        if (!empty($blockId)) {
            $sql .= " AND r.block_id = ?";
            $params[] = $blockId;
            $types .= 's';
        }
        
        $sql .= " ORDER BY h.name, b.name, r.room_no";
        
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
     * Get room by ID with details
     */
    public function getById($id) {
        $sql = "SELECT r.*, b.name as block_name, h.name as hostel_name, h.id as hostel_id,
                (SELECT COUNT(*) FROM hostel_allocations WHERE room_id = r.id AND status = 'active') as occupied_beds
                FROM `{$this->table}` r
                LEFT JOIN `hostel_blocks` b ON r.block_id = b.id
                LEFT JOIN `hostels` h ON b.hostel_id = h.id
                WHERE r.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $room = $result->fetch_assoc();
        if ($room) {
            $room['available_beds'] = $room['capacity'] - $room['occupied_beds'];
        }
        
        return $room;
    }
    
    /**
     * Check if room exists
     */
    public function exists($id) {
        $room = $this->find($id);
        return $room !== null;
    }
    
    /**
     * Create room
     */
    public function createRoom($data) {
        return $this->create($data);
    }
    
    /**
     * Update room
     */
    public function updateRoom($id, $data) {
        return $this->update($id, $data);
    }
    
    /**
     * Delete room
     */
    public function deleteRoom($id) {
        // Check if room has active allocations
        $allocModel = new RoomAllocationModel();
        $allocations = $allocModel->where('room_id', $id);
        $activeAllocations = array_filter($allocations, function($alloc) {
            return $alloc['status'] === 'active';
        });
        
        if (!empty($activeAllocations)) {
            return false; // Cannot delete room with active allocations
        }
        
        return $this->delete($id);
    }
}

