<?php
$env = file_get_contents(dirname(__DIR__) . '/.env');
if (!preg_match('/^LMS_AUTH_TOKEN=(.+)$/m', $env, $match)) {
    fwrite(STDERR, "token missing\n");
    exit(1);
}
$token = trim($match[1]);
function call_api(string $token, array $fields): array
{
    $ch = curl_init('http://127.0.0.1/SLGTIMIS/api/lms-auth.php');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($fields),
        CURLOPT_HTTPHEADER => ['X-SLGTI-Auth: ' . $token],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
    ]);
    $raw = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $json = is_string($raw) ? json_decode($raw, true) : null;
    return [$code, is_array($json) ? $json : []];
}
foreach ([['2025aut4am001', '0'], ['2025/AUT/4AM001', '0'], ['2025aut4am001', '1']] as $pair) {
    [$code, $body] = call_api($token, ['action' => 'lookup', 'username' => $pair[0], 'extended' => $pair[1]]);
    echo 'user=' . $pair[0] . ' extended=' . $pair[1] . ' http=' . $code . ' found=' . (int) ($body['found'] ?? 0) . ' bytes=' . strlen(json_encode($body) ?: '') . PHP_EOL;
}
$hash = (string) ($body['hash'] ?? '');
$kind = 'none';
if (strlen($hash) === 64 && ctype_xdigit($hash)) {
    $kind = 'sha256';
} elseif (str_starts_with($hash, '$2')) {
    $kind = 'bcrypt';
} elseif ($hash !== '') {
    $kind = 'other';
}
echo 'lookup_http=' . $code . PHP_EOL;
echo 'found=' . (int) ($body['found'] ?? 0) . PHP_EOL;
echo 'active=' . (int) ($body['active'] ?? 0) . PHP_EOL;
echo 'hash_kind=' . $kind . PHP_EOL;
echo 'hash_length=' . strlen($hash) . PHP_EOL;
[$mapCode, $map] = call_api($token, ['action' => 'map', 'extended' => '0']);
echo 'map_http=' . $mapCode . PHP_EOL;
echo 'map_users=' . (isset($map['users']) ? count($map['users']) : 0) . PHP_EOL;
[$denied] = call_api('nope', ['action' => 'lookup', 'username' => '2022/ICT/5TE100']);
echo 'denied_http=' . $denied . PHP_EOL;
