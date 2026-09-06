<?php
/**
 * Hikvision LAN devices — single config for this MVC app.
 * Controllers must not hardcode IPs/passwords elsewhere — require this file only.
 *
 * Optional override: config/hikvision.local.php (gitignored) if you need per-server secrets.
 */
declare(strict_types=1);

$local = __DIR__ . '/hikvision.local.php';
if (is_file($local)) {
    require_once $local;
}

if (!defined('HIKVISION_USER')) {
    define('HIKVISION_USER', 'admin');
}
if (!defined('HIKVISION_PASS')) {
    define('HIKVISION_PASS', 'TCI@itgls0206');
}

if (!defined('MAIN_MACHINE_IP')) {
    define('MAIN_MACHINE_IP', '172.16.0.26');
}
if (!defined('READER1_IP')) {
    define('READER1_IP', '172.16.0.29');
}
if (!defined('READER2_IP')) {
    define('READER2_IP', '172.16.0.28');
}
if (!defined('READER3_IP')) {
    define('READER3_IP', '172.16.0.27');
}

if (!defined('HIKVISION_HTTP_PORT')) {
    define('HIKVISION_HTTP_PORT', 80);
}
if (!defined('HIKVISION_USE_HTTPS')) {
    define('HIKVISION_USE_HTTPS', false);
}
if (!defined('HIKVISION_TIMEOUT')) {
    define('HIKVISION_TIMEOUT', 8);
}

/** Effective admin password (never require manual UI entry). */
function hikvision_pass(): string {
    $p = defined('HIKVISION_PASS') ? trim((string) HIKVISION_PASS) : '';
    if ($p !== '') {
        return $p;
    }
    // Built-in default so production works after git deploy without typing a password
    return 'TCI@itgls0206';
}

function hikvision_pass_configured(): bool {
    return hikvision_pass() !== '';
}

/**
 * @return list<array{key:string,label:string,ip:string,role:string}>
 */
function hikvision_devices(): array {
    return [
        ['key' => 'main', 'label' => 'MAIN MACHINE', 'ip' => MAIN_MACHINE_IP, 'role' => 'main'],
        ['key' => 'reader1', 'label' => 'READER 1', 'ip' => READER1_IP, 'role' => 'reader'],
        ['key' => 'reader2', 'label' => 'READER 2', 'ip' => READER2_IP, 'role' => 'reader'],
        ['key' => 'reader3', 'label' => 'READER 3', 'ip' => READER3_IP, 'role' => 'reader'],
    ];
}

/**
 * @return array{key:string,label:string,ip:string,role:string}|null
 */
function hikvision_device_by_key(string $key): ?array {
    $key = strtolower(trim($key));
    foreach (hikvision_devices() as $d) {
        if ($d['key'] === $key) {
            return $d;
        }
    }
    return null;
}
