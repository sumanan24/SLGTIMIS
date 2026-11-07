<?php require BASE_PATH . '/views/facilities/partials/helpers.php';
$t = $ticket;
$isReassign = !empty($t['responsible_staff_id']) || !empty($t['responsible_department_id']);
$currentAssignee = $t['responsible_name'] ?? $t['responsible_staff_id'] ?? $t['responsible_department_name'] ?? '';
$requesterName = $t['requester_name'] ?? $t['requester_staff_id'] ?? '';
?>
<div class="container-fluid px-0 fac-page">
    <div class="card border-0">
        <?php $facHeader(
            $isReassign ? 'Reassign Ticket' : 'Assign Ticket',
            $isReassign ? 'Select another person or team to handle this ticket.' : 'Select the person or team who will handle this ticket.'
        ); ?>
        <div class="card-body">
            <?php $facNav(); $facFlash(); ?>

            <div class="fac-section">
                <h6>Ticket Details</h6>
                <p class="fw-bold mb-1"><?php echo $h($t['ticket_number']); ?> <?php echo $badgePriority($t['current_priority'] ?? ''); ?> <?php echo $badgeStatus($t['status'] ?? ''); ?></p>
                <p class="fac-help mb-2">
                    <?php echo $h($t['category_name'] ?? ''); ?>
                    · Location: <?php echo $h($t['location'] ?? ''); ?>
                    · Requested by: <?php echo $h($requesterName); ?>
                    <?php if (!empty($t['requester_department_name'])): ?>(<?php echo $h($t['requester_department_name']); ?>)<?php endif; ?>
                </p>
                <p class="mb-0"><?php echo nl2br($h($t['description'] ?? '')); ?></p>
            </div>

            <?php if ($isReassign): ?>
                <div class="fac-notice mb-3">
                    This ticket is currently assigned to <strong><?php echo $h($currentAssignee !== '' ? $currentAssignee : 'another person'); ?></strong><?php echo !empty($t['deadline']) ? ' (deadline: ' . $h($t['deadline']) . ')' : ''; ?>.
                    The new assignee will receive an SMS notification.
                </div>
            <?php endif; ?>

            <form method="post" action="<?php echo $base; ?>/assign-save" id="facAssignForm">
                <input type="hidden" name="id" value="<?php echo (int) $t['id']; ?>">

                <div class="fac-section">
                    <h6>Assignee</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="facType">Assign To</label>
                            <select class="form-select" name="assignment_type" id="facType">
                                <option value="staff">Staff member</option>
                                <option value="team">Team (2 or more staff)</option>
                                <option value="vendor">External vendor</option>
                            </select>
                        </div>
                        <div class="col-md-4" id="facDeptBox">
                            <label class="form-label" for="facDept">Department</label>
                            <select class="form-select" id="facDept">
                                <option value="">All departments</option>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?php echo $h($d['department_id']); ?>" <?php echo (($selected_department ?? '') === $d['department_id']) ? 'selected' : ''; ?>><?php echo $h($d['department_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4" id="facStaffBox">
                            <label class="form-label" for="facStaff">Staff Member <span class="text-danger">*</span></label>
                            <select class="form-select" name="staff_id" id="facStaff" required>
                                <option value="">Select a staff member</option>
                            </select>
                        </div>
                    </div>

                    <div id="facTeamBox" class="mt-3" hidden>
                        <label class="form-label">Team Members <span class="text-danger">*</span></label>
                        <div id="facTeamChips" class="d-flex flex-wrap gap-2 mb-2"></div>
                        <div id="facTeamList" class="border rounded p-2" style="max-height:240px;overflow:auto;"></div>
                        <div id="facTeamIds"></div>
                        <div class="form-text mt-1" id="facTeamCount">Select at least 2 staff members. The first one selected is the team lead.</div>
                    </div>

                    <div id="facVendorBox" class="mt-3" hidden>
                        <label class="form-label" for="facVendorReason">Reason for Closing <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="vendor_reason" id="facVendorReason" rows="3" minlength="10" required placeholder="Why is this ticket being handed to an external vendor?"></textarea>
                        <div class="form-text">The ticket will be closed with this reason.</div>
                    </div>
                </div>

                <div id="facWorkBox">
                    <div class="fac-section">
                        <h6>Schedule</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="facStart">Start Date</label>
                                <input type="date" class="form-control" name="start_date" id="facStart" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="facDeadline">Deadline <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="deadline" id="facDeadline" required min="<?php echo date('Y-m-d'); ?>" value="<?php echo $h($suggested_deadline ?? ''); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="facFinish">Expected Completion</label>
                                <input type="date" class="form-control" name="expected_completion_date" id="facFinish">
                            </div>
                        </div>
                    </div>

                    <div class="fac-section">
                        <h6>Instructions</h6>
                        <textarea class="form-control" name="instructions" id="facInstr" rows="3" placeholder="Describe what needs to be done (optional)."></textarea>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" id="facSubmit" type="submit">Assign Ticket</button>
                    <a class="btn btn-outline-secondary" href="<?php echo $base; ?>/view?id=<?php echo (int) $t['id']; ?>">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
