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
        $this->password = (string) ($config['password'] ?? 'admin12345');
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

    /** Endpoint base used for requests (for diagnostics). */
    public function getBaseUrl(): string {
        return $this->baseUrl;
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

        if ($lockStatus === 'lock' || $unlockTime > 0) {
            $mins = max(1, (int) ceil($unlockTime / 60));
            return 'HTTP 401 — device admin account is temporarily locked after failed logins'
                . ($unlockTime > 0 ? (" (wait ~{$mins} min / {$unlockTime}s, or reboot the terminal)") : '')
                . '. Then retry Test on Biometric machines. URL: ' . $url;
        }

        return 'HTTP 401 Unauthorized at ' . $url
            . ' (user=' . $this->username . '). Digest login failed — use the same password as Device sync (HTTP port 80).';
    }

    /**
     * cURL options matching staff_attendance/includes/hikvision_sync_lib.php (working Digest path).
     *
     * @return \CurlHandle|resource
     */
    private function createDigestCurl(string $url, int $timeout) {
        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPAUTH => CURLAUTH_DIGEST,
            CURLOPT_USERPWD => $this->username . ':' . $this->password,
            CURLOPT_CONNECTTIMEOUT => min(15, max(3, $timeout)),
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_FOLLOWLOCATION => false,
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

    private function makeRequest($url, $method = 'GET', $data = null, $contentType = 'application/json', $timeoutOverride = null) {
        $timeout = $timeoutOverride !== null ? (int) $timeoutOverride : (int) $this->timeout;
        $ch = $this->createDigestCurl($url, $timeout);

        $headers = [];
        // Only send Content-Type when there is a body (sync lib does the same for POST)
        $method = strtoupper((string) $method);
        if ($data !== null && $data !== '' && $contentType) {
            $headers[] = 'Content-Type: ' . $contentType;
        }
        if ($headers !== []) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

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
            return ['ok' => true, 'message' => 'OK', 'users' => $users];
        } catch (Exception $e) {
            return ['ok' => false, 'message' => $e->getMessage(), 'users' => $users];
        }
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
     *
     * @return array{ok: bool, message: string, response?: mixed}
     */
    public function deleteFingerPrint(string $employeeNo, int $fingerNo = 0): array {
        $employeeNo = trim($employeeNo);
        if ($employeeNo === '') {
            return ['ok' => false, 'message' => 'Employee No is required.'];
        }

        $fpList = [
            [
                'employeeNo' => $employeeNo,
                'enableCardReader' => [1],
            ],
        ];
        if ($fingerNo > 0) {
            $fpList[0]['fingerPrintID'] = max(1, min(10, $fingerNo));
        }

        $payload = [
            'FingerPrintDelete' => [
                'mode' => 'byEmployeeNo',
                'EmployeeNoList' => [
                    ['employeeNo' => $employeeNo],
                ],
                'fingerPrintList' => $fpList,
            ],
        ];

        try {
            $url = $this->baseUrl . '/AccessControl/FingerPrint/Delete?format=json';
            $response = $this->makeRequest($url, 'PUT', json_encode($payload), 'application/json');
            if ($this->isHikvisionOk($response)) {
                return ['ok' => true, 'message' => 'Old fingerprint removed.', 'response' => $response];
            }
            // Not found / empty is fine when replacing
            $sub = is_array($response)
                ? (string) ($response['subStatusCode'] ?? ($response['ResponseStatus']['subStatusCode'] ?? ''))
                : '';
            if ($sub === '' || stripos($sub, 'not') !== false || stripos((string) $this->hikvisionErrorMessage($response), 'not') !== false) {
                return ['ok' => true, 'message' => 'No previous fingerprint (or already cleared).', 'response' => $response];
            }
            return ['ok' => false, 'message' => $this->hikvisionErrorMessage($response, 'Failed to delete fingerprint.'), 'response' => $response];
        } catch (Exception $e) {
            // Many firmwares return error when none exist — treat as OK for replace flow
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
     * Person must stand at the device and place a finger when prompted.
     *
     * @return array{ok: bool, message: string, response?: mixed}
     */
    public function enrollFingerPrint(string $employeeNo, int $fingerNo = 1): array {
        $employeeNo = trim($employeeNo);
        if ($employeeNo === '') {
            return ['ok' => false, 'message' => 'Employee No is required.'];
        }
        $fingerNo = max(1, min(10, $fingerNo));

        try {
            $captureUrl = $this->baseUrl . '/AccessControl/CaptureFingerPrint?format=json';
            $captureBody = json_encode([
                'CaptureFingerPrintCond' => [
                    'fingerNo' => $fingerNo,
                ],
            ]);
            // Capture waits for the person at the device
            $captured = $this->makeRequest($captureUrl, 'POST', $captureBody, 'application/json', 60);
            $fpBlock = is_array($captured) ? ($captured['CaptureFingerPrint'] ?? $captured) : null;
            $printData = is_array($fpBlock) ? ($fpBlock['fingerData'] ?? $fpBlock['fingerprintData'] ?? null) : null;
            if (!is_string($printData) || $printData === '') {
                return [
                    'ok' => false,
                    'message' => $this->hikvisionErrorMessage($captured, 'Fingerprint capture failed or timed out. Ask the person to place a finger on the device, then try again.'),
                    'response' => $captured,
                ];
            }

            $quality = is_array($fpBlock) ? ($fpBlock['fingerPrintQuality'] ?? null) : null;
            $downloadUrl = $this->baseUrl . '/AccessControl/FingerPrintDownload?format=json';
            $downloadBody = json_encode([
                'FingerPrintCfg' => [
                    'employeeNo' => $employeeNo,
                    'enableCardReader' => [1],
                    'fingerPrintID' => $fingerNo,
                    'fingerType' => 'normalFP',
                    'fingerData' => $printData,
                ],
            ]);
            $saved = $this->makeRequest($downloadUrl, 'POST', $downloadBody, 'application/json', 30);
            if ($this->isHikvisionOk($saved)) {
                $q = $quality !== null ? ' (quality: ' . $quality . ')' : '';
                return ['ok' => true, 'message' => 'Fingerprint enrolled for ' . $employeeNo . $q . '.', 'response' => $saved];
            }
            return ['ok' => false, 'message' => $this->hikvisionErrorMessage($saved, 'Fingerprint captured but save to user failed.'), 'response' => $saved];
        } catch (Exception $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Low-level request that returns status instead of throwing on HTTP 4xx.
     *
     * @return array{ok: bool, http_code: int, content_type: string, body: string, decoded: mixed, error: string}
     */
    private function makeRequestDetailed($url, $method = 'GET', $data = null, $contentType = 'application/json', $timeoutOverride = null): array {
        $timeout = $timeoutOverride !== null ? (int) $timeoutOverride : (int) $this->timeout;
        $ch = $this->createDigestCurl($url, $timeout);

        $headers = [];
        $method = strtoupper((string) $method);
        if ($data !== null && $data !== '' && $contentType) {
            $headers[] = 'Content-Type: ' . $contentType;
        }
        if ($headers !== []) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

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
            $json = json_decode($body, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $decoded = $json;
            } else {
                $xml = $this->parseXML($body);
                $decoded = $xml !== [] ? $xml : null;
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
            'message' => 'On-device face enrollment failed for DS-K1T320MFWX (' . $detail
                . '). Stand at the terminal while enrolling. Existing face was not removed.',
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

