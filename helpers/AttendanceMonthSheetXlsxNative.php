<?php
/**
 * Minimal .xlsx (OOXML) writer for the month attendance blank sheet — no PhpSpreadsheet (PHP 7.4–safe).
 */
class AttendanceMonthSheetXlsxNative {
    /**
     * @param array<int,array<string,mixed>> $students
     * @param array<int,array{date:string,day:string}> $calendarDays
     */
    public static function stream(array $students, array $calendarDays, string $month, string $courseId, string $group): void {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('PHP zip extension (ZipArchive) is required for Excel export.');
        }

        $title = strtoupper(date('M-y', strtotime($month . '-01')));
        $sheetName = substr(preg_replace('/[^A-Za-z0-9 _-]/', '', $title), 0, 31) ?: 'Attendance';

        $w = count($calendarDays);
        $lastCol = 4 + $w * 2;
        $n = count($students);
        $lastRow = 4 + $n * 2;

        $merges = [];
        $cells = [];

        $lastColLet = self::colLetter($lastCol);
        $merges[] = 'A1:' . $lastColLet . '1';
        $cells['A1'] = [$title, 2];

        $merges[] = 'A2:A4';
        $cells['A2'] = ['#', 1];
        $merges[] = 'B2:B4';
        $cells['B2'] = ['Student ID', 1];
        $merges[] = 'C2:C4';
        $cells['C2'] = ['Name with Initials', 1];
        $merges[] = 'D2:D4';
        $cells['D2'] = ['NIC', 1];

        $col = 5;
        foreach ($calendarDays as $dayInfo) {
            $c0 = self::colLetter($col);
            $c1 = self::colLetter($col + 1);
            $merges[] = $c0 . '2:' . $c1 . '2';
            $cells[$c0 . '2'] = [(string) ((int) $dayInfo['day']), 1];
            $cells[$c0 . '3'] = ['1', 1];
            $cells[$c1 . '3'] = ['2', 1];
            $cells[$c0 . '4'] = ['3', 1];
            $cells[$c1 . '4'] = ['4', 1];
            $col += 2;
        }

        $row = 5;
        $idx = 1;
        foreach ($students as $stu) {
            $r2 = $row + 1;
            $merges[] = 'A' . $row . ':A' . $r2;
            $cells['A' . $row] = [(string) $idx, 0];
            $merges[] = 'B' . $row . ':B' . $r2;
            $cells['B' . $row] = [(string) ($stu['student_id'] ?? ''), 0];
            $merges[] = 'C' . $row . ':C' . $r2;
            $cells['C' . $row] = [trim((string) ($stu['student_ininame'] ?? '')), 0];
            $merges[] = 'D' . $row . ':D' . $r2;
            $cells['D' . $row] = [(string) ($stu['student_nic'] ?? ''), 0];

            $col = 5;
            foreach ($calendarDays as $_d) {
                $c0 = self::colLetter($col);
                $c1 = self::colLetter($col + 1);
                $cells[$c0 . $row] = ['', 0];
                $cells[$c1 . $row] = ['', 0];
                $cells[$c0 . $r2] = ['', 0];
                $cells[$c1 . $r2] = ['', 0];
                $col += 2;
            }
            $idx++;
            $row += 2;
        }

        $sheetXml = self::buildSheetXml($cells, $merges, $lastCol, $lastRow);
        $stylesXml = self::buildStylesXml();
        $workbookXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . self::xmlEsc($sheetName) . '" sheetId="1" r:id="rId1"/></sheets></workbook>';

        $workbookRels = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';

        $rootRels = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
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
            . '<Application>SLGTI SIS</Application></Properties>';
        $coreXml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" '
            . 'xmlns:dc="http://purl.org/dc/elements/1.1/"><dc:creator>SLGTI SIS</dc:creator></cp:coreProperties>';

