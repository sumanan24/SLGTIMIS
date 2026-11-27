<style>
.payment-form-wrap .payment-form .form-control,
.payment-form-wrap .payment-form .form-select {
    width: 100%;
}

@media (max-width: 768px) {
    .payment-form-wrap.container-fluid {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    .payment-form-wrap .card {
        border-radius: 0;
    }
    .payment-form-wrap .card-body {
        padding: 1rem !important;
    }
    .payment-form-wrap .payment-form-actions {
        flex-direction: column;
    }
    .payment-form-wrap .payment-form-actions .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<div class="container-fluid px-4 py-3 payment-form-wrap">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white">
            <div class="d-flex align-items-center justify-content-between flex-wrap">
                <h5 class="mb-0 fw-bold"><i class="fas fa-edit me-2"></i>Edit Payment</h5>
                <a href="<?php echo APP_URL; ?>/payment-types" class="btn btn-outline-light btn-sm mt-2 mt-md-0">
                    <i class="fas fa-tags me-1"></i>Manage Payment Types
                </a>
            </div>
        </div>
        <div class="card-body">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <div><?php echo htmlspecialchars($error); ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo APP_URL; ?>/payments/edit?id=<?php echo urlencode($payment['pays_id']); ?>" class="payment-form">
                <div class="row g-3">
                    <div class="col-12 col-md-6 col-lg-4">
                        <label class="form-label fw-semibold mb-1">Student</label>
                        <div class="form-control-plaintext border rounded px-3 py-2 bg-light">
                            <strong><?php echo htmlspecialchars($payment['student_reg_no'] ?? $payment['student_id'] ?? '—'); ?></strong>
                            <?php if (!empty($payment['student_fullname'])): ?>
                                <span class="text-muted"> — <?php echo htmlspecialchars($payment['student_fullname']); ?></span>
                            <?php endif; ?>
                        </div>
                        <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($payment['student_id'] ?? ''); ?>">
                    </div>

                    <div class="col-12 col-md-6 col-lg-4">
                        <label for="payment_date" class="form-label fw-semibold mb-1">
                            Payment Date <span class="text-danger">*</span>
                        </label>
                        <input type="date" class="form-control" id="payment_date" name="payment_date"
                               value="<?php echo htmlspecialchars($payment['pays_date'] ?? date('Y-m-d')); ?>" required>
                    </div>

                    <div class="col-12 col-md-6 col-lg-4">
                        <label for="payment_type" class="form-label fw-semibold mb-1">
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

                    <div class="col-12 col-md-6 col-lg-3">
                        <label for="payment_reason" class="form-label fw-semibold mb-1">
                            Payment Reason <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="payment_reason" name="payment_reason" required>
                            <option value="">Select Payment Type First</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-6 col-lg-3">
                        <label for="payment_amount" class="form-label fw-semibold mb-1">
                            Payment Amount (Rs.) <span class="text-danger">*</span>
                        </label>
                        <input type="number" class="form-control" id="payment_amount" name="payment_amount"
                               step="0.01" min="0.01" value="<?php echo htmlspecialchars($payment['pays_amount'] ?? ''); ?>" required>
                    </div>

                    <div class="col-12 col-md-6 col-lg-3">
                        <label for="pays_qty" class="form-label fw-semibold mb-1">Quantity</label>
                        <input type="number" class="form-control" id="pays_qty" name="pays_qty"
                               value="<?php echo htmlspecialchars($payment['pays_qty'] ?? 1); ?>" min="1" required>
                    </div>

                    <?php $currentMethod = strtoupper(trim((string) ($payment['payment_method'] ?? 'CASH'))); ?>
                    <div class="col-12">
                        <div class="border rounded p-3 bg-light">
                            <div class="fw-semibold mb-2">Cash or bank <span class="text-danger">*</span></div>
                            <div class="d-flex flex-wrap gap-2" id="payMethodChoices">
                                <button type="button" class="btn btn-outline-primary pay-method" data-method="CASH">Cash</button>
                                <button type="button" class="btn btn-outline-primary pay-method" data-method="BANK">Bank</button>
                                <button type="button" class="btn btn-outline-primary pay-method" data-method="ONLINE">Online transfer</button>
                            </div>
                            <input type="hidden" name="payment_method" id="payment_method" value="<?php echo htmlspecialchars($currentMethod); ?>">
                            <div class="row g-3 mt-1">
                                <div class="col-12 col-md-4 pay-bank-field">
                                    <label for="bank_name" class="form-label fw-semibold mb-1">Bank name</label>
                                    <input type="text" class="form-control" id="bank_name" name="bank_name" maxlength="80"
                                           value="<?php echo htmlspecialchars($payment['bank_name'] ?? ''); ?>" placeholder="People's Bank">
                                </div>
                                <div class="col-12 col-md-4 pay-bank-field">
                                    <label for="bank_branch" class="form-label fw-semibold mb-1">Branch</label>
                                    <input type="text" class="form-control" id="bank_branch" name="bank_branch" maxlength="80"
                                           value="<?php echo htmlspecialchars($payment['bank_branch'] ?? ''); ?>" placeholder="Kilinochchi">
                                </div>
                                <div class="col-12 col-md-4">
                                    <label for="reference_no" class="form-label fw-semibold mb-1" id="referenceLabel">Receipt number</label>
                                    <input type="text" class="form-control" id="reference_no" name="reference_no" maxlength="64"
                                           value="<?php echo htmlspecialchars($payment['reference_no'] ?? ''); ?>">
                                    <div class="form-text" id="referenceHelp">The cash receipt number already on this payment.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-lg-3">
                        <label class="form-label fw-semibold mb-1">Approval Status</label>
                        <input type="hidden" name="approved" value="1">
                        <input type="text" class="form-control" value="Approved" readonly>
                    </div>

                    <div class="col-12">
                        <label for="payment_notes" class="form-label fw-semibold mb-1">Notes</label>
                        <textarea class="form-control" id="payment_notes" name="payment_notes"
                                  rows="3"><?php echo htmlspecialchars($payment['pays_note'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="payment-form-actions d-flex flex-wrap gap-2 mt-4 pt-3 border-top">
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

    var methodInput = document.getElementById('payment_method');
    var bankFields = document.querySelectorAll('.pay-bank-field');
    var referenceLabel = document.getElementById('referenceLabel');
    var referenceHelp = document.getElementById('referenceHelp');

    function showMethod(method) {
        methodInput.value = method;
        document.querySelectorAll('.pay-method').forEach(function (btn) {
            var on = btn.getAttribute('data-method') === method;
            btn.classList.toggle('btn-primary', on);
            btn.classList.toggle('btn-outline-primary', !on);
        });
        var isCash = method === 'CASH' || method === '';
        bankFields.forEach(function (field) {
            field.classList.toggle('d-none', isCash);
        });
        if (isCash) {
            referenceLabel.textContent = 'Cash receipt number';
            referenceHelp.textContent = 'Keep the daily number, or type a different cash receipt number.';
        } else if (method === 'ONLINE') {
            referenceLabel.textContent = 'Transfer reference';
            referenceHelp.textContent = 'The online transfer or deposit reference.';
        } else {
            referenceLabel.textContent = 'Bank slip number';
            referenceHelp.textContent = 'The slip or deposit number from the bank.';
        }
    }

    document.querySelectorAll('.pay-method').forEach(function (btn) {
        btn.addEventListener('click', function () {
            showMethod(btn.getAttribute('data-method') || 'CASH');
        });
    });
    showMethod(methodInput.value || 'CASH');
});
</script>
