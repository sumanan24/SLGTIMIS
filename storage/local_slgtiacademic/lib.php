<?php
defined('MOODLE_INTERNAL') || die();

/**
 * @return array<int,array<string,string>>
 */
function local_slgtiacademic_menu(): array {
    if (!local_slgtiacademic_user_can_view()) {
        return [];
    }
    $items = [
        ['key' => 'acad', 'label' => get_string('dashboard', 'local_slgtiacademic'), 'url' => (new moodle_url('/local/slgtiacademic/index.php'))->out(false), 'prefix' => '/local/slgtiacademic/index.php', 'icon' => 'home'],
        ['key' => 'acadstudents', 'label' => get_string('students', 'local_slgtiacademic'), 'url' => (new moodle_url('/local/slgtiacademic/students.php'))->out(false), 'prefix' => '/local/slgtiacademic/student', 'icon' => 'user'],
        ['key' => 'acadstaff', 'label' => get_string('instructors', 'local_slgtiacademic'), 'url' => (new moodle_url('/local/slgtiacademic/instructors.php'))->out(false), 'prefix' => '/local/slgtiacademic/instructors.php', 'icon' => 'user'],
        ['key' => 'acadcourses', 'label' => get_string('courses', 'local_slgtiacademic'), 'url' => (new moodle_url('/local/slgtiacademic/courses.php'))->out(false), 'prefix' => '/local/slgtiacademic/courses.php', 'icon' => 'courses'],
        ['key' => 'acadenrol', 'label' => get_string('enrolments', 'local_slgtiacademic'), 'url' => (new moodle_url('/local/slgtiacademic/enrolments.php'))->out(false), 'prefix' => '/local/slgtiacademic/enrol', 'icon' => 'task'],
        ['key' => 'acadreports', 'label' => get_string('reports', 'local_slgtiacademic'), 'url' => (new moodle_url('/local/slgtiacademic/reports.php'))->out(false), 'prefix' => '/local/slgtiacademic/reports.php', 'icon' => 'grade'],
        ['key' => 'acadmonitor', 'label' => get_string('monitoring', 'local_slgtiacademic'), 'url' => (new moodle_url('/local/slgtiacademic/reports.php', ['type' => 'activity']))->out(false), 'prefix' => '', 'icon' => 'calendar'],
    ];
    try {
        $viewer = \local_slgtiacademic\service::viewer();
    } catch (Throwable $e) {
        return [];
    }
    if (!empty($viewer['reports']) || $viewer['level'] === 'all' || $viewer['level'] === 'department') {
        array_splice($items, 1, 0, [[
            'key' => 'acaddept',
            'label' => get_string('departments', 'local_slgtiacademic'),
            'url' => (new moodle_url('/local/slgtiacademic/departments.php'))->out(false),
            'prefix' => '/local/slgtiacademic/departments.php',
            'icon' => 'courses',
        ]]);
    }
    if ($viewer['level'] === 'all') {
        $items[] = [
            'key' => 'acadsettings',
            'label' => get_string('settings', 'local_slgtiacademic'),
            'url' => (new moodle_url('/local/slgtiacademic/departments.php'))->out(false),
            'prefix' => '',
            'icon' => 'file',
        ];
    }
    if ($viewer['level'] === 'course') {
        $items = array_values(array_filter($items, function($item) {
            return in_array($item['key'], ['acadstudents', 'acadcourses', 'acadstaff'], true);
        }));
    }
    return $items;
}

function local_slgtiacademic_user_can_view(): bool {
    try {
        return \local_slgtiacademic\service::viewer()['level'] !== 'none';
    } catch (Throwable $e) {
        return false;
    }
}

function local_slgtiacademic_dashboard_html(): string {
    global $OUTPUT, $PAGE;
    if (!local_slgtiacademic_user_can_view()) {
        return '';
    }
    try {
        $viewer = \local_slgtiacademic\service::viewer();
        if ($viewer['level'] === 'course' || $viewer['level'] === 'none') {
            return '';
        }
        $stats = \local_slgtiacademic\service::stats();
    } catch (Throwable $e) {
        return '';
    }
    $cards = [
        get_string('totalstudents', 'local_slgtiacademic') => $stats['students'],
        get_string('totalstaff', 'local_slgtiacademic') => $stats['staff'],
        get_string('totaldepartments', 'local_slgtiacademic') => $stats['departments'],
        get_string('totalcourses', 'local_slgtiacademic') => $stats['courses'],
        get_string('activecourses', 'local_slgtiacademic') => $stats['activecourses'],
        get_string('totalenrolments', 'local_slgtiacademic') => $stats['enrolments'],
        get_string('activestudents', 'local_slgtiacademic') => $stats['activestudents'],
        get_string('suspendedstudents', 'local_slgtiacademic') => $stats['suspended'],
        get_string('pendingenrolments', 'local_slgtiacademic') => $stats['pending'],
        get_string('completedcourses', 'local_slgtiacademic') => $stats['completed'],
    ];
    $html = '<section class="slgti-academic-strip"><div class="slgti-academic-head"><h2>'
        . s(get_string('dashboard', 'local_slgtiacademic')) . '</h2><a href="'
        . s((new moodle_url('/local/slgtiacademic/index.php'))->out(false)) . '">'
        . s(get_string('reports', 'local_slgtiacademic')) . '</a></div><div class="slgti-academic-cards">';
    foreach ($cards as $label => $value) {
        $html .= '<div class="slgti-academic-card"><span>' . s($label) . '</span><strong>' . (int) $value . '</strong></div>';
    }
    $html .= '</div></section>';
    return $html;
}

/**
 * Own profile, or a person inside the viewer's department.
 */
function local_slgtiacademic_myprofile_navigation(\core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
    global $USER;
    try {
        $own = (int) $USER->id === (int) $user->id;
        if (!$own && !\local_slgtiacademic\service::user_allowed((int) $user->id)) {
            return;
        }
        $parsed = \local_slgtiacademic\service::parsed_username($user->username);
        $code = \local_slgtiacademic\service::user_department((int) $user->id);
        $profile = \local_slgtiacademic\service::summary((int) $user->id);
    } catch (Throwable $e) {
        return;
    }
    $category = new \core_user\output\myprofile\category('slgtiacademic', get_string('profilecat', 'local_slgtiacademic'));
    $tree->add_category($category);
    $nodes = [
        'studentid' => $parsed['studentid'],
        'department' => \local_slgtiacademic\departments::name($code),
        'year' => $parsed['year'] !== '' ? $parsed['year'] : '-',
        'nvq' => $parsed['nvq'] !== '' ? $parsed['nvq'] : '-',
        'qualification' => $profile['qualification'] ?? '-',
        'status' => !empty($user->suspended) ? get_string('suspended', 'local_slgtiacademic') : get_string('active', 'local_slgtiacademic'),
    ];
    foreach ($nodes as $key => $value) {
        $tree->add_node(new \core_user\output\myprofile\node(
            'slgtiacademic',
            $key,
            get_string($key, 'local_slgtiacademic'),
            null,
            $value
        ));
    }
}
