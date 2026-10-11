<?php
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
$val = static function (string $key) use ($student): string {
    $v = trim((string) ($student[$key] ?? ''));
    if ($v === '' || $v === '0') {
        return '';
    }
    if ($key === 'student_email' && preg_match('/@slgtimis\.local$/i', $v)) {
        return '';
    }
    return $v;
};
$selected = static function (string $key, string $want) use ($val): string {
    return $val($key) === $want ? ' selected' : '';
};
$relations = ['Father', 'Mother', 'Guardian', 'Brother', 'Sister', 'Spouse', 'Other'];
$currentRelation = $val('student_em_relation');
$provinceDistricts = is_file(BASE_PATH . '/config/sl_provinces_districts.php')
    ? require BASE_PATH . '/config/sl_provinces_districts.php'
    : [];
$districtPostal = is_file(BASE_PATH . '/config/sl_district_postal_codes.php')
    ? require BASE_PATH . '/config/sl_district_postal_codes.php'
    : [];
$provinceOptions = [];
$provinceMap = [];
foreach ($provinceDistricts as $short => $districts) {
    $full = $short . ' Province';
    $provinceOptions[] = $full;
    $provinceMap[$full] = $districts;
    $provinceMap[$short] = $districts;
}
$currentProvince = $val('student_provice');
if ($currentProvince !== '' && stripos($currentProvince, 'Province') === false) {
    $currentProvince .= ' Province';
}
?>
<style>
.cp-page { max-width: 820px; margin: 0 auto; }
.cp-hero {
    background: linear-gradient(135deg, #001f3f 0%, #003366 100%);
    color: #fff; border-radius: 14px; padding: 1rem 1.15rem; margin-bottom: 1rem;
}
.cp-hero h1 { font-size: 1.15rem; font-weight: 700; margin: 0 0 .25rem; }
.cp-step { font-size: .75rem; letter-spacing: .04em; text-transform: uppercase; opacity: .8; }
.cp-card {
    background: #fff; border: 1px solid #e6eaf0; border-radius: 12px;
    box-shadow: 0 1px 4px rgba(0,31,63,.06); padding: 1rem 1.1rem; margin-bottom: 1rem;
}
.cp-card h2 {
    font-size: .95rem; font-weight: 700; color: #001f3f; margin: 0 0 .85rem;
    padding-bottom: .5rem; border-bottom: 1px solid #eef1f4;
}
.cp-sub {
    font-size: .72rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase;
    color: #6c757d; margin: .85rem 0 .45rem;
}
.cp-card h2 + .cp-sub { margin-top: 0; }
.cp-sticky { position: sticky; bottom: 0; background: #f8f9fa; padding: .75rem 0 calc(.75rem + env(safe-area-inset-bottom, 0px)); }
@media (max-width: 576px) {
    .cp-hero h1 { font-size: 1.05rem; }
    .cp-card { padding: .9rem; }
    .form-label { font-size: .8rem; }
    .form-control, .form-select { font-size: .9rem; }
}
</style>

<div class="cp-page">
    <div class="cp-hero">
        <div class="cp-step">Step 2 of 2</div>
        <h1>Complete your personal and parent details</h1>
        <p class="mb-0 small opacity-75">Fill this once before using the student portal. Use English only.</p>
        <div class="small mt-2 opacity-90"><?php echo $e($val('student_fullname')); ?> &middot; <?php echo $e((string) ($student['student_id'] ?? '')); ?></div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger py-2"><?php echo $e((string) $error); ?></div>
    <?php endif; ?>

    <form method="post" action="<?php echo APP_URL; ?>/student/complete-profile" id="completeProfileForm" novalidate>
        <div class="cp-card">
            <h2><i class="fas fa-id-card me-2"></i>Personal information</h2>

            <div class="cp-sub">Identity</div>
            <div class="row g-2">
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Student ID</label>
                    <input class="form-control" value="<?php echo $e((string) ($student['student_id'] ?? '')); ?>" disabled>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">NIC</label>
                    <input class="form-control" value="<?php echo $e((string) ($student['student_nic'] ?? '')); ?>" disabled>
                </div>
            </div>

            <div class="cp-sub">Name</div>
            <div class="row g-2">
                <div class="col-4 col-md-3">
                    <label class="form-label fw-semibold" for="student_title">Title <span class="text-danger">*</span></label>
                    <select class="form-select" id="student_title" name="student_title" required>
                        <option value="">Select</option>
                        <option value="Mr."<?php echo $selected('student_title', 'Mr.'); ?>>Mr.</option>
                        <option value="Mrs."<?php echo $selected('student_title', 'Mrs.'); ?>>Mrs.</option>
                        <option value="Ms."<?php echo $selected('student_title', 'Ms.'); ?>>Ms.</option>
                    </select>
                </div>
                <div class="col-8 col-md-9">
                    <label class="form-label fw-semibold" for="student_fullname">Full name <span class="text-danger">*</span></label>
                    <input class="form-control" id="student_fullname" name="student_fullname" maxlength="255" required data-english-only="1" value="<?php echo $e($val('student_fullname')); ?>">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold" for="student_ininame">Name with initials <span class="text-danger">*</span></label>
                    <input class="form-control" id="student_ininame" name="student_ininame" maxlength="255" required data-english-only="1" value="<?php echo $e($val('student_ininame')); ?>">
                </div>
            </div>

            <div class="cp-sub">Personal</div>
            <div class="row g-2">
                <div class="col-6 col-md-4">
                    <label class="form-label fw-semibold" for="student_gender">Gender <span class="text-danger">*</span></label>
                    <select class="form-select" id="student_gender" name="student_gender" required>
                        <option value="">Select</option>
                        <option value="Male"<?php echo $selected('student_gender', 'Male'); ?>>Male</option>
                        <option value="Female"<?php echo $selected('student_gender', 'Female'); ?>>Female</option>
                    </select>
                </div>
                <div class="col-6 col-md-4">
                    <label class="form-label fw-semibold" for="student_dob">Date of birth <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="student_dob" name="student_dob" required value="<?php echo $e($val('student_dob')); ?>">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold" for="student_civil">Civil status <span class="text-danger">*</span></label>
                    <select class="form-select" id="student_civil" name="student_civil" required>
                        <option value="">Select</option>
                        <option value="Single"<?php echo $selected('student_civil', 'Single'); ?>>Single</option>
                        <option value="Married"<?php echo $selected('student_civil', 'Married'); ?>>Married</option>
                        <option value="Divorced"<?php echo $selected('student_civil', 'Divorced'); ?>>Divorced</option>
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label fw-semibold" for="student_nationality">Language <span class="text-danger">*</span></label>
                    <select class="form-select" id="student_nationality" name="student_nationality" required>
                        <option value="">Select</option>
                        <option value="Sinhala"<?php echo $selected('student_nationality', 'Sinhala'); ?>>Sinhala</option>
                        <option value="Tamil"<?php echo $selected('student_nationality', 'Tamil'); ?>>Tamil</option>
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label fw-semibold" for="student_religion">Religion <span class="text-danger">*</span></label>
                    <select class="form-select" id="student_religion" name="student_religion" required>
                        <option value="">Select</option>
                        <?php foreach (['Hinduism','Buddhism','Christianity','Islam'] as $r): ?>
                            <option value="<?php echo $e($r); ?>"<?php echo $selected('student_religion', $r); ?>><?php echo $e($r); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="cp-sub">Contact</div>
            <div class="row g-2">
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold" for="student_phone">Phone <span class="text-danger">*</span></label>
                    <input type="tel" class="form-control" id="student_phone" name="student_phone" inputmode="numeric" pattern="[0-9]{9,10}" required value="<?php echo $e($val('student_phone')); ?>">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold" for="student_whatsapp">WhatsApp <span class="text-danger">*</span></label>
                    <input class="form-control" id="student_whatsapp" name="student_whatsapp" inputmode="numeric" maxlength="20" required value="<?php echo $e($val('student_whatsapp')); ?>">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold" for="student_email">Email <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="student_email" name="student_email" maxlength="254" required data-english-only="1" value="<?php echo $e($val('student_email')); ?>">
                </div>
            </div>

            <div class="cp-sub">Address</div>
            <div class="row g-2">
                <div class="col-12">
                    <label class="form-label fw-semibold" for="student_address">Address <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="student_address" name="student_address" rows="2" maxlength="255" required data-english-only="1"><?php echo $e($val('student_address')); ?></textarea>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label fw-semibold" for="student_provice">Province <span class="text-danger">*</span></label>
                    <select class="form-select" id="student_provice" name="student_provice" required>
                        <option value="">Select</option>
                        <?php foreach ($provinceOptions as $p): ?>
                            <option value="<?php echo $e($p); ?>"<?php echo $currentProvince === $p ? ' selected' : ''; ?>><?php echo $e($p); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label fw-semibold" for="student_district">District <span class="text-danger">*</span></label>
                    <select class="form-select" id="student_district" name="student_district" required>
                        <option value="">Select province first</option>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label fw-semibold" for="student_zip">ZIP code <span class="text-danger">*</span></label>
                    <input class="form-control" id="student_zip" name="student_zip" inputmode="numeric" required value="<?php echo $e($val('student_zip')); ?>">
                    <div class="form-text">Filled from district</div>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label fw-semibold" for="student_divisions">GS division <span class="text-danger">*</span></label>
                    <input class="form-control" id="student_divisions" name="student_divisions" maxlength="50" required data-english-only="1" value="<?php echo $e($val('student_divisions')); ?>">
                </div>
            </div>
        </div>

        <div class="cp-card">
            <h2><i class="fas fa-users me-2"></i>Parent / guardian information</h2>
            <div class="row g-2">
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold" for="student_em_name">Parent / guardian name <span class="text-danger">*</span></label>
                    <input class="form-control" id="student_em_name" name="student_em_name" maxlength="255" required data-english-only="1" value="<?php echo $e($val('student_em_name')); ?>">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold" for="student_em_relation">Relation <span class="text-danger">*</span></label>
                    <select class="form-select" id="student_em_relation" name="student_em_relation" required>
                        <option value="">Select</option>
                        <?php foreach ($relations as $rel): ?>
                            <option value="<?php echo $e($rel); ?>"<?php echo $currentRelation === $rel ? ' selected' : ''; ?>><?php echo $e($rel); ?></option>
                        <?php endforeach; ?>
                        <?php if ($currentRelation !== '' && !in_array($currentRelation, $relations, true)): ?>
                            <option value="<?php echo $e($currentRelation); ?>" selected><?php echo $e($currentRelation); ?></option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold" for="student_em_phone">Parent / guardian phone <span class="text-danger">*</span></label>
                    <input type="tel" class="form-control" id="student_em_phone" name="student_em_phone" inputmode="numeric" pattern="[0-9]{9,10}" required value="<?php echo $e($val('student_em_phone')); ?>">
                </div>
                <div class="col-12">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="sameAddress">
                        <label class="form-check-label" for="sameAddress">Same as my address</label>
                    </div>
                    <label class="form-label fw-semibold" for="student_em_address">Parent / guardian address <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="student_em_address" name="student_em_address" rows="2" maxlength="255" required data-english-only="1"><?php echo $e($val('student_em_address')); ?></textarea>
                </div>
            </div>
        </div>

        <div class="cp-sticky">
            <button type="submit" class="btn btn-primary w-100 py-2">
                <i class="fas fa-save me-1"></i>Save and continue
            </button>
        </div>
    </form>
</div>

<script>
(function () {
    var provinceDistricts = <?php echo json_encode($provinceMap, JSON_UNESCAPED_UNICODE); ?>;
    var districtPostal = <?php echo json_encode($districtPostal, JSON_UNESCAPED_UNICODE); ?>;
    var savedDistrict = <?php echo json_encode($val('student_district')); ?>;
    var savedZip = <?php echo json_encode($val('student_zip')); ?>;
    var provinceSelect = document.getElementById('student_provice');
    var districtSelect = document.getElementById('student_district');
    var zipInput = document.getElementById('student_zip');

    function fillZip(district, force) {
        if (!zipInput) return;
        var code = district && districtPostal[district] ? districtPostal[district] : '';
        if (!code) {
            if (force) zipInput.value = '';
            return;
        }
        if (force || !String(zipInput.value || '').trim()) {
            zipInput.value = code;
        }
    }

    function loadDistricts(province, selectedDistrict, fillPostal) {
        if (!districtSelect) return;
        var list = provinceDistricts[province] || [];
        districtSelect.innerHTML = '';
        var first = document.createElement('option');
        first.value = '';
        first.textContent = province ? 'Select district' : 'Select province first';
        districtSelect.appendChild(first);
        list.forEach(function (district) {
            var option = document.createElement('option');
            option.value = district;
            option.textContent = district;
            if (selectedDistrict && selectedDistrict === district) option.selected = true;
            districtSelect.appendChild(option);
        });
        if (fillPostal) {
            fillZip(districtSelect.value, true);
        }
    }

    if (provinceSelect && districtSelect) {
        loadDistricts(provinceSelect.value, savedDistrict, false);
        if (savedZip) {
            zipInput.value = savedZip;
        } else {
            fillZip(districtSelect.value, true);
        }
        provinceSelect.addEventListener('change', function () {
            var list = provinceDistricts[this.value] || [];
            loadDistricts(this.value, list[0] || '', true);
        });
        districtSelect.addEventListener('change', function () {
            fillZip(this.value, true);
        });
    }

    var same = document.getElementById('sameAddress');
    var myAddr = document.getElementById('student_address');
    var emAddr = document.getElementById('student_em_address');
    if (same && myAddr && emAddr) {
        same.addEventListener('change', function () {
            if (same.checked) emAddr.value = myAddr.value;
        });
        myAddr.addEventListener('input', function () {
            if (same.checked) emAddr.value = myAddr.value;
        });
    }
    var form = document.getElementById('completeProfileForm');
    if (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    }
})();
</script>
