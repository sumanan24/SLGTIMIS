<?php
declare(strict_types=1);

/**
 * Hikvision ISAPI AcsEvent pull + insert into staff_attendance.
 */

/** PHP 7 compatible (DateTimeImmutable::createFromInterface is PHP 8+). */
function attendance_datetime_to_immutable(DateTimeInterface $dt): DateTimeImmutable
{
    if ($dt instanceof DateTimeImmutable) {
        return $dt;
    }
    if ($dt instanceof DateTime) {
        return DateTimeImmutable::createFromMutable($dt);
    }
    return new DateTimeImmutable($dt->format('Y-m-d H:i:s'), $dt->getTimezone());
}

function attendance_parse_device_time(string $raw): ?string
{
    $raw = trim($raw);
    if ($raw === '') {
        return null;
    }
    $tz = new DateTimeZone(defined('STAFF_TIMEZONE') ? STAFF_TIMEZONE : 'Asia/Colombo');
    try {
        $dt = new DateTimeImmutable($raw);
        return $dt->setTimezone($tz)->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        // continue with explicit formats
    }
    $formats = [
        'Y-m-d\TH:i:s',
        'Y-m-d\TH:i:sP',
        'Y-m-d H:i:s',
        'Y/m/d H:i:s',
        'Y-m-d\TH:i:s.v',
    ];
    foreach ($formats as $f) {
        $dt = DateTimeImmutable::createFromFormat($f, $raw, $tz);
        if ($dt !== false) {
            return $dt->setTimezone($tz)->format('Y-m-d H:i:s');
        }
    }
    $ts = strtotime($raw);
    if ($ts !== false) {
        return (new DateTimeImmutable('@' . $ts))->setTimezone($tz)->format('Y-m-d H:i:s');
    }
    return null;
}

/**
 * Total events in search (if device reports it). Used to page until all rows are fetched.
 */
function attendance_hikvision_acs_event_total(array $data): ?int
{
    $acs = $data['AcsEvent'] ?? null;
    if (!is_array($acs)) {
        return null;
    }
    foreach (['totalMatches', 'TotalMatches'] as $k) {
        if (isset($acs[$k]) && $acs[$k] !== '' && is_numeric($acs[$k])) {
            return (int) $acs[$k];
        }
    }
    return null;
}

/**
 * @return array<int, array<string, mixed>>
 */
function attendance_extract_info_list(array $data): array
{
    if (isset($data['AcsEvent']['InfoList']) && is_array($data['AcsEvent']['InfoList'])) {
        $il = $data['AcsEvent']['InfoList'];
        if (isset($il['time']) || isset($il['Time']) || isset($il['employeeNoString']) || isset($il['EmployeeNoString'])) {
            return [$il];
        }
        return array_values(array_filter($il, 'is_array'));
    }
    if (isset($data['AcsEvent']['InfoList']['Info']) && is_array($data['AcsEvent']['InfoList']['Info'])) {
        $x = $data['AcsEvent']['InfoList']['Info'];
        return isset($x[0]) ? $x : [$x];
    }
    if (isset($data['AcsEventList']) && is_array($data['AcsEventList'])) {
        return $data['AcsEventList'];
    }
    if (isset($data['AcsEvent']['Info']) && is_array($data['AcsEvent']['Info'])) {
        $x = $data['AcsEvent']['Info'];
        return isset($x[0]) ? $x : [$x];
    }
    return [];
}

/**
 * Flush buffered rows with INSERT IGNORE (multi-row batches).
 *
 * @param array<int, array{employee_no: string, staff_name: string, department: string, attendance_time: string, device_ip: string, event_type: string}> $batch
 */
