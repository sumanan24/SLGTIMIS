<div class="container-fluid px-4 py-3">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-cube me-2"></i>Module Details</h5>
                </div>
                <div class="card-body">
                    <?php $m = $module ?? []; ?>
                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-muted">Course</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($m['course_name'] ?? $m['course_id']); ?> <span class="text-muted">(<?php echo htmlspecialchars($m['course_id'] ?? ''); ?>)</span></dd>
                        <dt class="col-sm-4 text-muted">Version</dt>
                        <dd class="col-sm-8"><?php echo isset($m['course_version']) && (int)$m['course_version'] > 0 ? (int)$m['course_version'] : 'Default (0)'; ?></dd>
                        <dt class="col-sm-4 text-muted">Module ID</dt>
                        <dd class="col-sm-8 fw-semibold"><?php echo htmlspecialchars($m['module_id'] ?? ''); ?></dd>
                        <dt class="col-sm-4 text-muted">Module Name</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($m['module_name'] ?? '—'); ?></dd>
                        <dt class="col-sm-4 text-muted">Semester</dt>
                        <dd class="col-sm-8"><?php
                            $sem = isset($m['semester']) ? $m['semester'] : null;
                            echo ($sem !== null && $sem !== '') ? 'Semester ' . (int)$sem : '—';
                        ?></dd>
                        <dt class="col-sm-4 text-muted">Credit</dt>
                        <dd class="col-sm-8"><?php echo isset($m['credit']) && $m['credit'] !== '' && $m['credit'] !== null ? htmlspecialchars($m['credit']) : '—'; ?></dd>
                    </dl>
                    <hr>
                    <div class="d-flex gap-2">
                        <a href="<?php echo APP_URL; ?>/modules" class="btn btn-outline-secondary"><i class="fas fa-list me-1"></i>Back to list</a>
                        <a href="<?php echo APP_URL; ?>/modules?course_id=<?php echo urlencode($m['course_id'] ?? ''); ?>" class="btn btn-outline-primary">Modules for this course</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
