<?php
/**
 * Wizard-style public student application form (Level 04).
 *
 * @var string $application_level
 * @var string $csrf_token
 * @var list<string> $errors
 * @var array<string, mixed> $old
 * @var string|null $flash_success
 * @var array<string, list<string>> $sl_provinces_districts
 * @var array<string, string> $sl_district_postal_codes
 */
declare(strict_types=1);

$old = $old ?? [];
$sl_provinces_districts = $sl_provinces_districts ?? [];
$sl_district_postal_codes = $sl_district_postal_codes ?? [];
$errors = $errors ?? [];

$v = static function (string $key, string $default = '') use ($old): string {
    return htmlspecialchars((string) ($old[$key] ?? $default), ENT_QUOTES, 'UTF-8');
};

$actionUrl = rtrim(APP_URL, '/') . '/level' . htmlspecialchars($application_level, ENT_QUOTES, 'UTF-8') . 'application';
$levelLabel = $application_level === '04' ? 'Level 04' : 'Level 05';
$req = '<span class="text-danger fw-bold" aria-hidden="true">*</span>';

$today = new DateTimeImmutable('today');
$dobMax = $today->modify('-16 years')->format('Y-m-d');
$dobMin = $today->modify('-90 years')->format('Y-m-d');
?>

<style>
  :root { --wiz-brand: #0c4a6e; --wiz-accent: #0369a1; }
  .wiz-card { border-radius: 16px; border: 0; box-shadow: 0 12px 40px rgba(12, 74, 110, 0.12); }
  .wiz-header { border-bottom: 3px solid var(--wiz-accent); padding-bottom: 1rem; }
  .step-pills { display: flex; flex-wrap: wrap; gap: 0.35rem; justify-content: center; margin: 0.75rem 0 1.25rem; }
  .step-pill {
    display: flex; flex-direction: column; align-items: center; padding: 0.4rem 0.5rem; border-radius: 10px;
    font-size: 0.65rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.03em;
    color: var(--wiz-brand); opacity: 0.4; transition: opacity 0.2s, background 0.2s; min-width: 3.6rem;
  }
  .step-pill.active { opacity: 1; background: #e0f2fe; }
  .step-pill.done { opacity: 0.75; }
  .step-num {
    width: 1.55rem; height: 1.55rem; border-radius: 50%; background: var(--wiz-brand); color: #fff;
    display: flex; align-items: center; justify-content: center; font-size: 0.75rem; margin-bottom: 0.12rem;
  }
  .step-pill.active .step-num { background: var(--wiz-accent); }
  .wiz-pane { display: none; animation: fadeIn 0.25s ease; }
  .wiz-pane.show { display: block; }
  @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
  .l05-exam-card { border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; background: #fff; }
  .l05-exam-card .card-header { border-bottom: 1px solid #e2e8f0; background: #fff; }
  .l05-exam-card.ol .card-header { background: linear-gradient(135deg, #eff6ff 0%, #e0f2fe 100%); }
  .l05-exam-card.al .card-header { background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); }
  .l05-exam-card .card-header .l05-exam-title { color: #0f172a; font-weight: 700; font-size: 1.05rem; }
  .l05-exam-card .card-header .l05-exam-sub { font-size: 0.82rem; color: #64748b; }
  .l05-subject-slot { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 0.85rem 1rem; height: 100%; }
  .l05-subject-slot .l05-slot-label { font-size: 0.68rem; font-weight: 800; letter-spacing: 0.06em; text-transform: uppercase; color: #64748b; margin-bottom: 0.5rem; }
  .l05-section-label { font-size: 0.72rem; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: #475569; margin-bottom: 0.75rem; }
  .l05-course-pref-card { border-radius: 12px; border: 1px solid #e2e8f0; background: #fff; }
  .l05-course-pref-card .l05-pref-badge { font-size: 0.68rem; font-weight: 800; letter-spacing: 0.07em; text-transform: uppercase; color: #64748b; margin-bottom: 0.75rem; }
</style>

<?php if (!empty($flash_success)): ?>
<div class="alert alert-success mb-4" role="status"><?php echo htmlspecialchars($flash_success, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger mb-4" role="alert">
  <strong>Something is wrong. Please check:</strong>
  <ul class="mb-0 mt-2"><?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<div class="row justify-content-center">
  <div class="col-12 col-xl-10">
    <div class="card wiz-card">
      <div class="card-body p-4 p-md-5">
        <div class="wiz-header mb-3">
          <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="min-w-0">
              <div class="small text-muted fw-semibold">Sri Lanka German Training Institute · Apply online 2026</div>
              <h1 class="h3 mb-0 text-dark">Online application — <?php echo htmlspecialchars($levelLabel, ENT_QUOTES, 'UTF-8'); ?></h1>
            </div>
            <span class="badge text-bg-primary bg-opacity-75"><?php echo htmlspecialchars($levelLabel, ENT_QUOTES, 'UTF-8'); ?></span>
          </div>
        </div>

        <div id="globalAlert" class="alert d-none" role="alert"></div>
        <div id="nicContextBarFixed" class="alert py-2 px-3 small mb-3 d-none" role="status"></div>

        <div class="step-pills" id="stepPills" aria-label="Progress">
          <?php
          $labels = ['NIC', 'Personal', 'Contact', 'O/L', 'NVQ', 'Courses', 'Documents', 'Review'];
          for ($i = 0; $i < 8; $i++):
          ?>
            <div class="step-pill" data-step="<?php echo $i + 1; ?>">
              <span class="step-num"><?php echo $i + 1; ?></span>
              <span><?php echo htmlspecialchars($labels[$i], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
          <?php endfor; ?>
        </div>

        <form method="post" action="<?php echo $actionUrl; ?>" enctype="multipart/form-data" class="app-student-application-form" id="wizardForm" novalidate>
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="application_level" value="<?php echo htmlspecialchars($application_level, ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="application_id" id="application_id" value="<?php echo htmlspecialchars((string) ($old['application_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

          <!-- Step 1 -->
          <div class="wiz-pane show" data-step="1">
            <h2 class="h5 mb-3">Step 1</h2>
            <p class="text-muted small">Enter your National Identity Card number and click <strong>Next</strong>. If you already started an application, your details will load &mdash; you can change answers until you submit. After a complete submission, the form is read-only (download your PDF from Review).</p>
            <div class="row g-3">
              <div class="col-12 col-md-8">
                <label for="student_nic" class="form-label">NIC <?php echo $req; ?></label>
                <input type="text" class="form-control form-control-lg" id="student_nic" name="student_nic" maxlength="20" required autocomplete="off" placeholder="e.g. 123456789V or 12 digits" value="<?php echo $v('student_nic'); ?>" aria-describedby="student_nic_feedback">
                <div id="student_nic_feedback" class="app-live-feedback small mt-2" role="status" aria-live="polite"></div>
              </div>
            </div>
          </div>

          <!-- Step 2 Personal -->
          <div class="wiz-pane" data-step="2">
            <h2 class="h5 mb-3">Step 2 — Personal</h2>
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label" for="student_title">Title <?php echo $req; ?></label>
                <select class="form-select" id="student_title" name="student_title" required>
                  <option value="">Choose…</option>
                  <?php foreach (['Mr', 'Miss', 'Mrs'] as $t): ?>
                    <option value="<?php echo htmlspecialchars($t, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (($old['student_title'] ?? '') === $t) ? 'selected' : ''; ?>><?php echo htmlspecialchars($t, ENT_QUOTES, 'UTF-8'); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-8">
                <label class="form-label" for="student_full_name">Full name <?php echo $req; ?></label>
                <input type="text" class="form-control" id="student_full_name" name="student_full_name" maxlength="150" required value="<?php echo $v('student_full_name'); ?>" autocomplete="name">
              </div>
              <div class="col-md-6">
                <label class="form-label" for="student_initial_name">Name with initials <?php echo $req; ?></label>
                <input type="text" class="form-control" id="student_initial_name" name="student_initial_name" maxlength="100" required value="<?php echo $v('student_initial_name'); ?>">
              </div>
              <div class="col-md-3">
                <label class="form-label" for="student_gender">Gender <?php echo $req; ?></label>
                <select class="form-select" id="student_gender" name="student_gender" required>
                  <option value="">Choose…</option>
                  <?php foreach (['Male', 'Female', 'Other'] as $g): ?>
                    <option value="<?php echo htmlspecialchars($g, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (($old['student_gender'] ?? '') === $g) ? 'selected' : ''; ?>><?php echo htmlspecialchars($g, ENT_QUOTES, 'UTF-8'); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label" for="student_civil_status">Civil status <?php echo $req; ?></label>
                <select class="form-select" id="student_civil_status" name="student_civil_status" required>
                  <option value="">Choose…</option>
                  <?php foreach (['Single', 'Married'] as $cs): ?>
                    <option value="<?php echo htmlspecialchars($cs, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (($old['student_civil_status'] ?? '') === $cs) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cs, ENT_QUOTES, 'UTF-8'); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label" for="student_dob">Date of birth <?php echo $req; ?></label>
                <input type="date" class="form-control" id="student_dob" name="student_dob" required min="<?php echo htmlspecialchars($dobMin, ENT_QUOTES, 'UTF-8'); ?>" max="<?php echo htmlspecialchars($dobMax, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo $v('student_dob'); ?>">
                <div class="form-text">You must be <strong>16 years or older</strong>.</div>
              </div>
              <div class="col-md-4">
                <label class="form-label" for="student_language">Language <?php echo $req; ?></label>
                <select class="form-select" id="student_language" name="student_language" required>
                  <option value="">Choose…</option>
                  <?php foreach (['Tamil', 'Sinhala', 'English'] as $opt): ?>
                    <option value="<?php echo htmlspecialchars($opt, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (($old['student_language'] ?? '') === $opt) ? 'selected' : ''; ?>><?php echo htmlspecialchars($opt, ENT_QUOTES, 'UTF-8'); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label" for="student_religion">Religion <?php echo $req; ?></label>
                <select class="form-select" id="student_religion" name="student_religion" required>
                  <option value="">Choose…</option>
                  <?php foreach (['Hinduism', 'Buddhism', 'Islam', 'Christianity'] as $opt): ?>
                    <option value="<?php echo htmlspecialchars($opt, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (($old['student_religion'] ?? '') === $opt) ? 'selected' : ''; ?>><?php echo htmlspecialchars($opt, ENT_QUOTES, 'UTF-8'); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>

          <!-- Step 3 Contact -->
          <div class="wiz-pane" data-step="3">
            <h2 class="h5 mb-3">Step 3 — Contact &amp; address</h2>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label" for="student_email">Email <?php echo $req; ?></label>
                <input type="email" class="form-control" id="student_email" name="student_email" maxlength="100" required value="<?php echo $v('student_email'); ?>" autocomplete="email" inputmode="email">
              </div>
              <div class="col-md-3">
                <label class="form-label" for="student_phone">Phone <?php echo $req; ?></label>
                <input type="tel" class="form-control" id="student_phone" name="student_phone" maxlength="20" required value="<?php echo $v('student_phone'); ?>" autocomplete="tel" inputmode="tel" aria-describedby="student_phone_feedback">
                <div id="student_phone_feedback" class="app-live-feedback small mt-2" role="status" aria-live="polite"></div>
              </div>
              <div class="col-md-3">
                <label class="form-label" for="student_whatsapp">WhatsApp <?php echo $req; ?></label>
                <input type="tel" class="form-control" id="student_whatsapp" name="student_whatsapp" maxlength="20" required value="<?php echo $v('student_whatsapp'); ?>" autocomplete="tel" inputmode="tel" aria-describedby="student_whatsapp_feedback">
                <div id="student_whatsapp_feedback" class="app-live-feedback small mt-2" role="status" aria-live="polite"></div>
              </div>
              <div class="col-12">
                <label class="form-label" for="student_address">Address <?php echo $req; ?></label>
                <textarea class="form-control" id="student_address" name="student_address" rows="3" required><?php echo $v('student_address'); ?></textarea>
              </div>
              <div class="col-md-4">
                <label class="form-label" for="student_province">Province <?php echo $req; ?></label>
                <select class="form-select" id="student_province" name="student_province" required>
                  <option value="">Choose…</option>
                  <?php foreach (array_keys($sl_provinces_districts) as $prov): ?>
                    <option value="<?php echo htmlspecialchars($prov, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (($old['student_province'] ?? '') === $prov) ? 'selected' : ''; ?>><?php echo htmlspecialchars($prov, ENT_QUOTES, 'UTF-8'); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label" for="student_district">District <?php echo $req; ?></label>
                <select class="form-select" id="student_district" name="student_district" required>
                  <option value="">Choose province first…</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label" for="student_zip_code">Postal / ZIP code <?php echo $req; ?></label>
                <input type="text" class="form-control" id="student_zip_code" name="student_zip_code" maxlength="10" required value="<?php echo $v('student_zip_code'); ?>">
              </div>
            </div>
          </div>

          <!-- Step 4 O/L (Level 04 — A/L not collected on this form) -->
          <div class="wiz-pane" data-step="4">
            <h2 class="h5 mb-2">Step 4 — G.C.E. O/L</h2>
            <p class="text-muted small mb-4"><strong>Level 04:</strong> You must complete <strong>either</strong> this O/L section <strong>or</strong> all NVQ fields in step 5 (you may do both). If you start O/L, fill every field in this section.</p>

            <div class="card l05-exam-card ol shadow-sm mb-0">
              <div class="card-header py-3 d-flex align-items-start gap-3">
                <span class="rounded-3 bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:2.5rem;height:2.5rem;">
                  <i class="fas fa-book-open" aria-hidden="true"></i>
                </span>
                <div>
                  <div class="l05-exam-title">G.C.E. Ordinary Level (O/L)</div>
                  <p class="l05-exam-sub mb-0">Index, year, main subjects (grades) and three basket subject choices, or leave the whole section blank if you apply with NVQ only.</p>
                </div>
              </div>
              <div class="card-body pt-0">
                <div class="l05-section-label">Examination details</div>
                <div class="row g-3 mb-4">
                  <div class="col-md-6">
                    <label class="form-label small fw-semibold text-secondary" for="ol_index_number">Index number</label>
                    <input type="text" class="form-control" id="ol_index_number" name="ol_index_number" maxlength="50" value="<?php echo $v('ol_index_number'); ?>">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small fw-semibold text-secondary" for="ol_exam_year">Year of examination</label>
                    <input type="number" class="form-control" id="ol_exam_year" name="ol_exam_year" min="1990" max="2100" step="1" value="<?php echo $v('ol_exam_year'); ?>" placeholder="e.g. 2019">
                  </div>
                </div>
                <div class="l05-section-label">Subjects &amp; results</div>
                <div class="row g-3">
                  <?php for ($i = 1; $i <= 9; $i++) : ?>
                  <?php
                  $variant = 'wizard';
                  $extraAttr = '';
                  $slotReqHtml = '';
                  require __DIR__ . '/_ol_subject_slot.php';
                  ?>
                  <?php endfor; ?>
                </div>
              </div>
            </div>
          </div>

          <!-- Step 5 NVQ -->
          <div class="wiz-pane" data-step="5">
            <h2 class="h5 mb-3">Step 5 — NVQ</h2>
            <p class="text-muted small">Fill this section only if you are applying with <strong>NVQ</strong> (required together with O/L if you did not complete O/L in step 4). If you completed O/L in step 4, you may leave all NVQ fields blank.</p>
            <div class="row g-3">
              <div class="col-md-3">
                <label class="form-label" for="nvq_level">NVQ level</label>
                <select class="form-select" id="nvq_level" name="nvq_level">
                  <option value="">—</option>
                  <option value="3" <?php echo (($old['nvq_level'] ?? '') === '3') ? 'selected' : ''; ?>>NVQ Level 3</option>
                </select>
              </div>
              <div class="col-md-9">
                <label class="form-label" for="nvq_course_name">Course / qualification name</label>
                <input type="text" class="form-control" id="nvq_course_name" name="nvq_course_name" maxlength="255" value="<?php echo $v('nvq_course_name'); ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label" for="nvq_institute_name">Institute</label>
                <input type="text" class="form-control" id="nvq_institute_name" name="nvq_institute_name" maxlength="255" value="<?php echo $v('nvq_institute_name'); ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label" for="nvq_year_completed">Year completed</label>
                <input type="number" class="form-control" id="nvq_year_completed" name="nvq_year_completed" min="1900" max="2100" step="1" value="<?php echo $v('nvq_year_completed'); ?>">
              </div>
            </div>
          </div>

          <!-- Step 6 Courses -->
          <div class="wiz-pane" data-step="6">
            <h2 class="h5 mb-2">Step 6 — Course choices</h2>
            <p class="text-muted small mb-4">Choose a <strong>department</strong> first; the <strong>course</strong> list loads NVQ Level 04 courses for that department. First choice is required; second and third are optional.</p>
            <?php foreach ([1 => 'First', 2 => 'Second', 3 => 'Third'] as $prefNum => $prefLabel): $reqFirst = ((int) $prefNum === 1); ?>
              <div class="card l05-course-pref-card shadow-sm mb-3">
                <div class="card-body">
                  <div class="l05-pref-badge"><?php echo htmlspecialchars($prefLabel, ENT_QUOTES, 'UTF-8'); ?> choice <?php echo $reqFirst ? '<span class="text-danger">*</span>' : '<span class="text-muted fw-normal">(optional)</span>'; ?></div>
                  <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                      <label class="form-label small fw-semibold" for="dept_pref_<?php echo (int) $prefNum; ?>">Department <?php echo $reqFirst ? '<span class="text-danger">*</span>' : ''; ?></label>
                      <select class="form-select js-app-dept" id="dept_pref_<?php echo (int) $prefNum; ?>" name="dept_pref_<?php echo (int) $prefNum; ?>" data-pref="<?php echo (int) $prefNum; ?>"<?php echo $reqFirst ? ' required' : ''; ?>>
                        <option value="">Loading departments…</option>
                      </select>
                    </div>
                    <div class="col-md-7">
                      <label class="form-label small fw-semibold" for="course_priority_<?php echo (int) $prefNum; ?>">Course (NVQ 04) <?php echo $reqFirst ? '<span class="text-danger">*</span>' : ''; ?></label>
                      <select class="form-select js-app-course" id="course_priority_<?php echo (int) $prefNum; ?>" name="course_priority_<?php echo (int) $prefNum; ?>"<?php echo $reqFirst ? ' required' : ''; ?>>
                        <option value="">Choose department first…</option>
                      </select>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
            <p class="small text-muted mb-0"><i class="fas fa-info-circle me-1"></i>Only NVQ Level 4 courses are shown.</p>
          </div>

          <!-- Step 7 Documents -->
          <div class="wiz-pane" data-step="7">
            <h2 class="h5 mb-3">Step 7 — Documents</h2>
            <p class="text-muted small" id="docHelpNew">Upload <strong>NIC copy</strong>, <strong>birth certificate</strong>, and <strong>bank slip</strong> (PDF, JPG, or PNG, max 5 MB each). Upload an <strong>O/L certificate</strong> if you completed O/L in step 4, and an <strong>NVQ certificate</strong> (or N/A scan) if you completed NVQ in step 5. Images and raster PDFs are stored as compressed JPEG under 100 KB; if the server cannot convert a PDF, the original PDF is stored instead.</p>
            <p class="text-muted small d-none" id="docHelpUpdate">You already have files on record. Leave a file blank to keep the current upload, or choose a new file to replace it.</p>
            <div class="row g-3">
              <?php
              $docs = [
                  'nic_document' => ['Copy of ID card (NIC)', true, 'nic_document_path'],
                  'birth_certificate' => ['Birth certificate', true, 'birth_certificate_path'],
                  'ol_certificate' => ['O/L certificate (only if you completed O/L in step 4)', false, 'ol_certificate_path'],
                  'nvq_certificate' => ['NVQ certificate (only if you completed NVQ in step 5)', false, 'nvq_certificate_path'],
                  'bank_receipt' => ['Bank slip (payment)', true, 'bank_receipt_path'],
              ];
              foreach ($docs as $name => $info):
                  $lab = $info[0];
                  $mand = $info[1];
                  $pathCol = $info[2];
              ?>
                <div class="col-md-6">
                  <label class="form-label" for="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($lab, ENT_QUOTES, 'UTF-8'); ?> <?php echo $mand ? '<span class="text-danger doc-req">*</span>' : ''; ?></label>
                  <input type="file" class="form-control" id="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>" name="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png">
                  <div class="form-text existing-hint small text-muted" data-path-key="<?php echo htmlspecialchars($pathCol, ENT_QUOTES, 'UTF-8'); ?>"></div>
                </div>
              <?php endforeach; ?>
            </div>
            <p class="small text-muted mb-0 mt-2">Required uploads match your answers: O/L and NVQ certificates only when those sections are fully completed.</p>
          </div>

          <!-- Step 8 Review -->
          <div class="wiz-pane" data-step="8">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
              <div>
                <h2 class="h5 mb-1">Step 8 — Review</h2>
                <p class="text-muted small mb-0" id="reviewIntroEdit">Use <strong>Previous</strong> to edit. Submit sends your application to the server.</p>
                <p class="text-muted small mb-0 d-none" id="reviewIntroReadonly">Your application is already on file with all required documents. Download a PDF copy for your records. To change details, contact the institute.</p>
              </div>
              <button type="button" class="btn btn-outline-primary btn-sm flex-shrink-0" id="btnDownloadApplicationPdfReview" title="Download a PDF summary of your application">
                <i class="fas fa-file-pdf me-1"></i> Download application (PDF)
              </button>
            </div>
            <dl class="row small mb-0" id="reviewDl"></dl>
          </div>

          <div class="d-flex flex-wrap justify-content-between gap-2 mt-4 pt-3 border-top" id="wizNavRow">
            <button type="button" class="btn btn-outline-secondary" id="btnPrev" disabled>
              <i class="fas fa-arrow-left me-1"></i> Previous
            </button>
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-primary" id="btnNext">
                Next <i class="fas fa-arrow-right ms-1"></i>
              </button>
              <button type="submit" class="btn btn-success d-none" id="btnSubmit">
                <i class="fas fa-check me-1"></i> Submit
              </button>
            </div>
          </div>
        </form>

      </div>
    </div>
  </div>
</div>

<script>
window.APP_BASE = <?php echo json_encode(rtrim(APP_URL, '/'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
window.NVQ_COURSE_LEVEL = <?php echo json_encode(($application_level ?? '04') === '05' ? '5' : '4', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
window.APP_FORM_OLD = <?php echo json_encode($old, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>;
window.SL_PROVINCE_DISTRICTS = <?php echo json_encode($sl_provinces_districts, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>;
window.SL_DISTRICT_POSTAL = <?php echo json_encode($sl_district_postal_codes, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>;
</script>
<?php require __DIR__ . '/_address_province_scripts.php'; ?>
<?php require __DIR__ . '/_course_preferences_scripts.php'; ?>
<?php require __DIR__ . '/_contact_validation_scripts.php'; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js" crossorigin="anonymous"></script>
<script>
(function () {
  var totalSteps = 8;
  var currentStep = 1;
  var form = document.getElementById('wizardForm');
  var nicChecked = false;
  var recordFromDb = false;
  var hadUploadedDocs = false;
  var workflowStatus = '';
  var reviewOnlyMode = false;
  var apiBase = (typeof window.APP_BASE === 'string' ? window.APP_BASE : '').replace(/\/$/, '');
  var PATH_FIELD_TO_COL = {
    nic_document: 'nic_document_path',
    birth_certificate: 'birth_certificate_path',
    ol_certificate: 'ol_certificate_path',
    nvq_certificate: 'nvq_certificate_path',
    bank_receipt: 'bank_receipt_path'
  };

  function $(id) { return document.getElementById(id); }

  function l04EnsureSelectValue(selId, val) {
    if (val == null) return;
    var el = $(selId);
    if (!el) return;
    if (el.tagName === 'INPUT' && el.type === 'hidden') {
      el.value = String(val).trim();
      return;
    }
    if (el.tagName !== 'SELECT') return;
    var s = String(val).trim();
    if (s === '') return;
    var i;
    for (i = 0; i < el.options.length; i++) {
      if (el.options[i].value === s) {
        el.selectedIndex = i;
        return;
      }
    }
    var opt = document.createElement('option');
    opt.value = s;
    opt.textContent = s;
    el.appendChild(opt);
    el.value = s;
  }

  function inputVal(id) {
    var el = $(id);
    return el ? String(el.value || '').trim() : '';
  }

  function olExamKeys() {
    var keys = ['ol_index_number', 'ol_exam_year'];
    for (var i = 1; i <= 9; i++) {
      var s = (i < 10 ? '0' : '') + i;
      keys.push('ol_subject_name_' + s, 'ol_subject_' + s + '_marks');
    }
    return keys;
  }

  function olAnyFilled() {
    var keys = olExamKeys();
    for (var k = 0; k < keys.length; k++) {
      if (inputVal(keys[k]) !== '') return true;
    }
    return false;
  }

  function olPathComplete() {
    if (!inputVal('ol_index_number') || !inputVal('ol_exam_year')) return false;
    var yo = parseInt(($('ol_exam_year') && $('ol_exam_year').value) || '', 10);
    if (isNaN(yo) || yo < 1990 || yo > 2100) return false;
    for (var oi = 1; oi <= 9; oi++) {
      var os = (oi < 10 ? '0' : '') + oi;
      if (!inputVal('ol_subject_name_' + os) || !inputVal('ol_subject_' + os + '_marks')) return false;
    }
    return true;
  }

  function nvqExamKeys() {
    return ['nvq_level', 'nvq_course_name', 'nvq_institute_name', 'nvq_year_completed'];
  }

  function nvqAnyFilled() {
    var keys = nvqExamKeys();
    for (var k = 0; k < keys.length; k++) {
      if (inputVal(keys[k]) !== '') return true;
    }
    return false;
  }

  function nvqPathComplete() {
    return nvqExamKeys().every(function (id) { return inputVal(id) !== ''; });
  }

  function showAlert(msg, kind) {
    var el = $('globalAlert');
    el.className = 'alert alert-' + (kind || 'danger');
    el.textContent = msg;
    el.classList.remove('d-none');
  }
  function hideAlert() { $('globalAlert').classList.add('d-none'); }

  function normalizeNic(s) {
    return String(s || '').toUpperCase().trim().replace(/[\s\-_]+/g, '');
  }
  function isValidNic(n) {
    return /^(\d{9}[VX]|\d{12})$/.test(normalizeNic(n));
  }

  function digitsOnly(s) { return String(s || '').replace(/\D/g, ''); }
  function phoneOk(v) {
    var d = digitsOnly(v);
    if (d.indexOf('94') === 0 && d.length > 2) d = d.slice(2);
    else if (d.indexOf('0') === 0 && d.length > 1) d = d.slice(1);
    return d.length === 9 && /^[1-9]\d{8}$/.test(d);
  }

  function examResultOk(raw) {
    var m = String(raw || '').trim();
    if (!m) return false;
    return /^[A-FSW][+-]?$/i.test(m);
  }

  function olResultOk(raw) {
    var m = String(raw || '').trim().toUpperCase();
    return m === 'A' || m === 'B' || m === 'C' || m === 'S' || m === 'W';
  }

  function lockNic() {
    var el = $('student_nic');
    if (el) {
      el.readOnly = true;
      el.classList.add('bg-light');
    }
  }

  function clearFileHints() {
    document.querySelectorAll('.existing-hint').forEach(function (h) { h.textContent = ''; });
  }

  function hasStoredDoc(id) {
    var col = PATH_FIELD_TO_COL[id];
    if (!col) return false;
    var hint = document.querySelector('.existing-hint[data-path-key="' + col + '"]');
    return !!(hint && /^Current file:/.test(String(hint.textContent || '')));
  }

  function olCompleteFromData(d) {
    if (!d || typeof d !== 'object') return false;
    if (!String(d.ol_index_number || '').trim() || !String(d.ol_exam_year || '').trim()) return false;
    var y = parseInt(String(d.ol_exam_year || ''), 10);
    if (isNaN(y) || y < 1990 || y > 2100) return false;
    for (var oi = 1; oi <= 9; oi++) {
      var os = (oi < 10 ? '0' : '') + oi;
      if (!String(d['ol_subject_name_' + os] || '').trim()) return false;
      if (!String(d['ol_subject_' + os + '_marks'] || '').trim()) return false;
    }
    return true;
  }

  function nvqCompleteFromData(d) {
    if (!d || typeof d !== 'object') return false;
    return ['nvq_level', 'nvq_course_name', 'nvq_institute_name', 'nvq_year_completed'].every(function (k) {
      return String(d[k] || '').trim() !== '';
    });
  }

  function isFullySubmittedLevel04Data(d) {
    if (!d || typeof d !== 'object') return false;
    if (!olCompleteFromData(d) && !nvqCompleteFromData(d)) return false;
    var req = ['nic_document_path', 'birth_certificate_path', 'bank_receipt_path'];
    if (olCompleteFromData(d)) req.push('ol_certificate_path');
    if (nvqCompleteFromData(d)) req.push('nvq_certificate_path');
    return req.every(function (col) {
      return d[col] && String(d[col]).trim() !== '';
    });
  }

  function getApplicationId() {
    var el = $('application_id');
    return parseInt(String(el && el.value ? el.value : ''), 10) || 0;
  }

  function setReviewOnlyMode(on) {
    reviewOnlyMode = !!on;
    var pills = $('stepPills');
    var nav = $('wizNavRow');
    var introE = $('reviewIntroEdit');
    var introR = $('reviewIntroReadonly');
    if (pills) pills.classList.toggle('d-none', reviewOnlyMode);
    if (nav) nav.classList.toggle('d-none', reviewOnlyMode);
    if (introE) introE.classList.toggle('d-none', reviewOnlyMode);
    if (introR) introR.classList.toggle('d-none', !reviewOnlyMode);
  }

  var l04EditEnabled = true;
  function setFormEditable(editable) {
    l04EditEnabled = !!editable;
    if (!form) return;
    form.querySelectorAll('input, select, textarea').forEach(function (el) {
      if (!el || !el.name) return;
      if (el.name === 'student_nic' || el.type === 'hidden') return;
      if (el.type === 'file') {
        el.disabled = !l04EditEnabled;
        return;
      }
      if (el.tagName === 'SELECT') {
        el.disabled = !l04EditEnabled;
      } else {
        el.readOnly = !l04EditEnabled;
      }
      el.classList.toggle('bg-light', !l04EditEnabled);
    });
  }

  function syncDocUi() {
    var newH = $('docHelpNew');
    var upH = $('docHelpUpdate');
    if (recordFromDb && hadUploadedDocs) {
      if (newH) newH.classList.add('d-none');
      if (upH) upH.classList.remove('d-none');
      document.querySelectorAll('.doc-req').forEach(function (s) { s.classList.add('d-none'); });
    } else {
      if (newH) newH.classList.remove('d-none');
      if (upH) upH.classList.add('d-none');
      document.querySelectorAll('.doc-req').forEach(function (s) { s.classList.remove('d-none'); });
    }
  }

  function syncNicContextBar() {
    var bar = $('nicContextBarFixed');
    if (!bar) return;
    if (currentStep < 2 || !nicChecked) {
      bar.classList.add('d-none');
      bar.innerHTML = '';
      return;
    }
    bar.classList.remove('d-none', 'alert-info', 'alert-warning', 'alert-secondary', 'alert-success', 'alert-danger');
    var st = workflowStatus;
    var stLabel = st === 'approved' ? 'Approved' : (st === 'rejected' ? 'Rejected' : 'Pending review');
    if (reviewOnlyMode) {
      bar.classList.add('alert-secondary');
      var aid = getApplicationId();
      bar.innerHTML =
        '<i class="fas fa-circle-check me-2"></i><strong>Application on file.</strong> Reference <strong>#'
        + aid
        + '</strong>'
        + (st ? ' · Status: <strong>' + stLabel + '</strong>' : '')
        + '. Download your <strong>PDF</strong> from the Review step. This page is read-only.';
      return;
    }
    if (recordFromDb) {
      bar.classList.add('alert-warning');
      bar.innerHTML =
        '<i class="fas fa-rotate me-2"></i><strong>Continue your application.</strong> This NIC already has a Level 04 record'
        + (st ? ' (status: <strong>' + stLabel + '</strong>)' : '')
        + '. Change fields as needed, then use <strong>Next</strong> until you submit. After submission, this page becomes read-only.';
    } else {
      bar.classList.add('alert-info');
      bar.innerHTML =
        '<i class="fas fa-user-plus me-2"></i><strong>New application.</strong> Your NIC is confirmed. Complete all steps, then submit.';
    }
  }

  function applyPrefillFromServer(data) {
    if (!form || !data || typeof data !== 'object') return;
    workflowStatus = String(data.status || '').toLowerCase();
    Object.keys(data).forEach(function (k) {
      if (k.indexOf('_path') !== -1 || k === 'created_at' || k === 'application_workflow_status') return;
      if (/^course_priority_[123]$/.test(k) || /^dept_pref_[123]$/.test(k)) return;
      var el = form.elements.namedItem(k);
      if (!el || el.type === 'file' || el.type === 'hidden') return;
      el.value = data[k] == null ? '' : String(data[k]);
    });
    var p = String(data.student_province || '');
    var d = String(data.student_district || '');
    var map2 = (typeof window.SL_PROVINCE_DISTRICTS === 'object' && window.SL_PROVINCE_DISTRICTS) ? window.SL_PROVINCE_DISTRICTS : {};
    var postal2 = (typeof window.SL_DISTRICT_POSTAL === 'object' && window.SL_DISTRICT_POSTAL) ? window.SL_DISTRICT_POSTAL : {};
    var ps = $('student_province');
    var ds = $('student_district');
    if (ps && p && ds) {
      ps.value = p;
      ds.innerHTML = '';
      var opt0 = document.createElement('option');
      opt0.value = '';
      opt0.textContent = 'Choose district…';
      ds.appendChild(opt0);
      (map2[p] || []).forEach(function (dist) {
        var opt = document.createElement('option');
        opt.value = dist;
        opt.textContent = dist;
        if (d && d === dist) opt.selected = true;
        ds.appendChild(opt);
      });
      if (d) ds.value = d;
      var zip = $('student_zip_code');
      if (zip && d && postal2[d] && !String(zip.value || '').trim()) {
        zip.value = postal2[d];
      }
    }
    try {
      var fn = $('student_full_name');
      if (fn && String(fn.value || '').trim() === '(Pending)') fn.value = '';
    } catch (e1) {}
    for (var oi = 1; oi <= 9; oi++) {
      var os = (oi < 10 ? '0' : '') + oi;
      var nameKey = 'ol_subject_name_' + os;
      var markKey = 'ol_subject_' + os + '_marks';
      if (data[nameKey] != null) l04EnsureSelectValue(nameKey, data[nameKey]);
      if (data[markKey] != null) l04EnsureSelectValue(markKey, data[markKey]);
    }
    $('application_id').value = data.application_id ? String(data.application_id) : '';
    clearFileHints();
    Object.keys(PATH_FIELD_TO_COL).forEach(function (field) {
      var pk = PATH_FIELD_TO_COL[field];
      if (data[pk] && String(data[pk]).trim() !== '') {
        var hint = document.querySelector('.existing-hint[data-path-key="' + pk + '"]');
        if (hint) {
          var parts = String(data[pk]).split(/[/\\\\]/);
          hint.textContent = 'Current file: ' + parts[parts.length - 1];
        }
      }
    });
    if (typeof window.appCoursePrefsRestore === 'function') {
      window.appCoursePrefsRestore(data);
    }
  }

  function updatePills() {
    document.querySelectorAll('.step-pill').forEach(function (pill) {
      var n = parseInt(pill.getAttribute('data-step'), 10);
      pill.classList.toggle('active', n === currentStep);
      pill.classList.toggle('done', n < currentStep);
    });
  }

  function showStep(n) {
    document.querySelectorAll('.wiz-pane').forEach(function (pane) {
      var sn = parseInt(pane.getAttribute('data-step'), 10);
      pane.classList.toggle('show', sn === n);
    });
    currentStep = n;
    $('btnPrev').disabled = n === 1 || reviewOnlyMode;
    $('btnNext').classList.toggle('d-none', n === totalSteps || reviewOnlyMode);
    $('btnSubmit').classList.toggle('d-none', n !== totalSteps || reviewOnlyMode);
    updatePills();
    hideAlert();
    syncNicContextBar();
    if (n === 7) syncDocUi();
    if (n === totalSteps) buildReview();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function markInvalid(el, on) {
    if (!el) return;
    el.classList.toggle('is-invalid', !!on);
  }

  function validateStep(step) {
    hideAlert();
    if (step === 1) {
      var nicEl = $('student_nic');
      var ok = isValidNic(nicEl.value);
      markInvalid(nicEl, !ok);
      if (!ok) showAlert('Enter a valid NIC, then click Next.');
      return ok;
    }
    if (step === 2) {
      var ok2 = true;
      ['student_title','student_full_name','student_initial_name','student_gender','student_civil_status','student_dob','student_language','student_religion'].forEach(function (id) {
        var el = $(id);
        var v = el ? String(el.value || '').trim() : '';
        markInvalid(el, !v);
        if (!v) ok2 = false;
      });
      if (!ok2) showAlert('Please fill all required personal fields.');
      return ok2;
    }
    if (step === 3) {
      var ok3 = true;
      ['student_email','student_address','student_province','student_district','student_zip_code'].forEach(function (id) {
        var el = $(id);
        var v = el ? String(el.value || '').trim() : '';
        markInvalid(el, !v);
        if (!v) ok3 = false;
      });
      var em = $('student_email');
      var ev = em ? String(em.value || '').trim() : '';
      if (em) {
        var emOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(ev);
        markInvalid(em, !emOk);
        if (!emOk) ok3 = false;
      }
      var p = $('student_phone');
      if (p) { markInvalid(p, !phoneOk(p.value)); if (!phoneOk(p.value)) ok3 = false; }
      var w = $('student_whatsapp');
      if (w) { markInvalid(w, !phoneOk(w.value)); if (!phoneOk(w.value)) ok3 = false; }
      if (!ok3) showAlert('Please correct the highlighted contact/address fields.');
      return ok3;
    }
    if (step === 4) {
      if (olAnyFilled() && !olPathComplete()) {
        showAlert('For O/L: fill every field in that section, or clear all O/L fields if you do not have O/L.');
        return false;
      }
      if (olPathComplete()) {
        for (var o2 = 1; o2 <= 9; o2++) {
          var os2 = (o2 < 10 ? '0' : '') + o2;
          var mel = $('ol_subject_' + os2 + '_marks');
          if (!mel || !olResultOk(mel.value)) {
            showAlert('O/L results: choose A, B, C, S, or W for every subject.');
            if (mel) markInvalid(mel, true);
            return false;
          }
        }
      }
      return true;
    }
    if (step === 5) {
      if (nvqAnyFilled() && !nvqPathComplete()) {
        showAlert('For NVQ: fill every field, or clear all NVQ fields if you use O/L only.');
        nvqExamKeys().forEach(function (id) {
          var el = $(id);
          if (el) markInvalid(el, inputVal(id) === '');
        });
        return false;
      }
      var olOk = olPathComplete();
      var nvqOk = nvqPathComplete();
      if (!olOk && !nvqOk) {
        showAlert('Complete either O/L (step 4) or all NVQ fields below.');
        nvqExamKeys().forEach(function (id) {
          var el = $(id);
          if (el) markInvalid(el, true);
        });
        return false;
      }
      nvqExamKeys().forEach(function (id) {
        var el = $(id);
        if (el) markInvalid(el, false);
      });
      if (nvqOk) {
        var yn = parseInt(($('nvq_year_completed') && $('nvq_year_completed').value) || '', 10);
        if (isNaN(yn) || yn < 1900 || yn > 2100) {
          showAlert('NVQ year finished must be between 1900 and 2100.');
          markInvalid($('nvq_year_completed'), true);
          return false;
        }
      }
      return true;
    }
    if (step === 6) {
      var d1 = $('dept_pref_1');
      var c1 = $('course_priority_1');
      var okD = d1 && String(d1.value || '').trim() !== '';
      var okC = c1 && String(c1.value || '').trim() !== '';
      if (d1) markInvalid(d1, !okD);
      if (c1) markInvalid(c1, !okC);
      for (var pn = 2; pn <= 3; pn++) {
        var dn = $('dept_pref_' + pn);
        var cn = $('course_priority_' + pn);
        var dv = dn ? String(dn.value || '').trim() : '';
        var cv = cn ? String(cn.value || '').trim() : '';
        var partial = (dv && !cv) || (!dv && cv);
        if (dn) markInvalid(dn, partial);
        if (cn) markInvalid(cn, partial);
        if (partial) { showAlert('For each optional choice, pick both department and course, or leave both empty.'); return false; }
      }
      if (!okD || !okC) { showAlert('Choose a department and a course for your first preference.'); return false; }
      return true;
    }
    if (step === 7) {
      var miss = false;
      function checkDoc(id, required) {
        var inp = $(id);
        var hasNew = inp && inp.files && inp.files.length;
        if (hasNew) {
          if (inp) markInvalid(inp, false);
          return;
        }
        if (recordFromDb && hadUploadedDocs && hasStoredDoc(id)) {
          if (inp) markInvalid(inp, false);
          return;
        }
        if (required) {
          if (inp) markInvalid(inp, true);
          miss = true;
        } else if (inp) {
          markInvalid(inp, false);
        }
      }
      checkDoc('nic_document', true);
      checkDoc('birth_certificate', true);
      checkDoc('bank_receipt', true);
      checkDoc('ol_certificate', olPathComplete());
      checkDoc('nvq_certificate', nvqPathComplete());
      if (miss) showAlert('Please choose every required document, or keep existing uploads when updating.');
      return !miss;
    }
    return true;
  }

  function escapeHtml(t) {
    var d = document.createElement('div');
    d.textContent = t;
    return d.innerHTML;
  }

  function fieldText(id) {
    var el = $(id);
    if (!el) return '';
    if (el.tagName === 'SELECT') {
      var i = el.selectedIndex;
      if (i < 0 || !el.options[i]) return String(el.value || '').trim();
      var tx = String(el.options[i].text || '').trim();
      if (/^(Choose|Loading|No departments)/i.test(tx)) return '';
      return tx;
    }
    if (el.tagName === 'INPUT' && el.type === 'hidden') {
      return String(el.value || '').trim();
    }
    if (el.tagName === 'TEXTAREA' || (el.tagName === 'INPUT' && el.type !== 'file')) {
      return String(el.value || '').trim();
    }
    return '';
  }

  function buildReview() {
    var pairs = [
      ['NIC', 'student_nic'],
      ['Title', 'student_title'],
      ['Full name', 'student_full_name'],
      ['Name with initials', 'student_initial_name'],
      ['Gender', 'student_gender'],
      ['Civil status', 'student_civil_status'],
      ['Date of birth', 'student_dob'],
      ['Language', 'student_language'],
      ['Religion', 'student_religion'],
      ['Email', 'student_email'],
      ['Phone', 'student_phone'],
      ['WhatsApp', 'student_whatsapp'],
      ['Address', 'student_address'],
      ['Province', 'student_province'],
      ['District', 'student_district'],
      ['Postal / ZIP code', 'student_zip_code'],
      ['First choice — department', 'dept_pref_1'],
      ['First choice — course', 'course_priority_1'],
      ['Second choice — department', 'dept_pref_2'],
      ['Second choice — course', 'course_priority_2'],
      ['Third choice — department', 'dept_pref_3'],
      ['Third choice — course', 'course_priority_3'],
      ['O/L index', 'ol_index_number'],
      ['O/L year', 'ol_exam_year'],
      ['NVQ level', 'nvq_level'],
      ['NVQ course', 'nvq_course_name'],
      ['NVQ institute', 'nvq_institute_name'],
      ['NVQ year completed', 'nvq_year_completed']
    ];
    var html = '';
    pairs.forEach(function (p) {
      var label = p[0];
      var id = p[1];
      var val = fieldText(id);
      if (id === 'student_nic') val = normalizeNic(($('student_nic') && $('student_nic').value) || '');
      html += '<dt class="col-sm-4">' + escapeHtml(label) + '</dt><dd class="col-sm-8">' + escapeHtml(val) + '</dd>';
    });
    html += '<dt class="col-sm-4">Documents</dt><dd class="col-sm-8">';
    ['nic_document','birth_certificate','ol_certificate','nvq_certificate','bank_receipt'].forEach(function (id) {
      var inp = $(id);
      var name = id.replace(/_/g, ' ');
      var v = (inp && inp.files && inp.files.length) ? inp.files[0].name : '';
      if (!v && hasStoredDoc(id)) v = '(keeping existing file)';
      html += escapeHtml(name) + ': ' + escapeHtml(v || '—') + '<br>';
    });
    html += '</dd>';
    $('reviewDl').innerHTML = html;
  }

  var DOC_PDF_LABELS = {
    nic_document: 'NIC copy',
    birth_certificate: 'Birth certificate',
    ol_certificate: 'O/L certificate',
    nvq_certificate: 'NVQ certificate',
    bank_receipt: 'Bank payment slip'
  };

  function l04DownloadApplicationPdf() {
    if (typeof window.jspdf === 'undefined') {
      window.alert('PDF tools did not load. Please check your connection and refresh the page.');
      return;
    }
    var jsPDF = window.jspdf.jsPDF;
    var doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
    var pageW = doc.internal.pageSize.getWidth();
    var marginX = 14;
    var y = 14;
    doc.setTextColor(12, 74, 110);
    doc.setFontSize(15);
    doc.setFont('helvetica', 'bold');
    doc.text('Sri Lanka German Training Institute', pageW / 2, y, { align: 'center' });
    y += 7;
    doc.setFontSize(11);
    doc.text('NVQ Level 04 Application — 2026', pageW / 2, y, { align: 'center' });
    y += 6;
    doc.setFont('helvetica', 'normal');
    doc.setTextColor(71, 85, 105);
    doc.setFontSize(9);
    doc.text('Application summary (copy for your records)', pageW / 2, y, { align: 'center' });
    y += 5;
    var nic = normalizeNic(fieldText('student_nic'));
    var appId = getApplicationId();
    doc.text('NIC: ' + (nic || '—') + '    Reference: ' + (appId ? '#' + appId : '—'), pageW / 2, y, { align: 'center' });
    y += 4;
    doc.text('Generated: ' + new Date().toLocaleString(), pageW / 2, y, { align: 'center' });
    y += 9;
    doc.setTextColor(33, 37, 41);

    function l04PdfCell(s) {
      var t = String(s == null ? '' : s).replace(/\u0000/g, '');
      if (t.length > 650) t = t.substring(0, 647) + '\u2026';
      return t;
    }

    function addSection(title, pairs) {
      var body = [];
      for (var i = 0; i < pairs.length; i++) {
        var val = pairs[i][1];
        if (val === '' || val === undefined || val === null) val = '—';
        body.push([l04PdfCell(pairs[i][0]), l04PdfCell(val)]);
      }
      if (body.length === 0) return;
      var pageH = doc.internal.pageSize.getHeight();
      var estH = 14 + body.length * 6;
      if (y + estH > pageH - 10) {
        doc.addPage();
        y = 14;
      }
      doc.autoTable({
        startY: y,
        margin: { left: marginX, right: marginX, bottom: 14 },
        head: [[{ content: title, colSpan: 2 }]],
        body: body,
        theme: 'striped',
        styles: {
          fontSize: 8.5,
          cellPadding: 1.6,
          textColor: [33, 37, 41],
          lineColor: [200, 208, 216],
          lineWidth: 0.15,
          minCellHeight: 5
        },
        headStyles: {
          fillColor: [12, 74, 110],
          textColor: [255, 255, 255],
          fontStyle: 'bold',
          fontSize: 10,
          halign: 'left',
          valign: 'middle'
        },
        columnStyles: {
          0: { cellWidth: 48, fontStyle: 'bold', textColor: [51, 65, 85] },
          1: { cellWidth: pageW - marginX * 2 - 48 }
        }
      });
      y = doc.lastAutoTable.finalY + 6;
    }

    addSection('1. Personal information', [
      ['Title', fieldText('student_title')],
      ['Full name', fieldText('student_full_name')],
      ['Name with initials', fieldText('student_initial_name')],
      ['Gender', fieldText('student_gender')],
      ['Civil status', fieldText('student_civil_status')],
      ['Date of birth', fieldText('student_dob')],
      ['Language', fieldText('student_language')],
      ['Religion', fieldText('student_religion')]
    ]);

    addSection('2. Contact and address', [
      ['Email', fieldText('student_email')],
      ['Phone', fieldText('student_phone')],
      ['WhatsApp', fieldText('student_whatsapp')],
      ['Address', fieldText('student_address')],
      ['Postal / ZIP code', fieldText('student_zip_code')],
      ['Province', fieldText('student_province')],
      ['District', fieldText('student_district')]
    ]);

    if (olPathComplete()) {
      var olRows = [
        ['O/L index number', fieldText('ol_index_number')],
        ['O/L examination year', fieldText('ol_exam_year')]
      ];
      for (var oi = 1; oi <= 9; oi++) {
        var os = (oi < 10 ? '0' : '') + oi;
        olRows.push(['O/L subject ' + oi + ' — name', fieldText('ol_subject_name_' + os)]);
        olRows.push(['O/L subject ' + oi + ' — result', fieldText('ol_subject_' + os + '_marks')]);
      }
      addSection('3. G.C.E. Ordinary Level (O/L)', olRows);
    }

    if (nvqPathComplete()) {
      addSection(olPathComplete() ? '4. NVQ qualification' : '3. NVQ qualification', [
        ['NVQ level', fieldText('nvq_level')],
        ['Course / qualification name', fieldText('nvq_course_name')],
        ['Institute', fieldText('nvq_institute_name')],
        ['Year completed', fieldText('nvq_year_completed')]
      ]);
    }

    addSection('Course choices', [
      ['First choice — department', fieldText('dept_pref_1')],
      ['First choice — course', fieldText('course_priority_1')],
      ['Second choice — department', fieldText('dept_pref_2')],
      ['Second choice — course', fieldText('course_priority_2')],
      ['Third choice — department', fieldText('dept_pref_3')],
      ['Third choice — course', fieldText('course_priority_3')]
    ]);

    var docRows = [];
    ['nic_document', 'birth_certificate', 'ol_certificate', 'nvq_certificate', 'bank_receipt'].forEach(function (fk) {
      var pk = PATH_FIELD_TO_COL[fk];
      var lbl = DOC_PDF_LABELS[fk] || fk;
      var inp = $(fk);
      var val = '';
      if (inp && inp.files && inp.files.length) {
        val = inp.files[0].name;
      } else {
        var hint = document.querySelector('.existing-hint[data-path-key="' + pk + '"]');
        if (hint && hint.textContent) {
          val = hint.textContent.replace(/^Current file:\s*/i, '').trim();
        }
        if (!val && recordFromDb && hadUploadedDocs) {
          val = 'Previously uploaded (unchanged)';
        }
      }
      docRows.push([lbl, val || '—']);
    });
    addSection('Documents', docRows);

    doc.setPage(doc.getNumberOfPages());
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(7.5);
    doc.setTextColor(100, 116, 139);
    doc.text('SLGTI · NVQ Level 04 · PDF generated from your application data. Keep this file with your reference number.', marginX, doc.internal.pageSize.getHeight() - 7);

    var fn = 'SLGTI-L04-application';
    if (nic) fn += '-' + nic.replace(/[^0-9A-Z]/gi, '');
    fn += '-' + new Date().toISOString().slice(0, 10) + '.pdf';
    doc.save(fn);
  }

  var btnPdfReview = $('btnDownloadApplicationPdfReview');
  if (btnPdfReview) btnPdfReview.addEventListener('click', function () { l04DownloadApplicationPdf(); });

  $('btnNext').addEventListener('click', function () {
    if (currentStep === 1) {
      if (!validateStep(1)) return;
      var nic = normalizeNic($('student_nic').value);
      fetch(apiBase + '/student-application/api/check-application', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ nic: nic, application_level: '04' })
      })
        .then(function (r) { return r.text().then(function (t) { return { ok: r.ok, status: r.status, t: t }; }); })
        .then(function (res) {
          var j;
          try {
            j = JSON.parse(res.t);
          } catch (e) {
            showAlert('Server did not return JSON (HTTP ' + res.status + '). Check the PHP error log.');
            return;
          }
          if (j.status === 'error') {
            showAlert(j.message || 'NIC check failed.');
            return;
          }
          nicChecked = true;
          if (j.status === 'exists' && j.data) {
            recordFromDb = true;
            applyPrefillFromServer(j.data);
            hadUploadedDocs = ['nic_document_path', 'birth_certificate_path', 'bank_receipt_path', 'ol_certificate_path', 'nvq_certificate_path'].some(function (c) {
              return j.data[c] && String(j.data[c]).trim() !== '';
            });
            lockNic();
            if (isFullySubmittedLevel04Data(j.data)) {
              reviewOnlyMode = true;
              setReviewOnlyMode(true);
              setFormEditable(false);
              showStep(8);
              showAlert('This NIC already has a submitted Level 04 application with all required documents. You can download a PDF copy from Review.', 'info');
            } else {
              reviewOnlyMode = false;
              setReviewOnlyMode(false);
              setFormEditable(true);
              showStep(2);
            }
          } else {
            recordFromDb = false;
            reviewOnlyMode = false;
            setReviewOnlyMode(false);
            hadUploadedDocs = false;
            workflowStatus = '';
            $('application_id').value = '';
            clearFileHints();
            lockNic();
            setFormEditable(true);
            showStep(2);
          }
        })
        .catch(function () {
          showAlert('Could not reach the server to verify your NIC.');
        });
      return;
    }
    if (!validateStep(currentStep)) return;
    if (reviewOnlyMode) return;
    if (currentStep < totalSteps) showStep(currentStep + 1);
  });
  $('btnPrev').addEventListener('click', function () {
    if (reviewOnlyMode) return;
    if (currentStep <= 1) return;
    showStep(currentStep - 1);
  });

  if (form) {
    form.addEventListener('submit', function (ev) {
      if (reviewOnlyMode) {
        ev.preventDefault();
      }
    });
  }

  // If server returned errors, jump to the first likely step.
  if (document.querySelector('.alert.alert-danger')) {
    nicChecked = true;
    var aidEl = $('application_id');
    var aid = aidEl ? parseInt(String(aidEl.value || ''), 10) || 0 : 0;
    if (aid > 0) {
      recordFromDb = true;
      lockNic();
      if (typeof window.appCoursePrefsRestore === 'function') {
        window.appCoursePrefsRestore(window.APP_FORM_OLD || {});
      }
      setFormEditable(true);
    }
    showStep(2);
  } else {
    showStep(1);
  }

  (function bootDocHintsFromOld() {
    var old = window.APP_FORM_OLD || {};
    if (old.application_workflow_status) {
      workflowStatus = String(old.application_workflow_status).toLowerCase();
    }
    var pathCols = ['nic_document_path', 'birth_certificate_path', 'ol_certificate_path', 'nvq_certificate_path', 'bank_receipt_path'];
    pathCols.forEach(function (c) {
      if (!old[c] || !String(old[c]).trim()) return;
      var hint = document.querySelector('.existing-hint[data-path-key="' + c + '"]');
      if (hint) {
        var parts = String(old[c]).split(/[/\\\\]/);
        hint.textContent = 'Current file: ' + parts[parts.length - 1];
      }
    });
    if (pathCols.some(function (c) { return old[c] && String(old[c]).trim() !== ''; })) {
      hadUploadedDocs = true;
    }
    syncDocUi();
    syncNicContextBar();
  })();
})();
</script>