(function () {
    const isReassign = <?php echo $isReassign ? 'true' : 'false'; ?>;
    const currentStaff = <?php echo json_encode((string) ($t['responsible_staff_id'] ?? '')); ?>;
    const labels = { staff: <?php echo json_encode($isReassign ? 'Reassign Ticket' : 'Assign Ticket'); ?>, team: <?php echo json_encode($isReassign ? 'Reassign to Team' : 'Assign to Team'); ?>, vendor: 'Close Ticket' };

    const typeSel = document.getElementById('facType');
    const dept = document.getElementById('facDept');
    const staffSel = document.getElementById('facStaff');
    const deptBox = document.getElementById('facDeptBox');
    const staffBox = document.getElementById('facStaffBox');
    const teamBox = document.getElementById('facTeamBox');
    const vendorBox = document.getElementById('facVendorBox');
    const workBox = document.getElementById('facWorkBox');
    const teamList = document.getElementById('facTeamList');
    const teamChips = document.getElementById('facTeamChips');
    const teamIds = document.getElementById('facTeamIds');
    const teamCount = document.getElementById('facTeamCount');
    const submitBtn = document.getElementById('facSubmit');
    const form = document.getElementById('facAssignForm');

    let rows = <?php echo json_encode(array_values($picker ?? [])); ?>;
    const selected = new Map(); // staff_id -> name (insertion order = lead first)

    function label(p) {
        return (p.staff_name || p.staff_id) + ' (' + (p.open_tickets || 0) + ' open, ' + (p.overdue || 0) + ' overdue)';
    }
    function show(box, on) {
        box.hidden = !on;
        box.querySelectorAll('input, select, textarea').forEach(function (el) { el.disabled = !on; });
    }

    function renderStaff(message) {
        const keep = staffSel.value;
        staffSel.innerHTML = '';
        const first = document.createElement('option');
        first.value = '';
        first.textContent = message || (rows.length ? 'Select a staff member' : 'No staff found');
        staffSel.appendChild(first);
        rows.forEach(function (p) {
            if (isReassign && String(p.staff_id) === currentStaff) { return; }
            const o = document.createElement('option');
            o.value = p.staff_id;
            o.textContent = label(p);
            if (String(p.staff_id) === keep) { o.selected = true; }
            staffSel.appendChild(o);
        });
    }

    function renderTeam(message) {
        teamList.innerHTML = '';
        if (message || !rows.length) {
            const m = document.createElement('div');
            m.className = 'text-muted small';
            m.textContent = message || 'No staff found in this department.';
            teamList.appendChild(m);
        }
        rows.forEach(function (p) {
            const id = String(p.staff_id);
            const wrap = document.createElement('label');
            wrap.className = 'd-flex align-items-center gap-2 py-1';
            wrap.style.cursor = 'pointer';
            const cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.className = 'form-check-input mt-0';
            cb.checked = selected.has(id);
            cb.addEventListener('change', function () {
                if (cb.checked) { selected.set(id, p.staff_name || id); } else { selected.delete(id); }
                renderSelection();
            });
            const txt = document.createElement('span');
            txt.textContent = label(p);
            wrap.appendChild(cb);
            wrap.appendChild(txt);
            teamList.appendChild(wrap);
        });
        renderSelection();
    }

    function renderSelection() {
        teamChips.innerHTML = '';
        teamIds.innerHTML = '';
        let i = 0;
        selected.forEach(function (name, id) {
            const chip = document.createElement('span');
            chip.className = 'badge bg-primary d-inline-flex align-items-center gap-1';
            chip.textContent = name + (i === 0 ? ' (lead)' : '');
            const x = document.createElement('button');
            x.type = 'button';
            x.className = 'btn-close btn-close-white';
            x.style.fontSize = '0.55rem';
            x.setAttribute('aria-label', 'Remove ' + name);
            x.addEventListener('click', function () { selected.delete(id); renderTeam(); });
            chip.appendChild(x);
            teamChips.appendChild(chip);
            const h = document.createElement('input');
            h.type = 'hidden';
            h.name = 'team_staff_ids[]';
            h.value = id;
            teamIds.appendChild(h);
            i++;
        });
        const n = selected.size;
        teamCount.textContent = n === 0 ? 'Select at least 2 staff members. The first one selected is the team lead.' : (n === 1 ? '1 member selected. Select at least one more.' : n + ' members selected.');
        teamCount.className = 'form-text mt-1 ' + (n === 1 ? 'text-danger' : '');
    }

    function renderAll(message) { renderStaff(message); renderTeam(message); }

    function applyType() {
        const type = typeSel.value;
        show(deptBox, type !== 'vendor');
        show(staffBox, type === 'staff');
        show(teamBox, type === 'team');
        show(vendorBox, type === 'vendor');
        show(workBox, type !== 'vendor');
        submitBtn.textContent = labels[type] || labels.staff;
        submitBtn.className = 'btn ' + (type === 'vendor' ? 'btn-danger' : 'btn-primary');
    }

    dept.addEventListener('change', function () {
        renderAll('Loading staff...');
        fetch('<?php echo $base; ?>/staff-options?department_id=' + encodeURIComponent(dept.value), { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) { rows = d.rows || []; renderAll(rows.length ? '' : 'No staff found in this department'); })
            .catch(function () { rows = []; renderAll('Staff could not be loaded. Please try again.'); });
    });
    typeSel.addEventListener('change', applyType);

    form.addEventListener('submit', function (e) {
        const type = typeSel.value;
        if (type === 'team' && selected.size < 2) {
            e.preventDefault();
            alert('Please select at least two staff members for a team.');
            return;
        }
        if (type === 'vendor' && !confirm('This will close the ticket. Do you want to continue?')) {
            e.preventDefault();
        }
    });

    renderAll();
    applyType();
})();
</script>
