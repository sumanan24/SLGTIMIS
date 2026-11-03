<?php
$e = static function (?string $s): string {
    return htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
};
$d = $device ?? null;
$id = (int) ($d['id'] ?? 0);
?>
<div class="container-fluid px-3 px-md-4 devices-page-wrap">
    <?php require BASE_PATH . '/views/devices/_styles.php'; ?>
    <h1 class="asset-title">Update Asset Condition</h1>
    <p class="asset-help">Choose the asset, pick its condition, and save. The old condition is kept in the history.</p>
    <?php require BASE_PATH . '/views/devices/_nav.php'; ?>
    <?php if (!empty($_SESSION['error'])): ?><div class="alert alert-danger"><?php echo $e($_SESSION['error']); unset($_SESSION['error']); ?></div><?php endif; ?>

    <form method="post" enctype="multipart/form-data" action="<?php echo APP_URL; ?>/devices/condition-save" class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <label class="form-label">Which asset?</label>
            <select name="device_id" class="form-select form-select-lg mb-3" required onchange="if (this.value) location.href='<?php echo APP_URL; ?>/devices/condition?id=' + this.value;">
                <option value="">Choose an asset</option>
                <?php foreach ($devices ?? [] as $row): ?>
                <option value="<?php echo (int) $row['id']; ?>" <?php echo (int) $row['id'] === $id ? 'selected' : ''; ?>><?php echo $e(($row['asset_name'] ?: $row['asset_id']) . ' — ' . $row['asset_id']); ?></option>
                <?php endforeach; ?>
            </select>
            <?php if ($d): ?>
            <div class="d-flex gap-3 mb-3">
                <?php if (!empty($d['photo_path'])): ?><img src="<?php echo APP_URL . '/' . $e($d['photo_path']); ?>" alt="" class="asset-photo"><?php endif; ?>
                <div>
                    <h2 class="h5 mb-1"><?php echo $e($d['asset_name'] ?: $d['asset_id']); ?></h2>
                    <div>Current condition: <strong><?php echo $e($d['asset_condition'] ?: 'Good'); ?></strong></div>
                </div>
            </div>
            <label class="form-label">Condition</label>
            <div class="d-flex flex-wrap gap-2 mb-3">
                <?php foreach ($conditions ?? [] as $cond): ?>
                <label class="asset-choice"><input type="radio" name="asset_condition" value="<?php echo $e($cond); ?>" <?php echo ($d['asset_condition'] ?? 'Good') === $cond ? 'checked' : ''; ?> required> <?php echo $e($cond); ?></label>
                <?php endforeach; ?>
            </div>
            <label class="form-label">Remarks</label>
            <textarea name="remarks" class="form-control mb-3" rows="2" placeholder="What did you notice?"></textarea>
            <label class="form-label">Photo (optional)</label>
            <input type="file" name="photo" class="form-control form-control-lg mb-3" accept="image/jpeg,image/png,image/webp">
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-primary btn-lg" type="submit">Save Condition</button>
                <a class="btn btn-link btn-lg" href="<?php echo APP_URL; ?>/devices/list">Back</a>
            </div>
            <?php endif; ?>
        </div>
    </form>
    <?php if (!empty($history)): ?>
    <div class="card border-0 shadow-sm mt-3"><div class="card-body">
        <h2 class="h5">Previous conditions</h2>
        <ul class="list-group list-group-flush">
            <?php foreach ($history as $row): ?>
            <li class="list-group-item px-0"><?php echo $e($row['asset_condition'] ?: 'Updated'); ?> · <?php echo $e($row['recorded_at'] ?? ''); ?> <?php if (!empty($row['remarks'])): ?><span class="text-muted">— <?php echo $e($row['remarks']); ?></span><?php endif; ?></li>
            <?php endforeach; ?>
        </ul>
    </div></div>
    <?php endif; ?>
</div>
