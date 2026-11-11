<?php
/**
 * Bulk Calling Letter PDF (NVQ Level 04 / Northern Province).
 * Uses the official 3-page PDF as an immutable template and overlays only
 * student-specific fields + the Branch Principal digital signature.
 */

declare(strict_types=1);

require_once BASE_PATH . '/vendor/autoload.php';

use setasign\Fpdi\Fpdi;

final class CallingLetterPdfHelper
{
    public const TEMPLATE_RELATIVE = 'assets/document/Calling_Letter_L4_Northern_Province.pdf';
    public const TEMPLATE_PAGES = 3;

    /** Official template page size in PDF points (US Letter). */
    private const PAGE_W = 612.0;
    private const PAGE_H = 792.0;

    /**
     * @param list<array{
     *   name:string,
     *   address_lines?:list<string>,
     *   department:string,
     *   course:string,
     *   nvq_level:string
     * }> $students
     */
    public static function buildPdfBytes(array $students, ?string $signaturePath = null): string
    {
        $template = self::templatePath();
        if (!is_file($template)) {
            throw new RuntimeException('Calling letter template PDF is missing.');
        }
        if ($students === []) {
            throw new RuntimeException('No students to include in the calling letter PDF.');
        }

        if (function_exists('ini_set')) {
            @ini_set('memory_limit', '512M');
        }
        if (function_exists('set_time_limit')) {
            @set_time_limit(300);
        }

        $signaturePath = $signaturePath ?: self::principalSignatureFilePath();
        $pdf = new Fpdi('P', 'pt', [self::PAGE_W, self::PAGE_H]);
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetCompression(true);

        $pageCount = $pdf->setSourceFile($template);
        if ($pageCount < self::TEMPLATE_PAGES) {
            throw new RuntimeException('Calling letter template must have 3 pages.');
        }

        $tplIds = [];
        for ($p = 1; $p <= self::TEMPLATE_PAGES; $p++) {
            $tplIds[$p] = $pdf->importPage($p);
        }

        foreach ($students as $index => $student) {
            if (function_exists('set_time_limit')) {
                @set_time_limit(120);
            }
            self::addStudentLetter($pdf, $tplIds, $student, $signaturePath);
        }

        return $pdf->Output('S');
    }

    public static function templatePath(): string
    {
        return BASE_PATH . '/' . self::TEMPLATE_RELATIVE;
    }

    public static function principalSignatureFilePath(): ?string
    {
        // Official principal signature for calling letters.
        $preferred = BASE_PATH . '/assets/img/principal-signature.png';
        if (is_file($preferred) && filesize($preferred) > 0) {
            $processed = self::signaturePngWithTransparentBackground($preferred);
            return $processed ?: $preferred;
        }
        foreach ([
            BASE_PATH . '/public/images/principal-signature.png',
            BASE_PATH . '/assets/img/sign.png',
        ] as $path) {
            if (!is_file($path) || filesize($path) <= 0) {
                continue;
            }
            $processed = self::signaturePngWithTransparentBackground($path);
            return $processed ?: $path;
        }
        return null;
    }

