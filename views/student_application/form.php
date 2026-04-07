<?php
/**
 * @var string $application_level
 * @var string $csrf_token
 * @var list<string> $errors
 * @var array<string, mixed> $old
 * @var string|null $flash_success
 * @var array<string, list<string>> $sl_provinces_districts
 * @var array<string, string> $sl_district_postal_codes
 */
$old = $old ?? [];
$sl_provinces_districts = $sl_provinces_districts ?? [];
$sl_district_postal_codes = $sl_district_postal_codes ?? [];
$errors = $errors ?? [];
$v = static function (string $key, string $default = '') use ($old): string {
    return htmlspecialchars((string) ($old[$key] ?? $default), ENT_QUOTES, 'UTF-8');
};
$actionUrl = rtrim(APP_URL, '/') . '/level' . htmlspecialchars($application_level, ENT_QUOTES, 'UTF-8') . 'application';
$levelLabel = $application_level === '04' ? 'Level 04' : 'Level 05';
$req = '<span class="text-danger fw-bold" aria-hidden="true">*</span>';
?>
<?php if (!empty($flash_success)): ?>
<div class="alert app-form-alert app-form-alert-success mb-4"><?php echo htmlspecialchars($flash_success, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
<div class="alert app-form-alert app-form-alert-danger mb-4">
    <strong>Something is wrong. Please check:</strong>
    <ul class="mb-0 mt-2"><?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<div class="card app-form-hero border-0 mb-4">
    <div class="app-form-hero-accent"></div>
    <div class="card-body p-4 p-md-4">
        <div class="d-flex flex-wrap align-items-start gap-3">
            <span class="app-form-level-pill"><?php echo htmlspecialchars($levelLabel, ENT_QUOTES, 'UTF-8'); ?></span>
            <div class="flex-grow-1 min-w-0">
                <p class="app-form-hero-kicker mb-2">Sri Lanka German Training Institute · Apply online 2026</p>
                <h1 class="app-form-hero-title mb-2">Online application</h1>
                <?php if (($application_level ?? '04') === '05'): ?>
                <p class="app-form-hero-text mb-0">Fill in <strong>all</strong> boxes about you, your address, and your first course. Upload <strong>all</strong> files. For exams: write <strong>all</strong> O/L and A/L details, <strong>or</strong> write <strong>all</strong> NVQ details, <strong>or</strong> both. You can use English. You do <strong>not</strong> need a password or login.</p>
                <?php else: ?>
                <p class="app-form-hero-text mb-0">Fill in <strong>every</strong> box and upload <strong>every</strong> file. You can use English. You do <strong>not</strong> need a password or login.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<form method="post" action="<?php echo $actionUrl; ?>" enctype="multipart/form-data" class="app-student-application-form">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="application_level" value="<?php echo htmlspecialchars($application_level, ENT_QUOTES, 'UTF-8'); ?>">

    <?php require __DIR__ . '/_form_fields.php'; ?>

    <div class="card app-form-actions-card border-0 mb-5">
        <div class="card-body p-4 d-flex flex-column flex-sm-row flex-wrap gap-3 align-items-stretch align-items-sm-center justify-content-between">
            <button type="submit" class="btn app-form-btn-submit btn-lg px-4 order-1 order-sm-0">
                <i class="fas fa-paper-plane me-2"></i>Send application
            </button>
            <a href="<?php echo htmlspecialchars(rtrim(APP_URL, '/') . '/', ENT_QUOTES, 'UTF-8'); ?>" class="btn app-form-btn-back btn-lg order-2 order-sm-1">Go back</a>
        </div>
    </div>
</form>

<script>
window.APP_BASE = <?php echo json_encode(rtrim(APP_URL, '/'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
window.NVQ_COURSE_LEVEL = <?php echo json_encode(($application_level ?? '04') === '05' ? '5' : '4', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
window.APP_FORM_OLD = <?php echo json_encode($old, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>;
window.SL_PROVINCE_DISTRICTS = <?php echo json_encode($sl_provinces_districts, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>;
window.SL_DISTRICT_POSTAL = <?php echo json_encode($sl_district_postal_codes, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>;
</script>
<?php require __DIR__ . '/_address_province_scripts.php'; ?>
<?php require __DIR__ . '/_course_preferences_scripts.php'; ?>
