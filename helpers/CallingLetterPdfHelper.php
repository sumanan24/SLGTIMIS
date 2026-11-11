<?php
/**
 * Bulk Calling Letter PDF (NVQ Level 04 / 05).
 * Official 3-page templates at 1:1 size + 1 blank A4 page after the signature
 * (4 pages total per student). Overlays student fields + Branch Principal signature.
 */

declare(strict_types=1);

require_once BASE_PATH . '/vendor/autoload.php';

use setasign\Fpdi\Fpdi;

final class CallingLetterPdfHelper
{
    public const TEMPLATE_L4_NORTHERN = 'l4_northern';
    public const TEMPLATE_L4_OTHER = 'l4_other';
    public const TEMPLATE_L5_NORTHERN = 'l5_northern';
    public const TEMPLATE_L5_OTHER = 'l5_other';

    /** Back-compat aliases */
    public const TEMPLATE_NORTHERN = self::TEMPLATE_L4_NORTHERN;
    public const TEMPLATE_OTHER = self::TEMPLATE_L4_OTHER;

    /** @var array<string, string> */
    public const TEMPLATES = [
        self::TEMPLATE_L4_NORTHERN => 'assets/document/Calling_Letter_L4_Northern_Province.pdf',
        self::TEMPLATE_L4_OTHER => 'assets/document/Calling_Letter_L4_Other.pdf',
        self::TEMPLATE_L5_NORTHERN => 'assets/document/Calling_Letter_L5_Northern_Province.pdf',
        self::TEMPLATE_L5_OTHER => 'assets/document/Calling_Letter_L5_Other.pdf',
        // legacy keys
        'northern' => 'assets/document/Calling_Letter_L4_Northern_Province.pdf',
        'other' => 'assets/document/Calling_Letter_L4_Other.pdf',
    ];

