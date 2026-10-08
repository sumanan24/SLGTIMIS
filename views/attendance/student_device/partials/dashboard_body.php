<?php
declare(strict_types=1);
/** @var array $urls */
/** @var array|null $syncSummary */
/** @var array|null $lastSync */
/** @var array|null $connectionStatus */
/** @var string $machineHost */
/** @var int $todayCount */
/** @var int $uniqueToday */
/** @var int $totalRecords */
/** @var array $recentRows */

$e = static function ($v): string {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};

$connOk = $connectionStatus['ok'] ?? null;
$connClass = $connOk === true ? 'is-on' : ($connOk === false ? 'is-off' : 'is-unknown');
$connLabel = $connOk === true ? 'CONNECTED' : ($connOk === false ? 'DISCONNECTED' : 'NOT TESTED');
?>

<?php if (!empty($syncSummary)): ?>
    <div class="alert alert-info mb-3">
        <div class="fw-semibold mb-2"><?php echo $e($syncSummary['message'] ?? 'Sync finished'); ?></div>
        <div class="row g-2 small">
            <div class="col-6 col-md-3">Machines: <strong><?php echo (int) ($syncSummary['devices_online'] ?? 0); ?>/<?php echo (int) ($syncSummary['devices_total'] ?? 0); ?></strong></div>
            <div class="col-6 col-md-3">Retrieved: <strong><?php echo (int) ($syncSummary['records_retrieved'] ?? 0); ?></strong></div>
            <div class="col-6 col-md-3">Users synced: <strong><?php echo (int) ($syncSummary['machine_users'] ?? 0); ?></strong></div>
            <div class="col-6 col-md-3">Finger IDs: <strong><?php echo (int) ($syncSummary['finger_ids_linked'] ?? 0); ?></strong></div>
            <div class="col-6 col-md-3">Saved: <strong><?php echo (int) ($syncSummary['saved'] ?? 0); ?></strong></div>
            <div class="col-6 col-md-3">Valid students: <strong><?php echo (int) ($syncSummary['valid_student'] ?? 0); ?></strong></div>
            <div class="col-6 col-md-3">Staff ignored: <strong><?php echo (int) ($syncSummary['staff_ignored'] ?? 0); ?></strong></div>
            <div class="col-6 col-md-3">Unmatched: <strong><?php echo (int) ($syncSummary['unmatched'] ?? 0); ?></strong></div>
            <div class="col-6 col-md-3">Duplicates: <strong><?php echo (int) ($syncSummary['duplicates'] ?? 0); ?></strong></div>
        </div>
        <?php if (!empty($syncSummary['devices']) && is_array($syncSummary['devices'])): ?>
            <ul class="mb-0 mt-2 small">
                <?php foreach ($syncSummary['devices'] as $sd): ?>
                    <li>
                        <code><?php echo $e($sd['host'] ?? ''); ?></code>
                        (<?php echo $e($sd['role'] ?? ''); ?>) —
                        <?php echo !empty($sd['ok']) ? 'OK' : 'FAIL'; ?> —
                        records <?php echo (int) ($sd['records_retrieved'] ?? 0); ?>,
                        saved <?php echo (int) ($sd['saved'] ?? 0); ?>,
                        fingers <?php echo (int) ($sd['finger_ids_linked'] ?? 0); ?>
                        <?php if (!empty($sd['message'])): ?>
                            — <?php echo $e($sd['message']); ?>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    <?php unset($_SESSION['student_att_sync_summary']); ?>
<?php endif; ?>

<?php if (!empty($refreshUsersSummary) && is_array($refreshUsersSummary)): ?>
    <div class="alert alert-<?php echo !empty($refreshUsersSummary['ok']) ? 'success' : 'warning'; ?> mb-3">
        <div class="fw-semibold mb-2"><?php echo $e($refreshUsersSummary['message'] ?? 'User directory refresh'); ?></div>
        <?php if (!empty($refreshUsersSummary['devices']) && is_array($refreshUsersSummary['devices'])): ?>
            <ul class="mb-0 small">
                <?php foreach ($refreshUsersSummary['devices'] as $d): ?>
                    <li>
                        <code><?php echo $e($d['host'] ?? ''); ?></code>
                        (<?php echo $e($d['role'] ?? ''); ?>) —
                        <?php echo !empty($d['online']) ? 'ONLINE' : 'OFFLINE'; ?> —
                        <?php echo $e($d['message'] ?? ''); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    <?php unset($_SESSION['student_att_refresh_users']); ?>
