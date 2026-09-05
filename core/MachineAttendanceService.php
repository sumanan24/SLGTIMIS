<?php
/**
 * Hikvision ISAPI client for student fingerprint attendance machine.
 * Auth: HTTP Digest (same mechanism as the device web UI / AcsEvent search).
 * Does not store or log credentials, templates, or biometric images.
 */
declare(strict_types=1);

class MachineAttendanceService {
    private string $host;
    private string $username;
    private string $password;
    private bool $ssl;
    private int $port;
    private int $timeout;
    private string $timezone;
    private int $acsMajor;
    /** @var list<int> */
    private array $acsMinors;
    private int $maxResults;
    private int $maxPages;

    public function __construct(?array $config = null) {
        if ($config === null) {
            $config = require BASE_PATH . '/config/student_attendance_machine.php';
        }
        $this->host = trim((string) ($config['host'] ?? ''));
        $this->username = trim((string) ($config['username'] ?? 'admin'));
        $this->password = (string) ($config['password'] ?? '');
        $this->ssl = !empty($config['ssl']);
        $this->port = (int) ($config['port'] ?? 0);
        $this->timeout = max(10, (int) ($config['timeout'] ?? 60));
        $this->timezone = (string) ($config['timezone'] ?? 'Asia/Colombo');
        $this->acsMajor = (int) ($config['acs_major'] ?? 5);
        // Minors that return employeeNoString on DS-K1T343 (0=auth, 38=face, 75=face+auth)
        $minors = $config['acs_minors'] ?? [0, 38, 75];
        $this->acsMinors = is_array($minors) ? array_values(array_map('intval', $minors)) : [0, 38, 75];
        $this->maxResults = max(1, min(10000, (int) ($config['max_results_per_page'] ?? 2000)));
        $this->maxPages = max(1, (int) ($config['max_pages'] ?? 5000));
    }

    public function getHost(): string {
        return $this->host;
    }

    public function getBaseUrl(): string {
        $scheme = $this->ssl ? 'https' : 'http';
        $defaultPort = $this->ssl ? 443 : 80;
        $authority = $this->host;
        if ($this->port > 0 && $this->port !== $defaultPort) {
            $authority .= ':' . $this->port;
        }
        return $scheme . '://' . $authority . '/';
    }

    /**
     * @return array{ok: bool, message: string, device_info?: mixed}
     */
    public function testConnection(): array {
        if ($this->host === '' || $this->password === '') {
            return [
                'ok' => false,
                'message' => $this->missingCredentialsMessage(),
            ];
        }
        $url = rtrim($this->getBaseUrl(), '/') . '/ISAPI/System/deviceInfo';
        try {
            $res = $this->request('GET', $url, null, null, min(15, $this->timeout));
            if ($res['http_code'] === 401) {
                $msg = $this->formatUnauthorized($res['body']);
                return ['ok' => false, 'message' => $msg];
            }
            if ($res['error'] !== null) {
                return ['ok' => false, 'message' => $this->networkHint($res['error'])];
            }
            if ($res['http_code'] < 200 || $res['http_code'] >= 300) {
                return ['ok' => false, 'message' => 'Machine returned HTTP ' . $res['http_code']];
            }
            $info = $this->parseDeviceInfo($res['body']);
            return [
                'ok' => true,
                'message' => 'CONNECTED',
                'device_info' => $info,
            ];
        } catch (Throwable $e) {
            error_log('[MachineAttendanceService] testConnection: ' . $e->getMessage());
            return ['ok' => false, 'message' => 'Attendance machine is unavailable. Please check the network connection.'];
        }
    }

