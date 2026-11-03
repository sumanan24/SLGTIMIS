<?php
$e = static function (?string $s): string {
    return htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
};
$labels = ['waiting' => 'Waiting for Approval', 'approved' => 'Approved', 'rejected' => 'Rejected', 'completed' => 'Completed'];
?>
<div class="container-fluid px-3 px-md-4 devices-page-wrap">
    <?php require BASE_PATH . '/views/devices/_styles.php'; ?>
    <h1 class="asset-title">Asset Transfer</h1>
    <p class="asset-help">Send an asset to another department. An authorized officer approves it. The receiving department confirms when it arrives.</p>
    <?php require BASE_PATH . '/views/devices/_nav.php'; ?>
    <?php if (!empty($_SESSION['success'])): ?><div class="alert alert-success"><?php echo $e($_SESSION['success']); unset($_SESSION['success']); ?></div><?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?><div class="alert alert-danger"><?php echo $e($_SESSION['error']); unset($_SESSION['error']); ?></div><?php endif; ?>

    <?php if (!empty($canManage)): ?>
    <form method="post" action="<?php echo APP_URL; ?>/devices/transfer-save" class="card border-0 shadow-sm mb-3">
        <div class="card-body p-4">
            <label class="form-label">Which asset?</label>
            <select name="device_id" class="form-select form-select-lg mb-3" required>
                <option value="">Choose an asset</option>
                <?php foreach ($devices ?? [] as $row): ?>
                <option value="<?php echo (int) $row['id']; ?>"><?php echo $e(($row['asset_name'] ?: $row['asset_id']) . ' — ' . $row['asset_id']); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="form-label">Which department should receive it?</label>
            <select name="to_department_id" class="form-select form-select-lg mb-3" required>
                <option value="">Choose a department</option>
                <?php foreach ($departments ?? [] as $dep): ?>
                <option value="<?php echo $e($dep['department_id'] ?? ''); ?>"><?php echo $e($dep['department_name'] ?? ''); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="form-label">New room / location</label>
            <input name="to_location" class="form-control form-control-lg mb-3" placeholder="Example: Store room">
            <label class="form-label">Reason for transfer</label>
            <textarea name="reason" class="form-control mb-3" rows="2" required></textarea>
            <div class="d-flex gap-2">
                <button class="btn btn-primary btn-lg" type="submit" onclick="return confirm('Send this transfer request?');">Send Request</button>
                <a class="btn btn-link btn-lg" href="<?php echo APP_URL; ?>/devices/list">Back</a>
            </div>
        </div>
    </form>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h5">Transfer requests</h2>
            <?php if (empty($transfers)): ?><p class="text-muted mb-0">No transfer requests yet.</p><?php endif; ?>
            <?php foreach ($transfers ?? [] as $t): $status = (string) ($t['status'] ?? 'waiting'); ?>
            <div class="border rounded p-3 mb-2">
                <strong><?php echo $e($t['asset_name'] ?: $t['asset_id']); ?></strong>
                <span class="badge bg-secondary"><?php echo $e($labels[$status] ?? $status); ?></span>
                <div class="small"><?php echo $e(($t['from_department_name'] ?: 'Unknown') . ' → ' . ($t['to_department_name'] ?? '')); ?></div>
                <div class="small text-muted"><?php echo $e($t['reason'] ?? ''); ?></div>
                <?php if ($status === 'waiting' && !empty($canApprove)): ?>
                <form method="post" action="<?php echo APP_URL; ?>/devices/transfer-decide" class="d-flex gap-2 mt-2" onsubmit="return confirm('Save this decision?');">
                    <input type="hidden" name="transfer_id" value="<?php echo (int) $t['id']; ?>">
                    <button class="btn btn-success" name="decision" value="approved">Approve</button>
                    <button class="btn btn-outline-danger" name="decision" value="rejected">Reject</button>
                </form>
                <?php elseif ($status === 'approved' && !empty($canManage)): ?>
                <form method="post" action="<?php echo APP_URL; ?>/devices/transfer-confirm" class="mt-2" onsubmit="return confirm('Confirm that your department received this asset?');">
                    <input type="hidden" name="transfer_id" value="<?php echo (int) $t['id']; ?>">
                    <button class="btn btn-primary">Confirm Receipt</button>
                </form>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
