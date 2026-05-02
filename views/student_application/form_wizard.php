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

        <div class="step-pills" id="stepPills" aria-label="Progress">
          <?php
          $labels = ['NIC', 'Personal', 'Contact', 'School', 'NVQ', 'Courses', 'Documents', 'Review'];
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

          <!-- Step 1 -->
          <div class="wiz-pane show" data-step="1">
            <h2 class="h5 mb-3">Step 1</h2>
            <p class="text-muted small">Enter your National Identity Card number and click <strong>Next</strong>.</p>
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
                  <?php foreach (['Sinhala', 'Tamil', 'English'] as $opt): ?>
                    <option value="<?php echo htmlspecialchars($opt, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (($old['student_language'] ?? '') === $opt) ? 'selected' : ''; ?>><?php echo htmlspecialchars($opt, ENT_QUOTES, 'UTF-8'); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label" for="student_religion">Religion <?php echo $req; ?></label>
                <select class="form-select" id="student_religion" name="student_religion" required>
                  <option value="">Choose…</option>
                  <?php foreach (['Buddhism', 'Hinduism', 'Islam', 'Christianity'] as $opt): ?>
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
                <label class="form-label" for="student_zip_code">Postal / ZIP code <?php echo $req; ?></label>
                <input type="text" class="form-control" id="student_zip_code" name="student_zip_code" maxlength="10" required value="<?php echo $v('student_zip_code'); ?>">
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
            </div>
          </div>

          <!-- Step 4 O/L & A/L -->
          <div class="wiz-pane" data-step="4">
            <h2 class="h5 mb-2">Step 4 — School qualifications</h2>
            <p class="text-muted small mb-4"><strong>Level 04:</strong> O/L and A/L are required. Please complete every field.</p>

            <div class="card l05-exam-card ol shadow-sm mb-4">
              <div class="card-header py-3 d-flex align-items-start gap-3">
                <span class="rounded-3 bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:2.5rem;height:2.5rem;">
                  <i class="fas fa-book-open" aria-hidden="true"></i>
                </span>
                <div>
                  <div class="l05-exam-title">G.C.E. Ordinary Level (O/L) <?php echo $req; ?></div>
                  <p class="l05-exam-sub mb-0">Required — index, year, six core subjects, three basket subjects (one per category), and valid results</p>
                </div>
              </div>
              <div class="card-body pt-0">
                <div class="l05-section-label">Examination details</div>
                <div class="row g-3 mb-4">
                  <div class="col-md-6">
                    <label class="form-label small fw-semibold text-secondary" for="ol_index_number">Index number <?php echo $req; ?></label>
                    <input type="text" class="form-control" id="ol_index_number" name="ol_index_number" maxlength="50" required value="<?php echo $v('ol_index_number'); ?>">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small fw-semibold text-secondary" for="ol_exam_year">Year of examination <?php echo $req; ?></label>
                    <input type="number" class="form-control" id="ol_exam_year" name="ol_exam_year" min="1990" max="2100" step="1" required value="<?php echo $v('ol_exam_year'); ?>" placeholder="e.g. 2019">
                  </div>
                </div>
                <div class="l05-section-label">Subjects &amp; results</div>
                <p class="small text-muted mb-3">Slots 1–6: mandatory subjects; slots 7–9: one subject from each <strong>basket</strong> category. Pick each result (A–F, S, W±, or 0–100).</p>
                <div class="row g-3">
                  <?php for ($i = 1; $i <= 9; $i++) : ?>
                  <?php
                  $variant = 'wizard';
                  $extraAttr = ' required';
                  $slotReqHtml = ' ' . $req;
                  require __DIR__ . '/_ol_subject_slot.php';
                  ?>
                  <?php endfor; ?>
                </div>
              </div>
            </div>

            <div class="card l05-exam-card al shadow-sm">
              <div class="card-header py-3 d-flex align-items-start gap-3">
                <span class="rounded-3 bg-success bg-opacity-10 text-success d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:2.5rem;height:2.5rem;">
                  <i class="fas fa-graduation-cap" aria-hidden="true"></i>
                </span>
                <div>
                  <div class="l05-exam-title">G.C.E. Advanced Level (A/L) <?php echo $req; ?></div>
                  <p class="l05-exam-sub mb-0">Required — index, year, stream, and three subjects with results</p>
                </div>
              </div>
              <div class="card-body pt-0">
                <div class="l05-section-label">Examination details</div>
                <div class="row g-3 mb-4">
                  <div class="col-md-4">
                    <label class="form-label small fw-semibold text-secondary" for="al_index_number">Index number <?php echo $req; ?></label>
                    <input type="text" class="form-control" id="al_index_number" name="al_index_number" maxlength="50" required value="<?php echo $v('al_index_number'); ?>">
                  </div>
                  <div class="col-md-4">
                    <label class="form-label small fw-semibold text-secondary" for="al_exam_year">Year of examination <?php echo $req; ?></label>
                    <input type="number" class="form-control" id="al_exam_year" name="al_exam_year" min="1990" max="2100" step="1" required value="<?php echo $v('al_exam_year'); ?>" placeholder="e.g. 2022">
                  </div>
                  <div class="col-md-4">
                    <label class="form-label small fw-semibold text-secondary" for="al_stream">Stream <?php echo $req; ?></label>
                    <input type="text" class="form-control" id="al_stream" name="al_stream" maxlength="80" required value="<?php echo $v('al_stream'); ?>" placeholder="e.g. Physical Science, Arts">
                  </div>
                </div>
                <div class="l05-section-label">Subjects &amp; results</div>
                <p class="small text-muted mb-3">Three subjects with grades or marks (A–F, S, W±, or 0–100).</p>
                <div class="row g-3">
                  <?php for ($i = 1; $i <= 3; $i++): $s = sprintf('%02d', $i); ?>
                    <div class="col-md-4">
                      <div class="l05-subject-slot">
                        <div class="l05-slot-label">A/L subject <?php echo (int) $i; ?> <?php echo $req; ?></div>
                        <input type="text" class="form-control form-control-sm mb-2" id="al_subject_name_<?php echo $s; ?>" name="al_subject_name_<?php echo $s; ?>" maxlength="120" required value="<?php echo $v('al_subject_name_' . $s); ?>" placeholder="Subject name">
                        <input type="text" class="form-control form-control-sm" id="al_subject_<?php echo $s; ?>_marks" name="al_subject_<?php echo $s; ?>_marks" maxlength="10" required value="<?php echo $v('al_subject_' . $s . '_marks'); ?>" placeholder="Grade / marks">
                      </div>
                    </div>
                  <?php endfor; ?>
                </div>
              </div>
            </div>
          </div>

          <!-- Step 5 NVQ -->
          <div class="wiz-pane" data-step="5">
            <h2 class="h5 mb-3">Step 5 — NVQ</h2>
            <p class="text-muted small">If you have no NVQ yet, type <strong>N/A</strong> in the text boxes. For year, you can type <strong>2000</strong>.</p>
            <div class="row g-3">
              <div class="col-md-3">
                <label class="form-label" for="nvq_level">NVQ level <?php echo $req; ?></label>
                <input type="text" class="form-control" id="nvq_level" name="nvq_level" maxlength="50" required value="<?php echo $v('nvq_level'); ?>" placeholder="e.g. 4">
              </div>
              <div class="col-md-9">
                <label class="form-label" for="nvq_course_name">Course / qualification name <?php echo $req; ?></label>
                <input type="text" class="form-control" id="nvq_course_name" name="nvq_course_name" maxlength="255" required value="<?php echo $v('nvq_course_name'); ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label" for="nvq_institute_name">Institute <?php echo $req; ?></label>
                <input type="text" class="form-control" id="nvq_institute_name" name="nvq_institute_name" maxlength="255" required value="<?php echo $v('nvq_institute_name'); ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label" for="nvq_year_completed">Year completed <?php echo $req; ?></label>
                <input type="number" class="form-control" id="nvq_year_completed" name="nvq_year_completed" min="1900" max="2100" step="1" required value="<?php echo $v('nvq_year_completed'); ?>">
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
            <p class="text-muted small">Upload all six files (PDF, JPG, or PNG, max 5 MB each). Stored scans are compressed to JPEG under 100 KB.</p>
            <div class="row g-3">
              <?php
              $docs = [
                  'nic_document' => 'Copy of ID card (NIC)',
                  'birth_certificate' => 'Birth certificate',
                  'ol_certificate' => 'O/L certificate',
                  'al_certificate' => 'A/L certificate',
                  'nvq_certificate' => 'NVQ certificate (or a scan of N/A if not used)',
                  'bank_receipt' => 'Bank slip (payment)',
              ];
              foreach ($docs as $name => $lab):
              ?>
                <div class="col-md-6">
                  <label class="form-label" for="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($lab, ENT_QUOTES, 'UTF-8'); ?> <span class="text-danger">*</span></label>
                  <input type="file" class="form-control" id="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>" name="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" required>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Step 8 Review -->
          <div class="wiz-pane" data-step="8">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
              <div>
                <h2 class="h5 mb-1">Step 8 — Review</h2>
                <p class="text-muted small mb-0">Use <strong>Previous</strong> to edit. Submit sends your application to the server.</p>
              </div>
            </div>
            <dl class="row small mb-0" id="reviewDl"></dl>
          </div>

          <div class="d-flex flex-wrap justify-content-between gap-2 mt-4 pt-3 border-top">
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

