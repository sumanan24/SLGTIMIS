<?php
declare(strict_types=1);

final class StudentIdCardHelper
{
    public static function ensureComposerAutoload(): void
    {
        $autoload = defined('BASE_PATH') ? BASE_PATH . '/vendor/autoload.php' : dirname(__DIR__) . '/vendor/autoload.php';
        if (is_file($autoload)) {
            require_once $autoload;
        }
    }

    public static function qrPngDataUri(string $text, int $size = 360, int $margin = 0): string
    {
        self::ensureComposerAutoload();

        $qr = \Endroid\QrCode\QrCode::create($text)
            ->setSize($size)
            ->setMargin($margin);

        $writer = new \Endroid\QrCode\Writer\PngWriter();
        $result = $writer->write($qr);
        $png = $result->getString();

        return 'data:image/png;base64,' . base64_encode($png);
    }

    public static function imageFileToDataUri(string $filePath): ?string
    {
        if (!is_file($filePath)) {
            return null;
        }
        $ext = strtolower((string) pathinfo($filePath, PATHINFO_EXTENSION));
        // PHP < 8 compatibility: no match expression
        $mime = null;
        if ($ext === 'png') {
            $mime = 'image/png';
        } elseif ($ext === 'jpg' || $ext === 'jpeg') {
            $mime = 'image/jpeg';
        } elseif ($ext === 'gif') {
            $mime = 'image/gif';
        } elseif ($ext === 'svg') {
            $mime = 'image/svg+xml';
        }
        if ($mime === null) {
            return null;
        }
        $raw = (string) file_get_contents($filePath);
        if ($raw === '') {
            return null;
        }
        if ($mime === 'image/svg+xml') {
            return 'data:image/svg+xml;base64,' . base64_encode($raw);
        }
        return 'data:' . $mime . ';base64,' . base64_encode($raw);
    }

    public static function slgtiLogoDataUri(): string
    {
        $paths = [
            BASE_PATH . '/public/images/slgti-logo.svg',
            BASE_PATH . '/assets/img/slgtilogo.png',
        ];
        foreach ($paths as $p) {
            $uri = self::imageFileToDataUri($p);
            if ($uri !== null) {
                return $uri;
            }
        }
        $fallback = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 220 60"><rect width="220" height="60" rx="10" fill="#111827"/><text x="110" y="39" font-family="DejaVu Sans,Arial,sans-serif" font-size="22" text-anchor="middle" fill="#fff" font-weight="700">SLGTI</text></svg>';
        return 'data:image/svg+xml,' . rawurlencode($fallback);
    }

    public static function crestDataUri(): string
    {
        $fallback = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#f59e0b"/><stop offset="1" stop-color="#ef4444"/></linearGradient></defs><circle cx="32" cy="32" r="30" fill="url(#g)"/><circle cx="32" cy="32" r="26" fill="#fff" opacity="0.9"/><circle cx="32" cy="32" r="22" fill="#111827"/><text x="32" y="39" font-family="DejaVu Sans,Arial,sans-serif" font-size="18" text-anchor="middle" fill="#fff" font-weight="700">SL</text></svg>';
        return 'data:image/svg+xml,' . rawurlencode($fallback);
    }

    public static function principalSignatureDataUri(): ?string
    {
        $f = BASE_PATH . '/public/images/principal-signature.png';
        return self::imageFileToDataUri($f);
    }

    /**
     * Try to resolve a student profile image to a local file path in this project.
     */
    public static function resolveStudentProfileLocalPath(?array $student): ?string
    {
        if (!$student) {
            return null;
        }
        $rel = (string) ($student['student_profile_img'] ?? $student['file_path'] ?? '');
        $rel = ltrim($rel, '/');
        if ($rel === '') {
            return null;
        }
        // PHP < 8 compatibility: no str_starts_with
        if (substr($rel, 0, 7) === 'assets/') {
            $rel = substr($rel, 7);
        }
        $candidate = BASE_PATH . '/assets/' . $rel;
        if (is_file($candidate)) {
            return $candidate;
        }
        $candidate2 = BASE_PATH . '/' . $rel;
        if (is_file($candidate2)) {
            return $candidate2;
        }
        return null;
    }
}

