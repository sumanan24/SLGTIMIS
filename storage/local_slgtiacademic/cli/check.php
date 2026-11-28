<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/local/slgtiacademic/lib.php');

$admin = get_admin();
\core\session\manager::set_user($admin);
$stats = \local_slgtiacademic\service::stats();
$users = $DB->count_records_select('user', 'deleted = 0');
$courses = $DB->count_records_select('course', 'id > 1');
echo "USERS {$users}\nCOURSES {$courses}\n";
echo "STUDENTS {$stats['students']}\nSTAFF {$stats['staff']}\nDEPARTMENTS {$stats['departments']}\n";
echo "ACTIVECOURSES {$stats['activecourses']}\nENROLMENTS {$stats['enrolments']}\n";
echo "SUSPENDED {$stats['suspended']}\nPENDING {$stats['pending']}\nCOMPLETED {$stats['completed']}\n";
foreach (\local_slgtiacademic\departments::all() as $code => $name) {
    echo "DEPT {$code} students=" . (int) ($stats['studentsby'][$code] ?? 0)
        . " courses=" . (int) ($stats['coursesby'][$code] ?? 0)
        . " enrol=" . (int) ($stats['enrolby'][$code] ?? 0) . "\n";
}
echo "UNASSIGNED students=" . (int) ($stats['studentsby'][''] ?? 0) . "\n";
echo "DONE\n";
