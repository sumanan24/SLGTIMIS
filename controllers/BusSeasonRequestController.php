<?php
/**
 * Bus Season Request Controller
 * MVC Architecture - Handles all bus season request operations
 */

require_once BASE_PATH . '/core/SeasonRequestHelper.php';

class BusSeasonRequestController extends Controller {
    
    /**
     * Index - Display requests based on user role
     */
    public function index() {
        $this->requireAuth();
        
        $requestModel = $this->model('BusSeasonRequestModel');
        $requestModel->ensureTableStructure();
        
        // Student view
        if ($this->isStudent()) {
            return $this->studentIndex($requestModel);
        }
        
        // Staff/Admin view
        return $this->staffIndex($requestModel);
    }
    
    /**
     * Student index view
     */
    private function studentIndex($requestModel) {
        $studentId = $_SESSION['user_name'];
        $studentModel = $this->model('StudentModel');
        
        // Get student data
        $student = $studentModel->find($studentId);
        if (!$student) {
            $this->setError('Student record not found.');
            $this->redirect('student/dashboard');
            return;
        }
        
        // Season year is fixed to 2026
        $seasonYear = '2026';
        $departmentId = SeasonRequestHelper::getStudentDepartmentId($studentId);
        
        // Get requests with payments
        $requests = $requestModel->getByStudentId($studentId);
        foreach ($requests as &$request) {
            $payment = $requestModel->getPaymentCollectionByRequestId($request['id']);
            if ($payment) {
                $request['payment'] = $payment;
            }
        }
        unset($request);
        
        // Check if student already has request for 2026
        $hasExistingRequest = $requestModel->hasExistingRequest($studentId, '2026');
        
        $data = [
            'title' => 'Bus Season Request - 2026',
            'page' => 'bus-season-requests',
            'student' => $student,
            'seasonYear' => $seasonYear,
            'departmentId' => $departmentId,
            'requests' => $requests,
            'hasExistingRequest' => $hasExistingRequest,
            'message' => $this->getFlashMessage(),
            'error' => $this->getFlashError()
        ];
        
        return $this->view('bus-season-requests/index', $data);
    }
    
    /**
     * Staff/Admin index view
     */
    private function staffIndex($requestModel) {
        $requests = $requestModel->getRequestsForSAO();
        
        $data = [
            'title' => 'All Bus Season Requests',
            'page' => 'bus-season-requests-all',
            'requests' => $requests,
            'message' => $this->getFlashMessage(),
            'error' => $this->getFlashError()
        ];
        
        return $this->view('bus-season-requests/index', $data);
    }
    
    /**
     * Create new request (Student submission) - Simplified version
     * Only requires route_from and route_to, season year is 2026
     */
    public function create() {
        $this->requireAuth();
        $this->requireStudent();
        
        $logPrefix = "BusSeasonRequestController::create";
        error_log("{$logPrefix} - Request started. Method: " . $_SERVER['REQUEST_METHOD']);
        
        // Check POST method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->requirePost();
            return;
        }
        
        // Check if POST data is empty
        if (empty($_POST) && empty(file_get_contents('php://input'))) {
            error_log("{$logPrefix} - POST data is empty.");
            $isAjax = SeasonRequestHelper::isAjaxRequest();
            $errorMsg = 'No form data received. Please try again.';
            $this->handleResponse($isAjax, false, $errorMsg);
            return;
        }
        
        $isAjax = SeasonRequestHelper::isAjaxRequest();
        
        // Verify CSRF token
        $csrfToken = $this->post('csrf_token', '');
        if (!SeasonRequestHelper::verifyCSRFToken($csrfToken)) {
            $errorMsg = 'Invalid security token. Please refresh the page and try again.';
            $this->handleResponse($isAjax, false, $errorMsg);
            return;
        }
        
        $studentId = $_SESSION['user_name'] ?? null;
        if (!$studentId) {
            $errorMsg = 'Session error. Please log in again.';
            $this->handleResponse($isAjax, false, $errorMsg);
            return;
        }
        
