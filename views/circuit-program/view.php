<style>
    .detail-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    
    .info-label {
        font-weight: 600;
        color: #6c757d;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.5rem;
    }
    
    .info-value {
        font-size: 1rem;
        color: #212529;
        word-break: break-word;
    }
</style>

<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold"><i class="fas fa-route me-2"></i>Circuit Program Details</h4>
        <a href="<?php echo APP_URL; ?>/circuit-program" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Back to List
        </a>
    </div>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="detail-card">
        <h5 class="fw-bold mb-4">Employee Information</h5>
        <div class="row g-3">
            <div class="col-md-4">
                <div class="info-label">Name of Employee</div>
                <div class="info-value"><?php echo htmlspecialchars($program['employee_name']); ?></div>
            </div>
            <div class="col-md-4">
                <div class="info-label">Designation</div>
                <div class="info-value"><?php echo htmlspecialchars($program['designation'] ?? 'N/A'); ?></div>
            </div>
            <div class="col-md-4">
                <div class="info-label">Department</div>
                <div class="info-value">
                    <span class="badge bg-info bg-opacity-10 text-info">
                        <?php echo htmlspecialchars($program['department_name'] ?? 'N/A'); ?>
                    </span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-label">Mode of Travel</div>
                <div class="info-value"><?php echo htmlspecialchars($program['mode_of_travel'] ?? 'N/A'); ?></div>
            </div>
            <div class="col-md-4">
                <div class="info-label">Status</div>
                <div class="info-value">
                    <?php
                    $statusClass = 'warning';
                    $statusText = 'Pending';
                    if ($program['status'] === 'approved') {
                        $statusClass = 'success';
                        $statusText = 'Approved';
                    } elseif ($program['status'] === 'rejected') {
                        $statusClass = 'danger';
                        $statusText = 'Rejected';
                    }
                    ?>
                    <span class="badge bg-<?php echo $statusClass; ?> rounded-pill px-3 py-2">
                        <?php echo $statusText; ?>
                    </span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-label">Created Date</div>
                <div class="info-value"><?php echo $program['created_at'] ? date('F d, Y h:i A', strtotime($program['created_at'])) : 'N/A'; ?></div>
            </div>
        </div>
    </div>
    
    <div class="detail-card">
        <h5 class="fw-bold mb-4">Program Details</h5>
        <?php if (!empty($program['details'])): ?>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Destination</th>
                            <th>Purpose</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($program['details'] as $detail): ?>
                            <tr>
                                <td><?php echo date('F d, Y', strtotime($detail['date'])); ?></td>
                                <td><?php echo htmlspecialchars($detail['destination']); ?></td>
                                <td><?php echo htmlspecialchars($detail['purpose'] ?? 'N/A'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">No program details found.</div>
        <?php endif; ?>
    </div>
    
    <?php if ($program['status'] === 'approved' || $program['status'] === 'rejected'): ?>
        <div class="detail-card">
            <h5 class="fw-bold mb-4">Approval Information</h5>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="info-label">Approved By</div>
                    <div class="info-value"><?php echo htmlspecialchars($program['approver_name'] ?? 'N/A'); ?></div>
                </div>
                <div class="col-md-4">
                    <div class="info-label">Approver Role</div>
                    <div class="info-value">
                        <span class="badge bg-primary"><?php echo htmlspecialchars($program['approver_role'] ?? 'N/A'); ?></span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-label">Approval Date</div>
                    <div class="info-value"><?php echo $program['approval_date'] ? date('F d, Y h:i A', strtotime($program['approval_date'])) : 'N/A'; ?></div>
                </div>
                <?php if (!empty($program['approval_comments'])): ?>
                    <div class="col-12">
                        <div class="info-label">Comments</div>
                        <div class="info-value p-3 bg-light border rounded"><?php echo nl2br(htmlspecialchars($program['approval_comments'])); ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

