<div class="container-fluid px-4 py-3">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="mb-0 fw-bold"><i class="fas fa-users me-2"></i>Student Groups</h5>
                <a href="<?php echo APP_URL; ?>/groups/create" class="btn btn-light btn-sm mt-2 mt-md-0">
                    <i class="fas fa-plus me-1"></i>Create New Group
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
            
            <?php if (!empty($groups)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="fw-bold">Group Name</th>
                                <th class="fw-bold">Course</th>
                                <th class="fw-bold">Department</th>
                                <th class="fw-bold">Academic Year</th>
                                <th class="fw-bold text-center">Students</th>
                                <th class="fw-bold">Status</th>
                                <th class="fw-bold text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($groups as $group): ?>
                                <tr>
                                    <td>
                                        <span class="fw-semibold"><?php echo htmlspecialchars($group['name']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($group['course_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($group['department_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($group['academic_year'] ?? 'N/A'); ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-info rounded-pill px-3">
                                            <?php echo htmlspecialchars($group['student_count'] ?? 0); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo ($group['status'] === 'active') ? 'success' : 'secondary'; ?> rounded-pill px-3">
                                            <?php echo htmlspecialchars(ucfirst($group['status'] ?? 'active')); ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group" role="group">
                                            <a href="<?php echo APP_URL; ?>/groups/show?id=<?php echo urlencode($group['id']); ?>" 
                                               class="btn btn-outline-primary btn-sm" 
                                               title="View Group">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?php echo APP_URL; ?>/groups/edit?id=<?php echo urlencode($group['id']); ?>" 
                                               class="btn btn-outline-primary btn-sm" 
                                               title="Edit Group">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?php echo APP_URL; ?>/groups/delete?id=<?php echo urlencode($group['id']); ?>" 
                                               class="btn btn-outline-danger btn-sm" 
                                               onclick="return confirm('Are you sure you want to delete this group? This will also remove all students from the group.');"
                                               title="Delete Group">
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
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <p class="text-muted mb-3">No groups found.</p>
                    <a href="<?php echo APP_URL; ?>/groups/create" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Create one now
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