    /**
     * Return a trimmed white-background PNG (FPDF-safe).
     * Strips black plate / near-white paper and crops empty margins for proper sizing.
     */
    private static function signaturePngWithTransparentBackground(string $path): ?string
    {
        if (!function_exists('imagecreatefromstring') || !function_exists('imagepng')) {
            return null;
        }
        $raw = (string) file_get_contents($path);
        if ($raw === '') {
            return null;
        }
        $im = @imagecreatefromstring($raw);
        if (!$im) {
            return null;
        }
        $w = imagesx($im);
        $h = imagesy($im);
        $out = imagecreatetruecolor($w, $h);
        $white = imagecolorallocate($out, 255, 255, 255);
        imagefilledrectangle($out, 0, 0, $w, $h, $white);

        $minX = $w;
        $minY = $h;
        $maxX = -1;
        $maxY = -1;
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $rgba = imagecolorat($im, $x, $y);
                $r = ($rgba >> 16) & 0xFF;
                $g = ($rgba >> 8) & 0xFF;
                $b = $rgba & 0xFF;
                // Drop black plate behind the ink.
                if ($r < 40 && $g < 40 && $b < 40) {
                    continue;
                }
                // Drop near-white paper if present.
                if ($r > 245 && $g > 245 && $b > 245) {
                    continue;
                }
                imagesetpixel($out, $x, $y, imagecolorallocate($out, $r, $g, $b));
                if ($x < $minX) {
                    $minX = $x;
                }
                if ($y < $minY) {
                    $minY = $y;
                }
                if ($x > $maxX) {
                    $maxX = $x;
                }
                if ($y > $maxY) {
                    $maxY = $y;
                }
            }
        }
        imagedestroy($im);

        // Crop to ink bounds + small padding so the printed size matches the strokes.
        if ($maxX >= $minX && $maxY >= $minY) {
            $pad = 8;
            $minX = max(0, $minX - $pad);
            $minY = max(0, $minY - $pad);
            $maxX = min($w - 1, $maxX + $pad);
            $maxY = min($h - 1, $maxY + $pad);
            $cw = $maxX - $minX + 1;
            $ch = $maxY - $minY + 1;
            $cropped = imagecreatetruecolor($cw, $ch);
            $cWhite = imagecolorallocate($cropped, 255, 255, 255);
            imagefilledrectangle($cropped, 0, 0, $cw, $ch, $cWhite);
            imagecopy($cropped, $out, 0, 0, $minX, $minY, $cw, $ch);
            imagedestroy($out);
            $out = $cropped;
        }

        $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'slgti_principal_sig_' . md5($path . (string) filesize($path) . '_v2') . '.png';
        if (!@imagepng($out, $tmp)) {
            imagedestroy($out);
            return null;
        }
        imagedestroy($out);
        return is_file($tmp) ? $tmp : null;
    }

    /**
     * @param array<int, mixed> $tplIds
     * @param array{
     *   name:string,
     *   address_lines?:list<string>,
     *   department:string,
     *   course:string,
     *   nvq_level:string
     * } $student
     */
    private static function addStudentLetter(Fpdi $pdf, array $tplIds, array $student, ?string $signaturePath): void
    {
        $name = self::clean($student['name'] ?? '');
        $department = self::clean($student['department'] ?? '');
        $course = self::clean($student['course'] ?? '');
        $nvq = self::formatNvqLevel($student['nvq_level'] ?? '04');
        $addressLines = [];
        foreach (($student['address_lines'] ?? []) as $line) {
            $line = self::clean((string) $line);
            if ($line !== '') {
                $addressLines[] = $line;
            }
        }

        // Page 1 — letter body with student fields
        $pdf->AddPage();
        $pdf->useTemplate($tplIds[1], 0, 0, self::PAGE_W, self::PAGE_H);
        self::overlayPage1Fields($pdf, $name, $addressLines, $department, $course, $nvq);

        // Page 2 — dress code / payment (unchanged official content)
        $pdf->AddPage();
        $pdf->useTemplate($tplIds[2], 0, 0, self::PAGE_W, self::PAGE_H);

        // Page 3 — closing + principal signature
        $pdf->AddPage();
        $pdf->useTemplate($tplIds[3], 0, 0, self::PAGE_W, self::PAGE_H);
        self::overlayPrincipalSignature($pdf, $signaturePath);
    }

    /**
     * @param list<string> $addressLines
     */
    private static function overlayPage1Fields(
        Fpdi $pdf,
        string $name,
        array $addressLines,
        string $department,
        string $course,
        string $nvq
    ): void {
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetFont('Helvetica', '', 12);

        // Name line (PDF y≈708.6). Cover dotted area after "Mr./Ms. " and write the name.
        // "Mr./Ms. " ends near x≈110 in the template.
        self::whiteRect($pdf, 112, self::fromBottom(708.6), 420, 14);
        $pdf->SetXY(112, self::fromBottom(708.6) - 1);
        $pdf->Cell(420, 14, self::fit($pdf, $name, 420), 0, 0, 'L');

        // Address dotted lines (optional; keeps letter complete without changing official wording)
        $addrY = [691.78, 674.88];
        for ($i = 0; $i < 2; $i++) {
            $line = $addressLines[$i] ?? '';
            if ($line === '') {
                continue;
            }
            self::whiteRect($pdf, 72, self::fromBottom($addrY[$i]), 460, 14);
            $pdf->SetXY(72, self::fromBottom($addrY[$i]) - 1);
            $pdf->Cell(460, 14, self::fit($pdf, $line, 460), 0, 0, 'L');
        }

        // Values after the ":" for Department / Course / NVQ (colon at x≈216)
        // Use list pairs — float array keys are illegal in PHP 8.1+ (lose precision).
        $valueX = 230.0;
        $valueW = 300.0;
        $rows = [
            [556.97, $department],
            [540.08, $course],
            [523.28, $nvq],
        ];
        foreach ($rows as [$pdfY, $value]) {
            if ($value === '') {
                continue;
            }
            $y = self::fromBottom((float) $pdfY);
            self::whiteRect($pdf, $valueX, $y, $valueW, 14.0);
            $pdf->SetXY($valueX, $y - 1.0);
            $pdf->Cell($valueW, 14.0, self::fit($pdf, $value, $valueW), 0, 0, 'L');
        }
    }

    private static function overlayPrincipalSignature(Fpdi $pdf, ?string $signaturePath): void
    {
        if ($signaturePath === null || !is_file($signaturePath)) {
            return;
        }

        // Compact signature above "Branch Principal" (PDF y≈540).
        $targetWidthPt = 72.0; // ≈ 25 mm
        $maxHeightPt = 28.0;   // ≈ 10 mm
        $size = @getimagesize($signaturePath);
        $srcW = (float) (($size[0] ?? 0) ?: 1024);
        $srcH = (float) (($size[1] ?? 0) ?: 441);
        $imgW = $targetWidthPt;
        $imgH = $imgW * ($srcH / $srcW);
        if ($imgH > $maxHeightPt) {
            $imgH = $maxHeightPt;
            $imgW = $imgH * ($srcW / $srcH);
        }

        $x = 72.0;
        $y = self::fromBottom(548.0) - $imgH;
        try {
            // Pass both W/H so placement is exact after trim.
            $pdf->Image($signaturePath, $x, $y, $imgW, $imgH);
        } catch (Throwable $e) {
            error_log('CallingLetterPdfHelper signature: ' . $e->getMessage());
        }
    }

    private static function fromBottom(float $pdfY): float
    {
        return self::PAGE_H - $pdfY;
    }

    private static function whiteRect(Fpdi $pdf, float $x, float $y, float $w, float $h): void
    {
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect($x, $y - 2, $w, $h, 'F');
    }

    private static function clean(string $v): string
    {
        $v = trim(preg_replace('/\s+/u', ' ', $v) ?? '');
        // FPDF core fonts are Latin-1; replace characters it cannot encode.
        $converted = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $v);
        return $converted !== false ? $converted : $v;
    }

    private static function formatNvqLevel(string $level): string
    {
        $level = trim($level);
        if ($level === '') {
            return '04';
        }
        if (preg_match('/^\d+$/', $level)) {
            return str_pad($level, 2, '0', STR_PAD_LEFT);
        }
        return $level;
    }

    private static function fit(Fpdi $pdf, string $text, float $maxWidth): string
    {
        if ($text === '') {
            return '';
        }
        if ($pdf->GetStringWidth($text) <= $maxWidth) {
            return $text;
        }
        $ellipsis = '...';
        $out = $text;
        while ($out !== '' && $pdf->GetStringWidth($out . $ellipsis) > $maxWidth) {
            $out = substr($out, 0, -1);
        }
        return $out === '' ? '' : $out . $ellipsis;
    }
}
