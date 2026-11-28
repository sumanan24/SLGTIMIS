<?php
defined('MOODLE_INTERNAL') || die();

function local_slgtiacademic_page(string $id, string $title, string $url): void {
    global $PAGE, $OUTPUT, $SITE;
    $context = context_system::instance();
    require_login();
    if (!local_slgtiacademic_user_can_view()) {
        throw new required_capability_exception($context, 'local/slgtiacademic:view', 'nopermissions', '');
    }
    $PAGE->set_context($context);
    $PAGE->set_url(new moodle_url($url));
    $PAGE->set_pagelayout('standard');
    $PAGE->set_title($title);
    $PAGE->set_heading($SITE->fullname);
    $PAGE->requires->css(new moodle_url('/local/slgtiacademic/styles.css'));
}

function local_slgtiacademic_tabs(string $current): string {
    $viewer = \local_slgtiacademic\service::viewer();
    $tabs = [
        'index' => ['/local/slgtiacademic/index.php', get_string('dashboard', 'local_slgtiacademic')],
        'students' => ['/local/slgtiacademic/students.php', get_string('students', 'local_slgtiacademic')],
        'courses' => ['/local/slgtiacademic/courses.php', get_string('courses', 'local_slgtiacademic')],
        'instructors' => ['/local/slgtiacademic/instructors.php', get_string('instructors', 'local_slgtiacademic')],
    ];
    if ($viewer['level'] !== 'course') {
        $tabs = [
            'index' => $tabs['index'],
            'departments' => ['/local/slgtiacademic/departments.php', get_string('departments', 'local_slgtiacademic')],
        ] + $tabs;
        $tabs['enrolments'] = ['/local/slgtiacademic/enrolments.php', get_string('enrolments', 'local_slgtiacademic')];
        $tabs['reports'] = ['/local/slgtiacademic/reports.php', get_string('reports', 'local_slgtiacademic')];
    }
    $html = '<nav class="slgti-ac-tabs">';
    foreach ($tabs as $key => $tab) {
        $class = $key === $current ? ' class="active"' : '';
        $html .= '<a' . $class . ' href="' . s((new moodle_url($tab[0]))->out(false)) . '">' . s($tab[1]) . '</a>';
    }
    $html .= '</nav>';
    return $html;
}

function local_slgtiacademic_filters(array $fields): string {
    $html = '<form method="get" class="slgti-ac-filters">';
    foreach ($fields as $field) {
        $html .= '<label>' . s($field['label']);
        if ($field['type'] === 'select') {
            $html .= '<select name="' . s($field['name']) . '">';
            foreach ($field['options'] as $value => $label) {
                $selected = ((string) $field['value'] === (string) $value) ? ' selected' : '';
                $html .= '<option value="' . s($value) . '"' . $selected . '>' . s($label) . '</option>';
            }
            $html .= '</select>';
        } else {
            $html .= '<input type="' . s($field['type']) . '" name="' . s($field['name']) . '" value="' . s($field['value']) . '">';
        }
        $html .= '</label>';
    }
    $html .= '<button type="submit">' . s(get_string('search')) . '</button></form>';
    return $html;
}

function local_slgtiacademic_date(int $time): string {
    if ($time <= 0) {
        return '-';
    }
    return userdate($time, get_string('strftimedate', 'langconfig'));
}
