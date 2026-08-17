<?php
declare(strict_types=1);

require_once BASE_PATH . '/helpers/StudentIdCardHelper.php';

final class DeviceAssetHelper
{
    /** @var array<string, mixed>|null */
    private static ?array $labelConfig = null;

    /** Same QR on left + right sticker in one set. */
    public const QR_LABEL_COPIES = 2;

    /**
     * @return array<string, mixed>
     */
    public static function labelConfig(): array
    {
        if (self::$labelConfig === null) {
            $path = BASE_PATH . '/config/device_label_printer.php';
            self::$labelConfig = file_exists($path) ? (require $path) : [];
        }

        return self::$labelConfig;
    }

    public static function labelsPerSet(): int
    {
        return max(2, (int) (self::labelConfig()['labels_per_set'] ?? 2));
    }

    public static function defaultLabelSets(): int
    {
        return max(1, (int) (self::labelConfig()['default_sets'] ?? 1));
    }

    public static function maxLabelSets(): int
    {
        return max(1, (int) (self::labelConfig()['max_sets'] ?? 50));
    }

    public static function clampLabelSets(int $sets): int
    {
        return max(1, min(self::maxLabelSets(), $sets));
    }

    public static function labelDpi(): int
    {
        return max(100, (int) (self::labelConfig()['dpi'] ?? 203));
    }

    /** PNG QR size for browser/PDF preview. */
    public static function qrPngDataUri(string $token, ?int $size = null): string
    {
        $cfg = self::labelConfig();
        $size = $size ?? (int) ($cfg['qr_png_px'] ?? 320);
        $margin = (int) ($cfg['qr_margin_px'] ?? 4);

        return StudentIdCardHelper::qrPngDataUri(self::qrScanUrl($token), $size, $margin);
    }

    /**
     * @return array<string, float|int|string|bool>
     */
    public static function labelSpec(): array
    {
        $cfg = self::labelConfig();
        $w = (float) ($cfg['label_width_mm'] ?? 50.8);
        $h = (float) ($cfg['label_height_mm'] ?? 25.4);
        $gap = (float) ($cfg['horizontal_gap_mm'] ?? 3.0);
        $pad = (float) ($cfg['inner_padding_mm'] ?? 2.0);
        $qr = (float) ($cfg['qr_size_mm'] ?? 16.0);
        $n = self::labelsPerSet();

        return [
            'label_width_mm' => $w,
            'label_height_mm' => $h,
            'horizontal_gap_mm' => $gap,
            'inner_padding_mm' => $pad,
            'qr_size_mm' => $qr,
            'strip_width_mm' => ($w * $n) + ($gap * max(0, $n - 1)),
            'strip_height_mm' => $h,
            'labels_per_set' => $n,
            'layout' => (string) ($cfg['layout'] ?? 'centered'),
        ];
    }

    public static function mmToIn(float $mm): float
    {
        return $mm / 25.4;
    }

    public static function labelWidthIn(): float
    {
        $cfg = self::labelConfig();
        if (isset($cfg['label_width_mm'])) {
            return self::mmToIn((float) $cfg['label_width_mm']);
        }

        return (float) ($cfg['label_width_in'] ?? 2.0);
    }

    public static function labelHeightIn(): float
    {
        $cfg = self::labelConfig();
        if (isset($cfg['label_height_mm'])) {
            return self::mmToIn((float) $cfg['label_height_mm']);
        }

        return (float) ($cfg['label_height_in'] ?? 1.0);
    }

    public static function horizontalGapIn(): float
    {
        $cfg = self::labelConfig();
        if (isset($cfg['horizontal_gap_mm'])) {
            return self::mmToIn((float) $cfg['horizontal_gap_mm']);
        }

        return max(0.0, (float) ($cfg['horizontal_gap_in'] ?? 0.0));
    }

    public static function sideMarginIn(): float
    {
        return 0.0;
    }

    public static function stripWidthIn(): float
    {
        $spec = self::labelSpec();

        return self::mmToIn((float) $spec['strip_width_mm']);
    }

    /**
     * @param array<string, mixed> $device
     * @return array<string, mixed>
     */
    public static function stickerDataFromDevice(array $device, ?string $qrDataUri = null): array
    {
        $cfg = self::labelConfig();
        $token = (string) ($device['qr_token'] ?? '');
        if ($qrDataUri === null && $token !== '') {
            $qrDataUri = self::qrPngDataUriForDevice($device);
        }

        return [
            'deviceId' => (int) ($device['id'] ?? 0),
            'qrDataUri' => $qrDataUri ?? '',
            'assetId' => trim((string) ($device['asset_id'] ?? '')) ?: '—',
            'serial' => trim((string) ($device['serial_number'] ?? '')) ?: '—',
            'showSlgti' => false,
            'deviceType' => '',
            'department' => '',
            'status' => '',
        ];
    }

