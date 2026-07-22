<?php
/** @var array<string, mixed> $app */
$app = $app ?? [];
/** @var bool $staff_exclude_incomplete_drafts SAO/RSA: NIC-only drafts and rows without NIC+birth uploads excluded from lists (ADM / system admin see all). */
$staff_exclude_incomplete_drafts = (bool) ($staff_exclude_incomplete_drafts ?? false);
/** @var bool $can_delete */
$can_delete = (bool) ($can_delete ?? false);
/** @var bool $can_decide SAO / ADM may approve or reject; DIR is view-only */
$can_decide = (bool) ($can_decide ?? false);

if (!class_exists('StudentApplicationModel', false)) {
    require_once BASE_PATH . '/models/StudentApplicationModel.php';
}
if (!class_exists('StudentModel', false)) {
    require_once BASE_PATH . '/models/StudentModel.php';
}

$esc = static function (string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
};

/** Detail grid order; course rows use resolved `department_*` / `course_*` from `StudentApplicationModel::enrichApplicationForStaffExport`. */
$detailColumns = [
    'application_id', 'application_level', 'status', 'student_title', 'student_full_name', 'student_initial_name',
    'student_gender', 'student_civil_status', 'student_email', 'student_phone', 'student_whatsapp', 'student_nic', 'student_dob',
    'student_language', 'student_religion', 'student_blood_group', 'student_address', 'student_zip_code', 'student_district', 'student_province',
    'department_1', 'department_2', 'department_3', 'course_1', 'course_2', 'course_3', 'ol_index_number', 'ol_exam_year',
    'ol_subject_name_01', 'ol_subject_01_marks', 'ol_subject_name_02', 'ol_subject_02_marks', 'ol_subject_name_03', 'ol_subject_03_marks',
    'ol_subject_name_04', 'ol_subject_04_marks', 'ol_subject_name_05', 'ol_subject_05_marks', 'ol_subject_name_06', 'ol_subject_06_marks',
    'ol_subject_name_07', 'ol_subject_07_marks', 'ol_subject_name_08', 'ol_subject_08_marks', 'ol_subject_name_09', 'ol_subject_09_marks',
    'al_index_number', 'al_exam_year', 'al_stream',
    'al_subject_name_01', 'al_subject_01_marks', 'al_subject_name_02', 'al_subject_02_marks', 'al_subject_name_03', 'al_subject_03_marks',
    'nvq_level', 'nvq_course_name', 'nvq_institute_name', 'nvq_year_completed',
    'nic_document_path', 'birth_certificate_path', 'ol_certificate_path', 'al_certificate_path', 'nvq_certificate_path', 'bank_receipt_path', 'created_at',
];

$docLabels = [
    'nic_document_path' => 'NIC copy',
    'birth_certificate_path' => 'Birth certificate',
    'ol_certificate_path' => 'O/L certificate',
    'al_certificate_path' => 'A/L certificate',
    'nvq_certificate_path' => 'NVQ certificate',
    'bank_receipt_path' => 'Bank receipt',
];

$appId = (int) ($app['application_id'] ?? 0);
$appLevel = (string) ($app['application_level'] ?? '');
$waDigits = StudentModel::digitsForWhatsAppMe($app);
$_deleteAction = rtrim(APP_URL, '/') . '/student-applications/delete';
$approveAction = rtrim(APP_URL, '/') . '/student-applications/approve';
$rejectAction = rtrim(APP_URL, '/') . '/student-applications/reject';

$docMediaKind = static function (string $relativePath): string {
    $ext = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));
    // PHP 7.4 compatibility (no match expression).
    if ($ext === 'jpg' || $ext === 'jpeg' || $ext === 'png' || $ext === 'gif' || $ext === 'webp') {
        return 'image';
    }
    if ($ext === 'pdf') {
        return 'pdf';
    }
    return 'other';
};

$formatScalarCell = static function (string $column, string $raw, callable $esc): string {
    if ($column === 'student_address') {
        return nl2br($esc($raw));
    }
    if ($column === 'created_at' && $raw !== '') {
        $ts = strtotime($raw);
        return $esc($ts ? date('Y-m-d H:i:s', $ts) : $raw);
    }
    return $esc($raw);
};

