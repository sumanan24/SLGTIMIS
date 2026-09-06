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

<?php
$passwordConfigured = !empty($passwordConfigured);
$machineUsername = (string) ($machineUsername ?? 'admin');
?>
<?php if (!empty($canManageDevice) && !$passwordConfigured): ?>
    <div class="alert alert-danger mb-3">
        <div class="fw-semibold mb-1">Machines cannot go online — password missing on this server</div>
        <p class="small mb-3 mb-md-2">
            Production does not get secrets from git. Enter the same admin password you use at
            <code>http://172.16.0.26</code> (MAIN + all readers). It is saved only on this server
            (<code>config/student_attendance_machine.local.php</code>).
        </p>
        <form method="post" action="<?php echo $e($urls['save_credentials'] ?? '#'); ?>" class="row g-2 align-items-end">
            <input type="hidden" name="auto_test" value="1">
            <div class="col-md-2">
                <label class="form-label small mb-1">Username</label>
                <input type="text" name="machine_username" class="form-control" value="<?php echo $e($machineUsername); ?>" autocomplete="username">
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Device admin password</label>
                <input type="password" name="machine_password" class="form-control" required autocomplete="new-password" placeholder="Hikvision web login">
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Confirm password</label>
                <input type="password" name="machine_password_confirm" class="form-control" required autocomplete="new-password">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-danger w-100">
                    <i class="fas fa-key me-1"></i>Save password &amp; test machines
                </button>
            </div>
        </form>
    </div>
<?php elseif (!empty($canManageDevice) && $passwordConfigured): ?>
    <div class="alert alert-light border mb-3 py-2">
        <form method="post" action="<?php echo $e($urls['save_credentials'] ?? '#'); ?>" class="row g-2 align-items-end">
            <input type="hidden" name="auto_test" value="1">
            <div class="col-12">
                <span class="small text-muted">Update device password on this server (if terminals were reconfigured):</span>
            </div>
            <div class="col-md-2">
                <input type="text" name="machine_username" class="form-control form-control-sm" value="<?php echo $e($machineUsername); ?>" autocomplete="username" aria-label="Username">
            </div>
            <div class="col-md-3">
                <input type="password" name="machine_password" class="form-control form-control-sm" required autocomplete="new-password" placeholder="New device password" aria-label="Password">
            </div>
            <div class="col-md-3">
                <input type="password" name="machine_password_confirm" class="form-control form-control-sm" required autocomplete="new-password" placeholder="Confirm" aria-label="Confirm password">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-sm btn-outline-secondary w-100">Update password &amp; re-test</button>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php if (!empty($syncSummary)): ?>
    <div class="alert alert-info mb-3">
        <div class="fw-semibold mb-2"><?php echo $e($syncSummary['message'] ?? 'Synchronization Completed'); ?></div>
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
                        events <?php echo (int) ($sd['records_retrieved'] ?? 0); ?>,
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
<?php if (!empty($deviceCards)): ?>
<div class="alert alert-light border mb-3 py-2 small">
    LAN mode: Hikvision devices use private IPs only (no Internet check).
    SIS on LAN: <code><?php echo $e($sisPublicHost ?? 'sis.slgti.ac.lk'); ?></code>
    → <a href="<?php echo $e($sisLanUrl ?? 'http://172.16.1.245'); ?>"><?php echo $e($sisLanIp ?? '172.16.1.245'); ?></a>
    (set hosts/DNS or open the IP URL if Internet DNS is down).
</div>
<?php endif; ?>

