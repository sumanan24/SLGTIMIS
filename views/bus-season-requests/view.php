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
                <i class="fas fa-bus me-2 text-success"></i>
                Bus Season Request Details
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="<?php echo APP_URL; ?>/<?php 
                            if ($isStudent) {
                                echo 'bus-season-requests';
                            } elseif ($isHOD) {
                                echo 'bus-season-requests/hod-approval';
                            } elseif ($canSecondApprove) {
                                echo 'bus-season-requests/second-approval';
                            } else {
                                echo 'bus-season-requests/sao-process';
                            }
                        ?>">Bus Season Requests</a>
                    </li>
                    <li class="breadcrumb-item active">Details</li>
                </ol>
            </nav>
        </div>
        <div class="header-actions">
            <a href="<?php echo APP_URL; ?>/<?php 
                if ($isStudent) {
                    echo 'bus-season-requests';
                } elseif ($isHOD) {
                    echo 'bus-season-requests/hod-approval';
                } elseif ($canSecondApprove) {
                    echo 'bus-season-requests/second-approval';
                } else {
                    echo 'bus-season-requests/sao-process';
                }
            ?>" class="btn btn-outline-secondary">
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
                    $status = $request['status'] ?? 'pending';
                    $statusColors = [
                        'pending' => 'warning',
                        'hod_approved' => 'info',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'cancelled' => 'secondary'
                    ];
                    echo $statusColors[$status] ?? 'secondary';
                ?> fs-6 align-self-start">
                    <?php 
                    $statusLabels = [
                        'pending' => 'Pending HOD Approval',
                        'hod_approved' => 'HOD Approved - Pending Second Approval',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'cancelled' => 'Cancelled'
                    ];
                    echo $statusLabels[$status] ?? ucfirst($status);
                    ?>
                </span>
            </div>
            
            <div class="row g-3 row-gap">
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="info-section">
                        <div class="info-label">Season Year</div>
                        <div class="info-value"><?php echo htmlspecialchars($request['season_year'] ?? 'N/A'); ?></div>
                    </div>
                </div>
                
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="info-section">
                        <div class="info-label">Route From</div>
                        <div class="info-value"><?php echo htmlspecialchars($request['route_from'] ?? 'N/A'); ?></div>
                    </div>
                </div>
                
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="info-section">
                        <div class="info-label">Route To</div>
                        <div class="info-value"><?php echo htmlspecialchars($request['route_to'] ?? 'N/A'); ?></div>
                    </div>
                </div>
                
                <?php if (!empty($request['change_point'])): ?>
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="info-section">
                        <div class="info-label">Change Point</div>
                        <div class="info-value"><?php echo htmlspecialchars($request['change_point']); ?></div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="info-section">
                        <div class="info-label">Distance (KM)</div>
                        <div class="info-value"><?php echo number_format($request['distance_km'] ?? 0, 2); ?> km</div>
                    </div>
                </div>
                
                <?php if (!empty($request['season_name'])): ?>
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="info-section">
                        <div class="info-label">Season Name</div>
                        <div class="info-value"><?php echo htmlspecialchars($request['season_name']); ?></div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($request['depot_name'])): ?>
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="info-section">
                        <div class="info-label">Depot Name</div>
                        <div class="info-value"><?php echo htmlspecialchars($request['depot_name']); ?></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($request['notes'])): ?>
                <div class="info-section">
                    <div class="info-label">Notes</div>
                    <div class="info-value">
                        <div class="p-2 p-md-3 bg-light border rounded">
                            <?php echo nl2br(htmlspecialchars($request['notes'])); ?>
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
                        <div class="info-value"><?php echo htmlspecialchars($request['student_fullname'] ?? 'N/A'); ?></div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="info-section">
                        <div class="info-label">Email</div>
                        <div class="info-value"><?php echo htmlspecialchars($request['student_email'] ?? 'N/A'); ?></div>
                    </div>
                </div>
                <?php if (!empty($request['department_name'])): ?>
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="info-section">
                        <div class="info-label">Department</div>
                        <div class="info-value"><?php echo htmlspecialchars($request['department_name']); ?></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Approval Timeline Card -->
        <div class="detail-card">
            <h5 class="fw-bold mb-3">Approval Timeline</h5>
            
            <!-- Request Submitted -->
            <div class="timeline-item">
                <div class="info-label">Request Submitted</div>
                <div class="info-value">
                    <?php echo $request['created_at'] ? date('F d, Y h:i A', strtotime($request['created_at'])) : 'N/A'; ?>
                </div>
            </div>
            
            <!-- HOD Approval -->
            <?php 
            $status = $request['status'] ?? 'pending';
            if ($status !== 'pending' && !empty($request['hod_approval_date'])): ?>
                <div class="timeline-item <?php echo ($status === 'hod_approved' || $status === 'approved') ? 'approved' : 'rejected'; ?>">
                    <div class="info-label">
                        HOD Approval
                        <?php if ($status === 'hod_approved' || $status === 'approved'): ?>
                            <span class="badge bg-success ms-2">Approved</span>
                        <?php else: ?>
                            <span class="badge bg-danger ms-2">Rejected</span>
                        <?php endif; ?>
                    </div>
                    <div class="info-value">
                        <div>
                            <strong><?php echo htmlspecialchars($request['hod_approver_name'] ?? 'N/A'); ?></strong>
                            <br>
                            <small class="text-muted"><?php echo $request['hod_approval_date'] ? date('F d, Y h:i A', strtotime($request['hod_approval_date'])) : 'N/A'; ?></small>
                            <?php if (!empty($request['hod_comments'])): ?>
                                <br>
                                <div class="mt-2 p-2 bg-light border rounded">
                                    <small><?php echo nl2br(htmlspecialchars($request['hod_comments'])); ?></small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Second Approval -->
            <?php if ($status === 'approved' && !empty($request['second_approval_date'])): ?>
                <div class="timeline-item approved">
                    <div class="info-label">
                        Second Approval (<?php echo htmlspecialchars($request['second_approver_role'] ?? 'N/A'); ?>)
                        <span class="badge bg-success ms-2">Approved</span>
                    </div>
                    <div class="info-value">
                        <div>
                            <strong><?php echo htmlspecialchars($request['second_approver_name'] ?? 'N/A'); ?></strong>
                            <br>
                            <small class="text-muted"><?php echo $request['second_approval_date'] ? date('F d, Y h:i A', strtotime($request['second_approval_date'])) : 'N/A'; ?></small>
                            <?php if (!empty($request['second_comments'])): ?>
                                <br>
                                <div class="mt-2 p-2 bg-light border rounded">
                                    <small><?php echo nl2br(htmlspecialchars($request['second_comments'])); ?></small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php elseif ($status === 'rejected' && !empty($request['second_approval_date'])): ?>
                <div class="timeline-item rejected">
                    <div class="info-label">
                        Second Approval (<?php echo htmlspecialchars($request['second_approver_role'] ?? 'N/A'); ?>)
                        <span class="badge bg-danger ms-2">Rejected</span>
                    </div>
                    <div class="info-value">
                        <div>
                            <strong><?php echo htmlspecialchars($request['second_approver_name'] ?? 'N/A'); ?></strong>
                            <br>
                            <small class="text-muted"><?php echo $request['second_approval_date'] ? date('F d, Y h:i A', strtotime($request['second_approval_date'])) : 'N/A'; ?></small>
                            <?php if (!empty($request['second_comments'])): ?>
                                <br>
                                <div class="mt-2 p-2 bg-light border rounded">
                                    <small><?php echo nl2br(htmlspecialchars($request['second_comments'])); ?></small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Payment Information Card -->
        <?php if (isset($request['payment']) && !empty($request['payment'])): ?>
        <div class="detail-card">
            <h5 class="fw-bold mb-3">Payment Information</h5>
            <div class="row g-3 row-gap">
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="info-section">
                        <div class="info-label">Student Payment (30%)</div>
                        <div class="info-value text-success fw-bold">Rs. <?php echo number_format($request['payment']['student_paid'] ?? 0, 2); ?></div>
                    </div>
                </div>
                
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="info-section">
                        <div class="info-label">SLGTI Payment (35%)</div>
                        <div class="info-value">Rs. <?php echo number_format($request['payment']['slgti_paid'] ?? 0, 2); ?></div>
                    </div>
                </div>
                
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="info-section">
                        <div class="info-label">CTB Payment (35%)</div>
                        <div class="info-value">Rs. <?php echo number_format($request['payment']['ctb_paid'] ?? 0, 2); ?></div>
                    </div>
                </div>
                
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="info-section">
                        <div class="info-label">Total Season Value</div>
                        <div class="info-value text-primary fw-bold">Rs. <?php echo number_format($request['payment']['total_amount'] ?? 0, 2); ?></div>
                    </div>
                </div>
                
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="info-section">
                        <div class="info-label">Season Rate</div>
                        <div class="info-value">Rs. <?php echo number_format($request['payment']['season_rate'] ?? 0, 2); ?></div>
                    </div>
                </div>
                
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="info-section">
                        <div class="info-label">Payment Method</div>
                        <div class="info-value"><?php echo ucfirst(htmlspecialchars($request['payment']['payment_method'] ?? 'N/A')); ?></div>
                    </div>
                </div>
                
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="info-section">
                        <div class="info-label">Payment Date</div>
                        <div class="info-value"><?php echo $request['payment']['payment_date'] ? date('M d, Y', strtotime($request['payment']['payment_date'])) : 'N/A'; ?></div>
                    </div>
                </div>
                
                <?php if (!empty($request['payment']['payment_reference'])): ?>
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="info-section">
                        <div class="info-label">Payment Reference</div>
                        <div class="info-value"><?php echo htmlspecialchars($request['payment']['payment_reference']); ?></div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="info-section">
                        <div class="info-label">Collected By</div>
                        <div class="info-value"><?php echo htmlspecialchars($request['payment']['collected_by_name'] ?? 'N/A'); ?></div>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($request['payment']['notes'])): ?>
                <div class="info-section mt-3">
                    <div class="info-label">Payment Notes</div>
                    <div class="info-value">
                        <div class="p-2 p-md-3 bg-light border rounded">
                            <?php echo nl2br(htmlspecialchars($request['payment']['notes'])); ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php elseif ($status === 'approved' && ($isSAO || $isADM)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Payment has not been collected yet. <a href="<?php echo APP_URL; ?>/bus-season-requests/sao-process" class="alert-link">Go to payment collection</a>.
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

