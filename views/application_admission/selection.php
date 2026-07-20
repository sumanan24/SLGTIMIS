<?php
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
$sch = $schedule ?? [];
$isEntranceResults = !empty($isEntranceResults);
$pageTitle = $isEntranceResults ? 'Entrance exam results' : 'Interview selection list';
$saveLabel = $isEntranceResults ? 'Save exam results' : 'Save selection';
$viewOnlyMsg = $isEntranceResults
    ? 'View only. <strong>SAO</strong>, <strong>REG</strong>, and <strong>ADM</strong> can mark selected / not selected after the exam.'
    : 'View only. <strong>SAO</strong> and <strong>ADM</strong> can update candidate selection.';

if ($isEntranceResults) {
    $statusOptions = [
        ApplicationAdmissionScheduleModel::SELECTION_SCHEDULED => 'Pending — not marked yet',
        ApplicationAdmissionScheduleModel::SELECTION_SELECTED => 'Selected — eligible for interview',
        ApplicationAdmissionScheduleModel::SELECTION_NOT_SELECTED => 'Not selected',
        ApplicationAdmissionScheduleModel::SELECTION_WAITLIST => 'Waitlist',
    ];
} else {
    $statusOptions = [
        ApplicationAdmissionScheduleModel::SELECTION_SCHEDULED => '1 — Scheduled',
        ApplicationAdmissionScheduleModel::SELECTION_SELECTED => '2 — Selected',
        ApplicationAdmissionScheduleModel::SELECTION_NOT_SELECTED => '3 — Not selected',
        ApplicationAdmissionScheduleModel::SELECTION_WAITLIST => '4 — Waitlist',
    ];
}

$statusLabelsPlain = [
    ApplicationAdmissionScheduleModel::SELECTION_SCHEDULED => $isEntranceResults ? 'Pending' : 'Scheduled',
    ApplicationAdmissionScheduleModel::SELECTION_SELECTED => 'Selected',
    ApplicationAdmissionScheduleModel::SELECTION_NOT_SELECTED => 'Not selected',
    ApplicationAdmissionScheduleModel::SELECTION_WAITLIST => 'Waitlist',
];
$resultColumn = $isEntranceResults ? 'Exam result' : 'Selection';
?>
<div class="container-fluid px-3 px-md-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1"><?php echo $e($pageTitle); ?></h1>
            <p class="text-muted small mb-0"><?php echo $e($sch['title'] ?? ''); ?></p>
            <?php if ($isEntranceResults): ?>
            <p class="small text-muted mb-0 mt-1">Mark <strong>Selected</strong> for candidates who may proceed to interview (for courses that require an entrance exam).</p>
            <?php endif; ?>
        </div>
        <div class="d-flex gap-2">
            <a href="<?php echo APP_URL; ?>/application-admission/entries?id=<?php echo (int) ($sch['schedule_id'] ?? 0); ?>" class="btn btn-sm btn-outline-secondary">Applicants</a>
            <a href="<?php echo APP_URL; ?>/application-admission/pdf-selection?id=<?php echo (int) ($sch['schedule_id'] ?? 0); ?>" class="btn btn-sm btn-outline-dark"><i class="fas fa-file-pdf me-1"></i> PDF</a>
        </div>
    </div>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $e($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $e($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <?php if (empty($canUpdateSelection)): ?>
    <div class="alert alert-secondary py-2 small mb-3"><?php echo $viewOnlyMsg; ?></div>
    <?php endif; ?>

    <?php if (!empty($canUpdateSelection)): ?>
    <form method="post" action="<?php echo APP_URL; ?>/application-admission/selection-save">
        <input type="hidden" name="schedule_id" value="<?php echo (int) ($sch['schedule_id'] ?? 0); ?>">
    <?php endif; ?>

        <div class="table-responsive border rounded">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>NIC</th>
                        <th>Course</th>
                        <th><?php echo $e($resultColumn); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($entries)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No applicants.</td></tr>
                <?php else: ?>
                    <?php $n = 0; foreach ($entries as $row): $n++; ?>
                    <tr>
                        <td><?php echo $n; ?></td>
                        <td><?php echo $e($row['student_full_name'] ?? ''); ?></td>
                        <td><?php echo $e($row['student_nic'] ?? ''); ?></td>
                        <td class="small"><?php echo $e($row['course_priority_1'] ?? ''); ?></td>
                        <td>
                            <?php if (!empty($canUpdateSelection)): ?>
                            <select name="selection[<?php echo (int) $row['entry_id']; ?>]" class="form-select form-select-sm">
                                <?php foreach ($statusOptions as $val => $label): ?>
                                <option value="<?php echo $e($val); ?>" <?php echo ($row['selection_status'] ?? '') === $val ? 'selected' : ''; ?>><?php echo $e($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php else: ?>
                                <?php
                                $st = $row['selection_status'] ?? '';
                                echo $e($statusOptions[$st] ?? $statusLabelsPlain[$st] ?? $st);
                                ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

    <?php if (!empty($canUpdateSelection)): ?>
        <button type="submit" class="btn btn-primary mt-3"><?php echo $e($saveLabel); ?></button>
    </form>
    <?php endif; ?>
</div>
