<?php
/**
 * Hostel student list: each room starts with its number, then Reg. number, NIC, and name.
 */
class RoomStudentListPdf
{
    /**
     * @param list<array{code:string,students:list<array<string,mixed>>}> $rooms
     */
    public static function download(array $rooms, string $hostelName, string $dest = 'D')
    {
        $autoload = BASE_PATH . '/vendor/autoload.php';
        if (!is_file($autoload)) {
            throw new RuntimeException('PDF library is not installed.');
        }
        require_once $autoload;
        if (!class_exists('FPDF')) {
            throw new RuntimeException('FPDF is required to download the student list.');
        }

        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(12, 12, 12);

        $wNo = 10;
        $wPhoto = 16;
        $wReg = 42;
        $wNic = 36;
        $wName = 82;
        $rowH = 16;
        $thumbs = [];
        $printed = 0;

        foreach ($rooms as $room) {
            $students = is_array($room['students'] ?? null) ? $room['students'] : [];
            if (!$students) {
                continue;
            }
            $printed++;
            $code = (string) ($room['code'] ?? '');
            $pdf->AddPage();
            self::roomHead($pdf, $hostelName, $code, $wNo + $wPhoto + $wReg + $wNic + $wName);
            self::tableHead($pdf, $wNo, $wPhoto, $wReg, $wNic, $wName);
            $n = 0;
            foreach ($students as $stu) {
                if ($pdf->GetY() > 268) {
                    $pdf->AddPage();
                    self::roomHead($pdf, $hostelName, $code, $wNo + $wPhoto + $wReg + $wNic + $wName);
                    self::tableHead($pdf, $wNo, $wPhoto, $wReg, $wNic, $wName);
                }
                $n++;
                $thumb = self::thumb((string) ($stu['photo_file'] ?? ''), $thumbs);
                $x = $pdf->GetX();
                $y = $pdf->GetY();
                $pdf->Cell($wNo, $rowH, (string) $n, 1, 0, 'C');
                $pdf->Cell($wPhoto, $rowH, '', 1, 0);
                if ($thumb !== null) {
                    $iw = 12;
                    $ih = 14;
                    $pdf->Image($thumb, $x + $wNo + ($wPhoto - $iw) / 2, $y + ($rowH - $ih) / 2, $iw, $ih);
                }
                $pdf->Cell($wReg, $rowH, self::fit($pdf, (string) ($stu['student_id'] ?? ''), $wReg), 1, 0, 'L');
                $pdf->Cell($wNic, $rowH, self::fit($pdf, (string) ($stu['student_nic'] ?? ''), $wNic), 1, 0, 'L');
                $pdf->Cell($wName, $rowH, self::fit($pdf, (string) ($stu['student_fullname'] ?? ''), $wName), 1, 1, 'L');
            }
        }

        if ($printed === 0) {
            $pdf->AddPage();
            $pdf->SetFont('Helvetica', '', 11);
            $pdf->Cell(0, 8, 'No students are allocated in this hostel.', 0, 1);
        }

        $safe = preg_replace('/[^A-Za-z0-9._-]+/', '-', $hostelName) ?: 'hostel';
        if ($dest === 'S') {
            $bytes = $pdf->Output('S');
            self::cleanup($thumbs);
            return $bytes;
        }
        $pdf->Output('D', $safe . '-student-list.pdf');
        self::cleanup($thumbs);
        exit;
    }

    private static function roomHead(FPDF $pdf, string $hostelName, string $code, float $width): void
    {
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetTextColor(80, 80, 80);
        $pdf->Cell(0, 6, self::latin($hostelName . ' student list'), 0, 1, 'L');
        $pdf->SetFillColor(22, 50, 79);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Helvetica', 'B', 16);
        $pdf->Cell($width, 10, self::latin($code), 0, 1, 'L', true);
        $pdf->Ln(2);
    }

    private static function tableHead(FPDF $pdf, float $wNo, float $wPhoto, float $wReg, float $wNic, float $wName): void
    {
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFillColor(241, 245, 249);
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->Cell($wNo, 7, '#', 1, 0, 'C', true);
        $pdf->Cell($wPhoto, 7, 'Photo', 1, 0, 'C', true);
        $pdf->Cell($wReg, 7, 'Reg. number', 1, 0, 'L', true);
        $pdf->Cell($wNic, 7, 'NIC', 1, 0, 'L', true);
        $pdf->Cell($wName, 7, 'Name', 1, 1, 'L', true);
        $pdf->SetFont('Helvetica', '', 9);
    }

    /**
     * @param list<string> $thumbs
     */
    private static function thumb(string $path, array &$thumbs): ?string
    {
        if ($path === '' || !is_file($path)) {
            return null;
        }
        if (!function_exists('imagecreatefromstring') || !function_exists('imagejpeg')) {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            return in_array($ext, ['jpg', 'jpeg', 'png', 'gif'], true) ? $path : null;
        }
        $bin = @file_get_contents($path);
        if ($bin === false || $bin === '') {
            return null;
        }
        $src = @imagecreatefromstring($bin);
        if (!$src) {
            return null;
        }
        $sw = imagesx($src);
        $sh = imagesy($src);
        if ($sw < 1 || $sh < 1) {
            imagedestroy($src);
            return null;
        }
        $scale = min(80 / $sw, 100 / $sh, 1);
        $dw = max(1, (int) round($sw * $scale));
        $dh = max(1, (int) round($sh * $scale));
        $dst = imagecreatetruecolor($dw, $dh);
        if (!$dst) {
            imagedestroy($src);
            return null;
        }
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefilledrectangle($dst, 0, 0, $dw, $dh, $white);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $dw, $dh, $sw, $sh);
        imagedestroy($src);
        $jpg = tempnam(sys_get_temp_dir(), 'roomstu');
        if ($jpg === false) {
            imagedestroy($dst);
            return null;
        }
        $jpgFile = $jpg . '.jpg';
        @unlink($jpg);
        $ok = imagejpeg($dst, $jpgFile, 72);
        imagedestroy($dst);
        if (!$ok || !is_file($jpgFile)) {
            return null;
        }
        $thumbs[] = $jpgFile;
        return $jpgFile;
    }

    /**
     * @param list<string> $thumbs
     */
    private static function cleanup(array $thumbs): void
    {
        foreach ($thumbs as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    private static function fit(FPDF $pdf, string $text, float $width): string
    {
        $text = self::latin(trim($text));
        if ($text === '') {
            return '';
        }
        $limit = $width - 2;
        if ($pdf->GetStringWidth($text) <= $limit) {
            return $text;
        }
        while ($text !== '' && $pdf->GetStringWidth($text . '...') > $limit) {
            $text = substr($text, 0, -1);
        }
        return rtrim($text) . '...';
    }

    private static function latin(string $value): string
    {
        $value = str_replace(["\r", "\n", "\t"], ' ', $value);
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $value);
            if ($converted !== false && $converted !== '') {
                return $converted;
            }
        }
        return $value;
    }
}
