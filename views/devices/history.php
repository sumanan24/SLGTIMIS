<?php
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
?>
<div class="container-fluid px-3 px-md-4 devices-page-wrap">
    <?php require BASE_PATH . '/views/devices/_styles.php'; ?>
    <h1 class="h4 mb-3"><?php echo !empty($device) ? 'History — ' . $e($device['asset_id'] ?? '') : 'Device Audit History'; ?></h1>
    <?php require BASE_PATH . '/views/devices/_nav.php'; ?>

    <?php if (!empty($assignments)): ?>
    <div class="device-detail-section">
        <h3>Assignment History</h3>
        <table class="table table-sm"><thead><tr><th>Employee</th><th>Department</th><th>Issue</th><th>Return</th><th>Active</th></tr></thead><tbody>
        <?php foreach ($assignments as $a): ?>
        <tr><td><?php echo $e($a['staff_name'] ?? ''); ?> (<?php echo $e($a['employee_id'] ?? ''); ?>)</td><td><?php echo $e($a['department_name'] ?? ''); ?></td><td><?php echo $e($a['issue_date'] ?? ''); ?></td><td><?php echo $e($a['return_date'] ?? '—'); ?></td><td><?php echo !empty($a['is_active']) ? 'Yes' : 'No'; ?></td></tr>
        <?php endforeach; ?>
        </tbody></table>
    </div>
    <?php endif; ?>

    <div class="device-detail-section">
        <h3>Audit Log</h3>
        <table class="table table-sm"><thead><tr><th>When</th><th>User</th><th>Action</th><th>Asset</th></tr></thead><tbody>
        <?php if (empty($auditLogs)): ?><tr><td colspan="4" class="text-muted">No entries.</td></tr><?php else: foreach ($auditLogs as $log): ?>
        <tr><td><?php echo $e($log['created_at'] ?? ''); ?></td><td><?php echo $e($log['user_name'] ?? ''); ?></td><td><?php echo $e($log['action'] ?? ''); ?></td><td><?php echo $e($log['asset_id'] ?? '—'); ?></td></tr>
        <?php endforeach; endif; ?>
        </tbody></table>
    </div>
</div>
