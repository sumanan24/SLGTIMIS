<?php
declare(strict_types=1);
define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/config/database.php';
require BASE_PATH . '/core/Database.php';
require BASE_PATH . '/config/ldap.php';
require BASE_PATH . '/helpers/SisLdap.php';

$name = strtolower($argv[1] ?? 'keerththeejan');
$db = Database::getInstance();
$stmt = $db->prepare(
    "SELECT user_id, user_name, user_table, user_active,
            CHAR_LENGTH(user_password_hash) AS password_length,
            CASE
                WHEN user_password_hash REGEXP '^[0-9a-fA-F]{64}$' THEN 'sha256'
                WHEN user_password_hash LIKE '$2y$%' OR user_password_hash LIKE '$2a$%' THEN 'bcrypt'
                ELSE 'other'
            END AS password_kind,
            CASE WHEN user_email IS NULL OR user_email = '' THEN 'no' ELSE 'yes' END AS has_email
       FROM user
      WHERE LOWER(user_name) = ?
         OR LOWER(user_name) LIKE ?
      LIMIT 20"
);
$like = '%' . $name . '%';
$stmt->bind_param('ss', $name, $like);
$stmt->execute();
$result = $stmt->get_result();
echo "sis_auth_mode=" . LDAP_SIS_AUTH . PHP_EOL;
$found = 0;
while ($row = $result->fetch_assoc()) {
    $found++;
    echo 'sis_user=' . $row['user_id']
        . ' name=' . $row['user_name']
        . ' table=' . $row['user_table']
        . ' active=' . $row['user_active']
        . ' password_kind=' . $row['password_kind']
        . ' password_length=' . $row['password_length']
        . ' has_email=' . $row['has_email']
        . PHP_EOL;
    $log = $db->prepare(
        "SELECT action, result, LEFT(message, 120) AS message, created_at
           FROM ldap_sync_log
          WHERE username = ?
          ORDER BY id DESC
          LIMIT 8"
    );
    $username = (string) $row['user_name'];
    $log->bind_param('s', $username);
    $log->execute();
    $logs = $log->get_result();
    $count = 0;
    while ($entry = $logs->fetch_assoc()) {
        $count++;
        echo ' sync=' . $entry['created_at'] . ' ' . $entry['action'] . ' ' . $entry['result'] . ' ' . $entry['message'] . PHP_EOL;
    }
    if ($count === 0) {
        echo " sync=none" . PHP_EOL;
    }
}
if ($found === 0) {
    echo "sis_matches=0" . PHP_EOL;
    $emailStmt = $db->prepare(
        "SELECT user_id, user_name, user_table, user_active
           FROM user
          WHERE LOWER(user_email) LIKE ?
             OR LOWER(user_name) LIKE ?
          LIMIT 20"
    );
    $emailStmt->bind_param('ss', $like, $like);
    $emailStmt->execute();
    $emailRows = $emailStmt->get_result();
    while ($row = $emailRows->fetch_assoc()) {
        echo 'sis_related=' . $row['user_id'] . ' name=' . $row['user_name'] . ' table=' . $row['user_table'] . ' active=' . $row['user_active'] . PHP_EOL;
    }
    $nameStmt = $db->prepare(
        "SELECT 'staff' AS kind, staff_id AS login_name
           FROM staff
          WHERE LOWER(staff_name) LIKE ?
          UNION ALL
          SELECT 'student', student_id
           FROM student
          WHERE LOWER(student_fullname) LIKE ?
          LIMIT 20"
    );
    $nameStmt->bind_param('ss', $like, $like);
    $nameStmt->execute();
    $nameRows = $nameStmt->get_result();
    while ($row = $nameRows->fetch_assoc()) {
        echo 'sis_person=' . $row['kind'] . ' login=' . $row['login_name'] . PHP_EOL;
    }
}

$link = ldap_connect('ldap://' . LDAP_HOST . ':' . (int) LDAP_PORT);
ldap_set_option(null, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);
if (defined('LDAP_OPT_X_TLS_NEWCTX')) {
    ldap_set_option(null, LDAP_OPT_X_TLS_NEWCTX, 0);
}
ldap_set_option($link, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($link, LDAP_OPT_REFERRALS, 0);
if (LDAP_STARTTLS && !@ldap_start_tls($link)) {
    echo 'ldap_tls=' . ldap_error($link) . PHP_EOL;
    exit(1);
}
if (!@ldap_bind($link, LDAP_BIND_DN, LDAP_BIND_PASSWORD)) {
    echo 'ldap_bind=' . ldap_error($link) . PHP_EOL;
    exit(1);
}
$filter = '(uid=' . ldap_escape($name, '', LDAP_ESCAPE_FILTER) . ')';
$search = ldap_search($link, LDAP_PEOPLE_DN, $filter, ['uid', 'userPassword', 'businessCategory', 'pwdAccountLockedTime', 'mail']);
$entries = ldap_get_entries($link, $search);
echo 'ldap_matches=' . (int) ($entries['count'] ?? 0) . PHP_EOL;
if (($entries['count'] ?? 0) > 0) {
    $entry = $entries[0];
    $hash = (string) ($entry['userpassword'][0] ?? '');
    echo 'ldap_uid=' . ($entry['uid'][0] ?? '') . PHP_EOL;
    echo 'ldap_has_password=' . ($hash !== '' ? 'yes' : 'no') . PHP_EOL;
    echo 'ldap_password_length=' . strlen($hash) . PHP_EOL;
    $hashType = 'unknown';
    if (preg_match('/^\{([^}]+)\}/', $hash, $match)) {
        $hashType = $match[1];
    } elseif ($hash === '') {
        $hashType = 'none';
    }
    echo 'ldap_hash_type=' . $hashType . PHP_EOL;
    echo 'ldap_state=' . ($entry['businesscategory'][0] ?? 'none') . PHP_EOL;
    echo 'ldap_locked=' . (empty($entry['pwdaccountlockedtime'][0]) ? 'no' : 'yes') . PHP_EOL;
    echo 'ldap_has_mail=' . (empty($entry['mail'][0]) ? 'no' : 'yes') . PHP_EOL;
}
