<style>
/* Column plan: No (56px) | Dept ID (160px) | Name (flex) | Actions (110px) */
.departments-table {
    table-layout: fixed;
    width: 100%;
    margin-bottom: 0;
}

.departments-table col.col-no { width: 56px; }
.departments-table col.col-id { width: 160px; }
.departments-table col.col-actions { width: 110px; }

.departments-table thead th {
    padding: 0.75rem 1rem;
    font-size: 0.8125rem;
    letter-spacing: 0.02em;
    text-transform: uppercase;
    color: #495057;
    white-space: nowrap;
    vertical-align: middle;
    border-bottom: 2px solid #dee2e6;
}

.departments-table tbody td {
    padding: 0.75rem 1rem;
    vertical-align: middle;
}

.departments-table th.col-no,
.departments-table td.col-no {
    text-align: center;
    color: #6c757d;
}

.departments-table th.col-id,
.departments-table td.col-id {
    text-align: left;
}

.departments-table th.col-name,
.departments-table td.col-name {
    text-align: left;
    overflow: hidden;
    text-overflow: ellipsis;
}

.departments-table th.col-actions,
.departments-table td.col-actions {
    text-align: right;
}

.departments-actions .btn {
    min-width: 38px;
    height: 32px;
    padding: 0.375rem 0.75rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
}

.departments-actions .btn-group {
    display: inline-flex;
    justify-content: flex-end;
}

.departments-actions .btn-group > .btn:not(:first-child) {
    margin-left: 0.25rem;
}

.departments-page-wrap .table-responsive {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    overflow: hidden;
}

/* Departments index - mobile full-width, no side space */
@media (max-width: 768px) {
    .departments-page-wrap.container-fluid {
        padding-left: 0 !important;
        padding-right: 0 !important;
        padding-top: 0.5rem;
        padding-bottom: 0.5rem;
    }
    .departments-page-wrap .card {
        border-radius: 0;
    }
    .departments-page-wrap .card-header {
        padding: 0.75rem 0.5rem;
    }
    .departments-page-wrap .card-header .d-flex {
        flex-direction: column;
        align-items: stretch !important;
        gap: 0.5rem;
    }
    .departments-page-wrap .card-header h5 {
        font-size: 1rem;
    }
    .departments-page-wrap .card-body {
        padding: 0.75rem 0.5rem !important;
    }
    .departments-page-wrap .btn.btn-light.btn-sm {
        width: 100%;
        justify-content: center;
    }
    .departments-page-wrap .table-responsive {
        margin-left: -0.5rem;
        margin-right: -0.5rem;
        padding-left: 0;
        padding-right: 0;
        border-radius: 0;
        border-left: none;
        border-right: none;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .departments-table {
        min-width: 520px;
    }
    .departments-table thead th,
    .departments-table tbody td {
        padding: 0.5rem 0.625rem;
        white-space: nowrap;
    }
    .departments-table th.col-name,
    .departments-table td.col-name {
        white-space: normal;
        min-width: 140px;
    }
}
@media (max-width: 576px) {
    .departments-page-wrap.container-fluid {
        padding-top: 0.5rem;
        padding-bottom: 0.5rem;
    }
    .departments-page-wrap .card-body {
        padding: 0.5rem 0.375rem !important;
    }
    .departments-page-wrap .card-header {
        padding: 0.625rem 0.375rem;
    }
}
</style>

<div class="container-fluid px-4 py-3 departments-page-wrap">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="mb-0 fw-bold"><i class="fas fa-building me-2"></i>Departments</h5>
                <?php if (isset($isADM) && $isADM): ?>
                <a href="<?php echo APP_URL; ?>/departments/create" class="btn btn-light btn-sm mt-2 mt-md-0">
                    <i class="fas fa-plus me-1"></i>Add New Department
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
            
            <?php if (!empty($departments)): ?>
                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                    <div class="text-muted small">
                        <i class="fas fa-info-circle me-1"></i>
                        Showing <strong><?php echo count($departments); ?></strong> of <strong><?php echo number_format($total ?? count($departments)); ?></strong> department(s)
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle departments-table">
                        <colgroup>
                            <col class="col-no">
                            <col class="col-id">
                            <col class="col-name">
                            <col class="col-actions">
                        </colgroup>
                        <thead class="table-light">
                            <tr>
                                <th scope="col" class="fw-semibold col-no">No</th>
                                <th scope="col" class="fw-semibold col-id">Department ID</th>
                                <th scope="col" class="fw-semibold col-name">Department Name</th>
                                <th scope="col" class="fw-semibold col-actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $perPage = (int) ($perPage ?? 20);
                            $currentPage = (int) ($currentPage ?? 1);
                            $rowNumber = (($currentPage - 1) * $perPage) + 1;
                            foreach ($departments as $dept):
                            ?>
                                <tr>
                                    <td class="col-no"><?php echo $rowNumber++; ?></td>
                                    <td class="col-id"><span class="fw-semibold text-primary"><?php echo htmlspecialchars($dept['department_id']); ?></span></td>
                                    <td class="col-name"><?php echo htmlspecialchars($dept['department_name']); ?></td>
                                    <td class="col-actions departments-actions">
                                        <?php if (isset($isADM) && $isADM): ?>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="<?php echo APP_URL; ?>/departments/edit?id=<?php echo urlencode($dept['department_id']); ?>" class="btn btn-outline-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?php echo APP_URL; ?>/departments/delete?id=<?php echo urlencode($dept['department_id']); ?>" class="btn btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this department?');">
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

                <?php if (($totalPages ?? 1) > 1): ?>
                    <nav aria-label="Departments pagination" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($currentPage > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $currentPage - 1; ?>">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i == $currentPage ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($currentPage < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $currentPage + 1; ?>">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-building fa-3x text-muted mb-3"></i>
                    <p class="text-muted mb-3">No departments found.</p>
                    <?php if (isset($isADM) && $isADM): ?>
                    <a href="<?php echo APP_URL; ?>/departments/create" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Create one now
                    </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
