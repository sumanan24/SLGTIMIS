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
            $postedToken = (string) $this->post('form_token', '');
            $expectedToken = (string) ($_SESSION['payment_form_token'] ?? '');
            unset($_SESSION['payment_form_token']);
            if ($expectedToken === '' || !hash_equals($expectedToken, $postedToken)) {
                $_SESSION['error'] = 'This payment was already submitted or the form expired. Check the latest receipt before collecting again.';
                $this->redirect('payments/create');
                return;
            }

            $studentId = trim($this->post('student_id', ''));
            $paysDate = trim($this->post('payment_date', ''));
            $paymentMethod = strtoupper(trim($this->post('payment_method', '')));
            $paysNote = trim($this->post('payment_notes', ''));
            $referenceNo = trim($this->post('reference_no', ''));
            $approved = $this->post('approved', '0') == '1' ? 1 : 0;

            if ($studentId === '' || $paysDate === '') {
                $_SESSION['error'] = 'Student and payment date are required.';
                $this->redirect('payments/create');
                return;
            }

            $dateCheck = DateTime::createFromFormat('Y-m-d', $paysDate);
            if (!$dateCheck || $dateCheck->format('Y-m-d') !== $paysDate) {
                $_SESSION['error'] = 'Payment date is not valid.';
                $this->redirect('payments/create');
                return;
            }

            $allowedMethods = $this->posPaymentMethods($paymentModel);
            if ($paymentMethod !== '' && !isset($allowedMethods[$paymentMethod])) {
                $_SESSION['error'] = 'Select a payment method from the counter options.';
                $this->redirect('payments/create');
                return;
            }

            if (!$studentModel->exists($studentId)) {
                $_SESSION['error'] = 'Student not found.';
                $this->redirect('payments/create');
                return;
            }

            $parsedItems = $this->parsePosPaymentItems($this->post('items', []));
            if (!empty($parsedItems['error'])) {
                $_SESSION['error'] = $parsedItems['error'];
                $this->redirect('payments/create');
                return;
            }

            $paymentTypeModel = $this->model('PaymentTypeModel');
            foreach ($parsedItems['items'] as $item) {
                if (!$paymentTypeModel->exists($item['payment_reason'], $item['payment_type'])) {
                    $_SESSION['error'] = 'Invalid payment type and reason. Please select from the configured options or add them under Payment Types.';
                    $this->redirect('payments/create');
                    return;
                }
            }

            $studentEnrollmentModel = $this->model('StudentEnrollmentModel');
            $enrollment = $studentEnrollmentModel->getCurrentEnrollment($studentId);
            $paysDepartment = $enrollment['department_id'] ?? '';

            $db = Database::getInstance();
            if (!$db->begin_transaction()) {
                $_SESSION['error'] = 'Could not start the payment. Nothing was recorded.';
                $this->redirect('payments/create');
                return;
            }
            $savedIds = [];
            $saveFailed = false;
            foreach ($parsedItems['items'] as $item) {
                $result = $paymentModel->createPayment([
                    'student_id' => $studentId,
                    'pays_date' => $paysDate,
                    'pays_amount' => $item['payment_amount'],
                    'payment_reason' => $item['payment_reason'],
                    'payment_type' => $item['payment_type'],
                    'payment_method' => $paymentMethod,
                    'pays_note' => $paysNote,
                    'pays_qty' => $item['pays_qty'],
                    'reference_no' => $referenceNo,
                    'pays_department' => $paysDepartment,
                    'approved' => $approved
                ]);
                if (!$result) {
                    $saveFailed = true;
                    break;
                }
                $savedIds[] = (int) $result;
            }

            if ($saveFailed || count($savedIds) !== count($parsedItems['items'])) {
                $db->rollback();
                $_SESSION['error'] = 'Failed to save the payment. No items were recorded.';
                $this->redirect('payments/create');
                return;
            }

            $db->commit();
            $count = count($savedIds);
            $_SESSION['message'] = $count === 1
                ? 'Payment collected successfully.'
                : $count . ' payments collected successfully.';
            $this->redirect('payments/receipt?ids=' . implode(',', $savedIds));
        } else {
            $paymentReasons = $paymentModel->getPaymentReasons();
            $_SESSION['payment_form_token'] = bin2hex(random_bytes(16));

            $data = [
                'title' => 'Student Payment Counter',
                'page' => 'payments-create',
                'paymentReasons' => $paymentReasons,
                'paymentMethods' => $this->posPaymentMethods($paymentModel),
                'cashierName' => $_SESSION['user_name'] ?? 'Staff',
                'formToken' => $_SESSION['payment_form_token'],
                'error' => $_SESSION['error'] ?? null
            ];
            unset($_SESSION['error']);
            return $this->view('payments/create', $data);
        }
    }

    /**
     * AJAX student lookup for the payment counter.
     */
    public function searchStudents() {
        if (!isset($_SESSION['user_id'])) {
            $this->json(['students' => [], 'error' => 'Login required'], 401);
        }

        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        if (!$userModel->hasFinanceAccess($_SESSION['user_id'])) {
            $this->json(['students' => [], 'error' => 'Access denied'], 403);
        }

        $term = trim((string) $this->get('q', ''));
        if (strlen($term) < 2) {
            $this->json(['students' => []]);
        }

        $studentModel = $this->model('StudentModel');
        $rows = $studentModel->searchForPaymentCounter($term, 12);
        $students = [];
        foreach ($rows as $row) {
            $students[] = [
                'student_id' => $row['student_id'],
                'student_fullname' => $row['student_fullname'] ?? '',
                'student_nic' => $row['student_nic'] ?? '',
                'course_name' => $row['course_name'] ?? '',
                'department_name' => $row['department_name'] ?? '',
                'academic_year' => $row['academic_year'] ?? '',
                'photo_url' => $this->studentPhotoUrl($row['student_profile_img'] ?? ''),
            ];
        }

        $this->json(['students' => $students]);
    }
    
    public function receipt() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }

        if (!$this->checkPaymentsViewAccess()) {
            return;
        }

        $paymentModel = $this->model('PaymentModel');
        $ids = $this->receiptIdsFromRequest();
        if (!$ids) {
            $_SESSION['error'] = 'Payment ID is required.';
            $this->redirect('payments');
            return;
        }

        $payments = [];
        foreach ($ids as $receiptId) {
            $row = $paymentModel->getById($receiptId);
            if (!$row) {
                $_SESSION['error'] = 'Payment not found.';
                $this->redirect('payments');
                return;
            }
            $payments[] = $row;
        }

        $studentKey = (string) ($payments[0]['student_id'] ?? '');
        foreach ($payments as $row) {
            if ((string) ($row['student_id'] ?? '') !== $studentKey) {
                $_SESSION['error'] = 'These payments are not one collection.';
                $this->redirect('payments');
                return;
            }
        }

        $payment = $payments[0];
        $autoPrint = $this->get('print', '1') !== '0';
        $lineTotal = 0.0;
        foreach ($payments as $row) {
            $qty = max(1, (int) ($row['pays_qty'] ?? 1));
            $lineTotal += (float) ($row['pays_amount'] ?? 0) * $qty;
        }

        $data = [
            'title' => 'Payment Receipt (POS)',
            'use_print_layout' => true,
            'payment' => $payment,
            'payments' => $payments,
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

            $paymentTypeModel = $this->model('PaymentTypeModel');
            if (!$paymentTypeModel->exists($paymentReason, $paymentType)) {
                $_SESSION['error'] = 'Invalid payment type and reason. Please select from the configured options or add them under Payment Types.';
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

    /**
     * Payments report: date range, type-wise or student-wise.
     */
    public function report() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }

        if (!$this->checkPaymentsViewAccess()) {
            return;
        }

        $paymentModel = $this->model('PaymentModel');
        $studentModel = $this->model('StudentModel');
        $paymentTypeModel = $this->model('PaymentTypeModel');
        $hodDepartmentId = $this->getHODDepartment();

        $submitted = array_key_exists('date_from', $_GET) || array_key_exists('basis', $_GET);
        $dateFrom = trim((string) $this->get('date_from', ''));
        $dateTo = trim((string) $this->get('date_to', ''));
        if (!$submitted) {
            $dateFrom = date('Y-m-01');
            $dateTo = date('Y-m-d');
        }

        $basis = $this->get('basis', 'type');
        if (!in_array($basis, ['type', 'subtype', 'student', 'department'], true)) {
            $basis = 'type';
        }

        $paymentType = trim((string) $this->get('payment_type', ''));
        $paymentSubtype = trim((string) $this->get('payment_subtype', ''));
        $academicYear = trim((string) $this->get('academic_year', ''));
        if (strlen($academicYear) > 20) {
            $academicYear = '';
        }
        $studentId = trim((string) $this->get('student_id', ''));
        $paysDepartment = trim((string) $this->get('department', ''));
        $format = strtolower(trim((string) $this->get('format', '')));
        $error = null;

        if ($dateFrom !== '' && !$this->isValidReportDate($dateFrom)) {
            $error = 'Date from is not a valid date.';
            $dateFrom = '';
        }
        if ($dateTo !== '' && !$this->isValidReportDate($dateTo)) {
            $error = 'Date to is not a valid date.';
            $dateTo = '';
        }
        if ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
            $swap = $dateFrom;
            $dateFrom = $dateTo;
            $dateTo = $swap;
        }

        $filters = [];
        if ($dateFrom !== '') {
            $filters['date_from'] = $dateFrom;
        }
        if ($dateTo !== '') {
            $filters['date_to'] = $dateTo;
        }
        if ($paymentType !== '') {
            $filters['payment_type'] = $paymentType;
        }
        if ($paymentSubtype !== '') {
            $filters['payment_subtype'] = $paymentSubtype;
        }
        if ($academicYear !== '') {
            $filters['academic_year'] = $academicYear;
        }
        if ($studentId !== '') {
            $filters['student_id'] = $studentId;
        }
        if ($paysDepartment !== '') {
            $filters['pays_department'] = $paysDepartment;
        }
        if ($hodDepartmentId) {
            $filters['department_id'] = $hodDepartmentId;
        }

        $payments = $error ? [] : $this->uniquePayments($paymentModel->getAll($filters));
        usort($payments, [$this, 'comparePaymentsByDepartment']);
        if ($basis === 'student') {
            $groups = $this->groupPaymentsByStudent($payments);
        } elseif ($basis === 'subtype') {
            $groups = $this->groupPaymentsBySubtype($payments);
        } elseif ($basis === 'department') {
            $groups = $this->groupPaymentsByDepartment($payments);
        } else {
            $groups = $this->groupPaymentsByType($payments);
        }

        $summary = $this->paymentReportSummary($payments);

        if ($format === 'csv' && !$error) {
            $this->outputPaymentReportCsv($payments, $basis, $dateFrom, $dateTo);
            return;
        }

        $typeOptions = $paymentTypeModel->getDistinctTypes();
        foreach ($paymentModel->getUsedPaymentTypes() as $usedType) {
            if (!in_array($usedType, $typeOptions, true)) {
                $typeOptions[] = $usedType;
            }
        }
        sort($typeOptions, SORT_NATURAL | SORT_FLAG_CASE);

        $subtypeOptions = [];
        foreach ($paymentTypeModel->getAll() as $row) {
            $key = ($row['payment_type'] ?? '') . "\0" . ($row['payment_reason'] ?? '');
            $subtypeOptions[$key] = [
                'payment_type' => $row['payment_type'] ?? '',
                'payment_reason' => $row['payment_reason'] ?? '',
            ];
        }
        foreach ($paymentModel->getUsedPaymentSubtypes() as $row) {
            $key = ($row['payment_type'] ?? '') . "\0" . ($row['payment_reason'] ?? '');
            $subtypeOptions[$key] = [
                'payment_type' => $row['payment_type'] ?? '',
                'payment_reason' => $row['payment_reason'] ?? '',
            ];
        }
        $subtypeOptions = array_values($subtypeOptions);
        usort($subtypeOptions, function ($a, $b) {
            $type = strcasecmp((string) $a['payment_type'], (string) $b['payment_type']);
            if ($type !== 0) {
                return $type;
            }
            return strcasecmp((string) $a['payment_reason'], (string) $b['payment_reason']);
        });

        $studentFilters = ['status' => 'active'];
        if ($hodDepartmentId) {
            $studentFilters['department_id'] = $hodDepartmentId;
        }
        $students = $studentModel->getStudents(1, 1000, $studentFilters);

        $query = [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'basis' => $basis,
            'payment_type' => $paymentType,
            'payment_subtype' => $paymentSubtype,
            'academic_year' => $academicYear,
            'student_id' => $studentId,
            'department' => $paysDepartment,
        ];

        $data = [
            'title' => 'Payments Report',
            'page' => 'payments-report',
            'payments' => $payments,
            'groups' => $groups,
            'summary' => $summary,
            'students' => $students,
            'paymentTypes' => $typeOptions,
            'paymentSubtypes' => $subtypeOptions,
            'academicYears' => $studentModel->getAcademicYears(),
            'departments' => $this->model('DepartmentModel')->getAll(),
            'filters' => $query,
            'basis' => $basis,
            'error' => $error,
            'generatedAt' => date('d M Y, h:i A'),
            'printMode' => $format === 'print',
        ];

        if ($format === 'print') {
            $data['use_print_layout'] = true;
        }

        return $this->view('payments/report', $data);
    }

    /**
     * Methods the counter can collect. Starts from the form options already used
     * by payments, then adds any other method codes stored on past payments.
     */
    private function posPaymentMethods($paymentModel) {
        $methods = [
            'CASH' => ['label' => 'Cash', 'icon' => 'fa-money-bill-wave'],
            'BANK' => ['label' => 'Bank Transfer', 'icon' => 'fa-university'],
            'CHEQUE' => ['label' => 'Cheque', 'icon' => 'fa-money-check'],
            'ONLINE' => ['label' => 'Online Payment', 'icon' => 'fa-globe'],
            'SLGTI' => ['label' => 'SLGTI', 'icon' => 'fa-landmark'],
            'OTHER' => ['label' => 'Other', 'icon' => 'fa-ellipsis-h'],
        ];

        foreach ($paymentModel->getUsedPaymentMethods() as $code) {
            $code = strtoupper(trim((string) $code));
            if ($code === '' || isset($methods[$code])) {
                continue;
            }
            $methods[$code] = ['label' => $code, 'icon' => 'fa-wallet'];
        }

        return $methods;
    }

    private function studentPhotoUrl($relativePath) {
        $rel = trim((string) $relativePath);
        if ($rel === '') {
            return '';
        }
        $rel = ltrim(str_replace('\\', '/', $rel), '/');
        if (strpos($rel, 'assets/') === 0) {
            $rel = substr($rel, 7);
        }
        if (strpos($rel, 'img/student_profile/') === 0) {
            $rel = str_replace('img/student_profile/', 'img/Studnet_profile/', $rel);
        }
        if (strpos($rel, 'img/') !== 0) {
            $rel = 'img/Studnet_profile/' . basename($rel);
        }
        $full = BASE_PATH . '/assets/' . $rel;
        if (!is_file($full)) {
            return '';
        }
        return rtrim(APP_URL, '/') . '/assets/' . $rel;
    }

    /**
     * Validate the bill lines posted by the payment counter.
     */
    private function parsePosPaymentItems($raw) {
        if (!is_array($raw) || $raw === []) {
            return ['error' => 'Add at least one payment item.'];
        }
        if (count($raw) > 20) {
            return ['error' => 'A single collection can include up to 20 items.'];
        }

        $items = [];
        foreach (array_values($raw) as $index => $row) {
            if (!is_array($row)) {
                return ['error' => 'Payment item ' . ($index + 1) . ' is not valid.'];
            }
            $type = trim((string) ($row['payment_type'] ?? ''));
            $reason = trim((string) ($row['payment_reason'] ?? ''));
            $amount = trim((string) ($row['payment_amount'] ?? ''));
            $qty = (int) ($row['pays_qty'] ?? 1);
            $label = 'Item ' . ($index + 1);

            if ($type === '' || $reason === '') {
                return ['error' => $label . ' needs a payment type and reason.'];
            }
            if (!is_numeric($amount) || (float) $amount <= 0) {
                return ['error' => $label . ' needs an amount greater than zero.'];
            }
            if ($qty < 1 || $qty > 999) {
                return ['error' => $label . ' quantity must be between 1 and 999.'];
            }

            $items[] = [
                'payment_type' => $type,
                'payment_reason' => $reason,
                'payment_amount' => number_format(round((float) $amount, 2), 2, '.', ''),
                'pays_qty' => $qty,
            ];
        }

        return ['items' => $items];
    }

    private function receiptIdsFromRequest() {
        $ids = [];
        $packed = trim((string) $this->get('ids', ''));
        if ($packed !== '') {
            foreach (explode(',', $packed) as $part) {
                $id = (int) trim($part);
                if ($id > 0) {
                    $ids[] = $id;
                }
            }
        }
        $single = (int) $this->get('id', 0);
        if ($single > 0) {
            $ids[] = $single;
        }

        $unique = [];
        foreach ($ids as $id) {
            $unique[$id] = $id;
        }
        return array_values($unique);
    }

    private function isValidReportDate($value) {
        $dt = DateTime::createFromFormat('Y-m-d', $value);
        return $dt && $dt->format('Y-m-d') === $value;
    }

    private function uniquePayments($payments) {
        $unique = [];
        foreach ($payments as $payment) {
            $unique[(int) $payment['pays_id']] = $payment;
        }
        return array_values($unique);
    }

    private function paymentLineTotal($payment) {
        $qty = max(1, (int) ($payment['pays_qty'] ?? 1));
        return (float) ($payment['pays_amount'] ?? 0) * $qty;
    }

    private function paymentReportSummary($payments) {
        $students = [];
        $types = [];
        $subtypes = [];
        $departments = [];
        $total = 0.0;
        foreach ($payments as $payment) {
            $line = $this->paymentLineTotal($payment);
            $total += $line;
            $students[(string) ($payment['student_id'] ?? '')] = true;
            $type = trim((string) ($payment['payment_type'] ?? ''));
            if ($type === '') {
                $type = 'Unspecified';
            }
            if (!isset($types[$type])) {
                $types[$type] = ['count' => 0, 'amount' => 0.0];
            }
            $types[$type]['count']++;
            $types[$type]['amount'] += $line;

            $subtype = trim((string) ($payment['payment_reason'] ?? ''));
            if ($subtype === '') {
                $subtype = 'Unspecified';
            }
            $subtypeKey = $type . "\0" . $subtype;
            if (!isset($subtypes[$subtypeKey])) {
                $subtypes[$subtypeKey] = [
                    'type' => $type,
                    'subtype' => $subtype,
                    'count' => 0,
                    'amount' => 0.0,
                ];
            }
            $subtypes[$subtypeKey]['count']++;
            $subtypes[$subtypeKey]['amount'] += $line;

            $department = $this->paymentDepartmentLabel($payment);
            if (!isset($departments[$department['key']])) {
                $departments[$department['key']] = [
                    'code' => $department['key'],
                    'name' => $department['name'],
                    'count' => 0,
                    'students' => [],
                    'amount' => 0.0,
                ];
            }
            $departments[$department['key']]['count']++;
            $departments[$department['key']]['students'][(string) ($payment['student_id'] ?? '')] = true;
            $departments[$department['key']]['amount'] += $line;
        }
        ksort($types, SORT_NATURAL | SORT_FLAG_CASE);
        uasort($subtypes, function ($a, $b) {
            $type = strcasecmp($a['type'], $b['type']);
            if ($type !== 0) {
                return $type;
            }
            return strcasecmp($a['subtype'], $b['subtype']);
        });
        uasort($departments, function ($a, $b) {
            return $this->compareDepartmentCodes($a['code'] ?? '', $b['code'] ?? '', $a['name'] ?? '', $b['name'] ?? '');
        });
        foreach ($departments as $key => $row) {
            $departments[$key]['students'] = count($row['students']);
        }

        return [
            'count' => count($payments),
            'students' => count($students),
            'amount' => $total,
            'types' => $types,
            'subtypes' => $subtypes,
            'departments' => $departments,
        ];
    }

    private function paymentDepartmentLabel($payment) {
        $id = trim((string) ($payment['pays_department'] ?? ''));
        $name = trim((string) ($payment['pays_department_name'] ?? ''));
        if ($name === '') {
            $name = trim((string) ($payment['department_name'] ?? ''));
        }
        if ($name === '') {
            $name = $id !== '' ? $id : 'Unspecified';
        }
        $key = $id !== '' ? $id : $name;

        return ['key' => $key, 'name' => $name];
    }

    private function compareDepartmentCodes($codeA, $codeB, $nameA, $nameB) {
        $a = strcasecmp((string) $codeA, (string) $codeB);
        if ($a !== 0) {
            return $a;
        }
        return strcasecmp((string) $nameA, (string) $nameB);
    }

    private function comparePaymentsByDepartment($a, $b) {
        $departmentA = $this->paymentDepartmentLabel($a);
        $departmentB = $this->paymentDepartmentLabel($b);
        $department = $this->compareDepartmentCodes(
            $departmentA['key'],
            $departmentB['key'],
            $departmentA['name'],
            $departmentB['name']
        );
        if ($department !== 0) {
            return $department;
        }
        $date = strcmp((string) ($b['pays_date'] ?? ''), (string) ($a['pays_date'] ?? ''));
        if ($date !== 0) {
            return $date;
        }
        return ((int) ($b['pays_id'] ?? 0)) <=> ((int) ($a['pays_id'] ?? 0));
    }

    private function groupPaymentsByDepartment($payments) {
        $groups = [];
        foreach ($payments as $payment) {
            $department = $this->paymentDepartmentLabel($payment);
            $key = $department['key'];
            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'title' => $department['name'],
                    'code' => $department['key'],
                    'payments' => [],
                    'total' => 0.0,
                ];
            }
            $groups[$key]['payments'][] = $payment;
            $groups[$key]['total'] += $this->paymentLineTotal($payment);
        }
        uasort($groups, function ($a, $b) {
            return $this->compareDepartmentCodes($a['code'] ?? '', $b['code'] ?? '', $a['title'] ?? '', $b['title'] ?? '');
        });
        foreach ($groups as $key => $group) {
            usort($groups[$key]['payments'], [$this, 'comparePaymentsByDepartment']);
        }

        return $groups;
    }

    private function groupPaymentsByType($payments) {
        $groups = [];
        foreach ($payments as $payment) {
            $type = trim((string) ($payment['payment_type'] ?? ''));
            if ($type === '') {
                $type = 'Unspecified';
            }
            if (!isset($groups[$type])) {
                $groups[$type] = [
                    'title' => $type,
                    'payments' => [],
                    'total' => 0.0,
                ];
            }
            $groups[$type]['payments'][] = $payment;
            $groups[$type]['total'] += $this->paymentLineTotal($payment);
        }
        ksort($groups, SORT_NATURAL | SORT_FLAG_CASE);
        return $groups;
    }

    private function groupPaymentsBySubtype($payments) {
        $groups = [];
        foreach ($payments as $payment) {
            $type = trim((string) ($payment['payment_type'] ?? ''));
            if ($type === '') {
                $type = 'Unspecified';
            }
            $subtype = trim((string) ($payment['payment_reason'] ?? ''));
            if ($subtype === '') {
                $subtype = 'Unspecified';
            }
            $key = $type . "\0" . $subtype;
            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'title' => $subtype,
                    'type' => $type,
                    'payments' => [],
                    'total' => 0.0,
                ];
            }
            $groups[$key]['payments'][] = $payment;
            $groups[$key]['total'] += $this->paymentLineTotal($payment);
        }
        uasort($groups, function ($a, $b) {
            $type = strcasecmp((string) $a['type'], (string) $b['type']);
            if ($type !== 0) {
                return $type;
            }
            return strcasecmp((string) $a['title'], (string) $b['title']);
        });
        return $groups;
    }

    private function groupPaymentsByStudent($payments) {
        $groups = [];
        foreach ($payments as $payment) {
            $id = (string) ($payment['student_id'] ?? '');
            $department = $this->paymentDepartmentLabel($payment);
            if (!isset($groups[$id])) {
                $groups[$id] = [
                    'student_id' => $id,
                    'student_fullname' => $payment['student_fullname'] ?? '',
                    'student_reg_no' => $payment['student_reg_no'] ?? $id,
                    'course_name' => $payment['course_name'] ?? '',
                    'department_code' => $department['key'],
                    'department_name' => $department['name'],
                    'payments' => [],
                    'total' => 0.0,
                ];
            }
            $groups[$id]['payments'][] = $payment;
            $groups[$id]['total'] += $this->paymentLineTotal($payment);
        }

        uasort($groups, function ($a, $b) {
            $department = $this->compareDepartmentCodes(
                $a['department_code'] ?? '',
                $b['department_code'] ?? '',
                $a['department_name'] ?? '',
                $b['department_name'] ?? ''
            );
            if ($department !== 0) {
                return $department;
            }
            $name = strcasecmp((string) $a['student_fullname'], (string) $b['student_fullname']);
            if ($name !== 0) {
                return $name;
            }
            return strcmp((string) $a['student_id'], (string) $b['student_id']);
        });

        return $groups;
    }

    private function outputPaymentReportCsv($payments, $basis, $dateFrom, $dateTo) {
        $basisSlug = 'by-type';
        if ($basis === 'student') {
            $basisSlug = 'by-student';
        } elseif ($basis === 'subtype') {
            $basisSlug = 'by-subtype';
        } elseif ($basis === 'department') {
            $basisSlug = 'by-department';
        }
        $filename = 'payments-report-' . $basisSlug;
        if ($dateFrom !== '' || $dateTo !== '') {
            $filename .= '-' . ($dateFrom !== '' ? $dateFrom : 'start') . '-to-' . ($dateTo !== '' ? $dateTo : 'end');
        }
        $filename .= '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        $rows = $payments;
        if ($basis === 'student') {
            $studentGroups = $this->groupPaymentsByStudent($rows);
            fputcsv($output, ['Department', 'Student ID', 'Student Name', 'Course', 'Payments', 'Total Paid']);
            foreach ($studentGroups as $group) {
                $department = trim(($group['department_code'] ?? '') . ' ' . ($group['department_name'] ?? ''));
                fputcsv($output, [
                    $department,
                    $group['student_reg_no'] ?: $group['student_id'],
                    $group['student_fullname'],
                    $group['course_name'] ?? '',
                    count($group['payments']),
                    number_format((float) $group['total'], 2, '.', ''),
                ]);
            }
            fclose($output);
            exit();
        }

        fputcsv($output, [
            'Payment ID',
            'Date',
            'Student ID',
            'Student Name',
            'Course',
            'Department',
            'Payment Type',
            'Sub type',
            'Method',
            'Qty',
            'Unit Amount',
            'Total',
            'Reference',
        ]);

        if ($basis === 'department') {
            usort($rows, [$this, 'comparePaymentsByDepartment']);
        } elseif ($basis === 'subtype') {
            usort($rows, function ($a, $b) {
                $type = strcasecmp((string) ($a['payment_type'] ?? ''), (string) ($b['payment_type'] ?? ''));
                if ($type !== 0) {
                    return $type;
                }
                $subtype = strcasecmp((string) ($a['payment_reason'] ?? ''), (string) ($b['payment_reason'] ?? ''));
                if ($subtype !== 0) {
                    return $subtype;
                }
                return strcmp((string) ($b['pays_date'] ?? ''), (string) ($a['pays_date'] ?? ''));
            });
        } else {
            usort($rows, function ($a, $b) {
                $type = strcasecmp((string) ($a['payment_type'] ?? ''), (string) ($b['payment_type'] ?? ''));
                if ($type !== 0) {
                    return $type;
                }
                return strcmp((string) ($b['pays_date'] ?? ''), (string) ($a['pays_date'] ?? ''));
            });
        }

        foreach ($rows as $payment) {
            $qty = max(1, (int) ($payment['pays_qty'] ?? 1));
            fputcsv($output, [
                $payment['pays_id'],
                !empty($payment['pays_date']) ? date('Y-m-d', strtotime($payment['pays_date'])) : '',
                $payment['student_reg_no'] ?? $payment['student_id'] ?? '',
                $payment['student_fullname'] ?? '',
                $payment['course_name'] ?? '',
                $payment['pays_department_name'] ?? $payment['department_name'] ?? '',
                $payment['payment_type'] ?? '',
                $payment['payment_reason'] ?? '',
                $payment['payment_method'] ?? '',
                $qty,
                number_format((float) ($payment['pays_amount'] ?? 0), 2, '.', ''),
                number_format($this->paymentLineTotal($payment), 2, '.', ''),
                $payment['reference_no'] ?? '',
            ]);
        }

        fclose($output);
        exit();
    }
}

