<?php
/**
 * Single sticker inner markup (one column of the 2-up strip).
 * Vars: $e, $assetId, $assetTag, $serial, $line2, $qrDataUri, $labelConfig
 */
if (!class_exists('DeviceAssetHelper', false)) {
    require_once BASE_PATH . '/helpers/DeviceAssetHelper.php';
}
$cfg = $labelConfig ?? DeviceAssetHelper::labelConfig();
$showSlgti = !empty($cfg['show_slgti']);
$showPrefix = !empty($cfg['show_asset_id_prefix']);
$showMeta = !empty($cfg['show_tag_serial']);
$showBrand = !empty($cfg['show_brand_model']);
$idText = ($showPrefix ? 'Asset ID: ' : '') . ($assetId !== '' ? $assetId : '—');
?>
<table class="label-layout">
    <tr>
        <td class="qr">
            <?php if (!empty($qrDataUri)): ?><img src="<?php echo $qrDataUri; ?>" alt="QR"><?php endif; ?>
        </td>
        <td class="text">
            <?php if ($showSlgti): ?><div class="slgti">SLGTI</div><?php endif; ?>
            <div class="asset-id"><?php echo $e($idText); ?></div>
            <?php if ($showMeta && $assetTag !== ''): ?><div class="meta">Tag: <?php echo $e($assetTag); ?></div><?php endif; ?>
            <?php if ($showMeta && $serial !== ''): ?><div class="meta">S/N: <?php echo $e($serial); ?></div><?php endif; ?>
            <?php if ($showBrand && $line2 !== ''): ?><div class="brand"><?php echo $e($line2); ?></div><?php endif; ?>
        </td>
    </tr>
</table>
