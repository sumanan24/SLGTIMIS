<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Brand colours are applied before Boost compiles its styles.
 *
 * @param theme_config $theme
 * @return string
 */
function theme_slgti_get_pre_scss($theme) {
    $scss = theme_boost_get_pre_scss($theme);
    $scss .= <<<'SCSS'

$primary: #10243E;
$secondary: #315CF5;
$success: #15803d;
$warning: #d97706;
$danger: #dc2626;
$info: #22B8CF;
$body-bg: #F4F7FC;
$body-color: #17243A;
$headings-color: #10243E;
$border-radius: 0.75rem;
$border-radius-lg: 0.75rem;
$font-family-sans-serif: Inter, "Segoe UI", "Helvetica Neue", Arial, sans-serif;
$navbar-light-color: rgba(255, 255, 255, 0.92);
$navbar-light-hover-color: #ffffff;

SCSS;
    return $scss;
}

/**
 * Presentation rules only. No data queries.
 *
 * @param theme_config $theme
 * @return string
 */
/**
 * Inline icon. These are presentation only.
 *
 * @param string $name
 * @return string
 */
function theme_slgti_icon(string $name): string {
    $icons = [
        'home' => '<path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1v-9.5z"/>',
        'book' => '<path d="M5 4.5h9.5A2.5 2.5 0 0 1 17 7v12.5H7.5A2.5 2.5 0 0 0 5 22V4.5z"/><path d="M5 4.5A2.5 2.5 0 0 1 7.5 2H19v15"/>',
        'courses' => '<path d="M4 7.5 12 4l8 3.5L12 11 4 7.5z"/><path d="M8 10.2v4.2c0 .4 1.8 1.6 4 1.6s4-1.2 4-1.6v-4.2"/>',
        'calendar' => '<rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3.5V7M16 3.5V7M4 10h16"/>',
        'task' => '<path d="M8 6h10M8 12h10M8 18h10"/><path d="M4.5 6l1 1 2-2M4.5 12l1 1 2-2M4.5 18l1 1 2-2"/>',
        'grade' => '<path d="M12 3.5 14.2 8l5 .7-3.6 3.5.9 5L12 15.8 7.5 17.2l.9-5L4.8 8.7 9.8 8 12 3.5z"/>',
        'message' => '<path d="M5 6.5h14v9.2a1.5 1.5 0 0 1-1.5 1.5H9l-4 2.8v-2.8H5.5A1.5 1.5 0 0 1 4 15.7V8a1.5 1.5 0 0 1 1-1.5z"/>',
        'bell' => '<path d="M6 16.5h12l-1.2-2V10a4.8 4.8 0 0 0-9.6 0v4.5L6 16.5z"/><path d="M10 18a2 2 0 0 0 4 0"/>',
        'file' => '<path d="M7 3.5h6l4 4V20a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4.5a1 1 0 0 1 1-1z"/><path d="M13 3.5V8h4.5"/>',
        'user' => '<circle cx="12" cy="8" r="3.2"/><path d="M5.5 19.2a6.5 6.5 0 0 1 13 0"/>',
        'help' => '<circle cx="12" cy="12" r="8"/><path d="M9.5 9.4a2.5 2.5 0 1 1 3.2 2.4c-.8.4-1.2.9-1.2 1.7"/><path d="M12 16.8h.01"/>',
        'menu' => '<path d="M5 7h14M5 12h14M5 17h14"/>',
        'search' => '<circle cx="11" cy="11" r="6"/><path d="m16 16 3.5 3.5"/>',
    ];
    $path = $icons[$name] ?? $icons['home'];
    return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . $path . '</svg>';
}

/**
 * Sidebar destinations. Links stay inside Moodle.
 *
 * @return array
 */
function theme_slgti_navigation(): array {
    global $PAGE, $USER, $CFG;
    $path = $PAGE->url->get_path();
    $help = !empty($CFG->supportpage) ? $CFG->supportpage : (new moodle_url('/user/contactsitesupport.php'))->out(false);
    $items = [
        ['key' => 'dashboard', 'label' => get_string('dashboard', 'theme_slgti'), 'url' => (new moodle_url('/my/index.php'))->out(false), 'match' => '/my/index.php', 'icon' => 'home'],
        ['key' => 'learning', 'label' => get_string('mylearning', 'theme_slgti'), 'url' => (new moodle_url('/my/courses.php'))->out(false), 'match' => '/my/courses.php', 'icon' => 'book'],
        ['key' => 'courses', 'label' => get_string('allcourses', 'theme_slgti'), 'url' => (new moodle_url('/course/index.php'))->out(false), 'match' => '/course/index.php', 'icon' => 'courses'],
        ['key' => 'calendar', 'label' => get_string('calendar', 'theme_slgti'), 'url' => (new moodle_url('/calendar/view.php'))->out(false), 'match' => '/calendar/view.php', 'icon' => 'calendar'],
        ['key' => 'assignments', 'label' => get_string('assignments', 'theme_slgti'), 'url' => (new moodle_url('/calendar/view.php', ['view' => 'upcoming']))->out(false), 'match' => '', 'icon' => 'task'],
        ['key' => 'grades', 'label' => get_string('grades', 'theme_slgti'), 'url' => (new moodle_url('/grade/report/overview/index.php'))->out(false), 'match' => '/grade/report/overview/index.php', 'icon' => 'grade'],
        ['key' => 'messages', 'label' => get_string('messages', 'theme_slgti'), 'url' => (new moodle_url('/message/index.php'))->out(false), 'match' => '/message/index.php', 'icon' => 'message'],
        ['key' => 'notifications', 'label' => get_string('notifications', 'theme_slgti'), 'url' => (new moodle_url('/message/output/popup/notifications.php'))->out(false), 'match' => '/message/output/popup/notifications.php', 'icon' => 'bell'],
        ['key' => 'files', 'label' => get_string('privatefiles', 'theme_slgti'), 'url' => (new moodle_url('/user/files.php'))->out(false), 'match' => '/user/files.php', 'icon' => 'file'],
        ['key' => 'profile', 'label' => get_string('userprofile', 'theme_slgti'), 'url' => (new moodle_url('/user/profile.php', ['id' => $USER->id]))->out(false), 'match' => '/user/profile.php', 'icon' => 'user'],
        ['key' => 'help', 'label' => get_string('helpcentre', 'theme_slgti'), 'url' => $help, 'match' => '/user/contactsitesupport.php', 'icon' => 'help'],
    ];
    if (function_exists('local_slgtiacademic_menu')) {
        foreach (local_slgtiacademic_menu() as $extra) {
            $extra['match'] = '';
            $items[] = $extra;
        }
    }
    if (is_siteadmin()) {
        $items[] = [
            'key' => 'admin',
            'label' => get_string('siteadmin', 'theme_slgti'),
            'url' => (new moodle_url('/admin/search.php'))->out(false),
            'match' => '/admin/search.php',
            'icon' => 'courses',
        ];
    }
    foreach ($items as $index => $item) {
        $prefix = $item['prefix'] ?? '';
        $items[$index]['active'] = ($item['match'] !== '' && ($path === $item['match'] || ($item['match'] === '/my/index.php' && ($path === '/my/' || $path === '/my/index.php'))))
            || ($prefix !== '' && strpos($path, $prefix) === 0);
        $items[$index]['icon'] = theme_slgti_icon($item['icon']);
    }
    return $items;
}

