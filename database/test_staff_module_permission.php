<?php
/**
 * CLI checks for staff-member module permissions. Uses temporary staff IDs, then deletes them.
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Model.php';
require_once BASE_PATH . '/models/StaffModulePermissionModel.php';
require_once BASE_PATH . '/core/AccessControl.php';

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

$model = new StaffModulePermissionModel();
$model->ensureTable();

$roleOn = ['can_view' => 1, 'can_add' => 1, 'can_edit' => 0, 'can_delete' => 0];
$inherit = AccessControl::resolve(null, $roleOn);
assertTrue(!empty($inherit['can_view']) && empty($inherit['can_delete']), 'inherit uses role defaults');

$grant = AccessControl::resolve(['access_mode' => 'grant'], $roleOn);
assertTrue(!empty($grant['can_view']) && !empty($grant['can_delete']), 'grant is full access');

$deny = AccessControl::resolve(['access_mode' => 'deny'], $roleOn);
assertTrue(empty($deny['can_view']) && empty($deny['can_add']), 'deny is no access');

$custom = AccessControl::resolve([
    'access_mode' => 'custom',
    'can_view' => 1,
    'can_add' => 0,
    'can_edit' => 1,
    'can_delete' => 0,
], $roleOn);
assertTrue(!empty($custom['can_view']) && empty($custom['can_add']) && !empty($custom['can_edit']), 'custom uses staff flags');

$a = 'TMPPERM_A';
$b = 'TMPPERM_B';
$model->resetStaff($a);
$model->resetStaff($b);
$model->saveStaff($a, [
    'students' => ['access_mode' => 'grant'],
    'staff' => ['access_mode' => 'deny'],
], 0);
$model->saveStaff($b, [
    'students' => ['access_mode' => 'deny'],
    'staff' => ['access_mode' => 'grant'],
], 0);

$rowsA = $model->getByStaff($a);
$rowsB = $model->getByStaff($b);
assertTrue(($rowsA['students']['access_mode'] ?? '') === 'grant', 'Mukilan-style grant is stored for A');
assertTrue(($rowsA['staff']['access_mode'] ?? '') === 'deny', 'A is denied staff details');
assertTrue(($rowsB['students']['access_mode'] ?? '') === 'deny', 'Rathika-style deny is stored for B');
assertTrue(($rowsB['staff']['access_mode'] ?? '') === 'grant', 'B grant does not copy to A');
assertTrue(($rowsA['students']['access_mode'] ?? '') !== ($rowsB['students']['access_mode'] ?? ''), 'staff A and B stay independent');

$model->resetStaff($a);
assertTrue(empty($model->getByStaff($a)), 'reset to role defaults deletes override rows');
$model->resetStaff($b);

$catalog = AccessControl::catalog();
assertTrue(isset($catalog['students'], $catalog['staff'], $catalog['staff_attendance'], $catalog['personal_files']), 'example modules exist in catalog');

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
