<?php
/**
 * @var callable $v
 * @var string $application_level
 * @var string $req
 * @var array $old
 */
$req = $req ?? '<span class="text-danger fw-bold">*</span>';
$isLevel05 = (($application_level ?? '04') === '05');
$examAttr = $isLevel05 ? '' : ' required';
$sl_provinces_districts = $sl_provinces_districts ?? [];
$sl_district_postal_codes = $sl_district_postal_codes ?? [];
$default_zip = (string) ($old['student_zip_code'] ?? '');
if ($default_zip === '' && !empty($old['student_district']) && isset($sl_district_postal_codes[$old['student_district']])) {
    $default_zip = $sl_district_postal_codes[$old['student_district']];
}
$today = new DateTimeImmutable('today');
$dobMax = $today->modify('-16 years')->format('Y-m-d');
$dobMin = $today->modify('-90 years')->format('Y-m-d');
?>
<!-- Personal -->
<div class="card shadow-sm border-0 mb-4" id="section-about">
    <div class="card-body">
        <div class="app-form-section-title"><i class="fas fa-user me-2"></i>About you</div>
        <div class="row app-form-grid g-3">
            <div class="col-12 col-md-6 col-xl-4">
                <label class="form-label" for="student_title">Title <?php echo $req; ?></label>
                <select name="student_title" id="student_title" class="form-select form-select-sm" required>
                    <option value="">Choose…</option>
                    <?php foreach (['Mr', 'Ms', 'Mrs', 'Miss', 'Rev', 'Dr', 'Other'] as $opt): ?>
                    <option value="<?php echo htmlspecialchars($opt, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (($old['student_title'] ?? '') === $opt) ? 'selected' : ''; ?>><?php echo htmlspecialchars($opt, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-6 col-xl-8">
                <label class="form-label" for="student_full_name">Full name <?php echo $req; ?></label>
                <input type="text" name="student_full_name" id="student_full_name" class="form-control form-control-sm" required maxlength="150" value="<?php echo $v('student_full_name'); ?>" autocomplete="name">
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label" for="student_initial_name">Short name (with initials) <?php echo $req; ?></label>
                <input type="text" name="student_initial_name" id="student_initial_name" class="form-control form-control-sm" required maxlength="100" value="<?php echo $v('student_initial_name'); ?>">
            </div>
            <div class="col-12 col-md-6 col-xl-4">
                <label class="form-label" for="student_gender">Gender <?php echo $req; ?></label>
                <select name="student_gender" id="student_gender" class="form-select form-select-sm" required>
                    <option value="">Choose…</option>
                    <?php foreach (['Male', 'Female', 'Other'] as $g): ?>
                    <option value="<?php echo htmlspecialchars($g); ?>" <?php echo (($old['student_gender'] ?? '') === $g) ? 'selected' : ''; ?>><?php echo htmlspecialchars($g); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-6 col-xl-4">
                <label class="form-label" for="student_civil_status">Single or married <?php echo $req; ?></label>
                <select name="student_civil_status" id="student_civil_status" class="form-select form-select-sm" required>
                    <option value="">Choose…</option>
                    <?php foreach (['Single', 'Married'] as $g): ?>
                    <option value="<?php echo htmlspecialchars($g); ?>" <?php echo (($old['student_civil_status'] ?? '') === $g) ? 'selected' : ''; ?>><?php echo htmlspecialchars($g); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label" for="student_email">Email <?php echo $req; ?></label>
                <input type="email" name="student_email" id="student_email" class="form-control form-control-sm" required maxlength="100" value="<?php echo $v('student_email'); ?>" autocomplete="email" inputmode="email">
            </div>
            <div class="col-12 col-sm-6 col-md-4">
                <label class="form-label" for="student_phone">Phone <?php echo $req; ?></label>
                <input type="tel" name="student_phone" id="student_phone" class="form-control form-control-sm" required maxlength="20" value="<?php echo $v('student_phone'); ?>" autocomplete="tel">
            </div>
            <div class="col-12 col-sm-6 col-md-4">
                <label class="form-label" for="student_whatsapp">WhatsApp <?php echo $req; ?></label>
                <input type="tel" name="student_whatsapp" id="student_whatsapp" class="form-control form-control-sm" required maxlength="20" value="<?php echo $v('student_whatsapp'); ?>" autocomplete="tel">
            </div>
            <div class="col-12 col-md-6 col-xl-4">
                <label class="form-label" for="student_nic">ID card number (NIC) <?php echo $req; ?></label>
                <input type="text" name="student_nic" id="student_nic" class="form-control form-control-sm" required maxlength="20" placeholder="Example: 123456789V or 12 numbers" value="<?php echo $v('student_nic'); ?>" autocomplete="off">
            </div>
            <div class="col-12 col-sm-6 col-md-4">
                <label class="form-label" for="student_dob">Date of birth <?php echo $req; ?></label>
                <input type="date" name="student_dob" id="student_dob" class="form-control form-control-sm app-input-dob" required
                    min="<?php echo htmlspecialchars($dobMin, ENT_QUOTES, 'UTF-8'); ?>"
                    max="<?php echo htmlspecialchars($dobMax, ENT_QUOTES, 'UTF-8'); ?>"
                    value="<?php echo $v('student_dob'); ?>"
                    title="Tap to open calendar. You must be at least 16 years old.">
                <p class="small text-muted mb-0 mt-1">Tap the field to choose a date. You must be <strong>16 years or older</strong>.</p>
            </div>
            <div class="col-12 col-md-6 col-xl-4">
                <label class="form-label" for="student_language">Language <?php echo $req; ?></label>
                <select name="student_language" id="student_language" class="form-select form-select-sm" required>
                    <option value="">Choose…</option>
                    <?php foreach (['Tamil', 'Sinhala', 'English'] as $opt): ?>
                    <option value="<?php echo htmlspecialchars($opt, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (($old['student_language'] ?? '') === $opt) ? 'selected' : ''; ?>><?php echo htmlspecialchars($opt, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-sm-6 col-md-4">
                <label class="form-label" for="student_religion">Religion <?php echo $req; ?></label>
                <select name="student_religion" id="student_religion" class="form-select form-select-sm" required>
                    <option value="">Choose…</option>
                    <?php foreach (['Hinduism', 'Buddhism', 'Islam', 'Christianity'] as $opt): ?>
                    <option value="<?php echo htmlspecialchars($opt, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (($old['student_religion'] ?? '') === $opt) ? 'selected' : ''; ?>><?php echo htmlspecialchars($opt, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-sm-6 col-md-4">
                <label class="form-label" for="student_blood_group">Blood group <?php echo $req; ?></label>
                <select name="student_blood_group" id="student_blood_group" class="form-select form-select-sm" required>
                    <option value="">Choose…</option>
                    <?php foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $opt): ?>
                    <option value="<?php echo htmlspecialchars($opt, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (($old['student_blood_group'] ?? '') === $opt) ? 'selected' : ''; ?>><?php echo htmlspecialchars($opt, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- Address -->
