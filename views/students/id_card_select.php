<?php
$e = static function ($v): string {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};
?>

<div class="container-fluid px-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
        <div>
            <h4 class="mb-1 fw-bold"><i class="fas fa-id-card me-2"></i>ID Card Print</h4>
            <div class="text-muted small">Enter a student registration number to preview and download the ID card.</div>
        </div>
        <a class="btn btn-outline-secondary" href="<?php echo APP_URL; ?>/students">
            <i class="fas fa-users me-1"></i> Students
        </a>
    </div>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $e($_SESSION['error']); ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="card shadow-sm border-0" style="max-width: 720px;">
        <div class="card-body p-4">
            <form class="row g-3" method="GET" action="<?php echo APP_URL; ?>/students/id-card">
                <div class="col-12">
                    <label class="form-label fw-semibold">Student ID (Registration No.)</label>
                    <input
                        type="text"
                        name="student_id"
                        class="form-control form-control-lg"
                        placeholder="Example: 2025/COT/5CT001"
                        required
                        autocomplete="off"
                    />
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-eye me-1"></i> Preview ID Card
                    </button>
                    <a class="btn btn-outline-secondary btn-lg" href="<?php echo APP_URL; ?>/students">
                        <i class="fas fa-arrow-left me-1"></i> Back
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

