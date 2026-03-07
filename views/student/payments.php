<?php
$student = $student ?? [];
$hostelPayments = $hostelPayments ?? [];
$busSeasonPayments = $busSeasonPayments ?? [];
?>

<div class="container-fluid px-2 px-md-3 px-lg-4 py-3">
    <div class="mb-3">
        <a href="<?php echo APP_URL; ?>/student/dashboard" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
        </a>
    </div>
    
    <div class="student-dashboard-card p-3 p-md-4 mb-3">
        <h4 class="fw-bold mb-1">
            <i class="fas fa-money-bill-wave me-2" style="color: var(--student-primary);"></i>
            My Payments
        </h4>
        <p class="text-muted mb-0 small">
            Student ID: <?php echo htmlspecialchars($student['student_id'] ?? ''); ?> 
            &mdash; <?php echo htmlspecialchars($student['student_fullname'] ?? ''); ?>
        </p>
    </div>
    
    <div class="row g-3 g-md-4">
        <div class="col-12 col-lg-6">
            <div class="student-dashboard-card p-3 p-md-4 h-100">
                <h5 class="fw-bold mb-3">
                    <i class="fas fa-bus me-2 text-success"></i>Bus Season Payments
                </h5>
                <?php if (!empty($busSeasonPayments)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Student Paid</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($busSeasonPayments as $p): ?>
                                    <tr>
                                        <td class="small">
                                            <?php
                                            $date = $p['payment_date'] ?? $p['issued_at'] ?? null;
                                            echo $date ? htmlspecialchars(date('Y-m-d', strtotime($date))) : 'N/A';
                                            ?>
                                        </td>
                                        <td class="small text-success">
                                            Rs. <?php echo number_format($p['paid_amount'] ?? $p['student_paid'] ?? 0, 2); ?>
                                        </td>
                                        <td class="small">
                                            Rs. <?php echo number_format($p['total_amount'] ?? 0, 2); ?>
                                        </td>
                                        <td class="small">
                                            <span class="badge bg-<?php echo strtolower($p['status'] ?? $p['payment_status'] ?? '') === 'issued' ? 'success' : 'secondary'; ?>">
                                                <?php echo htmlspecialchars(ucfirst($p['status'] ?? $p['payment_status'] ?? 'N/A')); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted small mb-0">No bus season payments recorded.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="col-12 col-lg-6">
            <div class="student-dashboard-card p-3 p-md-4 h-100">
                <h5 class="fw-bold mb-3">
                    <i class="fas fa-receipt me-2 text-primary"></i>Hostel & Other Payments
                </h5>
                <?php if (!empty($hostelPayments)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Reason</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($hostelPayments as $p): ?>
                                    <tr>
                                        <td class="small">
                                            <?php echo htmlspecialchars(!empty($p['pays_date']) ? date('Y-m-d', strtotime($p['pays_date'])) : 'N/A'); ?>
                                        </td>
                                        <td class="small text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($p['payment_reason'] ?? ''); ?>">
                                            <?php echo htmlspecialchars($p['payment_reason'] ?? ''); ?>
                                        </td>
                                        <td class="small text-success">
                                            Rs. <?php echo number_format($p['pays_amount'] ?? 0, 2); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted small mb-0">No payments recorded.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