        // Get form data - only route_from and route_to
        $routeFrom = trim($this->post('route_from', ''));
        $routeTo = trim($this->post('route_to', ''));
        
        // Simple validation - only check if fields are not empty
        if (empty($routeFrom)) {
            $errorMsg = 'Please enter the route from location.';
            $this->handleResponse($isAjax, false, $errorMsg);
            return;
        }
        
        if (empty($routeTo)) {
            $errorMsg = 'Please enter the route to location.';
            $this->handleResponse($isAjax, false, $errorMsg);
            return;
        }
        
        try {
            $requestModel = $this->model('BusSeasonRequestModel');
            $requestModel->ensureTableStructure();
            
            // Check if student already has request for 2026
            if ($requestModel->hasExistingRequest($studentId, '2026')) {
                $errorMsg = 'You already have a bus season request for 2026. Only one request per year is allowed.';
                $this->handleResponse($isAjax, false, $errorMsg);
                return;
            }
            
            // Get department ID (optional)
            $departmentId = null;
            if (isset($_SESSION['user_id'])) {
                $enrollmentModel = $this->model('StudentEnrollmentModel');
                $currentEnrollment = $enrollmentModel->getCurrentEnrollment($studentId);
                if ($currentEnrollment && isset($currentEnrollment['course_id'])) {
                    $courseModel = $this->model('CourseModel');
                    $course = $courseModel->find($currentEnrollment['course_id']);
                    if ($course && isset($course['department_id'])) {
                        $departmentId = $course['department_id'];
                    }
                }
            }
            
            // Prepare request data - season year is 2026
            $requestData = [
                'student_id' => $studentId,
                'department_id' => $departmentId,
                'season_year' => '2026',
                'season_name' => '',
                'route_from' => $routeFrom,
                'route_to' => $routeTo,
                'change_point' => '',
                'distance_km' => 0,
                'notes' => ''
            ];
            
            error_log("{$logPrefix} - Request data: " . json_encode($requestData));
            
            // Check database connection
            $db = Database::getInstance();
            $conn = $db->getConnection();
            if (!$conn || $conn->connect_error) {
                $errorMsg = 'Database connection error. Please contact the administrator.';
                $this->handleResponse($isAjax, false, $errorMsg);
                return;
            }
            
            // Create request
            $newRequestId = $requestModel->create($requestData);
            
            if ($newRequestId) {
                error_log("{$logPrefix} - Request created successfully. ID: {$newRequestId}");
                
                // Log activity
                try {
                    SeasonRequestHelper::logActivity(
                        'CREATE',
                        $newRequestId,
                        "Student {$studentId} created bus season request for season year 2026",
                        $requestData
                    );
                } catch (Exception $e) {
                    error_log("{$logPrefix} - Activity log error: " . $e->getMessage());
                }
                
                // Regenerate CSRF token
                SeasonRequestHelper::generateCSRFToken();
                
                $successMsg = 'Bus season request submitted successfully. Waiting for HOD approval.';
                $this->handleResponse($isAjax, true, $successMsg);
            } else {
                error_log("{$logPrefix} - Request creation failed.");
                $errorMsg = 'Failed to submit request. Please try again.';
                $this->handleResponse($isAjax, false, $errorMsg);
            }
        } catch (Exception $e) {
            error_log("{$logPrefix} - Exception: " . $e->getMessage());
            $errorMsg = 'An error occurred. Please try again.';
            $this->handleResponse($isAjax, false, $errorMsg);
        } catch (Error $e) {
            error_log("{$logPrefix} - Fatal Error: " . $e->getMessage());
            $errorMsg = 'A system error occurred. Please contact the administrator.';
            $this->handleResponse($isAjax, false, $errorMsg);
        }
    }
    
    /**
     * HOD Approval View
     */
    public function hodApproval() {
        $this->requireAuth();
        $this->requireHOD();
        
        $requestModel = $this->model('BusSeasonRequestModel');
        $requestModel->ensureTableStructure();
        
        $hodDepartmentId = $this->getHODDepartment();
        if (!$hodDepartmentId) {
            $this->setError('Department not found for your HOD account.');
            $this->redirect('dashboard');
            return;
        }
        
        $requests = $requestModel->getPendingHODRequests($hodDepartmentId);
        
        $data = [
            'title' => 'Bus Season Requests - HOD Approval',
            'page' => 'bus-season-requests-hod',
            'requests' => $requests,
            'message' => $this->getFlashMessage(),
            'error' => $this->getFlashError()
        ];
        
        return $this->view('bus-season-requests/hod-approval', $data);
    }
    
    /**
     * HOD Approve/Reject Request
     */
    public function hodApprove() {
        $this->requireAuth();
        $this->requireHOD();
        $this->requirePost();
        
        $requestId = (int)$this->post('request_id', 0);
        $approved = $this->post('action') === 'approve';
        $comments = trim($this->post('comments', ''));
        
        if (empty($requestId)) {
            $this->setError('Request ID is required.');
            $this->redirect('bus-season-requests/hod-approval');
            return;
        }
        
        $requestModel = $this->model('BusSeasonRequestModel');
        $requestModel->ensureTableStructure();
        
        // Verify request belongs to HOD's department
        $request = $requestModel->findWithDetails($requestId);
        if (!$request) {
            $this->setError('Request not found.');
            $this->redirect('bus-season-requests/hod-approval');
            return;
        }
        
        $hodDepartmentId = $this->getHODDepartment();
        if ($request['department_id'] !== $hodDepartmentId) {
            $this->setError('Access denied. You can only approve requests from your department.');
            $this->redirect('bus-season-requests/hod-approval');
            return;
        }
        
        // Update approval
        $result = $requestModel->updateHODApproval($requestId, $_SESSION['user_id'], $approved, $comments);
        
        if ($result) {
            $action = $approved ? 'approved' : 'rejected';
            $this->setMessage("Request {$action} successfully.");
        } else {
            $this->setError('Failed to update request. Please try again.');
        }
        
        $this->redirect('bus-season-requests/hod-approval');
    }
    
    /**
     * Second Approval View (DIR, DPA, DPI, REG)
     */
    public function secondApproval() {
        $this->requireAuth();
        $this->requireSecondApprover();
        
        $requestModel = $this->model('BusSeasonRequestModel');
        $requestModel->ensureTableStructure();
        
        $userRole = $this->getUserRole();
        $requests = $requestModel->getPendingSecondRequests($userRole);
        
        $data = [
            'title' => 'Bus Season Requests - Second Approval',
            'page' => 'bus-season-requests-second',
            'requests' => $requests,
            'userRole' => $userRole,
            'message' => $this->getFlashMessage(),
            'error' => $this->getFlashError()
        ];
        
        return $this->view('bus-season-requests/second-approval', $data);
    }
    
    /**
     * Second Approve/Reject Request
     */
    public function secondApprove() {
        $this->requireAuth();
        $this->requireSecondApprover();
        $this->requirePost();
        
        $requestId = (int)$this->post('request_id', 0);
        $approved = $this->post('action') === 'approve';
        $comments = trim($this->post('comments', ''));
        
        if (empty($requestId)) {
            $this->setError('Request ID is required.');
            $this->redirect('bus-season-requests/second-approval');
            return;
        }
        
        $requestModel = $this->model('BusSeasonRequestModel');
        $requestModel->ensureTableStructure();
        
        $request = $requestModel->findWithDetails($requestId);
        if (!$request) {
            $this->setError('Request not found.');
            $this->redirect('bus-season-requests/second-approval');
            return;
        }
        
        $userRole = $this->getUserRole();
        $result = $requestModel->updateSecondApproval($requestId, $_SESSION['user_id'], $userRole, $approved, $comments);
        
        if ($result) {
            $action = $approved ? 'approved' : 'rejected';
            $this->setMessage("Request {$action} successfully.");
        } else {
            $this->setError('Failed to update request. Please try again.');
        }
        
        $this->redirect('bus-season-requests/second-approval');
    }
    
    /**
     * SAO Process - View all requests for payment collection
     */
    public function saoProcess() {
        $this->requireAuth();
        $this->requireSAOAccess();
        
        $requestModel = $this->model('BusSeasonRequestModel');
        $requestModel->ensureTableStructure();
        
        $requests = $requestModel->getRequestsForSAO();
        $studentModel = $this->model('StudentModel');
        $academicYears = $studentModel->getAcademicYears();
        
        // Get all active students for the dropdown
        $allStudents = $studentModel->getStudents(1, 1000, ['status' => 'Active']); // Get up to 1000 active students
        
        $data = [
            'title' => 'Bus Season Requests - Payment Collection',
            'page' => 'bus-season-requests-sao',
            'requests' => $requests,
            'academicYears' => $academicYears,
            'students' => $allStudents,
            'message' => $this->getFlashMessage(),
            'error' => $this->getFlashError()
        ];
        
        return $this->view('bus-season-requests/sao-process', $data);
    }
    
    /**
     * SAO Create Request for Student
     */
    public function saoCreateRequest() {
        $this->requireAuth();
        $this->requireSAOAccess();
        $this->requirePost();
        
        $isAjax = SeasonRequestHelper::isAjaxRequest();
        
        // Get form data
        $studentId = trim($this->post('student_id', ''));
        $routeFrom = trim($this->post('route_from', ''));
        $routeTo = trim($this->post('route_to', ''));
        
        // Validate
        if (empty($studentId)) {
            $errorMsg = 'Please enter a student ID.';
            $this->handleResponse($isAjax, false, $errorMsg);
            return;
        }
        
        if (empty($routeFrom)) {
            $errorMsg = 'Please enter the route from location.';
            $this->handleResponse($isAjax, false, $errorMsg);
            return;
        }
        
        if (empty($routeTo)) {
            $errorMsg = 'Please enter the route to location.';
            $this->handleResponse($isAjax, false, $errorMsg);
            return;
        }
        
        // Verify student exists
        $studentModel = $this->model('StudentModel');
        $student = $studentModel->find($studentId);
        if (!$student) {
            $errorMsg = 'Student not found. Please check the student ID.';
            $this->handleResponse($isAjax, false, $errorMsg);
            return;
        }
        
        try {
            $requestModel = $this->model('BusSeasonRequestModel');
            $requestModel->ensureTableStructure();
            
            // Check if student already has request for 2026
            if ($requestModel->hasExistingRequest($studentId, '2026')) {
                $errorMsg = 'This student already has a bus season request for 2026.';
                $this->handleResponse($isAjax, false, $errorMsg);
                return;
            }
            
            // Get department ID
            $departmentId = null;
            $enrollmentModel = $this->model('StudentEnrollmentModel');
            $currentEnrollment = $enrollmentModel->getCurrentEnrollment($studentId);
            if ($currentEnrollment && isset($currentEnrollment['course_id'])) {
                $courseModel = $this->model('CourseModel');
                $course = $courseModel->find($currentEnrollment['course_id']);
                if ($course && isset($course['department_id'])) {
                    $departmentId = $course['department_id'];
                }
            }
            
            // Prepare request data
            $requestData = [
                'student_id' => $studentId,
                'department_id' => $departmentId,
                'season_year' => '2026',
                'season_name' => '',
                'route_from' => $routeFrom,
                'route_to' => $routeTo,
                'change_point' => '',
                'distance_km' => 0,
                'notes' => 'Created by SAO'
            ];
            
            // Create request
            $newRequestId = $requestModel->create($requestData);
            
            if ($newRequestId) {
                // Log activity
                try {
                    SeasonRequestHelper::logActivity(
                        'CREATE',
                        $newRequestId,
                        "SAO created bus season request for student {$studentId} (Season Year 2026)",
                        $requestData
                    );
                } catch (Exception $e) {
                    error_log("SAO create request - Activity log error: " . $e->getMessage());
                }
                
                $successMsg = "Bus season request created successfully for student {$studentId}.";
                $this->handleResponse($isAjax, true, $successMsg);
            } else {
                $errorMsg = 'Failed to create request. Please try again.';
                $this->handleResponse($isAjax, false, $errorMsg);
            }
        } catch (Exception $e) {
            error_log("SAO create request - Exception: " . $e->getMessage());
            $errorMsg = 'An error occurred. Please try again.';
            $this->handleResponse($isAjax, false, $errorMsg);
        }
    }
    
    /**
     * SAO Save Payment Collection
     */
    public function saoProcessSave() {
        $this->requireAuth();
        $this->requireSAOAccess();
        $this->requirePost();
        
        $requestId = (int)$this->post('request_id', 0);
        $studentPayment = floatval($this->post('student_payment_amount', $this->post('paid_amount', 0)));
        $paymentMethod = trim($this->post('payment_method', 'cash'));
        $paymentReference = trim($this->post('payment_reference', ''));
        $notes = trim($this->post('notes', ''));
        
        if (empty($requestId)) {
            $this->setError('Request ID is required.');
            $this->redirect('bus-season-requests/sao-process');
            return;
        }
        
        if ($studentPayment <= 0) {
            $this->setError('Please enter a valid payment amount.');
            $this->redirect('bus-season-requests/sao-process');
            return;
        }
        
        $requestModel = $this->model('BusSeasonRequestModel');
        $requestModel->ensureTableStructure();
        
        $request = $requestModel->findWithDetails($requestId);
        if (!$request) {
            $this->setError('Request not found.');
            $this->redirect('bus-season-requests/sao-process');
            return;
        }
        
        if ($requestModel->hasPaymentCollection($requestId)) {
            $this->setError('Payment has already been collected for this request.');
            $this->redirect('bus-season-requests/sao-process');
            return;
        }
        
        // Create payment collection
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
            $requestModel->updateStatus($requestId, 'paid');
            
            SeasonRequestHelper::logActivity(
                'CREATE',
                $paymentId,
                "SAO collected initial payment for bus season request #{$requestId} - Student: Rs. {$studentPayment}",
                [
                    'request_id' => $requestId,
                    'student_id' => $request['student_id'],
                    'paid_amount' => $studentPayment,
                    'payment_method' => $paymentMethod,
                    'status' => 'paid'
                ]
            );
            
            $this->setMessage('Payment recorded successfully. Status set to paid.');
        } else {
            $this->setError('Failed to record payment collection. Please try again.');
        }
        
        $this->redirect('bus-season-requests/sao-process');
    }
    
    /**
     * Payment Collections View
     */
    public function paymentCollections() {
        $this->requireAuth();
        $this->requireSAOAccess();
        
        $requestModel = $this->model('BusSeasonRequestModel');
        $requestModel->ensureTableStructure();
        
        $filters = [
            'season_year' => $this->get('season_year', ''),
            'student_id' => $this->get('student_id', ''),
            'month' => $this->get('month', ''),
            'status' => $this->get('status', '')
        ];
        
        $collections = $requestModel->getAllPaymentCollections($filters);
        $studentModel = $this->model('StudentModel');
        $academicYears = $studentModel->getAcademicYears();
        
        $data = [
            'title' => 'Bus Season Payment Collections',
            'page' => 'bus-season-payments',
            'collections' => $collections,
            'academicYears' => $academicYears,
            'filters' => $filters,
            'message' => $this->getFlashMessage(),
            'error' => $this->getFlashError()
        ];
        
        return $this->view('bus-season-requests/payment-collections', $data);
    }
    
    /**
     * Update Payment Status (AJAX)
     */
    public function updatePaymentStatus() {
        $this->requireAuth();
        $this->requireSAOAccess();
        $this->requirePost();
        
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
            // Calculate payment breakdown using helper
            $totalAmount = $actualPrice > 0 ? $actualPrice : ($studentPortion > 0 ? $studentPortion / 0.30 : 0);
            $breakdown = SeasonRequestHelper::calculatePaymentBreakdown($totalAmount, $payment['paid_amount']);
            
            $updateData = [
                'total_amount' => $breakdown['total_amount'],
                'student_paid' => $breakdown['student_paid'],
                'slgti_paid' => $breakdown['slgti_paid'],
                'ctb_paid' => $breakdown['ctb_paid'],
                'season_rate' => $breakdown['total_amount'],
                'remaining_balance' => $breakdown['remaining_balance'],
                'payment_reference' => $paymentReference ?: $payment['payment_reference']
            ];
        }
        
        if ($requestModel->updatePaymentStatus($paymentId, $newStatus, $updateData)) {
            $requestModel->updateStatus($payment['request_id'], $newStatus);
            $this->json(['success' => true, 'message' => 'Status updated successfully.']);
        } else {
            $this->json(['success' => false, 'message' => 'Failed to update status.']);
        }
    }
    
    /**
     * Bulk Update Payment Status (AJAX)
     */
    public function bulkUpdateStatus() {
        $this->requireAuth();
        $this->requireSAOAccess();
        $this->requirePost();
        
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
            if ($payment && $requestModel->updatePaymentStatus($id, $newStatus)) {
                $requestModel->updateStatus($payment['request_id'], $newStatus);
                $successCount++;
            }
        }
        
        $this->json([
            'success' => true,
            'message' => "Successfully updated {$successCount} records to {$newStatus}."
        ]);
    }
    
    /**
     * Export Payments to Excel
     */
    public function exportPaymentsExcel() {
        $this->requireAuth();
        $this->requireSAOAccess();
        
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
        
        // CSV Headers
        if ($status === 'issued') {
            fputcsv($output, [
                'Name', 'NIC Number', 'Route', 'Total Price', 'Student Portion (30%)',
                'SLGTI Portion (35%)', 'CTB Portion (35%)', 'Issued Date', 'Reference Number'
            ]);
        } else {
            fputcsv($output, [
                'Name', 'NIC Number', 'Route', 'Paid Amount', 'Paid Date', 'Status', 'Reference Number'
            ]);
        }
        
        foreach ($collections as $c) {
            $route = SeasonRequestHelper::formatRoute(
                $c['route_from'] ?? '',
                $c['route_to'] ?? '',
                $c['change_point'] ?? ''
            );
            
            if ($status === 'issued') {
                fputcsv($output, [
                    $c['student_fullname'] ?? 'N/A',
                    $c['student_nic'] ?? 'N/A',
                    $route,
                    number_format($c['total_amount'] ?? 0, 2, '.', ''),
                    number_format($c['student_paid'] ?? 0, 2, '.', ''),
                    number_format($c['slgti_paid'] ?? 0, 2, '.', ''),
                    number_format($c['ctb_paid'] ?? 0, 2, '.', ''),
                    SeasonRequestHelper::formatDate($c['issued_at'] ?? null, 'Y-m-d'),
                    $c['payment_reference'] ?? 'N/A'
                ]);
            } else {
                fputcsv($output, [
                    $c['student_fullname'] ?? 'N/A',
                    $c['student_nic'] ?? 'N/A',
                    $route,
                    number_format($c['paid_amount'] ?? 0, 2, '.', ''),
                    SeasonRequestHelper::formatDate($c['payment_date'] ?? null, 'Y-m-d'),
                    ucfirst($c['payment_status'] ?? 'N/A'),
                    $c['payment_reference'] ?? 'N/A'
                ]);
            }
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * View Request Details
     */
    public function show() {
        $this->requireAuth();
        
        $requestId = (int)$this->get('id', 0);
        if (empty($requestId)) {
            $this->setError('Request ID is required.');
            $this->redirect('dashboard');
            return;
        }
        
        $requestModel = $this->model('BusSeasonRequestModel');
        $requestModel->ensureTableStructure();
        $request = $requestModel->findWithDetails($requestId);
        
        if (!$request) {
            $this->setError('Request not found.');
            $this->redirect('dashboard');
            return;
        }
        
        // Verify access permissions
        if (!$this->canViewRequest($request)) {
            $this->setError('Access denied.');
            $this->redirect('dashboard');
            return;
        }
        
        $data = [
            'title' => 'Bus Season Request Details',
            'page' => 'bus-season-requests',
            'request' => $request,
            'isStudent' => $this->isStudent(),
            'isHOD' => $this->isHOD(),
            'isSAO' => $this->isSAO(),
            'canSecondApprove' => $this->canSecondApprove(),
            'userRole' => $this->getUserRole()
        ];
        
        return $this->view('bus-season-requests/view', $data);
    }
    
    // ==================== Helper Methods ====================
    
    /**
     * Handle response (AJAX or redirect)
     */
    private function handleResponse($isAjax, $success, $message) {
        if ($isAjax) {
            SeasonRequestHelper::sendJsonResponse([
                'success' => $success,
                'message' => $message,
                'redirect' => $success ? APP_URL . '/bus-season-requests' : null
            ]);
        } else {
            if ($success) {
                $this->setMessage($message);
            } else {
                $this->setError($message);
            }
            $this->redirect('bus-season-requests');
        }
    }
    
    /**
     * Check if user can view request
     */
    private function canViewRequest($request) {
        if ($this->isStudent()) {
            return $request['student_id'] === $_SESSION['user_name'];
        }
        
        if ($this->isHOD()) {
            $hodDepartmentId = $this->getHODDepartment();
            return $request['department_id'] === $hodDepartmentId;
        }
        
        return true; // SAO, Admin, etc. can view all
    }
    
    /**
     * Require authentication
     */
    private function requireAuth() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            exit;
        }
    }
    
    /**
     * Require student role
     */
    private function requireStudent() {
        if (!isset($_SESSION['user_table']) || $_SESSION['user_table'] !== 'student') {
            $this->setError('Access denied. This section is only available for students.');
            $this->redirect('dashboard');
            exit;
        }
    }
    
    /**
     * Require HOD role
     */
    private function requireHOD() {
        if (!$this->isHOD()) {
            $this->setError('Access denied. Only Head of Department can access this section.');
            $this->redirect('dashboard');
            exit;
        }
    }
    
    /**
     * Require second approver role
     */
    private function requireSecondApprover() {
        if (!$this->canSecondApprove()) {
            $this->setError('Access denied. Only Director, DPA, DPI, or Registrar can access this section.');
            $this->redirect('dashboard');
            exit;
        }
    }
    
    /**
     * Require SAO access
     */
    private function requireSAOAccess() {
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $isSAO = $userModel->isSAO($_SESSION['user_id']);
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        $isADM = ($userRole === 'ADM');
        $isAdmin = $userModel->isAdmin($_SESSION['user_id']);
        
        if (!$isSAO && !$isADM && !$isAdmin) {
            $this->setError('Access denied. Only SAO and Administrators can access this section.');
            $this->redirect('dashboard');
            exit;
        }
    }
    
    /**
     * Require POST method
     */
    private function requirePost() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->setError('Invalid request method.');
            $this->redirect('dashboard');
            exit;
        }
    }
    
    /**
     * Check if user is student
     */
    private function isStudent() {
        return isset($_SESSION['user_table']) && $_SESSION['user_table'] === 'student';
    }
    
    /**
     * Check if user is SAO
     */
    private function isSAO() {
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        return $userModel->isSAO($_SESSION['user_id']);
    }
    
    /**
     * Check if user can second approve
     */
    private function canSecondApprove() {
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        $allowedRoles = ['DIR', 'DPA', 'DPI', 'REG'];
        return in_array($userRole, $allowedRoles) || $userModel->isAdmin($_SESSION['user_id']);
    }
    
    /**
     * Get user role
     */
    private function getUserRole() {
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        return $userModel->getUserRole($_SESSION['user_id']);
    }
    
    
    /**
     * Set flash message
     */
    private function setMessage($message) {
        $_SESSION['message'] = $message;
    }
    
    /**
     * Set flash error
     */
    private function setError($error) {
        $_SESSION['error'] = $error;
    }
    
    /**
     * Get flash message
     */
    private function getFlashMessage() {
        $message = $_SESSION['message'] ?? null;
        unset($_SESSION['message']);
        return $message;
    }
    
    /**
     * Get flash error
     */
    private function getFlashError() {
        $error = $_SESSION['error'] ?? null;
        unset($_SESSION['error']);
        return $error;
    }
    
}

