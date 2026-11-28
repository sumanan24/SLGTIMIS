<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/authlib.php');

/**
 * Check the SIS password hash. The hash is not stored in Moodle.
 */
class auth_plugin_sis extends auth_plugin_base {
    public function __construct() {
        $this->authtype = 'sis';
        $this->config = get_config('auth_sis');
    }

    public function user_login($username, $password) {
        $username = trim((string) $username);
        $password = (string) $password;
        if ($username === '' || $password === '') {
            return false;
        }
        $cfg = $this->settings();
        if ($cfg === null) {
            return false;
        }
        $payload = json_encode([
            'action' => 'lookup',
            'username' => $username,
            'extended' => !empty($GLOBALS['CFG']->extendedusernamechars) ? '1' : '0',
        ]);
        $body = $this->post($cfg, $payload);
        if (!is_array($body) || (int) ($body['found'] ?? 0) !== 1 || (int) ($body['active'] ?? 0) !== 1) {
            return false;
        }
        $hash = (string) ($body['hash'] ?? '');
        if (strlen($hash) === 64 && ctype_xdigit($hash)) {
            return hash_equals(strtolower($hash), hash('sha256', $password));
        }
        return $hash !== '' && password_verify($password, $hash);
    }

    public function prevent_local_passwords() {
        return true;
    }

    public function is_internal() {
        return false;
    }

    public function can_change_password() {
        return false;
    }

    public function can_reset_password() {
        return false;
    }

    /**
     * @return array{url:string,token:string}|null
     */
    private function settings(): ?array {
        $path = '/usr/local/etc/slgti-sis-auth.php';
        if (!is_file($path)) {
            return null;
        }
        $cfg = require $path;
        if (!is_array($cfg) || empty($cfg['url']) || empty($cfg['token'])) {
            return null;
        }
        return ['url' => (string) $cfg['url'], 'token' => (string) $cfg['token']];
    }

    /**
     * @param array{url:string,token:string} $cfg
     */
    private function post(array $cfg, string $payload): ?array {
        $ch = curl_init($cfg['url']);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-SLGTI-Auth: ' . $cfg['token'],
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
        ]);
        $raw = curl_exec($ch);
        curl_close($ch);
        if (!is_string($raw) || $raw === '') {
            return null;
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }
}
