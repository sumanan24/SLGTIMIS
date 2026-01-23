<style>
    .request-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        margin-bottom: 1.5rem;
        border-left: 4px solid var(--student-primary);
    }
    
    .request-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        transform: translateY(-2px);
    }
    
    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 600;
        display: inline-block;
    }
    
    .status-pending, .status-null {
        background-color: #fff3cd;
        color: #856404;
    }
    
    .status-hod_approved, .status-hodapproved, .status-HOD Approved {
        background-color: #cfe2ff;
        color: #084298;
    }
    
    .status-Approved {
        background-color: #d1e7dd;
        color: #0f5132;
    }
    
    .status-Rejected, .status-HOD Rejected {
        background-color: #f8d7da;
        color: #721c24;
    }
    
    .status-Cancelled {
        background-color: #e2e3e5;
        color: #383d41;
    }
    
    .form-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        padding: 2rem;
        margin-bottom: 2rem;
        border-left: 4px solid var(--student-primary);
    }
    
    .form-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #e9ecef;
    }
    
    @media (max-width: 768px) {
        .form-card {
            padding: 1.5rem;
        }
        
        .form-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }
    }
</style>

<div class="container-fluid px-3 px-md-4 py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold mb-2">
                <i class="fas fa-calendar-check me-2" style="color: var(--student-primary);"></i>
                OnPeak Request
            </h2>
            <p class="text-muted mb-0">Temporary Exit Application</p>
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
    
    <!-- Request Form -->
    <div class="form-card">
        <div class="form-header">
            <h4 class="fw-bold mb-0">
                <i class="fas fa-plus-circle me-2" style="color: var(--student-primary);"></i>
                OnPeak Request
            </h4>
            <a href="#" onclick="document.getElementById('requestForm').style.display = document.getElementById('requestForm').style.display === 'none' ? 'block' : 'none'; return false;" class="text-decoration-none text-muted">
                <i class="fas fa-eye-slash me-1"></i>Hide form
            </a>
        </div>
        <p class="text-muted mb-4">Temporary Exit Application</p>
        
        <form action="<?php echo APP_URL; ?>/on-peak-requests/create" method="POST" id="requestForm">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="student_id" class="form-label fw-semibold">Registration No <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="student_id" name="student_id" value="<?php echo htmlspecialchars($student['student_id'] ?? ''); ?>" readonly required>
                </div>
                
                <div class="col-md-6">
                    <label for="contact_no" class="form-label fw-semibold">Contact Number <span class="text-danger">*</span></label>
                    <input type="tel" class="form-control" id="contact_no" name="contact_no" placeholder="e.g., 0712345678" required>
                </div>
                
                <div class="col-md-6">
                    <label for="reason" class="form-label fw-semibold">Reason for Exit <span class="text-danger">*</span></label>
                    <select class="form-select" id="reason" name="reason" required>
                        <option value="">Select reason</option>
                        <option value="Medical">Medical</option>
                        <option value="Emergency">Emergency</option>
                        <option value="Personal">Personal</option>
                        <option value="Family">Family</option>
                        <option value="Official">Official</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Student Type <span class="text-danger">*</span></label>
                    <div class="mt-2">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="is_hostel_student" id="hostel_student" value="1" <?php echo (isset($isHostelStudent) && $isHostelStudent) ? 'checked' : ''; ?> required>
                            <label class="form-check-label" for="hostel_student">
                                <i class="fas fa-bed me-1"></i>Hostel Student
                            </label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="is_hostel_student" id="non_hostel_student" value="0" <?php echo (!isset($isHostelStudent) || !$isHostelStudent) ? 'checked' : ''; ?> required>
                            <label class="form-check-label" for="non_hostel_student">
                                <i class="fas fa-home me-1"></i>Non-Hostel Student
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label for="exit_date" class="form-label fw-semibold">Exit Date <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="date" class="form-control" id="exit_date" name="exit_date" required>
                        <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label for="exit_time" class="form-label fw-semibold">Exit Time <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="time" class="form-control" id="exit_time" name="exit_time" required>
                        <span class="input-group-text"><i class="fas fa-clock"></i></span>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label for="return_date" class="form-label fw-semibold">Return Date <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="date" class="form-control" id="return_date" name="return_date" required>
                        <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label for="return_time" class="form-label fw-semibold">Return Time <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="time" class="form-control" id="return_time" name="return_time" required>
                        <span class="input-group-text"><i class="fas fa-clock"></i></span>
                    </div>
                </div>
                
                <div class="col-12">
                    <label for="comment" class="form-label fw-semibold">Comments (optional)</label>
                    <textarea class="form-control" id="comment" name="comment" rows="4" placeholder="Comments (optional)"></textarea>
                </div>
                
                <div class="col-12">
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <small>This request must be approved by the HOD and <?php echo (isset($isHostelStudent) && $isHostelStudent) ? 'Director/Warden' : 'HOD'; ?>, when students want to exit SLGTI during school hours/ on peak (8.15 am - 4.15 pm).</small>
                    </div>
                </div>
                
                <div class="col-12">
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="fas fa-check me-2"></i>Request to approval
                    </button>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Request List -->
    <div class="mb-4">
        <h4 class="fw-bold mb-3">
            <i class="fas fa-list me-2" style="color: var(--student-primary);"></i>
            My Requests
        </h4>
        
        <?php if (empty($requests)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>No requests found. Submit a new request above.
            </div>
        <?php else: ?>
            <?php foreach ($requests as $request): ?>
                <div class="request-card p-3 p-md-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center gap-2 mb-2">
                                <h5 class="fw-bold mb-0">Temporary Exit Request</h5>
                                <span class="status-badge status-<?php echo str_replace(' ', '', $request['onpeak_request_status'] ?? 'pending'); ?>">
                                    <?php 
                                    $status = $request['onpeak_request_status'] ?? 'pending';
                                    $statusLabels = [
                                        'pending' => 'Pending HOD Approval',
                                        '' => 'Pending HOD Approval',
                                        'hod_approved' => 'HOD Approved - Pending Second Approval',
                                        'HOD Approved' => 'HOD Approved - Pending Second Approval',
                                        'Approved' => 'Approved',
                                        'Rejected' => 'Rejected',
                                        'HOD Rejected' => 'HOD Rejected',
                                        'Cancelled' => 'Cancelled'
                                    ];
                                    echo $statusLabels[$status] ?? ucfirst($status);
                                    ?>
                                </span>
                            </div>
                            
                            <div class="row g-2 mb-2">
                                <div class="col-6 col-md-3">
                                    <small class="text-muted">Exit Date:</small>
                                    <div class="fw-semibold"><?php echo $request['exit_date'] ? date('M d, Y', strtotime($request['exit_date'])) : 'N/A'; ?></div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <small class="text-muted">Exit Time:</small>
                                    <div class="fw-semibold"><?php echo $request['exit_time'] ? date('h:i A', strtotime($request['exit_time'])) : 'N/A'; ?></div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <small class="text-muted">Return Date:</small>
                                    <div class="fw-semibold"><?php echo $request['return_date'] ? date('M d, Y', strtotime($request['return_date'])) : 'N/A'; ?></div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <small class="text-muted">Return Time:</small>
                                    <div class="fw-semibold"><?php echo $request['return_time'] ? date('h:i A', strtotime($request['return_time'])) : 'N/A'; ?></div>
                                </div>
                            </div>
                            
                            <div class="row g-2 mb-2">
                                <div class="col-6 col-md-4">
                                    <small class="text-muted">Reason:</small>
                                    <div class="fw-semibold"><?php echo htmlspecialchars($request['reason'] ?? 'N/A'); ?></div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <small class="text-muted">Contact:</small>
                                    <div class="fw-semibold"><?php echo htmlspecialchars($request['contact_no'] ?? 'N/A'); ?></div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <small class="text-muted">Student Type:</small>
                                    <div class="fw-semibold">
                                        <?php echo (isset($request['is_hostel_student']) && $request['is_hostel_student'] == 1) ? '<i class="fas fa-bed text-success"></i> Hostel' : '<i class="fas fa-home text-info"></i> Non-Hostel'; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (!empty($request['comment'])): ?>
                                <div class="mb-2">
                                    <small class="text-muted">Comments:</small>
                                    <div class="small"><?php echo nl2br(htmlspecialchars($request['comment'])); ?></div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($request['hod_comments'])): ?>
                                <div class="mb-2">
                                    <small class="text-muted">HOD Comments:</small>
                                    <div class="small text-muted"><?php echo nl2br(htmlspecialchars($request['hod_comments'])); ?></div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($request['second_comments'])): ?>
                                <div class="mb-2">
                                    <small class="text-muted">Second Approver Comments:</small>
                                    <div class="small text-muted"><?php echo nl2br(htmlspecialchars($request['second_comments'])); ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-4 text-end mt-3 mt-md-0">
                            <div class="d-flex flex-column gap-2">
                                <a href="<?php echo APP_URL; ?>/on-peak-requests/view?id=<?php echo $request['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye me-1"></i>View Details
                                </a>
                                <?php 
                                $status = $request['onpeak_request_status'] ?? 'pending';
                                if ($status === 'pending' || $status === '' || $status === null): ?>
                                    <form action="<?php echo APP_URL; ?>/on-peak-requests/cancel" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to cancel this request?');">
                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-times me-1"></i>Cancel
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
