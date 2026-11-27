<?php
/**
 * Local checks for password hashing, queue encryption and the sync tables.
 * Does not connect to LDAP and does not change user records.
 */
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/config/database.php';
require BASE_PATH . '/core/Database.php';
require BASE_PATH . '/helpers/SisLdap.php';

$test = SisLdapSync::selfTest();
echo $test['detail'] . PHP_EOL;
if (!$test['ok']) {
    exit(1);
}

SisLdapSync::ensureSchema();
$db = Database::getInstance();
$queue = $db->query("SHOW TABLES LIKE 'ldap_sync_queue'");
$log = $db->query("SHOW TABLES LIKE 'ldap_sync_log'");
$queueOk = $queue && $queue->num_rows === 1;
$logOk = $log && $log->num_rows === 1;
echo 'ldap_sync_queue=' . ($queueOk ? 'present' : 'missing') . PHP_EOL;
echo 'ldap_sync_log=' . ($logOk ? 'present' : 'missing') . PHP_EOL;
exit($queueOk && $logOk ? 0 : 1);
