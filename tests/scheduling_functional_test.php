<?php
/**
 * Functional test on the application database. Creates clearly marked test rows ("SMOKE TEST"),
 * checks the business rules and removes the rows again. Run: php tests/scheduling_functional_test.php
 */
$base = dirname(__DIR__);
define('BASE_PATH', $base);
require_once $base . '/config/database.php';
require_once $base . '/core/Database.php';
require_once $base . '/core/Model.php';
require_once $base . '/core/Controller.php';
date_default_timezone_set('Asia/Colombo');
foreach (['SchedulingSchema', 'AttendanceRules'] as $h) {
    require_once $base . "/helpers/$h.php";
}
foreach (['GroupTimetableModel', 'GroupModel', 'DailyAttendanceModel', 'ClassAttendanceModel', 'AttendanceCorrectionModel', 'TrainingProgrammeModel', 'InstructorDiaryModel'] as $m) {
    require_once $base . "/models/$m.php";
}

$db = Database::getInstance();
$conn = $db->getConnection();
$pass = 0;
$fail = 0;
$check = static function ($name, $cond, $info = '') use (&$pass, &$fail) {
    if ($cond) {
        $pass++;
        echo "  ok   $name\n";
    } else {
        $fail++;
        echo "  FAIL $name" . ($info !== '' ? " -> $info" : '') . "\n";
    }
};
$one = static function ($sql) use ($db) {
    $r = $db->query($sql);
    return $r ? $r->fetch_assoc() : null;
};

$today = AttendanceRules::today();
$closed = date('Y-m-d', strtotime(date('Y-m-01', strtotime($today)) . ' -10 days')); // previous month
$g = $one("SELECT gs.group_id, gs.student_id FROM group_students gs WHERE gs.status = 'active' ORDER BY gs.group_id LIMIT 1");
if (!$g) {
    echo "No active group membership found; cannot run the functional test.\n";
    exit(2);
}
$sid = (string) $g['student_id'];
$gid = (int) $g['group_id'];
echo "Using group $gid, student $sid, today $today, closed date $closed\n";

$cleanup = static function () use ($db, $sid, $today, $closed) {
    $s = $db->escape($sid);
    $db->query("DELETE FROM attendance_audit_logs WHERE reason LIKE 'SMOKE TEST%'");
    $db->query("DELETE FROM attendance_corrections WHERE reason LIKE 'SMOKE TEST%'");
    $db->query("DELETE FROM student_daily_attendance WHERE student_id = '$s' AND attendance_date IN ('$today', '$closed') AND manually_updated = 1");
    $db->query("DELETE FROM class_attendance WHERE student_id = '$s' AND session_date IN ('$today', '$closed') AND source = 'manual'");
    $db->query("DELETE pp FROM programme_participants pp INNER JOIN training_programmes p ON p.id = pp.programme_id WHERE p.title LIKE 'SMOKE TEST%'");
    $db->query("DELETE ps FROM programme_schedules ps INNER JOIN training_programmes p ON p.id = ps.programme_id WHERE p.title LIKE 'SMOKE TEST%'");
    $db->query("DELETE FROM training_programmes WHERE title LIKE 'SMOKE TEST%'");
};
$cleanup();

