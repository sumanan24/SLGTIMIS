<?php
/**
 * Month attendance register as .xlsx via PhpSpreadsheet (composer: phpoffice/phpspreadsheet).
 * Layout: row 1 merged title (month + course + group); rows 2–4 headers with
 * # / Reg No / Name / NIC merged vertically; each teaching weekday shows day-of-month only
 * over two columns with slots 1–4 in a 2×2 grid; two data rows per student with a 2×2
 * empty block per day. SL public holidays excluded upstream (SriLankaPublicHolidays).
 */
class AttendanceRegisterExportXlsx {
    /**
     * @param array<int,array<string,mixed>> $students
     * @param array<int,array<string,mixed>>  $workingDays
     */
    public static function stream(array $students, array $workingDays, string $month, string $courseId, string $group): void {
        $autoload = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
        if (!is_readable($autoload)) {
            throw new RuntimeException('Composer vendor/autoload.php is missing; run composer install in the project root.');
        }
        require_once $autoload;

        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('PHP zip extension (ZipArchive) is required for PhpSpreadsheet .xlsx export. Enable extension=zip in php.ini.');
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $spreadsheet = self::buildSpreadsheet($students, $workingDays, $month, $courseId, $group);

        $filename = 'attendance_sheet_' . str_replace('-', '_', $month) . '_' . preg_replace('/[^A-Za-z0-9_-]+/', '_', $courseId);
        if ($group !== '') {
            $filename .= '_group' . preg_replace('/[^A-Za-z0-9_-]+/', '_', $group);
        }
        $filename .= '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        $spreadsheet->disconnectWorksheets();
    }

    /**
     * @param array<int,array<string,mixed>> $students
     * @param array<int,array<string,mixed>>  $workingDays
     */
    private static function buildSpreadsheet(array $students, array $workingDays, string $month, string $courseId, string $group): \PhpOffice\PhpSpreadsheet\Spreadsheet {
        $layout = self::buildLayout($students, $workingDays, $month, $courseId, $group);
        $cellsByRow = $layout['cellsByRow'];
        $merges = $layout['merges'];
        $lastCol = $layout['lastCol'];
        $lastRow = $layout['lastRow'];

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(self::safeSheetName($month));

        $lastLet = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastCol);

        foreach ($cellsByRow as $r => $cols) {
            foreach ($cols as $c => $val) {
                $coord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex((int) $c) . $r;
                $val = (string) $val;
                if ($c === 2 || $c === 4) {
                    $sheet->setCellValueExplicit($coord, $val, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                } else {
                    $sheet->setCellValue($coord, $val);
                }
            }
        }

        foreach ($merges as $ref) {
            $sheet->mergeCells($ref);
        }

        $sheet->getStyle('A1:' . $lastLet . '1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1:' . $lastLet . '1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle('A2:' . $lastLet . '4')->getFont()->setBold(true);
        $sheet->getStyle('A2:D4')->getAlignment()
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER)
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $col = 5;
        foreach ($workingDays as $_day) {
            $c0 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $c1 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1);
            $sheet->getStyle($c0 . '2:' . $c1 . '2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($c0 . '3:' . $c1 . '4')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $col += 2;
        }

        if ($lastRow >= 5) {
            $sheet->getStyle('A5:D' . $lastRow)->getAlignment()
                ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        }

        $sheet->getColumnDimension('C')->setWidth(28);
        $sheet->getColumnDimension('B')->setWidth(14);
        $sheet->getColumnDimension('D')->setWidth(14);

        return $spreadsheet;
    }

