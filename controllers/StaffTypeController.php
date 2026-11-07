<?php
/**
 * Staff Type Controller (Permanent, On Contract, Visiting Lecturer, ...)
 * Uses the same access rules as Staff Roles.
 */

class StaffTypeController extends Controller {

    private function csrfToken() {
        if (empty($_SESSION['stype_csrf'])) {
            $_SESSION['stype_csrf'] = bin2hex(random_bytes(16));
        }
        return $_SESSION['stype_csrf'];
    }

    private function validCsrf() {
        $sent = (string) $this->post('csrf', '');
        return $sent !== '' && !empty($_SESSION['stype_csrf']) && hash_equals($_SESSION['stype_csrf'], $sent);
    }

    public function index() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        if (!$this->requireModule('staff_roles', 'view')) {
            return;
        }

        require_once BASE_PATH . '/core/AccessControl.php';
        $uid = (int) $_SESSION['user_id'];
        $typeModel = $this->model('StaffTypeModel');

        $data = [
            'title' => 'Staff Types',
            'page' => 'staff-types',
            'types' => $typeModel->getAllWithUsage(),
            'canAdd' => AccessControl::can($uid, 'staff_roles', 'add'),
            'canEdit' => AccessControl::can($uid, 'staff_roles', 'edit'),
            'canDelete' => AccessControl::can($uid, 'staff_roles', 'delete'),
            'csrf' => $this->csrfToken(),
            'message' => $_SESSION['message'] ?? null,
            'error' => $_SESSION['error'] ?? null,
        ];
        unset($_SESSION['message'], $_SESSION['error']);
        return $this->view('staff-types/index', $data);
    }

    /** Create (no id) or update (id) a staff type. */
    public function save() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('staff-types');
            return;
        }

        $id = (int) $this->post('staff_type_id', 0);
        if (!$this->requireModule('staff_roles', $id > 0 ? 'edit' : 'add')) {
            return;
        }
        if (!$this->validCsrf()) {
            $_SESSION['error'] = 'Your session expired. Please try again.';
            $this->redirect('staff-types');
            return;
        }

        $name = trim(preg_replace('/\s+/', ' ', (string) $this->post('staff_type_name', '')));
        $description = trim((string) $this->post('staff_type_description', ''));
        $active = $this->post('is_active', '') === '1';

        if ($name === '') {
            $_SESSION['error'] = 'Staff type name is required.';
            $this->redirect('staff-types');
            return;
        }
        if (mb_strlen($name) > 50) {
            $_SESSION['error'] = 'Staff type name can be at most 50 characters.';
            $this->redirect('staff-types');
            return;
        }
        if (mb_strlen($description) > 255) {
            $description = mb_substr($description, 0, 255);
        }

        $typeModel = $this->model('StaffTypeModel');
        if ($typeModel->nameExists($name, $id)) {
            $_SESSION['error'] = 'A staff type named "' . $name . '" already exists.';
            $this->redirect('staff-types');
            return;
        }

        $error = null;
        if ($id > 0) {
            $before = $typeModel->find($id);
            if (!$before) {
                $_SESSION['error'] = 'Staff type not found.';
                $this->redirect('staff-types');
                return;
            }
            if ($typeModel->updateType($id, $name, $description, $active, $error)) {
                $this->logActivity('UPDATE', 'staff-types', (string) $id,
                    'Staff type updated: ' . $before['staff_type_name'] . ' -> ' . $name, $before,
                    ['staff_type_name' => $name, 'is_active' => $active ? 1 : 0]);
                $_SESSION['message'] = 'Staff type updated.'
                    . ((int) $before['is_system'] === 1 && $before['staff_type_name'] !== $name
                        ? ' (The ' . $before['staff_type_name'] . ' name is fixed by the system.)' : '');
            } else {
                $_SESSION['error'] = 'Failed to update staff type.' . ($error ? ' ' . $error : '');
            }
        } else {
            $newId = $typeModel->createType($name, $description, $active, $error);
            if ($newId !== false) {
                $this->logActivity('CREATE', 'staff-types', (string) $newId, 'Staff type created: ' . $name, null,
                    ['staff_type_name' => $name, 'is_active' => $active ? 1 : 0]);
                $_SESSION['message'] = 'Staff type "' . $name . '" created.';
            } else {
                $_SESSION['error'] = 'Failed to create staff type.' . ($error ? ' ' . $error : '');
            }
        }
        $this->redirect('staff-types');
    }

    public function delete() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        if (!$this->requireModule('staff_roles', 'delete')) {
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('staff-types');
            return;
        }
        if (!$this->validCsrf()) {
            $_SESSION['error'] = 'Your session expired. Please try again.';
            $this->redirect('staff-types');
            return;
        }

        $id = (int) $this->post('staff_type_id', 0);
        $typeModel = $this->model('StaffTypeModel');
        $before = $id > 0 ? $typeModel->find($id) : null;
        if (!$before) {
            $_SESSION['error'] = 'Staff type not found.';
            $this->redirect('staff-types');
            return;
        }

        $error = null;
        if ($typeModel->deleteType($id, $error)) {
            $this->logActivity('DELETE', 'staff-types', (string) $id, 'Staff type deleted: ' . $before['staff_type_name'], $before, null);
            $_SESSION['message'] = 'Staff type "' . $before['staff_type_name'] . '" deleted.';
        } else {
            $_SESSION['error'] = $error ?: 'Failed to delete staff type.';
        }
        $this->redirect('staff-types');
    }
}
