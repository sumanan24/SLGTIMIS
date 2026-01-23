<div class="container-fluid px-4 py-3">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-edit me-2"></i>Edit Payment</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <div><?php echo htmlspecialchars($error); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="<?php echo APP_URL; ?>/payments/edit?id=<?php echo urlencode($payment['pays_id']); ?>">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="student_id" class="form-label fw-semibold">
                                    Student <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="student_id" name="student_id" required>
                                    <option value="">Select Student</option>
                                    <?php if (isset($students) && !empty($students)): ?>
                                        <?php foreach ($students as $student): ?>
                                            <option value="<?php echo htmlspecialchars($student['student_id']); ?>" 
                                                    <?php echo ($payment['student_id'] ?? '') == $student['student_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($student['student_id'] . ' - ' . $student['student_fullname']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="payment_date" class="form-label fw-semibold">
                                    Payment Date <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control" id="payment_date" name="payment_date" 
                                       value="<?php echo htmlspecialchars($payment['pays_date'] ?? date('Y-m-d')); ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="payment_type" class="form-label fw-semibold">
                                    Payment Type <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="payment_type" name="payment_type" required>
                                    <option value="">Select Payment Type</option>
                                    <?php
                                    $types = [];
                                    if (isset($paymentReasons) && !empty($paymentReasons)) {
                                        foreach ($paymentReasons as $reason) {
                                            if (!in_array($reason['payment_type'], $types)) {
                                                $types[] = $reason['payment_type'];
                                            }
                                        }
                                        foreach ($types as $type) {
                                            $selected = ($payment['payment_type'] ?? '') == $type ? 'selected' : '';
                                            echo '<option value="' . htmlspecialchars($type) . '" ' . $selected . '>' . htmlspecialchars($type) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="payment_reason" class="form-label fw-semibold">
                                    Payment Reason <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="payment_reason" name="payment_reason" required>
                                    <option value="">Select Payment Type First</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="payment_amount" class="form-label fw-semibold">
                                    Payment Amount (Rs.) <span class="text-danger">*</span>
                                </label>
                                <input type="number" class="form-control" id="payment_amount" name="payment_amount" 
                                       step="0.01" min="0.01" value="<?php echo htmlspecialchars($payment['pays_amount'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="pays_qty" class="form-label fw-semibold">Quantity</label>
                                <input type="number" class="form-control" id="pays_qty" name="pays_qty" 
                                       value="<?php echo htmlspecialchars($payment['pays_qty'] ?? 1); ?>" min="1" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="payment_method" class="form-label fw-semibold">Payment Method</label>
                                <select class="form-select" id="payment_method" name="payment_method">
                                    <option value="">Select Method</option>
                                    <option value="CASH" <?php echo ($payment['payment_method'] ?? '') == 'CASH' ? 'selected' : ''; ?>>Cash</option>
                                    <option value="BANK" <?php echo ($payment['payment_method'] ?? '') == 'BANK' ? 'selected' : ''; ?>>Bank Transfer</option>
                                    <option value="CHEQUE" <?php echo ($payment['payment_method'] ?? '') == 'CHEQUE' ? 'selected' : ''; ?>>Cheque</option>
                                    <option value="ONLINE" <?php echo ($payment['payment_method'] ?? '') == 'ONLINE' ? 'selected' : ''; ?>>Online Payment</option>
                                    <option value="OTHER" <?php echo ($payment['payment_method'] ?? '') == 'OTHER' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="reference_no" class="form-label fw-semibold">Reference Number</label>
                                <input type="text" class="form-control" id="reference_no" name="reference_no" 
                                       value="<?php echo htmlspecialchars($payment['reference_no'] ?? ''); ?>" placeholder="Optional reference number">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="approved" class="form-label fw-semibold">Approval Status</label>
                                <select class="form-select" id="approved" name="approved">
                                    <option value="0" <?php echo (empty($payment['approved']) || $payment['approved'] == 0) ? 'selected' : ''; ?>>Pending</option>
                                    <option value="1" <?php echo (!empty($payment['approved']) && $payment['approved'] == 1) ? 'selected' : ''; ?>>Approved</option>
                                </select>
                            </div>
                            
                            <div class="col-12 mb-4">
                                <label for="payment_notes" class="form-label fw-semibold">Notes</label>
                                <textarea class="form-control" id="payment_notes" name="payment_notes" 
                                          rows="3"><?php echo htmlspecialchars($payment['pays_note'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Update Payment
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const paymentTypeSelect = document.getElementById('payment_type');
    const paymentReasonSelect = document.getElementById('payment_reason');
    
    const paymentReasons = <?php echo json_encode($paymentReasons ?? []); ?>;
    const currentPaymentType = '<?php echo htmlspecialchars($payment['payment_type'] ?? ''); ?>';
    const currentPaymentReason = '<?php echo htmlspecialchars($payment['payment_reason'] ?? ''); ?>';
    
    function updatePaymentReasons() {
        const selectedType = paymentTypeSelect.value;
        paymentReasonSelect.innerHTML = '<option value="">Select Payment Reason</option>';
        
        if (selectedType) {
            const filteredReasons = paymentReasons.filter(r => r.payment_type === selectedType);
            filteredReasons.forEach(function(reason) {
                const option = document.createElement('option');
                option.value = reason.payment_reason;
                option.textContent = reason.payment_reason;
                if (reason.payment_reason === currentPaymentReason) {
                    option.selected = true;
                }
                paymentReasonSelect.appendChild(option);
            });
        }
    }
    
    // Initialize on page load
    updatePaymentReasons();
    
    // Update when type changes
    paymentTypeSelect.addEventListener('change', updatePaymentReasons);
});
</script>
