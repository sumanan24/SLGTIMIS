<?php
/**
 * After SIS writes the new password to LDAP, use that directory for this Moodle account.
 * The password is not accepted or stored here.
 */
define('CLI_SCRIPT', true);
require '/var/www/moodle/config.php';

$username = $argv[1] ?? '';
if ($username === '' || preg_match('/[\r\n\0]/', $username) || strlen($username) > 191) {
    fwrite(STDERR, "bad username\n");
    exit(2);
}

$users = $DB->get_records_sql(
    "SELECT id, auth, username FROM {user} WHERE deleted = 0 AND LOWER(username) = LOWER(:username)",
    ['username' => $username]
);
if (!$users) {
    echo "moodle-user-missing\n";
    exit(0);
}
if (count($users) > 1) {
    echo "moodle-duplicate\n";
    exit(0);
}
$user = reset($users);
if ($user->auth === 'ldap') {
    echo "moodle-already-ldap\n";
    exit(0);
}
if (!in_array($user->auth, ['manual', 'db'], true)) {
    echo "moodle-auth-kept\n";
    exit(0);
}
$user->auth = 'ldap';
$user->password = 'not cached';
$DB->update_record('user', $user);
echo "moodle-ldap\n";
exit(0);
