<?php
declare(strict_types=1);
/**
 * Student Family Income Confirmation: A4, one page, declaration letter + Grama Niladhari certification.
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
$val = static function ($v) use ($e): string {
    $t = trim((string) $v);
    return $t === '' ? '&nbsp;' : $e($t);
};
$instName = (string) ($institute['name'] ?? 'Sri Lanka German Training Institute');
$instAddr = (string) ($institute['address'] ?? '');
$instPhone = (string) ($institute['phone'] ?? '');
$logoSrc = (string) ($logoSrc ?? '');
$s = is_array($sample ?? null) ? $sample : [];
$name = $u($s['full_name'] ?? '');
$regNo = $u($s['student_id'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Family Income Confirmation</title>
<?php include __DIR__ . '/_request_form_style.php'; ?>
<style>
.letter { font-size: 10pt; line-height: 1.95; text-align: left; margin: 4mm 0 0; color: #111; }
.letter p { margin: 0 0 4mm; }
.addr { font-size: 10pt; line-height: 1.55; margin: 7mm 0 0; }
.subject { font-size: 10pt; font-weight: bold; text-decoration: underline; margin: 7mm 0 0; }
.iv { display: inline-block; min-width: 45mm; border-bottom: 0.5pt solid #555; padding: 0 1.5mm; font-weight: bold; text-align: center; line-height: 1.3; }
.iv.s { min-width: 32mm; }
.gn { border: 0.7pt solid #555; margin-top: 12mm; }
.gn .oh { font-size: 8.8pt; font-weight: bold; letter-spacing: 0.07em; text-transform: uppercase; padding: 1.5mm 3mm; border-bottom: 0.7pt solid #555; background: #f2f2f2; }
.gn .gb { padding: 2mm 4mm 3mm; }
.gn .gt { font-size: 9.4pt; line-height: 1.7; text-align: left; margin: 0 0 1mm; }
.stamp { border: 0.7pt dashed #777; height: 24mm; }
</style>
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

<h1 class="title" style="font-size:13pt;">Student Family Income Confirmation</h1>

<table class="f"><tr class="sp"><td class="l"></td><td class="v0"></td><td class="gap"></td><td class="l"></td><td class="v0"></td></tr>
    <tr>
        <td class="l">Date</td><td class="v"><?php echo $val($s['date'] ?? ''); ?></td>
        <td colspan="3"></td>
    </tr>
</table>

<div class="addr">
    <strong>To:</strong><br>
    The Branch Principal,<br>
    Sri Lanka &ndash; German Training Institute (SLGTI),<br>
    Ariviyal Nagar, Kilinochchi.
</div>

<div class="subject">Subject: Confirmation of Family Annual Income</div>

<div class="letter">
    <p>
        I, <span class="iv"><?php echo $val($name); ?></span> (Student's Name), bearing Registration No.
        <span class="iv s"><?php echo $val($regNo); ?></span>, hereby declare that my family's total annual income is
        below <strong>Rs. 500,000</strong> (Five Hundred Thousand Rupees).
    </p>
    <p>
        I confirm that the above information is true and correct. I submit this declaration to SLGTI to verify my
        eligibility for the monthly student allowance of <strong>Rs. 7,500</strong>, subject to the applicable
        eligibility conditions.
    </p>
</div>

<table class="f" style="margin-top:6mm;"><tr class="sp"><td class="l"></td><td class="v0"></td><td class="gap"></td><td class="l"></td><td class="v0"></td></tr>
    <tr style="height:14mm;">
        <td class="l" style="height:14mm;">Student's signature</td><td class="v" style="height:14mm;">&nbsp;</td>
        <td class="gap"></td>
        <td class="l" style="height:14mm;">Contact number</td><td class="v" style="height:14mm;"><?php echo $val($s['phone'] ?? ''); ?></td>
    </tr>
</table>

<div class="gn">
    <div class="oh">Grama Niladhari Certification</div>
    <div class="gb">
        <p class="gt">
            I hereby certify that the above information regarding the student's family annual income is verified to
            the best of my knowledge and belief.
        </p>
        <table class="f"><tr class="sp"><td class="l"></td><td class="v0"></td><td class="gap"></td><td class="l"></td><td class="v0"></td></tr>
            <tr>
                <td class="l">GN name</td><td class="v">&nbsp;</td>
                <td class="gap"></td>
                <td class="l">GN division</td><td class="v">&nbsp;</td>
            </tr>
            <tr>
                <td class="l">Signature</td><td class="v">&nbsp;</td>
                <td class="gap"></td>
                <td class="l">Date</td><td class="v">&nbsp;</td>
            </tr>
            <tr>
                <td class="l" style="height:26mm;">Official stamp</td>
                <td style="height:26mm; vertical-align:bottom;"><div class="stamp">&nbsp;</div></td>
                <td colspan="3"></td>
            </tr>
        </table>
    </div>
</div>

</body>
</html>
