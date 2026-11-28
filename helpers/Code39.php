<?php
/**
 * Code 39 barcode as SVG. Scanners read the bars; no extra library.
 */
class Code39
{
    private const PATTERNS = [
        '0' => 'nnnwwnwnn', '1' => 'wnnwnnnnw', '2' => 'nnwwnnnnw', '3' => 'wnwwnnnnn',
        '4' => 'nnnwwnnnw', '5' => 'wnnwwnnnn', '6' => 'nnwwwnnnn', '7' => 'nnnwnnwnw',
        '8' => 'wnnwnnwnn', '9' => 'nnwwnnwnn', 'A' => 'wnnnnwnnw', 'B' => 'nnwnnwnnw',
        'C' => 'wnwnnwnnn', 'D' => 'nnnnwwnnw', 'E' => 'wnnnwwnnn', 'F' => 'nnwnwwnnn',
        'G' => 'nnnnnwwnw', 'H' => 'wnnnnwwnn', 'I' => 'nnwnnwwnn', 'J' => 'nnnnwwwnn',
        'K' => 'wnnnnnnww', 'L' => 'nnwnnnnww', 'M' => 'wnwnnnnwn', 'N' => 'nnnnwnnww',
        'O' => 'wnnnwnnwn', 'P' => 'nnwnwnnwn', 'Q' => 'nnnnnnwww', 'R' => 'wnnnnnwwn',
        'S' => 'nnwnnnwwn', 'T' => 'nnnnwnwwn', 'U' => 'wwnnnnnnw', 'V' => 'nwwnnnnnw',
        'W' => 'wwwnnnnnn', 'X' => 'nwnnwnnnw', 'Y' => 'wwnnwnnnn', 'Z' => 'nwwnwnnnn',
        '-' => 'nwnnnnwnw', '.' => 'wwnnnnwnn', ' ' => 'nwwnnnwnn', '*' => 'nwnnwnwnn',
        '$' => 'nwnwnwnnn', '/' => 'nwnwnnnwn', '+' => 'nwnnnwnwn', '%' => 'nnnwnwnwn',
    ];

    /**
     * Bar and space widths in narrow-module units. Quiet zones are included.
     *
     * @return list<array{0:int,1:bool}>
     */
    public static function modules(string $value): array
    {
        $text = strtoupper(trim($value));
        $text = preg_replace('/[^0-9A-Z\-\. \$\/\+%]/', '', $text) ?? '';
        if ($text === '') {
            return [];
        }
        $encoded = '*' . $text . '*';
        $mods = [[10, false]];
        $len = strlen($encoded);
        for ($i = 0; $i < $len; $i++) {
            $pat = self::PATTERNS[$encoded[$i]] ?? self::PATTERNS['-'];
            $isBar = true;
            for ($b = 0; $b < 9; $b++) {
                $mods[] = [$pat[$b] === 'w' ? 3 : 1, $isBar];
                $isBar = !$isBar;
            }
            $mods[] = [1, false];
        }
        $mods[] = [10, false];
        return $mods;
    }

    public static function svg(string $value, int $barHeight = 48): string
    {
        $mods = self::modules($value);
        if (!$mods) {
            return '';
        }
        $x = 0;
        $rects = '';
        foreach ($mods as $mod) {
            if ($mod[1]) {
                $rects .= '<rect x="' . $x . '" y="0" width="' . $mod[0] . '" height="' . $barHeight . '"/>';
            }
            $x += $mod[0];
        }
        $label = htmlspecialchars(strtoupper(trim($value)), ENT_QUOTES, 'UTF-8');
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $x . ' ' . $barHeight . '" preserveAspectRatio="none" role="img" aria-label="' . $label . '">' . $rects . '</svg>';
    }

    public static function draw($pdf, float $x, float $y, float $maxW, float $h, string $value): void
    {
        $mods = self::modules($value);
        if (!$mods || $maxW <= 0 || $h <= 0) {
            return;
        }
        $units = 0;
        foreach ($mods as $mod) {
            $units += $mod[0];
        }
        if ($units < 1) {
            return;
        }
        $scale = $maxW / $units;
        $pdf->SetFillColor(0, 0, 0);
        $cx = $x;
        foreach ($mods as $mod) {
            $pw = $mod[0] * $scale;
            if ($mod[1] && $pw > 0) {
                $pdf->Rect($cx, $y, $pw, $h, 'F');
            }
            $cx += $pw;
        }
    }
}
