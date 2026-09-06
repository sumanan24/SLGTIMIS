<?php
/**
 * Student fingerprint attendance machines (Hikvision ISAPI Digest over LAN).
 *
 * MAIN enrolls biometrics once; readers receive credentials via ISAPI sync.
 * Devices are reached by private IPs only — no Internet dependency.
 *
 * Secrets (never commit):
 *   - .env  STUDENT_HIKVISION_* / HIKVISION_*
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

$envFirst = static function (array $keys, ?string $default = null): ?string {
    foreach ($keys as $key) {
        $v = EnvLoader::get((string) $key, null);
        if ($v !== null && trim((string) $v) !== '') {
            return trim((string) $v);
        }
    }
    return $default;
};

$defaults = [
    'host' => '172.16.0.26',
    'username' => 'admin',
    'password' => '',
    'ssl' => false,
    'port' => 0,
    'timeout' => 20,
    'timezone' => 'Asia/Colombo',
    'reader_hosts' => ['172.16.0.29', '172.16.0.28', '172.16.0.27'],
];

$host = (string) ($envFirst(
    ['HIKVISION_MAIN_IP', 'STUDENT_HIKVISION_IP'],
    (string) ($localCfg['host'] ?? $defaults['host'])
) ?? $defaults['host']);

$user = (string) ($envFirst(
    ['HIKVISION_USER', 'STUDENT_HIKVISION_USER'],
    (string) ($localCfg['username'] ?? $defaults['username'])
) ?? $defaults['username']);

// Prefer local.php (server UI) then .env aliases — never commit secrets
$passLocal = trim((string) ($localCfg['password'] ?? ''));
$passEnv = (string) ($envFirst(['HIKVISION_PASS', 'STUDENT_HIKVISION_PASS'], '') ?? '');
$pass = $passLocal !== '' ? $passLocal : $passEnv;
$passwordSource = $passLocal !== '' ? 'local' : ($passEnv !== '' ? 'env' : 'none');

$https = $envFirst(
    ['STUDENT_HIKVISION_USE_HTTPS', 'HIKVISION_USE_HTTPS'],
    isset($localCfg['ssl']) ? (!empty($localCfg['ssl']) ? '1' : '0') : ($defaults['ssl'] ? '1' : '0')
);
$port = (int) ($envFirst(
    ['STUDENT_HIKVISION_HTTP_PORT', 'HIKVISION_HTTP_PORT'],
    (string) ($localCfg['port'] ?? $defaults['port'])
) ?? $defaults['port']);
$timeout = (int) ($envFirst(
    ['STUDENT_HIKVISION_TIMEOUT', 'HIKVISION_TIMEOUT'],
    (string) ($localCfg['timeout'] ?? $defaults['timeout'])
) ?? $defaults['timeout']);
$tz = $envFirst(
    ['STUDENT_ATTENDANCE_TIMEZONE'],
    (string) ($localCfg['timezone'] ?? $defaults['timezone'])
) ?? $defaults['timezone'];

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

$readerFromAliases = [];
foreach ([1, 2, 3, 4, 5, 6] as $n) {
    $rip = $envFirst(['HIKVISION_READER_' . $n . '_IP'], null);
    if ($rip !== null && $rip !== '') {
        $readerFromAliases[] = $rip;
    }
}

if ($readerFromAliases !== []) {
    $readerHosts = $parseHosts(implode(',', $readerFromAliases));
} else {
    $readerHostsRaw = $envFirst(
        ['STUDENT_HIKVISION_READER_IPS', 'HIKVISION_READER_IPS'],
        isset($localCfg['reader_hosts'])
            ? (is_array($localCfg['reader_hosts'])
                ? implode(',', $localCfg['reader_hosts'])
                : (string) $localCfg['reader_hosts'])
            : implode(',', $defaults['reader_hosts'])
    );
    $readerHosts = $parseHosts((string) ($readerHostsRaw ?? ''));
}

$readerHosts = array_values(array_filter(
    $readerHosts,
    static fn (string $ip): bool => $ip !== $host
));

$ssl = in_array(strtolower((string) $https), ['1', 'true', 'yes'], true);
$portVal = $port > 0 ? $port : 0;
$timeoutVal = $timeout > 0 ? $timeout : 20;
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

$sisLanIp = (string) ($envFirst(['SIS_LAN_IP', 'APP_LAN_IP'], '172.16.1.245') ?? '172.16.1.245');
$sisPublicHost = (string) ($envFirst(['SIS_PUBLIC_HOST'], 'sis.slgti.ac.lk') ?? 'sis.slgti.ac.lk');

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
    // SIS web app on LAN (not a Hikvision device)
    'sis_lan_ip' => $sisLanIp,
    'sis_public_host' => $sisPublicHost,
    'sis_lan_url' => 'http://' . $sisLanIp,
];
