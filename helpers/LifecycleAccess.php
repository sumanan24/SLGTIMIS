<?php
class LifecycleAccess {
    public static function actor($userId) {
        require_once BASE_PATH . '/models/UserModel.php';
        $users = new UserModel();
        $userId = (int) $userId;
        $role = (string) $users->getUserRole($userId);
        $admin = $users->isAdmin($userId);
        $office = $admin || $users->isAdminOrADM($userId) || $users->isSAO($userId) || $role === 'REG';
        $director = in_array($role, ['DIR', 'DPI', 'DPA'], true);
        $finance = in_array($role, ['FIN', 'ACC'], true);
        $hod = $users->isHOD($userId);
        $departmentId = '';
        $staffId = '';
        if (!empty($_SESSION['user_name'])) {
            $staffId = (string) $_SESSION['user_name'];
        }
        if ($staffId !== '') {
            $db = Database::getInstance();
            $stmt = $db->prepare('SELECT `department_id` FROM `staff` WHERE `staff_id` = ? LIMIT 1');
            if ($stmt) {
                $stmt->bind_param('s', $staffId);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $departmentId = $row ? (string) $row['department_id'] : '';
            }
        }
        return [
            'user_id' => $userId,
            'role' => $role,
            'staff_id' => $staffId,
            'admin' => $admin,
            'office' => $office,
            'director' => $director,
            'finance' => $finance,
            'hod' => $hod,
            'department_id' => $departmentId,
            'can_approve' => $office || $director,
            'can_delete' => $admin || $users->isAdminOrADM($userId) || $role === 'REG',
        ];
    }

    public static function canWrite(array $actor, $kind) {
        $kind = (string) $kind;
        if (!empty($actor['office'])) {
            return true;
        }
        if ($kind === 'clearance') {
            return !empty($actor['hod']) || !empty($actor['finance']) || !empty($actor['director']);
        }
        if (in_array($kind, ['industry', 'ojt', 'monitoring', 'evaluation', 'progress'], true)) {
            return !empty($actor['hod']);
        }
        if ($kind === 'approval' && !empty($actor['can_approve'])) {
            return true;
        }
        return false;
    }

    public static function canClearSection(array $actor, $section) {
        if (!empty($actor['office']) || !empty($actor['director'])) {
            return true;
        }
        $section = (string) $section;
        if (!empty($actor['finance']) && $section === 'Finance') {
            return true;
        }
        if (!empty($actor['hod']) && in_array($section, ['Department', 'Workshop'], true)) {
            return true;
        }
        return false;
    }
}
