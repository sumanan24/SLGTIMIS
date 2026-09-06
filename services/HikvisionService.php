<?php
/**
 * Hikvision LAN connectivity service (custom PHP MVC).
 * Talks only to private IPs — no Internet / external DNS checks.
 */
declare(strict_types=1);

class HikvisionService {
    private string $username;
    private string $password;
    private int $port;
    private bool $https;
    private int $timeout;

    public function __construct(?array $opts = null) {
        require_once BASE_PATH . '/config/hikvision.php';
        $this->username = (string) ($opts['username'] ?? HIKVISION_USER);
        $this->password = (string) ($opts['password'] ?? hikvision_pass());
        $this->port = (int) ($opts['port'] ?? HIKVISION_HTTP_PORT);
        if ($this->port <= 0) {
            $this->port = 80;
        }
        $this->https = (bool) ($opts['https'] ?? HIKVISION_USE_HTTPS);
        $this->timeout = max(3, (int) ($opts['timeout'] ?? HIKVISION_TIMEOUT));
    }

    /** @return list<array{key:string,label:string,ip:string,role:string}> */
    public function devices(): array {
        require_once BASE_PATH . '/config/hikvision.php';
        return hikvision_devices();
    }

    /**
     * Full LAN diagnostic for one device.
     *
     * @return array{
     *   key:string,label:string,ip:string,role:string,
     *   status:string,device_name:string,last_error:string,
     *   ping_ok:bool,tcp_ok:bool,http_ok:bool,auth_ok:bool,
     *   http_code:int,checked_at:string,details:array
     * }
     */
    public function testDevice(array $device): array {
        $ip = trim((string) ($device['ip'] ?? ''));
        $key = (string) ($device['key'] ?? '');
        $label = (string) ($device['label'] ?? $ip);
        $role = (string) ($device['role'] ?? '');
        $checkedAt = date('Y-m-d H:i:s');

        $result = [
            'key' => $key,
            'label' => $label,
            'ip' => $ip,
            'role' => $role,
            'status' => 'OFFLINE',
            'device_name' => '',
            'last_error' => '',
            'ping_ok' => false,
            'tcp_ok' => false,
            'http_ok' => false,
            'auth_ok' => false,
            'http_code' => 0,
            'checked_at' => $checkedAt,
            'details' => [],
        ];

        if ($ip === '' || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            $result['status'] = 'OFFLINE';
            $result['last_error'] = 'Invalid configuration — IP missing';
            return $result;
        }

        if (!function_exists('curl_init')) {
            $result['status'] = 'OFFLINE';
            $result['last_error'] = 'Invalid configuration — PHP cURL missing on this server';
            return $result;
        }

        if (trim($this->password) === '') {
            $result['status'] = 'OFFLINE';
            $result['last_error'] = 'Invalid configuration — deploy config/hikvision.php with HIKVISION_PASS';
            return $result;
        }

        // 1) IP reachability (ICMP ping — informational; may be blocked by firewall)
        $ping = $this->pingHost($ip);
        $result['ping_ok'] = !empty($ping['ok']);
        $result['details']['ping'] = $ping;

        // 2) TCP port
        $tcp = $this->tcpConnect($ip, $this->port, min(5, $this->timeout));
        $result['tcp_ok'] = !empty($tcp['ok']);
        $result['details']['tcp'] = $tcp;
        if (empty($tcp['ok'])) {
            $result['status'] = (($tcp['reason'] ?? '') === 'Connection Timeout') ? 'TIMEOUT' : 'OFFLINE';
            $result['last_error'] = (string) ($tcp['reason'] ?? 'Network Unreachable');
            return $result;
        }

        // 3) HTTP/HTTPS (no auth) — Hikvision usually returns 401
        $http = $this->httpProbe($ip);
        $result['http_ok'] = !empty($http['ok']);
        $result['http_code'] = (int) ($http['http_code'] ?? 0);
        $result['details']['http'] = $http;
        if (empty($http['ok'])) {
            $result['status'] = (($http['reason'] ?? '') === 'Connection Timeout') ? 'TIMEOUT' : 'OFFLINE';
            $result['last_error'] = (string) ($http['reason'] ?? 'Device Not Responding');
            return $result;
        }

        // 4) Digest auth + deviceInfo (ONE attempt only)
        $auth = $this->digestDeviceInfo($ip);
        $result['http_code'] = (int) ($auth['http_code'] ?? $result['http_code']);
        $result['details']['auth'] = [
            'ok' => !empty($auth['ok']),
            'http_code' => $result['http_code'],
            'reason' => $auth['reason'] ?? '',
        ];

        if (empty($auth['ok'])) {
            $code = (int) ($auth['http_code'] ?? 0);
            $result['status'] = 'AUTH ERROR';
            $result['auth_ok'] = false;
            if ($code === 401) {
                $result['last_error'] = 'HTTP 401 Authentication Failed';
            } elseif ($code === 403) {
                $result['last_error'] = 'HTTP 403 Forbidden';
            } else {
                $result['last_error'] = (string) ($auth['reason'] ?? 'HTTP 401 Authentication Failed');
            }
            return $result;
        }

        $result['status'] = 'ONLINE';
        $result['auth_ok'] = true;
        $result['device_name'] = (string) ($auth['device_name'] ?? '');
        $result['last_error'] = '';
        return $result;
    }

