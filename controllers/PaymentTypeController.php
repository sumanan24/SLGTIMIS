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
        $paymentTypeModel->ensureMainTypeTable();

        $data = [
            'title' => 'Payment Types',
            'page' => 'payment-types',
            'mainTypes' => $paymentTypeModel->getMainTypes(),
            'entries' => $paymentTypeModel->getAll(),
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
        $paymentTypeModel->ensureMainTypeTable();
        $kind = $this->get('kind', '') === 'main' ? 'main' : 'sub';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $kind = $this->post('kind', $kind) === 'main' ? 'main' : 'sub';
            $paymentType = trim($this->post('payment_type', ''));
            $back = 'payment-types/create?kind=' . $kind;

            if ($kind === 'main') {
                if ($paymentType === '') {
                    $_SESSION['error'] = 'Main type name is required.';
                    $this->redirect($back);
                    return;
                }
                if (strlen($paymentType) > 100) {
                    $_SESSION['error'] = 'Main type must be 100 characters or fewer.';
                    $this->redirect($back);
                    return;
                }
                if ($paymentTypeModel->mainTypeExists($paymentType)) {
                    $_SESSION['error'] = 'This main type already exists.';
                    $this->redirect($back);
                    return;
                }

                $sqlError = null;
                if ($paymentTypeModel->createMainType($paymentType, $sqlError)) {
                    $_SESSION['message'] = 'Main type added.';
                    $this->redirect('payment-types');
                } else {
                    $_SESSION['error'] = $sqlError ? 'Failed to add main type: ' . $sqlError : 'Failed to add main type.';
                    $this->redirect($back);
                }
                return;
            }

            $paymentReason = trim($this->post('payment_reason', ''));
            if ($paymentType === '' || $paymentReason === '') {
                $_SESSION['error'] = 'Choose a main type and enter a sub type.';
                $this->redirect($back);
                return;
            }
            if (strlen($paymentType) > 100 || strlen($paymentReason) > 50) {
                $_SESSION['error'] = 'Main type (max 100) or sub type (max 50) is too long.';
                $this->redirect($back);
                return;
            }
            if (!$paymentTypeModel->mainTypeExists($paymentType)) {
                $_SESSION['error'] = 'Add the main type before adding a sub type under it.';
                $this->redirect($back);
                return;
            }
            if ($paymentTypeModel->exists($paymentReason, $paymentType)) {
                $_SESSION['error'] = 'This sub type already exists under that main type.';
                $this->redirect($back);
                return;
            }

            $sqlError = null;
            $result = $paymentTypeModel->createEntry($paymentReason, $paymentType, $sqlError);
            if ($result !== false) {
                $_SESSION['message'] = 'Sub type added.';
                $this->redirect('payment-types');
            } else {
                $_SESSION['error'] = $sqlError ? 'Failed to add sub type: ' . $sqlError : 'Failed to add sub type.';
                $this->redirect($back);
            }
        } else {
            $data = [
                'title' => $kind === 'main' ? 'Add Main Type' : 'Add Sub Type',
                'page' => 'payment-types',
                'kind' => $kind,
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

        $kind = $this->get('kind', '') === 'main' ? 'main' : 'sub';
        $oldReason = trim($this->get('reason', ''));
        $oldType = trim($this->get('type', ''));
        $paymentTypeModel = $this->model('PaymentTypeModel');
        $paymentTypeModel->ensureMainTypeTable();

        if ($kind === 'main') {
            if ($oldType === '' || !$paymentTypeModel->mainTypeExists($oldType)) {
                $_SESSION['error'] = 'Main type not found.';
                $this->redirect('payment-types');
                return;
            }

            $back = 'payment-types/edit?' . http_build_query(['kind' => 'main', 'type' => $oldType]);
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $paymentType = trim($this->post('payment_type', ''));
                if ($paymentType === '') {
                    $_SESSION['error'] = 'Main type name is required.';
                    $this->redirect($back);
                    return;
                }
                if (strlen($paymentType) > 100) {
                    $_SESSION['error'] = 'Main type must be 100 characters or fewer.';
                    $this->redirect($back);
                    return;
                }
                if ($paymentType !== $oldType && $paymentTypeModel->mainTypeExists($paymentType)) {
                    $_SESSION['error'] = 'This main type already exists.';
                    $this->redirect($back);
                    return;
                }

                if ($paymentType === $oldType) {
                    $_SESSION['message'] = 'Main type saved.';
                    $this->redirect('payment-types');
                    return;
                }

                $sqlError = null;
                if ($paymentTypeModel->renameMainType($oldType, $paymentType, $sqlError)) {
                    $_SESSION['message'] = 'Main type updated. Existing payments now use the new name.';
                    $this->redirect('payment-types');
                } else {
                    $_SESSION['error'] = $sqlError ? 'Failed to update main type: ' . $sqlError : 'Failed to update main type.';
                    $this->redirect($back);
                }
                return;
            }

            $data = [
                'title' => 'Edit Main Type',
                'page' => 'payment-types',
                'kind' => 'main',
                'entry' => ['payment_type' => $oldType, 'payment_reason' => ''],
                'paymentTypes' => $paymentTypeModel->getDistinctTypes(),
                'error' => $_SESSION['error'] ?? null,
            ];
            unset($_SESSION['error']);
            return $this->view('payment-types/edit', $data);
        }

        if ($oldReason === '' || $oldType === '') {
            $_SESSION['error'] = 'Sub type and main type are required.';
            $this->redirect('payment-types');
            return;
        }

        $entry = $paymentTypeModel->findByKey($oldReason, $oldType);
        if (!$entry) {
            $_SESSION['error'] = 'Sub type not found.';
            $this->redirect('payment-types');
            return;
        }

        $back = 'payment-types/edit?' . http_build_query(['kind' => 'sub', 'reason' => $oldReason, 'type' => $oldType]);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $paymentType = trim($this->post('payment_type', ''));
            $paymentReason = trim($this->post('payment_reason', ''));

            if ($paymentType === '' || $paymentReason === '') {
                $_SESSION['error'] = 'Main type and sub type are required.';
                $this->redirect($back);
                return;
            }
            if (strlen($paymentType) > 100 || strlen($paymentReason) > 50) {
                $_SESSION['error'] = 'Main type (max 100) or sub type (max 50) is too long.';
                $this->redirect($back);
                return;
            }
            if (!$paymentTypeModel->mainTypeExists($paymentType)) {
                $_SESSION['error'] = 'Choose an existing main type.';
                $this->redirect($back);
                return;
            }
            if (($paymentReason !== $oldReason || $paymentType !== $oldType) && $paymentTypeModel->exists($paymentReason, $paymentType)) {
                $_SESSION['error'] = 'This sub type already exists under that main type.';
                $this->redirect($back);
                return;
            }

            if ($paymentReason === $oldReason && $paymentType === $oldType) {
                $_SESSION['message'] = 'Sub type saved.';
                $this->redirect('payment-types');
                return;
            }

            $sqlError = null;
            if ($paymentTypeModel->updateEntry($oldReason, $oldType, $paymentReason, $paymentType, $sqlError)) {
                $_SESSION['message'] = 'Sub type updated. Existing payments now use the new name.';
                $this->redirect('payment-types');
            } else {
                $_SESSION['error'] = $sqlError ? 'Failed to update sub type: ' . $sqlError : 'Failed to update sub type.';
                $this->redirect($back);
            }
            return;
        }

        $data = [
            'title' => 'Edit Sub Type',
            'page' => 'payment-types',
            'kind' => 'sub',
            'entry' => $entry,
            'paymentTypes' => $paymentTypeModel->getDistinctTypes(),
            'error' => $_SESSION['error'] ?? null,
        ];
        unset($_SESSION['error']);
        return $this->view('payment-types/edit', $data);
    }

    public function delete() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }

        if (!$this->checkFinanceAccess()) {
            return;
        }

        $kind = $this->get('kind', '') === 'main' ? 'main' : 'sub';
        $reason = trim($this->get('reason', ''));
        $type = trim($this->get('type', ''));
        $paymentTypeModel = $this->model('PaymentTypeModel');
        $paymentTypeModel->ensureMainTypeTable();

        if ($kind === 'main') {
            if ($type === '' || !$paymentTypeModel->mainTypeExists($type)) {
                $_SESSION['error'] = 'Main type not found.';
                $this->redirect('payment-types');
                return;
            }

            $subtypeCount = $paymentTypeModel->subtypeCount($type);
            $isUsed = $paymentTypeModel->mainTypeIsUsed($type) || $subtypeCount > 0;
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if ($isUsed) {
                    $_SESSION['error'] = $subtypeCount > 0
                        ? 'Remove the sub types under this main type before deleting it.'
                        : 'Cannot delete. Payments already use this main type.';
                    $this->redirect('payment-types');
                    return;
                }
                if ($paymentTypeModel->deleteMainType($type)) {
                    $_SESSION['message'] = 'Main type deleted.';
                } else {
                    $_SESSION['error'] = 'Failed to delete main type.';
                }
                $this->redirect('payment-types');
                return;
            }

            $data = [
                'title' => 'Delete Main Type',
                'page' => 'payment-types',
                'kind' => 'main',
                'entry' => ['payment_type' => $type, 'payment_reason' => ''],
                'isUsed' => $isUsed,
                'subtypeCount' => $subtypeCount,
            ];
            return $this->view('payment-types/delete', $data);
        }

        if ($reason === '' || $type === '') {
            $_SESSION['error'] = 'Sub type and main type are required.';
            $this->redirect('payment-types');
            return;
        }

        $entry = $paymentTypeModel->findByKey($reason, $type);
        if (!$entry) {
            $_SESSION['error'] = 'Sub type not found.';
            $this->redirect('payment-types');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($paymentTypeModel->isUsed($reason, $type)) {
                $_SESSION['error'] = 'Cannot delete. This sub type is used in existing payments.';
                $this->redirect('payment-types');
                return;
            }

            if ($paymentTypeModel->deleteEntry($reason, $type)) {
                $_SESSION['message'] = 'Sub type deleted.';
            } else {
                $_SESSION['error'] = 'Failed to delete sub type.';
            }
            $this->redirect('payment-types');
            return;
        }

        $data = [
            'title' => 'Delete Sub Type',
            'page' => 'payment-types',
            'kind' => 'sub',
            'entry' => $entry,
            'isUsed' => $paymentTypeModel->isUsed($reason, $type),
            'subtypeCount' => 0,
        ];
        return $this->view('payment-types/delete', $data);
    }
}
