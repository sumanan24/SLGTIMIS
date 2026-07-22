<?php
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
$sch = $schedule ?? [];
$isInterview = ($sch['schedule_type'] ?? '') === ApplicationAdmissionScheduleModel::TYPE_INTERVIEW;
$rollCourseCode = $rollCourseCode ?? ApplicationAdmissionScheduleModel::rollIndexCourseCodeFromSchedule($sch);
$entryCount = is_array($entries ?? null) ? count($entries) : 0;
$waByEntry = [];
foreach ($whatsAppRecipients ?? [] as $wr) {
    if (!empty($wr['entry_id'])) {
        $waByEntry[(int) $wr['entry_id']] = $wr;
    }
}
?>
<style>
.admission-entries-page-wrap {
    max-width: 1400px;
    margin: 0 auto;
}

.admission-entries-header {
    margin-bottom: 1.25rem;
}

.admission-entries-header .page-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.35rem;
}

.admission-entries-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.admission-entries-meta .meta-chip {
    font-size: 0.8125rem;
    padding: 0.25rem 0.65rem;
    border-radius: 999px;
    background: #f1f3f5;
    color: #495057;
    border: 1px solid #e9ecef;
}

.admission-entries-actions .btn {
    min-height: 32px;
    font-size: 0.875rem;
}

.admission-entries-summary {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.625rem 1rem;
    margin-bottom: 0.75rem;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    font-size: 0.875rem;
}

.admission-entries-summary strong {
    font-weight: 600;
}

.admission-picker-card {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    overflow: hidden;
    margin-bottom: 1rem;
}

.admission-picker-card .card-header {
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    padding: 0.625rem 1rem;
    font-size: 0.875rem;
    font-weight: 600;
}

.admission-picker-scroll {
    max-height: 220px;
    overflow: auto;
}

.admission-entries-table,
.admission-picker-table {
    table-layout: fixed;
    width: 100%;
    margin-bottom: 0;
    border-collapse: separate;
    border-spacing: 0;
}

.admission-entries-table col.col-remove { width: 40px; }
.admission-entries-table col.col-no { width: 48px; }
.admission-entries-table col.col-name { width: 20%; }
.admission-entries-table col.col-nic { width: 11%; }
.admission-entries-table col.col-course { width: 22%; }
.admission-entries-table col.col-roll { width: 11%; }
.admission-entries-table col.col-room { width: 10%; }
.admission-entries-table col.col-whatsapp { width: 11rem; }
.admission-entries-table col.col-sent { width: 80px; }

.admission-entries-table-readonly col.col-no { width: 48px; }
.admission-entries-table-readonly col.col-name { width: 24%; }
.admission-entries-table-readonly col.col-nic { width: 12%; }
.admission-entries-table-readonly col.col-course { width: 26%; }
.admission-entries-table-readonly col.col-roll { width: 12%; }
.admission-entries-table-readonly col.col-room { width: 12%; }
.admission-entries-table-readonly col.col-sent { width: 64px; }

.admission-picker-table col.col-pick { width: 40px; }
.admission-picker-table col.col-no { width: 48px; }
.admission-picker-table col.col-name { width: 32%; }
.admission-picker-table col.col-nic { width: 18%; }
.admission-picker-table col.col-course { width: auto; }

.admission-entries-table thead th,
.admission-picker-table thead th {
    padding: 0.625rem 0.75rem;
    font-size: 0.6875rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #495057;
    vertical-align: middle;
    border-bottom: 2px solid #dee2e6;
    background-color: #f8f9fa;
    white-space: nowrap;
}

.admission-entries-table tbody td,
.admission-picker-table tbody td {
    padding: 0.5rem 0.75rem;
    vertical-align: middle;
    line-height: 1.35;
    border-bottom: 1px solid #eef1f4;
    font-size: 0.875rem;
}

.admission-entries-table tbody tr:last-child td,
.admission-picker-table tbody tr:last-child td {
    border-bottom: none;
}

