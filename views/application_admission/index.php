<?php
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
$isInterview = ($tab ?? '') === ApplicationAdmissionScheduleModel::TYPE_INTERVIEW;
$typeLabel = $isInterview ? 'Interview' : 'Entrance exam';
$listBaseUrl = rtrim((string) ($listBaseUrl ?? (APP_URL . '/application-admission')), '/');
$entranceUrl = rtrim(APP_URL, '/') . '/application-admission';
$interviewUrl = rtrim(APP_URL, '/') . '/application-admission/interviews';
$createType = $isInterview ? 'interview' : 'entrance_exam';
$colspan = $isInterview ? 9 : 10;
$schedules = is_array($schedules ?? null) ? $schedules : [];
?>
<style>
.aa-page {
    width: 100%;
    max-width: none;
    margin: 0;
    padding-bottom: 1.5rem;
}
.aa-page-header {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem 1rem;
    margin-bottom: 1rem;
}
.aa-page-header h1 {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0 0 0.25rem;
    line-height: 1.3;
}
.aa-page-header .aa-sub {
    margin: 0;
    font-size: 0.8125rem;
    color: #6c757d;
}
.aa-module-nav {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
    margin-bottom: 1rem;
    padding: 0.35rem;
    background: #f1f3f5;
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
}
.aa-module-nav a {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.45rem 0.9rem;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #495057;
    text-decoration: none;
    border: 1px solid transparent;
}
.aa-module-nav a:hover {
    background: #fff;
    color: #0d6efd;
}
.aa-module-nav a.is-active {
    background: #fff;
    color: #0d6efd;
    border-color: #cfe2ff;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
}
.aa-filters {
    display: flex;
    flex-wrap: wrap;
    align-items: end;
    gap: 0.75rem 1rem;
    margin-bottom: 1rem;
    padding: 0.75rem 1rem;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
}
.aa-filters .aa-field {
    min-width: 8rem;
}
.aa-filters label {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.25rem;
}
.aa-table-wrap {
    width: 100%;
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
    background: #fff;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.aa-table {
    width: 100%;
    min-width: 100%;
    margin: 0;
    table-layout: auto;
    border-collapse: separate;
    border-spacing: 0;
}
.aa-table thead th {
    padding: 0.65rem 0.85rem;
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
.aa-table tbody td {
    padding: 0.65rem 0.85rem;
    font-size: 0.875rem;
    vertical-align: middle;
    border-bottom: 1px solid #eef1f4;
    color: #212529;
}
.aa-table tbody tr:last-child td {
    border-bottom: none;
}
.aa-table tbody tr:hover td {
    background: rgba(13, 110, 253, 0.035);
}
.aa-table .aa-col-no {
    width: 3rem;
    text-align: center;
    color: #6c757d;
}
.aa-table .aa-col-level {
    width: 4.5rem;
    text-align: center;
    white-space: nowrap;
    font-weight: 600;
}
.aa-table .aa-col-date {
    white-space: nowrap;
}
.aa-table .aa-col-count,
.aa-table .aa-col-pub {
    text-align: center;
    white-space: nowrap;
}
.aa-table .aa-col-title,
.aa-table .aa-col-course,
.aa-table .aa-col-dept,
.aa-table .aa-col-venue {
    white-space: normal;
    word-break: break-word;
}
.aa-table .aa-col-title {
    min-width: 12rem;
    font-weight: 600;
}
.aa-table .aa-col-course {
    min-width: 11rem;
}
.aa-table .aa-col-dept {
    min-width: 8rem;
}
.aa-table .aa-col-venue {
    min-width: 8rem;
}
.aa-table .aa-col-actions {
    text-align: right;
    white-space: nowrap;
}
.aa-table .aa-col-actions .btn {
    margin: 0.1rem;
}
.aa-empty {
    text-align: center;
    color: #6c757d;
    padding: 2rem 1rem !important;
}
</style>

<div class="container-fluid px-3 px-md-4 aa-page">
    <div class="aa-page-header">
        <div>
            <h1><?php echo $e($typeLabel); ?> schedules</h1>
            <p class="aa-sub"><?php echo $isInterview
                ? 'Department and course interviews — add only entrance exam Selected students.'
                : 'Entrance exams by centre / language. Mark Selected candidates for interview schedules.'; ?></p>
        </div>
        <?php if (!empty($canManage)): ?>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?php echo APP_URL; ?>/application-admission/create?type=<?php echo $e($createType); ?>" class="btn btn-primary btn-sm">
                <i class="fas fa-plus me-1"></i> New <?php echo $e(strtolower($typeLabel)); ?>
            </a>
        </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success py-2"><?php echo $e($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger py-2"><?php echo $e($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <nav class="aa-module-nav" aria-label="Admission schedules">
        <a class="<?php echo !$isInterview ? 'is-active' : ''; ?>" href="<?php echo $e($entranceUrl); ?>">
            <i class="fas fa-clipboard-list"></i> Entrance exams
        </a>
        <a class="<?php echo $isInterview ? 'is-active' : ''; ?>" href="<?php echo $e($interviewUrl); ?>">
            <i class="fas fa-comments"></i> Interviews
        </a>
    </nav>

    <form method="get" action="<?php echo $e($listBaseUrl); ?>" class="aa-filters">
        <div class="aa-field">
            <label for="aa_level">NVQ level</label>
            <select name="level" id="aa_level" class="form-select form-select-sm">
                <option value="">All</option>
                <option value="04" <?php echo ($levelFilter ?? '') === '04' ? 'selected' : ''; ?>>Level 04</option>
                <option value="05" <?php echo ($levelFilter ?? '') === '05' ? 'selected' : ''; ?>>Level 05</option>
            </select>
        </div>
        <?php if (!$isInterview): ?>
        <div class="aa-field" style="min-width:12rem;">
            <label for="aa_venue">Centre / Venue</label>
            <select name="venue" id="aa_venue" class="form-select form-select-sm">
                <option value="">All centres</option>
                <?php foreach (($venueOptions ?? []) as $venueOpt): ?>
                <option value="<?php echo $e($venueOpt); ?>" <?php echo ($venueFilter ?? '') === $venueOpt ? 'selected' : ''; ?>><?php echo $e($venueOpt); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <div class="aa-field">
            <label>&nbsp;</label>
            <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i> Apply</button>
        </div>
    </form>

    <div class="aa-table-wrap">
        <table class="table aa-table mb-0">
            <thead>
                <tr>
                    <th class="aa-col-no">#</th>
                    <th class="aa-col-title">Title</th>
                    <th class="aa-col-level">Level</th>
                    <?php if ($isInterview): ?>
                    <th class="aa-col-dept">Department</th>
                    <?php endif; ?>
                    <th class="aa-col-course">Course</th>
                    <th class="aa-col-date">Date</th>
                    <?php if (!$isInterview): ?>
                    <th class="aa-col-venue">Venue</th>
                    <?php endif; ?>
                    <th class="aa-col-count">Applicants</th>
                    <th class="aa-col-pub">Status</th>
                    <th class="aa-col-actions">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($schedules === []): ?>
                <tr><td colspan="<?php echo (int) $colspan; ?>" class="aa-empty">No <?php echo $e(strtolower($typeLabel)); ?> schedules yet.</td></tr>
            <?php else: ?>
                <?php $n = 0; foreach ($schedules as $s): $n++; ?>
                <tr>
                    <td class="aa-col-no"><?php echo $n; ?></td>
                    <td class="aa-col-title"><?php echo $e($s['title'] ?? ''); ?></td>
                    <td class="aa-col-level"><?php echo $e($s['application_level'] ?? ''); ?></td>
                    <?php if ($isInterview): ?>
                    <td class="aa-col-dept"><?php
                        $dn = trim((string) ($s['department_name'] ?? ''));
                        echo $e($dn !== '' ? $dn : '—');
                    ?></td>
                    <?php endif; ?>
                    <td class="aa-col-course"><?php
                        $cn = trim((string) ($s['course_name'] ?? ''));
                        echo $e($cn !== '' ? $cn : '—');
                    ?></td>
                    <td class="aa-col-date"><?php echo $e($s['schedule_date'] ?? ''); ?></td>
                    <?php if (!$isInterview): ?>
                    <td class="aa-col-venue"><?php echo $e($s['venue'] ?? '—'); ?></td>
                    <?php endif; ?>
                    <td class="aa-col-count"><?php echo (int) ($s['entry_count'] ?? 0); ?></td>
                    <td class="aa-col-pub">
                        <?php if (!empty($s['is_published'])): ?>
                            <span class="badge bg-success">Published</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Draft</span>
                        <?php endif; ?>
                    </td>
                    <td class="aa-col-actions">
                        <a href="<?php echo APP_URL; ?>/application-admission/entries?id=<?php echo (int) $s['schedule_id']; ?>" class="btn btn-sm btn-outline-secondary" title="Applicants"><i class="fas fa-users"></i></a>
                        <?php if ($isInterview): ?>
                        <a href="<?php echo APP_URL; ?>/application-admission/selection?id=<?php echo (int) $s['schedule_id']; ?>" class="btn btn-sm btn-outline-info" title="Selection list"><i class="fas fa-list-check"></i></a>
                        <?php else: ?>
                        <a href="<?php echo APP_URL; ?>/application-admission/selection?id=<?php echo (int) $s['schedule_id']; ?>" class="btn btn-sm btn-outline-info" title="Exam results"><i class="fas fa-clipboard-check"></i></a>
                        <?php endif; ?>
                        <a href="<?php echo APP_URL; ?>/application-admission/pdf-schedule?id=<?php echo (int) $s['schedule_id']; ?>" class="btn btn-sm btn-outline-dark" title="PDF"><i class="fas fa-file-pdf"></i></a>
                        <a href="<?php echo APP_URL; ?>/application-admission/export-participants?id=<?php echo (int) $s['schedule_id']; ?>" class="btn btn-sm btn-outline-success" title="Excel"><i class="fas fa-file-excel"></i></a>
                        <?php if (!empty($s['is_published'])): ?>
                        <a href="<?php echo $e($s['public_url'] ?? '#'); ?>" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" title="Public link"><i class="fas fa-link"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($canManage)): ?>
                        <a href="<?php echo APP_URL; ?>/application-admission/edit?id=<?php echo (int) $s['schedule_id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-edit"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
