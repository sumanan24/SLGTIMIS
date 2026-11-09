<div class="container-fluid px-4 py-3">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-trash me-2"></i>Delete Hostel</h5>
                </div>
                <div class="card-body">
                    <?php $blocked = !empty($allocations['active']); ?>
                    <?php if ($blocked): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-ban me-2"></i>
                            <strong>Cannot delete:</strong> <?php echo (int) $allocations['active']; ?> student(s) are currently allocated to rooms in this hostel. Move or release them first, or edit the hostel and set it to <em>Inactive</em>.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Warning:</strong> This action cannot be undone. The hostel and all of its
                            <?php echo (int) ($hostel['flat_count'] ?? 0); ?> flat(s) and <?php echo (int) ($hostel['room_count'] ?? 0); ?> room(s) will be deleted<?php echo !empty($allocations['total']) ? ', along with ' . (int) $allocations['total'] . ' past allocation record(s)' : ''; ?>.
                        </div>
                    <?php endif; ?>
                    
                    <p>Are you sure you want to delete the following hostel?</p>
                    
                    <table class="table table-bordered">
                        <tr>
                            <th>Name:</th>
                            <td><?php echo htmlspecialchars($hostel['name']); ?></td>
                        </tr>
                        <tr>
                            <th>Address:</th>
                            <td><?php echo htmlspecialchars($hostel['address'] ?? ''); ?></td>
                        </tr>
                        <tr>
                            <th>Gender:</th>
                            <td><?php echo htmlspecialchars($hostel['gender']); ?></td>
                        </tr>
                    </table>
                    
                    <?php if (!empty($rooms)): ?>
                        <div class="d-flex justify-content-between align-items-center mt-4 mb-2">
                            <h6 class="fw-bold mb-0"><i class="fas fa-door-open me-2"></i>Rooms information</h6>
                            <span class="small text-muted">
                                <?php echo count($flats ?? []); ?> flat(s) &middot; <?php echo count($rooms); ?> room(s) &middot;
                                <?php echo number_format($hostel['capacity'] ?? 0); ?> beds &middot;
                                <strong><?php echo number_format($hostel['occupied'] ?? 0); ?> occupied</strong>
                            </span>
                        </div>
                        <div class="table-responsive" style="max-height:320px; overflow-y:auto;">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light" style="position:sticky; top:0;">
                                    <tr>
                                        <th>Flat</th>
                                        <th>Room No.</th>
                                        <th class="text-end">Capacity</th>
                                        <th class="text-end">Occupied</th>
                                        <th class="text-end">Available</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rooms as $room):
                                        $cap = (int) ($room['capacity'] ?? 0);
                                        $occ = (int) ($room['occupied_beds'] ?? 0);
                                    ?>
                                        <tr class="<?php echo $occ > 0 ? '' : 'text-muted'; ?>">
                                            <td><?php echo htmlspecialchars($room['block_name'] ?? ''); ?></td>
                                            <td class="fw-semibold"><?php echo htmlspecialchars($room['room_no'] ?? ''); ?></td>
                                            <td class="text-end"><?php echo $cap; ?></td>
                                            <td class="text-end"><?php echo $occ; ?></td>
                                            <td class="text-end"><?php echo max(0, $cap - $occ); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?php echo APP_URL; ?>/hostels/delete?id=<?php echo urlencode($hostel['id']); ?>">
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="<?php echo APP_URL; ?>/hostels" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                            <a href="<?php echo APP_URL; ?>/rooms?hostel_id=<?php echo urlencode($hostel['id']); ?>" class="btn btn-outline-info">
                                <i class="fas fa-door-open me-2"></i>View Rooms
                            </a>
                            <button type="submit" class="btn btn-danger" <?php echo $blocked ? 'disabled' : ''; ?>>
                                <i class="fas fa-trash me-2"></i>Delete Hostel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

