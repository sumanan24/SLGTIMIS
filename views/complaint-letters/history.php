<?php
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
?>
<div class="container-fluid px-3 px-md-4 py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">Complaint Letter History</h1>
            <p class="text-muted small mb-0">Audit log of create, update, delete, generate, and print actions.</p>
        </div>
        <a href="<?php echo APP_URL; ?>/complaint-letters" class="btn btn-outline-secondary btn-sm">Back to list</a>
    </div>

    <div class="table-responsive bg-white border rounded">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Date/Time</th>
                    <th>Action</th>
                    <th>Reference</th>
                    <th>User</th>
                    <th>Role</th>
                    <th>Department</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($auditLogs)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No audit records.</td></tr>
                <?php else: foreach ($auditLogs as $log): ?>
                <tr>
                    <td><?php echo $e($log['created_at'] ?? ''); ?></td>
                    <td><?php echo $e(ucfirst(str_replace('_', ' ', (string)($log['action'] ?? '')))); ?></td>
                    <td>
                        <?php if (!empty($log['complaint_letter_id'])): ?>
                        <a href="<?php echo APP_URL; ?>/complaint-letters/view?id=<?php echo (int)$log['complaint_letter_id']; ?>"><?php echo $e($log['reference_no'] ?? ('#' . (int)$log['complaint_letter_id'])); ?></a>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td><?php echo $e($log['user_name'] ?? ''); ?></td>
                    <td><?php echo $e($log['user_role'] ?? ''); ?></td>
                    <td><?php echo $e($log['department_id'] ?? '—'); ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
