<style>
    .request-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        margin-bottom: 1.5rem;
        border-left: 4px solid #198754;
    }
    
    .request-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .student-info {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
    }
    
    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 600;
        display: inline-block;
    }
    
    .status-pending {
        background-color: #fff3cd;
        color: #856404;
    }
    
    .status-hod_approved {
        background-color: #cfe2ff;
        color: #084298;
    }
    
    .status-approved {
        background-color: #d1e7dd;
        color: #0f5132;
    }
    
    .status-rejected {
        background-color: #f8d7da;
        color: #721c24;
    }
    
    .filter-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    
    @media (max-width: 768px) {
        .request-card {
            padding: 1rem !important;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .filter-card {
            padding: 1rem;
        }
        
        .student-info {
            padding: 0.75rem;
        }
    }
</style>

<div class="container-fluid px-3 px-md-4 py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold mb-2">
                <i class="fas fa-money-bill-wave me-2 text-success"></i>
                Bus Season Payment Collections
            </h2>
            <p class="text-muted mb-0">
                View all bus season payment collections
            </p>
        </div>
    </div>
    
    <?php if (isset($message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <!-- Filters -->
    <div class="filter-card">
        <form method="GET" action="<?php echo APP_URL; ?>/bus-season-requests/payment-collections" class="row g-3">
            <div class="col-md-6">
                <label for="season_year" class="form-label fw-semibold">Season Year</label>
                <select class="form-select" id="season_year" name="season_year">
                    <option value="">All Years</option>
                    <?php if (!empty($academicYears)): ?>
                        <?php foreach ($academicYears as $year): ?>
                            <option value="<?php echo htmlspecialchars($year); ?>" 
                                    <?php echo (isset($filters['season_year']) && $filters['season_year'] === $year) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($year); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            
            <div class="col-md-6">
                <label for="student_id" class="form-label fw-semibold">Student ID</label>
                <input type="text" class="form-control" id="student_id" name="student_id" 
                       value="<?php echo htmlspecialchars($filters['student_id'] ?? ''); ?>" 
                       placeholder="Enter student ID">
            </div>
            
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter me-2"></i>Apply Filters
                </button>
                <a href="<?php echo APP_URL; ?>/bus-season-requests/payment-collections" class="btn btn-outline-secondary ms-2">
                    <i class="fas fa-times me-2"></i>Clear Filters
                </a>
            </div>
        </form>
    </div>
    
    <?php if (empty($collections)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>No bus season requests found.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Student ID</th>
                        <th>Student Name</th>
                        <th>Season Year</th>
                        <th>Department</th>
                        <th>Route</th>
                        <th>Request Status</th>
                        <th>HOD Approval</th>
                        <th>Second Approval</th>
                        <th>Payment Status</th>
                        <th>Student Paid (30%)</th>
                        <th>SLGTI Paid (35%)</th>
                        <th>CTB Paid (35%)</th>
                        <th>Total Value</th>
                        <th>Payment Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($collections as $collection): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($collection['student_id']); ?></td>
                            <td><?php echo htmlspecialchars($collection['student_fullname'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($collection['season_year'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($collection['department_name'] ?? 'N/A'); ?></td>
                            <td>
                                <small>
                                    <?php echo htmlspecialchars($collection['route_from'] ?? ''); ?> → 
                                    <?php echo htmlspecialchars($collection['route_to'] ?? ''); ?>
                                </small>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($collection['request_status'] ?? 'pending'); ?>">
                                    <?php 
                                    $status = $collection['request_status'] ?? 'pending';
                                    $statusLabels = [
                                        'pending' => 'Pending HOD',
                                        'hod_approved' => 'HOD Approved',
                                        'approved' => 'Approved',
                                        'rejected' => 'Rejected'
                                    ];
                                    echo $statusLabels[$status] ?? ucfirst($status);
                                    ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($collection['hod_approver_name'])): ?>
                                    <span class="badge bg-success">
                                        <i class="fas fa-check me-1"></i>Approved by <?php echo htmlspecialchars($collection['hod_approver_name']); ?>
                                    </span>
                                    <?php if (!empty($collection['hod_approval_date'])): ?>
                                        <br><small class="text-muted"><?php echo date('M d, Y', strtotime($collection['hod_approval_date'])); ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge bg-warning">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($collection['second_approver_name'])): ?>
                                    <span class="badge bg-success">
                                        <i class="fas fa-check me-1"></i>Approved by <?php echo htmlspecialchars($collection['second_approver_name']); ?> (<?php echo htmlspecialchars($collection['second_approver_role'] ?? ''); ?>)
                                    </span>
                                    <?php if (!empty($collection['second_approval_date'])): ?>
                                        <br><small class="text-muted"><?php echo date('M d, Y', strtotime($collection['second_approval_date'])); ?></small>
                                    <?php endif; ?>
                                <?php elseif ($collection['request_status'] === 'hod_approved'): ?>
                                    <span class="badge bg-info">Waiting for Second Approval</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($collection['payment_id'])): ?>
                                    <span class="badge bg-success">
                                        <i class="fas fa-check-circle me-1"></i>Collected
                                    </span>
                                <?php elseif ($collection['request_status'] === 'approved'): ?>
                                    <span class="badge bg-warning">Pending Collection</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Not Available</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-success fw-bold">
                                <?php if (!empty($collection['student_paid'])): ?>
                                    Rs. <?php echo number_format($collection['student_paid'], 2); ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($collection['slgti_paid'])): ?>
                                    Rs. <?php echo number_format($collection['slgti_paid'], 2); ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($collection['ctb_paid'])): ?>
                                    Rs. <?php echo number_format($collection['ctb_paid'], 2); ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-primary fw-bold">
                                <?php if (!empty($collection['total_amount'])): ?>
                                    Rs. <?php echo number_format($collection['total_amount'], 2); ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($collection['payment_date'])): ?>
                                    <?php echo date('M d, Y', strtotime($collection['payment_date'])); ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($requestId = $collection['request_id'] ?? null): ?>
                                    <a href="<?php echo APP_URL; ?>/bus-season-requests/view?id=<?php echo $requestId; ?>" 
                                       class="btn btn-sm btn-outline-primary" title="View Request Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if ($collection['request_status'] === 'approved' && empty($collection['payment_id'])): ?>
                                        <a href="<?php echo APP_URL; ?>/bus-season-requests/sao-process" 
                                           class="btn btn-sm btn-success ms-1" title="Process Payment">
                                            <i class="fas fa-money-bill-wave"></i>
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Summary Statistics -->
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title text-primary">Total Requests</h5>
                        <h3 class="mb-0"><?php echo count($collections); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title text-success">Payment Collected</h5>
                        <h3 class="mb-0 text-success">
                            <?php 
                            $collectedCount = 0;
                            $totalAmount = 0;
                            foreach ($collections as $c) {
                                if (!empty($c['payment_id'])) {
                                    $collectedCount++;
                                    $totalAmount += ($c['total_amount'] ?? 0);
                                }
                            }
                            echo $collectedCount . ' (' . number_format($totalAmount, 2) . ')';
                            ?>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title text-info">Pending Collection</h5>
                        <h3 class="mb-0 text-info">
                            <?php 
                            $pendingCount = 0;
                            foreach ($collections as $c) {
                                if ($c['request_status'] === 'approved' && empty($c['payment_id'])) {
                                    $pendingCount++;
                                }
                            }
                            echo $pendingCount;
                            ?>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title text-warning">HOD Approved</h5>
                        <h3 class="mb-0 text-warning">
                            <?php 
                            $hodApprovedCount = 0;
                            foreach ($collections as $c) {
                                if (in_array($c['request_status'], ['hod_approved', 'approved'])) {
                                    $hodApprovedCount++;
                                }
                            }
                            echo $hodApprovedCount;
                            ?>
                        </h3>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

