<?php
/**
 * Minimal .xlsx writer for simple header + data tables.
 * PHP 7.4+ safe — does not use PhpSpreadsheet (which needs PHP 8+).
 */
declare(strict_types=1);

require_once __DIR__ . '/StoredZipWriter.php';

class SimpleTableXlsx {
    /**
     * @param list<string> $headers
     * @param list<list<string|int|float|null>> $rows
     */
    public static function stream(string $filename, array $headers, array $rows, string $sheetName = 'Sheet1'): void {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $filename = str_replace(['"', "\r", "\n"], '', $filename);
        if ($filename === '') {
            $filename = 'export.xlsx';
        }
        if (!preg_match('/\.xlsx$/i', $filename)) {
            $filename .= '.xlsx';
        }

        $sheetXml = self::buildSheetXml($headers, $rows);
        $safeName = self::safeSheetName($sheetName);

        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '</Types>';

        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';

        $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '</Relationships>';

        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . self::xml($safeName) . '" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';

        $entries = [
            '[Content_Types].xml' => $contentTypes,
            '_rels/.rels' => $rels,
            'xl/workbook.xml' => $workbook,
            'xl/_rels/workbook.xml.rels' => $wbRels,
            'xl/worksheets/sheet1.xml' => $sheetXml,
        ];

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        $mem = fopen('php://temp', 'r+b');
        if ($mem === false) {
            throw new RuntimeException('SimpleTableXlsx: cannot open temp stream.');
        }
        try {
            StoredZipWriter::writeStream($mem, $entries);
            rewind($mem);
            fpassthru($mem);
        } finally {
            fclose($mem);
        }
    }

    /**
     * True when PhpSpreadsheet can be safely loaded (PHP 8+).
     */
    public static function phpSpreadsheetUsable(): bool {
        return PHP_VERSION_ID >= 80000;
    }

    /**
     * @param list<string> $headers
     * @param list<list<string|int|float|null>> $rows
     */
    private static function buildSheetXml(array $headers, array $rows): string {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetData>';

        $xml .= self::rowXml(1, $headers);
        $r = 2;
        foreach ($rows as $row) {
            $cells = [];
            foreach ($headers as $i => $_h) {
                $cells[] = isset($row[$i]) ? (string) $row[$i] : '';
            }
            $xml .= self::rowXml($r, $cells);
            $r++;
        }

        $xml .= '</sheetData></worksheet>';
        return $xml;
    }

    /**
     * @param list<string> $values
     */
    private static function rowXml(int $rowNum, array $values): string {
        $out = '<row r="' . $rowNum . '">';
        foreach ($values as $i => $val) {
            $col = self::colLetter($i + 1);
            $ref = $col . $rowNum;
            $text = self::xml((string) $val);
            $out .= '<c r="' . $ref . '" t="inlineStr"><is><t xml:space="preserve">' . $text . '</t></is></c>';
        }
        $out .= '</row>';
        return $out;
    }

    private static function colLetter(int $index): string {
        $letter = '';
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $index = intdiv($index - 1, 26);
        }
        return $letter !== '' ? $letter : 'A';
    }

    private static function safeSheetName(string $name): string {
        $name = preg_replace('/[\\\\\/\?\*\[\]:]/', '', $name) ?? 'Sheet1';
        $name = trim($name);
        if ($name === '') {
            $name = 'Sheet1';
        }
        if (function_exists('mb_substr')) {
            return mb_substr($name, 0, 31);
        }
        return substr($name, 0, 31);
    }

    private static function xml(string $s): string {
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
