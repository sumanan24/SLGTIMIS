<?php
/**
 * Room Allocation Controller
 */

class RoomAllocationController extends Controller {
    
    public function index() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Allow SAO, ADM, and FIN to view room allocations
        // FIN users have read-only access
        if (!$this->checkRoomAllocationViewAccess()) {
            return;
        }
        
        $allocationModel = $this->model('RoomAllocationModel');
        $hostelModel = $this->model('HostelModel');
        
        $page = $this->get('page', 1);
        $search = $this->get('search', '');
        $hostelId = $this->get('hostel_id', '');
        $roomId = $this->get('room_id', '');
        $status = $this->get('status', '');
        
        $filters = [];
        if (!empty($search)) {
            $filters['search'] = $search;
        }
        if (!empty($hostelId)) {
            $filters['hostel_id'] = $hostelId;
        }
        if (!empty($roomId)) {
            $filters['room_id'] = $roomId;
        }
        if (!empty($status)) {
            $filters['status'] = $status;
        }
        
        $hostels = $hostelModel->getAll();
        $roomModel = $this->model('RoomModel');
        
        // Check if user can manage (create/edit/delete) room allocations
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $canManage = $userModel->canManageRoomAllocations($_SESSION['user_id']);
        
        // If hostel is selected and no room filter, show room-wise card view
        $roomWiseView = !empty($hostelId) && empty($roomId) && empty($search);
        
        if ($roomWiseView) {
            // Get all rooms for the hostel
            $rooms = $roomModel->getByHostelId($hostelId);
            
            // Get allocations grouped by room
            $roomAllocations = [];
            foreach ($rooms as $room) {
                // Get allocations for this room
                $roomAllocs = $allocationModel->getByRoomId($room['id'], !empty($status) ? $status : null);
                
                // Filter by status if specified
                if (!empty($status)) {
                    $roomAllocs = array_filter($roomAllocs, function($alloc) use ($status) {
                        return ($alloc['status'] ?? '') === $status;
                    });
                }
                
                // Show all rooms, even empty ones
                $roomAllocations[$room['id']] = [
                    'room' => $room,
                    'allocations' => array_values($roomAllocs) // Re-index array
                ];
            }
            
            $data = [
                'title' => 'Room Allocations',
                'page' => 'room-allocations',
                'roomWiseView' => true,
                'roomAllocations' => $roomAllocations,
                'search' => $search,
                'hostel_id' => $hostelId,
                'room_id' => $roomId,
                'status' => $status,
                'hostels' => $hostels,
                'rooms' => $rooms,
                'canManage' => $canManage,
                'message' => $_SESSION['message'] ?? null,
                'error' => $_SESSION['error'] ?? null
            ];
        } else {
            // Regular table view
            $allocations = $allocationModel->getAllocations($page, 20, $filters);
            $total = $allocationModel->getTotalAllocations($filters);
            $totalPages = ceil($total / 20);
            
            // Get rooms for the selected hostel
            $rooms = [];
            if (!empty($hostelId)) {
                $rooms = $roomModel->getByHostelId($hostelId);
            }
            
            $data = [
                'title' => 'Room Allocations',
                'page' => 'room-allocations',
                'roomWiseView' => false,
                'allocations' => $allocations,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'total' => $total,
                'search' => $search,
                'hostel_id' => $hostelId,
                'room_id' => $roomId,
                'status' => $status,
                'hostels' => $hostels,
                'rooms' => $rooms,
                'canManage' => $canManage,
                'message' => $_SESSION['message'] ?? null,
                'error' => $_SESSION['error'] ?? null
            ];
        }
        
