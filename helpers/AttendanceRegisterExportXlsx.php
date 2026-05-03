<?php
/**
 * Month attendance register as .xlsx.
 * Uses PhpSpreadsheet (composer) when vendor + ZipArchive are available; otherwise falls back to
 * native OOXML + StoredZipWriter (no ext-zip). Same layout: title row, merged headers, 2×2 slots per day, two rows per student.
 */
class AttendanceRegisterExportXlsx {
    /**
     * @param array<int,array<string,mixed>> $students
     * @param array<int,array<string,mixed>>  $workingDays
     */
    public static function stream(array $students, array $workingDays, string $month, string $courseId, string $group): void {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $layout = self::buildLayout($students, $workingDays, $month, $courseId, $group);

        $filename = 'attendance_sheet_' . str_replace('-', '_', $month) . '_' . preg_replace('/[^A-Za-z0-9_-]+/', '_', $courseId);
        if ($group !== '') {
            $filename .= '_group' . preg_replace('/[^A-Za-z0-9_-]+/', '_', $group);
        }
        $filename .= '.xlsx';

        $root = dirname(__DIR__);
        $autoload = $root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
        $canPhps = is_readable($autoload) && class_exists('ZipArchive', false);

        if ($canPhps) {
            require_once $autoload;
            $canPhps = class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class);
        }

        if ($canPhps) {
            try {
                $spreadsheet = self::buildSpreadsheetFromLayout($layout, $month);
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
                header('Cache-Control: private, max-age=0, must-revalidate');
                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                $writer->save('php://output');
                $spreadsheet->disconnectWorksheets();
                return;
            } catch (Throwable $e) {
                error_log('AttendanceRegisterExportXlsx PhpSpreadsheet: ' . $e->getMessage());
            }
        }

