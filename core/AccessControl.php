<?php
/**
 * Staff Member → Module → Action access.
 * Role RBAC is the default; staff_module_permission rows override per person.
 */
class AccessControl {
    const MODE_INHERIT = 'inherit';
    const MODE_GRANT = 'grant';
    const MODE_DENY = 'deny';
    const MODE_CUSTOM = 'custom';

    private static $catalog = null;
    private static $permCache = [];
    private static $userCache = [];

    public static function catalog() {
        if (self::$catalog === null) {
            self::$catalog = require BASE_PATH . '/config/staff_modules.php';
            uasort(self::$catalog, function ($a, $b) {
                return ((int) ($a['sort'] ?? 100)) <=> ((int) ($b['sort'] ?? 100));
            });
        }
        return self::$catalog;
    }

    public static function can($userId, $moduleKey, $action = 'view') {
        $userId = (int) $userId;
        $action = self::normalizeAction($action);
        if ($userId <= 0) {
            return false;
        }

        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        if ($userModel->isAdmin($userId)) {
            return true;
        }

        $user = self::userRow($userId);
        if (!$user) {
            return false;
        }
        if (strtolower((string) ($user['user_table'] ?? '')) === 'student') {
            return false;
        }

        $staffId = trim((string) ($user['user_name'] ?? ''));
        $roleFlags = self::roleDefaults($userId, $moduleKey, $userModel);
        $row = self::staffRow($staffId, $moduleKey);
        $resolved = self::resolve($row, $roleFlags);
        if (empty($resolved['can_view']) && $action !== 'view') {
            return false;
        }
        return !empty($resolved['can_' . $action]);
    }

