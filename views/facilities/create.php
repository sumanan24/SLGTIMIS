<?php require BASE_PATH . '/views/facilities/partials/helpers.php'; ?>
<div class="container-fluid px-0 fac-page">
    <div class="card border-0">
        <div class="card-header"><h5 class="fw-bold mb-0">Report a problem</h5></div>
        <div class="card-body">
            <?php $facNav(); $facFlash(); ?>
            <p class="fac-help">Tell us what is broken and where it is. A ticket number is created automatically.</p>
            <div class="fac-chips">
                <div class="fac-chip"><span>You</span><strong><?php echo $h($actor['name'] ?? ''); ?></strong></div>
                <div class="fac-chip"><span>Department</span><strong><?php echo $h($actor['department_id'] ?? '—'); ?></strong></div>
            </div>
            <form method="post" action="<?php echo $base; ?>/store" enctype="multipart/form-data">
                <div class="fac-section">
                    <h6>What is the problem?</h6>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Short title <span class="text-danger">*</span></label>
                            <input class="form-control" name="title" required maxlength="200" placeholder="e.g. Light not working in Lab 2">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Category <span class="text-danger">*</span></label>
                            <select class="form-select" name="category_id" id="facCat" required>
                                <option value="">Choose</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo (int) $cat['id']; ?>"><?php echo $h($cat['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Subcategory</label>
                            <select class="form-select" name="subcategory_id" id="facSub"><option value="">Optional</option></select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Priority <span class="text-danger">*</span></label>
                            <select class="form-select" name="priority" required>
                                <?php foreach ($prLabel as $k => $lab): ?>
                                    <option value="<?php echo $h($k); ?>" <?php echo $k === 'medium' ? 'selected' : ''; ?>><?php echo $h($lab); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Details <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="description" rows="4" required placeholder="What happened, and what should be fixed?"></textarea>
                        </div>
                    </div>
                </div>
                <div class="fac-section">
                    <h6>Where is it?</h6>
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label">Location <span class="text-danger">*</span></label>
                            <input class="form-control" name="location" required placeholder="e.g. Main workshop">
                        </div>
                        <div class="col-md-3"><label class="form-label">Building</label><input class="form-control" name="building"></div>
                        <div class="col-md-2"><label class="form-label">Floor</label><input class="form-control" name="floor"></div>
                        <div class="col-md-2"><label class="form-label">Room</label><input class="form-control" name="room_area"></div>
                    </div>
                </div>
                <div class="fac-section">
                    <h6>Extra (optional)</h6>
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Needed by</label><input type="date" class="form-control" name="required_date"></div>
                        <div class="col-md-4"><label class="form-label">Your contact</label><input class="form-control" name="contact" value="<?php echo $h($actor['contact'] ?? ''); ?>"></div>
                        <div class="col-md-4"><label class="form-label">Remarks</label><input class="form-control" name="remarks"></div>
                        <div class="col-12">
                            <label class="form-label">Photos</label>
                            <input type="file" class="form-control" name="evidence[]" multiple accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx">
                            <div class="form-text">JPG, PNG or PDF — max 5 MB each.</div>
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Submit ticket</button>
                    <a class="btn btn-outline-secondary" href="<?php echo $base; ?>">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.getElementById('facCat').addEventListener('change', function () {
    const sel = document.getElementById('facSub');
    sel.innerHTML = '<option>Loading…</option>';
    fetch('<?php echo $base; ?>/subcategories?category_id=' + encodeURIComponent(this.value))
        .then(r => r.json())
        .then(d => {
            sel.innerHTML = '<option value="">Optional</option>';
            (d.rows || []).forEach(row => {
                const o = document.createElement('option');
                o.value = row.id; o.textContent = row.name;
                sel.appendChild(o);
            });
        });
});
</script>
