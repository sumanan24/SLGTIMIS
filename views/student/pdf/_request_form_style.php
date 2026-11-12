<?php
/**
 * Shared look for the one-page A4 request forms (hostel, bus season).
 * Plain black-on-white, label + underlined value, no heavy boxes.
 */
?>
<style>
@page { size: A4 portrait; margin: 0; }
* { box-sizing: border-box; }
html, body { margin: 0; padding: 0; background: #fff; }
/* dompdf ignores @page margins here, so the page margin is body padding (A4 minus 0.5mm safety) */
body { font-family: DejaVu Sans, Helvetica, Arial, sans-serif; font-size: 9pt; color: #111; line-height: 1.25; padding: 8mm 15mm 0 15mm; }

/* Letterhead */
.lh { width: 100%; border-collapse: collapse; table-layout: fixed; }
.lh td { padding: 0; vertical-align: middle; }
.lh .c-logo { width: 24%; }
.lh .c-logo img { height: 12mm; width: auto; display: block; }
.lh .c-mid { width: 52%; text-align: center; }
.lh .c-side { width: 24%; text-align: right; }
.inst-name { font-size: 10.5pt; font-weight: bold; letter-spacing: 0.02em; text-transform: uppercase; margin: 0 0 0.8mm; }
.inst-meta { font-size: 7.8pt; color: #444; line-height: 1.4; }
.photo {
    display: inline-block; width: 22mm; height: 27mm; border: 0.7pt solid #555;
    text-align: center; font-size: 6.5pt; color: #666; padding-top: 9mm; line-height: 1.3;
}
.rule { border: 0; border-top: 1.2pt solid #111; margin: 2mm 0 2.5mm; height: 0; }

/* Title */
.title { text-align: center; font-size: 14pt; font-weight: bold; letter-spacing: 0.12em; text-transform: uppercase; margin: 0; }
.sub { text-align: center; font-size: 8.5pt; color: #444; margin: 1mm 0 2mm; }

/* Section heading */
.sec {
    font-size: 8.8pt; font-weight: bold; letter-spacing: 0.07em; text-transform: uppercase;
    border-bottom: 0.8pt solid #111; padding-bottom: 0.8mm; margin: 4.5mm 0 0.8mm;
}

/* Field rows: label | value | gap | label | value */
table.f { width: 100%; border-collapse: collapse; table-layout: fixed; }
table.f td { padding: 0; height: 7.6mm; vertical-align: bottom; }
table.f td.l { width: 19%; font-size: 8.4pt; color: #444; padding-right: 2mm; white-space: nowrap; }
table.f td.v {
    width: 29%; border-bottom: 0.5pt solid #555; padding: 0 1mm 0.7mm;
    font-size: 9pt; font-weight: bold; text-transform: uppercase; overflow: hidden;
}
table.f td.gap { width: 4%; }
table.f td.v0 { width: 29%; }
table.f tr.sp td { height: 0; line-height: 0; font-size: 0; padding: 0; border: 0; }
table.f td.tall { height: 13mm; }
table.f td.top { vertical-align: top; padding-top: 1.6mm; }

/* Tick boxes */
.chk {
    display: inline-block; width: 3.1mm; height: 3.1mm; border: 0.7pt solid #333;
    margin: 0 1.2mm 0 0; vertical-align: middle; text-align: center;
    font-size: 6pt; line-height: 2.9mm; background: #fff;
}
.chk.on { background: #111; color: #fff; border-color: #111; font-weight: bold; }
.opt { display: inline-block; margin-right: 5mm; white-space: nowrap; font-size: 8.6pt; }

/* Hand-written lines */
.lines { margin-top: 0.5mm; }
.lines .ln { height: 7.6mm; border-bottom: 0.5pt solid #555; }

.decl { font-size: 8.4pt; line-height: 1.55; text-align: justify; margin: 2.5mm 0 0; color: #222; }

/* Signatures */
table.sig { width: 100%; border-collapse: collapse; table-layout: fixed; margin-top: 5mm; }
table.sig td { vertical-align: bottom; padding: 0; }
table.sig td.gap { width: 10%; }
table.sig td.box { width: 45%; }
.sig-line { height: 14mm; border-bottom: 0.7pt solid #111; }
.sig-lab { font-size: 8.2pt; font-weight: bold; margin-top: 1mm; }
.sig-sub { font-size: 7.8pt; color: #444; margin-top: 0.4mm; text-transform: uppercase; }

/* Office use */
.office { border: 0.7pt solid #555; margin-top: 7mm; }
.office .oh { font-size: 8pt; font-weight: bold; letter-spacing: 0.07em; text-transform: uppercase; padding: 1.2mm 2.5mm; border-bottom: 0.7pt solid #555; background: #f2f2f2; }
.office table { width: 100%; border-collapse: collapse; table-layout: fixed; }
.office td { width: 33.33%; padding: 1.4mm 2.5mm 1.6mm; vertical-align: top; border-left: 0.5pt solid #bbb; }
.office td:first-child { border-left: 0; }
.office table.room { width: 100%; border-collapse: collapse; table-layout: fixed; border-bottom: 0.5pt solid #bbb; }
.office table.room td { padding: 0; border: 0; vertical-align: bottom; height: 8.5mm; }
.office table.room td.rl { width: 16%; font-size: 8pt; font-weight: bold; padding: 0 1.5mm 0 3mm; white-space: nowrap; }
.office table.room td.rv {
    width: 34%; border-bottom: 0.5pt solid #555; padding: 0 1.5mm 0.6mm;
    font-size: 8.5pt; font-weight: bold; text-transform: uppercase;
}
.office .ot { font-size: 8pt; font-weight: bold; margin-bottom: 0.8mm; }
.office .oo { font-size: 8.2pt; margin-bottom: 0.6mm; }
.office .ol { border-bottom: 0.5pt solid #555; height: 6mm; }
.office .os { font-size: 7.2pt; color: #555; margin-top: 0.5mm; }
.foot { margin-top: 2.5mm; padding-top: 1mm; border-top: 0.5pt solid #bbb; font-size: 7pt; color: #666; text-align: center; }
</style>