$renderDocumentCell = static function (string $relativePath, string $title, callable $esc, int $applicationId, string $dbColumn) use ($docMediaKind): void {
    $url = StudentApplicationModel::storedUploadPublicUrl($relativePath !== '' ? $relativePath : null);
    $kind = $relativePath !== '' ? $docMediaKind($relativePath) : 'other';
    $downloadHref = rtrim(APP_URL, '/') . '/student-applications/download-document?id=' . $applicationId . '&col=' . rawurlencode($dbColumn);
    ?>
    <div class="sa-doc-preview-row">
        <div class="sa-doc-thumb">
            <?php if ($url && $kind === 'image'): ?>
            <a href="<?php echo $esc($url); ?>" target="_blank" rel="noopener">
                <img src="<?php echo $esc($url); ?>" alt="" class="rounded border bg-white" style="max-height:72px;max-width:140px;object-fit:cover;" loading="lazy">
            </a>
            <?php elseif ($url && $kind === 'pdf'): ?>
            <span class="text-danger d-inline-flex align-items-center justify-content-center" style="width:72px;height:72px;" aria-hidden="true"><i class="fas fa-file-pdf fa-2x"></i></span>
            <?php elseif ($url): ?>
            <span class="text-muted d-inline-flex align-items-center justify-content-center" style="width:72px;height:72px;" aria-hidden="true"><i class="fas fa-file fa-2x"></i></span>
            <?php else: ?>
            <span class="text-muted">—</span>
            <?php endif; ?>
        </div>
        <div class="sa-doc-actions">
            <?php if ($url): ?>
            <a class="btn btn-outline-primary btn-sm" href="<?php echo $esc($url); ?>" target="_blank" rel="noopener">
                <i class="fas fa-external-link-alt me-1" aria-hidden="true"></i>Open
            </a>
            <a class="btn btn-outline-success btn-sm" href="<?php echo $esc($downloadHref); ?>">
                <i class="fas fa-download me-1" aria-hidden="true"></i>Download
            </a>
            <?php else: ?>
            <span class="text-muted">Not uploaded</span>
            <?php endif; ?>
        </div>
    </div>
    <?php
};

$saAdminCss = htmlspecialchars(rtrim(APP_URL, '/') . '/assets/css/student-applications-admin.css', ENT_QUOTES, 'UTF-8');
$exportPdfUrl = rtrim(APP_URL, '/') . '/student-applications/export-pdf?id=' . $appId;
$editUrl = rtrim(APP_URL, '/') . '/student-applications/edit?id=' . $appId;
$updateReasonAction = rtrim(APP_URL, '/') . '/student-applications/update-rejection-reason';
$st = strtolower(trim((string) ($app['status'] ?? 'new')));
$rejectionReason = trim((string) ($app['rejection_reason'] ?? ''));
$listUrl = $st === 'rejected'
    ? rtrim(APP_URL, '/') . '/student-applications?tab=rejected'
    : rtrim(APP_URL, '/') . '/student-applications?tab=new';
