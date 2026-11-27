<?php
/**
 * Copy active SIS logins into LDAP ou=People for EDUROAM.
 *
 * Wi-Fi username is user.user_name exactly (slashes included).
 * A password already stored in LDAP is not replaced, except when SIS itself
 * has just changed that password. A locked SHA-256 hash is never copied,
 * because LDAP cannot check it for EAP-TTLS/PAP.
 */
declare(strict_types=1);

require_once __DIR__ . '/SisLdapSync.php';

class SisLdap
{
    public const PAGE_SIZE = 40;

    /** @var list<string> */
    public const DEPARTMENTS = [
        'Admin',
        'Automotive Technology',
        'Construction Technology',
        'Electrical Technology',
        'Finance Office',
        'Food Technology',
        'Information Communication Technology',
        'Mechanical Technology',
    ];

    /**
     * SIS password change. LDAP failure is logged and does not undo the SIS change.
     */
    public static function syncPassword(string $username, string $plain): void
    {
        $username = trim($username);
        if ($username === '' || $plain === '') {
            return;
        }
        try {
            $profile = self::profileByUsername($username);
            if ($profile === null) {
                $profile = [
                    'user_id' => 0,
                    'username' => $username,
                    'name' => $username,
                    'department' => '',
                    'type' => 'staff',
                ];
            }
            self::client()->upsert($profile, $plain, true);
            SisLdapSync::recordSuccess($username, 'password');
            self::linkMoodleAccount($username);
        } catch (Throwable $e) {
            self::fail($username, 'password', $e, $plain);
        }
    }

    public static function syncPasswordForUserId(int $userId, string $plain): void
    {
        $plain = (string) $plain;
        if ($userId <= 0 || $plain === '') {
            return;
        }
        $profile = null;
        try {
            $profile = self::profileByUserId($userId);
            if ($profile === null) {
                return;
            }
            self::client()->upsert($profile, $plain, true);
            SisLdapSync::recordSuccess((string) $profile['username'], 'password');
            self::linkMoodleAccount((string) $profile['username']);
        } catch (Throwable $e) {
            $name = $profile['username'] ?? (string) $userId;
            self::fail($name, 'password', $e, $plain);
        }
    }

    /**
     * Add one account when it is not already in LDAP.
     * $plain is written only for a new entry (new student NIC, new staff default).
     */
    public static function addIfMissing(string $username, ?string $plain = null): void
    {
        $username = trim($username);
        if ($username === '') {
            return;
        }
        try {
            $profile = self::profileByUsername($username);
            if ($profile === null) {
                return;
            }
            self::client()->upsert($profile, $plain, false);
            SisLdapSync::recordSuccess($username, 'provision');
        } catch (Throwable $e) {
            self::fail($username, 'provision', $e, $plain);
        }
    }

    /**
     * Lock or unlock the LDAP entry. The entry is not deleted and the password is not changed.
     */
    public static function syncActiveState(string $username, bool $active): void
    {
        $username = trim($username);
        if ($username === '') {
            return;
        }
        try {
            self::client()->setAccountState($username, $active);
            SisLdapSync::recordSuccess($username, $active ? 'enable' : 'disable');
        } catch (Throwable $e) {
            self::fail($username, $active ? 'enable' : 'disable', $e, null);
        }
    }

    /**
     * Copy name, department and type onto an existing LDAP entry. Password is not changed.
     */
    public static function syncProfileAttributes(string $username): void
    {
        $username = trim($username);
        if ($username === '') {
            return;
        }
        try {
            $profile = self::profileByUsername($username);
            if ($profile === null) {
                return;
            }
            self::client()->syncIdentity($profile);
            SisLdapSync::recordSuccess($username, 'attributes');
        } catch (Throwable $e) {
            self::fail($username, 'attributes', $e, null);
        }
    }

