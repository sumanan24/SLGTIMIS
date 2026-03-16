<style>
/* Departments delete - mobile full-width, no side space */
@media (max-width: 768px) {
    .dept-delete-wrap.container-fluid {
        padding-left: 0 !important;
        padding-right: 0 !important;
        padding-top: 0.5rem;
        padding-bottom: 0.5rem;
    }
    .dept-delete-wrap .row {
        margin-left: 0;
        margin-right: 0;
    }
    .dept-delete-wrap .row > [class*="col-"] {
        padding-left: 0;
        padding-right: 0;
    }
    .dept-delete-wrap .col-lg-6 {
        max-width: 100%;
        flex: 0 0 100%;
    }
    .dept-delete-wrap .card {
        border-radius: 0;
    }
    .dept-delete-wrap .card-header {
        padding: 0.75rem 0.5rem;
    }
    .dept-delete-wrap .card-header h5 {
        font-size: 1rem;
    }
    .dept-delete-wrap .card-body {
        padding: 0.75rem 0.5rem !important;
    }
    .dept-delete-wrap .table-responsive {
        margin-left: -0.5rem;
        margin-right: -0.5rem;
        padding-left: 0.5rem;
        padding-right: 0.5rem;
        -webkit-overflow-scrolling: touch;
    }
    .dept-delete-wrap .table {
        font-size: 0.9375rem;
    }
    .dept-delete-wrap .table th,
    .dept-delete-wrap .table td {
        padding: 0.5rem 0.4rem;
        white-space: nowrap;
    }
    .dept-delete-wrap .d-flex.gap-2 {
        flex-direction: column;
    }
    .dept-delete-wrap .d-flex.gap-2 .btn {
        width: 100%;
        justify-content: center;
    }
}
@media (max-width: 576px) {
    .dept-delete-wrap .card-body {
        padding: 0.5rem 0.375rem !important;
    }
    .dept-delete-wrap .card-header {
        padding: 0.625rem 0.375rem;
    }
}
</style>

<div class="container-fluid px-4 py-3 dept-delete-wrap">
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

