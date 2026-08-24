<?php
if (!class_exists('ComplaintLetterPdfHelper', false)) {
    require_once BASE_PATH . '/helpers/ComplaintLetterPdfHelper.php';
}
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
$c = $complaint ?? [];
$fmtDate = static function (?string $d): string {
    $ts = $d ? strtotime($d) : false;
    return $ts ? date('d F Y', $ts) : '—';
};
$logoSrc = ComplaintLetterPdfHelper::logoDataUri();
$studentRows = $students ?? [];
if ($studentRows === []) {
    $studentRows = [['student_id' => '', 'student_name' => '', 'course_name' => '']];
}
$singlePartial = BASE_PATH . '/views/complaint-letters/partials/letter_body.php';
$clPageMargin = ComplaintLetterPdfHelper::pageMarginCss();
?>
<style>
    <?php echo ComplaintLetterPdfHelper::complaintLetterStylesheet(); ?>
    .cl-preview-wrap { background: #e9ecef; padding: 1.5rem 1rem 2rem; }
    .cl-letter {
        width: 210mm;
        min-height: 297mm;
        margin: 0 auto 1.5rem;
        padding: <?php echo $clPageMargin; ?>;
        font-family: "Times New Roman", Times, serif;
        color: #111;
        background: #fff;
        box-sizing: border-box;
        page-break-after: always;
        page-break-inside: avoid;
        overflow-x: hidden;
    }
    .cl-letter .cl-a4-inner {
        width: 100%;
        max-width: 100%;
    }
    table.cl-postbox { width: 100%; }
    .cl-post-strong { text-transform: uppercase; }
    @media print {
        .no-print { display: none !important; }
        @page { size: A4 portrait; margin: <?php echo $clPageMargin; ?>; }
        html, body { margin: 0; padding: 0; background: #fff; }
        .cl-preview-wrap { padding: 0; background: #fff; }
        .cl-letter {
            width: auto;
            min-height: 0;
            margin: 0;
            padding: 0;
            box-shadow: none !important;
            border: none !important;
            page-break-after: always;
            page-break-inside: avoid;
        }
        .cl-letter:last-child { page-break-after: auto; }
    }
</style>

<div class="no-print text-center mb-3">
    <?php if (empty($autoPrint)): ?>
    <a href="<?php echo APP_URL; ?>/complaint-letters/view?id=<?php echo (int)($c['id'] ?? 0); ?>" class="btn btn-sm btn-outline-secondary">&larr; Back</a>
    <button type="button" class="btn btn-sm btn-primary" onclick="window.print()"><i class="fas fa-print me-1"></i> Print</button>
    <?php endif; ?>
    <p class="text-muted small mt-2 mb-0"><?php echo count($studentRows); ?> separate letter<?php echo count($studentRows) === 1 ? '' : 's'; ?> (one A4 page per student)</p>
</div>

<div class="cl-preview-wrap">
<?php foreach ($studentRows as $student):
    $complaint = $c;
?>
<div class="cl-letter border shadow-sm">
    <?php include $singlePartial; ?>
</div>
<?php endforeach; ?>
</div>

<?php if (!empty($autoPrint)): ?>
<script>window.addEventListener('load', function () { window.print(); });</script>
<?php endif; ?>