    /**
     * Fetch normalized attendance events for a date range (inclusive calendar days in configured TZ).
     * Names are filled from UserInfo when AcsEvent omits them.
     *
     * @return array{
     *   ok: bool,
     *   message: string,
     *   events: list<array{person_id: string, machine_name: string, attendance_date: string, attendance_time: string, attendance_datetime: string, machine_id: string, event_id: string, user_type: string}>,
     *   users: list<array{employee_no: string, name: string, user_type: string}>,
     *   retrieved: int
     * }
     */
    public function fetchEvents(DateTimeInterface $start, DateTimeInterface $end): array {
        if ($this->host === '' || $this->password === '') {
            return [
                'ok' => false,
                'message' => $this->missingCredentialsMessage(),
                'events' => [],
                'users' => [],
                'retrieved' => 0,
            ];
        }

        $userDir = $this->fetchUserDirectory();
        $userMap = [];
        foreach ($userDir['users'] as $u) {
            $userMap[$u['employee_no']] = $u;
        }

        $tz = new DateTimeZone($this->timezone);
        $startImm = $this->toImmutable($start)->setTimezone($tz);
        $endImm = $this->toImmutable($end)->setTimezone($tz);
        $startIso = $startImm->format('Y-m-d\TH:i:s');
        $endIso = $endImm->format('Y-m-d\TH:i:s');
        $url = rtrim($this->getBaseUrl(), '/') . '/ISAPI/AccessControl/AcsEvent?format=json';

        $byKey = [];
        $errors = [];

        foreach ($this->acsMinors as $minor) {
            $position = 0;
            $searchId = bin2hex(random_bytes(8));
            for ($page = 0; $page < $this->maxPages; $page++) {
                $payload = json_encode([
                    'AcsEventCond' => [
                        'searchID' => $searchId,
                        'searchResultPosition' => $position,
                        'maxResults' => $this->maxResults,
                        'major' => $this->acsMajor,
                        'minor' => $minor,
                        'startTime' => $startIso,
                        'endTime' => $endIso,
                    ],
                ], JSON_UNESCAPED_SLASHES);

                $res = $this->request('POST', $url, $payload, 'application/json', $this->timeout);
                if ($res['error'] !== null) {
                    $errors[] = $this->networkHint($res['error']);
                    break 2;
                }
                if ($res['http_code'] === 401) {
                    $errors[] = $this->formatUnauthorized($res['body']);
                    break 2;
                }
                if ($res['http_code'] < 200 || $res['http_code'] >= 300) {
                    if ($page === 0) {
                        $errors[] = 'HTTP ' . $res['http_code'] . ' for ACS minor ' . $minor;
                    }
                    break;
                }

                $data = json_decode($res['body'], true);
                if (!is_array($data)) {
                    $errors[] = 'Invalid JSON from machine AcsEvent.';
                    break 2;
                }

                $list = $this->extractInfoList($data);
                if ($list === []) {
                    break;
                }

                foreach ($list as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    $normalized = $this->normalizeEvent($item);
                    if ($normalized === null) {
                        continue;
                    }
                    // Fill name / type from UserInfo when AcsEvent has empty name
                    $pid = $normalized['person_id'];
                    if (isset($userMap[$pid])) {
                        if ($normalized['machine_name'] === '') {
                            $normalized['machine_name'] = $userMap[$pid]['name'];
                        }
                        if ($normalized['user_type'] === '' && $userMap[$pid]['user_type'] !== '') {
                            $normalized['user_type'] = $userMap[$pid]['user_type'];
                        }
                    }
                    $key = $normalized['event_id'] . '|' . $normalized['machine_id'];
                    $byKey[$key] = $normalized;
                }

                $count = count($list);
                $position += $count;
                if ($count < $this->maxResults) {
                    break;
                }
            }
        }

        $events = array_values($byKey);
        usort($events, static function ($a, $b) {
            return strcmp($b['attendance_datetime'], $a['attendance_datetime']);
        });

        if ($events === [] && $errors !== [] && empty($userDir['ok'])) {
            return [
                'ok' => false,
                'message' => $errors[0],
                'events' => [],
                'users' => $userDir['users'],
                'retrieved' => 0,
            ];
        }

        return [
            'ok' => true,
            'message' => 'OK',
            'events' => $events,
            'users' => $userDir['users'],
            'retrieved' => count($events),
        ];
    }

