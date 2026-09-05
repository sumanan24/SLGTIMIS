<?php
declare(strict_types=1);

/**
 * HTTP Digest client using only PHP streams (no ext-curl).
 * Used when Hikvision sync runs on hosts without php-curl.
 */

/**
 * @return array{status: int, headers: array<int, string>, body: string, error: ?string}
 */
function attendance_stream_http_request(string $url, string $method, array $headerLines, string $body, int $timeoutSec, bool $verifySsl): array
{
    $http_response_header = [];
    $opts = [
        'http' => [
            'method' => $method,
            'header' => implode("\r\n", $headerLines) . "\r\n",
            'content' => $body,
            'timeout' => $timeoutSec,
            'ignore_errors' => true,
        ],
    ];
    if (strpos($url, 'https:') === 0) {
        $opts['ssl'] = [
            'verify_peer' => $verifySsl,
            'verify_peer_name' => $verifySsl,
        ];
    }
    $ctx = stream_context_create($opts);
    $data = @file_get_contents($url, false, $ctx);
    if ($data === false) {
        $err = error_get_last();
        $msg = $err['message'] ?? 'HTTP request failed';
        return ['status' => 0, 'headers' => [], 'body' => '', 'error' => $msg];
    }
    $status = 0;
    if (isset($http_response_header[0]) && preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $m)) {
        $status = (int) $m[1];
    }
    return ['status' => $status, 'headers' => $http_response_header, 'body' => $data, 'error' => null];
}

/**
 * @return array<string, string>
 */
function attendance_parse_digest_params(string $digestHeader): array
{
    $out = [];
    if (!preg_match('/Digest\s+(.*)/is', $digestHeader, $m)) {
        return [];
    }
    $rest = $m[1];
    if (preg_match_all('/(\w+)\s*=\s*(?:"([^"]*)"|([^\s,]+))/', $rest, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            // Quoted branch sets [2]; unquoted sets [3]. When one branch matches, the other
            // capture may be omitted — never access [3] without ?? (fixes "Undefined offset: 3").
            $val = ($match[2] ?? '') !== '' ? ($match[2] ?? '') : ($match[3] ?? '');
            $out[$match[1]] = $val;
        }
    }
    return $out;
}

function attendance_build_digest_authorization(
    string $method,
    string $requestUri,
    string $user,
    string $pass,
    string $realm,
    string $nonce,
    string $qopList,
    string $opaque
): string {
    $qop = (stripos($qopList, 'auth') !== false) ? 'auth' : 'auth';
    $nc = '00000001';
    $cnonce = bin2hex(random_bytes(8));
    $ha1 = md5($user . ':' . $realm . ':' . $pass);
    $ha2 = md5($method . ':' . $requestUri);
    $response = md5($ha1 . ':' . $nonce . ':' . $nc . ':' . $cnonce . ':' . $qop . ':' . $ha2);

    $parts = [
        'username="' . str_replace(['\\', '"'], ['\\\\', '\\"'], $user) . '"',
        'realm="' . $realm . '"',
        'nonce="' . $nonce . '"',
        'uri="' . $requestUri . '"',
        'response="' . $response . '"',
        'qop=' . $qop,
        'nc=' . $nc,
        'cnonce="' . $cnonce . '"',
    ];
    if ($opaque !== '') {
        $parts[] = 'opaque="' . $opaque . '"';
    }
    return 'Digest ' . implode(', ', $parts);
}

/**
 * POST JSON with HTTP Digest (two-step if server returns 401).
 *
 * @return array{ok: bool, http_code: int, body: string, error: ?string}
 */
