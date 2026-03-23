<?php
declare(strict_types=1);
$staffDeviceSection = 'dashboard';
?>
<div class="container-fluid px-4 py-3">
    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars((string) $_SESSION['flash_error'], ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-2 col-md-3">
            <?php include BASE_PATH . '/staff_attendance/partials/staff_device_nav.php'; ?>
        </div>
        <div class="col-lg-10 col-md-9">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-id-card me-2"></i>Staff attendance (device)
                    </h5>
                </div>
                <div class="card-body">
                    <?php
                    $embed_main_layout = true;
                    include BASE_PATH . '/staff_attendance/partials/dashboard_body.php';
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include BASE_PATH . '/staff_attendance/partials/employee_select_search_assets.php'; ?>
