<?php
require('../../config.php');
require_once($CFG->dirroot . '/local/slgtiacademic/locallib.php');

$q = optional_param('q', '', PARAM_TEXT);
$department = optional_param('department', '', PARAM_ALPHANUM);
$courseid = optional_param('courseid', 0, PARAM_INT);
$method = optional_param('method', '', PARAM_ALPHA);
$status = optional_param('status', '', PARAM_ALPHA);
$year = optional_param('year', '', PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
local_slgtiacademic_page('enrolments', get_string('enrolments', 'local_slgtiacademic'), '/local/slgtiacademic/enrolments.php');
$viewer = \local_slgtiacademic\service::viewer();
if ($viewer['level'] === 'course') {
    throw new required_capability_exception(context_system::instance(), 'local/slgtiacademic:viewreports', 'nopermissions', '');
}
$filters = compact('q', 'department', 'courseid', 'method', 'status', 'year');
$rows = \local_slgtiacademic\service::enrolments($filters);
$perpage = 25;
$total = count($rows);
$view = array_slice($rows, $page * $perpage, $perpage);
$deptoptions = ['' => get_string('all')] + \local_slgtiacademic\service::selectable_departments();
unset($deptoptions['NONE']);
$years = ['' => get_string('all')];
for ($y = (int) date('Y') + 1; $y >= 2015; $y--) {
    $years[(string) $y] = (string) $y;
}

echo $OUTPUT->header();
echo local_slgtiacademic_tabs('enrolments');
if ($viewer['manage']) {
    echo '<p><a class="btn btn-primary" href="' . s((new moodle_url('/local/slgtiacademic/enrol.php'))->out(false)) . '">' . s(get_string('enrolselected', 'local_slgtiacademic')) . '</a></p>';
}
echo local_slgtiacademic_filters([
    ['name' => 'q', 'label' => get_string('search'), 'type' => 'text', 'value' => $q],
    ['name' => 'department', 'label' => get_string('department', 'local_slgtiacademic'), 'type' => 'select', 'value' => $department, 'options' => $deptoptions],
    ['name' => 'courseid', 'label' => get_string('course'), 'type' => 'select', 'value' => $courseid, 'options' => [0 => get_string('all')] + \local_slgtiacademic\service::selectable_courses()],
    ['name' => 'method', 'label' => get_string('method', 'local_slgtiacademic'), 'type' => 'select', 'value' => $method, 'options' => ['' => get_string('all'), 'manual' => 'manual', 'cohort' => 'cohort', 'self' => 'self', 'coursecompleted' => 'coursecompleted']],
    ['name' => 'status', 'label' => get_string('status'), 'type' => 'select', 'value' => $status, 'options' => ['' => get_string('all'), 'active' => get_string('active', 'local_slgtiacademic'), 'suspended' => get_string('suspended', 'local_slgtiacademic')]],
    ['name' => 'year', 'label' => get_string('year', 'local_slgtiacademic'), 'type' => 'select', 'value' => $year, 'options' => $years],
]);
if ($viewer['reports']) {
    $export = new moodle_url('/local/slgtiacademic/export.php', $filters + ['report' => 'enrolments']);
    echo '<p class="slgti-ac-export">';
    foreach (['csv' => 'CSV', 'excel' => 'Excel', 'pdf' => 'PDF'] as $format => $label) {
        $export->param('format', $format);
        echo '<a href="' . s($export->out(false)) . '">' . s($label) . '</a> ';
    }
    echo '</p>';
}
$table = new html_table();
$table->head = [get_string('fullname'), get_string('username'), get_string('department', 'local_slgtiacademic'), get_string('course'), get_string('method', 'local_slgtiacademic'), get_string('status'), get_string('startdate', 'local_slgtiacademic'), get_string('enddate', 'local_slgtiacademic')];
$table->attributes['class'] = 'generaltable slgti-ac-table';
foreach ($view as $row) {
    $table->data[] = [
        s($row['fullname']),
        s($row['username']),
        s($row['departmentname']),
        s($row['course']),
        s($row['method']),
        s($row['status']),
        local_slgtiacademic_date($row['timestart']),
        local_slgtiacademic_date($row['timeend']),
    ];
}
echo html_writer::table($table);
echo $OUTPUT->paging_bar($total, $page, $perpage, new moodle_url('/local/slgtiacademic/enrolments.php', $filters));
echo $OUTPUT->footer();
