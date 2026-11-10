<?php
/**
 * Shared Excel / PDF output for tabular reports.
 */
class ReportExport {
    /**
     * @param list<string> $headers
     * @param list<list<mixed>> $rows
     */
    public static function xlsx($filename, array $headers, array $rows, $sheet = 'Report') {
        require_once BASE_PATH . '/helpers/SimpleTableXlsx.php';
        $clean = [];
        foreach ($rows as $r) {
            $clean[] = array_map(static function ($v) {
                return $v === null ? '' : (is_scalar($v) ? $v : (string) json_encode($v));
            }, array_values($r));
        }
        SimpleTableXlsx::stream($filename . '.xlsx', $headers, $clean, mb_substr(preg_replace('/[\\\\\/\?\*\[\]:]/', ' ', $sheet), 0, 31));
    }

    /**
     * @param list<string> $headers
     * @param list<list<mixed>> $rows
     * @param list<string> $summary lines shown under the title
     */
    public static function pdf($filename, $title, array $summary, array $headers, array $rows, $landscape = true) {
        require_once BASE_PATH . '/helpers/ExamPdfHelper.php';
        ExamPdfHelper::loadDompdf();
        $e = static function ($v) {
            return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
        };
        $html = '<html><head><meta charset="utf-8"><style>
            body{font-family:Helvetica,Arial,sans-serif;font-size:9px;color:#1e293b}
            h1{font-size:15px;color:#0b2545;margin:0 0 2px}
            .sub{color:#475569;margin:0 0 8px}
            .brand{border-bottom:2px solid #0b2545;margin-bottom:8px;padding-bottom:4px;color:#0b2545;font-weight:bold;font-size:11px}
            table{width:100%;border-collapse:collapse}
            th{background:#0b2545;color:#fff;padding:4px 5px;text-align:left;font-size:8.5px}
            td{padding:3px 5px;border-bottom:1px solid #e2e8f0}
            tr:nth-child(even) td{background:#f8fafc}
            .foot{margin-top:8px;color:#64748b;font-size:8px}
        </style></head><body>';
        $html .= '<div class="brand">Sri Lanka German Training Institute &mdash; SLGTI MIS</div>';
        $html .= '<h1>' . $e($title) . '</h1>';
        foreach ($summary as $line) {
            $html .= '<p class="sub">' . $e($line) . '</p>';
        }
        $html .= '<table><thead><tr>';
        foreach ($headers as $h) {
            $html .= '<th>' . $e($h) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($rows as $r) {
            $html .= '<tr>';
            foreach (array_values($r) as $v) {
                $html .= '<td>' . $e($v) . '</td>';
            }
            $html .= '</tr>';
        }
        if (!$rows) {
            $html .= '<tr><td colspan="' . count($headers) . '">No records.</td></tr>';
        }
        $html .= '</tbody></table>';
        $html .= '<div class="foot">Generated ' . $e(date('d M Y H:i')) . ' (Asia/Colombo)</div></body></html>';

        @ini_set('memory_limit', '512M');
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'Helvetica');
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', $landscape ? 'landscape' : 'portrait');
        $dompdf->render();
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        $dompdf->stream($filename . '.pdf', ['Attachment' => true]);
        exit;
    }
}
