<?php
/**
 * Hostel Controller
 */

class HostelController extends Controller {
    
    public function index() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Only ADM can access hostels (view and manage)
        if (!$this->checkAdminOrADM()) {
            return;
        }
        
        $hostelModel = $this->model('HostelModel');
        
        // Check if user is ADM (for displaying create/edit/delete buttons in view)
        $isAdminOrADM = $this->isAdminOrADM();
        
        $page = $this->get('page', 1);
        $search = $this->get('search', '');
        
        $hostels = $hostelModel->getHostels($page, 20, $search);
        $total = $hostelModel->getTotalHostels($search);
        $totalPages = ceil($total / 20);
        
        $data = [
            'title' => 'Hostels',
            'page' => 'hostels',
            'hostels' => $hostels,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'search' => $search,
            'message' => $_SESSION['message'] ?? null,
            'error' => $_SESSION['error'] ?? null,
            'isAdminOrADM' => $isAdminOrADM
        ];
        
        unset($_SESSION['message'], $_SESSION['error']);
        return $this->view('hostels/index', $data);
    }
    
    /**
     * Read the hostel fields from the form (hostels table: name, gender, address, active).
     */
    private function hostelFormData(): array {
        return [
            'name' => trim((string) $this->post('name', '')),
            'address' => trim((string) $this->post('address', '')),
            'gender' => (string) $this->post('gender', ''),
            'active' => $this->post('status', 'active') === 'inactive' ? 0 : 1,
        ];
    }

    /** Flat rows posted by the form (flat_name[], flat_rooms[], flat_beds[]). */
    private function postedFlats(array $existingNames = []): array {
        $names = $this->post('flat_name', []);
        $rooms = $this->post('flat_rooms', []);
        $beds = $this->post('flat_beds', []);
        return $this->model('HostelModel')->cleanFlats(
            is_array($names) ? $names : [],
            is_array($rooms) ? $rooms : [],
            is_array($beds) ? $beds : [],
            $existingNames
        );
    }

    public function create() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Only ADM can create hostels
        if (!$this->checkAdminOrADM()) {
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $hostelModel = $this->model('HostelModel');
            $data = $this->hostelFormData();
            
            // Validation
            if ($data['name'] === '' || $data['address'] === '' || $data['gender'] === '') {
                $_SESSION['error'] = 'Name, Address, and Gender are required.';
                $this->redirect('hostels/create');
                return;
            }
            
            try {
                $flats = $this->postedFlats();
                $hostelModel->createHostelWithFlats($data, $flats);
                $_SESSION['message'] = 'Hostel created successfully' . ($flats ? ' with ' . count($flats) . ' flat(s).' : '. You can add flats later by editing the hostel.');
                $this->redirect('hostels');
            } catch (Throwable $e) {
                $_SESSION['error'] = $e instanceof InvalidArgumentException ? $e->getMessage() : 'Failed to create hostel. ' . $e->getMessage();
                $this->redirect('hostels/create');
            }
        } else {
            $data = [
                'title' => 'Create Hostel',
                'page' => 'hostels',
                'error' => $_SESSION['error'] ?? null
            ];
            unset($_SESSION['error']);
            return $this->view('hostels/create', $data);
        }
    }
    
    public function edit() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Only ADM can edit hostels
        if (!$this->checkAdminOrADM()) {
            return;
        }
        
        $id = $this->get('id', '');
        if (empty($id)) {
            $_SESSION['error'] = 'Hostel ID is required.';
            $this->redirect('hostels');
            return;
        }
        
        $hostelModel = $this->model('HostelModel');
        $hostel = $hostelModel->getById($id);
        
        if (!$hostel) {
            $_SESSION['error'] = 'Hostel not found.';
            $this->redirect('hostels');
            return;
        }
        
        $flatRows = $hostelModel->getFlats($id);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $this->hostelFormData();
            
            // Validation
            if ($data['name'] === '' || $data['address'] === '' || $data['gender'] === '') {
                $_SESSION['error'] = 'Name, Address, and Gender are required.';
                $this->redirect('hostels/edit?id=' . urlencode($id));
                return;
            }
            
            try {
                $arr = function ($key) { $v = $this->post($key, []); return is_array($v) ? $v : []; };
                $removeIds = $arr('remove_flat');
                $edits = $hostelModel->cleanFlatEdits($arr('edit_flat_id'), $arr('edit_flat_name'), $arr('edit_flat_rooms'), $arr('edit_flat_beds'), $removeIds, $flatRows);
                $flats = $this->postedFlats(array_column($edits, 'name'));
                $hostelModel->updateHostelWithFlats($id, $data, $flats, $edits, $removeIds);
                $_SESSION['message'] = 'Hostel updated successfully' . ($flats ? '; ' . count($flats) . ' flat(s) added.' : '.');
                $this->redirect('hostels');
            } catch (Throwable $e) {
                $_SESSION['error'] = $e instanceof InvalidArgumentException ? $e->getMessage() : 'Failed to update hostel. ' . $e->getMessage();
                $this->redirect('hostels/edit?id=' . urlencode($id));
            }
        } else {
            $data = [
                'title' => 'Edit Hostel',
                'page' => 'hostels',
                'hostel' => $hostel,
                'flats' => $flatRows,
                'error' => $_SESSION['error'] ?? null
            ];
            unset($_SESSION['error']);
            return $this->view('hostels/edit', $data);
        }
    }
        public function delete() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Only ADM can delete hostels
        if (!$this->checkAdminOrADM()) {
            return;
        }
        
        $id = $this->get('id', '');
        if (empty($id)) {
            $_SESSION['error'] = 'Hostel ID is required.';
            $this->redirect('hostels');
            return;
        }
        
        $hostelModel = $this->model('HostelModel');
        $hostel = $hostelModel->getById($id);
        
        if (!$hostel) {
            $_SESSION['error'] = 'Hostel not found.';
            $this->redirect('hostels');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $reason = null;
            $result = $hostelModel->deleteHostel($id, $reason);
            
            if ($result) {
                $_SESSION['message'] = 'Hostel deleted successfully.';
            } else {
                $_SESSION['error'] = 'Cannot delete hostel. ' . ($reason ?: 'Please try again.');
            }
            
            $this->redirect('hostels');
        } else {
            $data = [
                'title' => 'Delete Hostel',
                'page' => 'hostels',
                'hostel' => $hostel,
                'allocations' => $hostelModel->getAllocationCounts($id),
                'flats' => $hostelModel->getFlats($id),
                'rooms' => $this->model('RoomModel')->getRooms(1, 5000, ['hostel_id' => $id])
            ];
            return $this->view('hostels/delete', $data);
        }
    }
}

