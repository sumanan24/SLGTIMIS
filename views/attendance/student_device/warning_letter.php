<?php
declare(strict_types=1);
/** @var array $urls */
/** @var bool $canManageDevice */
/** @var array $student */
/** @var array $meta */
/** @var string $letterHtml */
/** @var string $letterCss */
/** @var string $pdfUrl */
/** @var string $printUrl */
/** @var string $reportMonth */

$e = static function ($v): string {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};

$canManageDevice = !empty($canManageDevice);
$studentDeviceSection = 'sao';
$pageTitle = 'Attendance Warning Letter';
$pageSubtitle = (string) (($student['student_name'] ?? '') . ' · ' . ($student['student_id'] ?? '') . ' · ' . ($meta['month_label'] ?? ''));

ob_start();
?>
<div class="sd-header-actions">
    <a class="btn btn-primary" href="<?php echo $e($printUrl); ?>" target="_blank" rel="noopener">
        <i class="fas fa-print me-1"></i>Print
    </a>
    <a class="btn btn-outline-danger" href="<?php echo $e($pdfUrl); ?>">
        <i class="fas fa-file-pdf me-1"></i>PDF
    </a>
    <a class="btn btn-outline-secondary" href="<?php echo $e($urls['sao'] . '?run=1&report_month=' . rawurlencode((string) $reportMonth)); ?>">
        Back to dashboard
    </a>
</div>
<?php
$headerActions = ob_get_clean();

ob_start();
?>
<style><?php echo $letterCss ?? ''; ?></style>
<div class="card sd-card">
    <div class="card-body">
        <div class="awl-preview-sheet">
            <?php echo $letterHtml; ?>
        </div>
    </div>
</div>
<style>
.awl-preview-sheet {
    max-width: 210mm;
    margin: 0 auto;
    background: #fff;
    padding: 8mm 10mm;
    border: 1px solid #e2e8f0;
}
</style>
<?php
$contentHtml = ob_get_clean();
$contentPartial = null;
include __DIR__ . '/partials/shell.php';