<div class="card shadow-sm border-0 mb-4" id="section-address">
    <div class="card-body">
        <div class="app-form-section-title"><i class="fas fa-map-marker-alt me-2"></i>Address</div>
        <div class="row app-form-grid g-3">
            <div class="col-12">
                <label class="form-label" for="student_address">Address <?php echo $req; ?></label>
                <textarea name="student_address" id="student_address" class="form-control form-control-sm" rows="3" required><?php echo $v('student_address'); ?></textarea>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label" for="student_province">Province <?php echo $req; ?></label>
                <select name="student_province" id="student_province" class="form-select form-select-sm" required>
                    <option value="">Choose…</option>
                    <?php foreach (array_keys($sl_provinces_districts) as $p): ?>
                    <option value="<?php echo htmlspecialchars($p, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (($old['student_province'] ?? '') === $p) ? 'selected' : ''; ?>><?php echo htmlspecialchars($p, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label" for="student_district">District <?php echo $req; ?></label>
                <select name="student_district" id="student_district" class="form-select form-select-sm" required>
                    <?php
                    $selProv = (string) ($old['student_province'] ?? '');
                    $selDist = (string) ($old['student_district'] ?? '');
                    if ($selProv === '' || !isset($sl_provinces_districts[$selProv])): ?>
                    <option value="">Choose province first…</option>
                    <?php else: ?>
                    <option value="">Choose district…</option>
                    <?php
                        foreach ($sl_provinces_districts[$selProv] as $d):
                    ?>
                    <option value="<?php echo htmlspecialchars($d, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($selDist === $d) ? 'selected' : ''; ?>><?php echo htmlspecialchars($d, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php
                        endforeach;
                    endif;
                    ?>
                </select>
            </div>
            <div class="col-12 col-sm-6 col-md-4">
                <label class="form-label" for="student_zip_code">Post code <?php echo $req; ?></label>
                <input type="text" name="student_zip_code" id="student_zip_code" class="form-control form-control-sm" required maxlength="10" value="<?php echo htmlspecialchars($default_zip, ENT_QUOTES, 'UTF-8'); ?>">
            </div>
        </div>
    </div>
