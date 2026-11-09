<?php
/**
 * Hostel Model
 *
 * Table layout: hostels (name, gender, address, active)
 *   -> hostel_blocks (hostel_id, name)   shown to users as "Flats"
 *   -> hostel_rooms  (block_id, room_no, capacity)   capacity = number of beds
 * A hostel's capacity is always the total beds of its rooms, so it is calculated, never typed in.
 */

class HostelModel extends Model {
    protected $table = 'hostels';

    const MAX_ROOMS_PER_FLAT = 100;
    const MAX_BEDS_PER_ROOM = 50;

    protected function getPrimaryKey() {
        return 'id';
    }

    /** SQL fragments that work out flats / rooms / beds / occupied beds for hostel alias h. */
    private function statsSql(): string {
        return "(SELECT COUNT(*) FROM `hostel_blocks` b WHERE b.hostel_id = h.id) AS flat_count,
                (SELECT COUNT(*) FROM `hostel_rooms` r JOIN `hostel_blocks` b ON b.id = r.block_id WHERE b.hostel_id = h.id) AS room_count,
                (SELECT COALESCE(SUM(r.capacity), 0) FROM `hostel_rooms` r JOIN `hostel_blocks` b ON b.id = r.block_id WHERE b.hostel_id = h.id) AS capacity,
                (SELECT COUNT(*) FROM `hostel_allocations` a
                    JOIN `hostel_rooms` r ON r.id = a.room_id
                    JOIN `hostel_blocks` b ON b.id = r.block_id
                    WHERE b.hostel_id = h.id AND a.status = 'active') AS occupied";
    }

    /**
     * Get all hostels with pagination and search (with flats, rooms, capacity and occupied beds)
     */
    public function getHostels($page = 1, $perPage = 20, $search = '') {
        $page = max(1, (int) $page);
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT h.*, " . $this->statsSql() . " FROM `{$this->table}` h WHERE 1=1";
        $params = [];
        $types = '';

        if (!empty($search)) {
            $searchTerm = '%' . $search . '%';
            $sql .= " AND (h.name LIKE ? OR h.address LIKE ? OR h.gender LIKE ?)";
            array_push($params, $searchTerm, $searchTerm, $searchTerm);
            $types .= 'sss';
        }

        $sql .= " ORDER BY h.name ASC LIMIT ? OFFSET ?";
        $params[] = (int) $perPage;
        $params[] = (int) $offset;
        $types .= 'ii';

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        while ($result && ($row = $result->fetch_assoc())) {
            $row['capacity'] = (int) $row['capacity'];
            $row['occupied'] = (int) $row['occupied'];
            $row['available'] = max(0, $row['capacity'] - $row['occupied']);
            $data[] = $row;
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
            $sql .= " AND (name LIKE ? OR address LIKE ? OR gender LIKE ?)";
            array_push($params, $searchTerm, $searchTerm, $searchTerm);
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
        return (int) $row['total'];
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
        return $this->find($id) !== null;
    }

    /**
     * Get hostel by ID, with its flats / rooms / capacity totals
     */
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT h.*, " . $this->statsSql() . " FROM `{$this->table}` h WHERE h.id = ? LIMIT 1");
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ($row) {
            $row['capacity'] = (int) $row['capacity'];
            $row['occupied'] = (int) $row['occupied'];
            $row['available'] = max(0, $row['capacity'] - $row['occupied']);
        }
        return $row ?: null;
    }