    /**
     * LDAP simple bind for SIS login.
     * null means LDAP is unavailable or this user is not in the directory, so SIS keeps its local check.
     */
    public static function verifyBind(string $username, string $password): ?bool
    {
        $username = trim($username);
        if ($username === '' || $password === '') {
            return null;
        }
        try {
            require_once BASE_PATH . '/config/ldap.php';
            if (!LDAP_SYNC_ENABLED) {
                return null;
            }
            $mode = defined('LDAP_SIS_AUTH') ? LDAP_SIS_AUTH : 'local';
            if ($mode === 'local') {
                return null;
            }
            return self::client()->passwordAccepted($username, $password);
        } catch (Throwable $e) {
            error_log('SisLdap login check skipped for ' . $username . ': ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Minute job and the admin Sync button.
     * Adds active SIS accounts that are not in LDAP. Does not change stored LDAP passwords.
     *
     * @return array{added:int,present:int,errors:int,message:string}
     */
    public static function addMissingAccounts(): array
    {
        $added = 0;
        $present = 0;
        $errors = 0;
        try {
            $client = self::client();
            $known = $client->uidIndex();
            foreach (self::activeAccounts() as $profile) {
                $key = self::uidKey($profile['username']);
                if (isset($known[$key])) {
                    $present++;
                    continue;
                }
                try {
                    $client->upsert($profile, null, false);
                    $added++;
                    $known[$key] = ['has_password' => false];
                } catch (Throwable $e) {
                    $errors++;
                    error_log('SisLdap missing-account add failed for ' . $profile['username'] . ': ' . $e->getMessage());
                    if (!SisLdapSync::isApplying()) {
                        SisLdapSync::recordFailure($profile['username'], 'provision', $e->getMessage(), null);
                    }
                }
            }
            return [
                'added' => $added,
                'present' => $present,
                'errors' => $errors,
                'message' => $added . ' account(s) added. Existing LDAP passwords were not changed.',
            ];
        } catch (Throwable $e) {
            error_log('SisLdap addMissingAccounts: ' . $e->getMessage());
            return [
                'added' => 0,
                'present' => 0,
                'errors' => 1,
                'message' => 'LDAP is not available. ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Admin monitor page data. 40 rows per page.
     *
     * @param array{q?:string,type?:string,department?:string,status?:string} $filters
     * @return array<string,mixed>
     */
    public static function monitor(array $filters, int $page): array
    {
        $page = max(1, $page);
        $accounts = self::activeAccounts();
        $ldapError = '';
        $index = [];
        try {
            $index = self::client()->uidIndex();
        } catch (Throwable $e) {
            $ldapError = $e->getMessage();
        }

        $rows = [];
        $counts = ['match' => 0, 'waiting' => 0, 'missing' => 0, 'total' => 0];
        foreach ($accounts as $profile) {
            $key = self::uidKey($profile['username']);
            if ($ldapError !== '') {
                $status = 'unavailable';
            } elseif (!isset($index[$key])) {
                $status = 'missing';
            } elseif (!empty($index[$key]['has_password'])) {
                $status = 'match';
            } else {
                $status = 'waiting';
            }
            if (isset($counts[$status])) {
                $counts[$status]++;
            }
            $counts['total']++;
            $profile['status'] = $status;
            if (!self::matchesFilters($profile, $filters)) {
                continue;
            }
            $rows[] = $profile;
        }

        $filtered = count($rows);
        $pages = max(1, (int) ceil($filtered / self::PAGE_SIZE));
        if ($page > $pages) {
            $page = $pages;
        }
        $offset = ($page - 1) * self::PAGE_SIZE;

        $departments = self::DEPARTMENTS;
        foreach ($accounts as $profile) {
            if ($profile['department'] !== '' && !in_array($profile['department'], $departments, true)) {
                $departments[] = $profile['department'];
            }
        }

        return [
            'rows' => array_slice($rows, $offset, self::PAGE_SIZE),
            'page' => $page,
            'pages' => $pages,
            'filtered' => $filtered,
            'counts' => $counts,
            'departments' => $departments,
            'ldap_error' => $ldapError,
            'ldap_config' => self::configStatus(),
            'enabled' => defined('LDAP_SYNC_ENABLED') ? LDAP_SYNC_ENABLED : true,
        ];
    }

    /**
     * @param array{username:string,name:string,department:string,type:string,status?:string} $profile
     * @param array{q?:string,type?:string,department?:string,status?:string} $filters
     */
    private static function matchesFilters(array $profile, array $filters): bool
    {
        $q = strtolower(trim((string) ($filters['q'] ?? '')));
        if ($q !== '') {
            $hay = strtolower($profile['username'] . ' ' . $profile['name']);
            if (!str_contains($hay, $q)) {
                return false;
            }
        }
        $type = strtolower(trim((string) ($filters['type'] ?? '')));
        if ($type !== '' && $type !== $profile['type']) {
            return false;
        }
        $department = trim((string) ($filters['department'] ?? ''));
        if ($department !== '' && strcasecmp($department, $profile['department']) !== 0) {
            return false;
        }
        $status = strtolower(trim((string) ($filters['status'] ?? '')));
        if ($status !== '' && $status !== ($profile['status'] ?? '')) {
            return false;
        }
        return true;
    }

    /**
     * @return list<array{user_id:int,username:string,name:string,department:string,type:string}>
     */
    public static function activeAccounts(): array
    {
        $db = Database::getInstance();
        $sql = "SELECT u.`user_id`, u.`user_name`, u.`user_table`,
                    CASE
                        WHEN u.`user_table` = 'student' THEN COALESCE(NULLIF(s.`student_fullname`, ''), u.`user_name`)
                        ELSE COALESCE(NULLIF(st.`staff_name`, ''), u.`user_name`)
                    END AS display_name,
                    CASE
                        WHEN u.`user_table` = 'student' THEN COALESCE((
                            SELECT d.`department_name`
                            FROM `student_enroll` se
                            INNER JOIN `course` c ON c.`course_id` = se.`course_id`
                            INNER JOIN `department` d ON d.`department_id` = c.`department_id`
                            WHERE se.`student_id` = s.`student_id`
                              AND se.`student_enroll_status` IN ('Following', 'Internship')
                            ORDER BY se.`academic_year` DESC
                            LIMIT 1
                        ), '')
                        ELSE COALESCE(sd.`department_name`, '')
                    END AS department_name
                FROM `user` u
                LEFT JOIN `student` s ON u.`user_table` = 'student'
                    AND (s.`student_id` = u.`user_name` OR s.`student_nic` = u.`user_name`)
                LEFT JOIN `staff` st ON u.`user_table` = 'staff' AND st.`staff_id` = u.`user_name`
                LEFT JOIN `department` sd ON sd.`department_id` = st.`department_id`
                WHERE u.`user_active` = 1
                  AND u.`user_table` IN ('student', 'staff')
                  AND u.`user_name` IS NOT NULL
                  AND u.`user_name` <> ''
                ORDER BY u.`user_name` ASC";
        $result = $db->query($sql);
        $rows = [];
        $seen = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $userId = (int) ($row['user_id'] ?? 0);
                if ($userId <= 0 || isset($seen[$userId])) {
                    continue;
                }
                $type = (string) ($row['user_table'] ?? '');
                if ($type !== 'student' && $type !== 'staff') {
                    continue;
                }
                $seen[$userId] = true;
                $rows[] = [
                    'user_id' => $userId,
                    'username' => (string) ($row['user_name'] ?? ''),
                    'name' => (string) ($row['display_name'] ?? ''),
                    'department' => (string) ($row['department_name'] ?? ''),
                    'type' => $type,
                ];
            }
        }
        return $rows;
    }

    /** @return array{user_id:int,username:string,name:string,department:string,type:string}|null */
    private static function profileByUserId(int $userId): ?array
    {
        foreach (self::activeAccounts() as $profile) {
            if ($profile['user_id'] === $userId) {
                return $profile;
            }
        }
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT `user_id`, `user_name`, `user_table` FROM `user` WHERE `user_id` = ? LIMIT 1");
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row || trim((string) ($row['user_name'] ?? '')) === '') {
            return null;
        }
        $type = (string) ($row['user_table'] ?? 'staff');
        return [
            'user_id' => (int) $row['user_id'],
            'username' => (string) $row['user_name'],
            'name' => (string) $row['user_name'],
            'department' => '',
            'type' => $type === 'student' ? 'student' : 'staff',
        ];
    }

    /** @return array{user_id:int,username:string,name:string,department:string,type:string}|null */
    private static function profileByUsername(string $username): ?array
    {
        foreach (self::activeAccounts() as $profile) {
            if ($profile['username'] === $username) {
                return $profile;
            }
        }
        return null;
    }

    public static function uidKey(string $username): string
    {
        return strtolower(trim($username));
    }

    /**
     * Point an existing Moodle account at LDAP after the directory password is saved.
     * RADIUS authentication reads this same directory. Accounting does not keep a password.
     */
    private static function linkMoodleAccount(string $username): void
    {
        $username = trim($username);
        $key = BASE_PATH . '/storage/lms-sync-key';
        if ($username === '' || !is_file($key) || preg_match('/[\r\n\0]/', $username)) {
            return;
        }
        $ssh = 'C:\\Windows\\System32\\OpenSSH\\ssh.exe';
        if (!is_file($ssh)) {
            $ssh = 'ssh';
        }
        $known = BASE_PATH . '/storage/lms-known-hosts';
        $remote = 'sudo -n /usr/bin/php /usr/local/sbin/slgti-moodle-use-ldap.php ' . escapeshellarg($username);
        $cmd = escapeshellarg($ssh)
            . ' -i ' . escapeshellarg($key)
            . ' -o BatchMode=yes -o StrictHostKeyChecking=accept-new'
            . ' -o UserKnownHostsFile=' . escapeshellarg($known)
            . ' ictadmin@172.16.1.230 ' . escapeshellarg($remote);
        $output = [];
        $code = 0;
        exec($cmd . ' 2>&1', $output, $code);
        $text = trim(implode(' ', $output));
        if (strlen($text) > 400) {
            $text = substr($text, 0, 400);
        }
        $linked = $code === 0 && (
            str_contains($text, 'moodle-ldap')
            || str_contains($text, 'moodle-already-ldap')
            || str_contains($text, 'moodle-user-missing')
            || str_contains($text, 'moodle-auth-kept')
            || str_contains($text, 'moodle-duplicate')
        );
        if ($linked) {
            SisLdapSync::recordSuccess($username, 'moodle', $text);
            return;
        }
        SisLdapSync::note($username, 'moodle', $text !== '' ? $text : 'Moodle could not be updated');
    }

    /**
     * Connection checks for the admin page. No passwords are read or returned.
     *
     * @return list<array{name:string,target:string,ok:bool,detail:string}>
     */
    public static function connectionTests(): array
    {
        require_once BASE_PATH . '/config/ldap.php';
        $tests = [];

        try {
            $db = Database::getInstance();
            $result = $db->query('SELECT 1 AS ok');
            $tests[] = [
                'name' => 'SIS database',
                'target' => DB_HOST . ' / ' . DB_NAME,
                'ok' => $result !== false,
                'detail' => $result !== false ? 'Connected' : 'Query failed',
            ];
        } catch (Throwable $e) {
            $tests[] = [
                'name' => 'SIS database',
                'target' => DB_HOST . ' / ' . DB_NAME,
                'ok' => false,
                'detail' => 'Database connection failed',
            ];
        }

        $ldapTarget = LDAP_HOST . ':' . (int) LDAP_PORT;
        try {
            $client = self::client();
            $found = $client->sampleAccount();
            $tests[] = [
                'name' => 'LDAP directory',
                'target' => $ldapTarget . ' / ' . LDAP_PEOPLE_DN,
                'ok' => true,
                'detail' => $found
                    ? 'Bind and search succeeded. Existing Wi-Fi and LMS passwords stay in LDAP and cannot be displayed.'
                    : 'Bind succeeded. No people entries were returned.',
            ];
        } catch (Throwable $e) {
            $tests[] = [
                'name' => 'LDAP directory',
                'target' => $ldapTarget,
                'ok' => false,
                'detail' => $e->getMessage(),
            ];
        }

        $tests[] = self::httpTest(
            'Moodle LMS',
            'https://lms.slgti.ac.lk/login/index.php'
        );
        $tests[] = self::tcpTest('LMS server', '172.16.1.230', 443);
        $tests[] = self::udpTest('RADIUS auth', '172.16.1.246', 1812);
        $tests[] = self::udpTest('RADIUS accounting', '172.16.1.246', 1813);

        return $tests;
    }

    /** @return array{name:string,target:string,ok:bool,detail:string} */
    private static function tcpTest(string $name, string $host, int $port): array
    {
        $errno = 0;
        $err = '';
        $socket = @fsockopen($host, $port, $errno, $err, 3.0);
        $ok = is_resource($socket);
        if ($ok) {
            fclose($socket);
        }
        $detail = $ok ? 'Port is accepting connections' : 'No TCP listener on this port';
        if (!$ok && ($port === 1812 || $port === 1813)) {
            $detail .= '. FreeRADIUS usually listens on UDP, so a closed TCP port does not by itself mean Wi-Fi is down.';
        }
        return [
            'name' => $name,
            'target' => $host . ':' . $port,
            'ok' => $ok,
            'detail' => $detail,
        ];
    }

    /** @return array{name:string,target:string,ok:bool,detail:string} */
    private static function httpTest(string $name, string $url): array
    {
        if (!function_exists('curl_init')) {
            return [
                'name' => $name,
                'target' => $url,
                'ok' => false,
                'detail' => 'HTTP client is not available',
            ];
        }
        $probe = self::httpStatus($url, true);
        if ($probe['ok']) {
            return [
                'name' => $name,
                'target' => $url,
                'ok' => true,
                'detail' => 'HTTP ' . $probe['code'],
            ];
        }
        if ($probe['ssl']) {
            $again = self::httpStatus($url, false);
            if ($again['ok']) {
                return [
                    'name' => $name,
                    'target' => $url,
                    'ok' => true,
                    'detail' => 'Login page returned HTTP ' . $again['code'] . '. This PC does not trust the LMS certificate.',
                ];
            }
        }
        return [
            'name' => $name,
            'target' => $url,
            'ok' => false,
            'detail' => $probe['error'] !== '' ? $probe['error'] : 'HTTP ' . $probe['code'],
        ];
    }

    /** @return array{ok:bool,code:int,error:string,ssl:bool} */
    private static function httpStatus(string $url, bool $verify): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_NOBODY => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_SSL_VERIFYPEER => $verify,
            CURLOPT_SSL_VERIFYHOST => $verify ? 2 : 0,
            CURLOPT_RETURNTRANSFER => true,
        ]);
        curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = (string) curl_error($ch);
        curl_close($ch);
        return [
            'ok' => $code >= 200 && $code < 400,
            'code' => $code,
            'error' => $error,
            'ssl' => stripos($error, 'SSL') !== false || stripos($error, 'certificate') !== false,
        ];
    }

