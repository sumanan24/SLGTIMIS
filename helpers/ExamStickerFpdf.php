<?php
/**
 * 2-up exam stickers — print-ready inch layout.
 * Strip: 4.0 in wide × 1.0 in high. Two 2.0 in labels fill the full width.
 *
 * Load vendor/autoload.php (FPDF) before this file.
 */
class ExamStickerFpdf extends FPDF {
    public const STRIP_W_IN = 4.0;
    public const STRIP_H_IN = 1.0;
    public const SIDE_MARGIN_IN = 0.0;
    public const GAP_IN = 0.0;
    public const LABEL_W_IN = 2.0;
    public const LABEL_H_IN = 1.0;
    public const MARGIN_X_IN = 0.18;
    public const MARGIN_Y_IN = 0.05;
    public const MARGIN_IN = 0.18;
    public const CORNER_IN = 0.06;
    /** 48px request — PDF font size is 48pt so the printed file measures 48. */
    public const FONT_SIZE_PT = 48.0;

    public function __construct($orientation = 'L', $unit = 'in', $size = null) {
        if ($size === null) {
            $size = [self::STRIP_W_IN, self::STRIP_H_IN];
        }
        parent::__construct($orientation, $unit, $size);
        $this->PDFVersion = '1.6';
        $this->SetDisplayMode('real');
        $this->SetAutoPageBreak(false, 0);
        $this->SetMargins(0, 0, 0);
    }

    public function AddPage($orientation = '', $size = '', $rotation = 0) {
        if ($size === '' || $size === null) {
            $size = [self::STRIP_W_IN, self::STRIP_H_IN];
        }
        parent::AddPage($orientation === '' ? 'L' : $orientation, $size, $rotation);
        $this->PageInfo[$this->page]['size'] = [
            self::STRIP_W_IN * $this->k,
            self::STRIP_H_IN * $this->k,
        ];
        $this->w = self::STRIP_W_IN;
        $this->h = self::STRIP_H_IN;
        $this->wPt = self::STRIP_W_IN * $this->k;
        $this->hPt = self::STRIP_H_IN * $this->k;
    }

    protected function _putcatalog() {
        parent::_putcatalog();
        $this->_put('/ViewerPreferences <</PrintScaling /None>>');
    }

    public function writeStudentNumber(float $x, float $y, float $w, float $h, string $text, float $sizePt): void {
        $this->SetFont('Helvetica', 'B', $sizePt);
        $usable = max(0.05, $w - (2.0 * self::MARGIN_X_IN));
        $natural = $this->GetStringWidth($text);
        $scale = ($natural > $usable && $natural > 0.0) ? ($usable / $natural) : 1.0;
        $drawW = $natural * $scale;
        $textX = $x + (($w - $drawW) / 2.0);
        $baseline = $y + ($h / 2.0) + (0.35 * $this->FontSize);

        $s = sprintf(
            'q BT %.2F Tz %.2F %.2F Td (%s) Tj ET Q',
            $scale * 100.0,
            $textX * $this->k,
            ($this->h - $baseline) * $this->k,
            $this->_escape($text)
        );
        if ($this->ColorFlag) {
            $s = 'q ' . $this->TextColor . ' ' . $s . ' Q';
        }
        $this->_out($s);
    }

    /**
     * @param string $style S = stroke, F = fill, DF/FD = both
     */
    public function roundedLabel(float $x, float $y, float $w, float $h, float $r, string $style = 'S'): void {
        $r = min($r, $w / 2.0, $h / 2.0);
        if ($r <= 0.0) {
            $this->Rect($x, $y, $w, $h, $style);
            return;
        }
        $k = $this->k;
        $hp = $this->h;
        $op = 'S';
        if ($style === 'F') {
            $op = 'f';
        } elseif ($style === 'FD' || $style === 'DF') {
            $op = 'B';
        }
        $MyArc = 4.0 / 3.0 * (sqrt(2.0) - 1.0);
        $this->_out(sprintf('%.2F %.2F m', ($x + $r) * $k, ($hp - $y) * $k));
        $xc = $x + $w - $r;
        $yc = $y + $r;
        $this->_out(sprintf('%.2F %.2F l', $xc * $k, ($hp - $y) * $k));
        $this->_arc($xc + $r * $MyArc, $yc - $r, $xc + $r, $yc - $r * $MyArc, $xc + $r, $yc);
        $xc = $x + $w - $r;
        $yc = $y + $h - $r;
        $this->_out(sprintf('%.2F %.2F l', ($x + $w) * $k, ($hp - $yc) * $k));
        $this->_arc($xc + $r, $yc + $r * $MyArc, $xc + $r * $MyArc, $yc + $r, $xc, $yc + $r);
        $xc = $x + $r;
        $yc = $y + $h - $r;
        $this->_out(sprintf('%.2F %.2F l', $xc * $k, ($hp - ($y + $h)) * $k));
        $this->_arc($xc - $r * $MyArc, $yc + $r, $xc - $r, $yc + $r * $MyArc, $xc - $r, $yc);
        $xc = $x + $r;
        $yc = $y + $r;
        $this->_out(sprintf('%.2F %.2F l', $x * $k, ($hp - $yc) * $k));
        $this->_arc($xc - $r, $yc - $r * $MyArc, $xc - $r * $MyArc, $yc - $r, $xc, $yc - $r);
        $this->_out($op);
    }

    private function _arc(float $x1, float $y1, float $x2, float $y2, float $x3, float $y3): void {
        $h = $this->h;
        $this->_out(sprintf(
            '%.2F %.2F %.2F %.2F %.2F %.2F c ',
            $x1 * $this->k,
            ($h - $y1) * $this->k,
            $x2 * $this->k,
            ($h - $y2) * $this->k,
            $x3 * $this->k,
            ($h - $y3) * $this->k
        ));
    }
}
