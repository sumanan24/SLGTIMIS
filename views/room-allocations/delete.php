<div class="container-fluid px-4 py-3">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-trash me-2"></i>Delete Allocation</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> This action cannot be undone.
                    </div>
                    
                    <p>Are you sure you want to delete the following allocation?</p>
                    
                    <table class="table table-bordered">
                        <tr>
                            <th>Student:</th>
                            <td><?php echo htmlspecialchars($allocation['student_fullname'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>Student ID:</th>
                            <td><?php echo htmlspecialchars($allocation['student_id'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>Hostel:</th>
                            <td><?php echo htmlspecialchars($allocation['hostel_name'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>Block:</th>
                            <td><?php echo htmlspecialchars($allocation['block_name'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>Room:</th>
                            <td><?php echo htmlspecialchars($allocation['room_no'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td>
                                <span class="badge <?php echo (($allocation['status'] ?? 'active') === 'active') ? 'bg-success' : 'bg-secondary'; ?>">
                                    <?php echo htmlspecialchars(ucfirst($allocation['status'] ?? 'active')); ?>
                                </span>
                            </td>
                        </tr>
                    </table>
                    
                    <form method="POST" action="<?php echo APP_URL; ?>/room-allocations/delete?id=<?php echo urlencode($allocation['id'] ?? ''); ?>">
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="<?php echo APP_URL; ?>/room-allocations" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash me-2"></i>Delete Allocation
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

