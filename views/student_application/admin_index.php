<?php
/** @var list<array<string, mixed>> $applications */
$applications = $applications ?? [];
/** @var array{total: int, by_level: list<array{level: string, count: int}>, by_district: list<array{label: string, count: int}>, by_course: list<array{label: string, count: int}>} $dashboard_stats */
$dashboard_stats = $dashboard_stats ?? ['total' => 0, 'by_level' => [], 'by_district' => [], 'by_course' => []];

$esc = static function (string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
};

$appBase = rtrim(APP_URL, '/');
$viewUrl = static function (int $id) use ($appBase, $esc): string {
    return $esc($appBase . '/student-applications/view?id=' . $id);
};

$formatSubmitted = static function (?string $createdAt) use ($esc): array {
    if ($createdAt === null || trim($createdAt) === '') {
        return ['order' => '', 'display' => ''];
    }
    $raw = trim($createdAt);
    $ts = strtotime($raw);
    $display = $ts ? date('Y-m-d H:i', $ts) : $raw;
    return ['order' => $esc($raw), 'display' => $esc($display)];
};

$total = (int) ($dashboard_stats['total'] ?? 0);
$byLevel = $dashboard_stats['by_level'] ?? [];
$byDistrict = $dashboard_stats['by_district'] ?? [];
$byCourse = $dashboard_stats['by_course'] ?? [];
$saAdminCss = htmlspecialchars(rtrim(APP_URL, '/') . '/assets/css/student-applications-admin.css', ENT_QUOTES, 'UTF-8');
?>
<link rel="stylesheet" href="<?php echo $saAdminCss; ?>?v=3">
<div class="sa-admin-page container-fluid py-3">
    <header class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h3 mb-0"><i class="fas fa-file-alt me-2 text-primary" aria-hidden="true"></i>Online applications</h1>
        <p class="small text-muted mb-0">Level 04 &amp; 05 · SAO / ADM</p>
    </header>

    <div class="row g-3 mb-4 align-items-stretch">
        <div class="col-md-4 col-lg-3 d-flex">
            <div class="card border-0 shadow-sm h-100 w-100 bg-primary bg-opacity-10">
                <div class="card-body">
                    <div class="text-uppercase small text-muted fw-semibold mb-1">Total applications</div>
                    <div class="display-6 fw-bold text-primary"><?php echo $total; ?></div>
                    <?php if ($byLevel !== []): ?>
                    <div class="mt-2 small" role="list">
                        <?php foreach ($byLevel as $lv): ?>
                        <span class="badge bg-secondary me-1 mb-1" role="listitem">
                            Level <?php echo $esc((string) ($lv['level'] ?? '')); ?>: <?php echo (int) ($lv['count'] ?? 0); ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-8 col-lg-9 d-flex flex-column">
            <div class="row g-3 flex-grow-1 align-items-stretch">
                <div class="col-md-6 d-flex">
                    <section class="card border-0 shadow-sm h-100 w-100 d-flex flex-column" aria-labelledby="sa-dash-district">
                        <div class="card-header py-2 fw-semibold small text-uppercase text-muted" id="sa-dash-district">
                            <i class="fas fa-map-marker-alt me-1 text-primary" aria-hidden="true"></i> By district
                        </div>
                        <div class="card-body p-0 sa-dash-scroll flex-grow-1">
                            <table class="table table-sm table-hover mb-0 small sa-dash-table">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th scope="col">District</th>
                                        <th scope="col" class="text-end">Count</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($byDistrict === []): ?>
                                    <tr><td colspan="2" class="text-muted text-center py-3">No data yet</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($byDistrict as $d): ?>
                                    <tr>
                                        <td><?php echo $esc((string) ($d['label'] ?? '')); ?></td>
                                        <td class="text-end fw-semibold"><?php echo (int) ($d['count'] ?? 0); ?></td>
                                    </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
                <div class="col-md-6 d-flex">
                    <section class="card border-0 shadow-sm h-100 w-100 d-flex flex-column" aria-labelledby="sa-dash-course">
                        <div class="card-header py-2 fw-semibold small text-uppercase text-muted" id="sa-dash-course">
                            <i class="fas fa-graduation-cap me-1 text-primary" aria-hidden="true"></i> By first course choice
                        </div>
                        <div class="card-body p-0 sa-dash-scroll flex-grow-1">
                            <table class="table table-sm table-hover mb-0 small sa-dash-table">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th scope="col">Course (1st preference)</th>
                                        <th scope="col" class="text-end">Count</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($byCourse === []): ?>
                                    <tr><td colspan="2" class="text-muted text-center py-3">No data yet</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($byCourse as $c): ?>
                                    <tr>
                                        <td class="text-break"><?php echo $esc((string) ($c['label'] ?? '')); ?></td>
                                        <td class="text-end fw-semibold"><?php echo (int) ($c['count'] ?? 0); ?></td>
                                    </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>

    <section class="card shadow-sm border-0" aria-labelledby="sa-apps-table-title">
        <div class="card-header border-0 bg-transparent pb-0 pt-3 px-3">
            <h2 class="h6 text-muted mb-0" id="sa-apps-table-title">All applications</h2>
        </div>
        <div class="card-body pt-2">
            <div class="table-responsive">
                <table class="table table-striped table-hover w-100" id="studentApplicationsTable">
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Level</th>
                            <th scope="col">Full name</th>
                            <th scope="col">NIC</th>
                            <th scope="col">District</th>
                            <th scope="col">Email</th>
                            <th scope="col">Phone</th>
                            <th scope="col">Date sent</th>
                            <th scope="col"><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $r):
                            $id = (int) ($r['application_id'] ?? 0);
                            $submitted = $formatSubmitted(isset($r['created_at']) ? (string) $r['created_at'] : null);
                            ?>
                        <tr>
                            <td><?php echo $id; ?></td>
                            <td><?php echo $esc((string) ($r['application_level'] ?? '')); ?></td>
                            <td><?php echo $esc((string) ($r['student_full_name'] ?? '')); ?></td>
                            <td><?php echo $esc((string) ($r['student_nic'] ?? '')); ?></td>
                            <td><?php echo $esc((string) ($r['student_district'] ?? '')); ?></td>
                            <td><?php echo $esc((string) ($r['student_email'] ?? '')); ?></td>
                            <td><?php echo $esc((string) ($r['student_phone'] ?? '')); ?></td>
                            <td data-order="<?php echo $submitted['order']; ?>"><?php echo $submitted['display']; ?></td>
                            <td>
                                <a class="btn btn-sm btn-outline-primary" href="<?php echo $viewUrl($id); ?>">View &amp; documents</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script>
(function () {
  document.addEventListener('DOMContentLoaded', function () {
    if (!window.jQuery || !jQuery.fn.DataTable) return;
    jQuery('#studentApplicationsTable').DataTable({
      order: [[0, 'desc']],
      pageLength: 25,
      lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
      columnDefs: [{ orderable: false, targets: -1 }]
    });
  });
})();
</script>
