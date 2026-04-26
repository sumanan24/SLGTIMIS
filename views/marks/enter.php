<?php
/** @var list<array<string,mixed>> $exams_list */
/** @var array<string,mixed>|null $exam */
/** @var list<array<string,mixed>> $modules */
/** @var list<array<string,mixed>> $rows */
/** @var int $selected_exam_id */
/** @var string $selected_module_id */
$base = rtrim(APP_URL, '/');
$h = static function (string $path) use ($base): string {
    return htmlspecialchars($base . '/' . ltrim($path, '/'), ENT_QUOTES, 'UTF-8');
};
$cell = static function (array $r, string $col, ?string $legacy = null): string {
    $v = $r[$col] ?? null;
    if (($v === null || $v === '') && $legacy !== null) {
        $v = $r[$legacy] ?? null;
    }
    return ($v !== null && $v !== '') ? (string) $v : '';
};
?>
<div class="container-fluid py-3">
    <div class="mb-4">
        <h1 class="h3 mb-0"><i class="fas fa-pen-to-square text-primary me-2"></i>Marks — 1st &amp; 2nd (7 questions + final each)</h1>
        <p class="text-muted small mb-0">Per module: enter Q1–Q7 and final for first and second marking. PDFs match these columns.</p>
    </div>

    <?php if (!empty($_SESSION['message'])): ?>
        <div class="alert alert-success alert-dismissible fade show"><?php echo htmlspecialchars($_SESSION['message'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?php echo htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="get" action="<?php echo $h('marks/enter'); ?>" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">Exam</label>
                    <select name="exam_id" class="form-select" onchange="this.form.submit()">
                        <option value="">Choose exam…</option>
                        <?php foreach ($exams_list as $ex): ?>
                            <option value="<?php echo (int)$ex['id']; ?>" <?php echo ((int)$selected_exam_id === (int)$ex['id']) ? 'selected' : ''; ?>>
                                #<?php echo (int)$ex['id']; ?> — <?php echo htmlspecialchars((string)($ex['course_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?> — <?php echo htmlspecialchars((string)($ex['exam_date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Module</label>
                    <select name="module_id" class="form-select" <?php echo empty($modules) ? 'disabled' : ''; ?> onchange="this.form.submit()">
                        <option value=""><?php echo empty($modules) ? 'No modules on this exam' : 'Choose module…'; ?></option>
                        <?php foreach ($modules as $m): ?>
                            <?php $mid = (string)($m['module_id'] ?? ''); if ($mid === '') { continue; } ?>
                            <option value="<?php echo htmlspecialchars($mid, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($selected_module_id === $mid) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($mid, ENT_QUOTES, 'UTF-8'); ?>
                                <?php if (($m['exam_date'] ?? '') !== ''): ?> — <?php echo htmlspecialchars((string)$m['exam_date'], ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <a href="<?php echo $h('exams'); ?>" class="btn btn-outline-secondary">Back to exams</a>
                </div>
            </form>
        </div>
    </div>

    <?php if (!empty($exam) && $selected_exam_id > 0 && $selected_module_id !== ''): ?>
    <form method="post" action="<?php echo $h('marks/save'); ?>">
        <input type="hidden" name="exam_id" value="<?php echo (int)$selected_exam_id; ?>">
        <input type="hidden" name="module_id" value="<?php echo htmlspecialchars($selected_module_id, ENT_QUOTES, 'UTF-8'); ?>">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <strong><?php echo htmlspecialchars((string)($exam['course_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                    <span class="text-muted small">— Module <?php echo htmlspecialchars($selected_module_id, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i>Save marks</button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 70vh;">
                    <table class="table table-hover table-bordered mb-0 align-middle text-nowrap small">
                        <thead class="table-light">
                            <tr>
                                <th rowspan="2" class="align-middle">#</th>
                                <th rowspan="2" class="align-middle">Reg. no.</th>
                                <th rowspan="2" class="align-middle">Name</th>
                                <th colspan="8" class="text-center border-primary">1st marking</th>
                                <th colspan="8" class="text-center border-info">2nd marking</th>
                                <th rowspan="2" class="align-middle">Admission</th>
                            </tr>
                            <tr>
                                <?php for ($q = 1; $q <= 7; $q++): ?>
                                    <th class="py-1">Q<?php echo $q; ?></th>
                                <?php endfor; ?>
                                <th class="py-1">Final</th>
                                <?php for ($q = 1; $q <= 7; $q++): ?>
                                    <th class="py-1">Q<?php echo $q; ?></th>
                                <?php endfor; ?>
                                <th class="py-1">Final</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 0; foreach ($rows as $r): $i++;
                                $sid = (string)($r['student_id'] ?? '');
                            ?>
                            <tr>
                                <td><?php echo $i; ?></td>
                                <td><?php echo htmlspecialchars($sid, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars((string)($r['student_fullname'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <?php for ($q = 1; $q <= 7; $q++): ?>
                                    <td class="p-1">
                                        <input type="text" class="form-control form-control-sm py-0 px-1" style="min-width:3.25rem;" name="marks_first[<?php echo htmlspecialchars($sid, ENT_QUOTES, 'UTF-8'); ?>][q<?php echo $q; ?>]" inputmode="decimal"
                                               value="<?php echo htmlspecialchars($cell($r, 'marks_q' . $q), ENT_QUOTES, 'UTF-8'); ?>">
                                    </td>
                                <?php endfor; ?>
                                <td class="p-1">
                                    <input type="text" class="form-control form-control-sm py-0 px-1" style="min-width:3.5rem;" name="marks_first[<?php echo htmlspecialchars($sid, ENT_QUOTES, 'UTF-8'); ?>][final]" inputmode="decimal"
                                           value="<?php echo htmlspecialchars($cell($r, 'marks_final', 'marks'), ENT_QUOTES, 'UTF-8'); ?>">
                                </td>
                                <?php for ($q = 1; $q <= 7; $q++): ?>
                                    <td class="p-1">
                                        <input type="text" class="form-control form-control-sm py-0 px-1" style="min-width:3.25rem;" name="marks_second[<?php echo htmlspecialchars($sid, ENT_QUOTES, 'UTF-8'); ?>][q<?php echo $q; ?>]" inputmode="decimal"
                                               value="<?php echo htmlspecialchars($cell($r, 'marks_second_q' . $q), ENT_QUOTES, 'UTF-8'); ?>">
                                    </td>
                                <?php endfor; ?>
                                <td class="p-1">
                                    <input type="text" class="form-control form-control-sm py-0 px-1" style="min-width:3.5rem;" name="marks_second[<?php echo htmlspecialchars($sid, ENT_QUOTES, 'UTF-8'); ?>][final]" inputmode="decimal"
                                           value="<?php echo htmlspecialchars($cell($r, 'marks_second_final', 'marks_second'), ENT_QUOTES, 'UTF-8'); ?>">
                                </td>
                                <td>
                                    <a class="btn btn-sm btn-outline-secondary py-0" target="_blank" href="<?php echo $h('print/admission-card?exam_id=' . (int)$selected_exam_id . '&student_id=' . urlencode($sid)); ?>">Admission</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </form>
    <?php else: ?>
        <p class="text-muted">Select an exam and module to enter marks.</p>
    <?php endif; ?>
</div>
