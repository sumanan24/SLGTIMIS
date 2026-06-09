<?php
if (empty($staffList)) {
    return;
}
?>
<hr class="my-4">
<h6 class="fw-bold"><i class="fas fa-user-shield me-2 text-primary"></i>Staff privileges (dynamic navbar)</h6>
<p class="text-muted small mb-2">
    Select staff who may see this menu option when <strong>dynamic navbar</strong> is enabled on the navbar settings page.
    Access is by staff name only (not role). ADM users always see all items.
    <?php if (!$isEdit): ?>
        Assignments are saved when you create this option.
    <?php endif; ?>
</p>
<div class="mb-2">
    <input type="text" class="form-control form-control-sm" id="staffSearch" placeholder="Search staff name or ID…" autocomplete="off">
</div>
<div class="form-check mb-2">
    <input class="form-check-input" type="checkbox" name="auto_approve_staff" value="1" id="auto_approve_staff" checked>
    <label class="form-check-label" for="auto_approve_staff">Approve new assignments immediately</label>
</div>
<div class="border rounded p-2 mb-3" style="max-height: 220px; overflow-y: auto;" id="staffAssignList">
    <?php foreach ($staffList as $st): ?>
        <?php $sid = $st['staff_id']; ?>
        <div class="form-check staff-assign-row" data-search="<?php echo $h(strtolower(($st['staff_name'] ?? '') . ' ' . $sid . ' ' . ($st['department_name'] ?? ''))); ?>">
            <input class="form-check-input" type="checkbox" name="assigned_staff[]" value="<?php echo $h($sid); ?>" id="staff_<?php echo $h($sid); ?>"
                <?php echo in_array($sid, $assignedStaffIds, true) ? 'checked' : ''; ?>>
            <label class="form-check-label" for="staff_<?php echo $h($sid); ?>">
                <strong><?php echo $h($st['staff_name'] ?? $sid); ?></strong>
                <span class="text-muted small">(<?php echo $h($sid); ?><?php if (!empty($st['department_name'])): ?> · <?php echo $h($st['department_name']); ?><?php endif; ?>)</span>
            </label>
        </div>
    <?php endforeach; ?>
</div>
<p class="small text-muted mb-0">
    Or manage all menu items for one staff member on
    <a href="<?php echo APP_URL; ?>/admin/nav-menu/staff-privileges">Staff navbar privileges</a>.
</p>

<?php if ($isEdit && !empty($assignments)): ?>
<h6 class="fw-bold mt-3">Current assignments</h6>
<div class="table-responsive mb-3">
    <table class="table table-sm table-bordered">
        <thead class="table-light">
            <tr><th>Staff name</th><th>Department</th><th>Status</th><th class="text-end">Actions</th></tr>
        </thead>
        <tbody>
            <?php foreach ($assignments as $a): ?>
                <tr>
                    <td><?php echo $h($a['staff_name']); ?> <span class="text-muted small">(<?php echo $h($a['staff_id']); ?>)</span></td>
                    <td><?php echo $h($a['department_name'] ?? '—'); ?></td>
                    <td>
                        <?php $st = $a['status']; $badge = $st === 'approved' ? 'success' : ($st === 'pending' ? 'warning' : 'secondary'); ?>
                        <span class="badge bg-<?php echo $badge; ?>"><?php echo $h(ucfirst($st)); ?></span>
                    </td>
                    <td class="text-end text-nowrap">
                        <?php if ($a['status'] === 'pending'): ?>
                            <a href="<?php echo APP_URL; ?>/admin/nav-menu/approve-assignment?id=<?php echo (int) $a['assignment_id']; ?>&return=edit" class="btn btn-outline-success btn-sm">Approve</a>
                            <a href="<?php echo APP_URL; ?>/admin/nav-menu/reject-assignment?id=<?php echo (int) $a['assignment_id']; ?>&return=edit" class="btn btn-outline-warning btn-sm">Reject</a>
                        <?php endif; ?>
                        <a href="<?php echo APP_URL; ?>/admin/nav-menu/delete-assignment?id=<?php echo (int) $a['assignment_id']; ?>&return=edit&confirm=1" class="btn btn-outline-danger btn-sm" onclick="return confirm('Remove this staff assignment?');">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
<script>
document.getElementById('staffSearch')?.addEventListener('input', function () {
    var q = this.value.toLowerCase().trim();
    document.querySelectorAll('.staff-assign-row').forEach(function (row) {
        var hay = row.getAttribute('data-search') || '';
        row.style.display = !q || hay.indexOf(q) !== -1 ? '' : 'none';
    });
});
</script>
