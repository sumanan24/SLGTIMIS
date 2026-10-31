<?php
/**
 * Password-reset OTP storage. Only the hash is stored.
 */
class PasswordResetOtpModel extends Model {
    protected $table = 'password_reset_otps';

    protected function getPrimaryKey() {
        return 'id';
    }

    public function ensureSchema(): void {
        $sql = "CREATE TABLE IF NOT EXISTS `password_reset_otps` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` INT NOT NULL,
            `otp_hash` VARCHAR(255) NOT NULL,
            `expires_at` DATETIME NOT NULL,
            `verified_at` DATETIME NULL DEFAULT NULL,
            `invalidated_at` DATETIME NULL DEFAULT NULL,
            `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
            `ip_address` VARCHAR(45) NOT NULL,
            `campaign_key` VARCHAR(80) NULL DEFAULT NULL,
            `sms_result` VARCHAR(40) NULL DEFAULT NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_pro_user_created` (`user_id`, `created_at`),
            KEY `idx_pro_ip_created` (`ip_address`, `created_at`),
            KEY `idx_pro_expires` (`expires_at`),
            CONSTRAINT `fk_pro_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->db->query($sql);

        $rate = "CREATE TABLE IF NOT EXISTS `password_reset_rate_events` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `ip_address` VARCHAR(45) NOT NULL,
            `event_type` VARCHAR(20) NOT NULL,
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_prr_ip` (`ip_address`, `event_type`, `created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->db->query($rate);
    }

    public function cleanup(): void {
        $this->db->query("DELETE FROM `password_reset_otps` WHERE `created_at` < (NOW() - INTERVAL 1 DAY)");
        $this->db->query("DELETE FROM `password_reset_rate_events` WHERE `created_at` < (NOW() - INTERVAL 1 DAY)");
    }

    public function recordIpEvent(string $ip, string $eventType): void {
        $stmt = $this->db->prepare("INSERT INTO `password_reset_rate_events` (`ip_address`, `event_type`, `created_at`) VALUES (?, ?, NOW())");
        $stmt->bind_param('ss', $ip, $eventType);
        $stmt->execute();
    }

    public function countIpEvents(string $ip, string $eventType, int $minutes): int {
        $minutes = max(1, (int) $minutes);
        $stmt = $this->db->prepare("SELECT COUNT(*) AS c FROM `password_reset_rate_events` WHERE `ip_address` = ? AND `event_type` = ? AND `created_at` >= (NOW() - INTERVAL {$minutes} MINUTE)");
        $stmt->bind_param('ss', $ip, $eventType);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return (int) ($row['c'] ?? 0);
    }

    public function countUserSends(int $userId, int $minutes): int {
        $minutes = max(1, (int) $minutes);
        $stmt = $this->db->prepare("SELECT COUNT(*) AS c FROM `password_reset_otps` WHERE `user_id` = ? AND `created_at` >= (NOW() - INTERVAL {$minutes} MINUTE)");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return (int) ($row['c'] ?? 0);
    }

    public function invalidateActive(int $userId): void {
        $stmt = $this->db->prepare("UPDATE `password_reset_otps` SET `invalidated_at` = NOW(), `updated_at` = NOW() WHERE `user_id` = ? AND `verified_at` IS NULL AND `invalidated_at` IS NULL");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
    }

    public function invalidateAllForUser(int $userId): void {
        $stmt = $this->db->prepare("UPDATE `password_reset_otps` SET `invalidated_at` = IF(`verified_at` IS NULL, NOW(), `invalidated_at`), `updated_at` = NOW() WHERE `user_id` = ? AND (`verified_at` IS NULL OR `invalidated_at` IS NULL)");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt = $this->db->prepare("UPDATE `password_reset_otps` SET `invalidated_at` = NOW(), `updated_at` = NOW() WHERE `user_id` = ? AND `invalidated_at` IS NULL");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
    }

    public function createPending(int $userId, string $otpHash, string $ip, int $ttlSeconds): int {
        $ttlSeconds = max(60, (int) $ttlSeconds);
        $stmt = $this->db->prepare("INSERT INTO `password_reset_otps` (`user_id`, `otp_hash`, `expires_at`, `attempts`, `ip_address`, `created_at`, `updated_at`) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL {$ttlSeconds} SECOND), 0, ?, NOW(), NOW())");
        $stmt->bind_param('iss', $userId, $otpHash, $ip);
        $stmt->execute();
        return (int) $this->db->lastInsertId();
    }

    public function markSmsResult(int $id, ?string $campaignKey, string $smsResult, bool $invalidate): void {
        if ($invalidate) {
            $stmt = $this->db->prepare("UPDATE `password_reset_otps` SET `campaign_key` = ?, `sms_result` = ?, `invalidated_at` = NOW(), `updated_at` = NOW() WHERE `id` = ?");
        } else {
            $stmt = $this->db->prepare("UPDATE `password_reset_otps` SET `campaign_key` = ?, `sms_result` = ?, `updated_at` = NOW() WHERE `id` = ?");
        }
        $key = $campaignKey ?? '';
        $stmt->bind_param('ssi', $key, $smsResult, $id);
        $stmt->execute();
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM `password_reset_otps` WHERE `id` = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: null;
    }

    public function incrementAttempts(int $id): int {
        $stmt = $this->db->prepare("UPDATE `password_reset_otps` SET `attempts` = `attempts` + 1, `updated_at` = NOW() WHERE `id` = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $this->findById($id);
        return (int) ($row['attempts'] ?? 0);
    }

    public function markVerified(int $id): void {
        $stmt = $this->db->prepare("UPDATE `password_reset_otps` SET `verified_at` = NOW(), `updated_at` = NOW() WHERE `id` = ? AND `verified_at` IS NULL");
        $stmt->bind_param('i', $id);
        $stmt->execute();
    }

    public function markExpiredAttempts(int $id): void {
        $stmt = $this->db->prepare("UPDATE `password_reset_otps` SET `invalidated_at` = NOW(), `updated_at` = NOW() WHERE `id` = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
    }

    /**
     * Locate a student or staff login without revealing which check failed.
     *
     * @return array{ok:bool,reason:string,user_id:int,mobile:string}
     */
    public function findAccount(string $identifier): array {
        $identifier = strtoupper(trim($identifier));
        $identifier = preg_replace('/\s+/', '', $identifier) ?? '';
        $denied = ['ok' => false, 'reason' => 'not_found', 'user_id' => 0, 'mobile' => ''];
        if ($identifier === '' || strlen($identifier) > 64) {
            $denied['reason'] = 'invalid';
            return $denied;
        }

        $ids = $this->userIdsByUsername($identifier);
        if (count($ids) > 1) {
            $denied['reason'] = 'ambiguous';
            return $denied;
        }
        if (count($ids) === 0) {
            $ids = array_values(array_unique(array_merge(
                $this->userIdsByStudentNic($identifier),
                $this->userIdsByStaffNic($identifier)
            )));
        }
        if (count($ids) !== 1) {
            $denied['reason'] = count($ids) > 1 ? 'ambiguous' : 'not_found';
            return $denied;
        }

        $userId = $ids[0];
        $stmt = $this->db->prepare("SELECT `user_id`, `user_name`, `user_table`, `user_active`, `account_locked` FROM `user` WHERE `user_id` = ? LIMIT 1");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        if (!$user) {
            return $denied;
        }
        if ((int) ($user['account_locked'] ?? 0) === 1) {
            $denied['reason'] = 'locked';
            $denied['user_id'] = $userId;
            return $denied;
        }
        if ((int) ($user['user_active'] ?? 0) !== 1) {
            $denied['reason'] = 'inactive';
            $denied['user_id'] = $userId;
            return $denied;
        }

        $table = (string) ($user['user_table'] ?? '');
        $username = (string) ($user['user_name'] ?? '');
        if ($table === 'student') {
            $profile = $this->studentProfile($username);
            if ($profile === null || strcasecmp((string) ($profile['student_status'] ?? ''), 'Active') !== 0) {
                $denied['reason'] = 'inactive';
                $denied['user_id'] = $userId;
                return $denied;
            }
            $mobile = $this->firstValidMobile([
                $profile['student_phone'] ?? '',
                $profile['student_whatsapp'] ?? '',
            ]);
        } elseif ($table === 'staff') {
            $profile = $this->staffProfile($username);
            $status = (string) ($profile['staff_status'] ?? '');
            if ($profile === null || ($status !== '' && !in_array($status, ['Working', 'Active'], true))) {
                $denied['reason'] = 'inactive';
                $denied['user_id'] = $userId;
                return $denied;
            }
            $mobile = $this->firstValidMobile([$profile['staff_pno'] ?? '']);
        } else {
            $denied['reason'] = 'inactive';
            $denied['user_id'] = $userId;
            return $denied;
        }

        if ($mobile === null) {
            $denied['reason'] = 'no_phone';
            $denied['user_id'] = $userId;
            return $denied;
        }

        return ['ok' => true, 'reason' => 'ok', 'user_id' => $userId, 'mobile' => $mobile];
    }

    public function mobileForUser(int $userId): ?string {
        $stmt = $this->db->prepare("SELECT `user_name`, `user_table`, `user_active` FROM `user` WHERE `user_id` = ? LIMIT 1");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        if (!$user || (int) ($user['user_active'] ?? 0) !== 1) {
            return null;
        }
        $username = (string) $user['user_name'];
        if (($user['user_table'] ?? '') === 'student') {
            $profile = $this->studentProfile($username);
            return $profile ? $this->firstValidMobile([$profile['student_phone'] ?? '', $profile['student_whatsapp'] ?? '']) : null;
        }
        if (($user['user_table'] ?? '') === 'staff') {
            $profile = $this->staffProfile($username);
            return $profile ? $this->firstValidMobile([$profile['staff_pno'] ?? '']) : null;
        }
        return null;
    }

    public static function maskMobile(string $local): string {
        $digits = preg_replace('/\D+/', '', $local) ?? '';
        if (strlen($digits) === 9 && $digits[0] === '7') {
            $digits = '0' . $digits;
        }
        if (strlen($digits) === 11 && strpos($digits, '94') === 0) {
            $digits = '0' . substr($digits, 2);
        }
        if (strlen($digits) < 6) {
            return '***';
        }
        return substr($digits, 0, 3) . '*****' . substr($digits, -3);
    }

    public static function normalizeLocal(string $raw): ?string {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        if (strlen($digits) === 11 && strpos($digits, '94') === 0) {
            $digits = '0' . substr($digits, 2);
        } elseif (strlen($digits) === 9 && $digits[0] === '7') {
            $digits = '0' . $digits;
        }
        if (!preg_match('/^07\d{8}$/', $digits)) {
            return null;
        }
        return $digits;
    }

    /**
     * @return int[]
     */
    private function userIdsByUsername(string $identifier): array {
        $stmt = $this->db->prepare("SELECT `user_id` FROM `user` WHERE UPPER(REPLACE(`user_name`, ' ', '')) = ? LIMIT 2");
        $stmt->bind_param('s', $identifier);
        $stmt->execute();
        return $this->collectIds($stmt->get_result());
    }

    /**
     * @return int[]
     */
    private function userIdsByStudentNic(string $nic): array {
        $stmt = $this->db->prepare("SELECT u.`user_id` FROM `student` s INNER JOIN `user` u ON u.`user_table` = 'student' AND u.`user_name` = s.`student_id` WHERE UPPER(REPLACE(REPLACE(s.`student_nic`, ' ', ''), '-', '')) = ? LIMIT 2");
        $stmt->bind_param('s', $nic);
        $stmt->execute();
        return $this->collectIds($stmt->get_result());
    }

    /**
     * @return int[]
     */
    private function userIdsByStaffNic(string $nic): array {
        $stmt = $this->db->prepare("SELECT u.`user_id` FROM `staff` s INNER JOIN `user` u ON u.`user_table` = 'staff' AND u.`user_name` = s.`staff_id` WHERE UPPER(REPLACE(REPLACE(s.`staff_nic`, ' ', ''), '-', '')) = ? LIMIT 2");
        $stmt->bind_param('s', $nic);
        $stmt->execute();
        return $this->collectIds($stmt->get_result());
    }

    /**
     * @param mysqli_result $result
     * @return int[]
     */
    private function collectIds($result): array {
        $ids = [];
        while ($row = $result->fetch_assoc()) {
            $ids[] = (int) $row['user_id'];
        }
        return $ids;
    }

    private function studentProfile(string $username): ?array {
        $stmt = $this->db->prepare("SELECT `student_status`, `student_phone`, `student_whatsapp` FROM `student` WHERE `student_id` = ? OR UPPER(REPLACE(REPLACE(`student_nic`, ' ', ''), '-', '')) = UPPER(?) LIMIT 1");
        $stmt->bind_param('ss', $username, $username);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: null;
    }

    private function staffProfile(string $username): ?array {
        $stmt = $this->db->prepare("SELECT `staff_status`, `staff_pno` FROM `staff` WHERE `staff_id` = ? OR UPPER(REPLACE(REPLACE(`staff_nic`, ' ', ''), '-', '')) = UPPER(?) LIMIT 1");
        $stmt->bind_param('ss', $username, $username);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: null;
    }

    /**
     * @param array<int, mixed> $candidates
     */
    private function firstValidMobile(array $candidates): ?string {
        foreach ($candidates as $candidate) {
            $local = self::normalizeLocal((string) $candidate);
            if ($local !== null) {
                return $local;
            }
        }
        return null;
    }
}
