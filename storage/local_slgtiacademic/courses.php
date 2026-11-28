<?php
require('../../config.php');
require_once($CFG->dirroot . '/local/slgtiacademic/locallib.php');

$q = optional_param('q', '', PARAM_TEXT);
$department = optional_param('department', '', PARAM_ALPHANUM);
$visible = optional_param('visible', '', PARAM_TEXT);
if (!in_array($visible, ['', '0', '1'], true)) {
    $visible = '';
}
$page = optional_param('page', 0, PARAM_INT);
local_slgtiacademic_page('courses', get_string('courses', 'local_slgtiacademic'), '/local/slgtiacademic/courses.php');
$rows = \local_slgtiacademic\service::courses(compact('q', 'department', 'visible'));
$perpage = 25;
$total = count($rows);
$rows = array_slice($rows, $page * $perpage, $perpage);
$deptoptions = ['' => get_string('all')] + \local_slgtiacademic\service::selectable_departments();
unset($deptoptions['NONE']);

echo $OUTPUT->header();
echo local_slgtiacademic_tabs('courses');
echo local_slgtiacademic_filters([
    ['name' => 'q', 'label' => get_string('search'), 'type' => 'text', 'value' => $q],
    ['name' => 'department', 'label' => get_string('department', 'local_slgtiacademic'), 'type' => 'select', 'value' => $department, 'options' => $deptoptions],
    ['name' => 'visible', 'label' => get_string('visibility', 'local_slgtiacademic'), 'type' => 'select', 'value' => $visible, 'options' => ['' => get_string('all'), '1' => get_string('visible', 'local_slgtiacademic'), '0' => get_string('hidden', 'local_slgtiacademic')]],
]);
$table = new html_table();
$table->head = [get_string('course'), get_string('department', 'local_slgtiacademic'), get_string('category'), get_string('visibility', 'local_slgtiacademic'), get_string('enrolledcount', 'local_slgtiacademic'), get_string('capacity', 'local_slgtiacademic'), get_string('startdate', 'local_slgtiacademic'), get_string('enddate', 'local_slgtiacademic')];
$table->attributes['class'] = 'generaltable slgti-ac-table';
foreach ($rows as $row) {
    $table->data[] = [
        html_writer::link(new moodle_url('/course/view.php', ['id' => $row['id']]), s($row['fullname'])),
        s($row['departmentname']),
        s($row['category']),
        (int) $row['visible'] ? get_string('yes') : get_string('no'),
        (int) $row['enrolled'],
        $row['capacity'] > 0 ? (int) $row['capacity'] : get_string('unassigned', 'local_slgtiacademic'),
        local_slgtiacademic_date($row['startdate']),
        local_slgtiacademic_date($row['enddate']),
    ];
}
echo html_writer::table($table);
echo $OUTPUT->paging_bar($total, $page, $perpage, new moodle_url('/local/slgtiacademic/courses.php', compact('q', 'department', 'visible')));
echo $OUTPUT->footer();
