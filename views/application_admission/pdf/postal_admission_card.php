<?php
/** @var array<string, mixed> $schedule */
/** @var array<string, mixed> $entry */
/** @var array{name: string, address: string, city_line: string} $mailing */
/** @var string $cardTitle */
/** @var string $cardSubtitle */
/** @var bool $isInterview */
/** @var string $logo_src */
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
$roll = trim((string) ($entry['roll_number'] ?? ''));
$isInterview = !empty($isInterview);
$instructions = ApplicationAdmissionPdfHelper::defaultExamInstructions($isInterview);
$scheduleInstructions = trim((string) ($schedule['instructions'] ?? ''));
$examCentre = trim((string) ($schedule['venue'] ?? ''));
$examTitle = trim((string) ($schedule['title'] ?? $cardSubtitle ?? ''));
$timeCell = $fmtTime($schedule['start_time'] ?? null);
if (!empty($schedule['end_time'])) {
    $timeCell .= ' – ' . $fmtTime($schedule['end_time']);
}
$allowText = $isInterview
    ? 'This candidate is allowed to attend this interview.'
    : 'This candidate is allowed to sit for this selection examination.';
$postFrom = ApplicationAdmissionPdfHelper::institutePostFrom();
$examYear = (string) ($schedule['schedule_date'] ?? '');
$examYear = preg_match('/^\d{4}/', $examYear, $ym) ? $ym[0] : date('Y');
$cornerLabel = $isInterview
    ? 'Interview ' . $examYear . ' — Admission Card'
    : 'Selection Examination ' . $examYear . ' — Admission Card';
