<?php
/**
 * SLGTI Admission Form — 2 pages A4 (front + back).
 */
$e = static function (?string $s): string {
    return htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
};
$logoSrc = (string) ($logo_src ?? '');
$principalSig = $principal_sig_src ?? null;
$principalName = trim((string) ($principal_name ?? 'R.Mathaan'));
if ($principalName === '') {
    $principalName = 'R.Mathaan';
}
$displayName = (string) ($student['display_name'] ?? $student['student_ininame'] ?? $student['student_fullname'] ?? '');
$indexNumber = trim((string) ($student['exam_roll_number'] ?? ''));
if ($indexNumber === '') {
    $indexNumber = (string) ($student['student_id'] ?? '');
}
$subjectShort = (string) ($meta['subject_short'] ?? $meta['subject_line'] ?? '');
$attendanceRows = 10;
$examRules = $exam_rules ?? [];
if (!is_array($examRules) || $examRules === []) {
    $examRules = [
        'Report to the examination centre at least 30 minutes before the scheduled time of each paper.',
        'Bring this admission form and the original National Identity Card (NIC) for verification.',
        'No candidate will be admitted to the examination hall after the paper has commenced.',
        'Mobile phones, smart watches, and unauthorised materials are strictly prohibited in the examination hall.',
        'Candidates must follow all instructions given by the supervisor and invigilator.',
        'Any malpractice, impersonation, or misconduct will result in immediate disqualification.',
    ];
}

$renderHead = static function () use ($e, $logoSrc, $meta): void {
    ?>
    <table class="adm-header" width="100%" cellpadding="0" cellspacing="0">
      <tr>
        <td class="adm-header-logo" align="left">
          <?php if ($logoSrc !== ''): ?>
            <img class="adm-logo" src="<?php echo $e($logoSrc); ?>" alt="SLGTI" />
          <?php endif; ?>
        </td>
      </tr>
      <tr>
        <td class="adm-header-text" align="center">
          <p class="adm-inst" align="center">Sri Lanka German Training Institute</p>
          <p class="adm-title" align="center">Examination Admission Form</p>
          <p class="adm-sub" align="center"><?php echo $e($meta['subtitle'] ?? ''); ?></p>
        </td>
      </tr>
    </table>
    <?php
};

$sectionOpen = static function (string $title) use ($e): void {
    echo '<div class="section-block"><div class="section-bar">' . $e($title) . '</div>';
};

