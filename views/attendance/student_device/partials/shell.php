<?php
declare(strict_types=1);
/**
 * @var string $studentDeviceSection
 * @var array $urls
 * @var string $pageTitle
 * @var string $pageSubtitle
 */
$e = static function ($v): string {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};
$pageTitle = $pageTitle ?? 'Student fingerprint attendance';
$pageSubtitle = $pageSubtitle ?? '';
?>
<div class="container-fluid px-3 px-sm-4 py-3 student-device-page">
    <?php include __DIR__ . '/styles.php'; ?>

    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $e($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $e($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-3 g-lg-4">
        <div class="col-12 col-md-3 col-lg-2">
            <?php include __DIR__ . '/nav.php'; ?>
        </div>
        <div class="col-12 col-md-9 col-lg-10 min-w-0">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <h4 class="mb-1 fw-bold"><?php echo $e($pageTitle); ?></h4>
                    <?php if ($pageSubtitle !== ''): ?>
                        <div class="text-muted small"><?php echo $e($pageSubtitle); ?></div>
                    <?php endif; ?>
                </div>
                <?php if (!empty($headerActions)) {
                    echo $headerActions;
                } ?>
            </div>
            <?php
            if (!empty($contentPartial) && is_file($contentPartial)) {
                include $contentPartial;
            }
            ?>
        </div>
    </div>
</div>
