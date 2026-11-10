<?php
declare(strict_types=1);
/** @var array $forms */
$forms = $forms ?? [];
$e = static function ($v): string {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};
?>
<style>
.sf-page { max-width: 1040px; margin: 0 auto; }
.sf-head { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: .75rem; margin-bottom: 1.25rem; }
.sf-head h2 { font-size: 1.5rem; font-weight: 700; color: #0f172a; margin: 0 0 .15rem; }
.sf-head p { margin: 0; color: #64748b; font-size: .9rem; }

.sf-steps { display: flex; flex-wrap: wrap; gap: .5rem 1.5rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: .75rem 1rem; margin-bottom: 1.25rem; font-size: .85rem; color: #475569; }
.sf-steps span { display: inline-flex; align-items: center; gap: .5rem; }
.sf-steps b { width: 22px; height: 22px; border-radius: 50%; background: var(--student-primary, #0d6efd); color: #fff; font-size: .75rem; display: inline-flex; align-items: center; justify-content: center; }

.sf-list { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 1px 4px rgba(15,23,42,.06); overflow: hidden; }
.sf-row { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; border-bottom: 1px solid #eef2f7; }
.sf-row:last-child { border-bottom: 0; }
.sf-row:hover { background: #fafcff; }
.sf-icon { flex: 0 0 48px; width: 48px; height: 48px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; background: rgba(13,110,253,.1); color: var(--student-primary, #0d6efd); font-size: 1.2rem; }
.sf-body { flex: 1 1 auto; min-width: 0; }
.sf-title { font-size: 1rem; font-weight: 700; color: #0f172a; margin: 0 0 .15rem; }
.sf-desc { font-size: .86rem; color: #64748b; margin: 0 0 .4rem; line-height: 1.45; }
.sf-meta { display: flex; flex-wrap: wrap; gap: .4rem; }
.sf-chip { display: inline-flex; align-items: center; gap: .35rem; font-size: .75rem; color: #475569; background: #f1f5f9; border-radius: 999px; padding: .15rem .6rem; }
.sf-action { flex: 0 0 auto; }
.sf-action .btn { min-width: 150px; white-space: nowrap; }

@media (max-width: 640px) {
    .sf-row { flex-wrap: wrap; }
    .sf-action { flex: 1 1 100%; }
    .sf-action .btn { width: 100%; }
}
</style>

<div class="container-fluid px-2 px-md-3 px-lg-4">
    <div class="sf-page">
        <div class="sf-head">
            <div>
                <h2><i class="fas fa-file-alt me-2" style="color: var(--student-primary);"></i>Forms</h2>
                <p>Download official SLGTI forms, pre-filled with your student profile.</p>
            </div>
            <a href="<?php echo APP_URL; ?>/student/dashboard" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Dashboard
            </a>
        </div>

        <div class="sf-steps">
            <span><b>1</b> Download the form</span>
            <span><b>2</b> Print and sign it</span>
            <span><b>3</b> Submit to the office shown</span>
        </div>

        <div class="sf-list">
            <?php foreach ($forms as $form): ?>
                <div class="sf-row">
                    <div class="sf-icon"><i class="fas <?php echo $e($form['icon'] ?? 'fa-file-pdf'); ?>"></i></div>
                    <div class="sf-body">
                        <h3 class="sf-title"><?php echo $e($form['title'] ?? ''); ?></h3>
                        <p class="sf-desc"><?php echo $e($form['description'] ?? ''); ?></p>
                        <div class="sf-meta">
                            <?php if (!empty($form['pages'])): ?>
                                <span class="sf-chip"><i class="fas fa-file-pdf"></i>A4 &middot; <?php echo $e($form['pages']); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($form['submit_to'])): ?>
                                <span class="sf-chip"><i class="fas fa-paper-plane"></i>Submit to: <?php echo $e($form['submit_to']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="sf-action">
                        <a class="btn btn-primary" href="<?php echo $e($form['url'] ?? '#'); ?>">
                            <i class="fas fa-download me-1"></i><?php echo $e($form['action_label'] ?? 'Download PDF'); ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
