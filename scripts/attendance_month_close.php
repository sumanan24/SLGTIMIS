<?php
/**
 * Freezes the biometric daily attendance of a closed calendar month so later rule changes never alter it.
 * Run on (or after) the 1st of every month, e.g. with Windows Task Scheduler:
 *   php scripts/attendance_month_close.php            (previous month)
 *   php scripts/attendance_month_close.php 2026-10    (a specific closed month)
 * Original fingerprint punches are never touched. Existing stored / corrected rows are kept as they are.
 */
if (PHP_SAPI !== 'cli') {
    exit('CLI only');
}
$base = dirname(__DIR__);
define('BASE_PATH', $base);
require_once $base . '/config/database.php';
require_once $base . '/core/Database.php';
require_once $base . '/core/Model.php';
require_once $base . '/core/EnvLoader.php';
EnvLoader::load($base . '/.env');
require_once $base . '/helpers/AttendanceRules.php';
require_once $base . '/helpers/SriLankaPublicHolidays.php';
require_once $base . '/models/DailyAttendanceModel.php';
require_once $base . '/models/StudentDeviceAttendanceModel.php';

$month = $argv[1] ?? date('Y-m', strtotime(AttendanceRules::today() . ' first day of last month'));
if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
    fwrite(STDERR, "Invalid month. Use YYYY-MM.\n");
    exit(1);
}
if ($month >= substr(AttendanceRules::today(), 0, 7)) {
    fwrite(STDERR, "Month $month is not closed yet.\n");
    exit(1);
}

$students = (new StudentDeviceAttendanceModel())->listActiveStudentsForReport('', '', '', '', '');
$unique = [];
foreach ($students as $s) {
    $unique[$s['student_id']] = $s;
}
$students = array_values($unique);
$model = new DailyAttendanceModel();
$days = 0;
foreach (SriLankaPublicHolidays::weekdayTeachingDaysInMonth($month) as $d) {
    $model->syncDay($students, $d['date']);
    $days++;
}
echo "Month $month: " . count($students) . " students, $days teaching days stored.\n";
