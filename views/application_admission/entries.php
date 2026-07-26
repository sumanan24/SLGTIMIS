<?php
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
$sch = $schedule ?? [];
$isInterview = ($sch['schedule_type'] ?? '') === ApplicationAdmissionScheduleModel::TYPE_INTERVIEW;
$rollCourseCode = $rollCourseCode ?? ApplicationAdmissionScheduleModel::rollIndexCourseCodeFromSchedule($sch);
$rollFormatPrefix = $rollFormatPrefix ?? ApplicationAdmissionScheduleModel::rollNumberPrefixFromSchedule($sch);
$rollFormatSample = $rollFormatSample ?? ApplicationAdmissionScheduleModel::rollNumberFormatSampleFromSchedule($sch);
$entryCount = is_array($entries ?? null) ? count($entries) : 0;
$filterProvinces = ApplicationAdmissionScheduleModel::normalizedProvinceFilters($filter_provinces ?? null);
$provinceFilterLabel = ApplicationAdmissionScheduleModel::provinceFilterLabel($filterProvinces);
$provinceFilterActive = $filterProvinces !== [];
$provinceIsSelected = static function (string $name) use ($filterProvinces): bool {
    foreach ($filterProvinces as $province) {
        if (strcasecmp($province, $name) === 0) {
            return true;
        }
    }

    return false;
};
$provinceOptions = is_array($province_options ?? null) ? $province_options : [];
$pickerCount = is_array($picker ?? null) ? count($picker) : 0;
$pickerUnfilteredCount = (int) ($picker_unfiltered_count ?? $pickerCount);
$entriesUrl = rtrim(APP_URL, '/') . '/application-admission/entries?id=' . (int) ($sch['schedule_id'] ?? 0);
$entriesUrlWithProvinces = static function (array $provinces) use ($entriesUrl): string {
    $url = $entriesUrl;
    foreach ($provinces as $province) {
        $url .= '&province[]=' . rawurlencode($province);
    }

    return $url;
};
$appBase = rtrim(APP_URL, '/');
$admissionCardsBulkUrl = $appBase . '/application-admission/admission-cards-bulk?id=' . (int) ($sch['schedule_id'] ?? 0);
if ($filterProvinces !== []) {
    foreach ($filterProvinces as $province) {
        $admissionCardsBulkUrl .= '&province[]=' . rawurlencode($province);
    }
}
$admissionCardUrl = static function (int $entryId) use ($appBase, $sch): string {
    return $appBase . '/application-admission/admission-card?id=' . (int) ($sch['schedule_id'] ?? 0) . '&entry_id=' . $entryId;
};
$applicationStatusLabel = static function (array $row) use ($e): string {
    $status = strtolower(trim((string) ($row['status'] ?? $row['application_status'] ?? '')));
    if ($status === 'approved') {
        return 'Approved';
    }
    if ($status === 'rejected') {
        return 'Rejected';
    }

    return $status !== '' ? ucfirst($status) : '—';
};
$waByEntry = [];
foreach ($whatsAppRecipients ?? [] as $wr) {
    if (!empty($wr['entry_id'])) {
        $waByEntry[(int) $wr['entry_id']] = $wr;
    }
}
$courseWiseRollSeq = is_array($courseWiseRollSeq ?? null) ? $courseWiseRollSeq : [];
?>
<style>
.admission-entries-page-wrap {
    max-width: 1400px;
    margin: 0 auto;
}

.admission-entries-header {
    margin-bottom: 1.25rem;
}

.admission-entries-header .page-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.35rem;
}

.admission-entries-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.admission-entries-meta .meta-chip {
    font-size: 0.8125rem;
    padding: 0.25rem 0.65rem;
    border-radius: 999px;
    background: #f1f3f5;
    color: #495057;
    border: 1px solid #e9ecef;
}

.admission-entries-actions .btn {
    min-height: 32px;
    font-size: 0.875rem;
}

.admission-entries-summary {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.625rem 1rem;
    margin-bottom: 0.75rem;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    font-size: 0.875rem;
}

.admission-entries-summary strong {
    font-weight: 600;
}

.admission-picker-card {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    overflow: hidden;
    margin-bottom: 1rem;
}

.admission-picker-card .card-header {
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    padding: 0.625rem 1rem;
    font-size: 0.875rem;
    font-weight: 600;
}

.admission-picker-scroll {
    max-height: 220px;
    overflow: auto;
}

.admission-entries-table,
.admission-picker-table {
    table-layout: auto;
    width: 100%;
    margin-bottom: 0;
    border-collapse: separate;
    border-spacing: 0;
}

.admission-entries-table {
    min-width: 72rem;
}

