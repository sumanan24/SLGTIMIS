<?php
/**
 * Payment Type / Reason Controller
 */

class PaymentTypeController extends Controller {

    public function index() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }

        if (!$this->checkFinanceAccess()) {
            return;
        }

        $paymentTypeModel = $this->model('PaymentTypeModel');
        $entries = $paymentTypeModel->getAll();

        $data = [
            'title' => 'Payment Types & Reasons',
            'page' => 'payment-types',
            'entries' => $entries,
            'message' => $_SESSION['message'] ?? null,
            'error' => $_SESSION['error'] ?? null,
        ];

        unset($_SESSION['message'], $_SESSION['error']);

        return $this->view('payment-types/index', $data);
    }

    public function create() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }

        if (!$this->checkFinanceAccess()) {
            return;
        }

        $paymentTypeModel = $this->model('PaymentTypeModel');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $paymentType = trim($this->post('payment_type', ''));
            $paymentReason = trim($this->post('payment_reason', ''));

            if ($paymentType === '' || $paymentReason === '') {
                $_SESSION['error'] = 'Payment type and reason are required.';
                $this->redirect('payment-types/create');
                return;
            }

            if (strlen($paymentType) > 100) {
                $_SESSION['error'] = 'Payment type must be 100 characters or fewer.';
                $this->redirect('payment-types/create');
                return;
            }

            if (strlen($paymentReason) > 50) {
                $_SESSION['error'] = 'Payment reason must be 50 characters or fewer.';
                $this->redirect('payment-types/create');
                return;
            }

            if ($paymentTypeModel->exists($paymentReason, $paymentType)) {
                $_SESSION['error'] = 'This payment type and reason combination already exists.';
                $this->redirect('payment-types/create');
                return;
            }

            $sqlError = null;
            $result = $paymentTypeModel->createEntry($paymentReason, $paymentType, $sqlError);

            if ($result !== false) {
                $_SESSION['message'] = 'Payment type and reason added successfully.';
                $this->redirect('payment-types');
            } else {
                $_SESSION['error'] = $sqlError ? 'Failed to add entry: ' . $sqlError : 'Failed to add payment type and reason.';
                $this->redirect('payment-types/create');
            }
        } else {
            $data = [
                'title' => 'Add Payment Type & Reason',
                'page' => 'payment-types',
                'paymentTypes' => $paymentTypeModel->getDistinctTypes(),
                'error' => $_SESSION['error'] ?? null,
            ];
            unset($_SESSION['error']);

            return $this->view('payment-types/create', $data);
        }
    }

    public function edit() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }

        if (!$this->checkFinanceAccess()) {
            return;
        }

        $oldReason = trim($this->get('reason', ''));
        $oldType = trim($this->get('type', ''));

        if ($oldReason === '' || $oldType === '') {
            $_SESSION['error'] = 'Payment type and reason are required.';
            $this->redirect('payment-types');
            return;
        }

        $paymentTypeModel = $this->model('PaymentTypeModel');
        $entry = $paymentTypeModel->findByKey($oldReason, $oldType);

        if (!$entry) {
            $_SESSION['error'] = 'Payment type and reason entry not found.';
            $this->redirect('payment-types');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $paymentType = trim($this->post('payment_type', ''));
            $paymentReason = trim($this->post('payment_reason', ''));

            if ($paymentType === '' || $paymentReason === '') {
                $_SESSION['error'] = 'Payment type and reason are required.';
                $this->redirect('payment-types/edit?' . http_build_query(['reason' => $oldReason, 'type' => $oldType]));
                return;
            }

            if (strlen($paymentType) > 100 || strlen($paymentReason) > 50) {
                $_SESSION['error'] = 'Payment type (max 100) or reason (max 50) is too long.';
                $this->redirect('payment-types/edit?' . http_build_query(['reason' => $oldReason, 'type' => $oldType]));
                return;
            }

            if ($paymentReason !== $oldReason || $paymentType !== $oldType) {
                if ($paymentTypeModel->exists($paymentReason, $paymentType)) {
                    $_SESSION['error'] = 'This payment type and reason combination already exists.';
                    $this->redirect('payment-types/edit?' . http_build_query(['reason' => $oldReason, 'type' => $oldType]));
                    return;
                }
            }

            $sqlError = null;
            $result = $paymentTypeModel->updateEntry($oldReason, $oldType, $paymentReason, $paymentType, $sqlError);

            if ($result) {
                $_SESSION['message'] = 'Payment type and reason updated successfully.';
                $this->redirect('payment-types');
            } else {
                $_SESSION['error'] = $sqlError ? 'Failed to update entry: ' . $sqlError : 'Failed to update payment type and reason.';
                $this->redirect('payment-types/edit?' . http_build_query(['reason' => $oldReason, 'type' => $oldType]));
            }
        } else {
            $data = [
                'title' => 'Edit Payment Type & Reason',
                'page' => 'payment-types',
                'entry' => $entry,
                'paymentTypes' => $paymentTypeModel->getDistinctTypes(),
                'error' => $_SESSION['error'] ?? null,
            ];
            unset($_SESSION['error']);

            return $this->view('payment-types/edit', $data);
        }
    }

    public function delete() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }

        if (!$this->checkFinanceAccess()) {
            return;
        }

        $reason = trim($this->get('reason', ''));
        $type = trim($this->get('type', ''));

        if ($reason === '' || $type === '') {
            $_SESSION['error'] = 'Payment type and reason are required.';
            $this->redirect('payment-types');
            return;
        }

        $paymentTypeModel = $this->model('PaymentTypeModel');
        $entry = $paymentTypeModel->findByKey($reason, $type);

        if (!$entry) {
            $_SESSION['error'] = 'Payment type and reason entry not found.';
            $this->redirect('payment-types');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($paymentTypeModel->isUsed($reason, $type)) {
                $_SESSION['error'] = 'Cannot delete. This entry is used in existing payment records.';
                $this->redirect('payment-types');
                return;
            }

            if ($paymentTypeModel->deleteEntry($reason, $type)) {
                $_SESSION['message'] = 'Payment type and reason deleted successfully.';
            } else {
                $_SESSION['error'] = 'Failed to delete payment type and reason.';
            }

            $this->redirect('payment-types');
        } else {
            $data = [
                'title' => 'Delete Payment Type & Reason',
                'page' => 'payment-types',
                'entry' => $entry,
                'isUsed' => $paymentTypeModel->isUsed($reason, $type),
            ];

            return $this->view('payment-types/delete', $data);
        }
    }
}
