<?php
/**
 * ADM / system admin: edit stored `student_applications` row (document paths not edited here).
 *
 * @var array<string, mixed> $app
 * @var int $application_id
 * @var array<string, string> $course_prefs_old Dept + course keys for course preference script (from API-style flatten).
 */
declare(strict_types=1);

$app = $app ?? [];
$application_id = (int) ($application_id ?? 0);
$course_prefs_old = isset($course_prefs_old) && is_array($course_prefs_old) ? $course_prefs_old : [];

if (!class_exists('StudentApplicationModel', false)) {
    require_once BASE_PATH . '/models/StudentApplicationModel.php';
}

$esc = static function (string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
};

$base = rtrim(APP_URL, '/');
$listUrl = $base . '/student-applications';
$viewUrl = $base . '/student-applications/view?id=' . $application_id;
$formAction = $base . '/student-applications/edit?id=' . $application_id;
$cols = StudentApplicationModel::getStaffEditableColumnNames();
$courseCols = ['course_priority_1', 'course_priority_2', 'course_priority_3'];

$jsOld = [];
for ($n = 1; $n <= 3; $n++) {
    $jsOld['dept_pref_' . $n] = (string) ($course_prefs_old['dept_pref_' . $n] ?? '');
    $jsOld['course_priority_' . $n] = (string) ($course_prefs_old['course_priority_' . $n] ?? '');
}

$selectOptions = [
    'application_level' => ['04' => 'Level 04', '05' => 'Level 05'],
    'status' => ['new' => 'New', 'approved' => 'Approved', 'rejected' => 'Rejected'],
    'student_title' => ['' => '—', 'Mr' => 'Mr', 'Miss' => 'Miss', 'Mrs' => 'Mrs'],
    'student_gender' => ['' => '—', 'Male' => 'Male', 'Female' => 'Female', 'Other' => 'Other'],
    'student_civil_status' => ['' => '—', 'Single' => 'Single', 'Married' => 'Married'],
    'student_language' => ['' => '—', 'Tamil' => 'Tamil', 'Sinhala' => 'Sinhala', 'English' => 'English'],
    'student_religion' => [
        '' => '—',
        'Hinduism' => 'Hinduism',
        'Buddhism' => 'Buddhism',
        'Islam' => 'Islam',
        'Christianity' => 'Christianity',
    ],
    'student_blood_group' => [
        '' => '—',
        'A+' => 'A+', 'A-' => 'A-', 'B+' => 'B+', 'B-' => 'B-',
        'AB+' => 'AB+', 'AB-' => 'AB-', 'O+' => 'O+', 'O-' => 'O-',
    ],
];

