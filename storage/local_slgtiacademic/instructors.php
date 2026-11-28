<?php
require('../../config.php');
require_once($CFG->dirroot . '/local/slgtiacademic/locallib.php');

$q = optional_param('q', '', PARAM_TEXT);
$department = optional_param('department', '', PARAM_ALPHANUM);
local_slgtiacademic_page('instructors', get_string('instructors', 'local_slgtiacademic'), '/local/slgtiacademic/instructors.php');
$rows = \local_slgtiacademic\service::instructors(compact('q', 'department'));
$deptoptions = ['' => get_string('all')] + \local_slgtiacademic\service::selectable_departments();
unset($deptoptions['NONE']);

echo $OUTPUT->header();
echo local_slgtiacademic_tabs('instructors');
echo local_slgtiacademic_filters([
    ['name' => 'q', 'label' => get_string('search'), 'type' => 'text', 'value' => $q],
    ['name' => 'department', 'label' => get_string('department', 'local_slgtiacademic'), 'type' => 'select', 'value' => $department, 'options' => $deptoptions],
]);
$table = new html_table();
$table->head = [get_string('fullname'), get_string('username'), get_string('department', 'local_slgtiacademic'), get_string('course'), get_string('enrolledcount', 'local_slgtiacademic')];
$table->attributes['class'] = 'generaltable slgti-ac-table';
foreach ($rows as $row) {
    $table->data[] = [
        html_writer::link(new moodle_url('/user/profile.php', ['id' => $row['id']]), s($row['fullname'])),
        s($row['username']),
        s($row['departmentname']),
        s($row['coursenames']),
        (int) $row['students'],
    ];
}
echo html_writer::table($table);
echo $OUTPUT->footer();
