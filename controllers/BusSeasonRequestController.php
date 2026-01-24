<?php
/**
 * Bus Season Request Controller
 */

class BusSeasonRequestController extends Controller {
    
    /**
     * Student view - List and submit bus season requests
     */
    public function index() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        $requestModel = $this->model('BusSeasonRequestModel');
        $studentModel = $this->model('StudentModel');
        $requestModel->addRequiredColumnsIfNotExists();

        // Check if user is a student
        if (isset($_SESSION['user_table']) && $_SESSION['user_table'] === 'student') {
            $studentId = $_SESSION['user_name'];
            
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
            $seasonYear = $currentEnrollment['academic_year'] ?? date('Y');
            
            // Get department ID
            $departmentId = null;
            if ($currentEnrollment && isset($currentEnrollment['course_id'])) {
                $courseModel = $this->model('CourseModel');
                $course = $courseModel->find($currentEnrollment['course_id']);
                if ($course && isset($course['department_id'])) {
                    $departmentId = $course['department_id'];
                }
            }
            
            // Get academic years
            $academicYears = $studentModel->getAcademicYears();
            
            // Get requests for student (with payment info)
            $requests = $requestModel->getByStudentId($studentId);
            
            // Add payment collection info to each request
            foreach ($requests as &$request) {
                $payment = $requestModel->getPaymentCollectionByRequestId($request['id']);
                if ($payment) {
                    $request['payment'] = $payment;
                }
            }
            unset($request);
            
            // Check if student already has request for current season year
            $hasExistingRequest = $requestModel->hasExistingRequest($studentId, $seasonYear);
            
            $data = [
                'title' => 'Bus Season Request',
                'page' => 'bus-season-requests',
                'student' => $student,
                'currentEnrollment' => $currentEnrollment,
                'seasonYear' => $seasonYear,
                'departmentId' => $departmentId,
                'requests' => $requests,
                'hasExistingRequest' => $hasExistingRequest,
                'academicYears' => $academicYears,
                'message' => $_SESSION['message'] ?? null,
                'error' => $_SESSION['error'] ?? null
            ];
            
            unset($_SESSION['message'], $_SESSION['error']);
            return $this->view('bus-season-requests/index', $data);
        } else {
            // Staff/Admin view - Show all requests
            $requests = $requestModel->getRequestsForSAO();
            
            $data = [
                'title' => 'All Bus Season Requests',
                'page' => 'bus-season-requests-all',
                'requests' => $requests,
                'message' => $_SESSION['message'] ?? null,
                'error' => $_SESSION['error'] ?? null
            ];
            
            unset($_SESSION['message'], $_SESSION['error']);
            return $this->view('bus-season-requests/index', $data);
        }
    }
    
    /**
     * Student submit bus season request
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
            $this->redirect('bus-season-requests');
            return;
        }
        
        $studentId = $_SESSION['user_name'];
        $studentModel = $this->model('StudentModel');
        $requestModel = $this->model('BusSeasonRequestModel');
        
        // Ensure columns exist
        $requestModel->addRequiredColumnsIfNotExists();
        
        // Get current enrollment
        $enrollmentModel = $this->model('StudentEnrollmentModel');
        $currentEnrollment = $enrollmentModel->getCurrentEnrollment($studentId);
        $seasonYear = $currentEnrollment['academic_year'] ?? date('Y');
        
        // Check if student already has request for this season year
        if ($requestModel->hasExistingRequest($studentId, $seasonYear)) {
            $_SESSION['error'] = 'You already have a bus season request for this season year. Only one request per year is allowed.';
            $this->redirect('bus-season-requests');
            return;
        }
        
        // Get department ID
        $departmentId = null;
        if ($currentEnrollment && isset($currentEnrollment['course_id'])) {
            $courseModel = $this->model('CourseModel');
            $course = $courseModel->find($currentEnrollment['course_id']);
            if ($course && isset($course['department_id'])) {
                $departmentId = $course['department_id'];
            }
        }
        
        // Get form data (only route information from students)
        $routeFrom = trim($this->post('route_from', ''));
        $routeTo = trim($this->post('route_to', ''));
        $changePoint = trim($this->post('change_point', ''));
        $distanceKm = floatval($this->post('distance_km', 0));
        
        // Validate required fields
        if (empty($routeFrom) || empty($routeTo) || $distanceKm <= 0) {
            $_SESSION['error'] = 'Please fill in all required fields: Route From, Route To, and Distance (KM).';
            $this->redirect('bus-season-requests');
            return;
        }
        
        // Prepare data (approval only, no payment details)
        $data = [
            'student_id' => $studentId,
            'department_id' => $departmentId,
            'season_year' => $seasonYear,
            'season_name' => '', // Empty for students
            'depot_name' => '', // Empty for students
            'route_from' => $routeFrom,
            'route_to' => $routeTo,
            'change_point' => $changePoint,
            'distance_km' => $distanceKm,
            'notes' => '' // Empty for students
        ];
        
        // Create request
        $newRequestId = $requestModel->createRequest($data);
        
        if ($newRequestId) {
            // Log activity
            $activityModel = $this->model('ActivityLogModel');
            $activityModel->logActivity([
                'activity_type' => 'CREATE',
                'module' => 'bus_season_request',
                'record_id' => $newRequestId,
                'description' => "Student {$studentId} created bus season request for season year {$seasonYear}",
                'new_values' => $data
            ]);
            
            $_SESSION['message'] = 'Bus season request submitted successfully. Waiting for HOD approval.';
        } else {
            $_SESSION['error'] = 'Failed to submit request. Please try again.';
        }
        
        $this->redirect('bus-season-requests');
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
        
        $requestModel = $this->model('BusSeasonRequestModel');
        $requestModel->addRequiredColumnsIfNotExists();
        
        // Get HOD's department
        $hodDepartmentId = $this->getHODDepartment();
        if (!$hodDepartmentId) {
            $_SESSION['error'] = 'Department not found for your HOD account.';
            $this->redirect('dashboard');
            return;
        }
        
        // Get pending requests
        $requests = $requestModel->getPendingHODRequests($hodDepartmentId);
        
        $data = [
            'title' => 'Bus Season Requests - HOD Approval',
            'page' => 'bus-season-requests-hod',
            'requests' => $requests,
            'message' => $_SESSION['message'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ];
        
        unset($_SESSION['message'], $_SESSION['error']);
        return $this->view('bus-season-requests/hod-approval', $data);
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
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Invalid request method.';
            $this->redirect('bus-season-requests/hod-approval');
            return;
        }
        
        // Check if user is HOD
        if (!$this->isHOD()) {
            $_SESSION['error'] = 'Access denied. Only Head of Department can approve requests.';
            $this->redirect('dashboard');
            return;
        }
        
        $requestId = (int)$this->post('request_id', 0);
        $approved = $this->post('action') === 'approve';
        $comments = trim($this->post('comments', ''));
        
        if (empty($requestId)) {
            $_SESSION['error'] = 'Request ID is required.';
            $this->redirect('bus-season-requests/hod-approval');
            return;
        }
        
        $requestModel = $this->model('BusSeasonRequestModel');
        $requestModel->addRequiredColumnsIfNotExists();
        
        // Verify request belongs to HOD's department
        $request = $requestModel->getRequestWithDetails($requestId);
        if (!$request) {
            $_SESSION['error'] = 'Request not found.';
            $this->redirect('bus-season-requests/hod-approval');
            return;
        }
        
        $hodDepartmentId = $this->getHODDepartment();
        if ($request['department_id'] !== $hodDepartmentId) {
            $_SESSION['error'] = 'Access denied. You can only approve requests from your department.';
            $this->redirect('bus-season-requests/hod-approval');
            return;
        }
        
        // Update approval
        $result = $requestModel->updateHODApproval($requestId, $_SESSION['user_id'], $approved, $comments);
        
        if ($result) {
            $action = $approved ? 'approved' : 'rejected';
            $_SESSION['message'] = "Request {$action} successfully.";
        } else {
            $_SESSION['error'] = 'Failed to update request. Please try again.';
        }
        
        $this->redirect('bus-season-requests/hod-approval');
    }
    
    /**
     * Second approval view (DIR, DPA, DPI, REG)
     */
    public function secondApproval() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        
        // Check if user is authorized for second approval (DIR, DPA, DPI, REG)
        $allowedRoles = ['DIR', 'DPA', 'DPI', 'REG'];
        $canApprove = in_array($userRole, $allowedRoles) || $userModel->isAdmin($_SESSION['user_id']);
        
        if (!$canApprove) {
            $_SESSION['error'] = 'Access denied. Only Director, DPA, DPI, or Registrar can approve requests.';
            $this->redirect('dashboard');
            return;
        }
        
        $requestModel = $this->model('BusSeasonRequestModel');
        $requestModel->addRequiredColumnsIfNotExists();
        
        // Get pending requests for second approval
        $requests = $requestModel->getPendingSecondRequests($userRole);
        
        $data = [
            'title' => 'Bus Season Requests - Second Approval',
            'page' => 'bus-season-requests-second',
            'requests' => $requests,
            'userRole' => $userRole,
            'message' => $_SESSION['message'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ];
        
        unset($_SESSION['message'], $_SESSION['error']);
        return $this->view('bus-season-requests/second-approval', $data);
    }
    
    /**
     * Second approve/reject request
     */
    public function secondApprove() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Invalid request method.';
            $this->redirect('bus-season-requests/second-approval');
            return;
        }
        
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        
        // Check if user is authorized for second approval (DIR, DPA, DPI, REG)
        $allowedRoles = ['DIR', 'DPA', 'DPI', 'REG'];
        $canApprove = in_array($userRole, $allowedRoles) || $userModel->isAdmin($_SESSION['user_id']);
        
        if (!$canApprove) {
            $_SESSION['error'] = 'Access denied. Only Director, DPA, DPI, or Registrar can approve requests.';
            $this->redirect('dashboard');
            return;
        }
        
        $requestId = (int)$this->post('request_id', 0);
        $approved = $this->post('action') === 'approve';
        $comments = trim($this->post('comments', ''));
        
        if (empty($requestId)) {
            $_SESSION['error'] = 'Request ID is required.';
            $this->redirect('bus-season-requests/second-approval');
            return;
        }
        
        $requestModel = $this->model('BusSeasonRequestModel');
        $requestModel->addRequiredColumnsIfNotExists();
        
        // Get request details before update
        $request = $requestModel->getRequestWithDetails($requestId);
        if (!$request) {
            $_SESSION['error'] = 'Request not found.';
            $this->redirect('bus-season-requests/second-approval');
            return;
        }
        
        // Get old values before update
        $oldValues = [
            'status' => $request['status'],
            'second_approver_id' => $request['second_approver_id'],
            'second_approver_role' => $request['second_approver_role'],
            'second_approval_date' => $request['second_approval_date'],
            'second_comments' => $request['second_comments']
        ];
        
        // Update second approval
        $result = $requestModel->updateSecondApproval($requestId, $_SESSION['user_id'], $userRole, $approved, $comments);
        
        if ($result) {
            // Get updated request to log new values
            $updatedRequest = $requestModel->getRequestWithDetails($requestId);
            
            // Log activity
            $activityModel = $this->model('ActivityLogModel');
            $action = $approved ? 'APPROVE' : 'REJECT';
            $activityModel->logActivity([
                'activity_type' => $action,
                'module' => 'bus_season_request',
                'record_id' => (string)$requestId,
                'description' => "{$userRole} {$action}D bus season request #{$requestId} for student {$request['student_id']}",
                'old_values' => $oldValues,
                'new_values' => [
                    'status' => $updatedRequest['status'],
                    'second_approver_id' => $updatedRequest['second_approver_id'],
                    'second_approver_role' => $updatedRequest['second_approver_role'],
                    'second_approval_date' => $updatedRequest['second_approval_date'],
                    'second_comments' => $updatedRequest['second_comments']
                ]
            ]);
            
            $actionText = $approved ? 'approved' : 'rejected';
            $_SESSION['message'] = "Request {$actionText} successfully.";
        } else {
            $_SESSION['error'] = 'Failed to update request. Please try again.';
        }
        
        $this->redirect('bus-season-requests/second-approval');
    }
    
    /**
     * SAO process requests - view all requests (HOD approved or not, need second approval or not)
     */
    public function saoProcess() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $isSAO = $userModel->isSAO($_SESSION['user_id']);
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        $isADM = ($userRole === 'ADM');
        $isAdmin = $userModel->isAdmin($_SESSION['user_id']);
        
        // Only SAO and ADM can access
        if (!$isSAO && !$isADM && !$isAdmin) {
            $_SESSION['error'] = 'Access denied. Only Student Affairs Office (SAO) and Administrators (ADM) can collect payments.';
            $this->redirect('dashboard');
            return;
        }
        
        $requestModel = $this->model('BusSeasonRequestModel');
        $requestModel->addRequiredColumnsIfNotExists();
        
        // Get all requests (HOD approved or not, need second approval or not)
        $requests = $requestModel->getRequestsForSAO();
        
        // Get academic years for filter
        $studentModel = $this->model('StudentModel');
        $academicYears = $studentModel->getAcademicYears();
        
        $data = [
            'title' => 'Bus Season Requests - Payment Collection',
            'page' => 'bus-season-requests-sao',
            'requests' => $requests,
            'academicYears' => $academicYears,
            'message' => $_SESSION['message'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ];
        
        unset($_SESSION['message'], $_SESSION['error']);
        return $this->view('bus-season-requests/sao-process', $data);
    }
    
    /**
     * SAO save payment collection
     */
    public function saoProcessSave() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Invalid request method.';
            $this->redirect('bus-season-requests/sao-process');
            return;
        }
        
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $isSAO = $userModel->isSAO($_SESSION['user_id']);
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        $isADM = ($userRole === 'ADM');
        $isAdmin = $userModel->isAdmin($_SESSION['user_id']);
        
        // Only SAO and ADM can access
        if (!$isSAO && !$isADM && !$isAdmin) {
            $_SESSION['error'] = 'Access denied. Only Student Affairs Office (SAO) and Administrators (ADM) can collect payments.';
            $this->redirect('dashboard');
            return;
        }
        
        $requestId = (int)$this->post('request_id', 0);
        $studentPayment = floatval($this->post('student_payment_amount', 0));
        $paymentReference = trim($this->post('payment_reference', ''));
        $notes = trim($this->post('notes', ''));
        $paymentMethod = trim($this->post('payment_method', 'cash'));
        
        if (empty($requestId)) {
            $_SESSION['error'] = 'Request ID is required.';
            $this->redirect('bus-season-requests/sao-process');
            return;
        }
        
        if ($studentPayment <= 0) {
            $_SESSION['error'] = 'Please enter a valid student payment amount.';
            $this->redirect('bus-season-requests/sao-process');
            return;
        }
        
        $requestModel = $this->model('BusSeasonRequestModel');
        $requestModel->addRequiredColumnsIfNotExists();
        
        // Check if request exists
        $request = $requestModel->getRequestWithDetails($requestId);
        if (!$request) {
            $_SESSION['error'] = 'Request not found.';
            $this->redirect('bus-season-requests/sao-process');
            return;
        }
        
        // Check if payment already collected
        if ($requestModel->hasPaymentCollection($requestId)) {
            $_SESSION['error'] = 'Payment has already been collected for this request.';
            $this->redirect('bus-season-requests/sao-process');
            return;
        }
        
        // Create initial payment collection record (status: paid)
        $paymentId = $requestModel->createPaymentCollection(
            $requestId,
            $request['student_id'],
            $studentPayment,
            0, // season_rate (filled later)
            $_SESSION['user_id'],
            $paymentMethod,
            $paymentReference,
            $notes
        );
        
        if ($paymentId) {
            // Update request status to 'paid'
            $requestModel->updateStatus($requestId, 'paid');

            // Log activity
            $activityModel = $this->model('ActivityLogModel');
            $activityModel->logActivity([
                'activity_type' => 'CREATE',
                'module' => 'bus_season_payment',
                'record_id' => $paymentId,
                'description' => "SAO/ADM collected initial payment for bus season request #{$requestId} - Student: Rs. {$studentPayment}. Status: paid",
                'new_values' => [
                    'request_id' => $requestId,
                    'student_id' => $request['student_id'],
                    'student_paid' => $studentPayment,
                    'payment_method' => $paymentMethod,
                    'payment_reference' => $paymentReference,
                    'status' => 'paid'
                ]
            ]);
            
            $_SESSION['message'] = 'Payment recorded successfully. Status set to paid.';
        } else {
            $_SESSION['error'] = 'Failed to record payment collection. Please try again.';
        }
        
        $this->redirect('bus-season-requests/sao-process');
    }

    /**
     * Update payment status (paid -> processing -> issued)
     */
    public function updatePaymentStatus() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->json(['success' => false, 'message' => 'Unauthorized'], 401);
            return;
        }
        
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $isSAO = $userModel->isSAO($_SESSION['user_id']);
        $isAdmin = $userModel->isAdmin($_SESSION['user_id']);
        
        if (!$isSAO && !$isAdmin) {
            $this->json(['success' => false, 'message' => 'Access denied.'], 403);
            return;
        }
        
        $paymentId = (int)$this->post('payment_id', 0);
        $newStatus = trim($this->post('status', ''));
        $actualPrice = floatval($this->post('actual_price', 0));
        $studentPortion = floatval($this->post('student_portion', 0));
        $paymentReference = trim($this->post('payment_reference', ''));
        
        if (empty($paymentId) || empty($newStatus)) {
            $this->json(['success' => false, 'message' => 'Payment ID and status are required.'], 400);
            return;
        }
        
        $requestModel = $this->model('BusSeasonRequestModel');
        $payment = $requestModel->getPaymentCollectionById($paymentId);
        
        if (!$payment) {
            $this->json(['success' => false, 'message' => 'Payment record not found.'], 404);
            return;
        }
        
        $updateData = [];
        if ($newStatus === 'issued') {
            // Use provided values or calculate defaults
            $totalAmount = $actualPrice > 0 ? $actualPrice : ($studentPortion > 0 ? $studentPortion / 0.30 : $requestModel->calculateSeasonTotal($payment['paid_amount']));
            
            // Student portion should be what was confirmed in the modal
            $finalStudentPaid = $studentPortion > 0 ? $studentPortion : $totalAmount * 0.30;
            
            // SLGTI pays 35%, CTB pays 35%
            $slgtiPaid = $totalAmount * 0.35;
            $ctbPaid = $totalAmount * 0.35;
            
            // Calculate balance (Difference between what they should pay now and what they initially paid)
            $remainingBalance = $finalStudentPaid - $payment['paid_amount'];
            
            $updateData = [
                'total_amount' => $totalAmount,
                'student_paid' => $finalStudentPaid,
                'slgti_paid' => $slgtiPaid,
                'ctb_paid' => $ctbPaid,
                'season_rate' => $totalAmount,
                'remaining_balance' => $remainingBalance,
                'payment_reference' => $paymentReference ?: $payment['payment_reference']
            ];
        }
        
        if ($requestModel->updatePaymentStatus($paymentId, $newStatus, $updateData)) {
            // Also update request status
            $requestModel->updateStatus($payment['request_id'], $newStatus);
            
            $this->json(['success' => true, 'message' => 'Status updated successfully.']);
        } else {
            $this->json(['success' => false, 'message' => 'Failed to update status.']);
        }
    }

    /**
     * Export payments to Excel
     */
    public function exportPaymentsExcel() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $isSAO = $userModel->isSAO($_SESSION['user_id']);
        $isAdmin = $userModel->isAdmin($_SESSION['user_id']);
        
        if (!$isSAO && !$isAdmin) {
            $_SESSION['error'] = 'Access denied.';
            $this->redirect('dashboard');
            return;
        }
        
        $requestModel = $this->model('BusSeasonRequestModel');
        $status = $this->get('status', 'paid');
        $filters = [
            'season_year' => $this->get('season_year', ''),
            'student_id' => $this->get('student_id', ''),
            'month' => $this->get('month', ''),
            'status' => $status
        ];
        
        $collections = $requestModel->getAllPaymentCollections($filters);
        
        $filename = 'bus_season_' . $status . '_' . ($filters['month'] ?: date('Y-m')) . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        
        echo "\xEF\xBB\xBF"; // BOM for UTF-8
        $output = fopen('php://output', 'w');
        
        // CSV Headers based on status
        if ($status === 'issued') {
            fputcsv($output, [
                'Name', 'NIC Number', 'Route', 'Total Price', 'Student Portion (30%)', 'SLGTI (35%)', 'CTB (35%)', 'Issued Date', 'Reference'
            ]);
        } else {
            fputcsv($output, [
                'Name', 'NIC Number', 'Route', 'Paid Amount', 'Date', 'Status', 'Reference'
            ]);
        }
        
        foreach ($collections as $c) {
            $route = ($c['route_from'] ?? '') . ' to ' . ($c['route_to'] ?? '');
            
            if ($status === 'issued') {
                fputcsv($output, [
                    $c['student_fullname'] ?? 'N/A',
                    $c['student_nic'] ?? 'N/A',
                    $route,
                    number_format($c['total_amount'] ?? 0, 2, '.', ''),
                    number_format($c['student_paid'] ?? 0, 2, '.', ''),
                    number_format($c['slgti_paid'] ?? 0, 2, '.', ''),
                    number_format($c['ctb_paid'] ?? 0, 2, '.', ''),
                    !empty($c['issued_at']) ? date('Y-m-d', strtotime($c['issued_at'])) : 'N/A',
                    $c['payment_reference'] ?? 'N/A'
                ]);
            } else {
                fputcsv($output, [
                    $c['student_fullname'] ?? 'N/A',
                    $c['student_nic'] ?? 'N/A',
                    $route,
                    number_format($c['paid_amount'] ?? 0, 2, '.', ''),
                    !empty($c['payment_date']) ? date('Y-m-d', strtotime($c['payment_date'])) : 'N/A',
                    ucfirst($c['payment_status'] ?? 'paid'),
                    $c['payment_reference'] ?? 'N/A'
                ]);
            }
        }
        
        fclose($output);
        exit;
    }

    /**
     * Bulk update payment status
     */
    public function bulkUpdateStatus() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->json(['success' => false, 'message' => 'Unauthorized'], 401);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Invalid request method'], 405);
            return;
        }

        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $isSAO = $userModel->isSAO($_SESSION['user_id']);
        $isAdmin = $userModel->isAdmin($_SESSION['user_id']);
        
        if (!$isSAO && !$isAdmin) {
            $this->json(['success' => false, 'message' => 'Access denied'], 403);
            return;
        }

        $paymentIds = $this->post('payment_ids', []);
        $newStatus = $this->post('status', '');

        if (empty($paymentIds) || empty($newStatus)) {
            $this->json(['success' => false, 'message' => 'No records or status provided'], 400);
            return;
        }

        $requestModel = $this->model('BusSeasonRequestModel');
        $successCount = 0;

        foreach ($paymentIds as $id) {
            $payment = $requestModel->getPaymentCollectionById($id);
            if ($payment) {
                if ($requestModel->updatePaymentStatus($id, $newStatus)) {
                    $requestModel->updateStatus($payment['request_id'], $newStatus);
                    $successCount++;
                }
            }
        }

        $this->json([
            'success' => true, 
            'message' => "Successfully updated {$successCount} records to {$newStatus}."
        ]);
    }
    
    /**
     * View payment collections (SAO/ADM)
     */
    public function paymentCollections() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $isSAO = $userModel->isSAO($_SESSION['user_id']);
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        $isADM = ($userRole === 'ADM');
        $isAdmin = $userModel->isAdmin($_SESSION['user_id']);
        
        // Only SAO and ADM can access
        if (!$isSAO && !$isADM && !$isAdmin) {
            $_SESSION['error'] = 'Access denied. Only Student Affairs Office (SAO) and Administrators (ADM) can view payment collections.';
            $this->redirect('dashboard');
            return;
        }
        
        $requestModel = $this->model('BusSeasonRequestModel');
        $requestModel->addRequiredColumnsIfNotExists();
        
        // Get filters
        $filters = [
            'season_year' => $this->get('season_year', ''),
            'student_id' => $this->get('student_id', ''),
            'month' => $this->get('month', '')
        ];
        
        // Get all payment collections
        $collections = $requestModel->getAllPaymentCollections($filters);
        
        // Get academic years for filter
        $studentModel = $this->model('StudentModel');
        $academicYears = $studentModel->getAcademicYears();
        
        $data = [
            'title' => 'Bus Season Payment Collections',
            'page' => 'bus-season-payments',
            'collections' => $collections,
            'academicYears' => $academicYears,
            'filters' => $filters,
            'message' => $_SESSION['message'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ];
        
        unset($_SESSION['message'], $_SESSION['error']);
        return $this->view('bus-season-requests/payment-collections', $data);
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
        
        $requestModel = $this->model('BusSeasonRequestModel');
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
        $isSAO = $userModel->isSAO($_SESSION['user_id']);
        $isADM = ($userRole === 'ADM');
        $allowedSecondRoles = ['DIR', 'DPA', 'DPI', 'REG'];
        $canSecondApprove = in_array($userRole, $allowedSecondRoles) || $userModel->isAdmin($_SESSION['user_id']);
        
        // Verify access
        if ($isStudent && $request['student_id'] !== $_SESSION['user_name']) {
            $_SESSION['error'] = 'Access denied. You can only view your own requests.';
            $this->redirect('bus-season-requests');
            return;
        }
        
        if ($isHOD) {
            $hodDepartmentId = $this->getHODDepartment();
            if ($request['department_id'] !== $hodDepartmentId) {
                $_SESSION['error'] = 'Access denied. You can only view requests from your department.';
                $this->redirect('bus-season-requests/hod-approval');
                return;
            }
        }
        
        $data = [
            'title' => 'Bus Season Request Details',
            'page' => 'bus-season-requests',
            'request' => $request,
            'isStudent' => $isStudent,
            'isHOD' => $isHOD,
            'isSAO' => $isSAO,
            'isADM' => $isADM,
            'canSecondApprove' => $canSecondApprove,
            'userRole' => $userRole
        ];
        
        return $this->view('bus-season-requests/view', $data);
    }
}

