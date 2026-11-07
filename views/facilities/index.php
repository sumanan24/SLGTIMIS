<?php require BASE_PATH . '/views/facilities/partials/helpers.php';
$filters = $filters ?? [];
$result = $result ?? ['rows' => [], 'total' => 0, 'page' => 1, 'per_page' => 20];
$pages = max(1, (int) ceil(($result['total'] ?: 1) / $result['per_page']));
$group = (string) ($filters['group'] ?? '');
$groupStatuses = ($group !== '' && isset(FacilitiesTicketModel::STATUS_GROUPS[$group])) ? FacilitiesTicketModel::STATUS_GROUPS[$group] : null;
$hasMore = ($filters['category_id'] ?? '') !== '' || ($filters['department_id'] ?? '') !== '' || ($filters['staff_id'] ?? '') !== '' || ($filters['location'] ?? '') !== '' || ($filters['date_from'] ?? '') !== '' || ($filters['date_to'] ?? '') !== '';
?>
<div class="container-fluid px-0 fac-page">
    <div class="card border-0">
        <?php
        $facSubtitles = ['tasks' => 'Tickets assigned to you.', 'pending' => 'Tickets waiting to be reviewed, assigned or started.', 'in_progress' => 'Tickets currently being worked on.', 'resolved' => 'Work completed and waiting for verification.', 'closed' => 'Tickets that are closed, cancelled or rejected.'];
        $facHeader($heading ?? 'All Tickets', $facSubtitles[$section ?? ''] ?? 'Search and filter every maintenance ticket.', $facNewBtn);
        ?>
        <div class="card-body">
            <?php $facNav(); $facFlash(); ?>
            <form method="get" action="<?php echo ($section ?? '') === 'tasks' ? $base . '/tasks' : $base; ?>">
                <?php if ($groupStatuses !== null): ?><input type="hidden" name="group" value="<?php echo $h($group); ?>"><?php endif; ?>
                <div class="fac-filters">
                    <div><label class="form-label">Search</label><input class="form-control form-control-sm" name="q" value="<?php echo $h($filters['q'] ?? ''); ?>" placeholder="Ticket no. or title"></div>
                    <div><label class="form-label">Status</label>
                        <select class="form-select form-select-sm" name="status">
                            <option value="">All</option>
                            <?php foreach ($stLabel as $k => $lab): if ($groupStatuses !== null && !in_array($k, $groupStatuses, true)) { continue; } ?>
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
                    <thead><tr><th>Ticket</th><th>Problem</th><th>Priority</th><th>Status</th><th>Assigned</th><th>Deadline</th><th class="text-end">Action</th></tr></thead>
                    <tbody>
                    <?php if (empty($result['rows'])): ?>
                        <tr><td colspan="7"><div class="fac-empty">No tickets match this search.</div></td></tr>
                    <?php else: foreach ($result['rows'] as $row): ?>
                        <tr>
                            <td><a href="<?php echo $base; ?>/view?id=<?php echo (int) $row['id']; ?>"><?php echo $h($row['ticket_number']); ?></a></td>
                            <td><?php echo $h($row['title']); ?><div class="small text-muted"><?php echo $h($row['location']); ?></div></td>
                            <td><?php echo $badgePriority($row['current_priority']); ?></td>
                            <td><?php echo $badgeStatus($row['status']); ?></td>
                            <td><?php echo $h($row['responsible_name'] ?? $row['responsible_staff_id'] ?? '—'); ?></td>
                            <td><?php echo $h($row['deadline'] ?? '—'); ?><div><?php echo $dueLabel($row); ?></div></td>
                            <td class="text-end text-nowrap">
                                <?php if (!empty($row['can_assign'])):
                                    $isAssigned = !empty($row['responsible_staff_id']) || !empty($row['responsible_department_id']); ?>
                                    <a class="btn btn-sm <?php echo $isAssigned ? 'btn-outline-primary' : 'btn-primary'; ?>" href="<?php echo $base; ?>/assign?id=<?php echo (int) $row['id']; ?>"><?php echo $isAssigned ? 'Reassign' : 'Assign'; ?></a>
                                <?php else: ?>
                                    <a class="btn btn-sm btn-outline-secondary" href="<?php echo $base; ?>/view?id=<?php echo (int) $row['id']; ?>">View</a>
                                <?php endif; ?>
                            </td>
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
