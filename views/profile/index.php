<?php
$h = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$user = $user ?? null;
$staff = $staff ?? null;
$isStaff = !empty($staff);
$deptLabel = '';
if (!empty($staffDepartment)) {
    $deptLabel = ($staffDepartment['department_name'] ?? '') . ' (' . ($staffDepartment['department_id'] ?? '') . ')';
} elseif ($isStaff) {
    $deptLabel = (string) ($staff['department_id'] ?? '');
}
$val = static function ($key) use ($staff) {
    return $staff[$key] ?? '';
};
$nicIncomplete = $isStaff && preg_match('/^NA[0-9A-F]{12}$/', (string) ($staff['staff_nic'] ?? ''));
$displayName = $isStaff ? (string) ($staff['staff_name'] ?? '') : (string) ($user['user_name'] ?? '');
$initials = '';
foreach (preg_split('/\s+/', trim($displayName), -1, PREG_SPLIT_NO_EMPTY) as $i => $part) {
    if ($i < 2) {
        $initials .= mb_strtoupper(mb_substr($part, 0, 1));
    }
}
$readonlyRows = $isStaff ? [
    'Staff ID' => $val('staff_id'),
    'Department' => $deptLabel,
    'Position' => $val('staff_position'),
    'Staff type' => $val('staff_type'),
    'Status' => $val('staff_status'),
    'Date of join' => $val('staff_date_of_join'),
    'EPF number' => $val('staff_epf'),
    'Finger machine no.' => $val('finger_machine_no'),
] : [];
?>
<style>
.pf { --pf-text:#0f172a; --pf-muted:#64748b; --pf-border:#e2e8f0; --pf-label:#334155; --pf-navy:#001f3f; }
.pf-shell { width:100%; max-width:960px; margin:0 auto; }
.pf-head { display:flex; align-items:center; gap:1rem; margin-bottom:1.25rem; }
.pf-avatar { flex:0 0 56px; width:56px; height:56px; border-radius:50%; background:#e0e7ff; color:#3730a3; display:flex; align-items:center; justify-content:center; font-size:1.2rem; font-weight:700; }
.pf-title { font-size:1.25rem; font-weight:700; color:var(--pf-text); margin:0 0 .2rem; line-height:1.3; }
.pf-lead { margin:0; font-size:.875rem; color:var(--pf-muted); }

.pf-card { background:#fff; border:1px solid var(--pf-border); border-radius:.65rem; margin-bottom:1rem; overflow:hidden; }
.pf-card-head { padding:.85rem 1.25rem; border-bottom:1px solid var(--pf-border); background:#f8fafc; }
.pf-card-head h2 { margin:0; font-size:.95rem; font-weight:700; color:var(--pf-text); }
.pf-card-head h2 i { color:#0d6efd; width:1.2rem; }
.pf-card-head p { margin:.15rem 0 0; font-size:.8rem; color:var(--pf-muted); }
.pf-card-body { padding:1.25rem; }

.pf-grid { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:1rem 1.25rem; align-items:start; }
.pf-grid.three { grid-template-columns:repeat(3, minmax(0,1fr)); }
.pf-full { grid-column:1 / -1; }
.pf-field { display:flex; flex-direction:column; min-width:0; }
.pf .form-label { display:block; margin:0 0 .4rem; font-size:.8125rem; font-weight:600; line-height:1.3; color:var(--pf-label); }
.pf .req { color:#dc3545; }
.pf .form-control, .pf .form-select { display:block; width:100%; max-width:100%; height:42px; min-height:42px; box-sizing:border-box; border-color:#cbd5e1; border-radius:8px; font-size:.9375rem; }
.pf textarea.form-control { height:auto; min-height:84px; resize:vertical; }
.pf .form-control:focus, .pf .form-select:focus { border-color:var(--pf-navy); box-shadow:0 0 0 3px rgba(0,31,63,.12); }
.pf .form-control:disabled, .pf .form-control[readonly] { background:#f1f5f9; color:#475569; }
.pf .help-mini { margin:.4rem 0 0; font-size:.8rem; line-height:1.4; color:var(--pf-muted); }

.pf-ro { display:grid; grid-template-columns:repeat(4, minmax(0,1fr)); gap:.75rem 1rem; }
.pf-ro div { min-width:0; }
.pf-ro dt { font-size:.7rem; font-weight:700; letter-spacing:.05em; text-transform:uppercase; color:var(--pf-muted); margin:0 0 .15rem; }
.pf-ro dd { margin:0; font-size:.9rem; color:var(--pf-text); overflow-wrap:anywhere; }

.pf-notice { display:flex; gap:.65rem; align-items:flex-start; padding:.75rem .95rem; border:1px solid #fde68a; background:#fffbeb; color:#92400e; border-radius:8px; font-size:.85rem; margin-bottom:1rem; line-height:1.45; }
.pf-notice i { margin-top:.15rem; }

.pf-file-link { display:flex; align-items:center; gap:.9rem; padding:.9rem 1.1rem; margin-bottom:1rem; background:#fff; border:1px solid var(--pf-border); border-radius:.65rem; color:var(--pf-text); text-decoration:none; }
.pf-file-link:hover { border-color:#93c5fd; background:#f8fbff; color:var(--pf-text); }
.pf-file-link .ic { flex:0 0 40px; width:40px; height:40px; border-radius:10px; background:#e0e7ff; color:#3730a3; display:flex; align-items:center; justify-content:center; }
.pf-file-link .tx { flex:1 1 auto; min-width:0; line-height:1.35; }
.pf-file-link .tx b { display:block; font-size:.95rem; }
.pf-file-link .tx small { color:var(--pf-muted); font-size:.8rem; }
.pf-file-link > .fa-chevron-right { color:#94a3b8; }
.pf-actions { display:flex; justify-content:flex-end; gap:.5rem; padding:.25rem 0 1rem; }
.pf-actions .btn { min-height:42px; min-width:130px; display:inline-flex; align-items:center; justify-content:center; }

@media (max-width:991.98px) { .pf-ro { grid-template-columns:repeat(2, minmax(0,1fr)); } .pf-grid.three { grid-template-columns:repeat(2, minmax(0,1fr)); } }
@media (max-width:767.98px) {
    .pf.container-fluid { padding-left:.75rem !important; padding-right:.75rem !important; }
    .pf-grid, .pf-grid.three { grid-template-columns:minmax(0,1fr); }
    .pf-card-body { padding:1rem; }
    .pf-actions { flex-direction:column-reverse; } .pf-actions .btn { width:100%; }
    .pf .form-control, .pf .form-select { font-size:16px; }
}
</style>

<div class="container-fluid px-4 py-3 pf">
    <div class="pf-shell">
        <div class="pf-head">
            <div class="pf-avatar" aria-hidden="true"><?php echo $h($initials !== '' ? $initials : '?'); ?></div>
            <div>
                <h1 class="pf-title">My profile</h1>
                <p class="pf-lead"><?php echo $h($displayName); ?><?php echo !empty($user['user_name']) && $isStaff ? ' &middot; ' . $h($user['user_name']) : ''; ?></p>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
                <i class="fas fa-check-circle me-2"></i><div><?php echo $h($message); ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><div><?php echo $h($error); ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($user): ?>
        <?php foreach (($headedDepartments ?? []) as $hd): ?>
            <a href="<?php echo APP_URL; ?>/departments/view?id=<?php echo urlencode($hd['department_id']); ?>" class="pf-file-link">
                <span class="ic" style="background:#dcfce7;color:#166534;"><i class="fas fa-user-tie"></i></span>
                <span class="tx"><b>Head of Department &middot; <?php echo $h($hd['department_name']); ?></b>
                    <small><?php echo $hd['head_type_label'] !== '' ? $h($hd['head_type_label']) . ' head. ' : ''; ?>Open the department page: staff, students, courses and history.</small></span>
                <i class="fas fa-chevron-right"></i>
            </a>
        <?php endforeach; ?>
        <?php if ($isStaff): ?>
            <a href="<?php echo APP_URL; ?>/staff/edit?id=<?php echo urlencode((string) $val('staff_id')); ?>" class="pf-file-link">
                <span class="ic"><i class="fas fa-folder-open"></i></span>
                <span class="tx"><b>My personal file</b>
                    <small>Update your photo, education, training, family details, bank details and documents.</small></span>
                <i class="fas fa-chevron-right"></i>
            </a>
        <?php endif; ?>
        <?php if ($nicIncomplete): ?>
            <div class="pf-notice"><i class="fas fa-info-circle"></i>
                <div>Your profile is not complete yet. Please enter your NIC, date of birth, phone number and address below.</div></div>
        <?php endif; ?>

        <form method="POST" action="<?php echo APP_URL; ?>/profile/update" autocomplete="off">
            <div class="pf-card">
                <div class="pf-card-head"><h2><i class="fas fa-user-circle"></i>Account</h2></div>
                <div class="pf-card-body">
                    <div class="pf-grid">
                        <div class="pf-field">
                            <label class="form-label" for="user_name_ro">Username</label>
                            <input type="text" class="form-control" id="user_name_ro" value="<?php echo $h($user['user_name'] ?? ''); ?>" disabled>
                            <div class="help-mini">Your username cannot be changed.</div>
                        </div>
                        <div class="pf-field">
                            <label class="form-label" for="user_email">Email <span class="req">*</span></label>
                            <input type="email" class="form-control" id="user_email" name="user_email" maxlength="254" required
                                   autocomplete="email" value="<?php echo $h($user['user_email'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($isStaff): ?>
            <div class="pf-card">
                <div class="pf-card-head"><h2><i class="fas fa-id-card"></i>Personal details</h2></div>
                <div class="pf-card-body">
                    <div class="pf-grid">
                        <div class="pf-field">
                            <label class="form-label" for="staff_name">Full name <span class="req">*</span></label>
                            <input type="text" class="form-control" id="staff_name" name="staff_name" maxlength="100" required
                                   value="<?php echo $h($val('staff_name')); ?>">
                        </div>
                        <div class="pf-field">
                            <label class="form-label" for="staff_ininame">Name with initials</label>
                            <input type="text" class="form-control" id="staff_ininame" name="staff_ininame" maxlength="100"
                                   value="<?php echo $h($val('staff_ininame')); ?>">
                        </div>
                    </div>
                    <div class="pf-grid three" style="margin-top:1rem;">
                        <div class="pf-field">
                            <label class="form-label" for="staff_nic">NIC <span class="req">*</span></label>
                            <input type="text" class="form-control" id="staff_nic" name="staff_nic" maxlength="20" required
                                   autocapitalize="characters" spellcheck="false"
                                   value="<?php echo $nicIncomplete ? '' : $h($val('staff_nic')); ?>">
                        </div>
                        <div class="pf-field">
                            <label class="form-label" for="staff_dob">Date of birth</label>
                            <input type="date" class="form-control" id="staff_dob" name="staff_dob" value="<?php echo $h($val('staff_dob')); ?>">
                        </div>
                        <div class="pf-field">
                            <label class="form-label" for="staff_gender">Gender</label>
                            <select class="form-select" id="staff_gender" name="staff_gender">
                                <option value="">Select</option>
                                <?php foreach (['Male', 'Female', 'Transgender'] as $g): ?>
                                    <option value="<?php echo $g; ?>" <?php echo $val('staff_gender') === $g ? 'selected' : ''; ?>><?php echo $g; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pf-card">
                <div class="pf-card-head"><h2><i class="fas fa-address-book"></i>Contact</h2></div>
                <div class="pf-card-body">
                    <div class="pf-grid">
                        <div class="pf-field">
                            <label class="form-label" for="staff_pno">Mobile number</label>
                            <input type="tel" inputmode="tel" autocomplete="tel" class="form-control" id="staff_pno" name="staff_pno" maxlength="16"
                                   value="<?php echo ($val('staff_pno') === '0' || $val('staff_pno') === 0) ? '' : $h($val('staff_pno')); ?>">
                        </div>
                        <div class="pf-field"></div>
                        <div class="pf-field">
                            <label class="form-label" for="staff_address">Permanent address</label>
                            <textarea class="form-control" id="staff_address" name="staff_address" rows="2" maxlength="255"><?php echo $val('staff_address') === '-' ? '' : $h($val('staff_address')); ?></textarea>
                        </div>
                        <div class="pf-field">
                            <label class="form-label" for="staff_current_address">Current address</label>
                            <textarea class="form-control" id="staff_current_address" name="staff_current_address" rows="2" maxlength="255"><?php echo $h($val('staff_current_address')); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pf-card">
                <div class="pf-card-head"><h2><i class="fas fa-phone-alt"></i>Emergency contact</h2></div>
                <div class="pf-card-body">
                    <div class="pf-grid three">
                        <div class="pf-field">
                            <label class="form-label" for="staff_emergency_name">Name</label>
                            <input type="text" class="form-control" id="staff_emergency_name" name="staff_emergency_name" maxlength="100"
                                   value="<?php echo $h($val('staff_emergency_name')); ?>">
                        </div>
                        <div class="pf-field">
                            <label class="form-label" for="staff_emergency_relation">Relationship</label>
                            <input type="text" class="form-control" id="staff_emergency_relation" name="staff_emergency_relation" maxlength="50"
                                   value="<?php echo $h($val('staff_emergency_relation')); ?>">
                        </div>
                        <div class="pf-field">
                            <label class="form-label" for="staff_emergency_phone">Phone</label>
                            <input type="tel" inputmode="tel" class="form-control" id="staff_emergency_phone" name="staff_emergency_phone" maxlength="20"
                                   value="<?php echo $h($val('staff_emergency_phone')); ?>">
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="pf-card">
                <div class="pf-card-head">
                    <h2><i class="fas fa-lock"></i>Change password</h2>
                    <p>Leave these blank to keep your current password.</p>
                </div>
                <div class="pf-card-body">
                    <div class="pf-grid three">
                        <div class="pf-field">
                            <label class="form-label" for="current_password">Current password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" autocomplete="current-password">
                        </div>
                        <div class="pf-field">
                            <label class="form-label" for="new_password">New password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" minlength="6" autocomplete="new-password">
                            <div class="help-mini">At least 6 characters.</div>
                        </div>
                        <div class="pf-field">
                            <label class="form-label" for="confirm_password">Confirm new password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6" autocomplete="new-password">
                        </div>
                    </div>
                </div>
            </div>

            <div class="pf-actions">
                <a href="<?php echo APP_URL; ?>/dashboard" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save changes</button>
            </div>
        </form>

        <?php if ($isStaff): ?>
            <div class="pf-card">
                <div class="pf-card-head">
                    <h2><i class="fas fa-briefcase"></i>Employment details</h2>
                    <p>Managed by the administration. Contact HR if anything here is wrong.</p>
                </div>
                <div class="pf-card-body">
                    <dl class="pf-ro mb-0">
                        <?php foreach ($readonlyRows as $label => $value): ?>
                            <div><dt><?php echo $h($label); ?></dt><dd><?php echo trim((string) $value) !== '' ? $h($value) : '—'; ?></dd></div>
                        <?php endforeach; ?>
                    </dl>
                </div>
            </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    var form = document.querySelector('.pf form');
    if (!form) return;
    var nic = document.getElementById('staff_nic');
    if (nic) nic.addEventListener('input', function () { this.value = this.value.toUpperCase(); });
    form.addEventListener('submit', function (e) {
        var np = document.getElementById('new_password');
        var cp = document.getElementById('confirm_password');
        var cur = document.getElementById('current_password');
        if (np && np.value !== '') {
            if (cur && cur.value === '') { e.preventDefault(); cur.focus(); alert('Enter your current password to change it.'); return; }
            if (cp && cp.value !== np.value) { e.preventDefault(); cp.focus(); alert('New password and confirm password do not match.'); return; }
        }
        var btn = form.querySelector('button[type="submit"]');
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving…'; }
    });
})();
</script>
