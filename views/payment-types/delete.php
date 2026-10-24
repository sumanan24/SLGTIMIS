<?php
$kind = ($kind ?? 'sub') === 'main' ? 'main' : 'sub';
$deleteQuery = $kind === 'main'
    ? http_build_query(['kind' => 'main', 'type' => $entry['payment_type']])
    : http_build_query(['kind' => 'sub', 'reason' => $entry['payment_reason'], 'type' => $entry['payment_type']]);
$subtypeCount = (int) ($subtypeCount ?? 0);
?>
<div class="container-fluid px-4 py-3">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-trash me-2"></i><?php echo $kind === 'main' ? 'Delete Main Type' : 'Delete Sub Type'; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($isUsed)): ?>
                        <div class="alert alert-warning">
                            <?php if ($kind === 'main' && $subtypeCount > 0): ?>
                                This main type still has <?php echo $subtypeCount; ?> sub type<?php echo $subtypeCount === 1 ? '' : 's'; ?>. Remove those sub types before deleting the main type.
                            <?php else: ?>
                                This cannot be deleted because payments already use it.
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">This cannot be undone.</div>
                    <?php endif; ?>

                    <table class="table table-bordered mb-4">
                        <tbody>
                            <tr>
                                <th class="bg-light" style="width: 200px;">Main type</th>
                                <td><?php echo htmlspecialchars($entry['payment_type']); ?></td>
                            </tr>
                            <?php if ($kind === 'sub'): ?>
                            <tr>
                                <th class="bg-light">Sub type</th>
                                <td><?php echo htmlspecialchars($entry['payment_reason']); ?></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <?php if (empty($isUsed)): ?>
                        <form method="POST" action="<?php echo APP_URL; ?>/payment-types/delete?<?php echo htmlspecialchars($deleteQuery); ?>">
                            <button type="submit" class="btn btn-danger"><i class="fas fa-trash me-1"></i>Yes, delete</button>
                            <a href="<?php echo APP_URL; ?>/payment-types" class="btn btn-outline-secondary">Cancel</a>
                        </form>
                    <?php else: ?>
                        <a href="<?php echo APP_URL; ?>/payment-types" class="btn btn-outline-secondary">Back</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
