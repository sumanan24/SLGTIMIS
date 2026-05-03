<?php
/**
 * Month attendance register as .xlsx (native OOXML; zip via StoredZipWriter, no ext-zip required).
 * Layout: row 1 merged title (month + course + group); rows 2–4 headers with
 * # / Reg No / Name / NIC merged vertically; each teaching weekday shows day-of-month only
 * over two columns with slots 1–4 in a 2×2 grid (day-of-month only, no year/month text); two data rows per student with a 2×2
 * empty block per day for marks. SL public holidays excluded upstream (SriLankaPublicHolidays).
 */
class AttendanceRegisterExportXlsx {
    /**
     * @param array<int,array<string,mixed>> $students
     * @param array<int,array<string,mixed>>  $workingDays
     */
    public static function stream(array $students, array $workingDays, string $month, string $courseId, string $group): void {
        require_once __DIR__ . '/StoredZipWriter.php';

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $layout = self::buildLayout($students, $workingDays, $month, $courseId, $group);
        $sheetXml = self::buildSheetXml($layout);
        if ($sheetXml === '' || strpos($sheetXml, '<worksheet') === false) {
            throw new RuntimeException('Worksheet XML is empty or invalid.');
        }

        $libErr = libxml_use_internal_errors(true);
        $xmlOk = @simplexml_load_string($sheetXml);
        libxml_clear_errors();
        libxml_use_internal_errors($libErr);
        if ($xmlOk === false) {
            error_log('AttendanceRegisterExportXlsx: sheet1.xml failed libxml parse');
            throw new RuntimeException('Worksheet XML failed validation.');
        }

        $stylesXml = self::buildStylesXml();
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

        $tmp = tempnam(sys_get_temp_dir(), 'slg');
        if ($tmp === false) {
            throw new RuntimeException('Could not create temp file.');
        }

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

        try {
            StoredZipWriter::writeFile($tmp, $entries);
        } catch (Throwable $e) {
            @unlink($tmp);
            throw $e;
        }

        $size = @filesize($tmp);
        if ($size === false || $size < 200) {
            @unlink($tmp);
            throw new RuntimeException('Output file too small.');
        }

        $filename = 'attendance_sheet_' . str_replace('-', '_', $month) . '_' . preg_replace('/[^A-Za-z0-9_-]+/', '_', $courseId);
        if ($group !== '') {
            $filename .= '_group' . preg_replace('/[^A-Za-z0-9_-]+/', '_', $group);
        }
        $filename .= '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Content-Length: ' . $size);

        readfile($tmp);
        @unlink($tmp);
    }

    /**
     * @param array<int,array<string,mixed>> $students
     * @param array<int,array<string,mixed>> $workingDays
     * @return array{cellsByRow: array<int,array<int,array{0:string,1:int}>>, merges: array<int,string>, lastCol: int, lastRow: int}
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

        /** @var array<int,array<int,array{0:string,1:int}>> $cells */
        $cells = [];
        $merges = [];

        $set = static function (int $r, int $c, string $val, int $style) use (&$cells): void {
            $cells[$r][$c] = [$val, $style];
        };

        $lastLet = self::colLetter($lastCol);
        $merges[] = 'A1:' . $lastLet . '1';
        $set(1, 1, $title, 2);

        $merges[] = 'A2:A4';
        $merges[] = 'B2:B4';
        $merges[] = 'C2:C4';
        $merges[] = 'D2:D4';
        $set(2, 1, '#', 1);
        $set(2, 2, 'Reg No', 1);
        $set(2, 3, 'Name', 1);
        $set(2, 4, 'NIC', 1);

        $col = 5;
        foreach ($workingDays as $day) {
            $dateStr = self::dayHeaderOnly(isset($day['date']) ? (string) $day['date'] : '');
            $c0 = self::colLetter($col);
            $c1 = self::colLetter($col + 1);
            $merges[] = $c0 . '2:' . $c1 . '2';
            $set(2, $col, $dateStr, 1);
            $set(3, $col, '1', 1);
            $set(3, $col + 1, '2', 1);
            $set(4, $col, '3', 1);
            $set(4, $col + 1, '4', 1);
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

            $set($row, 1, (string) $num, 0);
            $set($row, 2, $sid, 0);
            $set($row, 3, $name, 0);
            $set($row, 4, $nic, 0);

            $dc = 5;
            foreach ($workingDays as $_d) {
                $set($row, $dc, '', 0);
                $set($row, $dc + 1, '', 0);
                $set($r2, $dc, '', 0);
                $set($r2, $dc + 1, '', 0);
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

    /**
     * @param array{cellsByRow: array<int,array<int,array{0:string,1:int}>>, merges: array<int,string>, lastCol: int, lastRow: int} $layout
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
                $pair = $cellsByRow[$r][$ci];
                $val = $pair[0];
                $st = $pair[1];
                $addr = self::colLetter((int) $ci) . $r;
                $sb .= '<c r="' . self::escAttr($addr) . '" s="' . (int) $st . '" t="inlineStr">';
                $sb .= '<is><t>' . self::escT((string) $val) . '</t></is>';
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

    private static function buildStylesXml(): string {
        $k = '<color rgb="FF000000"/>';
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="3">'
            . '<font><sz val="11"/>' . $k . '<name val="Calibri"/><family val="2"/></font>'
            . '<font><b/><sz val="11"/>' . $k . '<name val="Calibri"/><family val="2"/></font>'
            . '<font><b/><sz val="14"/>' . $k . '<name val="Calibri"/><family val="2"/></font>'
            . '</fonts>'
            . '<fills count="1"><fill><patternFill patternType="none"/></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="3">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    private static function safeSheetName(string $month): string {
        $t = strtoupper(date('M-y', strtotime($month . '-01')));
        $t = preg_replace('/[^A-Za-z0-9 _-]/', '', $t);
        $t = substr($t, 0, 31);
        return $t !== '' ? $t : 'Register';
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

    /** Attribute value (A1, A1:Z9, sheet name, etc.) */
    private static function escAttr(string $s): string {
        $s = preg_replace('/[\x00-\x1F\x7F]/', '', $s);
        $flags = ENT_XML1 | ENT_QUOTES;
        if (defined('ENT_SUBSTITUTE')) {
            $flags |= ENT_SUBSTITUTE;
        }
        $h = htmlspecialchars($s, $flags, 'UTF-8');
        return ($h === false) ? '' : $h;
    }

    /** Cell text inside &lt;t&gt; */
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
        return ($h === false) ? '' : $h;
    }
}
