<?php $pendingCount = count($requests ?? []); ?>
<div class="container-fluid px-4 py-3 mis-approval-workflow">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="mb-1 fw-bold">
                        <i class="fas fa-bus me-2"></i>Bus Season — Second Approval
                    </h5>
                    <p class="mb-0 small opacity-75">Approve or reject requests after HOD first approval</p>
                </div>
                <?php if (!empty($userRole)): ?>
                    <span class="badge bg-light text-dark"><?php echo htmlspecialchars($userRole); ?></span>
                <?php endif; ?>
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
                            <p class="small mb-0">All HOD-approved bus season requests have been processed.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($requests as $request): ?>
                            <article class="approval-request-item">
                                <div class="approval-request-item__header">
                                    <div>
                                        <div class="approval-request-item__title"><?php echo htmlspecialchars($request['student_fullname'] ?? 'N/A'); ?></div>
                                        <div class="approval-request-item__meta">
                                            <span class="me-2"><i class="fas fa-id-card me-1"></i><?php echo htmlspecialchars($request['student_id']); ?></span>
                                            <span><i class="fas fa-building me-1"></i><?php echo htmlspecialchars($request['department_name'] ?? 'N/A'); ?></span>
                                        </div>
                                    </div>
                                    <span class="badge bg-success">HOD Approved</span>
                                </div>

                                <div class="approval-step-track">
                                    <span class="step-done"><i class="fas fa-check-circle me-1"></i>Submitted</span>
                                    <span class="step-arrow"><i class="fas fa-chevron-right"></i></span>
                                    <span class="step-done"><i class="fas fa-check-circle me-1"></i>HOD
                                        <?php if (!empty($request['hod_approver_name'])): ?>
                                            (<?php echo htmlspecialchars($request['hod_approver_name']); ?>)
                                        <?php endif; ?>
                                    </span>
                                    <span class="step-arrow"><i class="fas fa-chevron-right"></i></span>
                                    <span class="step-current"><i class="fas fa-circle-notch me-1"></i>Awaiting second approval</span>
                                </div>

                                <div class="approval-detail-grid">
                                    <h6 class="text-uppercase small fw-bold text-muted mb-3">Route information</h6>
                                    <div class="row g-3">
                                        <div class="col-md-3 col-6">
                                            <div class="detail-field">
                                                <label>Season year</label>
                                                <div class="detail-value"><?php echo htmlspecialchars($request['season_year'] ?? 'N/A'); ?></div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-6">
                                            <div class="detail-field">
                                                <label>Route from</label>
                                                <div class="detail-value"><?php echo htmlspecialchars($request['route_from'] ?? 'N/A'); ?></div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-6">
                                            <div class="detail-field">
                                                <label>Route to</label>
                                                <div class="detail-value"><?php echo htmlspecialchars($request['route_to'] ?? 'N/A'); ?></div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-6">
                                            <div class="detail-field">
                                                <label>Distance</label>
                                                <div class="detail-value"><?php echo number_format((float)($request['distance_km'] ?? 0), 2); ?> km</div>
                                            </div>
                                        </div>
                                        <?php if (!empty($request['change_point'])): ?>
                                        <div class="col-md-3 col-6">
                                            <div class="detail-field">
                                                <label>Change point</label>
                                                <div class="detail-value"><?php echo htmlspecialchars($request['change_point']); ?></div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($request['season_name'])): ?>
                                        <div class="col-md-3 col-6">
                                            <div class="detail-field">
                                                <label>Season name</label>
                                                <div class="detail-value"><?php echo htmlspecialchars($request['season_name']); ?></div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($request['depot_name'])): ?>
                                        <div class="col-md-3 col-6">
                                            <div class="detail-field">
                                                <label>Depot</label>
                                                <div class="detail-value"><?php echo htmlspecialchars($request['depot_name']); ?></div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (!empty($request['notes'])): ?>
                                        <div class="mt-3">
                                            <label class="small text-muted text-uppercase fw-semibold">Student notes</label>
                                            <div class="detail-note"><?php echo nl2br(htmlspecialchars($request['notes'])); ?></div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($request['hod_comments'])): ?>
                                        <div>
                                            <label class="small text-muted text-uppercase fw-semibold">HOD comments</label>
                                            <div class="detail-note"><?php echo nl2br(htmlspecialchars($request['hod_comments'])); ?></div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($request['created_at'])): ?>
                                        <p class="small text-muted mb-0 mt-2">
                                            <i class="fas fa-clock me-1"></i>Submitted <?php echo date('M d, Y h:i A', strtotime($request['created_at'])); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <form action="<?php echo APP_URL; ?>/bus-season-requests/second-approve" method="POST" class="approval-request-item__actions">
                                    <input type="hidden" name="request_id" value="<?php echo (int)$request['id']; ?>">
                                    <div class="mb-3">
                                        <label for="comments_<?php echo (int)$request['id']; ?>" class="form-label fw-semibold">Your comments (optional)</label>
                                        <textarea class="form-control" id="comments_<?php echo (int)$request['id']; ?>" name="comments" rows="2" placeholder="Add approval notes…"></textarea>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2 btn-group-actions">
                                        <button type="submit" name="action" value="approve" class="btn btn-success" onclick="return confirm('Approve this bus season request?');">
                                            <i class="fas fa-check me-1"></i>Approve
                                        </button>
                                        <button type="submit" name="action" value="reject" class="btn btn-danger" onclick="return confirm('Reject this bus season request?');">
                                            <i class="fas fa-times me-1"></i>Reject
                                        </button>
                                        <a href="<?php echo APP_URL; ?>/bus-season-requests/view?id=<?php echo (int)$request['id']; ?>" class="btn btn-outline-primary">
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
