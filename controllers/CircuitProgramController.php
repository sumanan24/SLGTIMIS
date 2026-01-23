<?php
/**
 * Circuit Program Controller
 */

class CircuitProgramController extends Controller {
    
    /**
     * List all circuit programs (staff view)
     */
    public function index() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Restrict student users
        if (isset($_SESSION['user_table']) && $_SESSION['user_table'] === 'student') {
            $_SESSION['error'] = 'Access denied. Students cannot access this page.';
            $this->redirect('student/dashboard');
            return;
        }
        
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        $isAdmin = $userModel->isAdmin($_SESSION['user_id']);
        
        $programModel = $this->model('CircuitProgramModel');
        $departmentModel = $this->model('DepartmentModel');
        
        // Get user's staff ID
        $user = $userModel->find($_SESSION['user_id']);
        $staffId = $user['user_name'] ?? null;
        
        $filters = [
            'status' => $this->get('status', ''),
            'staff_id' => $this->get('staff_id', ''),
            'department_id' => $this->get('department_id', '')
        ];
        
        // If not admin and not approver, show only own programs
        $canApprove = in_array($userRole, ['DIR', 'DPA', 'DPI', 'REG']) || $isAdmin;
        if (!$canApprove && $staffId) {
            $filters['staff_id'] = $staffId;
        }
        
        $programs = $programModel->getAll($filters);
        $departments = $departmentModel->getAll();
        
        $data = [
            'title' => 'Circuit Program',
            'page' => 'circuit-program',
            'programs' => $programs,
            'departments' => $departments,
            'filters' => $filters,
            'canApprove' => $canApprove,
            'message' => $_SESSION['message'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ];
        
        unset($_SESSION['message'], $_SESSION['error']);
        return $this->view('circuit-program/index', $data);
    }
    
    /**
     * Create new circuit program
     */
    public function create() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Restrict student users
        if (isset($_SESSION['user_table']) && $_SESSION['user_table'] === 'student') {
            $_SESSION['error'] = 'Access denied. Students cannot access this page.';
            $this->redirect('student/dashboard');
            return;
        }
        
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $user = $userModel->find($_SESSION['user_id']);
        $staffId = $user['user_name'] ?? null;
        
        // Require staff_id to exist
        if (!$staffId) {
            $_SESSION['error'] = 'Staff ID not found. Only staff members can create circuit programs.';
            $this->redirect('circuit-program');
            return;
        }
        
        $staffModel = $this->model('StaffModel');
        
        // Get staff info - staff must exist in staff table
        $staff = $staffModel->find($staffId);
        if (!$staff) {
            $_SESSION['error'] = 'Staff record not found. Please contact administrator.';
            $this->redirect('circuit-program');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $programModel = $this->model('CircuitProgramModel');
            
            // Use staff's own information - cannot be changed
            $employeeName = $staff['staff_name'];
            $designation = $staff['staff_position'] ?? '';
            $departmentId = $staff['department_id'] ?? '';
            $modeOfTravel = trim($this->post('mode_of_travel', ''));
            
            // Get program details (dates, destinations, purposes)
            $programDetails = [];
            $dates = $this->post('program_date', []);
            $destinations = $this->post('program_destination', []);
            $purposes = $this->post('program_purpose', []);
            
            // Combine into array
            if (is_array($dates) && is_array($destinations)) {
                $maxCount = max(count($dates), count($destinations));
                for ($i = 0; $i < $maxCount; $i++) {
                    if (!empty($dates[$i]) && !empty($destinations[$i])) {
                        $programDetails[] = [
                            'date' => $dates[$i],
                            'destination' => $destinations[$i],
                            'purpose' => $purposes[$i] ?? ''
                        ];
                    }
                }
            }
            
            // Validation
            if (empty($modeOfTravel)) {
                $_SESSION['error'] = 'Mode of Travel is required.';
                $this->redirect('circuit-program/create');
                return;
            }
            
            if (empty($programDetails)) {
                $_SESSION['error'] = 'At least one program detail (date and destination) is required.';
                $this->redirect('circuit-program/create');
                return;
            }
            
            // Validate dates - must be within 3 days before current date or future dates
            $currentDate = date('Y-m-d');
            $minAllowedDate = date('Y-m-d', strtotime('-3 days'));
            
            foreach ($programDetails as $detail) {
                $programDate = $detail['date'];
                if ($programDate < $minAllowedDate) {
                    $_SESSION['error'] = 'Program dates cannot be older than 3 days from today. Please select dates from ' . date('d M Y', strtotime($minAllowedDate)) . ' onwards.';
                    $this->redirect('circuit-program/create');
                    return;
                }
            }
            
            // Prepare data - use staff's own information
            $data = [
                'staff_id' => $staffId,
                'employee_name' => $employeeName, // From staff record
                'designation' => $designation, // From staff record
                'department_id' => $departmentId, // From staff record
                'mode_of_travel' => $modeOfTravel,
                'program_details' => $programDetails
            ];
            
            // Create program
            $programId = $programModel->createProgram($data);
            
            if ($programId) {
                // Log activity
                $this->logActivity('CREATE', 'circuit_program', $programId, 
                    "Created circuit program for {$employeeName}", null, $data);
                
                $_SESSION['message'] = 'Circuit program created successfully. Waiting for approval.';
                $this->redirect('circuit-program');
            } else {
                $_SESSION['error'] = 'Failed to create circuit program. Please try again.';
                $this->redirect('circuit-program/create');
            }
            return;
        }
        
