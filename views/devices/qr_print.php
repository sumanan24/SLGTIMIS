<?php
require_once BASE_PATH . '/helpers/DeviceAssetHelper.php';
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
$d = $device ?? [];
$cfg = $labelConfig ?? DeviceAssetHelper::labelConfig();
$assetId = trim((string) ($d['asset_id'] ?? ''));
$assetTag = trim((string) ($d['asset_tag_no'] ?? ''));
$serial = trim((string) ($d['serial_number'] ?? ''));
$line2 = trim((string) ($d['brand'] ?? '') . ' ' . (string) ($d['model'] ?? ''));
$copies = max(2, (int) ($labelCopies ?? DeviceAssetHelper::labelsPerSet()));
$sets = DeviceAssetHelper::clampLabelSets((int) ($labelSets ?? DeviceAssetHelper::defaultLabelSets()));
$pageW = DeviceAssetHelper::stripWidthIn();
$pageH = DeviceAssetHelper::labelHeightIn();
$labelW = DeviceAssetHelper::labelWidthIn();
$labelH = DeviceAssetHelper::labelHeightIn();
$printerModel = (string) ($cfg['printer_model'] ?? 'Zebra ZD230');
?>
<style>
@media screen {
    .no-print.toolbar {
        position: fixed; top: 0; left: 0; right: 0; z-index: 10;
        background: #f8f9fa; border-bottom: 1px solid #dee2e6;
        padding: 10px; text-align: center; font: 14px/1.4 Arial, sans-serif;
    }
    .no-print.toolbar p { margin: 0 0 8px; color: #666; font-size: 13px; }
    .no-print.toolbar .btn {
        display: inline-block; margin: 0 4px; padding: 6px 14px; font-size: 13px;
        text-decoration: none; border-radius: 4px; border: 1px solid transparent; cursor: pointer;
    }
    .no-print.toolbar .btn-primary { background: #0d6efd; color: #fff; border-color: #0d6efd; }
    .no-print.toolbar .btn-outline { background: #fff; color: #212529; border-color: #212529; }
    .no-print.toolbar .btn-zebra { background: #111; color: #fff; border-color: #111; }
    .screen-preview { margin-top: 96px; padding: 16px; text-align: center; background: #eee; min-height: 100vh; }
}
@media print {
    .no-print { display: none !important; }
    .screen-preview { margin: 0 !important; padding: 0 !important; background: #fff !important; min-height: 0 !important; }
    .label-set { page-break-after: always; width: <?php echo $pageW; ?>in !important; height: <?php echo $pageH; ?>in !important; }
    .label-set:last-child { page-break-after: auto; }
    .device-qr-sticker-pair { border: none !important; background: #fff !important; padding: 0 !important; width: <?php echo $pageW; ?>in !important; height: <?php echo $pageH; ?>in !important; }
    .device-qr-sticker-card { border-radius: 0 !important; box-shadow: none !important; outline: none !important; }
}
.device-qr-preview-grid { display: flex; flex-direction: column; gap: 12px; align-items: center; }
.device-qr-sticker-pair {
    display: inline-flex;
    gap: 0;
    padding: 8px;
    background: #e9ecef;
    border: 1px dashed #adb5bd;
    border-radius: 4px;
    width: <?php echo round($pageW * 100); ?>px;
}
.device-qr-sticker-card {
    width: <?php echo round($labelW * 100); ?>px;
    height: <?php echo round($labelH * 100); ?>px;
    border: 1px solid #212529;
    border-radius: 6px;
    background: #fff;
    padding: 6px 8px;
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 6px;
    box-shadow: 0 1px 2px rgba(0,0,0,.08);
    overflow: hidden;
    box-sizing: border-box;
}
.device-qr-sticker-card .qr-img { width: 64px; height: 64px; flex: 0 0 64px; object-fit: contain; }
.device-qr-sticker-card .qr-text { flex: 1; min-width: 0; text-align: left; }
.label-2x1 .slgti { font: 700 10px/1.05 Arial, sans-serif; color: #444; margin: 0; }
.label-2x1 .asset-id { font: 800 13px/1.1 Arial, sans-serif; margin: 0; word-break: break-all; }
@media print {
    .device-qr-sticker-card {
        width: <?php echo $labelW; ?>in;
        height: <?php echo $labelH; ?>in;
        border: none;
        border-right: 1px solid #ccc;
        border-radius: 0;
        padding: 0.06in 0.08in;
    }
    .device-qr-sticker-card:last-child { border-right: none; }
    .device-qr-sticker-card .qr-img { width: 0.68in; height: 0.68in; flex: 0 0 0.68in; }
}
</style>
<div class="no-print toolbar">
    <p><strong><?php echo $e($printerModel); ?></strong> — Full page <strong>4″ × 1″</strong> (two 2″ × 1″ labels) · <?php echo $sets; ?> set(s)</p>
    <button type="button" class="btn btn-primary" onclick="window.print()">Print</button>
    <a href="<?php echo APP_URL; ?>/devices/qr-pdf?id=<?php echo (int)($d['id'] ?? 0); ?>&amp;sets=<?php echo $sets; ?>" class="btn btn-outline">Download PDF</a>
    <a href="<?php echo APP_URL; ?>/devices/qr-zpl?id=<?php echo (int)($d['id'] ?? 0); ?>&amp;sets=<?php echo $sets; ?>" class="btn btn-zebra">Download ZPL</a>
</div>
<div class="screen-preview">
    <div class="device-qr-preview-grid">
        <?php for ($s = 0; $s < $sets; $s++): ?>
        <div class="label-set">
            <div class="device-qr-sticker-pair">
                <?php for ($i = 0; $i < $copies; $i++): ?>
                <div class="device-qr-sticker-card label-2x1">
                    <?php if (!empty($qrDataUri)): ?><img class="qr-img" src="<?php echo $qrDataUri; ?>" alt="QR"><?php endif; ?>
                    <div class="qr-text">
                        <?php require BASE_PATH . '/views/devices/partials/qr_label_text_only.php'; ?>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
        </div>
        <?php endfor; ?>
    </div>
</div>
