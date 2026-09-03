<style>
.academic-years-table { table-layout: fixed; width: 100%; margin-bottom: 0; }
.academic-years-table col.col-no { width: 56px; }
.academic-years-table col.col-year { width: 140px; }
.academic-years-table col.col-status { width: 120px; }
.academic-years-table col.col-actions { width: 110px; }
.academic-years-table thead th {
    padding: 0.75rem 1rem;
    font-size: 0.8125rem;
    letter-spacing: 0.02em;
    text-transform: uppercase;
    color: #495057;
    white-space: nowrap;
    vertical-align: middle;
    border-bottom: 2px solid #dee2e6;
}
.academic-years-table tbody td { padding: 0.75rem 1rem; vertical-align: middle; }
.academic-years-table th.col-no,
.academic-years-table td.col-no { text-align: center; color: #6c757d; }
.academic-years-table th.col-actions,
.academic-years-table td.col-actions { text-align: right; }
.academic-years-actions .btn {
    min-width: 38px;
    height: 32px;
    padding: 0.375rem 0.75rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.academic-years-page-wrap .table-responsive {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    overflow: hidden;
}
.academic-years-dates { font-size: 0.875rem; color: #334155; }
@media (max-width: 768px) {
    .academic-years-page-wrap.container-fluid { padding-left: 0 !important; padding-right: 0 !important; }
    .academic-years-page-wrap .card { border-radius: 0; }
    .academic-years-table { min-width: 720px; }
}
</style>

<div class="container-fluid px-4 py-3 academic-years-page-wrap">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="mb-0 fw-bold"><i class="fas fa-calendar-alt me-2"></i>Academic Years</h5>
                <?php if (!empty($isADM)): ?>
                <a href="<?php echo APP_URL; ?>/academic-years/create" class="btn btn-light btn-sm">
                    <i class="fas fa-plus me-1"></i>Add Academic Year
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

            <?php if (!empty($years)): ?>
                <div class="text-muted small mb-3">
                    Showing <strong><?php echo count($years); ?></strong> academic year(s)
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle academic-years-table">
                        <colgroup>
                            <col class="col-no">
                            <col class="col-year">
                            <col>
                            <col>
                            <col class="col-status">
                            <col class="col-actions">
                        </colgroup>
                        <thead class="table-light">
                            <tr>
                                <th class="col-no">No</th>
                                <th>Academic year</th>
                                <th>1st semester</th>
                                <th>2nd semester</th>
                                <th>Status</th>
                                <th class="col-actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $n = 1; foreach ($years as $row): ?>
                                <?php $isActive = (($row['academic_year_status'] ?? '') === 'Active'); ?>
                                <tr>
                                    <td class="col-no"><?php echo $n++; ?></td>
                                    <td><span class="fw-semibold text-primary"><?php echo htmlspecialchars($row['academic_year']); ?></span></td>
                                    <td class="academic-years-dates">
                                        <?php echo htmlspecialchars(AcademicYearModel::formatDate($row['first_semi_start_date'] ?? '')); ?>
                                        <span class="text-muted">–</span>
                                        <?php echo htmlspecialchars(AcademicYearModel::formatDate($row['first_semi_end_date'] ?? '')); ?>
                                    </td>
                                    <td class="academic-years-dates">
                                        <?php echo htmlspecialchars(AcademicYearModel::formatDate($row['second_semi_start_date'] ?? '')); ?>
                                        <span class="text-muted">–</span>
                                        <?php echo htmlspecialchars(AcademicYearModel::formatDate($row['second_semi_end_date'] ?? '')); ?>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill <?php echo $isActive ? 'bg-success' : 'bg-secondary'; ?>">
                                            <?php echo htmlspecialchars($row['academic_year_status'] ?? 'Active'); ?>
                                        </span>
                                    </td>
                                    <td class="col-actions academic-years-actions">
                                        <?php if (!empty($isADM)): ?>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="<?php echo APP_URL; ?>/academic-years/edit?id=<?php echo urlencode($row['academic_year']); ?>" class="btn btn-outline-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?php echo APP_URL; ?>/academic-years/delete?id=<?php echo urlencode($row['academic_year']); ?>" class="btn btn-outline-danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                        <?php else: ?>
                                        <span class="text-muted small">View only</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-calendar-alt fa-3x text-muted mb-3"></i>
                    <p class="text-muted mb-3">No academic years found.</p>
                    <?php if (!empty($isADM)): ?>
                    <a href="<?php echo APP_URL; ?>/academic-years/create" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Create one now
                    </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
