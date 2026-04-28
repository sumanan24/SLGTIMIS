<?php
/**
 * Dompdf-based PDF output for Exam module.
 * Requires: composer install (dompdf/dompdf). See project composer.json.
 */

class ExamPdfHelper {

    public static function dompdfAvailable(): bool {
        $autoload = defined('BASE_PATH') ? BASE_PATH . '/vendor/autoload.php' : dirname(__DIR__) . '/vendor/autoload.php';
        return is_file($autoload);
    }

    /**
     * @throws RuntimeException
     */
    public static function loadDompdf(): void {
        if (!self::dompdfAvailable()) {
            throw new RuntimeException(
                'Dompdf is not installed. From the project root run: composer install'
            );
        }
        require_once BASE_PATH . '/vendor/autoload.php';
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function renderTemplate(string $relativeView, array $data): string {
        $path = BASE_PATH . '/views/exams/pdf/' . $relativeView;
        if (!is_file($path)) {
            throw new RuntimeException('PDF template not found: ' . $relativeView);
        }
        extract($data, EXTR_SKIP);
        ob_start();
        include $path;
        return (string) ob_get_clean();
    }

    /**
     * Stream PDF download to browser.
     *
     * @throws RuntimeException
     */
    public static function streamHtml(string $html, string $filename, $paper = 'A4', string $orientation = 'portrait'): void {
        self::loadDompdf();
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper($paper, $orientation);
        $dompdf->render();
        $safe = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $filename) ?: 'document.pdf';
        if (substr($safe, -4) !== '.pdf') {
            $safe .= '.pdf';
        }
        $dompdf->stream($safe, ['Attachment' => true]);
        exit;
    }

    /**
     * Render HTML to PDF bytes (no output/headers).
     *
     * @throws RuntimeException
     */
    public static function renderPdfBytes(string $html, $paper = 'A4', string $orientation = 'portrait'): string {
        self::loadDompdf();
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper($paper, $orientation);
        $dompdf->render();
        return (string) $dompdf->output();
    }
}
