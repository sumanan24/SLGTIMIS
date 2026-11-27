<?php
/**
 * LDAP sync audit log and retry queue.
 * Passwords are encrypted in the queue and are never written to the log.
 */
declare(strict_types=1);

class SisLdapSync
{
    private static bool $applying = false;

    /** @var list<string> */
    public const INACTIVE_STAFF = ['terminated', 'resigned', 'retired', 'inactive', 'left', '0'];

    public static function isApplying(): bool
    {
        return self::$applying;
    }

    public static function ensureSchema(): void
    {
        $db = Database::getInstance();
        $db->query(
            "CREATE TABLE IF NOT EXISTS `ldap_sync_queue` (
                `id` bigint unsigned NOT NULL AUTO_INCREMENT,
                `user_id` int NOT NULL DEFAULT 0,
                `username` varchar(191) NOT NULL,
                `action` varchar(32) NOT NULL,
                `status` varchar(16) NOT NULL DEFAULT 'pending',
                `attempts` int NOT NULL DEFAULT 0,
                `last_error` varchar(500) NOT NULL DEFAULT '',
                `secret_payload` text DEFAULT NULL,
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_ldap_queue_status` (`status`, `id`),
                KEY `idx_ldap_queue_user` (`username`, `action`, `status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
        $db->query(
            "CREATE TABLE IF NOT EXISTS `ldap_sync_log` (
                `id` bigint unsigned NOT NULL AUTO_INCREMENT,
                `username` varchar(191) NOT NULL,
                `action` varchar(32) NOT NULL,
                `result` varchar(16) NOT NULL,
                `message` varchar(500) NOT NULL DEFAULT '',
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_ldap_log_created` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }

    public static function note(string $username, string $action, string $message): void
    {
        self::log(trim($username), self::action($action), 'review', self::safeMessage($message));
    }

    public static function recordSuccess(string $username, string $action, string $message = ''): void
    {
        $username = trim($username);
        $action = self::action($action);
        self::log($username, $action, 'ok', $message);
        try {
            self::ensureSchema();
            $db = Database::getInstance();
            $status = 'done';
            $stmt = $db->prepare(
                "UPDATE `ldap_sync_queue`
                 SET `status` = ?, `secret_payload` = NULL, `last_error` = ''
                 WHERE `username` = ? AND `action` = ? AND `status` IN ('pending', 'failed')"
            );
            if ($stmt) {
                $stmt->bind_param('sss', $status, $username, $action);
                $stmt->execute();
                $stmt->close();
            }
        } catch (Throwable $e) {
            error_log('SisLdapSync recordSuccess: ' . $e->getMessage());
        }
    }

    public static function recordFailure(string $username, string $action, string $message, ?string $secret = null): void
    {
        $username = trim($username);
        $action = self::action($action);
        $message = self::safeMessage($message);
        self::log($username, $action, 'failed', $message);
        if (self::$applying || $username === '') {
            return;
        }
        try {
            self::ensureSchema();
            $payload = null;
            if ($secret !== null && $secret !== '') {
                $payload = self::seal($secret);
                if ($payload === null) {
                    self::log($username, $action, 'failed', 'Password retry was not queued because it could not be encrypted.');
                }
            }
            $payload = $payload ?? '';
            $db = Database::getInstance();
            $existing = $db->prepare(
                "SELECT `id` FROM `ldap_sync_queue`
                 WHERE `username` = ? AND `action` = ? AND `status` IN ('pending', 'failed')
                 ORDER BY `id` DESC LIMIT 1"
            );
            $id = 0;
            if ($existing) {
                $existing->bind_param('ss', $username, $action);
                $existing->execute();
                $row = $existing->get_result()->fetch_assoc();
                $existing->close();
                $id = (int) ($row['id'] ?? 0);
            }
            if ($id > 0) {
                $stmt = $db->prepare(
                    "UPDATE `ldap_sync_queue`
                     SET `status` = 'pending', `attempts` = 0, `last_error` = ?, `secret_payload` = ?
                     WHERE `id` = ?"
                );
                if ($stmt) {
                    $stmt->bind_param('ssi', $message, $payload, $id);
                    $stmt->execute();
                    $stmt->close();
                }
                return;
            }
            $stmt = $db->prepare(
                "INSERT INTO `ldap_sync_queue` (`username`, `action`, `status`, `last_error`, `secret_payload`)
                 VALUES (?, ?, 'pending', ?, ?)"
            );
            if ($stmt) {
                $stmt->bind_param('ssss', $username, $action, $message, $payload);
                $stmt->execute();
                $stmt->close();
            }
        } catch (Throwable $e) {
            error_log('SisLdapSync recordFailure: ' . $e->getMessage());
        }
    }

    /**
     * @return array{done:int,failed:int,skipped:int}
     */
    public static function processQueue(): array
    {
        $done = 0;
        $failed = 0;
        $skipped = 0;
        try {
            self::ensureSchema();
            $db = Database::getInstance();
            $result = $db->query(
                "SELECT `id`, `username`, `action`, `attempts`, `secret_payload`
                 FROM `ldap_sync_queue`
                 WHERE `status` IN ('pending', 'failed') AND `attempts` < 8
                 ORDER BY `id` ASC
                 LIMIT 20"
            );
            if (!$result) {
                return ['done' => 0, 'failed' => 0, 'skipped' => 0];
            }
            while ($row = $result->fetch_assoc()) {
                $id = (int) $row['id'];
                $username = (string) $row['username'];
                $action = (string) $row['action'];
                $attempts = (int) $row['attempts'] + 1;
                $secret = null;
                if (!empty($row['secret_payload'])) {
                    $secret = self::open((string) $row['secret_payload']);
                    if ($secret === null && in_array($action, ['password', 'provision'], true)) {
                        self::finish($id, 'failed', $attempts, 'Stored password could not be decrypted. Change the password again in SIS.');
                        $failed++;
                        continue;
                    }
                }
                try {
                    self::$applying = true;
                    self::apply($username, $action, $secret);
                    self::finish($id, 'done', $attempts, '');
                    $done++;
                } catch (Throwable $e) {
                    $message = self::safeMessage($e->getMessage());
                    $status = $attempts >= 8 ? 'failed' : 'pending';
                    self::finish($id, $status, $attempts, $message);
                    self::log($username, $action, 'failed', $message);
                    $failed++;
                } finally {
                    self::$applying = false;
                }
            }
        } catch (Throwable $e) {
            error_log('SisLdapSync processQueue: ' . $e->getMessage());
            $skipped++;
        }
        return ['done' => $done, 'failed' => $failed, 'skipped' => $skipped];
    }

    /**
     * @return array{pending:int,failed:int,done:int,recent:list<array{username:string,action:string,result:string,message:string,created_at:string}>}
     */
    public static function dashboard(): array
    {
        $out = ['pending' => 0, 'failed' => 0, 'done' => 0, 'recent' => []];
        try {
            self::ensureSchema();
            $db = Database::getInstance();
            $counts = $db->query("SELECT `status`, COUNT(*) AS n FROM `ldap_sync_queue` GROUP BY `status`");
            if ($counts) {
                while ($row = $counts->fetch_assoc()) {
                    $status = (string) ($row['status'] ?? '');
                    if (isset($out[$status])) {
                        $out[$status] = (int) $row['n'];
                    }
                }
            }
            $logs = $db->query(
                "SELECT `username`, `action`, `result`, `message`, `created_at`
                 FROM `ldap_sync_log`
                 ORDER BY `id` DESC
                 LIMIT 12"
            );
            if ($logs) {
                while ($row = $logs->fetch_assoc()) {
                    $out['recent'][] = [
                        'username' => (string) ($row['username'] ?? ''),
                        'action' => (string) ($row['action'] ?? ''),
                        'result' => (string) ($row['result'] ?? ''),
                        'message' => (string) ($row['message'] ?? ''),
                        'created_at' => (string) ($row['created_at'] ?? ''),
                    ];
                }
            }
        } catch (Throwable $e) {
            error_log('SisLdapSync dashboard: ' . $e->getMessage());
        }
        return $out;
    }

    /**
     * Compare SIS accounts with LDAP. Does not write to LDAP or change passwords.
     *
     * @return array<string,mixed>
     */
    public static function dryRun(): array
    {
        require_once BASE_PATH . '/helpers/SisLdap.php';
        $accounts = self::sisAccounts();
        $duplicateKeys = [];
        $seen = [];
        foreach ($accounts as $account) {
            $key = SisLdap::uidKey($account['username']);
            if ($key === '') {
                continue;
            }
            if (isset($seen[$key])) {
                $duplicateKeys[$key] = true;
            }
            $seen[$key] = true;
        }

        $ldapError = '';
        $index = [];
        try {
            $index = (new SisLdapClient())->uidIndex();
        } catch (Throwable $e) {
            $ldapError = $e->getMessage();
        }

        $active = 0;
        $inactive = 0;
        $matched = 0;
        $missing = 0;
        $waiting = 0;
        $disabledInLdap = 0;
        $missingNames = [];
        $duplicateNames = [];
        foreach ($accounts as $account) {
            if ($account['active']) {
                $active++;
            } else {
                $inactive++;
            }
            $key = SisLdap::uidKey($account['username']);
            if (isset($duplicateKeys[$key])) {
                $duplicateNames[] = $account['username'];
            }
            if ($ldapError !== '') {
                continue;
            }
            if (!isset($index[$key])) {
                if ($account['active']) {
                    $missing++;
                    if (count($missingNames) < 50) {
                        $missingNames[] = $account['username'];
                    }
                }
                continue;
            }
            $matched++;
            if (empty($index[$key]['has_password'])) {
                $waiting++;
            }
            if (($index[$key]['account'] ?? '') === 'disabled') {
                $disabledInLdap++;
            }
        }

        $ldapOnly = 0;
        if ($ldapError === '') {
            foreach (array_keys($index) as $key) {
                if (!isset($seen[$key])) {
                    $ldapOnly++;
                }
            }
        }

        $queue = self::dashboard();
        $report = [
            'generated_at' => date('c'),
            'sis_accounts' => count($accounts),
            'sis_active' => $active,
            'sis_inactive' => $inactive,
            'ldap_entries' => $ldapError === '' ? count($index) : null,
            'matched' => $ldapError === '' ? $matched : null,
            'missing_ldap' => $ldapError === '' ? $missing : null,
            'missing_sample' => $missingNames,
            'duplicate_usernames' => array_values(array_unique($duplicateNames)),
            'waiting_for_password' => $ldapError === '' ? $waiting : null,
            'disabled_in_ldap' => $ldapError === '' ? $disabledInLdap : null,
            'ldap_not_in_sis' => $ldapError === '' ? $ldapOnly : null,
            'queue_pending' => $queue['pending'],
            'queue_failed' => $queue['failed'],
            'ldap_error' => $ldapError,
            'moodle' => 'Not queried. Match Moodle usernames to SIS usernames before changing Moodle authentication. Do not create a second Moodle account.',
            'writes' => 'none',
        ];
        self::writeReport($report);
        return $report;
    }

    /**
     * @return list<array{username:string,active:bool,type:string}>
     */
    public static function sisAccounts(): array
    {
        $db = Database::getInstance();
        $sql = "SELECT u.`user_name`, u.`user_table`, u.`user_active`, st.`staff_status`
                FROM `user` u
                LEFT JOIN `staff` st ON u.`user_table` = 'staff' AND st.`staff_id` = u.`user_name`
                WHERE u.`user_table` IN ('student', 'staff')
                  AND u.`user_name` IS NOT NULL
                  AND u.`user_name` <> ''
                ORDER BY u.`user_name` ASC";
        $result = $db->query($sql);
        $rows = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $type = (string) ($row['user_table'] ?? '');
                $status = strtolower(trim((string) ($row['staff_status'] ?? '')));
                $active = (int) ($row['user_active'] ?? 0) === 1;
                if ($type === 'staff' && $status !== '' && in_array($status, self::INACTIVE_STAFF, true)) {
                    $active = false;
                }
                $rows[] = [
                    'username' => (string) $row['user_name'],
                    'active' => $active,
                    'type' => $type,
                ];
            }
        }
        return $rows;
    }

    /**
     * @return array{checked:int,changed:int,errors:int,message:string}
     */
    public static function reconcileStates(): array
    {
        require_once BASE_PATH . '/helpers/SisLdap.php';
        $changed = 0;
        $errors = 0;
        try {
            $client = new SisLdapClient();
            $index = $client->uidIndex();
            $checked = 0;
            foreach (self::sisAccounts() as $account) {
                $key = SisLdap::uidKey($account['username']);
                if (!isset($index[$key])) {
                    continue;
                }
                $checked++;
                $current = (string) ($index[$key]['account'] ?? 'unknown');
                $wantActive = $account['active'];
                if (!$wantActive && $current === 'disabled') {
                    continue;
                }
                if ($wantActive && $current !== 'disabled') {
                    continue;
                }
                try {
                    $client->setAccountState($account['username'], $wantActive);
                    $changed++;
                } catch (Throwable $e) {
                    $errors++;
                    self::recordFailure($account['username'], $wantActive ? 'enable' : 'disable', $e->getMessage(), null);
                }
            }
            return [
                'checked' => $checked,
                'changed' => $changed,
                'errors' => $errors,
                'message' => $changed . ' LDAP account state(s) updated. Entries were not deleted.',
            ];
        } catch (Throwable $e) {
            return [
                'checked' => 0,
                'changed' => 0,
                'errors' => 1,
                'message' => 'LDAP account state was not updated. ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Update name, department and user type on existing LDAP entries. Passwords are not touched.
     *
     * @return array{updated:int,skipped:int,errors:int,message:string}
     */
    public static function syncAttributes(): array
    {
        require_once BASE_PATH . '/helpers/SisLdap.php';
        $updated = 0;
        $skipped = 0;
        $errors = 0;
        try {
            $client = new SisLdapClient();
            foreach (SisLdap::activeAccounts() as $profile) {
                try {
                    if ($client->syncIdentity($profile)) {
                        $updated++;
                    } else {
                        $skipped++;
                    }
                } catch (Throwable $e) {
                    $errors++;
                    self::recordFailure($profile['username'], 'attributes', $e->getMessage(), null);
                }
            }
            return [
                'updated' => $updated,
                'skipped' => $skipped,
                'errors' => $errors,
                'message' => $updated . ' LDAP profile(s) updated. Passwords were not changed.',
            ];
        } catch (Throwable $e) {
            return [
                'updated' => 0,
                'skipped' => 0,
                'errors' => 1,
                'message' => 'LDAP profiles were not updated. ' . $e->getMessage(),
            ];
        }
    }

    /** @return array{ok:bool,detail:string} */
    public static function selfTest(): array
    {
        $hash = SisLdapClient::ssha('self-test-secret');
        if (!str_starts_with($hash, '{SSHA}')) {
            return ['ok' => false, 'detail' => 'SSHA prefix missing'];
        }
        $raw = base64_decode(substr($hash, 6), true);
        if ($raw === false || strlen($raw) < 21) {
            return ['ok' => false, 'detail' => 'SSHA decode failed'];
        }
        $salt = substr($raw, 20);
        $digest = substr($raw, 0, 20);
        if (!hash_equals($digest, sha1('self-test-secret' . $salt, true))) {
            return ['ok' => false, 'detail' => 'SSHA digest mismatch'];
        }
        $sealed = self::seal('queue-secret');
        if ($sealed === null || self::open($sealed) !== 'queue-secret') {
            return ['ok' => false, 'detail' => 'Queue encryption round-trip failed'];
        }
        if (self::safeMessage('bind failed password=queue-secret') !== 'bind failed [redacted]') {
            return ['ok' => false, 'detail' => 'Password redaction failed'];
        }
        return ['ok' => true, 'detail' => 'SSHA, queue encryption and log redaction passed'];
    }

    private static function apply(string $username, string $action, ?string $secret): void
    {
        require_once BASE_PATH . '/helpers/SisLdap.php';
        if ($action === 'password') {
            if ($secret === null || $secret === '') {
                throw new RuntimeException('Password retry has no stored secret. Change the password again in SIS.');
            }
            SisLdap::syncPassword($username, $secret);
            return;
        }
        if ($action === 'provision') {
            SisLdap::addIfMissing($username, $secret);
            return;
        }
        if ($action === 'disable') {
            SisLdap::syncActiveState($username, false);
            return;
        }
        if ($action === 'enable') {
            SisLdap::syncActiveState($username, true);
            return;
        }
        if ($action === 'attributes') {
            SisLdap::syncProfileAttributes($username);
            return;
        }
        throw new RuntimeException('Unknown LDAP sync action.');
    }

    private static function finish(int $id, string $status, int $attempts, string $message): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "UPDATE `ldap_sync_queue`
             SET `status` = ?, `attempts` = ?, `last_error` = ?, `secret_payload` = IF(? = 'done', NULL, `secret_payload`)
             WHERE `id` = ?"
        );
        if ($stmt) {
            $stmt->bind_param('sissi', $status, $attempts, $message, $status, $id);
            $stmt->execute();
            $stmt->close();
        }
    }

    private static function log(string $username, string $action, string $result, string $message): void
    {
        try {
            self::ensureSchema();
            $db = Database::getInstance();
            $message = self::safeMessage($message);
            $stmt = $db->prepare(
                "INSERT INTO `ldap_sync_log` (`username`, `action`, `result`, `message`) VALUES (?, ?, ?, ?)"
            );
            if ($stmt) {
                $stmt->bind_param('ssss', $username, $action, $result, $message);
                $stmt->execute();
                $stmt->close();
            }
        } catch (Throwable $e) {
            error_log('SisLdapSync log: ' . $e->getMessage());
        }
    }

    /** @param array<string,mixed> $report */
    private static function writeReport(array $report): void
    {
        $dir = BASE_PATH . '/storage/ldap-reports';
        if (!is_dir($dir)) {
            @mkdir($dir, 0770, true);
        }
        $lines = [
            'SLGTI LDAP migration dry-run',
            'Generated: ' . (string) $report['generated_at'],
            'Writes: none',
            'SIS accounts: ' . (int) $report['sis_accounts'],
            'SIS active: ' . (int) $report['sis_active'],
            'SIS inactive: ' . (int) $report['sis_inactive'],
            'LDAP entries: ' . ($report['ldap_entries'] === null ? 'unavailable' : (string) $report['ldap_entries']),
            'Matched: ' . ($report['matched'] === null ? 'unavailable' : (string) $report['matched']),
            'Active SIS missing from LDAP: ' . ($report['missing_ldap'] === null ? 'unavailable' : (string) $report['missing_ldap']),
            'Duplicate SIS usernames: ' . count($report['duplicate_usernames']),
            'LDAP entries with no password yet: ' . ($report['waiting_for_password'] === null ? 'unavailable' : (string) $report['waiting_for_password']),
            'Disabled in LDAP: ' . ($report['disabled_in_ldap'] === null ? 'unavailable' : (string) $report['disabled_in_ldap']),
            'LDAP entries not in SIS: ' . ($report['ldap_not_in_sis'] === null ? 'unavailable' : (string) $report['ldap_not_in_sis']),
            'Queue pending: ' . (int) $report['queue_pending'],
            'Queue failed: ' . (int) $report['queue_failed'],
            'LDAP error: ' . ((string) $report['ldap_error'] !== '' ? (string) $report['ldap_error'] : 'none'),
            'Moodle: ' . (string) $report['moodle'],
            'Missing sample:',
        ];
        foreach ($report['missing_sample'] as $name) {
            $lines[] = '  ' . $name;
        }
        if ($report['duplicate_usernames'] !== []) {
            $lines[] = 'Duplicate usernames:';
            foreach ($report['duplicate_usernames'] as $name) {
                $lines[] = '  ' . $name;
            }
        }
        @file_put_contents($dir . '/latest.txt', implode(PHP_EOL, $lines) . PHP_EOL);
    }

    private static function action(string $action): string
    {
        $action = strtolower(trim($action));
        return $action !== '' ? $action : 'provision';
    }

    public static function safeMessage(string $message): string
    {
        $message = preg_replace('/password\s*[=:]\s*\S+/i', '[redacted]', $message) ?? $message;
        $message = preg_replace('/(userPassword|secret_payload)\s*[=:]\s*\S+/i', '$1=[redacted]', $message) ?? $message;
        if (strlen($message) > 480) {
            $message = substr($message, 0, 480);
        }
        return $message;
    }

    private static function key(): string
    {
        $env = getenv('LDAP_QUEUE_KEY');
        if (is_string($env) && strlen(trim($env)) >= 16) {
            return hash('sha256', trim($env), true);
        }
        $dir = BASE_PATH . '/storage';
        if (!is_dir($dir)) {
            @mkdir($dir, 0770, true);
        }
        $path = $dir . '/ldap-queue.key';
        if (!is_file($path)) {
            $fresh = random_bytes(32);
            @file_put_contents($path, $fresh);
        }
        $stored = @file_get_contents($path);
        if (!is_string($stored) || strlen($stored) < 32) {
            throw new RuntimeException('LDAP queue key is not available.');
        }
        return substr($stored, 0, 32);
    }

    private static function seal(string $plain): ?string
    {
        try {
            $iv = random_bytes(12);
            $tag = '';
            $cipher = openssl_encrypt($plain, 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, $iv, $tag);
            if (!is_string($cipher) || strlen($tag) !== 16) {
                return null;
            }
            return base64_encode($iv . $tag . $cipher);
        } catch (Throwable $e) {
            error_log('SisLdapSync seal failed');
            return null;
        }
    }

    private static function open(string $payload): ?string
    {
        try {
            $raw = base64_decode($payload, true);
            if (!is_string($raw) || strlen($raw) < 29) {
                return null;
            }
            $iv = substr($raw, 0, 12);
            $tag = substr($raw, 12, 16);
            $cipher = substr($raw, 28);
            $plain = openssl_decrypt($cipher, 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, $iv, $tag);
            return is_string($plain) ? $plain : null;
        } catch (Throwable $e) {
            return null;
        }
    }
}
