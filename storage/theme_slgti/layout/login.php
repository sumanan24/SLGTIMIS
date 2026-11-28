<?php
defined('MOODLE_INTERNAL') || die();

$bodyattributes = $OUTPUT->body_attributes(['slgti-login-page']);
$boost = theme_config::load('boost');
$logourl = $boost->setting_file_url('logo', 'logo');
if (empty($logourl)) {
    $fallback = $OUTPUT->get_logo_url();
    $logourl = $fallback ? $fallback->out(false) : '';
}
$loginbackgroundurl = $boost->setting_file_url('loginbackgroundimage', 'loginbackgroundimage');

$templatecontext = [
    'sitename' => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), 'escape' => false]),
    'output' => $OUTPUT,
    'bodyattributes' => $bodyattributes,
    'logourl' => $logourl ?: '',
    'loginbackgroundurl' => $loginbackgroundurl ?: '',
];

echo $OUTPUT->render_from_template('theme_slgti/login', $templatecontext);
