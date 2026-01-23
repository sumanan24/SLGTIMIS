<?php
/**
 * On-Peak/Off-Peak Request Controller
 */

class OnPeakRequestController extends Controller {
    
    /**
     * Student view - List and submit requests
     */
    public function index() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Check if user is a student
        if (!isset($_SESSION['user_table']) || $_SESSION['user_table'] !== 'student') {
            $_SESSION['error'] = 'Access denied. This section is only available for students.';
            $this->redirect('dashboard');
            return;
        }
        
        $studentId = $_SESSION['user_name'];
        $studentModel = $this->model('StudentModel');
        $requestModel = $this->model('OnPeakRequestModel');
        
        // Ensure table exists
        $requestModel->addRequiredColumnsIfNotExists();
        
        // Get student info
        $student = $studentModel->find($studentId);
        if (!$student) {
            $_SESSION['error'] = 'Student record not found.';
            $this->redirect('student/dashboard');
            return;
        }
        
        // Get current enrollment
        $enrollmentModel = $this->model('StudentEnrollmentModel');
        $currentEnrollment = $enrollmentModel->getCurrentEnrollment($studentId);
        $academicYear = $currentEnrollment['academic_year'] ?? date('Y');
        
        // Check if student is hostel student
        $roomAllocationModel = $this->model('RoomAllocationModel');
        $hostelAllocation = $roomAllocationModel->getActiveByStudentId($studentId);
        $isHostelStudent = !empty($hostelAllocation);
        
        // Get academic years
        $academicYears = $studentModel->getAcademicYears();
        
        // Get requests for student
        $requestModel->addRequiredColumnsIfNotExists();
        $requests = $requestModel->getByStudentId($studentId);
        
        $data = [
            'title' => 'On-Peak Request',
            'page' => 'on-peak-requests',
            'student' => $student,
            'currentEnrollment' => $currentEnrollment,
            'requests' => $requests,
            'isHostelStudent' => $isHostelStudent,
            'hostelAllocation' => $hostelAllocation,
            'message' => $_SESSION['message'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ];
        
        unset($_SESSION['message'], $_SESSION['error']);
        return $this->view('on-peak-requests/index', $data);
    }
    
    /**
     * Student submit request
     */
    public function create() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Check if user is a student
        if (!isset($_SESSION['user_table']) || $_SESSION['user_table'] !== 'student') {
            $_SESSION['error'] = 'Access denied. This section is only available for students.';
            $this->redirect('dashboard');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Invalid request method.';
            $this->redirect('on-peak-requests');
            return;
        }
        
        $studentId = $_SESSION['user_name'];
        $studentModel = $this->model('StudentModel');
        $requestModel = $this->model('OnPeakRequestModel');
        
        // Add required columns if they don't exist
        $requestModel->addRequiredColumnsIfNotExists();
        
        // Get student info
        $student = $studentModel->find($studentId);
        if (!$student) {
            $_SESSION['error'] = 'Student record not found.';
            $this->redirect('on-peak-requests');
            return;
        }
        
        // Get current enrollment for department_id
        $enrollmentModel = $this->model('StudentEnrollmentModel');
        $currentEnrollment = $enrollmentModel->getCurrentEnrollment($studentId);
        $departmentId = $currentEnrollment['department_id'] ?? '';
        
        // Check if student is hostel student
        $roomAllocationModel = $this->model('RoomAllocationModel');
        $hostelAllocation = $roomAllocationModel->getActiveByStudentId($studentId);
        $isHostelStudent = !empty($hostelAllocation) ? 1 : ($this->post('is_hostel_student', '0') === '1' ? 1 : 0);
        
        // Get form data
        $contactNo = trim($this->post('contact_no', ''));
        $reason = trim($this->post('reason', ''));
        $exitDate = trim($this->post('exit_date', ''));
        $exitTime = trim($this->post('exit_time', ''));
        $returnDate = trim($this->post('return_date', ''));
        $returnTime = trim($this->post('return_time', ''));
        $comment = trim($this->post('comment', ''));
        
        // Validation
        if (empty($exitDate)) {
            $_SESSION['error'] = 'Exit date is required.';
            $this->redirect('on-peak-requests');
            return;
        }
        
