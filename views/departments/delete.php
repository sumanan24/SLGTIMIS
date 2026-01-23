<div class="container-fluid px-4 py-3">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-trash me-2"></i>Delete Department</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning d-flex align-items-center" role="alert">
                        <i class="fas fa-exclamation-triangle me-2 fa-lg"></i>
                        <div>
                            <strong>Warning!</strong> Are you sure you want to delete this department? This action cannot be undone.
                        </div>
                    </div>
                    
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <th class="bg-light" style="width: 200px;">Department ID:</th>
                                    <td><span class="fw-semibold"><?php echo htmlspecialchars($department['department_id']); ?></span></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Department Name:</th>
                                    <td><?php echo htmlspecialchars($department['department_name']); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <form method="POST" action="<?php echo APP_URL; ?>/departments/delete?id=<?php echo urlencode($department['department_id']); ?>">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash me-1"></i>Yes, Delete Department
                            </button>
                            <a href="<?php echo APP_URL; ?>/departments" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