        unset($_SESSION['message'], $_SESSION['error']);
        return $this->view('room-allocations/index', $data);
    }
    
    public function create() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Only SAO and ADM can create room allocations
        if (!$this->checkRoomAllocationAccess()) {
            return;
        }
        
        $allocationModel = $this->model('RoomAllocationModel');
        $roomModel = $this->model('RoomModel');
        $hostelModel = $this->model('HostelModel');
        $studentModel = $this->model('StudentModel');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'student_id' => trim($this->post('student_id', '')),
                'room_id' => trim($this->post('room_id', '')),
                'allocated_at' => time(),
                'status' => 'active'
            ];
            
            // Validation
            if (empty($data['student_id']) || empty($data['room_id'])) {
                $_SESSION['error'] = 'Student ID and Room are required.';
                $this->redirect('room-allocations/create');
                return;
            }
            
            // Check if student exists
            $student = $studentModel->getById($data['student_id']);
            if (!$student) {
                $_SESSION['error'] = 'Student not found.';
                $this->redirect('room-allocations/create');
                return;
            }
            
            // Check if student already has active allocation
            if ($allocationModel->hasActiveAllocation($data['student_id'])) {
                $_SESSION['error'] = 'Student already has an active room allocation.';
                $this->redirect('room-allocations/create');
                return;
            }
            
            // Check if room has available beds
            if (!$allocationModel->hasAvailableBeds($data['room_id'])) {
                $_SESSION['error'] = 'Selected room is full.';
                $this->redirect('room-allocations/create');
                return;
            }
            
            // Create allocation
            $allocationId = $allocationModel->createAllocation($data);
            
            if ($allocationId) {
                // Log activity
                $this->logActivity(
                    'CREATE',
                    'room_allocation',
                    (string)$allocationId,
                    "Room allocated: Student {$data['student_id']} allocated to Room {$data['room_id']}",
                    null,
                    $data
                );
                
                $_SESSION['message'] = 'Room allocated successfully.';
                $this->redirect('room-allocations');
            } else {
                $_SESSION['error'] = 'Failed to allocate room.';
                $this->redirect('room-allocations/create');
            }
        } else {
            $hostelId = $this->get('hostel_id', '');
            $hostels = $hostelModel->getAll();
            $rooms = [];
            
            if (!empty($hostelId)) {
                $rooms = $roomModel->getAvailableRooms($hostelId);
            }
            
            $data = [
                'title' => 'Allocate Room',
                'page' => 'room-allocations',
                'hostels' => $hostels,
                'rooms' => $rooms,
                'hostel_id' => $hostelId,
                'error' => $_SESSION['error'] ?? null
            ];
            unset($_SESSION['error']);
            return $this->view('room-allocations/create', $data);
        }
    }
    
    public function edit() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Only SAO and ADM can edit room allocations
        if (!$this->checkRoomAllocationAccess()) {
            return;
        }
        
        $id = $this->get('id', '');
        if (empty($id)) {
            $_SESSION['error'] = 'Allocation ID is required.';
            $this->redirect('room-allocations');
            return;
        }
        
        $allocationModel = $this->model('RoomAllocationModel');
        $allocation = $allocationModel->getById($id);
        
        if (!$allocation) {
            $_SESSION['error'] = 'Allocation not found.';
            $this->redirect('room-allocations');
            return;
        }
        
        $roomModel = $this->model('RoomModel');
        $hostelModel = $this->model('HostelModel');
        $hostels = $hostelModel->getAll();
        $rooms = $roomModel->getAvailableRooms($allocation['hostel_id'] ?? '');
        
        // Include current room even if full
        $currentRoom = $roomModel->getById($allocation['room_id']);
        if ($currentRoom) {
            $found = false;
            foreach ($rooms as $room) {
                if ($room['id'] == $currentRoom['id']) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $rooms[] = $currentRoom;
            }
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $newRoomId = trim($this->post('room_id', ''));
            $status = $this->post('status', 'active');
            
            // Validation
            if (empty($newRoomId)) {
                $_SESSION['error'] = 'Room is required.';
                $this->redirect('room-allocations/edit?id=' . urlencode($id));
                return;
            }
            
            $data = [
                'room_id' => $newRoomId,
                'status' => $status
            ];
            
            // If changing room, check availability
            if ($newRoomId != $allocation['room_id'] && $status === 'active') {
                if (!$allocationModel->hasAvailableBeds($newRoomId)) {
                    $_SESSION['error'] = 'Selected room is full.';
                    $this->redirect('room-allocations/edit?id=' . urlencode($id));
                    return;
                }
            }
            
            // If deallocating, set deallocated_at
            if ($status === 'inactive' && $allocation['status'] === 'active') {
                $data['deallocated_at'] = time();
            }
            
            // Update allocation
            $result = $allocationModel->updateAllocation($id, $data);
            
            if ($result) {
                $_SESSION['message'] = 'Allocation updated successfully.';
                $this->redirect('room-allocations');
            } else {
                $_SESSION['error'] = 'Failed to update allocation.';
                $this->redirect('room-allocations/edit?id=' . urlencode($id));
            }
        } else {
            $data = [
                'title' => 'Edit Allocation',
                'page' => 'room-allocations',
                'allocation' => $allocation,
                'hostels' => $hostels,
                'rooms' => $rooms,
                'error' => $_SESSION['error'] ?? null
            ];
            unset($_SESSION['error']);
            return $this->view('room-allocations/edit', $data);
        }
    }
    
    public function delete() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Only SAO and ADM can delete room allocations
        if (!$this->checkRoomAllocationAccess()) {
            return;
        }
        
        $id = $this->get('id', '');
        if (empty($id)) {
            $_SESSION['error'] = 'Allocation ID is required.';
            $this->redirect('room-allocations');
            return;
        }
        
        $allocationModel = $this->model('RoomAllocationModel');
        $allocation = $allocationModel->getById($id);
        
        if (!$allocation) {
            $_SESSION['error'] = 'Allocation not found.';
            $this->redirect('room-allocations');
            return;
        }
        
        // Store old values for logging
        $oldValues = $allocation;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $allocationModel->deleteAllocation($id);
            
            if ($result) {
                // Log activity
                $this->logActivity(
                    'DELETE',
                    'room_allocation',
                    (string)$id,
                    "Room allocation deleted: Allocation ID {$id} - Student {$allocation['student_id']}",
                    $oldValues,
                    null
                );
                
                $_SESSION['message'] = 'Allocation deleted successfully.';
            } else {
                $_SESSION['error'] = 'Failed to delete allocation.';
            }
            
            $this->redirect('room-allocations');
        } else {
            $data = [
                'title' => 'Delete Allocation',
                'page' => 'room-allocations',
                'allocation' => $allocation
            ];
            return $this->view('room-allocations/delete', $data);
        }
    }
    
    /**
     * Deallocate (set status to inactive)
     */
    public function deallocate() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Only SAO and ADM can deallocate rooms
        if (!$this->checkRoomAllocationAccess()) {
            return;
        }
        
        $id = $this->get('id', '');
        if (empty($id)) {
            $_SESSION['error'] = 'Allocation ID is required.';
            $this->redirect('room-allocations');
            return;
        }
        
        $allocationModel = $this->model('RoomAllocationModel');
        $allocation = $allocationModel->getById($id);
        
        if (!$allocation) {
            $_SESSION['error'] = 'Allocation not found.';
            $this->redirect('room-allocations');
            return;
        }
        
        // Store old values for logging
        $oldValues = $allocation;
        
        $result = $allocationModel->deallocate($id);
        
        if ($result) {
            // Log activity
            $this->logActivity(
                'UPDATE',
                'room_allocation',
                (string)$id,
                "Room deallocated: Allocation ID {$id} - Student {$allocation['student_id']}",
                $oldValues,
                ['status' => 'inactive', 'deallocated_at' => time()]
            );
            
            $_SESSION['message'] = 'Room deallocated successfully.';
        } else {
            $_SESSION['error'] = 'Failed to deallocate room.';
        }
        
        $this->redirect('room-allocations');
    }
    
    /**
     * AJAX endpoint to get available rooms by hostel
     */
    public function getAvailableRooms() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->json(['success' => false, 'error' => 'Unauthorized'], 401);
            return;
        }
        
        // Only SAO and ADM can access room allocations
        if (!$this->canManageRoomAllocations()) {
            $this->json(['success' => false, 'error' => 'Access denied. Room Allocations section is only available for Student Affairs Office or Administrator.'], 403);
            return;
        }
        
        $hostelId = $this->get('hostel_id', '');
        $roomModel = $this->model('RoomModel');
        // For filter, get all rooms, not just available ones
        if (!empty($hostelId)) {
            $rooms = $roomModel->getByHostelId($hostelId);
        } else {
            $rooms = [];
        }
        $this->json($rooms);
    }
}

