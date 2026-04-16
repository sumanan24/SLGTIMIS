<?php
/**
 * Minimal PDF 1.4 for staff application summary — no Composer.
 * Two-column layout (Courier field / Helvetica value), header band, paged footers.
 * Text is transliterated toward Windows-1252 for safe PDF literals.
 */
class StudentApplicationSummaryPdf {

    private const PAGE_W = 612;
    private const PAGE_H = 792;
    private const MARGIN_L = 54;
    private const MARGIN_R = 558;
    private const COL_VALUE_X = 200;
    private const FOOTER_Y = 34;
    private const BODY_FLOOR = 52;

    private const TITLE_SIZE = 13;
    private const SUBTITLE_SIZE = 8;
    private const HEADER_BODY_SIZE = 9;
    private const FIELD_FONT_SIZE = 8;
    private const VALUE_FONT_SIZE = 9;
    private const LINE_LEADING = 10.2;
    private const FIELD_WRAP = 30;
    private const VALUE_WRAP = 66;

    /** Data lines budget (after column headers) — approximate. */
    private const FIRST_PAGE_DATA_LINES = 54;
    private const NEXT_PAGE_DATA_LINES = 58;

    /**
     * @param list<array{0: string, 1: string}> $rows Field label, value
     */
    public static function build(string $title, array $rows): string {
        $pageRowSets = self::paginateRows($rows);
        $pageCount = max(1, count($pageRowSets));

        $objects = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';

        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $objects[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';
        $objects[5] = '<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>';
        $objects[6] = '<< /Font << /F1 3 0 R /F2 4 0 R /F3 5 0 R >> >>';

        $nextId = 7;
        $kidRefs = [];
        foreach ($pageRowSets as $pi => $pageRows) {
            $pageObjId = $nextId++;
            $contentObjId = $nextId++;
            $kidRefs[] = "{$pageObjId} 0 R";
            $stream = self::buildSinglePageStream(
                $title,
                $pageRows,
                (int) $pi,
                $pageCount,
                (int) $pi === 0
            );
            $objects[$contentObjId] = '<< /Length ' . strlen($stream) . " >>\nstream\n{$stream}\nendstream";
            $objects[$pageObjId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . self::PAGE_W . ' ' . self::PAGE_H . '] '
                . "/Resources 6 0 R /Contents {$contentObjId} 0 R >>";
        }

        $kidsStr = implode(' ', $kidRefs);
        $objects[2] = "<< /Type /Pages /Kids [{$kidsStr}] /Count {$pageCount} >>";

        ksort($objects, SORT_NUMERIC);

        $pdf = "%PDF-1.4\n";
        $offsets = [0 => 0];
        $pos = strlen($pdf);
        foreach ($objects as $num => $body) {
            $offsets[$num] = $pos;
            $chunk = "{$num} 0 obj\n{$body}\nendobj\n";
            $pdf .= $chunk;
            $pos += strlen($chunk);
        }

        $xrefPos = $pos;
        $count = count($objects) + 1;
        $pdf .= "xref\n0 {$count}\n";
        $pdf .= "0000000000 65535 f \n";
        foreach (array_keys($objects) as $i) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size {$count} /Root 1 0 R >>\nstartxref\n{$xrefPos}\n%%EOF";

        return $pdf;
    }

    /**
     * @param list<array{0: string, 1: string}> $rows
     * @return list<list<array{0: string, 1: string}>>
     */
    private static function paginateRows(array $rows): array {
        if ($rows === []) {
            return [[]];
        }
        $pages = [];
        $current = [];
        $budget = self::FIRST_PAGE_DATA_LINES;

        foreach ($rows as $row) {
            $need = self::rowLineCount($row[0], $row[1]);
            if ($need > $budget && $current !== []) {
                $pages[] = $current;
                $current = [];
                $budget = self::NEXT_PAGE_DATA_LINES;
            }
            $current[] = $row;
            $budget -= $need;
            if ($budget < 0 && $current !== []) {
                $pages[] = $current;
                $current = [];
                $budget = self::NEXT_PAGE_DATA_LINES;
            }
        }
        if ($current !== []) {
            $pages[] = $current;
        }
        return $pages;
    }

    private static function rowLineCount(string $field, string $value): int {
        $fieldLines = self::wrapLine(self::toPdfText($field) . ':', self::FIELD_WRAP);
        $valueLines = self::wrapLine(self::toPdfText($value), self::VALUE_WRAP);

        return max(count($fieldLines), count($valueLines));
    }

    /**
     * @param list<array{0: string, 1: string}> $pageRows
     */
    private static function buildSinglePageStream(
        string $title,
        array $pageRows,
        int $pageIndex,
        int $pageTotal,
        bool $isFirstPage
    ): string {
        $lines = [];

        if ($isFirstPage) {
            $lines[] = 'q';
            $lines[] = '0.93 0.95 0.98 rg';
            $lines[] = '54 696 504 46 re';
            $lines[] = 'f';
            $lines[] = 'Q';
            $lines[] = 'BT';
            $lines[] = '/F2 ' . self::TITLE_SIZE . ' Tf';
            $lines[] = '0.1 0.14 0.2 rg';
            $lines[] = sprintf('1 0 0 1 %d 730 Tm', self::MARGIN_L + 4);
            $lines[] = self::literal(self::toPdfText($title)) . ' Tj';
            $lines[] = '/F1 ' . self::SUBTITLE_SIZE . ' Tf';
            $lines[] = '0.36 0.38 0.42 rg';
            $lines[] = sprintf('1 0 0 1 %d 714 Tm', self::MARGIN_L + 4);
            $lines[] = self::literal(self::toPdfText('Student application summary (data only — no uploaded files)')) . ' Tj';
            $lines[] = 'ET';
            $lines[] = '0.72 0.76 0.84 RG';
            $lines[] = '0.6 w';
            $lines[] = sprintf('%d 692 m %d 692 l S', self::MARGIN_L, self::MARGIN_R);
            $y = 674.0;
        } else {
            $lines[] = 'BT';
            $lines[] = '/F2 11 Tf';
            $lines[] = '0.1 0.14 0.2 rg';
            $lines[] = sprintf('1 0 0 1 %d 746 Tm', self::MARGIN_L + 4);
            $lines[] = self::literal(self::toPdfText($title . ' (continued)')) . ' Tj';
            $lines[] = 'ET';
            $lines[] = '0.72 0.76 0.84 RG';
            $lines[] = '0.6 w';
            $lines[] = sprintf('%d 732 m %d 732 l S', self::MARGIN_L, self::MARGIN_R);
            $y = 714.0;
        }

        $lines[] = 'BT';
        $lines[] = '/F2 ' . self::HEADER_BODY_SIZE . ' Tf';
        $lines[] = '0.22 0.26 0.32 rg';
        $lines[] = sprintf('1 0 0 1 %d %.2f Tm', self::MARGIN_L, $y);
        $lines[] = self::literal(self::toPdfText('Field')) . ' Tj';
        $lines[] = sprintf('1 0 0 1 %d %.2f Tm', self::COL_VALUE_X, $y);
        $lines[] = self::literal(self::toPdfText('Value')) . ' Tj';
        $y -= 13.0;

        foreach ($pageRows as [$field, $value]) {
            $fieldLabel = self::toPdfText($field) . ':';
            $valText = self::toPdfText($value);
            $fieldLines = self::wrapLine($fieldLabel, self::FIELD_WRAP);
            $valueLines = self::wrapLine($valText, self::VALUE_WRAP);
            $n = max(count($fieldLines), count($valueLines));
            for ($i = 0; $i < $n; $i++) {
                if ($y < self::BODY_FLOOR + 8) {
                    break 2;
                }
                if (isset($fieldLines[$i])) {
                    $lines[] = '/F3 ' . self::FIELD_FONT_SIZE . ' Tf';
                    $lines[] = '0.18 0.22 0.28 rg';
                    $lines[] = sprintf('1 0 0 1 %d %.2f Tm', self::MARGIN_L, $y);
                    $lines[] = self::literal($fieldLines[$i]) . ' Tj';
                }
                if (isset($valueLines[$i])) {
                    $lines[] = '/F1 ' . self::VALUE_FONT_SIZE . ' Tf';
                    $lines[] = '0 0 0 rg';
                    $lines[] = sprintf('1 0 0 1 %d %.2f Tm', self::COL_VALUE_X, $y);
                    $lines[] = self::literal($valueLines[$i]) . ' Tj';
                }
                $y -= self::LINE_LEADING;
            }
        }

        $lines[] = 'ET';

        $lines[] = 'BT';
        $lines[] = '/F1 7.5 Tf';
        $lines[] = '0.5 0.52 0.55 rg';
        $lines[] = sprintf('1 0 0 1 %d %d Tm', self::MARGIN_L, self::FOOTER_Y);
        $footer = 'SLGTIMIS — Online student applications — Page ' . ($pageIndex + 1) . ' of ' . $pageTotal;
        $lines[] = self::literal(self::toPdfText($footer)) . ' Tj';
        $lines[] = 'ET';

        return implode("\n", $lines);
    }

    private static function wrapLine(string $line, int $maxLen): array {
        if (strlen($line) <= $maxLen) {
            return [$line];
        }
        $parts = [];
        while ($line !== '') {
            if (strlen($line) <= $maxLen) {
                $parts[] = $line;
                break;
            }
            $slice = substr($line, 0, $maxLen);
            $sp = strrpos($slice, ' ');
            if ($sp !== false && $sp > 16) {
                $parts[] = substr($line, 0, $sp);
                $line = ltrim(substr($line, $sp));
            } else {
                $parts[] = $slice;
                $line = substr($line, $maxLen);
            }
        }

        return $parts;
    }

    private static function toPdfText(string $s): string {
        $s = str_replace(["\r\n", "\r"], "\n", $s);
        $s = preg_replace("/\n+/", ' | ', $s) ?? $s;
        $t = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $s);
        if ($t === false || $t === '') {
            $t = preg_replace('/[^\x20-\x7E]/', '?', $s) ?? $s;
        }

        return $t;
    }

    private static function literal(string $s): string {
        $s = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $s);

        return '(' . $s . ')';
    }
}
