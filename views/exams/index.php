<?php
/** @var list<array<string,mixed>> $exams */
$h = static function (string $path): string {
    return htmlspecialchars(rtrim(APP_URL, '/') . '/' . ltrim($path, '/'), ENT_QUOTES, 'UTF-8');
};
?>
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h3 mb-0"><i class="fas fa-file-signature text-primary me-2"></i>Exams</h1>
            <p class="text-muted small mb-0">Per-module PDFs and marks; edit or delete an exam from the list.</p>
        </div>
        <a href="<?php echo $h('exams/create'); ?>" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Create exam</a>
    </div>

    <?php if (!empty($_SESSION['message'])): ?>
        <div class="alert alert-success alert-dismissible fade show"><?php echo htmlspecialchars($_SESSION['message'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?php echo htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Course</th>
                            <th class="text-center">Sem.</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Venue</th>
                            <th class="text-center">Students</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($exams)): ?>
                            <tr><td colspan="8" class="text-center text-muted py-4">No exams yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($exams as $ex): ?>
                                <tr>
                                    <td><?php echo (int) ($ex['id'] ?? 0); ?></td>
                                    <td><?php echo htmlspecialchars((string) ($ex['course_name'] ?? $ex['course_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="text-center"><?php echo isset($ex['semester']) && $ex['semester'] !== null && $ex['semester'] !== '' ? (int) $ex['semester'] : '—'; ?></td>
                                    <td><?php echo htmlspecialchars((string) ($ex['exam_date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string) ($ex['exam_time'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string) ($ex['location'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="text-center"><?php echo (int) ($ex['student_count'] ?? 0); ?></td>
                                    <td>
                                        <a class="btn btn-sm btn-outline-success" href="<?php echo $h('exams/admission-select?exam_id=' . (int)$ex['id']); ?>" title="Choose students, then download admission PDF"><i class="fas fa-id-card me-1"></i>Admission</a>
                                        <a class="btn btn-sm btn-outline-secondary" target="_blank" href="<?php echo $h('print/admission-cards-bulk?exam_id=' . (int)$ex['id']); ?>" title="Admission PDF for every registered student"><i class="fas fa-users me-1"></i>Admission (all)</a>
                                        <a class="btn btn-sm btn-outline-primary" href="<?php echo $h('exams/edit?id=' . (int)$ex['id']); ?>"><i class="fas fa-pen me-1"></i>Edit</a>
                                        <form method="post" action="<?php echo $h('exams/delete'); ?>" class="d-inline" onsubmit="return confirm('Delete exam #<?php echo (int)$ex['id']; ?>? This removes registered students and module marks for this exam.');">
                                            <input type="hidden" name="exam_id" value="<?php echo (int)$ex['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash me-1"></i>Delete</button>
                                        </form>
                                    </td>
                                </tr>
                                <tr class="table-light">
                                    <td colspan="8" class="small py-2">
                                        <?php
                                        $mods = $ex['_modules'] ?? [];
                                        if (empty($mods)) {
                                            echo '<span class="text-muted">No modules in schedule.</span>';
                                        } else {
                                            foreach ($mods as $m) {
                                                $mid = trim((string) ($m['module_id'] ?? ''));
                                                if ($mid === '') {
                                                    continue;
                                                }
                                                $q = 'exam_id=' . (int) $ex['id'] . '&module_id=' . rawurlencode($mid);
                                                $mq = 'marks/enter?' . $q;
                                                $pq = 'print/';
                                                ?>
                                                <div class="d-flex flex-wrap align-items-center gap-2 mb-1 pb-1 border-bottom border-light-subtle">
                                                    <span class="fw-semibold"><?php echo htmlspecialchars($mid, ENT_QUOTES, 'UTF-8'); ?></span>
                                                    <?php if (($m['exam_date'] ?? '') !== ''): ?>
                                                        <span class="text-muted"><?php echo htmlspecialchars((string) $m['exam_date'], ENT_QUOTES, 'UTF-8'); ?></span>
                                                    <?php endif; ?>
                                                    <?php if (($m['exam_time'] ?? '') !== ''): ?>
                                                        <span class="text-muted"><?php echo htmlspecialchars((string) $m['exam_time'], ENT_QUOTES, 'UTF-8'); ?></span>
                                                    <?php endif; ?>
                                                    <?php if (($m['location'] ?? '') !== ''): ?>
                                                        <span class="text-muted">@ <?php echo htmlspecialchars((string) $m['location'], ENT_QUOTES, 'UTF-8'); ?></span>
                                                    <?php endif; ?>
                                                    <span class="ms-auto d-flex flex-wrap gap-1">
                                                        <a class="btn btn-sm btn-outline-secondary py-0 px-2" target="_blank" href="<?php echo $h($pq . 'attendance-sheet?' . $q); ?>">Attendance</a>
                                                        <a class="btn btn-sm btn-outline-secondary py-0 px-2" target="_blank" href="<?php echo $h($pq . 'first-mark-sheet?' . $q); ?>">1st</a>
                                                        <a class="btn btn-sm btn-outline-secondary py-0 px-2" target="_blank" href="<?php echo $h($pq . 'second-mark-sheet?' . $q); ?>">2nd</a>
                                                        <a class="btn btn-sm btn-outline-primary py-0 px-2" href="<?php echo $h($mq); ?>">Marks</a>
                                                    </span>
                                                </div>
                                                <?php
                                            }
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
