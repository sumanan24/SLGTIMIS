<div class="container-fluid px-4 py-3">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-trash me-2"></i>Delete Room</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> This action cannot be undone. Make sure this room has no active allocations.
                    </div>
                    
                    <p>Are you sure you want to delete the following room?</p>
                    
                    <table class="table table-bordered">
                        <tr>
                            <th>Room Number:</th>
                            <td><?php echo htmlspecialchars($room['room_no'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>Hostel:</th>
                            <td><?php echo htmlspecialchars($room['hostel_name'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>Block:</th>
                            <td><?php echo htmlspecialchars($room['block_name'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>Type:</th>
                            <td><?php echo htmlspecialchars($room['room_type'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>Capacity:</th>
                            <td><?php echo number_format($room['capacity'] ?? 0); ?></td>
                        </tr>
                        <tr>
                            <th>Occupied:</th>
                            <td><?php echo number_format($room['occupied_beds'] ?? 0); ?></td>
                        </tr>
                    </table>
                    
                    <form method="POST" action="<?php echo APP_URL; ?>/rooms/delete?id=<?php echo urlencode($room['id'] ?? ''); ?>">
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="<?php echo APP_URL; ?>/rooms" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash me-2"></i>Delete Room
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

