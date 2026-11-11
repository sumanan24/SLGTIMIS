<?php
declare(strict_types=1);
/**
 * One-page A4 Student Intake Checklist (red / black / white).
 * Only student details from MIS are filled; checklist boxes are blank for staff use.
 *
 * @var array  $student  student_id, name, department, course, academic_year, token
 * @var string $logoSrc
 * @var array  $institute
 */
$e = static function ($v): string {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};
$u = static function ($v) use ($e): string {
    $t = trim((string) ($v ?? ''));
    if ($t === '') {
        return '';
    }
    $up = function_exists('mb_strtoupper') ? mb_strtoupper($t, 'UTF-8') : strtoupper($t);
    return $e($up);
};
$val = static function ($v) use ($u): string {
    $t = $u($v);
    return $t === '' ? '&nbsp;' : $t;
};
$s = is_array($student ?? null) ? $student : [];
$instName = (string) ($institute['name'] ?? 'Sri Lanka German Training Institute');
$instAddr = (string) ($institute['address'] ?? '');
$instPhone = (string) ($institute['phone'] ?? '');
$logoSrc = (string) ($logoSrc ?? '');
$year = trim((string) ($s['academic_year'] ?? ''));
$token = trim((string) ($s['token'] ?? ''));

$sections = [
    ['title' => '1. Student Registration', 'items' => [
        'Student record created / verified in MIS',
        'Registration number issued and confirmed',
    ]],
    ['title' => '2. Photograph Capture', 'items' => [
        'Passport-size photograph captured',
        'Photograph uploaded to student profile',
    ]],
    ['title' => '3. Payment Verification', 'items' => [
        'Admission / registration fee verified',
        'Receipt recorded and payment status updated',
    ]],
    ['title' => '4. Document Verification', 'items' => [
        'NIC / birth certificate verified',
        'Educational certificates verified and filed',
    ]],
    ['title' => '5. Final Approval', 'items' => [
        'All previous sections completed',
        'Student cleared for class commencement',
    ]],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Intake Checklist</title>
<style>
@page { size: A4 portrait; margin: 0; }
* { box-sizing: border-box; }
html, body { margin: 0; padding: 0; background: #fff; }
body {
    font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
    font-size: 9pt; color: #111; line-height: 1.3;
    padding: 8mm 14mm 8mm 14mm;
}
.band { height: 3.5mm; background: #b91c1c; margin: 0 0 3mm; }
.lh { width: 100%; border-collapse: collapse; table-layout: fixed; }
.lh td { padding: 0; vertical-align: middle; }
.lh .c-logo { width: 18%; }
.lh .c-logo img { height: 11mm; width: auto; display: block; }
.lh .c-mid { width: 64%; text-align: center; }
.lh .c-side { width: 18%; text-align: right; }
.inst-name {
    font-size: 10.5pt; font-weight: bold; letter-spacing: 0.03em;
    text-transform: uppercase; color: #111; margin: 0 0 0.4mm;
}
.inst-meta { font-size: 7.4pt; color: #444; line-height: 1.3; }
.badge {
    display: inline-block; border: 1.1pt solid #b91c1c; color: #b91c1c;
    font-size: 6.8pt; font-weight: bold; letter-spacing: 0.06em; text-transform: uppercase;
    padding: 1.2mm 1.8mm; line-height: 1.2;
}
.rule { border: 0; border-top: 1.4pt solid #111; margin: 2.2mm 0 1.2mm; height: 0; }
.rule-red { border: 0; border-top: 1.2pt solid #b91c1c; margin: 0 0 2.2mm; height: 0; }

.title {
    text-align: center; font-size: 13pt; font-weight: bold; letter-spacing: 0.1em;
    text-transform: uppercase; margin: 0 0 0.6mm; color: #111;
}
.sub { text-align: center; font-size: 8.2pt; color: #444; margin: 0 0 2.2mm; }

table.f { width: 100%; border-collapse: collapse; table-layout: fixed; margin-bottom: 0.5mm; }
table.f td { padding: 0; height: 6.4mm; vertical-align: bottom; }
table.f td.l { width: 22%; font-size: 8pt; color: #444; padding-right: 2mm; white-space: nowrap; }
table.f td.v {
    width: 28%; border-bottom: 0.55pt solid #333; padding: 0 1mm 0.5mm;
    font-size: 9pt; font-weight: bold; text-transform: uppercase;
}
table.f td.gap { width: 4%; }
table.f tr.sp td { height: 0; line-height: 0; font-size: 0; padding: 0; border: 0; }

.sec { border: 0.65pt solid #111; margin: 2mm 0 0; }
.sec-h {
    background: #b91c1c; color: #fff; font-size: 8.2pt; font-weight: bold;
    letter-spacing: 0.05em; text-transform: uppercase; padding: 1.1mm 2.5mm;
}
.sec-b { padding: 0.8mm 2.5mm 1mm; }
.item { font-size: 8.2pt; padding: 0.9mm 0; border-bottom: 0.4pt dotted #ccc; }
.item:last-child { border-bottom: 0; }
.chk {
    display: inline-block; width: 3mm; height: 3mm; border: 0.65pt solid #111;
    margin: 0 1.8mm 0 0; vertical-align: middle; background: #fff;
}

.foot-row { width: 100%; border-collapse: collapse; table-layout: fixed; margin-top: 3mm; }
.foot-row td { vertical-align: top; padding: 0; }
.sig-box { border: 0.65pt solid #111; padding: 1.6mm 2.2mm; }
.sig-lab { font-size: 7.4pt; font-weight: bold; text-transform: uppercase; color: #111; }
.sig-line { border-bottom: 0.55pt solid #333; height: 7mm; margin: 0.8mm 0 0.5mm; }
.sig-sub { font-size: 7.2pt; color: #555; }
.gap-cell { width: 4%; }
.box-cell { width: 48%; }

.footer {
    margin-top: 2.5mm; padding-top: 1mm; border-top: 0.55pt solid #b91c1c;
    font-size: 6.8pt; color: #555; text-align: center;
}
</style>
</head>
<body>

<div class="band"></div>

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
        <td class="c-side"><span class="badge">Intake<br>Checklist</span></td>
    </tr>
</table>
<hr class="rule">
<hr class="rule-red">

<h1 class="title">Student Intake Checklist</h1>
<div class="sub">
    Academic year / intake:
    <strong><?php echo $year !== '' ? $e($year) : '____________________'; ?></strong>
</div>

<table class="f">
    <tr class="sp"><td class="l"></td><td class="v"></td><td class="gap"></td><td class="l"></td><td class="v"></td></tr>
    <tr>
        <td class="l">Student name</td>
        <td class="v" colspan="4" style="width:78%;"><?php echo $val($s['name'] ?? ''); ?></td>
    </tr>
    <tr>
        <td class="l">Registration no.</td>
        <td class="v"><?php echo $val($s['student_id'] ?? ''); ?></td>
        <td class="gap"></td>
        <td class="l">Token no.</td>
        <td class="v"><?php echo $token !== '' ? $val($token) : '&nbsp;'; ?></td>
    </tr>
    <tr>
        <td class="l">Department</td>
        <td class="v"><?php echo $val($s['department'] ?? ''); ?></td>
        <td class="gap"></td>
        <td class="l">Course</td>
        <td class="v"><?php echo $val($s['course'] ?? ''); ?></td>
    </tr>
</table>

<?php foreach ($sections as $sec): ?>
<div class="sec">
    <div class="sec-h"><?php echo $e($sec['title']); ?></div>
    <div class="sec-b">
        <?php foreach ($sec['items'] as $item): ?>
            <div class="item"><span class="chk"></span><?php echo $e($item); ?></div>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>

<table class="foot-row">
    <tr>
        <td class="box-cell">
            <div class="sig-box">
                <div class="sig-lab">Processed by</div>
                <div class="sig-line"></div>
                <div class="sig-sub">Name / signature &amp; date</div>
            </div>
        </td>
        <td class="gap-cell"></td>
        <td class="box-cell">
            <div class="sig-box">
                <div class="sig-lab">Final approval</div>
                <div class="sig-line"></div>
                <div class="sig-sub">Officer signature &amp; date</div>
            </div>
        </td>
    </tr>
</table>

<div class="footer">
    <?php echo $e($instName); ?> &middot; Student Intake Checklist &middot; One page per student
</div>

</body>
</html>
