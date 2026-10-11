<?php require BASE_PATH . '/views/facilities/partials/helpers.php';
$c = $summary['counts'] ?? [];
$mc = $mine_counts ?? [];
$mineOwn = $mc['mine'] ?? [];
$mineAsg = $mc['assigned'] ?? [];
$isLead = !empty($actor['is_officer']) || !empty($actor['is_monitor']);
?>
<?php if ($isLead): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<?php endif; ?>
<div class="container-fluid px-0 fac-page">
    <div class="card border-0">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0"><?php echo !empty($actor['is_officer']) ? 'Facilities work' : (!empty($actor['is_monitor']) ? 'Facilities overview' : 'My facilities work'); ?></h5>
            <a class="btn btn-sm btn-light" href="<?php echo $base; ?>/create">New ticket</a>
        </div>
        <div class="card-body">
            <?php $facNav(); $facFlash(); ?>

            <?php if (!empty($actor['is_officer']) || !empty($actor['is_monitor'])): ?>
            <div class="fac-stats">
                <a class="fac-stat" href="<?php echo $base; ?>"><span>Total</span><strong><?php echo (int) ($c['total'] ?? 0); ?></strong></a>
                <a class="fac-stat" href="<?php echo $base; ?>?status=new"><span>New</span><strong><?php echo (int) ($c['new_count'] ?? 0); ?></strong></a>
                <a class="fac-stat" href="<?php echo $base; ?>?status=in_progress"><span>In progress</span><strong><?php echo (int) ($c['in_progress'] ?? 0); ?></strong></a>
                <a class="fac-stat" href="<?php echo $base; ?>?status=completed_pending_verification"><span>To verify</span><strong><?php echo (int) ($c['pending_verification'] ?? 0); ?></strong></a>
                <a class="fac-stat is-bad" href="<?php echo $base; ?>?overdue=1"><span>Overdue</span><strong><?php echo (int) ($c['overdue_count'] ?? 0); ?></strong></a>
                <a class="fac-stat is-warn" href="<?php echo $base; ?>?priority=critical"><span>Critical</span><strong><?php echo (int) ($c['critical_count'] ?? 0); ?></strong></a>
                <a class="fac-stat" href="<?php echo $base; ?>?status=closed"><span>Closed</span><strong><?php echo (int) ($c['closed_count'] ?? 0); ?></strong></a>
                <a class="fac-stat" href="<?php echo $base; ?>?due_soon=1"><span>Due soon</span><strong><?php echo (int) ($mc['due_soon'] ?? 0); ?></strong></a>
            </div>
            <?php if (($summary['avg_hours'] ?? null) !== null): ?>
                <p class="fac-note mb-3">Average time to close: <?php echo $h(number_format((float) $summary['avg_hours'], 1)); ?> hours</p>
            <?php endif; ?>
            <?php else: ?>
            <div class="fac-stats">
                <a class="fac-stat" href="<?php echo $base; ?>"><span>My tickets</span><strong><?php echo (int) ($mineOwn['total'] ?? 0); ?></strong></a>
                <a class="fac-stat" href="<?php echo $base; ?>"><span>Assigned to me</span><strong><?php echo (int) ($mineAsg['total'] ?? 0); ?></strong></a>
                <a class="fac-stat" href="<?php echo $base; ?>?status=in_progress"><span>In progress</span><strong><?php echo (int) ($mineAsg['in_progress'] ?? 0); ?></strong></a>
                <a class="fac-stat is-bad" href="<?php echo $base; ?>?overdue=1"><span>Overdue</span><strong><?php echo (int) ($mc['overdue'] ?? 0); ?></strong></a>
            </div>
            <?php endif; ?>

            <?php if (!empty($queues)):
                $queueTabs = [
                    'new' => ['Need review', $queues['new']['rows'] ?? []],
                    'pending' => ['Need verify', $queues['pending']['rows'] ?? []],
                    'overdue' => ['Overdue', $queues['overdue']['rows'] ?? []],
                ];
            ?>
            <div class="fac-section" data-fac-tabs>
                <h6>Needs action</h6>
                <div class="fac-tabs">
                    <?php foreach ($queueTabs as $key => $tab): ?>
                        <button type="button" class="fac-tab" data-fac-tab="<?php echo $h($key); ?>"><?php echo $h($tab[0]); ?> (<?php echo count($tab[1]); ?>)</button>
                    <?php endforeach; ?>
                </div>
                <?php foreach ($queueTabs as $key => $tab): ?>
                <div data-fac-panel="<?php echo $h($key); ?>">
                    <?php if (!$tab[1]): ?>
                        <div class="fac-empty">Nothing waiting here.</div>
                    <?php else: ?>
                    <div class="fac-table-wrap">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Ticket</th><th>Problem</th><th>Priority</th><th></th></tr></thead>
                            <tbody>
                            <?php foreach ($tab[1] as $row): ?>
                                <tr>
                                    <td><a href="<?php echo $base; ?>/view?id=<?php echo (int) $row['id']; ?>"><?php echo $h($row['ticket_number']); ?></a></td>
                                    <td><?php echo $h($row['title']); ?></td>
                                    <td><?php echo $badgePriority($row['current_priority']); ?></td>
                                    <td class="text-end">
                                        <?php if ($key === 'new'): ?>
                                            <a class="btn btn-sm btn-primary" href="<?php echo $base; ?>/assign?id=<?php echo (int) $row['id']; ?>">Assign</a>
                                        <?php elseif ($key === 'pending'): ?>
                                            <a class="btn btn-sm btn-success" href="<?php echo $base; ?>/verify?id=<?php echo (int) $row['id']; ?>">Verify</a>
                                        <?php else: ?>
                                            <a class="btn btn-sm btn-outline-primary" href="<?php echo $base; ?>/view?id=<?php echo (int) $row['id']; ?>">Open</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="fac-section">
                <h6><?php echo $isLead ? 'Recent tickets' : 'My work'; ?></h6>
                <?php if (empty($mine['rows'])): ?>
                    <div class="fac-empty">No tickets yet. <a href="<?php echo $base; ?>/create">Report a problem</a></div>
                <?php else: ?>
                <div class="fac-table-wrap">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Ticket</th><th>Problem</th><th>Status</th><th>Deadline</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($mine['rows'] as $row): ?>
                            <tr>
                                <td><?php echo $h($row['ticket_number']); ?></td>
                                <td><?php echo $h($row['title']); ?></td>
                                <td><?php echo $badgeStatus($row['status']); ?></td>
                                <td><?php echo $dueLabel($row); ?></td>
                                <td class="text-end">
                                    <?php if (($row['responsible_staff_id'] ?? '') === ($actor['staff_id'] ?? '') && in_array($row['status'], ['assigned', 'accepted', 'in_progress', 'reopened'], true)): ?>
                                        <a class="btn btn-sm btn-primary" href="<?php echo $base; ?>/view?id=<?php echo (int) $row['id']; ?>#progress">Update</a>
                                    <?php else: ?>
                                        <a class="btn btn-sm btn-outline-primary" href="<?php echo $base; ?>/view?id=<?php echo (int) $row['id']; ?>">Open</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($isLead): ?>
            <div class="row g-3 mb-3">
                <div class="col-lg-6"><div class="fac-chart-box"><h6>Status</h6><canvas id="facStatusChart" height="160"></canvas></div></div>
                <div class="col-lg-6"><div class="fac-chart-box"><h6>This year</h6><canvas id="facTrendChart" height="160"></canvas></div></div>
            </div>
            <?php if (!empty($summary['departments'])): ?>
            <div class="fac-section">
                <h6>Departments</h6>
                <div class="fac-table-wrap">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Department</th><th>Total</th><th>Done</th><th>Open</th><th>Overdue</th></tr></thead>
                        <tbody>
                        <?php foreach ($summary['departments'] as $row): ?>
                            <tr>
                                <td><?php echo $h($row['department_name'] ?: $row['department_id']); ?></td>
                                <td><?php echo (int) $row['total']; ?></td>
                                <td><?php echo (int) $row['completed']; ?></td>
                                <td><?php echo (int) $row['pending']; ?></td>
                                <td><?php echo (int) $row['overdue']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; endif; ?>
        </div>
    </div>
</div>
<?php if ($isLead): ?>
<script>
(function () {
    if (typeof Chart === 'undefined') { return; }
    const statusData = <?php echo json_encode($summary['status'] ?? new stdClass()); ?>;
    const monthly = <?php echo json_encode($summary['monthly'] ?? []); ?>;
    new Chart(document.getElementById('facStatusChart'), {
        type: 'doughnut',
        data: { labels: Object.keys(statusData), datasets: [{ data: Object.values(statusData), backgroundColor: ['#94a3b8','#8b5cf6','#3b82f6','#f59e0b','#fb923c','#22c55e','#0f766e','#ef4444'] }] },
        options: { plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } } }
    });
    new Chart(document.getElementById('facTrendChart'), {
        type: 'line',
        data: {
            labels: monthly.map(r => r.ym),
            datasets: [
                { label: 'Created', data: monthly.map(r => +r.created_count), borderColor: '#001f3f', tension: 0.25 },
                { label: 'Closed', data: monthly.map(r => +r.closed_count), borderColor: '#0f766e', tension: 0.25 }
            ]
        },
        options: { plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });
})();
</script>
<?php endif; ?>
