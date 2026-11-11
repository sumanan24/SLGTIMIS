<?php
/**
 * Official SLGTI DL long envelope — print-ready.
 *
 * Page: 220 mm × 110 mm (ISO 269 DL, landscape proportions).
 * Header: envelope_header.png (logo left + Sinhala / Tamil / English centre).
 * Body: equal FROM | TO columns (no vertical divider).
 * Footer: thin rule + contact line.
 *
 * Dompdf note: setPaper([0,0,220mm,110mm], 'portrait') — custom size already
 * encodes landscape; do not pass orientation=landscape or Dompdf will swap axes.
 *
 * @var list<array{name:string,lines:list<string>}> $envelopes
 * @var string $headerImage Absolute path of the header artwork
 * @var callable $e HTML escaper
 */
$envelopes = $envelopes ?? [];
$headerImage = $headerImage ?? '';
$e = $e ?? static function ($v): string {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};
$total = count($envelopes);
?>
<style>
    /* Exact DL page box */
    @page {
        size: 220mm 110mm;
        margin: 5mm 12mm 5mm 12mm;
    }

    html, body {
        margin: 0;
        padding: 0;
        width: 100%;
        font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
        color: #111;
        font-size: 10pt;
    }

    .env {
        width: 100%;
        page-break-inside: avoid;
        overflow: hidden;
    }
    .env + .env {
        page-break-before: always;
    }

    /* ---- Header: logo left + trilingual name (in artwork) ---- */
    .hdr {
        width: 100%;
        margin: 0 0 5mm 0;
        padding: 0;
        text-align: left;
        overflow: hidden;
    }
    .hdr img {
        display: block;
        width: 100%;
        height: auto;
        max-height: 20mm;
        object-fit: contain;
        object-position: left center;
    }

    /* ---- Address: two equal columns, horizontally aligned ---- */
    table.addr {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        margin: 0;
    }
    td.from,
    td.to {
        width: 50%;
        vertical-align: top;
        padding: 0;
    }
    td.from {
        padding-right: 6mm;
    }
    td.to {
        padding-left: 6mm;
    }

    .lbl {
        font-size: 9pt;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: #333;
        margin: 0 0 2.2mm 0;
        line-height: 1.15;
    }

    .from-line {
        font-size: 9.5pt;
        font-weight: 700;
        line-height: 1.45;
        margin: 0 0 0.35mm 0;
        color: #111;
    }

    .to-name {
        font-size: 12pt;
        font-weight: 700;
        line-height: 1.35;
        margin: 0 0 1.6mm 0;
        color: #000;
        word-wrap: break-word;
    }
    .to-line {
        font-size: 10pt;
        font-weight: 400;
        line-height: 1.4;
        margin: 0 0 0.45mm 0;
        color: #111;
        word-wrap: break-word;
    }

    /* ---- Footer ---- */
    .foot {
        margin-top: 8mm;
        padding-top: 2.2mm;
        padding-bottom: 1mm;
        border-top: 0.6pt solid #888;
        text-align: center;
        font-size: 7pt;
        font-weight: 700;
        line-height: 1.35;
        color: #333;
        white-space: nowrap;
    }
</style>
<?php foreach ($envelopes as $i => $env):
    $name = trim((string) ($env['name'] ?? ''));
    $lines = [];
    foreach (($env['lines'] ?? []) as $line) {
        $line = trim((string) $line);
        if ($line !== '') {
            $lines[] = $line;
        }
    }
    if (count($lines) > 5) {
        $lines = array_slice($lines, 0, 5);
    }
?>
<div class="env">
    <?php if ($headerImage !== ''): ?>
        <div class="hdr">
            <img src="<?php echo $e($headerImage); ?>" alt="SLGTI — Sri Lanka German Training Institute">
        </div>
    <?php endif; ?>

    <table class="addr" role="presentation">
        <tr>
            <td class="from">
                <div class="lbl">From</div>
                <div class="from-line">Sri Lanka German Training Institute</div>
                <div class="from-line">Ariviyal Nagar,</div>
                <div class="from-line">Kilinochchi 44000,</div>
                <div class="from-line">Sri Lanka.</div>
            </td>
            <td class="to">
                <div class="lbl">To</div>
                <?php if ($name !== ''): ?>
                    <div class="to-name"><?php echo $e($name); ?></div>
                <?php endif; ?>
                <?php foreach ($lines as $line): ?>
                    <div class="to-line"><?php echo $e($line); ?></div>
                <?php endforeach; ?>
            </td>
        </tr>
    </table>

    <div class="foot">
        Tel: +94 21 492 7799&nbsp;&nbsp;|&nbsp;&nbsp;Mobile: +94 70 306 0138&nbsp;&nbsp;|&nbsp;&nbsp;Website: www.slgti.ac.lk&nbsp;&nbsp;|&nbsp;&nbsp;Email: info@slgti.ac.lk
    </div>
</div>
<?php endforeach; ?>