    /**
     * Build print strips. Duplicate mode: same device twice per strip.
     * Batch mode: pairs consecutive devices (last odd duplicated on right).
     *
     * @param list<array<string, mixed>> $devices
     * @return list<list<array<string, mixed>>>
     */
    public static function buildPrintStrips(array $devices, string $mode = 'duplicate', int $sets = 1): array
    {
        $perSet = self::labelsPerSet();
        $strips = [];

        if ($mode === 'batch') {
            for ($i = 0; $i < count($devices); $i += $perSet) {
                $chunk = array_slice($devices, $i, $perSet);
                if (count($chunk) === 1) {
                    $chunk[] = $chunk[0];
                }
                $slots = [];
                foreach ($chunk as $dev) {
                    $slots[] = self::stickerDataFromDevice($dev);
                }
                $strips[] = $slots;
            }

            return $strips;
        }

        foreach ($devices as $device) {
            $slot = self::stickerDataFromDevice($device);
            $row = array_fill(0, $perSet, $slot);
            for ($s = 0; $s < max(1, $sets); $s++) {
                $strips[] = $row;
            }
        }

        return $strips;
    }

    /**
     * @param list<list<array<string, mixed>>> $strips
     */
    public static function renderQrLabelPdfHtmlFromStrips(array $strips): string
    {
        $spec = self::labelSpec();
        extract([
            'printStrips' => $strips,
            'labelSpec' => $spec,
            'labelConfig' => self::labelConfig(),
        ], EXTR_SKIP);
        ob_start();
        include BASE_PATH . '/views/devices/pdf/qr_label.php';

        return (string) ob_get_clean();
    }

    /**
     * @param array<string, mixed> $device
     */
    public static function renderQrLabelPdfHtml(array $device, string $qrDataUri, int $sets = 1): string
    {
        $strips = self::buildPrintStrips([$device], 'duplicate', self::clampLabelSets($sets));

        return self::renderQrLabelPdfHtmlFromStrips($strips);
    }

    /** PDF page size in points (exact strip mm). */
    public static function labelPaper4x1Pdf(): array
    {
        $spec = self::labelSpec();
        $wPt = ((float) $spec['strip_width_mm']) * 72 / 25.4;
        $hPt = ((float) $spec['strip_height_mm']) * 72 / 25.4;

        return [0, 0, $wPt, $hPt];
    }

    /** Left / right sticker origin X (inches from strip left). */
    public static function labelSlotOriginsIn(): array
    {
        $origins = [];
        $x = self::sideMarginIn();
        $step = self::labelWidthIn() + self::horizontalGapIn();
        for ($i = 0; $i < self::labelsPerSet(); $i++) {
            $origins[] = $x;
            $x += $step;
        }

        return $origins;
    }

    public static function inchesToDots(float $inches): int
    {
        return (int) round($inches * self::labelDpi());
    }

    public static function mmToDots(float $mm): int
    {
        return self::inchesToDots(self::mmToIn($mm));
    }

    public static function inchesToMm(float $inches): float
    {
        return $inches * 25.4;
    }

    public static function labelWidthMm(): float
    {
        return self::inchesToMm(self::labelWidthIn());
    }

    public static function labelHeightMm(): float
    {
        return self::inchesToMm(self::labelHeightIn());
    }

    public static function horizontalGapMm(): float
    {
        return self::inchesToMm(self::horizontalGapIn());
    }

    public static function sideMarginMm(): float
    {
        return self::inchesToMm(self::sideMarginIn());
    }

    public static function stripWidthMm(): float
    {
        return self::inchesToMm(self::stripWidthIn());
    }

    public static function stripHeightMm(): float
    {
        return self::inchesToMm(self::labelHeightIn());
    }

    /** PDF / preview row width: two stickers + gap (no outer side margins). */
    public static function pdfContentWidthMm(): float
    {
        $n = self::labelsPerSet();

        return (self::labelWidthMm() * $n) + (self::horizontalGapMm() * max(0, $n - 1));
    }

    /** Dompdf page size in mm (landscape strip, same layout as admission 2-up stickers). */
    public static function labelPaper2UpMm(): array
    {
        return [0, 0, self::pdfContentWidthMm(), self::stripHeightMm()];
    }

    public static function stripWidthDots(): int
    {
        return self::inchesToDots(self::stripWidthIn());
    }

    public static function labelWidthDots(): int
    {
        return self::inchesToDots(self::labelWidthIn());
    }

    public static function labelHeightDots(): int
    {
        return self::inchesToDots(self::labelHeightIn());
    }

