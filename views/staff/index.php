<div class="container-fluid px-4 py-3">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white">
            <div class="d-flex align-items-center justify-content-between flex-wrap">
                <h5 class="mb-0 fw-bold"><i class="fas fa-chalkboard-teacher me-2"></i>Staff Management</h5>
                <?php 
                // Use canManageStaff from controller if available, otherwise check
                if (!isset($canManageStaff)) {
                    $canManageStaff = false;
                    if (isset($_SESSION['user_id'])) {
                        require_once BASE_PATH . '/models/UserModel.php';
                        $userModelStaff = new UserModel();
                        $userRoleStaff = $userModelStaff->getUserRole($_SESSION['user_id']);
                        $isAdminStaff = $userModelStaff->isAdmin($_SESSION['user_id']);
                        $canManageStaff = in_array($userRoleStaff, ['ADM', 'MHF', 'REG']) || $isAdminStaff;
                    }
                }
                ?>
                <?php if ($canManageStaff): ?>
                <a href="<?php echo APP_URL; ?>/staff/create" class="btn btn-light btn-sm mt-2 mt-md-0">
                    <i class="fas fa-plus me-1"></i>Add New Staff
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
                    <form method="GET" action="<?php echo APP_URL; ?>/staff" class="row g-3">
                        <div class="col-md-10">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Search by name, ID, email, or NIC..." 
                                   value="<?php echo htmlspecialchars($search ?? ''); ?>">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-1"></i>Search
                            </button>
                        </div>
                        <?php if (!empty($search)): ?>
                            <div class="col-12">
                                <a href="<?php echo APP_URL; ?>/staff" class="btn btn-outline-secondary btn-sm">
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
                    Showing <strong><?php echo count($staff); ?></strong> of <strong><?php echo number_format($total); ?></strong> staff members
                </div>
            </div>

            <?php if (!empty($staff)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="fw-bold">Name</th>
                                <th class="fw-bold">Department</th>
                                <th class="fw-bold">Status</th>
                                <?php if ($canManageStaff): ?>
                                <th class="fw-bold text-end">Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($staff as $member): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($member['staff_name']); ?></td>
                                    <td>
                                        <span class="badge bg-info bg-opacity-10 text-info">
                                            <?php echo htmlspecialchars($member['department_name'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $member['staff_status'] === 'Working' ? 'success' : 'warning'; ?> rounded-pill px-3 py-2">
                                            <i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i>
                                            <?php echo htmlspecialchars($member['staff_status'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <?php if ($canManageStaff): ?>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="<?php echo APP_URL; ?>/staff/edit?id=<?php echo urlencode($member['staff_id']); ?>" class="btn btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?php echo APP_URL; ?>/staff/delete?id=<?php echo urlencode($member['staff_id']); ?>" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to delete this staff member?');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($currentPage > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $currentPage - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i == $currentPage ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($currentPage < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $currentPage + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-user-tie fa-3x text-muted mb-3"></i>
                    <p class="text-muted mb-3">No staff members found. <?php if (!empty($search)): ?>Try a different search term.<?php endif; ?></p>
                    <?php if (empty($search) && $canManageStaff): ?>
                        <a href="<?php echo APP_URL; ?>/staff/create" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>Create one now
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

