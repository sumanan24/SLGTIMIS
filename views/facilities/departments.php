<?php require BASE_PATH . '/views/facilities/partials/helpers.php'; ?>
<div class="container-fluid px-0 fac-page">
    <div class="card border-0">
        <?php $facHeader('Departments', 'Tickets handled by each department.'); ?>
        <div class="card-body">
            <?php $facNav(); $facFlash(); ?>
            <p class="fac-help">Tickets handled by each department. Select a number to see those tickets.</p>
            <?php if (empty($rows)): ?>
                <div class="fac-empty">No department tickets yet.</div>
            <?php else: ?>
            <div class="fac-table-wrap">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Open</th>
                            <th class="text-end">In progress</th>
                            <th class="text-end">Closed</th>
                            <th class="text-end">Overdue</th>
                            <th class="text-end">Avg. days to close</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $row):
                        $deptId = (string) ($row['department_id'] ?? '');
                        $link = $base . '?department_id=' . rawurlencode($deptId);
                        $avgDays = ($row['avg_hours'] ?? null) !== null ? number_format(((float) $row['avg_hours']) / 24, 1) : '—';
                    ?>
                        <tr>
                            <td><?php echo $h($row['department_name'] ?: ($deptId !== '' ? $deptId : 'Not set')); ?></td>
                            <td class="text-end"><?php echo $deptId !== '' ? '<a href="' . $h($link) . '">' . (int) $row['total'] . '</a>' : (int) $row['total']; ?></td>
                            <td class="text-end"><?php echo (int) $row['pending']; ?></td>
                            <td class="text-end"><?php echo (int) ($row['in_progress'] ?? 0); ?></td>
                            <td class="text-end"><?php echo (int) $row['completed']; ?></td>
                            <td class="text-end"><?php echo (int) $row['overdue'] > 0 ? '<strong class="text-danger">' . (int) $row['overdue'] . '</strong>' : '0'; ?></td>
                            <td class="text-end"><?php echo $h($avgDays); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>