    /** Test all MAIN + readers (stops further auth after first AUTH ERROR to protect admin lock). */
    public function testAll(): array {
        $out = [];
        $stopAuth = false;
        foreach ($this->devices() as $device) {
            if ($stopAuth) {
                // Still check reachability without Digest
                $partial = $this->testReachabilityOnly($device);
                $partial['status'] = 'AUTH ERROR';
                $partial['last_error'] = 'Authentication skipped — previous device failed login (avoid lockout)';
                $out[] = $partial;
                continue;
            }
            $row = $this->testDevice($device);
            if (($row['status'] ?? '') === 'AUTH ERROR') {
                $stopAuth = true;
            }
            $out[] = $row;
        }
        return $out;
    }

    /** TCP + HTTP only (no Digest). */
    public function testReachabilityOnly(array $device): array {
        $ip = trim((string) ($device['ip'] ?? ''));
        $checkedAt = date('Y-m-d H:i:s');
        $row = [
            'key' => (string) ($device['key'] ?? ''),
            'label' => (string) ($device['label'] ?? $ip),
            'ip' => $ip,
            'role' => (string) ($device['role'] ?? ''),
            'status' => 'OFFLINE',
            'device_name' => '',
            'last_error' => '',
            'ping_ok' => false,
            'tcp_ok' => false,
            'http_ok' => false,
            'auth_ok' => false,
            'http_code' => 0,
            'checked_at' => $checkedAt,
            'details' => [],
        ];
        $ping = $this->pingHost($ip);
        $row['ping_ok'] = !empty($ping['ok']);
        $tcp = $this->tcpConnect($ip, $this->port, min(5, $this->timeout));
        $row['tcp_ok'] = !empty($tcp['ok']);
        if (empty($tcp['ok'])) {
            $row['last_error'] = (string) ($tcp['reason'] ?? 'Network Unreachable');
            $row['status'] = (($tcp['reason'] ?? '') === 'Connection Timeout') ? 'TIMEOUT' : 'OFFLINE';
            return $row;
        }
        $http = $this->httpProbe($ip);
        $row['http_ok'] = !empty($http['ok']);
        $row['http_code'] = (int) ($http['http_code'] ?? 0);
        if (empty($http['ok'])) {
            $row['last_error'] = (string) ($http['reason'] ?? 'Device Not Responding');
            $row['status'] = (($http['reason'] ?? '') === 'Connection Timeout') ? 'TIMEOUT' : 'OFFLINE';
            return $row;
        }
        $row['status'] = 'ONLINE'; // reachable; auth not tested
        return $row;
    }

    /** @return array{ok:bool,reason:string} */
    private function pingHost(string $ip): array {
        $isWin = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $cmd = $isWin
            ? 'ping -n 1 -w 1000 ' . escapeshellarg($ip)
            : 'ping -c 1 -W 1 ' . escapeshellarg($ip);
        $out = [];
        $code = 1;
        @exec($cmd, $out, $code);
        if ($code === 0) {
            return ['ok' => true, 'reason' => 'OK'];
        }
        return ['ok' => false, 'reason' => 'Network Unreachable'];
    }

    /** @return array{ok:bool,reason:string} */
    private function tcpConnect(string $ip, int $port, int $timeoutSec): array {
        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
        if (!function_exists('fsockopen') || in_array('fsockopen', $disabled, true)) {
            // Fall through — HTTP probe will decide
            return ['ok' => true, 'reason' => 'TCP probe skipped'];
        }
        $errno = 0;
        $errstr = '';
        $fp = @fsockopen($ip, $port, $errno, $errstr, max(1, $timeoutSec));
        if ($fp !== false && $fp !== null) {
            fclose($fp);
            return ['ok' => true, 'reason' => 'OK'];
        }
        $err = strtolower(trim($errstr . ' ' . $errno));
        if (str_contains($err, 'timed out') || str_contains($err, 'timeout') || $errno === 10060 || $errno === 110) {
            return ['ok' => false, 'reason' => 'Connection Timeout'];
        }
        if (str_contains($err, 'refused') || $errno === 10061 || $errno === 111) {
            return ['ok' => false, 'reason' => 'Port Closed'];
        }
        return ['ok' => false, 'reason' => 'Network Unreachable'];
    }

