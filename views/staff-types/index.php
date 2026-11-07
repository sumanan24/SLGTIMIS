<?php
$h = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$types = $types ?? [];
$canAdd = !empty($canAdd);
$canEdit = !empty($canEdit);
$canDelete = !empty($canDelete);
$csrf = $csrf ?? '';
$showActions = $canEdit || $canDelete;
$activeCount = 0;
$staffTotal = 0;
foreach ($types as $t) {
    if ((int) $t['is_active'] === 1) {
        $activeCount++;
    }
    $staffTotal += (int) $t['staff_total'];
}
?>
<style>
.sty { --sty-text:#0f172a; --sty-muted:#64748b; --sty-border:#e2e8f0; --sty-soft:#f8fafc; --sty-navy:#001f3f; max-width:1200px; }
.sty-head { display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-bottom:1.1rem; }
.sty-title { font-size:1.3rem; font-weight:700; color:var(--sty-text); margin:0 0 .2rem; line-height:1.3; }
.sty-title i { color:#0d6efd; }
.sty-lead { margin:0; font-size:.875rem; color:var(--sty-muted); }
.sty-head .btn { flex-shrink:0; min-height:40px; display:inline-flex; align-items:center; justify-content:center; white-space:nowrap; }

.sty-stats { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.75rem; margin-bottom:1rem; }
.sty-stat { background:#fff; border:1px solid var(--sty-border); border-radius:.65rem; padding:.8rem 1rem; }
.sty-stat b { display:block; font-size:1.4rem; line-height:1.2; color:var(--sty-text); font-variant-numeric:tabular-nums; }
.sty-stat span { font-size:.75rem; font-weight:600; letter-spacing:.04em; text-transform:uppercase; color:var(--sty-muted); }

.sty-panel { background:#fff; border:1px solid var(--sty-border); border-radius:.65rem; overflow:hidden; }
.sty-table { width:100%; margin:0; table-layout:fixed; border-collapse:collapse; }
.sty-table thead th { padding:.7rem 1rem; font-size:.7rem; font-weight:700; letter-spacing:.05em; text-transform:uppercase; color:var(--sty-muted); background:var(--sty-soft); border-bottom:1px solid var(--sty-border); text-align:left; vertical-align:middle; white-space:nowrap; }
.sty-table tbody td { padding:.8rem 1rem; vertical-align:middle; border-bottom:1px solid #f1f5f9; font-size:.875rem; color:var(--sty-text); overflow:hidden; }
.sty-table tbody tr:last-child td { border-bottom:0; }
.sty-table tbody tr:hover td { background:var(--sty-soft); }
.sty-table col.c-no { width:56px; } .sty-table col.c-name { width:24%; } .sty-table col.c-desc { width:auto; }
.sty-table col.c-staff { width:110px; } .sty-table col.c-status { width:120px; } .sty-table col.c-actions { width:112px; }
.sty-table th.c-no, .sty-table td.c-no { text-align:center; color:var(--sty-muted); }
.sty-table th.c-staff, .sty-table td.c-staff { text-align:center; }
.sty-table th.c-actions, .sty-table td.c-actions { text-align:right; }
.sty-name { display:flex; align-items:center; gap:.5rem; min-width:0; font-weight:600; }
.sty-name span.t { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.sty-tag { flex-shrink:0; font-size:.65rem; font-weight:700; letter-spacing:.04em; text-transform:uppercase; padding:.12rem .45rem; border-radius:4px; background:#eef2ff; color:#3730a3; }
.sty-desc { color:var(--sty-muted); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; display:block; }
.sty-count { display:inline-block; min-width:2rem; padding:.15rem .55rem; border-radius:999px; background:#f1f5f9; font-weight:700; font-size:.8rem; text-align:center; }
.sty-status { display:inline-flex; align-items:center; gap:.4rem; padding:.2rem .65rem; border-radius:999px; font-size:.75rem; font-weight:600; white-space:nowrap; }
.sty-status::before { content:""; width:6px; height:6px; border-radius:50%; background:currentColor; }
.sty-status.on { background:#dcfce7; color:#166534; } .sty-status.off { background:#f1f5f9; color:#64748b; }
.sty-actions { display:inline-flex; gap:.35rem; justify-content:flex-end; }
.sty-actions .btn { width:34px; height:34px; padding:0; display:inline-flex; align-items:center; justify-content:center; border-radius:8px; }
.sty-empty { text-align:center; padding:3rem 1rem; color:var(--sty-muted); }
.sty-empty i { display:block; font-size:2rem; color:#cbd5e1; margin-bottom:.75rem; }

.sty-modal .modal-content { border:0; border-radius:.75rem; }
.sty-modal .modal-header { border-bottom:1px solid var(--sty-border); padding:1rem 1.25rem; }
.sty-modal .modal-title { font-size:1.05rem; font-weight:700; }
.sty-modal .modal-body { padding:1.25rem; }
.sty-modal .modal-footer { border-top:1px solid var(--sty-border); padding:.85rem 1.25rem; gap:.5rem; }
.sty-modal .form-label { font-size:.8125rem; font-weight:600; color:#334155; margin-bottom:.4rem; }
.sty-modal .form-control { min-height:42px; border-radius:8px; border-color:#cbd5e1; }
.sty-modal textarea.form-control { min-height:84px; resize:vertical; }
.sty-modal .form-control:focus { border-color:var(--sty-navy); box-shadow:0 0 0 3px rgba(0,31,63,.12); }
.sty-modal .help-mini { margin:.4rem 0 0; font-size:.8rem; color:var(--sty-muted); }
.sty-modal .modal-footer .btn { min-height:40px; min-width:110px; }

@media (max-width:767.98px) {
    .sty.container-fluid { padding-left:.75rem !important; padding-right:.75rem !important; }
    .sty-head { flex-direction:column; align-items:stretch; } .sty-head .btn { width:100%; }
    .sty-stats { grid-template-columns:repeat(3,minmax(0,1fr)); gap:.5rem; }
    .sty-stat { padding:.65rem .7rem; } .sty-stat b { font-size:1.15rem; } .sty-stat span { font-size:.65rem; }
    .sty-table, .sty-table tbody, .sty-table tr, .sty-table td { display:block; width:100%; }
    .sty-table colgroup, .sty-table thead { display:none; }
    .sty-table tbody tr { padding:.85rem 1rem; border-bottom:1px solid var(--sty-border); }
    .sty-table tbody tr:last-child { border-bottom:0; }
    .sty-table tbody td, .sty-table tbody tr:hover td { padding:.3rem 0; border:0; background:transparent; text-align:left; }
    .sty-table td.c-no { display:none; }
    .sty-table td.c-staff, .sty-table td.c-status { display:flex; align-items:center; justify-content:space-between; }
    .sty-table td[data-label]::before { content:attr(data-label); font-size:.7rem; font-weight:700; letter-spacing:.05em; text-transform:uppercase; color:var(--sty-muted); }
    .sty-desc { white-space:normal; }
    .sty-table td.c-actions { padding-top:.6rem; }
    .sty-actions { width:100%; } .sty-actions .btn { flex:1 1 0; width:auto; height:40px; }
    .sty-modal .form-control { font-size:16px; }
    .sty-modal .modal-footer .btn { flex:1 1 0; }
}
</style>

<div class="container-fluid px-4 py-3 sty">
    <div class="sty-head">
        <div>
            <h1 class="sty-title"><i class="fas fa-id-badge me-2"></i>Staff types</h1>
            <p class="sty-lead">Manage the employment types offered when adding or editing staff.</p>
        </div>
        <?php if ($canAdd): ?>
            <button type="button" class="btn btn-primary" id="styAddBtn"><i class="fas fa-plus me-1"></i>Add type</button>
        <?php endif; ?>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-success alert-dismissible fade show py-2" role="alert"><?php echo $h($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show py-2" role="alert"><?php echo $h($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
    <?php endif; ?>

    <div class="sty-stats">
        <div class="sty-stat"><b><?php echo count($types); ?></b><span>Types</span></div>
        <div class="sty-stat"><b><?php echo $activeCount; ?></b><span>Active</span></div>
        <div class="sty-stat"><b><?php echo number_format($staffTotal); ?></b><span>Staff assigned</span></div>
    </div>

    <div class="sty-panel">
        <?php if (!empty($types)): ?>
            <div class="table-responsive">
                <table class="sty-table">
                    <colgroup>
                        <col class="c-no"><col class="c-name"><col class="c-desc"><col class="c-staff"><col class="c-status">
                        <?php if ($showActions): ?><col class="c-actions"><?php endif; ?>
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="c-no">#</th><th class="c-name">Type</th><th class="c-desc">Description</th>
                            <th class="c-staff">Staff</th><th class="c-status">Status</th>
                            <?php if ($showActions): ?><th class="c-actions">Actions</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($types as $i => $t): ?>
                            <?php
                            $isSystem = (int) $t['is_system'] === 1;
                            $isActive = (int) $t['is_active'] === 1;
                            $inUse = (int) $t['staff_total'] > 0;
                            $payload = [
                                'id' => (int) $t['staff_type_id'],
                                'name' => (string) $t['staff_type_name'],
                                'description' => (string) ($t['staff_type_description'] ?? ''),
                                'active' => $isActive,
                                'system' => $isSystem,
                            ];
                            ?>
                            <tr>
                                <td class="c-no"><?php echo $i + 1; ?></td>
                                <td class="c-name">
                                    <div class="sty-name">
                                        <span class="t" title="<?php echo $h($t['staff_type_name']); ?>"><?php echo $h($t['staff_type_name']); ?></span>
                                        <?php if ($isSystem): ?><span class="sty-tag" title="Required by the system">System</span><?php endif; ?>
                                    </div>
                                </td>
                                <td class="c-desc">
                                    <span class="sty-desc" title="<?php echo $h($t['staff_type_description'] ?? ''); ?>">
                                        <?php echo trim((string) ($t['staff_type_description'] ?? '')) !== '' ? $h($t['staff_type_description']) : '—'; ?>
                                    </span>
                                </td>
                                <td class="c-staff" data-label="Staff">
                                    <span class="sty-count" title="<?php echo (int) $t['staff_working']; ?> working"><?php echo (int) $t['staff_total']; ?></span>
                                </td>
                                <td class="c-status" data-label="Status">
                                    <span class="sty-status <?php echo $isActive ? 'on' : 'off'; ?>"><?php echo $isActive ? 'Active' : 'Inactive'; ?></span>
                                </td>
                                <?php if ($showActions): ?>
                                    <td class="c-actions">
                                        <div class="sty-actions">
                                            <?php if ($canEdit): ?>
                                                <button type="button" class="btn btn-outline-primary sty-edit" title="Edit" aria-label="Edit"
                                                        data-type="<?php echo $h(json_encode($payload)); ?>"><i class="fas fa-pen"></i></button>
                                            <?php endif; ?>
                                            <?php if ($canDelete): ?>
                                                <?php if ($isSystem || $inUse): ?>
                                                    <button type="button" class="btn btn-outline-secondary" disabled
                                                            title="<?php echo $isSystem ? 'Required by the system' : 'In use by staff. Mark it inactive instead.'; ?>"
                                                            aria-label="Cannot delete"><i class="fas fa-trash"></i></button>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-outline-danger sty-del" title="Delete" aria-label="Delete"
                                                            data-id="<?php echo (int) $t['staff_type_id']; ?>"
                                                            data-name="<?php echo $h($t['staff_type_name']); ?>"><i class="fas fa-trash"></i></button>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="sty-empty"><i class="fas fa-id-badge"></i><p class="mb-0">No staff types yet.</p></div>
        <?php endif; ?>
    </div>
</div>

<?php if ($canAdd || $canEdit): ?>
<div class="modal fade sty-modal" id="styFormModal" tabindex="-1" aria-labelledby="styFormTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="<?php echo APP_URL; ?>/staff-types/save" autocomplete="off">
            <input type="hidden" name="csrf" value="<?php echo $h($csrf); ?>">
            <input type="hidden" name="staff_type_id" id="styId" value="0">
            <div class="modal-header">
                <h5 class="modal-title" id="styFormTitle">Add staff type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label" for="styName">Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="styName" name="staff_type_name" maxlength="50" required>
                    <div class="help-mini" id="styNameHelp">Shown in the staff forms. Maximum 50 characters.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="styDesc">Description</label>
                    <textarea class="form-control" id="styDesc" name="staff_type_description" maxlength="255"></textarea>
                </div>
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" role="switch" id="styActive" name="is_active" value="1" checked>
                    <label class="form-check-label" for="styActive">Active (available when adding or editing staff)</label>
                </div>
                <div class="help-mini" id="styRename" style="display:none;">Renaming a type also updates every staff member who has it.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if ($canDelete): ?>
<div class="modal fade sty-modal" id="styDelModal" tabindex="-1" aria-labelledby="styDelTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="<?php echo APP_URL; ?>/staff-types/delete">
            <input type="hidden" name="csrf" value="<?php echo $h($csrf); ?>">
            <input type="hidden" name="staff_type_id" id="styDelId" value="0">
            <div class="modal-header">
                <h5 class="modal-title" id="styDelTitle">Delete staff type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">Delete <strong id="styDelName"></strong>? This cannot be undone.</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger"><i class="fas fa-trash me-1"></i>Delete</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
(function () {
    function $(id) { return document.getElementById(id); }
    function openModal(el) {
        if (!el || !window.bootstrap) return;
        document.body.appendChild(el); // avoid layout stacking contexts hiding the backdrop
        window.bootstrap.Modal.getOrCreateInstance(el).show();
    }

    var form = $('styFormModal');
    function fillForm(t) {
        $('styFormTitle').textContent = t ? 'Edit staff type' : 'Add staff type';
        $('styId').value = t ? t.id : 0;
        $('styName').value = t ? t.name : '';
        $('styDesc').value = t ? t.description : '';
        $('styActive').checked = t ? !!t.active : true;
        $('styName').readOnly = !!(t && t.system);
        $('styActive').disabled = !!(t && t.system);
        $('styNameHelp').textContent = (t && t.system)
            ? 'This name is required by the system and cannot be changed.'
            : 'Shown in the staff forms. Maximum 50 characters.';
        $('styRename').style.display = (t && !t.system) ? 'block' : 'none';
    }

    var add = $('styAddBtn');
    if (add && form) {
        add.addEventListener('click', function () { fillForm(null); openModal(form); });
    }
    document.querySelectorAll('.sty-edit').forEach(function (btn) {
        btn.addEventListener('click', function () {
            try { fillForm(JSON.parse(btn.getAttribute('data-type'))); openModal(form); } catch (e) {}
        });
    });
    if (form) {
        form.addEventListener('shown.bs.modal', function () { if (!$('styName').readOnly) $('styName').focus(); });
        // a disabled switch is not submitted; the server keeps system types active anyway
    }

    var del = $('styDelModal');
    document.querySelectorAll('.sty-del').forEach(function (btn) {
        btn.addEventListener('click', function () {
            $('styDelId').value = btn.getAttribute('data-id');
            $('styDelName').textContent = btn.getAttribute('data-name');
            openModal(del);
        });
    });
})();
</script>
