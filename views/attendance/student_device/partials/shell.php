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
<div class="student-device-page sd-fullpage">
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

    <?php include __DIR__ . '/nav.php'; ?>

    <div class="sd-fullpage-body">
        <div class="sd-page-head">
            <div class="sd-page-head-text">
                <h1 class="sd-page-title"><?php echo $e($pageTitle); ?></h1>
                <?php if ($pageSubtitle !== ''): ?>
                    <p class="sd-page-lead"><?php echo $e($pageSubtitle); ?></p>
                <?php endif; ?>
            </div>
            <?php if (!empty($headerActions)) {
                echo $headerActions;
            } ?>
        </div>
        <?php
        if (!empty($contentPartial) && is_file($contentPartial)) {
            include $contentPartial;
        } elseif (!empty($contentHtml)) {
            echo $contentHtml;
        }
        ?>
    </div>
</div>
