<?php
/**
 * Add active SIS accounts that are still missing from LDAP.
 * Does not change a password already stored in LDAP.
 *
 * Run every minute:
 *   C:\wamp64\bin\php\php8.2.0\php.exe C:\wamp64\www\SLGTIMIS\scripts\ldap_sync_missing.php
 */
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/config/database.php';
require BASE_PATH . '/core/Database.php';
require BASE_PATH . '/helpers/SisLdap.php';

$queue = SisLdapSync::processQueue();
echo 'queue done=' . $queue['done'] . ' failed=' . $queue['failed'] . PHP_EOL;

$states = SisLdapSync::reconcileStates();
echo $states['message'] . PHP_EOL;

$result = SisLdap::addMissingAccounts();
echo $result['message'] . PHP_EOL;
echo 'added=' . $result['added'] . ' present=' . $result['present'] . ' errors=' . $result['errors'] . PHP_EOL;
exit($result['errors'] > 0 && $result['added'] === 0 && $result['present'] === 0 ? 1 : 0);
