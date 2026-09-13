<?php
if (!isset($e) || !is_callable($e)) {
    $e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
}
if (!isset($fmt) || !is_callable($fmt)) {
    $fmt = static function ($n): string {
        if ($n === null || $n === '') {
            return '—';
        }
        if (is_numeric($n) && abs((float) $n - round((float) $n)) < 0.00001) {
            return (string) (int) round((float) $n);
        }

        return is_numeric($n) ? rtrim(rtrim(sprintf('%.2f', (float) $n), '0'), '.') : (string) $n;
    };
}
$level = trim((string) ($level ?? ''));
$courseId = trim((string) ($course_id ?? $courseId ?? ''));
$filterQuery = (string) ($filter_query ?? $filterQuery ?? '');
$overview = is_array($overview ?? null) ? $overview : [];
$ovTotals = is_array($overview['totals'] ?? null) ? $overview['totals'] : [];
$ovCourses = is_array($overview['courses'] ?? null) ? $overview['courses'] : [];
$hasFilter = !empty($has_filter);
$reportBase = rtrim(APP_URL, '/') . '/application-admission/report';
$dashScope = $hasFilter
    ? 'Counts for the applied filter. Clear the filter to see all NVQ 04 and 05 students.'
    : 'All NVQ 04 and 05 students. Apply a level, department, or course filter only when you need a subset.';
?>