        self::streamNative($layout, $month, $filename);
    }

    /**
     * @param array{cellsByRow: array<int,array<int,string>>, merges: array<int,string>, lastCol: int, lastRow: int} $layout
     */
    private static function buildSpreadsheetFromLayout(array $layout, string $month): \PhpOffice\PhpSpreadsheet\Spreadsheet {
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

        for ($col = 5; $col <= $lastCol; $col += 2) {
            $c0 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $c1 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1);
            $sheet->getStyle($c0 . '2:' . $c1 . '2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($c0 . '3:' . $c1 . '4')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
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
     * @param array{cellsByRow: array<int,array<int,string>>, merges: array<int,string>, lastCol: int, lastRow: int} $layout
     */
    private static function streamNative(array $layout, string $month, string $filename): void {
        $storedZip = __DIR__ . DIRECTORY_SEPARATOR . 'StoredZipWriter.php';
        if (!is_readable($storedZip)) {
            throw new RuntimeException('StoredZipWriter.php is missing from helpers.');
        }
        require_once $storedZip;

        $sheetXml = self::buildSheetXml($layout);
        if ($sheetXml === '' || strpos($sheetXml, '<worksheet') === false) {
            throw new RuntimeException('Worksheet XML is empty or invalid.');
        }

        $libErr = libxml_use_internal_errors(true);
        $parseFlags = LIBXML_NONET | (defined('LIBXML_PARSEHUGE') ? LIBXML_PARSEHUGE : 0);
        $xmlOk = @simplexml_load_string($sheetXml, 'SimpleXMLElement', $parseFlags);
        $libxmlErrors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($libErr);
        if ($xmlOk === false) {
            $msgs = [];
            foreach ($libxmlErrors as $err) {
                $msgs[] = trim($err->message) . ' (line ' . (int) $err->line . ')';
            }
            error_log('AttendanceRegisterExportXlsx native: libxml ' . ($msgs !== [] ? implode('; ', $msgs) : '(no detail)'));
            throw new RuntimeException('Worksheet XML failed validation.');
        }

        $stylesXml = self::buildStylesXmlNative();
        $sheetName = self::safeSheetName($month);
        $workbookXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . self::escAttr($sheetName) . '" sheetId="1" r:id="rId1"/></sheets></workbook>';

        $workbookRels = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';

        $rootRels = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            . '</Relationships>';

        $contentTypes = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            . '</Types>';

        $appXml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties">'
            . '<Application>SLGTI-SIS</Application></Properties>';
        $coreXml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" '
            . 'xmlns:dc="http://purl.org/dc/elements/1.1/"><dc:creator>SLGTI-SIS</dc:creator></cp:coreProperties>';

        $entries = [
            '[Content_Types].xml' => $contentTypes,
            '_rels/.rels' => $rootRels,
            'docProps/app.xml' => $appXml,
            'docProps/core.xml' => $coreXml,
            'xl/workbook.xml' => $workbookXml,
            'xl/_rels/workbook.xml.rels' => $workbookRels,
            'xl/styles.xml' => $stylesXml,
            'xl/worksheets/sheet1.xml' => $sheetXml,
        ];

        $mem = fopen('php://memory', 'r+b');
        if ($mem === false) {
            throw new RuntimeException('Excel export: could not open in-memory buffer.');
        }
        try {
            StoredZipWriter::writeStream($mem, $entries);
            rewind($mem);
            $binary = stream_get_contents($mem);
        } finally {
            fclose($mem);
        }
        if ($binary === false || strlen($binary) < 200) {
            throw new RuntimeException('Excel export: generated file too small.');
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Content-Length: ' . (string) strlen($binary));
        echo $binary;
    }

    /**
     * @param array{cellsByRow: array<int,array<int,string>>, merges: array<int,string>, lastCol: int, lastRow: int} $layout
     */
    private static function buildSheetXml(array $layout): string {
        $cellsByRow = $layout['cellsByRow'];
        $merges = $layout['merges'];
        $lastCol = $layout['lastCol'];
        $lastRow = $layout['lastRow'];
        if ($lastCol < 1 || $lastRow < 1) {
            return '';
        }

        $lastLet = self::colLetter($lastCol);
        $dim = 'A1:' . $lastLet . $lastRow;

        $sb = '<?xml version="1.0" encoding="UTF-8"?>';
        $sb .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
        $sb .= '<dimension ref="' . self::escAttr($dim) . '"/>';
        $sb .= '<sheetViews><sheetView workbookViewId="0"/></sheetViews>';
        $sb .= '<sheetFormatPr defaultRowHeight="15" defaultColWidth="9"/>';
        $sb .= '<sheetData>';

        for ($r = 1; $r <= $lastRow; $r++) {
            if (!isset($cellsByRow[$r]) || $cellsByRow[$r] === []) {
                continue;
            }
            $cols = array_keys($cellsByRow[$r]);
            sort($cols, SORT_NUMERIC);
            $spanLo = (int) $cols[0];
            $spanHi = (int) $cols[count($cols) - 1];
            $sb .= '<row r="' . $r . '" spans="' . $spanLo . ':' . $spanHi . '">';
            foreach ($cols as $ci) {
                $val = (string) ($cellsByRow[$r][$ci] ?? '');
                $addr = self::colLetter((int) $ci) . $r;
                $sb .= '<c r="' . self::escAttr($addr) . '" s="0" t="inlineStr">';
                $sb .= '<is><t>' . self::escT($val) . '</t></is>';
                $sb .= '</c>';
            }
            $sb .= '</row>';
        }

        $sb .= '</sheetData>';
        if ($merges !== []) {
            $sb .= '<mergeCells count="' . count($merges) . '">';
            foreach ($merges as $ref) {
                $sb .= '<mergeCell ref="' . self::escAttr($ref) . '"/>';
            }
            $sb .= '</mergeCells>';
        }
        $sb .= '</worksheet>';
        return $sb;
    }

    private static function buildStylesXmlNative(): string {
        $k = '<color rgb="FF000000"/>';
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="1"><font><sz val="11"/>' . $k . '<name val="Calibri"/><family val="2"/></font></fonts>'
            . '<fills count="1"><fill><patternFill patternType="none"/></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
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

        $lastLet = self::colLetter($lastCol);
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
            $c0 = self::colLetter($col);
            $c1 = self::colLetter($col + 1);
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

    private static function colLetter(int $colIndex): string {
        $s = '';
        $n = $colIndex;
        while ($n > 0) {
            $n--;
            $s = chr(65 + ($n % 26)) . $s;
            $n = intdiv($n, 26);
        }
        return $s;
    }

    private static function safeSheetName(string $month): string {
        $t = strtoupper(date('M-y', strtotime($month . '-01')));
        $t = preg_replace('/[^A-Za-z0-9 _-]/', '', $t);
        $t = substr($t, 0, 31);
        return $t !== '' ? $t : 'Register';
    }

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

    private static function escAttr(string $s): string {
        $s = preg_replace('/[\x00-\x1F\x7F]/', '', $s);
        $flags = ENT_XML1 | ENT_QUOTES;
        if (defined('ENT_SUBSTITUTE')) {
            $flags |= ENT_SUBSTITUTE;
        }
        $h = htmlspecialchars($s, $flags, 'UTF-8');
        if ($h === false || $h === '') {
            return '';
        }
        $stripped = @preg_replace('/[\x{FFFE}\x{FFFF}]/u', '', $h);
        return is_string($stripped) ? $stripped : $h;
    }

    private static function escT(string $s): string {
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $s);
        if ($s !== '' && function_exists('mb_check_encoding') && !mb_check_encoding($s, 'UTF-8')) {
            $s = @mb_convert_encoding($s, 'UTF-8', 'Windows-1252');
            if ($s === false || (function_exists('mb_check_encoding') && !mb_check_encoding($s, 'UTF-8'))) {
                $s = preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '', (string) $s);
            }
        }
        $flags = ENT_XML1 | ENT_COMPAT;
        if (defined('ENT_SUBSTITUTE')) {
            $flags |= ENT_SUBSTITUTE;
        }
        $h = htmlspecialchars($s, $flags, 'UTF-8');
        if ($h === false) {
            return '';
        }
        if ($h !== '') {
            $stripped = @preg_replace('/[\x{FFFE}\x{FFFF}]/u', '', $h);
            if (is_string($stripped)) {
                $h = $stripped;
            }
        }
        return $h;
    }
}
