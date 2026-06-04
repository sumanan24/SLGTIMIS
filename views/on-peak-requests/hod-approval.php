<?php $pendingCount = count($requests ?? []); ?>
<div class="container-fluid px-4 py-3 mis-approval-workflow">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white">
            <div>
                <h5 class="mb-1 fw-bold">
                    <i class="fas fa-calendar-check me-2"></i>On-Peak / Off-Peak — First Approval
                </h5>
                <p class="mb-0 small opacity-75">
                    HOD review for your department
                    <?php if (isset($department)): ?>
                        — <strong><?php echo htmlspecialchars($department['department_name'] ?? 'N/A'); ?></strong>
                    <?php endif; ?>
                </p>
            </div>
        </div>
        <div class="card-body">
            <?php if (isset($message)): ?>
                <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <div><?php echo htmlspecialchars($message); ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <div><?php echo htmlspecialchars($error); ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="approval-summary">
                <div class="approval-summary-card pending">
                    <div class="summary-icon"><i class="fas fa-hourglass-half"></i></div>
                    <div>
                        <div class="summary-value"><?php echo $pendingCount; ?></div>
                        <div class="summary-label">Pending approval</div>
                    </div>
                </div>
            </div>

            <div class="section-panel mb-0">
                <div class="section-panel-header">
                    <h6><i class="fas fa-list-ul me-2"></i>Pending requests</h6>
                    <span class="badge rounded-pill bg-warning text-dark"><?php echo $pendingCount; ?></span>
                </div>
                <div class="section-panel-body">
                    <?php if (empty($requests)): ?>
                        <div class="approval-empty">
                            <i class="fas fa-inbox"></i>
                            <p class="mb-0 fw-semibold">No pending requests</p>
                            <p class="small mb-0">New student requests will appear here.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($requests as $request): ?>
                            <article class="approval-request-item">
                                <div class="approval-request-item__header">
                                    <div>
                                        <div class="approval-request-item__title"><?php echo htmlspecialchars($request['student_fullname']); ?></div>
                                        <div class="approval-request-item__meta">
                                            <span class="me-2"><i class="fas fa-id-card me-1"></i><?php echo htmlspecialchars($request['student_id']); ?></span>
                                            <span><i class="fas fa-building me-1"></i><?php echo htmlspecialchars($request['department_name'] ?? 'N/A'); ?></span>
                                        </div>
                                    </div>
                                    <span class="badge bg-warning text-dark">Pending</span>
                                </div>

                                <div class="approval-step-track">
                                    <span class="step-done"><i class="fas fa-check-circle me-1"></i>Submitted</span>
                                    <span class="step-arrow"><i class="fas fa-chevron-right"></i></span>
                                    <span class="step-current"><i class="fas fa-circle-notch me-1"></i>Awaiting HOD approval</span>
                                </div>

                                <div class="approval-detail-grid">
                                    <div class="row g-3">
                                        <div class="col-md-3 col-6">
                                            <div class="detail-field">
                                                <label>Exit date</label>
                                                <div class="detail-value"><?php echo $request['exit_date'] ? date('M d, Y', strtotime($request['exit_date'])) : 'N/A'; ?></div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-6">
                                            <div class="detail-field">
                                                <label>Exit time</label>
                                                <div class="detail-value"><?php echo $request['exit_time'] ? date('h:i A', strtotime($request['exit_time'])) : 'N/A'; ?></div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-6">
                                            <div class="detail-field">
                                                <label>Return date</label>
                                                <div class="detail-value"><?php echo $request['return_date'] ? date('M d, Y', strtotime($request['return_date'])) : 'N/A'; ?></div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-6">
                                            <div class="detail-field">
                                                <label>Return time</label>
                                                <div class="detail-value"><?php echo $request['return_time'] ? date('h:i A', strtotime($request['return_time'])) : 'N/A'; ?></div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-6">
                                            <div class="detail-field">
                                                <label>Reason</label>
                                                <div class="detail-value"><?php echo htmlspecialchars($request['reason'] ?? 'N/A'); ?></div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-6">
                                            <div class="detail-field">
                                                <label>Contact</label>
                                                <div class="detail-value"><?php echo htmlspecialchars($request['contact_no'] ?? 'N/A'); ?></div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-6">
                                            <div class="detail-field">
                                                <label>Student type</label>
                                                <div class="detail-value">
                                                    <?php if (!empty($request['is_hostel_student']) && (int)$request['is_hostel_student'] === 1): ?>
                                                        <i class="fas fa-bed text-success me-1"></i>Hostel
                                                    <?php else: ?>
                                                        <i class="fas fa-home text-info me-1"></i>Non-hostel
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if (!empty($request['comment'])): ?>
                                        <div class="mt-3">
                                            <label class="small text-muted text-uppercase fw-semibold">Student comment</label>
                                            <div class="detail-note"><?php echo nl2br(htmlspecialchars($request['comment'])); ?></div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($request['request_date_time'])): ?>
                                        <p class="small text-muted mb-0 mt-2">
                                            <i class="fas fa-clock me-1"></i>Submitted <?php echo date('M d, Y h:i A', strtotime($request['request_date_time'])); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <form action="<?php echo APP_URL; ?>/on-peak-requests/hod-approve" method="POST" class="approval-request-item__actions">
                                    <input type="hidden" name="request_id" value="<?php echo (int)$request['id']; ?>">
                                    <div class="mb-3">
                                        <label for="comments_<?php echo (int)$request['id']; ?>" class="form-label fw-semibold">Your comments (optional)</label>
                                        <textarea class="form-control" id="comments_<?php echo (int)$request['id']; ?>" name="comments" rows="2" placeholder="Add approval notes…"></textarea>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2 btn-group-actions">
                                        <button type="submit" name="action" value="approve" class="btn btn-success" onclick="return confirm('Approve this request?');">
                                            <i class="fas fa-check me-1"></i>Approve
                                        </button>
                                        <button type="submit" name="action" value="reject" class="btn btn-danger" onclick="return confirm('Reject this request?');">
                                            <i class="fas fa-times me-1"></i>Reject
                                        </button>
                                        <a href="<?php echo APP_URL; ?>/on-peak-requests/view?id=<?php echo (int)$request['id']; ?>" class="btn btn-outline-primary">
                                            <i class="fas fa-eye me-1"></i>View full details
                                        </a>
                                    </div>
                                </form>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