    /** Dompdf custom page — one 2-up strip (points, 72 pt/in). */
    public static function labelPaper2Up(): array
    {
        return [0, 0, self::stripWidthIn() * 72, self::labelHeightIn() * 72];
    }

    public static function qrScanUrl(string $token): string
    {
        return rtrim(APP_URL, '/') . '/devices/qr/' . rawurlencode($token);
    }

    /** Public device record page — QR label destination (uses production base URL when configured). */
    public static function deviceViewUrl(int $deviceId): string
    {
        $cfg = self::labelConfig();
        $base = trim((string) ($cfg['qr_public_base_url'] ?? ''));
        if ($base === '') {
            $base = rtrim(APP_URL, '/');
        } else {
            $base = rtrim($base, '/');
        }

        return $base . '/devices/view?id=' . max(0, $deviceId);
    }

    /** Browser Print SSL setup — always localhost on the user's PC. */
    public static function browserPrintSslSupportUrl(): string
    {
        return 'https://localhost:9101/ssl_support';
    }

    /**
     * URL encoded in QR labels (view page when device id is known).
     *
     * @param array<string, mixed> $device
     */
    public static function qrContentUrl(array $device): string
    {
        $id = (int) ($device['id'] ?? 0);
        if ($id > 0) {
            return self::deviceViewUrl($id);
        }
        $token = trim((string) ($device['qr_token'] ?? ''));

        return $token !== '' ? self::qrScanUrl($token) : '';
    }

    public static function qrPngDataUriForDevice(array $device, ?int $size = null): string
    {
        $url = self::qrContentUrl($device);
        if ($url === '') {
            return '';
        }
        $cfg = self::labelConfig();
        $size = $size ?? (int) ($cfg['qr_png_px'] ?? 320);
        $margin = (int) ($cfg['qr_margin_px'] ?? 4);

        return StudentIdCardHelper::qrPngDataUri($url, $size, $margin);
    }

    /**
     * ZPL for Zebra ZD230 — one row with two identical labels; ^PQ repeats the set.
     *
     * @param array<string, mixed> $device
     */
    public static function renderQrLabelZpl(array $device, string $token, int $sets = 1): string
    {
        $sets = self::clampLabelSets($sets);
        $cfg = self::labelConfig();
        $labelWDots = self::labelWidthDots();
        $stripHDots = self::labelHeightDots();
        $stripWDots = self::stripWidthDots();
        $url = self::zplFieldData(self::qrContentUrl($device));
        $assetId = self::zplFieldData(trim((string) ($device['asset_id'] ?? '')) ?: '—');
        $serial = self::zplFieldData(trim((string) ($device['serial_number'] ?? '')) ?: '—');

        $qrMag = max(1, min(10, (int) ($cfg['qr_magnification'] ?? 3)));

        $lines = ['^XA', "^PW{$stripWDots}", "^LL{$stripHDots}", '^LH0,0', '^CI28'];

        $hOffset = (int) ($cfg['horizontal_offset_dots'] ?? 0);
        if ($hOffset !== 0) {
            $lines[] = '^LS' . $hOffset;
        }
        $vOffset = (int) ($cfg['vertical_offset_dots'] ?? 0);
        if ($vOffset !== 0) {
            $lines[] = '^LT' . $vOffset;
        }
        $darkness = $cfg['print_darkness'] ?? null;
        if ($darkness !== null && $darkness !== '') {
            $lines[] = '^MD' . max(0, min(30, (int) $darkness));
        }
        $speed = $cfg['print_speed'] ?? null;
        if ($speed !== null && $speed !== '') {
            $lines[] = '^PR' . max(1, (int) $speed);
        }

        $slotOrigins = array_map(
            static fn (float $in): int => self::inchesToDots($in),
            self::labelSlotOriginsIn()
        );
        foreach ($slotOrigins as $slotOrigin) {
            $lines = array_merge($lines, self::zplLabelSlot(
                $slotOrigin,
                $url,
                $assetId,
                $serial,
                $cfg,
                $qrMag
            ));
        }

        $lines[] = "^PQ{$sets},0,1,Y";
        $lines[] = '^XZ';

        return implode("\n", $lines) . "\n";
    }

