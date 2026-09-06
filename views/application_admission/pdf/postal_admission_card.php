<?php
/**
 * SLGTI Selection Examination / Interview — Postal Admission Card (A4, foldable).
 *
 * @var array<string, mixed> $schedule
 * @var array<string, mixed> $entry
 * @var array{name: string, address: string, city_line: string, phone?: string} $mailing
 * @var string $cardTitle
 * @var string $cardSubtitle
 * @var bool $isInterview
 * @var string $logo_src
 */
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');

$fmtDate = static function (?string $d): string {
    $ts = $d ? strtotime($d) : false;
    return $ts ? date('d.m.Y', $ts) : '—';
};

$fmtTime = static function (?string $t): string {
    if (!$t || trim($t) === '') {
        return '—';
    }
    $ts = strtotime($t);
    return $ts ? date('g:i A', $ts) : $t;
};

$mailName = trim((string) ($mailing['name'] ?? $entry['student_full_name'] ?? ''));
$mailAddress = trim((string) ($mailing['address'] ?? $entry['student_address'] ?? ''));
$mailCity = trim((string) ($mailing['city_line'] ?? ''));
$isInterview = !empty($isInterview);
$roll = $isInterview ? '' : trim((string) ($entry['roll_number'] ?? ''));
$nic = trim((string) ($entry['student_nic'] ?? ''));
$name = trim((string) ($entry['student_full_name'] ?? ''));
$nvq = trim((string) ($schedule['application_level'] ?? ''));

$instructions = ApplicationAdmissionPdfHelper::defaultExamInstructions($isInterview);
$scheduleInstructions = trim((string) ($schedule['instructions'] ?? ''));
if (mb_strlen($scheduleInstructions) > 120) {
    $scheduleInstructions = rtrim(mb_substr($scheduleInstructions, 0, 117)) . '…';
}

$examCentre = trim((string) ($schedule['venue'] ?? ''));
if ($isInterview && $examCentre === '') {
    $examCentre = 'Sri Lanka – German Training Institute, Ariviyal Nagar, Kilinochchi';
}
$examTitle = trim((string) ($schedule['title'] ?? $cardSubtitle ?? ''));
$courseLabel = trim((string) ($entry['course_priority_1'] ?? ''));
if (class_exists('ApplicationAdmissionScheduleModel')) {
    $resolved = ApplicationAdmissionScheduleModel::courseNameFromEntry($entry);
    if ($resolved !== '') {
        $courseLabel = $resolved;
    }
}

$timeStart = $fmtTime($schedule['start_time'] ?? null);
$timeEnd = !empty($schedule['end_time']) ? $fmtTime($schedule['end_time']) : '';
$timeCell = $timeEnd !== '' ? ($timeStart . ' – ' . $timeEnd) : $timeStart;

$allowText = $isInterview
    ? 'This candidate is allowed to attend this interview.'
    : 'This candidate is allowed to sit for this selection examination.';

$postFrom = ApplicationAdmissionPdfHelper::institutePostFrom();
$examYear = (string) ($schedule['schedule_date'] ?? '');
$examYear = preg_match('/^\d{4}/', $examYear, $ym) ? $ym[0] : date('Y');

$bannerTitle = $isInterview
    ? 'INTERVIEW INVITATION — ' . $examYear . ' INTAKE'
    : 'SELECTION EXAMINATION ' . $examYear . ' — ADMISSION CARD';

$docTitle = $cardTitle ?? ($isInterview
    ? 'INTERVIEW INVITATION — ' . $examYear . ' INTAKE'
    : 'SELECTION EXAMINATION — ADMISSION CARD');

$logoSrc = (string) ($logo_src ?? '');
$refNo = $isInterview
    ? ('SLGTI/ADM/' . $examYear . '/' . str_pad((string) ((int) ($entry['entry_id'] ?? 0)), 4, '0', STR_PAD_LEFT))
    : ApplicationAdmissionPdfHelper::admissionCardReference(
        (int) ($schedule['schedule_id'] ?? 0),
        $roll,
        (int) ($entry['entry_id'] ?? 0)
    );
$issuedDate = date('d.m.Y');
$letterDateLong = date('d F Y');
$interviewDateLong = (static function (?string $d): string {
    $ts = $d ? strtotime($d) : false;
    return $ts ? date('d F Y', $ts) : '—';
})($schedule['schedule_date'] ?? null);

