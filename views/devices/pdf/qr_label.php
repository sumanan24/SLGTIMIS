<?php
/**
 * Device QR PDF — full 4" × 1" page · two 2" × 1" identical stickers side-by-side.
 */
require_once BASE_PATH . '/helpers/DeviceAssetHelper.php';
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
$d = $device ?? [];
$cfg = $labelConfig ?? DeviceAssetHelper::labelConfig();
$assetId = trim((string) ($d['asset_id'] ?? ''));
$assetTag = trim((string) ($d['asset_tag_no'] ?? ''));
$serial = trim((string) ($d['serial_number'] ?? ''));
$line2 = trim((string) ($d['brand'] ?? '') . ' ' . (string) ($d['model'] ?? ''));
$labelsPerSet = DeviceAssetHelper::labelsPerSet();
$sets = DeviceAssetHelper::clampLabelSets((int) ($labelSets ?? DeviceAssetHelper::defaultLabelSets()));

$pageW = DeviceAssetHelper::stripWidthIn();
$pageH = DeviceAssetHelper::labelHeightIn();
$pageWmm = DeviceAssetHelper::inchesToMm($pageW);
$pageHmm = DeviceAssetHelper::inchesToMm($pageH);
$labelW = DeviceAssetHelper::labelWidthIn();
$labelH = DeviceAssetHelper::labelHeightIn();
$labelWmm = DeviceAssetHelper::labelWidthMm();
$labelHmm = DeviceAssetHelper::labelHeightMm();
$qrMm = min(18.0, round($labelWmm * 0.34, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            size: <?php echo round($pageWmm, 2); ?>mm <?php echo round($pageHmm, 2); ?>mm;
            margin: 0;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { font-family: DejaVu Sans, sans-serif; color: #111; }
        .pdf-label-set {
            width: <?php echo $pageW; ?>in;
            height: <?php echo $pageH; ?>in;
            page-break-after: always;
            overflow: hidden;
        }
        .pdf-label-set:last-child { page-break-after: auto; }
        table.pair-row {
            width: <?php echo $pageW; ?>in;
            height: <?php echo $pageH; ?>in;
            border-collapse: collapse;
            table-layout: fixed;
        }
        table.pair-row td {
            vertical-align: middle;
            padding: 0;
            width: <?php echo $labelW; ?>in;
            height: <?php echo $labelH; ?>in;
        }
        .sticker-card {
            width: <?php echo $labelW; ?>in;
            height: <?php echo $labelH; ?>in;
            background: #fff;
            border-right: 0.2mm solid #ccc;
            padding: 0;
            overflow: hidden;
        }
        table.pair-row td:last-child .sticker-card { border-right: none; }
        table.sticker-inner { width: 100%; height: 100%; border-collapse: collapse; }
        table.sticker-inner td { vertical-align: middle; }
        td.sticker-qr { text-align: left; }
        td.sticker-text { text-align: left; }
        .asset-no { font-size: 15px; font-weight: 600; color: #666; line-height: 1.15; margin-bottom: 0.5mm; word-break: break-all; }
        .serial-no { font-size: 15px; font-weight: bold; line-height: 1.15; word-break: break-all; color: #111; }
    </style>
</head>
<body>
<?php for ($s = 0; $s < $sets; $s++): ?>
    <div class="pdf-label-set">
        <table class="pair-row" cellpadding="0" cellspacing="0">
            <tr>
                <?php for ($i = 0; $i < $labelsPerSet; $i++): ?>
                <td>
                    <?php require BASE_PATH . '/views/devices/partials/qr_label_pdf_cell.php'; ?>
                </td>
                <?php endfor; ?>
            </tr>
        </table>
    </div>
<?php endfor; ?>
</body>
</html>
