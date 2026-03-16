<style>
@media (max-width: 768px) {
    .modules-page-wrap.container-fluid {
        padding-left: 0 !important;
        padding-right: 0 !important;
        padding-top: 0.5rem;
        padding-bottom: 0.5rem;
    }
    .modules-page-wrap .card { border-radius: 0; }
    .modules-page-wrap .card-header .d-flex { flex-direction: column; align-items: stretch !important; gap: 0.5rem; }
    .modules-page-wrap .card-body { padding: 0.75rem 0.5rem !important; }
    .modules-page-wrap .table-responsive { margin-left: -0.5rem; margin-right: -0.5rem; padding-left: 0.5rem; padding-right: 0.5rem; overflow-x: auto; }
}
</style>
<div class="container-fluid px-4 py-3 modules-page-wrap">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white">
            <div class="d-flex align-items-center justify-content-between flex-wrap">
                <h5 class="mb-0 fw-bold"><i class="fas fa-cubes me-2"></i>Modules</h5>
                <?php if (isset($canCreate) && $canCreate): ?>
                <a href="<?php echo APP_URL; ?>/modules/create" class="btn btn-light btn-sm mt-2 mt-md-0">
                    <i class="fas fa-plus me-1"></i>Add Module
                </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <?php if (isset($message)): ?>
                <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <div><?php echo htmlspecialchars($message); ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <div><?php echo htmlspecialchars($error); ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <form method="GET" action="<?php echo APP_URL; ?>/modules" class="mb-3">
                <label for="course_id" class="form-label small fw-bold text-muted">Filter by course</label>
                <div class="d-flex gap-2 flex-wrap">
                    <select name="course_id" id="course_id" class="form-select form-select-sm" style="max-width: 280px;">
                        <option value="">All courses</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?php echo htmlspecialchars($c['course_id']); ?>" <?php echo (isset($filter_course_id) && $filter_course_id === $c['course_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(($c['course_name'] ?? '') . ' (' . $c['course_id'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    <a href="<?php echo APP_URL; ?>/modules" class="btn btn-outline-secondary btn-sm">Clear</a>
                </div>
            </form>
            <?php if (!empty($modules)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="fw-bold">Course</th>
                                <th class="fw-bold">Version</th>
                                <th class="fw-bold">Module ID</th>
                                <th class="fw-bold">Module Name</th>
                                <th class="fw-bold">Credit</th>
                                <th class="fw-bold text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($modules as $m): 
                                $ver = isset($m['course_version']) ? (int)$m['course_version'] : 0;
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($m['course_name'] ?? $m['course_id']); ?> <span class="text-muted small">(<?php echo htmlspecialchars($m['course_id']); ?>)</span></td>
                                    <td><span class="badge bg-secondary"><?php echo $ver === 0 ? 'Default' : $ver; ?></span></td>
                                    <td><span class="fw-semibold text-primary"><?php echo htmlspecialchars($m['module_id']); ?></span></td>
                                    <td><?php echo htmlspecialchars($m['module_name'] ?? '—'); ?></td>
                                    <td><?php echo isset($m['credit']) && $m['credit'] !== '' && $m['credit'] !== null ? htmlspecialchars($m['credit']) : '—'; ?></td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="<?php echo APP_URL; ?>/modules/view?course_id=<?php echo urlencode($m['course_id']); ?>&module_id=<?php echo urlencode($m['module_id']); ?>&course_version=<?php echo $ver; ?>" class="btn btn-outline-primary" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if (isset($canEdit) && $canEdit): ?>
                                            <a href="<?php echo APP_URL; ?>/modules/edit?course_id=<?php echo urlencode($m['course_id']); ?>&module_id=<?php echo urlencode($m['module_id']); ?>&course_version=<?php echo $ver; ?>" class="btn btn-outline-secondary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?php echo APP_URL; ?>/modules/delete?course_id=<?php echo urlencode($m['course_id']); ?>&module_id=<?php echo urlencode($m['module_id']); ?>&course_version=<?php echo $ver; ?>" class="btn btn-outline-danger" title="Delete" onclick="return confirm('Delete this module?');">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-cubes fa-3x text-muted mb-3"></i>
                    <p class="text-muted mb-3">No modules found.</p>
                    <?php if (isset($canCreate) && $canCreate): ?>
                    <a href="<?php echo APP_URL; ?>/modules/create" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Add Module
                    </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
