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
        $this->host = $config['host'] ?? '192.168.1.64';
        $this->port = $config['port'] ?? 80;
        $this->username = $config['username'] ?? 'admin';
        $this->password = $config['password'] ?? 'admin12345';
        $this->timeout = $config['timeout'] ?? 10;
        $this->ssl = $config['ssl'] ?? false;
        
        // Build base URL for ISAPI (use https if SSL is enabled)
        $protocol = $this->ssl ? 'https' : 'http';
        $this->baseUrl = "{$protocol}://{$this->host}:{$this->port}/ISAPI";
    }
    
    /**
     * Test connection to Hikvision device
     * 
     * @return array ['success' => bool, 'message' => string]
     */
    public function testConnection() {
        try {
            // Try different endpoints to identify device type
            $protocol = $this->ssl ? 'https' : 'http';
            $urls = [
                $this->baseUrl . "/System/deviceInfo",
                "{$protocol}://{$this->host}:{$this->port}/ISAPI/System/deviceInfo",
                "{$protocol}://{$this->host}:{$this->port}/System/deviceInfo",
                "{$protocol}://{$this->host}:{$this->port}/",
            ];
            
            $lastError = '';
            foreach ($urls as $url) {
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
    private function makeRequest($url, $method = 'GET', $data = null, $contentType = 'application/json') {
        $ch = curl_init();
        
        // Authentication - try both Basic and Digest
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_USERPWD, $this->username . ':' . $this->password);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC | CURLAUTH_DIGEST); // Try both Basic and Digest
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        // Set headers
        $headers = [];
        if ($contentType) {
            $headers[] = 'Content-Type: ' . $contentType;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        // Set method and data
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);
        
        // Log raw response for debugging (first 1000 chars)
        error_log("Hikvision makeRequest - URL: $url, Method: $method, HTTP Code: $httpCode, Content-Type: $contentType");
        error_log("Hikvision makeRequest - Raw response (first 1000 chars): " . substr($response, 0, 1000));
        
        if ($error) {
            error_log("Hikvision CURL Error: $error");
            throw new Exception('CURL Error: ' . $error);
        }
        
        if ($httpCode >= 400) {
            error_log("Hikvision HTTP Error: $httpCode, Response: " . substr($response, 0, 500));
            throw new Exception('HTTP Error: ' . $httpCode . ' - Response: ' . substr($response, 0, 200));
        }
        
        // Return raw response if empty
        if (empty($response)) {
            error_log("Hikvision: Empty response received");
            return false;
        }
        
        // Try to decode as JSON first, then XML
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Try XML parsing
            $decoded = $this->parseXML($response);
            
            // If XML parsing also fails, log the raw response
            if (empty($decoded)) {
                error_log("Hikvision: Failed to parse response as JSON or XML. Raw response (first 1000 chars): " . substr($response, 0, 1000));
                // Return raw response as string for debugging
                return ['_raw_response' => $response, '_response_length' => strlen($response)];
            }
        }
        
        return $decoded;
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
}

