<?php
/**
 * Inventory Controller
 *
 * Rules:
 * - Any logged-in staff user can create inventory and inventory items.
 * - HOD and other staff can only see/manage inventory for their own department.
 * - Admin / ADM can see and manage all departments.
 */

class InventoryController extends Controller {

    /**
     * List inventory records
     */
    public function index() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }

        // Determine department filter based on current user
        $departmentId = $this->getCurrentUserDepartmentForInventory();

        $inventoryModel = $this->model('InventoryModel');
        $items = $inventoryModel->getInventories($departmentId);

        $data = [
            'title' => 'Inventory',
            'page' => 'inventory',
            'inventory' => $items,
            'departmentId' => $departmentId,
            'message' => $_SESSION['message'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ];

        unset($_SESSION['message'], $_SESSION['error']);
        return $this->view('inventory/index', $data);
    }

    /**
     * Create inventory record
     * Any staff user can create, but inventory is always tied to their own department.
     */
    public function create() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }

        // Only staff or admin/ADM accounts should access this
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $isAdminOrADM = $userModel->isAdminOrADM($_SESSION['user_id']);
        $userTable = $_SESSION['user_table'] ?? 'student';

        if (!$isAdminOrADM && $userTable !== 'staff') {
            $_SESSION['error'] = 'Access denied. Only staff can manage inventory.';
            $this->redirect('dashboard');
            return;
        }

        $departmentId = $this->getCurrentUserDepartmentForInventory();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $inventoryModel = $this->model('InventoryModel');
            $itemModel = $this->model('InventoryItemModel');

            $itemId = trim($this->post('item_id', ''));
            $status = trim($this->post('inventory_status', ''));
            $quantity = trim($this->post('inventory_quantity', ''));

            // Validation
            if (empty($departmentId)) {
                $_SESSION['error'] = 'Your department could not be determined. Contact administrator.';
                $this->redirect('inventory/create');
                return;
            }

            if (empty($itemId) || empty($status) || empty($quantity)) {
                $_SESSION['error'] = 'Item, Status, and Quantity are required.';
                $this->redirect('inventory/create');
                return;
            }

            if (!$itemModel->exists($itemId)) {
                $_SESSION['error'] = 'Selected item does not exist.';
                $this->redirect('inventory/create');
                return;
            }

            $data = [
                'inventory_department_id' => $departmentId,
                'item_id' => $itemId,
                'inventory_status' => $status,
                'inventory_quantity' => $quantity
            ];

            $sqlError = null;
            $result = $inventoryModel->createInventory($data, $sqlError);

            if ($result !== false) {
                $_SESSION['message'] = 'Inventory entry created successfully for your department.';
                $this->redirect('inventory');
            } else {
                if ($sqlError) {
                    error_log("InventoryController::create - SQL error: " . $sqlError);
                    $_SESSION['error'] = 'Failed to create inventory. Database error: ' . $sqlError;
                } else {
                    $_SESSION['error'] = 'Failed to create inventory.';
                }
                $this->redirect('inventory/create');
            }
        } else {
            $itemModel = $this->model('InventoryItemModel');
            $items = $itemModel->getAllItems();

            $data = [
                'title' => 'Create Inventory',
                'page' => 'inventory',
                'departmentId' => $departmentId,
                'items' => $items,
                'error' => $_SESSION['error'] ?? null
            ];
            unset($_SESSION['error']);
            return $this->view('inventory/create', $data);
        }
    }

    /**
     * Delete inventory record (HOD/staff can only delete their own department's entries)
     */
    public function delete() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }

        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $isAdminOrADM = $userModel->isAdminOrADM($_SESSION['user_id']);

        $inventoryModel = $this->model('InventoryModel');
        $id = (int)$this->get('id', 0);

        if ($id <= 0) {
            $_SESSION['error'] = 'Invalid inventory ID.';
            $this->redirect('inventory');
            return;
        }

        $record = $inventoryModel->getById($id);
        if (!$record) {
            $_SESSION['error'] = 'Inventory record not found.';
            $this->redirect('inventory');
            return;
        }

        // Department-based permission: non-admins can only manage their own department
        if (!$isAdminOrADM) {
            $userDept = $this->getCurrentUserDepartmentForInventory();
            if (empty($userDept) || $record['inventory_department_id'] !== $userDept) {
                $_SESSION['error'] = 'Access denied. You can only manage inventory for your own department.';
                $this->redirect('inventory');
                return;
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $inventoryModel->deleteInventory($id);
            if ($result) {
                $_SESSION['message'] = 'Inventory record deleted successfully.';
            } else {
                $_SESSION['error'] = 'Failed to delete inventory record.';
            }
            $this->redirect('inventory');
        } else {
            $data = [
                'title' => 'Delete Inventory',
                'page' => 'inventory',
                'inventory' => $record
            ];
            return $this->view('inventory/delete', $data);
        }
    }

    /**
     * Determine department for current user for inventory access.
     * - Admin/ADM: null (all departments)
     * - Staff (including HOD/IN roles): their staff.department_id
     * - Others: null
     */
    private function getCurrentUserDepartmentForInventory() {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }

        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();

        // Admin / ADM: can see all departments
        if ($userModel->isAdminOrADM($_SESSION['user_id'])) {
            return null;
        }

        // Staff users: department comes from staff table (staff_id == user_name)
        $userTable = $_SESSION['user_table'] ?? 'student';
        if ($userTable === 'staff') {
            $user = $userModel->find($_SESSION['user_id']);
            if ($user && isset($user['user_name'])) {
                $staffModel = $this->model('StaffModel');
                $staff = $staffModel->find($user['user_name']);
                if ($staff && isset($staff['department_id'])) {
                    return $staff['department_id'];
                }
            }
        }

        // Fallback: try existing department logic (for HOD/IN roles, etc.)
        return $this->getUserDepartment();
    }
}

