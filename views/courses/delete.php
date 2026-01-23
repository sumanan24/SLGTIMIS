<div class="container-fluid px-4 py-3">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-trash me-2"></i>Delete Course</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning d-flex align-items-center" role="alert">
                        <i class="fas fa-exclamation-triangle me-2 fa-lg"></i>
                        <div>
                            <strong>Warning!</strong> Are you sure you want to delete this course? This action cannot be undone.
                        </div>
                    </div>
                    
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <th class="bg-light" style="width: 200px;">Course ID:</th>
                                    <td><span class="fw-semibold text-primary"><?php echo htmlspecialchars($course['course_id']); ?></span></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Course Name:</th>
                                    <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Department:</th>
                                    <td>
                                        <span class="badge bg-info bg-opacity-10 text-info">
                                            <?php echo htmlspecialchars($course['department_name'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="bg-light">NVQ Level:</th>
                                    <td>
                                        <span class="badge bg-primary rounded-pill">
                                            Level <?php echo htmlspecialchars($course['course_nvq_level']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="bg-light">OJT Duration:</th>
                                    <td><?php echo htmlspecialchars($course['course_ojt_duration']); ?> months</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Institute Training:</th>
                                    <td><?php echo htmlspecialchars($course['course_institute_training']); ?> months</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <form method="POST" action="<?php echo APP_URL; ?>/courses/delete?id=<?php echo urlencode($course['course_id']); ?>">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash me-1"></i>Yes, Delete Course
                            </button>
                            <a href="<?php echo APP_URL; ?>/courses" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

