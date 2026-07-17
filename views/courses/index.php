<style>
/* Column plan (%): No 4 | ID 9 | Name 30 | Dept 18 | NVQ 10 | Status 12 | Actions 11 */
.courses-table {
    table-layout: fixed;
    width: 100%;
    margin-bottom: 0;
    border-collapse: separate;
    border-spacing: 0;
}

.courses-table col.col-no { width: 4%; }
.courses-table col.col-id { width: 9%; }
.courses-table col.col-name { width: 30%; }
.courses-table col.col-dept { width: 18%; }
.courses-table col.col-nvq { width: 10%; }
.courses-table col.col-status { width: 12%; }
.courses-table col.col-actions { width: 11%; }

.courses-table thead th {
    padding: 0.625rem 0.75rem;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #495057;
    white-space: nowrap;
    vertical-align: middle;
    border-bottom: 2px solid #dee2e6;
    background-color: #f8f9fa;
}

.courses-table tbody td {
    padding: 0.625rem 0.75rem;
    vertical-align: middle;
    line-height: 1.4;
    border-bottom: 1px solid #eef1f4;
}

.courses-table tbody tr:last-child td {
    border-bottom: none;
}

.courses-table tbody tr:hover td {
    background-color: rgba(13, 110, 253, 0.04);
}

.courses-table th.col-no,
.courses-table td.col-no {
    text-align: center;
    color: #6c757d;
    padding-left: 0.5rem;
    padding-right: 0.5rem;
    width: 48px;
}

.courses-table th.col-id,
.courses-table td.col-id {
    text-align: left;
    white-space: nowrap;
}

.courses-table th.col-name,
.courses-table td.col-name {
    text-align: left;
}

.courses-table td.col-name .course-name-text {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-weight: 500;
    color: #212529;
}

.courses-table th.col-dept,
.courses-table td.col-dept {
    text-align: left;
}

.courses-table td.col-dept .dept-text {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: #495057;
}

.courses-table th.col-nvq,
.courses-table td.col-nvq,
.courses-table th.col-status,
.courses-table td.col-status {
    text-align: center;
    padding-left: 0.5rem;
    padding-right: 0.5rem;
}

.courses-table th.col-actions,
.courses-table td.col-actions {
    text-align: right;
    padding-right: 0.75rem;
    white-space: nowrap;
}

.courses-table .courses-chip {
    display: inline-block;
    font-size: 0.6875rem;
    font-weight: 600;
    line-height: 1.2;
    padding: 0.35rem 0.55rem;
    border-radius: 999px;
    white-space: nowrap;
    vertical-align: middle;
}

.courses-table .courses-chip-nvq {
    background-color: rgba(13, 110, 253, 0.12);
    color: #0d6efd;
}

.courses-table .courses-chip-active {
    background-color: rgba(25, 135, 84, 0.15);
    color: #198754;
}

.courses-table .courses-chip-draft {
    background-color: rgba(255, 193, 7, 0.2);
    color: #856404;
}

.courses-table .courses-chip-deactivated {
    background-color: rgba(108, 117, 125, 0.18);
    color: #495057;
}

.courses-page-wrap .table-responsive {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    overflow: hidden;
    background: #fff;
}

.courses-actions .btn {
    min-width: 34px;
    width: 34px;
    height: 32px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8125rem;
}

.courses-actions .btn-group {
    display: inline-flex;
    justify-content: flex-end;
    gap: 0.25rem;
}

.courses-actions .btn-group > .btn + .btn {
    margin-left: 0;
}

.courses-page-wrap .courses-filter-form .form-control-sm,
.courses-page-wrap .courses-filter-form .form-select-sm {
    min-height: 38px;
}

.courses-filter-actions {
    display: flex;
    gap: 0.5rem;
    align-items: stretch;
}

.courses-filter-actions .btn {
    min-height: 38px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    white-space: nowrap;
}

.courses-filter-actions .courses-filter-btn {
    min-width: 38px;
    width: 38px;
    padding: 0;
    flex-shrink: 0;
}

.courses-header-btn {
    min-height: 38px;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    white-space: nowrap;
}

.courses-clear-btn {
    min-height: 32px;
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    white-space: nowrap;
}

.courses-summary-bar {
    padding: 0.625rem 0.875rem;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 0.375rem;
}

