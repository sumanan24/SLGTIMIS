<?php
/**
 * PDF output for application admission / interview schedules.
 */

class ApplicationAdmissionPdfHelper {

    public const INSTITUTE_POST_FROM_NAME = 'Sri Lanka German Training Institute';

    public const INSTITUTE_POST_FROM_ADDRESS = 'Ariviyal Nagar, Kilinochchi 44000';

    public const INSTITUTE_POST_FROM_PHONE = '0703060138';

    /**
     * @return array{name: string, address: string, phone: string}
     */
    public static function institutePostFrom(): array {
        return [
            'name' => self::INSTITUTE_POST_FROM_NAME,
            'address' => self::INSTITUTE_POST_FROM_ADDRESS,
            'phone' => self::INSTITUTE_POST_FROM_PHONE,
        ];
    }

    public static function admissionCardReference(int $scheduleId, string $roll, int $entryId): string {
        $roll = trim($roll);
        if ($roll !== '') {
            return 'SLGTI/ADM/' . $scheduleId . '/' . $roll;
        }
        if ($entryId > 0) {
            return 'SLGTI/ADM/' . $scheduleId . '/' . str_pad((string) $entryId, 4, '0', STR_PAD_LEFT);
        }

        return 'SLGTI/ADM/' . $scheduleId;
    }

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

    public static function wrapPostalAdmissionCardsDocument(string $bodyHtml): string {
        $css = self::postalAdmissionCardStylesheet();
        return '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>' . $css . '</style></head><body>' . $bodyHtml . '</body></html>';
    }