    /**
     * @param array<int,array<string,mixed>> $students
     * @param array<int,array<string,mixed>> $workingDays
     * @return array{cellsByRow: array<int,array<int,string>>, merges: array<int,string>, lastCol: int, lastRow: int}
     */
    private static function buildLayout(array $students, array $workingDays, string $month, string $courseId, string $group): array {
        $w = count($workingDays);
        $lastCol = 4 + 2 * $w;
        $n = count($students);
        $lastRow = 4 + 2 * $n;

        $ts = strtotime($month . '-01');
        $monthLabel = $ts ? date('F Y', $ts) : $month;
        $title = $monthLabel;
        if ($courseId !== '') {
            $title .= ' — ' . $courseId;
        }
        if ($group !== '') {
            $title .= ' — Group ' . $group;
        }
        $title = self::sanitizeText($title);

        /** @var array<int,array<int,string>> $cells */
        $cells = [];
        $merges = [];

        $set = static function (int $r, int $c, string $val) use (&$cells): void {
            $cells[$r][$c] = $val;
        };

        $lastLet = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastCol);
        $merges[] = 'A1:' . $lastLet . '1';
        $set(1, 1, $title);

        $merges[] = 'A2:A4';
        $merges[] = 'B2:B4';
        $merges[] = 'C2:C4';
        $merges[] = 'D2:D4';
        $set(2, 1, '#');
        $set(2, 2, 'Reg No');
        $set(2, 3, 'Name');
        $set(2, 4, 'NIC');

        $col = 5;
        foreach ($workingDays as $day) {
            $dateStr = self::dayHeaderOnly(isset($day['date']) ? (string) $day['date'] : '');
            $c0 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $c1 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1);
            $merges[] = $c0 . '2:' . $c1 . '2';
            $set(2, $col, $dateStr);
            $set(3, $col, '1');
            $set(3, $col + 1, '2');
            $set(4, $col, '3');
            $set(4, $col + 1, '4');
            $col += 2;
        }

        $row = 5;
        $num = 1;
        foreach ($students as $stu) {
            $r2 = $row + 1;
            $merges[] = 'A' . $row . ':A' . $r2;
            $merges[] = 'B' . $row . ':B' . $r2;
            $merges[] = 'C' . $row . ':C' . $r2;
            $merges[] = 'D' . $row . ':D' . $r2;

            $sid = (string) ($stu['student_id'] ?? '');
            $name = trim((string) ($stu['export_name'] ?? ''));
            if ($name === '') {
                $name = trim((string) ($stu['student_fullname'] ?? ''));
            }
            if ($name === '') {
                $name = trim((string) ($stu['student_ininame'] ?? ''));
            }
            if ($name === '') {
                $name = $sid;
            }
            $name = self::sanitizeText($name);
            $nic = self::sanitizeText((string) ($stu['student_nic'] ?? ''));

            $set($row, 1, (string) $num);
            $set($row, 2, $sid);
            $set($row, 3, $name);
            $set($row, 4, $nic);

            $dc = 5;
            foreach ($workingDays as $_d) {
                $set($row, $dc, '');
                $set($row, $dc + 1, '');
                $set($r2, $dc, '');
                $set($r2, $dc + 1, '');
                $dc += 2;
            }
            $num++;
            $row += 2;
        }

        return [
            'cellsByRow' => $cells,
            'merges' => $merges,
            'lastCol' => $lastCol,
            'lastRow' => $lastRow,
        ];
    }

    private static function safeSheetName(string $month): string {
        $t = strtoupper(date('M-y', strtotime($month . '-01')));
        $t = preg_replace('/[^A-Za-z0-9 _-]/', '', $t);
        $t = substr($t, 0, 31);
        return $t !== '' ? $t : 'Register';
    }

    /** Day of month only (01–31) for column headers; month/year come from the title row. */
    private static function dayHeaderOnly(string $dateYmd): string {
        $dateYmd = trim($dateYmd);
        if ($dateYmd === '') {
            return '';
        }
        $ts = strtotime($dateYmd);
        if ($ts === false) {
            return $dateYmd;
        }
        return date('d', $ts);
    }

    private static function sanitizeText(string $s): string {
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $s);
        if ($s !== '' && function_exists('mb_check_encoding') && !mb_check_encoding($s, 'UTF-8')) {
            $t = @mb_convert_encoding($s, 'UTF-8', 'Windows-1252');
            if ($t !== false && (!function_exists('mb_check_encoding') || mb_check_encoding($t, 'UTF-8'))) {
                $s = $t;
            } else {
                $s = preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '', $s);
            }
        }
        return $s;
    }
}
