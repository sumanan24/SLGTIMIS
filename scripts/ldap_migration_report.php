<?php
/**
 * Dry-run account match. This script does not create, update or delete LDAP entries.
 *
 *   C:\wamp64\bin\php\php8.2.29\php.exe C:\wamp64\www\SLGTIMIS\scripts\ldap_migration_report.php
 */
declare(strict_types=1);

if (in_array('--apply', $argv ?? [], true)) {
    fwrite(STDERR, "Refusing --apply. This report never writes to LDAP.\n");
    exit(2);
}

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/config/database.php';
require BASE_PATH . '/core/Database.php';
require BASE_PATH . '/helpers/SisLdap.php';

$report = SisLdapSync::dryRun();
$path = BASE_PATH . '/storage/ldap-reports/latest.txt';
echo 'Report written to ' . $path . PHP_EOL;
echo 'SIS accounts=' . (int) $report['sis_accounts'] . PHP_EOL;
echo 'Matched=' . ($report['matched'] === null ? 'unavailable' : (string) $report['matched']) . PHP_EOL;
echo 'Missing=' . ($report['missing_ldap'] === null ? 'unavailable' : (string) $report['missing_ldap']) . PHP_EOL;
echo 'Duplicates=' . count($report['duplicate_usernames']) . PHP_EOL;
echo 'Writes=none' . PHP_EOL;
exit(0);
