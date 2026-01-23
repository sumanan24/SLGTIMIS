<div class="container-fluid px-3 px-md-4 py-3 py-md-4">
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-danger text-white">
            <h4 class="mb-0"><i class="fas fa-lock me-2"></i>Locked Accounts</h4>
        </div>
        <div class="card-body">
            <?php if (isset($message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($lockedCount)): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong><?php echo $lockedCount; ?></strong> account(s) are currently locked.
                </div>
            <?php endif; ?>
            
            <!-- Filters Section -->
            <div class="card mb-4 border-0" style="background-color: #f8f9fa;">
                <div class="card-body">
                    <h5 class="card-title mb-3"><i class="fas fa-filter me-2"></i>Filters</h5>
                    <form method="GET" action="<?php echo APP_URL; ?>/admin/locked-accounts" id="filterForm">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" class="form-control" id="search" name="search" 
                                       value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>" 
                                       placeholder="Username, Email, or ID">
                            </div>
                            
                            <div class="col-md-2">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">All</option>
                                    <option value="1" <?php echo (isset($filters['status']) && $filters['status'] == '1') ? 'selected' : ''; ?>>Active</option>
                                    <option value="0" <?php echo (isset($filters['status']) && $filters['status'] == '0') ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <label for="has_login" class="form-label">Has Logged In</label>
                                <select class="form-select" id="has_login" name="has_login">
                                    <option value="">All</option>
                                    <option value="yes" <?php echo (isset($filters['has_login']) && $filters['has_login'] == 'yes') ? 'selected' : ''; ?>>Yes</option>
                                    <option value="no" <?php echo (isset($filters['has_login']) && $filters['has_login'] == 'no') ? 'selected' : ''; ?>>No</option>
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <label for="order_by" class="form-label">Sort By</label>
                                <select class="form-select" id="order_by" name="order_by">
                                    <option value="locked_at" <?php echo (isset($filters['order_by']) && $filters['order_by'] == 'locked_at') ? 'selected' : ''; ?>>Locked At</option>
                                    <option value="user_name" <?php echo (isset($filters['order_by']) && $filters['order_by'] == 'user_name') ? 'selected' : ''; ?>>Username</option>
                                    <option value="user_id" <?php echo (isset($filters['order_by']) && $filters['order_by'] == 'user_id') ? 'selected' : ''; ?>>User ID</option>
                                    <option value="user_last_login_timestamp" <?php echo (isset($filters['order_by']) && $filters['order_by'] == 'user_last_login_timestamp') ? 'selected' : ''; ?>>Last Login</option>
                                </select>
                            </div>
                            
                            <div class="col-md-1">
                                <label for="order_dir" class="form-label">Order</label>
                                <select class="form-select" id="order_dir" name="order_dir">
                                    <option value="DESC" <?php echo (isset($filters['order_dir']) && $filters['order_dir'] == 'DESC') ? 'selected' : ''; ?>>DESC</option>
                                    <option value="ASC" <?php echo (isset($filters['order_dir']) && $filters['order_dir'] == 'ASC') ? 'selected' : ''; ?>>ASC</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-1"></i>Apply Filters
                                </button>
                                <a href="<?php echo APP_URL; ?>/admin/locked-accounts" class="btn btn-secondary">
                                    <i class="fas fa-times me-1"></i>Clear
                                </a>
                                <?php if (!empty($users)): ?>
                                    <span class="badge bg-info ms-2">
                                        <?php echo count($users); ?> locked account(s)
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>User ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Lock Reason</th>
                            <th>Locked At</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">No locked accounts found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($user['user_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($user['user_email'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php if ($user['user_active'] == 1): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['lock_reason'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php if (!empty($user['locked_at'])): ?>
                                            <?php echo date('Y-m-d H:i:s', strtotime($user['locked_at'])); ?>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($user['user_last_login_timestamp'])): ?>
                                            <?php echo date('Y-m-d H:i:s', $user['user_last_login_timestamp']); ?>
                                        <?php else: ?>
                                            Never
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-success unlock-btn" data-user-id="<?php echo $user['user_id']; ?>" data-username="<?php echo htmlspecialchars($user['user_name']); ?>">
                                            <i class="fas fa-unlock me-1"></i>Unlock
                                        </button>
                                        <button class="btn btn-sm btn-primary reset-password-btn" data-user-id="<?php echo $user['user_id']; ?>" data-username="<?php echo htmlspecialchars($user['user_name']); ?>">
                                            <i class="fas fa-key me-1"></i>Reset Password
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Unlock Account Modal -->
<div class="modal fade" id="unlockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Unlock Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to unlock the account for <strong id="unlockUsername"></strong>?</p>
                <p class="text-muted small">This will allow the user to log in again.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirmUnlock">Unlock Account</button>
            </div>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reset Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="resetPasswordForm">
                <div class="modal-body">
                    <p>Reset password for user: <strong id="resetPasswordUsername"></strong></p>
                    <div class="mb-3">
                        <label for="newPassword" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="newPassword" name="new_password" required minlength="6" placeholder="Enter new password (minimum 6 characters)">
                        <div class="form-text">Password must be at least 6 characters long.</div>
                    </div>
                    <div class="mb-3">
                        <label for="confirmPassword" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirmPassword" name="confirm_password" required minlength="6" placeholder="Confirm new password">
                    </div>
                    <input type="hidden" id="resetUserId" name="user_id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentUserId = null;
    
    // Unlock Account
    const unlockModal = new bootstrap.Modal(document.getElementById('unlockModal'));
    document.querySelectorAll('.unlock-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            currentUserId = this.dataset.userId;
            document.getElementById('unlockUsername').textContent = this.dataset.username;
            unlockModal.show();
        });
    });
    
    document.getElementById('confirmUnlock').addEventListener('click', function() {
        if (!currentUserId) return;
        
        fetch('<?php echo APP_URL; ?>/admin/unlock-account', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'user_id=' + encodeURIComponent(currentUserId)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Account unlocked successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while unlocking the account.');
        });
    });
    
    // Reset Password
    const resetPasswordModal = new bootstrap.Modal(document.getElementById('resetPasswordModal'));
    document.querySelectorAll('.reset-password-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            currentUserId = this.dataset.userId;
            document.getElementById('resetUserId').value = currentUserId;
            document.getElementById('resetPasswordUsername').textContent = this.dataset.username;
            document.getElementById('resetPasswordForm').reset();
            resetPasswordModal.show();
        });
    });
    
    document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        
        if (newPassword !== confirmPassword) {
            alert('Passwords do not match!');
            return;
        }
        
        if (newPassword.length < 6) {
            alert('Password must be at least 6 characters long!');
            return;
        }
        
        fetch('<?php echo APP_URL; ?>/admin/reset-password', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'user_id=' + encodeURIComponent(currentUserId) + '&new_password=' + encodeURIComponent(newPassword)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Password reset successfully!');
                resetPasswordModal.hide();
                document.getElementById('resetPasswordForm').reset();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while resetting the password.');
        });
    });
});
</script>

