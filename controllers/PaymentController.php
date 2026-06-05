<?php
/**
 * Payment Controller
 */

class PaymentController extends Controller {
    
    public function index() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        if (!$this->checkPaymentsViewAccess()) {
            return;
        }

        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $canManagePayments = $userModel->hasFinanceAccess($_SESSION['user_id']);
        
        $paymentModel = $this->model('PaymentModel');
        $studentModel = $this->model('StudentModel');
        
        // Get HOD's department if user is HOD (though HOD shouldn't have finance access normally)
        $hodDepartmentId = $this->getHODDepartment();
        
        // Get filter parameters
        $search = $this->get('search', '');
        $studentId = $this->get('student_id', '');
        $paymentType = $this->get('payment_type', '');
        $dateFrom = $this->get('date_from', '');
        $dateTo = $this->get('date_to', '');
        $page = max(1, (int)$this->get('page', 1));
        $perPage = 20;
        
        // Build filters - include department filter for HOD
        $filters = [];
        if (!empty($search)) {
            $filters['search'] = $search;
        }
        if (!empty($studentId)) {
            $filters['student_id'] = $studentId;
        }
        if (!empty($paymentType)) {
            $filters['payment_type'] = $paymentType;
        }
        if (!empty($dateFrom)) {
            $filters['date_from'] = $dateFrom;
        }
        if (!empty($dateTo)) {
            $filters['date_to'] = $dateTo;
        }
        if ($hodDepartmentId) {
            $filters['department_id'] = $hodDepartmentId;
        }
        
        // Get total count
        $total = $paymentModel->getTotal($filters);
        $totalPages = ceil($total / $perPage);
        $page = min($page, max(1, $totalPages));
        
        // Get payments
        $payments = $paymentModel->getAll($filters);
        
        // Apply pagination manually (since getAll doesn't support limit/offset yet)
        $offset = ($page - 1) * $perPage;
        $payments = array_slice($payments, $offset, $perPage);
        
        // Get students for filter dropdown - filter by department if HOD
        $studentFilters = ['status' => 'active'];
        if ($hodDepartmentId) {
            $studentFilters['department_id'] = $hodDepartmentId;
        }
        $students = $studentModel->getStudents(1, 1000, $studentFilters);
        
        $data = [
            'title' => 'Payments',
            'page' => 'payments',
            'payments' => $payments,
            'students' => $students,
            'total' => $total,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'perPage' => $perPage,
            'filters' => [
                'search' => $search,
                'student_id' => $studentId,
                'payment_type' => $paymentType,
                'date_from' => $dateFrom,
                'date_to' => $dateTo
            ],
            'message' => $_SESSION['message'] ?? null,
            'error' => $_SESSION['error'] ?? null,
            'can_manage_payments' => $canManagePayments,
        ];
        
        unset($_SESSION['message'], $_SESSION['error']);
        
