<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Turn a typed SIS registration number or NIC into the LMS username.
 */
function auth_sis_canonical_username(string $typed): string {
    global $CFG;

    $typed = trim(core_text::strtolower($typed));
    if ($typed === '') {
        return '';
    }
    $path = '/usr/local/etc/slgti-sis-auth.php';
    if (!is_file($path)) {
        return '';
    }
    $cfg = require $path;
    if (!is_array($cfg) || empty($cfg['url']) || empty($cfg['token'])) {
        return '';
    }
    $payload = json_encode([
        'action' => 'resolve',
        'username' => $typed,
        'extended' => !empty($CFG->extendedusernamechars) ? '1' : '0',
    ]);
    $ch = curl_init((string) $cfg['url']);
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
    $body = is_string($raw) ? json_decode($raw, true) : null;
    if (!is_array($body) || (int) ($body['found'] ?? 0) !== 1 || (int) ($body['active'] ?? 0) !== 1) {
        return '';
    }
    $username = trim(core_text::strtolower((string) ($body['username'] ?? '')));
    if ($username === '') {
        return '';
    }
    return core_user::clean_field($username, 'username');
}
