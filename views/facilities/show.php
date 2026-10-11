<?php require BASE_PATH . '/views/facilities/partials/helpers.php';
$t = $ticket;
$id = (int) $t['id'];
$workOpen = in_array($t['status'], ['assigned', 'accepted', 'in_progress', 'reopened', 'on_hold'], true);
$place = trim(($t['building'] ?? '') . ' ' . ($t['floor'] ?? '') . ' ' . ($t['room_area'] ?? ''));
?>
<div class="container-fluid px-0 fac-page">
    <div class="card border-0">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="fw-bold mb-0"><?php echo $h($t['ticket_number']); ?></h5>
            <div><?php echo $badgeStatus($t['status']); ?> <?php echo $badgePriority($t['current_priority']); ?></div>
        </div>
        <div class="card-body">
            <?php $facNav(); $facFlash(); ?>

            <h6 class="fw-bold mb-1"><?php echo $h($t['title']); ?></h6>
            <p class="mb-2"><?php echo nl2br($h($t['description'])); ?></p>
            <?php if ($place !== '' || !empty($t['remarks'])): ?>
                <p class="fac-note"><?php echo $h($place); ?><?php echo !empty($t['remarks']) ? ' · ' . $h($t['remarks']) : ''; ?></p>
            <?php endif; ?>

            <div class="fac-meta">
                <div><span>Requester</span><strong><?php echo $h($t['requester_name'] ?? $t['requester_staff_id']); ?></strong></div>
                <div><span>Location</span><strong><?php echo $h($t['location']); ?></strong></div>
                <div><span>Assigned to</span><strong><?php echo $h($t['responsible_name'] ?? $t['vendor_name'] ?? 'Not assigned'); ?></strong></div>
                <div>
                    <span>Deadline · <?php echo (int) $t['progress_percent']; ?>%</span>
                    <strong><?php echo $h($t['deadline'] ?? '—'); ?></strong>
                    <?php echo $dueLabel($t); ?>
                    <div class="fac-progress mt-1"><span style="width:<?php echo (int) $t['progress_percent']; ?>%"></span></div>
                </div>
            </div>

            <div class="fac-actions">
                <?php if (!empty($can_work) && $t['status'] === 'assigned'): ?>
                    <form method="post" action="<?php echo $base; ?>/accept"><input type="hidden" name="id" value="<?php echo $id; ?>"><button class="btn btn-sm btn-primary">Accept work</button></form>
                <?php endif; ?>
                <?php if (!empty($can_work) && in_array($t['status'], ['accepted', 'assigned'], true)): ?>
                    <form method="post" action="<?php echo $base; ?>/start"><input type="hidden" name="id" value="<?php echo $id; ?>"><button class="btn btn-sm btn-outline-primary">Start work</button></form>
                <?php endif; ?>
                <?php if (!empty($actor['is_officer'])): ?>
                    <a class="btn btn-sm btn-primary" href="<?php echo $base; ?>/assign?id=<?php echo $id; ?>"><?php echo empty($t['responsible_staff_id']) ? 'Assign' : 'Reassign'; ?></a>
                    <?php if ($t['status'] === 'new'): ?>
                        <a class="btn btn-sm btn-outline-primary" href="<?php echo $base; ?>/review?id=<?php echo $id; ?>">Mark as reviewed</a>
                    <?php endif; ?>
                    <?php if (!empty($can_verify)): ?>
                        <a class="btn btn-sm btn-success" href="<?php echo $base; ?>/verify?id=<?php echo $id; ?>">Verify work</a>
                    <?php endif; ?>
                <?php endif; ?>
                <a class="btn btn-sm btn-outline-secondary" href="<?php echo $base; ?>/print?id=<?php echo $id; ?>" target="_blank">Print</a>
            </div>

            <div data-fac-tabs>
                <div class="fac-tabs">
                    <button type="button" class="fac-tab is-on" data-fac-tab="work">Work</button>
                    <button type="button" class="fac-tab" data-fac-tab="evidence">Photos</button>
                    <button type="button" class="fac-tab" data-fac-tab="history">History</button>
                    <?php if (!empty($actor['is_officer'])): ?>
                        <button type="button" class="fac-tab" data-fac-tab="manage">Manage</button>
                    <?php endif; ?>
                </div>

                <div data-fac-panel="work">
                    <?php if (!empty($can_confirm)): ?>
                    <form method="post" action="<?php echo $base; ?>/confirm" class="fac-section">
                        <input type="hidden" name="id" value="<?php echo $id; ?>">
                        <h6>Is the work OK?</h6>
                        <textarea class="form-control mb-2" name="comment" placeholder="Optional comment"></textarea>
                        <button class="btn btn-sm btn-success" name="decision" value="satisfactory">Yes, it is fixed</button>
                        <button class="btn btn-sm btn-outline-danger" name="decision" value="not_satisfactory">No, reopen</button>
                    </form>
                    <?php endif; ?>

                    <?php if ($workOpen && (!empty($can_work) || !empty($actor['is_officer']))): ?>
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <form method="post" action="<?php echo $base; ?>/progress" enctype="multipart/form-data" class="fac-section" id="progress">
                                <input type="hidden" name="id" value="<?php echo $id; ?>">
                                <h6>Update progress</h6>
                                <div class="row g-2">
                                    <div class="col-4">
                                        <label class="form-label">Done</label>
                                        <select class="form-select form-select-sm" name="progress_percent">
                                            <?php foreach ([10,25,50,75,90,100] as $p): ?>
                                                <option value="<?php echo $p; ?>" <?php echo (int) $t['progress_percent'] === $p ? 'selected' : ''; ?>><?php echo $p; ?>%</option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-8">
                                        <label class="form-label">What did you do?</label>
                                        <input class="form-control form-control-sm" name="work_update" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Photo (optional)</label>
                                        <input type="file" class="form-control form-control-sm" name="evidence[]" multiple>
                                        <input type="hidden" name="evidence_type" value="during">
                                    </div>
                                </div>
                                <button class="btn btn-sm btn-primary mt-2">Save update</button>
                            </form>
                        </div>
                        <div class="col-lg-6">
                            <form method="post" action="<?php echo $base; ?>/complete" enctype="multipart/form-data" class="fac-section">
                                <input type="hidden" name="id" value="<?php echo $id; ?>">
                                <h6>Work finished</h6>
                                <p class="fac-help">Add a photo of the finished work.</p>
                                <label class="form-label">What was completed?</label>
                                <textarea class="form-control form-control-sm" name="description" required></textarea>
                                <div class="row g-2 mt-1">
                                    <div class="col-6"><label class="form-label">Date</label><input type="date" class="form-control form-control-sm" name="completed_date" value="<?php echo date('Y-m-d'); ?>"></div>
                                    <div class="col-6"><label class="form-label">After photo</label><input type="file" class="form-control form-control-sm" name="evidence[]" multiple></div>
                                </div>
                                <input type="hidden" name="evidence_type" value="after">
                                <button class="btn btn-sm btn-success mt-2">Submit for check</button>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($can_work) && empty($actor['is_officer'])): ?>
                    <form method="post" action="<?php echo $base; ?>/deadline" class="fac-section">
                        <input type="hidden" name="id" value="<?php echo $id; ?>">
                        <h6>Need more time?</h6>
                        <div class="d-flex gap-2 flex-wrap">
                            <input type="date" class="form-control form-control-sm" name="deadline" required style="max-width:160px">
                            <input class="form-control form-control-sm" name="reason" placeholder="Reason" required>
                            <button class="btn btn-sm btn-outline-primary">Ask for extension</button>
                        </div>
                    </form>
                    <?php endif; ?>

                    <div class="fac-section">
                        <h6>Updates</h6>
                        <?php if (!$progress_rows): ?>
                            <div class="fac-empty">No updates yet.</div>
                        <?php else: ?>
                        <ul class="fac-timeline">
                            <?php foreach ($progress_rows as $p): ?>
                                <li>
                                    <strong><?php echo (int) $p['progress_percent']; ?>% — <?php echo $h($p['work_update']); ?></strong>
                                    <span><?php echo $h($p['created_at']); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    </div>
                </div>

                <div data-fac-panel="evidence" hidden>
                    <div class="fac-evidence-grid mb-3">
                        <?php foreach (['before' => 'Before', 'during' => 'During', 'after' => 'After'] as $ek => $el): ?>
                        <div class="fac-ev-box">
                            <h4><?php echo $h($el); ?></h4>
                            <?php $set = array_merge($evidence[$ek] ?? [], $ek === 'after' ? ($evidence['completion'] ?? []) : [], $ek === 'before' ? ($evidence['initial'] ?? []) : []); ?>
                            <?php if (!$set): ?><div class="text-muted small">None</div>
                            <?php else: foreach ($set as $ev): ?>
                                <div><a href="<?php echo $base; ?>/download?id=<?php echo $id; ?>&file=<?php echo (int) $ev['id']; ?>" target="_blank"><?php echo $h($ev['original_name']); ?></a></div>
                            <?php endforeach; endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (!in_array($t['status'], ['closed', 'cancelled'], true)): ?>
                    <form method="post" action="<?php echo $base; ?>/evidence" enctype="multipart/form-data" class="fac-section">
                        <input type="hidden" name="id" value="<?php echo $id; ?>">
                        <h6>Add a file</h6>
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label">Type</label>
                                <select class="form-select form-select-sm" name="evidence_type">
                                    <option value="before">Before</option>
                                    <option value="during">During</option>
                                    <option value="after">After</option>
                                    <option value="invoice">Invoice</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6"><label class="form-label">File</label><input type="file" class="form-control form-control-sm" name="evidence[]" multiple required></div>
                            <div class="col-md-3"><button class="btn btn-sm btn-outline-primary">Upload</button></div>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>

                <div data-fac-panel="history" hidden>
                    <?php if (!empty($can_comment)): ?>
                    <form method="post" action="<?php echo $base; ?>/comment" class="fac-section">
                        <input type="hidden" name="id" value="<?php echo $id; ?>">
                        <h6>Comment</h6>
                        <textarea class="form-control form-control-sm" name="comment" required></textarea>
                        <button class="btn btn-sm btn-outline-primary mt-2">Add comment</button>
                    </form>
                    <?php endif; ?>
                    <?php if ($comments): ?>
                    <div class="fac-section">
                        <h6>Comments</h6>
                        <?php foreach ($comments as $cmt): ?>
                            <p class="mb-2 small"><strong><?php echo $h($cmt['created_by']); ?></strong> · <?php echo $h($cmt['created_at']); ?><br><?php echo nl2br($h($cmt['comment'])); ?></p>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <div class="fac-section">
                        <h6>Activity</h6>
                        <ul class="fac-timeline">
                            <?php foreach ($timeline as $ev): ?>
                                <li>
                                    <strong><?php echo $h($ev['action']); ?></strong>
                                    <span><?php echo $h($ev['created_at']); ?>
                                    <?php if ($ev['remarks']): ?> · <?php echo $h($ev['remarks']); ?><?php endif; ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <?php if (!empty($actor['is_officer'])): ?>
                <div data-fac-panel="manage" hidden>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <form method="post" action="<?php echo $base; ?>/priority" class="fac-section">
                                <input type="hidden" name="id" value="<?php echo $id; ?>">
                                <h6>Priority</h6>
                                <select class="form-select form-select-sm mb-2" name="priority"><?php foreach ($prLabel as $k => $lab): ?><option value="<?php echo $h($k); ?>" <?php echo $t['current_priority'] === $k ? 'selected' : ''; ?>><?php echo $h($lab); ?></option><?php endforeach; ?></select>
                                <input class="form-control form-control-sm mb-2" name="reason" placeholder="Reason" required>
                                <button class="btn btn-sm btn-outline-primary">Update</button>
                            </form>
                        </div>
                        <div class="col-md-4">
                            <form method="post" action="<?php echo $base; ?>/deadline" class="fac-section">
                                <input type="hidden" name="id" value="<?php echo $id; ?>">
                                <h6>Deadline</h6>
                                <input type="date" class="form-control form-control-sm mb-2" name="deadline" value="<?php echo $h($t['deadline'] ?? ''); ?>" required>
                                <input class="form-control form-control-sm mb-2" name="reason" placeholder="Reason" required>
                                <button class="btn btn-sm btn-outline-primary">Update</button>
                            </form>
                        </div>
                        <div class="col-md-4">
                            <div class="fac-section">
                                <?php if (!empty($can_close)): ?>
                                <form method="post" action="<?php echo $base; ?>/close">
                                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                                    <h6>Close</h6>
                                    <input class="form-control form-control-sm mb-2" name="remarks" placeholder="Remarks">
                                    <input class="form-control form-control-sm mb-2" name="no_evidence_reason" placeholder="Reason if no photo">
                                    <button class="btn btn-sm btn-success">Close ticket</button>
                                </form>
                                <?php endif; ?>
                                <form method="post" action="<?php echo $base; ?>/hold" class="mt-2">
                                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                                    <input class="form-control form-control-sm mb-2" name="reason" placeholder="Hold reason">
                                    <button class="btn btn-sm btn-outline-secondary">Put on hold</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($t['status'] === 'closed' && (!empty($actor['is_officer']) || $actor['staff_id'] === $t['requester_staff_id'])): ?>
            <form method="post" action="<?php echo $base; ?>/reopen" class="fac-section">
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                <h6>Reopen</h6>
                <div class="d-flex gap-2">
                    <input class="form-control form-control-sm" name="reason" placeholder="Why?" required>
                    <button class="btn btn-sm btn-outline-danger">Reopen</button>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>
