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
    
    .student-info {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
    }
    
    @media (max-width: 768px) {
        .request-card {
            padding: 1rem !important;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .student-info {
            padding: 0.75rem;
        }
        
        .d-flex.gap-2 {
            flex-direction: column;
        }
        
        .d-flex.gap-2 .btn {
            width: 100%;
        }
    }
</style>

<div class="container-fluid px-3 px-md-4 py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold mb-2">
                <i class="fas fa-bus me-2 text-success"></i>
                Bus Season Request Approvals
            </h2>
            <p class="text-muted mb-0">
                Review and approve bus season requests from students in your department
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
                            <div class="fw-semibold"><?php echo htmlspecialchars($request['student_fullname'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">Department:</small>
                            <div class="fw-semibold"><?php echo htmlspecialchars($request['department_name'] ?? 'N/A'); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <h6 class="fw-bold mb-3">Route Information</h6>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <small class="text-muted d-block mb-1">Season Year:</small>
                            <div class="fw-semibold"><?php echo htmlspecialchars($request['season_year'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block mb-1">Route From:</small>
                            <div class="fw-semibold"><?php echo htmlspecialchars($request['route_from'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block mb-1">Route To:</small>
                            <div class="fw-semibold"><?php echo htmlspecialchars($request['route_to'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block mb-1">Distance (KM):</small>
                            <div class="fw-semibold"><?php echo number_format($request['distance_km'] ?? 0, 2); ?> km</div>
                        </div>
                        <?php if (!empty($request['change_point'])): ?>
                        <div class="col-md-3">
                            <small class="text-muted d-block mb-1">Change Point:</small>
                            <div class="fw-semibold"><?php echo htmlspecialchars($request['change_point']); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($request['season_name'])): ?>
                        <div class="col-md-3">
                            <small class="text-muted d-block mb-1">Season Name:</small>
                            <div class="fw-semibold"><?php echo htmlspecialchars($request['season_name']); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($request['depot_name'])): ?>
                        <div class="col-md-3">
                            <small class="text-muted d-block mb-1">Depot Name:</small>
                            <div class="fw-semibold"><?php echo htmlspecialchars($request['depot_name']); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (!empty($request['notes'])): ?>
                    <div class="mb-3">
                        <small class="text-muted d-block mb-2">Notes:</small>
                        <div class="p-3 bg-light border rounded"><?php echo nl2br(htmlspecialchars($request['notes'])); ?></div>
                    </div>
                <?php endif; ?>
                
                <div class="mb-3">
                    <small class="text-muted d-block mb-2">Submitted:</small>
                    <div class="small"><?php echo $request['created_at'] ? date('F d, Y h:i A', strtotime($request['created_at'])) : 'N/A'; ?></div>
                </div>
                
                <form action="<?php echo APP_URL; ?>/bus-season-requests/hod-approve" method="POST" class="approval-form">
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
                        <a href="<?php echo APP_URL; ?>/bus-season-requests/view?id=<?php echo $request['id']; ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-eye me-2"></i>View Details
                        </a>
                    </div>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

