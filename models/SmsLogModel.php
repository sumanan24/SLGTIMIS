<?php
/**
 * Local SMS outbox / inbox for ADM review.
 */

class SmsLogModel extends Model {
    protected $table = 'sms_outbox';

    protected function getPrimaryKey() {
        return 'id';
    }

    public function __construct() {
        parent::__construct();
        $this->ensureTables();
    }

    public function ensureTables(): void {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS `sms_outbox` (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `batch_id` VARCHAR(40) DEFAULT NULL,
              `mobile` VARCHAR(20) NOT NULL,
              `message` TEXT NOT NULL,
              `source` VARCHAR(40) NOT NULL DEFAULT 'unknown',
              `sent_by` VARCHAR(64) DEFAULT NULL,
              `result_code` VARCHAR(20) DEFAULT NULL,
              `status` VARCHAR(20) NOT NULL DEFAULT 'unknown',
              `gateway_message_id` VARCHAR(64) DEFAULT NULL,
              `time_sent` DATETIME DEFAULT NULL,
              `time_delivered` DATETIME DEFAULT NULL,
              `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `idx_sms_outbox_created` (`created_at`),
              KEY `idx_sms_outbox_mobile` (`mobile`),
              KEY `idx_sms_outbox_gateway` (`gateway_message_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS `sms_inbox` (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `gateway_message_id` VARCHAR(64) DEFAULT NULL,
              `mobile` VARCHAR(20) NOT NULL,
              `message` TEXT NOT NULL,
              `received_at` DATETIME DEFAULT NULL,
              `channel` VARCHAR(40) DEFAULT NULL,
              `raw_json` MEDIUMTEXT DEFAULT NULL,
              `fetched_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `idx_sms_inbox_fetched` (`fetched_at`),
              KEY `idx_sms_inbox_mobile` (`mobile`),
              KEY `idx_sms_inbox_gateway` (`gateway_message_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    /**
     * @param list<string> $mobiles
     */
    public function logOutboundBatch(
        array $mobiles,
        string $message,
        string $source,
        ?string $sentBy,
        string $status,
        ?string $resultCode,
        ?string $batchId = null
    ): void {
        if ($mobiles === []) {
            return;
        }
        $batchId = $batchId ?: bin2hex(random_bytes(8));
        $sql = "INSERT INTO `sms_outbox`
                (`batch_id`, `mobile`, `message`, `source`, `sent_by`, `result_code`, `status`, `time_sent`)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log('SmsLogModel::logOutboundBatch prepare: ' . ($this->db->getConnection()->error ?? ''));
            return;
        }
        $sentByVal = $sentBy !== null ? $sentBy : '';
        $codeVal = $resultCode !== null ? $resultCode : '';
        foreach ($mobiles as $mobile) {
            $mobile = trim((string) $mobile);
            if ($mobile === '') {
                continue;
            }
            $stmt->bind_param(
                'sssssss',
                $batchId,
                $mobile,
                $message,
                $source,
                $sentByVal,
                $codeVal,
                $status
            );
            $stmt->execute();
        }
    }

    /**
     * Upsert a row from Mobitel getSentMessageReports.
     *
     * @param array{gateway_message_id?:string,mobile:string,message:string,status?:string,time_sent?:?string,time_delivered?:?string,alias?:string} $row
     */
    public function upsertGatewaySent(array $row): bool {
        $gid = trim((string) ($row['gateway_message_id'] ?? ''));
        $mobile = trim((string) ($row['mobile'] ?? ''));
        $message = (string) ($row['message'] ?? '');
        if ($mobile === '' || $message === '') {
            return false;
        }
        if ($gid !== '') {
            $check = $this->db->prepare(
                "SELECT `id` FROM `sms_outbox` WHERE `gateway_message_id` = ? LIMIT 1"
            );
            if ($check) {
                $check->bind_param('s', $gid);
                $check->execute();
                if ($check->get_result()->fetch_assoc()) {
                    $upd = $this->db->prepare(
                        "UPDATE `sms_outbox` SET `status` = ?, `time_sent` = COALESCE(?, `time_sent`),
                         `time_delivered` = COALESCE(?, `time_delivered`) WHERE `gateway_message_id` = ?"
                    );
                    if ($upd) {
                        $status = (string) ($row['status'] ?? 'gateway');
                        $ts = $row['time_sent'] ?? null;
                        $td = $row['time_delivered'] ?? null;
                        $upd->bind_param('ssss', $status, $ts, $td, $gid);
                        return $upd->execute();
                    }
                    return false;
                }
            }
        }
        $sql = "INSERT INTO `sms_outbox`
                (`mobile`, `message`, `source`, `status`, `gateway_message_id`, `time_sent`, `time_delivered`, `result_code`)
                VALUES (?, ?, 'gateway_sync', ?, ?, ?, ?, NULL)";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $status = (string) ($row['status'] ?? 'gateway');
        $gidVal = $gid !== '' ? $gid : '';
        $ts = (string) ($row['time_sent'] ?? '');
        $td = (string) ($row['time_delivered'] ?? '');
        $tsVal = $ts !== '' ? $ts : null;
        $tdVal = $td !== '' ? $td : null;
        // mysqli needs variables by reference; empty gateway id stored as NULL via COALESCE-style empty.
        if ($gidVal === '') {
            $gidVal = null;
        }
        $stmt->bind_param('ssssss', $mobile, $message, $status, $gidVal, $tsVal, $tdVal);
        return $stmt->execute();
    }

    /**
     * @param array{gateway_message_id?:string,mobile:string,message:string,received_at?:?string,channel?:string,raw_json?:?string} $row
     */
    public function upsertInbox(array $row): bool {
        $mobile = trim((string) ($row['mobile'] ?? ''));
        $message = (string) ($row['message'] ?? '');
        if ($mobile === '' || $message === '') {
            return false;
        }
        $gid = trim((string) ($row['gateway_message_id'] ?? ''));
        $received = $row['received_at'] ?? null;
        $channel = (string) ($row['channel'] ?? '');
        $raw = $row['raw_json'] ?? null;

        if ($gid !== '') {
            $check = $this->db->prepare(
                "SELECT `id` FROM `sms_inbox` WHERE `gateway_message_id` = ? AND `mobile` = ? LIMIT 1"
            );
            if ($check) {
                $check->bind_param('ss', $gid, $mobile);
                $check->execute();
                if ($check->get_result()->fetch_assoc()) {
                    return true;
                }
            }
        }

        $sql = "INSERT INTO `sms_inbox`
                (`gateway_message_id`, `mobile`, `message`, `received_at`, `channel`, `raw_json`)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $gidNull = $gid !== '' ? $gid : null;
        $stmt->bind_param('ssssss', $gidNull, $mobile, $message, $received, $channel, $raw);
        return $stmt->execute();
    }

    /**
     * @return array{rows:list<array<string,mixed>>,total:int}
     */
    public function listOutbox(int $page = 1, int $perPage = 50, string $search = ''): array {
        $page = max(1, $page);
        $perPage = max(1, min(200, $perPage));
        $offset = ($page - 1) * $perPage;
        $where = '1=1';
        $params = [];
        $types = '';
        if ($search !== '') {
            $like = '%' . $search . '%';
            $where .= ' AND (`mobile` LIKE ? OR `message` LIKE ? OR `sent_by` LIKE ? OR `source` LIKE ?)';
            $params = [$like, $like, $like, $like];
            $types = 'ssss';
        }
        $countSql = "SELECT COUNT(*) AS c FROM `sms_outbox` WHERE {$where}";
        $total = 0;
        if ($params !== []) {
            $cst = $this->db->prepare($countSql);
            $cst->bind_param($types, ...$params);
            $cst->execute();
            $total = (int) ($cst->get_result()->fetch_assoc()['c'] ?? 0);
        } else {
            $cr = $this->db->query($countSql);
            $total = (int) ($cr->fetch_assoc()['c'] ?? 0);
        }

        $sql = "SELECT * FROM `sms_outbox` WHERE {$where} ORDER BY `created_at` DESC, `id` DESC LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;
        $types .= 'ii';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
        return ['rows' => $rows, 'total' => $total];
    }

    /**
     * @return array{rows:list<array<string,mixed>>,total:int}
     */
    public function listInbox(int $page = 1, int $perPage = 50, string $search = ''): array {
        $page = max(1, $page);
        $perPage = max(1, min(200, $perPage));
        $offset = ($page - 1) * $perPage;
        $where = '1=1';
        $params = [];
        $types = '';
        if ($search !== '') {
            $like = '%' . $search . '%';
            $where .= ' AND (`mobile` LIKE ? OR `message` LIKE ?)';
            $params = [$like, $like];
            $types = 'ss';
        }
        $countSql = "SELECT COUNT(*) AS c FROM `sms_inbox` WHERE {$where}";
        if ($params !== []) {
            $cst = $this->db->prepare($countSql);
            $cst->bind_param($types, ...$params);
            $cst->execute();
            $total = (int) ($cst->get_result()->fetch_assoc()['c'] ?? 0);
        } else {
            $cr = $this->db->query($countSql);
            $total = (int) ($cr->fetch_assoc()['c'] ?? 0);
        }

        $sql = "SELECT * FROM `sms_inbox` WHERE {$where}
                ORDER BY COALESCE(`received_at`, `fetched_at`) DESC, `id` DESC LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;
        $types .= 'ii';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
        return ['rows' => $rows, 'total' => $total];
    }
}
