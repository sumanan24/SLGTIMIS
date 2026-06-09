<?php
/**
 * Key/value system settings
 */

class SystemSettingModel extends Model {
    protected $table = 'system_setting';

    protected function getPrimaryKey() {
        return 'setting_key';
    }

    public function ensureTable(): void {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->table}` (
            `setting_key` VARCHAR(64) NOT NULL,
            `setting_value` TEXT NULL,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`setting_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->db->query($sql);
    }

    public function get(string $key, $default = null) {
        $this->ensureTable();
        $row = $this->find($key);
        if (!$row) {
            return $default;
        }
        return $row['setting_value'] ?? $default;
    }

    public function set(string $key, $value): bool {
        $this->ensureTable();
        $conn = $this->db->getConnection();
        $sql = "INSERT INTO `{$this->table}` (`setting_key`, `setting_value`) VALUES (?, ?)
                ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $val = (string) $value;
        $stmt->bind_param('ss', $key, $val);
        return $stmt->execute();
    }
}
