<?php
/**
 * Single horizontal QR sticker — QR + Asset No. + Serial Number only.
 */
$s = $sticker ?? [];
$e = $e ?? static fn (?string $v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
?>
<div class="device-qr-sticker device-qr-sticker-horizontal">
    <?php if (!empty($s['qrDataUri'])): ?>
    <div class="sticker-qr-wrap">
        <img class="sticker-qr" src="<?php echo $s['qrDataUri']; ?>" alt="QR Code">
    </div>
    <?php endif; ?>
    <div class="sticker-text">
        <div class="sticker-asset-no">A/N <?php echo $e($s['assetId'] ?? '—'); ?></div>
        <div class="sticker-serial">S/N <?php echo $e($s['serial'] ?? '—'); ?></div>
    </div>
</div>