        if (empty($exitTime)) {
            $_SESSION['error'] = 'Exit time is required.';
            $this->redirect('on-peak-requests');
            return;
        }
        
        if (empty($returnDate)) {
            $_SESSION['error'] = 'Return date is required.';
            $this->redirect('on-peak-requests');
            return;
        }
        
        if (empty($returnTime)) {
            $_SESSION['error'] = 'Return time is required.';
            $this->redirect('on-peak-requests');
            return;
        }
        
        if (empty($reason)) {
            $_SESSION['error'] = 'Reason for exit is required.';
            $this->redirect('on-peak-requests');
            return;
        }
        
        if (empty($contactNo)) {
            $_SESSION['error'] = 'Contact number is required.';
            $this->redirect('on-peak-requests');
            return;
        }
        
        // Check if student already has pending request
        if ($requestModel->hasPendingRequest($studentId)) {
            $_SESSION['error'] = 'You already have a pending request. Please wait for approval or cancel the existing request.';
            $this->redirect('on-peak-requests');
            return;
        }
        
        // Create request
        $requestData = [
            'student_id' => $studentId,
            'department_id' => $departmentId,
            'contact_no' => (int)$contactNo,
            'reason' => $reason,
            'exit_date' => $exitDate,
            'exit_time' => $exitTime,
            'return_date' => $returnDate,
            'return_time' => $returnTime,
            'comment' => $comment,
            'is_hostel_student' => $isHostelStudent
        ];
        
        $requestId = $requestModel->createRequest($requestData);
        
        if ($requestId) {
            // Log activity
            $this->logActivity(
                'CREATE',
                'on_peak_request',
                (string)$requestId,
                "Student {$studentId} created temporary exit request (Hostel: " . ($isHostelStudent ? 'Yes' : 'No') . ")",
                null,
                $requestData
            );
            
            if ($isHostelStudent) {
                $_SESSION['message'] = 'Temporary exit request submitted. Your request requires HOD approval first, then Director/Warden approval.';
            } else {
                $_SESSION['message'] = 'Temporary exit request submitted. Your request will be reviewed by the Head of Department. Once approved, you can temporarily exit SLGTI.';
            }
        } else {
            $_SESSION['error'] = 'Failed to submit request. Please try again.';
        }
        
