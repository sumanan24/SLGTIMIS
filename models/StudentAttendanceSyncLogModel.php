<?php
/**
 * Sync run history for student fingerprint attendance (no credentials / biometrics).
 */
declare(strict_types=1);

class StudentAttendanceSyncLogModel extends Model {
    protected $table = 'student_attendance_sync_logs';

    protected function getPrimaryKey() {
        return 'id';
    }

    public function ensureTable(): void {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->table}` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` INT NULL DEFAULT NULL,
            `username` VARCHAR(100) NOT NULL DEFAULT '',
            `started_at` DATETIME NOT NULL,
            `ended_at` DATETIME NULL DEFAULT NULL,
            `status` VARCHAR(32) NOT NULL DEFAULT 'running',
            `date_from` DATE NULL DEFAULT NULL,
            `date_to` DATE NULL DEFAULT NULL,
            `records_retrieved` INT NOT NULL DEFAULT 0,
            `valid_student` INT NOT NULL DEFAULT 0,
            `staff_ignored` INT NOT NULL DEFAULT 0,
            `empty_person_id` INT NOT NULL DEFAULT 0,
            `unmatched` INT NOT NULL DEFAULT 0,
            `duplicates` INT NOT NULL DEFAULT 0,
            `saved` INT NOT NULL DEFAULT 0,
            `failed` INT NOT NULL DEFAULT 0,
            `error_message` VARCHAR(500) NOT NULL DEFAULT '',
            `machine_id` VARCHAR(64) NOT NULL DEFAULT '',
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_started` (`started_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->db->query($sql);
    }

    public function startLog(?int $userId, string $username, string $dateFrom, string $dateTo, string $machineId): int {
        $this->ensureTable();
        $started = date('Y-m-d H:i:s');
        $status = 'running';
        $stmt = $this->db->prepare(
            "INSERT INTO `{$this->table}`
            (`user_id`, `username`, `started_at`, `status`, `date_from`, `date_to`, `machine_id`)
            VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        if (!$stmt) {
            return 0;
        }
        $uid = $userId !== null && $userId > 0 ? $userId : 0;
        $stmt->bind_param('issssss', $uid, $username, $started, $status, $dateFrom, $dateTo, $machineId);
        $stmt->execute();
        $id = (int) $stmt->insert_id;
        $stmt->close();
        return $id;
    }

    public function finishLog(int $id, array $stats): void {
        if ($id <= 0) {
            return;
        }
        $this->ensureTable();
        $ended = date('Y-m-d H:i:s');
        $status = (string) ($stats['status'] ?? 'ok');
        $err = mb_substr((string) ($stats['error_message'] ?? ''), 0, 500);
        $stmt = $this->db->prepare(
            "UPDATE `{$this->table}` SET
                `ended_at`=?, `status`=?,
                `records_retrieved`=?, `valid_student`=?, `staff_ignored`=?,
                `empty_person_id`=?, `unmatched`=?, `duplicates`=?, `saved`=?, `failed`=?,
                `error_message`=?
             WHERE `id`=?"
        );
        if (!$stmt) {
            return;
        }
        $ret = (int) ($stats['records_retrieved'] ?? 0);
        $valid = (int) ($stats['valid_student'] ?? 0);
        $staff = (int) ($stats['staff_ignored'] ?? 0);
        $empty = (int) ($stats['empty_person_id'] ?? 0);
        $unmatched = (int) ($stats['unmatched'] ?? 0);
        $dup = (int) ($stats['duplicates'] ?? 0);
        $saved = (int) ($stats['saved'] ?? 0);
        $failed = (int) ($stats['failed'] ?? 0);
        $stmt->bind_param(
            'ssiiiiiiiisi',
            $ended,
            $status,
            $ret,
            $valid,
            $staff,
            $empty,
            $unmatched,
            $dup,
            $saved,
            $failed,
            $err,
            $id
        );
        $stmt->execute();
        $stmt->close();
    }

    public function getLastSuccessful(): ?array {
        $this->ensureTable();
        $res = $this->db->query(
            "SELECT * FROM `{$this->table}` WHERE `status` IN ('ok','success') ORDER BY `ended_at` DESC LIMIT 1"
        );
        if ($res && ($row = $res->fetch_assoc())) {
            return $row;
        }
        return null;
    }

    /** @return list<array<string,mixed>> */
    public function recent(int $limit = 50): array {
        $this->ensureTable();
        $limit = max(1, min(200, $limit));
        $rows = [];
        $res = $this->db->query("SELECT * FROM `{$this->table}` ORDER BY `id` DESC LIMIT {$limit}");
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $rows[] = $r;
            }
        }
        return $rows;
    }
}
