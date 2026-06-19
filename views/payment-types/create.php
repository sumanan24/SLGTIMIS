<div class="container-fluid px-4 py-3">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-plus-circle me-2"></i>Add Payment Type & Reason</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <div><?php echo htmlspecialchars($error); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?php echo APP_URL; ?>/payment-types/create">
                        <div class="mb-3">
                            <label for="payment_type" class="form-label fw-semibold">
                                Payment Type <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="payment_type" name="payment_type"
                                   list="payment_type_list" maxlength="100" required
                                   placeholder="e.g., Student Charges, Exam, Accessories">
                            <datalist id="payment_type_list">
                                <?php if (!empty($paymentTypes)): ?>
                                    <?php foreach ($paymentTypes as $type): ?>
                                        <option value="<?php echo htmlspecialchars($type); ?>">
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </datalist>
                            <div class="form-text">Maximum 100 characters. Choose an existing type or enter a new one.</div>
                        </div>

                        <div class="mb-4">
                            <label for="payment_reason" class="form-label fw-semibold">
                                Payment Reason <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="payment_reason" name="payment_reason"
                                   maxlength="50" required placeholder="e.g., Hostel Charges, Library Charges">
                            <div class="form-text">Maximum 50 characters.</div>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Save
                            </button>
                            <a href="<?php echo APP_URL; ?>/payment-types" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