        return $this->view('payments/index', $data);
    }
    
    public function create() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Restrict to FIN, ACC, ADM only
        if (!$this->checkFinanceAccess()) {
            return;
        }
        
        $studentModel = $this->model('StudentModel');
        $paymentModel = $this->model('PaymentModel');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $studentId = trim($this->post('student_id', ''));
            $paysDate = trim($this->post('payment_date', ''));
            $paysAmount = trim($this->post('payment_amount', ''));
            $paymentReason = trim($this->post('payment_reason', ''));
            $paymentType = trim($this->post('payment_type', ''));
            $paymentMethod = trim($this->post('payment_method', ''));
            $paysNote = trim($this->post('payment_notes', ''));
            $paysQty = (int)$this->post('pays_qty', 1);
            $referenceNo = trim($this->post('reference_no', ''));
            $approved = $this->post('approved', '0') == '1' ? 1 : 0;
            
            // Validation
            if (empty($studentId) || empty($paysDate) || empty($paysAmount) || empty($paymentReason) || empty($paymentType)) {
                $_SESSION['error'] = 'Student, Payment Date, Amount, Reason, and Payment Type are required.';
                $this->redirect('payments/create');
                return;
            }
            
            // Validate amount
            if (!is_numeric($paysAmount) || $paysAmount <= 0) {
                $_SESSION['error'] = 'Payment amount must be a positive number.';
                $this->redirect('payments/create');
                return;
            }
            
            // Check if student exists
            if (!$studentModel->exists($studentId)) {
                $_SESSION['error'] = 'Student not found.';
                $this->redirect('payments/create');
                return;
            }
            
            // Get student's department from enrollment
            $studentEnrollmentModel = $this->model('StudentEnrollmentModel');
            $enrollment = $studentEnrollmentModel->getCurrentEnrollment($studentId);
            $paysDepartment = $enrollment['department_id'] ?? '';
            
            // Create payment
            $result = $paymentModel->createPayment([
                'student_id' => $studentId,
                'pays_date' => $paysDate,
                'pays_amount' => $paysAmount,
                'payment_reason' => $paymentReason,
                'payment_type' => $paymentType,
                'payment_method' => $paymentMethod,
                'pays_note' => $paysNote,
                'pays_qty' => $paysQty,
                'reference_no' => $referenceNo,
                'pays_department' => $paysDepartment,
                'approved' => $approved
            ]);
            
            if ($result) {
                $_SESSION['message'] = 'Payment created successfully.';
                $this->redirect('payments/receipt?id=' . (int)$result);
            } else {
                $_SESSION['error'] = 'Failed to create payment.';
                $this->redirect('payments/create');
            }
        } else {
            // Get students for dropdown
            $students = $studentModel->getStudents(1, 1000, ['status' => 'active']);
            
            // Get payment reasons and types
            $paymentReasons = $paymentModel->getPaymentReasons();
            
            $data = [
                'title' => 'Create Payment',
                'page' => 'payments-create',
                'students' => $students,
                'paymentReasons' => $paymentReasons,
                'error' => $_SESSION['error'] ?? null
            ];
            unset($_SESSION['error']);
            return $this->view('payments/create', $data);
        }
    }
    
    public function receipt() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }

        if (!$this->checkPaymentsViewAccess()) {
            return;
        }

        $id = (int)$this->get('id', 0);
        if ($id <= 0) {
            $_SESSION['error'] = 'Payment ID is required.';
            $this->redirect('payments');
            return;
        }

        $paymentModel = $this->model('PaymentModel');
        $payment = $paymentModel->getById($id);

        if (!$payment) {
            $_SESSION['error'] = 'Payment not found.';
            $this->redirect('payments');
            return;
        }

        $autoPrint = $this->get('print', '1') !== '0';
        $qty = max(1, (int)($payment['pays_qty'] ?? 1));
        $unitAmount = (float)($payment['pays_amount'] ?? 0);
        $lineTotal = $unitAmount * $qty;

        $data = [
            'title' => 'Payment Receipt (POS)',
            'use_print_layout' => true,
            'payment' => $payment,
            'cashierName' => $_SESSION['user_name'] ?? 'Staff',
            'lineTotal' => $lineTotal,
            'autoPrint' => $autoPrint,
            'posReceipt' => true,
            'message' => $_SESSION['message'] ?? null,
        ];
        unset($_SESSION['message']);

        return $this->view('payments/receipt', $data);
    }

    public function edit() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Restrict to FIN, ACC, ADM only
        if (!$this->checkFinanceAccess()) {
            return;
        }
        
        $id = $this->get('id', '');
        if (empty($id)) {
            $_SESSION['error'] = 'Payment ID is required.';
            $this->redirect('payments');
            return;
        }
        
        $paymentModel = $this->model('PaymentModel');
        $studentModel = $this->model('StudentModel');
        $payment = $paymentModel->getById($id);
        
        if (!$payment) {
            $_SESSION['error'] = 'Payment not found.';
            $this->redirect('payments');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $studentId = trim($this->post('student_id', ''));
            $paysDate = trim($this->post('payment_date', ''));
            $paysAmount = trim($this->post('payment_amount', ''));
            $paymentReason = trim($this->post('payment_reason', ''));
            $paymentType = trim($this->post('payment_type', ''));
            $paymentMethod = trim($this->post('payment_method', ''));
            $paysNote = trim($this->post('payment_notes', ''));
            $paysQty = (int)$this->post('pays_qty', 1);
            $referenceNo = trim($this->post('reference_no', ''));
            $approved = $this->post('approved', '0') == '1' ? 1 : 0;
            
            // Validation
            if (empty($studentId) || empty($paysDate) || empty($paysAmount) || empty($paymentReason) || empty($paymentType)) {
                $_SESSION['error'] = 'Student, Payment Date, Amount, Reason, and Payment Type are required.';
                $this->redirect('payments/edit?id=' . urlencode($id));
                return;
            }
            
            // Validate amount
            if (!is_numeric($paysAmount) || $paysAmount <= 0) {
                $_SESSION['error'] = 'Payment amount must be a positive number.';
                $this->redirect('payments/edit?id=' . urlencode($id));
                return;
            }
            
            // Check if student exists
            if (!$studentModel->exists($studentId)) {
                $_SESSION['error'] = 'Student not found.';
                $this->redirect('payments/edit?id=' . urlencode($id));
                return;
            }
            
            // Get student's department from enrollment
            $studentEnrollmentModel = $this->model('StudentEnrollmentModel');
            $enrollment = $studentEnrollmentModel->getCurrentEnrollment($studentId);
            $paysDepartment = $enrollment['department_id'] ?? $payment['pays_department'] ?? '';
            
            // Update payment
            $result = $paymentModel->updatePayment($id, [
                'student_id' => $studentId,
                'pays_date' => $paysDate,
                'pays_amount' => $paysAmount,
                'payment_reason' => $paymentReason,
                'payment_type' => $paymentType,
                'payment_method' => $paymentMethod,
                'pays_note' => $paysNote,
                'pays_qty' => $paysQty,
                'reference_no' => $referenceNo,
                'pays_department' => $paysDepartment,
                'approved' => $approved
            ]);
            
            if ($result) {
                $_SESSION['message'] = 'Payment updated successfully.';
                $this->redirect('payments');
            } else {
                $_SESSION['error'] = 'Failed to update payment.';
                $this->redirect('payments/edit?id=' . urlencode($id));
            }
        } else {
            // Get students for dropdown
            $students = $studentModel->getStudents(1, 1000, ['status' => 'active']);
            
            // Get payment reasons and types
            $paymentReasons = $paymentModel->getPaymentReasons();
            
            $data = [
                'title' => 'Edit Payment',
                'page' => 'payments-edit',
                'payment' => $payment,
                'students' => $students,
                'paymentReasons' => $paymentReasons,
                'error' => $_SESSION['error'] ?? null
            ];
            unset($_SESSION['error']);
            return $this->view('payments/edit', $data);
        }
    }
    
    public function delete() {
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Restrict to FIN, ACC, ADM only
        if (!$this->checkFinanceAccess()) {
            return;
        }
        
        $id = $this->get('id', '');
        if (empty($id)) {
            $_SESSION['error'] = 'Payment ID is required.';
            $this->redirect('payments');
            return;
        }
        
        $paymentModel = $this->model('PaymentModel');
        $payment = $paymentModel->getById($id);
        
        if (!$payment) {
            $_SESSION['error'] = 'Payment not found.';
            $this->redirect('payments');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Delete payment
            $result = $paymentModel->deletePayment($id);
            
            if ($result) {
                $_SESSION['message'] = 'Payment deleted successfully.';
            } else {
                $_SESSION['error'] = 'Failed to delete payment.';
            }
            
            $this->redirect('payments');
        } else {
            $data = [
                'title' => 'Delete Payment',
                'page' => 'payments-delete',
                'payment' => $payment
            ];
            return $this->view('payments/delete', $data);
        }
    }
}