    /**
     * Flats of one hostel with their room count, beds and occupied beds.
     */
    public function getFlats($hostelId): array {
        $sql = "SELECT b.id, b.name,
                    COUNT(r.id) AS room_count,
                    COALESCE(SUM(r.capacity), 0) AS capacity,
                    COALESCE(MIN(r.capacity), 0) AS min_cap,
                    COALESCE(MAX(r.capacity), 0) AS max_cap,
                    (SELECT COUNT(*) FROM `hostel_allocations` a
                        JOIN `hostel_rooms` rr ON rr.id = a.room_id
                        WHERE rr.block_id = b.id AND a.status = 'active') AS occupied
                FROM `hostel_blocks` b
                LEFT JOIN `hostel_rooms` r ON r.block_id = b.id
                WHERE b.hostel_id = ?
                GROUP BY b.id, b.name
                ORDER BY b.name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $hostelId);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($result && ($row = $result->fetch_assoc())) {
            $row['room_count'] = (int) $row['room_count'];
            $row['capacity'] = (int) $row['capacity'];
            $row['min_cap'] = (int) $row['min_cap'];
            $row['max_cap'] = (int) $row['max_cap'];
            $row['occupied'] = (int) $row['occupied'];
            // Beds per room when every room has the same size, otherwise null (mixed)
            $row['beds_per_room'] = ($row['room_count'] > 0 && $row['min_cap'] === $row['max_cap']) ? $row['max_cap'] : null;
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Check the flats submitted with a form. Each flat is [name, rooms, beds].
     * Returns the cleaned list (empty rows dropped) or throws InvalidArgumentException.
     *
     * @param array $existingNames Flat names the hostel already has (to refuse duplicates)
     */
    public function cleanFlats(array $names, array $rooms, array $beds, array $existingNames = []): array {
        $taken = [];
        foreach ($existingNames as $n) {
            $taken[strtolower(trim((string) $n))] = true;
        }
        $flats = [];
        foreach ($names as $i => $rawName) {
            $name = trim((string) $rawName);
            $roomCount = trim((string) ($rooms[$i] ?? ''));
            $bedCount = trim((string) ($beds[$i] ?? ''));
            if ($name === '' && $roomCount === '' && $bedCount === '') {
                continue; // blank row
            }
            $label = $name !== '' ? 'Flat "' . $name . '"' : 'A flat';
            if ($name === '') {
                throw new InvalidArgumentException('Enter a name or number for every flat.');
            }
            if (strlen($name) > 50) {
                throw new InvalidArgumentException($label . ': the name can be at most 50 characters.');
            }
            if (!ctype_digit($roomCount) || (int) $roomCount < 1 || (int) $roomCount > self::MAX_ROOMS_PER_FLAT) {
                throw new InvalidArgumentException($label . ': number of rooms must be between 1 and ' . self::MAX_ROOMS_PER_FLAT . '.');
            }
            if (!ctype_digit($bedCount) || (int) $bedCount < 1 || (int) $bedCount > self::MAX_BEDS_PER_ROOM) {
                throw new InvalidArgumentException($label . ': beds per room must be between 1 and ' . self::MAX_BEDS_PER_ROOM . '.');
            }
            $key = strtolower($name);
            if (isset($taken[$key])) {
                throw new InvalidArgumentException($label . ' is used more than once for this hostel.');
            }
            $taken[$key] = true;
            $flats[] = ['name' => $name, 'rooms' => (int) $roomCount, 'beds' => (int) $bedCount];
        }
        return $flats;
    }

    /**
     * Check the edits submitted for the flats a hostel already has.
     * Returns a list of ['id','name','rooms','beds'] (beds is null when left blank = keep as is).
     * Flats listed in $removeIds are skipped. Throws InvalidArgumentException.
     *
     * @param array $currentFlats rows from getFlats() (used to verify ids and detect changes)
     */
    public function cleanFlatEdits(array $ids, array $names, array $rooms, array $beds, array $removeIds, array $currentFlats): array {
        $byId = [];
        foreach ($currentFlats as $f) {
            $byId[(int) $f['id']] = $f;
        }
        $remove = array_map('intval', $removeIds);
        $taken = [];
        $edits = [];
        foreach ($ids as $i => $rawId) {
            $id = (int) $rawId;
            if (!isset($byId[$id]) || in_array($id, $remove, true)) {
                continue;
            }
            $cur = $byId[$id];
            $name = trim((string) ($names[$i] ?? ''));
            $roomCount = trim((string) ($rooms[$i] ?? ''));
            $bedCount = trim((string) ($beds[$i] ?? ''));
            $label = 'Flat "' . $cur['name'] . '"';
            if ($name === '' || strlen($name) > 50) {
                throw new InvalidArgumentException($label . ': enter a name of 1 to 50 characters.');
            }
            if (!ctype_digit($roomCount) || (int) $roomCount < 1 || (int) $roomCount > self::MAX_ROOMS_PER_FLAT) {
                throw new InvalidArgumentException($label . ': number of rooms must be between 1 and ' . self::MAX_ROOMS_PER_FLAT . ' (to delete the flat, tick Remove).');
            }
            if ($bedCount !== '' && (!ctype_digit($bedCount) || (int) $bedCount < 1 || (int) $bedCount > self::MAX_BEDS_PER_ROOM)) {
                throw new InvalidArgumentException($label . ': beds per room must be between 1 and ' . self::MAX_BEDS_PER_ROOM . '.');
            }
            $key = strtolower($name);
            if (isset($taken[$key])) {
                throw new InvalidArgumentException('Flat "' . $name . '" is used more than once for this hostel.');
            }
            $taken[$key] = true;
            $edits[] = ['id' => $id, 'name' => $name, 'rooms' => (int) $roomCount, 'beds' => $bedCount === '' ? null : (int) $bedCount];
        }
        return $edits;
    }

    /**
     * Apply renames / room and bed changes to existing flats. Call inside a transaction.
     * - rooms up: new rooms get the next free room numbers
     * - rooms down: only rooms that never had an allocation can be removed (highest first)
     * - beds: sets the capacity of every room, never below the students currently in it
     */
    private function applyFlatEdits(int $hostelId, array $edits): void {
        foreach ($edits as $e) {
            $blockId = (int) $e['id'];
            $label = 'Flat "' . $e['name'] . '"';

            $stmt = $this->db->prepare("UPDATE `hostel_blocks` SET `name` = ? WHERE `id` = ? AND `hostel_id` = ?");
            $stmt->bind_param('sii', $e['name'], $blockId, $hostelId);
            if (!$stmt->execute()) {
                throw new RuntimeException('Could not rename ' . $label . '.');
            }

            // Rooms of this flat with allocation counts
            $stmt = $this->db->prepare(
                "SELECT r.id, r.room_no, r.capacity,
                        (SELECT COUNT(*) FROM `hostel_allocations` a WHERE a.room_id = r.id) AS total_alloc,
                        (SELECT COUNT(*) FROM `hostel_allocations` a WHERE a.room_id = r.id AND a.status = 'active') AS active_alloc
                 FROM `hostel_rooms` r WHERE r.block_id = ? ORDER BY r.id ASC"
            );
            $stmt->bind_param('i', $blockId);
            $stmt->execute();
            $res = $stmt->get_result();
            $rooms = [];
            while ($res && ($row = $res->fetch_assoc())) {
                $rooms[] = $row;
            }

            // Beds per room
            if ($e['beds'] !== null) {
                $upd = $this->db->prepare("UPDATE `hostel_rooms` SET `capacity` = ? WHERE `id` = ?");
                foreach ($rooms as &$room) {
                    if ((int) $room['capacity'] === $e['beds']) {
                        continue;
                    }
                    if ((int) $room['active_alloc'] > $e['beds']) {
                        throw new InvalidArgumentException($label . ', room ' . $room['room_no'] . ' has ' . (int) $room['active_alloc'] . ' student(s); beds per room cannot be less than that.');
                    }
                    $rid = (int) $room['id'];
                    $upd->bind_param('ii', $e['beds'], $rid);
                    if (!$upd->execute()) {
                        throw new RuntimeException('Could not update beds of ' . $label . '.');
                    }
                    $room['capacity'] = $e['beds'];
                }
                unset($room);
            }

            // Rooms
            $current = count($rooms);
            if ($e['rooms'] > $current) {
                $used = [];
                foreach ($rooms as $room) {
                    $used[(string) $room['room_no']] = true;
                }
                $newCap = $e['beds'] ?? 0;
                if ($newCap < 1) {
                    foreach ($rooms as $room) {
                        $newCap = max($newCap, (int) $room['capacity']);
                    }
                }
                if ($newCap < 1) {
                    throw new InvalidArgumentException($label . ': enter the beds per room to add rooms.');
                }
                $ins = $this->db->prepare("INSERT INTO `hostel_rooms` (`block_id`, `room_no`, `capacity`) VALUES (?, ?, ?)");
                $n = 0;
                for ($added = 0; $added < $e['rooms'] - $current; $added++) {
                    do {
                        $n++;
                        $roomNo = (string) $n;
                    } while (isset($used[$roomNo]));
                    $used[$roomNo] = true;
                    $ins->bind_param('isi', $blockId, $roomNo, $newCap);
                    if (!$ins->execute()) {
                        throw new RuntimeException('Could not add room ' . $roomNo . ' to ' . $label . '.');
                    }
                }
            } elseif ($e['rooms'] < $current) {
                $toRemove = $current - $e['rooms'];
                $del = $this->db->prepare("DELETE FROM `hostel_rooms` WHERE `id` = ?");
                $delAlloc = $this->db->prepare("DELETE FROM `hostel_allocations` WHERE `room_id` = ? AND `status` <> 'active'");
                for ($i = $current - 1; $i >= 0 && $toRemove > 0; $i--) {
                    if ((int) $rooms[$i]['active_alloc'] > 0) {
                        continue;
                    }
                    $rid = (int) $rooms[$i]['id'];
                    $delAlloc->bind_param('i', $rid);
                    $delAlloc->execute();
                    $del->bind_param('i', $rid);
                    $del->execute();
                    $toRemove--;
                }
                if ($toRemove > 0) {
                    throw new InvalidArgumentException($label . ': cannot reduce the rooms to ' . $e['rooms'] . ' because the remaining rooms have students currently allocated.');
                }
            }
        }
    }

    /**
     * Delete whole flats and their rooms. Refused if any room ever had an allocation.
     * Call inside a transaction.
     */
    private function removeFlats(int $hostelId, array $removeIds, array $currentFlats): void {
        $byId = [];
        foreach ($currentFlats as $f) {
            $byId[(int) $f['id']] = $f;
        }
        foreach (array_unique(array_map('intval', $removeIds)) as $blockId) {
            if (!isset($byId[$blockId])) {
                continue;
            }
            $label = 'Flat "' . $byId[$blockId]['name'] . '"';
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) AS c FROM `hostel_allocations` a JOIN `hostel_rooms` r ON r.id = a.room_id WHERE r.block_id = ? AND a.status = 'active'"
            );
            $stmt->bind_param('i', $blockId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if ((int) ($row['c'] ?? 0) > 0) {
                throw new InvalidArgumentException($label . ' cannot be removed because ' . (int) $row['c'] . ' student(s) are currently allocated to its rooms.');
            }
            $stmt = $this->db->prepare("DELETE a FROM `hostel_allocations` a JOIN `hostel_rooms` r ON r.id = a.room_id WHERE r.block_id = ?");
            $stmt->bind_param('i', $blockId);
            $stmt->execute();
            $stmt = $this->db->prepare("DELETE FROM `hostel_rooms` WHERE `block_id` = ?");
            $stmt->bind_param('i', $blockId);
            $stmt->execute();
            $stmt = $this->db->prepare("DELETE FROM `hostel_blocks` WHERE `id` = ? AND `hostel_id` = ?");
            $stmt->bind_param('ii', $blockId, $hostelId);
            if (!$stmt->execute()) {
                throw new RuntimeException('Could not remove ' . $label . '.');
            }
        }
    }
    /**
     * Add flats (blocks) and their rooms to a hostel. Call inside a transaction.
     * Rooms are numbered 1..N inside each flat.
     */
    private function insertFlats($hostelId, array $flats): void {
        foreach ($flats as $flat) {
            $stmt = $this->db->prepare("INSERT INTO `hostel_blocks` (`hostel_id`, `name`) VALUES (?, ?)");
            $stmt->bind_param('is', $hostelId, $flat['name']);
            if (!$stmt->execute()) {
                throw new RuntimeException('Could not save flat ' . $flat['name'] . '.');
            }
            $blockId = (int) $this->db->lastInsertId();
            $roomStmt = $this->db->prepare("INSERT INTO `hostel_rooms` (`block_id`, `room_no`, `capacity`) VALUES (?, ?, ?)");
            for ($n = 1; $n <= $flat['rooms']; $n++) {
                $roomNo = (string) $n;
                $beds = (int) $flat['beds'];
                $roomStmt->bind_param('isi', $blockId, $roomNo, $beds);
                if (!$roomStmt->execute()) {
                    throw new RuntimeException('Could not save room ' . $n . ' of flat ' . $flat['name'] . '.');
                }
            }
        }
    }