.admission-entries-table tbody tr:hover td:not(.admission-wa-sent-cell),
.admission-picker-table tbody tr:hover td {
    background-color: rgba(13, 110, 253, 0.04);
}

.admission-entries-table tr.admission-wa-sent td {
    background-color: #d1e7dd !important;
}

.admission-entries-table tr.admission-wa-sent:hover td {
    background-color: #c3e6cb !important;
}

.admission-entries-table .col-no,
.admission-picker-table .col-no {
    text-align: center;
    color: #6c757d;
}

.admission-entries-table .col-nic {
    font-family: ui-monospace, monospace;
    font-size: 0.8125rem;
    color: #495057;
}

.admission-entries-table .col-course {
    font-size: 0.8125rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.admission-entries-table .col-roll input,
.admission-entries-table .col-room input {
    max-width: 100%;
    font-size: 0.8125rem;
}

.admission-entries-table .col-whatsapp,
.admission-entries-table .col-sent,
.admission-entries-table .col-remove {
    text-align: center;
    vertical-align: middle;
}

.admission-entries-table .col-whatsapp .wa-number {
    display: block;
    font-size: 0.75rem;
    color: #495057;
    word-break: break-all;
    line-height: 1.25;
    margin-bottom: 0.2rem;
}

.admission-entries-table .btn-wa-outline {
    padding: 0.2rem 0.45rem;
    line-height: 1;
}

.admission-entries-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem 0.75rem;
    padding: 1rem 0 0.5rem;
    border-top: 1px solid #eef1f4;
    margin-top: 0.25rem;
}

.admission-entries-toolbar .toolbar-hint {
    flex: 1 1 200px;
    font-size: 0.8125rem;
    color: #6c757d;
    margin: 0;
}

.admission-entries-secondary {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #dee2e6;
}

.admission-public-link {
    font-size: 0.8125rem;
    word-break: break-all;
}

.admission-entries-table-wrap {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    overflow: hidden;
}
</style>

