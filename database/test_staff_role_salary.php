<?php
/**
 * CLI checks for staff role salary logic. Safe: uses temporary role TSL, then deletes it.
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Model.php';
require_once BASE_PATH . '/models/StaffRoleModel.php';
require_once BASE_PATH . '/models/StaffRoleSalaryModel.php';

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

function assertEquals($expected, $actual, $label) {
    $ok = abs((float) $expected - (float) $actual) < 0.005;
    assertTrue($ok, $label . " (expected {$expected}, got {$actual})");
}

$roleModel = new StaffRoleModel();
$salary = new StaffRoleSalaryModel();
$roleId = 'TSL';

if (!$roleModel->exists($roleId)) {
    $created = $roleModel->createRole([
        'staff_position_type_id' => $roleId,
        'staff_position_type_name' => 'Salary Test Role',
        'staff_position' => 99,
    ]);
    assertTrue($created !== false, 'Create temporary test role TSL');
} else {
    assertTrue(true, 'Temporary test role TSL already exists');
}

$inc3 = [];
$inc2 = [];
$inc1 = [];
for ($y = 1; $y <= 18; $y++) {
    $inc3[$y] = $y * 100;          // 100, 200, ... 1800
    $inc2[$y] = 500 + ($y * 50);   // 550, 600, ...
    $inc1[$y] = 1000 + ($y * 25);  // unique per year
}

$save3 = $salary->saveGradeSalary($roleId, 3, [
    'basic_salary' => 50000,
    'max_basic_salary' => 90000,
    'effective_date' => '2020-01-01',
    'remarks' => 'Initial Grade 3',
    'increments' => $inc3,
    'allowance_items' => [
        1 => ['allowance_name' => 'Cost of Living Allowance', 'allowance_amount' => 5000],
        2 => ['allowance_name' => 'Special Allowance', 'allowance_amount' => 3000],
        3 => ['allowance_name' => '', 'allowance_amount' => 0],
    ],
], 1);
assertTrue($save3['ok'], 'Save Grade 3 scale: ' . $save3['message']);
assertTrue(empty($save3['revision']), 'First Grade 3 save is not a revision');

$save2 = $salary->saveGradeSalary($roleId, 2, [
    'basic_salary' => 70000,
    'max_basic_salary' => 120000,
    'effective_date' => '2020-01-01',
    'increments' => $inc2,
    'allowance_items' => [
        1 => ['allowance_name' => 'Cost of Living Allowance', 'allowance_amount' => 4000],
        2 => ['allowance_name' => 'Special Allowance', 'allowance_amount' => 3500],
        3 => ['allowance_name' => 'Professional Allowance', 'allowance_amount' => 2500],
    ],
], 1);
assertTrue($save2['ok'], 'Save Grade 2 scale');

$save1 = $salary->saveGradeSalary($roleId, 1, [
    'basic_salary' => 90000,
    'max_basic_salary' => 160000,
    'effective_date' => '2020-01-01',
    'increments' => $inc1,
    'allowance_items' => [
        1 => ['allowance_name' => 'Cost of Living Allowance', 'allowance_amount' => 7000],
        2 => ['allowance_name' => 'Special Allowance', 'allowance_amount' => 5000],
        3 => ['allowance_name' => '', 'allowance_amount' => 0],
    ],
], 1);
assertTrue($save1['ok'], 'Save Grade 1 scale');

$b3 = $salary->getGradeBundle($roleId, 3);
$b2 = $salary->getGradeBundle($roleId, 2);
$b1 = $salary->getGradeBundle($roleId, 1);

assertEquals(50000, $b3['scale']['basic_salary'], 'Grade 3 basic');
assertEquals(70000, $b2['scale']['basic_salary'], 'Grade 2 basic');
assertEquals(90000, $b1['scale']['basic_salary'], 'Grade 1 basic');
assertEquals(58000, $b3['scale']['gross_salary'], 'Grade 3 gross = basic + allowance');
assertEquals(5000, $b3['allowance_items'][1]['allowance_amount'], 'Grade 3 allowance 1 amount');
assertEquals(3000, $b3['allowance_items'][2]['allowance_amount'], 'Grade 3 allowance 2 amount');
assertEquals(0, $b3['allowance_items'][3]['allowance_amount'], 'Grade 3 optional third allowance unused');
assertEquals(10000, $b2['scale']['allowances'], 'Grade 2 total of 3 allowances');
assertEquals(2500, $b2['allowance_items'][3]['allowance_amount'], 'Grade 2 has a third named allowance');
assertEquals(100, $b3['increments'][1], 'Grade 3 year 1 increment');
assertEquals(1000, $b3['increments'][10], 'Grade 3 year 10 increment');
assertEquals(1100, $b3['increments'][11], 'Grade 3 year 11 increment is different');
assertEquals(1800, $b3['increments'][18], 'Grade 3 year 18 increment');
assertEquals(550, $b2['increments'][1], 'Grade 2 has its own year 1 increment');
assertEquals(1025, $b1['increments'][1], 'Grade 1 has its own year 1 increment');

$staff = [
    'staff_id' => 'TSL-EMP',
    'staff_position' => $roleId,
    'salary_grade' => 3,
    'staff_date_of_join' => '2020-01-01',
];

$y0 = $salary->calculateStaffSalary($staff, '2020-06-01');
assertEquals(0, $y0['service_year'], 'Service year 0 before first anniversary');
assertEquals(50000, $y0['basic'], 'Year 0 basic = starting basic');
assertEquals(8000, $y0['allowance'], 'Year 0 allowance');
assertEquals(58000, $y0['gross'], 'Year 0 gross');

$y1 = $salary->calculateStaffSalary($staff, '2021-01-01');
assertEquals(1, $y1['service_year'], 'Service year 1');
assertEquals(50100, $y1['basic'], 'Year 1 basic = 50000 + 100');
assertEquals(58100, $y1['gross'], 'Year 1 gross includes allowance');

$y2 = $salary->calculateStaffSalary($staff, '2022-01-01');
assertEquals(50300, $y2['basic'], 'Year 2 basic = 50100 + 200');

$sum18 = 50000;
for ($y = 1; $y <= 18; $y++) {
    $sum18 += $inc3[$y];
}
$sum18 = min(90000, $sum18);
$y18 = $salary->calculateStaffSalary($staff, '2038-01-01');
assertEquals(18, $y18['service_year'], 'Service year capped at 18');
assertEquals($sum18, $y18['basic'], 'Year 18 basic uses all individual increments and max cap');
assertTrue(!empty($y18['capped']) || $sum18 <= 90000, 'Max basic cap applied when needed');

$rev = $salary->saveGradeSalary($roleId, 3, [
    'basic_salary' => 60000,
    'max_basic_salary' => 95000,
    'effective_date' => '2020-06-01',
    'remarks' => 'Circular revision before increment',
    'increments' => $inc3,
    'allowance_items' => [
        1 => ['allowance_name' => 'Cost of Living Allowance', 'allowance_amount' => 5500],
        2 => ['allowance_name' => 'Special Allowance', 'allowance_amount' => 3500],
        3 => ['allowance_name' => '', 'allowance_amount' => 0],
    ],
], 1);
assertTrue($rev['ok'] && !empty($rev['revision']), 'Changing basic/allowance creates a revision');

$revisions = $salary->getRevisions($roleId, 3);
assertTrue(count($revisions) === 1, 'Exactly one revision row stored');
assertEquals(50000, $revisions[0]['old_basic'], 'Revision preserves old basic');
assertEquals(8000, $revisions[0]['old_allowance'], 'Revision preserves old allowance');
assertEquals(58000, $revisions[0]['old_gross'], 'Revision preserves old gross');
assertEquals(60000, $revisions[0]['new_basic'], 'Revision stores new basic');
assertEquals(9000, $revisions[0]['new_allowance'], 'Revision stores new allowance');
assertEquals(69000, $revisions[0]['new_gross'], 'Revision stores new gross');
assertTrue(!empty($revisions[0]['allowance_items']) && count($revisions[0]['allowance_items']) >= 2, 'Revision stores each allowance line');
assertEquals(5000, $revisions[0]['allowance_items'][0]['old_amount'], 'Revision old COLA');
assertEquals(5500, $revisions[0]['allowance_items'][0]['new_amount'], 'Revision new COLA');
assertEquals(3000, $revisions[0]['allowance_items'][1]['old_amount'], 'Revision old special allowance');
assertEquals(3500, $revisions[0]['allowance_items'][1]['new_amount'], 'Revision new special allowance');

$afterRevBeforeInc = $salary->calculateStaffSalary($staff, '2020-07-01');
assertEquals(60000, $afterRevBeforeInc['basic'], 'After revision and before increment, basic is the revised basic');
assertEquals(9000, $afterRevBeforeInc['allowance'], 'Allowance uses revised value');
assertEquals(69000, $afterRevBeforeInc['gross'], 'Gross uses revised basic + revised allowance');

$afterRevInc = $salary->calculateStaffSalary($staff, '2021-01-01');
assertEquals(60100, $afterRevInc['basic'], 'Increment after revision uses new basic 60000 + year 1 increment 100');
assertTrue($afterRevInc['basic'] != 50100, 'Increment after revision does not use old basic');

$historical = $salary->calculateStaffSalary($staff, '2020-03-01');
assertEquals(50000, $historical['basic'], 'Historical date before revision still uses old basic');
assertEquals(8000, $historical['allowance'], 'Historical date before revision still uses old allowance');

$g2staff = $staff;
$g2staff['salary_grade'] = 2;
$g2 = $salary->calculateStaffSalary($g2staff, '2021-01-01');
assertEquals(70550, $g2['basic'], 'Grade 2 year 1 uses Grade 2 start + Grade 2 increment');

$parts = $salary->calculateFromParts(50000, 8000, 90000, $inc3, 3, 60000);
assertEquals(60600, $parts['basic'], 'Revision-then-increment helper: 60000+100+200+300');
assertTrue(!empty($parts['revision_applied']), 'Helper flags revision applied');

$conn = Database::getInstance()->getConnection();
$deptId = 'ADM';
$staffId = 'TSL-EMP1';
$nic = '900000000V';
$email = 'tsl-emp1@slgti.test';
$epf = 'TSL-EPF-1';
$ins = $conn->prepare("INSERT INTO `staff` (`staff_id`,`department_id`,`staff_name`,`staff_address`,`staff_dob`,`staff_nic`,`staff_email`,`staff_pno`,`staff_date_of_join`,`staff_gender`,`staff_epf`,`staff_position`,`staff_type`,`staff_status`,`salary_grade`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
$name = 'Salary Test Employee';
$addr = '-';
$dob = '1990-01-01';
$pno = '0';
$join = '2020-01-01';
$gender = 'Male';
$pos = $roleId;
$type = 'Permanent';
$status = 'Working';
$grade3 = 3;
$ins->bind_param('ssssssssssssssi', $staffId, $deptId, $name, $addr, $dob, $nic, $email, $pno, $join, $gender, $epf, $pos, $type, $status, $grade3);
assertTrue($ins->execute(), 'Insert temporary staff for persist test: ' . $ins->error);
$ins->close();

$apply = $salary->applyToRoleStaff($roleId, 3, 1, '2021-01-01');
assertTrue($apply['ok'] && $apply['updated'] === 1, 'Apply calculated salary to temporary staff');

$stored = $conn->query("SELECT * FROM `staff_salary` WHERE `staff_id` = 'TSL-EMP1'")->fetch_assoc();
assertTrue(!empty($stored), 'staff_salary row created');
assertEquals(60100, $stored['current_basic'], 'Stored basic after revision + year 1 increment');
assertEquals(9000, $stored['current_allowance'], 'Stored allowance is revised allowance');
assertEquals(69100, $stored['current_gross'], 'Stored gross = revised basic+inc + allowance');

$histRows = [];
$histRes = $conn->query("SELECT * FROM `staff_salary_history` WHERE `staff_id` = 'TSL-EMP1' ORDER BY `id` ASC");
while ($row = $histRes->fetch_assoc()) {
    $histRows[] = $row;
}
assertTrue(count($histRows) >= 1, 'Staff salary history row written');
assertEquals(0, $histRows[0]['old_basic'], 'Initial history old basic starts at 0');
assertEquals(60100, $histRows[0]['new_basic'], 'Initial history new basic preserved');

$applyAgain = $salary->applyToRoleStaff($roleId, 3, 1, '2021-01-01');
$histCount = $conn->query("SELECT COUNT(*) AS c FROM `staff_salary_history` WHERE `staff_id` = 'TSL-EMP1'")->fetch_assoc();
assertEquals(count($histRows), $histCount['c'], 'Re-applying the same salary does not overwrite or duplicate history');

$conn->query("DELETE FROM `staff_salary_history` WHERE `staff_id` = 'TSL-EMP1'");
$conn->query("DELETE FROM `staff_salary` WHERE `staff_id` = 'TSL-EMP1'");
$conn->query("DELETE FROM `staff` WHERE `staff_id` = 'TSL-EMP1'");

$db = Database::getInstance();
$db->query("DELETE FROM `staff_role_salary_revision_allowance` WHERE `revision_id` IN (SELECT `id` FROM `staff_role_salary_revision` WHERE `staff_position_type_id` = 'TSL')");
$db->query("DELETE FROM `staff_role_salary_revision` WHERE `staff_position_type_id` = 'TSL'");
$db->query("DELETE FROM `staff_role_salary_increment` WHERE `salary_scale_id` IN (SELECT `id` FROM `staff_role_salary_scale` WHERE `staff_position_type_id` = 'TSL')");
$db->query("DELETE FROM `staff_role_salary_allowance` WHERE `salary_scale_id` IN (SELECT `id` FROM `staff_role_salary_scale` WHERE `staff_position_type_id` = 'TSL')");
$db->query("DELETE FROM `staff_role_salary_scale` WHERE `staff_position_type_id` = 'TSL'");
$roleModel->deleteRole($roleId);
assertTrue(!$roleModel->exists($roleId), 'Temporary test role TSL removed');

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
