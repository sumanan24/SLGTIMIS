<?php require BASE_PATH . '/views/facilities/partials/helpers.php';
$filters = $filters ?? [];
$result = $result ?? ['rows' => [], 'total' => 0, 'page' => 1, 'per_page' => 20];
$pages = max(1, (int) ceil(($result['total'] ?: 1) / $result['per_page']));
$hasMore = ($filters['category_id'] ?? '') !== '' || ($filters['department_id'] ?? '') !== '' || ($filters['staff_id'] ?? '') !== '' || ($filters['location'] ?? '') !== '' || ($filters['date_from'] ?? '') !== '' || ($filters['date_to'] ?? '') !== '';
?>
<div class="container-fluid px-0 fac-page">
    <div class="card border-0">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">Tickets</h5>
            <a class="btn btn-sm btn-light" href="<?php echo $base; ?>/create">New ticket</a>
        </div>
        <div class="card-body">
            <?php $facNav(); $facFlash(); ?>
            <form method="get" action="<?php echo $base; ?>">
                <div class="fac-filters">
                    <div><label class="form-label">Search</label><input class="form-control form-control-sm" name="q" value="<?php echo $h($filters['q'] ?? ''); ?>" placeholder="Ticket no. or title"></div>
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
                    <div class="fac-go">
                        <button class="btn btn-sm btn-primary">Search</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="facMoreBtn"><?php echo $hasMore ? 'Hide filters' : 'More filters'; ?></button>
                    </div>
                </div>
                <div id="facMoreFilters" <?php echo $hasMore ? '' : 'hidden'; ?>>
                    <div class="fac-filters">
                        <div><label class="form-label">Category</label>
                            <select class="form-select form-select-sm" name="category_id">
                                <option value="">All</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo (int) $cat['id']; ?>" <?php echo ((string) ($filters['category_id'] ?? '') === (string) $cat['id']) ? 'selected' : ''; ?>><?php echo $h($cat['name']); ?></option>
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
                        <div><label class="form-label">Staff ID</label><input class="form-control form-control-sm" name="staff_id" value="<?php echo $h($filters['staff_id'] ?? ''); ?>"></div>
                        <div><label class="form-label">Location</label><input class="form-control form-control-sm" name="location" value="<?php echo $h($filters['location'] ?? ''); ?>"></div>
                        <div><label class="form-label">From</label><input type="date" class="form-control form-control-sm" name="date_from" value="<?php echo $h($filters['date_from'] ?? ''); ?>"></div>
                        <div><label class="form-label">To</label><input type="date" class="form-control form-control-sm" name="date_to" value="<?php echo $h($filters['date_to'] ?? ''); ?>"></div>
                    </div>
                </div>
            </form>
            <p class="fac-note mb-2"><?php echo (int) $result['total']; ?> ticket(s)
                · <a href="<?php echo $base; ?>?overdue=1">Overdue</a>
                · <a href="<?php echo $base; ?>?due_soon=1">Due soon</a>
                · <a href="<?php echo $base; ?>?unassigned=1">Unassigned</a>
            </p>
            <div class="fac-table-wrap">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Ticket</th><th>Problem</th><th>Priority</th><th>Status</th><th>Assigned</th><th>Deadline</th></tr></thead>
                    <tbody>
                    <?php if (empty($result['rows'])): ?>
                        <tr><td colspan="6"><div class="fac-empty">No tickets match this search.</div></td></tr>
                    <?php else: foreach ($result['rows'] as $row): ?>
                        <tr>
                            <td><a href="<?php echo $base; ?>/view?id=<?php echo (int) $row['id']; ?>"><?php echo $h($row['ticket_number']); ?></a></td>
                            <td><?php echo $h($row['title']); ?><div class="small text-muted"><?php echo $h($row['location']); ?></div></td>
                            <td><?php echo $badgePriority($row['current_priority']); ?></td>
                            <td><?php echo $badgeStatus($row['status']); ?></td>
                            <td><?php echo $h($row['responsible_name'] ?? $row['responsible_staff_id'] ?? '—'); ?></td>
                            <td><?php echo $h($row['deadline'] ?? '—'); ?><div><?php echo $dueLabel($row); ?></div></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($pages > 1):
                $qs = $filters;
                unset($qs['officer_all'], $qs['scope_staff_id'], $qs['hod_scope'], $qs['scope_department_id']);
            ?>
                <div class="mt-2">
                    <?php for ($i = 1; $i <= min($pages, 12); $i++): ?>
                        <a class="btn btn-sm <?php echo $i === (int) $result['page'] ? 'btn-primary' : 'btn-outline-secondary'; ?>" href="<?php echo $base; ?>?<?php echo $h(http_build_query($qs + ['page' => $i])); ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