.admission-entries-table col.col-remove { width: 2.5rem; }
.admission-entries-table col.col-no { width: 3rem; }
.admission-entries-table col.col-name { width: 14%; min-width: 9rem; }
.admission-entries-table col.col-nic { width: 8.5rem; }
.admission-entries-table col.col-course { width: 12%; min-width: 8rem; }
.admission-entries-table col.col-province { width: 7rem; }
.admission-entries-table col.col-roll { width: 13.5rem; min-width: 13.5rem; }
.admission-entries-table col.col-card { width: 4.25rem; }
.admission-entries-table col.col-whatsapp { width: 11rem; }
.admission-entries-table col.col-sent { width: 4.5rem; }

.admission-entries-table-readonly {
    min-width: 66rem;
}

.admission-entries-table-readonly col.col-no { width: 3rem; }
.admission-entries-table-readonly col.col-name { width: 16%; min-width: 9rem; }
.admission-entries-table-readonly col.col-nic { width: 8.5rem; }
.admission-entries-table-readonly col.col-course { width: 14%; min-width: 8rem; }
.admission-entries-table-readonly col.col-province { width: 7rem; }
.admission-entries-table-readonly col.col-roll { width: 13.5rem; min-width: 13.5rem; }
.admission-entries-table-readonly col.col-card { width: 4.25rem; }
.admission-entries-table-readonly col.col-sent { width: 4rem; }

.admission-picker-table col.col-pick { width: 40px; }
.admission-picker-table col.col-no { width: 48px; }
.admission-picker-table col.col-name { width: 26%; }
.admission-picker-table col.col-nic { width: 14%; }
.admission-picker-table col.col-province { width: 14%; }
.admission-picker-table col.col-status { width: 5.5rem; }
.admission-entries-table col.col-status { width: 5.5rem; }
.admission-entries-table-readonly col.col-status { width: 5.5rem; }

.admission-status-badge {
    display: inline-block;
    font-size: 0.6875rem;
    font-weight: 600;
    text-transform: capitalize;
    padding: 0.15rem 0.45rem;
    border-radius: 999px;
    line-height: 1.2;
    white-space: nowrap;
}

.admission-status-approved {
    background: #d1e7dd;
    color: #0f5132;
}

.admission-status-rejected {
    background: #f8d7da;
    color: #842029;
}

.admission-province-filter {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 0.75rem;
    margin-bottom: 1rem;
    padding: 0.75rem 1rem;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
}