function attendance_digest_post_json(string $url, string $jsonBody, string $user, string $pass, int $timeoutSec, bool $verifySsl = false): array
{
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return ['ok' => false, 'http_code' => 0, 'body' => '', 'error' => 'Invalid URL'];
    }

    $baseHeaders = [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($jsonBody),
    ];

    $first = attendance_stream_http_request($url, 'POST', $baseHeaders, $jsonBody, $timeoutSec, $verifySsl);
    if ($first['error'] !== null) {
        return ['ok' => false, 'http_code' => 0, 'body' => '', 'error' => $first['error']];
    }

    if ($first['status'] >= 200 && $first['status'] < 300) {
        return ['ok' => true, 'http_code' => $first['status'], 'body' => $first['body'], 'error' => null];
    }

    if ($first['status'] !== 401) {
        return [
            'ok' => false,
            'http_code' => $first['status'],
            'body' => $first['body'],
            'error' => 'HTTP ' . $first['status'],
        ];
    }

    $www = '';
    foreach ($first['headers'] as $h) {
        if (stripos($h, 'WWW-Authenticate:') === 0) {
            $www = trim(substr($h, strlen('WWW-Authenticate:')));
            break;
        }
    }
    if ($www === '' || stripos($www, 'Digest') !== 0) {
        return ['ok' => false, 'http_code' => 401, 'body' => $first['body'], 'error' => 'Device did not offer Digest authentication.'];
    }

    $parsed = attendance_parse_digest_params($www);
    $realm = $parsed['realm'] ?? '';
    $nonce = $parsed['nonce'] ?? '';
    $qopList = $parsed['qop'] ?? 'auth';
    $opaque = $parsed['opaque'] ?? '';

    if ($realm === '' || $nonce === '') {
        return ['ok' => false, 'http_code' => 401, 'body' => '', 'error' => 'Incomplete Digest challenge from device.'];
    }

    $p = parse_url($url);
    if ($p === false || empty($p['host'])) {
        return ['ok' => false, 'http_code' => 0, 'body' => '', 'error' => 'Could not parse device URL.'];
    }
    $uri = ($p['path'] ?? '/') . (isset($p['query']) ? '?' . $p['query'] : '');

    $auth = attendance_build_digest_authorization('POST', $uri, $user, $pass, $realm, $nonce, $qopList, $opaque);

    $secondHeaders = array_merge($baseHeaders, ['Authorization: ' . $auth]);
    $second = attendance_stream_http_request($url, 'POST', $secondHeaders, $jsonBody, $timeoutSec, $verifySsl);
    if ($second['error'] !== null) {
        return ['ok' => false, 'http_code' => 0, 'body' => '', 'error' => $second['error']];
    }

    if ($second['status'] >= 200 && $second['status'] < 300) {
        return ['ok' => true, 'http_code' => $second['status'], 'body' => $second['body'], 'error' => null];
    }

    return [
        'ok' => false,
        'http_code' => $second['status'],
        'body' => $second['body'],
        'error' => 'HTTP ' . $second['status'] . ' — ' . substr($second['body'], 0, 400),
    ];
}

/**
 * GET/POST with HTTP Digest via PHP streams (no ext-curl).
 *
 * @return array{ok: bool, http_code: int, body: string, error: ?string}
 */
function attendance_digest_request(
    string $method,
    string $url,
    ?string $body,
    ?string $contentType,
    string $user,
    string $pass,
    int $timeoutSec,
    bool $verifySsl = false
): array {
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return ['ok' => false, 'http_code' => 0, 'body' => '', 'error' => 'Invalid URL'];
    }

    $method = strtoupper($method);
    $payload = $body ?? '';
    $baseHeaders = [];
    if ($contentType !== null && $contentType !== '') {
        $baseHeaders[] = 'Content-Type: ' . $contentType;
    }
    if ($payload !== '') {
        $baseHeaders[] = 'Content-Length: ' . strlen($payload);
    }

    $first = attendance_stream_http_request($url, $method, $baseHeaders, $payload, $timeoutSec, $verifySsl);
    if ($first['error'] !== null) {
        return ['ok' => false, 'http_code' => 0, 'body' => '', 'error' => $first['error']];
    }

    if ($first['status'] >= 200 && $first['status'] < 300) {
        return ['ok' => true, 'http_code' => $first['status'], 'body' => $first['body'], 'error' => null];
    }

    if ($first['status'] !== 401) {
        return [
            'ok' => $first['status'] >= 200 && $first['status'] < 300,
            'http_code' => $first['status'],
            'body' => $first['body'],
            'error' => null,
        ];
    }

    $www = '';
    foreach ($first['headers'] as $h) {
        if (stripos($h, 'WWW-Authenticate:') === 0) {
            $www = trim(substr($h, strlen('WWW-Authenticate:')));
            break;
        }
    }
    if ($www === '' || stripos($www, 'Digest') !== 0) {
        return ['ok' => false, 'http_code' => 401, 'body' => $first['body'], 'error' => null];
    }

    $parsed = attendance_parse_digest_params($www);
    $realm = $parsed['realm'] ?? '';
    $nonce = $parsed['nonce'] ?? '';
    $qopList = $parsed['qop'] ?? 'auth';
    $opaque = $parsed['opaque'] ?? '';

    if ($realm === '' || $nonce === '') {
        return ['ok' => false, 'http_code' => 401, 'body' => $first['body'], 'error' => 'Incomplete Digest challenge from device.'];
    }

    $p = parse_url($url);
    if ($p === false || empty($p['host'])) {
        return ['ok' => false, 'http_code' => 0, 'body' => '', 'error' => 'Could not parse device URL.'];
    }
    $uri = ($p['path'] ?? '/') . (isset($p['query']) ? '?' . $p['query'] : '');

    $auth = attendance_build_digest_authorization($method, $uri, $user, $pass, $realm, $nonce, $qopList, $opaque);
    $secondHeaders = array_merge($baseHeaders, ['Authorization: ' . $auth]);
    $second = attendance_stream_http_request($url, $method, $secondHeaders, $payload, $timeoutSec, $verifySsl);
    if ($second['error'] !== null) {
        return ['ok' => false, 'http_code' => 0, 'body' => '', 'error' => $second['error']];
    }

    return [
        'ok' => $second['status'] >= 200 && $second['status'] < 300,
        'http_code' => $second['status'],
        'body' => $second['body'],
        'error' => null,
    ];
}
