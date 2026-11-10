<?php
declare(strict_types=1);
/**
 * Bus Season Request Form: A4, one page, simple label + underlined value layout.
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
$seasonYear = trim((string) ($s['season_year'] ?? ''));
if ($seasonYear === '') {
    $seasonYear = $year;
}

$chk = static function (bool $on): string {
    return '<span class="chk' . ($on ? ' on' : '') . '">' . ($on ? '&#10003;' : '') . '</span>';
};
$val = static function ($v) use ($e): string {
    $t = trim((string) $v);
    return $t === '' ? '&nbsp;' : $e($t);
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Bus Season Request Form</title>
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
<h1 class="title">Bus Season Request Form</h1>

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
        <td class="l">Academic year</td><td class="v"><?php echo $val($u($s['academic_year'] ?? '')); ?></td>
        <td class="gap"></td>
        <td colspan="2"></td>
    </tr>
    <tr><td class="l top tall">Permanent address</td><td class="v tall" colspan="4" style="vertical-align:top; padding-top:1.6mm;"><?php echo $val($u($s['address'] ?? '')); ?></td></tr>
</table>

<div class="sec">2. Season and route</div>
<table class="f"><tr class="sp"><td class="l"></td><td class="v0"></td><td class="gap"></td><td class="l"></td><td class="v0"></td></tr>
    <tr>
        <td class="l">Season month</td><td class="v"><?php echo $val($u($s['season_month'] ?? '')); ?></td>
        <td class="gap"></td>
        <td class="l">Distance (km)</td><td class="v"><?php echo $val($s['distance_km'] ?? ''); ?></td>
    </tr>
    <tr>
        <td class="l">Route from</td><td class="v"><?php echo $val($u($s['route_from'] ?? '')); ?></td>
        <td class="gap"></td>
        <td class="l">Route to</td><td class="v"><?php echo $val($u($s['route_to'] ?? '')); ?></td>
    </tr>
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
    I request a bus season ticket for travel between my residence and the <?php echo $e($instName); ?>
    for the month and route stated above. I declare that the particulars given in this form are true and correct.
    I will use the season ticket only for the approved route and follow the institute transport rules.
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
    <table>
        <tr>
            <td>
                <div class="ot">Head of department</div>
                <div class="oo"><?php echo $chk(false); ?>Approved</div>
                <div class="oo"><?php echo $chk(false); ?>Rejected</div>
                <div class="ol"></div><div class="os">Signature &amp; date</div>
            </td>
            <td>
                <div class="ot">SAO / Registrar</div>
                <div class="oo">Receipt no.:</div>
                <div class="oo">&nbsp;</div>
                <div class="ol"></div><div class="os">Signature &amp; date</div>
            </td>
        </tr>
    </table>
</div>
</body>
</html>