<?php
/** @var array<string, mixed> $app */
$app = $app ?? [];
$link = static function (?string $rel): string {
    if (empty($rel)) {
        return '<span class="text-muted">—</span>';
    }
    $url = rtrim(APP_URL, '/') . '/' . str_replace('\\', '/', $rel);
    return '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener">Open</a>';
};
?>
<div class="container-fluid py-3">
    <div class="mb-3">
        <a href="<?php echo htmlspecialchars(rtrim(APP_URL, '/') . '/student-applications', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>All applications</a>
    </div>
    <h1 class="h3 mb-3">Application #<?php echo (int) ($app['application_id'] ?? 0); ?> <span class="badge bg-primary">Level <?php echo htmlspecialchars($app['application_level'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span></h1>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-semibold">Personal</div>
                <div class="card-body small">
                    <table class="table table-sm mb-0">
                        <?php foreach (['student_title' => 'Title', 'student_full_name' => 'Full name', 'student_initial_name' => 'Initials', 'student_gender' => 'Gender', 'student_civil_status' => 'Civil status', 'student_email' => 'Email', 'student_phone' => 'Phone', 'student_whatsapp' => 'WhatsApp', 'student_nic' => 'NIC', 'student_dob' => 'DOB', 'student_language' => 'Language', 'student_religion' => 'Religion', 'student_blood_group' => 'Blood group'] as $k => $lab): ?>
                        <tr><th class="w-40"><?php echo htmlspecialchars($lab); ?></th><td><?php echo htmlspecialchars((string) ($app[$k] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-semibold">Address &amp; preferences</div>
                <div class="card-body small">
                    <p><strong>Address</strong><br><?php echo nl2br(htmlspecialchars((string) ($app['student_address'] ?? ''), ENT_QUOTES, 'UTF-8')); ?></p>
                    <table class="table table-sm mb-0">
                        <?php foreach (['student_zip_code' => 'ZIP', 'student_district' => 'District', 'student_province' => 'Province', 'course_priority_1' => 'Course 1', 'course_priority_2' => 'Course 2', 'course_priority_3' => 'Course 3'] as $k => $lab): ?>
                        <tr><th><?php echo htmlspecialchars($lab); ?></th><td><?php echo htmlspecialchars((string) ($app[$k] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header fw-semibold">O/L &amp; A/L</div>
                <div class="card-body small">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="fw-semibold mb-2">O/L</p>
                            <table class="table table-sm table-bordered">
                                <tr><th>Index</th><td><?php echo htmlspecialchars((string) ($app['ol_index_number'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                                <tr><th>Year</th><td><?php echo htmlspecialchars((string) ($app['ol_exam_year'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                                <?php for ($i = 1; $i <= 9; $i++): $s = sprintf('%02d', $i); ?>
                                <tr>
                                    <th>S<?php echo $i; ?></th>
                                    <td><?php echo htmlspecialchars((string) ($app['ol_subject_name_' . $s] ?? ''), ENT_QUOTES, 'UTF-8'); ?> — <?php echo htmlspecialchars((string) ($app['ol_subject_' . $s . '_marks'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                                <?php endfor; ?>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <p class="fw-semibold mb-2">A/L</p>
                            <table class="table table-sm table-bordered">
                                <tr><th>Index</th><td><?php echo htmlspecialchars((string) ($app['al_index_number'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                                <tr><th>Year</th><td><?php echo htmlspecialchars((string) ($app['al_exam_year'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                                <tr><th>Stream</th><td><?php echo htmlspecialchars((string) ($app['al_stream'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                                <?php for ($i = 1; $i <= 3; $i++): $s = sprintf('%02d', $i); ?>
                                <tr>
                                    <th>S<?php echo $i; ?></th>
                                    <td><?php echo htmlspecialchars((string) ($app['al_subject_name_' . $s] ?? ''), ENT_QUOTES, 'UTF-8'); ?> — <?php echo htmlspecialchars((string) ($app['al_subject_' . $s . '_marks'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                                <?php endfor; ?>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header fw-semibold">NVQ</div>
                <div class="card-body small">
                    <table class="table table-sm mb-0">
                        <?php foreach (['nvq_level' => 'Level', 'nvq_course_name' => 'Course', 'nvq_institute_name' => 'Institute', 'nvq_year_completed' => 'Year'] as $k => $lab): ?>
                        <tr><th><?php echo htmlspecialchars($lab); ?></th><td><?php echo htmlspecialchars((string) ($app[$k] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header fw-semibold">Documents</div>
                <div class="card-body small">
                    <table class="table table-sm mb-0">
                        <?php
                        $docs = [
                            'nic_document_path' => 'NIC',
                            'birth_certificate_path' => 'Birth certificate',
                            'ol_certificate_path' => 'O/L certificate',
                            'al_certificate_path' => 'A/L certificate',
                            'nvq_certificate_path' => 'NVQ certificate',
                            'bank_receipt_path' => 'Bank receipt',
                        ];
                        foreach ($docs as $k => $lab):
                        ?>
                        <tr><th><?php echo htmlspecialchars($lab); ?></th><td><?php echo $link($app[$k] ?? null); ?></td></tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
