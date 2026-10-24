<?php
$mainTypes = $mainTypes ?? [];
$entries = $entries ?? [];
$grouped = [];
foreach ($entries as $entry) {
    $grouped[$entry['payment_type']][] = $entry;
}
?>
<div class="container-fluid px-4 py-3">
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-primary text-white">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="mb-0 fw-bold"><i class="fas fa-tags me-2"></i>Payment Types</h5>
                <a href="<?php echo APP_URL; ?>/payments/create" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-cash-register me-1"></i>Payment Counter
                </a>
            </div>
        </div>
        <div class="card-body">
            <?php if (!empty($message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <p class="text-muted mb-0">Main types and sub types are managed separately. A sub type always belongs to one main type.</p>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold">Main types</h6>
                    <a href="<?php echo APP_URL; ?>/payment-types/create?kind=main" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus me-1"></i>Add main type
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($mainTypes)): ?>
                        <p class="text-muted p-3 mb-0">No main types yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Main type</th>
                                        <th class="text-end">Sub types</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($mainTypes as $main): ?>
                                        <?php $typeQuery = http_build_query(['kind' => 'main', 'type' => $main['payment_type']]); ?>
                                        <tr>
                                            <td class="fw-semibold"><?php echo htmlspecialchars($main['payment_type']); ?></td>
                                            <td class="text-end"><?php echo (int) $main['subtype_count']; ?></td>
                                            <td class="text-end">
                                                <a href="<?php echo APP_URL; ?>/payment-types/edit?<?php echo htmlspecialchars($typeQuery); ?>" class="btn btn-outline-primary btn-sm" title="Rename"><i class="fas fa-edit"></i></a>
                                                <a href="<?php echo APP_URL; ?>/payment-types/delete?<?php echo htmlspecialchars($typeQuery); ?>" class="btn btn-outline-danger btn-sm" title="Delete"><i class="fas fa-trash"></i></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold">Sub types</h6>
                    <a href="<?php echo APP_URL; ?>/payment-types/create?kind=sub" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus me-1"></i>Add sub type
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($grouped)): ?>
                        <p class="text-muted p-3 mb-0">No sub types yet. Add a main type first, then add sub types under it.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Main type</th>
                                        <th>Sub type</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($grouped as $typeName => $rows): ?>
                                        <?php foreach ($rows as $entry): ?>
                                            <?php $subQuery = http_build_query(['kind' => 'sub', 'reason' => $entry['payment_reason'], 'type' => $entry['payment_type']]); ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($typeName); ?></td>
                                                <td class="fw-semibold"><?php echo htmlspecialchars($entry['payment_reason']); ?></td>
                                                <td class="text-end">
                                                    <a href="<?php echo APP_URL; ?>/payment-types/edit?<?php echo htmlspecialchars($subQuery); ?>" class="btn btn-outline-primary btn-sm" title="Edit"><i class="fas fa-edit"></i></a>
                                                    <a href="<?php echo APP_URL; ?>/payment-types/delete?<?php echo htmlspecialchars($subQuery); ?>" class="btn btn-outline-danger btn-sm" title="Delete"><i class="fas fa-trash"></i></a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
