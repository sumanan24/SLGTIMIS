<?php
/**
 * Student fingerprint attendance machine settings.
 * Credentials come from .env (or environment) — never from the UI.
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

$host = EnvLoader::get('STUDENT_HIKVISION_IP', $localCfg['host'] ?? '172.16.0.26');
$user = EnvLoader::get('STUDENT_HIKVISION_USER', $localCfg['username'] ?? 'admin');
$pass = EnvLoader::get('STUDENT_HIKVISION_PASS', $localCfg['password'] ?? '');
$https = EnvLoader::get('STUDENT_HIKVISION_USE_HTTPS', !empty($localCfg['ssl']) ? '1' : '0');
$port = (int) EnvLoader::get('STUDENT_HIKVISION_HTTP_PORT', (string) ($localCfg['port'] ?? '0'));
$timeout = (int) EnvLoader::get('STUDENT_HIKVISION_TIMEOUT', (string) ($localCfg['timeout'] ?? '60'));
$tz = EnvLoader::get('STUDENT_ATTENDANCE_TIMEZONE', $localCfg['timezone'] ?? 'Asia/Colombo');

return [
    'host' => (string) $host,
    'username' => (string) $user,
    'password' => (string) $pass,
    'ssl' => in_array(strtolower((string) $https), ['1', 'true', 'yes'], true),
    'port' => $port > 0 ? $port : 0,
    'timeout' => $timeout > 0 ? $timeout : 60,
    'timezone' => (string) $tz,
    'acs_major' => 5,
    // DS-K1T343: 0 auth, 38 face auth, 75 face+pic — include employeeNoString
    'acs_minors' => [0, 38, 75],
    'max_results_per_page' => 2000,
    'max_pages' => 5000,
];
