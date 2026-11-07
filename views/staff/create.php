<?php
$h = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$roles = $roles ?? [];
$departments = $departments ?? [];
?>
<style>
.stc {
    --stc-text: #0f172a;
    --stc-muted: #64748b;
    --stc-border: #e2e8f0;
    --stc-label: #334155;
    --stc-navy: #001f3f;
}
.stc-shell { width: 100%; max-width: 860px; margin: 0 auto; }

.stc-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; margin-bottom: 1.25rem; }
.stc-title { font-size: 1.25rem; font-weight: 700; color: var(--stc-text); margin: 0 0 0.25rem; line-height: 1.3; }
.stc-title i { color: #0d6efd; }
.stc-lead { margin: 0; font-size: 0.875rem; color: var(--stc-muted); line-height: 1.45; }
.stc-head .btn { flex-shrink: 0; min-height: 40px; min-width: 96px; display: inline-flex; align-items: center; justify-content: center; }

.stc-panel { background: #fff; border: 1px solid var(--stc-border); border-radius: 0.65rem; overflow: hidden; }
.stc-body { padding: 1.35rem 1.4rem 1.4rem; }

.stc-section {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    margin: 0 0 0.9rem;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: var(--stc-muted);
}
.stc-section::after { content: ""; flex: 1 1 auto; height: 1px; background: var(--stc-border); }
.stc-section + .stc-grid { margin-bottom: 1.5rem; }
.stc-grid + .stc-grid { margin-top: 1rem; }

.stc-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem 1.25rem; align-items: start; }
.stc-full { grid-column: 1 / -1; }
.stc-field { display: flex; flex-direction: column; min-width: 0; }

.stc .form-label { display: block; margin: 0 0 0.4rem; font-size: 0.8125rem; font-weight: 600; line-height: 1.3; color: var(--stc-label); }
.stc .req { color: #dc3545; }
.stc .form-control,
.stc .form-select {
    display: block;
    width: 100%;
    max-width: 100%;
    height: 42px;
    min-height: 42px;
    box-sizing: border-box;
    border-color: #cbd5e1;
    border-radius: 8px;
    font-size: 0.9375rem;
}
.stc .form-control:focus,
.stc .form-select:focus { border-color: var(--stc-navy); box-shadow: 0 0 0 3px rgba(0, 31, 63, 0.12); }
.stc .help-mini { margin: 0.4rem 0 0; font-size: 0.8rem; line-height: 1.4; color: var(--stc-muted); }

.stc-note {
    display: flex;
    align-items: flex-start;
    gap: 0.65rem;
    margin-top: 1.5rem;
    padding: 0.75rem 0.9rem;
    border: 1px solid #bfdbfe;
    background: #eff6ff;
    border-radius: 8px;
    font-size: 0.85rem;
    color: #1e3a8a;
    line-height: 1.45;
}
.stc-note i { margin-top: 0.15rem; }
.stc-note code { font-weight: 700; color: #1e3a8a; background: #dbeafe; padding: 0.05rem 0.4rem; border-radius: 4px; }

.stc-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.5rem;
    margin-top: 1.25rem;
    padding-top: 1rem;
    border-top: 1px solid var(--stc-border);
}
.stc-actions .btn { min-height: 40px; min-width: 130px; display: inline-flex; align-items: center; justify-content: center; }

@media (max-width: 767.98px) {
    .stc.container-fluid { padding-left: 0.75rem !important; padding-right: 0.75rem !important; }
    .stc-head { flex-direction: column; align-items: stretch; }
    .stc-head .btn, .stc-actions .btn { width: 100%; }
    .stc-actions { flex-direction: column-reverse; }
    .stc-grid { grid-template-columns: minmax(0, 1fr); }
    .stc-body { padding: 1.1rem 1rem 1.15rem; }
    .stc .form-control, .stc .form-select { font-size: 16px; }
}
</style>

<div class="container-fluid px-4 py-3 stc">
    <div class="stc-shell">
        <div class="stc-head">
            <div>
                <h1 class="stc-title"><i class="fas fa-user-plus me-2"></i>Add staff</h1>
                <p class="stc-lead">Enter the basic details. Other personal details can be added later in the staff personal file.</p>
            </div>
            <a href="<?php echo APP_URL; ?>/staff" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back
            </a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <div><?php echo $h($error); ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="stc-panel">
            <div class="stc-body">
                <form method="POST" action="<?php echo APP_URL; ?>/staff/create" id="stStaffForm">
                    <div class="stc-section">Identity</div>
                    <div class="stc-grid">
                        <div class="stc-field">
                            <label class="form-label" for="staff_id">Username <span class="req">*</span></label>
                            <input type="text" class="form-control" id="staff_id" name="staff_id" maxlength="64" required
                                   autocomplete="off" autocapitalize="none" spellcheck="false">
                            <div class="help-mini">Used to sign in.</div>
                        </div>
                        <div class="stc-field">
                            <label class="form-label" for="staff_ininame">Name with initials <span class="req">*</span></label>
                            <input type="text" class="form-control" id="staff_ininame" name="staff_ininame" maxlength="100" required
                                   autocomplete="off">
                        </div>
                        <div class="stc-field">
                            <label class="form-label" for="staff_epf">EPF number</label>
                            <input type="text" class="form-control" id="staff_epf" name="staff_epf" maxlength="20" autocomplete="off">
                        </div>
                        <div class="stc-field">
                            <label class="form-label" for="finger_machine_no">Finger machine number</label>
                            <input type="text" class="form-control" id="finger_machine_no" name="finger_machine_no" maxlength="50"
                                   autocomplete="off">
                        </div>
                    </div>

                    <div class="stc-section">Employment</div>
                    <div class="stc-grid">
                        <div class="stc-field">
                            <label class="form-label" for="staff_date_of_join">Date of join</label>
                            <input type="date" class="form-control" id="staff_date_of_join" name="staff_date_of_join"
                                   value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="stc-field">
                            <label class="form-label" for="department_id">Department <span class="req">*</span></label>
                            <select class="form-select" id="department_id" name="department_id" required>
                                <option value="">Select department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $h($dept['department_id']); ?>">
                                        <?php echo $h($dept['department_name']); ?> (<?php echo $h($dept['department_id']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="stc-field">
                            <label class="form-label" for="staff_status">Status <span class="req">*</span></label>
                            <select class="form-select" id="staff_status" name="staff_status" required>
                                <option value="Working" selected>Working</option>
                                <option value="Terminated">Terminated</option>
                                <option value="Resigned">Resigned</option>
                            </select>
                        </div>
                        <div class="stc-field">
                            <label class="form-label" for="staff_type">Staff type <span class="req">*</span></label>
                            <select class="form-select" id="staff_type" name="staff_type" required>
                                <option value="">Select staff type</option>
                                <?php foreach (($staffTypes ?? []) as $typeName): ?>
                                    <option value="<?php echo $h($typeName); ?>"><?php echo $h($typeName); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="stc-field stc-full">
                            <label class="form-label" for="staff_position">Position <span class="req">*</span></label>
                            <select class="form-select" id="staff_position" name="staff_position" required>
                                <option value="">Select position</option>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo $h($role['staff_position_type_id']); ?>">
                                        <?php echo $h($role['staff_position_type_name']); ?> (<?php echo $h($role['staff_position_type_id']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="stc-note">
                        <i class="fas fa-key"></i>
                        <div>The default login password is <code>Slgti@1234</code>. Ask the staff member to change it after the first sign-in.</div>
                    </div>

                    <div class="stc-actions">
                        <a href="<?php echo APP_URL; ?>/staff" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Create staff
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