    /**
     * All persons enrolled on the terminal (employeeNo + name + userType).
     *
     * @return array{ok: bool, message: string, users: list<array{employee_no: string, name: string, user_type: string}>}
     */
    public function fetchUserDirectory(): array {
        if ($this->host === '' || $this->password === '') {
            return ['ok' => false, 'message' => 'Not configured', 'users' => []];
        }
        $url = rtrim($this->getBaseUrl(), '/') . '/ISAPI/AccessControl/UserInfo/Search?format=json';
        $users = [];
        $position = 0;
        $pageSize = 30; // device capability max
        for ($page = 0; $page < 500; $page++) {
            $payload = json_encode([
                'UserInfoSearchCond' => [
                    'searchID' => 'dir' . $page,
                    'searchResultPosition' => $position,
                    'maxResults' => $pageSize,
                ],
            ], JSON_UNESCAPED_SLASHES);
            $res = $this->request('POST', $url, $payload, 'application/json', $this->timeout);
            if ($res['error'] !== null || $res['http_code'] === 401) {
                return [
                    'ok' => false,
                    'message' => $res['error'] !== null ? $this->networkHint($res['error']) : $this->formatUnauthorized($res['body']),
                    'users' => array_values($users),
                ];
            }
            if ($res['http_code'] < 200 || $res['http_code'] >= 300) {
                break;
            }
            $data = json_decode($res['body'], true);
            $list = $data['UserInfoSearch']['UserInfo'] ?? [];
            if (isset($list['employeeNo']) || isset($list['name'])) {
                $list = [$list];
            }
            if (!is_array($list) || $list === []) {
                break;
            }
            $n = 0;
            foreach ($list as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $eno = trim((string) ($row['employeeNo'] ?? $row['employeeNoString'] ?? ''));
                if ($eno === '') {
                    continue;
                }
                $users[$eno] = [
                    'employee_no' => $eno,
                    'name' => trim((string) ($row['name'] ?? '')),
                    'user_type' => strtolower(trim((string) ($row['userType'] ?? $row['employeeType'] ?? 'normal'))),
                ];
                $n++;
            }
            $position += max($n, count($list));
            $numMatches = (int) ($data['UserInfoSearch']['numOfMatches'] ?? $n);
            $total = (int) ($data['UserInfoSearch']['totalMatches'] ?? 0);
            if ($numMatches < $pageSize || ($total > 0 && count($users) >= $total)) {
                break;
            }
        }
        return ['ok' => true, 'message' => 'OK', 'users' => array_values($users)];
    }

    /**
     * @param array<string, mixed> $item
     * @return array{person_id: string, machine_name: string, attendance_date: string, attendance_time: string, attendance_datetime: string, machine_id: string, event_id: string, user_type: string}|null
     */
    private function normalizeEvent(array $item): ?array {
        $personId = $this->extractPersonId($item);
        if ($personId === '') {
            return null;
        }
        $timeRaw = $this->extractTimeRaw($item);
        $dt = $this->parseDeviceTime($timeRaw);
        if ($dt === null) {
            return null;
        }
        $machineName = trim((string) ($item['name'] ?? $item['Name'] ?? $item['personName'] ?? ''));
        $userType = strtolower(trim((string) ($item['userType'] ?? $item['UserType'] ?? $item['employeeType'] ?? '')));
        $eventId = $this->extractEventId($item, $personId, $dt);
        $machineId = $this->host;

        return [
            'person_id' => $personId,
            'machine_name' => $machineName,
            'attendance_date' => $dt->format('Y-m-d'),
            'attendance_time' => $dt->format('H:i:s'),
            'attendance_datetime' => $dt->format('Y-m-d H:i:s'),
            'machine_id' => $machineId,
            'event_id' => $eventId,
            'user_type' => $userType,
        ];
    }

