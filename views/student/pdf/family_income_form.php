<?php
declare(strict_types=1);
/**
 * Family Income Confirmation — A4, one page.
 * Grama Niladhari certification letter with student particulars pre-filled.
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
/** Empty handwriting gap — plain underscores (inline, no layout break). */
$blank = static function (int $dots = 18): string {
    return '<span class="u">' . str_repeat('_', max(8, $dots)) . '</span>';
};
/** Pre-filled value with underline; wraps with the paragraph. */
$fill = static function ($v, int $dots = 18) use ($e, $blank): string {
    $t = trim((string) $v);
    if ($t === '') {
        return $blank($dots);
    }
    return '<span class="u">' . $e($t) . '</span>';
};

$instName = (string) ($institute['name'] ?? 'Sri Lanka German Training Institute');
$instAddr = (string) ($institute['address'] ?? '');
$instPhone = (string) ($institute['phone'] ?? '');
$logoSrc = (string) ($logoSrc ?? '');
$s = is_array($sample ?? null) ? $sample : [];

$titleRaw = strtolower(rtrim(trim((string) ($s['title'] ?? '')), '.'));
if (in_array($titleRaw, ['mrs'], true)) {
    $honorific = 'Mrs.';
} elseif (in_array($titleRaw, ['ms', 'miss'], true)) {
    $honorific = 'Ms.';
} else {
    $honorific = 'Mr.';
}

$name = $u($s['full_name'] ?? '');
$nic = $u($s['nic'] ?? ($s['student_nic'] ?? ''));
$address = $u($s['address'] ?? '');
$date = trim((string) ($s['date'] ?? ''));
$regNo = $u($s['student_id'] ?? '');
$phone = $u($s['phone'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Family Income Confirmation</title>
<?php include __DIR__ . '/_request_form_style.php'; ?>
<style>
body { padding: 8mm 15mm 7mm 15mm !important; font-size: 9.2pt !important; }
.lh .c-logo img { height: 11mm !important; }
.inst-name { font-size: 10.2pt !important; }
.inst-meta { font-size: 7.5pt !important; }
.rule { margin: 1.8mm 0 2.2mm !important; }
.title { font-size: 12.5pt !important; letter-spacing: 0.08em !important; }

.fi-meta { margin-top: 1.5mm; }
table.f td { height: 6.8mm !important; }
table.f td.l { font-size: 8.2pt !important; }
table.f td.v { font-size: 8.8pt !important; }

.fi-subject {
    margin: 3mm 0 0;
    font-size: 9.2pt;
    font-weight: bold;
    text-align: center;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    border-bottom: 0.6pt solid #111;
    padding-bottom: 1mm;
}

.fi-body {
    margin: 3.5mm 0 0;
    font-size: 9.4pt;
    line-height: 1.65;
    text-align: left;
    color: #111;
    word-wrap: break-word;
    overflow-wrap: break-word;
}
.fi-body p {
    margin: 0 0 3mm;
    text-align: left;
}
.fi-body .u {
    font-weight: bold;
    text-decoration: underline;
    text-transform: uppercase;
    letter-spacing: 0.01em;
}
.amt { font-weight: bold; }

table.sig.fi-sig { margin-top: 5mm !important; }
table.sig.fi-sig .sig-line { height: 12mm !important; }
table.sig.fi-sig .sig-lab { font-size: 8pt !important; }
table.sig.fi-sig .sig-sub { font-size: 7.4pt !important; }

.office.fi-gn { margin-top: 5mm !important; }
.office.fi-gn .oh { font-size: 8pt !important; padding: 1.2mm 2.5mm !important; }
.office.fi-gn td { padding: 1.6mm 2.5mm 1.8mm !important; }
.office.fi-gn .ot { font-size: 7.8pt !important; margin-bottom: 0.7mm !important; }
.office.fi-gn .ol { height: 7mm !important; }
.office.fi-gn .os { font-size: 7pt !important; }
.office.fi-gn .stamp-box {
    height: 16mm;
    margin-top: 0.8mm;
}
.foot { margin-top: 2.5mm !important; font-size: 6.6pt !important; }
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
                <br>Tel: <?php
                    $phones = [];
                    if ($instPhone !== '') {
                        $phones[] = $instPhone;
                    }
                    $phones[] = '021 492 7799';
                    $phones = array_values(array_unique(array_map('trim', $phones)));
                    echo $e(implode(' / ', $phones));
                ?>
            </div>
        </td>
        <td class="c-side"></td>
    </tr>
</table>
<hr class="rule">

<h1 class="title">Family Income Confirmation</h1>

<table class="f fi-meta"><tr class="sp"><td class="l"></td><td class="v0"></td><td class="gap"></td><td class="l"></td><td class="v0"></td></tr>
    <tr>
        <td class="l">Student ID</td><td class="v"><?php echo $val($regNo); ?></td>
        <td class="gap"></td>
        <td class="l">Date</td><td class="v"><?php echo $val($date); ?></td>
    </tr>
    <tr>
        <td class="l">Contact no.</td><td class="v"><?php echo $val($phone); ?></td>
        <td colspan="3"></td>
    </tr>
</table>

<div class="fi-subject">Certificate of Family Annual Income</div>

<div class="fi-body">
    <p>This is to certify that <?php echo $e($honorific); ?> <?php echo $fill($name, 22); ?>, bearing National Identity Card Number <?php echo $fill($nic, 14); ?>, residing at <?php echo $fill($address, 28); ?>, is a resident of the <?php echo $blank(16); ?> Grama Niladhari Division, under the <?php echo $blank(16); ?> Divisional Secretariat.</p>
    <p>According to the available records, the total annual income of the applicant’s family from all sources is less than <span class="amt">LKR 500,000.00 (Sri Lankan Rupees Five Hundred Thousand Only).</span></p>
    <p>This certificate is issued upon the applicant’s request for submission to the <span class="amt">Sri Lanka German Training Institute (SLGTI), Kilinochchi</span>, for verification of family income and consideration of eligibility for student financial assistance.</p>
    <p>I hereby certify that the above information is true and accurate to the best of my knowledge.</p>
</div>

<table class="sig fi-sig">
    <tr>
        <td class="box">
            <div class="sig-line"></div>
            <div class="sig-lab">Signature of Grama Niladhari</div>
        </td>
        <td class="gap"></td>
        <td class="box">
            <div class="sig-line"></div>
            <div class="sig-lab">Date</div>
        </td>
    </tr>
</table>

<div class="office fi-gn">
    <div class="oh">Official particulars</div>
    <table>
        <tr>
            <td>
                <div class="ot">GN name</div>
                <div class="ol"></div>
            </td>
            <td>
                <div class="ot">GN division</div>
                <div class="ol"></div>
            </td>
            <td>
                <div class="ot">Official stamp</div>
                <div class="stamp-box">&nbsp;</div>
            </td>
        </tr>
    </table>
</div>

<div class="foot">SLGTI · Family Income Confirmation · For official use</div>

</body>
</html>
