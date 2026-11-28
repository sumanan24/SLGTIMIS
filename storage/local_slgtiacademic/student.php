<?php
require('../../config.php');
require_once($CFG->dirroot . '/local/slgtiacademic/locallib.php');

$id = required_param('id', PARAM_INT);
local_slgtiacademic_page('student', get_string('students', 'local_slgtiacademic'), '/local/slgtiacademic/student.php');
$profile = \local_slgtiacademic\service::student_profile($id);
if (!$profile) {
    throw new moodle_exception('nopermissions', 'error', '', 'user');
}
$context = context_system::instance();
echo $OUTPUT->header();
echo local_slgtiacademic_tabs('students');
echo '<section class="slgti-ac-profile">';
$fields = [
    get_string('studentid', 'local_slgtiacademic') => $profile['username'],
    get_string('fullname') => $profile['fullname'],
    get_string('username') => $profile['username'],
    get_string('department', 'local_slgtiacademic') => $profile['departmentname'],
    get_string('qualification', 'local_slgtiacademic') => $profile['qualification'],
    get_string('nvq', 'local_slgtiacademic') => $profile['nvq'] !== '' ? $profile['nvq'] : '-',
    get_string('year', 'local_slgtiacademic') => $profile['year'] !== '' ? $profile['year'] : '-',
    get_string('email') => $profile['email'],
    get_string('status', 'local_slgtiacademic') => $profile['status'],
];
echo '<dl class="slgti-ac-dl">';
foreach ($fields as $label => $value) {
    echo '<dt>' . s($label) . '</dt><dd>' . s($value) . '</dd>';
}
echo '</dl>';
if (has_capability('moodle/grade:viewall', $context) || is_siteadmin()) {
    echo '<p><a href="' . s((new moodle_url('/grade/report/overview/index.php', ['userid' => $id]))->out(false)) . '">' . s(get_string('grades')) . '</a></p>';
}
echo '</section>';
$table = new html_table();
$table->head = [get_string('course'), get_string('department', 'local_slgtiacademic'), get_string('method', 'local_slgtiacademic'), get_string('status'), get_string('startdate', 'local_slgtiacademic'), get_string('enddate', 'local_slgtiacademic')];
$table->attributes['class'] = 'generaltable slgti-ac-table';
foreach ($profile['enrolments'] as $enrol) {
    $table->data[] = [
        html_writer::link(new moodle_url('/course/view.php', ['id' => $enrol['courseid']]), s($enrol['course'])),
        s($enrol['departmentname']),
        s($enrol['method']),
        s($enrol['status']),
        local_slgtiacademic_date($enrol['timestart']),
        local_slgtiacademic_date($enrol['timeend']),
    ];
}
echo html_writer::table($table);
echo '<p class="slgti-ac-note">' . s(get_string('attendanceunavailable', 'local_slgtiacademic')) . '</p>';
echo $OUTPUT->footer();
