<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title ?? 'Print'); ?> — <?php echo htmlspecialchars(APP_NAME); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(rtrim(APP_URL, '/') . '/assets/css/style.css', ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="print-layout-body">
    <style>
    body.print-layout-body {
        margin: 0;
        padding: 0;
        background: #e9ecef;
    }
    @media print {
        body.print-layout-body {
            background: #fff !important;
            margin: 0 !important;
            padding: 0 !important;
        }
    }
    </style>
    <?php echo $content; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>
