<div class="container-fluid px-3 px-md-4 py-3 py-md-4">
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0"><i class="fas fa-list-alt me-2"></i>Activity Logs</h4>
        </div>
        <div class="card-body">
            <!-- Filters -->
            <form method="GET" action="<?php echo APP_URL; ?>/admin/activity-logs" class="mb-4">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($filters['username'] ?? ''); ?>" placeholder="Search username">
                    </div>
                    <div class="col-md-3">
                        <label for="activity_type" class="form-label">Activity Type</label>
                        <select class="form-select" id="activity_type" name="activity_type">
                            <option value="">All Types</option>
                            <?php foreach ($activityTypes as $type): ?>
                                <option value="<?php echo htmlspecialchars($type); ?>" <?php echo (isset($filters['activity_type']) && $filters['activity_type'] === $type) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Status</option>
                            <option value="success" <?php echo (isset($filters['status']) && $filters['status'] === 'success') ? 'selected' : ''; ?>>Success</option>
                            <option value="failed" <?php echo (isset($filters['status']) && $filters['status'] === 'failed') ? 'selected' : ''; ?>>Failed</option>
                            <option value="error" <?php echo (isset($filters['status']) && $filters['status'] === 'error') ? 'selected' : ''; ?>>Error</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="date_from" class="form-label">From Date</label>
                        <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo htmlspecialchars($filters['date_from'] ?? ''); ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="date_to" class="form-label">To Date</label>
                        <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo htmlspecialchars($filters['date_to'] ?? ''); ?>">
                    </div>
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i>Filter
                        </button>
                        <a href="<?php echo APP_URL; ?>/admin/activity-logs" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i>Clear
                        </a>
                    </div>
                </div>
            </form>
            
            <!-- Results Count -->
            <div class="mb-3">
                <strong>Total Logs:</strong> <?php echo number_format($totalLogs); ?>
            </div>
            
            <!-- Activity Logs Table -->
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Timestamp</th>
                            <th>User ID</th>
                            <th>Username</th>
                            <th>Activity Type</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>IP Address</th>
                            <th>Request URL</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted">No activity logs found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($log['log_id']); ?></td>
                                    <td><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($log['user_id'] ?? 'N/A'); ?></td>
                                    <td><strong><?php echo htmlspecialchars($log['username'] ?? 'N/A'); ?></strong></td>
                                    <td>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($log['activity_type']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($log['activity_description'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php
                                        $statusClass = 'bg-secondary';
                                        if ($log['status'] === 'success') $statusClass = 'bg-success';
                                        elseif ($log['status'] === 'failed') $statusClass = 'bg-danger';
                                        elseif ($log['status'] === 'error') $statusClass = 'bg-warning';
                                        ?>
                                        <span class="badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($log['status']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></td>
                                    <td class="text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($log['request_url'] ?? 'N/A'); ?>">
                                        <?php echo htmlspecialchars($log['request_url'] ?? 'N/A'); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Activity logs pagination">
                    <ul class="pagination justify-content-center">
                        <?php if ($currentPage > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $currentPage - 1; ?><?php echo !empty($filters) ? '&' . http_build_query($filters) : ''; ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                            <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($filters) ? '&' . http_build_query($filters) : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($currentPage < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $currentPage + 1; ?><?php echo !empty($filters) ? '&' . http_build_query($filters) : ''; ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>


