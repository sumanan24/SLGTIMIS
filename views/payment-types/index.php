<div class="container-fluid px-4 py-3">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white">
            <div class="d-flex align-items-center justify-content-between flex-wrap">
                <h5 class="mb-0 fw-bold"><i class="fas fa-tags me-2"></i>Payment Types & Reasons</h5>
                <div class="d-flex flex-wrap gap-2 mt-2 mt-md-0">
                    <a href="<?php echo APP_URL; ?>/payment-types/create" class="btn btn-light btn-sm">
                        <i class="fas fa-plus me-1"></i>Add New
                    </a>
                    <a href="<?php echo APP_URL; ?>/payments/create" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-money-bill-wave me-1"></i>Create Payment
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <?php if (isset($message)): ?>
                <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <div><?php echo htmlspecialchars($message); ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <div><?php echo htmlspecialchars($error); ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <p class="text-muted mb-4">
                Manage payment types and reasons used on the Create Payment form. Each row is a type + reason pair.
            </p>

            <?php if (!empty($entries)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="fw-bold">Payment Type</th>
                                <th class="fw-bold">Payment Reason</th>
                                <th class="fw-bold text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($entries as $entry): ?>
                                <?php
                                $editQuery = http_build_query([
                                    'reason' => $entry['payment_reason'],
                                    'type' => $entry['payment_type'],
                                ]);
                                ?>
                                <tr>
                                    <td><span class="badge bg-primary bg-opacity-10 text-primary"><?php echo htmlspecialchars($entry['payment_type']); ?></span></td>
                                    <td><?php echo htmlspecialchars($entry['payment_reason']); ?></td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="<?php echo APP_URL; ?>/payment-types/edit?<?php echo htmlspecialchars($editQuery); ?>" class="btn btn-outline-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?php echo APP_URL; ?>/payment-types/delete?<?php echo htmlspecialchars($editQuery); ?>" class="btn btn-outline-danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                    <p class="text-muted mb-3">No payment types or reasons found.</p>
                    <a href="<?php echo APP_URL; ?>/payment-types/create" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Add payment type & reason
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
