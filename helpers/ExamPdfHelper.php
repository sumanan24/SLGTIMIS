<?php
/**
 * Dompdf-based PDF output for Exam module.
 * Requires: composer install (dompdf/dompdf). See project composer.json.
 */

class ExamPdfHelper {

    /** Students per Dompdf job for bulk admission (2 A4 pages each). */
    private const ADMISSION_BULK_CHUNK = 6;

    public static function dompdfAvailable(): bool {
        $autoload = defined('BASE_PATH') ? BASE_PATH . '/vendor/autoload.php' : dirname(__DIR__) . '/vendor/autoload.php';
        return is_file($autoload);
    }

    /**
     * @throws RuntimeException
     */
    public static function loadDompdf(): void {
        if (!self::dompdfAvailable()) {
            throw new RuntimeException(
                'Dompdf is not installed. From the project root run: composer install'
            );
        }
        require_once BASE_PATH . '/vendor/autoload.php';
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function renderTemplate(string $relativeView, array $data): string {
        $path = BASE_PATH . '/views/exams/pdf/' . $relativeView;
        if (!is_file($path)) {
            throw new RuntimeException('PDF template not found: ' . $relativeView);
        }
        extract($data, EXTR_SKIP);
        ob_start();
        include $path;
        return (string) ob_get_clean();
    }

    private static function createDompdf(): \Dompdf\Dompdf {
        if (function_exists('ini_set')) {
            @ini_set('memory_limit', '512M');
        }
        self::loadDompdf();
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'Helvetica');
        $options->set('isFontSubsettingEnabled', false);
        $options->set('dpi', 96);
        $options->set('isPhpEnabled', false);
        if (defined('BASE_PATH')) {
            $options->setChroot(BASE_PATH);
        }

        return new \Dompdf\Dompdf($options);
    }

    public static function assetPathForPdf(string $relativePath): ?string {
        if (!defined('BASE_PATH')) {
            return null;
        }
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
        $full = BASE_PATH . '/' . $relativePath;

        return is_file($full) ? $relativePath : null;
    }

    public static function prepareBulkPdfJob(): void {
        if (function_exists('ini_set')) {
            @ini_set('memory_limit', '512M');
        }
        if (function_exists('set_time_limit')) {
            @set_time_limit(600);
        }
    }

    /**
     * Bulk admission: chunk students, render few PDFs, merge to one download (fast + single file).
     *
     * @param list<string> $innerParts One admission_card.php body per student
     * @param callable(string): string $wrapDocument Wraps combined HTML chunk in full document
     * @throws RuntimeException
     */
    public static function streamAdmissionInnerPartsMerged(array $innerParts, callable $wrapDocument, string $filename, int $chunkSize = 0): void {
        self::prepareBulkPdfJob();
        if ($innerParts === []) {
            throw new RuntimeException('No admission cards to render.');
        }
        if ($chunkSize < 1) {
            $chunkSize = self::ADMISSION_BULK_CHUNK;
        }

        if (count($innerParts) === 1) {
            self::streamHtml($wrapDocument($innerParts[0]), $filename);

            return;
        }

        self::loadDompdf();
        if (!class_exists('\setasign\Fpdi\Fpdi')) {
            throw new RuntimeException(
                'Bulk admission PDF merge requires FPDI. From the project root run: composer update setasign/fpdi setasign/fpdf'
            );
        }

        $pdfChunks = [];
        foreach (array_chunk($innerParts, $chunkSize) as $chunk) {
            $body = self::joinAdmissionInnerParts($chunk);
            $pdfChunks[] = self::renderPdfBytes($wrapDocument($body));
            unset($body);
        }

        self::streamMergedPdfBytes($pdfChunks, $filename);
    }

    /**
     * @param list<string> $innerParts
     */
    private static function joinAdmissionInnerParts(array $innerParts): string {
        $out = '';
        foreach ($innerParts as $i => $part) {
            $break = ($i > 0) ? ' style="page-break-before:always;"' : '';
            $out .= '<div class="admission-bulk-student"' . $break . '>' . $part . '</div>';
        }

        return $out;
    }

