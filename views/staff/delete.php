<div class="container-fluid px-4 py-3">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-trash me-2"></i>Delete Staff</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning d-flex align-items-center" role="alert">
                        <i class="fas fa-exclamation-triangle me-2 fa-lg"></i>
                        <div>
                            <strong>Warning!</strong> Are you sure you want to delete this staff member? This action cannot be undone.
                        </div>
                    </div>
                    
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <th class="bg-light" style="width: 200px;">Staff ID:</th>
                                    <td><span class="fw-semibold text-primary"><?php echo htmlspecialchars($staff['staff_id']); ?></span></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Name:</th>
                                    <td><?php echo htmlspecialchars($staff['staff_name']); ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Department:</th>
                                    <td>
                                        <span class="badge bg-info bg-opacity-10 text-info">
                                            <?php echo htmlspecialchars($staff['department_name'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Email:</th>
                                    <td><?php echo htmlspecialchars($staff['staff_email']); ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Position:</th>
                                    <td><?php echo htmlspecialchars($staff['staff_position']); ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Status:</th>
                                    <td>
                                        <span class="badge bg-<?php echo $staff['staff_status'] === 'Working' ? 'success' : 'warning'; ?> rounded-pill">
                                            <?php echo htmlspecialchars($staff['staff_status'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <form method="POST" action="<?php echo APP_URL; ?>/staff/delete?id=<?php echo urlencode($staff['staff_id']); ?>">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash me-1"></i>Yes, Delete Staff
                            </button>
                            <a href="<?php echo APP_URL; ?>/staff" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

