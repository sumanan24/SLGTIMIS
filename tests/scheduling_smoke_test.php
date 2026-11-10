<?php
/**
 * Smoke test: applies the self-migrating schema and runs the read paths of the new models.
 * Run: php tests/scheduling_smoke_test.php   (needs the application database)
 * It does not create attendance, diary or programme data.
 */
$base = dirname(__DIR__);
define('BASE_PATH', $base);
require_once $base . '/config/database.php';
require_once $base . '/core/Database.php';
require_once $base . '/core/Model.php';
require_once $base . '/core/Controller.php';
date_default_timezone_set('Asia/Colombo');

$ok = 0;
$bad = 0;
$step = static function ($name, callable $fn) use (&$ok, &$bad) {
    try {
        $res = $fn();
        $ok++;
        echo "  ok   $name" . ($res !== null ? " ($res)" : '') . "\n";
    } catch (Throwable $e) {
        $bad++;
        echo "  FAIL $name: " . $e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine() . "\n";
    }
};

require_once $base . '/helpers/SchedulingSchema.php';
require_once $base . '/helpers/AttendanceRules.php';

$step('schema ensure()', function () { SchedulingSchema::ensure(); });
$step('schema tables exist', function () {
    $db = Database::getInstance();
    $missing = [];
    foreach (['group_student_history', 'training_programmes', 'programme_schedules', 'programme_participants', 'student_daily_attendance', 'class_attendance', 'attendance_corrections', 'attendance_audit_logs', 'instructor_diary_reviews'] as $t) {
        $r = $db->query("SHOW TABLES LIKE '" . $db->escape($t) . "'");
        if (!$r || $r->num_rows === 0) {
            $missing[] = $t;
        }
    }
    if ($missing) {
        throw new RuntimeException('missing: ' . implode(', ', $missing));
    }
});
$step('schema is idempotent (second ensure)', function () { SchedulingSchema::ensure(); });
$step('config() reads settings', function () { return AttendanceRules::config()['morning_start']; });

require_once $base . '/models/GroupTimetableModel.php';
require_once $base . '/models/GroupModel.php';
require_once $base . '/models/DailyAttendanceModel.php';
require_once $base . '/models/ClassAttendanceModel.php';
require_once $base . '/models/AttendanceCorrectionModel.php';
require_once $base . '/models/TrainingProgrammeModel.php';
require_once $base . '/models/InstructorDiaryModel.php';

$step('sessionsForDate (published only)', function () {
    return count((new GroupTimetableModel())->sessionsForDate(date('Y-m-d'))) . ' sessions today';
});
$step('GroupModel::getAllWithDetails', function () { return count((new GroupModel())->getAllWithDetails(null)) . ' groups'; });
$step('DailyAttendanceModel::biometricRecords', function () {
    return count((new DailyAttendanceModel())->biometricRecords(date('Y-m-d', strtotime('-7 days')), date('Y-m-d'), '', '', 50)) . ' punches';
});
$step('AttendanceCorrectionModel lists', function () {
    $m = new AttendanceCorrectionModel();
    $m->listCorrections(['status' => 'pending']);
    $m->listAudit('', date('Y-m-01'), date('Y-m-d'));
});
$step('TrainingProgrammeModel::listProgrammes', function () { return count((new TrainingProgrammeModel())->listProgrammes([])) . ' programmes'; });
$step('InstructorDiaryModel legacy list', function () {
    $m = new InstructorDiaryModel();
    $m->getForReport(null, ['date_from' => date('Y-m-01'), 'date_to' => date('Y-m-d')]);
});

echo "\n$ok passed, $bad failed\n";
exit($bad === 0 ? 0 : 1);
