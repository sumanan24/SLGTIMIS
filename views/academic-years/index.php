<?php $yearCount = count($years ?? []); ?>
<style>
.academic-years-page-wrap {
    --ay-text: #0f172a;
    --ay-muted: #64748b;
    --ay-border: #e2e8f0;
    --ay-soft: #f8fafc;
}
.academic-years-page-wrap .ay-page-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.25rem;
}
.academic-years-page-wrap .ay-page-title {
    margin: 0 0 0.25rem;
    color: var(--ay-text);
    font-size: 1.35rem;
    font-weight: 700;
    line-height: 1.3;
}
.academic-years-page-wrap .ay-page-lead {
    margin: 0;
    color: var(--ay-muted);
    font-size: 0.875rem;
    line-height: 1.45;
}
.academic-years-page-wrap .ay-add-btn {
    min-height: 40px;
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    white-space: nowrap;
}
.academic-years-page-wrap .ay-panel {
    overflow: hidden;
    background: #fff;
    border: 1px solid var(--ay-border);
    border-radius: 0.65rem;
}
.academic-years-page-wrap .ay-panel-head {
    min-height: 52px;
    padding: 0.75rem 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    border-bottom: 1px solid var(--ay-border);
    background: var(--ay-soft);
}
.academic-years-page-wrap .ay-panel-title {
    margin: 0;
    color: var(--ay-text);
    font-size: 0.875rem;
    font-weight: 700;
}
.academic-years-page-wrap .ay-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 2rem;
    min-height: 1.65rem;
    padding: 0.15rem 0.55rem;
    color: #001f3f;
    background: #dbe7f3;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 700;
}
.academic-years-page-wrap .ay-alerts {
    padding: 1rem 1rem 0;
}
.academic-years-page-wrap .ay-alerts:empty { display: none; }
.academic-years-table {
    width: 100%;
    min-width: 780px;
    margin: 0;
    table-layout: fixed;
    border-collapse: separate;
    border-spacing: 0;
}
.academic-years-table col.col-no { width: 64px; }
.academic-years-table col.col-year { width: 150px; }
.academic-years-table col.col-semester { width: 235px; }
.academic-years-table col.col-status { width: 120px; }
.academic-years-table col.col-actions { width: 116px; }
.academic-years-table thead th {
    padding: 0.7rem 1rem;
    vertical-align: middle;
    color: #64748b;
    background: #fff;
    border: 0;
    border-bottom: 1px solid var(--ay-border);
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.045em;
    line-height: 1.3;
    text-transform: uppercase;
    white-space: nowrap;
}
.academic-years-table tbody td {
    height: 68px;
    padding: 0.75rem 1rem;
    vertical-align: middle;
    color: #334155;
    border: 0;
    border-bottom: 1px solid #eef2f6;
}
.academic-years-table tbody tr:last-child td { border-bottom: 0; }
.academic-years-table tbody tr:hover td { background: #f8fafc; }
.academic-years-table th.col-no,
.academic-years-table td.col-no {
    padding-left: 0.75rem;
    padding-right: 0.75rem;
    text-align: center;
    color: #94a3b8;
}
.academic-years-table th.col-year,
.academic-years-table td.col-year { text-align: left; }
.academic-years-table th.col-status,
.academic-years-table td.col-status { text-align: center; }
.academic-years-table th.col-actions,
.academic-years-table td.col-actions {
    padding-left: 0.5rem;
    padding-right: 1rem;
    text-align: right;
}
.academic-years-table .ay-year-code {
    color: #001f3f;
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-size: 0.875rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    white-space: nowrap;
}
.academic-years-table .ay-date-range {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #334155;
    font-size: 0.8125rem;
    white-space: nowrap;
}
.academic-years-table .ay-date-separator { color: #94a3b8; }
.academic-years-table .ay-status {
    min-width: 78px;
    padding: 0.4rem 0.65rem;
    font-size: 0.72rem;
}
.academic-years-actions .btn-group {
    display: inline-flex;
    gap: 0.35rem;
}
.academic-years-actions .btn-group > .btn + .btn { margin-left: 0; }
.academic-years-actions .btn {
    width: 34px;
    height: 34px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.375rem !important;
}
.academic-years-page-wrap .ay-empty {
    padding: 3rem 1.25rem;
    text-align: center;
}
@media (max-width: 767.98px) {
    .academic-years-page-wrap.container-fluid {
        padding-left: 0.75rem !important;
        padding-right: 0.75rem !important;
    }
    .academic-years-page-wrap .ay-page-head {
        flex-direction: column;
        align-items: stretch;
    }
    .academic-years-page-wrap .ay-add-btn { width: 100%; }
    .academic-years-page-wrap .ay-panel-head { padding: 0.7rem 0.8rem; }
    .academic-years-page-wrap .table-responsive {
        -webkit-overflow-scrolling: touch;
    }
}
</style>

<div class="container-fluid px-4 py-3 academic-years-page-wrap">
    <div class="ay-page-head">
        <div>
            <h1 class="ay-page-title"><i class="fas fa-calendar-alt text-primary me-2"></i>Academic years</h1>
            <p class="ay-page-lead">Manage academic periods, semester dates, and the currently active year.</p>
        </div>
        <?php if (!empty($isADM)): ?>
            <a href="<?php echo APP_URL; ?>/academic-years/create" class="btn btn-primary ay-add-btn">
                <i class="fas fa-plus me-1"></i>Add academic year
            </a>
        <?php endif; ?>
    </div>

    <div class="ay-panel">
        <div class="ay-panel-head">
            <h2 class="ay-panel-title">Academic year records</h2>
            <span class="ay-count"><?php echo number_format($yearCount); ?></span>
        </div>
        <?php if (isset($message) || isset($error)): ?>
        <div class="ay-alerts">
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
        </div>
        <?php endif; ?>

        <?php if (!empty($years)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle academic-years-table">
                        <colgroup>
                            <col class="col-no">
                            <col class="col-year">
                            <col class="col-semester">
                            <col class="col-semester">
                            <col class="col-status">
                            <col class="col-actions">
                        </colgroup>
                        <thead>
                            <tr>
                                <th class="col-no">No</th>
                                <th class="col-year">Academic year</th>
                                <th>1st semester</th>
                                <th>2nd semester</th>
                                <th class="col-status">Status</th>
                                <th class="col-actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $n = 1; foreach ($years as $row): ?>
                                <?php $isActive = (($row['academic_year_status'] ?? '') === 'Active'); ?>
                                <tr>
                                    <td class="col-no"><?php echo $n++; ?></td>
                                    <td class="col-year"><span class="ay-year-code"><?php echo htmlspecialchars($row['academic_year']); ?></span></td>
                                    <td>
                                        <span class="ay-date-range">
                                            <span><?php echo htmlspecialchars(AcademicYearModel::formatDate($row['first_semi_start_date'] ?? '')); ?></span>
                                            <span class="ay-date-separator">–</span>
                                            <span><?php echo htmlspecialchars(AcademicYearModel::formatDate($row['first_semi_end_date'] ?? '')); ?></span>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="ay-date-range">
                                            <span><?php echo htmlspecialchars(AcademicYearModel::formatDate($row['second_semi_start_date'] ?? '')); ?></span>
                                            <span class="ay-date-separator">–</span>
                                            <span><?php echo htmlspecialchars(AcademicYearModel::formatDate($row['second_semi_end_date'] ?? '')); ?></span>
                                        </span>
                                    </td>
                                    <td class="col-status">
                                        <span class="badge rounded-pill ay-status <?php echo $isActive ? 'bg-success' : 'bg-secondary'; ?>">
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
                <div class="ay-empty">
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
