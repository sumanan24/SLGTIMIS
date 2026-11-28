<?php
/**
 * Full A4 hostel allocation list. One room per page, table across the sheet.
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
        $pdf->AliasNbPages();
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(12, 12, 12);

        $widths = [9, 18, 36, 40, 32, 51];
        $rowH = 20;
        $thumbs = [];
        $printed = 0;

        foreach ($rooms as $room) {
            $students = is_array($room['students'] ?? null) ? $room['students'] : [];
            if (!$students) {
                continue;
            }
            if ($printed > 0) {
                self::footer($pdf, $hostelName);
            }
            $printed++;
            $code = (string) ($room['code'] ?? '');
            $pdf->AddPage();
            self::roomHead($pdf, $hostelName, $code, count($students), false);
            self::tableHead($pdf, $widths);
            $n = 0;
            foreach ($students as $stu) {
                if ($pdf->GetY() + $rowH > 246) {
                    self::footer($pdf, $hostelName);
                    $pdf->AddPage();
                    self::roomHead($pdf, $hostelName, $code, count($students), true);
                    self::tableHead($pdf, $widths);
                }
                $n++;
                self::studentRow($pdf, $stu, $n, $widths, $rowH, $thumbs);
            }
            if ($pdf->GetY() < 246) {
                self::signatures($pdf);
            }
        }

        if ($printed === 0) {
            $pdf->AddPage();
            self::roomHead($pdf, $hostelName, $hostelName, 0, false);
            $pdf->SetFont('Helvetica', '', 11);
            $pdf->SetTextColor(30, 41, 59);
            $pdf->Cell(0, 8, 'No students are allocated in this hostel.', 0, 1);
        }
        self::footer($pdf, $hostelName);

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

    private static function roomHead(FPDF $pdf, string $hostelName, string $code, int $count, bool $continued): void
    {
        $pdf->SetFillColor(15, 42, 68);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Cell(0, 8, self::latin('Sri Lanka German Training Institute'), 0, 1, 'L', true);
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->Cell(0, 5, self::latin('Hostel allocation  |  Student list'), 0, 1, 'L', true);
        $pdf->Ln(4);
        $pdf->SetTextColor(15, 42, 68);
        $pdf->SetFont('Helvetica', 'B', 18);
        $title = $code . ($continued ? '  (continued)' : '');
        $pdf->Cell(0, 10, self::latin($title), 0, 1, 'L');
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetTextColor(71, 85, 105);
        $meta = $hostelName . '    |    ' . $count . ($count === 1 ? ' student' : ' students') . '    |    ' . date('d F Y');
        $pdf->Cell(0, 6, self::latin($meta), 0, 1, 'L');
        $pdf->Ln(2);
    }

    /**
     * @param list<float> $widths
     */
    private static function tableHead(FPDF $pdf, array $widths): void
    {
        $pdf->SetFillColor(15, 42, 68);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetDrawColor(15, 42, 68);
        $pdf->SetFont('Helvetica', 'B', 9);
        $labels = ['#', 'Photo', 'Bed', 'Reg. number', 'NIC', 'Name'];
        $align = ['C', 'C', 'L', 'L', 'L', 'L'];
        foreach ($widths as $i => $width) {
            $pdf->Cell($width, 8, $labels[$i], 1, $i === count($widths) - 1 ? 1 : 0, $align[$i], true);
        }
        $pdf->SetTextColor(15, 23, 42);
        $pdf->SetFont('Helvetica', '', 9);
    }

    /**
     * @param array<string,mixed> $stu
     * @param list<float> $widths
     * @param list<string> $thumbs
     */
    private static function studentRow(FPDF $pdf, array $stu, int $n, array $widths, float $rowH, array &$thumbs): void
    {
        $fill = $n % 2 === 0;
        $pdf->SetFillColor($fill ? 248 : 255, $fill ? 250 : 255, $fill ? 252 : 255);
        $pdf->SetDrawColor(226, 232, 240);
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $pdf->Cell($widths[0], $rowH, (string) $n, 1, 0, 'C', $fill);
        $pdf->Cell($widths[1], $rowH, '', 1, 0, 'C', $fill);
        $thumb = self::thumb((string) ($stu['photo_file'] ?? ''), $thumbs);
        if ($thumb !== null) {
            $iw = 14;
            $ih = 17;
            $pdf->Image($thumb, $x + $widths[0] + ($widths[1] - $iw) / 2, $y + ($rowH - $ih) / 2, $iw, $ih);
        }
        $pdf->Cell($widths[2], $rowH, self::fit($pdf, ' ' . (string) ($stu['bed_code'] ?? ''), $widths[2]), 1, 0, 'L', $fill);
        $pdf->Cell($widths[3], $rowH, self::fit($pdf, ' ' . (string) ($stu['student_id'] ?? ''), $widths[3]), 1, 0, 'L', $fill);
        $pdf->Cell($widths[4], $rowH, self::fit($pdf, ' ' . (string) ($stu['student_nic'] ?? ''), $widths[4]), 1, 0, 'L', $fill);
        $pdf->Cell($widths[5], $rowH, self::fit($pdf, ' ' . (string) ($stu['student_fullname'] ?? ''), $widths[5]), 1, 1, 'L', $fill);
    }

    private static function signatures(FPDF $pdf): void
    {
        $pdf->SetY(250);
        $pdf->SetTextColor(30, 41, 59);
        $pdf->SetDrawColor(148, 163, 184);
        $pdf->SetFont('Helvetica', '', 9);
        $labels = ['Warden', 'SAO / Registrar', 'Date'];
        foreach ($labels as $i => $label) {
            $pdf->Cell(62, 6, $label, 0, $i === 2 ? 1 : 0, 'L');
        }
        $pdf->Ln(8);
        foreach ($labels as $i => $label) {
            $pdf->Cell(54, 6, '', 'B', 0);
            $pdf->Cell(8, 6, '', 0, $i === 2 ? 1 : 0);
        }
    }

    private static function footer(FPDF $pdf, string $hostelName): void
    {
        $pdf->SetY(-14);
        $pdf->SetDrawColor(203, 213, 225);
        $pdf->SetLineWidth(0.2);
        $y = $pdf->GetY();
        $pdf->Line(12, $y, 198, $y);
        $pdf->Ln(1.5);
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(0, 5, self::latin('SLGTI  |  ' . $hostelName . ' allocated students  |  Page ' . $pdf->PageNo() . ' of {nb}'), 0, 0, 'L');
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
        $scale = min(90 / $sw, 110 / $sh, 1);
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
