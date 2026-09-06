<?php
/**
 * Persists last Hikvision LAN test results.
 */
declare(strict_types=1);

class HikvisionModel extends Model {
    protected $table = 'hikvision_device_status';

    protected function getPrimaryKey() {
        return 'id';
    }

    public function ensureTable(): void {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->table}` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `device_key` VARCHAR(32) NOT NULL,
            `label` VARCHAR(64) NOT NULL DEFAULT '',
            `ip` VARCHAR(45) NOT NULL,
            `role` VARCHAR(16) NOT NULL DEFAULT 'reader',
            `status` VARCHAR(32) NOT NULL DEFAULT 'UNKNOWN',
            `device_name` VARCHAR(150) NOT NULL DEFAULT '',
            `last_error` VARCHAR(255) NOT NULL DEFAULT '',
            `ping_ok` TINYINT(1) NOT NULL DEFAULT 0,
            `tcp_ok` TINYINT(1) NOT NULL DEFAULT 0,
            `http_ok` TINYINT(1) NOT NULL DEFAULT 0,
            `auth_ok` TINYINT(1) NOT NULL DEFAULT 0,
            `http_code` INT NOT NULL DEFAULT 0,
            `last_seen` DATETIME NULL,
            `checked_at` DATETIME NULL,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_device_key` (`device_key`),
            KEY `idx_ip` (`ip`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->db->query($sql);
    }

    /**
     * @param array<string,mixed> $row from HikvisionService::testDevice
     */
    public function upsertStatus(array $row): void {
        $this->ensureTable();
        $key = trim((string) ($row['key'] ?? ''));
        $ip = trim((string) ($row['ip'] ?? ''));
        if ($key === '' || $ip === '') {
            return;
        }
        $status = (string) ($row['status'] ?? 'UNKNOWN');
        $online = strtoupper($status) === 'ONLINE';
        $lastSeen = $online ? (string) ($row['checked_at'] ?? date('Y-m-d H:i:s')) : null;

        $sql = "INSERT INTO `{$this->table}`
            (`device_key`,`label`,`ip`,`role`,`status`,`device_name`,`last_error`,
             `ping_ok`,`tcp_ok`,`http_ok`,`auth_ok`,`http_code`,`last_seen`,`checked_at`)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,NULLIF(?, ''),?)
            ON DUPLICATE KEY UPDATE
              `label`=VALUES(`label`),
              `ip`=VALUES(`ip`),
              `role`=VALUES(`role`),
              `status`=VALUES(`status`),
              `device_name`=VALUES(`device_name`),
              `last_error`=VALUES(`last_error`),
              `ping_ok`=VALUES(`ping_ok`),
              `tcp_ok`=VALUES(`tcp_ok`),
              `http_ok`=VALUES(`http_ok`),
              `auth_ok`=VALUES(`auth_ok`),
              `http_code`=VALUES(`http_code`),
              `last_seen`=IF(VALUES(`status`)='ONLINE', VALUES(`checked_at`), `last_seen`),
              `checked_at`=VALUES(`checked_at`)";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return;
        }
        $label = (string) ($row['label'] ?? '');
        $role = (string) ($row['role'] ?? '');
        $deviceName = (string) ($row['device_name'] ?? '');
        $lastError = mb_substr((string) ($row['last_error'] ?? ''), 0, 255);
        $ping = !empty($row['ping_ok']) ? 1 : 0;
        $tcp = !empty($row['tcp_ok']) ? 1 : 0;
        $http = !empty($row['http_ok']) ? 1 : 0;
        $auth = !empty($row['auth_ok']) ? 1 : 0;
        $httpCode = (int) ($row['http_code'] ?? 0);
        $checkedAt = (string) ($row['checked_at'] ?? date('Y-m-d H:i:s'));
        $lastSeenVal = $online ? $checkedAt : '';
        // Empty last_seen on insert when offline — ON DUPLICATE keeps previous last_seen
        $stmt->bind_param(
            'sssssssiiiiiss',
            $key,
            $label,
            $ip,
            $role,
            $status,
            $deviceName,
            $lastError,
            $ping,
            $tcp,
            $http,
            $auth,
            $httpCode,
            $lastSeenVal,
            $checkedAt
        );
        $stmt->execute();
        $stmt->close();
    }

    /** @return array<string,array<string,mixed>> keyed by device_key */
    public function allByKey(): array {
        $this->ensureTable();
        $out = [];
        $res = $this->db->query("SELECT * FROM `{$this->table}`");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $out[(string) $row['device_key']] = $row;
            }
            $res->free();
        }
        return $out;
    }
}
