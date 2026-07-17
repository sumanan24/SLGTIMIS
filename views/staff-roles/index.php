<style>
/* Column plan: No | Role ID | Role Name | Level | Actions */
.staff-roles-table {
    table-layout: fixed;
    width: 100%;
    margin-bottom: 0;
    border-collapse: separate;
    border-spacing: 0;
}

.staff-roles-table col.col-no { width: 4%; }
.staff-roles-table col.col-id { width: 12%; }
.staff-roles-table col.col-name { width: 44%; }
.staff-roles-table col.col-level { width: 14%; }
.staff-roles-table col.col-actions { width: 11%; }

.staff-roles-table thead th {
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

.staff-roles-table tbody td {
    padding: 0.625rem 0.75rem;
    vertical-align: middle;
    line-height: 1.4;
    border-bottom: 1px solid #eef1f4;
}

.staff-roles-table tbody tr:last-child td {
    border-bottom: none;
}

.staff-roles-table tbody tr:hover td {
    background-color: rgba(13, 110, 253, 0.04);
}

.staff-roles-table th.col-no,
.staff-roles-table td.col-no {
    text-align: center;
    color: #6c757d;
    padding-left: 0.5rem;
    padding-right: 0.5rem;
}

.staff-roles-table th.col-id,
.staff-roles-table td.col-id,
.staff-roles-table th.col-name,
.staff-roles-table td.col-name {
    text-align: left;
}

.staff-roles-table td.col-name .role-name-text {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-weight: 500;
    color: #212529;
}

.staff-roles-table th.col-level,
.staff-roles-table td.col-level {
    text-align: center;
    padding-left: 0.5rem;
    padding-right: 0.5rem;
}

.staff-roles-table th.col-actions,
.staff-roles-table td.col-actions {
    text-align: right;
    padding-right: 0.75rem;
    white-space: nowrap;
}

.staff-roles-table .staff-roles-chip {
    display: inline-block;
    font-size: 0.6875rem;
    font-weight: 600;
    line-height: 1.2;
    padding: 0.35rem 0.55rem;
    border-radius: 999px;
    white-space: nowrap;
    background-color: rgba(13, 202, 240, 0.18);
    color: #087990;
}

.staff-roles-page-wrap .table-responsive {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    overflow: hidden;
    background: #fff;
}

.staff-roles-actions .btn {
    min-width: 34px;
    width: 34px;
    height: 32px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8125rem;
}

.staff-roles-actions .btn-group {
    display: inline-flex;
    justify-content: flex-end;
    gap: 0.25rem;
}

.staff-roles-actions .btn-group > .btn + .btn {
    margin-left: 0;
}

.staff-roles-page-wrap .staff-roles-header-btn {
    min-height: 38px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.staff-roles-summary-bar {
    padding: 0.625rem 0.875rem;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 0.375rem;
}

@media (max-width: 768px) {
    .staff-roles-page-wrap.container-fluid {
        padding-left: 0 !important;
        padding-right: 0 !important;
        padding-top: 0.5rem;
        padding-bottom: 0.5rem;
    }
    .staff-roles-page-wrap .card {
        border-radius: 0;
    }
    .staff-roles-page-wrap .card-header {
        padding: 0.75rem 0.5rem;
    }
    .staff-roles-page-wrap .card-header .d-flex {
        flex-direction: column;
        align-items: stretch !important;
        gap: 0.5rem;
    }
    .staff-roles-page-wrap .staff-roles-header-btn {
        width: 100%;
    }
    .staff-roles-page-wrap .card-body {
        padding: 0.75rem 0.5rem !important;
    }
    .staff-roles-page-wrap .table-responsive {
        margin-left: -0.5rem;
        margin-right: -0.5rem;
        border-radius: 0;
        border-left: none;
        border-right: none;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .staff-roles-table {
        min-width: 640px;
    }
    .staff-roles-table thead th,
    .staff-roles-table tbody td {
        padding: 0.5rem 0.625rem;
    }
}
</style>

<div class="container-fluid px-4 py-3 staff-roles-page-wrap">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="mb-0 fw-bold"><i class="fas fa-user-tag me-2"></i>Staff Roles Management</h5>
                <a href="<?php echo APP_URL; ?>/staff-roles/create" class="btn btn-light btn-sm staff-roles-header-btn mt-2 mt-md-0">
                    <i class="fas fa-plus me-1"></i>Add New Role
                </a>
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
            
            <?php if (!empty($roles)): ?>
                <div class="staff-roles-summary-bar mb-3">
                    <div class="text-muted small mb-0">
                        <i class="fas fa-info-circle me-1"></i>
                        Showing <strong><?php echo count($roles); ?></strong> role(s)
                    </div>
                </div>

                <div class="table-responsive mb-0">
                    <table class="table table-hover align-middle staff-roles-table mb-0">
                        <colgroup>
                            <col class="col-no">
                            <col class="col-id">
                            <col class="col-name">
                            <col class="col-level">
                            <col class="col-actions">
                        </colgroup>
                        <thead class="table-light">
                            <tr>
                                <th scope="col" class="fw-semibold col-no">No</th>
                                <th scope="col" class="fw-semibold col-id">Role ID</th>
                                <th scope="col" class="fw-semibold col-name">Role Name</th>
                                <th scope="col" class="fw-semibold col-level">Position Level</th>
                                <th scope="col" class="fw-semibold col-actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $rowNumber = 1; foreach ($roles as $role): ?>
                                <tr>
                                    <td class="col-no"><?php echo $rowNumber++; ?></td>
                                    <td class="col-id">
                                        <code class="text-primary fw-semibold"><?php echo htmlspecialchars($role['staff_position_type_id']); ?></code>
                                    </td>
                                    <td class="col-name">
                                        <span class="role-name-text" title="<?php echo htmlspecialchars($role['staff_position_type_name']); ?>">
                                            <?php echo htmlspecialchars($role['staff_position_type_name']); ?>
                                        </span>
                                    </td>
                                    <td class="col-level">
                                        <span class="staff-roles-chip">
                                            Level <?php echo htmlspecialchars($role['staff_position']); ?>
                                        </span>
                                    </td>
                                    <td class="col-actions staff-roles-actions">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="<?php echo APP_URL; ?>/staff-roles/edit?id=<?php echo urlencode($role['staff_position_type_id']); ?>" class="btn btn-outline-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?php echo APP_URL; ?>/staff-roles/delete?id=<?php echo urlencode($role['staff_position_type_id']); ?>" class="btn btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this role?');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-user-tag fa-3x text-muted mb-3"></i>
                    <p class="text-muted mb-3">No staff roles found.</p>
                    <a href="<?php echo APP_URL; ?>/staff-roles/create" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Create one now
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
