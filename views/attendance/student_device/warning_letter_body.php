<?php
declare(strict_types=1);
/** @var array $student */
/** @var array $meta */
/** @var string $logoSrc */
/** @var array $institute */

$e = static function ($v): string {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};

$sid = (string) ($student['student_id'] ?? '');
$name = (string) ($student['student_name'] ?? '');
$dept = (string) ($student['department_name'] ?? '');
$group = (string) ($student['group_name'] ?? '—');
$pct = number_format((float) ($student['attendance_pct'] ?? 0), 1);
$leaveDays = (int) ($student['leave_days'] ?? 0);
$dates = $student['leave_dates'] ?? [];
if (!is_array($dates)) {
    $dates = [];
}
$monthLabel = (string) ($meta['month_label'] ?? '');
$letterDate = (string) ($meta['letter_date'] ?? date('d F Y'));
$ref = (string) ($meta['reference'] ?? ('SAO/ATT/' . date('Y') . '/' . $sid));
$inCutoff = (string) ($meta['in_cutoff'] ?? '08:40');
$outCutoff = (string) ($meta['out_cutoff'] ?? '16:00');
$threshold = (int) ($meta['consecutive_threshold'] ?? 3);
$instName = (string) ($institute['name'] ?? 'Sri Lanka German Training Institute');
$instAddr = (string) ($institute['address'] ?? '');
?>
<div class="letter-page">
    <div class="cl-a4-inner">
        <div class="cl-letterhead">
            <?php if ($logoSrc !== ''): ?>
                <img class="cl-letterhead-logo" src="<?php echo $e($logoSrc); ?>" alt="">
            <?php endif; ?>
            <div class="cl-letterhead-name"><?php echo $e($instName); ?></div>
            <?php if ($instAddr !== ''): ?>
                <div class="cl-letterhead-addr"><?php echo $e($instAddr); ?></div>
            <?php endif; ?>
            <hr class="cl-letterhead-rule">
        </div>

        <table class="cl-meta">
            <tr>
                <td class="cl-meta-ref">Ref: <?php echo $e($ref); ?></td>
                <td class="cl-meta-date">Date: <?php echo $e($letterDate); ?></td>
            </tr>
        </table>

        <div class="cl-subject">Subject: Warning Letter — Unauthorised Consecutive Absence (<?php echo $e($monthLabel); ?>)</div>

        <div class="cl-salutation">Dear Parent / Guardian / Student,</div>

        <table class="cl-particulars">
            <tr>
                <th>Student ID</th>
                <td class="cl-mono"><?php echo $e($sid); ?></td>
                <th>Name</th>
                <td><?php echo $e($name); ?></td>
            </tr>
            <tr>
                <th>Department</th>
                <td><?php echo $e($dept !== '' ? $dept : '—'); ?></td>
                <th>Group</th>
                <td><?php echo $e($group); ?></td>
            </tr>
            <tr>
                <th>Attendance %</th>
                <td><?php echo $e($pct); ?>%</td>
                <th>Consecutive days</th>
                <td><?php echo $leaveDays; ?></td>
            </tr>
        </table>

        <div class="cl-body">
            <p>
                This letter is issued by the Students Affairs Office regarding the attendance of the above student
                for <strong><?php echo $e($monthLabel); ?></strong>. Institute fingerprint attendance records show
                <strong><?php echo $leaveDays; ?> consecutive working day(s)</strong> of unauthorised absence
                (threshold: <?php echo $threshold; ?> days).
            </p>
            <p>Present attendance requires In on or before <strong><?php echo $e($inCutoff); ?></strong>
                and Out on or after <strong><?php echo $e($outCutoff); ?></strong>. Weekends, public holidays,
                and approved special leave are excluded from this calculation.</p>
            <?php if ($dates !== []): ?>
                <p><strong>Dates of consecutive unauthorised absence:</strong></p>
                <ul class="awl-dates">
                    <?php foreach ($dates as $d): ?>
                        <li><?php echo $e($d); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <p>
                Repeated absence without approved leave is a serious matter. The student is hereby warned to
                maintain regular attendance and to report to the Students Affairs Office / Head of Department
                immediately if there are genuine difficulties.
            </p>
            <p>
                Failure to improve may lead to further disciplinary action in accordance with institute regulations.
            </p>
            <p>Your cooperation is requested.</p>
        </div>

        <div class="awl-sign">
            <div>Yours faithfully,</div>
            <div class="awl-sign-line"></div>
            <div class="awl-sign-role"><strong>Students Affairs Officer</strong><br><?php echo $e($instName); ?></div>
        </div>
    </div>
</div>