$logoSrc = (string) ($logo_src ?? '');
$scheduleId = (int) ($schedule['schedule_id'] ?? 0);
$entryId = (int) ($entry['entry_id'] ?? 0);
$refNo = ApplicationAdmissionPdfHelper::admissionCardReference($scheduleId, $roll, $entryId);
$issuedDate = date('d.m.Y');
$instrHalf = (int) ceil(count($instructions) / 2);
$instrCol1 = array_slice($instructions, 0, $instrHalf);
$instrCol2 = array_slice($instructions, $instrHalf);
?>
<div class="postal-card-page">
    <table class="admission-sheet">
        <tr>
            <td class="sheet-postal">
                <div class="postal-zone-title"><?php echo $e($cornerLabel); ?></div>
                <table class="mail-envelope">
                    <tr>
                        <td class="mail-from">
                            <div class="mail-label">Post from</div>
                            <div class="mail-from-name"><?php echo $e($postFrom['name']); ?></div>
                            <div class="mail-from-address"><?php echo $e($postFrom['address']); ?></div>
                            <?php if (trim((string) ($postFrom['phone'] ?? '')) !== ''): ?>
                            <div class="mail-from-phone"><?php echo $e($postFrom['phone']); ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="mail-to">
                            <div class="mail-label">Post to</div>
                            <div class="mail-name"><?php echo $mailName !== '' ? $e($mailName) : '—'; ?></div>
                            <?php if ($mailAddress !== ''): ?>
                            <div class="mail-address"><?php echo nl2br($e($mailAddress)); ?></div>
                            <?php endif; ?>
                            <?php if ($mailCity !== ''): ?>
                            <div class="mail-city"><?php echo $e($mailCity); ?></div>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                <div class="fold-hint">Fold with <strong>Post to</strong> on the outside; <strong>Post from</strong> stays top-left when posted.</div>
            </td>
        </tr>
        <tr>
            <td class="sheet-fold">— — — Fold here — — —</td>
        </tr>
        <tr>
            <td class="sheet-body">
                <table class="body-layout">
                    <tr>
                        <td class="body-content">
                            <table class="head-row">
                                <tr>
                                    <td class="head-left">
                                        <div class="inst">Sri Lanka German Training Institute</div>
                                        <div class="doc-title"><?php echo $e($cardTitle ?? ($isInterview ? 'Interview — Admission Card' : 'Selection Examination — Admission Card')); ?></div>
                                        <?php if ($examTitle !== ''): ?>
                                        <div class="doc-sub"><?php echo $e($examTitle); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="head-right">
                                        <?php if ($logoSrc !== ''): ?>
                                        <img class="logo-img" src="<?php echo $e($logoSrc); ?>" alt="SLGTI" />
                                        <?php endif; ?>
                                        <div class="ref-block">
                                            <div class="ref-line"><span class="ref-label">Ref. No.</span> <?php echo $e($refNo); ?></div>
                                            <div class="ref-line"><span class="ref-label">Issued</span> <?php echo $e($issuedDate); ?></div>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                            <div class="header-rule"></div>

                            <div class="section-title">1. Candidate particulars</div>
                            <table class="info">
                                <colgroup>
                                    <col class="col-label">
                                    <col class="col-value">
                                    <col class="col-label">
                                    <col class="col-value">
                                </colgroup>
                                <tr>
                                    <th>Index / Roll number</th>
                                    <td class="mono"><?php echo $roll !== '' ? $e($roll) : '—'; ?></td>
                                    <th>NIC / Passport / Driving License No.</th>
                                    <td><?php echo $e($entry['student_nic'] ?? ''); ?></td>
                                </tr>
                                <tr>
                                    <th>Name (with initials)</th>
                                    <td colspan="3"><?php echo $e($entry['student_full_name'] ?? ''); ?></td>
                                </tr>
                                <tr>
                                    <th>NVQ Level</th>
                                    <td><?php echo $e($schedule['application_level'] ?? ''); ?></td>
                                    <th>Course / Subject</th>
                                    <td><?php echo $e($entry['course_priority_1'] ?? ''); ?></td>
                                </tr>
                                <?php if (!empty($schedule['student_language'])): ?>
                                <tr>
                                    <th>Medium of instruction</th>
                                    <td colspan="3"><?php echo $e($schedule['student_language']); ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <th><?php echo $isInterview ? 'Interview date' : 'Examination date'; ?></th>
                                    <td><?php echo $e($fmtDate($schedule['schedule_date'] ?? null)); ?></td>
                                    <th>Time</th>
                                    <td><?php echo $e($timeCell); ?></td>
                                </tr>
                                <tr>
                                    <th><?php echo $isInterview ? 'Interview venue' : 'Examination centre'; ?></th>
                                    <td colspan="3"><?php echo $examCentre !== '' ? $e($examCentre) : '—'; ?></td>
                                </tr>
                            </table>

                            <div class="allow-block"><?php echo $e($allowText); ?></div>

                            <div class="section-title">2. Instructions to candidates</div>
                            <table class="instr-cols">
                                <tr>
                                    <td class="instr-col">
                                        <ol class="instr-list" start="1">
                                            <?php foreach ($instrCol1 as $line): ?>
                                            <li><?php echo $e($line); ?></li>
                                            <?php endforeach; ?>
                                        </ol>
                                    </td>
                                    <td class="instr-col">
                                        <?php if ($instrCol2 !== []): ?>
                                        <ol class="instr-list" start="<?php echo $instrHalf + 1; ?>">
                                            <?php foreach ($instrCol2 as $line): ?>
                                            <li><?php echo $e($line); ?></li>
                                            <?php endforeach; ?>
                                        </ol>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </table>
                            <?php if ($scheduleInstructions !== ''): ?>
                            <div class="instr-additional"><strong>Additional instructions:</strong> <?php echo nl2br($e($scheduleInstructions)); ?></div>
                            <?php endif; ?>

                            <div class="section-title">3. Certification</div>
                            <div class="cert-block">
                                <div class="certified-by-body">I hereby certify that the applicant, whose details appear below, signed this application in my presence. To the best of my knowledge, the information provided is true and correct. This certification is issued in my official capacity as a Grama Niladhari, Justice of the Peace, Gazetted Government Officer, Principal, or Head of a Government Institution.</div>
                                <table class="footer-sig-row sig-row-applicant">
                                    <tr>
                                        <td class="footer-applicant">
                                            <div class="footer-sig-space">&nbsp;</div>
                                            <div class="footer-sig-line"></div>
                                            <div class="footer-sig-caption">Applicant&apos;s signature</div>
                                        </td>
                                        <td class="footer-date">
                                            <div class="footer-sig-space">&nbsp;</div>
                                            <div class="footer-sig-line"></div>
                                            <div class="footer-sig-caption">Date</div>
                                        </td>
                                    </tr>
                                </table>
                                <table class="footer-sig-row sig-row-officer">
                                    <tr>
                                        <td class="footer-cert-sig">
                                            <div class="footer-sig-space">&nbsp;</div>
                                            <div class="footer-sig-line"></div>
                                            <div class="footer-sig-caption">Signature of certifying officer</div>
                                        </td>
                                        <td class="footer-cert-name">
                                            <div class="footer-sig-space">&nbsp;</div>
                                            <div class="footer-sig-line"></div>
                                            <div class="footer-sig-caption">Name and designation (Official Rubber Stamp)</div>
                                        </td>
                                        <td class="footer-cert-date">
                                            <div class="footer-sig-space">&nbsp;</div>
                                            <div class="footer-sig-line"></div>
                                            <div class="footer-sig-caption">Date</div>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <div class="section-title section-title-exam-attendance">4. Examination attendance</div>
                            <table class="grid grid-attendance">
                                <thead>
                                    <tr>
                                        <th class="col-title">Examination title</th>
                                        <th class="col-sig">Candidate&apos;s signature</th>
                                        <th class="col-sig">Invigilator&apos;s signature</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="td-left"><?php echo $examTitle !== '' ? $e($examTitle) : $e($entry['course_priority_1'] ?? '—'); ?></td>
                                        <td class="sig-cell">&nbsp;</td>
                                        <td class="sig-cell">&nbsp;</td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="body-fill">&nbsp;</td>
                    </tr>
                    <tr>
                        <td class="body-foot">
                            <div class="gov-footer">Bring this admission card and your original NIC / Passport / Driving License to the <?php echo $isInterview ? 'interview' : 'examination'; ?> centre.</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>
