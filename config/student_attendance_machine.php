<?php
/**
 * Student fingerprint attendance machines (Hikvision ISAPI Digest).
 *
 * MAIN enrolls biometrics once; readers receive credentials via ISAPI sync.
 *
 * Overrides (prefer secrets here on shared hosts):
 *   - .env  STUDENT_HIKVISION_*
 *   - config/student_attendance_machine.local.php  (gitignored)
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/core/EnvLoader.php';
EnvLoader::load(dirname(__DIR__) . '/.env');

$local = dirname(__DIR__) . '/config/student_attendance_machine.local.php';
$localCfg = [];
if (is_file($local)) {
    $tmp = require $local;
    if (is_array($tmp)) {
        $localCfg = $tmp;
    }
}

$defaults = [
    'host' => '172.16.0.26',
    'username' => 'admin',
    'password' => '',
    'ssl' => false,
    'port' => 0,
    'timeout' => 60,
    'timezone' => 'Asia/Colombo',
    // Comma-separated reader IPs (synced after enroll on main)
    'reader_hosts' => ['172.16.0.29', '172.16.0.28', '172.16.0.27'],
];

$host = trim((string) EnvLoader::get('STUDENT_HIKVISION_IP', $localCfg['host'] ?? $defaults['host']));
$user = trim((string) EnvLoader::get('STUDENT_HIKVISION_USER', $localCfg['username'] ?? $defaults['username']));
// Prefer non-empty password: .env first, then local.php (gitignored), never commit secrets
$passEnv = trim((string) (EnvLoader::get('STUDENT_HIKVISION_PASS', '') ?? ''));
$passLocal = trim((string) ($localCfg['password'] ?? ''));
$pass = $passEnv !== '' ? $passEnv : $passLocal;
$passwordSource = $passEnv !== '' ? 'env' : ($passLocal !== '' ? 'local' : 'none');
$https = EnvLoader::get(
    'STUDENT_HIKVISION_USE_HTTPS',
    isset($localCfg['ssl']) ? (!empty($localCfg['ssl']) ? '1' : '0') : ($defaults['ssl'] ? '1' : '0')
);
$port = (int) EnvLoader::get(
    'STUDENT_HIKVISION_HTTP_PORT',
    (string) ($localCfg['port'] ?? $defaults['port'])
);
$timeout = (int) EnvLoader::get(
    'STUDENT_HIKVISION_TIMEOUT',
    (string) ($localCfg['timeout'] ?? $defaults['timeout'])
);
$tz = EnvLoader::get(
    'STUDENT_ATTENDANCE_TIMEZONE',
    $localCfg['timezone'] ?? $defaults['timezone']
);

$readerHostsRaw = EnvLoader::get(
    'STUDENT_HIKVISION_READER_IPS',
    isset($localCfg['reader_hosts'])
        ? (is_array($localCfg['reader_hosts'])
            ? implode(',', $localCfg['reader_hosts'])
            : (string) $localCfg['reader_hosts'])
        : implode(',', $defaults['reader_hosts'])
);

$parseHosts = static function (string $raw): array {
    $out = [];
    foreach (preg_split('/[\s,;]+/', trim($raw)) ?: [] as $ip) {
        $ip = trim((string) $ip);
        if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP)) {
            continue;
        }
        $out[$ip] = $ip;
    }
    return array_values($out);
};

$readerHosts = $parseHosts((string) $readerHostsRaw);
// Never list main as a reader
$readerHosts = array_values(array_filter(
    $readerHosts,
    static fn (string $ip): bool => $ip !== $host
));

$ssl = in_array(strtolower((string) $https), ['1', 'true', 'yes'], true);
$portVal = $port > 0 ? $port : 0;
$timeoutVal = $timeout > 0 ? $timeout : 60;
$password = (string) $pass;

$deviceCommon = [
    'username' => (string) $user,
    'password' => $password,
    'ssl' => $ssl,
    'port' => $portVal,
    'timeout' => $timeoutVal,
];

$devices = [];
if ($host !== '') {
    $devices[] = array_merge($deviceCommon, [
        'host' => $host,
        'role' => 'main',
        'label' => 'Main (enrollment)',
    ]);
}
foreach ($readerHosts as $i => $rip) {
    $devices[] = array_merge($deviceCommon, [
        'host' => $rip,
        'role' => 'reader',
        'label' => 'Reader ' . ($i + 1),
    ]);
}

return [
    'host' => $host,
    'username' => (string) $user,
    'password' => $password,
    'ssl' => $ssl,
    'port' => $portVal,
    'timeout' => $timeoutVal,
    'timezone' => (string) $tz,
    'acs_major' => 5,
    'acs_minors' => [0, 38, 75],
    'max_results_per_page' => 2000,
    'max_pages' => 5000,
    'reader_hosts' => $readerHosts,
    /** @var list<array{host:string,role:string,label:string,username:string,password:string,ssl:bool,port:int,timeout:int}> */
    'devices' => $devices,
    'configured' => ($host !== '' && $password !== ''),
    'password_source' => $passwordSource,
    'env_file_present' => EnvLoader::envFileExists(),
    'local_file_present' => is_file($local),
];
