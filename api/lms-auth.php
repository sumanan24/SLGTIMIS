<?php
/**
 * LMS login check. Returns a password hash only to the LMS shared secret.
 * The hash is not written to the LMS database.
 */
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/core/EnvLoader.php';
require BASE_PATH . '/config/database.php';
require BASE_PATH . '/core/Database.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo '{"ok":false}';
    exit;
}

EnvLoader::load(BASE_PATH . '/.env');
$expected = (string) EnvLoader::get('LMS_AUTH_TOKEN', '');
$given = (string) ($_SERVER['HTTP_X_SLGTI_AUTH'] ?? '');
if ($expected === '' || $given === '' || !hash_equals($expected, $given)) {
    http_response_code(403);
    echo '{"ok":false}';
    exit;
}

$raw = file_get_contents('php://input');
$contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? '');
if (is_string($raw) && $raw !== '' && str_contains($contentType, 'application/json')) {
    $parsed = json_decode($raw, true);
    if (is_array($parsed)) {
        $_POST = $parsed;
    }
} elseif ((!is_array($_POST) || $_POST === []) && is_string($raw) && $raw !== '') {
    $parsed = [];
    parse_str(str_replace('&amp;', '&', $raw), $parsed);
    if (is_array($parsed)) {
        $_POST = $parsed;
    }
}
$extended = (($_POST['extended'] ?? '0') === '1');
$action = (string) ($_POST['action'] ?? 'lookup');

function slgti_lms_username(string $name, bool $extended): string
{
    $name = strtolower(trim($name));
    if ($extended) {
        return $name;
    }
    return (string) preg_replace('/[^-\.@_a-z0-9]/', '', $name);
}

$db = Database::getInstance();
$sql = "SELECT u.`user_name`, u.`user_active`, u.`user_password_hash`,
            s.`student_id`, s.`student_nic`, st.`staff_nic`,
            COALESCE(NULLIF(u.`user_email`, ''), NULLIF(s.`student_email`, ''), NULLIF(st.`staff_email`, '')) AS email
          FROM `user` u
          LEFT JOIN `student` s ON u.`user_table` = 'student'
            AND (s.`student_id` = u.`user_name` OR s.`student_nic` = u.`user_name`)
          LEFT JOIN `staff` st ON u.`user_table` = 'staff' AND st.`staff_id` = u.`user_name`";
$result = $db->query($sql);
$rows = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
}

if ($action === 'map') {
    $users = [];
    $seenName = [];
    $seenMail = [];
    foreach ($rows as $row) {
        $username = slgti_lms_username((string) $row['user_name'], $extended);
        $email = strtolower(trim((string) $row['email']));
        if ($username === '' || $email === '' || str_ends_with($email, '@slgtimis.local')) {
            continue;
        }
        if (isset($seenName[$username]) || isset($seenMail[$email])) {
            $seenName[$username] = true;
            $seenMail[$email] = true;
            continue;
        }
        $seenName[$username] = false;
        $seenMail[$email] = false;
        $users[] = [
            'username' => $username,
            'email' => $email,
            'active' => (int) $row['user_active'] === 1 ? 1 : 0,
        ];
    }
    $clean = [];
    foreach ($users as $user) {
        if ($seenName[$user['username']] === true || $seenMail[$user['email']] === true) {
            continue;
        }
        $clean[] = $user;
    }
    echo json_encode(['ok' => true, 'users' => $clean]);
    exit;
}

function slgti_lms_keys(array $row, bool $extended): array
{
    $keys = [];
    foreach (['user_name', 'student_id', 'student_nic', 'staff_nic'] as $field) {
        $value = trim((string) ($row[$field] ?? ''));
        if ($value === '') {
            continue;
        }
        $key = slgti_lms_username($value, $extended);
        if ($key !== '') {
            $keys[$key] = true;
        }
    }
    return array_keys($keys);
}

$wanted = slgti_lms_username((string) ($_POST['username'] ?? ''), $extended);
$matches = [];
if ($wanted !== '') {
    foreach ($rows as $row) {
        if (in_array($wanted, slgti_lms_keys($row, $extended), true)) {
            $matches[] = $row;
        }
    }
}
if (count($matches) !== 1) {
    $withNic = 0;
    foreach ($rows as $candidate) {
        if (trim((string) ($candidate['student_nic'] ?? '')) !== '' || trim((string) ($candidate['staff_nic'] ?? '')) !== '') {
            $withNic++;
        }
    }
    echo json_encode([
        'ok' => true,
        'found' => 0,
        'rev' => 2,
        'matches' => count($matches),
        'ulen' => strlen($wanted),
        'rows' => count($rows),
        'with_nic' => $withNic,
    ]);
    exit;
}
$row = $matches[0];
$payload = [
    'ok' => true,
    'found' => 1,
    'rev' => 2,
    'active' => (int) $row['user_active'] === 1 ? 1 : 0,
    'username' => slgti_lms_username((string) $row['user_name'], $extended),
];
if ($action !== 'resolve') {
    $payload['hash'] = (string) $row['user_password_hash'];
}
echo json_encode($payload);
