<?php
require_once BASE_PATH . '/helpers/DeviceAssetHelper.php';
$stripW = DeviceAssetHelper::stripWidthIn();
$stripH = DeviceAssetHelper::labelHeightIn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=<?php echo $stripW; ?>in, height=<?php echo $stripH; ?>in">
    <title><?php echo htmlspecialchars($title ?? 'Label', ENT_QUOTES, 'UTF-8'); ?></title>
    <style>
        @page {
            size: <?php echo $stripW; ?>in <?php echo $stripH; ?>in;
            margin: 0;
        }
        html, body {
            width: <?php echo $stripW; ?>in;
            height: <?php echo $stripH; ?>in;
            margin: 0;
            padding: 0;
            overflow: hidden;
            background: #fff;
        }
        @media print {
            html, body {
                width: <?php echo $stripW; ?>in !important;
                height: <?php echo $stripH; ?>in !important;
                max-width: <?php echo $stripW; ?>in !important;
                max-height: <?php echo $stripH; ?>in !important;
                overflow: hidden !important;
            }
        }
    </style>
</head>
<body class="device-label-print-body">
    <?php echo $content; ?>
</body>
</html>