$sectionClose = static function (): void {
    echo '</div>';
};
?>
<div class="admission-student">

  <div class="adm-page adm-page-1">
    <table class="page-shell" width="100%" cellpadding="0" cellspacing="0">
      <tr><td class="shell-cell"><?php $renderHead(); ?></td></tr>

      <tr>
        <td class="shell-cell">
          <?php $sectionOpen('Part A – Candidate and Module Details'); ?>
          <div class="card card-attached part-a-box">
            <table class="part-a-table" width="100%" cellpadding="0" cellspacing="0">
              <colgroup>
                <col class="pa-col-label" />
                <col class="pa-col-value" />
              </colgroup>
              <tr>
                <td class="pa-label">1. Index Number :</td>
                <td class="pa-value pa-value-id"><?php echo $e($indexNumber); ?></td>
              </tr>
              <tr>
                <td class="pa-label">2. N.I.C. No. :</td>
                <td class="pa-value"><?php echo $e($student['student_nic'] ?? ''); ?></td>
              </tr>
              <tr>
                <td class="pa-label">3. Name (with Initials) :</td>
                <td class="pa-value pa-value-name"><?php echo $e($displayName); ?></td>
              </tr>
              <tr>
                <td class="pa-label">4. Subject :</td>
                <td class="pa-value"><?php echo $e($subjectShort); ?></td>
              </tr>
              <tr>
                <td class="pa-label">5. Examination Centre :</td>
                <td class="pa-value"><?php echo $e($meta['exam_centre'] ?? ''); ?></td>
              </tr>
              <tr>
                <td class="pa-label">6. NVQ Level and Semester :</td>
                <td class="pa-value"><?php echo $e($meta['nvq_semester'] ?? ''); ?></td>
              </tr>
            </table>
          </div>
          <?php $sectionClose(); ?>
        </td>
      </tr>

      <tr>
        <td class="shell-cell">
          <div class="card auth-card">
            <table class="auth-row" width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td class="auth-msg">
                  <p class="allow-text">This candidate is allowed to sit for this written examination.</p>
                </td>
                <td class="auth-principal">
                  <div class="principal-panel">
                    <?php if ($principalSig): ?>
                      <img class="principal-sig-img" src="<?php echo $e($principalSig); ?>" alt="<?php echo $e($principalName); ?>" />
                    <?php else: ?>
                      <span class="sig-space">&nbsp;</span>
                    <?php endif; ?>
                    <div class="principal-name-cell"><?php echo $e($principalName); ?></div>
                    <div class="principal-role-cell">Branch Principal</div>
                  </div>
                </td>
              </tr>
            </table>
          </div>
        </td>
      </tr>

      <tr>
        <td class="shell-cell">
          <?php $sectionOpen('Registered Examination Modules'); ?>
          <div class="card card-attached card-table-wrap module-summary-box">
            <table class="module-summary-table" width="100%" cellpadding="0" cellspacing="0">
              <colgroup><col style="width:130px" /><col /></colgroup>
              <thead>
                <tr>
                  <th class="ms-th">Code</th>
                  <th class="ms-th">Module Name</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($moduleRows)): ?>
                  <tr class="ms-data"><td colspan="2" class="muted">No modules scheduled.</td></tr>
                <?php else: ?>
                  <?php foreach ($moduleRows as $i => $mr): ?>
                    <tr class="ms-data<?php echo ($i % 2 === 1) ? ' ms-alt' : ''; ?>">
                      <td class="ms-code"><?php echo $e($mr['code']); ?></td>
                      <td class="ms-name"><?php echo $e($mr['name']); ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
          <?php $sectionClose(); ?>
        </td>
      </tr>

      <tr>
        <td class="shell-cell shell-bottom">
          <?php $sectionOpen('Part B – Attestation'); ?>
          <div class="card card-attached part-b-box">
            <p class="part-b-text">I certify that the above student is a registered student of this institute.</p>
            <table class="part-b-sign" width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td class="pb-hod">
                  <div class="pb-line">&nbsp;</div>
                  <div class="pb-label">Head of Department</div>
                </td>
                <td class="pb-date">
                  <div class="pb-line">&nbsp;</div>
                  <div class="pb-label">Date</div>
                </td>
                <td class="pb-stamp">
                  <div class="pb-stamp-box">&nbsp;</div>
                  <div class="pb-stamp-label">Official Stamp</div>
                </td>
              </tr>
            </table>
          </div>
          <?php $sectionClose(); ?>
        </td>
      </tr>
    </table>
  </div>

  <div class="adm-page adm-page-2">
    <table class="page-shell" width="100%" cellpadding="0" cellspacing="0">
      <tr>
        <td class="shell-cell">
          <?php $sectionOpen('Instructions to Candidates'); ?>
          <div class="card card-attached rules-card">
            <ol class="exam-rules-list">
              <?php foreach ($examRules as $rule): ?>
                <li><?php echo $e($rule); ?></li>
              <?php endforeach; ?>
            </ol>
          </div>
          <?php $sectionClose(); ?>
        </td>
      </tr>

      <tr>
        <td class="shell-cell">
          <?php $sectionOpen('Examination Schedule'); ?>
          <div class="card card-attached card-table-wrap">
            <table class="simple-table sched-table" width="100%" cellpadding="0" cellspacing="0">
              <colgroup>
                <col style="width:18%" />
                <col style="width:44%" />
                <col style="width:18%" />
                <col style="width:20%" />
              </colgroup>
              <tr class="simple-head">
                <td>Module Code</td>
                <td>Module Name</td>
                <td class="st-center">Date</td>
                <td class="st-center">Time</td>
              </tr>
              <?php if (empty($moduleRows)): ?>
                <tr><td colspan="4" class="muted center">No modules scheduled.</td></tr>
              <?php else: ?>
                <?php foreach ($moduleRows as $mr): ?>
                  <tr>
                    <td class="st-code"><?php echo $e($mr['code']); ?></td>
                    <td class="st-name"><?php echo $e($mr['name']); ?></td>
                    <td class="st-center"><?php echo $e($mr['date_dmy']); ?></td>
                    <td class="st-center"><?php echo $e($mr['time_display'] ?? $mr['time']); ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </table>
          </div>
          <?php $sectionClose(); ?>
        </td>
      </tr>

      <tr>
        <td class="shell-cell">
          <?php $sectionOpen('Examination Attendance Record'); ?>
          <div class="card card-attached card-table-wrap">
            <table class="simple-table attendance-table" width="100%" cellpadding="0" cellspacing="0">
              <colgroup>
                <col style="width:34%" /><col style="width:12%" /><col style="width:14%" /><col style="width:20%" /><col style="width:20%" />
              </colgroup>
              <tr class="simple-head">
                <td>Name of the Module</td>
                <td class="st-center">Date</td>
                <td class="st-center">Time</td>
                <td class="st-center">Candidate&apos;s Signature</td>
                <td class="st-center">Initials of Invigilator</td>
              </tr>
              <?php for ($i = 0; $i < $attendanceRows; $i++): ?>
                <tr class="att-row">
                  <td>&nbsp;</td>
                  <td class="st-center">&nbsp;</td>
                  <td class="st-center">&nbsp;</td>
                  <td class="st-center">&nbsp;</td>
                  <td class="st-center">&nbsp;</td>
                </tr>
              <?php endfor; ?>
            </table>
          </div>
          <?php $sectionClose(); ?>
        </td>
      </tr>

      <tr>
        <td class="shell-cell shell-bottom">
          <div class="card supervisor-card">
            <table class="supervisor-footer" width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td class="sup-label">Name of Supervisor:</td>
                <td class="sup-line" colspan="3">&nbsp;</td>
              </tr>
              <tr>
                <td class="sup-label">Signature of Supervisor:</td>
                <td class="sup-line sup-line-mid">&nbsp;</td>
                <td class="sup-date-label">Date:</td>
                <td class="sup-date-line">&nbsp;</td>
              </tr>
            </table>
          </div>
        </td>
      </tr>
    </table>
  </div>

</div>
