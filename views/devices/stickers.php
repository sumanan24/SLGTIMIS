<?php
$e = static function (?string $s): string {
    return htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
};
?>
<div class="container-fluid px-3 px-md-4 devices-page-wrap">
    <?php require BASE_PATH . '/views/devices/_styles.php'; ?>
    <h1 class="asset-title">QR Code Stickers</h1>
    <p class="asset-help">Every asset already has a QR code. Print one sticker, or tick several and print them together. Scan the code with a phone to see the asset.</p>
    <?php require BASE_PATH . '/views/devices/_nav.php'; ?>

    <form method="get" action="<?php echo APP_URL; ?>/devices/stickers" class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <?php if (empty($devices)): ?>
            <div class="asset-empty"><p>No assets to print yet.</p></div>
            <?php else: ?>
            <div class="row g-2">
                <?php foreach ($devices as $row): ?>
                <div class="col-md-6">
                    <label class="asset-choice w-100">
                        <input type="checkbox" name="ids[]" value="<?php echo (int) $row['id']; ?>">
                        <?php echo $e(($row['asset_name'] ?: $row['asset_id']) . ' — ' . $row['asset_id']); ?>
                    </label>
                </div>
                <?php endforeach; ?>
            </div>
            <button class="btn btn-primary btn-lg mt-3" type="submit">Show selected stickers</button>
            <?php endif; ?>
        </div>
    </form>

    <?php if (!empty($picked)): ?>
    <div class="d-flex gap-2 mb-3">
        <button class="btn btn-dark btn-lg" type="button" onclick="window.print()">Print stickers</button>
        <a class="btn btn-link btn-lg" href="<?php echo APP_URL; ?>/devices/scan">Scan a QR code</a>
    </div>
    <div class="row g-3 asset-print-sheet">
        <?php foreach ($picked as $device):
            $token = (string) ($device['qr_token'] ?? '');
            $qr = $token !== '' ? DeviceAssetHelper::qrPngDataUri($token, 180) : '';
        ?>
        <div class="col-6 col-md-4">
            <div class="asset-sticker">
                <?php if ($qr !== ''): ?><img src="<?php echo $qr; ?>" alt="QR code"><?php endif; ?>
                <strong><?php echo $e($device['asset_id'] ?? ''); ?></strong>
                <div><?php echo $e($device['asset_name'] ?: ($device['brand'] ?? '')); ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <a class="btn btn-link btn-lg" href="<?php echo APP_URL; ?>/devices/list">Back</a>
</div>
