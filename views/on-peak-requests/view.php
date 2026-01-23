<style>
    .detail-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    
    @media (max-width: 768px) {
        .detail-card {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
    }
    
    .info-section {
        margin-bottom: 1.5rem;
    }
    
    @media (max-width: 768px) {
        .info-section {
            margin-bottom: 1.25rem;
        }
    }
    
    .info-label {
        font-weight: 600;
        color: #6c757d;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.5rem;
    }
    
    @media (max-width: 768px) {
        .info-label {
            font-size: 0.75rem;
            margin-bottom: 0.375rem;
        }
    }
    
    .info-value {
        font-size: 1rem;
        color: #212529;
        word-break: break-word;
    }
    
    @media (max-width: 768px) {
        .info-value {
            font-size: 0.9rem;
        }
    }
    
    .timeline-item {
        border-left: 3px solid #e9ecef;
        padding-left: 1.5rem;
        margin-bottom: 1.5rem;
        position: relative;
    }
    
    @media (max-width: 768px) {
        .timeline-item {
            padding-left: 1rem;
            margin-bottom: 1.25rem;
        }
    }
    
    .timeline-item::before {
        content: '';
        position: absolute;
        left: -6px;
        top: 0.5rem;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #0d6efd;
    }
    
    @media (max-width: 768px) {
        .timeline-item::before {
            left: -5px;
            width: 10px;
            height: 10px;
        }
    }
    
    .timeline-item.approved::before {
        background: #198754;
    }
    
    .timeline-item.rejected::before {
        background: #dc3545;
    }
    
    @media (max-width: 768px) {
        .breadcrumb {
            font-size: 0.875rem;
        }
        
        h2 {
            font-size: 1.5rem;
        }
        
        h4, h5 {
            font-size: 1.25rem;
        }
        
        .badge {
            font-size: 0.75rem;
            padding: 0.375rem 0.625rem;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }
        
        .container-fluid {
            padding-left: 1rem;
            padding-right: 1rem;
        }
    }
    
    .request-id-badge {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.9rem;
        display: inline-block;
    }
    
    @media (max-width: 768px) {
        .request-id-badge {
            padding: 0.375rem 0.75rem;
            font-size: 0.8rem;
        }
    }
    
    .row-gap {
        row-gap: 1rem;
    }
    
    .detail-card h4,
    .detail-card h5 {
        margin-bottom: 1rem;
    }
    
    @media (max-width: 768px) {
        .detail-card h4,
        .detail-card h5 {
            margin-bottom: 0.75rem;
        }
    }
    
    .info-value i {
        margin-right: 0.5rem;
    }
    
    .timeline-item .info-value {
        line-height: 1.6;
    }
    
    @media (max-width: 768px) {
        .timeline-item .info-value {
            font-size: 0.85rem;
            line-height: 1.5;
        }
        
        .timeline-item .info-label {
            margin-bottom: 0.25rem;
        }
    }
    
    .breadcrumb-item + .breadcrumb-item::before {
        content: ">";
        padding: 0 0.5rem;
    }
    
    /* Header section alignment */
    .page-header-wrapper {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
    }
    
    @media (max-width: 767.98px) {
        .page-header-wrapper {
            flex-direction: column;
            width: 100%;
        }
        
        .page-header-wrapper .btn {
            width: 100%;
        }
    }
    
    .header-content {
        flex: 1;
        min-width: 0;
    }
    
    .header-actions {
        flex-shrink: 0;
    }
    
    @media (max-width: 767.98px) {
        .header-actions {
            width: 100%;
        }
    }
</style>

<div class="container-fluid px-3 px-md-4 py-3 py-md-4">
    <div class="page-header-wrapper mb-3 mb-md-4">
        <div class="header-content">
            <h2 class="fw-bold mb-2">
                <i class="fas fa-file-alt me-2 text-primary"></i>
                Request Details
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?php echo APP_URL; ?>/<?php echo $isStudent ? 'on-peak-requests' : ($isHOD ? 'on-peak-requests/hod-approval' : 'on-peak-requests/final-approval'); ?>">Requests</a></li>
                    <li class="breadcrumb-item active">Details</li>
                </ol>
            </nav>
        </div>
        <div class="header-actions">
            <a href="<?php echo APP_URL; ?>/<?php echo $isStudent ? 'on-peak-requests' : ($isHOD ? 'on-peak-requests/hod-approval' : 'on-peak-requests/final-approval'); ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back
            </a>
        </div>
    </div>
    
    <?php if (empty($request)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle me-2"></i>Request not found.
        </div>
    <?php else: ?>
        <!-- Request Status Card -->
        <div class="detail-card">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 gap-2 gap-md-0">
                <div class="d-flex flex-column gap-2">
                    <h4 class="fw-bold mb-0">Request Information</h4>
                    <?php if (isset($request['id'])): ?>
                        <div class="request-id-badge">
                            <i class="fas fa-hashtag me-1"></i>Request ID: <?php echo htmlspecialchars($request['id']); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <span class="badge bg-<?php 
                    $status = $request['onpeak_request_status'] ?? 'pending';
                    $statusColors = [
                        'pending' => 'warning',
                        '' => 'warning',
                        'hod_approved' => 'info',
                        'HOD Approved' => 'info',
                        'HOD Rejected' => 'danger',
                        'Approved' => 'success',
                        'Rejected' => 'danger',
                        'Cancelled' => 'secondary'
                    ];
                    echo $statusColors[$status] ?? 'secondary';
                ?> fs-6 align-self-start">
                    <?php 
                    $statusLabels = [
                        'pending' => 'Pending HOD Approval',
                        '' => 'Pending HOD Approval',
                        'hod_approved' => 'HOD Approved - Pending Second Approval',
                        'HOD Approved' => 'HOD Approved - Pending Second Approval',
                        'HOD Rejected' => 'HOD Rejected',
                        'Approved' => 'Approved',
                        'Rejected' => 'Rejected',
                        'Cancelled' => 'Cancelled'
                    ];
                    echo $statusLabels[$status] ?? ucfirst($status);
                    ?>
                </span>
            </div>
            
            <div class="row g-3 row-gap">
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="info-section">
                        <div class="info-label">Exit Date</div>
                        <div class="info-value"><?php echo $request['exit_date'] ? date('M d, Y', strtotime($request['exit_date'])) : 'N/A'; ?></div>
                    </div>
                </div>
                
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="info-section">
                        <div class="info-label">Exit Time</div>
                        <div class="info-value"><?php echo $request['exit_time'] ? date('h:i A', strtotime($request['exit_time'])) : 'N/A'; ?></div>
                    </div>
                </div>
                
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="info-section">
                        <div class="info-label">Return Date</div>
                        <div class="info-value"><?php echo $request['return_date'] ? date('M d, Y', strtotime($request['return_date'])) : 'N/A'; ?></div>
                    </div>
                </div>
                
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="info-section">
                        <div class="info-label">Return Time</div>
                        <div class="info-value"><?php echo $request['return_time'] ? date('h:i A', strtotime($request['return_time'])) : 'N/A'; ?></div>
                    </div>
                </div>
                
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="info-section">
                        <div class="info-label">Reason</div>
                        <div class="info-value"><?php echo htmlspecialchars($request['reason'] ?? 'N/A'); ?></div>
                    </div>
                </div>
                
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="info-section">
                        <div class="info-label">Contact No</div>
                        <div class="info-value"><?php echo htmlspecialchars($request['contact_no'] ?? 'N/A'); ?></div>
                    </div>
                </div>
                
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="info-section">
                        <div class="info-label">Student Type</div>
                        <div class="info-value">
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
                <div class="info-section">
                    <div class="info-label">Comments</div>
                    <div class="info-value">
                        <div class="p-2 p-md-3 bg-light border rounded">
                            <?php echo nl2br(htmlspecialchars($request['comment'])); ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Student Information Card -->
        <div class="detail-card">
            <h5 class="fw-bold mb-3">Student Information</h5>
            <div class="row g-3 row-gap">
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="info-section">
                        <div class="info-label">Student ID</div>
                        <div class="info-value"><?php echo htmlspecialchars($request['student_id']); ?></div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="info-section">
                        <div class="info-label">Student Name</div>
                        <div class="info-value"><?php echo htmlspecialchars($request['student_fullname']); ?></div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="info-section">
                        <div class="info-label">Email</div>
                        <div class="info-value"><?php echo htmlspecialchars($request['student_email'] ?? 'N/A'); ?></div>
                    </div>
                </div>
                <?php if ($request['student_gender']): ?>
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="info-section">
                        <div class="info-label">Gender</div>
                        <div class="info-value"><?php echo htmlspecialchars($request['student_gender']); ?></div>
                    </div>
                </div>
                <?php endif; ?>
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="info-section">
                        <div class="info-label">Department</div>
                        <div class="info-value"><?php echo htmlspecialchars($request['department_name'] ?? 'N/A'); ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Approval Timeline Card -->
        <div class="detail-card">
            <h5 class="fw-bold mb-3">Approval Timeline</h5>
            
            <!-- Request Submitted -->
            <div class="timeline-item">
                <div class="info-label">Request Submitted</div>
                <div class="info-value">
                    <?php echo $request['request_date_time'] ? date('F d, Y h:i A', strtotime($request['request_date_time'])) : 'N/A'; ?>
                </div>
            </div>
            
            <!-- HOD Approval -->
            <?php 
            $status = $request['onpeak_request_status'] ?? 'pending';
            if ($status !== 'pending' && $status !== '' && $status !== null): ?>
                <div class="timeline-item <?php echo ($status === 'hod_approved' || $status === 'HOD Approved' || $status === 'Approved') ? 'approved' : 'rejected'; ?>">
                    <div class="info-label">
                        HOD Approval
                        <?php if ($status === 'hod_approved' || $status === 'HOD Approved' || $status === 'Approved'): ?>
                            <span class="badge bg-success ms-2">Approved</span>
                        <?php elseif ($status === 'HOD Rejected'): ?>
                            <span class="badge bg-danger ms-2">Rejected</span>
                        <?php endif; ?>
                    </div>
                    <div class="info-value">
                        <?php if (!empty($request['hod_approver_name'])): ?>
                            <strong>Approver:</strong> <?php echo htmlspecialchars($request['hod_approver_name']); ?><br>
                        <?php endif; ?>
                        <?php if (!empty($request['hod_approval_date'])): ?>
                            <strong>Date:</strong> <?php echo date('F d, Y h:i A', strtotime($request['hod_approval_date'])); ?><br>
                        <?php endif; ?>
                        <?php if (!empty($request['hod_comments'])): ?>
                            <strong>Comments:</strong><br>
                            <div class="p-2 p-md-2 bg-light border rounded mt-2">
                                <?php echo nl2br(htmlspecialchars($request['hod_comments'])); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="timeline-item">
                    <div class="info-label">HOD Approval</div>
                    <div class="info-value text-muted">Pending...</div>
                </div>
            <?php endif; ?>
            
            <!-- Second Approval (Director/Warden) -->
            <?php 
            if ($status === 'Approved' || $status === 'Rejected'): ?>
                <div class="timeline-item <?php echo $status === 'Approved' ? 'approved' : 'rejected'; ?>">
                    <div class="info-label">
                        Second Approval (<?php echo htmlspecialchars($request['second_approver_role'] ?? 'Director/Warden'); ?>)
                        <?php if ($status === 'Approved'): ?>
                            <span class="badge bg-success ms-2">Approved</span>
                        <?php else: ?>
                            <span class="badge bg-danger ms-2">Rejected</span>
                        <?php endif; ?>
                    </div>
                    <div class="info-value">
                        <?php if (!empty($request['second_approver_name'])): ?>
                            <strong>Approver:</strong> <?php echo htmlspecialchars($request['second_approver_name']); ?><br>
                        <?php endif; ?>
                        <?php if (!empty($request['second_approver_role'])): ?>
                            <strong>Role:</strong> <?php echo htmlspecialchars($request['second_approver_role']); ?><br>
                        <?php endif; ?>
                        <?php if (!empty($request['second_approval_date'])): ?>
                            <strong>Date:</strong> <?php echo date('F d, Y h:i A', strtotime($request['second_approval_date'])); ?><br>
                        <?php endif; ?>
                        <?php if (!empty($request['second_comments'])): ?>
                            <strong>Comments:</strong><br>
                            <div class="p-2 p-md-2 bg-light border rounded mt-2">
                                <?php echo nl2br(htmlspecialchars($request['second_comments'])); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php elseif ($status === 'hod_approved' || $status === 'HOD Approved'): ?>
                <div class="timeline-item">
                    <div class="info-label">Second Approval (Director/Warden)</div>
                    <div class="info-value text-muted">Pending...</div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