    public static function roleDefaults($userId, $moduleKey, $userModel = null) {
        if ($userModel === null) {
            require_once BASE_PATH . '/models/UserModel.php';
            $userModel = new UserModel();
        }
        $userId = (int) $userId;
        $isAdmin = $userModel->isAdmin($userId);
        $isAdm = $userModel->isAdminOrADM($userId);
        $isHod = $userModel->isHOD($userId);
        $isSao = $userModel->isSAO($userId);
        $role = (string) $userModel->getUserRole($userId);
        $staffUser = true;

        $allFalse = [
            'can_view' => 0, 'can_add' => 0, 'can_edit' => 0, 'can_delete' => 0,
            'can_upload' => 0, 'can_download' => 0, 'can_approve' => 0,
        ];
        if ($isAdmin) {
            return self::withDocFlags(['can_view' => 1, 'can_add' => 1, 'can_edit' => 1, 'can_delete' => 1]);
        }

        switch ($moduleKey) {
            case 'students':
                $manageStudents = $isSao || $isAdm;
                return self::withDocFlags([
                    'can_view' => $staffUser ? 1 : 0,
                    'can_add' => $manageStudents ? 1 : 0,
                    'can_edit' => $manageStudents ? 1 : 0,
                    'can_delete' => $manageStudents ? 1 : 0,
                ]);
            case 'staff':
                $manage = $userModel->canManageStaff($userId);
                $view = !$isSao;
                return [
                    'can_view' => $view ? 1 : 0,
                    'can_add' => $manage ? 1 : 0,
                    'can_edit' => $manage ? 1 : 0,
                    'can_delete' => $manage ? 1 : 0,
                ];
            case 'staff_attendance':
                $view = $userModel->canViewStaffDeviceDashboardMonth($userId);
                $manage = $userModel->canManageStaffDeviceSyncDaily($userId);
                return [
                    'can_view' => $view ? 1 : 0,
                    'can_add' => $manage ? 1 : 0,
                    'can_edit' => $manage ? 1 : 0,
                    'can_delete' => $manage ? 1 : 0,
                ];
            case 'personal_files':
                $manage = $userModel->canManageStaff($userId) || $isAdm;
                return self::withDocFlags([
                    'can_view' => $manage ? 1 : 0,
                    'can_add' => $manage ? 1 : 0,
                    'can_edit' => $manage ? 1 : 0,
                    'can_delete' => $manage ? 1 : 0,
                ]);
            case 'student_attendance':
                $view = in_array($role, ['HOD', 'IN1', 'IN2', 'IN3', 'DIR', 'DPI', 'DPA', 'REG', 'FIN', 'ACC', 'SAO', 'ADM'], true) || $isAdmin;
                $manage = in_array($role, ['HOD', 'IN1', 'IN2', 'IN3', 'ADM'], true) || $isAdmin;
                return [
                    'can_view' => $view ? 1 : 0,
                    'can_add' => $manage ? 1 : 0,
                    'can_edit' => $manage ? 1 : 0,
                    'can_delete' => $manage ? 1 : 0,
                ];
            case 'staff_roles':
                return [
                    'can_view' => $isAdm ? 1 : 0,
                    'can_add' => $isAdm ? 1 : 0,
                    'can_edit' => $isAdm ? 1 : 0,
                    'can_delete' => $isAdm ? 1 : 0,
                ];
            case 'departments':
                return [
                    'can_view' => !$isSao ? 1 : 0,
                    'can_add' => $isAdm ? 1 : 0,
                    'can_edit' => $isAdm ? 1 : 0,
                    'can_delete' => $isAdm ? 1 : 0,
                ];
            case 'courses':
                $manageCourses = $isAdm || $isHod;
                return [
                    'can_view' => !$isSao ? 1 : 0,
                    'can_add' => $manageCourses ? 1 : 0,
                    'can_edit' => $manageCourses ? 1 : 0,
                    'can_delete' => $manageCourses ? 1 : 0,
                ];
            case 'groups':
                $ok = in_array($role, ['HOD', 'IN1', 'IN2', 'IN3', 'ADM'], true) || $isAdmin;
                return [
                    'can_view' => $ok ? 1 : 0,
                    'can_add' => $ok ? 1 : 0,
                    'can_edit' => $ok ? 1 : 0,
                    'can_delete' => $ok ? 1 : 0,
                ];
            case 'exams':
                $ok = $userModel->canAccessExamsModule($userId);
                return [
                    'can_view' => $ok ? 1 : 0,
                    'can_add' => $ok ? 1 : 0,
                    'can_edit' => $ok ? 1 : 0,
                    'can_delete' => $ok ? 1 : 0,
                ];
            case 'payments':
                $view = $userModel->canViewPaymentsList($userId);
                $manage = $userModel->hasFinanceAccess($userId);
                return [
                    'can_view' => $view ? 1 : 0,
                    'can_add' => $manage ? 1 : 0,
                    'can_edit' => $manage ? 1 : 0,
                    'can_delete' => $manage ? 1 : 0,
                ];
            case 'devices':
                $view = $userModel->canViewDevices($userId);
                $manage = $userModel->canManageDevices($userId);
                return [
                    'can_view' => $view ? 1 : 0,
                    'can_add' => $manage ? 1 : 0,
                    'can_edit' => $manage ? 1 : 0,
                    'can_delete' => $manage ? 1 : 0,
                ];
            case 'complaint_letters':
                $view = $userModel->canViewComplaintLetters($userId);
                $manage = $userModel->canManageComplaintLetters($userId);
                return [
                    'can_view' => $view ? 1 : 0,
                    'can_add' => $manage ? 1 : 0,
                    'can_edit' => $manage ? 1 : 0,
                    'can_delete' => $manage ? 1 : 0,
                ];
            case 'student_applications':
                $view = $userModel->canViewOnlineStudentApplications($userId)
                    || $userModel->canViewApplicationAdmissionSchedules($userId);
                $manage = $userModel->canManageApplicationAdmissionSchedules($userId);
                return [
                    'can_view' => $view ? 1 : 0,
                    'can_add' => $manage ? 1 : 0,
                    'can_edit' => $manage ? 1 : 0,
                    'can_delete' => $manage ? 1 : 0,
                ];
            case 'instructor_diary':
                $ok = !$isSao && (in_array($role, ['HOD', 'IN1', 'IN2', 'IN3', 'LE1', 'LE2', 'SLE', 'ADM'], true) || $isAdmin);
                return [
                    'can_view' => $ok ? 1 : 0,
                    'can_add' => $ok ? 1 : 0,
                    'can_edit' => $ok ? 1 : 0,
                    'can_delete' => $ok ? 1 : 0,
                ];
            case 'circuit_program':
                return self::withDocFlags(['can_view' => 1, 'can_add' => 1, 'can_edit' => 1, 'can_delete' => 1]);
            default:
                return $allFalse;
        }
    }

