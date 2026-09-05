<?php
/**
 * Student fingerprint attendance machine (Hikvision DS-K1T343).
 *
 * Deployable defaults (same pattern as config/hikvision.php / staff_attendance/config.php).
 * Optional overrides (prefer for secrets on shared hosts):
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

// Defaults match campus student terminal — override via .env / local.php when needed.
$defaults = [
    'host' => '172.16.0.26',
    'username' => 'admin',
    'password' => 'TCI@itgls0206',
    'ssl' => false,
    'port' => 0,
    'timeout' => 60,
    'timezone' => 'Asia/Colombo',
];

$host = EnvLoader::get('STUDENT_HIKVISION_IP', $localCfg['host'] ?? $defaults['host']);
$user = EnvLoader::get('STUDENT_HIKVISION_USER', $localCfg['username'] ?? $defaults['username']);
$pass = EnvLoader::get('STUDENT_HIKVISION_PASS', $localCfg['password'] ?? $defaults['password']);
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

$password = (string) $pass;
$hostStr = trim((string) $host);

return [
    'host' => $hostStr,
    'username' => (string) $user,
    'password' => $password,
    'ssl' => in_array(strtolower((string) $https), ['1', 'true', 'yes'], true),
    'port' => $port > 0 ? $port : 0,
    'timeout' => $timeout > 0 ? $timeout : 60,
    'timezone' => (string) $tz,
    'acs_major' => 5,
    // DS-K1T343: 0 auth, 38 face auth, 75 face+pic — include employeeNoString
    'acs_minors' => [0, 38, 75],
    'max_results_per_page' => 2000,
    'max_pages' => 5000,
    // Safe diagnostics for UI (no secrets)
    'configured' => ($hostStr !== '' && $password !== ''),
    'env_file_present' => EnvLoader::envFileExists(),
];
