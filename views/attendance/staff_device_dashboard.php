<?php
declare(strict_types=1);
?>
<div class="container-fluid px-4 py-3">
    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars((string) $_SESSION['flash_error'], ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-primary text-white d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h5 class="mb-0 fw-bold">
                <i class="fas fa-id-card me-2"></i>Staff attendance (device)
            </h5>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?php echo htmlspecialchars($urls['list'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-light btn-sm">
                    <i class="fas fa-list me-1"></i>List
                </a>
                <a href="<?php echo htmlspecialchars($urls['daily'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-light btn-sm">
                    <i class="fas fa-calendar-day me-1"></i>Daily report
                </a>
                <a href="<?php echo htmlspecialchars($urls['sync'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-success btn-sm">
                    <i class="fas fa-sync me-1"></i>Device sync
                </a>
            </div>
        </div>
        <div class="card-body">
            <?php
            $embed_main_layout = true;
            include BASE_PATH . '/staff_attendance/partials/dashboard_body.php';
            ?>
        </div>
    </div>
</div>
