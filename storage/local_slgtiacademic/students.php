<?php
require('../../config.php');
require_once($CFG->dirroot . '/local/slgtiacademic/locallib.php');

$q = optional_param('q', '', PARAM_TEXT);
$department = optional_param('department', '', PARAM_ALPHANUM);
$courseid = optional_param('courseid', 0, PARAM_INT);
$year = optional_param('year', '', PARAM_INT);
$account = optional_param('account', '', PARAM_ALPHA);
$enrolstate = optional_param('enrolstate', '', PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);

local_slgtiacademic_page('students', get_string('students', 'local_slgtiacademic'), '/local/slgtiacademic/students.php');
$filters = compact('q', 'department', 'courseid', 'year', 'account', 'enrolstate');
$perpage = 25;
[$rows, $total] = \local_slgtiacademic\service::students($filters, $page, $perpage);
$base = new moodle_url('/local/slgtiacademic/students.php', $filters);

$deptoptions = ['' => get_string('all')] + \local_slgtiacademic\service::selectable_departments();
$courseoptions = [0 => get_string('all')] + \local_slgtiacademic\service::selectable_courses();
$years = ['' => get_string('all')];
for ($y = (int) date('Y') + 1; $y >= 2015; $y--) {
    $years[(string) $y] = (string) $y;
}

echo $OUTPUT->header();
echo local_slgtiacademic_tabs('students');
echo local_slgtiacademic_filters([
    ['name' => 'q', 'label' => get_string('search'), 'type' => 'text', 'value' => $q],
    ['name' => 'department', 'label' => get_string('department', 'local_slgtiacademic'), 'type' => 'select', 'value' => $department, 'options' => $deptoptions],
    ['name' => 'courseid', 'label' => get_string('course'), 'type' => 'select', 'value' => $courseid, 'options' => $courseoptions],
    ['name' => 'year', 'label' => get_string('year', 'local_slgtiacademic'), 'type' => 'select', 'value' => $year, 'options' => $years],
    ['name' => 'account', 'label' => get_string('status', 'local_slgtiacademic'), 'type' => 'select', 'value' => $account, 'options' => ['' => get_string('all'), 'active' => get_string('active', 'local_slgtiacademic'), 'suspended' => get_string('suspended', 'local_slgtiacademic')]],
    ['name' => 'enrolstate', 'label' => get_string('enrolments', 'local_slgtiacademic'), 'type' => 'select', 'value' => $enrolstate, 'options' => ['' => get_string('all'), 'enrolled' => get_string('active', 'local_slgtiacademic'), 'notenrolled' => get_string('unassigned', 'local_slgtiacademic')]],
]);

$table = new html_table();
$table->head = [
    get_string('studentid', 'local_slgtiacademic'),
    get_string('fullname'),
    get_string('department', 'local_slgtiacademic'),
    get_string('year', 'local_slgtiacademic'),
    get_string('nvq', 'local_slgtiacademic'),
    get_string('status', 'local_slgtiacademic'),
    get_string('course'),
    get_string('completedcourses', 'local_slgtiacademic'),
];
$table->attributes['class'] = 'generaltable slgti-ac-table';
foreach ($rows as $row) {
    $link = html_writer::link(new moodle_url('/local/slgtiacademic/student.php', ['id' => $row['id']]), s($row['fullname']));
    $table->data[] = [
        s($row['username']),
        $link,
        s($row['departmentname']),
        s($row['year'] !== '' ? $row['year'] : '-'),
        s($row['nvq'] !== '' ? $row['nvq'] : '-'),
        html_writer::span(s($row['status']), $row['suspended'] ? 'slgti-badge bad' : 'slgti-badge ok'),
        (int) $row['courses'],
        (int) $row['completed'],
    ];
}
echo html_writer::table($table);
echo $OUTPUT->paging_bar($total, $page, $perpage, $base);
echo $OUTPUT->footer();
