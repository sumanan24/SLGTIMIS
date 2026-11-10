<?php
/**
 * CLI page renderer for smoke checks:
 *   php tests/render_page.php <user_id> <user_name> "<uri with query>"
 * Runs the real front controller as that staff user and prints a one-line verdict. Use GET URLs only.
 */
if (PHP_SAPI !== 'cli') {
    exit('CLI only');
}
$userId = (int) ($argv[1] ?? 0);
$userName = (string) ($argv[2] ?? '');
$uri = (string) ($argv[3] ?? '/');
chdir(dirname(__DIR__));
session_id('clirender' . $userId);
@session_start();
$_SESSION = [];
$_SESSION['user_id'] = $userId;
$_SESSION['user_name'] = $userName;
$_SESSION['user_table'] = (string) ($argv[4] ?? 'staff');
$_SESSION['last_activity'] = time();
$_SESSION['login_time'] = time();
$_SESSION['att_csrf'] = 'clitesttoken';
session_write_close();
$_SERVER['REQUEST_METHOD'] = 'GET';
if (!empty($argv[5])) { // optional POST body as JSON (CSRF token is added); creates data, so use with care
    $raw = $argv[5][0] === '@' ? (string) file_get_contents(substr($argv[5], 1)) : $argv[5];
    $_POST = json_decode($raw, true) ?: [];
    $_POST['csrf_token'] = 'clitesttoken';
    $_SERVER['REQUEST_METHOD'] = 'POST';
}
$_SERVER['SCRIPT_NAME'] = '/SLGTIMIS/index.php';
$_SERVER['REQUEST_URI'] = '/SLGTIMIS' . $uri;
$_SERVER['HTTP_HOST'] = 'localhost';
$q = parse_url($uri, PHP_URL_QUERY);
if ($q) {
    parse_str($q, $_GET);
}
ob_start(function ($out) use ($uri) {
    $text = trim(preg_replace('/\s+/', ' ', strip_tags(preg_replace('#<(script|style)\b.*?</\1>#is', '', $out))));
    $problems = [];
    if (preg_match_all('/(Fatal error|Parse error|Application Error|Warning:|Notice:|Deprecated:|Undefined (?:variable|index|array key|offset|property)|Uncaught|Error:)/i', $text, $mm, PREG_OFFSET_CAPTURE)) {
        foreach (array_slice($mm[0], 0, 2) as $hit) {
            $problems[] = substr($text, $hit[1], 260);
        }
    }
    preg_match('/<title>(.*?)<\/title>/is', $out, $t);
    $loc = '';
    foreach (headers_list() as $h) {
        if (stripos($h, 'Location:') === 0) {
            $loc = ' ' . $h;
        }
    }
    file_put_contents(sys_get_temp_dir() . '/render_' . preg_replace('/[^a-z0-9]+/i', '_', $uri) . '.html', $out);
    return $uri . ' => ' . (http_response_code() ?: 200) . $loc . ', ' . strlen($out) . ' bytes, title="' . ($t[1] ?? '') . '"'
        . ($problems ? "\n    PROBLEMS: " . implode("\n              ", $problems) : ' clean') . "\n";
});
require 'index.php';
