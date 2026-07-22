<?php
/**
 * Staff online applications — table markup (admin list + AJAX refresh).
 *
 * @param list<array<string, mixed>> $rows
 * @param callable(string):string $esc
 * @param callable(int):string $viewUrl
 * @param callable(int):string $editUrl
 * @param string $deleteAction
 * @param bool $can_delete
 * @param bool $can_edit
 * @param callable(?string):array{order:string,display:string} $formatSubmitted
 * @param bool $can_update_rejection_reason
 * @param string $update_reason_action
 * @param string $rejection_reason_return_path
 */
function sa_admin_student_applications_render_table(
    array $rows,
    callable $esc,
    callable $viewUrl,
    callable $editUrl,
    string $deleteAction,
    bool $can_delete,
    bool $can_edit,
    callable $formatSubmitted,
    string $statusLabel,
    string $badgeClass,
    int $rowNumBase = 0,
    int $filterCoursePriority = 1,
    bool $can_update_rejection_reason = false,
    string $update_reason_action = '',
    string $rejection_reason_return_path = 'student-applications?tab=rejected'
): void {
    $filterCoursePriority = in_array($filterCoursePriority, [1, 2, 3], true) ? $filterCoursePriority : 1;
    $isRejectedTab = ($statusLabel === 'rejected');
    $colCount = $isRejectedTab ? 14 : 13;
    $choiceThClass = static function (int $n) use ($filterCoursePriority): string {
        return $n === $filterCoursePriority ? ' sa-apps-col-choice sa-apps-col-choice--active' : ' sa-apps-col-choice';
    };
    $choiceTdClass = static function (int $n) use ($filterCoursePriority): string {
        return $n === $filterCoursePriority ? ' sa-apps-col-choice sa-apps-col-choice--active' : ' sa-apps-col-choice text-muted';
    };
    ?>
    <div class="table-responsive sa-apps-table-responsive">
        <table class="table table-striped table-hover table-bordered align-middle w-100 mb-0 sa-apps-table<?php echo $isRejectedTab ? ' sa-apps-table--rejected' : ''; ?>">
            <colgroup>
                <col class="sa-apps-col-num">
                <col class="sa-apps-col-level">
                <col class="sa-apps-col-status">
                <?php if ($isRejectedTab): ?>
                <col class="sa-apps-col-reason">
                <?php endif; ?>
                <col class="sa-apps-col-name">
                <col class="sa-apps-col-nic">
                <col class="sa-apps-col-choice">
                <col class="sa-apps-col-choice">
                <col class="sa-apps-col-choice">
                <col class="sa-apps-col-district">
                <col class="sa-apps-col-email">
                <col class="sa-apps-col-phone">
                <col class="sa-apps-col-date">
                <col class="sa-apps-col-actions">
            </colgroup>
            <thead class="table-light">
                <tr>
                    <th scope="col" class="sa-apps-col-num">#</th>
                    <th scope="col" class="sa-apps-col-level">Level</th>
                    <th scope="col" class="sa-apps-col-status">Status</th>
                    <?php if ($isRejectedTab): ?>
                    <th scope="col" class="sa-apps-col-reason">Rejection reason</th>
                    <?php endif; ?>
                    <th scope="col" class="sa-apps-col-name">Full name</th>
                    <th scope="col" class="sa-apps-col-nic">NIC</th>
                    <th scope="col" class="<?php echo trim('sa-apps-col-choice' . $choiceThClass(1)); ?>">1st choice</th>
                    <th scope="col" class="<?php echo trim('sa-apps-col-choice' . $choiceThClass(2)); ?>">2nd choice</th>
                    <th scope="col" class="<?php echo trim('sa-apps-col-choice' . $choiceThClass(3)); ?>">3rd choice</th>
                    <th scope="col" class="sa-apps-col-district">District</th>
                    <th scope="col" class="sa-apps-col-email">Email</th>
                    <th scope="col" class="sa-apps-col-phone">Phone</th>
                    <th scope="col" class="sa-apps-col-date">Date sent</th>
                    <th scope="col" class="sa-apps-col-actions"><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows === []): ?>
                <tr>
                    <td colspan="<?php echo (int) $colCount; ?>" class="sa-apps-empty text-secondary text-center py-5 px-3">No <?php echo $esc($statusLabel); ?> applications<?php
                        if ($statusLabel === 'new') {
                            echo ' to review';
                        } elseif ($statusLabel === 'rejected') {
                            echo ' for this view';
                        }
                    ?>.</td>
                </tr>
                <?php else: ?>
                    <?php
                    $rowIx = 0;
                    foreach ($rows as $r):
                        $rowIx++;
                        $id = (int) ($r['application_id'] ?? 0);
                        $seq = $rowNumBase + $rowIx;
                        $submitted = $formatSubmitted(isset($r['created_at']) ? (string) $r['created_at'] : null);
                        $waDigits = StudentModel::digitsForWhatsAppMe($r);
                        $rejectReason = trim((string) ($r['rejection_reason'] ?? ''));
                        $reasonPreview = $rejectReason;
                        if (strlen($reasonPreview) > 120) {
                            $reasonPreview = substr($reasonPreview, 0, 117) . '…';
                        }
                        ?>
                <tr>
                    <td class="sa-apps-col-num"><?php echo (int) $seq; ?></td>
                    <td class="sa-apps-col-level"><?php echo $esc((string) ($r['application_level'] ?? '')); ?></td>
                    <td class="sa-apps-col-status"><span class="badge rounded-pill px-2 <?php echo $esc($badgeClass); ?>"><?php echo $esc(ucfirst($statusLabel)); ?></span></td>
                    <?php if ($isRejectedTab): ?>
                    <td class="sa-apps-col-reason small">
                        <?php if ($rejectReason !== ''): ?>
                        <span class="sa-reason-text" title="<?php echo $esc($rejectReason); ?>"><?php echo $esc($reasonPreview); ?></span>
                        <?php else: ?>
                        <span class="badge bg-warning text-dark">Missing reason</span>
                        <?php endif; ?>
                        <?php if ($can_update_rejection_reason && $update_reason_action !== ''): ?>
                        <details class="sa-reason-update mt-1">
                            <summary class="text-primary user-select-none" style="cursor:pointer;">Update reason</summary>
                            <form method="post" action="<?php echo $esc($update_reason_action); ?>" class="mt-2">
                                <input type="hidden" name="application_id" value="<?php echo $id; ?>">
                                <input type="hidden" name="return_path" value="<?php echo $esc($rejection_reason_return_path); ?>">
                                <label class="visually-hidden" for="sa-reason-<?php echo $id; ?>">Rejection reason</label>
                                <textarea class="form-control form-control-sm mb-1" name="rejection_reason" id="sa-reason-<?php echo $id; ?>" rows="3" required maxlength="2000" placeholder="Enter rejection reason…"><?php echo $esc($rejectReason); ?></textarea>
                                <button type="submit" class="btn btn-sm btn-outline-primary">Save reason</button>
                            </form>
                        </details>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                    <td class="sa-apps-col-name" title="<?php echo $esc((string) ($r['student_full_name'] ?? '')); ?>"><?php echo $esc((string) ($r['student_full_name'] ?? '')); ?></td>
                    <td class="sa-apps-col-nic"><?php echo $esc((string) ($r['student_nic'] ?? '')); ?></td>
                    <td class="<?php echo trim('sa-apps-col-choice' . $choiceTdClass(1)); ?>" title="<?php echo $esc((string) ($r['course_choice_1'] ?? '')); ?>"><?php echo $esc((string) ($r['course_choice_1'] ?? '')); ?></td>
                    <td class="<?php echo trim('sa-apps-col-choice' . $choiceTdClass(2)); ?>" title="<?php echo $esc((string) ($r['course_choice_2'] ?? '')); ?>"><?php echo $esc((string) ($r['course_choice_2'] ?? '')); ?></td>
                    <td class="<?php echo trim('sa-apps-col-choice' . $choiceTdClass(3)); ?>" title="<?php echo $esc((string) ($r['course_choice_3'] ?? '')); ?>"><?php echo $esc((string) ($r['course_choice_3'] ?? '')); ?></td>
                    <td class="sa-apps-col-district"><?php echo $esc((string) ($r['student_district'] ?? '')); ?></td>
                    <td class="sa-apps-col-email" title="<?php echo $esc((string) ($r['student_email'] ?? '')); ?>"><?php echo $esc((string) ($r['student_email'] ?? '')); ?></td>
                    <td class="sa-apps-col-phone"><?php echo $esc((string) ($r['student_phone'] ?? '')); ?></td>
                    <td class="sa-apps-col-date" data-order="<?php echo $submitted['order']; ?>"><?php echo $submitted['display']; ?></td>
                    <td class="sa-apps-col-actions">
                        <?php if ($can_delete): ?>
                        <form id="sa-app-del-<?php echo $id; ?>" method="post" action="<?php echo $deleteAction; ?>" class="d-none"
                              onsubmit="return confirm('Delete application #<?php echo $id; ?>? This will also remove uploaded documents on the server.');">
                            <input type="hidden" name="application_id" value="<?php echo $id; ?>">
                        </form>
                        <?php endif; ?>
                        <div class="btn-group btn-group-sm sa-apps-table-actions" role="group" aria-label="Application #<?php echo $id; ?> actions">
                            <a class="btn btn-outline-primary" href="<?php echo $viewUrl($id); ?>"
                               title="View application">
                                <i class="fas fa-eye" aria-hidden="true"></i>
                                <span class="visually-hidden"> View</span>
                            </a>
                            <?php if ($can_edit): ?>
                            <a class="btn btn-outline-secondary" href="<?php echo $editUrl($id); ?>"
                               title="Edit application data">
                                <i class="fas fa-pen" aria-hidden="true"></i>
                                <span class="visually-hidden"> Edit</span>
                            </a>
                            <?php endif; ?>
                            <?php if ($waDigits !== null): ?>
                            <a class="btn btn-wa-outline" href="<?php echo $esc('https://wa.me/' . $waDigits); ?>"
                               target="_blank" rel="noopener noreferrer"
                               title="WhatsApp <?php echo $esc($waDigits); ?>">
                                <i class="fab fa-whatsapp" aria-hidden="true"></i>
                                <span class="visually-hidden"> WhatsApp</span>
                            </a>
                            <?php endif; ?>
                            <?php if ($can_delete): ?>
                            <button type="submit" form="sa-app-del-<?php echo $id; ?>" class="btn btn-outline-danger" title="Delete application">
                                <i class="fas fa-trash-alt" aria-hidden="true"></i>
                                <span class="visually-hidden"> Delete</span>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}
