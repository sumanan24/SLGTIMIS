<?php
/** @var array<string, mixed> $app */
$app = $app ?? [];
/** @var bool $can_delete */
$can_delete = (bool) ($can_delete ?? false);

if (!class_exists('StudentApplicationModel', false)) {
    require_once BASE_PATH . '/models/StudentApplicationModel.php';
}

$esc = static function (string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
};

/** Same order as `StudentApplicationModel::APPLICATION_DETAIL_SELECT`. */
$detailColumns = [
    'application_id', 'application_level', 'student_title', 'student_full_name', 'student_initial_name',
    'student_gender', 'student_civil_status', 'student_email', 'student_phone', 'student_whatsapp', 'student_nic', 'student_dob',
    'student_language', 'student_religion', 'student_blood_group', 'student_address', 'student_zip_code', 'student_district', 'student_province',
    'course_priority_1', 'course_priority_2', 'course_priority_3', 'ol_index_number', 'ol_exam_year',
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

$listUrl = rtrim(APP_URL, '/') . '/student-applications';
$appId = (int) ($app['application_id'] ?? 0);
$appLevel = (string) ($app['application_level'] ?? '');
$_deleteAction = rtrim(APP_URL, '/') . '/student-applications/delete';

$docMediaKind = static function (string $relativePath): string {
    $ext = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));
    return match ($ext) {
        'jpg', 'jpeg', 'png', 'gif', 'webp' => 'image',
        'pdf' => 'pdf',
        default => 'other',
    };
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
            <a class="btn btn-sm btn-outline-primary" href="<?php echo $esc($url); ?>" target="_blank" rel="noopener">Open</a>
            <?php if ($kind === 'image' || $kind === 'pdf'): ?>
            <button type="button" class="btn btn-sm btn-outline-secondary"
                data-bs-toggle="modal" data-bs-target="#saDocReviewModal"
                data-doc-url="<?php echo $esc($url); ?>"
                data-doc-type="<?php echo $esc($kind); ?>"
                data-doc-title="<?php echo $esc($title); ?>">
                Review
            </button>
            <?php endif; ?>
            <a class="btn btn-sm btn-outline-success" href="<?php echo $esc($downloadHref); ?>">
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
$exportDataUrl = rtrim(APP_URL, '/') . '/student-applications/export-data?id=' . $appId;
$exportPdfUrl = rtrim(APP_URL, '/') . '/student-applications/export-pdf?id=' . $appId;
?>
<link rel="stylesheet" href="<?php echo $saAdminCss; ?>?v=3">
<div class="sa-admin-page sa-admin-view container-fluid py-3">
    <div class="sa-view-toolbar">
        <a href="<?php echo $esc($listUrl); ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>All applications</a>
        <span class="badge bg-info text-dark">SAO / ADM</span>
        <div class="sa-export-actions">
            <a class="btn btn-success btn-sm" href="<?php echo $esc($exportDataUrl); ?>" title="CSV: all fields except document file paths">
                <i class="fas fa-file-export me-1" aria-hidden="true"></i>Download application data
            </a>
            <a class="btn btn-outline-danger btn-sm" href="<?php echo $esc($exportPdfUrl); ?>" title="PDF summary: same fields as CSV (no uploads)">
                <i class="fas fa-file-pdf me-1" aria-hidden="true"></i>Download PDF summary
            </a>
            <?php if ($can_delete): ?>
            <form method="post" action="<?php echo $esc($_deleteAction); ?>" class="d-inline"
                  onsubmit="return confirm('Delete application #<?php echo $appId; ?>? This will also remove uploaded documents on the server.');">
                <input type="hidden" name="application_id" value="<?php echo $appId; ?>">
                <button type="submit" class="btn btn-danger btn-sm">
                    <i class="fas fa-trash me-1" aria-hidden="true"></i>Delete
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
    <div class="sa-view-heading">
        <h1 class="h3">Application #<?php echo $appId; ?></h1>
        <span class="badge bg-primary align-middle">Level <?php echo $esc($appLevel); ?></span>
    </div>

    <div class="card shadow-sm border-primary border-opacity-25 sa-view-card">
        <div class="card-header fw-semibold d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span><i class="fas fa-table me-2"></i>Application record</span>
            <span class="small fw-normal text-muted">Columns match <code class="small">student_applications</code></span>
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

<div class="modal fade" id="saDocReviewModal" tabindex="-1" aria-labelledby="saDocReviewTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="saDocReviewTitle">Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 bg-dark bg-opacity-10 text-center">
                <img id="saDocReviewImg" src="" alt="" class="img-fluid d-none p-2" style="max-height:85vh;">
                <iframe id="saDocReviewIframe" title="Document preview" class="d-none w-100 bg-white" style="min-height:80vh;border:0;"></iframe>
                <p id="saDocReviewFallback" class="d-none p-4 text-muted mb-0">Use <strong>Open in new tab</strong> to view this file.</p>
            </div>
            <div class="modal-footer">
                <a id="saDocReviewOpen" href="#" target="_blank" rel="noopener" class="btn btn-primary">Open in new tab</a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
  document.addEventListener('DOMContentLoaded', function () {
    var modalEl = document.getElementById('saDocReviewModal');
    if (!modalEl || typeof bootstrap === 'undefined') return;

    function hideAll() {
      var img = document.getElementById('saDocReviewImg');
      var ifr = document.getElementById('saDocReviewIframe');
      var fb = document.getElementById('saDocReviewFallback');
      if (img) { img.classList.add('d-none'); img.removeAttribute('src'); }
      if (ifr) { ifr.classList.add('d-none'); ifr.removeAttribute('src'); }
      if (fb) fb.classList.add('d-none');
    }

    modalEl.addEventListener('show.bs.modal', function (ev) {
      var btn = ev.relatedTarget;
      if (!btn || !btn.getAttribute) return;
      var url = btn.getAttribute('data-doc-url') || '';
      var typ = btn.getAttribute('data-doc-type') || '';
      var title = btn.getAttribute('data-doc-title') || 'Document';
      var titleEl = document.getElementById('saDocReviewTitle');
      if (titleEl) titleEl.textContent = title;
      var openA = document.getElementById('saDocReviewOpen');
      if (openA) openA.href = url;
      hideAll();
      var img = document.getElementById('saDocReviewImg');
      var ifr = document.getElementById('saDocReviewIframe');
      var fb = document.getElementById('saDocReviewFallback');
      if (typ === 'image' && img) {
        img.src = url;
        img.classList.remove('d-none');
      } else if (typ === 'pdf' && ifr) {
        ifr.src = url;
        ifr.classList.remove('d-none');
      } else if (fb) {
        fb.classList.remove('d-none');
      }
    });

    modalEl.addEventListener('hidden.bs.modal', function () {
      var img = document.getElementById('saDocReviewImg');
      var ifr = document.getElementById('saDocReviewIframe');
      if (img) img.removeAttribute('src');
      if (ifr) ifr.removeAttribute('src');
    });
  });
})();
</script>
