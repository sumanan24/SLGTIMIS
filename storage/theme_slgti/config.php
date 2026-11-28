<?php
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/lib.php');

$THEME->name = 'slgti';
$THEME->parents = ['boost'];
$THEME->sheets = [];
$THEME->editor_scss = ['editor'];
$THEME->usefallback = true;
$THEME->scss = function($theme) {
    return theme_boost_get_main_scss_content($theme);
};
$THEME->extrascsscallback = 'theme_slgti_get_extra_scss';
$THEME->prescsscallback = 'theme_slgti_get_pre_scss';
$THEME->rendererfactory = 'theme_overridden_renderer_factory';
$THEME->iconsystem = \core\output\icon_system::FONTAWESOME;
$THEME->haseditswitch = true;
$THEME->usescourseindex = true;
$THEME->requiredblocks = '';
$THEME->activityheaderconfig = [
    'notitle' => true,
];
$slgtidrawer = [
    'file' => 'drawers.php',
    'regions' => ['side-pre'],
    'defaultregion' => 'side-pre',
];
$THEME->layouts = [
    'base' => [
        'file' => 'drawers.php',
        'regions' => [],
    ],
    'standard' => $slgtidrawer,
    'course' => $slgtidrawer,
    'coursecategory' => $slgtidrawer,
    'incourse' => $slgtidrawer,
    'frontpage' => $slgtidrawer,
    'admin' => $slgtidrawer,
    'mycourses' => $slgtidrawer,
    'mydashboard' => $slgtidrawer,
    'mypublic' => $slgtidrawer,
    'report' => $slgtidrawer,
    'login' => [
        'file' => 'login.php',
        'regions' => [],
        'options' => ['langmenu' => true],
    ],
];
