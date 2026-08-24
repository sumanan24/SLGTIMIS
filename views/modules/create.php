<style>
.module-create-wrap .page-head {
    border: 1px solid rgba(0,0,0,.06);
    background: #fff;
}
.module-create-wrap .card { border: 1px solid rgba(0,0,0,.06); }
.module-create-wrap .form-label { margin-bottom: .25rem; }
.module-create-wrap .help-mini { font-size: .825rem; color: #64748b; }
.module-create-wrap .req { color: #dc3545; }
@media (max-width: 768px) {
    .module-create-wrap.container-fluid { padding-left: 0.75rem !important; padding-right: 0.75rem !important; }
    .module-create-wrap .d-flex.gap-2 { flex-direction: column; }
    .module-create-wrap .d-flex.gap-2 .btn { width: 100%; justify-content: center; }
}
</style>

<div class="container-fluid px-4 py-3 module-create-wrap">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1 fw-bold"><i class="fas fa-cubes text-primary me-2"></i>Modules</h1>
            <div class="text-muted small">Add a single module or import multiple modules from Excel.</div>
        </div>
        <a href="<?php echo APP_URL; ?>/modules" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <div><?php echo htmlspecialchars($error); ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <ul class="nav nav-tabs" id="moduleTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tab-single" data-bs-toggle="tab" data-bs-target="#pane-single" type="button" role="tab">
                        <i class="fas fa-plus-circle me-1"></i>Add module
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-import" data-bs-toggle="tab" data-bs-target="#pane-import" type="button" role="tab">
                        <i class="fas fa-file-excel me-1 text-success"></i>Import Excel
                    </button>
                </li>
            </ul>

            <div class="tab-content pt-3">
                <div class="tab-pane fade show active" id="pane-single" role="tabpanel" aria-labelledby="tab-single">
                    <form method="POST" action="<?php echo APP_URL; ?>/modules/create" id="moduleCreateForm">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label for="course_id" class="form-label fw-semibold">Course <span class="req">*</span></label>
                                <select class="form-select" id="course_id" name="course_id" required>
                                    <option value="">Select Course</option>
                                    <?php foreach ($courses as $c): ?>
                                        <option value="<?php echo htmlspecialchars($c['course_id']); ?>">
                                            <?php echo htmlspecialchars(($c['course_name'] ?? '') . ' (' . $c['course_id'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="help-mini mt-1">Choose the course this module belongs to.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="course_version" class="form-label fw-semibold">Version <span class="req">*</span></label>
                                <select class="form-select" id="course_version" name="course_version" required disabled>
                                    <option value="">Select course first</option>
                                </select>
                                <div class="help-mini mt-1">Loads versions for the selected course.</div>
                            </div>

                            <div class="col-md-4">
                                <label for="module_id" class="form-label fw-semibold">Module ID <span class="req">*</span></label>
                                <input type="text" class="form-control" id="module_id" name="module_id" maxlength="50" required placeholder="e.g., G50C001M10">
                                <div class="help-mini mt-1">Unique within course + version.</div>
                            </div>
                            <div class="col-md-8">
                                <label for="module_name" class="form-label fw-semibold">Module Name <span class="req">*</span></label>
                                <input type="text" class="form-control" id="module_name" name="module_name" maxlength="255" required placeholder="e.g., Automobile Engines">
                            </div>

                            <div class="col-md-4">
                                <label for="semester" class="form-label fw-semibold">Semester</label>
                                <select class="form-select" id="semester" name="semester">
                                    <option value="">Not set</option>
                                    <?php for ($s = 1; $s <= 8; $s++): ?>
                                        <option value="<?php echo $s; ?>">Semester <?php echo $s; ?></option>
                                    <?php endfor; ?>
                                </select>
                                <div class="help-mini mt-1">Optional grouping for reporting.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="credit" class="form-label fw-semibold">Credit</label>
                                <input type="number" class="form-control" id="credit" name="credit" min="0" step="0.5" placeholder="e.g., 2 or 1.5">
                                <div class="help-mini mt-1">Optional.</div>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <div class="w-100">
                                    <div class="help-mini mb-2">Tip: For bulk modules, use the Import tab.</div>
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-save me-1"></i>Save module</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="tab-pane fade" id="pane-import" role="tabpanel" aria-labelledby="tab-import">
                    <div class="alert alert-info py-2 small mb-3">
                        Excel/CSV must contain a header row:
                        <span class="font-monospace">module_id</span>, <span class="font-monospace">module_name</span>,
                        <span class="font-monospace">credit</span> (optional), <span class="font-monospace">semester</span> (optional).
                    </div>

                    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                        <div class="help-mini">Download a template and fill your modules, then import.</div>
                        <a class="btn btn-outline-success btn-sm" href="<?php echo APP_URL; ?>/modules/download-sample-excel">
                            <i class="fas fa-download me-1"></i>Download sample CSV
                        </a>
                    </div>

                    <form method="POST" action="<?php echo APP_URL; ?>/modules/import-excel" enctype="multipart/form-data" id="moduleImportForm">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label for="import_course_id" class="form-label fw-semibold">Course <span class="req">*</span></label>
                                <select class="form-select" id="import_course_id" name="course_id" required>
                                    <option value="">Select Course</option>
                                    <?php foreach ($courses as $c): ?>
                                        <option value="<?php echo htmlspecialchars($c['course_id']); ?>">
                                            <?php echo htmlspecialchars(($c['course_name'] ?? '') . ' (' . $c['course_id'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="import_course_version" class="form-label fw-semibold">Version <span class="req">*</span></label>
                                <select class="form-select" id="import_course_version" name="course_version" required disabled>
                                    <option value="">Select course first</option>
                                </select>
                            </div>

                            <div class="col-md-12">
                                <label for="modules_file" class="form-label fw-semibold">File <span class="req">*</span></label>
                                <input class="form-control" type="file" id="modules_file" name="modules_file" accept=".xlsx,.xls,.csv" required>
                                <div class="help-mini mt-1">Upload <span class="font-monospace">.xlsx</span> (recommended) or <span class="font-monospace">.csv</span>.</div>
                            </div>

                            <div class="col-md-12">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-success"><i class="fas fa-upload me-1"></i>Import modules</button>
                                    <a href="<?php echo APP_URL; ?>/modules" class="btn btn-outline-secondary">Cancel</a>
                                </div>
                            </div>
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

    // Import form versions
    var importCourseSelect = document.getElementById('import_course_id');
    var importVersionSelect = document.getElementById('import_course_version');
    function fillImportVersions() {
        if (!importCourseSelect || !importVersionSelect) return;
        var cid = importCourseSelect.value;
        importVersionSelect.innerHTML = '';
        importVersionSelect.disabled = true;
        if (!cid) {
            importVersionSelect.options.add(new Option('Select course first', '', true, true));
            return;
        }
        var versions = versionsByCourse[cid] || [0];
        importVersionSelect.options.add(new Option('Select version', '', true, true));
        versions.forEach(function(v) {
            var label = v === 0 ? 'Default (0)' : 'Version ' + v;
            importVersionSelect.options.add(new Option(label, v, false, false));
        });
        importVersionSelect.disabled = false;
    }
    if (importCourseSelect && importVersionSelect) {
        importCourseSelect.addEventListener('change', fillImportVersions);
        if (importCourseSelect.value) fillImportVersions();
    }
})();
</script>
