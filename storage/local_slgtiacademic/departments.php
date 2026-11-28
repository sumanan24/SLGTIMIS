<?php
require('../../config.php');
require_once($CFG->dirroot . '/local/slgtiacademic/locallib.php');

local_slgtiacademic_page('departments', get_string('departments', 'local_slgtiacademic'), '/local/slgtiacademic/departments.php');
$viewer = \local_slgtiacademic\service::viewer();
if ($viewer['level'] === 'course') {
    throw new required_capability_exception(context_system::instance(), 'local/slgtiacademic:viewreports', 'nopermissions', '');
}
$context = context_system::instance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    $action = required_param('action', PARAM_ALPHA);
    if ($action === 'map') {
        \local_slgtiacademic\service::save_mapping(required_param('userid', PARAM_INT), required_param('department', PARAM_ALPHANUM));
    } else if ($action === 'clear') {
        \local_slgtiacademic\service::clear_mapping(required_param('userid', PARAM_INT));
    } else if ($action === 'scope') {
        \local_slgtiacademic\service::assign_scope(required_param('userid', PARAM_INT), required_param('department', PARAM_ALPHANUM));
    } else if ($action === 'unscope') {
        \local_slgtiacademic\service::remove_scope(required_param('scopeid', PARAM_INT));
    } else {
        throw new moodle_exception('invalidparameter');
    }
    redirect(new moodle_url('/local/slgtiacademic/departments.php'), get_string('saved', 'local_slgtiacademic'));
}

$stats = \local_slgtiacademic\service::stats();
$unassigned = \local_slgtiacademic\service::student_rows([
    'department' => 'NONE', 'q' => '', 'year' => '', 'account' => '', 'enrolstate' => '', 'courseid' => 0,
]);

echo $OUTPUT->header();
echo local_slgtiacademic_tabs('departments');
echo '<p class="slgti-ac-note">' . s(get_string('maphelp', 'local_slgtiacademic')) . '</p>';
$table = new html_table();
$table->head = [get_string('department', 'local_slgtiacademic'), get_string('students', 'local_slgtiacademic'), get_string('courses', 'local_slgtiacademic'), get_string('enrolments', 'local_slgtiacademic')];
$table->attributes['class'] = 'generaltable slgti-ac-table';
foreach (\local_slgtiacademic\departments::all() as $code => $name) {
    if ($viewer['level'] !== 'all' && !in_array($code, $viewer['departments'], true)) {
        continue;
    }
    $table->data[] = [
        s($name),
        (int) ($stats['studentsby'][$code] ?? 0),
        (int) ($stats['coursesby'][$code] ?? 0),
        (int) ($stats['enrolby'][$code] ?? 0),
    ];
}
if ($viewer['level'] === 'all') {
    $table->data[] = [
        s(get_string('unassigned', 'local_slgtiacademic')),
        (int) ($stats['studentsby'][''] ?? 0),
        (int) ($stats['coursesby'][''] ?? 0),
        (int) ($stats['enrolby'][''] ?? 0),
    ];
}
echo html_writer::table($table);

if ($viewer['manage']) {
    echo '<h3>' . s(get_string('unassigned', 'local_slgtiacademic')) . '</h3>';
    echo '<p>' . (int) count($unassigned) . '</p>';
    $options = \local_slgtiacademic\service::selectable_departments();
    unset($options['NONE']);
    echo '<table class="generaltable slgti-ac-table"><thead><tr><th>' . s(get_string('fullname')) . '</th><th>' . s(get_string('username')) . '</th><th></th></tr></thead><tbody>';
    foreach (array_slice($unassigned, 0, 40) as $student) {
        echo '<tr><td>' . s($student['fullname']) . '</td><td>' . s($student['username']) . '</td><td>';
        echo '<form method="post">';
        echo '<input type="hidden" name="sesskey" value="' . s(sesskey()) . '">';
        echo '<input type="hidden" name="action" value="map">';
        echo '<input type="hidden" name="userid" value="' . (int) $student['id'] . '">';
        echo '<select name="department">';
        foreach ($options as $code => $label) {
            echo '<option value="' . s($code) . '">' . s($label) . '</option>';
        }
        echo '</select> <button type="submit">' . s(get_string('claim', 'local_slgtiacademic')) . '</button></form></td></tr>';
    }
    echo '</tbody></table>';
}

if ($viewer['level'] === 'all') {
    echo '<h3>' . s(get_string('addadmin', 'local_slgtiacademic')) . '</h3>';
    echo '<p class="slgti-ac-note">' . s(get_string('scopehelp', 'local_slgtiacademic')) . '</p>';
    $admins = \local_slgtiacademic\service::scoped_admins();
    $atable = new html_table();
    $atable->head = [get_string('fullname'), get_string('username'), get_string('department', 'local_slgtiacademic'), ''];
    $atable->attributes['class'] = 'generaltable slgti-ac-table';
    foreach ($admins as $admin) {
        $form = '<form method="post"><input type="hidden" name="sesskey" value="' . s(sesskey()) . '">'
            . '<input type="hidden" name="action" value="unscope"><input type="hidden" name="scopeid" value="' . (int) $admin['id'] . '">'
            . '<button type="submit">' . s(get_string('remove', 'local_slgtiacademic')) . '</button></form>';
        $atable->data[] = [s($admin['fullname']), s($admin['username']), s($admin['departmentname']), $form];
    }
    echo html_writer::table($atable);
    echo '<form method="post" class="slgti-ac-filters">';
    echo '<input type="hidden" name="sesskey" value="' . s(sesskey()) . '">';
    echo '<input type="hidden" name="action" value="scope">';
    echo '<label>User ID <input type="number" name="userid" min="2" required></label>';
    echo '<label>' . s(get_string('department', 'local_slgtiacademic')) . ' <select name="department">';
    foreach (\local_slgtiacademic\departments::all() as $code => $label) {
        echo '<option value="' . s($code) . '">' . s($label) . '</option>';
    }
    echo '</select></label><button type="submit">' . s(get_string('addadmin', 'local_slgtiacademic')) . '</button></form>';
}
echo $OUTPUT->footer();
