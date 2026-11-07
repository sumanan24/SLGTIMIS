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
$canViewPayments = !empty($canViewPayments);
$canManagePayments = !empty($canManagePayments);

$chartJson = json_encode([
    'months' => [
        'labels' => array_column($months, 'label'),
        'values' => array_map('floatval', array_column($months, 'total')),
    ],
    'types' => [
        'labels' => array_column($byType, 'name'),
        'values' => array_map('floatval', array_column($byType, 'total')),
    ],
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$monthTotal = (float) $s('month_total');
$typeTotal = array_sum(array_column($byType, 'total'));
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
.fd { color: #1e293b; max-width: 1600px; }
.fd-banner { background: #0b2545; color: #fff; border-radius: 12px; padding: 1.1rem 1.25rem; margin-bottom: 1rem;
    display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap; }
.fd-banner h1 { font-size: 1.25rem; font-weight: 700; margin: 0 0 .2rem; }
.fd-banner p { margin: 0; color: #d6e2ef; font-size: .9rem; }
.fd-banner .fd-actions { display: flex; gap: .5rem; flex-wrap: wrap; }
.fd-banner .btn { min-height: 40px; display: inline-flex; align-items: center; justify-content: center; white-space: nowrap; }

.fd-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .75rem; margin-bottom: 1rem; }
.fd-kpi { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: .85rem 1rem; min-width: 0; }
.fd-kpi span { display: block; color: #64748b; font-size: .72rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; margin-bottom: .25rem; }
.fd-kpi strong { display: block; color: #0b2545; font-size: 1.35rem; line-height: 1.15; font-variant-numeric: tabular-nums; overflow-wrap: anywhere; }
.fd-kpi em { display: block; margin-top: .3rem; color: #64748b; font-size: .75rem; font-style: normal; }
.fd-kpi.warn strong { color: #b45309; }

.fd-grid { display: grid; grid-template-columns: 3fr 2fr; gap: 1rem; margin-bottom: 1rem; }
.fd-block { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; min-width: 0; }
.fd-block h2 { margin: 0; padding: .75rem 1rem .15rem; font-size: .95rem; font-weight: 700; color: #0b2545; }
.fd-cap { margin: 0; padding: 0 1rem .65rem; color: #64748b; font-size: .78rem; border-bottom: 1px solid #e2e8f0; }
.fd-chart { position: relative; height: 270px; padding: .75rem; }
.fd-empty { padding: 1.25rem 1rem; color: #64748b; margin: 0; }

.fd-table { width: 100%; margin: 0; border-collapse: collapse; }
.fd-table th { background: #f8fafc; color: #475569; font-size: .72rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase;
    border-bottom: 1px solid #e2e8f0; padding: .65rem 1rem; text-align: left; white-space: nowrap; }
.fd-table td { padding: .7rem 1rem; border-bottom: 1px solid #f1f5f9; font-size: .875rem; vertical-align: middle; }
.fd-table tr:last-child td { border-bottom: 0; }
.fd-table .num { text-align: right; font-variant-numeric: tabular-nums; font-weight: 600; white-space: nowrap; }
.fd-sub { display: block; font-size: .78rem; color: #64748b; }
.fd-badge { display: inline-block; padding: .15rem .6rem; border-radius: 999px; font-size: .72rem; font-weight: 600; }
.fd-badge.ok { background: #dcfce7; color: #166534; } .fd-badge.wait { background: #fef3c7; color: #92400e; }

.fd-types { list-style: none; margin: 0; padding: .35rem 1rem .75rem; }
.fd-types li { padding: .5rem 0; border-bottom: 1px solid #f1f5f9; }
.fd-types li:last-child { border-bottom: 0; }
.fd-types .row1 { display: flex; justify-content: space-between; gap: .75rem; font-size: .85rem; }
.fd-types .row1 b { font-variant-numeric: tabular-nums; white-space: nowrap; }
.fd-bar { height: 6px; background: #eef2f7; border-radius: 99px; margin-top: .35rem; overflow: hidden; }
.fd-bar i { display: block; height: 100%; background: #0b2545; border-radius: 99px; }

@media (max-width: 991.98px) { .fd-grid { grid-template-columns: 1fr; } .fd-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 767.98px) {
    .fd.container-fluid { padding-left: .75rem !important; padding-right: .75rem !important; }
    .fd-banner { align-items: stretch; } .fd-banner .fd-actions, .fd-banner .btn { width: 100%; }
    .fd-kpi strong { font-size: 1.1rem; } .fd-chart { height: 230px; }
    .fd-table thead { display: none; }
    .fd-table, .fd-table tbody, .fd-table tr, .fd-table td { display: block; width: 100%; }
    .fd-table tr { padding: .75rem 1rem; border-bottom: 1px solid #e2e8f0; }
    .fd-table tr:last-child { border-bottom: 0; }
    .fd-table td { padding: .2rem 0; border: 0; }
    .fd-table td[data-label] { display: flex; justify-content: space-between; gap: 1rem; text-align: right; }
    .fd-table td[data-label]::before { content: attr(data-label); font-size: .7rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: #64748b; text-align: left; }
}
</style>

<div class="container-fluid px-4 py-3 fd">
    <div class="fd-banner">
        <div>
            <h1>Finance dashboard</h1>
            <p>Welcome, <?php echo $h($user_name ?? 'User'); ?>. Payment collections as at <?php echo date('d M Y'); ?>.</p>
        </div>
        <div class="fd-actions">
            <?php if ($canManagePayments): ?>
                <a href="<?php echo APP_URL; ?>/payments/create" class="btn btn-light"><i class="fas fa-plus me-1"></i>Add payment</a>
            <?php endif; ?>
            <?php if ($canViewPayments): ?>
                <a href="<?php echo APP_URL; ?>/payments" class="btn btn-outline-light"><i class="fas fa-money-bill-wave me-1"></i>Payments</a>
                <a href="<?php echo APP_URL; ?>/payments/report" class="btn btn-outline-light"><i class="fas fa-file-alt me-1"></i>Reports</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-warning py-2"><?php echo $h($error); ?></div>
    <?php endif; ?>

    <div class="fd-kpis">
        <div class="fd-kpi"><span>Today</span><strong><?php echo $money($s('today_total')); ?></strong>
            <em><?php echo (int) $s('today_count'); ?> payment<?php echo (int) $s('today_count') === 1 ? '' : 's'; ?></em></div>
        <div class="fd-kpi"><span>This month</span><strong><?php echo $money($s('month_total')); ?></strong>
            <em><?php echo (int) $s('month_count'); ?> payments &middot; <?php echo $money($s('approved_month_total')); ?> approved</em></div>
        <div class="fd-kpi"><span>This year</span><strong><?php echo $money($s('year_total')); ?></strong>
            <em><?php echo (int) $s('year_count'); ?> payments</em></div>
        <div class="fd-kpi warn"><span>Pending approval</span><strong><?php echo $money($s('pending_total')); ?></strong>
            <em><?php echo (int) $s('pending_count'); ?> payment<?php echo (int) $s('pending_count') === 1 ? '' : 's'; ?> not yet approved</em></div>
    </div>

    <div class="fd-grid">
        <div class="fd-block">
            <h2>Collections, last 6 months</h2>
            <p class="fd-cap">Total value of recorded payments per month.</p>
            <div class="fd-chart"><canvas id="fdMonths" aria-label="Monthly collections"></canvas></div>
        </div>
        <div class="fd-block">
            <h2>This month by payment type</h2>
            <p class="fd-cap"><?php echo date('F Y'); ?></p>
            <?php if (!empty($byType)): ?>
                <ul class="fd-types">
                    <?php foreach ($byType as $t): ?>
                        <?php $pct = $typeTotal > 0 ? round($t['total'] / $typeTotal * 100) : 0; ?>
                        <li>
                            <div class="row1"><span><?php echo $h($t['name'] !== '' ? $t['name'] : 'Other'); ?>
                                <small class="text-muted">(<?php echo (int) $t['count']; ?>)</small></span>
                                <b><?php echo $money($t['total']); ?></b></div>
                            <div class="fd-bar"><i style="width: <?php echo (int) $pct; ?>%"></i></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="fd-empty">No payments recorded this month.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="fd-block">
        <h2>Recent payments</h2>
        <p class="fd-cap">Latest 10 recorded payments.</p>
        <?php if (!empty($recent)): ?>
            <div class="table-responsive">
                <table class="fd-table">
                    <thead>
                        <tr><th>Date</th><th>Student</th><th>Payment</th><th class="num">Amount</th><th>Status</th><?php if ($canViewPayments): ?><th></th><?php endif; ?></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent as $p): ?>
                            <?php $amount = (float) $p['pays_amount'] * max(1, (int) $p['pays_qty']); ?>
                            <tr>
                                <td data-label="Date"><?php echo $h(date('d M Y', strtotime($p['pays_date']))); ?></td>
                                <td data-label="Student"><?php echo $h($p['student_fullname'] ?? '—'); ?>
                                    <span class="fd-sub"><?php echo $h($p['student_id']); ?></span></td>
                                <td data-label="Payment"><?php echo $h($p['payment_reason']); ?>
                                    <span class="fd-sub"><?php echo $h($p['payment_type']); ?></span></td>
                                <td class="num" data-label="Amount"><?php echo $money($amount); ?></td>
                                <td data-label="Status">
                                    <?php if ((int) $p['approved'] === 1): ?><span class="fd-badge ok">Approved</span>
                                    <?php else: ?><span class="fd-badge wait">Pending</span><?php endif; ?>
                                </td>
                                <?php if ($canViewPayments): ?>
                                    <td>
                                        <a class="btn btn-sm btn-outline-primary" title="Receipt" aria-label="Receipt"
                                           href="<?php echo APP_URL; ?>/payments/receipt?id=<?php echo (int) $p['pays_id']; ?>"><i class="fas fa-receipt"></i></a>
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
            labels: data.months.labels,
            datasets: [{ label: 'Collections (Rs.)', data: data.months.values, backgroundColor: '#0b2545', borderRadius: 6, maxBarThickness: 48 }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: function (c) { return 'Rs. ' + Number(c.parsed.y).toLocaleString(undefined, { minimumFractionDigits: 2 }); } } }
            },
            scales: {
                y: { beginAtZero: true, ticks: { callback: function (v) { return Number(v).toLocaleString(); } } },
                x: { grid: { display: false } }
            }
        }
    });
})();
</script>
