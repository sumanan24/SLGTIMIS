<?php
require('../../config.php');
require_once($CFG->dirroot . '/local/slgtiacademic/locallib.php');
require_once($CFG->libdir . '/dataformatlib.php');

$report = required_param('report', PARAM_ALPHANUMEXT);
$format = required_param('format', PARAM_ALPHANUMEXT);
$department = optional_param('department', '', PARAM_ALPHANUM);
$year = optional_param('year', '', PARAM_INT);
$account = optional_param('account', '', PARAM_ALPHA);
$q = optional_param('q', '', PARAM_TEXT);
$courseid = optional_param('courseid', 0, PARAM_INT);
$method = optional_param('method', '', PARAM_ALPHA);
$status = optional_param('status', '', PARAM_ALPHA);

require_login();
$viewer = \local_slgtiacademic\service::viewer();
if (!$viewer['reports']) {
    throw new required_capability_exception(context_system::instance(), 'local/slgtiacademic:viewreports', 'nopermissions', '');
}
if (!in_array($format, ['csv', 'excel', 'pdf'], true)) {
    throw new moodle_exception('invalidparameter');
}
$filters = compact('department', 'year', 'account', 'q', 'courseid', 'method', 'status');
$filters['enrolstate'] = '';
if ($report === 'courses') {
    $rows = \local_slgtiacademic\service::courses($filters);
} else if ($report === 'enrolments') {
    $rows = \local_slgtiacademic\service::enrolments($filters);
} else if ($report === 'instructors') {
    $rows = \local_slgtiacademic\service::instructors($filters);
} else {
    $rows = \local_slgtiacademic\service::student_rows($filters);
}
if (!$rows) {
    $rows = [['notice' => get_string('none')]];
}
$columns = array_combine(array_keys($rows[0]), array_keys($rows[0]));
$exportrows = [];
foreach ($rows as $row) {
    $line = [];
    foreach ($columns as $column) {
        $value = $row[$column];
        if (is_array($value)) {
            $value = implode(', ', $value);
        }
        $line[$column] = (string) $value;
    }
    $exportrows[] = $line;
}
\core\dataformat::download_data(
    'slgti-' . $report,
    $format,
    $columns,
    new ArrayIterator($exportrows),
    function($row) {
        return $row;
    }
);
