<?php
/**
 * Attendance warning letter PDF helper (Dompdf).
 */
declare(strict_types=1);

require_once BASE_PATH . '/helpers/ExamPdfHelper.php';
require_once BASE_PATH . '/helpers/ComplaintLetterPdfHelper.php';

class AttendanceWarningLetterHelper {
    /**
     * @param array<string,mixed> $student
     * @param array<string,mixed> $meta
     */
    public static function renderHtml(array $student, array $meta): string {
        $path = BASE_PATH . '/views/attendance/student_device/warning_letter_body.php';
        if (!is_file($path)) {
            throw new RuntimeException('Warning letter template not found.');
        }
        $logoSrc = ComplaintLetterPdfHelper::logoDataUri();
        $institute = ComplaintLetterPdfHelper::institutePostFrom();
        extract([
            'student' => $student,
            'meta' => $meta,
            'logoSrc' => $logoSrc,
            'institute' => $institute,
        ], EXTR_SKIP);
        ob_start();
        include $path;
        return (string) ob_get_clean();
    }

    /**
     * @param array<string,mixed> $student
     * @param array<string,mixed> $meta
     */
    public static function streamPdf(array $student, array $meta, bool $attachment = true): void {
        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>'
            . ComplaintLetterPdfHelper::pdfPageStylesheet()
            . ComplaintLetterPdfHelper::complaintLetterStylesheet()
            . self::extraCss()
            . '</style></head><body>'
            . self::renderHtml($student, $meta)
            . '</body></html>';

        ExamPdfHelper::loadDompdf();
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Serif');
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $sid = preg_replace('/[^a-zA-Z0-9._-]+/', '_', (string) ($student['student_id'] ?? 'student')) ?: 'student';
        $month = preg_replace('/[^0-9-]+/', '', (string) ($meta['report_month'] ?? '')) ?: 'month';
        $dompdf->stream('attendance-warning-' . $sid . '-' . $month . '.pdf', ['Attachment' => $attachment]);
        exit;
    }

    private static function extraCss(): string {
        return '.awl-dates{margin:0 0 3mm 0;padding-left:5mm;}'
            . '.awl-dates li{margin:0 0 1mm 0;font-size:9pt;}'
            . '.awl-sign{margin-top:10mm;}'
            . '.awl-sign-line{margin-top:12mm;border-top:0.7pt solid #111;width:55mm;}'
            . '.awl-sign-role{font-size:8.5pt;margin-top:1mm;}';
    }
}