    private static function withDocFlags(array $flags) {
        $flags['can_upload'] = isset($flags['can_upload']) ? (int) $flags['can_upload'] : (int) !empty($flags['can_add']);
        $flags['can_download'] = isset($flags['can_download']) ? (int) $flags['can_download'] : (int) !empty($flags['can_view']);
        $flags['can_approve'] = isset($flags['can_approve']) ? (int) $flags['can_approve'] : (int) !empty($flags['can_edit']);
        foreach (['can_view', 'can_add', 'can_edit', 'can_delete'] as $key) {
            $flags[$key] = !empty($flags[$key]) ? 1 : 0;
        }
        return $flags;
    }

    public static function resolve($row, array $roleFlags) {
        $mode = strtolower((string) ($row['access_mode'] ?? self::MODE_INHERIT));
        if ($row === null || $mode === '' || $mode === self::MODE_INHERIT) {
            return $roleFlags;
        }
        if ($mode === self::MODE_DENY) {
            return self::withDocFlags(['can_view' => 0, 'can_add' => 0, 'can_edit' => 0, 'can_delete' => 0, 'can_upload' => 0, 'can_download' => 0, 'can_approve' => 0]);
        }
        if ($mode === self::MODE_GRANT) {
            return self::withDocFlags(['can_view' => 1, 'can_add' => 1, 'can_edit' => 1, 'can_delete' => 1, 'can_upload' => 1, 'can_download' => 1, 'can_approve' => 1]);
        }
        return self::withDocFlags([
            'can_view' => !empty($row['can_view']) ? 1 : 0,
            'can_add' => !empty($row['can_add']) ? 1 : 0,
            'can_edit' => !empty($row['can_edit']) ? 1 : 0,
            'can_delete' => !empty($row['can_delete']) ? 1 : 0,
            'can_upload' => !empty($row['can_upload']) ? 1 : 0,
            'can_download' => !empty($row['can_download']) ? 1 : 0,
            'can_approve' => !empty($row['can_approve']) ? 1 : 0,
        ]);
    }

    public static function matrixForStaff($staffId) {
        require_once BASE_PATH . '/models/UserModel.php';
        require_once BASE_PATH . '/models/StaffModulePermissionModel.php';
        $userModel = new UserModel();
        $targetUserId = self::userIdForStaff($staffId);
        $rows = (new StaffModulePermissionModel())->getByStaff($staffId);
        $matrix = [];
        foreach (self::catalog() as $key => $meta) {
            $role = $targetUserId ? self::withDocFlags(self::roleDefaults($targetUserId, $key, $userModel)) : self::withDocFlags([]);
            $row = $rows[$key] ?? null;
            $effective = self::resolve($row, $role);
            $matrix[$key] = [
                'key' => $key,
                'label' => $meta['label'],
                'access_mode' => $row['access_mode'] ?? self::MODE_INHERIT,
                'role' => $role,
                'effective' => $effective,
                'custom' => [
                    'can_view' => (int) ($row['can_view'] ?? $role['can_view']),
                    'can_add' => (int) ($row['can_add'] ?? $role['can_add']),
                    'can_edit' => (int) ($row['can_edit'] ?? $role['can_edit']),
                    'can_delete' => (int) ($row['can_delete'] ?? $role['can_delete']),
                    'can_upload' => (int) ($row['can_upload'] ?? $role['can_upload']),
                    'can_download' => (int) ($row['can_download'] ?? $role['can_download']),
                    'can_approve' => (int) ($row['can_approve'] ?? $role['can_approve']),
                ],
            ];
        }
        return $matrix;
    }

    public static function allowCurrentRequest() {
        if (empty($_SESSION['user_id'])) {
            return true;
        }
        if (!empty($_SESSION['user_table']) && $_SESSION['user_table'] === 'student') {
            return true;
        }
        require_once BASE_PATH . '/core/RequestPath.php';
        $uri = RequestPath::resolve();
        if (self::isPublicUri($uri)) {
            return true;
        }
        $mapped = self::mapUri($uri);
        if ($mapped === null) {
            return true;
        }
        return self::can((int) $_SESSION['user_id'], $mapped['module'], $mapped['action']);
    }

