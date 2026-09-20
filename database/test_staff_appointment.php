<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Model.php';
require_once BASE_PATH . '/models/StaffModel.php';

$failed = 0;
$passed = 0;

function assertTrue($cond, $label) {
    global $failed, $passed;
    if ($cond) {
        $passed++;
        echo "PASS  {$label}\n";
        return;
    }
    $failed++;
    echo "FAIL  {$label}\n";
}

$model = new StaffModel();
$db = Database::getInstance();
foreach (['staff_ininame', 'appoint_type', 'appoint_position', 'appoint_department_id'] as $col) {
    $r = $db->query("SHOW COLUMNS FROM `staff` LIKE '{$col}'");
    assertTrue($r && $r->num_rows === 1, "column {$col} exists");
}

$ok = StaffModel::normalizeAppointment('Permanent', 'IN3', 'acting', 'HOD', 'ICT');
assertTrue($ok['ok'] && $ok['appoint_type'] === 'acting' && $ok['appoint_position'] === 'HOD', 'permanent staff can act in another post');

$denyContract = StaffModel::normalizeAppointment('On Contract', 'IN3', 'cover_up', 'HOD', '');
assertTrue(!$denyContract['ok'], 'contract staff cannot take cover-up');

$denySame = StaffModel::normalizeAppointment('Permanent', 'HOD', 'acting', 'HOD', '');
assertTrue(!$denySame['ok'], 'cannot hold two posts that are the same permanent position');

$none = StaffModel::normalizeAppointment('Permanent', 'IN3', 'none', 'HOD', 'ICT');
assertTrue($none['ok'] && $none['appoint_type'] === 'none' && $none['appoint_position'] === null, 'none clears the other appointment');

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
