<?php
/**
 * Inventory management: items, stock in/out, transfer, log
 */
class InventoryController extends Controller {

    private function requireInventoryAccess() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return false;
        }
        if (isset($_SESSION['user_table']) && $_SESSION['user_table'] === 'student') {
            $_SESSION['error'] = 'Access denied.';
            $this->redirect('dashboard');
            return false;
        }
        return true;
    }

    /**
     * null = system admin only (user_table admin / user_name admin): all departments, optional filter
     * string = single department id (staff-linked users: own department only)
     * false = no department resolved (no access to data)
     *
     * Note: ADM and other roles are not treated as "see all" here — only true system admins are.
     * Department comes from staff.department_id where user.user_name = staff.staff_id.
     */
    private function getInventoryDepartmentScope() {
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        if ($userModel->isAdmin($_SESSION['user_id'])) {
            return null;
        }
        $dept = null;
        $user = $userModel->find($_SESSION['user_id']);
        if ($user && !empty($user['user_name'])) {
            $staffModel = $this->model('StaffModel');
            $staff = $staffModel->find($user['user_name']);
            if ($staff && isset($staff['department_id']) && $staff['department_id'] !== '' && $staff['department_id'] !== null) {
                $dept = $staff['department_id'];
            }
        }
        if ($dept === null) {
            $dept = $this->getUserDepartment();
        }
        if ($dept === null || $dept === '') {
            return false;
        }
        return $dept;
    }

    private function ensureTables() {
        $inv = $this->model('InventoryModel');
        if (!$inv->tableExists()) {
            $_SESSION['error'] = 'Inventory tables are not installed. Run sql/inventory_management.sql on the database.';
            $this->redirect('dashboard');
            return false;
        }
        return true;
    }

    public function dashboard() {
        if (!$this->requireInventoryAccess() || !$this->ensureTables()) {
            return;
        }
        $scope = $this->getInventoryDepartmentScope();
        $inventoryModel = $this->model('InventoryModel');
        $logModel = $this->model('InventoryLogModel');

        if ($scope === false) {
            $totalItems = 0;
            $lowStock = [];
            $recent = [];
        } elseif ($scope === null) {
            $totalItems = $inventoryModel->countItems(null);
            $lowStock = $inventoryModel->getLowStockItems(null, 25);
            $recent = $logModel->tableExists() ? $logModel->getRecent(15, null) : [];
        } else {
            $totalItems = $inventoryModel->countItems($scope);
            $lowStock = $inventoryModel->getLowStockItems($scope, 25);
            $recent = $logModel->tableExists() ? $logModel->getRecent(15, $scope) : [];
        }

        $data = [
            'title' => 'Inventory',
            'page' => 'inventory',
            'inventorySection' => 'dashboard',
            'totalItems' => $totalItems,
            'lowStock' => $lowStock,
            'recentActivity' => $recent,
            'departmentScope' => is_string($scope) ? $scope : null,
            'scopeNoDepartment' => ($scope === false),
            'message' => $_SESSION['message'] ?? null,
            'error' => $_SESSION['error'] ?? null,
        ];
        unset($_SESSION['message'], $_SESSION['error']);
        return $this->view('inventory/dashboard', $data);
    }

    public function itemsIndex() {
        if (!$this->requireInventoryAccess() || !$this->ensureTables()) {
            return;
        }
        $scope = $this->getInventoryDepartmentScope();
        $inventoryModel = $this->model('InventoryModel');
        $deptModel = $this->model('DepartmentModel');

        $search = trim($this->get('q', ''));
        $statusFilter = trim($this->get('status', ''));

        if ($scope === false) {
            $items = [];
            $deptFilter = '';
        } elseif ($scope === null) {
            $deptFilter = trim($this->get('department_id', ''));
            $items = $inventoryModel->getItems(
                $deptFilter !== '' ? $deptFilter : null,
                $search,
                $statusFilter
            );
        } else {
            $deptFilter = $scope;
            $items = $inventoryModel->getItems($scope, $search, $statusFilter);
        }

        $departments = $deptModel->getAll();

        $data = [
            'title' => 'Inventory Items',
            'page' => 'inventory',
            'inventorySection' => 'items',
            'items' => $items,
            'departments' => $departments,
            'search' => $search,
            'departmentFilter' => $deptFilter,
            'statusFilter' => $statusFilter,
            'departmentScope' => is_string($scope) ? $scope : null,
            'scopeNoDepartment' => ($scope === false),
            'canViewAllDepartments' => ($scope === null),
            'message' => $_SESSION['message'] ?? null,
            'error' => $_SESSION['error'] ?? null,
        ];
        unset($_SESSION['message'], $_SESSION['error']);
        return $this->view('inventory/items/index', $data);
    }

    public function itemsCreate() {
        if (!$this->requireInventoryAccess() || !$this->ensureTables()) {
            return;
        }
        $scope = $this->getInventoryDepartmentScope();
        if ($scope === false) {
            $_SESSION['error'] = 'Your account has no department assigned. Contact administrator.';
            $this->redirect('inventory/items');
            return;
        }
        $inventoryModel = $this->model('InventoryModel');
        $deptModel = $this->model('DepartmentModel');
        $departments = $deptModel->getAll();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $itemName = trim($this->post('item_name', ''));
            $itemCode = trim($this->post('item_code', ''));
            $departmentId = trim($this->post('department_id', ''));
            $category = trim($this->post('category', ''));
            $unit = trim($this->post('unit', ''));
            $quantity = (int)$this->post('quantity', 0);
            $reorderLevel = (int)$this->post('reorder_level', 5);
            $status = trim($this->post('status', 'active'));

            if ($itemName === '' || $itemCode === '') {
                $_SESSION['error'] = 'Item name and item code are required.';
                $this->redirect('inventory/items/create');
                return;
            }
            if (is_string($scope)) {
                $departmentId = $scope;
            }
            if ($departmentId === '') {
                $_SESSION['error'] = 'Department is required.';
                $this->redirect('inventory/items/create');
                return;
            }
            if (!in_array($status, ['active', 'inactive'], true)) {
                $status = 'active';
            }

            $data = [
                'item_name' => $itemName,
                'item_code' => $itemCode,
                'department_id' => $departmentId,
                'category' => $category,
                'unit' => $unit,
                'quantity' => max(0, $quantity),
                'reorder_level' => max(0, $reorderLevel),
                'status' => $status,
            ];

            $sqlError = null;
            $ok = $inventoryModel->createItem($data, $sqlError);
            if ($ok !== false) {
                $_SESSION['message'] = 'Item created successfully.';
                $this->redirect('inventory/items');
                return;
            }
            $_SESSION['error'] = 'Could not create item: ' . ($sqlError ?: 'database error');
            $this->redirect('inventory/items/create');
            return;
        }

        $data = [
            'title' => 'Add Item',
            'page' => 'inventory',
            'inventorySection' => 'items',
            'departments' => $departments,
            'departmentScope' => is_string($scope) ? $scope : null,
            'error' => $_SESSION['error'] ?? null,
        ];
        unset($_SESSION['error']);
        return $this->view('inventory/items/create', $data);
    }

    public function itemsEdit() {
        if (!$this->requireInventoryAccess() || !$this->ensureTables()) {
            return;
        }
        $scope = $this->getInventoryDepartmentScope();
        if ($scope === false) {
            $_SESSION['error'] = 'Your account has no department assigned.';
            $this->redirect('inventory/items');
            return;
        }
        $id = (int)$this->get('id', 0);
        if ($id <= 0) {
            $_SESSION['error'] = 'Invalid item.';
            $this->redirect('inventory/items');
            return;
        }

        $inventoryModel = $this->model('InventoryModel');
        $row = $inventoryModel->find($id);
        if (!$row) {
            $_SESSION['error'] = 'Item not found.';
            $this->redirect('inventory/items');
            return;
        }
        if (is_string($scope) && ($row['department_id'] ?? '') !== $scope) {
            $_SESSION['error'] = 'Access denied.';
            $this->redirect('inventory/items');
            return;
        }

        $deptModel = $this->model('DepartmentModel');
        $departments = $deptModel->getAll();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $itemName = trim($this->post('item_name', ''));
            $itemCode = trim($this->post('item_code', ''));
            $departmentId = trim($this->post('department_id', ''));
            $category = trim($this->post('category', ''));
            $unit = trim($this->post('unit', ''));
            $quantity = (int)$this->post('quantity', 0);
            $reorderLevel = (int)$this->post('reorder_level', 5);
            $status = trim($this->post('status', 'active'));

            if ($itemName === '' || $itemCode === '') {
                $_SESSION['error'] = 'Item name and item code are required.';
                $this->redirect('inventory/items/edit?id=' . $id);
                return;
            }
            if (is_string($scope)) {
                $departmentId = $scope;
            }
            if ($departmentId === '') {
                $_SESSION['error'] = 'Department is required.';
                $this->redirect('inventory/items/edit?id=' . $id);
                return;
            }
            if (!in_array($status, ['active', 'inactive'], true)) {
                $status = 'active';
            }

            $data = [
                'item_name' => $itemName,
                'item_code' => $itemCode,
                'department_id' => $departmentId,
                'category' => $category,
                'unit' => $unit,
                'quantity' => max(0, $quantity),
                'reorder_level' => max(0, $reorderLevel),
                'status' => $status,
            ];

            $sqlError = null;
            if ($inventoryModel->updateItem($id, $data, $sqlError)) {
                $_SESSION['message'] = 'Item updated.';
                $this->redirect('inventory/items');
                return;
            }
            $_SESSION['error'] = 'Update failed: ' . ($sqlError ?: '');
            $this->redirect('inventory/items/edit?id=' . $id);
            return;
        }

        $data = [
            'title' => 'Edit Item',
            'page' => 'inventory',
            'inventorySection' => 'items',
            'item' => $row,
            'departments' => $departments,
            'departmentScope' => is_string($scope) ? $scope : null,
            'error' => $_SESSION['error'] ?? null,
        ];
        unset($_SESSION['error']);
        return $this->view('inventory/items/edit', $data);
    }

    public function itemsDelete() {
        if (!$this->requireInventoryAccess() || !$this->ensureTables()) {
            return;
        }
        $scope = $this->getInventoryDepartmentScope();
        if ($scope === false) {
            $_SESSION['error'] = 'Your account has no department assigned.';
            $this->redirect('inventory/items');
            return;
        }
        $id = (int)$this->get('id', 0);
        if ($id <= 0) {
            $_SESSION['error'] = 'Invalid item.';
            $this->redirect('inventory/items');
            return;
        }

        $inventoryModel = $this->model('InventoryModel');
        $row = $inventoryModel->find($id);
        if (!$row) {
            $_SESSION['error'] = 'Item not found.';
            $this->redirect('inventory/items');
            return;
        }
        if (is_string($scope) && ($row['department_id'] ?? '') !== $scope) {
            $_SESSION['error'] = 'Access denied.';
            $this->redirect('inventory/items');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($inventoryModel->deleteItem($id)) {
                $_SESSION['message'] = 'Item deleted.';
            } else {
                $_SESSION['error'] = 'Could not delete item.';
            }
            $this->redirect('inventory/items');
            return;
        }

        $data = [
            'title' => 'Delete Item',
            'page' => 'inventory',
            'inventorySection' => 'items',
            'item' => $row,
        ];
        return $this->view('inventory/items/delete', $data);
    }

    public function stockIn() {
        if (!$this->requireInventoryAccess() || !$this->ensureTables()) {
            return;
        }
        $scope = $this->getInventoryDepartmentScope();
        $inventoryModel = $this->model('InventoryModel');
        $stockInModel = $this->model('StockInModel');
        $logModel = $this->model('InventoryLogModel');

        if ($scope === false) {
            $items = [];
        } else {
            $items = $inventoryModel->getItemsForSelect($scope === null ? null : $scope);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($scope === false) {
                $_SESSION['error'] = 'Your account has no department assigned.';
                $this->redirect('inventory/stock-in');
                return;
            }
            $itemId = (int)$this->post('item_id', 0);
            $qty = (int)$this->post('quantity', 0);
            $supplier = trim($this->post('supplier', ''));
            $dateIn = trim($this->post('date_in', ''));
            $price = trim($this->post('purchase_price', ''));

            if ($itemId <= 0 || $qty <= 0) {
                $_SESSION['error'] = 'Item and a positive quantity are required.';
                $this->redirect('inventory/stock-in');
                return;
            }

            $item = $inventoryModel->find($itemId);
            if (!$item) {
                $_SESSION['error'] = 'Invalid item.';
                $this->redirect('inventory/stock-in');
                return;
            }
            if (is_string($scope) && ($item['department_id'] ?? '') !== $scope) {
                $_SESSION['error'] = 'Access denied.';
                $this->redirect('inventory/stock-in');
                return;
            }

            $deptId = $item['department_id'] ?? null;
            $db = Database::getInstance();
            $db->begin_transaction();
            $sqlErr = null;

            $siData = [
                'item_id' => $itemId,
                'department_id' => $deptId,
                'quantity' => $qty,
                'supplier' => $supplier,
                'date_in' => $dateIn !== '' ? $dateIn : date('Y-m-d'),
            ];
            if ($price !== '' && is_numeric($price)) {
                $siData['purchase_price'] = $price;
            }

            $sid = $stockInModel->createRecord($siData, $sqlErr);
            if ($sid === false) {
                $db->rollback();
                $_SESSION['error'] = 'Stock in failed: ' . ($sqlErr ?: '');
                $this->redirect('inventory/stock-in');
                return;
            }
            $refId = (int)$db->lastInsertId();
            if ($refId <= 0) {
                $refId = (int)$sid;
            }

            if ($inventoryModel->adjustQuantity($itemId, $qty, $sqlErr) === false) {
                $db->rollback();
                $_SESSION['error'] = 'Could not update quantity: ' . ($sqlErr ?: '');
                $this->redirect('inventory/stock-in');
                return;
            }

            $logOk = $logModel->insertLog([
                'item_id' => $itemId,
                'department_id' => $deptId,
                'action_type' => 'IN',
                'quantity' => $qty,
                'reference_id' => $refId,
                'remarks' => $supplier !== '' ? ('Supplier: ' . $supplier) : null,
            ], $sqlErr);
            if ($logOk === false) {
                $db->rollback();
                $_SESSION['error'] = 'Could not write log: ' . ($sqlErr ?: '');
                $this->redirect('inventory/stock-in');
                return;
            }

            $db->commit();
            $_SESSION['message'] = 'Stock added successfully.';
            $this->redirect('inventory');
            return;
        }

        $data = [
            'title' => 'Stock In',
            'page' => 'inventory',
            'inventorySection' => 'stock-in',
            'items' => $items,
            'departmentScope' => is_string($scope) ? $scope : null,
            'scopeNoDepartment' => ($scope === false),
            'error' => $_SESSION['error'] ?? null,
            'message' => $_SESSION['message'] ?? null,
        ];
        unset($_SESSION['error'], $_SESSION['message']);
        return $this->view('inventory/stock-in', $data);
    }

    public function stockOut() {
        if (!$this->requireInventoryAccess() || !$this->ensureTables()) {
            return;
        }
        $scope = $this->getInventoryDepartmentScope();
        $inventoryModel = $this->model('InventoryModel');
        $stockOutModel = $this->model('StockOutModel');
        $logModel = $this->model('InventoryLogModel');

        if ($scope === false) {
            $items = [];
        } else {
            $items = $inventoryModel->getItemsForSelect($scope === null ? null : $scope);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($scope === false) {
                $_SESSION['error'] = 'Your account has no department assigned.';
                $this->redirect('inventory/stock-out');
                return;
            }
            $itemId = (int)$this->post('item_id', 0);
            $qty = (int)$this->post('quantity', 0);
            $issuedTo = trim($this->post('issued_to', ''));
            $issuedType = trim($this->post('issued_type', 'staff'));
            $reason = trim($this->post('reason', ''));
            $dateOut = trim($this->post('date_out', ''));

            if ($itemId <= 0 || $qty <= 0) {
                $_SESSION['error'] = 'Item and a positive quantity are required.';
                $this->redirect('inventory/stock-out');
                return;
            }
            if (!in_array($issuedType, ['student', 'staff'], true)) {
                $issuedType = 'staff';
            }

            $item = $inventoryModel->find($itemId);
            if (!$item) {
                $_SESSION['error'] = 'Invalid item.';
                $this->redirect('inventory/stock-out');
                return;
            }
            if (is_string($scope) && ($item['department_id'] ?? '') !== $scope) {
                $_SESSION['error'] = 'Access denied.';
                $this->redirect('inventory/stock-out');
                return;
            }

            $current = (int)($item['quantity'] ?? 0);
            if ($current < $qty) {
                $_SESSION['error'] = 'Insufficient stock. Available: ' . $current;
                $this->redirect('inventory/stock-out');
                return;
            }

            $deptId = $item['department_id'] ?? null;
            $db = Database::getInstance();
            $db->begin_transaction();
            $sqlErr = null;

            $soData = [
                'item_id' => $itemId,
                'department_id' => $deptId,
                'quantity' => $qty,
                'issued_to' => $issuedTo,
                'issued_type' => $issuedType,
                'reason' => $reason,
                'date_out' => $dateOut !== '' ? $dateOut : date('Y-m-d'),
            ];

            $so = $stockOutModel->createRecord($soData, $sqlErr);
            if ($so === false) {
                $db->rollback();
                $_SESSION['error'] = 'Stock out failed: ' . ($sqlErr ?: '');
                $this->redirect('inventory/stock-out');
                return;
            }
            $refId = (int)$db->lastInsertId();
            if ($refId <= 0) {
                $refId = (int)$so;
            }

            if ($inventoryModel->adjustQuantity($itemId, -$qty, $sqlErr) === false) {
                $db->rollback();
                $_SESSION['error'] = 'Could not update quantity: ' . ($sqlErr ?: '');
                $this->redirect('inventory/stock-out');
                return;
            }

            $logOk = $logModel->insertLog([
                'item_id' => $itemId,
                'department_id' => $deptId,
                'action_type' => 'OUT',
                'quantity' => $qty,
                'reference_id' => $refId,
                'remarks' => trim($issuedTo . ' | ' . $reason, ' |'),
            ], $sqlErr);
            if ($logOk === false) {
                $db->rollback();
                $_SESSION['error'] = 'Could not write log: ' . ($sqlErr ?: '');
                $this->redirect('inventory/stock-out');
                return;
            }

            $db->commit();
            $_SESSION['message'] = 'Stock issued successfully.';
            $this->redirect('inventory');
            return;
        }

        $data = [
            'title' => 'Stock Out',
            'page' => 'inventory',
            'inventorySection' => 'stock-out',
            'items' => $items,
            'departmentScope' => is_string($scope) ? $scope : null,
            'scopeNoDepartment' => ($scope === false),
            'error' => $_SESSION['error'] ?? null,
        ];
        unset($_SESSION['error']);
        return $this->view('inventory/stock-out', $data);
    }

    public function transfer() {
        if (!$this->requireInventoryAccess() || !$this->ensureTables()) {
            return;
        }
        $scope = $this->getInventoryDepartmentScope();
        $inventoryModel = $this->model('InventoryModel');
        $transferModel = $this->model('StockTransferModel');
        $logModel = $this->model('InventoryLogModel');
        $deptModel = $this->model('DepartmentModel');
        $departments = $deptModel->getAll();

        if ($scope === false) {
            $items = [];
        } else {
            $items = $inventoryModel->getItemsForSelect($scope === null ? null : $scope);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($scope === false) {
                $_SESSION['error'] = 'Your account has no department assigned.';
                $this->redirect('inventory/transfer');
                return;
            }
            $itemId = (int)$this->post('item_id', 0);
            $toDept = trim($this->post('to_department', ''));
            $qty = (int)$this->post('quantity', 0);
            $tdate = trim($this->post('transfer_date', ''));
            $approvedBy = trim($this->post('approved_by', ''));

            if ($itemId <= 0 || $qty <= 0 || $toDept === '') {
                $_SESSION['error'] = 'Item, quantity, and destination department are required.';
                $this->redirect('inventory/transfer');
                return;
            }

            $source = $inventoryModel->find($itemId);
            if (!$source) {
                $_SESSION['error'] = 'Invalid item.';
                $this->redirect('inventory/transfer');
                return;
            }
            $fromDept = $source['department_id'] ?? '';
            if ($fromDept === '' || $fromDept === null) {
                $_SESSION['error'] = 'Source item must have a department.';
                $this->redirect('inventory/transfer');
                return;
            }
            if ($fromDept === $toDept) {
                $_SESSION['error'] = 'Choose a different destination department.';
                $this->redirect('inventory/transfer');
                return;
            }
            if (is_string($scope) && $fromDept !== $scope) {
                $_SESSION['error'] = 'Access denied.';
                $this->redirect('inventory/transfer');
                return;
            }

            $code = trim($source['item_code'] ?? '');
            if ($code === '') {
                $_SESSION['error'] = 'Item must have an item code to transfer. Edit the item first.';
                $this->redirect('inventory/transfer');
                return;
            }

            $current = (int)($source['quantity'] ?? 0);
            if ($current < $qty) {
                $_SESSION['error'] = 'Insufficient stock. Available: ' . $current;
                $this->redirect('inventory/transfer');
                return;
            }

            $db = Database::getInstance();
            $db->begin_transaction();
            $sqlErr = null;

            if ($inventoryModel->adjustQuantity($itemId, -$qty, $sqlErr) === false) {
                $db->rollback();
                $_SESSION['error'] = 'Transfer failed (source): ' . ($sqlErr ?: '');
                $this->redirect('inventory/transfer');
                return;
            }

            $target = $inventoryModel->findByCodeAndDepartment($code, $toDept);
            if (!$target) {
                $newData = [
                    'item_name' => $source['item_name'],
                    'item_code' => $code,
                    'department_id' => $toDept,
                    'category' => $source['category'] ?? '',
                    'unit' => $source['unit'] ?? '',
                    'quantity' => 0,
                    'reorder_level' => (int)($source['reorder_level'] ?? 5),
                    'status' => $source['status'] ?? 'active',
                ];
                $cid = $inventoryModel->createItem($newData, $sqlErr);
                if ($cid === false) {
                    $db->rollback();
                    $_SESSION['error'] = 'Could not create target item row: ' . ($sqlErr ?: '');
                    $this->redirect('inventory/transfer');
                    return;
                }
                $targetId = (int)$db->lastInsertId();
                if ($targetId <= 0) {
                    $targetId = (int)$cid;
                }
            } else {
                $targetId = (int)$target['item_id'];
            }

            if ($inventoryModel->adjustQuantity($targetId, $qty, $sqlErr) === false) {
                $db->rollback();
                $_SESSION['error'] = 'Transfer failed (destination): ' . ($sqlErr ?: '');
                $this->redirect('inventory/transfer');
                return;
            }

            $trData = [
                'item_id' => $itemId,
                'from_department' => $fromDept,
                'to_department' => $toDept,
                'quantity' => $qty,
                'transfer_date' => $tdate !== '' ? $tdate : date('Y-m-d'),
                'approved_by' => $approvedBy,
            ];
            $tid = $transferModel->createRecord($trData, $sqlErr);
            if ($tid === false) {
                $db->rollback();
                $_SESSION['error'] = 'Transfer record failed: ' . ($sqlErr ?: '');
                $this->redirect('inventory/transfer');
                return;
            }
            $refId = (int)$db->lastInsertId();
            if ($refId <= 0) {
                $refId = (int)$tid;
            }

            $logOk = $logModel->insertLog([
                'item_id' => $itemId,
                'department_id' => $fromDept,
                'action_type' => 'TRANSFER',
                'quantity' => $qty,
                'reference_id' => $refId,
                'remarks' => 'To dept ' . $toDept . ($targetId ? ' (target item #' . $targetId . ')' : ''),
            ], $sqlErr);
            if ($logOk === false) {
                $db->rollback();
                $_SESSION['error'] = 'Could not write log: ' . ($sqlErr ?: '');
                $this->redirect('inventory/transfer');
                return;
            }

            $db->commit();
            $_SESSION['message'] = 'Transfer completed.';
            $this->redirect('inventory');
            return;
        }

        $data = [
            'title' => 'Transfer Stock',
            'page' => 'inventory',
            'inventorySection' => 'transfer',
            'items' => $items,
            'departments' => $departments,
            'departmentScope' => is_string($scope) ? $scope : null,
            'scopeNoDepartment' => ($scope === false),
            'error' => $_SESSION['error'] ?? null,
        ];
        unset($_SESSION['error']);
        return $this->view('inventory/transfer', $data);
    }

    public function log() {
        if (!$this->requireInventoryAccess() || !$this->ensureTables()) {
            return;
        }
        $scope = $this->getInventoryDepartmentScope();
        $logModel = $this->model('InventoryLogModel');
        $inventoryModel = $this->model('InventoryModel');
        $deptModel = $this->model('DepartmentModel');

        if (!$logModel->tableExists()) {
            $_SESSION['error'] = 'Inventory log table missing.';
            $this->redirect('inventory');
            return;
        }

        $filters = [
            'date_from' => trim($this->get('date_from', '')),
            'date_to' => trim($this->get('date_to', '')),
            'department_id' => trim($this->get('department_id', '')),
            'item_id' => trim($this->get('item_id', '')),
            'action_type' => trim($this->get('action_type', '')),
        ];
        if (is_string($scope)) {
            $filters['department_id'] = $scope;
        }

        if ($scope === false) {
            $logs = [];
            $allItems = [];
        } else {
            $logs = $logModel->getLogs($filters);
            $allItems = $inventoryModel->getItemsForSelect($scope === null ? null : $scope);
        }
        $departments = $deptModel->getAll();

        $data = [
            'title' => 'Inventory Log',
            'page' => 'inventory',
            'inventorySection' => 'log',
            'logs' => $logs,
            'filters' => $filters,
            'departments' => $departments,
            'allItems' => $allItems,
            'departmentScope' => is_string($scope) ? $scope : null,
            'scopeNoDepartment' => ($scope === false),
            'canViewAllDepartments' => ($scope === null),
        ];
        return $this->view('inventory/log', $data);
    }
}
