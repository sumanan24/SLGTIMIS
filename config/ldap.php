<?php
/**
 * SIS → LDAP (EDUROAM) directory settings.
 * The bind account must be a limited search account, not the LDAP administrator.
 * Put the real host and password in .env on the server. This file has no secrets.
 */
declare(strict_types=1);

if (is_file(BASE_PATH . '/core/EnvLoader.php')) {
    require_once BASE_PATH . '/core/EnvLoader.php';
    EnvLoader::load(BASE_PATH . '/.env');
}

if (!function_exists('slgti_ldap_env')) {
    function slgti_ldap_env(string $key, string $default = ''): string
    {
        $value = getenv($key);
        if ($value === false || trim((string) $value) === '') {
            return $default;
        }
        return trim((string) $value);
    }
}

if (!defined('LDAP_SYNC_ENABLED')) {
    define('LDAP_SYNC_ENABLED', slgti_ldap_env('LDAP_SYNC_ENABLED', '1') === '1');
}
if (!defined('LDAP_HOST')) {
    define('LDAP_HOST', slgti_ldap_env('LDAP_HOST', '127.0.0.1'));
}
if (!defined('LDAP_PORT')) {
    define('LDAP_PORT', (int) slgti_ldap_env('LDAP_PORT', '389'));
}
if (!defined('LDAP_STARTTLS')) {
    define('LDAP_STARTTLS', slgti_ldap_env('LDAP_STARTTLS', '1') === '1');
}
if (!defined('LDAP_BIND_DN')) {
    define('LDAP_BIND_DN', slgti_ldap_env('LDAP_BIND_DN', ''));
}
if (!defined('LDAP_BIND_PASSWORD')) {
    define('LDAP_BIND_PASSWORD', slgti_ldap_env('LDAP_BIND_PASSWORD', ''));
}
if (!defined('LDAP_PEOPLE_DN')) {
    define('LDAP_PEOPLE_DN', slgti_ldap_env('LDAP_PEOPLE_DN', 'ou=People,dc=slgti,dc=ac,dc=lk'));
}
if (!defined('LDAP_TLS_CA')) {
    define('LDAP_TLS_CA', slgti_ldap_env('LDAP_TLS_CA', ''));
}
