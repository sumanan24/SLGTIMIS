<?php
/**
 * Per-staff module permission overrides (View / Add / Edit / Delete).
 */

class StaffModulePermissionModel extends Model {
    protected $table = 'staff_module_permission';

    protected function getPrimaryKey() {
        return 'id';
    }

    public function __construct() {
        parent::__construct();
        $this->ensureTable();
    }

    public function ensureTable() {
        $this->db->query("CREATE TABLE IF NOT EXISTS `{$this->table}` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `staff_id` VARCHAR(64) NOT NULL,
            `module_key` VARCHAR(64) NOT NULL,
            `access_mode` ENUM('inherit','grant','deny','custom') NOT NULL DEFAULT 'inherit',
            `can_view` TINYINT(1) NOT NULL DEFAULT 0,
            `can_add` TINYINT(1) NOT NULL DEFAULT 0,
            `can_edit` TINYINT(1) NOT NULL DEFAULT 0,
            `can_delete` TINYINT(1) NOT NULL DEFAULT 0,
            `updated_by` INT(11) DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_staff_module` (`staff_id`, `module_key`),
            KEY `idx_staff_module_staff` (`staff_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        foreach (['can_upload', 'can_download', 'can_approve'] as $col) {
            $result = $this->db->query("SHOW COLUMNS FROM `{$this->table}` LIKE '{$col}'");
            if ($result && $result->num_rows === 0) {
                $this->db->query("ALTER TABLE `{$this->table}` ADD COLUMN `{$col}` TINYINT(1) NOT NULL DEFAULT 0 AFTER `can_delete`");
            }
        }
    }

    public function getByStaff($staffId) {
        $staffId = trim((string) $staffId);
        if ($staffId === '') {
            return [];
        }
        $stmt = $this->db->prepare("SELECT * FROM `{$this->table}` WHERE `staff_id` = ?");
        $stmt->bind_param('s', $staffId);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[$row['module_key']] = $row;
        }
        return $rows;
    }

    public function resetStaff($staffId) {
        $staffId = trim((string) $staffId);
        $stmt = $this->db->prepare("DELETE FROM `{$this->table}` WHERE `staff_id` = ?");
        $stmt->bind_param('s', $staffId);
        return $stmt->execute();
    }

    public function saveStaff($staffId, array $modules, $updatedBy = null) {
        $staffId = trim((string) $staffId);
        $allowedModes = ['inherit', 'grant', 'deny', 'custom'];
        foreach ($modules as $moduleKey => $payload) {
            $moduleKey = preg_replace('/[^a-z0-9_]/', '', strtolower((string) $moduleKey));
            if ($moduleKey === '') {
                continue;
            }
            $mode = strtolower((string) ($payload['access_mode'] ?? 'inherit'));
            if (!in_array($mode, $allowedModes, true)) {
                $mode = 'inherit';
            }
            if ($mode === 'inherit') {
                $stmt = $this->db->prepare("DELETE FROM `{$this->table}` WHERE `staff_id` = ? AND `module_key` = ?");
                $stmt->bind_param('ss', $staffId, $moduleKey);
                $stmt->execute();
                continue;
            }
            $view = !empty($payload['can_view']) ? 1 : 0;
            $add = !empty($payload['can_add']) ? 1 : 0;
            $edit = !empty($payload['can_edit']) ? 1 : 0;
            $delete = !empty($payload['can_delete']) ? 1 : 0;
            $upload = !empty($payload['can_upload']) ? 1 : 0;
            $download = !empty($payload['can_download']) ? 1 : 0;
            $approve = !empty($payload['can_approve']) ? 1 : 0;
            if ($mode === 'grant') {
                $view = $add = $edit = $delete = $upload = $download = $approve = 1;
            } elseif ($mode === 'deny') {
                $view = $add = $edit = $delete = $upload = $download = $approve = 0;
            }
            $sql = "INSERT INTO `{$this->table}`
                (`staff_id`, `module_key`, `access_mode`, `can_view`, `can_add`, `can_edit`, `can_delete`, `can_upload`, `can_download`, `can_approve`, `updated_by`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    `access_mode` = VALUES(`access_mode`),
                    `can_view` = VALUES(`can_view`),
                    `can_add` = VALUES(`can_add`),
                    `can_edit` = VALUES(`can_edit`),
                    `can_delete` = VALUES(`can_delete`),
                    `can_upload` = VALUES(`can_upload`),
                    `can_download` = VALUES(`can_download`),
                    `can_approve` = VALUES(`can_approve`),
                    `updated_by` = VALUES(`updated_by`)";
            $updatedBy = $updatedBy === null ? 0 : (int) $updatedBy;
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('sssiiiiiiii', $staffId, $moduleKey, $mode, $view, $add, $edit, $delete, $upload, $download, $approve, $updatedBy);
            $stmt->execute();
        }
        return true;
    }
}
