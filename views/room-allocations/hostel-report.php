<?php
$reportQuery = http_build_query(array_filter([
    'hostel_id' => $hostel_id ?? '',
    'gender' => $gender ?? '',
    'availability' => $availability ?? '',
    'search' => $search ?? ''
]));
$hasFilters = !empty($hostel_id) || !empty($gender) || !empty($availability) || !empty($search);
$totals = $totals ?? ['rooms' => 0, 'capacity' => 0, 'allocated' => 0, 'available' => 0];
?>
<div class="container-fluid px-4 py-3">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="mb-0 fw-bold"><i class="fas fa-chart-pie me-2"></i>Hostel Allocated &amp; Available Report</h5>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <a href="<?php echo APP_URL; ?>/hostel-report/export<?php echo $reportQuery !== '' ? '?' . $reportQuery : ''; ?>" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-file-excel me-1"></i>Download Report
                    </a>
                    <a href="<?php echo APP_URL; ?>/room-allocations" class="btn btn-light btn-sm">
                        <i class="fas fa-user-check me-1"></i>Room Allocations
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

            <div class="card border mb-4 shadow-sm">
                <div class="card-body">
                    <h6 class="mb-3 fw-bold text-primary"><i class="fas fa-filter me-2"></i>Filter Report</h6>
                    <form method="GET" action="<?php echo APP_URL; ?>/hostel-report" class="row g-3 align-items-end">
                        <div class="col-md-6 col-lg-3">
                            <label for="filter_hostel_id" class="form-label fw-bold small mb-1">Hostel</label>
                            <select name="hostel_id" id="filter_hostel_id" class="form-select form-select-sm">
                                <option value="">All Hostels</option>
                                <?php foreach ($hostels as $hostel): ?>
                                    <option value="<?php echo htmlspecialchars($hostel['id']); ?>"
                                            <?php echo ($hostel_id ?? '') == $hostel['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($hostel['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <label for="filter_gender" class="form-label fw-bold small mb-1">Gender</label>
                            <select name="gender" id="filter_gender" class="form-select form-select-sm">
                                <option value="">All Genders</option>
                                <?php foreach ($genders ?? [] as $g): ?>
                                    <option value="<?php echo htmlspecialchars($g); ?>"
                                            <?php echo ($gender ?? '') === $g ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($g); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <label for="filter_availability" class="form-label fw-bold small mb-1">Bed Status</label>
                            <select name="availability" id="filter_availability" class="form-select form-select-sm">
                                <option value="">All</option>
                                <option value="available" <?php echo ($availability ?? '') === 'available' ? 'selected' : ''; ?>>Available beds</option>
                                <option value="full" <?php echo ($availability ?? '') === 'full' ? 'selected' : ''; ?>>Fully allocated</option>
                                <option value="empty" <?php echo ($availability ?? '') === 'empty' ? 'selected' : ''; ?>>Empty rooms</option>
                            </select>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <label for="filter_search" class="form-label fw-bold small mb-1">Search</label>
                            <input type="text" name="search" id="filter_search" class="form-control form-control-sm"
                                   placeholder="Hostel, block, or room..."
                                   value="<?php echo htmlspecialchars($search ?? ''); ?>">
                        </div>
                        <div class="col-md-12 col-lg-2">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                                    <i class="fas fa-filter me-1"></i>Filter
                                </button>
                                <?php if ($hasFilters): ?>
                                    <a href="<?php echo APP_URL; ?>/hostel-report" class="btn btn-outline-secondary btn-sm" title="Clear">
                                        <i class="fas fa-times"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-6 col-lg-3">
                    <div class="border rounded p-3 h-100 bg-light">
                        <div class="text-muted small">Rooms</div>
                        <div class="fs-4 fw-bold"><?php echo number_format($totals['rooms']); ?></div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-muted small">Capacity</div>
                        <div class="fs-4 fw-bold"><?php echo number_format($totals['capacity']); ?></div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="border rounded p-3 h-100 bg-warning bg-opacity-10">
                        <div class="text-muted small">Allocated</div>
                        <div class="fs-4 fw-bold text-warning"><?php echo number_format($totals['allocated']); ?></div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="border rounded p-3 h-100 bg-success bg-opacity-10">
                        <div class="text-muted small">Available</div>
                        <div class="fs-4 fw-bold text-success"><?php echo number_format($totals['available']); ?></div>
                    </div>
                </div>
            </div>

            <?php if (!empty($hostelSummary)): ?>
                <h6 class="fw-bold mb-2"><i class="fas fa-building me-2"></i>Hostel Summary</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="fw-bold">Hostel</th>
                                <th class="fw-bold">Gender</th>
                                <th class="fw-bold">Location</th>
                                <th class="fw-bold text-end">Rooms</th>
                                <th class="fw-bold text-end">Capacity</th>
                                <th class="fw-bold text-end">Allocated</th>
                                <th class="fw-bold text-end">Available</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($hostelSummary as $hostelRow): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($hostelRow['hostel_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($hostelRow['hostel_gender'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($hostelRow['location'] ?? 'N/A'); ?></td>
                                    <td class="text-end"><?php echo number_format($hostelRow['rooms']); ?></td>
                                    <td class="text-end"><?php echo number_format($hostelRow['capacity']); ?></td>
                                    <td class="text-end text-warning fw-semibold"><?php echo number_format($hostelRow['allocated']); ?></td>
                                    <td class="text-end text-success fw-semibold"><?php echo number_format($hostelRow['available']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="fw-bold mb-0"><i class="fas fa-door-open me-2"></i>Room Detail</h6>
                <div class="text-muted small">
                    Showing <strong><?php echo number_format(count($rows ?? [])); ?></strong> rooms
                </div>
            </div>

            <?php if (!empty($rows)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="fw-bold">Hostel</th>
                                <th class="fw-bold">Gender</th>
                                <th class="fw-bold">Block</th>
                                <th class="fw-bold">Room</th>
                                <th class="fw-bold text-end">Capacity</th>
                                <th class="fw-bold text-end">Allocated</th>
                                <th class="fw-bold text-end">Available</th>
                                <th class="fw-bold">Occupancy</th>
                                <th class="fw-bold">Bed Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $row):
                                $capacity = (int) ($row['capacity'] ?? 0);
                                $allocated = (int) ($row['allocated'] ?? 0);
                                $available = (int) ($row['available'] ?? 0);
                                $percent = $capacity > 0 ? ($allocated / $capacity) * 100 : 0;
                                if ($allocated === 0) {
                                    $bedStatus = 'Empty';
                                    $bedClass = 'bg-secondary';
                                } elseif ($available <= 0) {
                                    $bedStatus = 'Full';
                                    $bedClass = 'bg-danger';
                                } else {
                                    $bedStatus = 'Available';
                                    $bedClass = 'bg-success';
                                }
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['hostel_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['hostel_gender'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['block_name'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge bg-info bg-opacity-10 text-info">
                                            <?php echo htmlspecialchars($row['room_no'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td class="text-end"><?php echo number_format($capacity); ?></td>
                                    <td class="text-end fw-semibold text-warning"><?php echo number_format($allocated); ?></td>
                                    <td class="text-end fw-semibold text-success"><?php echo number_format($available); ?></td>
                                    <td style="min-width: 120px;">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height: 8px;">
                                                <div class="progress-bar <?php echo $percent >= 100 ? 'bg-danger' : ($percent >= 80 ? 'bg-warning' : 'bg-success'); ?>"
                                                     role="progressbar"
                                                     style="width: <?php echo min($percent, 100); ?>%"></div>
                                            </div>
                                            <span class="small text-muted"><?php echo round($percent); ?>%</span>
                                        </div>
                                    </td>
                                    <td><span class="badge <?php echo $bedClass; ?>"><?php echo $bedStatus; ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-door-open fa-3x text-muted mb-3"></i>
                    <p class="text-muted mb-0">No rooms match the selected filters.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
