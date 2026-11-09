<div class="container-fluid px-4 py-3">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white">
            <div class="d-flex align-items-center justify-content-between flex-wrap">
                <h5 class="mb-0 fw-bold"><i class="fas fa-door-open me-2"></i>Room Management</h5>
                <a href="<?php echo APP_URL; ?>/rooms/create" class="btn btn-light btn-sm mt-2 mt-md-0">
                    <i class="fas fa-plus me-1"></i>Add New Room
                </a>
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
            
            <!-- Filters -->
            <div class="card border mb-4 shadow-sm">
                <div class="card-body">
                    <form method="GET" action="<?php echo APP_URL; ?>/rooms" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Hostel</label>
                            <select name="hostel_id" class="form-select">
                                <option value="">All Hostels</option>
                                <?php foreach ($hostels as $hostel): ?>
                                    <option value="<?php echo htmlspecialchars($hostel['id']); ?>" 
                                            <?php echo ($hostel_id ?? '') == $hostel['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($hostel['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Search</label>
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Search by room number..." 
                                   value="<?php echo htmlspecialchars($search ?? ''); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-1"></i>Search
                            </button>
                        </div>
                        <?php if (!empty($search) || !empty($hostel_id)): ?>
                            <div class="col-12">
                                <a href="<?php echo APP_URL; ?>/rooms" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-times me-1"></i>Clear Filters
                                </a>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="text-muted small">
                    <i class="fas fa-info-circle me-1"></i>
                    <strong><?php echo number_format($total); ?></strong> rooms in <strong><?php echo count($hostelGroups); ?></strong> hostel(s)
                </div>
            </div>

            <?php if (!empty($rooms)): ?>
                <?php foreach ($hostelGroups as $group):
                    $gCap = (int) $group['capacity'];
                    $gOcc = (int) $group['occupied'];
                    $gAvail = max(0, $gCap - $gOcc);
                ?>
                    <div class="card border mb-4 shadow-sm">
                        <div class="card-header bg-light d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <h6 class="mb-0 fw-bold text-primary">
                                <i class="fas fa-building me-2"></i><?php echo htmlspecialchars($group['name']); ?>
                            </h6>
                            <div class="small text-muted">
                                <span class="me-3">Flats: <strong><?php echo count($group['flats']); ?></strong></span>
                                <span class="me-3">Rooms: <strong><?php echo count($group['rooms']); ?></strong></span>
                                <span class="me-3">Capacity: <strong><?php echo number_format($gCap); ?></strong></span>
                                <span class="me-3">Occupied: <strong><?php echo number_format($gOcc); ?></strong></span>
                                <span>Available: <strong class="<?php echo $gAvail > 0 ? 'text-success' : 'text-danger'; ?>"><?php echo number_format($gAvail); ?></strong></span>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:50px">#</th>
                                        <th>Flat</th>
                                        <th>Room No.</th>
                                        <th class="text-end">Capacity</th>
                                        <th class="text-end">Occupied</th>
                                        <th class="text-end">Available</th>
                                        <th style="min-width:130px">Occupancy</th>
                                        <?php if (!empty($isAdminOrADM)): ?><th class="text-end">Actions</th><?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($group['rooms'] as $i => $room):
                                        $capacity = (int) ($room['capacity'] ?? 0);
                                        $occupied = (int) ($room['occupied_beds'] ?? 0);
                                        $available = max(0, $capacity - $occupied);
                                        $pct = $capacity > 0 ? min(100, ($occupied / $capacity) * 100) : 0;
                                        $barClass = $pct >= 100 ? 'bg-danger' : ($pct >= 80 ? 'bg-warning' : 'bg-success');
                                    ?>
                                        <tr>
                                            <td class="text-muted"><?php echo $i + 1; ?></td>
                                            <td><?php echo htmlspecialchars($room['block_name'] ?? 'N/A'); ?></td>
                                            <td class="fw-semibold"><?php echo htmlspecialchars($room['room_no'] ?? 'N/A'); ?></td>
                                            <td class="text-end"><?php echo number_format($capacity); ?></td>
                                            <td class="text-end"><?php echo number_format($occupied); ?></td>
                                            <td class="text-end">
                                                <span class="badge <?php echo $available > 0 ? 'bg-success text-success' : 'bg-danger text-danger'; ?> bg-opacity-10">
                                                    <?php echo number_format($available); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="progress flex-grow-1" style="height:8px;">
                                                        <div class="progress-bar <?php echo $barClass; ?>" role="progressbar" style="width: <?php echo round($pct); ?>%"></div>
                                                    </div>
                                                    <small class="text-muted"><?php echo round($pct); ?>%</small>
                                                </div>
                                            </td>
                                            <?php if (!empty($isAdminOrADM)): ?>
                                                <td class="text-end text-nowrap">
                                                    <a href="<?php echo APP_URL; ?>/rooms/edit?id=<?php echo urlencode($room['id'] ?? ''); ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="<?php echo APP_URL; ?>/rooms/delete?id=<?php echo urlencode($room['id'] ?? ''); ?>" class="btn btn-sm btn-outline-danger" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-light fw-semibold">
                                    <tr>
                                        <td colspan="3" class="text-end">Total</td>
                                        <td class="text-end"><?php echo number_format($gCap); ?></td>
                                        <td class="text-end"><?php echo number_format($gOcc); ?></td>
                                        <td class="text-end"><?php echo number_format($gAvail); ?></td>
                                        <td colspan="<?php echo !empty($isAdminOrADM) ? 2 : 1; ?>"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-door-open fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No rooms found.</p>
                    <?php if (isset($isAdminOrADM) && $isAdminOrADM): ?>
                    <a href="<?php echo APP_URL; ?>/rooms/create" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Add First Room
                    </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

