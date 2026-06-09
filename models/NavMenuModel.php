<?php
/**
 * Staff sidebar navigation menu (database-driven)
 */

class NavMenuModel extends Model {
    protected $table = 'staff_nav_menu';

    protected function getPrimaryKey() {
        return 'nav_id';
    }

    public function ensureTable(): void {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->table}` (
            `nav_id` INT(11) NOT NULL AUTO_INCREMENT,
            `parent_id` INT(11) DEFAULT NULL,
            `label` VARCHAR(120) NOT NULL,
            `route_path` VARCHAR(255) DEFAULT NULL COMMENT 'Relative route without leading slash; empty for parent-only',
            `icon_class` VARCHAR(80) DEFAULT 'fas fa-circle',
            `page_key` VARCHAR(80) DEFAULT NULL COMMENT 'Active state page id',
            `page_keys` TEXT DEFAULT NULL COMMENT 'JSON array of page keys for parent active state',
            `sort_order` INT(11) NOT NULL DEFAULT 0,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `is_divider` TINYINT(1) NOT NULL DEFAULT 0,
            `hide_for_sao` TINYINT(1) NOT NULL DEFAULT 0,
            `require_adm` TINYINT(1) NOT NULL DEFAULT 0,
            `require_admin` TINYINT(1) NOT NULL DEFAULT 0,
            `allowed_roles` TEXT DEFAULT NULL COMMENT 'JSON array of role codes; empty = all roles',
            `allowed_departments` TEXT DEFAULT NULL COMMENT 'JSON array of department_id; empty = all departments',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`nav_id`),
            KEY `idx_parent_sort` (`parent_id`, `sort_order`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->db->query($sql);
        $this->ensureStaffAssignOnlyColumn();
    }

    public function ensureStaffAssignOnlyColumn(): void {
        $check = $this->db->query("SHOW COLUMNS FROM `{$this->table}` LIKE 'staff_assign_only'");
        if ($check && $check->num_rows === 0) {
            $this->db->query("ALTER TABLE `{$this->table}` ADD COLUMN `staff_assign_only` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Only assigned approved staff see this item' AFTER `allowed_departments`");
        }
    }

    public function getAllForAdmin(): array {
        $this->ensureTable();
        $sql = "SELECT n.*, p.`label` AS parent_label,
                (SELECT COUNT(*) FROM `staff_nav_assignment` a WHERE a.`nav_id` = n.`nav_id`) AS assignment_count,
                (SELECT COUNT(*) FROM `staff_nav_assignment` a WHERE a.`nav_id` = n.`nav_id` AND a.`status` = 'approved') AS approved_staff_count
                FROM `{$this->table}` n
                LEFT JOIN `{$this->table}` p ON p.`nav_id` = n.`parent_id`
                ORDER BY COALESCE(n.`parent_id`, n.`nav_id`), n.`parent_id` IS NOT NULL, n.`sort_order`, n.`label`";
        $result = $this->db->query($sql);
        if (!$result) {
            return [];
        }
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $this->hydrateRow($row);
        }
        return $rows;
    }

    public function getActiveFlat(): array {
        $this->ensureTable();
        $sql = "SELECT * FROM `{$this->table}` WHERE `is_active` = 1 ORDER BY `sort_order`, `label`";
        $result = $this->db->query($sql);
        if (!$result) {
            return [];
        }
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $this->hydrateRow($row);
        }
        return $rows;
    }

    public function findById(int $id): ?array {
        $this->ensureTable();
        $row = $this->find($id);
        return $row ? $this->hydrateRow($row) : null;
    }

    public function getParentOptions(?int $excludeId = null): array {
        $this->ensureTable();
        $sql = "SELECT `nav_id`, `label`, `parent_id` FROM `{$this->table}`
                WHERE `is_divider` = 0 AND (`route_path` IS NULL OR `route_path` = '')
                ORDER BY `sort_order`, `label`";
        $result = $this->db->query($sql);
        if (!$result) {
            return [];
        }
        $options = [];
        while ($row = $result->fetch_assoc()) {
            if ($excludeId !== null && (int) $row['nav_id'] === $excludeId) {
                continue;
            }
            $options[] = $row;
        }
        return $options;
    }

    public function createItem(array $data): int|false {
        $this->ensureTable();
        $payload = $this->preparePayload($data);
        if ($this->create($payload)) {
            return (int) $this->db->getConnection()->insert_id;
        }
        return false;
    }

    public function updateItem(int $id, array $data): bool {
        $this->ensureTable();
        return $this->update($id, $this->preparePayload($data));
    }

    public function deleteItem(int $id): bool {
        $this->ensureTable();
        return $this->delete($id);
    }

    public function countActive(): int {
        $this->ensureTable();
        $result = $this->db->query("SELECT COUNT(*) AS c FROM `{$this->table}` WHERE `is_active` = 1 AND `is_divider` = 0");
        if (!$result) {
            return 0;
        }
        $row = $result->fetch_assoc();
        return (int) ($row['c'] ?? 0);
    }

    /**
     * Seed a starter menu (does nothing if items already exist).
     */
    public function seedDefaultsIfEmpty(): int {
        $this->ensureTable();
        $check = $this->db->query("SELECT COUNT(*) AS c FROM `{$this->table}`");
        $count = $check ? (int) ($check->fetch_assoc()['c'] ?? 0) : 0;
        if ($count > 0) {
            return 0;
        }

        $inserted = 0;
        $dashboardId = $this->createItem([
            'parent_id' => null,
            'label' => 'Dashboard',
            'route_path' => 'dashboard',
            'icon_class' => 'fas fa-tachometer-alt',
            'page_key' => 'dashboard',
            'sort_order' => 10,
            'is_active' => 1,
        ]);
        if ($dashboardId) {
            $inserted++;
        }

        $mgmtId = $this->createItem([
            'parent_id' => null,
            'label' => 'Management',
            'route_path' => '',
            'icon_class' => 'fas fa-graduation-cap',
            'page_key' => '',
            'page_keys' => ['departments', 'courses', 'modules', 'staff', 'inventory', 'groups'],
            'sort_order' => 20,
            'is_active' => 1,
            'hide_for_sao' => 1,
        ]);
        if ($mgmtId) {
            $inserted++;
            $children = [
                ['Departments', 'departments', 'fas fa-building', 'departments', 10],
                ['Courses', 'courses', 'fas fa-book', 'courses', 20],
                ['Modules', 'modules', 'fas fa-cubes', 'modules', 30],
                ['Staff', 'staff', 'fas fa-chalkboard-teacher', 'staff', 40],
                ['Inventory', 'inventory', 'fas fa-boxes', 'inventory', 50],
            ];
            foreach ($children as [$label, $route, $icon, $pageKey, $sort]) {
                if ($this->createItem([
                    'parent_id' => $mgmtId,
                    'label' => $label,
                    'route_path' => $route,
                    'icon_class' => $icon,
                    'page_key' => $pageKey,
                    'sort_order' => $sort,
                    'is_active' => 1,
                    'hide_for_sao' => 1,
                ])) {
                    $inserted++;
                }
            }
        }

        $studentId = $this->createItem([
            'parent_id' => null,
            'label' => 'Student Info',
            'route_path' => '',
            'icon_class' => 'fas fa-user-graduate',
            'page_key' => '',
            'page_keys' => ['students', 'student-applications'],
            'sort_order' => 30,
            'is_active' => 1,
        ]);
        if ($studentId) {
            $inserted++;
            foreach ([
                ['Students', 'students', 'fas fa-user-graduate', 'students', 10, []],
                ['Online applications', 'student-applications', 'fas fa-file-signature', 'student-applications', 20, []],
            ] as [$label, $route, $icon, $pageKey, $sort, $roles]) {
                if ($this->createItem([
                    'parent_id' => $studentId,
                    'label' => $label,
                    'route_path' => $route,
                    'icon_class' => $icon,
                    'page_key' => $pageKey,
                    'sort_order' => $sort,
                    'is_active' => 1,
                    'allowed_roles' => $roles,
                ])) {
                    $inserted++;
                }
            }
        }

        return $inserted;
    }

    private function preparePayload(array $data): array {
        $parentId = $data['parent_id'] ?? null;
        if ($parentId === '' || $parentId === '0') {
            $parentId = null;
        }

        $roles = $data['allowed_roles'] ?? [];
        if (!is_array($roles)) {
            $roles = [];
        }
        $roles = array_values(array_filter(array_map(static function ($r) {
            return strtoupper(trim((string) $r));
        }, $roles)));

        $departments = $data['allowed_departments'] ?? [];
        if (!is_array($departments)) {
            $departments = [];
        }
        $departments = array_values(array_filter(array_map('strval', $departments)));

        $pageKeys = $data['page_keys'] ?? [];
        if (!is_array($pageKeys)) {
            $pageKeys = [];
        }
        $pageKeys = array_values(array_filter(array_map('strval', $pageKeys)));
        if ($pageKeys === [] && !empty($data['page_key'])) {
            $pageKeys = [(string) $data['page_key']];
        }

        return [
            'parent_id' => $parentId,
            'label' => trim((string) ($data['label'] ?? '')),
            'route_path' => trim((string) ($data['route_path'] ?? '')),
            'icon_class' => trim((string) ($data['icon_class'] ?? 'fas fa-circle')) ?: 'fas fa-circle',
            'page_key' => trim((string) ($data['page_key'] ?? '')),
            'page_keys' => $pageKeys === [] ? null : json_encode($pageKeys),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'is_divider' => !empty($data['is_divider']) ? 1 : 0,
            'hide_for_sao' => !empty($data['hide_for_sao']) ? 1 : 0,
            'require_adm' => !empty($data['require_adm']) ? 1 : 0,
            'require_admin' => !empty($data['require_admin']) ? 1 : 0,
            'allowed_roles' => null,
            'allowed_departments' => null,
            'staff_assign_only' => 1,
        ];
    }

    private function hydrateRow(array $row): array {
        $row['allowed_roles'] = $this->decodeJsonList($row['allowed_roles'] ?? null);
        $row['allowed_departments'] = $this->decodeJsonList($row['allowed_departments'] ?? null);
        $row['page_keys'] = $this->decodeJsonList($row['page_keys'] ?? null);
        if ($row['page_keys'] === [] && !empty($row['page_key'])) {
            $row['page_keys'] = [(string) $row['page_key']];
        }
        return $row;
    }

    private function decodeJsonList($value): array {
        if ($value === null || $value === '') {
            return [];
        }
        if (is_array($value)) {
            return $value;
        }
        $decoded = json_decode((string) $value, true);
        return is_array($decoded) ? $decoded : [];
    }
}
