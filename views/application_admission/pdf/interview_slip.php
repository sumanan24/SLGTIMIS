<?php
/**
 * Interview invitation letter (personal slip PDF).
 *
 * @var array<string, mixed> $schedule
 * @var array<string, mixed> $entry
 * @var string $logo_src
 */
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');

$fmtDate = static function (?string $d): string {
    $ts = $d ? strtotime($d) : false;
    return $ts ? date('d F Y', $ts) : '__________________';
};
$fmtTime = static function (?string $t): string {
    if (!$t || trim($t) === '') {
        return '__________________';
    }
    $ts = strtotime($t);
    return $ts ? date('g:i A', $ts) : $t;
};

$name = trim((string) ($entry['student_full_name'] ?? ''));
$choiceInfo = is_array($interview_choice ?? null) ? $interview_choice : [];
$course = trim((string) ($choiceInfo['course_name'] ?? ''));
$choiceLabel = trim((string) ($choiceInfo['choice_label'] ?? ''));
$choiceNum = (int) ($choiceInfo['choice'] ?? 0);
if ($course === '') {
    if (class_exists('ApplicationAdmissionScheduleModel')) {
        $course = ApplicationAdmissionScheduleModel::courseNameFromEntry($entry);
    }
    if ($course === '') {
        $course = trim((string) ($entry['course_priority_1'] ?? ''));
    }
    if ($course === '') {
        $course = trim((string) ($schedule['course_name'] ?? ''));
    }
}
if ($choiceLabel === '' && $choiceNum >= 1 && $choiceNum <= 3 && class_exists('ApplicationAdmissionCutoffModel')) {
    $choiceLabel = ApplicationAdmissionCutoffModel::choiceOrdinal($choiceNum);
}

$interviewDate = $fmtDate($schedule['schedule_date'] ?? null);
$interviewTime = $fmtTime($schedule['start_time'] ?? null);
if (!empty($schedule['end_time'])) {
    $end = $fmtTime($schedule['end_time']);
    if ($end !== '__________________' && $interviewTime !== '__________________') {
        $interviewTime .= ' – ' . $end;
    }
}

$venue = trim((string) ($schedule['venue'] ?? ''));
if ($venue === '') {
    $venue = 'Sri Lanka – German Training Institute, Ariviyal Nagar, Kilinochchi';
}