</div>

<!-- Course -->
<?php
$nvqUiLabel = ($application_level ?? '04') === '05' ? '05' : '04';
$dbNvqHint = ($application_level ?? '04') === '05' ? '5' : '4';
?>
<div class="card shadow-sm border-0 mb-4" id="section-course">
    <div class="card-body">
        <div class="app-form-section-title"><i class="fas fa-list-ol me-2"></i>Course choices</div>
        <p class="small text-muted mb-3">You only see departments that have NVQ Level <?php echo htmlspecialchars($nvqUiLabel, ENT_QUOTES, 'UTF-8'); ?> courses. <strong>First choice</strong> is a must. <strong>Second and third</strong> are if you want.</p>
        <div class="vstack gap-4 app-course-pref">
                <?php foreach ([1 => 'First', 2 => 'Second', 3 => 'Third'] as $n => $label): ?>
                <?php $choiceRequired = ((int) $n === 1); ?>
                <div class="border rounded-3 p-3 bg-light bg-opacity-50">
                    <div class="fw-semibold text-primary mb-2 small text-uppercase"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?> choice <?php echo $choiceRequired ? $req : '<span class="text-muted fw-normal">(if you want)</span>'; ?></div>
                    <div class="row g-3 align-items-end">
                            <div class="col-12 col-md-5">
                                <label class="form-label" for="dept_pref_<?php echo (int) $n; ?>">Department <?php echo $choiceRequired ? $req : ''; ?></label>
                                <select name="dept_pref_<?php echo (int) $n; ?>" id="dept_pref_<?php echo (int) $n; ?>" class="form-select form-select-sm js-app-dept" data-pref="<?php echo (int) $n; ?>"<?php if ($choiceRequired) { echo ' required'; } ?>>
                                    <option value="">Loading…</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-7">
                                <label class="form-label" for="course_priority_<?php echo (int) $n; ?>">Course (NVQ <?php echo htmlspecialchars($nvqUiLabel, ENT_QUOTES, 'UTF-8'); ?>) <?php echo $choiceRequired ? $req : ''; ?></label>
                                <select name="course_priority_<?php echo (int) $n; ?>" id="course_priority_<?php echo (int) $n; ?>" class="form-select form-select-sm js-app-course"<?php if ($choiceRequired) { echo ' required'; } ?>>
                                    <option value="">Choose department first</option>
                                </select>
                            </div>
                    </div>
                </div>
                <?php endforeach; ?>
        </div>
        <p class="small text-muted mb-0 mt-3"><i class="fas fa-info-circle me-1"></i>Only NVQ Level <?php echo htmlspecialchars($dbNvqHint, ENT_QUOTES, 'UTF-8'); ?> courses are shown.</p>
    </div>
</div>

<?php if ($isLevel05): ?>
<div class="alert app-form-info-banner small mb-4"><strong>Level 05:</strong> Complete <strong>G.C.E. A/L</strong> in full <strong>or</strong> complete <strong>NVQ Level 4</strong> (course, institute, year). O/L is optional. NVQ is not required when A/L is complete.</div>
<?php endif; ?>

