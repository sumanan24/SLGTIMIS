<style>
@media (max-width: 768px) {
    .module-create-wrap.container-fluid { padding-left: 0 !important; padding-right: 0 !important; padding-top: 0.5rem; padding-bottom: 0.5rem; }
    .module-create-wrap .card { border-radius: 0; }
    .module-create-wrap .card-body { padding: 0.75rem 0.5rem !important; }
    .module-create-wrap .d-flex.gap-2 { flex-direction: column; }
    .module-create-wrap .d-flex.gap-2 .btn { width: 100%; justify-content: center; }
}
</style>
<div class="container-fluid px-4 py-3 module-create-wrap">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-plus-circle me-2"></i>Add Module</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <div><?php echo htmlspecialchars($error); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    <form method="POST" action="<?php echo APP_URL; ?>/modules/create" id="moduleCreateForm">
                        <div class="mb-3">
                            <label for="course_id" class="form-label fw-semibold">Course <span class="text-danger">*</span></label>
                            <select class="form-select" id="course_id" name="course_id" required>
                                <option value="">Select Course</option>
                                <?php foreach ($courses as $c): ?>
                                    <option value="<?php echo htmlspecialchars($c['course_id']); ?>">
                                        <?php echo htmlspecialchars(($c['course_name'] ?? '') . ' (' . $c['course_id'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="course_version" class="form-label fw-semibold">Version <span class="text-danger">*</span></label>
                            <select class="form-select" id="course_version" name="course_version" required disabled>
                                <option value="">Select course first</option>
                            </select>
                            <div class="form-text">Select a course to load its versions.</div>
                        </div>
                        <div class="mb-3">
                            <label for="module_id" class="form-label fw-semibold">Module ID <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="module_id" name="module_id" maxlength="50" required placeholder="e.g., M1, ENG101">
                        </div>
                        <div class="mb-3">
                            <label for="module_name" class="form-label fw-semibold">Module Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="module_name" name="module_name" maxlength="255" required placeholder="e.g., Engineering Mathematics">
                        </div>
                        <div class="mb-3">
                            <label for="credit" class="form-label fw-semibold">Credit</label>
                            <input type="number" class="form-control" id="credit" name="credit" min="0" step="0.5" placeholder="e.g., 2 or 1.5">
                            <div class="form-text">Credit hours (optional).</div>
                        </div>
                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Add Module</button>
                            <a href="<?php echo APP_URL; ?>/modules" class="btn btn-outline-secondary"><i class="fas fa-times me-1"></i>Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
(function() {
    var versionsByCourse = <?php echo json_encode($versionsByCourse ?? []); ?>;
    var courseSelect = document.getElementById('course_id');
    var versionSelect = document.getElementById('course_version');
    if (!courseSelect || !versionSelect) return;
    function fillVersions() {
        var cid = courseSelect.value;
        versionSelect.innerHTML = '';
        versionSelect.disabled = true;
        if (!cid) {
            versionSelect.options.add(new Option('Select course first', '', true, true));
            return;
        }
        var versions = versionsByCourse[cid] || [0];
        versionSelect.options.add(new Option('Select version', '', true, true));
        versions.forEach(function(v) {
            var label = v === 0 ? 'Default (0)' : 'Version ' + v;
            versionSelect.options.add(new Option(label, v, false, false));
        });
        versionSelect.disabled = false;
    }
    courseSelect.addEventListener('change', fillVersions);
    if (courseSelect.value) fillVersions();
})();
</script>
