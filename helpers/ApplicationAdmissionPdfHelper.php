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
        $css = '@page{margin:10mm;size:A4 portrait;}'
            . 'body{font-family:Helvetica,Arial,DejaVu Sans,sans-serif;font-size:11px;color:#0f172a;margin:0;padding:0;line-height:1.32;}'
            . '.postal-card-page{page-break-after:always;}'
            . '.postal-card-page:last-child{page-break-after:auto;}'
            . '.admission-sheet{width:100%;border-collapse:collapse;table-layout:fixed;border:2px solid #0f172a;}'
            . '.sheet-postal{vertical-align:top;padding:5mm 8mm 3mm;}'
            . '.sheet-fold{vertical-align:middle;text-align:center;font-size:9px;color:#64748b;background:#f8fafc;border-top:1px dashed #94a3b8;border-bottom:1px dashed #94a3b8;padding:3px 0;letter-spacing:0.06em;}'
            . '.sheet-body{vertical-align:top;padding:0;}'
            . '.body-layout{width:100%;border-collapse:collapse;table-layout:fixed;}'
            . '.body-content{vertical-align:top;padding:5mm 8mm 2mm;}'
            . '.body-fill{height:8mm;font-size:1px;line-height:1px;}'
            . '.body-foot{vertical-align:bottom;padding:0 8mm 5mm;}'
            . '.postal-zone-title{font-size:10px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;color:#0f2744;margin:0 0 4px;text-align:center;}'
            . '.mail-envelope{width:100%;border-collapse:collapse;table-layout:fixed;border:1.5px solid #0f172a;}'
            . '.mail-envelope td{vertical-align:top;padding:8px 10px;}'
            . '.mail-from{width:40%;border-right:1px solid #64748b;background:#f8fafc;}'
            . '.mail-to{width:60%;}'
            . '.mail-label{font-size:10px;text-transform:uppercase;letter-spacing:0.05em;font-weight:700;color:#334155;margin-bottom:4px;border-bottom:1px solid #cbd5e1;padding-bottom:2px;}'
            . '.mail-from-name{font-size:11px;font-weight:700;line-height:1.3;color:#0f172a;}'
            . '.mail-from-address{font-size:11px;line-height:1.35;margin-top:4px;color:#334155;}'
            . '.mail-from-phone{font-size:11px;line-height:1.3;margin-top:4px;color:#0f172a;font-weight:600;}'
            . '.mail-roll{font-size:11px;font-weight:700;line-height:1.3;margin:0 0 4px;color:#0f172a;font-family:DejaVu Sans Mono,Courier New,monospace;}'
            . '.mail-name{font-size:11px;font-weight:700;line-height:1.25;color:#0f172a;}'
            . '.mail-address{font-size:11px;line-height:1.35;margin-top:5px;white-space:pre-line;color:#0f172a;}'
            . '.mail-city{font-size:11px;line-height:1.3;margin-top:3px;color:#334155;}'
            . '.fold-hint{font-size:9px;color:#64748b;margin-top:4px;font-style:italic;text-align:center;line-height:1.3;}'
            . '.head-row{width:100%;border-collapse:collapse;table-layout:fixed;margin-bottom:0;}'
            . '.head-row td{border:none;vertical-align:top;padding:0;}'
            . '.head-left{width:70%;text-align:center;padding-right:8px;}'
            . '.head-right{width:30%;text-align:right;vertical-align:top;}'
            . '.logo-img{height:40px;width:auto;max-width:110px;display:block;margin:0 0 4px auto;}'
            . '.inst{font-size:13px;font-weight:700;color:#0b1220;letter-spacing:0.01em;}'
            . '.doc-title{font-size:12px;font-weight:700;margin-top:3px;color:#0b1220;text-transform:uppercase;letter-spacing:0.03em;}'
            . '.doc-sub{font-size:11px;font-weight:600;margin-top:2px;color:#334155;line-height:1.3;}'
            . '.ref-block{font-size:10px;color:#334155;line-height:1.35;text-align:right;}'
            . '.ref-line{margin-top:1px;}'
            . '.ref-label{font-weight:700;color:#0f172a;}'
            . '.header-rule{border-top:1px solid #0f172a;margin:5px 0 6px;}'
            . '.section-title{font-size:10px;font-weight:700;margin:0 0 3px;color:#0f2744;text-transform:uppercase;letter-spacing:0.04em;}'
            . '.section-title-exam-attendance{margin-top:10px;margin-bottom:3px;}'
            . 'table.info{width:100%;border-collapse:collapse;table-layout:fixed;margin:0 0 5px;}'
            . 'table.info .col-label{width:20%;}'
            . 'table.info .col-value{width:30%;}'
            . 'table.info th,table.info td{border:1px solid #cbd5e1;padding:4px 6px;vertical-align:middle;text-align:left;}'
            . 'table.info th{background:#f1f5f9;font-weight:700;text-transform:uppercase;font-size:10px;color:#0f2744;}'
            . 'table.info td{background:#fff;color:#0f172a;font-size:11px;}'
            . 'table.info td.mono{font-family:Courier,DejaVu Sans Mono,monospace;font-size:10px;word-break:break-all;}'
            . '.allow-block{border:1px solid #64748b;background:#f8fafc;padding:5px 7px;margin:0 0 5px;text-align:center;font-size:11px;font-style:italic;font-weight:600;line-height:1.32;color:#0f172a;}'
            . '.instr-cols{width:100%;border-collapse:collapse;margin:0 0 5px;table-layout:fixed;}'
            . '.instr-col{width:50%;vertical-align:top;padding:0 6px 0 0;}'
            . '.instr-col+.instr-col{padding:0 0 0 6px;border-left:1px solid #e2e8f0;}'
            . '.instr-list{margin:0 0 0 14px;padding:0;font-size:11px;line-height:1.32;color:#0f172a;}'
            . '.instr-list li{margin-bottom:1px;padding-left:2px;}'
            . '.instr-additional{border:1px solid #cbd5e1;background:#f8fafc;padding:4px 6px;font-size:11px;line-height:1.32;margin-bottom:5px;}'
            . 'table.grid{width:100%;border-collapse:collapse;margin:0 0 5px;table-layout:fixed;}'
            . 'table.grid th,table.grid td{border:1px solid #64748b;padding:4px 6px;vertical-align:middle;}'
            . 'table.grid th{background:#f1f5f9;font-weight:700;text-align:center;font-size:10px;text-transform:uppercase;color:#0f2744;}'
            . 'table.grid td{font-size:11px;}'
            . '.grid-attendance .col-title{width:48%;}'
            . '.grid-attendance .col-sig{width:26%;}'
            . '.grid-attendance .td-left{text-align:left;}'
            . '.grid-attendance .sig-cell{height:22px;vertical-align:bottom;}'
            . '.cert-block{border:1px solid #64748b;padding:5px 7px 5px;background:#fff;}'
            . '.certified-by-body{font-size:10px;line-height:1.4;font-weight:400;text-align:justify;margin:0 0 6px;color:#0f172a;}'
            . '.footer-sig-row{width:100%;border-collapse:collapse;table-layout:fixed;}'
            . '.footer-sig-row td{border:none;vertical-align:bottom;padding:0 5px 0 0;}'
            . '.sig-row-applicant{margin-top:25px;margin-bottom:4px;}'
            . '.sig-row-officer{margin-top:30px;margin-bottom:0;}'
            . '.footer-applicant{width:58%;}'
            . '.footer-date{width:22%;}'
            . '.footer-cert-sig{width:36%;}'
            . '.footer-cert-name{width:40%;}'
            . '.footer-cert-date{width:18%;}'
            . '.footer-sig-space{height:18px;}'
            . '.footer-sig-line{border-top:1px solid #0f172a;height:1px;font-size:1px;line-height:1px;}'
            . '.footer-sig-caption{font-size:9px;color:#334155;margin-top:2px;text-align:center;}'
            . '.gov-footer{font-size:11px;font-weight:700;text-align:center;border-top:1.5px solid #64748b;padding-top:5px;margin:0;line-height:1.35;color:#0f172a;}';

        return '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>' . $css . '</style></head><body>' . $bodyHtml . '</body></html>';
    }

    /**
     * Standard instructions (Sri Lanka government-style examination admission format).
     *
     * @return list<string>
     */
    public static function defaultExamInstructions(bool $isInterview = false): array {
        if ($isInterview) {
            return [
                'Report to the interview venue at least 30 minutes before the scheduled time.',
                'Bring this admission card and the original National Identity Card (NIC) for verification.',
                'Dress appropriately and follow the instructions of the interview panel.',
                'Mobile phones and unauthorised materials are not permitted inside the interview room.',
                'Candidates who commit malpractice or provide false information may be disqualified.',
            ];
        }

        return [
            'Report to the examination centre at least 30 minutes before the scheduled commencement time.',
            'Bring this admission card and the original National Identity Card (NIC) for verification.',
            'No candidate will be admitted to the examination hall after the examination has commenced.',
            'Mobile phones, smart watches, and unauthorised materials are strictly prohibited in the examination hall.',
            'Follow all instructions given by the supervisor, invigilator, and authorised officers.',
            'Any form of malpractice will result in immediate disqualification from the examination.',
        ];
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
