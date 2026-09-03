<?php
$suggestYear = date('Y') . '/' . ((int) date('Y') + 1);
$app = rtrim((string) APP_URL, '/');
?>
<style>
.ay-create-wrap {
    --ay-border: #e2e8f0;
    --ay-muted: #64748b;
    --ay-text: #0f172a;
    --ay-label: #334155;
}
.ay-create-wrap .ay-head { margin-bottom: 1.25rem; }
.ay-create-wrap .ay-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--ay-text);
    margin: 0 0 .25rem;
    line-height: 1.3;
}
.ay-create-wrap .ay-lead { font-size: .875rem; color: var(--ay-muted); margin: 0; }
.ay-create-wrap .ay-head .btn {
    min-height: 38px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.ay-create-wrap .ay-panel {
    background: #fff;
    border: 1px solid var(--ay-border);
    border-radius: .5rem;
    padding: 0 !important;
    margin: 0 !important;
    box-shadow: none !important;
    overflow: hidden;
}
.ay-create-wrap .ay-panel-body { padding: 1.25rem 1.35rem 1.35rem; }
.ay-create-wrap .ay-section-title {
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .05em;
    text-transform: uppercase;
    color: var(--ay-muted);
    margin: 0 0 .85rem;
}
.ay-create-wrap .form-label {
    font-size: .8125rem;
    font-weight: 600;
    color: var(--ay-label);
    margin-bottom: .35rem;
}
.ay-create-wrap .req { color: #dc3545; }
.ay-create-wrap .help-mini { font-size: .8rem; color: var(--ay-muted); margin-top: .35rem; }
.ay-create-wrap .form-control,
.ay-create-wrap .form-select {
    min-height: 42px;
    width: 100%;
    border-color: #cbd5e1;
}
.ay-create-wrap .ay-year-input {
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    letter-spacing: .04em;
    font-weight: 600;
}

.ay-create-wrap .ay-status {
    display: flex;
    gap: .5rem;
}
.ay-create-wrap .ay-status input { position: absolute; opacity: 0; pointer-events: none; }
.ay-create-wrap .ay-status label {
    flex: 1;
    margin: 0;
    min-height: 42px;
    border: 1px solid #cbd5e1;
    border-radius: .375rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: .4rem;
    font-size: .875rem;
    font-weight: 600;
    color: #475569;
    background: #fff;
    cursor: pointer;
}
.ay-create-wrap .ay-status input:focus-visible + label { box-shadow: 0 0 0 .2rem rgba(13,110,253,.2); }
.ay-create-wrap .ay-status input:checked + label[data-status="Active"] {
    border-color: #198754;
    background: #f0fdf4;
    color: #166534;
}
.ay-create-wrap .ay-status input:checked + label[data-status="Completed"] {
    border-color: #64748b;
    background: #f8fafc;
    color: #334155;
}

.ay-create-wrap .ay-sem-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}
.ay-create-wrap .ay-sem-card {
    border: 1px solid var(--ay-border);
    border-radius: .5rem;
    padding: 1rem 1.1rem 1.15rem;
    background: #f8fafc;
    min-width: 0;
}
.ay-create-wrap .ay-sem-card h3 {
    font-size: .9rem;
    font-weight: 700;
    color: var(--ay-text);
    margin: 0 0 .85rem;
    display: flex;
    align-items: center;
    gap: .5rem;
}
.ay-create-wrap .ay-sem-num {
    width: 1.5rem;
    height: 1.5rem;
    border-radius: 999px;
    background: #0d6efd;
    color: #fff;
    font-size: .75rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.ay-create-wrap .ay-sem-card .form-control { background: #fff; }
.ay-create-wrap .ay-actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: .5rem;
    margin-top: 1.25rem;
    padding-top: 1rem;
    border-top: 1px solid var(--ay-border);
}
.ay-create-wrap .ay-actions .btn {
    min-height: 40px;
    min-width: 120px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

@media (max-width: 767px) {
    .ay-create-wrap.container-fluid { padding-left: .75rem !important; padding-right: .75rem !important; }
    .ay-create-wrap .ay-head .btn { width: 100%; }
    .ay-create-wrap .ay-sem-grid { grid-template-columns: 1fr; }
    .ay-create-wrap .ay-actions { flex-direction: column; }
    .ay-create-wrap .ay-actions .btn { width: 100%; }
}
</style>

<div class="container-fluid px-4 py-3 ay-create-wrap">
    <div class="ay-head d-flex flex-wrap align-items-start justify-content-between gap-2">
        <div>
            <h1 class="ay-title"><i class="fas fa-calendar-plus text-primary me-2"></i>Create academic year</h1>
            <p class="ay-lead">Set the year code, status, and both semester date ranges.</p>
        </div>
        <a href="<?php echo htmlspecialchars($app . '/academic-years'); ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Back
        </a>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <div><?php echo htmlspecialchars($error); ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-12 col-lg-10 col-xl-8">
            <div class="ay-panel">
                <div class="ay-panel-body">
                    <form method="POST" action="<?php echo htmlspecialchars($app . '/academic-years/create'); ?>" id="ayCreateForm">
                        <div class="ay-section-title">Year details</div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="academic_year" class="form-label">Academic year <span class="req">*</span></label>
                                <input type="text" class="form-control ay-year-input" id="academic_year" name="academic_year"
                                       maxlength="11" required placeholder="<?php echo htmlspecialchars($suggestYear); ?>"
                                       autocomplete="off" inputmode="numeric">
                                <div class="help-mini">Use YYYY/YYYY, for example <?php echo htmlspecialchars($suggestYear); ?>.</div>
                            </div>
                            <div class="col-md-6">
                                <span class="form-label d-block">Status <span class="req">*</span></span>
                                <div class="ay-status" role="radiogroup" aria-label="Status">
                                    <input type="radio" name="academic_year_status" id="ayStatusActive" value="Active" checked>
                                    <label for="ayStatusActive" data-status="Active"><i class="fas fa-check-circle"></i> Active</label>
                                    <input type="radio" name="academic_year_status" id="ayStatusCompleted" value="Completed">
                                    <label for="ayStatusCompleted" data-status="Completed"><i class="fas fa-flag-checkered"></i> Completed</label>
                                </div>
                                <div class="help-mini">Active years appear in enrollment dropdowns.</div>
                            </div>
                        </div>

                        <div class="ay-section-title">Semester dates</div>
                        <div class="ay-sem-grid">
                            <section class="ay-sem-card">
                                <h3><span class="ay-sem-num">1</span> First semester</h3>
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <label for="first_semi_start_date" class="form-label">Start <span class="req">*</span></label>
                                        <input type="date" class="form-control" id="first_semi_start_date" name="first_semi_start_date" required>
                                    </div>
                                    <div class="col-sm-6">
                                        <label for="first_semi_end_date" class="form-label">End <span class="req">*</span></label>
                                        <input type="date" class="form-control" id="first_semi_end_date" name="first_semi_end_date" required>
                                    </div>
                                </div>
                            </section>
                            <section class="ay-sem-card">
                                <h3><span class="ay-sem-num">2</span> Second semester</h3>
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <label for="second_semi_start_date" class="form-label">Start <span class="req">*</span></label>
                                        <input type="date" class="form-control" id="second_semi_start_date" name="second_semi_start_date" required>
                                    </div>
                                    <div class="col-sm-6">
                                        <label for="second_semi_end_date" class="form-label">End <span class="req">*</span></label>
                                        <input type="date" class="form-control" id="second_semi_end_date" name="second_semi_end_date" required>
                                    </div>
                                </div>
                            </section>
                        </div>

                        <div class="ay-actions">
                            <a href="<?php echo htmlspecialchars($app . '/academic-years'); ?>" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save academic year</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    function pair(startId, endId) {
        var start = document.getElementById(startId);
        var end = document.getElementById(endId);
        if (!start || !end) return;
        start.addEventListener('change', function () {
            if (start.value) end.min = start.value;
            if (end.value && start.value && end.value < start.value) end.value = start.value;
        });
        end.addEventListener('change', function () {
            if (end.value) start.max = end.value;
        });
    }
    pair('first_semi_start_date', 'first_semi_end_date');
    pair('second_semi_start_date', 'second_semi_end_date');
});
</script>
