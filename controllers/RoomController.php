<?php
/**
 * Room Controller
 */

class RoomController extends Controller {
    
    public function index() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Only ADM can access rooms (view and manage)
        if (!$this->checkAdminOrADM()) {
            return;
        }
        
        $roomModel = $this->model('RoomModel');
        $hostelModel = $this->model('HostelModel');
        
        // Check if user is ADM (for displaying create/edit/delete buttons in view)
        $isAdminOrADM = $this->isAdminOrADM();
        
        $page = $this->get('page', 1);
        $search = $this->get('search', '');
        $hostelId = $this->get('hostel_id', '');
        $blockId = $this->get('block_id', '');
        
        $filters = [];
        if (!empty($search)) {
            $filters['search'] = $search;
        }
        if (!empty($hostelId)) {
            $filters['hostel_id'] = $hostelId;
        }
        if (!empty($blockId)) {
            $filters['block_id'] = $blockId;
        }
        
        // Hostel-wise table: load every matching room (no paging) and group by hostel
        $rooms = $roomModel->getRooms(1, 5000, $filters);
        $total = count($rooms);
        $totalPages = 1;
        $page = 1;

        // Active students per room (for room-wise student info)
        $allocationModel = $this->model('RoomAllocationModel');
        $roomIds = array_column($rooms, 'id');
        $studentsByRoom = $allocationModel->getActiveStudentsByRoomIds($roomIds);
        $bedMap = $roomModel->getBedSummaries($roomIds);

        $hostelGroups = [];
        foreach ($rooms as $room) {
            $rid = (string) ($room['id'] ?? '');
            $room['students'] = $studentsByRoom[$rid] ?? [];
            $key = (string) ($room['hostel_id'] ?? 0);
            $flatName = trim((string) ($room['block_name'] ?? ''));
            if ($flatName === '') {
                $flatName = 'Unassigned flat';
            }
            if (!isset($hostelGroups[$key])) {
                $hostelGroups[$key] = [
                    'id' => $key,
                    'name' => $room['hostel_name'] ?? 'Unknown hostel',
                    'rooms' => [],
                    'flats' => [],
                    'capacity' => 0,
                    'occupied' => 0,
                    'students' => 0,
                ];
            }
            $g = &$hostelGroups[$key];
            if (!isset($g['flats'][$flatName])) {
                $g['flats'][$flatName] = [
                    'name' => $flatName,
                    'rooms' => [],
                    'capacity' => 0,
                    'occupied' => 0,
                    'students' => 0,
                ];
            }
            $cap = (int) ($room['capacity'] ?? 0);
            $occ = (int) ($room['occupied_beds'] ?? 0);
            $stu = count($room['students']);
            $g['rooms'][] = $room;
            $g['flats'][$flatName]['rooms'][] = $room;
            $g['flats'][$flatName]['capacity'] += $cap;
            $g['flats'][$flatName]['occupied'] += $occ;
            $g['flats'][$flatName]['students'] += $stu;
            $g['capacity'] += $cap;
            $g['occupied'] += $occ;
            $g['students'] += $stu;
            unset($g);
        }
        foreach ($hostelGroups as &$g) {
            $g['flats'] = array_values($g['flats']);
        }
        unset($g);
        $hostels = $hostelModel->getAll();
        
        // Get blocks for selected hostel
        $blocks = [];
        if (!empty($hostelId)) {
            $blocks = $this->getBlocksByHostel($hostelId);
        }
        
        $data = [
            'title' => 'Rooms',
            'page' => 'rooms',
            'rooms' => $rooms,
            'hostelGroups' => array_values($hostelGroups),
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'search' => $search,
            'hostel_id' => $hostelId,
            'block_id' => $blockId,
            'hostels' => $hostels,
            'blocks' => $blocks,
            'message' => $_SESSION['message'] ?? null,
            'error' => $_SESSION['error'] ?? null,
            'isAdminOrADM' => $isAdminOrADM,
            'bedMap' => $bedMap,
        ];
        
