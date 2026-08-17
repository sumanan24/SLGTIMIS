<?php
/**
 * PDF sticker cell — QR left · Asset No. + Serial Number right only.
 */
if (!class_exists('DeviceAssetHelper', false)) {
    require_once BASE_PATH . '/helpers/DeviceAssetHelper.php';
}
$cfg = $labelConfig ?? DeviceAssetHelper::labelConfig();
$assetNo = trim((string) ($assetId ?? '')) ?: '—';
$serialNo = trim((string) ($serial ?? '')) ?: '—';
$cellQrMm = (float) ($cfg['qr_size_mm'] ?? 17.0);
$padMm = (float) ($cfg['inner_padding_mm'] ?? 1.5);
$gapMm = (float) ($cfg['text_gap_mm'] ?? 1.5);
?>
<div class="sticker-card" style="padding:<?php echo $padMm; ?>mm;">
    <table class="sticker-inner" cellpadding="0" cellspacing="0">
        <tr>
            <td class="sticker-qr" style="width:<?php echo round($cellQrMm + 1, 1); ?>mm;padding:0.5mm;">
                <?php if (!empty($qrDataUri)): ?>
                <img src="<?php echo $qrDataUri; ?>" alt="QR" style="width:<?php echo $cellQrMm; ?>mm;height:<?php echo $cellQrMm; ?>mm;display:block;">
                <?php endif; ?>
            </td>
            <td class="sticker-text" style="padding-left:<?php echo $gapMm; ?>mm;">
                <div class="asset-no">A/N <?php echo $e($assetNo); ?></div>
                <div class="serial-no">S/N <?php echo $e($serialNo); ?></div>
            </td>
        </tr>
    </table>
</div>
