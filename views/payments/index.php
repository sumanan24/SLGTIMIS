<div class="container-fluid px-4 py-3">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white">
            <div class="d-flex align-items-center justify-content-between flex-wrap">
                <h5 class="mb-0 fw-bold"><i class="fas fa-money-bill-wave me-2"></i>Payments</h5>
                <a href="<?php echo APP_URL; ?>/payments/create" class="btn btn-light btn-sm mt-2 mt-md-0">
                    <i class="fas fa-plus me-1"></i>Add New Payment
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
            <form method="GET" action="<?php echo APP_URL; ?>/payments" class="mb-4">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="search" class="form-label fw-semibold">Search</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>" 
                               placeholder="Student name, ID, or reason">
                    </div>
                    <div class="col-md-2">
                        <label for="student_id" class="form-label fw-semibold">Student</label>
                        <select class="form-select" id="student_id" name="student_id">
                            <option value="">All Students</option>
                            <?php if (isset($students) && !empty($students)): ?>
                                <?php foreach ($students as $student): ?>
                                    <option value="<?php echo htmlspecialchars($student['student_id']); ?>" 
                                            <?php echo ($filters['student_id'] ?? '') == $student['student_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($student['student_id'] . ' - ' . $student['student_fullname']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="approved" class="form-label fw-semibold">Status</label>
                        <select class="form-select" id="approved" name="approved">
                            <option value="">All Status</option>
                            <option value="1" <?php echo ($filters['approved'] ?? '') == '1' ? 'selected' : ''; ?>>Approved</option>
                            <option value="0" <?php echo ($filters['approved'] ?? '') == '0' ? 'selected' : ''; ?>>Pending</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="date_from" class="form-label fw-semibold">Date From</label>
                        <input type="date" class="form-control" id="date_from" name="date_from" 
                               value="<?php echo htmlspecialchars($filters['date_from'] ?? ''); ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="date_to" class="form-label fw-semibold">Date To</label>
                        <input type="date" class="form-control" id="date_to" name="date_to" 
                               value="<?php echo htmlspecialchars($filters['date_to'] ?? ''); ?>">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label fw-semibold">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i>
                            </button>
                            <a href="<?php echo APP_URL; ?>/payments" class="btn btn-outline-secondary">
                                <i class="fas fa-redo"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </form>
            
            <?php if (!empty($payments)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="fw-bold">Payment ID</th>
                                <th class="fw-bold">Student</th>
                                <th class="fw-bold">Date</th>
                                <th class="fw-bold">Amount</th>
                                <th class="fw-bold">Type</th>
                                <th class="fw-bold">Reason</th>
                                <th class="fw-bold">Method</th>
                                <th class="fw-bold">Status</th>
                                <th class="fw-bold text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><span class="fw-semibold text-primary">#<?php echo htmlspecialchars($payment['pays_id']); ?></span></td>
                                    <td>
                                        <div><?php echo htmlspecialchars($payment['student_reg_no'] ?? $payment['student_id']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($payment['student_fullname'] ?? '-'); ?></small>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($payment['pays_date'])); ?></td>
                                    <td><span class="fw-bold text-success">Rs. <?php echo number_format($payment['pays_amount'], 2); ?></span></td>
                                    <td><?php echo htmlspecialchars($payment['payment_type'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($payment['payment_reason'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($payment['payment_method'] ?? '-'); ?></td>
                                    <td>
                                        <?php
                                        $statusClass = 'bg-warning text-dark';
                                        $statusText = 'Pending';
                                        if (!empty($payment['approved']) && $payment['approved'] == 1) {
                                            $statusClass = 'bg-success';
                                            $statusText = 'Approved';
                                        }
                                        ?>
                                        <span class="badge <?php echo $statusClass; ?>">
                                            <?php echo $statusText; ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="<?php echo APP_URL; ?>/payments/edit?id=<?php echo urlencode($payment['pays_id']); ?>" class="btn btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?php echo APP_URL; ?>/payments/delete?id=<?php echo urlencode($payment['pays_id']); ?>" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to delete this payment?');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Payment pagination" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php
                            $queryParams = $filters;
                            $queryParams['page'] = max(1, $page - 1);
                            ?>
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo APP_URL; ?>/payments?<?php echo http_build_query($queryParams); ?>">Previous</a>
                            </li>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <?php
                                $queryParams['page'] = $i;
                                ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?php echo APP_URL; ?>/payments?<?php echo http_build_query($queryParams); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php
                            $queryParams['page'] = min($totalPages, $page + 1);
                            ?>
                            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo APP_URL; ?>/payments?<?php echo http_build_query($queryParams); ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-money-bill-wave fa-3x text-muted mb-3"></i>
                    <p class="text-muted mb-3">No payments found.</p>
                    <a href="<?php echo APP_URL; ?>/payments/create" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Create one now
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

