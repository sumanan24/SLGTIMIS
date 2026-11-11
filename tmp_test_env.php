<?php
define('BASE_PATH', 'c:/wamp64/www/SLGTIMIS');
require BASE_PATH . '/vendor/autoload.php';
require BASE_PATH . '/helpers/ExamPdfHelper.php';
require BASE_PATH . '/helpers/ApplicationAdmissionPdfHelper.php';

$viewPath = BASE_PATH . '/views/students/pdf/envelope.php';
$headerFile = BASE_PATH . '/assets/img/envelope_header.png';
$headerImage = is_file($headerFile) ? str_replace('\\', '/', $headerFile) : '';
$e = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$envelopes = [[
    'name' => 'Miss Thavarajasingam Atputha',
    'lines' => ['Kaaliyaanai, Puttur East Puttur.', 'Jaffna 40000'],
]];
extract(compact('envelopes', 'headerImage', 'e'), EXTR_SKIP);
ob_start();
include $viewPath;
$inner = ob_get_clean();
$html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Envelopes</title></head><body>' . $inner . '</body></html>';

$paper = ApplicationAdmissionPdfHelper::envelopePaperSize();
$bytes = ExamPdfHelper::renderPdfBytes($html, $paper, ApplicationAdmissionPdfHelper::ENVELOPE_ORIENTATION);
$out = BASE_PATH . '/tmp_envelope_sample.pdf';
file_put_contents($out, $bytes);

// Verify MediaBox via FPDI
$pdf = new \setasign\Fpdi\Fpdi();
$pageCount = $pdf->setSourceFile($out);
$tpl = $pdf->importPage(1);
$size = $pdf->getTemplateSize($tpl);
// Fpdi default unit is mm — getTemplateSize returns millimetres
$wMm = (float) ($size['width'] ?? 0);
$hMm = (float) ($size['height'] ?? 0);
$wPt = $wMm * 72 / 25.4;
$hPt = $hMm * 72 / 25.4;
$paper = ApplicationAdmissionPdfHelper::envelopePaperSize();

echo 'pages=' . $pageCount . PHP_EOL;
echo 'media_mm=' . round($wMm, 2) . ' x ' . round($hMm, 2) . PHP_EOL;
echo 'media_pt=' . round($wPt, 2) . ' x ' . round($hPt, 2) . PHP_EOL;
echo 'setPaper_pt=' . round($paper[2], 2) . ' x ' . round($paper[3], 2) . PHP_EOL;
echo 'expected_mm=220 x 110' . PHP_EOL;
echo 'ok_size=' . (abs($wMm - 220) < 0.5 && abs($hMm - 110) < 0.5 ? 'YES' : 'NO') . PHP_EOL;
echo 'landscape=' . ($wMm > $hMm ? 'YES' : 'NO') . PHP_EOL;
echo 'bytes=' . strlen($bytes) . PHP_EOL;
