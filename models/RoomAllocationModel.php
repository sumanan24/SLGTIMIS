<?php
/**
 * Room Allocation Model
 */

class RoomAllocationModel extends Model {
    protected $table = 'hostel_allocations';
    
    protected function getPrimaryKey() {
        return 'id';
    }
    
    /**
     * Get all allocations with student and room info
     */
    public function getAllocations($page = 1, $perPage = 20, $filters = []) {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT ha.*, 
                s.student_id, s.student_fullname, s.student_email, s.student_nic,
                r.room_no, r.capacity,
                b.name as block_name,
                h.name as hostel_name, h.gender as hostel_gender
                FROM `{$this->table}` ha
                LEFT JOIN `student` s ON ha.student_id = s.student_id
                LEFT JOIN `hostel_rooms` r ON ha.room_id = r.id
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
        
        if (!empty($filters['room_id'])) {
            $sql .= " AND ha.room_id = ?";
            $params[] = $filters['room_id'];
            $types .= 's';
        }
        
        if (!empty($filters['student_id'])) {
            $sql .= " AND ha.student_id = ?";
            $params[] = $filters['student_id'];
            $types .= 's';
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND ha.status = ?";
            $params[] = $filters['status'];
            $types .= 's';
        }
        
        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $sql .= " AND (s.student_fullname LIKE ? OR s.student_id LIKE ? OR r.room_no LIKE ?)";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= 'sss';
        }
        
        $sql .= " ORDER BY ha.allocated_at DESC LIMIT ? OFFSET ?";
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
     * Get total count of allocations
     */
    public function getTotalAllocations($filters = []) {
        $sql = "SELECT COUNT(*) as total FROM `{$this->table}` ha
                LEFT JOIN `student` s ON ha.student_id = s.student_id
                LEFT JOIN `hostel_rooms` r ON ha.room_id = r.id
                LEFT JOIN `hostel_blocks` b ON r.block_id = b.id
                WHERE 1=1";
        
        $params = [];
        $types = '';
        
        if (!empty($filters['hostel_id'])) {
            $sql .= " AND b.hostel_id = ?";
            $params[] = $filters['hostel_id'];
            $types .= 's';
        }
        
        if (!empty($filters['room_id'])) {
            $sql .= " AND ha.room_id = ?";
            $params[] = $filters['room_id'];
            $types .= 's';
        }
        
        if (!empty($filters['student_id'])) {
            $sql .= " AND ha.student_id = ?";
            $params[] = $filters['student_id'];
            $types .= 's';
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND ha.status = ?";
            $params[] = $filters['status'];
            $types .= 's';
        }
        
        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $sql .= " AND (s.student_fullname LIKE ? OR s.student_id LIKE ? OR r.room_no LIKE ?)";
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
     * Get allocation by ID with details
     */
    public function getById($id) {
        $sql = "SELECT ha.*, 
                s.student_id, s.student_fullname, s.student_email, s.student_nic, s.student_gender,
                r.room_no, r.capacity, r.block_id,
                b.name as block_name,
                h.name as hostel_name, h.gender as hostel_gender, h.id as hostel_id
                FROM `{$this->table}` ha
                LEFT JOIN `student` s ON ha.student_id = s.student_id
                LEFT JOIN `hostel_rooms` r ON ha.room_id = r.id
                LEFT JOIN `hostel_blocks` b ON r.block_id = b.id
                LEFT JOIN `hostels` h ON b.hostel_id = h.id
                WHERE ha.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Get active allocation for a student
     */
    public function getActiveByStudentId($studentId) {
        $sql = "SELECT ha.*, 
                r.room_no,
                b.name as block_name,
                h.name as hostel_name
                FROM `{$this->table}` ha
                LEFT JOIN `hostel_rooms` r ON ha.room_id = r.id
                LEFT JOIN `hostel_blocks` b ON r.block_id = b.id
                LEFT JOIN `hostels` h ON b.hostel_id = h.id
                WHERE ha.student_id = ? AND ha.status = 'active'
                ORDER BY ha.allocated_at DESC
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Get allocations by room ID
     */
    public function getByRoomId($roomId, $status = null) {
        $sql = "SELECT ha.*, 
                s.student_id, s.student_fullname, s.student_email, s.student_nic, s.student_gender,
                r.room_no, r.capacity,
                b.name as block_name,
                h.name as hostel_name
                FROM `{$this->table}` ha
                LEFT JOIN `student` s ON ha.student_id = s.student_id
                LEFT JOIN `hostel_rooms` r ON ha.room_id = r.id
                LEFT JOIN `hostel_blocks` b ON r.block_id = b.id
                LEFT JOIN `hostels` h ON b.hostel_id = h.id
                WHERE ha.room_id = ?";
        
        $params = [$roomId];
        $types = 's';
        
        if ($status !== null) {
            $sql .= " AND ha.status = ?";
            $params[] = $status;
            $types .= 's';
        }
        
        $sql .= " ORDER BY ha.allocated_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
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
     * Check if student has active allocation
     */
    public function hasActiveAllocation($studentId) {
        $allocation = $this->getActiveByStudentId($studentId);
        return $allocation !== null;
    }
    
    /**
     * Check if room has available beds
     */
    public function hasAvailableBeds($roomId) {
        $roomModel = new RoomModel();
        $room = $roomModel->getById($roomId);
        
        if (!$room) {
            return false;
        }
        
        $sql = "SELECT COUNT(*) as occupied FROM `{$this->table}` 
                WHERE room_id = ? AND status = 'active'";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $roomId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return ($room['capacity'] - $row['occupied']) > 0;
    }
    
    /**
     * Create allocation
     */
    public function createAllocation($data) {
        // Check if student already has active allocation
        if ($this->hasActiveAllocation($data['student_id'])) {
            return false; // Student already has active allocation
        }
        
        // Check if room has available beds
        if (!$this->hasAvailableBeds($data['room_id'])) {
            return false; // Room is full
        }
        
        // Set allocated_at date
        if (empty($data['allocated_at'])) {
            $data['allocated_at'] = date('Y-m-d');
        }
        
        // Set status to active if not provided
        if (empty($data['status'])) {
            $data['status'] = 'active';
        }
        
        return $this->create($data);
    }
    
    /**
     * Update allocation
     */
    public function updateAllocation($id, $data) {
        return $this->update($id, $data);
    }
    
    /**
     * Deallocate (set status to inactive)
     */
    public function deallocate($id, $leavingAt = null) {
        $data = [
            'status' => 'left',
            'leaving_at' => $leavingAt ?? date('Y-m-d')
        ];
        return $this->update($id, $data);
    }
    
    /**
     * Delete allocation
     */
    public function deleteAllocation($id) {
        return $this->delete($id);
    }
}