function attendance_flush_staff_batch(mysqli $db, array &$batch, array &$out): void
{
    if ($batch === []) {
        return;
    }
    $chunkSize = defined('HIKVISION_INSERT_BATCH_SIZE') ? max(1, min(1000, (int) HIKVISION_INSERT_BATCH_SIZE)) : 500;
    foreach (array_chunk($batch, $chunkSize) as $chunk) {
        $parts = [];
        foreach ($chunk as $r) {
            $parts[] = sprintf(
                "('%s','%s','%s','%s','%s','%s')",
                $db->real_escape_string($r['employee_no']),
                $db->real_escape_string($r['staff_name']),
                $db->real_escape_string($r['department']),
                $db->real_escape_string($r['attendance_time']),
                $db->real_escape_string($r['device_ip']),
                $db->real_escape_string($r['event_type'])
            );
        }
        $sql = 'INSERT IGNORE INTO staff_attendance (employee_no, staff_name, department, attendance_time, device_ip, event_type) VALUES ' . implode(',', $parts);
        $db->query($sql);
        $aff = $db->affected_rows;
        $out['inserted'] += $aff;
        $out['skipped_dup'] += count($chunk) - $aff;
    }
    $batch = [];
}

/**
 * Pull events from device and INSERT IGNORE into staff_attendance.
 *
 * @return array{ok: bool, inserted: int, skipped_dup: int, skipped_bad: int, total_received: int, debug: list<string>, error: ?string}
 */
function attendance_run_hikvision_sync(DateTimeInterface $start, DateTimeInterface $end): array
{
    $out = [
        'ok' => false,
        'inserted' => 0,
        'skipped_dup' => 0,
        'skipped_bad' => 0,
        'total_received' => 0,
        'debug' => [],
        'error' => null,
    ];

    $connectT = defined('HIKVISION_CURL_CONNECT_TIMEOUT') ? (int) HIKVISION_CURL_CONNECT_TIMEOUT : 10;
    $timeoutT = defined('HIKVISION_SYNC_CURL_TIMEOUT') ? (int) HIKVISION_SYNC_CURL_TIMEOUT : 300;

    try {
        return attendance_run_hikvision_sync_inner($start, $end, $connectT, $timeoutT);
    } catch (Throwable $e) {
        $out['error'] = 'Sync error: ' . $e->getMessage();
        return $out;
    }
}

/**
 * Full URL for AcsEvent search (includes optional port).
 */
function attendance_hikvision_acs_event_url(): string
{
    $https = defined('HIKVISION_USE_HTTPS') && HIKVISION_USE_HTTPS;
    $scheme = $https ? 'https' : 'http';
    $defaultPort = $https ? 443 : 80;
    $port = defined('HIKVISION_HTTP_PORT') ? (int) HIKVISION_HTTP_PORT : 0;
    $host = HIKVISION_IP;
    if ($port > 0 && $port !== $defaultPort) {
        $host .= ':' . $port;
    }
    return $scheme . '://' . $host . '/ISAPI/AccessControl/AcsEvent?format=json';
}

/**
 * Append hint when connection fails (typical: public web server vs private 172.16.x.x).
 */
function attendance_hikvision_curl_error_hint(string $curlErr): string
{
    $e = strtolower($curlErr);
    if (strpos($e, 'failed to connect') !== false
        || strpos($e, 'connection timed out') !== false
        || strpos($e, 'timed out') !== false
        || strpos($e, 'couldn\'t connect') !== false
        || strpos($e, 'could not resolve') !== false
        || strpos($e, 'network is unreachable') !== false) {
        return $curlErr . ' — ' . HIKVISION_IP . ' is a private LAN address: this PHP server has no route to it. '
            . 'Open sync from a machine on the same network as the terminal (e.g. local WAMP), or put the app server on that LAN/VPN. '
            . 'Longer timeouts do not fix a wrong network.';
    }
    return $curlErr;
}

/**
 * POST to Hikvision ISAPI: prefer ext-curl; otherwise HTTP Digest via PHP streams (allow_url_fopen).
 *
 * @return array{http_code: int, body: string, error: ?string}
 */