$saAdminCss = htmlspecialchars($base . '/assets/css/student-applications-admin.css', ENT_QUOTES, 'UTF-8');
$nvqJs = (isset($app['application_level']) && (string) $app['application_level'] === '05') ? '5' : '4';
?>
<link rel="stylesheet" href="<?php echo $saAdminCss; ?>?v=18">
<div class="sa-admin-page sa-admin-edit container-fluid py-3">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <a href="<?php echo $esc($listUrl); ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1" aria-hidden="true"></i>Applications</a>
            <a href="<?php echo $esc($viewUrl); ?>" class="btn btn-outline-primary btn-sm"><i class="fas fa-eye me-1" aria-hidden="true"></i>View #<?php echo (int) $application_id; ?></a>
        </div>
        <span class="badge bg-info text-dark">ADM · Edit stored fields</span>
    </div>
    <h1 class="h4 mb-3">Edit application #<?php echo (int) $application_id; ?></h1>
    <p class="text-muted small mb-4">Uploaded documents are not changed here. Use <strong>View</strong> to open or download files. Changing NIC or email must stay unique per NVQ level. Course choices use the same department and course lists as the public application form.</p>

    <form method="post" action="<?php echo $esc($formAction); ?>" class="card shadow-sm border-0" id="saAdminAppEditForm">
        <div class="card-body">
            <input type="hidden" name="application_id" value="<?php echo (int) $application_id; ?>">
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" style="min-width:14rem;">Field</th>
                            <th scope="col">Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cols as $col): ?>
                            <?php
                            if (in_array($col, $courseCols, true)) {
                                continue;
                            }
                            $val = array_key_exists($col, $app) && $app[$col] !== null ? (string) $app[$col] : '';
                            $label = str_replace('_', ' ', $col);
                            ?>
                        <tr>
                            <th scope="row" class="text-secondary small text-uppercase fw-semibold"><?php echo $esc($label); ?></th>
                            <td>
                                <?php if (isset($selectOptions[$col])): ?>
                                <select class="form-select form-select-sm" name="<?php echo $esc($col); ?>" id="f_<?php echo $esc($col); ?>">
                                    <?php foreach ($selectOptions[$col] as $optVal => $optLabel): ?>
                                    <option value="<?php echo $esc((string) $optVal); ?>"<?php echo $val === (string) $optVal ? ' selected' : ''; ?>><?php echo $esc($optLabel); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($col === 'application_level'): ?>
                                <p class="form-text small mb-0 mt-1">If you change NVQ level, course lists reload; pick departments and courses again if needed.</p>
                                <?php endif; ?>
                                <?php elseif ($col === 'student_address'): ?>
                                <textarea class="form-control form-control-sm" name="<?php echo $esc($col); ?>" id="f_<?php echo $esc($col); ?>" rows="3"><?php echo $esc($val); ?></textarea>
                                <?php elseif ($col === 'student_dob'): ?>
                                <input type="date" class="form-control form-control-sm" name="<?php echo $esc($col); ?>" id="f_<?php echo $esc($col); ?>" value="<?php echo $esc($val); ?>">
                                <?php elseif (in_array($col, ['ol_exam_year', 'al_exam_year', 'nvq_year_completed'], true)): ?>
                                <input type="number" class="form-control form-control-sm" name="<?php echo $esc($col); ?>" id="f_<?php echo $esc($col); ?>" value="<?php echo $esc($val); ?>" min="1900" max="2100" step="1">
                                <?php else: ?>
                                <input type="text" class="form-control form-control-sm" name="<?php echo $esc($col); ?>" id="f_<?php echo $esc($col); ?>" value="<?php echo $esc($val); ?>">
                                <?php endif; ?>
                            </td>
                        </tr>
                            <?php if ($col === 'student_province'): ?>
                                <?php for ($prefN = 1; $prefN <= 3; $prefN++): ?>
                        <tr class="sa-admin-cp-row">
                            <th scope="row" class="text-secondary small text-uppercase fw-semibold">Course preference <?php echo (int) $prefN; ?></th>
                            <td>
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-5">
                                        <label class="form-label small mb-0" for="dept_pref_<?php echo (int) $prefN; ?>">Department</label>
                                        <select class="form-select form-select-sm" id="dept_pref_<?php echo (int) $prefN; ?>" name="dept_pref_<?php echo (int) $prefN; ?>">
                                            <option value="">Loading…</option>
                                        </select>
                                    </div>
                                    <div class="col-md-7">
                                        <label class="form-label small mb-0" for="course_priority_<?php echo (int) $prefN; ?>">Course</label>
                                        <select class="form-select form-select-sm" name="course_priority_<?php echo (int) $prefN; ?>" id="course_priority_<?php echo (int) $prefN; ?>">
                                            <option value="">Choose department first…</option>
                                        </select>
                                    </div>
                                </div>
                            </td>
                        </tr>
                                <?php endfor; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-4 pt-3 border-top">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1" aria-hidden="true"></i>Save changes</button>
                <a href="<?php echo $esc($viewUrl); ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </div>
    </form>
</div>
<script>
window.APP_BASE = <?php echo json_encode($base, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
window.NVQ_COURSE_LEVEL = <?php echo json_encode($nvqJs, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
window.APP_FORM_OLD = <?php echo json_encode($jsOld, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>;
</script>
<?php require __DIR__ . '/_course_preferences_scripts.php'; ?>
<script>
(function () {
  var lev = document.getElementById('f_application_level');
  if (!lev || typeof window.initAppCoursePreferenceSelects !== 'function') return;
  lev.addEventListener('change', function () {
    window.NVQ_COURSE_LEVEL = lev.value === '05' ? '5' : '4';
    window.APP_FORM_OLD = {
      dept_pref_1: '', dept_pref_2: '', dept_pref_3: '',
      course_priority_1: '', course_priority_2: '', course_priority_3: ''
    };
    window.initAppCoursePreferenceSelects();
  });
})();
</script>