<?php if ($deviceCards !== []): ?>
<div class="card sd-card mb-3">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="fw-semibold">Hikvision machines (MAIN + readers)</span>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-sm btn-outline-primary" href="<?php echo $e($urls['test'] ?? '#'); ?>">
                <i class="fas fa-plug me-1"></i>Test all
            </a>
            <form method="post" action="<?php echo $e($urls['refresh_users'] ?? '#'); ?>" class="d-inline">
                <button type="submit" class="btn btn-sm btn-primary"
                        onclick="return confirm('Pull student/user lists from MAIN and all readers into the database?');">
                    <i class="fas fa-users me-1"></i>Get users from all machines
                </button>
            </form>
            <a class="btn btn-sm btn-outline-secondary" href="<?php echo $e($urls['devices'] ?? '#'); ?>">Device sync</a>
            <a class="btn btn-sm btn-outline-dark" href="<?php echo $e(rtrim(APP_URL, '/') . '/hikvision'); ?>">Hikvision LAN test</a>
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
                        <?php if (!empty($dc['last_synced'])): ?>
                            <div class="sd-device-mini-sync">Users synced: <?php echo $e($dc['last_synced']); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($dc['reason']) && $pillLabel !== 'ONLINE'): ?>
                            <div class="sd-device-mini-msg"><strong><?php echo $e($dc['reason']); ?></strong></div>
                        <?php endif; ?>
                        <?php if (!empty($dc['message'])): ?>
                            <?php
                            $msg = (string) $dc['message'];
                            if (stripos($msg, 'locked') !== false) {
                                $msg = 'Admin locked — wait ~15–20 min or reboot this terminal, then Test once.';
                            } elseif (strlen($msg) > 120) {
                                $msg = substr($msg, 0, 117) . '…';
                            }
                            ?>
                            <div class="sd-device-mini-msg"><?php echo $e($msg); ?></div>
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
            <div class="sd-label">Today's punches</div>
            <div class="sd-value"><?php echo (int) $todayCount; ?></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="sd-stat">
            <div class="sd-label">Unique students today</div>
            <div class="sd-value"><?php echo (int) $uniqueToday; ?></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="sd-stat">
            <div class="sd-label">Total punch records</div>
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
            <div class="sd-meta">Last punch sync: <?php echo $e($lastSync['ended_at'] ?? 'Never'); ?></div>
        </div>
    </div>
</div>

<div class="card sd-card mb-3">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <span class="fw-semibold">Synchronize punches from all 4 machines</span>
            <div class="small text-muted">Collects finger attendance (AcsEvent) from MAIN + readers, updates finger IDs, saves In/Out.</div>
        </div>
        <a class="btn btn-sm btn-outline-primary" href="<?php echo $e($urls['test']); ?>">
            <i class="fas fa-plug me-1"></i>Test all machines
        </a>
    </div>
    <div class="card-body">
        <div class="row g-3 sd-sync-grid align-items-end">
            <div class="col-sm-6 col-lg-3">
                <form method="post" action="<?php echo $e($urls['sync']); ?>">
                    <input type="hidden" name="sync_mode" value="today">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-calendar-day me-1"></i>Sync today (all)
                    </button>
                </form>
            </div>
            <div class="col-sm-6 col-lg-3">
                <form method="post" action="<?php echo $e($urls['sync']); ?>"
                      onsubmit="return confirm('Pull full history from MAIN + all readers. This may take several minutes. Continue?');">
                    <input type="hidden" name="sync_mode" value="full">
                    <button type="submit" class="btn btn-dark w-100">
                        <i class="fas fa-database me-1"></i>Sync full history (all)
                    </button>
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
                        <button type="submit" class="btn btn-outline-primary w-100">Sync range (all)</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card sd-card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <span class="fw-semibold">Recent attendance</span>
            <div class="sd-legend mt-1">
                <span><i class="dot in"></i>In</span>
                <span><i class="dot out"></i>Out</span>
                <span><i class="dot other"></i>Others</span>
            </div>
        </div>
        <a class="btn btn-sm btn-outline-secondary" href="<?php echo $e($urls['events']); ?>">View all events</a>
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
                <tr><td colspan="6" class="text-center text-muted py-4">No attendance yet. Run a sync to pull events.</td></tr>
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