    /**
     * @param array<string, mixed> $cfg
     * @return list<string>
     */
    private static function zplLabelSlot(
        int $slotOriginDots,
        string $url,
        string $assetId,
        string $serial,
        array $cfg,
        int $qrMag
    ): array {
        $padMm = (float) ($cfg['inner_padding_mm'] ?? 1.5);
        $qrPrintMm = (float) ($cfg['qr_print_width_mm'] ?? $cfg['qr_size_mm'] ?? 17.0);
        $gapMm = (float) ($cfg['text_gap_mm'] ?? 1.5);
        $labelHMm = self::labelHeightMm();

        $qrX = $slotOriginDots + self::mmToDots($padMm);
        $qrY = self::mmToDots(max($padMm, ($labelHMm - $qrPrintMm) / 2));
        $tx = $slotOriginDots + self::mmToDots($padMm + $qrPrintMm + $gapMm);

        $assetH = (int) ($cfg['asset_no_font_h'] ?? 14);
        $assetW = (int) ($cfg['asset_no_font_w'] ?? 12);
        $serialH = (int) ($cfg['serial_font_h'] ?? 22);
        $serialW = (int) ($cfg['serial_font_w'] ?? 18);

        $textBlockHmm = ($assetH / self::labelDpi() * 25.4 + 0.8)
            + ($serialH / self::labelDpi() * 25.4 + 0.5);
        $ty = self::mmToDots(max($padMm, ($labelHMm - $textBlockHmm) / 2));

        $lines = [];
        $lines[] = "^FO{$qrX},{$qrY}^BQN,2,{$qrMag}^FDQA,{$url}^FS";

        $y = $ty;
        $lines[] = "^FO{$tx},{$y}^A0N,{$assetH},{$assetW}^FDA/N {$assetId}^FS";
        $y += $assetH + 3;
        $lines[] = "^FO{$tx},{$y}^A0N,{$serialH},{$serialW}^FDS/N {$serial}^FS";

        return $lines;
    }

    private static function zplFieldData(string $value): string
    {
        return str_replace(['\\', '^', '~'], ['\\\\', '\\^', '\\~'], $value);
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

    /**
     * List printers installed on the Windows PC running PHP (WAMP server).
     *
     * @return list<array{name: string, port: string, driver: string, source: string}>
     */
    public static function listSystemPrinters(): array
    {
        if (PHP_OS_FAMILY !== 'Windows' || !function_exists('shell_exec')) {
            return [];
        }

        $cmd = 'powershell -NoProfile -ExecutionPolicy Bypass -Command '
            . escapeshellarg('Get-Printer | Select-Object Name,PortName,DriverName | ConvertTo-Json -Compress');
        $output = trim((string) @shell_exec($cmd));
        if ($output === '') {
            return [];
        }

        $decoded = json_decode($output, true);
        if ($decoded === null) {
            return [];
        }
        if (isset($decoded['Name'])) {
            $decoded = [$decoded];
        }
        if (!is_array($decoded)) {
            return [];
        }

        $printers = [];
        foreach ($decoded as $row) {
            if (!is_array($row)) {
                continue;
            }
            $name = trim((string) ($row['Name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $printers[] = [
                'name' => $name,
                'port' => trim((string) ($row['PortName'] ?? '')),
                'driver' => trim((string) ($row['DriverName'] ?? '')),
                'source' => 'pc',
            ];
        }

        return $printers;
    }

    /**
     * Send raw ZPL to a Windows printer via the spooler (same PC as WAMP).
     *
     * @return array{ok: bool, error?: string}
     */
    public static function sendZplToWindowsPrinter(string $printerName, string $zpl): array
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return ['ok' => false, 'error' => 'Server-side printing is only available on Windows.'];
        }
        $printerName = trim($printerName);
        if ($printerName === '') {
            return ['ok' => false, 'error' => 'Printer name is required.'];
        }
        if ($zpl === '') {
            return ['ok' => false, 'error' => 'Empty label data.'];
        }

        $allowed = array_column(self::listSystemPrinters(), 'name');
        if ($allowed !== [] && !in_array($printerName, $allowed, true)) {
            return ['ok' => false, 'error' => 'Printer not found on this PC.'];
        }

        $script = BASE_PATH . '/scripts/send-zpl-raw.ps1';
        if (!is_file($script)) {
            return ['ok' => false, 'error' => 'Print script missing on server.'];
        }

        $tmp = tempnam(sys_get_temp_dir(), 'slgti_zpl_');
        if ($tmp === false) {
            return ['ok' => false, 'error' => 'Could not create temporary file.'];
        }
        $zplPath = $tmp . '.zpl';
        @unlink($tmp);
        if (file_put_contents($zplPath, $zpl) === false) {
            return ['ok' => false, 'error' => 'Could not write label file.'];
        }

        $cmd = 'powershell -NoProfile -ExecutionPolicy Bypass -File '
            . escapeshellarg($script)
            . ' -PrinterName ' . escapeshellarg($printerName)
            . ' -ZplFile ' . escapeshellarg($zplPath)
            . ' 2>&1';

        $output = trim((string) @shell_exec($cmd));
        @unlink($zplPath);

        if ($output === '' || stripos($output, 'OK') !== false) {
            return ['ok' => true];
        }

        return ['ok' => false, 'error' => $output !== '' ? $output : 'Print command failed.'];
    }
}
