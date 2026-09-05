<?php
declare(strict_types=1);
/** @var string $letterHtml */
/** @var string $letterCss */
/** @var string $pdfUrl */
/** @var string $backUrl */
$e = static function ($v): string {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};
?>
<style>
<?php echo $letterCss ?? ''; ?>
@media print {
    body { background: #fff !important; }
    .no-print { display: none !important; }
}
.awl-toolbar {
    padding: 12px 16px;
    background: #fff;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    align-items: center;
}
.awl-sheet {
    max-width: 210mm;
    margin: 16px auto;
    background: #fff;
    padding: 15mm 20mm;
    box-shadow: 0 1px 8px rgba(0, 0, 0, 0.08);
}
</style>
<div class="awl-toolbar no-print">
    <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
        <i class="fas fa-print me-1"></i>Print
    </button>
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo $e($pdfUrl); ?>">
        <i class="fas fa-file-pdf me-1"></i>PDF
    </a>
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo $e($backUrl); ?>">Back</a>
</div>
<div class="awl-sheet">
    <?php echo $letterHtml; ?>
</div>
<script>
window.addEventListener('load', function () {
    setTimeout(function () { window.print(); }, 350);
});
</script>
