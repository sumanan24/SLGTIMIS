<?php
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
$typeLabel = $tab === ApplicationAdmissionScheduleModel::TYPE_INTERVIEW ? 'Interview' : 'Entrance exam';
?>
<div class="container-fluid px-3 px-md-4 application-admission-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">Admission &amp; interview schedules</h1>
            <p class="text-muted small mb-0">Plan entrance exams and interviews per course. Choose <strong>Exam and interview</strong> or <strong>Interview only</strong> when creating a schedule (suggested from approved application count).</p>
        </div>
        <?php if (!empty($canManage)): ?>
        <div class="d-flex gap-2">
            <a href="<?php echo APP_URL; ?>/application-admission/create?type=entrance_exam" class="btn btn-primary btn-sm">
                <i class="fas fa-plus me-1"></i> Entrance exam
            </a>
            <a href="<?php echo APP_URL; ?>/application-admission/create?type=interview" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-plus me-1"></i> Interview
            </a>
        </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $e($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $e($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link <?php echo $tab === ApplicationAdmissionScheduleModel::TYPE_ENTRANCE ? 'active' : ''; ?>"
               href="<?php echo APP_URL; ?>/application-admission?tab=entrance_exam">Entrance exams</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $tab === ApplicationAdmissionScheduleModel::TYPE_INTERVIEW ? 'active' : ''; ?>"
               href="<?php echo APP_URL; ?>/application-admission?tab=interview">Interviews</a>
        </li>
    </ul>

    <form method="get" class="row g-2 align-items-end mb-3">
        <input type="hidden" name="tab" value="<?php echo $e($tab); ?>">
        <div class="col-auto">
            <label class="form-label small mb-0">NVQ level</label>
            <select name="level" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All</option>
                <option value="04" <?php echo ($levelFilter ?? '') === '04' ? 'selected' : ''; ?>>Level 04</option>
                <option value="05" <?php echo ($levelFilter ?? '') === '05' ? 'selected' : ''; ?>>Level 05</option>
            </select>
        </div>
    </form>

    <div class="table-responsive border rounded">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width:3rem;">#</th>
                    <th>Title</th>
                    <th>Level</th>
                    <th>Course</th>
                    <th>Pathway</th>
                    <th>Date</th>
                    <th>Venue</th>
                    <th class="text-center">Applicants</th>
                    <th class="text-center">Published</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($schedules)): ?>
                <tr><td colspan="10" class="text-muted text-center py-4">No <?php echo $e(strtolower($typeLabel)); ?> schedules yet.</td></tr>
            <?php else: ?>
                <?php $n = 0; foreach ($schedules as $s): $n++; ?>
                <tr>
                    <td class="text-muted"><?php echo $n; ?></td>
                    <td><?php echo $e($s['title'] ?? ''); ?></td>
                    <td><?php echo $e($s['application_level'] ?? ''); ?></td>
                    <td class="small"><?php
                        $cn = trim((string) ($s['course_name'] ?? ''));
                        echo $e($cn !== '' ? $cn : '—');
                    ?></td>
                    <td class="small"><?php
                        $pw = ApplicationAdmissionScheduleModel::normalizePathway($s['admission_pathway'] ?? null);
                        echo $e(ApplicationAdmissionScheduleModel::pathwayLabel($pw));
                    ?></td>
                    <td><?php echo $e($s['schedule_date'] ?? ''); ?></td>
                    <td><?php echo $e($s['venue'] ?? ''); ?></td>
                    <td class="text-center"><?php echo (int) ($s['entry_count'] ?? 0); ?></td>
                    <td class="text-center">
                        <?php if (!empty($s['is_published'])): ?>
                            <span class="badge bg-success">Yes</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Draft</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <a href="<?php echo APP_URL; ?>/application-admission/entries?id=<?php echo (int) $s['schedule_id']; ?>" class="btn btn-sm btn-outline-secondary" title="Applicants"><i class="fas fa-users"></i></a>
                        <?php if (($s['schedule_type'] ?? '') === ApplicationAdmissionScheduleModel::TYPE_INTERVIEW): ?>
                        <a href="<?php echo APP_URL; ?>/application-admission/selection?id=<?php echo (int) $s['schedule_id']; ?>" class="btn btn-sm btn-outline-info" title="Selection list"><i class="fas fa-list-check"></i></a>
                        <?php elseif (($s['schedule_type'] ?? '') === ApplicationAdmissionScheduleModel::TYPE_ENTRANCE): ?>
                        <a href="<?php echo APP_URL; ?>/application-admission/selection?id=<?php echo (int) $s['schedule_id']; ?>" class="btn btn-sm btn-outline-info" title="Exam results"><i class="fas fa-clipboard-check"></i></a>
                        <?php endif; ?>
                        <a href="<?php echo APP_URL; ?>/application-admission/pdf-schedule?id=<?php echo (int) $s['schedule_id']; ?>" class="btn btn-sm btn-outline-dark" title="PDF"><i class="fas fa-file-pdf"></i></a>
                        <?php if (!empty($s['is_published'])): ?>
                        <a href="<?php echo $e($s['public_url'] ?? '#'); ?>" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" title="Public link"><i class="fas fa-link"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($canManage)): ?>
                        <a href="<?php echo APP_URL; ?>/application-admission/edit?id=<?php echo (int) $s['schedule_id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
