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
                    This ticket is currently assigned to <strong><?php echo $h($currentAssignee !== '' ? $currentAssignee : 'another person'); ?></strong>.
                    The new assignee will receive an SMS notification.
                </div>
            <?php endif; ?>

            <form method="post" action="<?php echo $base; ?>/assign-save" id="facAssignForm">
                <input type="hidden" name="id" value="<?php echo (int) $t['id']; ?>">
                <input type="hidden" name="assignment_type" id="facType" value="staff">

                <div class="fac-section">
                    <h6>Assignee</h6>
                    <div class="row g-3 mb-2" id="facDeptBox">
                        <div class="col-md-6">
                            <label class="form-label" for="facDept">Department</label>
                            <?php if (!empty($lock_department)):
                                $lockName = '';
                                foreach ($departments as $d) {
                                    if ((string) $d['department_id'] === (string) $selected_department) { $lockName = (string) $d['department_name']; break; }
                                } ?>
                                <input type="hidden" id="facDept" value="<?php echo $h($selected_department); ?>">
                                <div class="form-control-plaintext fw-semibold"><i class="fas fa-building me-2 text-muted"></i><?php echo $h($lockName !== '' ? $lockName : $selected_department); ?></div>
                                <div class="form-text">Your own department.</div>
                            <?php else: ?>
                                <select class="form-select" id="facDept">
                                    <option value="">All departments</option>
                                    <?php foreach ($departments as $d): ?>
                                        <option value="<?php echo $h($d['department_id']); ?>" <?php echo (($selected_department ?? '') === $d['department_id']) ? 'selected' : ''; ?>><?php echo $h($d['department_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="facSearch">Find a person</label>
                            <input type="search" class="form-control" id="facSearch" placeholder="Type a name..." autocomplete="off">
                        </div>
                    </div>

                    <div id="facStaffBox">
                        <label class="form-label">Staff Members <span class="text-danger">*</span></label>
                        <div id="facChips" class="d-flex flex-wrap gap-2 mb-2"></div>
                        <div id="facStaffList" class="fac-staff-list" role="group" aria-label="Staff members"></div>
                        <div id="facIds"></div>
                        <div class="form-text mt-1" id="facCount"></div>
                    </div>

                    <div class="form-check mt-3" id="facVendorToggleBox">
                        <input class="form-check-input" type="checkbox" id="facVendorToggle">
                        <label class="form-check-label" for="facVendorToggle">Hand over to an external vendor instead (closes the ticket)</label>
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
                            <div class="col-md-6">
                                <label class="form-label" for="facStart">Start Date</label>
                                <input type="date" class="form-control" name="start_date" id="facStart" value="<?php echo date('Y-m-d'); ?>">
                            </div>

                            <div class="col-md-6">
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
    var isReassign = <?php echo $isReassign ? 'true' : 'false'; ?>;
    var currentStaff = <?php echo json_encode((string) ($t['responsible_staff_id'] ?? '')); ?>;
    var baseLabel = <?php echo json_encode($isReassign ? 'Reassign Ticket' : 'Assign Ticket'); ?>;
    var teamLabel = <?php echo json_encode($isReassign ? 'Reassign to Team' : 'Assign to Team'); ?>;

    var typeInp = document.getElementById('facType');
    var dept = document.getElementById('facDept');
    var search = document.getElementById('facSearch');
    var deptBox = document.getElementById('facDeptBox');
    var staffBox = document.getElementById('facStaffBox');
    var listBox = document.getElementById('facStaffList');
    var chips = document.getElementById('facChips');
    var idsBox = document.getElementById('facIds');
    var countEl = document.getElementById('facCount');
    var vendorToggle = document.getElementById('facVendorToggle');
    var vendorBox = document.getElementById('facVendorBox');
    var workBox = document.getElementById('facWorkBox');
    var submitBtn = document.getElementById('facSubmit');
    var form = document.getElementById('facAssignForm');

    var rows = <?php echo json_encode(array_values($picker ?? [])); ?>;
    var selected = new Map(); // staff_id -> name; the first one is the lead
    var vendorMode = false;

    function setEnabled(box, on) {
        box.hidden = !on;
        box.querySelectorAll('input, select, textarea').forEach(function (el) { el.disabled = !on; });
    }

    function renderList(message) {
        listBox.innerHTML = '';
        var q = search.value.trim().toLowerCase();
        var shown = 0;
        if (!message) {
            rows.forEach(function (p) {
                var id = String(p.staff_id);
                if (isReassign && id === currentStaff) { return; }
                var name = p.staff_name || id;
                var text = (name + ' ' + id + ' ' + (p.staff_position || '')).toLowerCase();
                if (q && text.indexOf(q) < 0) { return; }
                shown++;
                var row = document.createElement('label');
                row.className = 'fac-staff-row' + (selected.has(id) ? ' is-on' : '');
                var cb = document.createElement('input');
                cb.type = 'checkbox';
                cb.className = 'form-check-input';
                cb.checked = selected.has(id);
                cb.addEventListener('change', function () {
                    if (cb.checked) { selected.set(id, name); } else { selected.delete(id); }
                    row.classList.toggle('is-on', cb.checked);
                    renderSelection();
                });
                var info = document.createElement('span');
                info.className = 'fac-staff-info';
                var strong = document.createElement('strong');
                strong.textContent = name;
                var small = document.createElement('small');
                small.textContent = (p.staff_position ? p.staff_position + ' · ' : '') + (p.open_tickets || 0) + ' open' + (parseInt(p.overdue || 0, 10) > 0 ? ', ' + p.overdue + ' overdue' : '');
                info.appendChild(strong);
                info.appendChild(small);
                row.appendChild(cb);
                row.appendChild(info);
                listBox.appendChild(row);
            });
        }
        if (message || !shown) {
            var m = document.createElement('div');
            m.className = 'fac-staff-empty';
            m.textContent = message || (q ? 'No one matches "' + search.value.trim() + '".' : 'No staff found in this department.');
            listBox.appendChild(m);
        }
        renderSelection();
    }

    function renderSelection() {
        chips.innerHTML = '';
        idsBox.innerHTML = '';
        var i = 0;
        selected.forEach(function (name, id) {
            var chip = document.createElement('span');
            chip.className = 'badge bg-primary d-inline-flex align-items-center gap-1';
            chip.textContent = name + (selected.size > 1 && i === 0 ? ' (lead)' : '');
            var x = document.createElement('button');
            x.type = 'button';
            x.className = 'btn-close btn-close-white';
            x.style.fontSize = '0.55rem';
            x.setAttribute('aria-label', 'Remove ' + name);
            x.addEventListener('click', function () { selected.delete(id); renderList(); });
            chip.appendChild(x);
            chips.appendChild(chip);
            if (!vendorMode) {
                var h = document.createElement('input');
                h.type = 'hidden';
                h.name = 'team_staff_ids[]';
                h.value = id;
                idsBox.appendChild(h);
            }
            i++;
        });
        var n = selected.size;
        typeInp.value = vendorMode ? 'vendor' : (n > 1 ? 'team' : 'staff');
        countEl.textContent = n === 0
            ? 'Tick one or more people. One person is assigned directly; two or more form a team and the first ticked is the lead.'
            : (n === 1 ? '1 person selected.' : n + ' people selected as a team. The first one ticked is the team lead.');
        if (!vendorMode) {
            submitBtn.textContent = n > 1 ? teamLabel + ' (' + n + ')' : baseLabel;
        }
    }

    function applyVendor() {
        vendorMode = vendorToggle.checked;
        setEnabled(deptBox, !vendorMode);
        setEnabled(staffBox, !vendorMode);
        setEnabled(vendorBox, vendorMode);
        setEnabled(workBox, !vendorMode);
        submitBtn.textContent = vendorMode ? 'Close Ticket' : baseLabel;
        submitBtn.className = 'btn ' + (vendorMode ? 'btn-danger' : 'btn-primary');
        renderSelection();
    }

    dept.addEventListener('change', function () {
        selected.clear();
        rows = [];
        renderList('Loading staff...');
        fetch('<?php echo $base; ?>/staff-options?department_id=' + encodeURIComponent(dept.value), { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) { rows = d.rows || []; renderList(); })
            .catch(function () { rows = []; renderList('Staff could not be loaded. Please try again.'); });
    });
    search.addEventListener('input', function () { renderList(); });
    search.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); } });
    vendorToggle.addEventListener('change', applyVendor);

    form.addEventListener('submit', function (e) {
        if (vendorMode) {
            if (!confirm('This will close the ticket. Do you want to continue?')) { e.preventDefault(); }
            return;
        }
        if (selected.size < 1) {
            e.preventDefault();
            alert('Please tick at least one staff member.');
        }
    });

    renderList();
    applyVendor();
})();
</script>
