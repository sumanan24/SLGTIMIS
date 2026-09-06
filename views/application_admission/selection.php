<?php
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
$sch = $schedule ?? [];
$entries = is_array($entries ?? null) ? $entries : [];
$isEntranceResults = !empty($isEntranceResults);
$canUpdate = !empty($canUpdateSelection);
$pageTitle = $isEntranceResults ? 'Entrance exam results' : 'Interview selection list';
$saveLabel = $isEntranceResults ? 'Save exam results' : 'Save selection';
$viewOnlyMsg = $isEntranceResults
    ? 'View only. <strong>SAO</strong>, <strong>REG</strong>, and <strong>ADM</strong> can mark selected / not selected after the exam.'
    : 'View only. <strong>SAO</strong> and <strong>ADM</strong> can update candidate selection.';
$selectedConst = ApplicationAdmissionScheduleModel::SELECTION_SELECTED;
$notSelectedConst = ApplicationAdmissionScheduleModel::SELECTION_NOT_SELECTED;
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
.aa-sel-table .aa-col-check {
    width: 3.25rem;
    text-align: center;
}
.aa-sel-table .aa-col-check .form-check-input {
    float: none;
    margin: 0;
    position: static;
    width: 1.1rem;
    height: 1.1rem;
    cursor: pointer;
}
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
.aa-sel-table tr.is-selected td {
    background: #d1e7dd;
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
.aa-sel-badge-yes {
    display: inline-block;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.2rem 0.55rem;
    border-radius: 999px;
    background: #d1e7dd;
    color: #0f5132;
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
            <?php if ($isEntranceResults && $canUpdate): ?>
            <p class="small text-muted mb-0 mt-1">Tick <strong>Selected</strong> for candidates who may go to interview. Unticked students are saved as <strong>Not selected</strong>.</p>
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
                        <th class="aa-col-check">
                            <?php if ($canUpdate): ?>
                            <div class="d-flex flex-column align-items-center gap-1">
                                <span>Selected</span>
                                <label class="small fw-normal text-muted mb-0" title="Select all">
                                    <input type="checkbox" class="form-check-input" id="aa-select-all"> All
                                </label>
                            </div>
                            <?php else: ?>
                            Result
                            <?php endif; ?>
                        </th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($entries === []): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">No applicants.</td>
                    </tr>
                <?php else: ?>
                    <?php $n = 0; foreach ($entries as $row): $n++;
                        $entryId = (int) ($row['entry_id'] ?? 0);
                        $isSelected = ($row['selection_status'] ?? '') === $selectedConst;
                    ?>
                    <tr class="<?php echo $isSelected ? 'is-selected' : ''; ?>">
                        <td class="aa-col-no"><?php echo $n; ?></td>
                        <td class="aa-col-name"><?php echo $e($row['student_full_name'] ?? ''); ?></td>
                        <td class="aa-col-nic"><?php echo $e($row['student_nic'] ?? ''); ?></td>
                        <td class="aa-col-course"><?php echo $e($row['course_priority_1'] ?? ''); ?></td>
                        <td class="aa-col-check">
                            <?php if ($canUpdate): ?>
                            <input type="hidden" name="entry_ids[]" value="<?php echo $entryId; ?>">
                            <input type="checkbox"
                                   class="form-check-input aa-row-select"
                                   name="selected_ids[]"
                                   value="<?php echo $entryId; ?>"
                                   <?php echo $isSelected ? 'checked' : ''; ?>
                                   title="Selected">
                            <?php elseif ($isSelected): ?>
                                <span class="aa-sel-badge-yes">Selected</span>
                            <?php elseif (($row['selection_status'] ?? '') === $notSelectedConst): ?>
                                <span class="aa-sel-badge-no">Not selected</span>
                            <?php else: ?>
                                <span class="text-muted small">Pending</span>
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
            <p class="aa-sel-hint mb-0">Select all ticks every student. Untick anyone who is not selected, then save.</p>
        </div>
    </form>
    <script>
    (function () {
        var form = document.getElementById('aa-selection-form');
        if (!form) return;
        var allCb = document.getElementById('aa-select-all');
        var rowCbs = form.querySelectorAll('.aa-row-select');

        function syncRow(cb) {
            var tr = cb.closest('tr');
            if (!tr) return;
            tr.classList.toggle('is-selected', cb.checked);
        }

        function syncSelectAllState() {
            if (!allCb || rowCbs.length === 0) return;
            var checked = 0;
            rowCbs.forEach(function (cb) { if (cb.checked) checked++; });
            allCb.checked = checked === rowCbs.length;
            allCb.indeterminate = checked > 0 && checked < rowCbs.length;
        }

        if (allCb) {
            allCb.addEventListener('change', function () {
                rowCbs.forEach(function (cb) {
                    cb.checked = allCb.checked;
                    syncRow(cb);
                });
                allCb.indeterminate = false;
            });
        }
        rowCbs.forEach(function (cb) {
            cb.addEventListener('change', function () {
                syncRow(cb);
                syncSelectAllState();
            });
            syncRow(cb);
        });
        syncSelectAllState();
    })();
    </script>
    <?php endif; ?>
</div>
