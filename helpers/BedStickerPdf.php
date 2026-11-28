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

        foreach (self::pairs($beds) as $pair) {
            $pdf->AddPage('L', [4.0, 1.0]);
            if ($pair[0] !== '') {
                self::drawNumber($pdf, 0.0, self::latin($pair[0]));
            }
            if ($pair[1] !== '') {
                self::drawNumber($pdf, 2.0, self::latin($pair[1]));
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

    /**
     * One strip is T1 beside B1, then T2 beside B2, for each room.
     *
     * @param list<array<string, mixed>> $beds
     * @return list<array{0:string,1:string}>
     */
    public static function pairs(array $beds): array
    {
        $byRoom = [];
        foreach ($beds as $bed) {
            $roomId = (string) ($bed['room_id'] ?? '');
            $code = trim((string) ($bed['barcode'] ?? ''));
            $bedNo = strtoupper(trim((string) ($bed['bed_no'] ?? '')));
            if ($roomId === '' || $code === '' || $bedNo === '') {
                continue;
            }
            if (!isset($byRoom[$roomId])) {
                $byRoom[$roomId] = ['T' => [], 'B' => []];
            }
            $side = $bedNo[0] === 'B' ? 'B' : 'T';
            $index = (int) substr($bedNo, 1);
            if ($index < 1) {
                $index = count($byRoom[$roomId][$side]) + 1;
            }
            $byRoom[$roomId][$side][$index] = $code;
        }
        $pairs = [];
        foreach ($byRoom as $room) {
            ksort($room['T']);
            ksort($room['B']);
            $tops = array_values($room['T']);
            $bottoms = array_values($room['B']);
            $count = max(count($tops), count($bottoms));
            for ($i = 0; $i < $count; $i++) {
                $pairs[] = [$tops[$i] ?? '', $bottoms[$i] ?? ''];
            }
        }
        return $pairs;
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
