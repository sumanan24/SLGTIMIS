<?php require BASE_PATH . '/views/facilities/partials/helpers.php';
$t = $ticket;
?>
<div class="container-fluid px-0 fac-page">
    <div class="card border-0">
        <div class="card-header"><h5 class="fw-bold mb-0">Assign work</h5></div>
        <div class="card-body">
            <?php $facNav(); $facFlash(); ?>
            <p class="fw-bold mb-1"><?php echo $h($t['ticket_number']); ?> · <?php echo $h($t['title']); ?></p>
            <p class="fac-help"><?php echo $h($t['location']); ?> · <?php echo $h($t['requester_name'] ?? $t['requester_staff_id']); ?></p>
            <p><?php echo nl2br($h($t['description'])); ?></p>
            <form method="post" action="<?php echo $base; ?>/assign-save">
                <input type="hidden" name="id" value="<?php echo (int) $t['id']; ?>">
                <div class="fac-section">
                    <h6>Who will do this?</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Assign to</label>
                            <select class="form-select" name="assignment_type" id="facType">
                                <option value="staff">A staff member</option>
                                <option value="department">A department</option>
                                <option value="team">A team</option>
                                <option value="vendor">Outside vendor</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Department</label>
                            <select class="form-select" name="department_id" id="facDept">
                                <option value="">Choose</option>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?php echo $h($d['department_id']); ?>" <?php echo (($selected_department ?? '') === $d['department_id']) ? 'selected' : ''; ?>><?php echo $h($d['department_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Responsible person</label>
                            <select class="form-select" name="staff_id" id="facStaff">
                                <option value="">Choose</option>
                                <?php foreach ($picker as $p): ?>
                                    <option value="<?php echo $h($p['staff_id']); ?>">
                                        <?php echo $h($p['staff_name']); ?> · open <?php echo (int) $p['open_tickets']; ?> · overdue <?php echo (int) $p['overdue']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Open and overdue tickets are shown to help you choose.</div>
                        </div>
                        <div class="col-12" id="facVendorWrap" hidden>
                            <label class="form-label">Vendor name</label>
                            <input class="form-control" name="vendor_name">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Supporting staff</label>
                            <input class="form-control" name="supporting_staff" placeholder="Optional">
                        </div>
                    </div>
                </div>
                <div class="fac-section">
                    <h6>When?</h6>
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Start</label><input type="date" class="form-control" name="start_date" value="<?php echo date('Y-m-d'); ?>"></div>
                        <div class="col-md-4"><label class="form-label">Deadline <span class="text-danger">*</span></label><input type="date" class="form-control" name="deadline" required></div>
                        <div class="col-md-4"><label class="form-label">Expected finish</label><input type="date" class="form-control" name="expected_completion_date"></div>
                        <div class="col-12"><label class="form-label">Instructions</label><textarea class="form-control" name="instructions" rows="3" placeholder="What should they do?"></textarea></div>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary">Assign</button>
                    <a class="btn btn-outline-secondary" href="<?php echo $base; ?>/view?id=<?php echo (int) $t['id']; ?>">Back</a>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.getElementById('facDept').addEventListener('change', function () {
    fetch('<?php echo $base; ?>/staff-options?department_id=' + encodeURIComponent(this.value))
        .then(r => r.json())
        .then(d => {
            const sel = document.getElementById('facStaff');
            sel.innerHTML = '<option value="">Choose</option>';
            (d.rows || []).forEach(p => {
                const o = document.createElement('option');
                o.value = p.staff_id;
                o.textContent = (p.staff_name || p.staff_id) + ' · open ' + (p.open_tickets || 0) + ' · overdue ' + (p.overdue || 0);
                sel.appendChild(o);
            });
        });
});
</script>
