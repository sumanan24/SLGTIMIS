<?php
declare(strict_types=1);
$e = static function ($v): string {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};
$academicYears = is_array($academicYears ?? null) ? $academicYears : [];
$selectedYear = (string) ($selectedYear ?? '');
$rows = is_array($rows ?? null) ? $rows : [];
$totals = is_array($totals ?? null) ? $totals : ['sinhala' => 0, 'tamil' => 0, 'other' => 0, 'total' => 0];
$departmentName = trim((string) ($departmentName ?? ''));
?>
<style>
.lang-page { max-width: 980px; }
.lang-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .75rem; margin-bottom: 1rem; }
.lang-stat { background: #fff; border: 1px solid #e6eaf0; border-radius: 12px; padding: .85rem 1rem; }
.lang-stat span { display: block; color: #64748b; font-size: .78rem; }
.lang-stat strong { font-size: 1.45rem; color: #0f172a; }
@media (max-width: 768px) { .lang-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media print {
    .no-print { display: none !important; }
    .lang-page { max-width: none; }
}
</style>

<div class="container-fluid px-3 px-lg-4 py-3 lang-page">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
        <div>
            <h4 class="mb-1 fw-bold"><i class="fas fa-language me-2"></i>Sinhala and Tamil student count</h4>
            <div class="text-muted small">Following and Internship students. Select an academic year.</div>
        </div>
        <div class="d-flex gap-2 no-print">
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print();">
                <i class="fas fa-print me-1"></i>Print
            </button>
            <a class="btn btn-outline-secondary btn-sm" href="<?php echo APP_URL; ?>/students">
                <i class="fas fa-arrow-left me-1"></i>Students
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-3 no-print">
        <div class="card-body bg-light">
            <form method="get" action="<?php echo APP_URL; ?>/students/language-report" class="row g-3 align-items-end">
                <div class="col-md-5 col-lg-4">
                    <label for="academic_year" class="form-label fw-bold small">Academic year</label>
                    <select class="form-select" id="academic_year" name="academic_year" onchange="this.form.submit()">
                        <?php if ($academicYears === []): ?>
                            <option value="">No academic years</option>
                        <?php endif; ?>
                        <?php foreach ($academicYears as $year): ?>
                            <option value="<?php echo $e($year); ?>" <?php echo $selectedYear === (string) $year ? 'selected' : ''; ?>>
                                <?php echo $e($year); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">Show count</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($selectedYear !== ''): ?>
        <p class="mb-2">
            <strong>Academic year <?php echo $e($selectedYear); ?></strong>
            <?php if ($departmentName !== ''): ?>
                <span class="text-muted">· <?php echo $e($departmentName); ?></span>
            <?php endif; ?>
        </p>
        <div class="lang-stats">
            <div class="lang-stat"><span>Sinhala</span><strong><?php echo (int) $totals['sinhala']; ?></strong></div>
            <div class="lang-stat"><span>Tamil</span><strong><?php echo (int) $totals['tamil']; ?></strong></div>
            <div class="lang-stat"><span>Not recorded</span><strong><?php echo (int) $totals['other']; ?></strong></div>
            <div class="lang-stat"><span>Total</span><strong><?php echo (int) $totals['total']; ?></strong></div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Department</th>
                            <th>Course</th>
                            <th class="text-end">Sinhala</th>
                            <th class="text-end">Tamil</th>
                            <th class="text-end">Not recorded</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($rows === []): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No students for this academic year.</td></tr>
                        <?php else: ?>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <td><?php echo $e($row['department_name'] ?? ''); ?></td>
                                    <td><?php echo $e($row['course_name'] ?? ''); ?></td>
                                    <td class="text-end"><?php echo (int) ($row['sinhala_count'] ?? 0); ?></td>
                                    <td class="text-end"><?php echo (int) ($row['tamil_count'] ?? 0); ?></td>
                                    <td class="text-end"><?php echo (int) ($row['other_count'] ?? 0); ?></td>
                                    <td class="text-end fw-semibold"><?php echo (int) ($row['total_count'] ?? 0); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <?php if ($rows !== []): ?>
                    <tfoot>
                        <tr class="table-light fw-bold">
                            <td colspan="2">Total</td>
                            <td class="text-end"><?php echo (int) $totals['sinhala']; ?></td>
                            <td class="text-end"><?php echo (int) $totals['tamil']; ?></td>
                            <td class="text-end"><?php echo (int) $totals['other']; ?></td>
                            <td class="text-end"><?php echo (int) $totals['total']; ?></td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