    /** @return array{name:string,target:string,ok:bool,detail:string} */
    private static function udpTest(string $name, string $host, int $port): array
    {
        $target = $host . ':' . $port . ' UDP';
        if (!function_exists('socket_create')) {
            return [
                'name' => $name,
                'target' => $target,
                'ok' => false,
                'detail' => 'UDP client is not available',
            ];
        }
        $socket = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if ($socket === false) {
            return [
                'name' => $name,
                'target' => $target,
                'ok' => false,
                'detail' => 'UDP socket could not be opened',
            ];
        }
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 2, 'usec' => 0]);
        $sent = @socket_sendto($socket, "\x00", 1, 0, $host, $port);
        $buffer = '';
        $from = '';
        $fromPort = 0;
        $received = @socket_recvfrom($socket, $buffer, 64, 0, $from, $fromPort);
        $error = socket_last_error($socket);
        socket_close($socket);
        if ($sent === false) {
            return [
                'name' => $name,
                'target' => $target,
                'ok' => false,
                'detail' => 'UDP packet could not be sent',
            ];
        }
        if ($received === false && in_array($error, [10054, 111, 10061], true)) {
            return [
                'name' => $name,
                'target' => $target,
                'ok' => false,
                'detail' => 'The server refused the UDP port',
            ];
        }
        return [
            'name' => $name,
            'target' => $target,
            'ok' => true,
            'detail' => $received !== false
                ? 'The RADIUS port answered'
                : 'The port did not refuse the packet. Wi-Fi auth still uses the password stored in LDAP.',
        ];
    }

    /** @return array{host:string,port:int,bind_ready:bool,env_file:bool} */
    public static function configStatus(): array
    {
        require_once BASE_PATH . '/config/ldap.php';
        return [
            'host' => (string) LDAP_HOST,
            'port' => (int) LDAP_PORT,
            'bind_ready' => LDAP_BIND_DN !== '' && LDAP_BIND_PASSWORD !== '',
            'env_file' => is_file(BASE_PATH . '/.env'),
        ];
    }

    private static function client(): SisLdapClient
    {
        require_once BASE_PATH . '/config/ldap.php';
        return new SisLdapClient();
    }

    private static function fail(string $username, string $action, Throwable $e, ?string $secret): void
    {
        error_log('SisLdap ' . $action . ' failed for ' . $username . ': ' . $e->getMessage());
        if (SisLdapSync::isApplying()) {
            throw $e;
        }
        SisLdapSync::recordFailure($username, $action, $e->getMessage(), $secret);
    }
}