/** @var bool $can_edit ADM / system admin */
$can_edit = (bool) ($can_edit ?? false);
?>
<link rel="stylesheet" href="<?php echo $saAdminCss; ?>?v=7">
<div class="sa-admin-page sa-admin-view container-fluid py-3">
    <form id="sa-view-form-approve" method="post" action="<?php echo $esc($approveAction); ?>" class="d-none" onsubmit="return confirm('Approve application #<?php echo $appId; ?>?');">
        <input type="hidden" name="application_id" value="<?php echo $appId; ?>">
    </form>
    <?php if ($can_decide && in_array($st, ['new', 'approved'], true)): ?>
    <div class="modal fade" id="saRejectModal" tabindex="-1" aria-labelledby="saRejectModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="sa-view-form-reject" method="post" action="<?php echo $esc($rejectAction); ?>" class="modal-content">
                <input type="hidden" name="application_id" value="<?php echo $appId; ?>">
                <div class="modal-header">
                    <h2 class="modal-title h5" id="saRejectModalLabel">Reject application #<?php echo $appId; ?></h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-2">The applicant may be notified separately. This reason is stored on the application record.</p>
                    <label class="form-label fw-semibold" for="sa_rejection_reason">Reason for rejection <span class="text-danger">*</span></label>
                    <textarea class="form-control" name="rejection_reason" id="sa_rejection_reason" rows="4" required maxlength="2000" placeholder="Enter a clear reason…"><?php echo $esc(trim((string) ($app['rejection_reason'] ?? ''))); ?></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Reject application</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
    <?php if ($can_delete): ?>
    <form id="sa-view-form-delete" method="post" action="<?php echo $esc($_deleteAction); ?>" class="d-none" onsubmit="return confirm('Delete application #<?php echo $appId; ?>? This will also remove uploaded documents on the server.');">
        <input type="hidden" name="application_id" value="<?php echo $appId; ?>">
    </form>
    <?php endif; ?>
    <div class="sa-view-toolbar">
        <a href="<?php echo $esc($listUrl); ?>" class="btn btn-outline-secondary btn-sm" title="Back to all applications">
            <i class="fas fa-arrow-left" aria-hidden="true"></i><span class="visually-hidden"> All applications</span>
        </a>
        <span class="badge <?php echo $staff_exclude_incomplete_drafts ? 'bg-secondary' : 'bg-info text-dark'; ?>"><?php echo $staff_exclude_incomplete_drafts ? 'NIC updated · full docs only' : 'Admin · all records'; ?></span>
        <div class="sa-export-actions">
            <div class="btn-group btn-group-sm" role="group" aria-label="Application actions">
                <a class="btn btn-outline-danger" href="<?php echo $esc($exportPdfUrl); ?>" title="Download PDF summary (fields plus merged uploads)">
                    <i class="fas fa-file-pdf" aria-hidden="true"></i><span class="visually-hidden"> Download PDF summary</span>
                </a>
                <?php if ($can_edit): ?>
                <a class="btn btn-outline-secondary" href="<?php echo $esc($editUrl); ?>" title="Edit stored application fields">
                    <i class="fas fa-pen me-1" aria-hidden="true"></i>Edit
                </a>
                <?php endif; ?>
                <?php if ($waDigits !== null): ?>
                <a class="btn btn-wa-outline" href="<?php echo $esc('https://wa.me/' . $waDigits); ?>"
                   target="_blank" rel="noopener noreferrer"
                   title="WhatsApp <?php echo $esc($waDigits); ?>">
                    <i class="fab fa-whatsapp" aria-hidden="true"></i><span class="visually-hidden"> WhatsApp applicant</span>
                </a>
                <?php endif; ?>
                <?php if ($can_decide && $st === 'new'): ?>
                <button type="submit" form="sa-view-form-approve" class="btn btn-primary" title="Approve application">
                    <i class="fas fa-check me-1" aria-hidden="true"></i>Approve
                </button>
                <?php endif; ?>
                <?php if ($can_decide && in_array($st, ['new', 'approved'], true)): ?>
                <button type="button" class="btn btn-outline-warning" title="Reject application" data-bs-toggle="modal" data-bs-target="#saRejectModal">
                    <i class="fas fa-times me-1" aria-hidden="true"></i>Reject
                </button>
                <?php endif; ?>
                <?php if ($can_delete): ?>
                <button type="submit" form="sa-view-form-delete" class="btn btn-danger" title="Delete application">
                    <i class="fas fa-trash-alt" aria-hidden="true"></i><span class="visually-hidden"> Delete</span>
                </button>
                <?php endif; ?>
            </div>
            <?php if ($st === 'approved'): ?>
            <span class="badge bg-success align-self-center">Approved</span>
            <?php elseif ($st === 'rejected'): ?>
            <span class="badge bg-danger align-self-center">Rejected</span>
            <?php endif; ?>
        </div>
    </div>
    <?php if (StudentApplicationModel::isSubmittedForStaffReview($app) === false): ?>
    <div class="alert alert-warning py-2 px-3 mb-3 small" role="status">
        <strong>Incomplete registration:</strong> applicant has not finished the online form beyond the NIC step (draft). Student Affairs staff do not see this row in the list; Administrators (ADM) and system admins can open it here.
    </div>
    <?php elseif ($staff_exclude_incomplete_drafts === false && !StudentApplicationModel::hasNicAndBirthCertificateUploaded($app)): ?>
    <div class="alert alert-warning py-2 px-3 mb-3 small" role="status">
        <strong>Missing identity documents:</strong> NIC copy and/or birth certificate is not uploaded. Student Affairs staff do not see this application until both are present; Administrators (ADM) can review it anyway.
    </div>
    <?php endif; ?>
    <?php if ($st === 'rejected'): ?>
    <div class="alert alert-danger border-danger mb-3 sa-view-rejection-reason" role="status">
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
            <div class="flex-grow-1">
                <h2 class="h6 alert-heading mb-2"><i class="fas fa-ban me-1" aria-hidden="true"></i>Rejection reason</h2>
                <?php if ($rejectionReason !== ''): ?>
                <div class="sa-rejection-reason-body mb-0"><?php echo nl2br($esc($rejectionReason)); ?></div>
                <?php else: ?>
                <p class="mb-0 text-danger-emphasis"><em>No reason was recorded.</em> Add one below or from the <a href="<?php echo $esc(rtrim(APP_URL, '/') . '/student-applications?tab=rejected'); ?>" class="alert-link">Rejected</a> applications list.</p>
                <?php endif; ?>
            </div>
            <?php if ($can_decide): ?>
            <button type="button" class="btn btn-sm btn-outline-dark" data-bs-toggle="collapse" data-bs-target="#saUpdateRejectionReason" aria-expanded="false" aria-controls="saUpdateRejectionReason">
                <?php echo $rejectionReason !== '' ? 'Update reason' : 'Add reason'; ?>
            </button>
            <?php endif; ?>
        </div>
        <?php if ($can_decide): ?>
        <div class="collapse mt-3<?php echo $rejectionReason === '' ? ' show' : ''; ?>" id="saUpdateRejectionReason">
            <form method="post" action="<?php echo $esc($updateReasonAction); ?>" class="border-top border-danger border-opacity-25 pt-3">
                <input type="hidden" name="application_id" value="<?php echo $appId; ?>">
                <input type="hidden" name="return_path" value="<?php echo $esc('student-applications/view?id=' . $appId); ?>">
                <label class="form-label fw-semibold small" for="sa_view_rejection_reason">Reason for rejection</label>
                <textarea class="form-control form-control-sm mb-2" name="rejection_reason" id="sa_view_rejection_reason" rows="4" required maxlength="2000" placeholder="Enter a clear reason…"><?php echo $esc($rejectionReason); ?></textarea>
                <button type="submit" class="btn btn-sm btn-danger">Save reason</button>
            </form>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <div class="sa-view-heading">
        <h1 class="h3">Application #<?php echo $appId; ?></h1>
        <span class="badge bg-primary align-middle">Level <?php echo $esc($appLevel); ?></span>
    </div>

    <div class="card shadow-sm border-primary border-opacity-25 sa-view-card">
        <div class="card-header fw-semibold d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span><i class="fas fa-table me-2"></i>Application record</span>
            <span class="small fw-normal text-muted">Course preferences: department name and course name (course code is not shown).</span>
        </div>
        <div class="card-body small p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover mb-0 sa-detail-table">
                    <colgroup>
                        <col class="sa-col-name">
                        <col class="sa-col-value">
                    </colgroup>
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="text-nowrap">Column</th>
                            <th scope="col">Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detailColumns as $col):
                            $raw = isset($app[$col]) ? (string) $app[$col] : '';
                            $isDoc = isset($docLabels[$col]);
                            ?>
                        <tr>
                            <th scope="row" class="text-break">
                                <span class="sa-field-label"><?php echo $esc($col); ?></span>
                                <?php if ($isDoc): ?>
                                <span class="sa-field-hint"><?php echo $esc($docLabels[$col]); ?></span>
                                <?php endif; ?>
                            </th>
                            <td class="text-break<?php echo $isDoc ? ' sa-doc-cell' : ''; ?>">
                                <?php if ($isDoc):
                                    $renderDocumentCell($raw, $docLabels[$col], $esc, $appId, $col);
                                else:
                                    echo $formatScalarCell($col, $raw, $esc);
                                endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
