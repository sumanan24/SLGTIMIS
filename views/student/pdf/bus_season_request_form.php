<?php
declare(strict_types=1);
/**
 * Bus Season Request Form — A4, 1 page. Values in FULL CAPS.
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
    return function_exists('mb_strtoupper')
        ? mb_strtoupper($t, 'UTF-8')
        : strtoupper($t);
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
    return $on
        ? '<span class="chk on">&#10003;</span>'
        : '<span class="chk"></span>';
};
$show = static function ($v) use ($e, $u): string {
    $t = trim((string) $v);
    if ($t === '') {
        return '<span class="blank">&nbsp;</span>';
    }
    return $e($u($t));
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Bus Season Request Form</title>
<style>
@page { size: A4 portrait; margin: 10px; }
* { box-sizing: border-box; }
html, body { margin: 0; padding: 0; width: 100%; background: #fff; color-scheme: light; }
body {
    font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
    font-size: 8pt; color: #0f172a; line-height: 1.3; width: 100%;
    background: #fff;
}
.page { width: 100%; }
.lh { width: 100%; border-collapse: collapse; margin: 0 0 2mm 0; table-layout: fixed; }
.lh td { border: none; vertical-align: middle; padding: 0; }
.lh-logo { width: 18%; }
.lh-logo img { height: 10mm; width: auto; display: block; }
.lh-mid { width: 64%; text-align: center; padding: 0 3mm; }
.lh-name {
    font-size: 10.5pt; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.05em; line-height: 1.2; margin: 0 0 1mm 0; color: #0f172a;
}
.lh-meta { font-size: 7pt; color: #475569; line-height: 1.4; text-transform: uppercase; }
.lh-side { width: 18%; }
.rule {
    height: 0; border: none;
    border-top: 1.6pt solid #0f172a; border-bottom: 0.45pt solid #0f172a;
    margin: 0 0 1.6mm 0; padding: 0; width: 100%;
}
.doc-h { text-align: center; margin: 0 0 1.4mm 0; width: 100%; }
.doc-h h1 {
    margin: 0 0 0.6mm 0; font-size: 11pt; font-weight: 700;
    letter-spacing: 0.08em; text-transform: uppercase; color: #0f172a;
}
.doc-h .sub {
    margin: 0; font-size: 7.5pt; color: #334155; text-transform: uppercase;
    letter-spacing: 0.04em;
}
.grid { width: 100%; border-collapse: collapse; table-layout: fixed; margin: 0; }
.grid td {
    border: 0.55pt solid #94a3b8; padding: 1mm 2mm;
    vertical-align: middle; height: 5.4mm; font-size: 7.5pt;
}
.grid .lab {
    width: 22%; background: #f1f5f9; font-size: 6.5pt; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.04em; color: #334155;
}
.grid .val {
    width: 28%; color: #0f172a; font-weight: 700; font-size: 7.5pt;
    text-transform: uppercase; letter-spacing: 0.02em; background: #fff;
}
.grid .lab-w { width: 22%; }
.grid .val-w { width: 78%; }
.grid .tall { height: 8mm; vertical-align: top; padding-top: 1.2mm; }
.sec {
    margin: 1.4mm 0 0 0; width: 100%; background: #1e3a5f; color: #fff;
    font-size: 7pt; font-weight: 700; letter-spacing: 0.06em;
    text-transform: uppercase; padding: 1mm 2.2mm;
}
.hint {
    margin: 0; padding: 1.2mm 2.2mm; font-size: 6.5pt; color: #64748b;
    text-transform: uppercase; letter-spacing: 0.02em;
    border: 0.55pt solid #94a3b8; border-top: none;
}
.chk {
    display: inline-block; width: 3mm; height: 3mm;
    border: 0.7pt solid #334155; margin: 0 1.2mm 0 0; vertical-align: middle;
    text-align: center; font-size: 5.5pt; line-height: 2.8mm; background: #fff;
}
.chk.on { background: #1e3a5f; border-color: #1e3a5f; color: #fff; font-weight: 700; }
.opt {
    display: inline-block; margin-right: 3.5mm; white-space: nowrap;
    font-size: 7pt; font-weight: 700; color: #0f172a; text-transform: uppercase;
}
.blank { display: block; border-bottom: 0.45pt solid #cbd5e1; min-height: 3.4mm; }
.decl {
    border: 0.55pt solid #94a3b8; border-top: none; width: 100%;
    padding: 1.4mm 2.2mm; font-size: 6.5pt; line-height: 1.35;
    text-align: justify; color: #1e293b; text-transform: uppercase; letter-spacing: 0.015em;
}
.signs { width: 100%; border-collapse: collapse; margin-top: 1.4mm; table-layout: fixed; }
.signs td { width: 50%; border: none; vertical-align: top; padding: 0; }
.signs td.left { padding-right: 2.2mm; }
.signs td.right { padding-left: 2.2mm; }
.sign-box { border: 0.55pt solid #cbd5e1; background: #fafbfc; padding: 2.2mm 2.6mm; width: 100%; }
.sign-line { height: 6.5mm; border-bottom: 0.7pt solid #0f172a; margin-bottom: 0.8mm; }
.sign-title { font-size: 7pt; font-weight: 700; color: #0f172a; text-transform: uppercase; }
.sign-meta { margin-top: 1mm; font-size: 7pt; color: #475569; text-transform: uppercase; font-weight: 700; }
.office { margin-top: 1.4mm; border: 0.7pt solid #334155; width: 100%; }
.office-h {
    background: #e2e8f0; padding: 1.3mm 2.4mm; font-size: 7pt; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.05em; color: #1e293b;
    border-bottom: 0.55pt solid #334155;
}
.office-b { padding: 2mm 2.4mm; font-size: 7pt; color: #334155; text-transform: uppercase; }
.office-b table { width: 100%; border-collapse: collapse; table-layout: fixed; }
.office-b td { border: none; padding: 0.7mm 0; vertical-align: top; font-weight: 600; }
.foot {
    margin-top: 1.6mm; padding-top: 1mm; border-top: 0.5pt solid #cbd5e1;
    font-size: 6pt; color: #64748b; width: 100%; text-transform: uppercase;
}
.foot .pg { float: right; }
</style>
</head>
<body>
<div class="page">

<table class="lh">
    <tr>
        <td class="lh-logo">
            <?php if ($logoSrc !== ''): ?>
                <img src="<?php echo $e($logoSrc); ?>" alt="">
            <?php endif; ?>
        </td>
        <td class="lh-mid">
            <div class="lh-name"><?php echo $e($u($instName)); ?></div>
            <div class="lh-meta">
                <?php echo $e($u($instAddr)); ?>
                <?php if ($instPhone !== ''): ?><br>TEL: <?php echo $e($instPhone); ?><?php endif; ?>
            </div>
        </td>
        <td class="lh-side"></td>
    </tr>
</table>
<hr class="rule">

<div class="doc-h">
    <h1>Bus Season Request Form</h1>
    <p class="sub">Season ticket · Season year <?php echo $e($u($seasonYear)); ?></p>
</div>

<table class="grid">
    <tr>
        <td class="lab">Student ID</td>
        <td class="val"><?php echo $show($s['student_id'] ?? ''); ?></td>
        <td class="lab">Date</td>
        <td class="val"><?php echo $show($s['date'] ?? ''); ?></td>
    </tr>
</table>

<div class="sec">1. Student details</div>
<table class="grid">
    <tr>
        <td class="lab lab-w">Full name</td>
        <td class="val val-w" colspan="3"><?php echo $show($s['full_name'] ?? ''); ?></td>
    </tr>
    <tr>
        <td class="lab lab-w">Name with initials</td>
        <td class="val val-w" colspan="3"><?php echo $show($s['name_initials'] ?? ''); ?></td>
    </tr>
    <tr>
        <td class="lab">NIC number</td>
        <td class="val"><?php echo $show($s['nic'] ?? ''); ?></td>
        <td class="lab">Phone</td>
        <td class="val"><?php echo $show($s['phone'] ?? ''); ?></td>
    </tr>
    <tr>
        <td class="lab lab-w tall">Permanent address</td>
        <td class="val val-w tall" colspan="3"><?php echo $show($s['address'] ?? ''); ?></td>
    </tr>
    <tr>
        <td class="lab">District</td>
        <td class="val"><?php echo $show($s['district'] ?? ''); ?></td>
        <td class="lab">Province</td>
        <td class="val"><?php echo $show($s['province'] ?? ''); ?></td>
    </tr>
</table>

<div class="sec">2. Course</div>
<table class="grid">
    <tr>
        <td class="lab lab-w">Department</td>
        <td class="val val-w" colspan="3"><?php echo $show($s['department'] ?? ''); ?></td>
    </tr>
    <tr>
        <td class="lab">Course</td>
        <td class="val"><?php echo $show($s['course'] ?? ''); ?></td>
        <td class="lab">NVQ level</td>
        <td class="val"><?php echo $show($s['nvq_level'] ?? ''); ?></td>
    </tr>
    <tr>
        <td class="lab">Academic year</td>
        <td class="val" colspan="3"><?php echo $show($s['academic_year'] ?? ''); ?></td>
    </tr>
</table>

<div class="sec">3. Season and route</div>
<table class="grid">
    <tr>
        <td class="lab">Season year</td>
        <td class="val"><?php echo $show($seasonYear); ?></td>
        <td class="lab">Month</td>
        <td class="val"><?php echo $show($s['season_month'] ?? ''); ?></td>
    </tr>
    <tr>
        <td class="lab">Route from</td>
        <td class="val" colspan="3"><?php echo $show($s['route_from'] ?? ''); ?></td>
    </tr>
    <tr>
        <td class="lab">Route to</td>
        <td class="val" colspan="3"><?php echo $show($s['route_to'] ?? ''); ?></td>
    </tr>
    <tr>
        <td class="lab">Change point</td>
        <td class="val"><?php echo $show($s['change_point'] ?? ''); ?></td>
        <td class="lab">Distance (km)</td>
        <td class="val"><?php echo $show($s['distance_km'] ?? ''); ?></td>
    </tr>
    <tr>
        <td class="lab">Depot</td>
        <td class="val" colspan="3"><?php echo $show($s['depot_name'] ?? ''); ?></td>
    </tr>
    <tr>
        <td class="lab">Purpose</td>
        <td class="val" colspan="3">
            <span class="opt"><?php echo $chk(true); ?> Travel between residence and SLGTI</span>
        </td>
    </tr>
</table>
<div class="hint">If a route field is blank, write it in before you submit this form. Correct any prefilled route that has changed.</div>

<div class="sec">4. Parent / guardian</div>
<table class="grid">
    <tr>
        <td class="lab">Name</td>
        <td class="val"><?php echo $show($s['guardian_name'] ?? ''); ?></td>
        <td class="lab">Relationship</td>
        <td class="val"><?php echo $show($s['guardian_rel'] ?? ''); ?></td>
    </tr>
    <tr>
        <td class="lab">Contact number</td>
        <td class="val"><?php echo $show($s['guardian_phone'] ?? ''); ?></td>
        <td class="lab">Occupation</td>
        <td class="val"><?php echo $show($s['guardian_job'] ?? ''); ?></td>
    </tr>
</table>

<div class="sec">5. Declaration</div>
<div class="decl">
    I request a bus season ticket for travel between my residence and the <?php echo $e($u($instName)); ?>
    for the month and route stated above. I declare that the particulars given in this form are true and correct.
    I will use the season ticket only for the approved route and follow the institute transport rules.
</div>

<table class="signs">
    <tr>
        <td class="left">
            <div class="sign-box">
                <div class="sign-line"></div>
                <div class="sign-title">Signature of student</div>
                <div class="sign-meta">Name: <?php echo $e($u($s['name_initials'] ?? '')); ?></div>
                <div class="sign-meta">Date: <?php echo $e($u($s['date'] ?? '')); ?></div>
            </div>
        </td>
        <td class="right">
            <div class="sign-box">
                <div class="sign-line"></div>
                <div class="sign-title">Signature of parent / guardian</div>
                <div class="sign-meta">Name: <?php echo $e($u($s['guardian_name'] ?? '')); ?></div>
                <div class="sign-meta">Date: ________________</div>
            </div>
        </td>
    </tr>
</table>

<div class="office">
    <div class="office-h">For office use only</div>
    <div class="office-b">
        <table>
            <tr>
                <td style="width:50%;">
                    HOD:
                    <span class="opt"><span class="chk"></span> Approved</span>
                    <span class="opt"><span class="chk"></span> Rejected</span>
                </td>
                <td style="width:50%;">Signature: ____________________________</td>
            </tr>
            <tr>
                <td>
                    Second approval:
                    <span class="opt"><span class="chk"></span> Approved</span>
                    <span class="opt"><span class="chk"></span> Rejected</span>
                </td>
                <td>Signature: ____________________________</td>
            </tr>
            <tr>
                <td>SAO receipt no.: ________________________</td>
                <td>Amount (Rs.): __________________________</td>
            </tr>
        </table>
    </div>
</div>

<div class="foot">
    <?php echo $e($u($instName)); ?> · BUS SEASON REQUEST FORM
    <span class="pg">PAGE 1 OF 1</span>
</div>
</div>
</body>
</html>
