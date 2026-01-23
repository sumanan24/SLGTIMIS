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
                                   placeholder="Search by room number or type..." 
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
                    Showing <strong><?php echo count($rooms); ?></strong> of <strong><?php echo number_format($total); ?></strong> rooms
                </div>
            </div>

            <?php if (!empty($rooms)): ?>
                <div class="row g-4">
                    <?php foreach ($rooms as $room): 
                        $occupied = $room['occupied_beds'] ?? 0;
                        $capacity = $room['capacity'] ?? 0;
                        $available = $room['available_beds'] ?? 0;
                        $occupancyPercent = $capacity > 0 ? ($occupied / $capacity) * 100 : 0;
                        $statusClass = (($room['status'] ?? 'active') === 'active') ? 'success' : 'secondary';
                    ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card shadow-sm border-0 h-100">
                                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0 fw-bold">
                                            <i class="fas fa-door-open me-2"></i>
                                            Room <?php echo htmlspecialchars($room['room_no'] ?? 'N/A'); ?>
                                        </h6>
                                        <small class="opacity-75">
                                            <?php echo htmlspecialchars($room['block_name'] ?? 'N/A'); ?>
                                        </small>
                                    </div>
                                    <span class="badge bg-light text-dark">
                                        <?php echo htmlspecialchars($room['hostel_name'] ?? 'N/A'); ?>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="text-muted small">Capacity</span>
                                            <span class="fw-bold"><?php echo number_format($capacity); ?> beds</span>
                                        </div>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar 
                                                <?php echo $occupancyPercent >= 100 ? 'bg-danger' : ($occupancyPercent >= 80 ? 'bg-warning' : 'bg-success'); ?>" 
                                                role="progressbar" 
                                                style="width: <?php echo min($occupancyPercent, 100); ?>%"
                                                aria-valuenow="<?php echo $occupancyPercent; ?>" 
                                                aria-valuemin="0" 
                                                aria-valuemax="100">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row g-2 mb-3">
                                        <div class="col-6">
                                            <div class="text-center p-2 bg-warning bg-opacity-10 rounded">
                                                <div class="fw-bold text-warning"><?php echo number_format($occupied); ?></div>
                                                <small class="text-muted">Occupied</small>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-center p-2 bg-success bg-opacity-10 rounded">
                                                <div class="fw-bold text-success"><?php echo number_format($available); ?></div>
                                                <small class="text-muted">Available</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($room['room_type'])): ?>
                                        <div class="mb-2">
                                            <small class="text-muted">Type:</small>
                                            <span class="badge bg-info bg-opacity-10 text-info">
                                                <?php echo htmlspecialchars($room['room_type']); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="mb-2">
                                        <small class="text-muted">Status:</small>
                                        <span class="badge bg-<?php echo $statusClass; ?>">
                                            <?php echo htmlspecialchars(ucfirst($room['status'] ?? 'active')); ?>
                                        </span>
                                    </div>
                                    
                                    <?php if (!empty($room['description'])): ?>
                                        <div class="mt-2">
                                            <small class="text-muted"><?php echo htmlspecialchars(substr($room['description'], 0, 50)); ?><?php echo strlen($room['description']) > 50 ? '...' : ''; ?></small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer bg-transparent border-top">
                                    <?php if (isset($isAdminOrADM) && $isAdminOrADM): ?>
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="<?php echo APP_URL; ?>/rooms/edit?id=<?php echo urlencode($room['id'] ?? ''); ?>" 
                                           class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="fas fa-edit me-1"></i>Edit
                                        </a>
                                        <a href="<?php echo APP_URL; ?>/rooms/delete?id=<?php echo urlencode($room['id'] ?? ''); ?>" 
                                           class="btn btn-sm btn-outline-danger" title="Delete">
                                            <i class="fas fa-trash me-1"></i>Delete
                                        </a>
                                    </div>
                                    <?php else: ?>
                                    <div class="text-center">
                                        <small class="text-muted">View Only</small>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($currentPage > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $currentPage - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($hostel_id) ? '&hostel_id=' . urlencode($hostel_id) : ''; ?>">Previous</a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i == $currentPage ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($hostel_id) ? '&hostel_id=' . urlencode($hostel_id) : ''; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($currentPage < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $currentPage + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($hostel_id) ? '&hostel_id=' . urlencode($hostel_id) : ''; ?>">Next</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
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