    public static function denyAndRedirect() {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'You do not have permission for this module.']);
            exit();
        }
        $_SESSION['error'] = 'You do not have permission to access this module.';
        $url = defined('APP_URL') ? rtrim(APP_URL, '/') . '/dashboard' : '/dashboard';
        header('Location: ' . $url);
        exit();
    }

    private static function mapUri($uri) {
        $uri = strtolower(trim((string) $uri, '/'));
        if ($uri === '') {
            return null;
        }
        $action = 'view';
        if (preg_match('#/(download)$#', $uri) || strpos($uri, '/download') !== false) {
            $action = 'download';
        } elseif (preg_match('#/(upload|photo)$#', $uri) || strpos($uri, '/upload') !== false) {
            $action = 'upload';
        } elseif (strpos($uri, '/approve') !== false) {
            $action = 'approve';
        } elseif (preg_match('#/(create|store)$#', $uri) || strpos($uri, '/create') !== false) {
            $action = 'add';
        } elseif (preg_match('#/(delete|remove)$#', $uri) || strpos($uri, '/delete') !== false) {
            $action = 'delete';
        } elseif (preg_match('#/(edit|update|save)#', $uri)) {
            $action = 'edit';
        }
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if ($uri === 'staff/edit' && $method === 'GET') {
            $action = 'view';
        }
        if ($uri === 'staff/file/photo' && $method === 'GET') {
            $action = 'view';
        }
        if ($uri === 'staff/file/save') {
            $action = 'add';
        }
        if ($uri === 'staff/file/document/download') {
            $action = 'download';
        }
        if ($method === 'POST' && $action === 'view') {
            $action = 'edit';
        }

        $best = null;
        $bestLen = -1;
        foreach (self::catalog() as $key => $meta) {
            foreach ($meta['prefixes'] as $prefix) {
                $prefix = strtolower(trim($prefix, '/'));
                if ($uri === $prefix || strpos($uri, $prefix . '/') === 0) {
                    $len = strlen($prefix);
                    if ($len > $bestLen) {
                        $bestLen = $len;
                        $best = ['module' => $key, 'action' => $action];
                    }
                }
            }
        }
        return $best;
    }

    private static function isPublicUri($uri) {
        $uri = strtolower(trim((string) $uri, '/'));
        $public = [
            '', 'home', 'login', 'logout',
            'student-application/api/check-application',
            'application-admission/interview-letter',
        ];
        if (in_array($uri, $public, true)) {
            return true;
        }
        $prefixes = [
            'student/', 'student-application', 'level04application', 'level05application',
            'application-admission/public', 'application-admission/interview-letter',
            'devices/qr/',
        ];
        foreach ($prefixes as $prefix) {
            if ($uri === rtrim($prefix, '/') || strpos($uri, $prefix) === 0) {
                return true;
            }
        }
        return false;
    }

    private static function normalizeAction($action) {
        $action = strtolower((string) $action);
        $map = [
            'view' => 'view', 'read' => 'view', 'list' => 'view', 'index' => 'view',
            'add' => 'add', 'create' => 'add', 'insert' => 'add',
            'edit' => 'edit', 'update' => 'edit', 'save' => 'edit',
            'delete' => 'delete', 'remove' => 'delete',
            'upload' => 'upload',
            'download' => 'download',
            'approve' => 'approve',
        ];
        return $map[$action] ?? 'view';
    }

    private static function staffRow($staffId, $moduleKey) {
        if ($staffId === '') {
            return null;
        }
        if (!isset(self::$permCache[$staffId])) {
            require_once BASE_PATH . '/models/StaffModulePermissionModel.php';
            self::$permCache[$staffId] = (new StaffModulePermissionModel())->getByStaff($staffId);
        }
        return self::$permCache[$staffId][$moduleKey] ?? null;
    }

    private static function userRow($userId) {
        if (!isset(self::$userCache[$userId])) {
            $db = Database::getInstance();
            $stmt = $db->prepare('SELECT `user_id`, `user_name`, `user_table` FROM `user` WHERE `user_id` = ? LIMIT 1');
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            self::$userCache[$userId] = $stmt->get_result()->fetch_assoc() ?: null;
        }
        return self::$userCache[$userId];
    }

    public static function userIdForStaff($staffId) {
        $staffId = trim((string) $staffId);
        if ($staffId === '') {
            return 0;
        }
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT `user_id` FROM `user` WHERE `user_name` = ? LIMIT 1");
        $stmt->bind_param('s', $staffId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ? (int) $row['user_id'] : 0;
    }
}