/**
 * StartTLS bind and ou=People writes. Passwords are stored as {SSHA}.
 */
class SisLdapClient
{
    /** @var resource|\LDAP\Connection|null */
    private $link = null;

    public function __construct()
    {
        require_once BASE_PATH . '/config/ldap.php';
        if (!LDAP_SYNC_ENABLED) {
            throw new RuntimeException('LDAP sync is disabled.');
        }
        if (!function_exists('ldap_connect')) {
            throw new RuntimeException('PHP LDAP extension is not enabled.');
        }
        if (LDAP_BIND_DN === '' || LDAP_BIND_PASSWORD === '') {
            throw new RuntimeException('LDAP bind account is not configured.');
        }
        $this->link = $this->open(LDAP_BIND_DN, LDAP_BIND_PASSWORD);
    }

    /**
     * @return resource|\LDAP\Connection
     */
    private function open(string $dn, string $password)
    {
        $uri = 'ldap://' . LDAP_HOST . ':' . (int) LDAP_PORT;
        if (defined('LDAP_OPT_X_TLS_REQUIRE_CERT')) {
            $requireCert = LDAP_OPT_X_TLS_DEMAND;
            // The directory certificate is issued to ldap.slgti.ac.lk. An IP
            // address cannot match that name, and this PC has no DNS for it.
            if (filter_var(LDAP_HOST, FILTER_VALIDATE_IP)) {
                $requireCert = LDAP_OPT_X_TLS_NEVER;
            }
            ldap_set_option(null, LDAP_OPT_X_TLS_REQUIRE_CERT, $requireCert);
        }
        if (LDAP_TLS_CA !== '' && defined('LDAP_OPT_X_TLS_CACERTFILE')) {
            ldap_set_option(null, LDAP_OPT_X_TLS_CACERTFILE, LDAP_TLS_CA);
        }
        if (defined('LDAP_OPT_X_TLS_NEWCTX')) {
            ldap_set_option(null, LDAP_OPT_X_TLS_NEWCTX, 0);
        }
        $link = @ldap_connect($uri);
        if ($link === false) {
            throw new RuntimeException('LDAP connection failed for ' . LDAP_HOST . '.');
        }
        ldap_set_option($link, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($link, LDAP_OPT_REFERRALS, 0);
        ldap_set_option($link, LDAP_OPT_NETWORK_TIMEOUT, 8);
        if (LDAP_STARTTLS && !@ldap_start_tls($link)) {
            $detail = function_exists('ldap_error') ? ldap_error($link) : 'StartTLS failed';
            throw new RuntimeException('LDAP StartTLS failed for ' . LDAP_HOST . ': ' . $detail);
        }
        if (!@ldap_bind($link, $dn, $password)) {
            $detail = function_exists('ldap_error') ? ldap_error($link) : 'bind failed';
            throw new RuntimeException('LDAP bind failed: ' . $detail);
        }
        return $link;
    }

    /**
     * @param array{username:string,name:string,department:string,type:string} $profile
     */
    public function upsert(array $profile, ?string $plain, bool $replacePassword): void
    {
        $username = trim($profile['username']);
        if ($username === '') {
            return;
        }
        $dn = 'uid=' . self::escapeDn($username) . ',' . LDAP_PEOPLE_DN;
        $exists = $this->entryExists($username);
        $password = ($plain !== null && $plain !== '') ? self::ssha($plain) : null;

        if (!$exists) {
            $name = trim($profile['name']) !== '' ? trim($profile['name']) : $username;
            $sn = self::surname($name);
            $entry = [
                'objectClass' => ['inetOrgPerson', 'organizationalPerson', 'person', 'top'],
                'uid' => $username,
                'cn' => $name,
                'sn' => $sn,
                'employeeType' => $profile['type'] === 'student' ? 'student' : 'staff',
                'businessCategory' => 'active',
            ];
            if (trim($profile['department']) !== '') {
                $entry['ou'] = trim($profile['department']);
            }
            if ($password !== null) {
                $entry['userPassword'] = $password;
            }
            if (!@ldap_add($this->link, $dn, $entry)) {
                throw new RuntimeException('LDAP add failed: ' . ldap_error($this->link));
            }
            return;
        }

        if ($replacePassword && $password !== null) {
            if (!@ldap_mod_replace($this->link, $dn, ['userPassword' => $password])) {
                throw new RuntimeException('LDAP password update failed: ' . ldap_error($this->link));
            }
        }
    }

    /**
     * Mark an existing entry active or disabled. Does not delete the entry or the password.
     */
    public function setAccountState(string $username, bool $active): void
    {
        $username = trim($username);
        if ($username === '' || !$this->entryExists($username)) {
            return;
        }
        $dn = 'uid=' . self::escapeDn($username) . ',' . LDAP_PEOPLE_DN;
        $desired = $active ? 'active' : 'disabled';
        if (!$this->replaceAttribute($dn, 'businessCategory', $desired)) {
            throw new RuntimeException('LDAP account state update failed: ' . ldap_error($this->link));
        }
        if ($active) {
            $this->clearPolicyLock($dn);
            return;
        }
        $locked = @ldap_mod_add($this->link, $dn, ['pwdAccountLockedTime' => '000001010000Z']);
        if (!$locked) {
            $detail = ldap_error($this->link);
            $already = is_string($detail) && (
                stripos($detail, 'Already exists') !== false
                || stripos($detail, 'Type or value exists') !== false
            );
            if (!$already) {
                error_log('SisLdap policy lock was not applied for ' . $username . ': ' . $detail);
            }
        }
    }

    /**
     * @param array{username:string,name:string,department:string,type:string} $profile
     */
    public function syncIdentity(array $profile): bool
    {
        $username = trim($profile['username']);
        if ($username === '' || !$this->entryExists($username)) {
            return false;
        }
        $name = trim($profile['name']) !== '' ? trim($profile['name']) : $username;
        $dn = 'uid=' . self::escapeDn($username) . ',' . LDAP_PEOPLE_DN;
        $entry = [
            'cn' => $name,
            'sn' => self::surname($name),
            'employeeType' => $profile['type'] === 'student' ? 'student' : 'staff',
        ];
        if (trim($profile['department']) !== '') {
            $entry['ou'] = trim($profile['department']);
        }
        if (!@ldap_mod_replace($this->link, $dn, $entry)) {
            throw new RuntimeException('LDAP profile update failed: ' . ldap_error($this->link));
        }
        return true;
    }

    public function passwordAccepted(string $username, string $plain): ?bool
    {
        $username = trim($username);
        if ($username === '' || $plain === '' || !$this->entryExists($username)) {
            return null;
        }
        if ($this->accountMarker($username) === 'disabled') {
            return false;
        }
        $dn = 'uid=' . self::escapeDn($username) . ',' . LDAP_PEOPLE_DN;
        try {
            $link = $this->open($dn, $plain);
        } catch (RuntimeException $e) {
            if (str_contains($e->getMessage(), 'LDAP bind failed')) {
                return false;
            }
            throw $e;
        }
        @ldap_unbind($link);
        return true;
    }

    private function replaceAttribute(string $dn, string $attribute, string $value): bool
    {
        if (@ldap_mod_replace($this->link, $dn, [$attribute => $value])) {
            return true;
        }
        return @ldap_mod_add($this->link, $dn, [$attribute => $value]);
    }

    private function clearPolicyLock(string $dn): void
    {
        $search = @ldap_read($this->link, $dn, '(objectClass=*)', ['pwdAccountLockedTime']);
        if ($search === false) {
            return;
        }
        $entries = ldap_get_entries($this->link, $search);
        $value = (string) ($entries[0]['pwdaccountlockedtime'][0] ?? '');
        if ($value === '') {
            return;
        }
        @ldap_mod_del($this->link, $dn, ['pwdAccountLockedTime' => $value]);
    }

    private function accountMarker(string $username): string
    {
        $filter = '(uid=' . self::escapeFilter($username) . ')';
        $search = @ldap_search($this->link, LDAP_PEOPLE_DN, $filter, ['businessCategory'], 0, 1);
        if ($search === false) {
            return 'unknown';
        }
        $entries = ldap_get_entries($this->link, $search);
        return strtolower((string) ($entries[0]['businesscategory'][0] ?? 'unknown'));
    }

    /**
     * @return array<string, array{has_password:bool}>
     */
    public function uidIndex(): array
    {
        $index = [];
        $cookie = '';
        $guard = 0;
        do {
            $guard++;
            if ($guard > 20) {
                break;
            }
            $controls = [];
            if (defined('LDAP_CONTROL_PAGEDRESULTS')) {
                $controls = [[
                    'oid' => LDAP_CONTROL_PAGEDRESULTS,
                    'iscritical' => true,
                    'value' => ['size' => 500, 'cookie' => $cookie],
                ]];
            }
            $search = @ldap_search(
                $this->link,
                LDAP_PEOPLE_DN,
                '(uid=*)',
                ['uid', 'userPassword', 'businessCategory'],
                0,
                0,
                0,
                LDAP_DEREF_NEVER,
                $controls
            );
            if ($search === false) {
                throw new RuntimeException('LDAP search failed: ' . ldap_error($this->link));
            }
            $entries = ldap_get_entries($this->link, $search);
            $count = (int) ($entries['count'] ?? 0);
            for ($i = 0; $i < $count; $i++) {
                $uid = (string) ($entries[$i]['uid'][0] ?? '');
                if ($uid === '') {
                    continue;
                }
                $hasPassword = !empty($entries[$i]['userpassword'][0]);
                $marker = strtolower((string) ($entries[$i]['businesscategory'][0] ?? ''));
                $account = $marker === 'disabled' ? 'disabled' : ($marker === 'active' ? 'active' : 'unknown');
                $index[SisLdap::uidKey($uid)] = ['has_password' => $hasPassword, 'account' => $account];
            }
            $cookie = '';
            if (defined('LDAP_CONTROL_PAGEDRESULTS') && function_exists('ldap_parse_result')) {
                $errcode = 0;
                $matcheddn = '';
                $errmsg = '';
                $referrals = [];
                $serverControls = [];
                ldap_parse_result($this->link, $search, $errcode, $matcheddn, $errmsg, $referrals, $serverControls);
                if (isset($serverControls[LDAP_CONTROL_PAGEDRESULTS]['value']['cookie'])) {
                    $cookie = (string) $serverControls[LDAP_CONTROL_PAGEDRESULTS]['value']['cookie'];
                }
            }
        } while ($cookie !== '');

        return $index;
    }

    private function entryExists(string $username): bool
    {
        $filter = '(uid=' . self::escapeFilter($username) . ')';
        $search = @ldap_search($this->link, LDAP_PEOPLE_DN, $filter, ['uid'], 0, 1);
        if ($search === false) {
            return false;
        }
        $entries = ldap_get_entries($this->link, $search);
        return (int) ($entries['count'] ?? 0) > 0;
    }

    public function sampleAccount(): bool
    {
        $search = @ldap_search($this->link, LDAP_PEOPLE_DN, '(uid=*)', ['uid'], 0, 1);
        if ($search === false) {
            throw new RuntimeException('LDAP search failed: ' . ldap_error($this->link));
        }
        $entries = ldap_get_entries($this->link, $search);
        return (int) ($entries['count'] ?? 0) > 0;
    }

    public static function ssha(string $password): string
    {
        $salt = random_bytes(8);
        return '{SSHA}' . base64_encode(sha1($password . $salt, true) . $salt);
    }

    private static function surname(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $sn = (string) end($parts);
        return $sn !== '' ? $sn : 'User';
    }

    private static function escapeDn(string $value): string
    {
        if (function_exists('ldap_escape')) {
            return ldap_escape($value, '', LDAP_ESCAPE_DN);
        }
        return addcslashes($value, '\\,+"<>;');
    }

    private static function escapeFilter(string $value): string
    {
        if (function_exists('ldap_escape')) {
            return ldap_escape($value, '', LDAP_ESCAPE_FILTER);
        }
        return str_replace(['\\', '*', '(', ')', "\0"], ['\\5c', '\\2a', '\\28', '\\29', '\\00'], $value);
    }
}