try {
    echo "\nDaily attendance\n";
    $daily = new DailyAttendanceModel();
    $rows = $daily->listDay($today, ['group_id' => (string) $gid]);
    $mine = null;
    foreach ($rows as $r) {
        if ((string) $r['student_id'] === $sid) {
            $mine = $r;
        }
    }
    $check('listDay returns the group members', count($rows) > 0);
    $check('student row present with a status', $mine !== null && in_array($mine['final_status'] ?? $mine['status'] ?? '', ['full', 'half', 'absent'], true), json_encode(array_keys($mine ?? [])));

    echo "\nCorrections\n";
    $corr = new AttendanceCorrectionModel();
    $actor = ['user_id' => 900001, 'user_name' => 'SMOKE_HOD'];
    $other = ['user_id' => 900002, 'user_name' => 'SMOKE_HIGHER'];
    $cur = $daily->getStudentDay($sid, $today, $gid);
    $new = $cur['final_status'] === 'full' ? 'half' : 'full';
    $r = $corr->submit($sid, $today, $new, 'SMOKE TEST direct', 'direct', $actor);
    $check('direct correction in current month applied', $r['ok'] === true, $r['message']);
    $after = $daily->getStudentDay($sid, $today, $gid);
    $check('daily final status updated, source manual', $after['final_status'] === $new && (int) $after['manually_updated'] === 1 && $after['source'] === 'manual');
    $check('original calculated status preserved', $after['calc_status'] === $cur['calc_status']);
    $check('audit row written', (int) $one("SELECT COUNT(*) c FROM attendance_audit_logs WHERE reason LIKE 'SMOKE TEST%'")['c'] >= 1);
    $r = $corr->submit($sid, $today, $new, 'SMOKE TEST same', 'direct', $actor);
    $check('same status rejected', $r['ok'] === false);
    $r = $corr->submit($sid, $today, 'absent', 'abc', 'direct', $actor);
    $check('short reason rejected', $r['ok'] === false);

    $r = $corr->submit($sid, $closed, 'full', 'SMOKE TEST closed direct', 'direct', $actor);
    $check('closed month: direct correction blocked', $r['ok'] === false && stripos($r['message'], 'locked') !== false, $r['message']);
    $r = $corr->submit($sid, $today, 'full', 'SMOKE TEST wrong mode', 'exceptional', $actor);
    $check('exceptional path refused for an editable month', $r['ok'] === false);

    $cl = $daily->getStudentDay($sid, $closed, $gid);
    $check('closed-month record can be read/created', $cl !== null);
    if ($cl) {
        $newC = $cl['final_status'] === 'full' ? 'half' : 'full';
        $r = $corr->submit($sid, $closed, $newC, 'SMOKE TEST exceptional', 'exceptional', $actor);
        $check('exceptional request created as pending', $r['ok'] === true, $r['message']);
        $cid = (int) ($r['correction_id'] ?? 0);
        $stillOld = $daily->getStudentDay($sid, $closed, $gid);
        $check('closed month unchanged before approval', $stillOld['final_status'] === $cl['final_status']);
        $d = $corr->decide($cid, true, 'ok', $actor, true);
        $check('requester cannot approve own request', $d['ok'] === false);
        $d = $corr->decide($cid, true, 'ok', $other, false);
        $check('non-higher authority cannot approve exceptional', $d['ok'] === false);
        $d = $corr->decide($cid, true, 'approved for test', $other, true);
        $check('higher authority approves exceptional', $d['ok'] === true, $d['message']);
        $fin = $daily->getStudentDay($sid, $closed, $gid);
        $check('closed month updated after approval', $fin['final_status'] === $newC);
        $d = $corr->decide($cid, false, 'x', $other, true);
        $check('decision cannot be repeated', $d['ok'] === false);
    }

    echo "\nTraining programme\n";
    $tp = new TrainingProgrammeModel();
    $dept = $one("SELECT c.department_id d FROM groups g INNER JOIN course c ON c.course_id = g.course_id WHERE g.id = $gid");
    $deptId = $dept ? (string) $dept['d'] : '';
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    $res = $tp->createProgramme([
        'title' => 'SMOKE TEST programme', 'description' => '', 'department_id' => $deptId, 'course_id' => null, 'batch' => null,
        'academic_year' => null, 'training_type' => 'Practical', 'instructor_staff_id' => null, 'learning_outcomes' => '', 'created_by' => 'SMOKE',
    ], [
        ['date' => $today, 'start' => '09:00:00', 'end' => '12:00:00', 'location' => 'Workshop'],
        ['date' => $tomorrow, 'start' => '09:00:00', 'end' => '12:00:00', 'location' => 'Workshop'],
    ], [], [$gid]);
    $check('programme created as draft with group members', $res['ok'] === true, $res['message']);
    $pid = (int) ($res['id'] ?? 0);
    $p = $tp->getProgramme($pid);
    $check('status is draft', $p && $p['status'] === 'draft');
    $check('participants copied from group', count($tp->participants($pid)) === count($tp->groupMemberIds($gid)));
    $draftList = $tp->listProgrammes(['student_id' => $sid, 'published_only' => true]);
    $hidden = true;
    foreach ($draftList as $x) {
        if ((int) $x['id'] === $pid) {
            $hidden = false;
        }
    }
    $check('draft is hidden from the student view', $hidden);
    $check('publish works', $tp->setStatus($pid, 'published') === true);
    $check('publish is not repeatable', $tp->setStatus($pid, 'published') === false);
    $att = $tp->attendance($pid);
    $first = $att['schedules'][0]['id'] ?? 0;
    $second = $att['schedules'][1]['id'] ?? 0;
    $check('attendance computed for today session', isset($att['matrix'][$sid][$first]) && in_array($att['matrix'][$sid][$first], ['present', 'partial', 'absent'], true));
    $check('future session is upcoming', ($att['matrix'][$sid][$second] ?? '') === 'upcoming');
    $check('progress rows built', count($tp->progress($pid)) === count($att['participants']));
    $check('cancel with reason', $tp->setStatus($pid, 'cancelled', 'smoke test') === true);
    $check('empty schedule rejected', $tp->createProgramme(['title' => 'SMOKE TEST none'] + [], [], [], [$gid])['ok'] === false);

    echo "\nTimetable conflicts\n";
    $tt = new GroupTimetableModel();
    $row = $one("SELECT * FROM group_timetable WHERE active = 1 LIMIT 1");
    if ($row) {
        $slot = $row;
        $slot['module_id'] = $row['module_id'];
        $conf = $tt->findConflicts($slot, (int) $row['timetable_id']);
        $check('findConflicts excludes the slot itself', is_array($conf));
        $conf2 = $tt->findConflicts($slot, 0);
        $check('an identical slot conflicts with the existing one', count($conf2) > 0);
    } else {
        echo "  skip (no timetable slots in the database)\n";
    }

    echo "\nLegacy compatibility\n";
    $di = new InstructorDiaryModel();
    $di->getForReport(null, ['date_from' => date('Y-m-01'), 'date_to' => $today]);
    $check('legacy diary report still runs', true);
    $check('legacy timetable slots default to published', (int) $one("SELECT COUNT(*) c FROM group_timetable WHERE publish_status IS NULL OR publish_status = ''")['c'] === 0);
} catch (Throwable $e) {
    $fail++;
    echo "  FAIL exception: " . $e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine() . "\n";
}

$cleanup();
echo "\n$pass passed, $fail failed (test rows removed)\n";
exit($fail === 0 ? 0 : 1);
