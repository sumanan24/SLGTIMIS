<?php
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
$sch = $schedule ?? [];
$entries = is_array($entries ?? null) ? $entries : [];
$isEntranceResults = !empty($isEntranceResults);
$canUpdate = !empty($canUpdateSelection);
$canRegister = !empty($canRegister);
$registrationStatuses = is_array($registrationStatuses ?? null) ? $registrationStatuses : [];
$selectionWhatsApp = is_array($selectionWhatsApp ?? null) ? $selectionWhatsApp : [];
$showSelectionWa = !$isEntranceResults;
$pageTitle = $isEntranceResults ? 'Entrance exam results' : 'Interview selection list';
$qualifiedCount = 0;
$waSentCount = 0;
if ($showSelectionWa) {
    foreach ($selectionWhatsApp as $waRow) {
        if (!empty($waRow['qualified'])) {
            $qualifiedCount++;
        }
        if (!empty($waRow['sent'])) {
            $waSentCount++;
        }
    }
}
$colCount = 5 + ($canRegister ? 1 : 0) + ($showSelectionWa ? 2 : 0);
$saveLabel = $isEntranceResults ? 'Save exam results' : 'Save marks';
$viewOnlyMsg = $isEntranceResults
    ? 'View only. <strong>SAO</strong>, <strong>REG</strong>, and <strong>ADM</strong> can enter exam marks.'
    : 'View only. <strong>SAO</strong> and <strong>ADM</strong> can enter marks.';
?>
<style>
.aa-sel-page { width: 100%; max-width: none; }
.aa-sel-header {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem 1rem;
    margin-bottom: 1rem;
}
.aa-sel-header h1 {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0 0 0.25rem;
}
.aa-sel-table-wrap {
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
    background: #fff;
    overflow-x: auto;
}
.aa-sel-table {
    width: 100%;
    margin: 0;
    table-layout: auto;
}
.aa-sel-table thead th {
    padding: 0.6rem 0.85rem;
    font-size: 0.6875rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #495057;
    background: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    white-space: nowrap;
    vertical-align: middle;
}
.aa-sel-table tbody td {
    padding: 0.55rem 0.85rem;
    font-size: 0.875rem;
    vertical-align: middle;
    border-bottom: 1px solid #eef1f4;
}
.aa-sel-table tbody tr:last-child td { border-bottom: none; }
.aa-sel-table .aa-col-no {
    width: 3rem;
    text-align: center;
    color: #6c757d;
}
.aa-sel-table .aa-col-name,
.aa-sel-table .aa-col-course {
    white-space: normal;
    word-break: break-word;
}
.aa-sel-table .aa-col-nic {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.8125rem;
    white-space: nowrap;
}
.aa-sel-table tr.is-absent td {
    background: #fff3cd;
}
.aa-sel-table .aa-col-marks {
    width: 5.5rem;
    text-align: center;
}
.aa-sel-table .aa-marks-input {
    width: 4.25rem;
    max-width: 100%;
    text-align: center;
    text-transform: lowercase;
    font-variant-numeric: tabular-nums;
    padding: 0.2rem 0.3rem;
}
.aa-sel-table .aa-marks-input.is-absent-val {
    background: #fff3cd;
    font-weight: 600;
    color: #856404;
}
.aa-sel-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.75rem 1rem;
    margin-top: 1rem;
}
.aa-sel-hint {
    font-size: 0.8125rem;
    color: #6c757d;
    margin: 0;
}
.aa-sel-badge-no {
    display: inline-block;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.2rem 0.55rem;
    border-radius: 999px;
    background: #f8d7da;
    color: #842029;
}
.aa-sel-table tr.aa-sel-wa-sent td {
    background: #e8f6ee;
}
.aa-sel-table .aa-col-wa,
.aa-sel-table .aa-col-sent {
    white-space: nowrap;
}
.aa-sel-wa-phone {
    display: block;
    font-size: 0.75rem;
    color: #495057;
    margin-bottom: 0.15rem;
}
.aa-sel-wa-meta {
    display: block;
    max-width: 9rem;
    white-space: normal;
    line-height: 1.25;
}
.aa-sel-sent-toggle {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    margin: 0;
    cursor: pointer;
    font-size: 0.8125rem;
    font-weight: 600;
}
.aa-sel-sent-toggle .aa-sel-wa-cb {
    float: none;
    margin: 0;
    cursor: pointer;
}
.btn-wa-outline {
    color: #128c7e;
    border-color: #128c7e;
    padding: 0.2rem 0.45rem;
    line-height: 1;
}
.btn-wa-outline:hover {
    color: #fff;
    background: #128c7e;
    border-color: #128c7e;
}
.aa-sent-persons {
    margin-top: 1.25rem;
}
</style>

