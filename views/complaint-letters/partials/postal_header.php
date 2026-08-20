<?php
/**
 * POST FROM / POST TO foldable postal header (complaint letters).
 *
 * @var array<string, mixed> $student
 * @var callable $e
 */
if (!class_exists('ComplaintLetterPdfHelper', false)) {
    require_once BASE_PATH . '/helpers/ComplaintLetterPdfHelper.php';
}
$postFrom = ComplaintLetterPdfHelper::institutePostFrom();
$studentId = trim((string) ($student['student_id'] ?? ''));
$studentName = trim((string) ($student['student_name'] ?? ''));
$mailAddress = trim((string) ($student['mail_address'] ?? ''));
$mailCity = trim((string) ($student['mail_city_line'] ?? ''));
?>
<table class="cl-postbox" width="100%" cellspacing="0" cellpadding="0">
    <tr>
        <td class="cl-from" width="40%">
            <div class="cl-post-label">POST FROM</div>
            <div class="cl-post-strong"><?php echo $e($postFrom['name']); ?></div>
            <div class="cl-post-text"><?php echo $e($postFrom['address']); ?></div>
            <?php if (trim((string) ($postFrom['phone'] ?? '')) !== ''): ?>
            <div class="cl-post-text"><strong>Phone:</strong> <?php echo $e($postFrom['phone']); ?></div>
            <?php endif; ?>
        </td>
        <td class="cl-to" width="60%">
            <div class="cl-post-label">POST TO</div>
            <?php if ($studentId !== ''): ?>
            <div class="cl-post-id"><?php echo $e($studentId); ?></div>
            <?php endif; ?>
            <div class="cl-post-strong"><?php echo $studentName !== '' ? $e($studentName) : '—'; ?></div>
            <?php if ($mailAddress !== ''): ?>
            <div class="cl-post-text"><?php echo nl2br($e($mailAddress)); ?></div>
            <?php endif; ?>
            <?php if ($mailCity !== ''): ?>
            <div class="cl-post-text"><?php echo $e($mailCity); ?></div>
            <?php endif; ?>
        </td>
    </tr>
</table>
<div class="cl-foldhint">Fold with <strong>Post to</strong> on the outside; Post from stays top-left when posted.</div>
<div class="cl-fold">— Fold here —</div>