<script>
(function () {
  var totalSteps = 8;
  var currentStep = 1;

  function $(id) { return document.getElementById(id); }

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
    if (/^[A-FSW][+-]?$/i.test(m)) return true;
    if (/^\d+$/.test(m)) {
      var n = parseInt(m, 10);
      return n >= 0 && n <= 100;
    }
    return false;
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
    $('btnPrev').disabled = n === 1;
    $('btnNext').classList.toggle('d-none', n === totalSteps);
    $('btnSubmit').classList.toggle('d-none', n !== totalSteps);
    updatePills();
    hideAlert();
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
      ['student_email','student_address','student_zip_code','student_province','student_district'].forEach(function (id) {
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
      var ids = ['ol_index_number','ol_exam_year','al_index_number','al_exam_year','al_stream'];
      for (var oi = 1; oi <= 9; oi++) {
        var os = (oi < 10 ? '0' : '') + oi;
        ids.push('ol_subject_name_' + os, 'ol_subject_' + os + '_marks');
      }
      for (var ai = 1; ai <= 3; ai++) {
        var as = (ai < 10 ? '0' : '') + ai;
        ids.push('al_subject_name_' + as, 'al_subject_' + as + '_marks');
      }
      var ok4 = true;
      ids.forEach(function (id) {
        var el = $(id);
        var v = el ? String(el.value || '').trim() : '';
        markInvalid(el, !v);
        if (!v) ok4 = false;
      });
      if (!ok4) {
        showAlert('Please complete all O/L and A/L fields.');
        return false;
      }
      var yo = parseInt($('ol_exam_year').value, 10);
      var ya = parseInt($('al_exam_year').value, 10);
      if (isNaN(yo) || yo < 1990 || yo > 2100) { showAlert('O/L year must be between 1990 and 2100.'); markInvalid($('ol_exam_year'), true); return false; }
      if (isNaN(ya) || ya < 1990 || ya > 2100) { showAlert('A/L year must be between 1990 and 2100.'); markInvalid($('al_exam_year'), true); return false; }
      for (var o2 = 1; o2 <= 9; o2++) {
        var os2 = (o2 < 10 ? '0' : '') + o2;
        if (!examResultOk($('ol_subject_' + os2 + '_marks').value)) {
          showAlert('O/L results: use a letter (A–F, S, W±) or a mark from 0 to 100 for every subject.');
          markInvalid($('ol_subject_' + os2 + '_marks'), true);
          return false;
        }
      }
      for (var a2 = 1; a2 <= 3; a2++) {
        var as2 = (a2 < 10 ? '0' : '') + a2;
        if (!examResultOk($('al_subject_' + as2 + '_marks').value)) {
          showAlert('A/L results: use a letter (A–F, S, W±) or a mark from 0 to 100 for every subject.');
          markInvalid($('al_subject_' + as2 + '_marks'), true);
          return false;
        }
      }
      return true;
    }
    if (step === 5) {
      var ok5 = true;
      ['nvq_level','nvq_course_name','nvq_institute_name','nvq_year_completed'].forEach(function (id) {
        var el = $(id);
        var v = el ? String(el.value || '').trim() : '';
        markInvalid(el, !v);
        if (!v) ok5 = false;
      });
      if (!ok5) { showAlert('Please complete all NVQ fields.'); return false; }
      var yn = parseInt($('nvq_year_completed').value, 10);
      if (isNaN(yn) || yn < 1900 || yn > 2100) { showAlert('NVQ year finished must be between 1900 and 2100.'); markInvalid($('nvq_year_completed'), true); return false; }
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
      ['nic_document','birth_certificate','ol_certificate','al_certificate','nvq_certificate','bank_receipt'].forEach(function (id) {
        var inp = $(id);
        var ok = inp && inp.files && inp.files.length;
        if (!ok) miss = true;
        markInvalid(inp, !ok);
      });
      if (miss) showAlert('Please choose all six document files.');
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
    if (el.tagName === 'TEXTAREA' || (el.tagName === 'INPUT' && el.type !== 'file' && el.type !== 'hidden')) {
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
      ['Postal / ZIP code', 'student_zip_code'],
      ['Province', 'student_province'],
      ['District', 'student_district'],
      ['First choice — department', 'dept_pref_1'],
      ['First choice — course', 'course_priority_1'],
      ['Second choice — department', 'dept_pref_2'],
      ['Second choice — course', 'course_priority_2'],
      ['Third choice — department', 'dept_pref_3'],
      ['Third choice — course', 'course_priority_3'],
      ['O/L index', 'ol_index_number'],
      ['O/L year', 'ol_exam_year'],
      ['A/L index', 'al_index_number'],
      ['A/L year', 'al_exam_year'],
      ['A/L stream', 'al_stream'],
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
    ['nic_document','birth_certificate','ol_certificate','al_certificate','nvq_certificate','bank_receipt'].forEach(function (id) {
      var inp = $(id);
      var name = id.replace(/_/g, ' ');
      var v = (inp && inp.files && inp.files.length) ? inp.files[0].name : '';
      html += escapeHtml(name) + ': ' + escapeHtml(v || '—') + '<br>';
    });
    html += '</dd>';
    $('reviewDl').innerHTML = html;
  }

  $('btnNext').addEventListener('click', function () {
    if (!validateStep(currentStep)) return;
    if (currentStep < totalSteps) showStep(currentStep + 1);
  });
  $('btnPrev').addEventListener('click', function () {
    if (currentStep <= 1) return;
    showStep(currentStep - 1);
  });

  // If server returned errors, jump to the first likely step.
  if (document.querySelector('.alert.alert-danger')) {
    showStep(2);
  } else {
    showStep(1);
  }
})();
</script>

