<?php
declare(strict_types=1);
/** @var array $forms */
$forms = $forms ?? [];
$e = static function ($v): string {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};
?>
<style>
.student-forms-page .sf-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 1px 4px rgba(15, 23, 42, 0.06);
    height: 100%;
    transition: box-shadow 0.2s ease, transform 0.2s ease;
}
.student-forms-page .sf-card:hover {
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.1);
    transform: translateY(-2px);
}
.student-forms-page .sf-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(13, 110, 253, 0.1);
    color: var(--student-primary, #0d6efd);
    font-size: 1.25rem;
}
.student-forms-page .sf-title {
    font-size: 1.05rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 0.35rem;
}
.student-forms-page .sf-desc {
    font-size: 0.875rem;
    color: #64748b;
    margin: 0;
    line-height: 1.45;
}
</style>

<div class="container-fluid px-2 px-md-3 px-lg-4 student-forms-page">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 mb-md-4">
        <div>
            <h2 class="mb-1 fw-bold">
                <i class="fas fa-file-alt me-2" style="color: var(--student-primary);"></i>Forms
            </h2>
            <p class="text-muted mb-0 small">Download forms filled with your student profile details.</p>
        </div>
        <a href="<?php echo APP_URL; ?>/student/dashboard" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Dashboard
        </a>
    </div>

    <div class="row g-3 g-md-4">
        <?php foreach ($forms as $form): ?>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="sf-card p-3 p-md-4 d-flex flex-column">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="sf-icon flex-shrink-0">
                            <i class="fas <?php echo $e($form['icon'] ?? 'fa-file-pdf'); ?>"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="sf-title"><?php echo $e($form['title'] ?? ''); ?></h3>
                            <p class="sf-desc"><?php echo $e($form['description'] ?? ''); ?></p>
                        </div>
                    </div>
                    <div class="mt-auto">
                        <a class="btn btn-primary w-100" href="<?php echo $e($form['url'] ?? '#'); ?>">
                            <i class="fas fa-download me-1"></i><?php echo $e($form['action_label'] ?? 'Download PDF'); ?>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