    /** @param array<string, mixed> $item */
    private function extractPersonId(array $item): string {
        $keys = [
            'employeeNoString', 'EmployeeNoString', 'employeeNo', 'EmployeeNo',
            'personID', 'PersonID', 'employeeID', 'EmployeeID', 'employeeId',
        ];
        foreach ($keys as $k) {
            if (isset($item[$k])) {
                $v = trim((string) $item[$k]);
                if ($v !== '') {
                    return $v;
                }
            }
        }
        return '';
    }

    /** @param array<string, mixed> $item */
    private function extractTimeRaw(array $item): string {
        foreach (['time', 'Time', 'dateTime', 'DateTime', 'eventTime', 'EventTime'] as $k) {
            if (!empty($item[$k])) {
                return trim((string) $item[$k]);
            }
        }
        return '';
    }

    /** @param array<string, mixed> $item */
    private function extractEventId(array $item, string $personId, DateTimeImmutable $dt): string {
        foreach (['serialNo', 'SerialNo', 'eventId', 'EventId', 'eventID', 'EventID', 'uuid', 'UUID'] as $k) {
            if (isset($item[$k]) && trim((string) $item[$k]) !== '') {
                return trim((string) $item[$k]);
            }
        }
        // Stable fallback when device omits serial — still de-dupes on re-sync
        return hash('sha256', $personId . '|' . $dt->format('Y-m-d H:i:s') . '|' . $this->host);
    }

    private function parseDeviceTime(string $raw): ?DateTimeImmutable {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        $tz = new DateTimeZone($this->timezone);
        try {
            return (new DateTimeImmutable($raw))->setTimezone($tz);
        } catch (Exception $e) {
            // fall through
        }
        foreach (['Y-m-d\TH:i:s', 'Y-m-d\TH:i:sP', 'Y-m-d H:i:s', 'Y/m/d H:i:s'] as $f) {
            $dt = DateTimeImmutable::createFromFormat($f, $raw, $tz);
            if ($dt !== false) {
                return $dt->setTimezone($tz);
            }
        }
        return null;
    }

    /** @return list<array<string, mixed>> */
    private function extractInfoList(array $data): array {
        if (isset($data['AcsEvent']['InfoList']) && is_array($data['AcsEvent']['InfoList'])) {
            $il = $data['AcsEvent']['InfoList'];
            if (isset($il['time']) || isset($il['employeeNoString']) || isset($il['EmployeeNoString'])) {
                return [$il];
            }
            $out = [];
            foreach ($il as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $out[] = isset($row['Info']) && is_array($row['Info']) ? $row['Info'] : $row;
            }
            return $out;
        }
        if (isset($data['AcsEventList']) && is_array($data['AcsEventList'])) {
            return array_values(array_filter($data['AcsEventList'], 'is_array'));
        }
        return [];
    }

    /**
     * @return array{http_code: int, body: string, error: ?string}
     */
    private function request(string $method, string $url, ?string $body, ?string $contentType, int $timeout): array {
        if (function_exists('curl_init')) {
            return $this->requestWithCurl($method, $url, $body, $contentType, $timeout);
        }
        return $this->requestWithStreams($method, $url, $body, $contentType, $timeout);
    }

    /**
     * @return array{http_code: int, body: string, error: ?string}
     */
    private function requestWithCurl(string $method, string $url, ?string $body, ?string $contentType, int $timeout): array {
        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPAUTH => CURLAUTH_DIGEST,
            CURLOPT_USERPWD => $this->username . ':' . $this->password,
            CURLOPT_CONNECTTIMEOUT => min(15, $timeout),
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
        ];
        if (defined('CURL_IPRESOLVE_V4')) {
            $opts[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
        }
        curl_setopt_array($ch, $opts);
        if (stripos($url, 'https:') === 0) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        }
        $headers = [];
        if ($body !== null && $contentType) {
            $headers[] = 'Content-Type: ' . $contentType;
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        if ($headers !== []) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($errno !== 0 || $response === false) {
            return ['http_code' => 0, 'body' => '', 'error' => $err !== '' ? $err : 'Request failed'];
        }
        return ['http_code' => $http, 'body' => (string) $response, 'error' => null];
    }

