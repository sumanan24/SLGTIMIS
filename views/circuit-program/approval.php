<style>
    .request-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        margin-bottom: 1.5rem;
        border-left: 4px solid #198754;
    }
    
    .request-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        transform: translateY(-2px);
    }
    
    .employee-info {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
    }
</style>

<div class="container-fluid px-3 px-md-4 py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold mb-2">
                <i class="fas fa-route me-2 text-success"></i>
                Circuit Program Approvals
            </h2>
            <p class="text-muted mb-0">
                Review and approve circuit programs submitted by staff
            </p>
        </div>
    </div>
    
    <?php if (isset($message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (empty($programs)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>No pending circuit programs for approval.
        </div>
    <?php else: ?>
        <?php foreach ($programs as $program): ?>
            <div class="request-card p-4">
                <div class="employee-info">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <small class="text-muted">Employee Name:</small>
                            <div class="fw-semibold"><?php echo htmlspecialchars($program['employee_name']); ?></div>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">Designation:</small>
                            <div class="fw-semibold"><?php echo htmlspecialchars($program['designation'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">Department:</small>
                            <div class="fw-semibold"><?php echo htmlspecialchars($program['department_name'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">Mode of Travel:</small>
                            <div class="fw-semibold"><?php echo htmlspecialchars($program['mode_of_travel'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">Submitted:</small>
                            <div class="fw-semibold"><?php echo $program['created_at'] ? date('F d, Y h:i A', strtotime($program['created_at'])) : 'N/A'; ?></div>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($program['details'])): ?>
                    <div class="mb-3">
                        <h6 class="fw-bold mb-3">Program Details</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
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
                    </div>
                <?php endif; ?>
                
                <form action="<?php echo APP_URL; ?>/circuit-program/approve" method="POST" class="approval-form">
                    <input type="hidden" name="program_id" value="<?php echo $program['id']; ?>">
                    
                    <div class="mb-3">
                        <label for="comments_<?php echo $program['id']; ?>" class="form-label fw-semibold">Comments (Optional)</label>
                        <textarea class="form-control" id="comments_<?php echo $program['id']; ?>" name="comments" rows="2" placeholder="Enter any comments..."></textarea>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" name="action" value="approve" class="btn btn-success">
                            <i class="fas fa-check me-1"></i>Approve
                        </button>
                        <button type="submit" name="action" value="reject" class="btn btn-danger">
                            <i class="fas fa-times me-1"></i>Reject
                        </button>
                        <a href="<?php echo APP_URL; ?>/circuit-program/view?id=<?php echo $program['id']; ?>" class="btn btn-outline-primary">
                            <i class="fas fa-eye me-1"></i>View Details
                        </a>
                    </div>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