        unset($_SESSION['message'], $_SESSION['error']);
        return $this->view('rooms/index', $data);
    }
    
    public function create() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Only ADM can create rooms
        if (!$this->checkAdminOrADM()) {
            return;
        }
        
        $hostelModel = $this->model('HostelModel');
        $hostels = $hostelModel->getAll();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $roomModel = $this->model('RoomModel');
            
            $data = [
                'block_id' => trim($this->post('block_id', '')),
                'room_no' => trim($this->post('room_no', '')),
                'capacity' => (int)$this->post('capacity', 0),
            ];
            
            // Validation
            if (empty($data['block_id']) || empty($data['room_no'])) {
                $_SESSION['error'] = 'Block and Room Number are required.';
                $this->redirect('rooms/create');
                return;
            }
            
            if ($data['capacity'] < 1) {
                $_SESSION['error'] = 'Capacity must be at least 1.';
                $this->redirect('rooms/create');
                return;
            }
            
            // Check if room number already exists in the same block
            $existingRooms = $roomModel->getByBlockId($data['block_id']);
            foreach ($existingRooms as $room) {
                if ($room['room_no'] === $data['room_no'] && $room['id'] != ($data['id'] ?? null)) {
                    $_SESSION['error'] = 'Room number already exists in this block.';
                    $this->redirect('rooms/create');
                    return;
                }
            }
            
            // Create room
            $result = $roomModel->createRoom($data);
            
            if ($result) {
                $roomModel->ensureBedsForRoom($result);
                $_SESSION['message'] = 'Room created successfully. Beds T1–T4 and B1–B4 were created.';
                $this->redirect('rooms');
            } else {
                $_SESSION['error'] = 'Failed to create room.';
                $this->redirect('rooms/create');
            }
        } else {
            $hostelId = $this->get('hostel_id', '');
            $blocks = [];
            if (!empty($hostelId)) {
                $blocks = $this->getBlocksByHostel($hostelId);
            }
            
            $data = [
                'title' => 'Create Room',
                'page' => 'rooms',
                'hostels' => $hostels,
                'blocks' => $blocks,
                'hostel_id' => $hostelId,
                'error' => $_SESSION['error'] ?? null
            ];
            unset($_SESSION['error']);
            return $this->view('rooms/create', $data);
        }
    }
    
    public function edit() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Only ADM can edit rooms
        if (!$this->checkAdminOrADM()) {
            return;
        }
        
        $id = $this->get('id', '');
        if (empty($id)) {
            $_SESSION['error'] = 'Room ID is required.';
            $this->redirect('rooms');
            return;
        }
        
        $roomModel = $this->model('RoomModel');
        $room = $roomModel->getById($id);
        
        if (!$room) {
            $_SESSION['error'] = 'Room not found.';
            $this->redirect('rooms');
            return;
        }
        
        $hostelModel = $this->model('HostelModel');
        $hostels = $hostelModel->getAll();
        $blocks = $this->getBlocksByHostel($room['hostel_id'] ?? '');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'block_id' => trim($this->post('block_id', '')),
                'room_no' => trim($this->post('room_no', '')),
                'capacity' => (int)$this->post('capacity', 0),
            ];
            
            // Validation
            if (empty($data['block_id']) || empty($data['room_no'])) {
                $_SESSION['error'] = 'Block and Room Number are required.';
                $this->redirect('rooms/edit?id=' . urlencode($id));
                return;
            }
            
            if ($data['capacity'] < 1) {
                $_SESSION['error'] = 'Capacity must be at least 1.';
                $this->redirect('rooms/edit?id=' . urlencode($id));
                return;
            }
            
            // Check if room number already exists in the same block
            $existingRooms = $roomModel->getByBlockId($data['block_id']);
            foreach ($existingRooms as $existingRoom) {
                if ($existingRoom['room_no'] === $data['room_no'] && $existingRoom['id'] != $id) {
                    $_SESSION['error'] = 'Room number already exists in this block.';
                    $this->redirect('rooms/edit?id=' . urlencode($id));
                    return;
                }
            }
            
            // Update room
            $result = $roomModel->updateRoom($id, $data);
            
            if ($result) {
                $added = $roomModel->ensureBedsForRoom($id);
                $_SESSION['message'] = $added > 0
                    ? 'Room updated. ' . $added . ' new bed number(s) were added.'
                    : 'Room updated successfully.';
                $this->redirect('rooms');
            } else {
                $_SESSION['error'] = 'Failed to update room.';
                $this->redirect('rooms/edit?id=' . urlencode($id));
            }
        } else {
            $data = [
                'title' => 'Edit Room',
                'page' => 'rooms',
                'room' => $room,
                'hostels' => $hostels,
                'blocks' => $blocks,
                'error' => $_SESSION['error'] ?? null
            ];
            unset($_SESSION['error']);
            return $this->view('rooms/edit', $data);
        }
    }
    
    public function delete() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Only ADM can delete rooms
        if (!$this->checkAdminOrADM()) {
            return;
        }
        
        $id = $this->get('id', '');
        if (empty($id)) {
            $_SESSION['error'] = 'Room ID is required.';
            $this->redirect('rooms');
            return;
        }
        
        $roomModel = $this->model('RoomModel');
        $room = $roomModel->getById($id);
        
        if (!$room) {
            $_SESSION['error'] = 'Room not found.';
            $this->redirect('rooms');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $reason = null;
            $result = $roomModel->deleteRoom($id, $reason);
            
            if ($result) {
                $_SESSION['message'] = 'Room deleted successfully.';
            } else {
                $_SESSION['error'] = 'Cannot delete room. ' . ($reason ?: 'Please try again.');
            }
            
            $this->redirect('rooms');
        } else {
            $data = [
                'title' => 'Delete Room',
                'page' => 'rooms',
                'room' => $room,
                'allocations' => $roomModel->getAllocationCounts($id)
            ];
            return $this->view('rooms/delete', $data);
        }
    }
    
    /**
     * Get blocks by hostel ID
     */
    private function getBlocksByHostel($hostelId) {
        if (empty($hostelId)) {
            return [];
        }
        
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT * FROM `hostel_blocks` WHERE `hostel_id` = ? ORDER BY `name` ASC");
            $stmt->bind_param("s", $hostelId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $blocks = [];
            while ($row = $result->fetch_assoc()) {
                $blocks[] = $row;
            }
            
            return $blocks;
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * AJAX endpoint to get blocks by hostel
     */
    public function getBlocks() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->json(['success' => false, 'error' => 'Unauthorized'], 401);
            return;
        }
        
        // Only ADM can access rooms
        if (!$this->isAdminOrADM()) {
            $this->json(['success' => false, 'error' => 'Access denied. Rooms section is only available for Administrator.'], 403);
            return;
        }
        
        $hostelId = $this->get('hostel_id', '');
        $blocks = $this->getBlocksByHostel($hostelId);
        $this->json($blocks);
    }

    public function createBeds() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        if (!$this->checkAdminOrADM()) {
            return;
        }
        $hostelId = (int) $this->post('hostel_id', 0);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $hostelId < 1) {
            $_SESSION['error'] = 'Select a hostel, then create the beds.';
            $this->redirect('rooms');
            return;
        }
        $roomModel = $this->model('RoomModel');
        $hostelModel = $this->model('HostelModel');
        $hostel = $hostelModel->getById($hostelId);
        $name = trim((string) ($hostel['name'] ?? ''));
        if ($name === '') {
            $_SESSION['error'] = 'That hostel was not found.';
            $this->redirect('rooms');
            return;
        }
        $result = $roomModel->createMissingBedsForHostel($hostelId);
        if ((int) $result['rooms'] === 0) {
            $_SESSION['error'] = $name . ' has no rooms yet.';
        } elseif ((int) $result['created'] === 0) {
            $_SESSION['message'] = $name . ' already has ' . (int) $result['total'] . ' bed number(s).';
        } else {
            $_SESSION['message'] = 'Created beds T1–T4 and B1–B4 for ' . $name . '. ' . (int) $result['created'] . ' new sticker code(s).';
        }
        $this->redirect('rooms?hostel_id=' . $hostelId);
    }

    public function bedStickers() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        if (!$this->checkAdminOrADM()) {
            return;
        }
        $hostelId = (int) $this->get('hostel_id', 0);
        $roomId = (int) $this->get('room_id', 0);
        $roomModel = $this->model('RoomModel');
        $hostelModel = $this->model('HostelModel');
        $fileLabel = '';
        $room = null;
        if ($roomId > 0) {
            $room = $roomModel->getById($roomId);
            if (!$room) {
                $_SESSION['error'] = 'That room was not found.';
                $this->redirect('rooms');
                return;
            }
            $hostelId = (int) ($room['hostel_id'] ?? 0);
            $beds = $roomModel->getBedsForRoom($roomId);
            $name = trim((string) ($room['hostel_name'] ?? ''));
            $fileLabel = $name . '-flat-' . trim((string) ($room['block_name'] ?? '')) . '-room-' . trim((string) ($room['room_no'] ?? ''));
            $hostel = ['id' => $hostelId, 'name' => $name];
        } else {
            if ($hostelId < 1) {
                $_SESSION['error'] = 'Select a hostel, then print the bed stickers.';
                $this->redirect('rooms');
                return;
            }
            $hostel = $hostelModel->getById($hostelId);
            $name = trim((string) ($hostel['name'] ?? ''));
            if ($name === '') {
                $_SESSION['error'] = 'That hostel was not found.';
                $this->redirect('rooms');
                return;
            }
            $beds = $roomModel->getBedsForStickers($hostelId);
        }
        if ((string) $this->get('download', '') === '1') {
            if (!$beds) {
                $_SESSION['error'] = 'Create the beds first, then download the stickers.';
                $this->redirect('rooms' . ($hostelId > 0 ? '?hostel_id=' . $hostelId : ''));
                return;
            }
            require_once BASE_PATH . '/helpers/BedStickerPdf.php';
            BedStickerPdf::download($beds, $name !== '' ? $name : 'Hostel', 'D', $fileLabel);
            return;
        }
        if ($name === '') {
            $_SESSION['error'] = 'That hostel was not found.';
            $this->redirect('rooms');
            return;
        }
        return $this->view('rooms/bed_stickers', [
            'title' => 'Bed stickers — ' . $name,
            'page' => 'rooms',
            'hostel' => $hostel,
            'hostel_id' => $hostelId,
            'beds' => $beds,
        ]);
    }

    public function assignStudents() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        if (!$this->checkAdminOrADM()) {
            return;
        }
        $roomModel = $this->model('RoomModel');
        $roomId = trim((string) $this->get('room_id', $this->post('room_id', '')));
        $room = $roomId !== '' ? $roomModel->getById($roomId) : null;
        if (!$room) {
            $_SESSION['error'] = 'That room was not found.';
            $this->redirect('rooms');
            return;
        }
        $hostelModel = $this->model('HostelModel');
        $hostel = $hostelModel->getById($room['hostel_id'] ?? 0);
        $gender = $this->hostelGenderKey($hostel['gender'] ?? '');
        $allocationModel = $this->model('RoomAllocationModel');
        $capacity = max(0, (int) ($room['capacity'] ?? 0));
        $occupied = max(0, (int) ($room['occupied_beds'] ?? 0));
        $free = max(0, $capacity - $occupied);
        $slots = min(8, $free);
        $locked = $allocationModel->roomAssignedNvq($roomId);
        $studentModel = $this->model('StudentModel');
        $academicYears = $studentModel->getAcademicYears();
        $academicYear = trim((string) $this->get('academic_year', $this->post('academic_year', '')));
        if (!in_array($academicYear, $academicYears, true)) {
            $academicYear = '';
        }
        $nvq = (string) ((int) $this->get('nvq', $this->post('nvq', '4')));
        if ($nvq !== '4' && $nvq !== '5') {
            $nvq = '4';
        }
        if (count($locked) === 1) {
            $nvq = $locked[0];
        }
        $back = 'rooms?hostel_id=' . (int) ($room['hostel_id'] ?? 0);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($slots < 1) {
                $_SESSION['error'] = 'This room is full.';
                $this->redirect($back);
                return;
            }
            $postedNvq = (string) ((int) $this->post('nvq', ''));
            if (count($locked) === 1 && $postedNvq !== $locked[0]) {
                $_SESSION['error'] = 'This room already has Level ' . str_pad($locked[0], 2, '0', STR_PAD_LEFT) . ' students. Keep the same level.';
                $this->redirect('rooms/assign?room_id=' . urlencode($roomId) . '&nvq=' . urlencode($locked[0]) . $this->assignYearQuery($academicYear));
                return;
            }
            if ($postedNvq === '4' || $postedNvq === '5') {
                $nvq = $postedNvq;
            }
            $raw = $this->post('student_ids', []);
            if (!is_array($raw)) {
                $raw = [$raw];
            }
            $picked = [];
            foreach ($raw as $id) {
                $id = trim((string) $id);
                if ($id !== '') {
                    $picked[$id] = $id;
                }
            }
            $picked = array_slice(array_values($picked), 0, $slots);
            if (!$picked) {
                $_SESSION['error'] = 'Select students for this room.';
                $this->redirect('rooms/assign?room_id=' . urlencode($roomId) . '&nvq=' . urlencode($nvq) . $this->assignYearQuery($academicYear));
                return;
            }
            $allowed = [];
            foreach ($allocationModel->listUnassignedStudents($nvq, $gender, $academicYear) as $stu) {
                $allowed[(string) $stu['student_id']] = true;
            }
            $assigned = 0;
            foreach ($picked as $studentId) {
                if (empty($allowed[$studentId])) {
                    continue;
                }
                $newId = $allocationModel->createAllocation([
                    'student_id' => $studentId,
                    'room_id' => $roomId,
                    'status' => 'active',
                ]);
                if ($newId) {
                    $assigned++;
                }
            }
            if ($assigned > 0) {
                $levelLabel = 'Level ' . str_pad($nvq, 2, '0', STR_PAD_LEFT);
                $_SESSION['message'] = 'Assigned ' . $assigned . ' ' . $levelLabel . ' student(s) to room ' . ($room['room_no'] ?? '') . '.';
                $this->logActivity(
                    'CREATE',
                    'room_allocation',
                    (string) $roomId,
                    'Assigned ' . $assigned . ' ' . $levelLabel . ' students to room ' . ($room['room_no'] ?? $roomId),
                    null,
                    ['room_id' => $roomId, 'nvq' => $nvq, 'count' => $assigned]
                );
            } else {
                $_SESSION['error'] = 'Those students were not assigned. They may already have a room, or this room is full.';
            }
            $this->redirect($back);
            return;
        }

        $students = $slots > 0 ? $allocationModel->listUnassignedStudents($nvq, $gender, $academicYear) : [];
        return $this->view('rooms/assign', [
            'title' => 'Assign students',
            'page' => 'rooms',
            'room' => $room,
            'hostel' => $hostel,
            'nvq' => $nvq,
            'locked' => $locked,
            'slots' => $slots,
            'free' => $free,
            'students' => $students,
            'gender' => $gender,
            'academic_year' => $academicYear,
            'academic_years' => $academicYears,
        ]);
    }

    private function assignYearQuery(string $academicYear): string
    {
        return $academicYear !== '' ? '&academic_year=' . urlencode($academicYear) : '';
    }

    private function hostelGenderKey($gender): string
    {
        $raw = strtolower(trim((string) $gender));
        if ($raw === 'male' || strpos($raw, 'boy') !== false) {
            return 'male';
        }
        if ($raw === 'female' || strpos($raw, 'girl') !== false) {
            return 'female';
        }
        if ($raw !== '' && $raw[0] === 'm') {
            return 'male';
        }
        if ($raw !== '' && $raw[0] === 'f') {
            return 'female';
        }
        return '';
    }
}

