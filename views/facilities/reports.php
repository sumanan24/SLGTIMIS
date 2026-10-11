<?php require BASE_PATH . '/views/facilities/partials/helpers.php';
$c = $summary['counts'] ?? [];
$exportQs = $filters ?? [];
unset($exportQs['officer_all'], $exportQs['scope_staff_id'], $exportQs['hod_scope'], $exportQs['scope_department_id']);
?>
<div class="container-fluid px-0 fac-page">
    <div class="card border-0">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">Reports</h5>
            <div>
                <a class="btn btn-sm btn-light" href="<?php echo $base; ?>/export?format=csv&amp;<?php echo $h(http_build_query($exportQs)); ?>">CSV</a>
                <a class="btn btn-sm btn-light" href="<?php echo $base; ?>/export?format=print&amp;<?php echo $h(http_build_query($exportQs)); ?>" target="_blank">Print</a>
            </div>
        </div>
        <div class="card-body">
            <?php $facNav(); $facFlash(); ?>
            <form method="get" action="<?php echo $base; ?>/reports" class="fac-filters">
                <div><label class="form-label">Status</label>
                    <select class="form-select form-select-sm" name="status">
                        <option value="">All</option>
                        <?php foreach ($stLabel as $k => $lab): ?>
                            <option value="<?php echo $h($k); ?>" <?php echo (($filters['status'] ?? '') === $k) ? 'selected' : ''; ?>><?php echo $h($lab); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div><label class="form-label">Priority</label>
                    <select class="form-select form-select-sm" name="priority">
                        <option value="">All</option>
                        <?php foreach ($prLabel as $k => $lab): ?>
                            <option value="<?php echo $h($k); ?>" <?php echo (($filters['priority'] ?? '') === $k) ? 'selected' : ''; ?>><?php echo $h($lab); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div><label class="form-label">Department</label>
                    <select class="form-select form-select-sm" name="department_id">
                        <option value="">All</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?php echo $h($d['department_id']); ?>" <?php echo (($filters['department_id'] ?? '') === $d['department_id']) ? 'selected' : ''; ?>><?php echo $h($d['department_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="fac-go"><button class="btn btn-sm btn-primary">Filter</button></div>
            </form>
            <div class="fac-stats">
                <div class="fac-stat"><span>Total</span><strong><?php echo (int) ($c['total'] ?? 0); ?></strong></div>
                <div class="fac-stat is-bad"><span>Overdue</span><strong><?php echo (int) ($c['overdue_count'] ?? 0); ?></strong></div>
                <div class="fac-stat"><span>Closed</span><strong><?php echo (int) ($c['closed_count'] ?? 0); ?></strong></div>
                <div class="fac-stat"><span>Reopened</span><strong><?php echo (int) ($c['reopened_count'] ?? 0); ?></strong></div>
            </div>
            <div class="fac-table-wrap">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Ticket</th><th>Problem</th><th>Priority</th><th>Status</th><th>Assigned</th><th>Deadline</th></tr></thead>
                    <tbody>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="6"><div class="fac-empty">No tickets for this filter.</div></td></tr>
                    <?php else: foreach ($rows as $row): ?>
                        <tr>
                            <td><a href="<?php echo $base; ?>/view?id=<?php echo (int) $row['id']; ?>"><?php echo $h($row['ticket_number']); ?></a></td>
                            <td><?php echo $h($row['title']); ?></td>
                            <td><?php echo $badgePriority($row['current_priority']); ?></td>
                            <td><?php echo $badgeStatus($row['status']); ?></td>
                            <td><?php echo $h($row['responsible_name'] ?? ''); ?></td>
                            <td><?php echo $h($row['deadline'] ?? ''); ?> <?php echo $dueLabel($row); ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