    /** @return array{ok:bool,http_code:int,reason:string} */
    private function httpProbe(string $ip): array {
        $scheme = $this->https ? 'https' : 'http';
        $portPart = (($this->https && $this->port === 443) || (!$this->https && $this->port === 80))
            ? ''
            : (':' . $this->port);
        $url = $scheme . '://' . $ip . $portPart . '/ISAPI/System/deviceInfo';

        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTPHEADER => ['Expect:'],
        ];
        if (defined('CURL_IPRESOLVE_V4')) {
            $opts[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
        }
        if ($this->https) {
            $opts[CURLOPT_SSL_VERIFYPEER] = false;
            $opts[CURLOPT_SSL_VERIFYHOST] = 0;
        }
        curl_setopt_array($ch, $opts);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = (string) curl_error($ch);
        curl_close($ch);

        if ($code > 0) {
            return ['ok' => true, 'http_code' => $code, 'reason' => 'OK'];
        }
        $el = strtolower($err);
        if (str_contains($el, 'timed out') || str_contains($el, 'timeout')) {
            return ['ok' => false, 'http_code' => 0, 'reason' => 'Connection Timeout'];
        }
        if (str_contains($el, 'refused')) {
            return ['ok' => false, 'http_code' => 0, 'reason' => 'Port Closed'];
        }
        return ['ok' => false, 'http_code' => 0, 'reason' => $err !== '' ? $err : 'Device Not Responding'];
    }

    /**
     * One Digest GET /ISAPI/System/deviceInfo
     * @return array{ok:bool,http_code:int,reason:string,device_name?:string}
     */
    private function digestDeviceInfo(string $ip): array {
        $scheme = $this->https ? 'https' : 'http';
        $portPart = (($this->https && $this->port === 443) || (!$this->https && $this->port === 80))
            ? ''
            : (':' . $this->port);
        $url = $scheme . '://' . $ip . $portPart . '/ISAPI/System/deviceInfo';

        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPAUTH => CURLAUTH_DIGEST,
            CURLOPT_USERPWD => $this->username . ':' . $this->password,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTPHEADER => ['Expect:'],
        ];
        if (defined('CURL_IPRESOLVE_V4')) {
            $opts[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
        }
        if ($this->https) {
            $opts[CURLOPT_SSL_VERIFYPEER] = false;
            $opts[CURLOPT_SSL_VERIFYHOST] = 0;
        }
        curl_setopt_array($ch, $opts);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = (string) curl_error($ch);
        curl_close($ch);

        if ($code === 401) {
            return ['ok' => false, 'http_code' => 401, 'reason' => 'HTTP 401 Authentication Failed'];
        }
        if ($code === 403) {
            return ['ok' => false, 'http_code' => 403, 'reason' => 'HTTP 403 Forbidden'];
        }
        if ($code <= 0 || $body === false) {
            $el = strtolower($err);
            if (str_contains($el, 'timed out') || str_contains($el, 'timeout')) {
                return ['ok' => false, 'http_code' => 0, 'reason' => 'Connection Timeout'];
            }
            return ['ok' => false, 'http_code' => 0, 'reason' => $err !== '' ? $err : 'Device Not Responding'];
        }
        if ($code >= 400) {
            return ['ok' => false, 'http_code' => $code, 'reason' => 'HTTP ' . $code];
        }

        $name = '';
        if (is_string($body) && $body !== '') {
            if (preg_match('/<(?:deviceName|model|deviceType)>\s*([^<]+)\s*</i', $body, $m)) {
                $name = trim(html_entity_decode($m[1], ENT_QUOTES | ENT_XML1, 'UTF-8'));
            }
            $json = json_decode($body, true);
            if (is_array($json)) {
                $di = $json['DeviceInfo'] ?? $json;
                if (is_array($di)) {
                    $name = (string) ($di['deviceName'] ?? $di['model'] ?? $di['deviceType'] ?? $name);
                }
            }
        }

        return ['ok' => true, 'http_code' => $code, 'reason' => 'OK', 'device_name' => $name];
    }
}