    /**
     * Digest via PHP streams when ext-curl is missing (same fallback as staff sync).
     *
     * @return array{http_code: int, body: string, error: ?string}
     */
    private function requestWithStreams(string $method, string $url, ?string $body, ?string $contentType, int $timeout): array {
        if (!ini_get('allow_url_fopen')) {
            return [
                'http_code' => 0,
                'body' => '',
                'error' => 'PHP curl is not enabled. Enable extension=curl in php.ini, or set allow_url_fopen=On for Digest fallback.',
            ];
        }
        $digestClient = (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__))
            . '/staff_attendance/includes/digest_http_client.php';
        if (!is_file($digestClient)) {
            return [
                'http_code' => 0,
                'body' => '',
                'error' => 'PHP curl extension is required (Digest fallback client missing).',
            ];
        }
        require_once $digestClient;
        $r = attendance_digest_request(
            $method,
            $url,
            $body,
            $contentType,
            $this->username,
            $this->password,
            $timeout,
            false
        );
        if ($r['error'] !== null && (int) $r['http_code'] === 0) {
            return ['http_code' => 0, 'body' => '', 'error' => $r['error']];
        }
        return [
            'http_code' => (int) $r['http_code'],
            'body' => (string) $r['body'],
            'error' => null,
        ];
    }

    private function missingCredentialsMessage(): string {
        return 'Machine credentials are not configured. Set password in config/student_attendance_machine.php '
            . '(or STUDENT_HIKVISION_* in .env / student_attendance_machine.local.php), then redeploy.';
    }

    private function formatUnauthorized(string $body): string {
        if (preg_match('/<lockStatus>\s*lock\s*<\/lockStatus>/i', $body)
            || preg_match('/<unlockTime>\s*(\d+)\s*<\/unlockTime>/i', $body, $m)) {
            $sec = isset($m[1]) ? (int) $m[1] : 0;
            $mins = max(1, (int) ceil(max(1, $sec) / 60));
            return 'Device admin account is temporarily locked. Wait about ' . $mins . ' minute(s) or reboot the terminal.';
        }
        return 'HTTP 401 Unauthorized — Digest login failed. Check username/password in config/student_attendance_machine.php or .env.';
    }

    private function networkHint(string $err): string {
        $e = strtolower($err);
        if (strpos($e, 'timed out') !== false || strpos($e, 'failed to connect') !== false
            || strpos($e, 'couldn\'t connect') !== false || strpos($e, 'refused') !== false) {
            return 'Attendance machine is unavailable. Please check the network connection.';
        }
        return $err;
    }

    /** @return array<string, string> */
    private function parseDeviceInfo(string $body): array {
        $out = [];
        foreach (['deviceName', 'model', 'serialNumber', 'deviceType', 'firmwareVersion'] as $tag) {
            if (preg_match('/<' . $tag . '>\s*([^<]+)\s*<\/' . $tag . '>/i', $body, $m)) {
                $out[$tag] = trim($m[1]);
            }
        }
        return $out;
    }

    private function toImmutable(DateTimeInterface $dt): DateTimeImmutable {
        if ($dt instanceof DateTimeImmutable) {
            return $dt;
        }
        if ($dt instanceof DateTime) {
            return DateTimeImmutable::createFromMutable($dt);
        }
        return new DateTimeImmutable($dt->format('Y-m-d H:i:s'), $dt->getTimezone());
    }
}
