<style>
    .request-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        margin-bottom: 1.5rem;
        border-left: 4px solid #0d6efd;
    }
    
    .request-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        transform: translateY(-2px);
    }
    
    .student-info {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
    }
</style>

<div class="container-fluid px-4 py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold mb-2">
                <i class="fas fa-check-circle me-2 text-primary"></i>
                On-Peak/Off-Peak Request Approvals
            </h2>
            <p class="text-muted mb-0">
                <?php if (isset($department)): ?>
                    Department: <strong><?php echo htmlspecialchars($department['department_name'] ?? 'N/A'); ?></strong>
                <?php endif; ?>
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
    
    <?php if (empty($requests)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>No pending requests for approval.
        </div>
    <?php else: ?>
        <?php foreach ($requests as $request): ?>
            <div class="request-card p-4">
                <div class="student-info">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <small class="text-muted">Student ID:</small>
                            <div class="fw-semibold"><?php echo htmlspecialchars($request['student_id']); ?></div>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">Student Name:</small>
                            <div class="fw-semibold"><?php echo htmlspecialchars($request['student_fullname']); ?></div>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">Department:</small>
                            <div class="fw-semibold"><?php echo htmlspecialchars($request['department_name'] ?? 'N/A'); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <small class="text-muted d-block mb-1">Exit Date:</small>
                            <div class="fw-semibold"><?php echo $request['exit_date'] ? date('M d, Y', strtotime($request['exit_date'])) : 'N/A'; ?></div>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block mb-1">Exit Time:</small>
                            <div class="fw-semibold"><?php echo $request['exit_time'] ? date('h:i A', strtotime($request['exit_time'])) : 'N/A'; ?></div>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block mb-1">Return Date:</small>
                            <div class="fw-semibold"><?php echo $request['return_date'] ? date('M d, Y', strtotime($request['return_date'])) : 'N/A'; ?></div>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block mb-1">Return Time:</small>
                            <div class="fw-semibold"><?php echo $request['return_time'] ? date('h:i A', strtotime($request['return_time'])) : 'N/A'; ?></div>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block mb-1">Reason:</small>
                            <div class="fw-semibold"><?php echo htmlspecialchars($request['reason'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block mb-1">Contact No:</small>
                            <div class="fw-semibold"><?php echo htmlspecialchars($request['contact_no'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block mb-1">Student Type:</small>
                            <div class="fw-semibold">
                                <?php if (isset($request['is_hostel_student']) && $request['is_hostel_student'] == 1): ?>
                                    <i class="fas fa-bed text-success"></i> Hostel Student
                                <?php else: ?>
                                    <i class="fas fa-home text-info"></i> Non-Hostel Student
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($request['comment'])): ?>
                    <div class="mb-3">
                        <small class="text-muted d-block mb-2">Comments:</small>
                        <div class="p-3 bg-light border rounded"><?php echo nl2br(htmlspecialchars($request['comment'])); ?></div>
                    </div>
                <?php endif; ?>
                
                <div class="mb-3">
                    <small class="text-muted d-block mb-2">Submitted:</small>
                    <div class="small"><?php echo $request['request_date_time'] ? date('F d, Y h:i A', strtotime($request['request_date_time'])) : 'N/A'; ?></div>
                </div>
                
                <form action="<?php echo APP_URL; ?>/on-peak-requests/hod-approve" method="POST" class="approval-form">
                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                    
                    <div class="mb-3">
                        <label for="comments_<?php echo $request['id']; ?>" class="form-label fw-semibold">Comments (Optional)</label>
                        <textarea class="form-control" id="comments_<?php echo $request['id']; ?>" name="comments" rows="3" placeholder="Enter your comments..."></textarea>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" name="action" value="approve" class="btn btn-success" onclick="return confirm('Are you sure you want to approve this request?');">
                            <i class="fas fa-check me-2"></i>Approve
                        </button>
                        <button type="submit" name="action" value="reject" class="btn btn-danger" onclick="return confirm('Are you sure you want to reject this request?');">
                            <i class="fas fa-times me-2"></i>Reject
                        </button>
                        <a href="<?php echo APP_URL; ?>/on-peak-requests/view?id=<?php echo $request['id']; ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-eye me-2"></i>View Details
                        </a>
                    </div>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

