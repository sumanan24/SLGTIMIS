<div class="container-fluid px-4 py-3">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>Delete Payment</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning d-flex align-items-center" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <div>Are you sure you want to delete this payment? This action cannot be undone.</div>
                    </div>
                    
                    <div class="card mb-3">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3">Payment Details</h6>
                            <table class="table table-sm mb-0">
                                <tr>
                                    <td class="fw-semibold" style="width: 40%;">Payment ID:</td>
                                    <td>#<?php echo htmlspecialchars($payment['pays_id']); ?></td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold">Student:</td>
                                    <td>
                                        <?php echo htmlspecialchars($payment['student_reg_no'] ?? $payment['student_id']); ?>
                                        <?php if (!empty($payment['student_fullname'])): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($payment['student_fullname']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold">Date:</td>
                                    <td><?php echo date('M d, Y', strtotime($payment['pays_date'])); ?></td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold">Amount:</td>
                                    <td><span class="fw-bold text-success">Rs. <?php echo number_format($payment['pays_amount'], 2); ?></span></td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold">Reason:</td>
                                    <td><?php echo htmlspecialchars($payment['payment_type'] ?? '-'); ?></td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold">Reason:</td>
                                    <td><?php echo htmlspecialchars($payment['payment_reason'] ?? '-'); ?></td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold">Status:</td>
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
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <form method="POST" action="<?php echo APP_URL; ?>/payments/delete?id=<?php echo urlencode($payment['pays_id']); ?>">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash me-1"></i>Delete Payment
                            </button>
                            <a href="<?php echo APP_URL; ?>/payments" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

