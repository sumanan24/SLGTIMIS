<?php $kind = ($kind ?? 'sub') === 'main' ? 'main' : 'sub'; ?>
<div class="container-fluid px-4 py-3">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-plus-circle me-2"></i><?php echo $kind === 'main' ? 'Add Main Type' : 'Add Sub Type'; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="<?php echo APP_URL; ?>/payment-types/create?kind=<?php echo $kind; ?>">
                        <input type="hidden" name="kind" value="<?php echo $kind; ?>">
                        <?php if ($kind === 'main'): ?>
                            <div class="mb-4">
                                <label for="payment_type" class="form-label fw-semibold">Main type <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="payment_type" name="payment_type" maxlength="100" required placeholder="e.g. Student Charges, Exam">
                                <div class="form-text">Maximum 100 characters. Sub types are added separately.</div>
                            </div>
                        <?php else: ?>
                            <div class="mb-3">
                                <label for="payment_type" class="form-label fw-semibold">Main type <span class="text-danger">*</span></label>
                                <select class="form-select" id="payment_type" name="payment_type" required>
                                    <option value="">Select a main type</option>
                                    <?php foreach (($paymentTypes ?? []) as $type): ?>
                                        <option value="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($type); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (empty($paymentTypes)): ?>
                                    <div class="form-text text-warning">Add a main type before adding a sub type.</div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-4">
                                <label for="payment_reason" class="form-label fw-semibold">Sub type <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="payment_reason" name="payment_reason" maxlength="50" required placeholder="e.g. Hostel Charges, 1st Installment">
                                <div class="form-text">Maximum 50 characters.</div>
                            </div>
                        <?php endif; ?>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary" <?php echo ($kind === 'sub' && empty($paymentTypes)) ? 'disabled' : ''; ?>>
                                <i class="fas fa-save me-1"></i>Save
                            </button>
                            <a href="<?php echo APP_URL; ?>/payment-types" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