        $this->redirect('on-peak-requests');
    }
    
    /**
     * HOD approval view
     */
    public function hodApproval() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Check if user is HOD
        if (!$this->isHOD()) {
            $_SESSION['error'] = 'Access denied. Only Head of Department can approve requests.';
            $this->redirect('dashboard');
            return;
        }
        
        $hodDepartmentId = $this->getHODDepartment();
        if (!$hodDepartmentId) {
            $_SESSION['error'] = 'Department information not found.';
            $this->redirect('dashboard');
            return;
        }
        
        $requestModel = $this->model('OnPeakRequestModel');
        
        // Ensure table exists
        $requestModel->addRequiredColumnsIfNotExists();
        
        // Get pending requests for HOD's department
        $requests = $requestModel->getPendingHODRequests($hodDepartmentId);
        
        // Get department info
        $departmentModel = $this->model('DepartmentModel');
        $department = $departmentModel->getById($hodDepartmentId);
        
        $data = [
            'title' => 'On-Peak/Off-Peak Request Approvals',
            'page' => 'on-peak-requests-hod',
            'requests' => $requests,
            'department' => $department,
            'message' => $_SESSION['message'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ];
        
        unset($_SESSION['message'], $_SESSION['error']);
        return $this->view('on-peak-requests/hod-approval', $data);
    }
    
    /**
     * HOD approve/reject request
     */
    public function hodApprove() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Check if user is HOD
        if (!$this->isHOD()) {
            $_SESSION['error'] = 'Access denied. Only Head of Department can approve requests.';
            $this->redirect('dashboard');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Invalid request method.';
            $this->redirect('on-peak-requests/hod-approval');
            return;
        }
        
        $requestId = (int)$this->post('request_id', 0);
        $approved = $this->post('action', '') === 'approve';
        $comments = trim($this->post('comments', ''));
        
        if (empty($requestId)) {
            $_SESSION['error'] = 'Request ID is required.';
            $this->redirect('on-peak-requests/hod-approval');
            return;
        }
        
        $requestModel = $this->model('OnPeakRequestModel');
        
        // Verify request belongs to HOD's department
        $requestModel->addRequiredColumnsIfNotExists();
        $request = $requestModel->getRequestWithDetails($requestId);
        if (!$request) {
            $_SESSION['error'] = 'Request not found.';
            $this->redirect('on-peak-requests/hod-approval');
            return;
        }
        
        $hodDepartmentId = $this->getHODDepartment();
        if ($request['department_id'] !== $hodDepartmentId) {
            $_SESSION['error'] = 'Access denied. You can only approve requests from your department.';
            $this->redirect('on-peak-requests/hod-approval');
            return;
        }
        
        // Get old values before update
        $oldValues = [
            'onpeak_request_status' => $request['onpeak_request_status'],
            'hod_approver_id' => $request['hod_approver_id'],
            'hod_approval_date' => $request['hod_approval_date'],
            'hod_comments' => $request['hod_comments']
        ];
        
        // Update approval
        $result = $requestModel->updateHODApproval($requestId, $_SESSION['user_id'], $approved, $comments);
        
        if ($result) {
            // Get updated request to log new values
            $updatedRequest = $requestModel->getRequestWithDetails($requestId);
            
            // Log activity
            $action = $approved ? 'APPROVE' : 'REJECT';
            $this->logActivity(
                $action,
                'on_peak_request',
                (string)$requestId,
                "HOD {$action}D temporary exit request #{$requestId} for student {$request['student_id']}",
                $oldValues,
                [
                    'onpeak_request_status' => $updatedRequest['onpeak_request_status'],
                    'hod_approver_id' => $updatedRequest['hod_approver_id'],
                    'hod_approval_date' => $updatedRequest['hod_approval_date'],
                    'hod_comments' => $updatedRequest['hod_comments']
                ]
            );
            
            $action = $approved ? 'approved' : 'rejected';
            $isHostel = isset($request['is_hostel_student']) && $request['is_hostel_student'] == 1;
            if ($approved && !$isHostel) {
                $_SESSION['message'] = "Request approved successfully. Non-hostel student can now temporarily exit SLGTI.";
            } else {
                $_SESSION['message'] = "Request {$action} successfully.";
            }
        } else {
            $_SESSION['error'] = 'Failed to update request. Please try again.';
        }
        
        $this->redirect('on-peak-requests/hod-approval');
    }
    
    /**
     * Final approval view (DIR, DPA, DPI, REG, ADM, HOD)
     */
    public function finalApproval() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        
        // Check if user is authorized for second approval (DIR, DPA, DPI, REG, ADM, HOD, or WAR)
        $canApprove = in_array($userRole, ['DIR', 'DPA', 'DPI', 'REG', 'ADM', 'HOD', 'WAR']) || $userModel->isAdmin($_SESSION['user_id']);
        
        if (!$canApprove) {
            $_SESSION['error'] = 'Access denied. Only authorized roles (DIR, DPA, DPI, REG, ADM, HOD, WAR) can approve hostel student requests.';
            $this->redirect('dashboard');
            return;
        }
        
        $requestModel = $this->model('OnPeakRequestModel');
        
        // Add required columns if they don't exist
        $requestModel->addRequiredColumnsIfNotExists();
        
        // Get user gender if WAR
        $userGender = null;
        if ($userRole === 'WAR') {
            $userGender = $userModel->getUserGender($_SESSION['user_id']);
        }
        
        // Get pending requests for second approval
        $requests = $requestModel->getPendingFinalRequests($userRole, $_SESSION['user_id'], $userGender);
        
        $data = [
            'title' => 'On-Peak/Off-Peak Final Approvals',
            'page' => 'on-peak-requests-final',
            'requests' => $requests,
            'userRole' => $userRole,
            'message' => $_SESSION['message'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ];
        
        unset($_SESSION['message'], $_SESSION['error']);
        return $this->view('on-peak-requests/final-approval', $data);
    }
    
    /**
     * Final approve/reject request
     */
    public function finalApprove() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Invalid request method.';
            $this->redirect('on-peak-requests/final-approval');
            return;
        }
        
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        
        // Check if user is authorized for second approval (DIR, DPA, DPI, REG, ADM, HOD, or WAR)
        $canApprove = in_array($userRole, ['DIR', 'DPA', 'DPI', 'REG', 'ADM', 'HOD', 'WAR']) || $userModel->isAdmin($_SESSION['user_id']);
        
        if (!$canApprove) {
            $_SESSION['error'] = 'Access denied. Only authorized roles (DIR, DPA, DPI, REG, ADM, HOD, WAR) can approve hostel student requests.';
            $this->redirect('dashboard');
            return;
        }
        
        $requestId = (int)$this->post('request_id', 0);
        $approved = $this->post('action', '') === 'approve';
        $comments = trim($this->post('comments', ''));
        
        if (empty($requestId)) {
            $_SESSION['error'] = 'Request ID is required.';
            $this->redirect('on-peak-requests/final-approval');
            return;
        }
        
        $requestModel = $this->model('OnPeakRequestModel');
        
        // Add required columns if they don't exist
        $requestModel->addRequiredColumnsIfNotExists();
        
        // Verify request is in correct status
        $request = $requestModel->getRequestWithDetails($requestId);
        if (!$request || ($request['onpeak_request_status'] !== 'hod_approved' && $request['onpeak_request_status'] !== 'HOD Approved')) {
            $_SESSION['error'] = 'Request not found or not ready for second approval.';
            $this->redirect('on-peak-requests/final-approval');
            return;
        }
        
        // Verify it's a hostel student request
        if (!isset($request['is_hostel_student']) || $request['is_hostel_student'] != 1) {
            $_SESSION['error'] = 'This request is for a non-hostel student and only requires HOD approval.';
            $this->redirect('on-peak-requests/final-approval');
            return;
        }
        
        // Get old values before update
        $oldValues = [
            'onpeak_request_status' => $request['onpeak_request_status'],
            'second_approver_id' => $request['second_approver_id'],
            'second_approver_role' => $request['second_approver_role'],
            'second_approval_date' => $request['second_approval_date'],
            'second_comments' => $request['second_comments']
        ];
        
        // Update second approval (Director/Warden)
        $result = $requestModel->updateSecondApproval($requestId, $_SESSION['user_id'], $userRole, $approved, $comments);
        
        if ($result) {
            // Get updated request to log new values
            $updatedRequest = $requestModel->getRequestWithDetails($requestId);
            
            // Log activity
            $action = $approved ? 'APPROVE' : 'REJECT';
            $this->logActivity(
                $action,
                'on_peak_request',
                (string)$requestId,
                "{$userRole} {$action}D temporary exit request #{$requestId} for student {$request['student_id']}",
                $oldValues,
                [
                    'onpeak_request_status' => $updatedRequest['onpeak_request_status'],
                    'second_approver_id' => $updatedRequest['second_approver_id'],
                    'second_approver_role' => $updatedRequest['second_approver_role'],
                    'second_approval_date' => $updatedRequest['second_approval_date'],
                    'second_comments' => $updatedRequest['second_comments']
                ]
            );
            
            $action = $approved ? 'approved' : 'rejected';
            $_SESSION['message'] = "Request {$action} successfully.";
        } else {
            $_SESSION['error'] = 'Failed to update request. Please try again.';
        }
        
        $this->redirect('on-peak-requests/final-approval');
    }
    
    /**
     * Student cancel request
     */
    public function cancel() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Check if user is a student
        if (!isset($_SESSION['user_table']) || $_SESSION['user_table'] !== 'student') {
            $_SESSION['error'] = 'Access denied.';
            $this->redirect('dashboard');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Invalid request method.';
            $this->redirect('on-peak-requests');
            return;
        }
        
        $requestId = (int)$this->post('request_id', 0);
        $studentId = $_SESSION['user_name'];
        
        if (empty($requestId)) {
            $_SESSION['error'] = 'Request ID is required.';
            $this->redirect('on-peak-requests');
            return;
        }
        
        $requestModel = $this->model('OnPeakRequestModel');
        $requestModel->addRequiredColumnsIfNotExists();
        
        // Get request details before cancellation
        $request = $requestModel->getRequestWithDetails($requestId);
        $oldValues = $request ? [
            'onpeak_request_status' => $request['onpeak_request_status'],
            'status' => $request['onpeak_request_status']
        ] : null;
        
        $result = $requestModel->cancelRequest($requestId, $studentId);
        
        if ($result) {
            // Log activity
            $this->logActivity(
                'DELETE',
                'on_peak_request',
                (string)$requestId,
                "Student {$studentId} cancelled temporary exit request #{$requestId}",
                $oldValues,
                ['onpeak_request_status' => 'Cancelled']
            );
            
            $_SESSION['message'] = 'Request cancelled successfully.';
        } else {
            $_SESSION['error'] = 'Failed to cancel request. The request may have already been processed.';
        }
        
        $this->redirect('on-peak-requests');
    }
    
    /**
     * View request details
     */
    public function show() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        $requestId = (int)$this->get('id', 0);
        if (empty($requestId)) {
            $_SESSION['error'] = 'Request ID is required.';
            $this->redirect('dashboard');
            return;
        }
        
        $requestModel = $this->model('OnPeakRequestModel');
        $requestModel->addRequiredColumnsIfNotExists();
        $request = $requestModel->getRequestWithDetails($requestId);
        
        if (!$request) {
            $_SESSION['error'] = 'Request not found.';
            $this->redirect('dashboard');
            return;
        }
        
        // Check access permissions
        $isStudent = isset($_SESSION['user_table']) && $_SESSION['user_table'] === 'student';
        $isHOD = $this->isHOD();
        
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        $canSecondApprove = in_array($userRole, ['DIR', 'DPA', 'DPI', 'REG', 'ADM', 'HOD', 'WAR']) || $userModel->isAdmin($_SESSION['user_id']);
        
        // Verify access - Students can only view their own requests
        if ($isStudent && $request['student_id'] !== $_SESSION['user_name']) {
            $_SESSION['error'] = 'Access denied. You can only view your own requests.';
            $this->redirect('on-peak-requests');
            return;
        }
        
        // HOD can only view requests from their department
        if ($isHOD) {
            $hodDepartmentId = $this->getHODDepartment();
            if ($request['department_id'] !== $hodDepartmentId) {
                $_SESSION['error'] = 'Access denied. You can only view requests from your department.';
                $this->redirect('on-peak-requests/hod-approval');
                return;
            }
        }
        
        // Second approvers (DIR, DPA, DPI, REG, ADM, HOD, WAR) can only view requests pending for their approval
        if ($canSecondApprove && !$isHOD && !$isStudent) {
            // Check if request is pending second approval or already approved/rejected by this user
            $status = $request['onpeak_request_status'] ?? 'pending';
            $isPendingSecondApproval = ($status === 'hod_approved' || $status === 'HOD Approved');
            $isAlreadyApprovedByUser = ($request['second_approver_id'] == $_SESSION['user_id']);
            
            // For WAR, also check gender match
            if ($userRole === 'WAR') {
                $userGender = $userModel->getUserGender($_SESSION['user_id']);
                $studentGender = $request['student_gender'] ?? '';
                $genderMatch = (strcasecmp($userGender, $studentGender) === 0);
                
                if ($isPendingSecondApproval && !$genderMatch) {
                    $_SESSION['error'] = 'Access denied. You can only approve requests from students of the same gender.';
                    $this->redirect('on-peak-requests/final-approval');
                    return;
                }
            }
            
            // Allow access if: request is pending second approval, or already approved/rejected by this user
            if (!$isPendingSecondApproval && !$isAlreadyApprovedByUser && $status !== 'Approved' && $status !== 'Rejected') {
                $_SESSION['error'] = 'Access denied. This request is not pending for your approval.';
                $this->redirect('on-peak-requests/final-approval');
                return;
            }
        }
        
        $data = [
            'title' => 'Request Details',
            'page' => 'on-peak-requests',
            'request' => $request,
            'isStudent' => $isStudent,
            'isHOD' => $isHOD,
            'canSecondApprove' => $canSecondApprove,
            'userRole' => $userRole
        ];
        
        return $this->view('on-peak-requests/view', $data);
    }
}

