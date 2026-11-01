<?php
$h = static function ($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); };
$alumni = $bundle['alumni'][0] ?? null;
?>
<link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/lifecycle.css?v=<?php echo time(); ?>">
<div class="lc-page">
    <div class="card">
        <div class="card-header"><p class="lc-kicker mb-0">Graduate record</p><h5 class="mb-0">Alumni details</h5></div>
        <div class="card-body">
            <?php if (!empty($_SESSION['message'])): ?><div class="alert alert-success"><?php echo $h($_SESSION['message']); unset($_SESSION['message']); ?></div><?php endif; ?>
            <?php if (!empty($_SESSION['error'])): ?><div class="alert alert-danger"><?php echo $h($_SESSION['error']); unset($_SESSION['error']); ?></div><?php endif; ?>
            <div class="lc-block">
                <h3><?php echo $h($student['student_fullname']); ?></h3>
                <p>Registration <?php echo $h($student['student_id']); ?><?php if ($alumni): ?> · Alumni ID <?php echo $h($alumni['alumni_code']); ?> · <?php echo !empty($alumni['verified']) ? 'Verified' : 'Waiting for the office to verify your update'; ?><?php endif; ?></p>
                <p class="text-muted">Updates to your phone, address and employment are checked by the office before they replace the official record.</p>
            </div>
            <form method="post" action="<?php echo APP_URL; ?>/alumni/portal" class="lc-fields">
                <input type="hidden" name="csrf" value="<?php echo $h($csrf); ?>">
                <label><span>Phone</span><input class="form-control" name="contact_phone" value="<?php echo $h($alumni['contact_phone'] ?? ''); ?>"></label>
                <label><span>Email</span><input class="form-control" type="email" name="contact_email" value="<?php echo $h($alumni['contact_email'] ?? $student['student_email']); ?>"></label>
                <label><span>Address</span><input class="form-control" name="contact_address" value="<?php echo $h($alumni['contact_address'] ?? $student['student_address']); ?>"></label>
                <label><span>About you</span><textarea class="form-control" name="bio"><?php echo $h($alumni['bio'] ?? ''); ?></textarea></label>
                <label><span>Organisation</span><input class="form-control" name="organisation"></label>
                <label><span>Designation</span><input class="form-control" name="designation"></label>
                <label><span>Employment type</span><select class="form-select" name="employment_type"><option value="">Select</option><?php foreach ($employment_types as $type): ?><option><?php echo $h($type); ?></option><?php endforeach; ?></select></label>
                <label><span>Location</span><select class="form-select" name="location_scope"><option value="">Select</option><option>Local</option><option>Foreign</option></select></label>
                <div><button class="btn btn-primary" type="submit">Submit for verification</button></div>
            </form>
        </div>
    </div>
</div>
