<style>
@media (max-width: 768px) {
    .module-edit-wrap.container-fluid { padding-left: 0 !important; padding-right: 0 !important; padding-top: 0.5rem; padding-bottom: 0.5rem; }
    .module-edit-wrap .card { border-radius: 0; }
    .module-edit-wrap .card-body { padding: 0.75rem 0.5rem !important; }
    .module-edit-wrap .d-flex.gap-2 { flex-direction: column; }
    .module-edit-wrap .d-flex.gap-2 .btn { width: 100%; justify-content: center; }
}
</style>
<?php $mod = $module ?? []; $ver = isset($mod['course_version']) ? (int)$mod['course_version'] : 0; $curSem = isset($mod['semester']) && $mod['semester'] !== '' && $mod['semester'] !== null ? (int)$mod['semester'] : ''; ?>
<div class="container-fluid px-4 py-3 module-edit-wrap">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-edit me-2"></i>Edit Module</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <div><?php echo htmlspecialchars($error); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    <form method="POST" action="<?php echo APP_URL; ?>/modules/edit?course_id=<?php echo urlencode($mod['course_id'] ?? ''); ?>&module_id=<?php echo urlencode($mod['module_id'] ?? ''); ?>&course_version=<?php echo $ver; ?>" id="moduleEditForm">
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-muted">Course</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars(($mod['course_name'] ?? '') . ' (' . ($mod['course_id'] ?? '') . ')'); ?>" disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-muted">Version</label>
                            <input type="text" class="form-control" value="<?php echo $ver === 0 ? 'Default (0)' : (int)$ver; ?>" disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-muted">Module ID</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($mod['module_id'] ?? ''); ?>" disabled>
                        </div>
                        <div class="mb-3">
                            <label for="module_name" class="form-label fw-semibold">Module Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="module_name" name="module_name" maxlength="255" required value="<?php echo htmlspecialchars($mod['module_name'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="semester" class="form-label fw-semibold">Semester</label>
                            <select class="form-select" id="semester" name="semester">
                                <option value="" <?php echo $curSem === '' ? 'selected' : ''; ?>>Not set</option>
                                <?php for ($s = 1; $s <= 8; $s++): ?>
                                    <option value="<?php echo $s; ?>" <?php echo $curSem !== '' && (int)$curSem === $s ? 'selected' : ''; ?>>Semester <?php echo $s; ?></option>
                                <?php endfor; ?>
                            </select>
                            <div class="form-text">Which semester this module belongs to (optional).</div>
                        </div>
                        <div class="mb-3">
                            <label for="credit" class="form-label fw-semibold">Credit</label>
                            <input type="number" class="form-control" id="credit" name="credit" min="0" step="0.5" value="<?php echo isset($mod['credit']) && $mod['credit'] !== '' && $mod['credit'] !== null ? htmlspecialchars((string)$mod['credit']) : ''; ?>">
                        </div>
                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save</button>
                            <a href="<?php echo APP_URL; ?>/modules" class="btn btn-outline-secondary"><i class="fas fa-times me-1"></i>Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
