<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/dashboard_data.php';

$data = staff_attendance_load_dashboard_state();
$data['dash_form_action'] = 'dashboard.php';
$data['urls'] = staff_attendance_dashboard_urls_for_module('dashboard.php');
$data['embed_main_layout'] = false;
extract($data);

$pageTitle = 'Attendance dashboard';
require __DIR__ . '/includes/header.php';
include __DIR__ . '/partials/dashboard_body.php';
require __DIR__ . '/includes/footer.php';
