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
    $formats = [
        'Y-m-d\TH:i:s',
        'Y-m-d\TH:i:sP',
        'Y-m-d H:i:s',
        'Y/m/d H:i:s',
        'Y-m-d\TH:i:s.v',
    ];
    foreach ($formats as $f) {
        $dt = DateTime::createFromFormat($f, $raw);
        if ($dt instanceof DateTime) {
            return $dt->format('Y-m-d H:i:s');
        }
    }
    $ts = strtotime($raw);
    if ($ts !== false) {
        return date('Y-m-d H:i:s', $ts);
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
    $timeoutT = defined('HIKVISION_CURL_TIMEOUT') ? (int) HIKVISION_CURL_TIMEOUT : 35;

    try {
        return attendance_run_hikvision_sync_inner($start, $end, $connectT, $timeoutT);
    } catch (Throwable $e) {
        $out['error'] = 'Sync error: ' . $e->getMessage();
        return $out;
    }
}

/**
 * @internal
 */
function attendance_run_hikvision_sync_inner(DateTimeInterface $start, DateTimeInterface $end, int $connectT, int $timeoutT): array
{
    $out = [
        'ok' => false,
        'inserted' => 0,
        'skipped_dup' => 0,
        'skipped_bad' => 0,
        'error' => null,
    ];

    if (!function_exists('curl_init')) {
        $out['error'] = 'PHP cURL extension is not enabled on this server.';
        return $out;
    }

    $startIso = $start->format('Y-m-d\TH:i:s');
    $endIso = $end->format('Y-m-d\TH:i:s');

    $scheme = HIKVISION_USE_HTTPS ? 'https' : 'http';
    $url = $scheme . '://' . HIKVISION_IP . '/ISAPI/AccessControl/AcsEvent?format=json';

    $position = 0;
    $maxResults = 100;
    $db = attendance_db();
    $insertSql = 'INSERT IGNORE INTO staff_attendance (employee_no, staff_name, department, attendance_time, device_ip, event_type)
                  VALUES (?, ?, ?, ?, ?, ?)';
    $ins = $db->prepare($insertSql);

    do {
        $payload = [
            'AcsEventCond' => [
                'searchID' => bin2hex(random_bytes(8)),
                'searchResultPosition' => $position,
                'maxResults' => $maxResults,
                'major' => 0,
                'minor' => 0,
                'startTime' => $startIso,
                'endTime' => $endIso,
            ],
        ];

        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
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
        if (HIKVISION_USE_HTTPS) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        }

        $response = curl_exec($ch);
        $curlErr = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            $out['error'] = 'cURL error: ' . $curlErr;
            return $out;
        }
        if ($httpCode < 200 || $httpCode >= 300) {
            $out['error'] = 'HTTP ' . $httpCode . ' — ' . substr((string) $response, 0, 500);
            return $out;
        }

        $data = json_decode((string) $response, true);
        if (!is_array($data)) {
            $out['error'] = 'Invalid JSON from device.';
            return $out;
        }

        $list = attendance_extract_info_list($data);
        if ($list === []) {
            break;
        }

        $batchCount = 0;
        foreach ($list as $item) {
            if (!is_array($item)) {
                $out['skipped_bad']++;
                continue;
            }
            $emp = (string) ($item['employeeNoString'] ?? $item['EmployeeNoString'] ?? $item['employeeNo'] ?? '');
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
            $batchCount++;
        }

        $position += $batchCount;
        if ($batchCount < $maxResults) {
            break;
        }
    } while (true);

    $out['ok'] = true;
    return $out;
}

