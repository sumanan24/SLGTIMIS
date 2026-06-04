<?php
$pendingCount = count($requests ?? []);
$completedCount = count($completedRequests ?? []);
?>
<div class="container-fluid px-4 py-3 mis-approval-workflow">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="mb-1 fw-bold">
                        <i class="fas fa-check-double me-2"></i>On-Peak / Off-Peak — Final Approval
                    </h5>
                    <p class="mb-0 small opacity-75">Second-level approval for hostel students after HOD approval</p>
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
                <div class="approval-summary-card completed">
                    <div class="summary-icon"><i class="fas fa-clipboard-check"></i></div>
                    <div>
                        <div class="summary-value"><?php echo $completedCount; ?></div>
                        <div class="summary-label">Completed (approved / rejected)</div>
                    </div>
                </div>
            </div>

            <div class="section-panel">
                <div class="section-panel-header">
                    <h6><i class="fas fa-list-ul me-2"></i>Pending requests</h6>
                    <span class="badge rounded-pill bg-warning text-dark"><?php echo $pendingCount; ?></span>
                </div>
                <div class="section-panel-body">
                    <?php if (empty($requests)): ?>
                        <div class="approval-empty">
                            <i class="fas fa-inbox"></i>
                            <p class="mb-0 fw-semibold">No pending requests</p>
                            <p class="small mb-0">All HOD-approved hostel requests have been processed.</p>
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
                                    <span class="step-current"><i class="fas fa-circle-notch me-1"></i>Awaiting final approval</span>
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
                                                        <?php if (!empty($request['student_gender'])): ?>
                                                            <span class="text-muted">(<?php echo htmlspecialchars($request['student_gender']); ?>)</span>
                                                        <?php endif; ?>
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

                                    <?php if (!empty($request['hod_comments'])): ?>
                                        <div>
                                            <label class="small text-muted text-uppercase fw-semibold">HOD comments</label>
                                            <div class="detail-note"><?php echo nl2br(htmlspecialchars($request['hod_comments'])); ?></div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($request['hod_approval_date'])): ?>
                                        <p class="small text-muted mb-0 mt-2">
                                            <i class="fas fa-clock me-1"></i>HOD approved <?php echo date('M d, Y h:i A', strtotime($request['hod_approval_date'])); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <form action="<?php echo APP_URL; ?>/on-peak-requests/final-approve" method="POST" class="approval-request-item__actions">
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

            <div class="section-panel mb-0">
                <div class="section-panel-header">
                    <h6><i class="fas fa-history me-2"></i>Approval history</h6>
                    <span class="badge rounded-pill bg-secondary"><?php echo $completedCount; ?></span>
                </div>
                <div class="section-panel-body flush-table">
                    <?php if (empty($completedRequests)): ?>
                        <div class="approval-empty">
                            <i class="fas fa-folder-open"></i>
                            <p class="mb-0 fw-semibold">No completed records yet</p>
                            <p class="small mb-0">Processed requests will appear here.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Student</th>
                                        <th>Department</th>
                                        <th>Exit</th>
                                        <th>Return</th>
                                        <th>Status</th>
                                        <th>HOD</th>
                                        <th>Final approver</th>
                                        <th>Date</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($completedRequests as $i => $row): ?>
                                        <?php
                                        $status = $row['onpeak_request_status'] ?? '';
                                        $isApproved = stripos($status, 'approved') !== false && stripos($status, 'reject') === false;
                                        ?>
                                        <tr>
                                            <td><?php echo $i + 1; ?></td>
                                            <td>
                                                <div class="fw-semibold"><?php echo htmlspecialchars($row['student_fullname']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($row['student_id']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['department_name'] ?? '—'); ?></td>
                                            <td class="small text-nowrap">
                                                <?php if ($row['exit_date']): ?>
                                                    <?php echo date('M d, Y', strtotime($row['exit_date'])); ?>
                                                    <?php if ($row['exit_time']): ?>
                                                        <br><span class="text-muted"><?php echo date('h:i A', strtotime($row['exit_time'])); ?></span>
                                                    <?php endif; ?>
                                                <?php else: ?>—<?php endif; ?>
                                            </td>
                                            <td class="small text-nowrap">
                                                <?php if ($row['return_date']): ?>
                                                    <?php echo date('M d, Y', strtotime($row['return_date'])); ?>
                                                    <?php if ($row['return_time']): ?>
                                                        <br><span class="text-muted"><?php echo date('h:i A', strtotime($row['return_time'])); ?></span>
                                                    <?php endif; ?>
                                                <?php else: ?>—<?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($isApproved): ?>
                                                    <span class="badge bg-success">Approved</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Rejected</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="small">
                                                <?php echo htmlspecialchars($row['hod_approver_name'] ?? '—'); ?>
                                                <?php if (!empty($row['hod_approval_date'])): ?>
                                                    <div class="text-muted"><?php echo date('M d, Y', strtotime($row['hod_approval_date'])); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="small">
                                                <?php echo htmlspecialchars($row['second_approver_name'] ?? '—'); ?>
                                                <?php if (!empty($row['second_approver_role'])): ?>
                                                    <span class="badge bg-light text-dark border ms-1"><?php echo htmlspecialchars($row['second_approver_role']); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="small text-nowrap">
                                                <?php echo !empty($row['second_approval_date'])
                                                    ? date('M d, Y h:i A', strtotime($row['second_approval_date']))
                                                    : '—'; ?>
                                            </td>
                                            <td class="text-end">
                                                <a href="<?php echo APP_URL; ?>/on-peak-requests/view?id=<?php echo (int)$row['id']; ?>" class="btn btn-sm btn-outline-primary" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
