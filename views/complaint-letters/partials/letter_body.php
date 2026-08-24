<?php
/**
 * Shared complaint letter body (screen preview + print + PDF).
 */
if (!class_exists('ComplaintLetterPdfHelper', false)) {
    require_once BASE_PATH . '/helpers/ComplaintLetterPdfHelper.php';
}

$studentName = trim((string) ($student['student_name'] ?? ''));
$studentId = trim((string) ($student['student_id'] ?? ''));
$letterRef = trim((string) ($complaint['reference_no'] ?? ''));
if ($studentId !== '') {
    $letterRef = $letterRef !== '' ? ($letterRef . ' / ' . $studentId) : $studentId;
}

$postFrom = ComplaintLetterPdfHelper::institutePostFrom();
$logoSrc = trim((string) ($logoSrc ?? ComplaintLetterPdfHelper::logoDataUri()));
$postalHeaderPath = BASE_PATH . '/views/complaint-letters/partials/postal_header.php';
?>
<div class="cl-a4-inner">
    <?php include $postalHeaderPath; ?>

    <div class="cl-letterhead">
        <?php if ($logoSrc !== ''): ?>
        <img src="<?php echo $e($logoSrc); ?>" alt="" class="cl-letterhead-logo">
        <?php endif; ?>
        <div class="cl-letterhead-name"><?php echo $e($postFrom['name']); ?></div>
        <div class="cl-letterhead-addr"><?php echo $e($postFrom['address']); ?></div>
    </div>
    <hr class="cl-letterhead-rule">

    <table class="cl-meta" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td class="cl-meta-ref"><strong>Reference:</strong> <?php echo $e($letterRef); ?></td>
            <td class="cl-meta-date"><strong>Date:</strong> <?php echo $e($fmtDate($complaint['letter_date'] ?? null)); ?></td>
        </tr>
    </table>

    <div class="cl-subject"><strong>Subject:</strong> <?php echo $e($complaint['subject'] ?? ''); ?></div>

    <p class="cl-salutation">Dear Parent / Guardian,</p>

    <table class="cl-particulars" cellspacing="0" cellpadding="0">
        <tr>
            <th>Student ID</th>
            <td class="cl-mono"><?php echo $e($studentId !== '' ? $studentId : '—'); ?></td>
            <th>Name</th>
            <td><?php echo $e($studentName !== '' ? $studentName : '—'); ?></td>
        </tr>
        <tr>
            <th>Department</th>
            <td colspan="3"><?php echo $e($complaint['department_name'] ?? $complaint['department_id'] ?? '—'); ?></td>
        </tr>
        <tr>
            <th>Course</th>
            <td><?php echo $e($student['course_name'] ?? $complaint['course_name'] ?? '—'); ?></td>
            <th>Academic Year</th>
            <td><?php echo $e($complaint['academic_year'] ?? '—'); ?></td>
        </tr>
    </table>

    <div class="cl-body"><?php echo ComplaintLetterPdfHelper::formatLetterContent($complaint['complaint_body'] ?? ''); ?></div>
</div>
