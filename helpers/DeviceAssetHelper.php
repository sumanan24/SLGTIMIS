<?php
declare(strict_types=1);

require_once BASE_PATH . '/helpers/StudentIdCardHelper.php';

final class DeviceAssetHelper
{
    public static function qrScanUrl(string $token): string
    {
        return rtrim(APP_URL, '/') . '/devices/qr/' . rawurlencode($token);
    }

    /** QR PNG size tuned for 2"×1" label (≈0.72" square). */
    public const QR_LABEL_PX = 140;

    /** Dompdf custom page: 2 inch × 1 inch in points (72 pt/in). */
    public const LABEL_PAPER_2X1 = [0, 0, 144, 72];

    public static function qrPngDataUri(string $token, ?int $size = null): string
    {
        $size = $size ?? self::QR_LABEL_PX;

        return StudentIdCardHelper::qrPngDataUri(self::qrScanUrl($token), $size, 0);
    }

    /**
     * @param array<string, mixed> $device
     */
    public static function renderQrLabelPdfHtml(array $device, string $qrDataUri): string
    {
        extract(['device' => $device, 'qrDataUri' => $qrDataUri], EXTR_SKIP);
        ob_start();
        include BASE_PATH . '/views/devices/pdf/qr_label.php';

        return (string) ob_get_clean();
    }

    public static function statusBadgeClass(string $status): string
    {
        $map = [
            'available' => 'success',
            'assigned' => 'primary',
            'under_maintenance' => 'warning',
            'damaged' => 'danger',
            'lost' => 'dark',
            'returned' => 'info',
            'retired' => 'secondary',
            'disposed' => 'secondary',
        ];

        return $map[$status] ?? 'secondary';
    }

    public static function warrantyBadgeClass(string $category): string
    {
        $map = [
            'expired' => 'danger',
            'expiring_30' => 'warning',
            'expiring_90' => 'info',
            'valid' => 'success',
        ];

        return $map[$category] ?? 'secondary';
    }
}
