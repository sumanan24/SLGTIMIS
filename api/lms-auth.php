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

$extended = (($_POST['extended'] ?? '0') === '1');
$action = (string) ($_POST['action'] ?? 'lookup');
if (($_POST['username'] ?? '') === '' && ($_POST['action'] ?? '') === '') {
    $raw = file_get_contents('php://input');
    error_log('lms-auth raw-bytes=' . (is_string($raw) ? strlen($raw) : 0));
    if (is_string($raw) && $raw !== '') {
        $parsed = [];
        parse_str($raw, $parsed);
        if (is_array($parsed)) {
            $_POST = $parsed + $_POST;
        }
    }
    $extended = (($_POST['extended'] ?? '0') === '1');
    $action = (string) ($_POST['action'] ?? 'lookup');
}

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

$wanted = slgti_lms_username((string) ($_POST['username'] ?? ''), $extended);
$matches = [];
foreach ($rows as $row) {
    if (slgti_lms_username((string) $row['user_name'], $extended) === $wanted && $wanted !== '') {
        $matches[] = $row;
    }
}
if (count($matches) !== 1) {
    error_log('lms-auth lookup miss ulen=' . strlen($wanted) . ' extended=' . ($extended ? '1' : '0') . ' matches=' . count($matches));
    echo '{"ok":true,"found":0}';
    exit;
}
$row = $matches[0];
echo json_encode([
    'ok' => true,
    'found' => 1,
    'active' => (int) $row['user_active'] === 1 ? 1 : 0,
    'hash' => (string) $row['user_password_hash'],
]);
