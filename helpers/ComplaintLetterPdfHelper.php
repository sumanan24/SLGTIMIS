<?php
/**
 * PDF rendering for student complaint letters.
 */

require_once BASE_PATH . '/helpers/ExamPdfHelper.php';
require_once BASE_PATH . '/helpers/ApplicationAdmissionPdfHelper.php';

class ComplaintLetterPdfHelper {

    public static function instituteName(): string {
        return 'Sri Lanka German Training Institute';
    }

    public static function instituteAddress(): string {
        return self::institutePostFrom()['address'];
    }

    /**
     * @return array{name: string, address: string, phone: string}
     */
    public static function institutePostFrom(): array {
        if (class_exists('ApplicationAdmissionPdfHelper', false)) {
            return ApplicationAdmissionPdfHelper::institutePostFrom();
        }

        return [
            'name' => 'Sri Lanka German Training Institute',
            'address' => 'Ariviyal Nagar, Kilinochchi 44000',
            'phone' => '0703060138',
        ];
    }

    public static function postalHeaderStylesheet(): string {
        return self::complaintLetterStylesheet();
    }

    /** Full stylesheet for postal header + official letter body (PDF + screen). */
    public static function complaintLetterStylesheet(): string {
        return ''
            . '.cl-a4-inner{width:100%;box-sizing:border-box;}'
            . 'table.cl-postbox{width:100%;border:1pt solid #111;margin:0 0 1.5mm 0;border-collapse:collapse;}'
            . 'table.cl-postbox td{vertical-align:top;padding:2mm 2.5mm;}'
            . 'td.cl-from{background:#fafafa;border-right:1pt solid #111;}'
            . 'td.cl-to{background:#fff;}'
            . '.cl-post-label{font-size:6.5pt;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:#333;border-bottom:0.6pt solid #bbb;padding-bottom:0.4mm;margin:0 0 0.8mm 0;}'
            . '.cl-post-strong{font-size:8.5pt;font-weight:700;line-height:1.2;text-transform:uppercase;}'
            . '.cl-post-id{font-size:8pt;font-weight:700;font-family:DejaVu Sans Mono,Courier New,monospace;margin:0 0 0.5mm 0;}'
            . '.cl-post-text{font-size:7.5pt;line-height:1.25;margin-top:0.3mm;color:#222;}'
            . '.cl-foldhint{text-align:center;font-size:6.5pt;font-style:italic;color:#444;margin:1mm 0 0.8mm 0;}'
            . '.cl-fold{text-align:center;font-size:7pt;color:#555;letter-spacing:0.08em;border-top:0.8pt dashed #777;border-bottom:0.8pt dashed #777;padding:0.8mm 0;margin:0 0 2.5mm 0;background:#f5f5f5;}'
            . '.cl-letterhead{text-align:center;margin:0 0 2mm 0;}'
            . '.cl-letterhead-logo{height:9mm;width:auto;display:block;margin:0 auto 1mm auto;}'
            . '.cl-letterhead-name{font-size:10pt;font-weight:700;letter-spacing:0.03em;text-transform:uppercase;line-height:1.2;color:#111;}'
            . '.cl-letterhead-addr{font-size:7.5pt;color:#333;margin-top:0.5mm;line-height:1.3;}'
            . '.cl-letterhead-rule{border:none;border-top:1pt solid #111;margin:2mm 0 2.5mm 0;}'
            . 'table.cl-meta{width:100%;margin:0 0 1.5mm 0;border-collapse:collapse;}'
            . 'table.cl-meta td{border:none;padding:0;vertical-align:top;font-size:9pt;line-height:1.3;}'
            . '.cl-meta-ref{font-weight:600;text-align:left;width:55%;}'
            . '.cl-meta-date{font-weight:600;text-align:right;width:45%;}'
            . '.cl-subject{font-size:9.5pt;font-weight:700;margin:0 0 3mm 0;padding-bottom:1mm;border-bottom:0.8pt solid #111;line-height:1.35;text-align:left;}'
            . '.cl-salutation{margin:0 0 3mm 0;font-size:9.5pt;text-align:left;}'
            . 'table.cl-particulars{width:100%;border-collapse:collapse;margin:0 0 3mm 0;border:0.7pt solid #666;table-layout:fixed;}'
            . 'table.cl-particulars th{width:18%;background:#f2f2f2;font-size:7pt;font-weight:700;text-align:left;padding:1.2mm 2mm;border:0.6pt solid #666;text-transform:uppercase;letter-spacing:0.03em;color:#222;vertical-align:middle;}'
            . 'table.cl-particulars td{width:32%;font-size:8.5pt;padding:1.2mm 2mm;border:0.6pt solid #666;font-weight:600;vertical-align:middle;color:#111;text-align:left;}'
            . 'table.cl-particulars td.cl-mono{font-family:DejaVu Sans Mono,Courier New,monospace;font-size:8pt;}'
            . '.cl-body{text-align:justify;text-justify:inter-word;white-space:pre-wrap;font-size:9.5pt;line-height:1.5;margin:0 0 2.5mm 0;word-wrap:break-word;}'
            . '.cl-body-action{margin-bottom:0;}'
            . '.cl-action-title{font-size:8.5pt;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;margin:3mm 0 1.5mm 0;color:#222;text-align:left;}'
            . '.cl-closing{margin-top:7mm;text-align:left;}'
            . '.cl-closing-text{margin:0 0 8mm 0;font-size:9.5pt;}'
            . '.cl-sig-line{margin:0;width:52mm;font-size:9.5pt;letter-spacing:0.06em;color:#111;}';
    }