    /**
     * Create a hostel together with its flats and rooms (all or nothing).
     *
     * @return int New hostel id
     */
    public function createHostelWithFlats(array $data, array $flats): int {
        $conn = $this->db->getConnection();
        $conn->begin_transaction();
        try {
            $id = $this->create($data, $err);
            if (!$id) {
                throw new RuntimeException('Failed to create hostel' . ($err ? ': ' . $err : '.'));
            }
            $this->insertFlats((int) $id, $flats);
            $conn->commit();
            return (int) $id;
        } catch (Throwable $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     * Update a hostel, edit / remove its existing flats and add new flats (all or nothing).
     */
    public function updateHostelWithFlats($id, array $data, array $flats, array $edits = [], array $removeIds = []): void {
        $conn = $this->db->getConnection();
        $conn->begin_transaction();
        try {
            if (!$this->update($id, $data, $err)) {
                throw new RuntimeException('Failed to update hostel' . ($err ? ': ' . $err : '.'));
            }
            $current = $this->getFlats($id);
            $this->applyFlatEdits((int) $id, $edits);
            $this->removeFlats((int) $id, $removeIds, $current);
            $this->insertFlats((int) $id, $flats);
            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     * Create hostel (plain)
     */
    public function createHostel($data) {
        return $this->create($data);
    }

    /**
     * Update hostel (plain)
     */
    public function updateHostel($id, $data) {
        return $this->update($id, $data);
    }

    /**
     * Allocation records (all / active) on the rooms of a hostel.
     *
     * @return array ['total' => int, 'active' => int]
     */
    public function getAllocationCounts($hostelId): array {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS total, COALESCE(SUM(a.status = 'active'), 0) AS active
             FROM `hostel_allocations` a
             JOIN `hostel_rooms` r ON r.id = a.room_id
             JOIN `hostel_blocks` b ON b.id = r.block_id
             WHERE b.hostel_id = ?"
        );
        $stmt->bind_param('s', $hostelId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: [];
        return ['total' => (int) ($row['total'] ?? 0), 'active' => (int) ($row['active'] ?? 0)];
    }

    /**
     * Delete a hostel together with its flats and rooms.
     * Refused (false + $reason) only while students are currently allocated; past allocation records are deleted with it.
     */
    public function deleteHostel($id, &$reason = null) {
        $alloc = $this->getAllocationCounts($id);
        if ($alloc['active'] > 0) {
            $reason = $alloc['active'] . ' student(s) are currently allocated to rooms in this hostel. Move or release them first, or mark the hostel Inactive.';
            return false;
        }
        $conn = $this->db->getConnection();
        $conn->begin_transaction();
        try {
            // Past allocation records go with the rooms
            $stmt = $this->db->prepare("DELETE a FROM `hostel_allocations` a JOIN `hostel_rooms` r ON r.id = a.room_id JOIN `hostel_blocks` b ON b.id = r.block_id WHERE b.hostel_id = ? AND a.status <> 'active'");
            $stmt->bind_param('s', $id);
            $stmt->execute();
            $stmt = $this->db->prepare("DELETE r FROM `hostel_rooms` r JOIN `hostel_blocks` b ON b.id = r.block_id WHERE b.hostel_id = ?");
            $stmt->bind_param('s', $id);
            $stmt->execute();
            $stmt = $this->db->prepare("DELETE FROM `hostel_blocks` WHERE `hostel_id` = ?");
            $stmt->bind_param('s', $id);
            $stmt->execute();
            if (!$this->delete($id)) {
                throw new RuntimeException('delete failed');
            }
            $conn->commit();
            return true;
        } catch (Throwable $e) {
            $conn->rollback();
            $reason = 'The hostel could not be deleted: ' . $e->getMessage();
            return false;
        }
    }
}