<!-- O/L -->
<div class="card shadow-sm border-0 mb-4" id="section-ol" data-qual-path="school">
    <div class="card-body">
        <div class="app-form-section-title"><i class="fas fa-certificate me-2"></i>O/L exam</div>
        <div class="row app-form-grid g-3 mb-3">
            <div class="col-12 col-sm-6 col-md-4">
                <label class="form-label" for="ol_index_number">Index number <?php echo $isLevel05 ? '' : $req; ?></label>
                <input type="text" name="ol_index_number" id="ol_index_number" class="form-control form-control-sm" maxlength="20" value="<?php echo $v('ol_index_number'); ?>"<?php echo $examAttr; ?>>
            </div>
            <div class="col-12 col-sm-6 col-md-4">
                <label class="form-label" for="ol_exam_year">Year of exam <?php echo $isLevel05 ? '' : $req; ?></label>
                <input type="number" name="ol_exam_year" id="ol_exam_year" class="form-control form-control-sm" min="1990" max="2100" placeholder="Year" value="<?php echo $v('ol_exam_year'); ?>"<?php echo $examAttr; ?>>
            </div>
        </div>
        <div class="row g-2 g-lg-3 app-exam-subjects-grid">
                <?php
                for ($fi = 1; $fi <= 9; $fi++) :
                    $i = $fi;
                    $variant = 'form';
                    $extraAttr = $examAttr;
                    $slotReqHtml = $isLevel05 ? '' : $req;
                    require __DIR__ . '/_ol_subject_slot.php';
                endfor;
                ?>
        </div>
    </div>
</div>

<!-- A/L -->
<div class="card shadow-sm border-0 mb-4" id="section-al" data-qual-path="school">
    <div class="card-body">
        <div class="app-form-section-title"><i class="fas fa-graduation-cap me-2"></i>A/L exam</div>
        <div class="row app-form-grid g-3 mb-3">
            <div class="col-12 col-sm-6 col-md-4">
                <label class="form-label" for="al_index_number">Index number <?php echo $isLevel05 ? '' : $req; ?></label>
                <input type="text" name="al_index_number" id="al_index_number" class="form-control form-control-sm" maxlength="20" value="<?php echo $v('al_index_number'); ?>"<?php echo $examAttr; ?>>
            </div>
            <div class="col-12 col-sm-6 col-md-4">
                <label class="form-label" for="al_exam_year">Year of exam <?php echo $isLevel05 ? '' : $req; ?></label>
                <input type="number" name="al_exam_year" id="al_exam_year" class="form-control form-control-sm" min="1990" max="2100" placeholder="Year" value="<?php echo $v('al_exam_year'); ?>"<?php echo $examAttr; ?>>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label" for="al_stream">Stream (Arts / Science / etc.) <?php echo $isLevel05 ? '' : $req; ?></label>
                <input type="text" name="al_stream" id="al_stream" class="form-control form-control-sm" maxlength="100" value="<?php echo $v('al_stream'); ?>"<?php echo $examAttr; ?>>
            </div>
        </div>
        <div class="row g-2 g-lg-3 app-exam-subjects-grid">
                <?php for ($i = 1; $i <= 3; $i++): $s = sprintf('%02d', $i); ?>
                <div class="col-12 col-lg-6">
                    <div class="app-al-row app-exam-subj-cell h-100">
                        <div class="app-subject-block">
                            <div class="app-subj-title">Subject <?php echo $s; ?> <?php echo $isLevel05 ? '' : $req; ?></div>
                            <div class="app-subj-fields">
                                <input type="text" name="al_subject_name_<?php echo $s; ?>" id="al_subject_name_<?php echo $s; ?>" class="form-control form-control-sm app-subj-name app-exam-input-compact" maxlength="100" value="<?php echo $v('al_subject_name_' . $s); ?>" aria-label="A/L Subject <?php echo $s; ?>"<?php echo $examAttr; ?>>
                                <div class="app-subj-mark-group">
                                    <label class="form-label app-subj-mark-label mb-0" for="al_subject_<?php echo $s; ?>_marks">Mark</label>
                                    <input type="number" name="al_subject_<?php echo $s; ?>_marks" id="al_subject_<?php echo $s; ?>_marks" class="form-control form-control-sm app-mark-input app-exam-input-compact" min="0" max="100" value="<?php echo $v('al_subject_' . $s . '_marks'); ?>"<?php echo $examAttr; ?>>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endfor; ?>
        </div>
    </div>
