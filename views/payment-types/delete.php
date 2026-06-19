<?php
$deleteQuery = http_build_query([
    'reason' => $entry['payment_reason'],
    'type' => $entry['payment_type'],
]);
?>
<div class="container-fluid px-4 py-3">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-trash me-2"></i>Delete Payment Type & Reason</h5>
                </div>
                <div class="card-body">
                    <?php if ($isUsed): ?>
                        <div class="alert alert-warning d-flex align-items-center" role="alert">
                            <i class="fas fa-exclamation-triangle me-2 fa-lg"></i>
                            <div>
                                <strong>Cannot delete.</strong> This entry is used in existing payment records.
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning d-flex align-items-center" role="alert">
                            <i class="fas fa-exclamation-triangle me-2 fa-lg"></i>
                            <div>
                                <strong>Warning!</strong> Are you sure you want to delete this entry? This action cannot be undone.
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="table-responsive mb-4">
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <th class="bg-light" style="width: 200px;">Payment Type:</th>
                                    <td><?php echo htmlspecialchars($entry['payment_type']); ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Payment Reason:</th>
                                    <td><?php echo htmlspecialchars($entry['payment_reason']); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <?php if (!$isUsed): ?>
                        <form method="POST" action="<?php echo APP_URL; ?>/payment-types/delete?<?php echo htmlspecialchars($deleteQuery); ?>">
                            <div class="d-flex flex-wrap gap-2">
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-trash me-1"></i>Yes, Delete
                                </button>
                                <a href="<?php echo APP_URL; ?>/payment-types" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i>Cancel
                                </a>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="<?php echo APP_URL; ?>/payment-types" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Back
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
