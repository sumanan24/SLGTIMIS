<div class="container-fluid px-4 py-3">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-trash me-2"></i>Delete Staff Role</h5>
                </div>
                <div class="card-body">
                    <?php if ($isUsed): ?>
                        <div class="alert alert-warning d-flex align-items-center" role="alert">
                            <i class="fas fa-exclamation-triangle me-2 fa-lg"></i>
                            <div>
                                <strong>Warning!</strong> This role is currently assigned to staff members or users and cannot be deleted.
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning d-flex align-items-center" role="alert">
                            <i class="fas fa-exclamation-triangle me-2 fa-lg"></i>
                            <div>
                                <strong>Warning!</strong> Are you sure you want to delete this role? This action cannot be undone.
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <th class="bg-light" style="width: 200px;">Role ID:</th>
                                    <td><span class="fw-semibold text-primary"><?php echo htmlspecialchars($role['staff_position_type_id']); ?></span></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Role Name:</th>
                                    <td><?php echo htmlspecialchars($role['staff_position_type_name']); ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Position Level:</th>
                                    <td>
                                        <span class="badge bg-info bg-opacity-10 text-info">
                                            Level <?php echo htmlspecialchars($role['staff_position']); ?>
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if (!$isUsed): ?>
                        <form method="POST" action="<?php echo APP_URL; ?>/staff-roles/delete?id=<?php echo urlencode($role['staff_position_type_id']); ?>">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-trash me-1"></i>Yes, Delete Role
                                </button>
                                <a href="<?php echo APP_URL; ?>/staff-roles" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i>Cancel
                                </a>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="d-flex gap-2">
                            <a href="<?php echo APP_URL; ?>/staff-roles" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Back to Roles
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

