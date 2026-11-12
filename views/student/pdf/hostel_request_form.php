<?php
declare(strict_types=1);
/**
 * Hostel Request Form: A4, one page, simple label + underlined value layout.
 * Values are printed in capitals.
 *
 * @var string $logoSrc
 * @var array  $institute
 * @var string $year
 * @var array  $sample
 */
$e = static function ($v): string {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};
$u = static function ($v): string {
    $t = trim((string) $v);
    if ($t === '') {
        return '';
    }
    return function_exists('mb_strtoupper') ? mb_strtoupper($t, 'UTF-8') : strtoupper($t);
};
$instName = (string) ($institute['name'] ?? 'Sri Lanka German Training Institute');
$instAddr = (string) ($institute['address'] ?? '');
$instPhone = (string) ($institute['phone'] ?? '');
$logoSrc = (string) ($logoSrc ?? '');
$year = (string) ($year ?? date('Y'));
$s = is_array($sample ?? null) ? $sample : [];
$academic = trim((string) ($s['academic_year'] ?? ''));
if ($academic === '') {
    $academic = $year . ' / ' . ((int) $year + 1);
}

$chk = static function (bool $on): string {
    return '<span class="chk' . ($on ? ' on' : '') . '">' . ($on ? '&#10003;' : '') . '</span>';
};
$val = static function ($v) use ($e): string {
    $t = trim((string) $v);
    return $t === '' ? '&nbsp;' : $e($t);
};
$blockRoom = trim((string) ($s['block_name'] ?? '')
    . ((($s['block_name'] ?? '') !== '' && ($s['room_no'] ?? '') !== '') ? ' / ' : '')
    . (string) ($s['room_no'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Hostel Request Form</title>
<?php include __DIR__ . '/_request_form_style.php'; ?>
</head>
<body>

<table class="lh">
    <tr>
        <td class="c-logo"><?php if ($logoSrc !== ''): ?><img src="<?php echo $e($logoSrc); ?>" alt=""><?php endif; ?></td>
        <td class="c-mid">
            <div class="inst-name"><?php echo $e($instName); ?></div>
            <div class="inst-meta">
                <?php echo $e($instAddr); ?>
                <?php if ($instPhone !== ''): ?><br>Tel: <?php echo $e($instPhone); ?><?php endif; ?>
            </div>
        </td>
        <td class="c-side"></td>
    </tr>
</table>
<hr class="rule">
<h1 class="title">Hostel Request Form</h1>

<table class="f"><tr class="sp"><td class="l"></td><td class="v0"></td><td class="gap"></td><td class="l"></td><td class="v0"></td></tr>
    <tr>
        <td class="l">Student ID</td><td class="v"><?php echo $val($u($s['student_id'] ?? '')); ?></td>
        <td class="gap"></td>
        <td class="l">Date</td><td class="v"><?php echo $val($s['date'] ?? ''); ?></td>
    </tr>
</table>

<div class="sec">1. Student details</div>
<table class="f"><tr class="sp"><td class="l"></td><td class="v0"></td><td class="gap"></td><td class="l"></td><td class="v0"></td></tr>
    <tr><td class="l">Full name</td><td class="v" colspan="4"><?php echo $val($u($s['full_name'] ?? '')); ?></td></tr>
    <tr>
        <td class="l">NIC number</td><td class="v"><?php echo $val($u($s['nic'] ?? '')); ?></td>
        <td class="gap"></td>
        <td class="l">Phone</td><td class="v"><?php echo $val($s['phone'] ?? ''); ?></td>
    </tr>
    <tr>
        <td class="l">Department</td><td class="v"><?php echo $val($u($s['department'] ?? '')); ?></td>
        <td class="gap"></td>
        <td class="l">Course</td><td class="v"><?php echo $val($u($s['course'] ?? '')); ?></td>
    </tr>
    <tr>
        <td class="l">Academic year</td><td class="v"><?php echo $val($u($academic)); ?></td>
        <td class="gap"></td>
        <td class="l">Gender</td>
        <td style="vertical-align:bottom; padding-bottom:0.7mm;">
            <span class="opt"><?php echo $chk(($s['gender'] ?? '') === 'Male'); ?>Male</span>
            <span class="opt"><?php echo $chk(($s['gender'] ?? '') === 'Female'); ?>Female</span>
        </td>
    </tr>
    <tr><td class="l top tall">Permanent address</td><td class="v tall" colspan="4" style="vertical-align:top; padding-top:1.6mm;"><?php echo $val($u($s['address'] ?? '')); ?></td></tr>
</table>

<div class="sec">2. Hostel request</div>
<table class="f"><tr class="sp"><td class="l"></td><td class="v0"></td><td class="gap"></td><td class="l"></td><td class="v0"></td></tr>
    <tr>
        <td class="l">Request type</td>
        <td colspan="4" style="vertical-align:bottom; padding-bottom:0.7mm;">
            <span class="opt"><?php echo $chk(!empty($s['request_new'])); ?>New admission</span>
            <span class="opt"><?php echo $chk(!empty($s['request_renewal'])); ?>Renewal</span>
            <span class="opt"><?php echo $chk(!empty($s['request_change'])); ?>Change of room</span>
        </td>
    </tr>
    <tr><td class="l">Reason for request</td><td class="v" colspan="4"><?php echo $val(''); ?></td></tr>
    <tr><td class="l">&nbsp;</td><td class="v" colspan="4">&nbsp;</td></tr>
</table>

<div class="sec">3. Parent / guardian</div>
<table class="f"><tr class="sp"><td class="l"></td><td class="v0"></td><td class="gap"></td><td class="l"></td><td class="v0"></td></tr>
    <tr>
        <td class="l">Name</td><td class="v"><?php echo $val($u($s['guardian_name'] ?? '')); ?></td>
        <td class="gap"></td>
        <td class="l">Contact number</td><td class="v"><?php echo $val($s['guardian_phone'] ?? ''); ?></td>
    </tr>
</table>

<div class="sec">4. Declaration</div>
<p class="decl">
    I request hostel accommodation at the <?php echo $e($instName); ?> for the academic year stated above.
    I declare that the particulars given in this form are true and correct. I agree to follow the hostel rules
    and the institute code of conduct, and to vacate the hostel if required by the administration.
</p>

<table class="sig">
    <tr>
        <td class="box">
            <div class="sig-line"></div>
            <div class="sig-lab">Signature of student</div>
        </td>
        <td class="gap"></td>
        <td class="box">
            <div class="sig-line"></div>
            <div class="sig-lab">Signature of parent / guardian</div>
        </td>
    </tr>
</table>

<div class="office">
    <div class="oh">For office use only</div>
    <table class="room">
        <tr>
            <td class="rl">Hostel</td><td class="rv"></td>
            <td class="rl">Block</td><td class="rv"></td>
            <td class="rl">Room no.</td><td class="rv"></td>
            <td class="rl">Bed no.</td><td class="rv"></td>
        </tr>
    </table>
    <table>
        <tr>
            <td>
                <div class="ot">Warden</div>
                <div class="oo"><?php echo $chk(false); ?>Recommended</div>
                <div class="oo"><?php echo $chk(false); ?>Not recommended</div>
                <div class="ol"></div><div class="os">Signature &amp; date</div>
            </td>
            <td>
                <div class="ot">Head of department</div>
                <div class="oo"><?php echo $chk(false); ?>Approved</div>
                <div class="oo"><?php echo $chk(false); ?>Rejected</div>
            </td>
            <td>
                <div class="ot">SAO / Registrar</div>
                <div class="oo">&nbsp;</div>
                <div class="oo">&nbsp;</div>
                <div class="ol"></div><div class="os">Signature &amp; date</div>
            </td>
        </tr>
    </table>
</div>
</body>
</html>