<?php
/**
 * Staff Type Model (Permanent, On Contract, Visiting Lecturer, ...)
 *
 * `staff.staff_type` stores the type NAME. It used to be a fixed ENUM; the schema helper below
 * converts it to VARCHAR so types can be managed from the `staff_type` table.
 */

class StaffTypeModel extends Model {
    protected $table = 'staff_type';

    /** Name the appointment rules depend on (Cover-up / Acting). Cannot be renamed or removed. */
    const SYSTEM_TYPE = 'Permanent';

    private static $schemaDone = false;

    public function __construct() {
        parent::__construct();
        $this->ensureSchema();
    }

    protected function getPrimaryKey() {
        return 'staff_type_id';
    }

    private function ensureSchema() {
        if (self::$schemaDone) {
            return;
        }
        self::$schemaDone = true;

        try {
            $this->db->query("CREATE TABLE IF NOT EXISTS `staff_type` (
                `staff_type_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `staff_type_name` VARCHAR(50) NOT NULL,
                `staff_type_description` VARCHAR(255) NULL DEFAULT NULL,
                `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                `is_system` TINYINT(1) NOT NULL DEFAULT 0,
                `sort_order` INT NOT NULL DEFAULT 0,
                `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`staff_type_id`),
                UNIQUE KEY `uq_staff_type_name` (`staff_type_name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            // ENUM -> VARCHAR so new types can be stored (existing values are kept as text).
            $col = $this->db->query("SHOW COLUMNS FROM `staff` LIKE 'staff_type'");
            $info = $col ? $col->fetch_assoc() : null;
            if ($info && stripos((string) ($info['Type'] ?? ''), 'enum') === 0) {
                $this->db->query("ALTER TABLE `staff` MODIFY `staff_type`
                    VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL");
            }

            $count = $this->db->query("SELECT COUNT(*) AS n FROM `staff_type`");
            $row = $count ? $count->fetch_assoc() : null;
            if ($row && (int) $row['n'] === 0) {
                $this->db->query("INSERT INTO `staff_type`
                    (`staff_type_name`, `staff_type_description`, `is_system`, `sort_order`) VALUES
                    ('Permanent', 'Permanent cadre staff. Can hold Cover-up or Acting duties.', 1, 1),
                    ('On Contract', 'Staff employed on a fixed-term contract.', 0, 2),
                    ('Visiting Lecturer', 'Part-time visiting lecturers.', 0, 3)");
            }

            // Make sure every type already used by staff exists in the list.
            $this->db->query("INSERT IGNORE INTO `staff_type` (`staff_type_name`, `sort_order`)
                SELECT DISTINCT s.`staff_type` COLLATE utf8mb4_unicode_ci, 100
                FROM `staff` s
                WHERE s.`staff_type` IS NOT NULL AND s.`staff_type` <> ''");
        } catch (Throwable $e) {
            error_log('StaffTypeModel::ensureSchema: ' . $e->getMessage());
        }
    }

    /**
     * All types with how many staff (and how many working staff) use each one.
     *
     * @return list<array<string, mixed>>
     */
    public function getAllWithUsage() {
        $usage = [];
        try {
            $result = $this->db->query("SELECT `staff_type` AS name, COUNT(*) AS total,
                    SUM(`staff_status` = 'Working') AS working
                FROM `staff` GROUP BY `staff_type`");
            while ($result && ($r = $result->fetch_assoc())) {
                $usage[strtolower((string) $r['name'])] = $r;
            }
        } catch (Throwable $e) {
            error_log('StaffTypeModel::getAllWithUsage: ' . $e->getMessage());
        }

        $rows = $this->all('`sort_order` ASC, `staff_type_name` ASC');
        foreach ($rows as &$row) {
            $u = $usage[strtolower((string) $row['staff_type_name'])] ?? null;
            $row['staff_total'] = $u ? (int) $u['total'] : 0;
            $row['staff_working'] = $u ? (int) $u['working'] : 0;
        }
        unset($row);
        return $rows;
    }

    /**
     * Type names offered in forms. Pass the staff member's current type so an inactive
     * type that is still assigned stays selectable.
     *
     * @return list<string>
     */
    public function getActiveNames($includeCurrent = '') {
        $names = [];
        foreach ($this->all('`sort_order` ASC, `staff_type_name` ASC') as $row) {
            if ((int) $row['is_active'] === 1) {
                $names[] = (string) $row['staff_type_name'];
            }
        }
        $includeCurrent = trim((string) $includeCurrent);
        if ($includeCurrent !== '' && !in_array($includeCurrent, $names, true)) {
            $names[] = $includeCurrent;
        }
        return $names;
    }

    public function isActiveName($name) {
        $name = trim((string) $name);
        if ($name === '') {
            return false;
        }
        $stmt = $this->db->prepare("SELECT 1 FROM `staff_type` WHERE `staff_type_name` = ? AND `is_active` = 1 LIMIT 1");
        $stmt->bind_param('s', $name);
        $stmt->execute();
        return (bool) $stmt->get_result()->fetch_assoc();
    }

    public function nameExists($name, $exceptId = 0) {
        $exceptId = (int) $exceptId;
        $stmt = $this->db->prepare("SELECT 1 FROM `staff_type` WHERE `staff_type_name` = ? AND `staff_type_id` <> ? LIMIT 1");
        $stmt->bind_param('si', $name, $exceptId);
        $stmt->execute();
        return (bool) $stmt->get_result()->fetch_assoc();
    }

    public function usageCount($name) {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS n FROM `staff` WHERE `staff_type` = ?");
        $stmt->bind_param('s', $name);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return (int) ($row['n'] ?? 0);
    }

    /**
     * @return int|false New id or false (see $error)
     */
    public function createType($name, $description, $active, &$error = null) {
        $name = trim((string) $name);
        $err = null;
        $next = $this->db->query("SELECT COALESCE(MAX(`sort_order`), 0) + 1 AS n FROM `staff_type`");
        $nextRow = $next ? $next->fetch_assoc() : null;
        $id = $this->create([
            'staff_type_name' => $name,
            'staff_type_description' => $description !== '' ? $description : null,
            'is_active' => $active ? 1 : 0,
            'is_system' => 0,
            'sort_order' => (int) ($nextRow['n'] ?? 1),
        ], $err);
        if ($id === false) {
            $error = $err;
        }
        return $id;
    }

    /**
     * Update a type. Renaming also renames it on every staff record.
     *
     * @return bool
     */
    public function updateType($id, $name, $description, $active, &$error = null) {
        $existing = $this->find($id);
        if (!$existing) {
            $error = 'Staff type not found.';
            return false;
        }
        $oldName = (string) $existing['staff_type_name'];
        $name = trim((string) $name);
        $isSystem = (int) $existing['is_system'] === 1;

        if ($isSystem) {
            // The name drives business rules, and the type must stay available.
            $name = $oldName;
            $active = true;
        }

        $conn = $this->db->getConnection();
        $conn->begin_transaction();
        try {
            $err = null;
            $ok = $this->update($id, [
                'staff_type_name' => $name,
                'staff_type_description' => $description !== '' ? $description : null,
                'is_active' => $active ? 1 : 0,
            ], $err);
            if (!$ok) {
                throw new RuntimeException($err ?: 'Could not update the staff type.');
            }
            if ($name !== $oldName) {
                $stmt = $this->db->prepare("UPDATE `staff` SET `staff_type` = ? WHERE `staff_type` = ?");
                $stmt->bind_param('ss', $name, $oldName);
                if (!$stmt->execute()) {
                    throw new RuntimeException($stmt->error ?: 'Could not update staff records.');
                }
            }
            $conn->commit();
            return true;
        } catch (Throwable $e) {
            $conn->rollback();
            $error = $e->getMessage();
            return false;
        }
    }

    /**
     * @return bool
     */
    public function deleteType($id, &$error = null) {
        $type = $this->find($id);
        if (!$type) {
            $error = 'Staff type not found.';
            return false;
        }
        if ((int) $type['is_system'] === 1) {
            $error = 'The ' . $type['staff_type_name'] . ' type is required by the system and cannot be deleted.';
            return false;
        }
        $used = $this->usageCount($type['staff_type_name']);
        if ($used > 0) {
            $error = 'Cannot delete "' . $type['staff_type_name'] . '": ' . $used
                . ' staff member' . ($used === 1 ? ' is' : 's are') . ' using it. Reassign them or mark the type inactive.';
            return false;
        }
        return $this->delete($id);
    }
}
