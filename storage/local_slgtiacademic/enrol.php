<?php
require('../../config.php');
require_once($CFG->dirroot . '/local/slgtiacademic/locallib.php');

local_slgtiacademic_page('enrol', get_string('enrolments', 'local_slgtiacademic'), '/local/slgtiacademic/enrol.php');
$viewer = \local_slgtiacademic\service::viewer();
$context = context_system::instance();
if (!$viewer['manage']) {
    throw new required_capability_exception($context, 'local/slgtiacademic:manage', 'nopermissions', '');
}
$department = optional_param('department', '', PARAM_ALPHANUM);
$courseid = optional_param('courseid', 0, PARAM_INT);
$role = optional_param('role', 'student', PARAM_ALPHA);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    $courseid = required_param('courseid', PARAM_INT);
    $role = required_param('role', PARAM_ALPHA);
    $userids = optional_param_array('userids', [], PARAM_INT);
    $result = \local_slgtiacademic\service::bulk_enrol($courseid, $userids, $role);
    redirect(new moodle_url('/local/slgtiacademic/enrol.php', ['department' => $department, 'courseid' => $courseid]),
        get_string('enrolledresult', 'local_slgtiacademic', (object) [
            'ok' => $result['ok'],
            'skipped' => $result['skipped'],
            'failed' => count($result['failed']),
        ]),
        $result['failed'] ? \core\output\notification::NOTIFY_WARNING : \core\output\notification::NOTIFY_SUCCESS);
}

$deptoptions = \local_slgtiacademic\service::selectable_departments();
unset($deptoptions['NONE']);
if ($department === '' && $deptoptions) {
    $department = (string) array_key_first($deptoptions);
}
$students = \local_slgtiacademic\service::student_rows([
    'department' => $department,
    'q' => '',
    'year' => '',
    'account' => 'active',
    'enrolstate' => '',
]);

echo $OUTPUT->header();
echo local_slgtiacademic_tabs('enrolments');
echo local_slgtiacademic_filters([
    ['name' => 'department', 'label' => get_string('department', 'local_slgtiacademic'), 'type' => 'select', 'value' => $department, 'options' => $deptoptions],
    ['name' => 'courseid', 'label' => get_string('course'), 'type' => 'select', 'value' => $courseid, 'options' => [0 => get_string('choosedots')] + \local_slgtiacademic\service::selectable_courses()],
]);
echo '<form method="post" action="' . s((new moodle_url('/local/slgtiacademic/enrol.php'))->out(false)) . '" class="slgti-ac-enrol">';
echo '<input type="hidden" name="sesskey" value="' . s(sesskey()) . '">';
echo '<input type="hidden" name="department" value="' . s($department) . '">';
echo '<label>' . s(get_string('course')) . ' <select name="courseid" required>';
foreach (\local_slgtiacademic\service::selectable_courses() as $id => $label) {
    if ($department !== '') {
        $code = '';
        foreach (\local_slgtiacademic\departments::all() as $c => $name) {
            if (strpos($label, $name) === 0) {
                $code = $c;
            }
        }
        if ($code !== $department) {
            continue;
        }
    }
    $selected = ((int) $id === (int) $courseid) ? ' selected' : '';
    echo '<option value="' . (int) $id . '"' . $selected . '>' . s($label) . '</option>';
}
echo '</select></label>';
echo '<label>' . s(get_string('role', 'local_slgtiacademic')) . ' <select name="role">';
foreach (['student' => get_string('defaultcoursestudent'), 'editingteacher' => get_string('defaultcourseteacher'), 'teacher' => get_string('noneditingteacher')] as $value => $label) {
    $selected = $role === $value ? ' selected' : '';
    echo '<option value="' . s($value) . '"' . $selected . '>' . s($label) . '</option>';
}
echo '</select></label>';
echo '<label>' . s(get_string('selectstudents', 'local_slgtiacademic')) . '</label>';
echo '<select name="userids[]" multiple size="12" required>';
foreach ($students as $student) {
    echo '<option value="' . (int) $student['id'] . '">' . s($student['fullname'] . ' (' . $student['username'] . ')') . '</option>';
}
echo '</select>';
echo '<button type="submit">' . s(get_string('enrolselected', 'local_slgtiacademic')) . '</button>';
echo '</form>';
echo $OUTPUT->footer();
