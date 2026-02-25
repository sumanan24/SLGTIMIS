<?php
$group_id = $group_id ?? '';
$group = $group ?? null;
$entries = $entries ?? [];
$grid = $grid ?? [];
$weekdaysToShow = $weekdaysToShow ?? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
$timeSlots = $timeSlots ?? [];
$modules = $modules ?? [];
$staff = $staff ?? [];
?>
<div class="container-fluid px-4 py-4">
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-primary text-white">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="mb-0 fw-bold"><i class="fas fa-calendar-alt me-2"></i><?php echo $group ? htmlspecialchars('Timetable: ' . $group['name']) : 'Group Timetable'; ?></h5>
                <?php if ($group_id !== ''): ?>
                    <a href="<?php echo APP_URL; ?>/group-timetable/create?group_id=<?php echo urlencode($group_id); ?>" class="btn btn-light btn-sm">
                        <i class="fas fa-plus me-1"></i>Add Entry
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <?php if (isset($message)): ?>
                <div class="alert alert-success alert-dismissible fade show"><?php echo htmlspecialchars($message); ?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show"><?php echo htmlspecialchars($error); ?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>

            <?php if ($group_id === ''): ?>
                <p class="text-muted">Use URL with <strong>group_id</strong> to view timetable, e.g. <code>group-timetable/index?group_id=8</code></p>
                <p class="mb-0"><a href="<?php echo APP_URL; ?>/groups">Go to Groups</a> and open a group, then use the timetable link for that group.</p>
            <?php elseif (!$group): ?>
                <p class="text-danger">Group not found.</p>
            <?php else: ?>
                <div class="mb-3">
                    <p class="mb-1"><strong>Course:</strong> <?php echo htmlspecialchars($group['course_name'] ?? '—'); ?></p>
                    <p class="mb-0"><strong>Department:</strong> <?php echo htmlspecialchars($group['department_name'] ?? '—'); ?></p>
                </div>
                <!-- Timetable list: one row per day, second column lists all time slots with module & staff -->
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0 timetable-list">
                        <thead class="table-light">
                            <tr>
                                <th class="fw-bold text-center" style="width: 140px;">Day</th>
                                <th class="fw-bold">Time Slots / Modules / Staff</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($weekdaysToShow as $day): ?>
                                <tr>
                                    <td class="fw-bold bg-light align-top text-center"><?php echo htmlspecialchars($day); ?></td>
                                    <td class="p-2">
                                        <?php if (!empty($timeSlots)): ?>
                                            <?php foreach ($timeSlots as $slotKey => $slotLabel):
                                                $entry = isset($grid[$day][$slotKey]) ? $grid[$day][$slotKey] : null;
                                                $isAllocated = is_array($entry) && isset($entry['id']) && $entry['id'] !== '' && $entry['id'] !== null;
                                            ?>
                                                <div class="d-flex justify-content-between align-items-start mb-2 slot-row">
                                                    <div class="small text-muted fw-semibold" style="min-width: 140px;">
                                                        <?php echo htmlspecialchars($slotLabel); ?>
                                                    </div>
                                                    <div class="flex-grow-1 ms-2">
                                                        <?php if ($isAllocated): ?>
                                                            <?php /* Allocated slot: show details only; no Add button; show Delete + Edit */ ?>
                                                            <div class="slot-filled">
                                                                <div class="small fw-bold text-primary">
                                                                    <span class="text-muted">Module:</span>
                                                                    <?php echo htmlspecialchars($entry['module_id'] ?? $entry['subject'] ?? '—'); ?>
                                                                    <?php if (!empty($entry['module_name'])): ?>
                                                                        <span class="fw-normal text-muted">(<?php echo htmlspecialchars($entry['module_name']); ?>)</span>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="small text-muted">
                                                                    <span class="text-muted">Staff ID:</span>
                                                                    <?php echo htmlspecialchars($entry['staff_id'] ?? '—'); ?>
                                                                    <?php if (!empty($entry['staff_name'])): ?>
                                                                        <span class="fw-normal">(<?php echo htmlspecialchars($entry['staff_name']); ?>)</span>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <?php if (!empty($entry['room'])): ?>
                                                                    <div class="small"><?php echo htmlspecialchars($entry['room']); ?></div>
                                                                <?php endif; ?>
                                                                <div class="btn-group btn-group-sm mt-1">
                                                                    <a href="<?php echo APP_URL; ?>/group-timetable/delete?id=<?php echo urlencode($entry['id']); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this slot?');" title="Delete">
                                                                        <i class="fas fa-trash-alt me-1"></i>Delete
                                                                    </a>
                                                                    <a href="<?php echo APP_URL; ?>/group-timetable/edit?id=<?php echo urlencode($entry['id']); ?>" class="btn btn-outline-primary btn-sm" title="Edit">
                                                                        <i class="fas fa-edit"></i>
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        <?php else: ?>
                                                            <?php /* Empty slot: Add button only; no Delete */ ?>
                                                            <div class="slot-empty d-flex align-items-center">
                                                                <span class="small text-muted me-2">Empty</span>
                                                                <a href="<?php echo APP_URL; ?>/group-timetable/create?group_id=<?php echo urlencode($group_id); ?>&day=<?php echo urlencode($day); ?>&time_slot=<?php echo urlencode($slotKey); ?>" class="btn btn-outline-success btn-sm">
                                                                    <i class="fas fa-plus me-1"></i>Add
                                                                </a>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="text-muted small">No time slots configured.</div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.timetable-list .slot-row:last-child { margin-bottom: 0; }
.slot-filled { font-size: 0.875rem; }
.slot-empty { font-size: 0.875rem; }
</style>