<div class="aa-part aa-part-dashboard" id="aa-exam-dashboard">
    <div class="aa-part-head">
        <div>
            <h2>Selection summary</h2>
            <p><?php echo $e($dashScope); ?></p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?php echo APP_URL; ?>/application-admission/export-report?<?php echo $e($filterQuery); ?>" class="btn btn-sm btn-outline-success">
                <i class="fas fa-file-excel me-1"></i> Download Excel
            </a>
            <a href="<?php echo APP_URL; ?>/application-admission/pdf-report?<?php echo $e($filterQuery); ?>" class="btn btn-sm btn-outline-dark">
                <i class="fas fa-file-pdf me-1"></i> PDF
            </a>
        </div>
    </div>

    <?php
        $fmtCutFull = static function (array $card) use ($fmt): string {
            $parts = is_array($card['cutoff_parts'] ?? null) ? $card['cutoff_parts'] : [];
            if ($parts === []) {
                if (empty($card['has_cutoff'])) {
                    return 'Not set';
                }

                return 'Northern ' . $fmt($card['cutoff_northern'] ?? null)
                    . ' / Other ' . $fmt($card['cutoff_other'] ?? null);
            }
            $bits = [];
            foreach ($parts as $p) {
                $pair = 'Northern ' . $fmt($p['cutoff_northern'] ?? null)
                    . ' / Other ' . $fmt($p['cutoff_other'] ?? null);
                $label = trim((string) ($p['label'] ?? ''));
                $bits[] = ($label !== '' && strcasecmp($label, 'All languages') !== 0)
                    ? ($label . ': ' . $pair)
                    : $pair;
            }

            return implode(' · ', $bits);
        };
    ?>
    <div class="table-responsive aa-sum-wrap">
        <table class="aa-sum-table">
            <thead>
                <tr>
                    <th class="aa-col-no">No</th>
                    <th>NVQ</th>
                    <th>Department</th>
                    <th>Course</th>
                    <th>Medium</th>
                    <th class="aa-num" title="Unique students with a 1st preference for this course">Applied students</th>
                    <th class="aa-num" title="Unique students who sat the exam">Exam students</th>
                    <th class="aa-num" title="Unique students who met the 1st-choice cutoff">Met cutoff</th>
                    <th class="aa-num" title="Unique students with at least 30 marks who missed the 1st-choice cutoff">Below cutoff (not failed)</th>
                    <th class="aa-num" title="Unique students selected for this course (the interview list). Direct count, not 1st+2nd+3rd added.">Selected students</th>
                    <th class="aa-num" title="Unique students placed into this course as 1st preference">1st option</th>
                    <th class="aa-num" title="Unique students placed into this course as 2nd preference">2nd option</th>
                    <th class="aa-num" title="Unique students placed into this course as 3rd preference">3rd option</th>
                    <th class="aa-num" title="Unique students who applied here and were selected for a different course">Selected other course</th>
                    <th>Cutoff (Northern / Other)</th>
                    <th class="aa-num">Failed students (below 30)</th>
                    <th class="aa-num">Absent students</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($ovCourses === []): ?>
                <tr><td colspan="17" class="aa-empty">No course counts yet. Enter entrance exam marks and set cutoffs first.</td></tr>
            <?php else: ?>
                <?php $n = 0; foreach ($ovCourses as $c):
                    $n++;
                    $cid = trim((string) ($c['course_id'] ?? ''));
                    $cname = trim((string) ($c['course_name'] ?? ''));
                    $cDid = trim((string) ($c['department_id'] ?? ''));
                    $dname = trim((string) ($c['department_name'] ?? ''));
                    $medium = trim((string) ($c['medium_label'] ?? ''));
                    $rowLevel = trim((string) ($c['application_level'] ?? $level));
                    $rowActive = $cid !== '' && strcasecmp($courseId, $cid) === 0;
                    $qs = [];
                    if ($rowLevel !== '') {
                        $qs['level'] = $rowLevel;
                    }
                    if ($cDid !== '') {
                        $qs['department_id'] = $cDid;
                    }
                    $deptHref = $reportBase . ($qs !== [] ? ('?' . http_build_query($qs)) : '');
                    if ($cid !== '') {
                        $qs['course_id'] = $cid;
                    }
                    $courseHref = $reportBase . ($qs !== [] ? ('?' . http_build_query($qs)) : '');
                ?>
                <tr class="<?php echo $rowActive ? 'is-active' : ''; ?>">
                    <td class="aa-col-no"><?php echo (int) $n; ?></td>
                    <td><?php echo $e($rowLevel !== '' ? $rowLevel : '—'); ?></td>
                    <td>
                        <a class="aa-dept-link" href="<?php echo $e($deptHref); ?>"
                           data-aa-dept="<?php echo $e($cDid); ?>" data-aa-course=""><?php echo $e($dname !== '' ? $dname : '—'); ?></a>
                    </td>
                    <td>
                        <a class="aa-course-row" href="<?php echo $e($courseHref); ?>"
                           data-aa-dept="<?php echo $e($cDid); ?>" data-aa-course="<?php echo $e($cid); ?>" data-aa-level="<?php echo $e($rowLevel); ?>">
                            <?php echo $e($cname !== '' ? $cname : $cid); ?>
                        </a>
                    </td>
                    <td><?php echo $e($medium !== '' && strcasecmp($medium, 'All languages') !== 0 ? $medium : 'English'); ?></td>
                    <td class="aa-num"><?php echo (int) ($c['applied'] ?? 0); ?></td>
                    <td class="aa-num"><?php echo (int) ($c['sat'] ?? 0); ?></td>
                    <td class="aa-num"><?php echo (int) ($c['met_cutoff'] ?? 0); ?></td>
                    <td class="aa-num"><?php echo (int) ($c['below_cutoff'] ?? 0); ?></td>
                    <td class="aa-num"><?php echo (int) ($c['selected'] ?? 0); ?></td>
                    <td class="aa-num"><?php echo (int) ($c['selected_1st'] ?? 0); ?></td>
                    <td class="aa-num"><?php echo (int) ($c['selected_2nd'] ?? 0); ?></td>
                    <td class="aa-num"><?php echo (int) ($c['selected_3rd'] ?? 0); ?></td>
                    <td class="aa-num"><?php echo (int) ($c['selected_other'] ?? 0); ?></td>
                    <td class="aa-course-cut"><?php echo $e($fmtCutFull($c)); ?></td>
                    <td class="aa-num is-fail"><?php echo (int) ($c['failed'] ?? 0); ?></td>
                    <td class="aa-num is-fail"><?php echo (int) ($c['absent'] ?? 0); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
            <?php if ($ovCourses !== []): ?>
            <tfoot>
                <tr>
                    <th colspan="5">Total</th>
                    <th class="aa-num"><?php echo (int) ($ovTotals['applied'] ?? 0); ?></th>
                    <th class="aa-num"><?php echo (int) ($ovTotals['sat'] ?? 0); ?></th>
                    <th class="aa-num"><?php echo (int) ($ovTotals['met_cutoff'] ?? 0); ?></th>
                    <th class="aa-num"><?php echo (int) ($ovTotals['below_cutoff'] ?? 0); ?></th>
                    <th class="aa-num"><?php echo (int) ($ovTotals['selected'] ?? 0); ?></th>
                    <th class="aa-num"><?php echo (int) ($ovTotals['selected_1st'] ?? 0); ?></th>
                    <th class="aa-num"><?php echo (int) ($ovTotals['selected_2nd'] ?? 0); ?></th>
                    <th class="aa-num"><?php echo (int) ($ovTotals['selected_3rd'] ?? 0); ?></th>
                    <th class="aa-num"><?php echo (int) ($ovTotals['selected_other'] ?? 0); ?></th>
                    <th></th>
                    <th class="aa-num is-fail"><?php echo (int) ($ovTotals['failed'] ?? 0); ?></th>
                    <th class="aa-num is-fail"><?php echo (int) ($ovTotals['absent'] ?? 0); ?></th>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>
