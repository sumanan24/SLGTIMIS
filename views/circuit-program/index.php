<div class="container-fluid px-4 py-3">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white">
            <div class="d-flex align-items-center justify-content-between flex-wrap">
                <h5 class="mb-0 fw-bold"><i class="fas fa-route me-2"></i>Circuit Program</h5>
                <a href="<?php echo APP_URL; ?>/circuit-program/create" class="btn btn-light btn-sm mt-2 mt-md-0">
                    <i class="fas fa-plus me-1"></i>Create Circuit Program
                </a>
            </div>
        </div>
        <div class="card-body">
            <?php if (isset($message)): ?>
                <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <div><?php echo htmlspecialchars($message); ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <div><?php echo htmlspecialchars($error); ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Filters -->
            <div class="card border mb-4 shadow-sm">
                <div class="card-body">
                    <form method="GET" action="<?php echo APP_URL; ?>/circuit-program" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label small">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="pending" <?php echo ($filters['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="approved" <?php echo ($filters['status'] ?? '') === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="rejected" <?php echo ($filters['status'] ?? '') === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Department</label>
                            <select name="department_id" class="form-select">
                                <option value="">All Departments</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo htmlspecialchars($dept['department_id']); ?>" 
                                            <?php echo ($filters['department_id'] ?? '') === $dept['department_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['department_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-1"></i>Filter
                            </button>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <a href="<?php echo APP_URL; ?>/circuit-program" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-times me-1"></i>Clear
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <?php if (!empty($programs)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="fw-bold">Employee Name</th>
                                <th class="fw-bold">Designation</th>
                                <th class="fw-bold">Department</th>
                                <th class="fw-bold">Mode of Travel</th>
                                <th class="fw-bold">Status</th>
                                <th class="fw-bold">Created</th>
                                <th class="fw-bold text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($programs as $program): ?>
                                <tr>
                                    <td><span class="fw-semibold"><?php echo htmlspecialchars($program['employee_name']); ?></span></td>
                                    <td><?php echo htmlspecialchars($program['designation'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge bg-info bg-opacity-10 text-info">
                                            <?php echo htmlspecialchars($program['department_name'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($program['mode_of_travel'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php
                                        $statusClass = 'warning';
                                        $statusText = 'Pending';
                                        if ($program['status'] === 'approved') {
                                            $statusClass = 'success';
                                            $statusText = 'Approved';
                                        } elseif ($program['status'] === 'rejected') {
                                            $statusClass = 'danger';
                                            $statusText = 'Rejected';
                                        }
                                        ?>
                                        <span class="badge bg-<?php echo $statusClass; ?> rounded-pill px-3 py-2">
                                            <i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i>
                                            <?php echo $statusText; ?>
                                        </span>
                                    </td>
                                    <td><small class="text-muted"><?php echo $program['created_at'] ? date('M d, Y', strtotime($program['created_at'])) : 'N/A'; ?></small></td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="<?php echo APP_URL; ?>/circuit-program/view?id=<?php echo $program['id']; ?>" class="btn btn-outline-primary" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($program['status'] === 'pending'): ?>
                                                <?php
                                                // Check if user owns this program or is admin
                                                require_once BASE_PATH . '/models/UserModel.php';
                                                $userModel = new UserModel();
                                                $user = $userModel->find($_SESSION['user_id']);
                                                $staffId = $user['user_name'] ?? null;
                                                $isAdmin = $userModel->isAdmin($_SESSION['user_id']);
                                                $canDelete = ($program['staff_id'] === $staffId) || $isAdmin;
                                                ?>
                                                <?php if ($canDelete): ?>
                                                <form method="POST" action="<?php echo APP_URL; ?>/circuit-program/delete" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this circuit program?');">
                                                    <input type="hidden" name="id" value="<?php echo $program['id']; ?>">
                                                    <button type="submit" class="btn btn-outline-danger" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center py-4">
                    <i class="fas fa-info-circle me-2"></i>
                    No circuit programs found.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

