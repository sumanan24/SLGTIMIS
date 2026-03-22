<?php
if (!isset($pageTitle)) {
    $pageTitle = 'Staff Attendance';
}
$base = attendance_base_url();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo attendance_escape($pageTitle); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-primary mb-3">
    <div class="container">
        <a class="navbar-brand" href="<?php echo attendance_escape($base); ?>/dashboard.php">Staff attendance (device)</a>
        <span class="navbar-text small text-white-50 d-none d-md-inline">Use the side menu for all tools.</span>
    </div>
</nav>
<div class="container pb-5">
<?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="alert alert-success alert-dismissible fade show"><?php echo attendance_escape((string) $_SESSION['flash_success']); unset($_SESSION['flash_success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?php echo attendance_escape((string) $_SESSION['flash_error']); unset($_SESSION['flash_error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