<div class="container-fluid px-3 px-md-4 aa-sel-page">
    <div class="aa-sel-header">
        <div>
            <h1><?php echo $e($pageTitle); ?></h1>
            <p class="text-muted small mb-0"><?php echo $e($sch['title'] ?? ''); ?></p>
            <p class="small text-muted mb-0 mt-1">Listed by marks, highest first. Absent and blank marks are at the bottom.</p>
            <?php if ($canUpdate): ?>
            <p class="small text-muted mb-0 mt-1">Type <strong>marks</strong> for each student. For absent students type <strong>ab</strong> and press Enter to move to the next row.</p>
            <?php endif; ?>
            <?php if ($showSelectionWa): ?>
            <p class="small text-muted mb-0 mt-1">WhatsApp selection results: <strong><?php echo (int) $waSentCount; ?></strong> sent of <strong><?php echo (int) $qualifiedCount; ?></strong> qualified applicants. Absent applicants are not included.</p>
            <?php endif; ?>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?php echo APP_URL; ?>/application-admission/entries?id=<?php echo (int) ($sch['schedule_id'] ?? 0); ?>" class="btn btn-sm btn-outline-secondary">Applicants</a>
            <a href="<?php echo APP_URL; ?>/application-admission/pdf-selection?id=<?php echo (int) ($sch['schedule_id'] ?? 0); ?>" class="btn btn-sm btn-outline-dark"><i class="fas fa-file-pdf me-1"></i> PDF</a>
        </div>
    </div>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger py-2"><?php echo $e($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success py-2"><?php echo $e($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <?php if (!$canUpdate): ?>
    <div class="alert alert-secondary py-2 small mb-3"><?php echo $viewOnlyMsg; ?></div>
    <?php endif; ?>

    <?php if ($canUpdate): ?>
    <form method="post" action="<?php echo APP_URL; ?>/application-admission/selection-save" id="aa-selection-form">
        <input type="hidden" name="schedule_id" value="<?php echo (int) ($sch['schedule_id'] ?? 0); ?>">
    <?php endif; ?>

        <div class="aa-sel-table-wrap">
            <table class="table aa-sel-table mb-0">
                <thead>
                    <tr>
                        <th class="aa-col-no">#</th>
                        <th class="aa-col-name">Name</th>
                        <th class="aa-col-nic">NIC</th>
                        <th class="aa-col-course">Course</th>
                        <th class="aa-col-marks">Marks</th>
                        <?php if ($showSelectionWa): ?>
                        <th class="aa-col-wa">WhatsApp</th>
                        <th class="aa-col-sent">Sent</th>
                        <?php endif; ?>
                        <?php if ($canRegister): ?>
                        <th class="aa-col-reg">Registration</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php if ($entries === []): ?>
                    <tr>
                        <td colspan="<?php echo (int) $colCount; ?>" class="text-center text-muted py-4">No applicants.</td>
                    </tr>
                <?php else: ?>
                    <?php $n = 0; foreach ($entries as $row): $n++;
                        $entryId = (int) ($row['entry_id'] ?? 0);
                        $marksVal = trim((string) ($row['exam_marks'] ?? ''));
                        $isAbsent = ApplicationAdmissionScheduleModel::isAbsentMarks($marksVal);
                        $nicKey = strtoupper(preg_replace('/\s+/', '', trim((string) ($row['student_nic'] ?? ''))));
                        $regStatus = $registrationStatuses[$nicKey] ?? null;
                        $wa = $selectionWhatsApp[$entryId] ?? null;
                        $waSent = !empty($wa['sent']);
                    ?>
                    <tr class="<?php echo trim(($isAbsent ? 'is-absent ' : '') . ($waSent ? 'aa-sel-wa-sent' : '')); ?>"
                        data-entry-id="<?php echo $entryId; ?>"
                        data-name="<?php echo $e($row['student_full_name'] ?? ''); ?>"
                        data-nic="<?php echo $e($row['student_nic'] ?? ''); ?>"
                        data-phone="<?php echo $e($wa['display_phone'] ?? ''); ?>"
                        data-course="<?php echo $e($row['course_priority_1'] ?? ''); ?>"
                        data-marks="<?php echo $e($marksVal); ?>">
                        <td class="aa-col-no"><?php echo $n; ?></td>
                        <td class="aa-col-name"><?php echo $e($row['student_full_name'] ?? ''); ?></td>
                        <td class="aa-col-nic"><?php echo $e($row['student_nic'] ?? ''); ?></td>
                        <td class="aa-col-course"><?php echo $e($row['course_priority_1'] ?? ''); ?></td>
                        <td class="aa-col-marks">
                            <?php if ($canUpdate): ?>
                            <input type="hidden" name="entry_ids[]" value="<?php echo $entryId; ?>">
                            <input type="text"
                                   class="form-control form-control-sm aa-marks-input<?php echo $isAbsent ? ' is-absent-val' : ''; ?>"
                                   name="exam_marks[<?php echo $entryId; ?>]"
                                   value="<?php echo $e($marksVal); ?>"
                                   maxlength="10"
                                   autocomplete="off"
                                   title="Type marks, or ab if absent">
                            <?php elseif ($isAbsent): ?>
                                <span class="aa-sel-badge-no">ab</span>
                            <?php elseif ($marksVal !== ''): ?>
                                <?php echo $e($marksVal); ?>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <?php if ($showSelectionWa): ?>
                        <td class="aa-col-wa">
                            <?php if (empty($wa['qualified'])): ?>
                                <span class="text-muted">—</span>
                            <?php else: ?>
                                <?php if (($wa['display_phone'] ?? '') !== ''): ?>
                                <span class="aa-sel-wa-phone"><?php echo $e($wa['display_phone']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($wa['url']) && $canUpdate): ?>
                                <a href="<?php echo $e($wa['url']); ?>" class="btn btn-wa-outline btn-sm aa-sel-wa-link" target="_blank" rel="noopener noreferrer" title="Open WhatsApp with the selection result message"><i class="fab fa-whatsapp" aria-hidden="true"></i><span class="visually-hidden"> Send selection result</span></a>
                                <?php elseif (empty($wa['url'])): ?>
                                <span class="text-muted small">No number</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td class="aa-col-sent">
                            <?php if (empty($wa['qualified'])): ?>
                                <span class="text-muted">—</span>
                            <?php elseif ($canUpdate): ?>
                                <label class="aa-sel-sent-toggle">
                                    <input type="checkbox" class="form-check-input aa-sel-wa-cb" value="1"
                                           data-entry-id="<?php echo $entryId; ?>"
                                           data-schedule-id="<?php echo (int) ($sch['schedule_id'] ?? 0); ?>"
                                           <?php echo $waSent ? 'checked' : ''; ?>
                                           title="Tick when the selection WhatsApp is sent. Untick to clear it.">
                                    <span>Sent</span>
                                </label>
                                <span class="aa-sel-wa-meta small text-success" aria-live="polite"><?php
                                    if ($waSent) {
                                        echo $e(trim(($wa['sent_at'] ?? '') . (($wa['sent_by'] ?? '') !== '' ? ' · ' . $wa['sent_by'] : '')));
                                    }
                                ?></span>
                            <?php elseif ($waSent): ?>
                                <i class="fas fa-check text-success" title="Sent"></i>
                                <span class="aa-sel-wa-meta small text-success"><?php echo $e(trim(($wa['sent_at'] ?? '') . (($wa['sent_by'] ?? '') !== '' ? ' · ' . $wa['sent_by'] : ''))); ?></span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                        <?php if ($canRegister): ?>
                        <td class="aa-col-reg">
                            <?php if ($isAbsent): ?>
                                <span class="text-muted">—</span>
                            <?php elseif (!empty($regStatus['registered'])): ?>
                                <span class="badge bg-success">Registered</span>
                                <div class="small text-muted"><?php echo $e($regStatus['student_id'] ?? ''); ?></div>
                                <a class="btn btn-sm btn-outline-secondary mt-1" href="<?php echo APP_URL; ?>/application-admission/register?schedule_id=<?php echo (int) ($sch['schedule_id'] ?? 0); ?>&amp;entry_id=<?php echo $entryId; ?>">View</a>
                            <?php else: ?>
                                <a class="btn btn-sm btn-outline-primary" href="<?php echo APP_URL; ?>/application-admission/register?schedule_id=<?php echo (int) ($sch['schedule_id'] ?? 0); ?>&amp;entry_id=<?php echo $entryId; ?>">Register</a>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

    <?php if ($canUpdate): ?>
        <div class="aa-sel-toolbar">
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i> <?php echo $e($saveLabel); ?></button>
            <p class="aa-sel-hint mb-0">Type marks or <strong>ab</strong> for absent, then press Enter for the next student.</p>
        </div>
    </form>
    <script>
    (function () {
        var form = document.getElementById('aa-selection-form');
        if (!form) return;
        var markInputs = form.querySelectorAll('.aa-marks-input');

        function isAbsentValue(val) {
            var v = String(val || '').replace(/\s+/g, '').toLowerCase();
            return v === 'ab' || v === 'abs' || v === 'absent' || v === 'ab.';
        }

        function applyMarksInput(input) {
            var tr = input.closest('tr');
            if (isAbsentValue(input.value)) {
                input.value = 'ab';
            }
            var absent = isAbsentValue(input.value);
            if (tr) {
                tr.classList.toggle('is-absent', absent);
            }
            input.classList.toggle('is-absent-val', absent);
        }

        markInputs.forEach(function (input) {
            input.addEventListener('blur', function () { applyMarksInput(input); });
            input.addEventListener('input', function () { applyMarksInput(input); });
        });
        form.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter') return;
            var t = e.target;
            if (!t || !t.classList || !t.classList.contains('aa-marks-input')) return;
            e.preventDefault();
            applyMarksInput(t);
            var list = Array.prototype.slice.call(markInputs);
            var i = list.indexOf(t);
            if (i >= 0 && i < list.length - 1) {
                list[i + 1].focus();
                list[i + 1].select();
            }
        });
    })();
    </script>
    <?php endif; ?>

    <?php if ($showSelectionWa): ?>
    <div class="aa-sent-persons">
        <h2 class="h6 mb-2">Sent persons</h2>
        <p class="aa-sel-hint mb-2">Applicants marked after the selection-result WhatsApp was sent. The message tells them they qualified and that the registration date will be announced soon.</p>
        <div class="aa-sel-table-wrap">
            <table class="table aa-sel-table mb-0" id="aa-sent-persons-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>NIC</th>
                        <th>WhatsApp</th>
                        <th>Course</th>
                        <th>Marks</th>
                        <th>Sent at</th>
                        <th>Marked by</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $sentRows = 0;
                foreach ($entries as $row):
                    $sentEntryId = (int) ($row['entry_id'] ?? 0);
                    $sentWa = $selectionWhatsApp[$sentEntryId] ?? null;
                    if (empty($sentWa['sent'])) {
                        continue;
                    }
                    $sentRows++;
                ?>
                    <tr data-entry-id="<?php echo $sentEntryId; ?>">
                        <td><?php echo $e($row['student_full_name'] ?? ''); ?></td>
                        <td class="aa-col-nic"><?php echo $e($row['student_nic'] ?? ''); ?></td>
                        <td><?php echo $e($sentWa['display_phone'] ?? ''); ?></td>
                        <td><?php echo $e($row['course_priority_1'] ?? ''); ?></td>
                        <td><?php echo $e($row['exam_marks'] ?? ''); ?></td>
                        <td class="aa-sent-at"><?php echo $e($sentWa['sent_at'] ?? ''); ?></td>
                        <td class="aa-sent-by"><?php echo $e($sentWa['sent_by'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($sentRows === 0): ?>
                    <tr class="aa-sent-empty">
                        <td colspan="7" class="text-center text-muted py-3">No selection messages marked as sent yet.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php if ($showSelectionWa && $canUpdate): ?>
<script>
(function () {
    var saveUrl = <?php echo json_encode(rtrim(APP_URL, '/') . '/application-admission/mark-selection-whatsapp-sent'); ?>;
    var sentBody = document.querySelector('#aa-sent-persons-table tbody');

    function sentMeta(sentAt, sentBy) {
        var parts = [];
        if (sentAt) parts.push(sentAt);
        if (sentBy) parts.push(sentBy);
        return parts.join(' · ');
    }

    function upsertSentRow(sourceRow, sentAt, sentBy) {
        if (!sentBody || !sourceRow) return;
        var entryId = sourceRow.getAttribute('data-entry-id') || '';
        var empty = sentBody.querySelector('.aa-sent-empty');
        if (empty) empty.remove();
        var existing = sentBody.querySelector('tr[data-entry-id="' + entryId + '"]');
        if (!existing) {
            existing = document.createElement('tr');
            existing.setAttribute('data-entry-id', entryId);
            existing.innerHTML = '<td></td><td class="aa-col-nic"></td><td></td><td></td><td></td><td class="aa-sent-at"></td><td class="aa-sent-by"></td>';
            sentBody.appendChild(existing);
        }
        var cells = existing.children;
        cells[0].textContent = sourceRow.getAttribute('data-name') || '';
        cells[1].textContent = sourceRow.getAttribute('data-nic') || '';
        cells[2].textContent = sourceRow.getAttribute('data-phone') || '';
        cells[3].textContent = sourceRow.getAttribute('data-course') || '';
        cells[4].textContent = sourceRow.getAttribute('data-marks') || '';
        cells[5].textContent = sentAt || '';
        cells[6].textContent = sentBy || '';
    }

    function removeSentRow(entryId) {
        if (!sentBody) return;
        var row = sentBody.querySelector('tr[data-entry-id="' + entryId + '"]');
        if (row) row.remove();
        if (!sentBody.querySelector('tr')) {
            var empty = document.createElement('tr');
            empty.className = 'aa-sent-empty';
            empty.innerHTML = '<td colspan="7" class="text-center text-muted py-3">No selection messages marked as sent yet.</td>';
            sentBody.appendChild(empty);
        }
    }

    function saveSent(cb, sent) {
        var tr = cb.closest('tr');
        var hint = tr ? tr.querySelector('.aa-sel-wa-meta') : null;
        var entryId = cb.getAttribute('data-entry-id');
        var scheduleId = cb.getAttribute('data-schedule-id');
        if (!entryId || !scheduleId) return;
        cb.checked = !!sent;
        if (tr) tr.classList.toggle('aa-sel-wa-sent', cb.checked);
        if (hint) {
            hint.textContent = 'Saving…';
            hint.classList.remove('text-danger');
        }
        var body = new URLSearchParams({
            schedule_id: scheduleId,
            entry_id: entryId,
            sent: sent ? '1' : '0'
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
                if (r.status === 401) throw new Error('Session expired. Please log in again.');
                return r.text().then(function (text) {
                    try { return JSON.parse(text); }
                    catch (e) { throw new Error('Server error. Could not save sent status.'); }
                });
            })
            .then(function (data) {
                if (!data || !data.success) {
                    throw new Error((data && data.error) ? data.error : 'Could not save sent status.');
                }
                if (hint) hint.textContent = sentMeta(data.sent_at, data.sent_by);
                if (cb.checked) upsertSentRow(tr, data.sent_at, data.sent_by);
                else removeSentRow(entryId);
            })
            .catch(function (err) {
                cb.checked = !sent;
                if (tr) tr.classList.toggle('aa-sel-wa-sent', cb.checked);
                if (hint) {
                    hint.textContent = 'Failed';
                    hint.classList.add('text-danger');
                }
                alert(err.message || 'Could not save sent status.');
            });
    }

    document.querySelectorAll('.aa-sel-wa-cb').forEach(function (cb) {
        cb.addEventListener('click', function (e) {
            e.stopPropagation();
        });
        cb.addEventListener('change', function () { saveSent(cb, cb.checked); });
    });
    document.querySelectorAll('.aa-sel-wa-link').forEach(function (link) {
        link.addEventListener('click', function () {
            var tr = link.closest('tr');
            var cb = tr ? tr.querySelector('.aa-sel-wa-cb') : null;
            if (cb && !cb.checked) {
                saveSent(cb, true);
            }
        });
    });
})();
</script>
<?php endif; ?>
