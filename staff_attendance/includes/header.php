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
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand" href="<?php echo attendance_escape($base); ?>/dashboard.php">Attendance</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="<?php echo attendance_escape($base); ?>/dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo attendance_escape($base); ?>/list_attendance.php">List</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo attendance_escape($base); ?>/daily_report.php">Daily report</a></li>
            </ul>
        </div>
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
