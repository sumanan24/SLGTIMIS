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
        
        $rooms = $roomModel->getRooms($page, 20, $filters);
        $total = $roomModel->getTotalRooms($filters);
        $totalPages = ceil($total / 20);
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
            'isAdminOrADM' => $isAdminOrADM
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
                'room_type' => trim($this->post('room_type', '')),
                'capacity' => (int)$this->post('capacity', 0),
                'description' => trim($this->post('description', '')),
                'status' => $this->post('status', 'active')
            ];
            
            // Validation
            if (empty($data['block_id']) || empty($data['room_no'])) {
                $_SESSION['error'] = 'Block and Room Number are required.';
                $this->redirect('rooms/create');
                return;
            }
            
            // Remove room_type if not needed (column doesn't exist in DB)
            unset($data['room_type']);
            
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
                $_SESSION['message'] = 'Room created successfully.';
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
                'room_type' => trim($this->post('room_type', '')),
                'capacity' => (int)$this->post('capacity', 0),
                'description' => trim($this->post('description', '')),
                'status' => $this->post('status', 'active')
            ];
            
            // Validation
            if (empty($data['block_id']) || empty($data['room_no'])) {
                $_SESSION['error'] = 'Block and Room Number are required.';
                $this->redirect('rooms/edit?id=' . urlencode($id));
                return;
            }
            
            // Remove room_type if not needed (column doesn't exist in DB)
            unset($data['room_type']);
            
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
                $_SESSION['message'] = 'Room updated successfully.';
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
            $result = $roomModel->deleteRoom($id);
            
            if ($result) {
                $_SESSION['message'] = 'Room deleted successfully.';
            } else {
                $_SESSION['error'] = 'Cannot delete room. It may have active allocations.';
            }
            
            $this->redirect('rooms');
        } else {
            $data = [
                'title' => 'Delete Room',
                'page' => 'rooms',
                'room' => $room
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
}