        $data = [
            'title' => 'Create Circuit Program',
            'page' => 'circuit-program-create',
            'staff' => $staff,
            'staffId' => $staffId,
            'message' => $_SESSION['message'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ];
        
        unset($_SESSION['message'], $_SESSION['error']);
        return $this->view('circuit-program/create', $data);
    }
    
    /**
     * View circuit program details
     */
    public function show() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Restrict student users
        if (isset($_SESSION['user_table']) && $_SESSION['user_table'] === 'student') {
            $_SESSION['error'] = 'Access denied. Students cannot access this page.';
            $this->redirect('student/dashboard');
            return;
        }
        
        $programId = (int)$this->get('id', 0);
        if (empty($programId)) {
            $_SESSION['error'] = 'Program ID is required.';
            $this->redirect('circuit-program');
            return;
        }
        
        $programModel = $this->model('CircuitProgramModel');
        $program = $programModel->getById($programId);
        
        if (!$program) {
            $_SESSION['error'] = 'Circuit program not found.';
            $this->redirect('circuit-program');
            return;
        }
        
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        $isAdmin = $userModel->isAdmin($_SESSION['user_id']);
        $canApprove = in_array($userRole, ['DIR', 'DPA', 'DPI', 'REG']) || $isAdmin;
        
        $data = [
            'title' => 'View Circuit Program',
            'page' => 'circuit-program-view',
            'program' => $program,
            'canApprove' => $canApprove,
            'message' => $_SESSION['message'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ];
        
        unset($_SESSION['message'], $_SESSION['error']);
        return $this->view('circuit-program/view', $data);
    }
    
    /**
     * Approval view (DIR, DPA, DPI, REG)
     */
    public function approval() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Restrict student users
        if (isset($_SESSION['user_table']) && $_SESSION['user_table'] === 'student') {
            $_SESSION['error'] = 'Access denied. Students cannot access this page.';
            $this->redirect('student/dashboard');
            return;
        }
        
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        $isAdmin = $userModel->isAdmin($_SESSION['user_id']);
        
        // Check if user is authorized for approval (DIR, DPA, DPI, REG)
        $canApprove = in_array($userRole, ['DIR', 'DPA', 'DPI', 'REG']) || $isAdmin;
        
        if (!$canApprove) {
            $_SESSION['error'] = 'Access denied. Only Director, DPA, DPI, or Registrar can approve circuit programs.';
            $this->redirect('dashboard');
            return;
        }
        
        $programModel = $this->model('CircuitProgramModel');
        $programs = $programModel->getPendingApprovals();
        
        // Get full program details for each program
        $programsWithDetails = [];
        foreach ($programs as $program) {
            $fullProgram = $programModel->getById($program['id']);
            $programsWithDetails[] = $fullProgram;
        }
        
        $data = [
            'title' => 'Circuit Program Approvals',
            'page' => 'circuit-program-approval',
            'programs' => $programsWithDetails,
            'userRole' => $userRole,
            'message' => $_SESSION['message'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ];
        
        unset($_SESSION['message'], $_SESSION['error']);
        return $this->view('circuit-program/approval', $data);
    }
    
