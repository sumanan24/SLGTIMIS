<?php
$group_id = $group_id ?? '';
$group = $group ?? null;
$entries = $entries ?? [];
$grid = $grid ?? [];
$weekdaysToShow = $weekdaysToShow ?? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
$timeSlots = $timeSlots ?? [];
$modules = $modules ?? [];
$staff = $staff ?? [];
$groupsList = $groupsList ?? [];
?>
<div class="container-fluid px-4 py-4">
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-primary text-white">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="mb-0 fw-bold"><i class="fas fa-calendar-alt me-2"></i><?php echo $group ? htmlspecialchars('Timetable: ' . $group['name']) : 'Group Timetable'; ?></h5>
            </div>
        </div>
        <div class="card-body">
            <?php if (isset($message)): ?>
                <div class="alert alert-success alert-dismissible fade show"><?php echo htmlspecialchars($message); ?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show"><?php echo htmlspecialchars($error); ?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>

            <?php if (!empty($groupsList)): ?>
                <form class="row g-2 align-items-end mb-4" method="get" action="<?php echo APP_URL; ?>/group-timetable/index">
                    <div class="col-md-6 col-lg-4">
                        <label class="form-label fw-bold mb-1">Select Group</label>
                        <select name="group_id" class="form-select" onchange="this.form.submit()">
                            <option value="">-- Choose a group --</option>
                            <?php foreach ($groupsList as $g): ?>
                                <option value="<?php echo htmlspecialchars($g['id']); ?>" <?php echo (string)$group_id === (string)$g['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(($g['name'] ?? '') . ' (' . ($g['course_name'] ?? '') . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            <?php endif; ?>

            <?php if ($group_id === ''): ?>
                <p class="text-muted mb-0">Select a group above to view its timetable.</p>
            <?php elseif (!$group): ?>
                <p class="text-danger mb-0">Group not found.</p>
            <?php else: ?>
                <div class="card border bg-light mb-4">
                    <div class="card-body py-3">
                        <div class="row g-3 small">
                            <div class="col-sm-6 col-md-4">
                                <span class="text-muted d-block">Course</span>
                                <span class="fw-semibold"><?php echo htmlspecialchars($group['course_name'] ?? '—'); ?></span>
                            </div>
                            <div class="col-sm-6 col-md-4">
                                <span class="text-muted d-block">Department</span>
                                <span class="fw-semibold"><?php echo htmlspecialchars($group['department_name'] ?? '—'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive rounded border timetable-wrap">
                    <table class="table table-hover align-middle mb-0 timetable-grid">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" class="text-center" style="width: 11%;">Day</th>
                                <th scope="col" style="width: 16%;">Time</th>
                                <th scope="col">Module</th>
                                <th scope="col">Staff</th>
                                <th scope="col" class="text-end" style="width: 14%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($weekdaysToShow as $day): ?>
                                <?php
                                $slotCount = count($timeSlots);
                                $rowInDay = 0;
                                ?>
                                <?php foreach ($timeSlots as $slotKey => $slotLabel): ?>
                                    <?php
                                    $entry = isset($grid[$day][$slotKey]) ? $grid[$day][$slotKey] : null;
                                    $entryPk = is_array($entry) ? ($entry['entry_id'] ?? $entry['id'] ?? $entry['timetable_id'] ?? null) : null;
                                    $isAllocated = $entryPk !== null && $entryPk !== '';
                                    $modName = $isAllocated ? trim((string)($entry['module_name'] ?? '')) : '';
                                    $modDisp = $isAllocated
                                        ? ($modName !== '' ? $modName : (string)($entry['module_id'] ?? '—'))
                                        : '';
                                    $staffName = $isAllocated ? trim((string)($entry['staff_name'] ?? '')) : '';
                                    $staffDisp = $isAllocated
                                        ? ($staffName !== '' ? $staffName : (string)($entry['staff_id'] ?? '—'))
                                        : '';
                                    ?>
                                    <tr class="timetable-row">
                                        <?php if ($rowInDay === 0): ?>
                                            <td class="bg-light text-center fw-semibold align-middle" rowspan="<?php echo (int)$slotCount; ?>"><?php echo htmlspecialchars($day); ?></td>
                                        <?php endif; ?>
                                        <td class="text-nowrap text-muted small"><?php echo htmlspecialchars($slotLabel); ?></td>
                                        <td class="timetable-cell-module"><?php echo $isAllocated ? htmlspecialchars($modDisp) : '<span class="text-muted">—</span>'; ?></td>
                                        <td class="timetable-cell-staff"><?php echo $isAllocated ? htmlspecialchars($staffDisp) : '<span class="text-muted">—</span>'; ?></td>
                                        <td class="text-end text-nowrap">
                                            <?php if ($isAllocated): ?>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="<?php echo APP_URL; ?>/group-timetable/delete?id=<?php echo urlencode((string)$entryPk); ?>" class="btn btn-outline-danger" onclick="return confirm('Delete this slot?');" title="Delete">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </a>
                                                    <a href="<?php echo APP_URL; ?>/group-timetable/edit?id=<?php echo urlencode((string)$entryPk); ?>" class="btn btn-outline-primary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                </div>
                                            <?php else: ?>
                                                <button type="button"
                                                    class="btn btn-success btn-sm btn-add-slot"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#timetableAddModal"
                                                    data-day="<?php echo htmlspecialchars($day); ?>"
                                                    data-slot-key="<?php echo htmlspecialchars($slotKey); ?>"
                                                    data-slot-label="<?php echo htmlspecialchars($slotLabel); ?>">
                                                    <i class="fas fa-plus me-1"></i>Add
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php $rowInDay++; ?>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($group && $group_id !== '' && !empty($timeSlots)): ?>
<!-- Add slot modal -->
<div class="modal fade" id="timetableAddModal" tabindex="-1" aria-labelledby="timetableAddModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="timetableAddModalLabel"><i class="fas fa-plus-circle me-2 text-success"></i>Add timetable slot</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="timetableAddAlert" class="alert alert-danger py-2 small d-none" role="alert"></div>
                <form id="timetableAddForm" novalidate>
                    <input type="hidden" name="group_id" id="timetableAddGroupId" value="<?php echo htmlspecialchars((string)$group_id); ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="timetableAddDay">Day</label>
                        <select class="form-select" id="timetableAddDay" name="day" required>
                            <?php foreach ($weekdaysToShow as $d): ?>
                                <option value="<?php echo htmlspecialchars($d); ?>"><?php echo htmlspecialchars($d); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="timetableAddSlot">Time slot</label>
                        <select class="form-select" id="timetableAddSlot" name="time_slot" required>
                            <?php foreach ($timeSlots as $sk => $sl): ?>
                                <option value="<?php echo htmlspecialchars($sk); ?>"><?php echo htmlspecialchars($sl); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">You can change the day or slot before saving.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="timetableAddModule">Module <span class="text-danger">*</span></label>
                        <select class="form-select" id="timetableAddModule" name="module_id" required>
                            <option value="">— Select module —</option>
                            <?php foreach ($modules as $m): ?>
                                <option value="<?php echo htmlspecialchars((string)($m['module_id'] ?? '')); ?>">
                                    <?php echo htmlspecialchars($m['module_name'] ?? $m['module_id'] ?? '—'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold" for="timetableAddStaff">Staff <span class="text-danger">*</span></label>
                        <select class="form-select" id="timetableAddStaff" name="staff_id" required>
                            <option value="">— Select staff —</option>
                            <?php foreach ($staff as $s): ?>
                                <option value="<?php echo htmlspecialchars((string)($s['staff_id'] ?? '')); ?>">
                                    <?php echo htmlspecialchars($s['staff_name'] ?? $s['staff_id'] ?? '—'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="timetableAddSave">
                    <i class="fas fa-save me-1"></i>Save
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const modalEl = document.getElementById('timetableAddModal');
    const form = document.getElementById('timetableAddForm');
    const alertEl = document.getElementById('timetableAddAlert');
    const saveBtn = document.getElementById('timetableAddSave');
    const saveUrl = '<?php echo APP_URL; ?>/group-timetable/save-slot';

    function showModalError(msg) {
        if (!alertEl) return;
        alertEl.textContent = msg;
        alertEl.classList.remove('d-none');
    }
    function hideModalError() {
        if (!alertEl) return;
        alertEl.classList.add('d-none');
        alertEl.textContent = '';
    }

    if (modalEl) {
        modalEl.addEventListener('show.bs.modal', function(ev) {
            hideModalError();
            const btn = ev.relatedTarget;
            if (!btn || !btn.classList.contains('btn-add-slot')) return;
            const day = btn.getAttribute('data-day') || '';
            const slotKey = btn.getAttribute('data-slot-key') || '';
            const daySel = document.getElementById('timetableAddDay');
            const slotSel = document.getElementById('timetableAddSlot');
            if (daySel) {
                for (let i = 0; i < daySel.options.length; i++) {
                    if (daySel.options[i].value === day) {
                        daySel.selectedIndex = i;
                        break;
                    }
                }
            }
            if (slotSel) {
                for (let j = 0; j < slotSel.options.length; j++) {
                    if (slotSel.options[j].value === slotKey) {
                        slotSel.selectedIndex = j;
                        break;
                    }
                }
            }
        });
        modalEl.addEventListener('hidden.bs.modal', function() {
            hideModalError();
            if (form) form.reset();
        });
    }

    if (saveBtn && form) {
        saveBtn.addEventListener('click', function() {
            hideModalError();
            const groupId = (document.getElementById('timetableAddGroupId') || {}).value || '';
            const day = (document.getElementById('timetableAddDay') || {}).value || '';
            const timeSlot = (document.getElementById('timetableAddSlot') || {}).value || '';
            const moduleId = (document.getElementById('timetableAddModule') || {}).value || '';
            const staffId = (document.getElementById('timetableAddStaff') || {}).value || '';
            if (!moduleId || !staffId) {
                showModalError('Please select both Module and Staff.');
                return;
            }
            const body = new URLSearchParams({
                group_id: groupId,
                day: day,
                time_slot: timeSlot,
                module_id: moduleId,
                staff_id: staffId
            });
            saveBtn.disabled = true;
            fetch(saveUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: body.toString(),
                credentials: 'same-origin'
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    window.location.reload();
                    return;
                }
                let err = data.error || 'Save failed.';
                if (data.sql_error) {
                    err += ' SQL: ' + data.sql_error;
                }
                showModalError(err);
            })
            .catch(function() {
                showModalError('Network error. Please try again.');
            })
            .finally(function() {
                saveBtn.disabled = false;
            });
        });
    }
})();
</script>
<?php endif; ?>

<style>
.timetable-wrap { overflow: hidden; }
.timetable-grid thead th { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.03em; }
.timetable-grid tbody td { vertical-align: middle; padding-top: 0.65rem; padding-bottom: 0.65rem; }
.timetable-grid .timetable-cell-module,
.timetable-grid .timetable-cell-staff { font-size: 0.925rem; }
</style>