    /** @page + root rules for PDF output (one A4 sheet per letter). */
    public static function pdfPageStylesheet(): string {
        return ''
            . '@page{size:A4 portrait;margin:10mm 12mm;}'
            . 'html,body{margin:0;padding:0;}'
            . 'body{font-family:DejaVu Serif,Times New Roman,serif;font-size:10pt;color:#111;line-height:1.45;}'
            . '.letter-page{width:100%;page-break-after:always;page-break-inside:avoid;}'
            . '.letter-page:last-child{page-break-after:auto;}';
    }

    public static function logoDataUri(): string {
        $paths = [
            BASE_PATH . '/assets/img/logo.png',
            BASE_PATH . '/assets/img/slgtilogo.png',
            BASE_PATH . '/public/images/slgti-logo.png',
        ];
        foreach ($paths as $p) {
            if (!is_file($p)) {
                continue;
            }
            $ext = strtolower(pathinfo($p, PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg'], true)) {
                $mime = $ext === 'jpg' ? 'jpeg' : $ext;

                return 'data:image/' . $mime . ';base64,' . base64_encode((string) file_get_contents($p));
            }
        }

        return '';
    }

    /**
     * @param array<string, mixed> $complaint
     * @param list<array<string, mixed>> $students
     */
    public static function renderHtml(array $complaint, array $students): string {
        $path = BASE_PATH . '/views/complaint-letters/pdf/letter.php';
        if (!is_file($path)) {
            throw new RuntimeException('Complaint letter PDF template not found.');
        }
        $logoSrc = self::logoDataUri();
        extract([
            'complaint' => $complaint,
            'students' => $students,
            'logoSrc' => $logoSrc,
        ], EXTR_SKIP);
        ob_start();
        include $path;

        return (string) ob_get_clean();
    }

    /**
     * @param array<string, mixed> $complaint
     * @param list<array<string, mixed>> $students
     */
    public static function streamPdf(array $complaint, array $students, bool $attachment = true): void {
        $html = self::renderHtml($complaint, $students);
        $ref = preg_replace('/[^a-zA-Z0-9._-]+/', '_', (string) ($complaint['reference_no'] ?? 'complaint')) ?: 'complaint';
        if (count($students) > 1) {
            $ref .= '-per-student';
        }
        ExamPdfHelper::loadDompdf();
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Serif');
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $filename = $ref . '.pdf';
        $dompdf->stream($filename, ['Attachment' => $attachment]);
        exit;
    }
}
