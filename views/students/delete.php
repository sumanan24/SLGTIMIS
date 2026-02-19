<div class="container-fluid px-4 py-3">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-trash me-2"></i>Delete Student</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning d-flex align-items-center" role="alert">
                        <i class="fas fa-exclamation-triangle me-2 fa-lg"></i>
                        <div>
                            <strong>Warning!</strong> Are you sure you want to delete this student? This action cannot be undone.
                        </div>
                    </div>
                    
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <th class="bg-light" style="width: 200px;">Student ID:</th>
                                    <td><span class="fw-semibold text-primary"><?php echo htmlspecialchars($student['student_id']); ?></span></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Full Name:</th>
                                    <td><?php echo htmlspecialchars($student['student_fullname']); ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Email:</th>
                                    <td><?php echo htmlspecialchars($student['student_email']); ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">NIC:</th>
                                    <td><?php echo htmlspecialchars($student['student_nic']); ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Status:</th>
                                    <td>
                                        <span class="badge bg-<?php echo $student['student_status'] === 'Active' ? 'success' : 'warning'; ?> rounded-pill">
                                            <?php echo htmlspecialchars($student['student_status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <form method="POST" action="<?php echo APP_URL; ?>/students/delete?id=<?php echo urlencode($student['student_id']); ?>">
                        <?php if (!empty($filters)): ?>
                            <?php foreach ($filters as $key => $value): ?>
                                <?php if (!empty($value)): ?>
                                    <input type="hidden" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($value); ?>">
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash me-1"></i>Yes, Delete Student
                            </button>
                            <?php 
                            $cancelUrl = APP_URL . '/students';
                            if (!empty($filters)) {
                                $cancelUrl .= '?' . http_build_query($filters);
                            }
                            ?>
                            <a href="<?php echo $cancelUrl; ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

