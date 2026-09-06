<?php
/**
 * Student attendance devices — thin wrapper over config/hikvision.php constants.
 * Controllers must not hardcode IPs/passwords.
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/hikvision.php';

$host = MAIN_MACHINE_IP;
$user = HIKVISION_USER;
$password = HIKVISION_PASS;
$ssl = (bool) HIKVISION_USE_HTTPS;
$portVal = (int) HIKVISION_HTTP_PORT;
$timeoutVal = (int) HIKVISION_TIMEOUT;
$readerHosts = [READER1_IP, READER2_IP, READER3_IP];

$deviceCommon = [
    'username' => $user,
    'password' => $password,
    'ssl' => $ssl,
    'port' => $portVal > 0 ? $portVal : 0,
    'timeout' => $timeoutVal > 0 ? $timeoutVal : 20,
];

$devices = [];
foreach (hikvision_devices() as $d) {
    $devices[] = array_merge($deviceCommon, [
        'host' => $d['ip'],
        'role' => $d['role'],
        'label' => $d['label'],
    ]);
}

return [
    'host' => $host,
    'username' => $user,
    'password' => $password,
    'ssl' => $ssl,
    'port' => $portVal > 0 ? $portVal : 0,
    'timeout' => $timeoutVal > 0 ? $timeoutVal : 20,
    'timezone' => 'Asia/Colombo',
    'acs_major' => 5,
    'acs_minors' => [0, 38, 75],
    'max_results_per_page' => 2000,
    'max_pages' => 5000,
    'reader_hosts' => $readerHosts,
    'devices' => $devices,
    'configured' => ($host !== '' && trim($password) !== ''),
    'password_source' => 'hikvision.php',
    'env_file_present' => false,
    'local_file_present' => is_file(dirname(__DIR__) . '/config/hikvision.local.php'),
    'sis_lan_ip' => defined('SIS_LAN_IP') ? SIS_LAN_IP : '172.16.1.245',
    'sis_public_host' => defined('SIS_PUBLIC_HOST') ? SIS_PUBLIC_HOST : 'sis.slgti.ac.lk',
    'sis_lan_url' => defined('SIS_LAN_URL') ? SIS_LAN_URL : 'http://172.16.1.245',
];
