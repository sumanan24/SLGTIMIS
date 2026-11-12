<?php
$h = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$money = static function ($value) {
    return 'Rs. ' . number_format((float) $value, 2);
};
$summary = $summary ?? [];
$s = static function ($key) use ($summary) {
    return $summary[$key] ?? 0;
};
$months = $months ?? [];
$byType = $by_type ?? [];
$recent = $recent ?? [];
$pending = $pending ?? [];
$lockedMonths = $lockedMonths ?? [];
$canViewPayments = !empty($canViewPayments);
$canManagePayments = !empty($canManagePayments);
$canViewAttendanceReport = !empty($canViewAttendanceReport);
$isFinanceAssistant = !empty($isFinanceAssistant);
$roleLabel = $isFinanceAssistant ? 'Finance Office' : (($user_role ?? '') === 'ACC' ? 'Accounts' : 'Finance');

$chartJson = json_encode([
    'labels' => array_column($months, 'label'),
    'values' => array_map('floatval', array_column($months, 'total')),
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$typeTotal = array_sum(array_column($byType, 'total'));
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
.fd { color: #1e293b; }
.fd-banner {
    background: #0b2545; color: #fff; border-radius: 12px; padding: 1rem 1.2rem; margin-bottom: 1rem;
    display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;
}
.fd-banner h1 { font-size: 1.2rem; font-weight: 700; margin: 0 0 .2rem; }
.fd-banner p { margin: 0; color: #d6e2ef; font-size: .88rem; }
.fd-actions { display: flex; gap: .5rem; flex-wrap: wrap; }
.fd-actions .btn { min-height: 38px; display: inline-flex; align-items: center; }

.fd-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .75rem; margin-bottom: 1rem; }
.fd-kpi { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: .85rem 1rem; }
.fd-kpi span { display: block; color: #64748b; font-size: .7rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; margin-bottom: .25rem; }
.fd-kpi strong { display: block; color: #0b2545; font-size: 1.3rem; line-height: 1.15; font-variant-numeric: tabular-nums; }
.fd-kpi em { display: block; margin-top: .3rem; color: #64748b; font-size: .75rem; font-style: normal; }
.fd-kpi.warn strong { color: #b45309; }

.fd-grid { display: grid; grid-template-columns: 1.4fr 1fr; gap: 1rem; margin-bottom: 1rem; }
.fd-grid-3 { display: grid; grid-template-columns: 1.2fr 1fr 1fr; gap: 1rem; margin-bottom: 1rem; }
.fd-block { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; min-width: 0; }
.fd-block h2 { margin: 0; padding: .75rem 1rem .2rem; font-size: .95rem; font-weight: 700; color: #0b2545; }
.fd-cap { margin: 0; padding: 0 1rem .65rem; color: #64748b; font-size: .78rem; border-bottom: 1px solid #e2e8f0; }
.fd-chart { position: relative; height: 240px; padding: .75rem; }
.fd-empty { padding: 1rem; color: #64748b; margin: 0; font-size: .875rem; }

.fd-table { width: 100%; margin: 0; border-collapse: collapse; }
.fd-table th {
    background: #f8fafc; color: #475569; font-size: .7rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase;
    border-bottom: 1px solid #e2e8f0; padding: .55rem .85rem; text-align: left; white-space: nowrap;
}
.fd-table td { padding: .55rem .85rem; border-bottom: 1px solid #f1f5f9; font-size: .85rem; vertical-align: middle; }
.fd-table tr:last-child td { border-bottom: 0; }
.fd-table .num { text-align: right; font-variant-numeric: tabular-nums; font-weight: 600; white-space: nowrap; }
.fd-sub { display: block; font-size: .75rem; color: #64748b; }
.fd-badge { display: inline-block; padding: .12rem .55rem; border-radius: 999px; font-size: .7rem; font-weight: 600; }
.fd-badge.ok { background: #dcfce7; color: #166534; }
.fd-badge.wait { background: #fef3c7; color: #92400e; }

.fd-types { list-style: none; margin: 0; padding: .35rem 1rem .75rem; }
.fd-types li { padding: .45rem 0; border-bottom: 1px solid #f1f5f9; }
.fd-types li:last-child { border-bottom: 0; }
.fd-types .row1 { display: flex; justify-content: space-between; gap: .75rem; font-size: .84rem; }
.fd-types .row1 b { font-variant-numeric: tabular-nums; white-space: nowrap; }
.fd-bar { height: 6px; background: #eef2f7; border-radius: 99px; margin-top: .3rem; overflow: hidden; }
.fd-bar i { display: block; height: 100%; background: #0b2545; border-radius: 99px; }

.fd-links { display: grid; grid-template-columns: 1fr; gap: .5rem; padding: .85rem 1rem 1rem; }
.fd-links a {
    display: flex; align-items: center; gap: .65rem; padding: .65rem .75rem; border: 1px solid #e2e8f0;
    border-radius: .5rem; text-decoration: none; color: #0b2545; font-weight: 600; font-size: .85rem;
}
.fd-links a:hover { background: #f8fafc; }
.fd-links a i { width: 1.1rem; text-align: center; color: #64748b; }

@media (max-width: 1199.98px) {
    .fd-grid-3 { grid-template-columns: 1fr; }
    .fd-grid { grid-template-columns: 1fr; }
}
@media (max-width: 991.98px) {
    .fd-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 575.98px) {
    .fd-kpis { grid-template-columns: 1fr; }
    .fd-banner .fd-actions, .fd-banner .btn { width: 100%; }
}
</style>

<div class="container-fluid px-3 px-lg-4 py-3 fd">
    <div class="fd-banner">
        <div>
            <h1><?php echo $h($roleLabel); ?> dashboard</h1>
            <p>Welcome, <?php echo $h($user_name ?? 'User'); ?> · <?php echo date('d M Y'); ?></p>
        </div>
        <div class="fd-actions">
            <?php if ($canManagePayments): ?>
                <a href="<?php echo APP_URL; ?>/payments/create" class="btn btn-light btn-sm"><i class="fas fa-plus me-1"></i>Add payment</a>
            <?php endif; ?>
            <?php if ($canViewPayments): ?>
                <a href="<?php echo APP_URL; ?>/payments" class="btn btn-outline-light btn-sm"><i class="fas fa-money-bill-wave me-1"></i>Payments</a>
                <a href="<?php echo APP_URL; ?>/payments/report" class="btn btn-outline-light btn-sm"><i class="fas fa-file-alt me-1"></i>Report</a>
            <?php endif; ?>
            <?php if ($canViewAttendanceReport): ?>
                <a href="<?php echo APP_URL; ?>/attendance/report" class="btn btn-outline-light btn-sm"><i class="fas fa-calendar-check me-1"></i>Attendance</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-warning py-2"><?php echo $h($error); ?></div>
    <?php endif; ?>

    <div class="fd-kpis">
        <div class="fd-kpi">
            <span>Today</span>
            <strong><?php echo $money($s('today_total')); ?></strong>
            <em><?php echo (int) $s('today_count'); ?> payment<?php echo (int) $s('today_count') === 1 ? '' : 's'; ?></em>
        </div>
        <div class="fd-kpi">
            <span>This month</span>
            <strong><?php echo $money($s('month_total')); ?></strong>
            <em><?php echo (int) $s('month_count'); ?> · <?php echo $money($s('approved_month_total')); ?> approved</em>
        </div>
        <div class="fd-kpi">
            <span>This year</span>
            <strong><?php echo $money($s('year_total')); ?></strong>
            <em><?php echo (int) $s('year_count'); ?> payments</em>
        </div>
        <div class="fd-kpi warn">
            <span>Pending approval</span>
            <strong><?php echo $money($s('pending_total')); ?></strong>
            <em><?php echo (int) $s('pending_count'); ?> waiting</em>
        </div>
    </div>

    <div class="fd-grid-3">
        <div class="fd-block">
            <h2>Pending payments</h2>
            <p class="fd-cap">Awaiting approval</p>
            <?php if (!empty($pending)): ?>
                <div class="table-responsive">
                    <table class="fd-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Student</th>
                                <th>Type</th>
                                <th class="num">Amount</th>
                                <?php if ($canViewPayments): ?><th></th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending as $p):
                                $amount = (float) $p['pays_amount'] * max(1, (int) $p['pays_qty']);
                            ?>
                                <tr>
                                    <td><?php echo $h(!empty($p['pays_date']) ? date('d M Y', strtotime($p['pays_date'])) : '—'); ?></td>
                                    <td>
                                        <?php echo $h($p['student_fullname'] ?? '—'); ?>
                                        <span class="fd-sub"><?php echo $h($p['student_id'] ?? ''); ?></span>
                                    </td>
                                    <td>
                                        <?php echo $h($p['payment_reason'] ?? '—'); ?>
                                        <span class="fd-sub"><?php echo $h($p['payment_type'] ?? ''); ?></span>
                                    </td>
                                    <td class="num"><?php echo $money($amount); ?></td>
                                    <?php if ($canViewPayments): ?>
                                        <td>
                                            <a class="btn btn-sm btn-outline-warning" title="Open"
                                               href="<?php echo APP_URL; ?>/payments?search=<?php echo urlencode((string) ($p['student_id'] ?? '')); ?>">
                                                <i class="fas fa-external-link-alt"></i>
                                            </a>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="fd-empty">No pending payments.</p>
            <?php endif; ?>
        </div>

        <div class="fd-block">
            <h2>Locked attendance</h2>
            <p class="fd-cap">Ready for allowance / payment check</p>
            <?php if (!empty($lockedMonths)): ?>
                <div class="table-responsive">
                    <table class="fd-table">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Department</th>
                                <?php if ($canViewAttendanceReport): ?><th></th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lockedMonths as $lock):
                                $month = (string) ($lock['month'] ?? '');
                                $deptId = (string) ($lock['department_id'] ?? '');
                                $monthLabel = $month !== '' ? date('M Y', strtotime($month . '-01')) : '—';
                            ?>
                                <tr>
                                    <td><?php echo $h($monthLabel); ?></td>
                                    <td>
                                        <?php echo $h($lock['department_name'] ?? $deptId); ?>
                                        <span class="fd-sub"><?php echo $h($deptId); ?></span>
                                    </td>
                                    <?php if ($canViewAttendanceReport): ?>
                                        <td>
                                            <a class="btn btn-sm btn-outline-primary"
                                               href="<?php echo APP_URL; ?>/attendance/report?<?php echo htmlspecialchars(http_build_query([
                                                   'department_id' => $deptId,
                                                   'month' => $month,
                                                   'view' => 'summary',
                                                   'source' => 'manual',
                                               ])); ?>">
                                                Open
                                            </a>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="fd-empty">No locked months found.</p>
            <?php endif; ?>
        </div>

        <div class="fd-block">
            <h2>Finance office</h2>
            <p class="fd-cap">Shortcuts</p>
            <div class="fd-links">
                <?php if ($canViewPayments): ?>
                    <a href="<?php echo APP_URL; ?>/payments"><i class="fas fa-list"></i>All payments</a>
                    <a href="<?php echo APP_URL; ?>/payments/report"><i class="fas fa-chart-bar"></i>Payment report</a>
                <?php endif; ?>
                <?php if ($canManagePayments): ?>
                    <a href="<?php echo APP_URL; ?>/payments/create"><i class="fas fa-plus-circle"></i>Record payment</a>
                <?php endif; ?>
                <?php if ($canViewAttendanceReport): ?>
                    <a href="<?php echo APP_URL; ?>/attendance/report"><i class="fas fa-user-check"></i>Attendance summary</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="fd-grid">
        <div class="fd-block">
            <h2>Collections · last 6 months</h2>
            <p class="fd-cap">Total recorded payment value</p>
            <div class="fd-chart"><canvas id="fdMonths" aria-label="Monthly collections"></canvas></div>
        </div>
        <div class="fd-block">
            <h2>This month by type</h2>
            <p class="fd-cap"><?php echo date('F Y'); ?></p>
            <?php if (!empty($byType)): ?>
                <ul class="fd-types">
                    <?php foreach ($byType as $t):
                        $pct = $typeTotal > 0 ? round($t['total'] / $typeTotal * 100) : 0;
                    ?>
                        <li>
                            <div class="row1">
                                <span><?php echo $h($t['name'] !== '' ? $t['name'] : 'Other'); ?>
                                    <small class="text-muted">(<?php echo (int) $t['count']; ?>)</small>
                                </span>
                                <b><?php echo $money($t['total']); ?></b>
                            </div>
                            <div class="fd-bar"><i style="width: <?php echo (int) $pct; ?>%"></i></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="fd-empty">No payments this month.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="fd-block">
        <h2>Recent payments</h2>
        <p class="fd-cap">Latest 10</p>
        <?php if (!empty($recent)): ?>
            <div class="table-responsive">
                <table class="fd-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Student</th>
                            <th>Payment</th>
                            <th class="num">Amount</th>
                            <th>Status</th>
                            <?php if ($canViewPayments): ?><th></th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent as $p):
                            $amount = (float) $p['pays_amount'] * max(1, (int) $p['pays_qty']);
                        ?>
                            <tr>
                                <td><?php echo $h(date('d M Y', strtotime($p['pays_date']))); ?></td>
                                <td>
                                    <?php echo $h($p['student_fullname'] ?? '—'); ?>
                                    <span class="fd-sub"><?php echo $h($p['student_id']); ?></span>
                                </td>
                                <td>
                                    <?php echo $h($p['payment_reason']); ?>
                                    <span class="fd-sub"><?php echo $h($p['payment_type']); ?></span>
                                </td>
                                <td class="num"><?php echo $money($amount); ?></td>
                                <td>
                                    <?php if ((int) $p['approved'] === 1): ?>
                                        <span class="fd-badge ok">Approved</span>
                                    <?php else: ?>
                                        <span class="fd-badge wait">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <?php if ($canViewPayments): ?>
                                    <td>
                                        <a class="btn btn-sm btn-outline-primary" title="Receipt"
                                           href="<?php echo APP_URL; ?>/payments/receipt?id=<?php echo (int) $p['pays_id']; ?>">
                                            <i class="fas fa-receipt"></i>
                                        </a>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="fd-empty">No payments recorded yet.</p>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    var data = <?php echo $chartJson; ?>;
    var el = document.getElementById('fdMonths');
    if (!el || !window.Chart) return;
    new Chart(el, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Collections (Rs.)',
                data: data.values,
                backgroundColor: '#0b2545',
                borderRadius: 6,
                maxBarThickness: 48
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function (c) {
                            return 'Rs. ' + Number(c.parsed.y).toLocaleString(undefined, { minimumFractionDigits: 2 });
                        }
                    }
                }
            },
            scales: {
                y: { beginAtZero: true, ticks: { callback: function (v) { return Number(v).toLocaleString(); } } },
                x: { grid: { display: false } }
            }
        }
    });
})();
</script>
