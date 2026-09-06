<?php
/**
 * Hikvision Fingerprint Device Integration
 * Supports Hikvision ISAPI protocol for attendance data retrieval
 */

class HikvisionIntegration {
    private $host;
    private $port;
    private $username;
    private $password;
    private $timeout;
    private $ssl;
    private $baseUrl;
    private $lastDebugInfo = [];
    
    /**
     * Constructor
     * 
     * @param array $config Configuration array with:
     *   - host: Device IP address
     *   - port: Device port (default: 80)
     *   - username: Device username
     *   - password: Device password
     *   - timeout: Connection timeout in seconds (default: 10)
     */
    public function __construct($config = []) {
        $this->host = trim((string) ($config['host'] ?? '192.168.1.64'));
        $this->port = (int) ($config['port'] ?? 80);
        $this->username = trim((string) ($config['username'] ?? 'admin'));
        // Never default to a real password — empty means “not configured”
        $this->password = (string) ($config['password'] ?? '');
        $this->timeout = (int) ($config['timeout'] ?? 15);
        $this->ssl = !empty($config['ssl']);
        
        // Omit default ports in URL (some firmware Digest realms are picky about host:port)
        $protocol = $this->ssl ? 'https' : 'http';
        $defaultPort = $this->ssl ? 443 : 80;
        if ($this->port === $defaultPort || $this->port <= 0) {
            $this->baseUrl = "{$protocol}://{$this->host}/ISAPI";
        } else {
            $this->baseUrl = "{$protocol}://{$this->host}:{$this->port}/ISAPI";
        }
    }

    /**
     * Build client using the same constants as Device sync (staff_attendance/config.php).
     * This is the proven Digest + HTTP path for DS-K1T320MFWX.
     */
    public static function fromAttendanceSyncConfig(): self {
        if (!defined('HIKVISION_IP') && is_file(BASE_PATH . '/staff_attendance/config.php')) {
            require_once BASE_PATH . '/staff_attendance/config.php';
        }
        $https = defined('HIKVISION_USE_HTTPS') && HIKVISION_USE_HTTPS;
        $port = 0;
        if (defined('HIKVISION_HTTP_PORT')) {
            $port = (int) HIKVISION_HTTP_PORT;
        }
        if ($port <= 0) {
            $port = $https ? 443 : 80;
        }
        return new self([
            'host' => defined('HIKVISION_IP') ? (string) HIKVISION_IP : '172.16.0.230',
            'port' => $port,
            'username' => defined('HIKVISION_USER') ? (string) HIKVISION_USER : 'admin',
            'password' => defined('HIKVISION_PASS') ? (string) HIKVISION_PASS : '',
            'timeout' => defined('HIKVISION_CURL_TIMEOUT') ? (int) HIKVISION_CURL_TIMEOUT : 60,
            'ssl' => $https,
        ]);
    }

    /** Student fingerprint terminal (config/student_attendance_machine.php + .env). */
    public static function fromStudentAttendanceConfig(?string $hostOverride = null): self {
        $config = require BASE_PATH . '/config/student_attendance_machine.php';
        $https = !empty($config['ssl']);
        $port = (int) ($config['port'] ?? 0);
        if ($port <= 0) {
            $port = $https ? 443 : 80;
        }
        $host = $hostOverride !== null && trim($hostOverride) !== ''
            ? trim($hostOverride)
            : (string) ($config['host'] ?? '');
        return new self([
            'host' => $host,
            'port' => $port,
            'username' => (string) ($config['username'] ?? 'admin'),
            'password' => (string) ($config['password'] ?? ''),
            'timeout' => max(15, (int) ($config['timeout'] ?? 60)),
            'ssl' => $https,
        ]);
    }

    /**
     * Client for one device entry from student_attendance_machine.php `devices` list.
     *
     * @param array{host:string,username?:string,password?:string,ssl?:bool,port?:int,timeout?:int} $device
     */
    public static function fromStudentDevice(array $device): self {
        $https = !empty($device['ssl']);
        $port = (int) ($device['port'] ?? 0);
        if ($port <= 0) {
            $port = $https ? 443 : 80;
        }
        return new self([
            'host' => trim((string) ($device['host'] ?? '')),
            'port' => $port,
            'username' => (string) ($device['username'] ?? 'admin'),
            'password' => (string) ($device['password'] ?? ''),
            'timeout' => max(15, (int) ($device['timeout'] ?? 60)),
            'ssl' => $https,
        ]);
    }

    /** Endpoint base used for requests (for diagnostics). */
    public function getBaseUrl(): string {
        return $this->baseUrl;
    }

    public function getHost(): string {
        return $this->host;
    }

    public function getAuthUser(): string {
        return $this->username;
    }
    
