<?php
/**
 * Procurement roles: who the current person is in the procurement workflow.
 *
 * Roles come from the staff position (DIR, ACC, PUR, PRO ...) or from an extra
 * assignment an administrator made on the Roles page. Everybody on staff is a requester.
 * A system administrator can view and report on everything but acts in no workflow step
 * unless a workflow role is also assigned to them.
 */
class ProcurementAccess {
    const REQUESTER = 'requester';
    const DIRECTOR = 'director';
    const ACCOUNTANT = 'accountant';
    const PURCHASE = 'purchase_officer';
    const PROCUREMENT = 'procurement_officer';

    const ROLE_LABELS = [
        'requester' => 'Requester',
        'director' => 'Director',
        'accountant' => 'Accountant',
        'purchase_officer' => 'Purchase Officer',
        'procurement_officer' => 'Procurement Officer',
    ];

    /** Roles an administrator may assign in addition to the staff position. */
    const ASSIGNABLE = ['director', 'accountant', 'purchase_officer', 'procurement_officer'];

    /** staff_position_type codes that carry a workflow role. */
    const POSITION_CODES = [
        'director' => ['DIR', 'DPR'],
        'accountant' => ['ACC', 'ACO', 'FIN'],
        'purchase_officer' => ['STK', 'PUR'],
        'procurement_officer' => ['PRO'],
    ];

    public static function actor(int $userId, ProcurementModel $model): array {
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $user = $userModel->find($userId) ?: [];
        $staffId = (string) ($user['user_name'] ?? '');
        $position = (string) $userModel->getUserRole($userId);
        $roles = [];
        if ($staffId !== '') {
            $roles[] = self::REQUESTER;
        }
        foreach (self::POSITION_CODES as $role => $codes) {
            if ($position !== '' && in_array($position, $codes, true)) {
                $roles[] = $role;
            }
        }
        if ($staffId !== '') {
            foreach ($model->assignedRoles($staffId) as $role) {
                if (in_array($role, self::ASSIGNABLE, true)) {
                    $roles[] = $role;
                }
            }
        }
        $roles = array_values(array_unique($roles));
        $isHod = $position === 'HOD';
        $hodDept = $isHod ? $userModel->getHODDepartment($userId) : null;
        return [
            'user_id' => $userId,
            'staff_id' => $staffId,
            'name' => $staffId !== '' ? $model->staffName($staffId) : '',
            'department_id' => $staffId !== '' ? $model->staffDepartment($staffId) : null,
            'position' => $position,
            'is_hod' => $isHod,
            'hod_department_id' => $hodDept ? (string) $hodDept : null,
            'is_admin' => $userModel->isAdmin($userId) || $userModel->isAdminOrADM($userId),
            'roles' => $roles,
        ];
    }

    public static function has(array $actor, string $role): bool {
        return in_array($role, $actor['roles'] ?? [], true);
    }

    /** Workflow roles only (everybody is a requester, so that one is not interesting). */
    public static function workflowRoles(array $actor): array {
        return array_values(array_intersect(self::ASSIGNABLE, $actor['roles'] ?? []));
    }

    public static function label(string $role): string {
        return self::ROLE_LABELS[$role] ?? ucfirst(str_replace('_', ' ', $role));
    }
}
