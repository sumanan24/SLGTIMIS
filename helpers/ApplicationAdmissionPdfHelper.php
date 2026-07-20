<?php
/**
 * PDF output for application admission / interview schedules.
 */

class ApplicationAdmissionPdfHelper {

    /**
     * @param array<string, mixed> $data
     */
    public static function renderTemplate(string $relativeView, array $data): string {
        $path = BASE_PATH . '/views/application_admission/pdf/' . $relativeView;
        if (!is_file($path)) {
            throw new RuntimeException('PDF template not found: ' . $relativeView);
        }
        extract($data, EXTR_SKIP);
        ob_start();
        include $path;
        return (string) ob_get_clean();
    }

    public static function wrapPdfDocument(string $bodyHtml): string {
        $css = '@page{margin:12mm;}body{font-family:Helvetica,Arial,DejaVu Sans,sans-serif;font-size:10pt;color:#0f172a;}'
            . 'table.grid{width:100%;border-collapse:collapse;margin:8px 0;}table.grid th,table.grid td{border:1px solid #cbd5e1;padding:5px 7px;text-align:left;}'
            . 'table.grid th{background:#f1f5f9;font-size:9px;text-transform:uppercase;}'
            . '.head-row{width:100%;border-collapse:collapse;margin-bottom:8px;}.head-row td{border:none;vertical-align:top;}'
            . '.inst{font-size:13px;font-weight:700;}.title{font-size:12px;font-weight:700;margin-top:4px;}.sub{font-size:10px;color:#475569;}'
            . '.logo-img{height:44px;width:auto;}.muted{color:#64748b;font-size:9px;}';
        return '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>' . $css . '</style></head><body>' . $bodyHtml . '</body></html>';
    }

    /**
     * @throws RuntimeException
     */
    public static function streamHtml(string $html, string $filename, $paper = 'A4', string $orientation = 'portrait'): void {
        require_once BASE_PATH . '/helpers/ExamPdfHelper.php';
        ExamPdfHelper::streamHtml($html, $filename, $paper, $orientation);
    }
}
