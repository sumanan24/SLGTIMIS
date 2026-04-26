<?php
/**
 * Admission form — PDF (single A4 page per student). Wrapped with HTML + styles by PrintController.
 *
 * @var array $exam
 * @var array $student
 * @var list<array{code: string, name: string, date_dmy: string, time: string}> $moduleRows
 * @var array{subtitle: string, subject_line: string, exam_centre: string, nvq_semester: string} $meta
 * @var string $logo_src Data URI for header logo (top-right).
 * @var string|null $principal_sig_src Data URI for Principal digital signature, or null for placeholder.
 */
$e = static function (?string $s): string {
    return htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
};
$logoSrc = (string) ($logo_src ?? '');
$principalSig = $principal_sig_src ?? null;
?>
<div class="admission-student admission-onepage">
  <table class="head-row">
    <tr>
      <td class="head-left">
        <div class="inst">Sri Lanka German Training Institute</div>
        <div class="title">Admission Form</div>
        <div class="sub"><?php echo $e($meta['subtitle'] ?? ''); ?></div>
      </td>
      <td class="head-right">
        <?php if ($logoSrc !== ''): ?>
          <img class="logo-img" src="<?php echo $e($logoSrc); ?>" alt="SLGTI" />
        <?php endif; ?>
      </td>
    </tr>
  </table>
  <div class="divider"></div>

  <p class="section-title">Candidate Details</p>
  <div class="section-box">
    <table class="info">
      <tr><th>Index Number</th><td><?php echo $e($student['student_id'] ?? ''); ?></td></tr>
      <tr><th>N.I.C. No.</th><td><?php echo $e($student['student_nic'] ?? ''); ?></td></tr>
      <tr><th>Name (with initials)</th><td><?php echo $e($student['student_fullname'] ?? ''); ?></td></tr>
      <tr><th>Subject</th><td><?php echo $e($meta['subject_line'] ?? ''); ?></td></tr>
      <tr><th>Examination Centre</th><td><?php echo $e($meta['exam_centre'] ?? ''); ?></td></tr>
      <tr><th>NVQ Level and Semester</th><td><?php echo $e($meta['nvq_semester'] ?? ''); ?></td></tr>
    </table>
  </div>

  <p class="section-title">Examination Schedule</p>
  <table class="grid grid-p2 tbl-schedule">
    <thead>
      <tr>
        <th style="width:18%;">Module Code</th>
        <th style="width:46%;">Module Name</th>
        <th style="width:14%;">Date</th>
        <th style="width:22%;">Time</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($moduleRows)): ?>
        <tr><td colspan="4" class="muted">No modules scheduled for this exam.</td></tr>
      <?php else: ?>
        <?php foreach ($moduleRows as $mr): ?>
          <tr>
            <td class="td-left"><?php echo $e($mr['code']); ?></td>
            <td class="td-left"><?php echo $e($mr['name']); ?></td>
            <td class="td-center"><?php echo $e($mr['date_dmy']); ?></td>
            <td class="td-center"><?php echo $e($mr['time']); ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>

  <table class="split-row" width="100%">
    <tr>
      <td class="split-left">
        <div class="allow-block allow-compact">
          <p class="allow">This candidate is allowed to sit for this written examination.</p>
          <div class="principal-auth">
            <div class="principal-sig">
              <?php if ($principalSig): ?>
                <img src="<?php echo $e($principalSig); ?>" alt="Principal signature" />
              <?php else: ?>
                <span class="sig-placeholder" title="Digital signature"></span>
              <?php endif; ?>
            </div>
            <div class="principal-rule"></div>
            <div class="principal-label">Director / Principal</div>
          </div>
        </div>
      </td>
      <td class="split-right">
        <div class="part-b part-b-p1">
          <p class="part-h2">Part B — Attestation</p>
          <p class="attest-text">I certify that the above student is a registered student of this institute.</p>
          <table class="attest">
            <tr>
              <td class="sig-cell">
                <div class="sig-line">Head of Department</div>
              </td>
              <td class="stamp-cell">
                <div>Official Stamp:</div>
              </td>
            </tr>
          </table>
        </div>
      </td>
    </tr>
  </table>

  <p class="section-title">Attendance / Invigilation Log</p>
  <table class="grid grid-p2 tbl-attendance">
    <thead>
      <tr>
        <th style="width:32%;">Name of the Module</th>
        <th style="width:12%;">Date</th>
        <th style="width:14%;">Time</th>
        <th style="width:21%;">Candidate&apos;s Signature</th>
        <th style="width:21%;">Initials of Invigilator</th>
      </tr>
    </thead>
    <tbody>
      <?php for ($i = 0; $i < 5; $i++): ?>
      <tr>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <?php endfor; ?>
    </tbody>
  </table>

  <div class="sup-p2 sup-compact">
    <div class="sup-lines">
      <div class="sup-line">
        <span class="sup-label">Name of Supervisor:</span>
        <span class="sup-fill sup-fill-name"></span>
      </div>

      <div class="sup-line sup-line-sig">
        <span class="sup-label">Signature of Supervisor:</span>
        <span class="sup-fill sup-fill-sig"></span>
        <span class="sup-date-wrap"><span class="sup-label">Date:</span> <span class="sup-fill sup-fill-date"></span></span>
      </div>
    </div>
  </div>
</div>
