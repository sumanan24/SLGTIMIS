<?php
/**
 * Room Allocation Model
 */

class RoomAllocationModel extends Model {
    protected $table = 'hostel_allocations';
    
    protected function getPrimaryKey() {
        return 'id';
    }

    public function __construct() {
        parent::__construct();
        $this->ensureUtf8mb4UnicodeCollation();
    }

    /**
     * Keep hostel tables on utf8mb4_unicode_ci so JOINs with student/course work.
     */
    public function ensureUtf8mb4UnicodeCollation(): void {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        try {
            $sql = "SELECT TABLE_COLLATION FROM information_schema.TABLES
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hostel_allocations' LIMIT 1";
            $result = $this->db->query($sql);
            $row = $result ? $result->fetch_assoc() : null;
            $collation = strtolower((string) ($row['TABLE_COLLATION'] ?? ''));
            if ($collation === '' || $collation === 'utf8mb4_unicode_ci') {
                return;
            }
            $this->db->query('SET FOREIGN_KEY_CHECKS = 0');
            foreach ([
                'hostels',
                'hostel_blocks',
                'hostel_rooms',
                'hostel_allocations',
                'hostel_requests',
                'hostel_fee_payments',
            ] as $table) {
                $exists = $this->db->query("SHOW TABLES LIKE '" . $this->db->escape($table) . "'");
                if (!$exists || $exists->num_rows === 0) {
                    continue;
                }
                $this->db->query(
                    "ALTER TABLE `{$table}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
                );
            }
            $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
        } catch (Throwable $e) {
            error_log('RoomAllocationModel::ensureUtf8mb4UnicodeCollation: ' . $e->getMessage());
            try {
                $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
            } catch (Throwable $ignored) {
            }
        }
    }

    /**
     * Enrollment joins required when filtering by department or academic year.
     */
    private function getDepartmentFilterJoins() {
        // COLLATE avoids illegal mix when hostel_* is still utf8mb4_0900_ai_ci.
        return " LEFT JOIN `student_enroll` se
                    ON s.student_id = se.student_id COLLATE utf8mb4_unicode_ci
                   AND se.student_enroll_status = 'Following'
                 LEFT JOIN `course` c
                    ON se.course_id = c.course_id COLLATE utf8mb4_unicode_ci";
    }

    private function needsEnrollmentJoin($filters) {
        return !empty($filters['department_id']) || !empty($filters['academic_year']);
    }