$year = (string) ($schedule['schedule_date'] ?? '');
$year = preg_match('/^\d{4}/', $year, $ym) ? $ym[0] : date('Y');
$entryId = (int) ($entry['entry_id'] ?? 0);
$refSerial = $entryId > 0 ? str_pad((string) $entryId, 4, '0', STR_PAD_LEFT) : '________';
$refNo = 'SLGTI/ADM/' . $year . '/' . $refSerial;
$letterDate = date('d F Y');
$logoSrc = (string) ($logo_src ?? '');
$principalSig = trim((string) ($principal_sig_src ?? ''));
$principalName = trim((string) ($principal_name ?? 'R. Mathaan'));
if ($principalName === '') {
    $principalName = 'R. Mathaan';
}
$entranceExamDateLong = '19th July 2026';
?>
<style>
.iv-letter { font-family: DejaVu Sans, Helvetica, Arial, sans-serif; font-size: 9.5pt; color: #111; line-height: 1.35; }
.iv-head { text-align: center; margin: 0 0 2mm 0; }
.iv-logo { height: 11mm; width: auto; display: block; margin: 0 auto 1.5mm auto; }
.iv-inst { font-size: 11pt; font-weight: 700; letter-spacing: 0.02em; text-transform: uppercase; }
.iv-addr { font-size: 9pt; margin-top: 0.5mm; }
.iv-rule { border: none; border-top: 1pt solid #111; margin: 2mm 0 3mm 0; }
.iv-meta { width: 100%; border-collapse: collapse; margin: 0 0 2.5mm 0; }
.iv-meta td { border: none; padding: 0; vertical-align: top; font-size: 9.5pt; }
.iv-meta td.right { text-align: right; }
.iv-title { text-align: center; font-size: 11pt; font-weight: 700; text-transform: uppercase; margin: 0 0 3mm 0; letter-spacing: 0.02em; }
.iv-dear { font-weight: 700; margin: 0 0 2mm 0; }
.iv-p { margin: 0 0 2mm 0; text-align: justify; font-size: 9.5pt; line-height: 1.35; }
.iv-details { width: 100%; border-collapse: collapse; margin: 1mm 0 2.5mm 0; }
.iv-details th, .iv-details td { border: none; padding: 0.8mm 0; text-align: left; vertical-align: top; font-size: 9.5pt; }
.iv-details th { width: 30%; font-weight: 700; }
.iv-choices { width: 100%; border-collapse: collapse; }
.iv-choices td { border: 0.5pt solid #bbb; padding: 0.8mm 1.5mm; font-size: 9pt; vertical-align: middle; }
.iv-ch-ord { width: 22%; }
.iv-ch-flag { width: 20%; text-align: center; font-size: 8pt; letter-spacing: 0.02em; text-transform: uppercase; }
.iv-ch-on { background-color: #fff3cd; font-weight: 700; }
.iv-h { font-size: 10pt; font-weight: 700; margin: 2.5mm 0 1mm 0; }
.iv-ul { margin: 0 0 2mm 4mm; padding: 0; font-size: 9.5pt; }
.iv-ul li { margin: 0 0 0.8mm 0; }
.iv-sign { margin-top: 4mm; }
.iv-sign-line { margin: 0 0 0.5mm 0; }
.iv-sign-img { height: 7mm; width: auto; max-width: 28mm; display: block; margin: 0.3mm 0 0 0; }
.iv-sign-name { font-weight: 700; margin: 1.5mm 0 0 0; }
.iv-sign-role { font-weight: 700; margin: 0; }
.iv-sign-org { margin: 0; font-size: 9pt; }
</style>

<div class="iv-letter">
    <div class="iv-head">
        <?php if ($logoSrc !== ''): ?>
        <img class="iv-logo" src="<?php echo $e($logoSrc); ?>" alt="SLGTI">
        <?php endif; ?>
        <div class="iv-inst">Sri Lanka – German Training Institute (SLGTI)</div>
        <div class="iv-addr">Ariviyal Nagar, Kilinochchi</div>
    </div>
    <hr class="iv-rule">

    <table class="iv-meta">
        <tr>
            <td><strong>Ref. No.:</strong> <?php echo $e($refNo); ?></td>
            <td class="right"><strong>Date:</strong> <?php echo $e($letterDate); ?></td>
        </tr>
    </table>

    <div class="iv-title">Invitation for the Selection Interview – <?php echo $e($year); ?> Intake</div>

    <p class="iv-dear">Dear Applicant,</p>

    <p class="iv-p">
        With reference to the application made by you for the enrollment to a course at the
        <strong>Sri Lanka – German Training Institute (SLGTI)</strong>, we are pleased to inform you
        that you have <strong>qualified the entrance examination held on <?php echo $e($entranceExamDateLong); ?></strong>
        <?php if ($choiceLabel !== '' && $course !== ''): ?>
        and are invited for the selection interview for your <strong><?php echo $e($choiceLabel); ?></strong> course
        <strong><?php echo $e($course); ?></strong>
        <?php elseif ($course !== ''): ?>
        and are invited for the selection interview for the course <strong><?php echo $e($course); ?></strong>
        <?php else: ?>
        and are invited for the selection interview
        <?php endif; ?>
        for the <?php echo $e($year); ?> Intake.
    </p>

    <p class="iv-p">You are kindly requested to attend the interview according to the following details:</p>

    <table class="iv-details">
        <tr>
            <th>Applicant Name:</th>
            <td><?php echo $name !== '' ? $e($name) : '____________________________________'; ?></td>
        </tr>
        <tr>
            <th>Course/Programme:</th>
            <td><?php echo $course !== '' ? $e($course) : '__________________________________'; ?></td>
        </tr>
        <tr>
            <th>Course choices:</th>
            <td><?php require BASE_PATH . '/views/application_admission/pdf/_interview_choices.php'; ?></td>
        </tr>
        <tr>
            <th>Interview Date:</th>
            <td><?php echo $e($interviewDate); ?></td>
        </tr>
        <tr>
            <th>Interview Time:</th>
            <td><?php echo $e($interviewTime); ?></td>
        </tr>
        <tr>
            <th>Venue:</th>
            <td><?php echo $e($venue); ?></td>
        </tr>
    </table>

    <div class="iv-h">Documents to Bring</div>
    <p class="iv-p">
        Please bring the <strong>original NIC, Birth Certificate, G.C.E. (O/L), G.C.E. (A/L), NVQ certificates, and any other relevant certificates</strong>
        for verification.
    </p>

    <div class="iv-h">Dress Code</div>
    <ul class="iv-ul">
        <li><strong>Male Applicants:</strong> White formal shirt, black trousers/jeans, formal shoes, neat professional haircut, and a clean-shaven</li>
        <li>
            <strong>Female Applicants:</strong> White blouse, black skirt/formal black jeans or trousers and formal shoes.
            <strong>Muslim female applicants may wear a black Abaya with a black or white Hijab.</strong>
        </li>
    </ul>

    <p class="iv-p">
        Arrive <strong>15 minutes before</strong> the scheduled time. Maintain a neat, clean and professional appearance.
        <strong>Being called for an interview does not guarantee admission.</strong>
        Final selection will follow the applicable admission criteria.
    </p>

    <p class="iv-p">We wish you every success in the interview and selection process.</p>

    <div class="iv-sign">
        <p class="iv-sign-line"><strong>Regards,</strong></p>
        <?php if ($principalSig !== ''): ?>
        <img class="iv-sign-img" src="<?php echo $e($principalSig); ?>" alt="<?php echo $e($principalName); ?>">
        <?php else: ?>
        <p class="iv-sign-line">..........................................................</p>
        <?php endif; ?>
        <p class="iv-sign-name"><?php echo $e($principalName); ?></p>
        <p class="iv-sign-role">Branch Principal</p>
        <p class="iv-sign-org">Sri Lanka – German Training Institute (SLGTI)</p>
        <p class="iv-sign-org">Ariviyal Nagar, Kilinochchi</p>
    </div>
</div>
