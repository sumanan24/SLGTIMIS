<?php
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
$fmt = static function ($n): string {
    if ($n === null || $n === '') {
        return '—';
    }
    if (is_numeric($n) && abs((float) $n - round((float) $n)) < 0.00001) {
        return (string) (int) round((float) $n);
    }

    return is_numeric($n) ? rtrim(rtrim(sprintf('%.2f', (float) $n), '0'), '.') : (string) $n;
};
$nic = (string) ($nic ?? '');
$level = (string) ($level ?? '');
$results = is_array($results ?? null) ? $results : [];
$searched = !empty($searched);
$aaNavActive = 'nic-result';
$formAction = rtrim(APP_URL, '/') . '/application-admission/nic-result';
$statusMeta = [
    'selected' => ['label' => 'Selected', 'class' => 'aa-badge-ok'],
    'failed' => ['label' => 'Not selected', 'class' => 'aa-badge-fail'],
    'applied' => ['label' => 'Applied — no exam result yet', 'class' => 'aa-badge-wait'],
];
?>
<style>
.aa-page { width: 100%; max-width: none; margin: 0; padding-bottom: 1.5rem; }
.aa-page-header { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 0.75rem 1rem; margin-bottom: 1rem; }
.aa-page-header h1 { font-size: 1.25rem; font-weight: 600; margin: 0 0 0.25rem; }
.aa-page-header .aa-sub { margin: 0; font-size: 0.8125rem; color: #6c757d; }
.aa-filters { display: flex; flex-wrap: wrap; align-items: end; gap: 0.75rem 1rem; margin-bottom: 1rem; padding: 0.75rem 1rem; background: #fff; border: 1px solid #dee2e6; border-radius: 0.5rem; }
.aa-filters label { display: block; font-size: 0.75rem; font-weight: 600; color: #495057; margin-bottom: 0.25rem; }
.aa-filters .aa-field-nic { min-width: 16rem; flex: 1 1 16rem; }
.aa-filters .aa-field-nic input { text-transform: uppercase; letter-spacing: 0.04em; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
.aa-card { background: #fff; border: 1px solid #dee2e6; border-radius: 0.5rem; margin-bottom: 1rem; overflow: hidden; }
.aa-card-head { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 0.5rem; padding: 0.75rem 1rem; background: #f8f9fa; border-bottom: 1px solid #dee2e6; }
.aa-card-head h2 { font-size: 1.05rem; font-weight: 700; margin: 0; }
.aa-meta { font-size: 0.8125rem; color: #6c757d; }
.aa-stats { display: flex; flex-wrap: wrap; gap: 0.5rem; padding: 0.85rem 1rem 0; }
.aa-stat { background: #fff; border: 1px solid #dee2e6; border-radius: 0.5rem; padding: 0.55rem 0.85rem; min-width: 7.5rem; }
.aa-stat strong { display: block; font-size: 1.15rem; line-height: 1.2; }
.aa-stat span { font-size: 0.75rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.04em; font-weight: 600; }
.aa-stat.aa-stat-ok { border-color: #bbf7d0; background: #f0fdf4; }
.aa-stat.aa-stat-ok strong { color: #166534; }
.aa-stat.aa-stat-fail { border-color: #fecaca; background: #fef2f2; }
.aa-stat.aa-stat-fail strong { color: #991b1b; }
.aa-stat.aa-stat-wait { border-color: #fde68a; background: #fffbeb; }
.aa-stat.aa-stat-wait strong { color: #92400e; }
.aa-badge { display: inline-flex; align-items: center; padding: 0.2rem 0.55rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700; }
.aa-badge-ok { background: #dcfce7; color: #166534; }
.aa-badge-fail { background: #fee2e2; color: #991b1b; }
.aa-badge-wait { background: #fef3c7; color: #92400e; }
.aa-dl { display: grid; grid-template-columns: 10rem 1fr; gap: 0.35rem 1rem; margin: 0; padding: 0.85rem 1rem; font-size: 0.9rem; }
.aa-dl dt { color: #6c757d; font-weight: 600; }
.aa-dl dd { margin: 0; }
.aa-table { width: 100%; margin: 0; }
.aa-table thead th { padding: 0.5rem 0.75rem; font-size: 0.6875rem; font-weight: 600; letter-spacing: 0.04em; text-transform: uppercase; color: #495057; background: #fff; border-bottom: 2px solid #dee2e6; }
.aa-table tbody td { padding: 0.55rem 0.75rem; font-size: 0.875rem; border-bottom: 1px solid #eef1f4; vertical-align: middle; }
.aa-table tbody tr:last-child td { border-bottom: none; }
.aa-choice-sel { background: #dcfce7; font-weight: 600; }
.aa-empty { text-align: center; color: #6c757d; padding: 2rem 1rem !important; }
.aa-selected-banner { margin: 0 1rem 1rem; padding: 0.7rem 0.85rem; border-radius: 0.5rem; background: #f0fdf4; border: 1px solid #bbf7d0; }
.aa-selected-banner strong { display: block; color: #166534; }
.aa-fail-banner { margin: 0 1rem 1rem; padding: 0.7rem 0.85rem; border-radius: 0.5rem; background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
</style>

<div class="container-fluid px-3 px-md-4 aa-page">
    <div class="aa-page-header">
        <div>
            <h1>NIC result</h1>
            <p class="aa-sub">Enter an NIC to check exam marks, cutoff, applied courses, and the selected course.</p>
        </div>
    </div>

    <?php require BASE_PATH . '/views/application_admission/_module_nav.php'; ?>

    <form class="aa-filters" method="get" action="<?php echo $e($formAction); ?>">
        <div class="aa-field-nic">
            <label for="aa-nic">NIC number</label>
            <input id="aa-nic" class="form-control form-control-sm" type="text" name="nic" value="<?php echo $e($nic); ?>" placeholder="9 digits + V/X or 12 digits" maxlength="20" autocomplete="off" required>
        </div>
        <div>
            <label for="aa-level">NVQ level</label>
            <select id="aa-level" class="form-select form-select-sm" name="level">
                <option value="" <?php echo $level === '' ? 'selected' : ''; ?>>All levels</option>
                <option value="04" <?php echo $level === '04' ? 'selected' : ''; ?>>NVQ 04</option>
                <option value="05" <?php echo $level === '05' ? 'selected' : ''; ?>>NVQ 05</option>
            </select>
        </div>
        <div>
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fas fa-search me-1"></i> Check result
            </button>
            <?php if ($searched): ?>
            <a class="btn btn-outline-secondary btn-sm" href="<?php echo $e($formAction); ?>">Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <?php if (!$searched): ?>
        <div class="aa-card">
            <p class="aa-empty mb-0">Enter a NIC number and click Check result.</p>
        </div>
    <?php elseif ($results === []): ?>
        <div class="aa-card">
            <p class="aa-empty mb-0">No applicant found for NIC <strong><?php echo $e($nic); ?></strong><?php echo $level !== '' ? ' at NVQ ' . $e($level) : ''; ?>.</p>
        </div>
    <?php else: ?>
        <?php foreach ($results as $row):
            $status = (string) ($row['status'] ?? '');
            $meta = $statusMeta[$status] ?? ['label' => $status, 'class' => 'aa-badge-wait'];
            $statClass = $status === 'selected' ? 'aa-stat-ok' : ($status === 'failed' ? 'aa-stat-fail' : 'aa-stat-wait');
            $resultLabel = $meta['label'];
            $applied = trim((string) ($row['applied_course'] ?? ''));
            $second = trim((string) ($row['second_course'] ?? ''));
            $third = trim((string) ($row['third_course'] ?? ''));
            $selectedCourse = trim((string) ($row['selected_course'] ?? ''));
            $choice = (int) ($row['choice'] ?? 0);
            $choices = [
                1 => ['label' => '1st choice', 'name' => $applied],
                2 => ['label' => '2nd choice', 'name' => $second],
                3 => ['label' => '3rd choice', 'name' => $third],
            ];
        ?>
        <div class="aa-card">
            <div class="aa-card-head">
                <div>
                    <h2><?php echo $e((string) ($row['name'] ?? '')); ?></h2>
                    <p class="aa-meta mb-0">
                        NIC <?php echo $e((string) ($row['nic'] ?? $nic)); ?>
                        · NVQ <?php echo $e((string) ($row['level'] ?? '')); ?>
                        <?php if (trim((string) ($row['roll_number'] ?? '')) !== ''): ?>
                            · Roll <?php echo $e((string) $row['roll_number']); ?>
                        <?php endif; ?>
                    </p>
                </div>
                <span class="aa-badge <?php echo $e($meta['class']); ?>"><?php echo $e($resultLabel); ?></span>
            </div>
            <div class="aa-stats">
                <div class="aa-stat">
                    <strong><?php echo $e($fmt($row['exam_marks'] ?? '')); ?></strong>
                    <span>Exam marks</span>
                </div>
                <div class="aa-stat">
                    <strong><?php echo $e($fmt($row['cutoff_applied'] ?? null)); ?></strong>
                    <span>Cutoff</span>
                </div>
                <div class="aa-stat <?php echo $e($statClass); ?>">
                    <strong><?php echo $e($status === 'selected' ? 'Selected' : ($status === 'failed' ? 'Failed' : 'Pending')); ?></strong>
                    <span>Cutoff result</span>
                </div>
            </div>
            <dl class="aa-dl">
                <dt>Phone</dt>
                <dd><?php echo $e(trim((string) ($row['phone'] ?? '')) !== '' ? (string) $row['phone'] : '—'); ?></dd>
                <dt>Medium / region</dt>
                <dd><?php
                    $bits = array_filter([
                        trim((string) ($row['medium'] ?? '')),
                        trim((string) ($row['region'] ?? '')),
                    ]);
                    echo $e($bits !== [] ? implode(' · ', $bits) : '—');
                ?></dd>
                <dt>Department</dt>
                <dd><?php echo $e(trim((string) ($row['department_name'] ?? '')) !== '' ? (string) $row['department_name'] : '—'); ?></dd>
            </dl>

            <table class="aa-table">
                <thead>
                    <tr>
                        <th>Applied course</th>
                        <th>Course</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($choices as $n => $choiceRow):
                        $isSel = $status === 'selected' && $choice === $n && $selectedCourse !== '';
                    ?>
                    <tr class="<?php echo $isSel ? 'aa-choice-sel' : ''; ?>">
                        <td><?php echo $e($choiceRow['label']); ?><?php echo $isSel ? ' · selected' : ''; ?></td>
                        <td><?php echo $e($choiceRow['name'] !== '' ? $choiceRow['name'] : '—'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($status === 'selected' && $selectedCourse !== ''): ?>
                <div class="aa-selected-banner">
                    <strong>Selected course</strong>
                    <?php echo $e($selectedCourse); ?>
                    <?php if (trim((string) ($row['choice_label'] ?? '')) !== ''): ?>
                        <span class="aa-meta"> (<?php echo $e((string) $row['choice_label']); ?>)</span>
                    <?php endif; ?>
                </div>
            <?php elseif ($status === 'failed'): ?>
                <div class="aa-fail-banner">
                    <strong>Not selected.</strong>
                    <?php echo $e(trim((string) ($row['fail_reason'] ?? '')) !== '' ? (string) $row['fail_reason'] : 'Did not meet cutoff or second-option rules.'); ?>
                </div>
            <?php else: ?>
                <p class="aa-empty mb-0">Application found. Exam marks and cutoff selection have not been recorded yet.</p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