    /**
     * Approve/reject circuit program
     */
    public function approve() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Restrict student users
        if (isset($_SESSION['user_table']) && $_SESSION['user_table'] === 'student') {
            $_SESSION['error'] = 'Access denied. Students cannot access this page.';
            $this->redirect('student/dashboard');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Invalid request method.';
            $this->redirect('circuit-program/approval');
            return;
        }
        
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        $isAdmin = $userModel->isAdmin($_SESSION['user_id']);
        
        // Check if user is authorized for approval (DIR, DPA, DPI, REG)
        $canApprove = in_array($userRole, ['DIR', 'DPA', 'DPI', 'REG']) || $isAdmin;
        
        if (!$canApprove) {
            $_SESSION['error'] = 'Access denied. Only Director, DPA, DPI, or Registrar can approve circuit programs.';
            $this->redirect('dashboard');
            return;
        }
        
        $programId = (int)$this->post('program_id', 0);
        $approved = $this->post('action', '') === 'approve';
        $comments = trim($this->post('comments', ''));
        
        if (empty($programId)) {
            $_SESSION['error'] = 'Program ID is required.';
            $this->redirect('circuit-program/approval');
            return;
        }
        
        $programModel = $this->model('CircuitProgramModel');
        
        // Verify program exists and is pending
        $program = $programModel->getById($programId);
        if (!$program || $program['status'] !== 'pending') {
            $_SESSION['error'] = 'Program not found or already processed.';
            $this->redirect('circuit-program/approval');
            return;
        }
        
        // Update approval
        $approverRole = $isAdmin ? 'ADM' : $userRole;
        $success = $programModel->updateApproval($programId, $_SESSION['user_id'], $approverRole, $approved, $comments);
        
        if ($success) {
            // Log activity
            $action = $approved ? 'APPROVE' : 'REJECT';
            $this->logActivity($action, 'circuit_program', $programId, 
                ($approved ? 'Approved' : 'Rejected') . " circuit program for {$program['employee_name']}", 
                ['status' => 'pending'], ['status' => $approved ? 'approved' : 'rejected']);
            
            $_SESSION['message'] = 'Circuit program ' . ($approved ? 'approved' : 'rejected') . ' successfully.';
            $this->redirect('circuit-program/approval');
        } else {
            $_SESSION['error'] = 'Failed to update approval status. Please try again.';
            $this->redirect('circuit-program/approval');
        }
    }
    
    /**
     * Delete circuit program (only if pending or by admin)
     */
    public function delete() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Restrict student users
        if (isset($_SESSION['user_table']) && $_SESSION['user_table'] === 'student') {
            $_SESSION['error'] = 'Access denied. Students cannot access this page.';
            $this->redirect('student/dashboard');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Invalid request method.';
            $this->redirect('circuit-program');
            return;
        }
        
        $programId = (int)$this->post('id', 0);
        if (empty($programId)) {
            $_SESSION['error'] = 'Program ID is required.';
            $this->redirect('circuit-program');
            return;
        }
        
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $isAdmin = $userModel->isAdmin($_SESSION['user_id']);
        
        $programModel = $this->model('CircuitProgramModel');
        $program = $programModel->getById($programId);
        
        if (!$program) {
            $_SESSION['error'] = 'Circuit program not found.';
            $this->redirect('circuit-program');
            return;
        }
        
        // Only allow deletion if pending or if admin
        if ($program['status'] !== 'pending' && !$isAdmin) {
            $_SESSION['error'] = 'Only pending programs can be deleted.';
            $this->redirect('circuit-program');
            return;
        }
        
        // Check if user owns the program or is admin
        $user = $userModel->find($_SESSION['user_id']);
        $staffId = $user['user_name'] ?? null;
        if ($program['staff_id'] !== $staffId && !$isAdmin) {
            $_SESSION['error'] = 'You can only delete your own programs.';
            $this->redirect('circuit-program');
            return;
        }
        
        // Delete program
        $success = $programModel->deleteProgram($programId);
        
        if ($success) {
            // Log activity
            $this->logActivity('DELETE', 'circuit_program', $programId, 
                "Deleted circuit program for {$program['employee_name']}", $program, null);
            
            $_SESSION['message'] = 'Circuit program deleted successfully.';
        } else {
            $_SESSION['error'] = 'Failed to delete circuit program. Please try again.';
        }
        
        $this->redirect('circuit-program');
    }
}