    /**
     * CSS for A4 postal selection-exam admission cards (Dompdf-safe full-width tables).
     */
    private static function postalAdmissionCardStylesheet(): string {
        return ''
            . '@page{size:A4 portrait;margin:10px 8mm 8mm 8mm;}'
            . 'html,body{margin:0;padding:0;}'
            . 'body{font-family:DejaVu Sans,Helvetica,Arial,sans-serif;font-size:9pt;color:#111;line-height:1.3;}'
            . '.adm-page{width:100%;page-break-inside:avoid;}'
            . '.adm-page+.adm-page{page-break-before:always;}'
            . 'table.adm-sheet{width:100%;border:1.25pt solid #111;border-collapse:collapse;}'
            . 'td.adm-side{width:20px;padding:0;border:none;font-size:1px;line-height:1px;}'
            . 'td.adm-main{padding:10px 0 3mm 0;border:none;vertical-align:top;}'
            . '.adm-banner{text-align:center;font-size:9.5pt;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;margin:0 0 1.6mm 0;}'
            . 'table{border-collapse:collapse;}'
            . 'table.adm-postbox{width:100%;border:1pt solid #111;margin:0;}'
            . 'table.adm-postbox td{vertical-align:top;padding:2mm 2.5mm;}'
            . 'td.adm-from{background:#fafafa;border-right:1pt solid #111;}'
            . 'td.adm-to{background:#fff;}'
            . '.adm-label{font-size:7pt;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:#333;border-bottom:0.6pt solid #bbb;padding-bottom:0.5mm;margin:0 0 1mm 0;}'
            . '.adm-strong{font-size:9pt;font-weight:700;line-height:1.25;}'
            . '.adm-roll{font-size:8.5pt;font-weight:700;font-family:DejaVu Sans Mono,Courier New,monospace;margin:0 0 0.6mm 0;}'
            . '.adm-text{font-size:8pt;line-height:1.3;margin-top:0.4mm;color:#222;}'
            . '.adm-foldhint{text-align:center;font-size:7pt;font-style:italic;color:#444;margin:1.4mm 0 1mm 0;}'
            . '.adm-fold{text-align:center;font-size:7.5pt;color:#555;letter-spacing:0.08em;border-top:0.8pt dashed #777;border-bottom:0.8pt dashed #777;padding:1mm 0;margin:0 0 2mm 0;background:#f5f5f5;}'
            . 'table.adm-header{width:100%;margin:0 0 1.2mm 0;}'
            . 'table.adm-header td{border:none;padding:0;vertical-align:top;}'
            . 'td.adm-hmid{text-align:center;padding-right:3mm;}'
            . 'td.adm-hmeta{text-align:right;padding-right:2mm;}'
            . '.adm-logo{height:11mm;width:auto;display:block;margin:0 0 1mm auto;}'
            . '.adm-institute{font-size:11pt;font-weight:700;line-height:1.2;}'
            . '.adm-doctitle{font-size:9pt;font-weight:700;margin-top:0.6mm;text-transform:uppercase;letter-spacing:0.03em;}'
            . '.adm-examline{font-size:8pt;font-weight:700;margin-top:0.6mm;}'
            . '.adm-meta-l{font-size:6.5pt;font-weight:700;color:#444;}'
            . '.adm-meta-v{font-size:7pt;margin-top:0.2mm;font-weight:600;word-wrap:break-word;line-height:1.2;}'
            . '.adm-meta-gap{margin-top:1.2mm;}'
            . '.adm-sec{font-size:7.5pt;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;margin:1.6mm 0 0.8mm 0;}'
            . 'table.adm-grid{width:100%;margin:0;}'
            . 'table.adm-grid-split{margin-top:-0.7pt;}'
            . 'table.adm-grid th{background:#f2f2f2;font-size:6.5pt;font-weight:700;text-transform:uppercase;letter-spacing:0.02em;}'
            . 'table.adm-grid td{font-size:8.5pt;font-weight:600;}'
            . 'table.adm-grid th,table.adm-grid td{border:0.7pt solid #666;padding:1.3mm 2mm;vertical-align:middle;text-align:left;}'
            . 'table.adm-grid td.adm-mono{font-family:DejaVu Sans Mono,Courier,monospace;font-size:7.5pt;}'
            . '.adm-allow{width:100%;border:0.7pt solid #666;border-top:none;background:#f2f2f2;text-align:center;font-size:8pt;font-style:italic;font-weight:600;padding:1.5mm 2mm;margin:0 0 0.5mm 0;}'
            . 'table.adm-instr{width:100%;margin:0 0 0.4mm 0;}'
            . 'table.adm-instr td{vertical-align:top;padding:0 2mm 0 0;border:none;}'
            . 'table.adm-instr td+td{padding:0 0 0 2mm;}'
            . 'table.adm-instr ol{margin:0 0 0 3.5mm;padding:0;font-size:7.5pt;line-height:1.35;}'
            . 'table.adm-instr li{margin:0 0 0.55mm 0;}'
            . '.adm-extra{width:100%;border:0.6pt solid #aaa;background:#fafafa;padding:1mm 2mm;font-size:7.5pt;margin:0 0 0.8mm 0;}'
            . '.adm-cert{width:100%;border:0.7pt solid #666;padding:2mm 2.5mm;margin:0 0 0.5mm 0;}'
            . '.adm-cert p{margin:0 0 1.4mm 0;font-size:7.5pt;line-height:1.35;text-align:justify;}'
            . 'table.adm-sig{width:100%;}'
            . 'table.adm-sig td{border:none;vertical-align:bottom;padding:0 2mm 0 0;}'
            . 'table.adm-sig td:last-child{padding-right:0;}'
            . '.adm-sig2{margin-top:4mm;}'
            . '.adm-sgap{height:6.5mm;font-size:1px;line-height:1px;}'
            . '.adm-sline{border-top:0.8pt solid #111;height:1px;font-size:1px;line-height:1px;}'
            . '.adm-scap{font-size:6.5pt;color:#444;text-align:center;margin-top:0.4mm;}'
            . 'table.adm-att{width:100%;margin:0 0 1mm 0;}'
            . 'table.adm-att th,table.adm-att td{border:0.7pt solid #666;padding:1.3mm 2mm;font-size:8pt;vertical-align:middle;}'
            . 'table.adm-att th{background:#f2f2f2;font-size:6.5pt;font-weight:700;text-transform:uppercase;text-align:center;}'
            . 'table.adm-att td{font-weight:600;}'
            . '.adm-asig{height:9mm;}'
            . '.adm-note{font-size:7.5pt;font-weight:700;text-align:center;border-top:1pt solid #666;padding-top:1.3mm;margin-top:0.4mm;}'
            . '.iv-body{margin:0;padding:0;}'
            . '.iv-body-title{text-align:center;font-size:11pt;font-weight:700;text-transform:uppercase;letter-spacing:0.03em;margin:1.5mm 0 3mm 0;}'
            . '.iv-body-dear{font-weight:700;font-size:9.5pt;margin:0 0 2mm 0;}'
            . '.iv-body-p{font-size:8.5pt;line-height:1.4;margin:0 0 2.2mm 0;text-align:justify;}'
            . 'table.iv-body-details{width:100%;border-collapse:collapse;margin:1mm 0 2.5mm 0;}'
            . 'table.iv-body-details th,table.iv-body-details td{border:none;padding:1mm 0;vertical-align:top;font-size:9pt;text-align:left;}'
            . 'table.iv-body-details th{width:30%;font-weight:700;}'
            . '.iv-body-h{font-size:9pt;font-weight:700;margin:2.5mm 0 1.2mm 0;}'
            . '.iv-body-ul{margin:0 0 2mm 4mm;padding:0;font-size:8.5pt;line-height:1.4;}'
            . '.iv-body-ul li{margin:0 0 1mm 0;}'
            . '.iv-body-sign{margin-top:4mm;font-size:9pt;}'
            . '.iv-body-sign-img{height:8mm;width:auto;max-width:32mm;display:block;margin:0.5mm 0 0.3mm 0;}'
            . '.iv-body-sign-name{font-weight:700;margin:1.5mm 0 0 0;}'
            . '.iv-body-sign-role{font-weight:700;margin:0;}'
            . '.iv-body-sign-org{margin:0;font-size:8pt;}';
    }

