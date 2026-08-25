<?php
/** @var list<array<string,mixed>> $exams */
$h = static function (string $path): string {
    return htmlspecialchars(rtrim(APP_URL, '/') . '/' . ltrim($path, '/'), ENT_QUOTES, 'UTF-8');
};
$e = static function (?string $s): string {
    return htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
};
$base = rtrim(APP_URL, '/');
?>
<link rel="stylesheet" href="<?php echo $e($base . '/assets/css/exams.css'); ?>?v=<?php echo (int) @filemtime(BASE_PATH . '/assets/css/exams.css'); ?>">

<div class="container-fluid px-3 px-md-4 py-3">
    <div class="exams-page-header d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h1 class="page-title"><i class="fas fa-file-signature text-primary me-2"></i>Exams</h1>
            <p class="page-lead">Create exams, register students, download admission forms, and enter marks per module.</p>
        </div>
        <a href="<?php echo $h('exams/create'); ?>" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>Create exam</a>
    </div>

    <?php if (!empty($_SESSION['message'])): ?>
        <div class="alert alert-success alert-dismissible fade show py-2"><?php echo $e($_SESSION['message']); unset($_SESSION['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show py-2"><?php echo $e($_SESSION['error']); unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="exams-card">
        <div class="card-body">
            <?php if (empty($exams)): ?>
                <div class="exams-empty">
                    <i class="fas fa-clipboard-list fa-2x text-muted mb-2 d-block"></i>
                    <p class="mb-2">No exams yet.</p>
                    <a href="<?php echo $h('exams/create'); ?>" class="btn btn-sm btn-primary">Create your first exam</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="exams-table exams-table-compact">
                        <colgroup>
                            <col class="col-id">
                            <col class="col-course">
                            <col class="col-sem">
                            <col class="col-date">
                            <col class="col-time">
                            <col class="col-venue">
                            <col class="col-students">
                            <col class="col-actions">
                        </colgroup>
                        <thead>
                            <tr>
                                <th class="col-id">#</th>
                                <th class="col-course">Course</th>
                                <th class="col-sem">Sem.</th>
                                <th class="col-date">Date</th>
                                <th class="col-time">Time</th>
                                <th class="col-venue">Venue</th>
                                <th class="col-students">Students</th>
                                <th class="col-actions text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($exams as $ex):
                                $examId = (int) ($ex['id'] ?? 0);
                                $courseName = (string) ($ex['course_name'] ?? '');
                                $courseId = (string) ($ex['course_id'] ?? '');
                            ?>
                                <tr class="exam-main-row">
                                    <td class="col-id fw-semibold"><?php echo $examId; ?></td>
                                    <td class="col-course">
                                        <a href="<?php echo $h('exams/view?id=' . $examId); ?>" class="course-name text-decoration-none fw-medium">
                                            <?php echo $e($courseName !== '' ? $courseName : $courseId); ?>
                                        </a>
                                        <?php if ($courseName !== '' && $courseId !== ''): ?>
                                            <span class="course-id"><?php echo $e($courseId); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="col-sem">
                                        <?php echo isset($ex['semester']) && $ex['semester'] !== null && $ex['semester'] !== '' ? (int) $ex['semester'] : '—'; ?>
                                    </td>
                                    <td class="col-date"><?php echo $e((string) ($ex['exam_date'] ?? '')); ?></td>
                                    <td class="col-time"><?php echo $e((string) ($ex['exam_time'] ?? '')); ?></td>
                                    <td class="col-venue">
                                        <span class="d-inline-block text-truncate" style="max-width:100%;" title="<?php echo $e((string) ($ex['location'] ?? '')); ?>">
                                            <?php echo $e((string) ($ex['location'] ?? '')); ?>
                                        </span>
                                    </td>
                                    <td class="col-students">
                                        <span class="badge bg-light text-dark border"><?php echo (int) ($ex['student_count'] ?? 0); ?></span>
                                    </td>
                                    <td class="col-actions">
                                        <div class="d-flex flex-wrap justify-content-end gap-1">
                                            <a class="btn btn-sm btn-outline-secondary" href="<?php echo $h('exams/view?id=' . $examId); ?>" title="View modules &amp; students"><i class="fas fa-eye"></i></a>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Admission PDFs">
                                                    <i class="fas fa-id-card"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li><a class="dropdown-item" href="<?php echo $h('exams/admission-select?exam_id=' . $examId); ?>"><i class="fas fa-user-check me-2 text-muted"></i>Select students</a></li>
                                                    <li><a class="dropdown-item" target="_blank" href="<?php echo $h('print/admission-cards-bulk?exam_id=' . $examId); ?>"><i class="fas fa-users me-2 text-muted"></i>All registered</a></li>
                                                </ul>
                                            </div>
                                            <a class="btn btn-sm btn-outline-primary" href="<?php echo $h('exams/edit?id=' . $examId); ?>" title="Edit exam"><i class="fas fa-pen"></i></a>
                                            <form method="post" action="<?php echo $h('exams/delete'); ?>" class="d-inline" onsubmit="return confirm('Delete exam #<?php echo $examId; ?>? This removes registered students and module marks for this exam.');">
                                                <input type="hidden" name="exam_id" value="<?php echo $examId; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete exam"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