@media (max-width: 768px) {
    .courses-page-wrap.container-fluid {
        padding-left: 0 !important;
        padding-right: 0 !important;
        padding-top: 0.5rem;
        padding-bottom: 0.5rem;
    }
    .courses-page-wrap > .card {
        border-radius: 0;
    }
    .courses-page-wrap .card-body {
        padding: 0.75rem 0.5rem !important;
    }
    .courses-page-wrap .card.border.mb-4 .card-body {
        padding: 0.625rem 0.5rem !important;
    }
    .courses-page-wrap .table-responsive {
        margin-left: -0.5rem;
        margin-right: -0.5rem;
        border-radius: 0;
        border-left: none;
        border-right: none;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .courses-table {
        min-width: 900px;
    }
    .courses-table thead th,
    .courses-table tbody td {
        padding: 0.5rem 0.625rem;
    }
    .courses-page-wrap .card-header {
        padding: 0.75rem 0.5rem;
    }
    .courses-page-wrap .card-header .d-flex {
        flex-direction: column;
        align-items: stretch !important;
        gap: 0.5rem;
    }
    .courses-page-wrap .courses-header-btn {
        width: 100%;
    }
    .courses-filter-actions {
        width: 100%;
    }
    .courses-filter-actions .btn-primary {
        flex: 1;
    }
}
@media (max-width: 576px) {
    .courses-page-wrap .card-body {
        padding: 0.5rem 0.375rem !important;
    }
    .courses-page-wrap .card.border.mb-4 .card-body {
        padding: 0.5rem 0.375rem !important;
    }
    .courses-page-wrap .card-header {
        padding: 0.625rem 0.375rem;
    }
}
</style>

<div class="container-fluid px-4 py-3 courses-page-wrap">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="mb-0 fw-bold"><i class="fas fa-book me-2"></i>Courses Management</h5>
                <?php if (isset($canCreate) && $canCreate): ?>
                <a href="<?php echo APP_URL; ?>/courses/create" class="btn btn-light courses-header-btn mt-2 mt-md-0">
                    <i class="fas fa-plus me-1"></i>Add New Course
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

            <div class="card border mb-4 shadow-sm">
                <div class="card-body">
                    <h6 class="card-title mb-3 fw-bold text-primary">
                        <i class="fas fa-filter me-2"></i>Filter Courses
                    </h6>
                    <form method="GET" action="<?php echo APP_URL; ?>/courses" class="courses-filter-form">
                        <div class="row g-3 align-items-end">
                            <div class="col-12 col-md-6 col-lg-3">
                                <label for="search" class="form-label small fw-semibold text-muted mb-1">Search</label>
                                <input type="text" class="form-control form-control-sm" id="search" name="search"
                                       value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>"
                                       placeholder="Course ID or Name">
                            </div>

                            <div class="col-12 col-md-6 col-lg-3">
                                <label for="department_id" class="form-label small fw-semibold text-muted mb-1">Department</label>
                                <select class="form-select form-select-sm" id="department_id" name="department_id">
                                    <option value="">All Departments</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo htmlspecialchars($dept['department_id']); ?>"
                                                <?php echo (isset($filters['department_id']) && $filters['department_id'] === $dept['department_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($dept['department_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12 col-md-6 col-lg-2">
                                <label for="nvq_level" class="form-label small fw-semibold text-muted mb-1">NVQ Level</label>
                                <select class="form-select form-select-sm" id="nvq_level" name="nvq_level">
                                    <option value="">All Levels</option>
                                    <option value="3" <?php echo (isset($filters['nvq_level']) && $filters['nvq_level'] === '3') ? 'selected' : ''; ?>>Level 3</option>
                                    <option value="4" <?php echo (isset($filters['nvq_level']) && $filters['nvq_level'] === '4') ? 'selected' : ''; ?>>Level 4</option>
                                    <option value="5" <?php echo (isset($filters['nvq_level']) && $filters['nvq_level'] === '5') ? 'selected' : ''; ?>>Level 5</option>
                                    <option value="6" <?php echo (isset($filters['nvq_level']) && $filters['nvq_level'] === '6') ? 'selected' : ''; ?>>Level 6</option>
                                    <option value="BRI" <?php echo (isset($filters['nvq_level']) && $filters['nvq_level'] === 'BRI') ? 'selected' : ''; ?>>BRI</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-6 col-lg-2">
                                <label for="course_status" class="form-label small fw-semibold text-muted mb-1">Status</label>
                                <select class="form-select form-select-sm" id="course_status" name="course_status">
                                    <option value="">All Statuses</option>
                                    <option value="active" <?php echo (isset($filters['course_status']) && $filters['course_status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="draft" <?php echo (isset($filters['course_status']) && $filters['course_status'] === 'draft') ? 'selected' : ''; ?>>Draft</option>
                                    <option value="deactivated" <?php echo (isset($filters['course_status']) && $filters['course_status'] === 'deactivated') ? 'selected' : ''; ?>>Deactivated</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-6 col-lg-2">
                                <label class="form-label small fw-semibold text-muted mb-1 d-none d-md-block invisible">Apply</label>
                                <div class="courses-filter-actions">
                                    <button type="submit" class="btn btn-primary btn-sm flex-fill">
                                        <i class="fas fa-filter me-1"></i>Filter
                                    </button>
                                    <a href="<?php echo APP_URL; ?>/courses" class="btn btn-outline-secondary btn-sm courses-filter-btn" title="Clear filters">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <?php if (!empty($courses)): ?>
                <?php
                $currentPage = (int) ($currentPage ?? 1);
                $totalPages = (int) ($totalPages ?? 1);
                $perPage = (int) ($perPage ?? 20);
                $paginationQuery = http_build_query(array_filter([
                    'search' => $filters['search'] ?? '',
                    'department_id' => $filters['department_id'] ?? '',
                    'nvq_level' => $filters['nvq_level'] ?? '',
                    'course_status' => $filters['course_status'] ?? '',
                ], static fn($value) => $value !== ''));
                $paginationPrefix = $paginationQuery ? '?' . $paginationQuery . '&' : '?';
                ?>
                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2 courses-summary-bar">
                    <div class="text-muted small mb-0">
                        <i class="fas fa-info-circle me-1"></i>
                        Showing <strong><?php echo count($courses); ?></strong> of <strong><?php echo number_format($total ?? count($courses)); ?></strong> course(s)
                    </div>
                    <?php if (!empty($filters['search']) || !empty($filters['department_id']) || !empty($filters['nvq_level']) || !empty($filters['course_status'])): ?>
                        <a href="<?php echo APP_URL; ?>/courses" class="btn btn-outline-danger btn-sm courses-clear-btn">
                            <i class="fas fa-times-circle me-1"></i>Clear Filters
                        </a>
                    <?php endif; ?>
                </div>

                <div class="table-responsive mb-0">
                    <table class="table table-hover align-middle courses-table mb-0">
                        <colgroup>
                            <col class="col-no">
                            <col class="col-id">
                            <col class="col-name">
                            <col class="col-dept">
                            <col class="col-nvq">
                            <col class="col-status">
                            <col class="col-actions">
                        </colgroup>
                        <thead class="table-light">
                            <tr>
                                <th scope="col" class="fw-semibold col-no">No</th>
                                <th scope="col" class="fw-semibold col-id">Course ID</th>
                                <th scope="col" class="fw-semibold col-name">Course Name</th>
                                <th scope="col" class="fw-semibold col-dept">Department</th>
                                <th scope="col" class="fw-semibold col-nvq">NVQ Level</th>
                                <th scope="col" class="fw-semibold col-status">Status</th>
                                <th scope="col" class="fw-semibold col-actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $rowNumber = (($currentPage - 1) * $perPage) + 1;
                            foreach ($courses as $course):
                            ?>
                                <tr>
                                    <td class="col-no"><?php echo $rowNumber++; ?></td>
                                    <td class="col-id">
                                        <code class="text-primary fw-semibold"><?php echo htmlspecialchars($course['course_id'] ?? ''); ?></code>
                                    </td>
                                    <td class="col-name">
                                        <span class="course-name-text" title="<?php echo htmlspecialchars($course['course_name']); ?>">
                                            <?php echo htmlspecialchars($course['course_name']); ?>
                                        </span>
                                    </td>
                                    <td class="col-dept">
                                        <span class="dept-text" title="<?php echo htmlspecialchars($course['department_name'] ?? '—'); ?>">
                                            <?php echo htmlspecialchars($course['department_name'] ?? '—'); ?>
                                        </span>
                                    </td>
                                    <td class="col-nvq">
                                        <span class="courses-chip courses-chip-nvq">
                                            Lv <?php echo htmlspecialchars($course['course_nvq_level']); ?>
                                        </span>
                                    </td>
                                    <?php
                                    $courseStatus = $course['course_status'] ?? 'active';
                                    $chipClass = $courseStatus === 'active'
                                        ? 'courses-chip-active'
                                        : ($courseStatus === 'draft' ? 'courses-chip-draft' : 'courses-chip-deactivated');
                                    $statusLabels = ['active' => 'Active', 'draft' => 'Draft', 'deactivated' => 'Inactive'];
                                    ?>
                                    <td class="col-status">
                                        <span class="courses-chip <?php echo $chipClass; ?>">
                                            <?php echo htmlspecialchars($statusLabels[$courseStatus] ?? ucfirst($courseStatus)); ?>
                                        </span>
                                    </td>
                                    <td class="col-actions courses-actions">
                                        <?php if (isset($canEdit) && $canEdit): ?>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="<?php echo APP_URL; ?>/courses/edit?id=<?php echo urlencode($course['course_id']); ?>"
                                               class="btn btn-outline-primary"
                                               title="Edit Course">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?php echo APP_URL; ?>/courses/delete?id=<?php echo urlencode($course['course_id']); ?>"
                                               class="btn btn-outline-danger"
                                               onclick="return confirm('Are you sure you want to delete this course?');"
                                               title="Delete Course">
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

                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Courses pagination" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($currentPage > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo $paginationPrefix; ?>page=<?php echo $currentPage - 1; ?>">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?php echo $paginationPrefix; ?>page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($currentPage < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo $paginationPrefix; ?>page=<?php echo $currentPage + 1; ?>">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                    <p class="text-muted mb-3">No courses found.</p>
                    <?php if (isset($canCreate) && $canCreate): ?>
                    <a href="<?php echo APP_URL; ?>/courses/create" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Create one now
                    </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
