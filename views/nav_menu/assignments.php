<?php
/** @var array $assignments */
/** @var string $filterStatus */
$h = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
unset($_SESSION['message'], $_SESSION['error']);
?>
<div class="container-fluid px-4 py-3">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="mb-0 fw-bold"><i class="fas fa-users me-2"></i>Staff Navbar Assignments</h5>
            <a href="<?php echo APP_URL; ?>/admin/nav-menu" class="btn btn-light btn-sm">Back to menu options</a>
        </div>
        <div class="card-body">
            <?php if (!empty($message)): ?>
                <div class="alert alert-success"><?php echo $h($message); ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $h($error); ?></div>
            <?php endif; ?>

            <form method="get" class="row g-2 mb-3">
                <div class="col-auto">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All statuses</option>
                        <option value="pending" <?php echo $filterStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $filterStatus === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $filterStatus === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Staff name</th>
                            <th>Menu option</th>
                            <th>Department</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($assignments)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No assignments found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($assignments as $a): ?>
                                <tr>
                                    <td><?php echo $h($a['staff_name']); ?> <span class="text-muted small">(<?php echo $h($a['staff_id']); ?>)</span></td>
                                    <td>
                                        <a href="<?php echo APP_URL; ?>/admin/nav-menu/edit?id=<?php echo (int) $a['nav_id']; ?>"><?php echo $h($a['nav_label']); ?></a>
                                    </td>
                                    <td><?php echo $h($a['department_name'] ?? '—'); ?></td>
                                    <td>
                                        <?php $st = $a['status']; $badge = $st === 'approved' ? 'success' : ($st === 'pending' ? 'warning' : 'secondary'); ?>
                                        <span class="badge bg-<?php echo $badge; ?>"><?php echo $h(ucfirst($st)); ?></span>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <?php if ($a['status'] === 'pending'): ?>
                                            <a href="<?php echo APP_URL; ?>/admin/nav-menu/approve-assignment?id=<?php echo (int) $a['assignment_id']; ?>" class="btn btn-outline-success btn-sm">Approve</a>
                                            <a href="<?php echo APP_URL; ?>/admin/nav-menu/reject-assignment?id=<?php echo (int) $a['assignment_id']; ?>" class="btn btn-outline-warning btn-sm">Reject</a>
                                        <?php endif; ?>
                                        <a href="<?php echo APP_URL; ?>/admin/nav-menu/edit?id=<?php echo (int) $a['nav_id']; ?>" class="btn btn-outline-primary btn-sm">Edit menu</a>
                                        <a href="<?php echo APP_URL; ?>/admin/nav-menu/delete-assignment?id=<?php echo (int) $a['assignment_id']; ?>&confirm=1" class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete this assignment?');">Delete</a>
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
