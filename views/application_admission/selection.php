<?php
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
$sch = $schedule ?? [];
$entries = is_array($entries ?? null) ? $entries : [];
$isEntranceResults = !empty($isEntranceResults);
$canUpdate = !empty($canUpdateSelection);
$pageTitle = $isEntranceResults ? 'Entrance exam results' : 'Interview selection list';
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
.aa-sel-table .aa-col-nic,
.aa-sel-table .aa-col-roll {
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
</style>

<div class="container-fluid px-3 px-md-4 aa-sel-page">
    <div class="aa-sel-header">
        <div>
            <h1><?php echo $e($pageTitle); ?></h1>
            <p class="text-muted small mb-0"><?php echo $e($sch['title'] ?? ''); ?></p>
            <?php if ($canUpdate): ?>
            <p class="small text-muted mb-0 mt-1">Type <strong>marks</strong> for each student. For absent students type <strong>ab</strong> and press Enter to move to the next row.</p>
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
                        <th class="aa-col-roll">Roll / Index</th>
                        <th class="aa-col-name">Name</th>
                        <th class="aa-col-nic">NIC</th>
                        <th class="aa-col-course">Course</th>
                        <th class="aa-col-marks">Marks</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($entries === []): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No applicants.</td>
                    </tr>
                <?php else: ?>
                    <?php $n = 0; foreach ($entries as $row): $n++;
                        $entryId = (int) ($row['entry_id'] ?? 0);
                        $roll = trim((string) ($row['roll_number'] ?? ''));
                        $marksVal = trim((string) ($row['exam_marks'] ?? ''));
                        $isAbsent = ApplicationAdmissionScheduleModel::isAbsentMarks($marksVal);
                    ?>
                    <tr class="<?php echo $isAbsent ? 'is-absent' : ''; ?>">
                        <td class="aa-col-no"><?php echo $n; ?></td>
                        <td class="aa-col-roll"><?php echo $roll !== '' ? $e($roll) : '<span class="text-muted">—</span>'; ?></td>
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
</div>