    /**
     * Standard instructions (Sri Lanka government-style examination admission format).
     *
     * @return list<string>
     */
    public static function defaultExamInstructions(bool $isInterview = false): array {
        if ($isInterview) {
            return [
                'Arrive at least 15 minutes before the scheduled interview time.',
                'Bring original NIC, Birth Certificate, and relevant educational/NVQ certificates for verification.',
                'Male applicants: white shirt, black jeans/trousers and formal shoes.',
                'Female applicants: white blouse, black skirt/formal black jeans or trousers and formal shoes. Muslim female applicants may wear a black Abaya with a black or white Hijab.',
                'Maintain a neat, clean and professional appearance.',
                'Being called for an interview does not guarantee admission. Final selection follows applicable admission criteria.',
            ];
        }

        return [
            'Report to the examination centre at least 30 minutes before the scheduled time.',
            'Bring this admission card and the original NIC for verification.',
            'No candidate will be admitted after the examination has commenced.',
            'Mobile phones, smart watches, and unauthorised materials are prohibited.',
            'Follow all instructions of the supervisor and invigilator.',
            'Malpractice will result in immediate disqualification.',
        ];
    }

    public static function wrapPdfDocument(string $bodyHtml): string {
        $css = '@page{margin:14mm;}'
            . 'body{font-family:DejaVu Sans,Helvetica,Arial,sans-serif;font-size:10pt;color:#0f172a;}'
            . 'table.grid{width:100%;border-collapse:collapse;margin:8px 0;}table.grid th,table.grid td{border:1px solid #cbd5e1;padding:5px 7px;text-align:left;}'
            . 'table.grid th{background:#f1f5f9;font-size:9px;text-transform:uppercase;}'
            . 'table.grid td.sig{height:22px;}'
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

    /**
     * Render many postal admission cards without exhausting PHP memory (chunk + merge).
     *
     * @param list<string> $bodyHtmlParts One card HTML fragment per page
     * @throws RuntimeException
     */
    public static function streamPostalAdmissionCardsMerged(array $bodyHtmlParts, string $filename, int $chunkSize = 15): void {
        if ($bodyHtmlParts === []) {
            throw new RuntimeException('No admission cards to render.');
        }
        require_once BASE_PATH . '/helpers/ExamPdfHelper.php';
        if (!ExamPdfHelper::dompdfAvailable()) {
            throw new RuntimeException('PDF engine not installed. Run: composer install.');
        }

        @ini_set('memory_limit', '512M');
        @set_time_limit(600);

        if (count($bodyHtmlParts) === 1) {
            self::streamHtml(self::wrapPostalAdmissionCardsDocument($bodyHtmlParts[0]), $filename);

            return;
        }

        ExamPdfHelper::loadDompdf();
        if (class_exists('\setasign\Fpdi\Fpdi')) {
            self::streamPostalAdmissionCardsMergedWithFpdi($bodyHtmlParts, $filename, $chunkSize);

            return;
        }

        self::streamPostalAdmissionCardsZip($bodyHtmlParts, $filename, $chunkSize);
    }

    /**
     * @param list<string> $bodyHtmlParts
     */
    private static function streamPostalAdmissionCardsMergedWithFpdi(array $bodyHtmlParts, string $filename, int $chunkSize): void {
        $tempFiles = [];
        try {
            foreach (array_chunk($bodyHtmlParts, max(1, $chunkSize)) as $chunk) {
                $html = self::wrapPostalAdmissionCardsDocument(implode('', $chunk));
                $bytes = ExamPdfHelper::renderPdfBytes($html, 'A4', 'portrait');
                unset($html);
                $tmp = tempnam(sys_get_temp_dir(), 'slgti_adm_');
                if ($tmp === false) {
                    throw new RuntimeException('Could not create temporary PDF file.');
                }
                file_put_contents($tmp, $bytes);
                unset($bytes);
                $tempFiles[] = $tmp;
            }
            self::streamMergedPdfFiles($tempFiles, $filename);
        } finally {
            foreach ($tempFiles as $path) {
                if (is_file($path)) {
                    @unlink($path);
                }
            }
        }
    }

    /**
     * Fallback when FPDI is unavailable: one PDF per chunk inside a ZIP download.
     *
     * @param list<string> $bodyHtmlParts
     */
    private static function streamPostalAdmissionCardsZip(array $bodyHtmlParts, string $filename, int $chunkSize): void {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException(
                'Bulk admission cards need either the FPDI library (run composer install) or PHP ZipArchive.'
            );
        }
        $zip = new ZipArchive();
        $tmpZip = tempnam(sys_get_temp_dir(), 'slgti_adm_zip_');
        if ($tmpZip === false) {
            throw new RuntimeException('Could not create temporary ZIP file.');
        }
        @unlink($tmpZip);
        if ($zip->open($tmpZip, ZipArchive::CREATE) !== true) {
            throw new RuntimeException('Could not open ZIP archive for admission cards.');
        }
        $partNo = 0;
        foreach (array_chunk($bodyHtmlParts, max(1, $chunkSize)) as $chunk) {
            $partNo++;
            $html = self::wrapPostalAdmissionCardsDocument(implode('', $chunk));
            $bytes = ExamPdfHelper::renderPdfBytes($html, 'A4', 'portrait');
            unset($html);
            $zip->addFromString(
                sprintf('part-%03d.pdf', $partNo),
                $bytes
            );
            unset($bytes);
        }
        $zip->close();

        $base = preg_replace('/\.pdf$/i', '', preg_replace('/[^a-zA-Z0-9._-]+/', '_', $filename) ?: 'admission-cards');
        $zipName = $base . '.zip';
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipName . '"');
        header('Content-Length: ' . (string) filesize($tmpZip));
        readfile($tmpZip);
        @unlink($tmpZip);
        exit;
    }

    /**
     * @param list<string> $pdfPaths
     * @throws RuntimeException
     */
    private static function streamMergedPdfFiles(array $pdfPaths, string $filename): void {
        ExamPdfHelper::loadDompdf();
        $pdf = new \setasign\Fpdi\Fpdi();
        foreach ($pdfPaths as $path) {
            $pageCount = $pdf->setSourceFile($path);
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);
                $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);
            }
        }
        $safe = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $filename) ?: 'document.pdf';
        if (substr(strtolower($safe), -4) !== '.pdf') {
            $safe .= '.pdf';
        }
        $pdf->Output('D', $safe);
        exit;
    }
}
