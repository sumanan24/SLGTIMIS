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
        
        $sql = "SELECT r.*, h.name as hostel_name, b.hostel_id as hostel_id, b.name as block_name,
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
        
        $sql .= " ORDER BY h.name, b.name, CAST(r.room_no AS UNSIGNED), r.room_no ASC LIMIT ? OFFSET ?";
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
        $sql = "SELECT r.*, b.name as block_name,
                (SELECT COUNT(*) FROM hostel_allocations WHERE room_id = r.id AND status = 'active') as occupied_beds
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
     * Allocation records (all / active) of one room.
     *
     * @return array ['total' => int, 'active' => int]
     */
    public function getAllocationCounts($roomId): array {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS total, COALESCE(SUM(status = 'active'), 0) AS active FROM `hostel_allocations` WHERE room_id = ?"
        );
        $stmt->bind_param('s', $roomId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: [];
        return ['total' => (int) ($row['total'] ?? 0), 'active' => (int) ($row['active'] ?? 0)];
    }

    /**
     * Delete room. Refused (false + $reason) only while students are currently allocated; past allocation records are deleted with it.
     */
    public function deleteRoom($id, &$reason = null) {
        $alloc = $this->getAllocationCounts($id);
        if ($alloc['active'] > 0) {
            $reason = $alloc['active'] . ' student(s) are currently allocated to this room. Move or release them first.';
            return false;
        }
        // Past (left / cancelled) allocation records are removed together with the room
        $conn = $this->db->getConnection();
        $conn->begin_transaction();
        try {
            $this->ensureBedTable();
            $bedStmt = $this->db->prepare("DELETE FROM `hostel_beds` WHERE `room_id` = ?");
            $bedStmt->bind_param('s', $id);
            $bedStmt->execute();
            $stmt = $this->db->prepare("DELETE FROM `hostel_allocations` WHERE `room_id` = ? AND `status` <> 'active'");
            $stmt->bind_param('s', $id);
            $stmt->execute();
            if (!$this->delete($id)) {
                throw new RuntimeException('delete failed');
            }
            $conn->commit();
            return true;
        } catch (Throwable $e) {
            $conn->rollback();
            $reason = 'The room could not be deleted. Please try again.';
            return false;
        }
    }
    /**
     * Room occupancy for the hostel allocated / available report.
     * Occupancy filter is applied by the caller after available beds are calculated.
     */
    public function getOccupancyReport($filters = []) {
        $sql = "SELECT r.id, r.room_no, r.capacity,
                b.name as block_name,
                h.id as hostel_id, h.name as hostel_name, h.gender as hostel_gender, h.address as location,
                (SELECT COUNT(*) FROM hostel_allocations ha WHERE ha.room_id = r.id AND ha.status = 'active') as allocated
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

        if (!empty($filters['gender'])) {
            $sql .= " AND h.gender = ?";
            $params[] = $filters['gender'];
            $types .= 's';
        }

        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $sql .= " AND (r.room_no LIKE ? OR h.name LIKE ? OR b.name LIKE ?)";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= 'sss';
        }

        $sql .= " ORDER BY h.name ASC, b.name ASC, r.room_no ASC";

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
                $capacity = (int) ($row['capacity'] ?? 0);
                $allocated = (int) ($row['allocated'] ?? 0);
                $row['capacity'] = $capacity;
                $row['allocated'] = $allocated;
                $row['available'] = $capacity - $allocated;
                $data[] = $row;
            }
        }

        return $data;
    }

    public function ensureBedTable(): void
    {
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS `hostel_beds` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `room_id` INT NOT NULL,
                `bed_no` VARCHAR(10) NOT NULL,
                `barcode` VARCHAR(40) NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq_room_bed` (`room_id`, `bed_no`),
                UNIQUE KEY `uniq_barcode` (`barcode`),
                KEY `idx_room` (`room_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
        $chk = $this->db->query(
            "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hostel_beds' AND CONSTRAINT_NAME = 'fk_hostel_beds_room'"
        );
        if ($chk && $chk->num_rows === 0) {
            $this->db->query(
                "ALTER TABLE `hostel_beds`
                 ADD CONSTRAINT `fk_hostel_beds_room`
                 FOREIGN KEY (`room_id`) REFERENCES `hostel_rooms` (`id`) ON DELETE CASCADE"
            );
        }
    }

    /** Top bunks T1–T4 and bottom bunks B1–B4. */
    public function bunkBedLabels(): array
    {
        return ['T1', 'T2', 'T3', 'T4', 'B1', 'B2', 'B3', 'B4'];
    }

    /**
     * Give every room in a hostel beds T1–T4 and B1–B4.
     *
     * @return array{created:int,rooms:int,total:int}
     */
    public function createMissingBedsForHostel(int $hostelId): array
    {
        $this->ensureBedTable();
        $this->removeNonBunkBedsForHostel($hostelId);
        $stmt = $this->db->prepare(
            "SELECT r.id, r.room_no, b.name AS block_name, h.name AS hostel_name
             FROM `hostel_rooms` r
             JOIN `hostel_blocks` b ON b.id = r.block_id
             JOIN `hostels` h ON h.id = b.hostel_id
             WHERE b.hostel_id = ?"
        );
        $stmt->bind_param('i', $hostelId);
        $stmt->execute();
        $result = $stmt->get_result();
        $rooms = [];
        while ($row = $result->fetch_assoc()) {
            $rooms[] = $row;
        }
        $includeFloor = $this->hostelUsesFloors($hostelId);
        $created = 0;
        foreach ($rooms as $room) {
            $created += $this->insertBunkBeds(
                (int) $room['id'],
                (string) $room['hostel_name'],
                (string) $room['block_name'],
                (string) $room['room_no'],
                $includeFloor
            );
        }
        $countStmt = $this->db->prepare(
            "SELECT COUNT(*) AS c FROM `hostel_beds` bd
             JOIN `hostel_rooms` r ON r.id = bd.room_id
             JOIN `hostel_blocks` b ON b.id = r.block_id
             WHERE b.hostel_id = ?"
        );
        $countStmt->bind_param('i', $hostelId);
        $countStmt->execute();
        $row = $countStmt->get_result()->fetch_assoc();
        return ['created' => $created, 'rooms' => count($rooms), 'total' => (int) ($row['c'] ?? 0)];
    }

    public function ensureBedsForRoom($roomId): int
    {
        $room = $this->getById($roomId);
        if (!$room) {
            return 0;
        }
        $this->ensureBedTable();
        $rid = (int) $room['id'];
        $del = $this->db->prepare(
            "DELETE FROM `hostel_beds` WHERE `room_id` = ? AND `bed_no` NOT IN ('T1','T2','T3','T4','B1','B2','B3','B4')"
        );
        $del->bind_param('i', $rid);
        $del->execute();
        return $this->insertBunkBeds(
            $rid,
            (string) ($room['hostel_name'] ?? ''),
            (string) ($room['block_name'] ?? ''),
            (string) ($room['room_no'] ?? ''),
            $this->hostelUsesFloors((int) ($room['hostel_id'] ?? 0))
        );
    }

    private function removeNonBunkBedsForHostel(int $hostelId): void
    {
        $del = $this->db->prepare(
            "DELETE bd FROM `hostel_beds` bd
             JOIN `hostel_rooms` r ON r.id = bd.room_id
             JOIN `hostel_blocks` b ON b.id = r.block_id
             WHERE b.hostel_id = ? AND bd.bed_no NOT IN ('T1','T2','T3','T4','B1','B2','B3','B4')"
        );
        $del->bind_param('i', $hostelId);
        $del->execute();
    }

    private function hostelUsesFloors(int $hostelId): bool
    {
        if ($hostelId < 1) {
            return false;
        }
        $stmt = $this->db->prepare("SELECT COUNT(*) AS c FROM `hostel_blocks` WHERE `hostel_id` = ?");
        $stmt->bind_param('i', $hostelId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return (int) ($row['c'] ?? 0) > 1;
    }

    private function insertBunkBeds(int $roomId, string $hostel, string $block, string $roomNo, bool $includeFloor): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO `hostel_beds` (`room_id`, `bed_no`, `barcode`) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE `barcode` = VALUES(`barcode`)"
        );
        $created = 0;
        foreach ($this->bunkBedLabels() as $bedNo) {
            $code = $this->bedBarcode($hostel, $block, $roomNo, $bedNo, $includeFloor);
            $stmt->bind_param('iss', $roomId, $bedNo, $code);
            $stmt->execute();
            if ($stmt->affected_rows === 1) {
                $created++;
            }
        }
        return $created;
    }

    private function bedBarcode(string $hostel, string $block, string $roomNo, string $bedNo, bool $includeFloor): string
    {
        $parts = [$this->barcodePart($hostel)];
        if ($includeFloor) {
            $floor = $this->barcodePart($block);
            if ($floor !== '') {
                $parts[] = $floor;
            }
        }
        $parts[] = $this->barcodePart($roomNo);
        $parts[] = $this->barcodePart($bedNo);
        $parts = array_values(array_filter($parts, static function ($part) {
            return $part !== '';
        }));
        $code = implode('/', $parts);
        if ($code === '') {
            $code = $this->barcodePart($bedNo);
        }
        return substr($code, 0, 40);
    }

    private function barcodePart(string $value): string
    {
        $token = strtoupper(trim($value));
        $token = preg_replace('/[^0-9A-Z]+/', '', $token) ?? '';
        return $token;
    }

    /**
     * @param array<int|string> $roomIds
     * @return array<string, array{count:int,bunks:int}>
     */
    public function getBedSummaries(array $roomIds): array
    {
        $ids = [];
        foreach ($roomIds as $id) {
            $n = (int) $id;
            if ($n > 0) {
                $ids[$n] = $n;
            }
        }
        if (!$ids) {
            return [];
        }
        $this->ensureBedTable();
        $in = implode(',', $ids);
        $result = $this->db->query(
            "SELECT room_id, COUNT(*) AS c,
                    SUM(bed_no IN ('T1','T2','T3','T4','B1','B2','B3','B4')) AS bunks
             FROM `hostel_beds` WHERE room_id IN ($in) GROUP BY room_id"
        );
        $map = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $map[(string) $row['room_id']] = [
                    'count' => (int) $row['c'],
                    'bunks' => (int) $row['bunks'],
                ];
            }
        }
        return $map;
    }

    public function getBedsForRoom(int $roomId): array
    {
        return $this->fetchStickerBeds('r.id = ?', $roomId);
    }

    public function getBedsForStickers(int $hostelId): array
    {
        return $this->fetchStickerBeds('h.id = ?', $hostelId);
    }

    private function fetchStickerBeds(string $where, int $id): array
    {
        $this->ensureBedTable();
        $stmt = $this->db->prepare(
            "SELECT bd.bed_no, bd.barcode, r.room_no, r.id AS room_id,
                    b.name AS block_name, h.name AS hostel_name
             FROM `hostel_beds` bd
             JOIN `hostel_rooms` r ON r.id = bd.room_id
             JOIN `hostel_blocks` b ON b.id = r.block_id
             JOIN `hostels` h ON h.id = b.hostel_id
             WHERE $where
             ORDER BY b.name ASC, CAST(r.room_no AS UNSIGNED) ASC, r.room_no ASC,
                      CASE LEFT(bd.bed_no, 1) WHEN 'T' THEN 1 WHEN 'B' THEN 2 ELSE 3 END,
                      CAST(SUBSTRING(bd.bed_no, 2) AS UNSIGNED) ASC"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }
}

