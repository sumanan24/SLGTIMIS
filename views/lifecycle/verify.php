<?php $h = static function ($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }; ?>
<div class="card border-0 shadow-sm mx-auto" style="max-width: 640px;">
    <div class="card-body p-4">
        <p class="text-uppercase small text-muted mb-1">Sri Lanka German Training Institute</p>
        <h1 class="h4">Certificate verification</h1>
        <p>Enter the certificate number printed on the document. This page shows the name, course and status only.</p>
        <form method="get" action="<?php echo APP_URL; ?>/verify/certificate" class="d-flex gap-2">
            <input class="form-control" name="number" value="<?php echo $h($number); ?>" placeholder="SLGTI/CERT/2026/0001" required>
            <button class="btn btn-primary" type="submit">Check</button>
        </form>
        <?php if ($number !== '' && !$certificate): ?>
            <div class="alert alert-warning mt-3 mb-0">No certificate matches that number.</div>
        <?php elseif ($certificate): ?>
            <dl class="row mt-3 mb-0">
                <dt class="col-sm-4">Certificate</dt><dd class="col-sm-8"><?php echo $h($certificate['certificate_no']); ?></dd>
                <dt class="col-sm-4">Name</dt><dd class="col-sm-8"><?php echo $h($certificate['printed_name']); ?></dd>
                <dt class="col-sm-4">Course</dt><dd class="col-sm-8"><?php echo $h($certificate['course_name']); ?></dd>
                <dt class="col-sm-4">Trade</dt><dd class="col-sm-8"><?php echo $h($certificate['trade']); ?></dd>
                <dt class="col-sm-4">NVQ</dt><dd class="col-sm-8"><?php echo $h($certificate['nvq_level']); ?></dd>
                <dt class="col-sm-4">Status</dt><dd class="col-sm-8"><?php echo $h($certificate['status']); ?></dd>
                <dt class="col-sm-4">Issued</dt><dd class="col-sm-8"><?php echo $h($certificate['issuance_date']); ?></dd>
                <dt class="col-sm-4">Awarded by</dt><dd class="col-sm-8"><?php echo $h($certificate['awarding_authority']); ?></dd>
            </dl>
        <?php endif; ?>
    </div>
</div>
