<div class="container-fluid px-4 py-3">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-trash me-2"></i>Delete Group</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning d-flex align-items-center" role="alert">
                        <i class="fas fa-exclamation-triangle me-2 fa-lg"></i>
                        <div>
                            <strong>Warning!</strong> Are you sure you want to delete this group? This will also remove all students from the group. This action cannot be undone.
                        </div>
                    </div>
                    
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <th class="bg-light" style="width: 200px;">Group Name:</th>
                                    <td><span class="fw-semibold text-primary"><?php echo htmlspecialchars($group['name']); ?></span></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Course:</th>
                                    <td><?php echo htmlspecialchars($group['course_name'] ?? 'N/A'); ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Department:</th>
                                    <td>
                                        <span class="badge bg-info bg-opacity-10 text-info">
                                            <?php echo htmlspecialchars($group['department_name'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Academic Year:</th>
                                    <td><?php echo htmlspecialchars($group['academic_year'] ?? 'N/A'); ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Status:</th>
                                    <td>
                                        <span class="badge bg-<?php echo ($group['status'] === 'active') ? 'success' : 'secondary'; ?> rounded-pill px-3">
                                            <?php echo htmlspecialchars(ucfirst($group['status'] ?? 'active')); ?>
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <form method="POST" action="<?php echo APP_URL; ?>/groups/delete?id=<?php echo urlencode($group['id']); ?>">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash me-1"></i>Yes, Delete Group
                            </button>
                            <a href="<?php echo APP_URL; ?>/groups" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