<div class="container-fluid px-3 px-md-4 admission-entries-page-wrap">
    <div class="admission-entries-header d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
            <h1 class="page-title"><?php echo $e($sch['title'] ?? 'Schedule'); ?></h1>
            <p class="text-muted small mb-0">Manage applicants on this <?php echo $isInterview ? 'interview' : 'entrance exam'; ?> schedule.</p>
            <div class="admission-entries-meta">
                <span class="meta-chip">NVQ <?php echo $e($sch['application_level'] ?? ''); ?></span>
                <?php if (!empty($sch['course_name'])): ?>
                <span class="meta-chip"><?php echo $e($sch['course_name']); ?></span>
                <?php endif; ?>
                <?php if (!empty($sch['student_language'])): ?>
                <span class="meta-chip"><i class="fas fa-language me-1"></i><?php echo $e($sch['student_language']); ?></span>
                <?php endif; ?>
                <span class="meta-chip"><?php echo $e(ApplicationAdmissionScheduleModel::pathwayLabel(
                    ApplicationAdmissionScheduleModel::normalizePathway($sch['admission_pathway'] ?? null)
                )); ?></span>
                <span class="meta-chip"><i class="far fa-calendar-alt me-1"></i><?php echo $e($sch['schedule_date'] ?? ''); ?></span>
                <span class="meta-chip"><i class="fas fa-map-marker-alt me-1"></i><?php echo $e($sch['venue'] ?? ''); ?></span>
                <?php if (!empty($sch['is_published'])): ?>
                <span class="meta-chip bg-success bg-opacity-10 text-success border-success border-opacity-25">Published</span>
                <?php else: ?>
                <span class="meta-chip">Draft</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="admission-entries-actions d-flex flex-wrap gap-2">
            <a href="<?php echo APP_URL; ?>/application-admission" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back</a>
            <a href="<?php echo APP_URL; ?>/application-admission/pdf-schedule?id=<?php echo (int) ($sch['schedule_id'] ?? 0); ?>" class="btn btn-sm btn-outline-dark"><i class="fas fa-file-pdf me-1"></i> Schedule PDF</a>
            <?php if ($isInterview): ?>
            <a href="<?php echo APP_URL; ?>/application-admission/selection?id=<?php echo (int) ($sch['schedule_id'] ?? 0); ?>" class="btn btn-sm btn-outline-info"><i class="fas fa-list-check me-1"></i> Selection</a>
            <?php else: ?>
            <a href="<?php echo APP_URL; ?>/application-admission/selection?id=<?php echo (int) ($sch['schedule_id'] ?? 0); ?>" class="btn btn-sm btn-outline-info"><i class="fas fa-clipboard-check me-1"></i> Exam results</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success py-2"><?php echo $e($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger py-2"><?php echo $e($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <?php if (!empty($sch['is_published'])): ?>
    <div class="alert alert-info py-2 small mb-3">
        <strong>Public link:</strong>
        <a class="admission-public-link d-inline-block mt-1" href="<?php echo $e($publicUrl ?? '#'); ?>" target="_blank" rel="noopener"><?php echo $e($publicUrl ?? ''); ?></a>
    </div>
    <?php elseif (!empty($canManage) && $entryCount > 0): ?>
    <div class="alert alert-warning py-2 small mb-3">Publish this schedule before sharing the public link on WhatsApp.</div>
    <?php endif; ?>

    <div class="admission-entries-summary">
        <span><strong><?php echo (int) $entryCount; ?></strong> applicant<?php echo $entryCount === 1 ? '' : 's'; ?> on schedule</span>
        <span class="text-muted">Roll format: <code><?php echo $e($rollCourseCode); ?>-001</code></span>
    </div>

    <?php if (!empty($canManage)): ?>
    <form method="post" action="<?php echo APP_URL; ?>/application-admission/entries-save">
        <input type="hidden" name="schedule_id" value="<?php echo (int) ($sch['schedule_id'] ?? 0); ?>">

        <?php if (!empty($pickerHint)): ?>
        <div class="alert alert-light border small py-2 mb-3"><?php echo $pickerHint; ?></div>
        <?php endif; ?>

        <?php if (!empty($picker)): ?>
        <div class="admission-picker-card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <span>Add approved applicants</span>
                <label class="small mb-0 fw-normal"><input type="checkbox" id="picker-select-all" class="form-check-input me-1"> Select all</label>
            </div>
            <div class="admission-picker-scroll">
                <table class="table admission-picker-table mb-0">
                    <colgroup>
                        <col class="col-pick"><col class="col-no"><col class="col-name"><col class="col-nic"><col class="col-course">
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="col-pick"></th>
                            <th class="col-no">No.</th>
                            <th class="col-name">Name</th>
                            <th class="col-nic">NIC</th>
                            <th class="col-course">Course (1st pref.)</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $pickNo = 0; foreach ($picker as $p): $pickNo++; ?>
                    <tr>
                        <td class="col-pick"><input type="checkbox" class="form-check-input picker-row-cb" name="add_application_ids[]" value="<?php echo (int) $p['application_id']; ?>"></td>
                        <td class="col-no"><?php echo $pickNo; ?></td>
                        <td class="col-name"><?php echo $e($p['student_full_name'] ?? ''); ?></td>
                        <td class="col-nic"><?php echo $e($p['student_nic'] ?? ''); ?></td>
                        <td class="col-course" title="<?php echo $e($p['course_priority_1'] ?? ''); ?>"><?php echo $e($p['course_priority_1'] ?? ''); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php else: ?>
        <p class="text-muted small mb-3"><?php
            if (!empty($pickerHint)) {
                echo 'No eligible applicants to add yet.';
            } else {
                echo 'No more approved applicants to add for this course/level.';
            }
        ?></p>
        <?php endif; ?>

        <div class="admission-entries-table-wrap" id="admission-entries-table">
            <table class="table admission-entries-table mb-0">
                <colgroup>
                    <col class="col-remove"><col class="col-no"><col class="col-name"><col class="col-nic"><col class="col-course">
                    <col class="col-roll"><col class="col-room"><col class="col-whatsapp"><col class="col-sent">
                </colgroup>
                <thead>
                    <tr>
                        <th class="col-remove" title="Remove from schedule"></th>
                        <th class="col-no">No.</th>
                        <th class="col-name">Name</th>
                        <th class="col-nic">NIC</th>
                        <th class="col-course">Course</th>
                        <th class="col-roll">Roll / Index</th>
                        <th class="col-room"><?php echo $isInterview ? 'Panel' : 'Hall'; ?></th>
                        <th class="col-whatsapp">WhatsApp</th>
                        <th class="col-sent">Sent</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($entries)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">No applicants on this schedule yet.</td></tr>
                <?php else: ?>
                    <?php $i = 0; foreach ($entries as $row): $i++;
                        $rollDisplay = ApplicationAdmissionScheduleModel::defaultRollIndexForEntry($sch, $row, $i);
                        $waSent = !empty($row['whatsapp_sent']);
                    ?>
                    <tr class="<?php echo $waSent ? 'admission-wa-sent' : ''; ?>">
                        <td class="col-remove"><input type="checkbox" class="form-check-input" name="remove_entry_ids[]" value="<?php echo (int) $row['entry_id']; ?>" title="Remove"></td>
                        <td class="col-no"><?php echo $i; ?></td>
                        <td class="col-name"><?php echo $e($row['student_full_name'] ?? ''); ?></td>
                        <td class="col-nic"><?php echo $e($row['student_nic'] ?? ''); ?></td>
                        <td class="col-course" title="<?php echo $e($row['course_priority_1'] ?? ''); ?>"><?php echo $e($row['course_priority_1'] ?? ''); ?></td>
                        <td class="col-roll"><input type="text" class="form-control form-control-sm roll-index-input" name="entries[<?php echo (int) $row['entry_id']; ?>][roll_number]" value="<?php echo $e($rollDisplay); ?>" data-seq="<?php echo $i; ?>"></td>
                        <td class="col-room"><input type="text" class="form-control form-control-sm" name="entries[<?php echo (int) $row['entry_id']; ?>][room_or_panel]" value="<?php echo $e($row['room_or_panel'] ?? ''); ?>"></td>
                        <td class="col-whatsapp">
                            <?php
                            $waRow = $waByEntry[(int) ($row['entry_id'] ?? 0)] ?? null;
                            $waDisplay = $waRow['display_phone'] ?? '';
                            if ($waDisplay === '') {
                                $waDisplay = trim((string) ($row['student_whatsapp'] ?? ''));
                                if ($waDisplay === '') {
                                    $waDisplay = trim((string) ($row['student_phone'] ?? ''));
                                }
                            }
                            if ($waDisplay !== ''): ?>
                            <span class="wa-number"><?php echo $e($waDisplay); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($sch['is_published']) && $waRow && !empty($waRow['url'])): ?>
                            <a href="<?php echo $e($waRow['url']); ?>" class="btn btn-wa-outline btn-sm" target="_blank" rel="noopener noreferrer" title="Open WhatsApp with schedule and PDF download links"><i class="fab fa-whatsapp" aria-hidden="true"></i><span class="visually-hidden"> Share links</span></a>
                            <?php elseif (empty($sch['is_published'])): ?>
                            <span class="text-muted small d-block" title="Publish the schedule before sharing links">Publish first</span>
                            <?php elseif ($waDisplay === ''): ?>
                            <span class="text-muted small d-block" title="Add phone or WhatsApp on the online application">No number</span>
                            <?php else: ?>
                            <span class="text-muted small d-block" title="Check phone / WhatsApp format on application">Invalid number</span>
                            <?php endif; ?>
                        </td>
                        <td class="col-sent admission-wa-sent-cell">
                            <input type="checkbox" class="form-check-input wa-sent-cb" name="entries[<?php echo (int) $row['entry_id']; ?>][whatsapp_sent]" value="1"
                                   data-entry-id="<?php echo (int) $row['entry_id']; ?>"
                                   data-schedule-id="<?php echo (int) ($sch['schedule_id'] ?? 0); ?>"
                                   <?php echo $waSent ? 'checked' : ''; ?> title="Link sent" autocomplete="off">
                            <span class="wa-sent-hint small text-success d-block" aria-live="polite"></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="admission-entries-toolbar">
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i> Save applicants</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-renumber-rolls"><i class="fas fa-sort-numeric-down me-1"></i> Renumber</button>
            <p class="toolbar-hint mb-0"><i class="fab fa-whatsapp text-success"></i> Use <strong>WhatsApp</strong> to send schedule + PDF download links, then tick <strong>Sent</strong> (auto-saves).</p>
        </div>
    </form>

    <div class="admission-entries-secondary">
        <form method="post" action="<?php echo APP_URL; ?>/application-admission/publish" class="d-inline">
            <input type="hidden" name="schedule_id" value="<?php echo (int) ($sch['schedule_id'] ?? 0); ?>">
            <?php if (empty($sch['is_published'])): ?>
                <input type="hidden" name="action" value="publish">
                <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Publish this schedule? The public link will become active.');"><i class="fas fa-globe me-1"></i> Publish</button>
            <?php else: ?>
                <input type="hidden" name="action" value="unpublish">
                <button type="submit" class="btn btn-outline-warning btn-sm">Unpublish</button>
            <?php endif; ?>
        </form>
        <form method="get" action="<?php echo APP_URL; ?>/application-admission/delete" class="d-inline"
              onsubmit="return confirm('Delete this schedule and all entries?');">
            <input type="hidden" name="id" value="<?php echo (int) ($sch['schedule_id'] ?? 0); ?>">
            <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fas fa-trash-alt me-1"></i> Delete schedule</button>
        </form>
    </div>

    <?php else: ?>

    <div class="admission-entries-table-wrap">
        <table class="table admission-entries-table admission-entries-table-readonly mb-0">
            <colgroup>
                <col class="col-no"><col class="col-name"><col class="col-nic"><col class="col-course">
                <col class="col-roll"><col class="col-room"><col class="col-whatsapp"><col class="col-sent">
            </colgroup>
            <thead>
                <tr>
                    <th class="col-no">No.</th>
                    <th class="col-name">Name</th>
                    <th class="col-nic">NIC</th>
                    <th class="col-course">Course</th>
                    <th class="col-roll">Roll / Index</th>
                    <th class="col-room"><?php echo $isInterview ? 'Panel' : 'Hall'; ?></th>
                    <th class="col-whatsapp">WhatsApp</th>
                    <th class="col-sent">Sent</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($entries)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No applicants on this schedule yet.</td></tr>
            <?php else: ?>
                <?php $i = 0; foreach ($entries as $row): $i++;
                    $rollOut = ApplicationAdmissionScheduleModel::defaultRollIndexForEntry($sch, $row, $i);
                    $waRow = $waByEntry[(int) ($row['entry_id'] ?? 0)] ?? null;
                    $waDisplay = $waRow['display_phone'] ?? trim((string) ($row['student_whatsapp'] ?? ''));
                    if ($waDisplay === '') {
                        $waDisplay = trim((string) ($row['student_phone'] ?? ''));
                    }
                ?>
                <tr class="<?php echo !empty($row['whatsapp_sent']) ? 'admission-wa-sent' : ''; ?>">
                    <td class="col-no"><?php echo $i; ?></td>
                    <td class="col-name"><?php echo $e($row['student_full_name'] ?? ''); ?></td>
                    <td class="col-nic"><?php echo $e($row['student_nic'] ?? ''); ?></td>
                    <td class="col-course" title="<?php echo $e($row['course_priority_1'] ?? ''); ?>"><?php echo $e($row['course_priority_1'] ?? ''); ?></td>
                    <td class="col-roll"><code class="small"><?php echo $e($rollOut); ?></code></td>
                    <td class="col-room"><?php echo $e($row['room_or_panel'] ?? '—'); ?></td>
                    <td class="col-whatsapp">
                        <?php if ($waDisplay !== ''): ?>
                        <span class="wa-number"><?php echo $e($waDisplay); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($sch['is_published']) && $waRow && !empty($waRow['url'])): ?>
                        <a href="<?php echo $e($waRow['url']); ?>" class="btn btn-wa-outline btn-sm" target="_blank" rel="noopener noreferrer" title="Open WhatsApp with download links"><i class="fab fa-whatsapp" aria-hidden="true"></i></a>
                        <?php elseif ($waDisplay === ''): ?>
                        <span class="text-muted small">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="col-sent"><?php if (!empty($row['whatsapp_sent'])): ?><i class="fas fa-check text-success" title="Sent"></i><?php else: ?><span class="text-muted">—</span><?php endif; ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php endif; ?>