function attendance_hikvision_isapi_post(string $url, string $body, int $connectT, int $timeoutT): array
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        $opts = [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPAUTH => CURLAUTH_DIGEST,
            CURLOPT_USERPWD => HIKVISION_USER . ':' . HIKVISION_PASS,
            CURLOPT_CONNECTTIMEOUT => $connectT,
            CURLOPT_TIMEOUT => $timeoutT,
        ];
        if (defined('CURL_IPRESOLVE_V4')) {
            $opts[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
        }
        curl_setopt_array($ch, $opts);
        if (strpos($url, 'https:') === 0) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        }
        $response = curl_exec($ch);
        $curlErr = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($response === false) {
            return ['http_code' => 0, 'body' => '', 'error' => 'cURL error: ' . attendance_hikvision_curl_error_hint($curlErr)];
        }
        return ['http_code' => $httpCode, 'body' => (string) $response, 'error' => null];
    }

    if (!ini_get('allow_url_fopen')) {
        return [
            'http_code' => 0,
            'body' => '',
            'error' => 'Enable PHP extension curl, or set allow_url_fopen=On in php.ini for HTTP Digest fallback.',
        ];
    }

    require_once __DIR__ . '/digest_http_client.php';
    $r = attendance_digest_post_json($url, $body, HIKVISION_USER, HIKVISION_PASS, $timeoutT, false);
    if ($r['ok']) {
        return ['http_code' => $r['http_code'], 'body' => $r['body'], 'error' => null];
    }
    return [
        'http_code' => $r['http_code'],
        'body' => $r['body'],
        'error' => $r['error'] ?? ('HTTP ' . $r['http_code']),
    ];
}

/**
 * @internal
 *
 * @return array{ok: bool, inserted: int, skipped_dup: int, skipped_bad: int, total_received: int, debug: list<string>, error: ?string}
 */
