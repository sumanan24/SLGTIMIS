<?php
declare(strict_types=1);
/** @var array $urls */
/** @var array $logs */
$e = static function ($v): string {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};
$studentDeviceSection = 'logs';
$pageTitle = 'Sync logs';
$pageSubtitle = 'History of device synchronization runs';
?>
<div class="container-fluid px-3 px-sm-4 py-3 student-device-page">
    <?php include __DIR__ . '/partials/styles.php'; ?>
    <div class="row g-3 g-lg-4">
        <div class="col-12 col-md-3 col-lg-2">
            <?php include __DIR__ . '/partials/nav.php'; ?>
        </div>
        <div class="col-12 col-md-9 col-lg-10 min-w-0">
            <div class="mb-3">
                <h4 class="mb-1 fw-bold"><?php echo $e($pageTitle); ?></h4>
                <div class="text-muted small"><?php echo $e($pageSubtitle); ?></div>
            </div>
            <div class="card sd-card">
                <div class="table-responsive">
                    <table class="table table-hover sd-events-table mb-0">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Started</th>
                            <th>Ended</th>
                            <th>Status</th>
                            <th>Range</th>
                            <th class="text-end">Retrieved</th>
                            <th class="text-end">Saved</th>
                            <th class="text-end">Dup</th>
                            <th class="text-end">Unmatched</th>
                            <th class="text-end">Staff</th>
                            <th>Error</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if ($logs === []): ?>
                            <tr><td colspan="12" class="text-center text-muted py-4">No logs yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?php echo (int) ($log['id'] ?? 0); ?></td>
                                    <td><?php echo $e($log['username'] ?? ''); ?></td>
                                    <td class="small"><?php echo $e($log['started_at'] ?? ''); ?></td>
                                    <td class="small"><?php echo $e($log['ended_at'] ?? ''); ?></td>
                                    <td>
                                        <?php
                                        $st = (string) ($log['status'] ?? '');
                                        $badge = $st === 'ok' || $st === 'success' ? 'text-bg-success' : ($st === 'error' ? 'text-bg-danger' : 'text-bg-secondary');
                                        ?>
                                        <span class="badge <?php echo $badge; ?>"><?php echo $e($st); ?></span>
                                    </td>
                                    <td class="small"><?php echo $e(($log['date_from'] ?? '') . ' → ' . ($log['date_to'] ?? '')); ?></td>
                                    <td class="text-end"><?php echo (int) ($log['records_retrieved'] ?? 0); ?></td>
                                    <td class="text-end"><?php echo (int) ($log['saved'] ?? 0); ?></td>
                                    <td class="text-end"><?php echo (int) ($log['duplicates'] ?? 0); ?></td>
                                    <td class="text-end"><?php echo (int) ($log['unmatched'] ?? 0); ?></td>
                                    <td class="text-end"><?php echo (int) ($log['staff_ignored'] ?? 0); ?></td>
                                    <td class="small text-danger"><?php echo $e($log['error_message'] ?? ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
