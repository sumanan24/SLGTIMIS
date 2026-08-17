<?php
/**
 * 2" × 1" device QR label (Dompdf).
 * @var array<string, mixed> $device
 */
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
$d = $device ?? [];
$assetId = trim((string) ($d['asset_id'] ?? ''));
$assetTag = trim((string) ($d['asset_tag_no'] ?? ''));
$serial = trim((string) ($d['serial_number'] ?? ''));
$line2 = trim((string) ($d['brand'] ?? '') . ' ' . (string) ($d['model'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        @page { size: 2in 1in; margin: 0; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 6pt; color: #111; }
        .label {
            width: 2in;
            height: 1in;
            padding: 0.04in 0.05in;
            overflow: hidden;
        }
        table.layout { width: 100%; height: 0.92in; border-collapse: collapse; }
        table.layout td { vertical-align: middle; }
        td.qr { width: 0.78in; text-align: center; }
        td.qr img { width: 0.72in; height: 0.72in; }
        td.text { padding-left: 0.04in; }
        .asset-id { font-size: 8pt; font-weight: bold; line-height: 1.1; }
        .meta { font-size: 5.5pt; line-height: 1.15; margin-top: 1pt; color: #333; }
        .brand { font-size: 5pt; color: #555; margin-top: 1pt; }
        .slgti { font-size: 4.5pt; font-weight: bold; letter-spacing: 0.3pt; color: #444; margin-bottom: 1pt; }
    </style>
</head>
<body>
    <div class="label">
        <table class="layout">
            <tr>
                <td class="qr">
                    <?php if (!empty($qrDataUri)): ?>
                    <img src="<?php echo $qrDataUri; ?>" alt="QR">
                    <?php endif; ?>
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
</body>
</html>
