<div class="container-fluid px-4 py-3">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white">
            <div class="d-flex align-items-center justify-content-between flex-wrap">
                <h5 class="mb-0 fw-bold"><i class="fas fa-building me-2"></i>Hostel Management</h5>
                <?php if (isset($isAdminOrADM) && $isAdminOrADM): ?>
                <a href="<?php echo APP_URL; ?>/hostels/create" class="btn btn-light btn-sm mt-2 mt-md-0">
                    <i class="fas fa-plus me-1"></i>Add New Hostel
                </a>
                <?php endif; ?>
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
            
            <!-- Search Box -->
            <div class="card border mb-4 shadow-sm">
                <div class="card-body">
                    <form method="GET" action="<?php echo APP_URL; ?>/hostels" class="row g-3">
                        <div class="col-md-10">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Search by name, location, or gender..." 
                                   value="<?php echo htmlspecialchars($search ?? ''); ?>">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-1"></i>Search
                            </button>
                        </div>
                        <?php if (!empty($search)): ?>
                            <div class="col-12">
                                <a href="<?php echo APP_URL; ?>/hostels" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-times me-1"></i>Clear Search
                                </a>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="text-muted small">
                    <i class="fas fa-info-circle me-1"></i>
                    Showing <strong><?php echo count($hostels); ?></strong> of <strong><?php echo number_format($total); ?></strong> hostels
                </div>
            </div>

            <?php if (!empty($hostels)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="fw-bold">Name</th>
                                <th class="fw-bold">Location</th>
                                <th class="fw-bold">Gender</th>
                                <th class="fw-bold">Capacity</th>
                                <th class="fw-bold">Status</th>
                                <th class="fw-bold text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($hostels as $hostel): ?>
                                <tr>
                                    <td><span class="fw-semibold text-primary"><?php echo htmlspecialchars($hostel['name'] ?? 'N/A'); ?></span></td>
                                    <td><?php echo htmlspecialchars($hostel['location'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge bg-info bg-opacity-10 text-info">
                                            <?php echo htmlspecialchars($hostel['gender'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo number_format($hostel['capacity'] ?? 0); ?></td>
                                    <td>
                                        <span class="badge <?php echo (($hostel['status'] ?? 'active') === 'active') ? 'bg-success' : 'bg-secondary'; ?>">
                                            <?php echo htmlspecialchars(ucfirst($hostel['status'] ?? 'active')); ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <?php if (isset($isAdminOrADM) && $isAdminOrADM): ?>
                                        <div class="btn-group" role="group">
                                            <a href="<?php echo APP_URL; ?>/hostels/edit?id=<?php echo urlencode($hostel['id'] ?? ''); ?>" 
                                               class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?php echo APP_URL; ?>/hostels/delete?id=<?php echo urlencode($hostel['id'] ?? ''); ?>" 
                                               class="btn btn-sm btn-outline-danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                        <?php else: ?>
                                        <span class="text-muted small">View Only</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($currentPage > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $currentPage - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Previous</a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i == $currentPage ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($currentPage < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $currentPage + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Next</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-building fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No hostels found.</p>
                    <?php if (isset($isAdminOrADM) && $isAdminOrADM): ?>
                    <a href="<?php echo APP_URL; ?>/hostels/create" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Add First Hostel
                    </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