/**
 * Read-only learning counts for the signed-in user.
 *
 * @return array
 */
function theme_slgti_learning_stats(): array {
    global $DB, $USER;
    $stats = ['enrolled' => 0, 'completed' => 0, 'inprogress' => 0, 'pending' => 0];
    if (!isloggedin() || isguestuser()) {
        return $stats;
    }
    $courses = enrol_get_my_courses(['id'], 'id ASC', 0);
    $stats['enrolled'] = count($courses);
    if (!$courses) {
        return $stats;
    }
    $ids = array_keys($courses);
    list($insql, $params) = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
    $params['userid'] = $USER->id;
    if ($DB->get_manager()->table_exists('course_completions')) {
        $stats['completed'] = (int) $DB->count_records_sql(
            "SELECT COUNT(1) FROM {course_completions} WHERE userid = :userid AND timecompleted > 0 AND course $insql",
            $params
        );
    }
    $stats['inprogress'] = max(0, $stats['enrolled'] - $stats['completed']);
    $studentids = [];
    foreach ($courses as $course) {
        $context = context_course::instance($course->id);
        if (has_capability('mod/assign:submit', $context) && !has_capability('mod/assign:grade', $context)) {
            $studentids[] = $course->id;
        }
    }
    if ($studentids && $DB->get_manager()->table_exists('assign') && $DB->get_manager()->table_exists('assign_submission')) {
        list($assignsql, $assignparams) = $DB->get_in_or_equal($studentids, SQL_PARAMS_NAMED);
        $assignparams['userid2'] = $USER->id;
        $assignparams['now'] = time();
        $stats['pending'] = (int) $DB->count_records_sql(
            "SELECT COUNT(a.id)
               FROM {assign} a
          LEFT JOIN {assign_submission} s
                 ON s.assignment = a.id AND s.userid = :userid2 AND s.latest = 1 AND s.status = 'submitted'
              WHERE a.course $assignsql
                AND s.id IS NULL
                AND (a.duedate = 0 OR a.duedate >= :now)",
            $assignparams
        );
    }
    return $stats;
}

/**
 * Values used by the dashboard welcome band.
 *
 * @return array
 */
function theme_slgti_welcome(): array {
    global $USER, $OUTPUT;
    $hour = (int) userdate(time(), '%H');
    if ($hour < 12) {
        $greeting = get_string('goodmorning', 'theme_slgti');
    } else if ($hour < 17) {
        $greeting = get_string('goodafternoon', 'theme_slgti');
    } else {
        $greeting = get_string('goodevening', 'theme_slgti');
    }
    $picture = '';
    if (isloggedin() && !isguestuser()) {
        $picture = $OUTPUT->user_picture($USER, ['size' => 72, 'link' => false, 'class' => 'slgti-avatar']);
    }
    return [
        'greeting' => $greeting,
        'fullname' => fullname($USER),
        'message' => get_string('welcomemessage', 'theme_slgti'),
        'today' => userdate(time(), '%A, %d %B %Y'),
        'avatar' => $picture,
        'actions' => [
            ['label' => get_string('mycourses', 'theme_slgti'), 'url' => (new moodle_url('/my/courses.php'))->out(false)],
            ['label' => get_string('calendar', 'theme_slgti'), 'url' => (new moodle_url('/calendar/view.php'))->out(false)],
            ['label' => get_string('grades', 'theme_slgti'), 'url' => (new moodle_url('/grade/report/overview/index.php'))->out(false)],
        ],
    ];
}

function theme_slgti_get_extra_scss($theme) {
    $scss = theme_boost_get_extra_scss($theme);
    $path = __DIR__ . '/scss/slgti.scss';
    if (is_readable($path)) {
        $scss .= "\n" . file_get_contents($path);
    }
    return $scss;
}
