<?php
/** @var array<string,mixed> $exam */
/** @var list<array<string,mixed>> $modules */
/** @var list<array{student_id: string, display_name: string}> $students */
$h = static function (string $path): string {
    return htmlspecialchars(rtrim(APP_URL, '/') . '/' . ltrim($path, '/'), ENT_QUOTES, 'UTF-8');
};
$e = static function (?string $s): string {
    return htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
};
$base = rtrim(APP_URL, '/');
$examId = (int) ($exam['id'] ?? 0);
$courseName = (string) ($exam['course_name'] ?? '');
$courseId = (string) ($exam['course_id'] ?? '');
?>
<link rel="stylesheet" href="<?php echo $e($base . '/assets/css/exams.css'); ?>?v=<?php echo (int) @filemtime(BASE_PATH . '/assets/css/exams.css'); ?>">

<div class="container-fluid px-3 px-md-4 py-3">
    <div class="exams-page-header d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h1 class="page-title"><i class="fas fa-eye text-primary me-2"></i>Exam #<?php echo $examId; ?></h1>
            <p class="page-lead mb-0">
                <?php echo $e($courseName !== '' ? $courseName : $courseId); ?>
                <?php if (isset($exam['semester']) && $exam['semester'] !== null && $exam['semester'] !== ''): ?>
                    · Semester <?php echo (int) $exam['semester']; ?>
                <?php endif; ?>
                · <?php echo count($students); ?> student<?php echo count($students) === 1 ? '' : 's'; ?>
            </p>
        </div>
        <div class="d-flex flex-wrap gap-1">
            <a href="<?php echo $h('exams'); ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Back</a>
            <a href="<?php echo $h('exams/edit?id=' . $examId); ?>" class="btn btn-outline-primary btn-sm"><i class="fas fa-pen me-1"></i>Edit</a>
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-outline-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-id-card me-1"></i>Admission
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="<?php echo $h('exams/admission-select?exam_id=' . $examId); ?>">Select students</a></li>
                    <li><a class="dropdown-item" target="_blank" href="<?php echo $h('print/admission-cards-bulk?exam_id=' . $examId); ?>">All registered</a></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="exams-card">
                <div class="card-body p-3">
                    <div class="exam-form-section-title mb-2">Exam summary</div>
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted">Course</dt>
                        <dd class="col-7"><?php echo $e($courseName !== '' ? $courseName : $courseId); ?></dd>
                        <dt class="col-5 text-muted">Course ID</dt>
                        <dd class="col-7"><?php echo $e($courseId); ?></dd>
                        <dt class="col-5 text-muted">Semester</dt>
                        <dd class="col-7"><?php echo isset($exam['semester']) && $exam['semester'] !== null && $exam['semester'] !== '' ? (int) $exam['semester'] : '—'; ?></dd>
                        <dt class="col-5 text-muted">Date</dt>
                        <dd class="col-7"><?php echo $e((string) ($exam['exam_date'] ?? '')); ?></dd>
                        <dt class="col-5 text-muted">Time</dt>
                        <dd class="col-7"><?php echo $e((string) ($exam['exam_time'] ?? '')); ?></dd>
                        <dt class="col-5 text-muted">Venue</dt>
                        <dd class="col-7"><?php echo $e((string) ($exam['location'] ?? '')); ?></dd>
                        <dt class="col-5 text-muted">Students</dt>
                        <dd class="col-7"><?php echo count($students); ?></dd>
                        <dt class="col-5 text-muted">Modules</dt>
                        <dd class="col-7"><?php echo count($modules); ?></dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="exams-card mb-3">
                <div class="card-body p-0">
                    <div class="p-3 pb-2 border-bottom">
                        <div class="exam-form-section-title mb-0">Module schedule (<?php echo count($modules); ?>)</div>
                    </div>
                    <?php if (empty($modules)): ?>
                        <p class="text-muted small p-3 mb-0">No modules in schedule.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="exams-modules-table mb-0">
                                <thead>
                                    <tr>
                                        <th style="width:14%;">Module</th>
                                        <th style="width:26%;">Name</th>
                                        <th style="width:12%;">Date</th>
                                        <th style="width:12%;">Time</th>
                                        <th style="width:16%;">Venue</th>
                                        <th style="width:20%;" class="text-end">Print / marks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($modules as $m):
                                        $mid = trim((string) ($m['module_id'] ?? ''));
                                        if ($mid === '') {
                                            continue;
                                        }
                                        $q = 'exam_id=' . $examId . '&module_id=' . rawurlencode($mid);
                                    ?>
                                        <tr>
                                            <td><span class="mod-code"><?php echo $e($mid); ?></span></td>
                                            <td><?php echo $e((string) ($m['module_name'] ?? '')); ?></td>
                                            <td><?php echo $e((string) ($m['exam_date'] ?? '')); ?></td>
                                            <td><?php echo $e((string) ($m['exam_time'] ?? '')); ?></td>
                                            <td><?php echo $e((string) ($m['location'] ?? '')); ?></td>
                                            <td class="mod-actions">
                                                <a class="btn btn-outline-secondary" target="_blank" href="<?php echo $h('print/attendance-sheet?' . $q); ?>" title="Attendance"><i class="fas fa-clipboard-check"></i></a>
                                                <a class="btn btn-outline-secondary" target="_blank" href="<?php echo $h('print/first-mark-sheet?' . $q); ?>" title="1st sheet"><i class="fas fa-file-alt"></i></a>
                                                <a class="btn btn-outline-secondary" target="_blank" href="<?php echo $h('print/second-mark-sheet?' . $q); ?>" title="2nd sheet"><i class="fas fa-file-alt"></i></a>
                                                <a class="btn btn-outline-primary" href="<?php echo $h('marks/enter?' . $q); ?>" title="Marks"><i class="fas fa-pen"></i></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="exams-card exam-roll-stickers-wrap" data-exam-id="<?php echo $examId; ?>" data-stickers-pdf-url="<?php echo $h('print/exam-roll-stickers?exam_id=' . $examId); ?>">
                <div class="card-body p-0">
                    <div class="p-3 pb-2 border-bottom">
                        <div class="exam-form-section-title mb-0">Registered students (<?php echo count($students); ?>)</div>
                    </div>
                    <?php if (empty($students)): ?>
                        <p class="text-muted small p-3 mb-0">No students registered.</p>
                    <?php else: ?>
                        <div class="exam-roll-toolbar px-3 py-2 border-bottom">
                            <label for="zebra-printer-select">Printer</label>
                            <select id="zebra-printer-select" class="form-select form-select-sm" title="Select Zebra printer">
                                <option value="">Detecting printers…</option>
                            </select>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-refresh-zebra-printers" title="Refresh printer list"><i class="fas fa-sync-alt"></i></button>
                            <label for="barcode-copies">Copies</label>
                            <select id="barcode-copies" class="form-select form-select-sm" title="Number of stickers per roll number">
                                <option value="1" selected>1</option>
                                <option value="2">2</option>
                                <option value="5">5</option>
                                <option value="10">10</option>
                            </select>
                            <button type="button" class="btn btn-dark btn-sm" id="btn-print-all-roll-numbers" title="Preview then print all roll-number stickers"><i class="fas fa-eye me-1"></i> Preview &amp; Print Roll Numbers</button>
                            <button type="button" class="btn btn-outline-dark btn-sm" id="btn-download-stickers-pdf" title="Download stickers as PDF (student registration no.)"><i class="fas fa-file-pdf me-1"></i> Stickers PDF</button>
                            <span class="text-muted small">2 stickers parallel · 50×25 mm · existing student reg. no.</span>
                        </div>
                        <div class="table-responsive">
                            <table class="exams-students-table mb-0">
                                <thead>
                                    <tr>
                                        <th style="width:8%;">#</th>
                                        <th style="width:36%;">Student reg. no.</th>
                                        <th>Name (with initials)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $n = 0; foreach ($students as $stu): $n++;
                                        $regNo = trim((string) ($stu['student_id'] ?? ''));
                                    ?>
                                        <tr class="exam-student-row" data-roll-number="<?php echo $e($regNo); ?>" data-student-id="<?php echo $e($regNo); ?>">
                                            <td class="text-muted"><?php echo $n; ?></td>
                                            <td class="exam-roll-cell">
                                                <div class="exam-roll-no" title="<?php echo $e($regNo); ?>"><?php echo $e($regNo); ?></div>
                                            </td>
                                            <td><?php echo $e((string) ($stu['display_name'] ?? '')); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php if (!empty($students)): ?>
<script src="<?php echo $e($base . '/assets/js/zebra-browser-print-client.js'); ?>"></script>
<script src="<?php echo $e($base . '/assets/js/student-barcode-sticker.js'); ?>?v=<?php echo (int) @filemtime(BASE_PATH . '/assets/js/student-barcode-sticker.js'); ?>"></script>
<?php endif; ?>
