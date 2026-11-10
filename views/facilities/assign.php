<?php require BASE_PATH . '/views/facilities/partials/helpers.php';
$t = $ticket;
$isReassign = !empty($t['responsible_staff_id']) || !empty($t['responsible_department_id']);
$currentAssignee = $t['responsible_name'] ?? $t['responsible_staff_id'] ?? $t['responsible_department_name'] ?? '';
$requesterName = $t['requester_name'] ?? $t['requester_staff_id'] ?? '';
?>
<div class="container-fluid px-0 fac-page">
    <div class="card border-0">
        <?php $facHeader(
            $isReassign ? 'Reassign' : 'Assign',
            'Choose staff.'
        ); ?>
        <div class="card-body">
            <?php $facNav(); $facFlash(); ?>

            <div class="fac-section">
                <h6>Ticket</h6>
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
                    Current: <strong><?php echo $h($currentAssignee !== '' ? $currentAssignee : 'another person'); ?></strong>
                </div>
            <?php endif; ?>

            <?php
            $sjs = $subjobs ?? [];
            $sjLabels = $subjob_labels ?? [];
            $sjOpen = array_filter($sjs, static function ($r) { return in_array($r['status'], ['requested', 'assigned'], true); });
            ?>
            <?php if ($sjOpen): ?>
                <div class="fac-notice mb-3">
                    <i class="fas fa-hourglass-half me-1"></i>
                    <strong>Waiting for support</strong> (<?php echo count($sjOpen); ?>)
                    <a href="<?php echo $base; ?>/view?id=<?php echo (int) $t['id']; ?>#support">View</a>
                </div>
            <?php endif; ?>

            <form method="post" action="<?php echo $base; ?>/assign-save" id="facAssignForm">
                <input type="hidden" name="id" value="<?php echo (int) $t['id']; ?>">
                <input type="hidden" name="assignment_type" id="facType" value="staff">

                <div class="fac-section">
                    <h6><span class="fac-step">1</span>Assignee</h6>
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
                            <label class="form-label" for="facSearch">Search</label>
                            <input type="search" class="form-control" id="facSearch" placeholder="Name" autocomplete="off">
                        </div>
                    </div>

                    <div id="facStaffBox">
                        <label class="form-label">Staff <span class="text-danger">*</span></label>
                        <div id="facChips" class="d-flex flex-wrap gap-2 mb-2"></div>
                        <div id="facStaffList" class="fac-staff-list" role="group" aria-label="Staff members"></div>
                        <div id="facIds"></div>
                        <div class="form-text mt-1" id="facCount"></div>
                    </div>

                    <div class="form-check mt-3" id="facVendorToggleBox">
                        <input class="form-check-input" type="checkbox" id="facVendorToggle">
                        <label class="form-check-label" for="facVendorToggle">External vendor</label>
                    </div>
                    <div id="facVendorBox" class="mt-3" hidden>
                        <label class="form-label" for="facVendorReason">Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="vendor_reason" id="facVendorReason" rows="3" minlength="10" required placeholder="Reason"></textarea>
                        <div class="form-text">The ticket will be closed.</div>
                    </div>
                </div>
                <div id="facWorkBox">
                    <div class="fac-section">
                        <h6><span class="fac-step">2</span>Instructions</h6>
                        <textarea class="form-control" name="instructions" id="facInstr" rows="3" placeholder="Optional"></textarea>
                    </div>

                    <div class="fac-section">
                        <h6><span class="fac-step">3</span>Schedule</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="facStart">Start</label>
                                <input type="date" class="form-control" name="start_date" id="facStart" value="<?php echo date('Y-m-d'); ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="facFinish">Finish</label>
                                <input type="date" class="form-control" name="expected_completion_date" id="facFinish">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" id="facSubmit" type="submit">Assign</button>
                    <a class="btn btn-outline-secondary" href="<?php echo $base; ?>/view?id=<?php echo (int) $t['id']; ?>">Cancel</a>
                </div>
            </form>

            <?php if ($sjs || !empty($can_request_subjob)): ?>
            <div class="fac-support fac-support-after">
                <div class="fac-support-head">
                    <h6><i class="fas fa-people-arrows me-2"></i>Support</h6>
                    <?php if ($sjOpen): ?>
                        <span class="fac-support-wait"><i class="fas fa-hourglass-half"></i> Waiting (<?php echo count($sjOpen); ?>)</span>
                    <?php endif; ?>
                </div>
                <?php foreach ($sjs as $sj): ?>
                    <a class="fac-sj-line" href="<?php echo $base; ?>/view?id=<?php echo (int) $t['id']; ?>#support">
                        <span class="fac-sj-line-main">
                            <strong><?php echo $h($sj['department_name']); ?></strong>
                            <small><?php echo $h($sj['description']); ?></small>
                        </span>
                        <span class="fac-sj-line-meta">
                            <span class="fac-sj-chip fac-sj-chip-<?php echo $h($sj['status']); ?>"><?php echo $h($sjLabels[$sj['status']] ?? $sj['status']); ?></span>
                            <?php if (!empty($sj['assigned_name'])): ?><small><?php echo $h($sj['assigned_name']); ?></small><?php endif; ?>
                        </span>
                    </a>
                <?php endforeach; ?>
                <?php if (!empty($can_request_subjob)): ?>
                <details class="fac-road-more fac-road-request" <?php echo $sjs ? '' : 'open'; ?>>
                    <summary><i class="fas fa-plus me-1"></i>Request support</summary>
                    <form method="post" action="<?php echo $base; ?>/subjob-request" class="mt-2">
                        <input type="hidden" name="id" value="<?php echo (int) $t['id']; ?>">
                        <div class="row g-2">
                            <div class="col-md-5">
                                <label class="form-label" for="facSupDept">Department <span class="text-danger">*</span></label>
                                <select class="form-select" id="facSupDept" name="department_id" required>
                                    <option value="">Choose</option>
                                    <?php foreach ($departments as $dp):
                                        if ((string) $dp['department_id'] === (string) ($t['responsible_department_id'] ?? '') || (!empty($lock_department) && (string) $dp['department_id'] === (string) ($selected_department ?? ''))) { continue; } ?>
                                        <option value="<?php echo $h($dp['department_id']); ?>"><?php echo $h($dp['department_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label" for="facSupDesc">Details <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="facSupDesc" name="description" rows="2" maxlength="1000" required placeholder="Details"></textarea>
                            </div>
                        </div>
                        <p class="fac-note mt-2 mb-2">The ticket waits until the support is done.</p>
                        <button class="btn btn-sm btn-primary">Send</button>
                    </form>
                </details>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
(function () {
    var isReassign = <?php echo $isReassign ? 'true' : 'false'; ?>;
    var currentStaff = <?php echo json_encode((string) ($t['responsible_staff_id'] ?? '')); ?>;
    var baseLabel = <?php echo json_encode('Assign'); ?>;
    var teamLabel = <?php echo json_encode('Assign'); ?>;

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
            m.textContent = message || (q ? 'No match.' : 'No staff.');
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
            ? 'Tick one or more.'
            : (n === 1 ? '1 selected' : n + ' selected, first is lead');
        if (!vendorMode) {
            submitBtn.textContent = baseLabel;
        }
    }

    function applyVendor() {
        vendorMode = vendorToggle.checked;
        setEnabled(deptBox, !vendorMode);
        setEnabled(staffBox, !vendorMode);
        setEnabled(vendorBox, vendorMode);
        setEnabled(workBox, !vendorMode);
        submitBtn.textContent = vendorMode ? 'Close' : baseLabel;
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
            if (!confirm('Close this ticket?')) { e.preventDefault(); }
            return;
        }
        if (selected.size < 1) {
            e.preventDefault();
            alert('Select staff.');
        }
    });

    renderList();
    applyVendor();
})();
</script>
