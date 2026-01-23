<div class="container-fluid px-4 py-3">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white">
            <div class="d-flex align-items-center justify-content-between flex-wrap">
                <h5 class="mb-0 fw-bold"><i class="fas fa-user-tag me-2"></i>Staff Roles Management</h5>
                <a href="<?php echo APP_URL; ?>/staff-roles/create" class="btn btn-light btn-sm mt-2 mt-md-0">
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
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="fw-bold">Role ID</th>
                                <th class="fw-bold">Role Name</th>
                                <th class="fw-bold">Position Level</th>
                                <th class="fw-bold text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($roles as $role): ?>
                                <tr>
                                    <td><span class="fw-semibold text-primary"><?php echo htmlspecialchars($role['staff_position_type_id']); ?></span></td>
                                    <td><?php echo htmlspecialchars($role['staff_position_type_name']); ?></td>
                                    <td>
                                        <span class="badge bg-info bg-opacity-10 text-info">
                                            Level <?php echo htmlspecialchars($role['staff_position']); ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="<?php echo APP_URL; ?>/staff-roles/edit?id=<?php echo urlencode($role['staff_position_type_id']); ?>" class="btn btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?php echo APP_URL; ?>/staff-roles/delete?id=<?php echo urlencode($role['staff_position_type_id']); ?>" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to delete this role?');">
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

