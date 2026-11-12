<?php
/**
 * CLI tests for the biometric attendance rules (no database needed).
 * Run: php tests/attendance_rules_test.php
 */
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/helpers/AttendanceRules.php';

$cfg = AttendanceRules::defaults();
$tz = new DateTimeZone('Asia/Colombo');
$pass = 0;
$fail = 0;
$check = static function ($name, $cond) use (&$pass, &$fail) {
    if ($cond) {
        $pass++;
        echo "  ok   $name\n";
    } else {
        $fail++;
        echo "  FAIL $name\n";
    }
};

echo "calculateDay\n";
$r = AttendanceRules::calculateDay(['07:45:10', '16:05:00'], $cfg);
$check('morning IN + evening OUT = full (1.0)', $r['status'] === 'full' && $r['value'] === 1.0 && $r['first_in'] === '07:45:10' && $r['last_out'] === '16:05:00');

$r = AttendanceRules::calculateDay(['06:00:00', '15:30:00'], $cfg);
$check('window edges 06:00 and 15:30 are valid = full', $r['status'] === 'full');

$r = AttendanceRules::calculateDay(['08:50:30', '17:00:00'], $cfg);
$check('08:50:30 still inside morning window (to 08:50:59)', $r['status'] === 'full');

$r = AttendanceRules::calculateDay(['07:50:00'], $cfg);
$check('morning only = half (0.5)', $r['status'] === 'half' && $r['value'] === 0.5 && strpos($r['reason'], 'no evening OUT') !== false);

$r = AttendanceRules::calculateDay(['16:10:00'], $cfg);
$check('evening only = half (0.5)', $r['status'] === 'half' && $r['value'] === 0.5);

$r = AttendanceRules::calculateDay(['09:30:00', '12:00:00'], $cfg);
$check('punches outside both windows = half, not guessed as IN/OUT', $r['status'] === 'half' && $r['first_in'] === '' && $r['last_out'] === '');

$r = AttendanceRules::calculateDay([], $cfg);
$check('no punch = absent, pending verification', $r['status'] === 'absent' && $r['no_punch'] === true && strpos($r['reason'], 'pending verification') !== false);

$r = AttendanceRules::calculateDay(['05:10:00', '23:15:00'], $cfg);
$check('punches outside valid range = absent, counted invalid', $r['status'] === 'absent' && $r['invalid_count'] === 2);

$r = AttendanceRules::calculateDay(['07:40:00', '07:40:30', '07:41:10', '16:00:00'], $cfg);
$check('duplicate scans within 120s are ignored', $r['status'] === 'full' && $r['duplicate_count'] === 2 && $r['first_in'] === '07:40:00');

$r = AttendanceRules::calculateDay(['16:30:00', '07:30:00', '15:45:00'], $cfg);
$check('unsorted input: last OUT is the latest evening punch', $r['status'] === 'full' && $r['last_out'] === '16:30:00');

$r = AttendanceRules::calculateDay(['7:45', 'abc', ''], $cfg);
$check('unparseable time strings are skipped safely', $r['status'] === 'half' && $r['punch_count'] === 1);

$custom = $cfg;
$custom['morning_end'] = '09:30';
$r = AttendanceRules::calculateDay(['09:10:00', '16:00:00'], $custom);
$check('configurable window changes the result', $r['status'] === 'full');
$r = AttendanceRules::calculateDay(['09:10:00', '16:00:00'], $cfg);
$check('same punches with default window = half', $r['status'] === 'half');

echo "\nintake start (2026/2027)\n";
$check('2026/2027 before 18 Nov does not count', AttendanceRules::attendanceCountsOn('2026-11-17', '2026/2027') === false);
$check('2026/2027 on 18 Nov counts', AttendanceRules::attendanceCountsOn('2026-11-18', '2026/2027') === true);
$check('2026/2027 after 18 Nov counts', AttendanceRules::attendanceCountsOn('2026-11-19', '2026/2027') === true);
$check('other intake is unchanged', AttendanceRules::attendanceCountsOn('2026-11-02', '2025/2026') === true);

echo "\neditState (month lock)\n";
$now = new DateTimeImmutable('2026-11-20 10:00:00', $tz);
$check('10 Nov record editable on 20 Nov', AttendanceRules::editState('2026-11-10', $now)['editable'] === true);
$check('record locks on 1 Dec', AttendanceRules::editState('2026-11-10', $now)['locks_on'] === '2026-12-01');
$now = new DateTimeImmutable('2026-11-30 23:59:59', $tz);
$check('10 Nov record still editable on 30 Nov 23:59:59', AttendanceRules::isEditable('2026-11-10', $now));
$now = new DateTimeImmutable('2026-12-01 00:00:00', $tz);
$st = AttendanceRules::editState('2026-11-10', $now);
$check('10 Nov record locked from 1 Dec 00:00', $st['editable'] === false && strpos($st['reason'], 'Locked') === 0);
$check('30 Nov record locked from 1 Dec', !AttendanceRules::isEditable('2026-11-30', $now));
$now = new DateTimeImmutable('2026-11-20 10:00:00', $tz);
$check('future date not editable', !AttendanceRules::isEditable('2026-11-25', $now));
$check('invalid date not editable', !AttendanceRules::isEditable('2026-13-45', $now));
$now = new DateTimeImmutable('2027-01-05 09:00:00', $tz);
$check('December record locked on 5 Jan (year boundary)', !AttendanceRules::isEditable('2026-12-31', $now));
$check('Dec record locks_on is 1 Jan 2027', AttendanceRules::editState('2026-12-15', $now)['locks_on'] === '2027-01-01');

echo "\nhelpers\n";
$check('hms normalises 8:5 invalid / 8:05 ok', AttendanceRules::hms('8:05') === '08:05:00' && AttendanceRules::hms('x') === '');
$check('valueForStatus', AttendanceRules::valueForStatus('full') === 1.0 && AttendanceRules::valueForStatus('half') === 0.5 && AttendanceRules::valueForStatus('absent') === 0.0);

echo "\n$pass passed, $fail failed\n";
exit($fail === 0 ? 0 : 1);