function attendance_run_hikvision_sync_inner(DateTimeInterface $start, DateTimeInterface $end, int $connectT, int $timeoutT): array
{
    if (function_exists('set_time_limit')) {
        @set_time_limit(0);
    }

    $out = [
        'ok' => false,
        'inserted' => 0,
        'skipped_dup' => 0,
        'skipped_bad' => 0,
        'total_received' => 0,
        'debug' => [],
        'error' => null,
    ];

    $tz = new DateTimeZone(defined('STAFF_TIMEZONE') ? STAFF_TIMEZONE : 'Asia/Colombo');
    $startIso = attendance_datetime_to_immutable($start)->setTimezone($tz)->format('Y-m-d\TH:i:s');
    $endIso = attendance_datetime_to_immutable($end)->setTimezone($tz)->format('Y-m-d\TH:i:s');

    $url = attendance_hikvision_acs_event_url();

    $searchId = bin2hex(random_bytes(8));
    $maxResults = defined('HIKVISION_MAX_RESULTS_PER_CHUNK') ? max(10, min(1000, (int) HIKVISION_MAX_RESULTS_PER_CHUNK)) : 100;
    $maxPages = defined('HIKVISION_MAX_SYNC_PAGES') ? max(1, (int) HIKVISION_MAX_SYNC_PAGES) : 20000;
    $acsMajor = defined('HIKVISION_ACS_MAJOR') ? (int) HIKVISION_ACS_MAJOR : 5;

    $debug = [];
    $debug[] = 'Device: ' . HIKVISION_IP . ' (DS-K1T320MFWX / ISAPI)';
    $debug[] = 'POST ' . $url;
    $debug[] = 'Digest auth, JSON body, major=' . $acsMajor . ' (Access Control only — minor not sent)';
    $debug[] = 'Time window (Asia/Colombo): ' . $startIso . ' → ' . $endIso;
    $debug[] = 'Pagination: searchResultPosition += events returned per page; maxResults=' . $maxResults . ' per request';

    $position = 0;
    $totalMatchesKnown = null;
    $cumulativeReceived = 0;
    $db = attendance_db();
    $batch = [];

    for ($page = 0; $page < $maxPages; $page++) {
        $payload = [
            'AcsEventCond' => [
                'searchID' => $searchId,
                'searchResultPosition' => $position,
                'maxResults' => $maxResults,
                'major' => $acsMajor,
                'startTime' => $startIso,
                'endTime' => $endIso,
            ],
        ];

        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $req = attendance_hikvision_isapi_post($url, $body, $connectT, $timeoutT);
        if ($req['error'] !== null) {
            $out['error'] = $req['error'];
            $out['debug'] = $debug;
            return $out;
        }
        $httpCode = $req['http_code'];
        $response = $req['body'];
        if ($httpCode < 200 || $httpCode >= 300) {
            $out['error'] = 'HTTP ' . $httpCode . ' — ' . substr((string) $response, 0, 500);
            $out['debug'] = $debug;
            return $out;
        }

        $data = json_decode((string) $response, true);
        if (!is_array($data)) {
            $out['error'] = 'Invalid JSON from device.';
            $out['debug'] = $debug;
            return $out;
        }

        if ($totalMatchesKnown === null) {
            $totalMatchesKnown = attendance_hikvision_acs_event_total($data);
            if ($totalMatchesKnown !== null) {
                $debug[] = 'Device reports totalMatches: ' . $totalMatchesKnown;
            }
        }

        $list = attendance_extract_info_list($data);
        $listCount = count($list);

        if ($listCount === 0) {
            $debug[] = 'No more records at searchResultPosition=' . $position . ' (end of result set).';
            break;
        }

        $cumulativeReceived += $listCount;
        $debug[] = sprintf(
            'Fetched %d records at position %d (cumulative received: %d)',
            $listCount,
            $position,
            $cumulativeReceived
        );

        foreach ($list as $item) {
            if (!is_array($item)) {
                $out['skipped_bad']++;
                continue;
            }
            $emp = (string) ($item['employeeNoString'] ?? $item['EmployeeNoString'] ?? $item['employeeNo'] ?? $item['EmployeeNo'] ?? $item['employeeID'] ?? '');
            $emp = trim($emp);
            $name = (string) ($item['name'] ?? $item['Name'] ?? '');
            $dept = (string) ($item['department'] ?? $item['Department'] ?? '');
            $timeRaw = (string) ($item['time'] ?? $item['Time'] ?? '');
            $major = isset($item['major']) ? (string) $item['major'] : '';
            $minor = isset($item['minor']) ? (string) $item['minor'] : '';
            $eventType = ($major !== '' || $minor !== '') ? trim($major . '/' . $minor, '/') : (string) $acsMajor;

            $attTime = attendance_parse_device_time($timeRaw);
            if ($emp === '' || $attTime === null) {
                $out['skipped_bad']++;
                continue;
            }

            $batch[] = [
                'employee_no' => $emp,
                'staff_name' => $name,
                'department' => $dept,
                'attendance_time' => $attTime,
                'device_ip' => HIKVISION_IP,
                'event_type' => $eventType,
            ];

            $insertChunk = defined('HIKVISION_INSERT_BATCH_SIZE') ? max(1, min(1000, (int) HIKVISION_INSERT_BATCH_SIZE)) : 500;
            if (count($batch) >= $insertChunk) {
                attendance_flush_staff_batch($db, $batch, $out);
            }
        }

        attendance_flush_staff_batch($db, $batch, $out);

        $position += $listCount;

        if ($totalMatchesKnown !== null && $position >= $totalMatchesKnown) {
            $debug[] = 'Reached totalMatches from device (' . $totalMatchesKnown . ').';
            break;
        }

        if ($listCount < $maxResults) {
            $debug[] = 'Last page had fewer than maxResults (' . $listCount . ' < ' . $maxResults . ').';
            break;
        }
    }

    $out['total_received'] = $cumulativeReceived;
    $debug[] = '---';
    $debug[] = 'Total received: ' . $cumulativeReceived;
    $debug[] = 'Total inserted (new rows): ' . $out['inserted'];
    $debug[] = 'Total skipped (duplicates): ' . $out['skipped_dup'];
    $debug[] = 'Total skipped (invalid rows): ' . $out['skipped_bad'];

    $out['debug'] = $debug;
    $out['ok'] = true;
    return $out;
}

