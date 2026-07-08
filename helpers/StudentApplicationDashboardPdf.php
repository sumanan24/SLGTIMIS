<?php
/**
 * PDF table export for online student applications dashboard breakdown cards.
 */

class StudentApplicationDashboardPdf {

    /**
     * @param list<array{label: string, count: int}> $rows
     */
    public static function buildHtml(
        string $reportTitle,
        string $filterSummary,
        array $rows,
        string $categoryLabel = 'Category'
    ): string {
        $esc = static function (string $s): string {
            return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        };

        $total = 0;
        foreach ($rows as $row) {
            $total += (int) ($row['count'] ?? 0);
        }

        $bodyRows = '';
        foreach ($rows as $row) {
            $lbl = (string) ($row['label'] ?? '');
            $cnt = (int) ($row['count'] ?? 0);
            $pct = $total > 0 ? round(100 * $cnt / $total, 1) : 0.0;
            $bodyRows .= '<tr>'
                . '<td>' . $esc($lbl) . '</td>'
                . '<td class="num">' . $cnt . '</td>'
                . '<td class="num">' . $esc(number_format($pct, 1)) . '%</td>'
                . '</tr>';
        }

        if ($bodyRows === '') {
            $bodyRows = '<tr><td colspan="3" class="empty">No data for the current filters.</td></tr>';
        } else {
            $bodyRows .= '<tr class="total-row">'
                . '<td><strong>Total</strong></td>'
                . '<td class="num"><strong>' . $total . '</strong></td>'
                . '<td class="num"><strong>100%</strong></td>'
                . '</tr>';
        }

        $generated = date('Y-m-d H:i');

        return '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>'
            . 'body{font-family:DejaVu Sans,sans-serif;font-size:10pt;color:#222;margin:28px;}'
            . 'h1{font-size:14pt;margin:0 0 4px;color:#1F4E79;}'
            . 'h2{font-size:11pt;margin:0 0 10px;font-weight:normal;color:#444;}'
            . '.meta{font-size:8.5pt;color:#666;margin-bottom:14px;}'
            . 'table{width:100%;border-collapse:collapse;}'
            . 'th,td{border:1px solid #bbb;padding:6px 8px;text-align:left;vertical-align:top;}'
            . 'th{background:#1F4E79;color:#fff;font-weight:bold;}'
            . 'td.num{text-align:right;white-space:nowrap;}'
            . 'tr.total-row td{background:#f0f4f8;}'
            . 'td.empty{text-align:center;color:#666;font-style:italic;}'
            . '</style></head><body>'
            . '<h1>SLGTI — Online Student Applications</h1>'
            . '<h2>' . $esc($reportTitle) . '</h2>'
            . '<div class="meta">' . $esc($filterSummary) . '<br>Generated: ' . $esc($generated) . '</div>'
            . '<table><thead><tr>'
            . '<th>' . $esc($categoryLabel) . '</th>'
            . '<th class="num">Count</th>'
            . '<th class="num">Share</th>'
            . '</tr></thead><tbody>'
            . $bodyRows
            . '</tbody></table></body></html>';
    }
}
