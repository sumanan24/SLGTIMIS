<?php
require('../../config.php');
require_once($CFG->dirroot . '/local/slgtiacademic/locallib.php');

$type = optional_param('type', 'students', PARAM_ALPHANUMEXT);
$department = optional_param('department', '', PARAM_ALPHANUM);
$year = optional_param('year', '', PARAM_INT);
$account = optional_param('account', '', PARAM_ALPHA);
if (!in_array($type, ['students', 'courses', 'enrolments', 'instructors', 'activity', 'completions', 'active', 'inactive', 'year'], true)) {
    $type = 'students';
}
local_slgtiacademic_page('reports', get_string('reports', 'local_slgtiacademic'), '/local/slgtiacademic/reports.php');
$viewer = \local_slgtiacademic\service::viewer();
if (!$viewer['reports']) {
    throw new required_capability_exception(context_system::instance(), 'local/slgtiacademic:viewreports', 'nopermissions', '');
}
$filters = ['department' => $department, 'year' => $year, 'account' => $account, 'q' => '', 'enrolstate' => '', 'courseid' => 0];
if ($type === 'active') {
    $filters['account'] = 'active';
}
if ($type === 'inactive') {
    $filters['account'] = 'active';
}
$rows = [];
if (in_array($type, ['students', 'activity', 'active', 'inactive', 'year', 'completions'], true)) {
    $rows = \local_slgtiacademic\service::student_rows($filters);
    $now = time();
    if ($type === 'active') {
        $rows = array_values(array_filter($rows, function($row) use ($now) {
            return $row['lastaccess'] >= ($now - (90 * DAYSECS)) && $row['lastaccess'] > 0;
        }));
    }
    if ($type === 'inactive') {
        $rows = array_values(array_filter($rows, function($row) use ($now) {
            return $row['lastaccess'] < ($now - (90 * DAYSECS));
        }));
    }
    if ($type === 'completions') {
        $rows = array_values(array_filter($rows, function($row) {
            return $row['completed'] > 0;
        }));
    }
} else if ($type === 'courses') {
    $rows = \local_slgtiacademic\service::courses($filters);
} else if ($type === 'enrolments') {
    $rows = \local_slgtiacademic\service::enrolments($filters);
} else {
    $rows = \local_slgtiacademic\service::instructors($filters);
}
$deptoptions = ['' => get_string('all')] + \local_slgtiacademic\service::selectable_departments();
unset($deptoptions['NONE']);
$years = ['' => get_string('all')];
for ($y = (int) date('Y') + 1; $y >= 2015; $y--) {
    $years[(string) $y] = (string) $y;
}
$types = [
    'students' => get_string('students', 'local_slgtiacademic'),
    'courses' => get_string('courses', 'local_slgtiacademic'),
    'enrolments' => get_string('enrolments', 'local_slgtiacademic'),
    'instructors' => get_string('instructors', 'local_slgtiacademic'),
    'activity' => get_string('monitoring', 'local_slgtiacademic'),
    'completions' => get_string('completedcourses', 'local_slgtiacademic'),
    'active' => get_string('activestudents', 'local_slgtiacademic'),
    'inactive' => get_string('notseen', 'local_slgtiacademic'),
    'year' => get_string('year', 'local_slgtiacademic'),
];

echo $OUTPUT->header();
echo local_slgtiacademic_tabs('reports');
echo local_slgtiacademic_filters([
    ['name' => 'type', 'label' => get_string('report', 'local_slgtiacademic'), 'type' => 'select', 'value' => $type, 'options' => $types],
    ['name' => 'department', 'label' => get_string('department', 'local_slgtiacademic'), 'type' => 'select', 'value' => $department, 'options' => $deptoptions],
    ['name' => 'year', 'label' => get_string('year', 'local_slgtiacademic'), 'type' => 'select', 'value' => $year, 'options' => $years],
    ['name' => 'account', 'label' => get_string('status', 'local_slgtiacademic'), 'type' => 'select', 'value' => $account, 'options' => ['' => get_string('all'), 'active' => get_string('active', 'local_slgtiacademic'), 'suspended' => get_string('suspended', 'local_slgtiacademic')]],
]);
$export = new moodle_url('/local/slgtiacademic/export.php', ['report' => $type, 'department' => $department, 'year' => $year, 'account' => $account]);
echo '<p class="slgti-ac-export">';
foreach (['csv' => 'CSV', 'excel' => 'Excel', 'pdf' => 'PDF'] as $format => $label) {
    $export->param('format', $format);
    echo '<a href="' . s($export->out(false)) . '">' . s(get_string('export', 'local_slgtiacademic') . ' ' . $label) . '</a> ';
}
echo '</p>';
echo '<p>' . (int) count($rows) . '</p>';
$table = new html_table();
$table->attributes['class'] = 'generaltable slgti-ac-table';
if ($rows) {
    $first = $rows[0];
    $keep = array_intersect(['fullname', 'username', 'departmentname', 'year', 'status', 'course', 'coursenames', 'enrolled', 'completed', 'method', 'lastaccess'], array_keys($first));
    $table->head = array_map(function($key) {
        return s(get_string_manager()->string_exists($key, 'local_slgtiacademic') ? get_string($key, 'local_slgtiacademic') : $key);
    }, $keep);
    foreach (array_slice($rows, 0, 100) as $row) {
        $line = [];
        foreach ($keep as $key) {
            $value = $row[$key];
            if ($key === 'lastaccess') {
                $value = local_slgtiacademic_date((int) $value);
            }
            $line[] = s(is_scalar($value) ? (string) $value : '');
        }
        $table->data[] = $line;
    }
}
echo html_writer::table($table);
echo '<p class="slgti-ac-note">' . s(get_string('attendanceunavailable', 'local_slgtiacademic')) . '</p>';
echo $OUTPUT->footer();
