<?php require BASE_PATH . '/views/facilities/partials/helpers.php'; ?>
<div class="container-fluid px-0 fac-page">
    <div class="card border-0">
        <?php $facHeader('Ticket Categories', 'Add, edit and delete categories. The responsible person receives every ticket in that category.', '<button type="button" class="btn btn-sm btn-light" id="facCatAdd"><i class="fas fa-plus me-1"></i>Add Category</button>'); ?>
        <div class="card-body">
            <?php $facNav(); $facFlash(); ?>

            <div class="fac-table-wrap">
                <table class="table table-sm mb-0 fac-cat-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Responsible person</th>
                            <th>Subcategories</th>
                            <th class="text-end">Tickets</th>
                            <th>Status</th>
                            <th class="fac-col-act text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="6"><div class="fac-empty">No categories yet. Use Add Category.</div></td></tr>
                    <?php else: foreach ($rows as $cat):
                        $tickets = (int) ($cat['ticket_count'] ?? 0);
                        $person = trim((string) ($cat['staff_name'] ?? ''));
                        $isActive = (int) ($cat['is_active'] ?? 1) === 1;
                    ?>
                        <tr>
                            <td data-label="Category"><strong><?php echo $h($cat['name']); ?></strong></td>
                            <td data-label="Responsible person">
                                <?php if (!empty($cat['staff_id'])): ?>
                                    <?php echo $h($person !== '' ? $person : $cat['staff_id']); ?> <span class="text-muted small">(<?php echo $h($cat['staff_id']); ?>)</span>
                                <?php else: ?>
                                    <span class="text-muted">Automatic (department head / Facility Officer)</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Subcategories">
                                <?php $hasSub = false; foreach ($subcategories as $sub): if ((int) $sub['category_id'] === (int) $cat['id']): $hasSub = true; ?>
                                    <span class="fac-badge fac-s-new"><?php echo $h($sub['name']); ?></span>
                                <?php endif; endforeach; if (!$hasSub): ?><span class="text-muted">—</span><?php endif; ?>
                            </td>
                            <td data-label="Tickets" class="text-end"><?php echo $tickets; ?></td>
                            <td data-label="Status">
                                <span class="fac-badge <?php echo $isActive ? 'fac-s-verified' : 'fac-s-on_hold'; ?>"><?php echo $isActive ? 'Active' : 'Inactive'; ?></span>
                            </td>
                            <td data-label="Actions" class="text-end text-nowrap">
                                <button type="button" class="btn btn-sm btn-outline-primary fac-cat-edit"
                                        data-id="<?php echo (int) $cat['id']; ?>"
                                        data-name="<?php echo $h($cat['name']); ?>"
                                        data-staff="<?php echo $h($cat['staff_id'] ?? ''); ?>"
                                        data-sort="<?php echo (int) $cat['sort_order']; ?>"
                                        data-active="<?php echo $isActive ? 1 : 0; ?>"><i class="fas fa-pen me-1"></i>Edit</button>
                                <?php if ($tickets > 0): ?>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="This category has <?php echo $tickets; ?> ticket(s). Set it to Inactive instead of deleting."><i class="fas fa-lock"></i></button>
                                <?php else: ?>
                                    <form method="post" action="<?php echo $base; ?>/categories/delete" class="d-inline" onsubmit="return confirm('Delete the category &quot;<?php echo $h(addslashes($cat['name'])); ?>&quot; and its subcategories?');">
                                        <input type="hidden" name="id" value="<?php echo (int) $cat['id']; ?>">
                                        <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <p class="fac-note mt-2 mb-3">A category with tickets cannot be deleted; set it to Inactive to hide it from the Create New Ticket form.</p>

            <form method="post" action="<?php echo $base; ?>/categories/save" class="fac-section">
                <input type="hidden" name="kind" value="subcategory">
                <h6>Add subcategory</h6>
                <div class="fac-inline-form">
                    <div>
                        <label class="form-label" for="facSubCat">Category</label>
                        <select class="form-select" id="facSubCat" name="category_id" required>
                            <?php foreach ($rows as $cat): ?><option value="<?php echo (int) $cat['id']; ?>"><?php echo $h($cat['name']); ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="facSubName">Subcategory name</label>
                        <input class="form-control" id="facSubName" name="name" placeholder="e.g. Fire extinguisher" required maxlength="120">
                    </div>
                    <div class="fac-go"><button class="btn btn-outline-primary"><i class="fas fa-plus me-1"></i>Add subcategory</button></div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="facCatModal" tabindex="-1" aria-labelledby="facCatModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content fac-page" method="post" action="<?php echo $base; ?>/categories/save">
            <input type="hidden" name="kind" value="category">
            <input type="hidden" name="id" id="facCatId" value="">
            <div class="modal-header">
                <h5 class="modal-title" id="facCatModalTitle">Add Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label" for="facCatName">Category name <span class="text-danger">*</span></label>
                    <input class="form-control" id="facCatName" name="name" required maxlength="120" placeholder="e.g. Safety">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="facCatStaff">Responsible person</label>
                    <select class="form-select" id="facCatStaff" name="staff_id">
                        <option value="">Automatic (department head / Facility Officer)</option>
                        <?php foreach ($staff_options as $staff): ?>
                            <option value="<?php echo $h($staff['staff_id']); ?>"><?php echo $h($staff['staff_name'] . ' (' . $staff['staff_id'] . ')'); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">New tickets in this category are sent to this person.</div>
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label" for="facCatSort">Display order</label>
                        <input class="form-control" type="number" min="0" id="facCatSort" name="sort_order" value="100">
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="facCatActive">Status</label>
                        <select class="form-select" id="facCatActive" name="is_active">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Category</button>
            </div>
        </form>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var el = document.getElementById('facCatModal');
    if (!el || typeof bootstrap === 'undefined') { return; }
    var modal = new bootstrap.Modal(el);
    function fill(d) {
        document.getElementById('facCatModalTitle').textContent = d.id ? 'Edit Category' : 'Add Category';
        document.getElementById('facCatId').value = d.id || '';
        document.getElementById('facCatName').value = d.name || '';
        document.getElementById('facCatStaff').value = d.staff || '';
        document.getElementById('facCatSort').value = d.sort !== undefined && d.sort !== '' ? d.sort : 100;
        document.getElementById('facCatActive').value = d.active !== undefined ? d.active : 1;
        modal.show();
    }
    document.getElementById('facCatAdd').addEventListener('click', function () { fill({}); });
    document.querySelectorAll('.fac-cat-edit').forEach(function (btn) {
        btn.addEventListener('click', function () { fill(btn.dataset); });
    });
    el.addEventListener('shown.bs.modal', function () { document.getElementById('facCatName').focus(); });
});
</script>