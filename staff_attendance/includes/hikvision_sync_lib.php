<?php
declare(strict_types=1);

/**
 * Hikvision ISAPI AcsEvent pull + insert into staff_attendance.
 */

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
 * Pull events from device and INSERT IGNORE into staff_attendance.
 *
 * @return array{ok: bool, inserted: int, skipped_dup: int, skipped_bad: int, error: ?string}
 */
function attendance_run_hikvision_sync(DateTimeInterface $start, DateTimeInterface $end): array
{
    $out = [
        'ok' => false,
        'inserted' => 0,
        'skipped_dup' => 0,
        'skipped_bad' => 0,
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
 * POST to Hikvision ISAPI: prefer ext-curl; otherwise HTTP Digest via PHP streams (allow_url_fopen).
 *
 * @return array{http_code: int, body: string, error: ?string}
 */
function attendance_hikvision_isapi_post(string $url, string $body, int $connectT, int $timeoutT): array
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPAUTH => CURLAUTH_DIGEST,
            CURLOPT_USERPWD => HIKVISION_USER . ':' . HIKVISION_PASS,
            CURLOPT_CONNECTTIMEOUT => $connectT,
            CURLOPT_TIMEOUT => $timeoutT,
        ]);
        if (strpos($url, 'https:') === 0) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        }
        $response = curl_exec($ch);
        $curlErr = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($response === false) {
            return ['http_code' => 0, 'body' => '', 'error' => 'cURL error: ' . $curlErr];
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
        'error' => null,
    ];

    $tz = new DateTimeZone(defined('STAFF_TIMEZONE') ? STAFF_TIMEZONE : 'Asia/Colombo');
    $startIso = DateTimeImmutable::createFromInterface($start)->setTimezone($tz)->format('Y-m-d\TH:i:s');
    $endIso = DateTimeImmutable::createFromInterface($end)->setTimezone($tz)->format('Y-m-d\TH:i:s');

    $scheme = HIKVISION_USE_HTTPS ? 'https' : 'http';
    $url = $scheme . '://' . HIKVISION_IP . '/ISAPI/AccessControl/AcsEvent?format=json';

    /** One search session for all chunks — regenerating searchID each request breaks pagination. */
    $searchId = bin2hex(random_bytes(8));

    $maxResults = defined('HIKVISION_MAX_RESULTS_PER_CHUNK') ? max(10, min(2000, (int) HIKVISION_MAX_RESULTS_PER_CHUNK)) : 500;
    $maxPages = defined('HIKVISION_MAX_SYNC_PAGES') ? max(1, (int) HIKVISION_MAX_SYNC_PAGES) : 20000;

    $position = 0;
    $totalMatchesKnown = null;
    $db = attendance_db();
    $insertSql = 'INSERT IGNORE INTO staff_attendance (employee_no, staff_name, department, attendance_time, device_ip, event_type)
                  VALUES (?, ?, ?, ?, ?, ?)';
    $ins = $db->prepare($insertSql);

    for ($page = 0; $page < $maxPages; $page++) {
        $payload = [
            'AcsEventCond' => [
                'searchID' => $searchId,
                'searchResultPosition' => $position,
                'maxResults' => $maxResults,
                'major' => 0,
                'minor' => 0,
                'startTime' => $startIso,
                'endTime' => $endIso,
            ],
        ];

        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $req = attendance_hikvision_isapi_post($url, $body, $connectT, $timeoutT);
        if ($req['error'] !== null) {
            $out['error'] = $req['error'];
            return $out;
        }
        $httpCode = $req['http_code'];
        $response = $req['body'];
        if ($httpCode < 200 || $httpCode >= 300) {
            $out['error'] = 'HTTP ' . $httpCode . ' — ' . substr((string) $response, 0, 500);
            return $out;
        }

        $data = json_decode((string) $response, true);
        if (!is_array($data)) {
            $out['error'] = 'Invalid JSON from device.';
            return $out;
        }

        if ($totalMatchesKnown === null) {
            $totalMatchesKnown = attendance_hikvision_acs_event_total($data);
        }

        $list = attendance_extract_info_list($data);
        $listCount = count($list);

        if ($listCount === 0) {
            break;
        }

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
            $eventType = ($major !== '' || $minor !== '') ? trim($major . '/' . $minor, '/') : 'event';

            $attTime = attendance_parse_device_time($timeRaw);
            if ($emp === '' || $attTime === null) {
                $out['skipped_bad']++;
                continue;
            }

            $deviceIp = HIKVISION_IP;
            $ins->bind_param('ssssss', $emp, $name, $dept, $attTime, $deviceIp, $eventType);
            $ins->execute();
            $aff = $ins->affected_rows;
            if ($aff === 1) {
                $out['inserted']++;
            } else {
                $out['skipped_dup']++;
            }
        }

        /** Next page offset must match how many events the device returned (not only valid DB rows). */
        $position += $listCount;

        if ($totalMatchesKnown !== null && $position >= $totalMatchesKnown) {
            break;
        }

        if ($listCount < $maxResults) {
            break;
        }
    }

    $out['ok'] = true;
    return $out;
}

