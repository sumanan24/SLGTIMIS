<div class="container-fluid px-4 py-3">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-plus-circle me-2"></i>Create New Payment</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <div><?php echo htmlspecialchars($error); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="<?php echo APP_URL; ?>/payments/create">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="student_id" class="form-label fw-semibold">
                                    Student <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="student_id" name="student_id" required>
                                    <option value="">Select Student</option>
                                    <?php if (isset($students) && !empty($students)): ?>
                                        <?php foreach ($students as $student): ?>
                                            <option value="<?php echo htmlspecialchars($student['student_id']); ?>">
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
                                       value="<?php echo date('Y-m-d'); ?>" required>
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
                                            echo '<option value="' . htmlspecialchars($type) . '">' . htmlspecialchars($type) . '</option>';
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
                                       step="0.01" min="0.01" required placeholder="0.00">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="pays_qty" class="form-label fw-semibold">Quantity</label>
                                <input type="number" class="form-control" id="pays_qty" name="pays_qty" 
                                       value="1" min="1" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="payment_method" class="form-label fw-semibold">Payment Method</label>
                                <select class="form-select" id="payment_method" name="payment_method">
                                    <option value="">Select Method</option>
                                    <option value="CASH">Cash</option>
                                    <option value="BANK">Bank Transfer</option>
                                    <option value="CHEQUE">Cheque</option>
                                    <option value="ONLINE">Online Payment</option>
                                    <option value="OTHER">Other</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="reference_no" class="form-label fw-semibold">Reference Number</label>
                                <input type="text" class="form-control" id="reference_no" name="reference_no" 
                                       placeholder="Optional reference number">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="approved" class="form-label fw-semibold">Approval Status</label>
                                <select class="form-select" id="approved" name="approved">
                                    <option value="0">Pending</option>
                                    <option value="1">Approved</option>
                                </select>
                            </div>
                            
                            <div class="col-12 mb-4">
                                <label for="payment_notes" class="form-label fw-semibold">Notes</label>
                                <textarea class="form-control" id="payment_notes" name="payment_notes" 
                                          rows="3" placeholder="Additional notes about this payment"></textarea>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Create Payment
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
    
    paymentTypeSelect.addEventListener('change', function() {
        const selectedType = this.value;
        paymentReasonSelect.innerHTML = '<option value="">Select Payment Reason</option>';
        
        if (selectedType) {
            const filteredReasons = paymentReasons.filter(r => r.payment_type === selectedType);
            filteredReasons.forEach(function(reason) {
                const option = document.createElement('option');
                option.value = reason.payment_reason;
                option.textContent = reason.payment_reason;
                paymentReasonSelect.appendChild(option);
            });
        }
    });
});
</script>
