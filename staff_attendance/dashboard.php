<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/dashboard_data.php';

$data = staff_attendance_load_dashboard_state();
$data['dash_form_action'] = 'dashboard.php';
$data['urls'] = staff_attendance_dashboard_urls_for_module('dashboard.php');
$data['embed_main_layout'] = true;
extract($data);

$pageTitle = 'Attendance dashboard';
$staffDeviceSection = 'dashboard';
require __DIR__ . '/includes/header.php';
?>
<div class="row g-4">
    <div class="col-lg-2 col-md-3">
        <?php include __DIR__ . '/partials/staff_device_nav.php'; ?>
    </div>
    <div class="col-lg-10 col-md-9">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0 fw-bold"><i class="fas fa-id-card me-2"></i>Staff attendance (device)</h5>
            </div>
            <div class="card-body">
                <?php include __DIR__ . '/partials/dashboard_body.php'; ?>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/partials/employee_select_search_assets.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
