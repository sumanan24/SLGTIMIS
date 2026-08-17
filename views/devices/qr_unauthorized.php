<?php
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="alert alert-danger text-center shadow-sm">
                <i class="fas fa-lock fa-2x mb-3 d-block"></i>
                <h2 class="h5"><?php echo $e($message ?? 'You are not authorized to view this device information.'); ?></h2>
                <p class="mb-3 small">Please log in with an authorized account (ADM, HOD ICT, ACC, or DIR).</p>
                <a href="<?php echo APP_URL; ?>/login" class="btn btn-primary btn-sm">Login</a>
                <a href="<?php echo APP_URL; ?>/dashboard" class="btn btn-outline-secondary btn-sm">Dashboard</a>
            </div>
        </div>
    </div>
</div>