    /** @deprecated Use TEMPLATES[TEMPLATE_L4_NORTHERN] */
    public const TEMPLATE_RELATIVE = 'assets/document/Calling_Letter_L4_Northern_Province.pdf';
    /** Source template pages; output is template + 1 blank A4. */
    public const TEMPLATE_PAGES = 3;
    public const OUTPUT_PAGES = 4;

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
     * @param string $templateKey l4_northern|l4_other|l5_northern|l5_other
     */
    public static function buildPdfBytes(
        array $students,
        ?string $signaturePath = null,
        string $templateKey = self::TEMPLATE_L4_NORTHERN
    ): string {
        $templateKey = self::normalizeTemplateKey($templateKey);
        $template = self::templatePath($templateKey);
        if (!is_file($template)) {
            throw new RuntimeException('Calling letter template PDF is missing (' . $templateKey . ').');
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

        $signaturePath = ($signaturePath !== null && $signaturePath !== '')
            ? $signaturePath
            : self::principalSignatureFilePath();
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

    public static function normalizeTemplateKey(?string $key): string
    {
        $key = strtolower(trim((string) $key));
        if ($key === 'northern') {
            return self::TEMPLATE_L4_NORTHERN;
        }
        if ($key === 'other') {
            return self::TEMPLATE_L4_OTHER;
        }
        return isset(self::TEMPLATES[$key]) ? $key : self::TEMPLATE_L4_NORTHERN;
    }

    public static function templatePath(?string $templateKey = null): string
    {
        $key = self::normalizeTemplateKey($templateKey);
        return BASE_PATH . '/' . self::TEMPLATES[$key];
    }

    /** NVQ level digits for a template key ('04' or '05'). */
    public static function nvqLevelForTemplate(?string $templateKey): string
    {
        $key = self::normalizeTemplateKey($templateKey);
        return (strpos($key, 'l5') === 0) ? '05' : '04';
    }

    /** Human label for UI / filenames. */
    public static function labelForTemplate(?string $templateKey): string
    {
        $key = self::normalizeTemplateKey($templateKey);
        if ($key === self::TEMPLATE_L4_OTHER) {
            return 'Level 04 / Other Provinces';
        }
        if ($key === self::TEMPLATE_L5_NORTHERN) {
            return 'Level 05 / Northern Province';
        }
        if ($key === self::TEMPLATE_L5_OTHER) {
            return 'Level 05 / Other Provinces';
        }
        return 'Level 04 / Northern Province';
    }

    public static function isOtherProvincesTemplate(?string $templateKey): bool
    {
        $key = self::normalizeTemplateKey($templateKey);
        return $key === self::TEMPLATE_L4_OTHER || $key === self::TEMPLATE_L5_OTHER;
    }

    public static function isNorthernRegionTemplate(?string $templateKey): bool
    {
        $key = self::normalizeTemplateKey($templateKey);
        return $key === self::TEMPLATE_L4_NORTHERN || $key === self::TEMPLATE_L5_NORTHERN;
    }

    /** True when student_provice is Northern (with or without "Province"). */
    public static function isNorthernProvince(?string $province): bool
    {
        $name = strtolower(trim((string) $province));
        $name = preg_replace('/\s+province$/i', '', $name) ?? $name;
        return trim($name) === 'northern';
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
        // Never print Mr./Ms. (strip if present in data or leftover from template wording).
        $name = preg_replace('/^(Mr|Ms|Mrs|Miss|Dr)\.?\s+/i', '', $name) ?? $name;
        $name = self::clean($name);
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

        // 1:1 official pages — same size and margins throughout.
        $fit = ['ox' => 0.0, 'oy' => 0.0, 'sx' => 1.0, 'sy' => 1.0];

        $pdf->AddPage();
        $pdf->useTemplate($tplIds[1], 0, 0, self::PAGE_W, self::PAGE_H);
        self::overlayPage1Fields($pdf, $fit, $name, $addressLines, $department, $course, $nvq);

        $pdf->AddPage();
        $pdf->useTemplate($tplIds[2], 0, 0, self::PAGE_W, self::PAGE_H);

        $pdf->AddPage();
        $pdf->useTemplate($tplIds[3], 0, 0, self::PAGE_W, self::PAGE_H);
        self::overlayPrincipalSignature($pdf, $fit, $signaturePath);

        // Blank A4 page after digital signature (4 pages total per student).
        $pdf->AddPage();
    }

    /**
     * @param array{ox:float,oy:float,sx:float,sy:float} $t
     * @param list<string> $addressLines
     */
    private static function overlayPage1Fields(
        Fpdi $pdf,
        array $t,
        string $name,
        array $addressLines,
        string $department,
        string $course,
        string $nvq
    ): void {
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFillColor(255, 255, 255);
        // Match formal letter body (Times) and keep a consistent size.
        $pdf->SetFont('Times', '', 12);
        $lineH = 14.0;
        $left = 72.0; // align with body text margin

        // Cover "Mr./Ms." (line above or same line) and print name only, left-aligned with body.
        self::whiteRectT($pdf, $t, 55.0, self::fromBottom(742.0), 500.0, 36.0);
        $nameY = self::fromBottom(725.5);
        self::textAtT($pdf, $t, $left, $nameY - 1.0, 460.0, $lineH, $name);

        // Address dotted lines — same left margin as name / body.
        foreach ([691.78, 674.88] as $i => $pdfY) {
            $line = $addressLines[$i] ?? '';
            if ($line === '') {
                continue;
            }
            $y = self::fromBottom((float) $pdfY);
            self::whiteRectT($pdf, $t, 55.0, $y, 500.0, 15.0);
            self::textAtT($pdf, $t, $left, $y - 1.0, 460.0, $lineH, $line);
        }

        // Department / Course / NVQ — shared value column after the colon.
        $valueX = 228.0;
        $valueW = 310.0;
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
            self::whiteRectT($pdf, $t, $valueX, $y, $valueW, 14.0);
            self::textAtT($pdf, $t, $valueX, $y - 0.5, $valueW, $lineH, $value);
        }
    }

    /**
     * @param array{ox:float,oy:float,sx:float,sy:float} $t
     */
    private static function overlayPrincipalSignature(Fpdi $pdf, array $t, ?string $signaturePath): void
    {
        if ($signaturePath === null || !is_file($signaturePath)) {
            return;
        }

        $targetWidthPt = 72.0 * $t['sx'];
        $maxHeightPt = 28.0 * $t['sy'];
        $size = @getimagesize($signaturePath);
        $srcW = (float) (($size[0] ?? 0) ?: 1024);
        $srcH = (float) (($size[1] ?? 0) ?: 441);
        $imgW = $targetWidthPt;
        $imgH = $imgW * ($srcH / $srcW);
        if ($imgH > $maxHeightPt) {
            $imgH = $maxHeightPt;
            $imgW = $imgH * ($srcW / $srcH);
        }

        $x = self::mapX($t, 72.0);
        $y = self::mapY($t, self::fromBottom(548.0)) - $imgH;
        try {
            $pdf->Image($signaturePath, $x, $y, $imgW, $imgH);
        } catch (Throwable $e) {
            error_log('CallingLetterPdfHelper signature: ' . $e->getMessage());
        }
    }

    private static function fromBottom(float $pdfY): float
    {
        return self::PAGE_H - $pdfY;
    }

    /** @param array{ox:float,oy:float,sx:float,sy:float} $t */
    private static function mapX(array $t, float $x): float
    {
        return $t['ox'] + ($x * $t['sx']);
    }

    /** @param array{ox:float,oy:float,sx:float,sy:float} $t */
    private static function mapY(array $t, float $y): float
    {
        return $t['oy'] + ($y * $t['sy']);
    }

    /** @param array{ox:float,oy:float,sx:float,sy:float} $t */
    private static function whiteRectT(Fpdi $pdf, array $t, float $x, float $y, float $w, float $h): void
    {
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect(
            self::mapX($t, $x),
            self::mapY($t, $y) - (2.0 * $t['sy']),
            $w * $t['sx'],
            $h * $t['sy'],
            'F'
        );
    }

    /** @param array{ox:float,oy:float,sx:float,sy:float} $t */
    private static function textAtT(
        Fpdi $pdf,
        array $t,
        float $x,
        float $y,
        float $w,
        float $h,
        string $text
    ): void {
        $mw = $w * $t['sx'];
        $pdf->SetXY(self::mapX($t, $x), self::mapY($t, $y));
        $pdf->Cell($mw, $h * $t['sy'], self::fit($pdf, $text, $mw), 0, 0, 'L');
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
