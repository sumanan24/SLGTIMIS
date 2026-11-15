<?php
define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/config/database.php';
require BASE_PATH . '/core/Database.php';
require BASE_PATH . '/helpers/SisLdap.php';
$monitor = SisLdap::monitor([], 1);
echo 'error=' . ($monitor['ldap_error'] === '' ? 'none' : $monitor['ldap_error']) . "\n";
echo 'total=' . $monitor['counts']['total'] . ' match=' . $monitor['counts']['match'] . ' waiting=' . $monitor['counts']['waiting'] . ' missing=' . $monitor['counts']['missing'] . "\n";
echo 'rows=' . count($monitor['rows']) . "\n";