</div>

<!-- NVQ -->
<div class="card shadow-sm border-0 mb-4" id="section-nvq" data-qual-path="nvq">
    <div class="card-body">
        <div class="app-form-section-title"><i class="fas fa-tools me-2"></i>NVQ</div>
        <?php if ($isLevel05): ?>
        <p class="small text-muted mb-3">Use this part <strong>only</strong> if you apply with an NVQ (not with completed G.C.E. A/L above).</p>
        <?php else: ?>
        <p class="small text-muted mb-3">If you have no NVQ yet, type <strong>N/A</strong> in the text boxes. For year, you can type <strong>2000</strong>.</p>
        <?php endif; ?>
        <div class="row app-form-grid g-3">
                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label" for="nvq_level">NVQ level <?php echo $isLevel05 ? '' : $req; ?></label>
                    <?php if ($isLevel05): ?>
                    <select name="nvq_level" id="nvq_level" class="form-select form-select-sm"<?php echo $examAttr; ?>>
                        <option value="">None — not declaring NVQ</option>
                        <option value="4" <?php echo (($old['nvq_level'] ?? '') === '4') ? 'selected' : ''; ?>>NVQ Level 4</option>
                    </select>
                    <?php else: ?>
                    <select name="nvq_level" id="nvq_level" class="form-select form-select-sm" required>
                        <option value="3" <?php echo (($old['nvq_level'] ?? '') === '3') ? 'selected' : ''; ?>>NVQ Level 3</option>
                    </select>
                    <?php endif; ?>
                </div>
                <div class="col-12 col-md-5">
                    <label class="form-label" for="nvq_course_name">Course <?php echo $isLevel05 ? '' : $req; ?></label>
                    <input type="text" name="nvq_course_name" id="nvq_course_name" class="form-control form-control-sm" maxlength="150" value="<?php echo $v('nvq_course_name'); ?>"<?php echo $examAttr; ?>>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label" for="nvq_institute_name">School / institute <?php echo $isLevel05 ? '' : $req; ?></label>
                    <input type="text" name="nvq_institute_name" id="nvq_institute_name" class="form-control form-control-sm" maxlength="150" value="<?php echo $v('nvq_institute_name'); ?>"<?php echo $examAttr; ?>>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label" for="nvq_year_completed">Year you finished <?php echo $isLevel05 ? '' : $req; ?></label>
                    <input type="number" name="nvq_year_completed" id="nvq_year_completed" class="form-control form-control-sm" min="1900" max="2100" placeholder="Year" value="<?php echo $v('nvq_year_completed'); ?>"<?php echo $examAttr; ?>>
                </div>
        </div>
    </div>
</div>

<!-- Documents -->
<div class="card shadow-sm border-0 mb-4" id="section-documents">
    <div class="card-body">
        <div class="app-form-section-title"><i class="fas fa-file-upload me-2"></i>Upload your documents</div>
        <p class="small text-muted mb-3">PDF or picture (JPG, PNG), each max <strong>5 MB</strong>. Please upload <strong>all</strong> files below.</p>
        <div class="row app-form-grid g-3">
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
                <div class="col-12 col-md-6">
                    <label class="form-label" for="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($lab, ENT_QUOTES, 'UTF-8'); ?> <?php echo $req; ?></label>
                    <input type="file" name="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>" id="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" required>
                </div>
                <?php endforeach; ?>
        </div>
        <p class="small text-muted mb-0 mt-2">If you see an error, choose all files again before you send the form.</p>
    </div>
</div>