    /**
     * @param list<string> $pdfBytesList
     * @throws RuntimeException
     */
    public static function streamMergedPdfBytes(array $pdfBytesList, string $filename): void {
        self::loadDompdf();
        if (!class_exists('\setasign\Fpdi\Fpdi')) {
            throw new RuntimeException('FPDI is required to merge PDFs.');
        }
        if ($pdfBytesList === []) {
            throw new RuntimeException('No PDF data to merge.');
        }

        $pdf = new \setasign\Fpdi\Fpdi();
        foreach ($pdfBytesList as $bytes) {
            if ($bytes === '') {
                continue;
            }
            $reader = \setasign\Fpdi\PdfParser\StreamReader::createByString($bytes);
            $pageCount = $pdf->setSourceFile($reader);
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);
                $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);
            }
            unset($bytes, $reader);
        }

        $safe = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $filename) ?: 'document.pdf';
        if (substr(strtolower($safe), -4) !== '.pdf') {
            $safe .= '.pdf';
        }
        $pdf->Output('D', $safe);
        exit;
    }

    /**
     * @param list<string> $pdfPaths Absolute paths to PDF files
     * @throws RuntimeException
     */
    public static function streamMergedPdfFiles(array $pdfPaths, string $filename): void {
        $chunks = [];
        foreach ($pdfPaths as $path) {
            if (is_file($path)) {
                $chunks[] = (string) file_get_contents($path);
            }
        }
        self::streamMergedPdfBytes($chunks, $filename);
    }

    /**
     * @param list<string> $htmlDocuments Each string is a full HTML document for Dompdf
     * @throws RuntimeException
     */
    public static function streamMultipleHtmlAsMergedPdf(array $htmlDocuments, string $filename): void {
        self::prepareBulkPdfJob();
        if ($htmlDocuments === []) {
            throw new RuntimeException('No documents to render.');
        }
        if (count($htmlDocuments) === 1) {
            self::streamHtml($htmlDocuments[0], $filename);

            return;
        }

        $pdfChunks = [];
        foreach ($htmlDocuments as $html) {
            $pdfChunks[] = self::renderPdfBytes($html);
            unset($html);
        }
        self::streamMergedPdfBytes($pdfChunks, $filename);
    }

    public static function streamHtml(string $html, string $filename, $paper = 'A4', string $orientation = 'portrait'): void {
        self::prepareBulkPdfJob();
        $dompdf = self::createDompdf();
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper($paper, $orientation);
        $dompdf->render();
        $safe = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $filename) ?: 'document.pdf';
        if (substr($safe, -4) !== '.pdf') {
            $safe .= '.pdf';
        }
        $dompdf->stream($safe, ['Attachment' => true]);
        exit;
    }

    /**
     * @throws RuntimeException
     */
    public static function renderPdfBytes(string $html, $paper = 'A4', string $orientation = 'portrait'): string {
        $dompdf = self::createDompdf();
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper($paper, $orientation);
        $dompdf->render();
        $out = (string) $dompdf->output();
        unset($dompdf);

        return $out;
    }

    /**
     * 2-up landscape stickers: existing student registration number only.
     * Each sticker 50×25 mm; page is two stickers with a 3 mm gap.
     *
     * @param list<array<string, mixed>> $students
     */
    public static function streamRollStickersPdf(array $students, int $copies, string $filename): void {
        self::loadDompdf();
        if (!class_exists('FPDF')) {
            throw new RuntimeException('FPDF is required to create roll-number stickers.');
        }

        $allowedCopies = [1, 2, 5, 10];
        $copies = in_array($copies, $allowedCopies, true) ? $copies : 1;
        $labelW = 50.0;
        $labelH = 25.0;
        $gap = 3.0;
        $pageW = $labelW + $gap + $labelW;
        $pageH = $labelH;

        $pdf = new FPDF('L', 'mm', [$pageW, $pageH]);
        $pdf->SetAutoPageBreak(false, 0);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetCompression(true);

        $count = count($students);
        for ($i = 0; $i < $count; $i += 2) {
            $left = $students[$i];
            $right = $students[$i + 1] ?? null;
            for ($c = 0; $c < $copies; $c++) {
                $pdf->AddPage('L', [$pageW, $pageH]);
                self::drawRollStickerCell($pdf, 0, 0, $labelW, $labelH, $left);
                if (is_array($right)) {
                    self::drawRollStickerCell($pdf, $labelW + $gap, 0, $labelW, $labelH, $right);
                }
            }
        }

        $safe = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $filename) ?: 'roll-stickers.pdf';
        if (substr(strtolower($safe), -4) !== '.pdf') {
            $safe .= '.pdf';
        }
        $pdf->Output('D', $safe);
        exit;
    }

    /**
     * @param array<string, mixed> $student
     */
    private static function drawRollStickerCell(\FPDF $pdf, float $x, float $y, float $w, float $h, array $student): void {
        $pdf->SetDrawColor(180, 180, 180);
        $pdf->SetLineWidth(0.2);
        $pdf->Rect($x + 0.4, $y + 0.4, $w - 0.8, $h - 0.8);

        $number = trim((string) ($student['student_id'] ?? ''));
        if ($number === '') {
            $number = trim((string) ($student['roll_number'] ?? ''));
        }
        $text = self::fpdfLatin($number);
        if ($text === '') {
            return;
        }

        $usable = $w - 2.4;
        $size = 12.0;
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Helvetica', 'B', $size);
        while ($size > 8.0 && $pdf->GetStringWidth($text) > $usable) {
            $size -= 0.4;
            $pdf->SetFont('Helvetica', 'B', $size);
        }
        $lineH = max(6.0, $size * 0.42);
        $pdf->SetXY($x, $y + (($h - $lineH) / 2));
        $pdf->Cell($w, $lineH, $text, 0, 0, 'C');
    }

    private static function fpdfLatin(string $value): string {
        $value = str_replace(["\r", "\n", "\t"], ' ', $value);
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $value);
            if ($converted !== false) {
                return $converted;
            }
        }

        return $value;
    }
}
