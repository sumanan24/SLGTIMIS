<?php
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
$d = $device ?? [];
$assetId = trim((string) ($d['asset_id'] ?? ''));
$assetTag = trim((string) ($d['asset_tag_no'] ?? ''));
$serial = trim((string) ($d['serial_number'] ?? ''));
$line2 = trim((string) ($d['brand'] ?? '') . ' ' . (string) ($d['model'] ?? ''));
?>
<style>
@page { size: 2in 1in; margin: 0; }
@media print {
    html, body { width: 2in; height: 1in; margin: 0; padding: 0; }
    .no-print { display: none !important; }
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Arial, Helvetica, sans-serif; font-size: 8px; color: #111; background: #f5f5f5; }
.label-2x1 {
    width: 2in;
    height: 1in;
    padding: 0.04in 0.05in;
    background: #fff;
    border: 1px dashed #ccc;
    overflow: hidden;
    margin: 0 auto;
}
@media print {
    .label-2x1 { border: none; margin: 0; }
    body { background: #fff; }
}
.label-2x1 table { width: 100%; height: 0.92in; border-collapse: collapse; }
.label-2x1 td { vertical-align: middle; }
.label-2x1 td.qr { width: 0.78in; text-align: center; }
.label-2x1 td.qr img { width: 0.72in; height: 0.72in; }
.label-2x1 td.text { padding-left: 0.04in; }
.label-2x1 .asset-id { font-size: 11px; font-weight: 700; line-height: 1.1; }
.label-2x1 .meta { font-size: 8px; line-height: 1.15; margin-top: 1px; color: #333; }
.label-2x1 .brand { font-size: 7px; color: #555; margin-top: 1px; }
.label-2x1 .slgti { font-size: 7px; font-weight: 700; color: #444; margin-bottom: 1px; }
.toolbar { text-align: center; padding: 12px; }
</style>
<div class="no-print toolbar">
    <p class="small text-muted mb-2">Label size: <strong>2″ × 1″</strong> — set paper to 2×1 in your printer dialog if needed.</p>
    <button type="button" class="btn btn-primary btn-sm" onclick="window.print()"><i class="fas fa-print me-1"></i> Print</button>
    <a href="<?php echo APP_URL; ?>/devices/qr-pdf?id=<?php echo (int)($d['id'] ?? 0); ?>" class="btn btn-outline-dark btn-sm"><i class="fas fa-file-pdf me-1"></i> Download PDF</a>
</div>
<div class="label-2x1">
    <table>
        <tr>
            <td class="qr">
                <?php if (!empty($qrDataUri)): ?><img src="<?php echo $qrDataUri; ?>" alt="QR"><?php endif; ?>
            </td>
            <td class="text">
                <div class="slgti">SLGTI</div>
                <div class="asset-id"><?php echo $e($assetId !== '' ? $assetId : '—'); ?></div>
                <?php if ($assetTag !== ''): ?><div class="meta">Tag: <?php echo $e($assetTag); ?></div><?php endif; ?>
                <?php if ($serial !== ''): ?><div class="meta">S/N: <?php echo $e($serial); ?></div><?php endif; ?>
                <?php if ($line2 !== ''): ?><div class="brand"><?php echo $e($line2); ?></div><?php endif; ?>
            </td>
        </tr>
    </table>
</div>
