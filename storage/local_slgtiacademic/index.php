<?php
require('../../config.php');
require_once($CFG->dirroot . '/local/slgtiacademic/locallib.php');

local_slgtiacademic_page('index', get_string('dashboard', 'local_slgtiacademic'), '/local/slgtiacademic/index.php');
$viewer = \local_slgtiacademic\service::viewer();
if ($viewer['level'] === 'course') {
    redirect(new moodle_url('/local/slgtiacademic/students.php'));
}
$stats = \local_slgtiacademic\service::stats();
if (!$stats['trend']) {
    $stats['trend'] = [get_string('none') => 0];
}
$names = [];
$studentvals = [];
$coursevals = [];
$enrolvals = [];
foreach (\local_slgtiacademic\departments::all() as $code => $name) {
    $names[] = $name;
    $studentvals[] = (int) ($stats['studentsby'][$code] ?? 0);
    $coursevals[] = (int) ($stats['coursesby'][$code] ?? 0);
    $enrolvals[] = (int) ($stats['enrolby'][$code] ?? 0);
}
$names[] = get_string('unassigned', 'local_slgtiacademic');
$studentvals[] = (int) ($stats['studentsby'][''] ?? 0);
$coursevals[] = (int) ($stats['coursesby'][''] ?? 0);
$enrolvals[] = (int) ($stats['enrolby'][''] ?? 0);

echo $OUTPUT->header();
echo local_slgtiacademic_tabs('index');
echo local_slgtiacademic_dashboard_html();
echo '<div class="slgti-ac-charts">';
echo $OUTPUT->render(\local_slgtiacademic\service::chart(get_string('studentsby', 'local_slgtiacademic'), $names, $studentvals));
echo $OUTPUT->render(\local_slgtiacademic\service::chart(get_string('coursesby', 'local_slgtiacademic'), $names, $coursevals));
echo $OUTPUT->render(\local_slgtiacademic\service::chart(get_string('enrolby', 'local_slgtiacademic'), $names, $enrolvals));
echo $OUTPUT->render(\local_slgtiacademic\service::chart(get_string('trend', 'local_slgtiacademic'), array_keys($stats['trend']), array_values($stats['trend']), 'line'));
echo $OUTPUT->render(\local_slgtiacademic\service::chart(get_string('activeinactive', 'local_slgtiacademic'), [get_string('seen', 'local_slgtiacademic'), get_string('notseen', 'local_slgtiacademic')], [$stats['seen'], $stats['inactive']], 'pie'));
echo $OUTPUT->render(\local_slgtiacademic\service::chart(get_string('completion', 'local_slgtiacademic'), [get_string('completedcourses', 'local_slgtiacademic'), get_string('totalenrolments', 'local_slgtiacademic')], [$stats['completed'], max(0, $stats['enrolments'] - $stats['completed'])], 'pie'));
echo '</div>';
echo '<p class="slgti-ac-note">' . s(get_string('attendanceunavailable', 'local_slgtiacademic')) . '</p>';
echo $OUTPUT->footer();