.admission-province-filter label {
    font-size: 0.8125rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.admission-province-filter .form-select {
    min-width: 12rem;
    font-size: 0.875rem;
}

.admission-province-checkboxes {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem 0.75rem;
    margin-top: 0.35rem;
}

.admission-province-checkboxes .form-check {
    margin: 0;
    min-height: auto;
}

.admission-province-checkboxes .form-check-label {
    font-size: 0.8125rem;
    font-weight: 500;
    cursor: pointer;
}

.admission-entries-table thead th,
.admission-picker-table thead th {
    padding: 0.625rem 0.75rem;
    font-size: 0.6875rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #495057;
    vertical-align: middle;
    border-bottom: 2px solid #dee2e6;
    background-color: #f8f9fa;
    white-space: nowrap;
}

.admission-entries-table tbody td,
.admission-picker-table tbody td {
    padding: 0.5rem 0.75rem;
    vertical-align: middle;
    line-height: 1.35;
    border-bottom: 1px solid #eef1f4;
    font-size: 0.875rem;
}

.admission-entries-table tbody tr:last-child td,
.admission-picker-table tbody tr:last-child td {
    border-bottom: none;
}

.admission-entries-table tbody tr:hover td:not(.admission-wa-sent-cell),
.admission-picker-table tbody tr:hover td {
    background-color: rgba(13, 110, 253, 0.04);
}

.admission-entries-table tr.admission-wa-sent td {
    background-color: #d1e7dd !important;
}

.admission-entries-table tr.admission-wa-sent:hover td {
    background-color: #c3e6cb !important;
}

.admission-entries-table .col-no,
.admission-picker-table .col-no {
    text-align: center;
    color: #6c757d;
}

.admission-entries-table .col-nic {
    font-family: ui-monospace, monospace;
    font-size: 0.8125rem;
    color: #495057;
}

.admission-entries-table .col-course {
    font-size: 0.8125rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.admission-entries-summary code {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.8125rem;
    white-space: nowrap;
    word-break: normal;
    padding: 0.15rem 0.4rem;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
}

.admission-entries-table .col-roll,
.admission-entries-table-readonly .col-roll {
    vertical-align: middle;
    text-align: left;
    white-space: nowrap;
}

.admission-entries-table thead th.col-roll {
    white-space: nowrap;
}

.admission-entries-table .col-roll input.roll-index-input {
    display: block;
    width: 100%;
    min-width: 12.75rem;
    max-width: none;
    box-sizing: border-box;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.75rem;
    letter-spacing: 0.02em;
    line-height: 1.35;
    padding: 0.4rem 0.5rem;
    white-space: nowrap;
    overflow-x: auto;
}

.admission-entries-table-readonly .col-roll .roll-index-readout {
    display: inline-block;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.75rem;
    letter-spacing: 0.02em;
    line-height: 1.35;
    white-space: nowrap;
    padding: 0.15rem 0;
    color: #212529;
}

.admission-entries-table .col-roll input,
.admission-entries-table .col-room input {
    font-size: 0.8125rem;
}

.admission-entries-table .col-whatsapp,
.admission-entries-table .col-sent,
.admission-entries-table .col-remove,
.admission-entries-table .col-card {
    text-align: center;
    vertical-align: middle;
}

.admission-entries-table .col-whatsapp .wa-number {
    display: block;
    font-size: 0.75rem;
    color: #495057;
    word-break: break-all;
    line-height: 1.25;
    margin-bottom: 0.2rem;
}

.admission-entries-table .btn-wa-outline {
    padding: 0.2rem 0.45rem;
    line-height: 1;
}

.admission-entries-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem 0.75rem;
    padding: 1rem 0 0.5rem;
    border-top: 1px solid #eef1f4;
    margin-top: 0.25rem;
}

.admission-entries-toolbar .toolbar-hint {
    flex: 1 1 200px;
    font-size: 0.8125rem;
    color: #6c757d;
    margin: 0;
}

.admission-entries-secondary {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #dee2e6;
}

.admission-public-link {
    font-size: 0.8125rem;
    word-break: break-all;
}

.admission-entries-table-wrap {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    overflow-x: auto;
    overflow-y: hidden;
    -webkit-overflow-scrolling: touch;
}
</style>

<div class="container-fluid px-3 px-md-4 admission-entries-page-wrap">
    <div class="admission-entries-header d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
            <h1 class="page-title"><?php echo $e($sch['title'] ?? 'Schedule'); ?></h1>
            <p class="text-muted small mb-0">Manage applicants on this <?php echo $isInterview ? 'interview' : 'entrance exam'; ?> schedule.</p>
            <div class="admission-entries-meta">
                <span class="meta-chip">NVQ <?php echo $e($sch['application_level'] ?? ''); ?></span>
                <?php if (!empty($sch['course_name'])): ?>
                <span class="meta-chip"><?php echo $e($sch['course_name']); ?></span>
                <?php endif; ?>
                <?php if (!empty($sch['student_language'])): ?>
                <span class="meta-chip"><i class="fas fa-language me-1"></i><?php echo $e($sch['student_language']); ?></span>
                <?php endif; ?>
                <span class="meta-chip"><?php echo $e(ApplicationAdmissionScheduleModel::pathwayLabel(
                    ApplicationAdmissionScheduleModel::normalizePathway($sch['admission_pathway'] ?? null)
                )); ?></span>
                <span class="meta-chip"><i class="far fa-calendar-alt me-1"></i><?php echo $e($sch['schedule_date'] ?? ''); ?></span>
                <span class="meta-chip"><i class="fas fa-map-marker-alt me-1"></i><?php echo $e($sch['venue'] ?? ''); ?></span>
                <?php if (!empty($sch['is_published'])): ?>
                <span class="meta-chip bg-success bg-opacity-10 text-success border-success border-opacity-25">Published</span>
                <?php else: ?>
                <span class="meta-chip">Draft</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="admission-entries-actions d-flex flex-wrap gap-2">
            <a href="<?php echo APP_URL; ?>/application-admission" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back</a>
            <?php if (!empty($canManage)): ?>
            <a href="<?php echo APP_URL; ?>/application-admission/edit?id=<?php echo (int) ($sch['schedule_id'] ?? 0); ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit me-1"></i> Edit schedule</a>
            <?php endif; ?>
            <a href="<?php echo APP_URL; ?>/application-admission/pdf-schedule?id=<?php echo (int) ($sch['schedule_id'] ?? 0); ?>" class="btn btn-sm btn-outline-dark"><i class="fas fa-file-pdf me-1"></i> Schedule PDF</a>
            <?php if ($entryCount > 0): ?>
            <a href="<?php echo $e($admissionCardsBulkUrl); ?>" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" title="Download postal admission cards (name &amp; address on top)"><i class="fas fa-id-card me-1"></i> Admission cards<?php echo $provinceFilterActive ? ' (' . $e($provinceFilterLabel) . ')' : ''; ?></a>
            <?php endif; ?>
            <?php if ($isInterview): ?>
            <a href="<?php echo APP_URL; ?>/application-admission/selection?id=<?php echo (int) ($sch['schedule_id'] ?? 0); ?>" class="btn btn-sm btn-outline-info"><i class="fas fa-list-check me-1"></i> Selection</a>
            <?php else: ?>
            <a href="<?php echo APP_URL; ?>/application-admission/selection?id=<?php echo (int) ($sch['schedule_id'] ?? 0); ?>" class="btn btn-sm btn-outline-info"><i class="fas fa-clipboard-check me-1"></i> Exam results</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success py-2"><?php echo $e($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger py-2"><?php echo $e($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <?php if (!empty($sch['is_published'])): ?>
    <div class="alert alert-info py-2 small mb-3">
        <strong>Public link:</strong>
        <a class="admission-public-link d-inline-block mt-1" href="<?php echo $e($publicUrl ?? '#'); ?>" target="_blank" rel="noopener"><?php echo $e($publicUrl ?? ''); ?></a>
    </div>
    <?php elseif (!empty($canManage) && $entryCount > 0): ?>
    <div class="alert alert-warning py-2 small mb-3">Publish this schedule before sharing the public link on WhatsApp.</div>
    <?php endif; ?>

    <div class="admission-entries-summary">
        <span><strong><?php echo (int) $entryCount; ?></strong> applicant<?php echo $entryCount === 1 ? '' : 's'; ?> on schedule</span>
        <?php if ($provinceFilterActive): ?>
        <span class="text-muted">Showing province<?php echo count($filterProvinces) === 1 ? '' : 's'; ?>: <strong><?php echo $e($provinceFilterLabel); ?></strong></span>
        <?php endif; ?>
        <?php if ($canManage && $provinceFilterActive && $pickerUnfilteredCount !== $pickerCount): ?>
        <span class="text-muted"><?php echo (int) $pickerCount; ?> to add (of <?php echo (int) $pickerUnfilteredCount; ?> eligible)</span>
        <?php endif; ?>
        <span class="text-muted">Roll format: <code><?php echo $e($rollFormatSample); ?></code> <span class="text-muted">(serial continues within department; restarts when department changes)</span></span>
    </div>

    <?php if ($provinceOptions !== []): ?>
    <div class="admission-province-filter">
        <div>
            <label class="d-block">Filter by province</label>
            <div class="form-text mb-1">Tick one or more provinces to load only those students. Applicants are sorted by department, then course, then province.</div>
            <div class="admission-province-checkboxes" id="admission-province-checkboxes">
                <div class="form-check">
                    <input class="form-check-input province-filter-cb" type="checkbox" id="province_filter_all" value="" <?php echo !$provinceFilterActive ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="province_filter_all">All provinces</label>
                </div>
                <?php foreach ($provinceOptions as $provinceOpt): ?>
                <div class="form-check">
                    <input class="form-check-input province-filter-cb province-filter-item" type="checkbox" id="province_filter_<?php echo $e(preg_replace('/[^A-Za-z0-9]+/', '_', $provinceOpt)); ?>" value="<?php echo $e($provinceOpt); ?>" <?php echo $provinceIsSelected($provinceOpt) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="province_filter_<?php echo $e(preg_replace('/[^A-Za-z0-9]+/', '_', $provinceOpt)); ?>"><?php echo $e($provinceOpt); ?></label>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($canManage)): ?>
    <form method="post" action="<?php echo APP_URL; ?>/application-admission/entries-save">
        <input type="hidden" name="schedule_id" value="<?php echo (int) ($sch['schedule_id'] ?? 0); ?>">
        <?php foreach ($filterProvinces as $filterProvinceItem): ?>
        <input type="hidden" name="filter_provinces[]" value="<?php echo $e($filterProvinceItem); ?>">
        <?php endforeach; ?>

        <?php if (!$isInterview && empty($sch['course_name']) && !empty($canManage)): ?>
        <div class="alert alert-warning py-2 small mb-3">
            <strong>Department and course not set.</strong> Use <a href="<?php echo APP_URL; ?>/application-admission/edit?id=<?php echo (int) ($sch['schedule_id'] ?? 0); ?>">Edit schedule</a> to assign them for this exam centre/venue before filtering applicants by course.
        </div>
        <?php endif; ?>

        <?php if (!empty($pickerHint)): ?>
        <div class="alert alert-light border small py-2 mb-3"><?php echo $pickerHint; ?></div>
        <?php endif; ?>
        <?php if (!empty($picker_entrance_fallback) && !empty($picker)): ?>
        <div class="alert alert-warning py-2 small mb-3">
            <strong>No entrance exam yet.</strong> Showing all approved and rejected applicants for this course because no entrance examination schedule exists. Create an entrance exam and mark <em>Selected</em> candidates when you want this interview list to follow exam results.
        </div>
        <?php endif; ?>

        <?php if (!empty($picker)): ?>
        <div class="admission-picker-card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <span>Add applicants (approved or rejected)</span>
                <label class="small mb-0 fw-normal"><input type="checkbox" id="picker-select-all" class="form-check-input me-1"> Select all</label>
            </div>
            <div class="admission-picker-scroll">
                <table class="table admission-picker-table mb-0">
                    <colgroup>
                        <col class="col-pick"><col class="col-no"><col class="col-name"><col class="col-nic"><col class="col-province"><col class="col-status"><col class="col-course">
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="col-pick"></th>
                            <th class="col-no">No.</th>
                            <th class="col-name">Name</th>
                            <th class="col-nic">NIC</th>
                            <th class="col-province">Province</th>
                            <th class="col-status">Status</th>
                            <th class="col-course">Course (1st pref.)</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $pickNo = 0; foreach ($picker as $p): $pickNo++; ?>
                    <tr>
                        <td class="col-pick"><input type="checkbox" class="form-check-input picker-row-cb" name="add_application_ids[]" value="<?php echo (int) $p['application_id']; ?>"></td>
                        <td class="col-no"><?php echo $pickNo; ?></td>
                        <td class="col-name"><?php echo $e($p['student_full_name'] ?? ''); ?></td>
                        <td class="col-nic"><?php echo $e($p['student_nic'] ?? ''); ?></td>
                        <td class="col-province"><?php echo $e($p['student_province'] ?? '—'); ?></td>
                        <td class="col-status"><?php
                            $pStatus = strtolower(trim((string) ($p['status'] ?? '')));
                            $pStatusClass = $pStatus === 'approved' ? 'admission-status-approved' : ($pStatus === 'rejected' ? 'admission-status-rejected' : '');
                        ?><span class="admission-status-badge <?php echo $e($pStatusClass); ?>"><?php echo $e($applicationStatusLabel($p)); ?></span></td>
                        <td class="col-course" title="<?php echo $e($p['course_priority_1'] ?? ''); ?>"><?php echo $e($p['course_priority_1'] ?? ''); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php else: ?>
        <p class="text-muted small mb-3"><?php
            if ($provinceFilterActive) {
                echo 'No eligible applicants to add for ' . (count($filterProvinces) === 1 ? 'province ' : 'provinces ') . $e($provinceFilterLabel) . '.';
            } elseif (!empty($has_entrance_schedule) && (int) ($entrance_selected_count ?? 0) === 0) {
                echo 'No eligible applicants yet. Open the entrance exam for this course, mark candidates as Selected, then return here.';
            } elseif (!empty($pickerHint)) {
                echo 'No eligible applicants to add yet.';
            } else {
                echo 'No more approved or rejected applicants to add for this course/level.';
            }
        ?></p>
        <?php endif; ?>

        <div class="admission-entries-table-wrap" id="admission-entries-table">
            <table class="table admission-entries-table mb-0">
                <colgroup>
                    <col class="col-remove"><col class="col-no"><col class="col-name"><col class="col-nic"><col class="col-province"><col class="col-status"><col class="col-course">
                    <col class="col-roll"><col class="col-card"><col class="col-whatsapp"><col class="col-sent">
                </colgroup>
                <thead>
                    <tr>
                        <th class="col-remove" title="Remove from schedule"></th>
                        <th class="col-no">No.</th>
                        <th class="col-name">Name</th>
                        <th class="col-nic">NIC</th>
                        <th class="col-province">Province</th>
                        <th class="col-status">Status</th>
                        <th class="col-course">Course</th>
                        <th class="col-roll">Roll / Index</th>
                        <th class="col-card"><span class="visually-hidden">Card</span></th>
                        <th class="col-whatsapp">WhatsApp</th>
                        <th class="col-sent">Sent</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($entries)): ?>
                    <tr><td colspan="11" class="text-center text-muted py-4">No applicants on this schedule yet.</td></tr>
                <?php else: ?>
                    <?php $i = 0; foreach ($entries as $row): $i++;
                        $entryId = (int) ($row['entry_id'] ?? 0);
                        $rollSeq = (int) ($courseWiseRollSeq[$entryId] ?? 1);
                        $rollDisplay = ApplicationAdmissionScheduleModel::formatRollNumberForEntry($sch, $row, $rollSeq);
                        $rollPrefix = ApplicationAdmissionScheduleModel::rollNumberPrefixForEntry($sch, $row);
                        $deptKey = ApplicationAdmissionScheduleModel::departmentCodeFromEntry($row);
                        $waSent = !empty($row['whatsapp_sent']);
                        $hideByProvince = !ApplicationAdmissionScheduleModel::rowMatchesProvinceFilter($row, $filterProvinces);
                    ?>
                    <tr class="<?php echo trim(($waSent ? 'admission-wa-sent ' : '') . ($hideByProvince ? 'd-none' : '')); ?>" data-dept-key="<?php echo $e($deptKey); ?>">
                        <td class="col-remove"><input type="checkbox" class="form-check-input" name="remove_entry_ids[]" value="<?php echo (int) $row['entry_id']; ?>" title="Remove"></td>
                        <td class="col-no"><?php echo $i; ?></td>
                        <td class="col-name"><?php echo $e($row['student_full_name'] ?? ''); ?></td>
                        <td class="col-nic"><?php echo $e($row['student_nic'] ?? ''); ?></td>
                        <td class="col-province"><?php echo $e($row['student_province'] ?? '—'); ?></td>
                        <td class="col-status"><?php
                            $rowStatus = strtolower(trim((string) ($row['application_status'] ?? $row['status'] ?? '')));
                            $rowStatusClass = $rowStatus === 'approved' ? 'admission-status-approved' : ($rowStatus === 'rejected' ? 'admission-status-rejected' : '');
                        ?><span class="admission-status-badge <?php echo $e($rowStatusClass); ?>"><?php echo $e($applicationStatusLabel($row)); ?></span></td>
                        <td class="col-course" title="<?php echo $e($row['course_priority_1'] ?? ''); ?>"><?php echo $e($row['course_priority_1'] ?? ''); ?></td>
                        <td class="col-roll"><input type="text" class="form-control form-control-sm roll-index-input" name="entries[<?php echo (int) $row['entry_id']; ?>][roll_number]" value="<?php echo $e($rollDisplay); ?>" data-seq="<?php echo $rollSeq; ?>" data-roll-prefix="<?php echo $e($rollPrefix); ?>" data-dept-key="<?php echo $e($deptKey); ?>"></td>
                        <td class="col-card">
                            <a href="<?php echo $e($admissionCardUrl((int) ($row['entry_id'] ?? 0))); ?>" class="btn btn-outline-primary btn-sm" target="_blank" rel="noopener" title="Download postal admission card"><i class="fas fa-id-card" aria-hidden="true"></i><span class="visually-hidden"> Card</span></a>
                        </td>
                        <td class="col-whatsapp">
                            <?php
                            $waRow = $waByEntry[(int) ($row['entry_id'] ?? 0)] ?? null;
                            $waDisplay = $waRow['display_phone'] ?? '';
                            if ($waDisplay === '') {
                                $waDisplay = trim((string) ($row['student_whatsapp'] ?? ''));
                                if ($waDisplay === '') {
                                    $waDisplay = trim((string) ($row['student_phone'] ?? ''));
                                }
                            }
                            if ($waDisplay !== ''): ?>
                            <span class="wa-number"><?php echo $e($waDisplay); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($sch['is_published']) && $waRow && !empty($waRow['url'])): ?>
                            <a href="<?php echo $e($waRow['url']); ?>" class="btn btn-wa-outline btn-sm" target="_blank" rel="noopener noreferrer" title="Open WhatsApp with schedule and PDF download links"><i class="fab fa-whatsapp" aria-hidden="true"></i><span class="visually-hidden"> Share links</span></a>
                            <?php elseif (empty($sch['is_published'])): ?>
                            <span class="text-muted small d-block" title="Publish the schedule before sharing links">Publish first</span>
                            <?php elseif ($waDisplay === ''): ?>
                            <span class="text-muted small d-block" title="Add phone or WhatsApp on the online application">No number</span>
                            <?php else: ?>
                            <span class="text-muted small d-block" title="Check phone / WhatsApp format on application">Invalid number</span>
                            <?php endif; ?>
                        </td>
                        <td class="col-sent admission-wa-sent-cell">
                            <input type="checkbox" class="form-check-input wa-sent-cb" name="entries[<?php echo (int) $row['entry_id']; ?>][whatsapp_sent]" value="1"
                                   data-entry-id="<?php echo (int) $row['entry_id']; ?>"
                                   data-schedule-id="<?php echo (int) ($sch['schedule_id'] ?? 0); ?>"
                                   <?php echo $waSent ? 'checked' : ''; ?> title="Link sent" autocomplete="off">
                            <span class="wa-sent-hint small text-success d-block" aria-live="polite"></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="admission-entries-toolbar">
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i> Save applicants</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-renumber-rolls"><i class="fas fa-sort-numeric-down me-1"></i> Renumber</button>
            <?php if ($entryCount > 0): ?>
            <a href="<?php echo $e($admissionCardsBulkUrl); ?>" class="btn btn-outline-primary btn-sm" target="_blank" rel="noopener"><i class="fas fa-id-card me-1"></i> Download admission cards<?php echo $provinceFilterActive ? ' (' . $e($provinceFilterLabel) . ')' : ''; ?></a>
            <?php endif; ?>
            <p class="toolbar-hint mb-0"><i class="fab fa-whatsapp text-success"></i> Use <strong>WhatsApp</strong> for links, or <strong>Admission cards</strong> for postal mail (name &amp; address printed on top).</p>
        </div>
    </form>

    <div class="admission-entries-secondary">
        <form method="post" action="<?php echo APP_URL; ?>/application-admission/publish" class="d-inline">
            <input type="hidden" name="schedule_id" value="<?php echo (int) ($sch['schedule_id'] ?? 0); ?>">
            <?php if (empty($sch['is_published'])): ?>
                <input type="hidden" name="action" value="publish">
                <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Publish this schedule? The public link will become active.');"><i class="fas fa-globe me-1"></i> Publish</button>
            <?php else: ?>
                <input type="hidden" name="action" value="unpublish">
                <button type="submit" class="btn btn-outline-warning btn-sm">Unpublish</button>
            <?php endif; ?>
        </form>
        <form method="get" action="<?php echo APP_URL; ?>/application-admission/delete" class="d-inline"
              onsubmit="return confirm('Delete this schedule and all entries?');">
            <input type="hidden" name="id" value="<?php echo (int) ($sch['schedule_id'] ?? 0); ?>">
            <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fas fa-trash-alt me-1"></i> Delete schedule</button>
        </form>
    </div>

    <?php else: ?>

    <div class="admission-entries-table-wrap">
        <table class="table admission-entries-table admission-entries-table-readonly mb-0">
            <colgroup>
                <col class="col-no"><col class="col-name"><col class="col-nic"><col class="col-province"><col class="col-status"><col class="col-course">
                <col class="col-roll"><col class="col-card"><col class="col-whatsapp"><col class="col-sent">
            </colgroup>
            <thead>
                <tr>
                    <th class="col-no">No.</th>
                    <th class="col-name">Name</th>
                    <th class="col-nic">NIC</th>
                    <th class="col-province">Province</th>
                    <th class="col-status">Status</th>
                    <th class="col-course">Course</th>
                    <th class="col-roll">Roll / Index</th>
                    <th class="col-card"><span class="visually-hidden">Card</span></th>
                    <th class="col-whatsapp">WhatsApp</th>
                    <th class="col-sent">Sent</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($entries)): ?>
                <tr><td colspan="10" class="text-center text-muted py-4">No applicants on this schedule yet.</td></tr>
            <?php else: ?>
                <?php $i = 0; foreach ($entries as $row): $i++;
                    $entryId = (int) ($row['entry_id'] ?? 0);
                    $rollSeq = (int) ($courseWiseRollSeq[$entryId] ?? 1);
                    $rollOut = ApplicationAdmissionScheduleModel::formatRollNumberForEntry($sch, $row, $rollSeq);
                    $waRow = $waByEntry[(int) ($row['entry_id'] ?? 0)] ?? null;
                    $waDisplay = $waRow['display_phone'] ?? trim((string) ($row['student_whatsapp'] ?? ''));
                    if ($waDisplay === '') {
                        $waDisplay = trim((string) ($row['student_phone'] ?? ''));
                    }
                    $hideByProvince = !ApplicationAdmissionScheduleModel::rowMatchesProvinceFilter($row, $filterProvinces);
                ?>
                <tr class="<?php echo trim((!empty($row['whatsapp_sent']) ? 'admission-wa-sent ' : '') . ($hideByProvince ? 'd-none' : '')); ?>">
                    <td class="col-no"><?php echo $i; ?></td>
                    <td class="col-name"><?php echo $e($row['student_full_name'] ?? ''); ?></td>
                    <td class="col-nic"><?php echo $e($row['student_nic'] ?? ''); ?></td>
                    <td class="col-province"><?php echo $e($row['student_province'] ?? '—'); ?></td>
                    <td class="col-status"><?php
                        $rowStatus = strtolower(trim((string) ($row['application_status'] ?? $row['status'] ?? '')));
                        $rowStatusClass = $rowStatus === 'approved' ? 'admission-status-approved' : ($rowStatus === 'rejected' ? 'admission-status-rejected' : '');
                    ?><span class="admission-status-badge <?php echo $e($rowStatusClass); ?>"><?php echo $e($applicationStatusLabel($row)); ?></span></td>
                    <td class="col-course" title="<?php echo $e($row['course_priority_1'] ?? ''); ?>"><?php echo $e($row['course_priority_1'] ?? ''); ?></td>
                    <td class="col-roll"><span class="roll-index-readout"><?php echo $e($rollOut); ?></span></td>
                    <td class="col-card">
                        <a href="<?php echo $e($admissionCardUrl((int) ($row['entry_id'] ?? 0))); ?>" class="btn btn-outline-primary btn-sm" target="_blank" rel="noopener" title="Download postal admission card"><i class="fas fa-id-card" aria-hidden="true"></i></a>
                    </td>
                    <td class="col-whatsapp">
                        <?php if ($waDisplay !== ''): ?>
                        <span class="wa-number"><?php echo $e($waDisplay); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($sch['is_published']) && $waRow && !empty($waRow['url'])): ?>
                        <a href="<?php echo $e($waRow['url']); ?>" class="btn btn-wa-outline btn-sm" target="_blank" rel="noopener noreferrer" title="Open WhatsApp with download links"><i class="fab fa-whatsapp" aria-hidden="true"></i></a>
                        <?php elseif ($waDisplay === ''): ?>
                        <span class="text-muted small">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="col-sent"><?php if (!empty($row['whatsapp_sent'])): ?><i class="fas fa-check text-success" title="Sent"></i><?php else: ?><span class="text-muted">—</span><?php endif; ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php endif; ?>