</div>
<?php if (!empty($canManage)): ?>
<script>
(function () {
    var saveUrl = <?php echo json_encode(rtrim(APP_URL, '/') . '/application-admission/mark-whatsapp-sent'); ?>;
    var rollCourseCode = <?php echo json_encode($rollCourseCode); ?>;

    function formatRollIndex(seq) {
        var n = Math.max(1, parseInt(seq, 10) || 1);
        var num = String(n);
        while (num.length < 3) {
            num = '0' + num;
        }
        var code = (rollCourseCode || '').replace(/[^A-Za-z0-9._-]+/g, '').toUpperCase();
        return code ? (code + '-' + num) : num;
    }

    var selectAll = document.getElementById('picker-select-all');
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            document.querySelectorAll('.picker-row-cb').forEach(function (cb) {
                cb.checked = selectAll.checked;
            });
        });
    }
    var renumberBtn = document.getElementById('btn-renumber-rolls');
    if (renumberBtn) {
        renumberBtn.addEventListener('click', function () {
            document.querySelectorAll('.roll-index-input').forEach(function (inp) {
                var seq = inp.getAttribute('data-seq');
                if (seq) {
                    inp.value = formatRollIndex(seq);
                }
            });
        });
    }

    function saveWaSent(cb) {
        var tr = cb.closest('tr');
        var hint = tr ? tr.querySelector('.wa-sent-hint') : null;
        var entryId = cb.getAttribute('data-entry-id');
        var scheduleId = cb.getAttribute('data-schedule-id');
        if (!entryId || !scheduleId) {
            return;
        }
        if (tr) {
            tr.classList.toggle('admission-wa-sent', cb.checked);
        }
        if (hint) {
            hint.textContent = 'Saving…';
            hint.classList.remove('text-danger');
        }
        cb.disabled = true;
        var body = new URLSearchParams({
            schedule_id: scheduleId,
            entry_id: entryId,
            sent: cb.checked ? '1' : '0'
        });
        fetch(saveUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: body.toString()
        })
            .then(function (r) {
                if (r.status === 401) {
                    throw new Error('Session expired. Please log in again.');
                }
                return r.text().then(function (text) {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        throw new Error('Server error. Could not save sent status.');
                    }
                });
            })
            .then(function (data) {
                if (!data || !data.success) {
                    throw new Error((data && data.error) ? data.error : 'Could not save sent status.');
                }
                if (hint) {
                    hint.textContent = 'Saved';
                    window.setTimeout(function () {
                        if (hint.textContent === 'Saved') {
                            hint.textContent = '';
                        }
                    }, 2000);
                }
            })
            .catch(function (err) {
                cb.checked = !cb.checked;
                if (tr) {
                    tr.classList.toggle('admission-wa-sent', cb.checked);
                }
                if (hint) {
                    hint.textContent = 'Failed';
                    hint.classList.add('text-danger');
                }
                alert(err.message || 'Could not save sent status.');
            })
            .finally(function () {
                cb.disabled = false;
            });
    }

    var entriesTable = document.getElementById('admission-entries-table');
    if (entriesTable) {
        entriesTable.addEventListener('change', function (ev) {
            var t = ev.target;
            if (t && t.classList && t.classList.contains('wa-sent-cb')) {
                saveWaSent(t);
            }
        });
    }
})();
</script>
<?php endif; ?>