<?php endif; ?>

<?php
$deviceCards = $deviceCards ?? [];
?>

<?php if ($deviceCards !== []): ?>
<div class="card sd-card mb-3">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="fw-semibold">Readers</span>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-sm btn-outline-primary" href="<?php echo $e($urls['test'] ?? '#'); ?>">Test</a>
            <form method="post" action="<?php echo $e($urls['refresh_users'] ?? '#'); ?>" class="d-inline">
                <button type="submit" class="btn btn-sm btn-primary"
                        onclick="return confirm('Load users from all readers?');">
                    Load users
                </button>
            </form>
            <a class="btn btn-sm btn-outline-secondary" href="<?php echo $e($urls['devices'] ?? '#'); ?>">Devices</a>
        </div>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <?php foreach ($deviceCards as $dc): ?>
                <?php
                $online = $dc['online'] ?? null;
                $status = strtolower((string) ($dc['status'] ?? ''));
                if ($status === 'online' || $online === true) {
                    $pillClass = 'is-on';
                    $pillLabel = 'ONLINE';
                } elseif ($status === 'auth_error') {
                    $pillClass = 'is-auth';
                    $pillLabel = 'AUTH ERROR';
                } elseif ($status === 'invalid_config') {
                    $pillClass = 'is-unknown';
                    $pillLabel = 'CONFIG';
                } elseif ($online === false || $status === 'offline') {
                    $pillClass = 'is-off';
                    $pillLabel = 'OFFLINE';
                } else {
                    $pillClass = 'is-unknown';
                    $pillLabel = 'UNKNOWN';
                }
                ?>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="sd-device-mini <?php echo $online === true ? 'is-online' : ($online === false ? 'is-offline' : ''); ?>">
                        <div class="sd-device-mini-top">
                            <div>
                                <div class="sd-device-mini-label"><?php echo $e($dc['label'] ?? ''); ?></div>
                                <code class="sd-device-mini-ip"><?php echo $e($dc['host'] ?? ''); ?></code>
                            </div>
                            <span class="sd-status-pill <?php echo $e($pillClass); ?>"><?php echo $e($pillLabel); ?></span>
                        </div>
                        <div class="sd-device-mini-meta">
                            <span><?php echo $e(strtoupper((string) ($dc['role'] ?? ''))); ?></span>
                            <span><strong><?php echo (int) ($dc['users'] ?? 0); ?></strong> users</span>
                        </div>
                        <?php if (!empty($dc['reason']) && $pillLabel !== 'ONLINE'): ?>
                            <div class="sd-device-mini-msg"><strong><?php echo $e($dc['reason']); ?></strong></div>
                        <?php endif; ?>
                        <?php if (!empty($dc['message'])): ?>
                            <?php
                            $msg = (string) $dc['message'];
                            if (stripos($msg, 'Password OK') !== false || stripos($msg, 'LAN check') !== false) {
                                $msg = '';
                            } elseif (stripos($msg, 'locked') !== false) {
                                $msg = 'Locked. Wait or reboot, then Test.';
                            } elseif (strlen($msg) > 120) {
                                $msg = substr($msg, 0, 117) . '…';
                            }
                            ?>
                            <?php if ($msg !== ''): ?>
                            <div class="sd-device-mini-msg"><?php echo $e($msg); ?></div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row g-3 mb-3">
    <div class="col-6 col-xl-3">
        <div class="sd-stat">
            <div class="sd-label">Punches today</div>
            <div class="sd-value"><?php echo (int) $todayCount; ?></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="sd-stat">
            <div class="sd-label">Students today</div>
            <div class="sd-value"><?php echo (int) $uniqueToday; ?></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="sd-stat">
            <div class="sd-label">All punches</div>
            <div class="sd-value"><?php echo (int) $totalRecords; ?></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="sd-stat">
            <div class="sd-label">Main machine</div>
            <div class="sd-value" style="font-size:1.05rem;"><?php echo $e($machineHost); ?></div>
            <div class="sd-meta">
                <span class="sd-status-pill <?php echo $e($connClass); ?>"><?php echo $e($connLabel); ?></span>
                <?php if (!empty($connectionStatus['tested_at'])): ?>
                    · <?php echo $e($connectionStatus['tested_at']); ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card sd-card mb-3">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <span class="fw-semibold">Sync punches</span>
            <div class="small text-muted">All readers. Same dates can be synced again without duplicates.</div>
        </div>
    </div>
    <div class="card-body">
        <div class="row g-3 sd-sync-grid align-items-end">
            <div class="col-sm-6 col-lg-3">
                <form method="post" action="<?php echo $e($urls['sync']); ?>">
                    <input type="hidden" name="sync_mode" value="today">
                    <button type="submit" class="btn btn-primary w-100">Today</button>
                </form>
            </div>
            <div class="col-sm-6 col-lg-3">
                <form method="post" action="<?php echo $e($urls['sync']); ?>"
                      onsubmit="return confirm('Sync full history? This can take several minutes.');">
                    <input type="hidden" name="sync_mode" value="full">
                    <button type="submit" class="btn btn-dark w-100">Full history</button>
                </form>
            </div>
            <div class="col-12 col-lg-6">
                <form method="post" action="<?php echo $e($urls['sync']); ?>" class="row g-2 align-items-end">
                    <input type="hidden" name="sync_mode" value="range">
                    <div class="col-6 col-md-4">
                        <label class="form-label small mb-1">From</label>
                        <input type="date" name="date_from" class="form-control" required value="<?php echo $e(date('Y-m-d', strtotime('-6 days'))); ?>">
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="form-label small mb-1">To</label>
                        <input type="date" name="date_to" class="form-control" required value="<?php echo $e(date('Y-m-d')); ?>">
                    </div>
                    <div class="col-12 col-md-4">
                        <button type="submit" class="btn btn-outline-primary w-100">Sync dates</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card sd-card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <span class="fw-semibold">Latest</span>
            <div class="sd-legend mt-1">
                <span><i class="dot in"></i>In</span>
                <span><i class="dot out"></i>Out</span>
            </div>
        </div>
        <a class="btn btn-sm btn-outline-secondary" href="<?php echo $e($urls['events']); ?>">Open list</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover sd-events-table mb-0">
            <thead>
            <tr>
                <th class="col-id">Student ID</th>
                <th class="col-name">Name</th>
                <th class="col-date">Date</th>
                <th class="col-time text-center">In</th>
                <th class="col-time text-center">Out</th>
                <th class="col-others">Others</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($recentRows)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No punches yet. Use Sync punches above.</td></tr>
            <?php else: ?>
                <?php foreach ($recentRows as $row): ?>
                    <tr>
                        <td class="col-id"><?php echo $e($row['student_id'] ?? ''); ?></td>
                        <td class="col-name"><?php echo $e($row['student_name'] ?? ''); ?></td>
                        <td class="col-date"><?php echo $e($row['attendance_date'] ?? ''); ?></td>
                        <td class="col-time text-center">
                            <?php if (!empty($row['time_in'])): ?>
                                <span class="sd-time-in"><?php echo $e($row['time_in']); ?></span>
                            <?php else: ?>
                                <span class="sd-time-empty">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="col-time text-center">
                            <?php if (!empty($row['time_out'])): ?>
                                <span class="sd-time-out"><?php echo $e($row['time_out']); ?></span>
                            <?php else: ?>
                                <span class="sd-time-empty">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="col-others"><?php echo $e($row['time_others'] ?? '') !== '' ? $e($row['time_others']) : '—'; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