</div>
<?php if (!empty($canManage)): ?>
<script>
(function () {
    var saveUrl = <?php echo json_encode(rtrim(APP_URL, '/') . '/application-admission/mark-whatsapp-sent'); ?>;
    var entriesUrl = <?php echo json_encode($entriesUrl); ?>;

    function formatRollIndex(prefix, seq) {
        var n = Math.max(1, parseInt(seq, 10) || 1);
        var num = String(n);
        while (num.length < 3) {
            num = '0' + num;
        }
        prefix = (prefix || '').replace(/\/+$/, '');
        return prefix ? (prefix + '/' + num) : num;
    }

    var provinceBox = document.getElementById('admission-province-checkboxes');
    if (provinceBox) {
        function buildProvinceFilterUrl() {
            var provinces = [];
            provinceBox.querySelectorAll('.province-filter-item:checked').forEach(function (cb) {
                if (cb.value) {
                    provinces.push(cb.value);
                }
            });
            if (provinces.length === 0) {
                return entriesUrl;
            }
            var url = entriesUrl;
            provinces.forEach(function (province) {
                url += '&province[]=' + encodeURIComponent(province);
            });
            return url;
        }

        provinceBox.addEventListener('change', function (ev) {
            var target = ev.target;
            if (!target || !target.classList || !target.classList.contains('province-filter-cb')) {
                return;
            }
            if (target.id === 'province_filter_all') {
                if (target.checked) {
                    provinceBox.querySelectorAll('.province-filter-item').forEach(function (cb) {
                        cb.checked = false;
                    });
                    window.location.href = entriesUrl;
                } else if (provinceBox.querySelectorAll('.province-filter-item:checked').length === 0) {
                    target.checked = true;
                }
                return;
            }
            var allCb = document.getElementById('province_filter_all');
            if (target.checked) {
                if (allCb) {
                    allCb.checked = false;
                }
            } else if (provinceBox.querySelectorAll('.province-filter-item:checked').length === 0) {
                if (allCb) {
                    allCb.checked = true;
                }
                window.location.href = entriesUrl;
                return;
            }
            window.location.href = buildProvinceFilterUrl();
        });
    }

    var selectAll = document.getElementById('picker-select-all');
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            document.querySelectorAll('.picker-row-cb').forEach(function (cb) {
                cb.checked = selectAll.checked;
            });
        });
    }
    var renumberBtn = document.getElementById('btn-renumber-rolls');
    if (renumberBtn) {
        renumberBtn.addEventListener('click', function () {
            var byDept = {};
            document.querySelectorAll('.roll-index-input').forEach(function (inp) {
                var key = inp.getAttribute('data-dept-key') || '';
                if (!byDept[key]) {
                    byDept[key] = [];
                }
                byDept[key].push(inp);
            });
            Object.keys(byDept).forEach(function (key) {
                byDept[key].forEach(function (inp, idx) {
                    var prefix = inp.getAttribute('data-roll-prefix') || '';
                    inp.value = formatRollIndex(prefix, idx + 1);
                    inp.setAttribute('data-seq', String(idx + 1));
                });
            });
        });
    }

    function saveWaSent(cb) {
        var tr = cb.closest('tr');
        var hint = tr ? tr.querySelector('.wa-sent-hint') : null;
        var entryId = cb.getAttribute('data-entry-id');
        var scheduleId = cb.getAttribute('data-schedule-id');
        if (!entryId || !scheduleId) {
            return;
        }
        if (tr) {
            tr.classList.toggle('admission-wa-sent', cb.checked);
        }
        if (hint) {
            hint.textContent = 'Saving…';
            hint.classList.remove('text-danger');
        }
        cb.disabled = true;
        var body = new URLSearchParams({
            schedule_id: scheduleId,
            entry_id: entryId,
            sent: cb.checked ? '1' : '0'
        });
        fetch(saveUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: body.toString()
        })
            .then(function (r) {
                if (r.status === 401) {
                    throw new Error('Session expired. Please log in again.');
                }
                return r.text().then(function (text) {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        throw new Error('Server error. Could not save sent status.');
                    }
                });
            })
            .then(function (data) {
                if (!data || !data.success) {
                    throw new Error((data && data.error) ? data.error : 'Could not save sent status.');
                }
                if (hint) {
                    hint.textContent = 'Saved';
                    window.setTimeout(function () {
                        if (hint.textContent === 'Saved') {
                            hint.textContent = '';
                        }
                    }, 2000);
                }
            })
            .catch(function (err) {
                cb.checked = !cb.checked;
                if (tr) {
                    tr.classList.toggle('admission-wa-sent', cb.checked);
                }
                if (hint) {
                    hint.textContent = 'Failed';
                    hint.classList.add('text-danger');
                }
                alert(err.message || 'Could not save sent status.');
            })
            .finally(function () {
                cb.disabled = false;
            });
    }

    var entriesTable = document.getElementById('admission-entries-table');
    if (entriesTable) {
        entriesTable.addEventListener('change', function (ev) {
            var t = ev.target;
            if (t && t.classList && t.classList.contains('wa-sent-cb')) {
                saveWaSent(t);
            }
        });
    }
})();
</script>
<?php endif; ?>
