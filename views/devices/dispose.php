<?php
$e = static function (?string $s): string {
    return htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
};
?>
<div class="container-fluid px-3 px-md-4 devices-page-wrap">
    <?php require BASE_PATH . '/views/devices/_styles.php'; ?>
    <h1 class="asset-title">Dispose Assets</h1>
    <p class="asset-help">Ask for an asset to be removed from use. After approval it stays in the history. It is not deleted.</p>
    <?php require BASE_PATH . '/views/devices/_nav.php'; ?>
    <?php if (!empty($_SESSION['success'])): ?><div class="alert alert-success"><?php echo $e($_SESSION['success']); unset($_SESSION['success']); ?></div><?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?><div class="alert alert-danger"><?php echo $e($_SESSION['error']); unset($_SESSION['error']); ?></div><?php endif; ?>

    <?php if (!empty($canManage)): ?>
    <form method="post" action="<?php echo APP_URL; ?>/devices/dispose-save" class="card border-0 shadow-sm mb-3" onsubmit="return confirm('Send this disposal request?');">
        <div class="card-body p-4">
            <label class="form-label">Which asset?</label>
            <select name="device_id" class="form-select form-select-lg mb-3" required>
                <option value="">Choose an asset</option>
                <?php foreach ($devices ?? [] as $row): if (($row['status'] ?? '') === 'disposed') continue; ?>
                <option value="<?php echo (int) $row['id']; ?>"><?php echo $e(($row['asset_name'] ?: $row['asset_id']) . ' — ' . $row['asset_id']); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="form-label">Reason</label>
            <select name="reason" class="form-select form-select-lg mb-3" required>
                <option value="">Choose a reason</option>
                <?php foreach ($reasons ?? [] as $reason): ?>
                <option value="<?php echo $e($reason); ?>"><?php echo $e($reason); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="form-label">Remarks</label>
            <textarea name="remarks" class="form-control mb-3" rows="2"></textarea>
            <div class="d-flex gap-2">
                <button class="btn btn-primary btn-lg" type="submit">Submit Request</button>
                <a class="btn btn-link btn-lg" href="<?php echo APP_URL; ?>/devices/list">Back</a>
            </div>
        </div>
    </form>
    <?php endif; ?>

    <div class="card border-0 shadow-sm"><div class="card-body">
        <h2 class="h5">Disposal history</h2>
        <?php if (empty($disposals)): ?><p class="text-muted mb-0">No disposal requests yet.</p><?php endif; ?>
        <?php foreach ($disposals ?? [] as $row): ?>
        <div class="border rounded p-3 mb-2">
            <strong><?php echo $e($row['asset_name'] ?: $row['asset_id']); ?></strong>
            <span class="badge bg-secondary"><?php echo $e(ucfirst((string) ($row['status'] ?? ''))); ?></span>
            <div class="small"><?php echo $e($row['reason'] ?? ''); ?> <?php if (!empty($row['remarks'])): ?>— <?php echo $e($row['remarks']); ?><?php endif; ?></div>
            <?php if (($row['status'] ?? '') === 'waiting' && !empty($canApprove)): ?>
            <form method="post" action="<?php echo APP_URL; ?>/devices/dispose-decide" class="d-flex gap-2 mt-2" onsubmit="return confirm('Save this decision?');">
                <input type="hidden" name="disposal_id" value="<?php echo (int) $row['id']; ?>">
                <button class="btn btn-success" name="decision" value="approved">Approve</button>
                <button class="btn btn-outline-danger" name="decision" value="rejected">Reject</button>
            </form>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div></div>
</div>
