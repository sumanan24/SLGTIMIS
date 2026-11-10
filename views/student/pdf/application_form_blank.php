<?php
declare(strict_types=1);
/**
 * Student Application Form: A4, 2 pages, same look as the other student forms.
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
$val = static function ($v) use ($e, $u): string {
    $t = $u($v);
    return $t === '' ? '&nbsp;' : $e($t);
};
$valRaw = static function ($v) use ($e): string {
    $t = trim((string) $v);
    return $t === '' ? '&nbsp;' : $e($t);
};
$chk = static function (bool $on): string {
    return '<span class="chk' . ($on ? ' on' : '') . '">' . ($on ? '&#10003;' : '') . '</span>';
};
$opt = static function (bool $on, string $label) use ($chk): string {
    return '<span class="opt">' . $chk($on) . $label . '</span>';
};
$instName = (string) ($institute['name'] ?? 'Sri Lanka German Training Institute');
$instAddr = (string) ($institute['address'] ?? '');
$instPhone = (string) ($institute['phone'] ?? '');
$logoSrc = (string) ($logoSrc ?? '');
$year = (string) ($year ?? date('Y'));
$nextYear = (string) ((int) $year + 1);
$s = is_array($sample ?? null) ? $sample : [];
$g = static function (string $k) use ($s): string {
    return (string) ($s[$k] ?? '');
};
$subjects = is_array($s['ol_subjects'] ?? null) ? $s['ol_subjects'] : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Application Form</title>
<?php include __DIR__ . '/_request_form_style.php'; ?>
<style>
body { font-size: 9pt; }
table.f td { height: 7mm; }
.opt { margin-right: 3.5mm; }
.p2h { width: 100%; border-collapse: collapse; table-layout: fixed; border-bottom: 1.2pt solid #111; margin-bottom: 1mm; }
.p2h td { padding: 0 0 1.5mm; font-size: 9pt; vertical-align: bottom; }
.p2h .a { font-weight: bold; text-transform: uppercase; letter-spacing: 0.03em; width: 60%; }
.p2h .b { text-align: right; color: #555; font-size: 8pt; width: 40%; }
table.subj { width: 100%; border-collapse: collapse; table-layout: fixed; margin-top: 1.5mm; }
table.subj th, table.subj td { border: 0.5pt solid #777; padding: 0 2mm; height: 6mm; font-size: 8.4pt; text-align: center; vertical-align: middle; }
table.subj th { background: #f2f2f2; font-size: 8pt; text-transform: uppercase; letter-spacing: 0.04em; }
table.subj .c-no { width: 9%; }
table.subj .c-sub { width: 51%; text-align: left; font-weight: bold; text-transform: uppercase; }
table.subj .c-gr { width: 20%; font-weight: bold; text-transform: uppercase; }
table.subj .c-yr { width: 20%; font-weight: bold; }
.gn { border: 0.7pt solid #555; margin-top: 6mm; }
.gn .oh { font-size: 8.8pt; font-weight: bold; letter-spacing: 0.07em; text-transform: uppercase; padding: 1.5mm 3mm; border-bottom: 0.7pt solid #555; background: #f2f2f2; }
.gn .gb { padding: 1mm 4mm 2mm; }
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
        <td class="c-side"><div class="photo">Passport-size<br>photograph</div></td>
    </tr>
</table>
<hr class="rule">

<h1 class="title">Student Application Form</h1>
<div class="sub">Admission &middot; Academic year <?php echo $e($year); ?> / <?php echo $e($nextYear); ?></div>

<table class="f"><tr class="sp"><td class="l"></td><td class="v0"></td><td class="gap"></td><td class="l"></td><td class="v0"></td></tr>
    <tr>
        <td class="l">Student ID</td><td class="v"><?php echo $val($s['student_id'] ?? ($s['student_regnumber'] ?? '')); ?></td>
        <td class="gap"></td>
        <td class="l">Date</td><td class="v"><?php echo $valRaw($g('date')); ?></td>
    </tr>
    <tr>
        <td class="l">Level applied</td>
        <td colspan="4" style="vertical-align:bottom; padding-bottom:0.7mm;">
            <?php echo $opt(!empty($s['level_04']), 'Level 04'); ?>
            <?php echo $opt(!empty($s['level_05']), 'Level 05'); ?>
        </td>
    </tr>
</table>

<div class="sec">1. Personal details</div>
<table class="f"><tr class="sp"><td class="l"></td><td class="v0"></td><td class="gap"></td><td class="l"></td><td class="v0"></td></tr>
    <tr>
        <td class="l">Title</td>
        <td colspan="4" style="vertical-align:bottom; padding-bottom:0.7mm;">
            <?php echo $opt($g('title') === 'Mr', 'Mr'); ?>
            <?php echo $opt($g('title') === 'Ms', 'Ms'); ?>
            <?php echo $opt($g('title') === 'Mrs', 'Mrs'); ?>
            <?php echo $opt($g('title') === 'Other', 'Other'); ?>
        </td>
    </tr>
    <tr><td class="l">Full name</td><td class="v" colspan="4"><?php echo $val($g('full_name')); ?></td></tr>
    <tr><td class="l">Name with initials</td><td class="v" colspan="4"><?php echo $val($g('name_initials')); ?></td></tr>
    <tr>
        <td class="l">NIC number</td><td class="v"><?php echo $val($g('nic')); ?></td>
        <td class="gap"></td>
        <td class="l">Date of birth</td><td class="v"><?php echo $valRaw($g('dob')); ?></td>
    </tr>
    <tr>
        <td class="l">Gender</td>
        <td style="vertical-align:bottom; padding-bottom:0.7mm;">
            <?php echo $opt($g('gender') === 'Male', 'Male'); ?>
            <?php echo $opt($g('gender') === 'Female', 'Female'); ?>
        </td>
        <td class="gap"></td>
        <td class="l">Civil status</td>
        <td style="vertical-align:bottom; padding-bottom:0.7mm;">
            <?php echo $opt($g('civil') === 'Single', 'Single'); ?>
            <?php echo $opt($g('civil') === 'Married', 'Married'); ?>
        </td>
    </tr>
    <tr>
        <td class="l">Language</td>
        <td colspan="4" style="vertical-align:bottom; padding-bottom:0.7mm;">
            <?php echo $opt($g('language') === 'Tamil', 'Tamil'); ?>
            <?php echo $opt($g('language') === 'Sinhala', 'Sinhala'); ?>
            <?php echo $opt($g('language') === 'English', 'English'); ?>
        </td>
    </tr>
    <tr>
        <td class="l">Religion</td><td class="v"><?php echo $val($g('religion')); ?></td>
        <td class="gap"></td>
        <td class="l">Nationality</td><td class="v"><?php echo $val($g('nationality')); ?></td>
    </tr>
    <tr>
        <td class="l">Blood group</td><td class="v"><?php echo $val($g('blood')); ?></td>
        <td colspan="3"></td>
    </tr>
</table>

<div class="sec">2. Contact and address</div>
<table class="f"><tr class="sp"><td class="l"></td><td class="v0"></td><td class="gap"></td><td class="l"></td><td class="v0"></td></tr>
    <tr>
        <td class="l">Phone</td><td class="v"><?php echo $valRaw($g('phone')); ?></td>
        <td class="gap"></td>
        <td class="l">WhatsApp</td><td class="v"><?php echo $valRaw($g('whatsapp')); ?></td>
    </tr>
    <tr><td class="l">Email</td><td class="v" colspan="4" style="text-transform:none;"><?php echo $valRaw($g('email')); ?></td></tr>
    <tr><td class="l top tall">Permanent address</td><td class="v tall" colspan="4" style="vertical-align:top; padding-top:1.6mm;"><?php echo $val($g('address')); ?></td></tr>
    <tr>
        <td class="l">Province</td><td class="v"><?php echo $val($g('province')); ?></td>
        <td class="gap"></td>
        <td class="l">District</td><td class="v"><?php echo $val($g('district')); ?></td>
    </tr>
    <tr>
        <td class="l">Postal code</td><td class="v"><?php echo $valRaw($g('postal')); ?></td>
        <td class="gap"></td>
        <td class="l">GN division</td><td class="v"><?php echo $val($g('gn')); ?></td>
    </tr>
</table>

<div class="sec">3. Parent / guardian</div>
<table class="f"><tr class="sp"><td class="l"></td><td class="v0"></td><td class="gap"></td><td class="l"></td><td class="v0"></td></tr>
    <tr>
        <td class="l">Name</td><td class="v"><?php echo $val($g('guardian_name')); ?></td>
        <td class="gap"></td>
        <td class="l">Relationship</td><td class="v"><?php echo $val($g('guardian_rel')); ?></td>
    </tr>
    <tr>
        <td class="l">Contact number</td><td class="v"><?php echo $valRaw($g('guardian_phone')); ?></td>
        <td class="gap"></td>
        <td class="l">Occupation</td><td class="v"><?php echo $val($g('guardian_job')); ?></td>
    </tr>
</table>

<div class="sec">4. Enrolled course</div>
<table class="f"><tr class="sp"><td class="l"></td><td class="v0"></td><td class="gap"></td><td class="l"></td><td class="v0"></td></tr>
    <tr><td class="l">Department</td><td class="v" colspan="4"><?php echo $val($g('department')); ?></td></tr>
    <tr><td class="l">Course</td><td class="v" colspan="4"><?php echo $val($g('course')); ?></td></tr>
    <tr>
        <td class="l">Course mode</td>
        <td colspan="4" style="vertical-align:bottom; padding-bottom:0.7mm;">
            <?php echo $opt($g('mode') === 'Full Time', 'Full time'); ?>
            <?php echo $opt($g('mode') === 'Part Time', 'Part time'); ?>
        </td>
    </tr>
</table>

<div class="foot"><?php echo $e($instName); ?> &middot; Student Application Form &middot; Page 1 of 2</div>

<div style="page-break-before: always; padding-top: 8mm;">

<table class="p2h">
    <tr>
        <td class="a"><?php echo $e($instName); ?></td>
        <td class="b">Student Application Form &middot; continued</td>
    </tr>
</table>

<div class="sec" style="margin-top:3mm;">5. Educational qualifications &ndash; G.C.E. O/L</div>
<table class="f"><tr class="sp"><td class="l"></td><td class="v0"></td><td class="gap"></td><td class="l"></td><td class="v0"></td></tr>
    <tr>
        <td class="l">Index number</td><td class="v"><?php echo $val($g('ol_index')); ?></td>
        <td class="gap"></td>
        <td class="l">Year of exam</td><td class="v"><?php echo $valRaw($g('ol_year')); ?></td>
    </tr>
</table>
<table class="subj">
    <tr><th class="c-no">No.</th><th class="c-sub" style="font-weight:bold;">Subject</th><th class="c-gr">Grade</th><th class="c-yr">Year</th></tr>
    <?php for ($i = 0; $i < 9; $i++): $row = $subjects[$i] ?? ['subject' => '', 'grade' => '', 'year' => '']; ?>
    <tr>
        <td class="c-no" style="font-weight:normal;"><?php echo $i + 1; ?></td>
        <td class="c-sub"><?php echo $val($row['subject'] ?? ''); ?></td>
        <td class="c-gr"><?php echo $val($row['grade'] ?? ''); ?></td>
        <td class="c-yr"><?php echo $valRaw($row['year'] ?? ''); ?></td>
    </tr>
    <?php endfor; ?>
</table>

<div class="sec">6. Educational qualifications &ndash; G.C.E. A/L / NVQ</div>
<table class="f"><tr class="sp"><td class="l"></td><td class="v0"></td><td class="gap"></td><td class="l"></td><td class="v0"></td></tr>
    <tr>
        <td class="l">Index / Reg. no.</td><td class="v"><?php echo $val($g('al_index')); ?></td>
        <td class="gap"></td>
        <td class="l">Year of exam</td><td class="v"><?php echo $valRaw($g('al_year')); ?></td>
    </tr>
    <tr><td class="l">Stream / NVQ level</td><td class="v" colspan="4"><?php echo $val($g('al_stream')); ?></td></tr>
    <tr><td class="l top tall">Subjects / results</td><td class="v tall" colspan="4" style="vertical-align:top; padding-top:1.6mm;"><?php echo $val($g('al_results')); ?></td></tr>
</table>

<div class="sec">7. Declaration by applicant</div>
<p class="decl">
    I declare that the particulars given in this application are true and correct to the best of my knowledge and belief.
    I understand that any false statement or omission may result in the rejection of this application or the cancellation
    of enrolment at the <?php echo $e($instName); ?>. I agree to follow the rules, regulations and code of conduct of the
    institute if selected.
</p>

<table class="sig">
    <tr>
        <td class="box">
            <div class="sig-line"></div>
            <div class="sig-lab">Signature of applicant</div>
        </td>
        <td class="gap"></td>
        <td class="box">
            <div class="sig-line"></div>
            <div class="sig-lab">Signature of parent / guardian</div>
        </td>
    </tr>
</table>

<div class="gn">
    <div class="oh">For office use only</div>
    <div class="gb">
        <table class="f"><tr class="sp"><td class="l"></td><td class="v0"></td><td class="gap"></td><td class="l"></td><td class="v0"></td></tr>
            <tr>
                <td class="l">Received on</td><td class="v">&nbsp;</td>
                <td class="gap"></td>
                <td class="l">Received by</td><td class="v">&nbsp;</td>
            </tr>
            <tr>
                <td class="l">Status</td>
                <td colspan="4" style="vertical-align:bottom; padding-bottom:0.7mm;">
                    <?php echo $opt(false, 'Accepted'); ?>
                    <?php echo $opt(false, 'Pending'); ?>
                    <?php echo $opt(false, 'Rejected'); ?>
                </td>
            </tr>
            <tr><td class="l">Remarks</td><td class="v" colspan="4">&nbsp;</td></tr>
        </table>
    </div>
</div>

<div class="foot"><?php echo $e($instName); ?> &middot; Student Application Form &middot; Page 2 of 2</div>
</div>

</body>
</html>