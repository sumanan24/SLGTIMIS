<?php
/**
 * Hostel bed stickers: 4 in × 1 in strip, two numbers side by side.
 */
class BedStickerPdf
{
    /**
     * @param list<array<string, mixed>> $beds
     */
    public static function download(array $beds, string $hostelName, string $dest = 'D', string $fileLabel = '')
    {
        $autoload = BASE_PATH . '/vendor/autoload.php';
        if (!is_file($autoload)) {
            throw new RuntimeException('PDF library is not installed.');
        }
        require_once $autoload;
        if (!class_exists('FPDF')) {
            throw new RuntimeException('FPDF is required to download bed stickers.');
        }

        $pdf = new FPDF('L', 'in', [4.0, 1.0]);
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetCompression(true);

        $codes = [];
        foreach ($beds as $bed) {
            $code = trim((string) ($bed['barcode'] ?? ''));
            if ($code !== '') {
                $codes[] = self::latin($code);
            }
        }
        $total = count($codes);
        for ($i = 0; $i < $total; $i += 2) {
            $pdf->AddPage('L', [4.0, 1.0]);
            self::drawNumber($pdf, 0.0, $codes[$i]);
            if (isset($codes[$i + 1])) {
                self::drawNumber($pdf, 2.0, $codes[$i + 1]);
            }
        }

        $label = trim($fileLabel) !== '' ? $fileLabel : $hostelName;
        $safe = preg_replace('/[^A-Za-z0-9._-]+/', '-', $label) ?: 'hostel';
        if ($dest === 'S') {
            return $pdf->Output('S');
        }
        $pdf->Output('D', $safe . '-bed-stickers.pdf');
        exit;
    }

    private static function drawNumber(FPDF $pdf, float $x, string $text): void
    {
        $maxW = 1.88;
        $size = 28;
        $pdf->SetFont('Helvetica', 'B', $size);
        while ($size > 10 && $pdf->GetStringWidth($text) > $maxW) {
            $size -= 1;
            $pdf->SetFont('Helvetica', 'B', $size);
        }
        $lineH = $size / 72.0;
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY($x + 0.06, (1.0 - $lineH) / 2);
        $pdf->Cell($maxW, $lineH, $text, 0, 0, 'C');
    }

    private static function latin(string $value): string
    {
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