$instrHalf = (int) ceil(count($instructions) / 2);
$instrLeft = array_slice($instructions, 0, $instrHalf);
$instrRight = array_slice($instructions, $instrHalf);

$medium = trim((string) ($schedule['application_level'] ?? '')) === '05'
    ? 'English'
    : trim((string) ($schedule['student_language'] ?? $entry['student_language'] ?? ''));
$attendTitle = $examTitle !== '' ? $examTitle : ($courseLabel !== '' ? $courseLabel : '—');
$centreNote = $isInterview ? 'interview' : 'examination';
$dateLabel = $isInterview ? 'INTERVIEW DATE' : 'EXAMINATION DATE';
$venueLabel = $isInterview ? 'INTERVIEW VENUE' : 'EXAMINATION CENTRE';
$principalSig = trim((string) ($principal_sig_src ?? ''));
$principalName = trim((string) ($principal_name ?? 'R. Mathaan'));
if ($principalName === '') {
    $principalName = 'R. Mathaan';
}
?>
<div class="adm-page">
<table class="adm-sheet" width="100%" cellspacing="0" cellpadding="0">
<tr>
<td class="adm-side" width="20">&nbsp;</td>
<td class="adm-main" width="100%">

    <div class="adm-banner"><?php echo $e($bannerTitle); ?></div>

    <table class="adm-postbox" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td class="adm-from" width="40%">
                <div class="adm-label">POST FROM</div>
                <div class="adm-strong"><?php echo $e($postFrom['name']); ?></div>
                <div class="adm-text"><?php echo $e($postFrom['address']); ?></div>
                <?php if (trim((string) ($postFrom['phone'] ?? '')) !== ''): ?>
                <div class="adm-text"><strong>Phone:</strong> <?php echo $e($postFrom['phone']); ?></div>
                <?php endif; ?>
            </td>
            <td class="adm-to" width="60%">
                <div class="adm-label">POST TO</div>
                <?php if ($roll !== ''): ?>
                <div class="adm-roll"><?php echo $e($roll); ?></div>
                <?php endif; ?>
                <div class="adm-strong"><?php echo $mailName !== '' ? $e($mailName) : '—'; ?></div>
                <?php if ($mailAddress !== ''): ?>
                <div class="adm-text"><?php echo nl2br($e($mailAddress)); ?></div>
                <?php endif; ?>
                <?php if ($mailCity !== ''): ?>
                <div class="adm-text"><?php echo $e($mailCity); ?></div>
                <?php endif; ?>
            </td>
        </tr>
    </table>
    <div class="adm-foldhint">Fold with <strong>Post to</strong> on the outside; Post from stays top-left when posted.</div>
    <div class="adm-fold">— Fold here —</div>

    <?php if ($isInterview): ?>
    <?php /* Interview invitation letter body — postal header above stays unchanged */ ?>
    <table class="adm-header" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td class="adm-hmid" width="68%">
                <div class="adm-institute">Sri Lanka – German Training Institute (SLGTI)</div>
                <div class="adm-examline">Ariviyal Nagar, Kilinochchi</div>
            </td>
            <td class="adm-hmeta" width="32%">
                <?php if ($logoSrc !== ''): ?>
                <img class="adm-logo" src="<?php echo $e($logoSrc); ?>" alt="SLGTI" />
                <?php endif; ?>
                <div class="adm-meta-l">Ref. No.</div>
                <div class="adm-meta-v"><?php echo $e($refNo); ?></div>
                <div class="adm-meta-l adm-meta-gap">Date</div>
                <div class="adm-meta-v"><?php echo $e($letterDateLong); ?></div>
            </td>
        </tr>
    </table>

    <div class="iv-body">
        <div class="iv-body-title">Interview Invitation – <?php echo $e($examYear); ?> Intake</div>

        <p class="iv-body-dear">Dear Applicant,</p>

        <p class="iv-body-p">
            With reference to your application for admission to a course at the
            <strong>Sri Lanka – German Training Institute (SLGTI)</strong>, we are pleased to inform you
            that you have been <strong>shortlisted for an interview for the <?php echo $e($examYear); ?> Intake</strong>.
        </p>

        <p class="iv-body-p">You are kindly requested to attend the interview according to the following details:</p>

        <table class="iv-body-details" width="100%" cellspacing="0" cellpadding="0">
            <tr>
                <th>Applicant Name:</th>
                <td><?php echo $name !== '' ? $e($name) : '—'; ?></td>
            </tr>
            <tr>
                <th>Course/Programme:</th>
                <td><?php echo $courseLabel !== '' ? $e($courseLabel) : '—'; ?></td>
            </tr>
            <tr>
                <th>Interview Date:</th>
                <td><?php echo $e($interviewDateLong); ?></td>
            </tr>
            <tr>
                <th>Interview Time:</th>
                <td><?php echo $e($timeCell); ?></td>
            </tr>
            <tr>
                <th>Venue:</th>
                <td><?php echo $e($examCentre); ?></td>
            </tr>
        </table>

        <div class="iv-body-h">Documents to Bring</div>
        <p class="iv-body-p">
            Please bring the <strong>original NIC, Birth Certificate, and relevant educational/NVQ certificates</strong>
            for verification.
        </p>

        <div class="iv-body-h">Dress Code</div>
        <ul class="iv-body-ul">
            <li><strong>Male Applicants:</strong> White shirt, black jeans/trousers and formal shoes.</li>
            <li>
                <strong>Female Applicants:</strong> White blouse, black skirt/formal black jeans or trousers and formal shoes.
                <strong>Muslim female applicants may wear a black Abaya with a black or white Hijab.</strong>
            </li>
        </ul>

        <p class="iv-body-p">
            Applicants are requested to arrive <strong>15 minutes before the scheduled interview time</strong>
            and maintain a neat, clean and professional appearance.
        </p>

        <p class="iv-body-p">
            Please note that <strong>being called for an interview does not guarantee admission</strong>.
            Final selection will be made in accordance with the applicable admission criteria and selection process.
        </p>

        <p class="iv-body-p">We wish you every success in the interview and selection process.</p>

        <div class="iv-body-sign">
            <div><strong>Regards,</strong></div>
            <?php if ($principalSig !== ''): ?>
            <img class="iv-body-sign-img" src="<?php echo $e($principalSig); ?>" alt="<?php echo $e($principalName); ?>" />
            <?php else: ?>
            <div>..........................................................</div>
            <?php endif; ?>
            <div class="iv-body-sign-name"><?php echo $e($principalName); ?></div>
            <div class="iv-body-sign-role">Branch Principal</div>
            <div class="iv-body-sign-org">Sri Lanka – German Training Institute (SLGTI)</div>
            <div class="iv-body-sign-org">Ariviyal Nagar, Kilinochchi</div>
        </div>
    </div>

    <?php else: ?>

    <table class="adm-header" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td class="adm-hmid" width="68%">
                <div class="adm-institute">Sri Lanka German Training Institute</div>
                <div class="adm-doctitle"><?php echo $e($docTitle); ?></div>
                <?php if ($examTitle !== ''): ?>
                <div class="adm-examline"><?php echo $e($examTitle); ?></div>
                <?php endif; ?>
            </td>
            <td class="adm-hmeta" width="32%">
                <?php if ($logoSrc !== ''): ?>
                <img class="adm-logo" src="<?php echo $e($logoSrc); ?>" alt="SLGTI" />
                <?php endif; ?>
                <div class="adm-meta-l">Ref. No.</div>
                <div class="adm-meta-v"><?php echo $e($refNo); ?></div>
                <div class="adm-meta-l adm-meta-gap">Issued</div>
                <div class="adm-meta-v"><?php echo $e($issuedDate); ?></div>
            </td>
        </tr>
    </table>

    <div class="adm-sec">1. CANDIDATE PARTICULARS</div>
    <table class="adm-grid" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <th width="32%">INDEX / ROLL NUMBER</th>
            <td width="68%" class="adm-mono"><?php echo $roll !== '' ? $e($roll) : '—'; ?></td>
        </tr>
        <tr>
            <th>NIC / PASSPORT / DRIVING LICENSE NO.</th>
            <td><?php echo $nic !== '' ? $e($nic) : '—'; ?></td>
        </tr>
        <tr>
            <th>NAME (WITH INITIALS)</th>
            <td><?php echo $name !== '' ? $e($name) : '—'; ?></td>
        </tr>
    </table>
    <table class="adm-grid adm-grid-split" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <th width="18%">NVQ LEVEL</th>
            <td width="12%"><?php echo $nvq !== '' ? $e($nvq) : '—'; ?></td>
            <th width="22%">COURSE / SUBJECT</th>
            <td width="48%"><?php echo $courseLabel !== '' ? $e($courseLabel) : '—'; ?></td>
        </tr>
    </table>
    <table class="adm-grid" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <th width="32%">MEDIUM OF INSTRUCTION</th>
            <td width="68%"><?php echo $medium !== '' ? $e($medium) : '—'; ?></td>
        </tr>
    </table>
    <table class="adm-grid adm-grid-split" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <th width="22%"><?php echo $e($dateLabel); ?></th>
            <td width="28%"><?php echo $e($fmtDate($schedule['schedule_date'] ?? null)); ?></td>
            <th width="12%">TIME</th>
            <td width="38%"><?php echo $e($timeCell); ?></td>
        </tr>
    </table>
    <table class="adm-grid" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <th width="32%"><?php echo $e($venueLabel); ?></th>
            <td width="68%"><?php echo $examCentre !== '' ? $e($examCentre) : '—'; ?></td>
        </tr>
    </table>
    <div class="adm-allow"><?php echo $e($allowText); ?></div>

    <div class="adm-sec">2. INSTRUCTIONS TO CANDIDATES</div>
    <table class="adm-instr" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td width="50%">
                <ol start="1">
                    <?php foreach ($instrLeft as $line): ?>
                    <li><?php echo $e($line); ?></li>
                    <?php endforeach; ?>
                </ol>
            </td>
            <td width="50%">
                <?php if ($instrRight !== []): ?>
                <ol start="<?php echo $instrHalf + 1; ?>">
                    <?php foreach ($instrRight as $line): ?>
                    <li><?php echo $e($line); ?></li>
                    <?php endforeach; ?>
                </ol>
                <?php endif; ?>
            </td>
        </tr>
    </table>
    <?php if ($scheduleInstructions !== ''): ?>
    <div class="adm-extra"><strong>Additional:</strong> <?php echo nl2br($e($scheduleInstructions)); ?></div>
    <?php endif; ?>

    <div class="adm-sec">3. CERTIFICATION</div>
    <div class="adm-cert">
        <p>I hereby certify that the applicant named above signed this admission card in my presence. To the best of my knowledge the particulars given are true and correct. Issued as Grama Niladhari / Justice of the Peace / Gazetted Government Officer / Principal / Head of a Government Institution.</p>
        <table class="adm-sig" width="100%" cellspacing="0" cellpadding="0">
            <tr>
                <td width="70%">
                    <div class="adm-sgap">&nbsp;</div>
                    <div class="adm-sline"></div>
                    <div class="adm-scap">Applicant&apos;s signature</div>
                </td>
                <td width="30%">
                    <div class="adm-sgap">&nbsp;</div>
                    <div class="adm-sline"></div>
                    <div class="adm-scap">Date</div>
                </td>
            </tr>
        </table>
        <table class="adm-sig adm-sig2" width="100%" cellspacing="0" cellpadding="0">
            <tr>
                <td width="34%">
                    <div class="adm-sgap">&nbsp;</div>
                    <div class="adm-sline"></div>
                    <div class="adm-scap">Signature of certifying officer</div>
                </td>
                <td width="42%">
                    <div class="adm-sgap">&nbsp;</div>
                    <div class="adm-sline"></div>
                    <div class="adm-scap">Name and designation (Official Rubber Stamp)</div>
                </td>
                <td width="24%">
                    <div class="adm-sgap">&nbsp;</div>
                    <div class="adm-sline"></div>
                    <div class="adm-scap">Date</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="adm-sec">4. EXAMINATION ATTENDANCE</div>
    <table class="adm-att" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <th width="50%">EXAMINATION TITLE</th>
            <th width="25%">CANDIDATE&apos;S SIGNATURE</th>
            <th width="25%">INVIGILATOR&apos;S SIGNATURE</th>
        </tr>
        <tr>
            <td><?php echo $e($attendTitle); ?></td>
            <td class="adm-asig">&nbsp;</td>
            <td class="adm-asig">&nbsp;</td>
        </tr>
    </table>

    <div class="adm-note">Bring this admission card and your original NIC / Passport / Driving License to the <?php echo $e($centreNote); ?> centre.</div>

    <?php endif; ?>

</td>
<td class="adm-side" width="20">&nbsp;</td>
</tr>
</table>
</div>
