<?php
declare(strict_types=1);
/**
 * Student Application Form — A4, 2 pages. Values in FULL CAPS; 100% grid align.
 *
 * @var string $logoSrc
 * @var array  $institute
 * @var string $year
 * @var array  $sample
 */
$e = static function ($v): string {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};
/** Uppercase display text (UTF-8 safe). */
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
$nextYear = (string) ((int) $year + 1);

$chk = static function (bool $on): string {
    return $on
        ? '<span class="chk on">&#10003;</span>'
        : '<span class="chk"></span>';
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Application Form</title>
<style>
@page {
    size: A4 portrait;
    margin: 10px;
}
* { box-sizing: border-box; }
html, body { margin: 0; padding: 0; width: 100%; }
body {
    font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
    font-size: 8pt;
    color: #0f172a;
    line-height: 1.3;
    width: 100%;
}

.page { width: 100%; }
.page-break { page-break-before: always; }

/* Letterhead — full width */
.lh { width: 100%; border-collapse: collapse; margin: 0 0 2mm 0; table-layout: fixed; }
.lh td { border: none; vertical-align: middle; padding: 0; }
.lh-logo { width: 18%; }
.lh-logo img { height: 12mm; width: auto; display: block; }
.lh-mid { width: 54%; text-align: center; padding: 0 3mm; }
.lh-name {
    font-size: 10.5pt; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.05em; line-height: 1.2; margin: 0 0 1mm 0; color: #0f172a;
}
.lh-meta { font-size: 7pt; color: #475569; line-height: 1.4; text-transform: uppercase; }
.lh-photo { width: 28%; text-align: right; }
.photo {
    display: inline-block; width: 22mm; height: 27mm;
    border: 0.75pt solid #334155; background: #f8fafc;
    text-align: center; font-size: 5.5pt; color: #64748b;
    line-height: 1.3; padding-top: 9mm; text-transform: uppercase;
}

.rule {
    height: 0; border: none;
    border-top: 1.6pt solid #0f172a; border-bottom: 0.45pt solid #0f172a;
    margin: 0 0 2.5mm 0; padding: 0; width: 100%;
}

.doc-h { text-align: center; margin: 0 0 2.2mm 0; width: 100%; }
.doc-h h1 {
    margin: 0 0 0.6mm 0; font-size: 11pt; font-weight: 700;
    letter-spacing: 0.08em; text-transform: uppercase; color: #0f172a;
}
.doc-h .sub {
    margin: 0; font-size: 7.5pt; color: #334155; text-transform: uppercase;
    letter-spacing: 0.04em;
}

/* 100% width grids — equal columns */
.grid {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    margin: 0;
}
.grid td {
    border: 0.55pt solid #94a3b8;
    padding: 1.4mm 2.2mm;
    vertical-align: middle;
    height: 6mm;
    font-size: 7.5pt;
}
.grid .lab {
    width: 22%;
    background: #f1f5f9;
    font-size: 6.5pt;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #334155;
}
.grid .val {
    width: 28%;
    color: #0f172a;
    font-weight: 700;
    font-size: 7.5pt;
    text-transform: uppercase;
    letter-spacing: 0.02em;
}
.grid .lab-w { width: 22%; }
.grid .val-w { width: 78%; }
.grid .tall { height: 8mm; vertical-align: top; padding-top: 1.5mm; }
.grid .tall2 { height: 9.5mm; vertical-align: top; padding-top: 1.5mm; }

.sec {
    margin: 2.2mm 0 0 0; width: 100%;
    background: #1e3a5f; color: #fff;
    font-size: 7pt; font-weight: 700;
    letter-spacing: 0.06em; text-transform: uppercase;
    padding: 1.4mm 2.4mm;
}
.hint {
    margin: 0.7mm 0 1mm 0; font-size: 6.5pt; color: #64748b;
    text-transform: uppercase; letter-spacing: 0.02em;
}

.chk {
    display: inline-block; width: 3mm; height: 3mm;
    border: 0.7pt solid #334155; margin: 0 1.2mm 0 0; vertical-align: middle;
    text-align: center; font-size: 5.5pt; line-height: 2.8mm; background: #fff;
}
.chk.on {
    background: #1e3a5f; border-color: #1e3a5f; color: #fff; font-weight: 700;
}
.opt {
    display: inline-block; margin-right: 3.5mm; white-space: nowrap;
    font-size: 7pt; font-weight: 700; color: #0f172a; text-transform: uppercase;
}

.subj {
    width: 100%; border-collapse: collapse; table-layout: fixed; margin: 0;
}
.subj th, .subj td {
    border: 0.55pt solid #94a3b8; padding: 1.1mm 1.8mm;
    height: 5.4mm; font-size: 7pt; text-align: center;
}
.subj th {
    background: #e2e8f0; font-size: 6.5pt; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.04em; color: #334155;
}
.subj .c-no { width: 8%; }
.subj .c-sub {
    width: 54%; text-align: left; padding-left: 2.4mm;
    font-weight: 700; text-transform: uppercase;
}
.subj .c-gr { width: 19%; font-weight: 700; text-transform: uppercase; }
.subj .c-yr { width: 19%; font-weight: 700; }

.decl {
    border: 0.55pt solid #94a3b8; border-top: none; width: 100%;
    padding: 2.2mm 2.6mm; font-size: 7pt; line-height: 1.42;
    text-align: justify; color: #1e293b; text-transform: uppercase;
    letter-spacing: 0.015em;
}

.signs { width: 100%; border-collapse: collapse; margin-top: 2.5mm; table-layout: fixed; }
.signs td { width: 50%; border: none; vertical-align: top; padding: 0; }
.signs td.left { padding-right: 2.5mm; }
.signs td.right { padding-left: 2.5mm; }
.sign-box {
    border: 0.55pt solid #cbd5e1; background: #fafbfc;
    padding: 2.2mm 2.6mm; width: 100%;
}
.sign-line {
    height: 9mm; border-bottom: 0.7pt solid #0f172a; margin-bottom: 1.2mm;
}
.sign-title {
    font-size: 7pt; font-weight: 700; color: #0f172a; text-transform: uppercase;
}
.sign-meta {
    margin-top: 1mm; font-size: 7pt; color: #475569; text-transform: uppercase;
    font-weight: 700;
}

.office { margin-top: 2.5mm; border: 0.7pt solid #334155; width: 100%; }
.office-h {
    background: #e2e8f0; padding: 1.3mm 2.4mm;
    font-size: 7pt; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.05em; color: #1e293b;
    border-bottom: 0.55pt solid #334155;
}
.office-b { padding: 2mm 2.4mm; font-size: 7pt; color: #334155; text-transform: uppercase; }
.office-b table { width: 100%; border-collapse: collapse; table-layout: fixed; }
.office-b td { border: none; padding: 1.1mm 0; vertical-align: top; font-weight: 600; }

.p2-head { width: 100%; border-collapse: collapse; margin: 0 0 2mm 0; table-layout: fixed; }
.p2-head td { border: none; padding: 0; vertical-align: middle; }
.p2-head .t {
    width: 60%; font-size: 8pt; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.04em; color: #1e3a5f;
}
.p2-head .r {
    width: 40%; text-align: right; font-size: 7pt; color: #64748b;
    text-transform: uppercase;
}
.p2-rule { border: none; border-top: 0.7pt solid #94a3b8; margin: 0 0 2.2mm 0; width: 100%; }

.foot {
    margin-top: 2.8mm; padding-top: 1.4mm; border-top: 0.5pt solid #cbd5e1;
    font-size: 6pt; color: #64748b; width: 100%; text-transform: uppercase;
}
.foot .pg { float: right; }
</style>
</head>
<body>

<!-- PAGE 1 -->
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
        <td class="lh-photo">
            <div class="photo">Passport-size<br>photograph</div>
        </td>
    </tr>
</table>
<hr class="rule">

<div class="doc-h">
    <h1>Student Application Form</h1>
    <p class="sub">Admission · Academic Year <?php echo $e($year); ?> / <?php echo $e($nextYear); ?></p>
</div>

<table class="grid">
    <tr>
        <td class="lab">Student ID</td>
        <td class="val"><?php echo $e($u($s['student_id'] ?? ($s['student_regnumber'] ?? ''))); ?></td>
        <td class="lab">Date</td>
        <td class="val"><?php echo $e($u($s['date'] ?? '')); ?></td>
    </tr>
    <tr>
        <td class="lab">Level applied</td>
        <td class="val" colspan="3">
            <span class="opt"><?php echo $chk(!empty($s['level_04'])); ?> Level 04</span>
            <span class="opt"><?php echo $chk(!empty($s['level_05'])); ?> Level 05</span>
        </td>
    </tr>
</table>

<div class="sec">1. Personal details</div>
<table class="grid">
    <tr>
        <td class="lab">Title</td>
        <td class="val" colspan="3">
            <span class="opt"><?php echo $chk(($s['title'] ?? '') === 'Mr'); ?> Mr</span>
            <span class="opt"><?php echo $chk(($s['title'] ?? '') === 'Ms'); ?> Ms</span>
            <span class="opt"><?php echo $chk(($s['title'] ?? '') === 'Mrs'); ?> Mrs</span>
            <span class="opt"><?php echo $chk(($s['title'] ?? '') === 'Other'); ?> Other</span>
        </td>
    </tr>
    <tr>
        <td class="lab lab-w">Full name</td>
        <td class="val val-w" colspan="3"><?php echo $e($u($s['full_name'] ?? '')); ?></td>
    </tr>
    <tr>
        <td class="lab lab-w">Name with initials</td>
        <td class="val val-w" colspan="3"><?php echo $e($u($s['name_initials'] ?? '')); ?></td>
    </tr>
    <tr>
        <td class="lab">NIC number</td>
        <td class="val"><?php echo $e($u($s['nic'] ?? '')); ?></td>
        <td class="lab">Date of birth</td>
        <td class="val"><?php echo $e($u($s['dob'] ?? '')); ?></td>
    </tr>
    <tr>
        <td class="lab">Gender</td>
        <td class="val">
            <span class="opt"><?php echo $chk(($s['gender'] ?? '') === 'Male'); ?> Male</span>
            <span class="opt"><?php echo $chk(($s['gender'] ?? '') === 'Female'); ?> Female</span>
            <span class="opt"><?php echo $chk(($s['gender'] ?? '') === 'Other'); ?> Other</span>
        </td>
        <td class="lab">Civil status</td>
        <td class="val">
            <span class="opt"><?php echo $chk(($s['civil'] ?? '') === 'Single'); ?> Single</span>
            <span class="opt"><?php echo $chk(($s['civil'] ?? '') === 'Married'); ?> Married</span>
        </td>
    </tr>
    <tr>
        <td class="lab">Language</td>
        <td class="val">
            <span class="opt"><?php echo $chk(($s['language'] ?? '') === 'Tamil'); ?> Tamil</span>
            <span class="opt"><?php echo $chk(($s['language'] ?? '') === 'Sinhala'); ?> Sinhala</span>
            <span class="opt"><?php echo $chk(($s['language'] ?? '') === 'English'); ?> English</span>
        </td>
        <td class="lab">Religion</td>
        <td class="val"><?php echo $e($u($s['religion'] ?? '')); ?></td>
    </tr>
    <tr>
        <td class="lab">Blood group</td>
        <td class="val"><?php echo $e($u($s['blood'] ?? '')); ?></td>
        <td class="lab">Nationality</td>
        <td class="val"><?php echo $e($u($s['nationality'] ?? '')); ?></td>
    </tr>
</table>

<div class="sec">2. Contact &amp; address</div>
<table class="grid">
    <tr>
        <td class="lab">Phone</td>
        <td class="val"><?php echo $e($u($s['phone'] ?? '')); ?></td>
        <td class="lab">WhatsApp</td>
        <td class="val"><?php echo $e($u($s['whatsapp'] ?? '')); ?></td>
    </tr>
    <tr>
        <td class="lab lab-w">Email</td>
        <td class="val val-w" colspan="3"><?php echo $e($u($s['email'] ?? '')); ?></td>
    </tr>
    <tr>
        <td class="lab lab-w tall">Permanent address</td>
        <td class="val val-w tall" colspan="3"><?php echo $e($u($s['address'] ?? '')); ?></td>
    </tr>
    <tr>
        <td class="lab">Province</td>
        <td class="val"><?php echo $e($u($s['province'] ?? '')); ?></td>
        <td class="lab">District</td>
        <td class="val"><?php echo $e($u($s['district'] ?? '')); ?></td>
    </tr>
    <tr>
        <td class="lab">Postal code</td>
        <td class="val"><?php echo $e($u($s['postal'] ?? '')); ?></td>
        <td class="lab">GN Division</td>
        <td class="val"><?php echo $e($u($s['gn'] ?? '')); ?></td>
    </tr>
</table>

<div class="sec">3. Parent / guardian</div>
<table class="grid">
    <tr>
        <td class="lab">Name</td>
        <td class="val"><?php echo $e($u($s['guardian_name'] ?? '')); ?></td>
        <td class="lab">Relationship</td>
        <td class="val"><?php echo $e($u($s['guardian_rel'] ?? '')); ?></td>
    </tr>
    <tr>
        <td class="lab">Contact number</td>
        <td class="val"><?php echo $e($u($s['guardian_phone'] ?? '')); ?></td>
        <td class="lab">Occupation</td>
        <td class="val"><?php echo $e($u($s['guardian_job'] ?? '')); ?></td>
    </tr>
</table>

<div class="foot">
    <?php echo $e($u($instName)); ?> · STUDENT APPLICATION FORM
    <span class="pg">PAGE 1 OF 2</span>
</div>
</div>

<!-- PAGE 2 -->
<div class="page page-break">

<table class="p2-head">
    <tr>
        <td class="t"><?php echo $e($u($instName)); ?></td>
        <td class="r">Student Application Form · Continued</td>
    </tr>
</table>
<hr class="p2-rule">

<div class="sec" style="margin-top:0;">4. Enrolled course</div>
<table class="grid">
    <tr>
        <td class="lab lab-w">Department</td>
        <td class="val val-w" colspan="3"><?php echo $e($u($s['department'] ?? '')); ?></td>
    </tr>
    <tr>
        <td class="lab lab-w">Course</td>
        <td class="val val-w" colspan="3"><?php echo $e($u($s['course'] ?? '')); ?></td>
    </tr>
    <tr>
        <td class="lab">Course mode</td>
        <td class="val" colspan="3">
            <span class="opt"><?php echo $chk(($s['mode'] ?? '') === 'Full Time'); ?> Full Time</span>
            <span class="opt"><?php echo $chk(($s['mode'] ?? '') === 'Part Time'); ?> Part Time</span>
        </td>
    </tr>
</table>

<div class="sec">5. Educational qualifications — G.C.E. O/L</div>
<table class="grid">
    <tr>
        <td class="lab">Index number</td>
        <td class="val"><?php echo $e($u($s['ol_index'] ?? '')); ?></td>
        <td class="lab">Year of exam</td>
        <td class="val"><?php echo $e($u($s['ol_year'] ?? '')); ?></td>
    </tr>
</table>
<table class="subj">
    <tr>
        <th class="c-no">No.</th>
        <th class="c-sub">Subject</th>
        <th class="c-gr">Grade</th>
        <th class="c-yr">Year</th>
    </tr>
    <?php
    $subjects = is_array($s['ol_subjects'] ?? null) ? $s['ol_subjects'] : [];
    for ($i = 0; $i < 9; $i++):
        $row = $subjects[$i] ?? ['subject' => '', 'grade' => '', 'year' => ''];
    ?>
    <tr>
        <td class="c-no"><?php echo $i + 1; ?></td>
        <td class="c-sub"><?php echo $e($u($row['subject'] ?? '')); ?></td>
        <td class="c-gr"><?php echo $e($u($row['grade'] ?? '')); ?></td>
        <td class="c-yr"><?php echo $e($u($row['year'] ?? '')); ?></td>
    </tr>
    <?php endfor; ?>
</table>

<div class="sec">6. Educational qualifications — G.C.E. A/L / NVQ</div>
<table class="grid">
    <tr>
        <td class="lab">Index / Reg. No.</td>
        <td class="val"><?php echo $e($u($s['al_index'] ?? '')); ?></td>
        <td class="lab">Year of exam</td>
        <td class="val"><?php echo $e($u($s['al_year'] ?? '')); ?></td>
    </tr>
    <tr>
        <td class="lab lab-w">Stream / NVQ level</td>
        <td class="val val-w" colspan="3"><?php echo $e($u($s['al_stream'] ?? '')); ?></td>
    </tr>
    <tr>
        <td class="lab lab-w tall2">Subjects / results</td>
        <td class="val val-w" colspan="3"><?php echo $e($u($s['al_results'] ?? '')); ?></td>
    </tr>
</table>

<div class="sec">7. Declaration by applicant</div>
<div class="decl">
    I hereby declare that the particulars furnished in this application are true and correct to the best of my knowledge
    and belief. I understand that any false statement or omission may result in the rejection of this application or
    cancellation of enrolment at the <?php echo $e($u($instName)); ?>. I agree to abide by the rules, regulations,
    and code of conduct of the institute if selected.
</div>

<table class="signs">
    <tr>
        <td class="left">
            <div class="sign-box">
                <div class="sign-line"></div>
                <div class="sign-title">Signature of applicant</div>
                <div class="sign-meta">Name: <?php echo $e($u($s['name_initials'] ?? '')); ?></div>
                <div class="sign-meta">Date: <?php echo $e($u($s['date'] ?? '')); ?></div>
            </div>
        </td>
        <td class="right">
            <div class="sign-box">
                <div class="sign-line"></div>
                <div class="sign-title">Signature of parent / guardian</div>
                <div class="sign-meta">Name: <?php echo $e($u($s['guardian_name'] ?? '')); ?></div>
                <div class="sign-meta">Date: <?php echo $e($u($s['date'] ?? '')); ?></div>
            </div>
        </td>
    </tr>
</table>

<div class="office">
    <div class="office-h">For office use only</div>
    <div class="office-b">
        <table>
            <tr>
                <td style="width:50%;">Received on: ____________________________</td>
                <td style="width:50%;">Received by: ____________________________</td>
            </tr>
            <tr>
                <td>
                    Status:
                    <span class="opt"><span class="chk"></span> Accepted</span>
                    <span class="opt"><span class="chk"></span> Pending</span>
                    <span class="opt"><span class="chk"></span> Rejected</span>
                </td>
                <td>Remarks: ____________________________________</td>
            </tr>
            <tr>
                <td colspan="2" style="padding-top:1.5mm;">
                    Course allocated: ________________________________
                    &nbsp;&nbsp; Student ID: ____________________
                </td>
            </tr>
        </table>
    </div>
</div>

<div class="foot">
    <?php echo $e($u($instName)); ?> · STUDENT APPLICATION FORM
    <span class="pg">PAGE 2 OF 2</span>
</div>
</div>

</body>
</html>
