<link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/staff-roles.css?v=<?php echo time(); ?>">

<div class="container-fluid px-0 sr-page">
    <div class="card border-0">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="fw-bold">
                <i class="fas fa-user-tag me-2"></i>Staff Roles
            </h5>
            <div class="sr-head-actions">
                <?php if (!empty($roles)): ?>
                    <span class="sr-count"><?php echo count($roles); ?> role<?php echo count($roles) === 1 ? '' : 's'; ?></span>
                <?php endif; ?>
                <a href="<?php echo APP_URL; ?>/staff-roles/create" class="btn btn-light btn-sm">
                    <i class="fas fa-plus me-1"></i>Add Role
                </a>
            </div>
        </div>
        <div class="card-body">
            <?php if (!empty($message)): ?>
                <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($roles)): ?>
                <div class="sr-table-wrap">
                    <div class="table-responsive mb-0">
                        <table class="table table-hover align-middle sr-table">
                            <colgroup>
                                <col class="col-no">
                                <col class="col-id">
                                <col class="col-name">
                                <col class="col-level">
                                <col class="col-actions">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th class="col-no">No</th>
                                    <th class="col-id">Role ID</th>
                                    <th class="col-name">Role Name</th>
                                    <th class="col-level">Level</th>
                                    <th class="col-actions">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $rowNumber = 1; foreach ($roles as $role): ?>
                                    <tr>
                                        <td class="col-no"><?php echo $rowNumber++; ?></td>
                                        <td class="col-id">
                                            <span class="sr-id"><?php echo htmlspecialchars($role['staff_position_type_id']); ?></span>
                                        </td>
                                        <td class="col-name">
                                            <span class="sr-name" title="<?php echo htmlspecialchars($role['staff_position_type_name']); ?>">
                                                <?php echo htmlspecialchars($role['staff_position_type_name']); ?>
                                            </span>
                                        </td>
                                        <td class="col-level">
                                            <span class="sr-level"><?php echo htmlspecialchars($role['staff_position']); ?></span>
                                        </td>
                                        <td class="col-actions">
                                            <div class="sr-actions">
                                                <a href="<?php echo APP_URL; ?>/staff-roles/edit?id=<?php echo urlencode($role['staff_position_type_id']); ?>"
                                                   class="btn btn-outline-primary" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="<?php echo APP_URL; ?>/staff-roles/delete?id=<?php echo urlencode($role['staff_position_type_id']); ?>"
                                                   class="btn btn-outline-danger" title="Delete"
                                                   onclick="return confirm('Delete this role?');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="sr-empty">
                    <i class="fas fa-user-tag fa-2x"></i>
                    <p class="text-muted mb-3">No staff roles yet.</p>
                    <a href="<?php echo APP_URL; ?>/staff-roles/create" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Add Role
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
