<?php
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
$sch = $schedule ?? [];
$isInterview = ($sch['schedule_type'] ?? '') === ApplicationAdmissionScheduleModel::TYPE_INTERVIEW;
$headline = $isInterview ? 'Interview schedule' : 'Entrance examination schedule';
?>
<div class="app-form-card mx-auto" style="max-width:640px;">
    <h1 class="h4 mb-2"><?php echo $e($headline); ?></h1>
    <p class="mb-1"><strong><?php echo $e($sch['title'] ?? ''); ?></strong></p>
    <p class="text-muted small mb-3">
        NVQ Level <?php echo $e($sch['application_level'] ?? ''); ?>
        <?php if (!empty($sch['course_name'])): ?> · <?php echo $e($sch['course_name']); ?><?php endif; ?>
        · <?php echo $e($sch['schedule_date'] ?? ''); ?>
        <?php if (!empty($sch['venue'])): ?> · <?php echo $e($sch['venue']); ?><?php endif; ?>
    </p>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $e($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <?php if (!empty($sch['instructions'])): ?>
    <div class="alert alert-light border small mb-3"><?php echo nl2br($e($sch['instructions'])); ?></div>
    <?php endif; ?>

    <div class="d-grid gap-2 mb-4">
        <a href="<?php echo $e($pdfScheduleUrl ?? '#'); ?>" class="btn btn-primary" target="_blank" rel="noopener">
            <i class="fas fa-download me-2"></i>Download full schedule (PDF)
        </a>
        <?php if (!empty($pdfSelectionUrl)): ?>
        <a href="<?php echo $e($pdfSelectionUrl); ?>" class="btn btn-outline-primary" target="_blank" rel="noopener">
            <i class="fas fa-list me-2"></i>Download selection list (PDF)
        </a>
        <?php endif; ?>
    </div>

    <hr>
    <h2 class="h6">Personal admission / interview slip</h2>
    <p class="small text-muted">Enter your NIC number as on the application. If you are on this schedule, you can download your individual slip.</p>
    <form method="post" action="<?php echo $e($slipFormAction ?? '#'); ?>">
        <div class="mb-3">
            <label class="form-label">NIC number</label>
            <input type="text" name="nic" class="form-control" required autocomplete="off" placeholder="e.g. 200312345678 or 991234567V">
        </div>
        <button type="submit" class="btn btn-outline-dark w-100">Download my slip (PDF)</button>
    </form>
</div>
