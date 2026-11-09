<?php require BASE_PATH . '/views/facilities/partials/helpers.php';
$t = $ticket;
$id = (int) $t['id'];
$workOpen = in_array($t['status'], ['assigned', 'accepted', 'in_progress', 'reopened', 'on_hold'], true);
$place = trim(($t['building'] ?? '') . ' ' . ($t['floor'] ?? '') . ' ' . ($t['room_area'] ?? ''));
?>
<div class="container-fluid px-0 fac-page">
    <div class="card border-0">
        <?php $facHeader($t['ticket_number'], (string) $t['title'], $badgeStatus($t['status']) . ' ' . $badgePriority($t['current_priority'])); ?>
        <div class="card-body">
            <?php $facNav(); $facFlash(); ?>

            <h6 class="fw-bold mb-1"><?php echo $h($t['title']); ?></h6>
            <p class="mb-2"><?php echo nl2br($h($t['description'])); ?></p>
            <?php if ($place !== '' || !empty($t['remarks'])): ?>
                <p class="fac-note"><?php echo $h($place); ?><?php echo !empty($t['remarks']) ? ' · ' . $h($t['remarks']) : ''; ?></p>
            <?php endif; ?>

            <?php
            $rm = $roadmap ?? null;
            if ($rm):
                $stateLabel = ['done' => 'Completed', 'current' => 'In progress', 'pending' => 'Pending', 'skipped' => 'Not required', 'stopped' => 'Stopped'];
                $fmtDate = static function ($d) { return $d ? date('d M Y, H:i', strtotime((string) $d)) : ''; };
            ?>
            <section class="fac-rm" aria-label="Ticket workflow">
                <div class="fac-rm-top">
                    <div>
                        <div class="fac-rm-kicker">Workflow status</div>
                        <div class="fac-rm-summary"><?php echo $h($rm['summary']); ?></div>
                    </div>
                    <div class="fac-rm-stats">
                        <span><strong><?php echo (int) $rm['done']; ?>/<?php echo (int) $rm['total']; ?></strong> stages</span>
                        <?php if ($rm['age'] !== ''): ?><span><?php echo $h($rm['age_label']); ?> <strong><?php echo $h($rm['age']); ?></strong></span><?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($rm['terminal'])): ?>
                <div class="fac-rm-stop">
                    <i class="fas fa-circle-xmark"></i>
                    <div>
                        <strong><?php echo $h($rm['terminal']['label']); ?></strong>
                        <?php if (!empty($rm['terminal']['date'])): ?> &middot; <?php echo $h($fmtDate($rm['terminal']['date'])); ?><?php endif; ?>
                        <?php if (!empty($rm['terminal']['actor'])): ?> &middot; <?php echo $h($rm['terminal']['actor']); ?><?php endif; ?>
                        <?php if (!empty($rm['terminal']['remarks'])): ?><div class="small"><?php echo $h($rm['terminal']['remarks']); ?></div><?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <ol class="fac-rm-steps">
                    <?php foreach ($rm['stages'] as $i => $st): ?>
                    <li class="is-<?php echo $h($st['state']); ?>">
                        <span class="fac-rm-dot"><?php if ($st['state'] === 'done'): ?><i class="fas fa-check"></i><?php elseif ($st['state'] === 'stopped'): ?><i class="fas fa-xmark"></i><?php elseif ($st['state'] === 'skipped'): ?><i class="fas fa-minus"></i><?php else: ?><i class="fas <?php echo $h($st['icon']); ?>"></i><?php endif; ?></span>
                        <b><?php echo $h($st['title']); ?></b>
                        <small><?php echo $st['state'] === 'done' && !empty($st['date']) ? $h(date('d M', strtotime((string) $st['date']))) : $h($stateLabel[$st['state']]); ?></small>
                    </li>
                    <?php endforeach; ?>
                </ol>

                <ol class="fac-road">
                    <?php foreach ($rm['stages'] as $i => $st): ?>
                    <li class="fac-road-item is-<?php echo $h($st['state']); ?>">
                        <span class="fac-road-node"><i class="fas <?php echo $h($st['icon']); ?>"></i></span>
                        <div class="fac-road-card">
                            <div class="fac-road-head">
                                <h6><span class="fac-road-no"><?php echo $i + 1; ?></span><?php echo $h($st['title']); ?><?php if (!empty($st['optional'])): ?> <span class="fac-road-opt">Optional</span><?php endif; ?></h6>
                                <span class="fac-road-chip"><?php echo $h($stateLabel[$st['state']]); ?></span>
                            </div>

                            <?php if ($st['state'] === 'done' && (!empty($st['date']) || !empty($st['actor']))): ?>
                            <div class="fac-road-when">
                                <i class="far fa-clock"></i>
                                <?php echo $h($fmtDate($st['date'] ?? null)); ?>
                                <?php if (!empty($st['actor'])): ?> &middot; <?php echo $h($st['actor']); ?><?php endif; ?>
                                <?php if (!empty($st['took'])): ?><span class="fac-road-took">+<?php echo $h($st['took']); ?></span><?php endif; ?>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($st['steps'])): ?>
                            <ol class="fac-road-mini">
                                <?php foreach ($st['steps'] as $ms): ?>
                                <li class="<?php echo $ms[1] ? 'is-on' : ''; ?>">
                                    <i class="fas <?php echo $ms[1] ? 'fa-circle-check' : 'fa-circle'; ?>"></i>
                                    <?php echo $h($ms[0]); ?>
                                    <?php if ($ms[1] && !empty($ms[2])): ?><small><?php echo $h(date('d M', strtotime((string) $ms[2]))); ?></small><?php endif; ?>
                                </li>
                                <?php endforeach; ?>
                            </ol>
                            <?php endif; ?>

                            <?php if (!empty($st['facts'])): ?>
                            <dl class="fac-road-facts">
                                <?php foreach ($st['facts'] as $f): ?>
                                <div><dt><?php echo $h($f[0]); ?></dt><dd><?php echo nl2br($h($f[1])); ?><?php if ($f[0] === 'Deadline' && !empty($st['due'])) { echo ' ' . $dueLabel($t); } ?></dd></div>
                                <?php endforeach; ?>
                            </dl>
                            <?php endif; ?>

                            <?php if (isset($st['progress']) && $st['progress'] !== null && $st['state'] !== 'pending'): ?>
                            <div class="fac-progress mb-2"><span style="width:<?php echo (int) $st['progress']; ?>%"></span></div>
                            <?php endif; ?>

                            <?php if (!empty($st['items'])): ?>
                            <div class="fac-road-sub">Materials used</div>
                            <div class="fac-road-chips">
                                <?php foreach ($st['items'] as $it): ?><span><?php echo $h($it); ?></span><?php endforeach; ?>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($st['files'])): ?>
                            <div class="fac-road-sub"><?php echo $st['key'] === 'work' ? 'Invoices and reports' : 'Attachments'; ?></div>
                            <div class="fac-road-chips">
                                <?php foreach ($st['files'] as $ev): ?>
                                <a href="<?php echo $base; ?>/download?id=<?php echo $id; ?>&file=<?php echo (int) $ev['id']; ?>" target="_blank"><i class="fas fa-paperclip"></i> <?php echo $h($ev['original_name']); ?></a>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($st['note'])): ?>
                            <div class="fac-road-note"><i class="fas fa-circle-info"></i> <?php echo $h($st['note']); ?></div>
                            <?php endif; ?>

                            <?php if ($st['key'] === 'procure' && !empty($st['row']) && in_array($st['row']['status'], ['requested', 'ordered'], true)):
                                $pr = $st['row'];
                                $canCancelProc = !empty($can_process_procurement) || (string) $pr['requested_by'] === (string) ($actor['staff_id'] ?? '');
                            ?>
                            <?php if (!empty($can_process_procurement)): ?>
                            <div class="fac-road-forms">
                                <?php if ($pr['status'] === 'requested'): ?>
                                <form method="post" action="<?php echo $base; ?>/procure-order" class="fac-road-form">
                                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                                    <input type="hidden" name="procurement_id" value="<?php echo (int) $pr['id']; ?>">
                                    <h6>Order details</h6>
                                    <div class="row g-2">
                                        <div class="col-md-5"><input class="form-control form-control-sm" name="supplier" placeholder="Supplier" required></div>
                                        <div class="col-md-3"><input type="date" class="form-control form-control-sm" name="expected_date" title="Expected delivery date"></div>
                                        <div class="col-md-4"><input class="form-control form-control-sm" name="remarks" placeholder="Remarks"></div>
                                    </div>
                                    <button class="btn btn-sm btn-primary mt-2">Record order</button>
                                </form>
                                <?php endif; ?>
                                <form method="post" action="<?php echo $base; ?>/procure-receive" class="fac-road-form">
                                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                                    <input type="hidden" name="procurement_id" value="<?php echo (int) $pr['id']; ?>">
                                    <h6>Goods receipt</h6>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <input class="form-control form-control-sm" name="remarks" placeholder="Remarks" style="max-width:320px">
                                        <button class="btn btn-sm btn-success">Confirm receipt</button>
                                    </div>
                                </form>
                            </div>
                            <?php endif; ?>
                            <?php if ($canCancelProc): ?>
                            <details class="fac-road-more">
                                <summary>Cancel request</summary>
                                <form method="post" action="<?php echo $base; ?>/procure-cancel" class="d-flex gap-2 flex-wrap mt-2">
                                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                                    <input type="hidden" name="procurement_id" value="<?php echo (int) $pr['id']; ?>">
                                    <input class="form-control form-control-sm" name="reason" placeholder="Reason for cancellation" required style="max-width:320px">
                                    <button class="btn btn-sm btn-outline-danger">Confirm cancellation</button>
                                </form>
                            </details>
                            <?php endif; ?>
                            <?php endif; ?>

                            <?php if ($st['key'] === 'work' && !empty($can_request_procurement) && $st['state'] !== 'done'): ?>
                            <details class="fac-road-more fac-road-request">
                                <summary><i class="fas fa-cart-shopping me-1"></i>Request procurement</summary>
                                <form method="post" action="<?php echo $base; ?>/procure-request" class="mt-2">
                                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                                    <label class="form-label">Items required <span class="text-muted">(one per line, with quantity)</span></label>
                                    <textarea class="form-control form-control-sm mb-2" name="items" rows="3" required></textarea>
                                    <label class="form-label">Reason</label>
                                    <input class="form-control form-control-sm mb-2" name="reason" required>
                                    <button class="btn btn-sm btn-primary">Submit request</button>
                                </form>
                            </details>
                            <?php endif; ?>

                            <?php if ($st['state'] === 'current'): ?>
                            <div class="fac-road-cta">
                                <?php if ($st['key'] === 'review' && (!empty($actor['is_officer']) || !empty($can_assign))): ?>
                                    <a class="btn btn-sm btn-primary" href="<?php echo $base; ?>/review?id=<?php echo $id; ?>">Mark as reviewed</a>
                                <?php endif; ?>
                                <?php if (($st['key'] === 'review' || $st['key'] === 'assign') && !empty($can_assign)): ?>
                                    <a class="btn btn-sm btn-primary" href="<?php echo $base; ?>/assign?id=<?php echo $id; ?>">Assign</a>
                                <?php endif; ?>
                                <?php if ($st['key'] === 'work' && !empty($can_work) && $t['status'] === 'assigned'): ?>
                                    <form method="post" action="<?php echo $base; ?>/accept" class="d-inline"><input type="hidden" name="id" value="<?php echo $id; ?>"><button class="btn btn-sm btn-primary">Accept work</button></form>
                                <?php endif; ?>
                                <?php if ($st['key'] === 'work' && !empty($can_work) && in_array($t['status'], ['accepted', 'assigned'], true)): ?>
                                    <form method="post" action="<?php echo $base; ?>/start" class="d-inline"><input type="hidden" name="id" value="<?php echo $id; ?>"><button class="btn btn-sm btn-outline-primary">Start work</button></form>
                                <?php endif; ?>
                                <?php if ($st['key'] === 'work' && $workOpen && (!empty($can_work) || !empty($actor['is_officer']))): ?>
                                    <a class="btn btn-sm btn-outline-primary" href="#progress">Update progress</a>
                                <?php endif; ?>
                                <?php if ($st['key'] === 'verify' && !empty($can_verify)): ?>
                                    <a class="btn btn-sm btn-success" href="<?php echo $base; ?>/verify?id=<?php echo $id; ?>">Verify work</a>
                                <?php endif; ?>
                                <?php if ($st['key'] === 'close' && !empty($can_close)): ?>
                                    <button type="button" class="btn btn-sm btn-success" data-fac-goto="manage">Close ticket</button>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ol>
            </section>
            <script>
            document.querySelectorAll('[data-fac-goto]').forEach(function (b) {
                b.addEventListener('click', function () {
                    var tab = document.querySelector('[data-fac-tab="' + b.getAttribute('data-fac-goto') + '"]');
                    if (tab) { tab.click(); tab.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
                });
            });
            </script>
            <?php endif; ?>

            <div class="fac-actions">
                <?php if (!empty($can_assign) && !empty($t['responsible_staff_id'])): ?>
                    <a class="btn btn-sm btn-outline-primary" href="<?php echo $base; ?>/assign?id=<?php echo $id; ?>">Reassign</a>
                <?php endif; ?>
                <a class="btn btn-sm btn-outline-secondary" href="<?php echo $base; ?>/print?id=<?php echo $id; ?>" target="_blank">Print</a>
            </div>

            <div data-fac-tabs>
                <div class="fac-tabs">
                    <button type="button" class="fac-tab is-on" data-fac-tab="work">Work</button>
                    <button type="button" class="fac-tab" data-fac-tab="evidence">Attachments</button>
                    <button type="button" class="fac-tab" data-fac-tab="history">History</button>
                    <?php if (!empty($actor['is_officer'])): ?>
                        <button type="button" class="fac-tab" data-fac-tab="manage">Manage</button>
                    <?php endif; ?>
                </div>

                <div data-fac-panel="work">
                    <?php if (!empty($can_confirm)): ?>
                    <form method="post" action="<?php echo $base; ?>/confirm" class="fac-section">
                        <input type="hidden" name="id" value="<?php echo $id; ?>">
                        <h6>Requester confirmation</h6>
                        <textarea class="form-control mb-2" name="comment" placeholder="Comment"></textarea>
                        <button class="btn btn-sm btn-success" name="decision" value="satisfactory">Confirm resolved</button>
                        <button class="btn btn-sm btn-outline-danger" name="decision" value="not_satisfactory">Not resolved - reopen</button>
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
                                        <label class="form-label">Progress</label>
                                        <select class="form-select form-select-sm" name="progress_percent">
                                            <?php foreach ([10,25,50,75,90,100] as $p): ?>
                                                <option value="<?php echo $p; ?>" <?php echo (int) $t['progress_percent'] === $p ? 'selected' : ''; ?>><?php echo $p; ?>%</option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-8">
                                        <label class="form-label">Work update</label>
                                        <input class="form-control form-control-sm" name="work_update" required>
                                    </div>
                                    <div class="col-7">
                                        <label class="form-label">Material used</label>
                                        <input class="form-control form-control-sm" name="material_item">
                                    </div>
                                    <div class="col-5">
                                        <label class="form-label">Quantity</label>
                                        <input class="form-control form-control-sm" name="material_qty">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Attachment</label>
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
                                <h6>Submit completion</h6>
                                <label class="form-label">Work completed</label>
                                <textarea class="form-control form-control-sm" name="description" required></textarea>
                                <div class="row g-2 mt-1">
                                    <div class="col-6"><label class="form-label">Completion date</label><input type="date" class="form-control form-control-sm" name="completed_date" value="<?php echo date('Y-m-d'); ?>"></div>
                                    <div class="col-6"><label class="form-label">Completion evidence</label><input type="file" class="form-control form-control-sm" name="evidence[]" multiple></div>
                                </div>
                                <input type="hidden" name="evidence_type" value="after">
                                <button class="btn btn-sm btn-success mt-2">Submit for verification</button>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($can_work) && empty($actor['is_officer'])): ?>
                    <form method="post" action="<?php echo $base; ?>/deadline" class="fac-section">
                        <input type="hidden" name="id" value="<?php echo $id; ?>">
                        <h6>Deadline extension</h6>
                        <div class="d-flex gap-2 flex-wrap">
                            <input type="date" class="form-control form-control-sm" name="deadline" required style="max-width:160px">
                            <input class="form-control form-control-sm" name="reason" placeholder="Reason" required>
                            <button class="btn btn-sm btn-outline-primary">Request extension</button>
                        </div>
                    </form>
                    <?php endif; ?>

                    <div class="fac-section">
                        <h6>Progress updates</h6>
                        <?php if (!$progress_rows): ?>
                            <div class="fac-empty">No updates recorded.</div>
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
                        <h6>Upload attachment</h6>
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
                                    <input class="form-control form-control-sm mb-2" name="no_evidence_reason" placeholder="Reason if no evidence attached">
                                    <button class="btn btn-sm btn-success">Close ticket</button>
                                </form>
                                <?php endif; ?>
                                <form method="post" action="<?php echo $base; ?>/hold" class="mt-2">
                                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                                    <input class="form-control form-control-sm mb-2" name="reason" placeholder="Reason for hold">
                                    <button class="btn btn-sm btn-outline-secondary">Place on hold</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($feedback)): ?>
            <div class="fac-section">
                <h6>Requester feedback</h6>
                <p class="mb-1">Overall <?php echo (int) $feedback['overall_rating']; ?>/5 · Response <?php echo (int) $feedback['response_rating']; ?>/5 · Quality <?php echo (int) $feedback['quality_rating']; ?>/5 · Behaviour <?php echo (int) $feedback['behaviour_rating']; ?>/5</p>
                <?php if (!empty($feedback['comments'])): ?><p class="mb-0"><?php echo nl2br($h($feedback['comments'])); ?></p><?php endif; ?>
            </div>
            <?php elseif (!empty($can_feedback)): ?>
            <form method="post" action="<?php echo $base; ?>/feedback" class="fac-section">
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                <h6>Service feedback</h6>
                <div class="row g-2">
                    <?php foreach (['overall_rating' => 'Overall', 'response_rating' => 'Response time', 'quality_rating' => 'Quality', 'behaviour_rating' => 'Staff behaviour'] as $name => $label): ?>
                    <div class="col-md-3">
                        <label class="form-label"><?php echo $h($label); ?></label>
                        <select class="form-select form-select-sm" name="<?php echo $h($name); ?>" required>
                            <option value="">1–5</option>
                            <?php for ($star = 1; $star <= 5; $star++): ?>
                                <option value="<?php echo $star; ?>"><?php echo $star; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <?php endforeach; ?>
                    <div class="col-12">
                        <label class="form-label">Comments</label>
                        <textarea class="form-control form-control-sm" name="comments" rows="2"></textarea>
                    </div>
                </div>
                <button class="btn btn-sm btn-primary mt-2">Submit feedback</button>
            </form>
            <?php endif; ?>

            <?php if ($t['status'] === 'closed' && (!empty($can_assign) || $actor['staff_id'] === $t['requester_staff_id'])): ?>
            <form method="post" action="<?php echo $base; ?>/reopen" class="fac-section">
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                <h6>Reopen</h6>
                <div class="d-flex gap-2">
                    <input class="form-control form-control-sm" name="reason" placeholder="Reason for reopening" required>
                    <button class="btn btn-sm btn-outline-danger">Reopen</button>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>
