<?php
$kind = ($kind ?? 'sub') === 'main' ? 'main' : 'sub';
$editQuery = $kind === 'main'
    ? http_build_query(['kind' => 'main', 'type' => $entry['payment_type']])
    : http_build_query(['kind' => 'sub', 'reason' => $entry['payment_reason'], 'type' => $entry['payment_type']]);
?>
<div class="container-fluid px-4 py-3">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-edit me-2"></i><?php echo $kind === 'main' ? 'Rename Main Type' : 'Edit Sub Type'; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="<?php echo APP_URL; ?>/payment-types/edit?<?php echo htmlspecialchars($editQuery); ?>">
                        <?php if ($kind === 'main'): ?>
                            <div class="mb-4">
                                <label for="payment_type" class="form-label fw-semibold">Main type <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="payment_type" name="payment_type" maxlength="100" required
                                       value="<?php echo htmlspecialchars($entry['payment_type']); ?>">
                                <div class="form-text">Renaming also updates payments already collected under this main type.</div>
                            </div>
                        <?php else: ?>
                            <div class="mb-3">
                                <label for="payment_type" class="form-label fw-semibold">Main type <span class="text-danger">*</span></label>
                                <select class="form-select" id="payment_type" name="payment_type" required>
                                    <?php foreach (($paymentTypes ?? []) as $type): ?>
                                        <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $type === $entry['payment_type'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($type); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label for="payment_reason" class="form-label fw-semibold">Sub type <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="payment_reason" name="payment_reason" maxlength="50" required
                                       value="<?php echo htmlspecialchars($entry['payment_reason']); ?>">
                                <div class="form-text">Renaming also updates payments already collected with this sub type.</div>
                            </div>
                        <?php endif; ?>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Update</button>
                            <a href="<?php echo APP_URL; ?>/payment-types" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
