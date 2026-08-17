<?php
/**
 * Text-only portion for compact QR sticker preview (no table/QR img wrapper).
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
<?php if ($showSlgti): ?><div class="slgti">SLGTI</div><?php endif; ?>
<div class="asset-id"><?php echo $e($idText); ?></div>
<?php if ($showMeta && $assetTag !== ''): ?><div class="meta">Tag: <?php echo $e($assetTag); ?></div><?php endif; ?>
<?php if ($showMeta && $serial !== ''): ?><div class="meta">S/N: <?php echo $e($serial); ?></div><?php endif; ?>
<?php if ($showBrand && $line2 !== ''): ?><div class="brand"><?php echo $e($line2); ?></div><?php endif; ?>
