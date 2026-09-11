<?php
/**
 * DL long envelope — heading with left logo, From | To columns (grayscale).
 *
 * @var array<string, mixed> $schedule
 * @var array<string, mixed> $entry
 * @var array{name: string, address: string, city_line: string, phone?: string} $mailing
 * @var bool $isInterview
 * @var string $logo_src
 */
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');

$postFrom = ApplicationAdmissionPdfHelper::institutePostFrom();
$mailName = trim((string) ($mailing['name'] ?? $entry['student_full_name'] ?? ''));
$mailAddress = trim((string) ($mailing['address'] ?? $entry['student_address'] ?? ''));
$mailCity = trim((string) ($mailing['city_line'] ?? ''));
$isInterview = !empty($isInterview);
$examYear = (string) ($schedule['schedule_date'] ?? '');
$examYear = preg_match('/^\d{4}/', $examYear, $ym) ? $ym[0] : date('Y');
$bannerTitle = $isInterview
    ? 'INVITATION FOR THE SELECTION INTERVIEW – ' . $examYear . ' INTAKE'
    : 'SELECTION EXAMINATION ' . $examYear . ' — ADMISSION CARD';
$fromName = 'Sri Lanka – German Training Institute';
$fromPlace = 'Ariviyal Nagar, Kilinochchi';
$fromAddr = trim((string) ($postFrom['address'] ?? $fromPlace));
$logoSrc = trim((string) ($logo_src ?? ''));
?>
<div class="env-page">
    <table width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td class="env-left-space" width="10">&nbsp;</td>
            <td valign="top">
    <div class="env-top-space">&nbsp;</div>
    <table class="env-head" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td class="env-logo" width="16%" valign="middle">
                <?php if ($logoSrc !== ''): ?>
                <img class="env-logo-img" src="<?php echo $e($logoSrc); ?>" width="42" alt="SLGTI" />
                <?php else: ?>
                &nbsp;
                <?php endif; ?>
            </td>
            <td class="env-head-text" width="68%" valign="middle">
                <div class="env-inst"><?php echo $e($fromName); ?></div>
                <div class="env-place"><?php echo $e($fromPlace); ?></div>
                <div class="env-banner"><?php echo $e($bannerTitle); ?></div>
            </td>
            <td class="env-head-pad" width="16%">&nbsp;</td>
        </tr>
    </table>

    <table class="env-cols" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td class="env-from" width="48%" valign="top">
                <div class="env-label">From</div>
                <div class="env-name"><?php echo $e($fromName); ?></div>
                <div class="env-addr"><?php echo $e($fromAddr); ?></div>
                <div class="env-addr">Sri Lanka.</div>
            </td>
            <td class="env-gap" width="4%">&nbsp;</td>
            <td class="env-to" width="48%" valign="top">
                <div class="env-label">To</div>
                <div class="env-name"><?php echo $mailName !== '' ? $e($mailName) : '—'; ?></div>
                <?php if ($mailAddress !== ''): ?>
                <div class="env-addr"><?php echo nl2br($e($mailAddress)); ?></div>
                <?php endif; ?>
                <?php if ($mailCity !== ''): ?>
                <div class="env-addr"><?php echo $e($mailCity); ?></div>
                <?php endif; ?>
                <div class="env-addr">Sri Lanka.</div>
            </td>
        </tr>
    </table>
            </td>
        </tr>
    </table>
</div>