        $zip = new ZipArchive();
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        if ($tmp === false || $zip->open($tmp, ZipArchive::OVERWRITE | ZipArchive::CREATE) !== true) {
            throw new RuntimeException('Could not create temporary Excel file.');
        }

        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', $rootRels);
        $zip->addFromString('docProps/app.xml', $appXml);
        $zip->addFromString('docProps/core.xml', $coreXml);
        $zip->addFromString('xl/workbook.xml', $workbookXml);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);
        $zip->addFromString('xl/styles.xml', $stylesXml);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->close();

        $filename = 'attendance_sheet_' . str_replace('-', '_', $month) . '_' . preg_replace('/[^A-Za-z0-9_-]+/', '_', $courseId);
        if ($group !== '') {
            $filename .= '_group' . preg_replace('/[^A-Za-z0-9_-]+/', '_', $group);
        }
        $filename .= '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
        header('Cache-Control: private, max-age=0');
        readfile($tmp);
        @unlink($tmp);
    }

    private static function colLetter($colIndex) {
        $letter = '';
        $n = (int) $colIndex;
        while ($n > 0) {
            $n--;
            $letter = chr(65 + ($n % 26)) . $letter;
            $n = intdiv($n, 26);
        }
        return $letter;
    }

    private static function xmlEsc($s) {
        return htmlspecialchars((string) $s, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    private static function buildStylesXml() {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="0"/>'
            . '<fonts count="3">'
            . '<font><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/></font>'
            . '<font><b/><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/></font>'
            . '<font><b/><sz val="14"/><color theme="1"/><name val="Calibri"/><family val="2"/></font>'
            . '</fonts>'
            . '<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
            . '<borders count="2">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border><left style="thin"><color auto="1"/></left><right style="thin"><color auto="1"/></right>'
            . '<top style="thin"><color auto="1"/></top><bottom style="thin"><color auto="1"/></bottom><diagonal/></border>'
            . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="3">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0"><alignment vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="1" xfId="0" applyFont="1"><alignment horizontal="center" vertical="center" wrapText="0"/></xf>'
            . '<xf numFmtId="0" fontId="2" fillId="0" borderId="1" xfId="0" applyFont="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    /**
     * @param array<string,array{0:string,1:int}> $cells ref => [value, styleIdx]
     * @param array<int,string> $merges e.g. A1:Z1
     */
    private static function buildSheetXml(array $cells, array $merges, $lastCol, $lastRow) {
        $widths = [];
        $widths[1] = 5;
        $widths[2] = 18;
        $widths[3] = 18;
        $widths[4] = 16;
        for ($c = 5; $c <= $lastCol; $c++) {
            $widths[$c] = 5;
        }

        $colsXml = '<cols>';
        foreach ($widths as $c => $w) {
            $colsXml .= '<col min="' . $c . '" max="' . $c . '" width="' . $w . '" customWidth="1"/>';
        }
        $colsXml .= '</cols>';

        $rowsXml = '';
        for ($r = 1; $r <= $lastRow; $r++) {
            $ht = ($r === 1) ? '22' : '18';
            $customH = ' customHeight="1"';
            $spans = '1:' . $lastCol;
            $rowCells = '';
            for ($c = 1; $c <= $lastCol; $c++) {
                $addr = self::colLetter($c) . $r;
                if (!isset($cells[$addr])) {
                    continue;
                }
                $entry = $cells[$addr];
                $val = $entry[0];
                $s = (int) $entry[1];
                $t = 'inlineStr';
                $inner = '<is><t>' . self::xmlEsc($val) . '</t></is>';
                $rowCells .= '<c r="' . $addr . '" s="' . $s . '" t="' . $t . '">' . $inner . '</c>';
            }
            if ($rowCells !== '') {
                $rowsXml .= '<row r="' . $r . '" spans="' . $spans . '" ht="' . $ht . '"' . $customH . '>' . $rowCells . '</row>';
            }
        }

        $mergeXml = '';
        if (!empty($merges)) {
            $mergeXml = '<mergeCells count="' . count($merges) . '">';
            foreach ($merges as $m) {
                $mergeXml .= '<mergeCell ref="' . self::xmlEsc($m) . '"/>';
            }
            $mergeXml .= '</mergeCells>';
        }

        $dim = 'A1:' . self::colLetter($lastCol) . $lastRow;

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<dimension ref="' . $dim . '"/>'
            . '<sheetFormatPr defaultRowHeight="15"/>'
            . '<sheetViews>'
            . '<sheetView tabSelected="1" workbookViewId="0">'
            . '<pane xSplit="4" ySplit="4" topLeftCell="E5" activePane="bottomRight" state="frozen"/>'
            . '</sheetView></sheetViews>'
            . $colsXml
            . '<sheetData>' . $rowsXml . '</sheetData>'
            . $mergeXml
            . '</worksheet>';
    }
}
