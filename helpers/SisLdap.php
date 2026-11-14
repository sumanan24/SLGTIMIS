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
        } catch (Throwable $e) {
            error_log('SisLdap password sync failed for ' . $username . ': ' . $e->getMessage());
        }
    }

    public static function syncPasswordForUserId(int $userId, string $plain): void
    {
        $plain = (string) $plain;
        if ($userId <= 0 || $plain === '') {
            return;
        }
        try {
            $profile = self::profileByUserId($userId);
            if ($profile === null) {
                return;
            }
            self::client()->upsert($profile, $plain, true);
        } catch (Throwable $e) {
            error_log('SisLdap password sync failed for user ' . $userId . ': ' . $e->getMessage());
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
        } catch (Throwable $e) {
            error_log('SisLdap add-if-missing failed for ' . $username . ': ' . $e->getMessage());
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

    private static function client(): SisLdapClient
    {
        require_once BASE_PATH . '/config/ldap.php';
        return new SisLdapClient();
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

        $uri = 'ldap://' . LDAP_HOST . ':' . (int) LDAP_PORT;
        $link = @ldap_connect($uri);
        if ($link === false) {
            throw new RuntimeException('LDAP connection failed.');
        }
        ldap_set_option($link, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($link, LDAP_OPT_REFERRALS, 0);
        ldap_set_option($link, LDAP_OPT_NETWORK_TIMEOUT, 8);
        if (LDAP_TLS_CA !== '' && defined('LDAP_OPT_X_TLS_CACERTFILE')) {
            ldap_set_option(null, LDAP_OPT_X_TLS_CACERTFILE, LDAP_TLS_CA);
        }
        if (LDAP_STARTTLS && !@ldap_start_tls($link)) {
            $detail = function_exists('ldap_error') ? ldap_error($link) : 'StartTLS failed';
            throw new RuntimeException('LDAP StartTLS failed: ' . $detail);
        }
        if (!@ldap_bind($link, LDAP_BIND_DN, LDAP_BIND_PASSWORD)) {
            $detail = function_exists('ldap_error') ? ldap_error($link) : 'bind failed';
            throw new RuntimeException('LDAP bind failed: ' . $detail);
        }
        $this->link = $link;
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
                ['uid', 'userPassword'],
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
                $index[SisLdap::uidKey($uid)] = ['has_password' => $hasPassword];
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
