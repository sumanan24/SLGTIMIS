<?php
declare(strict_types=1);

/**
 * Appends validated upload files (PDF / images) after the staff summary PDF using FPDI.
 */
final class StudentApplicationMergedPdf {

    /**
     * @param list<array{label: string, path: string}> $documents Ordered sections; path is absolute on disk.
     */
    public static function mergeSummaryWithDocuments(string $summaryPdfBinary, array $documents): string {
        if ($documents === []) {
            return $summaryPdfBinary;
        }

        $autoload = defined('BASE_PATH') ? BASE_PATH . '/vendor/autoload.php' : dirname(__DIR__) . '/vendor/autoload.php';
        if (!is_file($autoload)) {
            error_log('StudentApplicationMergedPdf: vendor/autoload.php missing');
            return $summaryPdfBinary;
        }
        require_once $autoload;

        if (!class_exists(\setasign\Fpdi\Fpdi::class)) {
            error_log('StudentApplicationMergedPdf: FPDI not available (run composer install)');
            return $summaryPdfBinary;
        }

        try {
            return self::doMerge($summaryPdfBinary, $documents);
        } catch (Throwable $e) {
            error_log('StudentApplicationMergedPdf: ' . $e->getMessage());
            return $summaryPdfBinary;
        }
    }

    /**
     * @param list<array{label: string, path: string}> $documents
     */
    private static function doMerge(string $summaryPdfBinary, array $documents): string {
        $pdf = new \setasign\Fpdi\Fpdi();

        $tmpSummary = tempnam(sys_get_temp_dir(), 'sa_sum_');
        if ($tmpSummary === false) {
            throw new RuntimeException('Could not create temp file for summary PDF');
        }
        file_put_contents($tmpSummary, $summaryPdfBinary);
        try {
            $summaryPages = $pdf->setSourceFile($tmpSummary);
            for ($i = 1; $i <= $summaryPages; $i++) {
                $pdf->AddPage();
                $tpl = $pdf->importPage($i);
                $pdf->useTemplate($tpl);
            }
        } finally {
            @unlink($tmpSummary);
        }

        $temps = [];

        try {
            foreach ($documents as $doc) {
                $label = $doc['label'];
                $absPath = $doc['path'];
                if ($absPath === '' || !is_readable($absPath)) {
                    continue;
                }

                $ext = strtolower(pathinfo($absPath, PATHINFO_EXTENSION));

                if ($ext === 'pdf') {
                    try {
                        $pages = $pdf->setSourceFile($absPath);
                        for ($p = 1; $p <= $pages; $p++) {
                            $pdf->AddPage();
                            $tpl = $pdf->importPage($p);
                            $pdf->useTemplate($tpl);
                        }
                    } catch (Throwable $e) {
                        $pdf->AddPage();
                        $pdf->SetFont('Helvetica', '', 10);
                        $pdf->MultiCell(0, 6, self::fpdfText('Could not merge PDF attachment: ' . $label . '.'));
                    }
                    continue;
                }

                $imgPath = null;
                if ($ext === 'webp') {
                    $converted = self::webpToJpegTemp($absPath);
                    if ($converted !== null) {
                        $temps[] = $converted;
                        $imgPath = $converted;
                    }
                } elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'], true)) {
                    $imgPath = $absPath;
                }

                if ($imgPath === null || !is_readable($imgPath)) {
                    continue;
                }

                $pdf->AddPage();
                $pdf->SetFont('Helvetica', 'B', 11);
                $pdf->Cell(0, 8, self::fpdfText($label), 0, 1);
                try {
                    $pdf->Image($imgPath, 10, 24, 190, 0);
                } catch (Throwable $e) {
                    $pdf->SetXY(10, 40);
                    $pdf->SetFont('Helvetica', '', 10);
                    $pdf->MultiCell(0, 6, self::fpdfText('Could not embed image for: ' . $label . '.'));
                }
            }
        } finally {
            foreach ($temps as $t) {
                @unlink($t);
            }
        }

        $out = $pdf->Output('S');
        if (!is_string($out)) {
            throw new RuntimeException('PDF output failed');
        }

        return $out;
    }

    private static function webpToJpegTemp(string $webpPath): ?string {
        if (!function_exists('imagecreatefromwebp') || !function_exists('imagejpeg')) {
            return null;
        }
        $im = @imagecreatefromwebp($webpPath);
        if ($im === false) {
            return null;
        }
        $tmp = tempnam(sys_get_temp_dir(), 'sa_webp_');
        if ($tmp === false) {
            imagedestroy($im);
            return null;
        }
        $jpg = $tmp . '.jpg';
        $ok = @imagejpeg($im, $jpg, 90);
        imagedestroy($im);
        @unlink($tmp);
        if (!$ok) {
            @unlink($jpg);
            return null;
        }

        return $jpg;
    }

    private static function fpdfText(string $s): string {
        $t = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $s);
        if ($t === false || $t === '') {
            $t = preg_replace('/[^\x20-\x7E]/', '?', $s) ?? $s;
        }

        return $t;
    }
}