    /**
     * @param list<mixed> $params
     * @return mysqli_result|false
     */
    private function runFilteredQuery(string $sql, string $types, array $params) {
        if ($params === []) {
            return $this->db->query($sql);
        }
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log('RoomAllocationModel prepare failed: ' . ($this->db->getConnection()->error ?? '') . ' | SQL: ' . $sql);
            return false;
        }
        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) {
            error_log('RoomAllocationModel execute failed: ' . ($stmt->error ?: ''));
            return false;
        }
        return $stmt->get_result();
    }

    /**
     * Append shared allocation filter conditions to a query.
     */
    private function appendAllocationFilters(&$sql, $filters, &$params, &$types) {
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

        if (!empty($filters['gender'])) {
            $sql .= " AND s.student_gender = ?";
            $params[] = $filters['gender'];
            $types .= 's';
        }

        if (!empty($filters['department_id'])) {
            $sql .= " AND c.department_id = ?";
            $params[] = $filters['department_id'];
            $types .= 's';
        }

        if (!empty($filters['academic_year'])) {
            $sql .= " AND se.academic_year = ?";
            $params[] = $filters['academic_year'];
            $types .= 's';
        }

        // Province region: northern | other (non-northern)
        if (!empty($filters['province_region'])) {
            $normalizedProvince = "LOWER(TRIM(REPLACE(REPLACE(IFNULL(s.`student_provice`, ''), ' Province', ''), ' province', '')))";
            if ($filters['province_region'] === 'northern') {
                $sql .= " AND {$normalizedProvince} = 'northern'";
            } elseif ($filters['province_region'] === 'other') {
                $sql .= " AND {$normalizedProvince} <> 'northern'";
            }
        }

        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $sql .= " AND (s.student_fullname LIKE ? OR s.student_id LIKE ? OR r.room_no LIKE ?)";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= 'sss';
        }
    }
    
    /**
     * Get all allocations with student and room info
     */
    public function getAllocations($page = 1, $perPage = 20, $filters = []) {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT ha.*, 
                s.student_id, s.student_fullname, s.student_email, s.student_nic, s.student_gender,
                r.room_no, r.capacity,
                b.name as block_name,
                h.name as hostel_name, h.gender as hostel_gender,
                d.department_name
                FROM `{$this->table}` ha
                LEFT JOIN `student` s ON ha.student_id COLLATE utf8mb4_unicode_ci = s.student_id
                LEFT JOIN `hostel_rooms` r ON ha.room_id = r.id
                LEFT JOIN `hostel_blocks` b ON r.block_id = b.id
                LEFT JOIN `hostels` h ON b.hostel_id = h.id";

        $sql .= $this->getDepartmentFilterJoins();
        $sql .= " LEFT JOIN `department` d ON c.department_id = d.department_id";

        $sql .= " WHERE 1=1";
        
        $params = [];
        $types = '';
        
        $this->appendAllocationFilters($sql, $filters, $params, $types);
        
        $sql .= " ORDER BY ha.allocated_at DESC LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;
        $types .= 'ii';
        
        $result = $this->runFilteredQuery($sql, $types, $params);
        
        $data = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        
        return $data;
    }
    
    /**
     * Get all allocations for export (no pagination)
     */
    public function getAllocationsForExport($filters = []) {
        $sql = "SELECT ha.*, 
                s.student_id, s.student_fullname, s.student_email, s.student_nic, s.student_gender,
                r.room_no, r.capacity,
                b.name as block_name,
                h.name as hostel_name, h.gender as hostel_gender,
                d.department_name
                FROM `{$this->table}` ha
                LEFT JOIN `student` s ON ha.student_id COLLATE utf8mb4_unicode_ci = s.student_id
                LEFT JOIN `hostel_rooms` r ON ha.room_id = r.id
                LEFT JOIN `hostel_blocks` b ON r.block_id = b.id
                LEFT JOIN `hostels` h ON b.hostel_id = h.id";

        $sql .= $this->getDepartmentFilterJoins();
        $sql .= " LEFT JOIN `department` d ON c.department_id = d.department_id";

        $sql .= " WHERE 1=1";
        
        $params = [];
        $types = '';
        
        $this->appendAllocationFilters($sql, $filters, $params, $types);
        
        $sql .= " ORDER BY ha.allocated_at DESC";

        $result = $this->runFilteredQuery($sql, $types, $params);
        
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
        $sql = "SELECT COUNT(DISTINCT ha.id) as total FROM `{$this->table}` ha
                LEFT JOIN `student` s ON ha.student_id COLLATE utf8mb4_unicode_ci = s.student_id
                LEFT JOIN `hostel_rooms` r ON ha.room_id = r.id
                LEFT JOIN `hostel_blocks` b ON r.block_id = b.id";

        if ($this->needsEnrollmentJoin($filters)) {
            $sql .= $this->getDepartmentFilterJoins();
        }

        $sql .= " WHERE 1=1";
        
        $params = [];
        $types = '';
        
        $this->appendAllocationFilters($sql, $filters, $params, $types);

        $result = $this->runFilteredQuery($sql, $types, $params);
        if (!$result) {
            return 0;
        }
        $row = $result->fetch_assoc();
        return (int) ($row['total'] ?? 0);
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
                LEFT JOIN `student` s ON ha.student_id COLLATE utf8mb4_unicode_ci = s.student_id
                LEFT JOIN `hostel_rooms` r ON ha.room_id = r.id
                LEFT JOIN `hostel_blocks` b ON r.block_id = b.id
                LEFT JOIN `hostels` h ON b.hostel_id = h.id
                WHERE ha.id = ?";
        
        $result = $this->runFilteredQuery($sql, 's', [$id]);
        return $result ? $result->fetch_assoc() : null;
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
                WHERE ha.student_id COLLATE utf8mb4_unicode_ci = ? AND ha.status = 'active'
                ORDER BY ha.allocated_at DESC
                LIMIT 1";

        $result = $this->runFilteredQuery($sql, 's', [$studentId]);
        return $result ? $result->fetch_assoc() : null;
    }
    
    /**
     * Get allocations by room ID
     */
    public function getByRoomId($roomId, $filters = []) {
        if (!is_array($filters)) {
            $filters = $filters !== null ? ['status' => $filters] : [];
        }

        $sql = "SELECT ha.*, 
                s.student_id, s.student_fullname, s.student_phone, s.student_nic, s.student_gender,
                r.room_no, r.capacity,
                b.name as block_name,
                h.name as hostel_name,
                d.department_name
                FROM `{$this->table}` ha
                LEFT JOIN `student` s ON ha.student_id COLLATE utf8mb4_unicode_ci = s.student_id
                LEFT JOIN `hostel_rooms` r ON ha.room_id = r.id
                LEFT JOIN `hostel_blocks` b ON r.block_id = b.id
                LEFT JOIN `hostels` h ON b.hostel_id = h.id"
                . $this->getDepartmentFilterJoins() . "
                LEFT JOIN `department` d ON c.department_id = d.department_id
                WHERE ha.room_id = ?";
        
        $params = [$roomId];
        $types = 's';
        
        if (!empty($filters['status'])) {
            $sql .= " AND ha.status = ?";
            $params[] = $filters['status'];
            $types .= 's';
        }

        if (!empty($filters['gender'])) {
            $sql .= " AND s.student_gender = ?";
            $params[] = $filters['gender'];
            $types .= 's';
        }

        if (!empty($filters['department_id'])) {
            $sql .= " AND c.department_id = ?";
            $params[] = $filters['department_id'];
            $types .= 's';
        }

        if (!empty($filters['academic_year'])) {
            $sql .= " AND se.academic_year = ?";
            $params[] = $filters['academic_year'];
            $types .= 's';
        }

        if (!empty($filters['province_region'])) {
            $normalizedProvince = "LOWER(TRIM(REPLACE(REPLACE(IFNULL(s.`student_provice`, ''), ' Province', ''), ' province', '')))";
            if ($filters['province_region'] === 'northern') {
                $sql .= " AND {$normalizedProvince} = 'northern'";
            } elseif ($filters['province_region'] === 'other') {
                $sql .= " AND {$normalizedProvince} <> 'northern'";
            }
        }
        
        $sql .= " ORDER BY ha.allocated_at DESC";

        $result = $this->runFilteredQuery($sql, $types, $params);
        
        $data = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        
        return $data;
    }

    /**
     * Active students keyed by room_id for a set of rooms.
     *
     * @param list<int|string> $roomIds
     * @return array<string, list<array<string,mixed>>>
     */
    public function getActiveStudentsByRoomIds(array $roomIds): array {
        $roomIds = array_values(array_unique(array_filter(array_map('strval', $roomIds), static function ($id) {
            return $id !== '';
        })));
        if ($roomIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($roomIds), '?'));
        $sql = "SELECT ha.room_id, ha.allocated_at, ha.status,
                s.student_id, s.student_fullname, s.student_phone, s.student_nic, s.student_gender,
                d.department_name, c.course_name
                FROM `{$this->table}` ha
                LEFT JOIN `student` s ON ha.student_id COLLATE utf8mb4_unicode_ci = s.student_id"
                . $this->getDepartmentFilterJoins() . "
                LEFT JOIN `department` d ON c.department_id = d.department_id
                WHERE ha.status = 'active' AND ha.room_id IN ($placeholders)
                ORDER BY ha.room_id ASC, s.student_fullname ASC";

        $types = str_repeat('s', count($roomIds));
        $result = $this->runFilteredQuery($sql, $types, $roomIds);
        if (!$result) {
            return [];
        }

        $byRoom = [];
        while ($row = $result->fetch_assoc()) {
            $rid = (string) ($row['room_id'] ?? '');
            if ($rid === '') {
                continue;
            }
            if (!isset($byRoom[$rid])) {
                $byRoom[$rid] = [];
            }
            $byRoom[$rid][] = $row;
        }
        return $byRoom;
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
     * Mark the allocation as left and record the leaving date.
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

