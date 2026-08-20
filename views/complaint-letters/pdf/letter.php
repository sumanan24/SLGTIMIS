<?php
if (!class_exists('ComplaintLetterPdfHelper', false)) {
    require_once BASE_PATH . '/helpers/ComplaintLetterPdfHelper.php';
}
/**
 * @var array<string, mixed> $complaint
 * @var list<array<string, mixed>> $students
 * @var string $logoSrc
 */
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
$fmtDate = static function (?string $d): string {
    $ts = $d ? strtotime($d) : false;
    return $ts ? date('d F Y', $ts) : '—';
};
$singlePath = BASE_PATH . '/views/complaint-letters/pdf/letter_single.php';
if ($students === []) {
    $students = [['student_id' => '', 'student_name' => '', 'course_name' => '']];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        <?php echo ComplaintLetterPdfHelper::pdfPageStylesheet(); ?>
        <?php echo ComplaintLetterPdfHelper::complaintLetterStylesheet(); ?>
    </style>
</head>
<body>
<?php foreach ($students as $student): ?>
    <?php include $singlePath; ?>
<?php endforeach; ?>
</body>
</html>
