<?php
/**
 * Who may open each service area and change its records.
 */
class ServiceAccess {
    public static function actor($userId) {
        require_once BASE_PATH . '/models/UserModel.php';
        $users = new UserModel();
        $user = $users->find($userId) ?: [];
        $role = (string) $users->getUserRole($userId);
        $admin = $users->isAdmin($userId) || $users->isAdminOrADM($userId);
        $director = in_array($role, ['DIR', 'DPI', 'DPA'], true);
        $auditor = $role === 'AUD';
        $finance = in_array($role, ['FIN', 'ACC'], true);
        $registrar = $role === 'REG' || $admin;
        $areas = [];
        if ($admin || $registrar || $director || $auditor || $finance || $role === 'HOD') {
            $areas = ['security', 'ac', 'cleaning', 'canteen', 'contracts'];
        }
        if (in_array($role, ['SEC'], true)) {
            $areas[] = 'security';
        }
        if (in_array($role, ['MHF', 'FAC', 'FO'], true)) {
            $areas[] = 'ac';
        }
        if ($role === 'CLN') {
            $areas[] = 'cleaning';
        }
        if (in_array($role, ['CNT', 'CAN'], true)) {
            $areas[] = 'canteen';
        }
        $areas = array_values(array_unique($areas));
        return [
            'user_id' => (int) $userId,
            'staff_id' => (string) ($user['user_name'] ?? ''),
            'name' => (string) ($user['user_name'] ?? ''),
            'role' => $role,
            'is_admin' => $admin,
            'is_director' => $director,
            'is_auditor' => $auditor,
            'is_finance' => $finance,
            'is_registrar' => $registrar,
            'areas' => $areas,
            'can_write' => $admin || $registrar || $finance || in_array($role, ['SEC', 'MHF', 'FAC', 'FO', 'CLN', 'CNT', 'CAN'], true),
        ];
    }

    public static function canArea(array $actor, $area) {
        if (!empty($actor['is_admin'])) {
            return true;
        }
        return in_array((string) $area, $actor['areas'], true);
    }

    public static function canWrite(array $actor, array $resource, $area) {
        if (empty($actor['can_write']) || !empty($actor['is_auditor']) || !empty($actor['is_director'])) {
            return false;
        }
        if (!empty($actor['is_admin']) || !empty($actor['is_registrar'])) {
            return true;
        }
        if (!empty($actor['is_finance'])) {
            return in_array($resource['table'], ['service_invoices', 'service_payments'], true);
        }
        $resourceArea = $resource['area'] === 'all' ? (string) $area : $resource['area'];
        return self::canArea($actor, $resourceArea) && $resourceArea !== 'contracts';
    }
}
