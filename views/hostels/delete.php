<div class="container-fluid px-4 py-3">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-trash me-2"></i>Delete Hostel</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> This action cannot be undone. Make sure this hostel has no rooms assigned.
                    </div>
                    
                    <p>Are you sure you want to delete the following hostel?</p>
                    
                    <table class="table table-bordered">
                        <tr>
                            <th>Name:</th>
                            <td><?php echo htmlspecialchars($hostel['name']); ?></td>
                        </tr>
                        <tr>
                            <th>Location:</th>
                            <td><?php echo htmlspecialchars($hostel['location']); ?></td>
                        </tr>
                        <tr>
                            <th>Gender:</th>
                            <td><?php echo htmlspecialchars($hostel['gender']); ?></td>
                        </tr>
                    </table>
                    
                    <form method="POST" action="<?php echo APP_URL; ?>/hostels/delete?id=<?php echo urlencode($hostel['id']); ?>">
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="<?php echo APP_URL; ?>/hostels" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash me-2"></i>Delete Hostel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

