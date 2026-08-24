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

    /** Printable area margins (top/bottom, left/right). */
    public static function pageMarginCss(): string {
        return '15mm 25mm';
    }

    /** Allowed HTML tags for complaint letter rich text. */
    public static function sanitizeLetterHtml(string $html): string {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        $html = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', $html) ?? $html;

        $html = preg_replace_callback('/\sstyle=(["\'])(.*?)\1/i', static function (array $m): string {
            $clean = self::sanitizeInlineStyle($m[2]);
            if ($clean === '') {
                return '';
            }

            return ' style="' . htmlspecialchars($clean, ENT_QUOTES, 'UTF-8') . '"';
        }, $html) ?? $html;

        $allowed = '<p><br><strong><b><em><i><u><s><sub><sup><ul><ol><li><blockquote>'
            . '<h1><h2><h3><h4><span><div><table><thead><tbody><tr><th><td><hr><a><img>';
        $html = strip_tags($html, $allowed);

        $html = preg_replace_callback('/<a\b[^>]*>/i', static function (array $m): string {
            if (preg_match('/\shref=(["\'])(.*?)\1/i', $m[0], $href)) {
                $url = trim($href[2]);
                if ($url !== '' && !preg_match('/^\s*javascript:/i', $url)) {
                    return '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">';
                }
            }

            return '<a>';
        }, $html) ?? $html;

        $html = preg_replace_callback('/<img\b[^>]*>/i', static function (array $m): string {
            if (preg_match('/\ssrc=(["\'])(.*?)\1/i', $m[0], $src)) {
                $url = trim($src[2]);
                if (preg_match('/^(https?:|data:image\/)/i', $url)) {
                    return '<img src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" alt="">';
                }
            }

            return '';
        }, $html) ?? $html;

        return $html;
    }

    private static function sanitizeInlineStyle(string $style): string {
        $safe = [];
        foreach (explode(';', $style) as $rule) {
            $rule = trim($rule);
            if ($rule === '' || strpos($rule, ':') === false) {
                continue;
            }
            [$prop, $val] = array_map('trim', explode(':', $rule, 2));
            $prop = strtolower($prop);
            $allowed = ['font-size', 'font-family', 'color', 'background-color', 'text-align', 'font-weight', 'font-style', 'text-decoration', 'line-height'];
            if (!in_array($prop, $allowed, true)) {
                continue;
            }
            if (preg_match('/expression|javascript|url\s*\(/i', $val)) {
                continue;
            }
            $safe[] = $prop . ': ' . $val;
        }

        return implode('; ', $safe);
    }

    /** Render stored complaint content (plain text or rich HTML). */
    public static function formatLetterContent(?string $content): string {
        $content = trim((string) $content);
        if ($content === '') {
            return '';
        }
        if ($content === strip_tags($content)) {
            return nl2br(htmlspecialchars($content, ENT_QUOTES, 'UTF-8'));
        }

        return self::sanitizeLetterHtml($content);
    }

    /** Prepare complaint body for the rich-text editor (plain text → paragraphs). */
    public static function prepareEditorContent(?string $content, ?string $defaultHtml = null): string {
        $defaultHtml ??= '<p>We wish to bring to your attention a matter concerning your ward\'s conduct at the institute.</p>'
            . '<p>[Describe the incident, dates, and impact.]</p>'
            . '<p>We request your cooperation in addressing this matter.</p>';

        $content = trim((string) $content);
        if ($content === '') {
            return $defaultHtml;
        }
        if ($content === strip_tags($content)) {
            $parts = preg_split('/\R\s*\R/', $content) ?: [];
            $parts = array_values(array_filter(array_map('trim', $parts), static fn (string $p): bool => $p !== ''));
            if ($parts === []) {
                $parts = [$content];
            }

            return implode('', array_map(
                static fn (string $p): string => '<p>' . htmlspecialchars($p, ENT_QUOTES, 'UTF-8') . '</p>',
                $parts
            ));
        }

        return $content;
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
            . '.cl-body{text-align:left;font-size:9.5pt;line-height:1.5;margin:0 0 2.5mm 0;word-wrap:break-word;overflow-wrap:break-word;}'
            . '.cl-body p{margin:0 0 2.5mm 0;text-align:inherit;}'
            . '.cl-body div{margin:0 0 2.5mm 0;text-align:inherit;}'
            . '.cl-body span{text-align:inherit;}'
            . '.cl-body ul,.cl-body ol{margin:0 0 2.5mm 0;padding-left:6mm;}'
            . '.cl-body li{margin:0 0 1mm 0;}'
            . '.cl-body blockquote{margin:0 0 2.5mm 0;padding-left:4mm;border-left:1pt solid #ccc;}'
            . '.cl-body table{width:100%;border-collapse:collapse;margin:0 0 2.5mm 0;}'
            . '.cl-body th,.cl-body td{border:0.6pt solid #666;padding:1.2mm 2mm;font-size:8.5pt;}'
            . '.cl-body a{color:#111;text-decoration:underline;}'
            . '.cl-body img{max-width:100%;height:auto;}'
            . '.cl-body-action{margin-bottom:0;}'
            . '.cl-action-title{font-size:8.5pt;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;margin:3mm 0 1.5mm 0;color:#222;text-align:left;}';
    }

    /** @page + root rules for PDF output (one A4 sheet per letter). */
    public static function pdfPageStylesheet(): string {
        return ''
            . '@page{size:A4 portrait;margin:' . self::pageMarginCss() . ';}'
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
