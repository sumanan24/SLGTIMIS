<?php
/**
 * Staff ↔ navbar option assignments (approve workflow)
 */

class StaffNavAssignmentModel extends Model {
    protected $table = 'staff_nav_assignment';

    protected function getPrimaryKey() {
        return 'assignment_id';
    }

    public function ensureTable(): void {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->table}` (
            `assignment_id` INT(11) NOT NULL AUTO_INCREMENT,
            `nav_id` INT(11) NOT NULL,
            `staff_id` VARCHAR(50) NOT NULL,
            `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
            `assigned_by` INT(11) DEFAULT NULL,
            `approved_by` INT(11) DEFAULT NULL,
            `approved_at` DATETIME DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`assignment_id`),
            UNIQUE KEY `uk_nav_staff` (`nav_id`, `staff_id`),
            KEY `idx_staff_status` (`staff_id`, `status`),
            KEY `idx_nav_status` (`nav_id`, `status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->db->query($sql);
    }

    public function getByStaffId(string $staffId): array {
        $this->ensureTable();
        $sql = "SELECT a.*, n.`label` AS nav_label, n.`route_path`, n.`parent_id`
                FROM `{$this->table}` a
                INNER JOIN `staff_nav_menu` n ON n.`nav_id` = a.`nav_id`
                WHERE a.`staff_id` = ?
                ORDER BY n.`sort_order`, n.`label`";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('s', $staffId);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();
        return $rows;
    }

    /**
     * Approved nav item counts per staff (for staff privileges list).
     */
    public function getApprovedCountsByStaff(): array {
        $this->ensureTable();
        $sql = "SELECT `staff_id`, COUNT(*) AS cnt FROM `{$this->table}` WHERE `status` = 'approved' GROUP BY `staff_id`";
        $result = $this->db->query($sql);
        $map = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $map[$row['staff_id']] = (int) $row['cnt'];
            }
        }
        return $map;
    }

    /**
     * Sync navbar options for one staff member.
     */
    public function syncForStaff(string $staffId, array $navIds, int $assignedBy, bool $autoApprove): void {
        $this->ensureTable();
        $navIds = array_values(array_unique(array_filter(array_map('intval', $navIds))));
        $existing = $this->getByStaffId($staffId);
        $existingByNav = [];
        foreach ($existing as $row) {
            $existingByNav[(int) $row['nav_id']] = $row;
        }

        foreach ($navIds as $navId) {
            if ($navId <= 0) {
                continue;
            }
            if (isset($existingByNav[$navId])) {
                if ($autoApprove && $existingByNav[$navId]['status'] !== 'approved') {
                    $this->approve((int) $existingByNav[$navId]['assignment_id'], $assignedBy);
                }
                continue;
            }
            $this->createAssignment($navId, $staffId, $assignedBy, $autoApprove);
        }

        foreach ($existingByNav as $navId => $row) {
            if (!in_array($navId, $navIds, true)) {
                $this->delete((int) $row['assignment_id']);
            }
        }
    }

    public function getByNavId(int $navId): array {
        $this->ensureTable();
        $sql = "SELECT a.*, s.`staff_name`, s.`department_id`, d.`department_name`
                FROM `{$this->table}` a
                INNER JOIN `staff` s ON s.`staff_id` = a.`staff_id`
                LEFT JOIN `department` d ON d.`department_id` = s.`department_id`
                WHERE a.`nav_id` = ?
                ORDER BY s.`staff_name` ASC";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('i', $navId);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();
        return $rows;
    }

    public function getAllForAdmin(?string $status = null): array {
        $this->ensureTable();
        $sql = "SELECT a.*, s.`staff_name`, n.`label` AS nav_label, d.`department_name`
                FROM `{$this->table}` a
                INNER JOIN `staff` s ON s.`staff_id` = a.`staff_id`
                INNER JOIN `staff_nav_menu` n ON n.`nav_id` = a.`nav_id`
                LEFT JOIN `department` d ON d.`department_id` = s.`department_id`";
        $params = [];
        $types = '';
        if ($status !== null && $status !== '') {
            $sql .= " WHERE a.`status` = ?";
            $params[] = $status;
            $types = 's';
        }
        $sql .= " ORDER BY a.`status` ASC, s.`staff_name` ASC, n.`label` ASC";
        if ($params !== []) {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->db->query($sql);
        }
        if (!$result) {
            return [];
        }
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }

    /** @return int[] */
    public function getApprovedNavIdsForStaff(string $staffId): array {
        $this->ensureTable();
        $sql = "SELECT `nav_id` FROM `{$this->table}` WHERE `staff_id` = ? AND `status` = 'approved'";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('s', $staffId);
        $stmt->execute();
        $result = $stmt->get_result();
        $ids = [];
        while ($row = $result->fetch_assoc()) {
            $ids[] = (int) $row['nav_id'];
        }
        $stmt->close();
        return $ids;
    }

    /** Nav items marked staff-only or with at least one assignment */
    public function getStaffRestrictedNavIds(): array {
        $this->ensureTable();
        require_once BASE_PATH . '/models/NavMenuModel.php';
        (new NavMenuModel())->ensureTable();
        $sql = "SELECT DISTINCT `nav_id` FROM `{$this->table}`
                UNION
                SELECT `nav_id` FROM `staff_nav_menu` WHERE `staff_assign_only` = 1";
        $result = $this->db->query($sql);
        if (!$result) {
            return [];
        }
        $ids = [];
        while ($row = $result->fetch_assoc()) {
            $ids[] = (int) $row['nav_id'];
        }
        return $ids;
    }

    public function findAssignment(int $assignmentId): ?array {
        $this->ensureTable();
        $row = $this->find($assignmentId);
        return $row ?: null;
    }

    /**
     * Sync staff checkboxes for a nav item (add new, remove unchecked).
     */
    public function syncForNav(int $navId, array $staffIds, int $assignedBy, bool $autoApprove): void {
        $this->ensureTable();
        $staffIds = array_values(array_unique(array_filter(array_map('strval', $staffIds))));
        $existing = $this->getByNavId($navId);
        $existingByStaff = [];
        foreach ($existing as $row) {
            $existingByStaff[$row['staff_id']] = $row;
        }

        foreach ($staffIds as $staffId) {
            if (isset($existingByStaff[$staffId])) {
                continue;
            }
            $this->createAssignment($navId, $staffId, $assignedBy, $autoApprove);
        }

        foreach ($existingByStaff as $staffId => $row) {
            if (!in_array($staffId, $staffIds, true)) {
                $this->delete((int) $row['assignment_id']);
            }
        }
    }

    /**
     * @return int|false New assignment id on success, false on failure
     */
    public function createAssignment(int $navId, string $staffId, int $assignedBy, bool $autoApprove) {
        $status = $autoApprove ? 'approved' : 'pending';
        $data = [
            'nav_id' => $navId,
            'staff_id' => $staffId,
            'status' => $status,
            'assigned_by' => $assignedBy,
        ];
        if ($autoApprove) {
            $data['approved_by'] = $assignedBy;
            $data['approved_at'] = date('Y-m-d H:i:s');
        }
        return $this->create($data);
    }

    public function approve(int $assignmentId, int $approvedBy): bool {
        $this->ensureTable();
        return $this->update($assignmentId, [
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function reject(int $assignmentId, int $rejectedBy): bool {
        $this->ensureTable();
        return $this->update($assignmentId, [
            'status' => 'rejected',
            'approved_by' => $rejectedBy,
            'approved_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function deleteAssignment(int $assignmentId): bool {
        $this->ensureTable();
        return $this->delete($assignmentId);
    }
}
