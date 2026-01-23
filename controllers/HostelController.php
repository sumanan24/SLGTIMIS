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
            
            $data = [
                'name' => trim($this->post('name', '')),
                'location' => trim($this->post('location', '')),
                'gender' => $this->post('gender', ''),
                'capacity' => (int)$this->post('capacity', 0),
                'description' => trim($this->post('description', '')),
                'status' => $this->post('status', 'active')
            ];
            
            // Validation
            if (empty($data['name']) || empty($data['location']) || empty($data['gender'])) {
                $_SESSION['error'] = 'Name, Location, and Gender are required.';
                $this->redirect('hostels/create');
                return;
            }
            
            if ($data['capacity'] < 1) {
                $_SESSION['error'] = 'Capacity must be at least 1.';
                $this->redirect('hostels/create');
                return;
            }
            
            // Create hostel
            $result = $hostelModel->createHostel($data);
            
            if ($result) {
                $_SESSION['message'] = 'Hostel created successfully.';
                $this->redirect('hostels');
            } else {
                $_SESSION['error'] = 'Failed to create hostel.';
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
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name' => trim($this->post('name', '')),
                'location' => trim($this->post('location', '')),
                'gender' => $this->post('gender', ''),
                'capacity' => (int)$this->post('capacity', 0),
                'description' => trim($this->post('description', '')),
                'status' => $this->post('status', 'active')
            ];
            
            // Validation
            if (empty($data['name']) || empty($data['location']) || empty($data['gender'])) {
                $_SESSION['error'] = 'Name, Location, and Gender are required.';
                $this->redirect('hostels/edit?id=' . urlencode($id));
                return;
            }
            
            if ($data['capacity'] < 1) {
                $_SESSION['error'] = 'Capacity must be at least 1.';
                $this->redirect('hostels/edit?id=' . urlencode($id));
                return;
            }
            
            // Update hostel
            $result = $hostelModel->updateHostel($id, $data);
            
            if ($result) {
                $_SESSION['message'] = 'Hostel updated successfully.';
                $this->redirect('hostels');
            } else {
                $_SESSION['error'] = 'Failed to update hostel.';
                $this->redirect('hostels/edit?id=' . urlencode($id));
            }
        } else {
            $data = [
                'title' => 'Edit Hostel',
                'page' => 'hostels',
                'hostel' => $hostel,
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
            $result = $hostelModel->deleteHostel($id);
            
            if ($result) {
                $_SESSION['message'] = 'Hostel deleted successfully.';
            } else {
                $_SESSION['error'] = 'Cannot delete hostel. It may have rooms assigned.';
            }
            
            $this->redirect('hostels');
        } else {
            $data = [
                'title' => 'Delete Hostel',
                'page' => 'hostels',
                'hostel' => $hostel
            ];
            return $this->view('hostels/delete', $data);
        }
    }
}