    /**
     * Test connection to Hikvision device
     * 
     * @return array ['success' => bool, 'message' => string]
     */
    public function testConnection() {
        try {
            // Prefer same URL shape as Device sync (omit default :80 / :443)
            $urls = [
                $this->baseUrl . '/System/deviceInfo',
            ];
            $protocol = $this->ssl ? 'https' : 'http';
            $defaultPort = $this->ssl ? 443 : 80;
            if ($this->port > 0 && $this->port !== $defaultPort) {
                $urls[] = "{$protocol}://{$this->host}:{$this->port}/ISAPI/System/deviceInfo";
            } else {
                $urls[] = "{$protocol}://{$this->host}/ISAPI/System/deviceInfo";
            }
            
            $lastError = '';
            foreach (array_unique($urls) as $url) {
                try {
                    $response = $this->makeRequest($url, 'GET', null, null);
                    
                    if ($response && isset($response['DeviceInfo'])) {
                        return [
                            'success' => true,
                            'message' => 'Connection successful',
                            'device_info' => $response['DeviceInfo'],
                            'url' => $url
                        ];
                    }
                    
                    // If we get a response but not the expected format, return success with raw response
                    if ($response !== false) {
                        return [
                            'success' => true,
                            'message' => 'Connection successful (unexpected response format)',
                            'raw_response' => $response,
                            'url' => $url
                        ];
                    }
                } catch (Exception $e) {
                    $lastError = $e->getMessage();
                    continue; // Try next URL
                }
            }
            
            return [
                'success' => false,
                'message' => 'Failed to get device information. Last error: ' . $lastError
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get attendance records from Hikvision device
     * 
     * @param string $startTime Start time (format: YYYY-MM-DDTHH:mm:ss)
     * @param string $endTime End time (format: YYYY-MM-DDTHH:mm:ss)
     * @return array Array of attendance records
     */
    public function getAttendanceRecords($startTime = null, $endTime = null) {
        try {
            // Default to today if not specified
            if (!$startTime) {
                $startTime = date('Y-m-d') . 'T00:00:00';
            }
            if (!$endTime) {
                $endTime = date('Y-m-d') . 'T23:59:59';
            }
            
            // Convert to Hikvision time format if needed
            $startTime = $this->formatTime($startTime);
            $endTime = $this->formatTime($endTime);
            
            // Build XML request for attendance records
            $xml = '<?xml version="1.0" encoding="UTF-8"?>
<CMSearchDescription>
    <searchID>1</searchID>
    <searchResultPosition>0</searchResultPosition>
    <maxResults>1000</maxResults>
    <timeList>
        <timeDescription>
            <startTime>' . $startTime . '</startTime>
            <endTime>' . $endTime . '</endTime>
        </timeDescription>
    </timeList>
</CMSearchDescription>';
            
            $url = $this->baseUrl . "/AccessControl/AcsEvent";
            
            // Log the request for debugging
            error_log("Hikvision request URL: $url");
            error_log("Hikvision request XML: $xml");
            
            $response = $this->makeRequest($url, 'POST', $xml, 'application/xml');
            
            // Log the raw response for debugging
            error_log("Hikvision response: " . json_encode($response, JSON_PRETTY_PRINT));
            
            if (!$response || empty($response)) {
                error_log("Hikvision: Empty response received");
                return [];
            }
            
            // Parse attendance records - try different response structures
            $records = [];
            
            // Try standard Hikvision format
            if (isset($response['CMSearchResult']['matchList']['searchMatchItem'])) {
                $items = $response['CMSearchResult']['matchList']['searchMatchItem'];
                
                // Handle single item vs array
                if (!isset($items[0]) && isset($items['AcsEvent'])) {
                    $items = [$items];
                }
                
                foreach ($items as $item) {
                    if (isset($item['AcsEvent'])) {
                        $event = $item['AcsEvent'];
                        $records[] = [
                            'employee_id' => $event['employeeNoString'] ?? $event['employeeNo'] ?? '',
                            'employee_name' => $event['employeeNoString'] ?? $event['name'] ?? '',
                            'card_no' => $event['cardNo'] ?? '',
                            'time' => $event['time'] ?? '',
                            'date' => $this->extractDate($event['time'] ?? ''),
                            'type' => $event['eventType'] ?? $event['eventTypeDesc'] ?? '1',
                            'device_id' => $event['deviceName'] ?? $event['deviceID'] ?? '',
                            'door_id' => $event['doorNo'] ?? '1'
                        ];
                    }
                }
            }
            // Try alternative format (direct AcsEvent array)
            elseif (isset($response['AcsEvent'])) {
                $items = $response['AcsEvent'];
                if (!isset($items[0])) {
                    $items = [$items];
                }
                foreach ($items as $event) {
                    $records[] = [
                        'employee_id' => $event['employeeNoString'] ?? $event['employeeNo'] ?? '',
                        'employee_name' => $event['employeeNoString'] ?? $event['name'] ?? '',
                        'card_no' => $event['cardNo'] ?? '',
                        'time' => $event['time'] ?? '',
                        'date' => $this->extractDate($event['time'] ?? ''),
                        'type' => $event['eventType'] ?? $event['eventTypeDesc'] ?? '1',
                        'device_id' => $event['deviceName'] ?? $event['deviceID'] ?? '',
                        'door_id' => $event['doorNo'] ?? '1'
                    ];
                }
            }
            // Try matchList format
            elseif (isset($response['matchList']['searchMatchItem'])) {
                $items = $response['matchList']['searchMatchItem'];
                if (!isset($items[0])) {
                    $items = [$items];
                }
                foreach ($items as $item) {
                    if (isset($item['AcsEvent'])) {
                        $event = $item['AcsEvent'];
                        $records[] = [
                            'employee_id' => $event['employeeNoString'] ?? $event['employeeNo'] ?? '',
                            'employee_name' => $event['employeeNoString'] ?? $event['name'] ?? '',
                            'card_no' => $event['cardNo'] ?? '',
                            'time' => $event['time'] ?? '',
                            'date' => $this->extractDate($event['time'] ?? ''),
                            'type' => $event['eventType'] ?? $event['eventTypeDesc'] ?? '1',
                            'device_id' => $event['deviceName'] ?? $event['deviceID'] ?? '',
                            'door_id' => $event['doorNo'] ?? '1'
                        ];
                    }
                }
            }
            
            error_log("Hikvision: Parsed " . count($records) . " records");
            
            return $records;
        } catch (Exception $e) {
            error_log('Hikvision getAttendanceRecords error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all users from Hikvision device
     * 
     * @return array Array of user records
     */
    public function getUsers() {
        try {
            $url = $this->baseUrl . "/AccessControl/UserInfo/Record?format=json";
            $response = $this->makeRequest($url, 'GET');
            
            $users = [];
            if ($response && isset($response['UserInfoSearch']['UserInfo'])) {
                $userList = $response['UserInfoSearch']['UserInfo'];
                
                // Handle single user vs array
                if (!isset($userList[0])) {
                    $userList = [$userList];
                }
                
                foreach ($userList as $user) {
                    $users[] = [
                        'employee_no' => $user['employeeNo'] ?? '',
                        'name' => $user['name'] ?? '',
                        'user_type' => $user['userType'] ?? 'normal',
                        'valid' => $user['Valid'] ?? true,
                        'card_no' => $user['cardNo'] ?? ''
                    ];
                }
            }
            
            return $users;
        } catch (Exception $e) {
            error_log('Hikvision getUsers error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Make HTTP request to Hikvision device
     * 
     * @param string $url Full URL
     * @param string $method HTTP method (GET, POST, PUT, DELETE)
     * @param string $data Request body data
     * @param string $contentType Content-Type header
     * @return array|false Decoded response or false on failure
     */
    /**
     * Explain HTTP 401 from Hikvision userCheck XML (wrong password vs temporary lockout).
     */
    private function formatUnauthorizedMessage(string $url, string $body): string {
        $lockStatus = '';
        $unlockTime = 0;
        if (preg_match('/<lockStatus>\s*([^<]+)\s*<\/lockStatus>/i', $body, $m)) {
            $lockStatus = strtolower(trim($m[1]));
        }
        if (preg_match('/<unlockTime>\s*(\d+)\s*<\/unlockTime>/i', $body, $m)) {
            $unlockTime = (int) $m[1];
        }

        $hostHint = $this->host !== '' ? $this->host : 'device';
        if ($lockStatus === 'lock' || $unlockTime > 0) {
            $mins = max(1, (int) ceil(max($unlockTime, 1) / 60));
            return 'Admin locked after failed logins — wait ~' . $mins
                . ' min or reboot ' . $hostHint
                . ', then login once in the browser (http://' . $hostHint
                . ') to confirm the password before using MIS. '
                . 'Each wrong MIS attempt re-locks the terminal — do not retry capture yet.';
        }

        return 'HTTP 401 Unauthorized (user=' . $this->username . '). '
            . 'Wrong password on this server — set STUDENT_HIKVISION_PASS / local.php to the same password as http://'
            . $hostHint . ' web login.';
    }

    /**
     * Single Digest login check (deviceInfo). Used before capture so we do not hammer CaptureFingerPrint.
     *
     * @return array{ok:bool,http_code:int,message:string,model?:string}
     */
    public function assertDigestLogin(): array {
        if (!$this->hasPasswordConfigured()) {
            return [
                'ok' => false,
                'http_code' => 0,
                'message' => 'Password empty on this server — save STUDENT_HIKVISION_PASS / local.php first.',
                'model' => '',
            ];
        }
        $res = $this->makeRequestDetailed(
            $this->baseUrl . '/System/deviceInfo',
            'GET',
            null,
            null,
            min(12, max(5, (int) $this->timeout))
        );
        if (!empty($res['ok'])) {
            $model = '';
            if (is_array($res['decoded'])) {
                $di = $res['decoded']['DeviceInfo'] ?? $res['decoded'];
                if (is_array($di)) {
                    $model = (string) ($di['model'] ?? $di['deviceName'] ?? $di['deviceType'] ?? '');
                }
            }
            return [
                'ok' => true,
                'http_code' => (int) $res['http_code'],
                'message' => 'OK',
                'model' => $model,
            ];
        }
        $msg = (string) ($res['error'] ?? '');
        if ($msg === '' && (int) $res['http_code'] === 401) {
            $msg = $this->formatUnauthorizedMessage($this->baseUrl . '/System/deviceInfo', (string) ($res['body'] ?? ''));
        }
        if ($msg === '') {
            $msg = 'Login failed HTTP ' . (int) ($res['http_code'] ?? 0);
        }
        return [
            'ok' => false,
            'http_code' => (int) ($res['http_code'] ?? 0),
            'message' => $msg,
            'model' => '',
        ];
    }

    /**
     * Warm Digest challenge/response with a cheap GET before Capture/Download POSTs.
     * @return array{ok:bool,message:string}
     */
    private function warmDigestAuth(): array {
        $check = $this->assertDigestLogin();
        return ['ok' => !empty($check['ok']), 'message' => (string) ($check['message'] ?? '')];
    }

    /**
     * Whether PHP cURL is available (required for Hikvision ISAPI).
     */
    public static function isCurlAvailable(): bool {
        return function_exists('curl_init') && function_exists('curl_setopt_array');
    }

    /**
     * @throws RuntimeException when php-curl is not installed
     */
    private function ensureCurlAvailable(): void {
        if (self::isCurlAvailable()) {
            return;
        }
        throw new RuntimeException(
            'PHP cURL extension is not installed (curl_init missing). '
            . 'On the server install php-curl (e.g. apt install php-curl) and restart PHP-FPM/Apache.'
        );
    }

    /**
     * cURL options matching staff_attendance/includes/hikvision_sync_lib.php (working Digest path).
     *
     * @return \CurlHandle|resource
     */
    private function createDigestCurl(string $url, int $timeout) {
        $this->ensureCurlAvailable();
        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPAUTH => CURLAUTH_DIGEST,
            CURLOPT_USERPWD => $this->username . ':' . $this->password,
            CURLOPT_CONNECTTIMEOUT => min(15, max(3, $timeout)),
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_FOLLOWLOCATION => false,
            // Digest + POST often breaks if curl sends Expect: 100-continue
            CURLOPT_HTTPHEADER => ['Expect:'],
        ];
        if (defined('CURL_IPRESOLVE_V4')) {
            $opts[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
        }
        curl_setopt_array($ch, $opts);
        if (stripos($url, 'https:') === 0) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        }
        return $ch;
    }

    public function hasPasswordConfigured(): bool {
        return trim($this->password) !== '';
    }

    /**
     * LAN-only device health check (no Internet / external DNS).
     *
     * Status:
     *   online      — HTTP + Digest auth + deviceInfo OK
     *   auth_error  — device reachable but login failed / locked / HTTP 401/403
     *   offline     — SIS server cannot reach device (timeout, refused, no route)
     *   invalid_config — missing host/password/cURL
     *
     * @return array{
     *   status: string,
     *   online: bool,
     *   locked: bool,
     *   tcp_ok: bool|null,
     *   http_ok: bool,
     *   auth_ok: bool,
     *   http_code: int,
     *   port: int,
     *   category: string,
     *   reason: string,
     *   message: string,
     *   model: string,
     *   checked_at: string,
     *   sis_server: string
     * }
     */
    public function diagnoseLanConnection(bool $attemptAuth = true): array {
        $checkedAt = date('Y-m-d H:i:s');
        $port = $this->port > 0 ? $this->port : ($this->ssl ? 443 : 80);
        $sisServer = $this->detectSisServerIdentity();
        $base = [
            'status' => 'offline',
            'online' => false,
            'locked' => false,
            'tcp_ok' => null,
            'http_ok' => false,
            'auth_ok' => false,
            'http_code' => 0,
            'port' => $port,
            'category' => 'routing',
            'reason' => 'Device unreachable',
            'message' => 'Device unreachable',
            'model' => '',
            'checked_at' => $checkedAt,
            'sis_server' => $sisServer,
        ];

        $host = trim($this->host);
        if ($host === '' || filter_var($host, FILTER_VALIDATE_IP) === false) {
            return [
                'status' => 'invalid_config',
                'online' => false,
                'locked' => false,
                'tcp_ok' => false,
                'http_ok' => false,
                'auth_ok' => false,
                'http_code' => 0,
                'port' => $port,
                'category' => 'config',
                'reason' => 'Invalid configuration',
                'message' => 'Invalid configuration — device host IP missing or invalid',
                'model' => '',
                'checked_at' => $checkedAt,
                'sis_server' => $sisServer,
            ];
        }
        // Keep trimmed host for probes
        $this->host = $host;

        if (!self::isCurlAvailable()) {
            return [
                'status' => 'invalid_config',
                'online' => false,
                'locked' => false,
                'tcp_ok' => false,
                'http_ok' => false,
                'auth_ok' => false,
                'http_code' => 0,
                'port' => $port,
                'category' => 'config',
                'reason' => 'Invalid configuration',
                'message' => 'Invalid configuration — PHP cURL not installed on SIS server (' . $sisServer . ')',
                'model' => '',
                'checked_at' => $checkedAt,
                'sis_server' => $sisServer,
            ];
        }

        // 1) Prefer cURL HTTP reachability (works even when fsockopen is disabled)
        $http = $this->probeHttpReachable($port, 5);
        $base['http_code'] = (int) ($http['http_code'] ?? 0);

        // 2) Optional TCP probe (informational; skipped if fsockopen disabled)
        $tcp = $this->probeTcpPort($host, $port, 3);
        if (!empty($tcp['skipped'])) {
            $base['tcp_ok'] = null;
        } else {
            $base['tcp_ok'] = !empty($tcp['ok']);
        }

        if (empty($http['ok'])) {
            $reason = (string) ($http['reason'] ?? 'Device unreachable');
            // Prefer TCP reason when HTTP timed out with no detail
            if (($reason === 'Device unreachable' || $reason === 'Timeout')
                && empty($tcp['skipped'])
                && empty($tcp['ok'])
                && !empty($tcp['reason'])
            ) {
                $reason = (string) $tcp['reason'];
            }
            $category = ($reason === 'Timeout' || $reason === 'Connection refused') ? 'firewall_port' : 'routing';
            error_log('[Hikvision LAN] SIS=' . $sisServer . ' cannot reach ' . $host . ':' . $port . ' — ' . $reason);
            return [
                'status' => 'offline',
                'online' => false,
                'locked' => false,
                'tcp_ok' => $base['tcp_ok'],
                'http_ok' => false,
                'auth_ok' => false,
                'http_code' => (int) ($http['http_code'] ?? 0),
                'port' => $port,
                'category' => $category,
                'reason' => $reason,
                'message' => 'OFFLINE — SIS server (' . $sisServer . ') cannot reach '
                    . $host . ':' . $port . ' (' . $reason . '). '
                    . 'Fix routing/VLAN/firewall between the SIS host and Hikvision LAN 172.16.0.0/24. '
                    . 'Internet is not required; this is a local network path problem.',
                'model' => '',
                'checked_at' => $checkedAt,
                'sis_server' => $sisServer,
            ];
        }
        $base['http_ok'] = true;
        // If TCP probe ran and failed but HTTP works, ignore TCP quirk
        if ($base['tcp_ok'] === false) {
            $base['tcp_ok'] = true;
        }

        if (!$attemptAuth) {
            return [
                'status' => 'auth_error',
                'online' => false,
                'locked' => false,
                'tcp_ok' => $base['tcp_ok'],
                'http_ok' => true,
                'auth_ok' => false,
                'http_code' => $base['http_code'],
                'port' => $port,
                'category' => 'auth',
                'reason' => 'Authentication skipped',
                'message' => 'LAN OK from SIS (' . $sisServer . ') — auth skipped to avoid admin lockout',
                'model' => '',
                'checked_at' => $checkedAt,
                'sis_server' => $sisServer,
            ];
        }

        if (!$this->hasPasswordConfigured()) {
            return [
                'status' => 'invalid_config',
                'online' => false,
                'locked' => false,
                'tcp_ok' => $base['tcp_ok'],
                'http_ok' => true,
                'auth_ok' => false,
                'http_code' => $base['http_code'],
                'port' => $port,
                'category' => 'config',
                'reason' => 'Invalid configuration',
                'message' => 'Device reachable from SIS — password empty on this server '
                    . '(set HIKVISION_PASS / STUDENT_HIKVISION_PASS in .env on ' . $sisServer . ')',
                'model' => '',
                'checked_at' => $checkedAt,
                'sis_server' => $sisServer,
            ];
        }

        // 3) ONE Digest auth + deviceInfo (never retry here)
        $auth = $this->assertDigestLogin();
        $httpCode = (int) ($auth['http_code'] ?? $base['http_code']);
        if (empty($auth['ok'])) {
            $msg = (string) ($auth['message'] ?? 'Authentication failed');
            $locked = stripos($msg, 'locked') !== false;
            $reason = 'Authentication failed';
            if ($httpCode === 403 || stripos($msg, '403') !== false) {
                $reason = 'HTTP 403';
            } elseif ($httpCode === 401 || stripos($msg, '401') !== false) {
                $reason = 'HTTP 401';
            }
            error_log('[Hikvision LAN] ' . $host . ' AUTH fail from SIS=' . $sisServer . ': ' . $reason);
            return [
                'status' => 'auth_error',
                'online' => false,
                'locked' => $locked,
                'tcp_ok' => $base['tcp_ok'],
                'http_ok' => true,
                'auth_ok' => false,
                'http_code' => $httpCode,
                'port' => $port,
                'category' => 'auth',
                'reason' => $reason,
                'message' => 'AUTH ERROR — ' . $msg,
                'model' => '',
                'checked_at' => $checkedAt,
                'sis_server' => $sisServer,
            ];
        }

        return [
            'status' => 'online',
            'online' => true,
            'locked' => false,
            'tcp_ok' => true,
            'http_ok' => true,
            'auth_ok' => true,
            'http_code' => $httpCode > 0 ? $httpCode : 200,
            'port' => $port,
            'category' => 'ok',
            'reason' => 'OK',
            'message' => 'ONLINE — reachable from SIS (' . $sisServer . ') + Digest auth OK',
            'model' => (string) ($auth['model'] ?? ''),
            'checked_at' => $checkedAt,
            'sis_server' => $sisServer,
        ];
    }

    /** Identity of the PHP host running this check (helps diagnose routing). */
    private function detectSisServerIdentity(): string {
        $parts = [];
        $addr = (string) ($_SERVER['SERVER_ADDR'] ?? '');
        if ($addr !== '') {
            $parts[] = $addr;
        }
        $name = (string) (@gethostname() ?: '');
        if ($name !== '' && $name !== $addr) {
            $parts[] = $name;
        }
        if (defined('SIS_LAN_IP') && SIS_LAN_IP !== '') {
            $parts[] = 'configured-lan=' . SIS_LAN_IP;
        }
        return $parts !== [] ? implode(' / ', $parts) : 'sis.slgti.ac.lk-host';
    }

    /**
     * @return array{ok:bool,reason:string,skipped?:bool}
     */
    private function probeTcpPort(string $host, int $port, int $timeoutSec = 3): array {
        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
        if (!function_exists('fsockopen') || in_array('fsockopen', $disabled, true)) {
            return ['ok' => false, 'reason' => 'TCP probe unavailable', 'skipped' => true];
        }
        $errno = 0;
        $errstr = '';
        $fp = @fsockopen($host, $port, $errno, $errstr, max(1, $timeoutSec));
        if ($fp !== false && $fp !== null) {
            fclose($fp);
            return ['ok' => true, 'reason' => 'OK'];
        }
        $err = strtolower(trim($errstr . ' ' . $errno));
        if (str_contains($err, 'timed out') || str_contains($err, 'timeout') || $errno === 10060 || $errno === 110) {
            return ['ok' => false, 'reason' => 'Timeout'];
        }
        if (str_contains($err, 'refused') || $errno === 10061 || $errno === 111) {
            return ['ok' => false, 'reason' => 'Connection refused'];
        }
        if (str_contains($err, 'network is unreachable') || str_contains($err, 'no route') || $errno === 101 || $errno === 113) {
            return ['ok' => false, 'reason' => 'Device unreachable'];
        }
        if ($errstr !== '') {
            return ['ok' => false, 'reason' => 'Device unreachable (' . trim($errstr) . ')'];
        }
        return ['ok' => false, 'reason' => 'Device unreachable'];
    }

    /**
     * HTTP GET without Digest — Hikvision normally returns 401 (proves HTTP service is up).
     *
     * @return array{ok:bool,http_code:int,reason:string}
     */
    private function probeHttpReachable(int $port, int $timeoutSec = 5): array {
        $protocol = $this->ssl ? 'https' : 'http';
        $url = $protocol . '://' . $this->host
            . (($port === 80 && !$this->ssl) || ($port === 443 && $this->ssl) ? '' : ':' . $port)
            . '/ISAPI/System/deviceInfo';
        try {
            $this->ensureCurlAvailable();
        } catch (Throwable $e) {
            return ['ok' => false, 'http_code' => 0, 'reason' => 'Invalid configuration'];
        }
        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $timeoutSec,
            CURLOPT_TIMEOUT => $timeoutSec,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTPHEADER => ['Expect:'],
            CURLOPT_NOBODY => false,
        ];
        if (defined('CURL_IPRESOLVE_V4')) {
            $opts[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
        }
        if ($this->ssl) {
            $opts[CURLOPT_SSL_VERIFYPEER] = false;
            $opts[CURLOPT_SSL_VERIFYHOST] = 0;
        }
        curl_setopt_array($ch, $opts);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = (string) curl_error($ch);
        curl_close($ch);

        if ($body === false || ($code === 0 && $err !== '')) {
            $el = strtolower($err);
            if (str_contains($el, 'timed out') || str_contains($el, 'timeout')) {
                return ['ok' => false, 'http_code' => 0, 'reason' => 'Timeout'];
            }
            if (str_contains($el, 'refused')) {
                return ['ok' => false, 'http_code' => 0, 'reason' => 'Connection refused'];
            }
            return ['ok' => false, 'http_code' => 0, 'reason' => $err !== '' ? $err : 'Device unreachable'];
        }

        // Any HTTP response from the device means the HTTP service is reachable on LAN
        if ($code > 0) {
            return ['ok' => true, 'http_code' => $code, 'reason' => 'OK'];
        }
        return ['ok' => false, 'http_code' => 0, 'reason' => 'Device unreachable'];
    }

    private function makeRequest($url, $method = 'GET', $data = null, $contentType = 'application/json', $timeoutOverride = null) {
        $timeout = $timeoutOverride !== null ? (int) $timeoutOverride : (int) $this->timeout;
        $ch = $this->createDigestCurl($url, $timeout);

        $headers = ['Expect:'];
        $method = strtoupper((string) $method);
        if ($data !== null && $data !== '' && $contentType) {
            $headers[] = 'Content-Type: ' . $contentType;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data ?? '');
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data ?? '');
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        } else {
            curl_setopt($ch, CURLOPT_HTTPGET, true);
        }

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = (string) curl_error($ch);
        $respCt = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        error_log("Hikvision makeRequest - URL: $url, Method: $method, HTTP Code: $httpCode, Content-Type: $respCt");
        error_log("Hikvision makeRequest - Raw response (first 1000 chars): " . substr((string) $response, 0, 1000));

        if ($response === false || $error !== '') {
            throw new Exception('CURL Error: ' . ($error !== '' ? $error : 'empty response'));
        }

        if ($httpCode === 401) {
            throw new Exception($this->formatUnauthorizedMessage($url, (string) $response));
        }

        if ($httpCode >= 400) {
            throw new Exception('HTTP Error: ' . $httpCode . ' - Response: ' . substr((string) $response, 0, 200));
        }

        if ($response === '' || $response === '0') {
            return false;
        }

        $decoded = json_decode((string) $response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }
        $decoded = $this->parseXML((string) $response);
        if (!empty($decoded)) {
            return $decoded;
        }
        return ['_raw_response' => (string) $response, '_response_length' => strlen((string) $response)];
    }
    
    /**
     * Parse XML response
     * 
     * @param string $xml XML string
     * @return array Parsed array
     */
    private function parseXML($xml) {
        try {
            $xml = simplexml_load_string($xml);
            if ($xml === false) {
                return [];
            }
            
            // Convert to array
            $json = json_encode($xml);
            return json_decode($json, true);
        } catch (Exception $e) {
            error_log('XML parse error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Format time for Hikvision API
     * 
     * @param string $time Time string
     * @return string Formatted time
     */
    private function formatTime($time) {
        // If already in correct format, return as is
        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/', $time)) {
            return $time;
        }
        
        // Try to convert from various formats
        $timestamp = strtotime($time);
        if ($timestamp === false) {
            return date('Y-m-d\TH:i:s');
        }
        
        return date('Y-m-d\TH:i:s', $timestamp);
    }
    
    /**
     * Extract date from Hikvision time format
     * 
     * @param string $time Hikvision time string (YYYY-MM-DDTHH:mm:ss)
     * @return string Date in YYYY-MM-DD format
     */
    private function extractDate($time) {
        if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $time, $matches)) {
            return $matches[1];
        }
        return date('Y-m-d');
    }
    
    /**
     * Get attendance report data from Hikvision device
     * This method retrieves comprehensive attendance records similar to the web interface report
     * 
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     * @param string $employeeId Optional employee ID to filter
     * @return array Array of attendance records
     */
    public function getAttendanceReport($startDate = null, $endDate = null, $employeeId = null) {
        try {
            // Default to last 30 days if not specified
            if (!$startDate) {
                $startDate = date('Y-m-d', strtotime('-30 days'));
            }
            if (!$endDate) {
                $endDate = date('Y-m-d');
            }
            
            // Format times for API
            $startTime = $this->formatTime($startDate . 'T00:00:00');
            $endTime = $this->formatTime($endDate . 'T23:59:59');
            
            // Try multiple API endpoints for attendance reports
            $endpoints = [
                '/AccessControl/AcsEvent',
                '/AccessControl/AcsEventRecord',
                '/AccessControl/Report/AttendanceRecord',
            ];
            
            $allRecords = [];
            
            foreach ($endpoints as $endpoint) {
                try {
                    // Build XML request
                    $xml = '<?xml version="1.0" encoding="UTF-8"?>
<CMSearchDescription>
    <searchID>1</searchID>
    <searchResultPosition>0</searchResultPosition>
    <maxResults>10000</maxResults>
    <timeList>
        <timeDescription>
            <startTime>' . htmlspecialchars($startTime) . '</startTime>
            <endTime>' . htmlspecialchars($endTime) . '</endTime>
        </timeDescription>
    </timeList>';
                    
                    // Add employee filter if provided
                    if ($employeeId) {
                        $xml .= '
    <employeeNoList>
        <employeeNo>' . htmlspecialchars($employeeId) . '</employeeNo>
    </employeeNoList>';
                    }
                    
                    $xml .= '
</CMSearchDescription>';
                    
                    $url = $this->baseUrl . $endpoint;
                    error_log("Hikvision attendance report request URL: $url");
                    error_log("Hikvision attendance report request XML: $xml");
                    
                    $response = $this->makeRequest($url, 'POST', $xml, 'application/xml');
                    
                    if ($response && !empty($response)) {
                        error_log("Hikvision attendance report response: " . json_encode($response, JSON_PRETTY_PRINT));
                        
                        // Parse response using the same logic as getAttendanceRecords
                        $records = $this->parseAttendanceResponse($response);
                        
                        if (!empty($records)) {
                            $allRecords = array_merge($allRecords, $records);
                            error_log("Hikvision: Found " . count($records) . " records from endpoint: $endpoint");
                            break; // Use the first endpoint that returns data
                        }
                    }
                } catch (Exception $e) {
                    error_log("Hikvision endpoint $endpoint error: " . $e->getMessage());
                    continue; // Try next endpoint
                }
            }
            
            // Remove duplicates based on employee_id + time
            $uniqueRecords = [];
            $seen = [];
            foreach ($allRecords as $record) {
                $key = ($record['employee_id'] ?? '') . '_' . ($record['time'] ?? '');
                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $uniqueRecords[] = $record;
                }
            }
            
            error_log("Hikvision: Total unique attendance records: " . count($uniqueRecords));
            
            // If no records found, store debug info
            if (empty($uniqueRecords)) {
                $this->lastDebugInfo = [
                    'endpoints_tried' => $endpoints,
                    'date_range' => [
                        'start' => $startDate,
                        'end' => $endDate,
                        'start_time' => $startTime,
                        'end_time' => $endTime
                    ],
                    'employee_id' => $employeeId
                ];
            }
            
            return $uniqueRecords;
            
        } catch (Exception $e) {
            error_log('Hikvision getAttendanceReport error: ' . $e->getMessage());
            $this->lastDebugInfo = ['error' => $e->getMessage()];
            return [];
        }
    }
    
    /**
     * Get last debug information (for troubleshooting)
     */
    public function getLastDebugInfo() {
        return $this->lastDebugInfo;
    }
    
    /**
     * Parse attendance response from Hikvision API
     * Handles multiple response formats
     * 
     * @param array $response API response array
     * @return array Parsed records
     */
    private function parseAttendanceResponse($response) {
        $records = [];
        
        if (empty($response) || !is_array($response)) {
            error_log("Hikvision parseAttendanceResponse: Empty or invalid response");
            return $records;
        }
        
        error_log("Hikvision parseAttendanceResponse: Response keys: " . implode(', ', array_keys($response)));
        
        // Try standard Hikvision format
        if (isset($response['CMSearchResult']['matchList']['searchMatchItem'])) {
            error_log("Hikvision: Found CMSearchResult.matchList.searchMatchItem structure");
            $items = $response['CMSearchResult']['matchList']['searchMatchItem'];
            
            // Handle single item vs array
            if (!isset($items[0]) && isset($items['AcsEvent'])) {
                $items = [$items];
            }
            
            foreach ($items as $item) {
                if (isset($item['AcsEvent'])) {
                    $event = $item['AcsEvent'];
                    $records[] = $this->formatAttendanceRecord($event);
                }
            }
        }
        // Try alternative format (direct AcsEvent array)
        elseif (isset($response['AcsEvent'])) {
            $items = $response['AcsEvent'];
            if (!isset($items[0])) {
                $items = [$items];
            }
            foreach ($items as $event) {
                $records[] = $this->formatAttendanceRecord($event);
            }
        }
        // Try matchList format
        elseif (isset($response['matchList']['searchMatchItem'])) {
            error_log("Hikvision: Found matchList.searchMatchItem structure");
            $items = $response['matchList']['searchMatchItem'];
            if (!isset($items[0])) {
                $items = [$items];
            }
            foreach ($items as $item) {
                if (isset($item['AcsEvent'])) {
                    $event = $item['AcsEvent'];
                    $records[] = $this->formatAttendanceRecord($event);
                }
            }
        }
        // Try CMSearchResult format (direct)
        elseif (isset($response['CMSearchResult']['matchList'])) {
            error_log("Hikvision: Found CMSearchResult.matchList structure");
            $matchList = $response['CMSearchResult']['matchList'];
            if (isset($matchList['searchMatchItem'])) {
                $items = $matchList['searchMatchItem'];
                if (!isset($items[0])) {
                    $items = [$items];
                }
                foreach ($items as $item) {
                    if (isset($item['AcsEvent'])) {
                        $event = $item['AcsEvent'];
                        $records[] = $this->formatAttendanceRecord($event);
                    }
                }
            }
        }
        
        return $records;
    }
    
    /**
     * Format a single attendance record from Hikvision event data
     * 
     * @param array $event Event data from API
     * @return array Formatted record
     */
    private function formatAttendanceRecord($event) {
        $time = $event['time'] ?? $event['timeLocal'] ?? '';
        $employeeNo = $event['employeeNoString'] ?? $event['employeeNo'] ?? '';
        $name = $event['name'] ?? $event['employeeName'] ?? '';
        
        return [
            'employee_id' => $employeeNo,
            'employee_name' => $name,
            'card_no' => $event['cardNo'] ?? '',
            'time' => $time,
            'date' => $this->extractDate($time),
            'type' => $event['eventType'] ?? $event['eventTypeDesc'] ?? '1',
            'event_description' => $event['eventTypeDesc'] ?? $event['eventType'] ?? '',
            'device_id' => $event['deviceName'] ?? $event['deviceID'] ?? '',
            'door_id' => $event['doorNo'] ?? '1',
            'verify_mode' => $event['verifyMode'] ?? '',
            'in_out' => $event['inOut'] ?? ''
        ];
    }
    
    /**
     * Get device status
     * 
     * @return array Device status information
     */
    public function getDeviceStatus() {
        try {
            $url = $this->baseUrl . "/System/status";
            $response = $this->makeRequest($url, 'GET');
            
            return $response ?: [];
        } catch (Exception $e) {
            error_log('Hikvision getDeviceStatus error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Whether a Hikvision ResponseStatus (or nested) indicates success.
     *
     * @param mixed $response
     */
    private function isHikvisionOk($response): bool {
        if ($response === false || $response === null) {
            return false;
        }
        if (!is_array($response)) {
            return true;
        }
        if (isset($response['statusCode'])) {
            return (int) $response['statusCode'] === 1;
        }
        if (isset($response['ResponseStatus']['statusCode'])) {
            return (int) $response['ResponseStatus']['statusCode'] === 1;
        }
        // Some firmware returns the created object without ResponseStatus
        if (isset($response['UserInfo']) || isset($response['CaptureFingerPrint']) || isset($response['CaptureFaceData'])) {
            return true;
        }
        if (isset($response['_raw_response'])) {
            return false;
        }
        return true;
    }

    /**
     * @param mixed $response
     */
    private function hikvisionErrorMessage($response, string $fallback = 'Device request failed'): string {
        if (!is_array($response)) {
            return $fallback;
        }
        $msg = $response['statusString']
            ?? $response['errorMsg']
            ?? ($response['ResponseStatus']['statusString'] ?? null)
            ?? ($response['ResponseStatus']['errorMsg'] ?? null);
        if (is_string($msg) && $msg !== '') {
            return $msg;
        }
        $sub = $response['subStatusCode'] ?? ($response['ResponseStatus']['subStatusCode'] ?? null);
        if (is_string($sub) && $sub !== '') {
            return $fallback . ' (' . $sub . ')';
        }
        return $fallback;
    }

    /**
     * Create a person on the terminal (employeeNo should match staff_id).
     *
     * @return array{ok: bool, message: string, response?: mixed}
     */
    public function createUser(string $employeeNo, string $name, string $userType = 'normal'): array {
        $employeeNo = trim($employeeNo);
        $name = trim($name);
        if ($employeeNo === '' || $name === '') {
            return ['ok' => false, 'message' => 'Employee No and name are required.'];
        }

        $payload = [
            'UserInfo' => [
                'employeeNo' => $employeeNo,
                'name' => $name,
                'userType' => $userType,
                'Valid' => [
                    'enable' => true,
                    'beginTime' => '2020-01-01T00:00:00',
                    'endTime' => '2037-12-31T23:59:59',
                ],
                'doorRight' => '1',
                'RightPlan' => [
                    ['doorNo' => 1, 'planTemplateNo' => '1'],
                ],
            ],
        ];

        try {
            $url = $this->baseUrl . '/AccessControl/UserInfo/Record?format=json';
            $response = $this->makeRequest($url, 'POST', json_encode($payload), 'application/json');
            if ($this->isHikvisionOk($response)) {
                return ['ok' => true, 'message' => 'User added on device.', 'response' => $response];
            }
            $sub = is_array($response)
                ? (string) ($response['subStatusCode'] ?? ($response['ResponseStatus']['subStatusCode'] ?? ''))
                : '';
            if (in_array($sub, ['deviceUserAlreadyExist', 'employeeNoAlreadyExist'], true)) {
                return ['ok' => true, 'message' => 'User already exists on device.', 'response' => $response];
            }
            return ['ok' => false, 'message' => $this->hikvisionErrorMessage($response, 'Failed to add user.'), 'response' => $response];
        } catch (Exception $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Search persons on the device (paginated until empty / cap).
     *
     * @return array{ok: bool, message: string, users: array<int, array{employee_no: string, name: string, user_type: string, finger_count: int, face_count: int}>}
     */
    public function searchUsers(int $maxResults = 100, string $employeeNo = ''): array {
        $employeeNo = trim($employeeNo);
        $pageSize = max(1, min(200, $maxResults));
        $users = [];
        $position = 0;

        try {
            for ($page = 0; $page < 50; $page++) {
                $search = [
                    'UserInfoSearchCond' => [
                        'searchID' => (string) (time() + $page),
                        'searchResultPosition' => $position,
                        'maxResults' => $pageSize,
                    ],
                ];
                if ($employeeNo !== '') {
                    $search['UserInfoSearchCond']['EmployeeNoList'] = [
                        ['employeeNo' => $employeeNo],
                    ];
                }

                $url = $this->baseUrl . '/AccessControl/UserInfo/Search?format=json';
                $response = $this->makeRequest($url, 'POST', json_encode($search), 'application/json');
                $list = $response['UserInfoSearch']['UserInfo'] ?? null;
                if ($list === null) {
                    break;
                }
                if (!isset($list[0])) {
                    $list = [$list];
                }
                $batch = 0;
                foreach ($list as $user) {
                    if (!is_array($user)) {
                        continue;
                    }
                    $batch++;
                    $users[] = [
                        'employee_no' => (string) ($user['employeeNo'] ?? ''),
                        'name' => (string) ($user['name'] ?? ''),
                        'user_type' => (string) ($user['userType'] ?? 'normal'),
                        'finger_count' => (int) ($user['numOfFP'] ?? $user['numOfFingerPrint'] ?? $user['fingerPrintNum'] ?? 0),
                        'face_count' => (int) ($user['numOfFace'] ?? $user['faceNum'] ?? 0),
                    ];
                }
                if ($employeeNo !== '' || $batch < $pageSize) {
                    break;
                }
                $position += $batch;
            }
            // UserInfo often omits numOfFP — enrich only for single-employee lookup.
            if ($employeeNo !== '' && $users !== []) {
                foreach ($users as &$u) {
                    if ((string) ($u['employee_no'] ?? '') !== $employeeNo) {
                        continue;
                    }
                    $detail = $this->getFingerPrintDetails($employeeNo);
                    if (!empty($detail['ok'])) {
                        $u['finger_count'] = (int) ($detail['count'] ?? $u['finger_count']);
                        $u['finger_slots'] = $detail['slots'] ?? [];
                    }
                }
                unset($u);
            }
            return ['ok' => true, 'message' => 'OK', 'users' => $users];
        } catch (Exception $e) {
            return ['ok' => false, 'message' => $e->getMessage(), 'users' => $users];
        }
    }

    /**
     * UserInfo/Search without FingerPrintUpload probes (fast — for metadata only).
     *
     * @return array{ok: bool, message: string, users: list<array<string,mixed>>}
     */
    public function searchUsersLite(int $maxResults = 5, string $employeeNo = ''): array {
        $employeeNo = trim($employeeNo);
        $pageSize = max(1, min(50, $maxResults));
        $users = [];
        try {
            $search = [
                'UserInfoSearchCond' => [
                    'searchID' => 'lite' . time(),
                    'searchResultPosition' => 0,
                    'maxResults' => $pageSize,
                ],
            ];
            if ($employeeNo !== '') {
                $search['UserInfoSearchCond']['EmployeeNoList'] = [
                    ['employeeNo' => $employeeNo],
                ];
            }
            $url = $this->baseUrl . '/AccessControl/UserInfo/Search?format=json';
            $response = $this->makeRequest($url, 'POST', json_encode($search), 'application/json');
            $list = $response['UserInfoSearch']['UserInfo'] ?? null;
            if ($list === null) {
                return ['ok' => true, 'message' => 'OK', 'users' => []];
            }
            if (!isset($list[0])) {
                $list = [$list];
            }
            foreach ($list as $user) {
                if (!is_array($user)) {
                    continue;
                }
                $users[] = [
                    'employee_no' => (string) ($user['employeeNo'] ?? ''),
                    'name' => (string) ($user['name'] ?? ''),
                    'user_type' => (string) ($user['userType'] ?? 'normal'),
                    'finger_count' => (int) ($user['numOfFP'] ?? $user['numOfFingerPrint'] ?? $user['fingerPrintNum'] ?? 0),
                    'face_count' => (int) ($user['numOfFace'] ?? $user['faceNum'] ?? 0),
                ];
            }
            return ['ok' => true, 'message' => 'OK', 'users' => $users];
        } catch (Exception $e) {
            return ['ok' => false, 'message' => $e->getMessage(), 'users' => $users];
        }
    }

    /**
     * Live fingerprint slots for one employee (Count + optional short Upload probe).
     *
     * @return array{ok: bool, message: string, count: int, slots: list<int>}
     */
    public function getFingerPrintDetails(string $employeeNo): array {
        $employeeNo = trim($employeeNo);
        if ($employeeNo === '') {
            return ['ok' => false, 'message' => 'Employee No required', 'count' => 0, 'slots' => []];
        }

        $slots = [];
        $countFromApi = null;

        $countRes = $this->makeRequestDetailed(
            $this->baseUrl . '/AccessControl/FingerPrint/Count?format=json&employeeNo=' . rawurlencode($employeeNo),
            'GET',
            null,
            'application/json',
            8
        );
        if ($countRes['ok'] && is_array($countRes['decoded'])) {
            $list = $countRes['decoded']['FingerPrintCountList'] ?? null;
            if (is_array($list)) {
                if (!isset($list[0]) && isset($list['numberOfFP'])) {
                    $list = [$list];
                }
                $total = 0;
                $ids = [];
                foreach ($list as $row) {
                    if (!is_array($row)) {
                        continue;
                    }
                    $total += (int) ($row['numberOfFP'] ?? 0);
                    $fpIds = $row['fingerPrintIDs'] ?? null;
                    if (is_array($fpIds)) {
                        foreach ($fpIds as $id) {
                            $ids[] = (int) $id;
                        }
                    }
                }
                $countFromApi = $total;
                foreach ($ids as $id) {
                    if ($id >= 1 && $id <= 10 && !in_array($id, $slots, true)) {
                        $slots[] = $id;
                    }
                }
            }
        }

        // Probe slots 1 and 2 (UI supports Finger 01 / 02). Skip if Count already gave IDs.
        if ($slots === []) {
            foreach ([1, 2] as $fid) {
                $body = json_encode([
                    'FingerPrintCond' => [
                        'searchID' => 'fp' . $fid . time(),
                        'employeeNo' => $employeeNo,
                        'cardReaderNo' => 1,
                        'fingerPrintID' => $fid,
                    ],
                ]);
                $res = $this->makeRequestDetailed(
                    $this->baseUrl . '/AccessControl/FingerPrintUpload?format=json',
                    'POST',
                    $body,
                    'application/json',
                    6
                );
                if (!$res['ok'] || !is_array($res['decoded'])) {
                    continue;
                }
                $info = $res['decoded']['FingerPrintInfo'] ?? $res['decoded'];
                $status = strtoupper((string) (is_array($info) ? ($info['status'] ?? '') : ''));
                if ($status === 'OK' || $status === 'SUCCESS') {
                    $slots[] = $fid;
                    continue;
                }
                // Some firmwares return list without status OK
                $list = is_array($info) ? ($info['FingerPrintList'] ?? null) : null;
                if (is_array($list) && $list !== []) {
                    $slots[] = $fid;
                }
            }
        }

        sort($slots);
        $count = $countFromApi !== null ? max($countFromApi, count($slots)) : count($slots);

        return [
            'ok' => true,
            'message' => 'OK',
            'count' => $count,
            'slots' => array_values($slots),
        ];
    }

    /**
     * Read fingerprint template bytes from this device (ISAPI FingerPrintUpload).
     * Used to copy credentials from MAIN → readers without re-enrolling.
     *
     * @return array{ok: bool, message: string, fingerData?: string, fingerPrintID?: int}
     */
    public function extractFingerPrintTemplate(string $employeeNo, int $fingerPrintID): array {
        $employeeNo = trim($employeeNo);
        $fingerPrintID = (int) $fingerPrintID;
        if ($employeeNo === '' || $fingerPrintID < 1) {
            return ['ok' => false, 'message' => 'Employee No and finger ID are required.'];
        }

        $bodies = [
            json_encode([
                'FingerPrintCond' => [
                    'searchID' => 'ext' . $fingerPrintID . time(),
                    'employeeNo' => $employeeNo,
                    'cardReaderNo' => 1,
                    'fingerPrintID' => $fingerPrintID,
                ],
            ]),
            json_encode([
                'FingerPrintCond' => [
                    'searchID' => 'extb' . $fingerPrintID . time(),
                    'employeeNo' => $employeeNo,
                    'enableCardReader' => [1],
                    'fingerPrintID' => $fingerPrintID,
                ],
            ]),
        ];

        $lastMsg = 'No fingerData returned.';
        foreach ($bodies as $body) {
            $res = $this->makeRequestDetailed(
                $this->baseUrl . '/AccessControl/FingerPrintUpload?format=json',
                'POST',
                $body,
                'application/json',
                30
            );
            if (!$res['ok']) {
                $lastMsg = $this->hikvisionErrorMessage(
                    is_array($res['decoded']) ? $res['decoded'] : null,
                    'FingerPrintUpload HTTP ' . $res['http_code'] . ($res['error'] !== '' ? ' ' . $res['error'] : '')
                );
                continue;
            }
            $data = $this->findFingerDataInResponse($res['decoded'], $res['body']);
            if ($data !== null && $data !== '') {
                return [
                    'ok' => true,
                    'message' => 'OK',
                    'fingerData' => $data,
                    'fingerPrintID' => $fingerPrintID,
                ];
            }
            $lastMsg = 'FingerPrintUpload OK but fingerData empty (firmware may block template export).';
        }

        // XML fallback
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<FingerPrintCond version="2.0" xmlns="http://www.isapi.org/ver20/XMLSchema">'
            . '<searchID>extx' . $fingerPrintID . time() . '</searchID>'
            . '<employeeNo>' . htmlspecialchars($employeeNo, ENT_XML1) . '</employeeNo>'
            . '<cardReaderNo>1</cardReaderNo>'
            . '<fingerPrintID>' . $fingerPrintID . '</fingerPrintID>'
            . '</FingerPrintCond>';
        $res = $this->makeRequestDetailed(
            $this->baseUrl . '/AccessControl/FingerPrintUpload',
            'POST',
            $xml,
            'application/xml; charset="UTF-8"',
            30
        );
        if ($res['ok']) {
            $data = $this->findFingerDataInResponse($res['decoded'], $res['body']);
            if ($data !== null && $data !== '') {
                return [
                    'ok' => true,
                    'message' => 'OK',
                    'fingerData' => $data,
                    'fingerPrintID' => $fingerPrintID,
                ];
            }
        } else {
            $lastMsg = $this->hikvisionErrorMessage(
                is_array($res['decoded']) ? $res['decoded'] : null,
                'FingerPrintUpload XML HTTP ' . $res['http_code']
            );
        }

        return ['ok' => false, 'message' => $lastMsg, 'fingerPrintID' => $fingerPrintID];
    }

    /**
     * Write fingerprint template to this device (ISAPI FingerPrintDownload).
     *
     * @return array{ok: bool, message: string, response?: mixed}
     */
    public function pushFingerPrintTemplate(string $employeeNo, int $fingerPrintID, string $fingerData): array {
        $employeeNo = trim($employeeNo);
        $fingerData = trim($fingerData);
        if ($employeeNo === '' || $fingerData === '' || $fingerPrintID < 1) {
            return ['ok' => false, 'message' => 'Employee No, finger ID and fingerData are required.'];
        }
        $errors = [];
        $res = $this->downloadFingerPrintToUser($employeeNo, $fingerPrintID, $fingerData, $errors);
        if (!empty($res['ok'])) {
            return ['ok' => true, 'message' => 'Fingerprint pushed to ' . $this->host, 'response' => $res['response'] ?? null];
        }
        return [
            'ok' => false,
            'message' => $res['message'] ?? ('Push failed: ' . implode('; ', $errors)),
            'response' => $res['response'] ?? null,
        ];
    }

    /**
     * @param mixed $decoded
     */
    private function findFingerDataInResponse($decoded, string $body): ?string {
        if (is_array($decoded)) {
            $candidates = [
                $decoded['FingerPrintInfo'] ?? null,
                $decoded['FingerPrintCfg'] ?? null,
                $decoded,
            ];
            foreach ($candidates as $block) {
                if (!is_array($block)) {
                    continue;
                }
                foreach (['fingerData', 'FingerData', 'printData', 'fingerPrintData'] as $k) {
                    if (!empty($block[$k]) && is_string($block[$k]) && strlen($block[$k]) > 20) {
                        return $block[$k];
                    }
                }
                $list = $block['FingerPrintList'] ?? $block['FingerPrint'] ?? null;
                if (is_array($list)) {
                    if (!isset($list[0]) && isset($list['fingerData'])) {
                        $list = [$list];
                    }
                    foreach ($list as $row) {
                        if (!is_array($row)) {
                            continue;
                        }
                        foreach (['fingerData', 'FingerData', 'printData'] as $k) {
                            if (!empty($row[$k]) && is_string($row[$k]) && strlen($row[$k]) > 20) {
                                return $row[$k];
                            }
                        }
                    }
                }
            }
        }
        if (preg_match('/<(?:fingerData|FingerData)>([^<]{20,})<\/(?:fingerData|FingerData)>/i', $body, $m)) {
            return html_entity_decode($m[1], ENT_QUOTES | ENT_XML1, 'UTF-8');
        }
        if (preg_match('/"fingerData"\s*:\s*"([^"]{20,})"/', $body, $m)) {
            return stripcslashes($m[1]);
        }
        return null;
    }

    /**
     * Update person name / type on the terminal.
     *
     * @return array{ok: bool, message: string, response?: mixed}
     */
    public function modifyUser(string $employeeNo, string $name, string $userType = 'normal'): array {
        $employeeNo = trim($employeeNo);
        $name = trim($name);
        if ($employeeNo === '' || $name === '') {
            return ['ok' => false, 'message' => 'Employee No and name are required.'];
        }

        $payload = [
            'UserInfo' => [
                'employeeNo' => $employeeNo,
                'name' => $name,
                'userType' => $userType,
                'Valid' => [
                    'enable' => true,
                    'beginTime' => '2020-01-01T00:00:00',
                    'endTime' => '2037-12-31T23:59:59',
                ],
            ],
        ];

        try {
            $url = $this->baseUrl . '/AccessControl/UserInfo/Modify?format=json';
            $response = $this->makeRequest($url, 'PUT', json_encode($payload), 'application/json');
            if ($this->isHikvisionOk($response)) {
                return ['ok' => true, 'message' => 'User updated on device.', 'response' => $response];
            }
            return ['ok' => false, 'message' => $this->hikvisionErrorMessage($response, 'Failed to update user.'), 'response' => $response];
        } catch (Exception $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Delete fingerprint slot(s) for a person on the device.
     * fingerNo 1–10 deletes that slot; 0 deletes all fingerprints for the employee.
     *
     * @return array{ok: bool, message: string, response?: mixed}
     */
    public function deleteFingerPrint(string $employeeNo, int $fingerNo = 0): array {
        $employeeNo = trim($employeeNo);
        if ($employeeNo === '') {
            return ['ok' => false, 'message' => 'Employee No is required.'];
        }

        $detail = [
            'employeeNo' => $employeeNo,
            'enableCardReader' => [1],
        ];
        if ($fingerNo > 0) {
            $detail['fingerPrintID'] = [max(1, min(10, $fingerNo))];
        }

        $payloads = [
            [
                'FingerPrintDelete' => [
                    'mode' => 'byEmployeeNo',
                    'EmployeeNoDetail' => $detail,
                ],
            ],
            // Legacy shape used by some firmwares
            [
                'FingerPrintDelete' => [
                    'mode' => 'byEmployeeNo',
                    'EmployeeNoList' => [['employeeNo' => $employeeNo]],
                    'fingerPrintList' => [
                        array_filter([
                            'employeeNo' => $employeeNo,
                            'enableCardReader' => [1],
                            'fingerPrintID' => $fingerNo > 0 ? max(1, min(10, $fingerNo)) : null,
                        ], static fn ($v) => $v !== null),
                    ],
                ],
            ],
        ];

        $lastMsg = 'Failed to delete fingerprint.';
        $lastResp = null;
        try {
            foreach ($payloads as $payload) {
                foreach (['json', 'xml'] as $fmt) {
                    if ($fmt === 'json') {
                        $url = $this->baseUrl . '/AccessControl/FingerPrint/Delete?format=json';
                        $body = json_encode($payload);
                        $ct = 'application/json';
                    } else {
                        $url = $this->baseUrl . '/AccessControl/FingerPrint/Delete';
                        $fpIdXml = $fingerNo > 0
                            ? '<fingerPrintID>' . max(1, min(10, $fingerNo)) . '</fingerPrintID>'
                            : '';
                        $body = '<?xml version="1.0" encoding="UTF-8"?>'
                            . '<FingerPrintDelete version="2.0" xmlns="http://www.isapi.org/ver20/XMLSchema">'
                            . '<mode>byEmployeeNo</mode>'
                            . '<EmployeeNoDetail>'
                            . '<employeeNo>' . htmlspecialchars($employeeNo, ENT_XML1) . '</employeeNo>'
                            . '<enableCardReader>1</enableCardReader>'
                            . $fpIdXml
                            . '</EmployeeNoDetail>'
                            . '</FingerPrintDelete>';
                        $ct = 'application/xml; charset="UTF-8"';
                    }
                    $res = $this->makeRequestDetailed($url, 'PUT', $body, $ct, 30);
                    $lastResp = $res['decoded'] ?? $res['body'];
                    if ($res['ok'] && $this->isHikvisionOk(is_array($res['decoded']) ? $res['decoded'] : ['statusCode' => 1])) {
                        $slot = $fingerNo > 0 ? ('Finger 0' . $fingerNo) : 'all fingerprints';
                        return ['ok' => true, 'message' => $slot . ' removed for ' . $employeeNo . '.', 'response' => $lastResp];
                    }
                    $lastMsg = $this->hikvisionErrorMessage(
                        is_array($res['decoded']) ? $res['decoded'] : null,
                        'HTTP ' . $res['http_code']
                    );
                    // Only try first JSON payload variants then XML once
                    if ($fmt === 'xml') {
                        break 2;
                    }
                }
            }
            // Not found is OK when clearing before re-enroll
            if (stripos($lastMsg, 'not') !== false || stripos($lastMsg, 'noFP') !== false) {
                return ['ok' => true, 'message' => 'No previous fingerprint (or already cleared).', 'response' => $lastResp];
            }
            return ['ok' => false, 'message' => $lastMsg, 'response' => $lastResp];
        } catch (Exception $e) {
            return ['ok' => true, 'message' => 'Fingerprint delete skipped: ' . $e->getMessage()];
        }
    }

    /**
     * Delete face library record for employee (FPID = employeeNo).
     *
     * @return array{ok: bool, message: string, response?: mixed}
     */
    public function deleteFace(string $employeeNo): array {
        $employeeNo = trim($employeeNo);
        if ($employeeNo === '') {
            return ['ok' => false, 'message' => 'Employee No is required.'];
        }

        $payload = [
            'FPID' => [
                ['value' => $employeeNo],
            ],
        ];

        try {
            $url = $this->baseUrl . '/Intelligent/FDLib/FDSearch/Delete?format=json&faceLibType=blackFD&FDID=1';
            $response = $this->makeRequest($url, 'PUT', json_encode($payload), 'application/json');
            if ($this->isHikvisionOk($response)) {
                return ['ok' => true, 'message' => 'Old face removed.', 'response' => $response];
            }
            return ['ok' => true, 'message' => 'No previous face (or already cleared).', 'response' => $response];
        } catch (Exception $e) {
            return ['ok' => true, 'message' => 'Face delete skipped: ' . $e->getMessage()];
        }
    }

    /**
     * Delete a person (UserInfo) from this terminal by Employee No.
     * Also clears face / fingerprints linked to that employee when possible.
     *
     * @return array{ok: bool, message: string, response?: mixed}
     */
    public function deleteUser(string $employeeNo): array {
        $employeeNo = trim($employeeNo);
        if ($employeeNo === '') {
            return ['ok' => false, 'message' => 'Employee No is required.'];
        }

        // Best-effort cleanup of biometrics first (ignore failures)
        try {
            $this->deleteFace($employeeNo);
        } catch (Throwable $e) {
            // ignore
        }
        try {
            $this->deleteFingerPrint($employeeNo, 0);
        } catch (Throwable $e) {
            // ignore
        }

        $payload = [
            'UserInfoDelCond' => [
                'EmployeeNoList' => [
                    ['employeeNo' => $employeeNo],
                ],
            ],
        ];
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $urls = [
            $this->baseUrl . '/AccessControl/UserInfo/Delete?format=json',
            $this->baseUrl . '/AccessControl/UserInfoDetail/Delete?format=json',
        ];
        $lastMsg = 'Delete failed';
        $lastResponse = null;

        try {
            foreach ($urls as $url) {
                foreach (['PUT', 'POST'] as $method) {
                    $response = $this->makeRequest($url, $method, $body, 'application/json');
                    $lastResponse = $response;
                    if ($this->isHikvisionOk($response)) {
                        return [
                            'ok' => true,
                            'message' => 'Person deleted from device.',
                            'response' => $response,
                        ];
                    }
                    $sub = is_array($response)
                        ? (string) ($response['subStatusCode'] ?? ($response['ResponseStatus']['subStatusCode'] ?? ''))
                        : '';
                    // Already gone is success for our purpose
                    if (in_array($sub, [
                        'employeeNoNotExist',
                        'deviceUserNotExist',
                        'notFound',
                        'InvalidOperation',
                    ], true)) {
                        return [
                            'ok' => true,
                            'message' => 'Person not on device (already removed).',
                            'response' => $response,
                        ];
                    }
                    $lastMsg = $this->hikvisionErrorMessage($response, 'Delete failed');
                }
            }

            // Verify: if search finds nobody, treat as deleted
            $check = $this->searchUsersLite(5, $employeeNo);
            if (!empty($check['ok']) && empty($check['users'])) {
                return [
                    'ok' => true,
                    'message' => 'Person not on device (already removed).',
                    'response' => $lastResponse,
                ];
            }

            return ['ok' => false, 'message' => $lastMsg, 'response' => $lastResponse];
        } catch (Exception $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Replace fingerprint by capturing a new print and writing the same finger slot.
     * Does NOT delete the old fingerprint first (overwrite via FingerPrintDownload).
     *
     * @return array{ok: bool, message: string, response?: mixed}
     */
    public function changeFingerPrint(string $employeeNo, int $fingerNo = 1): array {
        return $this->enrollFingerPrint($employeeNo, $fingerNo);
    }

    /**
     * Replace face: prefer on-device enrollment without deleting first.
     *
     * @return array{ok: bool, message: string, response?: mixed}
     */
    public function changeFace(string $employeeNo): array {
        return $this->enrollFaceOnDevice($employeeNo);
    }

    /**
     * Capture fingerprint on the terminal, then assign it to employeeNo.
     * Tries JSON + XML payloads (DS-K1T often returns badXmlContent for JSON-only CaptureFingerPrint).
     *
     * @return array{ok: bool, message: string, response?: mixed}
     */
    public function enrollFingerPrint(string $employeeNo, int $fingerNo = 1): array {
        $employeeNo = trim($employeeNo);
        if ($employeeNo === '') {
            return ['ok' => false, 'message' => 'Employee No is required.'];
        }
        if (!$this->hasPasswordConfigured()) {
            return [
                'ok' => false,
                'message' => 'Finger capture blocked: STUDENT_HIKVISION_PASS is empty on this server. '
                    . 'Set the same password as the MAIN terminal web login, then retry.',
            ];
        }
        $fingerNo = max(1, min(10, $fingerNo));
        $slotLabel = 'Finger 0' . $fingerNo;

        // One login check only — never spam CaptureFingerPrint while admin is locked / wrong password
        $login = $this->assertDigestLogin();
        if (empty($login['ok'])) {
            return [
                'ok' => false,
                'message' => $slotLabel . ' blocked — device login failed before capture. ' . ($login['message'] ?? ''),
            ];
        }

        $errors = [];
        try {
            $captured = $this->captureFingerPrintData($fingerNo, $errors);
            $printData = $captured['fingerData'] ?? null;
            $quality = $captured['quality'] ?? null;

            if (!is_string($printData) || $printData === '') {
                $detail = $errors !== [] ? implode('; ', array_slice(array_unique($errors), 0, 3)) : 'no finger data';
                $authHint = '';
                if (stripos($detail, '401') !== false || stripos($detail, 'locked') !== false || stripos($detail, 'Unauthorized') !== false) {
                    $authHint = ' Stop retrying. Confirm browser login at http://' . $this->host
                        . ' with user "' . $this->username . '", then re-save that exact password on the Device page.';
                    return [
                        'ok' => false,
                        'message' => $slotLabel . ' capture failed (auth). ' . $detail . $authHint,
                        'response' => $captured['raw'] ?? null,
                    ];
                }
                // Fallback: open terminal enrollment UI only when auth is OK
                $ui = $this->enrollFingerPrintViaTerminalUi($employeeNo, $fingerNo);
                if (!empty($ui['ok'])) {
                    return $ui;
                }
                return [
                    'ok' => false,
                    'message' => $slotLabel . ' capture failed. Ask the student to place a finger on the scanner, then try again. (' . $detail . ')',
                    'response' => $captured['raw'] ?? null,
                ];
            }

            $saved = $this->downloadFingerPrintToUser($employeeNo, $fingerNo, $printData, $errors);
            if (!empty($saved['ok'])) {
                $q = $quality !== null ? ' (quality: ' . $quality . ')' : '';
                return [
                    'ok' => true,
                    'message' => $slotLabel . ' enrolled for ' . $employeeNo . $q . '.',
                    'response' => $saved['response'] ?? null,
                ];
            }

            return [
                'ok' => false,
                'message' => $slotLabel . ' captured but save failed: '
                    . ($saved['message'] ?? ($errors[0] ?? 'unknown')),
                'response' => $saved['response'] ?? null,
            ];
        } catch (Exception $e) {
            return ['ok' => false, 'message' => $slotLabel . ': ' . $e->getMessage()];
        }
    }

    /**
     * @param list<string> $errors
     * @return array{fingerData:?string, quality:mixed, raw:mixed}
     */
    private function captureFingerPrintData(int $fingerNo, array &$errors): array {
        // Auth already verified by enrollFingerPrint(); do not warm again (extra 401s re-lock admin)

        $payloads = [
            [
                'path' => '/AccessControl/CaptureFingerPrint?format=json',
                'body' => json_encode(['CaptureFingerPrintCond' => ['fingerNo' => $fingerNo]]),
                'ct' => 'application/json',
            ],
            [
                'path' => '/AccessControl/CaptureFingerPrint',
                'body' => '<?xml version="1.0" encoding="UTF-8"?>'
                    . '<CaptureFingerPrintCond version="2.0" xmlns="http://www.isapi.org/ver20/XMLSchema">'
                    . '<fingerNo>' . $fingerNo . '</fingerNo>'
                    . '</CaptureFingerPrintCond>',
                'ct' => 'application/xml; charset=UTF-8',
            ],
        ];

        $attemptNo = 0;
        foreach ($payloads as $att) {
            // Prefer POST only — PUT retries on 401 were re-locking the admin account
            $attemptNo++;
            $url = $this->baseUrl . $att['path'];
            $res = $this->makeRequestDetailed($url, 'POST', $att['body'], $att['ct'], 70);
            $decoded = $res['decoded'];
            if ($res['http_code'] === 401) {
                $errors[] = 'Capture#' . $attemptNo . ' HTTP 401 '
                    . ($res['error'] !== '' ? $res['error'] : 'Unauthorized');
                // Stop immediately — more attempts extend the lockout
                return ['fingerData' => null, 'quality' => null, 'raw' => $decoded ?? $res['body']];
            }
            if ($res['http_code'] >= 400) {
                $errors[] = 'Capture#' . $attemptNo . ' HTTP ' . $res['http_code'] . ' '
                    . $this->hikvisionErrorMessage(is_array($decoded) ? $decoded : null, substr($res['body'], 0, 120));
                continue;
            }
            $fpBlock = is_array($decoded) ? ($decoded['CaptureFingerPrint'] ?? $decoded) : null;
            $printData = is_array($fpBlock)
                ? ($fpBlock['fingerData'] ?? $fpBlock['fingerprintData'] ?? null)
                : null;
            if ((!is_string($printData) || $printData === '') && is_string($res['body']) && $res['body'] !== '') {
                if (preg_match('/<(?:fingerData|fingerprintData)>([^<]+)</i', $res['body'], $m)) {
                    $printData = html_entity_decode($m[1], ENT_QUOTES | ENT_XML1, 'UTF-8');
                }
            }
            if (is_string($printData) && $printData !== '') {
                return [
                    'fingerData' => $printData,
                    'quality' => is_array($fpBlock) ? ($fpBlock['fingerPrintQuality'] ?? $fpBlock['fingerNo'] ?? null) : null,
                    'raw' => $decoded ?? $res['body'],
                ];
            }
            for ($p = 0; $p < 20; $p++) {
                usleep(500000);
                $prog = $this->makeRequestDetailed(
                    $this->baseUrl . '/AccessControl/CaptureFingerPrint/Progress',
                    'GET',
                    null,
                    'application/xml',
                    15
                );
                if ($prog['http_code'] === 401) {
                    $errors[] = 'Capture progress HTTP 401 — ' . ($prog['error'] !== '' ? $prog['error'] : 'Unauthorized');
                    return ['fingerData' => null, 'quality' => null, 'raw' => $prog['decoded'] ?? $prog['body']];
                }
                if ($prog['http_code'] >= 400) {
                    break;
                }
                $block = is_array($prog['decoded'])
                    ? ($prog['decoded']['CaptureFingerPrint'] ?? $prog['decoded'])
                    : null;
                $data = is_array($block) ? ($block['fingerData'] ?? $block['fingerprintData'] ?? null) : null;
                if ((!is_string($data) || $data === '') && is_string($prog['body'])) {
                    if (preg_match('/<(?:fingerData|fingerprintData)>([^<]+)</i', $prog['body'], $m2)) {
                        $data = html_entity_decode($m2[1], ENT_QUOTES | ENT_XML1, 'UTF-8');
                    }
                }
                if (is_string($data) && $data !== '') {
                    return [
                        'fingerData' => $data,
                        'quality' => is_array($block) ? ($block['fingerPrintQuality'] ?? null) : null,
                        'raw' => $prog['decoded'] ?? $prog['body'],
                    ];
                }
            }
            $errors[] = 'Capture#' . $attemptNo . ' POST — no fingerData (place finger on scanner)';
        }

        return ['fingerData' => null, 'quality' => null, 'raw' => null];
    }

    /**
     * @param list<string> $errors
     * @return array{ok: bool, message?: string, response?: mixed}
     */
    private function downloadFingerPrintToUser(string $employeeNo, int $fingerNo, string $printData, array &$errors): array {
        $jsonBodies = [
            [
                'FingerPrintCfg' => [
                    'employeeNo' => $employeeNo,
                    'enableCardReader' => [1],
                    'fingerPrintID' => $fingerNo,
                    'fingerType' => 'normalFP',
                    'fingerData' => $printData,
                ],
            ],
            [
                'FingerPrintCfg' => [
                    'employeeNo' => $employeeNo,
                    'cardReaderNo' => 1,
                    'fingerPrintID' => $fingerNo,
                    'fingerType' => 'normalFP',
                    'fingerData' => $printData,
                ],
            ],
        ];

        foreach ($jsonBodies as $idx => $payload) {
            $res = $this->makeRequestDetailed(
                $this->baseUrl . '/AccessControl/FingerPrintDownload?format=json',
                'POST',
                json_encode($payload),
                'application/json',
                40
            );
            if ($res['ok'] && $this->isHikvisionOk(is_array($res['decoded']) ? $res['decoded'] : ['statusCode' => 1])) {
                return ['ok' => true, 'response' => $res['decoded']];
            }
            $errors[] = 'Download JSON#' . ($idx + 1) . ' HTTP ' . $res['http_code'] . ' '
                . $this->hikvisionErrorMessage(is_array($res['decoded']) ? $res['decoded'] : null, '');
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<FingerPrintCfg version="2.0" xmlns="http://www.isapi.org/ver20/XMLSchema">'
            . '<employeeNo>' . htmlspecialchars($employeeNo, ENT_XML1) . '</employeeNo>'
            . '<enableCardReader>1</enableCardReader>'
            . '<fingerPrintID>' . $fingerNo . '</fingerPrintID>'
            . '<fingerType>normalFP</fingerType>'
            . '<fingerData>' . htmlspecialchars($printData, ENT_XML1) . '</fingerData>'
            . '</FingerPrintCfg>';
        $res = $this->makeRequestDetailed(
            $this->baseUrl . '/AccessControl/FingerPrintDownload',
            'POST',
            $xml,
            'application/xml; charset="UTF-8"',
            40
        );
        if ($res['ok'] && $this->isHikvisionOk(is_array($res['decoded']) ? $res['decoded'] : ['statusCode' => 1])) {
            return ['ok' => true, 'response' => $res['decoded']];
        }
        $msg = $this->hikvisionErrorMessage(
            is_array($res['decoded']) ? $res['decoded'] : null,
            'HTTP ' . $res['http_code']
        );
        $errors[] = 'Download XML: ' . $msg;
        return ['ok' => false, 'message' => $msg, 'response' => $res['decoded'] ?? $res['body']];
    }

    /**
     * Ask the terminal to open person credential enrollment, then poll finger_count.
     *
     * @return array{ok: bool, message: string, response?: mixed}
     */
    private function enrollFingerPrintViaTerminalUi(string $employeeNo, int $fingerNo): array {
        $baseline = $this->searchUsers(5, $employeeNo);
        $baseFp = 0;
        foreach ($baseline['users'] ?? [] as $u) {
            if ((string) ($u['employee_no'] ?? '') === $employeeNo) {
                $baseFp = (int) ($u['finger_count'] ?? 0);
                break;
            }
        }

        $setupAttempts = [
            json_encode([
                'UserInfoDetail' => [
                    'mode' => 'employeeNo',
                    'EmployeeNoList' => [['employeeNo' => $employeeNo]],
                ],
            ]),
            json_encode([
                'UserInfoDetail' => [
                    'mode' => 'byEmployeeNo',
                    'EmployeeNoList' => [['employeeNo' => $employeeNo]],
                ],
            ]),
        ];
        $setupOk = false;
        foreach ($setupAttempts as $payload) {
            $setup = $this->makeRequestDetailed(
                $this->baseUrl . '/AccessControl/UserInfoDetail/Setup?format=json',
                'PUT',
                $payload,
                'application/json',
                20
            );
            if ($setup['ok'] && $this->isHikvisionOk($setup['decoded'] ?? ['statusCode' => 1])) {
                $setupOk = true;
                break;
            }
        }
        if (!$setupOk) {
            return ['ok' => false, 'message' => 'Could not start terminal fingerprint enrollment UI.'];
        }

        $deadline = time() + 90;
        while (time() < $deadline) {
            sleep(3);
            $search = $this->searchUsers(5, $employeeNo);
            foreach ($search['users'] ?? [] as $u) {
                if ((string) ($u['employee_no'] ?? '') !== $employeeNo) {
                    continue;
                }
                $fc = (int) ($u['finger_count'] ?? 0);
                if ($fc > $baseFp) {
                    return [
                        'ok' => true,
                        'message' => 'Finger 0' . $fingerNo . ' enrolled on terminal for ' . $employeeNo
                            . ' (device fingers: ' . $fc . ').',
                        'response' => $u,
                    ];
                }
            }
        }

        return [
            'ok' => false,
            'message' => 'Terminal enrollment started for ' . $employeeNo
                . ', but no new fingerprint was detected within 90s. Complete capture on the device screen.',
        ];
    }

    /**
     * Low-level request that returns status instead of throwing on HTTP 4xx.
     *
     * @return array{ok: bool, http_code: int, content_type: string, body: string, decoded: mixed, error: string}
     */
    private function makeRequestDetailed($url, $method = 'GET', $data = null, $contentType = 'application/json', $timeoutOverride = null): array {
        $timeout = $timeoutOverride !== null ? (int) $timeoutOverride : (int) $this->timeout;
        try {
            $ch = $this->createDigestCurl($url, $timeout);
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'http_code' => 0,
                'content_type' => '',
                'body' => '',
                'decoded' => null,
                'error' => $e->getMessage(),
            ];
        }

        $headers = ['Expect:'];
        $method = strtoupper((string) $method);
        if ($data !== null && $data !== '' && $contentType) {
            $headers[] = 'Content-Type: ' . $contentType;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data ?? '');
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data ?? '');
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        } else {
            curl_setopt($ch, CURLOPT_HTTPGET, true);
        }

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = (string) curl_error($ch);
        $respContentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        $body = is_string($response) ? $response : '';
        $decoded = null;
        if ($body !== '') {
            $isBinary = strncmp($body, "\xFF\xD8\xFF", 3) === 0
                || stripos($respContentType, 'image/') === 0
                || stripos($respContentType, 'octet-stream') !== false;
            if (!$isBinary) {
                $json = json_decode($body, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $decoded = $json;
                } else {
                    $xml = $this->parseXML($body);
                    $decoded = $xml !== [] ? $xml : null;
                }
            }
        }

        return [
            'ok' => $error === '' && $httpCode > 0 && $httpCode < 400,
            'http_code' => $httpCode,
            'content_type' => $respContentType,
            'body' => $body,
            'decoded' => $decoded,
            'error' => $error !== '' ? $error : ($httpCode === 401 ? $this->formatUnauthorizedMessage($url, $body) : ''),
        ];
    }

    /**
     * Extract JPEG bytes from CaptureFaceData response (JSON base64, raw JPEG, or multipart).
     */
    private function extractFaceJpeg(string $body, string $contentType, $decoded): ?string {
        if (is_array($decoded)) {
            $faceBlock = $decoded['CaptureFaceData'] ?? $decoded;
            if (is_array($faceBlock)) {
                foreach (['faceData', 'FaceData', 'pictureData', 'facePicture', 'imgData'] as $key) {
                    if (!empty($faceBlock[$key]) && is_string($faceBlock[$key])) {
                        $bin = base64_decode($faceBlock[$key], true);
                        if ($bin !== false && strlen($bin) > 100) {
                            return $bin;
                        }
                    }
                }
            }
        }

        if (strncmp($body, "\xFF\xD8\xFF", 3) === 0) {
            return $body;
        }

        if (stripos($contentType, 'multipart') !== false || strpos($body, '--') === 0) {
            if (preg_match('/\r?\n\r?\n(\xFF\xD8\xFF.*?\xFF\xD9)/s', $body, $m)) {
                return $m[1];
            }
            $pos = strpos($body, "\xFF\xD8\xFF");
            $end = strrpos($body, "\xFF\xD9");
            if ($pos !== false && $end !== false && $end > $pos) {
                return substr($body, $pos, $end - $pos + 2);
            }
        }

        $trimmed = trim($body);
        if ($trimmed !== '' && preg_match('/^[A-Za-z0-9+\/=\r\n]+$/', $trimmed)) {
            $bin = base64_decode($trimmed, true);
            if ($bin !== false && strncmp($bin, "\xFF\xD8\xFF", 3) === 0) {
                return $bin;
            }
        }

        return null;
    }

    /**
     * Resolve face library FDID (default "1").
     */
    private function resolveFaceLibraryId(): string {
        $res = $this->makeRequestDetailed($this->baseUrl . '/Intelligent/FDLib?format=json', 'GET', null, 'application/json', 15);
        if (!$res['ok'] || !is_array($res['decoded'])) {
            return '1';
        }
        $list = $res['decoded']['FDLib'] ?? $res['decoded']['FaceLib'] ?? null;
        if (is_array($list)) {
            if (!isset($list[0])) {
                $list = [$list];
            }
            foreach ($list as $lib) {
                if (!is_array($lib)) {
                    continue;
                }
                $id = (string) ($lib['FDID'] ?? $lib['id'] ?? '');
                if ($id !== '') {
                    return $id;
                }
            }
        }
        return '1';
    }

    /**
     * Read enrolled face photo from the terminal (FDSearch → faceURL → JPEG).
     *
     * @return array{ok: bool, message: string, jpeg?: string, face_url?: string, fpid?: string}
     */
    public function getFacePhoto(string $employeeNo): array {
        $employeeNo = trim($employeeNo);
        if ($employeeNo === '') {
            return ['ok' => false, 'message' => 'Employee No is required.'];
        }

        $fdids = ['1', '2', '0'];
        $faceUrl = '';
        $fpid = $employeeNo;
        $lastMsg = 'No face record on machine.';

        foreach ($fdids as $fdid) {
            $body = json_encode([
                'searchResultPosition' => 0,
                'maxResults' => 5,
                'faceLibType' => 'blackFD',
                'FDID' => $fdid,
                'FPID' => $employeeNo,
            ], JSON_UNESCAPED_SLASHES);
            $res = $this->makeRequestDetailed(
                $this->baseUrl . '/Intelligent/FDLib/FDSearch?format=json',
                'POST',
                $body,
                'application/json',
                25
            );
            if (!$res['ok'] || !is_array($res['decoded'])) {
                $lastMsg = $this->hikvisionErrorMessage(
                    is_array($res['decoded']) ? $res['decoded'] : null,
                    'FDSearch HTTP ' . $res['http_code']
                );
                continue;
            }
            $matches = (int) ($res['decoded']['numOfMatches'] ?? 0);
            $list = $res['decoded']['MatchList'] ?? null;
            if ($matches < 1 || !is_array($list) || $list === []) {
                $lastMsg = 'No face photo for ' . $employeeNo . ' on machine.';
                continue;
            }
            if (!isset($list[0]) && isset($list['FPID'])) {
                $list = [$list];
            }
            $row = $list[0] ?? null;
            if (!is_array($row)) {
                continue;
            }
            $fpid = (string) ($row['FPID'] ?? $employeeNo);
            $faceUrl = trim((string) ($row['faceURL'] ?? $row['faceUrl'] ?? ''));
            // Some firmwares embed base64 faceData
            foreach (['faceData', 'pictureData', 'img', 'facePicture'] as $k) {
                if (!empty($row[$k]) && is_string($row[$k])) {
                    $bin = base64_decode($row[$k], true);
                    if (is_string($bin) && strncmp($bin, "\xFF\xD8\xFF", 3) === 0) {
                        return [
                            'ok' => true,
                            'message' => 'OK',
                            'jpeg' => $bin,
                            'face_url' => $faceUrl,
                            'fpid' => $fpid,
                        ];
                    }
                }
            }
            if ($faceUrl !== '') {
                break;
            }
        }

        if ($faceUrl === '') {
            return ['ok' => false, 'message' => $lastMsg];
        }

        // Download JPEG from device LOCALS faceURL (Digest auth)
        $urls = [$faceUrl];
        if (preg_match('#https?://[^/]+(/.*)$#i', $faceUrl, $m)) {
            $hostBase = preg_replace('#/ISAPI/?$#', '', $this->baseUrl);
            $urls[] = rtrim((string) $hostBase, '/') . $m[1];
        }
        foreach (array_unique($urls) as $url) {
            $img = $this->makeRequestDetailed($url, 'GET', null, null, 30);
            if (!$img['ok']) {
                continue;
            }
            $bytes = $img['body'];
            if (strncmp($bytes, "\xFF\xD8\xFF", 3) === 0) {
                return [
                    'ok' => true,
                    'message' => 'OK',
                    'jpeg' => $bytes,
                    'face_url' => $faceUrl,
                    'fpid' => $fpid,
                ];
            }
            // Multipart or JSON-wrapped base64
            if (preg_match('/\xFF\xD8\xFF.{20,}/s', $bytes, $mm)) {
                $start = strpos($bytes, "\xFF\xD8\xFF");
                if ($start !== false) {
                    $jpeg = substr($bytes, $start);
                    $end = strpos($jpeg, "\xFF\xD9");
                    if ($end !== false) {
                        $jpeg = substr($jpeg, 0, $end + 2);
                    }
                    return [
                        'ok' => true,
                        'message' => 'OK',
                        'jpeg' => $jpeg,
                        'face_url' => $faceUrl,
                        'fpid' => $fpid,
                    ];
                }
            }
        }

        return ['ok' => false, 'message' => 'Face record found but photo download failed.', 'face_url' => $faceUrl, 'fpid' => $fpid];
    }

    /**
     * Upload face JPEG to person FPID via FaceDataRecord multipart (no empty faceURL).
     *
     * @return array{ok: bool, message: string, response?: mixed}
     */
    public function enrollFaceFromJpeg(string $employeeNo, string $jpegBytes): array {
        $employeeNo = trim($employeeNo);
        if ($employeeNo === '') {
            return ['ok' => false, 'message' => 'Employee No is required.'];
        }
        if ($jpegBytes === '' || strncmp($jpegBytes, "\xFF\xD8\xFF", 3) !== 0) {
            return ['ok' => false, 'message' => 'A valid JPEG face photo is required (max ~200 KB recommended).'];
        }
        if (strlen($jpegBytes) > 250000) {
            return ['ok' => false, 'message' => 'Face photo is too large. Use a JPEG under 200 KB.'];
        }

        $fdid = $this->resolveFaceLibraryId();
        return $this->uploadFaceImage($employeeNo, $jpegBytes, $fdid);
    }

    /**
     * Upload face JPEG to person FPID via FaceDataRecord multipart (no empty faceURL).
     *
     * @return array{ok: bool, message: string, response?: mixed}
     */
    private function uploadFaceImage(string $employeeNo, string $jpegBytes, string $fdid = '1'): array {
        $meta = json_encode([
            'faceLibType' => 'blackFD',
            'FDID' => (string) $fdid,
            'FPID' => $employeeNo,
        ], JSON_UNESCAPED_SLASHES);

        $boundary = '----SLGTIFace' . bin2hex(random_bytes(6));
        $body = '--' . $boundary . "\r\n"
            . 'Content-Disposition: form-data; name="FaceDataRecord"' . "\r\n"
            . 'Content-Type: application/json' . "\r\n"
            . 'Content-Length: ' . strlen($meta) . "\r\n\r\n"
            . $meta . "\r\n"
            . '--' . $boundary . "\r\n"
            . 'Content-Disposition: form-data; name="FaceImage"; filename="face.jpg"' . "\r\n"
            . 'Content-Type: image/jpeg' . "\r\n"
            . 'Content-Length: ' . strlen($jpegBytes) . "\r\n\r\n"
            . $jpegBytes . "\r\n"
            . '--' . $boundary . '--' . "\r\n";

        $url = $this->baseUrl . '/Intelligent/FDLib/FaceDataRecord?format=json';
        $res = $this->makeRequestDetailed($url, 'POST', $body, 'multipart/form-data; boundary=' . $boundary, 45);
        if ($res['ok'] && $this->isHikvisionOk($res['decoded'] ?? ['statusCode' => 1])) {
            return ['ok' => true, 'message' => 'Face enrolled for ' . $employeeNo . '.', 'response' => $res['decoded']];
        }

        $modUrl = $this->baseUrl . '/Intelligent/FDLib/FDSetUp?format=json';
        $res2 = $this->makeRequestDetailed($modUrl, 'PUT', $body, 'multipart/form-data; boundary=' . $boundary, 45);
        if ($res2['ok'] && $this->isHikvisionOk($res2['decoded'] ?? ['statusCode' => 1])) {
            return ['ok' => true, 'message' => 'Face enrolled for ' . $employeeNo . '.', 'response' => $res2['decoded']];
        }

        $msg = $this->hikvisionErrorMessage(
            is_array($res['decoded']) ? $res['decoded'] : null,
            'Face image upload failed (HTTP ' . $res['http_code'] . ').'
        );
        if (is_string($res['body']) && $res['body'] !== '') {
            $msg .= ' ' . substr(preg_replace('/\s+/', ' ', $res['body']), 0, 180);
        }
        return ['ok' => false, 'message' => $msg, 'response' => $res['decoded'] ?? $res['body']];
    }

    /**
     * Replace face using an uploaded JPEG only when FaceDataRecord API is required.
     * Prefer enrollFaceOnDevice() for DS-K1T320MFWX (built-in camera).
     * Does NOT delete existing face until the new upload succeeds.
     *
     * @return array{ok: bool, message: string, response?: mixed}
     */
    public function changeFaceFromJpeg(string $employeeNo, string $jpegBytes): array {
        $enroll = $this->enrollFaceFromJpeg($employeeNo, $jpegBytes);
        if (!empty($enroll['ok'])) {
            return [
                'ok' => true,
                'message' => 'New face registered on machine (previous face replaced by device). ' . ($enroll['message'] ?? ''),
                'response' => $enroll['response'] ?? null,
            ];
        }
        return [
            'ok' => false,
            'message' => ($enroll['message'] ?? 'Face upload failed.') . ' Existing face was not deleted.',
            'response' => $enroll['response'] ?? null,
        ];
    }

    /**
     * On-device face enrollment for DS-K1T320MFWX (ISAPI).
     * Uses CaptureFaceData/capabilities when available, then CaptureFaceData (no invalid dataType),
     * Progress polling, and UserInfoDetail Setup for terminal UI enrollment.
     * Does not store images. Does not delete existing face first.
     *
     * @return array{ok: bool, message: string, response?: mixed}
     */
    public function enrollFaceOnDevice(string $employeeNo): array {
        $employeeNo = trim($employeeNo);
        if ($employeeNo === '') {
            return ['ok' => false, 'message' => 'Employee No is required.'];
        }

        $errors = [];
        $baseline = $this->searchUsers(5, $employeeNo);
        $baseFace = 0;
        foreach ($baseline['users'] ?? [] as $u) {
            if ((string) ($u['employee_no'] ?? '') === $employeeNo) {
                $baseFace = (int) ($u['face_count'] ?? 0);
                break;
            }
        }

        // Discover supported CaptureFaceData parameters (avoid hard-coded dataType → badParameters)
        $cap = $this->makeRequestDetailed(
            $this->baseUrl . '/AccessControl/CaptureFaceData/capabilities',
            'GET',
            null,
            'application/xml',
            15
        );
        $capOk = $cap['ok'];
        $this->logFaceDebug('capabilities HTTP ' . $cap['http_code'] . ' ' . substr($cap['body'], 0, 200));

        $xmlBodies = [];
        // Minimal body used by Hikvision person-AC examples (captureInfrared only)
        $xmlBodies[] = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<CaptureFaceDataCond version="2.0" xmlns="http://www.isapi.org/ver20/XMLSchema">'
            . '<captureInfrared>false</captureInfrared>'
            . '</CaptureFaceDataCond>';

        if ($capOk && is_string($cap['body']) && stripos($cap['body'], 'dataType') !== false) {
            foreach (['binary', 'url', 'binaryAndModelData', 'modelData'] as $dt) {
                if (stripos($cap['body'], $dt) === false) {
                    continue;
                }
                $xmlBodies[] = '<?xml version="1.0" encoding="UTF-8"?>'
                    . '<CaptureFaceDataCond version="2.0" xmlns="http://www.isapi.org/ver20/XMLSchema">'
                    . '<captureInfrared>false</captureInfrared>'
                    . '<dataType>' . $dt . '</dataType>'
                    . '</CaptureFaceDataCond>';
            }
        }

        $jpeg = null;
        // Prefer JSON CaptureFaceData on modern terminals (DS-K1T343)
        $jsonBodies = [
            json_encode(['CaptureFaceDataCond' => ['captureInfrared' => false]]),
            json_encode(['CaptureFaceDataCond' => ['dataType' => 'binary', 'captureInfrared' => false]]),
        ];
        foreach ($jsonBodies as $jsonBody) {
            $res = $this->makeRequestDetailed(
                $this->baseUrl . '/AccessControl/CaptureFaceData?format=json',
                'POST',
                $jsonBody,
                'application/json',
                45
            );
            if ($res['http_code'] >= 400) {
                $errors[] = 'CaptureFaceData JSON HTTP ' . $res['http_code'];
                continue;
            }
            $jpeg = $this->extractFaceJpeg($res['body'], $res['content_type'], $res['decoded']);
            if ($jpeg !== null) {
                break;
            }
        }

        if ($jpeg === null) {
        foreach ($xmlBodies as $xmlBody) {
            $res = $this->makeRequestDetailed(
                $this->baseUrl . '/AccessControl/CaptureFaceData',
                'POST',
                $xmlBody,
                'application/xml; charset="UTF-8"',
                20
            );
            if ($res['http_code'] >= 400) {
                $errors[] = 'CaptureFaceData HTTP ' . $res['http_code'];
                // Do not keep trying dataType variants if device rejects parameters entirely without capabilities
                if (!$capOk && stripos($res['body'], 'badParameters') !== false) {
                    break;
                }
                continue;
            }
            $jpeg = $this->extractFaceJpeg($res['body'], $res['content_type'], $res['decoded']);
            if ($jpeg !== null) {
                break;
            }
            for ($i = 0; $i < 30; $i++) {
                usleep(500000);
                $prog = $this->makeRequestDetailed(
                    $this->baseUrl . '/AccessControl/CaptureFaceData/Progress',
                    'GET',
                    null,
                    'application/xml',
                    15
                );
                if ($prog['http_code'] >= 400) {
                    break;
                }
                $jpeg = $this->extractFaceJpeg($prog['body'], $prog['content_type'], $prog['decoded']);
                if ($jpeg !== null) {
                    break 2;
                }
            }
        }
        } // end if jpeg === null XML fallback

        if ($jpeg !== null) {
            // Transient JPEG in memory only — push to machine FaceDataRecord, never save to DB
            $uploaded = $this->uploadFaceImage($employeeNo, $jpeg, $this->resolveFaceLibraryId());
            if (!empty($uploaded['ok'])) {
                return $uploaded;
            }
            $errors[] = $uploaded['message'] ?? 'FaceDataRecord upload failed';
        }

        // Terminal UI enrollment: ask device to open person face collection for this employeeNo
        $setupAttempts = [
            json_encode([
                'UserInfoDetail' => [
                    'mode' => 'employeeNo',
                    'EmployeeNoList' => [['employeeNo' => $employeeNo]],
                ],
            ]),
            json_encode([
                'UserInfoDetail' => [
                    'mode' => 'byEmployeeNo',
                    'EmployeeNoList' => [['employeeNo' => $employeeNo]],
                ],
            ]),
        ];
        $setupOk = false;
        foreach ($setupAttempts as $payload) {
            $setup = $this->makeRequestDetailed(
                $this->baseUrl . '/AccessControl/UserInfoDetail/Setup?format=json',
                'PUT',
                $payload,
                'application/json',
                20
            );
            if ($setup['ok'] && $this->isHikvisionOk($setup['decoded'] ?? ['statusCode' => 1])) {
                $setupOk = true;
                break;
            }
            $errors[] = 'UserInfoDetail/Setup HTTP ' . $setup['http_code'];
        }

        if ($setupOk) {
            // Poll until face_count increases or timeout (~90s)
            $deadline = time() + 90;
            while (time() < $deadline) {
                sleep(3);
                $search = $this->searchUsers(5, $employeeNo);
                foreach ($search['users'] ?? [] as $u) {
                    if ((string) ($u['employee_no'] ?? '') !== $employeeNo) {
                        continue;
                    }
                    $fc = (int) ($u['face_count'] ?? 0);
                    if ($fc > $baseFace) {
                        return [
                            'ok' => true,
                            'message' => 'Face enrolled on terminal for ' . $employeeNo . ' (face count ' . $fc . ').',
                            'response' => $u,
                        ];
                    }
                }
            }
            return [
                'ok' => false,
                'message' => 'Terminal enrollment mode was started for ' . $employeeNo
                    . ', but no new face was detected within 90s. Complete face capture on the device screen, then refresh status.',
            ];
        }

        $detail = $errors !== [] ? implode('; ', array_slice(array_unique($errors), 0, 4)) : 'unsupported';
        return [
            'ok' => false,
            'message' => 'On-device face enrollment failed (' . $detail
                . '). Stand at the terminal while enrolling, or use a student profile JPEG photo. Existing face was not removed.',
        ];
    }

    private function logFaceDebug(string $msg): void {
        error_log('[Hikvision Face] ' . $msg);
    }

    /**
     * Legacy wrapper — routes to on-device enrollment (no browser JPEG).
     *
     * @return array{ok: bool, message: string, response?: mixed}
     */
    public function enrollFace(string $employeeNo): array {
        return $this->enrollFaceOnDevice($employeeNo);
    }
}

