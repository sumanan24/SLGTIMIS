<?php
$e = static function ($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};
$hostelName = trim((string) ($hostel['name'] ?? 'Hostel'));
$beds = is_array($beds ?? null) ? $beds : [];
$back = APP_URL . '/rooms?hostel_id=' . urlencode((string) ($hostel_id ?? ''));
?>
<style>
.bed-print-bar { display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; margin-bottom: 1rem; }
.bed-sheet { display: flex; flex-direction: column; gap: 8px; align-items: flex-start; }
.bed-pair {
    width: 4in;
    height: 1in;
    display: flex;
    background: #fff;
    break-inside: avoid;
    page-break-after: always;
}
.bed-sticker {
    width: 2in;
    height: 1in;
    box-sizing: border-box;
    border: 1px dashed #94a3b8;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    font-family: Arial, Helvetica, sans-serif;
    font-weight: 800;
    font-size: 16pt;
    line-height: 1;
    letter-spacing: 0.01em;
    color: #0f172a;
}
@media print {
    .top-navbar, .sidebar, .sidebar-overlay, .footer, .bed-print-bar { display: none !important; }
    .main-content, .main-content-wrapper { margin: 0 !important; padding: 0 !important; background: #fff !important; }
    body { background: #fff !important; }
    .bed-sheet { gap: 0; }
    .bed-sticker { border: none; }
    @page { size: 4in 1in; margin: 0; }
}
</style>

<div class="bed-print-bar">
    <a href="<?php echo $e($back); ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Rooms</a>
    <?php if ($beds): ?>
        <button type="button" class="btn btn-dark btn-sm" onclick="window.print()"><i class="fas fa-print me-1"></i>Print stickers</button>
        <a class="btn btn-outline-dark btn-sm" href="<?php echo $e(APP_URL . '/rooms/bed-stickers?hostel_id=' . urlencode((string) ($hostel_id ?? '')) . '&download=1'); ?>"><i class="fas fa-download me-1"></i>Download stickers</a>
        <span class="text-muted small"><?php echo number_format(count($beds)); ?> stickers · T1 beside B1 · 4 in × 1 in · <?php echo $e($hostelName); ?></span>
    <?php endif; ?>
</div>

<?php if (!$beds): ?>
    <div class="alert alert-warning">
        <?php echo $e($hostelName); ?> has no bed numbers yet. Go back to Rooms, keep this hostel selected, and press Create beds.
    </div>
<?php else: ?>
    <div class="bed-sheet">
        <?php
        require_once BASE_PATH . '/helpers/BedStickerPdf.php';
        foreach (BedStickerPdf::pairs($beds) as $pair):
        ?>
            <div class="bed-pair">
                <div class="bed-sticker"><?php echo $e($pair[0]); ?></div>
                <div class="bed-sticker"><?php echo $e($pair[1]); ?></div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
